<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => 'required|exists:finance_accounts,id',
            'type' => 'required|in:debit,credit',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'reference_type' => 'nullable|string|max:50',
            'reference_id' => 'nullable|integer',
            'transaction_date' => 'required|date',
            'status' => 'sometimes|in:pending,posted,void',
            'created_by' => 'nullable|exists:users,id',
            'approved_by' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'account_id.required' => 'Account is required.',
            'account_id.exists' => 'Selected account does not exist.',
            'type.required' => 'Transaction type is required.',
            'type.in' => 'Type must be debit or credit.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be a positive value.',
            'transaction_date.required' => 'Transaction date is required.',
        ];
    }
}
