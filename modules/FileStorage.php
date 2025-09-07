<?php
/**
 * TPT Free ERP - File Storage Integration Module
 * Integration with cloud storage providers (Backblaze B2, Wasabi, AWS S3)
 */

class FileStorage extends BaseController {
    private $db;
    private $user;
    private $providers;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->providers = $this->getStorageProviders();
    }

    /**
     * Main file storage dashboard
     */
    public function index() {
        $this->requirePermission('filestorage.view');

        $data = [
            'title' => 'File Storage Dashboard',
            'storage_stats' => $this->getStorageStats(),
            'recent_uploads' => $this->getRecentUploads(),
            'storage_providers' => $this->getActiveProviders(),
            'file_categories' => $this->getFileCategories(),
            'usage_analytics' => $this->getUsageAnalytics()
        ];

        $this->render('modules/filestorage/dashboard', $data);
    }

    /**
     * File manager interface
     */
    public function files() {
        $this->requirePermission('filestorage.files.view');

        $filters = [
            'provider' => $_GET['provider'] ?? null,
            'category' => $_GET['category'] ?? null,
            'search' => $_GET['search'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];

        $files = $this->getFiles($filters);

        $data = [
            'title' => 'File Manager',
            'files' => $files,
            'filters' => $filters,
            'providers' => $this->getActiveProviders(),
            'categories' => $this->getFileCategories(),
            'file_summary' => $this->getFileSummary($filters)
        ];

        $this->render('modules/filestorage/files', $data);
    }

    /**
     * Upload files
     */
    public function upload() {
        $this->requirePermission('filestorage.upload');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processFileUpload();
        }

        $data = [
            'title' => 'Upload Files',
            'providers' => $this->getActiveProviders(),
            'categories' => $this->getFileCategories(),
            'upload_limits' => $this->getUploadLimits(),
            'accepted_types' => $this->getAcceptedFileTypes()
        ];

        $this->render('modules/filestorage/upload', $data);
    }

    /**
     * Storage provider management
     */
    public function providers() {
        $this->requirePermission('filestorage.providers.view');

        $data = [
            'title' => 'Storage Providers',
            'providers' => $this->getAllProviders(),
            'provider_stats' => $this->getProviderStats(),
            'available_providers' => $this->getAvailableProviderTypes(),
            'connection_tests' => $this->getConnectionTestResults()
        ];

        $this->render('modules/filestorage/providers', $data);
    }

    /**
     * CDN management
     */
    public function cdn() {
        $this->requirePermission('filestorage.cdn.view');

        $data = [
            'title' => 'CDN Management',
            'cdn_configs' => $this->getCDNConfigurations(),
            'cdn_stats' => $this->getCDNStats(),
            'cache_purge_history' => $this->getCachePurgeHistory(),
            'cdn_providers' => $this->getCDNProviders()
        ];

        $this->render('modules/filestorage/cdn', $data);
    }

    /**
     * File sharing and access control
     */
    public function sharing() {
        $this->requirePermission('filestorage.sharing.view');

        $data = [
            'title' => 'File Sharing',
            'shared_files' => $this->getSharedFiles(),
            'access_permissions' => $this->getAccessPermissions(),
            'share_links' => $this->getShareLinks(),
            'sharing_history' => $this->getSharingHistory()
        ];

        $this->render('modules/filestorage/sharing', $data);
    }

    /**
     * Backup and sync management
     */
    public function backup() {
        $this->requirePermission('filestorage.backup.view');

        $data = [
            'title' => 'Backup & Sync',
            'backup_jobs' => $this->getBackupJobs(),
            'sync_status' => $this->getSyncStatus(),
            'backup_history' => $this->getBackupHistory(),
            'sync_schedules' => $this->getSyncSchedules()
        ];

        $this->render('modules/filestorage/backup', $data);
    }

    /**
     * File analytics and reporting
     */
    public function analytics() {
        $this->requirePermission('filestorage.analytics.view');

        $data = [
            'title' => 'File Analytics',
            'usage_trends' => $this->getUsageTrends(),
            'file_type_distribution' => $this->getFileTypeDistribution(),
            'storage_costs' => $this->getStorageCosts(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'user_activity' => $this->getUserActivity()
        ];

        $this->render('modules/filestorage/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getStorageProviders() {
        return [
            'backblaze_b2' => [
                'name' => 'Backblaze B2',
                'class' => 'BackblazeB2Storage',
                'config_fields' => ['account_id', 'application_key', 'bucket_name']
            ],
            'wasabi' => [
                'name' => 'Wasabi',
                'class' => 'WasabiStorage',
                'config_fields' => ['access_key', 'secret_key', 'bucket_name', 'region']
            ],
            'aws_s3' => [
                'name' => 'AWS S3',
                'class' => 'AWSS3Storage',
                'config_fields' => ['access_key', 'secret_key', 'bucket_name', 'region']
            ],
            'local' => [
                'name' => 'Local Storage',
                'class' => 'LocalStorage',
                'config_fields' => ['base_path']
            ]
        ];
    }

    private function getStorageStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_files,
                SUM(file_size) as total_size,
                COUNT(DISTINCT provider) as active_providers,
                COUNT(DISTINCT category) as categories_used,
                MAX(uploaded_at) as last_upload,
                AVG(file_size) as avg_file_size
            FROM file_storage
            WHERE company_id = ? AND deleted_at IS NULL
        ", [$this->user['company_id']]);
    }

    private function getRecentUploads() {
        return $this->db->query("
            SELECT
                fs.*,
                u.first_name,
                u.last_name,
                sp.name as provider_name
            FROM file_storage fs
            LEFT JOIN users u ON fs.uploaded_by = u.id
            LEFT JOIN storage_providers sp ON fs.provider = sp.provider_type
            WHERE fs.company_id = ? AND fs.deleted_at IS NULL
            ORDER BY fs.uploaded_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getActiveProviders() {
        return $this->db->query("
            SELECT
                sp.*,
                COUNT(fs.id) as files_count,
                SUM(fs.file_size) as total_size,
                MAX(fs.uploaded_at) as last_used
            FROM storage_providers sp
            LEFT JOIN file_storage fs ON sp.provider_type = fs.provider
                AND fs.company_id = ? AND fs.deleted_at IS NULL
            WHERE sp.company_id = ? AND sp.is_active = true
            GROUP BY sp.id
            ORDER BY sp.created_at DESC
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getFileCategories() {
        return [
            'documents' => 'Documents',
            'images' => 'Images',
            'videos' => 'Videos',
            'audio' => 'Audio Files',
            'archives' => 'Archives',
            'backups' => 'Backups',
            'logs' => 'Logs',
            'temp' => 'Temporary Files'
        ];
    }

    private function getUsageAnalytics() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('month', uploaded_at) as month,
                COUNT(*) as uploads_count,
                SUM(file_size) as total_size,
                COUNT(DISTINCT uploaded_by) as active_users
            FROM file_storage
            WHERE company_id = ? AND uploaded_at >= ?
                AND deleted_at IS NULL
            GROUP BY DATE_TRUNC('month', uploaded_at)
            ORDER BY month DESC
            LIMIT 12
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 months'))
        ]);
    }

    private function getFiles($filters) {
        $where = ["fs.company_id = ? AND fs.deleted_at IS NULL"];
        $params = [$this->user['company_id']];

        if ($filters['provider']) {
            $where[] = "fs.provider = ?";
            $params[] = $filters['provider'];
        }

        if ($filters['category']) {
            $where[] = "fs.category = ?";
            $params[] = $filters['category'];
        }

        if ($filters['search']) {
            $where[] = "(fs.file_name LIKE ? OR fs.original_name LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        if ($filters['date_from']) {
            $where[] = "fs.uploaded_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "fs.uploaded_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                fs.*,
                u.first_name,
                u.last_name,
                sp.name as provider_name
            FROM file_storage fs
            LEFT JOIN users u ON fs.uploaded_by = u.id
            LEFT JOIN storage_providers sp ON fs.provider = sp.provider_type
            WHERE $whereClause
            ORDER BY fs.uploaded_at DESC
        ", $params);
    }

    private function getFileSummary($filters) {
        $where = ["company_id = ? AND deleted_at IS NULL"];
        $params = [$this->user['company_id']];

        if ($filters['provider']) {
            $where[] = "provider = ?";
            $params[] = $filters['provider'];
        }

        if ($filters['category']) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }

        if ($filters['date_from']) {
            $where[] = "uploaded_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "uploaded_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_files,
                SUM(file_size) as total_size,
                AVG(file_size) as avg_file_size,
                COUNT(DISTINCT provider) as providers_used,
                COUNT(DISTINCT category) as categories_used
            FROM file_storage
            WHERE $whereClause
        ", $params);
    }

    private function getUploadLimits() {
        return [
            'max_file_size' => 100 * 1024 * 1024, // 100MB
            'max_files_per_upload' => 10,
            'daily_upload_limit' => 1024 * 1024 * 1024, // 1GB per day
            'monthly_upload_limit' => 10 * 1024 * 1024 * 1024 // 10GB per month
        ];
    }

    private function getAcceptedFileTypes() {
        return [
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
            'videos' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
            'audio' => ['mp3', 'wav', 'ogg', 'aac', 'flac'],
            'archives' => ['zip', 'rar', '7z', 'tar', 'gz']
        ];
    }

    private function processFileUpload() {
        $this->requirePermission('filestorage.upload');

        try {
            $this->db->beginTransaction();

            $files = $_FILES['files'] ?? [];
            $provider = $_POST['provider'] ?? 'local';
            $category = $_POST['category'] ?? 'documents';
            $isPublic = isset($_POST['is_public']);

            if (empty($files)) {
                throw new Exception('No files uploaded');
            }

            $uploadedFiles = [];
            $errors = [];

            // Handle multiple files
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];

                    try {
                        $result = $this->uploadSingleFile($file, $provider, $category, $isPublic);
                        $uploadedFiles[] = $result;
                    } catch (Exception $e) {
                        $errors[] = [
                            'file' => $file['name'],
                            'error' => $e->getMessage()
                        ];
                    }
                }
            } else {
                // Single file
                try {
                    $result = $this->uploadSingleFile($files, $provider, $category, $isPublic);
                    $uploadedFiles[] = $result;
                } catch (Exception $e) {
                    $errors[] = [
                        'file' => $files['name'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'uploaded' => $uploadedFiles,
                'errors' => $errors,
                'message' => count($uploadedFiles) . ' files uploaded successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function uploadSingleFile($file, $provider, $category, $isPublic) {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = $this->generateUniqueFilename($file['name']);

        // Upload to storage provider
        $storageResult = $this->uploadToProvider($provider, $file['tmp_name'], $uniqueName, $isPublic);

        // Save to database
        $fileId = $this->db->insert('file_storage', [
            'company_id' => $this->user['company_id'],
            'file_id' => $this->generateFileId(),
            'original_name' => $file['name'],
            'file_name' => $uniqueName,
            'file_path' => $storageResult['path'],
            'file_size' => $file['size'],
            'file_type' => $file['type'],
            'file_extension' => $extension,
            'category' => $category,
            'provider' => $provider,
            'is_public' => $isPublic,
            'public_url' => $storageResult['public_url'] ?? null,
            'uploaded_by' => $this->user['id'],
            'metadata' => json_encode($this->extractFileMetadata($file))
        ]);

        return [
            'id' => $fileId,
            'name' => $file['name'],
            'size' => $file['size'],
            'url' => $storageResult['public_url'] ?? null
        ];
    }

    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $this->getUploadErrorMessage($file['error']));
        }

        // Check file size
        $maxSize = $this->getUploadLimits()['max_file_size'];
        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds limit: ' . $this->formatBytes($maxSize));
        }

        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $acceptedTypes = array_merge(...array_values($this->getAcceptedFileTypes()));

        if (!in_array($extension, $acceptedTypes)) {
            throw new Exception('File type not allowed: ' . $extension);
        }

        // Check for malicious content
        if ($this->isMaliciousFile($file['tmp_name'])) {
            throw new Exception('File contains malicious content');
        }
    }

    private function generateUniqueFilename($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        // Clean basename
        $basename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $basename);

        do {
            $uniqueName = $basename . '_' . time() . '_' . rand(1000, 9999);
            if ($extension) {
                $uniqueName .= '.' . $extension;
            }
            $exists = $this->db->querySingle("
                SELECT id FROM file_storage
                WHERE file_name = ? AND company_id = ?
            ", [$uniqueName, $this->user['company_id']]);
        } while ($exists);

        return $uniqueName;
    }

    private function generateFileId() {
        do {
            $fileId = 'FS' . date('Ymd') . rand(100000, 999999);
            $exists = $this->db->querySingle("
                SELECT id FROM file_storage WHERE file_id = ?
            ", [$fileId]);
        } while ($exists);

        return $fileId;
    }

    private function uploadToProvider($provider, $tmpPath, $filename, $isPublic) {
        $providerConfig = $this->getProviderConfig($provider);

        switch ($provider) {
            case 'backblaze_b2':
                return $this->uploadToBackblazeB2($providerConfig, $tmpPath, $filename, $isPublic);
            case 'wasabi':
                return $this->uploadToWasabi($providerConfig, $tmpPath, $filename, $isPublic);
            case 'aws_s3':
                return $this->uploadToAWSS3($providerConfig, $tmpPath, $filename, $isPublic);
            case 'local':
            default:
                return $this->uploadToLocal($providerConfig, $tmpPath, $filename, $isPublic);
        }
    }

    private function getProviderConfig($provider) {
        return $this->db->querySingle("
            SELECT * FROM storage_providers
            WHERE company_id = ? AND provider_type = ? AND is_active = true
        ", [$this->user['company_id'], $provider]);
    }

    private function extractFileMetadata($file) {
        $metadata = [
            'uploaded_at' => date('c'),
            'uploaded_by' => $this->user['id'],
            'original_name' => $file['name'],
            'mime_type' => $file['type'],
            'size_bytes' => $file['size']
        ];

        // Extract additional metadata based on file type
        if (strpos($file['type'], 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
            }
        }

        return $metadata;
    }

    private function getUploadErrorMessage($errorCode) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        return $messages[$errorCode] ?? 'Unknown upload error';
    }

    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function isMaliciousFile($filePath) {
        // Basic security check - you might want to implement more sophisticated checks
        $content = file_get_contents($filePath, false, null, 0, 1024);

        // Check for common malicious patterns
        $maliciousPatterns = [
            '<?php',
            '<script',
            'javascript:',
            'vbscript:',
            'onload=',
            'onerror='
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function getAllProviders() {
        return $this->db->query("
            SELECT
                sp.*,
                COUNT(fs.id) as files_count,
                SUM(fs.file_size) as total_size,
                MAX(fs.uploaded_at) as last_used
            FROM storage_providers sp
            LEFT JOIN file_storage fs ON sp.provider_type = fs.provider
                AND fs.company_id = ? AND fs.deleted_at IS NULL
            WHERE sp.company_id = ?
            GROUP BY sp.id
            ORDER BY sp.created_at DESC
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getProviderStats() {
        return $this->db->query("
            SELECT
                provider,
                COUNT(*) as files_count,
                SUM(file_size) as total_size,
                AVG(file_size) as avg_file_size,
                COUNT(CASE WHEN is_public = true THEN 1 END) as public_files,
                COUNT(CASE WHEN is_public = false THEN 1 END) as private_files
            FROM file_storage
            WHERE company_id = ? AND deleted_at IS NULL
            GROUP BY provider
            ORDER BY total_size DESC
        ", [$this->user['company_id']]);
    }

    private function getAvailableProviderTypes() {
        return [
            'backblaze_b2' => [
                'name' => 'Backblaze B2',
                'description' => 'Cost-effective cloud storage with S3-compatible API',
                'features' => ['S3-compatible', 'Low cost', 'Fast uploads']
            ],
            'wasabi' => [
                'name' => 'Wasabi',
                'description' => 'Enterprise-grade cloud storage with high performance',
                'features' => ['High performance', '99.9% uptime', 'S3-compatible']
            ],
            'aws_s3' => [
                'name' => 'AWS S3',
                'description' => 'Amazon\'s scalable storage service',
                'features' => ['Highly scalable', 'Global CDN', 'Advanced features']
            ],
            'local' => [
                'name' => 'Local Storage',
                'description' => 'Store files on local server',
                'features' => ['Fast access', 'No external costs', 'Full control']
            ]
        ];
    }

    private function getConnectionTestResults() {
        return $this->db->query("
            SELECT
                sp.name,
                sp.provider_type,
                sp.last_tested_at,
                sp.test_result,
                sp.test_error
            FROM storage_providers sp
            WHERE sp.company_id = ?
            ORDER BY sp.last_tested_at DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // STORAGE PROVIDER IMPLEMENTATIONS
    // ============================================================================

    private function uploadToBackblazeB2($config, $tmpPath, $filename, $isPublic) {
        // Implementation for Backblaze B2 upload
        // This would use the Backblaze B2 API
        return [
            'path' => 'b2://' . $config['bucket_name'] . '/' . $filename,
            'public_url' => $isPublic ? 'https://' . $config['bucket_name'] . '.s3.us-west-002.backblazeb2.com/' . $filename : null
        ];
    }

    private function uploadToWasabi($config, $tmpPath, $filename, $isPublic) {
        // Implementation for Wasabi upload
        // This would use the Wasabi S3-compatible API
        return [
            'path' => 'wasabi://' . $config['bucket_name'] . '/' . $filename,
            'public_url' => $isPublic ? 'https://' . $config['bucket_name'] . '.s3.' . $config['region'] . '.wasabisys.com/' . $filename : null
        ];
    }

    private function uploadToAWSS3($config, $tmpPath, $filename, $isPublic) {
        // Implementation for AWS S3 upload
        // This would use the AWS SDK
        return [
            'path' => 's3://' . $config['bucket_name'] . '/' . $filename,
            'public_url' => $isPublic ? 'https://' . $config['bucket_name'] . '.s3.' . $config['region'] . '.amazonaws.com/' . $filename : null
        ];
    }

    private function uploadToLocal($config, $tmpPath, $filename, $isPublic) {
        $basePath = $config['base_path'] ?? '/uploads';
        $fullPath = $basePath . '/' . date('Y/m/d') . '/' . $filename;

        // Ensure directory exists
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Move file
        if (!move_uploaded_file($tmpPath, $fullPath)) {
            throw new Exception('Failed to save file locally');
        }

        return [
            'path' => $fullPath,
            'public_url' => $isPublic ? '/files/' . date('Y/m/d') . '/' . $filename : null
        ];
    }

    // ============================================================================
    // CDN MANAGEMENT METHODS
    // ============================================================================

    private function getCDNConfigurations() {
        return $this->db->query("
            SELECT * FROM cdn_configurations
            WHERE company_id = ?
            ORDER BY created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCDNStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_files,
                SUM(file_size) as total_size,
                COUNT(DISTINCT provider) as cdn_providers,
                AVG(cache_hit_ratio) as avg_cache_hit_ratio
            FROM cdn_files
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCachePurgeHistory() {
        return $this->db->query("
            SELECT * FROM cdn_cache_purges
            WHERE company_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getCDNProviders() {
        return [
            'cloudflare' => 'Cloudflare',
            'cloudfront' => 'AWS CloudFront',
            'fastly' => 'Fastly',
            'akamai' => 'Akamai',
            'stackpath' => 'StackPath'
        ];
    }

    // ============================================================================
    // FILE SHARING METHODS
    // ============================================================================

    private function getSharedFiles() {
        return $this->db->query("
            SELECT
                fs.*,
                u.first_name,
                u.last_name,
                sl.expires_at,
                sl.access_count,
                sl.max_accesses
            FROM file_storage fs
            JOIN share_links sl ON fs.id = sl.file_id
            LEFT JOIN users u ON fs.uploaded_by = u.id
            WHERE fs.company_id = ? AND fs.deleted_at IS NULL
                AND sl.expires_at > NOW()
            ORDER BY sl.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAccessPermissions() {
        return $this->db->query("
            SELECT
                fap.*,
                fs.file_name,
                fs.original_name,
                u.first_name,
                u.last_name
            FROM file_access_permissions fap
            JOIN file_storage fs ON fap.file_id = fs.id
            LEFT JOIN users u ON fap.user_id = u.id
            WHERE fs.company_id = ?
            ORDER BY fap.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getShareLinks() {
        return $this->db->query("
            SELECT
                sl.*,
                fs.file_name,
                fs.original_name,
                fs.file_size
            FROM share_links sl
            JOIN file_storage fs ON sl.file_id = fs.id
            WHERE fs.company_id = ?
            ORDER BY sl.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSharingHistory() {
        return $this->db->query("
            SELECT
                fsh.*,
                fs.file_name,
                fs.original_name,
                u.first_name,
                u.last_name
            FROM file_sharing_history fsh
            JOIN file_storage fs ON fsh.file_id = fs.id
            LEFT JOIN users u ON fsh.user_id = u.id
            WHERE fs.company_id = ?
            ORDER BY fsh.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // BACKUP AND SYNC METHODS
    // ============================================================================

    private function getBackupJobs() {
        return $this->db->query("
            SELECT
                bj.*,
                COUNT(bf.id) as files_count,
                SUM(bf.file_size) as total_size
            FROM backup_jobs bj
            LEFT JOIN backup_files bf ON bj.id = bf.backup_job_id
            WHERE bj.company_id = ?
            GROUP BY bj.id
            ORDER BY bj.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSyncStatus() {
        return $this->db->query("
            SELECT
                ss.*,
                sp.name as provider_name
            FROM sync_status ss
            JOIN storage_providers sp ON ss.provider = sp.provider_type
            WHERE ss.company_id = ?
            ORDER BY ss.last_sync DESC
        ", [$this->user['company_id']]);
    }

    private function getBackupHistory() {
        return $this->db->query("
            SELECT
                bh.*,
                bj.name as job_name,
                COUNT(bf.id) as files_count
            FROM backup_history bh
            JOIN backup_jobs bj ON bh.backup_job_id = bj.id
            LEFT JOIN backup_files bf ON bh.id = bf.backup_history_id
            WHERE bh.company_id = ?
            GROUP BY bh.id
            ORDER BY bh.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getSyncSchedules() {
        return $this->db->query("
            SELECT * FROM sync_schedules
            WHERE company_id = ?
            ORDER BY next_run ASC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // ANALYTICS METHODS
    // ============================================================================

    private function getUsageTrends() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('day', uploaded_at) as date,
                COUNT(*) as uploads,
                SUM(file_size) as total_size,
                COUNT(DISTINCT uploaded_by) as users
            FROM file_storage
            WHERE company_id = ? AND uploaded_at >= ?
                AND deleted_at IS NULL
            GROUP BY DATE_TRUNC('day', uploaded_at)
            ORDER BY date DESC
            LIMIT 30
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getFileTypeDistribution() {
        return $this->db->query("
            SELECT
                file_extension,
                COUNT(*) as count,
                SUM(file_size) as total_size,
                AVG(file_size) as avg_size
            FROM file_storage
            WHERE company_id = ? AND deleted_at IS NULL
            GROUP BY file_extension
            ORDER BY count DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getStorageCosts() {
        return $this->db->query("
            SELECT
                provider,
                COUNT(*) as files_count,
                SUM(file_size) as total_size,
                SUM(storage_cost) as total_cost,
                AVG(storage_cost) as avg_cost_per_file
            FROM file_storage
            WHERE company_id = ? AND deleted_at IS NULL
            GROUP BY provider
            ORDER BY total_cost DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceMetrics() {
        return $this->db->query("
            SELECT
                provider,
                COUNT(*) as total_uploads,
                AVG(upload_time_ms) as avg_upload_time,
                MIN(upload_time_ms) as min_upload_time,
                MAX(upload_time_ms) as max_upload_time,
                COUNT(CASE WHEN upload_time_ms > 5000 THEN 1 END) as slow_uploads
            FROM file_storage
            WHERE company_id = ? AND deleted_at IS NULL
            GROUP BY provider
            ORDER BY avg_upload_time ASC
        ", [$this->user['company_id']]);
    }

    private function getUserActivity() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                COUNT(fs.id) as uploads_count,
                SUM(fs.file_size) as total_size,
                MAX(fs.uploaded_at) as last_activity
            FROM users u
            LEFT JOIN file_storage fs ON u.id = fs.uploaded_by
                AND fs.company_id = ? AND fs.deleted_at IS NULL
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY uploads_count DESC
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function downloadFile() {
        $this->requirePermission('filestorage.download');

        $fileId = $_GET['id'] ?? null;

        if (!$fileId) {
            $this->jsonResponse(['success' => false, 'error' => 'File ID required'], 400);
        }

        $file = $this->db->querySingle("
            SELECT * FROM file_storage
            WHERE file_id = ? AND company_id = ?
        ", [$fileId, $this->user['company_id']]);

        if (!$file) {
            $this->jsonResponse(['success' => false, 'error' => 'File not found'], 404);
        }

        // Check permissions
        if (!$this->canAccessFile($file)) {
            $this->jsonResponse(['success' => false, 'error' => 'Access denied'], 403);
        }

        // Log download
        $this->logFileAccess($file['id'], 'download');

        // Stream file
        $this->streamFile($file);
    }

    public function deleteFile() {
        $this->requirePermission('filestorage.delete');

        $data = $this->validateRequest([
            'file_id' => 'required|string'
        ]);

        try {
            $file = $this->db->querySingle("
                SELECT * FROM file_storage
                WHERE file_id = ? AND company_id = ?
            ", [$data['file_id'], $this->user['company_id']]);

            if (!$file) {
                throw new Exception('File not found');
            }

            // Soft delete
            $this->db->update('file_storage', [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $this->user['id']
            ], 'id = ?', [$file['id']]);

            // Optionally delete from storage provider
            if ($file['provider'] !== 'local') {
                $this->deleteFromProvider($file['provider'], $file['file_path']);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createShareLink() {
        $this->requirePermission('filestorage.share');

        $data = $this->validateRequest([
            'file_id' => 'required|string',
            'expires_in_days' => 'integer|min:1|max:365',
            'max_accesses' => 'integer|min:1|max:1000'
        ]);

        try {
            $file = $this->db->querySingle("
                SELECT * FROM file_storage
                WHERE file_id = ? AND company_id = ?
            ", [$data['file_id'], $this->user['company_id']]);

            if (!$file) {
                throw new Exception('File not found');
            }

            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$data['expires_in_days']} days"));
            $shareToken = $this->generateShareToken();

            $shareId = $this->db->insert('share_links', [
                'company_id' => $this->user['company_id'],
                'file_id' => $file['id'],
                'share_token' => $shareToken,
                'expires_at' => $expiresAt,
                'max_accesses' => $data['max_accesses'],
                'created_by' => $this->user['id']
            ]);

            $shareUrl = $this->getShareUrl($shareToken);

            $this->jsonResponse([
                'success' => true,
                'share_url' => $shareUrl,
                'expires_at' => $expiresAt
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================

    private function canAccessFile($file) {
        // Check if user owns the file or has been granted access
        if ($file['uploaded_by'] === $this->user['id']) {
            return true;
        }

        $permission = $this->db->querySingle("
            SELECT * FROM file_access_permissions
            WHERE file_id = ? AND user_id = ? AND permission_type = 'read'
        ", [$file['id'], $this->user['id']]);

        return $permission !== null;
    }

    private function logFileAccess($fileId, $action) {
        $this->db->insert('file_access_log', [
            'company_id' => $this->user['company_id'],
            'file_id' => $fileId,
            'user_id' => $this->user['id'],
            'action' => $action,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    private function streamFile($file) {
        // Implementation for streaming file content
        // This would depend on the storage provider
        header('Content-Type: ' . $file['file_type']);
        header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
        header('Content-Length: ' . $file['file_size']);

        // Stream file content based on provider
        switch ($file['provider']) {
            case 'local':
                readfile($file['file_path']);
                break;
            // Add other provider implementations
        }

        exit;
    }

    private function deleteFromProvider($provider, $filePath) {
        // Implementation for deleting from storage provider
        // This would use the appropriate API for each provider
    }

    private function generateShareToken() {
        do {
            $token = bin2hex(random_bytes(16));
            $exists = $this->db->querySingle("
                SELECT id FROM share_links WHERE share_token = ?
            ", [$token]);
        } while ($exists);

        return $token;
    }

    private function getShareUrl($token) {
        return $_SERVER['HTTP_HOST'] . '/share/' . $token;
    }
}
?>
