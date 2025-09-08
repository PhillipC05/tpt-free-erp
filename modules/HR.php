<?php
/**
 * TPT Free ERP - Human Resources Module
 * Complete employee management, payroll, attendance, and performance system
 */

class HR extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main HR dashboard
     */
    public function index() {
        $this->requirePermission('hr.view');

        $data = [
            'title' => 'Human Resources',
            'workforce_overview' => $this->getWorkforceOverview(),
            'attendance_summary' => $this->getAttendanceSummary(),
            'payroll_status' => $this->getPayrollStatus(),
            'recruitment_pipeline' => $this->getRecruitmentPipeline(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'training_overview' => $this->getTrainingOverview(),
            'hr_analytics' => $this->getHRAnalytics(),
            'recent_activities' => $this->getRecentHRActivities()
        ];

        $this->render('modules/hr/dashboard', $data);
    }

    /**
     * Employee management
     */
    public function employees() {
        $this->requirePermission('hr.employees.view');

        $filters = [
            'department' => $_GET['department'] ?? null,
            'status' => $_GET['status'] ?? null,
            'manager' => $_GET['manager'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $employees = $this->getEmployees($filters);

        $data = [
            'title' => 'Employee Management',
            'employees' => $employees,
            'filters' => $filters,
            'departments' => $this->getDepartments(),
            'positions' => $this->getPositions(),
            'employee_stats' => $this->getEmployeeStats($filters),
            'employee_templates' => $this->getEmployeeTemplates(),
            'bulk_actions' => $this->getBulkActions(),
            'org_chart' => $this->getOrgChart()
        ];

        $this->render('modules/hr/employees', $data);
    }

    /**
     * Payroll processing
     */
    public function payroll() {
        $this->requirePermission('hr.payroll.view');

        $data = [
            'title' => 'Payroll Processing',
            'payroll_runs' => $this->getPayrollRuns(),
            'salary_structures' => $this->getSalaryStructures(),
            'tax_calculations' => $this->getTaxCalculations(),
            'deductions' => $this->getDeductions(),
            'benefits' => $this->getBenefits(),
            'payroll_reports' => $this->getPayrollReports(),
            'payroll_analytics' => $this->getPayrollAnalytics(),
            'payroll_settings' => $this->getPayrollSettings()
        ];

        $this->render('modules/hr/payroll', $data);
    }

    /**
     * Attendance tracking
     */
    public function attendance() {
        $this->requirePermission('hr.attendance.view');

        $data = [
            'title' => 'Attendance Tracking',
            'attendance_records' => $this->getAttendanceRecords(),
            'time_tracking' => $this->getTimeTracking(),
            'leave_management' => $this->getLeaveManagement(),
            'overtime_tracking' => $this->getOvertimeTracking(),
            'attendance_policies' => $this->getAttendancePolicies(),
            'attendance_reports' => $this->getAttendanceReports(),
            'attendance_analytics' => $this->getAttendanceAnalytics(),
            'biometric_integration' => $this->getBiometricIntegration()
        ];

        $this->render('modules/hr/attendance', $data);
    }

    /**
     * Performance reviews
     */
    public function performance() {
        $this->requirePermission('hr.performance.view');

        $data = [
            'title' => 'Performance Management',
            'performance_reviews' => $this->getPerformanceReviews(),
            'goal_setting' => $this->getGoalSetting(),
            'competency_management' => $this->getCompetencyManagement(),
            'feedback_system' => $this->getFeedbackSystem(),
            'performance_analytics' => $this->getPerformanceAnalytics(),
            'development_plans' => $this->getDevelopmentPlans(),
            'performance_templates' => $this->getPerformanceTemplates(),
            'performance_settings' => $this->getPerformanceSettings()
        ];

        $this->render('modules/hr/performance', $data);
    }

    /**
     * Recruitment management
     */
    public function recruitment() {
        $this->requirePermission('hr.recruitment.view');

        $data = [
            'title' => 'Recruitment Management',
            'job_postings' => $this->getJobPostings(),
            'applicant_tracking' => $this->getApplicantTracking(),
            'interview_scheduling' => $this->getInterviewScheduling(),
            'candidate_evaluation' => $this->getCandidateEvaluation(),
            'offer_management' => $this->getOfferManagement(),
            'recruitment_analytics' => $this->getRecruitmentAnalytics(),
            'recruitment_templates' => $this->getRecruitmentTemplates(),
            'recruitment_settings' => $this->getRecruitmentSettings()
        ];

        $this->render('modules/hr/recruitment', $data);
    }

    /**
     * Benefits administration
     */
    public function benefits() {
        $this->requirePermission('hr.benefits.view');

        $data = [
            'title' => 'Benefits Administration',
            'benefit_plans' => $this->getBenefitPlans(),
            'enrollment_management' => $this->getEnrollmentManagement(),
            'claims_processing' => $this->getClaimsProcessing(),
            'benefit_providers' => $this->getBenefitProviders(),
            'benefit_analytics' => $this->getBenefitAnalytics(),
            'benefit_compliance' => $this->getBenefitCompliance(),
            'benefit_templates' => $this->getBenefitTemplates(),
            'benefit_settings' => $this->getBenefitSettings()
        ];

        $this->render('modules/hr/benefits', $data);
    }

    /**
     * Training and development
     */
    public function training() {
        $this->requirePermission('hr.training.view');

        $data = [
            'title' => 'Training & Development',
            'training_programs' => $this->getTrainingPrograms(),
            'course_catalog' => $this->getCourseCatalog(),
            'learning_paths' => $this->getLearningPaths(),
            'certification_tracking' => $this->getCertificationTracking(),
            'training_analytics' => $this->getTrainingAnalytics(),
            'training_compliance' => $this->getTrainingCompliance(),
            'training_templates' => $this->getTrainingTemplates(),
            'training_settings' => $this->getTrainingSettings()
        ];

        $this->render('modules/hr/training', $data);
    }

    /**
     * HR analytics and reporting
     */
    public function analytics() {
        $this->requirePermission('hr.analytics.view');

        $data = [
            'title' => 'HR Analytics',
            'workforce_analytics' => $this->getWorkforceAnalytics(),
            'turnover_analysis' => $this->getTurnoverAnalysis(),
            'engagement_surveys' => $this->getEngagementSurveys(),
            'diversity_reporting' => $this->getDiversityReporting(),
            'compensation_analytics' => $this->getCompensationAnalytics(),
            'productivity_metrics' => $this->getProductivityMetrics(),
            'hr_dashboards' => $this->getHRDashboards(),
            'predictive_analytics' => $this->getPredictiveHRAnalytics()
        ];

        $this->render('modules/hr/analytics', $data);
    }

    /**
     * Employee self-service
     */
    public function selfService() {
        $this->requirePermission('hr.selfservice.view');

        $data = [
            'title' => 'Employee Self-Service',
            'personal_info' => $this->getPersonalInfo(),
            'payroll_info' => $this->getPayrollInfo(),
            'benefits_info' => $this->getBenefitsInfo(),
            'time_off_requests' => $this->getTimeOffRequests(),
            'performance_reviews' => $this->getEmployeePerformanceReviews(),
            'training_enrollment' => $this->getTrainingEnrollment(),
            'document_center' => $this->getDocumentCenter(),
            'help_desk' => $this->getHelpDesk()
        ];

        $this->render('modules/hr/self_service', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getWorkforceOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN employment_status = 'active' THEN 1 END) as active_employees,
                COUNT(CASE WHEN employment_status = 'inactive' THEN 1 END) as inactive_employees,
                COUNT(DISTINCT department) as departments,
                AVG(salary) as avg_salary,
                COUNT(CASE WHEN hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_hires,
                COUNT(CASE WHEN termination_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as terminations,
                ROUND(AVG(DATEDIFF(CURDATE(), hire_date) / 365.25), 1) as avg_tenure_years
            FROM employees
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAttendanceSummary() {
        return $this->db->querySingle("
            SELECT
                COUNT(ar.id) as total_records,
                COUNT(CASE WHEN ar.status = 'present' THEN 1 END) as present_count,
                COUNT(CASE WHEN ar.status = 'absent' THEN 1 END) as absent_count,
                COUNT(CASE WHEN ar.status = 'late' THEN 1 END) as late_count,
                ROUND(AVG(ar.hours_worked), 2) as avg_hours_worked,
                COUNT(CASE WHEN ar.check_in_time > ar.scheduled_start THEN 1 END) as late_arrivals,
                ROUND((COUNT(CASE WHEN ar.status = 'present' THEN 1 END) / NULLIF(COUNT(ar.id), 0)) * 100, 2) as attendance_rate
            FROM attendance_records ar
            WHERE ar.company_id = ? AND ar.record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);
    }

    private function getPayrollStatus() {
        return $this->db->querySingle("
            SELECT
                COUNT(pr.id) as total_payrolls,
                COUNT(CASE WHEN pr.status = 'processed' THEN 1 END) as processed_payrolls,
                COUNT(CASE WHEN pr.status = 'pending' THEN 1 END) as pending_payrolls,
                SUM(pr.total_gross_pay) as total_gross_pay,
                SUM(pr.total_deductions) as total_deductions,
                SUM(pr.total_net_pay) as total_net_pay,
                MAX(pr.payroll_date) as last_payroll_date
            FROM payroll_runs pr
            WHERE pr.company_id = ? AND pr.payroll_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ", [$this->user['company_id']]);
    }

    private function getRecruitmentPipeline() {
        return $this->db->query("
            SELECT
                'openings' as stage,
                COUNT(jp.id) as count,
                COUNT(jp.id) as total
            FROM job_postings jp
            WHERE jp.company_id = ? AND jp.status = 'open'

            UNION ALL

            SELECT
                'applications' as stage,
                COUNT(ja.id) as count,
                COUNT(DISTINCT jp.id) as total
            FROM job_applications ja
            JOIN job_postings jp ON ja.job_posting_id = jp.id
            WHERE jp.company_id = ?

            UNION ALL

            SELECT
                'interviews' as stage,
                COUNT(i.id) as count,
                COUNT(DISTINCT ja.id) as total
            FROM interviews i
            JOIN job_applications ja ON i.job_application_id = ja.id
            JOIN job_postings jp ON ja.job_posting_id = jp.id
            WHERE jp.company_id = ?

            UNION ALL

            SELECT
                'offers' as stage,
                COUNT(jo.id) as count,
                COUNT(DISTINCT ja.id) as total
            FROM job_offers jo
            JOIN job_applications ja ON jo.job_application_id = ja.id
            JOIN job_postings jp ON ja.job_posting_id = jp.id
            WHERE jp.company_id = ?
        ", [
            $this->user['company_id'],
            $this->user['company_id'],
            $this->user['company_id'],
            $this->user['company_id']
        ]);
    }

    private function getPerformanceMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(pr.id) as total_reviews,
                AVG(pr.overall_rating) as avg_rating,
                COUNT(CASE WHEN pr.overall_rating >= 4 THEN 1 END) as high_performers,
                COUNT(CASE WHEN pr.overall_rating < 3 THEN 1 END) as low_performers,
                COUNT(CASE WHEN pr.review_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as recent_reviews,
                ROUND(AVG(pr.goals_achieved_percentage), 2) as avg_goals_achieved
            FROM performance_reviews pr
            WHERE pr.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTrainingOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(tp.id) as total_programs,
                COUNT(te.id) as total_enrollments,
                COUNT(CASE WHEN te.completion_status = 'completed' THEN 1 END) as completed_trainings,
                COUNT(CASE WHEN te.completion_status = 'in_progress' THEN 1 END) as in_progress_trainings,
                ROUND((COUNT(CASE WHEN te.completion_status = 'completed' THEN 1 END) / NULLIF(COUNT(te.id), 0)) * 100, 2) as completion_rate,
                AVG(te.progress_percentage) as avg_progress,
                COUNT(DISTINCT te.employee_id) as trained_employees
            FROM training_programs tp
            LEFT JOIN training_enrollments te ON tp.id = te.program_id
            WHERE tp.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getHRAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(e.id) as total_employees,
                ROUND(AVG(DATEDIFF(CURDATE(), e.hire_date) / 365.25), 1) as avg_tenure,
                COUNT(CASE WHEN e.termination_date IS NOT NULL THEN 1 END) as total_turnovers,
                ROUND((COUNT(CASE WHEN e.termination_date IS NOT NULL THEN 1 END) / NULLIF(COUNT(e.id), 0)) * 100, 2) as turnover_rate,
                AVG(e.salary) as avg_salary,
                COUNT(DISTINCT e.department) as departments,
                COUNT(CASE WHEN e.performance_rating >= 4 THEN 1 END) as high_performers,
                ROUND(AVG(e.engagement_score), 2) as avg_engagement
            FROM employees e
            WHERE e.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRecentHRActivities() {
        return $this->db->query("
            SELECT
                hra.*,
                u.first_name as user_first,
                u.last_name as user_last,
                hra.activity_type,
                hra.description,
                hra.related_record_type,
                hra.related_record_id,
                TIMESTAMPDIFF(MINUTE, hra.created_at, NOW()) as minutes_ago
            FROM hr_activities hra
            LEFT JOIN users u ON hra.user_id = u.id
            WHERE hra.company_id = ?
            ORDER BY hra.created_at DESC
            LIMIT 25
        ", [$this->user['company_id']]);
    }

    private function getEmployees($filters) {
        $where = ["e.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['department']) {
            $where[] = "e.department = ?";
            $params[] = $filters['department'];
        }

        if ($filters['status']) {
            $where[] = "e.employment_status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['manager']) {
            $where[] = "e.manager_id = ?";
            $params[] = $filters['manager'];
        }

        if ($filters['date_from']) {
            $where[] = "e.hire_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "e.hire_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ? OR e.employee_id LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                e.*,
                d.department_name,
                p.position_title,
                m.first_name as manager_first,
                m.last_name as manager_last,
                e.salary,
                e.hire_date,
                TIMESTAMPDIFF(YEAR, e.hire_date, CURDATE()) as years_of_service,
                e.performance_rating,
                e.engagement_score
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN employees m ON e.manager_id = m.id
            WHERE $whereClause
            ORDER BY e.last_name ASC, e.first_name ASC
        ", $params);
    }

    private function getDepartments() {
        return $this->db->query("
            SELECT
                d.*,
                COUNT(e.id) as employee_count,
                AVG(e.salary) as avg_salary,
                m.first_name as manager_first,
                m.last_name as manager_last
            FROM departments d
            LEFT JOIN employees e ON d.id = e.department_id
            LEFT JOIN employees m ON d.manager_id = m.id
            WHERE d.company_id = ?
            GROUP BY d.id, m.first_name, m.last_name
            ORDER BY d.department_name ASC
        ", [$this->user['company_id']]);
    }

    private function getPositions() {
        return $this->db->query("
            SELECT
                p.*,
                COUNT(e.id) as employee_count,
                AVG(e.salary) as avg_salary,
                MIN(e.salary) as min_salary,
                MAX(e.salary) as max_salary
            FROM positions p
            LEFT JOIN employees e ON p.id = e.position_id
            WHERE p.company_id = ?
            GROUP BY p.id
            ORDER BY p.position_title ASC
        ", [$this->user['company_id']]);
    }

    private function getEmployeeStats($filters) {
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

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_employees,
                COUNT(CASE WHEN employment_status = 'active' THEN 1 END) as active_employees,
                COUNT(CASE WHEN gender = 'male' THEN 1 END) as male_employees,
                COUNT(CASE WHEN gender = 'female' THEN 1 END) as female_employees,
                AVG(salary) as avg_salary,
                AVG(performance_rating) as avg_performance,
                AVG(engagement_score) as avg_engagement
            FROM employees
            WHERE $whereClause
        ", $params);
    }

    private function getEmployeeTemplates() {
        return $this->db->query("
            SELECT * FROM employee_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBulkActions() {
        return [
            'update_department' => 'Update Department',
            'update_position' => 'Update Position',
            'update_salary' => 'Update Salary',
            'update_status' => 'Update Employment Status',
            'send_notification' => 'Send Notification',
            'export_employees' => 'Export Employee Data',
            'import_employees' => 'Import Employee Data',
            'bulk_performance_review' => 'Bulk Performance Review'
        ];
    }

    private function getOrgChart() {
        return $this->db->query("
            SELECT
                e.id,
                e.first_name,
                e.last_name,
                e.position_title,
                e.department,
                m.id as manager_id,
                m.first_name as manager_first,
                m.last_name as manager_last,
                COUNT(r.id) as direct_reports
            FROM employees e
            LEFT JOIN employees m ON e.manager_id = m.id
            LEFT JOIN employees r ON r.manager_id = e.id
            WHERE e.company_id = ?
            GROUP BY e.id, e.first_name, e.last_name, e.position_title, e.department, m.id, m.first_name, m.last_name
            ORDER BY e.department, e.position_title
        ", [$this->user['company_id']]);
    }

    private function getPayrollRuns() {
        return $this->db->query("
            SELECT
                pr.*,
                COUNT(pre.id) as employee_count,
                SUM(pre.gross_pay) as total_gross,
                SUM(pre.total_deductions) as total_deductions,
                SUM(pre.net_pay) as total_net,
                pr.payroll_date,
                pr.status
            FROM payroll_runs pr
            LEFT JOIN payroll_entries pre ON pr.id = pre.payroll_run_id
            WHERE pr.company_id = ?
            GROUP BY pr.id
            ORDER BY pr.payroll_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSalaryStructures() {
        return $this->db->query("
            SELECT
                ss.*,
                p.position_title,
                COUNT(e.id) as employee_count,
                AVG(e.salary) as avg_salary,
                MIN(e.salary) as min_salary,
                MAX(e.salary) as max_salary
            FROM salary_structures ss
            LEFT JOIN positions p ON ss.position_id = p.id
            LEFT JOIN employees e ON p.id = e.position_id
            WHERE ss.company_id = ?
            GROUP BY ss.id, p.position_title
            ORDER BY ss.grade_level ASC
        ", [$this->user['company_id']]);
    }

    private function getTaxCalculations() {
        return $this->db->query("
            SELECT
                tc.*,
                e.first_name,
                e.last_name,
                tc.gross_pay,
                tc.federal_tax,
                tc.state_tax,
                tc.social_security,
                tc.medicare,
                tc.total_tax,
                tc.net_pay
            FROM tax_calculations tc
            JOIN employees e ON tc.employee_id = e.id
            WHERE tc.company_id = ?
            ORDER BY tc.calculation_date DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getDeductions() {
        return $this->db->query("
            SELECT
                d.*,
                e.first_name,
                e.last_name,
                dt.deduction_type,
                d.amount,
                d.is_mandatory,
                d.effective_date
            FROM deductions d
            JOIN employees e ON d.employee_id = e.id
            JOIN deduction_types dt ON d.deduction_type_id = dt.id
            WHERE d.company_id = ?
            ORDER BY d.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getBenefits() {
        return $this->db->query("
            SELECT
                b.*,
                e.first_name,
                e.last_name,
                bt.benefit_type,
                b.coverage_amount,
                b.employee_contribution,
                b.employer_contribution
            FROM benefits b
            JOIN employees e ON b.employee_id = e.id
            JOIN benefit_types bt ON b.benefit_type_id = bt.id
            WHERE b.company_id = ?
            ORDER BY bt.benefit_type ASC
        ", [$this->user['company_id']]);
    }

    private function getPayrollReports() {
        return $this->db->query("
            SELECT
                pr.*,
                pr.report_type,
                pr.report_period,
                pr.generated_date,
                pr.total_employees,
                pr.total_payroll_cost
            FROM payroll_reports pr
            WHERE pr.company_id = ?
            ORDER BY pr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPayrollAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(pr.id) as total_payrolls,
                SUM(pr.total_gross_pay) as total_gross_pay,
                SUM(pr.total_deductions) as total_deductions,
                SUM(pr.total_net_pay) as total_net_pay,
                AVG(pr.total_gross_pay) as avg_payroll_cost,
                AVG(pr.total_gross_pay / pr.employee_count) as avg_salary,
                MAX(pr.payroll_date) as last_payroll_date
            FROM payroll_runs pr
            WHERE pr.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPayrollSettings() {
        return $this->db->querySingle("
            SELECT * FROM payroll_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAttendanceRecords() {
        return $this->db->query("
            SELECT
                ar.*,
                e.first_name,
                e.last_name,
                ar.record_date,
                ar.check_in_time,
                ar.check_out_time,
                ar.hours_worked,
                ar.status,
                ar.is_late,
                ar.is_early_departure
            FROM attendance_records ar
            JOIN employees e ON ar.employee_id = e.id
            WHERE ar.company_id = ?
            ORDER BY ar.record_date DESC, ar.check_in_time DESC
        ", [$this->user['company_id']]);
    }

    private function getTimeTracking() {
        return $this->db->query("
            SELECT
                tt.*,
                e.first_name,
                e.last_name,
                p.project_name,
                tt.start_time,
                tt.end_time,
                TIMESTAMPDIFF(MINUTE, tt.start_time, tt.end_time) as duration_minutes,
                tt.description,
                tt.is_billable
            FROM time_tracking tt
            JOIN employees e ON tt.employee_id = e.id
            LEFT JOIN projects p ON tt.project_id = p.id
            WHERE tt.company_id = ?
            ORDER BY tt.start_time DESC
        ", [$this->user['company_id']]);
    }

    private function getLeaveManagement() {
        return $this->db->query("
            SELECT
                lm.*,
                e.first_name,
                e.last_name,
                lt.leave_type,
                lm.start_date,
                lm.end_date,
                lm.days_requested,
                lm.status,
                lm.approved_by
            FROM leave_management lm
            JOIN employees e ON lm.employee_id = e.id
            JOIN leave_types lt ON lm.leave_type_id = lt.id
            WHERE lm.company_id = ?
            ORDER BY lm.start_date DESC
        ", [$this->user['company_id']]);
    }

    private function getOvertimeTracking() {
        return $this->db->query("
            SELECT
                ot.*,
                e.first_name,
                e.last_name,
                ot.overtime_date,
                ot.hours_worked,
                ot.overtime_rate,
                ot.overtime_pay,
                ot.approved_by
            FROM overtime_tracking ot
            JOIN employees e ON ot.employee_id = e.id
            WHERE ot.company_id = ?
            ORDER BY ot.overtime_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAttendancePolicies() {
        return $this->db->query("
            SELECT * FROM attendance_policies
            WHERE company_id = ? AND is_active = true
            ORDER BY policy_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAttendanceReports() {
        return $this->db->query("
            SELECT
                ar.*,
                ar.report_type,
                ar.report_period,
                ar.generated_date,
                ar.total_employees,
                ar.present_days,
                ar.absent_days
            FROM attendance_reports ar
            WHERE ar.company_id = ?
            ORDER BY ar.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAttendanceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(ar.id) as total_records,
                COUNT(CASE WHEN ar.status = 'present' THEN 1 END) as present_count,
                COUNT(CASE WHEN ar.status = 'absent' THEN 1 END) as absent_count,
                COUNT(CASE WHEN ar.is_late = true THEN 1 END) as late_count,
                ROUND(AVG(ar.hours_worked), 2) as avg_hours_worked,
                ROUND((COUNT(CASE WHEN ar.status = 'present' THEN 1 END) / NULLIF(COUNT(ar.id), 0)) * 100, 2) as attendance_rate,
                COUNT(DISTINCT ar.employee_id) as active_employees
            FROM attendance_records ar
            WHERE ar.company_id = ? AND ar.record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);
    }

    private function getBiometricIntegration() {
        return $this->db->query("
            SELECT * FROM biometric_devices
            WHERE company_id = ? AND is_active = true
            ORDER BY device_name ASC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceReviews() {
        return $this->db->query("
            SELECT
                pr.*,
                e.first_name,
                e.last_name,
                r.first_name as reviewer_first,
                r.last_name as reviewer_last,
                pr.review_period,
                pr.overall_rating,
                pr.goals_achieved_percentage,
                pr.review_date
            FROM performance_reviews pr
            JOIN employees e ON pr.employee_id = e.id
            LEFT JOIN employees r ON pr.reviewer_id = r.id
            WHERE pr.company_id = ?
            ORDER BY pr.review_date DESC
        ", [$this->user['company_id']]);
    }

    private function getGoalSetting() {
        return $this->db->query("
            SELECT
                gs.*,
                e.first_name,
                e.last_name,
                gs.goal_title,
                gs.target_value,
                gs.current_value,
                gs.target_date,
                ROUND((gs.current_value / NULLIF(gs.target_value, 0)) * 100, 2) as progress_percentage,
                gs.status
            FROM goal_setting gs
            JOIN employees e ON gs.employee_id = e.id
            WHERE gs.company_id = ?
            ORDER BY gs.target_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCompetencyManagement() {
        return $this->db->query("
            SELECT
                cm.*,
                e.first_name,
                e.last_name,
                c.competency_name,
                cm.current_level,
                cm.target_level,
                cm.proficiency_percentage
            FROM competency_management cm
            JOIN employees e ON cm.employee_id = e.id
            JOIN competencies c ON cm.competency_id = c.id
            WHERE cm.company_id = ?
            ORDER BY c.competency_name ASC
        ", [$this->user['company_id']]);
    }

    private function getFeedbackSystem() {
        return $this->db->query("
            SELECT
                fs.*,
                e.first_name,
                e.last_name,
                fs.feedback_type,
                fs.feedback_text,
                fs.rating,
                fs.is_anonymous,
                fs.created_at
            FROM feedback_system fs
            JOIN employees e ON fs.employee_id = e.id
            WHERE fs.company_id = ?
            ORDER BY fs.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(pr.id) as total_reviews,
                AVG(pr.overall_rating) as avg_rating,
                COUNT(CASE WHEN pr.overall_rating >= 4 THEN 1 END) as high_performers,
                COUNT(CASE WHEN pr.overall_rating < 3 THEN 1 END) as low_performers,
                ROUND(AVG(pr.goals_achieved_percentage), 2) as avg_goals_achieved,
                COUNT(CASE WHEN pr.review_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as recent_reviews
            FROM performance_reviews pr
            WHERE pr.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getDevelopmentPlans() {
        return $this->db->query("
            SELECT
                dp.*,
                e.first_name,
                e.last_name,
                dp.plan_title,
                dp.target_completion_date,
                dp.progress_percentage,
                dp.status
            FROM development_plans dp
            JOIN employees e ON dp.employee_id = e.id
            WHERE dp.company_id = ?
            ORDER BY dp.target_completion_date ASC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceTemplates() {
        return $this->db->query("
            SELECT * FROM performance_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceSettings() {
        return $this->db->querySingle("
            SELECT * FROM performance_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getJobPostings() {
        return $this->db->query("
            SELECT
                jp.*,
                COUNT(ja.id) as application_count,
                d.department_name,
                p.position_title,
                jp.posting_date,
                jp.closing_date,
                jp.status
            FROM job_postings jp
            LEFT JOIN job_applications ja ON jp.id = ja.job_posting_id
            LEFT JOIN departments d ON jp.department_id = d.id
            LEFT JOIN positions p ON jp.position_id = p.id
            WHERE jp.company_id = ?
            GROUP BY jp.id, d.department_name, p.position_title
            ORDER BY jp.posting_date DESC
        ", [$this->user['company_id']]);
    }

    private function getApplicantTracking() {
        return $this->db->query("
            SELECT
                ja.*,
                jp.job_title,
                jp.department,
                ja.application_date,
                ja.status,
                ja.current_stage,
                COUNT(i.id) as interview_count
            FROM job_applications ja
            JOIN job_postings jp ON ja.job_posting_id = jp.id
            LEFT JOIN interviews i ON ja.id = i.job_application_id
            WHERE ja.company_id = ?
            GROUP BY ja.id, jp.job_title, jp.department
            ORDER BY ja.application_date DESC
        ", [$this->user['company_id']]);
    }

    private function getInterviewScheduling() {
        return $this->db->query("
            SELECT
                i.*,
                ja.first_name,
                ja.last_name,
                jp.job_title,
                i.interview_date,
                i.interview_time,
                i.interview_type,
                i.status,
                i.feedback
            FROM interviews i
            JOIN job_applications ja ON i.job_application_id = ja.id
            JOIN job_postings jp ON ja.job_posting_id = jp.id
            WHERE i.company_id = ?
            ORDER BY i.interview_date ASC, i.interview_time ASC
        ", [$this->user['company_id']]);
    }

    private function getCandidateEvaluation() {
        return $this->db->query("
            SELECT
                ce.*,
                ja.first_name,
                ja.last_name,
                jp.job_title,
                ce.skill_rating,
                ce.experience_rating,
                ce.cultural_fit_rating,
                ce.overall_rating,
                ce.recommendation
            FROM candidate_evaluations ce
            JOIN job_applications ja ON ce.job_application_id = ja.id
            JOIN job_postings jp ON ja.job_posting_id = jp.id
            WHERE ce.company_id = ?
            ORDER BY ce.evaluation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getOfferManagement() {
        return $this->db->query("
            SELECT
                jo.*,
                ja.first_name,
                ja.last_name,
                jp.job_title,
                jo.salary_offered,
                jo.start_date,
                jo.offer_status,
                jo.response_date
            FROM job_offers jo
            JOIN job_applications ja ON jo.job_application_id = ja.id
            JOIN job_postings jp ON ja.job_posting_id = jp.id
            WHERE jo.company_id = ?
            ORDER BY jo.created_date DESC
        ", [$this->user['company_id']]);
    }

    private function getRecruitmentAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(jp.id) as total_postings,
                COUNT(ja.id) as total_applications,
                ROUND(AVG(ja.application_count), 2) as avg_applications_per_posting,
                COUNT(i.id) as total_interviews,
                COUNT(jo.id) as total_offers,
                COUNT(CASE WHEN jo.offer_status = 'accepted' THEN 1 END) as accepted_offers,
                ROUND((COUNT(CASE WHEN jo.offer_status = 'accepted' THEN 1 END) / NULLIF(COUNT(jo.id), 0)) * 100, 2) as offer_acceptance_rate,
                AVG(TIMESTAMPDIFF(DAY, jp.posting_date, jp.closing_date)) as avg_time_to_fill
            FROM job_postings jp
            LEFT JOIN job_applications ja ON jp.id = ja.job_posting_id
            LEFT JOIN interviews i ON ja.id = i.job_application_id
            LEFT JOIN job_offers jo ON ja.id = jo.job_application_id
            WHERE jp.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRecruitmentTemplates() {
        return $this->db->query("
            SELECT * FROM recruitment_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getRecruitmentSettings() {
        return $this->db->querySingle("
            SELECT * FROM recruitment_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBenefitPlans() {
        return $this->db->query("
            SELECT
                bp.*,
                COUNT(be.id) as enrolled_employees,
                bp.plan_name,
                bp.coverage_type,
                bp.monthly_premium,
                bp.deductible_amount
            FROM benefit_plans bp
            LEFT JOIN benefit_enrollments be ON bp.id = be.plan_id
            WHERE bp.company_id = ?
            GROUP BY bp.id
            ORDER BY bp.plan_name ASC
        ", [$this->user['company_id']]);
    }

    private function getEnrollmentManagement() {
        return $this->db->query("
            SELECT
                be.*,
                e.first_name,
                e.last_name,
                bp.plan_name,
                be.enrollment_date,
                be.effective_date,
                be.status,
                be.employee_contribution,
                be.employer_contribution
            FROM benefit_enrollments be
            JOIN employees e ON be.employee_id = e.id
            JOIN benefit_plans bp ON be.plan_id = bp.id
            WHERE be.company_id = ?
            ORDER BY be.enrollment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getClaimsProcessing() {
        return $this->db->query("
            SELECT
                cp.*,
                e.first_name,
                e.last_name,
                bp.plan_name,
                cp.claim_amount,
                cp.approved_amount,
                cp.claim_date,
                cp.status,
                cp.processing_date
            FROM claims_processing cp
            JOIN employees e ON cp.employee_id = e.id
            JOIN benefit_plans bp ON cp.plan_id = bp.id
            WHERE cp.company_id = ?
            ORDER BY cp.claim_date DESC
        ", [$this->user['company_id']]);
    }

    private function getBenefitProviders() {
        return $this->db->query("
            SELECT
                bp.*,
                COUNT(bep.id) as plans_count,
                bp.provider_name,
                bp.contact_person,
                bp.contract_start_date,
                bp.contract_end_date
            FROM benefit_providers bp
            LEFT JOIN benefit_plans bep ON bp.id = bep.provider_id
            WHERE bp.company_id = ?
            GROUP BY bp.id
            ORDER BY bp.provider_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBenefitAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(bp.id) as total_plans,
                COUNT(be.id) as total_enrollments,
                SUM(bp.monthly_premium) as total_monthly_cost,
                AVG(bp.monthly_premium) as avg_premium,
                COUNT(cp.id) as total_claims,
                SUM(cp.claim_amount) as total_claim_amount,
                SUM(cp.approved_amount) as total_approved_amount,
                ROUND((SUM(cp.approved_amount) / NULLIF(SUM(cp.claim_amount), 0)) * 100, 2) as approval_rate
            FROM benefit_plans bp
            LEFT JOIN benefit_enrollments be ON bp.id = be.plan_id
            LEFT JOIN claims_processing cp ON bp.id = cp.plan_id
            WHERE bp.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBenefitCompliance() {
        return $this->db->query("
            SELECT
                bc.*,
                bc.compliance_type,
                bc.due_date,
                bc.status,
                bc.last_review_date,
                TIMESTAMPDIFF(DAY, CURDATE(), bc.due_date) as days_until_due
            FROM benefit_compliance bc
            WHERE bc.company_id = ?
            ORDER BY bc.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getBenefitTemplates() {
        return $this->db->query("
            SELECT * FROM benefit_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBenefitSettings() {
        return $this->db->querySingle("
            SELECT * FROM benefit_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTrainingPrograms() {
        return $this->db->query("
            SELECT
                tp.*,
                COUNT(te.id) as enrolled_count,
                COUNT(CASE WHEN te.completion_status = 'completed' THEN 1 END) as completed_count,
                ROUND((COUNT(CASE WHEN te.completion_status = 'completed' THEN 1 END) / NULLIF(COUNT(te.id), 0)) * 100, 2) as completion_rate,
                tp.program_name,
                tp.duration_hours,
                tp.start_date,
                tp.end_date
            FROM training_programs tp
            LEFT JOIN training_enrollments te ON tp.id = te.program_id
            WHERE tp.company_id = ?
            GROUP BY tp.id
            ORDER BY tp.start_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCourseCatalog() {
        return $this->db->query("
            SELECT
                cc.*,
                COUNT(te.id) as enrollment_count,
                AVG(te.progress_percentage) as avg_progress,
                cc.course_name,
                cc.duration_hours,
                cc.difficulty_level,
                cc.is_mandatory
            FROM course_catalog cc
            LEFT JOIN training_enrollments te ON cc.id = te.course_id
            WHERE cc.company_id = ?
            GROUP BY cc.id
            ORDER BY cc.course_name ASC
        ", [$this->user['company_id']]);
    }

    private function getLearningPaths() {
        return $this->db->query("
            SELECT
                lp.*,
                COUNT(lpc.id) as course_count,
                COUNT(te.id) as enrollment_count,
                lp.path_name,
                lp.target_completion_months,
                lp.is_mandatory
            FROM learning_paths lp
            LEFT JOIN learning_path_courses lpc ON lp.id = lpc.path_id
            LEFT JOIN training_enrollments te ON lp.id = te.learning_path_id
            WHERE lp.company_id = ?
            GROUP BY lp.id
            ORDER BY lp.path_name ASC
        ", [$this->user['company_id']]);
    }

    private function getCertificationTracking() {
        return $this->db->query("
            SELECT
                ct.*,
                e.first_name,
                e.last_name,
                c.certification_name,
                ct.issue_date,
                ct.expiry_date,
                ct.status,
                TIMESTAMPDIFF(DAY, CURDATE(), ct.expiry_date) as days_until_expiry
            FROM certification_tracking ct
            JOIN employees e ON ct.employee_id = e.id
            JOIN certifications c ON ct.certification_id = c.id
            WHERE ct.company_id = ?
            ORDER BY ct.expiry_date ASC
        ", [$this->user['company_id']]);
    }

    private function getTrainingAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(tp.id) as total_programs,
                COUNT(te.id) as total_enrollments,
                COUNT(CASE WHEN te.completion_status = 'completed' THEN 1 END) as completed_trainings,
                ROUND((COUNT(CASE WHEN te.completion_status = 'completed' THEN 1 END) / NULLIF(COUNT(te.id), 0)) * 100, 2) as completion_rate,
                AVG(te.progress_percentage) as avg_progress,
                COUNT(DISTINCT te.employee_id) as trained_employees,
                AVG(tp.duration_hours) as avg_program_duration
            FROM training_programs tp
            LEFT JOIN training_enrollments te ON tp.id = te.program_id
            WHERE tp.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTrainingCompliance() {
        return $this->db->query("
            SELECT
                tc.*,
                e.first_name,
                e.last_name,
                tc.compliance_type,
                tc.due_date,
                tc.status,
                tc.last_completed_date,
                TIMESTAMPDIFF(DAY, CURDATE(), tc.due_date) as days_until_due
            FROM training_compliance tc
            JOIN employees e ON tc.employee_id = e.id
            WHERE tc.company_id = ?
            ORDER BY tc.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getTrainingTemplates() {
        return $this->db->query("
            SELECT * FROM training_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTrainingSettings() {
        return $this->db->querySingle("
            SELECT * FROM training_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getWorkforceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(e.id) as total_employees,
                COUNT(CASE WHEN e.employment_status = 'active' THEN 1 END) as active_employees,
                ROUND(AVG(DATEDIFF(CURDATE(), e.hire_date) / 365.25), 1) as avg_tenure,
                COUNT(CASE WHEN e.termination_date IS NOT NULL THEN 1 END) as total_turnovers,
                ROUND((COUNT(CASE WHEN e.termination_date IS NOT NULL THEN 1 END) / NULLIF(COUNT(e.id), 0)) * 100, 2) as turnover_rate,
                AVG(e.salary) as avg_salary,
                COUNT(DISTINCT e.department) as departments,
                COUNT(CASE WHEN e.performance_rating >= 4 THEN 1 END) as high_performers,
                ROUND(AVG(e.engagement_score), 2) as avg_engagement
            FROM employees e
            WHERE e.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTurnoverAnalysis() {
        return $this->db->query("
            SELECT
                YEAR(termination_date) as year,
                MONTH(termination_date) as month,
                COUNT(*) as terminations,
                AVG(DATEDIFF(termination_date, hire_date) / 365.25) as avg_tenure_at_termination,
                d.department_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.company_id = ? AND e.termination_date IS NOT NULL
            GROUP BY YEAR(termination_date), MONTH(termination_date), d.department_name
            ORDER BY year DESC, month DESC
        ", [$this->user['company_id']]);
    }

    private function getEngagementSurveys() {
        return $this->db->query("
            SELECT
                es.*,
                COUNT(esr.id) as responses_count,
                AVG(esr.overall_satisfaction) as avg_satisfaction,
                es.survey_title,
                es.created_date,
                es.status
            FROM engagement_surveys es
            LEFT JOIN engagement_survey_responses esr ON es.id = esr.survey_id
            WHERE es.company_id = ?
            GROUP BY es.id
            ORDER BY es.created_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDiversityReporting() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN gender = 'male' THEN 1 END) as male_count,
                COUNT(CASE WHEN gender = 'female' THEN 1 END) as female_count,
                COUNT(CASE WHEN gender = 'other' THEN 1 END) as other_gender_count,
                ROUND((COUNT(CASE WHEN gender = 'female' THEN 1 END) / NULLIF(COUNT(*), 0)) * 100, 2) as female_percentage,
                COUNT(DISTINCT ethnicity) as ethnicities_count,
                AVG(age) as avg_age,
                COUNT(CASE WHEN age < 30 THEN 1 END) as under_30,
                COUNT(CASE WHEN age BETWEEN 30 AND 50 THEN 1 END) as age_30_50,
                COUNT(CASE WHEN age > 50 THEN 1 END) as over_50
            FROM employees
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCompensationAnalytics() {
        return $this->db->query("
            SELECT
                d.department_name,
                COUNT(e.id) as employee_count,
                AVG(e.salary) as avg_salary,
                MIN(e.salary) as min_salary,
                MAX(e.salary) as max_salary,
                ROUND(STDDEV(e.salary), 2) as salary_std_dev
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.company_id = ?
            GROUP BY d.id, d.department_name
            ORDER BY avg_salary DESC
        ", [$this->user['company_id']]);
    }

    private function getProductivityMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(ar.id) as total_attendance_records,
                ROUND(AVG(ar.hours_worked), 2) as avg_hours_worked,
                ROUND((COUNT(CASE WHEN ar.status = 'present' THEN 1 END) / NULLIF(COUNT(ar.id), 0)) * 100, 2) as attendance_rate,
                COUNT(CASE WHEN ar.is_late = true THEN 1 END) as late_instances,
                COUNT(tt.id) as total_time_entries,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, tt.start_time, tt.end_time) / 60), 2) as avg_hours_logged,
                COUNT(CASE WHEN pr.overall_rating >= 4 THEN 1 END) as high_performance_reviews
            FROM attendance_records ar
            LEFT JOIN time_tracking tt ON ar.employee_id = tt.employee_id
            LEFT JOIN performance_reviews pr ON ar.employee_id = pr.employee_id
            WHERE ar.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getHRDashboards() {
        return $this->db->query("
            SELECT * FROM hr_dashboards
            WHERE company_id = ? AND is_active = true
            ORDER BY dashboard_name ASC
        ", [$this->user['company_id']]);
    }

    private function getPredictiveHRAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN e.engagement_score < 3 THEN 1 END) as low_engagement_count,
                COUNT(CASE WHEN e.performance_rating < 3 THEN 1 END) as low_performance_count,
                COUNT(CASE WHEN e.termination_date IS NOT NULL THEN 1 END) as turnover_count,
                ROUND(AVG(e.engagement_score), 2) as avg_engagement,
                ROUND(AVG(e.performance_rating), 2) as avg_performance,
                COUNT(CASE WHEN e.hire_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as new_hires_90_days
            FROM employees e
            WHERE e.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPersonalInfo() {
        return $this->db->querySingle("
            SELECT
                e.first_name,
                e.last_name,
                e.email,
                e.phone,
                e.address,
                e.date_of_birth,
                e.hire_date,
                e.position_title,
                e.department,
                e.salary,
                e.employee_id
            FROM employees e
            WHERE e.id = ? AND e.company_id = ?
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getPayrollInfo() {
        return $this->db->query("
            SELECT
                pr.payroll_date,
                pr.gross_pay,
                pr.total_deductions,
                pr.net_pay,
                pr.payroll_period
            FROM payroll_entries pr
            WHERE pr.employee_id = ? AND pr.company_id = ?
            ORDER BY pr.payroll_date DESC
            LIMIT 12
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getBenefitsInfo() {
        return $this->db->query("
            SELECT
                be.*,
                bp.plan_name,
                bp.coverage_type,
                bp.monthly_premium,
                be.employee_contribution,
                be.employer_contribution
            FROM benefit_enrollments be
            JOIN benefit_plans bp ON be.plan_id = bp.id
            WHERE be.employee_id = ? AND be.company_id = ?
            ORDER BY bp.plan_name ASC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getTimeOffRequests() {
        return $this->db->query("
            SELECT
                lm.*,
                lt.leave_type,
                lm.start_date,
                lm.end_date,
                lm.days_requested,
                lm.status,
                lm.approved_by
            FROM leave_management lm
            JOIN leave_types lt ON lm.leave_type_id = lt.id
            WHERE lm.employee_id = ? AND lm.company_id = ?
            ORDER BY lm.start_date DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getEmployeePerformanceReviews() {
        return $this->db->query("
            SELECT
                pr.*,
                r.first_name as reviewer_first,
                r.last_name as reviewer_last,
                pr.review_period,
                pr.overall_rating,
                pr.goals_achieved_percentage,
                pr.review_date
            FROM performance_reviews pr
            LEFT JOIN employees r ON pr.reviewer_id = r.id
            WHERE pr.employee_id = ? AND pr.company_id = ?
            ORDER BY pr.review_date DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getTrainingEnrollment() {
        return $this->db->query("
            SELECT
                te.*,
                tp.program_name,
                tp.duration_hours,
                te.enrollment_date,
                te.completion_status,
                te.progress_percentage,
                te.completion_date
            FROM training_enrollments te
            JOIN training_programs tp ON te.program_id = tp.id
            WHERE te.employee_id = ? AND te.company_id = ?
            ORDER BY te.enrollment_date DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getDocumentCenter() {
        return $this->db->query("
            SELECT
                ed.*,
                ed.document_name,
                ed.document_type,
                ed.upload_date,
                ed.file_size,
                ed.is_confidential
            FROM employee_documents ed
            WHERE ed.employee_id = ? AND ed.company_id = ?
            ORDER BY ed.upload_date DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getHelpDesk() {
        return $this->db->query("
            SELECT
                hd.*,
                hd.ticket_subject,
                hd.ticket_description,
                hd.status,
                hd.priority,
                hd.created_date,
                hd.last_updated
            FROM help_desk_tickets hd
            WHERE hd.employee_id = ? AND hd.company_id = ?
            ORDER BY hd.created_date DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }
}
