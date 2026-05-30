<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Finance\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends BaseApiController
{
    protected array $validationRules = [
        'account_id' => 'required|exists:finance_accounts,id',
        'type' => 'required|in:debit,credit',
        'amount' => 'required|numeric|min:0',
        'description' => 'nullable|string',
        'reference_type' => 'nullable|string|max:50',
        'reference_id' => 'nullable|integer',
        'transaction_date' => 'required|date',
        'status' => 'sometimes|in:pending,posted,void',
        'created_by' => 'nullable|exists:users,id',
        'approved_by' => 'nullable|exists:users,id',
    ];

    protected array $validationMessages = [
        'account_id.required' => 'Account is required.',
        'account_id.exists' => 'Selected account does not exist.',
        'type.required' => 'Transaction type is required.',
        'type.in' => 'Type must be debit or credit.',
        'amount.required' => 'Amount is required.',
        'amount.min' => 'Amount must be a positive value.',
    ];

    public function __construct()
    {
        parent::__construct(new Transaction());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) return $error;

        $data = $request->all();
        $data['created_by'] = $data['created_by'] ?? Auth::id();

        $transaction = Transaction::create($data);
        return $this->respondCreated($transaction, 'Transaction created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::find($id);
        if (!$transaction) return $this->respondNotFound();

        if ($transaction->status === 'posted') {
            return $this->respondError('Cannot update a posted transaction', 422);
        }

        $error = $this->validate($request->all());
        if ($error) return $error;

        $transaction->update($request->all());
        return $this->respondSuccess('Transaction updated', $transaction->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $transaction = Transaction::find($id);
        if (!$transaction) return $this->respondNotFound();

        if ($transaction->status === 'posted') {
            return $this->respondError('Cannot delete a posted transaction', 422);
        }

        $transaction->delete();
        return $this->respondSuccess('Transaction deleted successfully');
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::find($id);
        if (!$transaction) return $this->respondNotFound();

        if ($transaction->status !== 'pending') {
            return $this->respondError('Only pending transactions can be approved', 422);
        }

        $transaction->update([
            'status' => 'posted',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $this->respondSuccess('Transaction approved', $transaction->fresh());
    }

    public function void(int $id): JsonResponse
    {
        $transaction = Transaction::find($id);
        if (!$transaction) return $this->respondNotFound();

        if ($transaction->status === 'void') {
            return $this->respondError('Transaction is already void', 422);
        }

        $transaction->update(['status' => 'void']);
        return $this->respondSuccess('Transaction voided', $transaction->fresh());
    }

    public function byAccount(Request $request, int $accountId): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $items = Transaction::where('account_id', $accountId)
            ->orderBy('transaction_date', 'desc')
            ->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}