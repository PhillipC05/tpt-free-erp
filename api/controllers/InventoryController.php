<?php

namespace TPT\ERP\Api\Controllers;

use TPT\ERP\Core\Response;
use TPT\ERP\Core\Request;
use TPT\ERP\Core\Database;
use TPT\ERP\Modules\Inventory;

/**
 * Inventory API Controller
 * Handles all inventory-related API endpoints
 */
class InventoryController extends BaseController
{
    private $inventory;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->inventory = new Inventory();
        $this->db = Database::getInstance();
    }

    /**
     * Get inventory overview
     * GET /api/inventory/overview
     */
    public function getOverview()
    {
        try {
            $this->requirePermission('inventory.view');

            $data = [
                'inventory_overview' => $this->inventory->getInventoryOverview(),
                'stock_levels' => $this->inventory->getStockLevels(),
                'warehouse_status' => $this->inventory->getWarehouseStatus(),
                'inventory_alerts' => $this->inventory->getInventoryAlerts(),
                'upcoming_deliveries' => $this->inventory->getUpcomingDeliveries()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get products with filtering
     * GET /api/inventory/products
     */
    public function getProducts()
    {
        try {
            $this->requirePermission('inventory.products.view');

            $filters = [
                'category' => $_GET['category'] ?? null,
                'supplier' => $_GET['supplier'] ?? null,
                'status' => $_GET['status'] ?? null,
                'stock_level' => $_GET['stock_level'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $products = $this->inventory->getProducts($filters);
            $total = $this->getProductsCount($filters);

            Response::json([
                'products' => $products,
                'pagination' => [
                    'page' => $filters['page'],
                    'limit' => $filters['limit'],
                    'total' => $total,
                    'pages' => ceil($total / $filters['limit'])
                ]
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create new product
     * POST /api/inventory/products
     */
    public function createProduct()
    {
        try {
            $this->requirePermission('inventory.products.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['product_name', 'sku', 'category_id', 'unit_cost', 'unit_price'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if SKU already exists
            if ($this->skuExists($data['sku'])) {
                Response::error('SKU already exists', 400);
                return;
            }

            $productData = [
                'product_name' => trim($data['product_name']),
                'sku' => trim($data['sku']),
                'description' => $data['description'] ?? '',
                'category_id' => $data['category_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'unit_cost' => (float)$data['unit_cost'],
                'unit_price' => (float)$data['unit_price'],
                'stock_quantity' => (int)($data['stock_quantity'] ?? 0),
                'reorder_point' => (int)($data['reorder_point'] ?? 0),
                'maximum_stock' => (int)($data['maximum_stock'] ?? 0),
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'status' => $data['status'] ?? 'active',
                'profit_margin_percentage' => $this->calculateProfitMargin($data['unit_cost'], $data['unit_price']),
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_date' => date('Y-m-d H:i:s'),
                'last_stock_update' => date('Y-m-d H:i:s')
            ];

            $productId = $this->db->insert('products', $productData);

            // Log the creation
            $this->logActivity('product_created', 'products', $productId, "Product '{$productData['product_name']}' created");

            Response::json([
                'success' => true,
                'product_id' => $productId,
                'message' => 'Product created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Update product
     * PUT /api/inventory/products/{id}
     */
    public function updateProduct($id)
    {
        try {
            $this->requirePermission('inventory.products.update');

            $data = Request::getJsonBody();

            // Check if product exists and belongs to company
            $product = $this->getProductById($id);
            if (!$product) {
                Response::error('Product not found', 404);
                return;
            }

            // Check SKU uniqueness if changed
            if (isset($data['sku']) && $data['sku'] !== $product['sku'] && $this->skuExists($data['sku'])) {
                Response::error('SKU already exists', 400);
                return;
            }

            $updateData = [];
            $allowedFields = [
                'product_name', 'sku', 'description', 'category_id', 'supplier_id',
                'unit_cost', 'unit_price', 'stock_quantity', 'reorder_point',
                'maximum_stock', 'warehouse_id', 'status'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['profit_margin_percentage'] = $this->calculateProfitMargin(
                    $updateData['unit_cost'] ?? $product['unit_cost'],
                    $updateData['unit_price'] ?? $product['unit_price']
                );
                $updateData['updated_by'] = $this->user['id'];
                $updateData['updated_date'] = date('Y-m-d H:i:s');

                $this->db->update('products', $updateData, ['id' => $id]);

                // Log the update
                $this->logActivity('product_updated', 'products', $id, "Product '{$product['product_name']}' updated");
            }

            Response::json([
                'success' => true,
                'message' => 'Product updated successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Delete product
     * DELETE /api/inventory/products/{id}
     */
    public function deleteProduct($id)
    {
        try {
            $this->requirePermission('inventory.products.delete');

            $product = $this->getProductById($id);
            if (!$product) {
                Response::error('Product not found', 404);
                return;
            }

            // Check if product has stock movements
            if ($this->hasStockMovements($id)) {
                Response::error('Cannot delete product with existing stock movements', 400);
                return;
            }

            $this->db->delete('products', ['id' => $id]);

            // Log the deletion
            $this->logActivity('product_deleted', 'products', $id, "Product '{$product['product_name']}' deleted");

            Response::json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get stock movements
     * GET /api/inventory/stock-movements
     */
    public function getStockMovements()
    {
        try {
            $this->requirePermission('inventory.stock.view');

            $filters = [
                'product_id' => $_GET['product_id'] ?? null,
                'movement_type' => $_GET['movement_type'] ?? null,
                'warehouse_id' => $_GET['warehouse_id'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $movements = $this->inventory->getStockMovements();
            $total = $this->getStockMovementsCount($filters);

            Response::json([
                'movements' => $movements,
                'pagination' => [
                    'page' => $filters['page'],
                    'limit' => $filters['limit'],
                    'total' => $total,
                    'pages' => ceil($total / $filters['limit'])
                ]
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create stock movement
     * POST /api/inventory/stock-movements
     */
    public function createStockMovement()
    {
        try {
            $this->requirePermission('inventory.stock.update');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['product_id', 'movement_type', 'quantity', 'reason'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Validate product exists
            $product = $this->getProductById($data['product_id']);
            if (!$product) {
                Response::error('Product not found', 400);
                return;
            }

            // Calculate new stock quantity
            $quantity = (int)$data['quantity'];
            $newStockQuantity = $this->calculateNewStockQuantity($product['stock_quantity'], $data['movement_type'], $quantity);

            if ($newStockQuantity < 0) {
                Response::error('Insufficient stock for this movement', 400);
                return;
            }

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Create stock movement record
                $movementData = [
                    'product_id' => $data['product_id'],
                    'movement_type' => $data['movement_type'],
                    'quantity' => $quantity,
                    'unit_cost' => (float)($data['unit_cost'] ?? $product['unit_cost']),
                    'total_value' => $quantity * (float)($data['unit_cost'] ?? $product['unit_cost']),
                    'warehouse_id' => $data['warehouse_id'] ?? $product['warehouse_id'],
                    'movement_date' => date('Y-m-d H:i:s'),
                    'reference_number' => $data['reference_number'] ?? $this->generateReferenceNumber(),
                    'reason' => $data['reason'],
                    'notes' => $data['notes'] ?? '',
                    'processed_by' => $this->user['id'],
                    'company_id' => $this->user['company_id']
                ];

                $movementId = $this->db->insert('stock_movements', $movementData);

                // Update product stock quantity
                $this->db->update('products', [
                    'stock_quantity' => $newStockQuantity,
                    'last_stock_update' => date('Y-m-d H:i:s'),
                    'updated_by' => $this->user['id'],
                    'updated_date' => date('Y-m-d H:i:s')
                ], ['id' => $data['product_id']]);

                // Check for stock alerts
                $this->checkStockAlerts($data['product_id'], $newStockQuantity);

                $this->db->commit();

                // Log the movement
                $this->logActivity('stock_movement', 'stock_movements', $movementId, "Stock movement: {$data['movement_type']} {$quantity} units of {$product['product_name']}");

                Response::json([
                    'success' => true,
                    'movement_id' => $movementId,
                    'new_stock_quantity' => $newStockQuantity,
                    'message' => 'Stock movement recorded successfully'
                ], 201);
            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get warehouses
     * GET /api/inventory/warehouses
     */
    public function getWarehouses()
    {
        try {
            $this->requirePermission('inventory.warehouses.view');

            $warehouses = $this->inventory->getWarehouses();

            Response::json(['warehouses' => $warehouses]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get suppliers
     * GET /api/inventory/suppliers
     */
    public function getSuppliers()
    {
        try {
            $this->requirePermission('inventory.suppliers.view');

            $suppliers = $this->inventory->getSuppliers();

            Response::json(['suppliers' => $suppliers]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get product categories
     * GET /api/inventory/categories
     */
    public function getCategories()
    {
        try {
            $this->requirePermission('inventory.products.view');

            $categories = $this->inventory->getProductCategories();

            Response::json(['categories' => $categories]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get inventory analytics
     * GET /api/inventory/analytics
     */
    public function getAnalytics()
    {
        try {
            $this->requirePermission('inventory.analytics.view');

            $data = [
                'inventory_analytics' => $this->inventory->getInventoryAnalytics(),
                'stock_trends' => $this->inventory->getStockTrends(),
                'product_performance' => $this->inventory->getProductPerformance(),
                'supplier_performance' => $this->inventory->getSupplierPerformance(),
                'cost_analysis' => $this->inventory->getCostAnalysis()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Bulk update products
     * POST /api/inventory/products/bulk-update
     */
    public function bulkUpdateProducts()
    {
        try {
            $this->requirePermission('inventory.products.update');

            $data = Request::getJsonBody();

            if (!isset($data['product_ids']) || !is_array($data['product_ids'])) {
                Response::error('Product IDs array is required', 400);
                return;
            }

            if (empty($data['updates'])) {
                Response::error('Updates object is required', 400);
                return;
            }

            $productIds = $data['product_ids'];
            $updates = $data['updates'];

            // Start transaction
            $this->db->beginTransaction();

            try {
                $updateCount = 0;

                foreach ($productIds as $productId) {
                    $product = $this->getProductById($productId);
                    if (!$product) continue;

                    $updateData = [];
                    $allowedFields = [
                        'category_id', 'supplier_id', 'unit_cost', 'unit_price',
                        'reorder_point', 'maximum_stock', 'warehouse_id', 'status'
                    ];

                    foreach ($allowedFields as $field) {
                        if (isset($updates[$field])) {
                            $updateData[$field] = $updates[$field];
                        }
                    }

                    if (!empty($updateData)) {
                        $updateData['profit_margin_percentage'] = $this->calculateProfitMargin(
                            $updateData['unit_cost'] ?? $product['unit_cost'],
                            $updateData['unit_price'] ?? $product['unit_price']
                        );
                        $updateData['updated_by'] = $this->user['id'];
                        $updateData['updated_date'] = date('Y-m-d H:i:s');

                        $this->db->update('products', $updateData, ['id' => $productId]);
                        $updateCount++;
                    }
                }

                $this->db->commit();

                // Log bulk update
                $this->logActivity('bulk_product_update', 'products', null, "Bulk updated {$updateCount} products");

                Response::json([
                    'success' => true,
                    'updated_count' => $updateCount,
                    'message' => "{$updateCount} products updated successfully"
                ]);
            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getProductById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM products WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function skuExists($sku)
    {
        $count = $this->db->queryValue(
            "SELECT COUNT(*) FROM products WHERE sku = ? AND company_id = ?",
            [$sku, $this->user['company_id']]
        );
        return $count > 0;
    }

    private function hasStockMovements($productId)
    {
        $count = $this->db->queryValue(
            "SELECT COUNT(*) FROM stock_movements WHERE product_id = ?",
            [$productId]
        );
        return $count > 0;
    }

    private function calculateNewStockQuantity($currentStock, $movementType, $quantity)
    {
        switch ($movementType) {
            case 'purchase':
            case 'return':
            case 'adjustment_in':
                return $currentStock + $quantity;
            case 'sale':
            case 'transfer_out':
            case 'adjustment_out':
            case 'damage':
                return $currentStock - $quantity;
            default:
                return $currentStock;
        }
    }

    private function calculateProfitMargin($cost, $price)
    {
        if ($cost <= 0) return 0;
        return round((($price - $cost) / $cost) * 100, 2);
    }

    private function generateReferenceNumber()
    {
        return 'SM-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    private function checkStockAlerts($productId, $newStockQuantity)
    {
        $product = $this->getProductById($productId);
        if (!$product) return;

        $alertType = null;
        $severity = 'low';

        if ($newStockQuantity == 0) {
            $alertType = 'out_of_stock';
            $severity = 'high';
        } elseif ($newStockQuantity <= $product['reorder_point']) {
            $alertType = 'low_stock';
            $severity = 'medium';
        } elseif ($newStockQuantity >= $product['maximum_stock']) {
            $alertType = 'overstock';
            $severity = 'low';
        }

        if ($alertType) {
            $this->db->insert('inventory_alerts', [
                'product_id' => $productId,
                'warehouse_id' => $product['warehouse_id'],
                'alert_type' => $alertType,
                'severity' => $severity,
                'message' => ucfirst(str_replace('_', ' ', $alertType)) . " alert for {$product['product_name']}",
                'company_id' => $this->user['company_id'],
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'active'
            ]);
        }
    }

    private function getProductsCount($filters)
    {
        $where = ["p.company_id = ?"];
        $params = [$this->user['company_id']];

        // Add same filters as getProducts method
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

        return $this->db->queryValue("SELECT COUNT(*) FROM products p WHERE $whereClause", $params);
    }

    private function getStockMovementsCount($filters)
    {
        $where = ["sm.company_id = ?"];
        $params = [$this->user['company_id']];

        if (isset($filters['product_id'])) {
            $where[] = "sm.product_id = ?";
            $params[] = $filters['product_id'];
        }

        if (isset($filters['movement_type'])) {
            $where[] = "sm.movement_type = ?";
            $params[] = $filters['movement_type'];
        }

        if (isset($filters['warehouse_id'])) {
            $where[] = "sm.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }

        if (isset($filters['date_from'])) {
            $where[] = "sm.movement_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (isset($filters['date_to'])) {
            $where[] = "sm.movement_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM stock_movements sm WHERE $whereClause", $params);
    }

    private function logActivity($action, $table, $recordId, $description)
    {
        $this->db->insert('audit_log', [
            'user_id' => $this->user['id'],
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
