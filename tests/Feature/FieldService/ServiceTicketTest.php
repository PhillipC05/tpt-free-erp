<?php

namespace Tests\Feature\FieldService;

use App\Models\FieldService\ServiceTicket;
use App\Models\Sales\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ServiceTicketTest extends TestCase
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


    public function test_can_list_tickets(): void
    {
        ServiceTicket::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/field-service/tickets', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_ticket(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/field-service/tickets', [
            'ticket_number' => 'TKT-0001',
            'customer_id' => $customer->id,
            'title' => 'Equipment not working',
            'description' => 'The machine stopped responding.',
            'priority' => 'high',
            'status' => 'open',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('field_service_tickets', ['ticket_number' => 'TKT-0001']);
    }

    public function test_create_ticket_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/v1/field-service/tickets', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_ticket(): void
    {
        $ticket = ServiceTicket::factory()->create();

        $response = $this->getJson("/api/v1/field-service/tickets/{$ticket->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $ticket->id]]);
    }

    public function test_show_nonexistent_ticket_returns_404(): void
    {
        $response = $this->getJson('/api/v1/field-service/tickets/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_ticket_status(): void
    {
        $ticket = ServiceTicket::factory()->create(['status' => 'open']);

        $response = $this->putJson("/api/v1/field-service/tickets/{$ticket->id}/status", [
            'status' => 'in_progress',
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('field_service_tickets', ['id' => $ticket->id, 'status' => 'in_progress']);
    }

    public function test_can_delete_ticket(): void
    {
        $ticket = ServiceTicket::factory()->create();

        $response = $this->deleteJson("/api/v1/field-service/tickets/{$ticket->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('field_service_tickets', ['id' => $ticket->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/field-service/tickets');

        $response->assertUnauthorized();
    }
}
