<?php

namespace Tests\Feature\Assets;

use App\Models\Assets\Asset;
use App\Models\Assets\MaintenanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class MaintenanceTest extends TestCase
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


    public function test_can_list_maintenance_records(): void
    {
        MaintenanceRecord::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/assets/maintenance', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_maintenance_record(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->postJson('/api/v1/assets/maintenance', [
            'asset_id' => $asset->id,
            'title' => 'Annual service',
            'description' => 'Annual preventive maintenance check',
            'type' => 'preventive',
            'scheduled_date' => '2026-06-15',
            'status' => 'scheduled',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('asset_maintenance', ['asset_id' => $asset->id]);
    }

    public function test_create_maintenance_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/v1/assets/maintenance', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_maintenance_record(): void
    {
        $record = MaintenanceRecord::factory()->create();

        $response = $this->getJson("/api/v1/assets/maintenance/{$record->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $record->id]]);
    }

    public function test_show_nonexistent_record_returns_404(): void
    {
        $response = $this->getJson('/api/v1/assets/maintenance/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_list_maintenance_by_asset(): void
    {
        $asset = Asset::factory()->create();
        MaintenanceRecord::factory()->count(2)->create(['asset_id' => $asset->id]);
        MaintenanceRecord::factory()->create();

        $response = $this->getJson("/api/v1/assets/assets/{$asset->id}/maintenance-history", $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/assets/maintenance');

        $response->assertUnauthorized();
    }
}
