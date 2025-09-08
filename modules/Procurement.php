<?php
/**
 * TPT Free ERP - Procurement Module
 * Complete vendor management, purchase orders, and supplier evaluation system
 */

class Procurement extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main procurement dashboard
     */
    public function index() {
        $this->requirePermission('procurement.view');

        $data = [
            'title' => 'Procurement Management',
            'procurement_overview' => $this->getProcurementOverview(),
            'pending_approvals' => $this->getPendingApprovals(),
            'supplier_performance' => $this->getSupplierPerformance(),
            'purchase_orders' => $this->getPurchaseOrdersSummary(),
            'spend_analysis' => $this->getSpendAnalysis(),
            'contract_expirations' => $this->getContractExpirations(),
            'requisition_status' => $this->getRequisitionStatus(),
            'procurement_analytics' => $this->getProcurementAnalytics()
        ];

        $this->render('modules/procurement/dashboard', $data);
    }

    /**
     * Vendor management
     */
    public function vendors() {
        $this->requirePermission('procurement.vendors.view');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'category' => $_GET['category'] ?? null,
            'rating_min' => $_GET['rating_min'] ?? null,
            'rating_max' => $_GET['rating_max'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $vendors = $this->getVendors($filters);

        $data = [
            'title' => 'Vendor Management',
            'vendors' => $vendors,
            'filters' => $filters,
            'vendor_categories' => $this->getVendorCategories(),
            'vendor_ratings' => $this->getVendorRatings(),
            'vendor_stats' => $this->getVendorStats($filters),
            'vendor_templates' => $this->getVendorTemplates(),
            'bulk_actions' => $this->getBulkActions(),
            'vendor_portal' => $this->getVendorPortal()
        ];

        $this->render('modules/procurement/vendors', $data);
    }

    /**
     * Purchase orders
     */
    public function purchaseOrders() {
        $this->requirePermission('procurement.purchase_orders.view');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'vendor' => $_GET['vendor'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'amount_min' => $_GET['amount_min'] ?? null,
            'amount_max' => $_GET['amount_max'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $purchase_orders = $this->getPurchaseOrders($filters);

        $data = [
            'title' => 'Purchase Orders',
            'purchase_orders' => $purchase_orders,
            'filters' => $filters,
            'approval_workflow' => $this->getApprovalWorkflow(),
            'purchase_templates' => $this->getPurchaseTemplates(),
            'shipping_methods' => $this->getShippingMethods(),
            'payment_terms' => $this->getPaymentTerms(),
            'po_stats' => $this->getPOStats($filters),
            'receiving_status' => $this->getReceivingStatus(),
            'po_analytics' => $this->getPOAnalytics()
        ];

        $this->render('modules/procurement/purchase_orders', $data);
    }

    /**
     * Requisitions
     */
    public function requisitions() {
        $this->requirePermission('procurement.requisitions.view');

        $data = [
            'title' => 'Purchase Requisitions',
            'requisitions' => $this->getRequisitions(),
            'requisition_templates' => $this->getRequisitionTemplates(),
            'approval_matrix' => $this->getApprovalMatrix(),
            'budget_checks' => $this->getBudgetChecks(),
            'requisition_workflow' => $this->getRequisitionWorkflow(),
            'requisition_analytics' => $this->getRequisitionAnalytics(),
            'pending_requisitions' => $this->getPendingRequisitions(),
            'requisition_reports' => $this->getRequisitionReports()
        ];

        $this->render('modules/procurement/requisitions', $data);
    }

    /**
     * Contract management
     */
    public function contracts() {
        $this->requirePermission('procurement.contracts.view');

        $data = [
            'title' => 'Contract Management',
            'contracts' => $this->getContracts(),
            'contract_templates' => $this->getContractTemplates(),
            'contract_types' => $this->getContractTypes(),
            'contract_expirations' => $this->getContractExpirations(),
            'contract_compliance' => $this->getContractCompliance(),
            'contract_analytics' => $this->getContractAnalytics(),
            'renewal_reminders' => $this->getRenewalReminders(),
            'contract_reports' => $this->getContractReports()
        ];

        $this->render('modules/procurement/contracts', $data);
    }

    /**
     * Supplier evaluation
     */
    public function supplierEvaluation() {
        $this->requirePermission('procurement.evaluation.view');

        $data = [
            'title' => 'Supplier Evaluation',
            'evaluations' => $this->getSupplierEvaluations(),
            'evaluation_criteria' => $this->getEvaluationCriteria(),
            'evaluation_templates' => $this->getEvaluationTemplates(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'evaluation_schedule' => $this->getEvaluationSchedule(),
            'evaluation_reports' => $this->getEvaluationReports(),
            'improvement_plans' => $this->getImprovementPlans(),
            'evaluation_analytics' => $this->getEvaluationAnalytics()
        ];

        $this->render('modules/procurement/supplier_evaluation', $data);
    }

    /**
     * Spend analysis
     */
    public function spendAnalysis() {
        $this->requirePermission('procurement.spend.view');

        $data = [
            'title' => 'Spend Analysis',
            'spend_by_category' => $this->getSpendByCategory(),
            'spend_by_vendor' => $this->getSpendByVendor(),
            'spend_trends' => $this->getSpendTrends(),
            'cost_savings' => $this->getCostSavings(),
            'spend_forecasting' => $this->getSpendForecasting(),
            'spend_benchmarks' => $this->getSpendBenchmarks(),
            'spend_reports' => $this->getSpendReports(),
            'spend_analytics' => $this->getSpendAnalytics()
        ];

        $this->render('modules/procurement/spend_analysis', $data);
    }

    /**
     * Supplier portal
     */
    public function supplierPortal() {
        $this->requirePermission('procurement.portal.view');

        $data = [
            'title' => 'Supplier Portal',
            'portal_vendors' => $this->getPortalVendors(),
            'portal_orders' => $this->getPortalOrders(),
            'portal_invoices' => $this->getPortalInvoices(),
            'portal_communications' => $this->getPortalCommunications(),
            'portal_performance' => $this->getPortalPerformance(),
            'portal_documents' => $this->getPortalDocuments(),
            'portal_analytics' => $this->getPortalAnalytics(),
            'portal_settings' => $this->getPortalSettings()
        ];

        $this->render('modules/procurement/supplier_portal', $data);
    }

    /**
     * Procurement analytics
     */
    public function analytics() {
        $this->requirePermission('procurement.analytics.view');

        $data = [
            'title' => 'Procurement Analytics',
            'procurement_metrics' => $this->getProcurementMetrics(),
            'supplier_analytics' => $this->getSupplierAnalytics(),
            'category_analytics' => $this->getCategoryAnalytics(),
            'compliance_analytics' => $this->getComplianceAnalytics(),
            'risk_analytics' => $this->getRiskAnalytics(),
            'performance_analytics' => $this->getPerformanceAnalytics(),
            'forecasting_analytics' => $this->getForecastingAnalytics(),
            'benchmarking_analytics' => $this->getBenchmarkingAnalytics()
        ];

        $this->render('modules/procurement/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getProcurementOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT v.id) as total_vendors,
                COUNT(DISTINCT po.id) as total_purchase_orders,
                COUNT(DISTINCT r.id) as total_requisitions,
                COUNT(DISTINCT c.id) as total_contracts,
                SUM(po.total_amount) as total_spend,
                AVG(po.total_amount) as avg_order_value,
                COUNT(CASE WHEN po.status = 'pending_approval' THEN 1 END) as pending_approvals,
                COUNT(CASE WHEN c.end_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as expiring_contracts
            FROM vendors v
            LEFT JOIN purchase_orders po ON po.company_id = v.company_id
            LEFT JOIN requisitions r ON r.company_id = v.company_id
            LEFT JOIN contracts c ON c.company_id = v.company_id
            WHERE v.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPendingApprovals() {
        return $this->db->query("
            SELECT
                pa.*,
                u.first_name as requested_by_first,
                u.last_name as requested_by_last,
                pa.approval_type,
                pa.amount,
                pa.description,
                TIMESTAMPDIFF(DAY, pa.created_at, NOW()) as days_pending,
                pa.priority_level
            FROM pending_approvals pa
            LEFT JOIN users u ON pa.requested_by = u.id
            WHERE pa.company_id = ? AND pa.status = 'pending'
            ORDER BY pa.priority_level DESC, pa.created_at ASC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getSupplierPerformance() {
        return $this->db->query("
            SELECT
                v.vendor_name,
                sp.rating_period,
                sp.on_time_delivery_rate,
                sp.quality_rating,
                sp.price_competitiveness,
                sp.overall_rating,
                sp.lead_time_days,
                sp.defect_rate_percentage
            FROM vendors v
            JOIN supplier_performance sp ON v.id = sp.vendor_id
            WHERE v.company_id = ?
            ORDER BY sp.rating_period DESC, sp.overall_rating DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getPurchaseOrdersSummary() {
        return $this->db->query("
            SELECT
                po.status,
                COUNT(*) as order_count,
                SUM(po.total_amount) as total_amount,
                AVG(po.total_amount) as avg_amount,
                AVG(TIMESTAMPDIFF(DAY, po.order_date, po.expected_delivery_date)) as avg_lead_time
            FROM purchase_orders po
            WHERE po.company_id = ?
            GROUP BY po.status
            ORDER BY order_count DESC
        ", [$this->user['company_id']]);
    }

    private function getSpendAnalysis() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(po.order_date, '%Y-%m') as month,
                COUNT(po.id) as order_count,
                SUM(po.total_amount) as total_spend,
                AVG(po.total_amount) as avg_order_value,
                COUNT(DISTINCT po.vendor_id) as unique_vendors
            FROM purchase_orders po
            WHERE po.company_id = ? AND po.order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(po.order_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getContractExpirations() {
        return $this->db->query("
            SELECT
                c.*,
                v.vendor_name,
                TIMESTAMPDIFF(DAY, CURDATE(), c.end_date) as days_until_expiry,
                c.contract_value,
                c.auto_renewal,
                c.renewal_notice_days
            FROM contracts c
            JOIN vendors v ON c.vendor_id = v.id
            WHERE c.company_id = ? AND c.end_date >= CURDATE() AND c.end_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            ORDER BY c.end_date ASC
        ", [$this->user['company_id']]);
    }

    private function getRequisitionStatus() {
        return $this->db->query("
            SELECT
                r.status,
                COUNT(*) as requisition_count,
                SUM(r.total_amount) as total_amount,
                AVG(r.total_amount) as avg_amount,
                AVG(TIMESTAMPDIFF(DAY, r.created_at, COALESCE(r.approved_at, CURDATE()))) as avg_approval_time
            FROM requisitions r
            WHERE r.company_id = ?
            GROUP BY r.status
            ORDER BY requisition_count DESC
        ", [$this->user['company_id']]);
    }

    private function getProcurementAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(po.id) as total_orders,
                SUM(po.total_amount) as total_spend,
                AVG(po.total_amount) as avg_order_value,
                COUNT(DISTINCT po.vendor_id) as unique_vendors,
                COUNT(CASE WHEN po.status = 'delivered' THEN 1 END) as completed_orders,
                ROUND((COUNT(CASE WHEN po.status = 'delivered' THEN 1 END) / NULLIF(COUNT(po.id), 0)) * 100, 2) as completion_rate,
                AVG(TIMESTAMPDIFF(DAY, po.order_date, po.actual_delivery_date)) as avg_delivery_time
            FROM purchase_orders po
            WHERE po.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getVendors($filters) {
        $where = ["v.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "v.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['category']) {
            $where[] = "v.category = ?";
            $params[] = $filters['category'];
        }

        if ($filters['rating_min']) {
            $where[] = "v.rating >= ?";
            $params[] = $filters['rating_min'];
        }

        if ($filters['rating_max']) {
            $where[] = "v.rating <= ?";
            $params[] = $filters['rating_max'];
        }

        if ($filters['search']) {
            $where[] = "(v.vendor_name LIKE ? OR v.contact_person LIKE ? OR v.email LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                v.*,
                COUNT(po.id) as order_count,
                SUM(po.total_amount) as total_spend,
                MAX(po.order_date) as last_order_date,
                AVG(po.total_amount) as avg_order_value,
                v.rating,
                v.lead_time_days,
                v.payment_terms
            FROM vendors v
            LEFT JOIN purchase_orders po ON v.id = po.vendor_id
            WHERE $whereClause
            GROUP BY v.id
            ORDER BY v.vendor_name ASC
        ", $params);
    }

    private function getVendorCategories() {
        return [
            'raw_materials' => 'Raw Materials',
            'finished_goods' => 'Finished Goods',
            'services' => 'Services',
            'equipment' => 'Equipment',
            'software' => 'Software',
            'consulting' => 'Consulting',
            'maintenance' => 'Maintenance',
            'utilities' => 'Utilities',
            'transportation' => 'Transportation',
            'other' => 'Other'
        ];
    }

    private function getVendorRatings() {
        return $this->db->query("
            SELECT
                v.vendor_name,
                vr.rating_period,
                vr.overall_rating,
                vr.delivery_rating,
                vr.quality_rating,
                vr.price_rating,
                vr.communication_rating,
                vr.rated_by
            FROM vendors v
            JOIN vendor_ratings vr ON v.id = vr.vendor_id
            WHERE v.company_id = ?
            ORDER BY vr.rating_period DESC, vr.overall_rating DESC
        ", [$this->user['company_id']]);
    }

    private function getVendorStats($filters) {
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

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_vendors,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_vendors,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as high_rated_vendors,
                AVG(rating) as avg_rating,
                SUM(total_spend) as total_spend,
                AVG(lead_time_days) as avg_lead_time
            FROM vendors
            WHERE $whereClause
        ", $params);
    }

    private function getVendorTemplates() {
        return $this->db->query("
            SELECT * FROM vendor_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getBulkActions() {
        return [
            'update_category' => 'Update Category',
            'update_rating' => 'Update Rating',
            'send_notification' => 'Send Notification',
            'update_status' => 'Update Status',
            'export_vendors' => 'Export Vendor Data',
            'import_vendors' => 'Import Vendor Data',
            'bulk_evaluation' => 'Bulk Evaluation',
            'update_payment_terms' => 'Update Payment Terms'
        ];
    }

    private function getVendorPortal() {
        return $this->db->query("
            SELECT
                vp.*,
                v.vendor_name,
                vp.portal_status,
                vp.last_login,
                vp.document_count,
                vp.order_count,
                vp.communication_count
            FROM vendor_portal vp
            JOIN vendors v ON vp.vendor_id = v.id
            WHERE vp.company_id = ?
            ORDER BY vp.last_login DESC
        ", [$this->user['company_id']]);
    }

    private function getPurchaseOrders($filters) {
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

        return $this->db->query("
            SELECT
                po.*,
                v.vendor_name,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                po.total_amount,
                po.status,
                po.order_date,
                po.expected_delivery_date,
                COUNT(poi.id) as item_count,
                po.approved_by,
                po.approved_at
            FROM purchase_orders po
            JOIN vendors v ON po.vendor_id = v.id
            LEFT JOIN users u ON po.created_by = u.id
            LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            WHERE $whereClause
            GROUP BY po.id, v.vendor_name, u.first_name, u.last_name
            ORDER BY po.order_date DESC
        ", $params);
    }

    private function getApprovalWorkflow() {
        return $this->db->query("
            SELECT * FROM approval_workflow
            WHERE company_id = ? AND is_active = true
            ORDER BY approval_level ASC, amount_threshold ASC
        ", [$this->user['company_id']]);
    }

    private function getPurchaseTemplates() {
        return $this->db->query("
            SELECT * FROM purchase_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getShippingMethods() {
        return $this->db->query("
            SELECT * FROM shipping_methods
            WHERE company_id = ? AND is_active = true
            ORDER BY sort_order ASC
        ", [$this->user['company_id']]);
    }

    private function getPaymentTerms() {
        return $this->db->query("
            SELECT * FROM payment_terms
            WHERE company_id = ? AND is_active = true
            ORDER BY net_days ASC
        ", [$this->user['company_id']]);
    }

    private function getPOStats($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['vendor']) {
            $where[] = "vendor_id = ?";
            $params[] = $filters['vendor'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_orders,
                SUM(total_amount) as total_value,
                AVG(total_amount) as avg_order_value,
                COUNT(DISTINCT vendor_id) as unique_vendors,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_orders,
                ROUND((COUNT(CASE WHEN status = 'delivered' THEN 1 END) / NULLIF(COUNT(*), 0)) * 100, 2) as completion_rate
            FROM purchase_orders
            WHERE $whereClause
        ", $params);
    }

    private function getReceivingStatus() {
        return $this->db->query("
            SELECT
                po.order_number,
                v.vendor_name,
                poi.item_description,
                poi.quantity_ordered,
                poi.quantity_received,
                poi.quantity_pending,
                po.expected_delivery_date,
                TIMESTAMPDIFF(DAY, CURDATE(), po.expected_delivery_date) as days_until_delivery
            FROM purchase_orders po
            JOIN vendors v ON po.vendor_id = v.id
            JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            WHERE po.company_id = ? AND poi.quantity_pending > 0
            ORDER BY po.expected_delivery_date ASC
        ", [$this->user['company_id']]);
    }

    private function getPOAnalytics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(po.order_date, '%Y-%m') as month,
                COUNT(po.id) as order_count,
                SUM(po.total_amount) as total_value,
                AVG(po.total_amount) as avg_order_value,
                COUNT(DISTINCT po.vendor_id) as unique_vendors
            FROM purchase_orders po
            WHERE po.company_id = ? AND po.order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(po.order_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getRequisitions() {
        return $this->db->query("
            SELECT
                r.*,
                u.first_name as requested_by_first,
                u.last_name as requested_by_last,
                d.department_name,
                r.total_amount,
                r.status,
                r.created_at,
                r.approved_at,
                COUNT(ri.id) as item_count
            FROM requisitions r
            LEFT JOIN users u ON r.requested_by = u.id
            LEFT JOIN departments d ON r.department_id = d.id
            LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
            WHERE r.company_id = ?
            GROUP BY r.id, u.first_name, u.last_name, d.department_name
            ORDER BY r.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getRequisitionTemplates() {
        return $this->db->query("
            SELECT * FROM requisition_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getApprovalMatrix() {
        return $this->db->query("
            SELECT * FROM approval_matrix
            WHERE company_id = ? AND is_active = true
            ORDER BY approval_level ASC, amount_threshold ASC
        ", [$this->user['company_id']]);
    }

    private function getBudgetChecks() {
        return $this->db->query("
            SELECT
                bc.*,
                r.requisition_number,
                bc.budget_available,
                bc.amount_requested,
                bc.budget_remaining,
                bc.check_status
            FROM budget_checks bc
            JOIN requisitions r ON bc.requisition_id = r.id
            WHERE bc.company_id = ?
            ORDER BY bc.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getRequisitionWorkflow() {
        return $this->db->query("
            SELECT * FROM requisition_workflow
            WHERE company_id = ? AND is_active = true
            ORDER BY step_order ASC
        ", [$this->user['company_id']]);
    }

    private function getRequisitionAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(r.id) as total_requisitions,
                SUM(r.total_amount) as total_requested,
                AVG(r.total_amount) as avg_requisition_value,
                COUNT(CASE WHEN r.status = 'approved' THEN 1 END) as approved_requisitions,
                COUNT(CASE WHEN r.status = 'rejected' THEN 1 END) as rejected_requisitions,
                ROUND((COUNT(CASE WHEN r.status = 'approved' THEN 1 END) / NULLIF(COUNT(r.id), 0)) * 100, 2) as approval_rate,
                AVG(TIMESTAMPDIFF(DAY, r.created_at, COALESCE(r.approved_at, CURDATE()))) as avg_approval_time
            FROM requisitions r
            WHERE r.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPendingRequisitions() {
        return $this->db->query("
            SELECT
                r.*,
                u.first_name as requested_by_first,
                u.last_name as requested_by_last,
                d.department_name,
                r.total_amount,
                TIMESTAMPDIFF(DAY, r.created_at, NOW()) as days_pending
            FROM requisitions r
            LEFT JOIN users u ON r.requested_by = u.id
            LEFT JOIN departments d ON r.department_id = d.id
            WHERE r.company_id = ? AND r.status = 'pending_approval'
            ORDER BY r.created_at ASC
        ", [$this->user['company_id']]);
    }

    private function getRequisitionReports() {
        return $this->db->query("
            SELECT
                rr.*,
                rr.report_type,
                rr.report_period,
                rr.generated_date,
                rr.total_requisitions,
                rr.total_amount
            FROM requisition_reports rr
            WHERE rr.company_id = ?
            ORDER BY rr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getContracts() {
        return $this->db->query("
            SELECT
                c.*,
                v.vendor_name,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                c.contract_value,
                c.start_date,
                c.end_date,
                TIMESTAMPDIFF(DAY, CURDATE(), c.end_date) as days_until_expiry,
                c.auto_renewal,
                c.renewal_notice_days
            FROM contracts c
            JOIN vendors v ON c.vendor_id = v.id
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.company_id = ?
            ORDER BY c.end_date ASC
        ", [$this->user['company_id']]);
    }

    private function getContractTemplates() {
        return $this->db->query("
            SELECT * FROM contract_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getContractTypes() {
        return [
            'supply' => 'Supply Agreement',
            'service' => 'Service Agreement',
            'maintenance' => 'Maintenance Agreement',
            'consulting' => 'Consulting Agreement',
            'license' => 'License Agreement',
            'nda' => 'Non-Disclosure Agreement',
            'msa' => 'Master Service Agreement',
            'other' => 'Other'
        ];
    }

    private function getContractCompliance() {
        return $this->db->query("
            SELECT
                cc.*,
                c.contract_title,
                v.vendor_name,
                cc.compliance_type,
                cc.due_date,
                cc.status,
                cc.last_review_date,
                TIMESTAMPDIFF(DAY, CURDATE(), cc.due_date) as days_until_due
            FROM contract_compliance cc
            JOIN contracts c ON cc.contract_id = c.id
            JOIN vendors v ON c.vendor_id = v.id
            WHERE cc.company_id = ?
            ORDER BY cc.due_date ASC
        ", [$this->user['company_id']]);
    }

    private function getContractAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(c.id) as total_contracts,
                SUM(c.contract_value) as total_contract_value,
                AVG(c.contract_value) as avg_contract_value,
                COUNT(CASE WHEN c.end_date >= CURDATE() THEN 1 END) as active_contracts,
                COUNT(CASE WHEN c.end_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as expiring_contracts,
                COUNT(CASE WHEN c.auto_renewal = true THEN 1 END) as auto_renewal_contracts,
                AVG(TIMESTAMPDIFF(MONTH, c.start_date, c.end_date)) as avg_contract_duration_months
            FROM contracts c
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRenewalReminders() {
        return $this->db->query("
            SELECT
                c.*,
                v.vendor_name,
                TIMESTAMPDIFF(DAY, CURDATE(), c.end_date) as days_until_expiry,
                c.renewal_notice_days,
                DATE_SUB(c.end_date, INTERVAL c.renewal_notice_days DAY) as renewal_reminder_date
            FROM contracts c
            JOIN vendors v ON c.vendor_id = v.id
            WHERE c.company_id = ? AND c.auto_renewal = false
                AND DATE_SUB(c.end_date, INTERVAL c.renewal_notice_days DAY) <= CURDATE()
                AND c.end_date >= CURDATE()
            ORDER BY days_until_expiry ASC
        ", [$this->user['company_id']]);
    }

    private function getContractReports() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.report_type,
                cr.report_period,
                cr.generated_date,
                cr.total_contracts,
                cr.total_value
            FROM contract_reports cr
            WHERE cr.company_id = ?
            ORDER BY cr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierEvaluations() {
        return $this->db->query("
            SELECT
                se.*,
                v.vendor_name,
                u.first_name as evaluated_by_first,
                u.last_name as evaluated_by_last,
                se.evaluation_criteria,
                se.rating_score,
                se.comments,
                se.evaluation_date
            FROM supplier_evaluations se
            JOIN vendors v ON se.vendor_id = v.id
            LEFT JOIN users u ON se.evaluated_by = u.id
            WHERE se.company_id = ?
            ORDER BY se.evaluation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getEvaluationCriteria() {
        return $this->db->query("
            SELECT * FROM evaluation_criteria
            WHERE company_id = ? AND is_active = true
            ORDER BY category, criteria_name
        ", [$this->user['company_id']]);
    }

    private function getEvaluationTemplates() {
        return $this->db->query("
            SELECT * FROM evaluation_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceMetrics() {
        return $this->db->query("
            SELECT
                pm.*,
                v.vendor_name,
                pm.metric_name,
                pm.target_value,
                pm.actual_value,
                ROUND(((pm.actual_value - pm.target_value) / NULLIF(pm.target_value, 0)) * 100, 2) as performance_percentage,
                pm.metric_period
            FROM performance_metrics pm
            JOIN vendors v ON pm.vendor_id = v.id
            WHERE pm.company_id = ?
            ORDER BY pm.metric_period DESC, performance_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getEvaluationSchedule() {
        return $this->db->query("
            SELECT
                es.*,
                v.vendor_name,
                es.scheduled_date,
                es.evaluation_type,
                es.status,
                TIMESTAMPDIFF(DAY, CURDATE(), es.scheduled_date) as days_until_evaluation
            FROM evaluation_schedule es
            JOIN vendors v ON es.vendor_id = v.id
            WHERE es.company_id = ?
            ORDER BY es.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getEvaluationReports() {
        return $this->db->query("
            SELECT
                er.*,
                er.report_type,
                er.report_period,
                er.generated_date,
                er.total_evaluations,
                er.avg_rating
            FROM evaluation_reports er
            WHERE er.company_id = ?
            ORDER BY er.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getImprovementPlans() {
        return $this->db->query("
            SELECT
                ip.*,
                v.vendor_name,
                ip.plan_title,
                ip.target_completion_date,
                ip.progress_percentage,
                ip.status
            FROM improvement_plans ip
            JOIN vendors v ON ip.vendor_id = v.id
            WHERE ip.company_id = ?
            ORDER BY ip.target_completion_date ASC
        ", [$this->user['company_id']]);
    }

    private function getEvaluationAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(se.id) as total_evaluations,
                AVG(se.rating_score) as avg_rating,
                COUNT(CASE WHEN se.rating_score >= 4 THEN 1 END) as high_ratings,
                COUNT(CASE WHEN se.rating_score < 3 THEN 1 END) as low_ratings,
                COUNT(DISTINCT se.vendor_id) as evaluated_vendors,
                MAX(se.evaluation_date) as last_evaluation_date
            FROM supplier_evaluations se
            WHERE se.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSpendByCategory() {
        return $this->db->query("
            SELECT
                pc.category_name,
                COUNT(po.id) as order_count,
                SUM(po.total_amount) as total_spend,
                AVG(po.total_amount) as avg_order_value,
                ROUND((SUM(po.total_amount) / NULLIF((SELECT SUM(total_amount) FROM purchase_orders WHERE company_id = po.company_id), 0)) * 100, 2) as spend_percentage
            FROM purchase_orders po
            JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            LEFT JOIN product_categories pc ON poi.category_id = pc.id
            WHERE po.company_id = ?
            GROUP BY pc.category_name
            ORDER BY total_spend DESC
        ", [$this->user['company_id']]);
    }

    private function getSpendByVendor() {
        return $this->db->query("
            SELECT
                v.vendor_name,
                COUNT(po.id) as order_count,
                SUM(po.total_amount) as total_spend,
                AVG(po.total_amount) as avg_order_value,
                MAX(po.order_date) as last_order_date,
                ROUND((SUM(po.total_amount) / NULLIF((SELECT SUM(total_amount) FROM purchase_orders WHERE company_id = po.company_id), 0)) * 100, 2) as spend_percentage
            FROM purchase_orders po
            JOIN vendors v ON po.vendor_id = v.id
            WHERE po.company_id = ?
            GROUP BY v.vendor_name, v.id
            ORDER BY total_spend DESC
        ", [$this->user['company_id']]);
    }

    private function getSpendTrends() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(po.order_date, '%Y-%m') as month,
                COUNT(po.id) as order_count,
                SUM(po.total_amount) as total_spend,
                AVG(po.total_amount) as avg_order_value,
                COUNT(DISTINCT po.vendor_id) as unique_vendors
            FROM purchase_orders po
            WHERE po.company_id = ? AND po.order_date >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
            GROUP BY DATE_FORMAT(po.order_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getCostSavings() {
        return $this->db->query("
            SELECT
                cs.*,
                cs.savings_category,
                cs.amount_saved,
                cs.savings_percentage,
                cs.implementation_date,
                cs.expected_annual_savings
            FROM cost_savings cs
            WHERE cs.company_id = ?
            ORDER BY cs.amount_saved DESC
        ", [$this->user['company_id']]);
    }

    private function getSpendForecasting() {
        return $this->db->query("
            SELECT
                sf.*,
                sf.forecast_period,
                sf.forecast_amount,
                sf.actual_amount,
                sf.confidence_level,
                ROUND(((sf.actual_amount - sf.forecast_amount) / NULLIF(sf.forecast_amount, 0)) * 100, 2) as accuracy_percentage
            FROM spend_forecasting sf
            WHERE sf.company_id = ?
            ORDER BY sf.forecast_period ASC
        ", [$this->user['company_id']]);
    }

    private function getSpendBenchmarks() {
        return $this->db->query("
            SELECT
                sb.*,
                sb.benchmark_category,
                sb.company_performance,
                sb.industry_average,
                sb.top_performer,
                ROUND(((sb.company_performance - sb.industry_average) / NULLIF(sb.industry_average, 0)) * 100, 2) as variance_percentage
            FROM spend_benchmarks sb
            WHERE sb.company_id = ?
            ORDER BY ABS(sb.company_performance - sb.industry_average) DESC
        ", [$this->user['company_id']]);
    }

    private function getSpendReports() {
        return $this->db->query("
            SELECT
                sr.*,
                sr.report_type,
                sr.report_period,
                sr.generated_date,
                sr.total_spend,
                sr.cost_savings
            FROM spend_reports sr
            WHERE sr.company_id = ?
            ORDER BY sr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSpendAnalytics() {
        return $this->db->querySingle("
            SELECT
                SUM(po.total_amount) as total_spend,
                AVG(po.total_amount) as avg_order_value,
                COUNT(DISTINCT po.vendor_id) as unique_vendors,
                COUNT(DISTINCT pc.id) as spend_categories,
                MAX(po.order_date) as last_order_date,
                AVG(TIMESTAMPDIFF(DAY, po.order_date, po.actual_delivery_date)) as avg_delivery_time,
                SUM(cs.amount_saved) as total_cost_savings
            FROM purchase_orders po
            LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            LEFT JOIN product_categories pc ON poi.category_id = pc.id
            LEFT JOIN cost_savings cs ON cs.company_id = po.company_id
            WHERE po.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPortalVendors() {
        return $this->db->query("
            SELECT
                v.*,
                vp.portal_status,
                vp.last_login,
                vp.document_count,
                vp.order_count,
                vp.communication_count
            FROM vendors v
            LEFT JOIN vendor_portal vp ON v.id = vp.vendor_id
            WHERE v.company_id = ?
            ORDER BY vp.last_login DESC
        ", [$this->user['company_id']]);
    }

    private function getPortalOrders() {
        return $this->db->query("
            SELECT
                po.*,
                v.vendor_name,
                po.order_number,
                po.total_amount,
                po.status,
                po.order_date
            FROM purchase_orders po
            JOIN vendors v ON po.vendor_id = v.id
            WHERE po.company_id = ?
            ORDER BY po.order_date DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getPortalInvoices() {
        return $this->db->query("
            SELECT
                vi.*,
                v.vendor_name,
                vi.invoice_number,
                vi.invoice_amount,
                vi.status,
                vi.due_date
            FROM vendor_invoices vi
            JOIN vendors v ON vi.vendor_id = v.id
            WHERE vi.company_id = ?
            ORDER BY vi.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getPortalCommunications() {
        return $this->db->query("
            SELECT
                pc.*,
                v.vendor_name,
                pc.communication_type,
                pc.subject,
                pc.sent_date,
                pc.status
            FROM portal_communications pc
            JOIN vendors v ON pc.vendor_id = v.id
            WHERE pc.company_id = ?
            ORDER BY pc.sent_date DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getPortalPerformance() {
        return $this->db->query("
            SELECT
                v.vendor_name,
                pp.rating_period,
                pp.on_time_delivery_rate,
                pp.quality_rating,
                pp.overall_rating,
                pp.feedback_score
            FROM vendors v
            JOIN portal_performance pp ON v.id = pp.vendor_id
            WHERE v.company_id = ?
            ORDER BY pp.rating_period DESC
        ", [$this->user['company_id']]);
    }

    private function getPortalDocuments() {
        return $this->db->query("
            SELECT
                pd.*,
                v.vendor_name,
                pd.document_name,
                pd.document_type,
                pd.upload_date,
                pd.download_count
            FROM portal_documents pd
            JOIN vendors v ON pd.vendor_id = v.id
            WHERE pd.company_id = ?
            ORDER BY pd.upload_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPortalAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT vp.vendor_id) as active_portal_vendors,
                COUNT(pc.id) as total_communications,
                COUNT(pd.id) as total_documents,
                AVG(pp.feedback_score) as avg_feedback_score,
                COUNT(CASE WHEN vp.last_login >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as active_vendors_last_30_days
            FROM vendor_portal vp
            LEFT JOIN portal_communications pc ON vp.vendor_id = pc.vendor_id
            LEFT JOIN portal_documents pd ON vp.vendor_id = pd.vendor_id
            LEFT JOIN portal_performance pp ON vp.vendor_id = pp.vendor_id
            WHERE vp.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPortalSettings() {
        return $this->db->querySingle("
            SELECT * FROM portal_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getProcurementMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(po.id) as total_orders,
                SUM(po.total_amount) as total_spend,
                AVG(po.total_amount) as avg_order_value,
                COUNT(DISTINCT po.vendor_id) as unique_vendors,
                COUNT(CASE WHEN po.status = 'delivered' THEN 1 END) as completed_orders,
                ROUND((COUNT(CASE WHEN po.status = 'delivered' THEN 1 END) / NULLIF(COUNT(po.id), 0)) * 100, 2) as completion_rate,
                AVG(TIMESTAMPDIFF(DAY, po.order_date, po.actual_delivery_date)) as avg_delivery_time
            FROM purchase_orders po
            WHERE po.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSupplierAnalytics() {
        return $this->db->query("
            SELECT
                v.vendor_name,
                COUNT(po.id) as order_count,
                SUM(po.total_amount) as total_spend,
                AVG(po.total_amount) as avg_order_value,
                AVG(v.rating) as avg_rating,
                AVG(TIMESTAMPDIFF(DAY, po.order_date, po.actual_delivery_date)) as avg_delivery_time,
                ROUND((COUNT(CASE WHEN po.status = 'delivered' THEN 1 END) / NULLIF(COUNT(po.id), 0)) * 100, 2) as on_time_delivery_rate
            FROM vendors v
            LEFT JOIN purchase_orders po ON v.id = po.vendor_id
            WHERE v.company_id = ?
            GROUP BY v.vendor_name, v.id
            ORDER BY total_spend DESC
        ", [$this->user['company_id']]);
    }

    private function getCategoryAnalytics() {
        return $this->db->query("
            SELECT
                pc.category_name,
                COUNT(po.id) as order_count,
                SUM(po.total_amount) as total_spend,
                AVG(po.total_amount) as avg_order_value,
                COUNT(DISTINCT po.vendor_id) as unique_vendors,
                ROUND((SUM(po.total_amount) / NULLIF((SELECT SUM(total_amount) FROM purchase_orders WHERE company_id = po.company_id), 0)) * 100, 2) as spend_percentage
            FROM purchase_orders po
            JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
            LEFT JOIN product_categories pc ON poi.category_id = pc.id
            WHERE po.company_id = ?
            GROUP BY pc.category_name
            ORDER BY total_spend DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(c.id) as total_contracts,
                COUNT(CASE WHEN c.end_date >= CURDATE() THEN 1 END) as active_contracts,
                COUNT(CASE WHEN c.end_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as expiring_contracts,
                COUNT(cc.id) as total_compliance_checks,
                COUNT(CASE WHEN cc.status = 'compliant' THEN 1 END) as compliant_checks,
                ROUND((COUNT(CASE WHEN cc.status = 'compliant' THEN 1 END) / NULLIF(COUNT(cc.id), 0)) * 100, 2) as compliance_rate
            FROM contracts c
            LEFT JOIN contract_compliance cc ON c.id = cc.contract_id
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRiskAnalytics() {
        return $this->db->query("
            SELECT
                v.vendor_name,
                COUNT(po.id) as order_count,
                SUM(po.total_amount) as total_spend,
                AVG(v.rating) as avg_rating,
                COUNT(CASE WHEN po.status = 'delayed' THEN 1 END) as delayed_orders,
                COUNT(CASE WHEN po.status = 'cancelled' THEN 1 END) as cancelled_orders,
                ROUND((COUNT(CASE WHEN po.status IN ('delayed', 'cancelled') THEN 1 END) / NULLIF(COUNT(po.id), 0)) * 100, 2) as risk_percentage
            FROM vendors v
            LEFT JOIN purchase_orders po ON v.id = po.vendor_id
            WHERE v.company_id = ?
            GROUP BY v.vendor_name, v.id
            ORDER BY risk_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getPerformanceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(se.id) as total_evaluations,
                AVG(se.rating_score) as avg_rating,
                COUNT(CASE WHEN se.rating_score >= 4 THEN 1 END) as high_performers,
                COUNT(CASE WHEN se.rating_score < 3 THEN 1 END) as low_performers,
                COUNT(DISTINCT se.vendor_id) as evaluated_vendors,
                MAX(se.evaluation_date) as last_evaluation_date
            FROM supplier_evaluations se
            WHERE se.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getForecastingAnalytics() {
        return $this->db->query("
            SELECT
                sf.forecast_period,
                sf.forecast_amount,
                sf.actual_amount,
                sf.confidence_level,
                ROUND(((sf.actual_amount - sf.forecast_amount) / NULLIF(sf.forecast_amount, 0)) * 100, 2) as accuracy_percentage
            FROM spend_forecasting sf
            WHERE sf.company_id = ?
            ORDER BY sf.forecast_period ASC
        ", [$this->user['company_id']]);
    }

    private function getBenchmarkingAnalytics() {
        return $this->db->query("
            SELECT
                ba.*,
                ba.benchmark_category,
                ba.company_performance,
                ba.industry_average,
                ba.top_performer,
                ROUND(((ba.company_performance - ba.industry_average) / NULLIF(ba.industry_average, 0)) * 100, 2) as variance_percentage
            FROM benchmarking_analytics ba
            WHERE ba.company_id = ?
            ORDER BY ABS(ba.company_performance - ba.industry_average) DESC
        ", [$this->user['company_id']]);
    }
}
