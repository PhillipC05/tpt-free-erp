<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orderId = $this->route('id');

        return [
            'order_number' => 'required|string|max:50|unique:sales_orders,order_number,' . $orderId,
            'customer_id' => 'required|exists:sales_customers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'status' => 'sometimes|in:draft,confirmed,processing,shipped,delivered,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'created_by' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'order_number.required' => 'Order number is required.',
            'order_number.unique' => 'This order number is already in use.',
            'customer_id.required' => 'Customer is required.',
            'customer_id.exists' => 'Selected customer does not exist.',
            'order_date.required' => 'Order date is required.',
            'subtotal.required' => 'Subtotal is required.',
            'total_amount.required' => 'Total amount is required.',
            'expected_delivery_date.after_or_equal' => 'Expected delivery date must be after or equal to order date.',
        ];
    }
}