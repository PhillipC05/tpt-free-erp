<?php
/**
 * TPT Free ERP - Project Management Module
 * Complete project planning, task management, and resource allocation system
 */

class ProjectManagement extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main project management dashboard
     */
    public function index() {
        $this->requirePermission('projects.view');

        $data = [
            'title' => 'Project Management',
            'project_overview' => $this->getProjectOverview(),
            'active_projects' => $this->getActiveProjects(),
            'project_status' => $this->getProjectStatus(),
            'upcoming_deadlines' => $this->getUpcomingDeadlines(),
            'resource_utilization' => $this->getResourceUtilization(),
            'project_budget' => $this->getProjectBudget(),
            'recent_activities' => $this->getRecentActivities(),
            'project_analytics' => $this->getProjectAnalytics()
        ];

        $this->render('modules/project_management/dashboard', $data);
    }

    /**
     * Project creation and planning
     */
    public function projects() {
        $this->requirePermission('projects.manage');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'manager' => $_GET['manager'] ?? null,
            'department' => $_GET['department'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $projects = $this->getProjects($filters);

        $data = [
            'title' => 'Project Planning',
            'projects' => $projects,
            'filters' => $filters,
            'project_templates' => $this->getProjectTemplates(),
            'project_managers' => $this->getProjectManagers(),
            'departments' => $this->getDepartments(),
            'project_status' => $this->getProjectStatus(),
            'project_priorities' => $this->getProjectPriorities(),
            'project_analytics' => $this->getProjectAnalytics(),
            'resource_forecasting' => $this->getResourceForecasting()
        ];

        $this->render('modules/project_management/projects', $data);
    }

    /**
     * Task management and tracking
     */
    public function tasks() {
        $this->requirePermission('projects.tasks.view');

        $filters = [
            'project' => $_GET['project'] ?? null,
            'status' => $_GET['status'] ?? null,
            'assignee' => $_GET['assignee'] ?? null,
            'priority' => $_GET['priority'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $tasks = $this->getTasks($filters);

        $data = [
            'title' => 'Task Management',
            'tasks' => $tasks,
            'filters' => $filters,
            'task_templates' => $this->getTaskTemplates(),
            'task_status' => $this->getTaskStatus(),
            'task_priorities' => $this->getTaskPriorities(),
            'task_categories' => $this->getTaskCategories(),
            'time_tracking' => $this->getTimeTracking(),
            'task_dependencies' => $this->getTaskDependencies(),
            'task_analytics' => $this->getTaskAnalytics(),
            'productivity_metrics' => $this->getProductivityMetrics()
        ];

        $this->render('modules/project_management/tasks', $data);
    }

    /**
     * Resource allocation
     */
    public function resources() {
        $this->requirePermission('projects.resources.view');

        $data = [
            'title' => 'Resource Allocation',
            'resource_overview' => $this->getResourceOverview(),
            'resource_calendar' => $this->getResourceCalendar(),
            'resource_utilization' => $this->getResourceUtilization(),
            'resource_conflicts' => $this->getResourceConflicts(),
            'resource_forecasting' => $this->getResourceForecasting(),
            'skill_matrix' => $this->getSkillMatrix(),
            'resource_optimization' => $this->getResourceOptimization(),
            'resource_analytics' => $this->getResourceAnalytics(),
            'resource_planning' => $this->getResourcePlanning()
        ];

        $this->render('modules/project_management/resources', $data);
    }

    /**
     * Gantt chart visualization
     */
    public function gantt() {
        $this->requirePermission('projects.gantt.view');

        $data = [
            'title' => 'Gantt Chart',
            'gantt_data' => $this->getGanttData(),
            'project_timeline' => $this->getProjectTimeline(),
            'milestones' => $this->getMilestones(),
            'critical_path' => $this->getCriticalPath(),
            'dependencies' => $this->getDependencies(),
            'resource_allocation' => $this->getResourceAllocation(),
            'progress_tracking' => $this->getProgressTracking(),
            'gantt_analytics' => $this->getGanttAnalytics(),
            'export_options' => $this->getGanttExportOptions()
        ];

        $this->render('modules/project_management/gantt', $data);
    }

    /**
     * Time tracking and billing
     */
    public function timeTracking() {
        $this->requirePermission('projects.time.view');

        $data = [
            'title' => 'Time Tracking & Billing',
            'time_entries' => $this->getTimeEntries(),
            'time_sheets' => $this->getTimeSheets(),
            'billing_rates' => $this->getBillingRates(),
            'time_approval' => $this->getTimeApproval(),
            'project_billing' => $this->getProjectBilling(),
            'time_analytics' => $this->getTimeAnalytics(),
            'productivity_reports' => $this->getProductivityReports(),
            'time_tracking_settings' => $this->getTimeTrackingSettings()
        ];

        $this->render('modules/project_management/time_tracking', $data);
    }

    /**
     * Project templates
     */
    public function templates() {
        $this->requirePermission('projects.templates.view');

        $data = [
            'title' => 'Project Templates',
            'project_templates' => $this->getProjectTemplates(),
            'task_templates' => $this->getTaskTemplates(),
            'workflow_templates' => $this->getWorkflowTemplates(),
            'resource_templates' => $this->getResourceTemplates(),
            'budget_templates' => $this->getBudgetTemplates(),
            'template_usage' => $this->getTemplateUsage(),
            'template_analytics' => $this->getTemplateAnalytics(),
            'template_management' => $this->getTemplateManagement()
        ];

        $this->render('modules/project_management/templates', $data);
    }

    /**
     * Project analytics and reporting
     */
    public function analytics() {
        $this->requirePermission('projects.analytics.view');

        $data = [
            'title' => 'Project Analytics',
            'project_performance' => $this->getProjectPerformance(),
            'resource_analytics' => $this->getResourceAnalytics(),
            'budget_analytics' => $this->getBudgetAnalytics(),
            'timeline_analytics' => $this->getTimelineAnalytics(),
            'quality_analytics' => $this->getQualityAnalytics(),
            'risk_analytics' => $this->getRiskAnalytics(),
            'productivity_analytics' => $this->getProductivityAnalytics(),
            'predictive_analytics' => $this->getPredictiveAnalytics()
        ];

        $this->render('modules/project_management/analytics', $data);
    }

    /**
     * Collaboration tools
     */
    public function collaboration() {
        $this->requirePermission('projects.collaboration.view');

        $data = [
            'title' => 'Project Collaboration',
            'team_communication' => $this->getTeamCommunication(),
            'document_sharing' => $this->getDocumentSharing(),
            'meeting_management' => $this->getMeetingManagement(),
            'feedback_system' => $this->getFeedbackSystem(),
            'collaboration_analytics' => $this->getCollaborationAnalytics(),
            'notification_center' => $this->getNotificationCenter(),
            'collaboration_settings' => $this->getCollaborationSettings(),
            'integration_tools' => $this->getIntegrationTools()
        ];

        $this->render('modules/project_management/collaboration', $data);
    }

    /**
     * Risk management
     */
    public function riskManagement() {
        $this->requirePermission('projects.risk.view');

        $data = [
            'title' => 'Risk Management',
            'risk_register' => $this->getRiskRegister(),
            'risk_assessment' => $this->getRiskAssessment(),
            'mitigation_plans' => $this->getMitigationPlans(),
            'risk_monitoring' => $this->getRiskMonitoring(),
            'contingency_planning' => $this->getContingencyPlanning(),
            'risk_reporting' => $this->getRiskReporting(),
            'risk_analytics' => $this->getRiskAnalytics(),
            'risk_templates' => $this->getRiskTemplates()
        ];

        $this->render('modules/project_management/risk_management', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getProjectOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT p.id) as total_projects,
                COUNT(CASE WHEN p.status = 'active' THEN 1 END) as active_projects,
                COUNT(CASE WHEN p.status = 'completed' THEN 1 END) as completed_projects,
                COUNT(CASE WHEN p.status = 'on_hold' THEN 1 END) as on_hold_projects,
                COUNT(CASE WHEN p.end_date < CURDATE() AND p.status != 'completed' THEN 1 END) as overdue_projects,
                SUM(p.budget) as total_budget,
                AVG(p.progress_percentage) as avg_progress,
                COUNT(DISTINCT t.id) as total_tasks,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks
            FROM projects p
            LEFT JOIN tasks t ON p.id = t.project_id
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getActiveProjects() {
        return $this->db->query("
            SELECT
                p.*,
                u.first_name as manager_first,
                u.last_name as manager_last,
                p.progress_percentage,
                p.budget,
                p.start_date,
                p.end_date,
                TIMESTAMPDIFF(DAY, CURDATE(), p.end_date) as days_remaining,
                COUNT(t.id) as total_tasks,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                ROUND((COUNT(CASE WHEN t.status = 'completed' THEN 1 END) / NULLIF(COUNT(t.id), 0)) * 100, 2) as task_completion_rate
            FROM projects p
            LEFT JOIN users u ON p.project_manager_id = u.id
            LEFT JOIN tasks t ON p.id = t.project_id
            WHERE p.company_id = ? AND p.status = 'active'
            GROUP BY p.id, u.first_name, u.last_name
            ORDER BY p.priority DESC, p.end_date ASC
        ", [$this->user['company_id']]);
    }

    private function getProjectStatus() {
        return $this->db->query("
            SELECT
                status,
                COUNT(*) as project_count,
                SUM(budget) as total_budget,
                AVG(progress_percentage) as avg_progress,
                COUNT(CASE WHEN end_date < CURDATE() THEN 1 END) as overdue_count
            FROM projects
            WHERE company_id = ?
            GROUP BY status
            ORDER BY project_count DESC
        ", [$this->user['company_id']]);
    }

    private function getUpcomingDeadlines() {
        return $this->db->query("
            SELECT
                p.project_name,
                p.end_date,
                TIMESTAMPDIFF(DAY, CURDATE(), p.end_date) as days_until_deadline,
                p.progress_percentage,
                p.priority,
                u.first_name as manager_first,
                u.last_name as manager_last,
                COUNT(t.id) as pending_tasks
            FROM projects p
            LEFT JOIN users u ON p.project_manager_id = u.id
            LEFT JOIN tasks t ON p.id = t.project_id AND t.status != 'completed'
            WHERE p.company_id = ? AND p.status = 'active' AND p.end_date >= CURDATE() AND p.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            GROUP BY p.id, p.project_name, p.end_date, p.progress_percentage, p.priority, u.first_name, u.last_name
            ORDER BY p.end_date ASC
        ", [$this->user['company_id']]);
    }

    private function getResourceUtilization() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                COUNT(t.id) as assigned_tasks,
                SUM(tt.hours_logged) as total_hours,
                AVG(tt.hours_logged) as avg_hours_per_task,
                COUNT(CASE WHEN t.status = 'in_progress' THEN 1 END) as active_tasks,
                ROUND((COUNT(CASE WHEN t.status = 'in_progress' THEN 1 END) / NULLIF(COUNT(t.id), 0)) * 100, 2) as utilization_rate
            FROM users u
            LEFT JOIN tasks t ON u.id = t.assigned_to
            LEFT JOIN time_tracking tt ON t.id = tt.task_id AND u.id = tt.user_id
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY utilization_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getProjectBudget() {
        return $this->db->query("
            SELECT
                p.project_name,
                p.budget,
                SUM(e.estimated_cost) as estimated_cost,
                SUM(e.actual_cost) as actual_cost,
                ROUND((SUM(e.actual_cost) / NULLIF(p.budget, 0)) * 100, 2) as budget_utilization,
                ROUND((SUM(e.actual_cost) / NULLIF(SUM(e.estimated_cost), 0)) * 100, 2) as cost_variance
            FROM projects p
            LEFT JOIN expenses e ON p.id = e.project_id
            WHERE p.company_id = ?
            GROUP BY p.id, p.project_name, p.budget
            ORDER BY budget_utilization DESC
        ", [$this->user['company_id']]);
    }

    private function getRecentActivities() {
        return $this->db->query("
            SELECT
                pa.*,
                u.first_name as user_first,
                u.last_name as user_last,
                p.project_name,
                pa.activity_type,
                pa.description,
                pa.created_at,
                TIMESTAMPDIFF(MINUTE, pa.created_at, NOW()) as minutes_ago
            FROM project_activities pa
            LEFT JOIN users u ON pa.user_id = u.id
            LEFT JOIN projects p ON pa.project_id = p.id
            WHERE pa.company_id = ?
            ORDER BY pa.created_at DESC
            LIMIT 25
        ", [$this->user['company_id']]);
    }

    private function getProjectAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(p.id) as total_projects,
                AVG(p.progress_percentage) as avg_progress,
                AVG(TIMESTAMPDIFF(DAY, p.start_date, p.end_date)) as avg_duration,
                COUNT(CASE WHEN p.status = 'completed' THEN 1 END) as completed_projects,
                ROUND((COUNT(CASE WHEN p.status = 'completed' THEN 1 END) / NULLIF(COUNT(p.id), 0)) * 100, 2) as completion_rate,
                AVG(p.budget) as avg_budget,
                SUM(p.budget) as total_budget
            FROM projects p
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getProjects($filters) {
        $where = ["p.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "p.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['manager']) {
            $where[] = "p.project_manager_id = ?";
            $params[] = $filters['manager'];
        }

        if ($filters['department']) {
            $where[] = "p.department_id = ?";
            $params[] = $filters['department'];
        }

        if ($filters['date_from']) {
            $where[] = "p.start_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "p.end_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(p.project_name LIKE ? OR p.description LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                p.*,
                u.first_name as manager_first,
                u.last_name as manager_last,
                d.department_name,
                p.progress_percentage,
                p.budget,
                p.start_date,
                p.end_date,
                TIMESTAMPDIFF(DAY, CURDATE(), p.end_date) as days_remaining,
                COUNT(t.id) as total_tasks,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                ROUND((COUNT(CASE WHEN t.status = 'completed' THEN 1 END) / NULLIF(COUNT(t.id), 0)) * 100, 2) as task_completion_rate
            FROM projects p
            LEFT JOIN users u ON p.project_manager_id = u.id
            LEFT JOIN departments d ON p.department_id = d.id
            LEFT JOIN tasks t ON p.id = t.project_id
            WHERE $whereClause
            GROUP BY p.id, u.first_name, u.last_name, d.department_name
            ORDER BY p.priority DESC, p.end_date ASC
        ", $params);
    }

    private function getProjectTemplates() {
        return $this->db->query("
            SELECT * FROM project_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getProjectManagers() {
        return $this->db->query("
            SELECT
                u.id,
                u.first_name,
                u.last_name,
                COUNT(p.id) as active_projects,
                AVG(p.progress_percentage) as avg_project_progress
            FROM users u
            LEFT JOIN projects p ON u.id = p.project_manager_id AND p.status = 'active'
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY active_projects DESC
        ", [$this->user['company_id']]);
    }

    private function getDepartments() {
        return $this->db->query("
            SELECT
                d.*,
                COUNT(p.id) as active_projects,
                SUM(p.budget) as total_budget
            FROM departments d
            LEFT JOIN projects p ON d.id = p.department_id AND p.status = 'active'
            WHERE d.company_id = ?
            GROUP BY d.id
            ORDER BY active_projects DESC
        ", [$this->user['company_id']]);
    }

    private function getProjectPriorities() {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical'
        ];
    }

    private function getResourceForecasting() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(p.end_date, '%Y-%m') as month,
                COUNT(p.id) as projects_ending,
                SUM(p.budget) as budget_at_risk,
                COUNT(DISTINCT p.project_manager_id) as managers_affected,
                AVG(p.progress_percentage) as avg_progress
            FROM projects p
            WHERE p.company_id = ? AND p.status = 'active'
            GROUP BY DATE_FORMAT(p.end_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getTasks($filters) {
        $where = ["t.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['project']) {
            $where[] = "t.project_id = ?";
            $params[] = $filters['project'];
        }

        if ($filters['status']) {
            $where[] = "t.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['assignee']) {
            $where[] = "t.assigned_to = ?";
            $params[] = $filters['assignee'];
        }

        if ($filters['priority']) {
            $where[] = "t.priority = ?";
            $params[] = $filters['priority'];
        }

        if ($filters['date_from']) {
            $where[] = "t.due_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "t.due_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(t.task_name LIKE ? OR t.description LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                t.*,
                p.project_name,
                u.first_name as assignee_first,
                u.last_name as assignee_last,
                t.progress_percentage,
                t.due_date,
                TIMESTAMPDIFF(DAY, CURDATE(), t.due_date) as days_until_due,
                SUM(tt.hours_logged) as total_hours_logged,
                COUNT(td.depends_on_task_id) as dependency_count
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN time_tracking tt ON t.id = tt.task_id
            LEFT JOIN task_dependencies td ON t.id = td.task_id
            WHERE $whereClause
            GROUP BY t.id, p.project_name, u.first_name, u.last_name
            ORDER BY t.priority DESC, t.due_date ASC
        ", $params);
    }

    private function getTaskTemplates() {
        return $this->db->query("
            SELECT * FROM task_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTaskStatus() {
        return [
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'on_hold' => 'On Hold',
            'review' => 'Under Review',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];
    }

    private function getTaskPriorities() {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent'
        ];
    }

    private function getTaskCategories() {
        return $this->db->query("
            SELECT
                tc.*,
                COUNT(t.id) as task_count,
                AVG(t.progress_percentage) as avg_progress
            FROM task_categories tc
            LEFT JOIN tasks t ON tc.id = t.category_id
            WHERE tc.company_id = ?
            GROUP BY tc.id
            ORDER BY task_count DESC
        ", [$this->user['company_id']]);
    }

    private function getTimeTracking() {
        return $this->db->query("
            SELECT
                tt.*,
                t.task_name,
                u.first_name,
                u.last_name,
                tt.start_time,
                tt.end_time,
                TIMESTAMPDIFF(MINUTE, tt.start_time, tt.end_time) as duration_minutes,
                tt.description,
                tt.is_billable
            FROM time_tracking tt
            JOIN tasks t ON tt.task_id = t.id
            JOIN users u ON tt.user_id = u.id
            WHERE tt.company_id = ?
            ORDER BY tt.start_time DESC
        ", [$this->user['company_id']]);
    }

    private function getTaskDependencies() {
        return $this->db->query("
            SELECT
                td.*,
                t1.task_name as dependent_task,
                t2.task_name as dependency_task,
                td.dependency_type,
                t1.status as dependent_status,
                t2.status as dependency_status
            FROM task_dependencies td
            JOIN tasks t1 ON td.task_id = t1.id
            JOIN tasks t2 ON td.depends_on_task_id = t2.id
            WHERE td.company_id = ?
            ORDER BY t1.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getTaskAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(t.id) as total_tasks,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                ROUND((COUNT(CASE WHEN t.status = 'completed' THEN 1 END) / NULLIF(COUNT(t.id), 0)) * 100, 2) as completion_rate,
                COUNT(CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN 1 END) as overdue_tasks,
                AVG(t.progress_percentage) as avg_progress,
                AVG(TIMESTAMPDIFF(DAY, t.created_at, COALESCE(t.completed_at, CURDATE()))) as avg_completion_time
            FROM tasks t
            WHERE t.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getProductivityMetrics() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                COUNT(t.id) as total_tasks,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                ROUND((COUNT(CASE WHEN t.status = 'completed' THEN 1 END) / NULLIF(COUNT(t.id), 0)) * 100, 2) as completion_rate,
                SUM(tt.hours_logged) as total_hours,
                AVG(tt.hours_logged) as avg_hours_per_task,
                COUNT(CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN 1 END) as overdue_tasks
            FROM users u
            LEFT JOIN tasks t ON u.id = t.assigned_to
            LEFT JOIN time_tracking tt ON t.id = tt.task_id AND u.id = tt.user_id
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY completion_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getResourceOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT u.id) as total_resources,
                COUNT(DISTINCT t.id) as total_tasks,
                COUNT(CASE WHEN t.status = 'in_progress' THEN 1 END) as active_tasks,
                AVG(resource_utilization) as avg_utilization,
                COUNT(CASE WHEN resource_utilization > 90 THEN 1 END) as overutilized_resources,
                COUNT(CASE WHEN resource_utilization < 50 THEN 1 END) as underutilized_resources,
                SUM(t.estimated_hours) as total_estimated_hours,
                SUM(tt.hours_logged) as total_logged_hours
            FROM users u
            LEFT JOIN tasks t ON u.id = t.assigned_to
            LEFT JOIN time_tracking tt ON t.id = tt.task_id AND u.id = tt.user_id
            LEFT JOIN resource_utilization ru ON u.id = ru.user_id
            WHERE u.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getResourceCalendar() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                t.task_name,
                t.start_date,
                t.due_date,
                t.estimated_hours,
                SUM(tt.hours_logged) as hours_logged,
                t.status
            FROM users u
            JOIN tasks t ON u.id = t.assigned_to
            LEFT JOIN time_tracking tt ON t.id = tt.task_id AND u.id = tt.user_id
            WHERE u.company_id = ? AND t.status IN ('not_started', 'in_progress')
            GROUP BY u.id, u.first_name, u.last_name, t.id, t.task_name, t.start_date, t.due_date, t.estimated_hours, t.status
            ORDER BY u.last_name, t.start_date
        ", [$this->user['company_id']]);
    }

    private function getResourceConflicts() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                COUNT(t.id) as concurrent_tasks,
                SUM(t.estimated_hours) as total_estimated_hours,
                GROUP_CONCAT(t.task_name SEPARATOR ', ') as task_names,
                MAX(t.due_date) as latest_due_date
            FROM users u
            JOIN tasks t ON u.id = t.assigned_to
            WHERE u.company_id = ? AND t.status = 'in_progress'
            GROUP BY u.id, u.first_name, u.last_name
            HAVING concurrent_tasks > 1
            ORDER BY concurrent_tasks DESC
        ", [$this->user['company_id']]);
    }

    private function getSkillMatrix() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                s.skill_name,
                us.proficiency_level,
                us.years_experience,
                us.certifications,
                COUNT(t.id) as tasks_with_skill
            FROM users u
            JOIN user_skills us ON u.id = us.user_id
            JOIN skills s ON us.skill_id = s.id
            LEFT JOIN task_skills ts ON s.id = ts.skill_id
            LEFT JOIN tasks t ON ts.task_id = t.id AND u.id = t.assigned_to
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name, s.id, s.skill_name, us.proficiency_level, us.years_experience, us.certifications
            ORDER BY s.skill_name, us.proficiency_level DESC
        ", [$this->user['company_id']]);
    }

    private function getResourceOptimization() {
        return $this->db->query("
            SELECT
                ro.*,
                ro.optimization_type,
                ro.current_utilization,
                ro.target_utilization,
                ro.estimated_benefit,
                ro.implementation_cost,
                ro.roi_percentage
            FROM resource_optimization ro
            WHERE ro.company_id = ?
            ORDER BY ro.roi_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getResourceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT u.id) as total_resources,
                AVG(ru.utilization_rate) as avg_utilization,
                COUNT(CASE WHEN ru.utilization_rate > 90 THEN 1 END) as overutilized,
                COUNT(CASE WHEN ru.utilization_rate < 30 THEN 1 END) as underutilized,
                AVG(us.proficiency_level) as avg_skill_level,
                COUNT(DISTINCT s.id) as total_skills,
                AVG(us.years_experience) as avg_experience
            FROM users u
            LEFT JOIN resource_utilization ru ON u.id = ru.user_id
            LEFT JOIN user_skills us ON u.id = us.user_id
            LEFT JOIN skills s ON us.skill_id = s.id
            WHERE u.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getResourcePlanning() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(t.due_date, '%Y-%m') as month,
                COUNT(t.id) as tasks_due,
                SUM(t.estimated_hours) as estimated_hours,
                COUNT(DISTINCT t.assigned_to) as resources_needed,
                AVG(ru.utilization_rate) as avg_utilization
            FROM tasks t
            LEFT JOIN resource_utilization ru ON t.assigned_to = ru.user_id
            WHERE t.company_id = ? AND t.status != 'completed'
            GROUP BY DATE_FORMAT(t.due_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getGanttData() {
        return $this->db->query("
            SELECT
                t.id,
                t.task_name,
                p.project_name,
                t.start_date,
                t.due_date,
                t.progress_percentage,
                t.priority,
                t.status,
                GROUP_CONCAT(td.depends_on_task_id) as dependencies
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            LEFT JOIN task_dependencies td ON t.id = td.task_id
            WHERE t.company_id = ?
            GROUP BY t.id, t.task_name, p.project_name, t.start_date, t.due_date, t.progress_percentage, t.priority, t.status
            ORDER BY t.start_date ASC
        ", [$this->user['company_id']]);
    }

    private function getProjectTimeline() {
        return $this->db->query("
            SELECT
                p.project_name,
                p.start_date,
                p.end_date,
                p.progress_percentage,
                COUNT(t.id) as total_tasks,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                MIN(t.start_date) as first_task_start,
                MAX(t.due_date) as last_task_due
            FROM projects p
            LEFT JOIN tasks t ON p.id = t.project_id
            WHERE p.company_id = ?
            GROUP BY p.id, p.project_name, p.start_date, p.end_date, p.progress_percentage
            ORDER BY p.start_date ASC
        ", [$this->user['company_id']]);
    }

    private function getMilestones() {
        return $this->db->query("
            SELECT
                m.*,
                p.project_name,
                m.milestone_name,
                m.target_date,
                m.actual_date,
                m.status,
                TIMESTAMPDIFF(DAY, CURDATE(), m.target_date) as days_until_target
            FROM milestones m
            JOIN projects p ON m.project_id = p.id
            WHERE m.company_id = ?
            ORDER BY m.target_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCriticalPath() {
        return $this->db->query("
            SELECT
                t.task_name,
                p.project_name,
                t.start_date,
                t.due_date,
                t.estimated_hours,
                t.priority,
                COUNT(td.task_id) as dependent_tasks,
                t.float_time,
                CASE WHEN t.float_time = 0 THEN 'critical' ELSE 'non_critical' END as path_type
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            LEFT JOIN task_dependencies td ON t.id = td.depends_on_task_id
            WHERE t.company_id = ?
            ORDER BY t.float_time ASC, t.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getDependencies() {
        return $this->db->query("
            SELECT
                td.*,
                t1.task_name as from_task,
                t2.task_name as to_task,
                td.dependency_type,
                td.lag_time
            FROM task_dependencies td
            JOIN tasks t1 ON td.depends_on_task_id = t1.id
            JOIN tasks t2 ON td.task_id = t2.id
            WHERE td.company_id = ?
            ORDER BY t1.start_date ASC
        ", [$this->user['company_id']]);
    }

    private function getResourceAllocation() {
        return $this->db->query("
            SELECT
                t.task_name,
                u.first_name,
                u.last_name,
                t.start_date,
                t.due_date,
                t.estimated_hours,
                SUM(tt.hours_logged) as hours_logged,
                ROUND((SUM(tt.hours_logged) / NULLIF(t.estimated_hours, 0)) * 100, 2) as completion_percentage
            FROM tasks t
            JOIN users u ON t.assigned_to = u.id
            LEFT JOIN time_tracking tt ON t.id = tt.task_id AND u.id = tt.user_id
            WHERE t.company_id = ?
            GROUP BY t.id, t.task_name, u.first_name, u.last_name, t.start_date, t.due_date, t.estimated_hours
            ORDER BY t.start_date ASC
        ", [$this->user['company_id']]);
    }

    private function getProgressTracking() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(t.updated_at, '%Y-%m-%d') as date,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as tasks_completed,
                COUNT(t.id) as total_tasks,
                ROUND((COUNT(CASE WHEN t.status = 'completed' THEN 1 END) / NULLIF(COUNT(t.id), 0)) * 100, 2) as completion_rate,
                SUM(tt.hours_logged) as hours_logged
            FROM tasks t
            LEFT JOIN time_tracking tt ON t.id = tt.task_id
            WHERE t.company_id = ?
            GROUP BY DATE_FORMAT(t.updated_at, '%Y-%m-%d')
            ORDER BY date ASC
        ", [$this->user['company_id']]);
    }

    private function getGanttAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(t.id) as total_tasks,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                COUNT(td.id) as total_dependencies,
                AVG(t.float_time) as avg_float_time,
                COUNT(CASE WHEN t.float_time = 0 THEN 1 END) as critical_path_tasks,
                MAX(t.due_date) as project_end_date,
                MIN(t.start_date) as project_start_date
            FROM tasks t
            LEFT JOIN task_dependencies td ON t.id = td.task_id OR t.id = td.depends_on_task_id
            WHERE t.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getGanttExportOptions() {
        return [
            'pdf' => 'PDF Document',
            'png' => 'PNG Image',
            'svg' => 'SVG Vector',
            'excel' => 'Excel Gantt Data',
            'mpp' => 'Microsoft Project',
            'json' => 'JSON Data'
        ];
    }

    private function getTimeEntries() {
        return $this->db->query("
            SELECT
                tt.*,
                t.task_name,
                p.project_name,
                u.first_name,
                u.last_name,
                tt.start_time,
                tt.end_time,
                TIMESTAMPDIFF(MINUTE, tt.start_time, tt.end_time) as duration_minutes,
                tt.description,
                tt.is_billable
            FROM time_tracking tt
            JOIN tasks t ON tt.task_id = t.id
            JOIN projects p ON t.project_id = p.id
            JOIN users u ON tt.user_id = u.id
            WHERE tt.company_id = ?
            ORDER BY tt.start_time DESC
        ", [$this->user['company_id']]);
    }

    private function getTimeSheets() {
        return $this->db->query("
            SELECT
                ts.*,
                u.first_name,
                u.last_name,
                ts.week_start_date,
                ts.total_hours,
                ts.billable_hours,
                ts.status,
                ts.submitted_at,
                ts.approved_at
            FROM time_sheets ts
            JOIN users u ON ts.user_id = u.id
            WHERE ts.company_id = ?
            ORDER BY ts.week_start_date DESC
        ", [$this->user['company_id']]);
    }

    private function getBillingRates() {
        return $this->db->query("
            SELECT
                br.*,
                u.first_name,
                u.last_name,
                br.hourly_rate,
                br.overtime_rate,
                br.effective_date,
                br.currency
            FROM billing_rates br
            JOIN users u ON br.user_id = u.id
            WHERE br.company_id = ?
            ORDER BY br.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getTimeApproval() {
        return $this->db->query("
            SELECT
                ta.*,
                ts.week_start_date,
                u.first_name,
                u.last_name,
                ta.requested_at,
                ta.approved_at,
                ta.status,
                ta.reviewer_comments
            FROM time_approvals ta
            JOIN time_sheets ts ON ta.time_sheet_id = ts.id
            JOIN users u ON ta.user_id = u.id
            WHERE ta.company_id = ?
            ORDER BY ta.requested_at DESC
        ", [$this->user['company_id']]);
    }

    private function getProjectBilling() {
        return $this->db->query("
            SELECT
                p.project_name,
                SUM(tt.hours_logged * br.hourly_rate) as total_billed,
                SUM(CASE WHEN tt.is_billable = true THEN tt.hours_logged ELSE 0 END) as billable_hours,
                SUM(tt.hours_logged) as total_hours,
                ROUND((SUM(CASE WHEN tt.is_billable = true THEN tt.hours_logged ELSE 0 END) / NULLIF(SUM(tt.hours_logged), 0)) * 100, 2) as billable_percentage,
                AVG(br.hourly_rate) as avg_hourly_rate
            FROM projects p
            LEFT JOIN tasks t ON p.id = t.project_id
            LEFT JOIN time_tracking tt ON t.id = tt.task_id
            LEFT JOIN billing_rates br ON tt.user_id = br.user_id
            WHERE p.company_id = ?
            GROUP BY p.id, p.project_name
            ORDER BY total_billed DESC
        ", [$this->user['company_id']]);
    }

    private function getTimeAnalytics() {
        return $this->db->querySingle("
            SELECT
                SUM(tt.hours_logged) as total_hours_logged,
                SUM(CASE WHEN tt.is_billable = true THEN tt.hours_logged ELSE 0 END) as billable_hours,
                COUNT(DISTINCT tt.user_id) as active_users,
                COUNT(DISTINCT tt.task_id) as active_tasks,
                AVG(tt.hours_logged) as avg_hours_per_entry,
                AVG(br.hourly_rate) as avg_hourly_rate,
                SUM(tt.hours_logged * br.hourly_rate) as total_billed_amount
            FROM time_tracking tt
            LEFT JOIN billing_rates br ON tt.user_id = br.user_id
            WHERE tt.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getProductivityReports() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                COUNT(tt.id) as time_entries,
                SUM(tt.hours_logged) as total_hours,
                AVG(tt.hours_logged) as avg_hours_per_entry,
                COUNT(DISTINCT t.id) as tasks_worked,
                ROUND((SUM(tt.hours_logged) / NULLIF(COUNT(DISTINCT t.id), 0)), 2) as avg_hours_per_task,
                SUM(CASE WHEN tt.is_billable = true THEN tt.hours_logged ELSE 0 END) as billable_hours
            FROM users u
            LEFT JOIN time_tracking tt ON u.id = tt.user_id
            LEFT JOIN tasks t ON tt.task_id = t.id
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY total_hours DESC
        ", [$this->user['company_id']]);
    }

    private function getTimeTrackingSettings() {
        return $this->db->querySingle("
            SELECT * FROM time_tracking_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getWorkflowTemplates() {
        return $this->db->query("
            SELECT * FROM workflow_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getResourceTemplates() {
        return $this->db->query("
            SELECT * FROM resource_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBudgetTemplates() {
        return $this->db->query("
            SELECT * FROM budget_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTemplateUsage() {
        return $this->db->query("
