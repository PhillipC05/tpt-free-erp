<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
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

    public function test_can_list_accounts(): void
    {
        Account::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/finance/accounts', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_account(): void
    {
        $response = $this->postJson('/api/v1/finance/accounts', [
            'code' => '1000',
            'name' => 'Cash Account',
            'type' => 'asset',
            'currency' => 'USD',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.code', '1000');

        $this->assertDatabaseHas('finance_accounts', ['code' => '1000']);
    }

    public function test_create_account_requires_code_name_and_type(): void
    {
        $response = $this->postJson('/api/v1/finance/accounts', [], $this->auth());

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_account_code_must_be_unique(): void
    {
        Account::factory()->create(['code' => '1000']);

        $response = $this->postJson('/api/v1/finance/accounts', [
            'code' => '1000',
            'name' => 'Duplicate',
            'type' => 'asset',
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_account(): void
    {
        $account = Account::factory()->create();

        $response = $this->getJson("/api/v1/finance/accounts/{$account->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $account->id]]);
    }

    public function test_show_nonexistent_account_returns_404(): void
    {
        $response = $this->getJson('/api/v1/finance/accounts/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_account(): void
    {
        $account = Account::factory()->create(['code' => '1000']);

        $response = $this->putJson("/api/v1/finance/accounts/{$account->id}", [
            'code' => '1000',
            'name' => 'Updated Name',
            'type' => 'asset',
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('finance_accounts', ['id' => $account->id, 'name' => 'Updated Name']);
    }

    public function test_can_delete_account(): void
    {
        $account = Account::factory()->create();

        $response = $this->deleteJson("/api/v1/finance/accounts/{$account->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('finance_accounts', ['id' => $account->id]);
    }

    public function test_can_retrieve_account_balance(): void
    {
        $account = Account::factory()->create();

        $response = $this->getJson("/api/v1/finance/accounts/{$account->id}/balance", $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data' => ['account', 'total_debits', 'total_credits', 'calculated_balance']]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/finance/accounts');

        $response->assertUnauthorized();
    }
}
