<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription\Plan;
use App\Models\Subscription\Subscription;
use App\Models\Sales\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;

        DB::table('roles')->insertOrIgnore([
            'name' => 'admin', 'display_name' => 'Admin', 'description' => 'System administrator',
        ]);
        $adminId = DB::table('roles')->where('name', 'admin')->value('id');
        DB::table('user_roles')->insert([
            'user_id' => $this->user->id, 'role_id' => $adminId,
        ]);
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_create_subscription(): void
    {
        $plan = Plan::factory()->create(['trial_days' => null]);
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/subscription/subscriptions', [
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
        ], $this->auth());

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.plan_id', $plan->id);
    }

    public function test_create_subscription_with_trial(): void
    {
        $plan = Plan::factory()->create(['trial_days' => 14]);
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/subscription/subscriptions', [
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
        ], $this->auth());

        $response->assertCreated()
            ->assertJsonPath('data.status', 'trialing')
            ->assertJsonStructure(['data' => ['trial_ends_at']]);
    }

    public function test_can_change_plan(): void
    {
        $oldPlan = Plan::factory()->create(['price' => 29]);
        $newPlan = Plan::factory()->create(['price' => 79]);
        $subscription = Subscription::factory()->create(['plan_id' => $oldPlan->id, 'status' => 'active']);

        $response = $this->postJson("/api/v1/subscription/subscriptions/{$subscription->id}/change-plan", [
            'plan_id' => $newPlan->id,
            'reason' => 'Need more features',
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'plan_id' => $newPlan->id]);
        $this->assertDatabaseHas('subscription_plan_changes', [
            'subscription_id' => $subscription->id,
            'change_type' => 'upgrade',
        ]);
    }

    public function test_can_cancel_subscription(): void
    {
        $subscription = Subscription::factory()->create(['status' => 'active']);

        $response = $this->postJson("/api/v1/subscription/subscriptions/{$subscription->id}/cancel", [
            'reason' => 'Not needed anymore',
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'cancelled']);
    }

    public function test_can_reactivate_subscription(): void
    {
        $subscription = Subscription::factory()->cancelled()->create();

        $response = $this->postJson("/api/v1/subscription/subscriptions/{$subscription->id}/reactivate", [], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'active']);
    }

    public function test_can_list_subscriptions(): void
    {
        Subscription::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/subscription/subscriptions', $this->auth());
        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_get_dashboard(): void
    {
        Subscription::factory()->count(3)->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/subscription/subscriptions/dashboard', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['mrr', 'active_count', 'trialing_count', 'plan_distribution'],
        ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/subscription/subscriptions');
        $response->assertUnauthorized();
    }
}
