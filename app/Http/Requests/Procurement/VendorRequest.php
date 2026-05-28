<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class VendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vendorId = $this->route('id');

        return [
            'code' => 'required|string|max:20|unique:procurement_vendors,code,' . $vendorId,
            'name' => 'required|string|max:200',
            'email' => 'required|email|max:200|unique:procurement_vendors,email,' . $vendorId,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive,blocked',
            'current_balance' => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vendor code is required.',
            'code.unique' => 'This vendor code is already in use.',
            'name.required' => 'Vendor name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already in use.',
        ];
    }
}