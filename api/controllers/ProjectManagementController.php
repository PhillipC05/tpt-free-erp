<?php

namespace TPT\ERP\Api\Controllers;

use TPT\ERP\Core\Response;
use TPT\ERP\Core\Request;
use TPT\ERP\Core\Database;
use TPT\ERP\Modules\ProjectManagement;

/**
 * Project Management API Controller
 * Handles all project management-related API endpoints
 */
class ProjectManagementController extends BaseController
{
    private $projectManagement;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->projectManagement = new ProjectManagement();
        $this->db = Database::getInstance();
    }

    /**
     * Get project management dashboard overview
     * GET /api/project-management/overview
     */
    public function getOverview()
    {
        try {
            $this->requirePermission('projects.view');

            $data = [
                'project_overview' => $this->projectManagement->getProjectOverview(),
                'active_projects' => $this->projectManagement->getActiveProjects(),
                'project_status' => $this->projectManagement->getProjectStatus(),
                'upcoming_deadlines' => $this->projectManagement->getUpcomingDeadlines(),
                'resource_utilization' => $this->projectManagement->getResourceUtilization(),
                'project_budget' => $this->projectManagement->getProjectBudget(),
                'recent_activities' => $this->projectManagement->getRecentActivities(),
                'project_analytics' => $this->projectManagement->getProjectAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get projects with filtering
     * GET /api/project-management/projects
     */
    public function getProjects()
    {
        try {
            $this->requirePermission('projects.view');

            $filters = [
                'status' => $_GET['status'] ?? null,
                'manager' => $_GET['manager'] ?? null,
                'department' => $_GET['department'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $projects = $this->projectManagement->getProjects($filters);
            $total = $this->getProjectsCount($filters);

            Response::json([
                'projects' => $projects,
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
     * Create new project
     * POST /api/project-management/projects
     */
    public function createProject()
    {
        try {
            $this->requirePermission('projects.manage');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['project_name', 'start_date', 'end_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $projectData = [
                'project_name' => trim($data['project_name']),
                'description' => $data['description'] ?? '',
                'department_id' => $data['department_id'] ?? null,
                'project_manager_id' => $data['project_manager_id'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'budget' => (float)($data['budget'] ?? 0),
                'priority' => $data['priority'] ?? 'medium',
                'status' => $data['status'] ?? 'planning',
                'progress_percentage' => 0,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $projectId = $this->db->insert('projects', $projectData);

            // Log the creation
            $this->logActivity('project_created', 'projects', $projectId, "Project '{$projectData['project_name']}' created");

            Response::json([
                'success' => true,
                'project_id' => $projectId,
                'message' => 'Project created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Update project
     * PUT /api/project-management/projects/{id}
     */
    public function updateProject($id)
    {
        try {
            $this->requirePermission('projects.manage');

            $data = Request::getJsonBody();

            // Check if project exists and user has access
            $project = $this->getProjectById($id);
            if (!$project) {
                Response::error('Project not found', 404);
                return;
            }

            $updateData = [];
            $allowedFields = [
                'project_name', 'description', 'department_id', 'project_manager_id',
                'start_date', 'end_date', 'budget', 'priority', 'status', 'progress_percentage'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_by'] = $this->user['id'];
                $updateData['updated_at'] = date('Y-m-d H:i:s');

                $this->db->update('projects', $updateData, ['id' => $id]);

                // Log the update
                $this->logActivity('project_updated', 'projects', $id, "Project '{$project['project_name']}' updated");

                Response::json([
                    'success' => true,
                    'message' => 'Project updated successfully'
                ]);
            } else {
                Response::json([
                    'success' => true,
                    'message' => 'No changes made'
                ]);
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get tasks with filtering
     * GET /api/project-management/tasks
     */
    public function getTasks()
    {
        try {
            $this->requirePermission('projects.tasks.view');

            $filters = [
                'project' => $_GET['project'] ?? null,
                'status' => $_GET['status'] ?? null,
                'assignee' => $_GET['assignee'] ?? null,
                'priority' => $_GET['priority'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $tasks = $this->projectManagement->getTasks($filters);
            $total = $this->getTasksCount($filters);

            Response::json([
                'tasks' => $tasks,
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
     * Create new task
     * POST /api/project-management/tasks
     */
    public function createTask()
    {
        try {
            $this->requirePermission('projects.tasks.manage');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['task_name', 'project_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $taskData = [
                'task_name' => trim($data['task_name']),
                'description' => $data['description'] ?? '',
                'project_id' => $data['project_id'],
                'assigned_to' => $data['assigned_to'] ?? null,
                'parent_task_id' => $data['parent_task_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'estimated_hours' => (float)($data['estimated_hours'] ?? 0),
                'priority' => $data['priority'] ?? 'medium',
                'status' => $data['status'] ?? 'not_started',
                'progress_percentage' => 0,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $taskId = $this->db->insert('tasks', $taskData);

            // Log the creation
            $this->logActivity('task_created', 'tasks', $taskId, "Task '{$taskData['task_name']}' created");

            Response::json([
                'success' => true,
                'task_id' => $taskId,
                'message' => 'Task created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Update task
     * PUT /api/project-management/tasks/{id}
     */
    public function updateTask($id)
    {
        try {
            $this->requirePermission('projects.tasks.manage');

            $data = Request::getJsonBody();

            // Check if task exists and user has access
            $task = $this->getTaskById($id);
            if (!$task) {
                Response::error('Task not found', 404);
                return;
            }

            $updateData = [];
            $allowedFields = [
                'task_name', 'description', 'assigned_to', 'parent_task_id', 'category_id',
                'start_date', 'due_date', 'estimated_hours', 'priority', 'status', 'progress_percentage'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_by'] = $this->user['id'];
                $updateData['updated_at'] = date('Y-m-d H:i:s');

                $this->db->update('tasks', $updateData, ['id' => $id]);

                // Log the update
                $this->logActivity('task_updated', 'tasks', $id, "Task '{$task['task_name']}' updated");

                Response::json([
                    'success' => true,
                    'message' => 'Task updated successfully'
                ]);
            } else {
                Response::json([
                    'success' => true,
                    'message' => 'No changes made'
                ]);
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Start time tracking for task
     * POST /api/project-management/tasks/{id}/start-timer
     */
    public function startTimeTracking($id)
    {
        try {
            $this->requirePermission('projects.time.track');

            // Check if task exists
            $task = $this->getTaskById($id);
            if (!$task) {
                Response::error('Task not found', 404);
                return;
            }

            // Check if user already has an active timer
            $activeTimer = $this->db->queryOne(
                "SELECT * FROM time_tracking WHERE user_id = ? AND end_time IS NULL",
                [$this->user['id']]
            );

            if ($activeTimer) {
                Response::error('You already have an active timer. Please stop it first.', 400);
                return;
            }

            $timerData = [
                'task_id' => $id,
                'user_id' => $this->user['id'],
                'start_time' => date('Y-m-d H:i:s'),
                'description' => '',
                'is_billable' => true,
                'company_id' => $this->user['company_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $timerId = $this->db->insert('time_tracking', $timerData);

            Response::json([
                'success' => true,
                'timer_id' => $timerId,
                'message' => 'Time tracking started'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Stop time tracking for task
     * POST /api/project-management/tasks/{id}/stop-timer
     */
    public function stopTimeTracking($id)
    {
        try {
            $this->requirePermission('projects.time.track');

            // Find active timer for this task and user
            $activeTimer = $this->db->queryOne(
                "SELECT * FROM time_tracking WHERE task_id = ? AND user_id = ? AND end_time IS NULL",
                [$id, $this->user['id']]
            );

            if (!$activeTimer) {
                Response::error('No active timer found for this task', 404);
                return;
            }

            $endTime = date('Y-m-d H:i:s');
            $hoursLogged = (strtotime($endTime) - strtotime($activeTimer['start_time'])) / 3600;

            $this->db->update('time_tracking', [
                'end_time' => $endTime,
                'hours_logged' => round($hoursLogged, 2),
                'updated_at' => $endTime
            ], ['id' => $activeTimer['id']]);

            Response::json([
                'success' => true,
                'hours_logged' => round($hoursLogged, 2),
                'message' => 'Time tracking stopped'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get time entries
     * GET /api/project-management/time-entries
     */
    public function getTimeEntries()
    {
        try {
            $this->requirePermission('projects.time.view');

            $filters = [
                'user' => $_GET['user'] ?? null,
                'task' => $_GET['task'] ?? null,
                'project' => $_GET['project'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $timeEntries = $this->projectManagement->getTimeEntries();
            $total = $this->getTimeEntriesCount($filters);

            Response::json([
                'time_entries' => $timeEntries,
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
     * Get Gantt chart data
     * GET /api/project-management/gantt
     */
    public function getGanttData()
    {
        try {
            $this->requirePermission('projects.gantt.view');

            $data = [
                'gantt_data' => $this->projectManagement->getGanttData(),
                'project_timeline' => $this->projectManagement->getProjectTimeline(),
                'milestones' => $this->projectManagement->getMilestones(),
                'critical_path' => $this->projectManagement->getCriticalPath(),
                'dependencies' => $this->projectManagement->getDependencies(),
                'resource_allocation' => $this->projectManagement->getResourceAllocation(),
                'progress_tracking' => $this->projectManagement->getProgressTracking(),
                'gantt_analytics' => $this->projectManagement->getGanttAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get resource utilization
     * GET /api/project-management/resources/utilization
     */
    public function getResourceUtilization()
    {
        try {
            $this->requirePermission('projects.resources.view');

            $data = [
                'resource_overview' => $this->projectManagement->getResourceOverview(),
                'resource_calendar' => $this->projectManagement->getResourceCalendar(),
                'resource_utilization' => $this->projectManagement->getResourceUtilization(),
                'resource_conflicts' => $this->projectManagement->getResourceConflicts(),
                'resource_forecasting' => $this->projectManagement->getResourceForecasting(),
                'skill_matrix' => $this->projectManagement->getSkillMatrix(),
                'resource_optimization' => $this->projectManagement->getResourceOptimization(),
                'resource_analytics' => $this->projectManagement->getResourceAnalytics(),
                'resource_planning' => $this->projectManagement->getResourcePlanning()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create task dependency
     * POST /api/project-management/tasks/{id}/dependencies
     */
    public function createTaskDependency($id)
    {
        try {
            $this->requirePermission('projects.tasks.manage');

            $data = Request::getJsonBody();

            // Validate required fields
            if (!isset($data['depends_on_task_id'])) {
                Response::error('depends_on_task_id is required', 400);
                return;
            }

            // Check if tasks exist
            $task = $this->getTaskById($id);
            $dependsOnTask = $this->getTaskById($data['depends_on_task_id']);

            if (!$task || !$dependsOnTask) {
                Response::error('Task not found', 404);
                return;
            }

            $dependencyData = [
                'task_id' => $id,
                'depends_on_task_id' => $data['depends_on_task_id'],
                'dependency_type' => $data['dependency_type'] ?? 'finish_to_start',
                'lag_time' => (int)($data['lag_time'] ?? 0),
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $dependencyId = $this->db->insert('task_dependencies', $dependencyData);

            // Log the creation
            $this->logActivity('dependency_created', 'task_dependencies', $dependencyId,
                "Dependency created between tasks {$task['task_name']} and {$dependsOnTask['task_name']}");

            Response::json([
                'success' => true,
                'dependency_id' => $dependencyId,
                'message' => 'Task dependency created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get project templates
     * GET /api/project-management/templates
     */
    public function getTemplates()
    {
        try {
            $this->requirePermission('projects.templates.view');

            $data = [
                'project_templates' => $this->projectManagement->getProjectTemplates(),
                'task_templates' => $this->projectManagement->getTaskTemplates(),
                'workflow_templates' => $this->projectManagement->getWorkflowTemplates(),
                'resource_templates' => $this->projectManagement->getResourceTemplates(),
                'budget_templates' => $this->projectManagement->getBudgetTemplates(),
                'template_usage' => $this->projectManagement->getTemplateUsage(),
                'template_analytics' => $this->projectManagement->getTemplateAnalytics(),
                'template_management' => $this->projectManagement->getTemplateManagement()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get project analytics
     * GET /api/project-management/analytics
     */
    public function getAnalytics()
    {
        try {
            $this->requirePermission('projects.analytics.view');

            $data = [
                'project_performance' => $this->projectManagement->getProjectPerformance(),
                'resource_analytics' => $this->projectManagement->getResourceAnalytics(),
                'budget_analytics' => $this->projectManagement->getBudgetAnalytics(),
                'timeline_analytics' => $this->projectManagement->getTimelineAnalytics(),
                'quality_analytics' => $this->projectManagement->getQualityAnalytics(),
                'risk_analytics' => $this->projectManagement->getRiskAnalytics(),
                'productivity_analytics' => $this->projectManagement->getProductivityAnalytics(),
                'predictive_analytics' => $this->projectManagement->getPredictiveAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Bulk update tasks
     * POST /api/project-management/tasks/bulk-update
     */
    public function bulkUpdateTasks()
    {
        try {
            $this->requirePermission('projects.tasks.manage');

            $data = Request::getJsonBody();

            if (!isset($data['task_ids']) || !is_array($data['task_ids'])) {
                Response::error('Task IDs array is required', 400);
                return;
            }

            if (empty($data['updates'])) {
                Response::error('Updates object is required', 400);
                return;
            }

            $taskIds = $data['task_ids'];
            $updates = $data['updates'];

            // Start transaction
            $this->db->beginTransaction();

            try {
                $updateCount = 0;

                foreach ($taskIds as $taskId) {
                    $task = $this->getTaskById($taskId);
                    if (!$task) continue;

                    $updateData = [];
                    $allowedFields = [
                        'status', 'priority', 'assigned_to', 'due_date', 'progress_percentage'
                    ];

                    foreach ($allowedFields as $field) {
                        if (isset($updates[$field])) {
                            $updateData[$field] = $updates[$field];
                        }
                    }

                    if (!empty($updateData)) {
                        $updateData['updated_by'] = $this->user['id'];
                        $updateData['updated_at'] = date('Y-m-d H:i:s');

                        $this->db->update('tasks', $updateData, ['id' => $taskId]);
                        $updateCount++;
                    }
                }

                $this->db->commit();

                // Log bulk update
                $this->logActivity('bulk_task_update', 'tasks', null, "Bulk updated {$updateCount} tasks");

                Response::json([
                    'success' => true,
                    'updated_count' => $updateCount,
                    'message' => "{$updateCount} tasks updated successfully"
                ]);
            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get project managers
     * GET /api/project-management/managers
     */
    public function getProjectManagers()
    {
        try {
            $this->requirePermission('projects.view');

            $managers = $this->projectManagement->getProjectManagers();

            Response::json(['managers' => $managers]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get departments
     * GET /api/project-management/departments
     */
    public function getDepartments()
    {
        try {
            $this->requirePermission('projects.view');

            $departments = $this->projectManagement->getDepartments();

            Response::json(['departments' => $departments]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get task categories
     * GET /api/project-management/task-categories
     */
    public function getTaskCategories()
    {
        try {
            $this->requirePermission('projects.tasks.view');

            $categories = $this->projectManagement->getTaskCategories();

            Response::json(['categories' => $categories]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getProjectById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM projects WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function getTaskById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM tasks WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function getProjectsCount($filters)
    {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['manager']) {
            $where[] = "project_manager_id = ?";
            $params[] = $filters['manager'];
        }

        if ($filters['department']) {
            $where[] = "department_id = ?";
            $params[] = $filters['department'];
        }

        if ($filters['date_from']) {
            $where[] = "start_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "end_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(project_name LIKE ? OR description LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM projects WHERE $whereClause", $params);
    }

    private function getTasksCount($filters)
    {
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

        return $this->db->queryValue("SELECT COUNT(*) FROM tasks t WHERE $whereClause", $params);
    }

    private function getTimeEntriesCount($filters)
    {
        $where = ["tt.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['user']) {
            $where[] = "tt.user_id = ?";
            $params[] = $filters['user'];
        }

        if ($filters['task']) {
            $where[] = "tt.task_id = ?";
            $params[] = $filters['task'];
        }

        if ($filters['project']) {
            $where[] = "t.project_id = ?";
            $params[] = $filters['project'];
        }

        if ($filters['date_from']) {
            $where[] = "DATE(tt.start_time) >= ?";
            $params[] = $filters['date_from'];
        }

        if ($filters['date_to']) {
            $where[] = "DATE(tt.start_time) <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("
            SELECT COUNT(*) FROM time_tracking tt
            LEFT JOIN tasks t ON tt.task_id = t.id
            WHERE $whereClause
        ", $params);
    }

    private function logActivity($action, $table, $recordId, $description)
    {
        $this->db->insert('project_activities', [
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
