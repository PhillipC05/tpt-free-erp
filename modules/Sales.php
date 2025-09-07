<?php
/**
 * TPT Free ERP - Sales & CRM Module
 * Complete customer relationship management, sales pipeline, and order processing
 */

class Sales extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main sales dashboard
     */
    public function index() {
        $this->requirePermission('sales.view');

        $data = [
            'title' => 'Sales & CRM',
            'sales_overview' => $this->getSalesOverview(),
            'pipeline_status' => $this->getPipelineStatus(),
            'recent_activities' => $this->getRecentActivities(),
            'top_performers' => $this->getTopPerformers(),
            'sales_targets' => $this->getSalesTargets(),
            'customer_insights' => $this->getCustomerInsights(),
            'forecast_accuracy' => $this->getForecastAccuracy(),
            'conversion_rates' => $this->getConversionRates()
        ];

        $this->render('modules/sales/dashboard', $data);
    }

    /**
     * Customer management
     */
    public function customers() {
        $this->requirePermission('sales.customers.view');

        $filters = [
            'segment' => $_GET['segment'] ?? null,
            'status' => $_GET['status'] ?? null,
            'assigned_to' => $_GET['assigned_to'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $customers = $this->getCustomers($filters);

        $data = [
            'title' => 'Customer Management',
            'customers' => $customers,
            'filters' => $filters,
            'segments' => $this->getCustomerSegments(),
            'customer_stats' => $this->getCustomerStats($filters),
            'customer_templates' => $this->getCustomerTemplates(),
            'bulk_actions' => $this->getBulkActions(),
            'customer_insights' => $this->getCustomerInsights()
        ];

        $this->render('modules/sales/customers', $data);
    }

    /**
     * Lead management
     */
    public function leads() {
        $this->requirePermission('sales.leads.view');

        $filters = [
            'source' => $_GET['source'] ?? null,
            'status' => $_GET['status'] ?? null,
            'assigned_to' => $_GET['assigned_to'] ?? null,
            'score_min' => $_GET['score_min'] ?? null,
            'score_max' => $_GET['score_max'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $leads = $this->getLeads($filters);

        $data = [
            'title' => 'Lead Management',
            'leads' => $leads,
            'filters' => $filters,
            'lead_sources' => $this->getLeadSources(),
            'lead_statuses' => $this->getLeadStatuses(),
            'lead_templates' => $this->getLeadTemplates(),
            'lead_scoring' => $this->getLeadScoring(),
            'lead_stats' => $this->getLeadStats($filters),
            'conversion_funnel' => $this->getConversionFunnel()
        ];

        $this->render('modules/sales/leads', $data);
    }

    /**
     * Opportunities management
     */
    public function opportunities() {
        $this->requirePermission('sales.opportunities.view');

        $filters = [
            'stage' => $_GET['stage'] ?? null,
            'assigned_to' => $_GET['assigned_to'] ?? null,
            'probability_min' => $_GET['probability_min'] ?? null,
            'probability_max' => $_GET['probability_max'] ?? null,
            'amount_min' => $_GET['amount_min'] ?? null,
            'amount_max' => $_GET['amount_max'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $opportunities = $this->getOpportunities($filters);

        $data = [
            'title' => 'Sales Opportunities',
            'opportunities' => $opportunities,
            'filters' => $filters,
            'pipeline_stages' => $this->getPipelineStages(),
            'opportunity_templates' => $this->getOpportunityTemplates(),
            'forecasting' => $this->getSalesForecasting(),
            'opportunity_stats' => $this->getOpportunityStats($filters),
            'win_loss_analysis' => $this->getWinLossAnalysis(),
            'sales_velocity' => $this->getSalesVelocity()
        ];

        $this->render('modules/sales/opportunities', $data);
    }

    /**
     * Sales pipeline
     */
    public function pipeline() {
        $this->requirePermission('sales.pipeline.view');

        $data = [
            'title' => 'Sales Pipeline',
            'pipeline_data' => $this->getPipelineData(),
            'pipeline_stages' => $this->getPipelineStages(),
            'pipeline_metrics' => $this->getPipelineMetrics(),
            'forecasting' => $this->getPipelineForecasting(),
            'conversion_rates' => $this->getStageConversionRates(),
            'bottlenecks' => $this->getPipelineBottlenecks(),
            'pipeline_trends' => $this->getPipelineTrends(),
            'sales_targets' => $this->getSalesTargets()
        ];

        $this->render('modules/sales/pipeline', $data);
    }

    /**
     * Order processing
     */
    public function orders() {
        $this->requirePermission('sales.orders.view');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'customer' => $_GET['customer'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'amount_min' => $_GET['amount_min'] ?? null,
            'amount_max' => $_GET['amount_max'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $orders = $this->getOrders($filters);

        $data = [
            'title' => 'Order Processing',
            'orders' => $orders,
            'filters' => $filters,
            'order_statuses' => $this->getOrderStatuses(),
            'order_templates' => $this->getOrderTemplates(),
            'shipping_methods' => $this->getShippingMethods(),
            'payment_terms' => $this->getPaymentTerms(),
            'order_stats' => $this->getOrderStats($filters),
            'fulfillment_status' => $this->getFulfillmentStatus(),
            'order_analytics' => $this->getOrderAnalytics()
        ];

        $this->render('modules/sales/orders', $data);
    }

    /**
     * Customer communication
     */
    public function communication() {
        $this->requirePermission('sales.communication.view');

        $data = [
            'title' => 'Customer Communication',
            'email_campaigns' => $this->getEmailCampaigns(),
            'communication_history' => $this->getCommunicationHistory(),
            'communication_templates' => $this->getCommunicationTemplates(),
            'follow_up_reminders' => $this->getFollowUpReminders(),
            'communication_analytics' => $this->getCommunicationAnalytics(),
            'customer_feedback' => $this->getCustomerFeedback(),
            'communication_automation' => $this->getCommunicationAutomation(),
            'communication_settings' => $this->getCommunicationSettings()
        ];

        $this->render('modules/sales/communication', $data);
    }

    /**
     * Sales analytics and reporting
     */
    public function analytics() {
        $this->requirePermission('sales.analytics.view');

        $data = [
            'title' => 'Sales Analytics',
            'sales_performance' => $this->getSalesPerformance(),
            'customer_analytics' => $this->getCustomerAnalytics(),
            'product_analytics' => $this->getProductAnalytics(),
            'geographic_analytics' => $this->getGeographicAnalytics(),
            'seasonal_trends' => $this->getSeasonalTrends(),
            'competitor_analysis' => $this->getCompetitorAnalysis(),
            'roi_analysis' => $this->getROIanalysis(),
            'predictive_analytics' => $this->getSalesPredictiveAnalytics()
        ];

        $this->render('modules/sales/analytics', $data);
    }

    /**
     * Sales forecasting
     */
    public function forecasting() {
        $this->requirePermission('sales.forecasting.view');

        $data = [
            'title' => 'Sales Forecasting',
            'forecast_models' => $this->getForecastModels(),
            'forecast_scenarios' => $this->getForecastScenarios(),
            'forecast_accuracy' => $this->getForecastAccuracy(),
            'forecast_assumptions' => $this->getForecastAssumptions(),
            'forecast_trends' => $this->getForecastTrends(),
            'forecast_validation' => $this->getForecastValidation(),
            'forecast_reports' => $this->getForecastReports(),
            'forecast_settings' => $this->getForecastSettings()
        ];

        $this->render('modules/sales/forecasting', $data);
    }

    /**
     * Customer segmentation
     */
    public function segmentation() {
        $this->requirePermission('sales.segmentation.view');

        $data = [
            'title' => 'Customer Segmentation',
            'customer_segments' => $this->getCustomerSegments(),
            'segment_criteria' => $this->getSegmentCriteria(),
            'segment_performance' => $this->getSegmentPerformance(),
            'segment_templates' => $this->getSegmentTemplates(),
            'segment_automation' => $this->getSegmentAutomation(),
            'segment_analytics' => $this->getSegmentAnalytics(),
            'segment_insights' => $this->getSegmentInsights(),
            'segment_settings' => $this->getSegmentSettings()
        ];

        $this->render('modules/sales/segmentation', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getSalesOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT c.id) as total_customers,
                COUNT(DISTINCT l.id) as total_leads,
                COUNT(DISTINCT o.id) as total_opportunities,
                COUNT(DISTINCT so.id) as total_orders,
                SUM(so.total_amount) as total_sales_value,
                AVG(so.total_amount) as avg_order_value,
                COUNT(CASE WHEN l.status = 'qualified' THEN 1 END) as qualified_leads,
                COUNT(CASE WHEN o.stage = 'closed_won' THEN 1 END) as won_opportunities,
                ROUND((COUNT(CASE WHEN o.stage = 'closed_won' THEN 1 END) / NULLIF(COUNT(o.id), 0)) * 100, 2) as win_rate
            FROM customers c
            LEFT JOIN leads l ON l.company_id = c.company_id
            LEFT JOIN opportunities o ON o.company_id = c.company_id
            LEFT JOIN sales_orders so ON so.company_id = c.company_id
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPipelineStatus() {
        return $this->db->query("
            SELECT
                ps.stage_name,
                ps.stage_order,
                COUNT(o.id) as opportunity_count,
                SUM(o.expected_value) as total_value,
                AVG(o.probability) as avg_probability,
                SUM(o.expected_value * o.probability / 100) as weighted_value,
                AVG(TIMESTAMPDIFF(DAY, o.created_at, NOW())) as avg_days_in_stage
            FROM pipeline_stages ps
            LEFT JOIN opportunities o ON ps.id = o.stage_id AND o.status = 'active'
            WHERE ps.company_id = ?
            GROUP BY ps.id, ps.stage_name, ps.stage_order
            ORDER BY ps.stage_order ASC
        ", [$this->user['company_id']]);
    }

    private function getRecentActivities() {
        return $this->db->query("
            SELECT
                sa.*,
                u.first_name as user_first,
                u.last_name as user_last,
                sa.activity_type,
                sa.description,
                sa.related_record_type,
                sa.related_record_id,
                TIMESTAMPDIFF(MINUTE, sa.created_at, NOW()) as minutes_ago
            FROM sales_activities sa
            LEFT JOIN users u ON sa.user_id = u.id
            WHERE sa.company_id = ?
            ORDER BY sa.created_at DESC
            LIMIT 25
        ", [$this->user['company_id']]);
    }

    private function getTopPerformers() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                u.id as user_id,
                COUNT(DISTINCT o.id) as opportunities_won,
                SUM(so.total_amount) as total_sales,
                AVG(so.total_amount) as avg_sale_size,
                COUNT(DISTINCT c.id) as new_customers,
                ROUND((COUNT(DISTINCT CASE WHEN o.stage = 'closed_won' THEN o.id END) / NULLIF(COUNT(DISTINCT o.id), 0)) * 100, 2) as win_rate
            FROM users u
            LEFT JOIN opportunities o ON u.id = o.assigned_to
            LEFT JOIN sales_orders so ON u.id = so.sales_rep_id
            LEFT JOIN customers c ON u.id = c.created_by
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY total_sales DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getSalesTargets() {
        return $this->db->query("
            SELECT
                st.*,
                u.first_name as assigned_to_first,
                u.last_name as assigned_to_last,
                st.target_amount,
                st.current_amount,
                ROUND((st.current_amount / NULLIF(st.target_amount, 0)) * 100, 2) as achievement_percentage,
                st.target_period,
                TIMESTAMPDIFF(DAY, CURDATE(), st.end_date) as days_remaining
            FROM sales_targets st
            LEFT JOIN users u ON st.assigned_to = u.id
            WHERE st.company_id = ? AND st.status = 'active'
            ORDER BY achievement_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerInsights() {
        return $this->db->query("
            SELECT
                c.customer_name,
                c.customer_segment,
                COUNT(so.id) as order_count,
                SUM(so.total_amount) as total_spent,
                MAX(so.order_date) as last_order_date,
                AVG(so.total_amount) as avg_order_value,
                TIMESTAMPDIFF(DAY, MAX(so.order_date), CURDATE()) as days_since_last_order,
                c.lifetime_value,
                c.customer_satisfaction_score
            FROM customers c
            LEFT JOIN sales_orders so ON c.id = so.customer_id
            WHERE c.company_id = ?
            GROUP BY c.id, c.customer_name, c.customer_segment, c.lifetime_value, c.customer_satisfaction_score
            ORDER BY total_spent DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getForecastAccuracy() {
        return $this->db->querySingle("
            SELECT
                AVG(forecast_accuracy) as avg_forecast_accuracy,
                COUNT(CASE WHEN forecast_accuracy >= 90 THEN 1 END) as high_accuracy_forecasts,
                COUNT(CASE WHEN forecast_accuracy < 70 THEN 1 END) as low_accuracy_forecasts,
                MAX(forecast_accuracy) as best_forecast_accuracy,
                MIN(forecast_accuracy) as worst_forecast_accuracy,
                AVG(ABS(actual_value - forecast_value) / NULLIF(actual_value, 0)) as avg_forecast_error
            FROM sales_forecasts sf
            WHERE sf.company_id = ? AND sf.actual_value IS NOT NULL
        ", [$this->user['company_id']]);
    }

    private function getConversionRates() {
        return $this->db->query("
            SELECT
                'lead_to_opportunity' as conversion_type,
                COUNT(CASE WHEN l.status = 'qualified' THEN 1 END) as converted_count,
                COUNT(l.id) as total_count,
                ROUND((COUNT(CASE WHEN l.status = 'qualified' THEN 1 END) / NULLIF(COUNT(l.id), 0)) * 100, 2) as conversion_rate
            FROM leads l
            WHERE l.company_id = ?

            UNION ALL

            SELECT
                'opportunity_to_sale' as conversion_type,
                COUNT(CASE WHEN o.stage = 'closed_won' THEN 1 END) as converted_count,
                COUNT(o.id) as total_count,
                ROUND((COUNT(CASE WHEN o.stage = 'closed_won' THEN 1 END) / NULLIF(COUNT(o.id), 0)) * 100, 2) as conversion_rate
            FROM opportunities o
            WHERE o.company_id = ?

            UNION ALL

            SELECT
                'quote_to_order' as conversion_type,
                COUNT(CASE WHEN q.status = 'accepted' THEN 1 END) as converted_count,
                COUNT(q.id) as total_count,
                ROUND((COUNT(CASE WHEN q.status = 'accepted' THEN 1 END) / NULLIF(COUNT(q.id), 0)) * 100, 2) as conversion_rate
            FROM quotes q
            WHERE q.company_id = ?
        ", [$this->user['company_id'], $this->user['company_id'], $this->user['company_id']]);
    }

    private function getCustomers($filters) {
        $where = ["c.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['segment']) {
            $where[] = "c.customer_segment = ?";
            $params[] = $filters['segment'];
        }

        if ($filters['status']) {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['assigned_to']) {
            $where[] = "c.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        if ($filters['date_from']) {
            $where[] = "c.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "c.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(c.customer_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                c.*,
                u1.first_name as assigned_to_first,
                u1.last_name as assigned_to_last,
                u2.first_name as created_by_first,
                u2.last_name as created_by_last,
                COUNT(so.id) as order_count,
                SUM(so.total_amount) as total_spent,
                MAX(so.order_date) as last_order_date,
                AVG(so.total_amount) as avg_order_value,
                c.lifetime_value,
                c.customer_satisfaction_score
            FROM customers c
            LEFT JOIN users u1 ON c.assigned_to = u1.id
            LEFT JOIN users u2 ON c.created_by = u2.id
            LEFT JOIN sales_orders so ON c.id = so.customer_id
            WHERE $whereClause
            GROUP BY c.id, u1.first_name, u1.last_name, u2.first_name, u2.last_name
            ORDER BY c.created_at DESC
        ", $params);
    }

    private function getCustomerSegments() {
        return $this->db->query("
            SELECT
                cs.*,
                COUNT(c.id) as customer_count,
                SUM(so.total_amount) as segment_revenue,
                AVG(c.customer_satisfaction_score) as avg_satisfaction,
                MAX(so.order_date) as last_order_date
            FROM customer_segments cs
            LEFT JOIN customers c ON cs.id = c.segment_id
            LEFT JOIN sales_orders so ON c.id = so.customer_id
            WHERE cs.company_id = ?
            GROUP BY cs.id
            ORDER BY segment_revenue DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerStats($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['segment']) {
            $where[] = "segment_id = ?";
            $params[] = $filters['segment'];
        }

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_customers,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
                COUNT(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_customers,
                AVG(lifetime_value) as avg_lifetime_value,
                AVG(customer_satisfaction_score) as avg_satisfaction,
                SUM(lifetime_value) as total_lifetime_value
            FROM customers
            WHERE $whereClause
        ", $params);
    }

    private function getCustomerTemplates() {
        return $this->db->query("
            SELECT * FROM customer_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBulkActions() {
        return [
            'update_segment' => 'Update Customer Segment',
            'assign_sales_rep' => 'Assign Sales Representative',
            'send_email_campaign' => 'Send Email Campaign',
            'update_status' => 'Update Customer Status',
            'export_customers' => 'Export Customer Data',
            'import_customers' => 'Import Customer Data',
            'merge_duplicates' => 'Merge Duplicate Records',
            'bulk_communication' => 'Send Bulk Communication'
        ];
    }

    private function getLeads($filters) {
        $where = ["l.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['source']) {
            $where[] = "l.lead_source = ?";
            $params[] = $filters['source'];
        }

        if ($filters['status']) {
            $where[] = "l.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['assigned_to']) {
            $where[] = "l.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        if ($filters['score_min']) {
            $where[] = "l.lead_score >= ?";
            $params[] = $filters['score_min'];
        }

        if ($filters['score_max']) {
            $where[] = "l.lead_score <= ?";
            $params[] = $filters['score_max'];
        }

        if ($filters['search']) {
            $where[] = "(l.first_name LIKE ? OR l.last_name LIKE ? OR l.email LIKE ? OR l.company LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                l.*,
                u.first_name as assigned_to_first,
                u.last_name as assigned_to_last,
                l.lead_score,
                l.lead_source,
                l.status,
                TIMESTAMPDIFF(DAY, l.created_at, NOW()) as days_old,
                COUNT(lf.id) as follow_up_count,
                MAX(lf.follow_up_date) as last_follow_up
            FROM leads l
            LEFT JOIN users u ON l.assigned_to = u.id
            LEFT JOIN lead_follow_ups lf ON l.id = lf.lead_id
            WHERE $whereClause
            GROUP BY l.id, u.first_name, u.last_name
            ORDER BY l.lead_score DESC, l.created_at DESC
        ", $params);
    }

    private function getLeadSources() {
        return [
            'website' => 'Website',
            'social_media' => 'Social Media',
            'referral' => 'Referral',
            'trade_show' => 'Trade Show',
            'cold_call' => 'Cold Call',
            'email_campaign' => 'Email Campaign',
            'paid_advertising' => 'Paid Advertising',
            'content_marketing' => 'Content Marketing',
            'partner' => 'Partner',
            'other' => 'Other'
        ];
    }

    private function getLeadStatuses() {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'proposal' => 'Proposal Sent',
            'negotiation' => 'Negotiation',
            'closed_won' => 'Closed Won',
            'closed_lost' => 'Closed Lost',
            'nurturing' => 'Nurturing',
            'disqualified' => 'Disqualified'
        ];
    }

    private function getLeadTemplates() {
        return $this->db->query("
            SELECT * FROM lead_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getLeadScoring() {
        return $this->db->query("
            SELECT
                ls.*,
                ls.scoring_criteria,
                ls.score_value,
                ls.max_score,
                COUNT(l.id) as leads_scored
            FROM lead_scoring ls
            LEFT JOIN leads l ON ls.id = l.scoring_model_id
            WHERE ls.company_id = ?
            GROUP BY ls.id
            ORDER BY ls.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getLeadStats($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['source']) {
            $where[] = "lead_source = ?";
            $params[] = $filters['source'];
        }

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_leads,
                COUNT(CASE WHEN status = 'qualified' THEN 1 END) as qualified_leads,
                COUNT(CASE WHEN status = 'closed_won' THEN 1 END) as converted_leads,
                AVG(lead_score) as avg_lead_score,
                COUNT(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_leads_this_month,
                ROUND((COUNT(CASE WHEN status = 'qualified' THEN 1 END) / NULLIF(COUNT(*), 0)) * 100, 2) as qualification_rate
            FROM leads
            WHERE $whereClause
        ", $params);
    }

    private function getConversionFunnel() {
        return $this->db->query("
            SELECT
                'leads' as stage,
                COUNT(*) as count,
                100.0 as conversion_rate
            FROM leads
            WHERE company_id = ?

            UNION ALL

            SELECT
                'qualified_leads' as stage,
                COUNT(*) as count,
                ROUND((COUNT(*) / NULLIF((SELECT COUNT(*) FROM leads WHERE company_id = ?), 0)) * 100, 2) as conversion_rate
            FROM leads
            WHERE company_id = ? AND status = 'qualified'

            UNION ALL

            SELECT
                'opportunities' as stage,
                COUNT(*) as count,
                ROUND((COUNT(*) / NULLIF((SELECT COUNT(*) FROM leads WHERE company_id = ? AND status = 'qualified'), 0)) * 100, 2) as conversion_rate
            FROM opportunities
            WHERE company_id = ?

            UNION ALL

            SELECT
                'closed_won' as stage,
                COUNT(*) as count,
                ROUND((COUNT(*) / NULLIF((SELECT COUNT(*) FROM opportunities WHERE company_id = ?), 0)) * 100, 2) as conversion_rate
            FROM opportunities
            WHERE company_id = ? AND stage = 'closed_won'
        ", [
            $this->user['company_id'],
            $this->user['company_id'],
            $this->user['company_id'],
            $this->user['company_id'],
            $this->user['company_id'],
            $this->user['company_id'],
            $this->user['company_id']
        ]);
    }

    private function getOpportunities($filters) {
        $where = ["o.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['stage']) {
            $where[] = "o.stage_id = ?";
            $params[] = $filters['stage'];
        }

        if ($filters['assigned_to']) {
            $where[] = "o.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        if ($filters['probability_min']) {
            $where[] = "o.probability >= ?";
            $params[] = $filters['probability_min'];
        }

        if ($filters['probability_max']) {
            $where[] = "o.probability <= ?";
            $params[] = $filters['probability_max'];
        }

        if ($filters['amount_min']) {
            $where[] = "o.expected_value >= ?";
            $params[] = $filters['amount_min'];
        }

        if ($filters['amount_max']) {
            $where[] = "o.expected_value <= ?";
            $params[] = $filters['amount_max'];
        }

        if ($filters['search']) {
            $where[] = "(o.opportunity_name LIKE ? OR c.customer_name LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                o.*,
                c.customer_name,
                u.first_name as assigned_to_first,
                u.last_name as assigned_to_last,
                ps.stage_name,
                o.expected_value,
                o.probability,
                o.expected_close_date,
                TIMESTAMPDIFF(DAY, CURDATE(), o.expected_close_date) as days_to_close,
                o.expected_value * o.probability / 100 as weighted_value
            FROM opportunities o
            JOIN customers c ON o.customer_id = c.id
            LEFT JOIN users u ON o.assigned_to = u.id
            LEFT JOIN pipeline_stages ps ON o.stage_id = ps.id
            WHERE $whereClause
            ORDER BY o.expected_value * o.probability / 100 DESC, o.expected_close_date ASC
        ", $params);
    }

    private function getPipelineStages() {
        return $this->db->query("
            SELECT
                ps.*,
                COUNT(o.id) as opportunity_count,
                SUM(o.expected_value) as total_value,
                AVG(o.probability) as avg_probability,
                SUM(o.expected_value * o.probability / 100) as weighted_value
            FROM pipeline_stages ps
            LEFT JOIN opportunities o ON ps.id = o.stage_id AND o.status = 'active'
            WHERE ps.company_id = ?
            GROUP BY ps.id
            ORDER BY ps.stage_order ASC
        ", [$this->user['company_id']]);
    }

    private function getOpportunityTemplates() {
        return $this->db->query("
            SELECT * FROM opportunity_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getSalesForecasting() {
        return $this->db->query("
            SELECT
                sf.*,
                sf.forecast_period,
                sf.forecast_amount,
                sf.actual_amount,
                sf.confidence_level,
                ROUND(((sf.actual_amount - sf.forecast_amount) / NULLIF(sf.forecast_amount, 0)) * 100, 2) as accuracy_percentage
            FROM sales_forecasts sf
            WHERE sf.company_id = ?
            ORDER BY sf.forecast_period ASC
        ", [$this->user['company_id']]);
    }

    private function getOpportunityStats($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['stage']) {
            $where[] = "stage_id = ?";
            $params[] = $filters['stage'];
        }

        if ($filters['assigned_to']) {
            $where[] = "assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_opportunities,
                COUNT(CASE WHEN stage_id IN (SELECT id FROM pipeline_stages WHERE stage_order >= 3) THEN 1 END) as advanced_opportunities,
                COUNT(CASE WHEN stage = 'closed_won' THEN 1 END) as won_opportunities,
                COUNT(CASE WHEN stage = 'closed_lost' THEN 1 END) as lost_opportunities,
                SUM(expected_value) as total_expected_value,
                SUM(expected_value * probability / 100) as total_weighted_value,
                AVG(probability) as avg_probability,
                ROUND((COUNT(CASE WHEN stage = 'closed_won' THEN 1 END) / NULLIF(COUNT(*), 0)) * 100, 2) as win_rate
            FROM opportunities
            WHERE $whereClause
        ", $params);
    }

    private function getWinLossAnalysis() {
        return $this->db->query("
            SELECT
                CASE
                    WHEN stage = 'closed_won' THEN 'won'
                    WHEN stage = 'closed_lost' THEN 'lost'
                    ELSE 'open'
                END as outcome,
                COUNT(*) as count,
                AVG(expected_value) as avg_deal_size,
                AVG(TIMESTAMPDIFF(DAY, created_at, COALESCE(close_date, CURDATE()))) as avg_sales_cycle,
                SUM(expected_value) as total_value
            FROM opportunities
            WHERE company_id = ? AND stage IN ('closed_won', 'closed_lost')
            GROUP BY
                CASE
                    WHEN stage = 'closed_won' THEN 'won'
                    WHEN stage = 'closed_lost' THEN 'lost'
                    ELSE 'open'
                END
        ", [$this->user['company_id']]);
    }

    private function getSalesVelocity() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as opportunities_created,
                COUNT(CASE WHEN stage = 'closed_won' THEN 1 END) as opportunities_won,
                AVG(TIMESTAMPDIFF(DAY, created_at, COALESCE(close_date, CURDATE()))) as avg_cycle_time,
                SUM(expected_value) as total_value_created,
                SUM(CASE WHEN stage = 'closed_won' THEN expected_value END) as total_value_won
            FROM opportunities
            WHERE company_id = ?
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ", [$this->user['company_id']]);
    }

    private function getPipelineData() {
        return $this->db->query("
            SELECT
                ps.stage_name,
                ps.stage_order,
                COUNT(o.id) as opportunity_count,
                SUM(o.expected_value) as total_value,
                AVG(o.probability) as avg_probability,
                SUM(o.expected_value * o.probability / 100) as weighted_value,
                GROUP_CONCAT(DISTINCT c.customer_name SEPARATOR ', ') as customers
            FROM pipeline_stages ps
            LEFT JOIN opportunities o ON ps.id = o.stage_id AND o.status = 'active'
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE ps.company_id = ?
            GROUP BY ps.id, ps.stage_name, ps.stage_order
            ORDER BY ps.stage_order ASC
        ", [$this->user['company_id']]);
    }

    private function getPipelineMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(o.id) as total_opportunities,
                SUM(o.expected_value) as total_pipeline_value,
                SUM(o.expected_value * o.probability / 100) as total_weighted_value,
                AVG(o.probability) as avg_probability,
                AVG(TIMESTAMPDIFF(DAY, o.created_at, CURDATE())) as avg_age,
                COUNT(DISTINCT o.customer_id) as unique_customers,
                COUNT(DISTINCT o.assigned_to) as active_sales_reps
            FROM opportunities o
            WHERE o.company_id = ? AND o.status = 'active'
        ", [$this->user['company_id']]);
    }

    private function getPipelineForecasting() {
        return $this->db->query("
            SELECT
                pf.*,
                pf.forecast_period,
                pf.forecast_amount,
                pf.confidence_level,
                pf.assumptions,
                pf.last_updated
            FROM pipeline_forecasts pf
            WHERE pf.company_id = ?
            ORDER BY pf.forecast_period ASC
        ", [$this->user['company_id']]);
    }

    private function getStageConversionRates() {
        return $this->db->query("
            SELECT
                ps1.stage_name as from_stage,
                ps2.stage_name as to_stage,
                COUNT(CASE WHEN o.stage_id = ps2.id THEN 1 END) as converted_count,
                COUNT(CASE WHEN o.stage_id = ps1.id THEN 1 END) as total_count,
                ROUND((COUNT(CASE WHEN o.stage_id = ps2.id THEN 1 END) / NULLIF(COUNT(CASE WHEN o.stage_id = ps1.id THEN 1 END), 0)) * 100, 2) as conversion_rate
            FROM pipeline_stages ps1
            CROSS JOIN pipeline_stages ps2 ON ps2.stage_order = ps1.stage_order + 1
            LEFT JOIN opportunities o ON o.company_id = ps1.company_id
            WHERE ps1.company_id = ?
            GROUP BY ps1.id, ps1.stage_name, ps2.id, ps2.stage_name
            ORDER BY ps1.stage_order ASC
        ", [$this->user['company_id']]);
    }

    private function getPipelineBottlenecks() {
        return $this->db->query("
            SELECT
                ps.stage_name,
                COUNT(o.id) as stuck_opportunities,
                AVG(TIMESTAMPDIFF(DAY, o.stage_entered_date, CURDATE())) as avg_days_stuck,
                COUNT(CASE WHEN TIMESTAMPDIFF(DAY, o.stage_entered_date, CURDATE()) > 30 THEN 1 END) as critically_stuck
            FROM pipeline_stages ps
            LEFT JOIN opportunities o ON ps.id = o.stage_id AND o.status = 'active'
            WHERE ps.company_id = ?
            GROUP BY ps.id, ps.stage_name
            HAVING stuck_opportunities > 0
            ORDER BY avg_days_stuck DESC
        ", [$this->user['company_id']]);
    }

    private function getPipelineTrends() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as opportunities_created,
                SUM(expected_value) as value_created,
                AVG(probability) as avg_probability,
                COUNT(CASE WHEN stage = 'closed_won' THEN 1 END) as won_count,
                SUM(CASE WHEN stage = 'closed_won' THEN expected_value END) as won_value
            FROM opportunities
            WHERE company_id = ?
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ", [$this->user['company_id']]);
    }

    private function getOrders($filters) {
        $where = ["so.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "so.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['customer']) {
            $where[] = "so.customer_id = ?";
            $params[] = $filters['customer'];
        }

        if ($filters['date_from']) {
            $where[] = "so.order_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "so.order_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['amount_min']) {
            $where[] = "so.total_amount >= ?";
            $params[] = $filters['amount_min'];
        }

        if ($filters['amount_max']) {
            $where[] = "so.total_amount <= ?";
            $params[] = $filters['amount_max'];
        }

        if ($filters['search']) {
            $where[] = "(so.order_number LIKE ? OR c.customer_name LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                so.*,
                c.customer_name,
                u.first_name as sales_rep_first,
                u.last_name as sales_rep_last,
                so.total_amount,
                so.status,
                so.order_date,
                COUNT(soi.id) as item_count,
                SUM(soi.quantity * soi.unit_price) as subtotal,
                so.tax_amount,
                so.shipping_amount
            FROM sales_orders so
            JOIN customers c ON so.customer_id = c.id
            LEFT JOIN users u ON so.sales_rep_id = u.id
            LEFT JOIN sales_order_items soi ON so.id = soi.sales_order_id
            WHERE $whereClause
            GROUP BY so.id, c.customer_name, u.first_name, u.last_name
            ORDER BY so.order_date DESC
        ", $params);
    }

    private function getOrderStatuses() {
        return [
            'draft' => 'Draft',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded'
        ];
    }

    private function getOrderTemplates() {
        return $this->db->query("
            SELECT * FROM order_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getShippingMethods() {
        return $this->db->query("
            SELECT * FROM shipping_methods
            WHERE company_id = ? AND is_active = true
            ORDER BY sort_order ASC
        ", [$this->user['company_id']]);
    }

    private function getPaymentTerms() {
        return $this->db->query("
            SELECT * FROM sales_payment_terms
            WHERE company_id = ? AND is_active = true
            ORDER BY net_days ASC
        ", [$this->user['company_id']]);
    }

    private function getOrderStats($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['customer']) {
            $where[] = "customer_id = ?";
            $params[] = $filters['customer'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                COUNT(DISTINCT customer_id) as unique_customers,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as orders_this_month,
                ROUND((COUNT(CASE WHEN status = 'delivered' THEN 1 END) / NULLIF(COUNT(*), 0)) * 100, 2) as completion_rate
            FROM sales_orders
            WHERE $whereClause
        ", $params);
    }

    private function getFulfillmentStatus() {
        return $this->db->query("
            SELECT
                status,
                COUNT(*) as order_count,
                SUM(total_amount) as total_value,
                AVG(TIMESTAMPDIFF(DAY, order_date, CURDATE())) as avg_days_open
            FROM sales_orders
            WHERE company_id = ?
            GROUP BY status
            ORDER BY order_count DESC
        ", [$this->user['company_id']]);
    }

    private function getOrderAnalytics() {
        return $this->db
