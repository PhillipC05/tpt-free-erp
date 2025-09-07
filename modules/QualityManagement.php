<?php
/**
 * TPT Free ERP - Quality Management Module
 * Complete quality control, audit management, and compliance system
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
            'title' => 'Quality Management',
            'quality_overview' => $this->getQualityOverview(),
            'quality_metrics' => $this->getQualityMetrics(),
            'audit_schedule' => $this->getAuditSchedule(),
            'non_conformance_status' => $this->getNonConformanceStatus(),
            'capa_status' => $this->getCAPAStatus(),
            'compliance_status' => $this->getComplianceStatus(),
            'quality_alerts' => $this->getQualityAlerts(),
            'quality_analytics' => $this->getQualityAnalytics()
        ];

        $this->render('modules/quality_management/dashboard', $data);
    }

    /**
     * Quality control processes
     */
    public function qualityControl() {
        $this->requirePermission('quality.control.view');

        $data = [
            'title' => 'Quality Control',
            'quality_plans' => $this->getQualityPlans(),
            'inspection_criteria' => $this->getInspectionCriteria(),
            'quality_checks' => $this->getQualityChecks(),
            'sampling_plans' => $this->getSamplingPlans(),
            'test_methods' => $this->getTestMethods(),
            'quality_gates' => $this->getQualityGates(),
            'quality_reports' => $this->getQualityReports(),
            'quality_control_analytics' => $this->getQualityControlAnalytics()
        ];

        $this->render('modules/quality_management/quality_control', $data);
    }

    /**
     * Audit management
     */
    public function auditManagement() {
        $this->requirePermission('quality.audit.view');

        $data = [
            'title' => 'Audit Management',
            'audit_schedule' => $this->getAuditSchedule(),
            'audit_plans' => $this->getAuditPlans(),
            'audit_checklists' => $this->getAuditChecklists(),
            'audit_findings' => $this->getAuditFindings(),
            'audit_reports' => $this->getAuditReports(),
            'audit_follow_ups' => $this->getAuditFollowUps(),
            'audit_analytics' => $this->getAuditAnalytics(),
            'audit_templates' => $this->getAuditTemplates()
        ];

        $this->render('modules/quality_management/audit_management', $data);
    }

    /**
     * Non-conformance tracking
     */
    public function nonConformance() {
        $this->requirePermission('quality.nonconformance.view');

        $data = [
            'title' => 'Non-Conformance Tracking',
            'non_conformance_records' => $this->getNonConformanceRecords(),
            'non_conformance_categories' => $this->getNonConformanceCategories(),
            'root_cause_analysis' => $this->getRootCauseAnalysis(),
            'containment_actions' => $this->getContainmentActions(),
            'non_conformance_trends' => $this->getNonConformanceTrends(),
            'non_conformance_reports' => $this->getNonConformanceReports(),
            'non_conformance_analytics' => $this->getNonConformanceAnalytics(),
            'non_conformance_templates' => $this->getNonConformanceTemplates()
        ];

        $this->render('modules/quality_management/non_conformance', $data);
    }

    /**
     * CAPA (Corrective Action Preventive Action)
     */
    public function capa() {
        $this->requirePermission('quality.capa.view');

        $data = [
            'title' => 'CAPA Management',
            'capa_records' => $this->getCAPARecords(),
            'capa_plans' => $this->getCAPAPlans(),
            'capa_effectiveness' => $this->getCAPAEffectiveness(),
            'capa_verification' => $this->getCAPAVerification(),
            'capa_trends' => $this->getCAPATrends(),
            'capa_reports' => $this->getCAPAReports(),
            'capa_analytics' => $this->getCAPAAnalytics(),
            'capa_templates' => $this->getCAPATemplates()
        ];

        $this->render('modules/quality_management/capa', $data);
    }

    /**
     * ISO compliance tracking
     */
    public function isoCompliance() {
        $this->requirePermission('quality.iso.view');

        $data = [
            'title' => 'ISO Compliance',
            'iso_standards' => $this->getISOStandards(),
            'compliance_requirements' => $this->getComplianceRequirements(),
            'compliance_assessments' => $this->getComplianceAssessments(),
            'compliance_gaps' => $this->getComplianceGaps(),
            'compliance_actions' => $this->getComplianceActions(),
            'compliance_reports' => $this->getComplianceReports(),
            'compliance_analytics' => $this->getComplianceAnalytics(),
            'compliance_templates' => $this->getComplianceTemplates()
        ];

        $this->render('modules/quality_management/iso_compliance', $data);
    }

    /**
     * Quality standards management
     */
    public function qualityStandards() {
        $this->requirePermission('quality.standards.view');

        $data = [
            'title' => 'Quality Standards',
            'quality_standards' => $this->getQualityStandards(),
            'standard_requirements' => $this->getStandardRequirements(),
            'standard_compliance' => $this->getStandardCompliance(),
            'standard_updates' => $this->getStandardUpdates(),
            'standard_training' => $this->getStandardTraining(),
            'standard_audits' => $this->getStandardAudits(),
            'standard_reports' => $this->getStandardReports(),
            'standard_analytics' => $this->getStandardAnalytics()
        ];

        $this->render('modules/quality_management/quality_standards', $data);
    }

    /**
     * Statistical process control
     */
    public function statisticalProcess() {
        $this->requirePermission('quality.spc.view');

        $data = [
            'title' => 'Statistical Process Control',
            'control_charts' => $this->getControlCharts(),
            'process_capability' => $this->getProcessCapability(),
            'control_limits' => $this->getControlLimits(),
            'out_of_control' => $this->getOutOfControl(),
            'process_variation' => $this->getProcessVariation(),
            'spc_reports' => $this->getSPCReports(),
            'spc_analytics' => $this->getSPCAnalytics(),
            'spc_templates' => $this->getSPCTemplates()
        ];

        $this->render('modules/quality_management/statistical_process', $data);
    }

    /**
     * Quality analytics and reporting
     */
    public function analytics() {
        $this->requirePermission('quality.analytics.view');

        $data = [
            'title' => 'Quality Analytics',
            'quality_trends' => $this->getQualityTrends(),
            'defect_analysis' => $this->getDefectAnalysis(),
            'quality_costs' => $this->getQualityCosts(),
            'supplier_quality' => $this->getSupplierQuality(),
            'process_performance' => $this->getProcessPerformance(),
            'quality_dashboards' => $this->getQualityDashboards(),
            'predictive_quality' => $this->getPredictiveQuality(),
            'quality_benchmarks' => $this->getQualityBenchmarks()
        ];

        $this->render('modules/quality_management/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getQualityOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT qc.id) as total_quality_checks,
                COUNT(CASE WHEN qc.result = 'pass' THEN 1 END) as passed_checks,
                COUNT(CASE WHEN qc.result = 'fail' THEN 1 END) as failed_checks,
                ROUND((COUNT(CASE WHEN qc.result = 'pass' THEN 1 END) / NULLIF(COUNT(qc.id), 0)) * 100, 2) as quality_rate,
                COUNT(DISTINCT nc.id) as total_non_conformances,
                COUNT(CASE WHEN nc.status = 'open' THEN 1 END) as open_non_conformances,
                COUNT(DISTINCT a.id) as total_audits,
                COUNT(CASE WHEN a.status = 'scheduled' THEN 1 END) as scheduled_audits,
                COUNT(DISTINCT capa.id) as total_capa,
                COUNT(CASE WHEN capa.status = 'open' THEN 1 END) as open_capa
            FROM quality_checks qc
            LEFT JOIN non_conformances nc ON nc.quality_check_id = qc.id
            LEFT JOIN audits a ON a.company_id = qc.company_id
            LEFT JOIN capa_records capa ON capa.company_id = qc.company_id
            WHERE qc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getQualityMetrics() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(qc.check_date, '%Y-%m') as month,
                COUNT(qc.id) as total_checks,
                COUNT(CASE WHEN qc.result = 'pass' THEN 1 END) as passed_checks,
                COUNT(CASE WHEN qc.result = 'fail' THEN 1 END) as failed_checks,
                ROUND((COUNT(CASE WHEN qc.result = 'pass' THEN 1 END) / NULLIF(COUNT(qc.id), 0)) * 100, 2) as quality_rate,
                AVG(qc.defect_rate) as avg_defect_rate,
                COUNT(nc.id) as non_conformances
            FROM quality_checks qc
            LEFT JOIN non_conformances nc ON nc.quality_check_id = qc.id
            WHERE qc.company_id = ? AND qc.check_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(qc.check_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getAuditSchedule() {
        return $this->db->query("
            SELECT
                a.*,
                at.audit_type_name,
                u.first_name as lead_auditor_first,
                u.last_name as lead_auditor_last,
                a.scheduled_date,
                a.status,
                TIMESTAMPDIFF(DAY, CURDATE(), a.scheduled_date) as days_until_audit
            FROM audits a
            JOIN audit_types at ON a.audit_type_id = at.id
            LEFT JOIN users u ON a.lead_auditor_id = u.id
            WHERE a.company_id = ? AND a.status IN ('planned', 'scheduled')
            ORDER BY a.scheduled_date ASC
        ", [$this->user['company_id']]);
    }

    private function getNonConformanceStatus() {
        return $this->db->query("
            SELECT
                status,
                COUNT(*) as count,
                SUM(severity_score) as total_severity,
                AVG(severity_score) as avg_severity,
                COUNT(CASE WHEN TIMESTAMPDIFF(DAY, reported_date, CURDATE()) > 30 THEN 1 END) as overdue_30_days
            FROM non_conformances
            WHERE company_id = ?
            GROUP BY status
            ORDER BY count DESC
        ", [$this->user['company_id']]);
    }

    private function getCAPAStatus() {
        return $this->db->query("
            SELECT
                status,
                COUNT(*) as count,
                AVG(progress_percentage) as avg_progress,
                COUNT(CASE WHEN TIMESTAMPDIFF(DAY, target_completion_date, CURDATE()) > 0 AND status != 'completed' THEN 1 END) as overdue
            FROM capa_records
            WHERE company_id = ?
            GROUP BY status
            ORDER BY count DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceStatus() {
        return $this->db->query("
            SELECT
                cs.*,
                cs.standard_name,
                cs.compliance_percentage,
                cs.last_assessment_date,
                cs.next_assessment_date,
                TIMESTAMPDIFF(DAY, CURDATE(), cs.next_assessment_date) as days_until_next
            FROM compliance_status cs
            WHERE cs.company_id = ?
            ORDER BY cs.compliance_percentage ASC
        ", [$this->user['company_id']]);
    }

    private function getQualityAlerts() {
        return $this->db->query("
            SELECT
                qa.*,
                qa.alert_type,
                qa.severity,
                qa.message,
                qa.triggered_at,
                qa.acknowledged_at,
                TIMESTAMPDIFF(MINUTE, qa.triggered_at, NOW()) as minutes_since_trigger
            FROM quality_alerts qa
            WHERE qa.company_id = ? AND qa.status = 'active'
            ORDER BY qa.severity DESC, qa.triggered_at DESC
        ", [$this->user['company_id']]);
    }

    private function getQualityAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(qc.id) as total_checks,
                ROUND((COUNT(CASE WHEN qc.result = 'pass' THEN 1 END) / NULLIF(COUNT(qc.id), 0)) * 100, 2) as overall_quality_rate,
                COUNT(nc.id) as total_non_conformances,
                COUNT(CASE WHEN nc.status = 'open' THEN 1 END) as open_non_conformances,
                COUNT(a.id) as total_audits,
                COUNT(capa.id) as total_capa,
                AVG(cs.compliance_percentage) as avg_compliance,
                COUNT(CASE WHEN qa.status = 'active' THEN 1 END) as active_alerts
            FROM quality_checks qc
            LEFT JOIN non_conformances nc ON nc.company_id = qc.company_id
            LEFT JOIN audits a ON a.company_id = qc.company_id
            LEFT JOIN capa_records capa ON capa.company_id = qc.company_id
            LEFT JOIN compliance_status cs ON cs.company_id = qc.company_id
            LEFT JOIN quality_alerts qa ON qa.company_id = qc.company_id
            WHERE qc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getQualityPlans() {
        return $this->db->query("
            SELECT
                qp.*,
                qp.plan_name,
                qp.applicable_to,
                qp.effective_date,
                qp.review_date,
                qp.status
            FROM quality_plans qp
            WHERE qp.company_id = ?
            ORDER BY qp.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getInspectionCriteria() {
        return $this->db->query("
            SELECT
                ic.*,
                ic.criteria_name,
                ic.specification,
                ic.tolerance_upper,
                ic.tolerance_lower,
                ic.measurement_unit,
                ic.is_critical
            FROM inspection_criteria ic
            WHERE ic.company_id = ?
            ORDER BY ic.is_critical DESC, ic.criteria_name ASC
        ", [$this->user['company_id']]);
    }

    private function getQualityChecks() {
        return $this->db->query("
            SELECT
                qc.*,
                ic.criteria_name,
                u.first_name as inspector_first,
                u.last_name as inspector_last,
                qc.check_date,
                qc.result,
                qc.actual_value,
                qc.defect_rate
            FROM quality_checks qc
            JOIN inspection_criteria ic ON qc.criteria_id = ic.id
            LEFT JOIN users u ON qc.inspector_id = u.id
            WHERE qc.company_id = ?
            ORDER BY qc.check_date DESC
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
                sp.aql_level,
                sp.is_active
            FROM sampling_plans sp
            WHERE sp.company_id = ?
            ORDER BY sp.is_active DESC, sp.plan_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTestMethods() {
        return $this->db->query("
            SELECT
                tm.*,
                tm.method_name,
                tm.test_type,
                tm.equipment_required,
                tm.procedure_document,
                tm.is_accredited
            FROM test_methods tm
            WHERE tm.company_id = ?
            ORDER BY tm.test_type ASC, tm.method_name ASC
        ", [$this->user['company_id']]);
    }

    private function getQualityGates() {
        return $this->db->query("
            SELECT
                qg.*,
                qg.gate_name,
                qg.stage_name,
                qg.criteria_required,
                qg.automatic_approval,
                qg.escalation_required
            FROM quality_gates qg
            WHERE qg.company_id = ?
            ORDER BY qg.stage_order ASC
        ", [$this->user['company_id']]);
    }

    private function getQualityReports() {
        return $this->db->query("
            SELECT
                qr.*,
                qr.report_type,
                qr.report_period,
                qr.generated_date,
                qr.quality_score,
                qr.defect_rate,
                qr.compliance_rate
            FROM quality_reports qr
            WHERE qr.company_id = ?
            ORDER BY qr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getQualityControlAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(qc.id) as total_checks,
                COUNT(CASE WHEN qc.result = 'pass' THEN 1 END) as passed_checks,
                ROUND((COUNT(CASE WHEN qc.result = 'pass' THEN 1 END) / NULLIF(COUNT(qc.id), 0)) * 100, 2) as pass_rate,
                AVG(qc.defect_rate) as avg_defect_rate,
                COUNT(DISTINCT qc.criteria_id) as active_criteria,
                COUNT(DISTINCT qc.inspector_id) as active_inspectors,
                MAX(qc.check_date) as last_check_date
            FROM quality_checks qc
            WHERE qc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAuditPlans() {
        return $this->db->query("
            SELECT
                ap.*,
                ap.plan_name,
                ap.audit_scope,
                ap.planning_date,
                ap.execution_date,
                ap.status
            FROM audit_plans ap
            WHERE ap.company_id = ?
            ORDER BY ap.planning_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAuditChecklists() {
        return $this->db->query("
            SELECT
                ac.*,
                ac.checklist_name,
                ac.audit_type,
                COUNT(aci.id) as item_count,
                ac.is_template
            FROM audit_checklists ac
            LEFT JOIN audit_checklist_items aci ON ac.id = aci.checklist_id
            WHERE ac.company_id = ?
            GROUP BY ac.id
            ORDER BY ac.audit_type ASC, ac.checklist_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAuditFindings() {
        return $this->db->query("
            SELECT
                af.*,
                a.audit_title,
                af.finding_type,
                af.severity,
                af.description,
                af.corrective_action_required,
                af.status
            FROM audit_findings af
            JOIN audits a ON af.audit_id = a.id
            WHERE af.company_id = ?
            ORDER BY af.severity DESC, af.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getAuditReports() {
        return $this->db->query("
            SELECT
                ar.*,
                a.audit_title,
                ar.report_date,
                ar.overall_score,
                ar.compliance_percentage,
                ar.recommendations_count
            FROM audit_reports ar
            JOIN audits a ON ar.audit_id = a.id
            WHERE ar.company_id = ?
            ORDER BY ar.report_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAuditFollowUps() {
        return $this->db->query("
            SELECT
                afu.*,
                af.finding_description,
                afu.follow_up_date,
                afu.status,
                afu.effectiveness_rating,
                TIMESTAMPDIFF(DAY, afu.follow_up_date, CURDATE()) as days_since_followup
            FROM audit_follow_ups afu
            JOIN audit_findings af ON afu.finding_id = af.id
            WHERE afu.company_id = ?
            ORDER BY afu.follow_up_date DESC
        ", [$this->user['company_id']]);
    }

    private function getAuditAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(a.id) as total_audits,
                COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_audits,
                AVG(ar.overall_score) as avg_audit_score,
                COUNT(af.id) as total_findings,
                COUNT(CASE WHEN af.severity = 'critical' THEN 1 END) as critical_findings,
                COUNT(CASE WHEN af.status = 'open' THEN 1 END) as open_findings,
                AVG(ar.compliance_percentage) as avg_compliance
            FROM audits a
            LEFT JOIN audit_reports ar ON a.id = ar.audit_id
            LEFT JOIN audit_findings af ON a.id = af.audit_id
            WHERE a.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAuditTemplates() {
        return $this->db->query("
            SELECT * FROM audit_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getNonConformanceRecords() {
        return $this->db->query("
            SELECT
                nc.*,
                ncc.category_name,
                u.first_name as reported_by_first,
                u.last_name as reported_by_last,
                nc.reported_date,
                nc.severity,
                nc.status,
                nc.root_cause
            FROM non_conformances nc
            JOIN non_conformance_categories ncc ON nc.category_id = ncc.id
            LEFT JOIN users u ON nc.reported_by = u.id
            WHERE nc.company_id = ?
            ORDER BY nc.reported_date DESC
        ", [$this->user['company_id']]);
    }

    private function getNonConformanceCategories() {
        return $this->db->query("
            SELECT
                ncc.*,
                COUNT(nc.id) as nonconformance_count,
                AVG(nc.severity_score) as avg_severity
            FROM non_conformance_categories ncc
            LEFT JOIN non_conformances nc ON ncc.id = nc.category_id
            WHERE ncc.company_id = ?
            GROUP BY ncc.id
            ORDER BY nonconformance_count DESC
        ", [$this->user['company_id']]);
    }

    private function getRootCauseAnalysis() {
        return $this->db->query("
            SELECT
                rca.*,
                nc.description as nonconformance_description,
                rca.root_cause_type,
                rca.analysis_method,
                rca.contributing_factors,
                rca.primary_root_cause
            FROM root_cause_analysis rca
            JOIN non_conformances nc ON rca.nonconformance_id = nc.id
            WHERE rca.company_id = ?
            ORDER BY rca.analysis_date DESC
        ", [$this->user['company_id']]);
    }

    private function getContainmentActions() {
        return $this->db->query("
            SELECT
                ca.*,
                nc.description as nonconformance_description,
                ca.action_description,
                ca.responsible_party,
                ca.target_completion_date,
                ca.actual_completion_date,
                ca.effectiveness_rating
            FROM containment_actions ca
            JOIN non_conformances nc ON ca.nonconformance_id = nc.id
            WHERE ca.company_id = ?
            ORDER BY ca.target_completion_date ASC
        ", [$this->user['company_id']]);
    }

    private function getNonConformanceTrends() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(nc.reported_date, '%Y-%m') as month,
                COUNT(nc.id) as total_nonconformances,
                COUNT(CASE WHEN nc.severity = 'critical' THEN 1 END) as critical_count,
                COUNT(CASE WHEN nc.severity = 'major' THEN 1 END) as major_count,
                COUNT(CASE WHEN nc.severity = 'minor' THEN 1 END) as minor_count,
                AVG(nc.severity_score) as avg_severity,
                COUNT(CASE WHEN nc.status = 'closed' THEN 1 END) as closed_count
            FROM non_conformances nc
            WHERE nc.company_id = ? AND nc.reported_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(nc.reported_date, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getNonConformanceReports() {
        return $this->db->query("
            SELECT
                ncr.*,
                ncr.report_type,
                ncr.report_period,
                ncr.generated_date,
                ncr.total_nonconformances,
                ncr.avg_resolution_time
            FROM nonconformance_reports ncr
            WHERE ncr.company_id = ?
            ORDER BY ncr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getNonConformanceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(nc.id) as total_nonconformances,
                COUNT(CASE WHEN nc.status = 'open' THEN 1 END) as open_nonconformances,
                AVG(nc.severity_score) as avg_severity,
                COUNT(CASE WHEN nc.severity = 'critical' THEN 1 END) as critical_count,
                AVG(TIMESTAMPDIFF(DAY, nc.reported_date, COALESCE(nc.resolved_date, CURDATE()))) as avg_resolution_time,
                COUNT(rca.id) as root_cause_analyses,
                COUNT(ca.id) as containment_actions
            FROM non_conformances nc
            LEFT JOIN root_cause_analysis rca ON nc.id = rca.nonconformance_id
            LEFT JOIN containment_actions ca ON nc.id = ca.nonconformance_id
            WHERE nc.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getNonConformanceTemplates() {
        return $this->db->query("
            SELECT * FROM nonconformance_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getCAPARecords() {
        return $this->db->query("
            SELECT
                cr.*,
                nc.description as nonconformance_description,
                cr.capa_type,
                cr.priority,
                cr.status,
                cr.target_completion_date,
                cr.progress_percentage
            FROM capa_records cr
            LEFT JOIN non_conformances nc ON cr.nonconformance_id = nc.id
            WHERE cr.company_id = ?
            ORDER BY cr.priority DESC, cr.target_completion_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCAPAPlans() {
        return $this->db->query("
            SELECT
                cp.*,
                cr.capa_type,
                cp.plan_description,
                cp.responsible_party,
                cp.target_completion_date,
                cp.actual_completion_date,
                cp.effectiveness_measure
            FROM capa_plans cp
            JOIN capa_records cr ON cp.capa_id = cr.id
            WHERE cp.company_id = ?
            ORDER BY cp.target_completion_date ASC
        ", [$this->user['company_id']]);
    }

    private function getCAPAEffectiveness() {
        return $this->db->query("
            SELECT
                ce.*,
                cr.capa_type,
                ce.effectiveness_rating,
                ce.measurement_method,
                ce.before_improvement,
                ce.after_improvement,
                ce.sustainability_assessment
            FROM capa_effectiveness ce
            JOIN capa_records cr ON ce.capa_id = cr.id
            WHERE ce.company_id = ?
            ORDER BY ce.measured_at DESC
        ", [$this->user['company_id']]);
    }

    private function getCAPAVerification() {
        return $this->db->query("
            SELECT
                cv.*,
                cr.capa_type,
                cv.verification_method,
                cv.verification_result,
                cv.verified_by,
                cv.verification_date,
                cv.next_review_date
            FROM capa_verification cv
            JOIN capa_records cr ON cv.capa_id = cr.id
            WHERE cv.company_id = ?
            ORDER BY cv.verification_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCAPATrends() {
        return $this->db->query("
            SELECT
                DATE_FORMAT(cr.created_at, '%Y-%m') as month,
                COUNT(cr.id) as total_capa,
                COUNT(CASE WHEN cr.status = 'completed' THEN 1 END) as completed_capa,
                COUNT(CASE WHEN cr.priority = 'high' THEN 1 END) as high_priority_capa,
                AVG(cr.progress_percentage) as avg_progress,
                AVG(ce.effectiveness_rating) as avg_effectiveness
            FROM capa_records cr
            LEFT JOIN capa_effectiveness ce ON cr.id = ce.capa_id
            WHERE cr.company_id = ? AND cr.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(cr.created_at, '%Y-%m')
            ORDER BY month DESC
        ", [$this->user['company_id']]);
    }

    private function getCAPAReports() {
        return $this->db->query("
            SELECT
                crp.*,
                crp.report_type,
                crp.report_period,
                crp.generated_date,
                crp.total_capa,
                crp.completion_rate,
                crp.avg_effectiveness
            FROM capa_reports crp
            WHERE crp.company_id = ?
            ORDER BY crp.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getCAPAAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(cr.id) as total_capa,
                COUNT(CASE WHEN cr.status = 'completed' THEN 1 END) as completed_capa,
                ROUND((COUNT(CASE WHEN cr.status = 'completed' THEN 1 END) / NULLIF(COUNT(cr.id), 0)) * 100, 2) as completion_rate,
                AVG(cr.progress_percentage) as avg_progress,
                COUNT(CASE WHEN cr.priority = 'high' THEN 1 END) as high_priority_capa,
                AVG(ce.effectiveness_rating) as avg_effectiveness,
                COUNT(cv.id) as verified_capa
            FROM capa_records cr
            LEFT JOIN capa_effectiveness ce ON cr.id = ce.capa_id
            LEFT JOIN capa_verification cv ON cr.id = cv.capa_id
            WHERE cr.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getCAPATemplates() {
        return $this->db->query("
            SELECT * FROM capa_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getISOStandards() {
        return $this->db->query("
            SELECT
                iso.*,
                iso.standard_number,
                iso.standard_name,
                iso.current_version,
                iso.certification_date,
                iso.next_audit_date,
                TIMESTAMPDIFF(DAY, CURDATE(), iso.next_audit_date) as days_until_audit
            FROM iso_standards iso
            WHERE iso.company_id = ?
            ORDER BY iso.next_audit_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceRequirements() {
        return $this->db->query("
            SELECT
                cr.*,
                iso.standard_name,
                cr.requirement_number,
                cr.requirement_text,
                cr.compliance_status,
                cr.evidence_required,
                cr.last_reviewed
            FROM compliance_requirements cr
            JOIN iso_standards iso ON cr.standard_id = iso.id
            WHERE cr.company_id = ?
            ORDER BY cr.compliance_status ASC, cr.requirement_number ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceAssessments() {
        return $this->db->query("
            SELECT
                ca.*,
                iso.standard_name,
                ca.assessment_date,
                ca.overall_score,
                ca.compliance_percentage,
                ca.assessor_name,
                ca.next_assessment_date
            FROM compliance_assessments ca
            JOIN iso_standards iso ON ca.standard_id = iso.id
            WHERE ca.company_id = ?
            ORDER BY ca.assessment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceGaps() {
        return $this->db->query("
            SELECT
                cg.*,
                cr.requirement_text,
                cg.gap_description,
                cg.severity,
                cg.target_resolution_date,
                cg.responsible_party,
                TIMESTAMPDIFF(DAY, CURDATE(), cg.target_resolution_date) as days_until_target
            FROM compliance_gaps cg
            JOIN compliance_requirements cr ON cg.requirement_id = cr.id
            WHERE cg.company_id = ?
            ORDER BY cg.severity DESC, cg.target_resolution_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceActions() {
        return $this->db->query("
            SELECT
                ca.*,
                cg.gap_description,
                ca.action_description,
                ca.target_completion_date,
                ca.actual_completion_date,
                ca.effectiveness_rating,
                ca.status
            FROM compliance_actions ca
            JOIN compliance_gaps cg ON ca.gap_id = cg.id
            WHERE ca.company_id = ?
            ORDER BY ca.target_completion_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceReports() {
        return $this->db->query("
            SELECT
                crp.*,
                iso.standard_name,
                crp.report_type,
                crp.report_period,
                crp.generated_date,
                crp.compliance_score,
                crp.gap_count
            FROM compliance_reports crp
            JOIN iso_standards iso ON crp.standard_id = iso.id
            WHERE crp.company_id = ?
            ORDER BY crp.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(iso.id) as total_standards,
                AVG(ca.compliance_percentage) as avg_compliance,
                COUNT(cg.id) as total_gaps,
                COUNT(CASE WHEN cg.severity = 'high' THEN 1 END) as high_severity_gaps,
                COUNT(ca.id) as total_actions,
                COUNT(CASE WHEN ca.status = 'completed' THEN 1 END) as completed_actions,
                ROUND((COUNT(CASE WHEN ca.status = 'completed' THEN 1 END) / NULLIF(COUNT(ca.id), 0)) * 100, 2) as action_completion_rate
            FROM iso_standards iso
            LEFT JOIN compliance_assessments ca ON iso.id = ca.standard_id
            LEFT JOIN compliance_gaps cg ON iso.id = cg.standard_id
            LEFT JOIN compliance_actions ca2 ON cg.id = ca2.gap_id
            WHERE iso.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getComplianceTemplates() {
        return $this->db->query("
            SELECT * FROM compliance_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY template_name ASC
        ", [$this->user['company_id']]);
    }

    private function getQualityStandards() {
        return $this->db->query("
            SELECT
                qs.*,
                qs.standard_name,
                qs.standard_version,
                qs.effective_date,
                qs.review_date,
                qs.status
            FROM quality_standards qs
            WHERE qs.company_id = ?
            ORDER BY qs.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getStandardRequirements() {
        return $this->db->query("
            SELECT
                sr.*,
                qs.standard_name,
                sr.requirement_number,
                sr.requirement_text,
                sr.compliance_method,
                sr.frequency,
                sr.responsible_party
            FROM standard_requirements sr
            JOIN quality_standards qs ON sr.standard_id = qs.id
            WHERE sr.company_id = ?
            ORDER BY sr.requirement_number ASC
        ", [$this->user['company_id']]);
    }

    private function getStandardCompliance() {
        return $this->db->query("
            SELECT
                sc.*,
                sr.requirement_text,
                sc.compliance_status,
                sc.last_checked,
                sc.next_check_date,
                sc.evidence_location,
                TIMESTAMPDIFF(DAY, CURDATE(), sc.next_check_date) as days_until_next
            FROM standard_compliance sc
            JOIN standard_requirements sr ON sc.requirement_id = sr.id
            WHERE sc.company_id = ?
            ORDER BY sc.next_check_date ASC
        ", [$this->user['company_id']]);
    }

    private function getStandardUpdates() {
        return $this->db->query("
            SELECT
                su.*,
                qs.standard_name,
                su.update_type,
                su.new_version,
                su.effective_date,
                su.implementation_plan,
                su.training_required
            FROM standard_updates su
            JOIN quality_standards qs ON su.standard_id = qs.id
            WHERE su.company_id = ?
            ORDER BY su.effective_date ASC
        ", [$this->user['company_id']]);
    }

    private function getStandardTraining() {
        return $this->db->query("
            SELECT
                st.*,
                qs.standard_name,
                st.training_name,
                st.required_for,
                st.frequency,
                st.last_completed,
                st.next_due
            FROM standard_training st
            JOIN quality_standards qs ON st.standard_id = qs.id
            WHERE st.company_id = ?
            ORDER BY st.next_due ASC
        ", [$this->user['company_id']]);
    }

    private function getStandardAudits() {
        return $this->db->query("
            SELECT
                sa.*,
                qs.standard_name,
                sa.audit_date,
                sa.audit_result,
                sa.findings_count,
                sa.corrective_actions,
                sa.next_audit_date
            FROM standard_audits sa
            JOIN quality_standards qs ON sa.standard_id = qs.id
            WHERE sa.company_id = ?
            ORDER BY sa.audit_date DESC
        ", [$this->user['company_id']]);
    }

    private function getStandardReports() {
        return $this->db->query("
            SELECT
                srp.*,
                qs.standard_name,
                srp.report_type,
                srp.report_period,
                srp.generated_date,
                srp.compliance_score,
                srp.gap_count
            FROM standard_reports srp
            JOIN quality_standards qs ON srp.standard_id = qs.id
            WHERE srp.company_id = ?
            ORDER BY srp.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getStandardAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(qs.id) as total_standards,
                COUNT(sr.id) as total_requirements,
                ROUND((COUNT(CASE WHEN sc.compliance_status = 'compliant' THEN 1 END) / NULLIF(COUNT(sc.id), 0)) * 100, 2) as compliance_rate,
                COUNT(su.id) as pending_updates,
                COUNT(st.id) as training_requirements,
                AVG(sa.audit_result) as avg_audit_score
            FROM quality_standards qs
            LEFT JOIN standard_requirements sr ON qs.id = sr.standard_id
            LEFT JOIN standard_compliance sc ON sr.id = sc.requirement_id
            LEFT JOIN standard_updates su ON qs.id = su.standard_id AND su.status = 'pending'
            LEFT JOIN standard_training st ON qs.id = st.standard_id
            LEFT JOIN standard_audits sa ON qs.id = sa.standard_id
            WHERE qs.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getControlCharts() {
        return $this->db->query("
            SELECT
                cc.*,
                cc.chart_name,
                cc.process_name,
                cc.control_limits_upper,
                cc.control_limits_lower,
                cc.mean_value,
                cc.last_updated,
                cc.status
            FROM control_charts cc
            WHERE cc.company_id = ?
            ORDER BY cc.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getProcessCapability() {
        return $this->db->query("
            SELECT
                pc.*,
                cc.chart_name,
                pc.capability_index,
                pc.performance_index,
                pc.process_sigma,
                pc.within_spec_percentage,
                pc.overall_spec_percentage
            FROM process_capability pc
            JOIN control_charts cc ON pc.chart_id = cc.id
            WHERE pc.company_id = ?
            ORDER BY pc.capability_index DESC
        ", [$this->user['company_id']]);
    }

    private function getControlLimits() {
        return $this->db->query("
            SELECT
                cl.*,
                cc.chart_name,
                cl.limit_type,
                cl.limit_value,
                cl.calculation_method,
                cl.last_calculated,
                cl.is_violated
