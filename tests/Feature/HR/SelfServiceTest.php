<?php

namespace Tests\Feature\HR;

use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use App\Models\HR\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->assignAdminRole();
        $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
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


    public function test_can_get_profile(): void
    {
        $response = $this->getJson('/api/v1/self-service/profile', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['employee', 'stats'],
        ]);
        $this->assertEquals($this->employee->id, $response->json('data.employee.id'));
    }

    public function test_profile_includes_stats(): void
    {
        $response = $this->getJson('/api/v1/self-service/profile', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['stats' => [
                'tenure_years', 'attendance_rate', 'total_hours_worked',
                'total_overtime_hours', 'total_leave_days_used',
                'pending_leave_requests', 'subordinates_count',
            ]],
        ]);
    }

    public function test_profile_includes_recent_payslip(): void
    {
        DB::table('hr_payroll')->insert([
            'payroll_number' => 'PAY-001',
            'employee_id' => $this->employee->id,
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'basic_salary' => 3500,
            'net_salary' => 3500,
            'status' => 'paid',
        ]);

        $response = $this->getJson('/api/v1/self-service/profile', $this->auth());
        $response->assertOk();
        $this->assertNotNull($response->json('data.recent_payslip'));
    }

    public function test_can_get_payslips(): void
    {
        for ($i = 0; $i < 3; $i++) {
            DB::table('hr_payroll')->insert([
                'payroll_number' => "PAY-{$i}",
                'employee_id' => $this->employee->id,
                'period_start' => now()->subMonths($i + 1)->startOfMonth()->toDateString(),
                'period_end' => now()->subMonths($i + 1)->endOfMonth()->toDateString(),
                'basic_salary' => 3500,
                'net_salary' => 3000,
                'status' => 'paid',
            ]);
        }

        $response = $this->getJson('/api/v1/self-service/payslips', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['payslips', 'total_paid'],
            'meta',
        ]);
        $this->assertCount(3, $response->json('data.payslips'));
    }

    public function test_payslips_only_show_own(): void
    {
        $otherEmployee = Employee::factory()->create();
        DB::table('hr_payroll')->insert([
            'payroll_number' => 'PAY-001',
            'employee_id' => $this->employee->id,
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'basic_salary' => 3500,
            'net_salary' => 3000,
            'status' => 'paid',
        ]);
        DB::table('hr_payroll')->insert([
            'payroll_number' => 'PAY-002',
            'employee_id' => $otherEmployee->id,
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'basic_salary' => 4000,
            'net_salary' => 3500,
            'status' => 'paid',
        ]);

        $response = $this->getJson('/api/v1/self-service/payslips', $this->auth());
        $response->assertOk()->assertJsonCount(1, 'data.payslips');
    }

    public function test_can_get_leave_balance(): void
    {
        $response = $this->getJson('/api/v1/self-service/leave-balance', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['balances', 'total_used', 'total_entitlement', 'recent_requests', 'upcoming_leave'],
        ]);
        $this->assertEquals(20, $response->json('data.balances.annual.entitlement'));
        $this->assertEquals(20, $response->json('data.balances.annual.remaining'));
    }

    public function test_leave_balance_shows_used(): void
    {
        LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'total_days' => 5,
            'status' => 'approved',
            'start_date' => now()->startOfYear()->addDays(10),
            'end_date' => now()->startOfYear()->addDays(14),
        ]);

        $response = $this->getJson('/api/v1/self-service/leave-balance', $this->auth());
        $response->assertOk();
        $this->assertEquals(5, $response->json('data.balances.annual.used'));
        $this->assertEquals(15, $response->json('data.balances.annual.remaining'));
    }

    public function test_can_get_attendance(): void
    {
        DB::table('hr_attendance')->insert([
            'employee_id' => $this->employee->id,
            'date' => now()->subDay()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '17:30:00',
            'total_hours' => 8.5,
            'regular_hours' => 8,
            'overtime_hours' => 0.5,
            'status' => 'present',
        ]);

        $response = $this->getJson('/api/v1/self-service/attendance', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['today', 'summary', 'records'],
        ]);
        $this->assertEquals(8.5, $response->json('data.summary.total_hours'));
        $this->assertEquals(0.5, $response->json('data.summary.overtime_hours'));
    }

    public function test_attendance_only_shows_own_records(): void
    {
        $otherEmployee = Employee::factory()->create();
        DB::table('hr_attendance')->insert([
            'employee_id' => $this->employee->id,
            'date' => now()->subDay()->toDateString(),
            'clock_in' => '09:00:00',
            'total_hours' => 8,
            'status' => 'present',
        ]);
        DB::table('hr_attendance')->insert([
            'employee_id' => $otherEmployee->id,
            'date' => now()->subDay()->toDateString(),
            'clock_in' => '09:00:00',
            'total_hours' => 8,
            'status' => 'present',
        ]);

        $response = $this->getJson('/api/v1/self-service/attendance', $this->auth());
        $response->assertOk()->assertJsonCount(1, 'data.records');
    }

    public function test_can_submit_leave_request(): void
    {
        $response = $this->postJson('/api/v1/self-service/leave', [
            'leave_type' => 'annual',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'total_days' => 5,
            'reason' => 'Vacation',
        ], $this->auth());

        $response->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseHas('hr_leave_requests', [
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);
    }

    public function test_can_cancel_own_leave(): void
    {
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/self-service/leave/{$leave->id}/cancel", [], $this->auth());
        $response->assertOk();
        $this->assertDatabaseHas('hr_leave_requests', ['id' => $leave->id, 'status' => 'cancelled']);
    }

    public function test_cannot_cancel_others_leave(): void
    {
        $otherEmployee = Employee::factory()->create();
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $otherEmployee->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/self-service/leave/{$leave->id}/cancel", [], $this->auth());
        $response->assertNotFound();
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/self-service/profile');
        $response->assertUnauthorized();
    }

    public function test_no_employee_profile_returns_404(): void
    {
        $user2 = User::factory()->create();
        $token2 = $user2->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/self-service/profile', [
            'Authorization' => "Bearer {$token2}",
        ]);
        $response->assertStatus(404);
    }
}
