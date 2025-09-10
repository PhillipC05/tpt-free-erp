<?php
/**
 * TPT Free ERP - User Experience Enhancements Module
 * Complete onboarding, notifications, dashboards, shortcuts, and help system
 */

class UserExperience extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main UX dashboard
     */
    public function index() {
        $this->requirePermission('ux.view');

        $data = [
            'title' => 'User Experience Dashboard',
            'onboarding_overview' => $this->getOnboardingOverview(),
            'notification_overview' => $this->getNotificationOverview(),
            'dashboard_overview' => $this->getDashboardOverview(),
            'shortcut_overview' => $this->getShortcutOverview(),
            'help_overview' => $this->getHelpOverview(),
            'user_engagement' => $this->getUserEngagement(),
            'ux_analytics' => $this->getUXAnalytics()
        ];

        $this->render('modules/ux/dashboard', $data);
    }

    /**
     * Onboarding tutorials
     */
    public function onboarding() {
        $this->requirePermission('ux.onboarding.view');

        $data = [
            'title' => 'Onboarding Tutorials',
            'tutorials' => $this->getTutorials(),
            'tutorial_progress' => $this->getTutorialProgress(),
            'user_progress' => $this->getUserProgress(),
            'tutorial_templates' => $this->getTutorialTemplates(),
            'tutorial_analytics' => $this->getTutorialAnalytics(),
            'tutorial_settings' => $this->getTutorialSettings()
        ];

        $this->render('modules/ux/onboarding', $data);
    }

    /**
     * Real-time notifications
     */
    public function notifications() {
        $this->requirePermission('ux.notifications.view');

        $data = [
            'title' => 'Notification Management',
            'notification_rules' => $this->getNotificationRules(),
            'notification_history' => $this->getNotificationHistory(),
            'notification_channels' => $this->getNotificationChannels(),
            'notification_templates' => $this->getNotificationTemplates(),
            'notification_analytics' => $this->getNotificationAnalytics(),
            'notification_settings' => $this->getNotificationSettings()
        ];

        $this->render('modules/ux/notifications', $data);
    }

    /**
     * Customizable dashboards
     */
    public function dashboards() {
        $this->requirePermission('ux.dashboards.view');

        $data = [
            'title' => 'Dashboard Customization',
            'user_dashboards' => $this->getUserDashboards(),
            'dashboard_widgets' => $this->getDashboardWidgets(),
            'dashboard_templates' => $this->getDashboardTemplates(),
            'dashboard_layouts' => $this->getDashboardLayouts(),
            'dashboard_analytics' => $this->getDashboardAnalytics(),
            'dashboard_settings' => $this->getDashboardSettings()
        ];

        $this->render('modules/ux/dashboards', $data);
    }

    /**
     * Keyboard shortcuts
     */
    public function shortcuts() {
        $this->requirePermission('ux.shortcuts.view');

        $data = [
            'title' => 'Keyboard Shortcuts',
            'shortcut_categories' => $this->getShortcutCategories(),
            'user_shortcuts' => $this->getUserShortcuts(),
            'shortcut_presets' => $this->getShortcutPresets(),
            'shortcut_conflicts' => $this->getShortcutConflicts(),
            'shortcut_analytics' => $this->getShortcutAnalytics(),
            'shortcut_settings' => $this->getShortcutSettings()
        ];

        $this->render('modules/ux/shortcuts', $data);
    }

    /**
     * Help and documentation
     */
    public function help() {
        $this->requirePermission('ux.help.view');

        $data = [
            'title' => 'Help & Documentation',
            'help_articles' => $this->getHelpArticles(),
            'help_categories' => $this->getHelpCategories(),
            'help_search' => $this->getHelpSearch(),
            'help_feedback' => $this->getHelpFeedback(),
            'help_analytics' => $this->getHelpAnalytics(),
            'help_settings' => $this->getHelpSettings()
        ];

        $this->render('modules/ux/help', $data);
    }

    /**
     * User feedback system
     */
    public function feedback() {
        $this->requirePermission('ux.feedback.view');

        $data = [
            'title' => 'User Feedback',
            'feedback_items' => $this->getFeedbackItems(),
            'feedback_categories' => $this->getFeedbackCategories(),
            'feedback_analytics' => $this->getFeedbackAnalytics(),
            'feedback_responses' => $this->getFeedbackResponses(),
            'feedback_settings' => $this->getFeedbackSettings(),
            'feedback_reports' => $this->getFeedbackReports()
        ];

        $this->render('modules/ux/feedback', $data);
    }

    /**
     * User engagement tracking
     */
    public function engagement() {
        $this->requirePermission('ux.engagement.view');

        $data = [
            'title' => 'User Engagement',
            'engagement_metrics' => $this->getEngagementMetrics(),
            'user_journeys' => $this->getUserJourneys(),
            'feature_usage' => $this->getFeatureUsage(),
            'engagement_insights' => $this->getEngagementInsights(),
            'engagement_reports' => $this->getEngagementReports(),
            'engagement_settings' => $this->getEngagementSettings()
        ];

        $this->render('modules/ux/engagement', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getOnboardingOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT t.id) as total_tutorials,
                COUNT(DISTINCT tp.user_id) as users_started,
                COUNT(CASE WHEN tp.completed = true THEN 1 END) as users_completed,
                ROUND((COUNT(CASE WHEN tp.completed = true THEN 1 END) / NULLIF(COUNT(DISTINCT tp.user_id), 0)) * 100, 2) as completion_rate,
                AVG(tp.time_spent_minutes) as avg_time_spent,
                MAX(tp.last_activity) as last_activity,
                COUNT(CASE WHEN tp.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_this_week
            FROM tutorials t
            LEFT JOIN tutorial_progress tp ON t.id = tp.tutorial_id
            WHERE t.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getNotificationOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_notifications,
                COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_notifications,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_notifications,
                COUNT(CASE WHEN status = 'read' THEN 1 END) as read_notifications,
                ROUND((COUNT(CASE WHEN status = 'read' THEN 1 END) / NULLIF(COUNT(CASE WHEN status = 'delivered' THEN 1 END), 0)) * 100, 2) as read_rate,
                COUNT(DISTINCT user_id) as unique_recipients,
                MAX(sent_at) as last_notification_sent
            FROM notifications
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getDashboardOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT ud.id) as total_dashboards,
                COUNT(DISTINCT ud.user_id) as users_with_custom_dashboards,
                COUNT(udw.widget_id) as total_widgets,
                AVG(ud.last_modified) as avg_last_modified,
                COUNT(CASE WHEN ud.is_default = false THEN 1 END) as custom_dashboards,
                MAX(ud.last_modified) as last_dashboard_update
            FROM user_dashboards ud
            LEFT JOIN user_dashboard_widgets udw ON ud.id = udw.dashboard_id
            WHERE ud.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getShortcutOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT us.id) as total_shortcuts,
                COUNT(DISTINCT us.user_id) as users_with_shortcuts,
                COUNT(CASE WHEN us.is_custom = true THEN 1 END) as custom_shortcuts,
                COUNT(CASE WHEN us.usage_count > 0 THEN 1 END) as used_shortcuts,
                AVG(us.usage_count) as avg_usage_count,
                MAX(us.last_used) as last_shortcut_used
            FROM user_shortcuts us
            WHERE us.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getHelpOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT ha.id) as total_articles,
                COUNT(DISTINCT hc.category_name) as total_categories,
                SUM(ha.views) as total_views,
                SUM(ha.helpful_votes) as total_helpful_votes,
                ROUND((SUM(ha.helpful_votes) / NULLIF(SUM(ha.views), 0)) * 100, 2) as helpful_rate,
                COUNT(CASE WHEN ha.last_updated >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recently_updated,
                MAX(ha.last_updated) as last_update
            FROM help_articles ha
            LEFT JOIN help_categories hc ON ha.category_id = hc.id
            WHERE ha.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getUserEngagement() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT ue.user_id) as engaged_users,
                AVG(ue.session_duration) as avg_session_duration,
                AVG(ue.page_views) as avg_page_views,
                AVG(ue.feature_usage) as avg_feature_usage,
                COUNT(CASE WHEN ue.engagement_score >= 80 THEN 1 END) as highly_engaged_users,
                COUNT(CASE WHEN ue.engagement_score < 30 THEN 1 END) as low_engaged_users,
                MAX(ue.last_activity) as last_activity
            FROM user_engagement ue
            WHERE ue.company_id = ? AND ue.activity_date >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d', strtotime('-30 days'))
        ]);
    }

    private function getUXAnalytics() {
        return $this->db->querySingle("
            SELECT
                AVG(tutorial_completion_rate) as tutorial_completion_rate,
                AVG(notification_read_rate) as notification_read_rate,
                AVG(dashboard_usage_score) as dashboard_usage_score,
                AVG(shortcut_adoption_rate) as shortcut_adoption_rate,
                AVG(help_article_satisfaction) as help_satisfaction,
                AVG(overall_ux_score) as overall_ux_score,
                MAX(last_ux_assessment) as last_assessment
            FROM ux_analytics
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTutorials() {
        return $this->db->query("
            SELECT
                t.*,
                COUNT(tp.user_id) as users_started,
                COUNT(CASE WHEN tp.completed = true THEN 1 END) as users_completed,
                ROUND((COUNT(CASE WHEN tp.completed = true THEN 1 END) / NULLIF(COUNT(tp.user_id), 0)) * 100, 2) as completion_rate,
                AVG(tp.time_spent_minutes) as avg_time_spent,
                t.last_updated
            FROM tutorials t
            LEFT JOIN tutorial_progress tp ON t.id = tp.tutorial_id
            WHERE t.company_id = ?
            GROUP BY t.id
            ORDER BY t.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getTutorialProgress() {
        return $this->db->query("
            SELECT
                tp.*,
                t.tutorial_name,
                tp.current_step,
                tp.total_steps,
                ROUND((tp.current_step / tp.total_steps) * 100, 2) as progress_percentage,
                tp.time_spent_minutes,
                tp.last_activity,
                TIMESTAMPDIFF(DAY, tp.last_activity, NOW()) as days_since_last_activity
            FROM tutorial_progress tp
            JOIN tutorials t ON tp.tutorial_id = t.id
            WHERE tp.company_id = ? AND tp.user_id = ?
            ORDER BY tp.last_activity DESC
        ", [$this->user['company_id'], $this->user['id']]);
    }

    private function getUserProgress() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT tp.tutorial_id) as tutorials_started,
                COUNT(CASE WHEN tp.completed = true THEN 1 END) as tutorials_completed,
                ROUND((COUNT(CASE WHEN tp.completed = true THEN 1 END) / NULLIF(COUNT(DISTINCT tp.tutorial_id), 0)) * 100, 2) as overall_completion_rate,
                SUM(tp.time_spent_minutes) as total_time_spent,
                AVG(tp.time_spent_minutes) as avg_time_per_tutorial,
                MAX(tp.last_activity) as last_tutorial_activity
            FROM tutorial_progress tp
            WHERE tp.company_id = ? AND tp.user_id = ?
        ", [$this->user['company_id'], $this->user['id']]);
    }

    private function getTutorialTemplates() {
        return [
            'getting_started' => [
                'name' => 'Getting Started',
                'description' => 'Basic introduction to the system',
                'steps' => ['login', 'dashboard', 'navigation', 'basic_features'],
                'estimated_time' => 15
            ],
            'inventory_management' => [
                'name' => 'Inventory Management',
                'description' => 'Learn to manage products and stock',
                'steps' => ['products', 'stock_levels', 'suppliers', 'reports'],
                'estimated_time' => 25
            ],
            'sales_process' => [
                'name' => 'Sales Process',
                'description' => 'Complete guide to managing sales',
                'steps' => ['customers', 'quotes', 'orders', 'invoices'],
                'estimated_time' => 30
            ],
            'project_management' => [
                'name' => 'Project Management',
                'description' => 'Learn to create and manage projects',
                'steps' => ['projects', 'tasks', 'resources', 'reporting'],
                'estimated_time' => 35
            ]
        ];
    }

    private function getTutorialAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_tutorial_sessions,
                AVG(time_spent_minutes) as avg_session_duration,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(CASE WHEN completed = true THEN 1 END) as completed_sessions,
                ROUND((COUNT(CASE WHEN completed = true THEN 1 END) / NULLIF(COUNT(*), 0)) * 100, 2) as completion_rate,
                AVG(drop_off_rate) as avg_drop_off_rate,
                MAX(created_at) as last_session
            FROM tutorial_progress
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getTutorialSettings() {
        return $this->db->querySingle("
            SELECT * FROM tutorial_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getNotificationRules() {
        return $this->db->query("
            SELECT
                nr.*,
                nr.rule_name,
                nr.trigger_event,
                nr.notification_type,
                nr.is_active,
                COUNT(n.id) as notifications_sent,
                MAX(n.sent_at) as last_sent,
                nr.last_updated
            FROM notification_rules nr
            LEFT JOIN notifications n ON nr.id = n.rule_id
            WHERE nr.company_id = ?
            GROUP BY nr.id
            ORDER BY nr.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getNotificationHistory() {
        return $this->db->query("
            SELECT
                n.*,
                nr.rule_name,
                n.notification_type,
                n.status,
                n.sent_at,
                n.delivered_at,
                n.read_at,
                TIMESTAMPDIFF(MINUTE, n.sent_at, n.read_at) as time_to_read
            FROM notifications n
            LEFT JOIN notification_rules nr ON n.rule_id = nr.id
            WHERE n.company_id = ?
            ORDER BY n.sent_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getNotificationChannels() {
        return [
            'email' => [
                'name' => 'Email',
                'description' => 'Send notifications via email',
                'reliability' => 'High',
                'cost' => 'Low',
                'features' => ['HTML content', 'attachments', 'tracking']
            ],
            'sms' => [
                'name' => 'SMS',
                'description' => 'Send notifications via text message',
                'reliability' => 'High',
                'cost' => 'Medium',
                'features' => ['Instant delivery', 'high open rates']
            ],
            'push' => [
                'name' => 'Push Notifications',
                'description' => 'Browser and mobile push notifications',
                'reliability' => 'Medium',
                'cost' => 'Free',
                'features' => ['Real-time', 'no app required']
            ],
            'in_app' => [
                'name' => 'In-App Notifications',
                'description' => 'Notifications within the application',
                'reliability' => 'High',
                'cost' => 'Free',
                'features' => ['Immediate', 'contextual', 'persistent']
            ]
        ];
    }

    private function getNotificationTemplates() {
        return $this->db->query("
            SELECT
                nt.*,
                nt.template_name,
                nt.notification_type,
                nt.subject_template,
                nt.body_template,
                COUNT(n.id) as usage_count,
                MAX(n.sent_at) as last_used,
                nt.last_updated
            FROM notification_templates nt
            LEFT JOIN notifications n ON nt.id = n.template_id
            WHERE nt.company_id = ?
            GROUP BY nt.id
            ORDER BY nt.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getNotificationAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_notifications,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered,
                COUNT(CASE WHEN status = 'read' THEN 1 END) as read,
                ROUND((COUNT(CASE WHEN status = 'read' THEN 1 END) / NULLIF(COUNT(CASE WHEN status = 'delivered' THEN 1 END), 0)) * 100, 2) as read_rate,
                AVG(TIMESTAMPDIFF(MINUTE, sent_at, read_at)) as avg_time_to_read,
                COUNT(DISTINCT user_id) as unique_recipients,
                MAX(sent_at) as last_notification
            FROM notifications
            WHERE company_id = ? AND sent_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getNotificationSettings() {
        return $this->db->querySingle("
            SELECT * FROM notification_settings
            WHERE company_id = ? AND user_id = ?
        ", [$this->user['company_id'], $this->user['id']]);
    }

    private function getUserDashboards() {
        return $this->db->query("
            SELECT
                ud.*,
                ud.dashboard_name,
                ud.is_default,
                COUNT(udw.widget_id) as widget_count,
                ud.last_modified,
                ud.created_at
            FROM user_dashboards ud
            LEFT JOIN user_dashboard_widgets udw ON ud.id = udw.dashboard_id
            WHERE ud.company_id = ? AND ud.user_id = ?
            GROUP BY ud.id
            ORDER BY ud.is_default DESC, ud.last_modified DESC
        ", [$this->user['company_id'], $this->user['id']]);
    }

    private function getDashboardWidgets() {
        return $this->db->query("
            SELECT
                dw.*,
                dw.widget_name,
                dw.widget_type,
                dw.category,
                COUNT(udw.id) as usage_count,
                AVG(udw.position_x) as avg_position_x,
                AVG(udw.position_y) as avg_position_y,
                dw.last_updated
            FROM dashboard_widgets dw
            LEFT JOIN user_dashboard_widgets udw ON dw.id = udw.widget_id
            WHERE dw.company_id = ?
            GROUP BY dw.id
            ORDER BY dw.category ASC, dw.widget_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDashboardTemplates() {
        return [
            'executive' => [
                'name' => 'Executive Dashboard',
                'description' => 'High-level overview for executives',
                'widgets' => ['revenue_chart', 'user_growth', 'system_health', 'key_metrics'],
                'layout' => '2x2_grid'
            ],
            'operations' => [
                'name' => 'Operations Dashboard',
                'description' => 'Operational metrics and KPIs',
                'widgets' => ['inventory_levels', 'order_status', 'production_metrics', 'quality_indicators'],
                'layout' => '3x2_grid'
            ],
            'sales' => [
                'name' => 'Sales Dashboard',
                'description' => 'Sales performance and pipeline',
                'widgets' => ['sales_pipeline', 'revenue_trends', 'customer_acquisition', 'deal_conversion'],
                'layout' => '2x3_grid'
            ],
            'finance' => [
                'name' => 'Finance Dashboard',
                'description' => 'Financial metrics and reports',
                'widgets' => ['profit_loss', 'cash_flow', 'budget_vs_actual', 'financial_ratios'],
                'layout' => '2x2_grid'
            ]
        ];
    }

    private function getDashboardLayouts() {
        return [
            '1_column' => [
                'name' => 'Single Column',
                'description' => 'Widgets stacked vertically',
                'columns' => 1,
                'responsive' => true
            ],
            '2_column' => [
                'name' => 'Two Columns',
                'description' => 'Widgets in two side-by-side columns',
                'columns' => 2,
                'responsive' => true
            ],
            '3_column' => [
                'name' => 'Three Columns',
                'description' => 'Widgets in three columns',
                'columns' => 3,
                'responsive' => true
            ],
            'grid' => [
                'name' => 'Grid Layout',
                'description' => 'Flexible grid-based layout',
                'columns' => 'auto',
                'responsive' => true
            ]
        ];
    }

    private function getDashboardAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT ud.id) as total_dashboards,
                COUNT(DISTINCT ud.user_id) as users_with_dashboards,
                COUNT(udw.id) as total_widgets_used,
                AVG(udw.size_width) as avg_widget_width,
                AVG(udw.size_height) as avg_widget_height,
                COUNT(CASE WHEN ud.is_default = false THEN 1 END) as custom_dashboards,
                MAX(ud.last_modified) as last_dashboard_change
            FROM user_dashboards ud
            LEFT JOIN user_dashboard_widgets udw ON ud.id = udw.dashboard_id
            WHERE ud.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getDashboardSettings() {
        return $this->db->querySingle("
            SELECT * FROM dashboard_settings
            WHERE company_id = ? AND user_id = ?
        ", [$this->user['company_id'], $this->user['id']]);
    }

    private function getShortcutCategories() {
        return [
            'navigation' => [
                'name' => 'Navigation',
                'description' => 'Shortcuts for moving around the application',
                'shortcuts' => ['dashboard', 'modules', 'settings', 'search']
            ],
            'actions' => [
                'name' => 'Actions',
                'description' => 'Shortcuts for common actions',
                'shortcuts' => ['new_record', 'save', 'delete', 'export']
            ],
            'views' => [
                'name' => 'Views',
                'description' => 'Shortcuts for changing views and filters',
                'shortcuts' => ['list_view', 'grid_view', 'filter', 'sort']
            ],
            'tools' => [
                'name' => 'Tools',
                'description' => 'Shortcuts for tools and utilities',
                'shortcuts' => ['help', 'notifications', 'shortcuts', 'feedback']
            ]
        ];
    }

    private function getUserShortcuts() {
        return $this->db->query("
            SELECT
                us.*,
                us.shortcut_key,
                us.shortcut_action,
                us.description,
                us.usage_count,
                us.last_used,
                us.is_custom,
                us.created_at
            FROM user_shortcuts us
            WHERE us.company_id = ? AND us.user_id = ?
            ORDER BY us.usage_count DESC, us.last_used DESC
        ", [$this->user['company_id'], $this->user['id']]);
    }

    private function getShortcutPresets() {
        return $this->db->query("
            SELECT
                sp.*,
                sp.preset_name,
                sp.description,
                COUNT(us.id) as users_using,
                sp.created_at,
                sp.last_updated
            FROM shortcut_presets sp
            LEFT JOIN user_shortcuts us ON sp.id = us.preset_id
            WHERE sp.company_id = ?
            GROUP BY sp.id
            ORDER BY sp.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getShortcutConflicts() {
        return $this->db->query("
            SELECT
                sc.*,
                sc.shortcut_key,
                sc.conflicting_actions,
                sc.severity,
                sc.detected_at,
                sc.resolved_at,
                sc.resolution
            FROM shortcut_conflicts sc
            WHERE sc.company_id = ?
            ORDER BY sc.severity DESC, sc.detected_at DESC
        ", [$this->user['company_id']]);
    }

    private function getShortcutAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT us.id) as total_shortcuts,
                COUNT(DISTINCT us.user_id) as users_with_shortcuts,
                SUM(us.usage_count) as total_usage,
                AVG(us.usage_count) as avg_usage_per_shortcut,
                COUNT(CASE WHEN us.is_custom = true THEN 1 END) as custom_shortcuts,
                MAX(us.last_used) as last_shortcut_used,
                COUNT(DISTINCT us.shortcut_key) as unique_keys_used
            FROM user_shortcuts us
            WHERE us.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getShortcutSettings() {
        return $this->db->querySingle("
            SELECT * FROM shortcut_settings
            WHERE company_id = ? AND user_id = ?
        ", [$this->user['company_id'], $this->user['id']]);
    }

    private function getHelpArticles() {
        return $this->db->query("
            SELECT
                ha.*,
                ha.article_title,
                ha.article_content,
                ha.views,
                ha.helpful_votes,
                ha.not_helpful_votes,
                ROUND((ha.helpful_votes / NULLIF(ha.helpful_votes + ha.not_helpful_votes, 0)) * 100, 2) as helpful_percentage,
                hc.category_name,
                ha.last_updated,
                ha.created_at
            FROM help_articles ha
            LEFT JOIN help_categories hc ON ha.category_id = hc.id
            WHERE ha.company_id = ?
            ORDER BY ha.views DESC, ha.helpful_votes DESC
        ", [$this->user['company_id']]);
    }

    private function getHelpCategories() {
        return $this->db->query("
            SELECT
                hc.*,
                hc.category_name,
                COUNT(ha.id) as article_count,
                SUM(ha.views) as total_views,
                AVG(ha.helpful_votes) as avg_helpful_votes,
                MAX(ha.last_updated) as last_updated
            FROM help_categories hc
            LEFT JOIN help_articles ha ON hc.id = ha.category_id
            WHERE hc.company_id = ?
            GROUP BY hc.id
            ORDER BY hc.category_name ASC
        ", [$this->user['company_id']]);
    }

    private function getHelpSearch() {
        return $this->db->query("
            SELECT
                hs.*,
                hs.search_query,
                hs.results_count,
                hs.clicked_result,
                hs.search_time,
                hs.user_ip,
                hs.created_at
            FROM help_search hs
            WHERE hs.company_id = ?
            ORDER BY hs.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getHelpFeedback() {
        return $this->db->query("
            SELECT
                hf.*,
                ha.article_title,
                hf.feedback_type,
                hf.feedback_text,
                hf.rating,
                hf.created_at,
                u.first_name,
                u.last_name
            FROM help_feedback hf
            LEFT JOIN help_articles ha ON hf.article_id = ha.id
            LEFT JOIN users u ON hf.user_id = u.id
            WHERE hf.company_id = ?
            ORDER BY hf.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getHelpAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT ha.id) as total_articles,
                SUM(ha.views) as total_views,
                SUM(ha.helpful_votes) as total_helpful_votes,
                ROUND((SUM(ha.helpful_votes) / NULLIF(SUM(ha.helpful_votes + ha.not_helpful_votes), 0)) * 100, 2) as overall_helpful_rate,
                COUNT(DISTINCT hs.id) as total_searches,
                AVG(hs.results_count) as avg_results_per_search,
                COUNT(CASE WHEN hs.clicked_result IS NOT NULL THEN 1 END) as successful_searches,
                ROUND((COUNT(CASE WHEN hs.clicked_result IS NOT NULL THEN 1 END) / NULLIF(COUNT(DISTINCT hs.id), 0)) * 100, 2) as search_success_rate
            FROM help_articles ha
            LEFT JOIN help_search hs ON hs.company_id = ha.company_id
            WHERE ha.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getHelpSettings() {
        return $this->db->querySingle("
            SELECT * FROM help_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getFeedbackItems() {
        return $this->db->query("
            SELECT
                fi.*,
                fi.feedback_title,
                fi.feedback_description,
                fi.feedback_type,
                fi.priority,
                fi.status,
                fi.votes,
                fi.created_at,
                u.first_name,
                u.last_name,
                COUNT(fr.id) as response_count
            FROM feedback_items fi
            LEFT JOIN users u ON fi.created_by = u.id
            LEFT JOIN feedback_responses fr ON fi.id = fr.feedback_id
            WHERE fi.company_id = ?
            GROUP BY fi.id
            ORDER BY fi.votes DESC, fi.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getFeedbackCategories() {
        return [
            'bug' => [
                'name' => 'Bug Report',
                'description' => 'Report bugs and technical issues',
                'priority' => 'High',
                'response_time' => 'we will get back to you as soon as we can'
            ],
            'feature' => [
                'name' => 'Feature Request',
                'description' => 'Suggest new features or improvements',
                'priority' => 'Medium',
                'response_time' => '1 week'
            ],
            'improvement' => [
                'name' => 'Improvement',
                'description' => 'Suggest UI/UX improvements',
                'priority' => 'Medium',
                'response_time' => '3 days'
            ],
            'question' => [
                'name' => 'Question',
                'description' => 'Ask questions about the system',
                'priority' => 'Low',
                'response_time' => '48 hours'
            ]
        ];
    }

    private function getFeedbackAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_feedback,
                COUNT(CASE WHEN status = 'open' THEN 1 END) as open_feedback,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_feedback,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_feedback,
                SUM(votes) as total_votes,
                AVG(votes) as avg_votes_per_item,
                COUNT(DISTINCT created_by) as unique_contributors,
                MAX(created_at) as last_feedback
            FROM feedback_items
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getFeedbackResponses() {
        return $this->db->query("
            SELECT
                fr.*,
                fi.feedback_title,
                fr.response_text,
                fr.response_type,
                fr.created_at,
                u.first_name,
                u.last_name
            FROM feedback_responses fr
            JOIN feedback_items fi ON fr.feedback_id = fi.id
            LEFT JOIN users u ON fr.responded_by = u.id
            WHERE fr.company_id = ?
            ORDER BY fr.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getFeedbackSettings() {
        return $this->db->querySingle("
            SELECT * FROM feedback_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getFeedbackReports() {
        return $this->db->query("
            SELECT
                fr.*,
                fr.report_name,
                fr.report_period,
                fr.total_feedback,
                fr.completed_feedback,
                fr.avg_response_time,
                fr.generated_date
            FROM feedback_reports fr
            WHERE fr.company_id = ?
            ORDER BY fr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getEngagementMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT ue.user_id) as active_users,
                AVG(ue.session_duration) as avg_session_duration,
                AVG(ue.page_views) as avg_page_views,
                AVG(ue.feature_usage) as avg_feature_usage,
                COUNT(CASE WHEN ue.engagement_score >= 80 THEN 1 END) as highly_engaged,
                COUNT(CASE WHEN ue.engagement_score < 30 THEN 1 END) as low_engaged,
                MAX(ue.last_activity) as last_activity
            FROM user_engagement ue
            WHERE ue.company_id = ? AND ue.activity_date >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d', strtotime('-30 days'))
        ]);
    }

    private function getUserJourneys() {
        return $this->db->query("
            SELECT
                uj.*,
                uj.journey_name,
                uj.start_page,
                uj.end_page,
                uj.steps_count,
                uj.completion_rate,
                uj.avg_duration,
                uj.created_at
            FROM user_journeys uj
            WHERE uj.company_id = ?
            ORDER BY uj.completion_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getFeatureUsage() {
        return $this->db->query("
            SELECT
                fu.*,
                fu.feature_name,
                fu.usage_count,
                fu.unique_users,
                fu.avg_session_duration,
                fu.last_used,
                ROUND((fu.usage_count / NULLIF(fu.unique_users, 0)), 2) as avg_usage_per_user
            FROM feature_usage fu
            WHERE fu.company_id = ?
            ORDER BY fu.usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getEngagementInsights() {
        return $this->db->query("
            SELECT
                ei.*,
                ei.insight_type,
                ei.insight_title,
                ei.insight_description,
                ei.severity,
                ei.affected_users,
                ei.recommended_action,
                ei.created_at
            FROM engagement_insights ei
            WHERE ei.company_id = ?
            ORDER BY ei.severity DESC, ei.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getEngagementReports() {
        return $this->db->query("
            SELECT
                er.*,
                er.report_name,
                er.report_period,
                er.active_users,
                er.engagement_score,
                er.retention_rate,
                er.generated_date
            FROM engagement_reports er
            WHERE er.company_id = ?
            ORDER BY er.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getEngagementSettings() {
        return $this->db->querySingle("
            SELECT * FROM engagement_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function getTutorialStep() {
        $tutorialId = $_GET['tutorial_id'] ?? null;
        $stepNumber = (int)($_GET['step'] ?? 1);

        if (!$tutorialId) {
            $this->jsonResponse(['error' => 'Tutorial ID required'], 400);
        }

        $step = $this->db->querySingle("
            SELECT
                ts.*,
                t.tutorial_name,
                ts.step_title,
                ts.step_content,
                ts.step_type,
                ts.step_order,
                ts.is_required
            FROM tutorial_steps ts
            JOIN tutorials t ON ts.tutorial_id = t.id
            WHERE ts.tutorial_id = ? AND ts.step_order = ?
                AND t.company_id = ?
        ", [$tutorialId, $stepNumber, $this->user['company_id']]);

        if (!$step) {
            $this->jsonResponse(['error' => 'Tutorial step not found'], 404);
        }

        $this->jsonResponse([
            'success' => true,
            'step' => $step
        ]);
    }

    public function updateTutorialProgress() {
        $data = $this->validateRequest([
            'tutorial_id' => 'required|string',
            'current_step' => 'required|integer',
            'completed' => 'boolean',
            'time_spent' => 'integer'
        ]);

        try {
            // Update or insert progress
            $existing = $this->db->querySingle("
                SELECT id FROM tutorial_progress
                WHERE tutorial_id = ? AND user_id = ? AND company_id = ?
            ", [$data['tutorial_id'], $this->user['id'], $this->user['company_id']]);

            if ($existing) {
                $this->db->update('tutorial_progress', [
                    'current_step' => $data['current_step'],
                    'completed' => $data['completed'] ?? false,
                    'time_spent_minutes' => $data['time_spent'] ?? 0,
                    'last_activity' => date('Y-m-d H:i:s')
                ], 'id = ?', [$existing['id']]);
            } else {
                $totalSteps = $this->db->querySingle("
                    SELECT COUNT(*) as count FROM tutorial_steps
                    WHERE tutorial_id = ?
                ", [$data['tutorial_id']]);

                $this->db->insert('tutorial_progress', [
                    'company_id' => $this->user['company_id'],
                    'tutorial_id' => $data['tutorial_id'],
                    'user_id' => $this->user['id'],
                    'current_step' => $data['current_step'],
                    'total_steps' => $totalSteps['count'] ?? 1,
                    'completed' => $data['completed'] ?? false,
                    'time_spent_minutes' => $data['time_spent'] ?? 0,
                    'last_activity' => date('Y-m-d H:i:s')
                ]);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Tutorial progress updated'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendNotification() {
        $this->requirePermission('ux.notifications.send');

        $data = $this->validateRequest([
            'user_ids' => 'required|array',
            'notification_type' => 'required|string',
            'title' => 'required|string',
            'message' => 'required|string',
            'channels' => 'array'
        ]);

        try {
            $notificationId = $this->db->insert('notifications', [
                'company_id' => $this->user['company_id'],
                'notification_type' => $data['notification_type'],
                'title' => $data['title'],
                'message' => $data['message'],
                'channels' => json_encode($data['channels'] ?? ['in_app']),
                'status' => 'queued',
                'created_by' => $this->user['id']
            ]);

            // Queue notification for sending
            foreach ($data['user_ids'] as $userId) {
                $this->db->insert('notification_recipients', [
                    'notification_id' => $notificationId,
                    'user_id' => $userId,
                    'status' => 'pending'
                ]);
            }

            $this->jsonResponse([
                'success' => true,
                'notification_id' => $notificationId,
                'message' => 'Notification queued for sending'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function saveDashboard() {
        $data = $this->validateRequest([
            'dashboard_name' => 'required|string',
            'widgets' => 'required|array',
            'layout' => 'required|string',
            'is_default' => 'boolean'
        ]);

        try {
            // Create or update dashboard
            $existing = $this->db->querySingle("
                SELECT id FROM user_dashboards
                WHERE user_id = ? AND company_id = ? AND dashboard_name = ?
            ", [$this->user['id'], $this->user['company_id'], $data['dashboard_name']]);

            if ($existing) {
                $dashboardId = $existing['id'];
                $this->db->update('user_dashboards', [
                    'layout' => $data['layout'],
                    'is_default' => $data['is_default'] ?? false,
                    'last_modified' => date('Y-m-d H:i:s')
                ], 'id = ?', [$dashboardId]);
            } else {
                $dashboardId = $this->db->insert('user_dashboards', [
                    'company_id' => $this->user['company_id'],
                    'user_id' => $this->user['id'],
                    'dashboard_name' => $data['dashboard_name'],
                    'layout' => $data['layout'],
                    'is_default' => $data['is_default'] ?? false,
                    'last_modified' => date('Y-m-d H:i:s')
                ]);
            }

            // Update widgets
            $this->db->delete('user_dashboard_widgets', 'dashboard_id = ?', [$dashboardId]);

            foreach ($data['widgets'] as $widget) {
                $this->db->insert('user_dashboard_widgets', [
                    'dashboard_id' => $dashboardId,
                    'widget_id' => $widget['widget_id'],
                    'position_x' => $widget['position_x'] ?? 0,
                    'position_y' => $widget['position_y'] ?? 0,
                    'size_width' => $widget['size_width'] ?? 1,
                    'size_height' => $widget['size_height'] ?? 1
                ]);
            }

            $this->jsonResponse([
                'success' => true,
                'dashboard_id' => $dashboardId,
                'message' => 'Dashboard saved successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function submitFeedback() {
        $data = $this->validateRequest([
            'feedback_type' => 'required|string',
            'title' => 'required|string',
            'description' => 'required|string',
            'category' => 'string',
            'priority' => 'string'
        ]);

        try {
            $feedbackId = $this->db->insert('feedback_items', [
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'feedback_type' => $data['feedback_type'],
                'feedback_title' => $data['title'],
                'feedback_description' => $data['description'],
                'category' => $data['category'] ?? 'general',
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'open',
                'votes' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'feedback_id' => $feedbackId,
                'message' => 'Feedback submitted successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function voteFeedback() {
        $data = $this->validateRequest([
            'feedback_id' => 'required|integer',
            'vote_type' => 'required|in:up,down'
        ]);

        try {
            // Check if user already voted
            $existingVote = $this->db->querySingle("
                SELECT id FROM feedback_votes
                WHERE feedback_id = ? AND user_id = ?
            ", [$data['feedback_id'], $this->user['id']]);

            if ($existingVote) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'You have already voted on this feedback'
                ], 400);
            }

            // Add vote
            $this->db->insert('feedback_votes', [
                'feedback_id' => $data['feedback_id'],
                'user_id' => $this->user['id'],
                'vote_type' => $data['vote_type'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Update feedback vote count
            if ($data['vote_type'] === 'up') {
                $this->db->query("
                    UPDATE feedback_items
                    SET votes = votes + 1
                    WHERE id = ?
                ", [$data['feedback_id']]);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Vote recorded successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateShortcutUsage() {
        $data = $this->validateRequest([
            'shortcut_id' => 'required|integer'
        ]);

        try {
            $this->db->query("
                UPDATE user_shortcuts
                SET usage_count = usage_count + 1, last_used = ?
                WHERE id = ? AND user_id = ?
            ", [
                date('Y-m-d H:i:s'),
                $data['shortcut_id'],
                $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Shortcut usage updated'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trackUserAction() {
        $data = $this->validateRequest([
            'action_type' => 'required|string',
            'page_url' => 'required|string',
            'element_id' => 'string',
            'element_type' => 'string',
            'metadata' => 'array'
        ]);

        try {
            $this->db->insert('user_actions', [
                'company_id' => $this->user['company_id'],
                'user_id' => $this->user['id'],
                'action_type' => $data['action_type'],
                'page_url' => $data['page_url'],
                'element_id' => $data['element_id'] ?? null,
                'element_type' => $data['element_type'] ?? null,
                'metadata' => json_encode($data['metadata'] ?? []),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'User action tracked'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
