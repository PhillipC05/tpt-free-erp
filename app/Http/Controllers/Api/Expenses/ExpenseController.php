<?php

namespace App\Http\Controllers\Api\Expenses;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Expenses\ExpenseReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends BaseApiController
{
    protected string $cacheTag = 'expense_reports';

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $query = ExpenseReport::query();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        $perPage = (int) $request->query('per_page', 15);
        $reports = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'title' => 'required|string|max:255',
            'department_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        if ($error) {
            return $error;
        }

        $report = ExpenseReport::create(array_merge($request->only([
            'title', 'department_id', 'project_id', 'notes',
        ]), [
            'user_id' => $request->user()->id,
            'status' => 'draft',
            'total_amount' => 0,
        ]));

        $this->cacheFlush();

        return $this->respondCreated($report);
    }

    public function show(int $id): JsonResponse
    {
        $report = ExpenseReport::with('items')->find($id);

        if (!$report) {
            return $this->respondNotFound('Expense report not found');
        }

        return $this->respond(['success' => true, 'data' => $report]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $report = ExpenseReport::find($id);

        if (!$report) {
            return $this->respondNotFound('Expense report not found');
        }

        $error = $this->validate($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'status' => 'nullable|string|in:draft,submitted,approved,rejected',
            'department_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        if ($error) {
            return $error;
        }

        $report->update($request->all());
        $this->cacheFlush();

        return $this->respondSuccess('Expense report updated successfully', $report);
    }

    public function approve(int $id): JsonResponse
    {
        $report = ExpenseReport::find($id);

        if (!$report) {
            return $this->respondNotFound('Expense report not found');
        }

        $report->update([
            'status' => 'approved',
            'approved_by' => request()->user()->id,
            'approved_at' => now(),
        ]);

        $this->cacheFlush();

        return $this->respondSuccess('Expense report approved successfully', $report);
    }

    public function reject(int $id): JsonResponse
    {
        $report = ExpenseReport::find($id);

        if (!$report) {
            return $this->respondNotFound('Expense report not found');
        }

        $report->update(['status' => 'rejected']);
        $this->cacheFlush();

        return $this->respondSuccess('Expense report rejected', $report);
    }

    public function destroy(int $id): JsonResponse
    {
        $report = ExpenseReport::find($id);

        if (!$report) {
            return $this->respondNotFound('Expense report not found');
        }

        if ($report->status !== 'draft') {
            return $this->respondError('Only draft expense reports can be deleted', 422);
        }

        $report->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Expense report deleted successfully');
    }
}
