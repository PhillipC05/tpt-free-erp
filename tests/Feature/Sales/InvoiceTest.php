<?php

namespace Tests\Feature\Sales;

use App\Models\Sales\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
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

    public function test_can_list_invoices(): void
    {
        Invoice::factory()->count(3)->create();

        $response = $this->getJson('/api/sales/invoices', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_invoice(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $response = $this->postJson('/api/sales/invoices', [
            'invoice_number' => 'INV-0001',
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'invoice_date' => '2026-05-01',
            'due_date' => '2026-06-01',
            'subtotal' => 1000.00,
            'tax_amount' => 100.00,
            'total_amount' => 1100.00,
            'paid_amount' => 0,
            'balance_due' => 1100.00,
            'status' => 'draft',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('sales_invoices', ['invoice_number' => 'INV-0001']);
    }

    public function test_create_invoice_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/sales/invoices', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->getJson("/api/sales/invoices/{$invoice->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $invoice->id]]);
    }

    public function test_show_nonexistent_invoice_returns_404(): void
    {
        $response = $this->getJson('/api/sales/invoices/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->deleteJson("/api/sales/invoices/{$invoice->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('sales_invoices', ['id' => $invoice->id, 'deleted_at' => null]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/sales/invoices');

        $response->assertUnauthorized();
    }
}
