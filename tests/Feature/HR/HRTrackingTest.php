<?php

namespace Tests\Feature\HR;

use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HRTrackingTest extends TestCase
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


    public function test_dashboard_returns_all_sections(): void
    {
        Employee::factory()->count(5)->create(['status' => 'active']);
        Department::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/hr/tracking/dashboard', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['summary', 'attendance', 'leave', 'department_breakdown', 'employment_type_breakdown', 'monthly_hires'],
        ]);
    }

    public function test_dashboard_calculates_turnover_rate(): void
    {
        Employee::factory()->count(10)->create(['status' => 'active']);
        Employee::factory()->count(2)->create([
            'status' => 'terminated',
            'termination_date' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->getJson('/api/v1/hr/tracking/dashboard', $this->auth());
        $response->assertOk();
        $this->assertGreaterThan(0, $response->json('data.summary.turnover_rate'));
    }

    public function test_attendance_report_returns_stats(): void
    {
        $employee = Employee::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            DB::table('hr_attendance')->insert([
                'employee_id' => $employee->id,
                'date' => now()->subDays($i)->toDateString(),
                'status' => 'present',
            ]);
        }

        $response = $this->getJson('/api/v1/hr/tracking/attendance-report', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['overall_rate', 'daily_stats', 'by_employee'],
        ]);
    }

    public function test_leave_report_returns_breakdowns(): void
    {
        $employee = Employee::factory()->create();
        for ($i = 0; $i < 3; $i++) {
            DB::table('hr_leave_requests')->insert([
                'employee_id' => $employee->id,
                'leave_type' => 'annual',
                'start_date' => now()->addDays($i * 10)->toDateString(),
                'end_date' => now()->addDays($i * 10 + 5)->toDateString(),
                'total_days' => 5,
                'status' => 'approved',
            ]);
        }

        $response = $this->getJson('/api/v1/hr/tracking/leave-report', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['by_status', 'by_type', 'by_department', 'monthly_trend'],
        ]);
    }

    public function test_payroll_report_returns_summary(): void
    {
        $response = $this->getJson('/api/v1/hr/tracking/payroll-report', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['summary', 'by_status', 'monthly_trend', 'top_earners'],
        ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/hr/tracking/dashboard');
        $response->assertUnauthorized();
    }
}
