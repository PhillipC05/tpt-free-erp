<?php

namespace Tests\Feature\HR;

use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private string $token;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = User::factory()->create()->createToken('test')->plainTextToken;
        $this->department = Department::factory()->create();
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'employee_code' => 'EMP-0001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@company.com',
            'hire_date' => '2024-01-15',
            'employment_type' => 'full_time',
            'status' => 'active',
        ], $overrides);
    }

    public function test_can_list_employees(): void
    {
        Employee::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/hr/employees', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_employee(): void
    {
        $response = $this->postJson('/api/v1/hr/employees', $this->validPayload(), $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('hr_employees', ['employee_code' => 'EMP-0001']);
    }

    public function test_employee_code_must_be_unique(): void
    {
        Employee::factory()->create(['employee_code' => 'EMP-0001']);

        $response = $this->postJson('/api/v1/hr/employees', $this->validPayload(), $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->getJson("/api/v1/hr/employees/{$employee->id}", $this->auth());

        $response->assertOk()->assertJson(['success' => true, 'data' => ['id' => $employee->id]]);
    }

    public function test_show_nonexistent_employee_returns_404(): void
    {
        $response = $this->getJson('/api/v1/hr/employees/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_employee(): void
    {
        $employee = Employee::factory()->create(['employee_code' => 'EMP-0001']);

        $response = $this->putJson("/api/v1/hr/employees/{$employee->id}", $this->validPayload([
            'first_name' => 'Jane',
        ]), $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('hr_employees', ['id' => $employee->id, 'first_name' => 'Jane']);
    }

    public function test_can_soft_delete_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->deleteJson("/api/v1/hr/employees/{$employee->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('hr_employees', ['id' => $employee->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/hr/employees');

        $response->assertUnauthorized();
    }
}
