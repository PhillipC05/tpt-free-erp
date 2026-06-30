<?php

namespace Tests\Feature\Agent;

use App\Jobs\AgentSkillJob;
use App\Models\Agent\AgentExecution;
use App\Models\Agent\AgentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AgentTest extends TestCase
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
            'name' => 'admin', 'display_name' => 'Admin', 'description' => 'Admin',
            'is_system' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $adminId = DB::table('roles')->where('name', 'admin')->value('id');
        DB::table('user_roles')->insert([
            'user_id' => $this->user->id, 'role_id' => $adminId,
            'assigned_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_can_list_agents(): void
    {
        AgentProfile::create([
            'name' => 'Finance Bot', 'agent_type' => 'local',
            'is_active' => true, 'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/agents', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_create_agent(): void
    {
        $response = $this->postJson('/api/v1/agents', [
            'name' => 'HR Assistant',
            'agent_type' => 'local',
            'description' => 'Handles HR tasks',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.name', 'HR Assistant');

        $this->assertDatabaseHas('agent_profiles', ['name' => 'HR Assistant']);
    }

    public function test_create_agent_requires_name_and_type(): void
    {
        $response = $this->postJson('/api/v1/agents', [], $this->auth());
        $response->assertStatus(422);
    }

    public function test_can_show_agent(): void
    {
        $agent = AgentProfile::create([
            'name' => 'Sales Bot', 'agent_type' => 'openrouter',
            'is_active' => true, 'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/agents/{$agent->id}", $this->auth());

        $response->assertOk()
            ->assertJsonPath('data.id', $agent->id)
            ->assertJsonPath('data.name', 'Sales Bot');
    }

    public function test_can_update_agent(): void
    {
        $agent = AgentProfile::create([
            'name' => 'Old Name', 'agent_type' => 'local',
            'is_active' => true, 'created_by' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/agents/{$agent->id}", [
            'name' => 'New Name',
            'is_active' => false,
        ], $this->auth());

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
        $this->assertDatabaseHas('agent_profiles', ['id' => $agent->id, 'is_active' => 0]);
    }

    public function test_can_soft_delete_agent(): void
    {
        $agent = AgentProfile::create([
            'name' => 'Disposable', 'agent_type' => 'local',
            'is_active' => true, 'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/agents/{$agent->id}", [], $this->auth());
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSoftDeleted('agent_profiles', ['id' => $agent->id]);
    }

    public function test_can_enable_skill_on_agent(): void
    {
        $agent = AgentProfile::create([
            'name' => 'Finance Agent', 'agent_type' => 'local',
            'is_active' => true, 'created_by' => $this->user->id,
        ]);

        // Create a minimal skill file so SkillRegistry can find it
        $skillDir = storage_path('app/skills/finance');
        if (! is_dir($skillDir)) {
            mkdir($skillDir, 0755, true);
        }
        file_put_contents($skillDir.'/test_skill.md', implode("\n", [
            '---',
            'name: Test Skill',
            'slug: finance.test_skill',
            'version: "1.0"',
            'category: finance',
            'description: A test skill.',
            'required_permissions:',
            '  - finance.view',
            'affected_modules:',
            '  - finance',
            'inputs: []',
            'outputs: []',
            'model_tier: fast',
            'estimated_tokens: 100',
            'cost_tier: low',
            'enabled_by_default: false',
            'tags: [test]',
            '---',
            '',
            '## Task',
            '',
            'Return JSON: {"result": "ok"}',
        ]));

        $response = $this->putJson("/api/v1/agents/{$agent->id}/skills/finance.test_skill", [
            'is_enabled' => true,
        ], $this->auth());

        $response->assertOk()->assertJsonPath('data.is_enabled', true);
        $this->assertDatabaseHas('agent_skill_assignments', [
            'agent_profile_id' => $agent->id,
            'skill_slug' => 'finance.test_skill',
            'is_enabled' => 1,
        ]);
    }

    public function test_can_create_agent_token(): void
    {
        $agent = AgentProfile::create([
            'name' => 'Token Agent', 'agent_type' => 'local',
            'is_active' => true, 'created_by' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/agents/{$agent->id}/tokens", [
            'name' => 'Production Token',
        ], $this->auth());

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['plain_token', 'warning']]);

        $this->assertDatabaseHas('agent_tokens', ['agent_profile_id' => $agent->id]);
    }

    public function test_can_view_execution_log(): void
    {
        $agent = AgentProfile::create([
            'name' => 'Log Agent', 'agent_type' => 'local',
            'is_active' => true, 'created_by' => $this->user->id,
        ]);

        AgentExecution::create([
            'agent_profile_id' => $agent->id,
            'skill_slug' => 'finance.extract_invoice',
            'trigger_type' => 'manual',
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/v1/agents/{$agent->id}/executions", $this->auth());

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_manual_skill_run_queues_job(): void
    {
        Queue::fake();

        $skillDir = storage_path('app/skills/finance');
        if (! is_dir($skillDir)) {
            mkdir($skillDir, 0755, true);
        }
        file_put_contents($skillDir.'/test_skill.md', implode("\n", [
            '---',
            'name: Test Skill',
            'slug: finance.test_skill',
            'version: "1.0"',
            'category: finance',
            'description: A test skill.',
            'required_permissions: []',
            'affected_modules: []',
            'inputs: []',
            'outputs: []',
            'model_tier: fast',
            'estimated_tokens: 100',
            'cost_tier: low',
            'enabled_by_default: false',
            'tags: []',
            '---',
            '',
            '## Task',
            'Return JSON.',
        ]));

        $agent = AgentProfile::create([
            'name' => 'Run Agent', 'agent_type' => 'local',
            'is_active' => true, 'created_by' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/agents/{$agent->id}/skills/finance.test_skill/run", [
            'input' => ['test' => true],
        ], $this->auth());

        $response->assertCreated()->assertJsonStructure(['data' => ['execution_id', 'status']]);
        Queue::assertPushed(AgentSkillJob::class);
    }

    public function test_cannot_access_agents_without_admin_role(): void
    {
        $nonAdmin = User::factory()->create();
        $token = $nonAdmin->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/agents', ['Authorization' => "Bearer {$token}"]);
        $response->assertForbidden();
    }
}
