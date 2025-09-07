-- TPT Open ERP - Permissions Table Schema
-- Migration: 004
-- Description: Granular permissions system

CREATE TABLE IF NOT EXISTS permissions (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(150) NOT NULL UNIQUE,
    display_name VARCHAR(200) NOT NULL,
    description TEXT,
    module VARCHAR(100) NOT NULL,
    resource VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,

    -- Permission hierarchy
    parent_permission_id INTEGER REFERENCES permissions(id),

    -- Permission settings
    requires_approval BOOLEAN DEFAULT false,
    approval_levels INTEGER DEFAULT 1,
    is_system_permission BOOLEAN DEFAULT false,

    -- Conditions and constraints
    conditions JSONB DEFAULT '{}',
    constraints JSONB DEFAULT '{}',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT permissions_unique_name UNIQUE (name),
    CONSTRAINT permissions_action_format CHECK (action IN ('create', 'read', 'update', 'delete', 'execute', 'approve', 'manage', '*')),
    CONSTRAINT permissions_no_self_parent CHECK (id != parent_permission_id)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_permissions_name ON permissions(name);
CREATE INDEX IF NOT EXISTS idx_permissions_uuid ON permissions(uuid);
CREATE INDEX IF NOT EXISTS idx_permissions_module ON permissions(module);
CREATE INDEX IF NOT EXISTS idx_permissions_resource ON permissions(resource);
CREATE INDEX IF NOT EXISTS idx_permissions_action ON permissions(action);
CREATE INDEX IF NOT EXISTS idx_permissions_parent ON permissions(parent_permission_id);
CREATE INDEX IF NOT EXISTS idx_permissions_system ON permissions(is_system_permission);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_permissions_module_resource ON permissions(module, resource);
CREATE INDEX IF NOT EXISTS idx_permissions_module_action ON permissions(module, action);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_permissions_active ON permissions(deleted_at) WHERE deleted_at IS NULL;

-- Triggers for updated_at
CREATE TRIGGER update_permissions_updated_at BEFORE UPDATE ON permissions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to get user permissions
CREATE OR REPLACE FUNCTION get_user_permissions(p_user_id INTEGER)
RETURNS TABLE (
    permission_name VARCHAR(150),
    module VARCHAR(100),
    resource VARCHAR(100),
    action VARCHAR(50)
) AS $$
BEGIN
    RETURN QUERY
    SELECT DISTINCT
        p.name,
        p.module,
        p.resource,
        p.action
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    JOIN LATERAL jsonb_array_elements_text(r.permissions) AS rp(perm_name) ON true
    JOIN permissions p ON p.name = rp.perm_name
    WHERE ur.user_id = p_user_id
      AND ur.is_active = true
      AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
      AND r.deleted_at IS NULL
      AND p.deleted_at IS NULL
    UNION
    SELECT DISTINCT
        p.name,
        p.module,
        p.resource,
        p.action
    FROM user_roles ur
    JOIN LATERAL jsonb_array_elements_text(ur.additional_permissions) AS ap(perm_name) ON true
    JOIN permissions p ON p.name = ap.perm_name
    WHERE ur.user_id = p_user_id
      AND ur.is_active = true
      AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
      AND p.deleted_at IS NULL;
END;
$$ LANGUAGE plpgsql;

-- Insert core system permissions
INSERT INTO permissions (name, display_name, description, module, resource, action, is_system_permission) VALUES
-- User Management
('users.view', 'View Users', 'View user accounts', 'users', 'user', 'read', true),
('users.create', 'Create Users', 'Create new user accounts', 'users', 'user', 'create', true),
('users.update', 'Update Users', 'Modify user accounts', 'users', 'user', 'update', true),
('users.delete', 'Delete Users', 'Delete user accounts', 'users', 'user', 'delete', true),
('users.manage', 'Manage Users', 'Full user management', 'users', 'user', 'manage', true),

-- Role Management
('roles.view', 'View Roles', 'View role definitions', 'users', 'role', 'read', true),
('roles.create', 'Create Roles', 'Create new roles', 'users', 'role', 'create', true),
('roles.update', 'Update Roles', 'Modify role definitions', 'users', 'role', 'update', true),
('roles.delete', 'Delete Roles', 'Delete roles', 'users', 'role', 'delete', true),
('roles.manage', 'Manage Roles', 'Full role management', 'users', 'role', 'manage', true),

-- System Administration
('system.settings', 'System Settings', 'Access system settings', 'system', 'settings', 'manage', true),
('system.logs', 'System Logs', 'View system logs', 'system', 'logs', 'read', true),
('system.backup', 'System Backup', 'Create system backups', 'system', 'backup', 'execute', true),
('modules.manage', 'Module Management', 'Manage system modules', 'system', 'modules', 'manage', true),

-- Profile Management
('profile.edit', 'Edit Profile', 'Edit own profile', 'users', 'profile', 'update', true),
('profile.view', 'View Profile', 'View own profile', 'users', 'profile', 'read', true)
ON CONFLICT (name) DO NOTHING;

-- Comments
COMMENT ON TABLE permissions IS 'Granular permissions for access control';
COMMENT ON COLUMN permissions.module IS 'Module this permission belongs to';
COMMENT ON COLUMN permissions.resource IS 'Resource type being protected';
COMMENT ON COLUMN permissions.action IS 'Action being performed (CRUD + custom)';
COMMENT ON COLUMN permissions.conditions IS 'JSON conditions for permission evaluation';
COMMENT ON COLUMN permissions.constraints IS 'JSON constraints on permission usage';
