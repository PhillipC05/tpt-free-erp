-- TPT Open ERP - Authentication Methods Schema
-- Migration: 028
-- Description: Enhanced authentication methods including TOTP, magic links, and backup codes

-- User authentication methods table
CREATE TABLE IF NOT EXISTS user_auth_methods (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    method_type VARCHAR(50) NOT NULL, -- 'totp', 'magic_link', 'sms', 'webauthn'
    method_data JSONB, -- Encrypted method-specific data
    is_enabled BOOLEAN DEFAULT true,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT user_auth_methods_unique_user_method UNIQUE (user_id, method_type),
    CONSTRAINT user_auth_methods_valid_type CHECK (method_type IN ('totp', 'magic_link', 'sms', 'webauthn', 'backup_codes'))
);

-- Backup codes for 2FA recovery
CREATE TABLE IF NOT EXISTS auth_backup_codes (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    code_hash VARCHAR(255) NOT NULL, -- Hashed backup code
    is_used BOOLEAN DEFAULT false,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Index for performance
    INDEX idx_backup_codes_user (user_id, is_used)
);

-- Magic link tokens for passwordless authentication
CREATE TABLE IF NOT EXISTS magic_link_tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash VARCHAR(255) NOT NULL, -- Secure token hash
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes for performance
    INDEX idx_magic_tokens_user (user_id),
    INDEX idx_magic_tokens_expires (expires_at),
    INDEX idx_magic_tokens_hash (token_hash(50))
);

-- Enhanced audit log for authentication events
CREATE TABLE IF NOT EXISTS auth_audit_log (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    event_type VARCHAR(100) NOT NULL, -- 'login', 'logout', '2fa_enabled', etc.
    method_used VARCHAR(50), -- 'password', 'totp', 'magic_link', etc.
    ip_address INET,
    user_agent TEXT,
    location_data JSONB, -- Geolocation data if available
    device_fingerprint VARCHAR(255),
    success BOOLEAN DEFAULT true,
    failure_reason VARCHAR(255),
    session_id VARCHAR(255),
    additional_data JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes for performance
    INDEX idx_auth_audit_user (user_id),
    INDEX idx_auth_audit_event (event_type),
    INDEX idx_auth_audit_time (created_at),
    INDEX idx_auth_audit_ip (ip_address)
);

-- User device tracking for security
CREATE TABLE IF NOT EXISTS user_devices (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    device_fingerprint VARCHAR(255) NOT NULL,
    device_name VARCHAR(255),
    device_type VARCHAR(50), -- 'desktop', 'mobile', 'tablet'
    browser_name VARCHAR(100),
    browser_version VARCHAR(50),
    os_name VARCHAR(100),
    os_version VARCHAR(50),
    ip_address INET,
    location_data JSONB,
    last_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_trusted BOOLEAN DEFAULT false,
    trust_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints and indexes
    CONSTRAINT user_devices_unique_fingerprint UNIQUE (user_id, device_fingerprint),
    INDEX idx_user_devices_user (user_id),
    INDEX idx_user_devices_fingerprint (device_fingerprint),
    INDEX idx_user_devices_trusted (is_trusted)
);

-- Password history for security policies
CREATE TABLE IF NOT EXISTS password_history (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    password_hash VARCHAR(255) NOT NULL,
    set_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    set_by INTEGER REFERENCES users(id), -- Who changed the password
    reason VARCHAR(100), -- 'user_change', 'admin_reset', 'policy_reset'

    -- Index for performance
    INDEX idx_password_history_user (user_id, set_at DESC)
);

-- Security alerts and notifications
CREATE TABLE IF NOT EXISTS security_alerts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    alert_type VARCHAR(100) NOT NULL, -- 'suspicious_login', 'password_changed', etc.
    severity VARCHAR(20) DEFAULT 'medium', -- 'low', 'medium', 'high', 'critical'
    title VARCHAR(255) NOT NULL,
    message TEXT,
    alert_data JSONB,
    is_read BOOLEAN DEFAULT false,
    read_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    resolved_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_security_alerts_user (user_id),
    INDEX idx_security_alerts_type (alert_type),
    INDEX idx_security_alerts_severity (severity),
    INDEX idx_security_alerts_created (created_at)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_user_auth_methods_user_type ON user_auth_methods(user_id, method_type);
CREATE INDEX IF NOT EXISTS idx_user_auth_methods_enabled ON user_auth_methods(is_enabled);
CREATE INDEX IF NOT EXISTS idx_auth_backup_codes_user_used ON auth_backup_codes(user_id, is_used);
CREATE INDEX IF NOT EXISTS idx_magic_link_tokens_user_expires ON magic_link_tokens(user_id, expires_at);
CREATE INDEX IF NOT EXISTS idx_auth_audit_log_user_event ON auth_audit_log(user_id, event_type);
CREATE INDEX IF NOT EXISTS idx_user_devices_user_trusted ON user_devices(user_id, is_trusted);
CREATE INDEX IF NOT EXISTS idx_password_history_user_date ON password_history(user_id, set_at DESC);
CREATE INDEX IF NOT EXISTS idx_security_alerts_user_unread ON security_alerts(user_id, is_read) WHERE is_read = false;

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION update_user_auth_methods_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_user_auth_methods_updated_at
    BEFORE UPDATE ON user_auth_methods
    FOR EACH ROW EXECUTE FUNCTION update_user_auth_methods_updated_at();

-- Comments
COMMENT ON TABLE user_auth_methods IS 'User authentication methods (TOTP, magic links, etc.)';
COMMENT ON TABLE auth_backup_codes IS 'Backup codes for 2FA recovery';
COMMENT ON TABLE magic_link_tokens IS 'Tokens for magic link authentication';
COMMENT ON TABLE auth_audit_log IS 'Detailed authentication audit log';
COMMENT ON TABLE user_devices IS 'Tracked user devices for security';
COMMENT ON TABLE password_history IS 'Password change history for security';
COMMENT ON TABLE security_alerts IS 'Security alerts and notifications';

-- Insert default authentication methods for existing users (run this after migration)
-- This would be handled by a seeder or migration script
