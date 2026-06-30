<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class LeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:hr_employees,id',
            'leave_type' => 'required|in:annual,sick,personal,unpaid,maternity,paternity,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'total_days' => 'required|numeric|min:0.5',
            'reason' => 'nullable|string',
            'status' => 'sometimes|in:pending,approved,rejected,cancelled',
            'approved_by' => 'nullable|exists:hr_employees,id',
            'rejection_reason' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee is required.',
            'employee_id.exists' => 'Selected employee does not exist.',
            'leave_type.required' => 'Leave type is required.',
            'leave_type.in' => 'Leave type must be one of: annual, sick, personal, unpaid, maternity, paternity, other.',
            'start_date.required' => 'Start date is required.',
            'end_date.required' => 'End date is required.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'total_days.required' => 'Total days is required.',
            'total_days.min' => 'Total days must be at least 0.5.',
        ];
    }
}
