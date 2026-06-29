<?php

namespace Tests\Feature\Quality;

use App\Models\Inventory\Product;
use App\Models\Quality\QualityCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class QualityCheckTest extends TestCase
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


    public function test_can_list_quality_checks(): void
    {
        QualityCheck::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/quality/checks', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_quality_check(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/v1/quality/checks', [
            'check_code' => 'QC-001',
            'product_id' => $product->id,
            'type' => 'incoming',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('quality_checks', ['check_code' => 'QC-001']);
    }

    public function test_create_quality_check_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/v1/quality/checks', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_quality_check(): void
    {
        $check = QualityCheck::factory()->create();

        $response = $this->getJson("/api/v1/quality/checks/{$check->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $check->id]]);
    }

    public function test_show_nonexistent_check_returns_404(): void
    {
        $response = $this->getJson('/api/v1/quality/checks/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_record_check_result(): void
    {
        $check = QualityCheck::factory()->create(['result' => null]);

        $response = $this->postJson("/api/v1/quality/checks/{$check->id}/record-result", [
            'result' => 'pass',
            'notes' => 'All criteria met',
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('quality_checks', ['id' => $check->id, 'result' => 'pass']);
    }

    public function test_can_delete_quality_check(): void
    {
        $check = QualityCheck::factory()->create();

        $response = $this->deleteJson("/api/v1/quality/checks/{$check->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('quality_checks', ['id' => $check->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/quality/checks');

        $response->assertUnauthorized();
    }
}
