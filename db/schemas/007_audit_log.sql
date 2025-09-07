-- TPT Open ERP - Audit Log Table Schema
-- Migration: 007
-- Description: Security and activity logging for compliance

CREATE TABLE IF NOT EXISTS audit_log (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),

    -- Event information
    event_type VARCHAR(50) NOT NULL,
    event_category VARCHAR(50) NOT NULL DEFAULT 'general',
    event_description TEXT,
    severity VARCHAR(20) DEFAULT 'info',

    -- User and session information
    user_id INTEGER REFERENCES users(id),
    session_id VARCHAR(255),
    ip_address INET,
    user_agent TEXT,
    location_lat DECIMAL(10,8),
    location_lng DECIMAL(11,8),

    -- Resource information
    resource_type VARCHAR(50),
    resource_id INTEGER,
    resource_uuid UUID,
    module VARCHAR(100),
    action VARCHAR(50),

    -- Data changes
    old_values JSONB DEFAULT '{}',
    new_values JSONB DEFAULT '{}',
    metadata JSONB DEFAULT '{}',

    -- Security information
    risk_level VARCHAR(20) DEFAULT 'low',
    threat_detected BOOLEAN DEFAULT false,
    anomaly_score DECIMAL(3,2) DEFAULT 0.0,

    -- Compliance information
    gdpr_related BOOLEAN DEFAULT false,
    data_subject_id INTEGER,
    retention_until TIMESTAMP NULL,

    -- System information
    request_id VARCHAR(255),
    correlation_id VARCHAR(255),
    api_version VARCHAR(10),
    user_timezone VARCHAR(50),

    -- Performance metrics
    execution_time_ms INTEGER,
    memory_usage_mb DECIMAL(8,2),
    database_queries INTEGER DEFAULT 0,

    -- Error information (if applicable)
    error_code VARCHAR(50),
    error_message TEXT,
    stack_trace TEXT,

    -- Audit Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),

    -- Constraints
    CONSTRAINT audit_log_event_type CHECK (event_type IN ('login', 'logout', 'create', 'read', 'update', 'delete', 'execute', 'export', 'import', 'security', 'error', 'system')),
    CONSTRAINT audit_log_severity CHECK (severity IN ('debug', 'info', 'warning', 'error', 'critical')),
    CONSTRAINT audit_log_risk_level CHECK (risk_level IN ('low', 'medium', 'high', 'critical')),
    CONSTRAINT audit_log_anomaly_score CHECK (anomaly_score >= 0.0 AND anomaly_score <= 1.0)
);

-- Indexes for performance (partitioning consideration)
CREATE INDEX IF NOT EXISTS idx_audit_log_uuid ON audit_log(uuid);
CREATE INDEX IF NOT EXISTS idx_audit_log_event_type ON audit_log(event_type);
CREATE INDEX IF NOT EXISTS idx_audit_log_event_category ON audit_log(event_category);
CREATE INDEX IF NOT EXISTS idx_audit_log_user_id ON audit_log(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_created_at ON audit_log(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_log_resource ON audit_log(resource_type, resource_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_module ON audit_log(module);
CREATE INDEX IF NOT EXISTS idx_audit_log_severity ON audit_log(severity);
CREATE INDEX IF NOT EXISTS idx_audit_log_risk_level ON audit_log(risk_level);
CREATE INDEX IF NOT EXISTS idx_audit_log_ip_address ON audit_log(ip_address);
CREATE INDEX IF NOT EXISTS idx_audit_log_session_id ON audit_log(session_id);

-- Composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_audit_log_user_event ON audit_log(user_id, event_type, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_log_resource_event ON audit_log(resource_type, resource_id, event_type, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_log_time_range ON audit_log(created_at DESC, event_type);
CREATE INDEX IF NOT EXISTS idx_audit_log_security_events ON audit_log(event_category, risk_level, created_at DESC) WHERE event_category = 'security';

-- Partial indexes for specific use cases
CREATE INDEX IF NOT EXISTS idx_audit_log_failed_logins ON audit_log(user_id, created_at DESC) WHERE event_type = 'login' AND severity = 'warning';
CREATE INDEX IF NOT EXISTS idx_audit_log_data_changes ON audit_log(resource_type, created_at DESC) WHERE event_type IN ('create', 'update', 'delete');
CREATE INDEX IF NOT EXISTS idx_audit_log_gdpr_events ON audit_log(gdpr_related, created_at DESC) WHERE gdpr_related = true;
CREATE INDEX IF NOT EXISTS idx_audit_log_threats ON audit_log(threat_detected, created_at DESC) WHERE threat_detected = true;

-- Function to log audit event
CREATE OR REPLACE FUNCTION log_audit_event(
    p_event_type VARCHAR(50),
    p_event_category VARCHAR(50) DEFAULT 'general',
    p_event_description TEXT DEFAULT NULL,
    p_severity VARCHAR(20) DEFAULT 'info',
    p_user_id INTEGER DEFAULT NULL,
    p_session_id VARCHAR(255) DEFAULT NULL,
    p_ip_address INET DEFAULT NULL,
    p_user_agent TEXT DEFAULT NULL,
    p_resource_type VARCHAR(50) DEFAULT NULL,
    p_resource_id INTEGER DEFAULT NULL,
    p_module VARCHAR(100) DEFAULT NULL,
    p_action VARCHAR(50) DEFAULT NULL,
    p_old_values JSONB DEFAULT '{}',
    p_new_values JSONB DEFAULT '{}',
    p_metadata JSONB DEFAULT '{}',
    p_risk_level VARCHAR(20) DEFAULT 'low',
    p_execution_time_ms INTEGER DEFAULT NULL
) RETURNS UUID AS $$
DECLARE
    new_uuid UUID;
BEGIN
    new_uuid := gen_random_uuid();

    INSERT INTO audit_log (
        uuid, event_type, event_category, event_description, severity,
        user_id, session_id, ip_address, user_agent,
        resource_type, resource_id, module, action,
        old_values, new_values, metadata, risk_level,
        execution_time_ms, created_by
    ) VALUES (
        new_uuid, p_event_type, p_event_category, p_event_description, p_severity,
        p_user_id, p_session_id, p_ip_address, p_user_agent,
        p_resource_type, p_resource_id, p_module, p_action,
        p_old_values, p_new_values, p_metadata, p_risk_level,
        p_execution_time_ms, p_user_id
    );

    RETURN new_uuid;
END;
$$ LANGUAGE plpgsql;

-- Function to get audit trail for a resource
CREATE OR REPLACE FUNCTION get_resource_audit_trail(
    p_resource_type VARCHAR(50),
    p_resource_id INTEGER,
    p_limit INTEGER DEFAULT 100
)
RETURNS TABLE (
    id BIGINT,
    event_type VARCHAR(50),
    event_description TEXT,
    user_id INTEGER,
    username VARCHAR(100),
    created_at TIMESTAMP,
    old_values JSONB,
    new_values JSONB
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        al.id,
        al.event_type,
        al.event_description,
        al.user_id,
        u.username,
        al.created_at,
        al.old_values,
        al.new_values
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.resource_type = p_resource_type
      AND al.resource_id = p_resource_id
    ORDER BY al.created_at DESC
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql;

-- Function to detect suspicious activity
CREATE OR REPLACE FUNCTION detect_suspicious_activity(
    p_user_id INTEGER,
    p_time_window_minutes INTEGER DEFAULT 60
)
RETURNS TABLE (
    suspicious_events INTEGER,
    failed_logins INTEGER,
    unusual_ips INTEGER,
    risk_score DECIMAL(3,2)
) AS $$
DECLARE
    window_start TIMESTAMP;
    total_events INTEGER := 0;
    failed_count INTEGER := 0;
    ip_count INTEGER := 0;
    calculated_risk DECIMAL(3,2) := 0.0;
BEGIN
    window_start := CURRENT_TIMESTAMP - INTERVAL '1 minute' * p_time_window_minutes;

    -- Count suspicious events
    SELECT COUNT(*) INTO total_events
    FROM audit_log
    WHERE user_id = p_user_id
      AND created_at >= window_start
      AND severity IN ('warning', 'error', 'critical');

    -- Count failed logins
    SELECT COUNT(*) INTO failed_count
    FROM audit_log
    WHERE user_id = p_user_id
      AND created_at >= window_start
      AND event_type = 'login'
      AND severity = 'warning';

    -- Count distinct IPs
    SELECT COUNT(DISTINCT ip_address) INTO ip_count
    FROM audit_log
    WHERE user_id = p_user_id
      AND created_at >= window_start
      AND ip_address IS NOT NULL;

    -- Calculate risk score (simple algorithm)
    calculated_risk := LEAST(1.0, (failed_count * 0.3) + (total_events * 0.1) + (ip_count * 0.2));

    RETURN QUERY SELECT total_events, failed_count, ip_count, calculated_risk;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE audit_log IS 'Comprehensive audit logging for security and compliance';
COMMENT ON COLUMN audit_log.event_type IS 'Type of event being logged';
COMMENT ON COLUMN audit_log.event_category IS 'Category for grouping related events';
COMMENT ON COLUMN audit_log.severity IS 'Severity level of the event';
COMMENT ON COLUMN audit_log.old_values IS 'Previous values before change (for data modifications)';
COMMENT ON COLUMN audit_log.new_values IS 'New values after change (for data modifications)';
COMMENT ON COLUMN audit_log.metadata IS 'Additional context and metadata for the event';
COMMENT ON COLUMN audit_log.risk_level IS 'Assessed risk level of the event';
COMMENT ON COLUMN audit_log.anomaly_score IS 'Machine learning anomaly detection score (0.0-1.0)';
