<?php

namespace Tests\Feature\HR;

use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    private string $token;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = User::factory()->create()->createToken('test')->plainTextToken;
        $this->employee = Employee::factory()->create();
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'total_days' => 5,
            'reason' => 'Vacation',
        ], $overrides);
    }

    public function test_can_create_leave_request(): void
    {
        $response = $this->postJson('/api/hr/leave-requests', $this->validPayload(), $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('hr_leave_requests', [
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        $response = $this->postJson('/api/hr/leave-requests', $this->validPayload([
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-05',
        ]), $this->auth());

        $response->assertStatus(422);
    }

    public function test_employee_must_exist(): void
    {
        $response = $this->postJson('/api/hr/leave-requests', $this->validPayload([
            'employee_id' => 99999,
        ]), $this->auth());

        $response->assertStatus(422);
    }

    public function test_leave_type_must_be_valid(): void
    {
        $response = $this->postJson('/api/hr/leave-requests', $this->validPayload([
            'leave_type' => 'unknown',
        ]), $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_list_leave_requests(): void
    {
        LeaveRequest::factory()->count(3)->create(['employee_id' => $this->employee->id]);

        $response = $this->getJson('/api/hr/leave-requests', $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_can_filter_leave_requests_by_employee(): void
    {
        $other = Employee::factory()->create();
        LeaveRequest::factory()->count(2)->create(['employee_id' => $this->employee->id]);
        LeaveRequest::factory()->create(['employee_id' => $other->id]);

        $response = $this->getJson("/api/hr/leave-requests?employee_id={$this->employee->id}", $this->auth());

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/hr/leave-requests');

        $response->assertUnauthorized();
    }
}
