<?php
/**
 * TPT Free ERP - Website & Social Module
 * Complete business website management, CMS, social media integration, and customer portal system
 */

class WebsiteSocial extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main website & social dashboard
     */
    public function index() {
        $this->requirePermission('website.view');

        $data = [
            'title' => 'Website & Social Media Management',
            'website_overview' => $this->getWebsiteOverview(),
            'content_overview' => $this->getContentOverview(),
            'social_overview' => $this->getSocialOverview(),
            'customer_portal' => $this->getCustomerPortal(),
            'marketing_campaigns' => $this->getMarketingCampaigns(),
            'seo_analytics' => $this->getSEOAnalytics(),
            'engagement_metrics' => $this->getEngagementMetrics(),
            'content_performance' => $this->getContentPerformance()
        ];

        $this->render('modules/website_social/dashboard', $data);
    }

    /**
     * Content management system (CMS)
     */
    public function cms() {
        $this->requirePermission('website.cms.view');

        $data = [
            'title' => 'Content Management System',
            'pages' => $this->getPages(),
            'posts' => $this->getPosts(),
            'categories' => $this->getCategories(),
            'tags' => $this->getTags(),
            'media_library' => $this->getMediaLibrary(),
            'content_templates' => $this->getContentTemplates(),
            'content_workflow' => $this->getContentWorkflow(),
            'content_analytics' => $this->getContentAnalytics(),
            'seo_tools' => $this->getSEOTools()
        ];

        $this->render('modules/website_social/cms', $data);
    }

    /**
     * Blog and news management
     */
    public function blog() {
        $this->requirePermission('website.blog.view');

        $data = [
            'title' => 'Blog & News Management',
            'blog_posts' => $this->getBlogPosts(),
            'blog_categories' => $this->getBlogCategories(),
            'blog_authors' => $this->getBlogAuthors(),
            'blog_comments' => $this->getBlogComments(),
            'blog_analytics' => $this->getBlogAnalytics(),
            'blog_subscriptions' => $this->getBlogSubscriptions(),
            'blog_templates' => $this->getBlogTemplates(),
            'blog_settings' => $this->getBlogSettings()
        ];

        $this->render('modules/website_social/blog', $data);
    }

    /**
     * Social media integration
     */
    public function socialMedia() {
        $this->requirePermission('website.social.view');

        $data = [
            'title' => 'Social Media Integration',
            'social_accounts' => $this->getSocialAccounts(),
            'social_posts' => $this->getSocialPosts(),
            'social_analytics' => $this->getSocialAnalytics(),
            'social_scheduling' => $this->getSocialScheduling(),
            'social_monitoring' => $this->getSocialMonitoring(),
            'social_campaigns' => $this->getSocialCampaigns(),
            'social_templates' => $this->getSocialTemplates(),
            'social_settings' => $this->getSocialSettings()
        ];

        $this->render('modules/website_social/social_media', $data);
    }

    /**
     * Customer portal
     */
    public function customerPortal() {
        $this->requirePermission('website.portal.view');

        $data = [
            'title' => 'Customer Portal',
            'portal_users' => $this->getPortalUsers(),
            'portal_content' => $this->getPortalContent(),
            'portal_analytics' => $this->getPortalAnalytics(),
            'portal_communications' => $this->getPortalCommunications(),
            'portal_support' => $this->getPortalSupport(),
            'portal_templates' => $this->getPortalTemplates(),
            'portal_settings' => $this->getPortalSettings(),
            'portal_security' => $this->getPortalSecurity()
        ];

        $this->render('modules/website_social/customer_portal', $data);
    }

    /**
     * E-commerce integration
     */
    public function ecommerce() {
        $this->requirePermission('website.ecommerce.view');

        $data = [
            'title' => 'E-commerce Integration',
            'products' => $this->getProducts(),
            'orders' => $this->getOrders(),
            'customers' => $this->getCustomers(),
            'inventory' => $this->getInventory(),
            'payments' => $this->getPayments(),
            'shipping' => $this->getShipping(),
            'ecommerce_analytics' => $this->getEcommerceAnalytics(),
            'ecommerce_settings' => $this->getEcommerceSettings()
        ];

        $this->render('modules/website_social/ecommerce', $data);
    }

    /**
     * Marketing tools
     */
    public function marketing() {
        $this->requirePermission('website.marketing.view');

        $data = [
            'title' => 'Marketing Tools',
            'email_campaigns' => $this->getEmailCampaigns(),
            'lead_generation' => $this->getLeadGeneration(),
            'marketing_automation' => $this->getMarketingAutomation(),
            'marketing_analytics' => $this->getMarketingAnalytics(),
            'marketing_templates' => $this->getMarketingTemplates(),
            'marketing_segments' => $this->getMarketingSegments(),
            'marketing_reports' => $this->getMarketingReports(),
            'marketing_settings' => $this->getMarketingSettings()
        ];

        $this->render('modules/website_social/marketing', $data);
    }

    /**
     * SEO and analytics
     */
    public function seo() {
        $this->requirePermission('website.seo.view');

        $data = [
            'title' => 'SEO & Analytics',
            'seo_overview' => $this->getSEOOverview(),
            'keyword_tracking' => $this->getKeywordTracking(),
            'backlink_analysis' => $this->getBacklinkAnalysis(),
            'competitor_analysis' => $this->getCompetitorAnalysis(),
            'seo_recommendations' => $this->getSEORecommendations(),
            'seo_tools' => $this->getSEOTools(),
            'seo_reports' => $this->getSEOReports(),
            'seo_settings' => $this->getSEOSettings()
        ];

        $this->render('modules/website_social/seo', $data);
    }

    /**
     * Website analytics
     */
    public function analytics() {
        $this->requirePermission('website.analytics.view');

        $data = [
            'title' => 'Website Analytics',
            'traffic_analytics' => $this->getTrafficAnalytics(),
            'user_behavior' => $this->getUserBehavior(),
            'conversion_tracking' => $this->getConversionTracking(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'custom_reports' => $this->getCustomReports(),
            'analytics_dashboards' => $this->getAnalyticsDashboards(),
            'analytics_goals' => $this->getAnalyticsGoals(),
            'analytics_settings' => $this->getAnalyticsSettings()
        ];

        $this->render('modules/website_social/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getWebsiteOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT p.id) as total_pages,
                COUNT(CASE WHEN p.status = 'published' THEN 1 END) as published_pages,
                COUNT(CASE WHEN p.status = 'draft' THEN 1 END) as draft_pages,
                COUNT(DISTINCT bp.id) as total_blog_posts,
                COUNT(DISTINCT sa.id) as connected_social_accounts,
                COUNT(DISTINCT pu.id) as portal_users,
                SUM(p.page_views) as total_page_views,
                AVG(p.avg_time_on_page) as avg_time_on_page,
                COUNT(CASE WHEN p.last_modified >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recently_updated_pages
            FROM pages p
            LEFT JOIN blog_posts bp ON bp.company_id = p.company_id
            LEFT JOIN social_accounts sa ON sa.company_id = p.company_id
            LEFT JOIN portal_users pu ON pu.company_id = p.company_id
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getContentOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT p.id) as total_pages,
                COUNT(DISTINCT bp.id) as total_blog_posts,
                COUNT(DISTINCT c.id) as total_categories,
                COUNT(DISTINCT t.id) as total_tags,
                SUM(p.page_views) as total_views,
                AVG(p.avg_time_on_page) as avg_engagement,
                COUNT(CASE WHEN p.status = 'published' THEN 1 END) as published_content,
                COUNT(CASE WHEN bp.featured = true THEN 1 END) as featured_posts,
                MAX(p.last_modified) as last_content_update
            FROM pages p
            LEFT JOIN blog_posts bp ON bp.company_id = p.company_id
            LEFT JOIN categories c ON c.company_id = p.company_id
            LEFT JOIN tags t ON t.company_id = p.company_id
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSocialOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT sa.id) as total_accounts,
                COUNT(DISTINCT sp.id) as total_posts,
                SUM(sp.likes) as total_likes,
                SUM(sp.shares) as total_shares,
                SUM(sp.comments) as total_comments,
                AVG(sp.engagement_rate) as avg_engagement_rate,
                COUNT(CASE WHEN sp.scheduled_date >= NOW() THEN 1 END) as scheduled_posts,
                COUNT(CASE WHEN sa.status = 'active' THEN 1 END) as active_accounts,
                MAX(sp.post_date) as last_post_date
            FROM social_accounts sa
            LEFT JOIN social_posts sp ON sp.account_id = sa.id
            WHERE sa.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCustomerPortal() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT pu.id) as total_users,
                COUNT(CASE WHEN pu.status = 'active' THEN 1 END) as active_users,
                COUNT(DISTINCT pc.id) as total_content_items,
                SUM(pc.views) as total_content_views,
                AVG(pc.avg_rating) as avg_content_rating,
                COUNT(DISTINCT ps.id) as total_support_tickets,
                COUNT(CASE WHEN ps.status = 'open' THEN 1 END) as open_tickets,
                AVG(TIMESTAMPDIFF(HOUR, ps.created_at, COALESCE(ps.resolved_at, NOW()))) as avg_resolution_time,
                MAX(pu.last_login) as last_user_activity
            FROM portal_users pu
            LEFT JOIN portal_content pc ON pc.company_id = pu.company_id
            LEFT JOIN portal_support ps ON ps.user_id = pu.id
            WHERE pu.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getMarketingCampaigns() {
        return $this->db->query("
            SELECT
                mc.campaign_name,
                mc.campaign_type,
                mc.status,
                mc.start_date,
                mc.end_date,
                mc.budget,
                mc.target_audience,
                mc.expected_reach,
                mc.actual_reach,
                mc.conversion_rate,
                mc.roi_percentage,
                TIMESTAMPDIFF(DAY, NOW(), mc.end_date) as days_remaining,
                mc.performance_score
            FROM marketing_campaigns mc
            WHERE mc.company_id = ?
            ORDER BY mc.start_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSEOAnalytics() {
        return $this->db->querySingle("
            SELECT
                AVG(s.keyword_ranking) as avg_keyword_ranking,
                COUNT(DISTINCT s.keyword) as tracked_keywords,
                COUNT(CASE WHEN s.keyword_ranking <= 10 THEN 1 END) as top_10_keywords,
                COUNT(CASE WHEN s.keyword_ranking <= 3 THEN 1 END) as top_3_keywords,
                SUM(s.search_volume) as total_search_volume,
                AVG(s.competition_level) as avg_competition,
                COUNT(DISTINCT ba.source_url) as total_backlinks,
                AVG(ba.domain_authority) as avg_domain_authority,
                MAX(s.last_updated) as last_seo_update
            FROM seo_keywords s
            LEFT JOIN backlinks ba ON ba.company_id = s.company_id
            WHERE s.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEngagementMetrics() {
        return $this->db->querySingle("
            SELECT
                SUM(p.page_views) as total_page_views,
                AVG(p.avg_time_on_page) as avg_time_on_page,
                AVG(p.bounce_rate) as avg_bounce_rate,
                COUNT(DISTINCT v.visitor_id) as unique_visitors,
                COUNT(CASE WHEN v.return_visitor = true THEN 1 END) as return_visitors,
                AVG(v.session_duration) as avg_session_duration,
                COUNT(CASE WHEN v.converted = true THEN 1 END) as conversions,
                ROUND((COUNT(CASE WHEN v.converted = true THEN 1 END) / NULLIF(COUNT(DISTINCT v.visitor_id), 0)) * 100, 2) as conversion_rate
            FROM pages p
            LEFT JOIN visitor_analytics v ON v.page_id = p.id
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getContentPerformance() {
        return $this->db->query("
            SELECT
                p.page_title,
                p.page_url,
                p.page_views,
                p.avg_time_on_page,
                p.bounce_rate,
                p.conversion_rate,
                p.seo_score,
                p.last_modified,
                CASE
                    WHEN p.page_views > 1000 THEN 'high'
                    WHEN p.page_views > 100 THEN 'medium'
                    ELSE 'low'
                END as performance_level
            FROM pages p
            WHERE p.company_id = ?
            ORDER BY p.page_views DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getPages() {
        return $this->db->query("
            SELECT
                p.*,
                u.first_name as author_first,
                u.last_name as author_last,
                c.category_name,
                p.page_views,
                p.avg_time_on_page,
                p.seo_score,
                p.last_modified,
                TIMESTAMPDIFF(DAY, NOW(), p.publish_date) as days_until_publish
            FROM pages p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.company_id = ?
            ORDER BY p.last_modified DESC
        ", [$this->user['company_id']]);
    }

    private function getPosts() {
        return $this->db->query("
            SELECT
                bp.*,
                u.first_name as author_first,
                u.last_name as author_last,
                bc.category_name,
                bp.views,
                bp.likes,
                bp.comments_count,
                bp.share_count,
                bp.avg_rating,
                bp.publish_date,
                TIMESTAMPDIFF(DAY, NOW(), bp.publish_date) as days_until_publish
            FROM blog_posts bp
            LEFT JOIN users u ON bp.author_id = u.id
            LEFT JOIN blog_categories bc ON bp.category_id = bc.id
            WHERE bp.company_id = ?
            ORDER BY bp.publish_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCategories() {
        return $this->db->query("
            SELECT
                c.*,
                COUNT(p.id) as page_count,
                COUNT(bp.id) as post_count,
                SUM(p.page_views) as total_views,
                AVG(p.seo_score) as avg_seo_score
            FROM categories c
            LEFT JOIN pages p ON c.id = p.category_id
            LEFT JOIN blog_posts bp ON c.id = bp.category_id
            WHERE c.company_id = ?
            GROUP BY c.id
            ORDER BY page_count + post_count DESC
        ", [$this->user['company_id']]);
    }

    private function getTags() {
        return $this->db->query("
            SELECT
                t.*,
                COUNT(pt.page_id) as usage_count,
                MAX(p.last_modified) as last_used
            FROM tags t
            LEFT JOIN page_tags pt ON t.id = pt.tag_id
            LEFT JOIN pages p ON pt.page_id = p.id
            WHERE t.company_id = ?
            GROUP BY t.id
            ORDER BY usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getMediaLibrary() {
        return $this->db->query("
            SELECT
                ml.*,
                ml.file_name,
                ml.file_type,
                ml.file_size,
                ml.upload_date,
                ml.usage_count,
                u.first_name as uploaded_by_first,
                u.last_name as uploaded_by_last,
                CASE
                    WHEN ml.file_type LIKE 'image/%' THEN 'image'
                    WHEN ml.file_type LIKE 'video/%' THEN 'video'
                    WHEN ml.file_type LIKE 'audio/%' THEN 'audio'
                    WHEN ml.file_type LIKE 'application/%' THEN 'document'
                    ELSE 'other'
                END as media_category
            FROM media_library ml
            LEFT JOIN users u ON ml.uploaded_by = u.id
            WHERE ml.company_id = ?
            ORDER BY ml.upload_date DESC
        ", [$this->user['company_id']]);
    }

    private function getContentTemplates() {
        return $this->db->query("
            SELECT * FROM content_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getContentWorkflow() {
        return $this->db->query("
            SELECT
                cw.*,
                p.page_title,
                u.first_name as current_reviewer_first,
                u.last_name as current_reviewer_last,
                cw.current_stage,
                cw.approval_required,
                cw.deadline,
                TIMESTAMPDIFF(DAY, NOW(), cw.deadline) as days_until_deadline
            FROM content_workflow cw
            LEFT JOIN pages p ON cw.content_id = p.id
            LEFT JOIN users u ON cw.current_reviewer_id = u.id
            WHERE cw.company_id = ?
            ORDER BY cw.deadline ASC
        ", [$this->user['company_id']]);
    }

    private function getContentAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(p.id) as total_pages,
                COUNT(CASE WHEN p.status = 'published' THEN 1 END) as published_pages,
                SUM(p.page_views) as total_views,
                AVG(p.avg_time_on_page) as avg_engagement,
                AVG(p.seo_score) as avg_seo_score,
                COUNT(CASE WHEN p.seo_score >= 80 THEN 1 END) as optimized_pages,
                COUNT(CASE WHEN p.last_modified >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recently_updated,
                MAX(p.last_modified) as last_update
            FROM pages p
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSEOTools() {
        return [
            'keyword_research' => 'Keyword Research Tool',
            'site_audit' => 'Site Audit Tool',
            'backlink_checker' => 'Backlink Checker',
            'competitor_analysis' => 'Competitor Analysis',
            'meta_tag_generator' => 'Meta Tag Generator',
            'sitemap_generator' => 'Sitemap Generator',
            'page_speed_test' => 'Page Speed Test',
            'mobile_friendly_test' => 'Mobile Friendly Test'
        ];
    }

    private function getBlogPosts() {
        return $this->db->query("
            SELECT
                bp.*,
                u.first_name as author_first,
                u.last_name as author_last,
                bc.category_name,
                bp.views,
                bp.likes,
                bp.comments_count,
                bp.share_count,
                bp.avg_rating,
                bp.publish_date,
                bp.featured_image,
                bp.excerpt,
                bp.read_time_minutes
            FROM blog_posts bp
            LEFT JOIN users u ON bp.author_id = u.id
            LEFT JOIN blog_categories bc ON bp.category_id = bc.id
            WHERE bp.company_id = ?
            ORDER BY bp.publish_date DESC
        ", [$this->user['company_id']]);
    }

    private function getBlogCategories() {
        return $this->db->query("
            SELECT
                bc.*,
                COUNT(bp.id) as post_count,
                SUM(bp.views) as total_views,
                AVG(bp.avg_rating) as avg_rating,
                MAX(bp.publish_date) as last_post_date
            FROM blog_categories bc
            LEFT JOIN blog_posts bp ON bc.id = bp.category_id
            WHERE bc.company_id = ?
            GROUP BY bc.id
            ORDER BY post_count DESC
        ", [$this->user['company_id']]);
    }

    private function getBlogAuthors() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                u.id as author_id,
                COUNT(bp.id) as total_posts,
                SUM(bp.views) as total_views,
                AVG(bp.avg_rating) as avg_rating,
                SUM(bp.likes) as total_likes,
                SUM(bp.comments_count) as total_comments,
                MAX(bp.publish_date) as last_post_date
            FROM users u
            LEFT JOIN blog_posts bp ON u.id = bp.author_id
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY total_posts DESC
        ", [$this->user['company_id']]);
    }

    private function getBlogComments() {
        return $this->db->query("
            SELECT
                bc.*,
                bp.post_title,
                u.first_name as commenter_first,
                u.last_name as commenter_last,
                bc.comment_text,
                bc.comment_date,
                bc.is_approved,
                bc.likes,
                bc.parent_comment_id
            FROM blog_comments bc
            JOIN blog_posts bp ON bc.post_id = bp.id
            LEFT JOIN users u ON bc.user_id = u.id
            WHERE bc.company_id = ?
            ORDER BY bc.comment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getBlogAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(bp.id) as total_posts,
                SUM(bp.views) as total_views,
                AVG(bp.avg_rating) as avg_rating,
                SUM(bp.likes) as total_likes,
                SUM(bp.comments_count) as total_comments,
                COUNT(CASE WHEN bp.featured = true THEN 1 END) as featured_posts,
                COUNT(CASE WHEN bp.publish_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_posts,
                AVG(bp.read_time_minutes) as avg_read_time
            FROM blog_posts bp
            WHERE bp.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBlogSubscriptions() {
        return $this->db->query("
            SELECT
                bs.*,
                bs.email,
                bs.subscription_date,
                bs.is_active,
                bs.preferences,
                bs.source,
                COUNT(be.id) as emails_sent,
                MAX(be.sent_date) as last_email_date
            FROM blog_subscriptions bs
            LEFT JOIN blog_emails be ON bs.id = be.subscription_id
            WHERE bs.company_id = ?
            GROUP BY bs.id
            ORDER BY bs.subscription_date DESC
        ", [$this->user['company_id']]);
    }

    private function getBlogTemplates() {
        return $this->db->query("
            SELECT * FROM blog_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBlogSettings() {
        return $this->db->querySingle("
            SELECT * FROM blog_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSocialAccounts() {
        return $this->db->query("
            SELECT
                sa.*,
                sa.platform,
                sa.account_name,
                sa.account_handle,
                sa.followers_count,
                sa.following_count,
                sa.posts_count,
                sa.engagement_rate,
                sa.last_post_date,
                sa.account_status,
                sa.api_connected,
                sa.last_sync_date
            FROM social_accounts sa
            WHERE sa.company_id = ?
            ORDER BY sa.followers_count DESC
        ", [$this->user['company_id']]);
    }

    private function getSocialPosts() {
        return $this->db->query("
            SELECT
                sp.*,
                sa.account_name,
                sa.platform,
                sp.post_content,
                sp.post_type,
                sp.scheduled_date,
                sp.posted_date,
                sp.likes,
                sp.shares,
                sp.comments,
                sp.reach,
                sp.impressions,
                sp.engagement_rate,
                sp.post_status
            FROM social_posts sp
            JOIN social_accounts sa ON sp.account_id = sa.id
            WHERE sp.company_id = ?
            ORDER BY sp.scheduled_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSocialAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT sa.id) as total_accounts,
                SUM(sa.followers_count) as total_followers,
                AVG(sa.engagement_rate) as avg_engagement_rate,
                COUNT(sp.id) as total_posts,
                SUM(sp.likes) as total_likes,
                SUM(sp.shares) as total_shares,
                SUM(sp.comments) as total_comments,
                AVG(sp.engagement_rate) as avg_post_engagement,
                COUNT(CASE WHEN sp.scheduled_date >= NOW() THEN 1 END) as scheduled_posts,
                MAX(sp.posted_date) as last_post_date
            FROM social_accounts sa
            LEFT JOIN social_posts sp ON sa.id = sp.account_id
            WHERE sa.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSocialScheduling() {
        return $this->db->query("
            SELECT
                sp.*,
                sa.account_name,
                sa.platform,
                sp.scheduled_date,
                sp.post_content,
                sp.media_attachments,
                sp.approval_status,
                sp.approved_by,
                TIMESTAMPDIFF(HOUR, NOW(), sp.scheduled_date) as hours_until_post
            FROM social_posts sp
            JOIN social_accounts sa ON sp.account_id = sa.id
            WHERE sp.company_id = ? AND sp.post_status = 'scheduled'
            ORDER BY sp.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getSocialMonitoring() {
        return $this->db->query("
            SELECT
                sm.*,
                sa.account_name,
                sa.platform,
                sm.mention_type,
                sm.mention_text,
                sm.sentiment_score,
                sm.engagement_count,
                sm.detected_at,
                TIMESTAMPDIFF(MINUTE, sm.detected_at, NOW()) as minutes_ago
            FROM social_monitoring sm
            JOIN social_accounts sa ON sm.account_id = sa.id
            WHERE sm.company_id = ?
            ORDER BY sm.detected_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSocialCampaigns() {
        return $this->db->query("
            SELECT
                sc.*,
                sc.campaign_name,
                sc.platform,
                sc.objective,
                sc.start_date,
                sc.end_date,
                sc.budget,
                sc.target_reach,
                sc.actual_reach,
                sc.engagement_rate,
                sc.conversion_rate,
                sc.roi_percentage
            FROM social_campaigns sc
            WHERE sc.company_id = ?
            ORDER BY sc.start_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSocialTemplates() {
        return $this->db->query("
            SELECT * FROM social_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getSocialSettings() {
        return $this->db->querySingle("
            SELECT * FROM social_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPortalUsers() {
        return $this->db->query("
            SELECT
                pu.*,
                pu.first_name,
                pu.last_name,
                pu.email,
                pu.registration_date,
                pu.last_login,
                pu.account_status,
                pu.user_type,
                COUNT(pc.id) as content_accessed,
                COUNT(DISTINCT ps.id) as support_tickets,
                AVG(pc.avg_rating) as avg_content_rating
            FROM portal_users pu
            LEFT JOIN portal_content_access pca ON pu.id = pca.user_id
            LEFT JOIN portal_content pc ON pca.content_id = pc.id
            LEFT JOIN portal_support ps ON pu.id = ps.user_id
            WHERE pu.company_id = ?
            GROUP BY pu.id
            ORDER BY pu.registration_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPortalContent() {
        return $this->db->query("
            SELECT
                pc.*,
                pc.content_title,
                pc.content_type,
                pc.access_level,
                pc.views,
                pc.downloads,
                pc.avg_rating,
                pc.publish_date,
                pc.last_updated,
                COUNT(pca.id) as total_accesses,
                COUNT(CASE WHEN pca.completed = true THEN 1 END) as completions
            FROM portal_content pc
            LEFT JOIN portal_content_access pca ON pc.id = pca.content_id
            WHERE pc.company_id = ?
            GROUP BY pc.id
            ORDER BY pc.views DESC
        ", [$this->user['company_id']]);
    }

    private function getPortalAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT pu.id) as total_users,
                COUNT(DISTINCT pc.id) as total_content,
                SUM(pc.views) as total_views,
                SUM(pc.downloads) as total_downloads,
                AVG(pc.avg_rating) as avg_content_rating,
                COUNT(DISTINCT ps.id) as total_support_tickets,
                COUNT(CASE WHEN ps.status = 'resolved' THEN 1 END) as resolved_tickets,
                ROUND((COUNT(CASE WHEN ps.status = 'resolved' THEN 1 END) / NULLIF(COUNT(DISTINCT ps.id), 0)) * 100, 2) as resolution_rate,
                AVG(TIMESTAMPDIFF(HOUR, ps.created_at, ps.resolved_at)) as avg_resolution_time
            FROM portal_users pu
            LEFT JOIN portal_content pc ON pc.company_id = pu.company_id
            LEFT JOIN portal_support ps ON ps.company_id = pu.company_id
            WHERE pu.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPortalCommunications() {
        return $this->db->query("
            SELECT
                pc.*,
                pu.first_name as user_first,
                pu.last_name as user_last,
                pc.communication_type,
                pc.subject,
                pc.message,
                pc.sent_date,
                pc.read_date,
                pc.response_date,
                TIMESTAMPDIFF(MINUTE, pc.sent_date, pc.read_date) as read_time_minutes
            FROM portal_communications pc
            JOIN portal_users pu ON pc.user_id = pu.id
            WHERE pc.company_id = ?
            ORDER BY pc.sent_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPortalSupport() {
        return $this->db->query("
            SELECT
                ps.*,
                pu.first_name as user_first,
                pu.last_name as user_last,
                ps.ticket_number,
                ps.subject,
                ps.priority,
                ps.status,
                ps.created_at,
                ps.last_updated,
                ps.assigned_to,
                ps.resolution_time_hours,
                COUNT(psm.id) as message_count
            FROM portal_support ps
            JOIN portal_users pu ON ps.user_id = pu.id
            LEFT JOIN portal_support_messages psm ON ps.id = psm.ticket_id
            WHERE ps.company_id = ?
            GROUP BY ps.id
            ORDER BY ps.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPortalTemplates() {
        return $this->db->query("
            SELECT * FROM portal_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getPortalSettings() {
        return $this->db->querySingle("
            SELECT * FROM portal_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPortalSecurity() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN pu.two_factor_enabled = true THEN 1 END) as users_with_2fa,
                COUNT(CASE WHEN pu.last_password_change >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 END) as recent_password_changes,
                AVG(pu.failed_login_attempts) as avg_failed_logins,
                COUNT(CASE WHEN pu.account_locked = true THEN 1 END) as locked_accounts,
                MAX(pu.last_security_audit) as last_security_audit,
                COUNT(DISTINCT ps.session_id) as active_sessions
            FROM portal_users pu
            LEFT JOIN portal_sessions ps ON pu.id = ps.user_id AND ps.active = true
            WHERE pu.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getProducts() {
        return $this->db->query("
            SELECT
                p.*,
                p.product_name,
                p.sku,
                p.price,
                p.stock_quantity,
                p.status,
                p.featured,
                p.avg_rating,
                p.total_sales,
                p.last_updated,
                pc.category_name,
                COUNT(pi.id) as image_count
            FROM products p
            LEFT JOIN product_categories pc ON p.category_id = pc.id
            LEFT JOIN product_images pi ON p.id = pi.product_id
            WHERE p.company_id = ?
            GROUP BY p.id
            ORDER BY p.total_sales DESC
        ", [$this->user['company_id']]);
    }

    private function getOrders() {
        return $this->db->query("
            SELECT
                o.*,
                o.order_number,
                o.order_date,
                o.total_amount,
                o.status,
                o.payment_status,
                o.shipping_status,
                c.first_name as customer_first,
                c.last_name as customer_last,
                COUNT(oi.id) as item_count,
                SUM(oi.quantity) as total_quantity
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.company_id = ?
            GROUP BY o.id
            ORDER BY o.order_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomers() {
        return $this->db->query("
            SELECT
                c.*,
                COUNT(o.id) as total_orders,
                SUM(o.total_amount) as total_spent,
                AVG(o.total_amount) as avg_order_value,
                MAX(o.order_date) as last_order_date,
                TIMESTAMPDIFF(DAY, MAX(o.order_date), NOW()) as days_since_last_order,
                c.customer_rating,
                c.loyalty_points
            FROM customers c
            LEFT JOIN orders o ON c.id = o.customer_id
            WHERE c.company_id = ?
            GROUP BY c.id
            ORDER BY total_spent DESC
        ", [$this->user['company_id']]);
    }

    private function getInventory() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                p.stock_quantity,
                p.low_stock_threshold,
                p.reorder_point,
                CASE
                    WHEN p.stock_quantity <= p.reorder_point THEN 'reorder'
                    WHEN p.stock_quantity <= p.low_stock_threshold THEN 'low_stock'
                    ELSE 'in_stock'
                END as stock_status,
                p.last_stock_update,
                p.supplier_name,
                p.lead_time_days
            FROM products p
            WHERE p.company_id = ?
            ORDER BY p.stock_quantity ASC
        ", [$this->user['company_id']]);
    }

    private function getPayments() {
        return $this->db->query("
            SELECT
                p.*,
                o.order_number,
                p.payment_method,
                p.amount,
                p.currency,
                p.status,
                p.transaction_id,
                p.payment_date,
                p.gateway_response,
                c.first_name as customer_first,
                c.last_name as customer_last
            FROM payments p
            JOIN orders o ON p.order_id = o.id
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE p.company_id = ?
            ORDER BY p.payment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getShipping() {
        return $this->db->query("
            SELECT
                s.*,
                o.order_number,
                s.carrier,
                s.tracking_number,
                s.shipping_method,
                s.shipping_cost,
                s.estimated_delivery,
                s.actual_delivery,
                s.status,
                s.shipping_address,
                TIMESTAMPDIFF(DAY, NOW(), s.estimated_delivery) as days_until_delivery
            FROM shipping s
            JOIN orders o ON s.order_id = o.id
            WHERE s.company_id = ?
            ORDER BY s.estimated_delivery ASC
        ", [$this->user['company_id']]);
    }

    private function getEcommerceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(o.id) as total_orders,
                SUM(o.total_amount) as total_revenue,
                AVG(o.total_amount) as avg_order_value,
                COUNT(DISTINCT o.customer_id) as unique_customers,
                COUNT(CASE WHEN o.status = 'completed' THEN 1 END) as completed_orders,
                ROUND((COUNT(CASE WHEN o.status = 'completed' THEN 1 END) / NULLIF(COUNT(o.id), 0)) * 100, 2) as conversion_rate,
                COUNT(p.id) as total_products,
                SUM(p.total_sales) as total_units_sold,
                AVG(p.avg_rating) as avg_product_rating
            FROM orders o
            LEFT JOIN products p ON p.company_id = o.company_id
            WHERE o.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEcommerceSettings() {
        return $this->db->querySingle("
            SELECT * FROM ecommerce_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEmailCampaigns() {
        return $this->db->query("
            SELECT
                ec.*,
                ec.campaign_name,
                ec.subject,
                ec.status,
                ec.send_date,
                ec.recipient_count,
                ec.open_rate,
                ec.click_rate,
                ec.conversion_rate,
                ec.bounce_rate,
                ec.unsubscribe_rate
            FROM email_campaigns ec
            WHERE ec.company_id = ?
            ORDER BY ec.send_date DESC
        ", [$this->user['company_id']]);
    }

    private function getLeadGeneration() {
        return $this->db->query("
            SELECT
                lg.*,
                lg.source,
                lg.lead_type,
                lg.contact_info,
                lg.lead_score,
                lg.status,
                lg.created_date,
                lg.last_contact,
                lg.conversion_probability,
                TIMESTAMPDIFF(DAY, lg.created_date, NOW()) as days_old
            FROM lead_generation lg
            WHERE lg.company_id = ?
            ORDER BY lg.lead_score DESC
        ", [$this->user['company_id']]);
    }

    private function getMarketingAutomation() {
        return $this->db->query("
            SELECT
                ma.*,
                ma.workflow_name,
                ma.trigger_event,
                ma.automation_type,
                ma.status,
                ma.enrolled_contacts,
                ma.completed_actions,
                ma.conversion_rate,
                ma.last_run,
                TIMESTAMPDIFF(DAY, ma.created_date, NOW()) as days_running
            FROM marketing_automation ma
            WHERE ma.company_id = ?
            ORDER BY ma.enrolled_contacts DESC
        ", [$this->user['company_id']]);
    }

    private function getMarketingAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(ec.id) as total_campaigns,
                SUM(ec.recipient_count) as total_recipients,
                AVG(ec.open_rate) as avg_open_rate,
                AVG(ec.click_rate) as avg_click_rate,
                AVG(ec.conversion_rate) as avg_conversion_rate,
                COUNT(lg.id) as total_leads,
                COUNT(CASE WHEN lg.status = 'converted' THEN 1 END) as converted_leads,
                ROUND((COUNT(CASE WHEN lg.status = 'converted' THEN 1 END) / NULLIF(COUNT(lg.id), 0)) * 100, 2) as lead_conversion_rate,
                COUNT(ma.id) as active_automations,
                SUM(ma.enrolled_contacts) as total_automation_enrollments
            FROM email_campaigns ec
            LEFT JOIN lead_generation lg ON lg.company_id = ec.company_id
            LEFT JOIN marketing_automation ma ON ma.company_id = ec.company_id
            WHERE ec.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getMarketingTemplates() {
        return $this->db->query("
            SELECT * FROM marketing_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getMarketingSegments() {
        return $this->db->query("
            SELECT
                ms.*,
                ms.segment_name,
                ms.segment_criteria,
                ms.contact_count,
                ms.created_date,
                ms.last_updated,
                AVG(ec.open_rate) as avg_open_rate,
                AVG(ec.click_rate) as avg_click_rate
            FROM marketing_segments ms
            LEFT JOIN email_campaigns ec ON ms.id = ec.segment_id
            WHERE ms.company_id = ?
            GROUP BY ms.id
            ORDER BY ms.contact_count DESC
        ", [$this->user['company_id']]);
    }

    private function getMarketingReports() {
        return $this->db->query("
            SELECT
                mr.*,
                mr.report_type,
                mr.report_period,
                mr.generated_date,
                mr.total_campaigns,
                mr.total_leads,
                mr.conversion_rate,
                mr.roi_percentage
            FROM marketing_reports mr
            WHERE mr.company_id = ?
            ORDER BY mr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getMarketingSettings() {
        return $this->db->querySingle("
            SELECT * FROM marketing_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSEOOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT sk.keyword) as tracked_keywords,
                AVG(sk.keyword_ranking) as avg_ranking,
                COUNT(CASE WHEN sk.keyword_ranking <= 10 THEN 1 END) as top_10_keywords,
                COUNT(CASE WHEN sk.keyword_ranking <= 3 THEN 1 END) as top_3_keywords,
                SUM(sk.search_volume) as total_search_volume,
                AVG(sk.competition_level) as avg_competition,
                COUNT(DISTINCT ba.source_url) as total_backlinks,
                AVG(ba.domain_authority) as avg_domain_authority,
                MAX(sk.last_updated) as last_update
            FROM seo_keywords sk
            LEFT JOIN backlinks ba ON ba.company_id = sk.company_id
            WHERE sk.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getKeywordTracking() {
        return $this->db->query("
            SELECT
                sk.*,
                sk.keyword,
                sk.keyword_ranking,
                sk.previous_ranking,
                sk.search_volume,
                sk.competition_level,
                sk.cpc,
                sk.trend_direction,
                sk.last_updated,
                sk.ranking_change
            FROM seo_keywords sk
            WHERE sk.company_id = ?
            ORDER BY sk.keyword_ranking ASC
        ", [$this->user['company_id']]);
    }

    private function getBacklinkAnalysis() {
        return $this->db->query("
            SELECT
                ba.*,
                ba.source_url,
                ba.target_url,
                ba.anchor_text,
                ba.domain_authority,
                ba.page_authority,
                ba.link_type,
                ba.follow_status,
                ba.first_seen,
                ba.last_seen,
                TIMESTAMPDIFF(DAY, ba.first_seen, NOW()) as days_old
            FROM backlinks ba
            WHERE ba.company_id = ?
            ORDER BY ba.domain_authority DESC
        ", [$this->user['company_id']]);
    }

    private function getCompetitorAnalysis() {
        return $this->db->query("
            SELECT
                ca.*,
                ca.competitor_domain,
                ca.competitor_ranking,
                ca.shared_keywords,
                ca.backlink_overlap,
                ca.content_overlap,
                ca.traffic_estimate,
                ca.last_updated,
                TIMESTAMPDIFF(DAY, ca.last_updated, NOW()) as days_since_update
            FROM competitor_analysis ca
            WHERE ca.company_id = ?
            ORDER BY ca.competitor_ranking ASC
        ", [$this->user['company_id']]);
    }

    private function getSEORecommendations() {
        return $this->db->query("
            SELECT
                sr.*,
                sr.recommendation_type,
                sr.priority,
                sr.description,
                sr.implementation_status,
                sr.estimated_impact,
                sr.implementation_cost,
                sr.created_at,
                TIMESTAMPDIFF(DAY, sr.created_at, NOW()) as days_old
            FROM seo_recommendations sr
            WHERE sr.company_id = ?
            ORDER BY sr.priority DESC, sr.estimated_impact DESC
        ", [$this->user['company_id']]);
    }

    private function getSEOReports() {
        return $this->db->query("
            SELECT
                sr.*,
                sr.report_type,
                sr.report_period,
                sr.generated_date,
                sr.keyword_rankings,
                sr.backlink_count,
                sr.traffic_metrics,
                sr.recommendations_count
            FROM seo_reports sr
            WHERE sr.company_id = ?
            ORDER BY sr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSEOSettings() {
        return $this->db->querySingle("
            SELECT * FROM seo_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTrafficAnalytics() {
        return $this->db->query("
            SELECT
                ta.*,
                ta.page_url,
                ta.page_views,
                ta.unique_visitors,
                ta.avg_session_duration,
                ta.bounce_rate,
                ta.traffic_source,
                ta.device_type,
                ta.geographic_location,
                ta.date_recorded
            FROM traffic_analytics ta
            WHERE ta.company_id = ?
            ORDER BY ta.date_recorded DESC
        ", [$this->user['company_id']]);
    }

    private function getUserBehavior() {
        return $this->db->query("
            SELECT
                ub.*,
                ub.user_id,
                ub.page_sequence,
                ub.time_spent,
                ub.actions_taken,
                ub.conversion_events,
                ub.session_start,
                ub.session_end,
                TIMESTAMPDIFF(MINUTE, ub.session_start, ub.session_end) as session_duration_minutes
            FROM user_behavior ub
            WHERE ub.company_id = ?
            ORDER BY ub.session_start DESC
        ", [$this->user['company_id']]);
    }

    private function getConversionTracking() {
        return $this->db->query("
            SELECT
                ct.*,
                ct.conversion_type,
                ct.conversion_value,
                ct.source_url,
                ct.conversion_date,
                ct.attribution_model,
                ct.customer_journey,
                ct.conversion_funnel
            FROM conversion_tracking ct
            WHERE ct.company_id = ?
            ORDER BY ct.conversion_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceMetrics() {
        return $this->db->querySingle("
            SELECT
                AVG(page_load_time) as avg_page_load_time,
                AVG(time_to_first_byte) as avg_time_to_first_byte,
                COUNT(CASE WHEN page_load_time > 3000 THEN 1 END) as slow_pages,
                COUNT(CASE WHEN mobile_friendly = false THEN 1 END) as non_mobile_friendly,
                AVG(seo_score) as avg_seo_score,
                COUNT(CASE WHEN https_enabled = true THEN 1 END) as https_pages,
                MAX(last_crawl_date) as last_crawl_date,
                COUNT(CASE WHEN crawl_errors > 0 THEN 1 END) as pages_with_errors
            FROM performance_metrics
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCustomReports() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.report_name,
                cr.report_type,
                cr.date_range,
                cr.filters_applied,
                cr.generated_date,
                cr.generated_by,
                cr.download_count
            FROM custom_reports cr
            WHERE cr.company_id = ?
            ORDER BY cr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAnalyticsDashboards() {
        return $this->db->query("
            SELECT
                ad.*,
                ad.dashboard_name,
                ad.dashboard_type,
                ad.widgets_config,
                ad.refresh_interval,
                ad.last_updated,
                ad.created_by,
                COUNT(adw.widget_id) as widget_count
            FROM analytics_dashboards ad
            LEFT JOIN analytics_dashboard_widgets adw ON ad.id = adw.dashboard_id
            WHERE ad.company_id = ?
            GROUP BY ad.id
            ORDER BY ad.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getAnalyticsGoals() {
        return $this->db->query("
            SELECT
                ag.*,
                ag.goal_name,
                ag.goal_type,
                ag.target_value,
                ag.current_value,
                ag.completion_percentage,
                ag.start_date,
                ag.end_date,
                ag.status,
                TIMESTAMPDIFF(DAY, NOW(), ag.end_date) as days_remaining
            FROM analytics_goals ag
            WHERE ag.company_id = ?
            ORDER BY ag.completion_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getAnalyticsSettings() {
        return $this->db->querySingle("
            SELECT * FROM analytics_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function createPage() {
        $this->requirePermission('website.cms.create');

        $data = $this->validateRequest([
            'page_title' => 'required|string',
            'page_content' => 'required|string',
            'page_url' => 'required|string',
            'category_id' => 'integer',
            'status' => 'required|in:draft,published',
            'seo_title' => 'string',
            'seo_description' => 'string',
            'publish_date' => 'string'
        ]);

        try {
            $pageId = $this->db->insert('pages', [
                'company_id' => $this->user['company_id'],
                'author_id' => $this->user['id'],
                'page_title' => $data['page_title'],
                'page_content' => $data['page_content'],
                'page_url' => $data['page_url'],
                'category_id' => $data['category_id'] ?? null,
                'status' => $data['status'],
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'publish_date' => $data['publish_date'] ?? date('Y-m-d H:i:s'),
                'last_modified' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'page_id' => $pageId,
                'message' => 'Page created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePage() {
        $this->requirePermission('website.cms.edit');

        $data = $this->validateRequest([
            'page_id' => 'required|integer',
            'page_title' => 'string',
            'page_content' => 'string',
            'page_url' => 'string',
            'category_id' => 'integer',
            'status' => 'in:draft,published',
            'seo_title' => 'string',
            'seo_description' => 'string'
        ]);

        try {
            $this->db->update('pages', [
                'page_title' => $data['page_title'] ?? null,
                'page_content' => $data['page_content'] ?? null,
                'page_url' => $data['page_url'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'status' => $data['status'] ?? null,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'last_modified' => date('Y-m-d H:i:s')
            ], 'id = ? AND company_id = ?', [
                $data['page_id'],
                $this->user['company_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Page updated successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createBlogPost() {
        $this->requirePermission('website.blog.create');

        $data = $this->validateRequest([
            'post_title' => 'required|string',
            'post_content' => 'required|string',
            'category_id' => 'required|integer',
            'excerpt' => 'string',
            'featured_image' => 'string',
            'tags' => 'array',
            'status' => 'required|in:draft,published',
            'publish_date' => 'string',
            'seo_title' => 'string',
            'seo_description' => 'string'
        ]);

        try {
            $postId = $this->db->insert('blog_posts', [
                'company_id' => $this->user['company_id'],
                'author_id' => $this->user['id'],
                'post_title' => $data['post_title'],
                'post_content' => $data['post_content'],
                'category_id' => $data['category_id'],
                'excerpt' => $data['excerpt'] ?? null,
                'featured_image' => $data['featured_image'] ?? null,
                'status' => $data['status'],
                'publish_date' => $data['publish_date'] ?? date('Y-m-d H:i:s'),
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'last_updated' => date('Y-m-d H:i:s')
            ]);

            // Add tags
            if (!empty($data['tags'])) {
                foreach ($data['tags'] as $tagName) {
                    $tagId = $this->db->querySingle("
                        SELECT id FROM tags
                        WHERE tag_name = ? AND company_id = ?
                    ", [$tagName, $this->user['company_id']]);

                    if (!$tagId) {
                        $tagId = $this->db->insert('tags', [
                            'company_id' => $this->user['company_id'],
                            'tag_name' => $tagName
                        ]);
                    }

                    $this->db->insert('blog_post_tags', [
                        'post_id' => $postId,
                        'tag_id' => $tagId
                    ]);
                }
            }

            $this->jsonResponse([
                'success' => true,
                'post_id' => $postId,
                'message' => 'Blog post created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function scheduleSocialPost() {
        $this->requirePermission('website.social.schedule');

        $data = $this->validateRequest([
            'account_id' => 'required|integer',
            'post_content' => 'required|string',
            'scheduled_date' => 'required|string',
            'media_attachments' => 'array',
            'post_type' => 'string'
        ]);

        try {
            $postId = $this->db->insert('social_posts', [
                'company_id' => $this->user['company_id'],
                'account_id' => $data['account_id'],
                'post_content' => $data['post_content'],
                'post_type' => $data['post_type'] ?? 'text',
                'media_attachments' => json_encode($data['media_attachments'] ?? []),
                'scheduled_date' => $data['scheduled_date'],
                'post_status' => 'scheduled',
                'created_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'post_id' => $postId,
                'message' => 'Social post scheduled successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createProduct() {
        $this->requirePermission('website.ecommerce.create');

        $data = $this->validateRequest([
            'product_name' => 'required|string',
            'sku' => 'required|string',
            'price' => 'required|numeric',
            'description' => 'string',
            'category_id' => 'integer',
            'stock_quantity' => 'integer',
            'status' => 'required|in:active,inactive',
            'featured' => 'boolean'
        ]);

        try {
            $productId = $this->db->insert('products', [
                'company_id' => $this->user['company_id'],
                'product_name' => $data['product_name'],
                'sku' => $data['sku'],
                'price' => $data['price'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'status' => $data['status'],
                'featured' => $data['featured'] ?? false,
                'last_updated' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'product_id' => $productId,
                'message' => 'Product created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendEmailCampaign() {
        $this->requirePermission('website.marketing.send');

        $data = $this->validateRequest([
            'campaign_name' => 'required|string',
            'subject' => 'required|string',
            'content' => 'required|string',
            'recipient_list' => 'required|array',
            'template_id' => 'integer',
            'send_date' => 'string'
        ]);

        try {
            $campaignId = $this->db->insert('email_campaigns', [
                'company_id' => $this->user['company_id'],
                'campaign_name' => $data['campaign_name'],
                'subject' => $data['subject'],
                'content' => $data['content'],
                'recipient_count' => count($data['recipient_list']),
                'template_id' => $data['template_id'] ?? null,
                'status' => 'scheduled',
                'send_date' => $data['send_date'] ?? date('Y-m-d H:i:s'),
                'created_by' => $this->user['id']
            ]);

            // Queue recipients
            foreach ($data['recipient_list'] as $email) {
                $this->db->insert('campaign_recipients', [
                    'campaign_id' => $campaignId,
                    'email' => $email,
                    'status' => 'pending'
                ]);
            }

            $this->jsonResponse([
                'success' => true,
                'campaign_id' => $campaignId,
                'message' => 'Email campaign scheduled successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trackSEOKeyword() {
        $this->requirePermission('website.seo.track');

        $data = $this->validateRequest([
            'keyword' => 'required|string',
            'search_volume' => 'integer',
            'competition_level' => 'numeric',
            'cpc' => 'numeric',
            'target_url' => 'string'
        ]);

        try {
            $keywordId = $this->db->insert('seo_keywords', [
                'company_id' => $this->user['company_id'],
                'keyword' => $data['keyword'],
                'search_volume' => $data['search_volume'] ?? 0,
                'competition_level' => $data['competition_level'] ?? 0,
                'cpc' => $data['cpc'] ?? 0,
                'target_url' => $data['target_url'] ?? null,
                'last_updated' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'keyword_id' => $keywordId,
                'message' => 'SEO keyword tracking started'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
