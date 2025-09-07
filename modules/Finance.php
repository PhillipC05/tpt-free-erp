<?php
/**
 * TPT Free ERP - Finance Module
 * Complete accounting, financial management, and reporting system
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
            'title' => 'Financial Management',
            'financial_overview' => $this->getFinancialOverview(),
            'cash_flow' => $this->getCashFlow(),
            'profit_loss' => $this->getProfitLoss(),
            'balance_sheet' => $this->getBalanceSheet(),
            'budget_vs_actual' => $this->getBudgetVsActual(),
            'accounts_receivable' => $this->getAccountsReceivable(),
            'accounts_payable' => $this->getAccountsPayable(),
            'financial_ratios' => $this->getFinancialRatios(),
            'upcoming_deadlines' => $this->getUpcomingDeadlines()
        ];

        $this->render('modules/finance/dashboard', $data);
    }

    /**
     * General ledger management
     */
    public function generalLedger() {
        $this->requirePermission('finance.gl.view');

        $filters = [
            'account' => $_GET['account'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'entry_type' => $_GET['entry_type'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $ledger_entries = $this->getLedgerEntries($filters);

        $data = [
            'title' => 'General Ledger',
            'ledger_entries' => $ledger_entries,
            'filters' => $filters,
            'chart_of_accounts' => $this->getChartOfAccounts(),
            'account_types' => $this->getAccountTypes(),
            'entry_types' => $this->getEntryTypes(),
            'journal_entries' => $this->getJournalEntries(),
            'ledger_summary' => $this->getLedgerSummary(),
            'reconciliation_status' => $this->getReconciliationStatus(),
            'bulk_actions' => $this->getBulkActions()
        ];

        $this->render('modules/finance/general_ledger', $data);
    }

    /**
     * Accounts payable
     */
    public function accountsPayable() {
        $this->requirePermission('finance.ap.view');

        $data = [
            'title' => 'Accounts Payable',
            'vendor_invoices' => $this->getVendorInvoices(),
            'payment_schedule' => $this->getPaymentSchedule(),
            'vendor_statements' => $this->getVendorStatements(),
            'cash_requirements' => $this->getCashRequirements(),
            'payment_terms' => $this->getPaymentTerms(),
            'discount_opportunities' => $this->getDiscountOpportunities(),
            'aging_analysis' => $this->getAgingAnalysis(),
            'vendor_performance' => $this->getVendorPerformance(),
            'ap_analytics' => $this->getAPAnalytics()
        ];

        $this->render('modules/finance/accounts_payable', $data);
    }

    /**
     * Accounts receivable
     */
    public function accountsReceivable() {
        $this->requirePermission('finance.ar.view');

        $data = [
            'title' => 'Accounts Receivable',
            'customer_invoices' => $this->getCustomerInvoices(),
            'collection_schedule' => $this->getCollectionSchedule(),
            'customer_statements' => $this->getCustomerStatements(),
            'credit_limits' => $this->getCreditLimits(),
            'collection_terms' => $this->getCollectionTerms(),
            'dunning_process' => $this->getDunningProcess(),
            'aging_analysis' => $this->getARAgingAnalysis(),
            'customer_risk' => $this->getCustomerRisk(),
            'ar_analytics' => $this->getARAnalytics()
        ];

        $this->render('modules/finance/accounts_receivable', $data);
    }

    /**
     * Budgeting and forecasting
     */
    public function budgeting() {
        $this->requirePermission('finance.budget.view');

        $data = [
            'title' => 'Budgeting & Forecasting',
            'budget_templates' => $this->getBudgetTemplates(),
            'budget_scenarios' => $this->getBudgetScenarios(),
            'budget_vs_actual' => $this->getBudgetVsActual(),
            'forecast_models' => $this->getForecastModels(),
            'variance_analysis' => $this->getVarianceAnalysis(),
            'budget_allocations' => $this->getBudgetAllocations(),
            'rolling_forecasts' => $this->getRollingForecasts(),
            'budget_analytics' => $this->getBudgetAnalytics(),
            'budget_settings' => $this->getBudgetSettings()
        ];

        $this->render('modules/finance/budgeting', $data);
    }

    /**
     * Financial reporting
     */
    public function reporting() {
        $this->requirePermission('finance.reports.view');

        $data = [
            'title' => 'Financial Reporting',
            'income_statement' => $this->getIncomeStatement(),
            'balance_sheet' => $this->getBalanceSheet(),
            'cash_flow_statement' => $this->getCashFlowStatement(),
            'financial_ratios' => $this->getFinancialRatios(),
            'trend_analysis' => $this->getTrendAnalysis(),
            'segment_reporting' => $this->getSegmentReporting(),
            'regulatory_reports' => $this->getRegulatoryReports(),
            'custom_reports' => $this->getCustomReports(),
            'report_schedules' => $this->getReportSchedules()
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
            'tax_calculations' => $this->getTaxCalculations(),
            'tax_filings' => $this->getTaxFilings(),
            'tax_credits' => $this->getTaxCredits(),
            'tax_liabilities' => $this->getTaxLiabilities(),
            'tax_compliance' => $this->getTaxCompliance(),
            'tax_planning' => $this->getTaxPlanning(),
            'tax_analytics' => $this->getTaxAnalytics(),
            'tax_settings' => $this->getTaxSettings()
        ];

        $this->render('modules/finance/tax_management', $data);
    }

    /**
     * Fixed assets management
     */
    public function fixedAssets() {
        $this->requirePermission('finance.assets.view');

        $data = [
            'title' => 'Fixed Assets',
            'asset_register' => $this->getAssetRegister(),
            'depreciation_schedule' => $this->getDepreciationSchedule(),
            'asset_disposals' => $this->getAssetDisposals(),
            'asset_valuation' => $this->getAssetValuation(),
            'lease_accounting' => $this->getLeaseAccounting(),
            'impairment_testing' => $this->getImpairmentTesting(),
            'asset_analytics' => $this->getAssetAnalytics(),
            'asset_settings' => $this->getAssetSettings()
        ];

        $this->render('modules/finance/fixed_assets', $data);
    }

    /**
     * Multi-currency management
     */
    public function multiCurrency() {
        $this->requirePermission('finance.currency.view');

        $data = [
            'title' => 'Multi-Currency Management',
            'currency_rates' => $this->getCurrencyRates(),
            'currency_positions' => $this->getCurrencyPositions(),
            'hedging_strategies' => $this->getHedgingStrategies(),
            'fx_gains_losses' => $this->getFXGainsLosses(),
            'currency_risk' => $this->getCurrencyRisk(),
            'translation_adjustments' => $this->getTranslationAdjustments(),
            'currency_analytics' => $this->getCurrencyAnalytics(),
            'currency_settings' => $this->getCurrencySettings()
        ];

        $this->render('modules/finance/multi_currency', $data);
    }

    /**
     * Financial analytics
     */
    public function analytics() {
        $this->requirePermission('finance.analytics.view');

        $data = [
            'title' => 'Financial Analytics',
            'profitability_analysis' => $this->getProfitabilityAnalysis(),
            'cash_flow_analysis' => $this->getCashFlowAnalysis(),
            'working_capital' => $this->getWorkingCapital(),
            'financial_ratios' => $this->getFinancialRatios(),
            'trend_analysis' => $this->getTrendAnalysis(),
            'benchmarking' => $this->getBenchmarking(),
            'predictive_modeling' => $this->getPredictiveModeling(),
            'custom_dashboards' => $this->getCustomDashboards()
        ];

        $this->render('modules/finance/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getFinancialOverview() {
        return $this->db->querySingle("
            SELECT
                SUM(CASE WHEN a.account_type = 'asset' THEN a.balance ELSE 0 END) as total_assets,
                SUM(CASE WHEN a.account_type = 'liability' THEN a.balance ELSE 0 END) as total_liabilities,
                SUM(CASE WHEN a.account_type = 'equity' THEN a.balance ELSE 0 END) as total_equity,
                SUM(CASE WHEN a.account_type = 'revenue' THEN a.balance ELSE 0 END) as total_revenue,
                SUM(CASE WHEN a.account_type = 'expense' THEN a.balance ELSE 0 END) as total_expenses,
                (SUM(CASE WHEN a.account_type = 'revenue' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_type = 'expense' THEN a.balance ELSE 0 END)) as net_income,
                COUNT(CASE WHEN ar.status = 'overdue' THEN 1 END) as overdue_receivables,
                COUNT(CASE WHEN ap.status = 'overdue' THEN 1 END) as overdue_payables,
                AVG(ar.days_outstanding) as avg_collection_period,
                AVG(ap.days_outstanding) as avg_payment_period
            FROM accounts a
            LEFT JOIN accounts_receivable ar ON ar.company_id = a.company_id
            LEFT JOIN accounts_payable ap ON ap.company_id = a.company_id
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCashFlow() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(t.transaction_date, '%Y-%m') as month,
                SUM(CASE WHEN t.transaction_type = 'operating' THEN t.amount ELSE 0 END) as operating_cash_flow,
                SUM(CASE WHEN t.transaction_type = 'investing' THEN t.amount ELSE 0 END) as investing_cash_flow,
                SUM(CASE WHEN t.transaction_type = 'financing' THEN t.amount ELSE 0 END) as financing_cash_flow,
                SUM(t.amount) as net_cash_flow,
                SUM(SUM(t.amount)) OVER (ORDER BY DATE_FORMAT(t.transaction_date, '%Y-%m')) as cumulative_cash_flow
            FROM transactions t
            WHERE t.company_id = ? AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(t.transaction_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getProfitLoss() {
        return $this->db->querySingle("
            SELECT
                SUM(CASE WHEN a.account_type = 'revenue' THEN a.balance ELSE 0 END) as total_revenue,
                SUM(CASE WHEN a.account_subtype = 'cost_of_goods_sold' THEN a.balance ELSE 0 END) as cost_of_goods_sold,
                (SUM(CASE WHEN a.account_type = 'revenue' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_subtype = 'cost_of_goods_sold' THEN a.balance ELSE 0 END)) as gross_profit,
                SUM(CASE WHEN a.account_subtype = 'operating_expenses' THEN a.balance ELSE 0 END) as operating_expenses,
                (SUM(CASE WHEN a.account_type = 'revenue' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_subtype = 'cost_of_goods_sold' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_subtype = 'operating_expenses' THEN a.balance ELSE 0 END)) as operating_income,
                SUM(CASE WHEN a.account_subtype = 'interest_expense' THEN a.balance ELSE 0 END) as interest_expense,
                SUM(CASE WHEN a.account_subtype = 'tax_expense' THEN a.balance ELSE 0 END) as tax_expense,
                (SUM(CASE WHEN a.account_type = 'revenue' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_subtype = 'cost_of_goods_sold' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_subtype = 'operating_expenses' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_subtype = 'interest_expense' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_subtype = 'tax_expense' THEN a.balance ELSE 0 END)) as net_income
            FROM accounts a
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBalanceSheet() {
        return $this->db->query("
            SELECT
                a.account_name,
                a.account_type,
                a.account_subtype,
                a.balance,
                a.normal_balance,
                CASE
                    WHEN a.account_type = 'asset' THEN 'Assets'
                    WHEN a.account_type = 'liability' THEN 'Liabilities'
                    WHEN a.account_type = 'equity' THEN 'Equity'
                    ELSE 'Other'
                END as category
            FROM accounts a
            WHERE a.company_id = ? AND a.is_active = true
            ORDER BY
                CASE a.account_type
                    WHEN 'asset' THEN 1
                    WHEN 'liability' THEN 2
                    WHEN 'equity' THEN 3
                    ELSE 4
                END,
                a.account_code ASC
        ", [$this->user['company_id']]);
    }

    private function getBudgetVsActual() {
        return $this->db->query("
            SELECT
                a.account_name,
                b.budget_amount,
                a.balance as actual_amount,
                (a.balance - b.budget_amount) as variance,
                ROUND(((a.balance - b.budget_amount) / NULLIF(b.budget_amount, 0)) * 100, 2) as variance_percentage,
                CASE
                    WHEN ABS((a.balance - b.budget_amount) / NULLIF(b.budget_amount, 0)) > 0.1 THEN 'significant'
                    WHEN ABS((a.balance - b.budget_amount) / NULLIF(b.budget_amount, 0)) > 0.05 THEN 'moderate'
                    ELSE 'minor'
                END as variance_severity
            FROM accounts a
            JOIN budgets b ON a.id = b.account_id AND b.budget_period = DATE_FORMAT(CURDATE(), '%Y-%m')
            WHERE a.company_id = ?
            ORDER BY ABS(a.balance - b.budget_amount) DESC
        ", [$this->user['company_id']]);
    }

    private function getAccountsReceivable() {
        return $this->db->querySingle("
            SELECT
                COUNT(ar.id) as total_invoices,
                SUM(ar.amount) as total_receivable,
                SUM(ar.amount_paid) as total_paid,
                (SUM(ar.amount) - SUM(ar.amount_paid)) as total_outstanding,
                COUNT(CASE WHEN ar.due_date < CURDATE() AND ar.amount > ar.amount_paid THEN 1 END) as overdue_invoices,
                SUM(CASE WHEN ar.due_date < CURDATE() AND ar.amount > ar.amount_paid THEN (ar.amount - ar.amount_paid) ELSE 0 END) as overdue_amount,
                AVG(ar.days_outstanding) as avg_collection_period,
                COUNT(DISTINCT ar.customer_id) as active_customers
            FROM accounts_receivable ar
            WHERE ar.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAccountsPayable() {
        return $this->db->querySingle("
            SELECT
                COUNT(ap.id) as total_invoices,
                SUM(ap.amount) as total_payable,
                SUM(ap.amount_paid) as total_paid,
                (SUM(ap.amount) - SUM(ap.amount_paid)) as total_outstanding,
                COUNT(CASE WHEN ap.due_date < CURDATE() AND ap.amount > ap.amount_paid THEN 1 END) as overdue_invoices,
                SUM(CASE WHEN ap.due_date < CURDATE() AND ap.amount > ap.amount_paid THEN (ap.amount - ap.amount_paid) ELSE 0 END) as overdue_amount,
                AVG(ap.days_outstanding) as avg_payment_period,
                COUNT(DISTINCT ap.vendor_id) as active_vendors
            FROM accounts_payable ap
            WHERE ap.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getFinancialRatios() {
        return $this->db->querySingle("
            SELECT
                -- Liquidity Ratios
                (SUM(CASE WHEN a.account_subtype = 'current_assets' THEN a.balance ELSE 0 END) / NULLIF(SUM(CASE WHEN a.account_subtype = 'current_liabilities' THEN a.balance ELSE 0 END), 0)) as current_ratio,
                ((SUM(CASE WHEN a.account_subtype = 'current_assets' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_subtype = 'inventory' THEN a.balance ELSE 0 END)) / NULLIF(SUM(CASE WHEN a.account_subtype = 'current_liabilities' THEN a.balance ELSE 0 END), 0)) as quick_ratio,
                
                -- Profitability Ratios
                ((SUM(CASE WHEN a.account_type = 'revenue' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_type = 'expense' THEN a.balance ELSE 0 END)) / NULLIF(SUM(CASE WHEN a.account_type = 'revenue' THEN a.balance ELSE 0 END), 0)) as net_profit_margin,
                ((SUM(CASE WHEN a.account_type = 'revenue' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_type = 'expense' THEN a.balance ELSE 0 END)) / NULLIF(SUM(CASE WHEN a.account_type = 'asset' THEN a.balance ELSE 0 END), 0)) as return_on_assets,
                
                -- Efficiency Ratios
                (SUM(CASE WHEN a.account_type = 'revenue' THEN a.balance ELSE 0 END) / NULLIF(SUM(CASE WHEN a.account_subtype = 'current_assets' THEN a.balance ELSE 0 END), 0)) as asset_turnover,
                (SUM(CASE WHEN a.account_type = 'revenue' THEN a.balance ELSE 0 END) / NULLIF(SUM(CASE WHEN a.account_subtype = 'receivables' THEN a.balance ELSE 0 END), 0)) as receivables_turnover,
                
                -- Leverage Ratios
                (SUM(CASE WHEN a.account_type = 'asset' THEN a.balance ELSE 0 END) / NULLIF(SUM(CASE WHEN a.account_type = 'equity' THEN a.balance ELSE 0 END), 0)) as debt_to_equity,
                (SUM(CASE WHEN a.account_type = 'liability' THEN a.balance ELSE 0 END) / NULLIF(SUM(CASE WHEN a.account_type = 'asset' THEN a.balance ELSE 0 END), 0)) as debt_ratio
                
            FROM accounts a
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getUpcomingDeadlines() {
        return $this->db->query("
            SELECT
                'AR' as type,
                ar.invoice_number as reference,
                c.customer_name as party_name,
                ar.due_date,
                (ar.amount - ar.amount_paid) as amount,
                TIMESTAMPDIFF(DAY, CURDATE(), ar.due_date) as days_until_due,
                CASE
                    WHEN ar.due_date < CURDATE() THEN 'overdue'
                    WHEN ar.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'due_soon'
                    ELSE 'upcoming'
                END as status
            FROM accounts_receivable ar
            JOIN customers c ON ar.customer_id = c.id
            WHERE ar.company_id = ? AND ar.amount > ar.amount_paid
            UNION ALL
            SELECT
                'AP' as type,
                ap.invoice_number as reference,
                v.vendor_name as party_name,
                ap.due_date,
                (ap.amount - ap.amount_paid) as amount,
                TIMESTAMPDIFF(DAY, CURDATE(), ap.due_date) as days_until_due,
                CASE
                    WHEN ap.due_date < CURDATE() THEN 'overdue'
                    WHEN ap.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'due_soon'
                    ELSE 'upcoming'
                END as status
            FROM accounts_payable ap
            JOIN vendors v ON ap.vendor_id = v.id
            WHERE ap.company_id = ? AND ap.amount > ap.amount_paid
            ORDER BY due_date ASC
            LIMIT 20
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getLedgerEntries($filters = []) {
        $where = ["le.company_id = ?"];
        $params = [$this->user['company_id']];

        if (isset($filters['account'])) {
            $where[] = "le.account_id = ?";
            $params[] = $filters['account'];
        }

        if (isset($filters['date_from'])) {
            $where[] = "le.entry_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (isset($filters['date_to'])) {
            $where[] = "le.entry_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (isset($filters['entry_type'])) {
            $where[] = "le.entry_type = ?";
            $params[] = $filters['entry_type'];
        }

        if (isset($filters['search'])) {
            $where[] = "(le.description LIKE ? OR le.reference_number LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                le.*,
                a.account_name,
                a.account_code,
                a.account_type,
                je.journal_entry_number,
                je.entry_date as journal_date,
                je.description as journal_description,
                le.debit_amount,
                le.credit_amount,
                (le.debit_amount - le.credit_amount) as net_amount,
                le.running_balance
            FROM ledger_entries le
            JOIN accounts a ON le.account_id = a.id
            LEFT JOIN journal_entries je ON le.journal_entry_id = je.id
            WHERE $whereClause
            ORDER BY le.entry_date DESC, le.id DESC
        ", $params);
    }

    private function getChartOfAccounts() {
        return $this->db->query("
            SELECT
                a.*,
                a.account_code,
                a.account_name,
                a.account_type,
                a.account_subtype,
                a.balance,
                a.normal_balance,
                COUNT(le.id) as transaction_count,
                MAX(le.entry_date) as last_transaction_date
            FROM accounts a
            LEFT JOIN ledger_entries le ON a.id = le.account_id
            WHERE a.company_id = ? AND a.is_active = true
            GROUP BY a.id
            ORDER BY a.account_code ASC
        ", [$this->user['company_id']]);
    }

    private function getAccountTypes() {
        return [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'revenue' => 'Revenue',
            'expense' => 'Expense'
        ];
    }

    private function getEntryTypes() {
        return [
            'journal' => 'Journal Entry',
            'invoice' => 'Invoice',
            'payment' => 'Payment',
            'adjustment' => 'Adjustment',
            'reconciliation' => 'Reconciliation'
        ];
    }

    private function getJournalEntries() {
        return $this->db->query("
            SELECT
                je.*,
                je.journal_entry_number,
                je.entry_date,
                je.description,
                je.total_debit,
                je.total_credit,
                je.status,
                COUNT(le.id) as line_count,
                u.first_name as created_by_first,
                u.last_name as created_by_last
            FROM journal_entries je
            LEFT JOIN ledger_entries le ON je.id = le.journal_entry_id
            LEFT JOIN users u ON je.created_by = u.id
            WHERE je.company_id = ?
            GROUP BY je.id
            ORDER BY je.entry_date DESC
        ", [$this->user['company_id']]);
    }

    private function getLedgerSummary() {
        return $this->db->query("
            SELECT
                a.account_type,
                COUNT(a.id) as account_count,
                SUM(a.balance) as total_balance,
                AVG(a.balance) as avg_balance,
                COUNT(CASE WHEN a.balance > 0 THEN 1 END) as active_accounts,
                COUNT(CASE WHEN a.balance = 0 THEN 1 END) as zero_balance_accounts
            FROM accounts a
            WHERE a.company_id = ?
            GROUP BY a.account_type
            ORDER BY total_balance DESC
        ", [$this->user['company_id']]);
    }

    private function getReconciliationStatus() {
        return $this->db->query("
            SELECT
                a.account_name,
                a.account_code,
                r.last_reconciliation_date,
                r.reconciled_balance,
                a.balance as current_balance,
                (a.balance - r.reconciled_balance) as unreconciled_amount,
                TIMESTAMPDIFF(DAY, r.last_reconciliation_date, CURDATE()) as days_since_reconciliation,
                CASE
                    WHEN TIMESTAMPDIFF(DAY, r.last_reconciliation_date, CURDATE()) > 30 THEN 'needs_attention'
                    WHEN ABS(a.balance - r.reconciled_balance) > 100 THEN 'significant_variance'
                    ELSE 'current'
                END as reconciliation_status
            FROM accounts a
            LEFT JOIN reconciliations r ON a.id = r.account_id AND r.is_latest = true
            WHERE a.company_id = ?
            ORDER BY TIMESTAMPDIFF(DAY, r.last_reconciliation_date, CURDATE()) DESC
        ", [$this->user['company_id']]);
    }

    private function getBulkActions() {
        return [
            'post_entries' => 'Post Journal Entries',
            'reverse_entries' => 'Reverse Entries',
            'export_ledger' => 'Export Ledger',
            'reconcile_accounts' => 'Reconcile Accounts',
            'adjust_entries' => 'Adjust Entries',
            'archive_entries' => 'Archive Entries'
        ];
    }

    private function getVendorInvoices() {
        return $this->db->query("
            SELECT
                ap.*,
                v.vendor_name,
                v.vendor_code,
                ap.invoice_number,
                ap.invoice_date,
                ap.due_date,
                ap.amount,
                ap.amount_paid,
                (ap.amount - ap.amount_paid) as balance,
                ap.status,
                ap.payment_terms,
                TIMESTAMPDIFF(DAY, CURDATE(), ap.due_date) as days_until_due,
                CASE
                    WHEN ap.due_date < CURDATE() AND ap.amount > ap.amount_paid THEN 'overdue'
                    WHEN ap.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND ap.amount > ap.amount_paid THEN 'due_soon'
                    ELSE 'current'
                END as payment_status
            FROM accounts_payable ap
            JOIN vendors v ON ap.vendor_id = v.id
            WHERE ap.company_id = ?
            ORDER BY ap.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getPaymentSchedule() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(ap.due_date, '%Y-%m-%d') as payment_date,
                COUNT(ap.id) as invoice_count,
                SUM(ap.amount - ap.amount_paid) as total_amount_due,
                GROUP_CONCAT(DISTINCT v.vendor_name SEPARATOR ', ') as vendors
            FROM accounts_payable ap
            JOIN vendors v ON ap.vendor_id = v.id
            WHERE ap.company_id = ? AND ap.amount > ap.amount_paid AND ap.due_date >= CURDATE()
            GROUP BY DATE_FORMAT(ap.due_date, '%Y-%m-%d')
            ORDER BY payment_date ASC
            LIMIT 30
        ", [$this->user['company_id']]);
    }

    private function getVendorStatements() {
        return $this->db->query("
            SELECT
                v.vendor_name,
                v.vendor_code,
                COUNT(ap.id) as open_invoices,
                SUM(CASE WHEN ap.due_date < CURDATE() AND ap.amount > ap.amount_paid THEN (ap.amount - ap.amount_paid) ELSE 0 END) as overdue_amount,
                SUM(ap.amount - ap.amount_paid) as total_outstanding,
                MAX(ap.due_date) as latest_due_date,
                AVG(ap.payment_terms) as avg_payment_terms,
                MAX(ap.invoice_date) as last_invoice_date
            FROM vendors v
            LEFT JOIN accounts_payable ap ON v.id = ap.vendor_id AND ap.amount > ap.amount_paid
            WHERE v.company_id = ?
            GROUP BY v.id, v.vendor_name, v.vendor_code
            ORDER BY total_outstanding DESC
        ", [$this->user['company_id']]);
    }

    private function getCashRequirements() {
        return $this->db->querySingle("
            SELECT
                SUM(CASE WHEN ap.due_date = CURDATE() THEN (ap.amount - ap.amount_paid) ELSE 0 END) as due_today,
                SUM(CASE WHEN ap.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN (ap.amount - ap.amount_paid) ELSE 0 END) as due_this_week,
                SUM(CASE WHEN ap.due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN (ap.amount - ap.amount_paid) ELSE 0 END) as due_this_month,
                SUM(CASE WHEN ap.due_date < CURDATE() THEN (ap.amount - ap.amount_paid) ELSE 0 END) as overdue_amount,
                COUNT(CASE WHEN ap.due_date = CURDATE() THEN 1 END) as invoices_due_today,
                COUNT(CASE WHEN ap.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as invoices_due_this_week
            FROM accounts_payable ap
            WHERE ap.company_id = ? AND ap.amount > ap.amount_paid
        ", [$this->user['company_id']]);
    }

    private function getPaymentTerms() {
        return $this->db->query("
            SELECT
                ap.payment_terms,
                COUNT(ap.id) as invoice_count,
                SUM(ap.amount) as total_amount,
                AVG(ap.payment_terms) as avg_terms,
                COUNT(CASE WHEN ap.due_date < CURDATE() THEN 1 END) as overdue_count,
                ROUND((COUNT(CASE WHEN ap.due_date < CURDATE() THEN 1 END) / NULLIF(COUNT(ap.id), 0)) * 100, 2) as overdue_percentage
            FROM accounts_payable ap
            WHERE ap.company_id = ?
            GROUP BY ap.payment_terms
            ORDER BY total_amount DESC
        ", [$this->user['company_id']]);
    }

    private function getDiscountOpportunities() {
        return $this->db->query("
            SELECT
                ap.invoice_number,
                v.vendor_name,
                ap.amount,
                ap.discount_terms,
                ap.discount_amount,
                ap.discount_due_date,
                TIMESTAMPDIFF(DAY, CURDATE(), ap.discount_due_date) as days_until_discount_expires,
                CASE
                    WHEN ap.discount_due_date < CURDATE() THEN 'expired'
                    WHEN ap.discount_due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'expires_soon'
                    ELSE 'available'
                END as discount_status
            FROM accounts_payable ap
            JOIN vendors v ON ap.vendor_id = v.id
            WHERE ap.company_id = ? AND ap.discount_amount > 0 AND ap.amount > ap.amount_paid
            ORDER BY ap.discount_due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getAgingAnalysis() {
        return $this->db->query("
            SELECT
                CASE
                    WHEN TIMESTAMPDIFF(DAY, ap.due_date, CURDATE()) <= 0 THEN 'current'
                    WHEN TIMESTAMPDIFF(DAY, ap.due_date, CURDATE()) <= 30 THEN '1-30_days'
                    WHEN TIMESTAMPDIFF(DAY, ap.due_date, CURDATE()) <= 60 THEN '31-60_days'
                    WHEN TIMESTAMPDIFF(DAY, ap.due_date, CURDATE()) <= 90 THEN '61-90_days'
                    ELSE 'over_90_days'
                END as aging_bucket,
                COUNT(ap.id) as invoice_count,
                SUM(ap.amount - ap.amount_paid) as amount_outstanding,
                ROUND((SUM(ap.amount - ap.amount_paid) / NULLIF((SELECT SUM(amount - amount_paid) FROM accounts_payable WHERE company_id = ? AND amount > amount_paid), 0)) * 100, 2) as percentage_of_total
            FROM accounts_payable ap
            WHERE ap.company_id = ? AND ap.amount > ap.amount_paid
            GROUP BY
                CASE
                    WHEN TIMESTAMPDIFF(DAY, ap.due_date, CURDATE()) <= 0 THEN 'current'
                    WHEN TIMESTAMPDIFF(DAY, ap.due_date, CURDATE()) <= 30 THEN '1-30_days'
                    WHEN TIMESTAMPDIFF(DAY, ap.due_date, CURDATE()) <= 60 THEN '31-60_days'
                    WHEN TIMESTAMPDIFF(DAY, ap.due_date, CURDATE()) <= 90 THEN '61-90_days'
                    ELSE 'over_90_days'
                END
            ORDER BY
                CASE aging_bucket
                    WHEN 'current' THEN 1
                    WHEN '1-30_days' THEN 2
                    WHEN '31-60_days' THEN 3
                    WHEN '61-90_days' THEN 4
                    WHEN 'over_90_days' THEN 5
                END
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getVendorPerformance() {
        return $this->db->query("
            SELECT
                v.vendor_name,
                COUNT(ap.id) as total_invoices,
                SUM(ap.amount) as total_invoice_amount,
                AVG(TIMESTAMPDIFF(DAY, ap.invoice_date, ap.due_date)) as avg_payment_terms,
                AVG(CASE WHEN ap.amount_paid >= ap.amount THEN TIMESTAMPDIFF(DAY, ap.invoice_date, ap.payment_date) END) as avg_payment_time,
                COUNT(CASE WHEN ap.due_date < CURDATE() AND ap.amount > ap.amount_paid THEN 1 END) as overdue_invoices,
                ROUND((COUNT(CASE WHEN ap.due_date < CURDATE() AND ap.amount > ap.amount_paid THEN 1 END) / NULLIF(COUNT(ap.id), 0)) * 100, 2) as overdue_percentage,
                SUM(ap.discount_amount) as total_discounts_taken
            FROM vendors v
            LEFT JOIN accounts_payable ap ON v.id = ap.vendor_id
            WHERE v.company_id = ?
            GROUP BY v.id, v.vendor_name
            ORDER BY total_invoice_amount DESC
        ", [$this->user['company_id']]);
    }

    private function getAPAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(ap.id) as total_invoices,
                SUM(ap.amount) as total_payable,
                SUM(ap.amount_paid) as total_paid,
                (SUM(ap.amount) - SUM(ap.amount_paid)) as outstanding_balance,
                COUNT(CASE WHEN ap.status = 'paid' THEN 1 END) as paid_invoices,
                ROUND((COUNT(CASE WHEN ap.status = 'paid' THEN 1 END) / NULLIF(COUNT(ap.id), 0)) * 100, 2) as payment_percentage,
                AVG(TIMESTAMPDIFF(DAY, ap.invoice_date, COALESCE(ap.payment_date, CURDATE()))) as avg_payment_time,
                COUNT(DISTINCT ap.vendor_id) as active_vendors
            FROM accounts_payable ap
            WHERE ap.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCustomerInvoices() {
        return $this->db->query("
            SELECT
                ar.*,
                c.customer_name,
                c.customer_code,
                ar.invoice_number,
                ar.invoice_date,
                ar.due_date,
                ar.amount,
                ar.amount_paid,
                (ar.amount - ar.amount_paid) as balance,
                ar.status,
                ar.collection_terms,
                TIMESTAMPDIFF(DAY, CURDATE(), ar.due_date) as days_until_due,
                CASE
                    WHEN ar.due_date < CURDATE() AND ar.amount > ar.amount_paid THEN 'overdue'
                    WHEN ar.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND ar.amount > ar.amount_paid THEN 'due_soon'
                    ELSE 'current'
                END as collection_status
            FROM accounts_receivable ar
            JOIN customers c ON ar.customer_id = c.id
            WHERE ar.company_id = ?
            ORDER BY ar.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCollectionSchedule() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(ar.due_date, '%Y-%m-%d') as collection_date,
                COUNT(ar.id) as invoice_count,
                SUM(ar.amount - ar.amount_paid) as total_amount_due,
                GROUP_CONCAT(DISTINCT c.customer_name SEPARATOR ', ') as customers
            FROM accounts_receivable ar
            JOIN customers c ON ar.customer_id = c.id
            WHERE ar.company_id = ? AND ar.amount > ar.amount_paid AND ar.due_date >= CURDATE()
            GROUP BY DATE_FORMAT(ar.due_date, '%Y-%m-%d')
            ORDER BY collection_date ASC
            LIMIT 30
        ", [$this->user['company_id']]);
    }

    private function getCustomerStatements() {
        return $this->db->query("
            SELECT
                c.customer_name,
                c.customer_code,
                COUNT(ar.id) as open_invoices,
                SUM(CASE WHEN ar.due_date < CURDATE() AND ar.amount > ar.amount_paid THEN (ar.amount - ar.amount_paid) ELSE 0 END) as overdue_amount,
                SUM(ar.amount - ar.amount_paid) as total_outstanding,
                MAX(ar.due_date) as latest_due_date,
                AVG(ar.collection_terms) as avg_collection_terms,
                MAX(ar.invoice_date) as last_invoice_date
            FROM customers c
            LEFT JOIN accounts_receivable ar ON c.id = ar.customer_id AND ar.amount > ar.amount_paid
            WHERE c.company_id = ?
            GROUP BY c.id, c.customer_name, c.customer_code
            ORDER BY total_outstanding DESC
        ", [$this->user['company_id']]);
    }

    private function getCreditLimits() {
        return $this->db->query("
            SELECT
                c.customer_name,
                c.customer_code,
                cl.credit_limit,
                cl.available_credit,
                cl.used_credit,
                ROUND((cl.used_credit / NULLIF(cl.credit_limit, 0)) * 100, 2) as credit_utilization,
                cl.last_review_date,
                cl.next_review_date,
                CASE
                    WHEN (cl.used_credit / NULLIF(cl.credit_limit, 0)) > 0.9 THEN 'high_utilization'
                    WHEN (cl.used_credit / NULLIF(cl.credit_limit, 0)) > 0.75 THEN 'moderate_utilization'
                    ELSE 'low_utilization'
                END as utilization_status
            FROM customers c
            JOIN credit_limits cl ON c.id = cl.customer_id
            WHERE c.company_id = ?
            ORDER BY credit_utilization DESC
        ", [$this->user['company_id']]);
    }

    private function getCollectionTerms() {
        return $this->db->query("
            SELECT
                ar.collection_terms,
                COUNT(ar.id) as invoice_count,
                SUM(ar.amount) as total_amount,
                AVG(ar.collection_terms) as avg_terms,
                COUNT(CASE WHEN ar.due_date < CURDATE() THEN 1 END) as overdue_count,
                ROUND((COUNT(CASE WHEN ar.due_date < CURDATE() THEN 1 END) / NULLIF(COUNT(ar.id), 0)) * 100, 2) as overdue_percentage
            FROM accounts_receivable ar
            WHERE ar.company_id = ?
            GROUP BY ar.collection_terms
            ORDER BY total_amount DESC
        ", [$this->user['company_id']]);
    }

    private function getDunningProcess() {
        return $this->db->query("
            SELECT
                ar.invoice_number,
                c.customer_name,
                ar.due_date,
                TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) as days_overdue,
                (ar.amount - ar.amount_paid) as overdue_amount,
                dp.dunning_level,
                dp.last_contact_date,
                dp.next_action_date,
                dp.contact_method,
                CASE
                    WHEN TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) > 90 THEN 'severe'
                    WHEN TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) > 60 THEN 'high'
                    WHEN TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) > 30 THEN 'medium'
                    ELSE 'low'
                END as priority_level
            FROM accounts_receivable ar
            JOIN customers c ON ar.customer_id = c.id
            LEFT JOIN dunning_process dp ON ar.id = dp.invoice_id
            WHERE ar.company_id = ? AND ar.due_date < CURDATE() AND ar.amount > ar.amount_paid
            ORDER BY days_overdue DESC
        ", [$this->user['company_id']]);
    }

    private function getARAgingAnalysis() {
        return $this->db->query("
            SELECT
                CASE
                    WHEN TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) <= 0 THEN 'current'
                    WHEN TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) <= 30 THEN '1-30_days'
                    WHEN TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) <= 60 THEN '31-60_days'
                    WHEN TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) <= 90 THEN '61-90_days'
                    ELSE 'over_90_days'
                END as aging_bucket,
                COUNT(ar.id) as invoice_count,
                SUM(ar.amount - ar.amount_paid) as amount_outstanding,
                ROUND((SUM(ar.amount - ar.amount_paid) / NULLIF((SELECT SUM(amount - amount_paid) FROM accounts_receivable WHERE company_id = ? AND amount > amount_paid), 0)) * 100, 2) as percentage_of_total
            FROM accounts_receivable ar
            WHERE ar.company_id = ? AND ar.amount > ar.amount_paid
            GROUP BY
                CASE
                    WHEN TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) <= 0 THEN 'current'
                    WHEN TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) <= 30 THEN '1-30_days'
                    WHEN TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) <= 60 THEN '31-60_days'
                    WHEN TIMESTAMPDIFF(DAY, ar.due_date, CURDATE()) <= 90 THEN '61-90_days'
                    ELSE 'over_90_days'
                END
            ORDER BY
                CASE aging_bucket
                    WHEN 'current' THEN 1
                    WHEN '1-30_days' THEN 2
                    WHEN '31-60_days' THEN 3
                    WHEN '61-90_days' THEN 4
                    WHEN 'over_90_days' THEN 5
                END
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getCustomerRisk() {
        return $this->db->query("
            SELECT
                c.customer_name,
                c.customer_code,
                cr.risk_score,
                cr.risk_level,
                cr.payment_history_score,
                cr.credit_utilization,
                cr.overdue_amount,
                cr.average_payment_days,
                cr.last_risk_assessment,
                CASE
                    WHEN cr.risk_score >= 80 THEN 'low_risk'
                    WHEN cr.risk_score >= 60 THEN 'medium_risk'
                    WHEN cr.risk_score >= 40 THEN 'high_risk'
                    ELSE 'very_high_risk'
                END as risk_category
            FROM customers c
            JOIN customer_risk cr ON c.id = cr.customer_id
            WHERE c.company_id = ?
            ORDER BY cr.risk_score ASC
        ", [$this->user['company_id']]);
    }

    private function getARAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(ar.id) as total_invoices,
                SUM(ar.amount) as total_receivable,
                SUM(ar.amount_paid) as total_collected,
                (SUM(ar.amount) - SUM(ar.amount_paid)) as outstanding_balance,
                COUNT(CASE WHEN ar.status = 'paid' THEN 1 END) as paid_invoices,
                ROUND((COUNT(CASE WHEN ar.status = 'paid' THEN 1 END) / NULLIF(COUNT(ar.id), 0)) * 100, 2) as collection_percentage,
                AVG(TIMESTAMPDIFF(DAY, ar.invoice_date, COALESCE(ar.payment_date, CURDATE()))) as avg_collection_time,
                COUNT(DISTINCT ar.customer_id) as active_customers
            FROM accounts_receivable ar
            WHERE ar.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBudgetTemplates() {
        return $this->db->query("
            SELECT * FROM budget_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBudgetScenarios() {
        return $this->db->query("
            SELECT
                bs.*,
                bs.scenario_name,
                bs.scenario_type,
                bs.probability_percentage,
                bs.created_date,
                bs.last_modified,
                u.first_name as created_by_first,
                u.last_name as created_by_last
            FROM budget_scenarios bs
            LEFT JOIN users u ON bs.created_by = u.id
            WHERE bs.company_id = ?
            ORDER BY bs.probability_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getForecastModels() {
        return $this->db->query("
            SELECT
                fm.*,
                fm.model_name,
                fm.model_type,
                fm.accuracy_score,
                fm.training_period_months,
                fm.last_trained,
                fm.next_training_date,
                fm.model_status
            FROM forecast_models fm
            WHERE fm.company_id = ?
            ORDER BY fm.accuracy_score DESC
        ", [$this->user['company_id']]);
    }

    private function getVarianceAnalysis() {
        return $this->db->query("
            SELECT
                a.account_name,
                b.budget_amount,
                a.balance as actual_amount,
                (a.balance - b.budget_amount) as variance,
                ROUND(((a.balance - b.budget_amount) / NULLIF(b.budget_amount, 0)) * 100, 2) as variance_percentage,
                CASE
                    WHEN ABS((a.balance - b.budget_amount) / NULLIF(b.budget_amount, 0)) > 0.1 THEN 'significant'
                    WHEN ABS((a.balance - b.budget_amount) / NULLIF(b.budget_amount, 0)) > 0.05 THEN 'moderate'
                    ELSE 'minor'
                END as variance_severity,
                b.budget_period
            FROM accounts a
            JOIN budgets b ON a.id = b.account_id
            WHERE a.company_id = ? AND b.budget_period >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 6 MONTH), '%Y-%m')
            ORDER BY ABS(a.balance - b.budget_amount) DESC
        ", [$this->user['company_id']]);
    }

    private function getBudgetAllocations() {
        return $this->db->query("
            SELECT
                ba.*,
                a.account_name,
                ba.allocation_percentage,
                ba.allocated_amount,
                ba.allocation_date,
                ba.allocation_type,
                u.first_name as allocated_by_first,
                u.last_name as allocated_by_last
            FROM budget_allocations ba
            JOIN accounts a ON ba.account_id = a.id
            LEFT JOIN users u ON ba.allocated_by = u.id
            WHERE ba.company_id = ?
            ORDER BY ba.allocation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getRollingForecasts() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(rf.forecast_date, '%Y-%m') as forecast_month,
                SUM(rf.forecast_amount) as total_forecast,
                SUM(rf.actual_amount) as total_actual,
                ROUND(((SUM(rf.actual_amount) - SUM(rf.forecast_amount)) / NULLIF(SUM(rf.forecast_amount), 0)) * 100, 2) as forecast_accuracy,
                COUNT(rf.id) as forecast_items
            FROM rolling_forecasts rf
            WHERE rf.company_id = ? AND rf.forecast_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(rf.forecast_date, '%Y-%m')
            ORDER BY forecast_month ASC
        ", [$this->user['company_id']]);
    }

    private function getBudgetAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT b.account_id) as budgeted_accounts,
                SUM(b.budget_amount) as total_budget,
                SUM(a.balance) as total_actual,
                ROUND(((SUM(a.balance) - SUM(b.budget_amount)) / NULLIF(SUM(b.budget_amount), 0)) * 100, 2) as overall_variance,
                COUNT(CASE WHEN ABS((a.balance - b.budget_amount) / NULLIF(b.budget_amount, 0)) > 0.1 THEN 1 END) as significant_variances,
                AVG(CASE WHEN b.budget_amount > 0 THEN (a.balance / b.budget_amount) ELSE 0 END) as budget_utilization
            FROM budgets b
            JOIN accounts a ON b.account_id = a.id
            WHERE b.company_id = ? AND b.budget_period = DATE_FORMAT(CURDATE(), '%Y-%m')
        ", [$this->user['company_id']]);
    }

    private function getBudgetSettings() {
        return $this->db->querySingle("
            SELECT * FROM budget_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getIncomeStatement() {
        return $this->db->query("
            SELECT
                a.account_name,
                a.account_subtype,
                a.balance,
                CASE
                    WHEN a.account_subtype = 'sales_revenue' THEN 'Revenue'
                    WHEN a.account_subtype = 'cost_of_goods_sold' THEN 'Cost of Goods Sold'
                    WHEN a.account_subtype = 'operating_expenses' THEN 'Operating Expenses'
                    WHEN a.account_subtype = 'interest_expense' THEN 'Interest Expense'
                    WHEN a.account_subtype = 'tax_expense' THEN 'Tax Expense'
                    ELSE 'Other'
                END as category
            FROM accounts a
            WHERE a.company_id = ? AND a.account_type IN ('revenue', 'expense')
            ORDER BY
                CASE a.account_type
                    WHEN 'revenue' THEN 1
                    WHEN 'expense' THEN 2
                END,
                a.account_subtype ASC
        ", [$this->user['company_id']]);
    }

    private function getCashFlowStatement() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(t.transaction_date, '%Y-%m') as month,
                SUM(CASE WHEN t.transaction_type = 'operating' THEN t.amount ELSE 0 END) as operating_activities,
                SUM(CASE WHEN t.transaction_type = 'investing' THEN t.amount ELSE 0 END) as investing_activities,
                SUM(CASE WHEN t.transaction_type = 'financing' THEN t.amount ELSE 0 END) as financing_activities,
                SUM(t.amount) as net_cash_flow,
                SUM(SUM(t.amount)) OVER (ORDER BY DATE_FORMAT(t.transaction_date, '%Y-%m')) as ending_cash_balance
            FROM transactions t
            WHERE t.company_id = ? AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(t.transaction_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getTrendAnalysis() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(t.transaction_date, '%Y-%m') as month,
                SUM(CASE WHEN a.account_type = 'revenue' THEN t.amount ELSE 0 END) as revenue,
                SUM(CASE WHEN a.account_type = 'expense' THEN t.amount ELSE 0 END) as expenses,
                (SUM(CASE WHEN a.account_type = 'revenue' THEN t.amount ELSE 0 END) - SUM(CASE WHEN a.account_type = 'expense' THEN t.amount ELSE 0 END)) as net_income,
                ROUND(((SUM(CASE WHEN a.account_type = 'revenue' THEN t.amount ELSE 0 END) - SUM(CASE WHEN a.account_type = 'expense' THEN t.amount ELSE 0 END)) / NULLIF(SUM(CASE WHEN a.account_type = 'revenue' THEN t.amount ELSE 0 END), 0)) * 100, 2) as profit_margin
            FROM transactions t
            JOIN accounts a ON t.account_id = a.id
            WHERE t.company_id = ? AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(t.transaction_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getSegmentReporting() {
        return $this->db->query("
            SELECT
                sr.segment_name,
                sr.segment_type,
                SUM(sr.revenue) as total_revenue,
                SUM(sr.expenses) as total_expenses,
                (SUM(sr.revenue) - SUM(sr.expenses)) as segment_profit,
                ROUND(((SUM(sr.revenue) - SUM(sr.expenses)) / NULLIF(SUM(sr.revenue), 0)) * 100, 2) as profit_margin,
                sr.reporting_period
            FROM segment_reporting sr
            WHERE sr.company_id = ?
            GROUP BY sr.segment_name, sr.segment_type, sr.reporting_period
            ORDER BY segment_profit DESC
        ", [$this->user['company_id']]);
    }

    private function getRegulatoryReports() {
        return $this->db->query("
            SELECT
                rr.*,
                rr.report_name,
                rr.report_type,
                rr.reporting_period,
                rr.submission_deadline,
                rr.status,
                rr.generated_date,
                rr.submitted_date,
                TIMESTAMPDIFF(DAY, CURDATE(), rr.submission_deadline) as days_until_deadline
            FROM regulatory_reports rr
            WHERE rr.company_id = ?
            ORDER BY rr.submission_deadline ASC
        ", [$this->user['company_id']]);
    }

    private function getCustomReports() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.report_name,
                cr.report_type,
                cr.created_date,
                cr.last_run_date,
                cr.run_count,
                u.first_name as created_by_first,
                u.last_name as created_by_last
            FROM custom_reports cr
            LEFT JOIN users u ON cr.created_by = u.id
            WHERE cr.company_id = ?
            ORDER BY cr.last_run_date DESC
        ", [$this->user['company_id']]);
    }

    private function getReportSchedules() {
        return $this->db->query("
            SELECT
                rs.*,
                rs.schedule_name,
                rs.report_type,
                rs.frequency,
                rs.next_run_date,
                rs.last_run_date,
                rs.recipients,
                rs.is_active,
                TIMESTAMPDIFF(DAY, CURDATE(), rs.next_run_date) as days_until_next
            FROM report_schedules rs
            WHERE rs.company_id = ?
            ORDER BY rs.next_run_date ASC
        ", [$this->user['company_id']]);
    }

    private function getTaxCalculations() {
        return $this->db->query("
            SELECT
                tc.*,
                tc.tax_year,
                tc.tax_type,
                tc.taxable_income,
                tc.tax_rate,
                tc.tax_amount,
                tc.payments_made,
                tc.balance_due,
                tc.filing_status
            FROM tax_calculations tc
            WHERE tc.company_id = ?
            ORDER BY tc.tax_year DESC
        ", [$this->user['company_id']]);
    }

    private function getTaxFilings() {
        return $this->db->query("
            SELECT
                tf.*,
                tf.tax_year,
                tf.filing_type,
                tf.filing_deadline,
                tf.submission_date,
                tf.status,
                tf.processing_fee,
                u.first_name as filed_by_first,
                u.last_name as filed_by_last
            FROM tax_filings tf
            LEFT JOIN users u ON tf.filed_by = u.id
            WHERE tf.company_id = ?
            ORDER BY tf.filing_deadline DESC
        ", [$this->user['company_id']]);
    }

    private function getTaxCredits() {
        return $this->db->query("
            SELECT
                tc.*,
                tc.credit_type,
                tc.credit_amount,
                tc.claim_date,
                tc.approval_date,
                tc.status,
                tc.expiration_date,
                TIMESTAMPDIFF(DAY, CURDATE(), tc.expiration_date) as days_until_expiration
            FROM tax_credits tc
            WHERE tc.company_id = ?
            ORDER BY tc.claim_date DESC
        ", [$this->user['company_id']]);
    }

    private function getTaxLiabilities() {
        return $this->db->querySingle("
            SELECT
                SUM(tc.tax_amount) as total_tax_liability,
                SUM(tc.payments_made) as total_payments_made,
                (SUM(tc.tax_amount) - SUM(tc.payments_made)) as outstanding_liability,
                COUNT(CASE WHEN tc.filing_status = 'pending' THEN 1 END) as pending_filings,
                COUNT(CASE WHEN tc.filing_status = 'overdue' THEN 1 END) as overdue_filings,
                MAX(tc.tax_year) as latest_tax_year
            FROM tax_calculations tc
            WHERE tc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTaxCompliance() {
        return $this->db->querySingle("
            SELECT
                COUNT(tf.id) as total_filings,
                COUNT(CASE WHEN tf.status = 'filed_on_time' THEN 1 END) as on_time_filings,
                ROUND((COUNT(CASE WHEN tf.status = 'filed_on_time' THEN 1 END) / NULLIF(COUNT(tf.id), 0)) * 100, 2) as compliance_rate,
                COUNT(CASE WHEN tf.filing_deadline < CURDATE() AND tf.status = 'pending' THEN 1 END) as overdue_filings,
                AVG(tc.tax_amount) as avg_tax_amount,
                SUM(tc.balance_due) as total_outstanding
            FROM tax_filings tf
            LEFT JOIN tax_calculations tc ON tf.tax_year = tc.tax_year AND tf.company_id = tc.company_id
            WHERE tf.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTaxPlanning() {
        return $this->db->query("
            SELECT
                tp.*,
                tp.planning_type,
                tp.target_tax_year,
                tp.estimated_savings,
                tp.implementation_cost,
                tp.roi_percentage,
                tp.priority_level,
                tp.implementation_date
            FROM tax_planning tp
            WHERE tp.company_id = ?
            ORDER BY tp.priority_level DESC, tp.estimated_savings DESC
        ", [$this->user['company_id']]);
    }

    private function getTaxAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT tc.tax_year) as tax_years_filed,
                SUM(tc.tax_amount) as total_tax_paid,
                AVG(tc.tax_rate) as avg_effective_rate,
                SUM(tc.balance_due) as current_liabilities,
                COUNT(tc.id) as total_calculations,
                MAX(tc.tax_year) as latest_year,
                AVG(tc.taxable_income) as avg_taxable_income
            FROM tax_calculations tc
            WHERE tc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTaxSettings() {
        return $this->db->querySingle("
            SELECT * FROM tax_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAssetRegister() {
        return $this->db->query("
            SELECT
                fa.*,
                fa.asset_name,
                fa.asset_code,
                fa.purchase_date,
                fa.purchase_cost,
                fa.accumulated_depreciation,
                (fa.purchase_cost - fa.accumulated_depreciation) as book_value,
                fa.depreciation_method,
                fa.useful_life_years,
                fa.location,
                fa.status
            FROM fixed_assets fa
            WHERE fa.company_id = ?
            ORDER BY fa.purchase_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationSchedule() {
        return $this->db->query("
            SELECT
                fa.asset_name,
                fa.asset_code,
                ds.depreciation_year,
                ds.depreciation_amount,
                ds.accumulated_depreciation,
                ds.book_value,
                ds.depreciation_method,
                ds.fiscal_year
            FROM fixed_assets fa
            JOIN depreciation_schedule ds ON fa.id = ds.asset_id
            WHERE fa.company_id = ?
            ORDER BY fa.asset_name, ds.depreciation_year
        ", [$this->user['company_id']]);
    }

    private function getAssetDisposals() {
        return $this->db->query("
            SELECT
                fa.asset_name,
                fa.asset_code,
                ad.disposal_date,
                ad.disposal_method,
                ad.disposal_proceeds,
                ad.book_value_at_disposal,
                (ad.disposal_proceeds - ad.book_value_at_disposal) as gain_loss,
                ad.disposal_reason,
                u.first_name as disposed_by_first,
                u.last_name as disposed_by_last
            FROM fixed_assets fa
            JOIN asset_disposals ad ON fa.id = ad.asset_id
            LEFT JOIN users u ON ad.disposed_by = u.id
            WHERE fa.company_id = ?
            ORDER BY ad.disposal_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetValuation() {
        return $this->db->querySingle("
            SELECT
                COUNT(fa.id) as total_assets,
                SUM(fa.purchase_cost) as total_cost,
                SUM(fa.accumulated_depreciation) as total_depreciation,
                SUM(fa.purchase_cost - fa.accumulated_depreciation) as total_book_value,
                AVG(fa.purchase_cost) as avg_asset_cost,
                AVG(fa.purchase_cost - fa.accumulated_depreciation) as avg_book_value,
                COUNT(CASE WHEN fa.status = 'active' THEN 1 END) as active_assets,
                COUNT(CASE WHEN fa.status = 'disposed' THEN 1 END) as disposed_assets
            FROM fixed_assets fa
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getLeaseAccounting() {
        return $this->db->query("
            SELECT
                la.*,
                la.lease_description,
                la.lease_start_date,
                la.lease_end_date,
                la.monthly_payment,
                la.present_value,
                la.interest_rate,
                la.lease_type,
                la.remaining_term_months,
                la.unamortized_balance
            FROM lease_accounting la
            WHERE la.company_id = ?
            ORDER BY la.lease_end_date ASC
        ", [$this->user['company_id']]);
    }

    private function getImpairmentTesting() {
        return $this->db->query("
            SELECT
                fa.asset_name,
                fa.asset_code,
                it.testing_date,
                it.carrying_amount,
                it.recoverable_amount,
                it.impairment_loss,
                it.testing_method,
                it.testing_frequency,
                u.first_name as tested_by_first,
                u.last_name as tested_by_last
            FROM fixed_assets fa
            JOIN impairment_testing it ON fa.id = it.asset_id
            LEFT JOIN users u ON it.tested_by = u.id
            WHERE fa.company_id = ?
            ORDER BY it.testing_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(fa.id) as total_assets,
                SUM(fa.purchase_cost) as total_acquisition_cost,
                SUM(fa.accumulated_depreciation) as total_depreciation,
                AVG(fa.purchase_cost) as avg_asset_cost,
                AVG(fa.useful_life_years) as avg_useful_life,
                COUNT(DISTINCT fa.location) as locations_used,
                COUNT(CASE WHEN fa.status = 'active' THEN 1 END) as active_assets,
                ROUND((SUM(fa.accumulated_depreciation) / NULLIF(SUM(fa.purchase_cost), 0)) * 100, 2) as overall_depreciation_rate
            FROM fixed_assets fa
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAssetSettings() {
        return $this->db->querySingle("
            SELECT * FROM asset_settings
            WHERE company_id = ?
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
                cr.source,
                cr.last_updated,
                TIMESTAMPDIFF(HOUR, cr.last_updated, NOW()) as hours_since_update
            FROM currency_rates cr
            WHERE cr.company_id = ?
            ORDER BY cr.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyPositions() {
        return $this->db->query("
            SELECT
                cp.*,
                cp.currency_code,
                cp.balance_amount,
                cp.average_rate,
                cp.current_rate,
                (cp.balance_amount * (cp.current_rate - cp.average_rate)) as unrealized_gain_loss,
                cp.position_type,
                cp.maturity_date,
                TIMESTAMPDIFF(DAY, CURDATE(), cp.maturity_date) as days_to_maturity
            FROM currency_positions cp
            WHERE cp.company_id = ?
            ORDER BY ABS(cp.balance_amount) DESC
        ", [$this->user['company_id']]);
    }

    private function getHedgingStrategies() {
        return $this->db->query("
            SELECT
                hs.*,
                hs.strategy_name,
                hs.hedge_type,
                hs.notional_amount,
                hs.hedge_rate,
                hs.maturity_date,
                hs.effectiveness_percentage,
                hs.counterparty,
                TIMESTAMPDIFF(DAY, CURDATE(), hs.maturity_date) as days_to_maturity
            FROM hedging_strategies hs
            WHERE hs.company_id = ?
            ORDER BY hs.maturity_date ASC
        ", [$this->user['company_id']]);
    }

    private function getFXGainsLosses() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(fx.transaction_date, '%Y-%m') as month,
                SUM(fx.gain_loss_amount) as total_fx_gain_loss,
                SUM(CASE WHEN fx.gain_loss_amount > 0 THEN fx.gain_loss_amount ELSE 0 END) as realized_gains,
                SUM(CASE WHEN fx.gain_loss_amount < 0 THEN ABS(fx.gain_loss_amount) ELSE 0 END) as realized_losses,
                COUNT(fx.id) as fx_transactions,
                AVG(fx.exchange_rate) as avg_exchange_rate
            FROM fx_gains_losses fx
            WHERE fx.company_id = ? AND fx.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(fx.transaction_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyRisk() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT cp.currency_code) as currencies_held,
                SUM(ABS(cp.balance_amount)) as total_exposure,
                SUM(ABS(cp.balance_amount * (cp.current_rate - cp.average_rate))) as total_unrealized_risk,
                COUNT(CASE WHEN ABS(cp.balance_amount * (cp.current_rate - cp.average_rate)) > 10000 THEN 1 END) as high_risk_positions,
                AVG(ABS(cp.balance_amount * (cp.current_rate - cp.average_rate))) as avg_position_risk,
                COUNT(hs.id) as active_hedges,
                ROUND((COUNT(hs.id) / NULLIF(COUNT(DISTINCT cp.currency_code), 0)) * 100, 2) as hedge_coverage_percentage
            FROM currency_positions cp
            LEFT JOIN hedging_strategies hs ON cp.currency_code = hs.currency_code AND hs.company_id = cp.company_id
            WHERE cp.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTranslationAdjustments() {
        return $this->db->query("
            SELECT
                ta.*,
                ta.adjustment_period,
                ta.functional_currency,
                ta.foreign_currency,
                ta.exchange_rate,
                ta.adjustment_amount,
                ta.adjustment_type,
                ta.affected_accounts,
                ta.created_date
            FROM translation_adjustments ta
            WHERE ta.company_id = ?
            ORDER BY ta.created_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT cr.from_currency) as currencies_used,
                COUNT(cr.id) as total_rate_updates,
                AVG(TIMESTAMPDIFF(HOUR, cr.last_updated, NOW())) as avg_update_frequency,
                COUNT(DISTINCT cp.currency_code) as currencies_with_positions,
                SUM(ABS(cp.balance_amount)) as total_currency_exposure,
                COUNT(CASE WHEN ABS(cp.balance_amount * (cp.current_rate - cp.average_rate)) > 5000 THEN 1 END) as significant_exposure_positions
            FROM currency_rates cr
            LEFT JOIN currency_positions cp ON cr.from_currency = cp.currency_code AND cr.company_id = cp.company_id
            WHERE cr.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCurrencySettings() {
        return $this->db->querySingle("
            SELECT * FROM currency_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getProfitabilityAnalysis() {
        return $this->db->query("
            SELECT
                pa.product_service,
                pa.revenue,
                pa.direct_costs,
                pa.contribution_margin,
                ROUND((pa.contribution_margin / NULLIF(pa.revenue, 0)) * 100, 2) as contribution_margin_percentage,
                pa.indirect_costs,
                pa.net_profit,
                ROUND((pa.net_profit / NULLIF(pa.revenue, 0)) * 100, 2) as profit_margin_percentage,
                pa.analysis_period
            FROM profitability_analysis pa
            WHERE pa.company_id = ?
            ORDER BY pa.net_profit DESC
        ", [$this->user['company_id']]);
    }

    private function getCashFlowAnalysis() {
        return $this->db->querySingle("
            SELECT
                SUM(CASE WHEN cf.cash_flow_type = 'operating' THEN cf.amount ELSE 0 END) as operating_cash_flow,
                SUM(CASE WHEN cf.cash_flow_type = 'investing' THEN cf.amount ELSE 0 END) as investing_cash_flow,
                SUM(CASE WHEN cf.cash_flow_type = 'financing' THEN cf.amount ELSE 0 END) as financing_cash_flow,
                SUM(cf.amount) as net_cash_flow,
                COUNT(CASE WHEN cf.amount < 0 THEN 1 END) as negative_cash_flow_periods,
                AVG(cf.amount) as avg_monthly_cash_flow,
                MIN(cf.amount) as lowest_cash_flow,
                MAX(cf.amount) as highest_cash_flow
            FROM cash_flow_analysis cf
            WHERE cf.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getWorkingCapital() {
        return $this->db->querySingle("
            SELECT
                SUM(CASE WHEN a.account_subtype = 'current_assets' THEN a.balance ELSE 0 END) as current_assets,
                SUM(CASE WHEN a.account_subtype = 'current_liabilities' THEN a.balance ELSE 0 END) as current_liabilities,
                (SUM(CASE WHEN a.account_subtype = 'current_assets' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_subtype = 'current_liabilities' THEN a.balance ELSE 0 END)) as working_capital,
                ROUND(((SUM(CASE WHEN a.account_subtype = 'current_assets' THEN a.balance ELSE 0 END) - SUM(CASE WHEN a.account_subtype = 'current_liabilities' THEN a.balance ELSE 0 END)) / NULLIF(SUM(CASE WHEN a.account_subtype = 'current_liabilities' THEN a.balance ELSE 0 END), 0)), 2) as working_capital_ratio,
                SUM(CASE WHEN a.account_subtype = 'inventory' THEN a.balance ELSE 0 END) as inventory_value,
                SUM(CASE WHEN a.account_subtype = 'receivables' THEN a.balance ELSE 0 END) as receivables_value,
                SUM(CASE WHEN a.account_subtype = 'payables' THEN a.balance ELSE 0 END) as payables_value
            FROM accounts a
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBenchmarking() {
        return $this->db->query("
            SELECT
                b.metric_name,
                b.company_value,
                b.industry_average,
                b.top_performer,
                ROUND(((b.company_value - b.industry_average) / NULLIF(b.industry_average, 0)) * 100, 2) as variance_from_average,
                CASE
                    WHEN b.company_value >= b.top_performer THEN 'leader'
                    WHEN b.company_value >= b.industry_average THEN 'above_average'
                    WHEN b.company_value >= b.industry_average * 0.9 THEN 'average'
                    ELSE 'below_average'
                END as performance_level,
                b.benchmark_period
            FROM benchmarking b
            WHERE b.company_id = ?
            ORDER BY ABS(b.company_value - b.industry_average) DESC
        ", [$this->user['company_id']]);
    }

    private function getPredictiveModeling() {
        return $this->db->query("
            SELECT
                pm.model_name,
                pm.target_metric,
                pm.prediction_accuracy,
                pm.confidence_interval,
                pm.prediction_horizon,
                pm.last_run_date,
                pm.next_run_date,
                pm.model_status,
                TIMESTAMPDIFF(DAY, CURDATE(), pm.next_run_date) as days_until_next_run
            FROM predictive_models pm
            WHERE pm.company_id = ?
            ORDER BY pm.prediction_accuracy DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomDashboards() {
        return $this->db->query("
            SELECT
                cd.*,
                cd.dashboard_name,
                cd.dashboard_type,
                cd.created_date,
                cd.last_modified,
                cd.created_by,
                cd.access_level,
                COUNT(cdw.id) as widget_count
            FROM custom_dashboards cd
            LEFT JOIN custom_dashboard_widgets cdw ON cd.id = cdw.dashboard_id
            WHERE cd.company_id = ?
            GROUP BY cd.id
            ORDER BY cd.last_modified DESC
        ", [$this->user['company_id']]);
    }
}
