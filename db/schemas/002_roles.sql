-- TPT Open ERP - Roles Table Schema
-- Migration: 002
-- Description: Role-based access control system

CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(200) NOT NULL,
    description TEXT,
    level INTEGER NOT NULL DEFAULT 1,
    is_system_role BOOLEAN DEFAULT false,
    is_default BOOLEAN DEFAULT false,

    -- Hierarchical roles
    parent_role_id INTEGER REFERENCES roles(id),

    -- Permissions as JSON array
    permissions JSONB DEFAULT '[]',

    -- Module-specific permissions
    module_permissions JSONB DEFAULT '{}',

    -- Role settings
    settings JSONB DEFAULT '{}',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT roles_level_range CHECK (level >= 1 AND level <= 100),
    CONSTRAINT roles_no_self_parent CHECK (id != parent_role_id)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_roles_name ON roles(name);
CREATE INDEX IF NOT EXISTS idx_roles_uuid ON roles(uuid);
CREATE INDEX IF NOT EXISTS idx_roles_level ON roles(level);
CREATE INDEX IF NOT EXISTS idx_roles_parent ON roles(parent_role_id);
CREATE INDEX IF NOT EXISTS idx_roles_system ON roles(is_system_role);
CREATE INDEX IF NOT EXISTS idx_roles_default ON roles(is_default);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_roles_active ON roles(deleted_at) WHERE deleted_at IS NULL;

-- Triggers for updated_at
CREATE TRIGGER update_roles_updated_at BEFORE UPDATE ON roles
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Comments
COMMENT ON TABLE roles IS 'Role definitions for access control system';
COMMENT ON COLUMN roles.level IS 'Hierarchical level (1=lowest, 100=highest)';
COMMENT ON COLUMN roles.permissions IS 'Array of permission strings';
COMMENT ON COLUMN roles.module_permissions IS 'Module-specific permission overrides';
COMMENT ON COLUMN roles.settings IS 'Role-specific configuration settings';

-- Insert default system roles
INSERT INTO roles (name, display_name, description, level, is_system_role, is_default, permissions) VALUES
('super_admin', 'Super Administrator', 'Full system access with all permissions', 100, true, false, '["*"]'),
('admin', 'Administrator', 'Administrative access to most system functions', 80, true, false, '["users.manage", "roles.manage", "system.settings", "modules.manage"]'),
('manager', 'Manager', 'Management access to business operations', 60, true, false, '["reports.view", "employees.manage", "projects.manage", "inventory.view"]'),
('user', 'User', 'Standard user access', 20, true, true, '["profile.edit", "tasks.view", "messages.send"]'),
('guest', 'Guest', 'Limited read-only access', 10, true, false, '["public.view"]')
ON CONFLICT (name) DO NOTHING;
