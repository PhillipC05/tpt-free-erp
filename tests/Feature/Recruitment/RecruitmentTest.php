<?php

namespace Tests\Feature\Recruitment;

use App\Models\HR\Employee;
use App\Models\Recruitment\Application;
use App\Models\Recruitment\Interview;
use App\Models\Recruitment\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecruitmentTest extends TestCase
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

    // ── Jobs ─────────────────────────────────────────────────────
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


    public function test_can_create_job(): void
    {
        $response = $this->postJson('/api/v1/recruitment/jobs', [
            'job_code' => 'JOB-001',
            'title' => 'Software Engineer',
            'description' => 'Build great software',
            'location' => 'Auckland',
            'salary_min' => 50000,
            'salary_max' => 80000,
        ], $this->auth());

        $response->assertCreated()->assertJsonPath('data.status', 'draft');
        $this->assertDatabaseHas('recruitment_jobs', ['job_code' => 'JOB-001', 'status' => 'draft']);
    }

    public function test_can_publish_job(): void
    {
        $job = Job::factory()->create(['status' => 'draft']);
        $response = $this->postJson("/api/v1/recruitment/jobs/{$job->id}/publish", [], $this->auth());
        $response->assertOk();
        $this->assertDatabaseHas('recruitment_jobs', ['id' => $job->id, 'status' => 'open']);
    }

    public function test_can_close_job(): void
    {
        $job = Job::factory()->open()->create();
        $response = $this->postJson("/api/v1/recruitment/jobs/{$job->id}/close", [], $this->auth());
        $response->assertOk();
        $this->assertDatabaseHas('recruitment_jobs', ['id' => $job->id, 'status' => 'closed']);
    }

    public function test_can_list_jobs(): void
    {
        Job::factory()->count(3)->open()->create();
        $response = $this->getJson('/api/v1/recruitment/jobs', $this->auth());
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_show_job(): void
    {
        $job = Job::factory()->create();
        $response = $this->getJson("/api/v1/recruitment/jobs/{$job->id}", $this->auth());
        $response->assertOk()->assertJson(['success' => true, 'data' => ['id' => $job->id]]);
    }

    // ── Applications ─────────────────────────────────────────────

    public function test_can_submit_application(): void
    {
        $job = Job::factory()->open()->create();

        $response = $this->postJson('/api/v1/recruitment/applications', [
            'job_id' => $job->id,
            'candidate_name' => 'John Smith',
            'candidate_email' => 'john@example.com',
            'expected_salary' => 65000,
        ], $this->auth());

        $response->assertCreated()->assertJsonPath('data.status', 'new');
    }

    public function test_cannot_apply_to_closed_job(): void
    {
        $job = Job::factory()->create(['status' => 'closed']);

        $response = $this->postJson('/api/v1/recruitment/applications', [
            'job_id' => $job->id,
            'candidate_name' => 'John',
            'candidate_email' => 'john@example.com',
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_update_application_status(): void
    {
        $job = Job::factory()->open()->create();
        $app = Application::factory()->create(['job_id' => $job->id, 'status' => 'new']);

        $response = $this->putJson("/api/v1/recruitment/applications/{$app->id}/status", [
            'status' => 'screening',
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('recruitment_applications', ['id' => $app->id, 'status' => 'screening']);
    }

    public function test_auto_fills_job_when_positions_filled(): void
    {
        $job = Job::factory()->open()->create(['positions' => 2]);
        Application::factory()->hired()->create(['job_id' => $job->id]);
        $app = Application::factory()->create(['job_id' => $job->id, 'status' => 'offer']);

        $response = $this->putJson("/api/v1/recruitment/applications/{$app->id}/status", [
            'status' => 'hired',
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('recruitment_jobs', ['id' => $job->id, 'status' => 'filled']);
    }

    // ── Interviews ───────────────────────────────────────────────

    public function test_can_schedule_interview(): void
    {
        $job = Job::factory()->open()->create();
        $app = Application::factory()->create(['job_id' => $job->id, 'status' => 'screening']);
        $interviewer = Employee::factory()->create();

        $response = $this->postJson("/api/v1/recruitment/applications/{$app->id}/interviews", [
            'interview_type' => 'video',
            'scheduled_at' => now()->addDays(3)->toIso8601String(),
            'duration_minutes' => 60,
            'interviewer_id' => $interviewer->id,
        ], $this->auth());

        $response->assertCreated();
        $this->assertDatabaseHas('recruitment_applications', ['id' => $app->id, 'status' => 'interview']);
    }

    public function test_complete_interview_with_high_score_advances_to_offer(): void
    {
        $job = Job::factory()->open()->create();
        $app = Application::factory()->interviewed()->create(['job_id' => $job->id]);
        $interview = Interview::factory()->create(['application_id' => $app->id, 'status' => 'scheduled']);

        $response = $this->putJson("/api/v1/recruitment/interviews/{$interview->id}", [
            'status' => 'completed',
            'score' => 8.5,
            'feedback' => 'Strong candidate',
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('recruitment_applications', ['id' => $app->id, 'status' => 'offer']);
    }

    // ── Pipeline & Dashboard ─────────────────────────────────────

    public function test_can_get_pipeline(): void
    {
        $job = Job::factory()->open()->create();
        Application::factory()->create(['job_id' => $job->id, 'status' => 'new', 'application_number' => 'APP-PIPE-001']);
        Application::factory()->create(['job_id' => $job->id, 'status' => 'new', 'application_number' => 'APP-PIPE-002']);
        Application::factory()->interviewed()->create(['job_id' => $job->id, 'application_number' => 'APP-PIPE-003']);

        $response = $this->getJson('/api/v1/recruitment/pipeline', $this->auth());
        $response->assertOk()->assertJsonStructure(['success', 'data' => ['pipeline', 'total']]);
        $this->assertEquals(3, $response->json('data.total'));
    }

    public function test_can_get_dashboard(): void
    {
        $job = Job::factory()->open()->create();
        Application::factory()->create(['job_id' => $job->id, 'status' => 'new', 'application_number' => 'APP-DASH-001']);

        $response = $this->getJson('/api/v1/recruitment/dashboard', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['open_jobs', 'total_applications', 'new_applications', 'conversion_rate'],
        ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/recruitment/jobs');
        $response->assertUnauthorized();
    }
}
