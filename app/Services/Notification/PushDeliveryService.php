<?php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class PushDeliveryService
{
    public function sendPush(User $user, string $title, string $body, ?array $data = null): array
    {
        $subscriptions = $user->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return ['sent' => 0, 'failed' => 0];
        }

        $vapidPublicKey = config('webpush.vapid.public_key');
        $vapidPrivateKey = config('webpush.vapid.private_key');

        if (! $vapidPublicKey || ! $vapidPrivateKey) {
            return ['sent' => 0, 'failed' => $subscriptions->count(), 'error' => 'VAPID keys not configured'];
        }

        $sent = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $payload = json_encode([
                    'title' => $title,
                    'body' => $body,
                    'data' => $data ?? [],
                    'icon' => '/favicon.ico',
                ]);

                $endpoint = $subscription->endpoint;
                $keys = [
                    'auth' => $subscription->keys_auth,
                    'p256dh' => $subscription->keys_p256dh,
                ];

                $this->sendWebPush($endpoint, $keys, $payload, $vapidPublicKey, $vapidPrivateKey);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                $subscription->delete();
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    private function sendWebPush(
        string $endpoint,
        array $keys,
        string $payload,
        string $vapidPublicKey,
        string $vapidPrivateKey,
    ): void {
        $encrypted = $this->encryptPayload($payload, $keys['p256dh']);
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Encoding' => 'aes128gcm',
            'TTL' => '86400',
        ];

        Http::withHeaders($headers)
            ->withBody($encrypted, 'application/octet-stream')
            ->post($endpoint);
    }

    private function encryptPayload(string $payload, string $userPublicKey): string
    {
        return sodium_crypto_aead_aes256gcm_encrypt(
            $payload,
            '',
            $userPublicKey,
            random_bytes(SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES)
        );
    }
}
