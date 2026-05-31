<?php

namespace Tests\Feature\Procurement;

use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\Vendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
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

    public function test_can_list_purchase_orders(): void
    {
        PurchaseOrder::factory()->count(3)->create();

        $response = $this->getJson('/api/procurement/purchase-orders', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_purchase_order(): void
    {
        $vendor = Vendor::factory()->create();

        $response = $this->postJson('/api/procurement/purchase-orders', [
            'po_number' => 'PO-0001',
            'vendor_id' => $vendor->id,
            'order_date' => '2026-05-01',
            'status' => 'draft',
            'subtotal' => 5000.00,
            'tax_amount' => 500.00,
            'total_amount' => 5500.00,
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('procurement_purchase_orders', ['po_number' => 'PO-0001']);
    }

    public function test_create_po_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/procurement/purchase-orders', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_purchase_order(): void
    {
        $po = PurchaseOrder::factory()->create();

        $response = $this->getJson("/api/procurement/purchase-orders/{$po->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $po->id]]);
    }

    public function test_show_nonexistent_po_returns_404(): void
    {
        $response = $this->getJson('/api/procurement/purchase-orders/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_po_status(): void
    {
        $po = PurchaseOrder::factory()->create(['status' => 'draft']);

        $response = $this->putJson("/api/procurement/purchase-orders/{$po->id}/status", [
            'status' => 'sent',
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('procurement_purchase_orders', ['id' => $po->id, 'status' => 'sent']);
    }

    public function test_can_delete_purchase_order(): void
    {
        $po = PurchaseOrder::factory()->create();

        $response = $this->deleteJson("/api/procurement/purchase-orders/{$po->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('procurement_purchase_orders', ['id' => $po->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/procurement/purchase-orders');

        $response->assertUnauthorized();
    }
}
