<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WarehouseTest extends TestCase
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
            'name' => 'admin', 'display_name' => 'Admin', 'description' => 'Admin',
            'is_system' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $adminId = DB::table('roles')->where('name', 'admin')->value('id');
        DB::table('user_roles')->insertOrIgnore([
            'user_id' => $this->user->id, 'role_id' => $adminId,
            'assigned_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_can_list_warehouses(): void
    {
        Warehouse::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/inventory/warehouses', $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_can_create_warehouse(): void
    {
        $response = $this->postJson('/api/v1/inventory/warehouses', [
            'code' => 'WH-01',
            'name' => 'Main Warehouse',
            'city' => 'New York',
            'country' => 'US',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('inventory_warehouses', ['code' => 'WH-01']);
    }

    public function test_warehouse_code_must_be_unique(): void
    {
        Warehouse::factory()->create(['code' => 'WH-01']);

        $response = $this->postJson('/api/v1/inventory/warehouses', [
            'code' => 'WH-01',
            'name' => 'Another Warehouse',
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_delete_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->deleteJson("/api/v1/inventory/warehouses/{$warehouse->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('inventory_warehouses', ['id' => $warehouse->id]);
    }
}
