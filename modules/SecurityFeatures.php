<?php
/**
 * TPT Free ERP - Comprehensive Security Features Module
 * Advanced security features including encryption, audit logging, compliance, and monitoring
 */

class SecurityFeatures extends BaseController {
    private $db;
    private $user;
    private $encryption;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->encryption = new Encryption();
    }

    /**
     * Main security dashboard
     */
    public function index() {
        $this->requirePermission('security.view');

        $data = [
            'title' => 'Security Dashboard',
            'security_overview' => $this->getSecurityOverview(),
            'threat_intelligence' => $this->getThreatIntelligence(),
            'compliance_status' => $this->getComplianceStatus(),
            'security_alerts' => $this->getSecurityAlerts(),
            'access_monitoring' => $this->getAccessMonitoring(),
            'encryption_status' => $this->getEncryptionStatus(),
            'audit_summary' => $this->getAuditSummary()
        ];

        $this->render('modules/security/dashboard', $data);
    }

    /**
     * Encryption management
     */
    public function encryption() {
        $this->requirePermission('security.encryption.view');

        $data = [
            'title' => 'Encryption Management',
            'encryption_keys' => $this->getEncryptionKeys(),
            'encrypted_data' => $this->getEncryptedData(),
            'key_rotation_schedule' => $this->getKeyRotationSchedule(),
            'encryption_algorithms' => $this->getEncryptionAlgorithms(),
            'data_classification' => $this->getDataClassification(),
            'encryption_logs' => $this->getEncryptionLogs()
        ];

        $this->render('modules/security/encryption', $data);
    }

    /**
     * Audit logging system
     */
    public function audit() {
        $this->requirePermission('security.audit.view');

        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'resource_type' => $_GET['resource_type'] ?? null,
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
            'severity' => $_GET['severity'] ?? 'all'
        ];

        $audit_logs = $this->getAuditLogs($filters);

        $data = [
            'title' => 'Audit Logging',
            'audit_logs' => $audit_logs,
            'filters' => $filters,
            'audit_summary' => $this->getAuditSummary($filters),
            'audit_policies' => $this->getAuditPolicies(),
            'log_retention' => $this->getLogRetentionSettings(),
            'audit_reports' => $this->getAuditReports()
        ];

        $this->render('modules/security/audit', $data);
    }

    /**
     * Compliance management
     */
    public function compliance() {
        $this->requirePermission('security.compliance.view');

        $data = [
            'title' => 'Compliance Management',
            'compliance_frameworks' => $this->getComplianceFrameworks(),
            'compliance_assessments' => $this->getComplianceAssessments(),
            'policy_management' => $this->getPolicyManagement(),
            'risk_assessment' => $this->getRiskAssessment(),
            'compliance_reports' => $this->getComplianceReports(),
            'regulatory_updates' => $this->getRegulatoryUpdates(),
            'compliance_training' => $this->getComplianceTraining()
        ];

        $this->render('modules/security/compliance', $data);
    }

    /**
     * Data privacy controls
     */
    public function privacy() {
        $this->requirePermission('security.privacy.view');

        $data = [
            'title' => 'Data Privacy Controls',
            'privacy_policies' => $this->getPrivacyPolicies(),
            'data_subject_requests' => $this->getDataSubjectRequests(),
            'consent_management' => $this->getConsentManagement(),
            'data_retention' => $this->getDataRetentionPolicies(),
            'privacy_impact_assessments' => $this->getPrivacyImpactAssessments(),
            'data_mapping' => $this->getDataMapping(),
            'privacy_training' => $this->getPrivacyTraining()
        ];

        $this->render('modules/security/privacy', $data);
    }

    /**
     * Security monitoring
     */
    public function monitoring() {
        $this->requirePermission('security.monitoring.view');

        $data = [
            'title' => 'Security Monitoring',
            'real_time_alerts' => $this->getRealTimeAlerts(),
            'security_metrics' => $this->getSecurityMetrics(),
            'intrusion_detection' => $this->getIntrusionDetection(),
            'anomaly_detection' => $this->getAnomalyDetection(),
            'log_analysis' => $this->getLogAnalysis(),
            'threat_hunting' => $this->getThreatHunting(),
            'monitoring_dashboards' => $this->getMonitoringDashboards()
        ];

        $this->render('modules/security/monitoring', $data);
    }

    /**
     * Access control management
     */
    public function accessControl() {
        $this->requirePermission('security.access.view');

        $data = [
            'title' => 'Access Control Management',
            'user_permissions' => $this->getUserPermissions(),
            'role_management' => $this->getRoleManagement(),
            'access_policies' => $this->getAccessPolicies(),
            'session_management' => $this->getSessionManagement(),
            'multi_factor_auth' => $this->getMultiFactorAuth(),
            'access_logs' => $this->getAccessLogs(),
            'access_reports' => $this->getAccessReports()
        ];

        $this->render('modules/security/access_control', $data);
    }

    /**
     * Incident response
     */
    public function incidentResponse() {
        $this->requirePermission('security.incident.view');

        $data = [
            'title' => 'Incident Response',
            'active_incidents' => $this->getActiveIncidents(),
            'incident_history' => $this->getIncidentHistory(),
            'response_playbooks' => $this->getResponsePlaybooks(),
            'incident_reports' => $this->getIncidentReports(),
            'forensic_tools' => $this->getForensicTools(),
            'communication_plans' => $this->getCommunicationPlans(),
            'recovery_procedures' => $this->getRecoveryProcedures()
        ];

        $this->render('modules/security/incident_response', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getSecurityOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_alerts,
                COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_alerts,
                COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_alerts,
                COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_alerts,
                COUNT(DISTINCT user_id) as active_users,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_sessions,
                AVG(encryption_compliance_score) as avg_encryption_score,
                COUNT(CASE WHEN compliance_status = 'compliant' THEN 1 END) as compliant_systems,
                COUNT(CASE WHEN compliance_status = 'non_compliant' THEN 1 END) as non_compliant_systems,
                MAX(last_security_scan) as last_scan_date
            FROM security_overview
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getThreatIntelligence() {
        return $this->db->query("
            SELECT
                ti.*,
                ti.threat_type,
                ti.threat_level,
                ti.source,
                ti.description,
                ti.indicators_of_compromise,
                ti.recommended_actions,
                ti.detection_date,
                ti.last_updated,
                TIMESTAMPDIFF(DAY, ti.detection_date, NOW()) as days_old
            FROM threat_intelligence ti
            WHERE ti.company_id = ? AND ti.is_active = true
            ORDER BY ti.threat_level DESC, ti.detection_date DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceStatus() {
        return $this->db->query("
            SELECT
                cf.framework_name,
                cf.compliance_percentage,
                cf.last_assessment_date,
                cf.next_assessment_date,
                cf.critical_findings,
                cf.status,
                cf.responsible_person,
                TIMESTAMPDIFF(DAY, NOW(), cf.next_assessment_date) as days_until_next_assessment
            FROM compliance_frameworks cf
            WHERE cf.company_id = ? AND cf.is_active = true
            ORDER BY cf.compliance_percentage ASC
        ", [$this->user['company_id']]);
    }

    private function getSecurityAlerts() {
        return $this->db->query("
            SELECT
                sa.*,
                sa.alert_type,
                sa.severity,
                sa.description,
                sa.source,
                sa.detection_time,
                sa.status,
                sa.assigned_to,
                sa.resolution_time,
                TIMESTAMPDIFF(MINUTE, sa.detection_time, NOW()) as minutes_since_detection
            FROM security_alerts sa
            WHERE sa.company_id = ?
            ORDER BY sa.severity DESC, sa.detection_time DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getAccessMonitoring() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN access_result = 'success' THEN 1 END) as successful_accesses,
                COUNT(CASE WHEN access_result = 'failed' THEN 1 END) as failed_accesses,
                COUNT(CASE WHEN access_result = 'blocked' THEN 1 END) as blocked_accesses,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT ip_address) as unique_ips,
                AVG(session_duration_minutes) as avg_session_duration,
                MAX(last_access_time) as last_access_time,
                COUNT(CASE WHEN is_suspicious = true THEN 1 END) as suspicious_activities
            FROM access_monitoring
            WHERE company_id = ? AND access_time >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ]);
    }

    private function getEncryptionStatus() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN encryption_status = 'encrypted' THEN 1 END) as encrypted_fields,
                COUNT(CASE WHEN encryption_status = 'unencrypted' THEN 1 END) as unencrypted_fields,
                COUNT(CASE WHEN key_status = 'active' THEN 1 END) as active_keys,
                COUNT(CASE WHEN key_status = 'expired' THEN 1 END) as expired_keys,
                COUNT(CASE WHEN key_status = 'compromised' THEN 1 END) as compromised_keys,
                MAX(last_key_rotation) as last_rotation_date,
                AVG(encryption_strength_score) as avg_encryption_strength
            FROM encryption_status
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAuditSummary($filters = []) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if (isset($filters['date_from'])) {
            $where[] = "timestamp >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (isset($filters['date_to'])) {
            $where[] = "timestamp <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_events,
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_events,
                COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_events,
                COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_events,
                COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_events,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT resource_type) as unique_resources,
                MAX(timestamp) as last_event_time
            FROM audit_logs
            WHERE $whereClause
        ", $params);
    }

    private function getEncryptionKeys() {
        return $this->db->query("
            SELECT
                ek.*,
                ek.key_id,
                ek.key_type,
                ek.algorithm,
                ek.key_length,
                ek.status,
                ek.created_date,
                ek.expiry_date,
                ek.last_used,
                ek.usage_count,
                TIMESTAMPDIFF(DAY, NOW(), ek.expiry_date) as days_until_expiry
            FROM encryption_keys ek
            WHERE ek.company_id = ?
            ORDER BY ek.status ASC, ek.expiry_date ASC
        ", [$this->user['company_id']]);
    }

    private function getEncryptedData() {
        return $this->db->query("
            SELECT
                ed.*,
                ed.table_name,
                ed.column_name,
                ed.data_type,
                ed.encryption_method,
                ed.encryption_key_id,
                ed.record_count,
                ed.last_encrypted,
                ed.compliance_status
            FROM encrypted_data ed
            WHERE ed.company_id = ?
            ORDER BY ed.compliance_status ASC, ed.last_encrypted DESC
        ", [$this->user['company_id']]);
    }

    private function getKeyRotationSchedule() {
        return $this->db->query("
            SELECT
                krs.*,
                krs.key_id,
                krs.rotation_schedule,
                krs.next_rotation_date,
                krs.last_rotation_date,
                krs.rotation_reason,
                krs.automatic_rotation,
                TIMESTAMPDIFF(DAY, NOW(), krs.next_rotation_date) as days_until_rotation
            FROM key_rotation_schedule krs
            WHERE krs.company_id = ?
            ORDER BY krs.next_rotation_date ASC
        ", [$this->user['company_id']]);
    }

    private function getEncryptionAlgorithms() {
        return [
            'AES-256-GCM' => [
                'name' => 'AES-256-GCM',
                'description' => 'Advanced Encryption Standard with Galois/Counter Mode',
                'key_length' => 256,
                'strength' => 'Very High',
                'recommended_use' => 'General data encryption'
            ],
            'AES-128-CBC' => [
                'name' => 'AES-128-CBC',
                'description' => 'Advanced Encryption Standard with Cipher Block Chaining',
                'key_length' => 128,
                'strength' => 'High',
                'recommended_use' => 'Legacy system compatibility'
            ],
            'ChaCha20-Poly1305' => [
                'name' => 'ChaCha20-Poly1305',
                'description' => 'ChaCha20 stream cipher with Poly1305 authenticator',
                'key_length' => 256,
                'strength' => 'Very High',
                'recommended_use' => 'High-performance encryption'
            ],
            'RSA-4096' => [
                'name' => 'RSA-4096',
                'description' => 'Rivest-Shamir-Adleman with 4096-bit key',
                'key_length' => 4096,
                'strength' => 'Very High',
                'recommended_use' => 'Asymmetric encryption and digital signatures'
            ]
        ];
    }

    private function getDataClassification() {
        return $this->db->query("
            SELECT
                dc.*,
                dc.classification_level,
                dc.data_category,
                dc.sensitivity_score,
                dc.retention_period_days,
                dc.encryption_required,
                dc.access_restrictions,
                dc.record_count
            FROM data_classification dc
            WHERE dc.company_id = ?
            ORDER BY dc.sensitivity_score DESC
        ", [$this->user['company_id']]);
    }

    private function getEncryptionLogs() {
        return $this->db->query("
            SELECT
                el.*,
                el.operation_type,
                el.table_name,
                el.record_id,
                el.encryption_key_id,
                el.operation_time,
                el.success,
                el.error_message,
                el.performance_ms
            FROM encryption_logs el
            WHERE el.company_id = ?
            ORDER BY el.operation_time DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getAuditLogs($filters) {
        $where = ["al.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['user_id']) {
            $where[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if ($filters['action']) {
            $where[] = "al.action = ?";
            $params[] = $filters['action'];
        }

        if ($filters['resource_type']) {
            $where[] = "al.resource_type = ?";
            $params[] = $filters['resource_type'];
        }

        if ($filters['date_from']) {
            $where[] = "al.timestamp >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "al.timestamp <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['severity'] !== 'all') {
            $where[] = "al.severity = ?";
            $params[] = $filters['severity'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                al.*,
                u.first_name,
                u.last_name,
                al.action,
                al.resource_type,
                al.resource_id,
                al.severity,
                al.timestamp,
                al.ip_address,
                al.user_agent,
                al.details
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE $whereClause
            ORDER BY al.timestamp DESC
            LIMIT 1000
        ", $params);
    }

    private function getAuditPolicies() {
        return $this->db->query("
            SELECT * FROM audit_policies
            WHERE company_id = ? AND is_active = true
            ORDER BY policy_name ASC
        ", [$this->user['company_id']]);
    }

    private function getLogRetentionSettings() {
        return $this->db->querySingle("
            SELECT * FROM log_retention_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAuditReports() {
        return $this->db->query("
            SELECT
                ar.*,
                ar.report_name,
                ar.report_type,
                ar.date_range,
                ar.generated_date,
                ar.total_events,
                ar.critical_events,
                ar.report_file_path
            FROM audit_reports ar
            WHERE ar.company_id = ?
            ORDER BY ar.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceFrameworks() {
        return [
            'gdpr' => [
                'name' => 'GDPR',
                'description' => 'General Data Protection Regulation',
                'region' => 'EU',
                'requirements' => ['Data protection', 'Privacy by design', 'Data subject rights'],
                'compliance_score' => 85
            ],
            'ccpa' => [
                'name' => 'CCPA',
                'description' => 'California Consumer Privacy Act',
                'region' => 'California, USA',
                'requirements' => ['Consumer rights', 'Data minimization', 'Security measures'],
                'compliance_score' => 90
            ],
            'hipaa' => [
                'name' => 'HIPAA',
                'description' => 'Health Insurance Portability and Accountability Act',
                'region' => 'USA',
                'requirements' => ['PHI protection', 'Security rule', 'Privacy rule'],
                'compliance_score' => 88
            ],
            'pci_dss' => [
                'name' => 'PCI DSS',
                'description' => 'Payment Card Industry Data Security Standard',
                'region' => 'Global',
                'requirements' => ['Card data protection', 'Security testing', 'Access control'],
                'compliance_score' => 92
            ],
            'sox' => [
                'name' => 'SOX',
                'description' => 'Sarbanes-Oxley Act',
                'region' => 'USA',
                'requirements' => ['Financial reporting', 'Internal controls', 'Audit trails'],
                'compliance_score' => 87
            ]
        ];
    }

    private function getComplianceAssessments() {
        return $this->db->query("
            SELECT
                ca.*,
                ca.framework_name,
                ca.assessment_date,
                ca.assessor_name,
                ca.compliance_score,
                ca.critical_findings,
                ca.recommendations,
                ca.next_assessment_date,
                TIMESTAMPDIFF(DAY, NOW(), ca.next_assessment_date) as days_until_next
            FROM compliance_assessments ca
            WHERE ca.company_id = ?
            ORDER BY ca.assessment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getPolicyManagement() {
        return $this->db->query("
            SELECT
                pm.*,
                pm.policy_name,
                pm.policy_type,
                pm.version,
                pm.effective_date,
                pm.review_date,
                pm.approval_status,
                pm.last_updated
            FROM policy_management pm
            WHERE pm.company_id = ?
            ORDER BY pm.policy_type ASC, pm.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getRiskAssessment() {
        return $this->db->query("
            SELECT
                ra.*,
                ra.risk_description,
                ra.risk_level,
                ra.probability,
                ra.impact,
                ra.mitigation_plan,
                ra.responsible_person,
                ra.status,
                ra.last_assessed
            FROM risk_assessment ra
            WHERE ra.company_id = ?
            ORDER BY ra.risk_level DESC, ra.probability * ra.impact DESC
        ", [$this->user['company_id']]);
    }

    private function getComplianceReports() {
        return $this->db->query("
            SELECT
                cr.*,
                cr.report_name,
                cr.framework_name,
                cr.report_period,
                cr.compliance_percentage,
                cr.generated_date,
                cr.generated_by
            FROM compliance_reports cr
            WHERE cr.company_id = ?
            ORDER BY cr.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getRegulatoryUpdates() {
        return $this->db->query("
            SELECT
                ru.*,
                ru.update_title,
                ru.framework_name,
                ru.effective_date,
                ru.description,
                ru.impact_assessment,
                ru.implementation_status,
                TIMESTAMPDIFF(DAY, NOW(), ru.effective_date) as days_until_effective
            FROM regulatory_updates ru
            WHERE ru.company_id = ?
            ORDER BY ru.effective_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceTraining() {
        return $this->db->query("
            SELECT
                ct.*,
                ct.training_name,
                ct.framework_name,
                ct.training_type,
                ct.completion_rate,
                ct.last_updated,
                ct.next_scheduled
            FROM compliance_training ct
            WHERE ct.company_id = ?
            ORDER BY ct.next_scheduled ASC
        ", [$this->user['company_id']]);
    }

    private function getPrivacyPolicies() {
        return $this->db->query("
            SELECT
                pp.*,
                pp.policy_name,
                pp.version,
                pp.effective_date,
                pp.last_reviewed,
                pp.next_review_date,
                pp.approval_status
            FROM privacy_policies pp
            WHERE pp.company_id = ?
            ORDER BY pp.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDataSubjectRequests() {
        return $this->db->query("
            SELECT
                dsr.*,
                dsr.request_type,
                dsr.subject_name,
                dsr.subject_email,
                dsr.request_date,
                dsr.status,
                dsr.completion_date,
                dsr.assigned_to,
                TIMESTAMPDIFF(DAY, dsr.request_date, COALESCE(dsr.completion_date, NOW())) as processing_days
            FROM data_subject_requests dsr
            WHERE dsr.company_id = ?
            ORDER BY dsr.request_date DESC
        ", [$this->user['company_id']]);
    }

    private function getConsentManagement() {
        return $this->db->query("
            SELECT
                cm.*,
                cm.consent_type,
                cm.subject_id,
                cm.granted_date,
                cm.expiry_date,
                cm.consent_status,
                cm.withdrawal_date,
                cm.consent_scope
            FROM consent_management cm
            WHERE cm.company_id = ?
            ORDER BY cm.granted_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDataRetentionPolicies() {
        return $this->db->query("
            SELECT
                drp.*,
                drp.data_type,
                drp.retention_period_days,
                drp.retention_reason,
                drp.disposal_method,
                drp.last_reviewed,
                drp.next_review_date
            FROM data_retention_policies drp
            WHERE drp.company_id = ?
            ORDER BY drp.data_type ASC
        ", [$this->user['company_id']]);
    }

    private function getPrivacyImpactAssessments() {
        return $this->db->query("
            SELECT
                pia.*,
                pia.assessment_name,
                pia.project_name,
                pia.assessment_date,
                pia.privacy_risk_level,
                pia.recommendations,
                pia.status,
                pia.next_review_date
            FROM privacy_impact_assessments pia
            WHERE pia.company_id = ?
            ORDER BY pia.assessment_date DESC
        ", [$this->user['company_id']]);
    }

    private function getDataMapping() {
        return $this->db->query("
            SELECT
                dm.*,
                dm.data_category,
                dm.data_type,
                dm.storage_location,
                dm.data_volume,
                dm.sensitivity_level,
                dm.processing_purpose,
                dm.retention_period,
                dm.last_updated
            FROM data_mapping dm
            WHERE dm.company_id = ?
            ORDER BY dm.sensitivity_level DESC, dm.data_volume DESC
        ", [$this->user['company_id']]);
    }

    private function getPrivacyTraining() {
        return $this->db->query("
            SELECT
                pt.*,
                pt.training_name,
                pt.training_type,
                pt.completion_rate,
                pt.last_updated,
                pt.next_scheduled,
                pt.mandatory_for_all
            FROM privacy_training pt
            WHERE pt.company_id = ?
            ORDER BY pt.next_scheduled ASC
        ", [$this->user['company_id']]);
    }

    private function getRealTimeAlerts() {
        return $this->db->query("
            SELECT
                rta.*,
                rta.alert_type,
                rta.severity,
                rta.description,
                rta.source,
                rta.detection_time,
                rta.status,
                TIMESTAMPDIFF(MINUTE, rta.detection_time, NOW()) as minutes_ago
            FROM real_time_alerts rta
            WHERE rta.company_id = ? AND rta.status = 'active'
            ORDER BY rta.severity DESC, rta.detection_time DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getSecurityMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN alert_type = 'intrusion' THEN 1 END) as intrusion_attempts,
                COUNT(CASE WHEN alert_type = 'malware' THEN 1 END) as malware_detections,
                COUNT(CASE WHEN alert_type = 'unauthorized_access' THEN 1 END) as unauthorized_accesses,
                COUNT(CASE WHEN alert_type = 'data_breach' THEN 1 END) as data_breach_attempts,
                AVG(response_time_minutes) as avg_response_time,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_incidents,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_incidents,
                MAX(last_scan_time) as last_scan_time
            FROM security_metrics
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ]);
    }

    private function getIntrusionDetection() {
        return $this->db->query("
            SELECT
                id.*,
                id.detection_type,
                id.source_ip,
                id.destination_ip,
                id.attack_type,
                id.severity,
                id.detection_time,
                id.status,
                id.blocked_packets,
                id.malicious_payload
            FROM intrusion_detection id
            WHERE id.company_id = ?
            ORDER BY id.detection_time DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getAnomalyDetection() {
        return $this->db->query("
            SELECT
                ad.*,
                ad.anomaly_type,
                ad.metric_name,
                ad.expected_value,
                ad.actual_value,
                ad.deviation_percentage,
                ad.severity,
                ad.detection_time,
                ad.status,
                ad.investigation_status
            FROM anomaly_detection ad
            WHERE ad.company_id = ?
            ORDER BY ad.severity DESC, ad.detection_time DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getLogAnalysis() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN log_level = 'ERROR' THEN 1 END) as error_logs,
                COUNT(CASE WHEN log_level = 'WARN' THEN 1 END) as warning_logs,
                COUNT(CASE WHEN pattern_type = 'suspicious' THEN 1 END) as suspicious_patterns,
                COUNT(CASE WHEN pattern_type = 'malicious' THEN 1 END) as malicious_patterns,
                AVG(analysis_time_ms) as avg_analysis_time,
                MAX(last_analysis_time) as last_analysis_time,
                COUNT(DISTINCT source_system) as systems_analyzed
            FROM log_analysis
            WHERE company_id = ? AND analysis_time >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);
    }

    private function getThreatHunting() {
        return $this->db->query("
            SELECT
                th.*,
                th.hunt_name,
                th.hunt_type,
                th.target_indicators,
                th.findings_count,
                th.status,
                th.started_at,
                th.completed_at,
                th.assigned_to
            FROM threat_hunting th
            WHERE th.company_id = ?
            ORDER BY th.started_at DESC
        ", [$this->user['company_id']]);
    }

    private function getMonitoringDashboards() {
        return $this->db->query("
            SELECT
                md.*,
                md.dashboard_name,
                md.dashboard_type,
                md.refresh_interval_seconds,
                md.last_updated,
                md.created_by,
                COUNT(mdw.widget_id) as widget_count
            FROM monitoring_dashboards md
            LEFT JOIN monitoring_dashboard_widgets mdw ON md.id = mdw.dashboard_id
            WHERE md.company_id = ?
            GROUP BY md.id
            ORDER BY md.dashboard_name ASC
        ", [$this->user['company_id']]);
    }

    private function getUserPermissions() {
        return $this->db->query("
            SELECT
                up.*,
                u.first_name,
                u.last_name,
                u.email,
                r.role_name,
                up.permission_name,
                up.resource_type,
                up.granted_date,
                up.expiry_date,
                up.is_active
            FROM user_permissions up
            JOIN users u ON up.user_id = u.id
            LEFT JOIN roles r ON up.role_id = r.id
            WHERE up.company_id = ?
            ORDER BY u.first_name, u.last_name ASC
        ", [$this->user['company_id']]);
    }

    private function getRoleManagement() {
        return $this->db->query("
            SELECT
                r.*,
                r.role_name,
                r.description,
                r.created_date,
                r.last_updated,
                COUNT(ur.user_id) as user_count,
                COUNT(rp.permission_id) as permission_count
            FROM roles r
            LEFT JOIN user_roles ur ON r.id = ur.role_id
            LEFT JOIN role_permissions rp ON r.id = rp.role_id
            WHERE r.company_id = ?
            GROUP BY r.id
            ORDER BY r.role_name ASC
        ", [$this->user['company_id']]);
    }

    private function getAccessPolicies() {
        return $this->db->query("
            SELECT
                ap.*,
                ap.policy_name,
                ap.policy_type,
                ap.resource_type,
                ap.access_level,
                ap.conditions,
                ap.is_active,
                ap.created_date
            FROM access_policies ap
            WHERE ap.company_id = ?
            ORDER BY ap.policy_type ASC, ap.resource_type ASC
        ", [$this->user['company_id']]);
    }

    private function getSessionManagement() {
        return $this->db->query("
            SELECT
                sm.*,
                u.first_name,
                u.last_name,
                sm.session_id,
                sm.ip_address,
                sm.user_agent,
                sm.login_time,
                sm.last_activity,
                sm.is_active,
                TIMESTAMPDIFF(MINUTE, sm.last_activity, NOW()) as minutes_since_activity
            FROM session_management sm
            LEFT JOIN users u ON sm.user_id = u.id
            WHERE sm.company_id = ?
            ORDER BY sm.login_time DESC
        ", [$this->user['company_id']]);
    }

    private function getMultiFactorAuth() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN mfa_enabled = true THEN 1 END) as users_with_mfa,
                COUNT(CASE WHEN mfa_method = 'totp' THEN 1 END) as totp_users,
                COUNT(CASE WHEN mfa_method = 'sms' THEN 1 END) as sms_users,
                COUNT(CASE WHEN mfa_method = 'email' THEN 1 END) as email_users,
                COUNT(CASE WHEN mfa_method = 'hardware' THEN 1 END) as hardware_users,
                AVG(mfa_setup_date) as avg_setup_time,
                COUNT(CASE WHEN last_mfa_failure >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as recent_failures
            FROM multi_factor_auth
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getAccessLogs() {
        return $this->db->query("
            SELECT
                al.*,
                u.first_name,
                u.last_name,
                al.resource_type,
                al.resource_id,
                al.access_type,
                al.access_result,
                al.access_time,
                al.ip_address,
                al.user_agent,
                al.session_id
            FROM access_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.company_id = ?
            ORDER BY al.access_time DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getAccessReports() {
        return $this->db->query("
            SELECT
                ar.*,
                ar.report_name,
                ar.report_type,
                ar.date_range,
                ar.generated_date,
                ar.total_accesses,
                ar.failed_accesses,
                ar.suspicious_activities
            FROM access_reports ar
            WHERE ar.company_id = ?
            ORDER BY ar.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getActiveIncidents() {
        return $this->db->query("
            SELECT
                ir.*,
                ir.incident_title,
                ir.severity,
                ir.status,
                ir.detection_time,
                ir.assigned_to,
                ir.estimated_resolution_time,
                TIMESTAMPDIFF(MINUTE, ir.detection_time, NOW()) as minutes_since_detection
            FROM incident_response ir
            WHERE ir.company_id = ? AND ir.status IN ('new', 'investigating', 'contained')
            ORDER BY ir.severity DESC, ir.detection_time DESC
        ", [$this->user['company_id']]);
    }

    private function getIncidentHistory() {
        return $this->db->query("
            SELECT
                ir.*,
                ir.incident_title,
                ir.incident_type,
                ir.severity,
                ir.status,
                ir.detection_time,
                ir.resolution_time,
                ir.assigned_to,
                TIMESTAMPDIFF(MINUTE, ir.detection_time, ir.resolution_time) as resolution_minutes
            FROM incident_response ir
            WHERE ir.company_id = ?
            ORDER BY ir.detection_time DESC
        ", [$this->user['company_id']]);
    }

    private function getResponsePlaybooks() {
        return $this->db->query("
            SELECT
                rp.*,
                rp.playbook_name,
                rp.incident_type,
                rp.version,
                rp.last_updated,
                rp.approved_by,
                COUNT(rps.step_number) as step_count
            FROM response_playbooks rp
            LEFT JOIN response_playbook_steps rps ON rp.id = rps.playbook_id
            WHERE rp.company_id = ?
            GROUP BY rp.id
            ORDER BY rp.incident_type ASC
        ", [$this->user['company_id']]);
    }

    private function getIncidentReports() {
        return $this->db->query("
            SELECT
                ir.*,
                ir.report_title,
                ir.incident_id,
                ir.generated_date,
                ir.report_type,
                ir.findings_summary,
                ir.recommendations,
                ir.generated_by
            FROM incident_reports ir
            WHERE ir.company_id = ?
            ORDER BY ir.generated_date DESC
        ", [$this->user['company_id']]);
    }

    private function getForensicTools() {
        return [
            'log_analyzer' => 'Log Analysis Tool',
            'memory_analyzer' => 'Memory Forensics Tool',
            'network_analyzer' => 'Network Traffic Analyzer',
            'file_carver' => 'File Carving Tool',
            'timeline_analyzer' => 'Timeline Analysis Tool',
            'artifact_extractor' => 'Digital Artifact Extractor'
        ];
    }

    private function getCommunicationPlans() {
        return $this->db->query("
            SELECT
                cp.*,
                cp.plan_name,
                cp.incident_type,
                cp.stakeholder_groups,
                cp.communication_channels,
                cp.key_messages,
                cp.last_updated
            FROM communication_plans cp
            WHERE cp.company_id = ?
            ORDER BY cp.incident_type ASC
        ", [$this->user['company_id']]);
    }

    private function getRecoveryProcedures() {
        return $this->db->query("
            SELECT
                rp.*,
                rp.procedure_name,
                rp.system_type,
                rp.recovery_steps,
                rp.estimated_recovery_time,
                rp.last_tested,
                rp.success_rate
            FROM recovery_procedures rp
            WHERE rp.company_id = ?
            ORDER BY rp.system_type ASC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function logAuditEvent() {
        $this->requirePermission('security.audit.log');

        $data = $this->validateRequest([
            'action' => 'required|string',
            'resource_type' => 'required|string',
            'resource_id' => 'required|string',
            'severity' => 'required|string|in:low,medium,high,critical',
            'details' => 'array'
        ]);

        try {
            $auditId = $this->db->insert('audit_logs', [
                'company_id' => $this->user['company_id'],
                'user_id' => $this->user['id'],
                'action' => $data['action'],
                'resource_type' => $data['resource_type'],
                'resource_id' => $data['resource_id'],
                'severity' => $data['severity'],
                'details' => json_encode($data['details'] ?? []),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'audit_id' => $auditId,
                'message' => 'Audit event logged successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createSecurityAlert() {
        $this->requirePermission('security.monitoring.alert');

        $data = $this->validateRequest([
            'alert_type' => 'required|string',
            'severity' => 'required|string|in:low,medium,high,critical',
            'description' => 'required|string',
            'source' => 'required|string',
            'details' => 'array'
        ]);

        try {
            $alertId = $this->db->insert('security_alerts', [
                'company_id' => $this->user['company_id'],
                'alert_type' => $data['alert_type'],
                'severity' => $data['severity'],
                'description' => $data['description'],
                'source' => $data['source'],
                'details' => json_encode($data['details'] ?? []),
                'detection_time' => date('Y-m-d H:i:s'),
                'status' => 'new'
            ]);

            // Trigger alert notifications
            $this->triggerAlertNotifications($alertId, $data);

            $this->jsonResponse([
                'success' => true,
                'alert_id' => $alertId,
                'message' => 'Security alert created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function encryptData() {
        $this->requirePermission('security.encryption.encrypt');

        $data = $this->validateRequest([
            'data' => 'required|string',
            'key_id' => 'required|string',
            'algorithm' => 'string'
        ]);

        try {
            $encryptedData = $this->encryption->encrypt($data['data'], $data['key_id'], $data['algorithm'] ?? 'AES-256-GCM');

            // Log encryption operation
            $this->db->insert('encryption_logs', [
                'company_id' => $this->user['company_id'],
                'operation_type' => 'encrypt',
                'encryption_key_id' => $data['key_id'],
                'operation_time' => date('Y-m-d H:i:s'),
                'success' => true,
                'performance_ms' => 0 // Would be measured in real implementation
            ]);

            $this->jsonResponse([
                'success' => true,
                'encrypted_data' => $encryptedData,
                'algorithm' => $data['algorithm'] ?? 'AES-256-GCM'
            ]);

        } catch (Exception $e) {
            // Log failed encryption
            $this->db->insert('encryption_logs', [
                'company_id' => $this->user['company_id'],
                'operation_type' => 'encrypt',
                'encryption_key_id' => $data['key_id'],
                'operation_time' => date('Y-m-d H:i:s'),
                'success' => false,
                'error_message' => $e->getMessage()
            ]);

            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function decryptData() {
        $this->requirePermission('security.encryption.decrypt');

        $data = $this->validateRequest([
            'encrypted_data' => 'required|string',
            'key_id' => 'required|string'
        ]);

        try {
            $decryptedData = $this->encryption->decrypt($data['encrypted_data'], $data['key_id']);

            // Log decryption operation
            $this->db->insert('encryption_logs', [
                'company_id' => $this->user['company_id'],
                'operation_type' => 'decrypt',
                'encryption_key_id' => $data['key_id'],
                'operation_time' => date('Y-m-d H:i:s'),
                'success' => true,
                'performance_ms' => 0 // Would be measured in real implementation
            ]);

            $this->jsonResponse([
                'success' => true,
                'decrypted_data' => $decryptedData
            ]);

        } catch (Exception $e) {
            // Log failed decryption
            $this->db->insert('encryption_logs', [
                'company_id' => $this->user['company_id'],
                'operation_type' => 'decrypt',
                'encryption_key_id' => $data['key_id'],
                'operation_time' => date('Y-m-d H:i:s'),
                'success' => false,
                'error_message' => $e->getMessage()
            ]);

            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================

    private function triggerAlertNotifications($alertId, $alertData) {
        // Implementation for triggering alert notifications
        // This would typically send emails, SMS, or push notifications
        // based on the alert severity and configured notification rules
    }
}
