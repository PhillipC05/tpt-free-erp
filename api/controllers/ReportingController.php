<?php

namespace TPT\ERP\Api\Controllers;

use TPT\ERP\Core\Response;
use TPT\ERP\Core\Request;
use TPT\ERP\Core\Database;
use TPT\ERP\Modules\Reporting;

/**
 * Reporting API Controller
 * Handles all reporting-related API endpoints
 */
class ReportingController extends BaseController
{
    private $reporting;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->reporting = new Reporting();
        $this->db = Database::getInstance();
    }

    /**
     * Get reporting dashboard overview
     * GET /api/reporting/overview
     */
    public function getOverview()
    {
        try {
            $this->requirePermission('reporting.view');

            $data = [
                'report_overview' => $this->reporting->getReportOverview(),
                'recent_reports' => $this->reporting->getRecentReports(),
                'popular_dashboards' => $this->reporting->getPopularDashboards(),
                'scheduled_reports' => $this->reporting->getScheduledReports(),
                'data_sources' => $this->reporting->getDataSources(),
                'report_categories' => $this->reporting->getReportCategories(),
                'ai_insights' => $this->reporting->getAIInsights(),
                'report_analytics' => $this->reporting->getReportAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get reports with filtering
     * GET /api/reporting/reports
     */
    public function getReports()
    {
        try {
            $this->requirePermission('reporting.view');

            $filters = [
                'category' => $_GET['category'] ?? null,
                'status' => $_GET['status'] ?? null,
                'created_by' => $_GET['created_by'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $reports = $this->reporting->getReports($filters);
            $total = $this->getReportsCount($filters);

            Response::json([
                'reports' => $reports,
                'pagination' => [
                    'page' => $filters['page'],
                    'limit' => $filters['limit'],
                    'total' => $total,
                    'pages' => ceil($total / $filters['limit'])
                ]
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create new report
     * POST /api/reporting/reports
     */
    public function createReport()
    {
        try {
            $this->requirePermission('reporting.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['report_name', 'query_template'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $reportData = [
                'report_name' => trim($data['report_name']),
                'description' => $data['description'] ?? '',
                'category' => $data['category'] ?? 'custom',
                'query_template' => $data['query_template'],
                'parameters' => isset($data['parameters']) ? json_encode($data['parameters']) : null,
                'report_type' => $data['report_type'] ?? 'standard',
                'data_source_id' => $data['data_source_id'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'is_public' => (bool)($data['is_public'] ?? false),
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $reportId = $this->db->insert('reports', $reportData);

            // Log the creation
            $this->logActivity('report_created', 'reports', $reportId, "Report '{$reportData['report_name']}' created");

            Response::json([
                'success' => true,
                'report_id' => $reportId,
                'message' => 'Report created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Generate report
     * POST /api/reporting/reports/{id}/generate
     */
    public function generateReport($id)
    {
        try {
            $this->requirePermission('reporting.view');

            $data = Request::getJsonBody();
            $parameters = $data['parameters'] ?? [];

            // Check if report exists and user has access
            $report = $this->getReportById($id);
            if (!$report) {
                Response::error('Report not found', 404);
                return;
            }

            // Generate the report
            $reportData = $this->reporting->generateReport($id, $parameters);

            Response::json([
                'success' => true,
                'report_data' => $reportData,
                'generated_at' => date('Y-m-d H:i:s'),
                'parameters_used' => $parameters
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Export report
     * GET /api/reporting/reports/{id}/export/{format}
     */
    public function exportReport($id, $format)
    {
        try {
            $this->requirePermission('reporting.view');

            $parameters = $_GET['parameters'] ?? [];

            // Check if report exists
            $report = $this->getReportById($id);
            if (!$report) {
                Response::error('Report not found', 404);
                return;
            }

            // Generate report data
            $reportData = $this->reporting->generateReport($id, $parameters);

            // Export in requested format
            $exportedData = $this->reporting->exportReport($id, $format, $reportData);

            // Set appropriate headers based on format
            switch ($format) {
                case 'pdf':
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="' . $report['report_name'] . '.pdf"');
                    break;
                case 'excel':
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment; filename="' . $report['report_name'] . '.xlsx"');
                    break;
                case 'csv':
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="' . $report['report_name'] . '.csv"');
                    break;
                case 'json':
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="' . $report['report_name'] . '.json"');
                    Response::json($exportedData);
                    return;
            }

            echo $exportedData;
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get dashboards
     * GET /api/reporting/dashboards
     */
    public function getDashboards()
    {
        try {
            $this->requirePermission('reporting.dashboards.view');

            $dashboards = $this->reporting->getUserDashboards();

            Response::json(['dashboards' => $dashboards]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create dashboard
     * POST /api/reporting/dashboards
     */
    public function createDashboard()
    {
        try {
            $this->requirePermission('reporting.dashboards.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['dashboard_name'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $dashboardData = [
                'dashboard_name' => trim($data['dashboard_name']),
                'description' => $data['description'] ?? '',
                'category' => $data['category'] ?? 'general',
                'layout_config' => isset($data['layout_config']) ? json_encode($data['layout_config']) : null,
                'theme_id' => $data['theme_id'] ?? null,
                'is_public' => (bool)($data['is_public'] ?? false),
                'is_favorite' => (bool)($data['is_favorite'] ?? false),
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $dashboardId = $this->db->insert('dashboards', $dashboardData);

            // Log the creation
            $this->logActivity('dashboard_created', 'dashboards', $dashboardId, "Dashboard '{$dashboardData['dashboard_name']}' created");

            Response::json([
                'success' => true,
                'dashboard_id' => $dashboardId,
                'message' => 'Dashboard created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Add widget to dashboard
     * POST /api/reporting/dashboards/{id}/widgets
     */
    public function addWidgetToDashboard($dashboardId)
    {
        try {
            $this->requirePermission('reporting.dashboards.update');

            $data = Request::getJsonBody();

            // Check if dashboard exists and user has access
            $dashboard = $this->getDashboardById($dashboardId);
            if (!$dashboard) {
                Response::error('Dashboard not found', 404);
                return;
            }

            // Validate required fields
            $required = ['widget_type', 'position_x', 'position_y'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $widgetData = [
                'dashboard_id' => $dashboardId,
                'widget_type' => $data['widget_type'],
                'widget_config' => isset($data['widget_config']) ? json_encode($data['widget_config']) : null,
                'position_x' => (int)$data['position_x'],
                'position_y' => (int)$data['position_y'],
                'width' => (int)($data['width'] ?? 4),
                'height' => (int)($data['height'] ?? 3),
                'title' => $data['title'] ?? '',
                'data_source_id' => $data['data_source_id'] ?? null,
                'refresh_interval' => (int)($data['refresh_interval'] ?? 300),
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $widgetId = $this->db->insert('dashboard_widgets', $widgetData);

            // Log the creation
            $this->logActivity('widget_added', 'dashboard_widgets', $widgetId, "Widget added to dashboard {$dashboard['dashboard_name']}");

            Response::json([
                'success' => true,
                'widget_id' => $widgetId,
                'message' => 'Widget added successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get data sources
     * GET /api/reporting/data-sources
     */
    public function getDataSources()
    {
        try {
            $this->requirePermission('reporting.view');

            $dataSources = $this->reporting->getDataSources();

            Response::json(['data_sources' => $dataSources]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create data source
     * POST /api/reporting/data-sources
     */
    public function createDataSource()
    {
        try {
            $this->requirePermission('reporting.data_sources.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['source_name', 'source_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $sourceData = [
                'source_name' => trim($data['source_name']),
                'source_type' => $data['source_type'],
                'description' => $data['description'] ?? '',
                'connection_string' => $data['connection_string'] ?? '',
                'connection_config' => isset($data['connection_config']) ? json_encode($data['connection_config']) : null,
                'is_active' => (bool)($data['is_active'] ?? true),
                'sync_frequency' => $data['sync_frequency'] ?? 'daily',
                'last_sync' => null,
                'sync_status' => 'pending',
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $sourceId = $this->db->insert('data_sources', $sourceData);

            // Log the creation
            $this->logActivity('data_source_created', 'data_sources', $sourceId, "Data source '{$sourceData['source_name']}' created");

            Response::json([
                'success' => true,
                'data_source_id' => $sourceId,
                'message' => 'Data source created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Schedule report
     * POST /api/reporting/reports/{id}/schedule
     */
    public function scheduleReport($id)
    {
        try {
            $this->requirePermission('reporting.scheduler.create');

            $data = Request::getJsonBody();

            // Check if report exists
            $report = $this->getReportById($id);
            if (!$report) {
                Response::error('Report not found', 404);
                return;
            }

            // Validate required fields
            $required = ['schedule_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $scheduleData = [
                'report_id' => $id,
                'schedule_name' => $data['schedule_name'] ?? $report['report_name'] . ' Schedule',
                'schedule_type' => $data['schedule_type'],
                'schedule_config' => isset($data['schedule_config']) ? json_encode($data['schedule_config']) : null,
                'delivery_method' => $data['delivery_method'] ?? 'email',
                'recipient_list' => isset($data['recipient_list']) ? json_encode($data['recipient_list']) : null,
                'parameters' => isset($data['parameters']) ? json_encode($data['parameters']) : null,
                'export_format' => $data['export_format'] ?? 'pdf',
                'is_active' => (bool)($data['is_active'] ?? true),
                'next_run' => $this->calculateNextRun($data),
                'last_run' => null,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $scheduleId = $this->db->insert('scheduled_reports', $scheduleData);

            // Log the scheduling
            $this->logActivity('report_scheduled', 'scheduled_reports', $scheduleId, "Report '{$report['report_name']}' scheduled");

            Response::json([
                'success' => true,
                'schedule_id' => $scheduleId,
                'message' => 'Report scheduled successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Share report
     * POST /api/reporting/reports/{id}/share
     */
    public function shareReport($id)
    {
        try {
            $this->requirePermission('reporting.sharing.create');

            $data = Request::getJsonBody();

            // Check if report exists
            $report = $this->getReportById($id);
            if (!$report) {
                Response::error('Report not found', 404);
                return;
            }

            // Validate required fields
            if (!isset($data['recipients']) || !is_array($data['recipients'])) {
                Response::error('Recipients array is required', 400);
                return;
            }

            $sharedReports = [];
            foreach ($data['recipients'] as $recipient) {
                $shareData = [
                    'report_id' => $id,
                    'shared_with' => $recipient['user_id'],
                    'permission_level' => $recipient['permission_level'] ?? 'view',
                    'expires_at' => $recipient['expires_at'] ?? null,
                    'message' => $recipient['message'] ?? '',
                    'company_id' => $this->user['company_id'],
                    'shared_by' => $this->user['id'],
                    'shared_at' => date('Y-m-d H:i:s')
                ];

                $shareId = $this->db->insert('shared_reports', $shareData);
                $sharedReports[] = $shareId;
            }

            // Log the sharing
            $this->logActivity('report_shared', 'shared_reports', null, "Report '{$report['report_name']}' shared with " . count($data['recipients']) . " users");

            Response::json([
                'success' => true,
                'shared_reports' => $sharedReports,
                'message' => 'Report shared successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get AI insights
     * GET /api/reporting/ai-insights
     */
    public function getAIInsights()
    {
        try {
            $this->requirePermission('reporting.ai.view');

            $insights = $this->reporting->getAIInsights();

            Response::json(['insights' => $insights]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Generate AI insight
     * POST /api/reporting/ai-insights/generate
     */
    public function generateAIInsight()
    {
        try {
            $this->requirePermission('reporting.ai.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['data_source_id', 'insight_type'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Generate AI insight
            $insight = $this->reporting->generateAIInsight($data['data_source_id'], $data['insight_type']);

            Response::json([
                'success' => true,
                'insight' => $insight,
                'message' => 'AI insight generated successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get report analytics
     * GET /api/reporting/analytics
     */
    public function getAnalytics()
    {
        try {
            $this->requirePermission('reporting.analytics.view');

            $data = [
                'usage_analytics' => $this->reporting->getUsageAnalytics(),
                'performance_metrics' => $this->reporting->getPerformanceMetrics(),
                'user_engagement' => $this->reporting->getUserEngagement(),
                'report_effectiveness' => $this->reporting->getReportEffectiveness(),
                'data_quality_metrics' => $this->reporting->getDataQualityMetrics(),
                'system_performance' => $this->reporting->getSystemPerformance(),
                'roi_analysis' => $this->reporting->getROIAnalysis(),
                'benchmarking' => $this->reporting->getBenchmarking()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Bulk update reports
     * POST /api/reporting/reports/bulk-update
     */
    public function bulkUpdateReports()
    {
        try {
            $this->requirePermission('reporting.update');

            $data = Request::getJsonBody();

            if (!isset($data['report_ids']) || !is_array($data['report_ids'])) {
                Response::error('Report IDs array is required', 400);
                return;
            }

            if (empty($data['updates'])) {
                Response::error('Updates object is required', 400);
                return;
            }

            $reportIds = $data['report_ids'];
            $updates = $data['updates'];

            // Start transaction
            $this->db->beginTransaction();

            try {
                $updateCount = 0;

                foreach ($reportIds as $reportId) {
                    $report = $this->getReportById($reportId);
                    if (!$report) continue;

                    $updateData = [];
                    $allowedFields = [
                        'category', 'status', 'is_public', 'description'
                    ];

                    foreach ($allowedFields as $field) {
                        if (isset($updates[$field])) {
                            $updateData[$field] = $updates[$field];
                        }
                    }

                    if (!empty($updateData)) {
                        $updateData['updated_by'] = $this->user['id'];
                        $updateData['updated_at'] = date('Y-m-d H:i:s');

                        $this->db->update('reports', $updateData, ['id' => $reportId]);
                        $updateCount++;
                    }
                }

                $this->db->commit();

                // Log bulk update
                $this->logActivity('bulk_report_update', 'reports', null, "Bulk updated {$updateCount} reports");

                Response::json([
                    'success' => true,
                    'updated_count' => $updateCount,
                    'message' => "{$updateCount} reports updated successfully"
                ]);
            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getReportById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM reports WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function getDashboardById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM dashboards WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function calculateNextRun($scheduleConfig)
    {
        $now = new DateTime();

        switch ($scheduleConfig['schedule_type']) {
            case 'daily':
                $time = $scheduleConfig['time'] ?? '09:00';
                $nextRun = new DateTime($now->format('Y-m-d') . ' ' . $time);
                if ($nextRun <= $now) {
                    $nextRun->modify('+1 day');
                }
                return $nextRun->format('Y-m-d H:i:s');

            case 'weekly':
                $day = $scheduleConfig['day'] ?? 'monday';
                $time = $scheduleConfig['time'] ?? '09:00';
                $nextRun = new DateTime('next ' . $day . ' ' . $time);
                return $nextRun->format('Y-m-d H:i:s');

            case 'monthly':
                $day = $scheduleConfig['day'] ?? 1;
                $time = $scheduleConfig['time'] ?? '09:00';
                $nextRun = new DateTime('first day of next month');
                $nextRun->setTime((int)substr($time, 0, 2), (int)substr($time, 3, 2));
                if ($day > 1) {
                    $nextRun->modify('+' . ($day - 1) . ' days');
                }
                return $nextRun->format('Y-m-d H:i:s');

            default:
                return $now->format('Y-m-d H:i:s');
        }
    }

    private function getReportsCount($filters)
    {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['category']) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['created_by']) {
            $where[] = "created_by = ?";
            $params[] = $filters['created_by'];
        }

        if ($filters['date_from']) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(report_name LIKE ? OR description LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM reports WHERE $whereClause", $params);
    }

    private function logActivity($action, $table, $recordId, $description)
    {
        $this->db->insert('reporting_activities', [
            'user_id' => $this->user['id'],
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'description' => $description,
            'company_id' => $this->user['company_id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
