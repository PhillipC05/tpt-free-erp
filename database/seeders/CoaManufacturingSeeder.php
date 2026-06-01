<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manufacturing-focused Chart of Accounts (~85 accounts).
 * Extends the standard COA with production-specific accounts.
 * Run via: php artisan db:seed --class=CoaManufacturingSeeder
 */
class CoaManufacturingSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('finance_accounts')->truncate();

        $now = now();

        $accounts = [
            // ── 1000s: Current Assets ──────────────────────────────────────
            ['code' => '1010', 'name' => 'Cash – Operating Account',        'type' => 'asset',     'category' => 'Cash & Equivalents',  'description' => 'Primary operating bank account'],
            ['code' => '1020', 'name' => 'Cash – Payroll Account',          'type' => 'asset',     'category' => 'Cash & Equivalents',  'description' => 'Dedicated payroll bank account'],
            ['code' => '1030', 'name' => 'Petty Cash',                      'type' => 'asset',     'category' => 'Cash & Equivalents',  'description' => 'On-hand petty cash fund'],
            ['code' => '1100', 'name' => 'Accounts Receivable',             'type' => 'asset',     'category' => 'Receivables',         'description' => 'Amounts owed by customers'],
            ['code' => '1110', 'name' => 'Allowance for Doubtful Accounts', 'type' => 'asset',     'category' => 'Receivables',         'description' => 'Estimated uncollectible receivables (contra)'],
            ['code' => '1300', 'name' => 'Prepaid Expenses',                'type' => 'asset',     'category' => 'Prepaid & Other',     'description' => 'Expenses paid in advance'],
            ['code' => '1310', 'name' => 'Prepaid Insurance',               'type' => 'asset',     'category' => 'Prepaid & Other',     'description' => 'Insurance premiums paid in advance'],

            // Manufacturing Inventories
            ['code' => '1200', 'name' => 'Raw Materials Inventory',         'type' => 'asset',     'category' => 'Inventory',           'description' => 'Materials awaiting production'],
            ['code' => '1210', 'name' => 'Purchased Components',            'type' => 'asset',     'category' => 'Inventory',           'description' => 'Bought-in parts and sub-assemblies'],
            ['code' => '1220', 'name' => 'Packaging Materials',             'type' => 'asset',     'category' => 'Inventory',           'description' => 'Boxes, labels, wrapping materials'],
            ['code' => '1230', 'name' => 'Work-in-Process (WIP)',           'type' => 'asset',     'category' => 'Inventory',           'description' => 'Partially completed production jobs'],
            ['code' => '1240', 'name' => 'Finished Goods Inventory',        'type' => 'asset',     'category' => 'Inventory',           'description' => 'Completed goods ready for sale'],
            ['code' => '1250', 'name' => 'Maintenance & Repair Supplies',   'type' => 'asset',     'category' => 'Inventory',           'description' => 'Spare parts and MRO supplies'],
            ['code' => '1400', 'name' => 'Other Current Assets',            'type' => 'asset',     'category' => 'Prepaid & Other',     'description' => 'Miscellaneous current assets'],

            // ── 1500s: Fixed Assets ────────────────────────────────────────
            ['code' => '1510', 'name' => 'Land',                            'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Land owned by the company'],
            ['code' => '1520', 'name' => 'Buildings & Plant',               'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Factory buildings and structures'],
            ['code' => '1525', 'name' => 'Accum. Depr. – Buildings',        'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Accumulated depreciation on buildings (contra)'],
            ['code' => '1530', 'name' => 'Production Machinery',            'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Core manufacturing equipment'],
            ['code' => '1535', 'name' => 'Accum. Depr. – Machinery',        'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Accumulated depreciation on machinery (contra)'],
            ['code' => '1540', 'name' => 'Material Handling Equipment',     'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Forklifts, conveyors, and handling equipment'],
            ['code' => '1545', 'name' => 'Accum. Depr. – MH Equipment',     'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Accumulated depreciation on MH equipment (contra)'],
            ['code' => '1550', 'name' => 'Tooling & Dies',                  'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Specialized production tooling and dies'],
            ['code' => '1555', 'name' => 'Accum. Depr. – Tooling',          'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Accumulated depreciation on tooling (contra)'],
            ['code' => '1560', 'name' => 'Vehicles',                        'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Company vehicles'],
            ['code' => '1565', 'name' => 'Accum. Depr. – Vehicles',         'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Accumulated depreciation on vehicles (contra)'],
            ['code' => '1570', 'name' => 'Office Furniture & Equipment',    'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Administrative office assets'],
            ['code' => '1575', 'name' => 'Accum. Depr. – Office Equipment', 'type' => 'asset',     'category' => 'Fixed Assets',        'description' => 'Accumulated depreciation on office equipment (contra)'],
            ['code' => '1600', 'name' => 'Patents & Intellectual Property', 'type' => 'asset',     'category' => 'Intangibles',         'description' => 'Owned IP and manufacturing patents'],

            // ── 2000s: Liabilities ─────────────────────────────────────────
            ['code' => '2010', 'name' => 'Accounts Payable',                'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Amounts owed to suppliers and vendors'],
            ['code' => '2020', 'name' => 'Accrued Liabilities',             'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Expenses incurred but not yet paid'],
            ['code' => '2030', 'name' => 'Accrued Payroll',                 'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Wages earned but not yet paid'],
            ['code' => '2040', 'name' => 'Accrued Taxes Payable',           'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Tax obligations not yet remitted'],
            ['code' => '2050', 'name' => 'Sales Tax Payable',               'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Sales tax collected from customers'],
            ['code' => '2060', 'name' => 'Payroll Tax Payable',             'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Payroll taxes withheld from employees'],
            ['code' => '2070', 'name' => 'Customer Deposits & Advances',    'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Advance payments from customers'],
            ['code' => '2080', 'name' => 'Warranty Reserve',                'type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Estimated future warranty obligations'],
            ['code' => '2500', 'name' => 'Long-term Loans Payable',         'type' => 'liability', 'category' => 'Long-term Liabilities','description' => 'Bank loans and long-term debt'],
            ['code' => '2510', 'name' => 'Equipment Finance Leases',        'type' => 'liability', 'category' => 'Long-term Liabilities','description' => 'Finance lease obligations for equipment'],
            ['code' => '2520', 'name' => 'Deferred Tax Liability',          'type' => 'liability', 'category' => 'Long-term Liabilities','description' => 'Future tax obligations from timing differences'],

            // ── 3000s: Equity ──────────────────────────────────────────────
            ['code' => '3010', 'name' => 'Common Stock',                    'type' => 'equity',    'category' => 'Equity',              'description' => 'Par value of issued shares'],
            ['code' => '3020', 'name' => 'Additional Paid-in Capital',      'type' => 'equity',    'category' => 'Equity',              'description' => 'Excess over par value from stock issuances'],
            ['code' => '3030', 'name' => 'Retained Earnings',               'type' => 'equity',    'category' => 'Equity',              'description' => 'Cumulative undistributed profits'],
            ['code' => '3040', 'name' => 'Treasury Stock',                  'type' => 'equity',    'category' => 'Equity',              'description' => 'Repurchased company shares (contra)'],

            // ── 4000s: Revenue ─────────────────────────────────────────────
            ['code' => '4010', 'name' => 'Product Sales Revenue',           'type' => 'revenue',   'category' => 'Revenue',             'description' => 'Revenue from manufactured product sales'],
            ['code' => '4020', 'name' => 'Contract Manufacturing Revenue',  'type' => 'revenue',   'category' => 'Revenue',             'description' => 'Revenue from toll/contract manufacturing'],
            ['code' => '4030', 'name' => 'Parts & Components Revenue',      'type' => 'revenue',   'category' => 'Revenue',             'description' => 'Revenue from parts and spare sales'],
            ['code' => '4040', 'name' => 'Service & Repair Revenue',        'type' => 'revenue',   'category' => 'Revenue',             'description' => 'Revenue from maintenance and repair services'],
            ['code' => '4050', 'name' => 'Interest Income',                 'type' => 'revenue',   'category' => 'Other Income',        'description' => 'Interest earned on deposits'],
            ['code' => '4090', 'name' => 'Sales Returns & Allowances',      'type' => 'revenue',   'category' => 'Revenue',             'description' => 'Contra revenue for product returns'],

            // ── 5000s: Cost of Goods Sold ──────────────────────────────────
            ['code' => '5010', 'name' => 'Raw Materials Used',              'type' => 'expense',   'category' => 'COGS',                'description' => 'Cost of raw materials consumed in production'],
            ['code' => '5020', 'name' => 'Direct Labor',                    'type' => 'expense',   'category' => 'COGS',                'description' => 'Wages of production workers'],
            ['code' => '5030', 'name' => 'Direct Labor – Overtime',         'type' => 'expense',   'category' => 'COGS',                'description' => 'Overtime premiums for production workers'],
            ['code' => '5100', 'name' => 'Factory Overhead – Applied',      'type' => 'expense',   'category' => 'COGS',                'description' => 'Overhead allocated to production jobs'],
            ['code' => '5110', 'name' => 'Indirect Materials',              'type' => 'expense',   'category' => 'COGS',                'description' => 'Supplies used in production not in BOM'],
            ['code' => '5120', 'name' => 'Indirect Labor',                  'type' => 'expense',   'category' => 'COGS',                'description' => 'Factory supervisors and support staff'],
            ['code' => '5130', 'name' => 'Factory Utilities',               'type' => 'expense',   'category' => 'COGS',                'description' => 'Power, gas, and water used in production'],
            ['code' => '5140', 'name' => 'Factory Rent & Facilities',       'type' => 'expense',   'category' => 'COGS',                'description' => 'Factory and warehouse lease costs'],
            ['code' => '5150', 'name' => 'Machine Depreciation',            'type' => 'expense',   'category' => 'COGS',                'description' => 'Depreciation on production equipment'],
            ['code' => '5160', 'name' => 'Production Equipment Repair',     'type' => 'expense',   'category' => 'COGS',                'description' => 'Maintenance and repair of production equipment'],
            ['code' => '5170', 'name' => 'Quality Control Costs',           'type' => 'expense',   'category' => 'COGS',                'description' => 'Inspection, testing, and QC activities'],
            ['code' => '5180', 'name' => 'Scrap & Spoilage',                'type' => 'expense',   'category' => 'COGS',                'description' => 'Cost of defective production and material waste'],
            ['code' => '5190', 'name' => 'Freight-in',                      'type' => 'expense',   'category' => 'COGS',                'description' => 'Inbound shipping to receive materials'],
            ['code' => '5900', 'name' => 'Manufacturing Variance',          'type' => 'expense',   'category' => 'COGS',                'description' => 'Standard vs actual cost variances'],

            // ── 6000s: Operating Expenses ──────────────────────────────────
            ['code' => '6010', 'name' => 'Salaries – Management',           'type' => 'expense',   'category' => 'Payroll',             'description' => 'Executive and management salaries'],
            ['code' => '6020', 'name' => 'Salaries – Administration',       'type' => 'expense',   'category' => 'Payroll',             'description' => 'Administrative staff salaries'],
            ['code' => '6030', 'name' => 'Sales Commissions',               'type' => 'expense',   'category' => 'Sales & Marketing',   'description' => 'Commissions paid to sales staff'],
            ['code' => '6040', 'name' => 'Payroll Taxes & Benefits',        'type' => 'expense',   'category' => 'Payroll',             'description' => 'Employer payroll taxes and benefits'],
            ['code' => '6050', 'name' => 'Office Rent',                     'type' => 'expense',   'category' => 'Facilities',          'description' => 'Administrative office lease costs'],
            ['code' => '6060', 'name' => 'Office Utilities',                'type' => 'expense',   'category' => 'Facilities',          'description' => 'Electric, gas, and water for office'],
            ['code' => '6070', 'name' => 'Marketing & Advertising',         'type' => 'expense',   'category' => 'Sales & Marketing',   'description' => 'Advertising and promotions'],
            ['code' => '6080', 'name' => 'Trade Shows & Events',            'type' => 'expense',   'category' => 'Sales & Marketing',   'description' => 'Industry event and exhibition costs'],
            ['code' => '6090', 'name' => 'Professional Fees',               'type' => 'expense',   'category' => 'Administrative',      'description' => 'Legal, audit, and consulting fees'],
            ['code' => '6100', 'name' => 'Office Supplies',                 'type' => 'expense',   'category' => 'Administrative',      'description' => 'General office and admin supplies'],
            ['code' => '6110', 'name' => 'Insurance – Property',            'type' => 'expense',   'category' => 'Administrative',      'description' => 'Property and equipment insurance'],
            ['code' => '6120', 'name' => 'Insurance – Liability',           'type' => 'expense',   'category' => 'Administrative',      'description' => 'Product and general liability insurance'],
            ['code' => '6130', 'name' => 'Travel & Entertainment',          'type' => 'expense',   'category' => 'Administrative',      'description' => 'Business travel and client meetings'],
            ['code' => '6140', 'name' => 'IT & Software',                   'type' => 'expense',   'category' => 'Technology',          'description' => 'ERP, MES, and other software costs'],
            ['code' => '6150', 'name' => 'Research & Development',          'type' => 'expense',   'category' => 'R&D',                 'description' => 'Product development and engineering'],
            ['code' => '6160', 'name' => 'Training & Certification',        'type' => 'expense',   'category' => 'Payroll',             'description' => 'Employee training, safety, and certifications'],
            ['code' => '6170', 'name' => 'Bank & Finance Charges',          'type' => 'expense',   'category' => 'Administrative',      'description' => 'Bank fees and payment processing costs'],
            ['code' => '6900', 'name' => 'Miscellaneous Expense',           'type' => 'expense',   'category' => 'Administrative',      'description' => 'Uncategorized operating expenses'],

            // ── 7000s: Non-operating ───────────────────────────────────────
            ['code' => '7010', 'name' => 'Interest Expense',                'type' => 'expense',   'category' => 'Non-operating',       'description' => 'Interest on loans and credit facilities'],
            ['code' => '7020', 'name' => 'Depreciation – Non-production',   'type' => 'expense',   'category' => 'Non-operating',       'description' => 'Depreciation on non-production assets'],
            ['code' => '7030', 'name' => 'Amortization Expense',            'type' => 'expense',   'category' => 'Non-operating',       'description' => 'Amortization of patents and intangibles'],
            ['code' => '7040', 'name' => 'Income Tax Expense',              'type' => 'expense',   'category' => 'Non-operating',       'description' => 'Corporate income taxes'],
            ['code' => '7050', 'name' => 'Loss on Disposal of Assets',      'type' => 'expense',   'category' => 'Non-operating',       'description' => 'Losses on equipment disposals'],
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
