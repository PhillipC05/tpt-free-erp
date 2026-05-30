<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends BaseApiController
{
    protected array $validationRules = [
        'employee_id' => 'required|exists:hr_employees,id',
        'leave_type' => 'required|in:annual,sick,personal,unpaid,maternity,paternity,other',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'total_days' => 'required|numeric|min:0.5',
        'reason' => 'nullable|string',
        'status' => 'sometimes|in:pending,approved,rejected,cancelled',
        'approved_by' => 'nullable|exists:hr_employees,id',
        'rejection_reason' => 'nullable|string',
    ];

    protected array $validationMessages = [
        'employee_id.required' => 'Employee is required.',
        'leave_type.required' => 'Leave type is required.',
        'start_date.required' => 'Start date is required.',
        'end_date.required' => 'End date is required.',
        'end_date.after_or_equal' => 'End date must be after or equal to start date.',
    ];

    public function __construct()
    {
        parent::__construct(new LeaveRequest());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'pending';

        $leave = LeaveRequest::create($data);
        return $this->respondCreated($leave, 'Leave request submitted successfully');
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $leave = LeaveRequest::find($id);
        if (!$leave) return $this->respondNotFound();

        if ($leave->status !== 'pending') {
            return $this->respondError('Only pending requests can be approved', 422);
        }

        $leave->update([
            'status' => 'approved',
            'approved_by' => $request->query('approved_by', Auth::id()),
            'approved_at' => now(),
        ]);

        return $this->respondSuccess('Leave request approved', $leave->fresh());
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $leave = LeaveRequest::find($id);
        if (!$leave) return $this->respondNotFound();

        if ($leave->status !== 'pending') {
            return $this->respondError('Only pending requests can be rejected', 422);
        }

        $error = $this->validate($request->all(), [
            'rejection_reason' => 'required|string',
        ]);
        if ($error) return $error;

        $leave->update([
            'status' => 'rejected',
            'rejection_reason' => $request->query('rejection_reason'),
        ]);

        return $this->respondSuccess('Leave request rejected', $leave->fresh());
    }

    public function cancel(int $id): JsonResponse
    {
        $leave = LeaveRequest::find($id);
        if (!$leave) return $this->respondNotFound();

        if (!in_array($leave->status, ['pending', 'approved'])) {
            return $this->respondError('Leave request cannot be cancelled', 422);
        }

        $leave->update(['status' => 'cancelled']);
        return $this->respondSuccess('Leave request cancelled', $leave->fresh());
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

    public function myLeaves(Request $request): JsonResponse
    {
        $employeeId = $request->query('employee_id');
        if (!$employeeId) {
            return $this->respondError('Employee ID is required', 400);
        }

        $query = LeaveRequest::where('employee_id', $employeeId);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('start_date', 'desc')->paginate(min($perPage, 100));

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
}