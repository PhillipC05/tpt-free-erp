<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Finance\Budget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends BaseApiController
{
    protected string $cacheTag = 'finance_budgets';

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $query = Budget::query();

        if ($request->filled('year')) {
            $query->where('year', $request->query('year'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }

        $perPage = (int) $request->query('per_page', 15);
        $budgets = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $budgets->items(),
            'meta' => [
                'current_page' => $budgets->currentPage(),
                'last_page' => $budgets->lastPage(),
                'per_page' => $budgets->perPage(),
                'total' => $budgets->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:255',
            'period_type' => 'required|string|in:annual,quarterly,monthly',
            'year' => 'required|integer|min:2000|max:2100',
            'period' => 'nullable|integer|min:1|max:12',
            'department_id' => 'nullable|integer',
            'status' => 'nullable|string|in:draft,active,closed',
            'notes' => 'nullable|string',
        ]);

        if ($error) {
            return $error;
        }

        $budget = Budget::create(array_merge($request->all(), [
            'created_by' => $request->user()->id,
        ]));

        $this->cacheFlush();

        return $this->respondCreated($budget);
    }

    public function show(int $id): JsonResponse
    {
        $budget = Budget::with('lines')->find($id);

        if (!$budget) {
            return $this->respondNotFound('Budget not found');
        }

        return $this->respond(['success' => true, 'data' => $budget]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $budget = Budget::find($id);

        if (!$budget) {
            return $this->respondNotFound('Budget not found');
        }

        $error = $this->validate($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'period_type' => 'sometimes|required|string|in:annual,quarterly,monthly',
            'year' => 'sometimes|required|integer|min:2000|max:2100',
            'period' => 'nullable|integer|min:1|max:12',
            'department_id' => 'nullable|integer',
            'status' => 'nullable|string|in:draft,active,closed',
            'notes' => 'nullable|string',
        ]);

        if ($error) {
            return $error;
        }

        $budget->update($request->all());
        $this->cacheFlush();

        return $this->respondSuccess('Budget updated successfully', $budget);
    }

    public function destroy(int $id): JsonResponse
    {
        $budget = Budget::find($id);

        if (!$budget) {
            return $this->respondNotFound('Budget not found');
        }

        $budget->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Budget deleted successfully');
    }

    public function approve(int $id): JsonResponse
    {
        $budget = Budget::find($id);

        if (!$budget) {
            return $this->respondNotFound('Budget not found');
        }

        if ($budget->status !== 'draft') {
            return $this->respondError('Only draft budgets can be approved', 422);
        }

        $budget->update(['status' => 'active']);
        $this->cacheFlush();

        return $this->respondSuccess('Budget approved and activated', $budget);
    }

    public function close(int $id): JsonResponse
    {
        $budget = Budget::find($id);

        if (!$budget) {
            return $this->respondNotFound('Budget not found');
        }

        if ($budget->status !== 'active') {
            return $this->respondError('Only active budgets can be closed', 422);
        }

        $budget->update(['status' => 'closed']);
        $this->cacheFlush();

        return $this->respondSuccess('Budget closed', $budget);
    }
}
