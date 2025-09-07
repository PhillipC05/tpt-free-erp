<?php
/**
 * TPT Free ERP - Advanced Analytics & Business Intelligence Module
 * Complete data visualization, predictive modeling, and business intelligence
 */

class AdvancedAnalytics extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main analytics dashboard
     */
    public function index() {
        $this->requirePermission('analytics.view');

        $data = [
            'title' => 'Advanced Analytics & BI',
            'dashboard_overview' => $this->getDashboardOverview(),
            'key_metrics' => $this->getKeyMetrics(),
            'data_insights' => $this->getDataInsights(),
            'predictive_analytics' => $this->getPredictiveAnalytics(),
            'real_time_updates' => $this->getRealTimeUpdates()
        ];

        $this->render('modules/analytics/dashboard', $data);
    }

    /**
     * Custom dashboard builder
     */
    public function dashboards() {
        $this->requirePermission('analytics.dashboards.view');

        $filters = [
            'category' => $_GET['category'] ?? null,
            'type' => $_GET['type'] ?? null,
            'shared' => $_GET['shared'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $dashboards = $this->getDashboards($filters);

        $data = [
            'title' => 'Dashboard Builder',
            'dashboards' => $dashboards,
            'filters' => $filters,
            'categories' => $this->getDashboardCategories(),
            'types' => $this->getDashboardTypes(),
            'widgets' => $this->getAvailableWidgets(),
            'dashboard_summary' => $this->getDashboardSummary($filters)
        ];

        $this->render('modules/analytics/dashboards', $data);
    }

    /**
     * Create custom dashboard
     */
    public function createDashboard() {
        $this->requirePermission('analytics.dashboards.create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processDashboardCreation();
        }

        $data = [
            'title' => 'Create Dashboard',
            'categories' => $this->getDashboardCategories(),
            'types' => $this->getDashboardTypes(),
            'widgets' => $this->getAvailableWidgets(),
            'data_sources' => $this->getDataSources(),
            'next_dashboard_id' => $this->generateNextDashboardId()
        ];

        $this->render('modules/analytics/create_dashboard', $data);
    }

    /**
     * Data visualization
     */
    public function visualizations() {
        $this->requirePermission('analytics.visualizations.view');

        $data = [
            'title' => 'Data Visualizations',
            'charts' => $this->getCharts(),
            'graphs' => $this->getGraphs(),
            'maps' => $this->getMaps(),
            'custom_visualizations' => $this->getCustomVisualizations(),
            'visualization_templates' => $this->getVisualizationTemplates()
        ];

        $this->render('modules/analytics/visualizations', $data);
    }

    /**
     * Predictive modeling
     */
    public function predictiveModeling() {
        $this->requirePermission('analytics.predictive.view');

        $data = [
            'title' => 'Predictive Modeling',
            'models' => $this->getPredictiveModels(),
            'algorithms' => $this->getAlgorithms(),
            'training_data' => $this->getTrainingData(),
            'model_performance' => $this->getModelPerformance(),
            'predictions' => $this->getPredictions()
        ];

        $this->render('modules/analytics/predictive_modeling', $data);
    }

    /**
     * Business intelligence reports
     */
    public function reports() {
        $this->requirePermission('analytics.reports.view');

        $filters = [
            'category' => $_GET['category'] ?? null,
            'type' => $_GET['type'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $reports = $this->getReports($filters);

        $data = [
            'title' => 'Business Intelligence Reports',
            'reports' => $reports,
            'filters' => $filters,
            'categories' => $this->getReportCategories(),
            'types' => $this->getReportTypes(),
            'schedules' => $this->getReportSchedules(),
            'report_summary' => $this->getReportSummary($filters)
        ];

        $this->render('modules/analytics/reports', $data);
    }

    /**
     * Data exploration and analysis
     */
    public function dataExploration() {
        $this->requirePermission('analytics.exploration.view');

        $data = [
            'title' => 'Data Exploration',
            'datasets' => $this->getDatasets(),
            'queries' => $this->getQueries(),
            'analysis_tools' => $this->getAnalysisTools(),
            'data_quality' => $this->getDataQuality(),
            'exploration_history' => $this->getExplorationHistory()
        ];

        $this->render('modules/analytics/data_exploration', $data);
    }

    /**
     * Real-time analytics
     */
    public function realTimeAnalytics() {
        $this->requirePermission('analytics.realtime.view');

        $data = [
            'title' => 'Real-time Analytics',
            'live_metrics' => $this->getLiveMetrics(),
            'streaming_data' => $this->getStreamingData(),
            'alerts' => $this->getRealTimeAlerts(),
            'performance_indicators' => $this->getPerformanceIndicators(),
            'trend_analysis' => $this->getTrendAnalysis()
        ];

        $this->render('modules/analytics/real_time_analytics', $data);
    }

    /**
     * Machine learning integration
     */
    public function machineLearning() {
        $this->requirePermission('analytics.ml.view');

        $data = [
            'title' => 'Machine Learning',
            'models' => $this->getMLModels(),
            'datasets' => $this->getMLDatasets(),
            'experiments' => $this->getExperiments(),
            'feature_engineering' => $this->getFeatureEngineering(),
            'model_deployment' => $this->getModelDeployment()
        ];

        $this->render('modules/analytics/machine_learning', $data);
    }

    /**
     * Advanced reporting and exports
     */
    public function advancedReporting() {
        $this->requirePermission('analytics.advanced.view');

        $data = [
            'title' => 'Advanced Reporting',
            'custom_queries' => $this->getCustomQueries(),
            'data_exports' => $this->getDataExports(),
            'scheduled_reports' => $this->getScheduledReports(),
            'report_templates' => $this->getReportTemplates(),
            'export_formats' => $this->getExportFormats()
        ];

        $this->render('modules/analytics/advanced_reporting', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getDashboardOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_dashboards,
                COUNT(CASE WHEN status = 'published' THEN 1 END) as published_dashboards,
                COUNT(CASE WHEN is_shared = true THEN 1 END) as shared_dashboards,
                SUM(view_count) as total_views,
                AVG(last_viewed_at) as avg_last_viewed,
                COUNT(DISTINCT category) as categories_used
            FROM dashboards
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getKeyMetrics() {
        return $this->db->query("
            SELECT
                km.*,
                km.current_value,
                km.previous_value,
                ROUND(
                    ((km.current_value - km.previous_value) / NULLIF(km.previous_value, 0)) * 100, 2
                ) as percentage_change,
                CASE
                    WHEN km.current_value > km.previous_value THEN 'increase'
                    WHEN km.current_value < km.previous_value THEN 'decrease'
                    ELSE 'stable'
                END as trend
            FROM key_metrics km
            WHERE km.company_id = ? AND km.is_active = true
            ORDER BY km.priority DESC, km.name ASC
            LIMIT 12
        ", [$this->user['company_id']]);
    }

    private function getDataInsights() {
        return $this->db->query("
            SELECT
                di.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(dic.id) as comments_count,
                MAX(dic.created_at) as last_comment
            FROM data_insights di
            LEFT JOIN users u ON di.created_by = u.id
            LEFT JOIN data_insight_comments dic ON di.id = dic.insight_id
            WHERE di.company_id = ?
            GROUP BY di.id, u.first_name, u.last_name
            ORDER BY di.created_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getPredictiveAnalytics() {
        return $this->db->query("
            SELECT
                pa.*,
                pm.name as model_name,
                pm.accuracy_score,
                pa.confidence_level,
                CASE
                    WHEN pa.prediction_type = 'trend' THEN 'Trending'
                    WHEN pa.prediction_type = 'anomaly' THEN 'Anomaly Detected'
                    WHEN pa.prediction_type = 'forecast' THEN 'Forecast'
                    ELSE 'Prediction'
                END as prediction_label
            FROM predictive_analytics pa
            JOIN predictive_models pm ON pa.model_id = pm.id
            WHERE pa.company_id = ? AND pa.confidence_level > 0.7
            ORDER BY pa.confidence_level DESC, pa.created_at DESC
            LIMIT 15
        ", [$this->user['company_id']]);
    }

    private function getRealTimeUpdates() {
        return $this->db->query("
            SELECT
                rtu.*,
                rtu.metric_value,
                rtu.previous_value,
                TIMESTAMPDIFF(SECOND, rtu.created_at, NOW()) as seconds_ago,
                CASE
                    WHEN rtu.metric_value > rtu.previous_value THEN 'up'
                    WHEN rtu.metric_value < rtu.previous_value THEN 'down'
                    ELSE 'stable'
                END as direction
            FROM real_time_updates rtu
            WHERE rtu.company_id = ? AND rtu.created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY rtu.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getDashboards($filters) {
        $where = ["d.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['category']) {
            $where[] = "d.category = ?";
            $params[] = $filters['category'];
        }

        if ($filters['type']) {
            $where[] = "d.type = ?";
            $params[] = $filters['type'];
        }

        if ($filters['shared'] !== null) {
            $where[] = "d.is_shared = ?";
            $params[] = $filters['shared'] === 'true' ? 1 : 0;
        }

        if ($filters['search']) {
            $where[] = "(d.name LIKE ? OR d.description LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                d.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(dw.id) as widget_count,
                COUNT(dv.id) as view_count,
                MAX(dv.viewed_at) as last_viewed
            FROM dashboards d
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN dashboard_widgets dw ON d.id = dw.dashboard_id
            LEFT JOIN dashboard_views dv ON d.id = dv.dashboard_id
            WHERE $whereClause
            GROUP BY d.id, u.first_name, u.last_name
            ORDER BY d.created_at DESC
        ", $params);
    }

    private function getDashboardCategories() {
        return [
            'executive' => 'Executive Dashboard',
            'operational' => 'Operational Dashboard',
            'financial' => 'Financial Dashboard',
            'sales' => 'Sales Dashboard',
            'marketing' => 'Marketing Dashboard',
            'hr' => 'HR Dashboard',
            'it' => 'IT Dashboard',
            'custom' => 'Custom Dashboard'
        ];
    }

    private function getDashboardTypes() {
        return [
            'realtime' => 'Real-time Dashboard',
            'historical' => 'Historical Dashboard',
            'predictive' => 'Predictive Dashboard',
            'comparative' => 'Comparative Dashboard',
            'kpi' => 'KPI Dashboard',
            'operational' => 'Operational Dashboard'
        ];
    }

    private function getAvailableWidgets() {
        return $this->db->query("
            SELECT * FROM widget_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getDataSources() {
        return $this->db->query("
            SELECT * FROM data_sources
            WHERE company_id = ? AND is_active = true
            ORDER BY type, name
        ", [$this->user['company_id']]);
    }

    private function getDashboardSummary($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['category']) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }

        if ($filters['type']) {
            $where[] = "type = ?";
            $params[] = $filters['type'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_dashboards,
                COUNT(CASE WHEN status = 'published' THEN 1 END) as published_dashboards,
                COUNT(CASE WHEN is_shared = true THEN 1 END) as shared_dashboards,
                SUM(view_count) as total_views,
                AVG(view_count) as avg_views_per_dashboard
            FROM dashboards
            WHERE $whereClause
        ", $params);
    }

    private function generateNextDashboardId() {
        $lastDashboard = $this->db->querySingle("
            SELECT dashboard_id FROM dashboards
            WHERE company_id = ? AND dashboard_id LIKE 'DB%'
            ORDER BY dashboard_id DESC
            LIMIT 1
        ", [$this->user['company_id']]);

        if ($lastDashboard) {
            $number = (int)substr($lastDashboard['dashboard_id'], 2) + 1;
            return 'DB' . str_pad($number, 6, '0', STR_PAD_LEFT);
        }

        return 'DB000001';
    }

    private function processDashboardCreation() {
        $this->requirePermission('analytics.dashboards.create');

        $data = $this->validateDashboardData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid dashboard data');
            $this->redirect('/analytics/create-dashboard');
        }

        try {
            $this->db->beginTransaction();

            $dashboardId = $this->db->insert('dashboards', [
                'company_id' => $this->user['company_id'],
                'dashboard_id' => $data['dashboard_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'category' => $data['category'],
                'type' => $data['type'],
                'layout' => json_encode($data['layout']),
                'settings' => json_encode($data['settings']),
                'is_shared' => isset($data['is_shared']) ? (bool)$data['is_shared'] : false,
                'status' => $data['status'],
                'created_by' => $this->user['id']
            ]);

            // Add widgets if provided
            if (!empty($data['widgets'])) {
                $this->addDashboardWidgets($dashboardId, $data['widgets']);
            }

            $this->db->commit();

            $this->setFlash('success', 'Dashboard created successfully');
            $this->redirect('/analytics/dashboards');

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to create dashboard: ' . $e->getMessage());
            $this->redirect('/analytics/create-dashboard');
        }
    }

    private function validateDashboardData($data) {
        if (empty($data['name']) || empty($data['category']) || empty($data['type'])) {
            return false;
        }

        return [
            'dashboard_id' => $data['dashboard_id'] ?? $this->generateNextDashboardId(),
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'category' => $data['category'],
            'type' => $data['type'],
            'layout' => $data['layout'] ?? [],
            'settings' => $data['settings'] ?? [],
            'widgets' => $data['widgets'] ?? [],
            'status' => $data['status'] ?? 'draft'
        ];
    }

    private function addDashboardWidgets($dashboardId, $widgets) {
        foreach ($widgets as $widget) {
            $this->db->insert('dashboard_widgets', [
                'dashboard_id' => $dashboardId,
                'widget_template_id' => $widget['template_id'],
                'name' => $widget['name'],
                'configuration' => json_encode($widget['configuration'] ?? []),
                'position' => json_encode($widget['position'] ?? []),
                'size' => json_encode($widget['size'] ?? []),
                'data_source' => $widget['data_source'] ?? null,
                'refresh_interval' => $widget['refresh_interval'] ?? 300,
                'is_active' => true
            ]);
        }
    }

    private function getCharts() {
        return $this->db->query("
            SELECT
                c.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(cv.id) as view_count,
                MAX(cv.viewed_at) as last_viewed
            FROM charts c
            LEFT JOIN users u ON c.created_by = u.id
            LEFT JOIN chart_views cv ON c.id = cv.chart_id
            WHERE c.company_id = ?
            GROUP BY c.id, u.first_name, u.last_name
            ORDER BY c.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getGraphs() {
        return $this->db->query("
            SELECT
                g.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(gv.id) as view_count,
                MAX(gv.viewed_at) as last_viewed
            FROM graphs g
            LEFT JOIN users u ON g.created_by = u.id
            LEFT JOIN graph_views gv ON g.id = gv.graph_id
            WHERE g.company_id = ?
            GROUP BY g.id, u.first_name, u.last_name
            ORDER BY g.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getMaps() {
        return $this->db->query("
            SELECT
                m.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(mv.id) as view_count,
                MAX(mv.viewed_at) as last_viewed
            FROM maps m
            LEFT JOIN users u ON m.created_by = u.id
            LEFT JOIN map_views mv ON m.id = mv.map_id
            WHERE m.company_id = ?
            GROUP BY m.id, u.first_name, u.last_name
            ORDER BY m.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomVisualizations() {
        return $this->db->query("
            SELECT
                cv.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(cvv.id) as view_count,
                MAX(cvv.viewed_at) as last_viewed
            FROM custom_visualizations cv
            LEFT JOIN users u ON cv.created_by = u.id
            LEFT JOIN custom_visualization_views cvv ON cv.id = cvv.visualization_id
            WHERE cv.company_id = ?
            GROUP BY cv.id, u.first_name, u.last_name
            ORDER BY cv.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getVisualizationTemplates() {
        return $this->db->query("
            SELECT * FROM visualization_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getPredictiveModels() {
        return $this->db->query("
            SELECT
                pm.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(pmp.id) as predictions_count,
                AVG(pmp.accuracy_score) as avg_accuracy,
                MAX(pmp.created_at) as last_prediction
            FROM predictive_models pm
            LEFT JOIN users u ON pm.created_by = u.id
            LEFT JOIN predictive_model_predictions pmp ON pm.id = pmp.model_id
            WHERE pm.company_id = ?
            GROUP BY pm.id, u.first_name, u.last_name
            ORDER BY pm.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAlgorithms() {
        return [
            'linear_regression' => 'Linear Regression',
            'logistic_regression' => 'Logistic Regression',
            'decision_tree' => 'Decision Tree',
            'random_forest' => 'Random Forest',
            'svm' => 'Support Vector Machine',
            'neural_network' => 'Neural Network',
            'k_means' => 'K-Means Clustering',
            'time_series' => 'Time Series Analysis',
            'anomaly_detection' => 'Anomaly Detection'
        ];
    }

    private function getTrainingData() {
        return $this->db->query("
            SELECT
                td.*,
                COUNT(tdr.id) as records_count,
                AVG(tdr.data_quality_score) as avg_data_quality,
                MAX(tdr.created_at) as last_updated
            FROM training_data td
            LEFT JOIN training_data_records tdr ON td.id = tdr.dataset_id
            WHERE td.company_id = ?
            GROUP BY td.id
            ORDER BY td.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getModelPerformance() {
        return $this->db->query("
            SELECT
                pm.name as model_name,
                pm.algorithm,
                AVG(mp.accuracy_score) as avg_accuracy,
                AVG(mp.precision_score) as avg_precision,
                AVG(mp.recall_score) as avg_recall,
                AVG(mp.f1_score) as avg_f1_score,
                COUNT(mp.id) as evaluation_count,
                MAX(mp.created_at) as last_evaluation
            FROM predictive_models pm
            LEFT JOIN model_performance mp ON pm.id = mp.model_id
            WHERE pm.company_id = ?
            GROUP BY pm.id, pm.name, pm.algorithm
            ORDER BY avg_accuracy DESC
        ", [$this->user['company_id']]);
    }

    private function getPredictions() {
        return $this->db->query("
            SELECT
                p.*,
                pm.name as model_name,
                p.prediction_value,
                p.confidence_score,
                p.actual_value,
                CASE
                    WHEN p.actual_value IS NOT NULL THEN
                        ABS(p.prediction_value - p.actual_value) / NULLIF(p.actual_value, 0) * 100
                    ELSE NULL
                END as prediction_error_percentage
            FROM predictions p
            JOIN predictive_models pm ON p.model_id = pm.id
            WHERE p.company_id = ?
            ORDER BY p.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getReports($filters) {
        $where = ["r.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['category']) {
            $where[] = "r.category = ?";
            $params[] = $filters['category'];
        }

        if ($filters['type']) {
            $where[] = "r.type = ?";
            $params[] = $filters['type'];
        }

        if ($filters['date_from']) {
            $where[] = "r.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "r.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(r.name LIKE ? OR r.description LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                r.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(rv.id) as view_count,
                COUNT(rd.id) as download_count,
                MAX(rv.viewed_at) as last_viewed,
                MAX(rd.downloaded_at) as last_downloaded
            FROM reports r
            LEFT JOIN users u ON r.created_by = u.id
            LEFT JOIN report_views rv ON r.id = rv.report_id
            LEFT JOIN report_downloads rd ON r.id = rd.report_id
            WHERE $whereClause
            GROUP BY r.id, u.first_name, u.last_name
            ORDER BY r.created_at DESC
        ", $params);
    }

    private function getReportCategories() {
        return [
            'financial' => 'Financial Reports',
            'operational' => 'Operational Reports',
            'sales' => 'Sales Reports',
            'marketing' => 'Marketing Reports',
            'hr' => 'HR Reports',
            'inventory' => 'Inventory Reports',
            'custom' => 'Custom Reports'
        ];
    }

    private function getReportTypes() {
        return [
            'summary' => 'Summary Report',
            'detailed' => 'Detailed Report',
            'comparative' => 'Comparative Report',
            'trend' => 'Trend Analysis',
            'forecast' => 'Forecast Report',
            'dashboard' => 'Dashboard Report'
        ];
    }

    private function getReportSchedules() {
        return $this->db->query("
            SELECT * FROM report_schedules
            WHERE company_id = ? AND is_active = true
            ORDER BY next_run ASC
        ", [$this->user['company_id']]);
    }

    private function getReportSummary($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['category']) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }

        if ($filters['type']) {
            $where[] = "type = ?";
            $params[] = $filters['type'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_reports,
                COUNT(CASE WHEN status = 'published' THEN 1 END) as published_reports,
                COUNT(CASE WHEN is_scheduled = true THEN 1 END) as scheduled_reports,
                SUM(view_count) as total_views,
                SUM(download_count) as total_downloads,
                AVG(view_count) as avg_views_per_report
            FROM reports
            WHERE $whereClause
        ", $params);
    }

    private function getDatasets() {
        return $this->db->query("
            SELECT
                ds.*,
                COUNT(dsr.id) as records_count,
                SUM(dsr.size_bytes) as total_size,
                MAX(dsr.created_at) as last_updated,
                AVG(dsr.data_quality_score) as avg_data_quality
            FROM datasets ds
            LEFT JOIN dataset_records dsr ON ds.id = dsr.dataset_id
            WHERE ds.company_id = ?
            GROUP BY ds.id
            ORDER BY ds.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getQueries() {
        return $this->db->query("
            SELECT
                q.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(qe.id) as execution_count,
                AVG(qe.execution_time_ms) as avg_execution_time,
                MAX(qe.executed_at) as last_executed
            FROM queries q
            LEFT JOIN users u ON q.created_by = u.id
            LEFT JOIN query_executions qe ON q.id = qe.query_id
            WHERE q.company_id = ?
            GROUP BY q.id, u.first_name, u.last_name
            ORDER BY q.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAnalysisTools() {
        return [
            'statistical_analysis' => 'Statistical Analysis',
            'correlation_analysis' => 'Correlation Analysis',
            'regression_analysis' => 'Regression Analysis',
            'cluster_analysis' => 'Cluster Analysis',
            'factor_analysis' => 'Factor Analysis',
            'time_series_analysis' => 'Time Series Analysis',
            'anomaly_detection' => 'Anomaly Detection',
            'data_mining' => 'Data Mining'
        ];
    }

    private function getDataQuality() {
        return $this->db->querySingle("
            SELECT
                AVG(completeness_score) as avg_completeness,
                AVG(accuracy_score) as avg_accuracy,
                AVG(consistency_score) as avg_consistency,
                AVG(timeliness_score) as avg_timeliness,
                COUNT(CASE WHEN completeness_score < 0.8 THEN 1 END) as low_completeness_count,
                COUNT(CASE WHEN accuracy_score < 0.8 THEN 1 END) as low_accuracy_count,
                COUNT(*) as total_records
            FROM data_quality_metrics
            WHERE company_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);
    }

    private function getExplorationHistory() {
        return $this->db->query("
            SELECT
                eh.*,
                u.first_name as user_first,
                u.last_name as user_last,
                eh.query_executed,
                eh.results_count,
                eh.execution_time_ms
            FROM exploration_history eh
            LEFT JOIN users u ON eh.user_id = u.id
            WHERE eh.company_id = ?
            ORDER BY eh.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getLiveMetrics() {
        return $this->db->query("
            SELECT
                lm.*,
                lm.metric_value,
                lm.previous_value,
                TIMESTAMPDIFF(SECOND, lm.updated_at, NOW()) as seconds_since_update,
                CASE
                    WHEN lm.metric_value > lm.previous_value THEN 'increasing'
                    WHEN lm.metric_value < lm.previous_value THEN 'decreasing'
                    ELSE 'stable'
                END as trend
            FROM live_metrics lm
            WHERE lm.company_id = ? AND lm.is_active = true
            ORDER BY lm.priority DESC, lm.name ASC
        ", [$this->user['company_id']]);
    }

    private function getStreamingData() {
        return $this->db->query("
            SELECT
                sd.*,
                sd.stream_name,
                sd.current_value,
                TIMESTAMPDIFF(SECOND, sd.last_updated, NOW()) as seconds_since_update,
                sd.data_rate_per_second
            FROM streaming_data sd
            WHERE sd.company_id = ? AND sd.is_active = true
            ORDER BY sd.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getRealTimeAlerts() {
        return $this->db->query("
            SELECT
                rta.*,
                rta.alert_message,
                rta.severity,
                TIMESTAMPDIFF(MINUTE, rta.created_at, NOW()) as minutes_active,
                rta.threshold_value,
                rta.current_value
            FROM real_time_alerts rta
            WHERE rta.company_id = ? AND rta.status = 'active'
                AND rta.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY rta.severity DESC, rta.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceIndicators() {
        return $this->db->query("
            SELECT
                pi.*,
                pi.indicator_name,
                pi.current_value,
                pi.target_value,
                ROUND((pi.current_value / NULLIF(pi.target_value, 0)) * 100, 2) as achievement_percentage,
                CASE
                    WHEN pi.current_value >= pi.target_value THEN 'achieved'
                    WHEN pi.current_value >= pi.target_value * 0.9 THEN 'near_target'
                    ELSE 'below_target'
                END as status
            FROM performance_indicators pi
            WHERE pi.company_id = ? AND pi.is_active = true
            ORDER BY pi.priority DESC, pi.indicator_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTrendAnalysis() {
        return $this->db->query("
            SELECT
                ta.*,
                ta.metric_name,
                ta.trend_direction,
                ta.trend_strength,
                ta.confidence_level,
                ta.predicted_change_percentage,
                ta.time_period_days
            FROM trend_analysis ta
            WHERE ta.company_id = ? AND ta.confidence_level > 0.7
            ORDER BY ta.confidence_level DESC, ta.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getMLModels() {
        return $this->db->query("
            SELECT
                mlm.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(mlmp.id) as predictions_count,
                AVG(mlmp.accuracy_score) as avg_accuracy,
                MAX(mlmp.created_at) as last_used
            FROM ml_models mlm
            LEFT JOIN users u ON mlm.created_by = u.id
            LEFT JOIN ml_model_predictions mlmp ON mlm.id = mlmp.model_id
            WHERE mlm.company_id = ?
            GROUP BY mlm.id, u.first_name, u.last_name
            ORDER BY mlm.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getMLDatasets() {
        return $this->db->query("
            SELECT
                mlds.*,
                COUNT(mldsr.id) as records_count,
                SUM(mldsr.size_bytes) as total_size,
                AVG(mldsr.data_quality_score) as avg_data_quality,
                MAX(mldsr.created_at) as last_updated
            FROM ml_datasets mlds
            LEFT JOIN ml_dataset_records mldsr ON mlds.id = mldsr.dataset_id
            WHERE mlds.company_id = ?
            GROUP BY mlds.id
            ORDER BY mlds.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getExperiments() {
        return $this->db->query("
            SELECT
                mle.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(mlep.id) as parameters_tested,
                AVG(mlep.accuracy_score) as avg_accuracy,
                MAX(mlep.created_at) as last_run
            FROM ml_experiments mle
            LEFT JOIN users u ON mle.created_by = u.id
            LEFT JOIN ml_experiment_parameters mlep ON mle.id = mlep.experiment_id
            WHERE mle.company_id = ?
            GROUP BY mle.id, u.first_name, u.last_name
            ORDER BY mle.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getFeatureEngineering() {
        return $this->db->query("
            SELECT
                fe.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(fef.id) as features_created,
                AVG(fef.importance_score) as avg_feature_importance,
                MAX(fef.created_at) as last_updated
            FROM feature_engineering fe
            LEFT JOIN users u ON fe.created_by = u.id
            LEFT JOIN feature_engineering_features fef ON fe.id = fef.engineering_id
            WHERE fe.company_id = ?
            GROUP BY fe.id, u.first_name, u.last_name
            ORDER BY fe.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getModelDeployment() {
        return $this->db->query("
            SELECT
                md.*,
                mlm.name as model_name,
                u.first_name as deployed_by_first,
                u.last_name as deployed_by_last,
                md.deployment_status,
                md.endpoint_url,
                COUNT(mdp.id) as predictions_count,
                AVG(mdp.response_time_ms) as avg_response_time
            FROM model_deployment md
            JOIN ml_models mlm ON md.model_id = mlm.id
            LEFT JOIN users u ON md.deployed_by = u.id
            LEFT JOIN model_deployment_predictions mdp ON md.id = mdp.deployment_id
            WHERE md.company_id = ?
            GROUP BY md.id, mlm.name, u.first_name, u.last_name
            ORDER BY md.deployed_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomQueries() {
        return $this->db->query("
            SELECT
                cq.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(cqe.id) as execution_count,
                AVG(cqe.execution_time_ms) as avg_execution_time,
                MAX(cqe.executed_at) as last_executed
            FROM custom_queries cq
            LEFT JOIN users u ON cq.created_by = u.id
            LEFT JOIN custom_query_executions cqe ON cq.id = cqe.query_id
            WHERE cq.company_id = ?
            GROUP BY cq.id, u.first_name, u.last_name
            ORDER BY cq.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getDataExports() {
        return $this->db->query("
            SELECT
                de.*,
                u.first_name as exported_by_first,
                u.last_name as exported_by_last,
                de.file_size_bytes,
                de.record_count,
                de.export_format,
                de.download_url
            FROM data_exports de
            LEFT JOIN users u ON de.exported_by = u.id
            WHERE de.company_id = ?
            ORDER BY de.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getScheduledReports() {
        return $this->db->query("
            SELECT
                sr.*,
                r.name as report_name,
                u.first_name as scheduled_by_first,
                u.last_name as scheduled_by_last,
                sr.next_run,
                sr.last_run,
                TIMESTAMPDIFF(MINUTE, NOW(), sr.next_run) as minutes_until_next
            FROM scheduled_reports sr
            JOIN reports r ON sr.report_id = r.id
            LEFT JOIN users u ON sr.scheduled_by = u.id
            WHERE sr.company_id = ? AND sr.is_active = true
            ORDER BY sr.next_run ASC
        ", [$this->user['company_id']]);
    }

    private function getReportTemplates() {
        return $this->db->query("
            SELECT * FROM report_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getExportFormats() {
        return [
            'csv' => 'CSV',
            'excel' => 'Excel (XLSX)',
            'pdf' => 'PDF',
            'json' => 'JSON',
            'xml' => 'XML',
            'html' => 'HTML'
        ];
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function createDashboard() {
        $this->requirePermission('analytics.dashboards.create');

        $data = $this->validateRequest([
            'name' => 'required|string',
            'description' => 'string',
            'category' => 'required|string',
            'type' => 'required|string',
            'layout' => 'array',
            'widgets' => 'array'
        ]);

        try {
            $dashboardId = $this->db->insert('dashboards', [
                'company_id' => $this->user['company_id'],
                'dashboard_id' => $this->generateNextDashboardId(),
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'category' => $data['category'],
                'type' => $data['type'],
                'layout' => json_encode($data['layout'] ?? []),
                'settings' => json_encode([]),
                'is_shared' => false,
                'status' => 'draft',
                'created_by' => $this->user['id']
            ]);

            // Add widgets if provided
            if (!empty($data['widgets'])) {
                $this->addDashboardWidgets($dashboardId, $data['widgets']);
            }

            $this->jsonResponse([
                'success' => true,
                'dashboard_id' => $dashboardId,
                'message' => 'Dashboard created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDashboardData() {
        $this->requirePermission('analytics.dashboards.view');

        $data = $this->validateRequest([
            'dashboard_id' => 'required|integer',
            'date_from' => 'date',
            'date_to' => 'date'
        ]);

        try {
            $dashboard = $this->db->querySingle("
                SELECT * FROM dashboards
                WHERE id = ? AND company_id = ?
            ", [$data['dashboard_id'], $this->user['company_id']]);

            if (!$dashboard) {
                throw new Exception('Dashboard not found');
            }

            $widgets = $this->db->query("
                SELECT * FROM dashboard_widgets
                WHERE dashboard_id = ? AND is_active = true
                ORDER BY position->>'$.order' ASC
            ", [$data['dashboard_id']]);

            $widgetData = [];
            foreach ($widgets as $widget) {
                $widgetData[] = [
                    'widget' => $widget,
                    'data' => $this->getWidgetData($widget, $data)
                ];
            }

            $this->jsonResponse([
                'success' => true,
                'dashboard' => $dashboard,
                'widgets' => $widgetData
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getWidgetData($widget, $params) {
        // Implementation for fetching widget data based on widget configuration
        // This would vary based on the widget type and data source
        return [
            'data' => [],
            'metadata' => [
                'last_updated' => date('Y-m-d H:i:s'),
                'data_points' => 0
            ]
        ];
    }

    public function runPredictiveModel() {
        $this->requirePermission('analytics.predictive.run');

        $data = $this->validateRequest([
            'model_id' => 'required|integer',
            'input_data' => 'required|array'
        ]);

        try {
            // Implementation for running predictive model
            // This would integrate with machine learning
