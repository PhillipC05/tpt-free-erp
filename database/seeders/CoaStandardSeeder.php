<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Standard GAAP-aligned Chart of Accounts (~70 accounts).
 * Run via: php artisan db:seed --class=CoaStandardSeeder
 */
class CoaStandardSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('finance_accounts')->truncate();

        $now = now();

        $accounts = [
            // ── 1000s: Current Assets ──────────────────────────────────────
            ['code' => '1000', 'name' => 'Current Assets',               'type' => 'asset',     'category' => 'Current Assets',  'description' => 'Total current assets',                      'parent_id' => null],
            ['code' => '1010', 'name' => 'Cash – Operating Account',     'type' => 'asset',     'category' => 'Cash & Equivalents', 'description' => 'Primary operating bank account',           'parent_id' => null],
            ['code' => '1020', 'name' => 'Cash – Payroll Account',       'type' => 'asset',     'category' => 'Cash & Equivalents', 'description' => 'Dedicated payroll bank account',           'parent_id' => null],
            ['code' => '1030', 'name' => 'Petty Cash',                   'type' => 'asset',     'category' => 'Cash & Equivalents', 'description' => 'On-hand petty cash fund',                  'parent_id' => null],
            ['code' => '1050', 'name' => 'Short-term Investments',       'type' => 'asset',     'category' => 'Cash & Equivalents', 'description' => 'Investments maturing within 90 days',      'parent_id' => null],
            ['code' => '1100', 'name' => 'Accounts Receivable',          'type' => 'asset',     'category' => 'Receivables',     'description' => 'Amounts owed by customers',                 'parent_id' => null],
            ['code' => '1110', 'name' => 'Allowance for Doubtful Accts', 'type' => 'asset',     'category' => 'Receivables',     'description' => 'Estimated uncollectible receivables (contra)', 'parent_id' => null],
            ['code' => '1150', 'name' => 'Notes Receivable – Current',   'type' => 'asset',     'category' => 'Receivables',     'description' => 'Short-term promissory notes due within 1 year', 'parent_id' => null],
            ['code' => '1200', 'name' => 'Inventory',                    'type' => 'asset',     'category' => 'Inventory',       'description' => 'Goods held for sale or production',         'parent_id' => null],
            ['code' => '1210', 'name' => 'Raw Materials Inventory',      'type' => 'asset',     'category' => 'Inventory',       'description' => 'Materials not yet in production',           'parent_id' => null],
            ['code' => '1220', 'name' => 'Work-in-Process Inventory',    'type' => 'asset',     'category' => 'Inventory',       'description' => 'Partially completed goods',                 'parent_id' => null],
            ['code' => '1230', 'name' => 'Finished Goods Inventory',     'type' => 'asset',     'category' => 'Inventory',       'description' => 'Completed goods ready for sale',            'parent_id' => null],
            ['code' => '1300', 'name' => 'Prepaid Expenses',             'type' => 'asset',     'category' => 'Prepaid & Other', 'description' => 'Expenses paid in advance',                  'parent_id' => null],
            ['code' => '1310', 'name' => 'Prepaid Insurance',            'type' => 'asset',     'category' => 'Prepaid & Other', 'description' => 'Insurance premiums paid in advance',        'parent_id' => null],
            ['code' => '1320', 'name' => 'Prepaid Rent',                 'type' => 'asset',     'category' => 'Prepaid & Other', 'description' => 'Rent paid in advance',                     'parent_id' => null],
            ['code' => '1400', 'name' => 'Other Current Assets',         'type' => 'asset',     'category' => 'Prepaid & Other', 'description' => 'Miscellaneous current assets',              'parent_id' => null],

            // ── 1500s: Non-current Assets ──────────────────────────────────
            ['code' => '1500', 'name' => 'Property, Plant & Equipment',  'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'Tangible long-term assets',                 'parent_id' => null],
            ['code' => '1510', 'name' => 'Land',                         'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'Land owned (not depreciated)',              'parent_id' => null],
            ['code' => '1520', 'name' => 'Buildings',                    'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'Buildings and structures owned',            'parent_id' => null],
            ['code' => '1525', 'name' => 'Accum. Depr. – Buildings',     'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'Accumulated depreciation on buildings (contra)', 'parent_id' => null],
            ['code' => '1530', 'name' => 'Machinery & Equipment',        'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'Production machinery and equipment',        'parent_id' => null],
            ['code' => '1535', 'name' => 'Accum. Depr. – Machinery',     'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'Accumulated depreciation on machinery (contra)', 'parent_id' => null],
            ['code' => '1540', 'name' => 'Vehicles',                     'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'Company-owned vehicles',                   'parent_id' => null],
            ['code' => '1545', 'name' => 'Accum. Depr. – Vehicles',      'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'Accumulated depreciation on vehicles (contra)', 'parent_id' => null],
            ['code' => '1550', 'name' => 'Furniture & Fixtures',         'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'Office furniture and fixtures',             'parent_id' => null],
            ['code' => '1555', 'name' => 'Accum. Depr. – Furniture',     'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'Accumulated depreciation on furniture (contra)', 'parent_id' => null],
            ['code' => '1560', 'name' => 'Computer Hardware & Software',  'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'IT equipment and software licenses',       'parent_id' => null],
            ['code' => '1565', 'name' => 'Accum. Depr. – IT Equipment',  'type' => 'asset',     'category' => 'Fixed Assets',    'description' => 'Accumulated depreciation on IT (contra)',   'parent_id' => null],
            ['code' => '1600', 'name' => 'Intangible Assets',            'type' => 'asset',     'category' => 'Intangibles',     'description' => 'Non-physical long-term assets',             'parent_id' => null],
            ['code' => '1610', 'name' => 'Patents & Trademarks',         'type' => 'asset',     'category' => 'Intangibles',     'description' => 'Intellectual property',                     'parent_id' => null],
            ['code' => '1620', 'name' => 'Goodwill',                     'type' => 'asset',     'category' => 'Intangibles',     'description' => 'Goodwill from acquisitions',                'parent_id' => null],

            // ── 2000s: Current Liabilities ─────────────────────────────────
            ['code' => '2000', 'name' => 'Current Liabilities',          'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Obligations due within 12 months',       'parent_id' => null],
            ['code' => '2010', 'name' => 'Accounts Payable',             'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Amounts owed to suppliers',              'parent_id' => null],
            ['code' => '2020', 'name' => 'Accrued Liabilities',          'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Expenses incurred but not yet paid',     'parent_id' => null],
            ['code' => '2030', 'name' => 'Accrued Payroll',              'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Wages earned but not yet paid',          'parent_id' => null],
            ['code' => '2040', 'name' => 'Accrued Taxes Payable',        'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Tax obligations not yet remitted',       'parent_id' => null],
            ['code' => '2050', 'name' => 'Sales Tax Payable',            'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Sales tax collected from customers',     'parent_id' => null],
            ['code' => '2060', 'name' => 'Payroll Tax Payable',          'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Payroll taxes withheld',                 'parent_id' => null],
            ['code' => '2070', 'name' => 'Short-term Notes Payable',     'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Notes payable due within 1 year',        'parent_id' => null],
            ['code' => '2080', 'name' => 'Current Portion – LT Debt',   'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Current portion of long-term debt',      'parent_id' => null],
            ['code' => '2090', 'name' => 'Deferred Revenue',             'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Revenue received but not yet earned',    'parent_id' => null],
            ['code' => '2100', 'name' => 'Customer Deposits',            'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Deposits received from customers',       'parent_id' => null],

            // ── 2500s: Long-term Liabilities ───────────────────────────────
            ['code' => '2500', 'name' => 'Long-term Liabilities',        'type' => 'liability', 'category' => 'Long-term Liabilities', 'description' => 'Obligations due beyond 12 months',    'parent_id' => null],
            ['code' => '2510', 'name' => 'Long-term Notes Payable',      'type' => 'liability', 'category' => 'Long-term Liabilities', 'description' => 'Notes payable due beyond 1 year',     'parent_id' => null],
            ['code' => '2520', 'name' => 'Bonds Payable',                'type' => 'liability', 'category' => 'Long-term Liabilities', 'description' => 'Bonds issued by the company',         'parent_id' => null],
            ['code' => '2530', 'name' => 'Lease Obligations – LT',       'type' => 'liability', 'category' => 'Long-term Liabilities', 'description' => 'Long-term lease liabilities',         'parent_id' => null],
            ['code' => '2540', 'name' => 'Deferred Tax Liability',       'type' => 'liability', 'category' => 'Long-term Liabilities', 'description' => 'Future tax obligations from timing differences', 'parent_id' => null],

            // ── 3000s: Equity ──────────────────────────────────────────────
            ['code' => '3000', 'name' => 'Equity',                       'type' => 'equity',    'category' => 'Equity',          'description' => 'Total owners equity',                       'parent_id' => null],
            ['code' => '3010', 'name' => 'Common Stock',                 'type' => 'equity',    'category' => 'Equity',          'description' => 'Par value of issued common shares',         'parent_id' => null],
            ['code' => '3020', 'name' => 'Additional Paid-in Capital',   'type' => 'equity',    'category' => 'Equity',          'description' => 'Excess over par value from stock issuances', 'parent_id' => null],
            ['code' => '3030', 'name' => 'Retained Earnings',            'type' => 'equity',    'category' => 'Equity',          'description' => 'Cumulative undistributed profits',          'parent_id' => null],
            ['code' => '3040', 'name' => 'Owner\'s Draw / Distributions', 'type' => 'equity',    'category' => 'Equity',          'description' => 'Withdrawals by owners',                     'parent_id' => null],
            ['code' => '3050', 'name' => 'Treasury Stock',               'type' => 'equity',    'category' => 'Equity',          'description' => 'Repurchased company shares (contra)',        'parent_id' => null],

            // ── 4000s: Revenue ─────────────────────────────────────────────
            ['code' => '4000', 'name' => 'Revenue',                      'type' => 'revenue',   'category' => 'Revenue',         'description' => 'Total revenue',                             'parent_id' => null],
            ['code' => '4010', 'name' => 'Product Sales Revenue',        'type' => 'revenue',   'category' => 'Revenue',         'description' => 'Revenue from product sales',                'parent_id' => null],
            ['code' => '4020', 'name' => 'Service Revenue',              'type' => 'revenue',   'category' => 'Revenue',         'description' => 'Revenue from services rendered',            'parent_id' => null],
            ['code' => '4030', 'name' => 'Subscription Revenue',         'type' => 'revenue',   'category' => 'Revenue',         'description' => 'Recurring subscription income',             'parent_id' => null],
            ['code' => '4040', 'name' => 'Interest Income',              'type' => 'revenue',   'category' => 'Other Income',    'description' => 'Interest earned on deposits and loans',     'parent_id' => null],
            ['code' => '4050', 'name' => 'Rental Income',                'type' => 'revenue',   'category' => 'Other Income',    'description' => 'Income from renting assets',                'parent_id' => null],
            ['code' => '4060', 'name' => 'Gain on Sale of Assets',       'type' => 'revenue',   'category' => 'Other Income',    'description' => 'Gains from disposing of assets',            'parent_id' => null],
            ['code' => '4090', 'name' => 'Sales Returns & Allowances',   'type' => 'revenue',   'category' => 'Revenue',         'description' => 'Contra revenue for returns (contra)',       'parent_id' => null],
            ['code' => '4095', 'name' => 'Sales Discounts',              'type' => 'revenue',   'category' => 'Revenue',         'description' => 'Discounts granted to customers (contra)',   'parent_id' => null],

            // ── 5000s: Cost of Goods Sold ──────────────────────────────────
            ['code' => '5000', 'name' => 'Cost of Goods Sold',           'type' => 'expense',   'category' => 'COGS',            'description' => 'Direct cost of goods sold',                 'parent_id' => null],
            ['code' => '5010', 'name' => 'Purchases',                    'type' => 'expense',   'category' => 'COGS',            'description' => 'Cost of inventory purchased',               'parent_id' => null],
            ['code' => '5020', 'name' => 'Purchase Returns & Allowances', 'type' => 'expense',   'category' => 'COGS',            'description' => 'Contra purchases account',                  'parent_id' => null],
            ['code' => '5030', 'name' => 'Freight-in',                   'type' => 'expense',   'category' => 'COGS',            'description' => 'Shipping costs to receive inventory',       'parent_id' => null],

            // ── 6000s: Operating Expenses ──────────────────────────────────
            ['code' => '6000', 'name' => 'Operating Expenses',           'type' => 'expense',   'category' => 'Operating Expenses', 'description' => 'Total operating expenses',               'parent_id' => null],
            ['code' => '6010', 'name' => 'Salaries & Wages',             'type' => 'expense',   'category' => 'Payroll',         'description' => 'Employee salaries and wages',               'parent_id' => null],
            ['code' => '6020', 'name' => 'Payroll Taxes – Employer',     'type' => 'expense',   'category' => 'Payroll',         'description' => 'Employer portion of payroll taxes',         'parent_id' => null],
            ['code' => '6030', 'name' => 'Employee Benefits',            'type' => 'expense',   'category' => 'Payroll',         'description' => 'Health insurance, 401k, and other benefits', 'parent_id' => null],
            ['code' => '6040', 'name' => 'Rent Expense',                 'type' => 'expense',   'category' => 'Facilities',      'description' => 'Office and facility rent',                  'parent_id' => null],
            ['code' => '6050', 'name' => 'Utilities',                    'type' => 'expense',   'category' => 'Facilities',      'description' => 'Electric, gas, water, and internet',        'parent_id' => null],
            ['code' => '6060', 'name' => 'Office Supplies',              'type' => 'expense',   'category' => 'Administrative',  'description' => 'Consumable office supplies',                'parent_id' => null],
            ['code' => '6070', 'name' => 'Repairs & Maintenance',        'type' => 'expense',   'category' => 'Facilities',      'description' => 'Costs to maintain property and equipment',  'parent_id' => null],
            ['code' => '6080', 'name' => 'Marketing & Advertising',      'type' => 'expense',   'category' => 'Sales & Marketing', 'description' => 'Advertising, promotions, and campaigns',  'parent_id' => null],
            ['code' => '6090', 'name' => 'Travel & Entertainment',       'type' => 'expense',   'category' => 'Administrative',  'description' => 'Business travel and client entertainment',  'parent_id' => null],
            ['code' => '6100', 'name' => 'Professional Fees',            'type' => 'expense',   'category' => 'Administrative',  'description' => 'Legal, accounting, and consulting fees',    'parent_id' => null],
            ['code' => '6110', 'name' => 'Insurance Expense',            'type' => 'expense',   'category' => 'Administrative',  'description' => 'Business insurance premiums',               'parent_id' => null],
            ['code' => '6120', 'name' => 'Telephone & Communications',   'type' => 'expense',   'category' => 'Administrative',  'description' => 'Phone, mobile, and communication costs',    'parent_id' => null],
            ['code' => '6130', 'name' => 'Software & Subscriptions',     'type' => 'expense',   'category' => 'Technology',      'description' => 'SaaS subscriptions and software licenses',  'parent_id' => null],
            ['code' => '6140', 'name' => 'Training & Development',       'type' => 'expense',   'category' => 'Payroll',         'description' => 'Employee training and education costs',      'parent_id' => null],
            ['code' => '6150', 'name' => 'Shipping & Delivery',          'type' => 'expense',   'category' => 'Operations',      'description' => 'Outbound shipping and freight costs',        'parent_id' => null],
            ['code' => '6160', 'name' => 'Bank Fees & Charges',          'type' => 'expense',   'category' => 'Administrative',  'description' => 'Bank service charges and fees',             'parent_id' => null],
            ['code' => '6900', 'name' => 'Miscellaneous Expense',        'type' => 'expense',   'category' => 'Administrative',  'description' => 'Uncategorized operating expenses',          'parent_id' => null],

            // ── 7000s: Other (Non-operating) Expenses ─────────────────────
            ['code' => '7000', 'name' => 'Other Expenses',               'type' => 'expense',   'category' => 'Non-operating',   'description' => 'Non-operating expenses',                    'parent_id' => null],
            ['code' => '7010', 'name' => 'Interest Expense',             'type' => 'expense',   'category' => 'Non-operating',   'description' => 'Interest on loans and credit facilities',   'parent_id' => null],
            ['code' => '7020', 'name' => 'Depreciation Expense',         'type' => 'expense',   'category' => 'Non-operating',   'description' => 'Periodic depreciation of fixed assets',     'parent_id' => null],
            ['code' => '7030', 'name' => 'Amortization Expense',         'type' => 'expense',   'category' => 'Non-operating',   'description' => 'Periodic amortization of intangibles',      'parent_id' => null],
            ['code' => '7040', 'name' => 'Income Tax Expense',           'type' => 'expense',   'category' => 'Non-operating',   'description' => 'Corporate income taxes',                    'parent_id' => null],
            ['code' => '7050', 'name' => 'Loss on Sale of Assets',       'type' => 'expense',   'category' => 'Non-operating',   'description' => 'Losses from disposing of assets',           'parent_id' => null],
        ];

        foreach ($accounts as $acct) {
            DB::table('finance_accounts')->insert(array_merge($acct, [
                'is_active' => true,
                'currency' => 'USD',
                'opening_balance' => 0,
                'current_balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
