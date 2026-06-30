<?php

namespace Tests\Feature\Assets;

use App\Models\Assets\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetTest extends TestCase
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

    public function test_can_list_assets(): void
    {
        Asset::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/assets/assets', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_asset(): void
    {
        $response = $this->postJson('/api/v1/assets/assets', [
            'asset_code' => 'AST-001',
            'name' => 'Dell Laptop',
            'type' => 'equipment',
            'purchase_date' => '2026-01-01',
            'purchase_cost' => 1500.00,
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('assets', ['asset_code' => 'AST-001']);
    }

    public function test_create_asset_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/v1/assets/assets', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_asset_code_must_be_unique(): void
    {
        Asset::factory()->create(['asset_code' => 'AST-001']);

        $response = $this->postJson('/api/v1/assets/assets', [
            'asset_code' => 'AST-001',
            'name' => 'Duplicate',
            'type' => 'equipment',
            'purchase_date' => '2026-01-01',
            'purchase_cost' => 500,
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_asset(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->getJson("/api/v1/assets/assets/{$asset->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $asset->id]]);
    }

    public function test_show_nonexistent_asset_returns_404(): void
    {
        $response = $this->getJson('/api/v1/assets/assets/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_calculate_depreciation(): void
    {
        $asset = Asset::factory()->create([
            'purchase_cost' => 10000,
            'salvage_value' => 1000,
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
            'current_value' => 10000,
        ]);

        $response = $this->postJson("/api/v1/assets/assets/{$asset->id}/depreciate", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'current_value' => 8200.00]);
    }

    public function test_depreciation_requires_configuration(): void
    {
        $asset = Asset::factory()->create([
            'useful_life_years' => null,
        ]);

        $response = $this->postJson("/api/v1/assets/assets/{$asset->id}/depreciate", [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_delete_asset(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->deleteJson("/api/v1/assets/assets/{$asset->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/assets/assets');

        $response->assertUnauthorized();
    }
}
