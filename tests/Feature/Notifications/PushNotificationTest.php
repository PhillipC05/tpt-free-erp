<?php

namespace Tests\Feature\Notifications;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->assignAdminRole();
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function assignAdminRole(): void
    {
        DB::table('roles')->insertOrIgnore([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => 'Admin',
            'is_system' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminId = DB::table('roles')->where('name', 'admin')->value('id');

        DB::table('user_roles')->insertOrIgnore([
            'user_id' => $this->user->id,
            'role_id' => $adminId,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_subscribe_to_push(): void
    {
        $response = $this->postJson('/api/v1/notifications/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-123',
            'keys' => [
                'auth' => base64_encode(random_bytes(16)),
                'p256dh' => base64_encode(random_bytes(65)),
            ],
        ], $this->auth());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Subscribed to push notifications',
            ]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-123',
        ]);
    }

    public function test_unsubscribe_from_push(): void
    {
        PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-456',
        ]);

        $response = $this->deleteJson('/api/v1/notifications/push/unsubscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-456',
        ], $this->auth());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Unsubscribed from push notifications',
            ]);

        $this->assertDatabaseMissing('push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-456',
        ]);
    }

    public function test_subscribe_requires_endpoint(): void
    {
        $response = $this->postJson('/api/v1/notifications/push/subscribe', [], $this->auth());

        $response->assertStatus(422);
    }

    public function test_unsubscribe_requires_endpoint(): void
    {
        $response = $this->deleteJson('/api/v1/notifications/push/unsubscribe', [], $this->auth());

        $response->assertStatus(422);
    }
}
