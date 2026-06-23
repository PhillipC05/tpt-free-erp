<?php

namespace App\Services;

use App\Jobs\WebhookDeliveryJob;
use App\Models\Webhook;
use App\Models\WebhookDelivery;

class WebhookService
{
    public function dispatch(string $event, array $payload): void
    {
        $webhooks = Webhook::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->orWhere(function ($q) use ($event) {
                // Support wildcard events like "finance.*"
                $module = explode('.', $event)[0];
                $q->where('is_active', true)->whereJsonContains('events', "{$module}.*");
            })
            ->get();

        foreach ($webhooks as $webhook) {
            $delivery = WebhookDelivery::create([
                'webhook_id'    => $webhook->id,
                'event'         => $event,
                'payload'       => $payload,
                'status'        => 'pending',
                'attempts'      => 0,
                'next_retry_at' => now(),
            ]);

            WebhookDeliveryJob::dispatch($delivery->id);
        }
    }

    public function testFire(Webhook $webhook): WebhookDelivery
    {
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event'      => 'webhook.test',
            'payload'    => [
                'event'     => 'webhook.test',
                'timestamp' => now()->toIso8601String(),
                'message'   => 'This is a test delivery from TPT Free ERP.',
            ],
            'status'        => 'pending',
            'attempts'      => 0,
            'next_retry_at' => now(),
        ]);

        WebhookDeliveryJob::dispatchSync($delivery->id);

        return $delivery->fresh();
    }
}
