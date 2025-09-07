<?php

namespace TPT\ERP\Api\Controllers;

use TPT\ERP\Core\Response;
use TPT\ERP\Core\Request;
use TPT\ERP\Core\Database;
use TPT\ERP\Modules\Manufacturing;

/**
 * Manufacturing API Controller
 * Handles all manufacturing-related API endpoints
 */
class ManufacturingController extends BaseController
{
    private $manufacturing;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->manufacturing = new Manufacturing();
        $this->db = Database::getInstance();
    }

    /**
     * Get manufacturing dashboard overview
     * GET /api/manufacturing/overview
     */
    public function getOverview()
    {
        try {
            $this->requirePermission('manufacturing.view');

            $data = [
                'production_overview' => $this->manufacturing->getProductionOverview(),
                'work_order_status' => $this->manufacturing->getWorkOrderStatus(),
                'production_schedule' => $this->manufacturing->getProductionSchedule(),
                'quality_metrics' => $this->manufacturing->getQualityMetrics(),
                'resource_utilization' => $this->manufacturing->getResourceUtilization(),
                'production_efficiency' => $this->manufacturing->getProductionEfficiency(),
                'inventory_status' => $this->manufacturing->getInventoryStatus(),
                'maintenance_alerts' => $this->manufacturing->getMaintenanceAlerts()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get production plans
     * GET /api/manufacturing/production-plans
     */
    public function getProductionPlans()
    {
        try {
            $this->requirePermission('manufacturing.planning.view');

            $plans = $this->manufacturing->getProductionPlans();

            Response::json(['production_plans' => $plans]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create production plan
     * POST /api/manufacturing/production-plans
     */
    public function createProductionPlan()
    {
        try {
            $this->requirePermission('manufacturing.planning.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['plan_name', 'start_date', 'end_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $planData = [
                'plan_name' => trim($data['plan_name']),
                'planning_period' => $data['planning_period'] ?? 'monthly',
                'total_quantity_planned' => (int)($data['total_quantity_planned'] ?? 0),
                'total_quantity_produced' => 0,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'description' => $data['description'] ?? '',
                'status' => 'draft',
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $planId = $this->db->insert('production_plans', $planData);

            // Log the creation
            $this->logActivity('production_plan_created', 'production_plans', $planId, "Production plan '{$planData['plan_name']}' created");

            Response::json([
                'success' => true,
                'plan_id' => $planId,
                'message' => 'Production plan created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get bills of materials
     * GET /api/manufacturing/boms
     */
    public function getBillsOfMaterials()
    {
        try {
            $this->requirePermission('manufacturing.bom.view');

            $filters = [
                'product' => $_GET['product'] ?? null,
                'status' => $_GET['status'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $boms = $this->manufacturing->getBillsOfMaterials($filters);
            $total = $this->getBOMsCount($filters);

            Response::json([
                'boms' => $boms,
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
     * Create bill of materials
     * POST /api/manufacturing/boms
     */
    public function createBOM()
    {
        try {
            $this->requirePermission('manufacturing.bom.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['product_id', 'version_number'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if product exists
            $product = $this->getProductById($data['product_id']);
            if (!$product) {
                Response::error('Product not found', 400);
                return;
            }

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Create BOM
                $bomData = [
                    'product_id' => $data['product_id'],
                    'version_number' => (int)$data['version_number'],
                    'effective_date' => $data['effective_date'] ?? date('Y-m-d'),
                    'status' => $data['status'] ?? 'draft',
                    'description' => $data['description'] ?? '',
                    'total_quantity' => (float)($data['total_quantity'] ?? 1),
                    'yield_percentage' => (float)($data['yield_percentage'] ?? 100),
                    'company_id' => $this->user['company_id'],
                    'created_by' => $this->user['id'],
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $bomId = $this->db->insert('bills_of_materials', $bomData);

                // Create BOM components if provided
                if (isset($data['components']) && is_array($data['components'])) {
                    foreach ($data['components'] as $component) {
                        $this->db->insert('bom_components', [
                            'bom_id' => $bomId,
                            'component_id' => $component['component_id'],
                            'item_description' => $component['item_description'] ?? '',
                            'quantity_required' => (float)$component['quantity_required'],
                            'unit_cost' => (float)($component['unit_cost'] ?? 0),
                            'labor_cost' => (float)($component['labor_cost'] ?? 0),
                            'overhead_cost' => (float)($component['overhead_cost'] ?? 0),
                            'yield_percentage' => (float)($component['yield_percentage'] ?? 100),
                            'company_id' => $this->user['company_id']
                        ]);
                    }
                }

                $this->db->commit();

                // Log the creation
                $this->logActivity('bom_created', 'bills_of_materials', $bomId, "BOM for product '{$product['product_name']}' created");

                Response::json([
                    'success' => true,
                    'bom_id' => $bomId,
                    'message' => 'Bill of materials created successfully'
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
     * Get work orders with filtering
     * GET /api/manufacturing/work-orders
     */
    public function getWorkOrders()
    {
        try {
            $this->requirePermission('manufacturing.work_orders.view');

            $filters = [
                'status' => $_GET['status'] ?? null,
                'production_line' => $_GET['production_line'] ?? null,
                'priority' => $_GET['priority'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $workOrders = $this->manufacturing->getWorkOrders($filters);
            $total = $this->getWorkOrdersCount($filters);

            Response::json([
                'work_orders' => $workOrders,
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
     * Create work order
     * POST /api/manufacturing/work-orders
     */
    public function createWorkOrder()
    {
        try {
            $this->requirePermission('manufacturing.work_orders.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['bom_id', 'production_line_id', 'quantity_planned'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if BOM exists
            $bom = $this->getBOMById($data['bom_id']);
            if (!$bom) {
                Response::error('Bill of materials not found', 400);
                return;
            }

            // Check if production line exists
            $productionLine = $this->getProductionLineById($data['production_line_id']);
            if (!$productionLine) {
                Response::error('Production line not found', 400);
                return;
            }

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Create work order
                $orderData = [
                    'work_order_number' => $this->generateWorkOrderNumber(),
                    'bom_id' => $data['bom_id'],
                    'production_line_id' => $data['production_line_id'],
                    'quantity_planned' => (int)$data['quantity_planned'],
                    'quantity_produced' => 0,
                    'quantity_scrapped' => 0,
                    'start_date' => $data['start_date'] ?? null,
                    'end_date' => $data['end_date'] ?? null,
                    'actual_start_date' => null,
                    'actual_end_date' => null,
                    'planned_cycle_time' => (float)($data['planned_cycle_time'] ?? 0),
                    'actual_cycle_time' => null,
                    'setup_time' => (float)($data['setup_time'] ?? 0),
                    'labor_efficiency' => null,
                    'material_cost' => 0,
                    'labor_cost' => 0,
                    'overhead_cost' => 0,
                    'priority' => $data['priority'] ?? 'medium',
                    'status' => 'draft',
                    'notes' => $data['notes'] ?? '',
                    'company_id' => $this->user['company_id'],
                    'created_by' => $this->user['id'],
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $orderId = $this->db->insert('work_orders', $orderData);

                // Create routing operations if provided
                if (isset($data['operations']) && is_array($data['operations'])) {
                    foreach ($data['operations'] as $index => $operation) {
                        $this->db->insert('routing_operations', [
                            'work_order_id' => $orderId,
                            'operation_name' => $operation['operation_name'],
                            'operation_sequence' => $index + 1,
                            'planned_setup_time' => (float)($operation['planned_setup_time'] ?? 0),
                            'planned_run_time' => (float)($operation['planned_run_time'] ?? 0),
                            'actual_setup_time' => null,
                            'actual_run_time' => null,
                            'status' => 'pending',
                            'company_id' => $this->user['company_id']
                        ]);
                    }
                }

                $this->db->commit();

                // Log the creation
                $this->logActivity('work_order_created', 'work_orders', $orderId, "Work order '{$orderData['work_order_number']}' created");

                Response::json([
                    'success' => true,
                    'work_order_id' => $orderId,
                    'work_order_number' => $orderData['work_order_number'],
                    'message' => 'Work order created successfully'
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
     * Update work order status
     * PUT /api/manufacturing/work-orders/{id}/status
     */
    public function updateWorkOrderStatus($id)
    {
        try {
            $this->requirePermission('manufacturing.work_orders.update');

            $data = Request::getJsonBody();

            if (!isset($data['status'])) {
                Response::error('Status is required', 400);
                return;
            }

            // Check if work order exists
            $workOrder = $this->getWorkOrderById($id);
            if (!$workOrder) {
                Response::error('Work order not found', 404);
                return;
            }

            $updateData = [
                'status' => $data['status'],
                'updated_by' => $this->user['id'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Add timestamps based on status
            if ($data['status'] === 'in_progress' && !$workOrder['actual_start_date']) {
                $updateData['actual_start_date'] = date('Y-m-d H:i:s');
            } elseif (in_array($data['status'], ['completed', 'cancelled']) && !$workOrder['actual_end_date']) {
                $updateData['actual_end_date'] = date('Y-m-d H:i:s');
                $updateData['completed_at'] = date('Y-m-d H:i:s');
            }

            $this->db->update('work_orders', $updateData, ['id' => $id]);

            // Log the status update
            $this->logActivity('work_order_status_updated', 'work_orders', $id, "Work order status updated to {$data['status']}");

            Response::json([
                'success' => true,
                'message' => 'Work order status updated successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Record production data
     * POST /api/manufacturing/work-orders/{id}/production-data
     */
    public function recordProductionData($id)
    {
        try {
            $this->requirePermission('manufacturing.work_orders.update');

            $data = Request::getJsonBody();

            // Check if work order exists
            $workOrder = $this->getWorkOrderById($id);
            if (!$workOrder) {
                Response::error('Work order not found', 404);
                return;
            }

            $productionData = [
                'work_order_id' => $id,
                'data_type' => $data['data_type'] ?? 'quantity',
                'value' => (float)$data['value'],
                'unit_of_measure' => $data['unit_of_measure'] ?? 'pieces',
                'timestamp' => date('Y-m-d H:i:s'),
                'recorded_by' => $this->user['id'],
                'quality_score' => (float)($data['quality_score'] ?? 100),
                'notes' => $data['notes'] ?? '',
                'company_id' => $this->user['company_id']
            ];

            $dataId = $this->db->insert('production_data', $productionData);

            // Update work order quantities if it's quantity data
            if ($data['data_type'] === 'quantity') {
                $this->updateWorkOrderQuantities($id, (float)$data['value']);
            }

            // Log the data recording
            $this->logActivity('production_data_recorded', 'production_data', $dataId, "Production data recorded for work order {$workOrder['work_order_number']}");

            Response::json([
                'success' => true,
                'data_id' => $dataId,
                'message' => 'Production data recorded successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get quality inspections
     * GET /api/manufacturing/quality-inspections
     */
    public function getQualityInspections()
    {
        try {
            $this->requirePermission('manufacturing.quality.view');

            $inspections = $this->manufacturing->getQualityInspections();

            Response::json(['quality_inspections' => $inspections]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create quality inspection
     * POST /api/manufacturing/quality-inspections
     */
    public function createQualityInspection()
    {
        try {
            $this->requirePermission('manufacturing.quality.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['work_order_id', 'inspection_type'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if work order exists
            $workOrder = $this->getWorkOrderById($data['work_order_id']);
            if (!$workOrder) {
                Response::error('Work order not found', 400);
                return;
            }

            $inspectionData = [
                'work_order_id' => $data['work_order_id'],
                'inspection_type' => $data['inspection_type'],
                'specification' => $data['specification'] ?? '',
                'actual_value' => $data['actual_value'] ?? null,
                'upper_limit' => $data['upper_limit'] ?? null,
                'lower_limit' => $data['lower_limit'] ?? null,
                'result' => $data['result'] ?? 'pending',
                'defect_rate' => (float)($data['defect_rate'] ?? 0),
                'inspected_by' => $this->user['id'],
                'inspection_date' => date('Y-m-d H:i:s'),
                'notes' => $data['notes'] ?? '',
                'company_id' => $this->user['company_id']
            ];

            $inspectionId = $this->db->insert('quality_inspections', $inspectionData);

            // Log the inspection
            $this->logActivity('quality_inspection_created', 'quality_inspections', $inspectionId, "Quality inspection created for work order {$workOrder['work_order_number']}");

            Response::json([
                'success' => true,
                'inspection_id' => $inspectionId,
                'message' => 'Quality inspection created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get production lines
     * GET /api/manufacturing/production-lines
     */
    public function getProductionLines()
    {
        try {
            $this->requirePermission('manufacturing.resources.view');

            $productionLines = $this->manufacturing->getProductionLines();

            Response::json(['production_lines' => $productionLines]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create production line
     * POST /api/manufacturing/production-lines
     */
    public function createProductionLine()
    {
        try {
            $this->requirePermission('manufacturing.resources.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['line_name'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $lineData = [
                'line_name' => trim($data['line_name']),
                'description' => $data['description'] ?? '',
                'capacity_per_hour' => (int)($data['capacity_per_hour'] ?? 0),
                'capacity_per_shift' => (int)($data['capacity_per_shift'] ?? 0),
                'capacity_per_day' => (int)($data['capacity_per_day'] ?? 0),
                'capacity_utilization' => 0,
                'availability_percentage' => 100,
                'oee_percentage' => 0,
                'status' => $data['status'] ?? 'active',
                'last_maintenance_date' => $data['last_maintenance_date'] ?? null,
                'next_maintenance_date' => $data['next_maintenance_date'] ?? null,
                'location' => $data['location'] ?? '',
                'supervisor_id' => $data['supervisor_id'] ?? null,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $lineId = $this->db->insert('production_lines', $lineData);

            // Log the creation
            $this->logActivity('production_line_created', 'production_lines', $lineId, "Production line '{$lineData['line_name']}' created");

            Response::json([
                'success' => true,
                'line_id' => $lineId,
                'message' => 'Production line created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get shop floor monitoring data
     * GET /api/manufacturing/shop-floor/monitoring
     */
    public function getShopFloorMonitoring()
    {
        try {
            $this->requirePermission('manufacturing.shop_floor.view');

            $data = [
                'production_monitoring' => $this->manufacturing->getProductionMonitoring(),
                'machine_monitoring' => $this->manufacturing->getMachineMonitoring(),
                'downtime_tracking' => $this->manufacturing->getDowntimeTracking(),
                'real_time_alerts' => $this->manufacturing->getRealTimeAlerts(),
                'shop_floor_analytics' => $this->manufacturing->getShopFloorAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Record downtime
     * POST /api/manufacturing/downtime
     */
    public function recordDowntime()
    {
        try {
            $this->requirePermission('manufacturing.shop_floor.update');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['production_line_id', 'downtime_reason', 'duration_minutes'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if production line exists
            $productionLine = $this->getProductionLineById($data['production_line_id']);
            if (!$productionLine) {
                Response::error('Production line not found', 400);
                return;
            }

            $downtimeData = [
                'production_line_id' => $data['production_line_id'],
                'work_order_id' => $data['work_order_id'] ?? null,
                'downtime_reason' => $data['downtime_reason'],
                'duration_minutes' => (int)$data['duration_minutes'],
                'cost_impact' => (float)($data['cost_impact'] ?? 0),
                'downtime_date' => $data['downtime_date'] ?? date('Y-m-d'),
                'start_time' => $data['start_time'] ?? date('H:i:s'),
                'end_time' => $data['end_time'] ?? null,
                'resolution_time' => $data['resolution_time'] ?? null,
                'reported_by' => $this->user['id'],
                'resolution_notes' => $data['resolution_notes'] ?? '',
                'company_id' => $this->user['company_id']
            ];

            $downtimeId = $this->db->insert('downtime_tracking', $downtimeData);

            // Log the downtime
            $this->logActivity('downtime_recorded', 'downtime_tracking', $downtimeId, "Downtime recorded for production line {$productionLine['line_name']}");

            Response::json([
                'success' => true,
                'downtime_id' => $downtimeId,
                'message' => 'Downtime recorded successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get manufacturing analytics
     * GET /api/manufacturing/analytics
     */
    public function getAnalytics()
    {
        try {
            $this->requirePermission('manufacturing.analytics.view');

            $data = [
                'production_analytics' => $this->manufacturing->getProductionAnalytics(),
                'efficiency_analytics' => $this->manufacturing->getEfficiencyAnalytics(),
                'quality_analytics' => $this->manufacturing->getQualityAnalytics(),
                'cost_analytics' => $this->manufacturing->getCostAnalytics(),
                'resource_analytics' => $this->manufacturing->getResourceAnalytics(),
                'predictive_analytics' => $this->manufacturing->getPredictiveAnalytics(),
                'benchmarking_analytics' => $this->manufacturing->getBenchmarkingAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Bulk update work orders
     * POST /api/manufacturing/work-orders/bulk-update
     */
    public function bulkUpdateWorkOrders()
    {
        try {
            $this->requirePermission('manufacturing.work_orders.update');

            $data = Request::getJsonBody();

            if (!isset($data['work_order_ids']) || !is_array($data['work_order_ids'])) {
                Response::error('Work order IDs array is required', 400);
                return;
            }

            if (empty($data['updates'])) {
                Response::error('Updates object is required', 400);
                return;
            }

            $workOrderIds = $data['work_order_ids'];
            $updates = $data['updates'];

            // Start transaction
            $this->db->beginTransaction();

            try {
                $updateCount = 0;

                foreach ($workOrderIds as $workOrderId) {
                    $workOrder = $this->getWorkOrderById($workOrderId);
                    if (!$workOrder) continue;

                    $updateData = [];
                    $allowedFields = [
                        'priority', 'status', 'start_date', 'end_date', 'notes'
                    ];

                    foreach ($allowedFields as $field) {
                        if (isset($updates[$field])) {
                            $updateData[$field] = $updates[$field];
                        }
                    }

                    if (!empty($updateData)) {
                        $updateData['updated_by'] = $this->user['id'];
                        $updateData['updated_at'] = date('Y-m-d H:i:s');

                        $this->db->update('work_orders', $updateData, ['id' => $workOrderId]);
                        $updateCount++;
                    }
                }

                $this->db->commit();

                // Log bulk update
                $this->logActivity('bulk_work_order_update', 'work_orders', null, "Bulk updated {$updateCount} work orders");

                Response::json([
                    'success' => true,
                    'updated_count' => $updateCount,
                    'message' => "{$updateCount} work orders updated successfully"
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

    private function getBOMById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM bills_of_materials WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function getWorkOrderById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM work_orders WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function getProductionLineById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM production_lines WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function generateWorkOrderNumber()
    {
        return 'WO-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    private function updateWorkOrderQuantities($workOrderId, $quantity)
    {
        // Get current quantities
        $workOrder = $this->getWorkOrderById($workOrderId);
        if (!$workOrder) return;

        $currentProduced = (int)$workOrder['quantity_produced'];
        $newProduced = $currentProduced + $quantity;

        // Update work order
        $this->db->update('work_orders', [
            'quantity_produced' => $newProduced,
            'updated_by' => $this->user['id'],
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $workOrderId]);

        // Check if work order should be completed
        if ($newProduced >= (int)$workOrder['quantity_planned']) {
            $this->db->update('work_orders', [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'updated_by' => $this->user['id'],
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $workOrderId]);
        }
    }

    private function getBOMsCount($filters)
    {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['product']) {
            $where[] = "product_id = ?";
            $params[] = $filters['product'];
        }

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM bills_of_materials WHERE $whereClause", $params);
    }

    private function getWorkOrdersCount($filters)
    {
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

        return $this->db->queryValue("SELECT COUNT(*) FROM work_orders wo LEFT JOIN bills_of_materials bom ON wo.bom_id = bom.id WHERE $whereClause", $params);
    }

    private function logActivity($action, $table, $recordId, $description)
    {
        $this->db->insert('manufacturing_activities', [
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
