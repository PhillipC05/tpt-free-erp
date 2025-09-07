<?php
/**
 * TPT Free ERP - Inventory Module
 * Complete product catalog, stock tracking, warehouse management, and supply chain system
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
            'warehouse_status' => $this->getWarehouseStatus(),
            'product_performance' => $this->getProductPerformance(),
            'supplier_performance' => $this->getSupplierPerformance(),
            'inventory_alerts' => $this->getInventoryAlerts(),
            'stock_movements' => $this->getStockMovements(),
            'inventory_analytics' => $this->getInventoryAnalytics(),
            'upcoming_deliveries' => $this->getUpcomingDeliveries()
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
            'stock_level' => $_GET['stock_level'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $products = $this->getProducts($filters);

        $data = [
            'title' => 'Product Catalog',
            'products' => $products,
            'filters' => $filters,
            'product_categories' => $this->getProductCategories(),
            'suppliers' => $this->getSuppliers(),
            'product_status' => $this->getProductStatus(),
            'product_templates' => $this->getProductTemplates(),
            'bulk_actions' => $this->getBulkActions(),
            'product_analytics' => $this->getProductAnalytics()
        ];

        $this->render('modules/inventory/products', $data);
    }

    /**
     * Stock tracking and alerts
     */
    public function stockTracking() {
        $this->requirePermission('inventory.stock.view');

        $data = [
            'title' => 'Stock Tracking',
            'current_stock' => $this->getCurrentStock(),
            'stock_alerts' => $this->getStockAlerts(),
            'stock_movements' => $this->getStockMovements(),
            'stock_forecasting' => $this->getStockForecasting(),
            'stock_valuation' => $this->getStockValuation(),
            'stock_aging' => $this->getStockAging(),
            'stock_turnover' => $this->getStockTurnover(),
            'stock_analytics' => $this->getStockAnalytics(),
            'stock_settings' => $this->getStockSettings()
        ];

        $this->render('modules/inventory/stock_tracking', $data);
    }

    /**
     * Warehouse management
     */
    public function warehouses() {
        $this->requirePermission('inventory.warehouses.view');

        $data = [
            'title' => 'Warehouse Management',
            'warehouses' => $this->getWarehouses(),
            'warehouse_layout' => $this->getWarehouseLayout(),
            'bin_locations' => $this->getBinLocations(),
            'warehouse_transfers' => $this->getWarehouseTransfers(),
            'warehouse_inventory' => $this->getWarehouseInventory(),
            'warehouse_utilization' => $this->getWarehouseUtilization(),
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
            'supplier_contracts' => $this->getSupplierContracts(),
            'supplier_ratings' => $this->getSupplierRatings(),
            'supplier_communications' => $this->getSupplierCommunications(),
            'supplier_analytics' => $this->getSupplierAnalytics(),
            'supplier_templates' => $this->getSupplierTemplates(),
            'supplier_settings' => $this->getSupplierSettings()
        ];

        $this->render('modules/inventory/suppliers', $data);
    }

    /**
     * Purchase orders
     */
    public function purchaseOrders() {
        $this->requirePermission('inventory.purchase_orders.view');

        $data = [
            'title' => 'Purchase Orders',
            'purchase_orders' => $this->getPurchaseOrders(),
            'po_approvals' => $this->getPOApprovals(),
            'po_receiving' => $this->getPOReceiving(),
            'po_invoicing' => $this->getPOInvoicing(),
            'po_returns' => $this->getPOReturns(),
            'po_analytics' => $this->getPOAnalytics(),
            'po_templates' => $this->getPOTemplates(),
            'po_settings' => $this->getPOSettings()
        ];

        $this->render('modules/inventory/purchase_orders', $data);
    }

    /**
     * Inventory optimization
     */
    public function optimization() {
        $this->requirePermission('inventory.optimization.view');

        $data = [
            'title' => 'Inventory Optimization',
            'demand_forecasting' => $this->getDemandForecasting(),
            'reorder_point_calculation' => $this->getReorderPointCalculation(),
            'abc_analysis' => $this->getABCAnalysis(),
            'safety_stock_calculation' => $this->getSafetyStockCalculation(),
            'inventory_policies' => $this->getInventoryPolicies(),
            'optimization_recommendations' => $this->getOptimizationRecommendations(),
            'optimization_analytics' => $this->getOptimizationAnalytics(),
            'optimization_settings' => $this->getOptimizationSettings()
        ];

        $this->render('modules/inventory/optimization', $data);
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
            'performance_reports' => $this->getPerformanceReports(),
            'custom_reports' => $this->getCustomReports(),
            'report_schedules' => $this->getReportSchedules()
        ];

        $this->render('modules/inventory/reporting', $data);
    }

    /**
     * Barcode and RFID management
     */
    public function barcodes() {
        $this->requirePermission('inventory.barcodes.view');

        $data = [
            'title' => 'Barcode & RFID Management',
            'barcode_generation' => $this->getBarcodeGeneration(),
            'rfid_tracking' => $this->getRFIDTracking(),
            'barcode_scanning' => $this->getBarcodeScanning(),
            'label_printing' => $this->getLabelPrinting(),
            'barcode_analytics' => $this->getBarcodeAnalytics(),
            'barcode_templates' => $this->getBarcodeTemplates(),
            'barcode_settings' => $this->getBarcodeSettings()
        ];

        $this->render('modules/inventory/barcodes', $data);
    }

    /**
     * Inventory analytics
     */
    public function analytics() {
        $this->requirePermission('inventory.analytics.view');

        $data = [
            'title' => 'Inventory Analytics',
            'stock_trends' => $this->getStockTrends(),
            'product_performance' => $this->getProductPerformance(),
            'warehouse_efficiency' => $this->getWarehouseEfficiency(),
            'supplier_performance' => $this->getSupplierPerformance(),
            'cost_analysis' => $this->getCostAnalysis(),
            'demand_patterns' => $this->getDemandPatterns(),
            'forecast_accuracy' => $this->getForecastAccuracy(),
            'custom_dashboards' => $this->getCustomDashboards()
        ];

        $this->render('modules/inventory/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getInventoryOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT p.id) as total_products,
                COUNT(CASE WHEN p.status = 'active' THEN 1 END) as active_products,
                SUM(p.stock_quantity) as total_stock_quantity,
                SUM(p.stock_quantity * p.unit_cost) as total_inventory_value,
                COUNT(CASE WHEN p.stock_quantity <= p.reorder_point THEN 1 END) as low_stock_items,
                COUNT(CASE WHEN p.stock_quantity = 0 THEN 1 END) as out_of_stock_items,
                COUNT(DISTINCT w.id) as total_warehouses,
                COUNT(DISTINCT s.id) as total_suppliers,
                AVG(p.stock_quantity) as avg_stock_level
            FROM products p
            LEFT JOIN warehouses w ON w.company_id = p.company_id
            LEFT JOIN suppliers s ON s.company_id = p.company_id
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getStockLevels() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                p.stock_quantity,
                p.reorder_point,
                p.maximum_stock,
                p.unit_cost,
                (p.stock_quantity * p.unit_cost) as stock_value,
                ROUND((p.stock_quantity / NULLIF(p.reorder_point, 0)) * 100, 2) as stock_percentage,
                CASE
                    WHEN p.stock_quantity = 0 THEN 'out_of_stock'
                    WHEN p.stock_quantity <= p.reorder_point THEN 'low_stock'
                    WHEN p.stock_quantity >= p.maximum_stock THEN 'overstock'
                    ELSE 'normal'
                END as stock_status,
                w.warehouse_name,
                p.last_stock_update
            FROM products p
            LEFT JOIN warehouses w ON p.warehouse_id = w.id
            WHERE p.company_id = ?
            ORDER BY
                CASE
                    WHEN p.stock_quantity = 0 THEN 1
                    WHEN p.stock_quantity <= p.reorder_point THEN 2
                    WHEN p.stock_quantity >= p.maximum_stock THEN 3
                    ELSE 4
                END,
                p.stock_quantity ASC
        ", [$this->user['company_id']]);
    }

    private function getWarehouseStatus() {
        return $this->db->query("
            SELECT
                w.warehouse_name,
                w.location,
                w.capacity,
                w.utilization_percentage,
                COUNT(p.id) as total_products,
                SUM(p.stock_quantity) as total_stock,
                SUM(p.stock_quantity * p.unit_cost) as total_value,
                COUNT(CASE WHEN p.stock_quantity <= p.reorder_point THEN 1 END) as low_stock_items,
                w.last_inventory_count,
                w.status
            FROM warehouses w
            LEFT JOIN products p ON w.id = p.warehouse_id
            WHERE w.company_id = ?
            GROUP BY w.id, w.warehouse_name, w.location, w.capacity, w.utilization_percentage, w.last_inventory_count, w.status
            ORDER BY w.utilization_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getProductPerformance() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                SUM(sm.quantity) as total_sold,
                SUM(sm.quantity * p.unit_price) as total_revenue,
                AVG(p.unit_price) as avg_selling_price,
                COUNT(DISTINCT sm.order_id) as total_orders,
                SUM(sm.quantity) / NULLIF(AVG(p.stock_quantity), 0) as turnover_ratio,
                MAX(sm.movement_date) as last_sale_date,
                p.profit_margin_percentage
            FROM products p
            LEFT JOIN stock_movements sm ON p.id = sm.product_id AND sm.movement_type = 'sale'
            WHERE p.company_id = ?
            GROUP BY p.id, p.product_name, p.sku, p.unit_price, p.stock_quantity, p.profit_margin_percentage
            ORDER BY total_revenue DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierPerformance() {
        return $this->db->query("
            SELECT
                s.supplier_name,
                s.supplier_code,
                COUNT(po.id) as total_orders,
                SUM(po.total_amount) as total_order_value,
                AVG(po.delivery_time_days) as avg_delivery_time,
                COUNT(CASE WHEN po.status = 'delivered' THEN 1 END) as on_time_deliveries,
                ROUND((COUNT(CASE WHEN po.status = 'delivered' THEN 1 END) / NULLIF(COUNT(po.id), 0)) * 100, 2) as on_time_percentage,
                AVG(s.rating) as supplier_rating,
                MAX(po.order_date) as last_order_date
            FROM suppliers s
            LEFT JOIN purchase_orders po ON s.id = po.supplier_id
            WHERE s.company_id = ?
            GROUP BY s.id, s.supplier_name, s.supplier_code, s.rating
            ORDER BY total_order_value DESC
        ", [$this->user['company_id']]);
    }

    private function getInventoryAlerts() {
        return $this->db->query("
            SELECT
                ia.alert_type,
                ia.severity,
                ia.message,
                p.product_name,
                p.sku,
                p.stock_quantity,
                p.reorder_point,
                w.warehouse_name,
                ia.created_at,
                TIMESTAMPDIFF(MINUTE, ia.created_at, NOW()) as minutes_since_alert,
                ia.status
            FROM inventory_alerts ia
            LEFT JOIN products p ON ia.product_id = p.id
            LEFT JOIN warehouses w ON ia.warehouse_id = w.id
            WHERE ia.company_id = ? AND ia.status = 'active'
            ORDER BY ia.severity DESC, ia.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getStockMovements() {
        return $this->db->query("
            SELECT
                sm.movement_type,
                sm.quantity,
                sm.unit_cost,
                sm.total_value,
                p.product_name,
                p.sku,
                w.warehouse_name,
                sm.movement_date,
                sm.reference_number,
                sm.reason,
                u.first_name as processed_by_first,
                u.last_name as processed_by_last
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            LEFT JOIN warehouses w ON sm.warehouse_id = w.id
            LEFT JOIN users u ON sm.processed_by = u.id
            WHERE sm.company_id = ?
            ORDER BY sm.movement_date DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getInventoryAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(p.id) as total_products,
                SUM(p.stock_quantity) as total_units,
                SUM(p.stock_quantity * p.unit_cost) as total_value,
                AVG(p.stock_quantity) as avg_stock_level,
                COUNT(CASE WHEN p.stock_quantity <= p.reorder_point THEN 1 END) as low_stock_products,
                COUNT(CASE WHEN p.stock_quantity = 0 THEN 1 END) as out_of_stock_products,
                ROUND((COUNT(CASE WHEN p.stock_quantity <= p.reorder_point THEN 1 END) / NULLIF(COUNT(p.id), 0)) * 100, 2) as low_stock_percentage,
                AVG(p.unit_cost) as avg_product_cost,
                MAX(p.last_stock_update) as last_inventory_update
            FROM products p
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getUpcomingDeliveries() {
        return $this->db->query("
            SELECT
                po.order_number,
                s.supplier_name,
                po.expected_delivery_date,
                po.total_amount,
                COUNT(poi.id) as total_items,
                TIMESTAMPDIFF(DAY, CURDATE(), po.expected_delivery_date) as days_until_delivery,
                CASE
                    WHEN po.expected_delivery_date < CURDATE() THEN 'overdue'
                    WHEN po.expected_delivery_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'due_soon'
                    ELSE 'on_schedule'
                END as delivery_status
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            WHERE po.company_id = ? AND po.status IN ('ordered', 'partial_delivery')
            ORDER BY po.expected_delivery_date ASC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getProducts($filters = []) {
        $where = ["p.company_id = ?"];
        $params = [$this->user['company_id']];

        if (isset($filters['category'])) {
            $where[] = "p.category_id = ?";
            $params[] = $filters['category'];
        }

        if (isset($filters['supplier'])) {
            $where[] = "p.supplier_id = ?";
            $params[] = $filters['supplier'];
        }

        if (isset($filters['status'])) {
            $where[] = "p.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['stock_level'])) {
            switch ($filters['stock_level']) {
                case 'out_of_stock':
                    $where[] = "p.stock_quantity = 0";
                    break;
                case 'low_stock':
                    $where[] = "p.stock_quantity > 0 AND p.stock_quantity <= p.reorder_point";
                    break;
                case 'normal':
                    $where[] = "p.stock_quantity > p.reorder_point AND p.stock_quantity < p.maximum_stock";
                    break;
                case 'overstock':
                    $where[] = "p.stock_quantity >= p.maximum_stock";
                    break;
            }
        }

        if (isset($filters['date_from'])) {
            $where[] = "p.created_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (isset($filters['date_to'])) {
            $where[] = "p.created_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (isset($filters['search'])) {
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
                w.warehouse_name,
                p.stock_quantity,
                p.reorder_point,
                p.maximum_stock,
                (p.stock_quantity * p.unit_cost) as stock_value,
                p.profit_margin_percentage,
                p.last_stock_update,
                CASE
                    WHEN p.stock_quantity = 0 THEN 'out_of_stock'
                    WHEN p.stock_quantity <= p.reorder_point THEN 'low_stock'
                    WHEN p.stock_quantity >= p.maximum_stock THEN 'overstock'
                    ELSE 'normal'
                END as stock_status
            FROM products p
            LEFT JOIN product_categories pc ON p.category_id = pc.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            LEFT JOIN warehouses w ON p.warehouse_id = w.id
            WHERE $whereClause
            ORDER BY p.product_name ASC
        ", $params);
    }

    private function getProductCategories() {
        return $this->db->query("
            SELECT
                pc.*,
                COUNT(p.id) as product_count,
                SUM(p.stock_quantity) as total_stock,
                SUM(p.stock_quantity * p.unit_cost) as total_value,
                AVG(p.profit_margin_percentage) as avg_margin
            FROM product_categories pc
            LEFT JOIN products p ON pc.id = p.category_id
            WHERE pc.company_id = ?
            GROUP BY pc.id
            ORDER BY product_count DESC
        ", [$this->user['company_id']]);
    }

    private function getSuppliers() {
        return $this->db->query("
            SELECT
                s.*,
                COUNT(p.id) as products_supplied,
                SUM(p.stock_quantity * p.unit_cost) as total_inventory_value,
                AVG(s.rating) as supplier_rating,
                MAX(po.order_date) as last_order_date,
                COUNT(po.id) as total_orders
            FROM suppliers s
            LEFT JOIN products p ON s.id = p.supplier_id
            LEFT JOIN purchase_orders po ON s.id = po.supplier_id
            WHERE s.company_id = ?
            GROUP BY s.id
            ORDER BY total_inventory_value DESC
        ", [$this->user['company_id']]);
    }

    private function getProductStatus() {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'discontinued' => 'Discontinued',
            'draft' => 'Draft'
        ];
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
            'update_category' => 'Update Category',
            'update_supplier' => 'Update Supplier',
            'update_pricing' => 'Update Pricing',
            'update_stock' => 'Update Stock Levels',
            'generate_barcodes' => 'Generate Barcodes',
            'export_products' => 'Export Products',
            'import_products' => 'Import Products',
            'bulk_pricing' => 'Bulk Pricing Update',
            'mass_update' => 'Mass Update'
        ];
    }

    private function getProductAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(p.id) as total_products,
                COUNT(CASE WHEN p.status = 'active' THEN 1 END) as active_products,
                ROUND((COUNT(CASE WHEN p.status = 'active' THEN 1 END) / NULLIF(COUNT(p.id), 0)) * 100, 2) as active_percentage,
                SUM(p.stock_quantity) as total_stock,
                SUM(p.stock_quantity * p.unit_cost) as total_value,
                AVG(p.unit_cost) as avg_cost,
                AVG(p.unit_price) as avg_price,
                AVG(p.profit_margin_percentage) as avg_margin,
                COUNT(DISTINCT p.category_id) as categories_used,
                COUNT(DISTINCT p.supplier_id) as suppliers_used
            FROM products p
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCurrentStock() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                p.stock_quantity,
                p.reserved_quantity,
                (p.stock_quantity - p.reserved_quantity) as available_quantity,
                p.reorder_point,
                p.maximum_stock,
                w.warehouse_name,
                p.last_stock_update,
                CASE
                    WHEN p.stock_quantity = 0 THEN 'out_of_stock'
                    WHEN p.stock_quantity <= p.reorder_point THEN 'low_stock'
                    WHEN p.stock_quantity >= p.maximum_stock THEN 'overstock'
                    ELSE 'normal'
                END as stock_status
            FROM products p
            LEFT JOIN warehouses w ON p.warehouse_id = w.id
            WHERE p.company_id = ?
            ORDER BY p.stock_quantity ASC
        ", [$this->user['company_id']]);
    }

    private function getStockAlerts() {
        return $this->db->query("
            SELECT
                sa.alert_type,
                sa.severity,
                sa.message,
                p.product_name,
                p.sku,
                p.stock_quantity,
                p.reorder_point,
                w.warehouse_name,
                sa.created_at,
                TIMESTAMPDIFF(MINUTE, sa.created_at, NOW()) as minutes_since_alert,
                sa.status,
                sa.acknowledged_by
            FROM stock_alerts sa
            LEFT JOIN products p ON sa.product_id = p.id
            LEFT JOIN warehouses w ON sa.warehouse_id = w.id
            WHERE sa.company_id = ? AND sa.status = 'active'
            ORDER BY sa.severity DESC, sa.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getStockForecasting() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                sf.forecast_period,
                sf.forecast_quantity,
                sf.actual_quantity,
                sf.accuracy_percentage,
                sf.forecast_method,
                sf.confidence_level,
                sf.last_updated
            FROM stock_forecasting sf
            JOIN products p ON sf.product_id = p.id
            WHERE sf.company_id = ?
            ORDER BY sf.forecast_period ASC
        ", [$this->user['company_id']]);
    }

    private function getStockValuation() {
        return $this->db->querySingle("
            SELECT
                SUM(p.stock_quantity * p.unit_cost) as total_fifo_value,
                SUM(p.stock_quantity * p.average_cost) as total_average_value,
                SUM(p.stock_quantity * p.last_cost) as total_lifo_value,
                AVG(p.unit_cost) as avg_unit_cost,
                AVG(p.average_cost) as avg_average_cost,
                COUNT(CASE WHEN p.unit_cost > p.average_cost THEN 1 END) as products_above_avg,
                COUNT(CASE WHEN p.unit_cost < p.average_cost THEN 1 END) as products_below_avg,
                MAX(p.last_cost_update) as last_valuation_update
            FROM products p
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getStockAging() {
        return $this->db->query("
            SELECT
                CASE
                    WHEN TIMESTAMPDIFF(DAY, p.last_stock_movement, CURDATE()) <= 30 THEN '0-30_days'
                    WHEN TIMESTAMPDIFF(DAY, p.last_stock_movement, CURDATE()) <= 60 THEN '31-60_days'
                    WHEN TIMESTAMPDIFF(DAY, p.last_stock_movement, CURDATE()) <= 90 THEN '61-90_days'
                    WHEN TIMESTAMPDIFF(DAY, p.last_stock_movement, CURDATE()) <= 180 THEN '91-180_days'
                    ELSE 'over_180_days'
                END as aging_bucket,
                COUNT(p.id) as product_count,
                SUM(p.stock_quantity) as total_quantity,
                SUM(p.stock_quantity * p.unit_cost) as total_value,
                ROUND((SUM(p.stock_quantity * p.unit_cost) / NULLIF((SELECT SUM(stock_quantity * unit_cost) FROM products WHERE company_id = ?), 0)) * 100, 2) as percentage_of_total
            FROM products p
            WHERE p.company_id = ?
            GROUP BY
                CASE
                    WHEN TIMESTAMPDIFF(DAY, p.last_stock_movement, CURDATE()) <= 30 THEN '0-30_days'
                    WHEN TIMESTAMPDIFF(DAY, p.last_stock_movement, CURDATE()) <= 60 THEN '31-60_days'
                    WHEN TIMESTAMPDIFF(DAY, p.last_stock_movement, CURDATE()) <= 90 THEN '61-90_days'
                    WHEN TIMESTAMPDIFF(DAY, p.last_stock_movement, CURDATE()) <= 180 THEN '91-180_days'
                    ELSE 'over_180_days'
                END
            ORDER BY
                CASE aging_bucket
                    WHEN '0-30_days' THEN 1
                    WHEN '31-60_days' THEN 2
                    WHEN '61-90_days' THEN 3
                    WHEN '91-180_days' THEN 4
                    WHEN 'over_180_days' THEN 5
                END
        ", [$this->user['company_id'], $this->user['company_id']]);
    }

    private function getStockTurnover() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                SUM(CASE WHEN sm.movement_type = 'sale' THEN sm.quantity ELSE 0 END) as units_sold,
                AVG(p.stock_quantity) as avg_stock,
                CASE
                    WHEN AVG(p.stock_quantity) > 0 THEN ROUND(SUM(CASE WHEN sm.movement_type = 'sale' THEN sm.quantity ELSE 0 END) / AVG(p.stock_quantity), 2)
                    ELSE 0
                END as turnover_ratio,
                CASE
                    WHEN SUM(CASE WHEN sm.movement_type = 'sale' THEN sm.quantity ELSE 0 END) / NULLIF(AVG(p.stock_quantity), 0) >= 12 THEN 'very_fast'
                    WHEN SUM(CASE WHEN sm.movement_type = 'sale' THEN sm.quantity ELSE 0 END) / NULLIF(AVG(p.stock_quantity), 0) >= 6 THEN 'fast'
                    WHEN SUM(CASE WHEN sm.movement_type = 'sale' THEN sm.quantity ELSE 0 END) / NULLIF(AVG(p.stock_quantity), 0) >= 3 THEN 'moderate'
                    WHEN SUM(CASE WHEN sm.movement_type = 'sale' THEN sm.quantity ELSE 0 END) / NULLIF(AVG(p.stock_quantity), 0) >= 1 THEN 'slow'
                    ELSE 'very_slow'
                END as turnover_speed
            FROM products p
            LEFT JOIN stock_movements sm ON p.id = sm.product_id AND sm.movement_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
            WHERE p.company_id = ?
            GROUP BY p.id, p.product_name, p.sku
            ORDER BY turnover_ratio DESC
        ", [$this->user['company_id']]);
    }

    private function getStockAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(p.id) as total_products,
                SUM(p.stock_quantity) as total_units,
                COUNT(CASE WHEN p.stock_quantity = 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN p.stock_quantity <= p.reorder_point THEN 1 END) as low_stock,
                COUNT(CASE WHEN p.stock_quantity >= p.maximum_stock THEN 1 END) as overstock,
                ROUND((COUNT(CASE WHEN p.stock_quantity = 0 THEN 1 END) / NULLIF(COUNT(p.id), 0)) * 100, 2) as out_of_stock_percentage,
                ROUND((COUNT(CASE WHEN p.stock_quantity <= p.reorder_point THEN 1 END) / NULLIF(COUNT(p.id), 0)) * 100, 2) as low_stock_percentage,
                AVG(p.stock_quantity) as avg_stock_level,
                SUM(p.stock_quantity * p.unit_cost) as total_inventory_value
            FROM products p
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getStockSettings() {
        return $this->db->querySingle("
            SELECT * FROM stock_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getWarehouses() {
        return $this->db->query("
            SELECT
                w.*,
                COUNT(p.id) as total_products,
                SUM(p.stock_quantity) as total_stock,
                SUM(p.stock_quantity * p.unit_cost) as total_value,
                w.utilization_percentage,
                w.capacity,
                w.last_inventory_count,
                TIMESTAMPDIFF(DAY, w.last_inventory_count, CURDATE()) as days_since_count
            FROM warehouses w
            LEFT JOIN products p ON w.id = p.warehouse_id
            WHERE w.company_id = ?
            GROUP BY w.id
            ORDER BY w.utilization_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getWarehouseLayout() {
        return $this->db->query("
            SELECT
                wl.zone_name,
                wl.aisle_number,
                wl.shelf_number,
                wl.bin_number,
                wl.capacity,
                wl.current_occupancy,
                ROUND((wl.current_occupancy / NULLIF(wl.capacity, 0)) * 100, 2) as utilization_percentage,
                p.product_name,
                p.sku,
                wl.last_updated
            FROM warehouse_layout wl
            LEFT JOIN products p ON wl.product_id = p.id
            WHERE wl.company_id = ?
            ORDER BY wl.zone_name, wl.aisle_number, wl.shelf_number, wl.bin_number
        ", [$this->user['company_id']]);
    }

    private function getBinLocations() {
        return $this->db->query("
            SELECT
                bl.*,
                p.product_name,
                p.sku,
                bl.quantity,
                bl.reserved_quantity,
                (bl.quantity - bl.reserved_quantity) as available_quantity,
                bl.last_updated,
                u.first_name as last_updated_by_first,
                u.last_name as last_updated_by_last
            FROM bin_locations bl
            LEFT JOIN products p ON bl.product_id = p.id
            LEFT JOIN users u ON bl.last_updated_by = u.id
            WHERE bl.company_id = ?
            ORDER BY bl.bin_location ASC
        ", [$this->user['company_id']]);
    }

    private function getWarehouseTransfers() {
        return $this->db->query("
            SELECT
                wt.*,
                p.product_name,
                p.sku,
                w1.warehouse_name as from_warehouse,
                w2.warehouse_name as to_warehouse,
                wt.quantity,
                wt.transfer_date,
                wt.status,
                wt.approved_by,
                u.first_name as initiated_by_first,
                u.last_name as initiated_by_last
            FROM warehouse_transfers wt
            JOIN products p ON wt.product_id = p.id
            JOIN warehouses w1 ON wt.from_warehouse_id = w1.id
            JOIN warehouses w2 ON wt.to_warehouse_id = w2.id
            LEFT JOIN users u ON wt.initiated_by = u.id
            WHERE wt.company_id = ?
            ORDER BY wt.transfer_date DESC
        ", [$this->user['company_id']]);
    }

    private function getWarehouseInventory() {
        return $this->db->query("
            SELECT
                w.warehouse_name,
                p.product_name,
                p.sku,
                wi.quantity,
                wi.reserved_quantity,
                (wi.quantity - wi.reserved_quantity) as available_quantity,
                wi.last_count_date,
                wi.count_variance,
                wi.accuracy_percentage,
                u.first_name as counted_by_first,
                u.last_name as counted_by_last
            FROM warehouse_inventory wi
            JOIN warehouses w ON wi.warehouse_id = w.id
            JOIN products p ON wi.product_id = p.id
            LEFT JOIN users u ON wi.counted_by = u.id
            WHERE wi.company_id = ?
            ORDER BY w.warehouse_name, p.product_name
        ", [$this->user['company_id']]);
    }

    private function getWarehouseUtilization() {
        return $this->db->query("
            SELECT
                w.warehouse_name,
                w.capacity,
                SUM(p.stock_quantity) as current_stock,
                ROUND((SUM(p.stock_quantity) / NULLIF(w.capacity, 0)) * 100, 2) as utilization_percentage,
                COUNT(p.id) as total_products,
                COUNT(DISTINCT pc.id) as categories_used,
                AVG(p.unit_cost) as avg_product_cost,
                SUM(p.stock_quantity * p.unit_cost) as total_value
            FROM warehouses w
            LEFT JOIN products p ON w.id = p.warehouse_id
            LEFT JOIN product_categories pc ON p.category_id = pc.id
            WHERE w.company_id = ?
            GROUP BY w.id, w.warehouse_name, w.capacity
            ORDER BY utilization_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getWarehouseAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(w.id) as total_warehouses,
                SUM(w.capacity) as total_capacity,
                SUM(p.stock_quantity) as total_stock,
                ROUND((SUM(p.stock_quantity) / NULLIF(SUM(w.capacity), 0)) * 100, 2) as overall_utilization,
                COUNT(CASE WHEN ROUND((SUM(p.stock_quantity) / NULLIF(w.capacity, 0)) * 100, 2) >= 90 THEN 1 END) as high_utilization_warehouses,
                COUNT(CASE WHEN ROUND((SUM(p.stock_quantity) / NULLIF(w.capacity, 0)) * 100, 2) <= 50 THEN 1 END) as low_utilization_warehouses,
                AVG(w.utilization_percentage) as avg_utilization
            FROM warehouses w
            LEFT JOIN products p ON w.id = p.warehouse_id
            WHERE w.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getWarehouseSettings() {
        return $this->db->querySingle("
            SELECT * FROM warehouse_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSupplierContracts() {
        return $this->db->query("
            SELECT
                sc.*,
                s.supplier_name,
                sc.contract_start_date,
                sc.contract_end_date,
                sc.contract_value,
                sc.payment_terms,
                sc.delivery_terms,
                sc.quality_requirements,
                TIMESTAMPDIFF(DAY, CURDATE(), sc.contract_end_date) as days_until_expiry,
                CASE
                    WHEN sc.contract_end_date < CURDATE() THEN 'expired'
                    WHEN sc.contract_end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expires_soon'
                    ELSE 'active'
                END as contract_status
            FROM supplier_contracts sc
            JOIN suppliers s ON sc.supplier_id = s.id
            WHERE sc.company_id = ?
            ORDER BY sc.contract_end_date ASC
        ", [$this->user['company_id']]);
    }

    private function getSupplierRatings() {
        return $this->db->query("
            SELECT
                s.supplier_name,
                s.supplier_code,
                sr.rating_category,
                sr.rating_score,
                sr.rating_date,
                sr.rated_by,
                sr.comments,
                u.first_name as rated_by_first,
                u.last_name as rated_by_last
            FROM supplier_ratings sr
            JOIN suppliers s ON sr.supplier_id = s.id
            LEFT JOIN users u ON sr.rated_by = u.id
            WHERE sr.company_id = ?
            ORDER BY sr.rating_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierCommunications() {
        return $this->db->query("
            SELECT
                sc.*,
                s.supplier_name,
                sc.communication_type,
                sc.subject,
                sc.message,
                sc.sent_date,
                sc.response_date,
                sc.follow_up_date,
                TIMESTAMPDIFF(DAY, CURDATE(), sc.follow_up_date) as days_until_followup
            FROM supplier_communications sc
            JOIN suppliers s ON sc.supplier_id = s.id
            WHERE sc.company_id = ?
            ORDER BY sc.sent_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(s.id) as total_suppliers,
                COUNT(CASE WHEN s.status = 'active' THEN 1 END) as active_suppliers,
                ROUND((COUNT(CASE WHEN s.status = 'active' THEN 1 END) / NULLIF(COUNT(s.id), 0)) * 100, 2) as active_percentage,
                AVG(s.rating) as avg_supplier_rating,
                COUNT(CASE WHEN s.rating >= 4.5 THEN 1 END) as top_rated_suppliers,
                COUNT(CASE WHEN s.rating < 3.0 THEN 1 END) as low_rated_suppliers,
                SUM(sc.contract_value) as total_contract_value,
                COUNT(sc.id) as active_contracts
            FROM suppliers s
            LEFT JOIN supplier_contracts sc ON s.id = sc.supplier_id AND sc.contract_end_date >= CURDATE()
            WHERE s.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSupplierTemplates() {
        return $this->db->query("
            SELECT * FROM supplier_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getSupplierSettings() {
        return $this->db->querySingle("
            SELECT * FROM supplier_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPurchaseOrders() {
        return $this->db->query("
            SELECT
                po.*,
                s.supplier_name,
                po.order_number,
                po.order_date,
                po.expected_delivery_date,
                po.total_amount,
                po.status,
                COUNT(poi.id) as total_items,
                SUM(poi.quantity_ordered) as total_quantity,
                po.approved_by,
                u.first_name as created_by_first,
                u.last_name as created_by_last
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            LEFT JOIN users u ON po.created_by = u.id
            WHERE po.company_id = ?
            GROUP BY po.id
            ORDER BY po.order_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPOApprovals() {
        return $this->db->query("
            SELECT
                po.order_number,
                s.supplier_name,
                po.total_amount,
                pa.approval_level,
                pa.approval_status,
                pa.approved_by,
                pa.approval_date,
                pa.comments,
                u.first_name as approver_first,
                u.last_name as approver_last
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN po_approvals pa ON po.id = pa.purchase_order_id
            LEFT JOIN users u ON pa.approved_by = u.id
            WHERE po.company_id = ?
            ORDER BY po.order_date DESC, pa.approval_level ASC
        ", [$this->user['company_id']]);
    }

    private function getPOReceiving() {
        return $this->db->query("
            SELECT
                po.order_number,
                s.supplier_name,
                pr.receipt_number,
                pr.receipt_date,
                pr.received_by,
                COUNT(poi.id) as items_received,
                SUM(poi.quantity_received) as total_quantity_received,
                pr.quality_check_status,
                pr.inspection_notes,
                u.first_name as received_by_first,
                u.last_name as received_by_last
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN po_receiving pr ON po.id = pr.purchase_order_id
            LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            LEFT JOIN users u ON pr.received_by = u.id
            WHERE po.company_id = ?
            GROUP BY po.id, pr.id
            ORDER BY pr.receipt_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPOInvoicing() {
        return $this->db->query("
            SELECT
                po.order_number,
                s.supplier_name,
                pi.invoice_number,
                pi.invoice_date,
                pi.invoice_amount,
                pi.payment_terms,
                pi.due_date,
                pi.payment_status,
                TIMESTAMPDIFF(DAY, CURDATE(), pi.due_date) as days_until_due,
                pi.approved_by,
                u.first_name as approved_by_first,
                u.last_name as approved_by_last
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN po_invoicing pi ON po.id = pi.purchase_order_id
            LEFT JOIN users u ON pi.approved_by = u.id
            WHERE po.company_id = ?
            ORDER BY pi.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getPOReturns() {
        return $this->db->query("
            SELECT
                po.order_number,
                s.supplier_name,
                pr.return_number,
                pr.return_date,
                pr.return_reason,
                COUNT(poi.id) as items_returned,
                SUM(poi.quantity_returned) as total_quantity_returned,
                SUM(poi.quantity_returned * poi.unit_cost) as total_return_value,
                pr.return_status,
                pr.processed_by,
                u.first_name as processed_by_first,
                u.last_name as processed_by_last
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN po_returns pr ON po.id = pr.purchase_order_id
            LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            LEFT JOIN users u ON pr.processed_by = u.id
            WHERE po.company_id = ?
            GROUP BY po.id, pr.id
            ORDER BY pr.return_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPOAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(po.id) as total_orders,
                SUM(po.total_amount) as total_order_value,
                COUNT(CASE WHEN po.status = 'delivered' THEN 1 END) as delivered_orders,
                ROUND((COUNT(CASE WHEN po.status = 'delivered' THEN 1 END) / NULLIF(COUNT(po.id), 0)) * 100, 2) as delivery_rate,
                AVG(po.delivery_time_days) as avg_delivery_time,
                COUNT(DISTINCT po.supplier_id) as active_suppliers,
                AVG(po.total_amount) as avg_order_value,
                MAX(po.order_date) as last_order_date
            FROM purchase_orders po
            WHERE po.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPOTemplates() {
        return $this->db->query("
            SELECT * FROM po_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getPOSettings() {
        return $this->db->querySingle("
            SELECT * FROM po_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getDemandForecasting() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                df.forecast_period,
                df.historical_demand,
                df.forecasted_demand,
                df.accuracy_percentage,
                df.forecast_method,
                df.confidence_level,
                df.last_updated
            FROM demand_forecasting df
            JOIN products p ON df.product_id = p.id
            WHERE df.company_id = ?
            ORDER BY df.forecast_period ASC
        ", [$this->user['company_id']]);
    }

    private function getReorderPointCalculation() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                rpc.lead_time_days,
                rpc.daily_demand,
                rpc.safety_stock,
                rpc.reorder_point,
                rpc.economic_order_quantity,
                rpc.calculated_date,
                CASE
                    WHEN p.stock_quantity <= rpc.reorder_point THEN 'reorder_needed'
                    WHEN p.stock_quantity <= (rpc.reorder_point + rpc.safety_stock) THEN 'low_stock'
                    ELSE 'normal'
                END as stock_status
            FROM reorder_point_calculation rpc
            JOIN products p ON rpc.product_id = p.id
            WHERE rpc.company_id = ?
            ORDER BY rpc.reorder_point - p.stock_quantity DESC
        ", [$this->user['company_id']]);
    }

    private function getABCAnalysis() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                abc.annual_demand,
                abc.annual_value,
                abc.abc_category,
                abc.cumulative_percentage,
                abc.priority_level,
                abc.reorder_frequency,
                abc.safety_stock_percentage
            FROM abc_analysis abc
            JOIN products p ON abc.product_id = p.id
            WHERE abc.company_id = ?
            ORDER BY abc.cumulative_percentage ASC
        ", [$this->user['company_id']]);
    }

    private function getSafetyStockCalculation() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                ssc.lead_time_demand,
                ssc.demand_variability,
                ssc.service_level_target,
                ssc.safety_stock_units,
                ssc.safety_stock_value,
                ssc.reorder_point_with_safety,
                ssc.calculated_date
            FROM safety_stock_calculation ssc
            JOIN products p ON ssc.product_id = p.id
            WHERE ssc.company_id = ?
            ORDER BY ssc.safety_stock_value DESC
        ", [$this->user['company_id']]);
    }

    private function getInventoryPolicies() {
        return $this->db->query("
            SELECT * FROM inventory_policies
            WHERE company_id = ?
            ORDER BY policy_name ASC
        ", [$this->user['company_id']]);
    }

    private function getOptimizationRecommendations() {
        return $this->db->query("
            SELECT
                orr.product_name,
                orr.sku,
                orr.recommendation_type,
                orr.current_value,
                orr.recommended_value,
                orr.potential_savings,
                orr.priority_level,
                orr.implementation_complexity,
                orr.expected_roi,
                orr.generated_date
            FROM optimization_recommendations orr
            WHERE orr.company_id = ?
            ORDER BY orr.priority_level DESC, orr.potential_savings DESC
        ", [$this->user['company_id']]);
    }

    private function getOptimizationAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(orr.id) as total_recommendations,
                SUM(orr.potential_savings) as total_potential_savings,
                COUNT(CASE WHEN orr.priority_level = 'high' THEN 1 END) as high_priority_recommendations,
                COUNT(CASE WHEN orr.recommendation_type = 'reorder_point' THEN 1 END) as reorder_optimizations,
                COUNT(CASE WHEN orr.recommendation_type = 'safety_stock' THEN 1 END) as safety_stock_optimizations,
                AVG(orr.expected_roi) as avg_expected_roi,
                MAX(orr.generated_date) as last_optimization_date
            FROM optimization_recommendations orr
            WHERE orr.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getOptimizationSettings() {
        return $this->db->querySingle("
            SELECT * FROM optimization_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getStockReports() {
        return $this->db->query("
            SELECT
                sr.report_name,
                sr.report_type,
                sr.generated_date,
                sr.total_products,
                sr.total_value,
                sr.low_stock_items,
                sr.out_of_stock_items,
                sr.report_period,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM stock_reports sr
            LEFT JOIN users u ON sr.generated_by = u.id
            WHERE sr.company_id = ?
            ORDER BY sr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getMovementReports() {
        return $this->db->query("
            SELECT
                mr.report_name,
                mr.report_type,
                mr.generated_date,
                mr.total_movements,
                mr.total_value,
                mr.incoming_movements,
                mr.outgoing_movements,
                mr.adjustments,
                mr.report_period,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM movement_reports mr
            LEFT JOIN users u ON mr.generated_by = u.id
            WHERE mr.company_id = ?
            ORDER BY mr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getValuationReports() {
        return $this->db->query("
            SELECT
                vr.report_name,
                vr.valuation_method,
                vr.generated_date,
                vr.total_value,
                vr.total_products,
                vr.avg_unit_cost,
                vr.inventory_turnover,
                vr.report_period,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM valuation_reports vr
            LEFT JOIN users u ON vr.generated_by = u.id
            WHERE vr.company_id = ?
            ORDER BY vr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierReports() {
        return $this->db->query("
            SELECT
                sr.report_name,
                sr.report_type,
                sr.generated_date,
                sr.total_suppliers,
                sr.active_suppliers,
                sr.total_orders,
                sr.on_time_deliveries,
                sr.avg_rating,
                sr.report_period,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM supplier_reports sr
            LEFT JOIN users u ON sr.generated_by = u.id
            WHERE sr.company_id = ?
            ORDER BY sr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getWarehouseReports() {
        return $this->db->query("
            SELECT
                wr.report_name,
                wr.report_type,
                wr.generated_date,
                wr.total_warehouses,
                wr.total_capacity,
                wr.total_stock,
                wr.avg_utilization,
                wr.report_period,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM warehouse_reports wr
            LEFT JOIN users u ON wr.generated_by = u.id
            WHERE wr.company_id = ?
            ORDER BY wr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceReports() {
        return $this->db->query("
            SELECT
                pr.report_name,
                pr.report_type,
                pr.generated_date,
                pr.inventory_turnover,
                pr.stock_accuracy,
                pr.order_fill_rate,
                pr.carrying_cost_percentage,
                pr.report_period,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM performance_reports pr
            LEFT JOIN users u ON pr.generated_by = u.id
            WHERE pr.company_id = ?
            ORDER BY pr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCustomReports() {
        return $this->db->query("
            SELECT
                cr.report_name,
                cr.report_type,
                cr.created_date,
                cr.last_run_date,
                cr.run_count,
                cr.is_scheduled,
                cr.schedule_frequency,
                u.first_name as created_by_first,
                u.last_name as created_by_last
            FROM custom_reports cr
            LEFT JOIN users u ON cr.created_by = u.id
            WHERE cr.company_id = ?
            ORDER BY cr.last_run_date DESC
        ", [$this->user['company_id']]);
    }

    private function getReportSchedules() {
        return $this->db->query("
            SELECT
                rs.schedule_name,
                rs.report_type,
                rs.frequency,
                rs.next_run_date,
                rs.last_run_date,
                rs.recipients,
                rs.is_active,
                TIMESTAMPDIFF(DAY, CURDATE(), rs.next_run_date) as days_until_next
            FROM report_schedules rs
            WHERE rs.company_id = ?
            ORDER BY rs.next_run_date ASC
        ", [$this->user['company_id']]);
    }

    private function getBarcodeGeneration() {
        return $this->db->query("
            SELECT
                bg.barcode_number,
                bg.barcode_type,
                p.product_name,
                p.sku,
                bg.generated_date,
                bg.print_count,
                bg.last_printed,
                u.first_name as generated_by_first,
                u.last_name as generated_by_last
            FROM barcode_generation bg
            LEFT JOIN products p ON bg.product_id = p.id
            LEFT JOIN users u ON bg.generated_by = u.id
            WHERE bg.company_id = ?
            ORDER BY bg.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getRFIDTracking() {
        return $this->db->query("
            SELECT
                rt.tag_id,
                rt.tag_type,
                p.product_name,
                p.sku,
                rt.location,
                rt.last_read_date,
                rt.battery_level,
                rt.status,
                rt.last_updated
            FROM rfid_tracking rt
            LEFT JOIN products p ON rt.product_id = p.id
            WHERE rt.company_id = ?
            ORDER BY rt.last_read_date DESC
        ", [$this->user['company_id']]);
    }

    private function getBarcodeScanning() {
        return $this->db->query("
            SELECT
                bs.scan_id,
                bs.barcode_number,
                p.product_name,
                p.sku,
                bs.scan_location,
                bs.scan_date,
                bs.scan_type,
                bs.quantity_scanned,
                u.first_name as scanned_by_first,
                u.last_name as scanned_by_last
            FROM barcode_scanning bs
            LEFT JOIN products p ON bs.product_id = p.id
            LEFT JOIN users u ON bs.scanned_by = u.id
            WHERE bs.company_id = ?
            ORDER BY bs.scan_date DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getLabelPrinting() {
        return $this->db->query("
            SELECT
                lp.print_job_id,
                lp.label_type,
                lp.quantity_printed,
                lp.print_date,
                lp.printer_location,
                lp.print_status,
                u.first_name as printed_by_first,
                u.last_name as printed_by_last
            FROM label_printing lp
            LEFT JOIN users u ON lp.printed_by = u.id
            WHERE lp.company_id = ?
            ORDER BY lp.print_date DESC
        ", [$this->user['company_id']]);
    }

    private function getBarcodeAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(bg.id) as total_barcodes_generated,
                COUNT(bs.id) as total_scans,
                COUNT(DISTINCT bg.product_id) as products_with_barcodes,
                AVG(bs.quantity_scanned) as avg_scan_quantity,
                COUNT(CASE WHEN bs.scan_type = 'receiving' THEN 1 END) as receiving_scans,
                COUNT(CASE WHEN bs.scan_type = 'shipping' THEN 1 END) as shipping_scans,
                COUNT(CASE WHEN bs.scan_type = 'inventory' THEN 1 END) as inventory_scans,
                MAX(bs.scan_date) as last_scan_date
            FROM barcode_generation bg
            LEFT JOIN barcode_scanning bs ON bg.barcode_number = bs.barcode_number
            WHERE bg.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getBarcodeTemplates() {
        return $this->db->query("
            SELECT * FROM barcode_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBarcodeSettings() {
        return $this->db->querySingle("
            SELECT * FROM barcode_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getStockTrends() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(sm.movement_date, '%Y-%m') as month,
                SUM(CASE WHEN sm.movement_type = 'sale' THEN sm.quantity ELSE 0 END) as units_sold,
                SUM(CASE WHEN sm.movement_type = 'purchase' THEN sm.quantity ELSE 0 END) as units_purchased,
                SUM(CASE WHEN sm.movement_type = 'adjustment' THEN sm.quantity ELSE 0 END) as adjustments,
                AVG(p.stock_quantity) as avg_stock_level,
                COUNT(DISTINCT sm.product_id) as active_products
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            WHERE sm.company_id = ? AND sm.movement_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(sm.movement_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getWarehouseEfficiency() {
        return $this->db->query("
            SELECT
                w.warehouse_name,
                we.picking_accuracy,
                we.putaway_efficiency,
                we.inventory_accuracy,
                we.order_processing_time,
                we.utilization_rate,
                we.error_rate,
                we.measured_date
            FROM warehouse_efficiency we
            JOIN warehouses w ON we.warehouse_id = w.id
            WHERE we.company_id = ?
            ORDER BY we.measured_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCostAnalysis() {
        return $this->db->querySingle("
            SELECT
                SUM(p.stock_quantity * p.unit_cost) as inventory_holding_cost,
                SUM(p.stock_quantity * p.carrying_cost_rate) as carrying_cost,
                SUM(p.ordering_cost) as ordering_cost,
                SUM(p.stockout_cost) as stockout_cost,
                (SUM(p.stock_quantity * p.unit_cost) + SUM(p.stock_quantity * p.carrying_cost_rate) + SUM(p.ordering_cost) + SUM(p.stockout_cost)) as total_inventory_cost,
                ROUND(((SUM(p.stock_quantity * p.unit_cost) + SUM(p.stock_quantity * p.carrying_cost_rate) + SUM(p.ordering_cost) + SUM(p.stockout_cost)) / NULLIF(SUM(p.stock_quantity * p.unit_cost), 0)) * 100, 2) as inventory_cost_percentage
            FROM products p
            WHERE p.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getDemandPatterns() {
        return $this->db->query("
            SELECT
                p.product_name,
                p.sku,
                dp.pattern_type,
                dp.seasonality_factor,
                dp.trend_direction,
                dp.volatility_index,
                dp.forecast_confidence,
                dp.last_analyzed
            FROM demand_patterns dp
            JOIN products p ON dp.product_id = p.id
            WHERE dp.company_id = ?
            ORDER BY dp.volatility_index DESC
        ", [$this->user['company_id']]);
    }

    private function getForecastAccuracy() {
        return $this->db->querySingle("
            SELECT
                COUNT(fa.id) as total_forecasts,
                AVG(fa.accuracy_percentage) as avg_accuracy,
                COUNT(CASE WHEN fa.accuracy_percentage >= 90 THEN 1 END) as high_accuracy_forecasts,
                COUNT(CASE WHEN fa.accuracy_percentage < 70 THEN 1 END) as low_accuracy_forecasts,
                MIN(fa.accuracy_percentage) as worst_accuracy,
                MAX(fa.accuracy_percentage) as best_accuracy,
                MAX(fa.measured_date) as last_accuracy_measurement
            FROM forecast_accuracy fa
            WHERE fa.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCustomDashboards() {
        return $this->db->query("
            SELECT
                cd.dashboard_name,
                cd.dashboard_type,
                cd.created_date,
                cd.last_modified,
                cd.created_by,
                cd.access_level,
                COUNT(cdw.id) as widget_count
            FROM custom_dashboards cd
            LEFT JOIN custom_dashboard_widgets cdw ON cd.id = cdw.dashboard_id
            WHERE cd.company_id = ?
            GROUP BY cd.id
            ORDER BY cd.last_modified DESC
        ", [$this->user['company_id']]);
    }
}
