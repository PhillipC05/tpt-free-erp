-- TPT Open ERP - Modules Table Schema
-- Migration: 005
-- Description: Module activation and management system

CREATE TABLE IF NOT EXISTS modules (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(200) NOT NULL,
    description TEXT,
    version VARCHAR(20) DEFAULT '1.0.0',
    category VARCHAR(50) NOT NULL,

    -- Module status and activation
    is_active BOOLEAN DEFAULT false,
    is_system_module BOOLEAN DEFAULT false,
    is_core_module BOOLEAN DEFAULT false,
    activation_order INTEGER DEFAULT 0,

    -- Dependencies
    dependencies JSONB DEFAULT '[]',
    conflicts JSONB DEFAULT '[]',

    -- Module configuration
    settings JSONB DEFAULT '{}',
    default_settings JSONB DEFAULT '{}',

    -- File paths and metadata
    path VARCHAR(255),
    icon VARCHAR(100),
    color VARCHAR(7),

    -- Version and update information
    installed_version VARCHAR(20),
    available_version VARCHAR(20),
    last_updated TIMESTAMP NULL,
    update_available BOOLEAN DEFAULT false,

    -- Permissions and access
    required_permissions JSONB DEFAULT '[]',
    admin_permissions JSONB DEFAULT '[]',

    -- Module metadata
    author VARCHAR(100),
    website VARCHAR(255),
    license VARCHAR(50),
    tags TEXT[],

    -- Audit Fields
    installed_by INTEGER REFERENCES users(id),
    activated_by INTEGER REFERENCES users(id),
    deactivated_by INTEGER REFERENCES users(id),
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    installed_at TIMESTAMP NULL,
    activated_at TIMESTAMP NULL,
    deactivated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT modules_version_format CHECK (version ~* '^\d+\.\d+\.\d+$'),
    CONSTRAINT modules_color_format CHECK (color IS NULL OR color ~* '^#[0-9A-Fa-f]{6}$')
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_modules_name ON modules(name);
CREATE INDEX IF NOT EXISTS idx_modules_uuid ON modules(uuid);
CREATE INDEX IF NOT EXISTS idx_modules_category ON modules(category);
CREATE INDEX IF NOT EXISTS idx_modules_active ON modules(is_active);
CREATE INDEX IF NOT EXISTS idx_modules_system ON modules(is_system_module);
CREATE INDEX IF NOT EXISTS idx_modules_core ON modules(is_core_module);
CREATE INDEX IF NOT EXISTS idx_modules_order ON modules(activation_order);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_modules_category_active ON modules(category, is_active);
CREATE INDEX IF NOT EXISTS idx_modules_active_order ON modules(is_active, activation_order) WHERE is_active = true;

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_modules_update_available ON modules(update_available) WHERE update_available = true;

-- Triggers for updated_at
CREATE TRIGGER update_modules_updated_at BEFORE UPDATE ON modules
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to get active modules
CREATE OR REPLACE FUNCTION get_active_modules()
RETURNS TABLE (
    id INTEGER,
    name VARCHAR(100),
    display_name VARCHAR(200),
    category VARCHAR(50),
    version VARCHAR(20),
    settings JSONB
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        m.id,
        m.name,
        m.display_name,
        m.category,
        m.version,
        m.settings
    FROM modules m
    WHERE m.is_active = true
      AND m.deleted_at IS NULL
    ORDER BY m.activation_order ASC, m.name ASC;
END;
$$ LANGUAGE plpgsql;

-- Function to check module dependencies
CREATE OR REPLACE FUNCTION check_module_dependencies(p_module_name VARCHAR(100))
RETURNS TABLE (
    dependency_name VARCHAR(100),
    is_satisfied BOOLEAN,
    installed_version VARCHAR(20),
    required_version VARCHAR(20)
) AS $$
DECLARE
    deps JSONB;
    dep_record RECORD;
BEGIN
    -- Get dependencies for the module
    SELECT dependencies INTO deps
    FROM modules
    WHERE name = p_module_name AND deleted_at IS NULL;

    -- If no dependencies, return empty result
    IF deps IS NULL OR jsonb_array_length(deps) = 0 THEN
        RETURN;
    END IF;

    -- Check each dependency
    FOR dep_record IN SELECT * FROM jsonb_array_elements_text(deps) AS dep(name)
    LOOP
        RETURN QUERY
        SELECT
            dep_record.dep::VARCHAR(100),
            CASE WHEN m.id IS NOT NULL THEN true ELSE false END,
            m.version,
            dep_record.dep -- This should be parsed for version requirements
        FROM modules m
        WHERE m.name = dep_record.dep
          AND m.is_active = true
          AND m.deleted_at IS NULL;
    END LOOP;
END;
$$ LANGUAGE plpgsql;

-- Insert core system modules
INSERT INTO modules (
    name, display_name, description, category, is_system_module, is_core_module,
    activation_order, required_permissions, admin_permissions
) VALUES
('users', 'User Management', 'User accounts, authentication, and profile management', 'system', true, true, 1,
 '["users.view"]', '["users.manage"]'),
('roles', 'Role Management', 'Role-based access control and permissions', 'system', true, true, 2,
 '["roles.view"]', '["roles.manage"]'),
('permissions', 'Permissions', 'Granular permission system', 'system', true, true, 3,
 '["system.settings"]', '["system.settings"]'),
('settings', 'System Settings', 'Global system configuration', 'system', true, true, 4,
 '["system.settings"]', '["system.settings"]'),
('audit', 'Audit Logging', 'Security and activity logging', 'system', true, true, 5,
 '["system.logs"]', '["system.logs"]'),
('modules', 'Module Management', 'Module activation and management', 'system', true, true, 6,
 '["modules.manage"]', '["modules.manage"]')
ON CONFLICT (name) DO NOTHING;

-- Comments
COMMENT ON TABLE modules IS 'Module definitions and activation status';
COMMENT ON COLUMN modules.category IS 'Module category (system, business, integration, etc.)';
COMMENT ON COLUMN modules.dependencies IS 'Array of required module dependencies';
COMMENT ON COLUMN modules.conflicts IS 'Array of conflicting modules';
COMMENT ON COLUMN modules.settings IS 'Module-specific configuration settings';
COMMENT ON COLUMN modules.required_permissions IS 'Permissions required to access this module';
COMMENT ON COLUMN modules.admin_permissions IS 'Permissions required to administer this module';
