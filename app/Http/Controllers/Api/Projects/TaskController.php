<?php

namespace App\Http\Controllers\Api\Projects;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Projects\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends BaseApiController
{
    protected array $validationRules = [
        'code' => 'required|string|max:20|unique:project_tasks,code',
        'project_id' => 'required|exists:projects,id',
        'title' => 'required|string|max:200',
        'description' => 'nullable|string',
        'assigned_to' => 'nullable|exists:hr_employees,id',
        'start_date' => 'nullable|date',
        'due_date' => 'nullable|date|after_or_equal:start_date',
        'status' => 'sometimes|in:pending,in_progress,completed,on_hold,cancelled',
        'priority' => 'nullable|in:low,medium,high,urgent',
        'estimated_hours' => 'nullable|numeric|min:0',
        'actual_hours' => 'nullable|numeric|min:0',
        'parent_id' => 'nullable|exists:project_tasks,id',
        'sort_order' => 'nullable|integer|min:0',
    ];

    protected array $validationMessages = [
        'code.required' => 'Task code is required.',
        'code.unique' => 'This task code is already in use.',
        'project_id.required' => 'Project is required.',
        'title.required' => 'Task title is required.',
    ];

    public function __construct()
    {
        parent::__construct(new Task());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'code' => 'required|string|max:20|unique:project_tasks,code',
        ]));
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'pending';

        $task = Task::create($data);
        return $this->respondCreated($task, 'Task created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::find($id);
        if (!$task) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:20|unique:project_tasks,code,' . $id,
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:hr_employees,id',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|in:pending,in_progress,completed,on_hold,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'parent_id' => 'nullable|exists:project_tasks,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        if ($error) return $error;

        $task->update($request->all());
        return $this->respondSuccess('Task updated', $task->fresh());
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $task = Task::find($id);
        if (!$task) return $this->respondNotFound();

        $data = [
            'status' => 'completed',
            'completed_at' => now()->toDateString(),
        ];

        if ($request->has('actual_hours')) {
            $data['actual_hours'] = $request->get('actual_hours');
        }

        $task->update($data);
        return $this->respondSuccess('Task completed', $task->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Task::query()->with(['project', 'assignee', 'parent']);

        if ($request->has('project_id')) {
            $query->where('project_id', $request->get('project_id'));
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->get('assigned_to'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        $perPage = $request->get('per_page', 15);
        $items = $query->orderBy('sort_order')->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

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
        $task = Task::with(['project', 'assignee', 'parent', 'subTasks', 'timeEntries'])->find($id);
        if (!$task) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $task]);
    }
}