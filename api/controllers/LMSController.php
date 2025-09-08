<?php
/**
 * TPT Free ERP - Learning Management System API Controller
 * Complete REST API for course management, student enrollment, certification, and compliance training
 */

class LMSController extends BaseController {
    private $db;
    private $user;
    private $lms;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->lms = new LMS();
    }

    // ============================================================================
    // DASHBOARD ENDPOINTS
    // ============================================================================

    /**
     * Get LMS overview
     */
    public function getOverview() {
        $this->requirePermission('lms.view');

        try {
            $overview = $this->lms->getCourseOverview();
            $this->jsonResponse($overview);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get enrollment statistics
     */
    public function getEnrollmentStats() {
        $this->requirePermission('lms.view');

        try {
            $stats = $this->lms->getEnrollmentStats();
            $this->jsonResponse($stats);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get certification status
     */
    public function getCertificationStatus() {
        $this->requirePermission('lms.certifications.view');

        try {
            $status = $this->lms->getCertificationStatus();
            $this->jsonResponse($status);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get training compliance
     */
    public function getTrainingCompliance() {
        $this->requirePermission('lms.compliance.view');

        try {
            $compliance = $this->lms->getTrainingCompliance();
            $this->jsonResponse($compliance);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get assessment results
     */
    public function getAssessmentResults() {
        $this->requirePermission('lms.assessments.view');

        try {
            $results = $this->lms->getAssessmentResults();
            $this->jsonResponse($results);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get learning analytics
     */
    public function getLearningAnalytics() {
        $this->requirePermission('lms.analytics.view');

        try {
            $analytics = $this->lms->getLearningAnalytics();
            $this->jsonResponse($analytics);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get upcoming deadlines
     */
    public function getUpcomingDeadlines() {
        $this->requirePermission('lms.view');

        try {
            $deadlines = $this->lms->getUpcomingDeadlines();
            $this->jsonResponse($deadlines);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get training alerts
     */
    public function getTrainingAlerts() {
        $this->requirePermission('lms.view');

        try {
            $alerts = $this->lms->getTrainingAlerts();
            $this->jsonResponse($alerts);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // COURSE MANAGEMENT ENDPOINTS
    // ============================================================================

    /**
     * Get courses with filtering and pagination
     */
    public function getCourses() {
        $this->requirePermission('lms.courses.view');

        try {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'category' => $_GET['category'] ?? null,
                'instructor' => $_GET['instructor'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null
            ];

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 50);

            $courses = $this->lms->getCourses($filters);
            $total = count($courses);
            $pages = ceil($total / $limit);
            $offset = ($page - 1) * $limit;

            $paginatedCourses = array_slice($courses, $offset, $limit);

            $this->jsonResponse([
                'courses' => $paginatedCourses,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => $pages
                ]
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get single course by ID
     */
    public function getCourse($id) {
        $this->requirePermission('lms.courses.view');

        try {
            $course = $this->db->querySingle("
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
                WHERE c.id = ? AND c.company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$course) {
                $this->errorResponse('Course not found', 404);
                return;
            }

            $this->jsonResponse($course);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create new course
     */
    public function createCourse() {
        $this->requirePermission('lms.courses.manage');

        try {
            $data = $this->getJsonInput();

            // Validate required fields
            $required = ['course_name', 'category_id', 'instructor_id'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            // Generate course code if not provided
            $courseCode = $data['course_code'] ?? $this->generateCourseCode($data['course_name']);

            // Prepare course data
            $courseData = [
                'company_id' => $this->user['company_id'],
                'course_name' => trim($data['course_name']),
                'course_code' => $courseCode,
                'category_id' => $data['category_id'],
                'instructor_id' => $data['instructor_id'],
                'description' => $data['description'] ?? null,
                'objectives' => $data['objectives'] ?? null,
                'prerequisites' => $data['prerequisites'] ?? null,
                'duration_hours' => $data['duration_hours'] ?? 0,
                'total_learning_hours' => $data['total_learning_hours'] ?? $data['duration_hours'],
                'difficulty_level' => $data['difficulty_level'] ?? 'intermediate',
                'max_enrollments' => $data['max_enrollments'] ?? null,
                'enrollment_count' => 0,
                'price' => $data['price'] ?? 0,
                'currency' => $data['currency'] ?? 'USD',
                'status' => $data['status'] ?? 'draft',
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'next_session_date' => $data['next_session_date'] ?? null,
                'certification_expiry' => $data['certification_expiry'] ?? null,
                'completion_rate' => 0,
                'student_rating' => 0,
                'is_self_paced' => $data['is_self_paced'] ?? true,
                'requires_approval' => $data['requires_approval'] ?? false,
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $courseId = $this->db->insert('courses', $courseData);

            // Log the creation
            $this->logActivity('course_created', 'Course created', $courseId, [
                'course_name' => $courseData['course_name'],
                'course_code' => $courseData['course_code']
            ]);

            $this->jsonResponse([
                'success' => true,
                'course_id' => $courseId,
                'course_code' => $courseCode,
                'message' => 'Course created successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update course
     */
    public function updateCourse($id) {
        $this->requirePermission('lms.courses.manage');

        try {
            $data = $this->getJsonInput();

            // Check if course exists and belongs to company
            $existing = $this->db->querySingle("
                SELECT id FROM courses WHERE id = ? AND company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$existing) {
                $this->errorResponse('Course not found', 404);
                return;
            }

            // Prepare update data
            $updateData = [];
            $allowedFields = [
                'course_name', 'course_code', 'category_id', 'instructor_id', 'description',
                'objectives', 'prerequisites', 'duration_hours', 'total_learning_hours',
                'difficulty_level', 'max_enrollments', 'price', 'currency', 'status',
                'start_date', 'end_date', 'next_session_date', 'certification_expiry',
                'is_self_paced', 'requires_approval'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $this->db->update('courses', $updateData, "id = ?", [$id]);

                // Log the update
                $this->logActivity('course_updated', 'Course updated', $id, $updateData);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Course updated successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete course
     */
    public function deleteCourse($id) {
        $this->requirePermission('lms.courses.manage');

        try {
            // Check if course exists and belongs to company
            $course = $this->db->querySingle("
                SELECT course_name, course_code FROM courses WHERE id = ? AND company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$course) {
                $this->errorResponse('Course not found', 404);
                return;
            }

            // Soft delete by updating status
            $this->db->update('courses', [
                'status' => 'archived',
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = ?", [$id]);

            // Log the deletion
            $this->logActivity('course_deleted', 'Course deleted', $id, [
                'course_name' => $course['course_name'],
                'course_code' => $course['course_code']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Course deleted successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get course categories
     */
    public function getCourseCategories() {
        $this->requirePermission('lms.courses.view');

        try {
            $categories = $this->lms->getCourseCategories();
            $this->jsonResponse($categories);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get instructors
     */
    public function getInstructors() {
        $this->requirePermission('lms.courses.view');

        try {
            $instructors = $this->lms->getInstructors();
            $this->jsonResponse($instructors);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // ENROLLMENT MANAGEMENT ENDPOINTS
    // ============================================================================

    /**
     * Get enrollments
     */
    public function getEnrollments() {
        $this->requirePermission('lms.enrollments.view');

        try {
            $enrollments = $this->lms->getEnrollments();
            $this->jsonResponse($enrollments);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Enroll student in course
     */
    public function enrollStudent() {
        $this->requirePermission('lms.enrollments.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['student_id', 'course_id'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if student is already enrolled
            $existing = $this->db->querySingle("
                SELECT id FROM enrollments
                WHERE student_id = ? AND course_id = ? AND company_id = ?
            ", [$data['student_id'], $data['course_id'], $this->user['company_id']]);

            if ($existing) {
                $this->errorResponse('Student is already enrolled in this course', 400);
                return;
            }

            // Get course details
            $course = $this->db->querySingle("
                SELECT max_enrollments, enrollment_count FROM courses
                WHERE id = ? AND company_id = ?
            ", [$data['course_id'], $this->user['company_id']]);

            if (!$course) {
                $this->errorResponse('Course not found', 404);
                return;
            }

            // Check enrollment limit
            if ($course['max_enrollments'] && $course['enrollment_count'] >= $course['max_enrollments']) {
                $this->errorResponse('Course enrollment limit reached', 400);
                return;
            }

            $enrollmentData = [
                'company_id' => $this->user['company_id'],
                'student_id' => $data['student_id'],
                'course_id' => $data['course_id'],
                'enrollment_date' => date('Y-m-d'),
                'status' => $data['status'] ?? 'active',
                'progress_percentage' => 0,
                'due_date' => $data['due_date'] ?? null,
                'enrolled_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $enrollmentId = $this->db->insert('enrollments', $enrollmentData);

            // Update course enrollment count
            $this->db->update('courses', [
                'enrollment_count' => $course['enrollment_count'] + 1,
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = ?", [$data['course_id']]);

            // Log the enrollment
            $this->logActivity('student_enrolled', 'Student enrolled in course', $enrollmentId, [
                'student_id' => $data['student_id'],
                'course_id' => $data['course_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'enrollment_id' => $enrollmentId,
                'message' => 'Student enrolled successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update enrollment progress
     */
    public function updateProgress($enrollmentId) {
        $this->requirePermission('lms.enrollments.manage');

        try {
            $data = $this->getJsonInput();

            if (!isset($data['progress_percentage'])) {
                $this->errorResponse('Progress percentage is required', 400);
                return;
            }

            // Check if enrollment exists and belongs to company
            $enrollment = $this->db->querySingle("
                SELECT id, course_id FROM enrollments
                WHERE id = ? AND company_id = ?
            ", [$enrollmentId, $this->user['company_id']]);

            if (!$enrollment) {
                $this->errorResponse('Enrollment not found', 404);
                return;
            }

            $updateData = [
                'progress_percentage' => min(100, max(0, (float)$data['progress_percentage'])),
                'last_activity_date' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Mark as completed if progress is 100%
            if ($updateData['progress_percentage'] >= 100) {
                $updateData['status'] = 'completed';
                $updateData['completion_date'] = date('Y-m-d');
            }

            $this->db->update('enrollments', $updateData, "id = ?", [$enrollmentId]);

            // Log the progress update
            $this->logActivity('progress_updated', 'Enrollment progress updated', $enrollmentId, [
                'progress_percentage' => $updateData['progress_percentage']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Progress updated successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // CERTIFICATION ENDPOINTS
    // ============================================================================

    /**
     * Get certifications
     */
    public function getCertifications() {
        $this->requirePermission('lms.certifications.view');

        try {
            $certifications = $this->lms->getCertifications();
            $this->jsonResponse($certifications);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Award certification
     */
    public function awardCertification() {
        $this->requirePermission('lms.certifications.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['student_id', 'certification_id', 'score'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if student already has this certification
            $existing = $this->db->querySingle("
                SELECT id FROM student_certifications
                WHERE student_id = ? AND certification_id = ? AND company_id = ?
            ", [$data['student_id'], $data['certification_id'], $this->user['company_id']]);

            if ($existing) {
                $this->errorResponse('Student already has this certification', 400);
                return;
            }

            // Get certification template details
            $template = $this->db->querySingle("
                SELECT validity_period_months FROM certification_templates
                WHERE id = ? AND company_id = ?
            ", [$data['certification_id'], $this->user['company_id']]);

            $issueDate = date('Y-m-d');
            $expiryDate = $template['validity_period_months']
                ? date('Y-m-d', strtotime("+{$template['validity_period_months']} months"))
                : null;

            $certificationData = [
                'company_id' => $this->user['company_id'],
                'student_id' => $data['student_id'],
                'certification_id' => $data['certification_id'],
                'issue_date' => $issueDate,
                'expiry_date' => $expiryDate,
                'score' => (float)$data['score'],
                'passed' => $data['passed'] ?? ($data['score'] >= 70),
                'status' => $data['status'] ?? 'active',
                'awarded_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $certificationId = $this->db->insert('student_certifications', $certificationData);

            // Log the certification award
            $this->logActivity('certification_awarded', 'Certification awarded', $certificationId, [
                'student_id' => $data['student_id'],
                'certification_id' => $data['certification_id'],
                'score' => $data['score']
            ]);

            $this->jsonResponse([
                'success' => true,
                'certification_id' => $certificationId,
                'message' => 'Certification awarded successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // ASSESSMENT ENDPOINTS
    // ============================================================================

    /**
     * Get assessments
     */
    public function getAssessments() {
        $this->requirePermission('lms.assessments.view');

        try {
            $assessments = $this->lms->getAssessments();
            $this->jsonResponse($assessments);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Submit assessment result
     */
    public function submitAssessmentResult() {
        $this->requirePermission('lms.assessments.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['student_id', 'assessment_id', 'score'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            // Get assessment details
            $assessment = $this->db->querySingle("
                SELECT passing_score FROM assessments
                WHERE id = ? AND company_id = ?
            ", [$data['assessment_id'], $this->user['company_id']]);

            if (!$assessment) {
                $this->errorResponse('Assessment not found', 404);
                return;
            }

            $resultData = [
                'company_id' => $this->user['company_id'],
                'student_id' => $data['student_id'],
                'assessment_id' => $data['assessment_id'],
                'score' => (float)$data['score'],
                'passing_score' => $assessment['passing_score'],
                'passed' => $data['score'] >= $assessment['passing_score'],
                'completion_time' => $data['completion_time'] ?? null,
                'attempt_date' => date('Y-m-d H:i:s'),
                'answers' => isset($data['answers']) ? json_encode($data['answers']) : null,
                'feedback' => $data['feedback'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $resultId = $this->db->insert('assessment_results', $resultData);

            // Log the assessment result
            $this->logActivity('assessment_submitted', 'Assessment result submitted', $resultId, [
                'student_id' => $data['student_id'],
                'assessment_id' => $data['assessment_id'],
                'score' => $data['score'],
                'passed' => $resultData['passed']
            ]);

            $this->jsonResponse([
                'success' => true,
                'result_id' => $resultId,
                'passed' => $resultData['passed'],
                'message' => 'Assessment result submitted successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // COMPLIANCE ENDPOINTS
    // ============================================================================

    /**
     * Get compliance requirements
     */
    public function getComplianceRequirements() {
        $this->requirePermission('lms.compliance.view');

        try {
            $requirements = $this->lms->getComplianceRequirements();
            $this->jsonResponse($requirements);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update compliance status
     */
    public function updateComplianceStatus() {
        $this->requirePermission('lms.compliance.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['user_id', 'requirement_id', 'compliance_status'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            $complianceData = [
                'company_id' => $this->user['company_id'],
                'user_id' => $data['user_id'],
                'requirement_id' => $data['requirement_id'],
                'compliance_status' => $data['compliance_status'],
                'enrollment_date' => $data['enrollment_date'] ?? date('Y-m-d'),
                'completion_date' => $data['completion_date'] ?? null,
                'next_training_date' => $data['next_training_date'] ?? null,
                'compliance_score' => $data['compliance_score'] ?? 0,
                'last_updated' => date('Y-m-d H:i:s'),
                'updated_by' => $this->user['id']
            ];

            // Check if record exists
            $existing = $this->db->querySingle("
                SELECT id FROM training_compliance
                WHERE user_id = ? AND requirement_id = ? AND company_id = ?
            ", [$data['user_id'], $data['requirement_id'], $this->user['company_id']]);

            if ($existing) {
                $this->db->update('training_compliance', $complianceData,
                    "user_id = ? AND requirement_id = ? AND company_id = ?",
                    [$data['user_id'], $data['requirement_id'], $this->user['company_id']]);
            } else {
                $this->db->insert('training_compliance', $complianceData);
            }

            // Log the compliance update
            $this->logActivity('compliance_updated', 'Compliance status updated', null, [
                'user_id' => $data['user_id'],
                'requirement_id' => $data['requirement_id'],
                'compliance_status' => $data['compliance_status']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Compliance status updated successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // ANALYTICS ENDPOINTS
    // ============================================================================

    /**
     * Get learning metrics
     */
    public function getLearningMetrics() {
        $this->requirePermission('lms.analytics.view');

        try {
            $metrics = $this->lms->getLearningMetrics();
            $this->jsonResponse($metrics);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get student engagement
     */
    public function getStudentEngagement() {
        $this->requirePermission('lms.analytics.view');

        try {
            $engagement = $this->lms->getStudentEngagement();
            $this->jsonResponse($engagement);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get course effectiveness
     */
    public function getCourseEffectiveness() {
        $this->requirePermission('lms.analytics.view');

        try {
            $effectiveness = $this->lms->getCourseEffectiveness();
            $this->jsonResponse($effectiveness);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // BULK OPERATIONS ENDPOINTS
    // ============================================================================

    /**
     * Bulk enroll students
     */
    public function bulkEnrollStudents() {
        $this->requirePermission('lms.enrollments.manage');

        try {
            $data = $this->getJsonInput();

            if (!isset($data['student_ids']) || !is_array($data['student_ids'])) {
                $this->errorResponse('Student IDs are required', 400);
                return;
            }

            if (!isset($data['course_id'])) {
                $this->errorResponse('Course ID is required', 400);
                return;
            }

            $studentIds = $data['student_ids'];
            $courseId = $data['course_id'];

            $enrolledCount = 0;
            foreach ($studentIds as $studentId) {
                try {
                    // Check if already enrolled
                    $existing = $this->db->querySingle("
                        SELECT id FROM enrollments
                        WHERE student_id = ? AND course_id = ? AND company_id = ?
                    ", [$studentId, $courseId, $this->user['company_id']]);

                    if (!$existing) {
                        $enrollmentData = [
                            'company_id' => $this->user['company_id'],
                            'student_id' => $studentId,
                            'course_id' => $courseId,
                            'enrollment_date' => date('Y-m-d'),
                            'status' => 'active',
                            'progress_percentage' => 0,
                            'enrolled_by' => $this->user['id'],
                            'created_at' => date('Y-m-d H:i:s')
                        ];

                        $this->db->insert('enrollments', $enrollmentData);
                        $enrolledCount++;
                    }
                } catch (Exception $e) {
                    // Continue with next student
                    continue;
                }
            }

            // Update course enrollment count
            $this->db->query("
                UPDATE courses SET enrollment_count = (
                    SELECT COUNT(*) FROM enrollments WHERE course_id = courses.id
                ) WHERE id = ?
            ", [$courseId]);

            $this->logActivity('bulk_enrollment', 'Bulk student enrollment', null, [
                'course_id' => $courseId,
                'enrolled_count' => $enrolledCount
            ]);

            $this->jsonResponse([
                'success' => true,
                'enrolled_count' => $enrolledCount,
                'message' => "$enrolledCount students enrolled successfully"
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Bulk update course status
     */
    public function bulkUpdateCourses() {
        $this->requirePermission('lms.courses.manage');

        try {
            $data = $this->getJsonInput();

            if (!isset($data['course_ids']) || !is_array($data['course_ids'])) {
                $this->errorResponse('Course IDs are required', 400);
                return;
            }

            if (!isset($data['updates']) || !is_array($data['updates'])) {
                $this->errorResponse('Updates data is required', 400);
                return;
            }

            $courseIds = $data['course_ids'];
            $updates = $data['updates'];

            $updatedCount = 0;
            foreach ($courseIds as $courseId) {
                // Verify course belongs to company
                $course = $this->db->querySingle("
                    SELECT id FROM courses WHERE id = ? AND company_id = ?
                ", [$courseId, $this->user['company_id']]);

                if ($course) {
                    $updates['updated_at'] = date('Y-m-d H:i:s');
                    $this->db->update('courses', $updates, "id = ?", [$courseId]);
                    $updatedCount++;
                }
            }

            $this->logActivity('courses_bulk_updated', 'Courses bulk updated', null, [
                'count' => $updatedCount,
                'updates' => $updates
            ]);

            $this->jsonResponse([
                'success' => true,
                'updated_count' => $updatedCount,
                'message' => "$updatedCount courses updated successfully"
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // UTILITY ENDPOINTS
    // ============================================================================

    /**
     * Get course status options
     */
    public function getCourseStatus() {
        $this->requirePermission('lms.view');

        try {
            $status = $this->lms->getCourseStatus();
            $this->jsonResponse($status);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Export courses data
     */
    public function exportCourses() {
        $this->requirePermission('lms.courses.view');

        try {
            $filters = $_GET;

            $courses = $this->lms->getCourses($filters);

            // Generate CSV
            $filename = 'courses_export_' . date('Y-m-d_H-i-s') . '.csv';
            $csvContent = $this->generateCoursesCSV($courses);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');

            echo $csvContent;
            exit;

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // PRIVATE HELPER METHODS
    // ============================================================================

    private function generateCourseCode($courseName) {
        $year = date('Y');

        // Create base code from course name
        $baseCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $courseName), 0, 3));

        // Get the last course code for this year
        $lastCourse = $this->db->querySingle("
            SELECT course_code FROM courses
            WHERE course_code LIKE ? AND company_id = ?
            ORDER BY id DESC LIMIT 1
        ", ["$baseCode-$year%", $this->user['company_id']]);

        if ($lastCourse) {
            $lastNumber = (int)substr($lastCourse['course_code'], -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s%03d', $baseCode, $year, $nextNumber);
    }

    private function generateCoursesCSV($courses) {
        $headers = [
            'Course Name',
            'Course Code',
            'Category',
            'Instructor',
            'Status',
            'Enrollments',
            'Completion Rate',
            'Rating',
            'Price',
            'Start Date',
            'End Date'
        ];

        $csv = implode(',', array_map(function($header) {
            return '"' . str_replace('"', '""', $header) . '"';
        }, $headers)) . "\n";

        foreach ($courses as $course) {
            $row = [
                $course['course_name'] ?? '',
                $course['course_code'] ?? '',
                $course['category_name'] ?? '',
                ($course['instructor_first'] ?? '') . ' ' . ($course['instructor_last'] ?? ''),
                $course['status'] ?? '',
                $course['enrollment_count'] ?? '',
                $course['completion_rate'] ? $course['completion_rate'] . '%' : '',
                $course['student_rating'] ? number_format($course['student_rating'], 1) : '',
                $course['price'] ? '$' . number_format($course['price'], 2) : '',
                $course['start_date'] ?? '',
                $course['end_date'] ?? ''
            ];

            $csv .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        return $csv;
    }

    private function logActivity($action, $description, $entityId = null, $details = null) {
        try {
            $this->db->insert('audit_log', [
                'company_id' => $this->user['company_id'],
                'user_id' => $this->user['id'],
                'action' => $action,
                'description' => $description,
                'entity_type' => 'course',
                'entity_id' => $entityId,
                'details' => json_encode($details),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
}
