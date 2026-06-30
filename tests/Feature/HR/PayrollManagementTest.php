<?php

namespace Tests\Feature\HR;

use App\Models\HR\Employee;
use App\Models\HR\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayrollManagementTest extends TestCase
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

    private function createPayroll(array $overrides = []): Payroll
    {
        $employee = Employee::factory()->create();

        return Payroll::create(array_merge([
            'payroll_number' => 'PAY-'.fake()->unique()->numerify('######'),
            'employee_id' => $employee->id,
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'basic_salary' => 3000,
            'net_salary' => 2500,
            'status' => 'draft',
        ], $overrides));
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

    public function test_can_create_payroll(): void
    {
        $employee = Employee::factory()->create();
        $response = $this->postJson('/api/v1/hr/payroll', [
            'payroll_number' => 'PAY-001',
            'employee_id' => $employee->id,
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'basic_salary' => 3000,
            'net_salary' => 2500,
        ], $this->auth());

        $response->assertCreated()->assertJsonPath('data.status', 'draft');
        $this->assertDatabaseHas('hr_payroll', ['payroll_number' => 'PAY-001', 'status' => 'draft']);
    }

    public function test_can_process_payroll(): void
    {
        $payroll = $this->createPayroll();
        $response = $this->postJson("/api/v1/hr/payroll/{$payroll->id}/process", [], $this->auth());
        $response->assertOk();
        $this->assertDatabaseHas('hr_payroll', ['id' => $payroll->id, 'status' => 'approved']);
    }

    public function test_can_approve_payroll(): void
    {
        $payroll = $this->createPayroll();
        $response = $this->postJson("/api/v1/hr/payroll/{$payroll->id}/approve", [], $this->auth());
        $response->assertOk();
        $this->assertDatabaseHas('hr_payroll', ['id' => $payroll->id, 'status' => 'approved']);
    }

    public function test_can_mark_paid(): void
    {
        $payroll = $this->createPayroll(['status' => 'approved']);
        $response = $this->postJson("/api/v1/hr/payroll/{$payroll->id}/mark-paid", [
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('hr_payroll', ['id' => $payroll->id, 'status' => 'paid']);
    }

    public function test_cannot_process_paid_payroll(): void
    {
        $payroll = $this->createPayroll(['status' => 'paid']);
        $response = $this->postJson("/api/v1/hr/payroll/{$payroll->id}/process", [], $this->auth());
        $response->assertStatus(422);
    }

    public function test_can_batch_generate(): void
    {
        $emp1 = Employee::factory()->create(['salary' => 3000, 'status' => 'active']);
        $emp2 = Employee::factory()->create(['salary' => 4000, 'status' => 'active']);

        $response = $this->postJson('/api/v1/hr/payroll/batch-generate', [
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
        ], $this->auth());

        $response->assertOk();
        $this->assertDatabaseHas('hr_payroll', ['employee_id' => $emp1->id, 'status' => 'draft']);
        $this->assertDatabaseHas('hr_payroll', ['employee_id' => $emp2->id, 'status' => 'draft']);
    }

    public function test_batch_generate_skips_existing(): void
    {
        $employee = Employee::factory()->create(['salary' => 3000, 'status' => 'active']);
        $periodStart = now()->subMonth()->startOfMonth()->toDateString();
        $periodEnd = now()->subMonth()->endOfMonth()->toDateString();

        DB::table('hr_payroll')->insert([
            'payroll_number' => 'PAY-SKIP-TEST',
            'employee_id' => $employee->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'basic_salary' => 3000,
            'net_salary' => 2500,
            'status' => 'draft',
        ]);

        $response = $this->postJson('/api/v1/hr/payroll/batch-generate', [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ], $this->auth());

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.skipped'));
    }

    public function test_can_get_summary(): void
    {
        $this->createPayroll(['status' => 'paid', 'basic_salary' => 3000, 'net_salary' => 2500]);
        $this->createPayroll(['status' => 'draft', 'basic_salary' => 4000, 'net_salary' => 3500]);

        $response = $this->getJson('/api/v1/hr/payroll/summary', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['total_employees', 'paid', 'pending', 'total_net_salary', 'by_department'],
        ]);
        $this->assertEquals(2, $response->json('data.total_employees'));
        $this->assertEquals(1, $response->json('data.paid'));
        $this->assertEquals(1, $response->json('data.pending'));
    }

    public function test_can_show_payroll(): void
    {
        $payroll = $this->createPayroll();
        $response = $this->getJson("/api/v1/hr/payroll/{$payroll->id}", $this->auth());
        $response->assertOk()->assertJsonStructure(['success', 'data' => ['gross_pay', 'net_pay']]);
    }

    public function test_can_list_payrolls(): void
    {
        $this->createPayroll();
        $response = $this->getJson('/api/v1/hr/payroll', $this->auth());
        $response->assertOk()->assertJsonStructure(['success', 'data', 'meta']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/hr/payroll');
        $response->assertUnauthorized();
    }
}
