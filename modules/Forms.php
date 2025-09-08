<?php
/**
 * TPT Free ERP - Dynamic Forms Module
 * Complete form builder, submission management, and analytics system
 */

class Forms extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main forms dashboard
     */
    public function index() {
        $this->requirePermission('forms.view');

        $data = [
            'title' => 'Dynamic Forms Dashboard',
            'forms_overview' => $this->getFormsOverview(),
            'recent_forms' => $this->getRecentForms(),
            'form_analytics' => $this->getFormAnalytics(),
            'popular_templates' => $this->getPopularTemplates(),
            'submission_trends' => $this->getSubmissionTrends(),
            'form_performance' => $this->getFormPerformance()
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
            'form_data' => $formId ? $this->getFormData($formId) : null,
            'form_elements' => $this->getFormElements(),
            'form_templates' => $this->getFormTemplates(),
            'validation_rules' => $this->getValidationRules(),
            'themes' => $this->getFormThemes(),
            'integrations' => $this->getFormIntegrations()
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
            'all_forms' => $this->getAllForms(),
            'form_categories' => $this->getFormCategories(),
            'form_status' => $this->getFormStatus(),
            'form_permissions' => $this->getFormPermissions(),
            'bulk_actions' => $this->getBulkActions(),
            'export_options' => $this->getExportOptions()
        ];

        $this->render('modules/forms/management', $data);
    }



    /**
     * Auto-generate reports for new forms
     */
    public function autoGenerateReports($formId) {
        $this->requirePermission('forms.edit');

        $form = $this->getForm($formId);
        if (!$form) {
            throw new Exception("Form not found");
        }

        $reports = [];

        // Generate basic analytics report
        $reports[] = $this->generateBasicAnalyticsReport($form);

        // Generate field performance report
        $reports[] = $this->generateFieldPerformanceReport($form);

        // Generate submission trends report
        $reports[] = $this->generateSubmissionTrendsReport($form);

        // Generate completion analysis report
        $reports[] = $this->generateCompletionAnalysisReport($form);

        // Generate dashboard for form
        $dashboard = $this->generateFormDashboard($form);

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

        $form = $this->getForm($formId);
        if (!$form) {
            throw new Exception("Form not found");
        }

        $analytics = $this->getFormAnalytics($formId);
        $optimizer = new ReportOptimizer();

        // Analyze form performance
        $analysis = $optimizer->analyzeReport($this->getFormReportId($formId));

        $optimizations = [
            'field_suggestions' => $this->suggestFieldImprovements($analytics),
            'layout_suggestions' => $this->suggestLayoutImprovements($analytics),
            'content_suggestions' => $this->suggestContentImprovements($analytics),
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

    /**
     * Form analytics
     */
    public function analytics() {
        $this->requirePermission('forms.analytics.view');

        $formId = $_GET['form_id'] ?? null;

        $data = [
            'title' => 'Form Analytics',
            'form_analytics' => $this->getDetailedFormAnalytics($formId),
            'field_analytics' => $this->getFieldAnalytics($formId),
            'conversion_funnel' => $this->getConversionFunnel($formId),
            'user_journey' => $this->getUserJourney($formId),
            'performance_metrics' => $this->getPerformanceMetrics($formId),
            'comparison_reports' => $this->getComparisonReports()
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
            'template_categories' => $this->getTemplateCategories(),
            'featured_templates' => $this->getFeaturedTemplates(),
            'custom_templates' => $this->getCustomTemplates(),
            'template_usage' => $this->getTemplateUsage(),
            'template_ratings' => $this->getTemplateRatings()
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
            'workflow_templates' => $this->getWorkflowTemplates(),
            'active_workflows' => $this->getActiveWorkflows(),
            'workflow_analytics' => $this->getWorkflowAnalytics(),
            'approval_processes' => $this->getApprovalProcesses(),
            'notification_rules' => $this->getNotificationRules()
        ];

        $this->render('modules/forms/workflows', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getFormsOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_forms,
                COUNT(CASE WHEN status = 'published' THEN 1 END) as published_forms,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_forms,
                COUNT(CASE WHEN status = 'archived' THEN 1 END) as archived_forms,
                SUM(total_submissions) as total_submissions,
                AVG(completion_rate) as avg_completion_rate,
                MAX(last_submission) as last_submission_date,
                COUNT(DISTINCT created_by) as active_creators
            FROM forms
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRecentForms() {
        return $this->db->query("
            SELECT
                f.*,
                f.form_title,
                f.description,
                f.status,
                f.total_submissions,
                f.last_submission,
                f.created_at,
                u.first_name,
                u.last_name,
                COUNT(ff.id) as field_count
            FROM forms f
            LEFT JOIN users u ON f.created_by = u.id
            LEFT JOIN form_fields ff ON f.id = ff.form_id
            WHERE f.company_id = ?
            GROUP BY f.id
            ORDER BY f.created_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getFormAnalytics() {
        return $this->db->querySingle("
            SELECT
                AVG(completion_rate) as avg_completion_rate,
                AVG(time_to_complete) as avg_time_to_complete,
                SUM(abandonment_rate) as total_abandonment_rate,
                COUNT(CASE WHEN mobile_optimized = true THEN 1 END) as mobile_optimized_forms,
                AVG(conversion_rate) as avg_conversion_rate,
                MAX(peak_submission_hour) as peak_hour,
                COUNT(DISTINCT template_used) as templates_used
            FROM form_analytics
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getPopularTemplates() {
        return $this->db->query("
            SELECT
                ft.*,
                ft.template_name,
                ft.category,
                ft.usage_count,
                ft.avg_rating,
                ft.last_used,
                COUNT(f.id) as forms_created
            FROM form_templates ft
            LEFT JOIN forms f ON ft.id = f.template_id
            WHERE ft.company_id = ?
            GROUP BY ft.id
            ORDER BY ft.usage_count DESC
            LIMIT 6
        ", [$this->user['company_id']]);
    }

    private function getSubmissionTrends() {
        return $this->db->query("
            SELECT
                DATE(created_at) as submission_date,
                COUNT(*) as submission_count,
                AVG(completion_time_seconds) as avg_completion_time,
                COUNT(DISTINCT user_id) as unique_submitters
            FROM form_submissions
            WHERE company_id = ? AND created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY submission_date DESC
            LIMIT 30
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getFormPerformance() {
        return $this->db->query("
            SELECT
                f.form_title,
                f.total_submissions,
                f.completion_rate,
                f.avg_completion_time,
                f.conversion_rate,
                f.last_submission,
                RANK() OVER (ORDER BY f.total_submissions DESC) as popularity_rank
            FROM forms f
            WHERE f.company_id = ? AND f.status = 'published'
            ORDER BY f.total_submissions DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getFormData($formId) {
        $form = $this->db->querySingle("
            SELECT * FROM forms WHERE id = ? AND company_id = ?
        ", [$formId, $this->user['company_id']]);

        if ($form) {
            $form['fields'] = $this->db->query("
                SELECT * FROM form_fields
                WHERE form_id = ? ORDER BY field_order ASC
            ", [$formId]);

            $form['settings'] = $this->db->querySingle("
                SELECT * FROM form_settings WHERE form_id = ?
            ", [$formId]);
        }

        return $form;
    }

    private function getFormElements() {
        return [
            'text' => [
                'name' => 'Text Input',
                'icon' => 'text-input',
                'description' => 'Single line text input',
                'properties' => ['placeholder', 'maxlength', 'pattern']
            ],
            'textarea' => [
                'name' => 'Text Area',
                'icon' => 'textarea',
                'description' => 'Multi-line text input',
                'properties' => ['placeholder', 'rows', 'maxlength']
            ],
            'select' => [
                'name' => 'Select Dropdown',
                'icon' => 'select',
                'description' => 'Dropdown selection',
                'properties' => ['options', 'multiple', 'placeholder']
            ],
            'radio' => [
                'name' => 'Radio Buttons',
                'icon' => 'radio',
                'description' => 'Single choice selection',
                'properties' => ['options', 'orientation']
            ],
            'checkbox' => [
                'name' => 'Checkboxes',
                'icon' => 'checkbox',
                'description' => 'Multiple choice selection',
                'properties' => ['options', 'orientation']
            ],
            'number' => [
                'name' => 'Number Input',
                'icon' => 'number',
                'description' => 'Numeric input with validation',
                'properties' => ['min', 'max', 'step', 'placeholder']
            ],
            'date' => [
                'name' => 'Date Picker',
                'icon' => 'calendar',
                'description' => 'Date selection',
                'properties' => ['format', 'min_date', 'max_date']
            ],
            'time' => [
                'name' => 'Time Picker',
                'icon' => 'clock',
                'description' => 'Time selection',
                'properties' => ['format', 'interval']
            ],
            'file' => [
                'name' => 'File Upload',
                'icon' => 'upload',
                'description' => 'File attachment',
                'properties' => ['accepted_types', 'max_size', 'multiple']
            ],
            'email' => [
                'name' => 'Email Input',
                'icon' => 'email',
                'description' => 'Email address input',
                'properties' => ['placeholder', 'confirmation']
            ],
            'phone' => [
                'name' => 'Phone Input',
                'icon' => 'phone',
                'description' => 'Phone number input',
                'properties' => ['format', 'country_code']
            ],
            'address' => [
                'name' => 'Address Field',
                'icon' => 'map',
                'description' => 'Complete address input',
                'properties' => ['components', 'validation']
            ],
            'rating' => [
                'name' => 'Rating Scale',
                'icon' => 'star',
                'description' => 'Star rating input',
                'properties' => ['max_rating', 'shape', 'color']
            ],
            'slider' => [
                'name' => 'Slider',
                'icon' => 'slider',
                'description' => 'Range slider input',
                'properties' => ['min', 'max', 'step', 'orientation']
            ],
            'signature' => [
                'name' => 'Signature Pad',
                'icon' => 'signature',
                'description' => 'Digital signature capture',
                'properties' => ['width', 'height', 'format']
            ]
        ];
    }

    private function getFormTemplates() {
        return $this->db->query("
            SELECT
                ft.*,
                ft.template_name,
                ft.description,
                ft.category,
                ft.preview_image,
                ft.usage_count,
                ft.avg_rating,
                COUNT(ftf.id) as field_count
            FROM form_templates ft
            LEFT JOIN form_template_fields ftf ON ft.id = ftf.template_id
            WHERE ft.company_id = ? AND ft.is_public = true
            GROUP BY ft.id
            ORDER BY ft.usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getValidationRules() {
        return [
            'required' => [
                'name' => 'Required',
                'description' => 'Field must be filled',
                'rule' => 'required'
            ],
            'email' => [
                'name' => 'Email Format',
                'description' => 'Must be valid email address',
                'rule' => 'email'
            ],
            'phone' => [
                'name' => 'Phone Format',
                'description' => 'Must be valid phone number',
                'rule' => 'phone'
            ],
            'numeric' => [
                'name' => 'Numeric Only',
                'description' => 'Must contain only numbers',
                'rule' => 'numeric'
            ],
            'alphanumeric' => [
                'name' => 'Alphanumeric',
                'description' => 'Letters and numbers only',
                'rule' => 'alphanumeric'
            ],
            'min_length' => [
                'name' => 'Minimum Length',
                'description' => 'Must be at least X characters',
                'rule' => 'min_length',
                'parameters' => ['length']
            ],
            'max_length' => [
                'name' => 'Maximum Length',
                'description' => 'Must be no more than X characters',
                'rule' => 'max_length',
                'parameters' => ['length']
            ],
            'pattern' => [
                'name' => 'Pattern Match',
                'description' => 'Must match specific pattern',
                'rule' => 'pattern',
                'parameters' => ['pattern']
            ],
            'date_range' => [
                'name' => 'Date Range',
                'description' => 'Must be within date range',
                'rule' => 'date_range',
                'parameters' => ['min_date', 'max_date']
            ],
            'file_size' => [
                'name' => 'File Size',
                'description' => 'File must be within size limits',
                'rule' => 'file_size',
                'parameters' => ['min_size', 'max_size']
            ],
            'file_type' => [
                'name' => 'File Type',
                'description' => 'File must be specific type',
                'rule' => 'file_type',
                'parameters' => ['allowed_types']
            ]
        ];
    }

    private function getFormThemes() {
        return [
            'default' => [
                'name' => 'Default',
                'description' => 'Clean, professional theme',
                'preview' => '/themes/default/preview.png',
                'colors' => ['primary' => '#007bff', 'secondary' => '#6c757d']
            ],
            'modern' => [
                'name' => 'Modern',
                'description' => 'Contemporary design with gradients',
                'preview' => '/themes/modern/preview.png',
                'colors' => ['primary' => '#667eea', 'secondary' => '#764ba2']
            ],
            'corporate' => [
                'name' => 'Corporate',
                'description' => 'Professional corporate styling',
                'preview' => '/themes/corporate/preview.png',
                'colors' => ['primary' => '#2c3e50', 'secondary' => '#34495e']
            ],
            'minimal' => [
                'name' => 'Minimal',
                'description' => 'Clean, minimal design',
                'preview' => '/themes/minimal/preview.png',
                'colors' => ['primary' => '#000000', 'secondary' => '#666666']
            ],
            'colorful' => [
                'name' => 'Colorful',
                'description' => 'Bright, engaging colors',
                'preview' => '/themes/colorful/preview.png',
                'colors' => ['primary' => '#ff6b6b', 'secondary' => '#4ecdc4']
            ]
        ];
    }

    private function getFormIntegrations() {
        return [
            'email' => [
                'name' => 'Email Notifications',
                'description' => 'Send email notifications on form submission',
                'settings' => ['recipients', 'templates', 'conditions']
            ],
            'webhook' => [
                'name' => 'Webhooks',
                'description' => 'Send data to external systems via webhooks',
                'settings' => ['url', 'method', 'headers', 'authentication']
            ],
            'api' => [
                'name' => 'API Integration',
                'description' => 'Integrate with external APIs',
                'settings' => ['endpoint', 'method', 'mapping', 'authentication']
            ],
            'database' => [
                'name' => 'Database Export',
                'description' => 'Export submissions to external databases',
                'settings' => ['connection', 'table', 'mapping']
            ],
            'crm' => [
                'name' => 'CRM Integration',
                'description' => 'Sync form data with CRM systems',
                'settings' => ['crm_type', 'mapping', 'sync_frequency']
            ],
            'analytics' => [
                'name' => 'Analytics Integration',
                'description' => 'Send form data to analytics platforms',
                'settings' => ['platform', 'tracking_id', 'events']
            ]
        ];
    }

    private function getAllForms() {
        return $this->db->query("
            SELECT
                f.*,
                f.form_title,
                f.description,
                f.status,
                f.category,
                f.total_submissions,
                f.last_submission,
                f.created_at,
                u.first_name,
                u.last_name,
                COUNT(ff.id) as field_count,
                AVG(fs.rating) as avg_rating
            FROM forms f
            LEFT JOIN users u ON f.created_by = u.id
            LEFT JOIN form_fields ff ON f.id = ff.form_id
            LEFT JOIN form_submissions fs ON f.id = fs.form_id
            WHERE f.company_id = ?
            GROUP BY f.id
            ORDER BY f.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getFormCategories() {
        return [
            'contact' => [
                'name' => 'Contact Forms',
                'description' => 'Forms for collecting contact information',
                'icon' => 'user'
            ],
            'survey' => [
                'name' => 'Surveys',
                'description' => 'Survey and feedback forms',
                'icon' => 'clipboard'
            ],
            'registration' => [
                'name' => 'Registration',
                'description' => 'Event or service registration forms',
                'icon' => 'calendar'
            ],
            'application' => [
                'name' => 'Applications',
                'description' => 'Job or membership applications',
                'icon' => 'file-text'
            ],
            'feedback' => [
                'name' => 'Feedback',
                'description' => 'Customer or employee feedback forms',
                'icon' => 'message-circle'
            ],
            'assessment' => [
                'name' => 'Assessments',
                'description' => 'Tests, quizzes, and evaluations',
                'icon' => 'award'
            ],
            'order' => [
                'name' => 'Order Forms',
                'description' => 'Product or service order forms',
                'icon' => 'shopping-cart'
            ],
            'custom' => [
                'name' => 'Custom Forms',
                'description' => 'Custom-built forms for specific needs',
                'icon' => 'settings'
            ]
        ];
    }

    private function getFormStatus() {
        return [
            'draft' => [
                'name' => 'Draft',
                'description' => 'Form is being created',
                'color' => 'gray',
                'actions' => ['edit', 'publish', 'delete']
            ],
            'published' => [
                'name' => 'Published',
                'description' => 'Form is live and accepting submissions',
                'color' => 'green',
                'actions' => ['edit', 'unpublish', 'duplicate', 'analytics']
            ],
            'unpublished' => [
                'name' => 'Unpublished',
                'description' => 'Form is not accepting submissions',
                'color' => 'yellow',
                'actions' => ['edit', 'publish', 'delete']
            ],
            'archived' => [
                'name' => 'Archived',
                'description' => 'Form is archived and no longer active',
                'color' => 'red',
                'actions' => ['restore', 'delete']
            ]
        ];
    }

    private function getFormPermissions() {
        return [
            'view' => [
                'name' => 'View Form',
                'description' => 'Can view the form',
                'roles' => ['admin', 'manager', 'user']
            ],
            'submit' => [
                'name' => 'Submit Form',
                'description' => 'Can submit responses to the form',
                'roles' => ['admin', 'manager', 'user']
            ],
            'edit' => [
                'name' => 'Edit Form',
                'description' => 'Can edit the form structure',
                'roles' => ['admin', 'manager']
            ],
            'delete' => [
                'name' => 'Delete Form',
                'description' => 'Can delete the form',
                'roles' => ['admin']
            ],
            'manage_submissions' => [
                'name' => 'Manage Submissions',
                'description' => 'Can view and manage form submissions',
                'roles' => ['admin', 'manager']
            ],
            'export_data' => [
                'name' => 'Export Data',
                'description' => 'Can export form submission data',
                'roles' => ['admin', 'manager']
            ]
        ];
    }

    private function getBulkActions() {
        return [
            'publish' => 'Publish Forms',
            'unpublish' => 'Unpublish Forms',
            'duplicate' => 'Duplicate Forms',
            'archive' => 'Archive Forms',
            'delete' => 'Delete Forms',
            'export' => 'Export Forms',
            'category' => 'Change Category',
            'permissions' => 'Update Permissions'
        ];
    }

    private function getExportOptions() {
        return [
            'json' => [
                'name' => 'JSON Export',
                'description' => 'Export form structure as JSON',
                'extension' => 'json'
            ],
            'xml' => [
                'name' => 'XML Export',
                'description' => 'Export form structure as XML',
                'extension' => 'xml'
            ],
            'csv' => [
                'name' => 'CSV Template',
                'description' => 'Export form fields as CSV template',
                'extension' => 'csv'
            ],
            'pdf' => [
                'name' => 'PDF Form',
                'description' => 'Export form as fillable PDF',
                'extension' => 'pdf'
            ]
        ];
    }

    private function getFormSubmissions($formId = null) {
        $where = ["fs.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($formId) {
            $where[] = "fs.form_id = ?";
            $params[] = $formId;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                fs.*,
                fs.submission_data,
                fs.status,
                fs.submitted_at,
                fs.ip_address,
                fs.user_agent,
                f.form_title,
                u.first_name,
                u.last_name
            FROM form_submissions fs
            LEFT JOIN forms f ON fs.form_id = f.id
            LEFT JOIN users u ON fs.user_id = u.id
            WHERE $whereClause
            ORDER BY fs.submitted_at DESC
        ", $params);
    }

    private function getSubmissionFilters() {
        return [
            'date_range' => [
                'name' => 'Date Range',
                'type' => 'date',
                'options' => ['today', 'yesterday', 'last_7_days', 'last_30_days', 'custom']
            ],
            'status' => [
                'name' => 'Status',
                'type' => 'select',
                'options' => ['all', 'complete', 'incomplete', 'flagged']
            ],
            'user' => [
                'name' => 'User',
                'type' => 'select',
                'options' => $this->getUsersList()
            ],
            'device' => [
                'name' => 'Device Type',
                'type' => 'select',
                'options' => ['desktop', 'mobile', 'tablet']
            ],
            'completion_time' => [
                'name' => 'Completion Time',
                'type' => 'range',
                'options' => ['0-30s', '30s-1m', '1-5m', '5-10m', '10m+']
            ]
        ];
    }

    private function getUsersList() {
        return $this->db->query("
            SELECT id, CONCAT(first_name, ' ', last_name) as name
            FROM users
            WHERE company_id = ?
            ORDER BY first_name, last_name
        ", [$this->user['company_id']]);
    }

    private function getSubmissionAnalytics($formId = null) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($formId) {
            $where[] = "form_id = ?";
            $params[] = $formId;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_submissions,
                COUNT(CASE WHEN status = 'complete' THEN 1 END) as complete_submissions,
                COUNT(CASE WHEN status = 'incomplete' THEN 1 END) as incomplete_submissions,
                AVG(completion_time_seconds) as avg_completion_time,
                MIN(submitted_at) as first_submission,
                MAX(submitted_at) as last_submission,
                COUNT(DISTINCT user_id) as unique_submitters
            FROM form_submissions
            WHERE $whereClause AND submitted_at >= ?
        ", array_merge($params, [date('Y-m-d H:i:s', strtotime('-30 days'))]));
    }

    private function getExportFormats() {
        return [
            'excel' => [
                'name' => 'Excel (.xlsx)',
                'description' => 'Microsoft Excel spreadsheet',
                'icon' => 'file-excel'
            ],
            'csv' => [
                'name' => 'CSV (.csv)',
                'description' => 'Comma-separated values',
                'icon' => 'file-csv'
            ],
            'json' => [
                'name' => 'JSON (.json)',
                'description' => 'JavaScript Object Notation',
                'icon' => 'file-json'
            ],
            'pdf' => [
                'name' => 'PDF Report (.pdf)',
                'description' => 'Portable Document Format report',
                'icon' => 'file-pdf'
            ],
            'xml' => [
                'name' => 'XML (.xml)',
                'description' => 'Extensible Markup Language',
                'icon' => 'file-xml'
            ]
        ];
    }

    private function getSubmissionBulkOperations() {
        return [
            'export' => 'Export Selected',
            'delete' => 'Delete Selected',
            'flag' => 'Flag for Review',
            'unflag' => 'Remove Flag',
            'notify' => 'Send Notification',
            'archive' => 'Archive Selected'
        ];
    }

    private function getDetailedFormAnalytics($formId = null) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($formId) {
            $where[] = "form_id = ?";
            $params[] = $formId;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                DATE(submitted_at) as date,
                COUNT(*) as submissions,
                AVG(completion_time_seconds) as avg_time,
                COUNT(CASE WHEN status = 'complete' THEN 1 END) as completed,
                COUNT(CASE WHEN device_type = 'mobile' THEN 1 END) as mobile_submissions,
                COUNT(CASE WHEN device_type = 'desktop' THEN 1 END) as desktop_submissions,
                COUNT(DISTINCT user_id) as unique_users
            FROM form_submissions
            WHERE $whereClause
            GROUP BY DATE(submitted_at)
            ORDER BY date DESC
            LIMIT 30
        ", $params);
    }

    private function getFieldAnalytics($formId) {
        if (!$formId) return [];

        return $this->db->query("
            SELECT
                ff.field_label,
                ff.field_type,
                COUNT(CASE WHEN fs.submission_data LIKE CONCAT('%', ff.field_name, '%') THEN 1 END) as responses,
                AVG(CASE WHEN JSON_EXTRACT(fs.submission_data, CONCAT('$.', ff.field_name, '.time')) THEN JSON_EXTRACT(fs.submission_data, CONCAT('$.', ff.field_name, '.time')) END) as avg_field_time,
                COUNT(CASE WHEN JSON_EXTRACT(fs.submission_data, CONCAT('$.', ff.field_name, '.error')) THEN 1 END) as errors
            FROM form_fields ff
            LEFT JOIN form_submissions fs ON ff.form_id = fs.form_id
            WHERE ff.form_id = ?
            GROUP BY ff.id, ff.field_label, ff.field_type
            ORDER BY ff.field_order ASC
        ", [$formId]);
    }

    private function getConversionFunnel($formId) {
        if (!$formId) return [];

        return $this->db->query("
            SELECT
                ff.field_label,
                COUNT(CASE WHEN fs.submission_data LIKE CONCAT('%', ff.field_name, '%') THEN 1 END) as reached_field,
                COUNT(CASE WHEN JSON_EXTRACT(fs.submission_data, CONCAT('$.', ff.field_name, '.completed')) = true THEN 1 END) as completed_field,
                ROUND(
                    (COUNT(CASE WHEN JSON_EXTRACT(fs.submission_data, CONCAT('$.', ff.field_name, '.completed')) = true THEN 1 END) /
                     NULLIF(COUNT(CASE WHEN fs.submission_data LIKE CONCAT('%', ff.field_name, '%') THEN 1 END), 0)) * 100, 2
                ) as completion_rate
            FROM form_fields ff
            LEFT JOIN form_submissions fs ON ff.form_id = fs.form_id
            WHERE ff.form_id = ?
            GROUP BY ff.id, ff.field_label
            ORDER BY ff.field_order ASC
        ", [$formId]);
    }

    private function getUserJourney($formId) {
        if (!$formId) return [];

        return $this->db->query("
            SELECT
                uj.step_number,
                uj.step_name,
                uj.avg_time_spent,
                uj.drop_off_rate,
                uj.engagement_score
            FROM user_journey uj
            WHERE uj.form_id = ?
            ORDER BY uj.step_number ASC
        ", [$formId]);
    }

    private function getPerformanceMetrics($formId) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($formId) {
            $where[] = "form_id = ?";
            $params[] = $formId;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                AVG(load_time_ms) as avg_load_time,
                AVG(first_interaction_time_ms) as avg_first_interaction,
                AVG(completion_time_seconds) as avg_completion_time,
                COUNT(CASE WHEN device_type = 'mobile' THEN 1 END) / COUNT(*) * 100 as mobile_percentage,
                AVG(form_abandonment_rate) as abandonment_rate,
                AVG(error_rate) as error_rate
            FROM form_performance
            WHERE $whereClause AND measured_at >= ?
        ", array_merge($params, [date('Y-m-d H:i:s', strtotime('-30 days'))]));
    }

    private function getComparisonReports() {
        return $this->db->query("
            SELECT
                f1.form_title as form1_title,
                f2.form_title as form2_title,
                f1.total_submissions as form1_submissions,
                f2.total_submissions as form2_submissions,
                f1.completion_rate as form1_completion,
                f2.completion_rate as form2_completion,
                f1.avg_completion_time as form1_time,
                f2.avg_completion_time as form2_time
            FROM forms f1
            CROSS JOIN forms f2
            WHERE f1.id < f2.id
                AND f1.company_id = ?
                AND f2.company_id = ?
                AND f1.status = 'published'
                AND f2.status = 'published'
            ORDER BY ABS(f1.total_submissions - f2.total_submissions) ASC
            LIMIT 10
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getTemplateCategories() {
        return [
            'business' => [
                'name' => 'Business Forms',
                'description' => 'Forms for business operations',
                'templates' => []
            ],
            'hr' => [
                'name' => 'HR Forms',
                'description' => 'Human resources related forms',
                'templates' => []
            ],
            'sales' => [
                'name' => 'Sales Forms',
                'description' => 'Sales and marketing forms',
                'templates' => []
            ],
            'survey' => [
                'name' => 'Surveys',
                'description' => 'Survey and feedback forms',
                'templates' => []
            ],
            'registration' => [
                'name' => 'Registration',
                'description' => 'Event and service registration',
                'templates' => []
            ],
            'assessment' => [
                'name' => 'Assessments',
                'description' => 'Tests and evaluations',
                'templates' => []
            ]
        ];
    }

    private function getFeaturedTemplates() {
        return $this->db->query("
            SELECT
                ft.*,
                ft.template_name,
                ft.description,
                ft.category,
                ft.preview_image,
                ft.usage_count,
                ft.avg_rating,
                COUNT(f.id) as forms_created
            FROM form_templates ft
            LEFT JOIN forms f ON ft.id = f.template_id
            WHERE ft.company_id = ? AND ft.featured = true
            GROUP BY ft.id
            ORDER BY ft.avg_rating DESC, ft.usage_count DESC
            LIMIT 8
        ", [$this->user['company_id']]);
    }

    private function getCustomTemplates() {
        return $this->db->query("
            SELECT
                ft.*,
                ft.template_name,
                ft.description,
                ft.category,
                ft.created_at,
                u.first_name,
                u.last_name
            FROM form_templates ft
            LEFT JOIN users u ON ft.created_by = u.id
            WHERE ft.company_id = ? AND ft.is_public = false
            ORDER BY ft.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getTemplateUsage() {
        return $this->db->query("
            SELECT
                ft.template_name,
                ft.category,
                COUNT(f.id) as usage_count,
                AVG(f.total_submissions) as avg_submissions,
                MAX(f.created_at) as last_used
            FROM form_templates ft
            LEFT JOIN forms f ON ft.id = f.template_id
            WHERE ft.company_id = ?
            GROUP BY ft.id, ft.template_name, ft.category
            ORDER BY usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getTemplateRatings() {
        return $this->db->query("
            SELECT
                ft.template_name,
                AVG(tr.rating) as avg_rating,
                COUNT(tr.id) as total_ratings,
                COUNT(CASE WHEN tr.rating = 5 THEN 1 END) as five_star_ratings
            FROM form_templates ft
            LEFT JOIN template_ratings tr ON ft.id = tr.template_id
            WHERE ft.company_id = ?
            GROUP BY ft.id, ft.template_name
            HAVING total_ratings > 0
            ORDER BY avg_rating DESC
        ", [$this->user['company_id']]);
    }

    private function getWorkflowTemplates() {
        return [
            'approval' => [
                'name' => 'Approval Workflow',
                'description' => 'Form submissions require approval',
                'steps' => ['submit', 'review', 'approve', 'complete'],
                'roles' => ['submitter', 'reviewer', 'approver']
            ],
            'review' => [
                'name' => 'Review Workflow',
                'description' => 'Multi-step review process',
                'steps' => ['submit', 'initial_review', 'detailed_review', 'final_approval'],
                'roles' => ['submitter', 'reviewer', 'senior_reviewer', 'approver']
            ],
            'feedback' => [
                'name' => 'Feedback Workflow',
                'description' => 'Collect and process feedback',
                'steps' => ['submit', 'analyze', 'respond', 'follow_up'],
                'roles' => ['submitter', 'analyst', 'responder', 'manager']
            ],
            'assessment' => [
                'name' => 'Assessment Workflow',
                'description' => 'Process assessment submissions',
                'steps' => ['submit', 'grade', 'review', 'certify'],
                'roles' => ['student', 'grader', 'reviewer', 'certifier']
            ]
        ];
    }

    private function getActiveWorkflows() {
        return $this->db->query("
            SELECT
                fw.*,
                fw.workflow_name,
                fw.form_id,
                fw.status,
                fw.current_step,
                fw.created_at,
                f.form_title,
                u.first_name,
                u.last_name
            FROM form_workflows fw
            LEFT JOIN forms f ON fw.form_id = f.id
            LEFT JOIN users u ON fw.initiated_by = u.id
            WHERE fw.company_id = ?
            ORDER BY fw.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getWorkflowAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_workflows,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_workflows,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as active_workflows,
                AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_completion_time,
                COUNT(CASE WHEN overdue = true THEN 1 END) as overdue_workflows,
                MAX(created_at) as last_workflow_created
            FROM form_workflows
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getApprovalProcesses() {
        return $this->db->query("
            SELECT
                ap.*,
                ap.process_name,
                ap.form_id,
                ap.approval_levels,
                ap.current_level,
                ap.status,
                ap.created_at,
                f.form_title
            FROM approval_processes ap
            LEFT JOIN forms f ON ap.form_id = f.id
            WHERE ap.company_id = ?
            ORDER BY ap.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getNotificationRules() {
        return $this->db->query("
            SELECT
                nr.*,
                nr.rule_name,
                nr.trigger_event,
                nr.notification_type,
                nr.recipient_roles,
                nr.is_active,
                nr.last_triggered
            FROM notification_rules nr
            WHERE nr.company_id = ?
            ORDER BY nr.is_active DESC, nr.last_triggered DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // NEW INTEGRATION METHODS
    // ============================================================================

    private function getForm() {
        // Get form by ID
        $formId = $_GET['id'] ?? null;
        if (!$formId) {
            return null;
        }

        return $this->db->querySingle("
            SELECT * FROM forms WHERE id = ? AND company_id = ?
        ", [$formId, $this->user['company_id']]);
    }

    private function getTotalSubmissions($formId) {
        $result = $this->db->querySingle("
            SELECT COUNT(*) as count FROM form_submissions
            WHERE form_id = ? AND company_id = ?
        ", [$formId, $this->user['company_id']]);

        return $result['count'] ?? 0;
    }

    private function getCompletionRate($formId) {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'complete' THEN 1 END) * 100.0 / COUNT(*) as rate
            FROM form_submissions
            WHERE form_id = ? AND company_id = ?
        ", [$formId, $this->user['company_id']]);

        return round($result['rate'] ?? 0, 2);
    }

    private function getAverageCompletionTime($formId) {
        $result = $this->db->querySingle("
            SELECT AVG(completion_time_seconds) as avg_time
            FROM form_submissions
            WHERE form_id = ? AND company_id = ? AND status = 'complete'
        ", [$formId, $this->user['company_id']]);

        return round($result['avg_time'] ?? 0, 2);
    }

    private function getFieldAnalytics($formId) {
        return $this->db->query("
            SELECT
                ff.field_label,
                ff.field_type,
                COUNT(fs.id) as responses,
                AVG(fs.completion_time_seconds) as avg_time
            FROM form_fields ff
            LEFT JOIN form_submissions fs ON ff.form_id = fs.form_id
            WHERE ff.form_id = ? AND ff.company_id = ?
            GROUP BY ff.id, ff.field_label, ff.field_type
        ", [$formId, $this->user['company_id']]);
    }

    private function getSubmissionTrends($formId) {
        return $this->db->query("
            SELECT
                DATE(submitted_at) as date,
                COUNT(*) as count
            FROM form_submissions
            WHERE form_id = ? AND company_id = ?
            GROUP BY DATE(submitted_at)
            ORDER BY date DESC
            LIMIT 30
        ", [$formId, $this->user['company_id']]);
    }

    private function getDeviceBreakdown($formId) {
        return $this->db->query("
            SELECT
                device_type,
                COUNT(*) as count
            FROM form_submissions
            WHERE form_id = ? AND company_id = ?
            GROUP BY device_type
        ", [$formId, $this->user['company_id']]);
    }

    private function getGeographicData($formId) {
        return $this->db->query("
            SELECT
                country,
                region,
                city,
                COUNT(*) as count
            FROM form_submissions
            WHERE form_id = ? AND company_id = ?
            GROUP BY country, region, city
            ORDER BY count DESC
        ", [$formId, $this->user['company_id']]);
    }

    private function getConversionFunnel($formId) {
        return $this->db->query("
            SELECT
                step_name,
                step_number,
                COUNT(*) as visitors,
                COUNT(CASE WHEN completed = true THEN 1 END) as completions
            FROM form_funnel_steps
            WHERE form_id = ? AND company_id = ?
            GROUP BY step_name, step_number
            ORDER BY step_number ASC
        ", [$formId, $this->user['company_id']]);
    }

    private function generateBasicAnalyticsReport($form) {
        return [
            'report_type' => 'basic_analytics',
            'form_id' => $form['id'],
            'form_title' => $form['form_title'],
            'total_submissions' => $this->getTotalSubmissions($form['id']),
            'completion_rate' => $this->getCompletionRate($form['id']),
            'avg_completion_time' => $this->getAverageCompletionTime($form['id']),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function generateFieldPerformanceReport($form) {
        $fieldAnalytics = $this->getFieldAnalytics($form['id']);

        return [
            'report_type' => 'field_performance',
            'form_id' => $form['id'],
            'form_title' => $form['form_title'],
            'field_analytics' => $fieldAnalytics,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function generateSubmissionTrendsReport($form) {
        $trends = $this->getSubmissionTrends($form['id']);

        return [
            'report_type' => 'submission_trends',
            'form_id' => $form['id'],
            'form_title' => $form['form_title'],
            'trends' => $trends,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function generateCompletionAnalysisReport($form) {
        return [
            'report_type' => 'completion_analysis',
            'form_id' => $form['id'],
            'form_title' => $form['form_title'],
            'completion_rate' => $this->getCompletionRate($form['id']),
            'avg_completion_time' => $this->getAverageCompletionTime($form['id']),
            'device_breakdown' => $this->getDeviceBreakdown($form['id']),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function generateFormDashboard($form) {
        return [
            'dashboard_type' => 'form_analytics',
            'form_id' => $form['id'],
            'form_title' => $form['form_title'],
            'widgets' => [
                [
                    'type' => 'metric',
                    'title' => 'Total Submissions',
                    'value' => $this->getTotalSubmissions($form['id']),
                    'change' => '+12%'
                ],
                [
                    'type' => 'metric',
                    'title' => 'Completion Rate',
                    'value' => $this->getCompletionRate($form['id']) . '%',
                    'change' => '+5%'
                ],
                [
                    'type' => 'chart',
                    'title' => 'Submission Trends',
                    'chart_type' => 'line',
                    'data' => $this->getSubmissionTrends($form['id'])
                ],
                [
                    'type' => 'chart',
                    'title' => 'Device Breakdown',
                    'chart_type' => 'pie',
                    'data' => $this->getDeviceBreakdown($form['id'])
                ]
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function suggestFieldImprovements($analytics) {
        $suggestions = [];

        if (isset($analytics['field_analytics'])) {
            foreach ($analytics['field_analytics'] as $field) {
                if ($field['responses'] == 0) {
                    $suggestions[] = [
                        'field' => $field['field_label'],
                        'suggestion' => 'Consider removing unused field',
                        'impact' => 'low'
                    ];
                }
                if (($field['avg_time'] ?? 0) > 60) {
                    $suggestions[] = [
                        'field' => $field['field_label'],
                        'suggestion' => 'Field takes too long to complete',
                        'impact' => 'medium'
                    ];
                }
            }
        }

        return $suggestions;
    }

    private function suggestLayoutImprovements($analytics) {
        $suggestions = [];

        if (($analytics['completion_rate'] ?? 0) < 50) {
            $suggestions[] = [
                'type' => 'layout',
                'suggestion' => 'Improve form layout to increase completion rate',
                'impact' => 'high'
            ];
        }

        if (($analytics['avg_completion_time'] ?? 0) > 300) {
            $suggestions[] = [
                'type' => 'layout',
                'suggestion' => 'Form is too long, consider multi-step approach',
                'impact' => 'high'
            ];
        }

        return $suggestions;
    }

    private function suggestContentImprovements($analytics) {
        $suggestions = [];

        if (($analytics['completion_rate'] ?? 0) < 70) {
            $suggestions[] = [
                'type' => 'content',
                'suggestion' => 'Review form instructions and help text',
                'impact' => 'medium'
            ];
        }

        return $suggestions;
    }

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

    private function getFormReportId($formId) {
        // Get or create a report ID for this form
        $report = $this->db->querySingle("
            SELECT id FROM reports
            WHERE form_id = ? AND company_id = ?
        ", [$formId, $this->user['company_id']]);

        if ($report) {
            return $report['id'];
        }

        // Create a new report for this form
        return $this->db->insert('reports', [
            'company_id' => $this->user['company_id'],
            'form_id' => $formId,
            'report_name' => 'Form Analytics Report',
            'report_type' => 'form_analytics',
            'created_by' => $this->user['id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
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
            $this->db->beginTransaction();

            // Create form
            $formId = $this->db->insert('forms', [
                'company_id' => $this->user['company_id'],
                'form_title' => $data['form_title'],
                'description' => $data['description'] ?? '',
                'category' => $data['category'] ?? 'custom',
                'template_id' => $data['template_id'] ?? null,
                'status' => 'draft',
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Create form settings
            if (isset($data['settings'])) {
                $this->db->insert('form_settings', [
                    'form_id' => $formId,
                    'settings' => json_encode($data['settings'])
                ]);
            }

            // If template is specified, copy template fields
            if ($data['template_id']) {
                $this->copyTemplateFields($data['template_id'], $formId);
            }

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'form_id' => $formId,
                'message' => 'Form created successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function copyTemplateFields($templateId, $formId) {
        $templateFields = $this->db->query("
            SELECT * FROM form_template_fields
            WHERE template_id = ?
            ORDER BY field_order ASC
        ", [$templateId]);

        foreach ($templateFields as $field) {
            $this->db->insert('form_fields', [
                'form_id' => $formId,
                'field_name' => $field['field_name'],
                'field_label' => $field['field_label'],
                'field_type' => $field['field_type'],
                'field_order' => $field['field_order'],
                'is_required' => $field['is_required'],
                'field_options' => $field['field_options'],
                'validation_rules' => $field['validation_rules']
            ]);
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
            // Check if field exists
            $existing = $this->db->querySingle("
                SELECT id FROM form_fields
                WHERE form_id = ? AND field_name = ?
            ", [$data['form_id'], $data['field_name']]);

            if ($existing) {
                // Update existing field
                $this->db->update('form_fields', [
                    'field_label' => $data['field_label'],
                    'field_type' => $data['field_type'],
                    'field_order' => $data['field_order'] ?? 0,
                    'is_required' => $data['is_required'] ?? false,
                    'field_options' => json_encode($data['field_options'] ?? []),
                    'validation_rules' => json_encode($data['validation_rules'] ?? [])
                ], 'id = ?', [$existing['id']]);

                $fieldId = $existing['id'];
            } else {
                // Create new field
                $fieldId = $this->db->insert('form_fields', [
                    'form_id' => $data['form_id'],
                    'field_name' => $data['field_name'],
                    'field_label' => $data['field_label'],
                    'field_type' => $data['field_type'],
                    'field_order' => $data['field_order'] ?? 0,
                    'is_required' => $data['is_required'] ?? false,
                    'field_options' => json_encode($data['field_options'] ?? []),
                    'validation_rules' => json_encode($data['validation_rules'] ?? [])
                ]);
            }

            $this->jsonResponse([
                'success' => true,
                'field_id' => $fieldId,
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
            $this->db->update('forms', [
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s'),
                'published_by' => $this->user['id']
            ], 'id = ? AND company_id = ?', [
                $data['form_id'],
                $this->user['company_id']
            ]);

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
            $this->db->beginTransaction();

            // Validate form exists and is published
            $form = $this->db->querySingle("
                SELECT * FROM forms WHERE id = ? AND company_id = ? AND status = 'published'
            ", [$data['form_id'], $this->user['company_id']]);

            if (!$form) {
                $this->jsonResponse(['error' => 'Form not found or not published'], 404);
            }

            // Validate submission data against form fields
            $validationResult = $this->validateSubmissionData($data['form_id'], $data['submission_data']);

            if (!$validationResult['valid']) {
                $this->jsonResponse([
                    'error' => 'Validation failed',
                    'errors' => $validationResult['errors']
                ], 400);
            }

            // Create submission record
            $submissionId = $this->db->insert('form_submissions', [
                'company_id' => $this->user['company_id'],
                'form_id' => $data['form_id'],
                'user_id' => $this->user['id'] ?? null,
                'submission_data' => json_encode($data['submission_data']),
                'device_info' => json_encode($data['device_info'] ?? []),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'status' => 'complete',
                'submitted_at' => date('Y-m-d H:i:s')
            ]);

            // Update form statistics
            $this->updateFormStatistics($data['form_id']);

            // Trigger integrations if configured
            $this->triggerFormIntegrations($data['form_id'], $data['submission_data'], $submissionId);

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'submission_id' => $submissionId,
                'message' => 'Form submitted successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function validateSubmissionData($formId, $submissionData) {
        $errors = [];
        $isValid = true;

        // Get form fields
        $fields = $this->db->query("
            SELECT * FROM form_fields
            WHERE form_id = ? ORDER BY field_order ASC
        ", [$formId]);

        foreach ($fields as $field) {
            $fieldName = $field['field_name'];
            $fieldValue = $submissionData[$fieldName] ?? null;

            // Check required fields
            if ($field['is_required'] && empty($fieldValue)) {
                $errors[$fieldName] = 'This field is required';
                $isValid = false;
                continue;
            }

            // Apply validation rules
            if (!empty($fieldValue)) {
                $validationRules = json_decode($field['validation_rules'], true);
                if ($validationRules) {
                    foreach ($validationRules as $rule => $params) {
                        if (!$this->validateFieldRule($fieldValue, $rule, $params)) {
                            $errors[$fieldName] = $this->getValidationErrorMessage($rule, $params);
                            $isValid = false;
                            break;
                        }
                    }
                }
            }
        }

        return [
            'valid' => $isValid,
            'errors' => $errors
        ];
    }

    private function validateFieldRule($value, $rule, $params) {
        switch ($rule) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'numeric':
                return is_numeric($value);
            case 'min_length':
                return strlen($value) >= ($params['length'] ?? 0);
            case 'max_length':
                return strlen($value) <= ($params['length'] ?? PHP_INT_MAX);
            case 'pattern':
                return preg_match($params['pattern'] ?? '', $value);
            default:
                return true;
        }
    }

    private function getValidationErrorMessage($rule, $params) {
        $messages = [
            'email' => 'Please enter a valid email address',
            'numeric' => 'Please enter a valid number',
            'min_length' => 'Must be at least ' . ($params['length'] ?? 0) . ' characters',
            'max_length' => 'Must be no more than ' . ($params['length'] ?? 0) . ' characters',
            'pattern' => 'Invalid format'
        ];

        return $messages[$rule] ?? 'Validation failed';
    }

    private function updateFormStatistics($formId) {
        // Update total submissions
        $this->db->query("
            UPDATE forms SET
                total_submissions = total_submissions + 1,
                last_submission = NOW()
            WHERE id = ?
        ", [$formId]);
    }

    private function triggerFormIntegrations($formId, $submissionData, $submissionId) {
        // Get form integrations
        $integrations = $this->db->query("
            SELECT * FROM form_integrations
            WHERE form_id = ? AND is_active = true
        ", [$formId]);

        foreach ($integrations as $integration) {
            try {
                switch ($integration['integration_type']) {
                    case 'email':
                        $this->sendIntegrationEmail($integration, $submissionData);
                        break;
                    case 'webhook':
                        $this->sendIntegrationWebhook($integration, $submissionData, $submissionId);
                        break;
                    case 'api':
                        $this->sendIntegrationAPI($integration, $submissionData, $submissionId);
                        break;
                }
            } catch (Exception $e) {
                // Log integration error but don't fail the submission
                error_log("Form integration failed: " . $e->getMessage());
            }
        }
    }

    private function sendIntegrationEmail($integration, $submissionData) {
        $settings = json_decode($integration['settings'], true);
        $recipients = $settings['recipients'] ?? [];
        $template = $settings['template'] ?? 'default';

        // Send email notification
        // Implementation would use the email system
    }

    private function sendIntegrationWebhook($integration, $submissionData, $submissionId) {
        $settings = json_decode($integration['settings'], true);
        $url = $settings['url'];
        $method = $settings['method'] ?? 'POST';
        $headers = $settings['headers'] ?? [];

        $payload = [
            'form_id' => $integration['form_id'],
            'submission_id' => $submissionId,
            'data' => $submissionData,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Send webhook
        // Implementation would use curl or similar
    }

    private function sendIntegrationAPI($integration, $submissionData, $submissionId) {
        $settings = json_decode($integration['settings'], true);
        $endpoint = $settings['endpoint'];
        $method = $settings['method'] ?? 'POST';
        $mapping = $settings['mapping'] ?? [];

        // Map submission data to API format
        $apiData = [];
        foreach ($mapping as $apiField => $formField) {
            $apiData[$apiField] = $submissionData[$formField] ?? null;
        }

        // Send API request
        // Implementation would use curl or similar
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
            // Get filtered submissions
            $submissions = $this->getFilteredSubmissions($data['form_id'], $data['filters']);

            // Format data for export
            $exportData = $this->formatExportData($submissions, $data['format'], $data['fields']);

            // Create export record
            $exportId = $this->db->insert('form_exports', [
                'company_id' => $this->user['company_id'],
                'form_id' => $data['form_id'],
                'export_format' => $data['format'],
                'record_count' => count($submissions),
                'filters' => json_encode($data['filters'] ?? []),
                'exported_by' => $this->user['id'],
                'exported_at' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'export_id' => $exportId,
                'data' => $exportData,
                'message' => 'Submissions exported successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getFilteredSubmissions($formId, $filters = []) {
        $where = ["form_id = ?"];
        $params = [$formId];

        if (!empty($filters['date_from'])) {
            $where[] = "submitted_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "submitted_at <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT * FROM form_submissions
            WHERE $whereClause
            ORDER BY submitted_at DESC
        ", $params);
    }

    private function formatExportData($submissions, $format, $fields = []) {
        $data = [];

        foreach ($submissions as $submission) {
            $submissionData = json_decode($submission['submission_data'], true);
            $row = [
                'submission_id' => $submission['id'],
                'submitted_at' => $submission['submitted_at'],
                'user_id' => $submission['user_id'],
                'status' => $submission['status']
            ];

            // Add form field data
            if (empty($fields)) {
                $row = array_merge($row, $submissionData);
            } else {
                foreach ($fields as $field) {
                    $row[$field] = $submissionData[$field] ?? '';
                }
            }

            $data[] = $row;
        }

        switch ($format) {
            case 'csv':
                return $this->arrayToCsv($data);
            case 'json':
                return json_encode($data);
            case 'xml':
                return $this->arrayToXml($data);
            default:
                return $data;
        }
    }

    private function arrayToCsv($data) {
        if (empty($data)) return '';

        $output = fopen('php://temp', 'r+');

        // Write headers
        fputcsv($output, array_keys($data[0]));

        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    private function arrayToXml($data) {
        $xml = new SimpleXMLElement('<submissions/>');

        foreach ($data as $row) {
            $submission = $xml->addChild('submission');
            foreach ($row as $key => $value) {
                $submission->addChild($key, htmlspecialchars($value));
            }
        }

        return $xml->asXML();
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
            $this->db->beginTransaction();

            // Create template
            $templateId = $this->db->insert('form_templates', [
                'company_id' => $this->user['company_id'],
                'template_name' => $data['template_name'],
                'description' => $data['description'] ?? '',
                'category' => $data['category'],
                'is_public' => $data['is_public'] ?? false,
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Copy form fields to template
            $this->copyFormFieldsToTemplate($data['form_id'], $templateId);

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'template_id' => $templateId,
                'message' => 'Template created successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function copyFormFieldsToTemplate($formId, $templateId) {
        $fields = $this->db->query("
            SELECT * FROM form_fields
            WHERE form_id = ?
            ORDER BY field_order ASC
        ", [$formId]);

        foreach ($fields as $field) {
            $this->db->insert('form_template_fields', [
                'template_id' => $templateId,
                'field_name' => $field['field_name'],
                'field_label' => $field['field_label'],
                'field_type' => $field['field_type'],
                'field_order' => $field['field_order'],
                'is_required' => $field['is_required'],
                'field_options' => $field['field_options'],
                'validation_rules' => $field['validation_rules']
            ]);
        }
    }

    public function getForm() {
        $formId = $_GET['id'] ?? null;

        if (!$formId) {
            $this->jsonResponse(['error' => 'Form ID required'], 400);
        }

        $form = $this->db->querySingle("
            SELECT
                f.*,
                f.form_title,
                f.description,
                f.category,
                f.status,
                f.total_submissions,
                f.created_at,
                u.first_name,
                u.last_name
            FROM forms f
            LEFT JOIN users u ON f.created_by = u.id
            WHERE f.id = ? AND f.company_id = ?
        ", [$formId, $this->user['company_id']]);

        if (!$form) {
            $this->jsonResponse(['error' => 'Form not found'], 404);
        }

        // Get form fields
        $form['fields'] = $this->db->query("
            SELECT * FROM form_fields
            WHERE form_id = ?
            ORDER BY field_order ASC
        ", [$formId]);

        // Get form settings
        $form['settings'] = $this->db->querySingle("
            SELECT * FROM form_settings WHERE form_id = ?
        ", [$formId]);

        $this->jsonResponse([
            'success' => true,
            'form' => $form
        ]);
    }

    public function deleteForm() {
        $this->requirePermission('forms.delete');

        $data = $this->validateRequest([
            'form_id' => 'required|integer'
        ]);

        try {
            $this->db->beginTransaction();

            // Delete form fields
            $this->db->delete('form_fields', 'form_id = ?', [$data['form_id']]);

            // Delete form settings
            $this->db->delete('form_settings', 'form_id = ?', [$data['form_id']]);

            // Delete form submissions
            $this->db->delete('form_submissions', 'form_id = ?', [$data['form_id']]);

            // Delete form
            $this->db->delete('forms', 'id = ? AND company_id = ?', [
                $data['form_id'],
                $this->user['company_id']
            ]);

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Form deleted successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
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
            $this->db->beginTransaction();

            // Get original form
            $originalForm = $this->db->querySingle("
                SELECT * FROM forms WHERE id = ? AND company_id = ?
            ", [$data['form_id'], $this->user['company_id']]);

            if (!$originalForm) {
                $this->jsonResponse(['error' => 'Form not found'], 404);
            }

            // Create duplicate form
            $newFormId = $this->db->insert('forms', [
                'company_id' => $this->user['company_id'],
                'form_title' => $data['new_title'],
                'description' => $originalForm['description'],
                'category' => $originalForm['category'],
                'template_id' => $originalForm['template_id'],
                'status' => 'draft',
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Copy form fields
            $this->copyFormFields($data['form_id'], $newFormId);

            // Copy form settings
            $this->copyFormSettings($data['form_id'], $newFormId);

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'form_id' => $newFormId,
                'message' => 'Form duplicated successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function copyFormFields($sourceFormId, $targetFormId) {
        $fields = $this->db->query("
            SELECT * FROM form_fields WHERE form_id = ?
        ", [$sourceFormId]);

        foreach ($fields as $field) {
            $this->db->insert('form_fields', [
                'form_id' => $targetFormId,
                'field_name' => $field['field_name'],
                'field_label' => $field['field_label'],
                'field_type' => $field['field_type'],
                'field_order' => $field['field_order'],
                'is_required' => $field['is_required'],
                'field_options' => $field['field_options'],
                'validation_rules' => $field['validation_rules']
            ]);
        }
    }

    private function copyFormSettings($sourceFormId, $targetFormId) {
        $settings = $this->db->querySingle("
            SELECT * FROM form_settings WHERE form_id = ?
        ", [$sourceFormId]);

        if ($settings) {
            $this->db->insert('form_settings', [
                'form_id' => $targetFormId,
                'settings' => $settings['settings']
            ]);
        }
    }

    public function rateTemplate() {
        $data = $this->validateRequest([
            'template_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'string'
        ]);

        try {
            // Check if user already rated this template
            $existing = $this->db->querySingle("
                SELECT id FROM template_ratings
                WHERE template_id = ? AND user_id = ? AND company_id = ?
            ", [$data['template_id'], $this->user['id'], $this->user['company_id']]);

            if ($existing) {
                $this->db->update('template_ratings', [
                    'rating' => $data['rating'],
                    'review' => $data['review'] ?? '',
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$existing['id']]);
            } else {
                $this->db->insert('template_ratings', [
                    'company_id' => $this->user['company_id'],
                    'template_id' => $data['template_id'],
                    'user_id' => $this->user['id'],
                    'rating' => $data['rating'],
                    'review' => $data['review'] ?? ''
                ]);
            }

            // Update template average rating
            $this->updateTemplateRating($data['template_id']);

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

    private function updateTemplateRating($templateId) {
        $avgRating = $this->db->querySingle("
            SELECT AVG(rating) as avg_rating
            FROM template_ratings
            WHERE template_id = ?
        ", [$templateId]);

        $this->db->update('form_templates', [
            'avg_rating' => $avgRating['avg_rating'] ?? 0
        ], 'id = ?', [$templateId]);
    }

    public function getFormAnalyticsData() {
        $formId = $_GET['form_id'] ?? null;
        $timeRange = $_GET['range'] ?? '30d';

        if (!$formId) {
            $this->jsonResponse(['error' => 'Form ID required'], 400);
        }

        $analytics = $this->getDetailedFormAnalytics($formId);

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
            $updated = 0;

            foreach ($data['form_ids'] as $formId) {
                switch ($data['action']) {
                    case 'publish':
                        $this->db->update('forms', [
                            'status' => 'published',
                            'published_at' => date('Y-m-d H:i:s'),
                            'published_by' => $this->user['id']
                        ], 'id = ? AND company_id = ?', [$formId, $this->user['company_id']]);
                        $updated++;
                        break;

                    case 'unpublish':
                        $this->db->update('forms', [
                            'status' => 'unpublished'
                        ], 'id = ? AND company_id = ?', [$formId, $this->user['company_id']]);
                        $updated++;
                        break;

                    case 'archive':
                        $this->db->update('forms', [
                            'status' => 'archived'
                        ], 'id = ? AND company_id = ?', [$formId, $this->user['company_id']]);
                        $updated++;
                        break;

                    case 'category':
                        if (isset($data['parameters']['category'])) {
                            $this->db->update('forms', [
                                'category' => $data['parameters']['category']
                            ], 'id = ? AND company_id = ?', [$formId, $this->user['company_id']]);
                            $updated++;
                        }
                        break;
                }
            }

            $this->jsonResponse([
                'success' => true,
                'updated_count' => $updated,
                'message' => "Successfully updated $updated forms"
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
