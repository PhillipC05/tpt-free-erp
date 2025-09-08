<?php
/**
 * TPT Free ERP - IoT & Device Integration Module
 * Complete IoT device management, sensor data collection, and real-time monitoring system
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
            'sensor_metrics' => $this->getSensorMetrics(),
            'active_devices' => $this->getActiveDevices(),
            'alerts_summary' => $this->getAlertsSummary(),
            'data_streams' => $this->getDataStreams(),
            'connectivity_status' => $this->getConnectivityStatus()
        ];

        $this->render('modules/iot/dashboard', $data);
    }

    /**
     * Device management
     */
    public function deviceManagement() {
        $this->requirePermission('iot.devices.view');

        $data = [
            'title' => 'Device Management',
            'devices' => $this->getDevices(),
            'device_types' => $this->getDeviceTypes(),
            'device_status' => $this->getDeviceStatus(),
            'device_groups' => $this->getDeviceGroups(),
            'device_filters' => $this->getDeviceFilters(),
            'bulk_operations' => $this->getBulkOperations()
        ];

        $this->render('modules/iot/device_management', $data);
    }

    /**
     * Sensor data collection
     */
    public function sensorData() {
        $this->requirePermission('iot.sensors.view');

        $data = [
            'title' => 'Sensor Data Collection',
            'sensor_readings' => $this->getSensorReadings(),
            'data_collection_rules' => $this->getDataCollectionRules(),
            'data_quality_metrics' => $this->getDataQualityMetrics(),
            'data_storage_config' => $this->getDataStorageConfig(),
            'data_export_options' => $this->getDataExportOptions()
        ];

        $this->render('modules/iot/sensor_data', $data);
    }

    /**
     * Real-time monitoring
     */
    public function realTimeMonitoring() {
        $this->requirePermission('iot.monitoring.view');

        $data = [
            'title' => 'Real-Time Monitoring',
            'live_dashboards' => $this->getLiveDashboards(),
            'alert_rules' => $this->getAlertRules(),
            'threshold_settings' => $this->getThresholdSettings(),
            'notification_channels' => $this->getNotificationChannels(),
            'monitoring_history' => $this->getMonitoringHistory()
        ];

        $this->render('modules/iot/real_time_monitoring', $data);
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
            'cost_benefits' => $this->getCostBenefits(),
            'accuracy_metrics' => $this->getAccuracyMetrics()
        ];

        $this->render('modules/iot/predictive_maintenance', $data);
    }

    /**
     * Alert system
     */
    public function alertSystem() {
        $this->requirePermission('iot.alerts.view');

        $data = [
            'title' => 'Alert System',
            'active_alerts' => $this->getActiveAlerts(),
            'alert_history' => $this->getAlertHistory(),
            'alert_templates' => $this->getAlertTemplates(),
            'escalation_rules' => $this->getEscalationRules(),
            'alert_analytics' => $this->getAlertAnalytics()
        ];

        $this->render('modules/iot/alert_system', $data);
    }

    /**
     * Device connectivity
     */
    public function connectivity() {
        $this->requirePermission('iot.connectivity.view');

        $data = [
            'title' => 'Device Connectivity',
            'network_topology' => $this->getNetworkTopology(),
            'connectivity_protocols' => $this->getConnectivityProtocols(),
            'bandwidth_usage' => $this->getBandwidthUsage(),
            'connection_logs' => $this->getConnectionLogs(),
            'connectivity_analytics' => $this->getConnectivityAnalytics()
        ];

        $this->render('modules/iot/connectivity', $data);
    }

    /**
     * Data analytics and insights
     */
    public function dataAnalytics() {
        $this->requirePermission('iot.analytics.view');

        $data = [
            'title' => 'IoT Data Analytics',
            'data_visualizations' => $this->getDataVisualizations(),
            'trend_analysis' => $this->getTrendAnalysis(),
            'correlation_analysis' => $this->getCorrelationAnalysis(),
            'predictive_insights' => $this->getPredictiveInsights(),
            'custom_dashboards' => $this->getCustomDashboards()
        ];

        $this->render('modules/iot/data_analytics', $data);
    }

    /**
     * Device security
     */
    public function deviceSecurity() {
        $this->requirePermission('iot.security.view');

        $data = [
            'title' => 'Device Security',
            'security_policies' => $this->getSecurityPolicies(),
            'access_controls' => $this->getAccessControls(),
            'encryption_settings' => $this->getEncryptionSettings(),
            'security_audits' => $this->getSecurityAudits(),
            'threat_detection' => $this->getThreatDetection()
        ];

        $this->render('modules/iot/device_security', $data);
    }

    /**
     * Integration management
     */
    public function integrations() {
        $this->requirePermission('iot.integrations.view');

        $data = [
            'title' => 'IoT Integrations',
            'api_endpoints' => $this->getAPIEndpoints(),
            'webhook_configurations' => $this->getWebhookConfigurations(),
            'third_party_integrations' => $this->getThirdPartyIntegrations(),
            'data_sync_status' => $this->getDataSyncStatus(),
            'integration_logs' => $this->getIntegrationLogs()
        ];

        $this->render('modules/iot/integrations', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getDeviceOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_devices,
                COUNT(CASE WHEN status = 'online' THEN 1 END) as online_devices,
                COUNT(CASE WHEN status = 'offline' THEN 1 END) as offline_devices,
                COUNT(CASE WHEN last_seen < DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as inactive_devices,
                COUNT(DISTINCT device_type) as device_types,
                AVG(battery_level) as avg_battery_level,
                SUM(data_points_today) as total_data_points
            FROM iot_devices
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSensorMetrics() {
        return [
            'total_sensors' => $this->getTotalSensors(),
            'active_sensors' => $this->getActiveSensors(),
            'data_collection_rate' => $this->getDataCollectionRate(),
            'data_quality_score' => $this->getDataQualityScore(),
            'sensor_uptime' => $this->getSensorUptime(),
            'alert_frequency' => $this->getAlertFrequency()
        ];
    }

    private function getTotalSensors() {
        $result = $this->db->querySingle("
            SELECT COUNT(*) as total
            FROM iot_sensors
            WHERE company_id = ?
        ", [$this->user['company_id']]);

        return $result['total'] ?? 0;
    }

    private function getActiveSensors() {
        $result = $this->db->querySingle("
            SELECT COUNT(*) as active
            FROM iot_sensors
            WHERE company_id = ? AND status = 'active' AND last_reading > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ", [$this->user['company_id']]);

        return $result['active'] ?? 0;
    }

    private function getDataCollectionRate() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(*) as readings,
                TIMESTAMPDIFF(MINUTE, MIN(timestamp), MAX(timestamp)) as time_span
            FROM sensor_readings
            WHERE company_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ", [$this->user['company_id']]);

        if ($result['time_span'] > 0) {
            return ($result['readings'] / $result['time_span']) * 60; // readings per hour
        }

        return 0;
    }

    private function getDataQualityScore() {
        $result = $this->db->querySingle("
            SELECT
                AVG(CASE WHEN quality_score IS NOT NULL THEN quality_score ELSE 0 END) as avg_quality,
                COUNT(CASE WHEN quality_score >= 80 THEN 1 END) as high_quality_readings,
                COUNT(*) as total_readings
            FROM sensor_readings
            WHERE company_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ", [$this->user['company_id']]);

        return $result['avg_quality'] ?? 0;
    }

    private function getSensorUptime() {
        $result = $this->db->querySingle("
            SELECT
                AVG(uptime_percentage) as avg_uptime
            FROM sensor_uptime_stats
            WHERE company_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ", [$this->user['company_id']]);

        return $result['avg_uptime'] ?? 0;
    }

    private function getAlertFrequency() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(*) as total_alerts,
                TIMESTAMPDIFF(HOUR, MIN(created_at), MAX(created_at)) as time_span
            FROM iot_alerts
            WHERE company_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ", [$this->user['company_id']]);

        if ($result['time_span'] > 0) {
            return $result['total_alerts'] / $result['time_span']; // alerts per hour
        }

        return 0;
    }

    private function getActiveDevices() {
        return $this->db->query("
            SELECT
                d.*,
                d.device_name,
                d.device_type,
                d.status,
                d.last_seen,
                d.battery_level,
                d.signal_strength,
                COUNT(s.id) as sensor_count,
                GROUP_CONCAT(DISTINCT s.sensor_type) as sensor_types
            FROM iot_devices d
            LEFT JOIN iot_sensors s ON d.id = s.device_id
            WHERE d.company_id = ? AND d.status = 'online'
            GROUP BY d.id
            ORDER BY d.last_seen DESC
        ", [$this->user['company_id']]);
    }

    private function getAlertsSummary() {
        return $this->db->query("
            SELECT
                alert_type,
                severity,
                COUNT(*) as count,
                MAX(created_at) as latest_alert
            FROM iot_alerts
            WHERE company_id = ? AND status = 'active'
            GROUP BY alert_type, severity
            ORDER BY severity DESC, count DESC
        ", [$this->user['company_id']]);
    }

    private function getDataStreams() {
        return $this->db->query("
            SELECT
                ds.*,
                ds.stream_name,
                ds.data_type,
                ds.frequency,
                ds.last_updated,
                COUNT(sr.id) as total_readings,
                AVG(sr.value) as avg_value,
                MIN(sr.value) as min_value,
                MAX(sr.value) as max_value
            FROM data_streams ds
            LEFT JOIN sensor_readings sr ON ds.id = sr.stream_id
            WHERE ds.company_id = ?
            GROUP BY ds.id
            ORDER BY ds.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getConnectivityStatus() {
        return $this->db->query("
            SELECT
                connectivity_type,
                COUNT(*) as device_count,
                AVG(signal_strength) as avg_signal,
                COUNT(CASE WHEN status = 'connected' THEN 1 END) as connected_count,
                COUNT(CASE WHEN status = 'disconnected' THEN 1 END) as disconnected_count
            FROM device_connectivity
            WHERE company_id = ?
            GROUP BY connectivity_type
            ORDER BY device_count DESC
        ", [$this->user['company_id']]);
    }

    private function getDevices() {
        return $this->db->query("
            SELECT
                d.*,
                d.device_name,
                d.device_type,
                d.serial_number,
                d.manufacturer,
                d.model,
                d.firmware_version,
                d.status,
                d.last_seen,
                d.battery_level,
                d.signal_strength,
                d.location,
                COUNT(s.id) as sensor_count,
                GROUP_CONCAT(DISTINCT s.sensor_type) as sensor_types
            FROM iot_devices d
            LEFT JOIN iot_sensors s ON d.id = s.device_id
            WHERE d.company_id = ?
            GROUP BY d.id
            ORDER BY d.device_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDeviceTypes() {
        return [
            'sensor_node' => 'Sensor Node',
            'gateway' => 'Gateway',
            'actuator' => 'Actuator',
            'controller' => 'Controller',
            'monitor' => 'Monitor',
            'tracker' => 'Tracker',
            'beacon' => 'Beacon'
        ];
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

    private function getDeviceGroups() {
        return $this->db->query("
            SELECT
                dg.*,
                dg.group_name,
                dg.description,
                COUNT(dg.id) as device_count,
                GROUP_CONCAT(DISTINCT d.device_type) as device_types
            FROM device_groups dg
            LEFT JOIN device_group_members dgm ON dg.id = dgm.group_id
            LEFT JOIN iot_devices d ON dgm.device_id = d.id
            WHERE dg.company_id = ?
            GROUP BY dg.id
            ORDER BY dg.group_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDeviceFilters() {
        return [
            'types' => $this->getDeviceTypes(),
            'statuses' => $this->getDeviceStatus(),
            'manufacturers' => $this->getDeviceManufacturers(),
            'locations' => $this->getDeviceLocations(),
            'connectivity' => $this->getConnectivityTypes()
        ];
    }

    private function getDeviceManufacturers() {
        return $this->db->query("
            SELECT DISTINCT manufacturer, COUNT(*) as device_count
            FROM iot_devices
            WHERE company_id = ? AND manufacturer IS NOT NULL
            GROUP BY manufacturer
            ORDER BY device_count DESC
        ", [$this->user['company_id']]);
    }

    private function getDeviceLocations() {
        return $this->db->query("
            SELECT DISTINCT location, COUNT(*) as device_count
            FROM iot_devices
            WHERE company_id = ? AND location IS NOT NULL
            GROUP BY location
            ORDER BY device_count DESC
        ", [$this->user['company_id']]);
    }

    private function getConnectivityTypes() {
        return [
            'wifi' => 'Wi-Fi',
            'bluetooth' => 'Bluetooth',
            'zigbee' => 'Zigbee',
            'zwave' => 'Z-Wave',
            'cellular' => 'Cellular',
            'ethernet' => 'Ethernet',
            'lora' => 'LoRa'
        ];
    }

    private function getBulkOperations() {
        return [
            'firmware_update' => 'Firmware Update',
            'configuration_change' => 'Configuration Change',
            'reboot' => 'Reboot Devices',
            'diagnostic_run' => 'Run Diagnostics',
            'data_sync' => 'Sync Data',
            'power_cycle' => 'Power Cycle'
        ];
    }

    private function getSensorReadings() {
        return $this->db->query("
            SELECT
                sr.*,
                sr.timestamp,
                sr.value,
                sr.unit,
                sr.quality_score,
                s.sensor_name,
                s.sensor_type,
                d.device_name,
                d.location
            FROM sensor_readings sr
            JOIN iot_sensors s ON sr.sensor_id = s.id
            JOIN iot_devices d ON s.device_id = d.id
            WHERE d.company_id = ?
            ORDER BY sr.timestamp DESC
            LIMIT 1000
        ", [$this->user['company_id']]);
    }

    private function getDataCollectionRules() {
        return $this->db->query("
            SELECT
                dcr.*,
                dcr.rule_name,
                dcr.collection_frequency,
                dcr.data_retention_days,
                dcr.compression_enabled,
                COUNT(s.id) as sensor_count
            FROM data_collection_rules dcr
            LEFT JOIN iot_sensors s ON dcr.id = s.collection_rule_id
            WHERE dcr.company_id = ?
            GROUP BY dcr.id
            ORDER BY dcr.rule_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDataQualityMetrics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(timestamp, '%Y-%m-%d') as date,
                AVG(quality_score) as avg_quality,
                COUNT(CASE WHEN quality_score >= 90 THEN 1 END) as high_quality_count,
                COUNT(CASE WHEN quality_score < 70 THEN 1 END) as low_quality_count,
                COUNT(*) as total_readings
            FROM sensor_readings
            WHERE company_id = ? AND timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d')
            ORDER BY date DESC
        ", [$this->user['company_id']]);
    }

    private function getDataStorageConfig() {
        return [
            'storage_type' => 'time_series_database',
            'retention_policy' => '90_days',
            'compression' => 'enabled',
            'backup_frequency' => 'daily',
            'replication' => 'enabled'
        ];
    }

    private function getDataExportOptions() {
        return [
            'csv' => 'CSV Export',
            'json' => 'JSON Export',
            'xml' => 'XML Export',
            'api' => 'API Access',
            'webhook' => 'Webhook Delivery',
            'scheduled' => 'Scheduled Export'
        ];
    }

    private function getLiveDashboards() {
        return $this->db->query("
            SELECT
                ld.*,
                ld.dashboard_name,
                ld.refresh_interval,
                ld.is_public,
                COUNT(ldw.id) as widget_count
            FROM live_dashboards ld
            LEFT JOIN live_dashboard_widgets ldw ON ld.id = ldw.dashboard_id
            WHERE ld.company_id = ?
            GROUP BY ld.id
            ORDER BY ld.dashboard_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAlertRules() {
        return $this->db->query("
            SELECT
                ar.*,
                ar.rule_name,
                ar.condition_type,
                ar.threshold_value,
                ar.severity,
                ar.is_active,
                COUNT(a.id) as trigger_count
            FROM alert_rules ar
            LEFT JOIN iot_alerts a ON ar.id = a.rule_id
            WHERE ar.company_id = ?
            GROUP BY ar.id
            ORDER BY ar.severity DESC, ar.trigger_count DESC
        ", [$this->user['company_id']]);
    }

    private function getThresholdSettings() {
        return $this->db->query("
            SELECT
                ts.*,
                ts.parameter_name,
                ts.warning_threshold,
                ts.critical_threshold,
                ts.unit,
                s.sensor_name,
                d.device_name
            FROM threshold_settings ts
            JOIN iot_sensors s ON ts.sensor_id = s.id
            JOIN iot_devices d ON s.device_id = d.id
            WHERE d.company_id = ?
            ORDER BY ts.parameter_name ASC
        ", [$this->user['company_id']]);
    }

    private function getNotificationChannels() {
        return [
            'email' => 'Email Notifications',
            'sms' => 'SMS Notifications',
            'push' => 'Push Notifications',
            'webhook' => 'Webhook Notifications',
            'dashboard' => 'Dashboard Alerts',
            'integration' => 'Third-party Integration'
        ];
    }

    private function getMonitoringHistory() {
        return $this->db->query("
            SELECT
                mh.*,
                mh.monitoring_type,
                mh.start_time,
                mh.end_time,
                mh.status,
                mh.issues_found,
                d.device_name
            FROM monitoring_history mh
            JOIN iot_devices d ON mh.device_id = d.id
            WHERE d.company_id = ?
            ORDER BY mh.start_time DESC
        ", [$this->user['company_id']]);
    }

    private function getMaintenancePredictions() {
        return $this->db->query("
            SELECT
                mp.*,
                mp.prediction_type,
                mp.risk_level,
                mp.predicted_date,
                mp.confidence_score,
                mp.recommended_action,
                d.device_name,
                s.sensor_name
            FROM maintenance_predictions mp
            JOIN iot_devices d ON mp.device_id = d.id
            LEFT JOIN iot_sensors s ON mp.sensor_id = s.id
            WHERE d.company_id = ?
            ORDER BY mp.risk_level DESC, mp.predicted_date ASC
        ", [$this->user['company_id']]);
    }

    private function getFailureAnalysis() {
        return $this->db->query("
            SELECT
                fa.*,
                fa.failure_type,
                fa.root_cause,
                fa.impact_level,
                fa.occurred_at,
                d.device_name,
                s.sensor_name
            FROM failure_analysis fa
            JOIN iot_devices d ON fa.device_id = d.id
            LEFT JOIN iot_sensors s ON fa.sensor_id = s.id
            WHERE d.company_id = ?
            ORDER BY fa.occurred_at DESC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceSchedules() {
        return $this->db->query("
            SELECT
                ms.*,
                ms.schedule_type,
                ms.next_maintenance,
                ms.frequency_days,
                ms.priority,
                d.device_name,
                s.sensor_name
            FROM maintenance_schedules ms
            JOIN iot_devices d ON ms.device_id = d.id
            LEFT JOIN iot_sensors s ON ms.sensor_id = s.id
            WHERE d.company_id = ?
            ORDER BY ms.next_maintenance ASC
        ", [$this->user['company_id']]);
    }

    private function getCostBenefits() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(predicted_savings) as predicted_savings,
                SUM(actual_savings) as actual_savings,
                SUM(preventive_cost) as preventive_cost,
                SUM(repair_cost) as repair_cost
            FROM predictive_maintenance_costs
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getAccuracyMetrics() {
        return $this->db->query("
            SELECT
                prediction_model,
                AVG(accuracy_score) as avg_accuracy,
                COUNT(CASE WHEN prediction_correct = true THEN 1 END) as correct_predictions,
                COUNT(*) as total_predictions,
                MAX(last_updated) as last_updated
            FROM prediction_accuracy
            WHERE company_id = ?
            GROUP BY prediction_model
            ORDER BY avg_accuracy DESC
        ", [$this->user['company_id']]);
    }

    private function getActiveAlerts() {
        return $this->db->query("
            SELECT
                a.*,
                a.alert_type,
                a.severity,
                a.message,
                a.created_at,
                a.status,
                d.device_name,
                s.sensor_name,
                ar.rule_name
            FROM iot_alerts a
            LEFT JOIN iot_devices d ON a.device_id = d.id
            LEFT JOIN iot_sensors s ON a.sensor_id = s.id
            LEFT JOIN alert_rules ar ON a.rule_id = ar.id
            WHERE a.company_id = ? AND a.status = 'active'
            ORDER BY a.severity DESC, a.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAlertHistory() {
        return $this->db->query("
            SELECT
                a.*,
                a.alert_type,
                a.severity,
                a.message,
                a.created_at,
                a.resolved_at,
                a.status,
                d.device_name,
                s.sensor_name
            FROM iot_alerts a
            LEFT JOIN iot_devices d ON a.device_id = d.id
            LEFT JOIN iot_sensors s ON a.sensor_id = s.id
            WHERE a.company_id = ?
            ORDER BY a.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAlertTemplates() {
        return $this->db->query("
            SELECT
                at.*,
                at.template_name,
                at.alert_type,
                at.severity,
                at.message_template,
                at.is_active,
                COUNT(a.id) as usage_count
            FROM alert_templates at
            LEFT JOIN iot_alerts a ON at.id = a.template_id
            WHERE at.company_id = ?
            GROUP BY at.id
            ORDER BY at.usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getEscalationRules() {
        return $this->db->query("
            SELECT
                er.*,
                er.rule_name,
                er.trigger_condition,
                er.escalation_delay,
                er.escalation_level,
                er.notification_method,
                COUNT(ae.id) as escalation_count
            FROM escalation_rules er
            LEFT JOIN alert_escalations ae ON er.id = ae.rule_id
            WHERE er.company_id = ?
            GROUP BY er.id
            ORDER BY er.escalation_level ASC
        ", [$this->user['company_id']]);
    }

    private function getAlertAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                alert_type,
                severity,
                COUNT(*) as alert_count,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_resolution_time
            FROM iot_alerts
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m'), alert_type, severity
            ORDER BY month DESC, alert_count DESC
        ", [$this->user['company_id']]);
    }

    private function getNetworkTopology() {
        return $this->db->query("
            SELECT
                nt.*,
                nt.node_type,
                nt.node_name,
                nt.parent_node,
                nt.connection_type,
                nt.signal_strength,
                d.device_name
            FROM network_topology nt
            LEFT JOIN iot_devices d ON nt.device_id = d.id
            WHERE nt.company_id = ?
            ORDER BY nt.node_type ASC, nt.node_name ASC
        ", [$this->user['company_id']]);
    }

    private function getConnectivityProtocols() {
        return [
            'mqtt' => 'MQTT',
            'coap' => 'CoAP',
            'http' => 'HTTP/HTTPS',
            'websocket' => 'WebSocket',
            'modbus' => 'Modbus',
            'bacnet' => 'BACnet',
            'opc_ua' => 'OPC UA'
        ];
    }

    private function getBandwidthUsage() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour,
                SUM(data_sent) as total_sent,
                SUM(data_received) as total_received,
                AVG(signal_strength) as avg_signal,
                COUNT(DISTINCT device_id) as active_devices
            FROM bandwidth_usage
            WHERE company_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00')
            ORDER BY hour DESC
        ", [$this->user['company_id']]);
    }

    private function getConnectionLogs() {
        return $this->db->query("
            SELECT
                cl.*,
                cl.connection_type,
                cl.event_type,
                cl.timestamp,
                cl.duration,
                cl.data_transferred,
                d.device_name
            FROM connection_logs cl
            JOIN iot_devices d ON cl.device_id = d.id
            WHERE d.company_id = ?
            ORDER BY cl.timestamp DESC
        ", [$this->user['company_id']]);
    }

    private function getConnectivityAnalytics() {
        return $this->db->query("
            SELECT
                connectivity_type,
                COUNT(*) as connection_count,
                AVG(duration) as avg_duration,
                SUM(data_transferred) as total_data,
                COUNT(CASE WHEN event_type = 'disconnected' THEN 1 END) as disconnection_count
            FROM connection_logs
            WHERE company_id = ? AND timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY connectivity_type
            ORDER BY connection_count DESC
        ", [$this->user['company_id']]);
    }

    private function getDataVisualizations() {
        return [
            'time_series' => 'Time Series Charts',
            'heat_maps' => 'Heat Maps',
            'scatter_plots' => 'Scatter Plots',
            'histograms' => 'Histograms',
            'box_plots' => 'Box Plots',
            'correlation_matrix' => 'Correlation Matrix'
        ];
    }

    private function getTrendAnalysis() {
        return $this->db->query("
            SELECT
                sensor_id,
                DATE_FORMAT(timestamp, '%Y-%m') as month,
                AVG(value) as avg_value,
                MIN(value) as min_value,
                MAX(value) as max_value,
                STDDEV(value) as std_deviation,
                COUNT(*) as reading_count
            FROM sensor_readings
            WHERE company_id = ? AND timestamp >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY sensor_id, DATE_FORMAT(timestamp, '%Y-%m')
            ORDER BY sensor_id, month ASC
        ", [$this->user['company_id']]);
    }

    private function getCorrelationAnalysis() {
        return $this->db->query("
            SELECT
                s1.sensor_name as sensor1,
                s2.sensor_name as sensor2,
                ca.correlation_coefficient,
                ca.significance_level,
                ca.time_lag,
                ca.last_calculated
            FROM correlation_analysis ca
            JOIN iot_sensors s1 ON ca.sensor1_id = s1.id
            JOIN iot_sensors s2 ON ca.sensor2_id = s2.id
            WHERE ca.company_id = ?
            ORDER BY ABS(ca.correlation_coefficient) DESC
        ", [$this->user['company_id']]);
    }

    private function getPredictiveInsights() {
        return $this->db->query("
            SELECT
                pi.*,
                pi.insight_type,
                pi.confidence_level,
                pi.prediction_value,
                pi.time_horizon,
                pi.generated_at,
                d.device_name,
                s.sensor_name
            FROM predictive_insights pi
            LEFT JOIN iot_devices d ON pi.device_id = d.id
            LEFT JOIN iot_sensors s ON pi.sensor_id = s.id
            WHERE pi.company_id = ?
            ORDER BY pi.confidence_level DESC, pi.generated_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomDashboards() {
        return $this->db->query("
            SELECT
                cd.*,
                cd.dashboard_name,
                cd.description,
                cd.is_public,
                cd.created_by,
                COUNT(cdw.id) as widget_count
            FROM custom_dashboards cd
            LEFT JOIN custom_dashboard_widgets cdw ON cd.id = cdw.dashboard_id
            WHERE cd.company_id = ?
            GROUP BY cd.id
            ORDER BY cd.dashboard_name ASC
        ", [$this->user['company_id']]);
    }

    private function getSecurityPolicies() {
        return $this->db->query("
            SELECT
                sp.*,
                sp.policy_name,
                sp.policy_type,
                sp.enforcement_level,
                sp.is_active,
                COUNT(spd.id) as device_count
            FROM security_policies sp
            LEFT JOIN security_policy_devices spd ON sp.id = spd.policy_id
            WHERE sp.company_id = ?
            GROUP BY sp.id
            ORDER BY sp.enforcement_level DESC
        ", [$this->user['company_id']]);
    }

    private function getAccessControls() {
        return $this->db->query("
            SELECT
                ac.*,
                ac.access_level,
                ac.resource_type,
                ac.allowed_actions,
                ac.ip_restrictions,
                COUNT(u.id) as user_count
            FROM access_controls ac
            LEFT JOIN user_access ua ON ac.id = ua.access_control_id
            LEFT JOIN users u ON ua.user_id = u.id
            WHERE ac.company_id = ?
            GROUP BY ac.id
            ORDER BY ac.access_level DESC
        ", [$this->user['company_id']]);
    }

    private function getEncryptionSettings() {
        return [
            'data_encryption' => 'AES-256',
            'key_rotation' => '90 days',
            'secure_boot' => 'enabled',
            'firmware_encryption' => 'enabled',
            'communication_encryption' => 'TLS 1.3'
        ];
    }

    private function getSecurityAudits() {
        return $this->db->query("
            SELECT
                sa.*,
                sa.audit_type,
                sa.audit_date,
                sa.findings_count,
                sa.risk_level,
                sa.recommendations,
                d.device_name
            FROM security_audits sa
            LEFT JOIN iot_devices d ON sa.device_id = d.id
            WHERE sa.company_id = ?
            ORDER BY sa.audit_date DESC
        ", [$this->user['company_id']]);
    }

    private function getThreatDetection() {
        return $this->db->query("
            SELECT
                td.*,
                td.threat_type,
                td.severity,
                td.detected_at,
                td.status,
                td.response_action,
                d.device_name
            FROM threat_detection td
            LEFT JOIN iot_devices d ON td.device_id = d.id
            WHERE td.company_id = ?
            ORDER BY td.detected_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAPIEndpoints() {
        return $this->db->query("
            SELECT
                ae.*,
                ae.endpoint_name,
                ae.endpoint_url,
                ae.method,
                ae.authentication_required,
                ae.rate_limit,
                COUNT(ael.id) as request_count
            FROM api_endpoints ae
            LEFT JOIN api_endpoint_logs ael ON ae.id = ael.endpoint_id
            WHERE ae.company_id = ?
            GROUP BY ae.id
            ORDER BY ae.endpoint_name ASC
        ", [$this->user['company_id']]);
    }

    private function getWebhookConfigurations() {
        return $this->db->query("
            SELECT
                wc.*,
                wc.webhook_name,
                wc.endpoint_url,
                wc.event_types,
                wc.is_active,
                wc.last_triggered,
                COUNT(wl.id) as trigger_count
            FROM webhook_configurations wc
            LEFT JOIN webhook_logs wl ON wc.id = wl.webhook_id
            WHERE wc.company_id = ?
            GROUP BY wc.id
            ORDER BY wc.webhook_name ASC
        ", [$this->user['company_id']]);
    }

    private function getThirdPartyIntegrations() {
        return [
            'aws_iot' => 'AWS IoT Core',
            'azure_iot' => 'Azure IoT Hub',
            'google_iot' => 'Google Cloud IoT',
            'ibm_watson' => 'IBM Watson IoT',
            'salesforce' => 'Salesforce IoT',
            'sap_iot' => 'SAP IoT'
        ];
    }

    private function getDataSyncStatus() {
        return $this->db->query("
            SELECT
                dss.*,
                dss.integration_name,
                dss.last_sync,
                dss.sync_status,
                dss.records_synced,
                dss.error_message
            FROM data_sync_status dss
            WHERE dss.company_id = ?
            ORDER BY dss.last_sync DESC
        ", [$this->user['company_id']]);
    }

    private function getIntegrationLogs() {
        return $this->db->query("
            SELECT
                il.*,
                il.integration_type,
                il.event_type,
                il.timestamp,
                il.status,
                il.data_size,
                il.processing_time
            FROM integration_logs il
            WHERE il.company_id = ?
            ORDER BY il.timestamp DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function registerDevice() {
        $this->requirePermission('iot.devices.create');

        $data = $this->validateRequest([
            'device_name' => 'required|string',
            'device_type' => 'required|string',
            'serial_number' => 'required|string',
            'manufacturer' => 'string',
            'model' => 'string',
            'location' => 'string',
            'connectivity_type' => 'string'
        ]);

        try {
            $deviceId = $this->db->insert('iot_devices', [
                'company_id' => $this->user['company_id'],
                'device_name' => $data['device_name'],
                'device_type' => $data['device_type'],
                'serial_number' => $data['serial_number'],
                'manufacturer' => $data['manufacturer'] ?? '',
                'model' => $data['model'] ?? '',
                'location' => $data['location'] ?? '',
                'connectivity_type' => $data['connectivity_type'] ?? 'wifi',
                'status' => 'offline',
                'firmware_version' => '1.0.0',
                'created_by' => $this->user['id']
            ]);

            // Log device registration
            $this->logDeviceEvent($deviceId, 'registered', 'Device registered in system');

            $this->jsonResponse([
                'success' => true,
                'device_id' => $deviceId,
                'message' => 'Device registered successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function logDeviceEvent($deviceId, $event, $details) {
        $this->db->insert('device_event_logs', [
            'company_id' => $this->user['company_id'],
            'device_id' => $deviceId,
            'event_type' => $event,
            'event_details' => $details,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $this->user['id']
        ]);
    }

    public function updateDeviceStatus() {
        $this->requirePermission('iot.devices.edit');

        $data = $this->validateRequest([
            'device_id' => 'required|integer',
            'status' => 'required|string',
            'battery_level' => 'numeric',
            'signal_strength' => 'numeric',
            'firmware_version' => 'string'
        ]);

        try {
            $this->db->update('iot_devices', [
                'status' => $data['status'],
                'battery_level' => $data['battery_level'] ?? null,
                'signal_strength' => $data['signal_strength'] ?? null,
                'firmware_version' => $data['firmware_version'] ?? null,
                'last_seen' => date('Y-m-d H:i:s'),
                'updated_by' => $this->user['id']
            ], 'id = ? AND company_id = ?', [
                $data['device_id'],
                $this->user['company_id']
            ]);

            // Log status update
            $this->logDeviceEvent($data['device_id'], 'status_update', "Status updated to {$data['status']}");

            $this->jsonResponse([
                'success' => true,
                'message' => 'Device status updated successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function submitSensorReading() {
        $this->requirePermission('iot.sensors.write');

        $data = $this->validateRequest([
            'device_id' => 'required|integer',
            'sensor_id' => 'required|integer',
            'value' => 'required|numeric',
            'unit' => 'string',
            'timestamp' => 'date',
            'quality_score' => 'numeric'
        ]);

        try {
            $readingId = $this->db->insert('sensor_readings', [
                'company_id' => $this->user['company_id'],
                'device_id' => $data['device_id'],
                'sensor_id' => $data['sensor_id'],
                'value' => $data['value'],
                'unit' => $data['unit'] ?? '',
                'timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
                'quality_score' => $data['quality_score'] ?? 100
            ]);

            // Update device last seen
            $this->db->update('iot_devices', [
                'last_seen' => date('Y-m-d H:i:s')
            ], 'id = ?', [$data['device_id']]);

            // Check for alert conditions
            $this->checkAlertConditions($data);

            $this->jsonResponse([
                'success' => true,
                'reading_id' => $readingId,
                'message' => 'Sensor reading submitted successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function checkAlertConditions($data) {
        // Check threshold alerts
        $thresholds = $this->db->query("
            SELECT * FROM threshold_settings
            WHERE sensor_id = ? AND company_id = ?
        ", [$data['sensor_id'], $this->user['company_id']]);

        foreach ($thresholds as $threshold) {
            $alertTriggered = false;
            $severity = 'low';

            if ($threshold['critical_threshold'] && $data['value'] >= $threshold['critical_threshold']) {
                $alertTriggered = true;
                $severity = 'critical';
            } elseif ($threshold['warning_threshold'] && $data['value'] >= $threshold['warning_threshold']) {
                $alertTriggered = true;
                $severity = 'warning';
            }

            if ($alertTriggered) {
                $this->createAlert([
                    'device_id' => $data['device_id'],
                    'sensor_id' => $data['sensor_id'],
                    'alert_type' => 'threshold_exceeded',
                    'severity' => $severity,
                    'message' => "{$threshold['parameter_name']} exceeded {$severity} threshold: {$data['value']} {$data['unit']}",
                    'rule_id' => $threshold['id']
                ]);
            }
        }
    }

    private function createAlert($alertData) {
        $this->db->insert('iot_alerts', [
            'company_id' => $this->user['company_id'],
            'device_id' => $alertData['device_id'],
            'sensor_id' => $alertData['sensor_id'] ?? null,
            'alert_type' => $alertData['alert_type'],
            'severity' => $alertData['severity'],
            'message' => $alertData['message'],
            'status' => 'active',
            'rule_id' => $alertData['rule_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function createAlertRule() {
        $this->requirePermission('iot.alerts.create');

        $data = $this->validateRequest([
            'rule_name' => 'required|string',
            'device_id' => 'integer',
            'sensor_id' => 'integer',
            'condition_type' => 'required|string',
            'threshold_value' => 'required|numeric',
            'severity' => 'required|string',
            'notification_channels' => 'array'
        ]);

        try {
            $ruleId = $this->db->insert('
