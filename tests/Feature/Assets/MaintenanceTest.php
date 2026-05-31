<?php

namespace Tests\Feature\Assets;

use App\Models\Assets\Asset;
use App\Models\Assets\MaintenanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_list_maintenance_records(): void
    {
        MaintenanceRecord::factory()->count(3)->create();

        $response = $this->getJson('/api/assets/maintenance', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_maintenance_record(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->postJson('/api/assets/maintenance', [
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
        $response = $this->postJson('/api/assets/maintenance', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_maintenance_record(): void
    {
        $record = MaintenanceRecord::factory()->create();

        $response = $this->getJson("/api/assets/maintenance/{$record->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $record->id]]);
    }

    public function test_show_nonexistent_record_returns_404(): void
    {
        $response = $this->getJson('/api/assets/maintenance/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_list_maintenance_by_asset(): void
    {
        $asset = Asset::factory()->create();
        MaintenanceRecord::factory()->count(2)->create(['asset_id' => $asset->id]);
        MaintenanceRecord::factory()->create();

        $response = $this->getJson("/api/assets/assets/{$asset->id}/maintenance-history", $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/assets/maintenance');

        $response->assertUnauthorized();
    }
}
