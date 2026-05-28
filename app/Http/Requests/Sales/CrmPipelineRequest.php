<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class CrmPipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pipelineId = $this->route('id');

        return [
            'code' => 'required|string|max:20|unique:sales_crm_pipelines,code,' . $pipelineId,
            'name' => 'required|string|max:200',
            'customer_id' => 'required|exists:sales_customers,id',
            'contact_name' => 'nullable|string|max:200',
            'contact_email' => 'nullable|email|max:200',
            'contact_phone' => 'nullable|string|max:20',
            'stage' => 'required|in:lead,qualified,proposal,negotiation,closed_won,closed_lost',
            'value' => 'nullable|numeric|min:0',
            'probability' => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'actual_close_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Pipeline code is required.',
            'code.unique' => 'This pipeline code is already in use.',
            'name.required' => 'Opportunity name is required.',
            'customer_id.required' => 'Customer is required.',
            'customer_id.exists' => 'Selected customer does not exist.',
            'stage.required' => 'Stage is required.',
            'stage.in' => 'Stage must be one of: lead, qualified, proposal, negotiation, closed_won, closed_lost.',
            'probability.min' => 'Probability must be between 0 and 100.',
            'probability.max' => 'Probability must be between 0 and 100.',
            'assigned_to.exists' => 'Selected assigned user does not exist.',
        ];
    }
}