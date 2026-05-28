<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function index(Request $request): JsonResponse
    {
        $query = LeaveRequest::query()->with(['employee']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->get('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('leave_type')) {
            $query->where('leave_type', $request->get('leave_type'));
        }

        $perPage = $request->get('per_page', 15);
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

    public function byEmployee(int $employee): JsonResponse
    {
        $leaves = LeaveRequest::where('employee_id', $employee)
            ->orderBy('start_date', 'desc')
            ->get();

        return $this->respond(['success' => true, 'data' => $leaves]);
    }
}
