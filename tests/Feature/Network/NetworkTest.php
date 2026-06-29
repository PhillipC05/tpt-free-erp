<?php

namespace Tests\Feature\Network;

use App\Models\Network\UserConnection;
use App\Models\Network\UserProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class NetworkTest extends TestCase
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


    public function test_can_create_own_profile(): void
    {
        $response = $this->postJson('/api/v1/network/profile', [
            'headline' => 'Software Engineer',
            'bio' => 'I build great software.',
            'company' => 'Acme Corp',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('network_user_profiles', [
            'user_id' => $this->user->id,
            'company' => 'Acme Corp',
        ]);
    }

    public function test_can_update_own_profile(): void
    {
        UserProfile::create([
            'user_id' => $this->user->id,
            'headline' => 'Original headline',
            'is_discoverable' => false,
        ]);

        $response = $this->putJson('/api/v1/network/profile', [
            'headline' => 'Updated headline',
        ], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('network_user_profiles', [
            'user_id' => $this->user->id,
            'headline' => 'Updated headline',
        ]);
    }

    public function test_can_opt_into_discovery(): void
    {
        $response = $this->postJson('/api/v1/network/profile/opt-in', [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('network_user_profiles', [
            'user_id' => $this->user->id,
            'is_discoverable' => 1,
        ]);
    }

    public function test_can_opt_out_of_discovery(): void
    {
        UserProfile::create([
            'user_id' => $this->user->id,
            'is_discoverable' => true,
        ]);

        $response = $this->postJson('/api/v1/network/profile/opt-out', [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('network_user_profiles', [
            'user_id' => $this->user->id,
            'is_discoverable' => 0,
        ]);
    }

    public function test_discovery_only_returns_discoverable_profiles(): void
    {
        $discoverableUser = User::factory()->create();
        UserProfile::create([
            'user_id' => $discoverableUser->id,
            'headline' => 'Discoverable',
            'is_discoverable' => true,
        ]);

        $hiddenUser = User::factory()->create();
        UserProfile::create([
            'user_id' => $hiddenUser->id,
            'headline' => 'Hidden',
            'is_discoverable' => false,
        ]);

        $response = $this->getJson('/api/v1/network/discovery', $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $userIds = array_column($data, 'user_id');

        $this->assertContains($discoverableUser->id, $userIds);
        $this->assertNotContains($hiddenUser->id, $userIds);
    }

    public function test_can_follow_a_user(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->postJson("/api/v1/network/follow/{$otherUser->id}", [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('network_user_follows', [
            'follower_id' => $this->user->id,
            'following_id' => $otherUser->id,
        ]);
    }

    public function test_can_unfollow_a_user(): void
    {
        $otherUser = User::factory()->create();

        \App\Models\Network\UserFollow::create([
            'follower_id' => $this->user->id,
            'following_id' => $otherUser->id,
        ]);

        $response = $this->deleteJson("/api/v1/network/unfollow/{$otherUser->id}", [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('network_user_follows', [
            'follower_id' => $this->user->id,
            'following_id' => $otherUser->id,
        ]);
    }

    public function test_can_send_connection_request(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->postJson("/api/v1/network/connections/request/{$otherUser->id}", [], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('network_user_connections', [
            'requester_id' => $this->user->id,
            'addressee_id' => $otherUser->id,
            'status' => 'pending',
        ]);
    }

    public function test_addressee_can_accept_connection(): void
    {
        $requester = User::factory()->create();
        $requesterToken = $requester->createToken('test')->plainTextToken;

        $connection = UserConnection::create([
            'requester_id' => $requester->id,
            'addressee_id' => $this->user->id,
            'status' => 'pending',
        ]);

        // The addressee (this->user) accepts
        $response = $this->postJson("/api/v1/network/connections/{$connection->id}/accept", [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('network_user_connections', [
            'id' => $connection->id,
            'status' => 'accepted',
        ]);
    }

    public function test_can_create_a_post(): void
    {
        $response = $this->postJson('/api/v1/network/posts', [
            'body' => 'This is my first network post!',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('network_posts', [
            'user_id' => $this->user->id,
            'body' => 'This is my first network post!',
        ]);
    }

    public function test_can_view_public_posts_in_feed(): void
    {
        \App\Models\Network\NetworkPost::create([
            'user_id' => $this->user->id,
            'body' => 'A public post',
            'type' => 'text',
            'visibility' => 'public',
            'likes_count' => 0,
            'comments_count' => 0,
        ]);

        $response = $this->getJson('/api/v1/network/posts', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/network/posts');

        $response->assertUnauthorized();
    }
}
