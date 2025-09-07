<?php
/**
 * TPT Free ERP - Webhooks System Module
 * Event-driven webhook management for external integrations
 */

class Webhooks extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main webhooks dashboard
     */
    public function index() {
        $this->requirePermission('webhooks.view');

        $data = [
            'title' => 'Webhooks Dashboard',
            'active_webhooks' => $this->getActiveWebhooks(),
            'recent_deliveries' => $this->getRecentDeliveries(),
            'delivery_stats' => $this->getDeliveryStats(),
            'failed_deliveries' => $this->getFailedDeliveries(),
            'webhook_events' => $this->getWebhookEvents()
        ];

        $this->render('modules/webhooks/dashboard', $data);
    }

    /**
     * Webhook management
     */
    public function webhooks() {
        $this->requirePermission('webhooks.manage');

        $filters = [
            'status' => $_GET['status'] ?? 'all',
            'event_type' => $_GET['event_type'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $webhooks = $this->getWebhooks($filters);

        $data = [
            'title' => 'Webhook Management',
            'webhooks' => $webhooks,
            'filters' => $filters,
            'event_types' => $this->getEventTypes(),
            'webhook_summary' => $this->getWebhookSummary()
        ];

        $this->render('modules/webhooks/webhooks', $data);
    }

    /**
     * Create new webhook
     */
    public function createWebhook() {
        $this->requirePermission('webhooks.create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processWebhookCreation();
        }

        $data = [
            'title' => 'Create Webhook',
            'event_types' => $this->getEventTypes(),
            'content_types' => $this->getContentTypes(),
            'security_methods' => $this->getSecurityMethods(),
            'next_webhook_id' => $this->generateNextWebhookId()
        ];

        $this->render('modules/webhooks/create_webhook', $data);
    }

    /**
     * Webhook deliveries
     */
    public function deliveries() {
        $this->requirePermission('webhooks.deliveries.view');

        $filters = [
            'webhook_id' => $_GET['webhook_id'] ?? null,
            'status' => $_GET['status'] ?? 'all',
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d')
        ];

        $deliveries = $this->getDeliveries($filters);

        $data = [
            'title' => 'Webhook Deliveries',
            'deliveries' => $deliveries,
            'filters' => $filters,
            'webhooks' => $this->getActiveWebhooks(),
            'delivery_summary' => $this->getDeliverySummary($filters)
        ];

        $this->render('modules/webhooks/deliveries', $data);
    }

    /**
     * Webhook testing
     */
    public function test() {
        $this->requirePermission('webhooks.test');

        $data = [
            'title' => 'Webhook Testing',
            'webhooks' => $this->getActiveWebhooks(),
            'test_events' => $this->getTestEvents(),
            'test_history' => $this->getTestHistory()
        ];

        $this->render('modules/webhooks/test', $data);
    }

    /**
     * Webhook logs and monitoring
     */
    public function logs() {
        $this->requirePermission('webhooks.logs.view');

        $filters = [
            'webhook_id' => $_GET['webhook_id'] ?? null,
            'level' => $_GET['level'] ?? 'all',
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 day')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d')
        ];

        $logs = $this->getWebhookLogs($filters);

        $data = [
            'title' => 'Webhook Logs',
            'logs' => $logs,
            'filters' => $filters,
            'webhooks' => $this->getActiveWebhooks(),
            'log_summary' => $this->getLogSummary($filters)
        ];

        $this->render('modules/webhooks/logs', $data);
    }

    /**
     * Webhook retry management
     */
    public function retries() {
        $this->requirePermission('webhooks.retries.view');

        $data = [
            'title' => 'Webhook Retries',
            'failed_deliveries' => $this->getFailedDeliveriesForRetry(),
            'retry_queue' => $this->getRetryQueue(),
            'retry_settings' => $this->getRetrySettings()
        ];

        $this->render('modules/webhooks/retries', $data);
    }

    /**
     * Webhook security settings
     */
    public function security() {
        $this->requirePermission('webhooks.security.view');

        $data = [
            'title' => 'Webhook Security',
            'security_settings' => $this->getSecuritySettings(),
            'ip_whitelist' => $this->getIPWhitelist(),
            'rate_limits' => $this->getRateLimits(),
            'security_audit' => $this->getSecurityAudit()
        ];

        $this->render('modules/webhooks/security', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getActiveWebhooks() {
        return $this->db->query("
            SELECT
                w.*,
                COUNT(wd.id) as total_deliveries,
                COUNT(CASE WHEN wd.status = 'success' THEN 1 END) as successful_deliveries,
                COUNT(CASE WHEN wd.status = 'failed' THEN 1 END) as failed_deliveries,
                MAX(wd.created_at) as last_delivery
            FROM webhooks w
            LEFT JOIN webhook_deliveries wd ON w.id = wd.webhook_id
            WHERE w.company_id = ? AND w.is_active = true
            GROUP BY w.id
            ORDER BY w.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getRecentDeliveries() {
        return $this->db->query("
            SELECT
                wd.*,
                w.name as webhook_name,
                w.url as webhook_url
            FROM webhook_deliveries wd
            JOIN webhooks w ON wd.webhook_id = w.id
            WHERE wd.company_id = ?
            ORDER BY wd.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getDeliveryStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_deliveries,
                COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_deliveries,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_deliveries,
                COUNT(CASE WHEN status = 'retry' THEN 1 END) as retry_deliveries,
                AVG(response_time_ms) as avg_response_time,
                MIN(response_time_ms) as min_response_time,
                MAX(response_time_ms) as max_response_time
            FROM webhook_deliveries
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ]);
    }

    private function getFailedDeliveries() {
        return $this->db->query("
            SELECT
                wd.*,
                w.name as webhook_name,
                w.url as webhook_url,
                wd.error_message
            FROM webhook_deliveries wd
            JOIN webhooks w ON wd.webhook_id = w.id
            WHERE wd.company_id = ? AND wd.status = 'failed'
                AND wd.created_at >= ?
            ORDER BY wd.created_at DESC
            LIMIT 10
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ]);
    }

    private function getWebhookEvents() {
        return [
            'user.created' => 'User Created',
            'user.updated' => 'User Updated',
            'user.deleted' => 'User Deleted',
            'customer.created' => 'Customer Created',
            'customer.updated' => 'Customer Updated',
            'order.created' => 'Order Created',
            'order.updated' => 'Order Updated',
            'order.completed' => 'Order Completed',
            'product.created' => 'Product Created',
            'product.updated' => 'Product Updated',
            'inventory.low_stock' => 'Low Stock Alert',
            'invoice.created' => 'Invoice Created',
            'invoice.paid' => 'Invoice Paid',
            'project.created' => 'Project Created',
            'project.updated' => 'Project Updated',
            'task.created' => 'Task Created',
            'task.completed' => 'Task Completed'
        ];
    }

    private function getWebhooks($filters) {
        $where = ["w.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status'] !== 'all') {
            $where[] = "w.is_active = ?";
            $params[] = $filters['status'] === 'active' ? true : false;
        }

        if ($filters['event_type']) {
            $where[] = "w.events LIKE ?";
            $params[] = '%' . $filters['event_type'] . '%';
        }

        if ($filters['search']) {
            $where[] = "(w.name LIKE ? OR w.url LIKE ? OR w.description LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                w.*,
                COUNT(wd.id) as total_deliveries,
                COUNT(CASE WHEN wd.status = 'success' THEN 1 END) as successful_deliveries,
                COUNT(CASE WHEN wd.status = 'failed' THEN 1 END) as failed_deliveries,
                MAX(wd.created_at) as last_delivery
            FROM webhooks w
            LEFT JOIN webhook_deliveries wd ON w.id = wd.webhook_id
            WHERE $whereClause
            GROUP BY w.id
            ORDER BY w.created_at DESC
        ", $params);
    }

    private function getEventTypes() {
        return $this->getWebhookEvents();
    }

    private function getContentTypes() {
        return [
            'application/json' => 'JSON',
            'application/xml' => 'XML',
            'application/x-www-form-urlencoded' => 'Form URL Encoded',
            'text/plain' => 'Plain Text'
        ];
    }

    private function getSecurityMethods() {
        return [
            'none' => 'No Security',
            'basic_auth' => 'Basic Authentication',
            'bearer_token' => 'Bearer Token',
            'api_key' => 'API Key',
            'hmac_sha256' => 'HMAC SHA256',
            'webhook_secret' => 'Webhook Secret'
        ];
    }

    private function getWebhookSummary() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_webhooks,
                COUNT(CASE WHEN is_active = true THEN 1 END) as active_webhooks,
                COUNT(CASE WHEN is_active = false THEN 1 END) as inactive_webhooks,
                AVG(success_rate) as avg_success_rate
            FROM (
                SELECT
                    w.*,
                    CASE
                        WHEN COUNT(wd.id) > 0 THEN
                            (COUNT(CASE WHEN wd.status = 'success' THEN 1 END) * 100.0 / COUNT(wd.id))
                        ELSE 0
                    END as success_rate
                FROM webhooks w
                LEFT JOIN webhook_deliveries wd ON w.id = wd.webhook_id
                    AND wd.created_at >= ?
                WHERE w.company_id = ?
                GROUP BY w.id
            ) webhook_stats
        ", [
            date('Y-m-d H:i:s', strtotime('-30 days')),
            $this->user['company_id']
        ]);
    }

    private function generateNextWebhookId() {
        $last_webhook = $this->db->querySingle("
            SELECT webhook_id FROM webhooks
            WHERE company_id = ? AND webhook_id LIKE 'WH%'
            ORDER BY webhook_id DESC
            LIMIT 1
        ", [$this->user['company_id']]);

        if ($last_webhook) {
            $number = (int)substr($last_webhook['webhook_id'], 2) + 1;
            return 'WH' . str_pad($number, 6, '0', STR_PAD_LEFT);
        }

        return 'WH000001';
    }

    private function processWebhookCreation() {
        $this->requirePermission('webhooks.create');

        $data = $this->validateWebhookData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid webhook data');
            $this->redirect('/webhooks/create');
        }

        try {
            $this->db->beginTransaction();

            $webhook_id = $this->db->insert('webhooks', [
                'company_id' => $this->user['company_id'],
                'webhook_id' => $data['webhook_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'url' => $data['url'],
                'method' => $data['method'],
                'content_type' => $data['content_type'],
                'events' => json_encode($data['events']),
                'headers' => json_encode($data['headers']),
                'security_method' => $data['security_method'],
                'security_config' => json_encode($data['security_config']),
                'is_active' => $data['is_active'],
                'retry_attempts' => $data['retry_attempts'],
                'retry_delay' => $data['retry_delay'],
                'timeout' => $data['timeout'],
                'created_by' => $this->user['id']
            ]);

            $this->db->commit();

            $this->setFlash('success', 'Webhook created successfully');
            $this->redirect('/webhooks');

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to create webhook: ' . $e->getMessage());
            $this->redirect('/webhooks/create');
        }
    }

    private function validateWebhookData($data) {
        if (empty($data['name']) || empty($data['url']) || empty($data['events'])) {
            return false;
        }

        // Validate URL
        if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            return false;
        }

        // Validate events
        if (!is_array($data['events']) || empty($data['events'])) {
            return false;
        }

        return [
            'webhook_id' => $data['webhook_id'] ?? $this->generateNextWebhookId(),
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'url' => $data['url'],
            'method' => $data['method'] ?? 'POST',
            'content_type' => $data['content_type'] ?? 'application/json',
            'events' => $data['events'],
            'headers' => $data['headers'] ?? [],
            'security_method' => $data['security_method'] ?? 'none',
            'security_config' => $data['security_config'] ?? [],
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
            'retry_attempts' => (int)($data['retry_attempts'] ?? 3),
            'retry_delay' => (int)($data['retry_delay'] ?? 60),
            'timeout' => (int)($data['timeout'] ?? 30)
        ];
    }

    private function getDeliveries($filters) {
        $where = ["wd.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['webhook_id']) {
            $where[] = "wd.webhook_id = ?";
            $params[] = $filters['webhook_id'];
        }

        if ($filters['status'] !== 'all') {
            $where[] = "wd.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['date_from']) {
            $where[] = "wd.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "wd.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                wd.*,
                w.name as webhook_name,
                w.url as webhook_url
            FROM webhook_deliveries wd
            JOIN webhooks w ON wd.webhook_id = w.id
            WHERE $whereClause
            ORDER BY wd.created_at DESC
        ", $params);
    }

    private function getDeliverySummary($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['date_from']) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_deliveries,
                COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_deliveries,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_deliveries,
                COUNT(CASE WHEN status = 'retry' THEN 1 END) as retry_deliveries,
                AVG(response_time_ms) as avg_response_time,
                ROUND(
                    (COUNT(CASE WHEN status = 'success' THEN 1 END) * 100.0 / COUNT(*)), 2
                ) as success_rate
            FROM webhook_deliveries
            WHERE $whereClause
        ", $params);
    }

    private function getTestEvents() {
        return [
            'user.created' => ['description' => 'Test user creation event', 'sample_data' => ['id' => 123, 'name' => 'John Doe', 'email' => 'john@example.com']],
            'order.created' => ['description' => 'Test order creation event', 'sample_data' => ['id' => 456, 'customer_id' => 789, 'total' => 99.99]],
            'product.updated' => ['description' => 'Test product update event', 'sample_data' => ['id' => 101, 'name' => 'Test Product', 'price' => 29.99]],
            'inventory.low_stock' => ['description' => 'Test low stock alert', 'sample_data' => ['product_id' => 202, 'current_stock' => 5, 'reorder_point' => 10]]
        ];
    }

    private function getTestHistory() {
        return $this->db->query("
            SELECT
                wth.*,
                w.name as webhook_name
            FROM webhook_test_history wth
            JOIN webhooks w ON wth.webhook_id = w.id
            WHERE wth.company_id = ?
            ORDER BY wth.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getWebhookLogs($filters) {
        $where = ["wl.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['webhook_id']) {
            $where[] = "wl.webhook_id = ?";
            $params[] = $filters['webhook_id'];
        }

        if ($filters['level'] !== 'all') {
            $where[] = "wl.level = ?";
            $params[] = $filters['level'];
        }

        if ($filters['date_from']) {
            $where[] = "wl.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "wl.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                wl.*,
                w.name as webhook_name
            FROM webhook_logs wl
            LEFT JOIN webhooks w ON wl.webhook_id = w.id
            WHERE $whereClause
            ORDER BY wl.created_at DESC
        ", $params);
    }

    private function getLogSummary($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['date_from']) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_logs,
                COUNT(CASE WHEN level = 'error' THEN 1 END) as error_logs,
                COUNT(CASE WHEN level = 'warning' THEN 1 END) as warning_logs,
                COUNT(CASE WHEN level = 'info' THEN 1 END) as info_logs,
                COUNT(CASE WHEN level = 'debug' THEN 1 END) as debug_logs
            FROM webhook_logs
            WHERE $whereClause
        ", $params);
    }

    private function getFailedDeliveriesForRetry() {
        return $this->db->query("
            SELECT
                wd.*,
                w.name as webhook_name,
                w.url as webhook_url,
                w.retry_attempts as max_retries,
                COUNT(wdr.id) as retry_count
            FROM webhook_deliveries wd
            JOIN webhooks w ON wd.webhook_id = w.id
            LEFT JOIN webhook_delivery_retries wdr ON wd.id = wdr.delivery_id
            WHERE wd.company_id = ? AND wd.status = 'failed'
                AND wd.created_at >= ?
            GROUP BY wd.id, w.name, w.url, w.retry_attempts
            HAVING retry_count < w.retry_attempts
            ORDER BY wd.created_at DESC
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ]);
    }

    private function getRetryQueue() {
        return $this->db->query("
            SELECT
                wdr.*,
                wd.webhook_id,
                w.name as webhook_name,
                wd.payload,
                wd.response_status,
                wd.error_message
            FROM webhook_delivery_retries wdr
            JOIN webhook_deliveries wd ON wdr.delivery_id = wd.id
            JOIN webhooks w ON wd.webhook_id = w.id
            WHERE wdr.company_id = ? AND wdr.status = 'pending'
            ORDER BY wdr.scheduled_at ASC
        ", [$this->user['company_id']]);
    }

    private function getRetrySettings() {
        return $this->db->querySingle("
            SELECT * FROM webhook_retry_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSecuritySettings() {
        return $this->db->querySingle("
            SELECT * FROM webhook_security_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getIPWhitelist() {
        return $this->db->query("
            SELECT * FROM webhook_ip_whitelist
            WHERE company_id = ?
            ORDER BY created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getRateLimits() {
        return $this->db->query("
            SELECT * FROM webhook_rate_limits
            WHERE company_id = ?
            ORDER BY created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSecurityAudit() {
        return $this->db->query("
            SELECT
                wsa.*,
                u.first_name,
                u.last_name
            FROM webhook_security_audit wsa
            LEFT JOIN users u ON wsa.user_id = u.id
            WHERE wsa.company_id = ?
            ORDER BY wsa.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // WEBHOOK DELIVERY METHODS
    // ============================================================================

    public function triggerEvent($eventType, $eventData) {
        // Get all active webhooks for this event type
        $webhooks = $this->db->query("
            SELECT * FROM webhooks
            WHERE company_id = ? AND is_active = true
                AND JSON_CONTAINS(events, ?)
        ", [$this->user['company_id'], json_encode($eventType)]);

        foreach ($webhooks as $webhook) {
            $this->deliverWebhook($webhook, $eventType, $eventData);
        }
    }

    private function deliverWebhook($webhook, $eventType, $eventData) {
        $startTime = microtime(true);

        try {
            // Prepare payload
            $payload = $this->preparePayload($webhook, $eventType, $eventData);

            // Prepare headers
            $headers = $this->prepareHeaders($webhook);

            // Make HTTP request
            $response = $this->makeWebhookRequest($webhook, $payload, $headers);

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            // Log successful delivery
            $this->logDelivery($webhook['id'], 'success', $response['status'], $responseTime, null);

        } catch (Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            // Log failed delivery
            $this->logDelivery($webhook['id'], 'failed', null, $responseTime, $e->getMessage());

            // Schedule retry if configured
            if ($webhook['retry_attempts'] > 0) {
                $this->scheduleRetry($webhook, $eventType, $eventData);
            }
        }
    }

    private function preparePayload($webhook, $eventType, $eventData) {
        $payload = [
            'webhook_id' => $webhook['webhook_id'],
            'event_type' => $eventType,
            'timestamp' => date('c'),
            'company_id' => $this->user['company_id'],
            'data' => $eventData
        ];

        // Add webhook-specific data
        if ($webhook['include_metadata']) {
            $payload['metadata'] = [
                'webhook_name' => $webhook['name'],
                'webhook_url' => $webhook['url'],
                'created_at' => $webhook['created_at']
            ];
        }

        return $payload;
    }

    private function prepareHeaders($webhook) {
        $headers = [
            'Content-Type' => $webhook['content_type'],
            'User-Agent' => 'TPT-ERP-Webhook/1.0',
            'X-Webhook-ID' => $webhook['webhook_id'],
            'X-Event-Source' => 'tpt-erp'
        ];

        // Add custom headers
        if ($webhook['headers']) {
            $customHeaders = json_decode($webhook['headers'], true);
            if ($customHeaders) {
                $headers = array_merge($headers, $customHeaders);
            }
        }

        // Add security headers
        $securityHeaders = $this->prepareSecurityHeaders($webhook);
        $headers = array_merge($headers, $securityHeaders);

        return $headers;
    }

    private function prepareSecurityHeaders($webhook) {
        $headers = [];

        switch ($webhook['security_method']) {
            case 'basic_auth':
                $config = json_decode($webhook['security_config'], true);
                if ($config && isset($config['username']) && isset($config['password'])) {
                    $credentials = base64_encode($config['username'] . ':' . $config['password']);
                    $headers['Authorization'] = 'Basic ' . $credentials;
                }
                break;

            case 'bearer_token':
                $config = json_decode($webhook['security_config'], true);
                if ($config && isset($config['token'])) {
                    $headers['Authorization'] = 'Bearer ' . $config['token'];
                }
                break;

            case 'api_key':
                $config = json_decode($webhook['security_config'], true);
                if ($config && isset($config['key']) && isset($config['header_name'])) {
                    $headers[$config['header_name']] = $config['key'];
                }
                break;

            case 'hmac_sha256':
                $config = json_decode($webhook['security_config'], true);
                if ($config && isset($config['secret'])) {
                    $timestamp = time();
                    $payload = json_encode($this->currentPayload);
                    $signature = hash_hmac('sha256', $timestamp . $payload, $config['secret']);
                    $headers['X-Timestamp'] = $timestamp;
                    $headers['X-Signature'] = $signature;
                }
                break;

            case 'webhook_secret':
                $config = json_decode($webhook['security_config'], true);
                if ($config && isset($config['secret'])) {
                    $headers['X-Webhook-Secret'] = $config['secret'];
                }
                break;
        }

        return $headers;
    }

    private function makeWebhookRequest($webhook, $payload, $headers) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $webhook['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $webhook['method']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, $webhook['timeout']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // Set headers
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = $key . ': ' . $value;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception("Webhook delivery failed: $error");
        }

        if ($httpCode >= 400) {
            throw new Exception("Webhook delivery failed with status $httpCode: $response");
        }

        return [
            'status' => $httpCode,
            'response' => $response
        ];
    }

    private function logDelivery($webhookId, $status, $responseStatus, $responseTime, $errorMessage) {
        $this->db->insert('webhook_deliveries', [
            'company_id' => $this->user['company_id'],
            'webhook_id' => $webhookId,
            'status' => $status,
            'response_status' => $responseStatus,
            'response_time_ms' => $responseTime,
            'error_message' => $errorMessage,
            'payload' => json_encode($this->currentPayload),
            'response_body' => $status === 'success' ? null : $errorMessage
        ]);
    }

    private function scheduleRetry($webhook, $eventType, $eventData) {
        $retryDelay = $webhook['retry_delay'];

        $this->db->insert('webhook_delivery_retries', [
            'company_id' => $this->user['company_id'],
            'webhook_id' => $webhook['id'],
            'event_type' => $eventType,
            'event_data' => json_encode($eventData),
            'scheduled_at' => date('Y-m-d H:i:s', strtotime("+$retryDelay seconds")),
            'status' => 'pending'
        ]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function testWebhook() {
        $this->requirePermission('webhooks.test');

        $data = $this->validateRequest([
            'webhook_id' => 'required|integer',
            'event_type' => 'required|string',
            'test_data' => 'array'
        ]);

        try {
            $webhook = $this->db->querySingle("
                SELECT * FROM webhooks
                WHERE id = ? AND company_id = ?
            ", [$data['webhook_id'], $this->user['company_id']]);

            if (!$webhook) {
                throw new Exception('Webhook not found');
            }

            $testData = $data['test_data'] ?? $this->getTestEvents()[$data['event_type']]['sample_data'] ?? [];

            // Deliver test webhook
            $this->deliverWebhook($webhook, $data['event_type'], $testData);

            // Log test
            $this->db->insert('webhook_test_history', [
                'company_id' => $this->user['company_id'],
                'webhook_id' => $data['webhook_id'],
                'event_type' => $data['event_type'],
                'test_data' => json_encode($testData),
                'status' => 'completed'
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Webhook test completed successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function retryDelivery() {
        $this->requirePermission('webhooks.retries.manage');

        $data = $this->validateRequest([
            'delivery_id' => 'required|integer'
        ]);

        try {
            $delivery = $this->db->querySingle("
                SELECT wd.*, w.* FROM webhook_deliveries wd
                JOIN webhooks w ON wd.webhook_id = w.id
                WHERE wd.id = ? AND wd.company_id = ?
            ", [$data['delivery_id'], $this->user['company_id']]);

            if (!$delivery) {
                throw new Exception('Delivery not found');
            }

            // Retry the delivery
            $this->deliverWebhook($delivery, $delivery['event_type'], json_decode($delivery['payload'], true));

            $this->jsonResponse([
                'success' => true,
                'message' => 'Delivery retry completed'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
?>
