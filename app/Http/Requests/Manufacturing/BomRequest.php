<?php

namespace App\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class BomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bomId = $this->route('id');

        return [
            'code' => 'required|string|max:20|unique:manufacturing_boms,code,'.$bomId,
            'name' => 'required|string|max:200',
            'product_id' => 'required|exists:inventory_products,id',
            'quantity' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'BOM code is required.',
            'code.unique' => 'This BOM code is already in use.',
            'name.required' => 'BOM name is required.',
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 0.01.',
        ];
    }
}
