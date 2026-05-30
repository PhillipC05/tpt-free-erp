<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = User::factory()->create()->createToken('test')->plainTextToken;
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_list_warehouses(): void
    {
        Warehouse::factory()->count(3)->create();

        $response = $this->getJson('/api/inventory/warehouses', $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_can_create_warehouse(): void
    {
        $response = $this->postJson('/api/inventory/warehouses', [
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

        $response = $this->postJson('/api/inventory/warehouses', [
            'code' => 'WH-01',
            'name' => 'Another Warehouse',
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_delete_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->deleteJson("/api/inventory/warehouses/{$warehouse->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('inventory_warehouses', ['id' => $warehouse->id]);
    }
}
