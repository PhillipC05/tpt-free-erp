<?php
/**
 * TPT Free ERP - Sales Module
 * Complete customer relationship management, sales pipeline, order processing, and revenue management system
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
            'title' => 'Sales Management',
            'sales_overview' => $this->getSalesOverview(),
            'sales_pipeline' => $this->getSalesPipeline(),
            'revenue_analytics' => $this->getRevenueAnalytics(),
            'customer_insights' => $this->getCustomerInsights(),
            'sales_targets' => $this->getSalesTargets(),
            'recent_activities' => $this->getRecentActivities(),
            'upcoming_tasks' => $this->getUpcomingTasks(),
            'sales_forecast' => $this->getSalesForecast()
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
            'value_tier' => $_GET['value_tier'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $customers = $this->getCustomers($filters);

        $data = [
            'title' => 'Customer Management',
            'customers' => $customers,
            'filters' => $filters,
            'customer_segments' => $this->getCustomerSegments(),
            'customer_status' => $this->getCustomerStatus(),
            'customer_templates' => $this->getCustomerTemplates(),
            'bulk_actions' => $this->getBulkActions(),
            'customer_analytics' => $this->getCustomerAnalytics()
        ];

        $this->render('modules/sales/customers', $data);
    }

    /**
     * Leads and opportunities
     */
    public function leads() {
        $this->requirePermission('sales.leads.view');

        $data = [
            'title' => 'Leads & Opportunities',
            'leads' => $this->getLeads(),
            'opportunities' => $this->getOpportunities(),
            'lead_sources' => $this->getLeadSources(),
            'lead_status' => $this->getLeadStatus(),
            'opportunity_stages' => $this->getOpportunityStages(),
            'conversion_rates' => $this->getConversionRates(),
            'lead_scoring' => $this->getLeadScoring(),
            'lead_analytics' => $this->getLeadAnalytics()
        ];

        $this->render('modules/sales/leads', $data);
    }

    /**
     * Sales pipeline management
     */
    public function pipeline() {
        $this->requirePermission('sales.pipeline.view');

        $data = [
            'title' => 'Sales Pipeline',
            'pipeline_stages' => $this->getPipelineStages(),
            'pipeline_opportunities' => $this->getPipelineOpportunities(),
            'pipeline_forecast' => $this->getPipelineForecast(),
            'pipeline_velocity' => $this->getPipelineVelocity(),
            'pipeline_conversion' => $this->getPipelineConversion(),
            'pipeline_analytics' => $this->getPipelineAnalytics(),
            'pipeline_settings' => $this->getPipelineSettings()
        ];

        $this->render('modules/sales/pipeline', $data);
    }

    /**
     * Order processing
     */
    public function orders() {
        $this->requirePermission('sales.orders.view');

        $data = [
            'title' => 'Order Processing',
            'sales_orders' => $this->getSalesOrders(),
            'order_approvals' => $this->getOrderApprovals(),
            'order_fulfillment' => $this->getOrderFulfillment(),
            'order_invoicing' => $this->getOrderInvoicing(),
            'order_returns' => $this->getOrderReturns(),
            'order_analytics' => $this->getOrderAnalytics(),
            'order_templates' => $this->getOrderTemplates(),
            'order_settings' => $this->getOrderSettings()
        ];

        $this->render('modules/sales/orders', $data);
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
            'territory_analytics' => $this->getTerritoryAnalytics(),
            'sales_forecasting' => $this->getSalesForecasting(),
            'sales_trends' => $this->getSalesTrends(),
            'custom_dashboards' => $this->getCustomDashboards()
        ];

        $this->render('modules/sales/analytics', $data);
    }

    /**
     * Sales targets and quotas
     */
    public function targets() {
        $this->requirePermission('sales.targets.view');

        $data = [
            'title' => 'Sales Targets & Quotas',
            'sales_targets' => $this->getSalesTargets(),
            'target_achievement' => $this->getTargetAchievement(),
            'quota_management' => $this->getQuotaManagement(),
            'commission_structure' => $this->getCommissionStructure(),
            'incentive_programs' => $this->getIncentivePrograms(),
            'target_analytics' => $this->getTargetAnalytics(),
            'target_settings' => $this->getTargetSettings()
        ];

        $this->render('modules/sales/targets', $data);
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
            'follow_up_reminders' => $this->getFollowUpReminders(),
            'communication_templates' => $this->getCommunicationTemplates(),
            'communication_analytics' => $this->getCommunicationAnalytics(),
            'communication_settings' => $this->getCommunicationSettings()
        ];

        $this->render('modules/sales/communication', $data);
    }

    /**
     * Sales reporting
     */
    public function reporting() {
        $this->requirePermission('sales.reports.view');

        $data = [
            'title' => 'Sales Reporting',
            'sales_reports' => $this->getSalesReports(),
            'customer_reports' => $this->getCustomerReports(),
            'pipeline_reports' => $this->getPipelineReports(),
            'performance_reports' => $this->getPerformanceReports(),
            'forecast_reports' => $this->getForecastReports(),
            'custom_reports' => $this->getCustomReports(),
            'report_schedules' => $this->getReportSchedules()
        ];

        $this->render('modules/sales/reporting', $data);
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
                SUM(so.total_amount) as total_revenue,
                AVG(so.total_amount) as avg_order_value,
                COUNT(CASE WHEN so.status = 'completed' THEN 1 END) as completed_orders,
                ROUND((COUNT(CASE WHEN so.status = 'completed' THEN 1 END) / NULLIF(COUNT(so.id), 0)) * 100, 2) as order_completion_rate,
                SUM(CASE WHEN DATE_FORMAT(so.order_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN so.total_amount ELSE 0 END) as current_month_revenue,
                SUM(CASE WHEN DATE_FORMAT(so.order_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m') THEN so.total_amount ELSE 0 END) as last_month_revenue
            FROM customers c
            LEFT JOIN leads l ON l.company_id = c.company_id
            LEFT JOIN opportunities o ON o.company_id = c.company_id
            LEFT JOIN sales_orders so ON so.company_id = c.company_id
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSalesPipeline() {
        return $this->db->query("
            SELECT
                ps.stage_name,
                ps.stage_order,
                COUNT(o.id) as opportunities_count,
                SUM(o.expected_value) as total_value,
                AVG(o.probability_percentage) as avg_probability,
                SUM(o.expected_value * o.probability_percentage / 100) as weighted_value,
                AVG(TIMESTAMPDIFF(DAY, o.created_date, COALESCE(o.close_date, CURDATE()))) as avg_days_in_stage
            FROM pipeline_stages ps
            LEFT JOIN opportunities o ON ps.id = o.stage_id AND o.status = 'active'
            WHERE ps.company_id = ?
            GROUP BY ps.id, ps.stage_name, ps.stage_order
            ORDER BY ps.stage_order ASC
        ", [$this->user['company_id']]);
    }

    private function getRevenueAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(so.order_date, '%Y-%m') as month,
                COUNT(so.id) as orders_count,
                SUM(so.total_amount) as total_revenue,
                AVG(so.total_amount) as avg_order_value,
                COUNT(DISTINCT so.customer_id) as unique_customers,
                SUM(so.total_amount) / COUNT(DISTINCT so.customer_id) as revenue_per_customer,
                ROUND((SUM(so.total_amount) - LAG(SUM(so.total_amount)) OVER (ORDER BY DATE_FORMAT(so.order_date, '%Y-%m'))) / NULLIF(LAG(SUM(so.total_amount)) OVER (ORDER BY DATE_FORMAT(so.order_date, '%Y-%m')), 0) * 100, 2) as month_over_month_growth
            FROM sales_orders so
            WHERE so.company_id = ? AND so.order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(so.order_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getCustomerInsights() {
        return $this->db->query("
            SELECT
                c.customer_name,
                c.customer_segment,
                COUNT(so.id) as total_orders,
                SUM(so.total_amount) as total_revenue,
                AVG(so.total_amount) as avg_order_value,
                MAX(so.order_date) as last_order_date,
                TIMESTAMPDIFF(DAY, MAX(so.order_date), CURDATE()) as days_since_last_order,
                ROUND((SUM(so.total_amount) / NULLIF((SELECT SUM(total_amount) FROM sales_orders WHERE company_id = ?), 0)) * 100, 2) as revenue_percentage,
                CASE
                    WHEN TIMESTAMPDIFF(DAY, MAX(so.order_date), CURDATE()) <= 30 THEN 'active'
                    WHEN TIMESTAMPDIFF(DAY, MAX(so.order_date), CURDATE()) <= 90 THEN 'recent'
                    WHEN TIMESTAMPDIFF(DAY, MAX(so.order_date), CURDATE()) <= 180 THEN 'at_risk'
                    ELSE 'churned'
                END as customer_status
            FROM customers c
            LEFT JOIN sales_orders so ON c.id = so.customer_id
            WHERE c.company_id = ?
            GROUP BY c.id, c.customer_name, c.customer_segment
            ORDER BY total_revenue DESC
            LIMIT 20
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getSalesTargets() {
        return $this->db->query("
            SELECT
                st.target_name,
                st.target_type,
                st.target_value,
                st.target_period,
                st.achieved_value,
                ROUND((st.achieved_value / NULLIF(st.target_value, 0)) * 100, 2) as achievement_percentage,
                st.target_start_date,
                st.target_end_date,
                TIMESTAMPDIFF(DAY, CURDATE(), st.target_end_date) as days_remaining,
                CASE
                    WHEN st.achieved_value >= st.target_value THEN 'achieved'
                    WHEN ROUND((st.achieved_value / NULLIF(st.target_value, 0)) * 100, 2) >= 75 THEN 'on_track'
                    WHEN ROUND((st.achieved_value / NULLIF(st.target_value, 0)) * 100, 2) >= 50 THEN 'behind'
                    ELSE 'at_risk'
                END as target_status
            FROM sales_targets st
            WHERE st.company_id = ? AND st.target_end_date >= CURDATE()
            ORDER BY st.target_end_date ASC
        ", [$this->user['company_id']]);
    }

    private function getRecentActivities() {
        return $this->db->query("
            SELECT
                'order' as activity_type,
                CONCAT('New order from ', c.customer_name) as description,
                so.order_date as activity_date,
                so.total_amount as amount,
                so.id as reference_id
            FROM sales_orders so
            JOIN customers c ON so.customer_id = c.id
            WHERE so.company_id = ?
            UNION ALL
            SELECT
                'opportunity' as activity_type,
                CONCAT('Opportunity updated: ', o.opportunity_name) as description,
                o.last_updated as activity_date,
                o.expected_value as amount,
                o.id as reference_id
            FROM opportunities o
            WHERE o.company_id = ?
            UNION ALL
            SELECT
                'lead' as activity_type,
                CONCAT('New lead: ', l.lead_name) as description,
                l.created_date as activity_date,
                NULL as amount,
                l.id as reference_id
            FROM leads l
            WHERE l.company_id = ?
            ORDER BY activity_date DESC
            LIMIT 10
        ", [$this->user['company_id'], $this->user['company_id'], $this->user['company_id']]);
    }

    private function getUpcomingTasks() {
        return $this->db->query("
            SELECT
                'follow_up' as task_type,
                CONCAT('Follow up with ', c.customer_name) as task_description,
                fu.follow_up_date as due_date,
                fu.priority,
                fu.id as task_id
            FROM follow_ups fu
            JOIN customers c ON fu.customer_id = c.id
            WHERE fu.company_id = ? AND fu.status = 'pending' AND fu.follow_up_date >= CURDATE()
            UNION ALL
            SELECT
                'meeting' as task_type,
                CONCAT('Meeting with ', c.customer_name) as task_description,
                m.meeting_date as due_date,
                m.priority,
                m.id as task_id
            FROM meetings m
            JOIN customers c ON m.customer_id = c.id
            WHERE m.company_id = ? AND m.status = 'scheduled' AND m.meeting_date >= CURDATE()
            ORDER BY due_date ASC
            LIMIT 10
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getSalesForecast() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(sf.forecast_date, '%Y-%m') as forecast_month,
                sf.forecasted_revenue,
                sf.actual_revenue,
                sf.confidence_level,
                ROUND(((sf.actual_revenue - sf.forecasted_revenue) / NULLIF(sf.forecasted_revenue, 0)) * 100, 2) as forecast_accuracy,
                sf.forecast_method,
                sf.last_updated
            FROM sales_forecast sf
            WHERE sf.company_id = ?
            ORDER BY sf.forecast_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCustomers($filters = []) {
        $where = ["c.company_id = ?"];
        $params = [$this->user['company_id']];

        if (isset($filters['segment'])) {
            $where[] = "c.customer_segment = ?";
            $params[] = $filters['segment'];
        }

        if (isset($filters['status'])) {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['value_tier'])) {
            switch ($filters['value_tier']) {
                case 'high':
                    $where[] = "(SELECT SUM(so.total_amount) FROM sales_orders so WHERE so.customer_id = c.id) >= 10000";
                    break;
                case 'medium':
                    $where[] = "(SELECT SUM(so.total_amount) FROM sales_orders so WHERE so.customer_id = c.id) BETWEEN 1000 AND 9999";
                    break;
                case 'low':
                    $where[] = "(SELECT SUM(so.total_amount) FROM sales_orders so WHERE so.customer_id = c.id) < 1000";
                    break;
            }
        }

        if (isset($filters['date_from'])) {
            $where[] = "c.created_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (isset($filters['date_to'])) {
            $where[] = "c.created_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (isset($filters['search'])) {
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
                COUNT(so.id) as total_orders,
                SUM(so.total_amount) as total_revenue,
                AVG(so.total_amount) as avg_order_value,
                MAX(so.order_date) as last_order_date,
                TIMESTAMPDIFF(DAY, MAX(so.order_date), CURDATE()) as days_since_last_order,
                CASE
                    WHEN SUM(so.total_amount) >= 10000 THEN 'high'
                    WHEN SUM(so.total_amount) >= 1000 THEN 'medium'
                    ELSE 'low'
                END as value_tier,
                CASE
                    WHEN TIMESTAMPDIFF(DAY, MAX(so.order_date), CURDATE()) <= 30 THEN 'active'
                    WHEN TIMESTAMPDIFF(DAY, MAX(so.order_date), CURDATE()) <= 90 THEN 'recent'
                    WHEN TIMESTAMPDIFF(DAY, MAX(so.order_date), CURDATE()) <= 180 THEN 'at_risk'
                    ELSE 'churned'
                END as customer_status
            FROM customers c
            LEFT JOIN sales_orders so ON c.id = so.customer_id
            WHERE $whereClause
            GROUP BY c.id
            ORDER BY total_revenue DESC
        ", $params);
    }

    private function getCustomerSegments() {
        return $this->db->query("
            SELECT
                cs.segment_name,
                COUNT(c.id) as customer_count,
                SUM(so.total_amount) as total_revenue,
                AVG(so.total_amount) as avg_order_value,
                AVG(cs.segment_score) as avg_segment_score
            FROM customer_segments cs
            LEFT JOIN customers c ON cs.id = c.segment_id
            LEFT JOIN sales_orders so ON c.id = so.customer_id
            WHERE cs.company_id = ?
            GROUP BY cs.id, cs.segment_name
            ORDER BY total_revenue DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerStatus() {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'prospect' => 'Prospect',
            'churned' => 'Churned'
        ];
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
            'update_segment' => 'Update Segment',
            'send_email' => 'Send Email Campaign',
            'create_opportunities' => 'Create Opportunities',
            'export_customers' => 'Export Customers',
            'bulk_update' => 'Bulk Update',
            'assign_territories' => 'Assign Territories'
        ];
    }

    private function getCustomerAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(c.id) as total_customers,
                COUNT(CASE WHEN c.status = 'active' THEN 1 END) as active_customers,
                ROUND((COUNT(CASE WHEN c.status = 'active' THEN 1 END) / NULLIF(COUNT(c.id), 0)) * 100, 2) as active_percentage,
                COUNT(DISTINCT c.customer_segment) as segments_used,
                AVG(c.customer_score) as avg_customer_score,
                COUNT(CASE WHEN c.customer_score >= 80 THEN 1 END) as high_value_customers,
                COUNT(CASE WHEN c.customer_score < 50 THEN 1 END) as low_value_customers,
                SUM(so.total_amount) as total_revenue,
                AVG(so.total_amount) as avg_order_value
            FROM customers c
            LEFT JOIN sales_orders so ON c.id = so.customer_id
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getLeads() {
        return $this->db->query("
            SELECT
                l.*,
                ls.source_name,
                l.lead_score,
                l.lead_status,
                TIMESTAMPDIFF(DAY, l.created_date, CURDATE()) as days_old,
                CASE
                    WHEN l.lead_score >= 80 THEN 'hot'
                    WHEN l.lead_score >= 60 THEN 'warm'
                    WHEN l.lead_score >= 40 THEN 'cool'
                    ELSE 'cold'
                END as lead_temperature
            FROM leads l
            LEFT JOIN lead_sources ls ON l.source_id = ls.id
            WHERE l.company_id = ?
            ORDER BY l.lead_score DESC, l.created_date DESC
        ", [$this->user['company_id']]);
    }

    private function getOpportunities() {
        return $this->db->query("
            SELECT
                o.*,
                c.customer_name,
                ps.stage_name,
                o.probability_percentage,
                o.expected_value,
                (o.expected_value * o.probability_percentage / 100) as weighted_value,
                TIMESTAMPDIFF(DAY, o.created_date, CURDATE()) as days_open,
                CASE
                    WHEN o.probability_percentage >= 80 THEN 'high'
                    WHEN o.probability_percentage >= 60 THEN 'medium'
                    WHEN o.probability_percentage >= 40 THEN 'low'
                    ELSE 'very_low'
                END as probability_tier
            FROM opportunities o
            JOIN customers c ON o.customer_id = c.id
            LEFT JOIN pipeline_stages ps ON o.stage_id = ps.id
            WHERE o.company_id = ? AND o.status = 'active'
            ORDER BY weighted_value DESC
        ", [$this->user['company_id']]);
    }

    private function getLeadSources() {
        return $this->db->query("
            SELECT
                ls.source_name,
                COUNT(l.id) as leads_count,
                COUNT(CASE WHEN l.lead_status = 'converted' THEN 1 END) as converted_leads,
                ROUND((COUNT(CASE WHEN l.lead_status = 'converted' THEN 1 END) / NULLIF(COUNT(l.id), 0)) * 100, 2) as conversion_rate,
                AVG(l.lead_score) as avg_lead_score,
                SUM(o.expected_value) as total_opportunity_value
            FROM lead_sources ls
            LEFT JOIN leads l ON ls.id = l.source_id
            LEFT JOIN opportunities o ON l.id = o.lead_id
            WHERE ls.company_id = ?
            GROUP BY ls.id, ls.source_name
            ORDER BY leads_count DESC
        ", [$this->user['company_id']]);
    }

    private function getLeadStatus() {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'converted' => 'Converted',
            'lost' => 'Lost'
        ];
    }

    private function getOpportunityStages() {
        return $this->db->query("
            SELECT
                ps.stage_name,
                ps.stage_order,
                COUNT(o.id) as opportunities_count,
                SUM(o.expected_value) as total_value,
                AVG(o.probability_percentage) as avg_probability,
                ROUND((COUNT(o.id) / NULLIF((SELECT COUNT(*) FROM opportunities WHERE company_id = ?), 0)) * 100, 2) as stage_percentage
            FROM pipeline_stages ps
            LEFT JOIN opportunities o ON ps.id = o.stage_id
            WHERE ps.company_id = ?
            GROUP BY ps.id, ps.stage_name, ps.stage_order
            ORDER BY ps.stage_order ASC
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getConversionRates() {
        return $this->db->querySingle("
            SELECT
                COUNT(l.id) as total_leads,
                COUNT(CASE WHEN l.lead_status = 'converted' THEN 1 END) as converted_leads,
                ROUND((COUNT(CASE WHEN l.lead_status = 'converted' THEN 1 END) / NULLIF(COUNT(l.id), 0)) * 100, 2) as overall_conversion_rate,
                COUNT(o.id) as total_opportunities,
                COUNT(CASE WHEN o.status = 'won' THEN 1 END) as won_opportunities,
                ROUND((COUNT(CASE WHEN o.status = 'won' THEN 1 END) / NULLIF(COUNT(o.id), 0)) * 100, 2) as opportunity_win_rate,
                AVG(TIMESTAMPDIFF(DAY, l.created_date, COALESCE(o.close_date, CURDATE()))) as avg_conversion_time
            FROM leads l
            LEFT JOIN opportunities o ON l.id = o.lead_id
            WHERE l.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getLeadScoring() {
        return $this->db->query("
            SELECT
                l.lead_name,
                l.lead_score,
                lsc.criteria_name,
                lsc.criteria_score,
                lsc.scoring_date
            FROM leads l
            JOIN lead_scoring_criteria lsc ON l.id = lsc.lead_id
            WHERE l.company_id = ?
            ORDER BY l.lead_score DESC, lsc.scoring_date DESC
        ", [$this->user['company_id']]);
    }

    private function getLeadAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(l.id) as total_leads,
                AVG(l.lead_score) as avg_lead_score,
                COUNT(CASE WHEN l.lead_score >= 80 THEN 1 END) as hot_leads,
                COUNT(CASE WHEN l.lead_score >= 60 AND l.lead_score < 80 THEN 1 END) as warm_leads,
                COUNT(CASE WHEN l.lead_score < 40 THEN 1 END) as cold_leads,
                COUNT(CASE WHEN l.lead_status = 'converted' THEN 1 END) as converted_leads,
                ROUND((COUNT(CASE WHEN l.lead_status = 'converted' THEN 1 END) / NULLIF(COUNT(l.id), 0)) * 100, 2) as conversion_rate,
                AVG(TIMESTAMPDIFF(DAY, l.created_date, CURDATE())) as avg_lead_age
            FROM leads l
            WHERE l.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPipelineStages() {
        return $this->db->query("
            SELECT
                ps.*,
                COUNT(o.id) as opportunities_count,
                SUM(o.expected_value) as total_value,
                AVG(o.probability_percentage) as avg_probability,
                SUM(o.expected_value * o.probability_percentage / 100) as weighted_value
            FROM pipeline_stages ps
            LEFT JOIN opportunities o ON ps.id = o.stage_id AND o.status = 'active'
            WHERE ps.company_id = ?
            GROUP BY ps.id
            ORDER BY ps.stage_order ASC
        ", [$this->user['company_id']]);
    }

    private function getPipelineOpportunities() {
        return $this->db->query("
            SELECT
                o.opportunity_name,
                c.customer_name,
                ps.stage_name,
                o.expected_value,
                o.probability_percentage,
                (o.expected_value * o.probability_percentage / 100) as weighted_value,
                o.expected_close_date,
                TIMESTAMPDIFF(DAY, CURDATE(), o.expected_close_date) as days_to_close,
                CASE
                    WHEN TIMESTAMPDIFF(DAY, CURDATE(), o.expected_close_date) < 0 THEN 'overdue'
                    WHEN TIMESTAMPDIFF(DAY, CURDATE(), o.expected_close_date) <= 30 THEN 'due_soon'
                    ELSE 'on_track'
                END as close_status
            FROM opportunities o
            JOIN customers c ON o.customer_id = c.id
            JOIN pipeline_stages ps ON o.stage_id = ps.id
            WHERE o.company_id = ? AND o.status = 'active'
            ORDER BY weighted_value DESC
        ", [$this->user['company_id']]);
    }

    private function getPipelineForecast() {
        return $this->db->query("
            SELECT
                ps.stage_name,
                COUNT(o.id) as opportunities_count,
                SUM(o.expected_value) as total_value,
                SUM(o.expected_value * o.probability_percentage / 100) as weighted_forecast,
                AVG(o.expected_close_date) as avg_close_date,
                COUNT(CASE WHEN o.expected_close_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as closing_this_month,
                COUNT(CASE WHEN o.expected_close_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as closing_this_quarter
            FROM pipeline_stages ps
            LEFT JOIN opportunities o ON ps.id = o.stage_id AND o.status = 'active'
            WHERE ps.company_id = ?
            GROUP BY ps.id, ps.stage_name
            ORDER BY ps.stage_order ASC
        ", [$this->user['company_id']]);
    }

    private function getPipelineVelocity() {
        return $this->db->querySingle("
            SELECT
                COUNT(o.id) as total_opportunities,
                AVG(TIMESTAMPDIFF(DAY, o.created_date, COALESCE(o.close_date, CURDATE()))) as avg_days_in_pipeline,
                COUNT(CASE WHEN TIMESTAMPDIFF(DAY, o.created_date, COALESCE(o.close_date, CURDATE())) <= 30 THEN 1 END) as fast_closes,
                COUNT(CASE WHEN TIMESTAMPDIFF(DAY, o.created_date, COALESCE(o.close_date, CURDATE())) > 90 THEN 1 END) as slow_opportunities,
                ROUND((COUNT(CASE WHEN TIMESTAMPDIFF(DAY, o.created_date, COALESCE(o.close_date, CURDATE())) <= 30 THEN 1 END) / NULLIF(COUNT(o.id), 0)) * 100, 2) as fast_close_rate,
                AVG(o.expected_value) as avg_opportunity_size,
                SUM(o.expected_value) / NULLIF(AVG(TIMESTAMPDIFF(DAY, o.created_date, COALESCE(o.close_date, CURDATE()))), 0) as velocity_index
            FROM opportunities o
            WHERE o.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPipelineConversion() {
        return $this->db->query("
            SELECT
                ps1.stage_name as from_stage,
                ps2.stage_name as to_stage,
                COUNT(pc.id) as conversions_count,
                AVG(TIMESTAMPDIFF(DAY, pc.stage_entry_date, pc.stage_exit_date)) as avg_days_in_stage,
                ROUND((COUNT(pc.id) / NULLIF((SELECT COUNT(*) FROM opportunities WHERE stage_id = ps1.id AND company_id = ?), 0)) * 100, 2) as conversion_rate
            FROM pipeline_conversions pc
            JOIN pipeline_stages ps1 ON pc.from_stage_id = ps1.id
            JOIN pipeline_stages ps2 ON pc.to_stage_id = ps2.id
            WHERE pc.company_id = ?
            GROUP BY pc.from_stage_id, pc.to_stage_id, ps1.stage_name, ps2.stage_name
            ORDER BY ps1.stage_order ASC, ps2.stage_order ASC
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getPipelineAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT ps.id) as total_stages,
                COUNT(o.id) as total_opportunities,
                SUM(o.expected_value) as total_pipeline_value,
                SUM(o.expected_value * o.probability_percentage / 100) as weighted_pipeline_value,
                AVG(o.probability_percentage) as avg_probability,
                COUNT(CASE WHEN o.probability_percentage >= 80 THEN 1 END) as high_probability_opportunities,
                COUNT(CASE WHEN o.expected_close_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as opportunities_closing_this_month,
                MAX(o.expected_close_date) as furthest_close_date
            FROM pipeline_stages ps
            LEFT JOIN opportunities o ON ps.id = o.stage_id AND o.status = 'active'
            WHERE ps.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPipelineSettings() {
        return $this->db->querySingle("
            SELECT * FROM pipeline_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSalesOrders() {
        return $this->db->query("
            SELECT
                so.*,
                c.customer_name,
                so.order_number,
                so.order_date,
                so.expected_delivery_date,
                so.total_amount,
                so.status,
                COUNT(soi.id) as total_items,
                SUM(soi.quantity) as total_quantity,
                so.approved_by,
                u.first_name as created_by_first,
                u.last_name as created_by_last
            FROM sales_orders so
            JOIN customers c ON so.customer_id = c.id
            LEFT JOIN sales_order_items soi ON so.id = soi.sales_order_id
            LEFT JOIN users u ON so.created_by = u.id
            WHERE so.company_id = ?
            GROUP BY so.id
            ORDER BY so.order_date DESC
        ", [$this->user['company_id']]);
    }

    private function getOrderApprovals() {
        return $this->db->query("
            SELECT
                so.order_number,
                c.customer_name,
                so.total_amount,
                oa.approval_level,
                oa.approval_status,
                oa.approved_by,
                oa.approval_date,
                oa.comments,
                u.first_name as approver_first,
                u.last_name as approver_last
            FROM sales_orders so
            JOIN customers c ON so.customer_id = c.id
            LEFT JOIN order_approvals oa ON so.id = oa.sales_order_id
            LEFT JOIN users u ON oa.approved_by = u.id
            WHERE so.company_id = ?
            ORDER BY so.order_date DESC, oa.approval_level ASC
        ", [$this->user['company_id']]);
    }

    private function getOrderFulfillment() {
        return $this->db->query("
            SELECT
                so.order_number,
                c.customer_name,
                of.fulfillment_status,
                of.shipment_date,
                of.delivery_date,
                of.tracking_number,
                of.carrier,
                COUNT(soi.id) as items_fulfilled,
                SUM(soi.quantity) as total_quantity_fulfilled,
                of.warehouse_location,
                u.first_name as fulfilled_by_first,
                u.last_name as fulfilled_by_last
            FROM sales_orders so
            JOIN customers c ON so.customer_id = c.id
            LEFT JOIN order_fulfillment of ON so.id = of.sales_order_id
            LEFT JOIN sales_order_items soi ON so.id = soi.sales_order_id
            LEFT JOIN users u ON of.fulfilled_by = u.id
            WHERE so.company_id = ?
            GROUP BY so.id, of.id
            ORDER BY of.shipment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getOrderInvoicing() {
        return $this->db->query("
            SELECT
                so.order_number,
                c.customer_name,
                oi.invoice_number,
                oi.invoice_date,
                oi.invoice_amount,
                oi.payment_terms,
                oi.due_date,
                oi.payment_status,
                TIMESTAMPDIFF(DAY, CURDATE(), oi.due_date) as days_until_due,
                oi.approved_by,
                u.first_name as approved_by_first,
                u.last_name as approved_by_last
            FROM sales_orders so
            JOIN customers c ON so.customer_id = c.id
            LEFT JOIN order_invoicing oi ON so.id = oi.sales_order_id
            LEFT JOIN users u ON oi.approved_by = u.id
            WHERE so.company_id = ?
            ORDER BY oi.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getOrderReturns() {
        return $this->db->query("
            SELECT
                so.order_number,
                c.customer_name,
                orr.return_number,
                orr.return_date,
                orr.return_reason,
                COUNT(soi.id) as items_returned,
                SUM(soi.quantity) as total_quantity_returned,
                SUM(soi.quantity * soi.unit_price) as total_return_value,
                orr.return_status,
                orr.processed_by,
                u.first_name as processed_by_first,
                u.last_name as processed_by_last
            FROM sales_orders so
            JOIN customers c ON so.customer_id = c.id
            LEFT JOIN order_returns orr ON so.id = orr.sales_order_id
            LEFT JOIN sales_order_items soi ON so.id = soi.sales_order_id
            LEFT JOIN users u ON orr.processed_by = u.id
            WHERE so.company_id = ?
            GROUP BY so.id, orr.id
            ORDER BY orr.return_date DESC
        ", [$this->user['company_id']]);
    }

    private function getOrderAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(so.id) as total_orders,
                SUM(so.total_amount) as total_order_value,
                COUNT(CASE WHEN so.status = 'completed' THEN 1 END) as completed_orders,
                ROUND((COUNT(CASE WHEN so.status = 'completed' THEN 1 END) / NULLIF(COUNT(so.id), 0)) * 100, 2) as completion_rate,
                AVG(so.total_amount) as avg_order_value,
                COUNT(DISTINCT so.customer_id) as unique_customers,
                AVG(TIMESTAMPDIFF(DAY, so.order_date, COALESCE(so.delivery_date, CURDATE()))) as avg_fulfillment_time,
                COUNT(CASE WHEN so.status = 'returned' THEN 1 END) as returned_orders,
                ROUND((COUNT(CASE WHEN so.status = 'returned' THEN 1 END) / NULLIF(COUNT(so.id), 0)) * 100, 2) as return_rate
            FROM sales_orders so
            WHERE so.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getOrderTemplates() {
        return $this->db->query("
            SELECT * FROM order_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getOrderSettings() {
        return $this->db->querySingle("
            SELECT * FROM order_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSalesPerformance() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                COUNT(so.id) as orders_count,
                SUM(so.total_amount) as total_revenue,
                AVG(so.total_amount) as avg_order_value,
                COUNT(DISTINCT so.customer_id) as unique_customers,
                ROUND((SUM(so.total_amount) / NULLIF((SELECT SUM(total_amount) FROM sales_orders WHERE company_id = ?), 0)) * 100, 2) as revenue_percentage,
                COUNT(o.id) as opportunities_created,
                COUNT(CASE WHEN o.status = 'won' THEN 1 END) as opportunities_won,
                ROUND((COUNT(CASE WHEN o.status = 'won' THEN 1 END) / NULLIF(COUNT(o.id), 0)) * 100, 2) as win_rate
            FROM users u
            LEFT JOIN sales_orders so ON u.id = so.sales_rep_id
            LEFT JOIN opportunities o ON u.id = o.assigned_to
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY total_revenue DESC
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getProductAnalytics() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                COUNT(soi.id) as orders_count,
                SUM(soi.quantity) as total_quantity_sold,
                SUM(soi.quantity * soi.unit_price) as total_revenue,
                AVG(soi.unit_price) as avg_selling_price,
                COUNT(DISTINCT so.customer_id) as unique_customers,
                ROUND((SUM(soi.quantity * soi.unit_price) / NULLIF((SELECT SUM(total_amount) FROM sales_orders WHERE company_id = ?), 0)) * 100, 2) as revenue_percentage,
                MAX(so.order_date) as last_sale_date,
                AVG(soi.quantity) as avg_quantity_per_order
            FROM products p
            LEFT JOIN sales_order_items soi ON p.id = soi.product_id
            LEFT JOIN sales_orders so ON soi.sales_order_id = so.id
            WHERE p.company_id = ?
            GROUP BY p.id, p.product_name, p.sku
            ORDER BY total_revenue DESC
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getTerritoryAnalytics() {
        return $this->db->query("
            SELECT
                t.territory_name,
                COUNT(c.id) as customers_count,
                SUM(so.total_amount) as total_revenue,
                AVG(so.total_amount) as avg_order_value,
                COUNT(DISTINCT so.customer_id) as active_customers,
                ROUND((SUM(so.total_amount) / NULLIF((SELECT SUM(total_amount) FROM sales_orders WHERE company_id = ?), 0)) * 100, 2) as revenue_percentage,
                COUNT(o.id) as opportunities_count,
                COUNT(CASE WHEN o.status = 'won' THEN 1 END) as won_opportunities,
                ROUND((COUNT(CASE WHEN o.status = 'won' THEN 1 END) / NULLIF(COUNT(o.id), 0)) * 100, 2) as win_rate
            FROM territories t
            LEFT JOIN customers c ON t.id = c.territory_id
            LEFT JOIN sales_orders so ON c.id = so.customer_id
            LEFT JOIN opportunities o ON c.id = o.customer_id
            WHERE t.company_id = ?
            GROUP BY t.id, t.territory_name
            ORDER BY total_revenue DESC
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getSalesForecasting() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(sf.forecast_date, '%Y-%m') as forecast_month,
                sf.forecasted_revenue,
                sf.actual_revenue,
                sf.confidence_level,
                ROUND(((sf.actual_revenue - sf.forecasted_revenue) / NULLIF(sf.forecasted_revenue, 0)) * 100, 2) as forecast_accuracy,
                sf.forecast_method,
                sf.last_updated
            FROM sales_forecasting sf
            WHERE sf.company_id = ?
            ORDER BY sf.forecast_date ASC
        ", [$this->user['company_id']]);
    }

    private function getSalesTrends() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(so.order_date, '%Y-%m') as month,
                COUNT(so.id) as orders_count,
                SUM(so.total_amount) as total_revenue,
                AVG(so.total_amount) as avg_order_value,
                COUNT(DISTINCT so.customer_id) as unique_customers,
                SUM(so.total_amount) / COUNT(DISTINCT so.customer_id) as revenue_per_customer,
                ROUND((SUM(so.total_amount) - LAG(SUM(so.total_amount)) OVER (ORDER BY DATE_FORMAT(so.order_date, '%Y-%m'))) / NULLIF(LAG(SUM(so.total_amount)) OVER (ORDER BY DATE_FORMAT(so.order_date, '%Y-%m')), 0) * 100, 2) as month_over_month_growth
            FROM sales_orders so
            WHERE so.company_id = ? AND so.order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(so.order_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getCustomDashboards() {
        return $this->db->query("
            SELECT
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

    private function getTargetAchievement() {
        return $this->db->query("
            SELECT
                st.target_name,
                st.target_value,
                st.achieved_value,
                ROUND((st.achieved_value / NULLIF(st.target_value, 0)) * 100, 2) as achievement_percentage,
                st.target_start_date,
                st.target_end_date,
                TIMESTAMPDIFF(DAY, CURDATE(), st.target_end_date) as days_remaining,
                CASE
                    WHEN st.achieved_value >= st.target_value THEN 'achieved'
                    WHEN ROUND((st.achieved_value / NULLIF(st.target_value, 0)) * 100, 2) >= 75 THEN 'on_track'
                    WHEN ROUND((st.achieved_value / NULLIF(st.target_value, 0)) * 100, 2) >= 50 THEN 'behind'
                    ELSE 'at_risk'
                END as target_status
            FROM sales_targets st
            WHERE st.company_id = ? AND st.target_end_date >= CURDATE()
            ORDER BY achievement_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getQuotaManagement() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                q.quota_period,
                q.quota_value,
                q.achieved_value,
                ROUND((q.achieved_value / NULLIF(q.quota_value, 0)) * 100, 2) as achievement_percentage,
                q.quota_start_date,
                q.quota_end_date,
                CASE
                    WHEN q.achieved_value >= q.quota_value THEN 'exceeded'
                    WHEN ROUND((q.achieved_value / NULLIF(q.quota_value, 0)) * 100, 2) >= 90 THEN 'on_track'
                    WHEN ROUND((q.achieved_value / NULLIF(q.quota_value, 0)) * 100, 2) >= 75 THEN 'behind'
                    ELSE 'at_risk'
                END as quota_status
            FROM quotas q
            JOIN users u ON q.user_id = u.id
            WHERE q.company_id = ?
            ORDER BY achievement_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getCommissionStructure() {
        return $this->db->query("
            SELECT
                cs.commission_name,
                cs.commission_type,
                cs.commission_rate,
                cs.commission_base,
                cs.effective_date,
                cs.expiry_date,
                cs.is_active,
                COUNT(c.id) as applicable_customers
            FROM commission_structures cs
            LEFT JOIN customers c ON cs.id = c.commission_structure_id
            WHERE cs.company_id = ?
            ORDER BY cs.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getIncentivePrograms() {
        return $this->db->query("
            SELECT
                ip.program_name,
                ip.program_type,
                ip.target_metric,
                ip.reward_amount,
                ip.reward_type,
                ip.program_start_date,
                ip.program_end_date,
                COUNT(ipu.id) as participants_count,
                SUM(ipu.reward_earned) as total_rewards_paid
            FROM incentive_programs ip
            LEFT JOIN incentive_program_users ipu ON ip.id = ipu.program_id
            WHERE ip.company_id = ?
            GROUP BY ip.id
            ORDER BY ip.program_start_date DESC
        ", [$this->user['company_id']]);
    }

    private function getTargetAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(st.id) as total_targets,
                COUNT(CASE WHEN st.achieved_value >= st.target_value THEN 1 END) as achieved_targets,
                ROUND((COUNT(CASE WHEN st.achieved_value >= st.target_value THEN 1 END) / NULLIF(COUNT(st.id), 0)) * 100, 2) as achievement_rate,
                AVG(ROUND((st.achieved_value / NULLIF(st.target_value, 0)) * 100, 2)) as avg_achievement_percentage,
                SUM(st.target_value) as total_target_value,
                SUM(st.achieved_value) as total_achieved_value,
                ROUND((SUM(st.achieved_value) / NULLIF(SUM(st.target_value), 0)) * 100, 2) as overall_achievement_percentage
            FROM sales_targets st
            WHERE st.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTargetSettings() {
        return $this->db->querySingle("
            SELECT * FROM target_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEmailCampaigns() {
        return $this->db->query("
            SELECT
                ec.campaign_name,
                ec.campaign_type,
                ec.sent_date,
                ec.recipient_count,
                ec.open_count,
                ec.click_count,
                ROUND((ec.open_count / NULLIF(ec.recipient_count, 0)) * 100, 2) as open_rate,
                ROUND((ec.click_count / NULLIF(ec.open_count, 0)) * 100, 2) as click_rate,
                ec.status,
                u.first_name as created_by_first,
                u.last_name as created_by_last
            FROM email_campaigns ec
            LEFT JOIN users u ON ec.created_by = u.id
            WHERE ec.company_id = ?
            ORDER BY ec.sent_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCommunicationHistory() {
        return $this->db->query("
            SELECT
                ch.communication_type,
                ch.subject,
                ch.sent_date,
                c.customer_name,
                ch.status,
                ch.response_date,
                TIMESTAMPDIFF(DAY, ch.sent_date, COALESCE(ch.response_date, CURDATE())) as response_time,
                u.first_name as sent_by_first,
                u.last_name as sent_by_last
            FROM communication_history ch
            JOIN customers c ON ch.customer_id = c.id
            LEFT JOIN users u ON ch.sent_by = u.id
            WHERE ch.company_id = ?
            ORDER BY ch.sent_date DESC
        ", [$this->user['company_id']]);
    }

    private function getFollowUpReminders() {
        return $this->db->query("
            SELECT
                c.customer_name,
                fu.follow_up_type,
                fu.follow_up_date,
                fu.priority,
                fu.notes,
                TIMESTAMPDIFF(DAY, CURDATE(), fu.follow_up_date) as days_until_due,
                CASE
                    WHEN fu.follow_up_date < CURDATE() THEN 'overdue'
                    WHEN fu.follow_up_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'due_today'
                    WHEN fu.follow_up_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'due_this_week'
                    ELSE 'upcoming'
                END as due_status,
                u.first_name as assigned_to_first,
                u.last_name as assigned_to_last
            FROM follow_up_reminders fu
            JOIN customers c ON fu.customer_id = c.id
            LEFT JOIN users u ON fu.assigned_to = u.id
            WHERE fu.company_id = ? AND fu.status = 'pending'
            ORDER BY fu.follow_up_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCommunicationTemplates() {
        return $this->db->query("
            SELECT * FROM communication_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getCommunicationAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(ec.id) as total_campaigns,
                SUM(ec.recipient_count) as total_recipients,
                SUM(ec.open_count) as total_opens,
                SUM(ec.click_count) as total_clicks,
                ROUND((SUM(ec.open_count) / NULLIF(SUM(ec.recipient_count), 0)) * 100, 2) as avg_open_rate,
                ROUND((SUM(ec.click_count) / NULLIF(SUM(ec.open_count), 0)) * 100, 2) as avg_click_rate,
                COUNT(ch.id) as total_communications,
                COUNT(CASE WHEN ch.response_date IS NOT NULL THEN 1 END) as responded_communications,
                ROUND((COUNT(CASE WHEN ch.response_date IS NOT NULL THEN 1 END) / NULLIF(COUNT(ch.id), 0)) * 100, 2) as response_rate,
                AVG(TIMESTAMPDIFF(DAY, ch.sent_date, ch.response_date)) as avg_response_time
            FROM email_campaigns ec
            LEFT JOIN communication_history ch ON ec.id = ch.campaign_id
            WHERE ec.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCommunicationSettings() {
        return $this->db->querySingle("
            SELECT * FROM communication_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSalesReports() {
        return $this->db->query("
            SELECT
                sr.report_name,
                sr.report_type,
                sr.generated_date,
                sr.total_revenue,
                sr.total_orders,
                sr.avg_order_value,
                sr.report_period,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM sales_reports sr
            LEFT JOIN users u ON sr.generated_by = u.id
            WHERE sr.company_id = ?
            ORDER BY sr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerReports() {
        return $this->db->query("
            SELECT
                cr.report_name,
                cr.report_type,
                cr.generated_date,
                cr.total_customers,
                cr.active_customers,
                cr.new_customers,
                cr.churned_customers,
                cr.report_period,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM customer_reports cr
            LEFT JOIN users u ON cr.generated_by = u.id
            WHERE cr.company_id = ?
            ORDER BY cr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPipelineReports() {
        return $this->db->query("
            SELECT
                pr.report_name,
                pr.report_type,
                pr.generated_date,
                pr.total_opportunities,
                pr.total_pipeline_value,
                pr.avg_deal_size,
                pr.conversion_rate,
                pr.report_period,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM pipeline_reports pr
            LEFT JOIN users u ON pr.generated_by = u.id
            WHERE pr.company_id = ?
            ORDER BY pr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceReports() {
        return $this->db->query("
            SELECT
                pr.report_name,
                pr.report_type,
                pr.generated_date,
                pr.total_sales_reps,
                pr.avg_revenue_per_rep,
                pr.top_performer,
                pr.report_period,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM performance_reports pr
            LEFT JOIN users u ON pr.generated_by = u.id
            WHERE pr.company_id = ?
            ORDER BY pr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getForecastReports() {
        return $this->db->query("
            SELECT
                fr.report_name,
                fr.report_type,
                fr.generated_date,
                fr.forecast_accuracy,
                fr.confidence_level,
                fr.forecast_period,
                fr.report_period,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM forecast_reports fr
            LEFT JOIN users u ON fr.generated_by = u.id
            WHERE fr.company_id = ?
            ORDER BY fr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomReports() {
        return $this->db->query("
            SELECT
                cr.report_name,
                cr.report_type,
                cr.created_date,
                cr.last_run_date,
                cr.run_count,
                cr.is_scheduled,
                cr.schedule_frequency,
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
}
