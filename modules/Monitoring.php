<?php
/**
 * TPT Free ERP - Monitoring & Analytics Module
 * Complete system monitoring, performance tracking, analytics dashboards, and alerting system
 */

class Monitoring extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main monitoring dashboard
     */
    public function index() {
        $this->requirePermission('monitoring.view');

        $data = [
            'title' => 'System Monitoring Dashboard',
            'system_overview' => $this->getSystemOverview(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'analytics_overview' => $this->getAnalyticsOverview(),
            'alerts_overview' => $this->getAlertsOverview(),
            'health_status' => $this->getHealthStatus(),
            'recent_activity' => $this->getRecentActivity()
        ];

        $this->render('modules/monitoring/dashboard', $data);
    }

    /**
     * System monitoring
     */
    public function system() {
        $this->requirePermission('monitoring.system.view');

        $data = [
            'title' => 'System Monitoring',
            'server_metrics' => $this->getServerMetrics(),
            'database_metrics' => $this->getDatabaseMetrics(),
            'application_metrics' => $this->getApplicationMetrics(),
            'resource_usage' => $this->getResourceUsage(),
            'system_logs' => $this->getSystemLogs(),
            'uptime_history' => $this->getUptimeHistory()
        ];

        $this->render('modules/monitoring/system', $data);
    }

    /**
     * Performance tracking
     */
    public function performance() {
        $this->requirePermission('monitoring.performance.view');

        $data = [
            'title' => 'Performance Tracking',
            'response_times' => $this->getResponseTimes(),
            'throughput_metrics' => $this->getThroughputMetrics(),
            'error_rates' => $this->getErrorRates(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
            'bottlenecks' => $this->getBottlenecks(),
            'performance_trends' => $this->getPerformanceTrends()
        ];

        $this->render('modules/monitoring/performance', $data);
    }

    /**
     * Analytics dashboards
     */
    public function analytics() {
        $this->requirePermission('monitoring.analytics.view');

        $data = [
            'title' => 'Analytics Dashboard',
            'user_analytics' => $this->getUserAnalytics(),
            'business_analytics' => $this->getBusinessAnalytics(),
            'system_analytics' => $this->getSystemAnalytics(),
            'custom_reports' => $this->getCustomReports(),
            'data_visualizations' => $this->getDataVisualizations(),
            'analytics_settings' => $this->getAnalyticsSettings()
        ];

        $this->render('modules/monitoring/analytics', $data);
    }

    /**
     * Alerting system
     */
    public function alerts() {
        $this->requirePermission('monitoring.alerts.view');

        $data = [
            'title' => 'Alert Management',
            'active_alerts' => $this->getActiveAlerts(),
            'alert_history' => $this->getAlertHistory(),
            'alert_rules' => $this->getAlertRules(),
            'alert_channels' => $this->getAlertChannels(),
            'alert_templates' => $this->getAlertTemplates(),
            'alert_analytics' => $this->getAlertAnalytics()
        ];

        $this->render('modules/monitoring/alerts', $data);
    }

    /**
     * Logs and audit trail
     */
    public function logs() {
        $this->requirePermission('monitoring.logs.view');

        $data = [
            'title' => 'System Logs',
            'application_logs' => $this->getApplicationLogs(),
            'security_logs' => $this->getSecurityLogs(),
            'error_logs' => $this->getErrorLogs(),
            'access_logs' => $this->getAccessLogs(),
            'audit_logs' => $this->getAuditLogs(),
            'log_filters' => $this->getLogFilters()
        ];

        $this->render('modules/monitoring/logs', $data);
    }

    /**
     * Reports and insights
     */
    public function reports() {
        $this->requirePermission('monitoring.reports.view');

        $data = [
            'title' => 'Monitoring Reports',
            'system_reports' => $this->getSystemReports(),
            'performance_reports' => $this->getPerformanceReports(),
            'security_reports' => $this->getSecurityReports(),
            'compliance_reports' => $this->getComplianceReports(),
            'custom_reports' => $this->getCustomReports(),
            'scheduled_reports' => $this->getScheduledReports()
        ];

        $this->render('modules/monitoring/reports', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getSystemOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'healthy' THEN 1 END) as healthy_components,
                COUNT(CASE WHEN status = 'warning' THEN 1 END) as warning_components,
                COUNT(CASE WHEN status = 'critical' THEN 1 END) as critical_components,
                COUNT(CASE WHEN status = 'offline' THEN 1 END) as offline_components,
                AVG(uptime_percentage) as avg_uptime,
                COUNT(CASE WHEN last_check >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 END) as recently_checked,
                MAX(last_check) as last_system_check
            FROM system_components
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPerformanceMetrics() {
        return $this->db->querySingle("
            SELECT
                AVG(response_time_ms) as avg_response_time,
                MAX(response_time_ms) as max_response_time,
                MIN(response_time_ms) as min_response_time,
                COUNT(CASE WHEN response_time_ms > 5000 THEN 1 END) as slow_requests,
                COUNT(CASE WHEN response_time_ms > 30000 THEN 1 END) as very_slow_requests,
                AVG(throughput_per_minute) as avg_throughput,
                MAX(throughput_per_minute) as peak_throughput,
                COUNT(CASE WHEN error_rate > 5 THEN 1 END) as high_error_endpoints
            FROM performance_metrics
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);
    }

    private function getAnalyticsOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT user_id) as active_users_today,
                SUM(page_views) as total_page_views,
                SUM(session_duration) as total_session_time,
                AVG(session_duration) as avg_session_duration,
                COUNT(DISTINCT feature_used) as features_used,
                SUM(conversions) as total_conversions,
                AVG(conversion_rate) as avg_conversion_rate,
                MAX(last_activity) as last_user_activity
            FROM analytics_data
            WHERE company_id = ? AND activity_date = CURDATE()
        ", [$this->user['company_id']]);
    }

    private function getAlertsOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_alerts,
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_alerts,
                COUNT(CASE WHEN severity = 'warning' THEN 1 END) as warning_alerts,
                COUNT(CASE WHEN severity = 'info' THEN 1 END) as info_alerts,
                COUNT(CASE WHEN acknowledged = false THEN 1 END) as unacknowledged_alerts,
                MAX(created_at) as last_alert_time,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_acknowledgment_time
            FROM alerts
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getHealthStatus() {
        return $this->db->query("
            SELECT
                component_name,
                status,
                health_score,
                last_check,
                response_time_ms,
                error_message,
                TIMESTAMPDIFF(MINUTE, last_check, NOW()) as minutes_since_check
            FROM system_components
            WHERE company_id = ?
            ORDER BY
                CASE
                    WHEN status = 'critical' THEN 1
                    WHEN status = 'warning' THEN 2
                    WHEN status = 'healthy' THEN 3
                    ELSE 4
                END,
                last_check DESC
        ", [$this->user['company_id']]);
    }

    private function getRecentActivity() {
        return $this->db->query("
            SELECT
                ra.*,
                ra.activity_type,
                ra.description,
                ra.severity,
                ra.created_at,
                u.first_name,
                u.last_name,
                TIMESTAMPDIFF(MINUTE, ra.created_at, NOW()) as minutes_ago
            FROM recent_activity ra
            LEFT JOIN users u ON ra.user_id = u.id
            WHERE ra.company_id = ?
            ORDER BY ra.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getServerMetrics() {
        return $this->db->query("
            SELECT
                sm.*,
                sm.metric_name,
                sm.metric_value,
                sm.unit,
                sm.collection_time,
                sm.server_name,
                TIMESTAMPDIFF(MINUTE, sm.collection_time, NOW()) as age_minutes
            FROM server_metrics sm
            WHERE sm.company_id = ?
            ORDER BY sm.collection_time DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getDatabaseMetrics() {
        return $this->db->querySingle("
            SELECT
                AVG(query_time_ms) as avg_query_time,
                MAX(query_time_ms) as max_query_time,
                SUM(queries_per_second) as total_qps,
                AVG(queries_per_second) as avg_qps,
                MAX(queries_per_second) as peak_qps,
                AVG(connection_count) as avg_connections,
                MAX(connection_count) as max_connections,
                AVG(cache_hit_ratio) as cache_hit_ratio,
                COUNT(CASE WHEN slow_queries > 0 THEN 1 END) as slow_query_instances
            FROM database_metrics
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);
    }

    private function getApplicationMetrics() {
        return $this->db->query("
            SELECT
                am.*,
                am.endpoint,
                am.method,
                am.response_time_ms,
                am.status_code,
                am.request_count,
                am.error_count,
                am.created_at
            FROM application_metrics am
            WHERE am.company_id = ?
            ORDER BY am.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getResourceUsage() {
        return $this->db->querySingle("
            SELECT
                AVG(cpu_usage_percent) as avg_cpu_usage,
                MAX(cpu_usage_percent) as max_cpu_usage,
                AVG(memory_usage_percent) as avg_memory_usage,
                MAX(memory_usage_percent) as max_memory_usage,
                AVG(disk_usage_percent) as avg_disk_usage,
                MAX(disk_usage_percent) as max_disk_usage,
                AVG(network_in_mbps) as avg_network_in,
                AVG(network_out_mbps) as avg_network_out,
                MAX(network_in_mbps) as peak_network_in,
                MAX(network_out_mbps) as peak_network_out
            FROM resource_usage
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);
    }

    private function getSystemLogs() {
        return $this->db->query("
            SELECT
                sl.*,
                sl.log_level,
                sl.message,
                sl.source,
                sl.created_at,
                u.first_name,
                u.last_name,
                TIMESTAMPDIFF(MINUTE, sl.created_at, NOW()) as minutes_ago
            FROM system_logs sl
            LEFT JOIN users u ON sl.user_id = u.id
            WHERE sl.company_id = ?
            ORDER BY sl.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getUptimeHistory() {
        return $this->db->query("
            SELECT
                DATE(created_at) as date,
                AVG(uptime_percentage) as uptime_percentage,
                COUNT(CASE WHEN status = 'up' THEN 1 END) as uptime_checks,
                COUNT(CASE WHEN status = 'down' THEN 1 END) as downtime_checks,
                MIN(created_at) as first_check,
                MAX(created_at) as last_check
            FROM uptime_checks
            WHERE company_id = ?
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ", [$this->user['company_id']]);
    }

    private function getResponseTimes() {
        return $this->db->query("
            SELECT
                endpoint,
                method,
                AVG(response_time_ms) as avg_response_time,
                MIN(response_time_ms) as min_response_time,
                MAX(response_time_ms) as max_response_time,
                PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY response_time_ms) as p95_response_time,
                PERCENTILE_CONT(0.99) WITHIN GROUP (ORDER BY response_time_ms) as p99_response_time,
                COUNT(*) as request_count
            FROM performance_metrics
            WHERE company_id = ? AND created_at >= ?
            GROUP BY endpoint, method
            ORDER BY avg_response_time DESC
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);
    }

    private function getThroughputMetrics() {
        return $this->db->querySingle("
            SELECT
                SUM(requests_per_minute) as total_rpm,
                AVG(requests_per_minute) as avg_rpm,
                MAX(requests_per_minute) as peak_rpm,
                SUM(bytes_transferred_mb) as total_bandwidth,
                AVG(bytes_transferred_mb) as avg_bandwidth,
                MAX(bytes_transferred_mb) as peak_bandwidth,
                COUNT(DISTINCT endpoint) as active_endpoints
            FROM throughput_metrics
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);
    }

    private function getErrorRates() {
        return $this->db->query("
            SELECT
                endpoint,
                method,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count,
                COUNT(*) as total_count,
                ROUND(
                    (COUNT(CASE WHEN status_code >= 400 THEN 1 END) / NULLIF(COUNT(*), 0)) * 100, 2
                ) as error_rate,
                COUNT(CASE WHEN status_code >= 500 THEN 1 END) as server_error_count,
                ROUND(
                    (COUNT(CASE WHEN status_code >= 500 THEN 1 END) / NULLIF(COUNT(*), 0)) * 100, 2
                ) as server_error_rate
            FROM performance_metrics
            WHERE company_id = ? AND created_at >= ?
            GROUP BY endpoint, method
            HAVING error_count > 0
            ORDER BY error_rate DESC
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);
    }

    private function getMemoryUsage() {
        return $this->db->query("
            SELECT
                created_at,
                memory_used_mb,
                memory_total_mb,
                ROUND((memory_used_mb / memory_total_mb) * 100, 2) as memory_usage_percent,
                memory_peak_mb,
                swap_used_mb,
                swap_total_mb
            FROM memory_metrics
            WHERE company_id = ?
            ORDER BY created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getCpuUsage() {
        return $this->db->query("
            SELECT
                created_at,
                cpu_usage_percent,
                cpu_user_percent,
                cpu_system_percent,
                cpu_idle_percent,
                load_average_1m,
                load_average_5m,
                load_average_15m
            FROM cpu_metrics
            WHERE company_id = ?
            ORDER BY created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getBottlenecks() {
        return $this->db->query("
            SELECT
                b.*,
                b.bottleneck_type,
                b.resource_name,
                b.severity,
                b.description,
                b.impact_score,
                b.detected_at,
                b.resolved_at,
                TIMESTAMPDIFF(MINUTE, b.detected_at, COALESCE(b.resolved_at, NOW())) as duration_minutes
            FROM bottlenecks b
            WHERE b.company_id = ?
            ORDER BY b.severity DESC, b.detected_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceTrends() {
        return $this->db->query("
            SELECT
                DATE(created_at) as date,
                AVG(response_time_ms) as avg_response_time,
                AVG(throughput_per_minute) as avg_throughput,
                AVG(error_rate) as avg_error_rate,
                AVG(cpu_usage_percent) as avg_cpu_usage,
                AVG(memory_usage_percent) as avg_memory_usage
            FROM performance_trends
            WHERE company_id = ?
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ", [$this->user['company_id']]);
    }

    private function getUserAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT user_id) as total_users,
                COUNT(DISTINCT CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN user_id END) as active_users_30d,
                COUNT(DISTINCT CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN user_id END) as active_users_7d,
                COUNT(DISTINCT CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN user_id END) as active_users_1d,
                AVG(session_duration) as avg_session_duration,
                SUM(page_views) as total_page_views,
                AVG(page_views) as avg_page_views_per_user,
                COUNT(DISTINCT CASE WHEN user_type = 'new' THEN user_id END) as new_users,
                COUNT(DISTINCT CASE WHEN user_type = 'returning' THEN user_id END) as returning_users
            FROM user_analytics
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBusinessAnalytics() {
        return $this->db->querySingle("
            SELECT
                SUM(revenue) as total_revenue,
                AVG(revenue) as avg_revenue_per_user,
                COUNT(DISTINCT orders) as total_orders,
                AVG(order_value) as avg_order_value,
                SUM(conversions) as total_conversions,
                AVG(conversion_rate) as avg_conversion_rate,
                COUNT(DISTINCT products_viewed) as products_viewed,
                COUNT(DISTINCT products_purchased) as products_purchased,
                AVG(customer_lifetime_value) as avg_clv
            FROM business_analytics
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getSystemAnalytics() {
        return $this->db->querySingle("
            SELECT
                AVG(uptime_percentage) as avg_uptime,
                AVG(response_time_ms) as avg_response_time,
                AVG(error_rate) as avg_error_rate,
                AVG(throughput_per_minute) as avg_throughput,
                COUNT(DISTINCT alerts) as total_alerts,
                COUNT(DISTINCT incidents) as total_incidents,
                AVG(incident_resolution_time) as avg_resolution_time,
                SUM(cost_savings) as total_cost_savings
            FROM system_analytics
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getDataVisualizations() {
        return [
            'charts' => [
                'line_chart' => 'Line Chart',
                'bar_chart' => 'Bar Chart',
                'pie_chart' => 'Pie Chart',
                'area_chart' => 'Area Chart',
                'scatter_plot' => 'Scatter Plot'
            ],
            'metrics' => [
                'kpi_cards' => 'KPI Cards',
                'gauges' => 'Gauges',
                'progress_bars' => 'Progress Bars',
                'heatmaps' => 'Heatmaps'
            ],
            'advanced' => [
                'funnel_chart' => 'Funnel Chart',
                'cohort_analysis' => 'Cohort Analysis',
                'sankey_diagram' => 'Sankey Diagram',
                'treemap' => 'Treemap'
            ]
        ];
    }

    private function getAnalyticsSettings() {
        return $this->db->querySingle("
            SELECT * FROM analytics_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getActiveAlerts() {
        return $this->db->query("
            SELECT
                a.*,
                a.alert_name,
                a.severity,
                a.status,
                a.description,
                a.created_at,
                a.acknowledged_at,
                u.first_name,
                u.last_name,
                TIMESTAMPDIFF(MINUTE, a.created_at, NOW()) as age_minutes
            FROM alerts a
            LEFT JOIN users u ON a.acknowledged_by = u.id
            WHERE a.company_id = ? AND a.status = 'active'
            ORDER BY
                CASE
                    WHEN a.severity = 'critical' THEN 1
                    WHEN a.severity = 'warning' THEN 2
                    WHEN a.severity = 'info' THEN 3
                    ELSE 4
                END,
                a.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAlertHistory() {
        return $this->db->query("
            SELECT
                a.*,
                a.alert_name,
                a.severity,
                a.status,
                a.description,
                a.created_at,
                a.resolved_at,
                u1.first_name as acknowledged_by_name,
                u2.first_name as resolved_by_name,
                TIMESTAMPDIFF(MINUTE, a.created_at, COALESCE(a.resolved_at, NOW())) as duration_minutes
            FROM alerts a
            LEFT JOIN users u1 ON a.acknowledged_by = u1.id
            LEFT JOIN users u2 ON a.resolved_by = u2.id
            WHERE a.company_id = ?
            ORDER BY a.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getAlertRules() {
        return $this->db->query("
            SELECT
                ar.*,
                ar.rule_name,
                ar.metric_name,
                ar.condition,
                ar.threshold,
                ar.severity,
                ar.is_active,
                COUNT(a.id) as trigger_count,
                MAX(a.created_at) as last_triggered,
                ar.last_updated
            FROM alert_rules ar
            LEFT JOIN alerts a ON ar.id = a.rule_id
            WHERE ar.company_id = ?
            GROUP BY ar.id
            ORDER BY ar.is_active DESC, ar.severity ASC
        ", [$this->user['company_id']]);
    }

    private function getAlertChannels() {
        return [
            'email' => [
                'name' => 'Email',
                'description' => 'Send alerts via email',
                'reliability' => 'High',
                'cost' => 'Low',
                'features' => ['HTML content', 'attachments', 'escalation']
            ],
            'sms' => [
                'name' => 'SMS',
                'description' => 'Send alerts via text message',
                'reliability' => 'High',
                'cost' => 'Medium',
                'features' => ['Instant delivery', 'high priority']
            ],
            'slack' => [
                'name' => 'Slack',
                'description' => 'Send alerts to Slack channels',
                'reliability' => 'High',
                'cost' => 'Low',
                'features' => ['Real-time', 'team collaboration']
            ],
            'webhook' => [
                'name' => 'Webhook',
                'description' => 'Send alerts via HTTP webhooks',
                'reliability' => 'High',
                'cost' => 'Free',
                'features' => ['Custom integration', 'programmatic handling']
            ]
        ];
    }

    private function getAlertTemplates() {
        return $this->db->query("
            SELECT
                at.*,
                at.template_name,
                at.severity,
                at.subject_template,
                at.body_template,
                COUNT(a.id) as usage_count,
                MAX(a.created_at) as last_used,
                at.last_updated
            FROM alert_templates at
            LEFT JOIN alerts a ON at.id = a.template_id
            WHERE at.company_id = ?
            GROUP BY at.id
            ORDER BY at.severity ASC, at.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getAlertAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_alerts,
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_alerts,
                COUNT(CASE WHEN severity = 'warning' THEN 1 END) as warning_alerts,
                COUNT(CASE WHEN severity = 'info' THEN 1 END) as info_alerts,
                COUNT(CASE WHEN acknowledged = true THEN 1 END) as acknowledged_alerts,
                COUNT(CASE WHEN resolved = true THEN 1 END) as resolved_alerts,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) as avg_ack_time,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_resolution_time,
                MAX(created_at) as last_alert
            FROM alerts
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getApplicationLogs() {
        return $this->db->query("
            SELECT
                al.*,
                al.log_level,
                al.message,
                al.endpoint,
                al.user_id,
                al.ip_address,
                al.created_at,
                u.first_name,
                u.last_name
            FROM application_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.company_id = ?
            ORDER BY al.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getSecurityLogs() {
        return $this->db->query("
            SELECT
                sl.*,
                sl.event_type,
                sl.severity,
                sl.description,
                sl.user_id,
                sl.ip_address,
                sl.user_agent,
                sl.created_at,
                u.first_name,
                u.last_name
            FROM security_logs sl
            LEFT JOIN users u ON sl.user_id = u.id
            WHERE sl.company_id = ?
            ORDER BY sl.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getErrorLogs() {
        return $this->db->query("
            SELECT
                el.*,
                el.error_type,
                el.error_message,
                el.stack_trace,
                el.endpoint,
                el.user_id,
                el.created_at,
                u.first_name,
                u.last_name
            FROM error_logs el
            LEFT JOIN users u ON el.user_id = u.id
            WHERE el.company_id = ?
            ORDER BY el.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getAccessLogs() {
        return $this->db->query("
            SELECT
                al.*,
                al.method,
                al.endpoint,
                al.status_code,
                al.response_time_ms,
                al.user_id,
                al.ip_address,
                al.user_agent,
                al.created_at,
                u.first_name,
                u.last_name
            FROM access_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.company_id = ?
            ORDER BY al.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getAuditLogs() {
        return $this->db->query("
            SELECT
                al.*,
                al.action,
                al.resource_type,
                al.resource_id,
                al.old_values,
                al.new_values,
                al.user_id,
                al.ip_address,
                al.created_at,
                u.first_name,
                u.last_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.company_id = ?
            ORDER BY al.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getLogFilters() {
        return [
            'levels' => ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'],
            'sources' => ['application', 'security', 'database', 'system', 'network'],
            'time_ranges' => [
                '1h' => 'Last Hour',
                '24h' => 'Last 24 Hours',
                '7d' => 'Last 7 Days',
                '30d' => 'Last 30 Days',
                'custom' => 'Custom Range'
            ],
            'users' => $this->db->query("
                SELECT DISTINCT u.id, u.first_name, u.last_name
                FROM users u
                JOIN system_logs sl ON u.id = sl.user_id
                WHERE u.company_id = ?
                ORDER BY u.first_name, u.last_name
            ", [$this->user['company_id']])
        ];
    }

    private function getSystemReports() {
        return $this->db->query("
            SELECT
                sr.*,
                sr.report_name,
                sr.report_type,
                sr.generated_at,
                sr.file_size_mb,
                sr.download_count,
                sr.expires_at,
                u.first_name,
                u.last_name
            FROM system_reports sr
            LEFT JOIN users u ON sr.generated_by = u.id
            WHERE sr.company_id = ?
            ORDER BY sr.generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceReports() {
        return $this->db->query("
            SELECT
                pr.*,
                pr.report_name,
                pr.time_period,
                pr.avg_response_time,
                pr.peak_response_time,
                pr.total_requests,
                pr.error_rate,
                pr.generated_at
            FROM performance_reports pr
            WHERE pr.company_id = ?
            ORDER BY pr.generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSecurityReports() {
        return $this->db->query("
            SELECT
                sr.*,
                sr.report_name,
                sr.incident_count,
                sr.threat_level,
                sr.vulnerabilities_found,
                sr.recommendations,
                sr.generated_at
            FROM security_reports sr
            WHERE sr.company_id = ?
            ORDER BY sr.generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceReports() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.report_name,
                cr.compliance_standard,
                cr.compliance_score,
                cr.violations_count,
                cr.remediation_steps,
                cr.generated_at
            FROM compliance_reports cr
            WHERE cr.company_id = ?
            ORDER BY cr.generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getScheduledReports() {
        return $this->db->query("
            SELECT
                sr.*,
                sr.report_name,
                sr.schedule_type,
                sr.next_run,
                sr.last_run,
                sr.is_active,
                sr.recipient_emails,
                TIMESTAMPDIFF(DAY, NOW(), sr.next_run) as days_until_next
            FROM scheduled_reports sr
            WHERE sr.company_id = ?
            ORDER BY sr.next_run ASC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function getMetrics() {
        $metricType = $_GET['type'] ?? 'system';
        $timeRange = $_GET['range'] ?? '1h';

        $timeMap = [
            '1h' => '-1 hour',
            '24h' => '-24 hours',
            '7d' => '-7 days',
            '30d' => '-30 days'
        ];

        $startTime = date('Y-m-d H:i:s', strtotime($timeMap[$timeRange] ?? '-1 hour'));

        switch ($metricType) {
            case 'system':
                $metrics = $this->getServerMetrics();
                break;
            case 'performance':
                $metrics = $this->getResponseTimes();
                break;
            case 'database':
                $metrics = $this->getDatabaseMetrics();
                break;
            case 'application':
                $metrics = $this->getApplicationMetrics();
                break;
            default:
                $metrics = [];
        }

        $this->jsonResponse([
            'success' => true,
            'metrics' => $metrics,
            'time_range' => $timeRange,
            'start_time' => $startTime
        ]);
    }

    public function acknowledgeAlert() {
        $this->requirePermission('monitoring.alerts.acknowledge');

        $data = $this->validateRequest([
            'alert_id' => 'required|integer'
        ]);

        try {
            $this->db->update('alerts', [
                'acknowledged' => true,
                'acknowledged_by' => $this->user['id'],
                'acknowledged_at' => date('Y-m-d H:i:s')
            ], 'id = ? AND company_id = ?', [
                $data['alert_id'],
                $this->user['company_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Alert acknowledged successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resolveAlert() {
        $this->requirePermission('monitoring.alerts.resolve');

        $data = $this->validateRequest([
            'alert_id' => 'required|integer',
            'resolution_notes' => 'string'
        ]);

        try {
            $this->db->update('alerts', [
                'status' => 'resolved',
                'resolved_by' => $this->user['id'],
                'resolved_at' => date('Y-m-d H:i:s'),
                'resolution_notes' => $data['resolution_notes'] ?? ''
            ], 'id = ? AND company_id = ?', [
                $data['alert_id'],
                $this->user['company_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Alert resolved successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createAlertRule() {
        $this->requirePermission('monitoring.alerts.create');

        $data = $this->validateRequest([
            'rule_name' => 'required|string',
            'metric_name' => 'required|string',
            'condition' => 'required|string',
            'threshold' => 'required|numeric',
            'severity' => 'required|string',
            'description' => 'string'
        ]);

        try {
            $ruleId = $this->db->insert('alert_rules', [
                'company_id' => $this->user['company_id'],
                'rule_name' => $data['rule_name'],
                'metric_name' => $data['metric_name'],
                'condition' => $data['condition'],
                'threshold' => $data['threshold'],
                'severity' => $data['severity'],
                'description' => $data['description'] ?? '',
                'is_active' => true,
                'created_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'rule_id' => $ruleId,
                'message' => 'Alert rule created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateReport() {
        $this->requirePermission('monitoring.reports.generate');

        $data = $this->validateRequest([
            'report_type' => 'required|string',
            'time_period' => 'required|string',
            'format' => 'required|string',
            'recipients' => 'array'
        ]);

        try {
            // Generate report based on type
            $reportData = $this->generateReportData($data['report_type'], $data['time_period']);

            // Create report record
            $reportId = $this->db->insert('system_reports', [
                'company_id' => $this->user['company_id'],
                'report_name' => ucfirst($data['report_type']) . ' Report - ' . date('Y-m-d'),
                'report_type' => $data['report_type'],
                'time_period' => $data['time_period'],
                'format' => $data['format'],
                'file_size_mb' => strlen(json_encode($reportData)) / 1024 / 1024,
                'generated_by' => $this->user['id'],
                'generated_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
            ]);

            // Send to recipients if specified
            if (!empty($data['recipients'])) {
                $this->sendReportToRecipients($reportId, $data['recipients'], $reportData);
            }

            $this->jsonResponse([
                'success' => true,
                'report_id' => $reportId,
                'message' => 'Report generated successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateReportData($reportType, $timePeriod) {
        // Implementation for generating different types of reports
        switch ($reportType) {
            case 'system':
                return $this->getSystemOverview();
            case 'performance':
                return $this->getPerformanceMetrics();
            case 'security':
                return $this->getSecurityReports();
            default:
                return [];
        }
    }

    private function sendReportToRecipients($reportId, $recipients, $reportData) {
        // Implementation for sending reports to recipients
        // This would integrate with email system
    }

    public function exportLogs() {
        $this->requirePermission('monitoring.logs.export');

        $data = $this->validateRequest([
            'log_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'filters' => 'array',
            'format' => 'required|string'
        ]);

        try {
            // Get filtered logs
            $logs = $this->getFilteredLogs($data);

            // Export in requested format
            $exportData = $this->formatExportData($logs, $data['format']);

            // Create export record
            $exportId = $this->db->insert('log_exports', [
                'company_id' => $this->user['company_id'],
                'log_type' => $data['log_type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'filters' => json_encode($data['filters'] ?? []),
                'format' => $data['format'],
                'record_count' => count($logs),
                'file_size_mb' => strlen($exportData) / 1024 / 1024,
                'exported_by' => $this->user['id'],
                'exported_at' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'export_id' => $exportId,
                'data' => $exportData,
                'message' => 'Logs exported successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getFilteredLogs($data) {
        // Implementation for filtering logs based on criteria
        // This would query the appropriate log table based on log_type
        return [];
    }

    private function formatExportData($logs, $format) {
        // Implementation for formatting export data
        switch ($format) {
            case 'json':
                return json_encode($logs);
            case 'csv':
                return $this->arrayToCsv($logs);
            case 'xml':
                return $this->arrayToXml($logs);
            default:
                return json_encode($logs);
        }
    }

    private function arrayToCsv($data) {
        // Implementation for converting array to CSV
        return '';
    }

    private function arrayToXml($data) {
        // Implementation for converting array to XML
        return '';
    }
}
?>
