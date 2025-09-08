<?php
/**
 * TPT Free ERP - Zapier Integration Module
 * Complete Zapier app integration with triggers, actions, and authentication
 */

class ZapierIntegration extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main Zapier integration dashboard
     */
    public function index() {
        $this->requirePermission('zapier.view');

        $data = [
            'title' => 'Zapier Integration',
            'zapier_apps' => $this->getZapierApps(),
            'active_zaps' => $this->getActiveZaps(),
            'zapier_stats' => $this->getZapierStats(),
            'recent_activity' => $this->getRecentActivity(),
            'authentication_status' => $this->getAuthenticationStatus()
        ];

        $this->render('modules/zapier/dashboard', $data);
    }

    /**
     * Zapier app management
     */
    public function apps() {
        $this->requirePermission('zapier.apps.view');

        $data = [
            'title' => 'Zapier Apps',
            'zapier_apps' => $this->getZapierApps(),
            'app_templates' => $this->getAppTemplates(),
            'app_versions' => $this->getAppVersions(),
            'app_analytics' => $this->getAppAnalytics()
        ];

        $this->render('modules/zapier/apps', $data);
    }

    /**
     * Create new Zapier app
     */
    public function createApp() {
        $this->requirePermission('zapier.apps.create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processAppCreation();
        }

        $data = [
            'title' => 'Create Zapier App',
            'app_templates' => $this->getAppTemplates(),
            'triggers' => $this->getAvailableTriggers(),
            'actions' => $this->getAvailableActions(),
            'auth_methods' => $this->getAuthMethods()
        ];

        $this->render('modules/zapier/create_app', $data);
    }

    /**
     * Zap management
     */
    public function zaps() {
        $this->requirePermission('zapier.zaps.view');

        $filters = [
            'status' => $_GET['status'] ?? 'all',
            'app_id' => $_GET['app_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];

        $zaps = $this->getZaps($filters);

        $data = [
            'title' => 'Zap Management',
            'zaps' => $zaps,
            'filters' => $filters,
            'zapier_apps' => $this->getZapierApps(),
            'zap_summary' => $this->getZapSummary($filters)
        ];

        $this->render('modules/zapier/zaps', $data);
    }

    /**
     * Authentication management
     */
    public function authentication() {
        $this->requirePermission('zapier.auth.view');

        $data = [
            'title' => 'Authentication Management',
            'auth_methods' => $this->getAuthMethods(),
            'api_keys' => $this->getAPIKeys(),
            'oauth_clients' => $this->getOAuthClients(),
            'webhook_secrets' => $this->getWebhookSecrets(),
            'auth_logs' => $this->getAuthLogs()
        ];

        $this->render('modules/zapier/authentication', $data);
    }

    /**
     * Data mapping and transformation
     */
    public function mapping() {
        $this->requirePermission('zapier.mapping.view');

        $data = [
            'title' => 'Data Mapping',
            'field_mappings' => $this->getFieldMappings(),
            'data_transforms' => $this->getDataTransforms(),
            'mapping_templates' => $this->getMappingTemplates(),
            'mapping_history' => $this->getMappingHistory()
        ];

        $this->render('modules/zapier/mapping', $data);
    }

    /**
     * Error handling and monitoring
     */
    public function errors() {
        $this->requirePermission('zapier.errors.view');

        $filters = [
            'app_id' => $_GET['app_id'] ?? null,
            'error_type' => $_GET['error_type'] ?? 'all',
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d')
        ];

        $errors = $this->getZapierErrors($filters);

        $data = [
            'title' => 'Error Monitoring',
            'errors' => $errors,
            'filters' => $filters,
            'zapier_apps' => $this->getZapierApps(),
            'error_summary' => $this->getErrorSummary($filters)
        ];

        $this->render('modules/zapier/errors', $data);
    }

    /**
     * Zapier API endpoints for external access
     */
    public function api() {
        $this->requirePermission('zapier.api.view');

        $data = [
            'title' => 'Zapier API',
            'api_endpoints' => $this->getZapierAPIEndpoints(),
            'api_logs' => $this->getAPIRequestLogs(),
            'rate_limits' => $this->getRateLimits(),
            'api_documentation' => $this->getAPIDocumentation()
        ];

        $this->render('modules/zapier/api', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getZapierApps() {
        return $this->db->query("
            SELECT
                za.*,
                COUNT(z.id) as zap_count,
                COUNT(DISTINCT z.user_id) as user_count,
                MAX(z.created_at) as last_zap_created
            FROM zapier_apps za
            LEFT JOIN zaps z ON za.id = z.app_id
            WHERE za.company_id = ?
            GROUP BY za.id
            ORDER BY za.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getActiveZaps() {
        return $this->db->query("
            SELECT
                z.*,
                za.name as app_name,
                u.first_name,
                u.last_name,
                COUNT(ze.id) as execution_count,
                MAX(ze.executed_at) as last_execution
            FROM zaps z
            JOIN zapier_apps za ON z.app_id = za.id
            LEFT JOIN users u ON z.user_id = u.id
            LEFT JOIN zap_executions ze ON z.id = ze.zap_id
            WHERE z.company_id = ? AND z.is_active = true
            GROUP BY z.id, za.name, u.first_name, u.last_name
            ORDER BY z.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getZapierStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT za.id) as total_apps,
                COUNT(DISTINCT z.id) as total_zaps,
                COUNT(ze.id) as total_executions,
                COUNT(CASE WHEN ze.status = 'success' THEN 1 END) as successful_executions,
                AVG(ze.execution_time_ms) as avg_execution_time,
                MAX(ze.executed_at) as last_execution
            FROM zapier_apps za
            LEFT JOIN zaps z ON za.id = z.app_id
            LEFT JOIN zap_executions ze ON z.id = ze.zap_id
            WHERE za.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRecentActivity() {
        return $this->db->query("
            SELECT
                za.*,
                'zap_created' as activity_type,
                za.created_at as activity_time,
                u.first_name,
                u.last_name
            FROM zapier_activity za
            LEFT JOIN users u ON za.user_id = u.id
            WHERE za.company_id = ?
            ORDER BY za.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getAuthenticationStatus() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN auth_status = 'connected' THEN 1 END) as connected_apps,
                COUNT(CASE WHEN auth_status = 'expired' THEN 1 END) as expired_apps,
                COUNT(CASE WHEN auth_status = 'error' THEN 1 END) as error_apps,
                MAX(last_auth_check) as last_check
            FROM zapier_auth_status
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAppTemplates() {
        return [
            'basic' => [
                'name' => 'Basic App',
                'description' => 'Simple app with basic triggers and actions',
                'triggers' => ['new_record', 'updated_record'],
                'actions' => ['create_record', 'update_record']
            ],
            'advanced' => [
                'name' => 'Advanced App',
                'description' => 'Full-featured app with all available triggers and actions',
                'triggers' => ['new_record', 'updated_record', 'deleted_record', 'webhook'],
                'actions' => ['create_record', 'update_record', 'delete_record', 'search_records']
            ],
            'custom' => [
                'name' => 'Custom App',
                'description' => 'Build a custom app with selected triggers and actions',
                'triggers' => [],
                'actions' => []
            ]
        ];
    }

    private function getAppVersions() {
        return $this->db->query("
            SELECT
                zav.*,
                za.name as app_name,
                COUNT(z.id) as zap_count
            FROM zapier_app_versions zav
            JOIN zapier_apps za ON zav.app_id = za.id
            LEFT JOIN zaps z ON zav.id = z.app_version_id
            WHERE zav.company_id = ?
            GROUP BY zav.id, za.name
            ORDER BY zav.version DESC
        ", [$this->user['company_id']]);
    }

    private function getAppAnalytics() {
        return $this->db->query("
            SELECT
                za.name as app_name,
                COUNT(z.id) as total_zaps,
                COUNT(ze.id) as total_executions,
                AVG(ze.execution_time_ms) as avg_execution_time,
                COUNT(CASE WHEN ze.status = 'success' THEN 1 END) as successful_executions
            FROM zapier_apps za
            LEFT JOIN zaps z ON za.id = z.app_id
            LEFT JOIN zap_executions ze ON z.id = ze.zap_id
            WHERE za.company_id = ?
            GROUP BY za.id, za.name
            ORDER BY total_executions DESC
        ", [$this->user['company_id']]);
    }

    private function getAvailableTriggers() {
        return [
            'new_record' => [
                'name' => 'New Record',
                'description' => 'Triggers when a new record is created',
                'input_fields' => ['table', 'filters']
            ],
            'updated_record' => [
                'name' => 'Updated Record',
                'description' => 'Triggers when a record is updated',
                'input_fields' => ['table', 'fields', 'filters']
            ],
            'deleted_record' => [
                'name' => 'Deleted Record',
                'description' => 'Triggers when a record is deleted',
                'input_fields' => ['table', 'filters']
            ],
            'webhook' => [
                'name' => 'Webhook',
                'description' => 'Triggers on webhook events',
                'input_fields' => ['url', 'method', 'headers']
            ],
            'scheduled' => [
                'name' => 'Scheduled',
                'description' => 'Triggers on a schedule',
                'input_fields' => ['frequency', 'time']
            ]
        ];
    }

    private function getAvailableActions() {
        return [
            'create_record' => [
                'name' => 'Create Record',
                'description' => 'Creates a new record',
                'input_fields' => ['table', 'data']
            ],
            'update_record' => [
                'name' => 'Update Record',
                'description' => 'Updates an existing record',
                'input_fields' => ['table', 'id', 'data']
            ],
            'delete_record' => [
                'name' => 'Delete Record',
                'description' => 'Deletes a record',
                'input_fields' => ['table', 'id']
            ],
            'search_records' => [
                'name' => 'Search Records',
                'description' => 'Searches for records',
                'input_fields' => ['table', 'query', 'filters']
            ],
            'send_email' => [
                'name' => 'Send Email',
                'description' => 'Sends an email',
                'input_fields' => ['to', 'subject', 'body']
            ]
        ];
    }

    private function getAuthMethods() {
        return [
            'api_key' => [
                'name' => 'API Key',
                'description' => 'Simple API key authentication',
                'fields' => ['api_key']
            ],
            'oauth2' => [
                'name' => 'OAuth 2.0',
                'description' => 'OAuth 2.0 authentication flow',
                'fields' => ['client_id', 'client_secret', 'redirect_uri']
            ],
            'basic_auth' => [
                'name' => 'Basic Auth',
                'description' => 'HTTP Basic authentication',
                'fields' => ['username', 'password']
            ],
            'bearer_token' => [
                'name' => 'Bearer Token',
                'description' => 'Bearer token authentication',
                'fields' => ['token']
            ]
        ];
    }

    private function processAppCreation() {
        $this->requirePermission('zapier.apps.create');

        $data = $this->validateAppData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid app data');
            $this->redirect('/zapier/create-app');
        }

        try {
            $this->db->beginTransaction();

            $appId = $this->db->insert('zapier_apps', [
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'template' => $data['template'],
                'triggers' => json_encode($data['triggers']),
                'actions' => json_encode($data['actions']),
                'auth_method' => $data['auth_method'],
                'auth_config' => json_encode($data['auth_config']),
                'webhook_url' => $this->generateWebhookUrl($data['name']),
                'is_active' => $data['is_active'],
                'version' => '1.0.0',
                'created_by' => $this->user['id']
            ]);

            // Create initial app version
            $this->db->insert('zapier_app_versions', [
                'company_id' => $this->user['company_id'],
                'app_id' => $appId,
                'version' => '1.0.0',
                'changelog' => 'Initial app creation',
                'is_current' => true,
                'created_by' => $this->user['id']
            ]);

            $this->db->commit();

            $this->setFlash('success', 'Zapier app created successfully');
            $this->redirect('/zapier/apps');

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to create app: ' . $e->getMessage());
            $this->redirect('/zapier/create-app');
        }
    }

    private function validateAppData($data) {
        if (empty($data['name']) || empty($data['template'])) {
            return false;
        }

        // Validate triggers and actions based on template
        $template = $this->getAppTemplates()[$data['template']] ?? null;
        if (!$template) {
            return false;
        }

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'template' => $data['template'],
            'triggers' => $data['triggers'] ?? $template['triggers'],
            'actions' => $data['actions'] ?? $template['actions'],
            'auth_method' => $data['auth_method'] ?? 'api_key',
            'auth_config' => $data['auth_config'] ?? [],
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true
        ];
    }

    private function generateWebhookUrl($appName) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $appName));
        return "/api/zapier/webhook/{$slug}";
    }

    private function getZaps($filters) {
        $where = ["z.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status'] !== 'all') {
            $where[] = "z.is_active = ?";
            $params[] = $filters['status'] === 'active' ? true : false;
        }

        if ($filters['app_id']) {
            $where[] = "z.app_id = ?";
            $params[] = $filters['app_id'];
        }

        if ($filters['date_from']) {
            $where[] = "z.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "z.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                z.*,
                za.name as app_name,
                u.first_name,
                u.last_name,
                COUNT(ze.id) as execution_count,
                MAX(ze.executed_at) as last_execution
            FROM zaps z
            JOIN zapier_apps za ON z.app_id = za.id
            LEFT JOIN users u ON z.user_id = u.id
            LEFT JOIN zap_executions ze ON z.id = ze.zap_id
            WHERE $whereClause
            GROUP BY z.id, za.name, u.first_name, u.last_name
            ORDER BY z.created_at DESC
        ", $params);
    }

    private function getZapSummary($filters) {
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
                COUNT(*) as total_zaps,
                COUNT(CASE WHEN is_active = true THEN 1 END) as active_zaps,
                COUNT(CASE WHEN is_active = false THEN 1 END) as inactive_zaps,
                AVG(execution_count) as avg_executions_per_zap
            FROM (
                SELECT
                    z.*,
                    COUNT(ze.id) as execution_count
                FROM zaps z
                LEFT JOIN zap_executions ze ON z.id = ze.zap_id
                WHERE $whereClause
                GROUP BY z.id
            ) zap_stats
        ", $params);
    }

    private function getAPIKeys() {
        return $this->db->query("
            SELECT
                zak.*,
                za.name as app_name,
                COUNT(zakl.id) as usage_count,
                MAX(zakl.used_at) as last_used
            FROM zapier_api_keys zak
            LEFT JOIN zapier_apps za ON zak.app_id = za.id
            LEFT JOIN zapier_api_key_logs zakl ON zak.id = zakl.api_key_id
            WHERE zak.company_id = ?
            GROUP BY zak.id, za.name
            ORDER BY zak.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getOAuthClients() {
        return $this->db->query("
            SELECT
                zoc.*,
                za.name as app_name,
                COUNT(zt.id) as token_count,
                MAX(zt.created_at) as last_token_issued
            FROM zapier_oauth_clients zoc
            LEFT JOIN zapier_apps za ON zoc.app_id = za.id
            LEFT JOIN zapier_tokens zt ON zoc.id = zt.client_id
            WHERE zoc.company_id = ?
            GROUP BY zoc.id, za.name
            ORDER BY zoc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getWebhookSecrets() {
        return $this->db->query("
            SELECT
                zws.*,
                za.name as app_name,
                COUNT(zwl.id) as usage_count,
                MAX(zwl.created_at) as last_used
            FROM zapier_webhook_secrets zws
            LEFT JOIN zapier_apps za ON zws.app_id = za.id
            LEFT JOIN zapier_webhook_logs zwl ON zws.id = zwl.secret_id
            WHERE zws.company_id = ?
            GROUP BY zws.id, za.name
            ORDER BY zws.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAuthLogs() {
        return $this->db->query("
            SELECT
                zal.*,
                za.name as app_name,
                u.first_name,
                u.last_name
            FROM zapier_auth_logs zal
            LEFT JOIN zapier_apps za ON zal.app_id = za.id
            LEFT JOIN users u ON zal.user_id = u.id
            WHERE zal.company_id = ?
            ORDER BY zal.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getFieldMappings() {
        return $this->db->query("
            SELECT
                zfm.*,
                za.name as app_name,
                COUNT(zfmd.id) as data_count
            FROM zapier_field_mappings zfm
            LEFT JOIN zapier_apps za ON zfm.app_id = za.id
            LEFT JOIN zapier_field_mapping_data zfmd ON zfm.id = zfmd.mapping_id
            WHERE zfm.company_id = ?
            GROUP BY zfm.id, za.name
            ORDER BY zfm.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getDataTransforms() {
        return $this->db->query("
            SELECT
                zdt.*,
                za.name as app_name,
                COUNT(zdtd.id) as usage_count
            FROM zapier_data_transforms zdt
            LEFT JOIN zapier_apps za ON zdt.app_id = za.id
            LEFT JOIN zapier_data_transform_data zdtd ON zdt.id = zdtd.transform_id
            WHERE zdt.company_id = ?
            GROUP BY zdt.id, za.name
            ORDER BY zdt.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getMappingTemplates() {
        return $this->db->query("
            SELECT * FROM zapier_mapping_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getMappingHistory() {
        return $this->db->query("
            SELECT
                zmh.*,
                za.name as app_name,
                u.first_name,
                u.last_name
            FROM zapier_mapping_history zmh
            LEFT JOIN zapier_apps za ON zmh.app_id = za.id
            LEFT JOIN users u ON zmh.user_id = u.id
            WHERE zmh.company_id = ?
            ORDER BY zmh.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getZapierErrors($filters) {
        $where = ["ze.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['app_id']) {
            $where[] = "ze.app_id = ?";
            $params[] = $filters['app_id'];
        }

        if ($filters['error_type'] !== 'all') {
            $where[] = "ze.error_type = ?";
            $params[] = $filters['error_type'];
        }

        if ($filters['date_from']) {
            $where[] = "ze.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "ze.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                ze.*,
                za.name as app_name,
                z.name as zap_name
            FROM zapier_errors ze
            LEFT JOIN zapier_apps za ON ze.app_id = za.id
            LEFT JOIN zaps z ON ze.zap_id = z.id
            WHERE $whereClause
            ORDER BY ze.created_at DESC
        ", $params);
    }

    private function getErrorSummary($filters) {
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
                COUNT(*) as total_errors,
                COUNT(CASE WHEN error_type = 'auth' THEN 1 END) as auth_errors,
                COUNT(CASE WHEN error_type = 'rate_limit' THEN 1 END) as rate_limit_errors,
                COUNT(CASE WHEN error_type = 'timeout' THEN 1 END) as timeout_errors,
                COUNT(CASE WHEN error_type = 'data' THEN 1 END) as data_errors
            FROM zapier_errors
            WHERE $whereClause
        ", $params);
    }

    private function getZapierAPIEndpoints() {
        return $this->db->query("
            SELECT
                zae.*,
                za.name as app_name,
                COUNT(zael.id) as call_count,
                AVG(zael.response_time_ms) as avg_response_time
            FROM zapier_api_endpoints zae
            LEFT JOIN zapier_apps za ON zae.app_id = za.id
            LEFT JOIN zapier_api_endpoint_logs zael ON zae.id = zael.endpoint_id
            WHERE zae.company_id = ?
            GROUP BY zae.id, za.name
            ORDER BY zae.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAPIRequestLogs() {
        return $this->db->query("
            SELECT
                zael.*,
                zae.endpoint_name,
                za.name as app_name
            FROM zapier_api_endpoint_logs zael
            LEFT JOIN zapier_api_endpoints zae ON zael.endpoint_id = zae.id
            LEFT JOIN zapier_apps za ON zael.app_id = za.id
            WHERE zael.company_id = ?
            ORDER BY zael.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getRateLimits() {
        return $this->db->query("
            SELECT
                zrl.*,
                za.name as app_name,
                zrl.current_usage,
                ROUND((zrl.current_usage / NULLIF(zrl.limit_value, 0)) * 100, 2) as usage_percentage
            FROM zapier_rate_limits zrl
            LEFT JOIN zapier_apps za ON zrl.app_id = za.id
            WHERE zrl.company_id = ?
            ORDER BY zrl.limit_type, zrl.usage_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getAPIDocumentation() {
        return $this->db->query("
            SELECT * FROM zapier_api_documentation
            WHERE company_id = ? AND is_published = true
            ORDER BY category, title
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // ZAPIER WEBHOOK ENDPOINTS
    // ============================================================================

    public function handleWebhook($appSlug) {
        try {
            // Find the app by slug
            $app = $this->db->querySingle("
                SELECT * FROM zapier_apps
                WHERE webhook_url LIKE ? AND company_id = ?
            ", ["%/{$appSlug}", $this->user['company_id']]);

            if (!$app) {
                $this->jsonResponse(['error' => 'App not found'], 404);
            }

            // Validate authentication
            if (!$this->validateWebhookAuth($app)) {
                $this->jsonResponse(['error' => 'Authentication failed'], 401);
            }

            // Parse webhook data
            $webhookData = $this->parseWebhookData($app);

            // Process the webhook
            $result = $this->processWebhook($app, $webhookData);

            // Log the webhook call
            $this->logWebhookCall($app['id'], $webhookData, $result);

            $this->jsonResponse(['success' => true, 'result' => $result]);

        } catch (Exception $e) {
            $this->logWebhookError($app['id'] ?? null, $e->getMessage());
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function validateWebhookAuth($app) {
        $authMethod = $app['auth_method'];
        $authConfig = json_decode($app['auth_config'], true);

        switch ($authMethod) {
            case 'api_key':
                $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
                return $providedKey === ($authConfig['api_key'] ?? '');

            case 'webhook_secret':
                $providedSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
                return hash_equals($authConfig['secret'] ?? '', $providedSecret);

            case 'basic_auth':
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
                if (preg_match('/Basic (.+)/', $authHeader, $matches)) {
                    $credentials = base64_decode($matches[1]);
                    list($username, $password) = explode(':', $credentials, 2);
                    return $username === ($authConfig['username'] ?? '') &&
                           $password === ($authConfig['password'] ?? '');
                }
                return false;

            default:
                return true; // No authentication
        }
    }

    private function parseWebhookData($app) {
        $input = file_get_contents('php://input');
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            return json_decode($input, true);
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($input, $data);
            return $data;
        } else {
            return ['raw_data' => $input];
        }
    }

    private function processWebhook($app, $data) {
        // Apply data mapping if configured
        $mappedData = $this->applyDataMapping($app, $data);

        // Execute configured actions
        $actions = json_decode($app['actions'], true);
        $results = [];

        foreach ($actions as $action) {
            try {
                $result = $this->executeZapierAction($action, $mappedData);
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
            }
        }

        return $results;
    }

    private function applyDataMapping($app, $data) {
        // Apply configured field mappings
        $mappings = $this->db->query("
            SELECT * FROM zapier_field_mappings
            WHERE app_id = ? AND is_active = true
        ", [$app['id']]);

        $mappedData = $data;

        foreach ($mappings as $mapping) {
            $sourceField = $mapping['source_field'];
            $targetField = $mapping['target_field'];
            $transform = $mapping['transform_type'];

            if (isset($data[$sourceField])) {
                $mappedData[$targetField] = $this->applyDataTransform($data[$sourceField], $transform, $mapping);
            }
        }

        return $mappedData;
    }

    private function applyDataTransform($value, $transformType, $mapping) {
        switch ($transformType) {
            case 'uppercase':
                return strtoupper($value);
            case 'lowercase':
                return strtolower($value);
            case 'trim':
                return trim($value);
            case 'date_format':
                $config = json_decode($mapping['transform_config'], true);
                return date($config['format'] ?? 'Y-m-d', strtotime($value));
            case 'number_format':
                $config = json_decode($mapping['transform_config'], true);
                return number_format($value, $config['decimals'] ?? 2);
            default:
                return $value;
        }
    }

    private function executeZapierAction($action, $data) {
        switch ($action['type']) {
            case 'create_record':
                return $this->createRecord($action['config'], $data);
            case 'update_record':
                return $this->updateRecord($action['config'], $data);
            case 'delete_record':
                return $this->deleteRecord($action['config'], $data);
            case 'search_records':
                return $this->searchRecords($action['config'], $data);
            case 'send_email':
                return $this->sendZapierEmail($action['config'], $data);
            default:
                throw new Exception('Unknown action type: ' . $action['type']);
        }
    }

    private function createRecord($config, $data) {
        $table = $config['table'];
        $recordData = [];

        // Map fields from webhook data to database fields
        foreach ($config['field_mapping'] as $webhookField => $dbField) {
            if (isset($data[$webhookField])) {
                $recordData[$dbField] = $data[$webhookField];
            }
        }

        // Add company_id for security
        $recordData['company_id'] = $this->user['company_id'];

        return $this->db->insert($table, $recordData);
    }

    private function updateRecord($config, $data) {
        $table = $config['table'];
        $id = $data[$config['id_field']] ?? null;

        if (!$id) {
            throw new Exception('Record ID not provided');
        }

        $recordData = [];
        foreach ($config['field_mapping'] as $webhookField => $dbField) {
            if (isset($data[$webhookField])) {
                $recordData[$dbField] = $data[$webhookField];
            }
        }

        return $this->db->update($table, $recordData, 'id = ? AND company_id = ?', [$id, $this->user['company_id']]);
    }

    private function deleteRecord($config, $data) {
        $table = $config['table'];
        $id = $data[$config['id_field']] ?? null;

        if (!$id) {
            throw new Exception('Record ID not provided');
        }

        return $this->db->delete($table, 'id = ? AND company_id = ?', [$id, $this->user['company_id']]);
    }

    private function searchRecords($config, $data) {
        $table = $config['table'];
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        // Build search conditions
        foreach ($config['search_fields'] as $field => $webhookField) {
            if (isset($data[$webhookField])) {
                $where[] = "$field LIKE ?";
                $params[] = '%' . $data[$webhookField] . '%';
            }
        }

        $whereClause = implode(' AND ', $where);
        return $this->db->query("SELECT * FROM $table WHERE $whereClause", $params);
    }

    private function sendZapierEmail($config, $data) {
        $email = new Email();

        $to = $this->replacePlaceholders($config['to'], $data);
        $subject = $this->replacePlaceholders($config['subject'], $data);
        $body = $this->replacePlaceholders($config['body'], $data);

        return $email->send($to, $subject, $body);
    }

    private function replacePlaceholders($text, $data) {
        foreach ($data as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        return $text;
    }

    private function logWebhookCall($appId, $data, $result) {
        $this->db->insert('zapier_webhook_logs', [
            'company_id' => $this->user['company_id'],
            'app_id' => $appId,
            'request_data' => json_encode($data),
            'response_data' => json_encode($result),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    private function logWebhookError($appId, $error) {
        $this->db->insert('zapier_errors', [
            'company_id' => $this->user['company_id'],
            'app_id' => $appId,
            'error_type' => 'webhook',
            'error_message' => $error,
            'request_data' => json_encode($_REQUEST),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function authenticate() {
        $data = $this->validateRequest([
            'app_id' => 'required|integer',
            'auth_method' => 'required|string',
            'credentials' => 'required|array'
        ]);

        try {
            $app = $this->db->querySingle("
                SELECT * FROM zapier_apps
                WHERE id = ? AND company_id = ?
            ", [$data['app_id'], $this->user['company_id']]);

            if (!$app) {
                throw new Exception('App not found');
            }

            $authResult = $this->processAuthentication($app, $data['auth_method'], $data['credentials']);

            // Log authentication attempt
            $this->db->insert('zapier_auth_logs', [
                'company_id' => $this->user['company_id'],
                'app_id' => $data['app_id'],
                'user_id' => $this->user['id'],
                'auth_method' => $data['auth_method'],
                'success' => $authResult['success'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            if ($authResult['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'token' => $authResult['token'],
                    'expires_at' => $authResult['expires_at']
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => $authResult['error']
                ], 401);
            }

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function processAuthentication($app, $method, $credentials) {
        switch ($method) {
            case 'api_key':
                return $this->authenticateAPIKey($app, $credentials);
            case 'oauth2':
                return $this->authenticateOAuth2($app, $credentials);
            case 'basic_auth':
                return $this->authenticateBasic($app, $credentials);
            default:
                return ['success' => false, 'error' => 'Unsupported authentication method'];
        }
    }

    private function authenticateAPIKey($app, $credentials) {
        $apiKey = $credentials['api_key'] ?? '';

        $keyRecord = $this->db->querySingle("
            SELECT * FROM zapier_api_keys
            WHERE app_id = ? AND api_key = ? AND is_active = true
        ", [$app['id'], $apiKey]);

        if ($keyRecord) {
            $token = $this->generateAuthToken($app['id'], $keyRecord['id']);
            return [
                'success' => true,
                'token' => $token,
                'expires_at' => date('c', strtotime('+1 hour'))
            ];
        }

        return ['success' => false, 'error' => 'Invalid API key'];
    }

    private function authenticateOAuth2($app, $credentials) {
        // OAuth2 flow implementation
        // This would handle the OAuth2 authorization code flow
        return ['success' => false, 'error' => 'OAuth2 not implemented'];
    }

    private function authenticateBasic($app, $credentials) {
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';

        $authConfig = json_decode($app['auth_config'], true);

        if ($username === ($authConfig['username'] ?? '') &&
            $password === ($authConfig['password'] ?? '')) {
            $token = $this->generateAuthToken($app['id']);
            return [
                'success' => true,
                'token' => $token,
                'expires_at' => date('c', strtotime('+1 hour'))
            ];
        }

        return ['success' => false, 'error' => 'Invalid credentials'];
    }

    private function generateAuthToken($appId, $keyId = null) {
        $tokenData = [
            'app_id' => $appId,
            'key_id' => $keyId,
            'user_id' => $this->user['id'],
            'company_id' => $this->user['company_id'],
            'created_at' => time(),
            'expires_at' => time() + 3600 // 1 hour
        ];

        $token = base64_encode(json_encode($tokenData));
        return $token;
    }

    public function getAppDefinition() {
        $appSlug = $_GET['app'] ?? '';

        $app = $this->db->querySingle("
            SELECT * FROM zapier_apps
            WHERE webhook_url LIKE ? AND is_active = true
        ", ["%/{$appSlug}"]);

        if (!$app) {
            $this->jsonResponse(['error' => 'App not found'], 404);
        }

        $definition = [
            'name' => $app['name'],
            'description' => $app['description'],
            'version' => $app['version'],
            'triggers' => $this->getTriggerDefinitions($app),
            'actions' => $this->getActionDefinitions($app),
            'authentication' => $this->getAuthDefinition($app)
        ];

        $this->jsonResponse($definition);
    }

    private function getTriggerDefinitions($app) {
        $triggers = json_decode($app['triggers'], true);
        $definitions = [];

        foreach ($triggers as $trigger) {
            $definitions[$trigger] = $this->getAvailableTriggers()[$trigger] ?? [];
        }

        return $definitions;
    }

    private function getActionDefinitions($app) {
        $actions = json_decode($app['actions'], true);
        $definitions = [];

        foreach ($actions as $action) {
            $definitions[$action] = $this->getAvailableActions()[$action] ?? [];
        }

        return $definitions;
    }

    private function getAuthDefinition($app) {
        return [
            'type' => $app['auth_method'],
            'fields' => $this->getAuthMethods()[$app['auth_method']]['fields'] ?? []
        ];
    }
}
?>
