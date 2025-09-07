<?php
/**
 * TPT Free ERP - Field Service Management Module
 * Complete service call management, technician scheduling, and customer service system
 */

class FieldService extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main field service dashboard
     */
    public function index() {
        $this->requirePermission('field_service.view');

        $data = [
            'title' => 'Field Service Management',
            'service_overview' => $this->getServiceOverview(),
            'service_calls' => $this->getServiceCalls(),
            'technician_status' => $this->getTechnicianStatus(),
            'service_schedule' => $this->getServiceSchedule(),
            'customer_satisfaction' => $this->getCustomerSatisfaction(),
            'service_analytics' => $this->getServiceAnalytics(),
            'upcoming_appointments' => $this->getUpcomingAppointments(),
            'service_alerts' => $this->getServiceAlerts()
        ];

        $this->render('modules/field_service/dashboard', $data);
    }

    /**
     * Service call management
     */
    public function serviceCalls() {
        $this->requirePermission('field_service.calls.view');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'priority' => $_GET['priority'] ?? null,
            'technician' => $_GET['technician'] ?? null,
            'customer' => $_GET['customer'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $service_calls = $this->getServiceCalls($filters);

        $data = [
            'title' => 'Service Call Management',
            'service_calls' => $service_calls,
            'filters' => $filters,
            'service_status' => $this->getServiceStatus(),
            'service_priorities' => $this->getServicePriorities(),
            'service_types' => $this->getServiceTypes(),
            'technicians' => $this->getTechnicians(),
            'customers' => $this->getCustomers(),
            'service_templates' => $this->getServiceTemplates(),
            'bulk_actions' => $this->getBulkActions(),
            'service_analytics' => $this->getServiceAnalytics()
        ];

        $this->render('modules/field_service/service_calls', $data);
    }

    /**
     * Technician scheduling
     */
    public function technicianScheduling() {
        $this->requirePermission('field_service.scheduling.view');

        $data = [
            'title' => 'Technician Scheduling',
            'technician_schedule' => $this->getTechnicianSchedule(),
            'technician_availability' => $this->getTechnicianAvailability(),
            'workload_distribution' => $this->getWorkloadDistribution(),
            'skill_matrix' => $this->getSkillMatrix(),
            'route_optimization' => $this->getRouteOptimization(),
            'schedule_conflicts' => $this->getScheduleConflicts(),
            'scheduling_analytics' => $this->getSchedulingAnalytics(),
            'scheduling_templates' => $this->getSchedulingTemplates()
        ];

        $this->render('modules/field_service/technician_scheduling', $data);
    }

    /**
     * Mobile technician app
     */
    public function mobileApp() {
        $this->requirePermission('field_service.mobile.view');

        $data = [
            'title' => 'Mobile Technician App',
            'app_features' => $this->getAppFeatures(),
            'app_users' => $this->getAppUsers(),
            'app_usage' => $this->getAppUsage(),
            'app_performance' => $this->getAppPerformance(),
            'offline_capabilities' => $this->getOfflineCapabilities(),
            'app_notifications' => $this->getAppNotifications(),
            'app_analytics' => $this->getAppAnalytics(),
            'app_settings' => $this->getAppSettings()
        ];

        $this->render('modules/field_service/mobile_app', $data);
    }

    /**
     * Customer communication
     */
    public function customerCommunication() {
        $this->requirePermission('field_service.communication.view');

        $data = [
            'title' => 'Customer Communication',
            'communication_history' => $this->getCommunicationHistory(),
            'communication_templates' => $this->getCommunicationTemplates(),
            'customer_feedback' => $this->getCustomerFeedback(),
            'appointment_reminders' => $this->getAppointmentReminders(),
            'service_updates' => $this->getServiceUpdates(),
            'communication_analytics' => $this->getCommunicationAnalytics(),
            'notification_settings' => $this->getNotificationSettings(),
            'communication_channels' => $this->getCommunicationChannels()
        ];

        $this->render('modules/field_service/customer_communication', $data);
    }

    /**
     * Service history tracking
     */
    public function serviceHistory() {
        $this->requirePermission('field_service.history.view');

        $data = [
            'title' => 'Service History Tracking',
            'service_history' => $this->getServiceHistory(),
            'equipment_history' => $this->getEquipmentHistory(),
            'maintenance_history' => $this->getMaintenanceHistory(),
            'parts_usage' => $this->getPartsUsage(),
            'service_trends' => $this->getServiceTrends(),
            'customer_history' => $this->getCustomerHistory(),
            'history_analytics' => $this->getHistoryAnalytics(),
            'history_reports' => $this->getHistoryReports()
        ];

        $this->render('modules/field_service/service_history', $data);
    }

    /**
     * Parts management
     */
    public function partsManagement() {
        $this->requirePermission('field_service.parts.view');

        $data = [
            'title' => 'Parts Management',
            'parts_inventory' => $this->getPartsInventory(),
            'parts_orders' => $this->getPartsOrders(),
            'parts_usage' => $this->getPartsUsage(),
            'parts_suppliers' => $this->getPartsSuppliers(),
            'parts_tracking' => $this->getPartsTracking(),
            'parts_analytics' => $this->getPartsAnalytics(),
            'parts_templates' => $this->getPartsTemplates(),
            'parts_settings' => $this->getPartsSettings()
        ];

        $this->render('modules/field_service/parts_management', $data);
    }

    /**
     * Service contracts
     */
    public function serviceContracts() {
        $this->requirePermission('field_service.contracts.view');

        $data = [
            'title' => 'Service Contracts',
            'service_contracts' => $this->getServiceContracts(),
            'contract_templates' => $this->getContractTemplates(),
            'contract_terms' => $this->getContractTerms(),
            'contract_billing' => $this->getContractBilling(),
            'contract_compliance' => $this->getContractCompliance(),
            'contract_renewals' => $this->getContractRenewals(),
            'contract_analytics' => $this->getContractAnalytics(),
            'contract_reports' => $this->getContractReports()
        ];

        $this->render('modules/field_service/service_contracts', $data);
    }

    /**
     * Field service analytics
     */
    public function analytics() {
        $this->requirePermission('field_service.analytics.view');

        $data = [
            'title' => 'Field Service Analytics',
            'service_performance' => $this->getServicePerformance(),
            'technician_productivity' => $this->getTechnicianProductivity(),
            'customer_satisfaction' => $this->getCustomerSatisfaction(),
            'service_efficiency' => $this->getServiceEfficiency(),
            'cost_analysis' => $this->getCostAnalysis(),
            'predictive_maintenance' => $this->getPredictiveMaintenance(),
            'geographic_analytics' => $this->getGeographicAnalytics(),
            'benchmarking' => $this->getBenchmarking()
        ];

        $this->render('modules/field_service/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getServiceOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT sc.id) as total_service_calls,
                COUNT(CASE WHEN sc.status = 'open' THEN 1 END) as open_service_calls,
                COUNT(CASE WHEN sc.status = 'in_progress' THEN 1 END) as in_progress_calls,
                COUNT(CASE WHEN sc.status = 'completed' THEN 1 END) as completed_calls,
                COUNT(CASE WHEN sc.priority = 'high' THEN 1 END) as high_priority_calls,
                COUNT(DISTINCT sc.customer_id) as unique_customers,
                COUNT(DISTINCT sc.technician_id) as active_technicians,
                AVG(sc.estimated_duration) as avg_service_duration,
                SUM(sc.labor_cost + sc.parts_cost) as total_service_cost
            FROM service_calls sc
            WHERE sc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getServiceCalls($filters = []) {
        $where = ["sc.company_id = ?"];
        $params = [$this->user['company_id']];

        if (isset($filters['status'])) {
            $where[] = "sc.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['priority'])) {
            $where[] = "sc.priority = ?";
            $params[] = $filters['priority'];
        }

        if (isset($filters['technician'])) {
            $where[] = "sc.technician_id = ?";
            $params[] = $filters['technician'];
        }

        if (isset($filters['customer'])) {
            $where[] = "sc.customer_id = ?";
            $params[] = $filters['customer'];
        }

        if (isset($filters['date_from'])) {
            $where[] = "sc.scheduled_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (isset($filters['date_to'])) {
            $where[] = "sc.scheduled_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (isset($filters['search'])) {
            $where[] = "(sc.service_number LIKE ? OR c.customer_name LIKE ? OR sc.description LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                sc.*,
                c.customer_name,
                c.customer_phone,
                c.customer_email,
                u.first_name as technician_first,
                u.last_name as technician_last,
                sc.scheduled_date,
                sc.actual_start_time,
                sc.actual_end_time,
                TIMESTAMPDIFF(DAY, CURDATE(), sc.scheduled_date) as days_until_scheduled,
                sc.estimated_duration,
                sc.actual_duration,
                sc.labor_cost,
                sc.parts_cost,
                sc.total_cost
            FROM service_calls sc
            LEFT JOIN customers c ON sc.customer_id = c.id
            LEFT JOIN users u ON sc.technician_id = u.id
            WHERE $whereClause
            ORDER BY sc.priority DESC, sc.scheduled_date ASC
        ", $params);
    }

    private function getTechnicianStatus() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                u.id as technician_id,
                COUNT(sc.id) as assigned_calls,
                COUNT(CASE WHEN sc.status = 'in_progress' THEN 1 END) as active_calls,
                COUNT(CASE WHEN sc.status = 'completed' THEN 1 END) as completed_calls,
                SUM(sc.estimated_duration) as total_estimated_hours,
                SUM(sc.actual_duration) as total_actual_hours,
                AVG(sc.customer_rating) as avg_customer_rating,
                t.availability_status
            FROM users u
            LEFT JOIN service_calls sc ON u.id = sc.technician_id
            LEFT JOIN technician_status t ON u.id = t.technician_id
            WHERE u.company_id = ? AND u.role = 'technician'
            GROUP BY u.id, u.first_name, u.last_name, t.availability_status
            ORDER BY assigned_calls DESC
        ", [$this->user['company_id']]);
    }

    private function getServiceSchedule() {
        return $this->db->query("
            SELECT
                sc.service_number,
                c.customer_name,
                u.first_name as technician_first,
                u.last_name as technician_last,
                sc.scheduled_date,
                sc.scheduled_time,
                sc.estimated_duration,
                sc.priority,
                sc.status,
                sc.service_location,
                TIMESTAMPDIFF(DAY, CURDATE(), sc.scheduled_date) as days_until_service
            FROM service_calls sc
            JOIN customers c ON sc.customer_id = c.id
            LEFT JOIN users u ON sc.technician_id = u.id
            WHERE sc.company_id = ? AND sc.status IN ('scheduled', 'confirmed')
            ORDER BY sc.scheduled_date ASC, sc.scheduled_time ASC
        ", [$this->user['company_id']]);
    }

    private function getCustomerSatisfaction() {
        return $this->db->querySingle("
            SELECT
                COUNT(sc.id) as total_completed_services,
                AVG(sc.customer_rating) as avg_customer_rating,
                COUNT(CASE WHEN sc.customer_rating >= 4 THEN 1 END) as satisfied_customers,
                COUNT(CASE WHEN sc.customer_rating < 3 THEN 1 END) as dissatisfied_customers,
                ROUND((COUNT(CASE WHEN sc.customer_rating >= 4 THEN 1 END) / NULLIF(COUNT(sc.id), 0)) * 100, 2) as satisfaction_rate,
                AVG(sc.response_time) as avg_response_time,
                AVG(sc.resolution_time) as avg_resolution_time
            FROM service_calls sc
            WHERE sc.company_id = ? AND sc.status = 'completed'
        ", [$this->user['company_id']]);
    }

    private function getServiceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(sc.id) as total_service_calls,
                ROUND((COUNT(CASE WHEN sc.status = 'completed' THEN 1 END) / NULLIF(COUNT(sc.id), 0)) * 100, 2) as completion_rate,
                AVG(sc.actual_duration) as avg_service_duration,
                AVG(sc.total_cost) as avg_service_cost,
                SUM(sc.total_cost) as total_service_cost,
                COUNT(DISTINCT sc.customer_id) as unique_customers,
                COUNT(DISTINCT sc.technician_id) as active_technicians,
                AVG(sc.customer_rating) as avg_customer_rating
            FROM service_calls sc
            WHERE sc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getUpcomingAppointments() {
        return $this->db->query("
            SELECT
                sc.service_number,
                c.customer_name,
                c.customer_phone,
                u.first_name as technician_first,
                u.last_name as technician_last,
                sc.scheduled_date,
                sc.scheduled_time,
                sc.service_type,
                sc.priority,
                sc.service_location,
                TIMESTAMPDIFF(HOUR, NOW(), CONCAT(sc.scheduled_date, ' ', sc.scheduled_time)) as hours_until_service
            FROM service_calls sc
            JOIN customers c ON sc.customer_id = c.id
            LEFT JOIN users u ON sc.technician_id = u.id
            WHERE sc.company_id = ? AND sc.status IN ('scheduled', 'confirmed')
                AND CONCAT(sc.scheduled_date, ' ', sc.scheduled_time) >= NOW()
                AND CONCAT(sc.scheduled_date, ' ', sc.scheduled_time) <= DATE_ADD(NOW(), INTERVAL 7 DAY)
            ORDER BY sc.scheduled_date ASC, sc.scheduled_time ASC
        ", [$this->user['company_id']]);
    }

    private function getServiceAlerts() {
        return $this->db->query("
            SELECT
                sa.*,
                sa.alert_type,
                sa.severity,
                sa.message,
                sa.service_call_id,
                sa.technician_id,
                sa.customer_id,
                sa.created_at,
                TIMESTAMPDIFF(MINUTE, sa.created_at, NOW()) as minutes_since_alert
            FROM service_alerts sa
            WHERE sa.company_id = ? AND sa.status = 'active'
            ORDER BY sa.severity DESC, sa.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getServiceStatus() {
        return [
            'open' => 'Open',
            'assigned' => 'Assigned',
            'scheduled' => 'Scheduled',
            'confirmed' => 'Confirmed',
            'in_progress' => 'In Progress',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];
    }

    private function getServicePriorities() {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
            'emergency' => 'Emergency'
        ];
    }

    private function getServiceTypes() {
        return $this->db->query("
            SELECT * FROM service_types
            WHERE company_id = ? AND is_active = true
            ORDER BY service_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTechnicians() {
        return $this->db->query("
            SELECT
                u.id,
                u.first_name,
                u.last_name,
                t.skill_level,
                t.certifications,
                t.availability_status,
                COUNT(sc.id) as active_assignments,
                AVG(sc.customer_rating) as avg_rating
            FROM users u
            JOIN technicians t ON u.id = t.user_id
            LEFT JOIN service_calls sc ON u.id = sc.technician_id AND sc.status IN ('assigned', 'scheduled', 'confirmed', 'in_progress')
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name, t.skill_level, t.certifications, t.availability_status
            ORDER BY active_assignments ASC, avg_rating DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomers() {
        return $this->db->query("
            SELECT
                c.*,
                COUNT(sc.id) as total_service_calls,
                MAX(sc.service_date) as last_service_date,
                AVG(sc.customer_rating) as avg_rating,
                SUM(sc.total_cost) as total_service_cost
            FROM customers c
            LEFT JOIN service_calls sc ON c.id = sc.customer_id
            WHERE c.company_id = ?
            GROUP BY c.id
            ORDER BY total_service_calls DESC
        ", [$this->user['company_id']]);
    }

    private function getServiceTemplates() {
        return $this->db->query("
            SELECT * FROM service_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBulkActions() {
        return [
            'assign_technician' => 'Assign Technician',
            'update_priority' => 'Update Priority',
            'reschedule_service' => 'Reschedule Service',
            'update_status' => 'Update Status',
            'send_notification' => 'Send Notification',
            'export_service_calls' => 'Export Service Calls',
            'bulk_schedule' => 'Bulk Schedule',
            'generate_invoice' => 'Generate Invoice'
        ];
    }

    private function getTechnicianSchedule() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                sc.service_number,
                sc.scheduled_date,
                sc.scheduled_time,
                sc.estimated_duration,
                sc.service_location,
                sc.priority,
                c.customer_name
            FROM users u
            JOIN service_calls sc ON u.id = sc.technician_id
            JOIN customers c ON sc.customer_id = c.id
            WHERE u.company_id = ? AND sc.status IN ('scheduled', 'confirmed', 'in_progress')
            ORDER BY u.last_name, sc.scheduled_date, sc.scheduled_time
        ", [$this->user['company_id']]);
    }

    private function getTechnicianAvailability() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                ta.available_date,
                ta.start_time,
                ta.end_time,
                ta.is_available,
                ta.working_hours,
                ta.break_hours,
                ta.travel_time
            FROM users u
            JOIN technician_availability ta ON u.id = ta.technician_id
            WHERE u.company_id = ?
            ORDER BY ta.available_date ASC, u.last_name ASC
        ", [$this->user['company_id']]);
    }

    private function getWorkloadDistribution() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                COUNT(sc.id) as assigned_services,
                SUM(sc.estimated_duration) as total_estimated_hours,
                SUM(sc.actual_duration) as total_actual_hours,
                AVG(sc.customer_rating) as avg_customer_rating,
                ROUND((SUM(sc.actual_duration) / NULLIF(SUM(sc.estimated_duration), 0)) * 100, 2) as efficiency_percentage
            FROM users u
            LEFT JOIN service_calls sc ON u.id = sc.technician_id
            WHERE u.company_id = ? AND u.role = 'technician'
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY assigned_services DESC
        ", [$this->user['company_id']]);
    }

    private function getSkillMatrix() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                s.skill_name,
                ts.proficiency_level,
                ts.certification_date,
                ts.expiry_date,
                ts.training_hours,
                COUNT(sc.id) as services_with_skill
            FROM users u
            JOIN technician_skills ts ON u.id = ts.technician_id
            JOIN skills s ON ts.skill_id = s.id
            LEFT JOIN service_calls sc ON u.id = sc.technician_id AND sc.service_type IN (
                SELECT st.id FROM service_types st WHERE st.required_skills LIKE CONCAT('%', s.skill_name, '%')
            )
            WHERE u.company_id = ?
            GROUP BY u.id, u.first_name, u.last_name, s.id, s.skill_name, ts.proficiency_level, ts.certification_date, ts.expiry_date, ts.training_hours
            ORDER BY s.skill_name, ts.proficiency_level DESC
        ", [$this->user['company_id']]);
    }

    private function getRouteOptimization() {
        return $this->db->query("
            SELECT
                ro.*,
                u.first_name,
                u.last_name,
                ro.route_date,
                ro.total_stops,
                ro.total_distance,
                ro.estimated_travel_time,
                ro.actual_travel_time,
                ro.fuel_cost,
                ro.optimization_savings
            FROM route_optimization ro
            JOIN users u ON ro.technician_id = u.id
            WHERE ro.company_id = ?
            ORDER BY ro.route_date DESC
        ", [$this->user['company_id']]);
    }

    private function getScheduleConflicts() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                sc1.service_number as service_1,
                sc2.service_number as service_2,
                sc1.scheduled_date,
                sc1.scheduled_time as time_1,
                sc2.scheduled_time as time_2,
                TIMESTAMPDIFF(MINUTE, sc1.scheduled_time, sc2.scheduled_time) as time_overlap,
                sc1.service_location as location_1,
                sc2.service_location as location_2
            FROM users u
            JOIN service_calls sc1 ON u.id = sc1.technician_id
            JOIN service_calls sc2 ON u.id = sc2.technician_id
            WHERE u.company_id = ? AND sc1.id < sc2.id
                AND sc1.scheduled_date = sc2.scheduled_date
                AND sc1.status IN ('scheduled', 'confirmed', 'in_progress')
                AND sc2.status IN ('scheduled', 'confirmed', 'in_progress')
                AND (
                    (sc1.scheduled_time <= sc2.scheduled_time AND ADDTIME(sc1.scheduled_time, SEC_TO_TIME(sc1.estimated_duration * 60)) > sc2.scheduled_time) OR
                    (sc2.scheduled_time <= sc1.scheduled_time AND ADDTIME(sc2.scheduled_time, SEC_TO_TIME(sc2.estimated_duration * 60)) > sc1.scheduled_time)
                )
            ORDER BY sc1.scheduled_date DESC, u.last_name ASC
        ", [$this->user['company_id']]);
    }

    private function getSchedulingAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT u.id) as total_technicians,
                COUNT(sc.id) as total_scheduled_services,
                AVG(sc.estimated_duration) as avg_service_duration,
                COUNT(CASE WHEN sc.status = 'completed' THEN 1 END) as completed_services,
                ROUND((COUNT(CASE WHEN sc.status = 'completed' THEN 1 END) / NULLIF(COUNT(sc.id), 0)) * 100, 2) as on_time_completion_rate,
                AVG(TIMESTAMPDIFF(MINUTE, sc.scheduled_time, sc.actual_start_time)) as avg_delay_minutes,
                COUNT(CASE WHEN TIMESTAMPDIFF(MINUTE, sc.scheduled_time, sc.actual_start_time) > 15 THEN 1 END) as late_starts
            FROM users u
            LEFT JOIN service_calls sc ON u.id = sc.technician_id
            WHERE u.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSchedulingTemplates() {
        return $this->db->query("
            SELECT * FROM scheduling_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAppFeatures() {
        return [
            'service_management' => 'Service Call Management',
            'time_tracking' => 'Time Tracking',
            'parts_inventory' => 'Parts Inventory',
            'customer_communication' => 'Customer Communication',
            'route_optimization' => 'Route Optimization',
            'offline_mode' => 'Offline Mode',
            'photo_documentation' => 'Photo Documentation',
            'signature_capture' => 'Signature Capture',
            'gps_tracking' => 'GPS Tracking',
            'push_notifications' => 'Push Notifications'
        ];
    }

    private function getAppUsers() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                mau.device_type,
                mau.app_version,
                mau.last_login,
                mau.login_count,
                mau.offline_usage_hours,
                mau.data_sync_status
            FROM users u
            JOIN mobile_app_users mau ON u.id = mau.user_id
            WHERE u.company_id = ?
            ORDER BY mau.last_login DESC
        ", [$this->user['company_id']]);
    }

    private function getAppUsage() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(au.session_date, '%Y-%m') as month,
                COUNT(au.id) as total_sessions,
                COUNT(DISTINCT au.user_id) as active_users,
                AVG(au.session_duration) as avg_session_duration,
                SUM(au.data_transferred) as total_data_transferred,
                COUNT(CASE WHEN au.offline_mode = true THEN 1 END) as offline_sessions,
                AVG(au.battery_usage) as avg_battery_usage
            FROM app_usage au
            JOIN users u ON au.user_id = u.id
            WHERE u.company_id = ? AND au.session_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(au.session_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getAppPerformance() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT mau.user_id) as total_app_users,
                AVG(ap.load_time) as avg_app_load_time,
                COUNT(CASE WHEN ap.crash_count > 0 THEN 1 END) as users_with_crashes,
                AVG(ap.crash_count) as avg_crashes_per_user,
                AVG(ap.memory_usage) as avg_memory_usage,
                COUNT(CASE WHEN ap.offline_sync_errors > 0 THEN 1 END) as sync_error_users,
                AVG(ap.battery_impact) as avg_battery_impact
            FROM mobile_app_users mau
            LEFT JOIN app_performance ap ON mau.user_id = ap.user_id
            JOIN users u ON mau.user_id = u.id
            WHERE u.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getOfflineCapabilities() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                oc.offline_sessions,
                oc.offline_duration_hours,
                oc.data_stored_mb,
                oc.sync_success_rate,
                oc.last_sync_date,
                TIMESTAMPDIFF(DAY, oc.last_sync_date, CURDATE()) as days_since_sync
            FROM users u
            JOIN offline_capabilities oc ON u.id = oc.user_id
            WHERE u.company_id = ?
            ORDER BY oc.offline_duration_hours DESC
        ", [$this->user['company_id']]);
    }

    private function getAppNotifications() {
        return $this->db->query("
            SELECT
                an.*,
                u.first_name,
                u.last_name,
                an.notification_type,
                an.message,
                an.sent_at,
                an.delivered_at,
                an.opened_at,
                TIMESTAMPDIFF(MINUTE, an.sent_at, an.delivered_at) as delivery_time_minutes
            FROM app_notifications an
            JOIN users u ON an.user_id = u.id
            WHERE an.company_id = ?
            ORDER BY an.sent_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAppAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(an.id) as total_notifications,
                COUNT(CASE WHEN an.delivered_at IS NOT NULL THEN 1 END) as delivered_notifications,
                COUNT(CASE WHEN an.opened_at IS NOT NULL THEN 1 END) as opened_notifications,
                ROUND((COUNT(CASE WHEN an.opened_at IS NOT NULL THEN 1 END) / NULLIF(COUNT(CASE WHEN an.delivered_at IS NOT NULL THEN 1 END), 0)) * 100, 2) as open_rate,
                AVG(TIMESTAMPDIFF(MINUTE, an.sent_at, an.delivered_at)) as avg_delivery_time,
                COUNT(DISTINCT an.user_id) as active_notification_users
            FROM app_notifications an
            JOIN users u ON an.user_id = u.id
            WHERE u.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAppSettings() {
        return $this->db->querySingle("
            SELECT * FROM app_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCommunicationHistory() {
        return $this->db->query("
            SELECT
                ch.*,
                c.customer_name,
                u.first_name as technician_first,
                u.last_name as technician_last,
                ch.communication_type,
                ch.subject,
                ch.message,
                ch.sent_at,
                ch.delivered_at,
                ch.read_at
            FROM communication_history ch
            JOIN customers c ON ch.customer_id = c.id
            LEFT JOIN users u ON ch.technician_id = u.id
            WHERE ch.company_id = ?
            ORDER BY ch.sent_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCommunicationTemplates() {
        return $this->db->query("
            SELECT * FROM communication_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getCustomerFeedback() {
        return $this->db->query("
            SELECT
                cf.*,
                sc.service_number,
                c.customer_name,
                cf.rating,
                cf.feedback_text,
                cf.feedback_date,
                cf.response_text,
                cf.response_date
            FROM customer_feedback cf
            JOIN service_calls sc ON cf.service_call_id = sc.id
            JOIN customers c ON cf.customer_id = c.id
            WHERE cf.company_id = ?
            ORDER BY cf.feedback_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAppointmentReminders() {
        return $this->db->query("
            SELECT
                ar.*,
                sc.service_number,
                c.customer_name,
                c.customer_phone,
                c.customer_email,
                ar.reminder_type,
                ar.scheduled_time,
                ar.sent_at,
                ar.delivery_status
            FROM appointment_reminders ar
            JOIN service_calls sc ON ar.service_call_id = sc.id
            JOIN customers c ON sc.customer_id = c.id
            WHERE ar.company_id = ?
            ORDER BY ar.scheduled_time DESC
        ", [$this->user['company_id']]);
    }

    private function getServiceUpdates() {
        return $this->db->
