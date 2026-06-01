<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcurementSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $vendors = [
            ['code' => 'VEND-001', 'name' => 'TechSupply Co.',         'email' => 'sales@techsupply.example',    'phone' => '+1-555-0201', 'payment_terms' => 'Net 30'],
            ['code' => 'VEND-002', 'name' => 'Industrial Materials Inc','email' => 'orders@indmat.example',       'phone' => '+1-555-0202', 'payment_terms' => 'Net 45'],
            ['code' => 'VEND-003', 'name' => 'FastParts Direct',        'email' => 'sales@fastparts.example',     'phone' => '+1-555-0203', 'payment_terms' => 'Net 15'],
            ['code' => 'VEND-004', 'name' => 'Office Depot Business',   'email' => 'b2b@officedepot.example',     'phone' => '+1-555-0204', 'payment_terms' => 'Net 30'],
            ['code' => 'VEND-005', 'name' => 'Global Freight Partners', 'email' => 'accounts@gfp.example',        'phone' => '+1-555-0205', 'payment_terms' => 'Net 30'],
            ['code' => 'VEND-006', 'name' => 'ProMfg Supplies Ltd',    'email' => 'sales@promfg.example',        'phone' => '+44-20-0206', 'payment_terms' => 'Net 60'],
        ];

        foreach ($vendors as $vendor) {
            DB::table('procurement_vendors')->insertOrIgnore([
                'code'            => $vendor['code'],
                'name'            => $vendor['name'],
                'email'           => $vendor['email'],
                'phone'           => $vendor['phone'],
                'payment_terms'   => $vendor['payment_terms'],
                'status'          => 'active',
                'current_balance' => 0,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }
}
