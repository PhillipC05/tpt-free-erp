<?php
/**
 * TPT Free ERP - Form Manager
 * Handles form CRUD operations and management
 */

class FormManager {
    private $db;
    private $user;

    public function __construct() {
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Get form by ID
     */
    public function getForm($formId) {
        return $this->db->querySingle("
            SELECT * FROM forms WHERE id = ? AND company_id = ?
        ", [$formId, $this->user['company_id']]);
    }

    /**
     * Get form data with fields and settings
     */
    public function getFormData($formId) {
        $form = $this->getForm($formId);

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

    /**
     * Get recent forms
     */
    public function getRecentForms() {
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

    /**
     * Get all forms
     */
    public function getAllForms() {
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

    /**
     * Get form categories
     */
    public function getFormCategories() {
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

    /**
     * Get form status options
     */
    public function getFormStatus() {
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

    /**
     * Get form permissions
     */
    public function getFormPermissions() {
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

    /**
     * Get bulk actions
     */
    public function getBulkActions() {
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

    /**
     * Get export options
     */
    public function getExportOptions() {
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

    /**
     * Get popular templates
     */
    public function getPopularTemplates() {
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

    /**
     * Get form templates
     */
    public function getFormTemplates() {
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

    /**
     * Get form integrations
     */
    public function getFormIntegrations() {
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

    /**
     * Get workflow templates
     */
    public function getWorkflowTemplates() {
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

    /**
     * Get active workflows
     */
    public function getActiveWorkflows() {
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

    /**
     * Get approval processes
     */
    public function getApprovalProcesses() {
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

    /**
     * Get notification rules
     */
    public function getNotificationRules() {
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

    /**
     * Create form
     */
    public function createForm($data) {
        $this->db->beginTransaction();

        try {
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
            return $formId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update form
     */
    public function updateForm($formId, $data) {
        return $this->db->update('forms', [
            'form_title' => $data['form_title'],
            'description' => $data['description'] ?? '',
            'category' => $data['category'] ?? 'custom',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ? AND company_id = ?', [$formId, $this->user['company_id']]);
    }

    /**
     * Delete form
     */
    public function deleteForm($formId) {
        $this->db->beginTransaction();

        try {
            // Delete form fields
            $this->db->delete('form_fields', 'form_id = ?', [$formId]);

            // Delete form settings
            $this->db->delete('form_settings', 'form_id = ?', [$formId]);

            // Delete form submissions
            $this->db->delete('form_submissions', 'form_id = ?', [$formId]);

            // Delete form
            $this->db->delete('forms', 'id = ? AND company_id = ?', [
                $formId,
                $this->user['company_id']
            ]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Publish form
     */
    public function publishForm($formId) {
        return $this->db->update('forms', [
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s'),
            'published_by' => $this->user['id']
        ], 'id = ? AND company_id = ?', [$formId, $this->user['company_id']]);
    }

    /**
     * Duplicate form
     */
    public function duplicateForm($formId, $newTitle) {
        $this->db->beginTransaction();

        try {
            // Get original form
            $originalForm = $this->getForm($formId);

            if (!$originalForm) {
                throw new Exception('Form not found');
            }

            // Create duplicate form
            $newFormId = $this->db->insert('forms', [
                'company_id' => $this->user['company_id'],
                'form_title' => $newTitle,
                'description' => $originalForm['description'],
                'category' => $originalForm['category'],
                'template_id' => $originalForm['template_id'],
                'status' => 'draft',
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Copy form fields
            $this->copyFormFields($formId, $newFormId);

            // Copy form settings
            $this->copyFormSettings($formId, $newFormId);

            $this->db->commit();
            return $newFormId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Copy template fields to form
     */
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

    /**
     * Copy form fields
     */
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

    /**
     * Copy form settings
     */
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

    /**
     * Get current user
     */
    private function getCurrentUser() {
        // This should be implemented to get the current user from session/auth
        return $_SESSION['user'] ?? null;
    }
}
