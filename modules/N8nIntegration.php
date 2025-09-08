<?php
/**
 * TPT Free ERP - n8n Integration Module
 * Complete integration with n8n for open-source workflow automation
 */

class N8nIntegration extends BaseController {
    private $db;
    private $user;
    private $n8nConfig;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->n8nConfig = $this->getN8nConfig();
    }

    /**
     * Main n8n integration dashboard
     */
    public function index() {
        $this->requirePermission('integrations.n8n.view');

        $data = array(
            'title' => 'n8n Integration',
            'workflows' => $this->getWorkflows(),
            'webhooks' => $this->getWebhooks(),
            'workflow_stats' => $this->getWorkflowStats(),
            'recent_activity' => $this->getRecentActivity(),
            'connection_status' => $this->getConnectionStatus()
        );

        $this->render('modules/integrations/n8n/dashboard', $data);
    }

    /**
     * Create new n8n workflow
     */
    public function createWorkflow() {
        $this->requirePermission('integrations.n8n.create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processWorkflowCreation();
        }

        $data = array(
            'title' => 'Create n8n Workflow',
            'templates' => $this->getWorkflowTemplates(),
            'triggers' => $this->getAvailableTriggers(),
            'nodes' => $this->getAvailableNodes(),
            'modules' => $this->getAvailableModules()
        );

        $this->render('modules/integrations/n8n/create_workflow', $data);
    }

    /**
     * Workflow execution monitoring
     */
    public function executions() {
        $this->requirePermission('integrations.n8n.monitoring.view');

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

        $this->render('modules/integrations/n8n/executions', $data);
    }

    /**
     * Webhook management
     */
    public function webhooks() {
        $this->requirePermission('integrations.n8n.webhooks.view');

        $data = array(
            'title' => 'n8n Webhooks',
            'webhooks' => $this->getWebhooks(),
            'webhook_logs' => $this->getWebhookLogs(),
            'webhook_stats' => $this->getWebhookStats()
        );

        $this->render('modules/integrations/n8n/webhooks', $data);
    }

    // ============================================================================
    // PRIVATE METHODS - DATA RETRIEVAL
    // ============================================================================

    private function getN8nConfig() {
        return $this->db->querySingle("
            SELECT * FROM integration_configs
            WHERE provider = 'n8n' AND company_id = ?
        ", array($this->user['company_id']));
    }

    private function getWorkflows() {
        return $this->db->query("
            SELECT
                nw.*,
                COUNT(nel.id) as execution_count,
                MAX(nel.executed_at) as last_execution,
                AVG(nel.execution_time_ms) as avg_execution_time
            FROM n8n_workflows nw
            LEFT JOIN n8n_execution_logs nel ON nw.id = nel.workflow_id
            WHERE nw.company_id = ?
            GROUP BY nw.id
            ORDER BY nw.created_at DESC
        ", array($this->user['company_id']));
    }

    private function getWebhooks() {
        return $this->db->query("
            SELECT
                nw.*,
                nwf.name as workflow_name,
                COUNT(nwl.id) as call_count,
                MAX(nwl.received_at) as last_call
            FROM n8n_webhooks nw
            LEFT JOIN n8n_workflows nwf ON nw.workflow_id = nwf.id
            LEFT JOIN n8n_webhook_logs nwl ON nw.id = nwl.webhook_id
            WHERE nw.company_id = ?
            GROUP BY nw.id, nwf.name
            ORDER BY nw.created_at DESC
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
                    nw.*,
                    COUNT(nel.id) as execution_count,
                    AVG(nel.execution_time_ms) as avg_execution_time
                FROM n8n_workflows nw
                LEFT JOIN n8n_execution_logs nel ON nw.id = nel.workflow_id
                WHERE nw.company_id = ?
                GROUP BY nw.id
            ) workflow_stats
        ", array($this->user['company_id']));
    }

    private function getRecentActivity() {
        return $this->db->query("
            SELECT
                'execution' as activity_type,
                CONCAT('Workflow executed: ', nw.name) as description,
                nel.executed_at as activity_time,
                nel.status,
                nel.execution_time_ms
            FROM n8n_execution_logs nel
            JOIN n8n_workflows nw ON nel.workflow_id = nw.id
            WHERE nel.company_id = ?
            UNION ALL
            SELECT
                'webhook' as activity_type,
                CONCAT('Webhook received: ', nw.name) as description,
                nwl.received_at as activity_time,
                'success' as status,
                NULL as execution_time_ms
            FROM n8n_webhook_logs nwl
            JOIN n8n_webhooks nw ON nwl.webhook_id = nw.id
            WHERE nwl.company_id = ?
            ORDER BY activity_time DESC
            LIMIT 20
        ", array($this->user['company_id'], $this->user['company_id']));
    }

    private function getConnectionStatus() {
        $config = $this->n8nConfig;

        if (!$config) {
            return array(
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'n8n integration not configured'
            );
        }

        // Test connection to n8n API
        $connectionTest = $this->testN8nConnection();

        return array(
            'connected' => $connectionTest['success'],
            'status' => $connectionTest['success'] ? 'connected' : 'error',
            'message' => $connectionTest['message'],
            'last_test' => date('Y-m-d H:i:s')
        );
    }

    private function getWorkflowTemplates() {
        return array(
            'order_processing' => array(
                'name' => 'Order Processing Automation',
                'description' => 'Automate order processing from creation to fulfillment',
                'nodes' => array('webhook', 'http_request', 'set', 'email', 'slack')
            ),
            'customer_onboarding' => array(
                'name' => 'Customer Onboarding Flow',
                'description' => 'Automated customer onboarding and welcome sequence',
                'nodes' => array('webhook', 'airtable', 'email', 'wait', 'slack')
            ),
            'inventory_management' => array(
                'name' => 'Inventory Management',
                'description' => 'Monitor inventory levels and automate reordering',
                'nodes' => array('schedule', 'http_request', 'if', 'email', 'google_sheets')
            ),
            'marketing_campaign' => array(
                'name' => 'Marketing Campaign Automation',
                'description' => 'Automate marketing campaigns and lead nurturing',
                'nodes' => array('webhook', 'segment', 'email', 'wait', 'mixpanel')
            )
        );
    }

    private function getAvailableTriggers() {
        return array(
            'webhook' => array(
                'name' => 'Webhook',
                'description' => 'Trigger workflow via HTTP webhook',
                'category' => 'trigger',
                'parameters' => array('method', 'path', 'authentication')
            ),
            'schedule' => array(
                'name' => 'Schedule',
                'description' => 'Trigger workflow on a schedule',
                'category' => 'trigger',
                'parameters' => array('cron_expression', 'timezone')
            ),
            'email' => array(
                'name' => 'Email Trigger',
                'description' => 'Trigger workflow when email is received',
                'category' => 'trigger',
                'parameters' => array('email_address', 'subject_filter')
            ),
            'database_change' => array(
                'name' => 'Database Change',
                'description' => 'Trigger when database records change',
                'category' => 'trigger',
                'parameters' => array('table', 'operation', 'conditions')
            )
        );
    }

    private function getAvailableNodes() {
        return array(
            'http_request' => array(
                'name' => 'HTTP Request',
                'description' => 'Make HTTP requests to external APIs',
                'category' => 'action',
                'parameters' => array('url', 'method', 'headers', 'body')
            ),
            'set' => array(
                'name' => 'Set',
                'description' => 'Set workflow data values',
                'category' => 'data',
                'parameters' => array('values', 'options')
            ),
            'if' => array(
                'name' => 'IF',
                'description' => 'Conditional logic for workflow branching',
                'category' => 'logic',
                'parameters' => array('conditions', 'true_branch', 'false_branch')
            ),
            'email' => array(
                'name' => 'Send Email',
                'description' => 'Send email messages',
                'category' => 'communication',
                'parameters' => array('to', 'subject', 'body', 'attachments')
            ),
            'slack' => array(
                'name' => 'Slack',
                'description' => 'Send messages to Slack channels',
                'category' => 'communication',
                'parameters' => array('channel', 'message', 'attachments')
            ),
            'airtable' => array(
                'name' => 'Airtable',
                'description' => 'Read/write data from Airtable bases',
                'category' => 'database',
                'parameters' => array('base_id', 'table_name', 'operation')
            ),
            'google_sheets' => array(
                'name' => 'Google Sheets',
                'description' => 'Read/write data from Google Sheets',
                'category' => 'spreadsheet',
                'parameters' => array('spreadsheet_id', 'sheet_name', 'operation')
            ),
            'wait' => array(
                'name' => 'Wait',
                'description' => 'Pause workflow execution',
                'category' => 'flow',
                'parameters' => array('duration', 'unit')
            ),
            'segment' => array(
                'name' => 'Segment',
                'description' => 'Send data to Segment for analytics',
                'category' => 'analytics',
                'parameters' => array('event_name', 'properties', 'user_id')
            ),
            'mixpanel' => array(
                'name' => 'Mixpanel',
                'description' => 'Track events in Mixpanel',
                'category' => 'analytics',
                'parameters' => array('event_name', 'properties', 'distinct_id')
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
        $this->requirePermission('integrations.n8n.create');

        $data = $this->validateWorkflowData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid workflow data');
            $this->redirect('/integrations/n8n/create-workflow');
        }

        try {
            $this->db->beginTransaction();

            $workflowId = $this->db->insert('n8n_workflows', array(
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'template' => $data['template'],
                'nodes' => json_encode($data['nodes']),
                'connections' => json_encode($data['connections']),
                'settings' => json_encode($data['settings']),
                'status' => 'inactive',
                'n8n_workflow_id' => null, // Will be set when deployed to n8n
                'created_by' => $this->user['id']
            ));

            // Create webhooks for trigger nodes
            $this->createWorkflowWebhooks($workflowId, $data['nodes']);

            $this->db->commit();

            $this->setFlash('success', 'n8n workflow created successfully');
            $this->redirect('/integrations/n8n');

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to create workflow: ' . $e->getMessage());
            $this->redirect('/integrations/n8n/create-workflow');
        }
    }

    private function validateWorkflowData($data) {
        if (empty($data['name']) || empty($data['nodes'])) {
            return false;
        }

        return array(
            'name' => $data['name'],
            'description' => isset($data['description']) ? $data['description'] : '',
            'template' => isset($data['template']) ? $data['template'] : 'custom',
            'nodes' => $data['nodes'],
            'connections' => isset($data['connections']) ? $data['connections'] : array(),
            'settings' => isset($data['settings']) ? $data['settings'] : array()
        );
    }

    private function createWorkflowWebhooks($workflowId, $nodes) {
        foreach ($nodes as $node) {
            if ($node['type'] === 'webhook') {
                $webhookId = $this->db->insert('n8n_webhooks', array(
                    'company_id' => $this->user['company_id'],
                    'workflow_id' => $workflowId,
                    'name' => $node['name'] . ' Webhook',
                    'webhook_url' => $this->generateWebhookUrl($node['name']),
                    'method' => isset($node['parameters']['method']) ? $node['parameters']['method'] : 'POST',
                    'authentication' => isset($node['parameters']['authentication']) ? $node['parameters']['authentication'] : 'none',
                    'is_active' => true,
                    'created_by' => $this->user['id']
                ));

                // Store webhook secret if authentication is enabled
                if (isset($node['parameters']['authentication']) && $node['parameters']['authentication'] !== 'none') {
                    $this->db->insert('n8n_webhook_secrets', array(
                        'company_id' => $this->user['company_id'],
                        'webhook_id' => $webhookId,
                        'secret' => $this->generateWebhookSecret(),
                        'created_by' => $this->user['id']
                    ));
                }
            }
        }
    }

    private function generateWebhookUrl($webhookName) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $webhookName));
        return "/api/integrations/n8n/webhook/{$slug}";
    }

    private function generateWebhookSecret() {
        return bin2hex(random_bytes(32));
    }

    private function testN8nConnection() {
        if (!$this->n8nConfig) {
            return array('success' => false, 'message' => 'n8n not configured');
        }

        // Test API connection
        $response = $this->makeN8nAPIRequest('GET', '/rest/workflows', array(), $this->n8nConfig);

        if (isset($response['data'])) {
            return array('success' => true, 'message' => 'Connected successfully');
        }

        return array(
            'success' => false,
            'message' => isset($response['message']) ? $response['message'] : 'Connection failed'
        );
    }

    private function makeN8nAPIRequest($method, $endpoint, $data, $config) {
        $baseUrl = rtrim($config['base_url'], '/');
        $url = $baseUrl . $endpoint;

        $headers = array(
            'X-N8N-API-KEY: ' . $config['api_key'],
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
                nwl.*,
                nw.name as webhook_name,
                nwf.name as workflow_name
            FROM n8n_webhook_logs nwl
            LEFT JOIN n8n_webhooks nw ON nwl.webhook_id = nw.id
            LEFT JOIN n8n_workflows nwf ON nw.workflow_id = nwf.id
            WHERE nwl.company_id = ?
            ORDER BY nwl.received_at DESC
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
            FROM n8n_webhook_logs
            WHERE company_id = ? AND received_at >= ?
        ", array(
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));
    }

    private function getExecutions($filters) {
        $where = array("nel.company_id = ?");
        $params = array($this->user['company_id']);

        if ($filters['workflow_id']) {
            $where[] = "nel.workflow_id = ?";
            $params[] = $filters['workflow_id'];
        }

        if ($filters['status'] !== 'all') {
            $where[] = "nel.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['date_from']) {
            $where[] = "nel.executed_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "nel.executed_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                nel.*,
                nw.name as workflow_name
            FROM n8n_execution_logs nel
            LEFT JOIN n8n_workflows nw ON nel.workflow_id = nw.id
            WHERE $whereClause
            ORDER BY nel.executed_at DESC
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
            FROM n8n_execution_logs
            WHERE $whereClause
        ", $params);
    }

    // ============================================================================
    // WEBHOOK HANDLING
    // ============================================================================

    public function handleWebhook($webhookSlug) {
        try {
            // Find webhook by slug
            $webhook = $this->db->querySingle("
                SELECT nw.*, nwf.name as workflow_name
                FROM n8n_webhooks nw
                JOIN n8n_workflows nwf ON nw.workflow_id = nwf.id
                WHERE nw.webhook_url LIKE ? AND nw.company_id = ? AND nw.is_active = true
            ", array("%/{$webhookSlug}", $this->user['company_id']));

            if (!$webhook) {
                $this->jsonResponse(array('error' => 'Webhook not found'), 404);
            }

            // Validate webhook authentication if enabled
            if (!$this->validateWebhookAuth($webhook)) {
                $this->jsonResponse(array('error' => 'Authentication failed'), 401);
            }

            // Parse webhook data
            $webhookData = $this->parseWebhookData();

            // Log webhook call
            $this->logWebhookCall($webhook['id'], $webhookData);

            // Execute workflow
            $result = $this->executeWorkflow($webhook['workflow_id'], $webhookData);

            $this->jsonResponse(array('success' => true, 'result' => $result));

        } catch (Exception $e) {
            $this->logWebhookError($webhook['id'] ?? null, $e->getMessage());
            $this->jsonResponse(array('error' => $e->getMessage()), 500);
        }
    }

    private function validateWebhookAuth($webhook) {
        $authMethod = $webhook['authentication'];

        switch ($authMethod) {
            case 'header':
                $apiKey = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
                return $this->validateApiKey($webhook['id'], $apiKey);

            case 'basic':
                return isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']);

            case 'none':
            default:
                return true;
        }
    }

    private function validateApiKey($webhookId, $apiKey) {
        if (!$apiKey) {
            return false;
        }

        $secret = $this->db->querySingle("
            SELECT secret FROM n8n_webhook_secrets
            WHERE webhook_id = ? AND company_id = ?
        ", array($webhookId, $this->user['company_id']));

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

    private function logWebhookCall($webhookId, $data) {
        $this->db->insert('n8n_webhook_logs', array(
            'company_id' => $this->user['company_id'],
            'webhook_id' => $webhookId,
            'request_data' => json_encode($data),
            'response_status' => 200,
            'response_time_ms' => 0,
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
        ));
    }

    private function executeWorkflow($workflowId, $data) {
        $workflow = $this->db->querySingle("
            SELECT * FROM n8n_workflows WHERE id = ? AND company_id = ?
        ", array($workflowId, $this->user['company_id']));

        if (!$workflow) {
            return array('error' => 'Workflow not found');
        }

        // Log execution start
        $executionId = $this->db->insert('n8n_execution_logs', array(
            'company_id' => $this->user['company_id'],
            'workflow_id' => $workflowId,
            'status' => 'running',
            'input_data' => json_encode($data),
            'executed_at' => date('Y-m-d H:i:s')
        ));

        try {
            $nodes = json_decode($workflow['nodes'], true);
            $connections = json_decode($workflow['connections'], true);

            $result = $this->processWorkflowNodes($nodes, $connections, $data);

            // Update execution log
            $this->db->update('n8n_execution_logs', array(
                'status' => 'success',
                'output_data' => json_encode($result),
                'execution_time_ms' => 0 // Could calculate actual time
            ), 'id = ?', array($executionId));

            return $result;

        } catch (Exception $e) {
            // Update execution log with error
            $this->db->update('n8n_execution_logs', array(
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'execution_time_ms' => 0
            ), 'id = ?', array($executionId));

            throw $e;
        }
    }

    private function processWorkflowNodes($nodes, $connections, $inputData) {
        $nodeResults = array();
        $currentData = $inputData;

        // Simple linear execution for now (could be enhanced with complex flow logic)
        foreach ($nodes as $node) {
            try {
                $result = $this->executeNode($node, $currentData);
                $nodeResults[$node['id']] = $result;
                $currentData = $result;
            } catch (Exception $e) {
                throw new Exception('Node execution failed: ' . $node['name'] . ' - ' . $e->getMessage());
            }
        }

        return $nodeResults;
    }

    private function executeNode($node, $inputData) {
        // Execute node based on type
        switch ($node['type']) {
            case 'set':
                return $this->executeSetNode($node, $inputData);
            case 'http_request':
                return $this->executeHttpRequestNode($node, $inputData);
            case 'email':
                return $this->executeEmailNode($node, $inputData);
            case 'if':
                return $this->executeIfNode($node, $inputData);
            default:
                return $inputData; // Pass through for unknown node types
        }
    }

    private function executeSetNode($node, $inputData) {
        $values = isset($node['parameters']['values']) ? $node['parameters']['values'] : array();
        return array_merge($inputData, $values);
    }

    private function executeHttpRequestNode($node, $inputData) {
        $params = $node['parameters'];
        $url = isset($params['url']) ? $params['url'] : '';
        $method = isset($params['method']) ? $params['method'] : 'GET';

        // Make HTTP request
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
            'input' => $inputData
        );
    }

    private function executeEmailNode($node, $inputData) {
        $params = $node['parameters'];

        // Send email using existing email system
        $email = new Email();
        $result = $email->send(
            isset($params['to']) ? $params['to'] : '',
            isset($params['subject']) ? $params['subject'] : '',
            isset($params['body']) ? $params['body'] : ''
        );

        return array(
            'email_sent' => $result,
            'input' => $inputData
        );
    }

    private function executeIfNode($node, $inputData) {
        $params = $node['parameters'];
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
            }
        }

        return array(
            'condition_met' => $conditionMet,
            'input' => $inputData
        );
    }

    private function logWebhookError($webhookId, $error) {
        $this->db->insert('n8n_execution_logs', array(
            'company_id' => $this->user['company_id'],
            'workflow_id' => null,
            'webhook_id' => $webhookId,
            'status' => 'error',
            'error_message' => $error,
            'execution_time_ms' => 0,
            'executed_at' => date('Y-m-d H:i:s')
        ));
    }
}
?>
