<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Standalone seeder that upserts all module permissions without touching roles or role assignments.
 * Safe to run repeatedly after adding new modules — existing permissions are left untouched.
 *
 * Usage: php artisan db:seed --class=PermissionSeeder
 */
class PermissionSeeder extends Seeder
{
    private const MODULES = [
        'finance', 'inventory', 'hr', 'sales', 'procurement',
        'manufacturing', 'projects', 'quality', 'assets',
        'field_service', 'lms', 'marketing', 'network',
        'expenses', 'budgets', 'documents', 'contracts', 'agents',
    ];

    private const ACTIONS = ['view', 'create', 'edit', 'delete', 'approve'];

    private const EXTRA_PERMISSIONS = [
        ['name' => 'agents.execute', 'display_name' => 'Execute Agent Skills', 'module' => 'agents'],
    ];

    public function run(): void
    {
        $now = now();
        $permissions = [];

        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                $permissions[] = [
                    'name'         => "{$module}.{$action}",
                    'display_name' => ucfirst($action) . ' ' . ucwords(str_replace('_', ' ', $module)),
                    'module'       => $module,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        foreach (self::EXTRA_PERMISSIONS as $extra) {
            $permissions[] = array_merge($extra, ['created_at' => $now, 'updated_at' => $now]);
        }

        DB::table('permissions')->insertOrIgnore($permissions);

        $this->command->info('PermissionSeeder: ' . count($permissions) . ' permissions upserted (duplicates skipped).');
    }
}
