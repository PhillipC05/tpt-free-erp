<?php

namespace App\Http\Controllers\Api\Projects;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Projects\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends BaseApiController
{
    protected array $validationRules = [
        'code' => 'required|string|max:20|unique:projects,code',
        'name' => 'required|string|max:200',
        'description' => 'nullable|string',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'status' => 'sometimes|in:planning,in_progress,on_hold,completed,cancelled',
        'priority' => 'nullable|in:low,medium,high,urgent',
        'project_manager_id' => 'nullable|exists:hr_employees,id',
        'budget' => 'nullable|numeric|min:0',
        'actual_cost' => 'nullable|numeric|min:0',
    ];

    protected array $validationMessages = [
        'code.required' => 'Project code is required.',
        'code.unique' => 'This project code is already in use.',
        'name.required' => 'Project name is required.',
        'start_date.required' => 'Start date is required.',
    ];

    public function __construct()
    {
        parent::__construct(new Project());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'code' => 'required|string|max:20|unique:projects,code',
        ]));
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'planning';

        $project = Project::create($data);
        return $this->respondCreated($project, 'Project created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $project = Project::find($id);
        if (!$project) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:20|unique:projects,code,' . $id,
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|in:planning,in_progress,on_hold,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'project_manager_id' => 'nullable|exists:hr_employees,id',
            'budget' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
        ]);
        if ($error) return $error;

        $project->update($request->all());
        return $this->respondSuccess('Project updated', $project->fresh());
    }

    public function show(int $id): JsonResponse
    {
        $project = Project::with(['manager', 'tasks'])->find($id);
        if (!$project) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $project]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Project::query()->with('manager');

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->has('project_manager_id')) {
            $query->where('project_manager_id', $request->query('project_manager_id'));
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

    public function tasks(int $id): JsonResponse
    {
        $project = Project::with('tasks.assignee')->find($id);
        if (!$project) return $this->respondNotFound();

        return $this->respond([
            'success' => true,
            'data' => $project->tasks,
        ]);
    }

    public function summary(int $id): JsonResponse
    {
        $project = Project::with('tasks')->find($id);
        if (!$project) return $this->respondNotFound();

        $totalTasks = $project->tasks->count();
        $completedTasks = $project->tasks->where('status', 'completed')->count();
        $inProgressTasks = $project->tasks->where('status', 'in_progress')->count();
        $pendingTasks = $project->tasks->where('status', 'pending')->count();

        return $this->respond([
            'success' => true,
            'data' => [
                'project' => $project,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'in_progress_tasks' => $inProgressTasks,
                'pending_tasks' => $pendingTasks,
                'completion_percentage' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            ],
        ]);
    }
}