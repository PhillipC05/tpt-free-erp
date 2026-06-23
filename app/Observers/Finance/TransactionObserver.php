<?php

namespace App\Observers\Finance;

use App\Models\Finance\Transaction;
use App\Services\WebhookService;

class TransactionObserver
{
    public function __construct(private readonly WebhookService $webhooks) {}

    public function created(Transaction $transaction): void
    {
        $this->webhooks->dispatch('finance.transaction_created', $transaction->toArray());
    }

    public function updated(Transaction $transaction): void
    {
        $this->webhooks->dispatch('finance.transaction_updated', $transaction->toArray());
    }

    public function deleted(Transaction $transaction): void
    {
        $this->webhooks->dispatch('finance.transaction_deleted', ['id' => $transaction->id]);
    }
}
