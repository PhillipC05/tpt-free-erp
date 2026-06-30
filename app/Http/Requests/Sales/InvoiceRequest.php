<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoiceId = $this->route('id');

        return [
            'invoice_number' => 'required|string|max:50|unique:sales_invoices,invoice_number,'.$invoiceId,
            'order_id' => 'nullable|exists:sales_orders,id',
            'customer_id' => 'required|exists:sales_customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'balance_due' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled,partially_paid',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_number.required' => 'Invoice number is required.',
            'invoice_number.unique' => 'This invoice number is already in use.',
            'customer_id.required' => 'Customer is required.',
            'customer_id.exists' => 'Selected customer does not exist.',
            'invoice_date.required' => 'Invoice date is required.',
            'due_date.required' => 'Due date is required.',
            'due_date.after_or_equal' => 'Due date must be after or equal to invoice date.',
            'total_amount.required' => 'Total amount is required.',
            'order_id.exists' => 'Selected order does not exist.',
        ];
    }
}
