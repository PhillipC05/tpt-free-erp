<?php
/**
 * TPT Free ERP - Reporting Module
 * Complete business intelligence, custom reports, and data visualization system
 */

class Reporting extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main reporting dashboard
     */
    public function index() {
        $this->requirePermission('reporting.view');

        $data = [
            'title' => 'Business Intelligence & Reporting',
            'report_overview' => $this->getReportOverview(),
            'recent_reports' => $this->getRecentReports(),
            'popular_dashboards' => $this->getPopularDashboards(),
            'scheduled_reports' => $this->getScheduledReports(),
            'data_sources' => $this->getDataSources(),
            'report_categories' => $this->getReportCategories(),
            'ai_insights' => $this->getAIInsights(),
            'report_analytics' => $this->getReportAnalytics()
        ];

        $this->render('modules/reporting/dashboard', $data);
    }

    /**
     * Custom report builder
     */
    public function reportBuilder() {
        $this->requirePermission('reporting.builder.view');

        $data = [
            'title' => 'Report Builder',
            'data_sources' => $this->getDataSources(),
            'report_templates' => $this->getReportTemplates(),
            'field_mappings' => $this->getFieldMappings(),
            'filter_options' => $this->getFilterOptions(),
            'aggregation_functions' => $this->getAggregationFunctions(),
            'chart_types' => $this->getChartTypes(),
            'export_formats' => $this->getExportFormats(),
            'saved_queries' => $this->getSavedQueries()
        ];

        $this->render('modules/reporting/report_builder', $data);
    }

    /**
     * Dashboard creation and management
     */
    public function dashboards() {
        $this->requirePermission('reporting.dashboards.view');

        $data = [
            'title' => 'Dashboard Management',
            'user_dashboards' => $this->getUserDashboards(),
            'shared_dashboards' => $this->getSharedDashboards(),
            'dashboard_templates' => $this->getDashboardTemplates(),
            'widget_library' => $this->getWidgetLibrary(),
            'dashboard_themes' => $this->getDashboardThemes(),
            'dashboard_permissions' => $this->getDashboardPermissions(),
            'dashboard_analytics' => $this->getDashboardAnalytics(),
            'real_time_data' => $this->getRealTimeData()
        ];

        $this->render('modules/reporting/dashboards', $data);
    }

    /**
     * Data visualization
     */
    public function dataVisualization() {
        $this->requirePermission('reporting.visualization.view');

        $data = [
            'title' => 'Data Visualization',
            'chart_library' => $this->getChartLibrary(),
            'visualization_templates' => $this->getVisualizationTemplates(),
            'color_palettes' => $this->getColorPalettes(),
            'data_sets' => $this->getDataSets(),
            'interactive_features' => $this->getInteractiveFeatures(),
            'drill_down_options' => $this->getDrillDownOptions(),
            'visualization_analytics' => $this->getVisualizationAnalytics(),
            'export_options' => $this->getVisualizationExportOptions()
        ];

        $this->render('modules/reporting/data_visualization', $data);
    }

    /**
     * AI-powered insights
     */
    public function aiInsights() {
        $this->requirePermission('reporting.ai.view');

        $data = [
            'title' => 'AI-Powered Insights',
            'trend_analysis' => $this->getTrendAnalysis(),
            'anomaly_detection' => $this->getAnomalyDetection(),
            'predictive_insights' => $this->getPredictiveInsights(),
            'correlation_analysis' => $this->getCorrelationAnalysis(),
            'pattern_recognition' => $this->getPatternRecognition(),
            'recommendations' => $this->getAIRecommendations(),
            'insight_history' => $this->getInsightHistory(),
            'ai_model_performance' => $this->getAIModelPerformance()
        ];

        $this->render('modules/reporting/ai_insights', $data);
    }

    /**
     * Report scheduling and automation
     */
    public function reportScheduler() {
        $this->requirePermission('reporting.scheduler.view');

        $data = [
            'title' => 'Report Scheduler',
            'scheduled_reports' => $this->getScheduledReports(),
            'schedule_templates' => $this->getScheduleTemplates(),
            'delivery_methods' => $this->getDeliveryMethods(),
            'recipient_management' => $this->getRecipientManagement(),
            'schedule_history' => $this->getScheduleHistory(),
            'automation_rules' => $this->getAutomationRules(),
            'schedule_analytics' => $this->getScheduleAnalytics(),
            'failure_notifications' => $this->getFailureNotifications()
        ];

        $this->render('modules/reporting/report_scheduler', $data);
    }

    /**
     * Report sharing and collaboration
     */
    public function reportSharing() {
        $this->requirePermission('reporting.sharing.view');

        $data = [
            'title' => 'Report Sharing & Collaboration',
            'shared_reports' => $this->getSharedReports(),
            'collaboration_sessions' => $this->getCollaborationSessions(),
            'access_permissions' => $this->getAccessPermissions(),
            'version_control' => $this->getVersionControl(),
            'comment_system' => $this->getCommentSystem(),
            'sharing_analytics' => $this->getSharingAnalytics(),
            'collaboration_templates' => $this->getCollaborationTemplates(),
            'audit_trail' => $this->getAuditTrail()
        ];

        $this->render('modules/reporting/report_sharing', $data);
    }

    /**
     * Report analytics and performance
     */
    public function reportAnalytics() {
        $this->requirePermission('reporting.analytics.view');

        $data = [
            'title' => 'Report Analytics',
            'usage_analytics' => $this->getUsageAnalytics(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'user_engagement' => $this->getUserEngagement(),
            'report_effectiveness' => $this->getReportEffectiveness(),
            'data_quality_metrics' => $this->getDataQualityMetrics(),
            'system_performance' => $this->getSystemPerformance(),
            'roi_analysis' => $this->getROIAnalysis(),
            'benchmarking' => $this->getBenchmarking()
        ];

        $this->render('modules/reporting/report_analytics', $data);
    }

    /**
     * AI-powered report optimization
     */
    public function reportOptimization() {
        $this->requirePermission('reporting.optimization.view');

        $reportId = $_GET['report_id'] ?? null;
        $analysis = null;

        if ($reportId) {
            $optimizer = new ReportOptimizer();
            $analysis = $optimizer->analyzeReport($reportId);
        }

        $data = [
            'title' => 'Report Optimization',
            'report_id' => $reportId,
            'analysis' => $analysis,
            'optimization_history' => $this->getOptimizationHistory(),
            'ai_suggestions' => $this->getAISuggestions(),
            'performance_insights' => $this->getPerformanceInsights()
        ];

        $this->render('modules/reporting/report_optimization', $data);
    }

    /**
     * BI tool integration management
     */
    public function biIntegration() {
        $this->requirePermission('reporting.bi.view');

        $biIntegration = new BIToolIntegration();

        $data = [
            'title' => 'BI Tool Integration',
            'supported_tools' => ['tableau', 'powerbi', 'qlik', 'looker'],
            'configured_tools' => $this->getConfiguredBITools(),
            'export_history' => $this->getBIExportHistory(),
            'sync_jobs' => $this->getBISyncJobs(),
            'embedded_dashboards' => $this->getEmbeddedDashboards(),
            'integration_stats' => $this->getBIIntegrationStats()
        ];

        $this->render('modules/reporting/bi_integration', $data);
    }

    /**
     * Configure BI tool
     */
    public function configureBITool() {
        $this->requirePermission('reporting.bi.configure');

        $toolName = $_POST['tool_name'] ?? '';
        $config = $_POST['config'] ?? [];

        try {
            $biIntegration = new BIToolIntegration();
            $result = $biIntegration->configureTool($toolName, $config);

            $this->jsonResponse([
                'success' => true,
                'message' => "BI tool {$toolName} configured successfully",
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Export data to BI tool
     */
    public function exportToBITool() {
        $this->requirePermission('reporting.bi.export');

        $toolName = $_POST['tool_name'] ?? '';
        $dataSource = $_POST['data_source'] ?? '';
        $options = $_POST['options'] ?? [];

        try {
            $biIntegration = new BIToolIntegration();
            $result = $biIntegration->exportToTool($toolName, $dataSource, $options);

            $this->jsonResponse([
                'success' => true,
                'message' => "Data exported to {$toolName} successfully",
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Import data from BI tool
     */
    public function importFromBITool() {
        $this->requirePermission('reporting.bi.import');

        $toolName = $_POST['tool_name'] ?? '';
        $remoteDataSource = $_POST['remote_data_source'] ?? '';
        $options = $_POST['options'] ?? [];

        try {
            $biIntegration = new BIToolIntegration();
            $result = $biIntegration->importFromTool($toolName, $remoteDataSource, $options);

            $this->jsonResponse([
                'success' => true,
                'message' => "Data imported from {$toolName} successfully",
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Create embedded BI dashboard
     */
    public function createEmbeddedDashboard() {
        $this->requirePermission('reporting.bi.embed');

        $toolName = $_POST['tool_name'] ?? '';
        $dashboardConfig = $_POST['dashboard_config'] ?? [];

        try {
            $biIntegration = new BIToolIntegration();
            $result = $biIntegration->createEmbeddedDashboard($toolName, $dashboardConfig);

            $this->jsonResponse([
                'success' => true,
                'message' => "Embedded dashboard created successfully",
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Sync data with BI tool
     */
    public function syncWithBITool() {
        $this->requirePermission('reporting.bi.sync');

        $toolName = $_POST['tool_name'] ?? '';
        $direction = $_POST['direction'] ?? '';
        $dataSource = $_POST['data_source'] ?? '';
        $options = $_POST['options'] ?? [];

        try {
            $biIntegration = new BIToolIntegration();
            $result = $biIntegration->syncData($toolName, $direction, $dataSource, $options);

            $this->jsonResponse([
                'success' => true,
                'message' => "Data sync with {$toolName} completed successfully",
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getReportOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT r.id) as total_reports,
                COUNT(DISTINCT d.id) as total_dashboards,
                COUNT(DISTINCT sr.id) as scheduled_reports,
                COUNT(DISTINCT ds.id) as data_sources,
                SUM(r.view_count) as total_views,
                AVG(r.execution_time) as avg_execution_time,
                COUNT(CASE WHEN r.last_run >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as active_reports,
                COUNT(CASE WHEN r.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_reports
            FROM reports r
            LEFT JOIN dashboards d ON d.company_id = r.company_id
            LEFT JOIN scheduled_reports sr ON sr.company_id = r.company_id
            LEFT JOIN data_sources ds ON ds.company_id = r.company_id
            WHERE r.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRecentReports() {
        return $this->db->query("
            SELECT
                r.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                r.report_type,
                r.last_run,
                r.execution_time,
                r.view_count,
                TIMESTAMPDIFF(MINUTE, r.last_run, NOW()) as minutes_since_run
            FROM reports r
            LEFT JOIN users u ON r.created_by = u.id
            WHERE r.company_id = ?
            ORDER BY r.last_run DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getPopularDashboards() {
        return $this->db->query("
            SELECT
                d.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(dv.id) as view_count,
                AVG(dv.session_duration) as avg_session_duration,
                MAX(dv.viewed_at) as last_viewed
            FROM dashboards d
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN dashboard_views dv ON d.id = dv.dashboard_id
            WHERE d.company_id = ?
            GROUP BY d.id, u.first_name, u.last_name
            ORDER BY view_count DESC
            LIMIT 5
        ", [$this->user['company_id']]);
    }

    private function getScheduledReports() {
        return $this->db->query("
            SELECT
                sr.*,
                r.report_name,
                sr.schedule_type,
                sr.next_run,
                sr.last_run,
                sr.recipient_count,
                TIMESTAMPDIFF(MINUTE, NOW(), sr.next_run) as minutes_until_next
            FROM scheduled_reports sr
            JOIN reports r ON sr.report_id = r.id
            WHERE sr.company_id = ? AND sr.is_active = true
            ORDER BY sr.next_run ASC
        ", [$this->user['company_id']]);
    }

    private function getDataSources() {
        return $this->db->query("
            SELECT
                ds.*,
                ds.source_type,
                ds.connection_status,
                ds.last_sync,
                ds.record_count,
                TIMESTAMPDIFF(MINUTE, ds.last_sync, NOW()) as minutes_since_sync
            FROM data_sources ds
            WHERE ds.company_id = ?
            ORDER BY ds.source_type ASC, ds.last_sync DESC
        ", [$this->user['company_id']]);
    }

    private function getReportCategories() {
        return [
            'financial' => 'Financial Reports',
            'operational' => 'Operational Reports',
            'sales' => 'Sales & Marketing',
            'inventory' => 'Inventory & Procurement',
            'hr' => 'Human Resources',
            'manufacturing' => 'Manufacturing',
            'quality' => 'Quality Management',
            'compliance' => 'Compliance & Audit',
            'executive' => 'Executive Dashboards',
            'custom' => 'Custom Reports'
        ];
    }

    private function getAIInsights() {
        return $this->db->query("
            SELECT
                ai.*,
                ai.insight_type,
                ai.confidence_score,
                ai.impact_level,
                ai.generated_at,
                ai.implemented_count
            FROM ai_insights ai
            WHERE ai.company_id = ?
            ORDER BY ai.confidence_score DESC, ai.generated_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getReportAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(r.id) as total_reports,
                AVG(r.execution_time) as avg_execution_time,
                SUM(r.view_count) as total_views,
                COUNT(CASE WHEN r.execution_time > 30 THEN 1 END) as slow_reports,
                COUNT(CASE WHEN r.last_error IS NOT NULL THEN 1 END) as failed_reports,
                AVG(r.data_volume) as avg_data_volume,
                COUNT(DISTINCT r.created_by) as active_creators
            FROM reports r
            WHERE r.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getReportTemplates() {
        return $this->db->query("
            SELECT * FROM report_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, template_name
        ", [$this->user['company_id']]);
    }

    private function getFieldMappings() {
        return $this->db->query("
            SELECT
                fm.*,
                ds.source_name,
                fm.source_field,
                fm.display_name,
                fm.data_type,
                fm.is_filterable,
                fm.is_sortable
            FROM field_mappings fm
            JOIN data_sources ds ON fm.data_source_id = ds.id
            WHERE fm.company_id = ?
            ORDER BY ds.source_name, fm.display_name
        ", [$this->user['company_id']]);
    }

    private function getFilterOptions() {
        return [
            'equals' => 'Equals',
            'not_equals' => 'Not Equals',
            'contains' => 'Contains',
            'not_contains' => 'Does Not Contain',
            'starts_with' => 'Starts With',
            'ends_with' => 'Ends With',
            'greater_than' => 'Greater Than',
            'less_than' => 'Less Than',
            'between' => 'Between',
            'in_list' => 'In List',
            'not_in_list' => 'Not In List',
            'is_null' => 'Is Null',
            'is_not_null' => 'Is Not Null'
        ];
    }

    private function getAggregationFunctions() {
        return [
            'count' => 'Count',
            'sum' => 'Sum',
            'avg' => 'Average',
            'min' => 'Minimum',
            'max' => 'Maximum',
            'median' => 'Median',
            'mode' => 'Mode',
            'stddev' => 'Standard Deviation',
            'variance' => 'Variance',
            'percentile' => 'Percentile'
        ];
    }

    private function getChartTypes() {
        return [
            'bar' => 'Bar Chart',
            'line' => 'Line Chart',
            'pie' => 'Pie Chart',
            'donut' => 'Donut Chart',
            'area' => 'Area Chart',
            'scatter' => 'Scatter Plot',
            'bubble' => 'Bubble Chart',
            'heatmap' => 'Heat Map',
            'treemap' => 'Tree Map',
            'gauge' => 'Gauge Chart',
            'funnel' => 'Funnel Chart',
            'waterfall' => 'Waterfall Chart'
        ];
    }

    private function getExportFormats() {
        return [
            'pdf' => 'PDF Document',
            'excel' => 'Excel Spreadsheet',
            'csv' => 'CSV File',
            'json' => 'JSON Data',
            'xml' => 'XML Data',
            'html' => 'HTML Report',
            'png' => 'PNG Image',
            'svg' => 'SVG Vector',
            'powerpoint' => 'PowerPoint Presentation'
        ];
    }

    private function getSavedQueries() {
        return $this->db->query("
            SELECT
                sq.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                sq.query_name,
                sq.last_executed,
                sq.execution_count
            FROM saved_queries sq
            LEFT JOIN users u ON sq.created_by = u.id
            WHERE sq.company_id = ?
            ORDER BY sq.last_executed DESC
        ", [$this->user['company_id']]);
    }

    private function getUserDashboards() {
        return $this->db->query("
            SELECT
                d.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(dw.id) as widget_count,
                d.last_modified,
                d.is_favorite
            FROM dashboards d
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN dashboard_widgets dw ON d.id = dw.dashboard_id
            WHERE d.company_id = ? AND d.created_by = ?
            GROUP BY d.id, u.first_name, u.last_name
            ORDER BY d.last_modified DESC
        ", [$this->user['company_id'], $this->user['id']]);
    }

    private function getSharedDashboards() {
        return $this->db->query("
            SELECT
                d.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(dw.id) as widget_count,
                dp.permission_level,
                d.last_modified
            FROM dashboards d
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN dashboard_widgets dw ON d.id = dw.dashboard_id
            JOIN dashboard_permissions dp ON d.id = dp.dashboard_id AND dp.user_id = ?
            WHERE d.company_id = ?
            GROUP BY d.id, u.first_name, u.last_name, dp.permission_level
            ORDER BY d.last_modified DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getDashboardTemplates() {
        return $this->db->query("
            SELECT * FROM dashboard_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, template_name
        ", [$this->user['company_id']]);
    }

    private function getWidgetLibrary() {
        return $this->db->query("
            SELECT
                wl.*,
                wl.widget_type,
                wl.category,
                wl.description,
                COUNT(dw.id) as usage_count
            FROM widget_library wl
            LEFT JOIN dashboard_widgets dw ON wl.id = dw.widget_type_id
            WHERE wl.company_id = ?
            GROUP BY wl.id
            ORDER BY wl.category, wl.widget_type
        ", [$this->user['company_id']]);
    }

    private function getDashboardThemes() {
        return $this->db->query("
            SELECT * FROM dashboard_themes
            WHERE company_id = ? AND is_active = true
            ORDER BY theme_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDashboardPermissions() {
        return $this->db->query("
            SELECT
                dp.*,
                u.first_name as user_first,
                u.last_name as user_last,
                d.dashboard_name,
                dp.permission_level,
                dp.granted_at
            FROM dashboard_permissions dp
            JOIN users u ON dp.user_id = u.id
            JOIN dashboards d ON dp.dashboard_id = d.id
            WHERE dp.company_id = ?
            ORDER BY dp.granted_at DESC
        ", [$this->user['company_id']]);
    }

    private function getDashboardAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(d.id) as total_dashboards,
                COUNT(dw.id) as total_widgets,
                AVG(dv.session_duration) as avg_session_duration,
                SUM(dv.view_count) as total_views,
                COUNT(DISTINCT dv.user_id) as unique_users,
                MAX(dv.viewed_at) as last_activity
            FROM dashboards d
            LEFT JOIN dashboard_widgets dw ON d.id = dw.dashboard_id
            LEFT JOIN dashboard_views dv ON d.id = dv.dashboard_id
            WHERE d.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRealTimeData() {
        return $this->db->query("
            SELECT
                rtd.*,
                ds.source_name,
                rtd.metric_name,
                rtd.current_value,
                rtd.previous_value,
                rtd.change_percentage,
                rtd.last_updated
            FROM real_time_data rtd
            JOIN data_sources ds ON rtd.data_source_id = ds.id
            WHERE rtd.company_id = ?
            ORDER BY rtd.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getChartLibrary() {
        return $this->db->query("
            SELECT
                cl.*,
                cl.chart_type,
                cl.category,
                COUNT(cv.id) as usage_count,
                cl.description
            FROM chart_library cl
            LEFT JOIN chart_visualizations cv ON cl.id = cv.chart_type_id
            WHERE cl.company_id = ?
            GROUP BY cl.id
            ORDER BY cl.category, cl.chart_type
        ", [$this->user['company_id']]);
    }

    private function getVisualizationTemplates() {
        return $this->db->query("
            SELECT * FROM visualization_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, template_name
        ", [$this->user['company_id']]);
    }

    private function getColorPalettes() {
        return $this->db->query("
            SELECT * FROM color_palettes
            WHERE company_id = ? AND is_active = true
            ORDER BY palette_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDataSets() {
        return $this->db->query("
            SELECT
                ds.*,
                ds.dataset_name,
                ds.record_count,
                ds.last_refreshed,
                COUNT(dsv.id) as visualization_count
            FROM data_sets ds
            LEFT JOIN data_set_visualizations dsv ON ds.id = dsv.dataset_id
            WHERE ds.company_id = ?
            GROUP BY ds.id
            ORDER BY ds.last_refreshed DESC
        ", [$this->user['company_id']]);
    }

    private function getInteractiveFeatures() {
        return [
            'drill_down' => 'Drill Down',
            'drill_up' => 'Drill Up',
            'filter' => 'Interactive Filters',
            'zoom' => 'Zoom & Pan',
            'tooltip' => 'Rich Tooltips',
            'legend' => 'Interactive Legend',
            'cross_filter' => 'Cross Filtering',
            'brush' => 'Brush Selection',
            'hover' => 'Hover Effects'
        ];
    }

    private function getDrillDownOptions() {
        return $this->db->query("
            SELECT
                ddo.*,
                ddo.dimension_name,
                ddo.hierarchy_level,
                COUNT(ddi.id) as interaction_count
            FROM drill_down_options ddo
            LEFT JOIN drill_down_interactions ddi ON ddo.id = ddi.option_id
            WHERE ddo.company_id = ?
            GROUP BY ddo.id
            ORDER BY ddo.dimension_name, ddo.hierarchy_level
        ", [$this->user['company_id']]);
    }

    private function getVisualizationAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(cv.id) as total_visualizations,
                COUNT(DISTINCT cv.created_by) as active_creators,
                AVG(cv.interaction_count) as avg_interactions,
                SUM(cv.export_count) as total_exports,
                COUNT(CASE WHEN cv.last_interaction >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as active_visualizations
            FROM chart_visualizations cv
            WHERE cv.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getVisualizationExportOptions() {
        return [
            'png' => 'PNG Image',
            'jpg' => 'JPEG Image',
            'svg' => 'SVG Vector',
            'pdf' => 'PDF Document',
            'excel' => 'Excel Data',
            'csv' => 'CSV Data',
            'json' => 'JSON Data'
        ];
    }

    private function getTrendAnalysis() {
        return $this->db->query("
            SELECT
                ta.*,
                ta.metric_name,
                ta.trend_direction,
                ta.trend_strength,
                ta.confidence_level,
                ta.time_period,
                ta.generated_at
            FROM trend_analysis ta
            WHERE ta.company_id = ?
            ORDER BY ta.confidence_level DESC, ta.generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAnomalyDetection() {
        return $this->db->query("
            SELECT
                ad.*,
                ad.metric_name,
                ad.anomaly_type,
                ad.severity_score,
                ad.expected_value,
                ad.actual_value,
                ad.detected_at
            FROM anomaly_detection ad
            WHERE ad.company_id = ?
            ORDER BY ad.severity_score DESC, ad.detected_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPredictiveInsights() {
        return $this->db->query("
            SELECT
                pi.*,
                pi.insight_type,
                pi.prediction_horizon,
                pi.confidence_interval,
                pi.recommended_action,
                pi.potential_impact,
                pi.generated_at
            FROM predictive_insights pi
            WHERE pi.company_id = ?
            ORDER BY pi.potential_impact DESC, pi.generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCorrelationAnalysis() {
        return $this->db->query("
            SELECT
                ca.*,
                ca.variable_1,
                ca.variable_2,
                ca.correlation_coefficient,
                ca.significance_level,
                ca.relationship_type,
                ca.analyzed_at
            FROM correlation_analysis ca
            WHERE ca.company_id = ?
            ORDER BY ABS(ca.correlation_coefficient) DESC, ca.analyzed_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPatternRecognition() {
        return $this->db->query("
            SELECT
                pr.*,
                pr.pattern_type,
                pr.pattern_description,
                pr.occurrences_count,
                pr.confidence_score,
                pr.business_impact,
                pr.discovered_at
            FROM pattern_recognition pr
            WHERE pr.company_id = ?
            ORDER BY pr.confidence_score DESC, pr.discovered_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAIRecommendations() {
        return $this->db->query("
            SELECT
                ar.*,
                ar.recommendation_type,
                ar.priority_level,
                ar.expected_benefit,
                ar.implementation_effort,
                ar.success_probability,
                ar.generated_at
            FROM ai_recommendations ar
            WHERE ar.company_id = ?
            ORDER BY ar.priority_level DESC, ar.expected_benefit DESC
        ", [$this->user['company_id']]);
    }

    private function getInsightHistory() {
        return $this->db->query("
            SELECT
                ih.*,
                ih.insight_type,
                ih.accuracy_rating,
                ih.business_value,
                ih.implementation_status,
                ih.created_at
            FROM insight_history ih
            WHERE ih.company_id = ?
            ORDER BY ih.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAIModelPerformance() {
        return $this->db->query("
            SELECT
                amp.*,
                amp.model_name,
                amp.accuracy_score,
                amp.precision_score,
                amp.recall_score,
                amp.f1_score,
                amp.last_trained
            FROM ai_model_performance amp
            WHERE amp.company_id = ?
            ORDER BY amp.accuracy_score DESC, amp.last_trained DESC
        ", [$this->user['company_id']]);
    }

    private function getScheduleTemplates() {
        return $this->db->query("
            SELECT * FROM schedule_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDeliveryMethods() {
        return [
            'email' => 'Email',
            'ftp' => 'FTP/SFTP',
            'api' => 'API Webhook',
            'sftp' => 'Secure FTP',
            'cloud_storage' => 'Cloud Storage',
            'internal_system' => 'Internal System',
            'print' => 'Print',
            'fax' => 'Fax'
        ];
    }

    private function getRecipientManagement() {
        return $this->db->query("
            SELECT
                rm.*,
                u.first_name as user_first,
                u.last_name as user_last,
                rm.delivery_method,
                rm.is_active,
                rm.last_sent
            FROM recipient_management rm
            LEFT JOIN users u ON rm.user_id = u.id
            WHERE rm.company_id = ?
            ORDER BY rm.delivery_method, u.last_name
        ", [$this->user['company_id']]);
    }

    private function getScheduleHistory() {
        return $this->db->query("
            SELECT
                sh.*,
                sr.schedule_name,
                sh.execution_status,
                sh.execution_time,
                sh.recipient_count,
                sh.error_message
            FROM schedule_history sh
            JOIN scheduled_reports sr ON sh.schedule_id = sr.id
            WHERE sh.company_id = ?
            ORDER BY sh.execution_time DESC
        ", [$this->user['company_id']]);
    }

    private function getAutomationRules() {
        return $this->db->query("
            SELECT
                ar.*,
                ar.rule_name,
                ar.trigger_condition,
                ar.action_type,
                ar.is_active,
                ar.last_triggered
            FROM automation_rules ar
            WHERE ar.company_id = ?
            ORDER BY ar.is_active DESC, ar.last_triggered DESC
        ", [$this->user['company_id']]);
    }

    private function getScheduleAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(sr.id) as total_scheduled,
                COUNT(CASE WHEN sr.is_active = true THEN 1 END) as active_schedules,
                AVG(sh.execution_time) as avg_execution_time,
                COUNT(CASE WHEN sh.execution_status = 'success' THEN 1 END) as successful_executions,
                COUNT(CASE WHEN sh.execution_status = 'failed' THEN 1 END) as failed_executions,
                ROUND((COUNT(CASE WHEN sh.execution_status = 'success' THEN 1 END) / NULLIF(COUNT(sh.id), 0)) * 100, 2) as success_rate
            FROM scheduled_reports sr
            LEFT JOIN schedule_history sh ON sr.id = sh.schedule_id
            WHERE sr.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getFailureNotifications() {
        return $this->db->query("
            SELECT
                fn.*,
                sr.schedule_name,
                fn.failure_reason,
                fn.notification_sent,
                fn.resolution_status,
                fn.occurred_at
            FROM failure_notifications fn
            JOIN scheduled_reports sr ON fn.schedule_id = sr.id
            WHERE fn.company_id = ?
            ORDER BY fn.occurred_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSharedReports() {
        return $this->db->query("
            SELECT
                sr.*,
                r.report_name,
                u.first_name as shared_by_first,
                u.last_name as shared_by_last,
                sr.permission_level,
                sr.shared_at,
                sr.access_count
            FROM shared_reports sr
            JOIN reports r ON sr.report_id = r.id
            LEFT JOIN users u ON sr.shared_by = u.id
            WHERE sr.company_id = ?
            ORDER BY sr.shared_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCollaborationSessions() {
        return $this->db->query("
            SELECT
                cs.*,
                r.report_name,
                cs.session_name,
                cs.participant_count,
                cs.last_activity,
                cs.session_status
            FROM collaboration_sessions cs
            JOIN reports r ON cs.report_id = r.id
            WHERE cs.company_id = ?
            ORDER BY cs.last_activity DESC
        ", [$this->user['company_id']]);
    }

    private function getAccessPermissions() {
        return $this->db->query("
            SELECT
                ap.*,
                r.report_name,
                u.first_name as user_first,
                u.last_name as user_last,
                ap.permission_type,
                ap.granted_at,
                ap.expires_at
            FROM access_permissions ap
            JOIN reports r ON ap.resource_id = r.id
            LEFT JOIN users u ON ap.user_id = u.id
            WHERE ap.company_id = ?
            ORDER BY ap.granted_at DESC
        ", [$this->user['company_id']]);
    }

    private function getVersionControl() {
        return $this->db->query("
            SELECT
                vc.*,
                r.report_name,
                u.first_name as modified_by_first,
                u.last_name as modified_by_last,
                vc.version_number,
                vc.change_description,
                vc.created_at
            FROM version_control vc
            JOIN reports r ON vc.report_id = r.id
            LEFT JOIN users u ON vc.modified_by = u.id
            WHERE vc.company_id = ?
            ORDER BY vc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCommentSystem() {
        return $this->db->query("
            SELECT
                cs.*,
                r.report_name,
                u.first_name as commenter_first,
                u.last_name as commenter_last,
                cs.comment_text,
                cs.comment_type,
                cs.created_at
            FROM comment_system cs
            JOIN reports r ON cs.report_id = r.id
            LEFT JOIN users u ON cs.user_id = u.id
            WHERE cs.company_id = ?
            ORDER BY cs.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSharingAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(sr.id) as total_shared,
                COUNT(DISTINCT sr.report_id) as unique_reports_shared,
                COUNT(DISTINCT sr.shared_with) as unique_recipients,
                AVG(sr.access_count) as avg_access_count,
                SUM(sr.access_count) as total_accesses,
                COUNT(CASE WHEN sr.last_accessed >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as active_shared_reports
            FROM shared_reports sr
            WHERE sr.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCollaborationTemplates() {
        return $this->db->query("
            SELECT * FROM collaboration_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAuditTrail() {
        return $this->db->query("
            SELECT
                at.*,
                r.report_name,
                u.first_name as user_first,
                u.last_name as user_last,
                at.action_type,
                at.action_description,
                at.ip_address,
                at.timestamp
            FROM audit_trail at
            JOIN reports r ON at.report_id = r.id
            LEFT JOIN users u ON at.user_id = u.id
            WHERE at.company_id = ?
            ORDER BY at.timestamp DESC
        ", [$this->user['company_id']]);
    }

    private function getUsageAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(ua.id) as total_sessions,
                COUNT(DISTINCT ua.user_id) as unique_users,
                AVG(ua.session_duration) as avg_session_duration,
                SUM(ua.page_views) as total_page_views,
                AVG(ua.page_views) as avg_page_views,
                COUNT(DISTINCT ua.report_id) as reports_accessed,
                MAX(ua.session_end) as last_activity
            FROM usage_analytics ua
            WHERE ua.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPerformanceMetrics() {
        return $this->db->query("
            SELECT
                pm.*,
                pm.metric_name,
                pm.metric_value,
                pm.target_value,
                ROUND(((pm.metric_value - pm.target_value) / NULLIF(pm.target_value, 0)) * 100, 2) as performance_percentage,
                pm.measured_at
            FROM performance_metrics pm
            WHERE pm.company_id = ?
            ORDER BY pm.measured_at DESC
        ", [$this->user['company_id']]);
    }

    private function getUserEngagement() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT ua.user_id) as engaged_users,
                AVG(ua.session_duration) as avg_session_time,
                COUNT(CASE WHEN ua.session_duration > 300 THEN 1 END) as long_sessions,
                COUNT(CASE WHEN ua.page_views > 10 THEN 1 END) as high_engagement_sessions,
                AVG(ua.return_visits) as avg_return_visits,
                COUNT(CASE WHEN ua.return_visits > 5 THEN 1 END) as frequent_users
            FROM usage_analytics ua
            WHERE ua.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getReportEffectiveness() {
        return $this->db->query("
            SELECT
                re.*,
                r.report_name,
                re.effectiveness_score,
                re.business_value,
                re.user_satisfaction,
                re.usage_frequency,
                re.measured_at
            FROM report_effectiveness re
            JOIN reports r ON re.report_id = r.id
            WHERE re.company_id = ?
            ORDER BY re.effectiveness_score DESC
        ", [$this->user['company_id']]);
    }

    private function getDataQualityMetrics() {
        return $this->db->query("
            SELECT
                dqm.*,
                ds.source_name,
                dqm.completeness_score,
                dqm.accuracy_score,
                dqm.consistency_score,
                dqm.timeliness_score,
                dqm.overall_quality_score
            FROM data_quality_metrics dqm
            JOIN data_sources ds ON dqm.data_source_id = ds.id
            WHERE dqm.company_id = ?
            ORDER BY dqm.overall_quality_score DESC
        ", [$this->user['company_id']]);
    }

    private function getSystemPerformance() {
        return $this->db->querySingle("
            SELECT
                AVG(r.execution_time) as avg_report_execution,
                COUNT(CASE WHEN r.execution_time > 60 THEN 1 END) as slow_reports,
                AVG(dv.load_time) as avg_dashboard_load,
                COUNT(CASE WHEN d.last_error IS NOT NULL THEN 1 END) as failed_dashboards,
                AVG(ds.sync_time) as avg_data_sync,
                COUNT(CASE WHEN ds.connection_status = 'error' THEN 1 END) as failed_connections
            FROM reports r
            LEFT JOIN dashboards d ON d.company_id = r.company_id
            LEFT JOIN dashboard_views dv ON d.id = dv.dashboard_id
            LEFT JOIN data_sources ds ON ds.company_id = r.company_id
            WHERE r.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getROIAnalysis() {
        return $this->db->query("
            SELECT
                roi.*,
                roi.investment_amount,
                roi.benefits_realized,
                roi.roi_percentage,
                roi.payback_period,
                roi.calculated_at
            FROM roi_analysis roi
            WHERE roi.company_id = ?
            ORDER BY roi.roi_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getBenchmarking() {
        return $this->db->query("
            SELECT
                b.*,
                b.metric_name,
                b.company_performance,
                b.industry_average,
                b.top_performer,
                ROUND(((b.company_performance - b.industry_average) / NULLIF(b.industry_average, 0)) * 100, 2) as variance_percentage,
                b.benchmark_period
            FROM benchmarking b
            WHERE b.company_id = ?
            ORDER BY ABS(b.company_performance - b.industry_average) DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    private function generateReport($reportId, $parameters = []) {
        // Implementation for report generation
        $report = $this->db->querySingle("SELECT * FROM reports WHERE id = ? AND company_id = ?", [$reportId, $this->user['company_id']]);

        if (!$report) {
            throw new Exception("Report not found");
        }

        // Execute report query with parameters
        $query = $this->buildReportQuery($report, $parameters);
        $data = $this->db->query($query, $parameters);

        // Update execution statistics
        $this->db->query("
            UPDATE reports
            SET last_run = NOW(),
                execution_time = ?,
                view_count = view_count + 1,
                last_error = NULL
            WHERE id = ?
        ", [microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], $reportId]);

        return $data;
    }

    private function buildReportQuery($report, $parameters) {
        // Implementation for building dynamic report queries
        $baseQuery = $report['query_template'];

        // Replace parameter placeholders
        foreach ($parameters as $key => $value) {
            $baseQuery = str_replace("{{$key}}", $this->db->escape($value), $baseQuery);
        }

        return $baseQuery;
    }

    private function exportReport($reportId, $format, $data) {
        // Implementation for report export in various formats
        switch ($format) {
            case 'pdf':
                return $this->exportToPDF($data);
            case 'excel':
                return $this->exportToExcel($data);
            case 'csv':
                return $this->exportToCSV($data);
            default:
                throw new Exception("Unsupported export format");
        }
    }

    private function exportToPDF($data) {
        // PDF export implementation
        // This would integrate with a PDF library like TCPDF or DomPDF
        return "PDF export functionality";
    }

    private function exportToExcel($data) {
        // Excel export implementation
        // This would integrate with PhpSpreadsheet
        return "Excel export functionality";
    }

    private function exportToCSV($data) {
        // CSV export implementation
        $output = fopen('php://temp', 'w');

        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));

            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    private function scheduleReport($reportId, $scheduleConfig) {
        // Implementation for scheduling reports
        $this->db->query("
            INSERT INTO scheduled_reports
            (report_id, company_id, schedule_type, schedule_config, next_run, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $reportId,
            $this->user['company_id'],
            $scheduleConfig['type'],
            json_encode($scheduleConfig),
            $this->calculateNextRun($scheduleConfig),
            $this->user['id']
        ]);
    }

    private function calculateNextRun($scheduleConfig) {
        // Implementation for calculating next run time based on schedule configuration
        $now = new DateTime();

        switch ($scheduleConfig['type']) {
            case 'daily':
                return $now->modify('+1 day')->format('Y-m-d H:i:s');
            case 'weekly':
                return $now->modify('next ' . $scheduleConfig['day'])->format('Y-m-d H:i:s');
            case 'monthly':
                return $now->modify('first day of next month')->format('Y-m-d H:i:s');
            default:
                return $now->format('Y-m-d H:i:s');
        }
    }

    private function shareReport($reportId, $recipients, $permissions) {
        // Implementation for sharing reports with other users
        foreach ($recipients as $recipient) {
            $this->db->query("
                INSERT INTO shared_reports
                (report_id, company_id, shared_with, permission_level, shared_by)
                VALUES (?, ?, ?, ?, ?)
            ", [
                $reportId,
                $this->user['company_id'],
                $recipient,
                $permissions,
                $this->user['id']
            ]);
        }
    }

    private function createDashboard($dashboardConfig) {
        // Implementation for creating dashboards
        $dashboardId = $this->db->query("
            INSERT INTO dashboards
            (company_id, dashboard_name, description, layout_config, created_by)
            VALUES (?, ?, ?, ?, ?)
        ", [
            $this->user['company_id'],
            $dashboardConfig['name'],
            $dashboardConfig['description'],
            json_encode($dashboardConfig['layout']),
            $this->user['id']
        ]);

        return $dashboardId;
    }

    private function addWidgetToDashboard($dashboardId, $widgetConfig) {
        // Implementation for adding widgets to dashboards
        $this->db->query("
            INSERT INTO dashboard_widgets
            (dashboard_id, company_id, widget_type, widget_config, position_x, position_y)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $dashboardId,
            $this->user['company_id'],
            $widgetConfig['type'],
            json_encode($widgetConfig['config']),
            $widgetConfig['position']['x'],
            $widgetConfig['position']['y']
        ]);
    }

    private function generateAIInsight($dataSource, $insightType) {
        // Implementation for AI-powered insight generation
        // This would integrate with AI/ML services
        $insight = [
            'type' => $insightType,
            'confidence' => rand(70, 95),
            'description' => "AI-generated insight for {$dataSource}",
            'recommendation' => "Recommended action based on analysis",
            'generated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->query("
            INSERT INTO ai_insights
            (company_id, insight_type, confidence_score, description, recommendation, generated_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $this->user['company_id'],
            $insight['type'],
            $insight['confidence'],
            $insight['description'],
            $insight['recommendation'],
            $insight['generated_at']
        ]);

        return $insight;
    }

    private function validateDataSource($dataSourceId) {
        // Implementation for validating data source connections
        $dataSource = $this->db->querySingle("SELECT * FROM data_sources WHERE id = ? AND company_id = ?", [$dataSourceId, $this->user['company_id']]);

        if (!$dataSource) {
            throw new Exception("Data source not found");
        }

        return $dataSource;
    }

    private function getOptimizationHistory() {
        return $this->db->query("
            SELECT
                ro.*,
                r.report_name,
                u.first_name as analyzed_by_first,
                u.last_name as analyzed_by_last,
                ro.generated_at
            FROM report_optimizations ro
            JOIN reports r ON ro.report_id = r.id
            LEFT JOIN users u ON ro.created_by = u.id
            WHERE ro.company_id = ?
            ORDER BY ro.generated_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getAISuggestions() {
        return $this->db->query("
            SELECT
                ai.*,
                ai.insight_type,
                ai.confidence_score,
                ai.description,
                ai.recommendation,
                ai.generated_at
            FROM ai_insights ai
            WHERE ai.company_id = ?
            ORDER BY ai.confidence_score DESC, ai.generated_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getPerformanceInsights() {
        return $this->db->query("
            SELECT
                pi.*,
                pi.insight_type,
                pi.confidence_interval,
                pi.recommended_action,
                pi.potential_impact,
                pi.generated_at
            FROM performance_insights pi
            WHERE pi.company_id = ?
            ORDER BY pi.potential_impact DESC, pi.generated_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getConfiguredBITools() {
        return $this->db->query("
            SELECT
                btc.*,
                btc.tool_name,
                btc.created_at,
                btc.updated_at,
                u.first_name as configured_by_first,
                u.last_name as configured_by_last
            FROM bi_tool_configs btc
            LEFT JOIN users u ON btc.created_by = u.id
            WHERE btc.company_id = ? AND btc.is_active = true
            ORDER BY btc.tool_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBIExportHistory() {
        return $this->db->query("
            SELECT
                beh.*,
                beh.tool_name,
                beh.data_source,
                beh.exported_at,
                u.first_name as exported_by_first,
                u.last_name as exported_by_last
            FROM bi_export_history beh
            LEFT JOIN users u ON beh.exported_by = u.id
            WHERE beh.company_id = ?
            ORDER BY beh.exported_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getBISyncJobs() {
        return $this->db->query("
            SELECT
                bsj.*,
                bsj.tool_name,
                bsj.direction,
                bsj.data_source,
                bsj.status,
                bsj.created_at,
                bsj.completed_at,
                u.first_name as created_by_first,
                u.last_name as created_by_last
            FROM bi_sync_jobs bsj
            LEFT JOIN users u ON bsj.created_by = u.id
            WHERE bsj.company_id = ?
            ORDER BY bsj.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getEmbeddedDashboards() {
        return $this->db->query("
            SELECT
                ed.*,
                ed.tool_name,
                ed.dashboard_name,
                ed.embed_url,
                ed.created_at,
                u.first_name as created_by_first,
                u.last_name as created_by_last
            FROM embedded_dashboards ed
            LEFT JOIN users u ON ed.created_by = u.id
            WHERE ed.company_id = ?
            ORDER BY ed.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getBIIntegrationStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT btc.id) as configured_tools,
                COUNT(DISTINCT beh.id) as total_exports,
                COUNT(DISTINCT bsj.id) as total_syncs,
                COUNT(CASE WHEN bsj.status = 'completed' THEN 1 END) as successful_syncs,
                COUNT(CASE WHEN bsj.status = 'failed' THEN 1 END) as failed_syncs,
                AVG(CASE WHEN bsj.status = 'completed' THEN TIMESTAMPDIFF(SECOND, bsj.created_at, bsj.completed_at) END) as avg_sync_time
            FROM bi_tool_configs btc
            LEFT JOIN bi_export_history beh ON beh.company_id = btc.company_id
            LEFT JOIN bi_sync_jobs bsj ON bsj.company_id = btc.company_id
            WHERE btc.company_id = ? AND btc.is_active = true
        ", [$this->user['company_id']]);
    }
}
