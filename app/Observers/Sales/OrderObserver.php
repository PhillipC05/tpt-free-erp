<?php

namespace App\Observers\Sales;

use App\Models\Sales\Order;
use App\Services\WebhookService;

class OrderObserver
{
    public function __construct(private readonly WebhookService $webhooks) {}

    public function created(Order $order): void
    {
        $this->webhooks->dispatch('sales.order_created', $order->toArray());
    }

    public function updated(Order $order): void
    {
        $this->webhooks->dispatch('sales.order_updated', $order->toArray());
    }

    public function deleted(Order $order): void
    {
        $this->webhooks->dispatch('sales.order_deleted', ['id' => $order->id]);
    }
}
