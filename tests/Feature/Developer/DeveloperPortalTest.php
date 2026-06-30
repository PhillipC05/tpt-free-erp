<?php

namespace Tests\Feature\Developer;

use App\Models\ApiKey;
use App\Models\ApiKeyUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeveloperPortalTest extends TestCase
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

    public function test_create_api_key(): void
    {
        $response = $this->postJson('/api/v1/developer/keys', [
            'name' => 'Test Key',
            'rate_limit_per_minute' => 120,
        ], $this->auth());

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'API key created successfully',
            ])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'key', 'key_prefix', 'rate_limit_per_minute'],
            ]);

        $this->assertDatabaseHas('api_keys', [
            'user_id' => $this->user->id,
            'name' => 'Test Key',
            'is_active' => true,
        ]);
    }

    public function test_create_api_key_requires_name(): void
    {
        $response = $this->postJson('/api/v1/developer/keys', [], $this->auth());

        $response->assertStatus(422);
    }

    public function test_list_api_keys(): void
    {
        ApiKey::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/developer/keys', $this->auth());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'meta' => ['total' => 3],
            ]);
    }

    public function test_revoke_api_key(): void
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id, 'is_active' => true]);

        $response = $this->deleteJson("/api/v1/developer/keys/{$apiKey->id}", [], $this->auth());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'API key revoked successfully',
            ]);

        $apiKey->refresh();
        $this->assertFalse($apiKey->is_active);
    }

    public function test_revoke_nonexistent_key_returns_404(): void
    {
        $response = $this->deleteJson('/api/v1/developer/keys/99999', [], $this->auth());

        $response->assertNotFound();
    }

    public function test_usage_stats(): void
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);

        ApiKeyUsage::factory()->count(5)->create([
            'api_key_id' => $apiKey->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->getJson("/api/v1/developer/keys/{$apiKey->id}/usage", $this->auth());

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['daily_usage', 'total_calls', 'error_calls', 'error_rate', 'period_days'],
            ]);
    }

    public function test_usage_by_endpoint(): void
    {
        $apiKey = ApiKey::factory()->create(['user_id' => $this->user->id]);

        ApiKeyUsage::factory()->count(3)->create([
            'api_key_id' => $apiKey->id,
            'endpoint' => 'v1/finance/accounts',
            'method' => 'GET',
        ]);

        $response = $this->getJson("/api/v1/developer/keys/{$apiKey->id}/usage/endpoints", $this->auth());

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['endpoints', 'period_days'],
            ]);
    }

    public function test_cannot_access_other_users_keys(): void
    {
        $otherUser = User::factory()->create();
        $apiKey = ApiKey::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/v1/developer/keys/{$apiKey->id}", [], $this->auth());

        $response->assertNotFound();
    }
}
