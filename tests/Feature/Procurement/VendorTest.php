<?php

namespace Tests\Feature\Procurement;

use App\Models\Procurement\Vendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorTest extends TestCase
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

    public function test_can_list_vendors(): void
    {
        Vendor::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/procurement/vendors', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_vendor(): void
    {
        $response = $this->postJson('/api/v1/procurement/vendors', [
            'code' => 'VEN-001',
            'name' => 'Supplier Inc',
            'email' => 'supplier@example.com',
            'status' => 'active',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('procurement_vendors', ['code' => 'VEN-001']);
    }

    public function test_create_vendor_requires_code_and_name(): void
    {
        $response = $this->postJson('/api/v1/procurement/vendors', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_vendor_code_must_be_unique(): void
    {
        Vendor::factory()->create(['code' => 'VEN-001']);

        $response = $this->postJson('/api/v1/procurement/vendors', [
            'code' => 'VEN-001',
            'name' => 'Duplicate',
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_vendor(): void
    {
        $vendor = Vendor::factory()->create();

        $response = $this->getJson("/api/v1/procurement/vendors/{$vendor->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $vendor->id]]);
    }

    public function test_show_nonexistent_vendor_returns_404(): void
    {
        $response = $this->getJson('/api/v1/procurement/vendors/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_vendor(): void
    {
        $vendor = Vendor::factory()->create(['code' => 'VEN-001']);

        $response = $this->putJson("/api/v1/procurement/vendors/{$vendor->id}", [
            'code' => 'VEN-001',
            'name' => 'Updated Supplier',
            'email' => $vendor->email,
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('procurement_vendors', ['id' => $vendor->id, 'name' => 'Updated Supplier']);
    }

    public function test_can_delete_vendor(): void
    {
        $vendor = Vendor::factory()->create();

        $response = $this->deleteJson("/api/v1/procurement/vendors/{$vendor->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('procurement_vendors', ['id' => $vendor->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/procurement/vendors');

        $response->assertUnauthorized();
    }
}
