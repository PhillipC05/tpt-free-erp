<?php

namespace App\Http\Controllers\Api\Expenses;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Expenses\ExpenseItem;
use App\Models\Expenses\ExpenseReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseItemController extends BaseApiController
{
    protected string $cacheTag = 'expense_reports';

    public function __construct()
    {
        parent::__construct();
    }

    public function listItems(int $expenseId): JsonResponse
    {
        $report = ExpenseReport::find($expenseId);

        if (!$report) {
            return $this->respondNotFound('Expense report not found');
        }

        $items = ExpenseItem::where('expense_report_id', $expenseId)
            ->orderBy('expense_date', 'desc')
            ->get();

        return $this->respond(['success' => true, 'data' => $items]);
    }

    public function createItem(Request $request, int $expenseId): JsonResponse
    {
        $report = ExpenseReport::find($expenseId);

        if (!$report) {
            return $this->respondNotFound('Expense report not found');
        }

        if (!in_array($report->status, ['draft', 'submitted'])) {
            return $this->respondError('Cannot add items to an ' . $report->status . ' expense report', 422);
        }

        $error = $this->validate($request->all(), [
            'category_id' => 'nullable|exists:expense_categories,id',
            'description' => 'required|string|max:500',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
        ]);

        if ($error) {
            return $error;
        }

        $item = ExpenseItem::create(array_merge($request->only([
            'category_id', 'description', 'expense_date', 'amount', 'currency',
        ]), [
            'expense_report_id' => $expenseId,
            'currency' => $request->input('currency', 'NZD'),
        ]));

        $report->update([
            'total_amount' => ExpenseItem::where('expense_report_id', $expenseId)->sum('amount'),
        ]);

        $this->cacheFlush();

        return $this->respondCreated($item);
    }

    public function getItem(int $expenseId, int $itemId): JsonResponse
    {
        $item = ExpenseItem::where('expense_report_id', $expenseId)->find($itemId);

        if (!$item) {
            return $this->respondNotFound('Expense item not found');
        }

        return $this->respond(['success' => true, 'data' => $item]);
    }

    public function updateItem(Request $request, int $expenseId, int $itemId): JsonResponse
    {
        $report = ExpenseReport::find($expenseId);

        if (!$report) {
            return $this->respondNotFound('Expense report not found');
        }

        if (!in_array($report->status, ['draft', 'submitted'])) {
            return $this->respondError('Cannot edit items on an ' . $report->status . ' expense report', 422);
        }

        $item = ExpenseItem::where('expense_report_id', $expenseId)->find($itemId);

        if (!$item) {
            return $this->respondNotFound('Expense item not found');
        }

        $error = $this->validate($request->all(), [
            'category_id' => 'nullable|exists:expense_categories,id',
            'description' => 'sometimes|required|string|max:500',
            'expense_date' => 'sometimes|required|date',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
        ]);

        if ($error) {
            return $error;
        }

        $item->update($request->only(['category_id', 'description', 'expense_date', 'amount', 'currency']));

        $report->update([
            'total_amount' => ExpenseItem::where('expense_report_id', $expenseId)->sum('amount'),
        ]);

        $this->cacheFlush();

        return $this->respondSuccess('Expense item updated', $item);
    }

    public function deleteItem(int $expenseId, int $itemId): JsonResponse
    {
        $report = ExpenseReport::find($expenseId);

        if (!$report) {
            return $this->respondNotFound('Expense report not found');
        }

        if (!in_array($report->status, ['draft', 'submitted'])) {
            return $this->respondError('Cannot delete items from an ' . $report->status . ' expense report', 422);
        }

        $item = ExpenseItem::where('expense_report_id', $expenseId)->find($itemId);

        if (!$item) {
            return $this->respondNotFound('Expense item not found');
        }

        if ($item->receipt_path) {
            Storage::disk('local')->delete($item->receipt_path);
        }

        $item->delete();

        $report->update([
            'total_amount' => ExpenseItem::where('expense_report_id', $expenseId)->sum('amount'),
        ]);

        $this->cacheFlush();

        return $this->respondSuccess('Expense item deleted');
    }

    public function uploadReceipt(Request $request, int $expenseId, int $itemId): JsonResponse
    {
        $report = ExpenseReport::find($expenseId);

        if (!$report) {
            return $this->respondNotFound('Expense report not found');
        }

        $item = ExpenseItem::where('expense_report_id', $expenseId)->find($itemId);

        if (!$item) {
            return $this->respondNotFound('Expense item not found');
        }

        $error = $this->validate($request->all(), [
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($error) {
            return $error;
        }

        if ($item->receipt_path) {
            Storage::disk('local')->delete($item->receipt_path);
        }

        $path = $request->file('receipt')->store('expenses/receipts', 'local');

        $item->update(['receipt_path' => $path]);
        $this->cacheFlush();

        return $this->respondSuccess('Receipt uploaded', [
            'receipt_path' => $path,
            'item' => $item->fresh(),
        ]);
    }
}
