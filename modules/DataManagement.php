<?php
/**
 * TPT Free ERP - Data Management Module
 * Complete backup, import/export, retention, and data validation system
 */

class DataManagement extends BaseController {
    private $db;
    private $user;
    private $backupPath;
    private $exportPath;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->backupPath = '/backups';
        $this->exportPath = '/exports';
    }

    /**
     * Main data management dashboard
     */
    public function index() {
        $this->requirePermission('data.view');

        $data = [
            'title' => 'Data Management Dashboard',
            'backup_overview' => $this->getBackupOverview(),
            'export_overview' => $this->getExportOverview(),
            'retention_overview' => $this->getRetentionOverview(),
            'validation_overview' => $this->getValidationOverview(),
            'storage_usage' => $this->getStorageUsage(),
            'recent_activities' => $this->getRecentActivities(),
            'data_health' => $this->getDataHealth()
        ];

        $this->render('modules/data/dashboard', $data);
    }

    /**
     * Backup management
     */
    public function backup() {
        $this->requirePermission('data.backup.view');

        $data = [
            'title' => 'Backup Management',
            'backup_schedules' => $this->getBackupSchedules(),
            'backup_history' => $this->getBackupHistory(),
            'backup_destinations' => $this->getBackupDestinations(),
            'backup_settings' => $this->getBackupSettings(),
            'backup_statistics' => $this->getBackupStatistics(),
            'storage_providers' => $this->getStorageProviders()
        ];

        $this->render('modules/data/backup', $data);
    }

    /**
     * Data import/export
     */
    public function importExport() {
        $this->requirePermission('data.import_export.view');

        $data = [
            'title' => 'Import & Export',
            'import_templates' => $this->getImportTemplates(),
            'export_templates' => $this->getExportTemplates(),
            'import_history' => $this->getImportHistory(),
            'export_history' => $this->getExportHistory(),
            'data_mappings' => $this->getDataMappings(),
            'supported_formats' => $this->getSupportedFormats(),
            'bulk_operations' => $this->getBulkOperations()
        ];

        $this->render('modules/data/import_export', $data);
    }

    /**
     * Data retention policies
     */
    public function retention() {
        $this->requirePermission('data.retention.view');

        $data = [
            'title' => 'Data Retention Policies',
            'retention_policies' => $this->getRetentionPolicies(),
            'retention_schedules' => $this->getRetentionSchedules(),
            'data_categories' => $this->getDataCategories(),
            'compliance_requirements' => $this->getComplianceRequirements(),
            'retention_reports' => $this->getRetentionReports(),
            'archival_settings' => $this->getArchivalSettings(),
            'deletion_logs' => $this->getDeletionLogs()
        ];

        $this->render('modules/data/retention', $data);
    }

    /**
     * Data validation and quality
     */
    public function validation() {
        $this->requirePermission('data.validation.view');

        $data = [
            'title' => 'Data Validation & Quality',
            'validation_rules' => $this->getValidationRules(),
            'data_quality_metrics' => $this->getDataQualityMetrics(),
            'validation_reports' => $this->getValidationReports(),
            'data_profiling' => $this->getDataProfiling(),
            'anomaly_detection' => $this->getAnomalyDetection(),
            'data_cleansing' => $this->getDataCleansing(),
            'quality_dashboards' => $this->getQualityDashboards()
        ];

        $this->render('modules/data/validation', $data);
    }

    /**
     * Data migration tools
     */
    public function migration() {
        $this->requirePermission('data.migration.view');

        $data = [
            'title' => 'Data Migration Tools',
            'migration_projects' => $this->getMigrationProjects(),
            'migration_templates' => $this->getMigrationTemplates(),
            'migration_history' => $this->getMigrationHistory(),
            'source_systems' => $this->getSourceSystems(),
            'target_systems' => $this->getTargetSystems(),
            'migration_monitoring' => $this->getMigrationMonitoring(),
            'rollback_procedures' => $this->getRollbackProcedures()
        ];

        $this->render('modules/data/migration', $data);
    }

    /**
     * Data archiving
     */
    public function archiving() {
        $this->requirePermission('data.archiving.view');

        $data = [
            'title' => 'Data Archiving',
            'archive_policies' => $this->getArchivePolicies(),
            'archive_storage' => $this->getArchiveStorage(),
            'archive_retrieval' => $this->getArchiveRetrieval(),
            'archive_compliance' => $this->getArchiveCompliance(),
            'archive_monitoring' => $this->getArchiveMonitoring(),
            'archive_reports' => $this->getArchiveReports(),
            'archive_settings' => $this->getArchiveSettings()
        ];

        $this->render('modules/data/archiving', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getBackupOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_backups,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_backups,
                COUNT(CASE WHEN status = 'running' THEN 1 END) as running_backups,
                SUM(backup_size_mb) as total_backup_size,
                AVG(backup_duration_minutes) as avg_backup_time,
                MAX(created_at) as last_backup_date,
                COUNT(DISTINCT backup_type) as backup_types_used
            FROM backup_history
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getExportOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_exports,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_exports,
                COUNT(CASE WHEN status = 'running' THEN 1 END) as running_exports,
                SUM(export_size_mb) as total_export_size,
                COUNT(DISTINCT export_format) as formats_used,
                MAX(created_at) as last_export_date,
                COUNT(DISTINCT created_by) as active_users
            FROM export_history
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getRetentionOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_policies,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_policies,
                SUM(records_deleted) as total_records_deleted,
                SUM(storage_freed_mb) as total_storage_freed,
                COUNT(CASE WHEN last_execution >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as policies_executed_recently,
                MAX(last_execution) as last_execution_date
            FROM retention_policies
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getValidationOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_validations,
                COUNT(CASE WHEN status = 'passed' THEN 1 END) as passed_validations,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_validations,
                COUNT(CASE WHEN status = 'warning' THEN 1 END) as warning_validations,
                AVG(quality_score) as avg_quality_score,
                COUNT(DISTINCT table_name) as tables_validated,
                MAX(last_validation) as last_validation_date
            FROM data_validation_results
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getStorageUsage() {
        return $this->db->querySingle("
            SELECT
                SUM(backup_size_mb) as backup_storage_used,
                SUM(export_size_mb) as export_storage_used,
                SUM(archive_size_mb) as archive_storage_used,
                COUNT(*) as total_files,
                AVG(file_size_mb) as avg_file_size,
                MAX(created_at) as last_file_created,
                COUNT(DISTINCT storage_location) as storage_locations_used
            FROM data_storage_usage
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRecentActivities() {
        return $this->db->query("
            SELECT
                da.*,
                da.activity_type,
                da.description,
                da.status,
                da.created_at,
                u.first_name,
                u.last_name,
                TIMESTAMPDIFF(MINUTE, da.created_at, NOW()) as minutes_ago
            FROM data_activities da
            LEFT JOIN users u ON da.user_id = u.id
            WHERE da.company_id = ?
            ORDER BY da.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getDataHealth() {
        return $this->db->querySingle("
            SELECT
                AVG(data_quality_score) as overall_quality_score,
                COUNT(CASE WHEN data_integrity_status = 'valid' THEN 1 END) as valid_records,
                COUNT(CASE WHEN data_integrity_status = 'invalid' THEN 1 END) as invalid_records,
                COUNT(CASE WHEN data_integrity_status = 'warning' THEN 1 END) as warning_records,
                COUNT(DISTINCT table_name) as tables_monitored,
                MAX(last_health_check) as last_health_check,
                COUNT(CASE WHEN requires_attention = true THEN 1 END) as items_requiring_attention
            FROM data_health_monitoring
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBackupSchedules() {
        return $this->db->query("
            SELECT
                bs.*,
                bs.schedule_name,
                bs.backup_type,
                bs.frequency,
                bs.next_run,
                bs.last_run,
                bs.is_active,
                TIMESTAMPDIFF(DAY, NOW(), bs.next_run) as days_until_next
            FROM backup_schedules bs
            WHERE bs.company_id = ?
            ORDER BY bs.next_run ASC
        ", [$this->user['company_id']]);
    }

    private function getBackupHistory() {
        return $this->db->query("
            SELECT
                bh.*,
                bh.backup_type,
                bh.status,
                bh.backup_size_mb,
                bh.duration_minutes,
                bh.created_at,
                u.first_name,
                u.last_name,
                bh.storage_location,
                bh.compressed,
                bh.encrypted
            FROM backup_history bh
            LEFT JOIN users u ON bh.created_by = u.id
            WHERE bh.company_id = ?
            ORDER BY bh.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getBackupDestinations() {
        return $this->db->query("
            SELECT
                bd.*,
                bd.destination_name,
                bd.destination_type,
                bd.is_active,
                bd.last_used,
                bd.total_backups,
                bd.success_rate
            FROM backup_destinations bd
            WHERE bd.company_id = ?
            ORDER BY bd.is_active DESC, bd.last_used DESC
        ", [$this->user['company_id']]);
    }

    private function getBackupSettings() {
        return $this->db->querySingle("
            SELECT * FROM backup_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBackupStatistics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_backups,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_backups,
                ROUND(
                    (COUNT(CASE WHEN status = 'completed' THEN 1 END) / NULLIF(COUNT(*), 0)) * 100, 2
                ) as success_rate,
                AVG(backup_size_mb) as avg_backup_size,
                AVG(duration_minutes) as avg_duration,
                SUM(backup_size_mb) as total_size_backed_up,
                MAX(created_at) as last_successful_backup
            FROM backup_history
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-90 days'))
        ]);
    }

    private function getStorageProviders() {
        return [
            'local' => [
                'name' => 'Local Storage',
                'description' => 'Store backups locally on server',
                'cost' => 'Free',
                'reliability' => 'Medium'
            ],
            'aws_s3' => [
                'name' => 'AWS S3',
                'description' => 'Amazon S3 cloud storage',
                'cost' => 'Pay per GB',
                'reliability' => 'High'
            ],
            'azure_blob' => [
                'name' => 'Azure Blob Storage',
                'description' => 'Microsoft Azure cloud storage',
                'cost' => 'Pay per GB',
                'reliability' => 'High'
            ],
            'google_cloud' => [
                'name' => 'Google Cloud Storage',
                'description' => 'Google Cloud Storage',
                'cost' => 'Pay per GB',
                'reliability' => 'High'
            ]
        ];
    }

    private function getImportTemplates() {
        return $this->db->query("
            SELECT
                it.*,
                it.template_name,
                it.data_type,
                it.file_format,
                it.field_mappings,
                it.validation_rules,
                it.last_used,
                it.usage_count
            FROM import_templates it
            WHERE it.company_id = ?
            ORDER BY it.last_used DESC
        ", [$this->user['company_id']]);
    }

    private function getExportTemplates() {
        return $this->db->query("
            SELECT
                et.*,
                et.template_name,
                et.data_type,
                et.file_format,
                et.filters,
                et.last_used,
                et.usage_count
            FROM export_templates et
            WHERE et.company_id = ?
            ORDER BY et.last_used DESC
        ", [$this->user['company_id']]);
    }

    private function getImportHistory() {
        return $this->db->query("
            SELECT
                ih.*,
                ih.import_type,
                ih.status,
                ih.records_processed,
                ih.records_imported,
                ih.records_failed,
                ih.created_at,
                u.first_name,
                u.last_name,
                ih.file_name,
                ih.file_size_mb
            FROM import_history ih
            LEFT JOIN users u ON ih.created_by = u.id
            WHERE ih.company_id = ?
            ORDER BY ih.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getExportHistory() {
        return $this->db->query("
            SELECT
                eh.*,
                eh.export_type,
                eh.status,
                eh.records_exported,
                eh.file_size_mb,
                eh.created_at,
                u.first_name,
                u.last_name,
                eh.file_name,
                eh.download_count
            FROM export_history eh
            LEFT JOIN users u ON eh.created_by = u.id
            WHERE eh.company_id = ?
            ORDER BY eh.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getDataMappings() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.source_field,
                dm.target_field,
                dm.data_type,
                dm.transformation_rule,
                dm.is_required,
                dm.validation_rule
            FROM data_mappings dm
            WHERE dm.company_id = ?
            ORDER BY dm.data_type ASC, dm.source_field ASC
        ", [$this->user['company_id']]);
    }

    private function getSupportedFormats() {
        return [
            'csv' => [
                'name' => 'CSV',
                'description' => 'Comma-separated values',
                'extensions' => ['csv'],
                'supports_headers' => true,
                'max_rows' => 1000000
            ],
            'xlsx' => [
                'name' => 'Excel',
                'description' => 'Microsoft Excel spreadsheet',
                'extensions' => ['xlsx', 'xls'],
                'supports_headers' => true,
                'max_rows' => 1000000
            ],
            'json' => [
                'name' => 'JSON',
                'description' => 'JavaScript Object Notation',
                'extensions' => ['json'],
                'supports_headers' => false,
                'max_rows' => 100000
            ],
            'xml' => [
                'name' => 'XML',
                'description' => 'Extensible Markup Language',
                'extensions' => ['xml'],
                'supports_headers' => false,
                'max_rows' => 500000
            ],
            'sql' => [
                'name' => 'SQL',
                'description' => 'SQL dump file',
                'extensions' => ['sql'],
                'supports_headers' => false,
                'max_rows' => 'unlimited'
            ]
        ];
    }

    private function getBulkOperations() {
        return [
            'bulk_import' => 'Bulk Import',
            'bulk_export' => 'Bulk Export',
            'bulk_delete' => 'Bulk Delete',
            'bulk_update' => 'Bulk Update',
            'bulk_validate' => 'Bulk Validate',
            'bulk_archive' => 'Bulk Archive'
        ];
    }

    private function getRetentionPolicies() {
        return $this->db->query("
            SELECT
                rp.*,
                rp.policy_name,
                rp.data_type,
                rp.retention_period_days,
                rp.retention_action,
                rp.is_active,
                rp.last_execution,
                rp.records_affected,
                TIMESTAMPDIFF(DAY, NOW(), DATE_ADD(rp.last_execution, INTERVAL rp.retention_period_days DAY)) as days_until_next_execution
            FROM retention_policies rp
            WHERE rp.company_id = ?
            ORDER BY rp.data_type ASC
        ", [$this->user['company_id']]);
    }

    private function getRetentionSchedules() {
        return $this->db->query("
            SELECT
                rs.*,
                rs.schedule_name,
                rs.frequency,
                rs.next_run,
                rs.last_run,
                rs.is_active,
                TIMESTAMPDIFF(DAY, NOW(), rs.next_run) as days_until_next
            FROM retention_schedules rs
            WHERE rs.company_id = ?
            ORDER BY rs.next_run ASC
        ", [$this->user['company_id']]);
    }

    private function getDataCategories() {
        return [
            'user_data' => [
                'name' => 'User Data',
                'description' => 'User profiles, authentication data',
                'retention_period' => 2555, // 7 years
                'compliance_requirements' => ['GDPR', 'CCPA']
            ],
            'transaction_data' => [
                'name' => 'Transaction Data',
                'description' => 'Financial transactions, orders',
                'retention_period' => 2555, // 7 years
                'compliance_requirements' => ['SOX', 'PCI DSS']
            ],
            'audit_logs' => [
                'name' => 'Audit Logs',
                'description' => 'Security and access logs',
                'retention_period' => 2555, // 7 years
                'compliance_requirements' => ['GDPR', 'SOX']
            ],
            'communication_data' => [
                'name' => 'Communication Data',
                'description' => 'Emails, messages, support tickets',
                'retention_period' => 1095, // 3 years
                'compliance_requirements' => ['GDPR']
            ],
            'temporary_data' => [
                'name' => 'Temporary Data',
                'description' => 'Cache, temporary files, session data',
                'retention_period' => 90, // 90 days
                'compliance_requirements' => []
            ]
        ];
    }

    private function getComplianceRequirements() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.requirement_name,
                cr.regulation,
                cr.retention_period_required,
                cr.data_disposal_method,
                cr.audit_requirements,
                cr.is_mandatory
            FROM compliance_requirements cr
            WHERE cr.company_id = ?
            ORDER BY cr.regulation ASC
        ", [$this->user['company_id']]);
    }

    private function getRetentionReports() {
        return $this->db->query("
            SELECT
                rr.*,
                rr.report_name,
                rr.report_period,
                rr.records_deleted,
                rr.storage_freed_mb,
                rr.generated_date,
                rr.generated_by
            FROM retention_reports rr
            WHERE rr.company_id = ?
            ORDER BY rr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getArchivalSettings() {
        return $this->db->querySingle("
            SELECT * FROM archival_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getDeletionLogs() {
        return $this->db->query("
            SELECT
                dl.*,
                dl.deletion_type,
                dl.records_deleted,
                dl.table_name,
                dl.deletion_reason,
                dl.executed_at,
                u.first_name,
                u.last_name
            FROM deletion_logs dl
            LEFT JOIN users u ON dl.executed_by = u.id
            WHERE dl.company_id = ?
            ORDER BY dl.executed_at DESC
        ", [$this->user['company_id']]);
    }

    private function getValidationRules() {
        return $this->db->query("
            SELECT
                vr.*,
                vr.rule_name,
                vr.table_name,
                vr.column_name,
                vr.validation_type,
                vr.validation_rule,
                vr.error_message,
                vr.is_active,
                vr.last_executed
            FROM validation_rules vr
            WHERE vr.company_id = ?
            ORDER BY vr.table_name ASC, vr.column_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDataQualityMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_records,
                COUNT(CASE WHEN quality_score >= 90 THEN 1 END) as high_quality_records,
                COUNT(CASE WHEN quality_score >= 70 AND quality_score < 90 THEN 1 END) as medium_quality_records,
                COUNT(CASE WHEN quality_score < 70 THEN 1 END) as low_quality_records,
                AVG(quality_score) as avg_quality_score,
                COUNT(CASE WHEN has_duplicates = true THEN 1 END) as duplicate_records,
                COUNT(CASE WHEN has_missing_data = true THEN 1 END) as incomplete_records,
                COUNT(CASE WHEN has_invalid_format = true THEN 1 END) as invalid_format_records
            FROM data_quality_metrics
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getValidationReports() {
        return $this->db->query("
            SELECT
                vr.*,
                vr.report_name,
                vr.validation_date,
                vr.total_records,
                vr.passed_records,
                vr.failed_records,
                vr.warning_records,
                vr.generated_by
            FROM validation_reports vr
            WHERE vr.company_id = ?
            ORDER BY vr.validation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDataProfiling() {
        return $this->db->query("
            SELECT
                dp.*,
                dp.table_name,
                dp.column_name,
                dp.data_type,
                dp.total_records,
                dp.unique_values,
                dp.null_values,
                dp.min_value,
                dp.max_value,
                dp.avg_value,
                dp.last_profiled
            FROM data_profiling dp
            WHERE dp.company_id = ?
            ORDER BY dp.table_name ASC, dp.column_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAnomalyDetection() {
        return $this->db->query("
            SELECT
                ad.*,
                ad.anomaly_type,
                ad.table_name,
                ad.column_name,
                ad.expected_pattern,
                ad.actual_pattern,
                ad.severity,
                ad.detected_at,
                ad.records_affected
            FROM anomaly_detection ad
            WHERE ad.company_id = ?
            ORDER BY ad.detected_at DESC
        ", [$this->user['company_id']]);
    }

    private function getDataCleansing() {
        return $this->db->query("
            SELECT
                dc.*,
                dc.cleansing_type,
                dc.table_name,
                dc.records_processed,
                dc.records_cleaned,
                dc.executed_at,
                dc.executed_by
            FROM data_cleansing dc
            WHERE dc.company_id = ?
            ORDER BY dc.executed_at DESC
        ", [$this->user['company_id']]);
    }

    private function getQualityDashboards() {
        return $this->db->query("
            SELECT
                qd.*,
                qd.dashboard_name,
                qd.dashboard_type,
                qd.last_updated,
                qd.created_by,
                COUNT(qdw.widget_id) as widget_count
            FROM quality_dashboards qd
            LEFT JOIN quality_dashboard_widgets qdw ON qd.id = qdw.dashboard_id
            WHERE qd.company_id = ?
            GROUP BY qd.id
            ORDER BY qd.dashboard_name ASC
        ", [$this->user['company_id']]);
    }

    private function getMigrationProjects() {
        return $this->db->query("
            SELECT
                mp.*,
                mp.project_name,
                mp.source_system,
                mp.target_system,
                mp.status,
                mp.start_date,
                mp.completion_date,
                mp.records_migrated,
                mp.assigned_to
            FROM migration_projects mp
            WHERE mp.company_id = ?
            ORDER BY mp.start_date DESC
        ", [$this->user['company_id']]);
    }

    private function getMigrationTemplates() {
        return $this->db->query("
            SELECT
                mt.*,
                mt.template_name,
                mt.source_system,
                mt.target_system,
                mt.data_type,
                mt.last_used,
                mt.usage_count
            FROM migration_templates mt
            WHERE mt.company_id = ?
            ORDER BY mt.last_used DESC
        ", [$this->user['company_id']]);
    }

    private function getMigrationHistory() {
        return $this->db->query("
            SELECT
                mh.*,
                mh.migration_type,
                mh.status,
                mh.records_processed,
                mh.records_migrated,
                mh.records_failed,
                mh.executed_at,
                mh.executed_by
            FROM migration_history mh
            WHERE mh.company_id = ?
            ORDER BY mh.executed_at DESC
        ", [$this->user['company_id']]);
    }

    private function getSourceSystems() {
        return [
            'legacy_erp' => 'Legacy ERP System',
            'spreadsheet' => 'Excel/CSV Files',
            'quickbooks' => 'QuickBooks',
            'sage' => 'Sage Accounting',
            'peachtree' => 'Peachtree Accounting',
            'custom_db' => 'Custom Database',
            'api' => 'External API'
        ];
    }

    private function getTargetSystems() {
        return [
            'tpt_erp' => 'TPT Free ERP',
            'aws_s3' => 'AWS S3',
            'azure_blob' => 'Azure Blob Storage',
            'google_cloud' => 'Google Cloud Storage',
            'local_storage' => 'Local Storage'
        ];
    }

    private function getMigrationMonitoring() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_migrations,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_migrations,
                COUNT(CASE WHEN status = 'running' THEN 1 END) as running_migrations,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_migrations,
                SUM(records_migrated) as total_records_migrated,
                AVG(duration_minutes) as avg_migration_time,
                MAX(executed_at) as last_migration_date
            FROM migration_history
            WHERE company_id = ? AND executed_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getRollbackProcedures() {
        return $this->db->query("
            SELECT
                rp.*,
                rp.procedure_name,
                rp.migration_id,
                rp.rollback_steps,
                rp.estimated_time,
                rp.created_at,
                rp.last_tested
            FROM rollback_procedures rp
            WHERE rp.company_id = ?
            ORDER BY rp.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getArchivePolicies() {
        return $this->db->query("
            SELECT
                ap.*,
                ap.policy_name,
                ap.data_type,
                ap.archive_criteria,
                ap.retention_after_archive,
                ap.is_active,
                ap.last_execution
            FROM archive_policies ap
            WHERE ap.company_id = ?
            ORDER BY ap.data_type ASC
        ", [$this->user['company_id']]);
    }

    private function getArchiveStorage() {
        return $this->db->query("
            SELECT
                als.*,
                als.storage_name,
                als.storage_type,
                als.total_capacity_gb,
                als.used_capacity_gb,
                als.available_capacity_gb,
                als.is_active,
                als.last_accessed
            FROM archive_storage_locations als
            WHERE als.company_id = ?
            ORDER BY als.is_active DESC, als.available_capacity_gb DESC
        ", [$this->user['company_id']]);
    }

    private function getArchiveRetrieval() {
        return $this->db->query("
            SELECT
                ar.*,
                ar.archive_id,
                ar.retrieval_request_date,
                ar.retrieval_completion_date,
                ar.requested_by,
                ar.approved_by,
                ar.status,
                TIMESTAMPDIFF(HOUR, ar.retrieval_request_date, ar.retrieval_completion_date) as retrieval_time_hours
            FROM archive_retrievals ar
            WHERE ar.company_id = ?
            ORDER BY ar.retrieval_request_date DESC
        ", [$this->user['company_id']]);
    }

    private function getArchiveCompliance() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_archives,
                COUNT(CASE WHEN compliance_status = 'compliant' THEN 1 END) as compliant_archives,
                COUNT(CASE WHEN compliance_status = 'non_compliant' THEN 1 END) as non_compliant_archives,
                COUNT(CASE WHEN integrity_verified = true THEN 1 END) as verified_archives,
                AVG(retention_days) as avg_retention_period,
                MAX(last_compliance_check) as last_compliance_check,
                COUNT(CASE WHEN requires_action = true THEN 1 END) as archives_requiring_action
            FROM archive_compliance
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getArchiveMonitoring() {
        return $this->db->query("
            SELECT
                am.*,
                am.monitor_type,
                am.archive_location,
                am.metric_name,
                am.metric_value,
                am.threshold_value,
                am.status,
                am.last_checked
            FROM archive_monitoring am
            WHERE am.company_id = ?
            ORDER BY am.last_checked DESC
        ", [$this->user['company_id']]);
    }

    private function getArchiveReports() {
        return $this->db->query("
            SELECT
                ar.*,
                ar.report_name,
                ar.report_period,
                ar.total_archives,
                ar.storage_used_gb,
                ar.retrieval_requests,
                ar.generated_date
            FROM archive_reports ar
            WHERE ar.company_id = ?
            ORDER BY ar.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getArchiveSettings() {
        return $this->db->querySingle("
            SELECT * FROM archive_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function createBackup() {
        $this->requirePermission('data.backup.create');

        $data = $this->validateBackupData($_POST);

        if (!$data) {
            $this->jsonResponse(['error' => 'Invalid backup data'], 400);
        }

        try {
            // Create backup job
            $backupId = $this->db->insert('backup_jobs', [
                'company_id' => $this->user['company_id'],
                'backup_type' => $data['backup_type'],
                'backup_name' => $data['backup_name'],
                'description' => $data['description'],
                'include_tables' => json_encode($data['include_tables']),
                'exclude_tables' => json_encode($data['exclude_tables']),
                'storage_destination' => $data['storage_destination'],
                'compression_enabled' => $data['compression_enabled'],
                'encryption_enabled' => $data['encryption_enabled'],
                'status' => 'queued',
                'created_by' => $this->user['id']
            ]);

            // Queue backup job
            $this->queueBackupJob($backupId, $data);

            $this->jsonResponse([
                'success' => true,
                'backup_id' => $backupId,
                'message' => 'Backup job created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function validateBackupData($data) {
        if (empty($data['backup_type']) || empty($data['backup_name'])) {
            return false;
        }

        return [
            'backup_type' => $data['backup_type'],
            'backup_name' => $data['backup_name'],
            'description' => $data['description'] ?? '',
            'include_tables' => $data['include_tables'] ?? [],
            'exclude_tables' => $data['exclude_tables'] ?? [],
            'storage_destination' => $data['storage_destination'] ?? 'local',
            'compression_enabled' => isset($data['compression_enabled']),
            'encryption_enabled' => isset($data['encryption_enabled'])
        ];
    }

    private function queueBackupJob($backupId, $data) {
        // Implementation for queuing backup job
        // This would integrate with a job queue system
    }

    public function exportData() {
        $this->requirePermission('data.export.create');

        $data = $this->validateExportData($_POST);

        if (!$data) {
            $this->jsonResponse(['error' => 'Invalid export data'], 400);
        }

        try {
            // Create export job
            $exportId = $this->db->insert('export_jobs', [
                'company_id' => $this->user['company_id'],
                'export_type' => $data['export_type'],
                'export_name' => $data['export_name'],
                'description' => $data['description'],
                'data_filters' => json_encode($data['data_filters']),
                'export_format' => $data['export_format'],
                'include_headers' => $data['include_headers'],
                'compression_enabled' => $data['compression_enabled'],
                'status' => 'queued',
                'created_by' => $this->user['id']
            ]);

            // Queue export job
            $this->queueExportJob($exportId, $data);

            $this->jsonResponse([
                'success' => true,
                'export_id' => $exportId,
                'message' => 'Export job created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function validateExportData($data) {
        if (empty($data['export_type']) || empty($data['export_name']) || empty($data['export_format'])) {
            return false;
        }

        return [
            'export_type' => $data['export_type'],
            'export_name' => $data['export_name'],
            'description' => $data['description'] ?? '',
            'data_filters' => $data['data_filters'] ?? [],
            'export_format' => $data['export_format'],
            'include_headers' => isset($data['include_headers']),
            'compression_enabled' => isset($data['compression_enabled'])
        ];
    }

    private function queueExportJob($exportId, $data) {
        // Implementation for queuing export job
        // This would integrate with a job queue system
    }

    public function importData() {
        $this->requirePermission('data.import.create');

        if (empty($_FILES['import_file'])) {
            $this->jsonResponse(['error' => 'No file uploaded'], 400);
        }

        $file = $_FILES['import_file'];
        $data = $this->validateImportData($_POST);

        if (!$data) {
            $this->jsonResponse(['error' => 'Invalid import data'], 400);
        }

        try {
            // Validate file
            $this->validateImportFile($file);

            // Create import job
            $importId = $this->db->insert('import_jobs', [
                'company_id' => $this->user['company_id'],
                'import_type' => $data['import_type'],
                'import_name' => $data['import_name'],
                'description' => $data['description'],
                'file_name' => $file['name'],
                'file_path' => $this->moveUploadedFile($file),
                'file_size' => $file['size'],
                'data_mappings' => json_encode($data['data_mappings']),
                'validation_rules' => json_encode($data['validation_rules']),
                'status' => 'queued',
                'created_by' => $this->user['id']
            ]);

            // Queue import job
            $this->queueImportJob($importId, $data);

            $this->jsonResponse([
                'success' => true,
                'import_id' => $importId,
                'message' => 'Import job created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function validateImportData($data) {
        if (empty($data['import_type']) || empty($data['import_name'])) {
            return false;
        }

        return [
            'import_type' => $data['import_type'],
            'import_name' => $data['import_name'],
            'description' => $data['description'] ?? '',
            'data_mappings' => $data['data_mappings'] ?? [],
            'validation_rules' => $data['validation_rules'] ?? []
        ];
    }

    private function validateImportFile($file) {
        // Check file size
        if ($file['size'] > 100 * 1024 * 1024) { // 100MB limit
            throw new Exception('File size exceeds limit');
        }

        // Check file type
        $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type');
        }
    }

    private function moveUploadedFile($file) {
        $uploadDir = '/tmp/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid() . '_' . basename($file['name']);
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save uploaded file');
        }

        return $filepath;
    }

    private function queueImportJob($importId, $data) {
        // Implementation for queuing import job
        // This would integrate with a job queue system
    }

    public function validateData() {
        $this->requirePermission('data.validation.run');

        $data = $this->validateValidationData($_POST);

        if (!$data) {
            $this->jsonResponse(['error' => 'Invalid validation data'], 400);
        }

        try {
            // Create validation job
            $validationId = $this->db->insert('validation_jobs', [
                'company_id' => $this->user['company_id'],
                'validation_name' => $data['validation_name'],
                'description' => $data['description'],
                'target_tables' => json_encode($data['target_tables']),
                'validation_rules' => json_encode($data['validation_rules']),
                'sample_size' => $data['sample_size'],
                'status' => 'queued',
                'created_by' => $this->user['id']
            ]);

            // Queue validation job
            $this->queueValidationJob($validationId, $data);

            $this->jsonResponse([
                'success' => true,
                'validation_id' => $validationId,
                'message' => 'Validation job created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function validateValidationData($data) {
        if (empty($data['validation_name']) || empty($data['target_tables'])) {
            return false;
        }

        return [
            'validation_name' => $data['validation_name'],
            'description' => $data['description'] ?? '',
            'target_tables' => $data['target_tables'],
            'validation_rules' => $data['validation_rules'] ?? [],
            'sample_size' => $data['sample_size'] ?? 1000
        ];
    }

    private function queueValidationJob($validationId, $data) {
        // Implementation for queuing validation job
        // This would integrate with a job queue system
    }

    public function getJobStatus() {
        $jobId = $_GET['job_id'] ?? null;
        $jobType = $_GET['job_type'] ?? null;

        if (!$jobId || !$jobType) {
            $this->jsonResponse(['error' => 'Job ID and type required'], 400);
        }

        $tableMap = [
            'backup' => 'backup_jobs',
            'export' => 'export_jobs',
            'import' => 'import_jobs',
            'validation' => 'validation_jobs'
        ];

        if (!isset($tableMap[$jobType])) {
            $this->jsonResponse(['error' => 'Invalid job type'], 400);
        }

        $table = $tableMap[$jobType];
        $job = $this->db->querySingle("
            SELECT id, status, progress_percentage, error_message, updated_at
            FROM {$table}
            WHERE id = ? AND company_id = ?
        ", [$jobId, $this->user['company_id']]);

        if (!$job) {
            $this->jsonResponse(['error' => 'Job not found'], 404);
        }

        $this->jsonResponse([
            'success' => true,
            'job_id' => $jobId,
            'status' => $job['status'],
            'progress' => $job['progress_percentage'] ?? 0,
            'error_message' => $job['error_message'] ?? null,
            'updated_at' => $job['updated_at']
        ]);
    }
}
?>
