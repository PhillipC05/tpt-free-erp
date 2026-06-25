<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VehicleTest extends TestCase
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

    public function test_can_list_vehicles(): void
    {
        Vehicle::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/fleet/vehicles', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_vehicle(): void
    {
        $response = $this->postJson('/api/v1/fleet/vehicles', [
            'vehicle_code' => 'VH-001',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2024,
            'license_plate' => 'ABC-1234',
            'status' => 'active',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.vehicle_code', 'VH-001');

        $this->assertDatabaseHas('fleet_vehicles', ['vehicle_code' => 'VH-001']);
    }

    public function test_create_vehicle_requires_fields(): void
    {
        $response = $this->postJson('/api/v1/fleet/vehicles', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_vehicle_code_must_be_unique(): void
    {
        Vehicle::factory()->create(['vehicle_code' => 'VH-001']);

        $response = $this->postJson('/api/v1/fleet/vehicles', [
            'vehicle_code' => 'VH-001',
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2024,
            'license_plate' => 'XYZ-5678',
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();

        $response = $this->getJson("/api/v1/fleet/vehicles/{$vehicle->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $vehicle->id]]);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $response = $this->getJson('/api/v1/fleet/vehicles/99999', $this->auth());
        $response->assertNotFound();
    }

    public function test_can_update_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['vehicle_code' => 'VH-001']);

        $response = $this->putJson("/api/v1/fleet/vehicles/{$vehicle->id}", [
            'vehicle_code' => 'VH-001',
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2025,
            'license_plate' => $vehicle->license_plate,
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('fleet_vehicles', ['id' => $vehicle->id, 'make' => 'Honda']);
    }

    public function test_can_delete_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();

        $response = $this->deleteJson("/api/v1/fleet/vehicles/{$vehicle->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('fleet_vehicles', ['id' => $vehicle->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/fleet/vehicles');
        $response->assertUnauthorized();
    }

    public function test_can_filter_by_status(): void
    {
        Vehicle::factory()->count(2)->create(['status' => 'active']);
        Vehicle::factory()->count(1)->inactive()->create();

        $response = $this->getJson('/api/v1/fleet/vehicles?status=active', $this->auth());
        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_can_search_vehicles(): void
    {
        Vehicle::factory()->create(['make' => 'Toyota', 'model' => 'Corolla']);
        Vehicle::factory()->create(['make' => 'Honda', 'model' => 'Civic']);

        $response = $this->getJson('/api/v1/fleet/vehicles?search=Toyota', $this->auth());
        $response->assertOk()->assertJsonCount(1, 'data');
    }
}
