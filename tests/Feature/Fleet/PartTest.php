<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\Part;
use App\Models\Fleet\PartUsage;
use App\Models\Fleet\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PartTest extends TestCase
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


    public function test_can_list_parts(): void
    {
        Part::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/fleet/parts', $this->auth());
        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_part(): void
    {
        $response = $this->postJson('/api/v1/fleet/parts', [
            'part_number' => 'PT-001',
            'name' => 'Brake Pad Set',
            'unit_cost' => 45.99,
            'quantity_on_hand' => 20,
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.part_number', 'PT-001');

        $this->assertDatabaseHas('fleet_parts', ['part_number' => 'PT-001']);
    }

    public function test_create_part_requires_fields(): void
    {
        $response = $this->postJson('/api/v1/fleet/parts', [], $this->auth());
        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_part_number_must_be_unique(): void
    {
        Part::factory()->create(['part_number' => 'PT-001']);

        $response = $this->postJson('/api/v1/fleet/parts', [
            'part_number' => 'PT-001',
            'name' => 'Duplicate Part',
            'unit_cost' => 10.00,
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_part(): void
    {
        $part = Part::factory()->create();
        $response = $this->getJson("/api/v1/fleet/parts/{$part->id}", $this->auth());
        $response->assertOk()->assertJson(['success' => true, 'data' => ['id' => $part->id]]);
    }

    public function test_can_update_part(): void
    {
        $part = Part::factory()->create(['part_number' => 'PT-001']);

        $response = $this->putJson("/api/v1/fleet/parts/{$part->id}", [
            'part_number' => 'PT-001',
            'name' => 'Updated Brake Pad',
            'unit_cost' => 55.99,
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('fleet_parts', ['id' => $part->id, 'name' => 'Updated Brake Pad']);
    }

    public function test_can_adjust_stock(): void
    {
        $part = Part::factory()->create(['quantity_on_hand' => 20]);

        $response = $this->postJson("/api/v1/fleet/parts/{$part->id}/adjust-stock", [
            'adjustment' => -5,
            'reason' => 'Used in maintenance',
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('fleet_parts', ['id' => $part->id, 'quantity_on_hand' => 15]);
    }

    public function test_stock_adjustment_cannot_go_negative(): void
    {
        $part = Part::factory()->create(['quantity_on_hand' => 5]);

        $response = $this->postJson("/api/v1/fleet/parts/{$part->id}/adjust-stock", [
            'adjustment' => -10,
            'reason' => 'Too many used',
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_view_low_stock_parts(): void
    {
        Part::factory()->lowStock()->create(['part_number' => 'LOW-001', 'quantity_on_hand' => 0, 'reorder_level' => 10]);
        Part::factory()->create(['part_number' => 'OK-001', 'quantity_on_hand' => 50, 'reorder_level' => 10]);

        $response = $this->getJson('/api/v1/fleet/parts/low-stock', $this->auth());
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_search_parts(): void
    {
        Part::factory()->create(['name' => 'Brake Pad Front']);
        Part::factory()->create(['name' => 'Oil Filter']);

        $response = $this->getJson('/api/v1/fleet/parts?search=Brake', $this->auth());
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_delete_part(): void
    {
        $part = Part::factory()->create();
        $response = $this->deleteJson("/api/v1/fleet/parts/{$part->id}", [], $this->auth());
        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('fleet_parts', ['id' => $part->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/fleet/parts');
        $response->assertUnauthorized();
    }

    public function test_can_record_part_usage(): void
    {
        $part = Part::factory()->create(['quantity_on_hand' => 20, 'unit_cost' => 25.00]);
        $vehicle = Vehicle::factory()->create();

        $response = $this->postJson('/api/v1/fleet/parts/usage', [
            'part_id' => $part->id,
            'vehicle_id' => $vehicle->id,
            'quantity' => 3,
            'used_date' => now()->toDateString(),
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);

        $this->assertDatabaseHas('fleet_parts', ['id' => $part->id, 'quantity_on_hand' => 17]);
        $this->assertDatabaseHas('fleet_part_usages', ['part_id' => $part->id, 'quantity' => 3]);
    }

    public function test_usage_cannot_exceed_stock(): void
    {
        $part = Part::factory()->create(['quantity_on_hand' => 2]);
        $vehicle = Vehicle::factory()->create();

        $response = $this->postJson('/api/v1/fleet/parts/usage', [
            'part_id' => $part->id,
            'vehicle_id' => $vehicle->id,
            'quantity' => 5,
            'used_date' => now()->toDateString(),
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_list_part_usages(): void
    {
        $part = Part::factory()->create();
        $vehicle = Vehicle::factory()->create();
        PartUsage::factory()->count(3)->create([
            'part_id' => $part->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response = $this->getJson('/api/v1/fleet/parts/usage', $this->auth());
        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }
}
