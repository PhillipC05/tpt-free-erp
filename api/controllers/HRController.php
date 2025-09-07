<?php

namespace TPT\ERP\Api\Controllers;

use TPT\ERP\Core\Response;
use TPT\ERP\Core\Request;
use TPT\ERP\Core\Database;
use TPT\ERP\Modules\HR;

/**
 * HR API Controller
 * Handles all human resources-related API endpoints
 */
class HRController extends BaseController
{
    private $hr;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->hr = new HR();
        $this->db = Database::getInstance();
    }

    /**
     * Get HR dashboard overview
     * GET /api/hr/overview
     */
    public function getOverview()
    {
        try {
            $this->requirePermission('hr.view');

            $data = [
                'workforce_overview' => $this->hr->getWorkforceOverview(),
                'attendance_summary' => $this->hr->getAttendanceSummary(),
                'payroll_status' => $this->hr->getPayrollStatus(),
                'recruitment_pipeline' => $this->hr->getRecruitmentPipeline(),
                'performance_metrics' => $this->hr->getPerformanceMetrics(),
                'training_overview' => $this->hr->getTrainingOverview(),
                'hr_analytics' => $this->hr->getHRAnalytics(),
                'recent_activities' => $this->hr->getRecentHRActivities()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get employees with filtering
     * GET /api/hr/employees
     */
    public function getEmployees()
    {
        try {
            $this->requirePermission('hr.employees.view');

            $filters = [
                'department' => $_GET['department'] ?? null,
                'status' => $_GET['status'] ?? null,
                'manager' => $_GET['manager'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $employees = $this->hr->getEmployees($filters);
            $total = $this->getEmployeesCount($filters);

            Response::json([
                'employees' => $employees,
                'pagination' => [
                    'page' => $filters['page'],
                    'limit' => $filters['limit'],
                    'total' => $total,
                    'pages' => ceil($total / $filters['limit'])
                ]
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create new employee
     * POST /api/hr/employees
     */
    public function createEmployee()
    {
        try {
            $this->requirePermission('hr.employees.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['first_name', 'last_name', 'email', 'hire_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                Response::error('Employee with this email already exists', 400);
                return;
            }

            $employeeData = [
                'employee_id' => $data['employee_id'] ?? $this->generateEmployeeId(),
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => trim($data['email']),
                'phone' => $data['phone'] ?? '',
                'hire_date' => $data['hire_date'],
                'department_id' => $data['department_id'] ?? null,
                'position_id' => $data['position_id'] ?? null,
                'manager_id' => $data['manager_id'] ?? null,
                'salary' => (float)($data['salary'] ?? 0),
                'employment_status' => $data['employment_status'] ?? 'active',
                'address' => $data['address'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'postal_code' => $data['postal_code'] ?? '',
                'country' => $data['country'] ?? '',
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? '',
                'emergency_contact_name' => $data['emergency_contact_name'] ?? '',
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? '',
                'notes' => $data['notes'] ?? '',
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_date' => date('Y-m-d H:i:s')
            ];

            $employeeId = $this->db->insert('employees', $employeeData);

            // Log the creation
            $this->logActivity('employee_created', 'employees', $employeeId, "Employee '{$employeeData['first_name']} {$employeeData['last_name']}' created");

            Response::json([
                'success' => true,
                'employee_id' => $employeeId,
                'employee_number' => $employeeData['employee_id'],
                'message' => 'Employee created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Update employee
     * PUT /api/hr/employees/{id}
     */
    public function updateEmployee($id)
    {
        try {
            $this->requirePermission('hr.employees.update');

            $data = Request::getJsonBody();

            // Check if employee exists and belongs to company
            $employee = $this->getEmployeeById($id);
            if (!$employee) {
                Response::error('Employee not found', 404);
                return;
            }

            // Check email uniqueness if changed
            if (isset($data['email']) && $data['email'] !== $employee['email'] && $this->emailExists($data['email'])) {
                Response::error('Employee with this email already exists', 400);
                return;
            }

            $updateData = [];
            $allowedFields = [
                'first_name', 'last_name', 'email', 'phone', 'department_id', 'position_id',
                'manager_id', 'salary', 'employment_status', 'address', 'city', 'state',
                'postal_code', 'country', 'date_of_birth', 'gender', 'emergency_contact_name',
                'emergency_contact_phone', 'notes'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_by'] = $this->user['id'];
                $updateData['updated_date'] = date('Y-m-d H:i:s');

                $this->db->update('employees', $updateData, ['id' => $id]);

                // Log the update
                $this->logActivity('employee_updated', 'employees', $id, "Employee '{$employee['first_name']} {$employee['last_name']}' updated");
            }

            Response::json([
                'success' => true,
                'message' => 'Employee updated successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Delete employee
     * DELETE /api/hr/employees/{id}
     */
    public function deleteEmployee($id)
    {
        try {
            $this->requirePermission('hr.employees.delete');

            $employee = $this->getEmployeeById($id);
            if (!$employee) {
                Response::error('Employee not found', 404);
                return;
            }

            // Check if employee has active records
            if ($this->hasActiveRecords($id)) {
                Response::error('Cannot delete employee with active records', 400);
                return;
            }

            $this->db->update('employees', [
                'employment_status' => 'terminated',
                'termination_date' => date('Y-m-d'),
                'updated_by' => $this->user['id'],
                'updated_date' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            // Log the termination
            $this->logActivity('employee_terminated', 'employees', $id, "Employee '{$employee['first_name']} {$employee['last_name']}' terminated");

            Response::json([
                'success' => true,
                'message' => 'Employee terminated successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get departments
     * GET /api/hr/departments
     */
    public function getDepartments()
    {
        try {
            $this->requirePermission('hr.employees.view');

            $departments = $this->hr->getDepartments();

            Response::json(['departments' => $departments]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create department
     * POST /api/hr/departments
     */
    public function createDepartment()
    {
        try {
            $this->requirePermission('hr.employees.create');

            $data = Request::getJsonBody();

            // Validate required fields
            if (empty($data['department_name'])) {
                Response::error('Department name is required', 400);
                return;
            }

            $departmentData = [
                'department_name' => trim($data['department_name']),
                'description' => $data['description'] ?? '',
                'manager_id' => $data['manager_id'] ?? null,
                'budget' => (float)($data['budget'] ?? 0),
                'location' => $data['location'] ?? '',
                'is_active' => true,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_date' => date('Y-m-d H:i:s')
            ];

            $departmentId = $this->db->insert('departments', $departmentData);

            // Log the creation
            $this->logActivity('department_created', 'departments', $departmentId, "Department '{$departmentData['department_name']}' created");

            Response::json([
                'success' => true,
                'department_id' => $departmentId,
                'message' => 'Department created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get attendance records
     * GET /api/hr/attendance
     */
    public function getAttendance()
    {
        try {
            $this->requirePermission('hr.attendance.view');

            $filters = [
                'employee_id' => $_GET['employee_id'] ?? null,
                'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
                'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
                'status' => $_GET['status'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $attendance = $this->hr->getAttendanceRecords();
            $analytics = $this->hr->getAttendanceAnalytics();

            Response::json([
                'attendance' => $attendance,
                'analytics' => $analytics,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Record attendance
     * POST /api/hr/attendance
     */
    public function recordAttendance()
    {
        try {
            $this->requirePermission('hr.attendance.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['employee_id', 'record_date', 'check_in_time'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if employee exists
            $employee = $this->getEmployeeById($data['employee_id']);
            if (!$employee) {
                Response::error('Employee not found', 400);
                return;
            }

            // Check if attendance already exists for this date
            if ($this->attendanceExists($data['employee_id'], $data['record_date'])) {
                Response::error('Attendance already recorded for this date', 400);
                return;
            }

            $attendanceData = [
                'employee_id' => $data['employee_id'],
                'record_date' => $data['record_date'],
                'check_in_time' => $data['check_in_time'],
                'check_out_time' => $data['check_out_time'] ?? null,
                'hours_worked' => $this->calculateHoursWorked($data['check_in_time'], $data['check_out_time']),
                'status' => $this->determineAttendanceStatus($data),
                'is_late' => $this->isLate($data['check_in_time']),
                'is_early_departure' => $this->isEarlyDeparture($data['check_out_time']),
                'notes' => $data['notes'] ?? '',
                'company_id' => $this->user['company_id'],
                'recorded_by' => $this->user['id'],
                'recorded_at' => date('Y-m-d H:i:s')
            ];

            $attendanceId = $this->db->insert('attendance_records', $attendanceData);

            // Log the attendance record
            $this->logActivity('attendance_recorded', 'attendance_records', $attendanceId, "Attendance recorded for employee {$employee['first_name']} {$employee['last_name']}");

            Response::json([
                'success' => true,
                'attendance_id' => $attendanceId,
                'message' => 'Attendance recorded successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get payroll information
     * GET /api/hr/payroll
     */
    public function getPayroll()
    {
        try {
            $this->requirePermission('hr.payroll.view');

            $data = [
                'payroll_runs' => $this->hr->getPayrollRuns(),
                'salary_structures' => $this->hr->getSalaryStructures(),
                'analytics' => $this->hr->getPayrollAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Process payroll
     * POST /api/hr/payroll/process
     */
    public function processPayroll()
    {
        try {
            $this->requirePermission('hr.payroll.create');

            $data = Request::getJsonBody();

            // Validate required fields
            if (empty($data['payroll_period_start']) || empty($data['payroll_period_end'])) {
                Response::error('Payroll period is required', 400);
                return;
            }

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Create payroll run
                $payrollRunData = [
                    'payroll_period_start' => $data['payroll_period_start'],
                    'payroll_period_end' => $data['payroll_period_end'],
                    'payroll_date' => date('Y-m-d'),
                    'status' => 'processing',
                    'total_gross_pay' => 0,
                    'total_deductions' => 0,
                    'total_net_pay' => 0,
                    'employee_count' => 0,
                    'company_id' => $this->user['company_id'],
                    'created_by' => $this->user['id'],
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $payrollRunId = $this->db->insert('payroll_runs', $payrollRunData);

                // Get active employees
                $employees = $this->db->query(
                    "SELECT * FROM employees WHERE company_id = ? AND employment_status = 'active'",
                    [$this->user['company_id']]
                );

                $totalGross = 0;
                $totalDeductions = 0;
                $totalNet = 0;
                $employeeCount = 0;

                // Process each employee
                foreach ($employees as $employee) {
                    $grossPay = $this->calculateGrossPay($employee, $data['payroll_period_start'], $data['payroll_period_end']);
                    $deductions = $this->calculateDeductions($employee['id']);
                    $netPay = $grossPay - $deductions['total'];

                    $payrollEntryData = [
                        'payroll_run_id' => $payrollRunId,
                        'employee_id' => $employee['id'],
                        'gross_pay' => $grossPay,
                        'federal_tax' => $deductions['federal_tax'],
                        'state_tax' => $deductions['state_tax'],
                        'social_security' => $deductions['social_security'],
                        'medicare' => $deductions['medicare'],
                        'other_deductions' => $deductions['other_deductions'],
                        'total_deductions' => $deductions['total'],
                        'net_pay' => $netPay,
                        'company_id' => $this->user['company_id']
                    ];

                    $this->db->insert('payroll_entries', $payrollEntryData);

                    $totalGross += $grossPay;
                    $totalDeductions += $deductions['total'];
                    $totalNet += $netPay;
                    $employeeCount++;
                }

                // Update payroll run totals
                $this->db->update('payroll_runs', [
                    'total_gross_pay' => $totalGross,
                    'total_deductions' => $totalDeductions,
                    'total_net_pay' => $totalNet,
                    'employee_count' => $employeeCount,
                    'status' => 'processed',
                    'processed_at' => date('Y-m-d H:i:s')
                ], ['id' => $payrollRunId]);

                $this->db->commit();

                // Log the payroll processing
                $this->logActivity('payroll_processed', 'payroll_runs', $payrollRunId, "Payroll processed for {$employeeCount} employees");

                Response::json([
                    'success' => true,
                    'payroll_run_id' => $payrollRunId,
                    'message' => "Payroll processed successfully for {$employeeCount} employees"
                ], 201);
            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get performance reviews
     * GET /api/hr/performance
     */
    public function getPerformance()
    {
        try {
            $this->requirePermission('hr.performance.view');

            $data = [
                'performance_reviews' => $this->hr->getPerformanceReviews(),
                'goal_setting' => $this->hr->getGoalSetting(),
                'analytics' => $this->hr->getPerformanceAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create performance review
     * POST /api/hr/performance/reviews
     */
    public function createPerformanceReview()
    {
        try {
            $this->requirePermission('hr.performance.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['employee_id', 'review_period', 'overall_rating'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if employee exists
            $employee = $this->getEmployeeById($data['employee_id']);
            if (!$employee) {
                Response::error('Employee not found', 400);
                return;
            }

            $reviewData = [
                'employee_id' => $data['employee_id'],
                'reviewer_id' => $data['reviewer_id'] ?? $this->user['id'],
                'review_period' => $data['review_period'],
                'review_date' => date('Y-m-d'),
                'overall_rating' => (int)$data['overall_rating'],
                'goals_achieved_percentage' => (int)($data['goals_achieved_percentage'] ?? 0),
                'strengths' => $data['strengths'] ?? '',
                'areas_for_improvement' => $data['areas_for_improvement'] ?? '',
                'development_plan' => $data['development_plan'] ?? '',
                'reviewer_comments' => $data['reviewer_comments'] ?? '',
                'employee_comments' => $data['employee_comments'] ?? '',
                'next_review_date' => $data['next_review_date'] ?? null,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $reviewId = $this->db->insert('performance_reviews', $reviewData);

            // Update employee's performance rating
            $this->db->update('employees', [
                'performance_rating' => $data['overall_rating'],
                'last_performance_review' => date('Y-m-d'),
                'updated_by' => $this->user['id'],
                'updated_date' => date('Y-m-d H:i:s')
            ], ['id' => $data['employee_id']]);

            // Log the performance review
            $this->logActivity('performance_review_created', 'performance_reviews', $reviewId, "Performance review created for {$employee['first_name']} {$employee['last_name']}");

            Response::json([
                'success' => true,
                'review_id' => $reviewId,
                'message' => 'Performance review created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get leave management
     * GET /api/hr/leave
     */
    public function getLeave()
    {
        try {
            $this->requirePermission('hr.attendance.view');

            $leave = $this->hr->getLeaveManagement();

            Response::json(['leave' => $leave]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Submit leave request
     * POST /api/hr/leave
     */
    public function submitLeaveRequest()
    {
        try {
            $this->requirePermission('hr.attendance.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['employee_id', 'leave_type_id', 'start_date', 'end_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if employee exists
            $employee = $this->getEmployeeById($data['employee_id']);
            if (!$employee) {
                Response::error('Employee not found', 400);
                return;
            }

            // Calculate days requested
            $startDate = new DateTime($data['start_date']);
            $endDate = new DateTime($data['end_date']);
            $daysRequested = $startDate->diff($endDate)->days + 1;

            $leaveData = [
                'employee_id' => $data['employee_id'],
                'leave_type_id' => $data['leave_type_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'days_requested' => $daysRequested,
                'reason' => $data['reason'] ?? '',
                'status' => 'pending',
                'approved_by' => null,
                'approval_date' => null,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $leaveId = $this->db->insert('leave_management', $leaveData);

            // Log the leave request
            $this->logActivity('leave_requested', 'leave_management', $leaveId, "Leave request submitted by {$employee['first_name']} {$employee['last_name']}");

            Response::json([
                'success' => true,
                'leave_id' => $leaveId,
                'message' => 'Leave request submitted successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Approve leave request
     * PUT /api/hr/leave/{id}/approve
     */
    public function approveLeaveRequest($id)
    {
        try {
            $this->requirePermission('hr.attendance.update');

            $leave = $this->getLeaveById($id);
            if (!$leave) {
                Response::error('Leave request not found', 404);
                return;
            }

            $this->db->update('leave_management', [
                'status' => 'approved',
                'approved_by' => $this->user['id'],
                'approval_date' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            // Log the approval
            $this->logActivity('leave_approved', 'leave_management', $id, "Leave request approved");

            Response::json([
                'success' => true,
                'message' => 'Leave request approved successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get recruitment data
     * GET /api/hr/recruitment
     */
    public function getRecruitment()
    {
        try {
            $this->requirePermission('hr.recruitment.view');

            $data = [
                'job_postings' => $this->hr->getJobPostings(),
                'applicant_tracking' => $this->hr->getApplicantTracking(),
                'analytics' => $this->hr->getRecruitmentAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create job posting
     * POST /api/hr/recruitment/jobs
     */
    public function createJobPosting()
    {
        try {
            $this->requirePermission('hr.recruitment.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['job_title', 'department_id', 'description'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $jobData = [
                'job_title' => trim($data['job_title']),
                'department_id' => $data['department_id'],
                'position_id' => $data['position_id'] ?? null,
                'description' => $data['description'],
                'requirements' => $data['requirements'] ?? '',
                'salary_range_min' => (float)($data['salary_range_min'] ?? 0),
                'salary_range_max' => (float)($data['salary_range_max'] ?? 0),
                'employment_type' => $data['employment_type'] ?? 'full_time',
                'location' => $data['location'] ?? '',
                'posting_date' => date('Y-m-d'),
                'closing_date' => $data['closing_date'] ?? null,
                'status' => 'open',
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $jobId = $this->db->insert('job_postings', $jobData);

            // Log the job posting
            $this->logActivity('job_posted', 'job_postings', $jobId, "Job posting created: {$jobData['job_title']}");

            Response::json([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Job posting created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get training data
     * GET /api/hr/training
     */
    public function getTraining()
    {
        try {
            $this->requirePermission('hr.training.view');

            $data = [
                'training_programs' => $this->hr->getTrainingPrograms(),
                'course_catalog' => $this->hr->getCourseCatalog(),
                'analytics' => $this->hr->getTrainingAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get HR analytics
     * GET /api/hr/analytics
     */
    public function getAnalytics()
    {
        try {
            $this->requirePermission('hr.analytics.view');

            $data = [
                'workforce_analytics' => $this->hr->getWorkforceAnalytics(),
                'turnover_analysis' => $this->hr->getTurnoverAnalysis(),
                'compensation_analytics' => $this->hr->getCompensationAnalytics(),
                'productivity_metrics' => $this->hr->getProductivityMetrics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Bulk update employees
     * POST /api/hr/employees/bulk-update
     */
    public function bulkUpdateEmployees()
    {
        try {
            $this->requirePermission('hr.employees.update');

            $data = Request::getJsonBody();

            if (!isset($data['employee_ids']) || !is_array($data['employee_ids'])) {
                Response::error('Employee IDs array is required', 400);
                return;
            }

            if (empty($data['updates'])) {
                Response::error('Updates object is required', 400);
                return;
            }

            $employeeIds = $data['employee_ids'];
            $updates = $data['updates'];

            // Start transaction
            $this->db->beginTransaction();

            try {
                $updateCount = 0;

                foreach ($employeeIds as $employeeId) {
                    $employee = $this->getEmployeeById($employeeId);
                    if (!$employee) continue;

                    $updateData = [];
                    $allowedFields = [
                        'department_id', 'position_id', 'salary', 'employment_status',
                        'manager_id', 'performance_rating', 'engagement_score'
                    ];

                    foreach ($allowedFields as $field) {
                        if (isset($updates[$field])) {
                            $updateData[$field] = $updates[$field];
                        }
                    }

                    if (!empty($updateData)) {
                        $updateData['updated_by'] = $this->user['id'];
                        $updateData['updated_date'] = date('Y-m-d H:i:s');

                        $this->db->update('employees', $updateData, ['id' => $employeeId]);
                        $updateCount++;
                    }
                }

                $this->db->commit();

                // Log bulk update
                $this->logActivity('bulk_employee_update', 'employees', null, "Bulk updated {$updateCount} employees");

                Response::json([
                    'success' => true,
                    'updated_count' => $updateCount,
                    'message' => "{$updateCount} employees updated successfully"
                ]);
            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getEmployeeById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM employees WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function getLeaveById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM leave_management WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function emailExists($email)
    {
        $count = $this->db->queryValue(
            "SELECT COUNT(*) FROM employees WHERE email = ? AND company_id = ?",
            [$email, $this->user['company_id']]
        );
        return $count > 0;
    }

    private function hasActiveRecords($employeeId)
    {
        // Check for active attendance, payroll, etc.
        $attendanceCount = $this->db->queryValue(
            "SELECT COUNT(*) FROM attendance_records WHERE employee_id = ? AND record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            [$employeeId]
        );

        return $attendanceCount > 0;
    }

    private function generateEmployeeId()
    {
        return 'EMP-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    private function attendanceExists($employeeId, $date)
    {
        $count = $this->db->queryValue(
            "SELECT COUNT(*) FROM attendance_records WHERE employee_id = ? AND record_date = ?",
            [$employeeId, $date]
        );
        return $count > 0;
    }

    private function calculateHoursWorked($checkIn, $checkOut)
    {
        if (!$checkOut) return 0;

        $checkInTime = strtotime($checkIn);
        $checkOutTime = strtotime($checkOut);

        return round(($checkOutTime - $checkInTime) / 3600, 2);
    }

    private function determineAttendanceStatus($data)
    {
        if (empty($data['check_in_time'])) {
            return 'absent';
        }

        if ($this->isLate($data['check_in_time'])) {
            return 'late';
        }

        return 'present';
    }

    private function isLate($checkInTime)
    {
        // Assume standard work start time is 9:00 AM
        $standardStart = strtotime('09:00:00');
        $checkIn = strtotime($checkInTime);

        return $checkIn > $standardStart;
    }

    private function isEarlyDeparture($checkOutTime)
    {
        if (!$checkOutTime) return false;

        // Assume standard work end time is 5:00 PM
        $standardEnd = strtotime('17:00:00');
        $checkOut = strtotime($checkOutTime);

        return $checkOut < $standardEnd;
    }

    private function calculateGrossPay($employee, $periodStart, $periodEnd)
    {
        // Simple calculation - in real implementation, this would be more complex
        $daysInPeriod = (strtotime($periodEnd) - strtotime($periodStart)) / (60 * 60 * 24) + 1;
        $workDays = $daysInPeriod * 5 / 7; // Assuming 5 work days per week

        return $employee['salary'] / 26 * $workDays; // Bi-weekly pay period
    }

    private function calculateDeductions($employeeId)
    {
        // Simple tax calculation - in real implementation, this would use tax tables
        $grossPay = $this->calculateGrossPay($this->getEmployeeById($employeeId), date('Y-m-d', strtotime('-14 days')), date('Y-m-d'));

        $federalTax = $grossPay * 0.12; // 12% federal tax
        $stateTax = $grossPay * 0.05; // 5% state tax
        $socialSecurity = $grossPay * 0.062; // 6.2% social security
        $medicare = $grossPay * 0.0145; // 1.45% medicare
        $otherDeductions = $grossPay * 0.03; // 3% other deductions

        return [
            'federal_tax' => round($federalTax, 2),
            'state_tax' => round($stateTax, 2),
            'social_security' => round($socialSecurity, 2),
            'medicare' => round($medicare, 2),
            'other_deductions' => round($otherDeductions, 2),
            'total' => round($federalTax + $stateTax + $socialSecurity + $medicare + $otherDeductions, 2)
        ];
    }

    private function getEmployeesCount($filters)
    {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['department']) {
            $where[] = "department_id = ?";
            $params[] = $filters['department'];
        }

        if ($filters['status']) {
            $where[] = "employment_status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['manager']) {
            $where[] = "manager_id = ?";
            $params[] = $filters['manager'];
        }

        if ($filters['date_from']) {
            $where[] = "hire_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "hire_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM employees WHERE $whereClause", $params);
    }

    private function logActivity($action, $table, $recordId, $description)
    {
        $this->db->insert('hr_activities', [
            'user_id' => $this->user['id'],
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'description' => $description,
            'company_id' => $this->user['company_id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
