<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends BaseApiController
{
    protected string $cacheTag = 'finance_accounts';

    protected array $validationRules = [
        'code' => 'required|string|max:20|unique:finance_accounts,code',
        'name' => 'required|string|max:200',
        'type' => 'required|in:asset,liability,equity,revenue,expense',
        'category' => 'nullable|string|max:100',
        'description' => 'nullable|string',
        'parent_id' => 'nullable|exists:finance_accounts,id',
        'is_active' => 'boolean',
        'currency' => 'string|size:3',
        'opening_balance' => 'numeric|min:0',
    ];

    public function __construct()
    {
        parent::__construct(new Account());
    }

    public function balance(int $id): JsonResponse
    {
        $account = Account::find($id);
        if (!$account) {
            return $this->respondNotFound();
        }

        $data = $this->cacheRemember("account_balance_{$id}", function () use ($id, $account) {
            $totalDebits  = Transaction::where('account_id', $id)->where('type', 'debit')->sum('amount');
            $totalCredits = Transaction::where('account_id', $id)->where('type', 'credit')->sum('amount');

            return [
                'account'             => $account,
                'total_debits'        => $totalDebits,
                'total_credits'       => $totalCredits,
                'calculated_balance'  => $totalDebits - $totalCredits,
                'current_balance'     => $account->current_balance,
            ];
        }, ttl: 300); // 5-minute TTL for live balance data

        return $this->respond(['success' => true, 'data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'code' => 'required|string|max:20|unique:finance_accounts,code',
        ]));
        if ($error) return $error;

        $account = Account::create($request->all());
        $this->cacheFlush();
        return $this->respondCreated($account, 'Account created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $account = Account::find($id);
        if (!$account) return $this->respondNotFound();

        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'code' => 'required|string|max:20|unique:finance_accounts,code,' . $id,
        ]));
        if ($error) return $error;

        $account->update($request->all());
        $this->cacheFlush();
        return $this->respondSuccess('Account updated', $account->fresh());
    }
}