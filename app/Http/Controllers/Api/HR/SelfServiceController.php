<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use App\Models\HR\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SelfServiceController extends BaseApiController
{
    public function profile(): JsonResponse
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            return $this->respondError('No employee profile linked to this account', 404);
        }

        $employee->load(['department', 'manager', 'subordinates']);

        $tenure = $employee->hire_date ? now()->diffInYears($employee->hire_date) : 0;

        $totalLeaveDays = $employee->leaveRequests()->where('status', 'approved')->sum('total_days');
        $pendingLeave = $employee->leaveRequests()->where('status', 'pending')->count();

        $totalDays = $employee->attendance()->where('date', '>=', now()->subMonths(3)->toDateString())->count();
        $attendanceRate = 0;
        $totalHours = 0;
        $totalOvertime = 0;
        if ($totalDays > 0) {
            $presentDays = $employee->attendance()->where('date', '>=', now()->subMonths(3)->toDateString())
                ->where('status', 'present')->count();
            $attendanceRate = round(($presentDays / $totalDays) * 100, 1);
            $totalHours = $employee->attendance()->where('date', '>=', now()->subMonths(3)->toDateString())->sum('total_hours');
            $totalOvertime = $employee->attendance()->where('date', '>=', now()->subMonths(3)->toDateString())->sum('overtime_hours');
        }

        $recentPayroll = $employee->user_id
            ? Payroll::where('employee_id', $employee->id)->latest('period_end')->first()
            : null;

        return $this->respond([
            'success' => true,
            'data' => [
                'employee' => $employee,
                'stats' => [
                    'tenure_years' => $tenure,
                    'attendance_rate' => $attendanceRate,
                    'total_hours_worked' => round($totalHours, 2),
                    'total_overtime_hours' => round($totalOvertime, 2),
                    'total_leave_days_used' => $totalLeaveDays,
                    'pending_leave_requests' => $pendingLeave,
                    'subordinates_count' => $employee->subordinates()->count(),
                ],
                'recent_payslip' => $recentPayroll,
            ],
        ]);
    }

    public function payslips(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            return $this->respondError('No employee profile linked to this account', 404);
        }

        $payslips = Payroll::where('employee_id', $employee->id)
            ->orderByDesc('period_end')
            ->paginate(min($request->query('per_page', 12), 50));

        $totalPaid = Payroll::where('employee_id', $employee->id)
            ->where('status', 'paid')
            ->sum('net_salary');

        return $this->respond([
            'success' => true,
            'data' => [
                'payslips' => $payslips->items(),
                'total_paid' => round($totalPaid, 2),
            ],
            'meta' => [
                'current_page' => $payslips->currentPage(),
                'last_page' => $payslips->lastPage(),
                'per_page' => $payslips->perPage(),
                'total' => $payslips->total(),
            ],
        ]);
    }

    public function leaveBalance(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            return $this->respondError('No employee profile linked to this account', 404);
        }

        $year = (int) $request->query('year', now()->year);

        $usedByType = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->select('leave_type', DB::raw('sum(total_days) as days_used'))
            ->groupBy('leave_type')
            ->get()
            ->pluck('days_used', 'leave_type')
            ->toArray();

        $policy = [
            'annual' => 20, 'sick' => 10, 'personal' => 5,
            'maternity' => 90, 'paternity' => 10, 'unpaid' => 0, 'other' => 0,
        ];

        $balances = [];
        foreach ($policy as $type => $entitlement) {
            $used = $usedByType[$type] ?? 0;
            $balances[$type] = [
                'entitlement' => $entitlement,
                'used' => $used,
                'remaining' => max(0, $entitlement - $used),
            ];
        }

        $recentRequests = LeaveRequest::where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $upcomingApproved = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('start_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'year' => $year,
                'balances' => $balances,
                'total_used' => array_sum($usedByType),
                'total_entitlement' => array_sum($policy),
                'recent_requests' => $recentRequests,
                'upcoming_leave' => $upcomingApproved,
            ],
        ]);
    }

    public function attendance(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            return $this->respondError('No employee profile linked to this account', 404);
        }

        $startDate = $request->query('start_date', now()->subMonths(1)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $records = Attendance::where('employee_id', $employee->id)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderByDesc('date')
            ->get();

        $totalDays = $records->count();
        $presentDays = $records->where('status', 'present')->count();
        $lateDays = $records->where('status', 'late')->count();
        $absentDays = $records->where('status', 'absent')->count();
        $totalHours = $records->sum('total_hours');
        $totalOvertime = $records->sum('overtime_hours');
        $totalRegular = $records->sum('regular_hours');
        $attendanceRate = $totalDays > 0 ? round(($presentDays + $lateDays) / $totalDays * 100, 1) : 0;

        $todayRecord = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', today())
            ->first();

        return $this->respond([
            'success' => true,
            'data' => [
                'today' => [
                    'clocked_in' => $todayRecord?->clock_in,
                    'clocked_out' => $todayRecord?->clock_out,
                    'total_hours' => $todayRecord?->total_hours,
                    'overtime_hours' => $todayRecord?->overtime_hours,
                    'status' => $todayRecord?->status,
                ],
                'summary' => [
                    'total_days' => $totalDays,
                    'present' => $presentDays,
                    'late' => $lateDays,
                    'absent' => $absentDays,
                    'total_hours' => round($totalHours, 2),
                    'regular_hours' => round($totalRegular, 2),
                    'overtime_hours' => round($totalOvertime, 2),
                    'attendance_rate' => $attendanceRate,
                ],
                'records' => $records,
            ],
        ]);
    }

    public function submitLeave(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            return $this->respondError('No employee profile linked to this account', 404);
        }

        $error = $this->validate($request->all(), [
            'leave_type' => 'required|in:annual,sick,personal,unpaid,maternity,paternity,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'total_days' => 'required|numeric|min:0.5',
            'reason' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $existing = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->orWhere(function ($q) use ($request) {
                $q->where('status', 'approved')
                    ->where('start_date', '<=', $request->end_date)
                    ->where('end_date', '>=', $request->start_date);
            })
            ->exists();

        if ($existing) {
            return $this->respondError('You already have a pending or overlapping leave request', 422);
        }

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_days' => $request->total_days,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return $this->respondCreated($leave, 'Leave request submitted');
    }

    public function cancelLeave(int $id): JsonResponse
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            return $this->respondError('No employee profile linked to this account', 404);
        }

        $leave = LeaveRequest::where('id', $id)->where('employee_id', $employee->id)->first();
        if (! $leave) {
            return $this->respondNotFound();
        }

        if (! in_array($leave->status, ['pending', 'approved'])) {
            return $this->respondError('Cannot cancel a '.$leave->status.' request', 422);
        }

        if ($leave->status === 'approved' && $leave->start_date <= now()->toDateString()) {
            return $this->respondError('Cannot cancel an active leave that has already started', 422);
        }

        $leave->update(['status' => 'cancelled']);

        return $this->respondSuccess('Leave request cancelled', $leave->fresh());
    }
}
