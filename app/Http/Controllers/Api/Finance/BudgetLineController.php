<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Finance\Budget;
use App\Models\Finance\BudgetLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetLineController extends BaseApiController
{
    protected string $cacheTag = 'finance_budgets';

    public function __construct()
    {
        parent::__construct();
    }

    public function index(int $budgetId): JsonResponse
    {
        $budget = Budget::find($budgetId);

        if (!$budget) {
            return $this->respondNotFound('Budget not found');
        }

        $lines = BudgetLine::where('budget_id', $budgetId)->get();

        return $this->respond(['success' => true, 'data' => $lines]);
    }

    public function store(Request $request, int $budgetId): JsonResponse
    {
        $budget = Budget::find($budgetId);

        if (!$budget) {
            return $this->respondNotFound('Budget not found');
        }

        if ($budget->status === 'closed') {
            return $this->respondError('Cannot add lines to a closed budget', 422);
        }

        $error = $this->validate($request->all(), [
            'account_id' => 'required|exists:finance_accounts,id',
            'budgeted_amount' => 'required|numeric|min:0',
            'actual_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($error) {
            return $error;
        }

        $exists = BudgetLine::where('budget_id', $budgetId)
            ->where('account_id', $request->input('account_id'))
            ->exists();

        if ($exists) {
            return $this->respondError('A line for this account already exists in the budget', 422);
        }

        $line = BudgetLine::create(array_merge($request->only([
            'account_id', 'budgeted_amount', 'actual_amount', 'notes',
        ]), [
            'budget_id' => $budgetId,
            'actual_amount' => $request->input('actual_amount', 0),
        ]));

        $this->cacheFlush();

        return $this->respondCreated($line);
    }

    public function show(int $budgetId, int $lineId): JsonResponse
    {
        $line = BudgetLine::where('budget_id', $budgetId)->find($lineId);

        if (!$line) {
            return $this->respondNotFound('Budget line not found');
        }

        return $this->respond(['success' => true, 'data' => $line]);
    }

    public function update(Request $request, int $budgetId, int $lineId): JsonResponse
    {
        $budget = Budget::find($budgetId);

        if (!$budget) {
            return $this->respondNotFound('Budget not found');
        }

        if ($budget->status === 'closed') {
            return $this->respondError('Cannot edit lines on a closed budget', 422);
        }

        $line = BudgetLine::where('budget_id', $budgetId)->find($lineId);

        if (!$line) {
            return $this->respondNotFound('Budget line not found');
        }

        $error = $this->validate($request->all(), [
            'account_id' => 'sometimes|required|exists:finance_accounts,id',
            'budgeted_amount' => 'sometimes|required|numeric|min:0',
            'actual_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($error) {
            return $error;
        }

        $line->update($request->only(['account_id', 'budgeted_amount', 'actual_amount', 'notes']));
        $this->cacheFlush();

        return $this->respondSuccess('Budget line updated', $line);
    }

    public function destroy(int $budgetId, int $lineId): JsonResponse
    {
        $budget = Budget::find($budgetId);

        if (!$budget) {
            return $this->respondNotFound('Budget not found');
        }

        if ($budget->status === 'closed') {
            return $this->respondError('Cannot delete lines from a closed budget', 422);
        }

        $line = BudgetLine::where('budget_id', $budgetId)->find($lineId);

        if (!$line) {
            return $this->respondNotFound('Budget line not found');
        }

        $line->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Budget line deleted');
    }

    public function variance(int $budgetId): JsonResponse
    {
        $budget = Budget::with('lines')->find($budgetId);

        if (!$budget) {
            return $this->respondNotFound('Budget not found');
        }

        $lines = $budget->lines->map(fn ($line) => [
            'id' => $line->id,
            'account_id' => $line->account_id,
            'budgeted_amount' => (float) $line->budgeted_amount,
            'actual_amount' => (float) $line->actual_amount,
            'variance' => (float) $line->budgeted_amount - (float) $line->actual_amount,
            'utilization_percent' => $line->budgeted_amount > 0
                ? round((float) $line->actual_amount / (float) $line->budgeted_amount * 100, 2)
                : 0,
            'notes' => $line->notes,
        ]);

        $totalBudgeted = $lines->sum('budgeted_amount');
        $totalActual = $lines->sum('actual_amount');

        return $this->respond([
            'success' => true,
            'data' => [
                'budget_id' => $budget->id,
                'budget_name' => $budget->name,
                'status' => $budget->status,
                'total_budgeted' => $totalBudgeted,
                'total_actual' => $totalActual,
                'total_variance' => $totalBudgeted - $totalActual,
                'utilization_percent' => $totalBudgeted > 0
                    ? round($totalActual / $totalBudgeted * 100, 2)
                    : 0,
                'lines' => $lines,
            ],
        ]);
    }
}
