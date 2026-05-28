<?php

namespace App\Http\Requests\FieldService;

use Illuminate\Foundation\Http\FormRequest;

class TicketResolveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution_notes' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'resolution_notes.required' => 'Resolution notes are required.',
        ];
    }
}