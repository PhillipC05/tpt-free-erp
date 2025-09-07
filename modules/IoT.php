<?php
/**
 * TPT Free ERP - IoT & Device Integration Module
 * Complete device management, sensor data collection, real-time monitoring, and predictive maintenance system
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
            'device_overview' => $this->getDeviceOverview(),
            'sensor_status' => $this->getSensorStatus(),
            'data_collection' => $this->getDataCollection(),
            'real_time_monitoring' => $this->getRealTimeMonitoring(),
            'predictive_maintenance' => $this->getPredictiveMaintenance(),
            'alert_system' => $this->getAlertSystem(),
            'device_analytics' => $this->getDeviceAnalytics(),
            'system_health' => $this->getSystemHealth()
        ];

        $this->render('modules/iot/dashboard', $data);
    }

    /**
     * Device registration and management
     */
    public function devices() {
        $this->requirePermission('iot.devices.view');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'type' => $_GET['type'] ?? null,
            'location' => $_GET['location'] ?? null,
            'category' => $_GET['category'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $devices = $this->getDevices($filters);

        $data = [
            'title' => 'Device Management',
            'devices' => $devices,
            'filters' => $filters,
            'device_types' => $this->getDeviceTypes(),
            'device_status' => $this->getDeviceStatus(),
            'device_categories' => $this->getDeviceCategories(),
            'device_locations' => $this->getDeviceLocations(),
            'device_templates' => $this->getDeviceTemplates(),
            'bulk_actions' => $this->getBulkActions(),
            'device_analytics' => $this->getDeviceAnalytics()
        ];

        $this->render('modules/iot/devices', $data);
    }

    /**
     * Sensor data collection
     */
    public function sensors() {
        $this->requirePermission('iot.sensors.view');

        $data = [
            'title' => 'Sensor Data Collection',
            'sensor_data' => $this->getSensorData(),
            'data_streams' => $this->getDataStreams(),
            'data_processing' => $this->getDataProcessing(),
            'data_storage' => $this->getDataStorage(),
            'data_quality' => $this->getDataQuality(),
            'data_analytics' => $this->getDataAnalytics(),
            'data_exports' => $this->getDataExports(),
            'data_settings' => $this->getDataSettings()
        ];

        $this->render('modules/iot/sensors', $data);
    }

    /**
     * Real-time monitoring
     */
    public function monitoring() {
        $this->requirePermission('iot.monitoring.view');

        $data = [
            'title' => 'Real-Time Monitoring',
            'live_data' => $this->getLiveData(),
            'monitoring_dashboards' => $this->getMonitoringDashboards(),
            'threshold_alerts' => $this->getThresholdAlerts(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'system_status' => $this->getSystemStatus(),
            'monitoring_logs' => $this->getMonitoringLogs(),
            'monitoring_analytics' => $this->getMonitoringAnalytics(),
            'monitoring_settings' => $this->getMonitoringSettings()
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
            'failure_analysis' => $this->getFailureAnalysis(),
            'maintenance_schedules' => $this->getMaintenanceSchedules(),
            'anomaly_detection' => $this->getAnomalyDetection(),
            'predictive_models' => $this->getPredictiveModels(),
            'maintenance_history' => $this->getMaintenanceHistory(),
            'predictive_analytics' => $this->getPredictiveAnalytics(),
            'maintenance_settings' => $this->getMaintenanceSettings()
        ];

        $this->render('modules/iot/predictive_maintenance', $data);
    }

    /**
     * Alert system
     */
    public function alerts() {
        $this->requirePermission('iot.alerts.view');

        $data = [
            'title' => 'Alert System',
            'active_alerts' => $this->getActiveAlerts(),
            'alert_history' => $this->getAlertHistory(),
            'alert_rules' => $this->getAlertRules(),
            'alert_templates' => $this->getAlertTemplates(),
            'alert_notifications' => $this->getAlertNotifications(),
            'alert_escalation' => $this->getAlertEscalation(),
            'alert_analytics' => $this->getAlertAnalytics(),
            'alert_settings' => $this->getAlertSettings()
        ];

        $this->render('modules/iot/alerts', $data);
    }

    /**
     * Device connectivity
     */
    public function connectivity() {
        $this->requirePermission('iot.connectivity.view');

        $data = [
            'title' => 'Device Connectivity',
            'connection_status' => $this->getConnectionStatus(),
            'network_topology' => $this->getNetworkTopology(),
            'communication_protocols' => $this->getCommunicationProtocols(),
            'data_transmission' => $this->getDataTransmission(),
            'connectivity_analytics' => $this->getConnectivityAnalytics(),
            'connectivity_logs' => $this->getConnectivityLogs(),
            'connectivity_settings' => $this->getConnectivitySettings(),
            'security_protocols' => $this->getSecurityProtocols()
        ];

        $this->render('modules/iot/connectivity', $data);
    }

    /**
     * Device firmware management
     */
    public function firmware() {
        $this->requirePermission('iot.firmware.view');

        $data = [
            'title' => 'Firmware Management',
            'firmware_versions' => $this->getFirmwareVersions(),
            'firmware_updates' => $this->getFirmwareUpdates(),
            'update_schedules' => $this->getUpdateSchedules(),
            'update_history' => $this->getUpdateHistory(),
            'firmware_compatibility' => $this->getFirmwareCompatibility(),
            'update_analytics' => $this->getUpdateAnalytics(),
            'firmware_templates' => $this->getFirmwareTemplates(),
            'firmware_settings' => $this->getFirmwareSettings()
        ];

        $this->render('modules/iot/firmware', $data);
    }

    /**
     * IoT analytics
     */
    public function analytics() {
        $this->requirePermission('iot.analytics.view');

        $data = [
            'title' => 'IoT Analytics',
            'device_performance' => $this->getDevicePerformance(),
            'data_insights' => $this->getDataInsights(),
            'predictive_insights' => $this->getPredictiveInsights(),
            'efficiency_metrics' => $this->getEfficiencyMetrics(),
            'cost_analysis' => $this->getCostAnalysis(),
            'trend_analysis' => $this->getTrendAnalysis(),
            'benchmarking' => $this->getBenchmarking(),
            'custom_reports' => $this->getCustomReports()
        ];

        $this->render('modules/iot/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getDeviceOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT d.id) as total_devices,
                COUNT(CASE WHEN d.status = 'online' THEN 1 END) as online_devices,
                COUNT(CASE WHEN d.status = 'offline' THEN 1 END) as offline_devices,
                COUNT(CASE WHEN d.status = 'maintenance' THEN 1 END) as maintenance_devices,
                COUNT(DISTINCT dt.device_type) as device_types,
                COUNT(DISTINCT dl.location_name) as locations,
                AVG(d.uptime_percentage) as avg_uptime,
                COUNT(CASE WHEN d.last_seen < DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as devices_not_reporting,
                COUNT(CASE WHEN d.firmware_update_available = true THEN 1 END) as devices_needing_updates
            FROM devices d
            LEFT JOIN device_types dt ON d.device_type_id = dt.id
            LEFT JOIN device_locations dl ON d.location_id = dl.id
            WHERE d.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSensorStatus() {
        return $this->db->query("
            SELECT
                s.sensor_name,
                s.sensor_type,
                d.device_name,
                s.status,
                s.last_reading,
                s.last_reading_value,
                s.units,
                TIMESTAMPDIFF(MINUTE, s.last_reading, NOW()) as minutes_since_last_reading,
                s.battery_level,
                s.signal_strength,
                CASE
                    WHEN TIMESTAMPDIFF(MINUTE, s.last_reading, NOW()) > 60 THEN 'not_reporting'
                    WHEN s.battery_level < 20 THEN 'low_battery'
                    WHEN s.signal_strength < 30 THEN 'weak_signal'
                    ELSE 'normal'
                END as sensor_health
            FROM sensors s
            JOIN devices d ON s.device_id = d.id
            WHERE d.company_id = ?
            ORDER BY s.last_reading DESC
        ", [$this->user['company_id']]);
    }

    private function getDataCollection() {
        return $this->db->querySingle("
            SELECT
                COUNT(sd.id) as total_readings,
                COUNT(DISTINCT sd.sensor_id) as active_sensors,
                COUNT(DISTINCT DATE(sd.reading_timestamp)) as days_with_data,
                AVG(sd.reading_value) as avg_reading_value,
                MIN(sd.reading_timestamp) as earliest_reading,
                MAX(sd.reading_timestamp) as latest_reading,
                SUM(CASE WHEN sd.reading_timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 ELSE 0 END) as readings_last_hour,
                SUM(CASE WHEN sd.reading_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as readings_last_24h,
                AVG(TIMESTAMPDIFF(SECOND, LAG(sd.reading_timestamp) OVER (ORDER BY sd.reading_timestamp), sd.reading_timestamp)) as avg_collection_interval
            FROM sensor_data sd
            JOIN sensors s ON sd.sensor_id = s.id
            JOIN devices d ON s.device_id = d.id
            WHERE d.company_id = ? AND sd.reading_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ", [$this->user['company_id']]);
    }

    private function getRealTimeMonitoring() {
        return $this->db->query("
            SELECT
                d.device_name,
                s.sensor_name,
                s.sensor_type,
                sd.reading_value,
                sd.reading_timestamp,
                s.units,
                sd.data_quality_score,
                CASE
                    WHEN sd.reading_value > s.threshold_high THEN 'above_threshold'
                    WHEN sd.reading_value < s.threshold_low THEN 'below_threshold'
                    ELSE 'normal'
                END as reading_status,
                TIMESTAMPDIFF(SECOND, sd.reading_timestamp, NOW()) as seconds_since_reading
            FROM sensor_data sd
            JOIN sensors s ON sd.sensor_id = s.id
            JOIN devices d ON s.device_id = d.id
            WHERE d.company_id = ? AND sd.reading_timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY sd.reading_timestamp DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getPredictiveMaintenance() {
        return $this->db->query("
            SELECT
                d.device_name,
                pm.prediction_type,
                pm.risk_level,
                pm.predicted_failure_date,
                pm.confidence_score,
                pm.recommended_action,
                TIMESTAMPDIFF(DAY, NOW(), pm.predicted_failure_date) as days_until_failure,
                pm.estimated_cost,
                pm.priority_level,
                pm.last_updated
            FROM predictive_maintenance pm
            JOIN devices d ON pm.device_id = d.id
            WHERE d.company_id = ? AND pm.status = 'active'
            ORDER BY pm.risk_level DESC, pm.predicted_failure_date ASC
        ", [$this->user['company_id']]);
    }

    private function getAlertSystem() {
        return $this->db->query("
            SELECT
                a.alert_type,
                a.severity,
                a.message,
                d.device_name,
                s.sensor_name,
                a.trigger_value,
                a.threshold_value,
                a.created_at,
                TIMESTAMPDIFF(MINUTE, a.created_at, NOW()) as minutes_since_alert,
                a.status,
                a.acknowledged_by,
                a.resolved_at
            FROM alerts a
            LEFT JOIN devices d ON a.device_id = d.id
            LEFT JOIN sensors s ON a.sensor_id = s.id
            WHERE a.company_id = ?
            ORDER BY a.severity DESC, a.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getDeviceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(d.id) as total_devices,
                ROUND((COUNT(CASE WHEN d.status = 'online' THEN 1 END) / NULLIF(COUNT(d.id), 0)) * 100, 2) as online_percentage,
                AVG(d.uptime_percentage) as avg_uptime,
                COUNT(CASE WHEN d.uptime_percentage >= 99 THEN 1 END) as high_uptime_devices,
                COUNT(CASE WHEN d.uptime_percentage < 95 THEN 1 END) as low_uptime_devices,
                AVG(d.battery_level) as avg_battery_level,
                COUNT(CASE WHEN d.battery_level < 20 THEN 1 END) as low_battery_devices,
                COUNT(CASE WHEN d.firmware_update_available = true THEN 1 END) as devices_needing_updates,
                AVG(TIMESTAMPDIFF(DAY, d.last_firmware_update, NOW())) as avg_days_since_update
            FROM devices d
            WHERE d.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSystemHealth() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN d.status = 'online' THEN 1 END) as online_devices,
                COUNT(CASE WHEN d.status = 'offline' THEN 1 END) as offline_devices,
                COUNT(CASE WHEN TIMESTAMPDIFF(MINUTE, d.last_seen, NOW()) > 60 THEN 1 END) as devices_not_reporting,
                COUNT(CASE WHEN s.battery_level < 20 THEN 1 END) as low_battery_sensors,
                COUNT(CASE WHEN s.signal_strength < 30 THEN 1 END) as weak_signal_sensors,
                COUNT(a.id) as active_alerts,
                COUNT(CASE WHEN pm.risk_level = 'high' THEN 1 END) as high_risk_predictions,
                ROUND(AVG(d.uptime_percentage), 2) as system_uptime,
                COUNT(CASE WHEN d.uptime_percentage >= 99 THEN 1 END) as high_performance_devices
            FROM devices d
            LEFT JOIN sensors s ON d.id = s.device_id
            LEFT JOIN alerts a ON d.id = a.device_id AND a.status = 'active'
            LEFT JOIN predictive_maintenance pm ON d.id = pm.device_id AND pm.status = 'active'
            WHERE d.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getDevices($filters = []) {
        $where = ["d.company_id = ?"];
        $params = [$this->user['company_id']];

        if (isset($filters['status'])) {
            $where[] = "d.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['type'])) {
            $where[] = "d.device_type_id = ?";
            $params[] = $filters['type'];
        }

        if (isset($filters['location'])) {
            $where[] = "d.location_id = ?";
            $params[] = $filters['location'];
        }

        if (isset($filters['category'])) {
            $where[] = "d.category_id = ?";
            $params[] = $filters['category'];
        }

        if (isset($filters['date_from'])) {
            $where[] = "d.installation_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (isset($filters['date_to'])) {
            $where[] = "d.installation_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (isset($filters['search'])) {
            $where[] = "(d.device_name LIKE ? OR d.device_id LIKE ? OR d.serial_number LIKE ? OR d.model LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                d.*,
                dt.type_name,
                dl.location_name,
                dc.category_name,
                d.uptime_percentage,
                d.battery_level,
                d.signal_strength,
                TIMESTAMPDIFF(MINUTE, d.last_seen, NOW()) as minutes_since_last_seen,
                COUNT(s.id) as sensor_count,
                COUNT(CASE WHEN s.status = 'active' THEN 1 END) as active_sensors,
                COUNT(a.id) as active_alerts,
                d.firmware_version,
                d.firmware_update_available
            FROM devices d
            LEFT JOIN device_types dt ON d.device_type_id = dt.id
            LEFT JOIN device_locations dl ON d.location_id = dl.id
            LEFT JOIN device_categories dc ON d.category_id = dc.id
            LEFT JOIN sensors s ON d.id = s.device_id
            LEFT JOIN alerts a ON d.id = a.device_id AND a.status = 'active'
            WHERE $whereClause
            GROUP BY d.id
            ORDER BY d.device_name ASC
        ", $params);
    }

    private function getDeviceTypes() {
        return $this->db->query("
            SELECT
                dt.*,
                COUNT(d.id) as device_count,
                AVG(d.uptime_percentage) as avg_uptime,
                COUNT(CASE WHEN d.status = 'online' THEN 1 END) as online_devices
            FROM device_types dt
            LEFT JOIN devices d ON dt.id = d.device_type_id
            WHERE dt.company_id = ?
            GROUP BY dt.id
            ORDER BY device_count DESC
        ", [$this->user['company_id']]);
    }

    private function getDeviceStatus() {
        return [
            'online' => 'Online',
            'offline' => 'Offline',
            'maintenance' => 'Maintenance',
            'error' => 'Error',
            'inactive' => 'Inactive'
        ];
    }

    private function getDeviceCategories() {
        return $this->db->query("
            SELECT
                dc.*,
                COUNT(d.id) as device_count,
                SUM(d.purchase_value) as total_value,
                AVG(d.uptime_percentage) as avg_uptime
            FROM device_categories dc
            LEFT JOIN devices d ON dc.id = d.category_id
            WHERE dc.company_id = ?
            GROUP BY dc.id
            ORDER BY device_count DESC
        ", [$this->user['company_id']]);
    }

    private function getDeviceLocations() {
        return $this->db->query("
            SELECT
                dl.*,
                COUNT(d.id) as device_count,
                COUNT(CASE WHEN d.status = 'online' THEN 1 END) as online_devices,
                AVG(d.uptime_percentage) as avg_uptime
            FROM device_locations dl
            LEFT JOIN devices d ON dl.id = d.location_id
            WHERE dl.company_id = ?
            GROUP BY dl.id
            ORDER BY device_count DESC
        ", [$this->user['company_id']]);
    }

    private function getDeviceTemplates() {
        return $this->db->query("
            SELECT * FROM device_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBulkActions() {
        return [
            'update_firmware' => 'Update Firmware',
            'reboot_devices' => 'Reboot Devices',
            'change_location' => 'Change Location',
            'update_category' => 'Update Category',
            'export_devices' => 'Export Device Data',
            'import_devices' => 'Import Device Data',
            'bulk_configuration' => 'Bulk Configuration',
            'generate_reports' => 'Generate Reports'
        ];
    }

    private function getSensorData() {
        return $this->db->query("
            SELECT
                s.sensor_name,
                s.sensor_type,
                d.device_name,
                sd.reading_value,
                sd.reading_timestamp,
                s.units,
                sd.data_quality_score,
                sd.collection_method,
                TIMESTAMPDIFF(SECOND, LAG(sd.reading_timestamp) OVER (PARTITION BY sd.sensor_id ORDER BY sd.reading_timestamp), sd.reading_timestamp) as interval_seconds
            FROM sensor_data sd
            JOIN sensors s ON sd.sensor_id = s.id
            JOIN devices d ON s.device_id = d.id
            WHERE d.company_id = ? AND sd.reading_timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY sd.reading_timestamp DESC
            LIMIT 1000
        ", [$this->user['company_id']]);
    }

    private function getDataStreams() {
        return $this->db->query("
            SELECT
                ds.stream_name,
                ds.stream_type,
                COUNT(sd.id) as total_readings,
                MIN(sd.reading_timestamp) as first_reading,
                MAX(sd.reading_timestamp) as last_reading,
                AVG(sd.reading_value) as avg_value,
                MIN(sd.reading_value) as min_value,
                MAX(sd.reading_value) as max_value,
                ds.sampling_rate,
                ds.retention_period_days,
                ds.is_active
            FROM data_streams ds
            LEFT JOIN sensor_data sd ON ds.id = sd.stream_id
            WHERE ds.company_id = ?
            GROUP BY ds.id
            ORDER BY total_readings DESC
        ", [$this->user['company_id']]);
    }

    private function getDataProcessing() {
        return $this->db->query("
            SELECT
                dp.process_name,
                dp.process_type,
                dp.status,
                dp.last_run,
                dp.execution_time_seconds,
                dp.records_processed,
                dp.success_rate,
                dp.error_count,
                TIMESTAMPDIFF(MINUTE, dp.last_run, NOW()) as minutes_since_last_run
            FROM data_processing dp
            WHERE dp.company_id = ?
            ORDER BY dp.last_run DESC
        ", [$this->user['company_id']]);
    }

    private function getDataStorage() {
        return $this->db->querySingle("
            SELECT
                COUNT(sd.id) as total_readings,
                SUM(LENGTH(sd.raw_data)) as total_data_size_bytes,
                COUNT(DISTINCT DATE(sd.reading_timestamp)) as days_stored,
                COUNT(DISTINCT sd.sensor_id) as sensors_with_data,
                AVG(sd.data_quality_score) as avg_data_quality,
                COUNT(CASE WHEN sd.data_quality_score < 80 THEN 1 END) as low_quality_readings,
                MAX(sd.reading_timestamp) as latest_reading,
                MIN(sd.reading_timestamp) as oldest_reading
            FROM sensor_data sd
            JOIN sensors s ON sd.sensor_id = s.id
            JOIN devices d ON s.device_id = d.id
            WHERE d.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getDataQuality() {
        return $this->db->query("
            SELECT
                s.sensor_name,
                d.device_name,
                AVG(sd.data_quality_score) as avg_quality,
                COUNT(sd.id) as total_readings,
                COUNT(CASE WHEN sd.data_quality_score >= 90 THEN 1 END) as high_quality_readings,
                COUNT(CASE WHEN sd.data_quality_score < 70 THEN 1 END) as low_quality_readings,
                ROUND((COUNT(CASE WHEN sd.data_quality_score >= 90 THEN 1 END) / NULLIF(COUNT(sd.id), 0)) * 100, 2) as quality_percentage,
                MAX(sd.reading_timestamp) as last_reading,
                COUNT(CASE WHEN sd.reading_timestamp < DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as stale_readings
            FROM sensor_data sd
            JOIN sensors s ON sd.sensor_id = s.id
            JOIN devices d ON s.device_id = d.id
            WHERE d.company_id = ?
            GROUP BY s.id, d.id
            ORDER BY avg_quality ASC
        ", [$this->user['company_id']]);
    }

    private function getDataAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT s.id) as total_sensors,
                COUNT(sd.id) as total_readings,
                AVG(sd.reading_value) as avg_reading_value,
                COUNT(DISTINCT DATE(sd.reading_timestamp)) as active_days,
                COUNT(CASE WHEN sd.reading_timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as readings_last_hour,
                COUNT(CASE WHEN sd.reading_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as readings_last_24h,
                AVG(sd.data_quality_score) as avg_data_quality,
                COUNT(CASE WHEN sd.data_quality_score < 80 THEN 1 END) as low_quality_readings
            FROM sensors s
            LEFT JOIN sensor_data sd ON s.id = sd.sensor_id
            JOIN devices d ON s.device_id = d.id
            WHERE d.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getDataExports() {
        return $this->db->query("
            SELECT
                de.export_name,
                de.export_type,
                de.status,
                de.created_at,
                de.completed_at,
                de.file_size_bytes,
                de.record_count,
                de.download_url,
                TIMESTAMPDIFF(MINUTE, de.created_at, de.completed_at) as processing_time_minutes
            FROM data_exports de
            WHERE de.company_id = ?
            ORDER BY de.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getDataSettings() {
        return $this->db->querySingle("
            SELECT * FROM data_collection_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getLiveData() {
        return $this->db->query("
            SELECT
                d.device_name,
                s.sensor_name,
                s.sensor_type,
                sd.reading_value,
                sd.reading_timestamp,
                s.units,
                sd.data_quality_score,
                CASE
                    WHEN sd.reading_value > s.threshold_high THEN 'high'
                    WHEN sd.reading_value < s.threshold_low THEN 'low'
                    ELSE 'normal'
                END as threshold_status,
                TIMESTAMPDIFF(SECOND, sd.reading_timestamp, NOW()) as seconds_ago
            FROM sensor_data sd
            JOIN sensors s ON sd.sensor_id = s.id
            JOIN devices d ON s.device_id = d.id
            WHERE d.company_id = ? AND sd.reading_timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY sd.reading_timestamp DESC
            LIMIT 200
        ", [$this->user['company_id']]);
    }

    private function getMonitoringDashboards() {
        return $this->db->query("
            SELECT
                md.dashboard_name,
                md.dashboard_type,
                md.is_active,
                md.refresh_interval_seconds,
                COUNT(mdw.id) as widget_count,
                md.created_by,
                md.last_modified,
                md.access_level
            FROM monitoring_dashboards md
            LEFT JOIN monitoring_dashboard_widgets mdw ON md.id = mdw.dashboard_id
            WHERE md.company_id = ?
            GROUP BY md.id
            ORDER BY md.is_active DESC, md.dashboard_name ASC
        ", [$this->user['company_id']]);
    }

    private function getThresholdAlerts() {
        return $this->db->query("
            SELECT
                ta.alert_name,
                ta.threshold_type,
                ta.threshold_value,
                ta.condition,
                d.device_name,
                s.sensor_name,
                ta.severity,
                ta.is_active,
                ta.last_triggered,
                COUNT(a.id) as trigger_count
            FROM threshold_alerts ta
            LEFT JOIN devices d ON ta.device_id = d.id
            LEFT JOIN sensors s ON ta.sensor_id = s.id
            LEFT JOIN alerts a ON ta.id = a.threshold_alert_id
            WHERE ta.company_id = ?
            GROUP BY ta.id
            ORDER BY ta.severity DESC, ta.last_triggered DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceMetrics() {
        return $this->db->query("
            SELECT
                pm.metric_name,
                pm.metric_type,
                pm.current_value,
                pm.target_value,
                pm.unit,
                ROUND(((pm.current_value - pm.target_value) / NULLIF(pm.target_value, 0)) * 100, 2) as performance_percentage,
                pm.last_updated,
                pm.trend_direction,
                CASE
                    WHEN pm.current_value >= pm.target_value * 0.95 THEN 'excellent'
                    WHEN pm.current_value >= pm.target_value * 0.85 THEN 'good'
                    WHEN pm.current_value >= pm.target_value * 0.75 THEN 'fair'
                    ELSE 'poor'
                END as performance_rating
            FROM performance_metrics pm
            WHERE pm.company_id = ?
            ORDER BY pm.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getSystemStatus() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN d.status = 'online' THEN 1 END) as online_devices,
                COUNT(CASE WHEN d.status = 'offline' THEN 1 END) as offline_devices,
                COUNT(CASE WHEN TIMESTAMPDIFF(MINUTE, d.last_seen, NOW()) > 60 THEN 1 END) as devices_not_reporting,
                COUNT(CASE WHEN s.battery_level < 20 THEN 1 END) as low_battery_sensors,
                COUNT(CASE WHEN s.signal_strength < 30 THEN 1 END) as weak_signal_sensors,
                COUNT(CASE WHEN a.status = 'active' THEN 1 END) as active_alerts,
                COUNT(CASE WHEN pm.risk_level = 'high' THEN 1 END) as high_risk_predictions,
                ROUND(AVG(d.uptime_percentage), 2) as system_uptime,
                COUNT(CASE WHEN d.uptime_percentage >= 99 THEN 1 END) as high_performance_devices
            FROM devices d
            LEFT JOIN sensors s ON d.id = s.device_id
            LEFT JOIN alerts a ON d.id = a.device_id
            LEFT JOIN predictive_maintenance pm ON d.id = pm.device_id
            WHERE d.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getMonitoringLogs() {
        return $this->db->query("
            SELECT
                ml.log_type,
                ml.severity,
                ml.message,
                d.device_name,
                s.sensor_name,
                ml.log_timestamp,
                ml.source_ip,
                ml.user_agent,
                TIMESTAMPDIFF(MINUTE, ml.log_timestamp, NOW()) as minutes_ago
            FROM monitoring_logs ml
            LEFT JOIN devices d ON ml.device_id = d.id
            LEFT JOIN sensors s ON ml.sensor_id = s.id
            WHERE ml.company_id = ?
            ORDER BY ml.log_timestamp DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getMonitoringAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(ml.id) as total_logs,
                COUNT(CASE WHEN ml.severity = 'error' THEN 1 END) as error_logs,
                COUNT(CASE WHEN ml.severity = 'warning' THEN 1 END) as warning_logs,
                COUNT(CASE WHEN ml.severity = 'info' THEN 1 END) as info_logs,
                COUNT(DISTINCT ml.device_id) as devices_with_logs,
                COUNT(DISTINCT DATE(ml.log_timestamp)) as days_with_logs,
                AVG(TIMESTAMPDIFF(SECOND, LAG(ml.log_timestamp) OVER (ORDER BY ml.log_timestamp), ml.log_timestamp)) as avg_log_interval,
                MAX(ml.log_timestamp) as latest_log
            FROM monitoring_logs ml
            WHERE ml.company_id = ? AND ml.log_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ", [$this->user['company_id']]);
    }

    private function getMonitoringSettings() {
        return $this->db->querySingle("
            SELECT * FROM monitoring_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getMaintenancePredictions() {
        return $this->db->query("
            SELECT
                d.device_name,
                pm.prediction_type,
                pm.risk_level,
                pm.predicted_failure_date,
                pm.confidence_score,
                pm.recommended_action,
                pm.estimated_cost,
                pm.priority_level,
                TIMESTAMPDIFF(DAY, NOW(), pm.predicted_failure_date) as days_until_failure,
                pm.last_updated,
                pm.model_accuracy
            FROM predictive_maintenance pm
            JOIN devices d ON pm.device_id = d.id
            WHERE d.company_id = ? AND pm.status = 'active'
            ORDER BY pm.risk_level DESC, pm.predicted_failure_date ASC
        ", [$this->user['company_id']]);
    }

    private function getFailureAnalysis() {
        return $this->db->query("
            SELECT
                fa.failure_type,
                fa.root_cause,
                COUNT(fa.id) as occurrence_count,
                AVG(fa.downtime_hours) as avg_downtime,
                AVG(fa.repair_cost) as avg_repair_cost,
                MAX(fa.failure_date) as last_occurrence,
                fa.prevention_measures,
                fa.risk_mitigation
            FROM failure_analysis fa
            JOIN devices d ON fa.device_id = d.id
            WHERE d.company_id = ?
            GROUP BY fa.failure_type, fa.root_cause, fa.prevention_measures, fa.risk_mitigation
            ORDER BY occurrence_count DESC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceSchedules() {
        return $this->db->query("
            SELECT
                d.device_name,
                ms.schedule_type,
                ms.next_maintenance_date,
                ms.maintenance_type,
                ms.estimated_duration_hours,
                ms.estimated_cost,
                ms.priority,
                TIMESTAMPDIFF(DAY, NOW(), ms.next_maintenance_date) as days_until_due,
                ms.preventive_measures,
                ms.last_completed
            FROM maintenance_schedules ms
            JOIN devices d ON ms.device_id = d.id
            WHERE d.company_id = ? AND ms.status = 'active'
            ORDER BY ms.next_maintenance_date ASC
        ", [$this->user['company_id']]);
    }

    private function getAnomalyDetection() {
        return $this->db->query("
            SELECT
                d.device_name,
                s.sensor_name,
                ad.anomaly_type,
                ad.severity,
                ad.detected_value,
                ad.expected_value,
                ad.confidence_score,
                ad.detection_timestamp,
                TIMESTAMPDIFF(MINUTE, ad.detection_timestamp, NOW()) as minutes_ago,
                ad.root_cause_analysis,
                ad.recommended_action
            FROM anomaly_detection ad
            JOIN sensors s ON ad.sensor_id = s.id
            JOIN devices d ON s.device_id = d.id
            WHERE d.company_id = ? AND ad.status = 'active'
            ORDER BY ad.severity DESC, ad.detection_timestamp DESC
        ", [$this->user['company_id']]);
    }

    private function getPredictiveModels() {
        return $this->db->query("
            SELECT
                pm.model_name,
                pm.model_type,
                pm.target_variable,
                pm.accuracy_score,
                pm.training_data_size,
                pm.last_trained,
                pm.next_training_date,
                pm.model_status,
                pm.performance_metrics,
                TIMESTAMPDIFF(DAY, NOW(), pm.next_training_date) as days_until_next_training
            FROM predictive_models pm
            WHERE pm.company_id = ?
            ORDER BY pm.accuracy_score DESC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceHistory() {
        return $this->db->query("
            SELECT
                d.device_name,
                mh.maintenance_date,
                mh.maintenance_type,
                mh.description,
                mh.technician,
                mh.duration_hours,
                mh.cost,
                mh.parts_used,
                mh.findings,
                mh.preventive_measures
            FROM maintenance_history mh
            JOIN devices d ON mh.device_id = d.id
            WHERE d.company_id = ?
            ORDER BY mh.maintenance_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPredictiveAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(pm.id) as total_predictions,
                COUNT(CASE WHEN pm.risk_level = 'high' THEN 1 END) as high_risk_predictions,
                COUNT(CASE WHEN pm.risk_level = 'medium' THEN 1 END) as medium_risk_predictions,
                COUNT(CASE WHEN pm.risk_level = 'low' THEN 1 END) as low_risk_predictions,
                AVG(pm.confidence_score) as avg_confidence,
                COUNT(CASE WHEN pm.confidence_score >= 80 THEN 1 END) as high_confidence_predictions,
                AVG(TIMESTAMPDIFF(DAY, NOW(), pm.predicted_failure_date)) as avg_days_to_failure,
                SUM(pm.estimated_cost) as total_predicted_cost
            FROM predictive_maintenance pm
            JOIN devices d ON pm.device_id = d.id
            WHERE d.company_id = ? AND pm.status = 'active'
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceSettings() {
        return $this->db->querySingle("
            SELECT * FROM predictive_maintenance_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getActiveAlerts() {
        return $this->db->query("
            SELECT
                a.alert_type,
                a.severity,
                a.message,
                d.device_name,
                s.sensor_name,
                a.trigger_value,
                a.threshold_value,
                a.created_at,
                TIMESTAMPDIFF(MINUTE, a.created_at, NOW()) as minutes_since_alert,
                a.acknowledged_by,
                a.acknowledged_at,
                a.escalation_level
            FROM alerts a
            LEFT JOIN devices d ON a.device_id = d.id
            LEFT JOIN sensors s ON a.sensor_id = s.id
            WHERE a.company_id = ? AND a.status = 'active'
            ORDER BY a.severity DESC, a.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAlertHistory() {
        return $this->db->query("
            SELECT
                a.alert_type,
                a.severity,
                a.message,
                d.device_name,
                s.sensor_name,
                a.created_at,
                a.resolved_at,
                TIMESTAMPDIFF(MINUTE, a.created_at, a.resolved_at) as resolution_time_minutes,
                a.resolved_by,
                a.root_cause,
                a.preventive_action
            FROM alerts a
            LEFT JOIN devices d ON a.device_id = d.id
            LEFT JOIN sensors s ON a.sensor_id = s.id
            WHERE a.company_id = ? AND a.status = 'resolved'
            ORDER BY a.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAlertRules() {
        return $this->db->query("
            SELECT
                ar.rule_name,
                ar.rule_type,
                ar.condition,
                ar.threshold_value,
                ar.severity,
                ar.is_active,
                COUNT(a.id) as trigger_count,
                MAX(a.created_at) as last_triggered,
                ar.created_by,
                ar.last_modified
            FROM alert_rules ar
            LEFT JOIN alerts a ON ar.id = a.alert_rule_id
            WHERE ar.company_id = ?
            GROUP BY ar.id
            ORDER BY trigger_count DESC
        ", [$this->user['company_id']]);
    }

    private function getAlertTemplates() {
        return $this->db->query("
            SELECT * FROM alert_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAlertNotifications() {
        return $this->db->query("
            SELECT
                an.notification_type,
                an.recipient,
                an.delivery_method,
                an.sent_at,
                an.delivered_at,
                an.opened_at,
                TIMESTAMPDIFF(MINUTE, an.sent_at, an.delivered_at) as delivery_time,
                an.status,
                an.failure_reason
            FROM alert_notifications an
            WHERE an.company_id = ?
            ORDER BY an.sent_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAlertEscalation() {
        return $this->db->query("
            SELECT
                ae.escalation_level,
                ae.escalation_time_minutes,
                ae.recipients,
                ae.notification_methods,
                ae.is_active,
                COUNT(ae.id) as escalation_count,
                AVG(TIMESTAMPDIFF(MINUTE, a.created_at, ae.escalated_at)) as avg_escalation_time
            FROM alert_escalation ae
            LEFT JOIN alerts a ON ae.alert_id = a.id
            WHERE ae.company_id = ?
            GROUP BY ae.escalation_level, ae.escalation_time_minutes, ae.recipients, ae.notification_methods, ae.is_active
            ORDER BY ae.escalation_level ASC
        ", [$this->user['company_id']]);
    }

    private function getAlertAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(a.id) as total_alerts,
                COUNT(CASE WHEN a.severity = 'critical' THEN 1 END) as critical_alerts,
                COUNT(CASE WHEN a.severity = 'high' THEN 1 END) as high_alerts,
                COUNT(CASE WHEN a.severity = 'medium' THEN 1 END) as medium_alerts,
                COUNT(CASE WHEN a.severity = 'low' THEN 1 END) as low_alerts,
                COUNT(CASE WHEN a.status = 'active' THEN 1 END) as active_alerts,
                AVG(TIMESTAMPDIFF(MINUTE, a.created_at, a.resolved_at)) as avg_resolution_time,
                COUNT(CASE WHEN TIMESTAMPDIFF(MINUTE, a.created_at, a.resolved_at) <= 15 THEN 1 END) as quick_resolutions,
                COUNT(an.id) as total_notifications,
                COUNT(CASE WHEN an.status = 'delivered' THEN 1 END) as delivered_notifications
            FROM alerts a
            LEFT JOIN alert_notifications an ON a.id = an.alert_id
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAlertSettings() {
        return $this->db->querySingle("
            SELECT * FROM alert_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getConnectionStatus() {
        return $this->db->query("
            SELECT
                d.device_name,
                d.connection_type,
                d.ip_address,
                d.mac_address,
                d.last_seen,
                TIMESTAMPDIFF(MINUTE, d.last_seen, NOW()) as minutes_since_last_seen,
                d.connection_status,
                d.signal_strength,
                d.bandwidth_usage,
                d.latency_ms,
                d.packet_loss_percentage
            FROM devices d
            WHERE d.company_id = ?
            ORDER BY d.last_seen DESC
        ", [$this->user['company_id']]);
    }

    private function getNetworkTopology() {
        return $this->db->query("
            SELECT
                nt.node_type,
                nt.node_name,
                nt.parent_node,
                nt.connection_type,
                nt.bandwidth_capacity,
                nt.current_utilization,
                ROUND((nt.current_utilization / NULLIF(nt.bandwidth_capacity, 0)) * 100, 2) as utilization_percentage,
                nt.latency_ms,
                nt.status,
                nt.last_updated
            FROM network_topology nt
            WHERE nt.company_id = ?
            ORDER BY nt.node_type, nt.node_name
        ", [$this->user['company_id']]);
    }

    private function getCommunicationProtocols() {
        return $this->db->query("
            SELECT
                cp.protocol_name,
                cp.protocol_type,
