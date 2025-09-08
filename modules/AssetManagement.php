<?php
/**
 * TPT Free ERP - Asset Management Module
 * Complete asset tracking, maintenance, and lifecycle management system
 */

class AssetManagement extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main asset management dashboard
     */
    public function index() {
        $this->requirePermission('assets.view');

        $data = [
            'title' => 'Asset Management Dashboard',
            'asset_overview' => $this->getAssetOverview(),
            'asset_metrics' => $this->getAssetMetrics(),
            'recent_assets' => $this->getRecentAssets(),
            'maintenance_schedule' => $this->getMaintenanceSchedule(),
            'depreciation_summary' => $this->getDepreciationSummary(),
            'asset_alerts' => $this->getAssetAlerts()
        ];

        $this->render('modules/assets/dashboard', $data);
    }

    /**
     * Asset catalog and inventory
     */
    public function assetCatalog() {
        $this->requirePermission('assets.view');

        $data = [
            'title' => 'Asset Catalog',
            'assets' => $this->getAssets(),
            'asset_categories' => $this->getAssetCategories(),
            'asset_locations' => $this->getAssetLocations(),
            'asset_statuses' => $this->getAssetStatuses(),
            'filters' => $this->getAssetFilters()
        ];

        $this->render('modules/assets/catalog', $data);
    }

    /**
     * Asset lifecycle management
     */
    public function assetLifecycle() {
        $this->requirePermission('assets.lifecycle.view');

        $data = [
            'title' => 'Asset Lifecycle Management',
            'lifecycle_stages' => $this->getLifecycleStages(),
            'asset_transitions' => $this->getAssetTransitions(),
            'retirement_schedule' => $this->getRetirementSchedule(),
            'disposal_records' => $this->getDisposalRecords(),
            'lifecycle_metrics' => $this->getLifecycleMetrics()
        ];

        $this->render('modules/assets/lifecycle', $data);
    }

    /**
     * Maintenance management
     */
    public function maintenance() {
        $this->requirePermission('assets.maintenance.view');

        $data = [
            'title' => 'Maintenance Management',
            'maintenance_schedule' => $this->getMaintenanceSchedule(),
            'maintenance_history' => $this->getMaintenanceHistory(),
            'preventive_maintenance' => $this->getPreventiveMaintenance(),
            'maintenance_work_orders' => $this->getMaintenanceWorkOrders(),
            'maintenance_costs' => $this->getMaintenanceCosts(),
            'maintenance_metrics' => $this->getMaintenanceMetrics()
        ];

        $this->render('modules/assets/maintenance', $data);
    }

    /**
     * Depreciation tracking
     */
    public function depreciation() {
        $this->requirePermission('assets.depreciation.view');

        $data = [
            'title' => 'Depreciation Tracking',
            'depreciation_schedule' => $this->getDepreciationSchedule(),
            'depreciation_methods' => $this->getDepreciationMethods(),
            'depreciation_entries' => $this->getDepreciationEntries(),
            'asset_values' => $this->getAssetValues(),
            'depreciation_reports' => $this->getDepreciationReports(),
            'depreciation_metrics' => $this->getDepreciationMetrics()
        ];

        $this->render('modules/assets/depreciation', $data);
    }

    /**
     * Asset tracking and location
     */
    public function assetTracking() {
        $this->requirePermission('assets.tracking.view');

        $data = [
            'title' => 'Asset Tracking',
            'asset_locations' => $this->getAssetLocations(),
            'location_history' => $this->getLocationHistory(),
            'asset_movements' => $this->getAssetMovements(),
            'geofencing' => $this->getGeofencingData(),
            'tracking_metrics' => $this->getTrackingMetrics()
        ];

        $this->render('modules/assets/tracking', $data);
    }

    /**
     * Compliance and insurance
     */
    public function compliance() {
        $this->requirePermission('assets.compliance.view');

        $data = [
            'title' => 'Compliance & Insurance',
            'compliance_requirements' => $this->getComplianceRequirements(),
            'insurance_policies' => $this->getInsurancePolicies(),
            'audit_trail' => $this->getAuditTrail(),
            'regulatory_reports' => $this->getRegulatoryReports(),
            'compliance_alerts' => $this->getComplianceAlerts(),
            'compliance_metrics' => $this->getComplianceMetrics()
        ];

        $this->render('modules/assets/compliance', $data);
    }

    /**
     * Asset reporting and analytics
     */
    public function reports() {
        $this->requirePermission('assets.reports.view');

        $data = [
            'title' => 'Asset Reports & Analytics',
            'asset_reports' => $this->getAssetReports(),
            'utilization_reports' => $this->getUtilizationReports(),
            'cost_analysis' => $this->getCostAnalysis(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'predictive_analytics' => $this->getPredictiveAnalytics(),
            'custom_reports' => $this->getCustomReports()
        ];

        $this->render('modules/assets/reports', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getAssetOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_assets,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_assets,
                COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_assets,
                COUNT(CASE WHEN status = 'retired' THEN 1 END) as retired_assets,
                SUM(acquisition_cost) as total_acquisition_value,
                SUM(current_value) as total_current_value,
                COUNT(CASE WHEN next_maintenance_date <= CURDATE() THEN 1 END) as overdue_maintenance,
                COUNT(CASE WHEN insurance_expiry <= CURDATE() THEN 1 END) as expired_insurance
            FROM assets
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAssetMetrics() {
        return [
            'asset_utilization_rate' => $this->calculateAssetUtilizationRate(),
            'maintenance_cost_ratio' => $this->calculateMaintenanceCostRatio(),
            'depreciation_rate' => $this->calculateDepreciationRate(),
            'asset_turnover_ratio' => $this->calculateAssetTurnoverRatio(),
            'return_on_assets' => $this->calculateReturnOnAssets(),
            'asset_lifecycle_efficiency' => $this->calculateAssetLifecycleEfficiency()
        ];
    }

    private function calculateAssetUtilizationRate() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN utilization_status = 'in_use' THEN 1 END) as in_use,
                COUNT(*) as total
            FROM assets
            WHERE company_id = ? AND status = 'active'
        ", [$this->user['company_id']]);

        return $result['total'] > 0 ? ($result['in_use'] / $result['total']) * 100 : 0;
    }

    private function calculateMaintenanceCostRatio() {
        $result = $this->db->querySingle("
            SELECT
                SUM(cost) as maintenance_cost,
                SUM(acquisition_cost) as total_asset_value
            FROM maintenance_records mr
            JOIN assets a ON mr.asset_id = a.id
            WHERE a.company_id = ? AND mr.maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        ", [$this->user['company_id']]);

        return $result['total_asset_value'] > 0 ? ($result['maintenance_cost'] / $result['total_asset_value']) * 100 : 0;
    }

    private function calculateDepreciationRate() {
        $result = $this->db->querySingle("
            SELECT
                AVG((acquisition_cost - current_value) / acquisition_cost * 100) as avg_depreciation_rate
            FROM assets
            WHERE company_id = ? AND status = 'active' AND acquisition_cost > 0
        ", [$this->user['company_id']]);

        return $result['avg_depreciation_rate'] ?? 0;
    }

    private function calculateAssetTurnoverRatio() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'retired' AND retirement_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 END) as retired_assets,
                COUNT(*) as total_assets
            FROM assets
            WHERE company_id = ?
        ", [$this->user['company_id']]);

        return $result['total_assets'] > 0 ? ($result['retired_assets'] / $result['total_assets']) * 100 : 0;
    }

    private function calculateReturnOnAssets() {
        $result = $this->db->querySingle("
            SELECT
                SUM(revenue_generated) as total_revenue,
                SUM(acquisition_cost) as total_asset_value
            FROM assets
            WHERE company_id = ? AND status = 'active'
        ", [$this->user['company_id']]);

        return $result['total_asset_value'] > 0 ? ($result['total_revenue'] / $result['total_asset_value']) * 100 : 0;
    }

    private function calculateAssetLifecycleEfficiency() {
        $result = $this->db->querySingle("
            SELECT
                AVG(DATEDIFF(retirement_date, acquisition_date) / 365) as avg_lifecycle_years,
                AVG(utilization_rate) as avg_utilization
            FROM assets
            WHERE company_id = ? AND retirement_date IS NOT NULL
        ", [$this->user['company_id']]);

        return ($result['avg_lifecycle_years'] ?? 0) * ($result['avg_utilization'] ?? 0);
    }

    private function getRecentAssets() {
        return $this->db->query("
            SELECT
                a.*,
                a.asset_tag,
                a.asset_name,
                a.category,
                a.status,
                a.acquisition_date,
                a.acquisition_cost,
                l.location_name,
                u.first_name,
                u.last_name
            FROM assets a
            LEFT JOIN asset_locations l ON a.location_id = l.id
            LEFT JOIN users u ON a.assigned_to = u.id
            WHERE a.company_id = ?
            ORDER BY a.acquisition_date DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceSchedule() {
        return $this->db->query("
            SELECT
                ms.*,
                ms.asset_id,
                ms.maintenance_type,
                ms.scheduled_date,
                ms.priority,
                ms.status,
                a.asset_name,
                a.asset_tag,
                DATEDIFF(ms.scheduled_date, CURDATE()) as days_until_due
            FROM maintenance_schedule ms
            JOIN assets a ON ms.asset_id = a.id
            WHERE a.company_id = ? AND ms.status = 'scheduled'
            ORDER BY ms.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationSummary() {
        return $this->db->query("
            SELECT
                MONTH(depreciation_date) as month,
                YEAR(depreciation_date) as year,
                SUM(depreciation_amount) as monthly_depreciation,
                SUM(accumulated_depreciation) as total_accumulated
            FROM depreciation_entries
            WHERE company_id = ? AND depreciation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY YEAR(depreciation_date), MONTH(depreciation_date)
            ORDER BY year DESC, month DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetAlerts() {
        return $this->db->query("
            SELECT
                aa.*,
                aa.alert_type,
                aa.severity,
                aa.message,
                aa.created_at,
                aa.status,
                a.asset_name,
                a.asset_tag
            FROM asset_alerts aa
            LEFT JOIN assets a ON aa.asset_id = a.id
            WHERE aa.company_id = ? AND aa.status = 'active'
            ORDER BY aa.severity DESC, aa.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAssets() {
        return $this->db->query("
            SELECT
                a.*,
                a.asset_tag,
                a.asset_name,
                a.category,
                a.status,
                a.acquisition_cost,
                a.current_value,
                a.location_id,
                a.assigned_to,
                l.location_name,
                u.first_name,
                u.last_name,
                c.category_name
            FROM assets a
            LEFT JOIN asset_locations l ON a.location_id = l.id
            LEFT JOIN users u ON a.assigned_to = u.id
            LEFT JOIN asset_categories c ON a.category_id = c.id
            WHERE a.company_id = ?
            ORDER BY a.asset_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAssetCategories() {
        return $this->db->query("
            SELECT
                ac.*,
                ac.category_name,
                ac.description,
                COUNT(a.id) as asset_count
            FROM asset_categories ac
            LEFT JOIN assets a ON ac.id = a.category_id
            WHERE ac.company_id = ?
            GROUP BY ac.id
            ORDER BY ac.category_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAssetLocations() {
        return $this->db->query("
            SELECT
                al.*,
                al.location_name,
                al.address,
                al.capacity,
                COUNT(a.id) as asset_count
            FROM asset_locations al
            LEFT JOIN assets a ON al.id = a.location_id
            WHERE al.company_id = ?
            GROUP BY al.id
            ORDER BY al.location_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAssetStatuses() {
        return [
            'active' => 'Active',
            'maintenance' => 'Under Maintenance',
            'retired' => 'Retired',
            'disposed' => 'Disposed',
            'lost' => 'Lost/Stolen'
        ];
    }

    private function getAssetFilters() {
        return [
            'categories' => $this->getAssetCategories(),
            'locations' => $this->getAssetLocations(),
            'statuses' => $this->getAssetStatuses(),
            'value_ranges' => [
                '0-1000' => '$0 - $1,000',
                '1000-10000' => '$1,000 - $10,000',
                '10000-50000' => '$10,000 - $50,000',
                '50000+' => '$50,000+'
            ]
        ];
    }

    private function getLifecycleStages() {
        return [
            'planning' => 'Planning & Acquisition',
            'deployment' => 'Deployment & Installation',
            'operation' => 'Operation & Maintenance',
            'upgrade' => 'Upgrade & Enhancement',
            'retirement' => 'Retirement & Disposal'
        ];
    }

    private function getAssetTransitions() {
        return $this->db->query("
            SELECT
                at.*,
                at.asset_id,
                at.from_stage,
                at.to_stage,
                at.transition_date,
                at.reason,
                a.asset_name,
                a.asset_tag
            FROM asset_transitions at
            JOIN assets a ON at.asset_id = a.id
            WHERE a.company_id = ?
            ORDER BY at.transition_date DESC
        ", [$this->user['company_id']]);
    }

    private function getRetirementSchedule() {
        return $this->db->query("
            SELECT
                a.*,
                a.asset_name,
                a.asset_tag,
                a.expected_retirement_date,
                a.retirement_reason,
                DATEDIFF(a.expected_retirement_date, CURDATE()) as days_until_retirement
            FROM assets a
            WHERE a.company_id = ? AND a.expected_retirement_date IS NOT NULL
            AND a.status = 'active'
            ORDER BY a.expected_retirement_date ASC
        ", [$this->user['company_id']]);
    }

    private function getDisposalRecords() {
        return $this->db->query("
            SELECT
                dr.*,
                dr.asset_id,
                dr.disposal_date,
                dr.disposal_method,
                dr.disposal_value,
                dr.reason,
                a.asset_name,
                a.asset_tag
            FROM disposal_records dr
            JOIN assets a ON dr.asset_id = a.id
            WHERE a.company_id = ?
            ORDER BY dr.disposal_date DESC
        ", [$this->user['company_id']]);
    }

    private function getLifecycleMetrics() {
        return $this->db->querySingle("
            SELECT
                AVG(DATEDIFF(COALESCE(retirement_date, CURDATE()), acquisition_date)) as avg_lifecycle_days,
                COUNT(CASE WHEN retirement_date IS NOT NULL THEN 1 END) as retired_assets,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_assets,
                AVG(acquisition_cost) as avg_acquisition_cost
            FROM assets
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceHistory() {
        return $this->db->query("
            SELECT
                mr.*,
                mr.asset_id,
                mr.maintenance_date,
                mr.maintenance_type,
                mr.description,
                mr.cost,
                mr.technician,
                a.asset_name,
                a.asset_tag
            FROM maintenance_records mr
            JOIN assets a ON mr.asset_id = a.id
            WHERE a.company_id = ?
            ORDER BY mr.maintenance_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPreventiveMaintenance() {
        return $this->db->query("
            SELECT
                pm.*,
                pm.asset_id,
                pm.schedule_type,
                pm.frequency_days,
                pm.last_performed,
                pm.next_due,
                a.asset_name,
                a.asset_tag
            FROM preventive_maintenance pm
            JOIN assets a ON pm.asset_id = a.id
            WHERE a.company_id = ?
            ORDER BY pm.next_due ASC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceWorkOrders() {
        return $this->db->query("
            SELECT
                wo.*,
                wo.asset_id,
                wo.work_order_number,
                wo.priority,
                wo.status,
                wo.requested_date,
                wo.due_date,
                a.asset_name,
                a.asset_tag
            FROM maintenance_work_orders wo
            JOIN assets a ON wo.asset_id = a.id
            WHERE a.company_id = ?
            ORDER BY wo.priority DESC, wo.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceCosts() {
        return $this->db->query("
            SELECT
                MONTH(maintenance_date) as month,
                YEAR(maintenance_date) as year,
                SUM(cost) as monthly_cost,
                COUNT(*) as maintenance_count
            FROM maintenance_records
            WHERE company_id = ? AND maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY YEAR(maintenance_date), MONTH(maintenance_date)
            ORDER BY year DESC, month DESC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_maintenance,
                AVG(cost) as avg_cost,
                SUM(cost) as total_cost,
                COUNT(CASE WHEN priority = 'critical' THEN 1 END) as critical_maintenance,
                AVG(DATEDIFF(completion_date, requested_date)) as avg_completion_days
            FROM maintenance_records
            WHERE company_id = ? AND maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        ", [$this->user['company_id']]);
    }

    private function getDepreciationSchedule() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                a.acquisition_cost,
                a.useful_life_years,
                de.depreciation_date,
                de.depreciation_amount,
                de.accumulated_depreciation,
                a.current_value
            FROM assets a
            LEFT JOIN depreciation_entries de ON a.id = de.asset_id
            WHERE a.company_id = ? AND a.status = 'active'
            ORDER BY a.asset_name ASC, de.depreciation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationMethods() {
        return [
            'straight_line' => 'Straight Line',
            'declining_balance' => 'Declining Balance',
            'units_of_production' => 'Units of Production',
            'sum_of_years_digits' => 'Sum of Years Digits'
        ];
    }

    private function getDepreciationEntries() {
        return $this->db->query("
            SELECT
                de.*,
                de.asset_id,
                de.depreciation_date,
                de.depreciation_amount,
                de.accumulated_depreciation,
                de.book_value,
                a.asset_name,
                a.asset_tag
            FROM depreciation_entries de
            JOIN assets a ON de.asset_id = a.id
            WHERE a.company_id = ?
            ORDER BY de.depreciation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetValues() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                a.acquisition_cost,
                a.current_value,
                (a.acquisition_cost - a.current_value) as accumulated_depreciation,
                ROUND((a.current_value / a.acquisition_cost) * 100, 2) as remaining_value_percentage
            FROM assets a
            WHERE a.company_id = ? AND a.status = 'active'
            ORDER BY a.current_value DESC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationReports() {
        return $this->db->query("
            SELECT
                YEAR(depreciation_date) as year,
                MONTH(depreciation_date) as month,
                SUM(depreciation_amount) as total_depreciation,
                COUNT(DISTINCT asset_id) as assets_depreciated
            FROM depreciation_entries
            WHERE company_id = ?
            GROUP BY YEAR(depreciation_date), MONTH(depreciation_date)
            ORDER BY year DESC, month DESC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationMetrics() {
        return $this->db->querySingle("
            SELECT
                SUM(acquisition_cost) as total_acquisition_cost,
                SUM(current_value) as total_current_value,
                SUM(acquisition_cost - current_value) as total_accumulated_depreciation,
                AVG((acquisition_cost - current_value) / acquisition_cost * 100) as avg_depreciation_percentage
            FROM assets
            WHERE company_id = ? AND status = 'active'
        ", [$this->user['company_id']]);
    }

    private function getLocationHistory() {
        return $this->db->query("
            SELECT
                lh.*,
                lh.asset_id,
                lh.from_location_id,
                lh.to_location_id,
                lh.move_date,
                lh.reason,
                fl.location_name as from_location,
                tl.location_name as to_location,
                a.asset_name,
                a.asset_tag
            FROM location_history lh
            JOIN assets a ON lh.asset_id = a.id
            LEFT JOIN asset_locations fl ON lh.from_location_id = fl.id
            LEFT JOIN asset_locations tl ON lh.to_location_id = tl.id
            WHERE a.company_id = ?
            ORDER BY lh.move_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetMovements() {
        return $this->db->query("
            SELECT
                am.*,
                am.asset_id,
                am.movement_type,
                am.from_location,
                am.to_location,
                am.movement_date,
                am.authorized_by,
                a.asset_name,
                a.asset_tag
            FROM asset_movements am
            JOIN assets a ON am.asset_id = a.id
            WHERE a.company_id = ?
            ORDER BY am.movement_date DESC
        ", [$this->user['company_id']]);
    }

    private function getGeofencingData() {
        return $this->db->query("
            SELECT
                gf.*,
                gf.asset_id,
                gf.geofence_name,
                gf.latitude,
                gf.longitude,
                gf.radius,
                gf.alert_type,
                a.asset_name,
                a.asset_tag
            FROM geofencing gf
            JOIN assets a ON gf.asset_id = a.id
            WHERE a.company_id = ?
            ORDER BY gf.geofence_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTrackingMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT asset_id) as tracked_assets,
                COUNT(*) as total_movements,
                AVG(DATEDIFF(CURDATE(), last_location_update)) as avg_days_since_update,
                COUNT(CASE WHEN geofence_alerts > 0 THEN 1 END) as assets_with_alerts
            FROM asset_tracking
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getComplianceRequirements() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.requirement_name,
                cr.description,
                cr.frequency,
                cr.next_due_date,
                cr.status,
                COUNT(ca.id) as compliance_actions
            FROM compliance_requirements cr
            LEFT JOIN compliance_actions ca ON cr.id = ca.requirement_id
            WHERE cr.company_id = ?
            GROUP BY cr.id
            ORDER BY cr.next_due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getInsurancePolicies() {
        return $this->db->query("
            SELECT
                ip.*,
                ip.policy_number,
                ip.provider,
                ip.coverage_amount,
                ip.premium_amount,
                ip.expiry_date,
                ip.policy_type,
                COUNT(a.id) as covered_assets
            FROM insurance_policies ip
            LEFT JOIN asset_insurance ai ON ip.id = ai.policy_id
            LEFT JOIN assets a ON ai.asset_id = a.id
            WHERE ip.company_id = ?
            GROUP BY ip.id
            ORDER BY ip.expiry_date ASC
        ", [$this->user['company_id']]);
    }

    private function getAuditTrail() {
        return $this->db->query("
            SELECT
                at.*,
                at.asset_id,
                at.action_type,
                at.action_date,
                at.user_id,
                at.details,
                a.asset_name,
                a.asset_tag,
                u.first_name,
                u.last_name
            FROM asset_audit_trail at
            JOIN assets a ON at.asset_id = a.id
            JOIN users u ON at.user_id = u.id
            WHERE a.company_id = ?
            ORDER BY at.action_date DESC
        ", [$this->user['company_id']]);
    }

    private function getRegulatoryReports() {
        return $this->db->query("
            SELECT
                rr.*,
                rr.report_name,
                rr.report_type,
                rr.due_date,
                rr.submission_date,
                rr.status,
                rr.regulatory_body
            FROM regulatory_reports rr
            WHERE rr.company_id = ?
            ORDER BY rr.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceAlerts() {
        return $this->db->query("
            SELECT
                ca.*,
                ca.alert_type,
                ca.severity,
                ca.message,
                ca.created_at,
                ca.status,
                cr.requirement_name
            FROM compliance_alerts ca
            LEFT JOIN compliance_requirements cr ON ca.requirement_id = cr.id
            WHERE ca.company_id = ? AND ca.status = 'active'
            ORDER BY ca.severity DESC, ca.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'compliant' THEN 1 END) as compliant_requirements,
                COUNT(CASE WHEN status = 'non_compliant' THEN 1 END) as non_compliant_requirements,
                COUNT(CASE WHEN expiry_date <= CURDATE() THEN 1 END) as expired_policies,
                COUNT(CASE WHEN next_due_date <= CURDATE() THEN 1 END) as overdue_reports
            FROM compliance_requirements cr
            LEFT JOIN insurance_policies ip ON cr.company_id = ip.company_id
            LEFT JOIN regulatory_reports rr ON cr.company_id = rr.company_id
            WHERE cr.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAssetReports() {
        return [
            'asset_inventory' => $this->getAssetInventoryReport(),
            'asset_utilization' => $this->getAssetUtilizationReport(),
            'maintenance_summary' => $this->getMaintenanceSummaryReport(),
            'depreciation_summary' => $this->getDepreciationSummaryReport(),
            'compliance_status' => $this->getComplianceStatusReport()
        ];
    }

    private function getAssetInventoryReport() {
        return $this->db->query("
            SELECT
                ac.category_name,
                COUNT(a.id) as asset_count,
                SUM(a.acquisition_cost) as total_acquisition_cost,
                SUM(a.current_value) as total_current_value,
                AVG(a.acquisition_cost) as avg_cost_per_asset
            FROM assets a
            LEFT JOIN asset_categories ac ON a.category_id = ac.id
            WHERE a.company_id = ?
            GROUP BY ac.category_name
            ORDER BY total_acquisition_cost DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetUtilizationReport() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                a.utilization_rate,
                a.total_usage_hours,
                a.downtime_hours,
                ROUND((a.total_usage_hours / (a.total_usage_hours + a.downtime_hours)) * 100, 2) as availability_percentage
            FROM assets a
            WHERE a.company_id = ? AND a.status = 'active'
            ORDER BY a.utilization_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceSummaryReport() {
        return $this->db->query("
            SELECT
                a.asset_name,
                COUNT(mr.id) as maintenance_count,
                SUM(mr.cost) as total_maintenance_cost,
                AVG(mr.cost) as avg_maintenance_cost,
                MAX(mr.maintenance_date) as last_maintenance_date
            FROM assets a
            LEFT JOIN maintenance_records mr ON a.id = mr.asset_id
            WHERE a.company_id = ?
            GROUP BY a.id, a.asset_name
            ORDER BY total_maintenance_cost DESC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationSummaryReport() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.acquisition_cost,
                a.current_value,
                (a.acquisition_cost - a.current_value) as accumulated_depreciation,
                ROUND(((a.acquisition_cost - a.current_value) / a.acquisition_cost) * 100, 2) as depreciation_percentage
            FROM assets a
            WHERE a.company_id = ? AND a.status = 'active'
            ORDER BY depreciation_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceStatusReport() {
        return $this->db->query("
            SELECT
                cr.requirement_name,
                cr.status,
                cr.next_due_date,
                DATEDIFF(cr.next_due_date, CURDATE()) as days_until_due,
                COUNT(ca.id) as actions_taken
            FROM compliance_requirements cr
            LEFT JOIN compliance_actions ca ON cr.id = ca.requirement_id
            WHERE cr.company_id = ?
            GROUP BY cr.id, cr.requirement_name, cr.status, cr.next_due_date
            ORDER BY cr.next_due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getUtilizationReports() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                a.utilization_rate,
                a.last_used_date,
                a.total_usage_hours,
                ROUND(a.utilization_rate, 2) as utilization_percentage
            FROM assets a
            WHERE a.company_id = ? AND a.status = 'active'
            ORDER BY a.utilization_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getCostAnalysis() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.acquisition_cost,
                SUM(mr.cost) as maintenance_cost,
                SUM(mr.cost) / a.acquisition_cost * 100 as maintenance_cost_percentage,
                a.current_value
            FROM assets a
            LEFT JOIN maintenance_records mr ON a.id = mr.asset_id
            WHERE a.company_id = ?
            GROUP BY a.id, a.asset_name, a.acquisition_cost, a.current_value
            ORDER BY maintenance_cost_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceMetrics() {
        return $this->db->querySingle("
            SELECT
                AVG(utilization_rate) as avg_utilization,
                AVG(availability_percentage) as avg_availability,
                SUM(acquisition_cost) / COUNT(*) as avg_cost_per_asset,
                COUNT(CASE WHEN status = 'active' THEN 1 END) / COUNT(*) * 100 as active_asset_percentage
            FROM assets
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPredictiveAnalytics() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                pa.prediction_type,
                pa.risk_level,
                pa.predicted_date,
                pa.confidence_score,
                pa.recommendation
            FROM assets a
            JOIN predictive_analytics pa ON a.id = pa.asset_id
            WHERE a.company_id = ? AND pa.prediction_type = 'failure'
            ORDER BY pa.risk_level DESC, pa.predicted_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCustomReports() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.report_name,
                cr.description,
                cr.created_by,
                cr.created_at,
                cr.last_run,
                u.first_name,
                u.last_name
            FROM custom_reports cr
            JOIN users u ON cr.created_by = u.id
            WHERE cr.company_id = ? AND cr.module = 'assets'
            ORDER BY cr.created_at DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function createAsset() {
        $this->requirePermission('assets.create');

        $data = $this->validateRequest([
            'asset_tag' => 'required|string',
            'asset_name' => 'required|string',
            'category_id' => 'required|integer',
            'description' => 'string',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|numeric',
            'location_id' => 'integer',
            'assigned_to' => 'integer',
            'useful_life_years' => 'integer',
            'depreciation_method' => 'string'
        ]);

        try {
            $this->db->beginTransaction();

            // Create asset
            $assetId = $this->db->insert('assets', [
                'company_id' => $this->user['company_id'],
                'asset_tag' => $data['asset_tag'],
                'asset_name' => $data['asset_name'],
                'category_id' => $data['category_id'],
                'description' => $data['description'] ?? '',
                'acquisition_date' => $data['acquisition_date'],
                'acquisition_cost' => $data['acquisition_cost'],
                'current_value' => $data['acquisition_cost'],
                'location_id' => $data['location_id'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'useful_life_years' => $data['useful_life_years'] ?? 5,
                'depreciation_method' => $data['depreciation_method'] ?? 'straight_line',
                'status' => 'active',
                'created_by' => $this->user['id']
            ]);

            // Create initial depreciation schedule
            $this->createDepreciationSchedule($assetId, $data);

            // Log audit trail
            $this->logAssetAction($assetId, 'created', 'Asset created');

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'asset_id' => $assetId,
                'message' => 'Asset created successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function createDepreciationSchedule($assetId, $data) {
        $usefulLife = $data['useful_life_years'] ?? 5;
        $annualDepreciation = $data['acquisition_cost'] / $usefulLife;
        $monthlyDepreciation = $annualDepreciation / 12;

        for ($year = 1; $year <= $usefulLife; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $depreciationDate = date('Y-m-d', strtotime("+{$year} years -{$month} months", strtotime($data['acquisition_date'])));

                $this->db->insert('depreciation_entries', [
                    'company_id' => $this->user['company_id'],
                    'asset_id' => $assetId,
                    'depreciation_date' => $depreciationDate,
                    'depreciation_amount' => $monthlyDepreciation,
                    'accumulated_depreciation' => $monthlyDepreciation * (($year - 1) * 12 + $month),
                    'book_value' => $data['acquisition_cost'] - ($monthlyDepreciation * (($year - 1) * 12 + $month))
                ]);
            }
        }
    }

    private function logAssetAction($assetId, $action, $details) {
        $this->db->insert('asset_audit_trail', [
            'company_id' => $this->user['company_id'],
            'asset_id' => $assetId,
            'action_type' => $action,
            'action_date' => date('Y-m-d H:i:s'),
            'user_id' => $this->user['id'],
            'details' => $details
        ]);
    }

    public function updateAsset() {
        $this->requirePermission('assets.edit');

        $data = $this->validateRequest([
            'asset_id' => 'required|integer',
            'asset_name' => 'string',
            'category_id' => 'integer',
            'description' => 'string',
            'location_id' => 'integer',
            'assigned_to' => 'integer',
            'status' => 'string'
        ]);

        try {
            $this->db->update('assets', [
                'asset_name' => $data['asset_name'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'description' => $data['description'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'status' => $data['status'] ?? null,
                'updated_by' => $this->user['id'],
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ? AND company_id = ?', [
                $data['asset_id'],
                $this->user['company_id']
            ]);

            // Log audit trail
            $this->logAssetAction($data['asset_id'], 'updated', 'Asset updated');

            $this->jsonResponse([
                'success' => true,
                'message' => 'Asset updated successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function scheduleMaintenance() {
        $this->requirePermission('assets.maintenance.schedule');

        $data = $this->validateRequest([
            'asset_id' => 'required|integer',
            'maintenance_type' => 'required|string',
            'scheduled_date' => 'required|date',
            'description' => 'required|string',
            'priority' => 'required|string',
            'estimated_cost' => 'numeric'
        ]);

        try {
            $scheduleId = $this->db->insert('maintenance_schedule', [
                'company_id' => $this->user['company_id'],
                'asset_id' => $data['asset_id'],
                'maintenance_type' => $data['maintenance_type'],
                'scheduled_date' => $data['scheduled_date'],
                'description' => $data['description'],
                'priority' => $data['priority'],
                'estimated_cost' => $data['estimated_cost'] ?? 0,
                'status' => 'scheduled',
                'created_by' => $this->user['id']
            ]);

            // Log audit trail
            $this->logAssetAction($data['asset_id'], 'maintenance_scheduled', 'Maintenance scheduled');

            $this->jsonResponse([
                'success' => true,
                'schedule_id' => $scheduleId,
                'message' => 'Maintenance scheduled successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function recordMaintenance() {
        $this->requirePermission('assets.maintenance.record');

        $data = $this->validateRequest([
            'asset_id' => 'required|integer',
            'maintenance_date' => 'required|date',
            'maintenance_type' => 'required|string',
            'description' => 'required|string',
            'cost' => 'numeric',
            'technician' => 'string',
            'parts_used' => 'string',
            'downtime_hours' => 'numeric'
        ]);

        try {
            $this->db->beginTransaction();

            // Record maintenance
            $recordId = $this->db->insert('maintenance_records', [
                'company_id' => $this->user['company_id'],
                'asset_id' => $data['asset_id'],
                'maintenance_date' => $data['maintenance_date'],
                'maintenance_type' => $data['maintenance_type'],
                'description' => $data['description'],
                'cost' => $data['cost'] ?? 0,
                'technician' => $data['technician'] ?? '',
                'parts_used' => $data['parts_used'] ?? '',
                'downtime_hours' => $data['downtime_hours'] ?? 0,
                'recorded_by' => $this->user['id']
            ]);

            // Update asset status if needed
            if ($data['maintenance_type'] === 'repair') {
                $this->db->update('assets', [
                    'status' => 'active',
                    'last_maintenance_date' => $data['maintenance_date']
                ], 'id = ?', [$data['asset_id']]);
            }

            // Log audit trail
            $this->logAssetAction($data['asset_id'], 'maintenance_completed', 'Maintenance completed');

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'record_id' => $recordId,
                'message' => 'Maintenance recorded successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateAssetReport() {
        $this->requirePermission('assets.reports.generate');

        $data = $this->validateRequest([
            'report_type' => 'required|string',
            'start_date' => 'date',
            'end_date' => 'date',
            'format' => 'required|string'
        ]);

        try {
            $reportData = [];

            switch ($data['report_type']) {
                case 'inventory':
                    $reportData = $this->getAssetInventoryReport();
                    break;
                case 'utilization':
                    $reportData = $this->getAssetUtilizationReport();
                    break;
                case 'maintenance':
                    $reportData = $this->getMaintenanceSummaryReport();
                    break;
                case 'depreciation':
                    $reportData = $this->getDepreciationSummaryReport();
                    break;
