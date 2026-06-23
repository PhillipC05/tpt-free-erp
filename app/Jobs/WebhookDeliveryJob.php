<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(private readonly int $deliveryId)
    {
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::with('webhook')->find($this->deliveryId);

        if (!$delivery || $delivery->status === 'delivered') {
            return;
        }

        $webhook = $delivery->webhook;

        if (!$webhook || !$webhook->is_active) {
            $delivery->update(['status' => 'failed', 'last_response' => 'Webhook inactive or deleted.']);
            return;
        }

        $delivery->increment('attempts');

        $body = json_encode([
            'event'     => $delivery->event,
            'timestamp' => now()->toIso8601String(),
            'data'      => $delivery->payload,
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $body, $webhook->secret);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type'     => 'application/json',
                    'X-TPT-Signature'  => $signature,
                    'X-TPT-Event'      => $delivery->event,
                    'X-TPT-Delivery'   => (string) $delivery->id,
                ])
                ->send('POST', $webhook->url, ['body' => $body]);

            if ($response->successful()) {
                $delivery->update([
                    'status'        => 'delivered',
                    'last_response' => substr($response->body(), 0, 1000),
                    'next_retry_at' => null,
                ]);

                $webhook->update([
                    'last_triggered_at' => now(),
                    'failure_count'     => 0,
                ]);
            } else {
                $this->handleFailure($delivery, $webhook, "HTTP {$response->status()}: " . substr($response->body(), 0, 500));
            }
        } catch (\Throwable $e) {
            $this->handleFailure($delivery, $webhook, $e->getMessage());
        }
    }

    private function handleFailure(WebhookDelivery $delivery, Webhook $webhook, string $reason): void
    {
        $attempt = $delivery->attempts;

        // Exponential backoff: 5min, 30min, 2hr
        $delays = [300, 1800, 7200];
        $nextRetry = now()->addSeconds($delays[min($attempt - 1, count($delays) - 1)]);

        $isFinalAttempt = $attempt >= $this->tries;

        $delivery->update([
            'status'        => $isFinalAttempt ? 'failed' : 'pending',
            'last_response' => $reason,
            'next_retry_at' => $isFinalAttempt ? null : $nextRetry,
        ]);

        $webhook->increment('failure_count');

        // Auto-disable after 10 consecutive failures
        if ($webhook->failure_count >= 10) {
            $webhook->update(['is_active' => false]);
            Log::warning("Webhook #{$webhook->id} auto-disabled after 10 consecutive failures.");
        }

        if (!$isFinalAttempt) {
            self::dispatch($delivery->id)->delay($nextRetry);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("WebhookDeliveryJob failed for delivery #{$this->deliveryId}: " . $e->getMessage());

        WebhookDelivery::where('id', $this->deliveryId)->update([
            'status'        => 'failed',
            'last_response' => $e->getMessage(),
        ]);
    }
}
