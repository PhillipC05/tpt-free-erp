<?php
/**
 * TPT Free ERP - Quality Management Module
 * Complete quality control, audit, and compliance management system
 */

class QualityManagement extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main quality management dashboard
     */
    public function index() {
        $this->requirePermission('quality.view');

        $data = [
            'title' => 'Quality Management Dashboard',
            'quality_overview' => $this->getQualityOverview(),
            'quality_metrics' => $this->getQualityMetrics(),
            'recent_inspections' => $this->getRecentInspections(),
            'pending_audits' => $this->getPendingAudits(),
            'non_conformance_reports' => $this->getNonConformanceReports(),
            'quality_alerts' => $this->getQualityAlerts()
        ];

        $this->render('modules/quality/dashboard', $data);
    }

    /**
     * Quality control processes
     */
    public function qualityControl() {
        $this->requirePermission('quality.control.view');

        $data = [
            'title' => 'Quality Control',
            'inspection_plans' => $this->getInspectionPlans(),
            'quality_checks' => $this->getQualityChecks(),
            'control_charts' => $this->getControlCharts(),
            'quality_standards' => $this->getQualityStandards(),
            'sampling_plans' => $this->getSamplingPlans(),
            'quality_gates' => $this->getQualityGates()
        ];

        $this->render('modules/quality/quality_control', $data);
    }

    /**
     * Audit management
     */
    public function auditManagement() {
        $this->requirePermission('quality.audit.view');

        $data = [
            'title' => 'Audit Management',
            'audit_schedule' => $this->getAuditSchedule(),
            'audit_findings' => $this->getAuditFindings(),
            'audit_reports' => $this->getAuditReports(),
            'audit_checklists' => $this->getAuditChecklists(),
            'audit_team' => $this->getAuditTeam(),
            'audit_metrics' => $this->getAuditMetrics()
        ];

        $this->render('modules/quality/audit_management', $data);
    }

    /**
     * Non-conformance tracking
     */
    public function nonConformance() {
        $this->requirePermission('quality.non_conformance.view');

        $data = [
            'title' => 'Non-Conformance Tracking',
            'non_conformance_list' => $this->getNonConformanceList(),
            'corrective_actions' => $this->getCorrectiveActions(),
            'preventive_actions' => $this->getPreventiveActions(),
            'root_cause_analysis' => $this->getRootCauseAnalysis(),
            'capa_workflow' => $this->getCAPAWorkflow(),
            'non_conformance_metrics' => $this->getNonConformanceMetrics()
        ];

        $this->render('modules/quality/non_conformance', $data);
    }

    /**
     * CAPA (Corrective Action Preventive Action)
     */
    public function capa() {
        $this->requirePermission('quality.capa.view');

        $data = [
            'title' => 'CAPA Management',
            'capa_initiatives' => $this->getCAPAInitiatives(),
            'capa_effectiveness' => $this->getCAPAEffectiveness(),
            'capa_timeline' => $this->getCAPATimeline(),
            'capa_resources' => $this->getCAPAResources(),
            'capa_reporting' => $this->getCAPAReporting(),
            'capa_dashboard' => $this->getCAPADashboard()
        ];

        $this->render('modules/quality/capa', $data);
    }

    /**
     * ISO compliance tracking
     */
    public function isoCompliance() {
        $this->requirePermission('quality.iso.view');

        $data = [
            'title' => 'ISO Compliance',
            'iso_standards' => $this->getISOStandards(),
            'compliance_status' => $this->getComplianceStatus(),
            'certification_tracking' => $this->getCertificationTracking(),
            'compliance_audits' => $this->getComplianceAudits(),
            'gap_analysis' => $this->getGapAnalysis(),
            'compliance_metrics' => $this->getComplianceMetrics()
        ];

        $this->render('modules/quality/iso_compliance', $data);
    }

    /**
     * Supplier quality management
     */
    public function supplierQuality() {
        $this->requirePermission('quality.supplier.view');

        $data = [
            'title' => 'Supplier Quality Management',
            'supplier_evaluations' => $this->getSupplierEvaluations(),
            'supplier_scorecards' => $this->getSupplierScorecards(),
            'supplier_audits' => $this->getSupplierAudits(),
            'quality_agreements' => $this->getQualityAgreements(),
            'supplier_performance' => $this->getSupplierPerformance(),
            'supplier_development' => $this->getSupplierDevelopment()
        ];

        $this->render('modules/quality/supplier_quality', $data);
    }

    /**
     * Document control
     */
    public function documentControl() {
        $this->requirePermission('quality.document.view');

        $data = [
            'title' => 'Document Control',
            'document_repository' => $this->getDocumentRepository(),
            'document_versions' => $this->getDocumentVersions(),
            'approval_workflow' => $this->getApprovalWorkflow(),
            'document_distribution' => $this->getDocumentDistribution(),
            'training_records' => $this->getTrainingRecords(),
            'document_metrics' => $this->getDocumentMetrics()
        ];

        $this->render('modules/quality/document_control', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getQualityOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'passed' THEN 1 END) as passed_inspections,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_inspections,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_inspections,
                COUNT(DISTINCT CASE WHEN nc.status = 'open' THEN nc.id END) as open_non_conformances,
                COUNT(DISTINCT CASE WHEN nc.status = 'closed' THEN nc.id END) as closed_non_conformances,
                ROUND(AVG(CASE WHEN qc.result = 'pass' THEN 100 ELSE 0 END), 2) as quality_score,
                COUNT(DISTINCT a.id) as total_audits,
                COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END) as completed_audits
            FROM quality_checks qc
            LEFT JOIN non_conformances nc ON qc.id = nc.quality_check_id
            LEFT JOIN audits a ON a.scheduled_date >= CURDATE()
            WHERE qc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getQualityMetrics() {
        return [
            'defect_rate' => $this->calculateDefectRate(),
            'first_pass_yield' => $this->calculateFirstPassYield(),
            'customer_complaint_rate' => $this->calculateCustomerComplaintRate(),
            'supplier_quality_score' => $this->calculateSupplierQualityScore(),
            'audit_compliance_rate' => $this->calculateAuditComplianceRate(),
            'capa_effectiveness' => $this->calculateCAPAEffectiveness()
        ];
    }

    private function calculateDefectRate() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN result = 'fail' THEN 1 END) as defects,
                COUNT(*) as total_checks
            FROM quality_checks
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['total_checks'] > 0 ? ($result['defects'] / $result['total_checks']) * 100 : 0;
    }

    private function calculateFirstPassYield() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN result = 'pass' AND inspection_round = 1 THEN 1 END) as first_pass,
                COUNT(*) as total_items
            FROM quality_checks
            WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['total_items'] > 0 ? ($result['first_pass'] / $result['total_items']) * 100 : 0;
    }

    private function calculateCustomerComplaintRate() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(*) as complaints,
                SUM(total_orders) as total_orders
            FROM customer_complaints cc
            JOIN sales_orders so ON cc.order_id = so.id
            WHERE cc.company_id = ? AND cc.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", [$this->user['company_id']]);

        return $result['total_orders'] > 0 ? ($result['complaints'] / $result['total_orders']) * 100 : 0;
    }

    private function calculateSupplierQualityScore() {
        $result = $this->db->querySingle("
            SELECT AVG(quality_score) as avg_score
            FROM supplier_evaluations
            WHERE company_id = ? AND evaluation_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ", [$this->user['company_id']]);

        return $result['avg_score'] ?? 0;
    }

    private function calculateAuditComplianceRate() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'passed' THEN 1 END) as passed_audits,
                COUNT(*) as total_audits
            FROM audits
            WHERE company_id = ? AND audit_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
        ", [$this->user['company_id']]);

        return $result['total_audits'] > 0 ? ($result['passed_audits'] / $result['total_audits']) * 100 : 0;
    }

    private function calculateCAPAEffectiveness() {
        $result = $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN effectiveness_rating >= 4 THEN 1 END) as effective_capa,
                COUNT(*) as total_capa
            FROM capa_initiatives
            WHERE company_id = ? AND completion_date >= DATE_SUB(CURDATE(), INTERVAL 180 DAY)
        ", [$this->user['company_id']]);

        return $result['total_capa'] > 0 ? ($result['effective_capa'] / $result['total_capa']) * 100 : 0;
    }

    private function getRecentInspections() {
        return $this->db->query("
            SELECT
                qc.*,
                qc.inspection_type,
                qc.result,
                qc.inspection_date,
                qc.inspector_name,
                p.product_name,
                u.first_name,
                u.last_name
            FROM quality_checks qc
            LEFT JOIN products p ON qc.product_id = p.id
            LEFT JOIN users u ON qc.created_by = u.id
            WHERE qc.company_id = ?
            ORDER BY qc.inspection_date DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getPendingAudits() {
        return $this->db->query("
            SELECT
                a.*,
                a.audit_type,
                a.scheduled_date,
                a.auditor_name,
                a.scope,
                DATEDIFF(a.scheduled_date, CURDATE()) as days_until_audit
            FROM audits a
            WHERE a.company_id = ? AND a.status = 'scheduled' AND a.scheduled_date >= CURDATE()
            ORDER BY a.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getNonConformanceReports() {
        return $this->db->query("
            SELECT
                nc.*,
                nc.non_conformance_type,
                nc.severity,
                nc.status,
                nc.reported_date,
                p.product_name,
                u.first_name,
                u.last_name
            FROM non_conformances nc
            LEFT JOIN products p ON nc.product_id = p.id
            LEFT JOIN users u ON nc.reported_by = u.id
            WHERE nc.company_id = ?
            ORDER BY nc.reported_date DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getQualityAlerts() {
        return $this->db->query("
            SELECT
                qa.*,
                qa.alert_type,
                qa.severity,
                qa.message,
                qa.created_at,
                qa.status
            FROM quality_alerts qa
            WHERE qa.company_id = ? AND qa.status = 'active'
            ORDER BY qa.severity DESC, qa.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getInspectionPlans() {
        return $this->db->query("
            SELECT
                ip.*,
                ip.plan_name,
                ip.inspection_type,
                ip.frequency,
                ip.next_inspection_date,
                p.product_name,
                COUNT(qc.id) as completed_inspections
            FROM inspection_plans ip
            LEFT JOIN products p ON ip.product_id = p.id
            LEFT JOIN quality_checks qc ON ip.id = qc.inspection_plan_id
            WHERE ip.company_id = ?
            GROUP BY ip.id
            ORDER BY ip.next_inspection_date ASC
        ", [$this->user['company_id']]);
    }

    private function getQualityChecks() {
        return $this->db->query("
            SELECT
                qc.*,
                qc.inspection_type,
                qc.result,
                qc.inspection_date,
                qc.critical_defects,
                qc.major_defects,
                qc.minor_defects,
                p.product_name,
                u.first_name,
                u.last_name
            FROM quality_checks qc
            LEFT JOIN products p ON qc.product_id = p.id
            LEFT JOIN users u ON qc.inspector_id = u.id
            WHERE qc.company_id = ?
            ORDER BY qc.inspection_date DESC
        ", [$this->user['company_id']]);
    }

    private function getControlCharts() {
        return $this->db->query("
            SELECT
                cc.*,
                cc.chart_type,
                cc.parameter_name,
                cc.ucl,
                cc.lcl,
                cc.cl,
                cc.last_updated,
                COUNT(cp.id) as data_points
            FROM control_charts cc
            LEFT JOIN control_chart_points cp ON cc.id = cp.chart_id
            WHERE cc.company_id = ?
            GROUP BY cc.id
            ORDER BY cc.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getQualityStandards() {
        return $this->db->query("
            SELECT
                qs.*,
                qs.standard_name,
                qs.standard_code,
                qs.version,
                qs.effective_date,
                qs.status
            FROM quality_standards qs
            WHERE qs.company_id = ?
            ORDER BY qs.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSamplingPlans() {
        return $this->db->query("
            SELECT
                sp.*,
                sp.plan_name,
                sp.sample_size,
                sp.acceptance_number,
                sp.rejection_number,
                sp.aql,
                sp.last_used
            FROM sampling_plans sp
            WHERE sp.company_id = ?
            ORDER BY sp.last_used DESC
        ", [$this->user['company_id']]);
    }

    private function getQualityGates() {
        return $this->db->query("
            SELECT
                qg.*,
                qg.gate_name,
                qg.stage,
                qg.criteria,
                qg.approval_required,
                qg.is_active
            FROM quality_gates qg
            WHERE qg.company_id = ?
            ORDER BY qg.stage ASC
        ", [$this->user['company_id']]);
    }

    private function getAuditSchedule() {
        return $this->db->query("
            SELECT
                a.*,
                a.audit_type,
                a.scheduled_date,
                a.auditor_name,
                a.scope,
                a.status,
                DATEDIFF(a.scheduled_date, CURDATE()) as days_until_audit
            FROM audits a
            WHERE a.company_id = ?
            ORDER BY a.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getAuditFindings() {
        return $this->db->query("
            SELECT
                af.*,
                af.finding_type,
                af.severity,
                af.description,
                af.recommendation,
                af.status,
                a.audit_type,
                a.auditor_name
            FROM audit_findings af
            LEFT JOIN audits a ON af.audit_id = a.id
            WHERE af.company_id = ?
            ORDER BY af.severity DESC, af.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAuditReports() {
        return $this->db->query("
            SELECT
                ar.*,
                ar.report_title,
                ar.audit_period,
                ar.overall_rating,
                ar.generated_date,
                a.audit_type,
                a.auditor_name
            FROM audit_reports ar
            LEFT JOIN audits a ON ar.audit_id = a.id
            WHERE ar.company_id = ?
            ORDER BY ar.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAuditChecklists() {
        return $this->db->query("
            SELECT
                ac.*,
                ac.checklist_name,
                ac.audit_type,
                ac.version,
                COUNT(aci.id) as total_items
            FROM audit_checklists ac
            LEFT JOIN audit_checklist_items aci ON ac.id = aci.checklist_id
            WHERE ac.company_id = ?
            GROUP BY ac.id
            ORDER BY ac.version DESC
        ", [$this->user['company_id']]);
    }

    private function getAuditTeam() {
        return $this->db->query("
            SELECT
                u.*,
                u.first_name,
                u.last_name,
                u.email,
                at.certifications,
                at.audit_experience_years,
                at.specializations
            FROM users u
            LEFT JOIN audit_team at ON u.id = at.user_id
            WHERE u.company_id = ? AND at.is_active = true
            ORDER BY at.audit_experience_years DESC
        ", [$this->user['company_id']]);
    }

    private function getAuditMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_audits,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_audits,
                COUNT(CASE WHEN overall_rating = 'excellent' THEN 1 END) as excellent_audits,
                COUNT(CASE WHEN overall_rating = 'good' THEN 1 END) as good_audits,
                COUNT(CASE WHEN overall_rating = 'needs_improvement' THEN 1 END) as needs_improvement_audits,
                AVG(CASE WHEN overall_rating = 'excellent' THEN 5
                         WHEN overall_rating = 'good' THEN 4
                         WHEN overall_rating = 'satisfactory' THEN 3
                         WHEN overall_rating = 'needs_improvement' THEN 2
                         ELSE 1 END) as avg_rating
            FROM audits
            WHERE company_id = ? AND audit_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
        ", [$this->user['company_id']]);
    }

    private function getNonConformanceList() {
        return $this->db->query("
            SELECT
                nc.*,
                nc.non_conformance_type,
                nc.severity,
                nc.description,
                nc.root_cause,
                nc.status,
                nc.reported_date,
                nc.target_resolution_date,
                p.product_name,
                u1.first_name as reported_by_name,
                u2.first_name as assigned_to_name
            FROM non_conformances nc
            LEFT JOIN products p ON nc.product_id = p.id
            LEFT JOIN users u1 ON nc.reported_by = u1.id
            LEFT JOIN users u2 ON nc.assigned_to = u2.id
            WHERE nc.company_id = ?
            ORDER BY nc.severity DESC, nc.reported_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCorrectiveActions() {
        return $this->db->query("
            SELECT
                ca.*,
                ca.action_description,
                ca.responsible_person,
                ca.target_completion_date,
                ca.actual_completion_date,
                ca.effectiveness_rating,
                ca.status,
                nc.non_conformance_type,
                nc.severity
            FROM corrective_actions ca
            LEFT JOIN non_conformances nc ON ca.non_conformance_id = nc.id
            WHERE ca.company_id = ?
            ORDER BY ca.target_completion_date ASC
        ", [$this->user['company_id']]);
    }

    private function getPreventiveActions() {
        return $this->db->query("
            SELECT
                pa.*,
                pa.action_description,
                pa.potential_risk,
                pa.responsible_person,
                pa.target_completion_date,
                pa.actual_completion_date,
                pa.status
            FROM preventive_actions pa
            WHERE pa.company_id = ?
            ORDER BY pa.target_completion_date ASC
        ", [$this->user['company_id']]);
    }

    private function getRootCauseAnalysis() {
        return $this->db->query("
            SELECT
                rca.*,
                rca.problem_description,
                rca.root_cause,
                rca.analysis_method,
                rca.completed_date,
                nc.non_conformance_type,
                nc.severity
            FROM root_cause_analysis rca
            LEFT JOIN non_conformances nc ON rca.non_conformance_id = nc.id
            WHERE rca.company_id = ?
            ORDER BY rca.completed_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCAPAWorkflow() {
        return $this->db->query("
            SELECT
                cw.*,
                cw.workflow_name,
                cw.current_stage,
                cw.initiated_date,
                cw.target_completion_date,
                cw.status,
                COUNT(ca.id) as corrective_actions,
                COUNT(pa.id) as preventive_actions
            FROM capa_workflows cw
            LEFT JOIN corrective_actions ca ON cw.id = ca.capa_workflow_id
            LEFT JOIN preventive_actions pa ON cw.id = pa.capa_workflow_id
            WHERE cw.company_id = ?
            GROUP BY cw.id
            ORDER BY cw.initiated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getNonConformanceMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_nc,
                COUNT(CASE WHEN status = 'open' THEN 1 END) as open_nc,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_nc,
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_nc,
                COUNT(CASE WHEN severity = 'major' THEN 1 END) as major_nc,
                COUNT(CASE WHEN severity = 'minor' THEN 1 END) as minor_nc,
                AVG(CASE WHEN status = 'closed' THEN DATEDIFF(resolution_date, reported_date) END) as avg_resolution_days
            FROM non_conformances
            WHERE company_id = ? AND reported_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ", [$this->user['company_id']]);
    }

    private function getCAPAInitiatives() {
        return $this->db->query("
            SELECT
                ci.*,
                ci.initiative_name,
                ci.problem_description,
                ci.initiated_date,
                ci.target_completion_date,
                ci.actual_completion_date,
                ci.status,
                ci.effectiveness_rating,
                COUNT(ca.id) as corrective_actions,
                COUNT(pa.id) as preventive_actions
            FROM capa_initiatives ci
            LEFT JOIN corrective_actions ca ON ci.id = ca.capa_initiative_id
            LEFT JOIN preventive_actions pa ON ci.id = pa.capa_initiative_id
            WHERE ci.company_id = ?
            GROUP BY ci.id
            ORDER BY ci.initiated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCAPAEffectiveness() {
        return $this->db->query("
            SELECT
                ci.initiative_name,
                ci.effectiveness_rating,
                ci.actual_completion_date,
                COUNT(ca.id) as actions_implemented,
                AVG(ca.effectiveness_rating) as avg_action_effectiveness
            FROM capa_initiatives ci
            LEFT JOIN corrective_actions ca ON ci.id = ca.capa_initiative_id
            WHERE ci.company_id = ? AND ci.status = 'completed'
            GROUP BY ci.id
            ORDER BY ci.actual_completion_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCAPATimeline() {
        return $this->db->query("
            SELECT
                DATE(ci.initiated_date) as date,
                COUNT(*) as initiated,
                COUNT(CASE WHEN ci.status = 'completed' THEN 1 END) as completed,
                AVG(ci.effectiveness_rating) as avg_effectiveness
            FROM capa_initiatives ci
            WHERE ci.company_id = ? AND ci.initiated_date >= DATE_SUB(CURDATE(), INTERVAL 180 DAY)
            GROUP BY DATE(ci.initiated_date)
            ORDER BY date ASC
        ", [$this->user['company_id']]);
    }

    private function getCAPAResources() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                COUNT(ca.id) as assigned_actions,
                AVG(ca.effectiveness_rating) as avg_effectiveness,
                COUNT(CASE WHEN ca.status = 'completed' THEN 1 END) as completed_actions
            FROM users u
            LEFT JOIN corrective_actions ca ON u.id = ca.responsible_person_id
            WHERE u.company_id = ?
            GROUP BY u.id
            ORDER BY assigned_actions DESC
        ", [$this->user['company_id']]);
    }

    private function getCAPAReporting() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_initiatives,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_initiatives,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_initiatives,
                ROUND(AVG(effectiveness_rating), 2) as avg_effectiveness,
                ROUND(AVG(DATEDIFF(actual_completion_date, initiated_date)), 1) as avg_completion_days
            FROM capa_initiatives
            WHERE company_id = ? AND initiated_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
        ", [$this->user['company_id']]);
    }

    private function getCAPADashboard() {
        return [
            'summary' => $this->getCAPAReporting(),
            'by_category' => $this->getCAPAByCategory(),
            'effectiveness_trend' => $this->getCAPAEffectivenessTrend(),
            'overdue_actions' => $this->getOverdueCAPAActions()
        ];
    }

    private function getCAPAByCategory() {
        return $this->db->query("
            SELECT
                category,
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                ROUND(AVG(effectiveness_rating), 2) as avg_effectiveness
            FROM capa_initiatives
            WHERE company_id = ?
            GROUP BY category
            ORDER BY total DESC
        ", [$this->user['company_id']]);
    }

    private function getCAPAEffectivenessTrend() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(actual_completion_date, '%Y-%m') as month,
                AVG(effectiveness_rating) as avg_effectiveness,
                COUNT(*) as completed_count
            FROM capa_initiatives
            WHERE company_id = ? AND status = 'completed' AND actual_completion_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
            GROUP BY DATE_FORMAT(actual_completion_date, '%Y-%m')
            ORDER BY month ASC
        ", [$this->user['company_id']]);
    }

    private function getOverdueCAPAActions() {
        return $this->db->query("
            SELECT
                ca.action_description,
                ca.target_completion_date,
                ca.responsible_person,
                DATEDIFF(CURDATE(), ca.target_completion_date) as days_overdue,
                ci.initiative_name
            FROM corrective_actions ca
            LEFT JOIN capa_initiatives ci ON ca.capa_initiative_id = ci.id
            WHERE ca.company_id = ? AND ca.status != 'completed' AND ca.target_completion_date < CURDATE()
            ORDER BY days_overdue DESC
        ", [$this->user['company_id']]);
    }

    private function getISOStandards() {
        return [
            'iso_9001' => [
                'name' => 'ISO 9001:2015',
                'description' => 'Quality Management Systems',
                'requirements' => ['Quality Policy', 'Quality Objectives', 'Management Review', 'Internal Audits']
            ],
            'iso_14001' => [
                'name' => 'ISO 14001:2015',
                'description' => 'Environmental Management Systems',
                'requirements' => ['Environmental Policy', 'Planning', 'Implementation', 'Checking', 'Management Review']
            ],
            'iso_45001' => [
                'name' => 'ISO 45001:2018',
                'description' => 'Occupational Health and Safety',
                'requirements' => ['OH&S Policy', 'Planning', 'Support', 'Operation', 'Performance Evaluation']
            ]
        ];
    }

    private function getComplianceStatus() {
        return $this->db->query("
            SELECT
                cs.*,
                cs.standard_name,
                cs.current_version,
                cs.certification_status,
                cs.certification_expiry,
                cs.last_audit_date,
                cs.next_audit_date
            FROM compliance_status cs
            WHERE cs.company_id = ?
            ORDER BY cs.certification_expiry ASC
        ", [$this->user['company_id']]);
    }

    private function getCertificationTracking() {
        return $this->db->query("
            SELECT
                ct.*,
                ct.certification_body,
                ct.certification_type,
                ct.issue_date,
                ct.expiry_date,
                ct.status,
                DATEDIFF(ct.expiry_date, CURDATE()) as days_until_expiry
            FROM certification_tracking ct
            WHERE ct.company_id = ?
            ORDER BY ct.expiry_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceAudits() {
        return $this->db->query("
            SELECT
                ca.*,
                ca.audit_type,
                ca.audit_date,
                ca.auditor_name,
                ca.findings_count,
                ca.non_conformances,
                ca.overall_rating
            FROM compliance_audits ca
            WHERE ca.company_id = ?
            ORDER BY ca.audit_date DESC
        ", [$this->user['company_id']]);
    }

    private function getGapAnalysis() {
        return $this->db->query("
            SELECT
                ga.*,
                ga.standard_name,
                ga.requirement,
                ga.current_status,
                ga.gap_description,
                ga.priority,
                ga.target_completion_date
            FROM gap_analysis ga
            WHERE ga.company_id = ?
            ORDER BY ga.priority DESC, ga.target_completion_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN certification_status = 'active' THEN 1 END) as active_certifications,
                COUNT(CASE WHEN certification_status = 'expired' THEN 1 END) as expired_certifications,
                COUNT(CASE WHEN overall_rating = 'excellent' THEN 1 END) as excellent_audits,
                COUNT(CASE WHEN overall_rating = 'good' THEN 1 END) as good_audits,
                COUNT(CASE WHEN overall_rating = 'needs_improvement' THEN 1 END) as needs_improvement_audits,
                AVG(CASE WHEN overall_rating = 'excellent' THEN 5
                         WHEN overall_rating = 'good' THEN 4
                         WHEN overall_rating = 'satisfactory' THEN 3
                         WHEN overall_rating = 'needs_improvement' THEN 2
                         ELSE 1 END) as avg_compliance_score
            FROM compliance_status cs
            LEFT JOIN compliance_audits ca ON cs.id = ca.compliance_status_id
            WHERE cs.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSupplierEvaluations() {
        return $this->db->query("
            SELECT
                se.*,
                se.supplier_name,
                se.evaluation_date,
                se.quality_score,
                se.delivery_score,
                se.overall_score,
                se.evaluator_name
            FROM supplier_evaluations se
            WHERE se.company_id = ?
            ORDER BY se.evaluation_date DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierScorecards() {
        return $this->db->query("
            SELECT
                ss.*,
                ss.supplier_name,
                ss.overall_score,
                ss.quality_score,
                ss.delivery_score,
                ss.cost_score,
                ss.last_updated
            FROM supplier_scorecards ss
            WHERE ss.company_id = ?
            ORDER BY ss.overall_score DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierAudits() {
        return $this->db->query("
            SELECT
                sa.*,
                sa.supplier_name,
                sa.audit_date,
                sa.audit_type,
                sa.findings_count,
                sa.overall_rating,
                sa.auditor_name
            FROM supplier_audits sa
            WHERE sa.company_id = ?
            ORDER BY sa.audit_date DESC
        ", [$this->user['company_id']]);
    }

    private function getQualityAgreements() {
        return $this->db->query("
            SELECT
                qa.*,
                qa.supplier_name,
                qa.agreement_type,
                qa.effective_date,
                qa.expiry_date,
                qa.status
            FROM quality_agreements qa
            WHERE qa.company_id = ?
            ORDER BY qa.expiry_date ASC
        ", [$this->user['company_id']]);
    }

    private function getSupplierPerformance() {
        return $this->db->query("
            SELECT
                sp.supplier_name,
                AVG(sp.quality_score) as avg_quality,
                AVG(sp.delivery_score) as avg_delivery,
                AVG(sp.cost_score) as avg_cost,
                COUNT(sp.id) as evaluation_count,
                MAX(sp.evaluation_date) as last_evaluation
            FROM supplier_performance sp
            WHERE sp.company_id = ?
            GROUP BY sp.supplier_name
            ORDER BY avg_quality DESC
        ", [$this->user['company_id']]);
    }

    private function getSupplierDevelopment() {
        return $this->db->query("
            SELECT
                sd.*,
                sd.supplier_name,
                sd.development_area,
                sd.target_date,
                sd.status,
                sd.assigned_to
            FROM supplier_development sd
            WHERE sd.company_id = ?
            ORDER BY sd.target_date ASC
        ", [$this->user['company_id']]);
    }

    private function getDocumentRepository() {
        return $this->db->query("
            SELECT
                dr.*,
                dr.document_name,
                dr.document_type,
                dr.version,
                dr.status,
                dr.last_updated,
                dr.approved_by
            FROM document_repository dr
            WHERE dr.company_id = ?
            ORDER BY dr.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getDocumentVersions() {
        return $this->db->query("
            SELECT
                dv.*,
                dv.document_name,
                dv.version_number,
                dv.change_description,
                dv.created_date,
                dv.created_by,
                dv.approval_status
            FROM document_versions dv
            WHERE dv.company_id = ?
            ORDER BY dv.created_date DESC
        ", [$this->user['company_id']]);
    }

    private function getApprovalWorkflow() {
        return $this->db->query("
            SELECT
                aw.*,
                aw.workflow_name,
                aw.document_type,
                aw.approval_levels,
                aw.is_active
            FROM approval_workflows aw
            WHERE aw.company_id = ?
            ORDER BY aw.document_type ASC
        ", [$this->user['company_id']]);
    }

    private function getDocumentDistribution() {
        return $this->db->query("
            SELECT
                dd.*,
                dd.document_name,
                dd.recipient_name,
                dd.distribution_date,
                dd.acknowledgement_date,
                dd.status
            FROM document_distribution dd
            WHERE dd.company_id = ?
            ORDER BY dd.distribution_date DESC
        ", [$this->user['company_id']]);
    }

    private function getTrainingRecords() {
        return $this->db->query("
            SELECT
                tr.*,
                tr.employee_name,
                tr.document_name,
                tr.training_date,
                tr.completion_status,
                tr.certification_expiry
            FROM training_records tr
            WHERE tr.company_id = ?
            ORDER BY tr.training_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDocumentMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_documents,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_documents,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_documents,
                COUNT(CASE WHEN status = 'obsolete' THEN 1 END) as obsolete_documents,
                AVG(version) as avg_versions,
                COUNT(DISTINCT training_records.employee_id) as trained_employees
            FROM document_repository dr
            LEFT JOIN training_records tr ON dr.id = tr.document_id
            WHERE dr.company_id = ?
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function createQualityCheck() {
        $this->requirePermission('quality.control.create');

        $data = $this->validateRequest([
            'product_id' => 'required|integer',
            'inspection_type' => 'required|string',
            'batch_lot_number' => 'string',
            'sample_size' => 'integer',
            'critical_defects' => 'integer',
            'major_defects' => 'integer',
            'minor_defects' => 'integer',
            'notes' => 'string'
        ]);

        try {
            $this->db->beginTransaction();

            // Determine result based on defects
            $totalDefects = ($data['critical_defects'] ?? 0) + ($data['major_defects'] ?? 0) + ($data['minor_defects'] ?? 0);
            $result = $totalDefects > 0 ? 'fail' : 'pass';

            // Create quality check
            $checkId = $this->db->insert('quality_checks', [
                'company_id' => $this->user['company_id'],
                'product_id' => $data['product_id'],
                'inspection_type' => $data['inspection_type'],
                'batch_lot_number' => $data['batch_lot_number'] ?? '',
                'sample_size' => $data['sample_size'] ?? 1,
                'critical_defects' => $data['critical_defects'] ?? 0,
                'major_defects' => $data['major_defects'] ?? 0,
                'minor_defects' => $data['minor_defects'] ?? 0,
                'result' => $result,
                'notes' => $data['notes'] ?? '',
                'inspector_id' => $this->user['id'],
                'inspection_date' => date('Y-m-d H:i:s')
            ]);

            // Create non-conformance if defects found
            if ($totalDefects > 0) {
                $this->createNonConformance($checkId, $data, $totalDefects);
            }

            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'check_id' => $checkId,
                'result' => $result,
                'message' => 'Quality check completed successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function createNonConformance($checkId, $data, $totalDefects) {
        $severity = 'minor';
        if (($data['critical_defects'] ?? 0) > 0) {
            $severity = 'critical';
        } elseif (($data['major_defects'] ?? 0) > 0) {
            $severity = 'major';
        }

        $this->db->insert('non_conformances', [
            'company_id' => $this->user['company_id'],
            'quality_check_id' => $checkId,
            'product_id' => $data['product_id'],
            'non_conformance_type' => 'product_defect',
            'severity' => $severity,
            'description' => "Quality check failed with {$totalDefects} defect(s)",
            'status' => 'open',
            'reported_by' => $this->user['id'],
            'reported_date' => date('Y-m-d H:i:s')
        ]);
    }

    public function createNonConformance() {
        $this->requirePermission('quality.non_conformance.create');

        $data = $this->validateRequest([
            'product_id' => 'integer',
            'non_conformance_type' => 'required|string',
            'severity' => 'required|string',
            'description' => 'required|string',
            'root_cause' => 'string',
            'immediate_action' => 'string',
            'assigned_to' => 'integer'
        ]);

        try {
            $ncId = $this->db->insert('non_conformances', [
                'company_id' => $this->user['company_id'],
                'product_id' => $data['product_id'] ?? null,
                'non_conformance_type' => $data['non_conformance_type'],
                'severity' => $data['severity'],
                'description' => $data['description'],
                'root_cause' => $data['root_cause'] ?? '',
                'immediate_action' => $data['immediate_action'] ?? '',
                'status' => 'open',
                'reported_by' => $this->user['id'],
                'assigned_to' => $data['assigned_to'] ?? null,
                'reported_date' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'non_conformance_id' => $ncId,
                'message' => 'Non-conformance reported successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function scheduleAudit() {
        $this->requirePermission('quality.audit.schedule');

        $data = $this->validateRequest([
            'audit_type' => 'required|string',
            'scheduled_date' => 'required|date',
            'auditor_name' => 'required|string',
            'scope' => 'required|string',
            'objectives' => 'string',
            'checklist_id' => 'integer'
        ]);

        try {
            $auditId = $this->db->insert('audits', [
                'company_id' => $this->user['company_id'],
                'audit_type' => $data['audit_type'],
                'scheduled_date' => $data['scheduled_date'],
                'auditor_name' => $data['auditor_name'],
                'scope' => $data['scope'],
                'objectives' => $data['objectives'] ?? '',
                'checklist_id' => $data['checklist_id'] ?? null,
                'status' => 'scheduled',
                'created_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'audit_id' => $auditId,
                'message' => 'Audit scheduled successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createCAPA() {
        $this->requirePermission('quality.capa.create');

        $data = $this->validateRequest([
            'non_conformance_id' => 'integer',
            'problem_description' => 'required|string',
            'category' => 'string',
            'priority' => 'required|string',
            'assigned_to' => 'integer',
            'target_completion_date' => 'required|date'
        ]);

        try {
            $capaId = $this->db->insert('capa_initiatives', [
                'company_id' => $this->user['company_id'],
                'non_conformance_id' => $data['non_conformance_id'] ?? null,
                'initiative_name' => 'CAPA-' . date('Y-m-d') . '-' . rand(1000, 9999),
                'problem_description' => $data['problem_description'],
                'category' => $data['category'] ?? 'general',
                'priority' => $data['priority'],
                'status' => 'initiated',
                'assigned_to' => $data['assigned_to'] ?? null,
                'target_completion_date' => $data['target_completion_date'],
                'initiated_date' => date('Y-m-d H:i:s'),
                'created_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'capa_id' => $capaId,
                'message' => 'CAPA initiative created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateQualityAlert() {
        $this->requirePermission('quality.alert.update');

        $data = $this->validateRequest([
            'alert_id' => 'required|integer',
            'status' => 'required|string',
            'resolution_notes' => 'string'
        ]);

        try {
            $this->db->update('quality_alerts', [
                'status' => $data['status'],
                'resolution_notes' => $data['resolution_notes'] ?? '',
                'resolved_by' => $this->user['id'],
                'resolved_at' => date('Y-m-d H:i:s')
            ], 'id = ? AND company_id = ?', [
                $data['alert_id'],
                $this->user['company_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Quality alert updated successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateQualityReport() {
        $this->requirePermission('quality.reporting.generate');

        $data = $this->validateRequest([
            'report_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'format' => 'required|string'
        ]);

        try {
            $reportData = [];

            switch ($data['report_type']) {
                case 'quality_overview':
                    $reportData = $this->getQualityOverview();
                    break;
                case 'non_conformance_summary':
                    $reportData = $this->getNonConformanceMetrics();
                    break;
                case 'audit_summary':
                    $reportData = $this->getAuditMetrics();
                    break;
                case 'supplier_performance':
                    $reportData = $this->getSupplierPerformance();
                    break;
                default:
                    $this->jsonResponse(['error' => 'Invalid report type'], 400);
            }

            // Create report record
            $reportId = $this->db->insert('quality_reports', [
                'company_id' => $this->user['company_id'],
                'report_type' => $data['report_type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'format' => $data['format'],
                'generated_by' => $this->user['id'],
                'generated_at' => date('Y-m-d H:i:s'),
                'data' => json_encode($reportData)
            ]);

            $this->jsonResponse([
                'success' => true,
                'report_id' => $reportId,
                'data' => $reportData,
                'message' => 'Quality report generated successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
?>
