<?php

namespace Tests\Feature\HR;

use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceTest extends TestCase
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


    public function test_can_clock_in(): void
    {
        $employee = Employee::factory()->create();
        $response = $this->postJson('/api/v1/hr/attendance/clock-in', [
            'employee_id' => $employee->id,
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('hr_attendance', [
            'employee_id' => $employee->id,
            'status' => 'present',
        ]);
    }

    public function test_cannot_clock_in_twice(): void
    {
        $employee = Employee::factory()->create();
        $this->postJson('/api/v1/hr/attendance/clock-in', ['employee_id' => $employee->id], $this->auth());
        $response = $this->postJson('/api/v1/hr/attendance/clock-in', ['employee_id' => $employee->id], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_clock_out(): void
    {
        $employee = Employee::factory()->create();
        $this->postJson('/api/v1/hr/attendance/clock-in', ['employee_id' => $employee->id], $this->auth());

        $response = $this->postJson('/api/v1/hr/attendance/clock-out', [
            'employee_id' => $employee->id,
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $record = Attendance::where('employee_id', $employee->id)->whereDate('date', today())->first();
        $this->assertNotNull($record->clock_out);
        $this->assertNotNull($record->total_hours);
    }

    public function test_cannot_clock_out_without_clock_in(): void
    {
        $employee = Employee::factory()->create();
        $response = $this->postJson('/api/v1/hr/attendance/clock-out', [
            'employee_id' => $employee->id,
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_get_daily_log(): void
    {
        $employee = Employee::factory()->create(['status' => 'active']);
        DB::table('hr_attendance')->insert([
            'employee_id' => $employee->id,
            'date' => today()->toDateString(),
            'clock_in' => '09:00:00',
            'status' => 'present',
        ]);

        $response = $this->getJson('/api/v1/hr/attendance/daily-log', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['date', 'summary', 'records'],
        ]);
    }

    public function test_daily_log_shows_absent_employees(): void
    {
        Employee::factory()->create(['status' => 'active']);
        $response = $this->getJson('/api/v1/hr/attendance/daily-log', $this->auth());

        $response->assertOk();
        $records = $response->json('data.records');
        $this->assertCount(1, $records);
        $this->assertEquals('absent', $records[0]['status']);
    }

    public function test_can_get_today_status(): void
    {
        $employee = Employee::factory()->create(['status' => 'active']);
        $this->postJson('/api/v1/hr/attendance/clock-in', ['employee_id' => $employee->id], $this->auth());

        $response = $this->getJson('/api/v1/hr/attendance/today-status', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['present', 'late', 'absent', 'total', 'attendance_rate'],
        ]);
        $this->assertEquals(1, $response->json('data.present'));
    }

    public function test_can_mark_absent(): void
    {
        $emp1 = Employee::factory()->create(['status' => 'active']);
        $emp2 = Employee::factory()->create(['status' => 'active']);

        $response = $this->postJson('/api/v1/hr/attendance/mark-absent', [
            'date' => now()->subDay()->toDateString(),
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('hr_attendance', ['employee_id' => $emp1->id, 'status' => 'absent']);
        $this->assertDatabaseHas('hr_attendance', ['employee_id' => $emp2->id, 'status' => 'absent']);
    }

    public function test_can_get_summary(): void
    {
        $employee = Employee::factory()->create();
        DB::table('hr_attendance')->insert([
            'employee_id' => $employee->id,
            'date' => now()->subDays(3)->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
            'total_hours' => 8,
            'status' => 'present',
        ]);

        $response = $this->getJson('/api/v1/hr/attendance/summary', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['total_days', 'present', 'attendance_rate', 'total_hours', 'total_overtime', 'total_regular'],
        ]);
    }

    public function test_clock_out_calculates_overtime(): void
    {
        $employee = Employee::factory()->create();
        $this->postJson('/api/v1/hr/attendance/clock-in', ['employee_id' => $employee->id], $this->auth());

        $record = Attendance::where('employee_id', $employee->id)->whereDate('date', today())->first();
        $record->update(['clock_in' => '08:00:00']);

        \Carbon\Carbon::setTestNow(now()->setTime(19, 0, 0));
        $response = $this->postJson('/api/v1/hr/attendance/clock-out', [
            'employee_id' => $employee->id,
        ], $this->auth());
        \Carbon\Carbon::setTestNow();

        $response->assertOk();
        $record->refresh();
        $this->assertEqualsWithDelta(11.0, (float) $record->total_hours, 0.5);
        $this->assertEquals(8.00, (float) $record->regular_hours);
        $this->assertEqualsWithDelta(3.0, (float) $record->overtime_hours, 0.5);
    }

    public function test_clock_out_no_overtime_under_8_hours(): void
    {
        $employee = Employee::factory()->create();
        $this->postJson('/api/v1/hr/attendance/clock-in', ['employee_id' => $employee->id], $this->auth());

        $record = Attendance::where('employee_id', $employee->id)->whereDate('date', today())->first();
        $record->update(['clock_in' => '10:00:00']);

        \Carbon\Carbon::setTestNow(now()->setTime(15, 0, 0));
        $response = $this->postJson('/api/v1/hr/attendance/clock-out', [
            'employee_id' => $employee->id,
        ], $this->auth());
        \Carbon\Carbon::setTestNow();

        $response->assertOk();
        $record->refresh();
        $this->assertEquals(0, (float) $record->overtime_hours);
        $this->assertEqualsWithDelta(5.0, (float) $record->total_hours, 0.5);
    }

    public function test_can_get_overtime_report(): void
    {
        $employee = Employee::factory()->create();
        DB::table('hr_attendance')->insert([
            'employee_id' => $employee->id,
            'date' => now()->subDays(2)->toDateString(),
            'clock_in' => '08:00:00',
            'clock_out' => '19:00:00',
            'total_hours' => 11,
            'regular_hours' => 8,
            'overtime_hours' => 3,
            'status' => 'present',
        ]);

        $response = $this->getJson('/api/v1/hr/attendance/overtime-report', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['total_overtime_hours', 'total_overtime_days', 'by_employee', 'daily_overtime'],
        ]);
        $this->assertEquals(3, $response->json('data.total_overtime_hours'));
    }

    public function test_overtime_report_filters_by_date(): void
    {
        $employee = Employee::factory()->create();
        DB::table('hr_attendance')->insert([
            'employee_id' => $employee->id,
            'date' => now()->subDays(5)->toDateString(),
            'clock_in' => '08:00:00',
            'clock_out' => '20:00:00',
            'total_hours' => 12,
            'regular_hours' => 8,
            'overtime_hours' => 4,
            'status' => 'present',
        ]);
        DB::table('hr_attendance')->insert([
            'employee_id' => $employee->id,
            'date' => now()->subDays(30)->toDateString(),
            'clock_in' => '08:00:00',
            'clock_out' => '19:00:00',
            'total_hours' => 11,
            'regular_hours' => 8,
            'overtime_hours' => 3,
            'status' => 'present',
        ]);

        $response = $this->getJson('/api/v1/hr/attendance/overtime-report?'.http_build_query([
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->toDateString(),
        ]), $this->auth());

        $response->assertOk();
        $this->assertEquals(4, $response->json('data.total_overtime_hours'));
        $this->assertEquals(1, $response->json('data.total_overtime_days'));
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/hr/attendance');
        $response->assertUnauthorized();
    }
}
