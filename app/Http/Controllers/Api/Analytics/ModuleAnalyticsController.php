<?php

namespace App\Http\Controllers\Api\Analytics;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleAnalyticsController extends BaseApiController
{
    public function finance(Request $request): JsonResponse
    {
        $months = (int) $request->query('months', 6);
        $startDate = now()->subMonths($months);

        $revenueByAccount = DB::table('finance_accounts')
            ->where('type', 'revenue')
            ->select('id', 'name', 'code', DB::raw('current_balance as value'))
            ->orderByDesc('current_balance')
            ->get();

        $expenseByCategory = DB::table('finance_accounts')
            ->where('type', 'expense')
            ->select('id', 'name', 'code', DB::raw('current_balance as value'))
            ->orderByDesc('current_balance')
            ->get();

        $budgetUtilization = DB::table('finance_budgets')
            ->where('status', 'active')
            ->select(
                'id',
                'name',
                'budgeted_amount',
                'actual_amount',
                DB::raw('CASE WHEN budgeted_amount > 0 THEN round((actual_amount / budgeted_amount) * 100, 1) ELSE 0 END as utilization_percent')
            )
            ->orderByDesc('budgeted_amount')
            ->get();

        $cashFlowTrend = DB::table('finance_transactions')
            ->join('finance_accounts', 'finance_transactions.account_id', '=', 'finance_accounts.id')
            ->where('finance_transactions.transaction_date', '>=', $startDate->toDateString())
            ->where('finance_transactions.status', 'posted')
            ->selectRaw(
                "strftime('%Y-%m', finance_transactions.transaction_date) as month,
                 SUM(CASE WHEN finance_transactions.type = 'credit' THEN finance_transactions.amount ELSE 0 END) as credits,
                 SUM(CASE WHEN finance_transactions.type = 'debit' THEN finance_transactions.amount ELSE 0 END) as debits"
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $this->respondSuccess('Finance analytics retrieved', [
            'revenue_by_account' => $revenueByAccount,
            'expense_by_category' => $expenseByCategory,
            'budget_utilization' => $budgetUtilization,
            'cash_flow_trend' => $cashFlowTrend,
        ]);
    }

    public function inventory(Request $request): JsonResponse
    {
        $months = (int) $request->query('months', 6);
        $startDate = now()->subMonths($months);

        $stockLevelsByWarehouse = DB::table('inventory_stock')
            ->join('inventory_warehouses', 'inventory_stock.warehouse_id', '=', 'inventory_warehouses.id')
            ->join('inventory_products', 'inventory_stock.product_id', '=', 'inventory_products.id')
            ->select(
                'inventory_warehouses.id as warehouse_id',
                'inventory_warehouses.name as warehouse_name',
                DB::raw('COUNT(DISTINCT inventory_stock.product_id) as product_count'),
                DB::raw('SUM(inventory_stock.quantity) as total_quantity'),
                DB::raw('SUM(inventory_stock.quantity * inventory_products.cost_price) as total_value')
            )
            ->groupBy('inventory_warehouses.id', 'inventory_warehouses.name')
            ->orderByDesc('total_value')
            ->get();

        $topProductsByRevenue = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.order_id', '=', 'sales_orders.id')
            ->join('inventory_products', 'sales_order_items.product_id', '=', 'inventory_products.id')
            ->where('sales_orders.status', 'delivered')
            ->where('sales_orders.created_at', '>=', $startDate)
            ->select(
                'inventory_products.id',
                'inventory_products.name',
                'inventory_products.sku',
                DB::raw('SUM(sales_order_items.quantity) as total_quantity'),
                DB::raw('SUM(sales_order_items.line_total) as total_revenue')
            )
            ->groupBy('inventory_products.id', 'inventory_products.name', 'inventory_products.sku')
            ->orderByDesc('total_revenue')
            ->limit(15)
            ->get();

        $lowStockAlerts = DB::table('inventory_products')
            ->join('inventory_stock', 'inventory_products.id', '=', 'inventory_stock.product_id')
            ->where('inventory_products.is_active', true)
            ->select(
                'inventory_products.id',
                'inventory_products.name',
                'inventory_products.sku',
                'inventory_products.min_stock_level',
                DB::raw('SUM(inventory_stock.quantity) as current_stock')
            )
            ->groupBy('inventory_products.id', 'inventory_products.name', 'inventory_products.sku', 'inventory_products.min_stock_level')
            ->havingRaw('current_stock <= inventory_products.min_stock_level AND inventory_products.min_stock_level > 0')
            ->orderBy('current_stock')
            ->get();

        $stockMovementHistory = DB::table('inventory_stock_movements')
            ->join('inventory_products', 'inventory_stock_movements.product_id', '=', 'inventory_products.id')
            ->join('inventory_warehouses', 'inventory_stock_movements.warehouse_id', '=', 'inventory_warehouses.id')
            ->where('inventory_stock_movements.movement_date', '>=', $startDate)
            ->selectRaw(
                "strftime('%Y-%m', inventory_stock_movements.movement_date) as month,
                 type,
                 COUNT(*) as count,
                 SUM(quantity) as total_quantity"
            )
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get();

        return $this->respondSuccess('Inventory analytics retrieved', [
            'stock_levels_by_warehouse' => $stockLevelsByWarehouse,
            'top_products_by_revenue' => $topProductsByRevenue,
            'low_stock_alerts' => $lowStockAlerts,
            'stock_movement_history' => $stockMovementHistory,
        ]);
    }

    public function hr(Request $request): JsonResponse
    {
        $headcountByDepartment = DB::table('hr_employees')
            ->leftJoin('hr_departments', 'hr_employees.department_id', '=', 'hr_departments.id')
            ->where('hr_employees.status', 'active')
            ->select(
                'hr_departments.id as department_id',
                'hr_departments.name as department_name',
                DB::raw('COUNT(*) as headcount'),
                DB::raw('AVG(hr_employees.salary) as avg_salary')
            )
            ->groupBy('hr_departments.id', 'hr_departments.name')
            ->orderByDesc('headcount')
            ->get();

        $attendanceRate = DB::table('hr_attendance')
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->selectRaw(
                "date,
                 SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                 SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                 SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                 COUNT(*) as total"
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'present' => $row->present_count,
                'absent' => $row->absent_count,
                'late' => $row->late_count,
                'rate' => $row->total > 0 ? round(($row->present_count / $row->total) * 100, 1) : 0,
            ]);

        $leaveUsage = DB::table('hr_leave_requests')
            ->where('status', 'approved')
            ->selectRaw(
                'leave_type,
                 COUNT(*) as request_count,
                 SUM(total_days) as total_days'
            )
            ->groupBy('leave_type')
            ->orderByDesc('total_days')
            ->get();

        $totalEmployees = DB::table('hr_employees')->where('status', 'active')->count();
        $terminatedCount = DB::table('hr_employees')
            ->where('status', 'terminated')
            ->where('termination_date', '>=', now()->subMonths(12)->toDateString())
            ->count();
        $avgHeadcount = DB::table('hr_employees')
            ->where('status', 'active')
            ->orWhere('termination_date', '>=', now()->subMonths(12)->toDateString())
            ->count();
        $turnoverRate = $avgHeadcount > 0 ? round(($terminatedCount / $avgHeadcount) * 100, 1) : 0;

        return $this->respondSuccess('HR analytics retrieved', [
            'headcount_by_department' => $headcountByDepartment,
            'attendance_rate' => $attendanceRate,
            'leave_usage' => $leaveUsage,
            'turnover_rate' => $turnoverRate,
        ]);
    }

    public function sales(Request $request): JsonResponse
    {
        $months = (int) $request->query('months', 6);
        $startDate = now()->subMonths($months);

        $pipelineValue = DB::table('sales_crm_pipelines')
            ->select(
                'stage',
                DB::raw('COUNT(*) as deal_count'),
                DB::raw('SUM(value) as total_value'),
                DB::raw('AVG(probability) as avg_probability')
            )
            ->groupBy('stage')
            ->get();

        $totalDeals = DB::table('sales_crm_pipelines')->count();
        $conversionRates = DB::table('sales_crm_pipelines')
            ->select(
                'stage',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('stage')
            ->get()
            ->map(fn ($row) => [
                'stage' => $row->stage,
                'count' => $row->count,
                'conversion_rate' => $totalDeals > 0 ? round(($row->count / $totalDeals) * 100, 1) : 0,
            ]);

        $topCustomers = DB::table('sales_orders')
            ->join('sales_customers', 'sales_orders.customer_id', '=', 'sales_customers.id')
            ->where('sales_orders.status', 'delivered')
            ->where('sales_orders.created_at', '>=', $startDate)
            ->select(
                'sales_customers.id',
                'sales_customers.name',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(sales_orders.total_amount) as total_revenue'),
                DB::raw('AVG(sales_orders.total_amount) as avg_order_value')
            )
            ->groupBy('sales_customers.id', 'sales_customers.name')
            ->orderByDesc('total_revenue')
            ->limit(15)
            ->get();

        $revenueByMonth = DB::table('sales_orders')
            ->where('status', 'delivered')
            ->where('created_at', '>=', $startDate)
            ->selectRaw(
                "strftime('%Y-%m', created_at) as month,
                 SUM(total_amount) as revenue,
                 COUNT(*) as order_count"
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $this->respondSuccess('Sales analytics retrieved', [
            'pipeline_value' => $pipelineValue,
            'conversion_rates' => $conversionRates,
            'top_customers' => $topCustomers,
            'revenue_by_month' => $revenueByMonth,
        ]);
    }

    public function procurement(Request $request): JsonResponse
    {
        $months = (int) $request->query('months', 6);
        $startDate = now()->subMonths($months);

        $poValueByVendor = DB::table('procurement_purchase_orders')
            ->join('procurement_vendors', 'procurement_purchase_orders.vendor_id', '=', 'procurement_vendors.id')
            ->where('procurement_purchase_orders.created_at', '>=', $startDate)
            ->select(
                'procurement_vendors.id',
                'procurement_vendors.name',
                DB::raw('COUNT(*) as po_count'),
                DB::raw('SUM(procurement_purchase_orders.total_amount) as total_spend'),
                DB::raw('AVG(procurement_purchase_orders.total_amount) as avg_po_value')
            )
            ->groupBy('procurement_vendors.id', 'procurement_vendors.name')
            ->orderByDesc('total_spend')
            ->get();

        $spendByCategory = DB::table('procurement_po_items')
            ->join('procurement_purchase_orders', 'procurement_po_items.purchase_order_id', '=', 'procurement_purchase_orders.id')
            ->leftJoin('inventory_products', 'procurement_po_items.product_id', '=', 'inventory_products.id')
            ->leftJoin('inventory_categories', 'inventory_products.category_id', '=', 'inventory_categories.id')
            ->where('procurement_purchase_orders.created_at', '>=', $startDate)
            ->select(
                DB::raw("COALESCE(inventory_categories.name, 'Uncategorized') as category"),
                DB::raw('SUM(procurement_po_items.line_total) as total_spend'),
                DB::raw('COUNT(*) as item_count')
            )
            ->groupBy('category')
            ->orderByDesc('total_spend')
            ->get();

        $totalPOs = DB::table('procurement_purchase_orders')
            ->where('created_at', '>=', $startDate)
            ->count();
        $receivedOnTime = DB::table('procurement_purchase_orders')
            ->where('status', 'received')
            ->where('created_at', '>=', $startDate)
            ->whereColumn('expected_delivery_date', '>=', DB::raw('updated_at'))
            ->count();
        $deliveryPerformance = $totalPOs > 0 ? round(($receivedOnTime / max($totalPOs, 1)) * 100, 1) : 0;

        $spendByMonth = DB::table('procurement_purchase_orders')
            ->where('created_at', '>=', $startDate)
            ->selectRaw(
                "strftime('%Y-%m', created_at) as month,
                 SUM(total_amount) as spend,
                 COUNT(*) as po_count"
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $this->respondSuccess('Procurement analytics retrieved', [
            'po_value_by_vendor' => $poValueByVendor,
            'spend_by_category' => $spendByCategory,
            'delivery_performance' => $deliveryPerformance,
            'spend_by_month' => $spendByMonth,
        ]);
    }
}
