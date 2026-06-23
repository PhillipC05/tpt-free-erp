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
            OnboardingPresetSeeder::class,

            // Chart of Accounts — swap to CoaSimpleSeeder or CoaManufacturingSeeder for different templates:
            //   php artisan migrate:fresh --seed (uses CoaStandardSeeder by default)
            //   php artisan db:seed --class=CoaSimpleSeeder        (lean startup)
            //   php artisan db:seed --class=CoaManufacturingSeeder (manufacturing)
            CoaStandardSeeder::class,

            // Module data — order matters due to FK dependencies
            InventorySeeder::class,     // categories → warehouses → products
            HrSeeder::class,            // departments → employees
            SalesSeeder::class,         // customers
            ProcurementSeeder::class,   // vendors
        ]);

        // Default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@tpt-erp.local'],
            [
                'name'               => 'System Administrator',
                'password'           => Hash::make('password'),
                'email_verified_at'  => now(),
            ]
        );

        // Assign admin role
        $adminRole = \Illuminate\Support\Facades\DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            \Illuminate\Support\Facades\DB::table('user_roles')->insertOrIgnore([
                'user_id'     => $admin->id,
                'role_id'     => $adminRole->id,
                'assigned_at' => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
