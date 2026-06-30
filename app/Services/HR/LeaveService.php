<?php

namespace App\Services\HR;

use App\Models\HR\LeaveRequest;
use Illuminate\Support\Collection;

class LeaveService
{
    public function createLeaveRequest(array $data): LeaveRequest
    {
        $data['status'] = $data['status'] ?? 'pending';

        return LeaveRequest::create($data);
    }

    public function approveLeave(LeaveRequest $leaveRequest, int $approverId): LeaveRequest
    {
        if ($leaveRequest->status !== 'pending') {
            throw new \RuntimeException('Only pending requests can be approved');
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);

        return $leaveRequest->fresh();
    }

    public function rejectLeave(LeaveRequest $leaveRequest, string $reason): LeaveRequest
    {
        if ($leaveRequest->status !== 'pending') {
            throw new \RuntimeException('Only pending requests can be rejected');
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $leaveRequest->fresh();
    }

    public function cancelLeave(LeaveRequest $leaveRequest): LeaveRequest
    {
        if (! in_array($leaveRequest->status, ['pending', 'approved'])) {
            throw new \RuntimeException('Leave request cannot be cancelled');
        }

        $leaveRequest->update(['status' => 'cancelled']);

        return $leaveRequest->fresh();
    }

    public function getLeavesByEmployee(int $employeeId, ?string $status = null): Collection
    {
        $query = LeaveRequest::where('employee_id', $employeeId)
            ->orderBy('start_date', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }
}
