<?php
/**
 * TPT Free ERP - IoT & Device Integration Module
 * Complete IoT device management, sensor data collection, and predictive maintenance
 */

class IoT extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main IoT dashboard
     */
    public function index() {
        $this->requirePermission('iot.view');

        $data = [
            'title' => 'IoT & Device Integration',
            'device_stats' => $this->getDeviceStats(),
            'sensor_readings' => $this->getLatestSensorReadings(),
            'alerts_summary' => $this->getAlertsSummary(),
            'predictive_insights' => $this->getPredictiveInsights(),
            'system_health' => $this->getSystemHealth()
        ];

        $this->render('modules/iot/dashboard', $data);
    }

    /**
     * Device management
     */
    public function devices() {
        $this->requirePermission('iot.devices.view');

        $filters = [
            'status' => $_GET['status'] ?? 'all',
            'type' => $_GET['type'] ?? null,
            'location' => $_GET['location'] ?? null,
            'category' => $_GET['category'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $devices = $this->getDevices($filters);

        $data = [
            'title' => 'Device Management',
            'devices' => $devices,
            'filters' => $filters,
            'device_types' => $this->getDeviceTypes(),
            'device_categories' => $this->getDeviceCategories(),
            'locations' => $this->getLocations(),
            'device_summary' => $this->getDeviceSummary($filters)
        ];

        $this->render('modules/iot/devices', $data);
    }

    /**
     * Register new device
     */
    public function registerDevice() {
        $this->requirePermission('iot.devices.create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processDeviceRegistration();
        }

        $data = [
            'title' => 'Register New Device',
            'device_types' => $this->getDeviceTypes(),
            'device_categories' => $this->getDeviceCategories(),
            'locations' => $this->getLocations(),
            'protocols' => $this->getSupportedProtocols(),
            'next_device_id' => $this->generateNextDeviceId()
        ];

        $this->render('modules/iot/register_device', $data);
    }

    /**
     * Sensor data management
     */
    public function sensors() {
        $this->requirePermission('iot.sensors.view');

        $data = [
            'title' => 'Sensor Data Management',
            'sensor_readings' => $this->getSensorReadings(),
            'sensor_types' => $this->getSensorTypes(),
            'data_quality' => $this->getDataQualityMetrics(),
            'data_trends' => $this->getDataTrends(),
            'anomaly_detection' => $this->getAnomalyDetection()
        ];

        $this->render('modules/iot/sensors', $data);
    }

    /**
     * Real-time monitoring
     */
    public function monitoring() {
        $this->requirePermission('iot.monitoring.view');

        $data = [
            'title' => 'Real-time Monitoring',
            'live_readings' => $this->getLiveReadings(),
            'active_alerts' => $this->getActiveAlerts(),
            'system_status' => $this->getSystemStatus(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'connectivity_status' => $this->getConnectivityStatus()
        ];

        $this->render('modules/iot/monitoring', $data);
    }

    /**
     * Predictive maintenance
     */
    public function predictiveMaintenance() {
        $this->requirePermission('iot.predictive.view');

        $data = [
            'title' => 'Predictive Maintenance',
            'maintenance_predictions' => $this->getMaintenancePredictions(),
            'failure_probability' => $this->getFailureProbability(),
            'maintenance_schedule' => $this->getPredictiveMaintenanceSchedule(),
            'cost_savings' => $this->getMaintenanceCostSavings(),
            'model_accuracy' => $this->getModelAccuracy()
        ];

        $this->render('modules/iot/predictive_maintenance', $data);
    }

    /**
     * Device security management
     */
    public function security() {
        $this->requirePermission('iot.security.view');

        $data = [
            'title' => 'Device Security',
            'security_events' => $this->getSecurityEvents(),
            'device_authentication' => $this->getDeviceAuthentication(),
            'firmware_updates' => $this->getFirmwareUpdates(),
            'access_control' => $this->getAccessControl(),
            'encryption_status' => $this->getEncryptionStatus()
        ];

        $this->render('modules/iot/security', $data);
    }

    /**
     * Edge computing management
     */
    public function edgeComputing() {
        $this->requirePermission('iot.edge.view');

        $data = [
            'title' => 'Edge Computing',
            'edge_nodes' => $this->getEdgeNodes(),
            'local_processing' => $this->getLocalProcessing(),
            'data_routing' => $this->getDataRouting(),
            'offline_capabilities' => $this->getOfflineCapabilities(),
            'edge_analytics' => $this->getEdgeAnalytics()
        ];

        $this->render('modules/iot/edge_computing', $data);
    }

    /**
     * Analytics and reporting
     */
    public function analytics() {
        $this->requirePermission('iot.analytics.view');

        $data = [
            'title' => 'IoT Analytics',
            'device_utilization' => $this->getDeviceUtilization(),
            'data_volume' => $this->getDataVolume(),
            'energy_consumption' => $this->getEnergyConsumption(),
            'roi_analysis' => $this->getIoTROI(),
            'performance_insights' => $this->getPerformanceInsights()
        ];

        $this->render('modules/iot/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getDeviceStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_devices,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_devices,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_devices,
                COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_devices,
                COUNT(CASE WHEN last_seen < DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as offline_devices,
                AVG(battery_level) as avg_battery_level,
                COUNT(DISTINCT device_type) as device_types_count
            FROM iot_devices
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getLatestSensorReadings() {
        return $this->db->query("
            SELECT
                sr.*,
                d.name as device_name,
                d.device_id as device_code,
                st.name as sensor_type_name,
                st.unit as measurement_unit
            FROM sensor_readings sr
            JOIN iot_devices d ON sr.device_id = d.id
            JOIN sensor_types st ON sr.sensor_type_id = st.id
            WHERE sr.company_id = ?
            ORDER BY sr.timestamp DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getAlertsSummary() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_alerts,
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_alerts,
                COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_alerts,
                COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_alerts,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_alerts,
                COUNT(CASE WHEN acknowledged_at IS NOT NULL THEN 1 END) as acknowledged_alerts
            FROM iot_alerts
            WHERE company_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ", [$this->user['company_id']]);
    }

    private function getPredictiveInsights() {
        return $this->db->query("
            SELECT
                pi.*,
                d.name as device_name,
                d.device_id as device_code,
                pt.name as prediction_type_name
            FROM predictive_insights pi
            JOIN iot_devices d ON pi.device_id = d.id
            JOIN prediction_types pt ON pi.prediction_type_id = pt.id
            WHERE pi.company_id = ? AND pi.confidence_score > 0.7
            ORDER BY pi.confidence_score DESC, pi.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getSystemHealth() {
        return $this->db->querySingle("
            SELECT
                AVG(CASE WHEN last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 100 ELSE 0 END) as connectivity_health,
                AVG(battery_level) as battery_health,
                COUNT(CASE WHEN status = 'active' THEN 1 END) * 100.0 / COUNT(*) as device_health,
                AVG(data_quality_score) as data_quality_health
            FROM iot_devices
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getDevices($filters) {
        $where = ["d.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status'] !== 'all') {
            $where[] = "d.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['type']) {
            $where[] = "d.device_type = ?";
            $params[] = $filters['type'];
        }

        if ($filters['location']) {
            $where[] = "d.location_id = ?";
            $params[] = $filters['location'];
        }

        if ($filters['category']) {
            $where[] = "d.category_id = ?";
            $params[] = $filters['category'];
        }

        if ($filters['search']) {
            $where[] = "(d.name LIKE ? OR d.device_id LIKE ? OR d.serial_number LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                d.*,
                dt.name as device_type_name,
                dc.name as category_name,
                l.name as location_name,
                COUNT(sr.id) as sensor_readings_count,
                MAX(sr.timestamp) as last_reading,
                AVG(sr.data_quality_score) as avg_data_quality
            FROM iot_devices d
            LEFT JOIN device_types dt ON d.device_type = dt.id
            LEFT JOIN device_categories dc ON d.category_id = dc.id
            LEFT JOIN locations l ON d.location_id = l.id
            LEFT JOIN sensor_readings sr ON d.id = sr.device_id
            WHERE $whereClause
            GROUP BY d.id, dt.name, dc.name, l.name
            ORDER BY d.created_at DESC
        ", $params);
    }

    private function getDeviceTypes() {
        return $this->db->query("
            SELECT * FROM device_types
            WHERE company_id = ?
            ORDER BY name ASC
        ", [$this->user['company_id']]);
    }

    private function getDeviceCategories() {
        return $this->db->query("
            SELECT * FROM device_categories
            WHERE company_id = ?
            ORDER BY name ASC
        ", [$this->user['company_id']]);
    }

    private function getLocations() {
        return $this->db->query("
            SELECT * FROM locations
            WHERE company_id = ?
            ORDER BY name ASC
        ", [$this->user['company_id']]);
    }

    private function getSupportedProtocols() {
        return [
            'mqtt' => 'MQTT',
            'http' => 'HTTP/HTTPS',
            'coap' => 'CoAP',
            'websocket' => 'WebSocket',
            'modbus' => 'Modbus',
            'opcua' => 'OPC UA',
            'custom' => 'Custom Protocol'
        ];
    }

    private function getDeviceSummary($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status'] !== 'all') {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['type']) {
            $where[] = "device_type = ?";
            $params[] = $filters['type'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_devices,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_devices,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_devices,
                COUNT(DISTINCT device_type) as device_types,
                COUNT(DISTINCT category_id) as categories,
                AVG(acquisition_cost) as avg_cost
            FROM iot_devices
            WHERE $whereClause
        ", $params);
    }

    private function generateNextDeviceId() {
        $lastDevice = $this->db->querySingle("
            SELECT device_id FROM iot_devices
            WHERE company_id = ? AND device_id LIKE 'IOT%'
            ORDER BY device_id DESC
            LIMIT 1
        ", [$this->user['company_id']]);

        if ($lastDevice) {
            $number = (int)substr($lastDevice['device_id'], 3) + 1;
            return 'IOT' . str_pad($number, 6, '0', STR_PAD_LEFT);
        }

        return 'IOT000001';
    }

    private function processDeviceRegistration() {
        $this->requirePermission('iot.devices.create');

        $data = $this->validateDeviceData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid device data');
            $this->redirect('/iot/register-device');
        }

        try {
            $this->db->beginTransaction();

            $deviceId = $this->db->insert('iot_devices', [
                'company_id' => $this->user['company_id'],
                'device_id' => $data['device_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'device_type' => $data['device_type'],
                'category_id' => $data['category_id'],
                'location_id' => $data['location_id'],
                'serial_number' => $data['serial_number'],
                'manufacturer' => $data['manufacturer'],
                'model' => $data['model'],
                'firmware_version' => $data['firmware_version'],
                'communication_protocol' => $data['communication_protocol'],
                'ip_address' => $data['ip_address'],
                'mac_address' => $data['mac_address'],
                'acquisition_date' => $data['acquisition_date'],
                'acquisition_cost' => $data['acquisition_cost'],
                'warranty_expiry' => $data['warranty_expiry'],
                'power_source' => $data['power_source'],
                'battery_capacity' => $data['battery_capacity'],
                'operating_temperature_min' => $data['operating_temperature_min'],
                'operating_temperature_max' => $data['operating_temperature_max'],
                'configuration' => json_encode($data['configuration']),
                'security_settings' => json_encode($data['security_settings']),
                'status' => 'inactive',
                'created_by' => $this->user['id']
            ]);

            // Register sensors if provided
            if (!empty($data['sensors'])) {
                $this->registerDeviceSensors($deviceId, $data['sensors']);
            }

            // Generate device authentication credentials
            $this->generateDeviceCredentials($deviceId);

            $this->db->commit();

            $this->setFlash('success', 'Device registered successfully');
            $this->redirect('/iot/devices');

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to register device: ' . $e->getMessage());
            $this->redirect('/iot/register-device');
        }
    }

    private function validateDeviceData($data) {
        if (empty($data['name']) || empty($data['device_type']) || empty($data['category_id'])) {
            return false;
        }

        return [
            'device_id' => $data['device_id'] ?? $this->generateNextDeviceId(),
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'device_type' => $data['device_type'],
            'category_id' => $data['category_id'],
            'location_id' => $data['location_id'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'manufacturer' => $data['manufacturer'] ?? '',
            'model' => $data['model'] ?? '',
            'firmware_version' => $data['firmware_version'] ?? '',
            'communication_protocol' => $data['communication_protocol'] ?? 'mqtt',
            'ip_address' => $data['ip_address'] ?? null,
            'mac_address' => $data['mac_address'] ?? null,
            'acquisition_date' => $data['acquisition_date'] ?? date('Y-m-d'),
            'acquisition_cost' => (float)($data['acquisition_cost'] ?? 0),
            'warranty_expiry' => $data['warranty_expiry'] ?? null,
            'power_source' => $data['power_source'] ?? 'battery',
            'battery_capacity' => (int)($data['battery_capacity'] ?? 0),
            'operating_temperature_min' => (float)($data['operating_temperature_min'] ?? -20),
            'operating_temperature_max' => (float)($data['operating_temperature_max'] ?? 60),
            'configuration' => $data['configuration'] ?? [],
            'security_settings' => $data['security_settings'] ?? [],
            'sensors' => $data['sensors'] ?? []
        ];
    }

    private function registerDeviceSensors($deviceId, $sensors) {
        foreach ($sensors as $sensor) {
            $this->db->insert('device_sensors', [
                'company_id' => $this->user['company_id'],
                'device_id' => $deviceId,
                'sensor_type_id' => $sensor['sensor_type_id'],
                'sensor_name' => $sensor['sensor_name'],
                'measurement_unit' => $sensor['measurement_unit'],
                'calibration_date' => date('Y-m-d'),
                'calibration_due' => date('Y-m-d', strtotime('+1 year')),
                'configuration' => json_encode($sensor['configuration'] ?? []),
                'status' => 'active'
            ]);
        }
    }

    private function generateDeviceCredentials($deviceId) {
        $credentials = [
            'device_id' => $deviceId,
            'api_key' => bin2hex(random_bytes(32)),
            'secret_key' => bin2hex(random_bytes(32)),
            'certificate' => $this->generateDeviceCertificate($deviceId),
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
        ];

        $this->db->insert('device_credentials', [
            'company_id' => $this->user['company_id'],
            'device_id' => $deviceId,
            'credentials' => json_encode($credentials),
            'status' => 'active',
            'created_by' => $this->user['id']
        ]);

        return $credentials;
    }

    private function generateDeviceCertificate($deviceId) {
        // Implementation for generating device certificate
        // This would typically use OpenSSL or similar library
        return '-----BEGIN CERTIFICATE-----\n' . base64_encode(random_bytes(512)) . '\n-----END CERTIFICATE-----';
    }

    private function getSensorReadings() {
        return $this->db->query("
            SELECT
                sr.*,
                d.name as device_name,
                d.device_id as device_code,
                st.name as sensor_type_name,
                st.unit as measurement_unit,
                ds.sensor_name
            FROM sensor_readings sr
            JOIN iot_devices d ON sr.device_id = d.id
            JOIN sensor_types st ON sr.sensor_type_id = st.id
            JOIN device_sensors ds ON sr.device_sensor_id = ds.id
            WHERE sr.company_id = ?
            ORDER BY sr.timestamp DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getSensorTypes() {
        return $this->db->query("
            SELECT * FROM sensor_types
            WHERE company_id = ?
            ORDER BY name ASC
        ", [$this->user['company_id']]);
    }

    private function getDataQualityMetrics() {
        return $this->db->querySingle("
            SELECT
                AVG(data_quality_score) as avg_quality_score,
                COUNT(CASE WHEN data_quality_score >= 0.9 THEN 1 END) as high_quality_readings,
                COUNT(CASE WHEN data_quality_score < 0.7 THEN 1 END) as low_quality_readings,
                COUNT(CASE WHEN is_anomaly = true THEN 1 END) as anomaly_count,
                COUNT(*) as total_readings
            FROM sensor_readings
            WHERE company_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ", [$this->user['company_id']]);
    }

    private function getDataTrends() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('hour', timestamp) as hour,
                COUNT(*) as readings_count,
                AVG(value) as avg_value,
                MIN(value) as min_value,
                MAX(value) as max_value,
                STDDEV(value) as value_stddev
            FROM sensor_readings
            WHERE company_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY DATE_TRUNC('hour', timestamp)
            ORDER BY hour DESC
        ", [$this->user['company_id']]);
    }

    private function getAnomalyDetection() {
        return $this->db->query("
            SELECT
                sr.*,
                d.name as device_name,
                d.device_id as device_code,
                st.name as sensor_type_name,
                ad.anomaly_score,
                ad.detection_method
            FROM sensor_readings sr
            JOIN iot_devices d ON sr.device_id = d.id
            JOIN sensor_types st ON sr.sensor_type_id = st.id
            JOIN anomaly_detection ad ON sr.id = ad.sensor_reading_id
            WHERE sr.company_id = ? AND sr.is_anomaly = true
                AND sr.timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY ad.anomaly_score DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getLiveReadings() {
        return $this->db->query("
            SELECT
                sr.*,
                d.name as device_name,
                d.device_id as device_code,
                st.name as sensor_type_name,
                st.unit as measurement_unit,
                TIMESTAMPDIFF(SECOND, sr.timestamp, NOW()) as seconds_ago
            FROM sensor_readings sr
            JOIN iot_devices d ON sr.device_id = d.id
            JOIN sensor_types st ON sr.sensor_type_id = st.id
            WHERE sr.company_id = ? AND sr.timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY sr.timestamp DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getActiveAlerts() {
        return $this->db->query("
            SELECT
                ia.*,
                d.name as device_name,
                d.device_id as device_code,
                at.name as alert_type_name,
                TIMESTAMPDIFF(MINUTE, ia.created_at, NOW()) as minutes_active
            FROM iot_alerts ia
            JOIN iot_devices d ON ia.device_id = d.id
            JOIN alert_types at ON ia.alert_type_id = at.id
            WHERE ia.company_id = ? AND ia.status = 'active'
            ORDER BY ia.severity DESC, ia.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getSystemStatus() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_devices,
                COUNT(CASE WHEN last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 END) as online_devices,
                COUNT(CASE WHEN battery_level < 20 THEN 1 END) as low_battery_devices,
                AVG(battery_level) as avg_battery_level,
                COUNT(CASE WHEN firmware_version != latest_firmware_version THEN 1 END) as outdated_firmware
            FROM iot_devices
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPerformanceMetrics() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('hour', timestamp) as hour,
                COUNT(*) as readings_per_hour,
                AVG(response_time_ms) as avg_response_time,
                COUNT(CASE WHEN response_time_ms > 1000 THEN 1 END) as slow_responses,
                AVG(data_quality_score) as avg_data_quality
            FROM sensor_readings
            WHERE company_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY DATE_TRUNC('hour', timestamp)
            ORDER BY hour DESC
        ", [$this->user['company_id']]);
    }

    private function getConnectivityStatus() {
        return $this->db->query("
            SELECT
                d.name as device_name,
                d.device_id as device_code,
                d.last_seen,
                TIMESTAMPDIFF(MINUTE, d.last_seen, NOW()) as minutes_since_last_seen,
                CASE
                    WHEN d.last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'online'
                    WHEN d.last_seen > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'recently_online'
                    ELSE 'offline'
                END as connectivity_status,
                d.connection_quality
            FROM iot_devices d
            WHERE d.company_id = ?
            ORDER BY d.last_seen DESC
        ", [$this->user['company_id']]);
    }

    private function getMaintenancePredictions() {
        return $this->db->query("
            SELECT
                mp.*,
                d.name as device_name,
                d.device_id as device_code,
                mt.name as maintenance_type_name,
                TIMESTAMPDIFF(DAY, NOW(), mp.predicted_date) as days_until_maintenance
            FROM maintenance_predictions mp
            JOIN iot_devices d ON mp.device_id = d.id
            JOIN maintenance_types mt ON mp.maintenance_type_id = mt.id
            WHERE mp.company_id = ? AND mp.confidence_score > 0.8
                AND mp.predicted_date >= NOW()
            ORDER BY mp.confidence_score DESC, mp.predicted_date ASC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getFailureProbability() {
        return $this->db->query("
            SELECT
                d.name as device_name,
                d.device_id as device_code,
                fp.failure_type,
                fp.probability_score,
                fp.predicted_time_to_failure,
                fp.recommended_action
            FROM failure_probability fp
            JOIN iot_devices d ON fp.device_id = d.id
            WHERE fp.company_id = ? AND fp.probability_score > 0.1
            ORDER BY fp.probability_score DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getPredictiveMaintenanceSchedule() {
        return $this->db->query("
            SELECT
                d.name as device_name,
                d.device_id as device_code,
                pms.scheduled_date,
                pms.maintenance_type,
                pms.estimated_cost,
                pms.confidence_score,
                TIMESTAMPDIFF(DAY, NOW(), pms.scheduled_date) as days_until_scheduled
            FROM predictive_maintenance_schedule pms
            JOIN iot_devices d ON pms.device_id = d.id
            WHERE pms.company_id = ? AND pms.status = 'scheduled'
                AND pms.scheduled_date >= NOW()
            ORDER BY pms.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceCostSavings() {
        return $this->db->querySingle("
            SELECT
                SUM(predicted_savings) as total_predicted_savings,
                SUM(actual_savings) as total_actual_savings,
                AVG(roi_percentage) as avg_roi,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_predictions
            FROM predictive_maintenance_savings
            WHERE company_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ", [$this->user['company_id']]);
    }

    private function getModelAccuracy() {
        return $this->db->query("
            SELECT
                model_name,
                AVG(accuracy_score) as avg_accuracy,
                COUNT(CASE WHEN accuracy_score >= 0.9 THEN 1 END) as high_accuracy_predictions,
                COUNT(CASE WHEN accuracy_score < 0.7 THEN 1 END) as low_accuracy_predictions,
                MAX(last_trained_at) as last_trained
            FROM ml_model_accuracy
            WHERE company_id = ?
            GROUP BY model_name
            ORDER BY avg_accuracy DESC
        ", [$this->user['company_id']]);
    }

    private function getSecurityEvents() {
        return $this->db->query("
            SELECT
                se.*,
                d.name as device_name,
                d.device_id as device_code,
                set.name as event_type_name
            FROM security_events se
            JOIN iot_devices d ON se.device_id = d.id
            JOIN security_event_types set ON se.event_type_id = set.id
            WHERE se.company_id = ?
            ORDER BY se.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getDeviceAuthentication() {
        return $this->db->query("
            SELECT
                d.name as device_name,
                d.device_id as device_code,
                dc.status as auth_status,
                dc.last_used_at,
                dc.expires_at,
                TIMESTAMPDIFF(DAY, NOW(), dc.expires_at) as days_until_expiry
            FROM iot_devices d
            JOIN device_credentials dc ON d.id = dc.device_id
            WHERE d.company_id = ?
            ORDER BY dc.expires_at ASC
        ", [$this->user['company_id']]);
    }

    private function getFirmwareUpdates() {
        return $this->db->query("
            SELECT
                fu.*,
                d.name as device_name,
                d.device_id as device_code,
                COUNT(CASE WHEN fus.status = 'completed' THEN 1 END) as devices_updated,
                COUNT(fus.id) as total_devices
            FROM firmware_updates fu
            LEFT JOIN iot_devices d ON fu.device_type = d.device_type
            LEFT JOIN firmware_update_status fus ON fu.id = fus.firmware_update_id
            WHERE fu.company_id = ?
            GROUP BY fu.id, d.name, d.device_id
            ORDER BY fu.release_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAccessControl() {
        return $this->db->query("
            SELECT
                d.name as device_name,
                d.device_id as device_code,
                acl.permission_level,
                acl.allowed_operations,
                acl.ip_whitelist,
                acl.last_access_at
            FROM iot_devices d
            JOIN access_control_list acl ON d.id = acl.device_id
            WHERE d.company_id = ?
            ORDER BY acl.last_access_at DESC
        ", [$this->user['company_id']]);
    }

    private function getEncryptionStatus() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN encryption_enabled = true THEN 1 END) as encrypted_devices,
                COUNT(*) as total_devices,
                ROUND(
                    (COUNT(CASE WHEN encryption_enabled = true THEN 1 END) * 100.0 / COUNT(*)), 2
                ) as encryption_percentage,
                COUNT(CASE WHEN certificate_expiry < DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 END) as expiring_certificates
            FROM iot_devices
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEdgeNodes() {
        return $this->db->query("
            SELECT
                en.*,
                COUNT(d.id) as connected_devices,
                AVG(en.cpu_usage) as avg_cpu_usage,
                AVG(en.memory_usage) as avg_memory_usage,
                SUM(en.data_processed_gb) as total_data_processed
            FROM edge_nodes en
            LEFT JOIN iot_devices d ON en.id = d.edge_node_id
            WHERE en.company_id = ?
            GROUP BY en.id
            ORDER BY en.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getLocalProcessing() {
        return $this->db->query("
            SELECT
                lp.*,
                d.name as device_name,
                d.device_id as device_code,
                lp.processing_time_ms,
                lp.data_reduction_ratio
            FROM local_processing lp
            JOIN iot_devices d ON lp.device_id = d.id
            WHERE lp.company_id = ?
            ORDER BY lp.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getDataRouting() {
        return $this->db->query("
            SELECT
                dr.*,
                d.name as device_name,
                d.device_id as device_code,
                en.name as edge_node_name,
                dr.routing_efficiency,
                dr.latency_ms
            FROM data_routing dr
            JOIN iot_devices d ON dr.device_id = d.id
            LEFT JOIN edge_nodes en ON dr.edge_node_id = en.id
            WHERE dr.company_id = ?
            ORDER BY dr.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getOfflineCapabilities() {
        return $this->db->query("
            SELECT
                d.name as device_name,
                d.device_id as device_code,
                oc.offline_duration_hours,
                oc.data_storage_capacity_mb,
                oc.last_sync_at,
                TIMESTAMPDIFF(HOUR, oc.last_sync_at, NOW()) as hours_since_sync
            FROM iot_devices d
            JOIN offline_capabilities oc ON d.id = oc.device_id
            WHERE d.company_id = ?
            ORDER BY oc.last_sync_at DESC
        ", [$this->user['company_id']]);
    }

    private function getEdgeAnalytics() {
        return $this->db->query("
            SELECT
                ea.*,
                en.name as edge_node_name,
                ea.analytics_type,
                ea.execution_time_ms,
                ea.accuracy_score
            FROM edge_analytics ea
            JOIN edge_nodes en ON ea.edge_node_id = en.id
            WHERE ea.company_id = ?
            ORDER BY ea.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getDeviceUtilization() {
        return $this->db->query("
            SELECT
                d.name as device_name,
                d.device_id as device_code,
                COUNT(sr.id) as total_readings,
                AVG(sr.value) as avg_reading_value,
                MAX(sr.timestamp) as last_reading,
                TIMESTAMPDIFF(HOUR, d.last_seen, NOW()) as hours_since_last_seen,
                CASE
                    WHEN d.last_seen > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'high'
                    WHEN d.last_seen > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 'medium'
                    ELSE 'low'
                END as utilization_level
            FROM iot_devices d
            LEFT JOIN sensor_readings sr ON d.id = sr.device_id
                AND sr.timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            WHERE d.company_id = ?
            GROUP BY d.id, d.name, d.device_id, d.last_seen
            ORDER BY total_readings DESC
        ", [$this->user['company_id']]);
    }

    private function getDataVolume() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('day', timestamp) as date,
                COUNT(*) as total_readings,
                SUM(data_size_bytes) as total_data_size,
                COUNT(DISTINCT device_id) as active_devices,
                AVG(data_quality_score) as avg_quality
            FROM sensor_readings
            WHERE company_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE_TRUNC('day', timestamp)
            ORDER BY date DESC
        ", [$this->user['company_id']]);
    }

    private function getEnergyConsumption() {
        return $this->db->query("
            SELECT
                d.name as device_name,
                d.device_id as device_code,
                AVG(ec.power_consumption_watts) as avg_power_consumption,
                SUM(ec.energy_consumed_kwh) as total_energy_consumed,
                MAX(ec.timestamp) as last_measurement
            FROM iot_devices d
            LEFT JOIN energy_consumption ec ON d.id = ec.device_id
                AND ec.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            WHERE d.company_id = ?
            GROUP BY d.id, d.name, d.device_id
            ORDER BY total_energy_consumed DESC
        ", [$this->user['company_id']]);
    }

    private function getIoTROI() {
        $totalInvestment = $this->getTotalIoTInvestment();
        $totalBenefits = $this->getTotalIoTBenefits();

        $roi = $totalInvestment > 0 ? (($totalBenefits - $totalInvestment) / $totalInvestment) * 100 : 0;

        return [
            'total_investment' => $totalInvestment,
            'total_benefits' => $totalBenefits,
            'net_benefit' => $totalBenefits - $totalInvestment,
            'roi_percentage' => round($roi, 2),
            'payback_period_months' => $this->calculatePaybackPeriod(),
            'cost_savings_breakdown' => $this->getCostSavingsBreakdown()
        ];
    }

    private function getTotalIoTInvestment() {
        return $this->db->querySingle("
            SELECT
                SUM(acquisition_cost) + SUM(maintenance_cost) + SUM(software_cost) as total_investment
            FROM iot_devices
            WHERE company_id = ?
        ", [$this->user['company_id']])['total_investment'] ?? 0;
    }

    private function getTotalIoTBenefits() {
        return $this->db->querySingle("
            SELECT
                SUM(productivity_gain) + SUM(cost_savings) + SUM(revenue_increase) as total_benefits
            FROM iot_roi_metrics
            WHERE company_id = ?
        ", [$this->user['company_id']])['total_benefits'] ?? 0;
    }

    private function calculatePaybackPeriod() {
        // Implementation for calculating payback period
        return 18; // months
    }

    private function getCostSavingsBreakdown() {
        return $this->db->query("
            SELECT
                savings_category,
                SUM(amount) as total_savings,
                AVG(percentage) as avg_percentage
            FROM iot_cost_savings
            WHERE company_id = ?
            GROUP BY savings_category
            ORDER BY total_savings DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceInsights() {
        return $this->db->query("
            SELECT
                insight_type,
                COUNT(*) as insight_count,
                AVG(confidence_score) as avg_confidence,
                MAX(created_at) as latest_insight
            FROM iot_performance_insights
            WHERE company_id = ?
            GROUP BY insight_type
            ORDER BY insight_count DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function receiveSensorData() {
        $data = $this->validateRequest([
            'device_id' => 'required|string',
            'sensor_data' => 'required|array',
            'timestamp' => 'date'
        ]);

        try {
            $this->db->beginTransaction();

            $device = $this->db->querySingle("
                SELECT id FROM iot_devices
                WHERE device_id = ? AND company_id = ?
            ", [$data['device_id'], $this->user['company_id']]);

            if (!$device) {
                throw new Exception('Device not found');
            }

            foreach ($data['sensor_data'] as $sensorData) {
                $this->db->insert('sensor_readings', [
                    'company_id' => $this->user['company_id'],
                    'device_id' => $device['id'],
                    'device_sensor_id' => $sensorData['sensor_id'],
                    'sensor_type_id' => $sensorData['sensor_type_id'],
                    'value' => $sensorData['value'],
                    'unit' => $sensorData['unit'],
                    'timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
                    'data_quality_score' => $sensorData['quality_score'] ?? 1.0,
                    'metadata' => json_encode($sensorData['metadata'] ?? [])
                ]);
            }

            // Update device last seen
            $this->db->update('iot_devices', [
                'last_seen' => date('Y-m-d H:i:s'),
                'battery_level' => $data['battery_level'] ?? null
            ], 'id = ?', [$device['id']]);

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Sensor data received successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDeviceStatus() {
        $data = $this->validateRequest([
            'device_id' => 'required|string'
        ]);

        try {
            $device = $this->db->querySingle("
                SELECT
                    d.*,
                    COUNT(sr.id) as recent_readings,
                    MAX(sr.timestamp) as last_reading,
                    AVG(sr.data_quality_score) as avg_data_quality
                FROM iot_devices d
                LEFT JOIN sensor_readings sr ON d.id = sr.device_id
                    AND sr.timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                WHERE d.device_id = ? AND d.company_id = ?
                GROUP BY d.id
            ", [$data['device_id'], $this->user['company_id']]);

            if (!$device) {
                throw new Exception('Device not found');
            }

            $this->jsonResponse([
                'success' => true,
                'device' => $device
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function sendDeviceCommand() {
        $this->requirePermission('iot.devices.control');

        $data = $this->validateRequest([
            'device_id' => 'required|string',
            'command' => 'required|string',
            'parameters' => 'array'
        ]);

        try {
            $device = $
