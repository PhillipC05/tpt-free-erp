<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('id');

        return [
            'code' => 'required|string|max:20|unique:sales_customers,code,'.$customerId,
            'name' => 'required|string|max:200',
            'email' => 'required|email|max:200|unique:sales_customers,email,'.$customerId,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
            'status' => 'sometimes|in:active,inactive,blocked',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Customer code is required.',
            'code.unique' => 'This customer code is already in use.',
            'name.required' => 'Customer name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already in use.',
            'assigned_to.exists' => 'Selected assigned user does not exist.',
        ];
    }
}
