<?php

namespace App\Observers\Inventory;

use App\Models\Inventory\StockMovement;
use App\Services\WebhookService;

class StockMovementObserver
{
    public function __construct(private readonly WebhookService $webhooks) {}

    public function created(StockMovement $movement): void
    {
        $this->webhooks->dispatch('inventory.stock_movement_created', $movement->toArray());
    }
}
