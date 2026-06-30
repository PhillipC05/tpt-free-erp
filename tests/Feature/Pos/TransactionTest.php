<?php

namespace Tests\Feature\Pos;

use App\Models\Pos\Terminal;
use App\Models\Pos\Transaction;
use App\Models\Pos\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Terminal $terminal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->assignAdminRole();
        $this->terminal = Terminal::factory()->create();
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

    public function test_can_list_transactions(): void
    {
        Transaction::factory()->count(3)->create(['terminal_id' => $this->terminal->id]);

        $response = $this->getJson('/api/v1/pos/transactions', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_transaction(): void
    {
        $response = $this->postJson('/api/v1/pos/transactions', [
            'terminal_id' => $this->terminal->id,
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('pos_transactions', ['status' => 'open']);
    }

    public function test_create_transaction_requires_terminal(): void
    {
        $response = $this->postJson('/api/v1/pos/transactions', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_transaction(): void
    {
        $transaction = Transaction::factory()->create(['terminal_id' => $this->terminal->id]);

        $response = $this->getJson("/api/v1/pos/transactions/{$transaction->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $transaction->id]]);
    }

    public function test_show_nonexistent_transaction_returns_404(): void
    {
        $response = $this->getJson('/api/v1/pos/transactions/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_add_item_to_open_transaction(): void
    {
        $transaction = Transaction::factory()->create(['terminal_id' => $this->terminal->id, 'status' => 'open']);

        $response = $this->postJson("/api/v1/pos/transactions/{$transaction->id}/items", [
            'description' => 'Coffee',
            'quantity' => 2,
            'unit_price' => 5.00,
            'tax_percent' => 10,
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pos_transaction_items', ['transaction_id' => $transaction->id]);
    }

    public function test_cannot_add_item_to_non_open_transaction(): void
    {
        $transaction = Transaction::factory()->completed()->create(['terminal_id' => $this->terminal->id]);

        $response = $this->postJson("/api/v1/pos/transactions/{$transaction->id}/items", [
            'description' => 'Coffee',
            'quantity' => 1,
            'unit_price' => 5.00,
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_checkout_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'terminal_id' => $this->terminal->id,
            'status' => 'open',
            'total_amount' => 25.00,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'line_total' => 25.00,
        ]);

        $response = $this->postJson("/api/v1/pos/transactions/{$transaction->id}/checkout", [
            'payments' => [
                ['method' => 'cash', 'amount' => 25.00],
            ],
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('pos_transactions', [
            'id' => $transaction->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('pos_payments', [
            'transaction_id' => $transaction->id,
            'method' => 'cash',
            'amount' => 25.00,
        ]);
    }

    public function test_checkout_fails_with_insufficient_payment(): void
    {
        $transaction = Transaction::factory()->create([
            'terminal_id' => $this->terminal->id,
            'status' => 'open',
            'total_amount' => 50.00,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'line_total' => 50.00,
        ]);

        $response = $this->postJson("/api/v1/pos/transactions/{$transaction->id}/checkout", [
            'payments' => [
                ['method' => 'cash', 'amount' => 25.00],
            ],
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_checkout_fails_on_empty_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'terminal_id' => $this->terminal->id,
            'status' => 'open',
            'total_amount' => 0,
        ]);

        $response = $this->postJson("/api/v1/pos/transactions/{$transaction->id}/checkout", [
            'payments' => [
                ['method' => 'cash', 'amount' => 10.00],
            ],
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_void_completed_transaction(): void
    {
        $transaction = Transaction::factory()->completed()->create(['terminal_id' => $this->terminal->id]);

        $response = $this->postJson("/api/v1/pos/transactions/{$transaction->id}/void", [
            'reason' => 'Customer changed mind',
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('pos_transactions', [
            'id' => $transaction->id,
            'status' => 'voided',
        ]);
    }

    public function test_can_get_summary(): void
    {
        Transaction::factory()->completed()->create([
            'terminal_id' => $this->terminal->id,
            'total_amount' => 100.00,
            'tax_amount' => 10.00,
        ]);

        $response = $this->getJson('/api/v1/pos/transactions/summary', $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/pos/transactions');

        $response->assertUnauthorized();
    }

    public function test_can_filter_by_status(): void
    {
        Transaction::factory()->count(2)->create(['terminal_id' => $this->terminal->id, 'status' => 'open']);
        Transaction::factory()->count(1)->completed()->create(['terminal_id' => $this->terminal->id]);

        $response = $this->getJson('/api/v1/pos/transactions?status=open', $this->auth());

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
