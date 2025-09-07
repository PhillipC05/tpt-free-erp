<?php
/**
 * TPT Free ERP - White-labeling & Customization Module
 * Complete branding, customization, and multi-tenant management
 */

class WhiteLabel extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main white-labeling dashboard
     */
    public function index() {
        $this->requirePermission('whitelabel.view');

        $data = [
            'title' => 'White-labeling & Customization',
            'branding_settings' => $this->getBrandingSettings(),
            'custom_themes' => $this->getCustomThemes(),
            'tenant_configurations' => $this->getTenantConfigurations(),
            'custom_workflows' => $this->getCustomWorkflows(),
            'domain_settings' => $this->getDomainSettings(),
            'customization_stats' => $this->getCustomizationStats()
        ];

        $this->render('modules/whitelabel/dashboard', $data);
    }

    /**
     * Branding management
     */
    public function branding() {
        $this->requirePermission('whitelabel.branding.view');

        $data = [
            'title' => 'Branding Management',
            'logo_settings' => $this->getLogoSettings(),
            'color_schemes' => $this->getColorSchemes(),
            'typography_settings' => $this->getTypographySettings(),
            'favicon_settings' => $this->getFaviconSettings(),
            'email_templates' => $this->getEmailTemplates(),
            'branding_previews' => $this->getBrandingPreviews()
        ];

        $this->render('modules/whitelabel/branding', $data);
    }

    /**
     * Theme customization
     */
    public function themes() {
        $this->requirePermission('whitelabel.themes.view');

        $data = [
            'title' => 'Theme Customization',
            'available_themes' => $this->getAvailableThemes(),
            'custom_themes' => $this->getCustomThemes(),
            'theme_components' => $this->getThemeComponents(),
            'theme_variables' => $this->getThemeVariables(),
            'theme_previews' => $this->getThemePreviews(),
            'theme_templates' => $this->getThemeTemplates()
        ];

        $this->render('modules/whitelabel/themes', $data);
    }

    /**
     * Multi-tenant management
     */
    public function tenants() {
        $this->requirePermission('whitelabel.tenants.view');

        $data = [
            'title' => 'Multi-tenant Management',
            'tenants' => $this->getTenants(),
            'tenant_settings' => $this->getTenantSettings(),
            'tenant_limits' => $this->getTenantLimits(),
            'tenant_usage' => $this->getTenantUsage(),
            'tenant_billing' => $this->getTenantBilling(),
            'tenant_templates' => $this->getTenantTemplates()
        ];

        $this->render('modules/whitelabel/tenants', $data);
    }

    /**
     * Custom workflow builder
     */
    public function workflows() {
        $this->requirePermission('whitelabel.workflows.view');

        $data = [
            'title' => 'Custom Workflow Builder',
            'workflow_templates' => $this->getWorkflowTemplates(),
            'workflow_components' => $this->getWorkflowComponents(),
            'custom_workflows' => $this->getCustomWorkflows(),
            'workflow_triggers' => $this->getWorkflowTriggers(),
            'workflow_actions' => $this->getWorkflowActions(),
            'workflow_conditions' => $this->getWorkflowConditions()
        ];

        $this->render('modules/whitelabel/workflows', $data);
    }

    /**
     * Domain and URL management
     */
    public function domains() {
        $this->requirePermission('whitelabel.domains.view');

        $data = [
            'title' => 'Domain Management',
            'custom_domains' => $this->getCustomDomains(),
            'domain_settings' => $this->getDomainSettings(),
            'ssl_certificates' => $this->getSSLCertificates(),
            'dns_settings' => $this->getDNSSettings(),
            'redirect_rules' => $this->getRedirectRules(),
            'domain_analytics' => $this->getDomainAnalytics()
        ];

        $this->render('modules/whitelabel/domains', $data);
    }

    /**
     * Reseller management
     */
    public function resellers() {
        $this->requirePermission('whitelabel.resellers.view');

        $data = [
            'title' => 'Reseller Management',
            'resellers' => $this->getResellers(),
            'reseller_plans' => $this->getResellerPlans(),
            'reseller_commissions' => $this->getResellerCommissions(),
            'reseller_customers' => $this->getResellerCustomers(),
            'reseller_reports' => $this->getResellerReports(),
            'reseller_support' => $this->getResellerSupport()
        ];

        $this->render('modules/whitelabel/resellers', $data);
    }

    /**
     * Custom integrations
     */
    public function integrations() {
        $this->requirePermission('whitelabel.integrations.view');

        $data = [
            'title' => 'Custom Integrations',
            'api_endpoints' => $this->getAPIEndpoints(),
            'webhooks' => $this->getWebhooks(),
            'custom_connectors' => $this->getCustomConnectors(),
            'data_mappings' => $this->getDataMappings(),
            'integration_logs' => $this->getIntegrationLogs(),
            'integration_templates' => $this->getIntegrationTemplates()
        ];

        $this->render('modules/whitelabel/integrations', $data);
    }

    /**
     * Localization and internationalization
     */
    public function localization() {
        $this->requirePermission('whitelabel.localization.view');

        $data = [
            'title' => 'Localization & i18n',
            'supported_languages' => $this->getSupportedLanguages(),
            'language_packs' => $this->getLanguagePacks(),
            'translation_keys' => $this->getTranslationKeys(),
            'currency_settings' => $this->getCurrencySettings(),
            'timezone_settings' => $this->getTimezoneSettings(),
            'regional_settings' => $this->getRegionalSettings()
        ];

        $this->render('modules/whitelabel/localization', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getBrandingSettings() {
        return $this->db->querySingle("
            SELECT * FROM branding_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCustomThemes() {
        return $this->db->query("
            SELECT
                ct.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(ctu.user_id) as usage_count
            FROM custom_themes ct
            LEFT JOIN users u ON ct.created_by = u.id
            LEFT JOIN custom_theme_usage ctu ON ct.id = ctu.theme_id
            WHERE ct.company_id = ?
            GROUP BY ct.id, u.first_name, u.last_name
            ORDER BY ct.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getTenantConfigurations() {
        return $this->db->query("
            SELECT
                tc.*,
                t.name as tenant_name,
                COUNT(tu.user_id) as user_count,
                SUM(tu.storage_used) as total_storage_used
            FROM tenant_configurations tc
            JOIN tenants t ON tc.tenant_id = t.id
            LEFT JOIN tenant_usage tu ON t.id = tu.tenant_id
            WHERE tc.company_id = ?
            GROUP BY tc.id, t.name
            ORDER BY tc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomWorkflows() {
        return $this->db->query("
            SELECT
                cw.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(cwe.id) as execution_count,
                MAX(cwe.executed_at) as last_executed
            FROM custom_workflows cw
            LEFT JOIN users u ON cw.created_by = u.id
            LEFT JOIN custom_workflow_executions cwe ON cw.id = cwe.workflow_id
            WHERE cw.company_id = ?
            GROUP BY cw.id, u.first_name, u.last_name
            ORDER BY cw.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getDomainSettings() {
        return $this->db->query("
            SELECT * FROM domain_settings
            WHERE company_id = ?
            ORDER BY is_primary DESC, created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomizationStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT ct.id) as custom_themes_count,
                COUNT(DISTINCT cw.id) as custom_workflows_count,
                COUNT(DISTINCT cd.id) as custom_domains_count,
                COUNT(DISTINCT t.id) as tenants_count,
                AVG(tc.customization_level) as avg_customization_level
            FROM custom_themes ct
            LEFT JOIN custom_workflows cw ON cw.company_id = ct.company_id
            LEFT JOIN custom_domains cd ON cd.company_id = ct.company_id
            LEFT JOIN tenants t ON t.company_id = ct.company_id
            LEFT JOIN tenant_configurations tc ON tc.company_id = ct.company_id
            WHERE ct.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getLogoSettings() {
        return $this->db->querySingle("
            SELECT * FROM logo_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getColorSchemes() {
        return $this->db->query("
            SELECT
                cs.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(csu.user_id) as usage_count
            FROM color_schemes cs
            LEFT JOIN users u ON cs.created_by = u.id
            LEFT JOIN color_scheme_usage csu ON cs.id = csu.scheme_id
            WHERE cs.company_id = ?
            GROUP BY cs.id, u.first_name, u.last_name
            ORDER BY cs.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getTypographySettings() {
        return $this->db->querySingle("
            SELECT * FROM typography_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getFaviconSettings() {
        return $this->db->querySingle("
            SELECT * FROM favicon_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEmailTemplates() {
        return $this->db->query("
            SELECT
                et.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(etu.id) as usage_count
            FROM email_templates et
            LEFT JOIN users u ON et.created_by = u.id
            LEFT JOIN email_template_usage etu ON et.id = etu.template_id
            WHERE et.company_id = ?
            GROUP BY et.id, u.first_name, u.last_name
            ORDER BY et.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getBrandingPreviews() {
        return $this->db->query("
            SELECT * FROM branding_previews
            WHERE company_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getAvailableThemes() {
        return $this->db->query("
            SELECT * FROM available_themes
            WHERE is_active = true
            ORDER BY name ASC
        ");
    }

    private function getThemeComponents() {
        return [
            'header' => 'Header Component',
            'navigation' => 'Navigation Menu',
            'sidebar' => 'Sidebar Panel',
            'dashboard' => 'Dashboard Widgets',
            'forms' => 'Form Elements',
            'tables' => 'Data Tables',
            'modals' => 'Modal Dialogs',
            'buttons' => 'Button Styles',
            'cards' => 'Card Components',
            'alerts' => 'Alert Messages'
        ];
    }

    private function getThemeVariables() {
        return $this->db->query("
            SELECT * FROM theme_variables
            WHERE company_id = ?
            ORDER BY category, variable_name
        ", [$this->user['company_id']]);
    }

    private function getThemePreviews() {
        return $this->db->query("
            SELECT * FROM theme_previews
            WHERE company_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getThemeTemplates() {
        return $this->db->query("
            SELECT * FROM theme_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getTenants() {
        return $this->db->query("
            SELECT
                t.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(tu.user_id) as user_count,
                SUM(tu.storage_used) as storage_used,
                MAX(tu.last_activity) as last_activity
            FROM tenants t
            LEFT JOIN users u ON t.created_by = u.id
            LEFT JOIN tenant_users tu ON t.id = tu.tenant_id
            WHERE t.company_id = ?
            GROUP BY t.id, u.first_name, u.last_name
            ORDER BY t.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getTenantSettings() {
        return $this->db->query("
            SELECT * FROM tenant_settings
            WHERE company_id = ?
            ORDER BY tenant_id, setting_key
        ", [$this->user['company_id']]);
    }

    private function getTenantLimits() {
        return $this->db->query("
            SELECT
                tl.*,
                t.name as tenant_name,
                tl.current_usage,
                ROUND((tl.current_usage / NULLIF(tl.limit_value, 0)) * 100, 2) as usage_percentage
            FROM tenant_limits tl
            JOIN tenants t ON tl.tenant_id = t.id
            WHERE tl.company_id = ?
            ORDER BY tl.limit_type, tl.usage_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getTenantUsage() {
        return $this->db->query("
            SELECT
                tu.*,
                t.name as tenant_name,
                u.first_name as user_first,
                u.last_name as user_last
            FROM tenant_usage tu
            JOIN tenants t ON tu.tenant_id = t.id
            LEFT JOIN users u ON tu.user_id = u.id
            WHERE tu.company_id = ?
            ORDER BY tu.last_activity DESC
        ", [$this->user['company_id']]);
    }

    private function getTenantBilling() {
        return $this->db->query("
            SELECT
                tb.*,
                t.name as tenant_name,
                tb.amount_due,
                tb.amount_paid,
                tb.due_date,
                CASE
                    WHEN tb.due_date < CURDATE() AND tb.status = 'unpaid' THEN 'overdue'
                    WHEN tb.due_date >= CURDATE() AND tb.status = 'unpaid' THEN 'pending'
                    ELSE tb.status
                END as billing_status
            FROM tenant_billing tb
            JOIN tenants t ON tb.tenant_id = t.id
            WHERE tb.company_id = ?
            ORDER BY tb.due_date DESC
        ", [$this->user['company_id']]);
    }

    private function getTenantTemplates() {
        return $this->db->query("
            SELECT * FROM tenant_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getWorkflowTemplates() {
        return $this->db->query("
            SELECT * FROM workflow_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getWorkflowComponents() {
        return [
            'triggers' => 'Workflow Triggers',
            'conditions' => 'Conditional Logic',
            'actions' => 'Workflow Actions',
            'delays' => 'Time Delays',
            'loops' => 'Loop Structures',
            'branches' => 'Conditional Branches',
            'webhooks' => 'Webhook Calls',
            'notifications' => 'Notifications',
            'approvals' => 'Approval Steps'
        ];
    }

    private function getWorkflowTriggers() {
        return $this->db->query("
            SELECT * FROM workflow_triggers
            WHERE company_id = ? AND is_active = true
            ORDER BY trigger_type, name
        ", [$this->user['company_id']]);
    }

    private function getWorkflowActions() {
        return $this->db->query("
            SELECT * FROM workflow_actions
            WHERE company_id = ? AND is_active = true
            ORDER BY action_type, name
        ", [$this->user['company_id']]);
    }

    private function getWorkflowConditions() {
        return $this->db->query("
            SELECT * FROM workflow_conditions
            WHERE company_id = ? AND is_active = true
            ORDER BY condition_type, name
        ", [$this->user['company_id']]);
    }

    private function getCustomDomains() {
        return $this->db->query("
            SELECT
                cd.*,
                u.first_name as configured_by_first,
                u.last_name as configured_by_last,
                cd.ssl_status,
                cd.dns_status,
                TIMESTAMPDIFF(DAY, cd.ssl_expiry, CURDATE()) as days_until_ssl_expiry
            FROM custom_domains cd
            LEFT JOIN users u ON cd.configured_by = u.id
            WHERE cd.company_id = ?
            ORDER BY cd.is_primary DESC, cd.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSSLCertificates() {
        return $this->db->query("
            SELECT
                ssl.*,
                cd.domain_name,
                TIMESTAMPDIFF(DAY, ssl.expiry_date, CURDATE()) as days_until_expiry,
                CASE
                    WHEN ssl.expiry_date < CURDATE() THEN 'expired'
                    WHEN ssl.expiry_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
                    ELSE 'valid'
                END as certificate_status
            FROM ssl_certificates ssl
            JOIN custom_domains cd ON ssl.domain_id = cd.id
            WHERE ssl.company_id = ?
            ORDER BY ssl.expiry_date ASC
        ", [$this->user['company_id']]);
    }

    private function getDNSSettings() {
        return $this->db->query("
            SELECT
                dns.*,
                cd.domain_name,
                dns.record_type,
                dns.record_value,
                dns.verification_status
            FROM dns_settings dns
            JOIN custom_domains cd ON dns.domain_id = cd.id
            WHERE dns.company_id = ?
            ORDER BY cd.domain_name, dns.record_type
        ", [$this->user['company_id']]);
    }

    private function getRedirectRules() {
        return $this->db->query("
            SELECT
                rr.*,
                cd.domain_name,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                rr.redirect_type,
                rr.is_active
            FROM redirect_rules rr
            JOIN custom_domains cd ON rr.domain_id = cd.id
            LEFT JOIN users u ON rr.created_by = u.id
            WHERE rr.company_id = ?
            ORDER BY rr.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getDomainAnalytics() {
        return $this->db->query("
            SELECT
                da.*,
                cd.domain_name,
                da.page_views,
                da.unique_visitors,
                da.bounce_rate,
                da.avg_session_duration
            FROM domain_analytics da
            JOIN custom_domains cd ON da.domain_id = cd.id
            WHERE da.company_id = ?
            ORDER BY da.date DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getResellers() {
        return $this->db->query("
            SELECT
                r.*,
                u.first_name as contact_first,
                u.last_name as contact_last,
                COUNT(rc.customer_id) as customer_count,
                SUM(rc.commission_earned) as total_commissions,
                MAX(rc.last_sale) as last_sale_date
            FROM resellers r
            LEFT JOIN users u ON r.contact_user_id = u.id
            LEFT JOIN reseller_customers rc ON r.id = rc.reseller_id
            WHERE r.company_id = ?
            GROUP BY r.id, u.first_name, u.last_name
            ORDER BY r.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getResellerPlans() {
        return $this->db->query("
            SELECT * FROM reseller_plans
            WHERE company_id = ? AND is_active = true
            ORDER BY commission_percentage DESC, name
        ", [$this->user['company_id']]);
    }

    private function getResellerCommissions() {
        return $this->db->query("
            SELECT
                rc.*,
                r.name as reseller_name,
                u.first_name as customer_first,
                u.last_name as customer_last,
                rc.commission_amount,
                rc.commission_percentage,
                rc.paid_status
            FROM reseller_commissions rc
            JOIN resellers r ON rc.reseller_id = r.id
            LEFT JOIN users u ON rc.customer_id = u.id
            WHERE rc.company_id = ?
            ORDER BY rc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getResellerCustomers() {
        return $this->db->query("
            SELECT
                rc.*,
                r.name as reseller_name,
                u.first_name as customer_first,
                u.last_name as customer_last,
                rc.signup_date,
                rc.last_payment_date,
                rc.total_paid
            FROM reseller_customers rc
            JOIN resellers r ON rc.reseller_id = r.id
            LEFT JOIN users u ON rc.customer_id = u.id
            WHERE rc.company_id = ?
            ORDER BY rc.signup_date DESC
        ", [$this->user['company_id']]);
    }

    private function getResellerReports() {
        return $this->db->query("
            SELECT
                rr.*,
                r.name as reseller_name,
                rr.report_period,
                rr.total_sales,
                rr.total_commissions,
                rr.customer_count
            FROM reseller_reports rr
            JOIN resellers r ON rr.reseller_id = r.id
            WHERE rr.company_id = ?
            ORDER BY rr.report_period DESC
        ", [$this->user['company_id']]);
    }

    private function getResellerSupport() {
        return $this->db->query("
            SELECT
                rs.*,
                r.name as reseller_name,
                u.first_name as requested_by_first,
                u.last_name as requested_by_last,
                rs.priority,
                rs.status,
                TIMESTAMPDIFF(HOUR, rs.created_at, NOW()) as hours_open
            FROM reseller_support rs
            JOIN resellers r ON rs.reseller_id = r.id
            LEFT JOIN users u ON rs.requested_by = u.id
            WHERE rs.company_id = ?
            ORDER BY rs.priority DESC, rs.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAPIEndpoints() {
        return $this->db->query("
            SELECT
                ae.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(ael.id) as call_count,
                AVG(ael.response_time_ms) as avg_response_time
            FROM api_endpoints ae
            LEFT JOIN users u ON ae.created_by = u.id
            LEFT JOIN api_endpoint_logs ael ON ae.id = ael.endpoint_id
            WHERE ae.company_id = ?
            GROUP BY ae.id, u.first_name, u.last_name
            ORDER BY ae.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getWebhooks() {
        return $this->db->query("
            SELECT
                w.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(wd.id) as delivery_count,
                COUNT(CASE WHEN wd.status = 'success' THEN 1 END) as success_count,
                MAX(wd.delivered_at) as last_delivery
            FROM webhooks w
            LEFT JOIN users u ON w.created_by = u.id
            LEFT JOIN webhook_deliveries wd ON w.id = wd.webhook_id
            WHERE w.company_id = ?
            GROUP BY w.id, u.first_name, u.last_name
            ORDER BY w.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomConnectors() {
        return $this->db->query("
            SELECT
                cc.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(cci.id) as integration_count,
                MAX(cci.last_sync) as last_sync
            FROM custom_connectors cc
            LEFT JOIN users u ON cc.created_by = u.id
            LEFT JOIN custom_connector_integrations cci ON cc.id = cci.connector_id
            WHERE cc.company_id = ?
            GROUP BY cc.id, u.first_name, u.last_name
            ORDER BY cc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getDataMappings() {
        return $this->db->query("
            SELECT
                dm.*,
                cc.name as connector_name,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                dm.mapping_status,
                COUNT(dmr.id) as record_count
            FROM data_mappings dm
            JOIN custom_connectors cc ON dm.connector_id = cc.id
            LEFT JOIN users u ON dm.created_by = u.id
            LEFT JOIN data_mapping_records dmr ON dm.id = dmr.mapping_id
            WHERE dm.company_id = ?
            GROUP BY dm.id, cc.name, u.first_name, u.last_name
            ORDER BY dm.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getIntegrationLogs() {
        return $this->db->query("
            SELECT
                il.*,
                cc.name as connector_name,
                il.log_level,
                il.message,
                TIMESTAMPDIFF(MINUTE, il.created_at, NOW()) as minutes_ago
            FROM integration_logs il
            LEFT JOIN custom_connectors cc ON il.connector_id = cc.id
            WHERE il.company_id = ?
            ORDER BY il.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getIntegrationTemplates() {
        return $this->db->query("
            SELECT * FROM integration_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getSupportedLanguages() {
        return $this->db->query("
            SELECT * FROM supported_languages
            WHERE is_active = true
            ORDER BY name ASC
        ");
    }

    private function getLanguagePacks() {
        return $this->db->query("
            SELECT
                lp.*,
                sl.name as language_name,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(lpt.translation_key) as translation_count,
                lp.completion_percentage
            FROM language_packs lp
            JOIN supported_languages sl ON lp.language_id = sl.id
            LEFT JOIN users u ON lp.created_by = u.id
            LEFT JOIN language_pack_translations lpt ON lp.id = lpt.pack_id
            WHERE lp.company_id = ?
            GROUP BY lp.id, sl.name, u.first_name, u.last_name
            ORDER BY lp.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getTranslationKeys() {
        return $this->db->query("
            SELECT
                tk.*,
                COUNT(lpt.id) as translation_count,
                COUNT(CASE WHEN lpt.translation_value IS NOT NULL THEN 1 END) as completed_translations
            FROM translation_keys tk
            LEFT JOIN language_pack_translations lpt ON tk.id = lpt.translation_key_id
            WHERE tk.company_id = ?
            GROUP BY tk.id
            ORDER BY tk.category, tk.key_name
        ", [$this->user['company_id']]);
    }

    private function getCurrencySettings() {
        return $this->db->query("
            SELECT * FROM currency_settings
            WHERE company_id = ?
            ORDER BY is_default DESC, currency_code
        ", [$this->user['company_id']]);
    }

    private function getTimezoneSettings() {
        return $this->db->query("
            SELECT * FROM timezone_settings
            WHERE company_id = ?
            ORDER BY timezone_name
        ", [$this->user['company_id']]);
    }

    private function getRegionalSettings() {
        return $this->db->query("
            SELECT * FROM regional_settings
            WHERE company_id = ?
            ORDER BY region_name
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function updateBranding() {
        $this->requirePermission('whitelabel.branding.update');

        $data = $this->validateRequest([
            'company_name' => 'string',
            'logo_url' => 'string',
            'primary_color' => 'string',
            'secondary_color' => 'string',
            'accent_color' => 'string',
            'font_family' => 'string',
            'favicon_url' => 'string'
        ]);

        try {
            $brandingId = $this->db->insert('branding_settings', [
                'company_id' => $this->user['company_id'],
                'company_name' => $data['company_name'] ?? '',
                'logo_url' => $data['logo_url'] ?? '',
                'primary_color' => $data['primary_color'] ?? '#007bff',
                'secondary_color' => $data['secondary_color'] ?? '#6c757d',
                'accent_color' => $data['accent_color'] ?? '#28a745',
                'font_family' => $data['font_family'] ?? 'Arial, sans-serif',
                'favicon_url' => $data['favicon_url'] ?? '',
                'updated_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'branding_id' => $brandingId,
                'message' => 'Branding settings updated successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createCustomTheme() {
        $this->requirePermission('whitelabel.themes.create');

        $data = $this->validateRequest([
            'name' => 'required|string',
            'description' => 'string',
            'variables' => 'required|array',
            'components' => 'array',
            'is_public' => 'boolean'
        ]);

        try {
            $themeId = $this->db->insert('custom_themes', [
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'variables' => json_encode($data['variables']),
                'components' => json_encode($data['components'] ?? []),
                'is_public' => $data['is_public'] ?? false,
                'created_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'theme_id' => $themeId,
                'message' => 'Custom theme created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createTenant() {
        $this->requirePermission('whitelabel.tenants.create');

        $data = $this->validateRequest([
            'name' => 'required|string',
            'domain' => 'required|string',
            'admin_email' => 'required|email',
            'plan_id' => 'required|integer',
            'settings' => 'array',
            'limits' => 'array'
        ]);

        try {
            $this->db->beginTransaction();

            $tenantId = $this->db->insert('tenants', [
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'domain' => $data['domain'],
                'admin_email' => $data['admin_email'],
                'plan_id' => $data['plan_id'],
                'status' => 'active',
                'created_by' => $this->user['id']
            ]);

            // Create tenant configuration
            if (!empty($data['settings'])) {
                foreach ($data['settings'] as $key => $value) {
                    $this->db->insert('tenant_configurations', [
                        'company_id' => $this->user['company_id'],
                        'tenant_id' => $tenantId,
                        'setting_key' => $key,
                        'setting_value' => json_encode($value),
                        'created_by' => $this->user['id']
                    ]);
                }
            }

            // Set tenant limits
            if (!empty($data['limits'])) {
                foreach ($data['limits'] as $type => $limit) {
                    $this->db->insert('tenant_limits', [
                        'company_id' => $this->user['company_id'],
                        'tenant_id' => $tenantId,
                        'limit_type' => $type,
                        'limit_value' => $limit,
                        'current_usage' => 0,
                        'created_by' => $this->user['id']
                    ]);
                }
            }

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'tenant_id' => $tenantId,
                'message' => 'Tenant created successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createCustomWorkflow() {
        $this->requirePermission('whitelabel.workflows.create');

        $data = $this->validateRequest([
            'name' => 'required|string',
            'description' => 'string',
            'trigger_id' => 'required|integer',
            'conditions' => 'array',
            'actions' => 'required|array',
            'is_active' => 'boolean'
        ]);

        try {
            $workflowId = $this->db->insert('custom_workflows', [
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'trigger_id' => $data['trigger_id'],
                'conditions' => json_encode($data['conditions'] ?? []),
                'actions' => json_encode($data['actions']),
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'workflow_id' => $workflowId,
                'message' => 'Custom workflow created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addCustomDomain() {
        $this->requirePermission('whitelabel.domains.add');

        $data = $this->validateRequest([
            'domain_name' => 'required|string',
            'is_primary' => 'boolean',
            'ssl_auto_renew' => 'boolean'
        ]);

        try {
            $domainId = $this->db->insert('custom_domains', [
                'company_id' => $this->user['company_id'],
                'domain_name' => $data['domain_name'],
                'is_primary' => $data['is_primary'] ?? false,
                'ssl_status' => 'pending',
                'dns_status' => 'pending',
                'ssl_auto_renew' => $data['ssl_auto_renew'] ?? true,
                'configured_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'domain_id' => $domainId,
                'message' => 'Custom domain added successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createReseller() {
        $this->requirePermission('whitelabel.resellers.create');

        $data = $this->validateRequest([
            'name' => 'required|string',
            'contact_email' => 'required|email',
            'contact_phone' => 'string',
            'commission_percentage' => 'required|numeric',
            'plan_id' => 'required|integer',
            'is_active' => 'boolean'
        ]);

        try {
            $resellerId = $this->db->insert('resellers', [
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?? '',
                'commission_percentage' => $data['commission_percentage'],
                'plan_id' => $data['plan_id'],
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'reseller_id' => $resellerId,
                'message' => 'Reseller created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createAPIEndpoint() {
        $this->requirePermission('whitelabel.integrations.create');

        $data = $this->validateRequest([
            'name' => 'required|string',
            'endpoint_url' => 'required|string',
            'method' => 'required|string',
            'headers' => 'array',
            'parameters' => 'array',
            'authentication' => 'array',
            'is_active' => 'boolean'
        ]);

        try {
            $endpointId = $this->db->insert('api_endpoints', [
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'endpoint_url' => $data['endpoint_url'],
                'method' => $data['method'],
                'headers' => json_encode($data['headers'] ?? []),
                'parameters' => json_encode($data['parameters'] ?? []),
                'authentication' => json_encode($data['authentication'] ?? []),
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'endpoint_id' => $endpointId,
                'message' => 'API endpoint created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addLanguagePack() {
        $this->requirePermission('whitelabel.localization.add');

        $data = $this->validateRequest([
            'language_id' => 'required|integer',
            'name' => 'required|string',
            'description' => 'string',
            'is_default' => 'boolean'
        ]);

        try {
            $packId = $this->db->insert('language_packs', [
                'company_id' => $this->user['company_id'],
                'language_id' => $data['language_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'is_default' => $data['is_default'] ?? false,
                'completion_percentage' => 0,
                'created_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'pack_id' => $packId,
                'message' => 'Language pack added successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
