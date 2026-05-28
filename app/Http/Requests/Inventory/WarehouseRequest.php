<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $warehouseId = $this->route('id');

        return [
            'code' => 'required|string|max:20|unique:inventory_warehouses,code,' . $warehouseId,
            'name' => 'required|string|max:200',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Warehouse code is required.',
            'code.unique' => 'This warehouse code is already in use.',
            'name.required' => 'Warehouse name is required.',
        ];
    }
}