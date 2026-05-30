<?php

namespace App\Services\HR;

use App\Models\HR\Employee;
use App\Models\HR\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    public function createEmployee(array $data): Employee
    {
        return Employee::create($data);
    }

    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        return $employee->fresh();
    }

    public function getOrganizationalChart(): Collection
    {
        return Department::whereNull('parent_id')
            ->with(['children', 'manager', 'employees' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get();
    }

    public function getHierarchy(int $employeeId): array
    {
        $employee = Employee::with(['manager', 'subordinates'])->findOrFail($employeeId);

        $chain = [];
        $current = $employee;

        while ($current->manager) {
            $chain[] = [
                'id' => $current->manager->id,
                'name' => $current->manager->first_name . ' ' . $current->manager->last_name,
                'position' => $current->manager->position,
            ];
            $current = $current->manager;
        }

        return [
            'employee' => $employee,
            'reporting_to' => $chain,
            'subordinates' => $employee->subordinates,
        ];
    }

    public function getEmployeeAttendance(int $employeeId, ?string $startDate = null, ?string $endDate = null): array
    {
        $employee = Employee::findOrFail($employeeId);
        $query = $employee->attendances();

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $records = $query->orderBy('date', 'desc')->get();

        return [
            'employee' => $employee,
            'records' => $records,
            'summary' => [
                'present' => $records->where('status', 'present')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'late' => $records->where('status', 'late')->count(),
                'half_day' => $records->where('status', 'half_day')->count(),
                'total_hours' => $records->sum('total_hours'),
            ],
        ];
    }

    public function getEmployeeLeaveBalance(int $employeeId): array
    {
        $employee = Employee::findOrFail($employeeId);
        $leaveRequests = $employee->leaveRequests()->where('status', 'approved')->get();

        $balances = [
            'annual' => ['allocated' => 20, 'used' => 0, 'remaining' => 20],
            'sick' => ['allocated' => 10, 'used' => 0, 'remaining' => 10],
            'personal' => ['allocated' => 5, 'used' => 0, 'remaining' => 5],
        ];

        foreach ($leaveRequests as $leave) {
            if (isset($balances[$leave->leave_type])) {
                $balances[$leave->leave_type]['used'] += (float) $leave->total_days;
                $balances[$leave->leave_type]['remaining'] =
                    $balances[$leave->leave_type]['allocated'] - $balances[$leave->leave_type]['used'];
            }
        }

        return $balances;
    }
}