<?php
/**
 * TPT Free ERP - Make.com Integration Module
 * Complete integration with Make.com (formerly Integromat) for visual workflow automation
 */

class MakeIntegration extends BaseController {
    private $db;
    private $user;
    private $makeConfig;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->makeConfig = $this->getMakeConfig();
    }

    /**
     * Main Make.com integration dashboard
     */
    public function index() {
        $this->requirePermission('integrations.make.view');

        $data = array(
            'title' => 'Make.com Integration',
            'scenarios' => $this->getScenarios(),
            'webhooks' => $this->getWebhooks(),
            'scenario_stats' => $this->getScenarioStats(),
            'recent_activity' => $this->getRecentActivity(),
            'connection_status' => $this->getConnectionStatus()
        );

        $this->render('modules/integrations/make/dashboard', $data);
    }

    /**
     * Create new Make.com scenario
     */
    public function createScenario() {
        $this->requirePermission('integrations.make.create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processScenarioCreation();
        }

        $data = array(
            'title' => 'Create Make.com Scenario',
            'templates' => $this->getScenarioTemplates(),
            'triggers' => $this->getAvailableTriggers(),
            'actions' => $this->getAvailableActions(),
            'modules' => $this->getAvailableModules()
        );

        $this->render('modules/integrations/make/create_scenario', $data);
    }

    /**
     * Webhook management
     */
    public function webhooks() {
        $this->requirePermission('integrations.make.webhooks.view');

        $data = array(
            'title' => 'Make.com Webhooks',
            'webhooks' => $this->getWebhooks(),
            'webhook_logs' => $this->getWebhookLogs(),
            'webhook_stats' => $this->getWebhookStats()
        );

        $this->render('modules/integrations/make/webhooks', $data);
    }

    /**
     * Data mapping and transformation
     */
    public function mapping() {
        $this->requirePermission('integrations.make.mapping.view');

        $data = array(
            'title' => 'Data Mapping',
            'mappings' => $this->getDataMappings(),
            'transformations' => $this->getDataTransformations(),
            'mapping_templates' => $this->getMappingTemplates()
        );

        $this->render('modules/integrations/make/mapping', $data);
    }

    /**
     * Scenario monitoring and logs
     */
    public function monitoring() {
        $this->requirePermission('integrations.make.monitoring.view');

        $filters = array(
            'scenario_id' => isset($_GET['scenario_id']) ? $_GET['scenario_id'] : null,
            'status' => isset($_GET['status']) ? $_GET['status'] : 'all',
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-7 days')),
            'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d')
        );

        $logs = $this->getExecutionLogs($filters);

        $data = array(
            'title' => 'Scenario Monitoring',
            'logs' => $logs,
            'filters' => $filters,
            'scenarios' => $this->getScenarios(),
            'execution_stats' => $this->getExecutionStats($filters)
        );

        $this->render('modules/integrations/make/monitoring', $data);
    }

    // ============================================================================
    // PRIVATE METHODS - DATA RETRIEVAL
    // ============================================================================

    private function getMakeConfig() {
        return $this->db->querySingle("
            SELECT * FROM integration_configs
            WHERE provider = 'make' AND company_id = ?
        ", array($this->user['company_id']));
    }

    private function getScenarios() {
        return $this->db->query("
            SELECT
                ms.*,
                COUNT(mel.id) as execution_count,
                MAX(mel.executed_at) as last_execution,
                AVG(mel.execution_time_ms) as avg_execution_time
            FROM make_scenarios ms
            LEFT JOIN make_execution_logs mel ON ms.id = mel.scenario_id
            WHERE ms.company_id = ?
            GROUP BY ms.id
            ORDER BY ms.created_at DESC
        ", array($this->user['company_id']));
    }

    private function getWebhooks() {
        return $this->db->query("
            SELECT
                mw.*,
                ms.name as scenario_name,
                COUNT(mwl.id) as call_count,
                MAX(mwl.received_at) as last_call
            FROM make_webhooks mw
            LEFT JOIN make_scenarios ms ON mw.scenario_id = ms.id
            LEFT JOIN make_webhook_logs mwl ON mw.id = mwl.webhook_id
            WHERE mw.company_id = ?
            GROUP BY mw.id, ms.name
            ORDER BY mw.created_at DESC
        ", array($this->user['company_id']));
    }

    private function getScenarioStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_scenarios,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_scenarios,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_scenarios,
                SUM(execution_count) as total_executions,
                AVG(avg_execution_time) as avg_execution_time
            FROM (
                SELECT
                    ms.*,
                    COUNT(mel.id) as execution_count,
                    AVG(mel.execution_time_ms) as avg_execution_time
                FROM make_scenarios ms
                LEFT JOIN make_execution_logs mel ON ms.id = mel.scenario_id
                WHERE ms.company_id = ?
                GROUP BY ms.id
            ) scenario_stats
        ", array($this->user['company_id']));
    }

    private function getRecentActivity() {
        return $this->db->query("
            SELECT
                'execution' as activity_type,
                CONCAT('Scenario executed: ', ms.name) as description,
                mel.executed_at as activity_time,
                mel.status,
                mel.execution_time_ms
            FROM make_execution_logs mel
            JOIN make_scenarios ms ON mel.scenario_id = ms.id
            WHERE mel.company_id = ?
            UNION ALL
            SELECT
                'webhook' as activity_type,
                CONCAT('Webhook received: ', mw.name) as description,
                mwl.received_at as activity_time,
                'success' as status,
                NULL as execution_time_ms
            FROM make_webhook_logs mwl
            JOIN make_webhooks mw ON mwl.webhook_id = mw.id
            WHERE mwl.company_id = ?
            ORDER BY activity_time DESC
            LIMIT 20
        ", array($this->user['company_id'], $this->user['company_id']));
    }

    private function getConnectionStatus() {
        $config = $this->makeConfig;

        if (!$config) {
            return array(
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'Make.com integration not configured'
            );
        }

        // Test connection to Make.com API
        $connectionTest = $this->testMakeConnection();

        return array(
            'connected' => $connectionTest['success'],
            'status' => $connectionTest['success'] ? 'connected' : 'error',
            'message' => $connectionTest['message'],
            'last_test' => date('Y-m-d H:i:s')
        );
    }

    private function getScenarioTemplates() {
        return array(
            'ecommerce_order' => array(
                'name' => 'Ecommerce Order Processing',
                'description' => 'Process new ecommerce orders and update inventory',
                'triggers' => array('new_order'),
                'actions' => array('update_inventory', 'send_notification', 'create_invoice')
            ),
            'customer_sync' => array(
                'name' => 'Customer Data Sync',
                'description' => 'Sync customer data between systems',
                'triggers' => array('customer_created', 'customer_updated'),
                'actions' => array('sync_to_crm', 'update_newsletter', 'create_support_ticket')
            ),
            'inventory_alert' => array(
                'name' => 'Inventory Alert System',
                'description' => 'Monitor inventory levels and send alerts',
                'triggers' => array('low_stock', 'out_of_stock'),
                'actions' => array('send_alert', 'create_purchase_order', 'update_supplier')
            ),
            'marketing_automation' => array(
                'name' => 'Marketing Automation',
                'description' => 'Automate marketing campaigns and customer engagement',
                'triggers' => array('customer_signup', 'order_completed', 'abandoned_cart'),
                'actions' => array('send_email', 'update_segment', 'create_task')
            )
        );
    }

    private function getAvailableTriggers() {
        return array(
            'new_order' => array(
                'name' => 'New Order',
                'description' => 'Triggers when a new order is created',
                'module' => 'ecommerce',
                'fields' => array('order_id', 'customer_id', 'total_amount', 'items')
            ),
            'customer_created' => array(
                'name' => 'Customer Created',
                'description' => 'Triggers when a new customer is created',
                'module' => 'customers',
                'fields' => array('customer_id', 'email', 'first_name', 'last_name')
            ),
            'customer_updated' => array(
                'name' => 'Customer Updated',
                'description' => 'Triggers when customer data is updated',
                'module' => 'customers',
                'fields' => array('customer_id', 'updated_fields')
            ),
            'low_stock' => array(
                'name' => 'Low Stock Alert',
                'description' => 'Triggers when product stock is low',
                'module' => 'inventory',
                'fields' => array('product_id', 'current_stock', 'threshold')
            ),
            'out_of_stock' => array(
                'name' => 'Out of Stock',
                'description' => 'Triggers when product is out of stock',
                'module' => 'inventory',
                'fields' => array('product_id', 'sku')
            ),
            'customer_signup' => array(
                'name' => 'Customer Signup',
                'description' => 'Triggers when a customer signs up',
                'module' => 'customers',
                'fields' => array('customer_id', 'signup_source', 'email')
            ),
            'order_completed' => array(
                'name' => 'Order Completed',
                'description' => 'Triggers when an order is completed',
                'module' => 'ecommerce',
                'fields' => array('order_id', 'total_amount', 'payment_method')
            ),
            'abandoned_cart' => array(
                'name' => 'Abandoned Cart',
                'description' => 'Triggers when a cart is abandoned',
                'module' => 'ecommerce',
                'fields' => array('cart_id', 'customer_id', 'cart_value', 'last_activity')
            )
        );
    }

    private function getAvailableActions() {
        return array(
            'update_inventory' => array(
                'name' => 'Update Inventory',
                'description' => 'Update product inventory levels',
                'module' => 'inventory',
                'fields' => array('product_id', 'quantity_change', 'reason')
            ),
            'send_notification' => array(
                'name' => 'Send Notification',
                'description' => 'Send email or SMS notification',
                'module' => 'communication',
                'fields' => array('recipient', 'subject', 'message', 'type')
            ),
            'create_invoice' => array(
                'name' => 'Create Invoice',
                'description' => 'Create an invoice for an order',
                'module' => 'finance',
                'fields' => array('order_id', 'customer_id', 'amount', 'due_date')
            ),
            'sync_to_crm' => array(
                'name' => 'Sync to CRM',
                'description' => 'Sync customer data to CRM system',
                'module' => 'crm',
                'fields' => array('customer_data', 'crm_system', 'sync_fields')
            ),
            'update_newsletter' => array(
                'name' => 'Update Newsletter',
                'description' => 'Add/update customer in newsletter system',
                'module' => 'marketing',
                'fields' => array('customer_id', 'email', 'subscription_status')
            ),
            'create_support_ticket' => array(
                'name' => 'Create Support Ticket',
                'description' => 'Create a support ticket for the customer',
                'module' => 'support',
                'fields' => array('customer_id', 'subject', 'description', 'priority')
            ),
            'send_alert' => array(
                'name' => 'Send Alert',
                'description' => 'Send alert to specified recipients',
                'module' => 'communication',
                'fields' => array('recipients', 'alert_type', 'message', 'priority')
            ),
            'create_purchase_order' => array(
                'name' => 'Create Purchase Order',
                'description' => 'Create a purchase order for low stock items',
                'module' => 'procurement',
                'fields' => array('product_id', 'supplier_id', 'quantity', 'due_date')
            ),
            'update_supplier' => array(
                'name' => 'Update Supplier',
                'description' => 'Notify supplier about low stock',
                'module' => 'procurement',
                'fields' => array('supplier_id', 'product_id', 'message')
            ),
            'send_email' => array(
                'name' => 'Send Email',
                'description' => 'Send marketing or transactional email',
                'module' => 'communication',
                'fields' => array('template', 'recipient', 'variables')
            ),
            'update_segment' => array(
                'name' => 'Update Customer Segment',
                'description' => 'Update customer segment in marketing system',
                'module' => 'marketing',
                'fields' => array('customer_id', 'segment', 'reason')
            ),
            'create_task' => array(
                'name' => 'Create Task',
                'description' => 'Create a task for team members',
                'module' => 'project_management',
                'fields' => array('title', 'description', 'assignee', 'due_date', 'priority')
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
            'crm' => 'CRM'
        );
    }

    // ============================================================================
    // PRIVATE METHODS - BUSINESS LOGIC
    // ============================================================================

    private function processScenarioCreation() {
        $this->requirePermission('integrations.make.create');

        $data = $this->validateScenarioData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid scenario data');
            $this->redirect('/integrations/make/create-scenario');
        }

        try {
            $this->db->beginTransaction();

            $scenarioId = $this->db->insert('make_scenarios', array(
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'template' => $data['template'],
                'triggers' => json_encode($data['triggers']),
                'actions' => json_encode($data['actions']),
                'status' => 'inactive',
                'make_scenario_id' => null, // Will be set when published to Make.com
                'webhook_url' => $this->generateWebhookUrl($data['name']),
                'created_by' => $this->user['id']
            ));

            // Create webhooks for triggers
            $this->createScenarioWebhooks($scenarioId, $data['triggers']);

            $this->db->commit();

            $this->setFlash('success', 'Make.com scenario created successfully');
            $this->redirect('/integrations/make');

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to create scenario: ' . $e->getMessage());
            $this->redirect('/integrations/make/create-scenario');
        }
    }

    private function validateScenarioData($data) {
        if (empty($data['name']) || empty($data['triggers']) || empty($data['actions'])) {
            return false;
        }

        return array(
            'name' => $data['name'],
            'description' => isset($data['description']) ? $data['description'] : '',
            'template' => isset($data['template']) ? $data['template'] : 'custom',
            'triggers' => $data['triggers'],
            'actions' => $data['actions']
        );
    }

    private function generateWebhookUrl($scenarioName) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $scenarioName));
        return "/api/integrations/make/webhook/{$slug}";
    }

    private function createScenarioWebhooks($scenarioId, $triggers) {
        foreach ($triggers as $trigger) {
            $webhookId = $this->db->insert('make_webhooks', array(
                'company_id' => $this->user['company_id'],
                'scenario_id' => $scenarioId,
                'name' => $trigger['name'] . ' Webhook',
                'trigger_type' => $trigger['type'],
                'webhook_url' => $this->generateWebhookUrl($trigger['name']),
                'is_active' => true,
                'created_by' => $this->user['id']
            ));

            // Store webhook secret
            $this->db->insert('make_webhook_secrets', array(
                'company_id' => $this->user['company_id'],
                'webhook_id' => $webhookId,
                'secret' => $this->generateWebhookSecret(),
                'created_by' => $this->user['id']
            ));
        }
    }

    private function generateWebhookSecret() {
        return bin2hex(random_bytes(32));
    }

    private function testMakeConnection() {
        if (!$this->makeConfig) {
            return array('success' => false, 'message' => 'Make.com not configured');
        }

        // Test API connection
        $response = $this->makeAPIRequest('GET', 'https://api.make.com/v2/users/me', array(), $this->makeConfig);

        if (isset($response['id'])) {
            return array('success' => true, 'message' => 'Connected successfully');
        }

        return array(
            'success' => false,
            'message' => isset($response['error']) ? $response['error']['message'] : 'Connection failed'
        );
    }

    private function makeAPIRequest($method, $url, $data, $config) {
        $headers = array(
            'Authorization: Token ' . $config['api_token'],
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
            return array('error' => array('message' => 'API request failed: ' . $error));
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 400) {
            return array(
                'error' => array(
                    'message' => isset($responseData['message']) ? $responseData['message'] : 'API error',
                    'code' => $httpCode
                )
            );
        }

        return $responseData;
    }

    private function getWebhookLogs() {
        return $this->db->query("
            SELECT
                mwl.*,
                mw.name as webhook_name,
                ms.name as scenario_name
            FROM make_webhook_logs mwl
            LEFT JOIN make_webhooks mw ON mwl.webhook_id = mw.id
            LEFT JOIN make_scenarios ms ON mw.scenario_id = ms.id
            WHERE mwl.company_id = ?
            ORDER BY mwl.received_at DESC
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
            FROM make_webhook_logs
            WHERE company_id = ? AND received_at >= ?
        ", array(
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));
    }

    private function getDataMappings() {
        return $this->db->query("
            SELECT
                mdm.*,
                ms.name as scenario_name
            FROM make_data_mappings mdm
            LEFT JOIN make_scenarios ms ON mdm.scenario_id = ms.id
            WHERE mdm.company_id = ?
            ORDER BY mdm.created_at DESC
        ", array($this->user['company_id']));
    }

    private function getDataTransformations() {
        return $this->db->query("
            SELECT
                mdt.*,
                ms.name as scenario_name
            FROM make_data_transformations mdt
            LEFT JOIN make_scenarios ms ON mdt.scenario_id = ms.id
            WHERE mdt.company_id = ?
            ORDER BY mdt.created_at DESC
        ", array($this->user['company_id']));
    }

    private function getMappingTemplates() {
        return $this->db->query("
            SELECT * FROM make_mapping_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY name ASC
        ", array($this->user['company_id']));
    }

    private function getExecutionLogs($filters) {
        $where = array("mel.company_id = ?");
        $params = array($this->user['company_id']);

        if ($filters['scenario_id']) {
            $where[] = "mel.scenario_id = ?";
            $params[] = $filters['scenario_id'];
        }

        if ($filters['status'] !== 'all') {
            $where[] = "mel.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['date_from']) {
            $where[] = "mel.executed_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "mel.executed_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                mel.*,
                ms.name as scenario_name
            FROM make_execution_logs mel
            LEFT JOIN make_scenarios ms ON mel.scenario_id = ms.id
            WHERE $whereClause
            ORDER BY mel.executed_at DESC
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
            FROM make_execution_logs
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
                SELECT mw.*, ms.name as scenario_name
                FROM make_webhooks mw
                JOIN make_scenarios ms ON mw.scenario_id = ms.id
                WHERE mw.webhook_url LIKE ? AND mw.company_id = ? AND mw.is_active = true
            ", array("%/{$webhookSlug}", $this->user['company_id']));

            if (!$webhook) {
                $this->jsonResponse(array('error' => 'Webhook not found'), 404);
            }

            // Validate webhook signature
            if (!$this->validateWebhookSignature($webhook)) {
                $this->jsonResponse(array('error' => 'Invalid signature'), 401);
            }

            // Parse webhook data
            $webhookData = $this->parseWebhookData();

            // Log webhook call
            $this->logWebhookCall($webhook['id'], $webhookData);

            // Process webhook based on trigger type
            $result = $this->processWebhookTrigger($webhook, $webhookData);

            // Execute scenario actions
            $this->executeScenarioActions($webhook['scenario_id'], $result);

            $this->jsonResponse(array('success' => true, 'processed' => true));

        } catch (Exception $e) {
            $this->logWebhookError($webhook['id'] ?? null, $e->getMessage());
            $this->jsonResponse(array('error' => $e->getMessage()), 500);
        }
    }

    private function validateWebhookSignature($webhook) {
        $signature = isset($_SERVER['HTTP_X_MAKE_SIGNATURE']) ? $_SERVER['HTTP_X_MAKE_SIGNATURE'] : '';

        if (!$signature) {
            return false;
        }

        // Get webhook secret
        $secret = $this->db->querySingle("
            SELECT secret FROM make_webhook_secrets
            WHERE webhook_id = ? AND company_id = ?
        ", array($webhook['id'], $this->user['company_id']));

        if (!$secret) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', file_get_contents('php://input'), $secret['secret']);

        return hash_equals($expectedSignature, $signature);
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
        $this->db->insert('make_webhook_logs', array(
            'company_id' => $this->user['company_id'],
            'webhook_id' => $webhookId,
            'request_data' => json_encode($data),
            'response_status' => 200,
            'response_time_ms' => 0, // Could be calculated
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
        ));
    }

    private function processWebhookTrigger($webhook, $data) {
        // Process webhook based on trigger type
        switch ($webhook['trigger_type']) {
            case 'new_order':
                return $this->processNewOrderTrigger($data);
            case 'customer_created':
                return $this->processCustomerCreatedTrigger($data);
            case 'low_stock':
                return $this->processLowStockTrigger($data);
            default:
                return $data; // Return data as-is for custom processing
        }
    }

    private function executeScenarioActions($scenarioId, $data) {
        $scenario = $this->db->querySingle("
            SELECT * FROM make_scenarios WHERE id = ? AND company_id = ?
        ", array($scenarioId, $this->user['company_id']));

        if (!$scenario) {
            return;
        }

        $actions = json_decode($scenario['actions'], true);

        foreach ($actions as $action) {
            try {
                $this->executeAction($action, $data);
            } catch (Exception $e) {
                // Log action error
                $this->logExecutionError($scenarioId, $action, $e->getMessage());
            }
        }
    }

    private function executeAction($action, $data) {
        // Execute action based on type
        switch ($action['type']) {
            case 'update_inventory':
                $this->executeUpdateInventory($action, $data);
                break;
            case 'send_notification':
                $this->executeSendNotification($action, $data);
                break;
            case 'create_invoice':
                $this->executeCreateInvoice($action, $data);
                break;
            // Add more action types as needed
        }
    }

    private function logWebhookError($webhookId, $error) {
        $this->db->insert('make_execution_logs', array(
            'company_id' => $this->user['company_id'],
            'scenario_id' => null,
            'webhook_id' => $webhookId,
            'status' => 'error',
            'error_message' => $error,
            'execution_time_ms' => 0,
            'executed_at' => date('Y-m-d H:i:s')
        ));
    }

    private function logExecutionError($scenarioId, $action, $error) {
        $this->db->insert('make_execution_logs', array(
            'company_id' => $this->user['company_id'],
            'scenario_id' => $scenarioId,
            'status' => 'error',
            'error_message' => 'Action failed: ' . $action['type'] . ' - ' . $error,
            'execution_time_ms' => 0,
            'executed_at' => date('Y-m-d H:i:s')
        ));
    }

    // Placeholder methods for action execution
    private function executeUpdateInventory($action, $data) { /* Implementation */ }
    private function executeSendNotification($action, $data) { /* Implementation */ }
    private function executeCreateInvoice($action, $data) { /* Implementation */ }

    // Placeholder methods for trigger processing
    private function processNewOrderTrigger($data) { return $data; }
    private function processCustomerCreatedTrigger($data) { return $data; }
    private function processLowStockTrigger($data) { return $data; }
}
?>
