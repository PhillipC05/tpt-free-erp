<?php

namespace Tests\Feature;

use App\Jobs\WebhookDeliveryJob;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookDeliveryJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeWebhook(array $overrides = []): Webhook
    {
        $user = User::factory()->create();

        return Webhook::create(array_merge([
            'user_id'   => $user->id,
            'url'       => 'https://example.com/hook',
            'secret'    => 'test-secret',
            'events'    => ['finance.*'],
            'is_active' => true,
        ], $overrides));
    }

    private function makeDelivery(Webhook $webhook, array $overrides = []): WebhookDelivery
    {
        return WebhookDelivery::create(array_merge([
            'webhook_id'    => $webhook->id,
            'event'         => 'finance.transaction_created',
            'payload'       => ['id' => 1, 'amount' => 500],
            'status'        => 'pending',
            'attempts'      => 0,
            'next_retry_at' => now(),
        ], $overrides));
    }

    public function test_successful_delivery_marks_status_as_delivered(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $webhook  = $this->makeWebhook();
        $delivery = $this->makeDelivery($webhook);

        WebhookDeliveryJob::dispatchSync($delivery->id);

        $this->assertDatabaseHas('webhook_deliveries', [
            'id'     => $delivery->id,
            'status' => 'delivered',
        ]);

        $this->assertDatabaseHas('webhooks', [
            'id'            => $webhook->id,
            'failure_count' => 0,
        ]);
    }

    public function test_failed_delivery_increments_failure_count_and_keeps_pending_for_retry(): void
    {
        Http::fake(['*' => Http::response('error', 500)]);

        $webhook  = $this->makeWebhook();
        $delivery = $this->makeDelivery($webhook);

        WebhookDeliveryJob::dispatchSync($delivery->id);

        $this->assertDatabaseHas('webhook_deliveries', [
            'id'     => $delivery->id,
            'status' => 'pending', // not yet at max attempts
        ]);

        $this->assertDatabaseHas('webhooks', [
            'id'            => $webhook->id,
            'failure_count' => 1,
        ]);
    }

    public function test_delivery_fails_permanently_after_max_attempts(): void
    {
        Http::fake(['*' => Http::response('error', 500)]);

        $webhook  = $this->makeWebhook();
        // Start with attempts already at max - 1 so the next run pushes it over
        $delivery = $this->makeDelivery($webhook, ['attempts' => 2]);

        WebhookDeliveryJob::dispatchSync($delivery->id);

        $this->assertDatabaseHas('webhook_deliveries', [
            'id'     => $delivery->id,
            'status' => 'failed',
        ]);
    }

    public function test_webhook_auto_disabled_after_10_consecutive_failures(): void
    {
        Http::fake(['*' => Http::response('error', 500)]);

        $webhook  = $this->makeWebhook(['failure_count' => 9]);
        $delivery = $this->makeDelivery($webhook, ['attempts' => 2]); // final attempt

        WebhookDeliveryJob::dispatchSync($delivery->id);

        $this->assertDatabaseHas('webhooks', [
            'id'        => $webhook->id,
            'is_active' => false,
        ]);
    }

    public function test_inactive_webhook_causes_delivery_to_fail_immediately(): void
    {
        Http::fake(); // should never be called

        $webhook  = $this->makeWebhook(['is_active' => false]);
        $delivery = $this->makeDelivery($webhook);

        WebhookDeliveryJob::dispatchSync($delivery->id);

        $this->assertDatabaseHas('webhook_deliveries', [
            'id'     => $delivery->id,
            'status' => 'failed',
        ]);

        Http::assertNothingSent();
    }

    public function test_already_delivered_delivery_is_skipped(): void
    {
        Http::fake();

        $webhook  = $this->makeWebhook();
        $delivery = $this->makeDelivery($webhook, ['status' => 'delivered']);

        WebhookDeliveryJob::dispatchSync($delivery->id);

        // Status should remain delivered, no extra HTTP call
        $this->assertDatabaseHas('webhook_deliveries', [
            'id'     => $delivery->id,
            'status' => 'delivered',
        ]);

        Http::assertNothingSent();
    }

    public function test_job_failed_method_marks_delivery_as_failed(): void
    {
        $webhook  = $this->makeWebhook();
        $delivery = $this->makeDelivery($webhook);

        $job = new WebhookDeliveryJob($delivery->id);
        $job->failed(new \RuntimeException('Queue infrastructure failure'));

        $this->assertDatabaseHas('webhook_deliveries', [
            'id'     => $delivery->id,
            'status' => 'failed',
        ]);
    }

    public function test_hmac_signature_sent_in_request_header(): void
    {
        $capturedHeaders = [];

        Http::fake(function ($request) use (&$capturedHeaders) {
            $capturedHeaders = $request->headers();
            return Http::response('ok', 200);
        });

        $webhook  = $this->makeWebhook(['secret' => 'my-secret']);
        $delivery = $this->makeDelivery($webhook);

        WebhookDeliveryJob::dispatchSync($delivery->id);

        $this->assertArrayHasKey('x-tpt-signature', array_change_key_case($capturedHeaders, CASE_LOWER));
        $this->assertStringStartsWith('sha256=', array_change_key_case($capturedHeaders, CASE_LOWER)['x-tpt-signature'][0]);
    }

    public function test_delivery_for_nonexistent_id_does_not_throw(): void
    {
        $this->expectNotToPerformAssertions();
        WebhookDeliveryJob::dispatchSync(999999);
    }
}
