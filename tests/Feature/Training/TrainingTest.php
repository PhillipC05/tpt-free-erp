<?php

namespace Tests\Feature\Training;

use App\Models\HR\Employee;
use App\Models\Training\Certification;
use App\Models\Training\Program;
use App\Models\Training\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrainingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;

        DB::table('roles')->insertOrIgnore([
            'name' => 'admin', 'display_name' => 'Admin', 'description' => 'System administrator',
        ]);
        $adminId = DB::table('roles')->where('name', 'admin')->value('id');
        DB::table('user_roles')->insert([
            'user_id' => $this->user->id, 'role_id' => $adminId,
        ]);
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_create_program(): void
    {
        $response = $this->postJson('/api/v1/training/programs', [
            'code' => 'TRN-001',
            'name' => 'Safety Induction',
            'type' => 'safety',
            'duration_hours' => 4,
            'is_mandatory' => true,
        ], $this->auth());

        $response->assertCreated()->assertJsonPath('data.code', 'TRN-001');
        $this->assertDatabaseHas('training_programs', ['code' => 'TRN-001']);
    }

    public function test_can_list_programs(): void
    {
        Program::factory()->count(3)->create();
        $response = $this->getJson('/api/v1/training/programs', $this->auth());
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_create_session(): void
    {
        $program = Program::factory()->create();
        $response = $this->postJson('/api/v1/training/sessions', [
            'program_id' => $program->id,
            'title' => 'Safety Workshop',
            'starts_at' => now()->addDays(5)->toIso8601String(),
        ], $this->auth());

        $response->assertCreated()->assertJsonPath('data.status', 'scheduled');
    }

    public function test_can_enroll_employee(): void
    {
        $session = Session::factory()->create(['max_participants' => 10]);
        $employee = Employee::factory()->create();

        $response = $this->postJson("/api/v1/training/sessions/{$session->id}/enroll", [
            'employee_id' => $employee->id,
        ], $this->auth());

        $response->assertCreated();
        $this->assertDatabaseHas('training_enrollments', [
            'session_id' => $session->id,
            'employee_id' => $employee->id,
            'status' => 'enrolled',
        ]);
    }

    public function test_cannot_enroll_twice(): void
    {
        $session = Session::factory()->create();
        $employee = Employee::factory()->create();

        $this->postJson("/api/v1/training/sessions/{$session->id}/enroll", [
            'employee_id' => $employee->id,
        ], $this->auth());

        $response = $this->postJson("/api/v1/training/sessions/{$session->id}/enroll", [
            'employee_id' => $employee->id,
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_complete_session(): void
    {
        $session = Session::factory()->create(['status' => 'in_progress']);
        $response = $this->postJson("/api/v1/training/sessions/{$session->id}/complete", [], $this->auth());
        $response->assertOk();
        $this->assertDatabaseHas('training_sessions', ['id' => $session->id, 'status' => 'completed']);
    }

    public function test_can_record_certification(): void
    {
        $employee = Employee::factory()->create();
        $response = $this->postJson('/api/v1/training/certifications', [
            'employee_id' => $employee->id,
            'certification_name' => 'OSHA 30',
            'issued_date' => now()->toDateString(),
            'expiry_date' => now()->addYears(2)->toDateString(),
        ], $this->auth());

        $response->assertCreated()->assertJsonPath('data.status', 'active');
    }

    public function test_can_renew_certification(): void
    {
        $employee = Employee::factory()->create();
        $cert = Certification::factory()->create([
            'employee_id' => $employee->id,
            'expiry_date' => now()->addMonth(),
        ]);
        $response = $this->postJson("/api/v1/training/certifications/{$cert->id}/renew", [], $this->auth());
        $response->assertOk();
        $this->assertDatabaseHas('training_certifications', ['id' => $cert->id, 'status' => 'active']);
    }

    public function test_can_get_dashboard(): void
    {
        Program::factory()->count(2)->create();
        Session::factory()->create();
        $employee = Employee::factory()->create();
        Certification::factory()->create(['employee_id' => $employee->id]);

        $response = $this->getJson('/api/v1/training/dashboard', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['total_programs', 'total_sessions', 'total_enrollments', 'active_certifications', 'programs_by_type'],
        ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/training/programs');
        $response->assertUnauthorized();
    }
}
