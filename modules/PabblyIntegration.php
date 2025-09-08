<?php
/**
 * TPT Free ERP - Pabbly Connect Integration Module
 * Complete integration with Pabbly Connect for workflow automation
 */

class PabblyIntegration extends BaseController {
    private $db;
    private $user;
    private $pabblyConfig;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->pabblyConfig = $this->getPabblyConfig();
    }

    /**
     * Main Pabbly Connect integration dashboard
     */
    public function index() {
        $this->requirePermission('integrations.pabbly.view');

        $data = array(
            'title' => 'Pabbly Connect Integration',
            'workflows' => $this->getWorkflows(),
            'triggers' => $this->getTriggers(),
            'workflow_stats' => $this->getWorkflowStats(),
            'recent_activity' => $this->getRecentActivity(),
            'connection_status' => $this->getConnectionStatus()
        );

        $this->render('modules/integrations/pabbly/dashboard', $data);
    }

    /**
     * Create new Pabbly Connect workflow
     */
    public function createWorkflow() {
        $this->requirePermission('integrations.pabbly.create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processWorkflowCreation();
        }

        $data = array(
            'title' => 'Create Pabbly Connect Workflow',
            'templates' => $this->getWorkflowTemplates(),
            'triggers' => $this->getAvailableTriggers(),
            'actions' => $this->getAvailableActions(),
            'modules' => $this->getAvailableModules()
        );

        $this->render('modules/integrations/pabbly/create_workflow', $data);
    }

    /**
     * Workflow execution history
     */
    public function executions() {
        $this->requirePermission('integrations.pabbly.monitoring.view');

        $filters = array(
            'workflow_id' => isset($_GET['workflow_id']) ? $_GET['workflow_id'] : null,
            'status' => isset($_GET['status']) ? $_GET['status'] : 'all',
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-7 days')),
            'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d')
        );

        $executions = $this->getExecutions($filters);

        $data = array(
            'title' => 'Workflow Executions',
            'executions' => $executions,
            'filters' => $filters,
            'workflows' => $this->getWorkflows(),
            'execution_stats' => $this->getExecutionStats($filters)
        );

        $this->render('modules/integrations/pabbly/executions', $data);
    }

    /**
     * Webhook management
     */
    public function webhooks() {
        $this->requirePermission('integrations.pabbly.webhooks.view');

        $data = array(
            'title' => 'Pabbly Connect Webhooks',
            'webhooks' => $this->getWebhooks(),
            'webhook_logs' => $this->getWebhookLogs(),
            'webhook_stats' => $this->getWebhookStats()
        );

        $this->render('modules/integrations/pabbly/webhooks', $data);
    }

    // ============================================================================
    // PRIVATE METHODS - DATA RETRIEVAL
    // ============================================================================

    private function getPabblyConfig() {
        return $this->db->querySingle("
            SELECT * FROM integration_configs
            WHERE provider = 'pabbly' AND company_id = ?
        ", array($this->user['company_id']));
    }

    private function getWorkflows() {
        return $this->db->query("
            SELECT
                pw.*,
                COUNT(pel.id) as execution_count,
                MAX(pel.executed_at) as last_execution,
                AVG(pel.execution_time_ms) as avg_execution_time
            FROM pabbly_workflows pw
            LEFT JOIN pabbly_execution_logs pel ON pw.id = pel.workflow_id
            WHERE pw.company_id = ?
            GROUP BY pw.id
            ORDER BY pw.created_at DESC
        ", array($this->user['company_id']));
    }

    private function getTriggers() {
        return $this->db->query("
            SELECT
                pt.*,
                pw.name as workflow_name,
                COUNT(ptl.id) as trigger_count,
                MAX(ptl.triggered_at) as last_trigger
            FROM pabbly_triggers pt
            LEFT JOIN pabbly_workflows pw ON pt.workflow_id = pw.id
            LEFT JOIN pabbly_trigger_logs ptl ON pt.id = ptl.trigger_id
            WHERE pt.company_id = ?
            GROUP BY pt.id, pw.name
            ORDER BY pt.created_at DESC
        ", array($this->user['company_id']));
    }

    private function getWorkflowStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_workflows,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_workflows,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_workflows,
                SUM(execution_count) as total_executions,
                AVG(avg_execution_time) as avg_execution_time
            FROM (
                SELECT
                    pw.*,
                    COUNT(pel.id) as execution_count,
                    AVG(pel.execution_time_ms) as avg_execution_time
                FROM pabbly_workflows pw
                LEFT JOIN pabbly_execution_logs pel ON pw.id = pel.workflow_id
                WHERE pw.company_id = ?
                GROUP BY pw.id
            ) workflow_stats
        ", array($this->user['company_id']));
    }

    private function getRecentActivity() {
        return $this->db->query("
            SELECT
                'execution' as activity_type,
                CONCAT('Workflow executed: ', pw.name) as description,
                pel.executed_at as activity_time,
                pel.status,
                pel.execution_time_ms
            FROM pabbly_execution_logs pel
            JOIN pabbly_workflows pw ON pel.workflow_id = pw.id
            WHERE pel.company_id = ?
            UNION ALL
            SELECT
                'trigger' as activity_type,
                CONCAT('Trigger fired: ', pt.name) as description,
                ptl.triggered_at as activity_time,
                'success' as status,
                NULL as execution_time_ms
            FROM pabbly_trigger_logs ptl
            JOIN pabbly_triggers pt ON ptl.trigger_id = pt.id
            WHERE ptl.company_id = ?
            ORDER BY activity_time DESC
            LIMIT 20
        ", array($this->user['company_id'], $this->user['company_id']));
    }

    private function getConnectionStatus() {
        $config = $this->pabblyConfig;

        if (!$config) {
            return array(
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'Pabbly Connect integration not configured'
            );
        }

        // Test connection to Pabbly Connect API
        $connectionTest = $this->testPabblyConnection();

        return array(
            'connected' => $connectionTest['success'],
            'status' => $connectionTest['success'] ? 'connected' : 'error',
            'message' => $connectionTest['message'],
            'last_test' => date('Y-m-d H:i:s')
        );
    }

    private function getWorkflowTemplates() {
        return array(
            'lead_nurturing' => array(
                'name' => 'Lead Nurturing Automation',
                'description' => 'Automate lead capture and nurturing campaigns',
                'steps' => array('webhook', 'lead_scoring', 'email_sequence', 'crm_sync')
            ),
            'order_fulfillment' => array(
                'name' => 'Order Fulfillment Workflow',
                'description' => 'Streamline order processing and fulfillment',
                'steps' => array('order_webhook', 'inventory_check', 'payment_verify', 'shipping_notification')
            ),
            'customer_support' => array(
                'name' => 'Customer Support Automation',
                'description' => 'Automate customer support ticket routing',
                'steps' => array('support_ticket', 'priority_check', 'agent_assignment', 'response_template')
            ),
            'social_media' => array(
                'name' => 'Social Media Management',
                'description' => 'Automate social media posting and engagement',
                'steps' => array('content_scheduler', 'multi_platform_post', 'engagement_tracking', 'analytics_report')
            )
        );
    }

    private function getAvailableTriggers() {
        return array(
            'webhook' => array(
                'name' => 'Webhook',
                'description' => 'Trigger workflow via HTTP webhook',
                'category' => 'developer',
                'parameters' => array('method', 'path', 'authentication')
            ),
            'email' => array(
                'name' => 'Email Received',
                'description' => 'Trigger when email is received',
                'category' => 'communication',
                'parameters' => array('email_address', 'subject_filter')
            ),
            'form_submission' => array(
                'name' => 'Form Submission',
                'description' => 'Trigger when form is submitted',
                'category' => 'marketing',
                'parameters' => array('form_id', 'field_filters')
            ),
            'api_call' => array(
                'name' => 'API Call',
                'description' => 'Trigger via API endpoint',
                'category' => 'developer',
                'parameters' => array('endpoint', 'method', 'headers')
            ),
            'schedule' => array(
                'name' => 'Schedule',
                'description' => 'Trigger on a schedule',
                'category' => 'automation',
                'parameters' => array('frequency', 'time', 'timezone')
            ),
            'database_change' => array(
                'name' => 'Database Change',
                'description' => 'Trigger when database records change',
                'category' => 'data',
                'parameters' => array('table', 'operation', 'conditions')
            )
        );
    }

    private function getAvailableActions() {
        return array(
            'send_email' => array(
                'name' => 'Send Email',
                'description' => 'Send email to specified recipients',
                'category' => 'communication',
                'parameters' => array('to', 'subject', 'body', 'attachments')
            ),
            'create_task' => array(
                'name' => 'Create Task',
                'description' => 'Create a task in project management system',
                'category' => 'productivity',
                'parameters' => array('title', 'description', 'assignee', 'due_date', 'priority')
            ),
            'update_record' => array(
                'name' => 'Update Record',
                'description' => 'Update database record',
                'category' => 'data',
                'parameters' => array('table', 'record_id', 'fields', 'values')
            ),
            'api_request' => array(
                'name' => 'API Request',
                'description' => 'Make HTTP request to external API',
                'category' => 'developer',
                'parameters' => array('url', 'method', 'headers', 'body')
            ),
            'conditional_logic' => array(
                'name' => 'Conditional Logic',
                'description' => 'Execute different paths based on conditions',
                'category' => 'logic',
                'parameters' => array('conditions', 'true_path', 'false_path')
            ),
            'delay' => array(
                'name' => 'Delay',
                'description' => 'Pause workflow execution',
                'category' => 'flow',
                'parameters' => array('duration', 'unit')
            ),
            'send_sms' => array(
                'name' => 'Send SMS',
                'description' => 'Send SMS message',
                'category' => 'communication',
                'parameters' => array('phone_number', 'message')
            ),
            'create_invoice' => array(
                'name' => 'Create Invoice',
                'description' => 'Generate and send invoice',
                'category' => 'finance',
                'parameters' => array('customer_id', 'amount', 'description', 'due_date')
            ),
            'social_post' => array(
                'name' => 'Social Media Post',
                'description' => 'Post to social media platforms',
                'category' => 'marketing',
                'parameters' => array('platform', 'content', 'image', 'schedule')
            ),
            'file_upload' => array(
                'name' => 'File Upload',
                'description' => 'Upload file to cloud storage',
                'category' => 'storage',
                'parameters' => array('file', 'destination', 'folder')
            )
        );
    }

    private function getAvailableModules() {
        return array(
            'ecommerce' => 'Ecommerce',
            'customers' => 'Customer Management',
            'inventory' => 'Inventory',
            'finance' => 'Finance & Accounting',
            'communication' => 'Communication',
            'marketing' => 'Marketing',
            'support' => 'Customer Support',
            'procurement' => 'Procurement',
            'project_management' => 'Project Management',
            'analytics' => 'Analytics'
        );
    }

    // ============================================================================
    // PRIVATE METHODS - BUSINESS LOGIC
    // ============================================================================

    private function processWorkflowCreation() {
        $this->requirePermission('integrations.pabbly.create');

        $data = $this->validateWorkflowData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid workflow data');
            $this->redirect('/integrations/pabbly/create-workflow');
        }

        try {
            $this->db->beginTransaction();

            $workflowId = $this->db->insert('pabbly_workflows', array(
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'template' => $data['template'],
                'trigger_type' => $data['trigger_type'],
                'trigger_config' => json_encode($data['trigger_config']),
                'actions' => json_encode($data['actions']),
                'status' => 'inactive',
                'pabbly_workflow_id' => null, // Will be set when deployed to Pabbly
                'webhook_url' => $this->generateWebhookUrl($data['name']),
                'created_by' => $this->user['id']
            ));

            // Create trigger
            $this->createWorkflowTrigger($workflowId, $data);

            $this->db->commit();

            $this->setFlash('success', 'Pabbly Connect workflow created successfully');
            $this->redirect('/integrations/pabbly');

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to create workflow: ' . $e->getMessage());
            $this->redirect('/integrations/pabbly/create-workflow');
        }
    }

    private function validateWorkflowData($data) {
        if (empty($data['name']) || empty($data['trigger_type'])) {
            return false;
        }

        return array(
            'name' => $data['name'],
            'description' => isset($data['description']) ? $data['description'] : '',
            'template' => isset($data['template']) ? $data['template'] : 'custom',
            'trigger_type' => $data['trigger_type'],
            'trigger_config' => isset($data['trigger_config']) ? $data['trigger_config'] : array(),
            'actions' => isset($data['actions']) ? $data['actions'] : array()
        );
    }

    private function createWorkflowTrigger($workflowId, $data) {
        $triggerId = $this->db->insert('pabbly_triggers', array(
            'company_id' => $this->user['company_id'],
            'workflow_id' => $workflowId,
            'name' => $data['name'] . ' Trigger',
            'trigger_type' => $data['trigger_type'],
            'config' => json_encode($data['trigger_config']),
            'webhook_url' => $this->generateWebhookUrl($data['name']),
            'is_active' => true,
            'created_by' => $this->user['id']
        ));

        // Store webhook secret if needed
        if (isset($data['trigger_config']['authentication']) &&
            $data['trigger_config']['authentication'] !== 'none') {
            $this->db->insert('pabbly_webhook_secrets', array(
                'company_id' => $this->user['company_id'],
                'trigger_id' => $triggerId,
                'secret' => $this->generateWebhookSecret(),
                'created_by' => $this->user['id']
            ));
        }
    }

    private function generateWebhookUrl($workflowName) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $workflowName));
        return "/api/integrations/pabbly/webhook/{$slug}";
    }

    private function generateWebhookSecret() {
        return bin2hex(random_bytes(32));
    }

    private function testPabblyConnection() {
        if (!$this->pabblyConfig) {
            return array('success' => false, 'message' => 'Pabbly Connect not configured');
        }

        // Test API connection
        $response = $this->makePabblyAPIRequest('GET', '/api/v1/account', array(), $this->pabblyConfig);

        if (isset($response['success']) && $response['success']) {
            return array('success' => true, 'message' => 'Connected successfully');
        }

        return array(
            'success' => false,
            'message' => isset($response['message']) ? $response['message'] : 'Connection failed'
        );
    }

    private function makePabblyAPIRequest($method, $endpoint, $data, $config) {
        $baseUrl = rtrim($config['base_url'], '/');
        $url = $baseUrl . $endpoint;

        $headers = array(
            'Api-Key: ' . $config['api_key'],
            'Api-Secret: ' . $config['api_secret'],
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return array('message' => 'API request failed: ' . $error);
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 400) {
            return array(
                'message' => isset($responseData['message']) ? $responseData['message'] : 'API error',
                'code' => $httpCode
            );
        }

        return $responseData;
    }

    private function getWebhookLogs() {
        return $this->db->query("
            SELECT
                pwl.*,
                pt.name as trigger_name,
                pwf.name as workflow_name
            FROM pabbly_webhook_logs pwl
            LEFT JOIN pabbly_triggers pt ON pwl.trigger_id = pt.id
            LEFT JOIN pabbly_workflows pwf ON pt.workflow_id = pwf.id
            WHERE pwl.company_id = ?
            ORDER BY pwl.received_at DESC
            LIMIT 50
        ", array($this->user['company_id']));
    }

    private function getWebhookStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_calls,
                COUNT(CASE WHEN response_status = 200 THEN 1 END) as successful_calls,
                COUNT(CASE WHEN response_status >= 400 THEN 1 END) as failed_calls,
                AVG(response_time_ms) as avg_response_time,
                MAX(received_at) as last_call
            FROM pabbly_webhook_logs
            WHERE company_id = ? AND received_at >= ?
        ", array(
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));
    }

    private function getWebhooks() {
        return $this->db->query("
            SELECT
                pt.*,
                pw.name as workflow_name,
                COUNT(pwl.id) as call_count,
                MAX(pwl.received_at) as last_call
            FROM pabbly_triggers pt
            LEFT JOIN pabbly_workflows pw ON pt.workflow_id = pw.id
            LEFT JOIN pabbly_webhook_logs pwl ON pt.id = pwl.trigger_id
            WHERE pt.company_id = ?
            GROUP BY pt.id, pw.name
            ORDER BY pt.created_at DESC
        ", array($this->user['company_id']));
    }

    private function getExecutions($filters) {
        $where = array("pel.company_id = ?");
        $params = array($this->user['company_id']);

        if ($filters['workflow_id']) {
            $where[] = "pel.workflow_id = ?";
            $params[] = $filters['workflow_id'];
        }

        if ($filters['status'] !== 'all') {
            $where[] = "pel.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['date_from']) {
            $where[] = "pel.executed_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "pel.executed_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                pel.*,
                pw.name as workflow_name
            FROM pabbly_execution_logs pel
            LEFT JOIN pabbly_workflows pw ON pel.workflow_id = pw.id
            WHERE $whereClause
            ORDER BY pel.executed_at DESC
            LIMIT 100
        ", $params);
    }

    private function getExecutionStats($filters) {
        $where = array("company_id = ?");
        $params = array($this->user['company_id']);

        if ($filters['date_from']) {
            $where[] = "executed_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "executed_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_executions,
                COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_executions,
                COUNT(CASE WHEN status = 'error' THEN 1 END) as failed_executions,
                AVG(execution_time_ms) as avg_execution_time,
                MIN(executed_at) as first_execution,
                MAX(executed_at) as last_execution
            FROM pabbly_execution_logs
            WHERE $whereClause
        ", $params);
    }

    // ============================================================================
    // WEBHOOK HANDLING
    // ============================================================================

    public function handleWebhook($webhookSlug) {
        try {
            // Find trigger by webhook slug
            $trigger = $this->db->querySingle("
                SELECT pt.*, pw.name as workflow_name
                FROM pabbly_triggers pt
                JOIN pabbly_workflows pw ON pt.workflow_id = pw.id
                WHERE pt.webhook_url LIKE ? AND pt.company_id = ? AND pt.is_active = true
            ", array("%/{$webhookSlug}", $this->user['company_id']));

            if (!$trigger) {
                $this->jsonResponse(array('error' => 'Webhook not found'), 404);
            }

            // Validate webhook authentication if enabled
            if (!$this->validateWebhookAuth($trigger)) {
                $this->jsonResponse(array('error' => 'Authentication failed'), 401);
            }

            // Parse webhook data
            $webhookData = $this->parseWebhookData();

            // Log webhook call
            $this->logWebhookCall($trigger['id'], $webhookData);

            // Execute workflow
            $result = $this->executeWorkflow($trigger['workflow_id'], $webhookData);

            $this->jsonResponse(array('success' => true, 'result' => $result));

        } catch (Exception $e) {
            $this->logWebhookError($trigger['id'] ?? null, $e->getMessage());
            $this->jsonResponse(array('error' => $e->getMessage()), 500);
        }
    }

    private function validateWebhookAuth($trigger) {
        $config = json_decode($trigger['config'], true);
        $authMethod = isset($config['authentication']) ? $config['authentication'] : 'none';

        switch ($authMethod) {
            case 'api_key':
                $apiKey = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
                return $this->validateApiKey($trigger['id'], $apiKey);

            case 'basic':
                return isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']);

            case 'none':
            default:
                return true;
        }
    }

    private function validateApiKey($triggerId, $apiKey) {
        if (!$apiKey) {
            return false;
        }

        $secret = $this->db->querySingle("
            SELECT secret FROM pabbly_webhook_secrets
            WHERE trigger_id = ? AND company_id = ?
        ", array($triggerId, $this->user['company_id']));

        return $secret && hash_equals($secret['secret'], $apiKey);
    }

    private function parseWebhookData() {
        $input = file_get_contents('php://input');
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

        if (strpos($contentType, 'application/json') !== false) {
            return json_decode($input, true);
        }

        return array('raw_data' => $input);
    }

    private function logWebhookCall($triggerId, $data) {
        $this->db->insert('pabbly_webhook_logs', array(
            'company_id' => $this->user['company_id'],
            'trigger_id' => $triggerId,
            'request_data' => json_encode($data),
            'response_status' => 200,
            'response_time_ms' => 0,
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
        ));
    }

    private function executeWorkflow($workflowId, $data) {
        $workflow = $this->db->querySingle("
            SELECT * FROM pabbly_workflows WHERE id = ? AND company_id = ?
        ", array($workflowId, $this->user['company_id']));

        if (!$workflow) {
            return array('error' => 'Workflow not found');
        }

        // Log execution start
        $executionId = $this->db->insert('pabbly_execution_logs', array(
            'company_id' => $this->user['company_id'],
            'workflow_id' => $workflowId,
            'status' => 'running',
            'input_data' => json_encode($data),
            'executed_at' => date('Y-m-d H:i:s')
        ));

        try {
            $actions = json_decode($workflow['actions'], true);
            $result = $this->processWorkflowActions($actions, $data);

            // Update execution log
            $this->db->update('pabbly_execution_logs', array(
                'status' => 'success',
                'output_data' => json_encode($result),
                'execution_time_ms' => 0 // Could calculate actual time
            ), 'id = ?', array($executionId));

            return $result;

        } catch (Exception $e) {
            // Update execution log with error
            $this->db->update('pabbly_execution_logs', array(
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'execution_time_ms' => 0
            ), 'id = ?', array($executionId));

            throw $e;
        }
    }

    private function processWorkflowActions($actions, $inputData) {
        $results = array();
        $currentData = $inputData;

        foreach ($actions as $action) {
            try {
                $result = $this->executeAction($action, $currentData);
                $results[] = array(
                    'action' => $action['type'],
                    'success' => true,
                    'result' => $result
                );
                $currentData = array_merge($currentData, $result);
            } catch (Exception $e) {
                $results[] = array(
                    'action' => $action['type'],
                    'success' => false,
                    'error' => $e->getMessage()
                );
            }
        }

        return $results;
    }

    private function executeAction($action, $inputData) {
        // Execute action based on type
        switch ($action['type']) {
            case 'send_email':
                return $this->executeSendEmail($action, $inputData);
            case 'create_task':
                return $this->executeCreateTask($action, $inputData);
            case 'update_record':
                return $this->executeUpdateRecord($action, $inputData);
            case 'api_request':
                return $this->executeApiRequest($action, $inputData);
            case 'conditional_logic':
                return $this->executeConditionalLogic($action, $inputData);
            case 'delay':
                return $this->executeDelay($action, $inputData);
            default:
                return array('message' => 'Action type not implemented: ' . $action['type']);
        }
    }

    private function executeSendEmail($action, $inputData) {
        $params = $action['parameters'];

        // Send email using existing email system
        $email = new Email();
        $result = $email->send(
            isset($params['to']) ? $params['to'] : '',
            isset($params['subject']) ? $params['subject'] : '',
            isset($params['body']) ? $params['body'] : ''
        );

        return array(
            'email_sent' => $result,
            'recipient' => isset($params['to']) ? $params['to'] : '',
            'input' => $inputData
        );
    }

    private function executeCreateTask($action, $inputData) {
        $params = $action['parameters'];

        // Create task in project management system
        $taskId = $this->db->insert('tasks', array(
            'company_id' => $this->user['company_id'],
            'title' => isset($params['title']) ? $params['title'] : 'Pabbly Task',
            'description' => isset($params['description']) ? $params['description'] : '',
            'assigned_to' => isset($params['assignee']) ? $params['assignee'] : null,
            'due_date' => isset($params['due_date']) ? $params['due_date'] : null,
            'priority' => isset($params['priority']) ? $params['priority'] : 'medium',
            'status' => 'pending',
            'created_by' => $this->user['id']
        ));

        return array(
            'task_id' => $taskId,
            'task_created' => true,
            'input' => $inputData
        );
    }

    private function executeUpdateRecord($action, $inputData) {
        $params = $action['parameters'];
        $table = isset($params['table']) ? $params['table'] : '';
        $recordId = isset($params['record_id']) ? $params['record_id'] : null;
        $fields = isset($params['fields']) ? $params['fields'] : array();

        if (!$table || !$recordId || empty($fields)) {
            throw new Exception('Invalid update record parameters');
        }

        $this->db->update($table, $fields, 'id = ? AND company_id = ?', array($recordId, $this->user['company_id']));

        return array(
            'record_updated' => true,
            'table' => $table,
            'record_id' => $recordId,
            'input' => $inputData
        );
    }

    private function executeApiRequest($action, $inputData) {
        $params = $action['parameters'];
        $url = isset($params['url']) ? $params['url'] : '';
        $method = isset($params['method']) ? $params['method'] : 'GET';

        if (!$url) {
            throw new Exception('API URL not specified');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (isset($params['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $params['headers']);
        }

        if (isset($params['body']) && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params['body']));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array(
            'response' => json_decode($response, true),
            'status_code' => $httpCode,
            'url' => $url,
            'method' => $method,
            'input' => $inputData
        );
    }

    private function executeConditionalLogic($action, $inputData) {
        $params = $action['parameters'];
        $conditions = isset($params['conditions']) ? $params['conditions'] : array();

        // Simple condition evaluation
        $conditionMet = true;
        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = $condition['value'];

            $fieldValue = isset($inputData[$field]) ? $inputData[$field] : null;

            switch ($operator) {
                case 'equals':
                    $conditionMet = $conditionMet && ($fieldValue == $value);
                    break;
                case 'not_equals':
                    $conditionMet = $conditionMet && ($fieldValue != $value);
                    break;
                case 'greater_than':
                    $conditionMet = $conditionMet && ($fieldValue > $value);
                    break;
                case 'less_than':
                    $conditionMet = $conditionMet && ($fieldValue < $value);
                    break;
                case 'contains':
                    $conditionMet = $conditionMet && strpos($fieldValue, $value) !== false;
                    break;
            }
        }

        return array(
            'condition_met' => $conditionMet,
            'path_taken' => $conditionMet ? 'true' : 'false',
            'input' => $inputData
        );
    }

    private function executeDelay($action, $inputData) {
        $params = $action['parameters'];
        $duration = isset($params['duration']) ? (int)$params['duration'] : 0;
        $unit = isset($params['unit']) ? $params['unit'] : 'seconds';

        // Convert to seconds
        switch ($unit) {
            case 'minutes':
                $duration *= 60;
                break;
            case 'hours':
                $duration *= 3600;
                break;
        }

        // In a real implementation, this would use a job queue
        // For now, just simulate the delay
        if ($duration > 0 && $duration <= 300) { // Max 5 minutes
            sleep($duration);
        }

        return array(
            'delay_executed' => true,
            'duration' => $duration,
            'unit' => $unit,
            'input' => $inputData
        );
    }

    private function logWebhookError($triggerId, $error) {
        $this->db->insert('pabbly_execution_logs', array(
            'company_id' => $this->user['company_id'],
            'workflow_id' => null,
            'trigger_id' => $triggerId,
            'status' => 'error',
            'error_message' => $error,
            'execution_time_ms' => 0,
            'executed_at' => date('Y-m-d H:i:s')
        ));
    }
}
?>
