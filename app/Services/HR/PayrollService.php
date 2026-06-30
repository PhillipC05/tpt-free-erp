<?php

namespace App\Services\HR;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\HR\Payroll;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function createPayroll(array $data): Payroll
    {
        return Payroll::create($data);
    }

    public function processPayroll(Payroll $payroll): Payroll
    {
        if ($payroll->status !== 'draft') {
            throw new \RuntimeException('Only draft payrolls can be processed');
        }

        return DB::transaction(function () use ($payroll) {
            $payroll->update([
                'status' => 'processed',
                'processed_by' => auth()->id(),
            ]);

            return $payroll->fresh();
        });
    }

    public function approvePayroll(Payroll $payroll): Payroll
    {
        if ($payroll->status !== 'processed') {
            throw new \RuntimeException('Only processed payrolls can be approved');
        }

        return DB::transaction(function () use ($payroll) {
            $payroll->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return $payroll->fresh();
        });
    }

    public function markPayrollPaid(Payroll $payroll, array $data): Payroll
    {
        if ($payroll->status !== 'approved') {
            throw new \RuntimeException('Only approved payrolls can be marked as paid');
        }

        $payroll->update(array_merge($data, ['status' => 'paid']));

        return $payroll->fresh();
    }

    public function getPayrollSummary(?string $periodStart = null, ?string $periodEnd = null): array
    {
        $query = Payroll::whereIn('status', ['processed', 'approved', 'paid']);

        if ($periodStart) {
            $query->where('period_start', '>=', $periodStart);
        }
        if ($periodEnd) {
            $query->where('period_end', '<=', $periodEnd);
        }

        $payrolls = $query->get();

        return [
            'total_employees' => $payrolls->count(),
            'total_basic_salary' => (float) $payrolls->sum('basic_salary'),
            'total_allowances' => (float) $payrolls->sum('allowances'),
            'total_overtime' => (float) $payrolls->sum('overtime'),
            'total_deductions' => (float) $payrolls->sum('deductions'),
            'total_tax' => (float) $payrolls->sum('tax_amount'),
            'total_net_salary' => (float) $payrolls->sum('net_salary'),
        ];
    }

    public function calculatePayroll(Employee $employee, string $periodStart, string $periodEnd): array
    {
        $baseSalary = (float) $employee->salary;

        // Calculate overtime from attendance
        $overtimeHours = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->where('status', 'present')
            ->whereRaw('total_hours > 8')
            ->sum(DB::raw('GREATEST(total_hours - 8, 0)'));

        $hourlyRate = $baseSalary / (22 * 8); // 22 working days, 8 hours per day
        $overtime = round($overtimeHours * $hourlyRate * 1.5, 2); // 1.5x overtime rate

        $allowances = round($baseSalary * 0.1, 2); // 10% standard allowance
        $deductions = round($baseSalary * 0.05, 2); // 5% standard deductions
        $taxAmount = round(($baseSalary + $allowances + $overtime - $deductions) * 0.15, 2); // 15% tax

        $grossPay = $baseSalary + $allowances + $overtime;
        $netSalary = $grossPay - $deductions - $taxAmount;

        return [
            'basic_salary' => $baseSalary,
            'allowances' => $allowances,
            'overtime' => $overtime,
            'deductions' => $deductions,
            'tax_amount' => $taxAmount,
            'gross_pay' => $grossPay,
            'net_salary' => $netSalary,
        ];
    }
}
