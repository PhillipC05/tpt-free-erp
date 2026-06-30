<?php

namespace Tests\Feature\Contracts;

use App\Models\Contracts\Contract;
use App\Models\Contracts\ContractMilestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContractMilestoneTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->assignAdminRole();
        $this->contract = Contract::factory()->create(['created_by' => $this->user->id]);
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

    public function test_can_list_milestones_for_contract(): void
    {
        ContractMilestone::factory()->count(2)->create(['contract_id' => $this->contract->id]);

        $response = $this->getJson("/api/v1/contracts/{$this->contract->id}/milestones", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_milestone(): void
    {
        $response = $this->postJson("/api/v1/contracts/{$this->contract->id}/milestones", [
            'title' => 'Initial Payment',
            'due_date' => '2026-08-01',
            'payment_amount' => 5000.00,
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.title', 'Initial Payment');

        $this->assertDatabaseHas('contract_milestones', [
            'contract_id' => $this->contract->id,
            'title' => 'Initial Payment',
        ]);
    }

    public function test_create_milestone_validates_required_fields(): void
    {
        $response = $this->postJson("/api/v1/contracts/{$this->contract->id}/milestones", [], $this->auth());

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_can_show_milestone(): void
    {
        $milestone = ContractMilestone::factory()->create(['contract_id' => $this->contract->id]);

        $response = $this->getJson(
            "/api/v1/contracts/{$this->contract->id}/milestones/{$milestone->id}",
            $this->auth()
        );

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.id', $milestone->id);
    }

    public function test_show_returns_404_for_wrong_contract(): void
    {
        $otherContract = Contract::factory()->create(['created_by' => $this->user->id]);
        $milestone = ContractMilestone::factory()->create(['contract_id' => $otherContract->id]);

        $response = $this->getJson(
            "/api/v1/contracts/{$this->contract->id}/milestones/{$milestone->id}",
            $this->auth()
        );

        $response->assertNotFound();
    }

    public function test_can_update_milestone(): void
    {
        $milestone = ContractMilestone::factory()->create(['contract_id' => $this->contract->id]);

        $response = $this->putJson(
            "/api/v1/contracts/{$this->contract->id}/milestones/{$milestone->id}",
            ['title' => 'Updated Milestone Title'],
            $this->auth()
        );

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('contract_milestones', [
            'id' => $milestone->id,
            'title' => 'Updated Milestone Title',
        ]);
    }

    public function test_can_mark_milestone_complete(): void
    {
        $milestone = ContractMilestone::factory()->create([
            'contract_id' => $this->contract->id,
            'is_completed' => false,
        ]);

        $response = $this->patchJson(
            "/api/v1/contracts/{$this->contract->id}/milestones/{$milestone->id}/complete",
            [],
            $this->auth()
        );

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('contract_milestones', [
            'id' => $milestone->id,
            'is_completed' => 1,
        ]);
        $this->assertNotNull(ContractMilestone::find($milestone->id)->completed_at);
    }

    public function test_can_delete_milestone(): void
    {
        $milestone = ContractMilestone::factory()->create(['contract_id' => $this->contract->id]);

        $response = $this->deleteJson(
            "/api/v1/contracts/{$this->contract->id}/milestones/{$milestone->id}",
            [],
            $this->auth()
        );

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('contract_milestones', ['id' => $milestone->id]);
    }

    public function test_returns_404_for_nonexistent_contract(): void
    {
        $response = $this->getJson('/api/v1/contracts/99999/milestones', $this->auth());

        $response->assertNotFound();
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson("/api/v1/contracts/{$this->contract->id}/milestones");

        $response->assertUnauthorized();
    }
}
