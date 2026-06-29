<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->token = $user->createToken('test')->plainTextToken;
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'sku' => 'SKU-0001',
            'name' => 'Test Product',
            'unit' => 'pcs',
            'unit_price' => 29.99,
            'cost_price' => 15.00,
            'valuation_method' => 'average',
            'min_stock_level' => 5,
        ], $overrides);
    }

    public function test_can_list_products(): void
    {
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/inventory/products', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_product(): void
    {
        $response = $this->postJson('/api/v1/inventory/products', $this->validPayload(), $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('inventory_products', ['sku' => 'SKU-0001']);
    }

    public function test_sku_must_be_unique(): void
    {
        Product::factory()->create(['sku' => 'SKU-DUPE']);

        $response = $this->postJson('/api/v1/inventory/products', $this->validPayload(['sku' => 'SKU-DUPE']), $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/inventory/products/{$product->id}", $this->auth());

        $response->assertOk()->assertJson(['success' => true, 'data' => ['id' => $product->id]]);
    }

    public function test_show_returns_404_for_missing_product(): void
    {
        $response = $this->getJson('/api/v1/inventory/products/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['sku' => 'SKU-001']);

        $response = $this->putJson("/api/v1/inventory/products/{$product->id}", $this->validPayload([
            'sku' => 'SKU-001',
            'name' => 'Updated Name',
        ]), $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('inventory_products', ['id' => $product->id, 'name' => 'Updated Name']);
    }

    public function test_can_soft_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/v1/inventory/products/{$product->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('inventory_products', ['id' => $product->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/inventory/products');

        $response->assertUnauthorized();
    }
}
