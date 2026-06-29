<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RBACTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function makeAdminUser(): void
    {
        DB::table('roles')->insertOrIgnore([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => 'System administrator',
            'is_system' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminId = DB::table('roles')->where('name', 'admin')->value('id');

        DB::table('user_roles')->insert([
            'user_id' => $this->user->id,
            'role_id' => $adminId,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function grantPermission(string $permissionName): void
    {
        DB::table('roles')->insertOrIgnore([
            'name' => 'finance_viewer',
            'display_name' => 'Finance Viewer',
            'description' => 'Can view finance data',
            'is_system' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->where('name', 'finance_viewer')->value('id');

        DB::table('permissions')->insertOrIgnore([
            'name' => $permissionName,
            'display_name' => $permissionName,
            'module' => 'finance',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');

        DB::table('role_permissions')->insertOrIgnore([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $this->user->id,
            'role_id' => $roleId,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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


    public function test_unauthenticated_user_cannot_access_finance_endpoints(): void
    {
        $response = $this->getJson('/api/v1/finance/accounts');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_without_permission_gets_403_on_finance_accounts(): void
    {
        // User has no roles/permissions at all
        $response = $this->getJson('/api/v1/finance/accounts', $this->auth());

        $response->assertForbidden()
            ->assertJson(['success' => false]);
    }

    public function test_authenticated_user_with_finance_view_permission_can_access_finance_accounts(): void
    {
        $this->grantPermission('finance.view');

        $response = $this->getJson('/api/v1/finance/accounts', $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_admin_user_can_access_finance_accounts(): void
    {
        $this->makeAdminUser();

        $response = $this->getJson('/api/v1/finance/accounts', $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_role_assignment_endpoint_only_accessible_by_admin(): void
    {
        // Create a role to assign to
        DB::table('roles')->insert([
            'name' => 'test_role',
            'display_name' => 'Test Role',
            'description' => 'A test role',
            'is_system' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roleId = DB::table('roles')->where('name', 'test_role')->value('id');

        $otherUser = User::factory()->create();

        // Non-admin user tries to assign role
        $response = $this->postJson("/api/v1/roles/{$roleId}/users/assign", [
            'user_id' => $otherUser->id,
        ], $this->auth());

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_list_roles(): void
    {
        // User has no admin role
        $response = $this->getJson('/api/v1/roles', $this->auth());

        $response->assertForbidden();
    }

    public function test_admin_can_list_roles(): void
    {
        $this->makeAdminUser();

        $response = $this->getJson('/api/v1/roles', $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);
    }
}
