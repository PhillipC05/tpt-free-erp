<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $customers = [
            ['code' => 'ACME-001',  'name' => 'Acme Corporation',       'email' => 'accounts@acme.example',        'phone' => '+1-555-0101', 'city' => 'New York',   'country' => 'US', 'payment_terms' => 'Net 30',  'credit_limit' => 50000.00],
            ['code' => 'GLOB-001',  'name' => 'Global Tech Solutions',  'email' => 'billing@globaltech.example',   'phone' => '+1-555-0102', 'city' => 'San Francisco','country' => 'US', 'payment_terms' => 'Net 15',  'credit_limit' => 75000.00],
            ['code' => 'VERT-001',  'name' => 'Vertex Industries',      'email' => 'ap@vertex.example',            'phone' => '+1-555-0103', 'city' => 'Chicago',    'country' => 'US', 'payment_terms' => 'Net 45',  'credit_limit' => 100000.00],
            ['code' => 'NOVA-001',  'name' => 'Nova Manufacturing',     'email' => 'purchasing@nova.example',      'phone' => '+1-555-0104', 'city' => 'Houston',    'country' => 'US', 'payment_terms' => 'Net 30',  'credit_limit' => 40000.00],
            ['code' => 'PEAK-001',  'name' => 'Peak Performance Ltd',   'email' => 'finance@peakperf.example',     'phone' => '+44-20-0105', 'city' => 'London',     'country' => 'GB', 'payment_terms' => 'Net 60',  'credit_limit' => 80000.00],
            ['code' => 'STAR-001',  'name' => 'Starline Retail Group',  'email' => 'invoices@starline.example',    'phone' => '+1-555-0106', 'city' => 'Atlanta',    'country' => 'US', 'payment_terms' => 'Net 30',  'credit_limit' => 60000.00],
            ['code' => 'MESA-001',  'name' => 'Mesa Digital Inc',       'email' => 'finance@mesadigital.example',  'phone' => '+1-555-0107', 'city' => 'Phoenix',    'country' => 'US', 'payment_terms' => 'Net 15',  'credit_limit' => 25000.00],
            ['code' => 'BLUE-001',  'name' => 'Bluewater Logistics',    'email' => 'accounts@bluewater.example',   'phone' => '+61-2-0108',  'city' => 'Sydney',     'country' => 'AU', 'payment_terms' => 'Net 30',  'credit_limit' => 55000.00],
            ['code' => 'APEX-001',  'name' => 'Apex Healthcare Systems','email' => 'billing@apexhealth.example',   'phone' => '+1-555-0109', 'city' => 'Boston',     'country' => 'US', 'payment_terms' => 'Net 45',  'credit_limit' => 120000.00],
            ['code' => 'IRON-001',  'name' => 'Ironclad Construction',  'email' => 'ar@ironclad.example',          'phone' => '+1-555-0110', 'city' => 'Denver',     'country' => 'US', 'payment_terms' => 'Net 30',  'credit_limit' => 90000.00],
        ];

        foreach ($customers as $cust) {
            DB::table('sales_customers')->insertOrIgnore([
                'code'          => $cust['code'],
                'name'          => $cust['name'],
                'email'         => $cust['email'],
                'phone'         => $cust['phone'],
                'city'          => $cust['city'],
                'country'       => $cust['country'],
                'payment_terms' => $cust['payment_terms'],
                'credit_limit'  => $cust['credit_limit'],
                'current_balance' => 0,
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }
}
