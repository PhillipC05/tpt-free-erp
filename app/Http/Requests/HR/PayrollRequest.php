<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class PayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $payrollId = $this->route('id');

        return [
            'payroll_number' => 'required|string|max:50|unique:hr_payroll,payroll_number,' . $payrollId,
            'employee_id' => 'required|exists:hr_employees,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'overtime' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'net_salary' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'status' => 'sometimes|in:draft,processed,approved,paid,cancelled',
            'notes' => 'nullable|string',
            'processed_by' => 'nullable|exists:users,id',
            'approved_by' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'payroll_number.required' => 'Payroll number is required.',
            'payroll_number.unique' => 'This payroll number is already in use.',
            'employee_id.required' => 'Employee is required.',
            'employee_id.exists' => 'Selected employee does not exist.',
            'period_start.required' => 'Period start date is required.',
            'period_end.required' => 'Period end date is required.',
            'period_end.after_or_equal' => 'Period end date must be after or equal to period start date.',
            'basic_salary.required' => 'Basic salary is required.',
            'net_salary.required' => 'Net salary is required.',
        ];
    }
}