<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Lean startup / small-business Chart of Accounts (~25 accounts).
 * Run via: php artisan db:seed --class=CoaSimpleSeeder
 */
class CoaSimpleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('finance_accounts')->truncate();

        $now = now();

        $accounts = [
            // Assets
            ['code' => '1000', 'name' => 'Cash & Bank',              'type' => 'asset',     'category' => 'Cash & Equivalents', 'description' => 'All bank and cash accounts'],
            ['code' => '1100', 'name' => 'Accounts Receivable',      'type' => 'asset',     'category' => 'Receivables',        'description' => 'Amounts owed by customers'],
            ['code' => '1200', 'name' => 'Inventory',                 'type' => 'asset',     'category' => 'Inventory',          'description' => 'Goods held for sale'],
            ['code' => '1300', 'name' => 'Prepaid Expenses',          'type' => 'asset',     'category' => 'Prepaid & Other',    'description' => 'Expenses paid in advance'],
            ['code' => '1500', 'name' => 'Fixed Assets',              'type' => 'asset',     'category' => 'Fixed Assets',       'description' => 'Equipment, furniture, computers'],
            ['code' => '1510', 'name' => 'Accum. Depreciation',       'type' => 'asset',     'category' => 'Fixed Assets',       'description' => 'Accumulated depreciation (contra)'],

            // Liabilities
            ['code' => '2000', 'name' => 'Accounts Payable',          'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Amounts owed to suppliers'],
            ['code' => '2100', 'name' => 'Accrued Liabilities',       'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Expenses incurred but unpaid'],
            ['code' => '2200', 'name' => 'Tax Payable',               'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Sales and income taxes owed'],
            ['code' => '2300', 'name' => 'Payroll Liabilities',       'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Wages and payroll taxes owed'],
            ['code' => '2500', 'name' => 'Loans Payable',             'type' => 'liability', 'category' => 'Long-term Liabilities','description' => 'Business loans and credit lines'],

            // Equity
            ['code' => '3000', 'name' => 'Owner\'s Equity',           'type' => 'equity',    'category' => 'Equity',              'description' => 'Owner\'s investment in the business'],
            ['code' => '3100', 'name' => 'Retained Earnings',         'type' => 'equity',    'category' => 'Equity',              'description' => 'Cumulative net income retained'],
            ['code' => '3200', 'name' => 'Owner\'s Draw',             'type' => 'equity',    'category' => 'Equity',              'description' => 'Withdrawals by the owner'],

            // Revenue
            ['code' => '4000', 'name' => 'Sales Revenue',             'type' => 'revenue',   'category' => 'Revenue',             'description' => 'Revenue from product and service sales'],
            ['code' => '4100', 'name' => 'Other Income',              'type' => 'revenue',   'category' => 'Other Income',        'description' => 'Interest, grants, and miscellaneous income'],

            // COGS
            ['code' => '5000', 'name' => 'Cost of Goods Sold',        'type' => 'expense',   'category' => 'COGS',                'description' => 'Direct cost of products sold'],

            // Operating Expenses
            ['code' => '6000', 'name' => 'Payroll & Benefits',        'type' => 'expense',   'category' => 'Payroll',             'description' => 'Salaries, wages, and employee benefits'],
            ['code' => '6100', 'name' => 'Rent & Utilities',          'type' => 'expense',   'category' => 'Facilities',          'description' => 'Office rent, electricity, internet'],
            ['code' => '6200', 'name' => 'Marketing',                 'type' => 'expense',   'category' => 'Sales & Marketing',   'description' => 'Advertising and promotional costs'],
            ['code' => '6300', 'name' => 'Professional Services',     'type' => 'expense',   'category' => 'Administrative',      'description' => 'Legal, accounting, and consulting fees'],
            ['code' => '6400', 'name' => 'Software & Technology',     'type' => 'expense',   'category' => 'Technology',          'description' => 'SaaS tools, hosting, and software costs'],
            ['code' => '6500', 'name' => 'Office & Admin',            'type' => 'expense',   'category' => 'Administrative',      'description' => 'Supplies, postage, and miscellaneous admin'],
            ['code' => '6600', 'name' => 'Travel & Entertainment',    'type' => 'expense',   'category' => 'Administrative',      'description' => 'Business travel and client meetings'],
            ['code' => '7000', 'name' => 'Interest & Bank Fees',      'type' => 'expense',   'category' => 'Non-operating',       'description' => 'Loan interest and bank charges'],
        ];

        foreach ($accounts as $acct) {
            DB::table('finance_accounts')->insert(array_merge($acct, [
                'parent_id'       => null,
                'is_active'       => true,
                'currency'        => 'USD',
                'opening_balance' => 0,
                'current_balance' => 0,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]));
        }
    }
}
