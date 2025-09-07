-- TPT Open ERP - Time Tracking Table Schema
-- Migration: 025
-- Description: Time entries table for project management module

CREATE TABLE IF NOT EXISTS time_entries (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    user_id INTEGER NOT NULL REFERENCES users(id),
    task_id INTEGER REFERENCES tasks(id),
    project_id INTEGER REFERENCES projects(id),
    description TEXT,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP,
    duration_minutes INTEGER,
    billable BOOLEAN DEFAULT true,
    rate_per_hour DECIMAL(10,2),

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_time_entries_uuid ON time_entries(uuid);
CREATE INDEX IF NOT EXISTS idx_time_entries_user ON time_entries(user_id);
CREATE INDEX IF NOT EXISTS idx_time_entries_task ON time_entries(task_id);
CREATE INDEX IF NOT EXISTS idx_time_entries_project ON time_entries(project_id);
CREATE INDEX IF NOT EXISTS idx_time_entries_start_time ON time_entries(start_time);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION update_time_entries_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_time_entries_updated_at BEFORE UPDATE ON time_entries
    FOR EACH ROW EXECUTE FUNCTION update_time_entries_updated_at();

-- Comments
COMMENT ON TABLE time_entries IS 'Time tracking entries for tasks and projects';
COMMENT ON COLUMN time_entries.uuid IS 'Universally unique identifier';
COMMENT ON COLUMN time_entries.user_id IS 'User who logged the time';
COMMENT ON COLUMN time_entries.task_id IS 'Associated task';
COMMENT ON COLUMN time_entries.project_id IS 'Associated project';
COMMENT ON COLUMN time_entries.duration_minutes IS 'Duration in minutes';
COMMENT ON COLUMN time_entries.billable IS 'Whether this time is billable';
COMMENT ON COLUMN time_entries.rate_per_hour IS 'Hourly rate for billing';
