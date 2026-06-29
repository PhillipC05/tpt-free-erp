<?php

namespace Tests\Feature\Pos;

use App\Models\Pos\Terminal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TerminalTest extends TestCase
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


    public function test_can_list_terminals(): void
    {
        Terminal::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/pos/terminals', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_terminal(): void
    {
        $response = $this->postJson('/api/v1/pos/terminals', [
            'terminal_code' => 'POS-001',
            'name' => 'Main Counter',
            'status' => 'active',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.terminal_code', 'POS-001');

        $this->assertDatabaseHas('pos_terminals', ['terminal_code' => 'POS-001']);
    }

    public function test_create_terminal_requires_code_and_name(): void
    {
        $response = $this->postJson('/api/v1/pos/terminals', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_terminal_code_must_be_unique(): void
    {
        Terminal::factory()->create(['terminal_code' => 'POS-001']);

        $response = $this->postJson('/api/v1/pos/terminals', [
            'terminal_code' => 'POS-001',
            'name' => 'Duplicate',
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_terminal(): void
    {
        $terminal = Terminal::factory()->create();

        $response = $this->getJson("/api/v1/pos/terminals/{$terminal->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $terminal->id]]);
    }

    public function test_show_nonexistent_terminal_returns_404(): void
    {
        $response = $this->getJson('/api/v1/pos/terminals/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_terminal(): void
    {
        $terminal = Terminal::factory()->create(['terminal_code' => 'POS-001']);

        $response = $this->putJson("/api/v1/pos/terminals/{$terminal->id}", [
            'terminal_code' => 'POS-001',
            'name' => 'Updated Terminal',
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('pos_terminals', ['id' => $terminal->id, 'name' => 'Updated Terminal']);
    }

    public function test_can_delete_terminal(): void
    {
        $terminal = Terminal::factory()->create();

        $response = $this->deleteJson("/api/v1/pos/terminals/{$terminal->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('pos_terminals', ['id' => $terminal->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/pos/terminals');

        $response->assertUnauthorized();
    }

    public function test_can_filter_by_status(): void
    {
        Terminal::factory()->count(2)->create(['status' => 'active']);
        Terminal::factory()->count(1)->inactive()->create();

        $response = $this->getJson('/api/v1/pos/terminals?status=active', $this->auth());

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_terminals(): void
    {
        Terminal::factory()->create(['name' => 'Front Desk']);
        Terminal::factory()->create(['name' => 'Back Office']);

        $response = $this->getJson('/api/v1/pos/terminals?search=Front', $this->auth());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
