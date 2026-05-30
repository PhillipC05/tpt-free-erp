<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CurrencySeeder::class,
            TaxRateSeeder::class,
        ]);

        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@tpt-erp.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role
        $adminRole = \Illuminate\Support\Facades\DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            \Illuminate\Support\Facades\DB::table('user_roles')->insertOrIgnore([
                'user_id' => $admin->id,
                'role_id' => $adminRole->id,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
