<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accountId = $this->route('id');

        return [
            'code' => 'required|string|max:20|unique:finance_accounts,code,' . $accountId,
            'name' => 'required|string|max:200',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:finance_accounts,id',
            'is_active' => 'boolean',
            'currency' => 'nullable|string|size:3',
            'opening_balance' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Account code is required.',
            'code.unique' => 'This account code is already in use.',
            'name.required' => 'Account name is required.',
            'type.required' => 'Account type is required.',
            'type.in' => 'Type must be one of: asset, liability, equity, revenue, expense.',
            'parent_id.exists' => 'Selected parent account does not exist.',
        ];
    }
}