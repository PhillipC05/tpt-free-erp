<?php

namespace App\Http\Requests\Quality;

use Illuminate\Foundation\Http\FormRequest;

class QualityCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $checkId = $this->route('id');

        return [
            'check_code' => 'required|string|max:50|unique:quality_checks,check_code,' . $checkId,
            'product_id' => 'required|exists:inventory_products,id',
            'reference_type' => 'nullable|string|max:50',
            'reference_id' => 'nullable|integer',
            'type' => 'required|in:incoming,in_process,finished,dispatch',
            'result' => 'required|in:pass,fail,conditional',
            'notes' => 'nullable|string',
            'inspected_by' => 'nullable|exists:hr_employees,id',
            'inspected_at' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'check_code.required' => 'Check code is required.',
            'check_code.unique' => 'This check code is already in use.',
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'type.required' => 'Check type is required.',
            'type.in' => 'Type must be one of: incoming, in_process, finished, dispatch.',
            'result.required' => 'Result is required.',
            'result.in' => 'Result must be one of: pass, fail, conditional.',
            'inspected_by.exists' => 'Selected inspector does not exist.',
        ];
    }
}