<?php

namespace Tests\Feature\Contracts;

use App\Models\Contracts\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContractTest extends TestCase
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

    public function test_can_create_contract(): void
    {
        $response = $this->postJson('/api/v1/contracts', [
            'title' => 'Service Agreement',
            'contract_number' => 'CONT-2026-001',
            'type' => 'service',
            'status' => 'draft',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.contract_number', 'CONT-2026-001');

        $this->assertDatabaseHas('contracts', [
            'contract_number' => 'CONT-2026-001',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_can_list_contracts(): void
    {
        Contract::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/v1/contracts', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_update_contract(): void
    {
        $contract = Contract::factory()->create([
            'contract_number' => 'CONT-2026-002',
            'created_by' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/contracts/{$contract->id}", [
            'title' => 'Updated Contract Title',
            'contract_number' => 'CONT-2026-002',
            'type' => $contract->type,
        ], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'title' => 'Updated Contract Title',
        ]);
    }

    public function test_can_sign_contract(): void
    {
        $contract = Contract::factory()->create([
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/contracts/{$contract->id}/sign", [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => 'signed',
            'signed_by' => $this->user->id,
        ]);
    }

    public function test_can_delete_contract(): void
    {
        $contract = Contract::factory()->create(['created_by' => $this->user->id]);

        $response = $this->deleteJson("/api/v1/contracts/{$contract->id}", [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/contracts');

        $response->assertUnauthorized();
    }
}
