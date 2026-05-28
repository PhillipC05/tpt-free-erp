<?php

namespace App\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class WorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $woId = $this->route('id');

        return [
            'wo_number' => 'required|string|max:50|unique:manufacturing_work_orders,wo_number,' . $woId,
            'product_id' => 'required|exists:inventory_products,id',
            'bom_id' => 'nullable|exists:manufacturing_boms,id',
            'planned_quantity' => 'required|numeric|min:1',
            'produced_quantity' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|in:planned,in_progress,completed,cancelled,on_hold',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:hr_employees,id',
        ];
    }

    public function messages(): array
    {
        return [
            'wo_number.required' => 'Work order number is required.',
            'wo_number.unique' => 'This work order number is already in use.',
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'planned_quantity.required' => 'Planned quantity is required.',
            'planned_quantity.min' => 'Planned quantity must be at least 1.',
            'start_date.required' => 'Start date is required.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'bom_id.exists' => 'Selected BOM does not exist.',
            'assigned_to.exists' => 'Selected employee does not exist.',
        ];
    }
}