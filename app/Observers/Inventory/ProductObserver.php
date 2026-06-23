<?php

namespace App\Observers\Inventory;

use App\Models\Inventory\Product;
use App\Services\WebhookService;

class ProductObserver
{
    public function __construct(private readonly WebhookService $webhooks) {}

    public function created(Product $product): void
    {
        $this->webhooks->dispatch('inventory.product_created', $product->toArray());
    }

    public function updated(Product $product): void
    {
        $this->webhooks->dispatch('inventory.product_updated', $product->toArray());
    }

    public function deleted(Product $product): void
    {
        $this->webhooks->dispatch('inventory.product_deleted', ['id' => $product->id]);
    }
}
