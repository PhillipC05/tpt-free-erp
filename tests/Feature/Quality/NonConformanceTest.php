<?php

namespace Tests\Feature\Quality;

use App\Models\Quality\NonConformance;
use App\Models\Quality\QualityCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NonConformanceTest extends TestCase
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

    public function test_can_list_non_conformances(): void
    {
        NonConformance::factory()->count(3)->create();

        $response = $this->getJson('/api/quality/non-conformances', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_non_conformance(): void
    {
        $check = QualityCheck::factory()->create();

        $response = $this->postJson('/api/quality/non-conformances', [
            'nc_number' => 'NC-001',
            'check_id' => $check->id,
            'description' => 'Dimensional deviation found',
            'severity' => 'major',
            'status' => 'open',
            'target_resolution_date' => '2026-06-30',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('quality_non_conformances', ['nc_number' => 'NC-001']);
    }

    public function test_create_nc_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/quality/non-conformances', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_non_conformance(): void
    {
        $nc = NonConformance::factory()->create();

        $response = $this->getJson("/api/quality/non-conformances/{$nc->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $nc->id]]);
    }

    public function test_show_nonexistent_nc_returns_404(): void
    {
        $response = $this->getJson('/api/quality/non-conformances/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_nc_status(): void
    {
        $nc = NonConformance::factory()->create(['status' => 'open']);

        $response = $this->putJson("/api/quality/non-conformances/{$nc->id}/status", [
            'status' => 'investigating',
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('quality_non_conformances', ['id' => $nc->id, 'status' => 'investigating']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/quality/non-conformances');

        $response->assertUnauthorized();
    }
}
