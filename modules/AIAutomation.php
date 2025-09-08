<?php
/**
 * TPT Free ERP - AI-Powered Automation & Agents Module
 * Intelligent workflow automation and AI agent framework
 */

class AIAutomation extends BaseController {
    private $db;
    private $user;
    private $aiConnector;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->aiConnector = new AIConnectors();
    }

    /**
     * Main AI automation dashboard
     */
    public function index() {
        $this->requirePermission('ai.automation.view');

        $data = [
            'title' => 'AI Automation Dashboard',
            'active_workflows' => $this->getActiveWorkflows(),
            'automation_stats' => $this->getAutomationStats(),
            'recent_executions' => $this->getRecentExecutions(),
            'ai_agents' => $this->getActiveAgents(),
            'performance_metrics' => $this->getPerformanceMetrics()
        ];

        $this->render('modules/ai_automation/dashboard', $data);
    }

    /**
     * Workflow management
     */
    public function workflows() {
        $this->requirePermission('ai.automation.workflows.view');

        $filters = [
            'status' => $_GET['status'] ?? 'all',
            'category' => $_GET['category'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $workflows = $this->getWorkflows($filters);

        $data = [
            'title' => 'Workflow Management',
            'workflows' => $workflows,
            'filters' => $filters,
            'categories' => $this->getWorkflowCategories(),
            'workflow_summary' => $this->getWorkflowSummary()
        ];

        $this->render('modules/ai_automation/workflows', $data);
    }

    /**
     * Create new workflow
     */
    public function createWorkflow() {
        $this->requirePermission('ai.automation.workflows.create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processWorkflowCreation();
        }

        $data = [
            'title' => 'Create Workflow',
            'categories' => $this->getWorkflowCategories(),
            'triggers' => $this->getWorkflowTriggers(),
            'actions' => $this->getWorkflowActions(),
            'conditions' => $this->getWorkflowConditions(),
            'ai_models' => $this->getAvailableAIModels()
        ];

        $this->render('modules/ai_automation/create_workflow', $data);
    }

    /**
     * AI agents management
     */
    public function agents() {
        $this->requirePermission('ai.automation.agents.view');

        $data = [
            'title' => 'AI Agents',
            'agents' => $this->getAgents(),
            'agent_types' => $this->getAgentTypes(),
            'agent_stats' => $this->getAgentStats(),
            'conversations' => $this->getAgentConversations()
        ];

        $this->render('modules/ai_automation/agents', $data);
    }

    /**
     * Create new AI agent
     */
    public function createAgent() {
        $this->requirePermission('ai.automation.agents.create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processAgentCreation();
        }

        $data = [
            'title' => 'Create AI Agent',
            'agent_types' => $this->getAgentTypes(),
            'capabilities' => $this->getAgentCapabilities(),
            'ai_models' => $this->getAvailableAIModels(),
            'personas' => $this->getAgentPersonas()
        ];

        $this->render('modules/ai_automation/create_agent', $data);
    }

    /**
     * Decision automation
     */
    public function decisions() {
        $this->requirePermission('ai.automation.decisions.view');

        $data = [
            'title' => 'Decision Automation',
            'decision_rules' => $this->getDecisionRules(),
            'decision_history' => $this->getDecisionHistory(),
            'decision_accuracy' => $this->getDecisionAccuracy(),
            'pending_decisions' => $this->getPendingDecisions()
        ];

        $this->render('modules/ai_automation/decisions', $data);
    }

    /**
     * Predictive analytics
     */
    public function predictions() {
        $this->requirePermission('ai.automation.predictions.view');

        $data = [
            'title' => 'Predictive Analytics',
            'predictions' => $this->getPredictions(),
            'prediction_models' => $this->getPredictionModels(),
            'accuracy_metrics' => $this->getPredictionAccuracy(),
            'forecasts' => $this->getForecasts()
        ];

        $this->render('modules/ai_automation/predictions', $data);
    }

    /**
     * Learning and optimization
     */
    public function learning() {
        $this->requirePermission('ai.automation.learning.view');

        $data = [
            'title' => 'AI Learning & Optimization',
            'learning_models' => $this->getLearningModels(),
            'optimization_suggestions' => $this->getOptimizationSuggestions(),
            'performance_improvements' => $this->getPerformanceImprovements(),
            'training_data' => $this->getTrainingData()
        ];

        $this->render('modules/ai_automation/learning', $data);
    }

    /**
     * Automation analytics
     */
    public function analytics() {
        $this->requirePermission('ai.automation.analytics.view');

        $data = [
            'title' => 'Automation Analytics',
            'efficiency_metrics' => $this->getEfficiencyMetrics(),
            'cost_savings' => $this->getCostSavings(),
            'error_reduction' => $this->getErrorReduction(),
            'roi_analysis' => $this->getROIAnalysis()
        ];

        $this->render('modules/ai_automation/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getActiveWorkflows() {
        return $this->db->query("
            SELECT
                w.*,
                COUNT(we.id) as total_executions,
                COUNT(CASE WHEN we.status = 'success' THEN 1 END) as successful_executions,
                MAX(we.executed_at) as last_execution,
                AVG(we.execution_time_ms) as avg_execution_time
            FROM ai_workflows w
            LEFT JOIN ai_workflow_executions we ON w.id = we.workflow_id
            WHERE w.company_id = ? AND w.is_active = true
            GROUP BY w.id
            ORDER BY w.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAutomationStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT w.id) as total_workflows,
                COUNT(DISTINCT a.id) as total_agents,
                COUNT(we.id) as total_executions,
                COUNT(CASE WHEN we.status = 'success' THEN 1 END) as successful_executions,
                AVG(we.execution_time_ms) as avg_execution_time,
                SUM(w.time_saved_hours) as total_time_saved
            FROM ai_workflows w
            LEFT JOIN ai_agents a ON a.company_id = w.company_id
            LEFT JOIN ai_workflow_executions we ON we.workflow_id = w.id
            WHERE w.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRecentExecutions() {
        return $this->db->query("
            SELECT
                we.*,
                w.name as workflow_name,
                w.category as workflow_category,
                u.first_name,
                u.last_name
            FROM ai_workflow_executions we
            JOIN ai_workflows w ON we.workflow_id = w.id
            LEFT JOIN users u ON we.triggered_by = u.id
            WHERE we.company_id = ?
            ORDER BY we.executed_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getActiveAgents() {
        return $this->db->query("
            SELECT
                a.*,
                COUNT(ac.id) as total_conversations,
                MAX(ac.updated_at) as last_conversation,
                AVG(ac.satisfaction_rating) as avg_satisfaction
            FROM ai_agents a
            LEFT JOIN ai_agent_conversations ac ON a.id = ac.agent_id
            WHERE a.company_id = ? AND a.is_active = true
            GROUP BY a.id
            ORDER BY a.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceMetrics() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('day', executed_at) as date,
                COUNT(*) as executions,
                AVG(execution_time_ms) as avg_time,
                COUNT(CASE WHEN status = 'success' THEN 1 END) as successes,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failures
            FROM ai_workflow_executions
            WHERE company_id = ? AND executed_at >= ?
            GROUP BY DATE_TRUNC('day', executed_at)
            ORDER BY date DESC
            LIMIT 30
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getWorkflows($filters) {
        $where = ["w.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status'] !== 'all') {
            $where[] = "w.is_active = ?";
            $params[] = $filters['status'] === 'active' ? true : false;
        }

        if ($filters['category']) {
            $where[] = "w.category = ?";
            $params[] = $filters['category'];
        }

        if ($filters['search']) {
            $where[] = "(w.name LIKE ? OR w.description LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                w.*,
                COUNT(we.id) as total_executions,
                COUNT(CASE WHEN we.status = 'success' THEN 1 END) as successful_executions,
                MAX(we.executed_at) as last_execution
            FROM ai_workflows w
            LEFT JOIN ai_workflow_executions we ON w.id = we.workflow_id
            WHERE $whereClause
            GROUP BY w.id
            ORDER BY w.created_at DESC
        ", $params);
    }

    private function getWorkflowCategories() {
        return [
            'customer_service' => 'Customer Service',
            'sales_automation' => 'Sales Automation',
            'inventory_management' => 'Inventory Management',
            'financial_processing' => 'Financial Processing',
            'hr_operations' => 'HR Operations',
            'quality_control' => 'Quality Control',
            'project_management' => 'Project Management',
            'marketing' => 'Marketing',
            'compliance' => 'Compliance',
            'custom' => 'Custom Workflows'
        ];
    }

    private function getWorkflowTriggers() {
        return [
            'schedule' => ['name' => 'Schedule', 'description' => 'Time-based triggers'],
            'event' => ['name' => 'Event', 'description' => 'System event triggers'],
            'webhook' => ['name' => 'Webhook', 'description' => 'External webhook triggers'],
            'api' => ['name' => 'API Call', 'description' => 'API endpoint triggers'],
            'condition' => ['name' => 'Condition', 'description' => 'Conditional triggers'],
            'manual' => ['name' => 'Manual', 'description' => 'Manual execution triggers']
        ];
    }

    private function getWorkflowActions() {
        return [
            'send_email' => ['name' => 'Send Email', 'description' => 'Send automated emails'],
            'create_task' => ['name' => 'Create Task', 'description' => 'Create tasks in project management'],
            'update_record' => ['name' => 'Update Record', 'description' => 'Update database records'],
            'generate_report' => ['name' => 'Generate Report', 'description' => 'Generate automated reports'],
            'send_notification' => ['name' => 'Send Notification', 'description' => 'Send system notifications'],
            'api_call' => ['name' => 'API Call', 'description' => 'Make external API calls'],
            'ai_analysis' => ['name' => 'AI Analysis', 'description' => 'Perform AI-powered analysis'],
            'conditional_action' => ['name' => 'Conditional Action', 'description' => 'Execute based on conditions']
        ];
    }

    private function getWorkflowConditions() {
        return [
            'equals' => 'Equals',
            'not_equals' => 'Not Equals',
            'greater_than' => 'Greater Than',
            'less_than' => 'Less Than',
            'contains' => 'Contains',
            'not_contains' => 'Does Not Contain',
            'starts_with' => 'Starts With',
            'ends_with' => 'Ends With',
            'is_empty' => 'Is Empty',
            'is_not_empty' => 'Is Not Empty',
            'matches_regex' => 'Matches Regex',
            'custom_condition' => 'Custom Condition'
        ];
    }

    private function getAvailableAIModels() {
        return [
            'gpt-4' => 'GPT-4 (OpenAI)',
            'claude-3' => 'Claude 3 (Anthropic)',
            'gemini-pro' => 'Gemini Pro (Google)',
            'auto' => 'Auto Select'
        ];
    }

    private function getWorkflowSummary() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_workflows,
                COUNT(CASE WHEN is_active = true THEN 1 END) as active_workflows,
                COUNT(CASE WHEN is_active = false THEN 1 END) as inactive_workflows,
                AVG(success_rate) as avg_success_rate
            FROM (
                SELECT
                    w.*,
                    CASE
                        WHEN COUNT(we.id) > 0 THEN
                            (COUNT(CASE WHEN we.status = 'success' THEN 1 END) * 100.0 / COUNT(we.id))
                        ELSE 0
                    END as success_rate
                FROM ai_workflows w
                LEFT JOIN ai_workflow_executions we ON w.id = we.workflow_id
                    AND we.executed_at >= ?
                WHERE w.company_id = ?
                GROUP BY w.id
            ) workflow_stats
        ", [
            date('Y-m-d H:i:s', strtotime('-30 days')),
            $this->user['company_id']
        ]);
    }

    private function processWorkflowCreation() {
        $this->requirePermission('ai.automation.workflows.create');

        $data = $this->validateWorkflowData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid workflow data');
            $this->redirect('/ai-automation/create-workflow');
        }

        try {
            $this->db->beginTransaction();

            $workflowId = $this->db->insert('ai_workflows', [
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'category' => $data['category'],
                'trigger_type' => $data['trigger_type'],
                'trigger_config' => json_encode($data['trigger_config']),
                'actions' => json_encode($data['actions']),
                'conditions' => json_encode($data['conditions']),
                'ai_model' => $data['ai_model'],
                'is_active' => $data['is_active'],
                'time_saved_hours' => $data['time_saved_hours'],
                'created_by' => $this->user['id']
            ]);

            $this->db->commit();

            $this->setFlash('success', 'Workflow created successfully');
            $this->redirect('/ai-automation/workflows');

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to create workflow: ' . $e->getMessage());
            $this->redirect('/ai-automation/create-workflow');
        }
    }

    private function validateWorkflowData($data) {
        if (empty($data['name']) || empty($data['category']) || empty($data['trigger_type'])) {
            return false;
        }

        // Validate trigger configuration
        if (!isset($data['trigger_config']) || !is_array($data['trigger_config'])) {
            return false;
        }

        // Validate actions
        if (!isset($data['actions']) || !is_array($data['actions'])) {
            return false;
        }

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'category' => $data['category'],
            'trigger_type' => $data['trigger_type'],
            'trigger_config' => $data['trigger_config'],
            'actions' => $data['actions'],
            'conditions' => $data['conditions'] ?? [],
            'ai_model' => $data['ai_model'] ?? 'auto',
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
            'time_saved_hours' => (int)($data['time_saved_hours'] ?? 0)
        ];
    }

    private function getAgents() {
        return $this->db->query("
            SELECT
                a.*,
                COUNT(ac.id) as total_conversations,
                AVG(ac.satisfaction_rating) as avg_satisfaction,
                MAX(ac.updated_at) as last_active
            FROM ai_agents a
            LEFT JOIN ai_agent_conversations ac ON a.id = ac.agent_id
            WHERE a.company_id = ?
            GROUP BY a.id
            ORDER BY a.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAgentTypes() {
        return [
            'customer_support' => [
                'name' => 'Customer Support Agent',
                'description' => 'Handles customer inquiries and support tickets',
                'capabilities' => ['chat', 'email', 'ticketing']
            ],
            'sales_assistant' => [
                'name' => 'Sales Assistant',
                'description' => 'Assists with sales inquiries and lead qualification',
                'capabilities' => ['lead_scoring', 'recommendations', 'follow_up']
            ],
            'data_analyst' => [
                'name' => 'Data Analyst',
                'description' => 'Analyzes business data and generates insights',
                'capabilities' => ['data_analysis', 'reporting', 'forecasting']
            ],
            'workflow_orchestrator' => [
                'name' => 'Workflow Orchestrator',
                'description' => 'Manages and optimizes business workflows',
                'capabilities' => ['automation', 'optimization', 'monitoring']
            ],
            'quality_assurance' => [
                'name' => 'Quality Assurance Agent',
                'description' => 'Ensures quality standards and compliance',
                'capabilities' => ['validation', 'compliance', 'auditing']
            ]
        ];
    }

    private function getAgentCapabilities() {
        return [
            'natural_language' => 'Natural Language Processing',
            'data_analysis' => 'Data Analysis & Insights',
            'automation' => 'Workflow Automation',
            'decision_making' => 'Decision Support',
            'learning' => 'Continuous Learning',
            'integration' => 'System Integration',
            'reporting' => 'Automated Reporting',
            'alerting' => 'Smart Alerting'
        ];
    }

    private function getAgentPersonas() {
        return [
            'professional' => 'Professional & Formal',
            'friendly' => 'Friendly & Approachable',
            'technical' => 'Technical Expert',
            'executive' => 'Executive Summary',
            'creative' => 'Creative & Innovative',
            'analytical' => 'Analytical & Detailed'
        ];
    }

    private function getAgentStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_agents,
                COUNT(CASE WHEN is_active = true THEN 1 END) as active_agents,
                AVG(avg_satisfaction) as overall_satisfaction,
                SUM(total_conversations) as total_conversations
            FROM (
                SELECT
                    a.*,
                    COUNT(ac.id) as total_conversations,
                    AVG(ac.satisfaction_rating) as avg_satisfaction
                FROM ai_agents a
                LEFT JOIN ai_agent_conversations ac ON a.id = ac.agent_id
                WHERE a.company_id = ?
                GROUP BY a.id
            ) agent_stats
        ", [$this->user['company_id']]);
    }

    private function getAgentConversations() {
        return $this->db->query("
            SELECT
                ac.*,
                a.name as agent_name,
                a.type as agent_type,
                u.first_name,
                u.last_name
            FROM ai_agent_conversations ac
            JOIN ai_agents a ON ac.agent_id = a.id
            LEFT JOIN users u ON ac.user_id = u.id
            WHERE ac.company_id = ?
            ORDER BY ac.updated_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function processAgentCreation() {
        $this->requirePermission('ai.automation.agents.create');

        $data = $this->validateAgentData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid agent data');
            $this->redirect('/ai-automation/create-agent');
        }

        try {
            $this->db->beginTransaction();

            $agentId = $this->db->insert('ai_agents', [
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'type' => $data['type'],
                'capabilities' => json_encode($data['capabilities']),
                'ai_model' => $data['ai_model'],
                'persona' => $data['persona'],
                'system_prompt' => $data['system_prompt'],
                'configuration' => json_encode($data['configuration']),
                'is_active' => $data['is_active'],
                'created_by' => $this->user['id']
            ]);

            $this->db->commit();

            $this->setFlash('success', 'AI Agent created successfully');
            $this->redirect('/ai-automation/agents');

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to create agent: ' . $e->getMessage());
            $this->redirect('/ai-automation/create-agent');
        }
    }

    private function validateAgentData($data) {
        if (empty($data['name']) || empty($data['type']) || empty($data['system_prompt'])) {
            return false;
        }

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'type' => $data['type'],
            'capabilities' => $data['capabilities'] ?? [],
            'ai_model' => $data['ai_model'] ?? 'auto',
            'persona' => $data['persona'] ?? 'professional',
            'system_prompt' => $data['system_prompt'],
            'configuration' => $data['configuration'] ?? [],
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true
        ];
    }

    private function getDecisionRules() {
        return $this->db->query("
            SELECT
                dr.*,
                COUNT(dd.id) as total_decisions,
                COUNT(CASE WHEN dd.outcome = 'approved' THEN 1 END) as approved_decisions,
                AVG(dd.confidence_score) as avg_confidence
            FROM ai_decision_rules dr
            LEFT JOIN ai_decisions dd ON dr.id = dd.rule_id
            WHERE dr.company_id = ?
            GROUP BY dr.id
            ORDER BY dr.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getDecisionHistory() {
        return $this->db->query("
            SELECT
                dd.*,
                dr.name as rule_name,
                dr.category as rule_category,
                u.first_name,
                u.last_name
            FROM ai_decisions dd
            JOIN ai_decision_rules dr ON dd.rule_id = dr.id
            LEFT JOIN users u ON dd.made_by = u.id
            WHERE dd.company_id = ?
            ORDER BY dd.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getDecisionAccuracy() {
        return $this->db->query("
            SELECT
                dr.name,
                dr.category,
                COUNT(dd.id) as total_decisions,
                COUNT(CASE WHEN dd.outcome = 'approved' THEN 1 END) as approved,
                COUNT(CASE WHEN dd.outcome = 'rejected' THEN 1 END) as rejected,
                COUNT(CASE WHEN dd.outcome = 'escalated' THEN 1 END) as escalated,
                AVG(dd.confidence_score) as avg_confidence,
                ROUND(
                    (COUNT(CASE WHEN dd.final_outcome = dd.recommended_outcome THEN 1 END) * 100.0 / COUNT(dd.id)), 2
                ) as accuracy_rate
            FROM ai_decision_rules dr
            LEFT JOIN ai_decisions dd ON dr.id = dd.rule_id
            WHERE dr.company_id = ?
            GROUP BY dr.id, dr.name, dr.category
            ORDER BY accuracy_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getPendingDecisions() {
        return $this->db->query("
            SELECT
                dd.*,
                dr.name as rule_name,
                dr.category as rule_category
            FROM ai_decisions dd
            JOIN ai_decision_rules dr ON dd.rule_id = dr.id
            WHERE dd.company_id = ? AND dd.status = 'pending'
            ORDER BY dd.created_at ASC
        ", [$this->user['company_id']]);
    }

    private function getPredictions() {
        return $this->db->query("
            SELECT
                p.*,
                pm.name as model_name,
                pm.category as model_category,
                ROUND(
                    (COUNT(CASE WHEN p.actual_value IS NOT NULL AND ABS(p.predicted_value - p.actual_value) <= p.tolerance THEN 1 END) * 100.0 / COUNT(*)), 2
                ) as accuracy_rate
            FROM ai_predictions p
            JOIN ai_prediction_models pm ON p.model_id = pm.id
            WHERE p.company_id = ?
            GROUP BY p.id, pm.name, pm.category
            ORDER BY p.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getPredictionModels() {
        return $this->db->query("
            SELECT
                pm.*,
                COUNT(p.id) as total_predictions,
                AVG(p.accuracy_rate) as avg_accuracy,
                MAX(p.created_at) as last_used
            FROM ai_prediction_models pm
            LEFT JOIN ai_predictions p ON pm.id = p.model_id
            WHERE pm.company_id = ?
            GROUP BY pm.id
            ORDER BY pm.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPredictionAccuracy() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('month', created_at) as month,
                COUNT(*) as total_predictions,
                AVG(accuracy_rate) as avg_accuracy,
                MIN(accuracy_rate) as min_accuracy,
                MAX(accuracy_rate) as max_accuracy
            FROM ai_predictions
            WHERE company_id = ? AND created_at >= ?
            GROUP BY DATE_TRUNC('month', created_at)
            ORDER BY month DESC
            LIMIT 12
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 months'))
        ]);
    }

    private function getForecasts() {
        return $this->db->query("
            SELECT
                f.*,
                pm.name as model_name,
                COUNT(fp.id) as data_points
            FROM ai_forecasts f
            JOIN ai_prediction_models pm ON f.model_id = pm.id
            LEFT JOIN ai_forecast_points fp ON f.id = fp.forecast_id
            WHERE f.company_id = ?
            GROUP BY f.id, pm.name
            ORDER BY f.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getLearningModels() {
        return $this->db->query("
            SELECT
                lm.*,
                COUNT(lmd.id) as training_samples,
                AVG(lm.performance_score) as avg_performance,
                MAX(lm.last_trained_at) as last_trained
            FROM ai_learning_models lm
            LEFT JOIN ai_learning_model_data lmd ON lm.id = lmd.model_id
            WHERE lm.company_id = ?
            GROUP BY lm.id
            ORDER BY lm.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getOptimizationSuggestions() {
        return $this->db->query("
            SELECT
                os.*,
                lm.name as model_name,
                os.implemented_at IS NOT NULL as is_implemented,
                os.estimated_savings
            FROM ai_optimization_suggestions os
            JOIN ai_learning_models lm ON os.model_id = lm.id
            WHERE os.company_id = ?
            ORDER BY os.potential_impact DESC, os.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getPerformanceImprovements() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('month', implemented_at) as month,
                COUNT(*) as improvements_count,
                SUM(estimated_savings) as total_savings,
                AVG(performance_gain_percent) as avg_performance_gain
            FROM ai_optimization_suggestions
            WHERE company_id = ? AND implemented_at IS NOT NULL
                AND implemented_at >= ?
            GROUP BY DATE_TRUNC('month', implemented_at)
            ORDER BY month DESC
            LIMIT 12
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 months'))
        ]);
    }

    private function getTrainingData() {
        return $this->db->query("
            SELECT
                lmd.*,
                lm.name as model_name,
                lmd.quality_score,
                lmd.is_validated
            FROM ai_learning_model_data lmd
            JOIN ai_learning_models lm ON lmd.model_id = lm.id
            WHERE lmd.company_id = ?
            ORDER BY lmd.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getEfficiencyMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT w.id) as automated_workflows,
                COUNT(we.id) as total_executions,
                SUM(w.time_saved_hours) as total_time_saved,
                AVG(we.execution_time_ms) / 1000 as avg_execution_seconds,
                COUNT(CASE WHEN we.status = 'success' THEN 1 END) * 100.0 / COUNT(we.id) as success_rate
            FROM ai_workflows w
            LEFT JOIN ai_workflow_executions we ON w.id = we.workflow_id
            WHERE w.company_id = ? AND w.is_active = true
        ", [$this->user['company_id']]);
    }

    private function getCostSavings() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('month', created_at) as month,
                SUM(time_saved_hours * ?) as labor_cost_savings,
                SUM(error_prevention_savings) as error_savings,
                SUM(optimization_savings) as optimization_savings
            FROM ai_automation_metrics
            WHERE company_id = ? AND created_at >= ?
            GROUP BY DATE_TRUNC('month', created_at)
            ORDER BY month DESC
            LIMIT 12
        ", [
            25.0, // Average hourly rate
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 months'))
        ]);
    }

    private function getErrorReduction() {
        return $this->db->query("
            SELECT
                category,
                SUM(errors_prevented) as total_prevented,
                SUM(errors_occurred) as total_occurred,
                ROUND(
                    (SUM(errors_prevented) * 100.0 / (SUM(errors_prevented) + SUM(errors_occurred))), 2
                ) as prevention_rate
            FROM ai_error_tracking
            WHERE company_id = ? AND created_at >= ?
            GROUP BY category
            ORDER BY total_prevented DESC
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getROIAnalysis() {
        $totalInvestment = $this->getTotalInvestment();
        $totalSavings = $this->getTotalSavings();

        $roi = $totalInvestment > 0 ? (($totalSavings - $totalInvestment) / $totalInvestment) * 100 : 0;

        return [
            'total_investment' => $totalInvestment,
            'total_savings' => $totalSavings,
            'net_benefit' => $totalSavings - $totalInvestment,
            'roi_percentage' => round($roi, 2),
            'payback_period_months' => $this->calculatePaybackPeriod($totalInvestment, $totalSavings)
        ];
    }

    private function getTotalInvestment() {
        // Calculate total investment in AI automation (API costs, development time, etc.)
        return $this->db->querySingle("
            SELECT
                SUM(api_cost_usd) + SUM(development_cost_usd) + SUM(infrastructure_cost_usd) as total_investment
            FROM ai_investment_tracking
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 months'))
        ])['total_investment'] ?? 0;
    }

    private function getTotalSavings() {
        // Calculate total savings from automation
        return $this->db->querySingle("
            SELECT
                SUM(time_saved_hours * ?) + SUM(error_prevention_savings) + SUM(optimization_savings) as total_savings
            FROM ai_automation_metrics
            WHERE company_id = ? AND created_at >= ?
        ", [
            25.0, // Average hourly rate
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 months'))
        ])['total_savings'] ?? 0;
    }

    private function calculatePaybackPeriod($investment, $monthlySavings) {
        if ($monthlySavings <= 0) return null;

        $months = ceil($investment / $monthlySavings);
        return $months;
    }

    // ============================================================================
    // WORKFLOW EXECUTION METHODS
    // ============================================================================

    public function executeWorkflow($workflowId, $triggerData = []) {
        $workflow = $this->db->querySingle("
            SELECT * FROM ai_workflows
            WHERE id = ? AND company_id = ? AND is_active = true
        ", [$workflowId, $this->user['company_id']]);

        if (!$workflow) {
            throw new Exception('Workflow not found or inactive');
        }

        try {
            $this->db->beginTransaction();

            $executionId = $this->db->insert('ai_workflow_executions', [
                'company_id' => $this->user['company_id'],
                'workflow_id' => $workflowId,
                'trigger_data' => json_encode($triggerData),
                'status' => 'running',
                'triggered_by' => $this->user['id'] ?? null,
                'started_at' => date('Y-m-d H:i:s')
            ]);

            // Execute workflow actions
            $result = $this->executeWorkflowActions($workflow, $triggerData);

            // Update execution status
            $this->db->update('ai_workflow_executions', [
                'status' => $result['success'] ? 'success' : 'failed',
                'result_data' => json_encode($result),
                'execution_time_ms' => $result['execution_time_ms'],
                'completed_at' => date('Y-m-d H:i:s'),
                'error_message' => $result['error'] ?? null
            ], 'id = ?', [$executionId]);

            $this->db->commit();

            return $result;

        } catch (Exception $e) {
            $this->db->rollback();

            // Log failed execution
            $this->db->update('ai_workflow_executions', [
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$executionId]);

            throw $e;
        }
    }

    private function executeWorkflowActions($workflow, $triggerData) {
        $startTime = microtime(true);
        $actions = json_decode($workflow['actions'], true);
        $results = [];

        foreach ($actions as $action) {
            try {
                $result = $this->executeAction($action, $triggerData, $workflow);
                $results[] = [
                    'action' => $action['type'],
                    'success' => true,
                    'result' => $result
                ];
            } catch (Exception $e) {
                $results[] = [
                    'action' => $action['type'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];

                // Stop execution if action fails and workflow is set to fail-fast
                if ($workflow['fail_fast']) {
                    break;
                }
            }
        }

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000);

        return [
            'success' => !in_array(false, array_column($results, 'success')),
            'results' => $results,
            'execution_time_ms' => $executionTime
        ];
    }

    private function executeAction($action, $triggerData, $workflow) {
        switch ($action['type']) {
            case 'send_email':
                return $this->executeSendEmailAction($action, $triggerData);
            case 'create_task':
                return $this->executeCreateTaskAction($action, $triggerData);
            case 'update_record':
                return $this->executeUpdateRecordAction($action, $triggerData);
            case 'generate_report':
                return $this->executeGenerateReportAction($action, $triggerData);
            case 'send_notification':
                return $this->executeSendNotificationAction($action, $triggerData);
            case 'api_call':
                return $this->executeApiCallAction($action, $triggerData);
            case 'ai_analysis':
                return $this->executeAIAnalysisAction($action, $triggerData, $workflow);
            default:
                throw new Exception('Unknown action type: ' . $action['type']);
        }
    }

    private function executeSendEmailAction($action, $triggerData) {
        // Implementation for sending emails
        $email = new Email();
        return $email->send(
            $action['config']['to'],
            $action['config']['subject'],
            $action['config']['body'],
            $action['config']['template'] ?? null
        );
    }

    private function executeCreateTaskAction($action, $triggerData) {
        // Implementation for creating tasks
        $taskData = [
            'title' => $action['config']['title'] ?? 'Automated Task',
            'description' => $action['config']['description'] ?? '',
            'priority' => $action['config']['priority'] ?? 'medium',
            'assigned_to' => $action['config']['assigned_to'] ?? null,
            'due_date' => $action['config']['due_date'] ?? null,
            'project_id' => $action['config']['project_id'] ?? null,
            'company_id' => $this->user['company_id'],
            'created_by' => $this->user['id'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert('tasks', $taskData);
    }

    private function executeUpdateRecordAction($action, $triggerData) {
        // Implementation for updating records
        $table = $action['config']['table'];
        $data = $action['config']['data'];
        $where = $action['config']['where'];

        return $this->db->update($table, $data, $where['condition'], $where['params']);
    }

    private function executeGenerateReportAction($action, $triggerData) {
        // Implementation for generating reports
        $reportType = $action['config']['report_type'];
        $parameters = $action['config']['parameters'] ?? [];

        // Generate report based on type
        switch ($reportType) {
            case 'sales':
                return $this->generateSalesReport($parameters);
            case 'inventory':
                return $this->generateInventoryReport($parameters);
            case 'financial':
                return $this->generateFinancialReport($parameters);
            default:
                return $this->generateCustomReport($parameters);
        }
    }

    private function executeSendNotificationAction($action, $triggerData) {
        // Implementation for sending notifications
        $notification = new Notification();
        return $notification->send(
            $action['config']['user_id'] ?? $this->user['id'],
            $action['config']['type'],
            $action['config']['title'],
            $action['config']['message']
        );
    }

    private function executeApiCallAction($action, $triggerData) {
        // Implementation for making API calls
        $url = $action['config']['url'];
        $method = $action['config']['method'] ?? 'GET';
        $headers = $action['config']['headers'] ?? [];
        $data = $action['config']['data'] ?? [];

        // Make HTTP request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'response' => $response,
            'http_code' => $httpCode
        ];
    }

    private function executeAIAnalysisAction($action, $triggerData, $workflow) {
        // Implementation for AI analysis
        $analysisType = $action['config']['analysis_type'];
        $data = $action['config']['data'] ?? $triggerData;

        // Use AI connector for analysis
        return $this->aiConnector->analyze($analysisType, $data, $workflow['ai_model']);
    }

    private function generateSalesReport($parameters) {
        // Implementation for sales report generation
        return $this->db->query("
            SELECT
                DATE_TRUNC('month', created_at) as month,
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value
            FROM sales_orders
            WHERE company_id = ? AND created_at >= ?
            GROUP BY DATE_TRUNC('month', created_at)
            ORDER BY month DESC
        ", [
            $this->user['company_id'],
            $parameters['start_date'] ?? date('Y-m-d H:i:s', strtotime('-6 months'))
        ]);
    }

    private function generateInventoryReport($parameters) {
        // Implementation for inventory report generation
        return $this->db->query("
            SELECT
                p.name,
                p.sku,
                i.quantity,
                i.min_stock_level,
                CASE WHEN i.quantity <= i.min_stock_level THEN 'Low Stock' ELSE 'In Stock' END as status
            FROM inventory i
            JOIN products p ON i.product_id = p.id
            WHERE i.company_id = ?
            ORDER BY i.quantity ASC
        ", [$this->user['company_id']]);
    }

    private function generateFinancialReport($parameters) {
        // Implementation for financial report generation
        return $this->db->query("
            SELECT
                DATE_TRUNC('month', transaction_date) as month,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses,
                SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as net_profit
            FROM financial_transactions
            WHERE company_id = ? AND transaction_date >= ?
            GROUP BY DATE_TRUNC('month', transaction_date)
            ORDER BY month DESC
        ", [
            $this->user['company_id'],
            $parameters['start_date'] ?? date('Y-m-d H:i:s', strtotime('-6 months'))
        ]);
    }

    private function generateCustomReport($parameters) {
        // Implementation for custom report generation
        $query = $parameters['query'] ?? '';
        $params = $parameters['params'] ?? [];

        if (empty($query)) {
            return [];
        }

        return $this->db->query($query, $params);
    }
}
