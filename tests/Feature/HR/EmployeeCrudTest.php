<?php

namespace Tests\Feature\HR;

use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeCrudTest extends TestCase
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


    public function test_can_list_employees(): void
    {
        Employee::factory()->count(3)->create();
        $response = $this->getJson('/api/v1/hr/employees', $this->auth());
        $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    }

    public function test_can_create_employee(): void
    {
        $response = $this->postJson('/api/v1/hr/employees', [
            'employee_code' => 'EMP-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'hire_date' => '2026-01-15',
            'employment_type' => 'full_time',
        ], $this->auth());

        $response->assertCreated()->assertJsonPath('data.employee_code', 'EMP-001');
        $this->assertDatabaseHas('hr_employees', ['employee_code' => 'EMP-001']);
    }

    public function test_create_requires_fields(): void
    {
        $response = $this->postJson('/api/v1/hr/employees', [], $this->auth());
        $response->assertStatus(422);
    }

    public function test_can_update_employee(): void
    {
        $emp = Employee::factory()->create(['employee_code' => 'EMP-001']);
        $response = $this->putJson("/api/v1/hr/employees/{$emp->id}", [
            'employee_code' => 'EMP-001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $emp->email,
            'hire_date' => $emp->hire_date->toDateString(),
            'employment_type' => $emp->employment_type,
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('hr_employees', ['id' => $emp->id, 'first_name' => 'Jane']);
    }

    public function test_can_show_employee(): void
    {
        $emp = Employee::factory()->create();
        $response = $this->getJson("/api/v1/hr/employees/{$emp->id}", $this->auth());
        $response->assertOk()->assertJson(['success' => true, 'data' => ['id' => $emp->id]]);
    }

    public function test_can_get_profile(): void
    {
        $emp = Employee::factory()->create();
        $response = $this->getJson("/api/v1/hr/employees/{$emp->id}/profile", $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['employee', 'stats'],
        ]);
    }

    public function test_profile_includes_stats(): void
    {
        $emp = Employee::factory()->create();
        $response = $this->getJson("/api/v1/hr/employees/{$emp->id}/profile", $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['stats' => ['tenure_years', 'attendance_rate', 'total_leave_days', 'pending_leave', 'subordinates_count']],
        ]);
    }

    public function test_can_delete_employee(): void
    {
        $emp = Employee::factory()->create();
        $response = $this->deleteJson("/api/v1/hr/employees/{$emp->id}", [], $this->auth());
        $response->assertOk();
        $this->assertSoftDeleted('hr_employees', ['id' => $emp->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/hr/employees');
        $response->assertUnauthorized();
    }

    public function test_can_search_employees(): void
    {
        Employee::factory()->create(['first_name' => 'John', 'last_name' => 'Smith']);
        Employee::factory()->create(['first_name' => 'Jane', 'last_name' => 'Jones']);

        $response = $this->getJson('/api/v1/hr/employees?search=John', $this->auth());
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_status(): void
    {
        Employee::factory()->count(2)->create(['status' => 'active']);
        Employee::factory()->create(['status' => 'terminated']);

        $response = $this->getJson('/api/v1/hr/employees?status=active', $this->auth());
        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
