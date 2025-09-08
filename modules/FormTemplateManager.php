<?php
/**
 * TPT Free ERP - Form Template Manager
 * Handles form template management
 */

class FormTemplateManager {
    private $db;
    private $user;

    public function __construct() {
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Get template categories
     */
    public function getTemplateCategories() {
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

    /**
     * Get featured templates
     */
    public function getFeaturedTemplates() {
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

    /**
     * Get custom templates
     */
    public function getCustomTemplates() {
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

    /**
     * Get template usage
     */
    public function getTemplateUsage() {
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

    /**
     * Get template ratings
     */
    public function getTemplateRatings() {
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

    /**
     * Get template by ID
     */
    public function getTemplate($templateId) {
        return $this->db->querySingle("
            SELECT * FROM form_templates WHERE id = ? AND company_id = ?
        ", [$templateId, $this->user['company_id']]);
    }

    /**
     * Get template fields
     */
    public function getTemplateFields($templateId) {
        return $this->db->query("
            SELECT * FROM form_template_fields
            WHERE template_id = ?
            ORDER BY field_order ASC
        ", [$templateId]);
    }

    /**
     * Create template
     */
    public function createTemplate($data) {
        $this->db->beginTransaction();

        try {
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
            return $templateId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update template
     */
    public function updateTemplate($templateId, $data) {
        return $this->db->update('form_templates', [
            'template_name' => $data['template_name'],
            'description' => $data['description'] ?? '',
            'category' => $data['category'],
            'is_public' => $data['is_public'] ?? false,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ? AND company_id = ?', [$templateId, $this->user['company_id']]);
    }

    /**
     * Delete template
     */
    public function deleteTemplate($templateId) {
        $this->db->beginTransaction();

        try {
            // Delete template fields
            $this->db->delete('form_template_fields', 'template_id = ?', [$templateId]);

            // Delete template ratings
            $this->db->delete('template_ratings', 'template_id = ?', [$templateId]);

            // Delete template
            $this->db->delete('form_templates', 'id = ? AND company_id = ?', [
                $templateId,
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
     * Rate template
     */
    public function rateTemplate($templateId, $rating, $review = '') {
        // Check if user already rated this template
        $existing = $this->db->querySingle("
            SELECT id FROM template_ratings
            WHERE template_id = ? AND user_id = ? AND company_id = ?
        ", [$templateId, $this->user['id'], $this->user['company_id']]);

        if ($existing) {
            $this->db->update('template_ratings', [
                'rating' => $rating,
                'review' => $review,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('template_ratings', [
                'company_id' => $this->user['company_id'],
                'template_id' => $templateId,
                'user_id' => $this->user['id'],
                'rating' => $rating,
                'review' => $review
            ]);
        }

        // Update template average rating
        $this->updateTemplateRating($templateId);

        return true;
    }

    /**
     * Get template statistics
     */
    public function getTemplateStatistics($templateId) {
        return $this->db->querySingle("
            SELECT
                ft.template_name,
                ft.usage_count,
                ft.avg_rating,
                COUNT(tr.id) as total_ratings,
                COUNT(f.id) as forms_created,
                AVG(f.total_submissions) as avg_submissions_per_form,
                MAX(f.created_at) as last_used
            FROM form_templates ft
            LEFT JOIN template_ratings tr ON ft.id = tr.template_id
            LEFT JOIN forms f ON ft.id = f.template_id
            WHERE ft.id = ? AND ft.company_id = ?
            GROUP BY ft.id, ft.template_name, ft.usage_count, ft.avg_rating
        ", [$templateId, $this->user['company_id']]);
    }

    /**
     * Get popular templates by category
     */
    public function getPopularTemplatesByCategory($category) {
        return $this->db->query("
            SELECT
                ft.*,
                ft.template_name,
                ft.description,
                ft.preview_image,
                ft.usage_count,
                ft.avg_rating,
                COUNT(f.id) as forms_created
            FROM form_templates ft
            LEFT JOIN forms f ON ft.id = f.template_id
            WHERE ft.company_id = ? AND ft.category = ? AND ft.is_public = true
            GROUP BY ft.id
            ORDER BY ft.usage_count DESC, ft.avg_rating DESC
            LIMIT 10
        ", [$this->user['company_id'], $category]);
    }

    /**
     * Search templates
     */
    public function searchTemplates($query, $category = null) {
        $where = ["ft.company_id = ? AND ft.is_public = true"];
        $params = [$this->user['company_id']];

        if (!empty($query)) {
            $where[] = "(ft.template_name LIKE ? OR ft.description LIKE ?)";
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }

        if ($category) {
            $where[] = "ft.category = ?";
            $params[] = $category;
        }

        $whereClause = implode(' AND ', $where);

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
            WHERE $whereClause
            GROUP BY ft.id
            ORDER BY ft.usage_count DESC, ft.avg_rating DESC
        ", $params);
    }

    /**
     * Duplicate template
     */
    public function duplicateTemplate($templateId, $newName) {
        $this->db->beginTransaction();

        try {
            // Get original template
            $originalTemplate = $this->getTemplate($templateId);

            if (!$originalTemplate) {
                throw new Exception('Template not found');
            }

            // Create duplicate template
            $newTemplateId = $this->db->insert('form_templates', [
                'company_id' => $this->user['company_id'],
                'template_name' => $newName,
                'description' => $originalTemplate['description'],
                'category' => $originalTemplate['category'],
                'is_public' => false, // Duplicates are private by default
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Copy template fields
            $this->copyTemplateFields($templateId, $newTemplateId);

            $this->db->commit();
            return $newTemplateId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Export template
     */
    public function exportTemplate($templateId) {
        $template = $this->getTemplate($templateId);
        $fields = $this->getTemplateFields($templateId);

        if (!$template) {
            throw new Exception('Template not found');
        }

        return [
            'template' => $template,
            'fields' => $fields,
            'exported_at' => date('Y-m-d H:i:s'),
            'exported_by' => $this->user['id']
        ];
    }

    /**
     * Import template
     */
    public function importTemplate($templateData) {
        $this->db->beginTransaction();

        try {
            // Create template
            $templateId = $this->db->insert('form_templates', [
                'company_id' => $this->user['company_id'],
                'template_name' => $templateData['template']['template_name'],
                'description' => $templateData['template']['description'] ?? '',
                'category' => $templateData['template']['category'],
                'is_public' => false, // Imported templates are private by default
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Import template fields
            foreach ($templateData['fields'] as $field) {
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

            $this->db->commit();
            return $templateId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get template usage history
     */
    public function getTemplateUsageHistory($templateId) {
        return $this->db->query("
            SELECT
                f.form_title,
                f.created_at as used_at,
                f.total_submissions,
                u.first_name,
                u.last_name
            FROM forms f
            LEFT JOIN users u ON f.created_by = u.id
            WHERE f.template_id = ? AND f.company_id = ?
            ORDER BY f.created_at DESC
        ", [$templateId, $this->user['company_id']]);
    }

    /**
     * Copy form fields to template
     */
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

    /**
     * Copy template fields
     */
    private function copyTemplateFields($sourceTemplateId, $targetTemplateId) {
        $fields = $this->db->query("
            SELECT * FROM form_template_fields WHERE template_id = ?
        ", [$sourceTemplateId]);

        foreach ($fields as $field) {
            $this->db->insert('form_template_fields', [
                'template_id' => $targetTemplateId,
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
     * Update template rating
     */
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

    /**
     * Get current user
     */
    private function getCurrentUser() {
        // This should be implemented to get the current user from session/auth
        return $_SESSION['user'] ?? null;
    }
}
