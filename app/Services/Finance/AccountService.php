<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use Illuminate\Support\Collection;

class AccountService
{
    public function createAccount(array $data): Account
    {
        $data['current_balance'] = $data['opening_balance'] ?? 0;
        return Account::create($data);
    }

    public function updateAccount(Account $account, array $data): Account
    {
        $account->update($data);
        return $account->fresh();
    }

    public function deleteAccount(Account $account): bool
    {
        if ($account->transactions()->exists()) {
            throw new \RuntimeException('Cannot delete account with existing transactions');
        }
        return $account->delete();
    }

    public function getChartOfAccounts(): Collection
    {
        return Account::whereNull('parent_id')
            ->with('children')
            ->orderBy('code')
            ->get();
    }

    public function getAccountBalance(int $accountId, ?string $startDate = null, ?string $endDate = null): float
    {
        $query = Transaction::where('account_id', $accountId)->where('status', 'posted');

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $debits = (float) (clone $query)->where('type', 'debit')->sum('amount');
        $credits = (float) (clone $query)->where('type', 'credit')->sum('amount');

        return $debits - $credits;
    }

    public function getTrialBalance(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $accounts = Account::where('is_active', true)->get();
        $result = [];

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, null, $date);
            $result[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'type' => $account->type,
                'balance' => $balance,
            ];
        }

        return $result;
    }

    public function getIncomeStatement(string $startDate, string $endDate): array
    {
        $revenueAccounts = Account::where('type', 'revenue')->where('is_active', true)->get();
        $expenseAccounts = Account::where('type', 'expense')->where('is_active', true)->get();

        $revenues = [];
        $totalRevenue = 0;

        foreach ($revenueAccounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate);
            $revenues[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'balance' => $balance,
            ];
            $totalRevenue += $balance;
        }

        $expenses = [];
        $totalExpense = 0;

        foreach ($expenseAccounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate);
            $expenses[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'balance' => $balance,
            ];
            $totalExpense += $balance;
        }

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'revenues' => $revenues,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'total_expense' => $totalExpense,
            'net_income' => $totalRevenue - $totalExpense,
        ];
    }

    public function getBalanceSheet(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $assetAccounts = Account::where('type', 'asset')->where('is_active', true)->get();
        $liabilityAccounts = Account::where('type', 'liability')->where('is_active', true)->get();
        $equityAccounts = Account::where('type', 'equity')->where('is_active', true)->get();

        $assets = [];
        $totalAssets = 0;

        foreach ($assetAccounts as $account) {
            $balance = $this->getAccountBalance($account->id, null, $date);
            $assets[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'balance' => $balance,
            ];
            $totalAssets += $balance;
        }

        $liabilities = [];
        $totalLiabilities = 0;

        foreach ($liabilityAccounts as $account) {
            $balance = $this->getAccountBalance($account->id, null, $date);
            $liabilities[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'balance' => $balance,
            ];
            $totalLiabilities += $balance;
        }

        $equity = [];
        $totalEquity = 0;

        foreach ($equityAccounts as $account) {
            $balance = $this->getAccountBalance($account->id, null, $date);
            $equity[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'balance' => $balance,
            ];
            $totalEquity += $balance;
        }

        return [
            'date' => $date,
            'assets' => ['items' => $assets, 'total' => $totalAssets],
            'liabilities' => ['items' => $liabilities, 'total' => $totalLiabilities],
            'equity' => ['items' => $equity, 'total' => $totalEquity],
            'total_liabilities_equity' => $totalLiabilities + $totalEquity,
        ];
    }
}