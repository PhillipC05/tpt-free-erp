<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockMovement;
use App\Models\HR\Employee;
use App\Models\HR\Attendance;
use App\Models\HR\LeaveRequest;
use App\Models\Sales\Customer;
use App\Models\Sales\Order;
use App\Models\Projects\Project;
use App\Models\Projects\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        return $this->respond([
            'success' => true,
            'data' => [
                'finance' => $this->getFinanceSummary(),
                'inventory' => $this->getInventorySummary(),
                'hr' => $this->getHRSummary(),
                'sales' => $this->getSalesSummary(),
                'projects' => $this->getProjectSummary(),
                'system' => [
                    'active_users' => User::count(),
                    'new_users_today' => User::whereDate('created_at', today())->count(),
                ],
            ],
        ]);
    }

    public function finance(Request $request): JsonResponse
    {
        return $this->respond(['success' => true, 'data' => $this->getFinanceSummary()]);
    }

    public function inventory(Request $request): JsonResponse
    {
        return $this->respond(['success' => true, 'data' => $this->getInventorySummary()]);
    }

    public function hr(Request $request): JsonResponse
    {
        return $this->respond(['success' => true, 'data' => $this->getHRSummary()]);
    }

    public function sales(Request $request): JsonResponse
    {
        return $this->respond(['success' => true, 'data' => $this->getSalesSummary()]);
    }

    private function getFinanceSummary(): array
    {
        return [
            'total_assets' => Account::where('type', 'asset')->sum('current_balance'),
            'total_liabilities' => Account::where('type', 'liability')->sum('current_balance'),
            'total_equity' => Account::where('type', 'equity')->sum('current_balance'),
            'total_revenue' => Account::where('type', 'revenue')->sum('current_balance'),
            'total_expenses' => Account::where('type', 'expense')->sum('current_balance'),
            'recent_transactions' => Transaction::with('account')->latest()->take(10)->get(),
        ];
    }

    private function getInventorySummary(): array
    {
        $totalProducts = Product::count();
        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'min_stock_level')->count();

        return [
            'total_products' => $totalProducts,
            'low_stock_products' => $lowStockProducts,
            'recent_movements' => StockMovement::with('product')->latest()->take(10)->get(),
        ];
    }

    private function getHRSummary(): array
    {
        return [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('status', 'active')->count(),
            'on_leave_today' => LeaveRequest::where('status', 'approved')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->count(),
            'pending_leave_requests' => LeaveRequest::where('status', 'pending')->count(),
            'present_today' => Attendance::whereDate('date', today())->where('status', 'present')->count(),
            'absent_today' => Attendance::whereDate('date', today())->where('status', 'absent')->count(),
        ];
    }

    private function getSalesSummary(): array
    {
        return [
            'total_customers' => Customer::count(),
            'pending_orders' => Order::whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('total_amount'),
            'revenue_today' => Order::whereDate('order_date', today())->sum('total_amount'),
            'recent_orders' => Order::with('customer')->latest()->take(10)->get(),
        ];
    }

    private function getProjectSummary(): array
    {
        return [
            'total_projects' => Project::count(),
            'active_projects' => Project::where('status', 'active')->count(),
            'completed_tasks' => Task::where('status', 'done')->count(),
            'pending_tasks' => Task::whereNotIn('status', ['done', 'cancelled'])->count(),
        ];
    }
}