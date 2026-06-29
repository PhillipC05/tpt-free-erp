<?php

namespace Tests\Feature\HR;

use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DirectoryTest extends TestCase
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


    public function test_can_list_directory(): void
    {
        Employee::factory()->count(5)->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/hr/directory', $this->auth());
        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJsonCount(5, 'data');
    }

    public function test_directory_filters_by_department(): void
    {
        $dept = Department::factory()->create();
        Employee::factory()->count(2)->create(['department_id' => $dept->id, 'status' => 'active']);
        Employee::factory()->create(['status' => 'active']);

        $response = $this->getJson("/api/v1/hr/directory?department_id={$dept->id}", $this->auth());
        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_directory_search(): void
    {
        Employee::factory()->create(['first_name' => 'John', 'last_name' => 'Smith', 'status' => 'active']);
        Employee::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe', 'status' => 'active']);

        $response = $this->getJson('/api/v1/hr/directory?search=John', $this->auth());
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_directory_excludes_inactive(): void
    {
        Employee::factory()->create(['status' => 'active']);
        Employee::factory()->create(['status' => 'terminated']);

        $response = $this->getJson('/api/v1/hr/directory', $this->auth());
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_get_org_chart(): void
    {
        $ceo = Employee::factory()->create(['status' => 'active', 'manager_id' => null]);
        $vp = Employee::factory()->create(['status' => 'active', 'manager_id' => $ceo->id]);
        Employee::factory()->create(['status' => 'active', 'manager_id' => $vp->id]);

        $response = $this->getJson('/api/v1/hr/directory/org-chart', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data',
        ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    public function test_org_chart_shows_subordinates(): void
    {
        $ceo = Employee::factory()->create(['status' => 'active', 'manager_id' => null]);
        $vp = Employee::factory()->create(['status' => 'active', 'manager_id' => $ceo->id]);

        $response = $this->getJson('/api/v1/hr/directory/org-chart', $this->auth());
        $response->assertOk();

        $chart = $response->json('data');
        $this->assertCount(1, $chart);
        $this->assertCount(1, $chart[0]['children']);
    }

    public function test_can_get_full_org_chart(): void
    {
        Employee::factory()->count(5)->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/hr/directory/org-chart-full', $this->auth());
        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => ['chart', 'stats'],
        ]);

        $this->assertEquals(5, $response->json('data.stats.total_employees'));
    }

    public function test_can_get_directory_profile(): void
    {
        $emp = Employee::factory()->create(['status' => 'active']);

        $response = $this->getJson("/api/v1/hr/directory/{$emp->id}", $this->auth());
        $response->assertOk()->assertJson([
            'success' => true,
            'data' => ['id' => $emp->id],
        ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/hr/directory');
        $response->assertUnauthorized();
    }
}
