<?php

namespace Tests\Feature\Projects;

use App\Models\Projects\Project;
use App\Models\Projects\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskTest extends TestCase
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

    public function test_can_list_tasks(): void
    {
        Task::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/projects/tasks', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_task(): void
    {
        $project = Project::factory()->create();

        $response = $this->postJson('/api/v1/projects/tasks', [
            'code' => 'TASK-001',
            'project_id' => $project->id,
            'title' => 'Set up database',
            'status' => 'todo',
            'priority' => 'medium',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('project_tasks', ['code' => 'TASK-001']);
    }

    public function test_create_task_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/v1/projects/tasks', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_task(): void
    {
        $task = Task::factory()->create();

        $response = $this->getJson("/api/v1/projects/tasks/{$task->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $task->id]]);
    }

    public function test_show_nonexistent_task_returns_404(): void
    {
        $response = $this->getJson('/api/v1/projects/tasks/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_task_status(): void
    {
        $task = Task::factory()->create(['status' => 'todo']);

        $response = $this->putJson("/api/v1/projects/tasks/{$task->id}/status", [
            'status' => 'in_progress',
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('project_tasks', ['id' => $task->id, 'status' => 'in_progress']);
    }

    public function test_can_list_tasks_by_project(): void
    {
        $project = Project::factory()->create();
        Task::factory()->count(2)->create(['project_id' => $project->id]);
        Task::factory()->create();

        $response = $this->getJson("/api/v1/projects/projects/{$project->id}/tasks", $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/projects/tasks');

        $response->assertUnauthorized();
    }
}
