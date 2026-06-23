<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleExpirationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function insertRole(string $name): int
    {
        DB::table('roles')->insertOrIgnore([
            'name'         => $name,
            'display_name' => $name,
            'is_system'    => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return DB::table('roles')->where('name', $name)->value('id');
    }

    private function insertPermission(string $name, string $module = 'finance'): int
    {
        DB::table('permissions')->insertOrIgnore([
            'name'         => $name,
            'display_name' => $name,
            'module'       => $module,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return DB::table('permissions')->where('name', $name)->value('id');
    }

    private function assignRole(int $roleId, ?\Carbon\Carbon $expiresAt = null): void
    {
        DB::table('user_roles')->insert([
            'user_id'     => $this->user->id,
            'role_id'     => $roleId,
            'assigned_at' => now(),
            'expires_at'  => $expiresAt,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // role: middleware tests
    // -------------------------------------------------------------------------

    public function test_non_expired_role_grants_access_to_admin_route(): void
    {
        $roleId = $this->insertRole('admin');
        $this->assignRole($roleId, now()->addDay());

        $this->getJson('/api/v1/roles', $this->auth())->assertOk();
    }

    public function test_expired_role_denies_access_to_admin_route(): void
    {
        $roleId = $this->insertRole('admin');
        $this->assignRole($roleId, now()->subSecond()); // expired 1 second ago

        $this->getJson('/api/v1/roles', $this->auth())->assertForbidden();
    }

    public function test_role_with_null_expires_at_never_expires(): void
    {
        $roleId = $this->insertRole('admin');
        $this->assignRole($roleId, null); // null = never expires

        $this->getJson('/api/v1/roles', $this->auth())->assertOk();
    }

    public function test_role_expiring_in_future_grants_access(): void
    {
        $roleId = $this->insertRole('admin');
        $this->assignRole($roleId, now()->addYear());

        $this->getJson('/api/v1/roles', $this->auth())->assertOk();
    }

    public function test_role_expiry_at_exact_now_denies_access(): void
    {
        $roleId = $this->insertRole('admin');
        // Set expires_at to now minus one second to simulate a just-expired role
        $this->assignRole($roleId, now()->subSeconds(1));

        $this->getJson('/api/v1/roles', $this->auth())->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // permission: middleware tests (via User::hasPermission → getActiveRoleNames)
    // -------------------------------------------------------------------------

    public function test_non_expired_permission_role_grants_finance_access(): void
    {
        $roleId       = $this->insertRole('finance_viewer_exp');
        $permissionId = $this->insertPermission('finance.view');

        DB::table('role_permissions')->insert([
            'role_id'       => $roleId,
            'permission_id' => $permissionId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $this->assignRole($roleId, now()->addHour());

        $this->getJson('/api/v1/finance/accounts', $this->auth())->assertOk();
    }

    public function test_expired_permission_role_denies_finance_access(): void
    {
        $roleId       = $this->insertRole('finance_viewer_expired');
        $permissionId = $this->insertPermission('finance.view');

        DB::table('role_permissions')->insert([
            'role_id'       => $roleId,
            'permission_id' => $permissionId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $this->assignRole($roleId, now()->subHour()); // already expired

        $this->getJson('/api/v1/finance/accounts', $this->auth())->assertForbidden();
    }

    public function test_user_with_mixed_roles_expired_and_active_uses_active_role(): void
    {
        // Expired admin role
        $adminRoleId = $this->insertRole('admin');
        $this->assignRole($adminRoleId, now()->subDay());

        // Active finance.view permission via a non-expired role
        $roleId       = $this->insertRole('finance_active');
        $permissionId = $this->insertPermission('finance.view');

        DB::table('role_permissions')->insert([
            'role_id'       => $roleId,
            'permission_id' => $permissionId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $this->assignRole($roleId, null); // active, no expiry

        // Should be allowed via the active finance role (not admin)
        $this->getJson('/api/v1/finance/accounts', $this->auth())->assertOk();
        // Should NOT be able to reach admin-only route despite having expired admin role
        $this->getJson('/api/v1/roles', $this->auth())->assertForbidden();
    }

    public function test_soft_deleted_role_assignment_denies_access(): void
    {
        $roleId = $this->insertRole('admin');
        $this->assignRole($roleId, null);

        // Soft-delete the assignment
        DB::table('user_roles')
            ->where('user_id', $this->user->id)
            ->where('role_id', $roleId)
            ->update(['deleted_at' => now()]);

        $this->getJson('/api/v1/roles', $this->auth())->assertForbidden();
    }
}
