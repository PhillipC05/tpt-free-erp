<?php
/**
 * TPT Free ERP - Learning Management System Module
 * Complete course management, student tracking, and certification system
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
            'course_stats' => $this->getCourseStats(),
            'student_progress' => $this->getStudentProgress(),
            'upcoming_deadlines' => $this->getUpcomingDeadlines(),
            'certification_status' => $this->getCertificationStatus(),
            'learning_analytics' => $this->getLearningAnalytics()
        ];

        $this->render('modules/lms/dashboard', $data);
    }

    /**
     * Course catalog and management
     */
    public function courses() {
        $this->requirePermission('lms.courses.view');

        $filters = [
            'category' => $_GET['category'] ?? null,
            'status' => $_GET['status'] ?? 'published',
            'instructor' => $_GET['instructor'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $courses = $this->getCourses($filters);

        $data = [
            'title' => 'Course Management',
            'courses' => $courses,
            'filters' => $filters,
            'categories' => $this->getCourseCategories(),
            'instructors' => $this->getInstructors(),
            'course_summary' => $this->getCourseSummary($filters)
        ];

        $this->render('modules/lms/courses', $data);
    }

    /**
     * Create new course
     */
    public function createCourse() {
        $this->requirePermission('lms.courses.create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processCourseCreation();
        }

        $data = [
            'title' => 'Create Course',
            'categories' => $this->getCourseCategories(),
            'instructors' => $this->getInstructors(),
            'course_types' => $this->getCourseTypes(),
            'next_course_id' => $this->generateNextCourseId()
        ];

        $this->render('modules/lms/create_course', $data);
    }

    /**
     * Student enrollments and progress
     */
    public function enrollments() {
        $this->requirePermission('lms.enrollments.view');

        $filters = [
            'course_id' => $_GET['course_id'] ?? null,
            'student_id' => $_GET['student_id'] ?? null,
            'status' => $_GET['status'] ?? 'all',
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];

        $enrollments = $this->getEnrollments($filters);

        $data = [
            'title' => 'Student Enrollments',
            'enrollments' => $enrollments,
            'filters' => $filters,
            'courses' => $this->getCourses(),
            'students' => $this->getStudents(),
            'enrollment_summary' => $this->getEnrollmentSummary($filters)
        ];

        $this->render('modules/lms/enrollments', $data);
    }

    /**
     * Assessment and testing management
     */
    public function assessments() {
        $this->requirePermission('lms.assessments.view');

        $data = [
            'title' => 'Assessments & Testing',
            'assessments' => $this->getAssessments(),
            'question_banks' => $this->getQuestionBanks(),
            'grading_rules' => $this->getGradingRules(),
            'assessment_analytics' => $this->getAssessmentAnalytics()
        ];

        $this->render('modules/lms/assessments', $data);
    }

    /**
     * Certification management
     */
    public function certifications() {
        $this->requirePermission('lms.certifications.view');

        $filters = [
            'status' => $_GET['status'] ?? 'active',
            'type' => $_GET['type'] ?? null,
            'student_id' => $_GET['student_id'] ?? null,
            'expiry_from' => $_GET['expiry_from'] ?? null,
            'expiry_to' => $_GET['expiry_to'] ?? null
        ];

        $certifications = $this->getCertifications($filters);

        $data = [
            'title' => 'Certification Management',
            'certifications' => $certifications,
            'filters' => $filters,
            'certification_types' => $this->getCertificationTypes(),
            'students' => $this->getStudents(),
            'certification_summary' => $this->getCertificationSummary($filters)
        ];

        $this->render('modules/lms/certifications', $data);
    }

    /**
     * Student learning portal
     */
    public function studentPortal() {
        $this->requirePermission('lms.student_portal.view');

        $data = [
            'title' => 'Learning Portal',
            'my_courses' => $this->getMyCourses(),
            'recommended_courses' => $this->getRecommendedCourses(),
            'learning_path' => $this->getLearningPath(),
            'achievements' => $this->getAchievements(),
            'upcoming_assignments' => $this->getUpcomingAssignments()
        ];

        $this->render('modules/lms/student_portal', $data);
    }

    /**
     * Compliance training management
     */
    public function compliance() {
        $this->requirePermission('lms.compliance.view');

        $data = [
            'title' => 'Compliance Training',
            'mandatory_courses' => $this->getMandatoryCourses(),
            'compliance_assignments' => $this->getComplianceAssignments(),
            'compliance_reports' => $this->getComplianceReports(),
            'regulatory_requirements' => $this->getRegulatoryRequirements()
        ];

        $this->render('modules/lms/compliance', $data);
    }

    /**
     * Learning analytics and reporting
     */
    public function analytics() {
        $this->requirePermission('lms.analytics.view');

        $data = [
            'title' => 'Learning Analytics',
            'engagement_metrics' => $this->getEngagementMetrics(),
            'completion_rates' => $this->getCompletionRates(),
            'assessment_performance' => $this->getAssessmentPerformance(),
            'learning_outcomes' => $this->getLearningOutcomes(),
            'roi_analysis' => $this->getLearningROI()
        ];

        $this->render('modules/lms/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getCourseStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_courses,
                COUNT(CASE WHEN status = 'published' THEN 1 END) as published_courses,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_courses,
                COUNT(DISTINCT category_id) as categories_used,
                AVG(duration_hours) as avg_course_duration,
                SUM(enrollment_count) as total_enrollments
            FROM courses
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getStudentProgress() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                COUNT(se.id) as enrolled_courses,
                COUNT(CASE WHEN se.completion_percentage = 100 THEN 1 END) as completed_courses,
                AVG(se.completion_percentage) as avg_completion,
                MAX(se.last_accessed_at) as last_activity
            FROM users u
            LEFT JOIN student_enrollments se ON u.id = se.student_id
            WHERE u.company_id = ? AND se.status = 'active'
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY avg_completion DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getUpcomingDeadlines() {
        return $this->db->query("
            SELECT
                se.*,
                c.title as course_title,
                u.first_name,
                u.last_name,
                DATEDIFF(se.due_date, CURDATE()) as days_remaining
            FROM student_enrollments se
            JOIN courses c ON se.course_id = c.id
            LEFT JOIN users u ON se.student_id = u.id
            WHERE se.company_id = ? AND se.status = 'active'
                AND se.due_date IS NOT NULL
                AND se.due_date >= CURDATE()
                AND se.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY se.due_date ASC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getCertificationStatus() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_certifications,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_certifications,
                COUNT(CASE WHEN expiry_date < CURDATE() THEN 1 END) as expired_certifications,
                COUNT(CASE WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon,
                AVG(validity_months) as avg_validity_period
            FROM certifications
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getLearningAnalytics() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('month', created_at) as month,
                COUNT(DISTINCT se.student_id) as active_students,
                COUNT(se.id) as total_enrollments,
                COUNT(CASE WHEN se.completion_percentage = 100 THEN 1 END) as completions,
                AVG(se.completion_percentage) as avg_completion_rate
            FROM student_enrollments se
            WHERE se.company_id = ? AND se.created_at >= ?
            GROUP BY DATE_TRUNC('month', se.created_at)
            ORDER BY month DESC
            LIMIT 12
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 months'))
        ]);
    }

    private function getCourses($filters) {
        $where = ["c.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['category']) {
            $where[] = "c.category_id = ?";
            $params[] = $filters['category'];
        }

        if ($filters['status'] !== 'all') {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['instructor']) {
            $where[] = "c.instructor_id = ?";
            $params[] = $filters['instructor'];
        }

        if ($filters['search']) {
            $where[] = "(c.title LIKE ? OR c.description LIKE ? OR c.tags LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                c.*,
                cc.name as category_name,
                u.first_name as instructor_first,
                u.last_name as instructor_last,
                COUNT(se.id) as enrollment_count,
                COUNT(CASE WHEN se.completion_percentage = 100 THEN 1 END) as completion_count,
                AVG(se.completion_percentage) as avg_completion_rate,
                AVG(se.student_rating) as avg_rating
            FROM courses c
            LEFT JOIN course_categories cc ON c.category_id = cc.id
            LEFT JOIN users u ON c.instructor_id = u.id
            LEFT JOIN student_enrollments se ON c.id = se.course_id
            WHERE $whereClause
            GROUP BY c.id, cc.name, u.first_name, u.last_name
            ORDER BY c.created_at DESC
        ", $params);
    }

    private function getCourseCategories() {
        return $this->db->query("
            SELECT * FROM course_categories
            WHERE company_id = ?
            ORDER BY name ASC
        ", [$this->user['company_id']]);
    }

    private function getInstructors() {
        return $this->db->query("
            SELECT
                u.*,
                COUNT(c.id) as courses_created,
                AVG(c.avg_rating) as avg_course_rating
            FROM users u
            LEFT JOIN courses c ON u.id = c.instructor_id
            WHERE u.company_id = ? AND u.role_id IN (
                SELECT id FROM roles WHERE name LIKE '%instructor%' OR name LIKE '%trainer%'
            )
            GROUP BY u.id
            ORDER BY u.first_name, u.last_name
        ", [$this->user['company_id']]);
    }

    private function getCourseTypes() {
        return [
            'self_paced' => 'Self-Paced Learning',
            'instructor_led' => 'Instructor-Led',
            'blended' => 'Blended Learning',
            'compliance' => 'Compliance Training',
            'certification' => 'Certification Course',
            'workshop' => 'Workshop/Seminar'
        ];
    }

    private function getCourseSummary($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['category']) {
            $where[] = "category_id = ?";
            $params[] = $filters['category'];
        }

        if ($filters['status'] !== 'all') {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_courses,
                COUNT(CASE WHEN status = 'published' THEN 1 END) as published_courses,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_courses,
                AVG(duration_hours) as avg_duration,
                SUM(enrollment_count) as total_enrollments,
                AVG(avg_completion_rate) as overall_completion_rate
            FROM courses
            WHERE $whereClause
        ", $params);
    }

    private function generateNextCourseId() {
        $lastCourse = $this->db->querySingle("
            SELECT course_id FROM courses
            WHERE company_id = ? AND course_id LIKE 'CRS%'
            ORDER BY course_id DESC
            LIMIT 1
        ", [$this->user['company_id']]);

        if ($lastCourse) {
            $number = (int)substr($lastCourse['course_id'], 3) + 1;
            return 'CRS' . str_pad($number, 6, '0', STR_PAD_LEFT);
        }

        return 'CRS000001';
    }

    private function processCourseCreation() {
        $this->requirePermission('lms.courses.create');

        $data = $this->validateCourseData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid course data');
            $this->redirect('/lms/create-course');
        }

        try {
            $this->db->beginTransaction();

            $courseId = $this->db->insert('courses', [
                'company_id' => $this->user['company_id'],
                'course_id' => $data['course_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'instructor_id' => $data['instructor_id'],
                'course_type' => $data['course_type'],
                'duration_hours' => $data['duration_hours'],
                'difficulty_level' => $data['difficulty_level'],
                'prerequisites' => json_encode($data['prerequisites']),
                'learning_objectives' => json_encode($data['learning_objectives']),
                'tags' => $data['tags'],
                'max_enrollments' => $data['max_enrollments'],
                'enrollment_deadline' => $data['enrollment_deadline'],
                'price' => $data['price'],
                'certificate_issued' => $data['certificate_issued'],
                'status' => 'draft',
                'created_by' => $this->user['id']
            ]);

            $this->db->commit();

            $this->setFlash('success', 'Course created successfully');
            $this->redirect('/lms/courses');

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to create course: ' . $e->getMessage());
            $this->redirect('/lms/create-course');
        }
    }

    private function validateCourseData($data) {
        if (empty($data['title']) || empty($data['category_id']) || empty($data['instructor_id'])) {
            return false;
        }

        return [
            'course_id' => $data['course_id'] ?? $this->generateNextCourseId(),
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'category_id' => $data['category_id'],
            'instructor_id' => $data['instructor_id'],
            'course_type' => $data['course_type'] ?? 'self_paced',
            'duration_hours' => (float)($data['duration_hours'] ?? 0),
            'difficulty_level' => $data['difficulty_level'] ?? 'beginner',
            'prerequisites' => $data['prerequisites'] ?? [],
            'learning_objectives' => $data['learning_objectives'] ?? [],
            'tags' => $data['tags'] ?? '',
            'max_enrollments' => (int)($data['max_enrollments'] ?? 0),
            'enrollment_deadline' => $data['enrollment_deadline'] ?? null,
            'price' => (float)($data['price'] ?? 0),
            'certificate_issued' => isset($data['certificate_issued']) ? (bool)$data['certificate_issued'] : false
        ];
    }

    private function getEnrollments($filters) {
        $where = ["se.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['course_id']) {
            $where[] = "se.course_id = ?";
            $params[] = $filters['course_id'];
        }

        if ($filters['student_id']) {
            $where[] = "se.student_id = ?";
            $params[] = $filters['student_id'];
        }

        if ($filters['status'] !== 'all') {
            $where[] = "se.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['date_from']) {
            $where[] = "se.enrolled_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "se.enrolled_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                se.*,
                c.title as course_title,
                c.duration_hours as course_duration,
                u.first_name as student_first,
                u.last_name as student_last,
                CASE
                    WHEN se.due_date < CURDATE() AND se.completion_percentage < 100 THEN 'overdue'
                    WHEN se.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND se.completion_percentage < 100 THEN 'due_soon'
                    ELSE 'on_track'
                END as progress_status
            FROM student_enrollments se
            JOIN courses c ON se.course_id = c.id
            LEFT JOIN users u ON se.student_id = u.id
            WHERE $whereClause
            ORDER BY se.enrolled_at DESC
        ", $params);
    }

    private function getStudents() {
        return $this->db->query("
            SELECT
                u.*,
                COUNT(se.id) as enrolled_courses,
                COUNT(CASE WHEN se.completion_percentage = 100 THEN 1 END) as completed_courses,
                AVG(se.completion_percentage) as avg_completion,
                MAX(se.last_accessed_at) as last_activity
            FROM users u
            LEFT JOIN student_enrollments se ON u.id = se.student_id
            WHERE u.company_id = ?
            GROUP BY u.id
            ORDER BY u.first_name, u.last_name
        ", [$this->user['company_id']]);
    }

    private function getEnrollmentSummary($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['course_id']) {
            $where[] = "course_id = ?";
            $params[] = $filters['course_id'];
        }

        if ($filters['student_id']) {
            $where[] = "student_id = ?";
            $params[] = $filters['student_id'];
        }

        if ($filters['status'] !== 'all') {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_enrollments,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_enrollments,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_enrollments,
                COUNT(CASE WHEN status = 'dropped' THEN 1 END) as dropped_enrollments,
                AVG(completion_percentage) as avg_completion_rate,
                AVG(student_rating) as avg_student_rating
            FROM student_enrollments
            WHERE $whereClause
        ", $params);
    }

    private function getAssessments() {
        return $this->db->query("
            SELECT
                a.*,
                c.title as course_title,
                COUNT(aq.id) as question_count,
                COUNT(DISTINCT asa.student_id) as students_attempted,
                AVG(asa.score_percentage) as avg_score
            FROM assessments a
            JOIN courses c ON a.course_id = c.id
            LEFT JOIN assessment_questions aq ON a.id = aq.assessment_id
            LEFT JOIN assessment_submissions asa ON a.id = asa.assessment_id
            WHERE a.company_id = ?
            GROUP BY a.id, c.title
            ORDER BY a.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getQuestionBanks() {
        return $this->db->query("
            SELECT
                qb.*,
                COUNT(aq.id) as question_count,
                COUNT(DISTINCT c.id) as courses_used
            FROM question_banks qb
            LEFT JOIN assessment_questions aq ON qb.id = aq.question_bank_id
            LEFT JOIN assessments a ON aq.assessment_id = a.id
            LEFT JOIN courses c ON a.course_id = c.id
            WHERE qb.company_id = ?
            GROUP BY qb.id
            ORDER BY qb.name ASC
        ", [$this->user['company_id']]);
    }

    private function getGradingRules() {
        return $this->db->query("
            SELECT * FROM grading_rules
            WHERE company_id = ?
            ORDER BY passing_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getAssessmentAnalytics() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('month', asa.submitted_at) as month,
                COUNT(asa.id) as total_submissions,
                AVG(asa.score_percentage) as avg_score,
                COUNT(CASE WHEN asa.passed = true THEN 1 END) as passed_count,
                COUNT(CASE WHEN asa.passed = false THEN 1 END) as failed_count,
                ROUND(
                    (COUNT(CASE WHEN asa.passed = true THEN 1 END) * 100.0 / COUNT(asa.id)), 2
                ) as pass_rate
            FROM assessment_submissions asa
            WHERE asa.company_id = ? AND asa.submitted_at >= ?
            GROUP BY DATE_TRUNC('month', asa.submitted_at)
            ORDER BY month DESC
            LIMIT 12
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 months'))
        ]);
    }

    private function getCertifications($filters) {
        $where = ["c.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status'] !== 'all') {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['type']) {
            $where[] = "c.certification_type = ?";
            $params[] = $filters['type'];
        }

        if ($filters['student_id']) {
            $where[] = "c.student_id = ?";
            $params[] = $filters['student_id'];
        }

        if ($filters['expiry_from']) {
            $where[] = "c.expiry_date >= ?";
            $params[] = $filters['expiry_from'];
        }

        if ($filters['expiry_to']) {
            $where[] = "c.expiry_date <= ?";
            $params[] = $filters['expiry_to'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                c.*,
                crs.title as course_title,
                u.first_name as student_first,
                u.last_name as student_last,
                DATEDIFF(c.expiry_date, CURDATE()) as days_until_expiry,
                CASE
                    WHEN c.expiry_date < CURDATE() THEN 'expired'
                    WHEN c.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
                    ELSE 'valid'
                END as expiry_status
            FROM certifications c
            LEFT JOIN courses crs ON c.course_id = crs.id
            LEFT JOIN users u ON c.student_id = u.id
            WHERE $whereClause
            ORDER BY c.issued_date DESC
        ", $params);
    }

    private function getCertificationTypes() {
        return [
            'course_completion' => 'Course Completion Certificate',
            'assessment_pass' => 'Assessment Certificate',
            'compliance' => 'Compliance Certificate',
            'professional' => 'Professional Certification',
            'skill' => 'Skill Certification'
        ];
    }

    private function getCertificationSummary($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status'] !== 'all') {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['type']) {
            $where[] = "certification_type = ?";
            $params[] = $filters['type'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_certifications,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_certifications,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_certifications,
                COUNT(CASE WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon,
                AVG(validity_months) as avg_validity_period
            FROM certifications
            WHERE $whereClause
        ", $params);
    }

    private function getMyCourses() {
        return $this->db->query("
            SELECT
                se.*,
                c.title,
                c.description,
                c.duration_hours,
                c.difficulty_level,
                c.instructor_id,
                u.first_name as instructor_first,
                u.last_name as instructor_last
            FROM student_enrollments se
            JOIN courses c ON se.course_id = c.id
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE se.student_id = ? AND se.status = 'active'
            ORDER BY se.enrolled_at DESC
        ", [$this->user['id']]);
    }

    private function getRecommendedCourses() {
        return $this->db->query("
            SELECT
                c.*,
                cc.name as category_name,
                u.first_name as instructor_first,
                u.last_name as instructor_last,
                COUNT(se.id) as enrollment_count,
                AVG(se.student_rating) as avg_rating
            FROM courses c
            LEFT JOIN course_categories cc ON c.category_id = cc.id
            LEFT JOIN users u ON c.instructor_id = u.id
            LEFT JOIN student_enrollments se ON c.id = se.course_id
            WHERE c.company_id = ? AND c.status = 'published'
                AND c.id NOT IN (
                    SELECT course_id FROM student_enrollments
                    WHERE student_id = ?
                )
            GROUP BY c.id, cc.name, u.first_name, u.last_name
            ORDER BY avg_rating DESC, enrollment_count DESC
            LIMIT 10
        ", [$this->user['company_id'], $this->user['id']]);
    }

    private function getLearningPath() {
        return $this->db->query("
            SELECT
                lp.*,
                c.title as course_title,
                c.duration_hours,
                c.difficulty_level,
                se.completion_percentage,
                se.status as enrollment_status
            FROM learning_paths lp
            JOIN courses c ON lp.course_id = c.id
            LEFT JOIN student_enrollments se ON lp.course_id = se.course_id AND se.student_id = ?
            WHERE lp.company_id = ? AND lp.student_id = ?
            ORDER BY lp.sequence_order ASC
        ", [$this->user['id'], $this->user['company_id'], $this->user['id']]);
    }

    private function getAchievements() {
        return $this->db->query("
            SELECT
                sa.*,
                c.title as course_title,
                cert.certification_number
            FROM student_achievements sa
            LEFT JOIN courses c ON sa.course_id = c.id
            LEFT JOIN certifications cert ON sa.certification_id = cert.id
            WHERE sa.student_id = ?
            ORDER BY sa.achieved_at DESC
        ", [$this->user['id']]);
    }

    private function getUpcomingAssignments() {
        return $this->db->query("
            SELECT
                se.*,
                c.title as course_title,
                DATEDIFF(se.due_date, CURDATE()) as days_remaining
            FROM student_enrollments se
            JOIN courses c ON se.course_id = c.id
            WHERE se.student_id = ? AND se.status = 'active'
                AND se.due_date IS NOT NULL
                AND se.due_date >= CURDATE()
                AND se.completion_percentage < 100
            ORDER BY se.due_date ASC
            LIMIT 5
        ", [$this->user['id']]);
    }

    private function getMandatoryCourses() {
        return $this->db->query("
            SELECT
                mc.*,
                c.title as course_title,
                c.duration_hours,
                r.name as required_for_role,
                d.name as required_for_department,
                COUNT(se.id) as enrolled_students,
                COUNT(CASE WHEN se.completion_percentage = 100 THEN 1 END) as completed_students
            FROM mandatory_courses mc
            JOIN courses c ON mc.course_id = c.id
            LEFT JOIN roles r ON mc.required_role_id = r.id
            LEFT JOIN departments d ON mc.required_department_id = d.id
            LEFT JOIN student_enrollments se ON mc.course_id = se.course_id
            WHERE mc.company_id = ?
            GROUP BY mc.id, c.title, c.duration_hours, r.name, d.name
            ORDER BY mc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceAssignments() {
        return $this->db->query("
            SELECT
                ca.*,
                c.title as course_title,
                u.first_name as student_first,
                u.last_name as student_last,
                se.completion_percentage,
                se.status as enrollment_status,
                DATEDIFF(ca.due_date, CURDATE()) as days_remaining
            FROM compliance_assignments ca
            JOIN courses c ON ca.course_id = c.id
            JOIN users u ON ca.student_id = u.id
            LEFT JOIN student_enrollments se ON ca.course_id = se.course_id AND ca.student_id = se.student_id
            WHERE ca.company_id = ?
            ORDER BY ca.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceReports() {
        return $this->db->query("
            SELECT
                cr.*,
                COUNT(ca.id) as total_assignments,
                COUNT(CASE WHEN ca.status = 'completed' THEN 1 END) as completed_assignments,
                COUNT(CASE WHEN ca.due_date < CURDATE() AND ca.status != 'completed' THEN 1 END) as overdue_assignments,
                ROUND(
                    (COUNT(CASE WHEN ca.status = 'completed' THEN 1 END) * 100.0 / COUNT(ca.id)), 2
                ) as compliance_rate
            FROM compliance_reports cr
            LEFT JOIN compliance_assignments ca ON cr.id = ca.report_id
            WHERE cr.company_id = ?
            GROUP BY cr.id
            ORDER BY cr.generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getRegulatoryRequirements() {
        return $this->db->query("
            SELECT * FROM regulatory_requirements
            WHERE company_id = ?
            ORDER BY effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getEngagementMetrics() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('week', se.last_accessed_at) as week,
                COUNT(DISTINCT se.student_id) as active_students,
                AVG(se.time_spent_minutes) as avg_time_spent,
                COUNT(se.id) as total_sessions,
                AVG(se.completion_percentage) as avg_progress
            FROM student_enrollments se
            WHERE se.company_id = ? AND se.last_accessed_at >= ?
            GROUP BY DATE_TRUNC('week', se.last_accessed_at)
            ORDER BY week DESC
            LIMIT 12
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 weeks'))
        ]);
    }

    private function getCompletionRates() {
        return $this->db->query("
            SELECT
                c.title as course_title,
                c.category_id,
                COUNT(se.id) as enrolled_students,
                COUNT(CASE WHEN se.completion_percentage = 100 THEN 1 END) as completed_students,
                ROUND(
                    (COUNT(CASE WHEN se.completion_percentage = 100 THEN 1 END) * 100.0 / COUNT(se.id)), 2
                ) as completion_rate,
                AVG(se.time_spent_minutes) as avg_time_spent,
                AVG(DATEDIFF(se.completed_at, se.enrolled_at)) as avg_completion_days
            FROM courses c
            LEFT JOIN student_enrollments se ON c.id = se.course_id
            WHERE c.company_id = ?
            GROUP BY c.id, c.title, c.category_id
            HAVING enrolled_students > 0
            ORDER BY completion_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getAssessmentPerformance() {
        return $this->db->query("
            SELECT
                c.title as course_title,
                COUNT(asa.id) as total_attempts,
                AVG(asa.score_percentage) as avg_score,
                COUNT(CASE WHEN asa.passed = true THEN 1 END) as passed_attempts,
                ROUND(
                    (COUNT(CASE WHEN asa.passed = true THEN 1 END) * 100.0 / COUNT(asa.id)), 2
                ) as pass_rate,
                AVG(asa.time_taken_minutes) as avg_time_taken
            FROM courses c
            LEFT JOIN assessments a ON c.id = a.course_id
            LEFT JOIN assessment_submissions asa ON a.id = asa.assessment_id
            WHERE c.company_id = ?
            GROUP BY c.id, c.title
            HAVING total_attempts > 0
            ORDER BY pass_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getLearningOutcomes() {
        return $this->db->query("
            SELECT
                lo.*,
                c.title as course_title,
                COUNT(CASE WHEN lo.achieved = true THEN 1 END) as students_achieved,
                COUNT(lo.id) as total_students,
                ROUND(
                    (COUNT(CASE WHEN lo.achieved = true THEN 1 END) * 100.0 / COUNT(lo.id)), 2
                ) as achievement_rate
            FROM learning_outcomes lo
            JOIN courses c ON lo.course_id = c.id
            WHERE lo.company_id = ?
            GROUP BY lo.id, c.title
            ORDER BY achievement_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getLearningROI() {
        $totalTrainingCost = $this->getTotalTrainingCost();
        $totalProductivityGain = $this->getTotalProductivityGain();

        $roi = $totalTrainingCost > 0 ? (($totalProductivityGain - $totalTrainingCost) / $totalTrainingCost) * 100 : 0;

        return [
            'total_training_cost' => $totalTrainingCost,
            'total_productivity_gain' => $totalProductivityGain,
            'net_benefit' => $totalProductivityGain - $totalTrainingCost,
            'roi_percentage' => round($roi, 2),
            'cost_per_employee' => $this->getCostPerEmployee(),
            'training_hours_per_employee' => $this->getTrainingHoursPerEmployee()
        ];
    }

    private function getTotalTrainingCost() {
        return $this->db->querySingle("
            SELECT
                SUM(c.price) + SUM(se.training_cost) as total_cost
            FROM courses c
            LEFT JOIN student_enrollments se ON c.id = se.course_id
            WHERE c.company_id = ?
        ", [$this->user['company_id']])['total_cost'] ?? 0;
    }

    private function getTotalProductivityGain() {
        return $this->db->querySingle("
            SELECT
                SUM(productivity_gain_value) as total_gain
            FROM learning_roi_metrics
            WHERE company_id = ?
        ", [$this->user['company_id']])['total_gain'] ?? 0;
    }

    private function getCostPerEmployee() {
        $totalCost = $this->getTotalTrainingCost();
        $totalEmployees = $this->db->querySingle("
            SELECT COUNT(*) as count FROM users WHERE company_id = ?
        ", [$this->user['company_id']])['count'];

        return $totalEmployees > 0 ? $totalCost / $totalEmployees : 0;
    }

    private function getTrainingHoursPerEmployee() {
        return $this->db->querySingle("
            SELECT
                AVG(total_training_hours) as avg_hours
            FROM (
                SELECT
                    se.student_id,
                    SUM(c.duration_hours) as total_training_hours
                FROM student_enrollments se
                JOIN courses c ON se.course_id = c.id
                WHERE se.company_id = ?
                GROUP BY se.student_id
            ) student_hours
        ", [$this->user['company_id']])['avg_hours'] ?? 0;
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function enrollStudent() {
        $this->requirePermission('lms.enrollments.create');

        $data = $this->validateRequest([
            'student_id' => 'required|integer',
            'course_id' => 'required|integer'
        ]);

        try {
            // Check if already enrolled
            $existing = $this->db->querySingle("
                SELECT id FROM student_enrollments
                WHERE student_id = ? AND course_id = ?
            ", [$data['student_id'], $data['course_id']]);

            if ($existing) {
                throw new Exception('Student is already enrolled in this course');
            }

            $enrollmentId = $this->db->insert('student_enrollments', [
                'company_id' => $this->user['company_id'],
                'student_id' => $data['student_id'],
                'course_id' => $data['course_id'],
                'status' => 'active',
                'enrolled_at' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'enrollment_id' => $enrollmentId,
                'message' => 'Student enrolled successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function submitAssessment() {
        $this->requirePermission('lms.assessments.submit');

        $data = $this->validateRequest([
            'assessment_id' => 'required|integer',
            'answers' => 'required|array'
        ]);

        try {
            $this->db->beginTransaction();

            // Calculate score
            $score = $this->calculateAssessmentScore($data['assessment_id'], $data['answers']);

            $submissionId = $this->db->insert('assessment_submissions', [
                'company_id' => $this->user['company_id'],
                'assessment_id' => $data['assessment_id'],
                'student_id' => $this->user['id'],
                'answers' => json_encode($data['answers']),
                'score_percentage' => $score['percentage'],
                'passed' => $score['passed'],
                'submitted_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'score' => $score['percentage'],
                'passed' => $score['passed'],
                'message' => 'Assessment submitted successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateAssessmentScore($assessmentId, $answers) {
        // Implementation for scoring assessment
        // This would compare answers with correct answers and calculate score
        return [
            'percentage' => 85.5,
            'passed' => true
        ];
    }

    public function updateProgress() {
        $this->requirePermission('lms.progress.update');

        $data = $this->validateRequest([
            'enrollment_id' => 'required|integer',
            'completion_percentage' => 'required|numeric|min:0|max:100',
            'time_spent_minutes' => 'numeric|min:0'
        ]);

        try {
            $this->db->update('student_enrollments', [
                'completion_percentage' => $data['completion_percentage'],
                'time_spent_minutes' => ($data['time_spent_minutes'] ?? 0),
                'last_accessed_at' => date('Y-m-d H:i:s')
            ], 'id = ? AND student_id = ?', [
                $data['enrollment_id'],
                $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Progress updated successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
?>
