<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\Driver;
use App\Models\Fleet\Trip;
use App\Models\Fleet\Vehicle;
use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TripTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Vehicle $vehicle;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->assignAdminRole();
        $this->vehicle = Vehicle::factory()->create();
        $employee = Employee::factory()->create();
        $this->driver = Driver::factory()->create(['employee_id' => $employee->id]);
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

    public function test_can_list_trips(): void
    {
        Trip::factory()->count(3)->create([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
        ]);

        $response = $this->getJson('/api/v1/fleet/trips', $this->auth());
        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_trip(): void
    {
        $response = $this->postJson('/api/v1/fleet/trips', [
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'start_location' => 'Auckland Depot',
            'start_odometer' => 50000,
            'start_time' => now()->toIso8601String(),
            'purpose' => 'Client delivery',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.status', 'scheduled');

        $this->assertDatabaseHas('fleet_trips', ['status' => 'scheduled']);
    }

    public function test_create_trip_requires_fields(): void
    {
        $response = $this->postJson('/api/v1/fleet/trips', [], $this->auth());
        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_start_trip(): void
    {
        $trip = Trip::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'status' => 'scheduled',
        ]);

        $response = $this->postJson("/api/v1/fleet/trips/{$trip->id}/start", [], $this->auth());
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('fleet_trips', ['id' => $trip->id, 'status' => 'in_progress']);
    }

    public function test_cannot_start_non_scheduled_trip(): void
    {
        $trip = Trip::factory()->completed()->create([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
        ]);

        $response = $this->postJson("/api/v1/fleet/trips/{$trip->id}/start", [], $this->auth());
        $response->assertStatus(422);
    }

    public function test_can_complete_trip(): void
    {
        $trip = Trip::factory()->inProgress()->create([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'start_odometer' => 50000,
        ]);

        $response = $this->postJson("/api/v1/fleet/trips/{$trip->id}/complete", [
            'end_location' => 'Wellington Office',
            'end_odometer' => 50250,
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('fleet_trips', [
            'id' => $trip->id,
            'status' => 'completed',
            'distance' => 250.0,
        ]);

        $this->assertDatabaseHas('fleet_vehicles', [
            'id' => $this->vehicle->id,
            'current_odometer' => 50250,
        ]);
    }

    public function test_can_cancel_trip(): void
    {
        $trip = Trip::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'status' => 'scheduled',
        ]);

        $response = $this->postJson("/api/v1/fleet/trips/{$trip->id}/cancel", [], $this->auth());
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('fleet_trips', ['id' => $trip->id, 'status' => 'cancelled']);
    }

    public function test_can_filter_by_status(): void
    {
        Trip::factory()->count(2)->create([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'status' => 'scheduled',
        ]);
        Trip::factory()->completed()->create([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
        ]);

        $response = $this->getJson('/api/v1/fleet/trips?status=scheduled', $this->auth());
        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/fleet/trips');
        $response->assertUnauthorized();
    }
}
