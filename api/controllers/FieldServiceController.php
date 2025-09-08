<?php
/**
 * TPT Free ERP - Field Service API Controller
 * Complete REST API for service call management, technician scheduling, and customer service
 */

class FieldServiceController extends BaseController {
    private $db;
    private $user;
    private $fieldService;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->fieldService = new FieldService();
    }

    // ============================================================================
    // DASHBOARD ENDPOINTS
    // ============================================================================

    /**
     * Get field service overview
     */
    public function getOverview() {
        $this->requirePermission('field_service.view');

        try {
            $overview = $this->fieldService->getServiceOverview();
            $this->jsonResponse($overview);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get service calls overview
     */
    public function getServiceCallsOverview() {
        $this->requirePermission('field_service.calls.view');

        try {
            $overview = $this->fieldService->getServiceCalls([]);
            $this->jsonResponse($overview);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get technician status
     */
    public function getTechnicianStatus() {
        $this->requirePermission('field_service.view');

        try {
            $status = $this->fieldService->getTechnicianStatus();
            $this->jsonResponse($status);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get service schedule
     */
    public function getServiceSchedule() {
        $this->requirePermission('field_service.view');

        try {
            $schedule = $this->fieldService->getServiceSchedule();
            $this->jsonResponse($schedule);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get customer satisfaction metrics
     */
    public function getCustomerSatisfaction() {
        $this->requirePermission('field_service.view');

        try {
            $satisfaction = $this->fieldService->getCustomerSatisfaction();
            $this->jsonResponse($satisfaction);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get service analytics
     */
    public function getServiceAnalytics() {
        $this->requirePermission('field_service.analytics.view');

        try {
            $analytics = $this->fieldService->getServiceAnalytics();
            $this->jsonResponse($analytics);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get upcoming appointments
     */
    public function getUpcomingAppointments() {
        $this->requirePermission('field_service.view');

        try {
            $appointments = $this->fieldService->getUpcomingAppointments();
            $this->jsonResponse($appointments);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get service alerts
     */
    public function getServiceAlerts() {
        $this->requirePermission('field_service.view');

        try {
            $alerts = $this->fieldService->getServiceAlerts();
            $this->jsonResponse($alerts);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // SERVICE CALL MANAGEMENT ENDPOINTS
    // ============================================================================

    /**
     * Get service calls with filtering and pagination
     */
    public function getServiceCalls() {
        $this->requirePermission('field_service.calls.view');

        try {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'priority' => $_GET['priority'] ?? null,
                'technician' => $_GET['technician'] ?? null,
                'customer' => $_GET['customer'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'search' => $_GET['search'] ?? null
            ];

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 50);

            $serviceCalls = $this->fieldService->getServiceCalls($filters);
            $total = count($serviceCalls);
            $pages = ceil($total / $limit);
            $offset = ($page - 1) * $limit;

            $paginatedCalls = array_slice($serviceCalls, $offset, $limit);

            $this->jsonResponse([
                'service_calls' => $paginatedCalls,
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
     * Get single service call by ID
     */
    public function getServiceCall($id) {
        $this->requirePermission('field_service.calls.view');

        try {
            $serviceCall = $this->db->querySingle("
                SELECT
                    sc.*,
                    c.customer_name,
                    c.customer_phone,
                    c.customer_email,
                    c.customer_address,
                    u.first_name as technician_first,
                    u.last_name as technician_last,
                    u.phone as technician_phone,
                    u.email as technician_email,
                    st.service_name,
                    st.description as service_description,
                    TIMESTAMPDIFF(DAY, CURDATE(), sc.scheduled_date) as days_until_scheduled,
                    TIMESTAMPDIFF(MINUTE, sc.scheduled_time, sc.actual_start_time) as delay_minutes
                FROM service_calls sc
                LEFT JOIN customers c ON sc.customer_id = c.id
                LEFT JOIN users u ON sc.technician_id = u.id
                LEFT JOIN service_types st ON sc.service_type = st.id
                WHERE sc.id = ? AND sc.company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$serviceCall) {
                $this->errorResponse('Service call not found', 404);
                return;
            }

            $this->jsonResponse($serviceCall);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create new service call
     */
    public function createServiceCall() {
        $this->requirePermission('field_service.calls.manage');

        try {
            $data = $this->getJsonInput();

            // Validate required fields
            $required = ['customer_id', 'service_type', 'scheduled_date'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            // Generate service number
            $serviceNumber = $this->generateServiceNumber();

            // Prepare service call data
            $serviceCallData = [
                'company_id' => $this->user['company_id'],
                'service_number' => $serviceNumber,
                'customer_id' => $data['customer_id'],
                'service_type' => $data['service_type'],
                'technician_id' => $data['technician_id'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => $data['status'] ?? 'open',
                'scheduled_date' => $data['scheduled_date'],
                'scheduled_time' => $data['scheduled_time'] ?? null,
                'estimated_duration' => $data['estimated_duration'] ?? 60,
                'service_location' => $data['service_location'] ?? null,
                'description' => $data['description'] ?? null,
                'special_instructions' => $data['special_instructions'] ?? null,
                'estimated_labor_cost' => $data['estimated_labor_cost'] ?? 0,
                'estimated_parts_cost' => $data['estimated_parts_cost'] ?? 0,
                'contact_person' => $data['contact_person'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $serviceCallId = $this->db->insert('service_calls', $serviceCallData);

            // Log the creation
            $this->logActivity('service_call_created', 'Service call created', $serviceCallId, [
                'service_number' => $serviceNumber,
                'customer_id' => $data['customer_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'service_call_id' => $serviceCallId,
                'service_number' => $serviceNumber,
                'message' => 'Service call created successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update service call
     */
    public function updateServiceCall($id) {
        $this->requirePermission('field_service.calls.manage');

        try {
            $data = $this->getJsonInput();

            // Check if service call exists and belongs to company
            $existing = $this->db->querySingle("
                SELECT id FROM service_calls WHERE id = ? AND company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$existing) {
                $this->errorResponse('Service call not found', 404);
                return;
            }

            // Prepare update data
            $updateData = [];
            $allowedFields = [
                'customer_id', 'service_type', 'technician_id', 'priority', 'status',
                'scheduled_date', 'scheduled_time', 'actual_start_time', 'actual_end_time',
                'estimated_duration', 'actual_duration', 'service_location', 'description',
                'special_instructions', 'estimated_labor_cost', 'actual_labor_cost',
                'estimated_parts_cost', 'actual_parts_cost', 'labor_cost', 'parts_cost',
                'total_cost', 'customer_rating', 'customer_feedback', 'resolution_notes',
                'contact_person', 'contact_phone'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $this->db->update('service_calls', $updateData, "id = ?", [$id]);

                // Log the update
                $this->logActivity('service_call_updated', 'Service call updated', $id, $updateData);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Service call updated successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete service call
     */
    public function deleteServiceCall($id) {
        $this->requirePermission('field_service.calls.manage');

        try {
            // Check if service call exists and belongs to company
            $serviceCall = $this->db->querySingle("
                SELECT service_number FROM service_calls WHERE id = ? AND company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$serviceCall) {
                $this->errorResponse('Service call not found', 404);
                return;
            }

            // Soft delete by updating status
            $this->db->update('service_calls', [
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = ?", [$id]);

            // Log the deletion
            $this->logActivity('service_call_deleted', 'Service call deleted', $id, [
                'service_number' => $serviceCall['service_number']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Service call deleted successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Assign technician to service call
     */
    public function assignTechnician($id) {
        $this->requirePermission('field_service.calls.manage');

        try {
            $data = $this->getJsonInput();

            if (!isset($data['technician_id'])) {
                $this->errorResponse('Technician ID is required', 400);
                return;
            }

            // Check if service call exists and belongs to company
            $existing = $this->db->querySingle("
                SELECT id FROM service_calls WHERE id = ? AND company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$existing) {
                $this->errorResponse('Service call not found', 404);
                return;
            }

            $this->db->update('service_calls', [
                'technician_id' => $data['technician_id'],
                'status' => 'assigned',
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = ?", [$id]);

            // Log the assignment
            $this->logActivity('technician_assigned', 'Technician assigned to service call', $id, [
                'technician_id' => $data['technician_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Technician assigned successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update service call status
     */
    public function updateServiceStatus($id) {
        $this->requirePermission('field_service.calls.manage');

        try {
            $data = $this->getJsonInput();

            if (!isset($data['status'])) {
                $this->errorResponse('Status is required', 400);
                return;
            }

            // Check if service call exists and belongs to company
            $existing = $this->db->querySingle("
                SELECT id FROM service_calls WHERE id = ? AND company_id = ?
            ", [$id, $this->user['company_id']]);

            if (!$existing) {
                $this->errorResponse('Service call not found', 404);
                return;
            }

            $updateData = [
                'status' => $data['status'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Add timestamps based on status
            if ($data['status'] === 'in_progress' && !isset($existing['actual_start_time'])) {
                $updateData['actual_start_time'] = date('H:i:s');
            } elseif ($data['status'] === 'completed' && !isset($existing['actual_end_time'])) {
                $updateData['actual_end_time'] = date('H:i:s');
                if (isset($existing['actual_start_time'])) {
                    $start = strtotime($existing['actual_start_time']);
                    $end = strtotime($updateData['actual_end_time']);
                    $updateData['actual_duration'] = round(($end - $start) / 60, 2); // minutes
                }
            }

            $this->db->update('service_calls', $updateData, "id = ?", [$id]);

            // Log the status update
            $this->logActivity('service_status_updated', 'Service call status updated', $id, [
                'old_status' => $existing['status'],
                'new_status' => $data['status']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Service call status updated successfully'
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // TECHNICIAN SCHEDULING ENDPOINTS
    // ============================================================================

    /**
     * Get technician schedule
     */
    public function getTechnicianSchedule() {
        $this->requirePermission('field_service.scheduling.view');

        try {
            $schedule = $this->fieldService->getTechnicianSchedule();
            $this->jsonResponse($schedule);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get technician availability
     */
    public function getTechnicianAvailability() {
        $this->requirePermission('field_service.scheduling.view');

        try {
            $availability = $this->fieldService->getTechnicianAvailability();
            $this->jsonResponse($availability);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get workload distribution
     */
    public function getWorkloadDistribution() {
        $this->requirePermission('field_service.scheduling.view');

        try {
            $workload = $this->fieldService->getWorkloadDistribution();
            $this->jsonResponse($workload);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get skill matrix
     */
    public function getSkillMatrix() {
        $this->requirePermission('field_service.scheduling.view');

        try {
            $skills = $this->fieldService->getSkillMatrix();
            $this->jsonResponse($skills);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get route optimization
     */
    public function getRouteOptimization() {
        $this->requirePermission('field_service.scheduling.view');

        try {
            $routes = $this->fieldService->getRouteOptimization();
            $this->jsonResponse($routes);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get schedule conflicts
     */
    public function getScheduleConflicts() {
        $this->requirePermission('field_service.scheduling.view');

        try {
            $conflicts = $this->fieldService->getScheduleConflicts();
            $this->jsonResponse($conflicts);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get scheduling analytics
     */
    public function getSchedulingAnalytics() {
        $this->requirePermission('field_service.analytics.view');

        try {
            $analytics = $this->fieldService->getSchedulingAnalytics();
            $this->jsonResponse($analytics);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // CUSTOMER COMMUNICATION ENDPOINTS
    // ============================================================================

    /**
     * Get communication history
     */
    public function getCommunicationHistory() {
        $this->requirePermission('field_service.communication.view');

        try {
            $history = $this->fieldService->getCommunicationHistory();
            $this->jsonResponse($history);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get customer feedback
     */
    public function getCustomerFeedback() {
        $this->requirePermission('field_service.communication.view');

        try {
            $feedback = $this->fieldService->getCustomerFeedback();
            $this->jsonResponse($feedback);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get appointment reminders
     */
    public function getAppointmentReminders() {
        $this->requirePermission('field_service.communication.view');

        try {
            $reminders = $this->fieldService->getAppointmentReminders();
            $this->jsonResponse($reminders);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Send communication to customer
     */
    public function sendCommunication() {
        $this->requirePermission('field_service.communication.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['customer_id', 'communication_type', 'subject', 'message'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            $communicationData = [
                'company_id' => $this->user['company_id'],
                'customer_id' => $data['customer_id'],
                'service_call_id' => $data['service_call_id'] ?? null,
                'technician_id' => $data['technician_id'] ?? $this->user['id'],
                'communication_type' => $data['communication_type'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'sent_at' => date('Y-m-d H:i:s'),
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $communicationId = $this->db->insert('communication_history', $communicationData);

            // Log the communication
            $this->logActivity('communication_sent', 'Communication sent to customer', $communicationId, [
                'communication_type' => $data['communication_type'],
                'customer_id' => $data['customer_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'communication_id' => $communicationId,
                'message' => 'Communication sent successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // SERVICE HISTORY ENDPOINTS
    // ============================================================================

    /**
     * Get service history
     */
    public function getServiceHistory() {
        $this->requirePermission('field_service.history.view');

        try {
            $history = $this->fieldService->getServiceHistory();
            $this->jsonResponse($history);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get equipment history
     */
    public function getEquipmentHistory() {
        $this->requirePermission('field_service.history.view');

        try {
            $history = $this->fieldService->getEquipmentHistory();
            $this->jsonResponse($history);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get parts usage history
     */
    public function getPartsUsage() {
        $this->requirePermission('field_service.history.view');

        try {
            $usage = $this->fieldService->getPartsUsage();
            $this->jsonResponse($usage);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // PARTS MANAGEMENT ENDPOINTS
    // ============================================================================

    /**
     * Get parts inventory
     */
    public function getPartsInventory() {
        $this->requirePermission('field_service.parts.view');

        try {
            $inventory = $this->fieldService->getPartsInventory();
            $this->jsonResponse($inventory);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get parts orders
     */
    public function getPartsOrders() {
        $this->requirePermission('field_service.parts.view');

        try {
            $orders = $this->fieldService->getPartsOrders();
            $this->jsonResponse($orders);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create parts order
     */
    public function createPartsOrder() {
        $this->requirePermission('field_service.parts.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['part_id', 'quantity', 'service_call_id'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            $orderData = [
                'company_id' => $this->user['company_id'],
                'service_call_id' => $data['service_call_id'],
                'part_id' => $data['part_id'],
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? 0,
                'total_cost' => ($data['quantity'] ?? 0) * ($data['unit_cost'] ?? 0),
                'order_status' => $data['order_status'] ?? 'pending',
                'ordered_by' => $this->user['id'],
                'ordered_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $orderId = $this->db->insert('parts_orders', $orderData);

            // Log the order
            $this->logActivity('parts_order_created', 'Parts order created', $orderId, [
                'part_id' => $data['part_id'],
                'quantity' => $data['quantity']
            ]);

            $this->jsonResponse([
                'success' => true,
                'order_id' => $orderId,
                'message' => 'Parts order created successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // SERVICE CONTRACTS ENDPOINTS
    // ============================================================================

    /**
     * Get service contracts
     */
    public function getServiceContracts() {
        $this->requirePermission('field_service.contracts.view');

        try {
            $contracts = $this->fieldService->getServiceContracts();
            $this->jsonResponse($contracts);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create service contract
     */
    public function createServiceContract() {
        $this->requirePermission('field_service.contracts.manage');

        try {
            $data = $this->getJsonInput();

            $required = ['customer_id', 'contract_type', 'start_date', 'end_date'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->errorResponse("Field '$field' is required", 400);
                    return;
                }
            }

            $contractData = [
                'company_id' => $this->user['company_id'],
                'customer_id' => $data['customer_id'],
                'contract_number' => $this->generateContractNumber(),
                'contract_type' => $data['contract_type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'billing_frequency' => $data['billing_frequency'] ?? 'monthly',
                'contract_value' => $data['contract_value'] ?? 0,
                'description' => $data['description'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'status' => $data['status'] ?? 'active',
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $contractId = $this->db->insert('service_contracts', $contractData);

            // Log the contract creation
            $this->logActivity('service_contract_created', 'Service contract created', $contractId, [
                'contract_number' => $contractData['contract_number'],
                'customer_id' => $data['customer_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'contract_id' => $contractId,
                'contract_number' => $contractData['contract_number'],
                'message' => 'Service contract created successfully'
            ], 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // ANALYTICS ENDPOINTS
    // ============================================================================

    /**
     * Get service performance metrics
     */
    public function getServicePerformance() {
        $this->requirePermission('field_service.analytics.view');

        try {
            $performance = $this->fieldService->getServicePerformance();
            $this->jsonResponse($performance);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get technician productivity
     */
    public function getTechnicianProductivity() {
        $this->requirePermission('field_service.analytics.view');

        try {
            $productivity = $this->fieldService->getTechnicianProductivity();
            $this->jsonResponse($productivity);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get service efficiency metrics
     */
    public function getServiceEfficiency() {
        $this->requirePermission('field_service.analytics.view');

        try {
            $efficiency = $this->fieldService->getServiceEfficiency();
            $this->jsonResponse($efficiency);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get cost analysis
     */
    public function getCostAnalysis() {
        $this->requirePermission('field_service.analytics.view');

        try {
            $costs = $this->fieldService->getCostAnalysis();
            $this->jsonResponse($costs);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // BULK OPERATIONS ENDPOINTS
    // ============================================================================

    /**
     * Bulk update service calls
     */
    public function bulkUpdateServiceCalls() {
        $this->requirePermission('field_service.calls.manage');

        try {
            $data = $this->getJsonInput();

            if (!isset($data['service_call_ids']) || !is_array($data['service_call_ids'])) {
                $this->errorResponse('Service call IDs are required', 400);
                return;
            }

            if (!isset($data['updates']) || !is_array($data['updates'])) {
                $this->errorResponse('Updates data is required', 400);
                return;
            }

            $serviceCallIds = $data['service_call_ids'];
            $updates = $data['updates'];

            $updatedCount = 0;
            foreach ($serviceCallIds as $serviceCallId) {
                // Verify service call belongs to company
                $serviceCall = $this->db->querySingle("
                    SELECT id FROM service_calls WHERE id = ? AND company_id = ?
                ", [$serviceCallId, $this->user['company_id']]);

                if ($serviceCall) {
                    $updates['updated_at'] = date('Y-m-d H:i:s');
                    $this->db->update('service_calls', $updates, "id = ?", [$serviceCallId]);
                    $updatedCount++;
                }
            }

            $this->logActivity('service_calls_bulk_updated', 'Service calls bulk updated', null, [
                'count' => $updatedCount,
                'updates' => $updates
            ]);

            $this->jsonResponse([
                'success' => true,
                'updated_count' => $updatedCount,
                'message' => "$updatedCount service calls updated successfully"
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Bulk assign technicians
     */
    public function bulkAssignTechnicians() {
        $this->requirePermission('field_service.calls.manage');

        try {
            $data = $this->getJsonInput();

            if (!isset($data['service_call_ids']) || !is_array($data['service_call_ids'])) {
                $this->errorResponse('Service call IDs are required', 400);
                return;
            }

            if (!isset($data['technician_id'])) {
                $this->errorResponse('Technician ID is required', 400);
                return;
            }

            $serviceCallIds = $data['service_call_ids'];
            $technicianId = $data['technician_id'];

            $assignedCount = 0;
            foreach ($serviceCallIds as $serviceCallId) {
                // Verify service call belongs to company
                $serviceCall = $this->db->querySingle("
                    SELECT id FROM service_calls WHERE id = ? AND company_id = ?
                ", [$serviceCallId, $this->user['company_id']]);

                if ($serviceCall) {
                    $this->db->update('service_calls', [
                        'technician_id' => $technicianId,
                        'status' => 'assigned',
                        'updated_at' => date('Y-m-d H:i:s')
                    ], "id = ?", [$serviceCallId]);
                    $assignedCount++;
                }
            }

            $this->logActivity('technicians_bulk_assigned', 'Technicians bulk assigned', null, [
                'count' => $assignedCount,
                'technician_id' => $technicianId
            ]);

            $this->jsonResponse([
                'success' => true,
                'assigned_count' => $assignedCount,
                'message' => "$assignedCount service calls assigned to technician successfully"
            ]);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // UTILITY ENDPOINTS
    // ============================================================================

    /**
     * Get service types
     */
    public function getServiceTypes() {
        $this->requirePermission('field_service.view');

        try {
            $types = $this->fieldService->getServiceTypes();
            $this->jsonResponse($types);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get technicians
     */
    public function getTechnicians() {
        $this->requirePermission('field_service.view');

        try {
            $technicians = $this->fieldService->getTechnicians();
            $this->jsonResponse($technicians);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get customers
     */
    public function getCustomers() {
        $this->requirePermission('field_service.view');

        try {
            $customers = $this->fieldService->getCustomers();
            $this->jsonResponse($customers);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // PRIVATE HELPER METHODS
    // ============================================================================

    private function generateServiceNumber() {
        $year = date('Y');
        $month = date('m');

        // Get the last service number for this month
        $lastService = $this->db->querySingle("
            SELECT service_number FROM service_calls
            WHERE service_number LIKE ? AND company_id = ?
            ORDER BY id DESC LIMIT 1
        ", ["SVC-$year$month%", $this->user['company_id']]);

        if ($lastService) {
            $lastNumber = (int)substr($lastService['service_number'], -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('SVC-%s%s%04d', $year, $month, $nextNumber);
    }

    private function generateContractNumber() {
        $year = date('Y');

        // Get the last contract number for this year
        $lastContract = $this->db->querySingle("
            SELECT contract_number FROM service_contracts
            WHERE contract_number LIKE ? AND company_id = ?
            ORDER BY id DESC LIMIT 1
        ", ["CON-$year%", $this->user['company_id']]);

        if ($lastContract) {
            $lastNumber = (int)substr($lastContract['contract_number'], -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('CON-%s%04d', $year, $nextNumber);
    }

    private function logActivity($action, $description, $entityId = null, $details = null) {
        try {
            $this->db->insert('audit_log', [
                'company_id' => $this->user['company_id'],
                'user_id' => $this->user['id'],
                'action' => $action,
                'description' => $description,
                'entity_type' => 'service_call',
                'entity_id' => $entityId,
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
