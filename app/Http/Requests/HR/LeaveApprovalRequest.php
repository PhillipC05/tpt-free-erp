<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class LeaveApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approved_by' => 'nullable|exists:hr_employees,id',
        ];
    }

    public function messages(): array
    {
        return [
            'approved_by.exists' => 'Selected approver does not exist.',
        ];
    }
}