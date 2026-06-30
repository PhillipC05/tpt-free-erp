<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('id');

        return [
            'code' => 'required|string|max:20|unique:hr_departments,code,'.$departmentId,
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:hr_employees,id',
            'parent_id' => 'nullable|exists:hr_departments,id',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Department code is required.',
            'code.unique' => 'This department code is already in use.',
            'name.required' => 'Department name is required.',
            'manager_id.exists' => 'Selected manager does not exist.',
            'parent_id.exists' => 'Selected parent department does not exist.',
        ];
    }
}
