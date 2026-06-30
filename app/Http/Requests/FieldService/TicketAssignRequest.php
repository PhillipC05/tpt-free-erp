<?php

namespace App\Http\Requests\FieldService;

use Illuminate\Foundation\Http\FormRequest;

class TicketAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => 'required|exists:hr_employees,id',
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.required' => 'Assignee is required.',
            'assigned_to.exists' => 'Selected assignee does not exist.',
        ];
    }
}
