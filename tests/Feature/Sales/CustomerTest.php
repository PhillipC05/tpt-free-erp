<?php

namespace Tests\Feature\Sales;

use App\Models\Sales\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
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

    public function test_can_list_customers(): void
    {
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/sales/customers', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_customer(): void
    {
        $response = $this->postJson('/api/v1/sales/customers', [
            'code' => 'CUST-001',
            'name' => 'Acme Corp',
            'email' => 'acme@example.com',
            'status' => 'active',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.code', 'CUST-001');

        $this->assertDatabaseHas('sales_customers', ['code' => 'CUST-001']);
    }

    public function test_create_customer_requires_code_name(): void
    {
        $response = $this->postJson('/api/v1/sales/customers', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_customer_code_must_be_unique(): void
    {
        Customer::factory()->create(['code' => 'CUST-001']);

        $response = $this->postJson('/api/v1/sales/customers', [
            'code' => 'CUST-001',
            'name' => 'Duplicate',
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/v1/sales/customers/{$customer->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $customer->id]]);
    }

    public function test_show_nonexistent_customer_returns_404(): void
    {
        $response = $this->getJson('/api/v1/sales/customers/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create(['code' => 'CUST-001']);

        $response = $this->putJson("/api/v1/sales/customers/{$customer->id}", [
            'code' => 'CUST-001',
            'name' => 'Updated Name',
            'email' => $customer->email,
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('sales_customers', ['id' => $customer->id, 'name' => 'Updated Name']);
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/api/v1/sales/customers/{$customer->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('sales_customers', ['id' => $customer->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/sales/customers');

        $response->assertUnauthorized();
    }
}
