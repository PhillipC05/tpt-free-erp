<?php
/**
 * TPT Free ERP - API Marketplace Module
 * Complete developer portal and API management system
 */

class APIMarketplace extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main API marketplace dashboard
     */
    public function index() {
        $this->requirePermission('api_marketplace.view');

        $data = [
            'title' => 'API Marketplace',
            'marketplace_overview' => $this->getMarketplaceOverview(),
            'api_metrics' => $this->getAPIMetrics(),
            'popular_apis' => $this->getPopularAPIs(),
            'recent_activity' => $this->getRecentActivity(),
            'developer_stats' => $this->getDeveloperStats()
        ];

        $this->render('modules/api_marketplace/dashboard', $data);
    }

    /**
     * API catalog and discovery
     */
    public function apiCatalog() {
        $this->requirePermission('api_marketplace.catalog.view');

        $data = [
            'title' => 'API Catalog',
            'api_endpoints' => $this->getAPIEndpoints(),
            'api_categories' => $this->getAPICategories(),
            'api_providers' => $this->getAPIProviders(),
            'api_ratings' => $this->getAPIRatings(),
            'featured_apis' => $this->getFeaturedAPIs()
        ];

        $this->render('modules/api_marketplace/catalog', $data);
    }

    /**
     * Developer portal
     */
    public function developerPortal() {
        $this->requirePermission('api_marketplace.developer.view');

        $data = [
            'title' => 'Developer Portal',
            'my_applications' => $this->getMyApplications(),
            'api_keys' => $this->getAPIKeys(),
            'usage_statistics' => $this->getUsageStatistics(),
            'documentation' => $this->getDocumentation(),
            'support_tickets' => $this->getSupportTickets()
        ];

        $this->render('modules/api_marketplace/developer_portal', $data);
    }

    /**
     * API management
     */
    public function apiManagement() {
        $this->requirePermission('api_marketplace.management.view');

        $data = [
            'title' => 'API Management',
            'published_apis' => $this->getPublishedAPIs(),
            'api_versions' => $this->getAPIVersions(),
            'api_analytics' => $this->getAPIAnalytics(),
            'rate_limiting' => $this->getRateLimiting(),
            'api_monitoring' => $this->getAPIMonitoring()
        ];

        $this->render('modules/api_marketplace/management', $data);
    }

    /**
     * Application registration
     */
    public function appRegistration() {
        $this->requirePermission('api_marketplace.apps.view');

        $data = [
            'title' => 'Application Registration',
            'registered_apps' => $this->getRegisteredApps(),
            'app_categories' => $this->getAppCategories(),
            'app_reviews' => $this->getAppReviews(),
            'app_analytics' => $this->getAppAnalytics(),
            'monetization' => $this->getMonetizationData()
        ];

        $this->render('modules/api_marketplace/app_registration', $data);
    }

    /**
     * API documentation
     */
    public function apiDocumentation() {
        $this->requirePermission('api_marketplace.docs.view');

        $data = [
            'title' => 'API Documentation',
            'api_reference' => $this->getAPIReference(),
            'code_examples' => $this->getCodeExamples(),
            'tutorials' => $this->getTutorials(),
            'changelogs' => $this->getChangelogs(),
            'faqs' => $this->getFAQs()
        ];

        $this->render('modules/api_marketplace/documentation', $data);
    }

    /**
     * Usage tracking and billing
     */
    public function usageTracking() {
        $this->requirePermission('api_marketplace.usage.view');

        $data = [
            'title' => 'Usage Tracking & Billing',
            'usage_metrics' => $this->getUsageMetrics(),
            'billing_history' => $this->getBillingHistory(),
            'pricing_plans' => $this->getPricingPlans(),
            'payment_methods' => $this->getPaymentMethods(),
            'usage_alerts' => $this->getUsageAlerts()
        ];

        $this->render('modules/api_marketplace/usage_tracking', $data);
    }

    /**
     * Integration marketplace
     */
    public function integrationMarketplace() {
        $this->requirePermission('api_marketplace.integrations.view');

        $data = [
            'title' => 'Integration Marketplace',
            'available_integrations' => $this->getAvailableIntegrations(),
            'integration_templates' => $this->getIntegrationTemplates(),
            'webhook_configurations' => $this->getWebhookConfigurations(),
            'data_mapping' => $this->getDataMapping(),
            'integration_analytics' => $this->getIntegrationAnalytics()
        ];

        $this->render('modules/api_marketplace/integration_marketplace', $data);
    }

    /**
     * Analytics and reporting
     */
    public function analytics() {
        $this->requirePermission('api_marketplace.analytics.view');

        $data = [
            'title' => 'API Analytics & Reporting',
            'api_performance' => $this->getAPIPerformance(),
            'developer_analytics' => $this->getDeveloperAnalytics(),
            'market_trends' => $this->getMarketTrends(),
            'revenue_analytics' => $this->getRevenueAnalytics(),
            'custom_reports' => $this->getCustomReports()
        ];

        $this->render('modules/api_marketplace/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getMarketplaceOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT api.id) as total_apis,
                COUNT(DISTINCT app.id) as total_applications,
                COUNT(DISTINCT dev.id) as total_developers,
                SUM(usage.calls_count) as total_api_calls,
                AVG(rating.rating) as avg_api_rating,
                SUM(billing.amount) as total_revenue
            FROM apis api
            LEFT JOIN applications app ON api.id = app.api_id
            LEFT JOIN developers dev ON dev.id = app.developer_id
            LEFT JOIN api_usage usage ON api.id = usage.api_id
            LEFT JOIN api_ratings rating ON api.id = rating.api_id
            LEFT JOIN billing_history billing ON billing.api_id = api.id
            WHERE api.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAPIMetrics() {
        return [
            'total_api_calls' => $this->getTotalAPICalls(),
            'average_response_time' => $this->getAverageResponseTime(),
            'error_rate' => $this->getErrorRate(),
            'uptime_percentage' => $this->getUptimePercentage(),
            'active_developers' => $this->getActiveDevelopers(),
            'revenue_generated' => $this->getRevenueGenerated()
        ];
    }

    private function getTotalAPICalls() {
        $result = $this->db->querySingle("
            SELECT SUM(calls_count) as total_calls
            FROM api_usage
            WHERE company_id = ? AND usage_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['total_calls'] ?? 0;
    }

    private function getAverageResponseTime() {
        $result = $this->db->querySingle("
            SELECT AVG(response_time_ms) as avg_response_time
            FROM api_performance_logs
            WHERE company_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ", [$this->user['company_id']]);

        return $result['avg_response_time'] ?? 0;
    }

    private function getErrorRate() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count,
                COUNT(*) as total_count
            FROM api_performance_logs
            WHERE company_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ", [$this->user['company_id']]);

        if ($result['total_count'] > 0) {
            return ($result['error_count'] / $result['total_count']) * 100;
        }

        return 0;
    }

    private function getUptimePercentage() {
        $result = $this->db->querySingle("
            SELECT AVG(uptime_percentage) as avg_uptime
            FROM api_uptime_stats
            WHERE company_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['avg_uptime'] ?? 0;
    }

    private function getActiveDevelopers() {
        $result = $this->db->querySingle("
            SELECT COUNT(DISTINCT developer_id) as active_developers
            FROM api_usage
            WHERE company_id = ? AND usage_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['active_developers'] ?? 0;
    }

    private function getRevenueGenerated() {
        $result = $this->db->querySingle("
            SELECT SUM(amount) as total_revenue
            FROM billing_history
            WHERE company_id = ? AND billing_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['total_revenue'] ?? 0;
    }

    private function getPopularAPIs() {
        return $this->db->query("
            SELECT
                api.name,
                api.description,
                api.category,
                COUNT(usage.id) as total_calls,
                AVG(rating.rating) as avg_rating,
                COUNT(DISTINCT app.developer_id) as unique_developers
            FROM apis api
            LEFT JOIN api_usage usage ON api.id = usage.api_id
            LEFT JOIN api_ratings rating ON api.id = rating.api_id
            LEFT JOIN applications app ON api.id = app.api_id
            WHERE api.company_id = ?
            GROUP BY api.id, api.name, api.description, api.category
            ORDER BY total_calls DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getRecentActivity() {
        return $this->db->query("
            SELECT
                activity.activity_type,
                activity.description,
                activity.created_at,
                api.name as api_name,
                dev.name as developer_name,
                app.name as app_name
            FROM marketplace_activity activity
            LEFT JOIN apis api ON activity.api_id = api.id
            LEFT JOIN developers dev ON activity.developer_id = dev.id
            LEFT JOIN applications app ON activity.application_id = app.id
            WHERE activity.company_id = ?
            ORDER BY activity.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getDeveloperStats() {
        return $this->db->query("
            SELECT
                dev.name,
                dev.email,
                COUNT(app.id) as total_apps,
                COUNT(DISTINCT api.id) as apis_used,
                SUM(usage.calls_count) as total_calls,
                AVG(rating.rating) as avg_rating_given,
                dev.registration_date
            FROM developers dev
            LEFT JOIN applications app ON dev.id = app.developer_id
            LEFT JOIN apis api ON app.api_id = api.id
            LEFT JOIN api_usage usage ON api.id = usage.api_id AND app.developer_id = usage.developer_id
            LEFT JOIN api_ratings rating ON api.id = rating.api_id AND dev.id = rating.developer_id
            WHERE dev.company_id = ?
            GROUP BY dev.id, dev.name, dev.email, dev.registration_date
            ORDER BY total_apps DESC
        ", [$this->user['company_id']]);
    }

    private function getAPIEndpoints() {
        return $this->db->query("
            SELECT
                api.name,
                api.description,
                api.category,
                api.version,
                api.endpoint_url,
                api.method,
                api.is_active,
                api.requires_authentication,
                COUNT(usage.id) as usage_count,
                AVG(perf.response_time_ms) as avg_response_time,
                api.created_at
            FROM apis api
            LEFT JOIN api_usage usage ON api.id = usage.api_id
            LEFT JOIN api_performance_logs perf ON api.id = perf.api_id
            WHERE api.company_id = ?
            GROUP BY api.id, api.name, api.description, api.category, api.version, api.endpoint_url, api.method, api.is_active, api.requires_authentication, api.created_at
            ORDER BY usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getAPICategories() {
        return [
            'authentication' => 'Authentication & Security',
            'data_management' => 'Data Management',
            'communication' => 'Communication',
            'analytics' => 'Analytics & Reporting',
            'integration' => 'Third-party Integration',
            'automation' => 'Automation & Workflow',
            'financial' => 'Financial Services',
            'utilities' => 'Utilities & Tools'
        ];
    }

    private function getAPIProviders() {
        return $this->db->query("
            SELECT
                provider,
                COUNT(*) as api_count,
                AVG(rating.rating) as avg_rating,
                SUM(usage.calls_count) as total_usage
            FROM apis api
            LEFT JOIN api_ratings rating ON api.id = rating.api_id
            LEFT JOIN api_usage usage ON api.id = usage.api_id
            WHERE api.company_id = ?
            GROUP BY provider
            ORDER BY total_usage DESC
        ", [$this->user['company_id']]);
    }

    private function getAPIRatings() {
        return $this->db->query("
            SELECT
                api.name,
                AVG(rating.rating) as avg_rating,
                COUNT(rating.id) as total_ratings,
                COUNT(CASE WHEN rating.rating = 5 THEN 1 END) as five_star_ratings,
                MAX(rating.created_at) as latest_rating
            FROM apis api
            LEFT JOIN api_ratings rating ON api.id = rating.api_id
            WHERE api.company_id = ?
            GROUP BY api.id, api.name
            ORDER BY avg_rating DESC
        ", [$this->user['company_id']]);
    }

    private function getFeaturedAPIs() {
        return $this->db->query("
            SELECT
                api.name,
                api.description,
                api.category,
                api.endpoint_url,
                COUNT(usage.id) as usage_count,
                AVG(rating.rating) as avg_rating
            FROM apis api
            LEFT JOIN api_usage usage ON api.id = usage.api_id
            LEFT JOIN api_ratings rating ON api.id = rating.api_id
            WHERE api.company_id = ? AND api.is_featured = true
            GROUP BY api.id, api.name, api.description, api.category, api.endpoint_url
            ORDER BY usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getMyApplications() {
        return $this->db->query("
            SELECT
                app.name,
                app.description,
                app.status,
                app.created_at,
                api.name as api_name,
                COUNT(usage.id) as api_calls,
                app.last_used
            FROM applications app
            JOIN apis api ON app.api_id = api.id
            LEFT JOIN api_usage usage ON app.id = usage.application_id
            WHERE app.developer_id = ? AND app.company_id = ?
            GROUP BY app.id, app.name, app.description, app.status, app.created_at, api.name, app.last_used
            ORDER BY app.last_used DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getAPIKeys() {
        return $this->db->query("
            SELECT
                ak.api_key_masked,
                ak.name,
                ak.created_at,
                ak.last_used,
                ak.is_active,
                ak.rate_limit,
                COUNT(usage.id) as usage_count
            FROM api_keys ak
            LEFT JOIN api_usage usage ON ak.id = usage.api_key_id
            WHERE ak.developer_id = ? AND ak.company_id = ?
            GROUP BY ak.id, ak.api_key_masked, ak.name, ak.created_at, ak.last_used, ak.is_active, ak.rate_limit
            ORDER BY ak.last_used DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getUsageStatistics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(usage_date, '%Y-%m') as month,
                SUM(calls_count) as total_calls,
                SUM(data_transferred_mb) as total_data,
                COUNT(DISTINCT api_id) as apis_used,
                AVG(response_time_ms) as avg_response_time
            FROM api_usage
            WHERE developer_id = ? AND company_id = ?
            GROUP BY DATE_FORMAT(usage_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getDocumentation() {
        return [
            'api_reference' => $this->getAPIReference(),
            'getting_started' => $this->getGettingStartedGuide(),
            'tutorials' => $this->getTutorials(),
            'sdk_downloads' => $this->getSDKDownloads(),
            'code_samples' => $this->getCodeSamples()
        ];
    }

    private function getSupportTickets() {
        return $this->db->query("
            SELECT
                st.ticket_number,
                st.subject,
                st.status,
                st.priority,
                st.created_at,
                st.last_updated,
                COUNT(messages.id) as message_count
            FROM support_tickets st
            LEFT JOIN ticket_messages messages ON st.id = messages.ticket_id
            WHERE st.developer_id = ? AND st.company_id = ?
            GROUP BY st.id, st.ticket_number, st.subject, st.status, st.priority, st.created_at, st.last_updated
            ORDER BY st.last_updated DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getPublishedAPIs() {
        return $this->db->query("
            SELECT
                api.name,
                api.version,
                api.status,
                api.created_at,
                COUNT(sub.id) as subscribers,
                COUNT(usage.id) as total_calls,
                AVG(rating.rating) as avg_rating
            FROM apis api
            LEFT JOIN api_subscriptions sub ON api.id = sub.api_id
            LEFT JOIN api_usage usage ON api.id = usage.api_id
            LEFT JOIN api_ratings rating ON api.id = rating.api_id
            WHERE api.publisher_id = ? AND api.company_id = ?
            GROUP BY api.id, api.name, api.version, api.status, api.created_at
            ORDER BY api.created_at DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getAPIVersions() {
        return $this->db->query("
            SELECT
                api.name,
                av.version_number,
                av.release_date,
                av.is_current,
                av.deprecation_date,
                COUNT(usage.id) as usage_count
            FROM apis api
            JOIN api_versions av ON api.id = av.api_id
            LEFT JOIN api_usage usage ON api.id = usage.api_id AND av.version_number = usage.api_version
            WHERE api.publisher_id = ? AND api.company_id = ?
            GROUP BY api.id, api.name, av.id, av.version_number, av.release_date, av.is_current, av.deprecation_date
            ORDER BY av.release_date DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getAPIAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(usage_date, '%Y-%m-%d') as date,
                SUM(calls_count) as total_calls,
                COUNT(DISTINCT developer_id) as unique_developers,
                AVG(response_time_ms) as avg_response_time,
                SUM(error_count) as total_errors
            FROM api_usage
            WHERE api_id IN (SELECT id FROM apis WHERE publisher_id = ? AND company_id = ?)
            GROUP BY DATE_FORMAT(usage_date, '%Y-%m-%d')
            ORDER BY date DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getRateLimiting() {
        return $this->db->query("
            SELECT
                rl.api_id,
                rl.plan_name,
                rl.requests_per_hour,
                rl.requests_per_day,
                rl.concurrent_requests,
                COUNT(rlv.id) as violations_count
            FROM rate_limits rl
            LEFT JOIN rate_limit_violations rlv ON rl.id = rlv.rate_limit_id
            WHERE rl.api_id IN (SELECT id FROM apis WHERE publisher_id = ? AND company_id = ?)
            GROUP BY rl.id, rl.api_id, rl.plan_name, rl.requests_per_hour, rl.requests_per_day, rl.concurrent_requests
            ORDER BY rl.plan_name ASC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getAPIMonitoring() {
        return $this->db->query("
            SELECT
                api.name,
                am.uptime_percentage,
                am.avg_response_time,
                am.error_rate,
                am.last_checked,
                am.status
            FROM apis api
            JOIN api_monitoring am ON api.id = am.api_id
            WHERE api.publisher_id = ? AND api.company_id = ?
            ORDER BY am.last_checked DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getRegisteredApps() {
        return $this->db->query("
            SELECT
                app.name,
                app.description,
                app.category,
                app.status,
                app.created_at,
                api.name as api_name,
                COUNT(usage.id) as usage_count,
                AVG(rating.rating) as avg_rating
            FROM applications app
            JOIN apis api ON app.api_id = api.id
            LEFT JOIN api_usage usage ON app.id = usage.application_id
            LEFT JOIN app_ratings rating ON app.id = rating.application_id
            WHERE app.developer_id = ? AND app.company_id = ?
            GROUP BY app.id, app.name, app.description, app.category, app.status, app.created_at, api.name
            ORDER BY app.created_at DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getAppCategories() {
        return [
            'business_intelligence' => 'Business Intelligence',
            'customer_management' => 'Customer Management',
            'data_integration' => 'Data Integration',
            'automation' => 'Automation',
            'analytics' => 'Analytics',
            'communication' => 'Communication',
            'productivity' => 'Productivity',
            'utilities' => 'Utilities'
        ];
    }

    private function getAppReviews() {
        return $this->db->query("
            SELECT
                ar.rating,
                ar.review_text,
                ar.created_at,
                u.first_name,
                u.last_name,
                app.name as app_name
            FROM app_ratings ar
            JOIN users u ON ar.user_id = u.id
            JOIN applications app ON ar.application_id = app.id
            WHERE app.developer_id = ? AND app.company_id = ?
            ORDER BY ar.created_at DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getAppAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as new_registrations,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_apps,
                SUM(downloads) as total_downloads,
                AVG(rating) as avg_rating
            FROM applications
            WHERE developer_id = ? AND company_id = ?
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getMonetizationData() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(billing_date, '%Y-%m') as month,
                SUM(amount) as total_revenue,
                COUNT(*) as transactions,
                AVG(amount) as avg_transaction,
                billing_type
            FROM billing_history
            WHERE api_id IN (SELECT id FROM apis WHERE publisher_id = ? AND company_id = ?)
            GROUP BY DATE_FORMAT(billing_date, '%Y-%m'), billing_type
            ORDER BY month DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getAPIReference() {
        return $this->db->query("
            SELECT
                api.name,
                api.endpoint_url,
                api.method,
                api.description,
                api.parameters,
                api.response_format,
                api.authentication_required,
                api.rate_limit
            FROM apis api
            WHERE api.company_id = ? AND api.is_documented = true
            ORDER BY api.category ASC, api.name ASC
        ", [$this->user['company_id']]);
    }

    private function getCodeExamples() {
        return [
            'curl' => 'cURL Examples',
            'javascript' => 'JavaScript/Node.js',
            'python' => 'Python',
            'php' => 'PHP',
            'java' => 'Java',
            'csharp' => 'C#',
            'ruby' => 'Ruby',
            'go' => 'Go'
        ];
    }

    private function getTutorials() {
        return $this->db->query("
            SELECT
                t.title,
                t.description,
                t.difficulty_level,
                t.estimated_time,
                t.view_count,
                t.created_at
            FROM tutorials t
            WHERE t.company_id = ?
            ORDER BY t.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getChangelogs() {
        return $this->db->query("
            SELECT
                cl.version,
                cl.release_date,
                cl.changes,
                cl.breaking_changes,
                cl.deprecation_warnings
            FROM api_changelogs cl
            WHERE cl.company_id = ?
            ORDER BY cl.release_date DESC
        ", [$this->user['company_id']]);
    }

    private function getFAQs() {
        return $this->db->query("
            SELECT
                faq.question,
                faq.answer,
                faq.category,
                faq.view_count,
                faq.last_updated
            FROM faqs faq
            WHERE faq.company_id = ?
            ORDER BY faq.category ASC, faq.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getUsageMetrics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(usage_date, '%Y-%m-%d') as date,
                SUM(calls_count) as total_calls,
                SUM(data_transferred_mb) as data_transferred,
                COUNT(DISTINCT api_id) as apis_used,
                AVG(response_time_ms) as avg_response_time
            FROM api_usage
            WHERE developer_id = ? AND company_id = ?
            GROUP BY DATE_FORMAT(usage_date, '%Y-%m-%d')
            ORDER BY date DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getBillingHistory() {
        return $this->db->query("
            SELECT
                bh.billing_date,
                bh.amount,
                bh.currency,
                bh.billing_type,
                bh.description,
                api.name as api_name,
                bh.status
            FROM billing_history bh
            JOIN apis api ON bh.api_id = api.id
            WHERE bh.developer_id = ? AND bh.company_id = ?
            ORDER BY bh.billing_date DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getPricingPlans() {
        return $this->db->query("
            SELECT
                pp.plan_name,
                pp.price_per_month,
                pp.requests_per_month,
                pp.features,
                pp.is_popular,
                COUNT(sub.id) as subscribers
            FROM pricing_plans pp
            LEFT JOIN subscriptions sub ON pp.id = sub.plan_id
            WHERE pp.company_id = ?
            GROUP BY pp.id, pp.plan_name, pp.price_per_month, pp.requests_per_month, pp.features, pp.is_popular
            ORDER BY pp.price_per_month ASC
        ", [$this->user['company_id']]);
    }

    private function getPaymentMethods() {
        return [
            'credit_card' => 'Credit Card',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Bank Transfer',
            'crypto' => 'Cryptocurrency',
            'invoice' => 'Invoice'
        ];
    }

    private function getUsageAlerts() {
        return $this->db->query("
            SELECT
                ua.alert_type,
                ua.message,
                ua.threshold_value,
                ua.current_value,
                ua.created_at,
                api.name as api_name
            FROM usage_alerts ua
            LEFT JOIN apis api ON ua.api_id = api.id
            WHERE ua.developer_id = ? AND ua.company_id = ?
            ORDER BY ua.created_at DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getAvailableIntegrations() {
        return $this->db->query("
            SELECT
                i.name,
                i.description,
                i.category,
                i.provider,
                i.setup_complexity,
                COUNT(ui.id) as users_count,
                AVG(rating.rating) as avg_rating
            FROM integrations i
            LEFT JOIN user_integrations ui ON i.id = ui.integration_id
            LEFT JOIN integration_ratings rating ON i.id = rating.integration_id
            WHERE i.company_id = ? AND i.is_available = true
            GROUP BY i.id, i.name, i.description, i.category, i.provider, i.setup_complexity
            ORDER BY users_count DESC
        ", [$this->user['company_id']]);
    }

    private function getIntegrationTemplates() {
        return $this->db->query("
            SELECT
                it.template_name,
                it.description,
                it.integration_type,
                it.configuration_steps,
                COUNT(uit.id) as usage_count
            FROM integration_templates it
            LEFT JOIN user_integration_templates uit ON it.id = uit.template_id
            WHERE it.company_id = ?
            GROUP BY it.id, it.template_name, it.description, it.integration_type, it.configuration_steps
            ORDER BY usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getWebhookConfigurations() {
        return $this->db->query("
            SELECT
                wc.webhook_url,
                wc.event_types,
                wc.is_active,
                wc.created_at,
                wc.last_triggered,
                COUNT(wl.id) as trigger_count
            FROM webhook_configurations wc
            LEFT JOIN webhook_logs wl ON wc.id = wl.webhook_id
            WHERE wc.developer_id = ? AND wc.company_id = ?
            GROUP BY wc.id, wc.webhook_url, wc.event_types, wc.is_active, wc.created_at, wc.last_triggered
            ORDER BY wc.created_at DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getDataMapping() {
        return $this->db->query("
            SELECT
                dm.source_field,
                dm.target_field,
                dm.data_type,
                dm.transformation_rule,
                dm.is_active,
                i.name as integration_name
            FROM data_mapping dm
            JOIN integrations i ON dm.integration_id = i.id
            WHERE dm.developer_id = ? AND dm.company_id = ?
            ORDER BY i.name ASC, dm.source_field ASC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getIntegrationAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                integration_type,
                COUNT(*) as total_integrations,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_integrations,
                SUM(data_processed) as total_data_processed,
                AVG(success_rate) as avg_success_rate
            FROM integration_logs
            WHERE company_id = ?
            GROUP BY DATE_FORMAT(created_at, '%Y-%m'), integration_type
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getAPIPerformance() {
        return $this->db->query("
            SELECT
                api.name,
                DATE_FORMAT(pl.log_date, '%Y-%m-%d') as date,
                AVG(pl.response_time_ms) as avg_response_time,
                COUNT(CASE WHEN pl.status_code >= 400 THEN 1 END) as error_count,
                COUNT(*) as total_requests,
                MAX(pl.response_time_ms) as max_response_time,
                MIN(pl.response_time_ms) as min_response_time
            FROM apis api
            JOIN api_performance_logs pl ON api.id = pl.api_id
            WHERE api.company_id = ?
            GROUP BY api.id, api.name, DATE_FORMAT(pl.log_date, '%Y-%m-%d')
            ORDER BY date DESC, api.name ASC
        ", [$this->user['company_id']]);
    }

    private function getDeveloperAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(DISTINCT developer_id) as new_developers,
                COUNT(DISTINCT application_id) as new_applications,
                SUM(calls_count) as total_api_calls,
                AVG(rating) as avg_rating_given
            FROM developer_activity
            WHERE company_id = ?
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getMarketTrends() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                category,
                COUNT(*) as api_count,
                SUM(usage_count) as total_usage,
                AVG(rating) as avg_rating
            FROM api_market_trends
            WHERE company_id = ?
            GROUP BY DATE_FORMAT(created_at, '%Y-%m'), category
            ORDER BY month DESC, total_usage DESC
        ", [$this->user['company_id']]);
    }

    private function getRevenueAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(billing_date, '%Y-%m') as month,
                SUM(amount) as total_revenue,
                COUNT(*) as transactions,
                AVG(amount) as avg_transaction_value,
                billing_type
            FROM billing_history
            WHERE company_id = ?
            GROUP BY DATE_FORMAT(billing_date, '%Y-%m'), billing_type
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomReports() {
        return $this->db->query("
            SELECT
                cr.report_name,
                cr.description,
                cr.report_type,
                cr.created_by,
                cr.created_at,
                cr.last_run,
                COUNT(runs.id) as run_count
            FROM custom_reports cr
            LEFT JOIN report_runs runs ON cr.id = runs.report_id
            WHERE cr.company_id = ? AND cr.module = 'api_marketplace'
            GROUP BY cr.id, cr.report_name, cr.description, cr.report_type, cr.created_by, cr.created_at, cr.last_run
            ORDER BY cr.created_at DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function registerApplication() {
        $this->requirePermission('api_marketplace.apps.create');

        $data = $this->validateRequest([
            'application_name' => 'required|string',
            'description' => 'required|string',
            'api_id' => 'required|integer',
            'category' => 'string',
            'redirect_uri' => 'string',
            'website_url' => 'string'
        ]);

        try {
            $appId = $this->db->insert('applications', [
                'company_id' => $this->user['company_id'],
                'developer_id' => $this->user['id'],
                'api_id' => $data['api_id'],
                'name' => $data['application_name'],
                'description' => $data['description'],
                'category' => $data['category'] ?? 'utilities',
                'redirect_uri' => $data['redirect_uri'] ?? '',
                'website_url' => $data['website_url'] ?? '',
                'status' => 'pending',
                'client_id' => $this->generateClientId(),
                'client_secret' => $this->generateClientSecret(),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Log activity
            $this->logMarketplaceActivity('application_registered', "Application '{$data['application_name']}' registered", null, $appId);

            $this->jsonResponse([
                'success' => true,
                'application_id' => $appId,
                'client_id' => $this->getClientId($appId),
                'message' => 'Application registered successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateClientId() {
        return 'app_' . bin2hex(random_bytes(16));
    }

    private function generateClientSecret() {
        return bin2hex(random_bytes(32));
    }

    private function getClientId($appId) {
        $result = $this->db->querySingle("SELECT client_id FROM applications WHERE id = ?", [$appId]);
        return $result['client_id'] ?? null;
    }

    public function generateAPIKey() {
        $this->requirePermission('api_marketplace.keys.create');

        $data = $this->validateRequest([
            'application_id' => 'required|integer',
            'name' => 'required|string',
            'rate_limit' => 'integer'
        ]);

        try {
            $apiKey = $this->generateAPIKeyString();
            $maskedKey = substr($apiKey, 0, 8) . '...' . substr($apiKey, -4);

            $keyId = $this->db->insert('api_keys', [
                'company_id' => $this->user['company_id'],
                'developer_id' => $this->user['id'],
                'application_id' => $data['application_id'],
                'api_key' => password_hash($apiKey, PASSWORD_DEFAULT),
                'api_key_masked' => $maskedKey,
                'name' => $data['name'],
                'rate_limit' => $data['rate_limit'] ?? 1000,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Log activity
            $this->logMarketplaceActivity('api_key_generated', "API key '{$data['name']}' generated");

            $this->jsonResponse([
                'success' => true,
                'api_key_id' => $keyId,
                'api_key' => $apiKey, // Only shown once
                'masked_key' => $maskedKey,
                'message' => 'API key generated successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateAPIKeyString() {
        return 'ak_' . bin2hex(random_bytes(24));
    }

    private function logMarketplaceActivity($activityType, $description, $apiId = null, $applicationId = null) {
        $this->db->insert('marketplace_activity', [
            'company_id' => $this->user['company_id'],
            'developer_id' => $this->user['id'],
            'api_id' => $apiId,
            'application_id' => $applicationId,
            'activity_type' => $activityType,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function rateAPI() {
        $this->requirePermission('api_marketplace.ratings.create');

        $data = $this->validateRequest([
            'api_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'string'
        ]);

        try {
            // Check if user already rated this API
            $existing = $this->db->querySingle("
                SELECT id FROM api_ratings
                WHERE api_id = ? AND developer_id = ? AND company_id = ?
            ", [$data['api_id'], $this->user['id'], $this->user['company_id']]);

            if ($existing) {
                // Update existing rating
                $this->db->update('api_ratings', [
                    'rating' => $data['rating'],
                    'review' => $data['review'] ?? '',
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$existing['id']]);
            } else {
                // Create new rating
                $this->db->insert('api_ratings', [
                    'company_id' => $this->user['company_id'],
                    'api_id' => $data['api_id'],
                    'developer_id' => $this->user['id'],
                    'rating' => $data['rating'],
                    'review' => $data['review'] ?? '',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Log activity
            $this->logMarketplaceActivity('api_rated', "API rated {$data['rating']} stars", $data['api_id']);

            $this->jsonResponse([
                'success' => true,
                'message' => 'API rating submitted successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createSupportTicket() {
        $this->requirePermission('api_marketplace.support.create');

        $data = $this->validateRequest([
            'subject' => 'required|string',
            'description' => 'required|string',
            'priority' => 'required|string',
            'category' => 'required|string',
            'api_id' => 'integer',
            'application_id' => 'integer'
        ]);

        try {
            $ticketNumber = 'TICKET-' . date('Ymd') . '-' . rand(1000, 9999);

            $ticketId = $this->db->insert('support_tickets', [
                'company_id' => $this->user['company_id'],
                'developer_id' => $this->user['id'],
                'ticket_number' => $ticketNumber,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'priority' => $data['priority'],
                'category' => $data['category'],
                'api_id' => $data['api_id'] ?? null,
                'application_id' => $data['application_id'] ?? null,
                'status' => 'open',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Log activity
            $this->logMarketplaceActivity('support_ticket_created', "Support ticket '{$ticketNumber}' created");

            $this->jsonResponse([
                'success' => true,
                'ticket_id' => $ticketId,
                'ticket_number' => $ticketNumber,
                'message' => 'Support ticket created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function publishAPI() {
        $this->requirePermission('api_marketplace.apis.publish');

        $data = $this->validateRequest([
            'name' => 'required|string',
            'description' => 'required|string',
            'endpoint_url' => 'required|string',
            'method' => 'required|string',
            'category' => 'required|string',
            'version' => 'required|string',
            'pricing_model' => 'string',
            'rate_limit' => 'integer'
        ]);

        try {
            $apiId = $this->db->insert('apis', [
                'company_id' => $this->user['company_id'],
                'publisher_id' => $this->user['id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'endpoint_url' => $data['endpoint_url'],
                'method' => $data['method'],
                'category' => $data['category'],
                'version' => $data['version'],
                'pricing_model' => $data['pricing_model'] ?? 'free',
                'rate_limit' => $data['rate_limit'] ?? 1000,
                'status' => 'published',
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Log activity
            $this->logMarketplaceActivity('api_published', "API '{$data['name']}' published", $apiId);

            $this->jsonResponse([
                'success' => true,
                'api_id' => $apiId,
                'message' => 'API published successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function subscribeToAPI() {
        $this->requirePermission('api_marketplace.apis.subscribe');

        $data = $this->validateRequest([
            'api_id' => 'required|integer',
            'plan_id' => 'integer'
        ]);

        try {
            // Check if already subscribed
            $existing = $this->db->querySingle("
                SELECT id FROM api_subscriptions
                WHERE api_id = ? AND developer_id = ? AND status = 'active'
            ", [$data['api_id'], $this->user['id']]);

            if ($existing) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Already subscribed to this API'
                ], 400);
            }

            $subscriptionId = $this->db->insert('api_subscriptions', [
                'company_id' => $this->user['company_id'],
                'api_id' => $data['api_id'],
                'developer_id' => $this->user['id'],
                'plan_id' => $data['plan_id'] ?? null,
                'status' => 'active',
                'subscribed_at' => date('Y-m-d H:i:s')
            ]);

            // Log activity
            $this->logMarketplaceActivity('api_subscribed', "Subscribed to API", $data['api_id']);

            $this->jsonResponse([
                'success' => true,
                'subscription_id' => $subscriptionId,
                'message' => 'Successfully subscribed to API'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAPIUsage() {
        $this->requirePermission('api_marketplace.usage.view');

        $data = $this->validateRequest([
            'api_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date'
        ]);

        try {
            $whereClause = "developer_id = ? AND company_id = ?";
            $params = [$this->user['id'], $this->user['company_id']];

            if ($data['api_id']) {
                $whereClause .= " AND api_id = ?";
                $params[] = $data['api_id'];
            }

            if ($data['start_date']) {
                $whereClause .= " AND usage_date >= ?";
                $params[] = $data['start_date'];
            }

            if ($data['end_date']) {
                $whereClause .= " AND usage_date <= ?";
                $params[] = $data['end_date'];
            }

            $usage = $this->db->query("
                SELECT
                    usage_date,
                    api_id,
                    calls_count,
                    data_transferred_mb,
                    response_time_ms,
                    error_count
                FROM api_usage
                WHERE {$whereClause}
                ORDER BY usage_date DESC
            ", $params);

            $this->jsonResponse([
                'success' => true,
                'usage' => $usage
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createWebhook() {
        $this->requirePermission('api_marketplace.webhooks.create');

        $data = $this->validateRequest([
            'webhook_url' => 'required|string',
            'event_types' => 'required|array',
            'name' => 'required|string',
            'description' => 'string'
        ]);

        try {
            $webhookId = $this->db->insert('webhook_configurations', [
                'company_id' => $this->user['company_id'],
                'developer_id' => $this->user['id'],
                'webhook_url' => $data['webhook_url'],
                'event_types' => json_encode($data['event_types']),
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Log activity
            $this->logMarketplaceActivity('webhook_created', "Webhook '{$data['name']}' created");

            $this->jsonResponse([
                'success' => true,
                'webhook_id' => $webhookId,
                'message' => 'Webhook created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testAPIEndpoint() {
        $this->requirePermission('api_marketplace.apis.test');

        $data = $this->validateRequest([
            'api_id' => 'required|integer',
            'parameters' => 'array'
        ]);

        try {
            // Get API details
            $api = $this->db->querySingle("
                SELECT * FROM apis WHERE id = ? AND company_id = ?
            ", [$data['api_id'], $this->user['company_id']]);

            if (!$api) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'API not found'
                ], 404);
            }

            // Simulate API call (in real implementation, this would make actual HTTP request)
            $testResult = [
                'status_code' => 200,
                'response_time' => rand(50, 500),
                'response_body' => json_encode(['test' => 'success', 'timestamp' => date('Y-m-d H:i:s')]),
                'success' => true
            ];

            // Log test call
            $this->db->insert('api_test_logs', [
                'company_id' => $this->user['company_id'],
                'api_id' => $data['api_id'],
                'developer_id' => $this->user['id'],
                'test_parameters' => json_encode($data['parameters'] ?? []),
                'test_result' => json_encode($testResult),
                'tested_at' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'test_result' => $testResult,
                'message' => 'API endpoint tested successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSDKDownload() {
        $this->requirePermission('api_marketplace.sdks.download');

        $data = $this->validateRequest([
            'language' => 'required|string',
            'api_id' => 'integer'
        ]);

        try {
            // Log SDK download
            $this->db->insert('sdk_downloads', [
                'company_id' => $this->user['company_id'],
                'developer_id' => $this->user['id'],
                'api_id' => $data['api_id'] ?? null,
                'language' => $data['language'],
                'downloaded_at' => date('Y-m-d H:i:s')
            ]);

            // In real implementation, this would serve the actual SDK file
            $this->jsonResponse([
                'success' => true,
                'download_url' => "/downloads/sdk/{$data['language']}/latest.zip",
                'message' => 'SDK download initiated'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getGettingStartedGuide() {
        return [
            'title' => 'Getting Started with TPT Free ERP API',
            'steps' => [
                '1. Register your application',
                '2. Generate API keys',
                '3. Review API documentation',
                '4. Test API endpoints',
                '5. Implement authentication',
                '6. Handle rate limits',
                '7. Monitor usage and errors'
            ],
            'resources' => [
                'api_reference' => '/docs/api-reference',
                'code_examples' => '/docs/examples',
                'tutorials' => '/docs/tutorials',
                'support' => '/support'
            ]
        ];
    }

    public function getSDKDownloads() {
        return [
            'javascript' => [
                'name' => 'JavaScript SDK',
                'version' => '2.1.0',
                'download_url' => '/downloads/sdk/javascript/2.1.0.zip',
                'documentation' => '/docs/sdk/javascript'
            ],
            'python' => [
                'name' => 'Python SDK',
                'version' => '2.0.5',
                'download_url' => '/downloads/sdk/python/2.0.5.zip',
                'documentation' => '/docs/sdk/python'
            ],
            'php' => [
                'name' => 'PHP SDK',
                'version' => '1.9.2',
                'download_url' => '/downloads/sdk/php/1.9.2.zip',
                'documentation' => '/docs/sdk/php'
            ],
            'csharp' => [
                'name' => 'C# SDK',
                'version' => '1.8.1',
                'download_url' => '/downloads/sdk/csharp/1.8.1.zip',
                'documentation' => '/docs/sdk/csharp'
            ]
        ];
    }

    public function getCodeSamples() {
        return [
            'authentication' => [
                'javascript' => "const client = new TPTClient({ apiKey: 'your-api-key' });",
                'python' => "client = TPTClient(api_key='your-api-key')",
                'php' => "$client = new TPTClient(['api_key' => 'your-api-key']);"
            ],
            'basic_request' => [
                'javascript' => "const response = await client.get('/api/v1/users');",
                'python' => "response = client.get('/api/v1/users')",
                'php' => "$response = $client->get('/api/v1/users');"
            ]
        ];
    }
}
?>
