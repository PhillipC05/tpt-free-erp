<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\MaintenanceRecord;
use App\Models\Fleet\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaintenanceTrackingTest extends TestCase
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

        DB::table('roles')->insertOrIgnore([
            'name' => 'admin', 'display_name' => 'Admin', 'description' => 'System administrator',
        ]);
        $adminId = DB::table('roles')->where('name', 'admin')->value('id');
        DB::table('user_roles')->insert([
            'user_id' => $this->user->id, 'role_id' => $adminId,
        ]);

        $this->vehicle = Vehicle::factory()->create();
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_dashboard_returns_summary(): void
    {
        MaintenanceRecord::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status' => 'scheduled',
            'scheduled_date' => now()->subDays(5),
        ]);
        MaintenanceRecord::factory()->completed()->create([
            'vehicle_id' => $this->vehicle->id,
            'cost' => 250,
        ]);

        $response = $this->getJson('/api/v1/fleet/maintenance-tracking/dashboard', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['summary', 'overdue_records', 'upcoming_records', 'recent_completed', 'cost_by_type', 'cost_by_vehicle', 'monthly_cost'],
        ]);

        $this->assertGreaterThanOrEqual(1, $response->json('data.summary.overdue_count'));
    }

    public function test_vehicle_history_requires_vehicle_id(): void
    {
        $response = $this->getJson('/api/v1/fleet/maintenance-tracking/history', $this->auth());
        $response->assertStatus(422);
    }

    public function test_vehicle_history_returns_records(): void
    {
        MaintenanceRecord::factory()->completed()->create([
            'vehicle_id' => $this->vehicle->id,
            'completed_date' => now()->subDays(30),
            'cost' => 100,
        ]);
        MaintenanceRecord::factory()->completed()->create([
            'vehicle_id' => $this->vehicle->id,
            'completed_date' => now()->subDays(10),
            'cost' => 200,
        ]);

        $response = $this->getJson('/api/v1/fleet/maintenance-tracking/history?vehicle_id='.$this->vehicle->id, $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['vehicle', 'records', 'total_cost', 'last_service_date', 'avg_interval_days', 'type_breakdown'],
        ]);

        $this->assertEquals(300, $response->json('data.total_cost'));
        $this->assertNotNull($response->json('data.avg_interval_days'));
    }

    public function test_overdue_returns_scheduled_past_date(): void
    {
        MaintenanceRecord::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status' => 'scheduled',
            'scheduled_date' => now()->subDays(3),
        ]);

        $response = $this->getJson('/api/v1/fleet/maintenance-tracking/overdue', $this->auth());
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_cost_report_returns_breakdowns(): void
    {
        MaintenanceRecord::factory()->completed()->create([
            'vehicle_id' => $this->vehicle->id,
            'type' => 'preventive',
            'cost' => 150,
            'service_provider' => 'ABC Mechanics',
            'completed_date' => now()->subDays(10),
        ]);

        $response = $this->getJson('/api/v1/fleet/maintenance-tracking/cost-report', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['summary', 'by_type', 'by_vehicle', 'by_provider', 'by_month'],
        ]);

        $this->assertEquals(150, $response->json('data.summary.total_cost'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.summary.total_records'));
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/fleet/maintenance-tracking/dashboard');
        $response->assertUnauthorized();
    }
}
