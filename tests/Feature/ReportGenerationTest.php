<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ReportGenerationJob;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
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
            'name' => 'admin', 'display_name' => 'Admin', 'description' => 'Admin',
            'is_system' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $adminId = DB::table('roles')->where('name', 'admin')->value('id');
        DB::table('user_roles')->insert([
            'user_id' => $this->user->id, 'role_id' => $adminId,
            'assigned_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_can_queue_report_generation(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/reports/generate', [
            'report_type' => 'trial_balance',
            'format'      => 'json',
        ], $this->auth());

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['report_id', 'status', 'message']])
            ->assertJsonPath('data.status', 'queued');

        Queue::assertPushed(ReportGenerationJob::class);

        $this->assertDatabaseHas('generated_reports', [
            'user_id'     => $this->user->id,
            'report_type' => 'trial_balance',
            'status'      => 'queued',
        ]);
    }

    public function test_generate_requires_valid_report_type(): void
    {
        $response = $this->postJson('/api/v1/reports/generate', [
            'report_type' => 'invalid_type',
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_poll_report_status(): void
    {
        $reportId = DB::table('generated_reports')->insertGetId([
            'user_id'     => $this->user->id,
            'report_type' => 'balance_sheet',
            'parameters'  => json_encode([]),
            'format'      => 'json',
            'status'      => 'completed',
            'result_data' => json_encode(['report' => 'balance_sheet', 'rows' => []]),
            'expires_at'  => now()->addDays(7),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->getJson("/api/v1/reports/{$reportId}", $this->auth());

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.report_type', 'balance_sheet');
    }

    public function test_cannot_see_another_users_report(): void
    {
        $other = User::factory()->create();
        $reportId = DB::table('generated_reports')->insertGetId([
            'user_id'     => $other->id,
            'report_type' => 'trial_balance',
            'parameters'  => json_encode([]),
            'format'      => 'json',
            'status'      => 'completed',
            'result_data' => json_encode(['rows' => []]),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->getJson("/api/v1/reports/{$reportId}", $this->auth());
        $response->assertNotFound();
    }

    public function test_can_download_completed_report_as_csv(): void
    {
        $rows = [
            ['account_code' => '1000', 'account_name' => 'Cash', 'balance' => 5000.00],
            ['account_code' => '2000', 'account_name' => 'Accounts Payable', 'balance' => -1200.00],
        ];

        $reportId = DB::table('generated_reports')->insertGetId([
            'user_id'     => $this->user->id,
            'report_type' => 'trial_balance',
            'parameters'  => json_encode([]),
            'format'      => 'csv',
            'status'      => 'completed',
            'result_data' => json_encode(['report' => 'trial_balance', 'rows' => $rows]),
            'expires_at'  => now()->addDays(7),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->get("/api/v1/reports/{$reportId}/download", $this->auth());

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_download_pending_report_returns_422(): void
    {
        $reportId = DB::table('generated_reports')->insertGetId([
            'user_id'     => $this->user->id,
            'report_type' => 'trial_balance',
            'parameters'  => json_encode([]),
            'format'      => 'json',
            'status'      => 'queued',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->getJson("/api/v1/reports/{$reportId}/download", $this->auth());
        $response->assertStatus(422);
    }

    public function test_can_create_scheduled_report(): void
    {
        $response = $this->postJson('/api/v1/reports/scheduled', [
            'name'        => 'Weekly P&L',
            'report_type' => 'income_statement',
            'frequency'   => 'weekly',
            'format'      => 'csv',
        ], $this->auth());

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'next_run_at']]);

        $this->assertDatabaseHas('scheduled_reports', [
            'user_id'     => $this->user->id,
            'report_type' => 'income_statement',
            'frequency'   => 'weekly',
        ]);
    }

    public function test_can_list_scheduled_reports(): void
    {
        DB::table('scheduled_reports')->insert([
            'user_id'     => $this->user->id,
            'name'        => 'Daily TB',
            'report_type' => 'trial_balance',
            'parameters'  => json_encode([]),
            'format'      => 'json',
            'frequency'   => 'daily',
            'is_active'   => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->getJson('/api/v1/reports/scheduled', $this->auth());

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_delete_scheduled_report(): void
    {
        $id = DB::table('scheduled_reports')->insertGetId([
            'user_id'     => $this->user->id,
            'name'        => 'To Delete',
            'report_type' => 'trial_balance',
            'parameters'  => json_encode([]),
            'format'      => 'json',
            'frequency'   => 'daily',
            'is_active'   => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->deleteJson("/api/v1/reports/scheduled/{$id}", [], $this->auth());
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('scheduled_reports', ['id' => $id]);
    }
}
