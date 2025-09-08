<?php
/**
 * TPT Free ERP - Advanced Analytics & BI Module
 * Complete business intelligence and advanced analytics system
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
            'analytics_overview' => $this->getAnalyticsOverview(),
            'kpi_metrics' => $this->getKPIMetrics(),
            'trend_analysis' => $this->getTrendAnalysis(),
            'predictive_insights' => $this->getPredictiveInsights(),
            'custom_dashboards' => $this->getCustomDashboards()
        ];

        $this->render('modules/advanced_analytics/dashboard', $data);
    }

    /**
     * Data visualization
     */
    public function dataVisualization() {
        $this->requirePermission('analytics.visualization.view');

        $data = [
            'title' => 'Data Visualization',
            'chart_types' => $this->getChartTypes(),
            'data_sources' => $this->getDataSources(),
            'visualization_templates' => $this->getVisualizationTemplates(),
            'interactive_charts' => $this->getInteractiveCharts(),
            'export_options' => $this->getExportOptions()
        ];

        $this->render('modules/advanced_analytics/data_visualization', $data);
    }

    /**
     * Predictive modeling
     */
    public function predictiveModeling() {
        $this->requirePermission('analytics.predictive.view');

        $data = [
            'title' => 'Predictive Modeling',
            'ml_models' => $this->getMLModels(),
            'model_training' => $this->getModelTraining(),
            'prediction_accuracy' => $this->getPredictionAccuracy(),
            'forecasting_models' => $this->getForecastingModels(),
            'model_validation' => $this->getModelValidation()
        ];

        $this->render('modules/advanced_analytics/predictive_modeling', $data);
    }

    /**
     * Machine learning integration
     */
    public function machineLearning() {
        $this->requirePermission('analytics.ml.view');

        $data = [
            'title' => 'Machine Learning Integration',
            'ml_algorithms' => $this->getMLAlgorithms(),
            'training_datasets' => $this->getTrainingDatasets(),
            'model_deployment' => $this->getModelDeployment(),
            'feature_engineering' => $this->getFeatureEngineering(),
            'model_monitoring' => $this->getModelMonitoring()
        ];

        $this->render('modules/advanced_analytics/machine_learning', $data);
    }

    /**
     * Custom dashboard builder
     */
    public function customDashboards() {
        $this->requirePermission('analytics.dashboards.view');

        $data = [
            'title' => 'Custom Dashboard Builder',
            'dashboard_templates' => $this->getDashboardTemplates(),
            'widget_library' => $this->getWidgetLibrary(),
            'layout_options' => $this->getLayoutOptions(),
            'sharing_permissions' => $this->getSharingPermissions(),
            'dashboard_analytics' => $this->getDashboardAnalytics()
        ];

        $this->render('modules/advanced_analytics/custom_dashboards', $data);
    }

    /**
     * Data export and sharing
     */
    public function dataExport() {
        $this->requirePermission('analytics.export.view');

        $data = [
            'title' => 'Data Export & Sharing',
            'export_formats' => $this->getExportFormats(),
            'scheduled_exports' => $this->getScheduledExports(),
            'data_sharing' => $this->getDataSharing(),
            'api_endpoints' => $this->getAPIEndpoints(),
            'data_catalog' => $this->getDataCatalog()
        ];

        $this->render('modules/advanced_analytics/data_export', $data);
    }

    /**
     * Real-time analytics
     */
    public function realTimeAnalytics() {
        $this->requirePermission('analytics.realtime.view');

        $data = [
            'title' => 'Real-Time Analytics',
            'live_data_streams' => $this->getLiveDataStreams(),
            'real_time_charts' => $this->getRealTimeCharts(),
            'alert_system' => $this->getAlertSystem(),
            'streaming_analytics' => $this->getStreamingAnalytics(),
            'performance_metrics' => $this->getPerformanceMetrics()
        ];

        $this->render('modules/advanced_analytics/real_time_analytics', $data);
    }

    /**
     * Advanced reporting
     */
    public function advancedReporting() {
        $this->requirePermission('analytics.reporting.view');

        $data = [
            'title' => 'Advanced Reporting',
            'report_builder' => $this->getReportBuilder(),
            'scheduled_reports' => $this->getScheduledReports(),
            'report_templates' => $this->getReportTemplates(),
            'data_drilling' => $this->getDataDrilling(),
            'report_distribution' => $this->getReportDistribution()
        ];

        $this->render('modules/advanced_analytics/advanced_reporting', $data);
    }

    /**
     * Statistical analysis
     */
    public function statisticalAnalysis() {
        $this->requirePermission('analytics.statistics.view');

        $data = [
            'title' => 'Statistical Analysis',
            'statistical_tests' => $this->getStatisticalTests(),
            'correlation_analysis' => $this->getCorrelationAnalysis(),
            'regression_models' => $this->getRegressionModels(),
            'hypothesis_testing' => $this->getHypothesisTesting(),
            'confidence_intervals' => $this->getConfidenceIntervals()
        ];

        $this->render('modules/advanced_analytics/statistical_analysis', $data);
    }

    /**
     * AI-powered insights
     */
    public function aiInsights() {
        $this->requirePermission('analytics.ai.view');

        $data = [
            'title' => 'AI-Powered Insights',
            'insight_generation' => $this->getInsightGeneration(),
            'anomaly_detection' => $this->getAnomalyDetection(),
            'pattern_recognition' => $this->getPatternRecognition(),
            'recommendation_engine' => $this->getRecommendationEngine(),
            'natural_language_queries' => $this->getNaturalLanguageQueries()
        ];

        $this->render('modules/advanced_analytics/ai_insights', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getAnalyticsOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT dashboard_id) as total_dashboards,
                COUNT(DISTINCT report_id) as total_reports,
                COUNT(DISTINCT model_id) as total_models,
                SUM(data_points) as total_data_points,
                AVG(accuracy_score) as avg_model_accuracy,
                COUNT(DISTINCT user_id) as active_users
            FROM analytics_overview
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getKPIMetrics() {
        return [
            'revenue_growth' => $this->calculateRevenueGrowth(),
            'customer_acquisition' => $this->calculateCustomerAcquisition(),
            'operational_efficiency' => $this->calculateOperationalEfficiency(),
            'market_share' => $this->calculateMarketShare(),
            'employee_productivity' => $this->calculateEmployeeProductivity(),
            'customer_satisfaction' => $this->calculateCustomerSatisfaction()
        ];
    }

    private function calculateRevenueGrowth() {
        $result = $this->db->query("
            SELECT
                DATE_FORMAT(date, '%Y-%m') as month,
                SUM(revenue) as monthly_revenue
            FROM revenue_data
            WHERE company_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);

        if (count($result) >= 2) {
            $current = end($result)['monthly_revenue'];
            $previous = prev($result)['monthly_revenue'];
            return $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        }

        return 0;
    }

    private function calculateCustomerAcquisition() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN acquisition_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_customers,
                COUNT(*) as total_customers
            FROM customers
            WHERE company_id = ?
        ", [$this->user['company_id']]);

        return $result['total_customers'] > 0 ? ($result['new_customers'] / $result['total_customers']) * 100 : 0;
    }

    private function calculateOperationalEfficiency() {
        $result = $this->db->querySingle("
            SELECT
                AVG(processing_time) as avg_processing_time,
                AVG(cost_per_transaction) as avg_cost
            FROM operational_metrics
            WHERE company_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return [
            'processing_time' => $result['avg_processing_time'] ?? 0,
            'cost_efficiency' => $result['avg_cost'] ?? 0
        ];
    }

    private function calculateMarketShare() {
        $result = $this->db->querySingle("
            SELECT
                company_revenue,
                industry_total_revenue
            FROM market_share_data
            WHERE company_id = ? AND date = (SELECT MAX(date) FROM market_share_data WHERE company_id = ?)
        ", [$this->user['company_id'], $this->user['company_id']]);

        return $result['industry_total_revenue'] > 0 ? ($result['company_revenue'] / $result['industry_total_revenue']) * 100 : 0;
    }

    private function calculateEmployeeProductivity() {
        $result = $this->db->querySingle("
            SELECT
                AVG(tasks_completed) as avg_tasks,
                AVG(hours_worked) as avg_hours
            FROM employee_productivity
            WHERE company_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['avg_hours'] > 0 ? $result['avg_tasks'] / $result['avg_hours'] : 0;
    }

    private function calculateCustomerSatisfaction() {
        $result = $this->db->querySingle("
            SELECT AVG(satisfaction_score) as avg_satisfaction
            FROM customer_feedback
            WHERE company_id = ? AND feedback_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['avg_satisfaction'] ?? 0;
    }

    private function getTrendAnalysis() {
        return $this->db->query("
            SELECT
                metric_name,
                DATE_FORMAT(date, '%Y-%m') as month,
                value,
                trend_direction,
                growth_rate,
                forecast_value
            FROM trend_analysis
            WHERE company_id = ?
            ORDER BY metric_name, date DESC
        ", [$this->user['company_id']]);
    }

    private function getPredictiveInsights() {
        return $this->db->query("
            SELECT
                insight_type,
                insight_description,
                confidence_level,
                impact_level,
                recommended_action,
                generated_at,
                model_used
            FROM predictive_insights
            WHERE company_id = ?
            ORDER BY confidence_level DESC, generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomDashboards() {
        return $this->db->query("
            SELECT
                cd.*,
                cd.dashboard_name,
                cd.description,
                cd.is_public,
                cd.created_by,
                COUNT(cdw.id) as widget_count,
                cd.last_modified
            FROM custom_dashboards cd
            LEFT JOIN custom_dashboard_widgets cdw ON cd.id = cdw.dashboard_id
            WHERE cd.company_id = ?
            GROUP BY cd.id, cd.dashboard_name, cd.description, cd.is_public, cd.created_by, cd.last_modified
            ORDER BY cd.dashboard_name ASC
        ", [$this->user['company_id']]);
    }

    private function getChartTypes() {
        return [
            'line_chart' => 'Line Chart',
            'bar_chart' => 'Bar Chart',
            'pie_chart' => 'Pie Chart',
            'area_chart' => 'Area Chart',
            'scatter_plot' => 'Scatter Plot',
            'histogram' => 'Histogram',
            'heatmap' => 'Heat Map',
            'treemap' => 'Tree Map',
            'radar_chart' => 'Radar Chart',
            'box_plot' => 'Box Plot',
            'candlestick' => 'Candlestick Chart',
            'gauge' => 'Gauge Chart'
        ];
    }

    private function getDataSources() {
        return $this->db->query("
            SELECT
                ds.*,
                ds.source_name,
                ds.source_type,
                ds.connection_status,
                ds.last_sync,
                ds.record_count
            FROM data_sources ds
            WHERE ds.company_id = ?
            ORDER BY ds.source_name ASC
        ", [$this->user['company_id']]);
    }

    private function getVisualizationTemplates() {
        return $this->db->query("
            SELECT
                vt.*,
                vt.template_name,
                vt.category,
                vt.description,
                vt.thumbnail_url,
                COUNT(vu.id) as usage_count
            FROM visualization_templates vt
            LEFT JOIN visualization_usage vu ON vt.id = vu.template_id
            WHERE vt.company_id = ?
            GROUP BY vt.id, vt.template_name, vt.category, vt.description, vt.thumbnail_url
            ORDER BY vt.category ASC, vt.usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getInteractiveCharts() {
        return $this->db->query("
            SELECT
                ic.*,
                ic.chart_name,
                ic.chart_type,
                ic.interactivity_level,
                ic.data_source,
                ic.created_by,
                ic.view_count
            FROM interactive_charts ic
            WHERE ic.company_id = ?
            ORDER BY ic.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getExportOptions() {
        return [
            'pdf' => 'PDF Export',
            'png' => 'PNG Image',
            'svg' => 'SVG Vector',
            'csv' => 'CSV Data',
            'xlsx' => 'Excel Spreadsheet',
            'json' => 'JSON Data',
            'xml' => 'XML Data'
        ];
    }

    private function getMLModels() {
        return $this->db->query("
            SELECT
                ml.*,
                ml.model_name,
                ml.algorithm_type,
                ml.accuracy_score,
                ml.training_status,
                ml.last_trained,
                ml.prediction_count
            FROM ml_models ml
            WHERE ml.company_id = ?
            ORDER BY ml.accuracy_score DESC
        ", [$this->user['company_id']]);
    }

    private function getModelTraining() {
        return $this->db->query("
            SELECT
                mt.*,
                mt.training_job_name,
                mt.model_type,
                mt.training_status,
                mt.progress_percentage,
                mt.estimated_completion,
                mt.training_accuracy
            FROM model_training mt
            WHERE mt.company_id = ?
            ORDER BY mt.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPredictionAccuracy() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(test_date, '%Y-%m') as month,
                model_name,
                AVG(accuracy_score) as avg_accuracy,
                MIN(accuracy_score) as min_accuracy,
                MAX(accuracy_score) as max_accuracy,
                COUNT(*) as test_count
            FROM prediction_accuracy_tests
            WHERE company_id = ?
            GROUP BY DATE_FORMAT(test_date, '%Y-%m'), model_name
            ORDER BY month DESC, avg_accuracy DESC
        ", [$this->user['company_id']]);
    }

    private function getForecastingModels() {
        return $this->db->query("
            SELECT
                fm.*,
                fm.model_name,
                fm.forecast_horizon,
                fm.accuracy_score,
                fm.last_forecast,
                fm.forecast_frequency
            FROM forecasting_models fm
            WHERE fm.company_id = ?
            ORDER BY fm.accuracy_score DESC
        ", [$this->user['company_id']]);
    }

    private function getModelValidation() {
        return $this->db->query("
            SELECT
                mv.*,
                mv.validation_type,
                mv.model_name,
                mv.validation_score,
                mv.validation_date,
                mv.recommendations
            FROM model_validation mv
            WHERE mv.company_id = ?
            ORDER BY mv.validation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getMLAlgorithms() {
        return [
            'linear_regression' => 'Linear Regression',
            'logistic_regression' => 'Logistic Regression',
            'decision_trees' => 'Decision Trees',
            'random_forest' => 'Random Forest',
            'gradient_boosting' => 'Gradient Boosting',
            'neural_networks' => 'Neural Networks',
            'svm' => 'Support Vector Machines',
            'k_means' => 'K-Means Clustering',
            'pca' => 'Principal Component Analysis',
            'time_series' => 'Time Series Analysis'
        ];
    }

    private function getTrainingDatasets() {
        return $this->db->query("
            SELECT
                td.*,
                td.dataset_name,
                td.record_count,
                td.feature_count,
                td.data_quality_score,
                td.last_updated
            FROM training_datasets td
            WHERE td.company_id = ?
            ORDER BY td.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getModelDeployment() {
        return $this->db->query("
            SELECT
                md.*,
                md.model_name,
                md.deployment_status,
                md.endpoint_url,
                md.deployment_date,
                md.prediction_count
            FROM model_deployment md
            WHERE md.company_id = ?
            ORDER BY md.deployment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getFeatureEngineering() {
        return $this->db->query("
            SELECT
                fe.*,
                fe.feature_name,
                fe.feature_type,
                fe.importance_score,
                fe.correlation_coefficient,
                fe.created_at
            FROM feature_engineering fe
            WHERE fe.company_id = ?
            ORDER BY fe.importance_score DESC
        ", [$this->user['company_id']]);
    }

    private function getModelMonitoring() {
        return $this->db->query("
            SELECT
                mm.*,
                mm.model_name,
                mm.monitoring_metric,
                mm.current_value,
                mm.threshold_value,
                mm.alert_status,
                mm.last_checked
            FROM model_monitoring mm
            WHERE mm.company_id = ?
            ORDER BY mm.alert_status DESC, mm.last_checked DESC
        ", [$this->user['company_id']]);
    }

    private function getDashboardTemplates() {
        return $this->db->query("
            SELECT
                dt.*,
                dt.template_name,
                dt.category,
                dt.description,
                dt.widget_count,
                COUNT(du.id) as usage_count
            FROM dashboard_templates dt
            LEFT JOIN dashboard_usage du ON dt.id = du.template_id
            WHERE dt.company_id = ?
            GROUP BY dt.id, dt.template_name, dt.category, dt.description, dt.widget_count
            ORDER BY dt.category ASC, dt.usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getWidgetLibrary() {
        return [
            'kpi_cards' => 'KPI Cards',
            'line_charts' => 'Line Charts',
            'bar_charts' => 'Bar Charts',
            'pie_charts' => 'Pie Charts',
            'tables' => 'Data Tables',
            'gauges' => 'Gauge Charts',
            'heatmaps' => 'Heat Maps',
            'trend_indicators' => 'Trend Indicators',
            'forecast_charts' => 'Forecast Charts',
            'comparison_charts' => 'Comparison Charts'
        ];
    }

    private function getLayoutOptions() {
        return [
            'grid' => 'Grid Layout',
            'masonry' => 'Masonry Layout',
            'flexible' => 'Flexible Layout',
            'responsive' => 'Responsive Layout',
            'custom' => 'Custom Layout'
        ];
    }

    private function getSharingPermissions() {
        return [
            'private' => 'Private',
            'team' => 'Team Only',
            'department' => 'Department',
            'company' => 'Company Wide',
            'public' => 'Public Access'
        ];
    }

    private function getDashboardAnalytics() {
        return $this->db->query("
            SELECT
                da.*,
                da.dashboard_name,
                da.view_count,
                da.avg_load_time,
                da.user_engagement,
                da.last_accessed
            FROM dashboard_analytics da
            WHERE da.company_id = ?
            ORDER BY da.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getExportFormats() {
        return [
            'pdf' => 'PDF Document',
            'excel' => 'Excel Spreadsheet',
            'csv' => 'CSV File',
            'json' => 'JSON Data',
            'xml' => 'XML Data',
            'powerbi' => 'Power BI',
            'tableau' => 'Tableau',
            'api' => 'API Endpoint'
        ];
    }

    private function getScheduledExports() {
        return $this->db->query("
            SELECT
                se.*,
                se.export_name,
                se.frequency,
                se.format,
                se.recipient_emails,
                se.last_run,
                se.next_run
            FROM scheduled_exports se
            WHERE se.company_id = ?
            ORDER BY se.next_run ASC
        ", [$this->user['company_id']]);
    }

    private function getDataSharing() {
        return $this->db->query("
            SELECT
                ds.*,
                ds.share_name,
                ds.share_type,
                ds.recipient_count,
                ds.access_level,
                ds.expiration_date,
                ds.created_at
            FROM data_sharing ds
            WHERE ds.company_id = ?
            ORDER BY ds.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAPIEndpoints() {
        return $this->db->query("
            SELECT
                ae.*,
                ae.endpoint_name,
                ae.endpoint_url,
                ae.method,
                ae.response_format,
                ae.request_count,
                ae.last_accessed
            FROM analytics_api_endpoints ae
            WHERE ae.company_id = ?
            ORDER BY ae.request_count DESC
        ", [$this->user['company_id']]);
    }

    private function getDataCatalog() {
        return $this->db->query("
            SELECT
                dc.*,
                dc.dataset_name,
                dc.category,
                dc.description,
                dc.record_count,
                dc.last_updated,
                dc.popularity_score
            FROM data_catalog dc
            WHERE dc.company_id = ?
            ORDER BY dc.popularity_score DESC
        ", [$this->user['company_id']]);
    }

    private function getLiveDataStreams() {
        return $this->db->query("
            SELECT
                lds.*,
                lds.stream_name,
                lds.data_source,
                lds.update_frequency,
                lds.last_update,
                lds.data_points_per_minute
            FROM live_data_streams lds
            WHERE lds.company_id = ?
            ORDER BY lds.data_points_per_minute DESC
        ", [$this->user['company_id']]);
    }

    private function getRealTimeCharts() {
        return $this->db->query("
            SELECT
                rtc.*,
                rtc.chart_name,
                rtc.chart_type,
                rtc.refresh_interval,
                rtc.data_source,
                rtc.active_connections
            FROM real_time_charts rtc
            WHERE rtc.company_id = ?
            ORDER BY rtc.active_connections DESC
        ", [$this->user['company_id']]);
    }

    private function getAlertSystem() {
        return $this->db->query("
            SELECT
                alert.*,
                alert.alert_name,
                alert.condition,
                alert.threshold,
                alert.current_value,
                alert.status,
                alert.created_at
            FROM analytics_alerts alert
            WHERE alert.company_id = ?
            ORDER BY alert.status DESC, alert.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getStreamingAnalytics() {
        return $this->db->query("
            SELECT
                sa.*,
                sa.metric_name,
                sa.current_value,
                sa.change_percentage,
                sa.trend,
                sa.last_updated
            FROM streaming_analytics sa
            WHERE sa.company_id = ?
            ORDER BY sa.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceMetrics() {
        return $this->db->query("
            SELECT
                pm.*,
                pm.metric_name,
                pm.current_value,
                pm.target_value,
                pm.performance_percentage,
                pm.last_calculated
            FROM performance_metrics pm
            WHERE pm.company_id = ?
            ORDER BY pm.performance_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getReportBuilder() {
        return [
            'data_sources' => $this->getDataSources(),
            'report_templates' => $this->getReportTemplates(),
            'filter_options' => $this->getFilterOptions(),
            'aggregation_functions' => $this->getAggregationFunctions(),
            'formatting_options' => $this->getFormattingOptions()
        ];
    }

    private function getScheduledReports() {
        return $this->db->query("
            SELECT
                sr.*,
                sr.report_name,
                sr.frequency,
                sr.recipient_emails,
                sr.last_generated,
                sr.next_run,
                sr.generation_status
            FROM scheduled_reports sr
            WHERE sr.company_id = ?
            ORDER BY sr.next_run ASC
        ", [$this->user['company_id']]);
    }

    private function getReportTemplates() {
        return $this->db->query("
            SELECT
                rt.*,
                rt.template_name,
                rt.category,
                rt.description,
                rt.field_count,
                COUNT(ru.id) as usage_count
            FROM report_templates rt
            LEFT JOIN report_usage ru ON rt.id = ru.template_id
            WHERE rt.company_id = ?
            GROUP BY rt.id, rt.template_name, rt.category, rt.description, rt.field_count
            ORDER BY rt.category ASC, rt.usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getDataDrilling() {
        return [
            'drill_down' => 'Drill Down',
            'drill_up' => 'Drill Up',
            'drill_through' => 'Drill Through',
            'drill_across' => 'Drill Across',
            'slice_dice' => 'Slice and Dice'
        ];
    }

    private function getReportDistribution() {
        return [
            'email' => 'Email Distribution',
            'dashboard' => 'Dashboard Publishing',
            'api' => 'API Access',
            'download' => 'Download Links',
            'scheduled' => 'Scheduled Delivery'
        ];
    }

    private function getStatisticalTests() {
        return [
            't_test' => 'T-Test',
            'anova' => 'ANOVA',
            'chi_square' => 'Chi-Square Test',
            'correlation' => 'Correlation Analysis',
            'regression' => 'Regression Analysis',
            'mann_whitney' => 'Mann-Whitney U Test',
            'kruskal_wallis' => 'Kruskal-Wallis Test'
        ];
    }

    private function getCorrelationAnalysis() {
        return $this->db->query("
            SELECT
                ca.*,
                ca.variable1,
                ca.variable2,
                ca.correlation_coefficient,
                ca.p_value,
                ca.significance_level,
                ca.calculated_at
            FROM correlation_analysis ca
            WHERE ca.company_id = ?
            ORDER BY ABS(ca.correlation_coefficient) DESC
        ", [$this->user['company_id']]);
    }

    private function getRegressionModels() {
        return $this->db->query("
            SELECT
                rm.*,
                rm.model_name,
                rm.r_squared,
                rm.adjusted_r_squared,
                rm.f_statistic,
                rm.created_at
            FROM regression_models rm
            WHERE rm.company_id = ?
            ORDER BY rm.r_squared DESC
        ", [$this->user['company_id']]);
    }

    private function getHypothesisTesting() {
        return $this->db->query("
            SELECT
                ht.*,
                ht.test_name,
                ht.null_hypothesis,
                ht.alternative_hypothesis,
                ht.p_value,
                ht.test_result,
                ht.conclusion
            FROM hypothesis_tests ht
            WHERE ht.company_id = ?
            ORDER BY ht.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getConfidenceIntervals() {
        return $this->db->query("
            SELECT
                ci.*,
                ci.parameter_name,
                ci.estimate,
                ci.lower_bound,
                ci.upper_bound,
                ci.confidence_level,
                ci.calculated_at
            FROM confidence_intervals ci
            WHERE ci.company_id = ?
            ORDER BY ci.calculated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getInsightGeneration() {
        return $this->db->query("
            SELECT
                ig.*,
                ig.insight_title,
                ig.insight_description,
                ig.confidence_score,
                ig.impact_score,
                ig.generated_at,
                ig.ai_model_used
            FROM insight_generation ig
            WHERE ig.company_id = ?
            ORDER BY ig.confidence_score DESC, ig.generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAnomalyDetection() {
        return $this->db->query("
            SELECT
                ad.*,
                ad.metric_name,
                ad.anomaly_score,
                ad.expected_value,
                ad.actual_value,
                ad.detected_at,
                ad.severity_level
            FROM anomaly_detection ad
            WHERE ad.company_id = ?
            ORDER BY ad.anomaly_score DESC, ad.detected_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPatternRecognition() {
        return $this->db->query("
            SELECT
                pr.*,
                pr.pattern_name,
                pr.pattern_type,
                pr.confidence_level,
                pr.frequency,
                pr.last_observed,
                pr.business_impact
            FROM pattern_recognition pr
            WHERE pr.company_id = ?
            ORDER BY pr.confidence_level DESC, pr.frequency DESC
        ", [$this->user['company_id']]);
    }

    private function getRecommendationEngine() {
        return $this->db->query("
            SELECT
                re.*,
                re.recommendation_type,
                re.recommendation_text,
                re.confidence_score,
                re.expected_impact,
                re.generated_at,
                re.implemented
            FROM recommendation_engine re
            WHERE re.company_id = ?
            ORDER BY re.confidence_score DESC, re.generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getNaturalLanguageQueries() {
        return $this->db->query("
            SELECT
                nlq.*,
                nlq.query_text,
                nlq.parsed_intent,
                nlq.generated_sql,
                nlq.execution_time,
                nlq.result_count,
                nlq.asked_at
            FROM natural_language_queries nlq
            WHERE nlq.company_id = ?
            ORDER BY nlq.asked_at DESC
        ", [$this->user['company_id']]);
    }

    private function getFilterOptions() {
        return [
            'date_range' => 'Date Range',
            'numeric_range' => 'Numeric Range',
            'categorical' => 'Categorical Filters',
            'text_search' => 'Text Search',
            'geographic' => 'Geographic Filters'
        ];
    }

    private function getAggregationFunctions() {
        return [
            'sum' => 'Sum',
            'average' => 'Average',
            'count' => 'Count',
            'min' => 'Minimum',
            'max' => 'Maximum',
            'median' => 'Median',
            'mode' => 'Mode',
            'standard_deviation' => 'Standard Deviation'
        ];
    }

    private function getFormattingOptions() {
        return [
            'conditional_formatting' => 'Conditional Formatting',
            'data_bars' => 'Data Bars',
            'color_scales' => 'Color Scales',
            'icon_sets' => 'Icon Sets',
            'number_formatting' => 'Number Formatting',
            'date_formatting' => 'Date Formatting'
        ];
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function createCustomDashboard() {
        $this->requirePermission('analytics.dashboards.create');

        $data = $this->validateRequest([
            'dashboard_name' => 'required|string',
            'description' => 'string',
            'template_id' => 'integer',
            'widgets' => 'required|array',
            'layout' => 'string',
            'is_public' => 'boolean'
        ]);

        try {
            $dashboardId = $this->db->insert('custom_dashboards', [
                'company_id' => $this->user['company_id'],
                'dashboard_name' => $data['dashboard_name'],
                'description' => $data['description'] ?? '',
                'template_id' => $data['template_id'] ?? null,
                'layout' => $data['layout'] ?? 'grid',
                'is_public' => $data['is_public'] ?? false,
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Add widgets to dashboard
            foreach ($data['widgets'] as $widget) {
                $this->db->insert('custom_dashboard_widgets', [
                    'dashboard_id' => $dashboardId,
                    'widget_type' => $widget['type'],
                    'widget_config' => json_encode($widget['config']),
                    'position_x' => $widget['position']['x'] ?? 0,
                    'position_y' => $widget['position']['y'] ?? 0,
                    'width' => $widget['size']['width'] ?? 4,
                    'height' => $widget['size']['height'] ?? 3
                ]);
            }

            $this->jsonResponse([
                'success' => true,
                'dashboard_id' => $dashboardId,
                'message' => 'Custom dashboard created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateReport() {
        $this->requirePermission('analytics.reporting.generate');

        $data = $this->validateRequest([
            'report_name' => 'required|string',
            'template_id' => 'integer',
            'filters' => 'array',
            'group_by' => 'array',
            'aggregations' => 'array',
            'format' => 'required|string'
        ]);

        try {
            $reportId = $this->db->insert('generated_reports', [
                'company_id' => $this->user['company_id'],
                'report_name' => $data['report_name'],
                'template_id' => $data['template_id'] ?? null,
                'filters' => json_encode($data['filters'] ?? []),
                'group_by' => json_encode($data['group_by'] ?? []),
                'aggregations' => json_encode($data['aggregations'] ?? []),
                'format' => $data['format'],
                'status' => 'generating',
                'generated_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Trigger report generation (this would typically be done asynchronously)
            $this->generateReportData($reportId, $data);

            $this->jsonResponse([
                'success' => true,
                'report_id' => $reportId,
                'message' => 'Report generation started'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateReportData($reportId, $data) {
        // This is a simplified version. In a real implementation, this would be more complex
        // and likely run as a background job

        try {
            // Generate sample report data
            $reportData = [
                'summary' => [
                    'total_records' => rand(1000, 10000),
                    'date_range' => 'Last 30 days',
                    'generated_at' => date('Y-m-d H:i:s')
                ],
                'data' => $this->getSampleReportData(),
                'charts' => $this->getSampleCharts()
            ];

            $this->db->update('generated_reports', [
                'report_data' => json_encode($reportData),
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$reportId]);

        } catch (Exception $e) {
            $this->db->update('generated_reports', [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ], 'id = ?', [$reportId]);
        }
    }

    private function getSampleReportData() {
        // Generate sample data for demonstration
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'id' => $i,
                'category' => 'Category ' . $i,
                'value' => rand(100, 1000),
                'percentage' => rand(1, 100),
                'date' => date('Y-m-d', strtotime("-{$i} days"))
            ];
        }
        return $data;
    }

    private function getSampleCharts() {
        return [
            [
                'type' => 'bar',
                'title' => 'Sample Bar Chart',
                'data' => $this->getSampleReportData()
            ],
            [
                'type' => 'line',
                'title' => 'Sample Line Chart',
                'data' => $this->getSampleReportData()
            ]
        ];
    }

    public function runPredictiveModel() {
        $this->requirePermission('analytics.predictive.run');

        $data = $this->validateRequest([
            'model_id' => 'required|integer',
            'input_data' => 'required|array',
            'prediction_type' => 'required|string'
        ]);

        try {
            // In a real implementation, this would call the actual ML model
            $prediction = $this->runMockPrediction($data);

            // Log prediction
            $this->db->insert('prediction_logs', [
                'company_id' => $this->user['company_id'],
                'model_id' => $data['model_id'],
                'input_data' => json_encode($data['input_data']),
                'prediction_result' => json_encode($prediction),
                'prediction_type' => $data['prediction_type'],
                'user_id' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'prediction' => $prediction,
                'message' => 'Prediction completed successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function runMockPrediction($data) {
        // Mock prediction for demonstration
        return [
            'prediction_value' => rand(1000, 10000),
            'confidence_score' => rand(70, 95) / 100,
            'prediction_range' => [
                'lower' => rand(800, 900),
                'upper' => rand(1100, 1200)
            ],
            'factors' => [
                'trend' => 'increasing',
                'seasonality' => 'moderate',
                'external_factors' => 'positive'
            ]
        ];
    }

    public function exportData() {
        $this->requirePermission('analytics.export.create');

        $data = $this->validateRequest([
            'data_source' => 'required|string',
            'filters' => 'array',
            'format' => 'required|string',
            'include_charts' => 'boolean'
        ]);

        try {
            $exportId = $this->db->insert('data_exports', [
                'company_id' => $this->user['company_id'],
                'data_source' => $data['data_source'],
                'filters' => json_encode($data['filters'] ?? []),
                'format' => $data['format'],
                'include_charts' => $data['include_charts'] ?? false,
                'status' => 'processing',
                'requested_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Trigger export generation (asynchronous in real implementation)
            $this->generateExportFile($exportId, $data);

            $this->jsonResponse([
                'success' => true,
                'export_id' => $exportId,
                'message' => 'Data export initiated'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateExportFile($exportId, $data) {
        try {
            // Generate sample export data
            $exportData = [
                'metadata' => [
                    'exported_at' => date('Y-m-d H:i:s'),
                    'format' => $data['format'],
                    'record_count' => rand(100, 1000)
                ],
                'data' => $this->getSampleExportData(),
                'summary' => [
                    'total_value' => rand(10000, 100000),
                    'average_value' => rand(100, 500),
                    'categories' => ['A', 'B', 'C', 'D']
                ]
            ];

            $this->db->update('data_exports', [
                'export_data' => json_encode($exportData),
                'file_path' => "/exports/export_{$exportId}.{$data['format']}",
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$exportId]);

        } catch (Exception $e) {
            $this->db->update('data_exports', [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ], 'id = ?', [$exportId]);
        }
    }

    private function getSampleExportData() {
        $data = [];
        for ($i = 1; $i <= 50; $i++) {
            $data[] = [
                'id' => $i,
                'name' => "Item {$i}",
                'category' => ['A', 'B', 'C', 'D'][rand(0, 3)],
                'value' => rand(10, 1000),
                'date' => date('Y-m-d', strtotime("-{$i} days"))
            ];
        }
        return $data;
    }

    public function createAlert() {
        $this->requirePermission('analytics.alerts.create');

        $data = $this->validateRequest([
            'alert_name' => 'required|string',
            'metric_name' => 'required|string',
            'condition' => 'required|string',
            'threshold' => 'required|numeric',
            'severity' => 'required|string',
            'notification_channels' => 'array'
        ]);

        try {
            $alertId = $this->db->insert('analytics_alerts', [
                'company_id' => $this->user['company_id'],
                'alert_name' => $data['alert_name'],
                'metric_name' => $data['metric_name'],
                'condition' => $data['condition'],
                'threshold' => $data['threshold'],
                'severity' => $data['severity'],
                'notification_channels' => json_encode($data['notification_channels'] ?? []),
                'is_active' => true,
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'alert_id' => $alertId,
                'message' => 'Alert created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function naturalLanguageQuery() {
        $this->requirePermission('analytics.nlq.query');

        $data = $this->validateRequest([
            'query' => 'required|string',
            'data_context' => 'string'
        ]);

        try {
            // In a real implementation, this would use NLP to parse the query
            $parsedQuery = $this->parseNaturalLanguageQuery($data['query']);

            // Generate SQL based on parsed query
            $generatedSQL = $this->generateSQLFromQuery($parsedQuery);

            // Execute the query
            $result = $this->db->query($generatedSQL, [$this->user['company_id']]);

            // Log the natural language query
            $this->db->insert('natural_language_queries', [
                'company_id' => $this->user['company_id'],
                'user_id' => $this->user['id'],
                'query_text' => $data['query'],
                'parsed_intent' => json_encode($parsedQuery),
                'generated_sql' => $generatedSQL,
                'execution_time' => 0.05, // Mock execution time
                'result_count' => count($result),
                'asked_at' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'query' => $data['query'],
                'parsed_intent' => $parsedQuery,
                'result_count' => count($result),
                'data' => array_slice($result, 0, 100), // Limit results for display
                'message' => 'Natural language query executed successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function parseNaturalLanguageQuery($query) {
        // Mock NLP parsing - in real implementation, this would use actual NLP
        $query = strtolower($query);

        $intent = [
            'action' => 'select',
            'target' => 'data',
            'filters' => [],
            'aggregations' => []
        ];

        // Simple keyword matching for demonstration
        if (strpos($query, 'show') !== false || strpos($query, 'get') !== false) {
            $intent['action'] = 'select';
        }

        if (strpos($query, 'count') !== false) {
            $intent['aggregations'][] = 'count';
        }

        if (strpos($query, 'sum') !== false) {
            $intent['aggregations'][] = 'sum';
        }

        if (strpos($query, 'average') !== false || strpos($query, 'avg') !== false) {
            $intent['aggregations'][] = 'average';
        }

        return $intent;
    }

    private function generateSQLFromQuery($parsedQuery) {
        // Mock SQL generation - in real implementation, this would be more sophisticated
        $baseQuery = "SELECT * FROM analytics_data WHERE company_id = ?";

        if (in_array('count', $parsedQuery['aggregations'])) {
            $baseQuery = "SELECT COUNT(*) as count FROM analytics_data WHERE company_id = ?";
        }

        return $baseQuery;
    }
}
?>
