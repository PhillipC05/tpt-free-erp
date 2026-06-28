<?php

namespace App\Http\Controllers\Api;

use App\Models\Fleet\FuelLog;
use App\Models\Fleet\MaintenanceRecord;
use App\Models\Fleet\Trip;
use App\Models\Fleet\Vehicle;
use App\Models\Notification\NotificationMessage;
use App\Models\Subscription\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends BaseApiController
{
    public function kpis(Request $request): JsonResponse
    {
        $period = $request->query('period', 'month');
        $startDate = match ($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        $prevStart = match ($period) {
            'week' => now()->subWeeks(2),
            'month' => now()->subMonths(2),
            'quarter' => now()->subQuarters(2),
            'year' => now()->subYears(2),
            default => now()->subMonths(2),
        };
        $prevEnd = $startDate;

        $revenue = DB::table('sales_orders')->where('status', 'delivered')
            ->where('created_at', '>=', $startDate)->sum('total_amount');
        $prevRevenue = DB::table('sales_orders')->where('status', 'delivered')
            ->where('created_at', '>=', $prevStart)->where('created_at', '<', $prevEnd)->sum('total_amount');
        $revenueTrend = $prevRevenue > 0 ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0;

        $orders = DB::table('sales_orders')->where('created_at', '>=', $startDate)->count();
        $prevOrders = DB::table('sales_orders')->where('created_at', '>=', $prevStart)->where('created_at', '<', $prevEnd)->count();
        $ordersTrend = $prevOrders > 0 ? round((($orders - $prevOrders) / $prevOrders) * 100, 1) : 0;

        $fuelCost = FuelLog::where('date', '>=', $startDate->toDateString())->sum('total_cost');
        $maintenanceCost = MaintenanceRecord::where('status', 'completed')
            ->where('completed_date', '>=', $startDate->toDateString())->sum('cost');
        $fleetCost = $fuelCost + $maintenanceCost;

        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $mrr = Subscription::where('status', 'active')
            ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
            ->selectRaw('COALESCE(sum(subscription_plans.price * subscriptions.quantity * (1 - subscriptions.discount_percent / 100)), 0) as mrr')
            ->value('mrr') ?? 0;

        $tripsThisPeriod = Trip::where('status', 'completed')
            ->where('end_time', '>=', $startDate)->count();
        $totalDistance = Trip::where('status', 'completed')
            ->where('end_time', '>=', $startDate)->sum('distance');

        $activeVehicles = Vehicle::where('status', 'active')->count();
        $vehiclesInMaintenance = Vehicle::where('status', 'maintenance')->count();

        $newCustomers = DB::table('sales_customers')
            ->where('created_at', '>=', $startDate)->count();

        $pendingOrders = DB::table('sales_orders')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->count();

        return $this->respond([
            'success' => true,
            'data' => [
                'revenue' => ['current' => round($revenue, 2), 'trend' => $revenueTrend],
                'orders' => ['current' => $orders, 'trend' => $ordersTrend],
                'new_customers' => $newCustomers,
                'pending_orders' => $pendingOrders,
                'fleet_costs' => round($fleetCost, 2),
                'fleet_distance_km' => round($totalDistance, 1),
                'trips_completed' => $tripsThisPeriod,
                'mrr' => round($mrr, 2),
                'active_subscriptions' => $activeSubscriptions,
                'active_vehicles' => $activeVehicles,
                'vehicles_in_maintenance' => $vehiclesInMaintenance,
                'period' => $period,
            ],
        ]);
    }

    public function charts(Request $request): JsonResponse
    {
        $months = (int) $request->query('months', 6);

        $revenueTrend = DB::table('sales_orders')
            ->where('status', 'delivered')
            ->where('created_at', '>=', now()->subMonths($months))
            ->selectRaw("strftime('%Y-%m', created_at) as month, sum(total_amount) as value")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $ordersTrend = DB::table('sales_orders')
            ->where('created_at', '>=', now()->subMonths($months))
            ->selectRaw("strftime('%Y-%m', created_at) as month, count(*) as value")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $fleetFuelCost = FuelLog::where('date', '>=', now()->subMonths($months)->toDateString())
            ->selectRaw("strftime('%Y-%m', date) as month, sum(total_cost) as value")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $fleetMaintenanceCost = MaintenanceRecord::where('status', 'completed')
            ->where('completed_date', '>=', now()->subMonths($months)->toDateString())
            ->selectRaw("strftime('%Y-%m', completed_date) as month, sum(cost) as value")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $subscriptionMrr = DB::table('subscriptions')
            ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
            ->where('subscriptions.status', 'active')
            ->selectRaw("'current' as period, sum(subscription_plans.price * subscriptions.quantity * (1 - subscriptions.discount_percent / 100)) as value")
            ->groupBy('period')
            ->get();

        $topProducts = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.order_id', '=', 'sales_orders.id')
            ->join('inventory_products', 'sales_order_items.product_id', '=', 'inventory_products.id')
            ->where('sales_orders.status', 'delivered')
            ->where('sales_orders.created_at', '>=', now()->subMonths($months))
            ->select('inventory_products.name', DB::raw('sum(sales_order_items.quantity) as total_quantity'), DB::raw('sum(sales_order_items.line_total) as total_revenue'))
            ->groupBy('inventory_products.id', 'inventory_products.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        $fleetCostByType = MaintenanceRecord::where('status', 'completed')
            ->where('completed_date', '>=', now()->subMonths($months)->toDateString())
            ->select('type', DB::raw('sum(cost) as value'))
            ->groupBy('type')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'revenue_trend' => $revenueTrend,
                'orders_trend' => $ordersTrend,
                'fleet_fuel_cost_trend' => $fleetFuelCost,
                'fleet_maintenance_cost_trend' => $fleetMaintenanceCost,
                'subscription_mrr' => $subscriptionMrr,
                'top_products' => $topProducts,
                'fleet_cost_by_type' => $fleetCostByType,
            ],
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 30), 100);

        $recentOrders = DB::table('sales_orders')
            ->join('sales_customers', 'sales_orders.customer_id', '=', 'sales_customers.id')
            ->select('sales_orders.id', 'sales_orders.order_number', 'sales_orders.status', 'sales_orders.created_at', 'sales_customers.name as customer_name')
            ->orderByDesc('sales_orders.created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => ['type' => 'order', 'label' => "Order {$item->order_number}", 'detail' => "{$item->customer_name} — {$item->status}", 'created_at' => $item->created_at]);

        $recentTrips = Trip::with('vehicle:id,vehicle_code')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => ['type' => 'trip', 'label' => "Trip {$item->trip_number}", 'detail' => "{$item->vehicle->vehicle_code} — {$item->status}", 'created_at' => $item->created_at]);

        $recentMaintenance = MaintenanceRecord::with('vehicle:id,vehicle_code')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => ['type' => 'maintenance', 'label' => $item->title, 'detail' => "{$item->vehicle->vehicle_code} — {$item->status}", 'created_at' => $item->created_at]);

        $recentNotifications = NotificationMessage::where('channel', 'in_app')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => ['type' => 'notification', 'label' => $item->subject ?? 'Notification', 'detail' => $item->body, 'created_at' => $item->created_at]);

        $activity = $recentOrders->concat($recentTrips)->concat($recentMaintenance)->concat($recentNotifications)
            ->sortByDesc('created_at')
            ->values()
            ->take($limit);

        return $this->respond([
            'success' => true,
            'data' => $activity,
        ]);
    }

    public function moduleSummary(): JsonResponse
    {
        $finance = [
            'total_assets' => DB::table('finance_accounts')->where('type', 'asset')->sum('current_balance'),
            'total_liabilities' => DB::table('finance_accounts')->where('type', 'liability')->sum('current_balance'),
            'total_revenue' => DB::table('finance_accounts')->where('type', 'revenue')->sum('current_balance'),
            'total_expenses' => DB::table('finance_accounts')->where('type', 'expense')->sum('current_balance'),
        ];

        $inventory = [
            'total_products' => DB::table('inventory_products')->where('is_active', true)->count(),
            'total_stock_value' => DB::table('inventory_stock')
                ->join('inventory_products', 'inventory_stock.product_id', '=', 'inventory_products.id')
                ->selectRaw('sum(inventory_stock.quantity * inventory_products.cost_price) as value')
                ->value('value') ?? 0,
        ];

        $sales = [
            'total_customers' => DB::table('sales_customers')->count(),
            'total_orders' => DB::table('sales_orders')->count(),
            'pending_orders' => DB::table('sales_orders')->whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'total_revenue' => DB::table('sales_orders')->where('status', 'delivered')->sum('total_amount'),
        ];

        $fleet = [
            'active_vehicles' => Vehicle::where('status', 'active')->count(),
            'total_vehicles' => Vehicle::count(),
            'pending_maintenance' => MaintenanceRecord::where('status', 'scheduled')->count(),
        ];

        $hr = [
            'active_employees' => DB::table('hr_employees')->where('status', 'active')->count(),
            'pending_leave' => DB::table('hr_leave_requests')->where('status', 'pending')->count(),
        ];

        $subscription = [
            'active_count' => DB::table('subscriptions')->where('status', 'active')->count(),
            'mrr' => DB::table('subscriptions')
                ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
                ->where('subscriptions.status', 'active')
                ->selectRaw('COALESCE(sum(subscription_plans.price * subscriptions.quantity * (1 - subscriptions.discount_percent / 100)), 0) as mrr')
                ->value('mrr') ?? 0,
        ];

        return $this->respond([
            'success' => true,
            'data' => compact('finance', 'inventory', 'sales', 'fleet', 'hr', 'subscription'),
        ]);
    }
}
