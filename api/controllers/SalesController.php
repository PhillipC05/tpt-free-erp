<?php

namespace TPT\ERP\Api\Controllers;

use TPT\ERP\Core\Response;
use TPT\ERP\Core\Request;
use TPT\ERP\Core\Database;
use TPT\ERP\Modules\Sales;

/**
 * Sales API Controller
 * Handles all sales and CRM-related API endpoints
 */
class SalesController extends BaseController
{
    private $sales;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->sales = new Sales();
        $this->db = Database::getInstance();
    }

    /**
     * Get sales overview
     * GET /api/sales/overview
     */
    public function getOverview()
    {
        try {
            $this->requirePermission('sales.view');

            $data = [
                'sales_overview' => $this->sales->getSalesOverview(),
                'sales_pipeline' => $this->sales->getSalesPipeline(),
                'revenue_analytics' => $this->sales->getRevenueAnalytics(),
                'customer_insights' => $this->sales->getCustomerInsights(),
                'sales_targets' => $this->sales->getSalesTargets(),
                'recent_activities' => $this->sales->getRecentActivities(),
                'upcoming_tasks' => $this->sales->getUpcomingTasks(),
                'sales_forecast' => $this->sales->getSalesForecast()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get customers with filtering
     * GET /api/sales/customers
     */
    public function getCustomers()
    {
        try {
            $this->requirePermission('sales.customers.view');

            $filters = [
                'segment' => $_GET['segment'] ?? null,
                'status' => $_GET['status'] ?? null,
                'value_tier' => $_GET['value_tier'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $customers = $this->sales->getCustomers($filters);
            $total = $this->getCustomersCount($filters);

            Response::json([
                'customers' => $customers,
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
     * Create new customer
     * POST /api/sales/customers
     */
    public function createCustomer()
    {
        try {
            $this->requirePermission('sales.customers.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['customer_name', 'email'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                Response::error('Customer with this email already exists', 400);
                return;
            }

            $customerData = [
                'customer_name' => trim($data['customer_name']),
                'email' => trim($data['email']),
                'phone' => $data['phone'] ?? '',
                'address' => $data['address'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'postal_code' => $data['postal_code'] ?? '',
                'country' => $data['country'] ?? '',
                'customer_segment' => $data['customer_segment'] ?? 'prospect',
                'customer_score' => (int)($data['customer_score'] ?? 50),
                'status' => $data['status'] ?? 'active',
                'industry' => $data['industry'] ?? '',
                'company_size' => $data['company_size'] ?? '',
                'website' => $data['website'] ?? '',
                'notes' => $data['notes'] ?? '',
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_date' => date('Y-m-d H:i:s')
            ];

            $customerId = $this->db->insert('customers', $customerData);

            // Log the creation
            $this->logActivity('customer_created', 'customers', $customerId, "Customer '{$customerData['customer_name']}' created");

            Response::json([
                'success' => true,
                'customer_id' => $customerId,
                'message' => 'Customer created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Update customer
     * PUT /api/sales/customers/{id}
     */
    public function updateCustomer($id)
    {
        try {
            $this->requirePermission('sales.customers.update');

            $data = Request::getJsonBody();

            // Check if customer exists and belongs to company
            $customer = $this->getCustomerById($id);
            if (!$customer) {
                Response::error('Customer not found', 404);
                return;
            }

            // Check email uniqueness if changed
            if (isset($data['email']) && $data['email'] !== $customer['email'] && $this->emailExists($data['email'])) {
                Response::error('Customer with this email already exists', 400);
                return;
            }

            $updateData = [];
            $allowedFields = [
                'customer_name', 'email', 'phone', 'address', 'city', 'state',
                'postal_code', 'country', 'customer_segment', 'customer_score',
                'status', 'industry', 'company_size', 'website', 'notes'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_by'] = $this->user['id'];
                $updateData['updated_date'] = date('Y-m-d H:i:s');

                $this->db->update('customers', $updateData, ['id' => $id]);

                // Log the update
                $this->logActivity('customer_updated', 'customers', $id, "Customer '{$customer['customer_name']}' updated");
            }

            Response::json([
                'success' => true,
                'message' => 'Customer updated successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Delete customer
     * DELETE /api/sales/customers/{id}
     */
    public function deleteCustomer($id)
    {
        try {
            $this->requirePermission('sales.customers.delete');

            $customer = $this->getCustomerById($id);
            if (!$customer) {
                Response::error('Customer not found', 404);
                return;
            }

            // Check if customer has orders
            if ($this->hasOrders($id)) {
                Response::error('Cannot delete customer with existing orders', 400);
                return;
            }

            $this->db->delete('customers', ['id' => $id]);

            // Log the deletion
            $this->logActivity('customer_deleted', 'customers', $id, "Customer '{$customer['customer_name']}' deleted");

            Response::json([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get leads
     * GET /api/sales/leads
     */
    public function getLeads()
    {
        try {
            $this->requirePermission('sales.leads.view');

            $leads = $this->sales->getLeads();

            Response::json(['leads' => $leads]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create new lead
     * POST /api/sales/leads
     */
    public function createLead()
    {
        try {
            $this->requirePermission('sales.leads.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['lead_name', 'email'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $leadData = [
                'lead_name' => trim($data['lead_name']),
                'email' => trim($data['email']),
                'phone' => $data['phone'] ?? '',
                'company' => $data['company'] ?? '',
                'lead_source_id' => $data['lead_source_id'] ?? null,
                'lead_score' => (int)($data['lead_score'] ?? 50),
                'lead_status' => $data['lead_status'] ?? 'new',
                'estimated_value' => (float)($data['estimated_value'] ?? 0),
                'notes' => $data['notes'] ?? '',
                'assigned_to' => $data['assigned_to'] ?? $this->user['id'],
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_date' => date('Y-m-d H:i:s')
            ];

            $leadId = $this->db->insert('leads', $leadData);

            // Log the creation
            $this->logActivity('lead_created', 'leads', $leadId, "Lead '{$leadData['lead_name']}' created");

            Response::json([
                'success' => true,
                'lead_id' => $leadId,
                'message' => 'Lead created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get opportunities
     * GET /api/sales/opportunities
     */
    public function getOpportunities()
    {
        try {
            $this->requirePermission('sales.pipeline.view');

            $opportunities = $this->sales->getOpportunities();

            Response::json(['opportunities' => $opportunities]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create new opportunity
     * POST /api/sales/opportunities
     */
    public function createOpportunity()
    {
        try {
            $this->requirePermission('sales.pipeline.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['opportunity_name', 'customer_id', 'expected_value'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Validate customer exists
            $customer = $this->getCustomerById($data['customer_id']);
            if (!$customer) {
                Response::error('Customer not found', 400);
                return;
            }

            $opportunityData = [
                'opportunity_name' => trim($data['opportunity_name']),
                'customer_id' => $data['customer_id'],
                'lead_id' => $data['lead_id'] ?? null,
                'stage_id' => $data['stage_id'] ?? 1, // Default to first stage
                'expected_value' => (float)$data['expected_value'],
                'probability_percentage' => (int)($data['probability_percentage'] ?? 50),
                'expected_close_date' => $data['expected_close_date'] ?? null,
                'description' => $data['description'] ?? '',
                'assigned_to' => $data['assigned_to'] ?? $this->user['id'],
                'status' => 'active',
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_date' => date('Y-m-d H:i:s'),
                'last_updated' => date('Y-m-d H:i:s')
            ];

            $opportunityId = $this->db->insert('opportunities', $opportunityData);

            // Log the creation
            $this->logActivity('opportunity_created', 'opportunities', $opportunityId, "Opportunity '{$opportunityData['opportunity_name']}' created");

            Response::json([
                'success' => true,
                'opportunity_id' => $opportunityId,
                'message' => 'Opportunity created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Update opportunity stage
     * PUT /api/sales/opportunities/{id}/stage
     */
    public function updateOpportunityStage($id)
    {
        try {
            $this->requirePermission('sales.pipeline.update');

            $data = Request::getJsonBody();

            if (!isset($data['stage_id'])) {
                Response::error('Stage ID is required', 400);
                return;
            }

            // Check if opportunity exists
            $opportunity = $this->getOpportunityById($id);
            if (!$opportunity) {
                Response::error('Opportunity not found', 404);
                return;
            }

            // Record stage change for analytics
            $this->db->insert('pipeline_conversions', [
                'opportunity_id' => $id,
                'from_stage_id' => $opportunity['stage_id'],
                'to_stage_id' => $data['stage_id'],
                'stage_entry_date' => $opportunity['created_date'],
                'stage_exit_date' => date('Y-m-d H:i:s'),
                'company_id' => $this->user['company_id']
            ]);

            // Update opportunity
            $this->db->update('opportunities', [
                'stage_id' => $data['stage_id'],
                'last_updated' => date('Y-m-d H:i:s'),
                'updated_by' => $this->user['id']
            ], ['id' => $id]);

            // Log the stage change
            $this->logActivity('opportunity_stage_changed', 'opportunities', $id, "Opportunity stage updated");

            Response::json([
                'success' => true,
                'message' => 'Opportunity stage updated successfully'
            ]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get sales orders
     * GET /api/sales/orders
     */
    public function getOrders()
    {
        try {
            $this->requirePermission('sales.orders.view');

            $orders = $this->sales->getSalesOrders();

            Response::json(['orders' => $orders]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create sales order
     * POST /api/sales/orders
     */
    public function createOrder()
    {
        try {
            $this->requirePermission('sales.orders.create');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['customer_id', 'order_items'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            // Validate customer exists
            $customer = $this->getCustomerById($data['customer_id']);
            if (!$customer) {
                Response::error('Customer not found', 400);
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
                // Create order
                $orderData = [
                    'order_number' => $this->generateOrderNumber(),
                    'customer_id' => $data['customer_id'],
                    'order_date' => date('Y-m-d H:i:s'),
                    'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                    'total_amount' => $totalAmount,
                    'status' => 'draft',
                    'notes' => $data['notes'] ?? '',
                    'sales_rep_id' => $data['sales_rep_id'] ?? $this->user['id'],
                    'company_id' => $this->user['company_id'],
                    'created_by' => $this->user['id'],
                    'created_date' => date('Y-m-d H:i:s')
                ];

                $orderId = $this->db->insert('sales_orders', $orderData);

                // Create order items
                foreach ($orderItems as $item) {
                    $this->db->insert('sales_order_items', [
                        'sales_order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'quantity' => (int)$item['quantity'],
                        'unit_price' => (float)$item['unit_price'],
                        'discount_percentage' => (float)($item['discount_percentage'] ?? 0),
                        'line_total' => (float)$item['quantity'] * (float)$item['unit_price'] * (1 - (float)($item['discount_percentage'] ?? 0) / 100),
                        'company_id' => $this->user['company_id']
                    ]);
                }

                $this->db->commit();

                // Log the creation
                $this->logActivity('order_created', 'sales_orders', $orderId, "Sales order '{$orderData['order_number']}' created");

                Response::json([
                    'success' => true,
                    'order_id' => $orderId,
                    'order_number' => $orderData['order_number'],
                    'message' => 'Sales order created successfully'
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
     * Get pipeline stages
     * GET /api/sales/pipeline/stages
     */
    public function getPipelineStages()
    {
        try {
            $this->requirePermission('sales.pipeline.view');

            $stages = $this->sales->getPipelineStages();

            Response::json(['stages' => $stages]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get customer segments
     * GET /api/sales/customer-segments
     */
    public function getCustomerSegments()
    {
        try {
            $this->requirePermission('sales.customers.view');

            $segments = $this->sales->getCustomerSegments();

            Response::json(['segments' => $segments]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get lead sources
     * GET /api/sales/lead-sources
     */
    public function getLeadSources()
    {
        try {
            $this->requirePermission('sales.leads.view');

            $sources = $this->sales->getLeadSources();

            Response::json(['sources' => $sources]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get sales analytics
     * GET /api/sales/analytics
     */
    public function getAnalytics()
    {
        try {
            $this->requirePermission('sales.analytics.view');

            $data = [
                'sales_performance' => $this->sales->getSalesPerformance(),
                'customer_analytics' => $this->sales->getCustomerAnalytics(),
                'conversion_rates' => $this->sales->getConversionRates(),
                'sales_trends' => $this->sales->getSalesTrends(),
                'pipeline_velocity' => $this->sales->getPipelineVelocity()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Bulk update customers
     * POST /api/sales/customers/bulk-update
     */
    public function bulkUpdateCustomers()
    {
        try {
            $this->requirePermission('sales.customers.update');

            $data = Request::getJsonBody();

            if (!isset($data['customer_ids']) || !is_array($data['customer_ids'])) {
                Response::error('Customer IDs array is required', 400);
                return;
            }

            if (empty($data['updates'])) {
                Response::error('Updates object is required', 400);
                return;
            }

            $customerIds = $data['customer_ids'];
            $updates = $data['updates'];

            // Start transaction
            $this->db->beginTransaction();

            try {
                $updateCount = 0;

                foreach ($customerIds as $customerId) {
                    $customer = $this->getCustomerById($customerId);
                    if (!$customer) continue;

                    $updateData = [];
                    $allowedFields = [
                        'customer_segment', 'status', 'customer_score',
                        'industry', 'company_size', 'website', 'notes'
                    ];

                    foreach ($allowedFields as $field) {
                        if (isset($updates[$field])) {
                            $updateData[$field] = $updates[$field];
                        }
                    }

                    if (!empty($updateData)) {
                        $updateData['updated_by'] = $this->user['id'];
                        $updateData['updated_date'] = date('Y-m-d H:i:s');

                        $this->db->update('customers', $updateData, ['id' => $customerId]);
                        $updateCount++;
                    }
                }

                $this->db->commit();

                // Log bulk update
                $this->logActivity('bulk_customer_update', 'customers', null, "Bulk updated {$updateCount} customers");

                Response::json([
                    'success' => true,
                    'updated_count' => $updateCount,
                    'message' => "{$updateCount} customers updated successfully"
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

    private function getCustomerById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM customers WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function getOpportunityById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM opportunities WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function emailExists($email)
    {
        $count = $this->db->queryValue(
            "SELECT COUNT(*) FROM customers WHERE email = ? AND company_id = ?",
            [$email, $this->user['company_id']]
        );
        return $count > 0;
    }

    private function hasOrders($customerId)
    {
        $count = $this->db->queryValue(
            "SELECT COUNT(*) FROM sales_orders WHERE customer_id = ?",
            [$customerId]
        );
        return $count > 0;
    }

    private function generateOrderNumber()
    {
        return 'SO-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    private function getCustomersCount($filters)
    {
        $where = ["c.company_id = ?"];
        $params = [$this->user['company_id']];

        // Add same filters as getCustomers method
        if (isset($filters['segment'])) {
            $where[] = "c.customer_segment = ?";
            $params[] = $filters['segment'];
        }

        if (isset($filters['status'])) {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['value_tier'])) {
            switch ($filters['value_tier']) {
                case 'high':
                    $where[] = "(SELECT SUM(so.total_amount) FROM sales_orders so WHERE so.customer_id = c.id) >= 10000";
                    break;
                case 'medium':
                    $where[] = "(SELECT SUM(so.total_amount) FROM sales_orders so WHERE so.customer_id = c.id) BETWEEN 1000 AND 9999";
                    break;
                case 'low':
                    $where[] = "(SELECT SUM(so.total_amount) FROM sales_orders so WHERE so.customer_id = c.id) < 1000";
                    break;
            }
        }

        if (isset($filters['date_from'])) {
            $where[] = "c.created_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (isset($filters['date_to'])) {
            $where[] = "c.created_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (isset($filters['search'])) {
            $where[] = "(c.customer_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM customers c WHERE $whereClause", $params);
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
