<?php

namespace Tests\Feature\Analytics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsTest extends TestCase
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


    public function test_kpis_returns_all_fields(): void
    {
        $response = $this->getJson('/api/v1/analytics/kpis', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => [
                'revenue', 'orders', 'new_customers', 'pending_orders',
                'fleet_costs', 'fleet_distance_km', 'trips_completed',
                'mrr', 'active_subscriptions', 'active_vehicles', 'vehicles_in_maintenance',
            ],
        ]);
    }

    public function test_kpis_accepts_period_filter(): void
    {
        $response = $this->getJson('/api/v1/analytics/kpis?period=year', $this->auth());
        $response->assertOk()->assertJsonPath('data.period', 'year');
    }

    public function test_charts_returns_trends(): void
    {
        $response = $this->getJson('/api/v1/analytics/charts', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => [
                'revenue_trend', 'orders_trend', 'fleet_fuel_cost_trend',
                'fleet_maintenance_cost_trend', 'subscription_mrr',
                'top_products', 'fleet_cost_by_type',
            ],
        ]);
    }

    public function test_activity_returns_feed(): void
    {
        $response = $this->getJson('/api/v1/analytics/activity', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data',
        ]);
    }

    public function test_activity_accepts_limit(): void
    {
        $response = $this->getJson('/api/v1/analytics/activity?limit=5', $this->auth());
        $response->assertOk();
        $this->assertLessThanOrEqual(5, count($response->json('data')));
    }

    public function test_module_summary_returns_all_modules(): void
    {
        $response = $this->getJson('/api/v1/analytics/modules', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['finance', 'inventory', 'sales', 'fleet', 'hr', 'subscription'],
        ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/analytics/kpis');
        $response->assertUnauthorized();
    }
}
