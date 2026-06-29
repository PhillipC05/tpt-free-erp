<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->account = Account::factory()->create();
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'account_id' => $this->account->id,
            'type' => 'debit',
            'amount' => 500.00,
            'description' => 'Test transaction',
            'transaction_date' => '2026-01-15',
        ], $overrides);
    }

    public function test_can_create_transaction(): void
    {
        $response = $this->postJson('/api/v1/finance/transactions', $this->validPayload(), $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('finance_transactions', [
            'account_id' => $this->account->id,
            'type' => 'debit',
            'amount' => 500.00,
        ]);
    }

    public function test_transaction_type_must_be_debit_or_credit(): void
    {
        $response = $this->postJson('/api/v1/finance/transactions', $this->validPayload(['type' => 'invalid']), $this->auth());

        $response->assertStatus(422);
    }

    public function test_amount_must_be_positive(): void
    {
        $response = $this->postJson('/api/v1/finance/transactions', $this->validPayload(['amount' => -100]), $this->auth());

        $response->assertStatus(422);
    }

    public function test_account_must_exist(): void
    {
        $response = $this->postJson('/api/v1/finance/transactions', $this->validPayload(['account_id' => 99999]), $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_approve_pending_transaction(): void
    {
        $transaction = Transaction::factory()->create(['account_id' => $this->account->id, 'status' => 'pending']);

        $response = $this->postJson("/api/v1/finance/transactions/{$transaction->id}/approve", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('finance_transactions', ['id' => $transaction->id, 'status' => 'posted']);
    }

    public function test_cannot_update_posted_transaction(): void
    {
        $transaction = Transaction::factory()->posted()->create(['account_id' => $this->account->id]);

        $response = $this->putJson("/api/v1/finance/transactions/{$transaction->id}", $this->validPayload(), $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_void_transaction(): void
    {
        $transaction = Transaction::factory()->create(['account_id' => $this->account->id, 'status' => 'pending']);

        $response = $this->postJson("/api/v1/finance/transactions/{$transaction->id}/void", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('finance_transactions', ['id' => $transaction->id, 'status' => 'void']);
    }

    public function test_cannot_delete_posted_transaction(): void
    {
        $transaction = Transaction::factory()->posted()->create(['account_id' => $this->account->id]);

        $response = $this->deleteJson("/api/v1/finance/transactions/{$transaction->id}", [], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_list_transactions_by_account(): void
    {
        Transaction::factory()->count(3)->create(['account_id' => $this->account->id]);
        $otherAccount = Account::factory()->create();
        Transaction::factory()->create(['account_id' => $otherAccount->id]);

        $response = $this->getJson("/api/v1/finance/accounts/{$this->account->id}/transactions", $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }
}
