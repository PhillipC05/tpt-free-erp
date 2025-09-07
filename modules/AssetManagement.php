<?php
/**
 * TPT Free ERP - Asset Management Module
 * Complete asset tracking, maintenance, depreciation, and lifecycle management system
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
            'title' => 'Asset Management',
            'asset_overview' => $this->getAssetOverview(),
            'asset_status' => $this->getAssetStatus(),
            'maintenance_schedule' => $this->getMaintenanceSchedule(),
            'depreciation_summary' => $this->getDepreciationSummary(),
            'asset_valuation' => $this->getAssetValuation(),
            'maintenance_alerts' => $this->getMaintenanceAlerts(),
            'compliance_status' => $this->getComplianceStatus(),
            'asset_analytics' => $this->getAssetAnalytics()
        ];

        $this->render('modules/asset_management/dashboard', $data);
    }

    /**
     * Asset registration and tracking
     */
    public function assets() {
        $this->requirePermission('assets.manage');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'category' => $_GET['category'] ?? null,
            'location' => $_GET['location'] ?? null,
            'department' => $_GET['department'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $assets = $this->getAssets($filters);

        $data = [
            'title' => 'Asset Tracking',
            'assets' => $assets,
            'filters' => $filters,
            'asset_categories' => $this->getAssetCategories(),
            'asset_status' => $this->getAssetStatus(),
            'asset_locations' => $this->getAssetLocations(),
            'asset_departments' => $this->getAssetDepartments(),
            'asset_templates' => $this->getAssetTemplates(),
            'bulk_actions' => $this->getBulkActions(),
            'asset_analytics' => $this->getAssetAnalytics()
        ];

        $this->render('modules/asset_management/assets', $data);
    }

    /**
     * Maintenance scheduling
     */
    public function maintenance() {
        $this->requirePermission('assets.maintenance.view');

        $data = [
            'title' => 'Maintenance Management',
            'maintenance_schedule' => $this->getMaintenanceSchedule(),
            'maintenance_history' => $this->getMaintenanceHistory(),
            'maintenance_plans' => $this->getMaintenancePlans(),
            'maintenance_work_orders' => $this->getMaintenanceWorkOrders(),
            'preventive_maintenance' => $this->getPreventiveMaintenance(),
            'maintenance_costs' => $this->getMaintenanceCosts(),
            'maintenance_analytics' => $this->getMaintenanceAnalytics(),
            'maintenance_templates' => $this->getMaintenanceTemplates()
        ];

        $this->render('modules/asset_management/maintenance', $data);
    }

    /**
     * Depreciation calculation
     */
    public function depreciation() {
        $this->requirePermission('assets.depreciation.view');

        $data = [
            'title' => 'Depreciation Management',
            'depreciation_schedule' => $this->getDepreciationSchedule(),
            'depreciation_methods' => $this->getDepreciationMethods(),
            'depreciation_calculations' => $this->getDepreciationCalculations(),
            'depreciation_reports' => $this->getDepreciationReports(),
            'asset_valuation' => $this->getAssetValuation(),
            'depreciation_analytics' => $this->getDepreciationAnalytics(),
            'depreciation_templates' => $this->getDepreciationTemplates(),
            'tax_implications' => $this->getTaxImplications()
        ];

        $this->render('modules/asset_management/depreciation', $data);
    }

    /**
     * Asset lifecycle management
     */
    public function lifecycle() {
        $this->requirePermission('assets.lifecycle.view');

        $data = [
            'title' => 'Asset Lifecycle',
            'lifecycle_stages' => $this->getLifecycleStages(),
            'lifecycle_transitions' => $this->getLifecycleTransitions(),
            'asset_disposal' => $this->getAssetDisposal(),
            'asset_retirement' => $this->getAssetRetirement(),
            'lifecycle_analytics' => $this->getLifecycleAnalytics(),
            'lifecycle_reports' => $this->getLifecycleReports(),
            'lifecycle_templates' => $this->getLifecycleTemplates(),
            'upgrade_planning' => $this->getUpgradePlanning()
        ];

        $this->render('modules/asset_management/lifecycle', $data);
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
            'compliance_audits' => $this->getComplianceAudits(),
            'regulatory_compliance' => $this->getRegulatoryCompliance(),
            'safety_compliance' => $this->getSafetyCompliance(),
            'environmental_compliance' => $this->getEnvironmentalCompliance(),
            'compliance_reports' => $this->getComplianceReports(),
            'compliance_analytics' => $this->getComplianceAnalytics()
        ];

        $this->render('modules/asset_management/compliance', $data);
    }

    /**
     * Asset analytics and reporting
     */
    public function analytics() {
        $this->requirePermission('assets.analytics.view');

        $data = [
            'title' => 'Asset Analytics',
            'asset_utilization' => $this->getAssetUtilization(),
            'maintenance_costs' => $this->getMaintenanceCosts(),
            'asset_performance' => $this->getAssetPerformance(),
            'lifecycle_costs' => $this->getLifecycleCosts(),
            'asset_depreciation' => $this->getAssetDepreciation(),
            'asset_efficiency' => $this->getAssetEfficiency(),
            'predictive_maintenance' => $this->getPredictiveMaintenance(),
            'asset_benchmarks' => $this->getAssetBenchmarks()
        ];

        $this->render('modules/asset_management/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getAssetOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT a.id) as total_assets,
                COUNT(CASE WHEN a.status = 'active' THEN 1 END) as active_assets,
                COUNT(CASE WHEN a.status = 'maintenance' THEN 1 END) as maintenance_assets,
                COUNT(CASE WHEN a.status = 'retired' THEN 1 END) as retired_assets,
                SUM(a.purchase_value) as total_asset_value,
                AVG(a.purchase_value) as avg_asset_value,
                COUNT(CASE WHEN a.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as upcoming_maintenance,
                COUNT(CASE WHEN a.warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as expiring_warranties,
                COUNT(CASE WHEN a.insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as expiring_insurance
            FROM assets a
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAssetStatus() {
        return $this->db->query("
            SELECT
                status,
                COUNT(*) as asset_count,
                SUM(purchase_value) as total_value,
                AVG(purchase_value) as avg_value,
                COUNT(CASE WHEN next_maintenance_date <= CURDATE() THEN 1 END) as overdue_maintenance
            FROM assets
            WHERE company_id = ?
            GROUP BY status
            ORDER BY asset_count DESC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceSchedule() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                ms.scheduled_date,
                ms.maintenance_type,
                ms.description,
                ms.priority,
                TIMESTAMPDIFF(DAY, CURDATE(), ms.scheduled_date) as days_until_due,
                ms.estimated_duration,
                ms.assigned_technician
            FROM assets a
            JOIN maintenance_schedule ms ON a.id = ms.asset_id
            WHERE a.company_id = ? AND ms.status = 'scheduled' AND ms.scheduled_date >= CURDATE()
            ORDER BY ms.scheduled_date ASC, ms.priority DESC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationSummary() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(d.depreciation_date, '%Y-%m') as month,
                SUM(d.depreciation_amount) as total_depreciation,
                COUNT(DISTINCT d.asset_id) as assets_depreciated,
                AVG(d.depreciation_amount) as avg_depreciation
            FROM depreciation d
            WHERE d.company_id = ? AND d.depreciation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(d.depreciation_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetValuation() {
        return $this->db->querySingle("
            SELECT
                SUM(a.purchase_value) as total_purchase_value,
                SUM(a.current_value) as total_current_value,
                SUM(a.accumulated_depreciation) as total_accumulated_depreciation,
                AVG(a.current_value) as avg_current_value,
                COUNT(CASE WHEN a.current_value < a.purchase_value * 0.1 THEN 1 END) as fully_depreciated,
                COUNT(CASE WHEN a.current_value > a.purchase_value * 0.5 THEN 1 END) as high_value_assets
            FROM assets a
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceAlerts() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                ma.alert_type,
                ma.severity,
                ma.message,
                ma.due_date,
                TIMESTAMPDIFF(DAY, CURDATE(), ma.due_date) as days_until_due,
                ma.estimated_cost,
                ma.priority
            FROM assets a
            JOIN maintenance_alerts ma ON a.id = ma.asset_id
            WHERE a.company_id = ? AND ma.status = 'active'
            ORDER BY ma.priority DESC, ma.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceStatus() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                cs.compliance_type,
                cs.compliance_status,
                cs.last_check_date,
                cs.next_check_date,
                TIMESTAMPDIFF(DAY, CURDATE(), cs.next_check_date) as days_until_next,
                cs.compliance_score
            FROM assets a
            JOIN compliance_status cs ON a.id = cs.asset_id
            WHERE a.company_id = ?
            ORDER BY cs.next_check_date ASC
        ", [$this->user['company_id']]);
    }

    private function getAssetAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(a.id) as total_assets,
                ROUND((COUNT(CASE WHEN a.status = 'active' THEN 1 END) / NULLIF(COUNT(a.id), 0)) * 100, 2) as active_asset_percentage,
                AVG(TIMESTAMPDIFF(YEAR, a.purchase_date, CURDATE())) as avg_asset_age,
                SUM(a.purchase_value) as total_asset_value,
                AVG(a.purchase_value) as avg_asset_value,
                COUNT(CASE WHEN a.next_maintenance_date <= CURDATE() THEN 1 END) as overdue_maintenance,
                COUNT(CASE WHEN a.warranty_expiry <= CURDATE() THEN 1 END) as expired_warranties
            FROM assets a
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAssets($filters) {
        $where = ["a.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "a.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['category']) {
            $where[] = "a.category_id = ?";
            $params[] = $filters['category'];
        }

        if ($filters['location']) {
            $where[] = "a.location_id = ?";
            $params[] = $filters['location'];
        }

        if ($filters['department']) {
            $where[] = "a.department_id = ?";
            $params[] = $filters['department'];
        }

        if ($filters['date_from']) {
            $where[] = "a.purchase_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "a.purchase_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(a.asset_name LIKE ? OR a.asset_tag LIKE ? OR a.serial_number LIKE ? OR a.model LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                a.*,
                ac.category_name,
                al.location_name,
                ad.department_name,
                u.first_name as assigned_to_first,
                u.last_name as assigned_to_last,
                a.purchase_value,
                a.current_value,
                a.accumulated_depreciation,
                TIMESTAMPDIFF(YEAR, a.purchase_date, CURDATE()) as asset_age_years,
                TIMESTAMPDIFF(DAY, CURDATE(), a.next_maintenance_date) as days_until_maintenance,
                TIMESTAMPDIFF(DAY, CURDATE(), a.warranty_expiry) as days_until_warranty_expiry
            FROM assets a
            LEFT JOIN asset_categories ac ON a.category_id = ac.id
            LEFT JOIN asset_locations al ON a.location_id = al.id
            LEFT JOIN asset_departments ad ON a.department_id = ad.id
            LEFT JOIN users u ON a.assigned_to = u.id
            WHERE $whereClause
            ORDER BY a.asset_name ASC
        ", $params);
    }

    private function getAssetCategories() {
        return $this->db->query("
            SELECT
                ac.*,
                COUNT(a.id) as asset_count,
                SUM(a.purchase_value) as total_value,
                AVG(a.purchase_value) as avg_value
            FROM asset_categories ac
            LEFT JOIN assets a ON ac.id = a.category_id
            WHERE ac.company_id = ?
            GROUP BY ac.id
            ORDER BY asset_count DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetLocations() {
        return $this->db->query("
            SELECT
                al.*,
                COUNT(a.id) as asset_count,
                SUM(a.purchase_value) as total_value
            FROM asset_locations al
            LEFT JOIN assets a ON al.id = a.location_id
            WHERE al.company_id = ?
            GROUP BY al.id
            ORDER BY asset_count DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetDepartments() {
        return $this->db->query("
            SELECT
                ad.*,
                COUNT(a.id) as asset_count,
                SUM(a.purchase_value) as total_value
            FROM asset_departments ad
            LEFT JOIN assets a ON ad.id = a.department_id
            WHERE ad.company_id = ?
            GROUP BY ad.id
            ORDER BY asset_count DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetTemplates() {
        return $this->db->query("
            SELECT * FROM asset_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBulkActions() {
        return [
            'update_category' => 'Update Category',
            'update_location' => 'Update Location',
            'update_department' => 'Update Department',
            'update_status' => 'Update Status',
            'schedule_maintenance' => 'Schedule Maintenance',
            'export_assets' => 'Export Asset Data',
            'import_assets' => 'Import Asset Data',
            'bulk_depreciation' => 'Bulk Depreciation',
            'bulk_disposal' => 'Bulk Disposal'
        ];
    }

    private function getMaintenanceHistory() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                mh.maintenance_date,
                mh.maintenance_type,
                mh.description,
                mh.technician,
                mh.cost,
                mh.downtime_hours,
                mh.parts_used
            FROM assets a
            JOIN maintenance_history mh ON a.id = mh.asset_id
            WHERE a.company_id = ?
            ORDER BY mh.maintenance_date DESC
        ", [$this->user['company_id']]);
    }

    private function getMaintenancePlans() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                mp.plan_name,
                mp.frequency_days,
                mp.maintenance_type,
                mp.description,
                mp.estimated_cost,
                mp.last_performed,
                mp.next_due
            FROM assets a
            JOIN maintenance_plans mp ON a.id = mp.asset_id
            WHERE a.company_id = ?
            ORDER BY mp.next_due ASC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceWorkOrders() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                mwo.work_order_number,
                mwo.maintenance_type,
                mwo.priority,
                mwo.status,
                mwo.scheduled_date,
                mwo.completed_date,
                mwo.assigned_technician,
                mwo.estimated_cost
            FROM assets a
            JOIN maintenance_work_orders mwo ON a.id = mwo.asset_id
            WHERE a.company_id = ?
            ORDER BY mwo.scheduled_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPreventiveMaintenance() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                pm.schedule_type,
                pm.frequency,
                pm.last_pm_date,
                pm.next_pm_date,
                pm.pm_tasks,
                pm.estimated_duration,
                TIMESTAMPDIFF(DAY, CURDATE(), pm.next_pm_date) as days_until_next
            FROM assets a
            JOIN preventive_maintenance pm ON a.id = pm.asset_id
            WHERE a.company_id = ?
            ORDER BY pm.next_pm_date ASC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceCosts() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(mh.maintenance_date, '%Y-%m') as month,
                COUNT(mh.id) as maintenance_count,
                SUM(mh.cost) as total_cost,
                AVG(mh.cost) as avg_cost,
                SUM(mh.downtime_hours) as total_downtime,
                AVG(mh.downtime_hours) as avg_downtime
            FROM maintenance_history mh
            JOIN assets a ON mh.asset_id = a.id
            WHERE a.company_id = ? AND mh.maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(mh.maintenance_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(mh.id) as total_maintenance,
                SUM(mh.cost) as total_maintenance_cost,
                AVG(mh.cost) as avg_maintenance_cost,
                SUM(mh.downtime_hours) as total_downtime,
                AVG(mh.downtime_hours) as avg_downtime,
                COUNT(CASE WHEN mh.maintenance_type = 'preventive' THEN 1 END) as preventive_maintenance,
                COUNT(CASE WHEN mh.maintenance_type = 'corrective' THEN 1 END) as corrective_maintenance,
                ROUND((COUNT(CASE WHEN mh.maintenance_type = 'preventive' THEN 1 END) / NULLIF(COUNT(mh.id), 0)) * 100, 2) as preventive_percentage
            FROM maintenance_history mh
            JOIN assets a ON mh.asset_id = a.id
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceTemplates() {
        return $this->db->query("
            SELECT * FROM maintenance_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationSchedule() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                ds.depreciation_method,
                ds.useful_life_years,
                ds.salvage_value,
                ds.annual_depreciation,
                ds.accumulated_depreciation,
                ds.current_book_value,
                ds.next_depreciation_date
            FROM assets a
            JOIN depreciation_schedule ds ON a.id = ds.asset_id
            WHERE a.company_id = ?
            ORDER BY ds.next_depreciation_date ASC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationMethods() {
        return [
            'straight_line' => 'Straight Line',
            'declining_balance' => 'Declining Balance',
            'units_of_production' => 'Units of Production',
            'sum_of_years_digits' => 'Sum of Years Digits',
            'double_declining_balance' => 'Double Declining Balance'
        ];
    }

    private function getDepreciationCalculations() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                dc.calculation_date,
                dc.depreciation_method,
                dc.depreciation_amount,
                dc.accumulated_depreciation,
                dc.book_value,
                dc.fiscal_year,
                dc.fiscal_period
            FROM assets a
            JOIN depreciation_calculations dc ON a.id = dc.asset_id
            WHERE a.company_id = ?
            ORDER BY dc.calculation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationReports() {
        return $this->db->query("
            SELECT
                dr.*,
                dr.report_type,
                dr.report_period,
                dr.generated_date,
                dr.total_depreciation,
                dr.total_assets,
                dr.average_depreciation
            FROM depreciation_reports dr
            WHERE dr.company_id = ?
            ORDER BY dr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDepreciationAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(a.id) as total_depreciable_assets,
                SUM(a.purchase_value) as total_purchase_value,
                SUM(a.accumulated_depreciation) as total_accumulated_depreciation,
                SUM(a.current_value) as total_current_value,
                AVG(a.purchase_value) as avg_purchase_value,
                AVG(a.accumulated_depreciation) as avg_accumulated_depreciation,
                ROUND((SUM(a.accumulated_depreciation) / NULLIF(SUM(a.purchase_value), 0)) * 100, 2) as overall_depreciation_percentage
            FROM assets a
            WHERE a.company_id = ? AND a.depreciation_method IS NOT NULL
        ", [$this->user['company_id']]);
    }

    private function getDepreciationTemplates() {
        return $this->db->query("
            SELECT * FROM depreciation_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTaxImplications() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                ti.tax_year,
                ti.depreciation_deduction,
                ti.section_179_deduction,
                ti.bonus_depreciation,
                ti.total_tax_benefit,
                ti.effective_tax_rate
            FROM assets a
            JOIN tax_implications ti ON a.id = ti.asset_id
            WHERE a.company_id = ?
            ORDER BY ti.tax_year DESC
        ", [$this->user['company_id']]);
    }

    private function getLifecycleStages() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                als.stage_name,
                als.stage_order,
                als.entry_date,
                als.exit_date,
                als.stage_duration_days,
                als.stage_cost,
                als.stage_status
            FROM assets a
            JOIN asset_lifecycle_stages als ON a.id = als.asset_id
            WHERE a.company_id = ?
            ORDER BY a.asset_name, als.stage_order
        ", [$this->user['company_id']]);
    }

    private function getLifecycleTransitions() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                alt.from_stage,
                alt.to_stage,
                alt.transition_date,
                alt.transition_reason,
                alt.approved_by,
                alt.transition_cost,
                alt.notes
            FROM assets a
            JOIN asset_lifecycle_transitions alt ON a.id = alt.asset_id
            WHERE a.company_id = ?
            ORDER BY alt.transition_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetDisposal() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                ad.disposal_date,
                ad.disposal_method,
                ad.disposal_reason,
                ad.disposal_value,
                ad.buyer_name,
                ad.disposal_costs,
                ad.net_proceeds
            FROM assets a
            JOIN asset_disposal ad ON a.id = ad.asset_id
            WHERE a.company_id = ?
            ORDER BY ad.disposal_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetRetirement() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                ar.retirement_date,
                ar.retirement_reason,
                ar.retirement_value,
                ar.disposal_method,
                ar.environmental_impact,
                ar.recycling_info,
                ar.final_disposition
            FROM assets a
            JOIN asset_retirement ar ON a.id = ar.asset_id
            WHERE a.company_id = ?
            ORDER BY ar.retirement_date DESC
        ", [$this->user['company_id']]);
    }

    private function getLifecycleAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT a.id) as total_assets,
                AVG(TIMESTAMPDIFF(DAY, a.purchase_date, COALESCE(ar.retirement_date, CURDATE()))) as avg_lifecycle_days,
                COUNT(ar.id) as retired_assets,
                COUNT(ad.id) as disposed_assets,
                SUM(ad.net_proceeds) as total_disposal_proceeds,
                AVG(ad.net_proceeds) as avg_disposal_proceeds,
                COUNT(CASE WHEN als.stage_name = 'Active' THEN 1 END) as active_assets,
                COUNT(CASE WHEN als.stage_name = 'Maintenance' THEN 1 END) as maintenance_assets
            FROM assets a
            LEFT JOIN asset_retirement ar ON a.id = ar.asset_id
            LEFT JOIN asset_disposal ad ON a.id = ad.asset_id
            LEFT JOIN asset_lifecycle_stages als ON a.id = als.asset_id AND als.exit_date IS NULL
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getLifecycleReports() {
        return $this->db->query("
            SELECT
                lr.*,
                lr.report_type,
                lr.report_period,
                lr.generated_date,
                lr.total_assets,
                lr.avg_lifecycle,
                lr.retirement_rate
            FROM lifecycle_reports lr
            WHERE lr.company_id = ?
            ORDER BY lr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getLifecycleTemplates() {
        return $this->db->query("
            SELECT * FROM lifecycle_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getUpgradePlanning() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                up.upgrade_type,
                up.planned_date,
                up.estimated_cost,
                up.expected_benefits,
                up.risk_assessment,
                up.approval_status,
                TIMESTAMPDIFF(DAY, CURDATE(), up.planned_date) as days_until_upgrade
            FROM assets a
            JOIN upgrade_planning up ON a.id = up.asset_id
            WHERE a.company_id = ?
            ORDER BY up.planned_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceRequirements() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                cr.requirement_type,
                cr.description,
                cr.frequency,
                cr.last_compliance_check,
                cr.next_compliance_check,
                cr.compliance_status,
                TIMESTAMPDIFF(DAY, CURDATE(), cr.next_compliance_check) as days_until_next
            FROM assets a
            JOIN compliance_requirements cr ON a.id = cr.asset_id
            WHERE a.company_id = ?
            ORDER BY cr.next_compliance_check ASC
        ", [$this->user['company_id']]);
    }

    private function getInsurancePolicies() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                ip.policy_number,
                ip.insurance_provider,
                ip.policy_type,
                ip.coverage_amount,
                ip.premium_amount,
                ip.start_date,
                ip.expiry_date,
                ip.deductible_amount,
                TIMESTAMPDIFF(DAY, CURDATE(), ip.expiry_date) as days_until_expiry
            FROM assets a
            JOIN insurance_policies ip ON a.id = ip.asset_id
            WHERE a.company_id = ?
            ORDER BY ip.expiry_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceAudits() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                ca.audit_date,
                ca.audit_type,
                ca.auditor_name,
                ca.audit_result,
                ca.findings,
                ca.corrective_actions,
                ca.next_audit_date
            FROM assets a
            JOIN compliance_audits ca ON a.id = ca.asset_id
            WHERE a.company_id = ?
            ORDER BY ca.audit_date DESC
        ", [$this->user['company_id']]);
    }

    private function getRegulatoryCompliance() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                rc.regulation_name,
                rc.compliance_status,
                rc.last_review_date,
                rc.next_review_date,
                rc.compliance_officer,
                rc.documentation_location,
                TIMESTAMPDIFF(DAY, CURDATE(), rc.next_review_date) as days_until_next
            FROM assets a
            JOIN regulatory_compliance rc ON a.id = rc.asset_id
            WHERE a.company_id = ?
            ORDER BY rc.next_review_date ASC
        ", [$this->user['company_id']]);
    }

    private function getSafetyCompliance() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                sc.safety_standard,
                sc.last_inspection_date,
                sc.next_inspection_date,
                sc.inspection_result,
                sc.safety_rating,
                sc.corrective_actions,
                TIMESTAMPDIFF(DAY, CURDATE(), sc.next_inspection_date) as days_until_next
            FROM assets a
            JOIN safety_compliance sc ON a.id = sc.asset_id
            WHERE a.company_id = ?
            ORDER BY sc.next_inspection_date ASC
        ", [$this->user['company_id']]);
    }

    private function getEnvironmentalCompliance() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                ec.environmental_standard,
                ec.last_assessment_date,
                ec.next_assessment_date,
                ec.environmental_impact,
                ec.emissions_data,
                ec.waste_management,
                ec.sustainability_rating
            FROM assets a
            JOIN environmental_compliance ec ON a.id = ec.asset_id
            WHERE a.company_id = ?
            ORDER BY ec.next_assessment_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceReports() {
        return $this->db->query("
            SELECT
                crp.*,
                crp.report_type,
                crp.report_period,
                crp.generated_date,
                crp.compliance_score,
                crp.non_compliant_assets,
                crp.corrective_actions
            FROM compliance_reports crp
            WHERE crp.company_id = ?
            ORDER BY crp.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT a.id) as total_assets,
                COUNT(CASE WHEN cr.compliance_status = 'compliant' THEN 1 END) as compliant_assets,
                ROUND((COUNT(CASE WHEN cr.compliance_status = 'compliant' THEN 1 END) / NULLIF(COUNT(DISTINCT a.id), 0)) * 100, 2) as compliance_rate,
                COUNT(CASE WHEN ip.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as expiring_insurance,
                COUNT(CASE WHEN sc.safety_rating < 80 THEN 1 END) as low_safety_rating,
                AVG(sc.safety_rating) as avg_safety_rating,
                COUNT(CASE WHEN ec.sustainability_rating < 70 THEN 1 END) as low_sustainability_rating
            FROM assets a
            LEFT JOIN compliance_requirements cr ON a.id = cr.asset_id
            LEFT JOIN insurance_policies ip ON a.id = ip.asset_id
            LEFT JOIN safety_compliance sc ON a.id = sc.asset_id
            LEFT JOIN environmental_compliance ec ON a.id = ec.asset_id
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAssetUtilization() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                au.utilization_date,
                au.utilization_percentage,
                au.operating_hours,
                au.downtime_hours,
                au.efficiency_rating,
                au.productivity_score
            FROM assets a
            JOIN asset_utilization au ON a.id = au.asset_id
            WHERE a.company_id = ?
            ORDER BY au.utilization_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetPerformance() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                ap.metric_name,
                ap.metric_value,
                ap.target_value,
                ap.performance_date,
                ROUND(((ap.metric_value - ap.target_value) / NULLIF(ap.target_value, 0)) * 100, 2) as performance_percentage
            FROM assets a
            JOIN asset_performance ap ON a.id = ap.asset_id
            WHERE a.company_id = ?
            ORDER BY ap.performance_date DESC
        ", [$this->user['company_id']]);
    }

    private function getLifecycleCosts() {
        return $this->db->query("
            SELECT
                a.asset_name,
                a.asset_tag,
                lc.cost_category,
                lc.cost_amount,
                lc.cost_date,
                lc.accumulated_cost,
                lc.cost_projection
            FROM assets a
            JOIN lifecycle_costs lc ON a.id = lc.asset_id
            WHERE a.company_id = ?
            ORDER BY lc.cost_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAssetDepreciation() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(d.depreciation_date, '%Y-%m') as month,
                COUNT(DISTINCT d.asset_id) as assets_depreciated,
                SUM(d.depreciation_amount) as total_depreciation,
                AVG(d.depreciation_amount) as avg_depreciation,
                SUM(a.purchase_value) as total_asset_value,
                ROUND((SUM(d.depreciation_amount) / NULLIF(SUM(a.purchase_value), 0)) * 100, 2) as depreciation_rate
            FROM depreciation d
            JOIN assets a ON d.asset_id = a
