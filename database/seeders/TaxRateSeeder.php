<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $taxRates = [
            ['code' => 'NO_TAX', 'name' => 'No Tax / Exempt', 'rate' => 0.0000, 'type' => 'percentage', 'is_active' => true, 'description' => 'Tax-exempt transactions'],
            ['code' => 'VAT_5', 'name' => 'VAT 5%', 'rate' => 5.0000, 'type' => 'percentage', 'is_active' => true, 'description' => 'Standard 5% VAT'],
            ['code' => 'VAT_10', 'name' => 'VAT 10%', 'rate' => 10.0000, 'type' => 'percentage', 'is_active' => true, 'description' => 'Standard 10% VAT'],
            ['code' => 'VAT_15', 'name' => 'VAT 15%', 'rate' => 15.0000, 'type' => 'percentage', 'is_active' => true, 'description' => 'Standard 15% VAT'],
            ['code' => 'VAT_20', 'name' => 'VAT 20%', 'rate' => 20.0000, 'type' => 'percentage', 'is_active' => true, 'description' => 'UK standard VAT rate'],
            ['code' => 'GST_10', 'name' => 'GST 10%', 'rate' => 10.0000, 'type' => 'percentage', 'is_active' => true, 'description' => 'Australian GST'],
            ['code' => 'US_SALES_8', 'name' => 'US Sales Tax 8%', 'rate' => 8.0000, 'type' => 'percentage', 'is_active' => true, 'description' => 'Typical US state sales tax'],
            ['code' => 'WHT_10', 'name' => 'Withholding Tax 10%', 'rate' => 10.0000, 'type' => 'percentage', 'is_active' => true, 'description' => 'Withholding tax on services'],
        ];

        foreach ($taxRates as &$rate) {
            $rate['created_at'] = now();
            $rate['updated_at'] = now();
        }

        DB::table('finance_tax_rates')->insertOrIgnore($taxRates);
    }
}
