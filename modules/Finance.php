<?php
/**
 * TPT Free ERP - Finance & Accounting Module
 * Complete financial management, accounting, and reporting system
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
            'title' => 'Finance & Accounting',
            'financial_overview' => $this->getFinancialOverview(),
            'cash_flow' => $this->getCashFlowSummary(),
            'profit_loss' => $this->getProfitLossSummary(),
            'balance_sheet' => $this->getBalanceSheetSummary(),
            'pending_transactions' => $this->getPendingTransactions(),
            'financial_ratios' => $this->getFinancialRatios(),
            'budget_vs_actual' => $this->getBudgetVsActual(),
            'recent_activity' => $this->getRecentFinancialActivity()
        ];

        $this->render('modules/finance/dashboard', $data);
    }

    /**
     * General ledger management
     */
    public function generalLedger() {
        $this->requirePermission('finance.gl.view');

        $filters = [
            'account_id' => $_GET['account_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? date('Y-m-01'),
            'date_to' => $_GET['date_to'] ?? date('Y-m-t'),
            'transaction_type' => $_GET['transaction_type'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $ledger_entries = $this->getLedgerEntries($filters);

        $data = [
            'title' => 'General Ledger',
            'ledger_entries' => $ledger_entries,
            'filters' => $filters,
            'chart_of_accounts' => $this->getChartOfAccounts(),
            'account_balances' => $this->getAccountBalances(),
            'ledger_summary' => $this->getLedgerSummary($filters),
            'posting_rules' => $this->getPostingRules()
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
            'payment_terms' => $this->getPaymentTerms(),
            'aging_report' => $this->getAPAgingReport(),
            'cash_requirements' => $this->getCashRequirements(),
            'vendor_statements' => $this->getVendorStatements(),
            'payment_schedules' => $this->getPaymentSchedules(),
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
            'payment_terms' => $this->getARTerms(),
            'aging_report' => $this->getARAgingReport(),
            'collections' => $this->getCollections(),
            'customer_statements' => $this->getCustomerStatements(),
            'credit_limits' => $this->getCreditLimits(),
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
            'budgets' => $this->getBudgets(),
            'budget_categories' => $this->getBudgetCategories(),
            'forecasts' => $this->getForecasts(),
            'budget_vs_actual' => $this->getBudgetVsActualDetailed(),
            'variance_analysis' => $this->getVarianceAnalysis(),
            'budget_templates' => $this->getBudgetTemplates(),
            'forecasting_models' => $this->getForecastingModels()
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
            'financial_ratios' => $this->getDetailedFinancialRatios(),
            'trend_analysis' => $this->getTrendAnalysis(),
            'comparative_reports' => $this->getComparativeReports(),
            'custom_reports' => $this->getCustomFinancialReports(),
            'report_templates' => $this->getReportTemplates()
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
            'tax_rates' => $this->getTaxRates(),
            'tax_codes' => $this->getTaxCodes(),
            'tax_returns' => $this->getTaxReturns(),
            'tax_calculations' => $this->getTaxCalculations(),
            'tax_compliance' => $this->getTaxCompliance(),
            'tax_analytics' => $this->getTaxAnalytics(),
            'tax_settings' => $this->getTaxSettings()
        ];

        $this->render('modules/finance/tax_management', $data);
    }

    /**
     * Multi-currency management
     */
    public function currencies() {
        $this->requirePermission('finance.currency.view');

        $data = [
            'title' => 'Multi-Currency Management',
            'exchange_rates' => $this->getExchangeRates(),
            'currency_accounts' => $this->getCurrencyAccounts(),
            'currency_transactions' => $this->getCurrencyTransactions(),
            'hedging' => $this->getHedgingPositions(),
            'currency_risk' => $this->getCurrencyRisk(),
            'conversion_rules' => $this->getConversionRules(),
            'currency_analytics' => $this->getCurrencyAnalytics()
        ];

        $this->render('modules/finance/currencies', $data);
    }

    /**
     * Fixed assets management
     */
    public function fixedAssets() {
        $this->requirePermission('finance.assets.view');

        $data = [
            'title' => 'Fixed Assets Management',
            'assets' => $this->getFixedAssets(),
            'depreciation' => $this->getDepreciationSchedule(),
            'asset_categories' => $this->getAssetCategories(),
            'disposals' => $this->getAssetDisposals(),
            'maintenance' => $this->getAssetMaintenance(),
            'asset_register' => $this->getAssetRegister(),
            'asset_analytics' => $this->getAssetAnalytics()
        ];

        $this->render('modules/finance/fixed_assets', $data);
    }

    /**
     * Financial analytics
     */
    public function analytics() {
        $this->requirePermission('finance.analytics.view');

        $data = [
            'title' => 'Financial Analytics',
            'kpi_dashboard' => $this->getKPIDashboard(),
            'trend_analysis' => $this->getFinancialTrendAnalysis(),
            'benchmarking' => $this->getBenchmarking(),
            'scenario_planning' => $this->getScenarioPlanning(),
            'sensitivity_analysis' => $this->getSensitivityAnalysis(),
            'predictive_modeling' => $this->getFinancialPredictiveModeling(),
            'risk_assessment' => $this->getFinancialRiskAssessment()
        ];

        $this->render('modules/finance/analytics', $data);
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
                COUNT(DISTINCT CASE WHEN status = 'pending' THEN id END) as pending_transactions,
                AVG(CASE WHEN account_type = 'asset' THEN balance END) as avg_asset_balance
            FROM accounts a
            LEFT JOIN account_balances ab ON a.id = ab.account_id
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCashFlowSummary() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(transaction_date, '%Y-%m') as month,
                SUM(CASE WHEN transaction_type = 'cash_inflow' THEN amount ELSE 0 END) as cash_inflow,
                SUM(CASE WHEN transaction_type = 'cash_outflow' THEN amount ELSE 0 END) as cash_outflow,
                SUM(CASE WHEN transaction_type = 'cash_inflow' THEN amount ELSE 0 END) -
                SUM(CASE WHEN transaction_type = 'cash_outflow' THEN amount ELSE 0 END) as net_cash_flow
            FROM cash_flow_transactions
            WHERE company_id = ? AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getProfitLossSummary() {
        return $this->db->querySingle("
            SELECT
                SUM(revenue) as total_revenue,
                SUM(cost_of_goods_sold) as total_cogs,
                SUM(operating_expenses) as total_opex,
                SUM(other_income) as total_other_income,
                SUM(other_expenses) as total_other_expenses,
                (SUM(revenue) - SUM(cost_of_goods_sold) - SUM(operating_expenses) +
                 SUM(other_income) - SUM(other_expenses)) as net_profit,
                ROUND(((SUM(revenue) - SUM(cost_of_goods_sold) - SUM(operating_expenses) +
                       SUM(other_income) - SUM(other_expenses)) / NULLIF(SUM(revenue), 0)) * 100, 2) as profit_margin
            FROM profit_loss_summary
            WHERE company_id = ? AND period = 'current_month'
        ", [$this->user['company_id']]);
    }

    private function getBalanceSheetSummary() {
        return $this->db->querySingle("
            SELECT
                SUM(current_assets) as current_assets,
                SUM(fixed_assets) as fixed_assets,
                SUM(current_liabilities) as current_liabilities,
                SUM(long_term_liabilities) as long_term_liabilities,
                SUM(equity) as equity,
                (SUM(current_assets) + SUM(fixed_assets)) as total_assets,
                (SUM(current_liabilities) + SUM(long_term_liabilities) + SUM(equity)) as total_liabilities_equity,
                ROUND((SUM(current_assets) / NULLIF(SUM(current_liabilities), 0)), 2) as current_ratio
            FROM balance_sheet_summary
            WHERE company_id = ? AND as_of_date = CURDATE()
        ", [$this->user['company_id']]);
    }

    private function getPendingTransactions() {
        return $this->db->query("
            SELECT
                pt.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                TIMESTAMPDIFF(DAY, pt.created_at, NOW()) as days_pending,
                pt.amount,
                pt.description
            FROM pending_transactions pt
            LEFT JOIN users u ON pt.created_by = u.id
            WHERE pt.company_id = ? AND pt.status = 'pending'
            ORDER BY pt.created_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getFinancialRatios() {
        return $this->db->querySingle("
            SELECT
                current_ratio,
                quick_ratio,
                debt_to_equity_ratio,
                return_on_assets,
                return_on_equity,
                gross_profit_margin,
                operating_profit_margin,
                net_profit_margin,
                asset_turnover_ratio,
                inventory_turnover_ratio
            FROM financial_ratios
            WHERE company_id = ? AND calculated_date = (
                SELECT MAX(calculated_date) FROM financial_ratios WHERE company_id = ?
            )
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getBudgetVsActual() {
        return $this->db->query("
            SELECT
                category,
                SUM(budget_amount) as budget_amount,
                SUM(actual_amount) as actual_amount,
                ROUND(((SUM(actual_amount) - SUM(budget_amount)) / NULLIF(SUM(budget_amount), 0)) * 100, 2) as variance_percentage,
                CASE
                    WHEN SUM(actual_amount) > SUM(budget_amount) THEN 'over_budget'
                    WHEN SUM(actual_amount) < SUM(budget_amount) THEN 'under_budget'
                    ELSE 'on_budget'
                END as budget_status
            FROM budget_vs_actual
            WHERE company_id = ? AND period = 'current_month'
            GROUP BY category
            ORDER BY ABS(SUM(actual_amount) - SUM(budget_amount)) DESC
        ", [$this->user['company_id']]);
    }

    private function getRecentFinancialActivity() {
        return $this->db->query("
            SELECT
                fa.*,
                u.first_name as user_first,
                u.last_name as user_last,
                fa.activity_type,
                fa.description,
                fa.amount,
                TIMESTAMPDIFF(MINUTE, fa.created_at, NOW()) as minutes_ago
            FROM financial_activity fa
            LEFT JOIN users u ON fa.user_id = u.id
            WHERE fa.company_id = ?
            ORDER BY fa.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getLedgerEntries($filters) {
        $where = ["le.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['account_id']) {
            $where[] = "le.account_id = ?";
            $params[] = $filters['account_id'];
        }

        if ($filters['date_from']) {
            $where[] = "le.transaction_date >= ?";
            $params[] = $filters['date_from'];
        }

        if ($filters['date_to']) {
            $where[] = "le.transaction_date <= ?";
            $params[] = $filters['date_to'];
        }

        if ($filters['transaction_type']) {
            $where[] = "le.transaction_type = ?";
            $params[] = $filters['transaction_type'];
        }

        if ($filters['search']) {
            $where[] = "(le.description LIKE ? OR le.reference LIKE ?)";
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
                u.first_name as posted_by_first,
                u.last_name as posted_by_last,
                le.debit_amount,
                le.credit_amount,
                le.transaction_date,
                le.description
            FROM ledger_entries le
            JOIN accounts a ON le.account_id = a.id
            LEFT JOIN users u ON le.posted_by = u.id
            WHERE $whereClause
            ORDER BY le.transaction_date DESC, le.id DESC
        ", $params);
    }

    private function getChartOfAccounts() {
        return $this->db->query("
            SELECT
                a.*,
                COUNT(le.id) as transaction_count,
                MAX(le.transaction_date) as last_transaction,
                a.balance,
                a.account_type,
                a.sub_type
            FROM accounts a
            LEFT JOIN ledger_entries le ON a.id = le.account_id
            WHERE a.company_id = ?
            GROUP BY a.id
            ORDER BY a.account_code ASC
        ", [$this->user['company_id']]);
    }

    private function getAccountBalances() {
        return $this->db->query("
            SELECT
                a.account_name,
                a.account_code,
                a.account_type,
                ab.balance,
                ab.last_updated,
                ab.currency_code
            FROM accounts a
            JOIN account_balances ab ON a.id = ab.account_id
            WHERE a.company_id = ?
            ORDER BY a.account_code ASC
        ", [$this->user['company_id']]);
    }

    private function getLedgerSummary($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['account_id']) {
            $where[] = "account_id = ?";
            $params[] = $filters['account_id'];
        }

        if ($filters['date_from']) {
            $where[] = "transaction_date >= ?";
            $params[] = $filters['date_from'];
        }

        if ($filters['date_to']) {
            $where[] = "transaction_date <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_entries,
                SUM(debit_amount) as total_debits,
                SUM(credit_amount) as total_credits,
                COUNT(DISTINCT account_id) as accounts_affected,
                MIN(transaction_date) as earliest_date,
                MAX(transaction_date) as latest_date
            FROM ledger_entries
            WHERE $whereClause
        ", $params);
    }

    private function getPostingRules() {
        return $this->db->query("
            SELECT * FROM posting_rules
            WHERE company_id = ? AND is_active = true
            ORDER BY rule_type, name
        ", [$this->user['company_id']]);
    }

    private function getVendorInvoices() {
        return $this->db->query("
            SELECT
                vi.*,
                v.vendor_name,
                v.vendor_code,
                pt.terms_name,
                vi.invoice_amount,
                vi.amount_paid,
                vi.amount_due,
                vi.due_date,
                TIMESTAMPDIFF(DAY, CURDATE(), vi.due_date) as days_until_due,
                CASE
                    WHEN vi.due_date < CURDATE() AND vi.amount_due > 0 THEN 'overdue'
                    WHEN vi.due_date >= CURDATE() AND vi.amount_due > 0 THEN 'pending'
                    ELSE 'paid'
                END as payment_status
            FROM vendor_invoices vi
            JOIN vendors v ON vi.vendor_id = v.id
            LEFT JOIN payment_terms pt ON vi.payment_terms_id = pt.id
            WHERE vi.company_id = ?
            ORDER BY vi.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getPaymentTerms() {
        return $this->db->query("
            SELECT * FROM payment_terms
            WHERE company_id = ? AND is_active = true
            ORDER BY net_days ASC
        ", [$this->user['company_id']]);
    }

    private function getAPAgingReport() {
        return $this->db->query("
            SELECT
                CASE
                    WHEN TIMESTAMPDIFF(DAY, vi.due_date, CURDATE()) <= 0 THEN 'current'
                    WHEN TIMESTAMPDIFF(DAY, vi.due_date, CURDATE()) <= 30 THEN '1-30_days'
                    WHEN TIMESTAMPDIFF(DAY, vi.due_date, CURDATE()) <= 60 THEN '31-60_days'
                    WHEN TIMESTAMPDIFF(DAY, vi.due_date, CURDATE()) <= 90 THEN '61-90_days'
                    ELSE '90+_days'
                END as aging_bucket,
                COUNT(*) as invoice_count,
                SUM(vi.amount_due) as total_amount,
                AVG(vi.amount_due) as avg_amount
            FROM vendor_invoices vi
            WHERE vi.company_id = ? AND vi.amount_due > 0
            GROUP BY
                CASE
                    WHEN TIMESTAMPDIFF(DAY, vi.due_date, CURDATE()) <= 0 THEN 'current'
                    WHEN TIMESTAMPDIFF(DAY, vi.due_date, CURDATE()) <= 30 THEN '1-30_days'
                    WHEN TIMESTAMPDIFF(DAY, vi.due_date, CURDATE()) <= 60 THEN '31-60_days'
                    WHEN TIMESTAMPDIFF(DAY, vi.due_date, CURDATE()) <= 90 THEN '61-90_days'
                    ELSE '90+_days'
                END
            ORDER BY aging_bucket
        ", [$this->user['company_id']]);
    }

    private function getCashRequirements() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(vi.due_date, '%Y-%m-%d') as due_date,
                COUNT(*) as invoice_count,
                SUM(vi.amount_due) as total_due,
                GROUP_CONCAT(DISTINCT v.vendor_name SEPARATOR ', ') as vendors
            FROM vendor_invoices vi
            JOIN vendors v ON vi.vendor_id = v.id
            WHERE vi.company_id = ? AND vi.amount_due > 0
                AND vi.due_date >= CURDATE()
                AND vi.due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE_FORMAT(vi.due_date, '%Y-%m-%d')
            ORDER BY due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getVendorStatements() {
        return $this->db->query("
            SELECT
                v.vendor_name,
                v.vendor_code,
                COUNT(vi.id) as total_invoices,
                SUM(vi.invoice_amount) as total_invoice_amount,
                SUM(vi.amount_paid) as total_paid,
                SUM(vi.amount_due) as total_due,
                MAX(vi.invoice_date) as last_invoice_date,
                AVG(vi.invoice_amount) as avg_invoice_amount
            FROM vendors v
            LEFT JOIN vendor_invoices vi ON v.id = vi.vendor_id
            WHERE v.company_id = ?
            GROUP BY v.id, v.vendor_name, v.vendor_code
            ORDER BY total_due DESC
        ", [$this->user['company_id']]);
    }

    private function getPaymentSchedules() {
        return $this->db->query("
            SELECT
                ps.*,
                v.vendor_name,
                ps.scheduled_amount,
                ps.scheduled_date,
                TIMESTAMPDIFF(DAY, CURDATE(), ps.scheduled_date) as days_until_payment,
                ps.payment_method,
                ps.status
            FROM payment_schedules ps
            JOIN vendors v ON ps.vendor_id = v.id
            WHERE ps.company_id = ? AND ps.status = 'scheduled'
            ORDER BY ps.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getAPAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(vi.id) as total_invoices,
                SUM(vi.invoice_amount) as total_invoice_amount,
                SUM(vi.amount_due) as total_outstanding,
                AVG(vi.invoice_amount) as avg_invoice_amount,
                COUNT(CASE WHEN vi.due_date < CURDATE() AND vi.amount_due > 0 THEN 1 END) as overdue_invoices,
                SUM(CASE WHEN vi.due_date < CURDATE() AND vi.amount_due > 0 THEN vi.amount_due END) as overdue_amount,
                ROUND((SUM(vi.amount_due) / NULLIF(SUM(vi.invoice_amount), 0)) * 100, 2) as outstanding_percentage
            FROM vendor_invoices vi
            WHERE vi.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCustomerInvoices() {
        return $this->db->query("
            SELECT
                ci.*,
                c.customer_name,
                c.customer_code,
                pt.terms_name,
                ci.invoice_amount,
                ci.amount_received,
                ci.amount_due,
                ci.due_date,
                TIMESTAMPDIFF(DAY, CURDATE(), ci.due_date) as days_until_due,
                CASE
                    WHEN ci.due_date < CURDATE() AND ci.amount_due > 0 THEN 'overdue'
                    WHEN ci.due_date >= CURDATE() AND ci.amount_due > 0 THEN 'pending'
                    ELSE 'paid'
                END as payment_status
            FROM customer_invoices ci
            JOIN customers c ON ci.customer_id = c.id
            LEFT JOIN payment_terms pt ON ci.payment_terms_id = pt.id
            WHERE ci.company_id = ?
            ORDER BY ci.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getARTerms() {
        return $this->db->query("
            SELECT * FROM ar_payment_terms
            WHERE company_id = ? AND is_active = true
            ORDER BY net_days ASC
        ", [$this->user['company_id']]);
    }

    private function getARAgingReport() {
        return $this->db->query("
            SELECT
                CASE
                    WHEN TIMESTAMPDIFF(DAY, ci.due_date, CURDATE()) <= 0 THEN 'current'
                    WHEN TIMESTAMPDIFF(DAY, ci.due_date, CURDATE()) <= 30 THEN '1-30_days'
                    WHEN TIMESTAMPDIFF(DAY, ci.due_date, CURDATE()) <= 60 THEN '31-60_days'
                    WHEN TIMESTAMPDIFF(DAY, ci.due_date, CURDATE()) <= 90 THEN '61-90_days'
                    ELSE '90+_days'
                END as aging_bucket,
                COUNT(*) as invoice_count,
                SUM(ci.amount_due) as total_amount,
                AVG(ci.amount_due) as avg_amount
            FROM customer_invoices ci
            WHERE ci.company_id = ? AND ci.amount_due > 0
            GROUP BY
                CASE
                    WHEN TIMESTAMPDIFF(DAY, ci.due_date, CURDATE()) <= 0 THEN 'current'
                    WHEN TIMESTAMPDIFF(DAY, ci.due_date, CURDATE()) <= 30 THEN '1-30_days'
                    WHEN TIMESTAMPDIFF(DAY, ci.due_date, CURDATE()) <= 60 THEN '31-60_days'
                    WHEN TIMESTAMPDIFF(DAY, ci.due_date, CURDATE()) <= 90 THEN '61-90_days'
                    ELSE '90+_days'
                END
            ORDER BY aging_bucket
        ", [$this->user['company_id']]);
    }

    private function getCollections() {
        return $this->db->query("
            SELECT
                col.*,
                c.customer_name,
                ci.invoice_number,
                col.collection_amount,
                col.collection_date,
                col.collection_method,
                col.status
            FROM collections col
            JOIN customers c ON col.customer_id = c.id
            LEFT JOIN customer_invoices ci ON col.invoice_id = ci.id
            WHERE col.company_id = ?
            ORDER BY col.collection_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerStatements() {
        return $this->db->query("
            SELECT
                c.customer_name,
                c.customer_code,
                COUNT(ci.id) as total_invoices,
                SUM(ci.invoice_amount) as total_invoice_amount,
                SUM(ci.amount_received) as total_received,
                SUM(ci.amount_due) as total_due,
                MAX(ci.invoice_date) as last_invoice_date,
                AVG(ci.invoice_amount) as avg_invoice_amount
            FROM customers c
            LEFT JOIN customer_invoices ci ON c.id = ci.customer_id
            WHERE c.company_id = ?
            GROUP BY c.id, c.customer_name, c.customer_code
            ORDER BY total_due DESC
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
                ROUND((cl.used_credit / NULLIF(cl.credit_limit, 0)) * 100, 2) as utilization_percentage,
                cl.last_review_date,
                cl.next_review_date
            FROM customers c
            JOIN credit_limits cl ON c.id = cl.customer_id
            WHERE c.company_id = ?
            ORDER BY utilization_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getARAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(ci.id) as total_invoices,
                SUM(ci.invoice_amount) as total_invoice_amount,
                SUM(ci.amount_due) as total_outstanding,
                AVG(ci.invoice_amount) as avg_invoice_amount,
                COUNT(CASE WHEN ci.due_date < CURDATE() AND ci.amount_due > 0 THEN 1 END) as overdue_invoices,
                SUM(CASE WHEN ci.due_date < CURDATE() AND ci.amount_due > 0 THEN ci.amount_due END) as overdue_amount,
                ROUND((SUM(ci.amount_due) / NULLIF(SUM(ci.invoice_amount), 0)) * 100, 2) as outstanding_percentage,
                AVG(TIMESTAMPDIFF(DAY, ci.invoice_date, ci.due_date)) as avg_payment_terms
            FROM customer_invoices ci
            WHERE ci.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBudgets() {
        return $this->db->query("
            SELECT
                b.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                bc.category_name,
                b.budget_amount,
                b.actual_amount,
                ROUND(((b.actual_amount - b.budget_amount) / NULLIF(b.budget_amount, 0)) * 100, 2) as variance_percentage,
                b.period,
                b.status
            FROM budgets b
            LEFT JOIN users u ON b.created_by = u.id
            LEFT JOIN budget_categories bc ON b.category_id = bc.id
            WHERE b.company_id = ?
            ORDER BY b.period DESC, b.category_id ASC
        ", [$this->user['company_id']]);
    }

    private function getBudgetCategories() {
        return $this->db->query("
            SELECT
                bc.*,
                COUNT(b.id) as budget_count,
                SUM(b.budget_amount) as total_budget,
                SUM(b.actual_amount) as total_actual
            FROM budget_categories bc
            LEFT JOIN budgets b ON bc.id = b.category_id
            WHERE bc.company_id = ?
            GROUP BY bc.id
            ORDER BY bc.category_name ASC
        ", [$this->user['company_id']]);
    }

    private function getForecasts() {
        return $this->db->query("
            SELECT
                f.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                f.forecast_period,
                f.forecast_amount,
                f.confidence_level,
                f.actual_amount,
                ROUND(((f.actual_amount - f.forecast_amount) / NULLIF(f.forecast_amount, 0)) * 100, 2) as accuracy_percentage
            FROM forecasts f
            LEFT JOIN users u ON f.created_by = u.id
            WHERE f.company_id = ?
            ORDER BY f.forecast_period DESC
        ", [$this->user['company_id']]);
    }

    private function getBudgetVsActualDetailed() {
        return $this->db->query("
            SELECT
                bc.category_name,
                b.period,
                SUM(b.budget_amount) as budget_amount,
                SUM(b.actual_amount) as actual_amount,
                ROUND(((SUM(b.actual_amount) - SUM(b.budget_amount)) / NULLIF(SUM(b.budget_amount), 0)) * 100, 2) as variance_percentage,
                CASE
                    WHEN SUM(b.actual_amount) > SUM(b.budget_amount) THEN 'over_budget'
                    WHEN SUM(b.actual_amount) < SUM(b.budget_amount) THEN 'under_budget'
                    ELSE 'on_budget'
                END as budget_status
            FROM budgets b
            JOIN budget_categories bc ON b.category_id = bc.id
            WHERE b.company_id = ?
            GROUP BY bc.category_name, b.period
            ORDER BY b.period DESC, bc.category_name ASC
        ", [$this->user['company_id']]);
    }

    private function getVarianceAnalysis() {
        return $this->db->query("
            SELECT
                bc.category_name,
                b.period,
                b.budget_amount,
                b.actual_amount,
                (b.actual_amount - b.budget_amount) as variance_amount,
                ROUND(((b.actual_amount - b.budget_amount) / NULLIF(b.budget_amount, 0)) * 100, 2) as variance_percentage,
                CASE
                    WHEN b.actual_amount > b.budget_amount THEN 'unfavorable'
                    WHEN b.actual_amount < b.budget_amount THEN 'favorable'
                    ELSE 'neutral'
                END as variance_type
            FROM budgets b
            JOIN budget_categories bc ON b.category_id = bc.id
            WHERE b.company_id = ?
            ORDER BY ABS(b.actual_amount - b.budget_amount) DESC
        ", [$this->user['company_id']]);
    }

    private function getBudgetTemplates() {
        return $this->db->query("
            SELECT * FROM budget_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getForecastingModels() {
        return [
            'linear_regression' => 'Linear Regression',
            'moving_average' => 'Moving Average',
            'exponential_smoothing' => 'Exponential Smoothing',
            'arima' => 'ARIMA',
            'seasonal_decomposition' => 'Seasonal Decomposition',
            'trend_analysis' => 'Trend Analysis'
        ];
    }

    private function getIncomeStatement() {
        return $this->db->query("
            SELECT
                is_item,
                amount,
                percentage_of_revenue,
                comparison_period_amount,
                ROUND(((amount - comparison_period_amount) / NULLIF(comparison_period_amount, 0)) * 100, 2) as growth_percentage
            FROM income_statement
            WHERE company_id = ? AND period = 'current_year'
            ORDER BY sort_order ASC
        ", [$this->user['company_id']]);
    }

    private function getBalanceSheet() {
        return $this->db->query("
            SELECT
                bs_item,
                amount,
                comparison_period_amount,
                ROUND(((amount - comparison_period_amount) / NULLIF(comparison_period_amount, 0)) * 100, 2) as change_percentage
            FROM balance_sheet
            WHERE company_id = ? AND as_of_date = CURDATE()
            ORDER BY sort_order ASC
        ", [$this->user['company_id']]);
    }

    private function getCashFlowStatement() {
        return $this->db->query("
            SELECT
                cf_item,
                amount,
                comparison_period_amount,
                ROUND(((amount - comparison_period_amount) / NULLIF(comparison_period_amount, 0)) * 100, 2) as change_percentage
            FROM cash_flow_statement
            WHERE company_id = ? AND period = 'current_year'
            ORDER BY sort_order ASC
        ", [$this->user['company_id']]);
    }

    private function getDetailedFinancialRatios() {
        return $this->db->query("
            SELECT
                ratio_name,
                ratio_value,
                benchmark_value,
                industry_average,
                trend_direction,
                calculation_date
            FROM detailed_financial_ratios
            WHERE company_id = ?
            ORDER BY ratio_category, ratio_name
        ", [$this->user['company_id']]);
    }

    private function getTrendAnalysis() {
        return $this->db->query("
            SELECT
                metric_name,
                period,
                current_value,
                previous_value,
                ROUND(((current_value - previous_value) / NULLIF(previous_value, 0)) * 100, 2) as change_percentage,
                trend_direction,
                forecast_value
            FROM financial_trends
            WHERE company_id = ?
            ORDER BY metric_name, period DESC
        ", [$this->user['company_id']]);
    }

    private function getComparativeReports() {
        return $this->db->query("
            SELECT
                report_type,
                period1_value,
                period2_value,
                ROUND(((period1_value - period2_value) / NULLIF(period2_value, 0)) * 100, 2) as change_percentage,
                comparison_type,
                generated_date
            FROM comparative_reports
            WHERE company_id = ?
            ORDER BY report_type, generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomFinancialReports() {
        return $this->db->query("
            SELECT
                cfr.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                cfr.report_name,
                cfr.last_generated,
                cfr.generation_count
            FROM custom_financial_reports cfr
            LEFT JOIN users u ON cfr.created_by = u.id
            WHERE cfr.company_id = ?
            ORDER BY cfr.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getReportTemplates() {
        return $this->db->query("
            SELECT * FROM financial_report_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTaxRates() {
        return $this->db->query("
            SELECT
                tr.*,
                tr.tax_rate,
                tr.effective_date,
                tr.expiry_date,
                CASE
                    WHEN tr.expiry_date < CURDATE() THEN 'expired'
                    WHEN tr.effective_date > CURDATE() THEN 'future'
                    ELSE 'active'
                END as status
            FROM tax_rates tr
            WHERE tr.company_id = ?
            ORDER BY tr.tax_type, tr.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getTaxCodes() {
        return $this->db->query("
            SELECT
                tc.*,
                tr.tax_rate,
                tc.tax_code,
                tc.description,
                COUNT(tt.id) as transaction_count
            FROM tax_codes tc
            LEFT JOIN tax_rates tr ON tc.tax_rate_id = tr.id
            LEFT JOIN tax_transactions tt ON tc.id = tt.tax_code_id
            WHERE tc.company_id = ?
            GROUP BY tc.id, tr.tax_rate
            ORDER BY tc.tax_code ASC
        ", [$this->user['company_id']]);
    }

    private function getTaxReturns() {
        return $this->db->query("
            SELECT
                tr.*,
                tr.tax_period,
                tr.tax_amount,
                tr.filing_status,
                tr.due_date,
                TIMESTAMPDIFF(DAY, CURDATE(), tr.due_date) as days_until_due,
                tr.filed_date
            FROM tax_returns tr
            WHERE tr.company_id = ?
            ORDER BY tr.due_date DESC
        ", [$this->user['company_id']]);
    }

    private function getTaxCalculations() {
        return $this->db->query("
            SELECT
                tc.*,
                tc.transaction_amount,
                tc.tax_amount,
                tc.tax_rate,
                tc.tax_type,
                tc.calculation_date
            FROM tax_calculations tc
            WHERE tc.company_id = ?
            ORDER BY tc.calculation_date DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getTaxCompliance() {
        return $this->db->querySingle("
            SELECT
                COUNT(tr.id) as total_returns,
                COUNT(CASE WHEN tr.filing_status = 'filed_on_time' THEN 1 END) as on_time_filings,
                COUNT(CASE WHEN tr.filing_status = 'filed_late' THEN 1 END) as late_filings,
                COUNT(CASE WHEN tr.filing_status = 'pending' AND tr.due_date < CURDATE() THEN 1 END) as overdue_returns,
                ROUND((COUNT(CASE WHEN tr.filing_status = 'filed_on_time' THEN 1 END) / NULLIF(COUNT(tr.id), 0)) * 100, 2) as compliance_rate
            FROM tax_returns tr
            WHERE tr.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTaxAnalytics() {
        return $this->db->query("
            SELECT
                tax_type,
                SUM(tax_amount) as total_tax_amount,
                AVG(tax_rate) as avg_tax_rate,
                COUNT(*) as transaction_count,
                MAX(calculation_date) as last_calculation
            FROM tax_calculations
            WHERE company_id = ?
            GROUP BY tax_type
            ORDER BY total_tax_amount DESC
        ", [$this->user['company_id']]);
    }

    private function getTaxSettings() {
        return $this->db->querySingle("
            SELECT * FROM tax_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getExchangeRates() {
        return $this->db->query("
            SELECT
                er.*,
                er.from_currency,
                er.to_currency,
                er.exchange_rate,
                er.last_updated,
                TIMESTAMPDIFF(HOUR, er.last_updated, NOW()) as hours_since_update
            FROM exchange_rates er
            WHERE er.company_id = ?
            ORDER BY er.from_currency, er.to_currency
        ", [$this->user['company_id']]);
    }

    private function getCurrencyAccounts() {
        return $this->db->query("
            SELECT
                ca.*,
                ca.currency_code,
                ca.account_balance,
                ca.available_balance,
                ca.last_transaction_date
            FROM currency_accounts ca
            WHERE ca.company_id = ?
            ORDER BY ca.currency_code, ca.account_balance DESC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyTransactions() {
        return $this->db->query("
            SELECT
                ct.*,
                ct.transaction_amount,
                ct.from_currency,
                ct.to_currency,
                ct.exchange_rate_used,
                ct.transaction_date
            FROM currency_transactions ct
            WHERE ct.company_id = ?
            ORDER BY ct.transaction_date DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getHedgingPositions() {
        return $this->db->query("
            SELECT
                hp.*,
                hp.hedge_type,
                hp.notional_amount,
                hp.strike_price,
                hp.expiry_date,
                hp.current_value,
                TIMESTAMPDIFF(DAY, CURDATE(), hp.expiry_date) as days_to_expiry
            FROM hedging_positions hp
            WHERE hp.company_id = ?
            ORDER BY hp.expiry_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyRisk() {
        return $this->db->querySingle("
            SELECT
                SUM(CASE WHEN currency_code != 'USD' THEN account_balance END) as foreign_currency_exposure,
                COUNT(DISTINCT currency_code) as currencies_held,
                AVG(exchange_rate_volatility) as avg_volatility,
                MAX(exchange_rate_volatility) as max_volatility,
                SUM(hedged_amount) as total_hedged_amount
            FROM currency_accounts ca
            LEFT JOIN hedging_positions hp ON ca.currency_code = hp.currency_code AND hp.company_id = ca.company_id
            WHERE ca.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getConversionRules() {
        return $this->db->query("
            SELECT * FROM currency_conversion_rules
            WHERE company_id = ? AND is_active = true
            ORDER BY from_currency, to_currency
        ", [$this->user['company_id']]);
    }

    private function getCurrencyAnalytics() {
        return $this->db->query("
            SELECT
                currency_code,
                COUNT(*) as transaction_count,
                SUM(transaction_amount) as total_volume,
                AVG(exchange_rate_used) as avg_exchange_rate,
                MAX(transaction_date) as last_transaction
            FROM currency_transactions
            WHERE company_id = ?
            GROUP BY currency_code
            ORDER BY total_volume DESC
        ", [$this->user['company_id']]);
    }

    private function getFixedAssets() {
        return $this->db->query("
            SELECT
                fa.*,
                u.first_name as acquired_by_first,
                u.last_name as acquired_by_last,
                ac.category_name,
                fa.asset_value,
                fa.accumulated_depreciation,
                fa.net_book_value,
                fa.depreciation_rate,
                TIMESTAMPDIFF(YEAR, fa.acquisition_date, CURDATE()) as age_years
            FROM fixed_assets fa
            LEFT JOIN users u ON fa.acquired_by = u.id
            LEFT JOIN asset_categories ac ON fa.category_id = ac.id
            WHERE fa.company_id = ?
            ORDER BY fa.net_book_value DESC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationSchedule() {
        return $this->db->query("
            SELECT
                fa.asset_name
