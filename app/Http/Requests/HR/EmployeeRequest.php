<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('id');

        return [
            'employee_code' => 'required|string|max:20|unique:hr_employees,employee_code,' . $employeeId,
            'user_id' => 'nullable|exists:users,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:200|unique:hr_employees,email,' . $employeeId,
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
    }

    public function messages(): array
    {
        return [
            'employee_code.required' => 'Employee code is required.',
            'employee_code.unique' => 'This employee code is already taken.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already in use.',
            'hire_date.required' => 'Hire date is required.',
            'employment_type.required' => 'Employment type is required.',
            'employment_type.in' => 'Employment type must be one of: permanent, contract, intern, probation.',
            'department_id.exists' => 'Selected department does not exist.',
            'manager_id.exists' => 'Selected manager does not exist.',
            'termination_date.after_or_equal' => 'Termination date must be after or equal to hire date.',
        ];
    }
}