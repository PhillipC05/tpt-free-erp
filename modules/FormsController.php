<?php
/**
 * TPT Free ERP - Forms Controller
 * Main controller for form management operations
 */

class FormsController extends BaseController {
    private $db;
    private $user;
    private $formManager;
    private $formAnalytics;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->formManager = new FormManager();
        $this->formAnalytics = new FormAnalytics();
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
        $formBuilder = new FormBuilder();

        $data = [
            'title' => 'Form Builder',
            'form_data' => $formId ? $this->formManager->getFormData($formId) : null,
            'form_elements' => $formBuilder->getFormElements(),
            'form_templates' => $this->formManager->getFormTemplates(),
            'validation_rules' => $formBuilder->getValidationRules(),
            'themes' => $formBuilder->getFormThemes(),
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

        $templateManager = new FormTemplateManager();

        $data = [
            'title' => 'Form Templates',
            'template_categories' => $templateManager->getTemplateCategories(),
            'featured_templates' => $templateManager->getFeaturedTemplates(),
            'custom_templates' => $templateManager->getCustomTemplates(),
            'template_usage' => $templateManager->getTemplateUsage(),
            'template_ratings' => $templateManager->getTemplateRatings()
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
}
