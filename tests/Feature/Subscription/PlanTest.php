<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlanTest extends TestCase
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

    public function test_can_list_plans(): void
    {
        Plan::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/subscription/plans', $this->auth());
        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_can_create_plan(): void
    {
        $response = $this->postJson('/api/v1/subscription/plans', [
            'code' => 'PRO-001',
            'name' => 'Professional',
            'price' => 49.99,
            'billing_interval' => 'monthly',
        ], $this->auth());

        $response->assertCreated()->assertJsonPath('data.code', 'PRO-001');
        $this->assertDatabaseHas('subscription_plans', ['code' => 'PRO-001']);
    }

    public function test_create_plan_requires_fields(): void
    {
        $response = $this->postJson('/api/v1/subscription/plans', [], $this->auth());
        $response->assertStatus(422);
    }

    public function test_plan_code_must_be_unique(): void
    {
        Plan::factory()->create(['code' => 'PRO-001']);
        $response = $this->postJson('/api/v1/subscription/plans', [
            'code' => 'PRO-001', 'name' => 'Dup', 'price' => 10, 'billing_interval' => 'monthly',
        ], $this->auth());
        $response->assertStatus(422);
    }

    public function test_can_update_plan(): void
    {
        $plan = Plan::factory()->create(['code' => 'PRO-001']);
        $response = $this->putJson("/api/v1/subscription/plans/{$plan->id}", [
            'code' => 'PRO-001', 'name' => 'Pro Plus', 'price' => 79.99, 'billing_interval' => 'monthly',
        ], $this->auth());
        $response->assertOk();
        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id, 'name' => 'Pro Plus']);
    }

    public function test_can_delete_plan(): void
    {
        $plan = Plan::factory()->create();
        $response = $this->deleteJson("/api/v1/subscription/plans/{$plan->id}", [], $this->auth());
        $response->assertOk();
        $this->assertSoftDeleted('subscription_plans', ['id' => $plan->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/subscription/plans');
        $response->assertUnauthorized();
    }
}
