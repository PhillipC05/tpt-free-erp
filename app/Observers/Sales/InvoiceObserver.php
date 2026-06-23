<?php

namespace App\Observers\Sales;

use App\Models\Sales\Invoice;
use App\Services\WebhookService;

class InvoiceObserver
{
    public function __construct(private readonly WebhookService $webhooks) {}

    public function created(Invoice $invoice): void
    {
        $this->webhooks->dispatch('sales.invoice_created', $invoice->toArray());
    }

    public function updated(Invoice $invoice): void
    {
        $this->webhooks->dispatch('sales.invoice_updated', $invoice->toArray());
    }

    public function deleted(Invoice $invoice): void
    {
        $this->webhooks->dispatch('sales.invoice_deleted', ['id' => $invoice->id]);
    }
}
