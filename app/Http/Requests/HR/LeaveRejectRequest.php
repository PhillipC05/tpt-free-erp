<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class LeaveRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Rejection reason is required.',
        ];
    }
}