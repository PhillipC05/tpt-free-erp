<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = $this->route('id');

        return [
            'code' => 'required|string|max:20|unique:projects,code,'.$projectId,
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
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Project code is required.',
            'code.unique' => 'This project code is already in use.',
            'name.required' => 'Project name is required.',
            'start_date.required' => 'Start date is required.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'project_manager_id.exists' => 'Selected project manager does not exist.',
        ];
    }
}
