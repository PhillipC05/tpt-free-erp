<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends BaseApiController
{
    protected array $validationRules = [
        'employee_code' => 'required|string|max:20|unique:hr_employees,employee_code',
        'user_id' => 'nullable|exists:users,id',
        'first_name' => 'required|string|max:100',
        'last_name' => 'required|string|max:100',
        'email' => 'required|email|max:200|unique:hr_employees,email',
        'phone' => 'nullable|string|max:20',
        'position' => 'nullable|string|max:200',
        'department_id' => 'nullable|exists:hr_departments,id',
        'manager_id' => 'nullable|exists:hr_employees,id',
        'hire_date' => 'required|date',
        'termination_date' => 'nullable|date|after_or_equal:hire_date',
        'employment_type' => 'required|in:permanent,contract,intern,probation',
        'status' => 'sometimes|in:active,inactive,terminated,suspended',
        'salary' => 'nullable|numeric|min:0',
        'currency' => 'nullable|string|size:3',
        'address' => 'nullable|string',
        'emergency_contact' => 'nullable|string|max:200',
        'emergency_phone' => 'nullable|string|max:20',
    ];

    protected array $validationMessages = [
        'employee_code.required' => 'Employee code is required.',
        'employee_code.unique' => 'This employee code is already taken.',
        'first_name.required' => 'First name is required.',
        'last_name.required' => 'Last name is required.',
        'email.required' => 'Email address is required.',
        'email.unique' => 'This email address is already in use.',
        'hire_date.required' => 'Hire date is required.',
        'employment_type.required' => 'Employment type is required.',
    ];

    public function __construct()
    {
        parent::__construct(new Employee());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'employee_code' => 'required|string|max:20|unique:hr_employees,employee_code',
            'email' => 'required|email|max:200|unique:hr_employees,email',
        ]));
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'active';

        $employee = Employee::create($data);
        return $this->respondCreated($employee, 'Employee created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $employee = Employee::find($id);
        if (!$employee) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'employee_code' => 'required|string|max:20|unique:hr_employees,employee_code,' . $id,
            'user_id' => 'nullable|exists:users,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:200|unique:hr_employees,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:200',
            'department_id' => 'nullable|exists:hr_departments,id',
            'manager_id' => 'nullable|exists:hr_employees,id',
            'hire_date' => 'required|date',
            'termination_date' => 'nullable|date|after_or_equal:hire_date',
            'employment_type' => 'required|in:permanent,contract,intern,probation',
            'status' => 'sometimes|in:active,inactive,terminated,suspended',
            'salary' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:200',
            'emergency_phone' => 'nullable|string|max:20',
        ]);
        if ($error) return $error;

        $employee->update($request->all());
        return $this->respondSuccess('Employee updated', $employee->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Employee::query()->with(['department', 'manager']);

        if ($request->has('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('employment_type')) {
            $query->where('employment_type', $request->query('employment_type'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->paginate(min($perPage, 100));

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

    public function subordinates(int $id): JsonResponse
    {
        $employee = Employee::with('subordinates')->find($id);
        if (!$employee) return $this->respondNotFound();

        return $this->respond([
            'success' => true,
            'data' => $employee->subordinates,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $employee = Employee::with(['department', 'manager', 'subordinates'])->find($id);
        if (!$employee) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $employee]);
    }
}