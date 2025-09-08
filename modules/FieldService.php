<?php
/**
 * TPT Free ERP - Field Service Management Module
 * Complete field service operations, technician management, and mobile workforce solutions
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
            'title' => 'Field Service Management Dashboard',
            'service_overview' => $this->getServiceOverview(),
            'service_metrics' => $this->getServiceMetrics(),
            'pending_service_calls' => $this->getPendingServiceCalls(),
            'technician_schedule' => $this->getTechnicianSchedule(),
            'service_alerts' => $this->getServiceAlerts(),
            'performance_metrics' => $this->getPerformanceMetrics()
        ];

        $this->render('modules/field_service/dashboard', $data);
    }

    /**
     * Service call management
     */
    public function serviceCalls() {
        $this->requirePermission('field_service.service_calls.view');

        $data = [
            'title' => 'Service Call Management',
            'service_calls' => $this->getServiceCalls(),
            'service_call_statuses' => $this->getServiceCallStatuses(),
            'service_call_priorities' => $this->getServiceCallPriorities(),
            'service_call_types' => $this->getServiceCallTypes(),
            'service_call_filters' => $this->getServiceCallFilters()
        ];

        $this->render('modules/field_service/service_calls', $data);
    }

    /**
     * Technician management
     */
    public function technicians() {
        $this->requirePermission('field_service.technicians.view');

        $data = [
            'title' => 'Technician Management',
            'technicians' => $this->getTechnicians(),
            'technician_skills' => $this->getTechnicianSkills(),
            'technician_schedule' => $this->getTechnicianSchedule(),
            'technician_performance' => $this->getTechnicianPerformance(),
            'technician_locations' => $this->getTechnicianLocations()
        ];

        $this->render('modules/field_service/technicians', $data);
    }

    /**
     * Scheduling and dispatch
     */
    public function scheduling() {
        $this->requirePermission('field_service.scheduling.view');

        $data = [
            'title' => 'Scheduling & Dispatch',
            'dispatch_board' => $this->getDispatchBoard(),
            'appointment_schedule' => $this->getAppointmentSchedule(),
            'resource_allocation' => $this->getResourceAllocation(),
            'route_optimization' => $this->getRouteOptimization(),
            'scheduling_rules' => $this->getSchedulingRules()
        ];

        $this->render('modules/field_service/scheduling', $data);
    }

    /**
     * Mobile technician app
     */
    public function mobileApp() {
        $this->requirePermission('field_service.mobile.view');

        $data = [
            'title' => 'Mobile Technician App',
            'mobile_features' => $this->getMobileFeatures(),
            'app_configuration' => $this->getAppConfiguration(),
            'offline_capabilities' => $this->getOfflineCapabilities(),
            'mobile_sync' => $this->getMobileSync(),
            'app_analytics' => $this->getAppAnalytics()
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
            'customer_notifications' => $this->getCustomerNotifications(),
            'communication_templates' => $this->getCommunicationTemplates(),
            'feedback_system' => $this->getFeedbackSystem(),
            'customer_portal' => $this->getCustomerPortal(),
            'communication_analytics' => $this->getCommunicationAnalytics()
        ];

        $this->render('modules/field_service/customer_communication', $data);
    }

    /**
     * Service history and analytics
     */
    public function serviceHistory() {
        $this->requirePermission('field_service.history.view');

        $data = [
            'title' => 'Service History & Analytics',
            'service_history' => $this->getServiceHistory(),
            'service_analytics' => $this->getServiceAnalytics(),
            'customer_history' => $this->getCustomerHistory(),
            'equipment_history' => $this->getEquipmentHistory(),
            'performance_trends' => $this->getPerformanceTrends()
        ];

        $this->render('modules/field_service/service_history', $data);
    }

    /**
     * Parts and inventory management
     */
    public function partsManagement() {
        $this->requirePermission('field_service.parts.view');

        $data = [
            'title' => 'Parts & Inventory Management',
            'parts_catalog' => $this->getPartsCatalog(),
            'parts_inventory' => $this->getPartsInventory(),
            'parts_orders' => $this->getPartsOrders(),
            'parts_usage' => $this->getPartsUsage(),
            'parts_analytics' => $this->getPartsAnalytics()
        ];

        $this->render('modules/field_service/parts_management', $data);
    }

    /**
     * Work order management
     */
    public function workOrders() {
        $this->requirePermission('field_service.work_orders.view');

        $data = [
            'title' => 'Work Order Management',
            'work_orders' => $this->getWorkOrders(),
            'work_order_templates' => $this->getWorkOrderTemplates(),
            'work_order_status' => $this->getWorkOrderStatus(),
            'work_order_priorities' => $this->getWorkOrderPriorities(),
            'work_order_analytics' => $this->getWorkOrderAnalytics()
        ];

        $this->render('modules/field_service/work_orders', $data);
    }

    /**
     * Reporting and analytics
     */
    public function reports() {
        $this->requirePermission('field_service.reports.view');

        $data = [
            'title' => 'Field Service Reports & Analytics',
            'service_reports' => $this->getServiceReports(),
            'technician_reports' => $this->getTechnicianReports(),
            'customer_reports' => $this->getCustomerReports(),
            'performance_reports' => $this->getPerformanceReports(),
            'custom_reports' => $this->getCustomReports()
        ];

        $this->render('modules/field_service/reports', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getServiceOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_service_calls,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_calls,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_calls,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_calls,
                COUNT(CASE WHEN priority = 'critical' THEN 1 END) as critical_calls,
                COUNT(CASE WHEN scheduled_date < CURDATE() AND status != 'completed' THEN 1 END) as overdue_calls,
                COUNT(DISTINCT technician_id) as active_technicians,
                AVG(customer_satisfaction_rating) as avg_satisfaction
            FROM service_calls
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getServiceMetrics() {
        return [
            'first_time_resolution_rate' => $this->calculateFirstTimeResolutionRate(),
            'average_response_time' => $this->calculateAverageResponseTime(),
            'average_resolution_time' => $this->calculateAverageResolutionTime(),
            'customer_satisfaction_score' => $this->calculateCustomerSatisfactionScore(),
            'technician_utilization_rate' => $this->calculateTechnicianUtilizationRate(),
            'service_call_completion_rate' => $this->calculateServiceCallCompletionRate()
        ];
    }

    private function calculateFirstTimeResolutionRate() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN resolution_type = 'first_visit' THEN 1 END) as first_time_resolutions,
                COUNT(*) as total_resolutions
            FROM service_calls
            WHERE company_id = ? AND status = 'completed' AND resolution_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['total_resolutions'] > 0 ? ($result['first_time_resolutions'] / $result['total_resolutions']) * 100 : 0;
    }

    private function calculateAverageResponseTime() {
        $result = $this->db->querySingle("
            SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) as avg_response_hours
            FROM service_calls
            WHERE company_id = ? AND first_response_at IS NOT NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['avg_response_hours'] ?? 0;
    }

    private function calculateAverageResolutionTime() {
        $result = $this->db->querySingle("
            SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolution_date)) as avg_resolution_hours
            FROM service_calls
            WHERE company_id = ? AND resolution_date IS NOT NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['avg_resolution_hours'] ?? 0;
    }

    private function calculateCustomerSatisfactionScore() {
        $result = $this->db->querySingle("
            SELECT AVG(customer_satisfaction_rating) as avg_satisfaction
            FROM service_calls
            WHERE company_id = ? AND customer_satisfaction_rating IS NOT NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ", [$this->user['company_id']]);

        return $result['avg_satisfaction'] ?? 0;
    }

    private function calculateTechnicianUtilizationRate() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(DISTINCT CASE WHEN status = 'assigned' THEN technician_id END) as active_technicians,
                COUNT(DISTINCT technician_id) as total_technicians
            FROM service_calls sc
            JOIN technicians t ON sc.technician_id = t.id
            WHERE sc.company_id = ? AND sc.scheduled_date >= CURDATE()
        ", [$this->user['company_id']]);

        return $result['total_technicians'] > 0 ? ($result['active_technicians'] / $result['total_technicians']) * 100 : 0;
    }

    private function calculateServiceCallCompletionRate() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_calls,
                COUNT(*) as total_calls
            FROM service_calls
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['total_calls'] > 0 ? ($result['completed_calls'] / $result['total_calls']) * 100 : 0;
    }

    private function getPendingServiceCalls() {
        return $this->db->query("
            SELECT
                sc.*,
                sc.service_call_number,
                sc.problem_description,
                sc.priority,
                sc.status,
                sc.scheduled_date,
                sc.customer_name,
                sc.customer_address,
                t.first_name as technician_first_name,
                t.last_name as technician_last_name,
                DATEDIFF(sc.scheduled_date, CURDATE()) as days_until_due
            FROM service_calls sc
            LEFT JOIN technicians t ON sc.technician_id = t.id
            WHERE sc.company_id = ? AND sc.status IN ('pending', 'scheduled')
            ORDER BY sc.priority DESC, sc.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getTechnicianSchedule() {
        return $this->db->query("
            SELECT
                t.first_name,
                t.last_name,
                COUNT(sc.id) as assigned_calls,
                COUNT(CASE WHEN sc.status = 'completed' THEN 1 END) as completed_calls,
                SUM(sc.estimated_duration) as total_estimated_hours,
                GROUP_CONCAT(DISTINCT sc.scheduled_date ORDER BY sc.scheduled_date) as scheduled_dates
            FROM technicians t
            LEFT JOIN service_calls sc ON t.id = sc.technician_id AND sc.scheduled_date >= CURDATE()
            WHERE t.company_id = ?
            GROUP BY t.id, t.first_name, t.last_name
            ORDER BY assigned_calls DESC
        ", [$this->user['company_id']]);
    }

    private function getServiceAlerts() {
        return $this->db->query("
            SELECT
                sa.*,
                sa.alert_type,
                sa.severity,
                sa.message,
                sa.created_at,
                sa.status,
                sc.service_call_number,
                t.first_name as technician_name
            FROM service_alerts sa
            LEFT JOIN service_calls sc ON sa.service_call_id = sc.id
            LEFT JOIN technicians t ON sa.technician_id = t.id
            WHERE sa.company_id = ? AND sa.status = 'active'
            ORDER BY sa.severity DESC, sa.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceMetrics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_calls,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_calls,
                AVG(customer_satisfaction_rating) as avg_satisfaction,
                AVG(TIMESTAMPDIFF(HOUR, created_at, resolution_date)) as avg_resolution_time
            FROM service_calls
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getServiceCalls() {
        return $this->db->query("
            SELECT
                sc.*,
                sc.service_call_number,
                sc.problem_description,
                sc.priority,
                sc.status,
                sc.scheduled_date,
                sc.customer_name,
                sc.customer_address,
                sc.customer_phone,
                sc.customer_email,
                t.first_name as technician_first_name,
                t.last_name as technician_last_name,
                eq.equipment_name,
                eq.serial_number
            FROM service_calls sc
            LEFT JOIN technicians t ON sc.technician_id = t.id
            LEFT JOIN equipment eq ON sc.equipment_id = eq.id
            WHERE sc.company_id = ?
            ORDER BY sc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getServiceCallStatuses() {
        return [
            'pending' => 'Pending',
            'scheduled' => 'Scheduled',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];
    }

    private function getServiceCallPriorities() {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical'
        ];
    }

    private function getServiceCallTypes() {
        return [
            'repair' => 'Equipment Repair',
            'maintenance' => 'Preventive Maintenance',
            'installation' => 'Equipment Installation',
            'inspection' => 'Safety Inspection',
            'training' => 'Customer Training',
            'warranty' => 'Warranty Service',
            'emergency' => 'Emergency Service'
        ];
    }

    private function getServiceCallFilters() {
        return [
            'statuses' => $this->getServiceCallStatuses(),
            'priorities' => $this->getServiceCallPriorities(),
            'types' => $this->getServiceCallTypes(),
            'date_ranges' => [
                'today' => 'Today',
                'week' => 'This Week',
                'month' => 'This Month',
                'overdue' => 'Overdue'
            ]
        ];
    }

    private function getTechnicians() {
        return $this->db->query("
            SELECT
                t.*,
                t.first_name,
                t.last_name,
                t.email,
                t.phone,
                t.specialization,
                t.employment_status,
                COUNT(sc.id) as total_service_calls,
                COUNT(CASE WHEN sc.status = 'completed' THEN 1 END) as completed_calls,
                AVG(sc.customer_satisfaction_rating) as avg_satisfaction,
                t.last_location_update
            FROM technicians t
            LEFT JOIN service_calls sc ON t.id = sc.technician_id
            WHERE t.company_id = ?
            GROUP BY t.id
            ORDER BY t.last_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTechnicianSkills() {
        return $this->db->query("
            SELECT
                ts.*,
                ts.skill_name,
                ts.proficiency_level,
                COUNT(t.id) as technicians_with_skill
            FROM technician_skills ts
            LEFT JOIN technicians t ON ts.technician_id = t.id
            WHERE ts.company_id = ?
            GROUP BY ts.skill_name, ts.proficiency_level
            ORDER BY ts.skill_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTechnicianPerformance() {
        return $this->db->query("
            SELECT
                t.first_name,
                t.last_name,
                COUNT(sc.id) as total_calls,
                COUNT(CASE WHEN sc.status = 'completed' THEN 1 END) as completed_calls,
                ROUND((COUNT(CASE WHEN sc.status = 'completed' THEN 1 END) / COUNT(sc.id)) * 100, 2) as completion_rate,
                AVG(sc.customer_satisfaction_rating) as avg_satisfaction,
                AVG(TIMESTAMPDIFF(HOUR, sc.created_at, sc.resolution_date)) as avg_resolution_time
            FROM technicians t
            LEFT JOIN service_calls sc ON t.id = sc.technician_id
            WHERE t.company_id = ? AND sc.created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            GROUP BY t.id, t.first_name, t.last_name
            ORDER BY completion_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getTechnicianLocations() {
        return $this->db->query("
            SELECT
                t.first_name,
                t.last_name,
                t.current_latitude,
                t.current_longitude,
                t.last_location_update,
                TIMESTAMPDIFF(MINUTE, t.last_location_update, NOW()) as minutes_since_update,
                COUNT(sc.id) as active_assignments
            FROM technicians t
            LEFT JOIN service_calls sc ON t.id = sc.technician_id AND sc.status IN ('assigned', 'in_progress')
            WHERE t.company_id = ? AND t.location_tracking_enabled = true
            GROUP BY t.id, t.first_name, t.last_name, t.current_latitude, t.current_longitude, t.last_location_update
            ORDER BY t.last_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDispatchBoard() {
        return $this->db->query("
            SELECT
                sc.service_call_number,
                sc.customer_name,
                sc.customer_address,
                sc.priority,
                sc.scheduled_date,
                sc.estimated_duration,
                t.first_name as technician_first_name,
                t.last_name as technician_last_name,
                t.current_latitude,
                t.current_longitude,
                sc.status
            FROM service_calls sc
            LEFT JOIN technicians t ON sc.technician_id = t.id
            WHERE sc.company_id = ? AND sc.status IN ('pending', 'scheduled', 'assigned', 'in_progress')
            ORDER BY sc.priority DESC, sc.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getAppointmentSchedule() {
        return $this->db->query("
            SELECT
                sc.scheduled_date,
                sc.service_call_number,
                sc.customer_name,
                sc.customer_address,
                sc.estimated_duration,
                t.first_name as technician_first_name,
                t.last_name as technician_last_name,
                sc.priority,
                sc.status
            FROM service_calls sc
            LEFT JOIN technicians t ON sc.technician_id = t.id
            WHERE sc.company_id = ? AND sc.scheduled_date >= CURDATE()
            ORDER BY sc.scheduled_date ASC, sc.priority DESC
        ", [$this->user['company_id']]);
    }

    private function getResourceAllocation() {
        return $this->db->query("
            SELECT
                t.first_name,
                t.last_name,
                COUNT(sc.id) as assigned_calls,
                SUM(sc.estimated_duration) as total_estimated_hours,
                t.max_daily_hours,
                ROUND((SUM(sc.estimated_duration) / t.max_daily_hours) * 100, 2) as utilization_percentage
            FROM technicians t
            LEFT JOIN service_calls sc ON t.id = sc.technician_id AND sc.scheduled_date = CURDATE()
            WHERE t.company_id = ?
            GROUP BY t.id, t.first_name, t.last_name, t.max_daily_hours
            ORDER BY utilization_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getRouteOptimization() {
        return $this->db->query("
            SELECT
                t.first_name,
                t.last_name,
                COUNT(sc.id) as daily_calls,
                GROUP_CONCAT(sc.customer_address ORDER BY sc.scheduled_date) as route_addresses,
                SUM(sc.estimated_duration) as total_duration,
                t.base_location_address
            FROM technicians t
            LEFT JOIN service_calls sc ON t.id = sc.technician_id AND DATE(sc.scheduled_date) = CURDATE()
            WHERE t.company_id = ?
            GROUP BY t.id, t.first_name, t.last_name, t.base_location_address
            ORDER BY t.last_name ASC
        ", [$this->user['company_id']]);
    }

    private function getSchedulingRules() {
        return [
            'max_daily_hours' => 8,
            'max_consecutive_days' => 5,
            'min_break_between_calls' => 30, // minutes
            'max_travel_time' => 120, // minutes
            'skill_matching_required' => true,
            'geographic_zones' => true
        ];
    }

    private function getMobileFeatures() {
        return [
            'offline_mode' => true,
            'gps_tracking' => true,
            'photo_capture' => true,
            'signature_capture' => true,
            'parts_tracking' => true,
            'time_tracking' => true,
            'customer_communication' => true,
            'work_order_updates' => true
        ];
    }

    private function getAppConfiguration() {
        return $this->db->querySingle("
            SELECT
                offline_sync_enabled,
                gps_tracking_enabled,
                photo_capture_enabled,
                auto_check_in_enabled,
                customer_signature_required,
                parts_tracking_enabled,
                time_tracking_enabled
            FROM mobile_app_config
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getOfflineCapabilities() {
        return [
            'max_offline_days' => 7,
            'auto_sync_frequency' => 15, // minutes
            'conflict_resolution' => 'manual',
            'data_compression' => true,
            'low_bandwidth_mode' => true
        ];
    }

    private function getMobileSync() {
        return $this->db->query("
            SELECT
                device_id,
                last_sync_time,
                sync_status,
                pending_uploads,
                device_type,
                app_version
            FROM mobile_sync_status
            WHERE company_id = ?
            ORDER BY last_sync_time DESC
        ", [$this->user['company_id']]);
    }

    private function getAppAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(DISTINCT device_id) as active_devices,
                AVG(session_duration) as avg_session_duration,
                COUNT(CASE WHEN sync_status = 'success' THEN 1 END) as successful_syncs,
                COUNT(CASE WHEN sync_status = 'failed' THEN 1 END) as failed_syncs
            FROM mobile_app_analytics
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerNotifications() {
        return $this->db->query("
            SELECT
                cn.*,
                cn.notification_type,
                cn.sent_at,
                cn.delivery_status,
                sc.service_call_number,
                sc.customer_name
            FROM customer_notifications cn
            LEFT JOIN service_calls sc ON cn.service_call_id = sc.id
            WHERE cn.company_id = ?
            ORDER BY cn.sent_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCommunicationTemplates() {
        return $this->db->query("
            SELECT
                ct.*,
                ct.template_name,
                ct.template_type,
                ct.subject,
                ct.is_active,
                COUNT(cn.id) as usage_count
            FROM communication_templates ct
            LEFT JOIN customer_notifications cn ON ct.id = cn.template_id
            WHERE ct.company_id = ?
            GROUP BY ct.id
            ORDER BY ct.template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getFeedbackSystem() {
        return $this->db->query("
            SELECT
                cf.*,
                cf.rating,
                cf.feedback_text,
                cf.created_at,
                sc.service_call_number,
                sc.customer_name,
                t.first_name as technician_name
            FROM customer_feedback cf
            LEFT JOIN service_calls sc ON cf.service_call_id = sc.id
            LEFT JOIN technicians t ON sc.technician_id = t.id
            WHERE cf.company_id = ?
            ORDER BY cf.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerPortal() {
        return [
            'portal_enabled' => true,
            'features' => [
                'service_history' => true,
                'schedule_appointment' => true,
                'view_invoices' => true,
                'submit_feedback' => true,
                'track_technician' => true,
                'emergency_contact' => true
            ],
            'customization' => $this->getPortalCustomization()
        ];
    }

    private function getPortalCustomization() {
        return $this->db->querySingle("
            SELECT
                portal_title,
                primary_color,
                logo_url,
                welcome_message,
                contact_information
            FROM customer_portal_config
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCommunicationAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(sent_at, '%Y-%m') as month,
                COUNT(*) as total_notifications,
                COUNT(CASE WHEN delivery_status = 'delivered' THEN 1 END) as delivered,
                COUNT(CASE WHEN delivery_status = 'opened' THEN 1 END) as opened,
                ROUND((COUNT(CASE WHEN delivery_status = 'opened' THEN 1 END) / COUNT(*)) * 100, 2) as open_rate
            FROM customer_notifications
            WHERE company_id = ? AND sent_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(sent_at, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getServiceHistory() {
        return $this->db->query("
            SELECT
                sc.service_call_number,
                sc.customer_name,
                sc.problem_description,
                sc.resolution_description,
                sc.created_at,
                sc.resolution_date,
                sc.customer_satisfaction_rating,
                t.first_name as technician_name,
                TIMESTAMPDIFF(HOUR, sc.created_at, sc.resolution_date) as resolution_time_hours
            FROM service_calls sc
            LEFT JOIN technicians t ON sc.technician_id = t.id
            WHERE sc.company_id = ? AND sc.status = 'completed'
            ORDER BY sc.resolution_date DESC
        ", [$this->user['company_id']]);
    }

    private function getServiceAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_calls,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_calls,
                AVG(customer_satisfaction_rating) as avg_satisfaction,
                AVG(TIMESTAMPDIFF(HOUR, created_at, resolution_date)) as avg_resolution_time,
                COUNT(DISTINCT customer_id) as unique_customers
            FROM service_calls
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomerHistory() {
        return $this->db->query("
            SELECT
                customer_name,
                COUNT(*) as total_calls,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_calls,
                AVG(customer_satisfaction_rating) as avg_satisfaction,
                MAX(created_at) as last_service_date,
                SUM(total_cost) as total_spent
            FROM service_calls
            WHERE company_id = ?
            GROUP BY customer_name
            ORDER BY total_calls DESC
        ", [$this->user['company_id']]);
    }

    private function getEquipmentHistory() {
        return $this->db->query("
            SELECT
                eq.equipment_name,
                eq.serial_number,
                COUNT(sc.id) as service_count,
                MAX(sc.created_at) as last_service_date,
                AVG(sc.total_cost) as avg_service_cost,
                SUM(sc.total_cost) as total_service_cost
            FROM equipment eq
            LEFT JOIN service_calls sc ON eq.id = sc.equipment_id
            WHERE eq.company_id = ?
            GROUP BY eq.id, eq.equipment_name, eq.serial_number
            ORDER BY service_count DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceTrends() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                AVG(TIMESTAMPDIFF(HOUR, created_at, resolution_date)) as avg_resolution_time,
                AVG(customer_satisfaction_rating) as avg_satisfaction,
                COUNT(CASE WHEN resolution_type = 'first_visit' THEN 1 END) / COUNT(*) * 100 as first_time_resolution_rate,
                COUNT(*) as call_volume
            FROM service_calls
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getPartsCatalog() {
        return $this->db->query("
            SELECT
                pc.*,
                pc.part_number,
                pc.part_name,
                pc.description,
                pc.unit_cost,
                pc.compatible_equipment,
                COUNT(pi.id) as inventory_count
            FROM parts_catalog pc
            LEFT JOIN parts_inventory pi ON pc.id = pi.part_id
            WHERE pc.company_id = ?
            GROUP BY pc.id
            ORDER BY pc.part_name ASC
        ", [$this->user['company_id']]);
    }

    private function getPartsInventory() {
        return $this->db->query("
            SELECT
                pi.*,
                pi.quantity
