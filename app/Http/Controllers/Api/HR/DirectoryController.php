<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\Department;
use App\Models\HR\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectoryController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Employee::query()->with(['department', 'manager', 'subordinates']);

        if ($request->has('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        } else {
            $query->where('status', 'active');
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->query('per_page', 50);
        $items = $query->orderBy('first_name')->paginate(min($perPage, 100));

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

    public function orgChart(Request $request): JsonResponse
    {
        $departmentId = $request->query('department_id');

        $query = Employee::query()->with(['subordinates' => function ($q) {
            $q->with(['subordinates.subordinates.subordinates.subordinates.subordinates.subordinates']);
        }])->whereNull('manager_id')
            ->where('status', 'active');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $roots = $query->orderBy('first_name')->get();

        $tree = $roots->map(fn ($emp) => $this->buildNode($emp));

        return $this->respond([
            'success' => true,
            'data' => $tree,
        ]);
    }

    public function orgChartFull(): JsonResponse
    {
        $employees = Employee::with([
            'subordinates.subordinates.subordinates.subordinates.subordinates.subordinates',
            'department',
        ])->where('status', 'active')->get();

        $roots = $employees->whereNull('manager_id');
        $tree = $roots->map(fn ($emp) => $this->buildNode($emp));

        $totalEmployees = $employees->count();
        $totalDepartments = Department::count();

        $departmentBreakdown = $employees->groupBy(fn ($e) => $e->department->name ?? 'Unassigned')
            ->map(fn ($group) => ['name' => $group->first()->department->name ?? 'Unassigned', 'count' => $group->count()])
            ->values();

        return $this->respond([
            'success' => true,
            'data' => [
                'chart' => $tree,
                'stats' => [
                    'total_employees' => $totalEmployees,
                    'total_departments' => $totalDepartments,
                    'department_breakdown' => $departmentBreakdown,
                ],
            ],
        ]);
    }

    public function profile(int $id): JsonResponse
    {
        $employee = Employee::with([
            'department',
            'manager.subordinates',
            'subordinates.subordinates',
            'attendance' => fn ($q) => $q->orderByDesc('date')->limit(7),
            'leaveRequests' => fn ($q) => $q->orderByDesc('created_at')->limit(5),
        ])->find($id);

        if (! $employee) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $employee]);
    }

    private function buildNode(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'position' => $employee->position,
            'email' => $employee->email,
            'department' => $employee->department->name ?? null,
            'department_id' => $employee->department_id,
            'hire_date' => $employee->hire_date?->format('Y-m-d'),
            'employment_type' => $employee->employment_type,
            'children' => $employee->subordinates->map(fn ($sub) => $this->buildNode($sub)),
        ];
    }
}
