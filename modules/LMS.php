<?php
/**
 * TPT Free ERP - Learning Management System Module
 * Complete course management, student enrollment, certification, and compliance training system
 */

class LMS extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main LMS dashboard
     */
    public function index() {
        $this->requirePermission('lms.view');

        $data = [
            'title' => 'Learning Management System',
            'course_overview' => $this->getCourseOverview(),
            'enrollment_stats' => $this->getEnrollmentStats(),
            'certification_status' => $this->getCertificationStatus(),
            'training_compliance' => $this->getTrainingCompliance(),
            'assessment_results' => $this->getAssessmentResults(),
            'learning_analytics' => $this->getLearningAnalytics(),
            'upcoming_deadlines' => $this->getUpcomingDeadlines(),
            'training_alerts' => $this->getTrainingAlerts()
        ];

        $this->render('modules/lms/dashboard', $data);
    }

    /**
     * Course management
     */
    public function courses() {
        $this->requirePermission('lms.courses.view');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'category' => $_GET['category'] ?? null,
            'instructor' => $_GET['instructor'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $courses = $this->getCourses($filters);

        $data = [
            'title' => 'Course Management',
            'courses' => $courses,
            'filters' => $filters,
            'course_categories' => $this->getCourseCategories(),
            'course_status' => $this->getCourseStatus(),
            'instructors' => $this->getInstructors(),
            'course_templates' => $this->getCourseTemplates(),
            'bulk_actions' => $this->getBulkActions(),
            'course_analytics' => $this->getCourseAnalytics()
        ];

        $this->render('modules/lms/courses', $data);
    }

    /**
     * Student enrollment management
     */
    public function enrollments() {
        $this->requirePermission('lms.enrollments.view');

        $data = [
            'title' => 'Student Enrollments',
            'enrollments' => $this->getEnrollments(),
            'enrollment_stats' => $this->getEnrollmentStats(),
            'enrollment_trends' => $this->getEnrollmentTrends(),
            'student_progress' => $this->getStudentProgress(),
            'enrollment_reports' => $this->getEnrollmentReports(),
            'enrollment_templates' => $this->getEnrollmentTemplates(),
            'enrollment_analytics' => $this->getEnrollmentAnalytics(),
            'enrollment_settings' => $this->getEnrollmentSettings()
        ];

        $this->render('modules/lms/enrollments', $data);
    }

    /**
     * Certification management
     */
    public function certifications() {
        $this->requirePermission('lms.certifications.view');

        $data = [
            'title' => 'Certification Management',
            'certifications' => $this->getCertifications(),
            'certification_templates' => $this->getCertificationTemplates(),
            'certification_requirements' => $this->getCertificationRequirements(),
            'certification_exams' => $this->getCertificationExams(),
            'certification_tracking' => $this->getCertificationTracking(),
            'certification_reports' => $this->getCertificationReports(),
            'certification_analytics' => $this->getCertificationAnalytics(),
            'certification_settings' => $this->getCertificationSettings()
        ];

        $this->render('modules/lms/certifications', $data);
    }

    /**
     * Assessment and testing
     */
    public function assessments() {
        $this->requirePermission('lms.assessments.view');

        $data = [
            'title' => 'Assessments & Testing',
            'assessments' => $this->getAssessments(),
            'assessment_templates' => $this->getAssessmentTemplates(),
            'assessment_results' => $this->getAssessmentResults(),
            'assessment_analytics' => $this->getAssessmentAnalytics(),
            'question_bank' => $this->getQuestionBank(),
            'assessment_reports' => $this->getAssessmentReports(),
            'assessment_settings' => $this->getAssessmentSettings(),
            'grading_system' => $this->getGradingSystem()
        ];

        $this->render('modules/lms/assessments', $data);
    }

    /**
     * Compliance training
     */
    public function compliance() {
        $this->requirePermission('lms.compliance.view');

        $data = [
            'title' => 'Compliance Training',
            'compliance_requirements' => $this->getComplianceRequirements(),
            'compliance_courses' => $this->getComplianceCourses(),
            'compliance_tracking' => $this->getComplianceTracking(),
            'compliance_reports' => $this->getComplianceReports(),
            'compliance_alerts' => $this->getComplianceAlerts(),
            'compliance_analytics' => $this->getComplianceAnalytics(),
            'compliance_templates' => $this->getComplianceTemplates(),
            'compliance_settings' => $this->getComplianceSettings()
        ];

        $this->render('modules/lms/compliance', $data);
    }

    /**
     * Learning analytics
     */
    public function analytics() {
        $this->requirePermission('lms.analytics.view');

        $data = [
            'title' => 'Learning Analytics',
            'learning_metrics' => $this->getLearningMetrics(),
            'student_engagement' => $this->getStudentEngagement(),
            'course_effectiveness' => $this->getCourseEffectiveness(),
            'learning_trends' => $this->getLearningTrends(),
            'performance_insights' => $this->getPerformanceInsights(),
            'predictive_analytics' => $this->getPredictiveAnalytics(),
            'benchmarking' => $this->getBenchmarking(),
            'custom_reports' => $this->getCustomReports()
        ];

        $this->render('modules/lms/analytics', $data);
    }

    /**
     * Content management
     */
    public function content() {
        $this->requirePermission('lms.content.view');

        $data = [
            'title' => 'Content Management',
            'content_library' => $this->getContentLibrary(),
            'content_categories' => $this->getContentCategories(),
            'content_authors' => $this->getContentAuthors(),
            'content_reviews' => $this->getContentReviews(),
            'content_analytics' => $this->getContentAnalytics(),
            'content_templates' => $this->getContentTemplates(),
            'content_settings' => $this->getContentSettings(),
            'media_library' => $this->getMediaLibrary()
        ];

        $this->render('modules/lms/content', $data);
    }

    /**
     * Instructor management
     */
    public function instructors() {
        $this->requirePermission('lms.instructors.view');

        $data = [
            'title' => 'Instructor Management',
            'instructors' => $this->getInstructors(),
            'instructor_performance' => $this->getInstructorPerformance(),
            'instructor_courses' => $this->getInstructorCourses(),
            'instructor_ratings' => $this->getInstructorRatings(),
            'instructor_analytics' => $this->getInstructorAnalytics(),
            'instructor_templates' => $this->getInstructorTemplates(),
            'instructor_settings' => $this->getInstructorSettings(),
            'instructor_reports' => $this->getInstructorReports()
        ];

        $this->render('modules/lms/instructors', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getCourseOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT c.id) as total_courses,
                COUNT(CASE WHEN c.status = 'published' THEN 1 END) as published_courses,
                COUNT(CASE WHEN c.status = 'draft' THEN 1 END) as draft_courses,
                COUNT(CASE WHEN c.status = 'archived' THEN 1 END) as archived_courses,
                SUM(c.enrollment_count) as total_enrollments,
                AVG(c.completion_rate) as avg_completion_rate,
                COUNT(CASE WHEN c.next_session_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as upcoming_sessions,
                COUNT(CASE WHEN c.certification_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_certifications,
                AVG(c.student_rating) as avg_course_rating
            FROM courses c
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEnrollmentStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(e.id) as total_enrollments,
                COUNT(CASE WHEN e.status = 'active' THEN 1 END) as active_enrollments,
                COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completed_enrollments,
                COUNT(CASE WHEN e.status = 'dropped' THEN 1 END) as dropped_enrollments,
                ROUND((COUNT(CASE WHEN e.status = 'completed' THEN 1 END) / NULLIF(COUNT(e.id), 0)) * 100, 2) as completion_rate,
                AVG(e.progress_percentage) as avg_progress,
                COUNT(CASE WHEN e.due_date <= CURDATE() AND e.status = 'active' THEN 1 END) as overdue_enrollments,
                COUNT(CASE WHEN e.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND e.status = 'active' THEN 1 END) as due_soon
            FROM enrollments e
            WHERE e.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCertificationStatus() {
        return $this->db->query("
            SELECT
                ct.certification_name,
                COUNT(sc.id) as total_certifications,
                COUNT(CASE WHEN sc.status = 'active' THEN 1 END) as active_certifications,
                COUNT(CASE WHEN sc.status = 'expired' THEN 1 END) as expired_certifications,
                COUNT(CASE WHEN sc.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon,
                AVG(sc.score) as avg_score,
                COUNT(CASE WHEN sc.score >= 80 THEN 1 END) as passed_certifications
            FROM certification_templates ct
            LEFT JOIN student_certifications sc ON ct.id = sc.certification_id
            WHERE ct.company_id = ?
            GROUP BY ct.id, ct.certification_name
            ORDER BY total_certifications DESC
        ", [$this->user['company_id']]);
    }

    private function getTrainingCompliance() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT u.id) as total_employees,
                COUNT(CASE WHEN tc.compliance_status = 'compliant' THEN 1 END) as compliant_employees,
                ROUND((COUNT(CASE WHEN tc.compliance_status = 'compliant' THEN 1 END) / NULLIF(COUNT(DISTINCT u.id), 0)) * 100, 2) as compliance_rate,
                COUNT(CASE WHEN tc.next_training_date <= CURDATE() THEN 1 END) as overdue_training,
                COUNT(CASE WHEN tc.next_training_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as training_due_soon,
                AVG(tc.compliance_score) as avg_compliance_score,
                COUNT(CASE WHEN tc.compliance_score < 70 THEN 1 END) as low_compliance
            FROM users u
            LEFT JOIN training_compliance tc ON u.id = tc.user_id
            WHERE u.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAssessmentResults() {
        return $this->db->query("
            SELECT
                a.assessment_name,
                COUNT(ar.id) as total_attempts,
                AVG(ar.score) as avg_score,
                COUNT(CASE WHEN ar.passed = true THEN 1 END) as passed_attempts,
                ROUND((COUNT(CASE WHEN ar.passed = true THEN 1 END) / NULLIF(COUNT(ar.id), 0)) * 100, 2) as pass_rate,
                MIN(ar.score) as min_score,
                MAX(ar.score) as max_score,
                AVG(ar.completion_time) as avg_completion_time
            FROM assessments a
            LEFT JOIN assessment_results ar ON a.id = ar.assessment_id
            WHERE a.company_id = ?
            GROUP BY a.id, a.assessment_name
            ORDER BY total_attempts DESC
        ", [$this->user['company_id']]);
    }

    private function getLearningAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT c.id) as total_courses,
                COUNT(DISTINCT e.student_id) as total_students,
                AVG(e.progress_percentage) as avg_progress,
                AVG(c.completion_rate) as avg_completion_rate,
                SUM(c.total_learning_hours) as total_learning_hours,
                AVG(c.student_rating) as avg_course_rating,
                COUNT(CASE WHEN e.last_activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as active_students_week,
                COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completed_enrollments
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getUpcomingDeadlines() {
        return $this->db->query("
            SELECT
                c.course_name,
                u.first_name,
                u.last_name,
                e.due_date,
                e.progress_percentage,
                TIMESTAMPDIFF(DAY, CURDATE(), e.due_date) as days_until_due,
                CASE
                    WHEN e.due_date <= CURDATE() THEN 'overdue'
                    WHEN e.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'due_soon'
                    ELSE 'upcoming'
                END as deadline_status
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            JOIN users u ON e.student_id = u.id
            WHERE e.company_id = ? AND e.status = 'active' AND e.due_date >= CURDATE()
            ORDER BY e.due_date ASC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getTrainingAlerts() {
        return $this->db->query("
            SELECT
                ta.*,
                ta.alert_type,
                ta.severity,
                ta.message,
                ta.user_id,
                ta.course_id,
                ta.created_at,
                TIMESTAMPDIFF(MINUTE, ta.created_at, NOW()) as minutes_since_alert
            FROM training_alerts ta
            WHERE ta.company_id = ? AND ta.status = 'active'
            ORDER BY ta.severity DESC, ta.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCourses($filters = []) {
        $where = ["c.company_id = ?"];
        $params = [$this->user['company_id']];

        if (isset($filters['status'])) {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['category'])) {
            $where[] = "c.category_id = ?";
            $params[] = $filters['category'];
        }

        if (isset($filters['instructor'])) {
            $where[] = "c.instructor_id = ?";
            $params[] = $filters['instructor'];
        }

        if (isset($filters['date_from'])) {
            $where[] = "c.created_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (isset($filters['date_to'])) {
            $where[] = "c.created_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (isset($filters['search'])) {
            $where[] = "(c.course_name LIKE ? OR c.description LIKE ? OR c.course_code LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                c.*,
                cc.category_name,
                u.first_name as instructor_first,
                u.last_name as instructor_last,
                c.enrollment_count,
                c.completion_rate,
                c.student_rating,
                c.total_learning_hours,
                TIMESTAMPDIFF(DAY, CURDATE(), c.next_session_date) as days_until_next_session,
                TIMESTAMPDIFF(DAY, CURDATE(), c.certification_expiry) as days_until_certification_expiry
            FROM courses c
            LEFT JOIN course_categories cc ON c.category_id = cc.id
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE $whereClause
            ORDER BY c.created_date DESC
        ", $params);
    }

    private function getCourseCategories() {
        return $this->db->query("
            SELECT
                cc.*,
                COUNT(c.id) as course_count,
                SUM(c.enrollment_count) as total_enrollments,
                AVG(c.completion_rate) as avg_completion_rate
            FROM course_categories cc
            LEFT JOIN courses c ON cc.id = c.category_id
            WHERE cc.company_id = ?
            GROUP BY cc.id
            ORDER BY course_count DESC
        ", [$this->user['company_id']]);
    }

    private function getCourseStatus() {
        return [
            'draft' => 'Draft',
            'review' => 'Under Review',
            'published' => 'Published',
            'archived' => 'Archived',
            'retired' => 'Retired'
        ];
    }

    private function getInstructors() {
        return $this->db->query("
            SELECT
                u.id,
                u.first_name,
                u.last_name,
                COUNT(c.id) as courses_taught,
                SUM(c.enrollment_count) as total_students,
                AVG(c.student_rating) as avg_rating,
                AVG(c.completion_rate) as avg_completion_rate,
                i.specialization,
                i.certifications,
                i.teaching_experience_years
            FROM users u
            JOIN instructors i ON u.id = i.user_id
            LEFT JOIN courses c ON u.id = c.instructor_id
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name, i.specialization, i.certifications, i.teaching_experience_years
            ORDER BY courses_taught DESC
        ", [$this->user['company_id']]);
    }

    private function getCourseTemplates() {
        return $this->db->query("
            SELECT * FROM course_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBulkActions() {
        return [
            'publish_courses' => 'Publish Courses',
            'archive_courses' => 'Archive Courses',
            'duplicate_courses' => 'Duplicate Courses',
            'update_category' => 'Update Category',
            'assign_instructor' => 'Assign Instructor',
            'export_courses' => 'Export Course Data',
            'import_courses' => 'Import Course Data',
            'bulk_enrollment' => 'Bulk Enrollment',
            'generate_reports' => 'Generate Reports'
        ];
    }

    private function getCourseAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(c.id) as total_courses,
                ROUND((COUNT(CASE WHEN c.status = 'published' THEN 1 END) / NULLIF(COUNT(c.id), 0)) * 100, 2) as published_percentage,
                AVG(c.enrollment_count) as avg_enrollment,
                AVG(c.completion_rate) as avg_completion_rate,
                AVG(c.student_rating) as avg_rating,
                SUM(c.total_learning_hours) as total_learning_hours,
                COUNT(CASE WHEN c.next_session_date <= CURDATE() THEN 1 END) as courses_with_sessions,
                COUNT(CASE WHEN c.certification_expiry <= CURDATE() THEN 1 END) as expired_certifications
            FROM courses c
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEnrollments() {
        return $this->db->query("
            SELECT
                e.*,
                c.course_name,
                c.course_code,
                u.first_name as student_first,
                u.last_name as student_last,
                e.enrollment_date,
                e.progress_percentage,
                e.last_activity_date,
                e.due_date,
                TIMESTAMPDIFF(DAY, CURDATE(), e.due_date) as days_until_due,
                e.completion_date,
                e.final_score,
                e.certification_earned
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            JOIN users u ON e.student_id = u.id
            WHERE e.company_id = ?
            ORDER BY e.enrollment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getEnrollmentTrends() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(e.enrollment_date, '%Y-%m') as month,
                COUNT(e.id) as enrollments,
                COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completions,
                ROUND((COUNT(CASE WHEN e.status = 'completed' THEN 1 END) / NULLIF(COUNT(e.id), 0)) * 100, 2) as completion_rate,
                AVG(e.progress_percentage) as avg_progress
            FROM enrollments e
            WHERE e.company_id = ? AND e.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(e.enrollment_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getStudentProgress() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                COUNT(e.id) as enrolled_courses,
                COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completed_courses,
                ROUND((COUNT(CASE WHEN e.status = 'completed' THEN 1 END) / NULLIF(COUNT(e.id), 0)) * 100, 2) as completion_rate,
                AVG(e.progress_percentage) as avg_progress,
                SUM(c.total_learning_hours) as total_learning_hours,
                AVG(e.final_score) as avg_score,
                MAX(e.last_activity_date) as last_activity
            FROM users u
            LEFT JOIN enrollments e ON u.id = e.student_id
            LEFT JOIN courses c ON e.course_id = c.id
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY avg_progress DESC
        ", [$this->user['company_id']]);
    }

    private function getEnrollmentReports() {
        return $this->db->query("
            SELECT
                er.*,
                er.report_type,
                er.report_period,
                er.generated_date,
                er.total_enrollments,
                er.completion_rate,
                er.avg_progress
            FROM enrollment_reports er
            WHERE er.company_id = ?
            ORDER BY er.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getEnrollmentTemplates() {
        return $this->db->query("
            SELECT * FROM enrollment_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getEnrollmentAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT u.id) as total_students,
                COUNT(e.id) as total_enrollments,
                AVG(e.progress_percentage) as avg_progress,
                ROUND((COUNT(CASE WHEN e.status = 'completed' THEN 1 END) / NULLIF(COUNT(e.id), 0)) * 100, 2) as overall_completion_rate,
                COUNT(CASE WHEN e.due_date <= CURDATE() AND e.status = 'active' THEN 1 END) as overdue_enrollments,
                COUNT(CASE WHEN e.last_activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as active_students,
                AVG(TIMESTAMPDIFF(DAY, e.enrollment_date, COALESCE(e.completion_date, CURDATE()))) as avg_completion_days
            FROM users u
            LEFT JOIN enrollments e ON u.id = e.student_id
            WHERE u.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEnrollmentSettings() {
        return $this->db->querySingle("
            SELECT * FROM enrollment_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCertifications() {
        return $this->db->query("
            SELECT
                sc.*,
                ct.certification_name,
                ct.description,
                u.first_name as student_first,
                u.last_name as student_last,
                sc.issue_date,
                sc.expiry_date,
                sc.score,
                sc.status,
                TIMESTAMPDIFF(DAY, CURDATE(), sc.expiry_date) as days_until_expiry
            FROM student_certifications sc
            JOIN certification_templates ct ON sc.certification_id = ct.id
            JOIN users u ON sc.student_id = u.id
            WHERE sc.company_id = ?
            ORDER BY sc.issue_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCertificationTemplates() {
        return $this->db->query("
            SELECT
                ct.*,
                COUNT(sc.id) as total_awarded,
                COUNT(CASE WHEN sc.status = 'active' THEN 1 END) as active_certifications,
                AVG(sc.score) as avg_score,
                COUNT(CASE WHEN sc.expiry_date <= CURDATE() THEN 1 END) as expired_certifications
            FROM certification_templates ct
            LEFT JOIN student_certifications sc ON ct.id = sc.certification_id
            WHERE ct.company_id = ?
            GROUP BY ct.id
            ORDER BY total_awarded DESC
        ", [$this->user['company_id']]);
    }

    private function getCertificationRequirements() {
        return $this->db->query("
            SELECT
                cr.*,
                ct.certification_name,
                c.course_name,
                cr.requirement_type,
                cr.description,
                cr.is_mandatory,
                cr.minimum_score,
                cr.validity_period_months
            FROM certification_requirements cr
            JOIN certification_templates ct ON cr.certification_id = ct.id
            LEFT JOIN courses c ON cr.course_id = c.id
            WHERE cr.company_id = ?
            ORDER BY ct.certification_name, cr.requirement_order
        ", [$this->user['company_id']]);
    }

    private function getCertificationExams() {
        return $this->db->query("
            SELECT
                ce.*,
                ct.certification_name,
                ce.exam_name,
                ce.total_questions,
                ce.passing_score,
                ce.time_limit_minutes,
                COUNT(ar.id) as total_attempts,
                AVG(ar.score) as avg_score,
                COUNT(CASE WHEN ar.passed = true THEN 1 END) as passed_attempts
            FROM certification_exams ce
            JOIN certification_templates ct ON ce.certification_id = ct.id
            LEFT JOIN assessment_results ar ON ce.id = ar.assessment_id
            WHERE ce.company_id = ?
            GROUP BY ce.id, ct.certification_name
            ORDER BY total_attempts DESC
        ", [$this->user['company_id']]);
    }

    private function getCertificationTracking() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                ct.certification_name,
                cr.requirement_type,
                cr.description,
                ctr.completion_date,
                ctr.score,
                ctr.status,
                ctr.notes
            FROM users u
            JOIN certification_tracking ctr ON u.id = ctr.student_id
            JOIN certification_requirements cr ON ctr.requirement_id = cr.id
            JOIN certification_templates ct ON cr.certification_id = ct.id
            WHERE ctr.company_id = ?
            ORDER BY u.last_name, ct.certification_name, cr.requirement_order
        ", [$this->user['company_id']]);
    }

    private function getCertificationReports() {
        return $this->db->query("
            SELECT
                crp.*,
                crp.report_type,
                crp.report_period,
                crp.generated_date,
                crp.total_certifications,
                crp.completion_rate,
                crp.avg_score
            FROM certification_reports crp
            WHERE crp.company_id = ?
            ORDER BY crp.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCertificationAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT ct.id) as total_certification_types,
                COUNT(sc.id) as total_certifications_awarded,
                COUNT(CASE WHEN sc.status = 'active' THEN 1 END) as active_certifications,
                ROUND((COUNT(CASE WHEN sc.status = 'active' THEN 1 END) / NULLIF(COUNT(sc.id), 0)) * 100, 2) as active_percentage,
                AVG(sc.score) as avg_certification_score,
                COUNT(CASE WHEN sc.expiry_date <= CURDATE() THEN 1 END) as expired_certifications,
                COUNT(CASE WHEN sc.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as expiring_soon
            FROM certification_templates ct
            LEFT JOIN student_certifications sc ON ct.id = sc.certification_id
            WHERE ct.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCertificationSettings() {
        return $this->db->querySingle("
            SELECT * FROM certification_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAssessments() {
        return $this->db->query("
            SELECT
                a.*,
                c.course_name,
                a.assessment_name,
                a.assessment_type,
                a.total_questions,
                a.passing_score,
                a.time_limit_minutes,
                COUNT(ar.id) as total_attempts,
                AVG(ar.score) as avg_score,
                COUNT(CASE WHEN ar.passed = true THEN 1 END) as passed_attempts,
                ROUND((COUNT(CASE WHEN ar.passed = true THEN 1 END) / NULLIF(COUNT(ar.id), 0)) * 100, 2) as pass_rate
            FROM assessments a
            LEFT JOIN courses c ON a.course_id = c.id
            LEFT JOIN assessment_results ar ON a.id = ar.assessment_id
            WHERE a.company_id = ?
            GROUP BY a.id, c.course_name
            ORDER BY total_attempts DESC
        ", [$this->user['company_id']]);
    }

    private function getAssessmentTemplates() {
        return $this->db->query("
            SELECT * FROM assessment_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAssessmentAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(a.id) as total_assessments,
                COUNT(ar.id) as total_attempts,
                AVG(ar.score) as avg_score,
                ROUND((COUNT(CASE WHEN ar.passed = true THEN 1 END) / NULLIF(COUNT(ar.id), 0)) * 100, 2) as overall_pass_rate,
                AVG(ar.completion_time) as avg_completion_time,
                COUNT(CASE WHEN ar.score >= 90 THEN 1 END) as excellent_scores,
                COUNT(CASE WHEN ar.score < 60 THEN 1 END) as failing_scores,
                COUNT(DISTINCT ar.student_id) as students_assessed
            FROM assessments a
            LEFT JOIN assessment_results ar ON a.id = ar.assessment_id
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getQuestionBank() {
        return $this->db->query("
            SELECT
                qb.*,
                qb.question_text,
                qb.question_type,
                qb.difficulty_level,
                qb.category,
                COUNT(qba.id) as times_used,
                AVG(qba.score) as avg_score,
                COUNT(CASE WHEN qba.is_correct = true THEN 1 END) as correct_answers
            FROM question_bank qb
            LEFT JOIN question_bank_answers qba ON qb.id = qba.question_id
            WHERE qb.company_id = ?
            GROUP BY qb.id
            ORDER BY times_used DESC
        ", [$this->user['company_id']]);
    }

    private function getAssessmentReports() {
        return $this->db->query("
            SELECT
                arp.*,
                arp.report_type,
                arp.report_period,
                arp.generated_date,
                arp.total_assessments,
                arp.avg_score,
                arp.pass_rate
            FROM assessment_reports arp
            WHERE arp.company_id = ?
            ORDER BY arp.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAssessmentSettings() {
        return $this->db->querySingle("
            SELECT * FROM assessment_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getGradingSystem() {
        return $this->db->query("
            SELECT * FROM grading_system
            WHERE company_id = ?
            ORDER BY min_score ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceRequirements() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.requirement_name,
                cr.description,
                cr.frequency,
                cr.is_mandatory,
                cr.target_completion_date,
                COUNT(tc.id) as enrolled_users,
                COUNT(CASE WHEN tc.compliance_status = 'compliant' THEN 1 END) as compliant_users,
                ROUND((COUNT(CASE WHEN tc.compliance_status = 'compliant' THEN 1 END) / NULLIF(COUNT(tc.id), 0)) * 100, 2) as compliance_rate
            FROM compliance_requirements cr
            LEFT JOIN training_compliance tc ON cr.id = tc.requirement_id
            WHERE cr.company_id = ?
            GROUP BY cr.id
            ORDER BY cr.target_completion_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceCourses() {
        return $this->db->query("
            SELECT
                c.*,
                cr.requirement_name,
                c.course_name,
                c.enrollment_count,
                c.completion_rate,
                c.student_rating,
                COUNT(tc.id) as compliance_enrollments,
                COUNT(CASE WHEN tc.compliance_status = 'compliant' THEN 1 END) as compliant_completions
            FROM courses c
            JOIN compliance_requirements cr ON c.id = cr.course_id
            LEFT JOIN training_compliance tc ON cr.id = tc.requirement_id
            WHERE c.company_id = ?
            GROUP BY c.id, cr.requirement_name
            ORDER BY c.enrollment_count DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceTracking() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                cr.requirement_name,
                tc.compliance_status,
                tc.enrollment_date,
                tc.completion_date,
                tc.next_training_date,
                tc.compliance_score,
                tc.last_updated,
                TIMESTAMPDIFF(DAY, CURDATE(), tc.next_training_date) as days_until_next
            FROM users u
            JOIN training_compliance tc ON u.id = tc.user_id
            JOIN compliance_requirements cr ON tc.requirement_id = cr.id
            WHERE tc.company_id = ?
            ORDER BY tc.next_training_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceReports() {
        return $this->db->query("
            SELECT
                crp.*,
                crp.report_type,
                crp.report_period,
                crp.generated_date,
                crp.compliance_rate,
                crp.non_compliant_users,
                crp.upcoming_deadlines
            FROM compliance_reports crp
            WHERE crp.company_id = ?
            ORDER BY crp.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceAlerts() {
        return $this->db->query("
            SELECT
                ca.*,
                ca.alert_type,
                ca.severity,
                ca.message,
                ca.user_id,
                ca.requirement_id,
                ca.created_at,
                TIMESTAMPDIFF(MINUTE, ca.created_at, NOW()) as minutes_since_alert
            FROM compliance_alerts ca
            WHERE ca.company_id = ? AND ca.status = 'active'
            ORDER BY ca.severity DESC, ca.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT cr.id) as total_requirements,
                COUNT(DISTINCT u.id) as total_users,
                COUNT(tc.id) as total_compliance_records,
                ROUND((COUNT(CASE WHEN tc.compliance_status = 'compliant' THEN 1 END) / NULLIF(COUNT(tc.id), 0)) * 100, 2) as overall_compliance_rate,
                COUNT(CASE WHEN tc.next_training_date <= CURDATE() THEN 1 END) as overdue_training,
                COUNT(CASE WHEN tc.compliance_score < 70 THEN 1 END) as low_compliance_scores,
                AVG(tc.compliance_score) as avg_compliance_score
            FROM compliance_requirements cr
            CROSS JOIN users u
            LEFT JOIN training_compliance tc ON u.id = tc.user_id AND cr.id = tc.requirement_id
            WHERE cr.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getComplianceTemplates() {
        return $this->db->query("
            SELECT * FROM compliance_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceSettings() {
        return $this->db->querySingle("
            SELECT * FROM compliance_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getLearningMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT c.id) as total_courses,
                COUNT(DISTINCT e.student_id) as total_students,
                COUNT(e.id) as total_enrollments,
                ROUND((COUNT(CASE WHEN e.status = 'completed' THEN 1 END) / NULLIF(COUNT(e.id), 0)) * 100, 2) as completion_rate,
                AVG(e.progress_percentage) as avg_progress,
                AVG(c.student_rating) as avg_course_rating,
                SUM(c.total_learning_hours) as total_learning_hours,
                COUNT(CASE WHEN e.last_activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as active_students_week
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getStudentEngagement() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(e.last_activity_date, '%Y-%m-%d') as date,
                COUNT(DISTINCT e.student_id) as active_students,
                COUNT(e.id) as total_activities,
                AVG(e.progress_percentage) as avg_progress,
                COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completions
            FROM enrollments e
            WHERE e.company_id = ? AND e.last_activity_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE_FORMAT(e.last_activity_date, '%Y-%m-%d')
            ORDER BY date DESC
        ", [$this->user['company_id']]);
    }

    private function getCourseEffectiveness() {
        return $this->db->query("
            SELECT
                c.course_name,
                c.enrollment_count,
                c.completion_rate,
                c.student_rating,
                AVG(ar.score) as avg_assessment_score,
                COUNT(ar.id) as total_assessments,
                ROUND((COUNT(CASE WHEN ar.passed = true THEN 1 END) / NULLIF(COUNT(ar.id), 0)) * 100, 2) as assessment_pass_rate,
                AVG(e.progress_percentage) as avg_progress
            FROM courses c
            LEFT JOIN assessments a ON c.id = a.course_id
            LEFT JOIN assessment_results ar ON a.id = ar.assessment_id
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE c.company_id = ?
            GROUP BY c.id, c.course_name, c.enrollment_count, c.completion_rate, c.student_rating
            ORDER BY c.completion_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getLearningTrends() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(e.enrollment_date, '%Y-%m') as month,
                COUNT(e.id) as new_enrollments,
                COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completions,
                AVG(e.progress_percentage) as avg_progress,
                AVG(c.student_rating) as avg_rating
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.company_id = ? AND e.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(e.enrollment_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceInsights() {
        return $this->db->query("
            SELECT
                'Top Performing Courses' as insight_type,
                c.course_name as insight_value,
                c.completion_rate as metric_value,
                CONCAT('Completion rate: ', ROUND(c.completion_rate, 1), '%') as description
            FROM courses c
            WHERE c.company_id = ?
            ORDER BY c.completion_rate DESC
            LIMIT 5
            UNION ALL
            SELECT
                'Most Popular Courses' as insight_type,
                c.course_name as insight_value,
                c.enrollment_count as metric_value,
                CONCAT('Enrollments: ', c.enrollment_count) as description
            FROM courses c
            WHERE c.company_id = ?
            ORDER BY c.enrollment_count DESC
            LIMIT 5
            UNION ALL
            SELECT
                'High Rated Courses' as insight_type,
                c.course_name as insight_value,
                c.student_rating as metric_value,
                CONCAT('Rating: ', ROUND(c.student_rating, 1), '/5') as description
            FROM courses c
            WHERE c.company_id = ?
            ORDER BY c.student_rating DESC
            LIMIT 5
        ", [$this->user['company_id'], $this->user['company_id'], $this->user['company_id']]);
    }

    private function getPredictiveAnalytics() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                e.progress_percentage,
                e.last_activity_date,
                TIMESTAMPDIFF(DAY, e.last_activity_date, CURDATE()) as days_since_activity,
                CASE
                    WHEN e.progress_percentage < 25 AND TIMESTAMPDIFF(DAY, e.last_activity_date, CURDATE()) > 14 THEN 'at_risk'
                    WHEN e.progress_percentage >= 25 AND e.progress_percentage < 50 AND TIMESTAMPDIFF(DAY, e.last_activity_date, CURDATE()) > 7 THEN 'needs_attention'
                    WHEN e.progress_percentage >= 75 THEN 'on_track'
                    ELSE 'normal'
                END as risk_level,
                CASE
                    WHEN e.due_date <= CURDATE() THEN 'overdue'
                    WHEN e.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'due_soon'
                    ELSE 'on_track'
                END as deadline_status
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
            WHERE e.company_id = ? AND e.status = 'active'
            ORDER BY
                CASE
                    WHEN e.progress_percentage < 25 AND TIMESTAMPDIFF(DAY, e.last_activity_date, CURDATE()) > 14 THEN 1
                    WHEN e.progress_percentage >= 25 AND e.progress_percentage < 50 AND TIMESTAMPDIFF(DAY, e.last_activity_date, CURDATE()) > 7 THEN 2
                    ELSE 3
                END,
                e.progress_percentage ASC
        ", [$this->user['company_id']]);
    }

    private function getBenchmarking() {
        return $this->db->querySingle("
            SELECT
                AVG(c.completion_rate) as avg_completion_rate,
                AVG(c.student_rating) as avg_student_rating,
                AVG(c.enrollment_count) as avg_enrollment_count,
                COUNT(CASE WHEN c.completion_rate >= 80 THEN 1 END) as high_completion_courses,
                COUNT(CASE WHEN c.student_rating >= 4.5 THEN 1 END) as high_rated_courses,
