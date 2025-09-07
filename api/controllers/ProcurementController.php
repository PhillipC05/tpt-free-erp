<?php

namespace TPT\ERP\Api\Controllers;

use TPT\ERP\Core\Response;
use TPT\ERP\Core\Request;
use TPT\ERP\Core\Database;
use TPT\ERP\Modules\Procurement;

/**
 * Procurement API Controller
 * Handles all procurement-related API endpoints
 */
class ProcurementController extends BaseController
{
    private $procurement;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->procurement = new Procurement();
        $this->db = Database::getInstance();
    }

    /**
     * Get procurement dashboard overview
     * GET /api/procurement/overview
     */
    public function getOverview()
    {
        try {
            $this->requirePermission('procurement.view');

            $data = [
                'procurement_overview' => $this->procurement->getProcurementOverview(),
                'pending_approvals' => $this->procurement->getPendingApprovals(),
                'supplier_performance' => $this->procurement->getSupplierPerformance(),
                'purchase_orders' => $this->procurement->getPurchaseOrdersSummary(),
                'spend_analysis' => $this->procurement->getSpendAnalysis(),
                'contract_expirations' => $this->procurement->getContractExpirations(),
                'requisition_status' => $this->procurement->getRequisitionStatus(),
                'procurement_analytics' => $this->procurement->getProcurementAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get vendors with filtering
     * GET /api/procurement/vendors
     */
    public function getVendors()
    {
        try {
            $this->requirePermission('procurement.vendors.view');

            $filters = [
                'status' => $_GET['status'] ?? null,
                'category' => $_GET['category'] ?? null,
                'rating_min' => $_GET['rating_min'] ?? null,
                'rating_max' => $_GET['rating_max'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $vendors = $this->procurement->getVendors($filters);
            $total = $this->getVendorsCount($filters);

            Response::json([
                'vendors' => $vendors,
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
     * Create new vendor
     * POST /api/procurement/vendors
     */
    public function createVendor()
    {
        try {
            $this->requirePermission('procurement.vendors.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['vendor_name', 'email'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                Response::error('Vendor with this email already exists', 400);
                return;
            }

            $vendorData = [
                'vendor_name' => trim($data['vendor_name']),
                'email' => trim($data['email']),
                'phone' => $data['phone'] ?? '',
                'contact_person' => $data['contact_person'] ?? '',
                'address' => $data['address'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'postal_code' => $data['postal_code'] ?? '',
                'country' => $data['country'] ?? '',
                'category' => $data['category'] ?? 'other',
                'rating' => (float)($data['rating'] ?? 3.0),
                'status' => $data['status'] ?? 'active',
                'lead_time_days' => (int)($data['lead_time_days'] ?? 7),
                'payment_terms' => $data['payment_terms'] ?? 'net_30',
                'tax_id' => $data['tax_id'] ?? '',
                'website' => $data['website'] ?? '',
                'notes' => $data['notes'] ?? '',
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_date' => date('Y-m-d H:i:s')
            ];

            $vendorId = $this->db->insert('vendors', $vendorData);

            // Log the creation
            $this->logActivity('vendor_created', 'vendors', $vendorId, "Vendor '{$vendorData['vendor_name']}' created");

            Response::json([
                'success' => true,
                'vendor_id' => $vendorId,
                'message' => 'Vendor created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Update vendor
     * PUT /api/procurement/vendors/{id}
     */
    public function updateVendor($id)
    {
        try {
            $this->requirePermission('procurement.vendors.update');

            $data = Request::getJsonBody();

            // Check if vendor exists and belongs to company
            $vendor = $this->getVendorById($id);
            if (!$vendor) {
                Response::error('Vendor not found', 404);
                return;
            }

            // Check email uniqueness if changed
            if (isset($data['email']) && $data['email'] !== $vendor['email'] && $this->emailExists($data['email'])) {
                Response::error('Vendor with this email already exists', 400);
                return;
            }

            $updateData = [];
            $allowedFields = [
                'vendor_name', 'email', 'phone', 'contact_person', 'address', 'city', 'state',
                'postal_code', 'country', 'category', 'rating', 'status', 'lead_time_days',
                'payment_terms', 'tax_id', 'website', 'notes'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_by'] = $this->user['id'];
                $updateData['updated_date'] = date('Y-m-d H:i:s');

                $this->db->update('vendors', $updateData, ['id' => $id]);

                // Log the update
                $this->logActivity('vendor_updated', 'vendors', $id, "Vendor '{$vendor['vendor_name']}' updated");
            }

            Response::json([
                'success' => true,
                'message' => 'Vendor updated successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Delete vendor
     * DELETE /api/procurement/vendors/{id}
     */
    public function deleteVendor($id)
    {
        try {
            $this->requirePermission('procurement.vendors.delete');

            $vendor = $this->getVendorById($id);
            if (!$vendor) {
                Response::error('Vendor not found', 404);
                return;
            }

            // Check if vendor has active orders
            if ($this->hasActiveOrders($id)) {
                Response::error('Cannot delete vendor with active orders', 400);
                return;
            }

            $this->db->update('vendors', [
                'status' => 'inactive',
                'updated_by' => $this->user['id'],
                'updated_date' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            // Log the deactivation
            $this->logActivity('vendor_deactivated', 'vendors', $id, "Vendor '{$vendor['vendor_name']}' deactivated");

            Response::json([
                'success' => true,
                'message' => 'Vendor deactivated successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get purchase orders with filtering
     * GET /api/procurement/purchase-orders
     */
    public function getPurchaseOrders()
    {
        try {
            $this->requirePermission('procurement.purchase_orders.view');

            $filters = [
                'status' => $_GET['status'] ?? null,
                'vendor' => $_GET['vendor'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'amount_min' => $_GET['amount_min'] ?? null,
                'amount_max' => $_GET['amount_max'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $purchaseOrders = $this->procurement->getPurchaseOrders($filters);
            $total = $this->getPurchaseOrdersCount($filters);

            Response::json([
                'purchase_orders' => $purchaseOrders,
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
     * Create purchase order
     * POST /api/procurement/purchase-orders
     */
    public function createPurchaseOrder()
    {
        try {
            $this->requirePermission('procurement.purchase_orders.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['vendor_id', 'order_items'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if vendor exists
            $vendor = $this->getVendorById($data['vendor_id']);
            if (!$vendor) {
                Response::error('Vendor not found', 400);
                return;
            }

            // Calculate order total
            $orderItems = $data['order_items'];
            $totalAmount = 0;
            foreach ($orderItems as $item) {
                $totalAmount += (float)$item['quantity'] * (float)$item['unit_price'];
            }

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Create purchase order
                $orderData = [
                    'order_number' => $this->generateOrderNumber(),
                    'vendor_id' => $data['vendor_id'],
                    'order_date' => date('Y-m-d H:i:s'),
                    'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                    'total_amount' => $totalAmount,
                    'status' => 'draft',
                    'shipping_method' => $data['shipping_method'] ?? '',
                    'payment_terms' => $data['payment_terms'] ?? 'net_30',
                    'notes' => $data['notes'] ?? '',
                    'company_id' => $this->user['company_id'],
                    'created_by' => $this->user['id'],
                    'created_date' => date('Y-m-d H:i:s')
                ];

                $orderId = $this->db->insert('purchase_orders', $orderData);

                // Create order items
                foreach ($orderItems as $item) {
                    $this->db->insert('purchase_order_items', [
                        'purchase_order_id' => $orderId,
                        'item_description' => $item['item_description'],
                        'quantity' => (int)$item['quantity'],
                        'unit_price' => (float)$item['unit_price'],
                        'line_total' => (float)$item['quantity'] * (float)$item['unit_price'],
                        'category_id' => $item['category_id'] ?? null,
                        'company_id' => $this->user['company_id']
                    ]);
                }

                $this->db->commit();

                // Log the creation
                $this->logActivity('purchase_order_created', 'purchase_orders', $orderId, "Purchase order '{$orderData['order_number']}' created");

                Response::json([
                    'success' => true,
                    'order_id' => $orderId,
                    'order_number' => $orderData['order_number'],
                    'message' => 'Purchase order created successfully'
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
     * Update purchase order status
     * PUT /api/procurement/purchase-orders/{id}/status
     */
    public function updatePurchaseOrderStatus($id)
    {
        try {
            $this->requirePermission('procurement.purchase_orders.update');

            $data = Request::getJsonBody();

            if (!isset($data['status'])) {
                Response::error('Status is required', 400);
                return;
            }

            // Check if order exists
            $order = $this->getPurchaseOrderById($id);
            if (!$order) {
                Response::error('Purchase order not found', 404);
                return;
            }

            $updateData = [
                'status' => $data['status'],
                'updated_by' => $this->user['id'],
                'updated_date' => date('Y-m-d H:i:s')
            ];

            // Add delivery date if status is delivered
            if ($data['status'] === 'delivered') {
                $updateData['actual_delivery_date'] = date('Y-m-d H:i:s');
            }

            $this->db->update('purchase_orders', $updateData, ['id' => $id]);

            // Log the status update
            $this->logActivity('purchase_order_status_updated', 'purchase_orders', $id, "Purchase order status updated to {$data['status']}");

            Response::json([
                'success' => true,
                'message' => 'Purchase order status updated successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get requisitions
     * GET /api/procurement/requisitions
     */
    public function getRequisitions()
    {
        try {
            $this->requirePermission('procurement.requisitions.view');

            $requisitions = $this->procurement->getRequisitions();

            Response::json(['requisitions' => $requisitions]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create requisition
     * POST /api/procurement/requisitions
     */
    public function createRequisition()
    {
        try {
            $this->requirePermission('procurement.requisitions.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['department_id', 'requisition_items'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Calculate requisition total
            $requisitionItems = $data['requisition_items'];
            $totalAmount = 0;
            foreach ($requisitionItems as $item) {
                $totalAmount += (float)$item['estimated_cost'];
            }

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Create requisition
                $requisitionData = [
                    'requisition_number' => $this->generateRequisitionNumber(),
                    'department_id' => $data['department_id'],
                    'requested_by' => $this->user['id'],
                    'total_amount' => $totalAmount,
                    'status' => 'draft',
                    'priority' => $data['priority'] ?? 'medium',
                    'required_date' => $data['required_date'] ?? null,
                    'justification' => $data['justification'] ?? '',
                    'notes' => $data['notes'] ?? '',
                    'company_id' => $this->user['company_id'],
                    'created_by' => $this->user['id'],
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $requisitionId = $this->db->insert('requisitions', $requisitionData);

                // Create requisition items
                foreach ($requisitionItems as $item) {
                    $this->db->insert('requisition_items', [
                        'requisition_id' => $requisitionId,
                        'item_description' => $item['item_description'],
                        'quantity' => (int)$item['quantity'],
                        'estimated_cost' => (float)$item['estimated_cost'],
                        'category_id' => $item['category_id'] ?? null,
                        'company_id' => $this->user['company_id']
                    ]);
                }

                $this->db->commit();

                // Log the creation
                $this->logActivity('requisition_created', 'requisitions', $requisitionId, "Requisition '{$requisitionData['requisition_number']}' created");

                Response::json([
                    'success' => true,
                    'requisition_id' => $requisitionId,
                    'requisition_number' => $requisitionData['requisition_number'],
                    'message' => 'Requisition created successfully'
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
     * Approve requisition
     * PUT /api/procurement/requisitions/{id}/approve
     */
    public function approveRequisition($id)
    {
        try {
            $this->requirePermission('procurement.requisitions.approve');

            $requisition = $this->getRequisitionById($id);
            if (!$requisition) {
                Response::error('Requisition not found', 404);
                return;
            }

            $this->db->update('requisitions', [
                'status' => 'approved',
                'approved_by' => $this->user['id'],
                'approved_at' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            // Log the approval
            $this->logActivity('requisition_approved', 'requisitions', $id, "Requisition approved");

            Response::json([
                'success' => true,
                'message' => 'Requisition approved successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get contracts
     * GET /api/procurement/contracts
     */
    public function getContracts()
    {
        try {
            $this->requirePermission('procurement.contracts.view');

            $contracts = $this->procurement->getContracts();

            Response::json(['contracts' => $contracts]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create contract
     * POST /api/procurement/contracts
     */
    public function createContract()
    {
        try {
            $this->requirePermission('procurement.contracts.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['vendor_id', 'contract_title', 'start_date', 'end_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if vendor exists
            $vendor = $this->getVendorById($data['vendor_id']);
            if (!$vendor) {
                Response::error('Vendor not found', 400);
                return;
            }

            $contractData = [
                'vendor_id' => $data['vendor_id'],
                'contract_title' => trim($data['contract_title']),
                'contract_type' => $data['contract_type'] ?? 'supply',
                'contract_number' => $this->generateContractNumber(),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'contract_value' => (float)($data['contract_value'] ?? 0),
                'auto_renewal' => (bool)($data['auto_renewal'] ?? false),
                'renewal_notice_days' => (int)($data['renewal_notice_days'] ?? 30),
                'payment_terms' => $data['payment_terms'] ?? 'net_30',
                'description' => $data['description'] ?? '',
                'terms_conditions' => $data['terms_conditions'] ?? '',
                'status' => 'active',
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $contractId = $this->db->insert('contracts', $contractData);

            // Log the creation
            $this->logActivity('contract_created', 'contracts', $contractId, "Contract '{$contractData['contract_title']}' created");

            Response::json([
                'success' => true,
                'contract_id' => $contractId,
                'contract_number' => $contractData['contract_number'],
                'message' => 'Contract created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get spend analysis
     * GET /api/procurement/spend-analysis
     */
    public function getSpendAnalysis()
    {
        try {
            $this->requirePermission('procurement.spend.view');

            $data = [
                'spend_by_category' => $this->procurement->getSpendByCategory(),
                'spend_by_vendor' => $this->procurement->getSpendByVendor(),
                'spend_trends' => $this->procurement->getSpendTrends(),
                'cost_savings' => $this->procurement->getCostSavings(),
                'spend_analytics' => $this->procurement->getSpendAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get supplier evaluations
     * GET /api/procurement/supplier-evaluations
     */
    public function getSupplierEvaluations()
    {
        try {
            $this->requirePermission('procurement.evaluation.view');

            $evaluations = $this->procurement->getSupplierEvaluations();

            Response::json(['evaluations' => $evaluations]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create supplier evaluation
     * POST /api/procurement/supplier-evaluations
     */
    public function createSupplierEvaluation()
    {
        try {
            $this->requirePermission('procurement.evaluation.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['vendor_id', 'evaluation_criteria', 'rating_score'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if vendor exists
            $vendor = $this->getVendorById($data['vendor_id']);
            if (!$vendor) {
                Response::error('Vendor not found', 400);
                return;
            }

            $evaluationData = [
                'vendor_id' => $data['vendor_id'],
                'evaluated_by' => $this->user['id'],
                'evaluation_criteria' => $data['evaluation_criteria'],
                'rating_score' => (float)$data['rating_score'],
                'comments' => $data['comments'] ?? '',
                'evaluation_date' => date('Y-m-d'),
                'next_evaluation_date' => $data['next_evaluation_date'] ?? null,
                'company_id' => $this->user['company_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $evaluationId = $this->db->insert('supplier_evaluations', $evaluationData);

            // Update vendor rating
            $this->updateVendorRating($data['vendor_id']);

            // Log the evaluation
            $this->logActivity('supplier_evaluated', 'supplier_evaluations', $evaluationId, "Supplier evaluation completed for {$vendor['vendor_name']}");

            Response::json([
                'success' => true,
                'evaluation_id' => $evaluationId,
                'message' => 'Supplier evaluation completed successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Bulk update vendors
     * POST /api/procurement/vendors/bulk-update
     */
    public function bulkUpdateVendors()
    {
        try {
            $this->requirePermission('procurement.vendors.update');

            $data = Request::getJsonBody();

            if (!isset($data['vendor_ids']) || !is_array($data['vendor_ids'])) {
                Response::error('Vendor IDs array is required', 400);
                return;
            }

            if (empty($data['updates'])) {
                Response::error('Updates object is required', 400);
                return;
            }

            $vendorIds = $data['vendor_ids'];
            $updates = $data['updates'];

            // Start transaction
            $this->db->beginTransaction();

            try {
                $updateCount = 0;

                foreach ($vendorIds as $vendorId) {
                    $vendor = $this->getVendorById($vendorId);
                    if (!$vendor) continue;

                    $updateData = [];
                    $allowedFields = [
                        'category', 'status', 'rating', 'lead_time_days', 'payment_terms'
                    ];

                    foreach ($allowedFields as $field) {
                        if (isset($updates[$field])) {
                            $updateData[$field] = $updates[$field];
                        }
                    }

                    if (!empty($updateData)) {
                        $updateData['updated_by'] = $this->user['id'];
                        $updateData['updated_date'] = date('Y-m-d H:i:s');

                        $this->db->update('vendors', $updateData, ['id' => $vendorId]);
                        $updateCount++;
                    }
                }

                $this->db->commit();

                // Log bulk update
                $this->logActivity('bulk_vendor_update', 'vendors', null, "Bulk updated {$updateCount} vendors");

                Response::json([
                    'success' => true,
                    'updated_count' => $updateCount,
                    'message' => "{$updateCount} vendors updated successfully"
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

    private function getVendorById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM vendors WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function getPurchaseOrderById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM purchase_orders WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function getRequisitionById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM requisitions WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function emailExists($email)
    {
        $count = $this->db->queryValue(
            "SELECT COUNT(*) FROM vendors WHERE email = ? AND company_id = ?",
            [$email, $this->user['company_id']]
        );
        return $count > 0;
    }

    private function hasActiveOrders($vendorId)
    {
        $count = $this->db->queryValue(
            "SELECT COUNT(*) FROM purchase_orders WHERE vendor_id = ? AND status IN ('draft', 'approved', 'ordered', 'shipped')",
            [$vendorId]
        );
        return $count > 0;
    }

    private function generateOrderNumber()
    {
        return 'PO-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    private function generateRequisitionNumber()
    {
        return 'REQ-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    private function generateContractNumber()
    {
        return 'CTR-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    private function updateVendorRating($vendorId)
    {
        // Calculate average rating from recent evaluations
        $avgRating = $this->db->queryValue("
            SELECT AVG(rating_score) FROM supplier_evaluations
            WHERE vendor_id = ? AND evaluation_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
        ", [$vendorId]);

        if ($avgRating) {
            $this->db->update('vendors', [
                'rating' => round($avgRating, 1),
                'updated_by' => $this->user['id'],
                'updated_date' => date('Y-m-d H:i:s')
            ], ['id' => $vendorId]);
        }
    }

    private function getVendorsCount($filters)
    {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['category']) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }

        if ($filters['rating_min']) {
            $where[] = "rating >= ?";
            $params[] = $filters['rating_min'];
        }

        if ($filters['rating_max']) {
            $where[] = "rating <= ?";
            $params[] = $filters['rating_max'];
        }

        if ($filters['search']) {
            $where[] = "(vendor_name LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM vendors WHERE $whereClause", $params);
    }

    private function getPurchaseOrdersCount($filters)
    {
        $where = ["po.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "po.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['vendor']) {
            $where[] = "po.vendor_id = ?";
            $params[] = $filters['vendor'];
        }

        if ($filters['date_from']) {
            $where[] = "po.order_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "po.order_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['amount_min']) {
            $where[] = "po.total_amount >= ?";
            $params[] = $filters['amount_min'];
        }

        if ($filters['amount_max']) {
            $where[] = "po.total_amount <= ?";
            $params[] = $filters['amount_max'];
        }

        if ($filters['search']) {
            $where[] = "(po.order_number LIKE ? OR v.vendor_name LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM purchase_orders po LEFT JOIN vendors v ON po.vendor_id = v.id WHERE $whereClause", $params);
    }

    private function logActivity($action, $table, $recordId, $description)
    {
        $this->db->insert('procurement_activities', [
            'user_id' => $this->user['id'],
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'description' => $description,
            'company_id' => $this->user['company_id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
