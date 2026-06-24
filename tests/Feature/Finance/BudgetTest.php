<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\BudgetLine;
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

    // ===== BUDGET APPROVAL WORKFLOW =====

    public function test_can_approve_draft_budget(): void
    {
        $budget = Budget::factory()->create([
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/finance/budgets/{$budget->id}/approve", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('finance_budgets', ['id' => $budget->id, 'status' => 'active']);
    }

    public function test_cannot_approve_non_draft_budget(): void
    {
        $budget = Budget::factory()->create([
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/finance/budgets/{$budget->id}/approve", [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_close_active_budget(): void
    {
        $budget = Budget::factory()->create([
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/finance/budgets/{$budget->id}/close", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('finance_budgets', ['id' => $budget->id, 'status' => 'closed']);
    }

    public function test_cannot_close_draft_budget(): void
    {
        $budget = Budget::factory()->create([
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/finance/budgets/{$budget->id}/close", [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    // ===== BUDGET LINES =====

    public function test_can_list_budget_lines(): void
    {
        $budget = Budget::factory()->create(['created_by' => $this->user->id]);
        $account = Account::factory()->create();
        BudgetLine::factory()->create(['budget_id' => $budget->id, 'account_id' => $account->id]);

        $response = $this->getJson("/api/v1/finance/budgets/{$budget->id}/lines", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data']);
    }

    public function test_can_create_budget_line(): void
    {
        $budget = Budget::factory()->create([
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        $account = Account::factory()->create();

        $response = $this->postJson("/api/v1/finance/budgets/{$budget->id}/lines", [
            'account_id' => $account->id,
            'budgeted_amount' => 10000,
            'notes' => 'Test line',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('budget_lines', [
            'budget_id' => $budget->id,
            'account_id' => $account->id,
        ]);
    }

    public function test_cannot_add_duplicate_account_line_to_budget(): void
    {
        $budget = Budget::factory()->create(['created_by' => $this->user->id]);
        $account = Account::factory()->create();
        BudgetLine::factory()->create(['budget_id' => $budget->id, 'account_id' => $account->id]);

        $response = $this->postJson("/api/v1/finance/budgets/{$budget->id}/lines", [
            'account_id' => $account->id,
            'budgeted_amount' => 5000,
        ], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_cannot_add_line_to_closed_budget(): void
    {
        $budget = Budget::factory()->create([
            'status' => 'closed',
            'created_by' => $this->user->id,
        ]);
        $account = Account::factory()->create();

        $response = $this->postJson("/api/v1/finance/budgets/{$budget->id}/lines", [
            'account_id' => $account->id,
            'budgeted_amount' => 5000,
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_update_budget_line(): void
    {
        $budget = Budget::factory()->create([
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        $account = Account::factory()->create();
        $line = BudgetLine::factory()->create([
            'budget_id' => $budget->id,
            'account_id' => $account->id,
            'budgeted_amount' => 1000,
        ]);

        $response = $this->putJson(
            "/api/v1/finance/budgets/{$budget->id}/lines/{$line->id}",
            ['budgeted_amount' => 2500],
            $this->auth()
        );

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('budget_lines', ['id' => $line->id, 'budgeted_amount' => 2500]);
    }

    public function test_can_delete_budget_line(): void
    {
        $budget = Budget::factory()->create([
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        $account = Account::factory()->create();
        $line = BudgetLine::factory()->create([
            'budget_id' => $budget->id,
            'account_id' => $account->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/finance/budgets/{$budget->id}/lines/{$line->id}",
            [],
            $this->auth()
        );

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('budget_lines', ['id' => $line->id]);
    }

    // ===== BUDGET VARIANCE =====

    public function test_variance_endpoint_returns_correct_structure(): void
    {
        $budget = Budget::factory()->create(['created_by' => $this->user->id]);
        $account = Account::factory()->create();
        BudgetLine::factory()->create([
            'budget_id' => $budget->id,
            'account_id' => $account->id,
            'budgeted_amount' => 10000,
            'actual_amount' => 7500,
        ]);

        $response = $this->getJson("/api/v1/finance/budgets/{$budget->id}/variance", $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['data' => [
                'budget_id', 'budget_name', 'status',
                'total_budgeted', 'total_actual', 'total_variance',
                'utilization_percent', 'lines',
            ]]);
    }

    public function test_variance_calculates_correctly(): void
    {
        $budget = Budget::factory()->create(['created_by' => $this->user->id]);
        $account = Account::factory()->create();
        BudgetLine::factory()->create([
            'budget_id' => $budget->id,
            'account_id' => $account->id,
            'budgeted_amount' => 10000,
            'actual_amount' => 6000,
        ]);

        $response = $this->getJson("/api/v1/finance/budgets/{$budget->id}/variance", $this->auth());

        $data = $response->json('data');
        $this->assertEquals(10000, $data['total_budgeted']);
        $this->assertEquals(6000, $data['total_actual']);
        $this->assertEquals(4000, $data['total_variance']);
        $this->assertEquals(60.0, $data['utilization_percent']);
    }
}
