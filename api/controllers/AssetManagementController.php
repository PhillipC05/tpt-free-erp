<?php
/**
 * TPT Free ERP - Asset Management API Controller
 * Complete REST API for asset tracking, maintenance, depreciation, and lifecycle management
 */

class AssetManagementController extends BaseController {
    private $db;
    private $user;
    private $assetManagement;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->assetManagement = new AssetManagement();
    }

    // ============================================================================
    // DASHBOARD ENDPOINTS
    // ============================================================================

    /**
     * Get asset management overview
     */
    public function getOverview() {
        $this->requirePermission('assets.view');

        try {
            $overview = $this->assetManagement->getAssetOverview();
            $this->jsonResponse($overview);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get asset status summary
     */
    public function getAssetStatus() {
        $this->requirePermission('assets.view');

        try {
            $status = $this->assetManagement->getAssetStatus();
            $this->jsonResponse($status);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get maintenance alerts
     */
    public function getMaintenanceAlerts() {
        $this->requirePermission('assets.view');

        try {
            $alerts = $this->assetManagement->getMaintenanceAlerts();
            $this->jsonResponse($alerts);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get compliance status
     */
    public function getComplianceStatus() {
        $this->requirePermission('assets.view');

        try {
            $compliance = $this->assetManagement->getComplianceStatus();
            $this->jsonResponse($compliance);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get asset analytics
     */
    public function getAssetAnalytics() {
        $this->requirePermission('assets.analytics.view');

        try {
            $analytics = $this->assetManagement->getAssetAnalytics();
            $this->jsonResponse($analytics);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // ASSET MANAGEMENT ENDPOINTS
    // ============================================================================

    /**
     * Get assets with filtering and pagination
     */
    public function getAssets() {
        $this->requirePermission('assets.view');

        try {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'category' => $_GET['category'] ?? null,
                'location' => $_GET['location'] ?? null,
                'department' => $_GET['department'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null
            ];

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 50);

            $assets = $this->assetManagement->getAssets($filters);
            $total = count($assets);
            $pages = ceil($total / $limit);
            $offset = ($page - 1) * $limit;

            $paginatedAssets = array_slice($assets, $offset, $limit);

            $this->jsonResponse([
                'assets' => $paginatedAssets,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => $pages
                ]
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get single asset by ID
     */
    public function getAsset($id) {
        $this->requirePermission('assets.view');

        try {
            $asset = $this->db->querySingle("
                SELECT
                    a.*,
                    ac.category_name,
                    al.location_name,
                    ad.department_name,
                    u.first_name as assigned_to_first,
                    u.last_name as assigned_to_last,
                    TIMESTAMPDIFF(YEAR, a.purchase_date, CURDATE()) as asset_age_years,
                    TIMESTAMPDIFF(DAY, CURDATE(), a.next_maintenance_date) as days_until_maintenance,
                    TIMESTAMPDIFF(DAY, CURDATE(), a.warranty_expiry) as days_until_warranty_expiry
                FROM assets a
                LEFT JOIN asset_categories ac ON a.category_id = ac.id
                LEFT JOIN asset_locations al ON a.location_id = al.id
                LEFT JOIN asset_departments ad ON a.department_id = ad.id
                LEFT JOIN users u ON a.assigned_to = u.id
                WHERE a.id = ? AND a.company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$asset) {
                $this->errorResponse('Asset not found', 404);
                return;
            }

            $this->jsonResponse($asset);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create new asset
     */
    public function createAsset() {
        $this->requirePermission('assets.manage');

        try {
            $data = $this->getJsonInput();

            // Validate required fields
            $required = ['asset_name', 'asset_tag', 'category_id', 'purchase_value', 'purchase_date'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            // Prepare asset data
            $assetData = [
                'company_id' => $this->user['company_id'],
                'asset_name' => trim($data['asset_name']),
                'asset_tag' => trim($data['asset_tag']),
                'category_id' => $data['category_id'],
                'description' => $data['description'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'model' => $data['model'] ?? null,
                'manufacturer' => $data['manufacturer'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'purchase_value' => (float)$data['purchase_value'],
                'current_value' => (float)$data['purchase_value'],
                'location_id' => $data['location_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'status' => $data['status'] ?? 'active',
                'warranty_expiry' => $data['warranty_expiry'] ?? null,
                'insurance_expiry' => $data['insurance_expiry'] ?? null,
                'depreciation_method' => $data['depreciation_method'] ?? null,
                'useful_life_years' => $data['useful_life_years'] ?? null,
                'salvage_value' => $data['salvage_value'] ?? 0,
                'next_maintenance_date' => $data['next_maintenance_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $assetId = $this->db->insert('assets', $assetData);

            // Log the creation
            $this->logActivity('asset_created', 'Asset created', $assetId, [
                'asset_name' => $assetData['asset_name'],
                'asset_tag' => $assetData['asset_tag']
            ]);

            $this->jsonResponse([
                'success' => true,
                'asset_id' => $assetId,
                'message' => 'Asset created successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update asset
     */
    public function updateAsset($id) {
        $this->requirePermission('assets.manage');

        try {
            $data = $this->getJsonInput();

            // Check if asset exists and belongs to company
            $existing = $this->db->querySingle("
                SELECT id FROM assets WHERE id = ? AND company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$existing) {
                $this->errorResponse('Asset not found', 404);
                return;
            }

            // Prepare update data
            $updateData = [];
            $allowedFields = [
                'asset_name', 'asset_tag', 'category_id', 'description', 'serial_number',
                'model', 'manufacturer', 'purchase_value', 'current_value', 'location_id',
                'department_id', 'assigned_to', 'status', 'warranty_expiry', 'insurance_expiry',
                'depreciation_method', 'useful_life_years', 'salvage_value', 'next_maintenance_date',
                'notes'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $this->db->update('assets', $updateData, "id = ?", [$id]);

                // Log the update
                $this->logActivity('asset_updated', 'Asset updated', $id, $updateData);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Asset updated successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete asset
     */
    public function deleteAsset($id) {
        $this->requirePermission('assets.manage');

        try {
            // Check if asset exists and belongs to company
            $asset = $this->db->querySingle("
                SELECT asset_name, asset_tag FROM assets WHERE id = ? AND company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$asset) {
                $this->errorResponse('Asset not found', 404);
                return;
            }

            // Soft delete by updating status
            $this->db->update('assets', [
                'status' => 'retired',
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = ?", [$id]);

            // Log the deletion
            $this->logActivity('asset_deleted', 'Asset deleted', $id, [
                'asset_name' => $asset['asset_name'],
                'asset_tag' => $asset['asset_tag']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Asset deleted successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get asset categories
     */
    public function getAssetCategories() {
        $this->requirePermission('assets.view');

        try {
            $categories = $this->assetManagement->getAssetCategories();
            $this->jsonResponse($categories);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get asset locations
     */
    public function getAssetLocations() {
        $this->requirePermission('assets.view');

        try {
            $locations = $this->assetManagement->getAssetLocations();
            $this->jsonResponse($locations);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get asset departments
     */
    public function getAssetDepartments() {
        $this->requirePermission('assets.view');

        try {
            $departments = $this->assetManagement->getAssetDepartments();
            $this->jsonResponse($departments);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // MAINTENANCE ENDPOINTS
    // ============================================================================

    /**
     * Get maintenance schedule
     */
    public function getMaintenanceSchedule() {
        $this->requirePermission('assets.maintenance.view');

        try {
            $schedule = $this->assetManagement->getMaintenanceSchedule();
            $this->jsonResponse($schedule);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get maintenance history
     */
    public function getMaintenanceHistory() {
        $this->requirePermission('assets.maintenance.view');

        try {
            $history = $this->assetManagement->getMaintenanceHistory();
            $this->jsonResponse($history);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create maintenance work order
     */
    public function createMaintenanceWorkOrder() {
        $this->requirePermission('assets.maintenance.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['asset_id', 'maintenance_type', 'scheduled_date'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            $workOrderData = [
                'asset_id' => $data['asset_id'],
                'work_order_number' => $this->generateWorkOrderNumber(),
                'maintenance_type' => $data['maintenance_type'],
                'priority' => $data['priority'] ?? 'medium',
                'description' => $data['description'] ?? null,
                'scheduled_date' => $data['scheduled_date'],
                'estimated_cost' => $data['estimated_cost'] ?? 0,
                'assigned_technician' => $data['assigned_technician'] ?? null,
                'status' => 'scheduled',
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $workOrderId = $this->db->insert('maintenance_work_orders', $workOrderData);

            $this->logActivity('maintenance_work_order_created', 'Maintenance work order created', $workOrderId);

            $this->jsonResponse([
                'success' => true,
                'work_order_id' => $workOrderId,
                'message' => 'Maintenance work order created successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update maintenance work order
     */
    public function updateMaintenanceWorkOrder($id) {
        $this->requirePermission('assets.maintenance.manage');

        try {
            $data = $this->getJsonInput();

            $updateData = [];
            $allowedFields = [
                'maintenance_type', 'priority', 'description', 'scheduled_date',
                'completed_date', 'estimated_cost', 'actual_cost', 'assigned_technician',
                'status', 'notes'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $this->db->update('maintenance_work_orders', $updateData, "id = ?", [$id]);

                $this->logActivity('maintenance_work_order_updated', 'Maintenance work order updated', $id);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Maintenance work order updated successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // DEPRECIATION ENDPOINTS
    // ============================================================================

    /**
     * Get depreciation schedule
     */
    public function getDepreciationSchedule() {
        $this->requirePermission('assets.depreciation.view');

        try {
            $schedule = $this->assetManagement->getDepreciationSchedule();
            $this->jsonResponse($schedule);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Calculate depreciation for asset
     */
    public function calculateDepreciation($assetId) {
        $this->requirePermission('assets.depreciation.manage');

        try {
            $asset = $this->db->querySingle("
                SELECT * FROM assets WHERE id = ? AND company_id = ?
            ", [$assetId, $this->user['company_id']]);

            if (!$asset) {
                $this->errorResponse('Asset not found', 404);
                return;
            }

            $depreciationAmount = $this->calculateDepreciationAmount($asset);

            $depreciationData = [
                'asset_id' => $assetId,
                'company_id' => $this->user['company_id'],
                'depreciation_date' => date('Y-m-d'),
                'depreciation_amount' => $depreciationAmount,
                'book_value' => $asset['current_value'] - $depreciationAmount,
                'fiscal_year' => date('Y'),
                'fiscal_period' => date('m'),
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $depreciationId = $this->db->insert('depreciation', $depreciationData);

            // Update asset current value
            $this->db->update('assets', [
                'current_value' => $depreciationData['book_value'],
                'accumulated_depreciation' => ($asset['accumulated_depreciation'] ?? 0) + $depreciationAmount,
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = ?", [$assetId]);

            $this->logActivity('depreciation_calculated', 'Depreciation calculated', $assetId, [
                'amount' => $depreciationAmount,
                'book_value' => $depreciationData['book_value']
            ]);

            $this->jsonResponse([
                'success' => true,
                'depreciation_id' => $depreciationId,
                'depreciation_amount' => $depreciationAmount,
                'book_value' => $depreciationData['book_value'],
                'message' => 'Depreciation calculated successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // LIFECYCLE ENDPOINTS
    // ============================================================================

    /**
     * Get asset lifecycle stages
     */
    public function getLifecycleStages($assetId) {
        $this->requirePermission('assets.lifecycle.view');

        try {
            $stages = $this->db->query("
                SELECT * FROM asset_lifecycle_stages
                WHERE asset_id = ? AND company_id = ?
                ORDER BY stage_order ASC
            ", [$assetId, $this->user['company_id']]);

            $this->jsonResponse($stages);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create asset disposal record
     */
    public function createAssetDisposal() {
        $this->requirePermission('assets.lifecycle.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['asset_id', 'disposal_date', 'disposal_method'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            $disposalData = [
                'asset_id' => $data['asset_id'],
                'company_id' => $this->user['company_id'],
                'disposal_date' => $data['disposal_date'],
                'disposal_method' => $data['disposal_method'],
                'disposal_reason' => $data['disposal_reason'] ?? null,
                'disposal_value' => $data['disposal_value'] ?? 0,
                'buyer_name' => $data['buyer_name'] ?? null,
                'disposal_costs' => $data['disposal_costs'] ?? 0,
                'net_proceeds' => ($data['disposal_value'] ?? 0) - ($data['disposal_costs'] ?? 0),
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $disposalId = $this->db->insert('asset_disposal', $disposalData);

            // Update asset status to retired
            $this->db->update('assets', [
                'status' => 'retired',
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = ?", [$data['asset_id']]);

            $this->logActivity('asset_disposed', 'Asset disposed', $data['asset_id'], [
                'disposal_method' => $data['disposal_method'],
                'net_proceeds' => $disposalData['net_proceeds']
            ]);

            $this->jsonResponse([
                'success' => true,
                'disposal_id' => $disposalId,
                'message' => 'Asset disposal recorded successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // COMPLIANCE ENDPOINTS
    // ============================================================================

    /**
     * Get compliance requirements
     */
    public function getComplianceRequirements() {
        $this->requirePermission('assets.compliance.view');

        try {
            $requirements = $this->assetManagement->getComplianceRequirements();
            $this->jsonResponse($requirements);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get insurance policies
     */
    public function getInsurancePolicies() {
        $this->requirePermission('assets.compliance.view');

        try {
            $policies = $this->assetManagement->getInsurancePolicies();
            $this->jsonResponse($policies);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create compliance audit
     */
    public function createComplianceAudit() {
        $this->requirePermission('assets.compliance.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['asset_id', 'audit_date', 'audit_type'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            $auditData = [
                'asset_id' => $data['asset_id'],
                'company_id' => $this->user['company_id'],
                'audit_date' => $data['audit_date'],
                'audit_type' => $data['audit_type'],
                'auditor_name' => $data['auditor_name'] ?? null,
                'audit_result' => $data['audit_result'] ?? 'pending',
                'findings' => $data['findings'] ?? null,
                'corrective_actions' => $data['corrective_actions'] ?? null,
                'next_audit_date' => $data['next_audit_date'] ?? null,
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $auditId = $this->db->insert('compliance_audits', $auditData);

            $this->logActivity('compliance_audit_created', 'Compliance audit created', $data['asset_id'], [
                'audit_type' => $data['audit_type'],
                'audit_result' => $data['audit_result']
            ]);

            $this->jsonResponse([
                'success' => true,
                'audit_id' => $auditId,
                'message' => 'Compliance audit created successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // ANALYTICS ENDPOINTS
    // ============================================================================

    /**
     * Get asset utilization data
     */
    public function getAssetUtilization() {
        $this->requirePermission('assets.analytics.view');

        try {
            $utilization = $this->assetManagement->getAssetUtilization();
            $this->jsonResponse($utilization);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get maintenance costs
     */
    public function getMaintenanceCosts() {
        $this->requirePermission('assets.analytics.view');

        try {
            $costs = $this->assetManagement->getMaintenanceCosts();
            $this->jsonResponse($costs);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get lifecycle costs
     */
    public function getLifecycleCosts() {
        $this->requirePermission('assets.analytics.view');

        try {
            $costs = $this->assetManagement->getLifecycleCosts();
            $this->jsonResponse($costs);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // BULK OPERATIONS ENDPOINTS
    // ============================================================================

    /**
     * Bulk update assets
     */
    public function bulkUpdateAssets() {
        $this->requirePermission('assets.manage');

        try {
            $data = $this->getJsonInput();

            if (!isset($data['asset_ids']) || !is_array($data['asset_ids'])) {
                $this->errorResponse('Asset IDs are required', 400);
                return;
            }

            if (!isset($data['updates']) || !is_array($data['updates'])) {
                $this->errorResponse('Updates data is required', 400);
                return;
            }

            $assetIds = $data['asset_ids'];
            $updates = $data['updates'];

            $updatedCount = 0;
            foreach ($assetIds as $assetId) {
                // Verify asset belongs to company
                $asset = $this->db->querySingle("
                    SELECT id FROM assets WHERE id = ? AND company_id = ?
                ", [$assetId, $this->user['company_id']]);

                if ($asset) {
                    $updates['updated_at'] = date('Y-m-d H:i:s');
                    $this->db->update('assets', $updates, "id = ?", [$assetId]);
                    $updatedCount++;
                }
            }

            $this->logActivity('assets_bulk_updated', 'Assets bulk updated', null, [
                'count' => $updatedCount,
                'updates' => $updates
            ]);

            $this->jsonResponse([
                'success' => true,
                'updated_count' => $updatedCount,
                'message' => "$updatedCount assets updated successfully"
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Bulk export assets
     */
    public function exportAssets() {
        $this->requirePermission('assets.view');

        try {
            $filters = $_GET;

            $assets = $this->assetManagement->getAssets($filters);

            // Generate CSV
            $filename = 'assets_export_' . date('Y-m-d_H-i-s') . '.csv';
            $csvContent = $this->generateAssetsCSV($assets);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');

            echo $csvContent;
            exit;

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // PRIVATE HELPER METHODS
    // ============================================================================

    private function calculateDepreciationAmount($asset) {
        if (!$asset['depreciation_method'] || !$asset['useful_life_years']) {
            return 0;
        }

        $purchaseValue = $asset['purchase_value'];
        $salvageValue = $asset['salvage_value'] ?? 0;
        $usefulLife = $asset['useful_life_years'];
        $currentValue = $asset['current_value'];

        switch ($asset['depreciation_method']) {
            case 'straight_line':
                $annualDepreciation = ($purchaseValue - $salvageValue) / $usefulLife;
                return min($annualDepreciation, $currentValue - $salvageValue);

            case 'declining_balance':
                $rate = 2 / $usefulLife; // Double declining balance
                $depreciation = $currentValue * $rate;
                return min($depreciation, $currentValue - $salvageValue);

            default:
                return 0;
        }
    }

    private function generateWorkOrderNumber() {
        $year = date('Y');
        $month = date('m');

        // Get the last work order number for this month
        $lastWorkOrder = $this->db->querySingle("
            SELECT work_order_number FROM maintenance_work_orders
            WHERE work_order_number LIKE ? AND company_id = ?
            ORDER BY id DESC LIMIT 1
        ", ["$year$month%", $this->user['company_id']]);

        if ($lastWorkOrder) {
            $lastNumber = (int)substr($lastWorkOrder['work_order_number'], -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s%s%04d', $year, $month, $nextNumber);
    }

    private function generateAssetsCSV($assets) {
        $headers = [
            'Asset Name',
            'Asset Tag',
            'Category',
            'Location',
            'Department',
            'Status',
            'Purchase Date',
            'Purchase Value',
            'Current Value',
            'Assigned To',
            'Warranty Expiry',
            'Next Maintenance'
        ];

        $csv = implode(',', array_map(function($header) {
            return '"' . str_replace('"', '""', $header) . '"';
        }, $headers)) . "\n";

        foreach ($assets as $asset) {
            $row = [
                $asset['asset_name'] ?? '',
                $asset['asset_tag'] ?? '',
                $asset['category_name'] ?? '',
                $asset['location_name'] ?? '',
                $asset['department_name'] ?? '',
                $asset['status'] ?? '',
                $asset['purchase_date'] ?? '',
                $asset['purchase_value'] ?? '',
                $asset['current_value'] ?? '',
                ($asset['assigned_to_first'] ?? '') . ' ' . ($asset['assigned_to_last'] ?? ''),
                $asset['warranty_expiry'] ?? '',
                $asset['next_maintenance_date'] ?? ''
            ];

            $csv .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        return $csv;
    }

    private function logActivity($action, $description, $assetId = null, $details = null) {
        try {
            $this->db->insert('audit_log', [
                'company_id' => $this->user['company_id'],
                'user_id' => $this->user['id'],
                'action' => $action,
                'description' => $description,
                'entity_type' => 'asset',
                'entity_id' => $assetId,
                'details' => json_encode($details),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
}
