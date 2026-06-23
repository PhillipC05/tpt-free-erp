<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BudgetTest extends TestCase
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

        DB::table('user_roles')->insert([
            'user_id' => $this->user->id,
            'role_id' => $adminId,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_can_list_budgets(): void
    {
        Budget::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/v1/finance/budgets', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_budget(): void
    {
        $response = $this->postJson('/api/v1/finance/budgets', [
            'name' => 'FY2026 Annual Budget',
            'period_type' => 'annual',
            'year' => 2026,
            'status' => 'draft',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.name', 'FY2026 Annual Budget');

        $this->assertDatabaseHas('finance_budgets', ['name' => 'FY2026 Annual Budget']);
    }

    public function test_create_budget_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/finance/budgets', [], $this->auth());

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_create_budget_validates_period_type_enum(): void
    {
        $response = $this->postJson('/api/v1/finance/budgets', [
            'name' => 'Test',
            'period_type' => 'invalid_type',
            'year' => 2026,
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_budget_with_lines(): void
    {
        $budget = Budget::factory()->create(['created_by' => $this->user->id]);

        $response = $this->getJson("/api/v1/finance/budgets/{$budget->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.id', $budget->id);
    }

    public function test_show_returns_404_for_missing_budget(): void
    {
        $response = $this->getJson('/api/v1/finance/budgets/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_budget(): void
    {
        $budget = Budget::factory()->create([
            'name' => 'Old Name',
            'created_by' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/finance/budgets/{$budget->id}", [
            'name' => 'Updated Budget',
            'status' => 'active',
        ], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('finance_budgets', [
            'id' => $budget->id,
            'name' => 'Updated Budget',
            'status' => 'active',
        ]);
    }

    public function test_can_delete_budget(): void
    {
        $budget = Budget::factory()->create(['created_by' => $this->user->id]);

        $response = $this->deleteJson("/api/v1/finance/budgets/{$budget->id}", [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('finance_budgets', ['id' => $budget->id]);
    }

    public function test_can_filter_budgets_by_year(): void
    {
        Budget::factory()->create(['year' => 2025, 'created_by' => $this->user->id]);
        Budget::factory()->create(['year' => 2026, 'created_by' => $this->user->id]);

        $response = $this->getJson('/api/v1/finance/budgets?year=2026', $this->auth());

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(2026, $data[0]['year']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/finance/budgets');

        $response->assertUnauthorized();
    }
}
