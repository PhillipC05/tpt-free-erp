<?php

namespace Tests\Feature\Projects;

use App\Models\Projects\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
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

    public function test_can_list_projects(): void
    {
        Project::factory()->count(3)->create();

        $response = $this->getJson('/api/projects/projects', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_project(): void
    {
        $response = $this->postJson('/api/projects/projects', [
            'code' => 'PROJ-001',
            'name' => 'New ERP Implementation',
            'start_date' => '2026-06-01',
            'status' => 'planning',
            'priority' => 'high',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('projects', ['code' => 'PROJ-001']);
    }

    public function test_create_project_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/projects/projects', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_project_code_must_be_unique(): void
    {
        Project::factory()->create(['code' => 'PROJ-001']);

        $response = $this->postJson('/api/projects/projects', [
            'code' => 'PROJ-001',
            'name' => 'Duplicate',
            'start_date' => '2026-06-01',
            'status' => 'planning',
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->getJson("/api/projects/projects/{$project->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $project->id]]);
    }

    public function test_show_nonexistent_project_returns_404(): void
    {
        $response = $this->getJson('/api/projects/projects/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_project(): void
    {
        $project = Project::factory()->create(['code' => 'PROJ-001']);

        $response = $this->putJson("/api/projects/projects/{$project->id}", [
            'code' => 'PROJ-001',
            'name' => 'Updated Project',
            'start_date' => '2026-06-01',
            'status' => 'active',
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'active']);
    }

    public function test_can_delete_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->deleteJson("/api/projects/projects/{$project->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/projects/projects');

        $response->assertUnauthorized();
    }
}
