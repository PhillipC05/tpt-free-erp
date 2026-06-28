<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\Attendance;
use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use App\Models\HR\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HRTrackingController extends BaseApiController
{
    public function dashboard(Request $request): JsonResponse
    {
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $newThisMonth = Employee::where('created_at', '>=', now()->startOfMonth())->count();
        $terminatedThisMonth = Employee::where('status', 'terminated')
            ->where('termination_date', '>=', now()->startOfMonth()->toDateString())->count();

        $turnoverRate = $activeEmployees > 0
            ? round(($terminatedThisMonth / max($activeEmployees + $terminatedThisMonth, 1)) * 100, 1)
            : 0;

        $presentToday = Attendance::whereDate('date', today())
            ->where('status', 'present')->count();
        $absentToday = Attendance::whereDate('date', today())
            ->where('status', 'absent')->count();
        $lateToday = Attendance::whereDate('date', today())
            ->where('status', 'late')->count();
        $attendanceRate = ($presentToday + $absentToday + $lateToday) > 0
            ? round(($presentToday / ($presentToday + $absentToday + $lateToday)) * 100, 1)
            : 0;

        $pendingLeave = LeaveRequest::where('status', 'pending')->count();
        $approvedLeave = LeaveRequest::where('status', 'approved')
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->count();

        $departmentBreakdown = Department::withCount('employees')
            ->get()
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'employee_count' => $d->employees_count]);

        $employmentTypeBreakdown = Employee::where('status', 'active')
            ->select('employment_type', DB::raw('count(*) as count'))
            ->groupBy('employment_type')
            ->get();

        $monthlyHires = Employee::where('created_at', '>=', now()->subMonths(12))
            ->selectRaw("strftime('%Y-%m', created_at) as month, count(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $leaveByType = LeaveRequest::where('start_date', '>=', now()->subMonths(6)->toDateString())
            ->select('leave_type', DB::raw('count(*) as count'), DB::raw('sum(total_days) as total_days'))
            ->groupBy('leave_type')
            ->get();

        $recentLeaves = LeaveRequest::with('employee')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_employees' => $totalEmployees,
                    'active_employees' => $activeEmployees,
                    'new_this_month' => $newThisMonth,
                    'terminated_this_month' => $terminatedThisMonth,
                    'turnover_rate' => $turnoverRate,
                ],
                'attendance' => [
                    'present_today' => $presentToday,
                    'absent_today' => $absentToday,
                    'late_today' => $lateToday,
                    'attendance_rate' => $attendanceRate,
                ],
                'leave' => [
                    'pending_requests' => $pendingLeave,
                    'on_leave_today' => $approvedLeave,
                ],
                'department_breakdown' => $departmentBreakdown,
                'employment_type_breakdown' => $employmentTypeBreakdown,
                'monthly_hires' => $monthlyHires,
                'leave_by_type' => $leaveByType,
                'recent_leave_requests' => $recentLeaves,
            ],
        ]);
    }

    public function attendanceReport(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->subMonths(1)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $employeeId = $request->query('employee_id');

        $query = Attendance::query()
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $dailyStats = (clone $query)
            ->selectRaw("date, sum(case when status = 'present' then 1 else 0 end) as present, sum(case when status = 'absent' then 1 else 0 end) as absent, sum(case when status = 'late' then 1 else 0 end) as late, count(*) as total")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalDays = $dailyStats->sum('total');
        $totalPresent = $dailyStats->sum('present');
        $overallRate = $totalDays > 0 ? round(($totalPresent / $totalDays) * 100, 1) : 0;

        $byEmployee = (clone $query)
            ->select('employee_id', DB::raw('count(*) as days'), DB::raw("sum(case when status = 'present' then 1 else 0 end) as present_days"), DB::raw("sum(case when status = 'late' then 1 else 0 end) as late_days"), DB::raw("sum(case when status = 'absent' then 1 else 0 end) as absent_days"))
            ->groupBy('employee_id')
            ->with('employee:id,first_name,last_name,employee_code')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'overall_rate' => $overallRate,
                'total_days' => $totalDays,
                'daily_stats' => $dailyStats,
                'by_employee' => $byEmployee,
            ],
        ]);
    }

    public function leaveReport(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->subMonths(6)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $query = LeaveRequest::where('start_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate);

        $byStatus = (clone $query)
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total_days) as total_days'))
            ->groupBy('status')
            ->get();

        $byType = (clone $query)
            ->select('leave_type', DB::raw('count(*) as count'), DB::raw('sum(total_days) as total_days'))
            ->groupBy('leave_type')
            ->get();

        $byDepartment = (clone $query)
            ->join('hr_employees', 'hr_leave_requests.employee_id', '=', 'hr_employees.id')
            ->leftJoin('hr_departments', 'hr_employees.department_id', '=', 'hr_departments.id')
            ->select('hr_departments.name as department_name', DB::raw('count(*) as count'), DB::raw('sum(hr_leave_requests.total_days) as total_days'))
            ->groupBy('hr_departments.name')
            ->orderByDesc('total_days')
            ->get();

        $monthlyTrend = (clone $query)
            ->selectRaw("strftime('%Y-%m', start_date) as month, count(*) as count, sum(total_days) as days")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'by_status' => $byStatus,
                'by_type' => $byType,
                'by_department' => $byDepartment,
                'monthly_trend' => $monthlyTrend,
            ],
        ]);
    }

    public function payrollReport(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->subMonths(3)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $query = Payroll::where('hr_payroll.created_at', '>=', $startDate)
            ->where('hr_payroll.created_at', '<=', $endDate);

        $summary = (clone $query)
            ->selectRaw('count(*) as total_records, sum(basic_salary) as total_basic, sum(allowances) as total_allowances, sum(deductions) as total_deductions, sum(net_salary) as total_net')
            ->first();

        $byStatus = (clone $query)
            ->select('hr_payroll.status', DB::raw('count(*) as count'), DB::raw('sum(net_salary) as total_net'))
            ->groupBy('hr_payroll.status')
            ->get();

        $monthlyTrend = (clone $query)
            ->selectRaw("strftime('%Y-%m', hr_payroll.created_at) as month, sum(net_salary) as total, count(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $topEarners = (clone $query)
            ->where('hr_payroll.status', 'paid')
            ->join('hr_employees', 'hr_payroll.employee_id', '=', 'hr_employees.id')
            ->select('hr_employees.first_name', 'hr_employees.last_name', 'hr_employees.employee_code', 'hr_payroll.net_salary')
            ->orderByDesc('hr_payroll.net_salary')
            ->limit(10)
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'by_status' => $byStatus,
                'monthly_trend' => $monthlyTrend,
                'top_earners' => $topEarners,
            ],
        ]);
    }
}
