<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $taskId = $this->route('id');

        return [
            'code' => 'required|string|max:20|unique:project_tasks,code,' . $taskId,
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
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Task code is required.',
            'code.unique' => 'This task code is already in use.',
            'project_id.required' => 'Project is required.',
            'project_id.exists' => 'Selected project does not exist.',
            'title.required' => 'Task title is required.',
            'due_date.after_or_equal' => 'Due date must be after or equal to start date.',
            'assigned_to.exists' => 'Selected assignee does not exist.',
            'parent_id.exists' => 'Selected parent task does not exist.',
        ];
    }
}