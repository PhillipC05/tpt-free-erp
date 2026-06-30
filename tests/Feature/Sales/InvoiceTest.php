<?php

namespace Tests\Feature\Sales;

use App\Models\Sales\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_can_list_invoices(): void
    {
        Invoice::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/sales/invoices', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_invoice(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $response = $this->postJson('/api/v1/sales/invoices', [
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
        $response = $this->postJson('/api/v1/sales/invoices', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->getJson("/api/v1/sales/invoices/{$invoice->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $invoice->id]]);
    }

    public function test_show_nonexistent_invoice_returns_404(): void
    {
        $response = $this->getJson('/api/v1/sales/invoices/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->deleteJson("/api/v1/sales/invoices/{$invoice->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('sales_invoices', ['id' => $invoice->id, 'deleted_at' => null]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/sales/invoices');

        $response->assertUnauthorized();
    }
}
