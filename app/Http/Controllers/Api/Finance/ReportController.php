<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Finance\Account;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());
        $accounts = Account::where('is_active', true)->get();
        $result = [];

        foreach ($accounts as $account) {
            $debits = Transaction::where('account_id', $account->id)
                ->where('type', 'debit')
                ->where('status', 'posted')
                ->where('transaction_date', '<=', $date)
                ->sum('amount');

            $credits = Transaction::where('account_id', $account->id)
                ->where('type', 'credit')
                ->where('status', 'posted')
                ->where('transaction_date', '<=', $date)
                ->sum('amount');

            $result[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'type' => $account->type,
                'total_debits' => (float) $debits,
                'total_credits' => (float) $credits,
                'balance' => (float) ($debits - $credits),
            ];
        }

        $totalDebits = array_sum(array_column($result, 'total_debits'));
        $totalCredits = array_sum(array_column($result, 'total_credits'));

        return $this->respond([
            'success' => true,
            'data' => [
                'date' => $date,
                'accounts' => $result,
                'totals' => [
                    'total_debits' => $totalDebits,
                    'total_credits' => $totalCredits,
                    'difference' => $totalDebits - $totalCredits,
                ],
            ],
        ]);
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

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

        $netIncome = $totalRevenue - $totalExpense;

        return $this->respond([
            'success' => true,
            'data' => [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'revenues' => $revenues,
                'total_revenue' => $totalRevenue,
                'expenses' => $expenses,
                'total_expense' => $totalExpense,
                'net_income' => $netIncome,
            ],
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());

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

        return $this->respond([
            'success' => true,
            'data' => [
                'date' => $date,
                'assets' => ['items' => $assets, 'total' => $totalAssets],
                'liabilities' => ['items' => $liabilities, 'total' => $totalLiabilities],
                'equity' => ['items' => $equity, 'total' => $totalEquity],
                'total_liabilities_equity' => $totalLiabilities + $totalEquity,
            ],
        ]);
    }

    private function getAccountBalance(int $accountId, ?string $startDate, ?string $endDate): float
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
}