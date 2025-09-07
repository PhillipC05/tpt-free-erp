-- TPT Open ERP - Quality Management Schema
-- Migration: 030
-- Description: Quality management tables for quality control, audits, and non-conformances

-- Quality Checks Table
CREATE TABLE IF NOT EXISTS quality_checks (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    check_type VARCHAR(100) NOT NULL,
    reference_type VARCHAR(50) NOT NULL, -- 'product', 'process', 'service', 'supplier'
    reference_id INTEGER NOT NULL,
    checklist_id INTEGER,
    inspector_id INTEGER REFERENCES users(id),
    status VARCHAR(50) DEFAULT 'pending',
    result VARCHAR(20) DEFAULT 'pending', -- 'pass', 'fail', 'pending'
    score DECIMAL(5,2),
    notes TEXT,
    corrective_actions TEXT,
    preventive_actions TEXT,
    due_date DATE,
    completed_at TIMESTAMP NULL,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT quality_checks_status_check CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled')),
    CONSTRAINT quality_checks_result_check CHECK (result IN ('pass', 'fail', 'pending')),
    CONSTRAINT quality_checks_reference_check CHECK (reference_type IN ('product', 'process', 'service', 'supplier'))
);

-- Quality Audits Table
CREATE TABLE IF NOT EXISTS quality_audits (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    audit_type VARCHAR(100) NOT NULL,
    scope TEXT NOT NULL,
    auditor_id INTEGER REFERENCES users(id),
    department_id INTEGER,
    status VARCHAR(50) DEFAULT 'planned',
    planned_date DATE,
    actual_date DATE,
    findings TEXT,
    recommendations TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    closure_date DATE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT quality_audits_status_check CHECK (status IN ('planned', 'in_progress', 'completed', 'cancelled'))
);

-- Non-Conformances Table
CREATE TABLE IF NOT EXISTS non_conformances (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    nc_number VARCHAR(50) UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    severity VARCHAR(20) DEFAULT 'minor',
    source VARCHAR(100) NOT NULL,
    detected_by INTEGER REFERENCES users(id),
    assigned_to INTEGER REFERENCES users(id),
    status VARCHAR(50) DEFAULT 'open',
    root_cause TEXT,
    corrective_action TEXT,
    preventive_action TEXT,
    cost_impact DECIMAL(15,2),
    due_date DATE,
    closed_at TIMESTAMP NULL,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT non_conformances_severity_check CHECK (severity IN ('minor', 'major', 'critical')),
    CONSTRAINT non_conformances_status_check CHECK (status IN ('open', 'investigating', 'action_required', 'closed'))
);

-- Quality Checklists Table
CREATE TABLE IF NOT EXISTS quality_checklists (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    checklist_type VARCHAR(100) NOT NULL,
    is_template BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Quality Checklist Items Table
CREATE TABLE IF NOT EXISTS quality_checklist_items (
    id SERIAL PRIMARY KEY,
    checklist_id INTEGER NOT NULL REFERENCES quality_checklists(id) ON DELETE CASCADE,
    item_order INTEGER NOT NULL,
    question TEXT NOT NULL,
    expected_response VARCHAR(20), -- 'yes', 'no', 'n/a'
    is_required BOOLEAN DEFAULT TRUE,
    weight DECIMAL(3,2) DEFAULT 1.0,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Quality Check Responses Table
CREATE TABLE IF NOT EXISTS quality_check_responses (
    id SERIAL PRIMARY KEY,
    quality_check_id INTEGER NOT NULL REFERENCES quality_checks(id) ON DELETE CASCADE,
    checklist_item_id INTEGER NOT NULL REFERENCES quality_checklist_items(id),
    response VARCHAR(20) NOT NULL,
    notes TEXT,
    score DECIMAL(5,2),

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_quality_checks_uuid ON quality_checks(uuid);
CREATE INDEX IF NOT EXISTS idx_quality_checks_reference ON quality_checks(reference_type, reference_id);
CREATE INDEX IF NOT EXISTS idx_quality_checks_status ON quality_checks(status);
CREATE INDEX IF NOT EXISTS idx_quality_checks_inspector ON quality_checks(inspector_id);

CREATE INDEX IF NOT EXISTS idx_quality_audits_uuid ON quality_audits(uuid);
CREATE INDEX IF NOT EXISTS idx_quality_audits_auditor ON quality_audits(auditor_id);
CREATE INDEX IF NOT EXISTS idx_quality_audits_status ON quality_audits(status);

CREATE INDEX IF NOT EXISTS idx_non_conformances_uuid ON non_conformances(uuid);
CREATE INDEX IF NOT EXISTS idx_non_conformances_status ON non_conformances(status);
CREATE INDEX IF NOT EXISTS idx_non_conformances_assigned ON non_conformances(assigned_to);
CREATE INDEX IF NOT EXISTS idx_non_conformances_severity ON non_conformances(severity);

CREATE INDEX IF NOT EXISTS idx_quality_checklists_uuid ON quality_checklists(uuid);
CREATE INDEX IF NOT EXISTS idx_quality_checklists_type ON quality_checklists(checklist_type);

CREATE INDEX IF NOT EXISTS idx_quality_checklist_items_checklist ON quality_checklist_items(checklist_id);

CREATE INDEX IF NOT EXISTS idx_quality_check_responses_check ON quality_check_responses(quality_check_id);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION update_quality_checks_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_quality_checks_updated_at BEFORE UPDATE ON quality_checks
    FOR EACH ROW EXECUTE FUNCTION update_quality_checks_updated_at();

CREATE OR REPLACE FUNCTION update_quality_audits_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_quality_audits_updated_at BEFORE UPDATE ON quality_audits
    FOR EACH ROW EXECUTE FUNCTION update_quality_audits_updated_at();

CREATE OR REPLACE FUNCTION update_non_conformances_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_non_conformances_updated_at BEFORE UPDATE ON non_conformances
    FOR EACH ROW EXECUTE FUNCTION update_non_conformances_updated_at();

CREATE OR REPLACE FUNCTION update_quality_checklists_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_quality_checklists_updated_at BEFORE UPDATE ON quality_checklists
    FOR EACH ROW EXECUTE FUNCTION update_quality_checklists_updated_at();

CREATE OR REPLACE FUNCTION update_quality_checklist_items_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_quality_checklist_items_updated_at BEFORE UPDATE ON quality_checklist_items
    FOR EACH ROW EXECUTE FUNCTION update_quality_checklist_items_updated_at();

CREATE OR REPLACE FUNCTION update_quality_check_responses_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_quality_check_responses_updated_at BEFORE UPDATE ON quality_check_responses
    FOR EACH ROW EXECUTE FUNCTION update_quality_check_responses_updated_at();

-- Comments
COMMENT ON TABLE quality_checks IS 'Quality checks performed on products, processes, services, or suppliers';
COMMENT ON TABLE quality_audits IS 'Quality audits conducted on departments or processes';
COMMENT ON TABLE non_conformances IS 'Non-conformance reports for quality issues';
COMMENT ON TABLE quality_checklists IS 'Templates and checklists for quality inspections';
COMMENT ON TABLE quality_checklist_items IS 'Individual items within quality checklists';
COMMENT ON TABLE quality_check_responses IS 'Responses to quality checklist items during inspections';
