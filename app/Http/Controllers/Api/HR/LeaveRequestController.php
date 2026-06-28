<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveRequestController extends BaseApiController
{
    protected array $validationRules = [
        'employee_id' => 'required|exists:hr_employees,id',
        'leave_type' => 'required|in:annual,sick,personal,unpaid,maternity,paternity,other',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'total_days' => 'required|numeric|min:0.5',
        'reason' => 'nullable|string',
        'status' => 'sometimes|in:pending,approved,rejected,cancelled',
    ];

    public function __construct()
    {
        parent::__construct(new LeaveRequest);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $existing = LeaveRequest::where('employee_id', $request->employee_id)
            ->where('status', 'pending')
            ->orWhere(function ($q) use ($request) {
                $q->where('status', 'approved')
                    ->where('start_date', '<=', $request->end_date)
                    ->where('end_date', '>=', $request->start_date);
            })
            ->exists();

        if ($existing) {
            return $this->respondError('Employee already has a pending or overlapping leave request for this period', 422);
        }

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'pending';

        $leave = LeaveRequest::create($data);

        return $this->respondCreated($leave->fresh(['employee']), 'Leave request submitted');
    }

    public function index(Request $request): JsonResponse
    {
        $query = LeaveRequest::query()->with(['employee', 'approver']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->query('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('leave_type')) {
            $query->where('leave_type', $request->query('leave_type'));
        }

        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('end_date', '<=', $request->query('end_date'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $leave = LeaveRequest::with(['employee', 'approver'])->find($id);
        if (! $leave) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $leave]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $leave = LeaveRequest::find($id);
        if (! $leave) {
            return $this->respondNotFound();
        }

        if ($leave->status !== 'pending') {
            return $this->respondError('Only pending requests can be approved', 422);
        }

        $leave->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return $this->respondSuccess('Leave request approved', $leave->fresh(['employee']));
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $leave = LeaveRequest::find($id);
        if (! $leave) {
            return $this->respondNotFound();
        }

        if ($leave->status !== 'pending') {
            return $this->respondError('Only pending requests can be rejected', 422);
        }

        $error = $this->validate($request->all(), [
            'reason' => 'required|string|max:500',
        ]);
        if ($error) {
            return $error;
        }

        $leave->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->input('reason'),
        ]);

        return $this->respondSuccess('Leave request rejected', $leave->fresh(['employee']));
    }

    public function cancel(int $id): JsonResponse
    {
        $leave = LeaveRequest::find($id);
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

    public function balance(Request $request): JsonResponse
    {
        $employeeId = $request->query('employee_id');
        if (! $employeeId) {
            return $this->respondError('employee_id is required', 422);
        }

        $employee = Employee::find($employeeId);
        if (! $employee) {
            return $this->respondNotFound('Employee not found');
        }

        $year = (int) $request->query('year', now()->year);

        $usedByType = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->select('leave_type', DB::raw('sum(total_days) as days_used'))
            ->groupBy('leave_type')
            ->get()
            ->pluck('days_used', 'leave_type')
            ->toArray();

        $policy = [
            'annual' => 20,
            'sick' => 10,
            'personal' => 5,
            'maternity' => 90,
            'paternity' => 10,
            'unpaid' => 0,
            'other' => 0,
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

        $upcomingApproved = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('start_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->get();

        $totalUsed = array_sum($usedByType);
        $totalEntitlement = array_sum($policy);

        return $this->respond([
            'success' => true,
            'data' => [
                'employee' => $employee,
                'year' => $year,
                'balances' => $balances,
                'total_used' => $totalUsed,
                'total_entitlement' => $totalEntitlement,
                'upcoming_leave' => $upcomingApproved,
            ],
        ]);
    }

    public function teamPending(): JsonResponse
    {
        $leaves = LeaveRequest::with(['employee'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->respond(['success' => true, 'data' => $leaves]);
    }

    public function calendar(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        $leaves = LeaveRequest::with(['employee'])
            ->where('status', 'approved')
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->orderBy('start_date')
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'employee' => $leave->employee->first_name.' '.$leave->employee->last_name,
                    'leave_type' => $leave->leave_type,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'total_days' => $leave->total_days,
                ];
            });

        return $this->respond(['success' => true, 'data' => $leaves]);
    }
}
