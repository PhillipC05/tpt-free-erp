<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class TaskCompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_hours' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'actual_hours.min' => 'Actual hours must be at least 0.',
        ];
    }
}