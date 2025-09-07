-- TPT Open ERP - Reporting Reports
-- Migration: 021
-- Description: Custom report builder and management

CREATE TABLE IF NOT EXISTS reporting_reports (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    report_number VARCHAR(50) NOT NULL UNIQUE,
    report_name VARCHAR(255) NOT NULL,

    -- Report metadata
    report_type VARCHAR(50) DEFAULT 'custom', -- custom, standard, scheduled, dashboard
    category VARCHAR(100) NOT NULL,
    subcategory VARCHAR(100),

    -- Report configuration
    data_source JSONB NOT NULL DEFAULT '{}', -- tables, joins, filters
    columns JSONB NOT NULL DEFAULT '[]', -- selected columns with formatting
    filters JSONB DEFAULT '{}', -- filter conditions
    sorting JSONB DEFAULT '[]', -- sort order
    grouping JSONB DEFAULT '[]', -- group by fields

    -- Report properties
    is_active BOOLEAN DEFAULT true,
    is_public BOOLEAN DEFAULT false,
    is_scheduled BOOLEAN DEFAULT false,
    schedule_config JSONB DEFAULT '{}',

    -- Access control
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    allowed_users JSONB DEFAULT '[]', -- specific users who can access
    allowed_roles JSONB DEFAULT '[]', -- roles that can access

    -- Report settings
    output_format VARCHAR(20) DEFAULT 'html', -- html, pdf, excel, csv, json
    page_size VARCHAR(20) DEFAULT 'a4',
    orientation VARCHAR(10) DEFAULT 'portrait', -- portrait, landscape
    theme VARCHAR(50) DEFAULT 'default',

    -- Performance and caching
    cache_enabled BOOLEAN DEFAULT true,
    cache_ttl INTEGER DEFAULT 3600, -- seconds
    last_run_at TIMESTAMP NULL,
    execution_time_ms INTEGER,

    -- Report statistics
    run_count INTEGER DEFAULT 0,
    last_accessed_at TIMESTAMP NULL,
    average_execution_time DECIMAL(8,2),

    -- Audit Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT reporting_reports_report_type CHECK (report_type IN ('custom', 'standard', 'scheduled', 'dashboard', 'template')),
    CONSTRAINT reporting_reports_output_format CHECK (output_format IN ('html', 'pdf', 'excel', 'csv', 'json', 'xml')),
    CONSTRAINT reporting_reports_orientation CHECK (orientation IN ('portrait', 'landscape')),
    CONSTRAINT reporting_reports_cache_ttl_positive CHECK (cache_ttl > 0)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_reporting_reports_number ON reporting_reports(report_number);
CREATE INDEX IF NOT EXISTS idx_reporting_reports_type ON reporting_reports(report_type);
CREATE INDEX IF NOT EXISTS idx_reporting_reports_category ON reporting_reports(category);
CREATE INDEX IF NOT EXISTS idx_reporting_reports_active ON reporting_reports(is_active);
CREATE INDEX IF NOT EXISTS idx_reporting_reports_public ON reporting_reports(is_public);
CREATE INDEX IF NOT EXISTS idx_reporting_reports_scheduled ON reporting_reports(is_scheduled);
CREATE INDEX IF NOT EXISTS idx_reporting_reports_created_by ON reporting_reports(created_by);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_reporting_reports_category_type ON reporting_reports(category, report_type);
CREATE INDEX IF NOT EXISTS idx_reporting_reports_active_public ON reporting_reports(is_active, is_public);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_reporting_reports_scheduled_active ON reporting_reports(id, report_name)
    WHERE is_scheduled = true AND is_active = true;

-- Triggers for updated_at
CREATE TRIGGER update_reporting_reports_updated_at BEFORE UPDATE ON reporting_reports
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to generate report number
CREATE OR REPLACE FUNCTION generate_report_number()
RETURNS VARCHAR(50) AS $$
DECLARE
    current_year INTEGER;
    sequence_number INTEGER;
    report_num VARCHAR(50);
BEGIN
    current_year := EXTRACT(YEAR FROM CURRENT_DATE);

    -- Get next sequence number for the year
    SELECT COALESCE(MAX(CAST(SUBSTRING(report_number FROM '[0-9]+$') AS INTEGER)), 0) + 1
    INTO sequence_number
    FROM reporting_reports
    WHERE report_number LIKE 'RPT-' || current_year || '-%';

    report_num := 'RPT-' || current_year || '-' || LPAD(sequence_number::TEXT, 6, '0');

    RETURN report_num;
END;
$$ LANGUAGE plpgsql;

-- Function to get user accessible reports
CREATE OR REPLACE FUNCTION get_user_accessible_reports(p_user_id INTEGER)
RETURNS TABLE (
    report_id INTEGER,
    report_name VARCHAR(255),
    category VARCHAR(100),
    report_type VARCHAR(50),
    is_public BOOLEAN,
    created_by_name VARCHAR(200)
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        rr.id,
        rr.report_name,
        rr.category,
        rr.report_type,
        rr.is_public,
        CONCAT(u.first_name, ' ', u.last_name)
    FROM reporting_reports rr
    LEFT JOIN users u ON rr.created_by = u.id
    WHERE rr.is_active = true
      AND rr.deleted_at IS NULL
      AND (
          rr.is_public = true
          OR rr.created_by = p_user_id
          OR rr.allowed_users @> jsonb_build_array(p_user_id)
          OR EXISTS (
              SELECT 1 FROM user_roles ur
              JOIN roles r ON ur.role_id = r.id
              WHERE ur.user_id = p_user_id
                AND ur.is_active = true
                AND (ur.expires_at IS NULL OR ur.expires_at > CURRENT_TIMESTAMP)
                AND rr.allowed_roles ? r.name
          )
      )
    ORDER BY rr.category, rr.report_name;
END;
$$ LANGUAGE plpgsql;

-- Function to update report statistics
CREATE OR REPLACE FUNCTION update_report_statistics(p_report_id INTEGER, p_execution_time_ms INTEGER)
RETURNS VOID AS $$
DECLARE
    current_run_count INTEGER;
    current_avg_time DECIMAL(8,2);
BEGIN
    -- Get current statistics
    SELECT run_count, average_execution_time
    INTO current_run_count, current_avg_time
    FROM reporting_reports
    WHERE id = p_report_id;

    -- Update statistics
    UPDATE reporting_reports
    SET run_count = current_run_count + 1,
        last_run_at = CURRENT_TIMESTAMP,
        last_accessed_at = CURRENT_TIMESTAMP,
        execution_time_ms = p_execution_time_ms,
        average_execution_time = CASE
            WHEN current_run_count = 0 THEN p_execution_time_ms::DECIMAL(8,2)
            ELSE ((current_avg_time * current_run_count) + p_execution_time_ms) / (current_run_count + 1)
        END,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_report_id;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE reporting_reports IS 'Custom report definitions with configuration and access control';
COMMENT ON COLUMN reporting_reports.data_source IS 'JSON configuration of tables, joins, and data sources';
COMMENT ON COLUMN reporting_reports.columns IS 'JSON array of selected columns with formatting options';
COMMENT ON COLUMN reporting_reports.filters IS 'JSON object defining filter conditions';
COMMENT ON COLUMN reporting_reports.allowed_users IS 'JSON array of user IDs with access to this report';
COMMENT ON COLUMN reporting_reports.allowed_roles IS 'JSON array of role names with access to this report';
COMMENT ON COLUMN reporting_reports.schedule_config IS 'JSON configuration for scheduled report execution';
