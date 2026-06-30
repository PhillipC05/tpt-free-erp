<?php

namespace Tests\Feature\Expenses;

use App\Models\Expenses\ExpenseReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpenseTest extends TestCase
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

    public function test_can_create_expense_report(): void
    {
        $response = $this->postJson('/api/v1/expenses', [
            'title' => 'Q2 Travel Expenses',
            'notes' => 'Flights and accommodation for Q2 conference.',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('expense_reports', [
            'title' => 'Q2 Travel Expenses',
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);
    }

    public function test_can_list_own_expense_reports(): void
    {
        ExpenseReport::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/expenses', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_update_draft_expense(): void
    {
        $report = ExpenseReport::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->putJson("/api/v1/expenses/{$report->id}", [
            'title' => 'Updated Expense Report',
        ], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('expense_reports', [
            'id' => $report->id,
            'title' => 'Updated Expense Report',
        ]);
    }

    public function test_admin_can_approve_expense(): void
    {
        $report = ExpenseReport::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'submitted',
        ]);

        $response = $this->postJson("/api/v1/expenses/{$report->id}/approve", [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('expense_reports', [
            'id' => $report->id,
            'status' => 'approved',
        ]);
    }

    public function test_cannot_delete_non_draft_expense(): void
    {
        $report = ExpenseReport::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);

        $response = $this->deleteJson("/api/v1/expenses/{$report->id}", [], $this->auth());

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/expenses');

        $response->assertUnauthorized();
    }
}
