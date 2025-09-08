<?php
/**
 * TPT Free ERP - IoT & Device Integration API Controller
 * Complete REST API for device management, sensor data collection, real-time monitoring, and predictive maintenance
 */

class IoTController extends BaseController {
    private $db;
    private $user;
    private $iot;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->iot = new IoT();
    }

    // ============================================================================
    // DASHBOARD ENDPOINTS
    // ============================================================================

    /**
     * Get IoT overview
     */
    public function getOverview() {
        $this->requirePermission('iot.view');

        try {
            $overview = $this->iot->getDeviceOverview();
            $this->jsonResponse($overview);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get sensor status
     */
    public function getSensorStatus() {
        $this->requirePermission('iot.view');

        try {
            $status = $this->iot->getSensorStatus();
            $this->jsonResponse($status);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get data collection metrics
     */
    public function getDataCollection() {
        $this->requirePermission('iot.view');

        try {
            $collection = $this->iot->getDataCollection();
            $this->jsonResponse($collection);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get real-time monitoring data
     */
    public function getRealTimeMonitoring() {
        $this->requirePermission('iot.monitoring.view');

        try {
            $monitoring = $this->iot->getRealTimeMonitoring();
            $this->jsonResponse($monitoring);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get predictive maintenance data
     */
    public function getPredictiveMaintenance() {
        $this->requirePermission('iot.predictive.view');

        try {
            $maintenance = $this->iot->getPredictiveMaintenance();
            $this->jsonResponse($maintenance);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get alert system data
     */
    public function getAlertSystem() {
        $this->requirePermission('iot.alerts.view');

        try {
            $alerts = $this->iot->getAlertSystem();
            $this->jsonResponse($alerts);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get device analytics
     */
    public function getDeviceAnalytics() {
        $this->requirePermission('iot.analytics.view');

        try {
            $analytics = $this->iot->getDeviceAnalytics();
            $this->jsonResponse($analytics);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get system health
     */
    public function getSystemHealth() {
        $this->requirePermission('iot.view');

        try {
            $health = $this->iot->getSystemHealth();
            $this->jsonResponse($health);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // DEVICE MANAGEMENT ENDPOINTS
    // ============================================================================

    /**
     * Get devices with filtering and pagination
     */
    public function getDevices() {
        $this->requirePermission('iot.devices.view');

        try {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'type' => $_GET['type'] ?? null,
                'location' => $_GET['location'] ?? null,
                'category' => $_GET['category'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null
            ];

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 50);

            $devices = $this->iot->getDevices($filters);
            $total = count($devices);
            $pages = ceil($total / $limit);
            $offset = ($page - 1) * $limit;

            $paginatedDevices = array_slice($devices, $offset, $limit);

            $this->jsonResponse([
                'devices' => $paginatedDevices,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => $pages
                ]
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get single device by ID
     */
    public function getDevice($id) {
        $this->requirePermission('iot.devices.view');

        try {
            $device = $this->db->querySingle("
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
                WHERE d.id = ? AND d.company_id = ?
                GROUP BY d.id
            ", [$id, $this->user['company_id']]);

            if (!$device) {
                $this->errorResponse('Device not found', 404);
                return;
            }

            $this->jsonResponse($device);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Register new device
     */
    public function registerDevice() {
        $this->requirePermission('iot.devices.manage');

        try {
            $data = $this->getJsonInput();

            // Validate required fields
            $required = ['device_name', 'device_type_id', 'location_id'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            // Generate device ID if not provided
            $deviceId = $data['device_id'] ?? $this->generateDeviceId($data['device_type_id']);

            // Prepare device data
            $deviceData = [
                'company_id' => $this->user['company_id'],
                'device_id' => $deviceId,
                'device_name' => trim($data['device_name']),
                'device_type_id' => $data['device_type_id'],
                'location_id' => $data['location_id'],
                'category_id' => $data['category_id'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'model' => $data['model'] ?? null,
                'manufacturer' => $data['manufacturer'] ?? null,
                'firmware_version' => $data['firmware_version'] ?? '1.0.0',
                'ip_address' => $data['ip_address'] ?? null,
                'mac_address' => $data['mac_address'] ?? null,
                'connection_type' => $data['connection_type'] ?? 'wifi',
                'status' => $data['status'] ?? 'offline',
                'installation_date' => $data['installation_date'] ?? date('Y-m-d'),
                'purchase_value' => $data['purchase_value'] ?? 0,
                'warranty_expiry' => $data['warranty_expiry'] ?? null,
                'maintenance_schedule' => $data['maintenance_schedule'] ?? null,
                'uptime_percentage' => 0,
                'battery_level' => $data['battery_level'] ?? null,
                'signal_strength' => $data['signal_strength'] ?? null,
                'last_seen' => null,
                'firmware_update_available' => false,
                'last_firmware_update' => null,
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $deviceDbId = $this->db->insert('devices', $deviceData);

            // Log the device registration
            $this->logActivity('device_registered', 'Device registered', $deviceDbId, [
                'device_name' => $deviceData['device_name'],
                'device_id' => $deviceData['device_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'device_id' => $deviceDbId,
                'device_unique_id' => $deviceId,
                'message' => 'Device registered successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update device
     */
    public function updateDevice($id) {
        $this->requirePermission('iot.devices.manage');

        try {
            $data = $this->getJsonInput();

            // Check if device exists and belongs to company
            $existing = $this->db->querySingle("
                SELECT id FROM devices WHERE id = ? AND company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$existing) {
                $this->errorResponse('Device not found', 404);
                return;
            }

            // Prepare update data
            $updateData = [];
            $allowedFields = [
                'device_name', 'device_type_id', 'location_id', 'category_id', 'serial_number',
                'model', 'manufacturer', 'firmware_version', 'ip_address', 'mac_address',
                'connection_type', 'status', 'installation_date', 'purchase_value',
                'warranty_expiry', 'maintenance_schedule', 'battery_level', 'signal_strength'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $this->db->update('devices', $updateData, "id = ?", [$id]);

                // Log the update
                $this->logActivity('device_updated', 'Device updated', $id, $updateData);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Device updated successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete device
     */
    public function deleteDevice($id) {
        $this->requirePermission('iot.devices.manage');

        try {
            // Check if device exists and belongs to company
            $device = $this->db->querySingle("
                SELECT device_name, device_id FROM devices WHERE id = ? AND company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$device) {
                $this->errorResponse('Device not found', 404);
                return;
            }

            // Soft delete by updating status
            $this->db->update('devices', [
                'status' => 'inactive',
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = ?", [$id]);

            // Log the deletion
            $this->logActivity('device_deleted', 'Device deleted', $id, [
                'device_name' => $device['device_name'],
                'device_id' => $device['device_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Device deleted successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update device status
     */
    public function updateDeviceStatus($id) {
        $this->requirePermission('iot.devices.manage');

        try {
            $data = $this->getJsonInput();

            if (!isset($data['status'])) {
                $this->errorResponse('Status is required', 400);
                return;
            }

            // Check if device exists and belongs to company
            $device = $this->db->querySingle("
                SELECT id FROM devices WHERE id = ? AND company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$device) {
                $this->errorResponse('Device not found', 404);
                return;
            }

            $updateData = [
                'status' => $data['status'],
                'last_seen' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Update additional fields if provided
            if (isset($data['battery_level'])) {
                $updateData['battery_level'] = $data['battery_level'];
            }
            if (isset($data['signal_strength'])) {
                $updateData['signal_strength'] = $data['signal_strength'];
            }
            if (isset($data['uptime_percentage'])) {
                $updateData['uptime_percentage'] = $data['uptime_percentage'];
            }

            $this->db->update('devices', $updateData, "id = ?", [$id]);

            // Log the status update
            $this->logActivity('device_status_updated', 'Device status updated', $id, [
                'status' => $data['status']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Device status updated successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get device types
     */
    public function getDeviceTypes() {
        $this->requirePermission('iot.devices.view');

        try {
            $types = $this->iot->getDeviceTypes();
            $this->jsonResponse($types);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get device categories
     */
    public function getDeviceCategories() {
        $this->requirePermission('iot.devices.view');

        try {
            $categories = $this->iot->getDeviceCategories();
            $this->jsonResponse($categories);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get device locations
     */
    public function getDeviceLocations() {
        $this->requirePermission('iot.devices.view');

        try {
            $locations = $this->iot->getDeviceLocations();
            $this->jsonResponse($locations);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // SENSOR DATA ENDPOINTS
    // ============================================================================

    /**
     * Get sensor data
     */
    public function getSensorData() {
        $this->requirePermission('iot.sensors.view');

        try {
            $sensorData = $this->iot->getSensorData();
            $this->jsonResponse($sensorData);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Submit sensor reading
     */
    public function submitSensorReading() {
        $this->requirePermission('iot.sensors.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['device_id', 'sensor_id', 'reading_value'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            // Verify device belongs to company
            $device = $this->db->querySingle("
                SELECT id FROM devices WHERE device_id = ? AND company_id = ?
            ", [$data['device_id'], $this->user['company_id']]);

            if (!$device) {
                $this->errorResponse('Device not found', 404);
                return;
            }

            // Verify sensor belongs to device
            $sensor = $this->db->querySingle("
                SELECT id, threshold_low, threshold_high FROM sensors
                WHERE id = ? AND device_id = ?
            ", [$data['sensor_id'], $device['id']]);

            if (!$sensor) {
                $this->errorResponse('Sensor not found', 404);
                return;
            }

            $readingData = [
                'company_id' => $this->user['company_id'],
                'sensor_id' => $data['sensor_id'],
                'reading_value' => (float)$data['reading_value'],
                'reading_timestamp' => $data['reading_timestamp'] ?? date('Y-m-d H:i:s'),
                'units' => $data['units'] ?? null,
                'data_quality_score' => $data['data_quality_score'] ?? 100,
                'collection_method' => $data['collection_method'] ?? 'api',
                'raw_data' => isset($data['raw_data']) ? json_encode($data['raw_data']) : null,
                'stream_id' => $data['stream_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $readingId = $this->db->insert('sensor_data', $readingData);

            // Update device last seen
            $this->db->update('devices', [
                'last_seen' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = ?", [$device['id']]);

            // Check for threshold alerts
            $this->checkThresholdAlerts($data['sensor_id'], $readingData['reading_value'], $sensor);

            // Log the reading
            $this->logActivity('sensor_reading_submitted', 'Sensor reading submitted', $readingId, [
                'sensor_id' => $data['sensor_id'],
                'reading_value' => $readingData['reading_value']
            ]);

            $this->jsonResponse([
                'success' => true,
                'reading_id' => $readingId,
                'message' => 'Sensor reading submitted successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get data streams
     */
    public function getDataStreams() {
        $this->requirePermission('iot.sensors.view');

        try {
            $streams = $this->iot->getDataStreams();
            $this->jsonResponse($streams);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get data quality metrics
     */
    public function getDataQuality() {
        $this->requirePermission('iot.sensors.view');

        try {
            $quality = $this->iot->getDataQuality();
            $this->jsonResponse($quality);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // MONITORING ENDPOINTS
    // ============================================================================

    /**
     * Get live data
     */
    public function getLiveData() {
        $this->requirePermission('iot.monitoring.view');

        try {
            $liveData = $this->iot->getLiveData();
            $this->jsonResponse($liveData);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get monitoring dashboards
     */
    public function getMonitoringDashboards() {
        $this->requirePermission('iot.monitoring.view');

        try {
            $dashboards = $this->iot->getMonitoringDashboards();
            $this->jsonResponse($dashboards);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get threshold alerts
     */
    public function getThresholdAlerts() {
        $this->requirePermission('iot.monitoring.view');

        try {
            $alerts = $this->iot->getThresholdAlerts();
            $this->jsonResponse($alerts);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics() {
        $this->requirePermission('iot.monitoring.view');

        try {
            $metrics = $this->iot->getPerformanceMetrics();
            $this->jsonResponse($metrics);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get system status
     */
    public function getSystemStatus() {
        $this->requirePermission('iot.monitoring.view');

        try {
            $status = $this->iot->getSystemStatus();
            $this->jsonResponse($status);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // PREDICTIVE MAINTENANCE ENDPOINTS
    // ============================================================================

    /**
     * Get maintenance predictions
     */
    public function getMaintenancePredictions() {
        $this->requirePermission('iot.predictive.view');

        try {
            $predictions = $this->iot->getMaintenancePredictions();
            $this->jsonResponse($predictions);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get failure analysis
     */
    public function getFailureAnalysis() {
        $this->requirePermission('iot.predictive.view');

        try {
            $analysis = $this->iot->getFailureAnalysis();
            $this->jsonResponse($analysis);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get maintenance schedules
     */
    public function getMaintenanceSchedules() {
        $this->requirePermission('iot.predictive.view');

        try {
            $schedules = $this->iot->getMaintenanceSchedules();
            $this->jsonResponse($schedules);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get anomaly detection
     */
    public function getAnomalyDetection() {
        $this->requirePermission('iot.predictive.view');

        try {
            $anomalies = $this->iot->getAnomalyDetection();
            $this->jsonResponse($anomalies);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // ALERT SYSTEM ENDPOINTS
    // ============================================================================

    /**
     * Get active alerts
     */
    public function getActiveAlerts() {
        $this->requirePermission('iot.alerts.view');

        try {
            $alerts = $this->iot->getActiveAlerts();
            $this->jsonResponse($alerts);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get alert history
     */
    public function getAlertHistory() {
        $this->requirePermission('iot.alerts.view');

        try {
            $history = $this->iot->getAlertHistory();
            $this->jsonResponse($history);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Acknowledge alert
     */
    public function acknowledgeAlert($alertId) {
        $this->requirePermission('iot.alerts.manage');

        try {
            // Check if alert exists and belongs to company
            $alert = $this->db->querySingle("
                SELECT id FROM alerts WHERE id = ? AND company_id = ?
            ", [$alertId, $this->user['company_id']]);

            if (!$alert) {
                $this->errorResponse('Alert not found', 404);
                return;
            }

            $this->db->update('alerts', [
                'acknowledged_by' => $this->user['id'],
                'acknowledged_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = ?", [$alertId]);

            // Log the acknowledgment
            $this->logActivity('alert_acknowledged', 'Alert acknowledged', $alertId, [
                'acknowledged_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Alert acknowledged successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Resolve alert
     */
    public function resolveAlert($alertId) {
        $this->requirePermission('iot.alerts.manage');

        try {
            $data = $this->getJsonInput();

            // Check if alert exists and belongs to company
            $alert = $this->db->querySingle("
                SELECT id FROM alerts WHERE id = ? AND company_id = ?
            ", [$alertId, $this->user['company_id']]);

            if (!$alert) {
                $this->errorResponse('Alert not found', 404);
                return;
            }

            $updateData = [
                'status' => 'resolved',
                'resolved_by' => $this->user['id'],
                'resolved_at' => date('Y-m-d H:i:s'),
                'root_cause' => $data['root_cause'] ?? null,
                'preventive_action' => $data['preventive_action'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->update('alerts', $updateData, "id = ?", [$alertId]);

            // Log the resolution
            $this->logActivity('alert_resolved', 'Alert resolved', $alertId, [
                'resolved_by' => $this->user['id'],
                'root_cause' => $data['root_cause'] ?? null
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Alert resolved successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // CONNECTIVITY ENDPOINTS
    // ============================================================================

    /**
     * Get connection status
     */
    public function getConnectionStatus() {
        $this->requirePermission('iot.connectivity.view');

        try {
            $status = $this->iot->getConnectionStatus();
            $this->jsonResponse($status);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get network topology
     */
    public function getNetworkTopology() {
        $this->requirePermission('iot.connectivity.view');

        try {
            $topology = $this->iot->getNetworkTopology();
            $this->jsonResponse($topology);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // FIRMWARE MANAGEMENT ENDPOINTS
    // ============================================================================

    /**
     * Get firmware versions
     */
    public function getFirmwareVersions() {
        $this->requirePermission('iot.firmware.view');

        try {
            $versions = $this->iot->getFirmwareVersions();
            $this->jsonResponse($versions);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Schedule firmware update
     */
    public function scheduleFirmwareUpdate() {
        $this->requirePermission('iot.firmware.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['device_id', 'firmware_version', 'scheduled_time'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if device exists and belongs to company
            $device = $this->db->querySingle("
                SELECT id FROM devices WHERE id = ? AND company_id = ?
            ", [$data['device_id'], $this->user['company_id']]);

            if (!$device) {
                $this->errorResponse('Device not found', 404);
                return;
            }

            $updateData = [
                'company_id' => $this->user['company_id'],
                'device_id' => $data['device_id'],
                'firmware_version' => $data['firmware_version'],
                'scheduled_time' => $data['scheduled_time'],
                'status' => 'scheduled',
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $updateId = $this->db->insert('firmware_updates', $updateData);

            // Mark device as having update available
            $this->db->update('devices', [
                'firmware_update_available' => true,
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = ?", [$data['device_id']]);

            // Log the firmware update schedule
            $this->logActivity('firmware_update_scheduled', 'Firmware update scheduled', $updateId, [
                'device_id' => $data['device_id'],
                'firmware_version' => $data['firmware_version']
            ]);

            $this->jsonResponse([
                'success' => true,
                'update_id' => $updateId,
                'message' => 'Firmware update scheduled successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // ANALYTICS ENDPOINTS
    // ============================================================================

    /**
     * Get device performance
     */
    public function getDevicePerformance() {
        $this->requirePermission('iot.analytics.view');

        try {
            $performance = $this->iot->getDevicePerformance();
            $this->jsonResponse($performance);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get data insights
     */
    public function getDataInsights() {
        $this->requirePermission('iot.analytics.view');

        try {
            $insights = $this->iot->getDataInsights();
            $this->jsonResponse($insights);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // BULK OPERATIONS ENDPOINTS
    // ============================================================================

    /**
     * Bulk update devices
     */
    public function bulkUpdateDevices() {
        $this->requirePermission('iot.devices.manage');

        try {
            $data = $this->getJsonInput();

            if (!isset($data['device_ids']) || !is_array($data['device_ids'])) {
                $this->errorResponse('Device IDs are required', 400);
                return;
            }

            if (!isset($data['updates']) || !is_array($data['updates'])) {
                $this->errorResponse('Updates data is required', 400);
                return;
            }

            $deviceIds = $data['device_ids'];
            $updates = $data['updates'];

            $updatedCount = 0;
            foreach ($deviceIds as $deviceId) {
                // Verify device belongs to company
                $device = $this->db->querySingle("
                    SELECT id FROM devices WHERE id = ? AND company_id = ?
                ", [$deviceId, $this->user['company_id']]);

                if ($device) {
                    $updates['updated_at'] = date('Y-m-d H:i:s');
                    $this->db->update('devices', $updates, "id = ?", [$deviceId]);
                    $updatedCount++;
                }
            }

            $this->logActivity('devices_bulk_updated', 'Devices bulk updated', null, [
                'count' => $updatedCount,
                'updates' => $updates
            ]);

            $this->jsonResponse([
                'success' => true,
                'updated_count' => $updatedCount,
                'message' => "$updatedCount devices updated successfully"
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Bulk reboot devices
     */
    public function bulkRebootDevices() {
        $this->requirePermission('iot.devices.manage');

        try {
            $data = $this->getJsonInput();

            if (!isset($data['device_ids']) || !is_array($data['device_ids'])) {
                $this->errorResponse('Device IDs are required', 400);
                return;
            }

            $deviceIds = $data['device_ids'];
            $rebootedCount = 0;

            foreach ($deviceIds as $deviceId) {
                // Verify device belongs to company
                $device = $this->db->querySingle("
                    SELECT id FROM devices WHERE id = ? AND company_id = ?
                ", [$deviceId, $this->user['company_id']]);

                if ($device) {
                    // Create reboot command
                    $this->db->insert('device_commands', [
                        'company_id' => $this->user['company_id'],
                        'device_id' => $deviceId,
                        'command_type' => 'reboot',
                        'command_data' => json_encode(['action' => 'reboot']),
                        'status' => 'pending',
                        'created_by' => $this->user['id'],
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $rebootedCount++;
                }
            }

            $this->logActivity('devices_bulk_rebooted', 'Devices bulk rebooted', null, [
                'count' => $rebootedCount
            ]);

            $this->jsonResponse([
                'success' => true,
                'rebooted_count' => $rebootedCount,
                'message' => "$rebootedCount devices reboot commands sent successfully"
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // UTILITY ENDPOINTS
    // ============================================================================

    /**
     * Get device status options
     */
    public function getDeviceStatus() {
        $this->requirePermission('iot.view');

        try {
            $status = $this->iot->getDeviceStatus();
            $this->jsonResponse($status);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Export devices data
     */
    public function exportDevices() {
        $this->requirePermission('iot.devices.view');

        try {
            $filters = $_GET;

            $devices = $this->iot->getDevices($filters);

            // Generate CSV
            $filename = 'devices_export_' . date('Y-m-d_H-i-s') . '.csv';
            $csvContent = $this->generateDevicesCSV($devices);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');

            echo $csvContent;
            exit;

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // PRIVATE HELPER METHODS
    // ============================================================================

    private function generateDeviceId($deviceTypeId) {
        $year = date('Y');

        // Get device type code
        $type = $this->db->querySingle("
            SELECT type_code FROM device_types WHERE id = ?
        ", [$deviceTypeId]);

        $typeCode = $type ? $type['type_code'] : 'DEV';

        // Get the last device ID for this type and year
        $lastDevice = $this->db->querySingle("
            SELECT device_id FROM devices
            WHERE device_id LIKE ? AND company_id = ?
            ORDER BY id DESC LIMIT 1
        ", ["$typeCode-$year%", $this->user['company_id']]);

        if ($lastDevice) {
            $lastNumber = (int)substr($lastDevice['device_id'], -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s%04d', $typeCode, $year, $nextNumber);
    }

    private function checkThresholdAlerts($sensorId, $readingValue, $sensor) {
        try {
            // Check if reading exceeds thresholds
            $alertTriggered = false;
            $alertType = '';
            $severity = 'low';

            if ($sensor['threshold_high'] && $readingValue > $sensor['threshold_high']) {
                $alertTriggered = true;
                $alertType = 'high_threshold';
                $severity = 'high';
            } elseif ($sensor['threshold_low'] && $readingValue < $sensor['threshold_low']) {
                $alertTriggered = true;
                $alertType = 'low_threshold';
                $severity = 'medium';
            }

            if ($alertTriggered) {
                // Get device info
                $device = $this->db->querySingle("
                    SELECT d.device_name, s.sensor_name
                    FROM devices d
                    JOIN sensors s ON d.id = s.device_id
                    WHERE s.id = ?
                ", [$sensorId]);

                $alertData = [
                    'company_id' => $this->user['company_id'],
                    'device_id' => $sensor['device_id'],
                    'sensor_id' => $sensorId,
                    'alert_type' => $alertType,
                    'severity' => $severity,
                    'message' => sprintf(
                        '%s on %s: Reading %.2f %s (Threshold: %.2f %s)',
                        $device['sensor_name'],
                        $device['device_name'],
                        $readingValue,
                        $sensor['units'] ?? '',
                        $alertType === 'high_threshold' ? $sensor['threshold_high'] : $sensor['threshold_low'],
                        $sensor['units'] ?? ''
                    ),
                    'trigger_value' => $readingValue,
                    'threshold_value' => $alertType === 'high_threshold' ? $sensor['threshold_high'] : $sensor['threshold_low'],
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $this->db->insert('alerts', $alertData);
            }
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log('Failed to check threshold alerts: ' . $e->getMessage());
        }
    }

    private function generateDevicesCSV($devices) {
        $headers = [
            'Device Name',
            'Device ID',
            'Type',
            'Location',
            'Status',
            'Last Seen',
            'Uptime %',
            'Battery %',
            'Signal Strength',
            'Firmware Version'
        ];

        $csv = implode(',', array_map(function($header) {
            return '"' . str_replace('"', '""', $header) . '"';
        }, $headers)) . "\n";

        foreach ($devices as $device) {
            $row = [
                $device['device_name'] ?? '',
                $device['device_id'] ?? '',
                $device['type_name'] ?? '',
                $device['location_name'] ?? '',
                $device['status'] ?? '',
                $device['last_seen'] ?? '',
                $device['uptime_percentage'] ? $device['uptime_percentage'] . '%' : '',
                $device['battery_level'] ? $device['battery_level'] . '%' : '',
                $device['signal_strength'] ? $device['signal_strength'] . '%' : '',
                $device['firmware_version'] ?? ''
            ];

            $csv .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        return $csv;
    }

    private function logActivity($action, $description, $entityId = null, $details = null) {
        try {
            $this->db->insert('audit_log', [
                'company_id' => $this->user['company_id'],
                'user_id' => $this->user['id'],
                'action' => $action,
                'description' => $description,
                'entity_type' => 'device',
                'entity_id' => $entityId,
                'details' => json_encode($details),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
}
