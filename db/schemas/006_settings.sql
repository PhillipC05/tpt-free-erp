-- TPT Open ERP - Settings Table Schema
-- Migration: 006
-- Description: Global system settings and configuration

CREATE TABLE IF NOT EXISTS settings (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    key VARCHAR(255) NOT NULL UNIQUE,
    value TEXT,
    value_type VARCHAR(20) DEFAULT 'string',
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    module VARCHAR(100),

    -- Setting properties
    is_system_setting BOOLEAN DEFAULT false,
    is_encrypted BOOLEAN DEFAULT false,
    requires_restart BOOLEAN DEFAULT false,
    validation_rules JSONB DEFAULT '{}',

    -- Setting metadata
    display_name VARCHAR(200),
    description TEXT,
    default_value TEXT,
    options JSONB DEFAULT '[]',

    -- Access control
    read_permissions JSONB DEFAULT '[]',
    write_permissions JSONB DEFAULT '[]',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT settings_value_type CHECK (value_type IN ('string', 'integer', 'float', 'boolean', 'json', 'array')),
    CONSTRAINT settings_key_format CHECK (key ~* '^[a-zA-Z][a-zA-Z0-9_.-]*$')
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(key);
CREATE INDEX IF NOT EXISTS idx_settings_uuid ON settings(uuid);
CREATE INDEX IF NOT EXISTS idx_settings_category ON settings(category);
CREATE INDEX IF NOT EXISTS idx_settings_module ON settings(module);
CREATE INDEX IF NOT EXISTS idx_settings_system ON settings(is_system_setting);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_settings_category_module ON settings(category, module);
CREATE INDEX IF NOT EXISTS idx_settings_module_key ON settings(module, key);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_settings_active ON settings(deleted_at) WHERE deleted_at IS NULL;

-- Triggers for updated_at
CREATE TRIGGER update_settings_updated_at BEFORE UPDATE ON settings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to get setting value with type casting
CREATE OR REPLACE FUNCTION get_setting(p_key VARCHAR(255), p_default_value TEXT DEFAULT NULL)
RETURNS TEXT AS $$
DECLARE
    setting_value TEXT;
    setting_type VARCHAR(20);
BEGIN
    SELECT value, value_type INTO setting_value, setting_type
    FROM settings
    WHERE key = p_key AND deleted_at IS NULL;

    IF setting_value IS NOT NULL THEN
        RETURN setting_value;
    ELSE
        RETURN p_default_value;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Function to set setting value
CREATE OR REPLACE FUNCTION set_setting(p_key VARCHAR(255), p_value TEXT, p_user_id INTEGER DEFAULT NULL)
RETURNS BOOLEAN AS $$
BEGIN
    INSERT INTO settings (key, value, updated_by, created_by)
    VALUES (p_key, p_value, p_user_id, p_user_id)
    ON CONFLICT (key) DO UPDATE SET
        value = EXCLUDED.value,
        updated_by = EXCLUDED.updated_by,
        updated_at = CURRENT_TIMESTAMP;

    RETURN true;
END;
$$ LANGUAGE plpgsql;

-- Insert default system settings
INSERT INTO settings (
    key, value, value_type, category, is_system_setting,
    display_name, description, default_value
) VALUES
-- Application Settings
('app.name', 'TPT Open ERP', 'string', 'application', true,
 'Application Name', 'The name of the application', 'TPT Open ERP'),
('app.version', '1.0.0', 'string', 'application', true,
 'Application Version', 'Current application version', '1.0.0'),
('app.debug', 'false', 'boolean', 'application', true,
 'Debug Mode', 'Enable debug mode for development', 'false'),
('app.timezone', 'UTC', 'string', 'application', true,
 'Timezone', 'Default application timezone', 'UTC'),
('app.locale', 'en', 'string', 'application', true,
 'Locale', 'Default application locale', 'en'),

-- Security Settings
('security.session_timeout', '7200', 'integer', 'security', true,
 'Session Timeout', 'Session timeout in seconds', '7200'),
('security.max_login_attempts', '5', 'integer', 'security', true,
 'Max Login Attempts', 'Maximum failed login attempts before lockout', '5'),
('security.lockout_duration', '900', 'integer', 'security', true,
 'Lockout Duration', 'Account lockout duration in seconds', '900'),
('security.password_min_length', '8', 'integer', 'security', true,
 'Minimum Password Length', 'Minimum password length requirement', '8'),
('security.two_factor_required', 'false', 'boolean', 'security', true,
 'Two-Factor Required', 'Require 2FA for all users', 'false'),

-- Email Settings
('email.smtp_host', '', 'string', 'email', false,
 'SMTP Host', 'SMTP server hostname', ''),
('email.smtp_port', '587', 'integer', 'email', false,
 'SMTP Port', 'SMTP server port', '587'),
('email.smtp_username', '', 'string', 'email', false,
 'SMTP Username', 'SMTP authentication username', ''),
('email.from_address', 'noreply@tpt-erp.com', 'string', 'email', false,
 'From Address', 'Default email from address', 'noreply@tpt-erp.com'),
('email.from_name', 'TPT Open ERP', 'string', 'email', false,
 'From Name', 'Default email from name', 'TPT Open ERP'),

-- File Storage Settings
('storage.default_disk', 'local', 'string', 'storage', false,
 'Default Storage Disk', 'Default file storage disk', 'local'),
('storage.max_file_size', '10485760', 'integer', 'storage', false,
 'Max File Size', 'Maximum file upload size in bytes', '10485760'),
('storage.allowed_extensions', '["jpg","jpeg","png","pdf","doc","docx","xls","xlsx"]', 'json', 'storage', false,
 'Allowed Extensions', 'Allowed file extensions for upload', '["jpg","jpeg","png","pdf","doc","docx","xls","xlsx"]'),

-- Module Settings
('modules.auto_activate_core', 'true', 'boolean', 'modules', true,
 'Auto Activate Core', 'Automatically activate core modules', 'true'),
('modules.allow_custom_modules', 'true', 'boolean', 'modules', false,
 'Allow Custom Modules', 'Allow installation of custom modules', 'true'),

-- Performance Settings
('performance.cache_enabled', 'true', 'boolean', 'performance', true,
 'Cache Enabled', 'Enable caching for performance', 'true'),
('performance.query_cache_ttl', '3600', 'integer', 'performance', true,
 'Query Cache TTL', 'Query cache time-to-live in seconds', '3600'),
('performance.page_cache_ttl', '1800', 'integer', 'performance', true,
 'Page Cache TTL', 'Page cache time-to-live in seconds', '1800'),

-- API Settings
('api.rate_limit', '1000', 'integer', 'api', true,
 'API Rate Limit', 'API requests per hour per user', '1000'),
('api.documentation_enabled', 'true', 'boolean', 'api', true,
 'API Documentation', 'Enable API documentation', 'true'),
('api.version', 'v1', 'string', 'api', true,
 'API Version', 'Current API version', 'v1')
ON CONFLICT (key) DO NOTHING;

-- Comments
COMMENT ON TABLE settings IS 'Global system settings and configuration';
COMMENT ON COLUMN settings.key IS 'Unique setting key identifier';
COMMENT ON COLUMN settings.value_type IS 'Data type of the setting value';
COMMENT ON COLUMN settings.category IS 'Setting category for organization';
COMMENT ON COLUMN settings.module IS 'Module this setting belongs to (if module-specific)';
COMMENT ON COLUMN settings.validation_rules IS 'JSON validation rules for the setting';
COMMENT ON COLUMN settings.options IS 'JSON array of available options for select-type settings';
