<?php

namespace App\Http\Requests\FieldService;

use Illuminate\Foundation\Http\FormRequest;

class ServiceTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ticketId = $this->route('id');

        return [
            'ticket_number' => 'required|string|max:50|unique:field_service_tickets,ticket_number,'.$ticketId,
            'customer_id' => 'required|exists:sales_customers,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'sometimes|in:open,assigned,in_progress,resolved,closed,cancelled',
            'assigned_to' => 'nullable|exists:hr_employees,id',
            'scheduled_date' => 'nullable|date',
            'resolution_notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_number.required' => 'Ticket number is required.',
            'ticket_number.unique' => 'This ticket number is already in use.',
            'customer_id.required' => 'Customer is required.',
            'customer_id.exists' => 'Selected customer does not exist.',
            'title.required' => 'Title is required.',
            'priority.required' => 'Priority is required.',
            'priority.in' => 'Priority must be one of: low, medium, high, urgent.',
            'assigned_to.exists' => 'Selected assignee does not exist.',
        ];
    }
}
