<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin',               'display_name' => 'Administrator',        'description' => 'Full system access',              'is_system' => true],
            ['name' => 'manager',             'display_name' => 'Manager',              'description' => 'Module management access',        'is_system' => true],
            ['name' => 'accountant',          'display_name' => 'Accountant',           'description' => 'Finance module access',           'is_system' => false],
            ['name' => 'hr_officer',          'display_name' => 'HR Officer',           'description' => 'HR module access',                'is_system' => false],
            ['name' => 'sales_rep',           'display_name' => 'Sales Representative', 'description' => 'Sales and CRM module access',     'is_system' => false],
            ['name' => 'warehouse_staff',     'display_name' => 'Warehouse Staff',      'description' => 'Inventory module access',         'is_system' => false],
            ['name' => 'procurement_officer', 'display_name' => 'Procurement Officer',  'description' => 'Procurement module access',       'is_system' => false],
            ['name' => 'viewer',              'display_name' => 'Viewer',               'description' => 'Read-only access to all modules', 'is_system' => false],
        ];

        $modules = [
            'finance', 'inventory', 'hr', 'sales', 'procurement',
            'manufacturing', 'projects', 'quality', 'assets', 'field_service', 'lms',
            'marketing', 'network', 'expenses', 'budgets', 'documents', 'contracts', 'agents',
        ];
        $actions = ['view', 'create', 'edit', 'delete', 'approve'];

        $permissions = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissions[] = [
                    'name'         => "{$module}.{$action}",
                    'display_name' => ucfirst($action) . ' ' . ucwords(str_replace('_', ' ', $module)),
                    'module'       => $module,
                    'description'  => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
        }

        foreach ($roles as &$role) {
            $role['created_at'] = now();
            $role['updated_at'] = now();
        }
        unset($role);

        DB::table('roles')->insertOrIgnore($roles);
        DB::table('permissions')->insertOrIgnore($permissions);

        // Agent-specific permission
        DB::table('permissions')->insertOrIgnore([[
            'name'         => 'agents.execute',
            'display_name' => 'Execute Agents',
            'module'       => 'agents',
            'description'  => null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]]);

        $this->assignRolePermissions('admin', array_merge($this->allActions($modules), ['agents.execute']));
        $this->assignRolePermissions('viewer', $this->viewOnly($modules));

        $this->assignRolePermissions('manager', $this->allActions($modules, exclude: ['delete']));

        $this->assignRolePermissions('accountant', array_merge(
            $this->allActions(['finance', 'expenses', 'budgets']),
            $this->viewOnly(['inventory', 'procurement', 'projects', 'contracts'])
        ));

        $this->assignRolePermissions('hr_officer', array_merge(
            $this->allActions(['hr', 'expenses']),
            $this->viewOnly(['finance', 'projects', 'documents'])
        ));

        $this->assignRolePermissions('sales_rep', array_merge(
            $this->allActions(['sales', 'marketing']),
            $this->viewOnly(['inventory', 'network', 'documents', 'contracts'])
        ));

        $this->assignRolePermissions('warehouse_staff', array_merge(
            $this->allActions(['inventory']),
            $this->viewOnly(['procurement', 'manufacturing'])
        ));

        $this->assignRolePermissions('procurement_officer', array_merge(
            $this->allActions(['procurement', 'contracts']),
            $this->viewOnly(['inventory', 'finance', 'manufacturing'])
        ));
    }

    /** @return string[] */
    private function allActions(array $modules, array $exclude = []): array
    {
        $actions = array_diff(['view', 'create', 'edit', 'delete', 'approve'], $exclude);
        $result = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $result[] = "{$module}.{$action}";
            }
        }
        return $result;
    }

    /** @return string[] */
    private function viewOnly(array $modules): array
    {
        return array_map(fn ($m) => "{$m}.view", $modules);
    }

    private function assignRolePermissions(string $roleName, array $permissionNames): void
    {
        $roleId = DB::table('roles')->where('name', $roleName)->value('id');
        if (!$roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        $rows = $permissionIds->map(fn ($pid) => [
            'role_id'       => $roleId,
            'permission_id' => $pid,
            'created_at'    => now(),
            'updated_at'    => now(),
        ])->all();

        DB::table('role_permissions')->insertOrIgnore($rows);
    }
}
