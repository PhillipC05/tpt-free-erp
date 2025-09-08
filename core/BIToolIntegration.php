<?php
/**
 * TPT Free ERP - External BI Tool Integration System
 * Provides connectivity with Tableau, Power BI, and other BI platforms
 */

class BIToolIntegration {
    private $db;
    private $user;
    private $supportedTools = ['tableau', 'powerbi', 'qlik', 'looker'];

    public function __construct() {
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Configure BI tool connection
     */
    public function configureTool($toolName, $config) {
        if (!in_array($toolName, $this->supportedTools)) {
            throw new Exception("Unsupported BI tool: {$toolName}");
        }

        // Validate configuration
        $this->validateToolConfig($toolName, $config);

        // Test connection
        $this->testConnection($toolName, $config);

        // Store configuration
        $this->storeToolConfig($toolName, $config);

        return [
            'status' => 'success',
            'tool' => $toolName,
            'configured_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Export data to BI tool
     */
    public function exportToTool($toolName, $dataSource, $options = []) {
        $config = $this->getToolConfig($toolName);
        if (!$config) {
            throw new Exception("BI tool {$toolName} not configured");
        }

        switch ($toolName) {
            case 'tableau':
                return $this->exportToTableau($config, $dataSource, $options);
            case 'powerbi':
                return $this->exportToPowerBI($config, $dataSource, $options);
            case 'qlik':
                return $this->exportToQlik($config, $dataSource, $options);
            case 'looker':
                return $this->exportToLooker($config, $dataSource, $options);
            default:
                throw new Exception("Export method not implemented for {$toolName}");
        }
    }

    /**
     * Import data from BI tool
     */
    public function importFromTool($toolName, $remoteDataSource, $options = []) {
        $config = $this->getToolConfig($toolName);
        if (!$config) {
            throw new Exception("BI tool {$toolName} not configured");
        }

        switch ($toolName) {
            case 'tableau':
                return $this->importFromTableau($config, $remoteDataSource, $options);
            case 'powerbi':
                return $this->importFromPowerBI($config, $remoteDataSource, $options);
            case 'qlik':
                return $this->importFromQlik($config, $remoteDataSource, $options);
            case 'looker':
                return $this->importFromLooker($config, $remoteDataSource, $options);
            default:
                throw new Exception("Import method not implemented for {$toolName}");
        }
    }

    /**
     * Create embedded dashboard
     */
    public function createEmbeddedDashboard($toolName, $dashboardConfig) {
        $config = $this->getToolConfig($toolName);
        if (!$config) {
            throw new Exception("BI tool {$toolName} not configured");
        }

        switch ($toolName) {
            case 'tableau':
                return $this->createTableauEmbedded($config, $dashboardConfig);
            case 'powerbi':
                return $this->createPowerBIEmbedded($config, $dashboardConfig);
            case 'qlik':
                return $this->createQlikEmbedded($config, $dashboardConfig);
            case 'looker':
                return $this->createLookerEmbedded($config, $dashboardConfig);
            default:
                throw new Exception("Embedded dashboard not supported for {$toolName}");
        }
    }

    /**
     * Sync data between systems
     */
    public function syncData($toolName, $direction, $dataSource, $options = []) {
        $config = $this->getToolConfig($toolName);
        if (!$config) {
            throw new Exception("BI tool {$toolName} not configured");
        }

        $syncId = $this->createSyncJob($toolName, $direction, $dataSource, $options);

        try {
            switch ($direction) {
                case 'to_bi':
                    $result = $this->syncToBITool($toolName, $config, $dataSource, $options);
                    break;
                case 'from_bi':
                    $result = $this->syncFromBITool($toolName, $config, $dataSource, $options);
                    break;
                case 'bidirectional':
                    $result = $this->syncBidirectional($toolName, $config, $dataSource, $options);
                    break;
                default:
                    throw new Exception("Invalid sync direction: {$direction}");
            }

            $this->updateSyncJob($syncId, 'completed', $result);
            return $result;

        } catch (Exception $e) {
            $this->updateSyncJob($syncId, 'failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Tableau-specific methods
     */
    private function exportToTableau($config, $dataSource, $options) {
        // Prepare data for Tableau
        $data = $this->prepareDataForTableau($dataSource, $options);

        // Create Tableau data extract or connect to Tableau Server
        $tableauConnection = $this->connectToTableau($config);

        // Upload data to Tableau
        $result = $tableauConnection->uploadData($data, $options);

        // Store export metadata
        $this->storeExportMetadata('tableau', $dataSource, $result);

        return [
            'status' => 'success',
            'tool' => 'tableau',
            'data_source' => $dataSource,
            'tableau_url' => $result['url'] ?? null,
            'exported_at' => date('Y-m-d H:i:s'),
            'record_count' => count($data)
        ];
    }

    private function importFromTableau($config, $remoteDataSource, $options) {
        $tableauConnection = $this->connectToTableau($config);

        // Query data from Tableau
        $data = $tableauConnection->queryData($remoteDataSource, $options);

        // Transform and store data locally
        $localDataSource = $this->storeImportedData($data, $options);

        return [
            'status' => 'success',
            'tool' => 'tableau',
            'remote_source' => $remoteDataSource,
            'local_source' => $localDataSource,
            'imported_at' => date('Y-m-d H:i:s'),
            'record_count' => count($data)
        ];
    }

    private function createTableauEmbedded($config, $dashboardConfig) {
        $tableauConnection = $this->connectToTableau($config);

        // Create embedded dashboard
        $embeddedConfig = $tableauConnection->createEmbeddedDashboard($dashboardConfig);

        return [
            'embed_type' => 'tableau',
            'embed_url' => $embeddedConfig['embed_url'],
            'dashboard_id' => $embeddedConfig['dashboard_id'],
            'token' => $embeddedConfig['token'],
            'expires_at' => $embeddedConfig['expires_at']
        ];
    }

    /**
     * Power BI-specific methods
     */
    private function exportToPowerBI($config, $dataSource, $options) {
        // Prepare data for Power BI
        $data = $this->prepareDataForPowerBI($dataSource, $options);

        // Connect to Power BI service
        $powerbiConnection = $this->connectToPowerBI($config);

        // Push data to Power BI dataset
        $result = $powerbiConnection->pushData($data, $options);

        // Store export metadata
        $this->storeExportMetadata('powerbi', $dataSource, $result);

        return [
            'status' => 'success',
            'tool' => 'powerbi',
            'data_source' => $dataSource,
            'dataset_id' => $result['dataset_id'] ?? null,
            'exported_at' => date('Y-m-d H:i:s'),
            'record_count' => count($data)
        ];
    }

    private function importFromPowerBI($config, $remoteDataSource, $options) {
        $powerbiConnection = $this->connectToPowerBI($config);

        // Query data from Power BI
        $data = $powerbiConnection->queryDataset($remoteDataSource, $options);

        // Transform and store data locally
        $localDataSource = $this->storeImportedData($data, $options);

        return [
            'status' => 'success',
            'tool' => 'powerbi',
            'remote_source' => $remoteDataSource,
            'local_source' => $localDataSource,
            'imported_at' => date('Y-m-d H:i:s'),
            'record_count' => count($data)
        ];
    }

    private function createPowerBIEmbedded($config, $dashboardConfig) {
        $powerbiConnection = $this->connectToPowerBI($config);

        // Create embedded report
        $embeddedConfig = $powerbiConnection->createEmbeddedReport($dashboardConfig);

        return [
            'embed_type' => 'powerbi',
            'embed_url' => $embeddedConfig['embed_url'],
            'report_id' => $embeddedConfig['report_id'],
            'access_token' => $embeddedConfig['access_token'],
            'expires_at' => $embeddedConfig['expires_at']
        ];
    }

    /**
     * Qlik-specific methods
     */
    private function exportToQlik($config, $dataSource, $options) {
        $data = $this->prepareDataForQlik($dataSource, $options);
        $qlikConnection = $this->connectToQlik($config);

        $result = $qlikConnection->uploadData($data, $options);
        $this->storeExportMetadata('qlik', $dataSource, $result);

        return [
            'status' => 'success',
            'tool' => 'qlik',
            'data_source' => $dataSource,
            'app_id' => $result['app_id'] ?? null,
            'exported_at' => date('Y-m-d H:i:s'),
            'record_count' => count($data)
        ];
    }

    private function importFromQlik($config, $remoteDataSource, $options) {
        $qlikConnection = $this->connectToQlik($config);
        $data = $qlikConnection->queryData($remoteDataSource, $options);
        $localDataSource = $this->storeImportedData($data, $options);

        return [
            'status' => 'success',
            'tool' => 'qlik',
            'remote_source' => $remoteDataSource,
            'local_source' => $localDataSource,
            'imported_at' => date('Y-m-d H:i:s'),
            'record_count' => count($data)
        ];
    }

    private function createQlikEmbedded($config, $dashboardConfig) {
        $qlikConnection = $this->connectToQlik($config);
        $embeddedConfig = $qlikConnection->createEmbeddedApp($dashboardConfig);

        return [
            'embed_type' => 'qlik',
            'embed_url' => $embeddedConfig['embed_url'],
            'app_id' => $embeddedConfig['app_id'],
            'web_integration_id' => $embeddedConfig['web_integration_id']
        ];
    }

    /**
     * Looker-specific methods
     */
    private function exportToLooker($config, $dataSource, $options) {
        $data = $this->prepareDataForLooker($dataSource, $options);
        $lookerConnection = $this->connectToLooker($config);

        $result = $lookerConnection->uploadData($data, $options);
        $this->storeExportMetadata('looker', $dataSource, $result);

        return [
            'status' => 'success',
            'tool' => 'looker',
            'data_source' => $dataSource,
            'project_id' => $result['project_id'] ?? null,
            'exported_at' => date('Y-m-d H:i:s'),
            'record_count' => count($data)
        ];
    }

    private function importFromLooker($config, $remoteDataSource, $options) {
        $lookerConnection = $this->connectToLooker($config);
        $data = $lookerConnection->queryData($remoteDataSource, $options);
        $localDataSource = $this->storeImportedData($data, $options);

        return [
            'status' => 'success',
            'tool' => 'looker',
            'remote_source' => $remoteDataSource,
            'local_source' => $localDataSource,
            'imported_at' => date('Y-m-d H:i:s'),
            'record_count' => count($data)
        ];
    }

    private function createLookerEmbedded($config, $dashboardConfig) {
        $lookerConnection = $this->connectToLooker($config);
        $embeddedConfig = $lookerConnection->createEmbeddedDashboard($dashboardConfig);

        return [
            'embed_type' => 'looker',
            'embed_url' => $embeddedConfig['embed_url'],
            'dashboard_id' => $embeddedConfig['dashboard_id'],
            'embed_token' => $embeddedConfig['embed_token']
        ];
    }

    /**
     * Data preparation methods
     */
    private function prepareDataForTableau($dataSource, $options) {
        // Transform data to Tableau-compatible format
        $data = $this->getDataSourceData($dataSource);

        // Apply any transformations
        if (isset($options['transformations'])) {
            $data = $this->applyTransformations($data, $options['transformations']);
        }

        return $data;
    }

    private function prepareDataForPowerBI($dataSource, $options) {
        // Transform data to Power BI-compatible format
        $data = $this->getDataSourceData($dataSource);

        // Power BI specific formatting
        foreach ($data as &$row) {
            // Ensure proper data types
            foreach ($row as $key => $value) {
                if (is_string($value) && strtotime($value)) {
                    $row[$key] = date('c', strtotime($value)); // ISO 8601 format
                }
            }
        }

        return $data;
    }

    private function prepareDataForQlik($dataSource, $options) {
        // Transform data to Qlik-compatible format
        $data = $this->getDataSourceData($dataSource);
        return $data;
    }

    private function prepareDataForLooker($dataSource, $options) {
        // Transform data to Looker-compatible format
        $data = $this->getDataSourceData($dataSource);
        return $data;
    }

    /**
     * Connection methods
     */
    private function connectToTableau($config) {
        // Implement Tableau connection logic
        return new TableauConnection($config);
    }

    private function connectToPowerBI($config) {
        // Implement Power BI connection logic
        return new PowerBIConnection($config);
    }

    private function connectToQlik($config) {
        // Implement Qlik connection logic
        return new QlikConnection($config);
    }

    private function connectToLooker($config) {
        // Implement Looker connection logic
        return new LookerConnection($config);
    }

    /**
     * Validation and utility methods
     */
    private function validateToolConfig($toolName, $config) {
        $requiredFields = $this->getRequiredConfigFields($toolName);

        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new Exception("Missing required configuration field: {$field}");
            }
        }
    }

    private function getRequiredConfigFields($toolName) {
        $fields = [
            'tableau' => ['server_url', 'site_id', 'username', 'password'],
            'powerbi' => ['client_id', 'client_secret', 'tenant_id', 'workspace_id'],
            'qlik' => ['server_url', 'app_id', 'api_key'],
            'looker' => ['base_url', 'client_id', 'client_secret']
        ];

        return $fields[$toolName] ?? [];
    }

    private function testConnection($toolName, $config) {
        try {
            switch ($toolName) {
                case 'tableau':
                    $connection = $this->connectToTableau($config);
                    $connection->testConnection();
                    break;
                case 'powerbi':
                    $connection = $this->connectToPowerBI($config);
                    $connection->testConnection();
                    break;
                case 'qlik':
                    $connection = $this->connectToQlik($config);
                    $connection->testConnection();
                    break;
                case 'looker':
                    $connection = $this->connectToLooker($config);
                    $connection->testConnection();
                    break;
            }
        } catch (Exception $e) {
            throw new Exception("Connection test failed for {$toolName}: " . $e->getMessage());
        }
    }

    private function getToolConfig($toolName) {
        return $this->db->querySingle("
            SELECT * FROM bi_tool_configs
            WHERE tool_name = ? AND company_id = ? AND is_active = true
        ", [$toolName, $this->user['company_id']]);
    }

    private function storeToolConfig($toolName, $config) {
        // Encrypt sensitive data
        $encryptedConfig = $this->encryptConfig($config);

        $this->db->query("
            INSERT INTO bi_tool_configs
            (tool_name, company_id, config_data, created_by, created_at)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            config_data = VALUES(config_data),
            updated_by = VALUES(created_by),
            updated_at = NOW()
        ", [
            $toolName,
            $this->user['company_id'],
            json_encode($encryptedConfig),
            $this->user['id'],
            date('Y-m-d H:i:s')
        ]);
    }

    private function encryptConfig($config) {
        $encryption = new Encryption();
        $encrypted = [];

        foreach ($config as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $encrypted[$key] = $encryption->encrypt($value);
            } else {
                $encrypted[$key] = $value;
            }
        }

        return $encrypted;
    }

    private function isSensitiveField($fieldName) {
        $sensitiveFields = ['password', 'secret', 'key', 'token'];
        return in_array(strtolower($fieldName), $sensitiveFields);
    }

    private function getDataSourceData($dataSource) {
        // Retrieve data from local data source
        return $this->db->query("SELECT * FROM {$dataSource} WHERE company_id = ?", [$this->user['company_id']]);
    }

    private function storeImportedData($data, $options) {
        // Create new data source table and import data
        $tableName = 'imported_' . time() . '_' . uniqid();

        // Create table structure
        $this->createImportedTable($tableName, $data);

        // Insert data
        $this->insertImportedData($tableName, $data);

        return $tableName;
    }

    private function createImportedTable($tableName, $data) {
        if (empty($data)) return;

        $columns = [];
        foreach (array_keys($data[0]) as $column) {
            $columns[] = "`{$column}` TEXT";
        }

        $sql = "CREATE TABLE `{$tableName}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `company_id` INT NOT NULL,
            `imported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            " . implode(', ', $columns) . ",
            INDEX `idx_company` (`company_id`)
        )";

        $this->db->query($sql);
    }

    private function insertImportedData($tableName, $data) {
        foreach ($data as $row) {
            $row['company_id'] = $this->user['company_id'];
            $this->db->insert($tableName, $row);
        }
    }

    private function storeExportMetadata($toolName, $dataSource, $result) {
        $this->db->query("
            INSERT INTO bi_export_history
            (tool_name, company_id, data_source, export_result, exported_by, exported_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $toolName,
            $this->user['company_id'],
            $dataSource,
            json_encode($result),
            $this->user['id'],
            date('Y-m-d H:i:s')
        ]);
    }

    private function createSyncJob($toolName, $direction, $dataSource, $options) {
        return $this->db->query("
            INSERT INTO bi_sync_jobs
            (tool_name, company_id, direction, data_source, options, status, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, 'running', ?, ?)
        ", [
            $toolName,
            $this->user['company_id'],
            $direction,
            $dataSource,
            json_encode($options),
            $this->user['id'],
            date('Y-m-d H:i:s')
        ]);
    }

    private function updateSyncJob($syncId, $status, $result) {
        $this->db->query("
            UPDATE bi_sync_jobs
            SET status = ?, result = ?, completed_at = ?
            WHERE id = ?
        ", [
            $status,
            json_encode($result),
            date('Y-m-d H:i:s'),
            $syncId
        ]);
    }

    private function syncToBITool($toolName, $config, $dataSource, $options) {
        return $this->exportToTool($toolName, $dataSource, $options);
    }

    private function syncFromBITool($toolName, $config, $dataSource, $options) {
        return $this->importFromTool($toolName, $dataSource, $options);
    }

    private function syncBidirectional($toolName, $config, $dataSource, $options) {
        // Sync data in both directions
        $toResult = $this->syncToBITool($toolName, $config, $dataSource, $options);
        $fromResult = $this->syncFromBITool($toolName, $config, $dataSource, $options);

        return [
            'to_bi' => $toResult,
            'from_bi' => $fromResult,
            'sync_completed_at' => date('Y-m-d H:i:s')
        ];
    }

    private function applyTransformations($data, $transformations) {
        // Apply data transformations
        foreach ($transformations as $transformation) {
            switch ($transformation['type']) {
                case 'filter':
                    $data = $this->applyFilter($data, $transformation);
                    break;
                case 'aggregate':
                    $data = $this->applyAggregation($data, $transformation);
                    break;
                case 'sort':
                    $data = $this->applySort($data, $transformation);
                    break;
            }
        }

        return $data;
    }

    private function applyFilter($data, $filter) {
        return array_filter($data, function($row) use ($filter) {
            $value = $row[$filter['field']] ?? null;
            return $this->matchesFilter($value, $filter);
        });
    }

    private function applyAggregation($data, $aggregation) {
        // Implement aggregation logic
        return $data; // Placeholder
    }

    private function applySort($data, $sort) {
        usort($data, function($a, $b) use ($sort) {
            $field = $sort['field'];
            $direction = $sort['direction'] ?? 'asc';

            if ($direction === 'desc') {
                return strcmp($b[$field], $a[$field]);
            } else {
                return strcmp($a[$field], $b[$field]);
            }
        });

        return $data;
    }

    private function matchesFilter($value, $filter) {
        switch ($filter['operator']) {
            case 'equals':
                return $value == $filter['value'];
            case 'contains':
                return stripos($value, $filter['value']) !== false;
            case 'greater_than':
                return $value > $filter['value'];
            case 'less_than':
                return $value < $filter['value'];
            default:
                return true;
        }
    }

    private function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
}

/**
 * BI Tool Connection Classes
 */
class TableauConnection {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function testConnection() {
        // Implement Tableau connection test
        return true;
    }

    public function uploadData($data, $options) {
        // Implement Tableau data upload
        return ['url' => 'https://tableau.example.com/workbook/123'];
    }

    public function queryData($dataSource, $options) {
        // Implement Tableau data query
        return [];
    }

    public function createEmbeddedDashboard($config) {
        // Implement Tableau embedded dashboard creation
        return [
            'embed_url' => 'https://tableau.example.com/embed/123',
            'dashboard_id' => 'dashboard_123',
            'token' => 'token_123',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ];
    }
}

class PowerBIConnection {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function testConnection() {
        // Implement Power BI connection test
        return true;
    }

    public function pushData($data, $options) {
        // Implement Power BI data push
        return ['dataset_id' => 'dataset_123'];
    }

    public function queryDataset($datasetId, $options) {
        // Implement Power BI dataset query
        return [];
    }

    public function createEmbeddedReport($config) {
        // Implement Power BI embedded report creation
        return [
            'embed_url' => 'https://app.powerbi.com/embed/123',
            'report_id' => 'report_123',
            'access_token' => 'token_123',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ];
    }
}

class QlikConnection {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function testConnection() {
        // Implement Qlik connection test
        return true;
    }

    public function uploadData($data, $options) {
        // Implement Qlik data upload
        return ['app_id' => 'app_123'];
    }

    public function queryData($appId, $options) {
        // Implement Qlik data query
        return [];
    }

    public function createEmbeddedApp($config) {
        // Implement Qlik embedded app creation
        return [
            'embed_url' => 'https://qlik.example.com/embed/123',
            'app_id' => 'app_123',
            'web_integration_id' => 'web_int_123'
        ];
    }
}

class LookerConnection {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function testConnection() {
        // Implement Looker connection test
        return true;
    }

    public function uploadData($data, $options) {
        // Implement Looker data upload
        return ['project_id' => 'project_123'];
    }

    public function queryData($projectId, $options) {
        // Implement Looker data query
        return [];
    }

    public function createEmbeddedDashboard($config) {
        // Implement Looker embedded dashboard creation
        return [
            'embed_url' => 'https://looker.example.com/embed/123',
            'dashboard_id' => 'dashboard_123',
            'embed_token' => 'token_123'
        ];
    }
}
