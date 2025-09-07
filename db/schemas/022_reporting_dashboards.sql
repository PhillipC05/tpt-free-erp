-- TPT Open ERP - Reporting Dashboards
-- Migration: 022
-- Description: Customizable dashboard builder with widgets and analytics

CREATE TABLE IF NOT EXISTS reporting_dashboards (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    dashboard_number VARCHAR(50) NOT NULL UNIQUE,
    dashboard_name VARCHAR(255) NOT NULL,
    description TEXT,

    -- Dashboard properties
    dashboard_type VARCHAR(20) DEFAULT 'user', -- user, system, public, template
    category VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    is_public BOOLEAN DEFAULT false,
    is_default BOOLEAN DEFAULT false,

    -- Layout configuration
    layout_config JSONB DEFAULT '{}', -- grid layout, responsive settings
    theme VARCHAR(50) DEFAULT 'default',
    refresh_interval INTEGER DEFAULT 300, -- seconds

    -- Access control
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    allowed_users JSONB DEFAULT '[]',
    allowed_roles JSONB DEFAULT '[]',

    -- Dashboard settings
    auto_refresh BOOLEAN DEFAULT true,
    show_filters BOOLEAN DEFAULT true,
    show_export BOOLEAN DEFAULT true,
    show_fullscreen BOOLEAN DEFAULT true,

    -- Performance and caching
    cache_enabled BOOLEAN DEFAULT true,
    cache_ttl INTEGER DEFAULT 300, -- seconds
    last_refreshed_at TIMESTAMP NULL,

    -- Statistics
    view_count INTEGER DEFAULT 0,
    last_viewed_at TIMESTAMP NULL,
    average_load_time DECIMAL(6,2),

    -- Audit Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT reporting_dashboards_dashboard_type CHECK (dashboard_type IN ('user', 'system', 'public', 'template')),
    CONSTRAINT reporting_dashboards_refresh_interval_positive CHECK (refresh_interval > 0),
    CONSTRAINT reporting_dashboards_cache_ttl_positive CHECK (cache_ttl > 0)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_reporting_dashboards_number ON reporting_dashboards(dashboard_number);
CREATE INDEX IF NOT EXISTS idx_reporting_dashboards_type ON reporting_dashboards(dashboard_type);
CREATE INDEX IF NOT EXISTS idx_reporting_dashboards_category ON reporting_dashboards(category);
CREATE INDEX IF NOT EXISTS idx_reporting_dashboards_active ON reporting_dashboards(is_active);
CREATE INDEX IF NOT EXISTS idx_reporting_dashboards_public ON reporting_dashboards(is_public);
CREATE INDEX IF NOT EXISTS idx_reporting_dashboards_default ON reporting_dashboards(is_default);
CREATE INDEX IF NOT EXISTS idx_reporting_dashboards_created_by ON reporting_dashboards(created_by);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_reporting_dashboards_type_active ON reporting_dashboards(dashboard_type, is_active);
CREATE INDEX IF NOT EXISTS idx_reporting_dashboards_category_type ON reporting_dashboards(category, dashboard_type);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_reporting_dashboards_public_active ON reporting_dashboards(id, dashboard_name)
    WHERE is_public = true AND is_active = true;

-- Triggers for updated_at
CREATE TRIGGER update_reporting_dashboards_updated_at BEFORE UPDATE ON reporting_dashboards
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to generate dashboard number
CREATE OR REPLACE FUNCTION generate_dashboard_number()
RETURNS VARCHAR(50) AS $$
DECLARE
    current_year INTEGER;
    sequence_number INTEGER;
    dashboard_num VARCHAR(50);
BEGIN
    current_year := EXTRACT(YEAR FROM CURRENT_DATE);

    -- Get next sequence number for the year
    SELECT COALESCE(MAX(CAST(SUBSTRING(dashboard_number FROM '[0-9]+$') AS INTEGER)), 0) + 1
    INTO sequence_number
    FROM reporting_dashboards
    WHERE dashboard_number LIKE 'DB-' || current_year || '-%';

    dashboard_num := 'DB-' || current_year || '-' || LPAD(sequence_number::TEXT, 6, '0');

    RETURN dashboard_num;
END;
$$ LANGUAGE plpgsql;

-- Function to get user accessible dashboards
CREATE OR REPLACE FUNCTION get_user_accessible_dashboards(p_user_id INTEGER)
RETURNS TABLE (
    dashboard_id INTEGER,
    dashboard_name VARCHAR(255),
    category VARCHAR(100),
    dashboard_type VARCHAR(20),
    is_public BOOLEAN,
    is_default BOOLEAN,
    created_by_name VARCHAR(200)
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        rd.id,
        rd.dashboard_name,
        rd.category,
        rd.dashboard_type,
        rd.is_public,
        rd.is_default,
        CONCAT(u.first_name, ' ', u.last_name)
    FROM reporting_dashboards rd
    LEFT JOIN users u ON rd.created_by = u.id
    WHERE rd.is_active = true
      AND rd.deleted_at IS NULL
      AND (
          rd.is_public = true
          OR rd.created_by = p_user_id
          OR rd.allowed_users @> jsonb_build_array(p_user_id)
          OR EXISTS (
              SELECT 1 FROM user_roles ur
              JOIN roles r ON ur.role_id = r.id
              WHERE ur.user_id = p_user_id
                AND ur.is_active = true
                AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
                AND rd.allowed_roles ? r.name
          )
      )
    ORDER BY rd.is_default DESC, rd.category, rd.dashboard_name;
END;
$$ LANGUAGE plpgsql;

-- Function to update dashboard statistics
CREATE OR REPLACE FUNCTION update_dashboard_statistics(p_dashboard_id INTEGER, p_load_time DECIMAL)
RETURNS VOID AS $$
DECLARE
    current_view_count INTEGER;
    current_avg_time DECIMAL(6,2);
BEGIN
    -- Get current statistics
    SELECT view_count, average_load_time
    INTO current_view_count, current_avg_time
    FROM reporting_dashboards
    WHERE id = p_dashboard_id;

    -- Update statistics
    UPDATE reporting_dashboards
    SET view_count = current_view_count + 1,
        last_viewed_at = CURRENT_TIMESTAMP,
        average_load_time = CASE
            WHEN current_view_count = 0 THEN p_load_time
            ELSE ((current_avg_time * current_view_count) + p_load_time) / (current_view_count + 1)
        END,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_dashboard_id;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE reporting_dashboards IS 'Dashboard definitions with layout and access control';
COMMENT ON COLUMN reporting_dashboards.layout_config IS 'JSON configuration of grid layout and widget positions';
COMMENT ON COLUMN reporting_dashboards.allowed_users IS 'JSON array of user IDs with access to this dashboard';
COMMENT ON COLUMN reporting_dashboards.allowed_roles IS 'JSON array of role names with access to this dashboard';
COMMENT ON COLUMN reporting_dashboards.refresh_interval IS 'Auto-refresh interval in seconds';
