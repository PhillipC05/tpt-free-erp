<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class PayrollPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_date.date' => 'Please provide a valid payment date.',
        ];
    }
}