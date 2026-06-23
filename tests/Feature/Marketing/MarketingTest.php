<?php

namespace Tests\Feature\Marketing;

use App\Models\Marketing\Campaign;
use App\Models\Marketing\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketingTest extends TestCase
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

    public function test_can_list_campaigns(): void
    {
        Campaign::factory()->count(2)->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/v1/marketing/campaigns', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_campaign(): void
    {
        $response = $this->postJson('/api/v1/marketing/campaigns', [
            'name' => 'Summer Sale',
            'code' => 'SUMMER-2026',
            'type' => 'email',
            'status' => 'draft',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.code', 'SUMMER-2026');

        $this->assertDatabaseHas('marketing_campaigns', ['code' => 'SUMMER-2026']);
    }

    public function test_can_update_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'code' => 'CAMP-001',
            'created_by' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/marketing/campaigns/{$campaign->id}", [
            'name' => 'Updated Campaign',
            'code' => 'CAMP-001',
            'type' => 'email',
        ], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('marketing_campaigns', ['id' => $campaign->id, 'name' => 'Updated Campaign']);
    }

    public function test_can_delete_campaign(): void
    {
        $campaign = Campaign::factory()->create(['created_by' => $this->user->id]);

        $response = $this->deleteJson("/api/v1/marketing/campaigns/{$campaign->id}", [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('marketing_campaigns', ['id' => $campaign->id]);
    }

    public function test_can_create_lead(): void
    {
        $response = $this->postJson('/api/v1/marketing/leads', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'source' => 'organic',
            'status' => 'new',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.email', 'jane.doe@example.com');

        $this->assertDatabaseHas('marketing_leads', ['email' => 'jane.doe@example.com']);
    }

    public function test_can_convert_lead_to_customer(): void
    {
        $lead = Lead::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john.smith@example.com',
            'source' => 'organic',
            'status' => 'qualified',
        ]);

        $response = $this->postJson("/api/v1/marketing/leads/{$lead->id}/convert", [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('marketing_leads', [
            'id' => $lead->id,
            'status' => 'converted',
        ]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/marketing/campaigns');

        $response->assertUnauthorized();
    }
}
