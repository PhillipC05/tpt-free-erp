-- TPT Open ERP - Projects Table Schema
-- Migration: 023
-- Description: Projects table for project management module

CREATE TABLE IF NOT EXISTS projects (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    code VARCHAR(50) UNIQUE,
    status VARCHAR(50) DEFAULT 'planning',
    priority VARCHAR(20) DEFAULT 'medium',
    start_date DATE,
    end_date DATE,
    budget DECIMAL(15,2),
    actual_cost DECIMAL(15,2) DEFAULT 0,
    progress_percentage INTEGER DEFAULT 0,
    manager_id INTEGER REFERENCES users(id),
    client_id INTEGER REFERENCES customers(id),
    department_id INTEGER,
    tags TEXT[],

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT projects_status_check CHECK (status IN ('planning', 'active', 'on_hold', 'completed', 'cancelled')),
    CONSTRAINT projects_priority_check CHECK (priority IN ('low', 'medium', 'high', 'urgent')),
    CONSTRAINT projects_progress_check CHECK (progress_percentage >= 0 AND progress_percentage <= 100)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_projects_uuid ON projects(uuid);
CREATE INDEX IF NOT EXISTS idx_projects_status ON projects(status);
CREATE INDEX IF NOT EXISTS idx_projects_manager ON projects(manager_id);
CREATE INDEX IF NOT EXISTS idx_projects_client ON projects(client_id);
CREATE INDEX IF NOT EXISTS idx_projects_dates ON projects(start_date, end_date);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION update_projects_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_projects_updated_at BEFORE UPDATE ON projects
    FOR EACH ROW EXECUTE FUNCTION update_projects_updated_at();

-- Comments
COMMENT ON TABLE projects IS 'Projects table for managing project information';
COMMENT ON COLUMN projects.uuid IS 'Universally unique identifier';
COMMENT ON COLUMN projects.code IS 'Unique project code for identification';
COMMENT ON COLUMN projects.status IS 'Current status of the project';
COMMENT ON COLUMN projects.priority IS 'Priority level of the project';
COMMENT ON COLUMN projects.progress_percentage IS 'Completion percentage of the project';
