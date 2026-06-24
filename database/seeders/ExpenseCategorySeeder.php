<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Meals & Entertainment',   'code' => 'MEALS',    'requires_receipt' => true],
            ['name' => 'Travel',                   'code' => 'TRAVEL',   'requires_receipt' => true],
            ['name' => 'Accommodation',            'code' => 'ACCOMM',   'requires_receipt' => true],
            ['name' => 'Transport & Parking',      'code' => 'TRANSP',   'requires_receipt' => false],
            ['name' => 'Software & Subscriptions', 'code' => 'SOFTWARE', 'requires_receipt' => true],
            ['name' => 'Office Supplies',          'code' => 'OFFICE',   'requires_receipt' => false],
            ['name' => 'Training & Education',     'code' => 'TRAINING', 'requires_receipt' => true],
            ['name' => 'Marketing & Advertising',  'code' => 'MKTG',     'requires_receipt' => true],
            ['name' => 'Communication',            'code' => 'COMMS',    'requires_receipt' => false],
            ['name' => 'Equipment & Hardware',     'code' => 'EQUIP',    'requires_receipt' => true],
            ['name' => 'Professional Services',    'code' => 'PROFSVC',  'requires_receipt' => true],
            ['name' => 'Miscellaneous',            'code' => 'MISC',     'requires_receipt' => false],
        ];

        foreach ($categories as $category) {
            DB::table('expense_categories')->insertOrIgnore(array_merge($category, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
