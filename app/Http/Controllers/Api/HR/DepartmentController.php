<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends BaseApiController
{
    protected string $cacheTag = 'hr_departments';

    protected int $cacheTtl = 3600;

    protected array $validationRules = [
        'code' => 'required|string|max:20|unique:hr_departments,code',
        'name' => 'required|string|max:200',
        'description' => 'nullable|string',
        'manager_id' => 'nullable|exists:hr_employees,id',
        'parent_id' => 'nullable|exists:hr_departments,id',
        'is_active' => 'boolean',
    ];

    public function __construct()
    {
        parent::__construct(new Department);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'code' => 'required|string|max:20|unique:hr_departments,code',
        ]));
        if ($error) {
            return $error;
        }

        $department = Department::create($request->all());

        return $this->respondCreated($department, 'Department created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $department = Department::find($id);
        if (! $department) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:20|unique:hr_departments,code,'.$id,
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:hr_employees,id',
            'parent_id' => 'nullable|exists:hr_departments,id',
            'is_active' => 'boolean',
        ]);
        if ($error) {
            return $error;
        }

        $department->update($request->all());

        return $this->respondSuccess('Department updated', $department->fresh());
    }

    public function show(int $id): JsonResponse
    {
        $department = Department::with(['manager', 'parent', 'children', 'employees'])->find($id);
        if (! $department) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $department]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Department::query()->with(['manager', 'parent']);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
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
}
