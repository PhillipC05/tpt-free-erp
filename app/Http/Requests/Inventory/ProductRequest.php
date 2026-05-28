<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id');

        return [
            'sku' => 'required|string|max:50|unique:inventory_products,sku,' . $productId,
            'barcode' => 'nullable|string|max:100|unique:inventory_products,barcode,' . $productId,
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'unit' => 'required|string|max:20',
            'unit_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'valuation_method' => 'nullable|string|in:fifo,weighted_average,standard',
            'min_stock_level' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'Product SKU is required.',
            'sku.unique' => 'This SKU is already in use.',
            'name.required' => 'Product name is required.',
            'unit.required' => 'Unit is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'valuation_method.in' => 'Valuation method must be one of: fifo, weighted_average, standard.',
            'barcode.unique' => 'This barcode is already in use.',
        ];
    }
}