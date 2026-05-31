<?php

namespace Tests\Feature\Manufacturing;

use App\Models\Inventory\Product;
use App\Models\Manufacturing\WorkOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_list_work_orders(): void
    {
        WorkOrder::factory()->count(3)->create();

        $response = $this->getJson('/api/manufacturing/work-orders', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_work_order(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/manufacturing/work-orders', [
            'wo_number' => 'WO-0001',
            'product_id' => $product->id,
            'planned_quantity' => 100,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'planned',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('manufacturing_work_orders', ['wo_number' => 'WO-0001']);
    }

    public function test_create_work_order_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/manufacturing/work-orders', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_work_order(): void
    {
        $wo = WorkOrder::factory()->create();

        $response = $this->getJson("/api/manufacturing/work-orders/{$wo->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $wo->id]]);
    }

    public function test_show_nonexistent_work_order_returns_404(): void
    {
        $response = $this->getJson('/api/manufacturing/work-orders/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_start_work_order(): void
    {
        $wo = WorkOrder::factory()->create(['status' => 'planned']);

        $response = $this->postJson("/api/manufacturing/work-orders/{$wo->id}/start", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('manufacturing_work_orders', ['id' => $wo->id, 'status' => 'in_progress']);
    }

    public function test_can_delete_work_order(): void
    {
        $wo = WorkOrder::factory()->create();

        $response = $this->deleteJson("/api/manufacturing/work-orders/{$wo->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/manufacturing/work-orders');

        $response->assertUnauthorized();
    }
}
