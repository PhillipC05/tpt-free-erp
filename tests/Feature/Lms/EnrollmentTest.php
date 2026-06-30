<?php

namespace Tests\Feature\Lms;

use App\Models\HR\Employee;
use App\Models\Lms\Course;
use App\Models\Lms\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnrollmentTest extends TestCase
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

    public function test_can_list_enrollments(): void
    {
        Enrollment::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/lms/enrollments', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_enrollment(): void
    {
        $course = Course::factory()->create();
        $employee = Employee::factory()->create();

        $response = $this->postJson('/api/v1/lms/enrollments', [
            'course_id' => $course->id,
            'employee_id' => $employee->id,
            'enrollment_date' => '2026-05-31',
            'status' => 'enrolled',
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('lms_enrollments', [
            'course_id' => $course->id,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_create_enrollment_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/v1/lms/enrollments', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_show_enrollment(): void
    {
        $enrollment = Enrollment::factory()->create();

        $response = $this->getJson("/api/v1/lms/enrollments/{$enrollment->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $enrollment->id]]);
    }

    public function test_show_nonexistent_enrollment_returns_404(): void
    {
        $response = $this->getJson('/api/v1/lms/enrollments/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_enroll_via_course_endpoint(): void
    {
        $course = Course::factory()->create();
        $employee = Employee::factory()->create();

        $response = $this->postJson("/api/v1/lms/courses/{$course->id}/enroll", [
            'employee_id' => $employee->id,
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('lms_enrollments', [
            'course_id' => $course->id,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_can_mark_enrollment_complete(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => 'in_progress']);

        $response = $this->putJson("/api/v1/lms/enrollments/{$enrollment->id}/complete", [
            'score' => 88.5,
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('lms_enrollments', ['id' => $enrollment->id, 'status' => 'completed']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/lms/enrollments');

        $response->assertUnauthorized();
    }
}
