<?php

namespace TPT\ERP\Api\Controllers;

use TPT\ERP\Core\Response;
use TPT\ERP\Core\Request;
use TPT\ERP\Core\Database;
use TPT\ERP\Modules\QualityManagement;

/**
 * Quality Management API Controller
 * Handles all quality management-related API endpoints
 */
class QualityManagementController extends BaseController
{
    private $qualityManagement;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->qualityManagement = new QualityManagement();
        $this->db = Database::getInstance();
    }

    /**
     * Get quality management dashboard overview
     * GET /api/quality-management/overview
     */
    public function getOverview()
    {
        try {
            $this->requirePermission('quality.view');

            $data = [
                'quality_overview' => $this->qualityManagement->getQualityOverview(),
                'quality_metrics' => $this->qualityManagement->getQualityMetrics(),
                'audit_schedule' => $this->qualityManagement->getAuditSchedule(),
                'non_conformance_status' => $this->qualityManagement->getNonConformanceStatus(),
                'capa_status' => $this->qualityManagement->getCAPAStatus(),
                'compliance_status' => $this->qualityManagement->getComplianceStatus(),
                'quality_alerts' => $this->qualityManagement->getQualityAlerts(),
                'quality_analytics' => $this->qualityManagement->getQualityAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get quality checks with filtering
     * GET /api/quality-management/quality-checks
     */
    public function getQualityChecks()
    {
        try {
            $this->requirePermission('quality.control.view');

            $filters = [
                'criteria_id' => $_GET['criteria_id'] ?? null,
                'result' => $_GET['result'] ?? null,
                'inspector_id' => $_GET['inspector_id'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $qualityChecks = $this->qualityManagement->getQualityChecks();
            $total = $this->getQualityChecksCount($filters);

            Response::json([
                'quality_checks' => $qualityChecks,
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
     * Create quality check
     * POST /api/quality-management/quality-checks
     */
    public function createQualityCheck()
    {
        try {
            $this->requirePermission('quality.control.manage');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['criteria_id', 'check_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $checkData = [
                'criteria_id' => $data['criteria_id'],
                'inspector_id' => $data['inspector_id'] ?? $this->user['id'],
                'check_date' => $data['check_date'],
                'actual_value' => $data['actual_value'] ?? null,
                'result' => $data['result'] ?? 'pending',
                'defect_rate' => (float)($data['defect_rate'] ?? 0),
                'notes' => $data['notes'] ?? '',
                'attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $checkId = $this->db->insert('quality_checks', $checkData);

            // Log the creation
            $this->logActivity('quality_check_created', 'quality_checks', $checkId, "Quality check created for criteria {$data['criteria_id']}");

            Response::json([
                'success' => true,
                'quality_check_id' => $checkId,
                'message' => 'Quality check created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get audits with filtering
     * GET /api/quality-management/audits
     */
    public function getAudits()
    {
        try {
            $this->requirePermission('quality.audit.view');

            $filters = [
                'status' => $_GET['status'] ?? null,
                'audit_type_id' => $_GET['audit_type_id'] ?? null,
                'lead_auditor_id' => $_GET['lead_auditor_id'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $audits = $this->qualityManagement->getAuditSchedule();
            $total = $this->getAuditsCount($filters);

            Response::json([
                'audits' => $audits,
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
     * Create audit
     * POST /api/quality-management/audits
     */
    public function createAudit()
    {
        try {
            $this->requirePermission('quality.audit.manage');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['audit_title', 'audit_type_id', 'scheduled_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $auditData = [
                'audit_title' => trim($data['audit_title']),
                'audit_type_id' => $data['audit_type_id'],
                'description' => $data['description'] ?? '',
                'scope' => $data['scope'] ?? '',
                'objectives' => isset($data['objectives']) ? json_encode($data['objectives']) : null,
                'lead_auditor_id' => $data['lead_auditor_id'] ?? null,
                'audit_team' => isset($data['audit_team']) ? json_encode($data['audit_team']) : null,
                'scheduled_date' => $data['scheduled_date'],
                'duration_days' => (int)($data['duration_days'] ?? 1),
                'status' => $data['status'] ?? 'planned',
                'checklist_id' => $data['checklist_id'] ?? null,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $auditId = $this->db->insert('audits', $auditData);

            // Log the creation
            $this->logActivity('audit_created', 'audits', $auditId, "Audit '{$auditData['audit_title']}' created");

            Response::json([
                'success' => true,
                'audit_id' => $auditId,
                'message' => 'Audit created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get non-conformances with filtering
     * GET /api/quality-management/non-conformances
     */
    public function getNonConformances()
    {
        try {
            $this->requirePermission('quality.nonconformance.view');

            $filters = [
                'status' => $_GET['status'] ?? null,
                'category_id' => $_GET['category_id'] ?? null,
                'severity' => $_GET['severity'] ?? null,
                'reported_by' => $_GET['reported_by'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $nonConformances = $this->qualityManagement->getNonConformanceRecords();
            $total = $this->getNonConformancesCount($filters);

            Response::json([
                'non_conformances' => $nonConformances,
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
     * Create non-conformance
     * POST /api/quality-management/non-conformances
     */
    public function createNonConformance()
    {
        try {
            $this->requirePermission('quality.nonconformance.manage');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['description', 'category_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $ncData = [
                'description' => trim($data['description']),
                'category_id' => $data['category_id'],
                'severity' => $data['severity'] ?? 'minor',
                'severity_score' => (int)($data['severity_score'] ?? 1),
                'reported_by' => $data['reported_by'] ?? $this->user['id'],
                'reported_date' => $data['reported_date'] ?? date('Y-m-d'),
                'location' => $data['location'] ?? '',
                'root_cause' => $data['root_cause'] ?? '',
                'immediate_action' => $data['immediate_action'] ?? '',
                'status' => $data['status'] ?? 'open',
                'priority' => $data['priority'] ?? 'medium',
                'attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null,
                'quality_check_id' => $data['quality_check_id'] ?? null,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $ncId = $this->db->insert('non_conformances', $ncData);

            // Log the creation
            $this->logActivity('nonconformance_created', 'non_conformances', $ncId, "Non-conformance created: {$ncData['description']}");

            Response::json([
                'success' => true,
                'nonconformance_id' => $ncId,
                'message' => 'Non-conformance created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get CAPA records with filtering
     * GET /api/quality-management/capa
     */
    public function getCAPA()
    {
        try {
            $this->requirePermission('quality.capa.view');

            $filters = [
                'status' => $_GET['status'] ?? null,
                'priority' => $_GET['priority'] ?? null,
                'capa_type' => $_GET['capa_type'] ?? null,
                'responsible_party' => $_GET['responsible_party'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 50)
            ];

            $capaRecords = $this->qualityManagement->getCAPARecords();
            $total = $this->getCAPACount($filters);

            Response::json([
                'capa_records' => $capaRecords,
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
     * Create CAPA record
     * POST /api/quality-management/capa
     */
    public function createCAPA()
    {
        try {
            $this->requirePermission('quality.capa.manage');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['capa_type', 'description'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $capaData = [
                'capa_type' => $data['capa_type'],
                'description' => trim($data['description']),
                'priority' => $data['priority'] ?? 'medium',
                'status' => $data['status'] ?? 'open',
                'nonconformance_id' => $data['nonconformance_id'] ?? null,
                'root_cause' => $data['root_cause'] ?? '',
                'target_completion_date' => $data['target_completion_date'] ?? null,
                'responsible_party' => $data['responsible_party'] ?? '',
                'resources_required' => isset($data['resources_required']) ? json_encode($data['resources_required']) : null,
                'progress_percentage' => 0,
                'attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $capaId = $this->db->insert('capa_records', $capaData);

            // Log the creation
            $this->logActivity('capa_created', 'capa_records', $capaId, "CAPA created: {$capaData['description']}");

            Response::json([
                'success' => true,
                'capa_id' => $capaId,
                'message' => 'CAPA record created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get ISO compliance data
     * GET /api/quality-management/iso-compliance
     */
    public function getISOCompliance()
    {
        try {
            $this->requirePermission('quality.iso.view');

            $data = [
                'iso_standards' => $this->qualityManagement->getISOStandards(),
                'compliance_requirements' => $this->qualityManagement->getComplianceRequirements(),
                'compliance_assessments' => $this->qualityManagement->getComplianceAssessments(),
                'compliance_gaps' => $this->qualityManagement->getComplianceGaps(),
                'compliance_actions' => $this->qualityManagement->getComplianceActions(),
                'compliance_reports' => $this->qualityManagement->getComplianceReports(),
                'compliance_analytics' => $this->qualityManagement->getComplianceAnalytics(),
                'compliance_templates' => $this->qualityManagement->getComplianceTemplates()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get quality standards
     * GET /api/quality-management/standards
     */
    public function getQualityStandards()
    {
        try {
            $this->requirePermission('quality.standards.view');

            $data = [
                'quality_standards' => $this->qualityManagement->getQualityStandards(),
                'standard_requirements' => $this->qualityManagement->getStandardRequirements(),
                'standard_compliance' => $this->qualityManagement->getStandardCompliance(),
                'standard_updates' => $this->qualityManagement->getStandardUpdates(),
                'standard_training' => $this->qualityManagement->getStandardTraining(),
                'standard_audits' => $this->qualityManagement->getStandardAudits(),
                'standard_reports' => $this->qualityManagement->getStandardReports(),
                'standard_analytics' => $this->qualityManagement->getStandardAnalytics()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get statistical process control data
     * GET /api/quality-management/spc
     */
    public function getSPC()
    {
        try {
            $this->requirePermission('quality.spc.view');

            $data = [
                'control_charts' => $this->qualityManagement->getControlCharts(),
                'process_capability' => $this->qualityManagement->getProcessCapability(),
                'control_limits' => $this->qualityManagement->getControlLimits(),
                'out_of_control' => $this->qualityManagement->getOutOfControl(),
                'process_variation' => $this->qualityManagement->getProcessVariation(),
                'spc_reports' => $this->qualityManagement->getSPCReports(),
                'spc_analytics' => $this->qualityManagement->getSPCAnalytics(),
                'spc_templates' => $this->qualityManagement->getSPCTemplates()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get quality analytics
     * GET /api/quality-management/analytics
     */
    public function getAnalytics()
    {
        try {
            $this->requirePermission('quality.analytics.view');

            $data = [
                'quality_trends' => $this->qualityManagement->getQualityTrends(),
                'defect_analysis' => $this->qualityManagement->getDefectAnalysis(),
                'quality_costs' => $this->qualityManagement->getQualityCosts(),
                'supplier_quality' => $this->qualityManagement->getSupplierQuality(),
                'process_performance' => $this->qualityManagement->getProcessPerformance(),
                'quality_dashboards' => $this->qualityManagement->getQualityDashboards(),
                'predictive_quality' => $this->qualityManagement->getPredictiveQuality(),
                'quality_benchmarks' => $this->qualityManagement->getQualityBenchmarks()
            ];

            Response::json($data);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create audit finding
     * POST /api/quality-management/audits/{id}/findings
     */
    public function createAuditFinding($auditId)
    {
        try {
            $this->requirePermission('quality.audit.manage');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['finding_type', 'description'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $findingData = [
                'audit_id' => $auditId,
                'finding_type' => $data['finding_type'],
                'description' => trim($data['description']),
                'severity' => $data['severity'] ?? 'minor',
                'category' => $data['category'] ?? '',
                'clause_reference' => $data['clause_reference'] ?? '',
                'evidence' => isset($data['evidence']) ? json_encode($data['evidence']) : null,
                'corrective_action_required' => (bool)($data['corrective_action_required'] ?? true),
                'status' => $data['status'] ?? 'open',
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $findingId = $this->db->insert('audit_findings', $findingData);

            // Log the creation
            $this->logActivity('audit_finding_created', 'audit_findings', $findingId, "Audit finding created for audit {$auditId}");

            Response::json([
                'success' => true,
                'finding_id' => $findingId,
                'message' => 'Audit finding created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create root cause analysis
     * POST /api/quality-management/non-conformances/{id}/root-cause
     */
    public function createRootCauseAnalysis($ncId)
    {
        try {
            $this->requirePermission('quality.nonconformance.manage');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['analysis_method'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $rcaData = [
                'nonconformance_id' => $ncId,
                'analysis_method' => $data['analysis_method'],
                'analysis_date' => $data['analysis_date'] ?? date('Y-m-d'),
                'analyst' => $data['analyst'] ?? $this->user['id'],
                'root_cause_type' => $data['root_cause_type'] ?? '',
                'primary_root_cause' => $data['primary_root_cause'] ?? '',
                'contributing_factors' => isset($data['contributing_factors']) ? json_encode($data['contributing_factors']) : null,
                'analysis_tools_used' => isset($data['analysis_tools_used']) ? json_encode($data['analysis_tools_used']) : null,
                'recommendations' => isset($data['recommendations']) ? json_encode($data['recommendations']) : null,
                'attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $rcaId = $this->db->insert('root_cause_analysis', $rcaData);

            // Log the creation
            $this->logActivity('root_cause_analysis_created', 'root_cause_analysis', $rcaId, "Root cause analysis created for non-conformance {$ncId}");

            Response::json([
                'success' => true,
                'rca_id' => $rcaId,
                'message' => 'Root cause analysis created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Create containment action
     * POST /api/quality-management/non-conformances/{id}/containment
     */
    public function createContainmentAction($ncId)
    {
        try {
            $this->requirePermission('quality.nonconformance.manage');

            $data = Request::getJsonBody();

            // Validate required fields
            $required = ['action_description'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::error("Field '$field' is required", 400);
                    return;
                }
            }

            $actionData = [
                'nonconformance_id' => $ncId,
                'action_description' => trim($data['action_description']),
                'responsible_party' => $data['responsible_party'] ?? '',
                'target_completion_date' => $data['target_completion_date'] ?? null,
                'actual_completion_date' => $data['actual_completion_date'] ?? null,
                'effectiveness_rating' => (int)($data['effectiveness_rating'] ?? 0),
                'status' => $data['status'] ?? 'planned',
                'resources_used' => isset($data['resources_used']) ? json_encode($data['resources_used']) : null,
                'attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null,
                'company_id' => $this->user['company_id'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $actionId = $this->db->insert('containment_actions', $actionData);

            // Log the creation
            $this->logActivity('containment_action_created', 'containment_actions', $actionId, "Containment action created for non-conformance {$ncId}");

            Response::json([
                'success' => true,
                'action_id' => $actionId,
                'message' => 'Containment action created successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Bulk update quality checks
     * POST /api/quality-management/quality-checks/bulk-update
     */
    public function bulkUpdateQualityChecks()
    {
        try {
            $this->requirePermission('quality.control.manage');

            $data = Request::getJsonBody();

            if (!isset($data['check_ids']) || !is_array($data['check_ids'])) {
                Response::error('Check IDs array is required', 400);
                return;
            }

            if (empty($data['updates'])) {
                Response::error('Updates object is required', 400);
                return;
            }

            $checkIds = $data['check_ids'];
            $updates = $data['updates'];

            // Start transaction
            $this->db->beginTransaction();

            try {
                $updateCount = 0;

                foreach ($checkIds as $checkId) {
                    $check = $this->getQualityCheckById($checkId);
                    if (!$check) continue;

                    $updateData = [];
                    $allowedFields = [
                        'result', 'actual_value', 'defect_rate', 'notes'
                    ];

                    foreach ($allowedFields as $field) {
                        if (isset($updates[$field])) {
                            $updateData[$field] = $updates[$field];
                        }
                    }

                    if (!empty($updateData)) {
                        $updateData['updated_by'] = $this->user['id'];
                        $updateData['updated_at'] = date('Y-m-d H:i:s');

                        $this->db->update('quality_checks', $updateData, ['id' => $checkId]);
                        $updateCount++;
                    }
                }

                $this->db->commit();

                // Log bulk update
                $this->logActivity('bulk_quality_check_update', 'quality_checks', null, "Bulk updated {$updateCount} quality checks");

                Response::json([
                    'success' => true,
                    'updated_count' => $updateCount,
                    'message' => "{$updateCount} quality checks updated successfully"
                ]);
            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get inspection criteria
     * GET /api/quality-management/inspection-criteria
     */
    public function getInspectionCriteria()
    {
        try {
            $this->requirePermission('quality.control.view');

            $criteria = $this->qualityManagement->getInspectionCriteria();

            Response::json(['inspection_criteria' => $criteria]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get non-conformance categories
     * GET /api/quality-management/nc-categories
     */
    public function getNonConformanceCategories()
    {
        try {
            $this->requirePermission('quality.nonconformance.view');

            $categories = $this->qualityManagement->getNonConformanceCategories();

            Response::json(['categories' => $categories]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Get audit types
     * GET /api/quality-management/audit-types
     */
    public function getAuditTypes()
    {
        try {
            $this->requirePermission('quality.audit.view');

            $auditTypes = $this->db->query("
                SELECT * FROM audit_types
                WHERE company_id = ? AND is_active = true
                ORDER BY audit_type_name ASC
            ", [$this->user['company_id']]);

            Response::json(['audit_types' => $auditTypes]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getQualityCheckById($id)
    {
        return $this->db->queryOne(
            "SELECT * FROM quality_checks WHERE id = ? AND company_id = ?",
            [$id, $this->user['company_id']]
        );
    }

    private function getQualityChecksCount($filters)
    {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['criteria_id']) {
            $where[] = "criteria_id = ?";
            $params[] = $filters['criteria_id'];
        }

        if ($filters['result']) {
            $where[] = "result = ?";
            $params[] = $filters['result'];
        }

        if ($filters['inspector_id']) {
            $where[] = "inspector_id = ?";
            $params[] = $filters['inspector_id'];
        }

        if ($filters['date_from']) {
            $where[] = "check_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "check_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM quality_checks WHERE $whereClause", $params);
    }

    private function getAuditsCount($filters)
    {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['audit_type_id']) {
            $where[] = "audit_type_id = ?";
            $params[] = $filters['audit_type_id'];
        }

        if ($filters['lead_auditor_id']) {
            $where[] = "lead_auditor_id = ?";
            $params[] = $filters['lead_auditor_id'];
        }

        if ($filters['date_from']) {
            $where[] = "scheduled_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "scheduled_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM audits WHERE $whereClause", $params);
    }

    private function getNonConformancesCount($filters)
    {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['category_id']) {
            $where[] = "category_id = ?";
            $params[] = $filters['category_id'];
        }

        if ($filters['severity']) {
            $where[] = "severity = ?";
            $params[] = $filters['severity'];
        }

        if ($filters['reported_by']) {
            $where[] = "reported_by = ?";
            $params[] = $filters['reported_by'];
        }

        if ($filters['date_from']) {
            $where[] = "reported_date >= ?";
            $params[] = $filters['date_from'];
        }

        if ($filters['date_to']) {
            $where[] = "reported_date <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM non_conformances WHERE $whereClause", $params);
    }

    private function getCAPACount($filters)
    {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status']) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['priority']) {
            $where[] = "priority = ?";
            $params[] = $filters['priority'];
        }

        if ($filters['capa_type']) {
            $where[] = "capa_type = ?";
            $params[] = $filters['capa_type'];
        }

        if ($filters['responsible_party']) {
            $where[] = "responsible_party = ?";
            $params[] = $filters['responsible_party'];
        }

        if ($filters['date_from']) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->queryValue("SELECT COUNT(*) FROM capa_records WHERE $whereClause", $params);
    }

    private function logActivity($action, $table, $recordId, $description)
    {
        $this->db->insert('quality_activities', [
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
