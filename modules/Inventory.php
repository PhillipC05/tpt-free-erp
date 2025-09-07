<?php
/**
 * TPT Free ERP - Inventory Management Module
 * Complete product catalog, stock tracking, warehouse management, and optimization
 */

class Inventory extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main inventory dashboard
     */
    public function index() {
        $this->requirePermission('inventory.view');

        $data = [
            'title' => 'Inventory Management',
            'inventory_overview' => $this->getInventoryOverview(),
            'stock_levels' => $this->getStockLevels(),
            'low_stock_alerts' => $this->getLowStockAlerts(),
            'warehouse_status' => $this->getWarehouseStatus(),
            'recent_movements' => $this->getRecentMovements(),
            'inventory_valuation' => $this->getInventoryValuation(),
            'demand_forecast' => $this->getDemandForecast(),
            'abc_analysis' => $this->getABCAnalysis()
        ];

        $this->render('modules/inventory/dashboard', $data);
    }

    /**
     * Product catalog management
     */
    public function products() {
        $this->requirePermission('inventory.products.view');

        $filters = [
            'category' => $_GET['category'] ?? null,
            'supplier' => $_GET['supplier'] ?? null,
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $products = $this->getProducts($filters);

        $data = [
            'title' => 'Product Catalog',
            'products' => $products,
            'filters' => $filters,
            'categories' => $this->getProductCategories(),
            'suppliers' => $this->getSuppliers(),
            'product_templates' => $this->getProductTemplates(),
            'bulk_actions' => $this->getBulkActions(),
            'product_stats' => $this->getProductStats($filters)
        ];

        $this->render('modules/inventory/products', $data);
    }

    /**
     * Stock tracking and management
     */
    public function stock() {
        $this->requirePermission('inventory.stock.view');

        $data = [
            'title' => 'Stock Management',
            'current_stock' => $this->getCurrentStock(),
            'stock_movements' => $this->getStockMovements(),
            'stock_adjustments' => $this->getStockAdjustments(),
            'stock_alerts' => $this->getStockAlerts(),
            'stock_forecasting' => $this->getStockForecasting(),
            'cycle_counts' => $this->getCycleCounts(),
            'stock_valuation' => $this->getStockValuation(),
            'stock_analytics' => $this->getStockAnalytics()
        ];

        $this->render('modules/inventory/stock', $data);
    }

    /**
     * Warehouse management
     */
    public function warehouses() {
        $this->requirePermission('inventory.warehouses.view');

        $data = [
            'title' => 'Warehouse Management',
            'warehouses' => $this->getWarehouses(),
            'warehouse_zones' => $this->getWarehouseZones(),
            'warehouse_locations' => $this->getWarehouseLocations(),
            'warehouse_transfers' => $this->getWarehouseTransfers(),
            'warehouse_inventory' => $this->getWarehouseInventory(),
            'warehouse_layout' => $this->getWarehouseLayout(),
            'warehouse_analytics' => $this->getWarehouseAnalytics(),
            'warehouse_settings' => $this->getWarehouseSettings()
        ];

        $this->render('modules/inventory/warehouses', $data);
    }

    /**
     * Supplier management
     */
    public function suppliers() {
        $this->requirePermission('inventory.suppliers.view');

        $data = [
            'title' => 'Supplier Management',
            'suppliers' => $this->getSuppliers(),
            'supplier_performance' => $this->getSupplierPerformance(),
            'supplier_orders' => $this->getSupplierOrders(),
            'supplier_contracts' => $this->getSupplierContracts(),
            'supplier_evaluations' => $this->getSupplierEvaluations(),
            'supplier_communications' => $this->getSupplierCommunications(),
            'supplier_analytics' => $this->getSupplierAnalytics(),
            'supplier_templates' => $this->getSupplierTemplates()
        ];

        $this->render('modules/inventory/suppliers', $data);
    }

    /**
     * Inventory optimization
     */
    public function optimization() {
        $this->requirePermission('inventory.optimization.view');

        $data = [
            'title' => 'Inventory Optimization',
            'demand_forecasting' => $this->getDemandForecasting(),
            'abc_analysis' => $this->getABCAnalysis(),
            'safety_stock' => $this->getSafetyStock(),
            'reorder_points' => $this->getReorderPoints(),
            'inventory_turnover' => $this->getInventoryTurnover(),
            'stockout_analysis' => $this->getStockoutAnalysis(),
            'optimization_recommendations' => $this->getOptimizationRecommendations(),
            'optimization_settings' => $this->getOptimizationSettings()
        ];

        $this->render('modules/inventory/optimization', $data);
    }

    /**
     * Purchase orders and receiving
     */
    public function purchaseOrders() {
        $this->requirePermission('inventory.purchase_orders.view');

        $data = [
            'title' => 'Purchase Orders',
            'purchase_orders' => $this->getPurchaseOrders(),
            'pending_deliveries' => $this->getPendingDeliveries(),
            'receiving_schedule' => $this->getReceivingSchedule(),
            'quality_inspections' => $this->getQualityInspections(),
            'purchase_analytics' => $this->getPurchaseAnalytics(),
            'supplier_lead_times' => $this->getSupplierLeadTimes(),
            'purchase_templates' => $this->getPurchaseTemplates(),
            'approval_workflow' => $this->getApprovalWorkflow()
        ];

        $this->render('modules/inventory/purchase_orders', $data);
    }

    /**
     * Barcode and QR code management
     */
    public function barcodes() {
        $this->requirePermission('inventory.barcodes.view');

        $data = [
            'title' => 'Barcode Management',
            'barcode_formats' => $this->getBarcodeFormats(),
            'product_barcodes' => $this->getProductBarcodes(),
            'barcode_scanning' => $this->getBarcodeScanning(),
            'barcode_templates' => $this->getBarcodeTemplates(),
            'bulk_barcode_generation' => $this->getBulkBarcodeGeneration(),
            'barcode_analytics' => $this->getBarcodeAnalytics(),
            'integration_settings' => $this->getIntegrationSettings(),
            'barcode_settings' => $this->getBarcodeSettings()
        ];

        $this->render('modules/inventory/barcodes', $data);
    }

    /**
     * Inventory reporting
     */
    public function reporting() {
        $this->requirePermission('inventory.reports.view');

        $data = [
            'title' => 'Inventory Reporting',
            'stock_reports' => $this->getStockReports(),
            'movement_reports' => $this->getMovementReports(),
            'valuation_reports' => $this->getValuationReports(),
            'supplier_reports' => $this->getSupplierReports(),
            'warehouse_reports' => $this->getWarehouseReports(),
            'optimization_reports' => $this->getOptimizationReports(),
            'custom_reports' => $this->getCustomInventoryReports(),
            'report_schedules' => $this->getReportSchedules()
        ];

        $this->render('modules/inventory/reporting', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getInventoryOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT p.id) as total_products,
                COUNT(DISTINCT w.id) as total_warehouses,
                COUNT(DISTINCT s.id) as total_suppliers,
                SUM(ps.quantity_on_hand) as total_stock_quantity,
                SUM(ps.quantity_on_hand * p.unit_cost) as total_inventory_value,
                COUNT(CASE WHEN ps.quantity_on_hand <= ps.reorder_point THEN 1 END) as low_stock_items,
                COUNT(CASE WHEN ps.quantity_on_hand = 0 THEN 1 END) as out_of_stock_items,
                AVG(ps.quantity_on_hand) as avg_stock_level
            FROM products p
            LEFT JOIN product_stock ps ON p.id = ps.product_id
            LEFT JOIN warehouses w ON ps.warehouse_id = w.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getStockLevels() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                ps.quantity_on_hand,
                ps.quantity_reserved,
                ps.quantity_available,
                ps.reorder_point,
                ps.maximum_stock,
                ROUND((ps.quantity_on_hand / NULLIF(ps.reorder_point, 0)) * 100, 2) as stock_percentage,
                CASE
                    WHEN ps.quantity_on_hand = 0 THEN 'out_of_stock'
                    WHEN ps.quantity_on_hand <= ps.reorder_point THEN 'low_stock'
                    WHEN ps.quantity_on_hand >= ps.maximum_stock THEN 'overstock'
                    ELSE 'normal'
                END as stock_status
            FROM products p
            JOIN product_stock ps ON p.id = ps.product_id
            WHERE p.company_id = ?
            ORDER BY ps.quantity_on_hand / NULLIF(ps.reorder_point, 0) ASC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getLowStockAlerts() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                ps.quantity_on_hand,
                ps.reorder_point,
                ps.maximum_stock,
                s.supplier_name,
                w.warehouse_name,
                TIMESTAMPDIFF(DAY, ps.last_stock_check, NOW()) as days_since_check,
                CASE
                    WHEN ps.quantity_on_hand = 0 THEN 'critical'
                    WHEN ps.quantity_on_hand <= ps.reorder_point * 0.5 THEN 'high'
                    ELSE 'medium'
                END as alert_priority
            FROM products p
            JOIN product_stock ps ON p.id = ps.product_id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            LEFT JOIN warehouses w ON ps.warehouse_id = w.id
            WHERE p.company_id = ? AND ps.quantity_on_hand <= ps.reorder_point
            ORDER BY ps.quantity_on_hand ASC
        ", [$this->user['company_id']]);
    }

    private function getWarehouseStatus() {
        return $this->db->query("
            SELECT
                w.warehouse_name,
                w.location,
                w.capacity_sqft,
                w.utilization_percentage,
                COUNT(DISTINCT ps.product_id) as total_products,
                SUM(ps.quantity_on_hand) as total_quantity,
                SUM(ps.quantity_on_hand * p.unit_cost) as total_value,
                w.status,
                w.last_inventory_date
            FROM warehouses w
            LEFT JOIN product_stock ps ON w.id = ps.warehouse_id
            LEFT JOIN products p ON ps.product_id = p.id
            WHERE w.company_id = ?
            GROUP BY w.id, w.warehouse_name, w.location, w.capacity_sqft, w.utilization_percentage, w.status, w.last_inventory_date
            ORDER BY w.utilization_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getRecentMovements() {
        return $this->db->query("
            SELECT
                sm.*,
                p.product_name,
                p.sku,
                w_from.warehouse_name as from_warehouse,
                w_to.warehouse_name as to_warehouse,
                u.first_name as moved_by_first,
                u.last_name as moved_by_last,
                sm.movement_type,
                sm.quantity,
                sm.reason
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            LEFT JOIN warehouses w_from ON sm.from_warehouse_id = w_from.id
            LEFT JOIN warehouses w_to ON sm.to_warehouse_id = w_to.id
            LEFT JOIN users u ON sm.moved_by = u.id
            WHERE sm.company_id = ?
            ORDER BY sm.movement_date DESC
            LIMIT 25
        ", [$this->user['company_id']]);
    }

    private function getInventoryValuation() {
        return $this->db->querySingle("
            SELECT
                SUM(ps.quantity_on_hand * p.unit_cost) as total_inventory_value,
                SUM(ps.quantity_on_hand * p.selling_price) as potential_sales_value,
                AVG(p.unit_cost) as avg_cost_per_unit,
                AVG(p.selling_price) as avg_selling_price,
                AVG(p.selling_price - p.unit_cost) as avg_gross_margin,
                ROUND(AVG((p.selling_price - p.unit_cost) / NULLIF(p.selling_price, 0)) * 100, 2) as avg_margin_percentage,
                COUNT(CASE WHEN p.selling_price > p.unit_cost THEN 1 END) as profitable_products
            FROM products p
            LEFT JOIN product_stock ps ON p.id = ps.product_id
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getDemandForecast() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                df.forecast_period,
                df.historical_demand,
                df.forecasted_demand,
                df.confidence_level,
                df.forecast_accuracy,
                ROUND(((df.forecasted_demand - df.historical_demand) / NULLIF(df.historical_demand, 0)) * 100, 2) as growth_percentage
            FROM products p
            JOIN demand_forecast df ON p.id = df.product_id
            WHERE p.company_id = ?
            ORDER BY df.forecast_period ASC, df.confidence_level DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getABCAnalysis() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                abc.annual_demand_value,
                abc.cumulative_percentage,
                abc.abc_classification,
                abc.reorder_frequency,
                abc.safety_stock_days,
                abc.lead_time_days
            FROM products p
            JOIN abc_analysis abc ON p.id = abc.product_id
            WHERE p.company_id = ?
            ORDER BY abc.annual_demand_value DESC
        ", [$this->user['company_id']]);
    }

    private function getProducts($filters) {
        $where = ["p.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['category']) {
            $where[] = "p.category_id = ?";
            $params[] = $filters['category'];
        }

        if ($filters['supplier']) {
            $where[] = "p.supplier_id = ?";
            $params[] = $filters['supplier'];
        }

        if ($filters['status']) {
            $where[] = "p.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['search']) {
            $where[] = "(p.product_name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                p.*,
                pc.category_name,
                s.supplier_name,
                ps.quantity_on_hand,
                ps.quantity_available,
                ps.reorder_point,
                p.unit_cost,
                p.selling_price,
                ROUND((p.selling_price - p.unit_cost) / NULLIF(p.selling_price, 0) * 100, 2) as profit_margin,
                p.created_at,
                p.updated_at
            FROM products p
            LEFT JOIN product_categories pc ON p.category_id = pc.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            LEFT JOIN product_stock ps ON p.id = ps.product_id
            WHERE $whereClause
            ORDER BY p.product_name ASC
        ", $params);
    }

    private function getProductCategories() {
        return $this->db->query("
            SELECT
                pc.*,
                COUNT(p.id) as product_count,
                SUM(ps.quantity_on_hand) as total_stock,
                SUM(ps.quantity_on_hand * p.unit_cost) as total_value
            FROM product_categories pc
            LEFT JOIN products p ON pc.id = p.category_id
            LEFT JOIN product_stock ps ON p.id = ps.product_id
            WHERE pc.company_id = ?
            GROUP BY pc.id
            ORDER BY pc.category_name ASC
        ", [$this->user['company_id']]);
    }

    private function getSuppliers() {
        return $this->db->query("
            SELECT
                s.*,
                COUNT(p.id) as product_count,
                COUNT(po.id) as order_count,
                AVG(s.lead_time_days) as avg_lead_time,
                s.performance_rating,
                s.last_order_date
            FROM suppliers s
            LEFT JOIN products p ON s.id = p.supplier_id
            LEFT JOIN purchase_orders po ON s.id = po.supplier_id
            WHERE s.company_id = ?
            GROUP BY s.id
            ORDER BY s.supplier_name ASC
        ", [$this->user['company_id']]);
    }

    private function getProductTemplates() {
        return $this->db->query("
            SELECT * FROM product_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBulkActions() {
        return [
            'update_prices' => 'Update Prices',
            'update_stock' => 'Update Stock Levels',
            'change_category' => 'Change Category',
            'update_suppliers' => 'Update Suppliers',
            'export_products' => 'Export Products',
            'import_products' => 'Import Products',
            'delete_products' => 'Delete Products',
            'duplicate_products' => 'Duplicate Products'
        ];
    }

    private function getProductStats($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['category']) {
            $where[] = "category_id = ?";
            $params[] = $filters['category'];
        }

        if ($filters['supplier']) {
            $where[] = "supplier_id = ?";
            $params[] = $filters['supplier'];
        }

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_products,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_products,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_products,
                AVG(unit_cost) as avg_cost,
                AVG(selling_price) as avg_price,
                SUM(unit_cost * quantity_on_hand) as total_value
            FROM products p
            LEFT JOIN product_stock ps ON p.id = ps.product_id
            WHERE $whereClause
        ", $params);
    }

    private function getCurrentStock() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                w.warehouse_name,
                ps.quantity_on_hand,
                ps.quantity_reserved,
                ps.quantity_available,
                ps.reorder_point,
                ps.maximum_stock,
                ps.last_stock_check,
                ps.location_code
            FROM products p
            JOIN product_stock ps ON p.id = ps.product_id
            JOIN warehouses w ON ps.warehouse_id = w.id
            WHERE p.company_id = ?
            ORDER BY p.product_name, w.warehouse_name
        ", [$this->user['company_id']]);
    }

    private function getStockMovements() {
        return $this->db->query("
            SELECT
                sm.*,
                p.product_name,
                p.sku,
                w_from.warehouse_name as from_warehouse,
                w_to.warehouse_name as to_warehouse,
                u.first_name as moved_by_first,
                u.last_name as moved_by_last,
                sm.movement_type,
                sm.quantity,
                sm.unit_cost,
                sm.total_value,
                sm.reason
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            LEFT JOIN warehouses w_from ON sm.from_warehouse_id = w_from.id
            LEFT JOIN warehouses w_to ON sm.to_warehouse_id = w_to.id
            LEFT JOIN users u ON sm.moved_by = u.id
            WHERE sm.company_id = ?
            ORDER BY sm.movement_date DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getStockAdjustments() {
        return $this->db->query("
            SELECT
                sa.*,
                p.product_name,
                p.sku,
                w.warehouse_name,
                u.first_name as adjusted_by_first,
                u.last_name as adjusted_by_last,
                sa.adjustment_type,
                sa.quantity_before,
                sa.quantity_after,
                sa.adjustment_quantity,
                sa.reason,
                sa.approved_by
            FROM stock_adjustments sa
            JOIN products p ON sa.product_id = p.id
            JOIN warehouses w ON sa.warehouse_id = w.id
            LEFT JOIN users u ON sa.adjusted_by = u.id
            WHERE sa.company_id = ?
            ORDER BY sa.adjustment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getStockAlerts() {
        return $this->db->query("
            SELECT
                sa.*,
                p.product_name,
                p.sku,
                w.warehouse_name,
                sa.alert_type,
                sa.threshold_value,
                sa.current_value,
                sa.alert_message,
                sa.is_active,
                sa.last_triggered
            FROM stock_alerts sa
            JOIN products p ON sa.product_id = p.id
            JOIN warehouses w ON sa.warehouse_id = w.id
            WHERE sa.company_id = ?
            ORDER BY sa.alert_type, sa.last_triggered DESC
        ", [$this->user['company_id']]);
    }

    private function getStockForecasting() {
        return $this->db->query("
            SELECT
                sf.*,
                p.product_name,
                p.sku,
                sf.forecast_method,
                sf.forecast_period,
                sf.historical_data_points,
                sf.forecast_accuracy,
                sf.confidence_interval,
                sf.last_updated
            FROM stock_forecasting sf
            JOIN products p ON sf.product_id = p.id
            WHERE sf.company_id = ?
            ORDER BY sf.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getCycleCounts() {
        return $this->db->query("
            SELECT
                cc.*,
                w.warehouse_name,
                u.first_name as counted_by_first,
                u.last_name as counted_by_last,
                cc.cycle_count_type,
                cc.total_items,
                cc.items_counted,
                cc.discrepancies_found,
                cc.accuracy_percentage,
                cc.status
            FROM cycle_counts cc
            JOIN warehouses w ON cc.warehouse_id = w.id
            LEFT JOIN users u ON cc.counted_by = u.id
            WHERE cc.company_id = ?
            ORDER BY cc.scheduled_date DESC
        ", [$this->user['company_id']]);
    }

    private function getStockValuation() {
        return $this->db->query("
            SELECT
                w.warehouse_name,
                COUNT(DISTINCT p.id) as total_products,
                SUM(ps.quantity_on_hand) as total_quantity,
                SUM(ps.quantity_on_hand * p.unit_cost) as total_cost_value,
                SUM(ps.quantity_on_hand * p.selling_price) as total_retail_value,
                AVG(p.unit_cost) as avg_cost,
                AVG(p.selling_price) as avg_retail,
                AVG(p.selling_price - p.unit_cost) as avg_margin
            FROM warehouses w
            LEFT JOIN product_stock ps ON w.id = ps.warehouse_id
            LEFT JOIN products p ON ps.product_id = p.id
            WHERE w.company_id = ?
            GROUP BY w.id, w.warehouse_name
            ORDER BY total_cost_value DESC
        ", [$this->user['company_id']]);
    }

    private function getStockAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT p.id) as total_products,
                SUM(ps.quantity_on_hand) as total_stock,
                AVG(ps.quantity_on_hand) as avg_stock_per_product,
                COUNT(CASE WHEN ps.quantity_on_hand = 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN ps.quantity_on_hand <= ps.reorder_point THEN 1 END) as low_stock,
                COUNT(CASE WHEN ps.quantity_on_hand >= ps.maximum_stock THEN 1 END) as overstock,
                AVG(TIMESTAMPDIFF(DAY, ps.last_movement_date, NOW())) as avg_days_since_movement
            FROM products p
            LEFT JOIN product_stock ps ON p.id = ps.product_id
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getWarehouses() {
        return $this->db->query("
            SELECT
                w.*,
                COUNT(DISTINCT ps.product_id) as total_products,
                SUM(ps.quantity_on_hand) as total_quantity,
                SUM(ps.quantity_on_hand * p.unit_cost) as total_value,
                w.capacity_sqft,
                w.utilization_percentage,
                w.last_inventory_date
            FROM warehouses w
            LEFT JOIN product_stock ps ON w.id = ps.warehouse_id
            LEFT JOIN products p ON ps.product_id = p.id
            WHERE w.company_id = ?
            GROUP BY w.id
            ORDER BY w.warehouse_name ASC
        ", [$this->user['company_id']]);
    }

    private function getWarehouseZones() {
        return $this->db->query("
            SELECT
                wz.*,
                w.warehouse_name,
                wz.zone_type,
                wz.capacity_sqft,
                wz.utilization_percentage,
                COUNT(wl.id) as total_locations
            FROM warehouse_zones wz
            JOIN warehouses w ON wz.warehouse_id = w.id
            LEFT JOIN warehouse_locations wl ON wz.id = wl.zone_id
            WHERE wz.company_id = ?
            GROUP BY wz.id, w.warehouse_name
            ORDER BY w.warehouse_name, wz.zone_name
        ", [$this->user['company_id']]);
    }

    private function getWarehouseLocations() {
        return $this->db->query("
            SELECT
                wl.*,
                w.warehouse_name,
                wz.zone_name,
                p.product_name,
                wl.location_code,
                wl.capacity_units,
                wl.occupied_units,
                wl.is_occupied
            FROM warehouse_locations wl
            JOIN warehouses w ON wl.warehouse_id = w.id
            JOIN warehouse_zones wz ON wl.zone_id = wz.id
            LEFT JOIN products p ON wl.product_id = p.id
            WHERE wl.company_id = ?
            ORDER BY w.warehouse_name, wz.zone_name, wl.location_code
        ", [$this->user['company_id']]);
    }

    private function getWarehouseTransfers() {
        return $this->db->query("
            SELECT
                wt.*,
                w_from.warehouse_name as from_warehouse,
                w_to.warehouse_name as to_warehouse,
                u.first_name as requested_by_first,
                u.last_name as requested_by_last,
                wt.transfer_status,
                wt.total_items,
                wt.transfer_date,
                wt.expected_delivery_date
            FROM warehouse_transfers wt
            JOIN warehouses w_from ON wt.from_warehouse_id = w_from.id
            JOIN warehouses w_to ON wt.to_warehouse_id = w_to.id
            LEFT JOIN users u ON wt.requested_by = u.id
            WHERE wt.company_id = ?
            ORDER BY wt.transfer_date DESC
        ", [$this->user['company_id']]);
    }

    private function getWarehouseInventory() {
        return $this->db->query("
            SELECT
                w.warehouse_name,
                wz.zone_name,
                wl.location_code,
                p.product_name,
                p.sku,
                ps.quantity_on_hand,
                ps.quantity_available,
                wl.capacity_units,
                wl.occupied_units
            FROM warehouses w
            JOIN warehouse_zones wz ON w.id = wz.warehouse_id
            JOIN warehouse_locations wl ON wz.id = wl.zone_id
            LEFT JOIN product_stock ps ON wl.product_id = ps.product_id AND w.id = ps.warehouse_id
            LEFT JOIN products p ON wl.product_id = p.id
            WHERE w.company_id = ?
            ORDER BY w.warehouse_name, wz.zone_name, wl.location_code
        ", [$this->user['company_id']]);
    }

    private function getWarehouseLayout() {
        return $this->db->query("
            SELECT
                w.warehouse_name,
                w.layout_data,
                w.capacity_sqft,
                w.utilization_percentage,
                COUNT(wz.id) as total_zones,
                COUNT(wl.id) as total_locations,
                COUNT(CASE WHEN wl.is_occupied = true THEN 1 END) as occupied_locations
            FROM warehouses w
            LEFT JOIN warehouse_zones wz ON w.id = wz.warehouse_id
            LEFT JOIN warehouse_locations wl ON wz.id = wl.zone_id
            WHERE w.company_id = ?
            GROUP BY w.id, w.warehouse_name, w.layout_data, w.capacity_sqft, w.utilization_percentage
        ", [$this->user['company_id']]);
    }

    private function getWarehouseAnalytics() {
        return $this->db->query("
            SELECT
                w.warehouse_name,
                COUNT(DISTINCT p.id) as unique_products,
                SUM(ps.quantity_on_hand) as total_stock,
                AVG(ps.quantity_on_hand) as avg_stock_per_product,
                COUNT(wt.id) as total_transfers,
                AVG(wt.transfer_time_days) as avg_transfer_time,
                w.utilization_percentage,
                w.inventory_accuracy_percentage
            FROM warehouses w
            LEFT JOIN product_stock ps ON w.id = ps.warehouse_id
            LEFT JOIN products p ON ps.product_id = p.id
            LEFT JOIN warehouse_transfers wt ON w.id = wt.from_warehouse_id OR w.id = wt.to_warehouse_id
            WHERE w.company_id = ?
            GROUP BY w.id, w.warehouse_name, w.utilization_percentage, w.inventory_accuracy_percentage
            ORDER BY w.utilization_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getWarehouseSettings() {
        return $this->db->querySingle("
            SELECT * FROM warehouse_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSupplierPerformance() {
        return $this->db->query("
            SELECT
                s.supplier_name,
                sp.rating_period,
                sp.on_time_delivery_rate,
                sp.quality_rating,
                sp.price_competitiveness,
                sp.overall_rating,
                sp.lead_time_days,
                sp.defect_rate_percentage
            FROM suppliers s
            JOIN supplier_performance sp ON s.id = sp.supplier_id
            WHERE s.company_id = ?
            ORDER BY sp.rating_period DESC, sp.overall_rating DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierOrders() {
        return $this->db->query("
            SELECT
                po.*,
                s.supplier_name,
                u.first_name as ordered_by_first,
                u.last_name as ordered_by_last,
                po.order_status,
                po.total_amount,
                po.expected_delivery_date,
                TIMESTAMPDIFF(DAY, po.order_date, NOW()) as days_since_order
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN users u ON po.ordered_by = u.id
            WHERE po.company_id = ?
            ORDER BY po.order_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierContracts() {
        return $this->db->query("
            SELECT
                sc.*,
                s.supplier_name,
                u.first_name as negotiated_by_first,
                u.last_name as negotiated_by_last,
                sc.contract_type,
                sc.start_date,
                sc.end_date,
                sc.contract_value,
                TIMESTAMPDIFF(DAY, CURDATE(), sc.end_date) as days_until_expiry
            FROM supplier_contracts sc
            JOIN suppliers s ON sc.supplier_id = s.id
            LEFT JOIN users u ON sc.negotiated_by = u.id
            WHERE sc.company_id = ?
            ORDER BY sc.end_date ASC
        ", [$this->user['company_id']]);
    }

    private function getSupplierEvaluations() {
        return $this->db->query("
            SELECT
                se.*,
                s.supplier_name,
                u.first_name as evaluated_by_first,
                u.last_name as evaluated_by_last,
                se.evaluation_criteria,
                se.rating_score,
                se.comments,
                se.evaluation_date
            FROM supplier_evaluations se
            JOIN suppliers s ON se.supplier_id = s.id
            LEFT JOIN users u ON se.evaluated_by = u.id
            WHERE se.company_id = ?
            ORDER BY se.evaluation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierCommunications() {
        return $this->db->query("
            SELECT
                sc.*,
                s.supplier_name,
                u.first_name as sent_by_first,
                u.last_name as sent_by_last,
                sc.communication_type,
                sc.subject,
                sc.sent_date,
                sc.response_received
            FROM supplier_communications sc
            JOIN suppliers s ON sc.supplier_id = s.id
            LEFT JOIN users u ON sc.sent_by = u.id
            WHERE sc.company_id = ?
            ORDER BY sc.sent_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierAnalytics() {
        return $this->db->query("
            SELECT
                s.supplier_name,
                COUNT(po.id) as total_orders,
                SUM(po.total_amount) as total_order_value,
                AVG(po.total_amount) as avg_order_value,
                AVG(sp.on_time_delivery_rate) as avg_delivery_rate,
                AVG(sp.quality_rating) as avg_quality_rating,
                AVG(sp.lead_time_days) as avg_lead_time,
                MAX(po.order_date) as last_order_date
            FROM suppliers s
            LEFT JOIN purchase_orders po ON s.id = po.supplier_id
            LEFT JOIN supplier_performance sp ON s.id = sp.supplier_id
            WHERE s.company_id = ?
            GROUP BY s.id, s.supplier_name
            ORDER BY total_order_value DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierTemplates() {
        return $this->db->query("
            SELECT * FROM supplier_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDemandForecasting() {
        return $this->db->query("
            SELECT
                df.*,
                p.product_name,
                p.sku,
                df.forecast_method,
                df.forecast_period,
                df.actual_demand,
                df.forecasted_demand,
                df.accuracy_percentage,
                df.confidence_level
            FROM demand_forecasting df
            JOIN products p ON df.product_id = p.id
            WHERE df.company_id = ?
            ORDER BY df.forecast_period ASC, df.confidence_level DESC
        ", [$this->user['company_id']]);
    }

    private function getSafetyStock() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                ss.safety_stock_level,
                ss.reorder_point,
                ss.lead_time_days,
                ss.demand_variability,
                ss.service_level_target,
                ss.calculated_date
            FROM products p
            JOIN safety_stock ss ON p.id = ss.product_id
            WHERE p.company_id = ?
            ORDER BY ss.safety_stock_level DESC
        ", [$this->user['company_id']]);
    }

    private function getReorderPoints() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                rp.reorder_point,
                rp.safety_stock,
                rp.lead_time_demand,
                rp.average_daily_demand,
                rp.reorder_quantity,
                rp.last_calculated
            FROM products p
            JOIN reorder_points rp ON p.id = rp.product_id
            WHERE p.company_id = ?
            ORDER BY rp.reorder_point ASC
        ", [$this->user['company_id']]);
    }

    private function getInventoryTurnover() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                it.turnover_ratio,
                it.cost_of_goods_sold,
                it.average_inventory_value,
                it.turnover_period_days,
                it.turnover_efficiency,
                it.calculation_period
            FROM products p
            JOIN inventory_turnover it ON p.id = it.product_id
            WHERE p.company_id = ?
            ORDER BY it.turnover_ratio DESC
        ", [$this->user['company_id']]);
    }

    private function getStockoutAnalysis() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                sa.stockout_events,
                sa.total_demand_during_stockout,
                sa.avg_stockout_duration_days,
                sa.stockout_cost,
                sa.preventive_actions,
                sa.last_stockout_date
            FROM products p
            JOIN stockout_analysis sa ON p.id = sa.product_id
            WHERE p.company_id = ?
            ORDER BY sa.stockout_events DESC
        ", [$this->user['company_id']]);
    }

    private function getOptimizationRecommendations() {
        return $this->db->query("
            SELECT
                orr.*,
                p.product_name,
                p.sku,
                orr.recommendation_type,
                orr.priority_level,
                orr.potential_savings,
                orr.implementation_complexity,
                orr.expected_roi
            FROM optimization_recommendations orr
            JOIN products p ON orr.product_id = p.id
            WHERE orr.company_id = ?
            ORDER BY orr.priority_level ASC, orr.potential_savings DESC
        ", [$this->user['company_id']]);
    }

    private function getOptimizationSettings() {
        return $this->db->querySingle("
            SELECT * FROM optimization_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPurchaseOrders() {
        return $this->db->query("
            SELECT
                po.*,
                s.supplier_name,
                u.first_name as ordered_by_first,
                u.last_name as ordered_by_last,
                po.order_status,
                po.total_amount,
                po.expected_delivery_date,
                TIMESTAMPDIFF(DAY, po.order_date, NOW()) as days_since_order,
                COUNT(poi.id) as total_items
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN users u ON po.ordered_by = u.id
            LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            WHERE po.company_id = ?
            GROUP BY po.id, s.supplier_name, u.first_name, u.last_name
            ORDER BY po.order_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPendingDeliveries() {
        return $this->db->query("
            SELECT
                po.order_number,
                s.supplier_name,
                poi.product_name,
                poi.quantity_ordered,
                poi.quantity_received,
                poi.quantity_pending,
                po.expected_delivery_date,
                TIMESTAMPDIFF(DAY, CURDATE(), po.expected_delivery_date) as days_until_delivery
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            WHERE po.company_id = ? AND poi.quantity_pending > 0
            ORDER BY po.expected_delivery_date ASC
        ", [$this->user['company_id']]);
    }

    private function getReceivingSchedule() {
        return $this->db->query("
            SELECT
                po.order_number,
                s.supplier_name,
                po.expected_delivery_date,
                COUNT(poi.id) as total_items,
                SUM(poi.quantity_pending) as pending_quantity,
                SUM(poi.quantity_ordered * poi.unit_price) as pending_value
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            WHERE po.company_id = ? AND po.order_status = 'approved' AND poi.quantity_pending > 0
            GROUP BY po.id, po.order_number, s.supplier_name, po.expected_delivery_date
            ORDER BY po.expected_delivery_date ASC
        ", [$this->user['company_id']]);
    }

    private function getQualityInspections() {
        return $this->db->query("
            SELECT
                qi.*,
                po.order_number,
                s.supplier_name,
                p.product_name,
                u.first_name as inspected_by_first,
                u.last_name as inspected_by_last,
                qi.inspection_result,
                qi.defect_quantity,
                qi.accepted_quantity,
                qi.rejected_quantity
            FROM quality_inspections qi
            JOIN purchase_orders po ON qi.purchase_order_id = po.id
            JOIN suppliers s ON po.supplier_id = s.id
            JOIN products p ON qi.product_id = p.id
            LEFT JOIN users u ON qi.inspected_by = u.id
            WHERE qi.company_id = ?
            ORDER BY qi.inspection_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPurchaseAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(po.id) as total_orders,
                SUM(po.total_amount) as total_purchase_value,
                AVG(po.total_amount) as avg_order_value,
                COUNT(DISTINCT s.id) as unique_suppliers,
                AVG(s.lead_time_days) as avg_lead_time,
                COUNT(CASE WHEN po.order_status = 'delivered' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN po.expected_delivery_date < CURDATE() AND po.order_status != 'delivered' THEN 1 END) as overdue_deliveries
            FROM purchase_orders po
            LEFT JOIN suppliers s ON po.supplier_id = s.id
            WHERE po.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSupplierLeadTimes() {
        return $this->db->query("
            SELECT
                s.supplier_name,
                AVG(TIMESTAMPDIFF(DAY, po.order_date, po.actual_delivery_date)) as avg_lead_time_days,
                MIN(TIMESTAMPDIFF(DAY, po.order_date, po.actual_delivery_date)) as min_lead_time,
                MAX(TIMESTAMPDIFF(DAY, po.order_date, po.actual_delivery_date)) as max_lead_time,
                COUNT(po.id) as total_orders,
                COUNT(CASE WHEN po.actual_delivery_date <= po.expected_delivery_date THEN 1 END) as on_time_deliveries
            FROM suppliers s
            LEFT JOIN purchase_orders po ON s.id = po.supplier_id AND po.order_status = 'delivered'
            WHERE s.company_id = ?
            GROUP BY s.id, s.supplier_name
            ORDER BY avg_lead_time_days ASC
        ", [$this->user['company_id']]);
    }

    private function getPurchaseTemplates() {
        return $this->db->query("
            SELECT * FROM purchase_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getApprovalWorkflow() {
        return $this->db->query("
            SELECT * FROM purchase_approval_workflow
            WHERE company_id = ? AND is_active = true
            ORDER BY approval_level ASC
        ", [$this->user['company_id']]);
    }

    private function getBarcodeFormats() {
        return [
            'code128' => 'Code 128',
            'code39' => 'Code 39',
            'ean13' => 'EAN-13',
            'upc' => 'UPC-A',
            'qr' => 'QR Code',
            'datamatrix' => 'Data Matrix',
            'pdf417' => 'PDF417'
        ];
    }

    private function getProductBarcodes() {
        return $this->db->query("
            SELECT
                pb.*,
                p.product_name,
                p.sku,
                pb.barcode_format,
                pb.barcode_value,
                pb.is_active,
                pb.print_count
            FROM product_barcodes pb
            JOIN products p ON pb.product_id = p.id
            WHERE pb.company_id = ?
            ORDER BY p.product_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBarcodeScanning() {
        return $this->db->query("
            SELECT
                bs.*,
                p.product_name,
                p.sku,
                u.first_name as scanned_by_first,
                u.last_name as scanned_by_last,
                bs.scan_type,
                bs.quantity_scanned,
