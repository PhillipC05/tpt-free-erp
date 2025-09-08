<?php
/**
 * TPT Free ERP - Forms Controller
 * Main controller for form management operations
 *
 * This module has been refactored into smaller, focused classes:
 * - FormManager: Form CRUD operations
 * - FormBuilder: Form building and element management
 * - FormAnalytics: Analytics and reporting
 * - FormTemplateManager: Template management
 * - FormSubmissionManager: Submission handling
 * - FormValidator: Validation logic
 */

class Forms extends BaseController {
    private $db;
    private $user;
    private $formManager;
    private $formBuilder;
    private $formAnalytics;
    private $formTemplateManager;
    private $formSubmissionManager;
    private $formValidator;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->formManager = new FormManager();
        $this->formBuilder = new FormBuilder();
        $this->formAnalytics = new FormAnalytics();
        $this->formTemplateManager = new FormTemplateManager();
        $this->formSubmissionManager = new FormSubmissionManager();
        $this->formValidator = new FormValidator();
    }

    /**
     * Main forms dashboard
     */
    public function index() {
        $this->requirePermission('forms.view');

        $data = [
            'title' => 'Dynamic Forms Dashboard',
            'forms_overview' => $this->formAnalytics->getFormsOverview(),
            'recent_forms' => $this->formManager->getRecentForms(),
            'form_analytics' => $this->formAnalytics->getFormAnalyticsSummary(),
            'popular_templates' => $this->formManager->getPopularTemplates(),
            'submission_trends' => $this->formAnalytics->getSubmissionTrends(),
            'form_performance' => $this->formAnalytics->getFormPerformance()
        ];

        $this->render('modules/forms/dashboard', $data);
    }

    /**
     * Form builder
     */
    public function builder() {
        $this->requirePermission('forms.builder.view');

        $formId = $_GET['form_id'] ?? null;

        $data = [
            'title' => 'Form Builder',
            'form_data' => $formId ? $this->formManager->getFormData($formId) : null,
            'form_elements' => $this->formBuilder->getFormElements(),
            'form_templates' => $this->formManager->getFormTemplates(),
            'validation_rules' => $this->formBuilder->getValidationRules(),
            'themes' => $this->formBuilder->getFormThemes(),
            'integrations' => $this->formManager->getFormIntegrations()
        ];

        $this->render('modules/forms/builder', $data);
    }

    /**
     * Form management
     */
    public function management() {
        $this->requirePermission('forms.management.view');

        $data = [
            'title' => 'Form Management',
            'all_forms' => $this->formManager->getAllForms(),
            'form_categories' => $this->formManager->getFormCategories(),
            'form_status' => $this->formManager->getFormStatus(),
            'form_permissions' => $this->formManager->getFormPermissions(),
            'bulk_actions' => $this->formManager->getBulkActions(),
            'export_options' => $this->formManager->getExportOptions()
        ];

        $this->render('modules/forms/management', $data);
    }

    /**
     * Form analytics
     */
    public function analytics() {
        $this->requirePermission('forms.analytics.view');

        $formId = $_GET['form_id'] ?? null;

        $data = [
            'title' => 'Form Analytics',
            'form_analytics' => $this->formAnalytics->getDetailedFormAnalytics($formId),
            'field_analytics' => $this->formAnalytics->getFieldAnalytics($formId),
            'conversion_funnel' => $this->formAnalytics->getConversionFunnel($formId),
            'user_journey' => $this->formAnalytics->getUserJourney($formId),
            'performance_metrics' => $this->formAnalytics->getPerformanceMetrics($formId),
            'comparison_reports' => $this->formAnalytics->getComparisonReports()
        ];

        $this->render('modules/forms/analytics', $data);
    }

    /**
     * Form templates
     */
    public function templates() {
        $this->requirePermission('forms.templates.view');

        $data = [
            'title' => 'Form Templates',
            'template_categories' => $this->formTemplateManager->getTemplateCategories(),
            'featured_templates' => $this->formTemplateManager->getFeaturedTemplates(),
            'custom_templates' => $this->formTemplateManager->getCustomTemplates(),
            'template_usage' => $this->formTemplateManager->getTemplateUsage(),
            'template_ratings' => $this->formTemplateManager->getTemplateRatings()
        ];

        $this->render('modules/forms/templates', $data);
    }

    /**
     * Form workflows
     */
    public function workflows() {
        $this->requirePermission('forms.workflows.view');

        $data = [
            'title' => 'Form Workflows',
            'workflow_templates' => $this->formManager->getWorkflowTemplates(),
            'active_workflows' => $this->formManager->getActiveWorkflows(),
            'workflow_analytics' => $this->formAnalytics->getWorkflowAnalytics(),
            'approval_processes' => $this->formManager->getApprovalProcesses(),
            'notification_rules' => $this->formManager->getNotificationRules()
        ];

        $this->render('modules/forms/workflows', $data);
    }

    /**
     * Auto-generate reports for new forms
     */
    public function autoGenerateReports($formId) {
        $this->requirePermission('forms.edit');

        $form = $this->formManager->getForm($formId);
        if (!$form) {
            throw new Exception("Form not found");
        }

        $reports = [];

        // Generate basic analytics report
        $reports[] = $this->formAnalytics->generateBasicAnalyticsReport($form);

        // Generate field performance report
        $reports[] = $this->formAnalytics->generateFieldPerformanceReport($form);

        // Generate submission trends report
        $reports[] = $this->formAnalytics->generateSubmissionTrendsReport($form);

        // Generate completion analysis report
        $reports[] = $this->formAnalytics->generateCompletionAnalysisReport($form);

        // Generate dashboard for form
        $dashboard = $this->formAnalytics->generateFormDashboard($form);

        return [
            'reports' => $reports,
            'dashboard' => $dashboard,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Optimize form based on analytics
     */
    public function optimizeForm($formId) {
        $this->requirePermission('forms.edit');

        $form = $this->formManager->getForm($formId);
        if (!$form) {
            throw new Exception("Form not found");
        }

        $analytics = $this->formAnalytics->getFormAnalytics($formId);
        $optimizer = new ReportOptimizer();

        // Analyze form performance
        $analysis = $optimizer->analyzeReport($this->formAnalytics->getFormReportId($formId));

        $optimizations = [
            'field_suggestions' => $this->formAnalytics->suggestFieldImprovements($analytics),
            'layout_suggestions' => $this->formAnalytics->suggestLayoutImprovements($analytics),
            'content_suggestions' => $this->formAnalytics->suggestContentImprovements($analytics),
            'technical_optimizations' => $analysis['performance_optimizations'] ?? [],
            'accessibility_improvements' => $analysis['accessibility_improvements'] ?? []
        ];

        return $optimizations;
    }

    /**
     * Export form data to BI tools
     */
    public function exportToBITool($formId, $toolName, $options = []) {
        $this->requirePermission('forms.export');

        $biIntegration = new BIToolIntegration();

        // Get form submissions data
        $dataSource = $this->getFormDataSource($formId);

        // Export to specified BI tool
        $result = $biIntegration->exportToTool($toolName, $dataSource, $options);

        // Log export activity
        $this->logBIExport($formId, $toolName, $result);

        return $result;
    }

    /**
     * Create embedded BI dashboard for form
     */
    public function createEmbeddedDashboard($formId, $toolName, $dashboardConfig) {
        $this->requirePermission('forms.edit');

        $biIntegration = new BIToolIntegration();

        // Create embedded dashboard
        $result = $biIntegration->createEmbeddedDashboard($toolName, $dashboardConfig);

        // Store embedded dashboard info
        $this->storeEmbeddedDashboard($formId, $result);

        return $result;
    }

    /**
     * Sync form data with BI tools
     */
    public function syncWithBITool($formId, $toolName, $direction = 'to_bi', $options = []) {
        $this->requirePermission('forms.sync');

        $biIntegration = new BIToolIntegration();
        $dataSource = $this->getFormDataSource($formId);

        // Sync data
        $result = $biIntegration->syncData($toolName, $direction, $dataSource, $options);

        // Log sync activity
        $this->logBISync($formId, $toolName, $direction, $result);

        return $result;
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function createForm() {
        $this->requirePermission('forms.create');

        $data = $this->validateRequest([
            'form_title' => 'required|string',
            'description' => 'string',
            'category' => 'string',
            'template_id' => 'integer',
            'settings' => 'array'
        ]);

        try {
            $formId = $this->formManager->createForm($data);
            $this->jsonResponse([
                'success' => true,
                'form_id' => $formId,
                'message' => 'Form created successfully'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function saveFormField() {
        $this->requirePermission('forms.edit');

        $data = $this->validateRequest([
            'form_id' => 'required|integer',
            'field_name' => 'required|string',
            'field_label' => 'required|string',
            'field_type' => 'required|string',
            'field_order' => 'integer',
            'is_required' => 'boolean',
            'field_options' => 'array',
            'validation_rules' => 'array'
        ]);

        try {
            $result = $this->formBuilder->saveFormField($data);
            $this->jsonResponse([
                'success' => true,
                'field_id' => $result,
                'message' => 'Form field saved successfully'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function publishForm() {
        $this->requirePermission('forms.publish');

        $data = $this->validateRequest([
            'form_id' => 'required|integer'
        ]);

        try {
            $this->formManager->publishForm($data['form_id']);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Form published successfully'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function submitForm() {
        $data = $this->validateRequest([
            'form_id' => 'required|integer',
            'submission_data' => 'required|array',
            'device_info' => 'array'
        ]);

        try {
            $result = $this->formSubmissionManager->submitForm($data);
            $this->jsonResponse([
                'success' => true,
                'submission_id' => $result,
                'message' => 'Form submitted successfully'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportSubmissions() {
        $this->requirePermission('forms.export');

        $data = $this->validateRequest([
            'form_id' => 'required|integer',
            'format' => 'required|string',
            'filters' => 'array',
            'fields' => 'array'
        ]);

        try {
            $result = $this->formSubmissionManager->exportSubmissions($data);
            $this->jsonResponse([
                'success' => true,
                'export_id' => $result['export_id'],
                'data' => $result['data'],
                'message' => 'Submissions exported successfully'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createTemplate() {
        $this->requirePermission('forms.templates.create');

        $data = $this->validateRequest([
            'template_name' => 'required|string',
            'description' => 'string',
            'category' => 'required|string',
            'form_id' => 'required|integer',
            'is_public' => 'boolean'
        ]);

        try {
            $templateId = $this->formTemplateManager->createTemplate($data);
            $this->jsonResponse([
                'success' => true,
                'template_id' => $templateId,
                'message' => 'Template created successfully'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteForm() {
        $this->requirePermission('forms.delete');

        $data = $this->validateRequest([
            'form_id' => 'required|integer'
        ]);

        try {
            $this->formManager->deleteForm($data['form_id']);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Form deleted successfully'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function duplicateForm() {
        $this->requirePermission('forms.duplicate');

        $data = $this->validateRequest([
            'form_id' => 'required|integer',
            'new_title' => 'required|string'
        ]);

        try {
            $formId = $this->formManager->duplicateForm($data['form_id'], $data['new_title']);
            $this->jsonResponse([
                'success' => true,
                'form_id' => $formId,
                'message' => 'Form duplicated successfully'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rateTemplate() {
        $data = $this->validateRequest([
            'template_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'string'
        ]);

        try {
            $this->formTemplateManager->rateTemplate($data);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Template rated successfully'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFormAnalyticsData() {
        $formId = $_GET['form_id'] ?? null;
        $timeRange = $_GET['range'] ?? '30d';

        if (!$formId) {
            $this->jsonResponse(['error' => 'Form ID required'], 400);
        }

        $analytics = $this->formAnalytics->getDetailedFormAnalytics($formId);

        $this->jsonResponse([
            'success' => true,
            'analytics' => $analytics,
            'time_range' => $timeRange
        ]);
    }

    public function bulkUpdateForms() {
        $this->requirePermission('forms.bulk_update');

        $data = $this->validateRequest([
            'form_ids' => 'required|array',
            'action' => 'required|string',
            'parameters' => 'array'
        ]);

        try {
            $result = $this->formManager->bulkUpdateForms($data);
            $this->jsonResponse([
                'success' => true,
                'updated_count' => $result,
                'message' => "Successfully updated $result forms"
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================================================
    // PRIVATE HELPER METHODS
    // ============================================================================

    private function getFormDataSource($formId) {
        return 'form_submissions_' . $formId;
    }

    private function logBIExport($formId, $toolName, $result) {
        $this->db->insert('bi_export_logs', [
            'company_id' => $this->user['company_id'],
            'form_id' => $formId,
            'tool_name' => $toolName,
            'export_result' => json_encode($result),
            'exported_by' => $this->user['id'],
            'exported_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function storeEmbeddedDashboard($formId, $result) {
        $this->db->insert('embedded_dashboards', [
            'company_id' => $this->user['company_id'],
            'form_id' => $formId,
            'tool_name' => $result['embed_type'],
            'dashboard_name' => 'Form Analytics Dashboard',
            'embed_url' => $result['embed_url'],
            'embed_config' => json_encode($result),
            'created_by' => $this->user['id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function logBISync($formId, $toolName, $direction, $result) {
        $this->db->insert('bi_sync_logs', [
            'company_id' => $this->user['company_id'],
            'form_id' => $formId,
            'tool_name' => $toolName,
            'direction' => $direction,
            'sync_result' => json_encode($result),
            'synced_by' => $this->user['id'],
            'synced_at' => date('Y-m-d H:i:s')
        ]);
    }
}
