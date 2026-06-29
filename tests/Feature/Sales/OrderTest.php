<?php

namespace Tests\Feature\Sales;

use App\Models\Sales\Customer;
use App\Models\Sales\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
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

    public function test_can_list_orders(): void
    {
        Order::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/sales/orders', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_order(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/sales/orders', [
            'order_number' => 'ORD-0001',
            'customer_id' => $customer->id,
            'order_date' => '2026-05-01',
            'status' => 'draft',
            'subtotal' => 1000.00,
            'tax_amount' => 100.00,
            'total_amount' => 1100.00,
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('sales_orders', ['order_number' => 'ORD-0001']);
    }

    public function test_create_order_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/v1/sales/orders', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_order(): void
    {
        $order = Order::factory()->create();

        $response = $this->getJson("/api/v1/sales/orders/{$order->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $order->id]]);
    }

    public function test_show_nonexistent_order_returns_404(): void
    {
        $response = $this->getJson('/api/v1/sales/orders/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_order_status(): void
    {
        $order = Order::factory()->create(['status' => 'draft']);

        $response = $this->putJson("/api/v1/sales/orders/{$order->id}/status", [
            'status' => 'confirmed',
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('sales_orders', ['id' => $order->id, 'status' => 'confirmed']);
    }

    public function test_can_delete_order(): void
    {
        $order = Order::factory()->create();

        $response = $this->deleteJson("/api/v1/sales/orders/{$order->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('sales_orders', ['id' => $order->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/sales/orders');

        $response->assertUnauthorized();
    }
}
