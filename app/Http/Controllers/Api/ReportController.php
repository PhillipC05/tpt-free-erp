<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Finance\ReportController as FinanceReportController;
use App\Models\Finance\Transaction;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Projects\Project;
use App\Models\Projects\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    // ===== Finance reports (delegated to Finance\ReportController) =====

    public function balanceSheet(Request $request): JsonResponse
    {
        return (new FinanceReportController())->balanceSheet($request);
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        return (new FinanceReportController())->incomeStatement($request);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        return (new FinanceReportController())->trialBalance($request);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $inflows = (float) Transaction::where('type', 'debit')
            ->where('status', 'posted')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $outflows = (float) Transaction::where('type', 'credit')
            ->where('status', 'posted')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        return $this->respond([
            'success' => true,
            'data' => [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_inflows' => $inflows,
                'total_outflows' => $outflows,
                'net_cash_flow' => $inflows - $outflows,
            ],
        ]);
    }

    // ===== HR reports =====

    public function attendance(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $query = Attendance::whereBetween('date', [$startDate, $endDate]);

        $summary = [
            'present' => (clone $query)->where('status', 'present')->count(),
            'absent' => (clone $query)->where('status', 'absent')->count(),
            'late' => (clone $query)->where('status', 'late')->count(),
            'total_hours' => (float) (clone $query)->sum('total_hours'),
        ];

        return $this->respond([
            'success' => true,
            'data' => [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'summary' => $summary,
            ],
        ]);
    }

    public function payroll(Request $request): JsonResponse
    {
        $month = $request->query('month', now()->format('Y-m'));

        $employeeCount = Employee::where('status', 'active')->count();

        return $this->respond([
            'success' => true,
            'data' => [
                'month' => $month,
                'active_employees' => $employeeCount,
                'total_gross' => 0,
                'total_deductions' => 0,
                'total_net' => 0,
            ],
        ]);
    }

    // ===== Sales reports =====

    public function sales(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalRevenue = (float) Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('total_amount');

        return $this->respond([
            'success' => true,
            'data' => [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
            ],
        ]);
    }

    public function customer(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        return $this->respond([
            'success' => true,
            'data' => [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'new_customers' => 0,
                'active_customers' => 0,
            ],
        ]);
    }

    // ===== Procurement reports =====

    public function purchases(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $totalPOs = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->count();
        $totalValue = (float) PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->sum('total_amount');

        return $this->respond([
            'success' => true,
            'data' => [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_purchase_orders' => $totalPOs,
                'total_value' => $totalValue,
            ],
        ]);
    }

    // ===== Projects reports =====

    public function projects(Request $request): JsonResponse
    {
        $total = Project::count();
        $active = Project::where('status', 'active')->count();
        $totalHours = (float) TimeEntry::sum('hours');

        return $this->respond([
            'success' => true,
            'data' => [
                'total_projects' => $total,
                'active_projects' => $active,
                'total_logged_hours' => $totalHours,
            ],
        ]);
    }

    // ===== Generic report endpoints =====

    public function index(Request $request): JsonResponse
    {
        return $this->respondSuccess('Available reports', [
            'finance' => ['balance-sheet', 'income-statement', 'cash-flow', 'trial-balance'],
            'hr' => ['attendance', 'payroll'],
            'sales' => ['sales', 'customer'],
            'procurement' => ['purchases'],
            'projects' => ['projects'],
        ]);
    }

    public function available(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function generate(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'report_type' => 'required|string',
            'parameters' => 'nullable|array',
        ]);
        if ($error) return $error;

        return $this->respondSuccess('Report generation queued', [
            'report_type' => $request->report_type,
            'status' => 'queued',
        ]);
    }

    public function scheduled(): JsonResponse
    {
        return $this->respondSuccess('Scheduled reports', ['scheduled' => []]);
    }
}
