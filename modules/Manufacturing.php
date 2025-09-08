<?php
/**
 * TPT Free ERP - Manufacturing Module
 * Complete production planning, work order management, and quality control system
 */

class Manufacturing extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main manufacturing dashboard
     */
    public function index() {
        $this->requirePermission('manufacturing.view');

        $data = [
            'title' => 'Manufacturing Management',
            'production_overview' => $this->getProductionOverview(),
            'work_order_status' => $this->getWorkOrderStatus(),
            'production_schedule' => $this->getProductionSchedule(),
            'quality_metrics' => $this->getQualityMetrics(),
            'resource_utilization' => $this->getResourceUtilization(),
            'production_efficiency' => $this->getProductionEfficiency(),
            'inventory_status' => $this->getInventoryStatus(),
            'maintenance_alerts' => $this->getMaintenanceAlerts()
        ];

        $this->render('modules/manufacturing/dashboard', $data);
    }

    /**
     * Production planning
     */
    public function productionPlanning() {
        $this->requirePermission('manufacturing.planning.view');

        $data = [
            'title' => 'Production Planning',
            'production_plans' => $this->getProductionPlans(),
            'demand_forecasting' => $this->getDemandForecasting(),
            'capacity_planning' => $this->getCapacityPlanning(),
            'material_requirements' => $this->getMaterialRequirements(),
            'production_scheduling' => $this->getProductionScheduling(),
            'resource_allocation' => $this->getResourceAllocation(),
            'production_optimization' => $this->getProductionOptimization(),
            'planning_analytics' => $this->getPlanningAnalytics()
        ];

        $this->render('modules/manufacturing/production_planning', $data);
    }

    /**
     * Bill of materials
     */
    public function billOfMaterials() {
        $this->requirePermission('manufacturing.bom.view');

        $data = [
            'title' => 'Bill of Materials',
            'boms' => $this->getBillsOfMaterials(),
            'bom_templates' => $this->getBOMTemplates(),
            'material_substitutions' => $this->getMaterialSubstitutions(),
            'cost_rollup' => $this->getCostRollup(),
            'bom_versions' => $this->getBOMVersions(),
            'where_used' => $this->getWhereUsed(),
            'bom_analytics' => $this->getBOMAnalytics(),
            'engineering_changes' => $this->getEngineeringChanges()
        ];

        $this->render('modules/manufacturing/bill_of_materials', $data);
    }

    /**
     * Work order management
     */
    public function workOrders() {
        $this->requirePermission('manufacturing.work_orders.view');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'production_line' => $_GET['production_line'] ?? null,
            'priority' => $_GET['priority'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $work_orders = $this->getWorkOrders($filters);

        $data = [
            'title' => 'Work Order Management',
            'work_orders' => $work_orders,
            'filters' => $filters,
            'work_order_templates' => $this->getWorkOrderTemplates(),
            'routing_operations' => $this->getRoutingOperations(),
            'labor_tracking' => $this->getLaborTracking(),
            'work_order_status' => $this->getWorkOrderStatus(),
            'quality_checks' => $this->getQualityChecks(),
            'work_order_analytics' => $this->getWorkOrderAnalytics(),
            'production_reporting' => $this->getProductionReporting()
        ];

        $this->render('modules/manufacturing/work_orders', $data);
    }

    /**
     * Quality control
     */
    public function qualityControl() {
        $this->requirePermission('manufacturing.quality.view');

        $data = [
            'title' => 'Quality Control',
            'quality_inspections' => $this->getQualityInspections(),
            'quality_standards' => $this->getQualityStandards(),
            'non_conformance' => $this->getNonConformance(),
            'corrective_actions' => $this->getCorrectiveActions(),
            'quality_metrics' => $this->getQualityMetrics(),
            'statistical_process' => $this->getStatisticalProcess(),
            'quality_audits' => $this->getQualityAudits(),
            'quality_analytics' => $this->getQualityAnalytics()
        ];

        $this->render('modules/manufacturing/quality_control', $data);
    }

    /**
     * Resource planning
     */
    public function resourcePlanning() {
        $this->requirePermission('manufacturing.resources.view');

        $data = [
            'title' => 'Resource Planning',
            'production_lines' => $this->getProductionLines(),
            'equipment_management' => $this->getEquipmentManagement(),
            'labor_resources' => $this->getLaborResources(),
            'material_planning' => $this->getMaterialPlanning(),
            'capacity_analysis' => $this->getCapacityAnalysis(),
            'resource_scheduling' => $this->getResourceScheduling(),
            'resource_optimization' => $this->getResourceOptimization(),
            'resource_analytics' => $this->getResourceAnalytics()
        ];

        $this->render('modules/manufacturing/resource_planning', $data);
    }

    /**
     * Shop floor control
     */
    public function shopFloor() {
        $this->requirePermission('manufacturing.shop_floor.view');

        $data = [
            'title' => 'Shop Floor Control',
            'production_monitoring' => $this->getProductionMonitoring(),
            'operator_interface' => $this->getOperatorInterface(),
            'machine_monitoring' => $this->getMachineMonitoring(),
            'downtime_tracking' => $this->getDowntimeTracking(),
            'production_data' => $this->getProductionData(),
            'real_time_alerts' => $this->getRealTimeAlerts(),
            'shop_floor_analytics' => $this->getShopFloorAnalytics(),
            'performance_dashboards' => $this->getPerformanceDashboards()
        ];

        $this->render('modules/manufacturing/shop_floor', $data);
    }

    /**
     * Manufacturing analytics
     */
    public function analytics() {
        $this->requirePermission('manufacturing.analytics.view');

        $data = [
            'title' => 'Manufacturing Analytics',
            'production_analytics' => $this->getProductionAnalytics(),
            'efficiency_analytics' => $this->getEfficiencyAnalytics(),
            'quality_analytics' => $this->getQualityAnalytics(),
            'cost_analytics' => $this->getCostAnalytics(),
            'resource_analytics' => $this->getResourceAnalytics(),
            'predictive_analytics' => $this->getPredictiveAnalytics(),
            'benchmarking_analytics' => $this->getBenchmarkingAnalytics(),
            'manufacturing_dashboards' => $this->getManufacturingDashboards()
        ];

        $this->render('modules/manufacturing/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getProductionOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT wo.id) as total_work_orders,
                COUNT(DISTINCT pl.id) as active_production_lines,
                COUNT(DISTINCT bom.id) as total_boms,
                SUM(wo.quantity_produced) as total_produced,
                SUM(wo.quantity_planned) as total_planned,
                ROUND((SUM(wo.quantity_produced) / NULLIF(SUM(wo.quantity_planned), 0)) * 100, 2) as production_efficiency,
                COUNT(CASE WHEN wo.status = 'in_progress' THEN 1 END) as active_work_orders,
                COUNT(CASE WHEN wo.priority = 'high' THEN 1 END) as high_priority_orders
            FROM work_orders wo
            LEFT JOIN production_lines pl ON wo.production_line_id = pl.id
            LEFT JOIN bills_of_materials bom ON wo.bom_id = bom.id
            WHERE wo.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getWorkOrderStatus() {
        return $this->db->query("
            SELECT
                status,
                COUNT(*) as order_count,
                SUM(quantity_produced) as total_produced,
                SUM(quantity_planned) as total_planned,
                ROUND((SUM(quantity_produced) / NULLIF(SUM(quantity_planned), 0)) * 100, 2) as completion_rate,
                AVG(TIMESTAMPDIFF(DAY, created_at, COALESCE(completed_at, CURDATE()))) as avg_completion_days
            FROM work_orders
            WHERE company_id = ?
            GROUP BY status
            ORDER BY order_count DESC
        ", [$this->user['company_id']]);
    }

    private function getProductionSchedule() {
        return $this->db->query("
            SELECT
                wo.*,
                pl.line_name,
                bom.product_name,
                wo.start_date,
                wo.end_date,
                wo.quantity_planned,
                wo.quantity_produced,
                TIMESTAMPDIFF(DAY, CURDATE(), wo.start_date) as days_until_start,
                TIMESTAMPDIFF(DAY, wo.start_date, wo.end_date) as planned_duration
            FROM work_orders wo
            JOIN production_lines pl ON wo.production_line_id = pl.id
            JOIN bills_of_materials bom ON wo.bom_id = bom.id
            WHERE wo.company_id = ? AND wo.status IN ('planned', 'scheduled', 'in_progress')
            ORDER BY wo.start_date ASC
        ", [$this->user['company_id']]);
    }

    private function getQualityMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(qi.id) as total_inspections,
                COUNT(CASE WHEN qi.result = 'pass' THEN 1 END) as passed_inspections,
                COUNT(CASE WHEN qi.result = 'fail' THEN 1 END) as failed_inspections,
                ROUND((COUNT(CASE WHEN qi.result = 'pass' THEN 1 END) / NULLIF(COUNT(qi.id), 0)) * 100, 2) as quality_rate,
                COUNT(nc.id) as total_non_conformances,
                COUNT(CASE WHEN nc.status = 'open' THEN 1 END) as open_non_conformances,
                AVG(qi.defect_rate) as avg_defect_rate,
                MAX(qi.inspection_date) as last_inspection_date
            FROM quality_inspections qi
            LEFT JOIN non_conformances nc ON nc.work_order_id = qi.work_order_id
            WHERE qi.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getResourceUtilization() {
        return $this->db->query("
            SELECT
                pl.line_name,
                pl.capacity_utilization,
                pl.availability_percentage,
                pl.oee_percentage,
                COUNT(wo.id) as active_work_orders,
                SUM(wo.quantity_produced) as total_produced,
                AVG(wo.actual_cycle_time) as avg_cycle_time,
                pl.last_maintenance_date,
                TIMESTAMPDIFF(DAY, pl.last_maintenance_date, CURDATE()) as days_since_maintenance
            FROM production_lines pl
            LEFT JOIN work_orders wo ON pl.id = wo.production_line_id AND wo.status = 'in_progress'
            WHERE pl.company_id = ?
            GROUP BY pl.id, pl.line_name, pl.capacity_utilization, pl.availability_percentage, pl.oee_percentage, pl.last_maintenance_date
            ORDER BY pl.capacity_utilization DESC
        ", [$this->user['company_id']]);
    }

    private function getProductionEfficiency() {
        return $this->db->querySingle("
            SELECT
                AVG(wo.actual_cycle_time) as avg_cycle_time,
                AVG(wo.planned_cycle_time) as avg_planned_cycle_time,
                ROUND((AVG(wo.planned_cycle_time) / NULLIF(AVG(wo.actual_cycle_time), 0)) * 100, 2) as cycle_time_efficiency,
                AVG(wo.setup_time) as avg_setup_time,
                SUM(wo.quantity_produced) as total_produced,
                SUM(wo.quantity_scrapped) as total_scrapped,
                ROUND((SUM(wo.quantity_scrapped) / NULLIF(SUM(wo.quantity_produced + wo.quantity_scrapped), 0)) * 100, 2) as scrap_rate,
                AVG(wo.labor_efficiency) as avg_labor_efficiency
            FROM work_orders wo
            WHERE wo.company_id = ? AND wo.completed_at IS NOT NULL
        ", [$this->user['company_id']]);
    }

    private function getInventoryStatus() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.current_stock,
                p.safety_stock,
                p.reorder_point,
                CASE
                    WHEN p.current_stock <= p.reorder_point THEN 'reorder'
                    WHEN p.current_stock <= p.safety_stock THEN 'low'
                    ELSE 'normal'
                END as stock_status,
                SUM(bomc.quantity_required) as required_for_production,
                p.last_inventory_count,
                TIMESTAMPDIFF(DAY, p.last_inventory_count, CURDATE()) as days_since_count
            FROM products p
            LEFT JOIN bom_components bomc ON p.id = bomc.component_id
            WHERE p.company_id = ?
            GROUP BY p.id, p.product_name, p.current_stock, p.safety_stock, p.reorder_point, p.last_inventory_count
            HAVING required_for_production > 0
            ORDER BY
                CASE
                    WHEN p.current_stock <= p.reorder_point THEN 1
                    WHEN p.current_stock <= p.safety_stock THEN 2
                    ELSE 3
                END ASC
        ", [$this->user['company_id']]);
    }

    private function getMaintenanceAlerts() {
        return $this->db->query("
            SELECT
                pl.line_name,
                ma.alert_type,
                ma.severity,
                ma.description,
                ma.due_date,
                TIMESTAMPDIFF(DAY, CURDATE(), ma.due_date) as days_until_due,
                ma.estimated_downtime,
                ma.priority
            FROM maintenance_alerts ma
            JOIN production_lines pl ON ma.production_line_id = pl.id
            WHERE ma.company_id = ? AND ma.status = 'active'
            ORDER BY ma.priority DESC, ma.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getProductionPlans() {
        return $this->db->query("
            SELECT
                pp.*,
                pp.plan_name,
                pp.planning_period,
                pp.total_quantity_planned,
                pp.total_quantity_produced,
                ROUND((pp.total_quantity_produced / NULLIF(pp.total_quantity_planned, 0)) * 100, 2) as plan_completion,
                pp.start_date,
                pp.end_date,
                TIMESTAMPDIFF(DAY, CURDATE(), pp.end_date) as days_remaining
            FROM production_plans pp
            WHERE pp.company_id = ?
            ORDER BY pp.start_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDemandForecasting() {
        return $this->db->query("
            SELECT
                df.*,
                p.product_name,
                df.forecast_period,
                df.forecast_quantity,
                df.actual_demand,
                df.accuracy_percentage,
                df.forecast_method,
                df.last_updated
            FROM demand_forecasting df
            JOIN products p ON df.product_id = p.id
            WHERE df.company_id = ?
            ORDER BY df.forecast_period DESC
        ", [$this->user['company_id']]);
    }

    private function getCapacityPlanning() {
        return $this->db->query("
            SELECT
                cp.*,
                pl.line_name,
                cp.planning_period,
                cp.available_capacity,
                cp.required_capacity,
                cp.capacity_utilization,
                ROUND(((cp.required_capacity - cp.available_capacity) / NULLIF(cp.available_capacity, 0)) * 100, 2) as capacity_gap,
                cp.bottleneck_analysis
            FROM capacity_planning cp
            JOIN production_lines pl ON cp.production_line_id = pl.id
            WHERE cp.company_id = ?
            ORDER BY cp.capacity_gap DESC
        ", [$this->user['company_id']]);
    }

    private function getMaterialRequirements() {
        return $this->db->query("
            SELECT
                p.product_name,
                SUM(bomc.quantity_required * wo.quantity_planned) as total_required,
                p.current_stock,
                SUM(bomc.quantity_required * wo.quantity_planned) - p.current_stock as shortage_quantity,
                CASE
                    WHEN SUM(bomc.quantity_required * wo.quantity_planned) > p.current_stock THEN 'shortage'
                    ELSE 'available'
                END as availability_status,
                MAX(wo.start_date) as earliest_needed_date
            FROM work_orders wo
            JOIN bom_components bomc ON wo.bom_id = bomc.bom_id
            JOIN products p ON bomc.component_id = p.id
            WHERE wo.company_id = ? AND wo.status IN ('planned', 'scheduled')
            GROUP BY p.id, p.product_name, p.current_stock
            HAVING shortage_quantity > 0
            ORDER BY shortage_quantity DESC
        ", [$this->user['company_id']]);
    }

    private function getProductionScheduling() {
        return $this->db->query("
            SELECT
                ps.*,
                wo.work_order_number,
                pl.line_name,
                ps.scheduled_start,
                ps.scheduled_end,
                ps.actual_start,
                ps.actual_end,
                TIMESTAMPDIFF(MINUTE, ps.scheduled_start, ps.actual_start) as start_variance,
                ps.priority,
                ps.scheduling_status
            FROM production_scheduling ps
            JOIN work_orders wo ON ps.work_order_id = wo.id
            JOIN production_lines pl ON ps.production_line_id = pl.id
            WHERE ps.company_id = ?
            ORDER BY ps.scheduled_start ASC
        ", [$this->user['company_id']]);
    }

    private function getResourceAllocation() {
        return $this->db->query("
            SELECT
                ra.*,
                pl.line_name,
                wo.work_order_number,
                ra.resource_type,
                ra.allocated_quantity,
                ra.utilization_rate,
                ra.allocation_start,
                ra.allocation_end
            FROM resource_allocation ra
            JOIN production_lines pl ON ra.production_line_id = pl.id
            LEFT JOIN work_orders wo ON ra.work_order_id = wo.id
            WHERE ra.company_id = ?
            ORDER BY ra.allocation_start ASC
        ", [$this->user['company_id']]);
    }

    private function getProductionOptimization() {
        return $this->db->query("
            SELECT
                po.*,
                po.optimization_type,
                po.current_efficiency,
                po.target_efficiency,
                po.estimated_improvement,
                po.implementation_cost,
                po.payback_period,
                po.status
            FROM production_optimization po
            WHERE po.company_id = ?
            ORDER BY po.estimated_improvement DESC
        ", [$this->user['company_id']]);
    }

    private function getPlanningAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(pp.id) as total_plans,
                AVG(pp.plan_completion) as avg_plan_completion,
                COUNT(df.id) as total_forecasts,
                AVG(df.accuracy_percentage) as avg_forecast_accuracy,
                COUNT(cp.id) as total_capacity_plans,
                AVG(cp.capacity_utilization) as avg_capacity_utilization,
                COUNT(CASE WHEN cp.capacity_gap > 0 THEN 1 END) as capacity_constraints
            FROM production_plans pp
            LEFT JOIN demand_forecasting df ON df.company_id = pp.company_id
            LEFT JOIN capacity_planning cp ON cp.company_id = pp.company_id
            WHERE pp.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBillsOfMaterials($filters = []) {
        $where = ["bom.company_id = ?"];
        $params = [$this->user['company_id']];

        if (isset($filters['product'])) {
            $where[] = "bom.product_id = ?";
            $params[] = $filters['product'];
        }

        if (isset($filters['status'])) {
            $where[] = "bom.status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                bom.*,
                p.product_name,
                p.product_code,
                COUNT(bomc.id) as component_count,
                SUM(bomc.quantity_required * bomc.unit_cost) as total_cost,
                bom.version_number,
                bom.effective_date,
                bom.status
            FROM bills_of_materials bom
            JOIN products p ON bom.product_id = p.id
            LEFT JOIN bom_components bomc ON bom.id = bomc.bom_id
            WHERE $whereClause
            GROUP BY bom.id, p.product_name, p.product_code
            ORDER BY bom.effective_date DESC
        ", $params);
    }

    private function getBOMTemplates() {
        return $this->db->query("
            SELECT * FROM bom_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getMaterialSubstitutions() {
        return $this->db->query("
            SELECT
                ms.*,
                p1.product_name as original_material,
                p2.product_name as substitute_material,
                ms.substitution_ratio,
                ms.cost_impact,
                ms.quality_impact,
                ms.approval_required
            FROM material_substitutions ms
            JOIN products p1 ON ms.original_material_id = p1.id
            JOIN products p2 ON ms.substitute_material_id = p2.id
            WHERE ms.company_id = ?
            ORDER BY ms.cost_impact ASC
        ", [$this->user['company_id']]);
    }

    private function getCostRollup() {
        return $this->db->query("
            SELECT
                bom.id,
                p.product_name,
                SUM(bomc.quantity_required * bomc.unit_cost) as material_cost,
                SUM(bomc.quantity_required * bomc.labor_cost) as labor_cost,
                SUM(bomc.quantity_required * (bomc.unit_cost + bomc.labor_cost + bomc.overhead_cost)) as total_cost,
                AVG(bomc.yield_percentage) as avg_yield,
                COUNT(bomc.id) as component_count
            FROM bills_of_materials bom
            JOIN products p ON bom.product_id = p.id
            JOIN bom_components bomc ON bom.id = bomc.bom_id
            WHERE bom.company_id = ?
            GROUP BY bom.id, p.product_name
            ORDER BY total_cost DESC
        ", [$this->user['company_id']]);
    }

    private function getBOMVersions() {
        return $this->db->query("
            SELECT
                bomv.*,
                p.product_name,
                bomv.version_number,
                bomv.change_reason,
                bomv.effective_date,
                bomv.created_by,
                COUNT(bomvc.id) as component_changes
            FROM bom_versions bomv
            JOIN products p ON bomv.product_id = p.id
            LEFT JOIN bom_version_changes bomvc ON bomv.id = bomvc.bom_version_id
            WHERE bomv.company_id = ?
            GROUP BY bomv.id, p.product_name
            ORDER BY bomv.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getWhereUsed() {
        return $this->db->query("
            SELECT
                p.product_name as component,
                p2.product_name as used_in,
                bomc.quantity_required,
                bom.version_number,
                COUNT(wo.id) as work_order_count
            FROM bom_components bomc
            JOIN products p ON bomc.component_id = p.id
            JOIN bills_of_materials bom ON bomc.bom_id = bom.id
            JOIN products p2 ON bom.product_id = p2.id
            LEFT JOIN work_orders wo ON bom.id = wo.bom_id
            WHERE bomc.company_id = ?
            GROUP BY p.id, p.product_name, p2.id, p2.product_name, bomc.quantity_required, bom.version_number
            ORDER BY p.product_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBOMAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(bom.id) as total_boms,
                AVG(component_count) as avg_components_per_bom,
                AVG(total_cost) as avg_bom_cost,
                COUNT(CASE WHEN bom.status = 'active' THEN 1 END) as active_boms,
                COUNT(CASE WHEN bom.version_number > 1 THEN 1 END) as revised_boms,
                MAX(bom.effective_date) as latest_bom_date
            FROM bills_of_materials bom
            LEFT JOIN (
                SELECT bom_id, COUNT(*) as component_count, SUM(quantity_required * unit_cost) as total_cost
                FROM bom_components
                GROUP BY bom_id
            ) bom_stats ON bom.id = bom_stats.bom_id
            WHERE bom.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEngineeringChanges() {
        return $this->db->query("
            SELECT
                ec.*,
                p.product_name,
                ec.change_type,
                ec.change_description,
                ec.implementation_date,
                ec.impact_assessment,
                ec.approval_status
            FROM engineering_changes ec
            JOIN products p ON ec.product_id = p.id
            WHERE ec.company_id = ?
            ORDER BY ec.implementation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getWorkOrders($filters) {
        $where = ["wo.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "wo.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['production_line']) {
            $where[] = "wo.production_line_id = ?";
            $params[] = $filters['production_line'];
        }

        if ($filters['priority']) {
            $where[] = "wo.priority = ?";
            $params[] = $filters['priority'];
        }

        if ($filters['date_from']) {
            $where[] = "wo.start_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "wo.start_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(wo.work_order_number LIKE ? OR bom.product_name LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                wo.*,
                bom.product_name,
                pl.line_name,
                wo.quantity_planned,
                wo.quantity_produced,
                wo.quantity_scrapped,
                ROUND((wo.quantity_produced / NULLIF(wo.quantity_planned, 0)) * 100, 2) as completion_percentage,
                wo.start_date,
                wo.end_date,
                wo.priority,
                wo.status
            FROM work_orders wo
            JOIN bills_of_materials bom ON wo.bom_id = bom.id
            JOIN production_lines pl ON wo.production_line_id = pl.id
            WHERE $whereClause
            ORDER BY wo.priority DESC, wo.start_date ASC
        ", $params);
    }

    private function getWorkOrderTemplates() {
        return $this->db->query("
            SELECT * FROM work_order_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getRoutingOperations() {
        return $this->db->query("
            SELECT
                ro.*,
                wo.work_order_number,
                ro.operation_name,
                ro.operation_sequence,
                ro.planned_setup_time,
                ro.planned_run_time,
                ro.actual_setup_time,
                ro.actual_run_time,
                ro.status
            FROM routing_operations ro
            JOIN work_orders wo ON ro.work_order_id = wo.id
            WHERE ro.company_id = ?
            ORDER BY wo.work_order_number, ro.operation_sequence
        ", [$this->user['company_id']]);
    }

    private function getLaborTracking() {
        return $this->db->query("
            SELECT
                lt.*,
                wo.work_order_number,
                e.first_name,
                e.last_name,
                lt.operation_name,
                lt.time_spent,
                lt.efficiency_rating,
                lt.date_worked
            FROM labor_tracking lt
            JOIN work_orders wo ON lt.work_order_id = wo.id
            JOIN employees e ON lt.employee_id = e.id
            WHERE lt.company_id = ?
            ORDER BY lt.date_worked DESC
        ", [$this->user['company_id']]);
    }

    private function getQualityChecks() {
        return $this->db->query("
            SELECT
                qc.*,
                wo.work_order_number,
                qc.check_type,
                qc.specification,
                qc.actual_value,
                qc.result,
                qc.checked_by,
                qc.check_date
            FROM quality_checks qc
            JOIN work_orders wo ON qc.work_order_id = wo.id
            WHERE qc.company_id = ?
            ORDER BY qc.check_date DESC
        ", [$this->user['company_id']]);
    }

    private function getWorkOrderAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(wo.id) as total_work_orders,
                AVG(wo.completion_percentage) as avg_completion_rate,
                AVG(wo.actual_cycle_time) as avg_cycle_time,
                AVG(wo.labor_efficiency) as avg_labor_efficiency,
                SUM(wo.quantity_scrapped) as total_scrapped,
                ROUND((SUM(wo.quantity_scrapped) / NULLIF(SUM(wo.quantity_produced + wo.quantity_scrapped), 0)) * 100, 2) as overall_scrap_rate,
                COUNT(CASE WHEN wo.status = 'completed' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN wo.status = 'in_progress' THEN 1 END) as in_progress_orders
            FROM work_orders wo
            WHERE wo.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getProductionReporting() {
        return $this->db->query("
            SELECT
                pr.*,
                pr.report_type,
                pr.report_period,
                pr.generated_date,
                pr.total_produced,
                pr.total_planned,
                pr.efficiency_percentage
            FROM production_reports pr
            WHERE pr.company_id = ?
            ORDER BY pr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getQualityInspections() {
        return $this->db->query("
            SELECT
                qi.*,
                wo.work_order_number,
                qi.inspection_type,
                qi.specification,
                qi.actual_value,
                qi.result,
                qi.defect_rate,
                qi.inspection_date
            FROM quality_inspections qi
            JOIN work_orders wo ON qi.work_order_id = wo.id
            WHERE qi.company_id = ?
            ORDER BY qi.inspection_date DESC
        ", [$this->user['company_id']]);
    }

    private function getQualityStandards() {
        return $this->db->query("
            SELECT * FROM quality_standards
            WHERE company_id = ? AND is_active = true
            ORDER BY standard_name ASC
        ", [$this->user['company_id']]);
    }

    private function getNonConformance() {
        return $this->db->query("
            SELECT
                nc.*,
                wo.work_order_number,
                nc.non_conformance_type,
                nc.severity,
                nc.description,
                nc.root_cause,
                nc.status,
                nc.reported_date
            FROM non_conformances nc
            JOIN work_orders wo ON nc.work_order_id = wo.id
            WHERE nc.company_id = ?
            ORDER BY nc.reported_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCorrectiveActions() {
        return $this->db->query("
            SELECT
                ca.*,
                nc.non_conformance_type,
                ca.action_description,
                ca.responsible_party,
                ca.target_completion_date,
                ca.actual_completion_date,
                ca.effectiveness_rating
            FROM corrective_actions ca
            JOIN non_conformances nc ON ca.non_conformance_id = nc.id
            WHERE ca.company_id = ?
            ORDER BY ca.target_completion_date ASC
        ", [$this->user['company_id']]);
    }

    private function getStatisticalProcess() {
        return $this->db->query("
            SELECT
                spc.*,
                spc.process_name,
                spc.control_limits_upper,
                spc.control_limits_lower,
                spc.mean_value,
                spc.standard_deviation,
                spc.capability_index,
                spc.last_updated
            FROM statistical_process_control spc
            WHERE spc.company_id = ?
            ORDER BY spc.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getQualityAudits() {
        return $this->db->query("
            SELECT
                qa.*,
                qa.audit_type,
                qa.audit_scope,
                qa.audit_date,
                qa.findings_count,
                qa.score_percentage,
                qa.next_audit_date
            FROM quality_audits qa
            WHERE qa.company_id = ?
            ORDER BY qa.audit_date DESC
        ", [$this->user['company_id']]);
    }

    private function getProductionLines() {
        return $this->db->query("
            SELECT
                pl.*,
                COUNT(wo.id) as active_work_orders,
                pl.capacity_utilization,
                pl.availability_percentage,
                pl.oee_percentage,
                pl.last_maintenance_date,
                TIMESTAMPDIFF(DAY, pl.last_maintenance_date, CURDATE()) as days_since_maintenance
            FROM production_lines pl
            LEFT JOIN work_orders wo ON pl.id = wo.production_line_id AND wo.status = 'in_progress'
            WHERE pl.company_id = ?
            GROUP BY pl.id
            ORDER BY pl.capacity_utilization DESC
        ", [$this->user['company_id']]);
    }

    private function getEquipmentManagement() {
        return $this->db->query("
            SELECT
                em.*,
                pl.line_name,
                em.equipment_name,
                em.equipment_type,
                em.status,
                em.last_maintenance_date,
                em.next_maintenance_date,
                TIMESTAMPDIFF(DAY, CURDATE(), em.next_maintenance_date) as days_until_maintenance
            FROM equipment_management em
            JOIN production_lines pl ON em.production_line_id = pl.id
            WHERE em.company_id = ?
            ORDER BY em.next_maintenance_date ASC
        ", [$this->user['company_id']]);
    }

    private function getLaborResources() {
        return $this->db->query("
            SELECT
                lr.*,
                e.first_name,
                e.last_name,
                lr.skill_level,
                lr.hourly_rate,
                lr.availability_percentage,
                lr.utilization_rate,
                lr.last_assignment_date
            FROM labor_resources lr
            JOIN employees e ON lr.employee_id = e.id
            WHERE lr.company_id = ?
            ORDER BY lr.utilization_rate DESC
        ", [$this->user['company_id']]);
    }

    private function getMaterialPlanning() {
        return $this->db->query("
            SELECT
                mp.*,
                p.product_name,
                mp.planned_quantity,
                mp.available_quantity,
                mp.required_date,
                mp.planning_status,
                TIMESTAMPDIFF(DAY, CURDATE(), mp.required_date) as days_until_required
            FROM material_planning mp
            JOIN products p ON mp.material_id = p.id
            WHERE mp.company_id = ?
            ORDER BY mp.required_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCapacityAnalysis() {
        return $this->db->query("
            SELECT
                ca.*,
                pl.line_name,
                ca.analysis_period,
                ca.available_capacity,
                ca.required_capacity,
                ca.capacity_gap,
                ca.bottleneck_factor,
                ca.recommendations
            FROM capacity_analysis ca
            JOIN production_lines pl ON ca.production_line_id = pl.id
            WHERE ca.company_id = ?
            ORDER BY ca.capacity_gap DESC
        ", [$this->user['company_id']]);
    }

    private function getResourceScheduling() {
        return $this->db->query("
            SELECT
                rs.*,
                pl.line_name,
                rs.resource_type,
                rs.scheduled_start,
                rs.scheduled_end,
                rs.utilization_percentage,
                rs.scheduling_status
            FROM resource_scheduling rs
            JOIN production_lines pl ON rs.production_line_id = pl.id
            WHERE rs.company_id = ?
            ORDER BY rs.scheduled_start ASC
        ", [$this->user['company_id']]);
    }

    private function getResourceOptimization() {
        return $this->db->query("
            SELECT
                ro.*,
                ro.optimization_type,
                ro.current_utilization,
                ro.target_utilization,
                ro.estimated_savings,
                ro.implementation_cost,
                ro.roi_percentage
            FROM resource_optimization ro
            WHERE ro.company_id = ?
            ORDER BY ro.roi_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getProductionMonitoring() {
        return $this->db->query("
            SELECT
                pm.*,
                wo.work_order_number,
                pl.line_name,
                pm.metric_name,
                pm.current_value,
                pm.target_value,
                pm.status,
                pm.last_updated
            FROM production_monitoring pm
            JOIN work_orders wo ON pm.work_order_id = wo.id
            JOIN production_lines pl ON pm.production_line_id = pl.id
            WHERE pm.company_id = ?
            ORDER BY pm.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getOperatorInterface() {
        return $this->db->query("
            SELECT
                oi.*,
                e.first_name,
                e.last_name,
                wo.work_order_number,
                oi.interface_type,
                oi.last_activity,
                oi.session_duration,
                oi.productivity_score
            FROM operator_interface oi
            JOIN employees e ON oi.operator_id = e.id
            LEFT JOIN work_orders wo ON oi.work_order_id = wo.id
            WHERE oi.company_id = ?
            ORDER BY oi.last_activity DESC
        ", [$this->user['company_id']]);
    }

    private function getMachineMonitoring() {
        return $this->db->query("
            SELECT
                mm.*,
                pl.line_name,
                mm.machine_name,
                mm.status,
                mm.uptime_percentage,
                mm.efficiency_percentage,
                mm.last_maintenance,
                mm.next_maintenance
            FROM machine_monitoring mm
            JOIN production_lines pl ON mm.production_line_id = pl.id
            WHERE mm.company_id = ?
            ORDER BY mm.status ASC, mm.efficiency_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getDowntimeTracking() {
        return $this->db->query("
            SELECT
                dt.*,
                pl.line_name,
                dt.downtime_reason,
                dt.duration_minutes,
                dt.cost_impact,
                dt.downtime_date,
                dt.resolution_time
            FROM downtime_tracking dt
            JOIN production_lines pl ON dt.production_line_id = pl.id
            WHERE dt.company_id = ?
            ORDER BY dt.downtime_date DESC
        ", [$this->user['company_id']]);
    }

    private function getProductionData() {
        return $this->db->query("
            SELECT
                pd.*,
                wo.work_order_number,
                pd.data_type,
                pd.value,
                pd.unit_of_measure,
                pd.timestamp,
                pd.quality_score
            FROM production_data pd
            JOIN work_orders wo ON pd.work_order_id = wo.id
            WHERE pd.company_id = ?
            ORDER BY pd.timestamp DESC
        ", [$this->user['company_id']]);
    }

    private function getRealTimeAlerts() {
        return $this->db->query("
            SELECT
                rta.*,
                pl.line_name,
                rta.alert_type,
                rta.severity,
                rta.message,
                rta.trigger_value,
                rta.current_value,
                rta.timestamp
            FROM real_time_alerts rta
            JOIN production_lines pl ON rta.production_line_id = pl.id
            WHERE rta.company_id = ? AND rta.status = 'active'
            ORDER BY rta.severity DESC, rta.timestamp DESC
        ", [$this->user['company_id']]);
    }

    private function getShopFloorAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(wo.id) as active_work_orders,
                AVG(pl.capacity_utilization) as avg_capacity_utilization,
                AVG(pl.oee_percentage) as avg_oee,
                SUM(dt.duration_minutes) as total_downtime_minutes,
                COUNT(rta.id) as active_alerts,
                AVG(mm.efficiency_percentage) as avg_machine_efficiency,
                COUNT(CASE WHEN mm.status = 'running' THEN 1 END) as running_machines
            FROM work_orders wo
            JOIN production_lines pl ON wo.production_line_id = pl.id
            LEFT JOIN downtime_tracking dt ON pl.id = dt.production_line_id
            LEFT JOIN real_time_alerts rta ON pl.id = rta.production_line_id AND rta.status = 'active'
            LEFT JOIN machine_monitoring mm ON pl.id = mm.production_line_id
            WHERE wo.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPerformanceDashboards() {
        return $this->db->query("
            SELECT
                pd.*,
                pd.dashboard_name,
                pd.update_frequency,
                pd.last_updated,
                COUNT(pdm.id) as metric_count,
                pd.is_active
            FROM performance_dashboards pd
            LEFT JOIN performance_dashboard_metrics pdm ON pd.id = pdm.dashboard_id
            WHERE pd.company_id = ?
            GROUP BY pd.id
            ORDER BY pd.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getProductionAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(wo.id) as total_work_orders,
                SUM(wo.quantity_produced) as total_produced,
                SUM(wo.quantity_planned) as total_planned,
                ROUND((SUM(wo.quantity_produced) / NULLIF(SUM(wo.quantity_planned), 0)) * 100, 2) as overall_efficiency,
                AVG(wo.actual_cycle_time) as avg_cycle_time,
                AVG(wo.labor_efficiency) as avg_labor_efficiency,
                ROUND((SUM(wo.quantity_scrapped) / NULLIF(SUM(wo.quantity_produced + wo.quantity_scrapped), 0)) * 100, 2) as scrap_rate
            FROM work_orders wo
            WHERE wo.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEfficiencyAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(wo.completed_at, '%Y-%m') as month,
                COUNT(wo.id) as completed_orders,
                SUM(wo.quantity_produced) as total_produced,
                AVG(wo.actual_cycle_time) as avg_cycle_time,
                AVG(wo.labor_efficiency) as avg_efficiency,
                ROUND((SUM(wo.quantity_scrapped) / NULLIF(SUM(wo.quantity_produced + wo.quantity_scrapped), 0)) * 100, 2) as scrap_rate
            FROM work_orders wo
            WHERE wo.company_id = ? AND wo.completed_at IS NOT NULL
            GROUP BY DATE_FORMAT(wo.completed_at, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getCostAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(wo.created_at, '%Y-%m') as month,
                SUM(wo.material_cost) as total_material_cost,
                SUM(wo.labor_cost) as total_labor_cost,
                SUM(wo.overhead_cost) as total_overhead_cost,
                SUM(wo.material_cost + wo.labor_cost + wo.overhead_cost) as total_cost,
                AVG(wo.material_cost + wo.labor_cost + wo.overhead_cost) as avg_cost_per_order,
                SUM(wo.quantity_produced) as total_produced
            FROM work_orders wo
            WHERE wo.company_id = ?
            GROUP BY DATE_FORMAT(wo.created_at, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getPredictiveAnalytics() {
        return $this->db->query("
            SELECT
                pa.*,
                pa.prediction_type,
                pa.prediction_model,
                pa.accuracy_percentage,
                pa.confidence_level,
                pa.last_updated
            FROM predictive_analytics pa
            WHERE pa.company_id = ?
            ORDER BY pa.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getBenchmarkingAnalytics() {
        return $this->db->query("
            SELECT
                ba.*,
                ba.metric_name,
                ba.company_performance,
                ba.industry_average,
                ba.top_performer,
                ba.performance_gap,
                ba.benchmark_date
            FROM benchmarking_analytics ba
            WHERE ba.company_id = ?
            ORDER BY ba.performance_gap DESC
        ", [$this->user['company_id']]);
    }

    private function getManufacturingDashboards() {
        return $this->db->query("
            SELECT
                md.*,
                md.dashboard_name,
                md.update_frequency,
                md.last_updated,
                COUNT(mdm.id) as metric_count,
                md.is_active
            FROM manufacturing_dashboards md
            LEFT JOIN manufacturing_dashboard_metrics mdm ON md.id = mdm.dashboard_id
            WHERE md.company_id = ?
            GROUP BY md.id
            ORDER BY md.last_updated DESC
        ", [$this->user['company_id']]);
    }
}
