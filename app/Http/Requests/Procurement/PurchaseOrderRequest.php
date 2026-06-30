<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $poId = $this->route('id');

        return [
            'po_number' => 'required|string|max:50|unique:procurement_purchase_orders,po_number,'.$poId,
            'vendor_id' => 'required|exists:procurement_vendors,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'status' => 'sometimes|in:draft,sent,confirmed,received,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'created_by' => 'nullable|exists:users,id',
            'approved_by' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'po_number.required' => 'Purchase order number is required.',
            'po_number.unique' => 'This PO number is already in use.',
            'vendor_id.required' => 'Vendor is required.',
            'vendor_id.exists' => 'Selected vendor does not exist.',
            'order_date.required' => 'Order date is required.',
            'subtotal.required' => 'Subtotal is required.',
            'total_amount.required' => 'Total amount is required.',
            'expected_delivery_date.after_or_equal' => 'Expected delivery date must be after or equal to order date.',
        ];
    }
}
