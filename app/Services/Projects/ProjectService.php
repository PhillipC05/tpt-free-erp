<?php

namespace App\Services\Projects;

use App\Models\Projects\Project;
use App\Models\Projects\Task;
use App\Models\Projects\TimeEntry;
use Illuminate\Support\Collection;

class ProjectService
{
    public function createProject(array $data): Project
    {
        $data['status'] = $data['status'] ?? 'planning';

        return Project::create($data);
    }

    public function updateProject(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }

    public function getProjectSummary(Project $project): array
    {
        $tasks = $project->tasks;
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->whereIn('status', ['done', 'completed'])->count();
        $inProgressTasks = $tasks->where('status', 'in_progress')->count();
        $pendingTasks = $tasks->where('status', 'todo')->count();

        $totalEstimated = $tasks->sum('estimated_hours');
        $totalActual = $tasks->sum('actual_hours');

        return [
            'project' => $project,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'in_progress_tasks' => $inProgressTasks,
            'pending_tasks' => $pendingTasks,
            'completion_percentage' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            'total_estimated_hours' => $totalEstimated,
            'total_actual_hours' => $totalActual,
            'budget_utilization' => $project->budget > 0
                ? round(($project->actual_cost / $project->budget) * 100, 2)
                : 0,
        ];
    }

    public function getProjectGanttData(Project $project): array
    {
        $tasks = $project->tasks()->orderBy('start_date')->orderBy('sort_order')->get();

        return [
            'project' => $project,
            'tasks' => $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'code' => $task->code,
                    'title' => $task->title,
                    'start_date' => $task->start_date?->toDateString(),
                    'due_date' => $task->due_date?->toDateString(),
                    'duration' => $task->start_date && $task->due_date
                        ? $task->start_date->diffInDays($task->due_date) + 1
                        : null,
                    'progress' => $task->status === 'done' ? 100 : ($task->status === 'in_progress' ? 50 : 0),
                    'assigned_to' => $task->assignee ? $task->assignee->first_name.' '.$task->assignee->last_name : null,
                    'status' => $task->status,
                    'dependencies' => $task->parent_id ? [$task->parent_id] : [],
                ];
            }),
        ];
    }

    public function getResourceAllocation(?int $projectId = null): Collection
    {
        $query = Task::with(['assignee', 'project'])
            ->whereIn('status', ['todo', 'in_progress'])
            ->whereNotNull('assigned_to');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->orderBy('assigned_to')->get()->groupBy('assigned_to');
    }

    public function getTimeReport(int $projectId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = TimeEntry::whereHas('task', function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        })->with(['task', 'user']);

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $entries = $query->orderBy('date', 'desc')->get();

        return [
            'project_id' => $projectId,
            'total_hours' => (float) $entries->sum('hours'),
            'billable_hours' => (float) $entries->where('is_billable', true)->sum('hours'),
            'total_entries' => $entries->count(),
            'entries' => $entries,
        ];
    }
}
