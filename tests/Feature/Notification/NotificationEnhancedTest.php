<?php

namespace Tests\Feature\Notification;

use App\Models\Notification\NotificationMessage;
use App\Models\Notification\NotificationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationEnhancedTest extends TestCase
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

    public function test_can_list_templates(): void
    {
        NotificationTemplate::factory()->count(3)->create();
        $response = $this->getJson('/api/v1/notification-templates', $this->auth());
        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_can_create_template(): void
    {
        $response = $this->postJson('/api/v1/notification-templates', [
            'code' => 'order_confirmed',
            'name' => 'Order Confirmed',
            'subject' => 'Order {{order_number}} confirmed',
            'body' => 'Your order {{order_number}} has been confirmed.',
            'default_channels' => ['in_app', 'email'],
        ], $this->auth());

        $response->assertCreated()->assertJsonPath('data.code', 'order_confirmed');
        $this->assertDatabaseHas('notification_templates', ['code' => 'order_confirmed']);
    }

    public function test_can_preview_template(): void
    {
        $template = NotificationTemplate::factory()->create([
            'subject' => 'Hello {{name}}',
            'body' => 'Your order {{order_number}} is ready.',
        ]);

        $response = $this->postJson("/api/v1/notification-templates/{$template->id}/preview", [
            'name' => 'John',
            'order_number' => 'ORD-001',
        ], $this->auth());

        $response->assertOk()
            ->assertJsonPath('data.subject', 'Hello John')
            ->assertJsonPath('data.body', 'Your order ORD-001 is ready.');
    }

    public function test_can_delete_template(): void
    {
        $template = NotificationTemplate::factory()->create();
        $response = $this->deleteJson("/api/v1/notification-templates/{$template->id}", [], $this->auth());
        $response->assertOk();
        $this->assertSoftDeleted('notification_templates', ['id' => $template->id]);
    }

    public function test_can_list_notifications(): void
    {
        NotificationMessage::factory()->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/v1/notifications-enhanced', $this->auth());
        $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    }

    public function test_can_get_unread_count(): void
    {
        NotificationMessage::factory()->count(3)->unread()->create(['user_id' => $this->user->id]);
        NotificationMessage::factory()->count(2)->read()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/notifications-enhanced/unread-count', $this->auth());
        $response->assertOk()->assertJsonPath('data.count', 3);
    }

    public function test_can_mark_notification_read(): void
    {
        $msg = NotificationMessage::factory()->unread()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/v1/notifications-enhanced/{$msg->id}/read", [], $this->auth());
        $response->assertOk();

        $msg->refresh();
        $this->assertNotNull($msg->read_at);
    }

    public function test_can_mark_all_read(): void
    {
        NotificationMessage::factory()->count(5)->unread()->create(['user_id' => $this->user->id]);

        $response = $this->putJson('/api/v1/notifications-enhanced/read-all', [], $this->auth());
        $response->assertOk();

        $this->assertEquals(0, NotificationMessage::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    public function test_can_set_preferences(): void
    {
        $response = $this->postJson('/api/v1/notifications-enhanced/preferences', [
            'template_code' => 'order_confirmed',
            'channels' => ['email'],
            'email_enabled' => true,
            'in_app_enabled' => false,
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'template_code' => 'order_confirmed',
            'in_app_enabled' => false,
        ]);
    }

    public function test_can_send_notification(): void
    {
        $template = NotificationTemplate::factory()->create(['code' => 'test_event']);

        $response = $this->postJson('/api/v1/notifications-enhanced/send', [
            'user_id' => $this->user->id,
            'template_code' => 'test_event',
            'data' => ['name' => 'Test'],
        ], $this->auth());

        $response->assertCreated();
        $this->assertDatabaseHas('notification_queue', [
            'user_id' => $this->user->id,
            'channel' => 'in_app',
        ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/notifications-enhanced');
        $response->assertUnauthorized();
    }
}
