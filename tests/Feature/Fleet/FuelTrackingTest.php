<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FuelLog;
use App\Models\Fleet\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FuelTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->assignAdminRole();
        $this->vehicle = Vehicle::factory()->create(['current_odometer' => 50000]);
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


    public function test_dashboard_returns_summary(): void
    {
        FuelLog::factory()->count(5)->create([
            'vehicle_id' => $this->vehicle->id,
            'date' => now()->subDays(5),
            'odometer' => 50010,
        ]);

        $response = $this->getJson('/api/v1/fleet/fuel-tracking/dashboard', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['summary', 'monthly_trend', 'cost_by_fuel_type', 'top_stations', 'recent_logs'],
        ]);
    }

    public function test_dashboard_accepts_date_range(): void
    {
        $response = $this->getJson('/api/v1/fleet/fuel-tracking/dashboard?'.http_build_query([
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
        ]), $this->auth());

        $response->assertOk();
        $this->assertEquals(now()->subDays(30)->toDateString(), $response->json('data.summary.period.start'));
    }

    public function test_efficiency_requires_vehicle_id(): void
    {
        $response = $this->getJson('/api/v1/fleet/fuel-tracking/efficiency', $this->auth());
        $response->assertStatus(422);
    }

    public function test_efficiency_returns_records_for_vehicle(): void
    {
        FuelLog::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'odometer' => 50100,
            'quantity' => 40,
            'total_cost' => 80,
            'date' => now()->subDays(2),
        ]);
        FuelLog::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'odometer' => 50600,
            'quantity' => 50,
            'total_cost' => 100,
            'date' => now()->subDays(1),
        ]);

        $response = $this->getJson('/api/v1/fleet/fuel-tracking/efficiency?vehicle_id='.$this->vehicle->id, $this->auth());

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['vehicle', 'efficiency_records', 'average_efficiency', 'total_fuel_cost', 'total_distance_km'],
            ]);

        $this->assertGreaterThan(0, $response->json('data.total_distance_km'));
        $this->assertNotNull($response->json('data.average_efficiency.km_per_liter'));
    }

    public function test_consumption_groups_by_vehicle(): void
    {
        $vehicle2 = Vehicle::factory()->create();

        FuelLog::factory()->create(['vehicle_id' => $this->vehicle->id, 'total_cost' => 50, 'quantity' => 25]);
        FuelLog::factory()->create(['vehicle_id' => $this->vehicle->id, 'total_cost' => 60, 'quantity' => 30]);
        FuelLog::factory()->create(['vehicle_id' => $vehicle2->id, 'total_cost' => 40, 'quantity' => 20]);

        $response = $this->getJson('/api/v1/fleet/fuel-tracking/consumption', $this->auth());
        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_price_history_returns_daily_prices(): void
    {
        FuelLog::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'fuel_type' => 'gasoline',
            'unit_cost' => 2.50,
            'date' => now()->subDays(3),
        ]);
        FuelLog::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'fuel_type' => 'gasoline',
            'unit_cost' => 2.60,
            'date' => now()->subDays(1),
        ]);

        $response = $this->getJson('/api/v1/fleet/fuel-tracking/price-history?fuel_type=gasoline', $this->auth());
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['fuel_type', 'overall', 'daily_prices'],
            ])
            ->assertJson(['data' => ['fuel_type' => 'gasoline']]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/fleet/fuel-tracking/dashboard');
        $response->assertUnauthorized();
    }
}
