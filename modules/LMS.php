<?php
/**
 * TPT Free ERP - Learning Management System Module
 * Complete e-learning platform with course management, certifications, and compliance training
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
            'lms_overview' => $this->getLMSOverview(),
            'lms_metrics' => $this->getLMSMetrics(),
            'recent_enrollments' => $this->getRecentEnrollments(),
            'upcoming_courses' => $this->getUpcomingCourses(),
            'certification_expiring' => $this->getCertificationExpiring(),
            'learning_alerts' => $this->getLearningAlerts()
        ];

        $this->render('modules/lms/dashboard', $data);
    }

    /**
     * Course catalog and management
     */
    public function courseCatalog() {
        $this->requirePermission('lms.courses.view');

        $data = [
            'title' => 'Course Catalog',
            'courses' => $this->getCourses(),
            'course_categories' => $this->getCourseCategories(),
            'course_levels' => $this->getCourseLevels(),
            'course_filters' => $this->getCourseFilters(),
            'featured_courses' => $this->getFeaturedCourses()
        ];

        $this->render('modules/lms/course_catalog', $data);
    }

    /**
     * Course creation and authoring
     */
    public function courseAuthoring() {
        $this->requirePermission('lms.courses.create');

        $data = [
            'title' => 'Course Authoring',
            'course_templates' => $this->getCourseTemplates(),
            'content_types' => $this->getContentTypes(),
            'assessment_tools' => $this->getAssessmentTools(),
            'media_library' => $this->getMediaLibrary(),
            'authoring_tools' => $this->getAuthoringTools()
        ];

        $this->render('modules/lms/course_authoring', $data);
    }

    /**
     * Student enrollment and management
     */
    public function studentManagement() {
        $this->requirePermission('lms.students.view');

        $data = [
            'title' => 'Student Management',
            'enrollments' => $this->getEnrollments(),
            'student_progress' => $this->getStudentProgress(),
            'student_performance' => $this->getStudentPerformance(),
            'learning_paths' => $this->getLearningPaths(),
            'student_groups' => $this->getStudentGroups()
        ];

        $this->render('modules/lms/student_management', $data);
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
            'certification_tracking' => $this->getCertificationTracking(),
            'renewal_reminders' => $this->getRenewalReminders(),
            'compliance_reporting' => $this->getComplianceReporting()
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
            'quiz_builder' => $this->getQuizBuilder(),
            'exam_management' => $this->getExamManagement(),
            'grading_system' => $this->getGradingSystem(),
            'assessment_analytics' => $this->getAssessmentAnalytics(),
            'question_bank' => $this->getQuestionBank()
        ];

        $this->render('modules/lms/assessments', $data);
    }

    /**
     * Learning analytics and reporting
     */
    public function learningAnalytics() {
        $this->requirePermission('lms.analytics.view');

        $data = [
            'title' => 'Learning Analytics',
            'engagement_metrics' => $this->getEngagementMetrics(),
            'completion_rates' => $this->getCompletionRates(),
            'learning_outcomes' => $this->getLearningOutcomes(),
            'performance_trends' => $this->getPerformanceTrends(),
            'roi_analysis' => $this->getROIAnalysis()
        ];

        $this->render('modules/lms/learning_analytics', $data);
    }

    /**
     * Compliance training
     */
    public function complianceTraining() {
        $this->requirePermission('lms.compliance.view');

        $data = [
            'title' => 'Compliance Training',
            'compliance_courses' => $this->getComplianceCourses(),
            'regulatory_requirements' => $this->getRegulatoryRequirements(),
            'training_records' => $this->getTrainingRecords(),
            'audit_trail' => $this->getAuditTrail(),
            'compliance_reporting' => $this->getComplianceReporting()
        ];

        $this->render('modules/lms/compliance_training', $data);
    }

    /**
     * Virtual classroom and live sessions
     */
    public function virtualClassroom() {
        $this->requirePermission('lms.virtual_classroom.view');

        $data = [
            'title' => 'Virtual Classroom',
            'live_sessions' => $this->getLiveSessions(),
            'session_scheduling' => $this->getSessionScheduling(),
            'video_conferencing' => $this->getVideoConferencing(),
            'interactive_tools' => $this->getInteractiveTools(),
            'session_recordings' => $this->getSessionRecordings()
        ];

        $this->render('modules/lms/virtual_classroom', $data);
    }

    /**
     * Mobile learning
     */
    public function mobileLearning() {
        $this->requirePermission('lms.mobile.view');

        $data = [
            'title' => 'Mobile Learning',
            'mobile_app_features' => $this->getMobileAppFeatures(),
            'offline_content' => $this->getOfflineContent(),
            'mobile_analytics' => $this->getMobileAnalytics(),
            'device_compatibility' => $this->getDeviceCompatibility(),
            'mobile_optimization' => $this->getMobileOptimization()
        ];

        $this->render('modules/lms/mobile_learning', $data);
    }

    /**
     * Instructor management
     */
    public function instructorManagement() {
        $this->requirePermission('lms.instructors.view');

        $data = [
            'title' => 'Instructor Management',
            'instructors' => $this->getInstructors(),
            'instructor_performance' => $this->getInstructorPerformance(),
            'course_assignments' => $this->getCourseAssignments(),
            'teaching_load' => $this->getTeachingLoad(),
            'instructor_development' => $this->getInstructorDevelopment()
        ];

        $this->render('modules/lms/instructor_management', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getLMSOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT c.id) as total_courses,
                COUNT(DISTINCT e.id) as total_enrollments,
                COUNT(DISTINCT s.id) as total_students,
                COUNT(DISTINCT cert.id) as total_certifications,
                AVG(cp.completion_percentage) as avg_completion_rate,
                COUNT(CASE WHEN ce.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_certifications
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN students s ON e.student_id = s.id
            LEFT JOIN course_progress cp ON e.id = cp.enrollment_id
            LEFT JOIN certifications cert ON cert.student_id = s.id
            LEFT JOIN certification_expiry ce ON cert.id = ce.certification_id
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getLMSMetrics() {
        return [
            'course_completion_rate' => $this->calculateCourseCompletionRate(),
            'student_engagement_score' => $this->calculateStudentEngagementScore(),
            'certification_completion_rate' => $this->calculateCertificationCompletionRate(),
            'average_learning_time' => $this->calculateAverageLearningTime(),
            'content_effectiveness' => $this->calculateContentEffectiveness(),
            'training_roi' => $this->calculateTrainingROI()
        ];
    }

    private function calculateCourseCompletionRate() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN cp.completion_percentage = 100 THEN 1 END) as completed,
                COUNT(*) as total_enrollments
            FROM course_progress cp
            JOIN enrollments e ON cp.enrollment_id = e.id
            JOIN courses c ON e.course_id = c.id
            WHERE c.company_id = ? AND e.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ", [$this->user['company_id']]);

        return $result['total_enrollments'] > 0 ? ($result['completed'] / $result['total_enrollments']) * 100 : 0;
    }

    private function calculateStudentEngagementScore() {
        $result = $this->db->querySingle("
            SELECT
                AVG(cp.time_spent_minutes) as avg_time_spent,
                AVG(cp.interactions_count) as avg_interactions,
                AVG(cp.completion_percentage) as avg_completion
            FROM course_progress cp
            JOIN enrollments e ON cp.enrollment_id = e.id
            JOIN courses c ON e.course_id = c.id
            WHERE c.company_id = ? AND e.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        // Calculate engagement score based on time spent, interactions, and completion
        $time_score = min($result['avg_time_spent'] / 60, 1) * 40; // Max 40 points for time
        $interaction_score = min($result['avg_interactions'] / 10, 1) * 30; // Max 30 points for interactions
        $completion_score = ($result['avg_completion'] / 100) * 30; // Max 30 points for completion

        return $time_score + $interaction_score + $completion_score;
    }

    private function calculateCertificationCompletionRate() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN ce.issue_date IS NOT NULL THEN 1 END) as issued_certifications,
                COUNT(*) as total_certifications
            FROM certifications cert
            LEFT JOIN certification_expiry ce ON cert.id = ce.certification_id
            WHERE cert.company_id = ? AND cert.created_at >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
        ", [$this->user['company_id']]);

        return $result['total_certifications'] > 0 ? ($result['issued_certifications'] / $result['total_certifications']) * 100 : 0;
    }

    private function calculateAverageLearningTime() {
        $result = $this->db->querySingle("
            SELECT AVG(cp.time_spent_minutes) as avg_learning_time
            FROM course_progress cp
            JOIN enrollments e ON cp.enrollment_id = e.id
            JOIN courses c ON e.course_id = c.id
            WHERE c.company_id = ? AND e.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['avg_learning_time'] ?? 0;
    }

    private function calculateContentEffectiveness() {
        $result = $this->db->querySingle("
            SELECT
                AVG(cp.completion_percentage) as avg_completion,
                AVG(f.rating) as avg_rating
            FROM course_progress cp
            JOIN enrollments e ON cp.enrollment_id = e.id
            JOIN courses c ON e.course_id = c.id
            LEFT JOIN feedback f ON e.id = f.enrollment_id
            WHERE c.company_id = ? AND e.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ", [$this->user['company_id']]);

        return (($result['avg_completion'] ?? 0) + ($result['avg_rating'] ?? 0) * 20) / 2;
    }

    private function calculateTrainingROI() {
        $result = $this->db->querySingle("
            SELECT
                SUM(c.cost_per_student * e.student_count) as total_training_cost,
                SUM(p.productivity_gain) as total_productivity_gain
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN productivity_metrics p ON e.id = p.enrollment_id
            WHERE c.company_id = ? AND e.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
        ", [$this->user['company_id']]);

        return $result['total_training_cost'] > 0 ? (($result['total_productivity_gain'] - $result['total_training_cost']) / $result['total_training_cost']) * 100 : 0;
    }

    private function getRecentEnrollments() {
        return $this->db->query("
            SELECT
                e.*,
                e.enrollment_date,
                c.course_title,
                s.first_name,
                s.last_name,
                s.email,
                cp.completion_percentage,
                cp.last_accessed
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            JOIN students s ON e.student_id = s.id
            LEFT JOIN course_progress cp ON e.id = cp.enrollment_id
            WHERE c.company_id = ?
            ORDER BY e.enrollment_date DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getUpcomingCourses() {
        return $this->db->query("
            SELECT
                c.*,
                c.course_title,
                c.start_date,
                c.enrollment_deadline,
                c.max_students,
                COUNT(e.id) as enrolled_students,
                i.first_name as instructor_first_name,
                i.last_name as instructor_last_name
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN instructors i ON c.instructor_id = i.id
            WHERE c.company_id = ? AND c.start_date >= CURDATE()
            GROUP BY c.id, c.course_title, c.start_date, c.enrollment_deadline, c.max_students, i.first_name, i.last_name
            ORDER BY c.start_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCertificationExpiring() {
        return $this->db->query("
            SELECT
                ce.*,
                ce.expiry_date,
                cert.certification_name,
                s.first_name,
                s.last_name,
                s.email,
                DATEDIFF(ce.expiry_date, CURDATE()) as days_until_expiry
            FROM certification_expiry ce
            JOIN certifications cert ON ce.certification_id = cert.id
            JOIN students s ON cert.student_id = s.id
            WHERE cert.company_id = ? AND ce.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            ORDER BY ce.expiry_date ASC
        ", [$this->user['company_id']]);
    }

    private function getLearningAlerts() {
        return $this->db->query("
            SELECT
                la.*,
                la.alert_type,
                la.severity,
                la.message,
                la.created_at,
                la.status,
                c.course_title,
                s.first_name as student_name
            FROM learning_alerts la
            LEFT JOIN courses c ON la.course_id = c.id
            LEFT JOIN students s ON la.student_id = s.id
            WHERE la.company_id = ? AND la.status = 'active'
            ORDER BY la.severity DESC, la.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCourses() {
        return $this->db->query("
            SELECT
                c.*,
                c.course_title,
                c.description,
                c.category,
                c.level,
                c.duration_hours,
                c.cost_per_student,
                c.max_students,
                COUNT(e.id) as enrolled_students,
                AVG(cp.completion_percentage) as avg_completion,
                AVG(f.rating) as avg_rating,
                i.first_name as instructor_first_name,
                i.last_name as instructor_last_name
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN course_progress cp ON e.id = cp.enrollment_id
            LEFT JOIN feedback f ON e.id = f.enrollment_id
            LEFT JOIN instructors i ON c.instructor_id = i.id
            WHERE c.company_id = ?
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCourseCategories() {
        return [
            'technical' => 'Technical Skills',
            'soft_skills' => 'Soft Skills',
            'compliance' => 'Compliance & Safety',
            'leadership' => 'Leadership & Management',
            'industry_specific' => 'Industry Specific',
            'certification' => 'Certification Prep'
        ];
    }

    private function getCourseLevels() {
        return [
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
            'expert' => 'Expert'
        ];
    }

    private function getCourseFilters() {
        return [
            'categories' => $this->getCourseCategories(),
            'levels' => $this->getCourseLevels(),
            'duration' => [
                '0-2' => '0-2 hours',
                '2-5' => '2-5 hours',
                '5-10' => '5-10 hours',
                '10+' => '10+ hours'
            ],
            'cost' => [
                'free' => 'Free',
                '0-50' => '$0-$50',
                '50-100' => '$50-$100',
                '100+' => '$100+'
            ]
        ];
    }

    private function getFeaturedCourses() {
        return $this->db->query("
            SELECT
                c.*,
                c.course_title,
                c.description,
                COUNT(e.id) as enrollment_count,
                AVG(f.rating) as avg_rating,
                AVG(cp.completion_percentage) as avg_completion
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN feedback f ON e.id = f.enrollment_id
            LEFT JOIN course_progress cp ON e.id = cp.enrollment_id
            WHERE c.company_id = ? AND c.is_featured = true
            GROUP BY c.id
            ORDER BY enrollment_count DESC
            LIMIT 6
        ", [$this->user['company_id']]);
    }

    private function getCourseTemplates() {
        return $this->db->query("
            SELECT
                ct.*,
                ct.template_name,
                ct.description,
                ct.category,
                ct.estimated_duration,
                COUNT(c.id) as usage_count
            FROM course_templates ct
            LEFT JOIN courses c ON ct.id = c.template_id
            WHERE ct.company_id = ?
            GROUP BY ct.id
            ORDER BY ct.usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getContentTypes() {
        return [
            'video' => 'Video Lectures',
            'document' => 'Documents & PDFs',
            'presentation' => 'Presentations',
            'quiz' => 'Quizzes & Tests',
            'assignment' => 'Assignments',
            'discussion' => 'Discussion Forums',
            'live_session' => 'Live Sessions',
            'interactive' => 'Interactive Content'
        ];
    }

    private function getAssessmentTools() {
        return [
            'multiple_choice' => 'Multiple Choice',
            'true_false' => 'True/False',
            'short_answer' => 'Short Answer',
            'essay' => 'Essay',
            'matching' => 'Matching',
            'ordering' => 'Ordering',
            'fill_blank' => 'Fill in the Blank'
        ];
    }

    private function getMediaLibrary() {
        return $this->db->query("
            SELECT
                ml.*,
                ml.file_name,
                ml.file_type,
                ml.file_size,
                ml.upload_date,
                COUNT(cu.id) as usage_count
            FROM media_library ml
            LEFT JOIN content_usage cu ON ml.id = cu.media_id
            WHERE ml.company_id = ?
            GROUP BY ml.id
            ORDER BY ml.upload_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAuthoringTools() {
        return [
            'content_editor' => 'Rich Text Editor',
            'video_recorder' => 'Video Recorder',
            'screen_capture' => 'Screen Capture',
            'quiz_builder' => 'Quiz Builder',
            'certificate_designer' => 'Certificate Designer',
            'scorm_converter' => 'SCORM Converter'
        ];
    }

    private function getEnrollments() {
        return $this->db->query("
            SELECT
                e.*,
                e.enrollment_date,
                e.status,
                c.course_title,
                s.first_name,
                s.last_name,
                s.email,
                cp.completion_percentage,
                cp.time_spent_minutes,
                cp.last_accessed
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            JOIN students s ON e.student_id = s.id
            LEFT JOIN course_progress cp ON e.id = cp.enrollment_id
            WHERE c.company_id = ?
            ORDER BY e.enrollment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getStudentProgress() {
        return $this->db->query("
            SELECT
                s.first_name,
                s.last_name,
                s.email,
                COUNT(e.id) as enrolled_courses,
                COUNT(CASE WHEN cp.completion_percentage = 100 THEN 1 END) as completed_courses,
                AVG(cp.completion_percentage) as avg_completion,
                SUM(cp.time_spent_minutes) as total_time_spent,
                MAX(cp.last_accessed) as last_activity
            FROM students s
            LEFT JOIN enrollments e ON s.id = e.student_id
            LEFT JOIN course_progress cp ON e.id = cp.enrollment_id
            WHERE s.company_id = ?
            GROUP BY s.id, s.first_name, s.last_name, s.email
            ORDER BY avg_completion DESC
        ", [$this->user['company_id']]);
    }

    private function getStudentPerformance() {
        return $this->db->query("
            SELECT
                s.first_name,
                s.last_name,
                AVG(q.score) as avg_quiz_score,
                AVG(a.grade) as avg_assignment_grade,
                COUNT(CASE WHEN q.score >= 80 THEN 1 END) as high_scores,
                COUNT(CASE WHEN q.score < 60 THEN 1 END) as low_scores,
                MAX(q.attempt_date) as last_assessment
            FROM students s
            LEFT JOIN quiz_attempts q ON s.id = q.student_id
            LEFT JOIN assignment_submissions a ON s.id = a.student_id
            WHERE s.company_id = ?
            GROUP BY s.id, s.first_name, s.last_name
            ORDER BY avg_quiz_score DESC
        ", [$this->user['company_id']]);
    }

    private function getLearningPaths() {
        return $this->db->query("
            SELECT
                lp.*,
                lp.path_name,
                lp.description,
                lp.target_audience,
                lp.estimated_duration,
                COUNT(lpc.id) as course_count,
                COUNT(CASE WHEN lps.status = 'completed' THEN 1 END) as completed_by_students
            FROM learning_paths lp
            LEFT JOIN learning_path_courses lpc ON lp.id = lpc.path_id
            LEFT JOIN learning_path_students lps ON lp.id = lps.path_id
            WHERE lp.company_id = ?
            GROUP BY lp.id
            ORDER BY lp.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getStudentGroups() {
        return $this->db->query("
            SELECT
                sg.*,
                sg.group_name,
                sg.description,
                COUNT(sg.id) as student_count,
                i.first_name as instructor_first_name,
                i.last_name as instructor_last_name
            FROM student_groups sg
            LEFT JOIN group_members gm ON sg.id = gm.group_id
            LEFT JOIN instructors i ON sg.instructor_id = i.id
            WHERE sg.company_id = ?
            GROUP BY sg.id
            ORDER BY sg.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCertifications() {
        return $this->db->query("
            SELECT
                cert.*,
                cert.certification_name,
                cert.description,
                cert.validity_period_months,
                cert.issuing_authority,
                COUNT(ce.id) as issued_count,
                COUNT(CASE WHEN ce.expiry_date > CURDATE() THEN 1 END) as active_count
            FROM certifications cert
            LEFT JOIN certification_expiry ce ON cert.id = ce.certification_id
            WHERE cert.company_id = ?
            GROUP BY cert.id
            ORDER BY cert.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCertificationTemplates() {
        return $this->db->query("
            SELECT
                ct.*,
                ct.template_name,
                ct.certification_type,
                ct.design_layout,
                ct.include_qr,
                ct.include_barcode,
                COUNT(c.id) as usage_count
            FROM certification_templates ct
            LEFT JOIN certifications c ON ct.id = c.template_id
            WHERE ct.company_id = ?
            GROUP BY ct.id
            ORDER BY ct.usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getCertificationTracking() {
        return $this->db->query("
            SELECT
                ce.*,
                ce.issue_date,
                ce.expiry_date,
                cert.certification_name,
                s.first_name,
                s.last_name,
                s.email,
                DATEDIFF(ce.expiry_date, CURDATE()) as days_until_expiry
            FROM certification_expiry ce
            JOIN certifications cert ON ce.certification_id = cert.id
            JOIN students s ON ce.student_id = s.id
            WHERE cert.company_id = ?
            ORDER BY ce.expiry_date ASC
        ", [$this->user['company_id']]);
    }

    private function getRenewalReminders() {
        return $this->db->query("
            SELECT
                ce.*,
                ce.expiry_date,
                cert.certification_name,
                s.first_name,
                s.last_name,
                s.email,
                DATEDIFF(ce.expiry_date, CURDATE()) as days_until_expiry,
                COUNT(r.id) as reminder_count
            FROM certification_expiry ce
            JOIN certifications cert ON ce.certification_id = cert.id
            JOIN students s ON ce.student_id = s.id
            LEFT JOIN renewal_reminders r ON ce.id = r.certification_expiry_id
            WHERE cert.company_id = ? AND ce.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            GROUP BY ce.id
            ORDER BY ce.expiry_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceReporting() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.report_name,
                cr.report_period,
                cr.generated_date,
                cr.compliance_percentage,
                cr.non_compliant_count,
                cr.total_students
            FROM compliance_reports cr
            WHERE cr.company_id = ?
            ORDER BY cr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getQuizBuilder() {
        return [
            'question_types' => $this->getAssessmentTools(),
            'scoring_methods' => [
                'percentage' => 'Percentage Based',
                'points' => 'Points Based',
                'weighted' => 'Weighted Scoring'
            ],
            'quiz_settings' => [
                'time_limit' => 'Time Limit',
                'attempts_allowed' => 'Attempts Allowed',
                'passing_score' => 'Passing Score',
                'randomize_questions' => 'Randomize Questions'
            ]
        ];
    }

    private function getExamManagement() {
        return $this->db->query("
            SELECT
                e.*,
                e.exam_name,
                e.exam_type,
                e.duration_minutes,
                e.passing_score,
                e.max_attempts,
                COUNT(ea.id) as attempt_count,
                AVG(ea.score) as avg_score
            FROM exams e
            LEFT JOIN exam_attempts ea ON e.id = ea.exam_id
            WHERE e.company_id = ?
            GROUP BY e.id
            ORDER BY e.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getGradingSystem() {
        return [
            'grade_scales' => [
                'letter' => ['A', 'B', 'C', 'D', 'F'],
                'percentage' => ['90-100%', '80-89%', '70-79%', '60-69%', '0-59%'],
                'points' => ['Excellent', 'Good', 'Satisfactory', 'Needs Improvement', 'Unsatisfactory']
            ],
            'grading_rules' => [
                'auto_grading' => 'Automatic Grading',
                'manual_grading' => 'Manual Grading',
                'peer_grading' => 'Peer Grading',
                'rubric_based' => 'Rubric Based'
            ]
        ];
    }

    private function getAssessmentAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_assessments,
                AVG(score) as avg_score,
                COUNT(CASE WHEN score >= 80 THEN 1 END) as high_scores,
                COUNT(CASE WHEN score < 60 THEN 1 END) as low_scores,
                AVG(time_taken_minutes) as avg_time_taken
            FROM assessment_results
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
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
                COUNT(qba.id) as usage_count,
                AVG(qba.score) as avg_score
            FROM question_bank qb
            LEFT JOIN question_bank_attempts qba ON qb.id = qba.question_id
            WHERE qb.company_id = ?
            GROUP BY qb.id
            ORDER BY qb.usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getEngagementMetrics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(DISTINCT student_id) as active_students,
                AVG(time_spent_minutes) as avg_time_spent,
                AVG(interactions_count) as avg_interactions,
                COUNT(CASE WHEN completion_percentage = 100 THEN 1 END) as completions
            FROM course_progress
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getCompletionRates() {
        return $this->db->query("
            SELECT
                c.course_title,
                COUNT(e.id) as total_enrollments,
                COUNT(CASE WHEN cp.completion_percentage = 100 THEN 1 END) as completions,
                ROUND((COUNT(CASE WHEN cp.completion_percentage = 100 THEN 1 END) / COUNT(e.id)) * 100, 2) as completion_rate,
                AVG(cp.time_spent_minutes) as avg_time_to_complete
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN course_progress cp ON e.id = cp.enrollment_id
            WHERE c.company_id = ?
            GROUP BY c.id, c.course_title
            ORDER BY completion_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getLearningOutcomes() {
        return $this->db->query("
            SELECT
                lo.*,
                lo.outcome_name,
                lo.description,
                lo.target_percentage,
                COUNT(CASE WHEN la.achieved = true THEN 1 END) as achieved_count,
                COUNT(la.id) as total_attempts,
                ROUND((COUNT(CASE WHEN la.achieved = true THEN 1 END) / COUNT(la.id)) * 100, 2) as achievement_rate
            FROM learning_outcomes lo
            LEFT JOIN learning_achievements la ON lo.id = la.outcome_id
            WHERE lo.company_id = ?
            GROUP BY lo.id
            ORDER BY achievement_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceTrends() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                AVG(completion_percentage) as avg_completion,
                AVG(time_spent_minutes) as avg_time_spent,
                COUNT(CASE WHEN completion_percentage = 100 THEN 1 END) as total_completions,
                COUNT(DISTINCT student_id) as active_students
            FROM course_progress
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getROIAnalysis() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(c.cost_per_student * e.student_count) as training_cost,
                SUM(pm.productivity_gain) as productivity_gain,
                SUM(pm.cost_savings) as cost_savings,
                ROUND(((SUM(pm.productivity_gain) + SUM(pm.cost_savings) - SUM(c.cost_per_student * e.student_count)) / SUM(c.cost_per_student * e.student_count)) * 100, 2) as roi_percentage
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN productivity_metrics pm ON e.id = pm.enrollment_id
            WHERE c.company_id = ? AND e.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
            GROUP BY DATE_FORMAT(e.enrollment_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceCourses() {
        return $this->db->query("
            SELECT
                c.*,
                c.course_title,
                c.compliance_standard,
                c.refresh_frequency_months,
                COUNT(e.id) as enrolled_students,
                COUNT(CASE WHEN cp.completion_percentage = 100 THEN 1 END) as completed_students,
                MAX(cp.completed_at) as last_completion
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN course_progress cp ON e.id = cp.enrollment_id
            WHERE c.company_id = ? AND c.is_compliance_course = true
            GROUP BY c.id
            ORDER BY c.refresh_frequency_months ASC
        ", [$this->user['company_id']]);
    }

    private function getRegulatoryRequirements() {
        return $this->db->query("
            SELECT
                rr.*,
                rr.requirement_name,
                rr.regulatory_body,
                rr.compliance_deadline,
                rr.training_required,
                COUNT(ct.id) as training_courses,
                COUNT(CASE WHEN rr.status = 'compliant' THEN 1 END) as compliant_records
            FROM regulatory_requirements rr
            LEFT JOIN compliance_training ct ON rr.id = ct.requirement_id
            WHERE rr.company_id = ?
            GROUP BY rr.id
            ORDER BY rr.compliance_deadline ASC
        ", [$this->user['company_id']]);
    }

    private function getTrainingRecords() {
        return $this->db->query("
            SELECT
                tr.*,
                tr.training_date,
                tr.completion_status,
                tr.certification_issued,
                c.course_title,
                s.first_name,
                s.last_name,
                rr.requirement_name
            FROM training_records tr
            JOIN courses c ON tr.course_id = c.id
            JOIN students s ON tr.student_id = s.id
            LEFT JOIN regulatory_requirements rr ON tr.requirement_id = rr.id
            WHERE c.company_id = ?
            ORDER BY tr.training_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAuditTrail() {
        return $this->db->query("
            SELECT
                at.*,
                at.action_type,
                at.action_date,
                at.details,
                c.course_title,
                s.first_name as student_name,
                u.first_name as user_name
            FROM audit_trail at
            LEFT JOIN courses c ON at.course_id = c.id
            LEFT JOIN students s ON at.student_id = s.id
            LEFT JOIN users u ON at.user_id = u.id
            WHERE at.company_id = ?
            ORDER BY at.action_date DESC
        ", [$this->user['company_id']]);
    }

    private function getLiveSessions() {
        return $this->db->query("
            SELECT
                ls.*,
                ls.session_title,
                ls.scheduled_date,
                ls.duration_minutes,
                ls.max_participants,
                COUNT(lsp.id) as registered_participants,
                i.first_name as instructor_first_name,
                i.last_name as instructor_last_name
            FROM live_sessions ls
            LEFT JOIN live_session_participants lsp ON ls.id = lsp.session_id
            LEFT JOIN instructors i ON ls.instructor_id = i.id
            WHERE ls.company_id = ?
            GROUP BY ls.id
            ORDER BY ls.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getSessionScheduling() {
        return [
            'availability' => $this->getInstructorAvailability(),
            'room_capacity' => $this->getRoomCapacity(),
            'technical_requirements' => $this->getTechnicalRequirements(),
            'time_zones' => $this->getSupportedTimeZones()
        ];
    }

    private function getVideoConferencing() {
        return [
            'providers' => ['Zoom', 'Microsoft Teams', 'Google Meet', 'WebRTC'],
            'features' => [
                'screen_sharing' => true,
                'recording' => true,
                'breakout_rooms' => true,
                'polling' => true,
                'chat' => true,
                'whiteboard' => true
            ],
            'integrations' => $this->getVideoIntegrations()
        ];
    }

    private function getInteractiveTools() {
        return [
            'polls' => 'Real-time Polls',
            'quizzes' => 'Live Quizzes',
            'whiteboard' => 'Collaborative Whiteboard',
            'breakout_rooms' => 'Breakout Rooms',
            'hand_raising' => 'Hand Raising',
            'screen_sharing' => 'Screen Sharing',
            'annotations' => 'Content Annotations'
        ];
    }

    private function getSessionRecordings() {
        return $this->db->query("
            SELECT
                sr.*,
                sr.recording_title,
                sr.recording_date,
                sr.duration_minutes,
                sr.file_size,
                sr.view_count,
                ls.session_title,
                i.first_name as instructor_name
            FROM session_recordings sr
            JOIN live_sessions ls ON sr.session_id = ls.id
            LEFT JOIN instructors i ON ls.instructor_id = i.id
            WHERE sr.company_id = ?
            ORDER BY sr.recording_date DESC
        ", [$this->user['company_id']]);
    }

    private function getMobileAppFeatures() {
        return [
            'offline_access' => true,
            'push_notifications' => true,
            'progress_sync' => true,
            'certificate_download' => true,
            'social_learning' => true,
            'gamification' => true,
            'adaptive_learning' => true
        ];
    }

    private function getOfflineContent() {
        return $this->db->query("
            SELECT
                oc.*,
                oc.content_title,
                oc.content_type,
                oc.file_size,
                oc.download_count,
                c.course_title
            FROM offline_content oc
            JOIN courses c ON oc.course_id = c.id
            WHERE c.company_id = ?
            ORDER BY oc.download_count DESC
        ", [$this->user['company_id']]);
    }

    private function getMobileAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(access_date, '%Y-%m') as month,
                COUNT(DISTINCT student_id) as active_mobile_users,
                SUM(session_duration) as total_mobile_time,
                AVG(session_duration) as avg_session_duration,
                COUNT(CASE WHEN device_type = 'mobile' THEN 1 END) as mobile_sessions,
                COUNT(CASE WHEN device_type = 'tablet' THEN 1 END) as tablet_sessions
            FROM mobile_analytics
            WHERE company_id = ? AND access_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(access_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getDeviceCompatibility() {
        return [
            'ios' => ['min_version' => '12.0', 'supported' => true],
            'android' => ['min_version' => '8.0', 'supported' => true],
            'windows' => ['min_version' => '10', 'supported' => true],
            'macos' => ['min_version' => '10.14', 'supported' => true]
        ];
    }

    private function getMobileOptimization() {
        return [
            'responsive_design' => true,
            'touch_optimized' => true,
            'low_bandwidth_mode' => true,
            'battery_optimization' => true,
            'offline_first' => true,
            'progressive_web_app' => true
        ];
    }

    private function getInstructors() {
        return $this->db->query("
            SELECT
                i.*,
                i.first_name,
                i.last_name,
                i.email,
                i.specialization,
                i.rating,
                COUNT(c.id) as courses_taught,
                COUNT(e.id) as total_students,
                AVG(f.rating) as avg_feedback_rating
            FROM instructors i
            LEFT JOIN courses c ON i.id = c.instructor_id
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN feedback f ON e.id = f.enrollment_id
            WHERE i.company_id = ?
            GROUP BY i.id
            ORDER BY i.rating DESC
        ", [$this->user['company_id']]);
    }

    private function getInstructorPerformance() {
        return $this->db->query("
            SELECT
                i.first_name,
                i.last_name,
                COUNT(c.id) as courses_count,
                COUNT(e.id) as students_count,
                AVG(cp.completion_percentage) as avg_completion_rate,
                AVG(f.rating) as avg_rating,
                SUM(c.cost_per_student * (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id)) as revenue_generated
            FROM instructors i
            LEFT JOIN courses c ON i.id = c.instructor_id
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN course_progress cp ON e.id = cp.enrollment_id
            LEFT JOIN feedback f ON e.id = f.enrollment_id
            WHERE i.company_id = ?
            GROUP BY i.id, i.first_name, i.last_name
            ORDER BY avg_rating DESC
        ", [$this->user['company_id']]);
    }

    private function getCourseAssignments() {
        return $this->db->query("
            SELECT
                c.course_title,
                c.start_date,
                c.end_date,
                i.first_name as instructor_first_name,
                i.last_name as instructor_last_name,
                COUNT(e.id) as enrolled_students,
                AVG(cp.completion_percentage) as avg_completion
            FROM courses c
            JOIN instructors i ON c.instructor_id = i.id
            LEFT JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN course_progress cp ON e.id = cp.enrollment_id
            WHERE c.company_id = ?
            GROUP BY c.id, c.course_title, c.start_date, c.end_date, i.first_name, i.last_name
            ORDER BY c.start_date ASC
        ", [$this->user['company_id']]);
    }

    private function getTeachingLoad() {
        return $this->db->query("
            SELECT
                i.first_name,
                i.last_name,
                COUNT(c.id) as active_courses,
                SUM(c.duration_hours) as total_teaching_hours,
                COUNT(e.id) as total_students,
                AVG(c.cost_per_student) as avg_course_price
            FROM instructors i
            LEFT JOIN courses c ON i.id = c.instructor_id AND c.status = 'active
