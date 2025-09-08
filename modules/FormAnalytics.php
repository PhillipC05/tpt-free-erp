<?php
/**
 * TPT Free ERP - Form Analytics
 * Handles form analytics and reporting
 */

class FormAnalytics {
    private $db;
    private $user;

    public function __construct() {
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Get forms overview
     */
    public function getFormsOverview() {
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

    /**
     * Get form analytics summary
     */
    public function getFormAnalyticsSummary() {
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

    /**
     * Get submission trends
     */
    public function getSubmissionTrends() {
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

    /**
     * Get form performance
     */
    public function getFormPerformance() {
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

    /**
     * Get detailed form analytics
     */
    public function getDetailedFormAnalytics($formId = null) {
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

    /**
     * Get field analytics
     */
    public function getFieldAnalytics($formId) {
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

    /**
     * Get conversion funnel
     */
    public function getConversionFunnel($formId) {
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

    /**
     * Get user journey
     */
    public function getUserJourney($formId) {
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

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($formId) {
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

    /**
     * Get comparison reports
     */
    public function getComparisonReports() {
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

    /**
     * Get workflow analytics
     */
    public function getWorkflowAnalytics() {
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

    /**
     * Generate basic analytics report
     */
    public function generateBasicAnalyticsReport($form) {
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

    /**
     * Generate field performance report
     */
    public function generateFieldPerformanceReport($form) {
        $fieldAnalytics = $this->getFieldAnalytics($form['id']);

        return [
            'report_type' => 'field_performance',
            'form_id' => $form['id'],
            'form_title' => $form['form_title'],
            'field_analytics' => $fieldAnalytics,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate submission trends report
     */
    public function generateSubmissionTrendsReport($form) {
        $trends = $this->getFormSubmissionTrends($form['id']);

        return [
            'report_type' => 'submission_trends',
            'form_id' => $form['id'],
            'form_title' => $form['form_title'],
            'trends' => $trends,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate completion analysis report
     */
    public function generateCompletionAnalysisReport($form) {
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

    /**
     * Generate form dashboard
     */
    public function generateFormDashboard($form) {
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
                    'data' => $this->getFormSubmissionTrends($form['id'])
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

    /**
     * Suggest field improvements
     */
    public function suggestFieldImprovements($analytics) {
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

    /**
     * Suggest layout improvements
     */
    public function suggestLayoutImprovements($analytics) {
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

    /**
     * Suggest content improvements
     */
    public function suggestContentImprovements($analytics) {
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

    /**
     * Get form report ID
     */
    public function getFormReportId($formId) {
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

    /**
     * Get total submissions
     */
    private function getTotalSubmissions($formId) {
        $result = $this->db->querySingle("
            SELECT COUNT(*) as count FROM form_submissions
            WHERE form_id = ? AND company_id = ?
        ", [$formId, $this->user['company_id']]);

        return $result['count'] ?? 0;
    }

    /**
     * Get completion rate
     */
    private function getCompletionRate($formId) {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'complete' THEN 1 END) * 100.0 / COUNT(*) as rate
            FROM form_submissions
            WHERE form_id = ? AND company_id = ?
        ", [$formId, $this->user['company_id']]);

        return round($result['rate'] ?? 0, 2);
    }

    /**
     * Get average completion time
     */
    private function getAverageCompletionTime($formId) {
        $result = $this->db->querySingle("
            SELECT AVG(completion_time_seconds) as avg_time
            FROM form_submissions
            WHERE form_id = ? AND company_id = ? AND status = 'complete'
        ", [$formId, $this->user['company_id']]);

        return round($result['avg_time'] ?? 0, 2);
    }

    /**
     * Get form submission trends
     */
    private function getFormSubmissionTrends($formId) {
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

    /**
     * Get device breakdown
     */
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

    /**
     * Get current user
     */
    private function getCurrentUser() {
        // This should be implemented to get the current user from session/auth
        return $_SESSION['user'] ?? null;
    }
}
