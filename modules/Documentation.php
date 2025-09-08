<?php
/**
 * TPT Free ERP - Documentation Module
 * Complete documentation system with user manuals, API docs, guides, and tutorials
 */

class Documentation extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main documentation dashboard
     */
    public function index() {
        $this->requirePermission('documentation.view');

        $data = [
            'title' => 'Documentation Center',
            'documentation_overview' => $this->getDocumentationOverview(),
            'user_manuals' => $this->getUserManuals(),
            'api_documentation' => $this->getAPIDocumentation(),
            'installation_guides' => $this->getInstallationGuides(),
            'developer_docs' => $this->getDeveloperDocs(),
            'video_tutorials' => $this->getVideoTutorials(),
            'search_index' => $this->getSearchIndex(),
            'recent_updates' => $this->getRecentUpdates()
        ];

        $this->render('modules/documentation/dashboard', $data);
    }

    /**
     * User manuals
     */
    public function userManuals() {
        $this->requirePermission('documentation.user_manuals.view');

        $data = [
            'title' => 'User Manuals',
            'manual_categories' => $this->getManualCategories(),
            'featured_manuals' => $this->getFeaturedManuals(),
            'getting_started' => $this->getGettingStartedManuals(),
            'module_manuals' => $this->getModuleManuals(),
            'troubleshooting' => $this->getTroubleshootingManuals(),
            'manual_feedback' => $this->getManualFeedback(),
            'manual_analytics' => $this->getManualAnalytics()
        ];

        $this->render('modules/documentation/user_manuals', $data);
    }

    /**
     * API documentation
     */
    public function apiDocs() {
        $this->requirePermission('documentation.api.view');

        $data = [
            'title' => 'API Documentation',
            'api_endpoints' => $this->getAPIEndpoints(),
            'api_categories' => $this->getAPICategories(),
            'authentication_guide' => $this->getAuthenticationGuide(),
            'api_examples' => $this->getAPIExamples(),
            'sdk_downloads' => $this->getSDKDownloads(),
            'api_changelog' => $this->getAPIChangelog(),
            'api_testing_tools' => $this->getAPITestingTools()
        ];

        $this->render('modules/documentation/api_docs', $data);
    }

    /**
     * Installation guides
     */
    public function installation() {
        $this->requirePermission('documentation.installation.view');

        $data = [
            'title' => 'Installation Guides',
            'system_requirements' => $this->getSystemRequirements(),
            'installation_methods' => $this->getInstallationMethods(),
            'configuration_guides' => $this->getConfigurationGuides(),
            'upgrade_guides' => $this->getUpgradeGuides(),
            'troubleshooting_install' => $this->getTroubleshootingInstall(),
            'post_installation' => $this->getPostInstallation(),
            'installation_analytics' => $this->getInstallationAnalytics()
        ];

        $this->render('modules/documentation/installation', $data);
    }

    /**
     * Developer documentation
     */
    public function developer() {
        $this->requirePermission('documentation.developer.view');

        $data = [
            'title' => 'Developer Documentation',
            'architecture_overview' => $this->getArchitectureOverview(),
            'coding_standards' => $this->getCodingStandards(),
            'api_reference' => $this->getAPIReference(),
            'module_development' => $this->getModuleDevelopment(),
            'testing_guide' => $this->getTestingGuide(),
            'deployment_guide' => $this->getDeploymentGuide(),
            'contributing_guide' => $this->getContributingGuide()
        ];

        $this->render('modules/documentation/developer', $data);
    }

    /**
     * Video tutorials
     */
    public function videos() {
        $this->requirePermission('documentation.videos.view');

        $data = [
            'title' => 'Video Tutorials',
            'video_categories' => $this->getVideoCategories(),
            'featured_videos' => $this->getFeaturedVideos(),
            'tutorial_series' => $this->getTutorialSeries(),
            'quick_tips' => $this->getQuickTips(),
            'webinars' => $this->getWebinars(),
            'video_analytics' => $this->getVideoAnalytics(),
            'video_feedback' => $this->getVideoFeedback()
        ];

        $this->render('modules/documentation/videos', $data);
    }

    /**
     * Search and navigation
     */
    public function search() {
        $query = $_GET['q'] ?? '';
        $category = $_GET['category'] ?? '';
        $type = $_GET['type'] ?? '';

        $data = [
            'title' => 'Documentation Search',
            'search_query' => $query,
            'search_results' => $this->performSearch($query, $category, $type),
            'search_filters' => $this->getSearchFilters(),
            'popular_searches' => $this->getPopularSearches(),
            'search_suggestions' => $this->getSearchSuggestions($query),
            'search_analytics' => $this->getSearchAnalytics()
        ];

        $this->render('modules/documentation/search', $data);
    }

    /**
     * Documentation management
     */
    public function management() {
        $this->requirePermission('documentation.management.view');

        $data = [
            'title' => 'Documentation Management',
            'content_management' => $this->getContentManagement(),
            'version_control' => $this->getVersionControl(),
            'translation_management' => $this->getTranslationManagement(),
            'review_workflow' => $this->getReviewWorkflow(),
            'publishing_tools' => $this->getPublishingTools(),
            'analytics_dashboard' => $this->getAnalyticsDashboard(),
            'quality_assurance' => $this->getQualityAssurance()
        ];

        $this->render('modules/documentation/management', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getDocumentationOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT dm.id) as total_documents,
                COUNT(DISTINCT CASE WHEN dm.doc_type = 'user_manual' THEN dm.id END) as user_manuals,
                COUNT(DISTINCT CASE WHEN dm.doc_type = 'api_doc' THEN dm.id END) as api_documents,
                COUNT(DISTINCT CASE WHEN dm.doc_type = 'installation' THEN dm.id END) as installation_guides,
                COUNT(DISTINCT CASE WHEN dm.doc_type = 'developer' THEN dm.id END) as developer_docs,
                COUNT(DISTINCT CASE WHEN dm.doc_type = 'video' THEN dm.id END) as video_tutorials,
                SUM(dm.view_count) as total_views,
                AVG(dm.rating) as avg_rating,
                COUNT(DISTINCT CASE WHEN dm.last_updated >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN dm.id END) as recently_updated,
                MAX(dm.last_updated) as last_update
            FROM documentation_master dm
            WHERE dm.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getUserManuals() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                dm.doc_type,
                dm.category,
                dm.view_count,
                dm.download_count,
                dm.rating,
                dm.last_updated,
                u.first_name,
                u.last_name,
                COUNT(dc.id) as chapter_count
            FROM documentation_master dm
            LEFT JOIN users u ON dm.author_id = u.id
            LEFT JOIN documentation_chapters dc ON dm.id = dc.doc_id
            WHERE dm.company_id = ? AND dm.doc_type = 'user_manual'
            GROUP BY dm.id
            ORDER BY dm.view_count DESC, dm.rating DESC
        ", [$this->user['company_id']]);
    }

    private function getAPIDocumentation() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                dm.category,
                dm.view_count,
                dm.last_updated,
                COUNT(ae.id) as endpoint_count,
                COUNT(DISTINCT ae.category) as category_count
            FROM documentation_master dm
            LEFT JOIN api_endpoints ae ON dm.id = ae.doc_id
            WHERE dm.company_id = ? AND dm.doc_type = 'api_doc'
            GROUP BY dm.id
            ORDER BY dm.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getInstallationGuides() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                dm.category,
                dm.view_count,
                dm.last_updated,
                ig.installation_method,
                ig.estimated_time,
                ig.difficulty_level
            FROM documentation_master dm
            JOIN installation_guides ig ON dm.id = ig.doc_id
            WHERE dm.company_id = ?
            ORDER BY ig.difficulty_level ASC, dm.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getDeveloperDocs() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                dm.category,
                dm.view_count,
                dm.last_updated,
                dd.target_audience,
                dd.prerequisites,
                dd.learning_objectives
            FROM documentation_master dm
            JOIN developer_docs dd ON dm.id = dd.doc_id
            WHERE dm.company_id = ?
            ORDER BY dd.target_audience ASC, dm.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getVideoTutorials() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                dm.category,
                dm.view_count,
                dm.last_updated,
                vt.duration_minutes,
                vt.video_url,
                vt.thumbnail_url,
                vt.transcript_available
            FROM documentation_master dm
            JOIN video_tutorials vt ON dm.id = vt.doc_id
            WHERE dm.company_id = ?
            ORDER BY dm.view_count DESC, vt.duration_minutes ASC
        ", [$this->user['company_id']]);
    }

    private function getSearchIndex() {
        return $this->db->query("
            SELECT
                si.*,
                si.keyword,
                si.content_type,
                si.relevance_score,
                COUNT(sir.id) as search_count
            FROM search_index si
            LEFT JOIN search_index_results sir ON si.id = sir.index_id
            WHERE si.company_id = ?
            GROUP BY si.id
            ORDER BY si.relevance_score DESC
            LIMIT 1000
        ", [$this->user['company_id']]);
    }

    private function getRecentUpdates() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.doc_type,
                dm.last_updated,
                u.first_name,
                u.last_name,
                TIMESTAMPDIFF(DAY, dm.last_updated, NOW()) as days_since_update
            FROM documentation_master dm
            LEFT JOIN users u ON dm.author_id = u.id
            WHERE dm.company_id = ?
            ORDER BY dm.last_updated DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getManualCategories() {
        return [
            'getting_started' => [
                'name' => 'Getting Started',
                'description' => 'Basic introduction and setup guides',
                'icon' => 'rocket',
                'documents' => []
            ],
            'user_management' => [
                'name' => 'User Management',
                'description' => 'Managing users, roles, and permissions',
                'icon' => 'users',
                'documents' => []
            ],
            'modules' => [
                'name' => 'Module Guides',
                'description' => 'Detailed guides for each ERP module',
                'icon' => 'cubes',
                'documents' => []
            ],
            'administration' => [
                'name' => 'System Administration',
                'description' => 'System configuration and maintenance',
                'icon' => 'cog',
                'documents' => []
            ],
            'troubleshooting' => [
                'name' => 'Troubleshooting',
                'description' => 'Common issues and solutions',
                'icon' => 'wrench',
                'documents' => []
            ]
        ];
    }

    private function getFeaturedManuals() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                dm.view_count,
                dm.rating,
                dm.last_updated
            FROM documentation_master dm
            WHERE dm.company_id = ? AND dm.doc_type = 'user_manual' AND dm.featured = true
            ORDER BY dm.rating DESC, dm.view_count DESC
            LIMIT 6
        ", [$this->user['company_id']]);
    }

    private function getGettingStartedManuals() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                dm.view_count,
                dm.estimated_read_time
            FROM documentation_master dm
            WHERE dm.company_id = ? AND dm.doc_type = 'user_manual' AND dm.category = 'getting_started'
            ORDER BY dm.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getModuleManuals() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                dm.category,
                dm.view_count,
                dm.last_updated
            FROM documentation_master dm
            WHERE dm.company_id = ? AND dm.doc_type = 'user_manual' AND dm.category = 'modules'
            ORDER BY dm.category ASC, dm.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getTroubleshootingManuals() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                dm.view_count,
                tm.common_issues,
                tm.solutions_count
            FROM documentation_master dm
            JOIN troubleshooting_manuals tm ON dm.id = tm.doc_id
            WHERE dm.company_id = ?
            ORDER BY tm.solutions_count DESC, dm.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getManualFeedback() {
        return $this->db->query("
            SELECT
                mf.*,
                mf.feedback_type,
                mf.feedback_text,
                mf.rating,
                mf.created_at,
                u.first_name,
                u.last_name,
                dm.title as document_title
            FROM manual_feedback mf
            LEFT JOIN users u ON mf.user_id = u.id
            LEFT JOIN documentation_master dm ON mf.doc_id = dm.id
            WHERE mf.company_id = ?
            ORDER BY mf.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getManualAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT mf.id) as total_feedback,
                AVG(mf.rating) as avg_rating,
                COUNT(CASE WHEN mf.feedback_type = 'helpful' THEN 1 END) as helpful_feedback,
                COUNT(CASE WHEN mf.feedback_type = 'confusing' THEN 1 END) as confusing_feedback,
                COUNT(CASE WHEN mf.feedback_type = 'outdated' THEN 1 END) as outdated_feedback,
                MAX(mf.created_at) as last_feedback
            FROM manual_feedback mf
            WHERE mf.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAPIEndpoints() {
        return $this->db->query("
            SELECT
                ae.*,
                ae.endpoint,
                ae.method,
                ae.description,
                ae.category,
                ae.parameters,
                ae.responses,
                ae.auth_required,
                ae.deprecated
            FROM api_endpoints ae
            WHERE ae.company_id = ?
            ORDER BY ae.category ASC, ae.endpoint ASC
        ", [$this->user['company_id']]);
    }

    private function getAPICategories() {
        return [
            'authentication' => [
                'name' => 'Authentication',
                'description' => 'User authentication and authorization',
                'endpoints' => []
            ],
            'users' => [
                'name' => 'User Management',
                'description' => 'User CRUD operations',
                'endpoints' => []
            ],
            'modules' => [
                'name' => 'Modules',
                'description' => 'ERP module operations',
                'endpoints' => []
            ],
            'reports' => [
                'name' => 'Reports',
                'description' => 'Report generation and management',
                'endpoints' => []
            ],
            'system' => [
                'name' => 'System',
                'description' => 'System configuration and monitoring',
                'endpoints' => []
            ]
        ];
    }

    private function getAuthenticationGuide() {
        return $this->db->querySingle("
            SELECT * FROM documentation_master
            WHERE company_id = ? AND doc_type = 'api_doc' AND category = 'authentication'
            ORDER BY last_updated DESC
            LIMIT 1
        ", [$this->user['company_id']]);
    }

    private function getAPIExamples() {
        return $this->db->query("
            SELECT
                ae.*,
                ae.language,
                ae.code_example,
                ae.description,
                ae.endpoint_id
            FROM api_examples ae
            WHERE ae.company_id = ?
            ORDER BY ae.language ASC, ae.endpoint_id ASC
        ", [$this->user['company_id']]);
    }

    private function getSDKDownloads() {
        return [
            'php' => [
                'name' => 'PHP SDK',
                'description' => 'Official PHP SDK for TPT Free ERP API',
                'version' => '1.0.0',
                'download_url' => '/downloads/sdk/php/tpt-erp-php-sdk.zip',
                'documentation_url' => '/docs/sdk/php',
                'size' => '2.5 MB'
            ],
            'javascript' => [
                'name' => 'JavaScript SDK',
                'description' => 'Official JavaScript SDK for TPT Free ERP API',
                'version' => '1.0.0',
                'download_url' => '/downloads/sdk/js/tpt-erp-js-sdk.zip',
                'documentation_url' => '/docs/sdk/javascript',
                'size' => '1.8 MB'
            ],
            'python' => [
                'name' => 'Python SDK',
                'description' => 'Official Python SDK for TPT Free ERP API',
                'version' => '1.0.0',
                'download_url' => '/downloads/sdk/python/tpt-erp-python-sdk.zip',
                'documentation_url' => '/docs/sdk/python',
                'size' => '3.2 MB'
            ]
        ];
    }

    private function getAPIChangelog() {
        return $this->db->query("
            SELECT
                ac.*,
                ac.version,
                ac.release_date,
                ac.changes,
                ac.breaking_changes,
                ac.deprecated_endpoints
            FROM api_changelog ac
            WHERE ac.company_id = ?
            ORDER BY ac.release_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAPITestingTools() {
        return [
            'postman_collection' => [
                'name' => 'Postman Collection',
                'description' => 'Complete API collection for Postman',
                'download_url' => '/downloads/api/postman_collection.json',
                'last_updated' => date('Y-m-d')
            ],
            'swagger_ui' => [
                'name' => 'Swagger UI',
                'description' => 'Interactive API documentation',
                'url' => '/api/docs',
                'last_updated' => date('Y-m-d')
            ],
            'api_tester' => [
                'name' => 'API Tester Tool',
                'description' => 'Built-in API testing interface',
                'url' => '/docs/api-tester',
                'last_updated' => date('Y-m-d')
            ]
        ];
    }

    private function getSystemRequirements() {
        return [
            'server' => [
                'os' => 'Linux, Windows Server 2016+, macOS 10.15+',
                'web_server' => 'Apache 2.4+, Nginx 1.18+',
                'php' => 'PHP 8.1+ with extensions: pdo, pdo_pgsql, mbstring, openssl, curl, gd, zip',
                'database' => 'PostgreSQL 13+',
                'memory' => 'Minimum 2GB RAM, Recommended 4GB+',
                'storage' => 'Minimum 10GB free space',
                'cpu' => 'Dual-core processor, Recommended quad-core+'
            ],
            'client' => [
                'browser' => 'Chrome 90+, Firefox 88+, Safari 14+, Edge 90+',
                'javascript' => 'ES6+ support required',
                'cookies' => 'Must be enabled',
                'local_storage' => 'Must be enabled',
                'screen_resolution' => 'Minimum 1024x768'
            ]
        ];
    }

    private function getInstallationMethods() {
        return [
            'docker' => [
                'name' => 'Docker Installation',
                'description' => 'Quick setup using Docker containers',
                'difficulty' => 'Easy',
                'estimated_time' => 15,
                'prerequisites' => ['Docker', 'Docker Compose'],
                'steps' => []
            ],
            'manual' => [
                'name' => 'Manual Installation',
                'description' => 'Step-by-step manual installation',
                'difficulty' => 'Medium',
                'estimated_time' => 45,
                'prerequisites' => ['Web server', 'PHP', 'PostgreSQL'],
                'steps' => []
            ],
            'cloud' => [
                'name' => 'Cloud Installation',
                'description' => 'Automated cloud deployment',
                'difficulty' => 'Easy',
                'estimated_time' => 10,
                'prerequisites' => ['Cloud account (AWS, Azure, GCP)'],
                'steps' => []
            ]
        ];
    }

    private function getConfigurationGuides() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                dm.view_count,
                cg.config_type,
                cg.difficulty_level
            FROM documentation_master dm
            JOIN configuration_guides cg ON dm.id = cg.doc_id
            WHERE dm.company_id = ?
            ORDER BY cg.difficulty_level ASC, dm.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getUpgradeGuides() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                ug.from_version,
                ug.to_version,
                ug.breaking_changes,
                ug.backup_required
            FROM documentation_master dm
            JOIN upgrade_guides ug ON dm.id = ug.doc_id
            WHERE dm.company_id = ?
            ORDER BY ug.to_version DESC
        ", [$this->user['company_id']]);
    }

    private function getTroubleshootingInstall() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                ti.common_issues,
                ti.solutions_count,
                ti.difficulty_level
            FROM documentation_master dm
            JOIN troubleshooting_install ti ON dm.id = ti.doc_id
            WHERE dm.company_id = ?
            ORDER BY ti.difficulty_level ASC, dm.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getPostInstallation() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                pi.tasks,
                pi.estimated_time,
                pi.priority
            FROM documentation_master dm
            JOIN post_installation pi ON dm.id = pi.doc_id
            WHERE dm.company_id = ?
            ORDER BY pi.priority ASC, pi.estimated_time ASC
        ", [$this->user['company_id']]);
    }

    private function getInstallationAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT i.id) as total_installations,
                COUNT(CASE WHEN i.status = 'completed' THEN 1 END) as successful_installations,
                COUNT(CASE WHEN i.status = 'failed' THEN 1 END) as failed_installations,
                ROUND((COUNT(CASE WHEN i.status = 'completed' THEN 1 END) / NULLIF(COUNT(i.id), 0)) * 100, 2) as success_rate,
                AVG(i.installation_time_minutes) as avg_installation_time,
                MAX(i.completed_at) as last_successful_installation
            FROM installations i
            WHERE i.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getArchitectureOverview() {
        return $this->db->querySingle("
            SELECT * FROM documentation_master
            WHERE company_id = ? AND doc_type = 'developer' AND category = 'architecture'
            ORDER BY last_updated DESC
            LIMIT 1
        ", [$this->user['company_id']]);
    }

    private function getCodingStandards() {
        return $this->db->querySingle("
            SELECT * FROM documentation_master
            WHERE company_id = ? AND doc_type = 'developer' AND category = 'coding_standards'
            ORDER BY last_updated DESC
            LIMIT 1
        ", [$this->user['company_id']]);
    }

    private function getAPIReference() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                ar.language,
                ar.framework,
                ar.code_examples
            FROM documentation_master dm
            JOIN api_reference ar ON dm.id = ar.doc_id
            WHERE dm.company_id = ?
            ORDER BY ar.language ASC
        ", [$this->user['company_id']]);
    }

    private function getModuleDevelopment() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                md.module_type,
                md.prerequisites,
                md.learning_curve
            FROM documentation_master dm
            JOIN module_development md ON dm.id = md.doc_id
            WHERE dm.company_id = ?
            ORDER BY md.learning_curve ASC
        ", [$this->user['company_id']]);
    }

    private function getTestingGuide() {
        return $this->db->querySingle("
            SELECT * FROM documentation_master
            WHERE company_id = ? AND doc_type = 'developer' AND category = 'testing'
            ORDER BY last_updated DESC
            LIMIT 1
        ", [$this->user['company_id']]);
    }

    private function getDeploymentGuide() {
        return $this->db->querySingle("
            SELECT * FROM documentation_master
            WHERE company_id = ? AND doc_type = 'developer' AND category = 'deployment'
            ORDER BY last_updated DESC
            LIMIT 1
        ", [$this->user['company_id']]);
    }

    private function getContributingGuide() {
        return $this->db->querySingle("
            SELECT * FROM documentation_master
            WHERE company_id = ? AND doc_type = 'developer' AND category = 'contributing'
            ORDER BY last_updated DESC
            LIMIT 1
        ", [$this->user['company_id']]);
    }

    private function getVideoCategories() {
        return [
            'getting_started' => [
                'name' => 'Getting Started',
                'description' => 'Introduction and basic setup',
                'videos' => []
            ],
            'tutorials' => [
                'name' => 'Tutorials',
                'description' => 'Step-by-step tutorials',
                'videos' => []
            ],
            'best_practices' => [
                'name' => 'Best Practices',
                'description' => 'Tips and best practices',
                'videos' => []
            ],
            'advanced' => [
                'name' => 'Advanced Topics',
                'description' => 'Advanced features and techniques',
                'videos' => []
            ],
            'webinars' => [
                'name' => 'Webinars',
                'description' => 'Recorded webinars and presentations',
                'videos' => []
            ]
        ];
    }

    private function getFeaturedVideos() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                vt.duration_minutes,
                vt.thumbnail_url,
                vt.view_count
            FROM documentation_master dm
            JOIN video_tutorials vt ON dm.id = vt.doc_id
            WHERE dm.company_id = ? AND dm.doc_type = 'video' AND dm.featured = true
            ORDER BY vt.view_count DESC
            LIMIT 6
        ", [$this->user['company_id']]);
    }

    private function getTutorialSeries() {
        return $this->db->query("
            SELECT
                ts.*,
                ts.series_name,
                ts.description,
                COUNT(vt.id) as video_count,
                SUM(vt.duration_minutes) as total_duration,
                MAX(vt.created_at) as last_updated
            FROM tutorial_series ts
            LEFT JOIN video_tutorials vt ON ts.id = vt.series_id
            WHERE ts.company_id = ?
            GROUP BY ts.id
            ORDER BY ts.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getQuickTips() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                vt.duration_minutes,
                vt.thumbnail_url
            FROM documentation_master dm
            JOIN video_tutorials vt ON dm.id = vt.doc_id
            WHERE dm.company_id = ? AND dm.doc_type = 'video' AND vt.duration_minutes <= 5
            ORDER BY dm.view_count DESC
        ", [$this->user['company_id']]);
    }

    private function getWebinars() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.description,
                vt.duration_minutes,
                vt.webinar_date,
                vt.presenter,
                vt.registration_url
            FROM documentation_master dm
            JOIN video_tutorials vt ON dm.id = vt.doc_id
            WHERE dm.company_id = ? AND dm.doc_type = 'video' AND vt.is_webinar = true
            ORDER BY vt.webinar_date DESC
        ", [$this->user['company_id']]);
    }

    private function getVideoAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT vt.id) as total_videos,
                SUM(vt.view_count) as total_views,
                AVG(vt.view_count) as avg_views_per_video,
                SUM(vt.duration_minutes) as total_watch_time,
                AVG(vt.rating) as avg_rating,
                COUNT(CASE WHEN vt.view_count > 1000 THEN 1 END) as popular_videos,
                MAX(vt.created_at) as last_video_uploaded
            FROM video_tutorials vt
            WHERE vt.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getVideoFeedback() {
        return $this->db->query("
            SELECT
                vf.*,
                vf.feedback_type,
                vf.feedback_text,
                vf.rating,
                vf.created_at,
                u.first_name,
                u.last_name,
                dm.title as video_title
            FROM video_feedback vf
            LEFT JOIN users u ON vf.user_id = u.id
            LEFT JOIN documentation_master dm ON vf.video_id = dm.id
            WHERE vf.company_id = ?
            ORDER BY vf.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function performSearch($query, $category, $type) {
        if (empty($query)) {
            return [];
        }

        $sql = "
            SELECT
                dm.*,
                dm.title,
                dm.description,
                dm.doc_type,
                dm.category,
                dm.view_count,
                dm.last_updated,
                MATCH(dm.title, dm.description, dm.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
            FROM documentation_master dm
            WHERE dm.company_id = ? AND MATCH(dm.title, dm.description, dm.content) AGAINST(? IN NATURAL LANGUAGE MODE)
        ";

        $params = [$query, $this->user['company_id'], $query];

        if (!empty($category)) {
            $sql .= " AND dm.category = ?";
            $params[] = $category;
        }

        if (!empty($type)) {
            $sql .= " AND dm.doc_type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY relevance_score DESC LIMIT 50";

        return $this->db->query($sql, $params);
    }

    private function getSearchFilters() {
        return [
            'categories' => $this->getManualCategories(),
            'types' => [
                'user_manual' => 'User Manuals',
                'api_doc' => 'API Documentation',
                'installation' => 'Installation Guides',
                'developer' => 'Developer Docs',
                'video' => 'Video Tutorials'
            ],
            'date_ranges' => [
                'today' => 'Today',
                'week' => 'This Week',
                'month' => 'This Month',
                'year' => 'This Year',
                'all' => 'All Time'
            ]
        ];
    }

    private function getPopularSearches() {
        return $this->db->query("
            SELECT
                ps.*,
                ps.search_query,
                ps.search_count,
                ps.last_searched
            FROM popular_searches ps
            WHERE ps.company_id = ?
            ORDER BY ps.search_count DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getSearchSuggestions($query) {
        if (strlen($query) < 3) {
            return [];
        }

        return $this->db->query("
            SELECT DISTINCT
                LEFT(dm.title, LOCATE(?, dm.title) + LENGTH(?)) as suggestion
            FROM documentation_master dm
            WHERE dm.company_id = ? AND dm.title LIKE ?
            ORDER BY dm.view_count DESC
            LIMIT 5
        ", [$query, $query, $this->user['company_id'], '%' . $query . '%']);
    }

    private function getSearchAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT s.id) as total_searches,
                COUNT(DISTINCT s.user_id) as unique_searchers,
                AVG(s.results_count) as avg_results_per_search,
                COUNT(CASE WHEN s.clicked_result IS NOT NULL THEN 1 END) as successful_searches,
                ROUND((COUNT(CASE WHEN s.clicked_result IS NOT NULL THEN 1 END) / NULLIF(COUNT(s.id), 0)) * 100, 2) as success_rate,
                MAX(s.created_at) as last_search
            FROM search_history s
            WHERE s.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getContentManagement() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.title,
                dm.doc_type,
                dm.status,
                dm.last_updated,
                u.first_name,
                u.last_name,
                COUNT(dr.id) as review_count,
                COUNT(CASE WHEN dr.status = 'approved' THEN 1 END) as approved_reviews
            FROM documentation_master dm
            LEFT JOIN users u ON dm.author_id = u.id
            LEFT JOIN documentation_reviews dr ON dm.id = dr.doc_id
            WHERE dm.company_id = ?
            GROUP BY dm.id
            ORDER BY dm.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getVersionControl() {
        return $this->db->query("
            SELECT
                dv.*,
                dv.version_number,
                dv.change_description,
                dv.created_at,
                u.first_name,
                u.last_name,
                COUNT(dvc.id) as change_count
            FROM documentation_versions dv
            LEFT JOIN users u ON dv.author_id = u.id
            LEFT JOIN documentation_version_changes dvc ON dv.id = dvc.version_id
            WHERE dv.company_id = ?
            GROUP BY dv.id
            ORDER BY dv.version_number DESC
        ", [$this->user['company_id']]);
    }

    private function getTranslationManagement() {
        return $this->db->query("
            SELECT
                dt.*,
                dt.language_code,
                dt.language_name,
                COUNT(dm.id) as documents_translated,
                COUNT(CASE WHEN dm.translation_status = 'completed' THEN 1 END) as completed_translations,
                ROUND((COUNT(CASE WHEN dm.translation_status = 'completed' THEN 1 END) / NULLIF(COUNT(dm.id), 0)) * 100, 2) as completion_percentage,
                MAX(dm.last_updated) as last_translation_update
            FROM documentation_translations dt
            LEFT JOIN documentation_master dm ON dt.id = dm.translation_id
            WHERE dt.company_id = ?
            GROUP BY dt.id
            ORDER BY dt.language_name ASC
        ", [$this->user['company_id']]);
    }

    private function getReviewWorkflow() {
        return $this->db->query("
            SELECT
                dr.*,
                dr.review_status,
                dr.reviewer_comments,
                dr.created_at,
                u1.first_name as reviewer_name,
                u2.first_name as author_name,
                dm.title as document_title
            FROM documentation_reviews dr
            LEFT JOIN users u1 ON dr.reviewer_id = u1.id
            LEFT JOIN users u2 ON dr.author_id = u2.id
            LEFT JOIN documentation_master dm ON dr.doc_id = dm.id
            WHERE dr.company_id = ?
            ORDER BY dr.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPublishingTools() {
        return [
            'content_editor' => [
                'name' => 'Content Editor',
                'description' => 'Rich text editor for documentation',
                'features' => ['WYSIWYG editing', 'Image upload', 'Code syntax highlighting']
            ],
            'version_manager' => [
                'name' => 'Version Manager',
                'description' => 'Manage document versions and changes',
                'features' => ['Version history', 'Diff viewer', 'Rollback capability']
            ],
            'publishing_workflow' => [
                'name' => 'Publishing Workflow',
                'description' => 'Review and approval workflow',
                'features' => ['Review requests', 'Approval process', 'Publishing schedule']
            ],
            'analytics_dashboard' => [
                'name' => 'Analytics Dashboard',
                'description' => 'Track documentation usage and effectiveness',
                'features' => ['View tracking', 'Search analytics', 'User feedback']
            ]
        ];
    }

    private function getAnalyticsDashboard() {
        return $this->db->querySingle("
            SELECT
                SUM(dm.view_count) as total_views,
                COUNT(DISTINCT dm.id) as total_documents,
                AVG(dm.rating) as avg_rating,
                COUNT(DISTINCT CASE WHEN dm.last_updated >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN dm.id END) as recently_updated,
                COUNT(DISTINCT s.user_id) as active_searchers,
                COUNT(DISTINCT vf.user_id) as feedback_providers
            FROM documentation_master dm
            LEFT JOIN search_history s ON s.company_id = dm.company_id
            LEFT JOIN video_feedback vf ON vf.company_id = dm.company_id
            WHERE dm.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getQualityAssurance() {
        return $this->db->query("
            SELECT
                qa.*,
                qa.check_type,
                qa.status,
                qa.issues_found,
                qa.created_at,
                u.first_name,
                u.last_name
            FROM quality_assurance qa
            LEFT JOIN users u ON qa.performed_by = u.id
            WHERE qa.company_id = ?
            ORDER BY qa.created_at DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function getDocument() {
        $docId = $_GET['id'] ?? null;

        if (!$docId) {
            $this->jsonResponse(['error' => 'Document ID required'], 400);
        }

        $document = $this->db->querySingle("
            SELECT
                dm.*,
                dm.title,
                dm.content,
                dm.doc_type,
                dm.category,
                dm.view_count,
                dm.last_updated,
                u.first_name,
                u.last_name
            FROM documentation_master dm
            LEFT JOIN users u ON dm.author_id = u.id
            WHERE dm.id = ? AND dm.company_id = ?
        ", [$docId, $this->user['company_id']]);

        if (!$document) {
            $this->jsonResponse(['error' => 'Document not found'], 404);
        }

        // Increment view count
        $this->db->update('documentation_master', [
            'view_count' => $document['view_count'] + 1
        ], 'id = ?', [$docId]);

        $this->jsonResponse([
            'success' => true,
            'document' => $document
        ]);
    }

    public function rateDocument() {
        $data = $this->validateRequest([
            'document_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5'
        ]);

        try {
            // Check if user already rated this document
            $existing = $this->db->querySingle("
                SELECT id FROM document_ratings
                WHERE document_id = ? AND user_id = ? AND company_id = ?
            ", [$data['document_id'], $this->user['id'], $this->user['company_id']]);

            if ($existing) {
                $this->db->update('document_ratings', [
                    'rating' => $data['rating'],
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$existing['id']]);
            } else {
                $this->db->insert('document_ratings', [
                    'company_id' => $this->user['company_id'],
                    'document_id' => $data['document_id'],
                    'user_id' => $this->user['id'],
                    'rating' => $data['rating']
                ]);
            }

            // Update document average rating
            $this->updateDocumentRating($data['document_id']);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Rating submitted successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function updateDocumentRating($documentId) {
        $avgRating = $this->db->querySingle("
            SELECT AVG(rating) as avg_rating
            FROM document_ratings
            WHERE document_id = ?
        ", [$documentId]);

        $this->db->update('documentation_master', [
            'rating' => $avgRating['avg_rating'] ?? 0
        ], 'id = ?', [$documentId]);
    }

    public function submitFeedback() {
        $data = $this->validateRequest([
            'document_id' => 'required|integer',
            'feedback_type' => 'required|string',
            'feedback_text' => 'string',
            'rating' => 'integer'
        ]);

        try {
            $this->db->insert('manual_feedback', [
                'company_id' => $this->user['company_id'],
                'doc_id' => $data['document_id'],
                'user_id' => $this->user['id'],
                'feedback_type' => $data['feedback_type'],
                'feedback_text' => $data['feedback_text'] ?? '',
                'rating' => $data['rating'] ?? null
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Feedback submitted successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trackSearch() {
        $data = $this->validateRequest([
            'query' => 'required|string',
            'results_count' => 'integer',
            'clicked_result' => 'string'
        ]);

        try {
            $this->db->insert('search_history', [
                'company_id' => $this->user['company_id'],
                'user_id' => $this->user['id'],
                'search_query' => $data['query'],
                'results_count' => $data['results_count'] ?? 0,
                'clicked_result' => $data['clicked_result'] ?? null,
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Search tracked
