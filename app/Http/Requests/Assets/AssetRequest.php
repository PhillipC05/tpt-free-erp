<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;

class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assetId = $this->route('id');

        return [
            'asset_code' => 'required|string|max:50|unique:assets,asset_code,' . $assetId,
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'type' => 'required|string|max:100',
            'serial_number' => 'nullable|string|max:100|unique:assets,serial_number,' . $assetId,
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_years' => 'nullable|integer|min:1',
            'depreciation_method' => 'nullable|in:straight_line,declining_balance,sum_of_years,units_of_production',
            'status' => 'sometimes|in:active,in_use,under_maintenance,retired,disposed',
            'assigned_to' => 'nullable|exists:hr_employees,id',
            'location_id' => 'nullable|exists:inventory_warehouses,id',
        ];
    }

    public function messages(): array
    {
        return [
            'asset_code.required' => 'Asset code is required.',
            'asset_code.unique' => 'This asset code is already in use.',
            'name.required' => 'Asset name is required.',
            'type.required' => 'Asset type is required.',
            'purchase_date.required' => 'Purchase date is required.',
            'purchase_cost.required' => 'Purchase cost is required.',
            'serial_number.unique' => 'This serial number is already in use.',
            'assigned_to.exists' => 'Selected assignee does not exist.',
            'location_id.exists' => 'Selected location does not exist.',
            'depreciation_method.in' => 'Depreciation method must be one of: straight_line, declining_balance, sum_of_years, units_of_production.',
        ];
    }
}