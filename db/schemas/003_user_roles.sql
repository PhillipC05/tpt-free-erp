-- TPT Open ERP - User Roles Junction Table
-- Migration: 003
-- Description: Many-to-many relationship between users and roles

CREATE TABLE IF NOT EXISTS user_roles (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,

    -- Role assignment details
    assigned_by INTEGER REFERENCES users(id),
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT true,

    -- Additional permissions for this specific assignment
    additional_permissions JSONB DEFAULT '[]',
    permission_overrides JSONB DEFAULT '{}',

    -- Context-specific role (e.g., project-specific roles)
    context_type VARCHAR(50), -- 'global', 'project', 'department', etc.
    context_id INTEGER,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT user_roles_unique_active UNIQUE (user_id, role_id, context_type, context_id) DEFERRABLE INITIALLY DEFERRED,
    CONSTRAINT user_roles_no_expired CHECK (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_user_roles_user_id ON user_roles(user_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_role_id ON user_roles(role_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_active ON user_roles(is_active);
CREATE INDEX IF NOT EXISTS idx_user_roles_expires ON user_roles(expires_at);
CREATE INDEX IF NOT EXISTS idx_user_roles_context ON user_roles(context_type, context_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_assigned_by ON user_roles(assigned_by);

-- Composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_user_roles_user_active ON user_roles(user_id, is_active);
CREATE INDEX IF NOT EXISTS idx_user_roles_user_context ON user_roles(user_id, context_type, context_id);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_user_roles_active_not_expired ON user_roles(user_id, role_id)
    WHERE is_active = true AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP);

-- Triggers for updated_at
CREATE TRIGGER update_user_roles_updated_at BEFORE UPDATE ON user_roles
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to get active user roles
CREATE OR REPLACE FUNCTION get_active_user_roles(p_user_id INTEGER)
RETURNS TABLE (
    role_id INTEGER,
    role_name VARCHAR(100),
    permissions JSONB,
    context_type VARCHAR(50),
    context_id INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        r.id,
        r.name,
        r.permissions,
        ur.context_type,
        ur.context_id
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    WHERE ur.user_id = p_user_id
      AND ur.is_active = true
      AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
      AND r.deleted_at IS NULL
    ORDER BY r.level DESC;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE user_roles IS 'Junction table linking users to their assigned roles';
COMMENT ON COLUMN user_roles.additional_permissions IS 'Extra permissions granted to this specific user-role assignment';
COMMENT ON COLUMN user_roles.permission_overrides IS 'Permission overrides for this specific assignment';
COMMENT ON COLUMN user_roles.context_type IS 'Context type for role assignment (global, project, department, etc.)';
COMMENT ON COLUMN user_roles.context_id IS 'Context identifier for role assignment';
