<?php

namespace Tests\Feature\HR;

use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
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

    public function test_can_create_leave_request(): void
    {
        $employee = Employee::factory()->create();
        $response = $this->postJson('/api/v1/hr/leave-requests', [
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(9)->toDateString(),
            'total_days' => 5,
            'reason' => 'Vacation',
        ], $this->auth());

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseHas('hr_leave_requests', ['employee_id' => $employee->id, 'status' => 'pending']);
    }

    public function test_cannot_create_overlapping_leave(): void
    {
        $employee = Employee::factory()->create();
        LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'status' => 'approved',
        ]);

        $response = $this->postJson('/api/v1/hr/leave-requests', [
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'total_days' => 5,
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_approve_leave(): void
    {
        $employee = Employee::factory()->create();
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/hr/leave-requests/{$leave->id}/approve", [], $this->auth());
        $response->assertOk();
        $this->assertDatabaseHas('hr_leave_requests', ['id' => $leave->id, 'status' => 'approved']);
    }

    public function test_can_reject_leave(): void
    {
        $employee = Employee::factory()->create();
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/hr/leave-requests/{$leave->id}/reject", [
            'reason' => 'Insufficient staffing',
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('hr_leave_requests', ['id' => $leave->id, 'status' => 'rejected']);
    }

    public function test_reject_requires_reason(): void
    {
        $employee = Employee::factory()->create();
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/hr/leave-requests/{$leave->id}/reject", [], $this->auth());
        $response->assertStatus(422);
    }

    public function test_can_cancel_pending_leave(): void
    {
        $employee = Employee::factory()->create();
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/hr/leave-requests/{$leave->id}/cancel", [], $this->auth());
        $response->assertOk();
        $this->assertDatabaseHas('hr_leave_requests', ['id' => $leave->id, 'status' => 'cancelled']);
    }

    public function test_cannot_approve_already_approved(): void
    {
        $employee = Employee::factory()->create();
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'approved',
        ]);

        $response = $this->postJson("/api/v1/hr/leave-requests/{$leave->id}/approve", [], $this->auth());
        $response->assertStatus(422);
    }

    public function test_can_get_balance(): void
    {
        $employee = Employee::factory()->create();
        LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'total_days' => 5,
            'start_date' => now()->startOfYear()->addDays(10),
            'end_date' => now()->startOfYear()->addDays(14),
            'status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/hr/leave-requests/balance?employee_id={$employee->id}", $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['balances', 'total_used', 'total_entitlement'],
        ]);
        $this->assertEquals(5, $response->json('data.balances.annual.used'));
        $this->assertEquals(15, $response->json('data.balances.annual.remaining'));
    }

    public function test_can_get_team_pending(): void
    {
        $employee = Employee::factory()->create();
        LeaveRequest::factory()->count(2)->create([
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]);
        LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/hr/leave-requests/team-pending', $this->auth());
        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_can_get_calendar(): void
    {
        $employee = Employee::factory()->create();
        LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'approved',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(9),
        ]);

        $response = $this->getJson('/api/v1/hr/leave-requests/calendar', $this->auth());
        $response->assertOk()->assertJsonStructure(['success', 'data']);
    }

    public function test_can_list_leave_requests(): void
    {
        LeaveRequest::factory()->count(3)->create();
        $response = $this->getJson('/api/v1/hr/leave-requests', $this->auth());
        $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/hr/leave-requests');
        $response->assertUnauthorized();
    }
}
