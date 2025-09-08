<?php
/**
 * TPT Free ERP - Finance Module
 * Complete financial management system with accounting, budgeting, and reporting
 */

class Finance extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main finance dashboard
     */
    public function index() {
        $this->requirePermission('finance.view');

        $data = [
            'title' => 'Finance Dashboard',
            'financial_overview' => $this->getFinancialOverview(),
            'cash_flow' => $this->getCashFlow(),
            'profit_loss' => $this->getProfitLoss(),
            'balance_sheet' => $this->getBalanceSheet(),
            'key_metrics' => $this->getKeyMetrics(),
            'recent_transactions' => $this->getRecentTransactions(),
            'pending_approvals' => $this->getPendingApprovals()
        ];

        $this->render('modules/finance/dashboard', $data);
    }

    /**
     * General ledger management
     */
    public function generalLedger() {
        $this->requirePermission('finance.general_ledger.view');

        $data = [
            'title' => 'General Ledger',
            'chart_of_accounts' => $this->getChartOfAccounts(),
            'ledger_entries' => $this->getLedgerEntries(),
            'journal_entries' => $this->getJournalEntries(),
            'account_balances' => $this->getAccountBalances(),
            'ledger_filters' => $this->getLedgerFilters(),
            'posting_rules' => $this->getPostingRules()
        ];

        $this->render('modules/finance/general_ledger', $data);
    }

    /**
     * Accounts payable
     */
    public function accountsPayable() {
        $this->requirePermission('finance.accounts_payable.view');

        $data = [
            'title' => 'Accounts Payable',
            'vendor_invoices' => $this->getVendorInvoices(),
            'payment_schedules' => $this->getPaymentSchedules(),
            'vendor_statements' => $this->getVendorStatements(),
            'payment_terms' => $this->getPaymentTerms(),
            'aging_report' => $this->getAgingReport(),
            'cash_requirements' => $this->getCashRequirements()
        ];

        $this->render('modules/finance/accounts_payable', $data);
    }

    /**
     * Accounts receivable
     */
    public function accountsReceivable() {
        $this->requirePermission('finance.accounts_receivable.view');

        $data = [
            'title' => 'Accounts Receivable',
            'customer_invoices' => $this->getCustomerInvoices(),
            'payment_receipts' => $this->getPaymentReceipts(),
            'customer_statements' => $this->getCustomerStatements(),
            'collection_policies' => $this->getCollectionPolicies(),
            'credit_limits' => $this->getCreditLimits(),
            'dunning_process' => $this->getDunningProcess()
        ];

        $this->render('modules/finance/accounts_receivable', $data);
    }

    /**
     * Budgeting and forecasting
     */
    public function budgeting() {
        $this->requirePermission('finance.budgeting.view');

        $data = [
            'title' => 'Budgeting & Forecasting',
            'budget_templates' => $this->getBudgetTemplates(),
            'budget_scenarios' => $this->getBudgetScenarios(),
            'forecast_models' => $this->getForecastModels(),
            'variance_analysis' => $this->getVarianceAnalysis(),
            'budget_approval_workflow' => $this->getBudgetApprovalWorkflow(),
            'rolling_forecasts' => $this->getRollingForecasts()
        ];

        $this->render('modules/finance/budgeting', $data);
    }

    /**
     * Financial reporting
     */
    public function reporting() {
        $this->requirePermission('finance.reporting.view');

        $data = [
            'title' => 'Financial Reporting',
            'income_statement' => $this->getIncomeStatement(),
            'balance_sheet_report' => $this->getBalanceSheetReport(),
            'cash_flow_statement' => $this->getCashFlowStatement(),
            'financial_ratios' => $this->getFinancialRatios(),
            'trend_analysis' => $this->getTrendAnalysis(),
            'comparative_reports' => $this->getComparativeReports(),
            'regulatory_reports' => $this->getRegulatoryReports()
        ];

        $this->render('modules/finance/reporting', $data);
    }

    /**
     * Tax management
     */
    public function taxManagement() {
        $this->requirePermission('finance.tax.view');

        $data = [
            'title' => 'Tax Management',
            'tax_codes' => $this->getTaxCodes(),
            'tax_rates' => $this->getTaxRates(),
            'tax_calculations' => $this->getTaxCalculations(),
            'tax_filings' => $this->getTaxFilings(),
            'tax_audit_trail' => $this->getTaxAuditTrail(),
            'tax_compliance' => $this->getTaxCompliance(),
            'tax_planning' => $this->getTaxPlanning()
        ];

        $this->render('modules/finance/tax_management', $data);
    }

    /**
     * Fixed assets management
     */
    public function fixedAssets() {
        $this->requirePermission('finance.fixed_assets.view');

        $data = [
            'title' => 'Fixed Assets',
            'asset_register' => $this->getAssetRegister(),
            'depreciation_schedule' => $this->getDepreciationSchedule(),
            'asset_disposals' => $this->getAssetDisposals(),
            'maintenance_costs' => $this->getMaintenanceCosts(),
            'asset_valuation' => $this->getAssetValuation(),
            'insurance_coverage' => $this->getInsuranceCoverage()
        ];

        $this->render('modules/finance/fixed_assets', $data);
    }

    /**
     * Multi-currency support
     */
    public function multiCurrency() {
        $this->requirePermission('finance.multi_currency.view');

        $data = [
            'title' => 'Multi-Currency Management',
            'currency_rates' => $this->getCurrencyRates(),
            'exchange_rate_history' => $this->getExchangeRateHistory(),
            'currency_conversion' => $this->getCurrencyConversion(),
            'hedging_strategies' => $this->getHedgingStrategies(),
            'currency_risk' => $this->getCurrencyRisk(),
            'translation_adjustments' => $this->getTranslationAdjustments()
        ];

        $this->render('modules/finance/multi_currency', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getFinancialOverview() {
        return $this->db->querySingle("
            SELECT
                SUM(CASE WHEN account_type = 'asset' THEN balance ELSE 0 END) as total_assets,
                SUM(CASE WHEN account_type = 'liability' THEN balance ELSE 0 END) as total_liabilities,
                SUM(CASE WHEN account_type = 'equity' THEN balance ELSE 0 END) as total_equity,
                SUM(CASE WHEN account_type = 'revenue' THEN balance ELSE 0 END) as total_revenue,
                SUM(CASE WHEN account_type = 'expense' THEN balance ELSE 0 END) as total_expenses,
                (SUM(CASE WHEN account_type = 'revenue' THEN balance ELSE 0 END) - SUM(CASE WHEN account_type = 'expense' THEN balance ELSE 0 END)) as net_income,
                COUNT(DISTINCT CASE WHEN transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN transaction_id END) as recent_transactions
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCashFlow() {
        return $this->db->query("
            SELECT
                DATE(transaction_date) as date,
                SUM(CASE WHEN transaction_type = 'cash_inflow' THEN amount ELSE 0 END) as cash_inflow,
                SUM(CASE WHEN transaction_type = 'cash_outflow' THEN amount ELSE 0 END) as cash_outflow,
                SUM(CASE WHEN transaction_type = 'cash_inflow' THEN amount ELSE 0 END) - SUM(CASE WHEN transaction_type = 'cash_outflow' THEN amount ELSE 0 END) as net_cash_flow,
                SUM(SUM(CASE WHEN transaction_type = 'cash_inflow' THEN amount ELSE 0 END) - SUM(CASE WHEN transaction_type = 'cash_outflow' THEN amount ELSE 0 END)) OVER (ORDER BY DATE(transaction_date)) as cumulative_cash_flow
            FROM financial_transactions
            WHERE company_id = ? AND transaction_date >= ?
            GROUP BY DATE(transaction_date)
            ORDER BY date DESC
            LIMIT 30
        ", [
            $this->user['company_id'],
            date('Y-m-d', strtotime('-30 days'))
        ]);
    }

    private function getProfitLoss() {
        return $this->db->querySingle("
            SELECT
                SUM(CASE WHEN account_type = 'revenue' THEN balance ELSE 0 END) as total_revenue,
                SUM(CASE WHEN account_type = 'cost_of_goods_sold' THEN balance ELSE 0 END) as cost_of_goods_sold,
                SUM(CASE WHEN account_type = 'revenue' THEN balance ELSE 0 END) - SUM(CASE WHEN account_type = 'cost_of_goods_sold' THEN balance ELSE 0 END) as gross_profit,
                SUM(CASE WHEN account_type = 'operating_expense' THEN balance ELSE 0 END) as operating_expenses,
                SUM(CASE WHEN account_type = 'revenue' THEN balance ELSE 0 END) - SUM(CASE WHEN account_type = 'cost_of_goods_sold' THEN balance ELSE 0 END) - SUM(CASE WHEN account_type = 'operating_expense' THEN balance ELSE 0 END) as operating_profit,
                SUM(CASE WHEN account_type = 'interest_expense' THEN balance ELSE 0 END) as interest_expense,
                SUM(CASE WHEN account_type = 'tax_expense' THEN balance ELSE 0 END) as tax_expense,
                SUM(CASE WHEN account_type = 'revenue' THEN balance ELSE 0 END) - SUM(CASE WHEN account_type = 'cost_of_goods_sold' THEN balance ELSE 0 END) - SUM(CASE WHEN account_type = 'operating_expense' THEN balance ELSE 0 END) - SUM(CASE WHEN account_type = 'interest_expense' THEN balance ELSE 0 END) - SUM(CASE WHEN account_type = 'tax_expense' THEN balance ELSE 0 END) as net_profit
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBalanceSheet() {
        return $this->db->query("
            SELECT
                fa.account_type,
                fa.account_name,
                ab.balance,
                fa.account_category,
                fa.account_subcategory
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ? AND fa.account_type IN ('asset', 'liability', 'equity')
            ORDER BY
                CASE fa.account_type
                    WHEN 'asset' THEN 1
                    WHEN 'liability' THEN 2
                    WHEN 'equity' THEN 3
                END,
                fa.account_category ASC,
                fa.account_name ASC
        ", [$this->user['company_id']]);
    }

    private function getKeyMetrics() {
        return [
            'current_ratio' => $this->calculateCurrentRatio(),
            'quick_ratio' => $this->calculateQuickRatio(),
            'debt_to_equity' => $this->calculateDebtToEquity(),
            'return_on_assets' => $this->calculateReturnOnAssets(),
            'return_on_equity' => $this->calculateReturnOnEquity(),
            'gross_margin' => $this->calculateGrossMargin(),
            'operating_margin' => $this->calculateOperatingMargin(),
            'net_margin' => $this->calculateNetMargin()
        ];
    }

    private function calculateCurrentRatio() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_category = 'current_asset' THEN ab.balance ELSE 0 END) as current_assets,
                SUM(CASE WHEN fa.account_category = 'current_liability' THEN ab.balance ELSE 0 END) as current_liabilities
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['current_liabilities'] > 0 ? $result['current_assets'] / $result['current_liabilities'] : 0;
    }

    private function calculateQuickRatio() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_category = 'current_asset' AND fa.account_name NOT LIKE '%inventory%' THEN ab.balance ELSE 0 END) as quick_assets,
                SUM(CASE WHEN fa.account_category = 'current_liability' THEN ab.balance ELSE 0 END) as current_liabilities
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['current_liabilities'] > 0 ? $result['quick_assets'] / $result['current_liabilities'] : 0;
    }

    private function calculateDebtToEquity() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_type = 'liability' THEN ab.balance ELSE 0 END) as total_liabilities,
                SUM(CASE WHEN fa.account_type = 'equity' THEN ab.balance ELSE 0 END) as total_equity
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['total_equity'] > 0 ? $result['total_liabilities'] / $result['total_equity'] : 0;
    }

    private function calculateReturnOnAssets() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_type = 'revenue' THEN ab.balance ELSE 0 END) - SUM(CASE WHEN fa.account_type = 'expense' THEN ab.balance ELSE 0 END) as net_income,
                SUM(CASE WHEN fa.account_type = 'asset' THEN ab.balance ELSE 0 END) as total_assets
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['total_assets'] > 0 ? $result['net_income'] / $result['total_assets'] : 0;
    }

    private function calculateReturnOnEquity() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_type = 'revenue' THEN ab.balance ELSE 0 END) - SUM(CASE WHEN fa.account_type = 'expense' THEN ab.balance ELSE 0 END) as net_income,
                SUM(CASE WHEN fa.account_type = 'equity' THEN ab.balance ELSE 0 END) as total_equity
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['total_equity'] > 0 ? $result['net_income'] / $result['total_equity'] : 0;
    }

    private function calculateGrossMargin() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_type = 'revenue' THEN ab.balance ELSE 0 END) as total_revenue,
                SUM(CASE WHEN fa.account_type = 'cost_of_goods_sold' THEN ab.balance ELSE 0 END) as cost_of_goods_sold
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['total_revenue'] > 0 ? (($result['total_revenue'] - $result['cost_of_goods_sold']) / $result['total_revenue']) * 100 : 0;
    }

    private function calculateOperatingMargin() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_type = 'revenue' THEN ab.balance ELSE 0 END) as total_revenue,
                SUM(CASE WHEN fa.account_type = 'operating_expense' THEN ab.balance ELSE 0 END) as operating_expenses
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['total_revenue'] > 0 ? (($result['total_revenue'] - $result['operating_expenses']) / $result['total_revenue']) * 100 : 0;
    }

    private function calculateNetMargin() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_type = 'revenue' THEN ab.balance ELSE 0 END) as total_revenue,
                SUM(CASE WHEN fa.account_type = 'revenue' THEN ab.balance ELSE 0 END) - SUM(CASE WHEN fa.account_type = 'expense' THEN ab.balance ELSE 0 END) as net_income
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['total_revenue'] > 0 ? ($result['net_income'] / $result['total_revenue']) * 100 : 0;
    }

    private function getRecentTransactions() {
        return $this->db->query("
            SELECT
                ft.*,
                ft.transaction_description,
                ft.transaction_type,
                ft.amount,
                ft.transaction_date,
                fa.account_name,
                u.first_name,
                u.last_name
            FROM financial_transactions ft
            LEFT JOIN financial_accounts fa ON ft.account_id = fa.id
            LEFT JOIN users u ON ft.created_by = u.id
            WHERE ft.company_id = ?
            ORDER BY ft.transaction_date DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getPendingApprovals() {
        return $this->db->query("
            SELECT
                pa.*,
                pa.approval_type,
                pa.description,
                pa.amount,
                pa.requested_date,
                u1.first_name as requester_name,
                u2.first_name as approver_name
            FROM pending_approvals pa
            LEFT JOIN users u1 ON pa.requested_by = u1.id
            LEFT JOIN users u2 ON pa.assigned_to = u2.id
            WHERE pa.company_id = ? AND pa.status = 'pending'
            ORDER BY pa.requested_date DESC
        ", [$this->user['company_id']]);
    }

    private function getChartOfAccounts() {
        return $this->db->query("
            SELECT
                fa.*,
                fa.account_code,
                fa.account_name,
                fa.account_type,
                fa.account_category,
                fa.account_subcategory,
                fa.is_active,
                ab.balance,
                ab.last_updated
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
            ORDER BY fa.account_code ASC
        ", [$this->user['company_id']]);
    }

    private function getLedgerEntries() {
        return $this->db->query("
            SELECT
                le.*,
                le.entry_date,
                le.description,
                le.debit_amount,
                le.credit_amount,
                fa.account_name,
                je.journal_reference,
                u.first_name,
                u.last_name
            FROM ledger_entries le
            LEFT JOIN financial_accounts fa ON le.account_id = fa.id
            LEFT JOIN journal_entries je ON le.journal_entry_id = je.id
            LEFT JOIN users u ON le.created_by = u.id
            WHERE le.company_id = ?
            ORDER BY le.entry_date DESC, le.id DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getJournalEntries() {
        return $this->db->query("
            SELECT
                je.*,
                je.journal_reference,
                je.entry_date,
                je.description,
                je.total_debit,
                je.total_credit,
                je.status,
                u.first_name,
                u.last_name,
                COUNT(le.id) as line_count
            FROM journal_entries je
            LEFT JOIN users u ON je.created_by = u.id
            LEFT JOIN ledger_entries le ON je.id = le.journal_entry_id
            WHERE je.company_id = ?
            GROUP BY je.id
            ORDER BY je.entry_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAccountBalances() {
        return $this->db->query("
            SELECT
                fa.account_code,
                fa.account_name,
                fa.account_type,
                ab.balance,
                ab.last_updated,
                ab.opening_balance,
                ab.closing_balance
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
            ORDER BY fa.account_code ASC
        ", [$this->user['company_id']]);
    }

    private function getLedgerFilters() {
        return [
            'date_ranges' => [
                'today' => 'Today',
                'this_week' => 'This Week',
                'this_month' => 'This Month',
                'this_quarter' => 'This Quarter',
                'this_year' => 'This Year',
                'custom' => 'Custom Range'
            ],
            'account_types' => [
                'asset' => 'Assets',
                'liability' => 'Liabilities',
                'equity' => 'Equity',
                'revenue' => 'Revenue',
                'expense' => 'Expenses'
            ],
            'transaction_types' => [
                'journal_entry' => 'Journal Entries',
                'invoice' => 'Invoices',
                'payment' => 'Payments',
                'adjustment' => 'Adjustments'
            ]
        ];
    }

    private function getPostingRules() {
        return $this->db->query("
            SELECT
                pr.*,
                pr.rule_name,
                pr.transaction_type,
                pr.debit_account,
                pr.credit_account,
                pr.is_active,
                pr.last_used
            FROM posting_rules pr
            WHERE pr.company_id = ?
            ORDER BY pr.is_active DESC, pr.last_used DESC
        ", [$this->user['company_id']]);
    }

    private function getVendorInvoices() {
        return $this->db->query("
            SELECT
                vi.*,
                vi.invoice_number,
                vi.vendor_name,
                vi.invoice_date,
                vi.due_date,
                vi.amount,
                vi.status,
                vi.payment_terms,
                DATEDIFF(vi.due_date, CURDATE()) as days_until_due
            FROM vendor_invoices vi
            WHERE vi.company_id = ?
            ORDER BY vi.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getPaymentSchedules() {
        return $this->db->query("
            SELECT
                ps.*,
                ps.vendor_name,
                ps.payment_amount,
                ps.scheduled_date,
                ps.payment_method,
                ps.status,
                ps.approved_by
            FROM payment_schedules ps
            WHERE ps.company_id = ?
            ORDER BY ps.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getVendorStatements() {
        return $this->db->query("
            SELECT
                vs.*,
                vs.vendor_name,
                vs.statement_period,
                vs.opening_balance,
                vs.closing_balance,
                vs.total_invoices,
                vs.total_payments,
                vs.outstanding_balance
            FROM vendor_statements vs
            WHERE vs.company_id = ?
            ORDER BY vs.statement_period DESC
        ", [$this->user['company_id']]);
    }

    private function getPaymentTerms() {
        return [
            'net_15' => [
                'name' => 'Net 15',
                'description' => 'Payment due in 15 days',
                'discount' => 0
            ],
            'net_30' => [
                'name' => 'Net 30',
                'description' => 'Payment due in 30 days',
                'discount' => 0
            ],
            'net_45' => [
                'name' => 'Net 45',
                'description' => 'Payment due in 45 days',
                'discount' => 0
            ],
            '2_10_net_30' => [
                'name' => '2/10 Net 30',
                'description' => '2% discount if paid within 10 days, otherwise due in 30 days',
                'discount' => 2
            ]
        ];
    }

    private function getAgingReport() {
        return $this->db->query("
            SELECT
                CASE
                    WHEN DATEDIFF(CURDATE(), vi.due_date) <= 30 THEN 'current'
                    WHEN DATEDIFF(CURDATE(), vi.due_date) <= 60 THEN '31_60_days'
                    WHEN DATEDIFF(CURDATE(), vi.due_date) <= 90 THEN '61_90_days'
                    ELSE 'over_90_days'
                END as aging_category,
                COUNT(*) as invoice_count,
                SUM(vi.amount) as total_amount
            FROM vendor_invoices vi
            WHERE vi.company_id = ? AND vi.status = 'unpaid'
            GROUP BY
                CASE
                    WHEN DATEDIFF(CURDATE(), vi.due_date) <= 30 THEN 'current'
                    WHEN DATEDIFF(CURDATE(), vi.due_date) <= 60 THEN '31_60_days'
                    WHEN DATEDIFF(CURDATE(), vi.due_date) <= 90 THEN '61_90_days'
                    ELSE 'over_90_days'
                END
        ", [$this->user['company_id']]);
    }

    private function getCashRequirements() {
        return $this->db->querySingle("
            SELECT
                SUM(CASE WHEN DATEDIFF(vi.due_date, CURDATE()) <= 7 THEN vi.amount ELSE 0 END) as due_this_week,
                SUM(CASE WHEN DATEDIFF(vi.due_date, CURDATE()) <= 30 THEN vi.amount ELSE 0 END) as due_this_month,
                SUM(CASE WHEN DATEDIFF(vi.due_date, CURDATE()) <= 90 THEN vi.amount ELSE 0 END) as due_next_quarter,
                AVG(vi.amount) as avg_invoice_amount,
                COUNT(*) as total_outstanding_invoices
            FROM vendor_invoices vi
            WHERE vi.company_id = ? AND vi.status = 'unpaid'
        ", [$this->user['company_id']]);
    }

    private function getCustomerInvoices() {
        return $this->db->query("
            SELECT
                ci.*,
                ci.invoice_number,
                ci.customer_name,
                ci.invoice_date,
                ci.due_date,
                ci.amount,
                ci.status,
                ci.payment_terms,
                DATEDIFF(ci.due_date, CURDATE()) as days_until_due
            FROM customer_invoices ci
            WHERE ci.company_id = ?
            ORDER BY ci.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getPaymentReceipts() {
        return $this->db->query("
            SELECT
                pr.*,
                pr.receipt_number,
                pr.customer_name,
                pr.payment_date,
                pr.amount,
                pr.payment_method,
                pr.reference_number
            FROM payment_receipts pr
            WHERE pr.company_id = ?
            ORDER BY pr.payment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerStatements() {
        return $this->db->query("
            SELECT
                cs.*,
                cs.customer_name,
                cs.statement_period,
                cs.opening_balance,
                cs.closing_balance,
                cs.total_invoices,
                cs.total_payments,
                cs.outstanding_balance
            FROM customer_statements cs
            WHERE cs.company_id = ?
            ORDER BY cs.statement_period DESC
        ", [$this->user['company_id']]);
    }

    private function getCollectionPolicies() {
        return $this->db->query("
            SELECT
                cp.*,
                cp.policy_name,
                cp.days_overdue,
                cp.action_required,
                cp.notification_template,
                cp.is_active
            FROM collection_policies cp
            WHERE cp.company_id = ?
            ORDER BY cp.days_overdue ASC
        ", [$this->user['company_id']]);
    }

    private function getCreditLimits() {
        return $this->db->query("
            SELECT
                cl.*,
                cl.customer_name,
                cl.credit_limit,
                cl.current_balance,
                cl.available_credit,
                cl.last_review_date,
                cl.next_review_date
            FROM credit_limits cl
            WHERE cl.company_id = ?
            ORDER BY cl.available_credit ASC
        ", [$this->user['company_id']]);
    }

    private function getDunningProcess() {
        return $this->db->query("
            SELECT
                dp.*,
                dp.customer_name,
                dp.invoice_number,
                dp.days_overdue,
                dp.dunning_level,
                dp.last_contact,
                dp.next_action,
                dp.amount_due
            FROM dunning_process dp
            WHERE dp.company_id = ?
            ORDER BY dp.days_overdue DESC
        ", [$this->user['company_id']]);
    }

    private function getBudgetTemplates() {
        return [
            'operating_budget' => [
                'name' => 'Operating Budget',
                'description' => 'Monthly operating expenses and revenue',
                'categories' => ['revenue', 'cost_of_goods_sold', 'operating_expenses', 'capital_expenditures'],
                'frequency' => 'monthly'
            ],
            'capital_budget' => [
                'name' => 'Capital Budget',
                'description' => 'Capital expenditures and investments',
                'categories' => ['equipment', 'facilities', 'technology', 'vehicles'],
                'frequency' => 'annual'
            ],
            'cash_flow_budget' => [
                'name' => 'Cash Flow Budget',
                'description' => 'Cash inflows and outflows',
                'categories' => ['operating_cash_flow', 'investing_cash_flow', 'financing_cash_flow'],
                'frequency' => 'monthly'
            ]
        ];
    }

    private function getBudgetScenarios() {
        return $this->db->query("
            SELECT
                bs.*,
                bs.scenario_name,
                bs.budget_type,
                bs.fiscal_year,
                bs.total_budget,
                bs.status,
                bs.created_by,
                bs.last_updated
            FROM budget_scenarios bs
            WHERE bs.company_id = ?
            ORDER BY bs.fiscal_year DESC, bs.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getForecastModels() {
        return $this->db->query("
            SELECT
                fm.*,
                fm.model_name,
                fm.forecast_type,
                fm.accuracy_percentage,
                fm.last_run,
                fm.next_run,
                fm.is_active
            FROM forecast_models fm
            WHERE fm.company_id = ?
            ORDER BY fm.is_active DESC, fm.last_run DESC
        ", [$this->user['company_id']]);
    }

    private function getVarianceAnalysis() {
        return $this->db->query("
            SELECT
                va.*,
                va.budget_category,
                va.budgeted_amount,
                va.actual_amount,
                va.variance_amount,
                va.variance_percentage,
                va.variance_type,
                va.explanation
            FROM variance_analysis va
            WHERE va.company_id = ?
            ORDER BY ABS(va.variance_percentage) DESC
        ", [$this->user['company_id']]);
    }

    private function getBudgetApprovalWorkflow() {
        return $this->db->query("
            SELECT
                baw.*,
                baw.budget_name,
                baw.submitted_amount,
                baw.approved_amount,
                baw.status,
                baw.submitted_by,
                baw.approved_by,
                baw.submission_date,
                baw.approval_date
            FROM budget_approval_workflow baw
            WHERE baw.company_id = ?
            ORDER BY baw.submission_date DESC
        ", [$this->user['company_id']]);
    }

    private function getRollingForecasts() {
        return $this->db->query("
            SELECT
                rf.*,
                rf.forecast_period,
                rf.forecast_type,
                rf.predicted_value,
                rf.confidence_level,
                rf.actual_value,
                rf.accuracy_percentage
            FROM rolling_forecasts rf
            WHERE rf.company_id = ?
            ORDER BY rf.forecast_period DESC
        ", [$this->user['company_id']]);
    }

    private function getIncomeStatement() {
        return $this->db->query("
            SELECT
                fa.account_category,
                fa.account_name,
                ab.balance,
                fa.account_type
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ? AND fa.account_type IN ('revenue', 'expense', 'cost_of_goods_sold')
            ORDER BY
                CASE fa.account_type
                    WHEN 'revenue' THEN 1
                    WHEN 'cost_of_goods_sold' THEN 2
                    WHEN 'expense' THEN 3
                END,
                fa.account_category ASC
        ", [$this->user['company_id']]);
    }

    private function getBalanceSheetReport() {
        return $this->db->query("
            SELECT
                fa.account_type,
                fa.account_category,
                fa.account_name,
                ab.balance
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ? AND fa.account_type IN ('asset', 'liability', 'equity')
            ORDER BY
                CASE fa.account_type
                    WHEN 'asset' THEN 1
                    WHEN 'liability' THEN 2
                    WHEN 'equity' THEN 3
                END,
                fa.account_category ASC
        ", [$this->user['company_id']]);
    }

    private function getCashFlowStatement() {
        return $this->db->query("
            SELECT
                cfs.cash_flow_category,
                cfs.activity_type,
                cfs.amount,
                cfs.description
            FROM cash_flow_statement cfs
            WHERE cfs.company_id = ?
            ORDER BY cfs.cash_flow_category ASC, cfs.activity_type ASC
        ", [$this->user['company_id']]);
    }

    private function getFinancialRatios() {
        return [
            'liquidity_ratios' => [
                'current_ratio' => $this->calculateCurrentRatio(),
                'quick_ratio' => $this->calculateQuickRatio(),
                'cash_ratio' => $this->calculateCashRatio()
            ],
            'leverage_ratios' => [
                'debt_ratio' => $this->calculateDebtRatio(),
                'debt_to_equity' => $this->calculateDebtToEquity(),
                'interest_coverage' => $this->calculateInterestCoverage()
            ],
            'profitability_ratios' => [
                'gross_margin' => $this->calculateGrossMargin(),
                'operating_margin' => $this->calculateOperatingMargin(),
                'net_margin' => $this->calculateNetMargin(),
                'return_on_assets' => $this->calculateReturnOnAssets(),
                'return_on_equity' => $this->calculateReturnOnEquity()
            ],
            'efficiency_ratios' => [
                'asset_turnover' => $this->calculateAssetTurnover(),
                'inventory_turnover' => $this->calculateInventoryTurnover(),
                'receivables_turnover' => $this->calculateReceivablesTurnover()
            ]
        ];
    }

    private function calculateCashRatio() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_name LIKE '%cash%' THEN ab.balance ELSE 0 END) as cash_equivalents,
                SUM(CASE WHEN fa.account_category = 'current_liability' THEN ab.balance ELSE 0 END) as current_liabilities
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['current_liabilities'] > 0 ? $result['cash_equivalents'] / $result['current_liabilities'] : 0;
    }

    private function calculateDebtRatio() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_type = 'liability' THEN ab.balance ELSE 0 END) as total_liabilities,
                SUM(CASE WHEN fa.account_type = 'asset' THEN ab.balance ELSE 0 END) as total_assets
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['total_assets'] > 0 ? $result['total_liabilities'] / $result['total_assets'] : 0;
    }

    private function calculateInterestCoverage() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_type = 'revenue' THEN ab.balance ELSE 0 END) - SUM(CASE WHEN fa.account_type = 'expense' THEN ab.balance ELSE 0 END) as ebit,
                SUM(CASE WHEN fa.account_name LIKE '%interest%' THEN ab.balance ELSE 0 END) as interest_expense
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['interest_expense'] > 0 ? $result['ebit'] / $result['interest_expense'] : 0;
    }

    private function calculateAssetTurnover() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_type = 'revenue' THEN ab.balance ELSE 0 END) as total_revenue,
                SUM(CASE WHEN fa.account_type = 'asset' THEN ab.balance ELSE 0 END) as total_assets
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['total_assets'] > 0 ? $result['total_revenue'] / $result['total_assets'] : 0;
    }

    private function calculateInventoryTurnover() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_type = 'cost_of_goods_sold' THEN ab.balance ELSE 0 END) as cost_of_goods_sold,
                SUM(CASE WHEN fa.account_name LIKE '%inventory%' THEN ab.balance ELSE 0 END) as avg_inventory
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['avg_inventory'] > 0 ? $result['cost_of_goods_sold'] / $result['avg_inventory'] : 0;
    }

    private function calculateReceivablesTurnover() {
        $result = $this->db->querySingle("
            SELECT
                SUM(CASE WHEN fa.account_type = 'revenue' THEN ab.balance ELSE 0 END) as total_revenue,
                SUM(CASE WHEN fa.account_name LIKE '%receivable%' THEN ab.balance ELSE 0 END) as avg_receivables
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);

        return $result['avg_receivables'] > 0 ? $result['total_revenue'] / $result['avg_receivables'] : 0;
    }

    private function getTrendAnalysis() {
        return $this->db->query("
            SELECT
                DATE(created_at) as period,
                SUM(CASE WHEN account_type = 'revenue' THEN balance ELSE 0 END) as revenue,
                SUM(CASE WHEN account_type = 'expense' THEN balance ELSE 0 END) as expenses,
                SUM(CASE WHEN account_type = 'revenue' THEN balance ELSE 0 END) - SUM(CASE WHEN account_type = 'expense' THEN balance ELSE 0 END) as net_income
            FROM financial_accounts fa
            LEFT JOIN account_balances ab ON fa.id = ab.account_id
            WHERE fa.company_id = ?
            GROUP BY DATE(created_at)
            ORDER BY period DESC
            LIMIT 12
        ", [$this->user['company_id']]);
    }

    private function getComparativeReports() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.report_period,
                cr.current_period_amount,
                cr.previous_period_amount,
                cr.percentage_change,
                cr.report_type
            FROM comparative_reports cr
            WHERE cr.company_id = ?
            ORDER BY cr.report_period DESC
        ", [$this->user['company_id']]);
    }

    private function getRegulatoryReports() {
        return $this->db->query("
            SELECT
                rr.*,
                rr.report_name,
                rr.regulatory_body,
                rr.filing_deadline,
                rr.status,
                rr.last_filed
            FROM regulatory_reports rr
            WHERE rr.company_id = ?
            ORDER BY rr.filing_deadline ASC
        ", [$this->user['company_id']]);
    }

    private function getTaxCodes() {
        return $this->db->query("
            SELECT
                tc.*,
                tc.tax_code,
                tc.tax_rate,
                tc.description,
                tc.is_active,
                tc.last_updated
            FROM tax_codes tc
            WHERE tc.company_id = ?
            ORDER BY tc.tax_code ASC
        ", [$this->user['company_id']]);
    }

    private function getTaxRates() {
        return $this->db->query("
            SELECT
                tr.*,
                tr.jurisdiction,
                tr.tax_type,
                tr.rate_percentage,
                tr.effective_date,
                tr.expiration_date
            FROM tax_rates tr
            WHERE tr.company_id = ?
            ORDER BY tr.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getTaxCalculations() {
        return $this->db->query("
            SELECT
                tc.*,
                tc.transaction_id,
                tc.taxable_amount,
                tc.tax_amount,
                tc.tax_code_used,
                tc.calculation_date
            FROM tax_calculations tc
            WHERE tc.company_id = ?
            ORDER BY tc.calculation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getTaxFilings() {
        return $this->db->query("
            SELECT
                tf.*,
                tf.filing_period,
                tf.tax_authority,
                tf.amount_due,
                tf.filing_status,
                tf.due_date,
                tf.filed_date
            FROM tax_filings tf
            WHERE tf.company_id = ?
            ORDER BY tf.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getTaxAuditTrail() {
        return $this->db->query("
            SELECT
                tat.*,
                tat.action,
                tat.transaction_id,
                tat.old_value,
                tat.new_value,
                tat.user_id,
                tat.timestamp
            FROM tax_audit_trail tat
            WHERE tat.company_id = ?
            ORDER BY tat.timestamp DESC
        ", [$this->user['company_id']]);
    }

    private function getTaxCompliance() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_filings,
                COUNT(CASE WHEN filing_status = 'on_time' THEN 1 END) as on_time_filings,
                COUNT(CASE WHEN filing_status = 'late' THEN 1 END) as late_filings,
                ROUND((COUNT(CASE WHEN filing_status = 'on_time' THEN 1 END) / NULLIF(COUNT(*), 0)) * 100, 2) as compliance_rate,
                MAX(filed_date) as last_filing_date,
                MIN(due_date) as next_due_date
            FROM tax_filings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTaxPlanning() {
        return $this->db->query("
            SELECT
                tp.*,
                tp.strategy_name,
                tp.potential_savings,
                tp.implementation_complexity,
                tp.time_horizon,
                tp.status
            FROM tax_planning tp
            WHERE tp.company_id = ?
            ORDER BY tp.potential_savings DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetRegister() {
        return $this->db->query("
            SELECT
                ar.*,
                ar.asset_name,
                ar.asset_category,
                ar.purchase_date,
                ar.purchase_cost,
                ar.current_value,
                ar.depreciation_method,
                ar.useful_life_years
            FROM asset_register ar
            WHERE ar.company_id = ?
            ORDER BY ar.purchase_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationSchedule() {
        return $this->db->query("
            SELECT
                ds.*,
                ds.asset_id,
                ds.depreciation_period,
                ds.depreciation_amount,
                ds.accumulated_depreciation,
                ds.book_value,
                ds.depreciation_date
            FROM depreciation_schedule ds
            WHERE ds.company_id = ?
            ORDER BY ds.depreciation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetDisposals() {
        return $this->db->query("
            SELECT
                ad.*,
                ad.asset_id,
                ad.disposal_date,
                ad.disposal_value,
                ad.disposal_method,
                ad.gain_loss_amount,
                ad.reason_for_disposal
            FROM asset_disposals ad
            WHERE ad.company_id = ?
            ORDER BY ad.disposal_date DESC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceCosts() {
        return $this->db->query("
            SELECT
                mc.*,
                mc.asset_id,
                mc.maintenance_date,
                mc.maintenance_type,
                mc.cost,
                mc.description,
                mc.next_maintenance_date
            FROM maintenance_costs mc
            WHERE mc.company_id = ?
            ORDER BY mc.maintenance_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetValuation() {
        return $this->db->query("
            SELECT
                av.*,
                av.asset_id,
                av.valuation_date,
                av.market_value,
                av.book_value,
                av.appraisal_method,
                av.appraiser_name
            FROM asset_valuation av
            WHERE av.company_id = ?
            ORDER BY av.valuation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getInsuranceCoverage() {
        return $this->db->query("
            SELECT
                ic.*,
                ic.asset_id,
                ic.insurance_policy,
                ic.coverage_amount,
                ic.premium_amount,
                ic.coverage_start_date,
                ic.coverage_end_date,
                ic.insurance_provider
            FROM insurance_coverage ic
            WHERE ic.company_id = ?
            ORDER BY ic.coverage_end_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyRates() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.from_currency,
                cr.to_currency,
                cr.exchange_rate,
                cr.effective_date,
                cr.source
            FROM currency_rates cr
            WHERE cr.company_id = ?
            ORDER BY cr.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getExchangeRateHistory() {
        return $this->db->query("
            SELECT
                erh.*,
                erh.currency_pair,
                erh.exchange_rate,
                erh.date,
                erh.source
            FROM exchange_rate_history erh
            WHERE erh.company_id = ?
            ORDER BY erh.date DESC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyConversion() {
        return $this->db->query("
            SELECT
                cc.*,
                cc.transaction_id,
                cc.original_amount,
                cc.original_currency,
                cc.converted_amount,
                cc.converted_currency,
                cc.exchange_rate_used,
                cc.conversion_date
            FROM currency_conversion cc
            WHERE cc.company_id = ?
            ORDER BY cc.conversion_date DESC
        ", [$this->user['company_id']]);
    }

    private function getHedgingStrategies() {
        return $this->db->query("
            SELECT
                hs.*,
                hs.strategy_name,
                hs.currency_pair,
                hs.contract_amount,
                hs.maturity_date,
                hs.hedge_effectiveness,
                hs.status
            FROM hedging_strategies hs
            WHERE hs.company_id = ?
            ORDER BY hs.maturity_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyRisk() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_exposure,
                SUM(CASE WHEN exposure_type = 'transaction' THEN amount ELSE 0 END) as transaction_exposure,
                SUM(CASE WHEN exposure_type = 'translation' THEN amount ELSE 0 END) as translation_exposure,
                SUM(CASE WHEN exposure_type = 'economic' THEN amount ELSE 0 END) as economic_exposure,
                AVG(volatility_percentage) as avg_volatility,
                MAX(volatility_percentage) as max_volatility
            FROM currency_risk
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTranslationAdjustments() {
        return $this->db->query("
            SELECT
                ta.*,
                ta.account_id,
                ta.original_amount,
                ta.adjusted_amount,
                ta.exchange_rate_used,
                ta.adjustment_date,
                ta.adjustment_reason
            FROM translation_adjustments ta
            WHERE ta.company_id = ?
            ORDER BY ta.adjustment_date DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function createJournalEntry() {
        $this->requirePermission('finance.journal_entry.create');

        $data = $this->validateRequest([
            'reference' => 'required|string',
            'date' => 'required|date',
            'description' => 'required|string',
            'entries' => 'required|array'
        ]);

        try {
            // Start transaction
            $this->db->beginTransaction();

            // Create journal entry
            $journalId = $this->db->insert('journal_entries', [
                'company_id' => $this->user['company_id'],
                'journal_reference' => $data['reference'],
                'entry_date' => $data['date'],
                'description' => $data['description'],
                'status' => 'posted',
                'created_by' => $this->user['id']
            ]);

            $totalDebit = 0;
            $totalCredit = 0;

            // Create ledger entries
            foreach ($data['entries'] as $entry) {
                $debitAmount = $entry['debit_amount'] ?? 0;
                $creditAmount = $entry['credit_amount'] ?? 0;

                $this->db->insert('ledger_entries', [
                    'company_id' => $this->user['company_id'],
                    'journal_entry_id' => $journalId,
                    'account_id' => $entry['account_id'],
                    'entry_date' => $data['date'],
                    'description' => $entry['description'] ?? $data['description'],
                    'debit_amount' => $debitAmount,
                    'credit_amount' => $creditAmount,
                    'created_by' => $this->user['id']
                ]);

                $totalDebit += $debitAmount;
                $totalCredit += $creditAmount;
            }

            // Validate that debits equal credits
            if (abs($totalDebit - $totalCredit) > 0.01) {
                $this->db->rollback();
                $this->jsonResponse(['error' => 'Debits must equal credits'], 400);
            }

            // Update journal entry totals
            $this->db->update('journal_entries', [
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit
            ], 'id = ?', [$journalId]);

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'journal_id' => $journalId,
                'message' => 'Journal entry created successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createInvoice() {
        $this->requirePermission('finance.invoice.create');

        $data = $this->validateRequest([
            'customer_id' => 'required|integer',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'items' => 'required|array',
            'tax_rate' => 'numeric',
            'notes' => 'string'
        ]);

        try {
            $this->db->beginTransaction();

            // Calculate totals
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $taxAmount = $subtotal * ($data['tax_rate'] ?? 0) / 100;
            $total = $subtotal + $taxAmount;

            // Create invoice
            $invoiceId = $this->db->insert('customer_invoices', [
                'company_id' => $this->user['company_id'],
                'customer_id' => $data['customer_id'],
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'status' => 'sent',
                'notes' => $data['notes'] ?? '',
                'created_by' => $this->user['id']
            ]);

            // Create invoice items
            foreach ($data['items'] as $item) {
                $this->db->insert('invoice_items', [
                    'invoice_id' => $invoiceId,
                    'item_description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['quantity'] * $item['unit_price']
                ]);
            }

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'invoice_id' => $invoiceId,
                'invoice_number' => $this->getInvoiceNumber($invoiceId),
                'message' => 'Invoice created successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateInvoiceNumber() {
        $year = date('Y');
        $count = $this->db->querySingle("
            SELECT COUNT(*) as count FROM customer_invoices
            WHERE company_id = ? AND YEAR(invoice_date) = ?
        ", [$this->user['company_id'], $year]);

        return 'INV-' . $year . '-' . str_pad($count['count'] + 1, 4, '0', STR_PAD_LEFT);
    }

    private function getInvoiceNumber($invoiceId) {
        $invoice = $this->db->querySingle("
            SELECT invoice_number FROM customer_invoices WHERE id = ?
        ", [$invoiceId]);

        return $invoice['invoice_number'];
    }

    public function recordPayment() {
        $this->requirePermission('finance.payment.record');

        $data = $this->validateRequest([
            'invoice_id' => 'required|integer',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'reference' => 'string',
            'notes' => 'string'
        ]);

        try {
            $this->db->beginTransaction();

            // Get invoice details
            $invoice = $this->db->querySingle("
                SELECT * FROM customer_invoices WHERE id = ? AND company_id = ?
            ", [$data['invoice_id'], $this->user['company_id']]);

            if (!$invoice) {
                $this->jsonResponse(['error' => 'Invoice not found'], 404);
            }

            // Create payment receipt
            $receiptId = $this->db->insert('payment_receipts', [
                'company_id' => $this->user['company_id'],
                'invoice_id' => $data['invoice_id'],
                'receipt_number' => $this->generateReceiptNumber(),
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? '',
                'notes' => $data['notes'] ?? '',
                'created_by' => $this->user['id']
            ]);

            // Update invoice status
            $paidAmount = $this->getPaidAmount($data['invoice_id']);
            $newPaidAmount = $paidAmount + $data['amount'];

            if ($newPaidAmount >= $invoice['total_amount']) {
                $status = 'paid';
            } elseif ($newPaidAmount > 0) {
                $status = 'partially_paid';
            } else {
                $status = $invoice['status'];
            }

            $this->db->update('customer_invoices', [
                'status' => $status,
                'paid_amount' => $newPaidAmount
            ], 'id = ?', [$data['invoice_id']]);

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'receipt_id' => $receiptId,
                'receipt_number' => $this->getReceiptNumber($receiptId),
                'message' => 'Payment recorded successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateReceiptNumber() {
        $year = date('Y');
        $count = $this->db->querySingle("
            SELECT COUNT(*) as count FROM payment_receipts
            WHERE company_id = ? AND YEAR(payment_date) = ?
        ", [$this->user['company_id'], $year]);

        return 'REC-' . $year . '-' . str_pad($count['count'] + 1, 4, '0', STR_PAD_LEFT);
    }

    private function getReceiptNumber($receiptId) {
        $receipt = $this->db->querySingle("
            SELECT receipt_number FROM payment_receipts WHERE id = ?
        ", [$receiptId]);

        return $receipt['receipt_number'];
    }

    private function getPaidAmount($invoiceId) {
        $result = $this->db->querySingle("
            SELECT COALESCE(SUM(amount), 0) as paid_amount
            FROM payment_receipts
            WHERE invoice_id = ?
        ", [$invoiceId]);

        return $result['paid_amount'];
    }

    public function generateFinancialReport() {
        $this->requirePermission('finance.reporting.generate');

        $data = $this->validateRequest([
            'report_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'format' => 'required|string'
        ]);

        try {
            $reportData = [];

            switch ($data['report_type']) {
                case 'income_statement':
                    $reportData = $this->getIncomeStatement();
                    break;
                case 'balance_sheet':
                    $reportData = $this->getBalanceSheetReport();
                    break;
                case 'cash_flow':
                    $reportData = $this->getCashFlowStatement();
                    break;
                case 'trial_balance':
                    $reportData = $this->getTrialBalance();
                    break;
                default:
                    $this->jsonResponse(['error' => 'Invalid report type'], 400);
            }

            // Create report record
            $reportId = $this->db->insert('financial_reports', [
                'company_id' => $this->user['company_id'],
                'report_type' => $data['report_type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'format' => $data['format'],
                'generated_by' => $this->user['id'],
                'generated_at' => date('Y-m-d H:i:s'),
                'data' => json_encode($reportData)
            ]);

            $this->jsonResponse([
                'success' => true,
                'report_id' => $reportId,
                'data' => $reportData,
                'message' => 'Financial report generated successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getTrialBalance() {
        return $this->db->query("
            SELECT
                fa.account_code,
                fa.account_name,
                SUM(le.debit_amount) as debit_total,
                SUM(le.credit_amount) as credit_total,
                (SUM(le.debit_amount) - SUM(le.credit_amount)) as balance
            FROM financial_accounts fa
            LEFT JOIN ledger_entries le ON fa.id = le.account_id
            WHERE fa.company_id = ?
            GROUP BY fa.id, fa.account_code, fa.account_name
            HAVING ABS(SUM(le.debit_amount) - SUM(le.credit_amount)) > 0.01
            ORDER BY fa.account_code ASC
        ", [$this->user['company_id']]);
    }

    public function approveTransaction() {
        $this->requirePermission('finance.transaction.approve');

        $data = $this->validateRequest([
            'transaction_id' => 'required|integer',
            'approval_notes' => 'string'
        ]);

        try {
            $this->db->update('pending_approvals', [
                'status' => 'approved',
                'approved_by' => $this->user['id'],
                'approved_at' => date('Y-m-d H:i:s'),
                'approval_notes' => $data['approval_notes'] ?? ''
            ], 'id = ? AND company_id = ?', [
                $data['transaction_id'],
                $this->user['company_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Transaction approved successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function calculateTax() {
        $this->requirePermission('finance.tax.calculate');

        $data = $this->validateRequest([
            'taxable_amount' => 'required|numeric',
            'tax_code' => 'required|string',
            'transaction_date' => 'required|date'
        ]);

        try {
            // Get tax rate
            $taxRate = $this->db->querySingle("
                SELECT tax_rate FROM tax_codes
                WHERE tax_code = ? AND company_id = ? AND is_active = true
            ", [$data['tax_code'], $this->user['company_id']]);

            if (!$taxRate) {
                $this->jsonResponse(['error' => 'Invalid tax code'], 400);
            }

            $taxAmount = $data['taxable_amount'] * $taxRate['tax_rate'] / 100;

            // Record tax calculation
            $this->db->insert('tax_calculations', [
                'company_id' => $this->user['company_id'],
                'taxable_amount' => $data['taxable_amount'],
                'tax_amount' => $taxAmount,
                'tax_code_used' => $data['tax_code'],
                'calculation_date' => $data['transaction_date'],
                'calculated_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'tax_amount' => $taxAmount,
                'tax_rate' => $taxRate['tax_rate'],
                'total_amount' => $data['taxable_amount'] + $taxAmount
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
?>
