<?php
/**
 * TPT Free ERP - Business Website & Social Module
 * Complete CMS, blog system, social media integration, and customer portal
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
            'title' => 'Business Website & Social',
            'website_stats' => $this->getWebsiteStats(),
            'social_accounts' => $this->getSocialAccounts(),
            'recent_posts' => $this->getRecentPosts(),
            'customer_portal' => $this->getCustomerPortalStats(),
            'marketing_campaigns' => $this->getMarketingCampaigns(),
            'seo_performance' => $this->getSEOPerformance()
        ];

        $this->render('modules/website/dashboard', $data);
    }

    /**
     * Content Management System (CMS)
     */
    public function cms() {
        $this->requirePermission('website.cms.view');

        $data = [
            'title' => 'Content Management System',
            'pages' => $this->getPages(),
            'page_templates' => $this->getPageTemplates(),
            'content_blocks' => $this->getContentBlocks(),
            'media_library' => $this->getMediaLibrary(),
            'page_builder' => $this->getPageBuilder(),
            'seo_settings' => $this->getSEOSettings()
        ];

        $this->render('modules/website/cms', $data);
    }

    /**
     * Blog and news system
     */
    public function blog() {
        $this->requirePermission('website.blog.view');

        $filters = [
            'category' => $_GET['category'] ?? null,
            'status' => $_GET['status'] ?? null,
            'author' => $_GET['author'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $posts = $this->getBlogPosts($filters);

        $data = [
            'title' => 'Blog & News Management',
            'posts' => $posts,
            'filters' => $filters,
            'categories' => $this->getBlogCategories(),
            'tags' => $this->getBlogTags(),
            'authors' => $this->getBlogAuthors(),
            'post_templates' => $this->getPostTemplates(),
            'blog_stats' => $this->getBlogStats($filters)
        ];

        $this->render('modules/website/blog', $data);
    }

    /**
     * Social media integration
     */
    public function socialMedia() {
        $this->requirePermission('website.social.view');

        $data = [
            'title' => 'Social Media Integration',
            'accounts' => $this->getSocialAccounts(),
            'posts' => $this->getSocialPosts(),
            'schedules' => $this->getSocialSchedules(),
            'analytics' => $this->getSocialAnalytics(),
            'platforms' => $this->getSupportedPlatforms(),
            'templates' => $this->getSocialTemplates(),
            'engagement' => $this->getSocialEngagement()
        ];

        $this->render('modules/website/social_media', $data);
    }

    /**
     * Customer portal
     */
    public function customerPortal() {
        $this->requirePermission('website.customer_portal.view');

        $data = [
            'title' => 'Customer Portal',
            'customers' => $this->getPortalCustomers(),
            'tickets' => $this->getSupportTickets(),
            'knowledge_base' => $this->getCustomerKnowledgeBase(),
            'orders' => $this->getCustomerOrders(),
            'downloads' => $this->getCustomerDownloads(),
            'portal_settings' => $this->getPortalSettings(),
            'customer_stats' => $this->getCustomerStats()
        ];

        $this->render('modules/website/customer_portal', $data);
    }

    /**
     * E-commerce integration
     */
    public function eCommerce() {
        $this->requirePermission('website.ecommerce.view');

        $data = [
            'title' => 'E-commerce Integration',
            'products' => $this->getEcommerceProducts(),
            'orders' => $this->getEcommerceOrders(),
            'customers' => $this->getEcommerceCustomers(),
            'payments' => $this->getPaymentMethods(),
            'shipping' => $this->getShippingMethods(),
            'inventory' => $this->getEcommerceInventory(),
            'analytics' => $this->getEcommerceAnalytics(),
            'integrations' => $this->getEcommerceIntegrations()
        ];

        $this->render('modules/website/ecommerce', $data);
    }

    /**
     * Marketing tools
     */
    public function marketing() {
        $this->requirePermission('website.marketing.view');

        $data = [
            'title' => 'Marketing Tools',
            'campaigns' => $this->getMarketingCampaigns(),
            'email_marketing' => $this->getEmailMarketing(),
            'lead_generation' => $this->getLeadGeneration(),
            'analytics' => $this->getMarketingAnalytics(),
            'automation' => $this->getMarketingAutomation(),
            'segmentation' => $this->getCustomerSegmentation(),
            'templates' => $this->getMarketingTemplates(),
            'performance' => $this->getMarketingPerformance()
        ];

        $this->render('modules/website/marketing', $data);
    }

    /**
     * SEO and analytics
     */
    public function seo() {
        $this->requirePermission('website.seo.view');

        $data = [
            'title' => 'SEO & Analytics',
            'seo_settings' => $this->getSEOSettings(),
            'keywords' => $this->getSEOKeywords(),
            'backlinks' => $this->getBacklinks(),
            'analytics' => $this->getWebsiteAnalytics(),
            'performance' => $this->getSEOPerformance(),
            'competitors' => $this->getSEOCompetitors(),
            'reports' => $this->getSEOReports(),
            'recommendations' => $this->getSEORecommendations()
        ];

        $this->render('modules/website/seo', $data);
    }

    /**
     * Website forms and leads
     */
    public function forms() {
        $this->requirePermission('website.forms.view');

        $data = [
            'title' => 'Website Forms & Leads',
            'forms' => $this->getWebsiteForms(),
            'submissions' => $this->getFormSubmissions(),
            'leads' => $this->getLeads(),
            'templates' => $this->getFormTemplates(),
            'integrations' => $this->getFormIntegrations(),
            'analytics' => $this->getFormAnalytics(),
            'automation' => $this->getFormAutomation()
        ];

        $this->render('modules/website/forms', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getWebsiteStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT p.id) as total_pages,
                COUNT(DISTINCT bp.id) as total_blog_posts,
                COUNT(DISTINCT sa.id) as connected_social_accounts,
                COUNT(DISTINCT pc.id) as portal_customers,
                SUM(wa.page_views) as total_page_views,
                AVG(wa.bounce_rate) as avg_bounce_rate
            FROM pages p
            LEFT JOIN blog_posts bp ON bp.company_id = p.company_id
            LEFT JOIN social_accounts sa ON sa.company_id = p.company_id
            LEFT JOIN portal_customers pc ON pc.company_id = p.company_id
            LEFT JOIN website_analytics wa ON wa.company_id = p.company_id
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSocialAccounts() {
        return $this->db->query("
            SELECT
                sa.*,
                COUNT(sp.id) as posts_count,
                MAX(sp.created_at) as last_post_date,
                AVG(sp.engagement_rate) as avg_engagement,
                sa.followers_count,
                sa.connection_status
            FROM social_accounts sa
            LEFT JOIN social_posts sp ON sa.id = sp.account_id
            WHERE sa.company_id = ?
            GROUP BY sa.id
            ORDER BY sa.platform, sa.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getRecentPosts() {
        return $this->db->query("
            SELECT
                bp.*,
                u.first_name as author_first,
                u.last_name as author_last,
                bc.name as category_name,
                COUNT(bpc.id) as comment_count,
                COUNT(bpv.id) as view_count,
                MAX(bpv.viewed_at) as last_viewed
            FROM blog_posts bp
            LEFT JOIN users u ON bp.author_id = u.id
            LEFT JOIN blog_categories bc ON bp.category_id = bc.id
            LEFT JOIN blog_post_comments bpc ON bp.id = bpc.post_id
            LEFT JOIN blog_post_views bpv ON bp.id = bpv.post_id
            WHERE bp.company_id = ? AND bp.status = 'published'
            GROUP BY bp.id, u.first_name, u.last_name, bc.name
            ORDER BY bp.published_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getCustomerPortalStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(pc.id) as total_customers,
                COUNT(CASE WHEN pc.last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_customers,
                COUNT(st.id) as open_tickets,
                AVG(pc.satisfaction_score) as avg_satisfaction,
                COUNT(co.id) as total_orders,
                SUM(co.total_amount) as total_order_value
            FROM portal_customers pc
            LEFT JOIN support_tickets st ON pc.id = st.customer_id AND st.status = 'open'
            LEFT JOIN customer_orders co ON pc.id = co.customer_id
            WHERE pc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getMarketingCampaigns() {
        return $this->db->query("
            SELECT
                mc.*,
                COUNT(mce.id) as email_count,
                COUNT(mcl.id) as lead_count,
                mc.budget_allocated,
                mc.budget_spent,
                ROUND((mc.budget_spent / NULLIF(mc.budget_allocated, 0)) * 100, 2) as budget_used_percentage,
                mc.roi_percentage,
                mc.status
            FROM marketing_campaigns mc
            LEFT JOIN marketing_campaign_emails mce ON mc.id = mce.campaign_id
            LEFT JOIN marketing_campaign_leads mcl ON mc.id = mcl.campaign_id
            WHERE mc.company_id = ?
            GROUP BY mc.id
            ORDER BY mc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSEOPerformance() {
        return $this->db->querySingle("
            SELECT
                AVG(wa.page_views) as avg_page_views,
                AVG(wa.unique_visitors) as avg_unique_visitors,
                AVG(wa.bounce_rate) as avg_bounce_rate,
                AVG(wa.avg_session_duration) as avg_session_duration,
                COUNT(DISTINCT wa.page_url) as indexed_pages,
                MAX(wa.created_at) as last_updated
            FROM website_analytics wa
            WHERE wa.company_id = ? AND wa.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);
    }

    private function getPages() {
        return $this->db->query("
            SELECT
                p.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                pt.name as template_name,
                COUNT(pc.id) as content_blocks_count,
                MAX(pc.updated_at) as last_content_update,
                p.seo_score,
                p.page_views
            FROM pages p
            LEFT JOIN users u ON p.created_by = u.id
            LEFT JOIN page_templates pt ON p.template_id = pt.id
            LEFT JOIN page_content pc ON p.id = pc.page_id
            WHERE p.company_id = ?
            GROUP BY p.id, u.first_name, u.last_name, pt.name
            ORDER BY p.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPageTemplates() {
        return $this->db->query("
            SELECT * FROM page_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getContentBlocks() {
        return $this->db->query("
            SELECT
                pcb.*,
                p.title as page_title,
                u.first_name as updated_by_first,
                u.last_name as updated_by_last,
                pcb.block_type,
                pcb.content,
                pcb.is_active
            FROM page_content_blocks pcb
            JOIN pages p ON pcb.page_id = p.id
            LEFT JOIN users u ON pcb.updated_by = u.id
            WHERE pcb.company_id = ?
            ORDER BY p.title, pcb.sort_order
        ", [$this->user['company_id']]);
    }

    private function getMediaLibrary() {
        return $this->db->query("
            SELECT
                ml.*,
                u.first_name as uploaded_by_first,
                u.last_name as uploaded_by_last,
                ml.file_name,
                ml.file_size,
                ml.mime_type,
                ml.alt_text,
                COUNT(mlu.id) as usage_count
            FROM media_library ml
            LEFT JOIN users u ON ml.uploaded_by = u.id
            LEFT JOIN media_library_usage mlu ON ml.id = mlu.media_id
            WHERE ml.company_id = ?
            GROUP BY ml.id, u.first_name, u.last_name
            ORDER BY ml.uploaded_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPageBuilder() {
        return [
            'components' => [
                'hero' => 'Hero Section',
                'text' => 'Text Block',
                'image' => 'Image Block',
                'gallery' => 'Image Gallery',
                'video' => 'Video Player',
                'form' => 'Contact Form',
                'cta' => 'Call to Action',
                'testimonials' => 'Testimonials',
                'pricing' => 'Pricing Table',
                'faq' => 'FAQ Section'
            ],
            'layouts' => [
                'single_column' => 'Single Column',
                'two_column' => 'Two Column',
                'three_column' => 'Three Column',
                'sidebar_left' => 'Left Sidebar',
                'sidebar_right' => 'Right Sidebar',
                'full_width' => 'Full Width'
            ]
        ];
    }

    private function getSEOSettings() {
        return $this->db->querySingle("
            SELECT * FROM seo_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBlogPosts($filters) {
        $where = ["bp.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['category']) {
            $where[] = "bp.category_id = ?";
            $params[] = $filters['category'];
        }

        if ($filters['status']) {
            $where[] = "bp.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['author']) {
            $where[] = "bp.author_id = ?";
            $params[] = $filters['author'];
        }

        if ($filters['date_from']) {
            $where[] = "bp.published_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "bp.published_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(bp.title LIKE ? OR bp.content LIKE ? OR bp.excerpt LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                bp.*,
                u.first_name as author_first,
                u.last_name as author_last,
                bc.name as category_name,
                COUNT(bpc.id) as comment_count,
                COUNT(bpv.id) as view_count,
                COUNT(bpl.id) as like_count,
                bp.seo_score,
                bp.read_time_minutes
            FROM blog_posts bp
            LEFT JOIN users u ON bp.author_id = u.id
            LEFT JOIN blog_categories bc ON bp.category_id = bc.id
            LEFT JOIN blog_post_comments bpc ON bp.id = bpc.post_id
            LEFT JOIN blog_post_views bpv ON bp.id = bpv.post_id
            LEFT JOIN blog_post_likes bpl ON bp.id = bpl.post_id
            WHERE $whereClause
            GROUP BY bp.id, u.first_name, u.last_name, bc.name
            ORDER BY bp.created_at DESC
        ", $params);
    }

    private function getBlogCategories() {
        return $this->db->query("
            SELECT
                bc.*,
                COUNT(bp.id) as post_count,
                MAX(bp.published_at) as last_post_date
            FROM blog_categories bc
            LEFT JOIN blog_posts bp ON bc.id = bp.category_id
            WHERE bc.company_id = ?
            GROUP BY bc.id
            ORDER BY bc.name ASC
        ", [$this->user['company_id']]);
    }

    private function getBlogTags() {
        return $this->db->query("
            SELECT
                bt.*,
                COUNT(bpt.post_id) as usage_count
            FROM blog_tags bt
            LEFT JOIN blog_post_tags bpt ON bt.id = bpt.tag_id
            WHERE bt.company_id = ?
            GROUP BY bt.id
            ORDER BY bt.name ASC
        ", [$this->user['company_id']]);
    }

    private function getBlogAuthors() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                u.id as user_id,
                COUNT(bp.id) as post_count,
                MAX(bp.published_at) as last_post_date,
                AVG(bp.seo_score) as avg_seo_score
            FROM users u
            LEFT JOIN blog_posts bp ON u.id = bp.author_id
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY u.first_name, u.last_name
        ", [$this->user['company_id']]);
    }

    private function getPostTemplates() {
        return $this->db->query("
            SELECT * FROM blog_post_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getBlogStats($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['category']) {
            $where[] = "category_id = ?";
            $params[] = $filters['category'];
        }

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_posts,
                COUNT(CASE WHEN status = 'published' THEN 1 END) as published_posts,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_posts,
                SUM(view_count) as total_views,
                SUM(comment_count) as total_comments,
                AVG(seo_score) as avg_seo_score
            FROM blog_posts
            WHERE $whereClause
        ", $params);
    }

    private function getSocialPosts() {
        return $this->db->query("
            SELECT
                sp.*,
                sa.platform,
                sa.account_name,
                u.first_name as posted_by_first,
                u.last_name as posted_by_last,
                sp.post_type,
                sp.engagement_rate,
                sp.reach_count,
                sp.impression_count
            FROM social_posts sp
            JOIN social_accounts sa ON sp.account_id = sa.id
            LEFT JOIN users u ON sp.posted_by = u.id
            WHERE sp.company_id = ?
            ORDER BY sp.scheduled_time DESC
        ", [$this->user['company_id']]);
    }

    private function getSocialSchedules() {
        return $this->db->query("
            SELECT
                ss.*,
                sa.platform,
                sa.account_name,
                u.first_name as scheduled_by_first,
                u.last_name as scheduled_by_last,
                ss.post_content,
                ss.scheduled_time,
                TIMESTAMPDIFF(MINUTE, NOW(), ss.scheduled_time) as minutes_until_post
            FROM social_schedules ss
            JOIN social_accounts sa ON ss.account_id = sa.id
            LEFT JOIN users u ON ss.scheduled_by = u.id
            WHERE ss.company_id = ? AND ss.status = 'scheduled'
            ORDER BY ss.scheduled_time ASC
        ", [$this->user['company_id']]);
    }

    private function getSocialAnalytics() {
        return $this->db->query("
            SELECT
                sa.platform,
                sa.account_name,
                COUNT(sp.id) as total_posts,
                SUM(sp.reach_count) as total_reach,
                SUM(sp.impression_count) as total_impressions,
                AVG(sp.engagement_rate) as avg_engagement,
                MAX(sp.created_at) as last_post_date,
                COUNT(CASE WHEN sp.scheduled_time > NOW() THEN 1 END) as scheduled_posts
            FROM social_accounts sa
            LEFT JOIN social_posts sp ON sa.id = sp.account_id
            WHERE sa.company_id = ?
            GROUP BY sa.id, sa.platform, sa.account_name
            ORDER BY sa.platform, sa.account_name
        ", [$this->user['company_id']]);
    }

    private function getSupportedPlatforms() {
        return [
            'facebook' => ['name' => 'Facebook', 'features' => ['posts', 'pages', 'groups', 'ads']],
            'twitter' => ['name' => 'Twitter/X', 'features' => ['tweets', 'threads', 'spaces']],
            'linkedin' => ['name' => 'LinkedIn', 'features' => ['posts', 'articles', 'companies']],
            'instagram' => ['name' => 'Instagram', 'features' => ['posts', 'stories', 'reels']],
            'youtube' => ['name' => 'YouTube', 'features' => ['videos', 'shorts', 'live']],
            'tiktok' => ['name' => 'TikTok', 'features' => ['videos', 'live', 'duets']],
            'pinterest' => ['name' => 'Pinterest', 'features' => ['pins', 'boards', 'ads']]
        ];
    }

    private function getSocialTemplates() {
        return $this->db->query("
            SELECT * FROM social_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY platform, template_type
        ", [$this->user['company_id']]);
    }

    private function getSocialEngagement() {
        return $this->db->query("
            SELECT
                sp.platform,
                COUNT(se.id) as total_engagements,
                SUM(CASE WHEN se.engagement_type = 'like' THEN 1 ELSE 0 END) as likes,
                SUM(CASE WHEN se.engagement_type = 'comment' THEN 1 ELSE 0 END) as comments,
                SUM(CASE WHEN se.engagement_type = 'share' THEN 1 ELSE 0 END) as shares,
                SUM(CASE WHEN se.engagement_type = 'click' THEN 1 ELSE 0 END) as clicks,
                AVG(se.engagement_value) as avg_engagement_value
            FROM social_posts sp
            LEFT JOIN social_engagements se ON sp.id = se.post_id
            WHERE sp.company_id = ?
            GROUP BY sp.platform
            ORDER BY sp.platform
        ", [$this->user['company_id']]);
    }

    private function getPortalCustomers() {
        return $this->db->query("
            SELECT
                pc.*,
                u.first_name,
                u.last_name,
                u.email,
                COUNT(st.id) as ticket_count,
                COUNT(co.id) as order_count,
                MAX(pc.last_login) as last_login,
                pc.account_status,
                pc.satisfaction_score
            FROM portal_customers pc
            LEFT JOIN users u ON pc.user_id = u.id
            LEFT JOIN support_tickets st ON pc.id = st.customer_id
            LEFT JOIN customer_orders co ON pc.id = co.customer_id
            WHERE pc.company_id = ?
            GROUP BY pc.id, u.first_name, u.last_name, u.email
            ORDER BY pc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSupportTickets() {
        return $this->db->query("
            SELECT
                st.*,
                pc.customer_name,
                u.first_name as assigned_to_first,
                u.last_name as assigned_to_last,
                st.priority,
                st.status,
                st.created_at,
                TIMESTAMPDIFF(HOUR, st.created_at, NOW()) as hours_open
            FROM support_tickets st
            JOIN portal_customers pc ON st.customer_id = pc.id
            LEFT JOIN users u ON st.assigned_to = u.id
            WHERE st.company_id = ?
            ORDER BY st.priority DESC, st.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerKnowledgeBase() {
        return $this->db->query("
            SELECT
                kb.*,
                COUNT(kbv.id) as view_count,
                COUNT(kbh.id) as helpful_count,
                AVG(kbr.rating) as avg_rating,
                kb.category,
                kb.is_featured
            FROM knowledge_base kb
            LEFT JOIN knowledge_base_views kbv ON kb.id = kbv.article_id
            LEFT JOIN knowledge_base_helpful kbh ON kb.id = kbh.article_id
            LEFT JOIN knowledge_base_ratings kbr ON kb.id = kbr.article_id
            WHERE kb.company_id = ? AND kb.is_published = true
            GROUP BY kb.id
            ORDER BY kb.is_featured DESC, kb.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerOrders() {
        return $this->db->query("
            SELECT
                co.*,
                pc.customer_name,
                co.order_number,
                co.total_amount,
                co.status,
                co.created_at,
                COUNT(coi.id) as item_count
            FROM customer_orders co
            JOIN portal_customers pc ON co.customer_id = pc.id
            LEFT JOIN customer_order_items coi ON co.id = coi.order_id
            WHERE co.company_id = ?
            GROUP BY co.id, pc.customer_name
            ORDER BY co.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerDownloads() {
        return $this->db->query("
            SELECT
                cd.*,
                pc.customer_name,
                f.file_name,
                f.file_size,
                cd.downloaded_at,
                cd.ip_address
            FROM customer_downloads cd
            JOIN portal_customers pc ON cd.customer_id = pc.id
            JOIN files f ON cd.file_id = f.id
            WHERE cd.company_id = ?
            ORDER BY cd.downloaded_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPortalSettings() {
        return $this->db->querySingle("
            SELECT * FROM portal_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCustomerStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(pc.id) as total_customers,
                COUNT(CASE WHEN pc.last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_customers,
                COUNT(CASE WHEN pc.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_customers,
                COUNT(st.id) as total_tickets,
                COUNT(CASE WHEN st.status = 'open' THEN 1 END) as open_tickets,
                AVG(pc.satisfaction_score) as avg_satisfaction
            FROM portal_customers pc
            LEFT JOIN support_tickets st ON pc.id = st.customer_id
            WHERE pc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEcommerceProducts() {
        return $this->db->query("
            SELECT
                ep.*,
                COUNT(epv.id) as variant_count,
                COUNT(epi.id) as image_count,
                SUM(epv.stock_quantity) as total_stock,
                ep.price,
                ep.is_active,
                ep.featured
            FROM ecommerce_products ep
            LEFT JOIN ecommerce_product_variants epv ON ep.id = epv.product_id
            LEFT JOIN ecommerce_product_images epi ON ep.id = epi.product_id
            WHERE ep.company_id = ?
            GROUP BY ep.id
            ORDER BY ep.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getEcommerceOrders() {
        return $this->db->query("
            SELECT
                eo.*,
                pc.customer_name,
                eo.order_number,
                eo.total_amount,
                eo.status,
                eo.payment_status,
                eo.created_at,
                COUNT(eoi.id) as item_count
            FROM ecommerce_orders eo
            LEFT JOIN portal_customers pc ON eo.customer_id = pc.id
            LEFT JOIN ecommerce_order_items eoi ON eo.id = eoi.order_id
            WHERE eo.company_id = ?
            GROUP BY eo.id, pc.customer_name
            ORDER BY eo.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getEcommerceCustomers() {
        return $this->db->query("
            SELECT
                pc.*,
                COUNT(eo.id) as order_count,
                SUM(eo.total_amount) as total_spent,
                MAX(eo.created_at) as last_order_date,
                AVG(pc.satisfaction_score) as satisfaction_score
            FROM portal_customers pc
            LEFT JOIN ecommerce_orders eo ON pc.id = eo.customer_id
            WHERE pc.company_id = ?
            GROUP BY pc.id
            ORDER BY pc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPaymentMethods() {
        return $this->db->query("
            SELECT * FROM payment_methods
            WHERE company_id = ? AND is_active = true
            ORDER BY sort_order, name
        ", [$this->user['company_id']]);
    }

    private function getShippingMethods() {
        return $this->db->query("
            SELECT * FROM shipping_methods
            WHERE company_id = ? AND is_active = true
            ORDER BY sort_order, name
        ", [$this->user['company_id']]);
    }

    private function getEcommerceInventory() {
        return $this->db->query("
            SELECT
                ep.name as product_name,
                ep.sku,
                SUM(epv.stock_quantity) as total_stock,
                SUM(epv.reserved_quantity) as reserved_stock,
                SUM(epv.stock_quantity - epv.reserved_quantity) as available_stock,
                MIN(epv.low_stock_threshold) as low_stock_threshold,
                COUNT(CASE WHEN epv.stock_quantity <= epv.low_stock_threshold THEN 1 END) as low_stock_variants
            FROM ecommerce_products ep
            LEFT JOIN ecommerce_product_variants epv ON ep.id = epv.product_id
            WHERE ep.company_id = ?
            GROUP BY ep.id, ep.name, ep.sku
            ORDER BY available_stock ASC
        ", [$this->user['company_id']]);
    }

    private function getEcommerceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(eo.id) as total_orders,
                SUM(eo.total_amount) as total_revenue,
                AVG(eo.total_amount) as avg_order_value,
                COUNT(DISTINCT eo.customer_id) as unique_customers,
                SUM(eoi.quantity) as total_items_sold,
                COUNT(CASE WHEN eo.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as orders_last_30_days
            FROM ecommerce_orders eo
            LEFT JOIN ecommerce_order_items eoi ON eo.id = eoi.order_id
            WHERE eo.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEcommerceIntegrations() {
        return $this->db->query("
            SELECT * FROM ecommerce_integrations
            WHERE company_id = ? AND is_active = true
            ORDER BY platform, created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getEmailMarketing() {
        return $this->db->query("
            SELECT
                em.*,
                COUNT(emc.id) as campaign_count,
                COUNT(ems.id) as subscriber_count,
                AVG(emc.open_rate) as avg_open_rate,
                AVG(emc.click_rate) as avg_click_rate
            FROM email_marketing em
            LEFT JOIN email_marketing_campaigns emc ON em.id = emc.marketing_id
            LEFT JOIN email_marketing_subscribers ems ON em.id = ems.marketing_id
            WHERE em.company_id = ?
            GROUP BY em.id
            ORDER BY em.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getLeadGeneration() {
        return $this->db->query("
            SELECT
                lg.*,
                COUNT(lgc.id) as conversion_count,
                lg.source_type,
                lg.lead_score,
                lg.status,
                lg.created_at
            FROM lead_generation lg
            LEFT JOIN lead_generation_conversions lgc ON lg.id = lgc.lead_id
            WHERE lg.company_id = ?
            ORDER BY lg.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getMarketingAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(mc.id) as total_campaigns,
                SUM(mc.budget_allocated) as total_budget,
                SUM(mc.budget_spent) as total_spent,
                AVG(mc.roi_percentage) as avg_roi,
                COUNT(lg.id) as total_leads,
                COUNT(CASE WHEN lg.status = 'qualified' THEN 1 END) as qualified_leads,
                AVG(lg.lead_score) as avg_lead_score
            FROM marketing_campaigns mc
            LEFT JOIN lead_generation lg ON lg.company_id = mc.company_id
            WHERE mc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getMarketingAutomation() {
        return $this->db->query("
            SELECT
                ma.*,
                COUNT(maf.id) as trigger_count,
                COUNT(maa.id) as action_count,
                ma.is_active,
                ma.last_run_at,
                COUNT(mae.id) as execution_count
            FROM marketing_automation ma
            LEFT JOIN marketing_automation_triggers maf ON ma.id = maf.automation_id
            LEFT JOIN marketing_automation_actions maa ON ma.id = maa.automation_id
            LEFT JOIN marketing_automation_executions mae ON ma.id = mae.automation_id
            WHERE ma.company_id = ?
            GROUP BY ma.id
            ORDER BY ma.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerSegmentation() {
        return $this->db->query("
            SELECT
                cs.*,
                COUNT(csm.customer_id) as customer_count,
                cs.segment_criteria,
                cs.segment_type,
                cs.created_at
            FROM customer_segments cs
            LEFT JOIN customer_segment_members csm ON cs.id = csm.segment_id
            WHERE cs.company_id = ?
            GROUP BY cs.id
            ORDER BY cs.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getMarketingTemplates() {
        return $this->db->query("
            SELECT * FROM marketing_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getMarketingPerformance() {
        return $this->db->query("
            SELECT
                mc.name as campaign_name,
                mc.type as campaign_type,
                mc.status,
                mc.budget_spent,
                mc.roi_percentage,
                COUNT(mce.id) as email_sent,
                AVG(mce.open_rate) as avg_open_rate,
                AVG(mce.click_rate) as avg_click_rate,
                COUNT(lg.id) as leads_generated
            FROM marketing_campaigns mc
            LEFT JOIN marketing_campaign_emails mce ON mc.id = mce.campaign_id
            LEFT JOIN lead_generation lg ON lg.campaign_id = mc.id
            WHERE mc.company_id = ?
            GROUP BY mc.id, mc.name, mc.type, mc.status, mc.budget_spent, mc.roi_percentage
            ORDER BY mc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSEOKeywords() {
        return $this->db->query("
            SELECT
                sk.*,
                sk.keyword,
                sk.search_volume,
                sk.difficulty_score,
                sk.current_ranking,
                sk.target_ranking,
                sk.competition_level
            FROM seo_keywords sk
            WHERE sk.company_id = ?
            ORDER BY sk.search_volume DESC, sk.current_ranking ASC
        ", [$this->user['company_id']]);
    }

    private function getBacklinks() {
        return $this->db->query("
            SELECT
                sb.*,
                sb.source_url,
                sb.target_url,
                sb.anchor_text,
                sb.domain_authority,
                sb.link_type,
                sb.follow_status,
                sb.first_seen_date
            FROM seo_backlinks sb
            WHERE sb.company_id = ?
            ORDER BY sb.domain_authority DESC, sb.first_seen_date DESC
        ", [$this->user['company_id']]);
    }

    private function getWebsiteAnalytics() {
        return $this->db->query("
            SELECT
                wa.*,
                wa.page_url,
                wa.page_title,
                wa.page_views,
                wa.unique_visitors,
                wa.bounce_rate,
                wa.avg_session_duration,
                wa.entrance_rate,
                wa.exit_rate
            FROM website_analytics wa
            WHERE wa.company_id = ?
            ORDER BY wa.page_views DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getSEOPerformance() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT wa.page_url) as indexed_pages,
                AVG(wa.page_views) as avg_page_views,
                AVG(wa.bounce_rate) as avg_bounce_rate,
                AVG(wa.avg_session_duration) as avg_session_duration,
                COUNT(sb.id) as total_backlinks,
                AVG(sb.domain_authority) as avg_domain_authority,
                COUNT(DISTINCT sk.keyword) as tracked_keywords,
                AVG(sk.current_ranking) as avg_keyword_ranking
            FROM website_analytics wa
            LEFT JOIN seo_backlinks sb ON sb.company_id = wa.company_id
            LEFT JOIN seo_keywords sk ON sk.company_id = wa.company_id
            WHERE wa.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSEOCompetitors() {
        return $this->db->query("
            SELECT
                sc.*,
                sc.competitor_domain,
                sc.competitor_authority,
                sc.overlapping_keywords,
                sc.common_backlinks,
                sc.threat_level
            FROM seo_competitors sc
            WHERE sc.company_id = ?
            ORDER BY sc.threat_level DESC, sc.competitor_authority DESC
        ", [$this->user['company_id']]);
    }

    private function getSEOReports() {
        return $this->db->query("
            SELECT
                sr.*,
                sr.report_type,
                sr.report_period,
                sr.generated_at,
                sr.key_findings,
                sr.recommendations
            FROM seo_reports sr
            WHERE sr.company_id = ?
            ORDER BY sr.generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSEORecommendations() {
        return $this->db->query("
            SELECT
                sre.*,
                sre.recommendation_type,
                sre.priority_level,
                sre.implementation_difficulty,
                sre.estimated_impact,
                sre.status
            FROM seo_recommendations sre
            WHERE sre.company_id = ?
            ORDER BY sre.priority_level ASC, sre.estimated_impact DESC
        ", [$this->user['company_id']]);
    }

    private function getWebsiteForms() {
        return $this->db->query("
            SELECT
                wf.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(wfs.id) as submission_count,
                MAX(wfs.submitted_at) as last_submission,
                wf.form_type,
                wf.is_active
            FROM website_forms wf
            LEFT JOIN users u ON wf.created_by = u.id
            LEFT JOIN website_form_submissions wfs ON wf.id = wfs.form_id
            WHERE wf.company_id = ?
            GROUP BY wf.id, u.first_name, u.last_name
            ORDER BY wf.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getFormSubmissions() {
        return $this->db->query("
            SELECT
                wfs.*,
                wf.form_name,
                wfs.submitted_data,
                wfs.submitted_at,
                wfs.ip_address,
                wfs.user_agent
            FROM website_form_submissions wfs
            JOIN website_forms wf ON wfs.form_id = wf.id
            WHERE wfs.company_id = ?
            ORDER BY wfs.submitted_at DESC
        ", [$this->user['company_id']]);
    }

    private function getLeads() {
        return $this->db->query("
            SELECT
                l.*,
                l.lead_source,
                l.lead_score,
                l.status,
                l.created_at,
                COUNT(lf.id) as follow_up_count,
                MAX(lf.follow_up_date) as last_follow_up
            FROM leads l
            LEFT JOIN lead_follow_ups lf ON l.id = lf.lead_id
            WHERE l.company_id = ?
            GROUP BY l.id
            ORDER BY l.lead_score DESC, l.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getFormTemplates() {
        return $this->db->query("
            SELECT * FROM form_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getFormIntegrations() {
        return $this->db->query("
            SELECT * FROM form_integrations
            WHERE company_id = ? AND is_active = true
            ORDER BY platform, created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getFormAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(wf.id) as total_forms,
                COUNT(wfs.id) as total_submissions,
                AVG(wfs.conversion_rate) as avg_conversion_rate,
                COUNT(l.id) as total_leads,
                COUNT(CASE WHEN l.status = 'qualified' THEN 1 END) as qualified_leads,
                AVG(l.lead_score) as avg_lead_score
            FROM website_forms wf
            LEFT JOIN website_form_submissions wfs ON wf.id = wfs.form_id
            LEFT JOIN leads l ON l.form_submission_id = wfs.id
            WHERE wf.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getFormAutomation() {
        return $this->db->query("
            SELECT
                fa.*,
                wf.form_name,
                fa.trigger_event,
                fa.automation_action,
                fa.is_active,
                COUNT(fae.id) as execution_count
            FROM form_automation fa
            JOIN website_forms wf ON fa.form_id = wf.id
            LEFT JOIN form_automation_executions fae ON fa.id = fae.automation_id
            WHERE fa.company_id = ?
            GROUP BY fa.id, wf.form_name
            ORDER BY fa.created_at DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function createBlogPost() {
        $this->requirePermission('website.blog.create');

        $data = $this->validateRequest([
            'title' => 'required|string',
            'content' => 'required|string',
            'excerpt' => 'string',
            'category_id' => 'required|integer',
            'tags' => 'array',
            'featured_image' => 'string',
            'seo_title' => 'string',
            'seo_description' => 'string',
            'status' => 'string',
            'scheduled_date' => 'date'
        ]);

        try {
            $this->db->beginTransaction();

            $postId = $this->db->insert('blog_posts', [
                'company_id' => $this->user['company_id'],
                'title' => $data['title'],
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? '',
                'category_id' => $data['category_id'],
                'featured_image'
