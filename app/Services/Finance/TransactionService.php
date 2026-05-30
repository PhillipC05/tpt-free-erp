<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $data['created_by'] = $data['created_by'] ?? Auth::id();
            $data['status'] = $data['status'] ?? 'pending';

            $transaction = Transaction::create($data);

            // Update account balance if posting directly
            if ($data['status'] === 'posted') {
                $this->updateAccountBalance($data['account_id'], $data['type'], $data['amount']);
            }

            return $transaction;
        });
    }

    public function approveTransaction(Transaction $transaction, int $approverId): Transaction
    {
        if ($transaction->status !== 'pending') {
            throw new \RuntimeException('Only pending transactions can be approved');
        }

        return DB::transaction(function () use ($transaction, $approverId) {
            $transaction->update([
                'status' => 'posted',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            $this->updateAccountBalance($transaction->account_id, $transaction->type, $transaction->amount);

            return $transaction->fresh();
        });
    }

    public function voidTransaction(Transaction $transaction): Transaction
    {
        if ($transaction->status === 'void') {
            throw new \RuntimeException('Transaction is already void');
        }

        return DB::transaction(function () use ($transaction) {
            // Reverse the balance effect if it was posted
            if ($transaction->status === 'posted') {
                $reversalType = $transaction->type === 'debit' ? 'credit' : 'debit';
                $this->updateAccountBalance($transaction->account_id, $reversalType, $transaction->amount);
            }

            $transaction->update(['status' => 'void']);

            return $transaction->fresh();
        });
    }

    public function getTransactionsByAccount(int $accountId, array $filters = [])
    {
        $query = Transaction::where('account_id', $accountId)
            ->with('account')
            ->orderBy('transaction_date', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('transaction_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('transaction_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate(min($filters['per_page'] ?? 15, 100));
    }

    public function getAccountsPayable(): array
    {
        $liabilityAccounts = Account::where('type', 'liability')
            ->where('is_active', true)
            ->where('category', 'payable')
            ->get();

        $payables = [];
        $totalPayable = 0;

        foreach ($liabilityAccounts as $account) {
            $balance = abs((float) $account->current_balance);
            $payables[] = [
                'account_id' => $account->id,
                'account_code' => $account->code,
                'account_name' => $account->name,
                'balance' => $balance,
            ];
            $totalPayable += $balance;
        }

        return ['items' => $payables, 'total' => $totalPayable];
    }

    public function getAccountsReceivable(): array
    {
        $assetAccounts = Account::where('type', 'asset')
            ->where('is_active', true)
            ->where('category', 'receivable')
            ->get();

        $receivables = [];
        $totalReceivable = 0;

        foreach ($assetAccounts as $account) {
            $balance = (float) $account->current_balance;
            $receivables[] = [
                'account_id' => $account->id,
                'account_code' => $account->code,
                'account_name' => $account->name,
                'balance' => $balance,
            ];
            $totalReceivable += $balance;
        }

        return ['items' => $receivables, 'total' => $totalReceivable];
    }

    private function updateAccountBalance(int $accountId, string $type, float $amount): void
    {
        $account = Account::findOrFail($accountId);

        $newBalance = (float) $account->current_balance;

        if ($type === 'debit') {
            // Debit increases asset/expense accounts, decreases liability/equity/revenue
            if (in_array($account->type, ['asset', 'expense'])) {
                $newBalance += $amount;
            } else {
                $newBalance -= $amount;
            }
        } else {
            // Credit increases liability/equity/revenue, decreases asset/expense
            if (in_array($account->type, ['liability', 'equity', 'revenue'])) {
                $newBalance += $amount;
            } else {
                $newBalance -= $amount;
            }
        }

        $account->update(['current_balance' => $newBalance]);
    }
}