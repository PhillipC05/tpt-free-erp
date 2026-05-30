<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Full system access', 'is_system' => true],
            ['name' => 'manager', 'display_name' => 'Manager', 'description' => 'Module management access', 'is_system' => true],
            ['name' => 'accountant', 'display_name' => 'Accountant', 'description' => 'Finance module access', 'is_system' => false],
            ['name' => 'hr_officer', 'display_name' => 'HR Officer', 'description' => 'HR module access', 'is_system' => false],
            ['name' => 'sales_rep', 'display_name' => 'Sales Representative', 'description' => 'Sales module access', 'is_system' => false],
            ['name' => 'warehouse_staff', 'display_name' => 'Warehouse Staff', 'description' => 'Inventory module access', 'is_system' => false],
            ['name' => 'procurement_officer', 'display_name' => 'Procurement Officer', 'description' => 'Procurement module access', 'is_system' => false],
            ['name' => 'viewer', 'display_name' => 'Viewer', 'description' => 'Read-only access to all modules', 'is_system' => false],
        ];

        $modules = ['finance', 'inventory', 'hr', 'sales', 'procurement', 'manufacturing', 'projects', 'quality', 'assets', 'field_service', 'lms'];
        $actions = ['view', 'create', 'edit', 'delete', 'approve'];

        $permissions = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissions[] = [
                    'name' => "{$module}.{$action}",
                    'display_name' => ucfirst($action) . ' ' . ucwords(str_replace('_', ' ', $module)),
                    'module' => $module,
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach ($roles as &$role) {
            $role['created_at'] = now();
            $role['updated_at'] = now();
        }

        DB::table('roles')->insertOrIgnore($roles);
        DB::table('permissions')->insertOrIgnore($permissions);

        // Grant all permissions to admin role
        $adminId = DB::table('roles')->where('name', 'admin')->value('id');
        $allPermissionIds = DB::table('permissions')->pluck('id');

        $rolePermissions = $allPermissionIds->map(fn ($pid) => [
            'role_id' => $adminId,
            'permission_id' => $pid,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        DB::table('role_permissions')->insertOrIgnore($rolePermissions);

        // Grant view-only permissions to viewer role
        $viewerId = DB::table('roles')->where('name', 'viewer')->value('id');
        $viewPermissionIds = DB::table('permissions')->where('name', 'like', '%.view')->pluck('id');

        $viewerPermissions = $viewPermissionIds->map(fn ($pid) => [
            'role_id' => $viewerId,
            'permission_id' => $pid,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        DB::table('role_permissions')->insertOrIgnore($viewerPermissions);
    }
}
