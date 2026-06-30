<?php

namespace App\Services\Finance;

use App\Models\Finance\Budget;
use Illuminate\Support\Collection;

class BudgetService
{
    public function createBudget(array $data): Budget
    {
        return Budget::create($data);
    }

    public function updateBudget(Budget $budget, array $data): Budget
    {
        $budget->update($data);

        return $budget->fresh();
    }

    public function getBudgetsByFiscalYear(string $fiscalYear): Collection
    {
        return Budget::where('fiscal_year', $fiscalYear)
            ->with(['account', 'department'])
            ->orderBy('code')
            ->get();
    }

    public function getBudgetVarianceReport(string $fiscalYear): array
    {
        $budgets = Budget::where('fiscal_year', $fiscalYear)
            ->with(['account', 'department'])
            ->get();

        $report = [];

        foreach ($budgets as $budget) {
            $variance = (float) $budget->budgeted_amount - (float) $budget->actual_amount;
            $utilization = $budget->budgeted_amount > 0
                ? round((float) $budget->actual_amount / (float) $budget->budgeted_amount * 100, 2)
                : 0;

            $report[] = [
                'code' => $budget->code,
                'name' => $budget->name,
                'account' => $budget->account ? $budget->account->name : null,
                'department' => $budget->department ? $budget->department->name : null,
                'budgeted_amount' => (float) $budget->budgeted_amount,
                'actual_amount' => (float) $budget->actual_amount,
                'variance' => $variance,
                'utilization_percent' => $utilization,
            ];
        }

        return $report;
    }
}
