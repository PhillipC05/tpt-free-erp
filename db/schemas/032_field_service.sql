-- TPT Open ERP - Field Service Schema
-- Migration: 032
-- Description: Field service management tables for service calls, technicians, and scheduling

-- Service Calls Table
CREATE TABLE IF NOT EXISTS service_calls (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    call_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INTEGER NOT NULL REFERENCES customers(id),
    contact_person VARCHAR(255),
    contact_phone VARCHAR(50),
    contact_email VARCHAR(255),
    service_type VARCHAR(100) NOT NULL,
    priority VARCHAR(20) DEFAULT 'medium',
    status VARCHAR(50) DEFAULT 'new',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location_address TEXT,
    location_city VARCHAR(100),
    location_state VARCHAR(100),
    location_zip VARCHAR(20),
    location_country VARCHAR(100),
    scheduled_date DATE,
    scheduled_time TIME,
    estimated_duration INTERVAL,
    actual_start_time TIMESTAMP NULL,
    actual_end_time TIMESTAMP NULL,
    assigned_technician_id INTEGER REFERENCES users(id),
    backup_technician_id INTEGER REFERENCES users(id),
    equipment_details TEXT,
    symptoms TEXT,
    diagnosis TEXT,
    resolution TEXT,
    parts_used TEXT[],
    labor_hours DECIMAL(8,2),
    travel_time DECIMAL(8,2),
    total_cost DECIMAL(15,2),
    customer_rating INTEGER,
    customer_feedback TEXT,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT service_calls_priority_check CHECK (priority IN ('low', 'medium', 'high', 'critical')),
    CONSTRAINT service_calls_status_check CHECK (status IN ('new', 'scheduled', 'in_progress', 'on_hold', 'completed', 'cancelled')),
    CONSTRAINT service_calls_rating_check CHECK (customer_rating >= 1 AND customer_rating <= 5)
);

-- Technicians Table
CREATE TABLE IF NOT EXISTS technicians (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    employee_id VARCHAR(50) UNIQUE,
    specialization VARCHAR(255),
    skill_level VARCHAR(20) DEFAULT 'intermediate',
    hourly_rate DECIMAL(10,2),
    availability_schedule JSONB,
    territories TEXT[],
    certifications TEXT[],
    languages TEXT[],
    is_active BOOLEAN DEFAULT TRUE,
    hire_date DATE,
    license_number VARCHAR(100),
    license_expiry DATE,
    insurance_provider VARCHAR(255),
    insurance_policy VARCHAR(255),
    insurance_expiry DATE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT technicians_skill_level_check CHECK (skill_level IN ('beginner', 'intermediate', 'advanced', 'expert'))
);

-- Technician Skills Table
CREATE TABLE IF NOT EXISTS technician_skills (
    id SERIAL PRIMARY KEY,
    technician_id INTEGER NOT NULL REFERENCES technicians(id) ON DELETE CASCADE,
    skill_name VARCHAR(255) NOT NULL,
    proficiency_level INTEGER DEFAULT 1,
    certification_date DATE,
    certification_expiry DATE,
    is_active BOOLEAN DEFAULT TRUE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT technician_skills_proficiency_check CHECK (proficiency_level >= 1 AND proficiency_level <= 5),
    UNIQUE(technician_id, skill_name)
);

-- Service Schedules Table
CREATE TABLE IF NOT EXISTS service_schedules (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    technician_id INTEGER NOT NULL REFERENCES technicians(id),
    service_call_id INTEGER REFERENCES service_calls(id),
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    schedule_type VARCHAR(50) DEFAULT 'service_call',
    title VARCHAR(255),
    description TEXT,
    location TEXT,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_pattern VARCHAR(100),
    parent_schedule_id INTEGER REFERENCES service_schedules(id),
    status VARCHAR(50) DEFAULT 'scheduled',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT service_schedules_status_check CHECK (status IN ('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled')),
    CONSTRAINT service_schedules_type_check CHECK (schedule_type IN ('service_call', 'training', 'meeting', 'maintenance', 'other'))
);

-- Service History Table
CREATE TABLE IF NOT EXISTS service_history (
    id SERIAL PRIMARY KEY,
    service_call_id INTEGER NOT NULL REFERENCES service_calls(id) ON DELETE CASCADE,
    technician_id INTEGER REFERENCES technicians(id),
    action_type VARCHAR(100) NOT NULL,
    action_description TEXT,
    old_value TEXT,
    new_value TEXT,
    notes TEXT,
    location_lat DECIMAL(10,8),
    location_lng DECIMAL(11,8),
    attachments TEXT[],

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Service Parts Table
CREATE TABLE IF NOT EXISTS service_parts (
    id SERIAL PRIMARY KEY,
    service_call_id INTEGER NOT NULL REFERENCES service_calls(id) ON DELETE CASCADE,
    part_number VARCHAR(100) NOT NULL,
    part_name VARCHAR(255) NOT NULL,
    quantity_used DECIMAL(10,2) NOT NULL,
    unit_cost DECIMAL(10,2),
    total_cost DECIMAL(15,2),
    serial_number VARCHAR(100),
    warranty_info TEXT,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Service Templates Table
CREATE TABLE IF NOT EXISTS service_templates (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    service_type VARCHAR(100) NOT NULL,
    estimated_duration INTERVAL,
    required_skills TEXT[],
    standard_parts TEXT[],
    checklist_items TEXT[],
    instructions TEXT,
    is_active BOOLEAN DEFAULT TRUE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Technician Time Tracking Table
CREATE TABLE IF NOT EXISTS technician_time_tracking (
    id SERIAL PRIMARY KEY,
    technician_id INTEGER NOT NULL REFERENCES technicians(id),
    service_call_id INTEGER REFERENCES service_calls(id),
    date_worked DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    break_duration INTERVAL DEFAULT '00:00:00',
    total_hours DECIMAL(8,2),
    activity_type VARCHAR(100) DEFAULT 'service',
    description TEXT,
    billable BOOLEAN DEFAULT TRUE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    UNIQUE(technician_id, date_worked, start_time)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_service_calls_uuid ON service_calls(uuid);
CREATE INDEX IF NOT EXISTS idx_service_calls_customer ON service_calls(customer_id);
CREATE INDEX IF NOT EXISTS idx_service_calls_status ON service_calls(status);
CREATE INDEX IF NOT EXISTS idx_service_calls_technician ON service_calls(assigned_technician_id);
CREATE INDEX IF NOT EXISTS idx_service_calls_scheduled ON service_calls(scheduled_date);
CREATE INDEX IF NOT EXISTS idx_service_calls_priority ON service_calls(priority);

CREATE INDEX IF NOT EXISTS idx_technicians_user ON technicians(user_id);
CREATE INDEX IF NOT EXISTS idx_technicians_active ON technicians(is_active);

CREATE INDEX IF NOT EXISTS idx_technician_skills_technician ON technician_skills(technician_id);

CREATE INDEX IF NOT EXISTS idx_service_schedules_technician ON service_schedules(technician_id);
CREATE INDEX IF NOT EXISTS idx_service_schedules_date ON service_schedules(schedule_date);
CREATE INDEX IF NOT EXISTS idx_service_schedules_status ON service_schedules(status);

CREATE INDEX IF NOT EXISTS idx_service_history_call ON service_history(service_call_id);

CREATE INDEX IF NOT EXISTS idx_service_parts_call ON service_parts(service_call_id);

CREATE INDEX IF NOT EXISTS idx_service_templates_type ON service_templates(service_type);

CREATE INDEX IF NOT EXISTS idx_technician_time_technician ON technician_time_tracking(technician_id);
CREATE INDEX IF NOT EXISTS idx_technician_time_date ON technician_time_tracking(date_worked);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION update_service_calls_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_service_calls_updated_at BEFORE UPDATE ON service_calls
    FOR EACH ROW EXECUTE FUNCTION update_service_calls_updated_at();

CREATE OR REPLACE FUNCTION update_technicians_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_technicians_updated_at BEFORE UPDATE ON technicians
    FOR EACH ROW EXECUTE FUNCTION update_technicians_updated_at();

CREATE OR REPLACE FUNCTION update_technician_skills_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_technician_skills_updated_at BEFORE UPDATE ON technician_skills
    FOR EACH ROW EXECUTE FUNCTION update_technician_skills_updated_at();

CREATE OR REPLACE FUNCTION update_service_schedules_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_service_schedules_updated_at BEFORE UPDATE ON service_schedules
    FOR EACH ROW EXECUTE FUNCTION update_service_schedules_updated_at();

CREATE OR REPLACE FUNCTION update_service_parts_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_service_parts_updated_at BEFORE UPDATE ON service_parts
    FOR EACH ROW EXECUTE FUNCTION update_service_parts_updated_at();

CREATE OR REPLACE FUNCTION update_service_templates_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_service_templates_updated_at BEFORE UPDATE ON service_templates
    FOR EACH ROW EXECUTE FUNCTION update_service_templates_updated_at();

CREATE OR REPLACE FUNCTION update_technician_time_tracking_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_technician_time_tracking_updated_at BEFORE UPDATE ON technician_time_tracking
    FOR EACH ROW EXECUTE FUNCTION update_technician_time_tracking_updated_at();

-- Comments
COMMENT ON TABLE service_calls IS 'Service calls for field service management';
COMMENT ON TABLE technicians IS 'Technician profiles and information';
COMMENT ON TABLE technician_skills IS 'Skills and certifications for technicians';
COMMENT ON TABLE service_schedules IS 'Scheduling for technicians and service calls';
COMMENT ON TABLE service_history IS 'History and audit trail for service calls';
COMMENT ON TABLE service_parts IS 'Parts used in service calls';
COMMENT ON TABLE service_templates IS 'Templates for common service types';
COMMENT ON TABLE technician_time_tracking IS 'Time tracking for technicians';
