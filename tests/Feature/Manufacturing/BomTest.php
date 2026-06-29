<?php

namespace Tests\Feature\Manufacturing;

use App\Models\Inventory\Product;
use App\Models\Manufacturing\Bom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class BomTest extends TestCase
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


    public function test_can_list_boms(): void
    {
        Bom::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/manufacturing/boms', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_bom(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/v1/manufacturing/boms', [
            'code' => 'BOM-001',
            'name' => 'Widget Assembly',
            'product_id' => $product->id,
            'quantity' => 1,
            'is_active' => true,
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('manufacturing_boms', ['code' => 'BOM-001']);
    }

    public function test_create_bom_requires_code_and_name(): void
    {
        $response = $this->postJson('/api/v1/manufacturing/boms', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_bom_code_must_be_unique(): void
    {
        Bom::factory()->create(['code' => 'BOM-001']);

        $response = $this->postJson('/api/v1/manufacturing/boms', [
            'code' => 'BOM-001',
            'name' => 'Duplicate',
            'quantity' => 1,
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_bom(): void
    {
        $bom = Bom::factory()->create();

        $response = $this->getJson("/api/v1/manufacturing/boms/{$bom->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $bom->id]]);
    }

    public function test_show_nonexistent_bom_returns_404(): void
    {
        $response = $this->getJson('/api/v1/manufacturing/boms/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_bom(): void
    {
        $bom = Bom::factory()->create(['code' => 'BOM-001']);

        $response = $this->putJson("/api/v1/manufacturing/boms/{$bom->id}", [
            'code' => 'BOM-001',
            'name' => 'Updated Assembly',
            'product_id' => $bom->product_id,
            'quantity' => 2,
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('manufacturing_boms', ['id' => $bom->id, 'name' => 'Updated Assembly']);
    }

    public function test_can_delete_bom(): void
    {
        $bom = Bom::factory()->create();

        $response = $this->deleteJson("/api/v1/manufacturing/boms/{$bom->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('manufacturing_boms', ['id' => $bom->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/manufacturing/boms');

        $response->assertUnauthorized();
    }
}
