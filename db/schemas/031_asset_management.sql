-- TPT Open ERP - Asset Management Schema
-- Migration: 031
-- Description: Asset management tables for tracking assets, maintenance, and depreciation

-- Assets Table
CREATE TABLE IF NOT EXISTS assets (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    asset_tag VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    subcategory VARCHAR(100),
    manufacturer VARCHAR(255),
    model VARCHAR(255),
    serial_number VARCHAR(255),
    purchase_date DATE,
    purchase_cost DECIMAL(15,2),
    current_value DECIMAL(15,2),
    location VARCHAR(255),
    department_id INTEGER,
    assigned_to INTEGER REFERENCES users(id),
    status VARCHAR(50) DEFAULT 'active',
    condition VARCHAR(50) DEFAULT 'good',
    warranty_expiry DATE,
    insurance_policy VARCHAR(255),
    insurance_expiry DATE,
    depreciation_method VARCHAR(50) DEFAULT 'straight_line',
    useful_life_years INTEGER,
    salvage_value DECIMAL(15,2) DEFAULT 0,
    accumulated_depreciation DECIMAL(15,2) DEFAULT 0,
    disposal_date DATE,
    disposal_value DECIMAL(15,2),

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT assets_status_check CHECK (status IN ('active', 'inactive', 'maintenance', 'disposed', 'lost')),
    CONSTRAINT assets_condition_check CHECK (condition IN ('excellent', 'good', 'fair', 'poor', 'broken')),
    CONSTRAINT assets_depreciation_check CHECK (depreciation_method IN ('straight_line', 'declining_balance', 'units_of_production'))
);

-- Asset Maintenance Table
CREATE TABLE IF NOT EXISTS asset_maintenance (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    asset_id INTEGER NOT NULL REFERENCES assets(id) ON DELETE CASCADE,
    maintenance_type VARCHAR(100) NOT NULL,
    description TEXT,
    scheduled_date DATE,
    completed_date DATE,
    next_due_date DATE,
    priority VARCHAR(20) DEFAULT 'medium',
    status VARCHAR(50) DEFAULT 'scheduled',
    assigned_to INTEGER REFERENCES users(id),
    vendor_id INTEGER,
    cost DECIMAL(15,2),
    notes TEXT,
    attachments TEXT[],

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT asset_maintenance_priority_check CHECK (priority IN ('low', 'medium', 'high', 'critical')),
    CONSTRAINT asset_maintenance_status_check CHECK (status IN ('scheduled', 'in_progress', 'completed', 'cancelled', 'overdue'))
);

-- Asset Depreciation Table
CREATE TABLE IF NOT EXISTS asset_depreciation (
    id SERIAL PRIMARY KEY,
    asset_id INTEGER NOT NULL REFERENCES assets(id) ON DELETE CASCADE,
    depreciation_date DATE NOT NULL,
    depreciation_amount DECIMAL(15,2) NOT NULL,
    accumulated_depreciation DECIMAL(15,2) NOT NULL,
    book_value DECIMAL(15,2) NOT NULL,
    fiscal_year INTEGER,
    fiscal_period INTEGER,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    UNIQUE(asset_id, depreciation_date)
);

-- Asset Transfers Table
CREATE TABLE IF NOT EXISTS asset_transfers (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    asset_id INTEGER NOT NULL REFERENCES assets(id),
    from_location VARCHAR(255),
    to_location VARCHAR(255),
    from_department_id INTEGER,
    to_department_id INTEGER,
    from_user_id INTEGER REFERENCES users(id),
    to_user_id INTEGER REFERENCES users(id),
    transfer_date DATE NOT NULL,
    reason TEXT,
    approved_by INTEGER REFERENCES users(id),
    status VARCHAR(50) DEFAULT 'pending',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT asset_transfers_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'completed'))
);

-- Asset Categories Table
CREATE TABLE IF NOT EXISTS asset_categories (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_category_id INTEGER REFERENCES asset_categories(id),
    depreciation_rate DECIMAL(5,4), -- Annual depreciation rate
    useful_life_years INTEGER,
    is_active BOOLEAN DEFAULT TRUE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Asset Documents Table
CREATE TABLE IF NOT EXISTS asset_documents (
    id SERIAL PRIMARY KEY,
    asset_id INTEGER NOT NULL REFERENCES assets(id) ON DELETE CASCADE,
    document_type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INTEGER,
    mime_type VARCHAR(100),
    expiry_date DATE,

    -- Audit Fields
    uploaded_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_assets_uuid ON assets(uuid);
CREATE INDEX IF NOT EXISTS idx_assets_tag ON assets(asset_tag);
CREATE INDEX IF NOT EXISTS idx_assets_category ON assets(category);
CREATE INDEX IF NOT EXISTS idx_assets_status ON assets(status);
CREATE INDEX IF NOT EXISTS idx_assets_assigned ON assets(assigned_to);
CREATE INDEX IF NOT EXISTS idx_assets_location ON assets(location);

CREATE INDEX IF NOT EXISTS idx_asset_maintenance_asset ON asset_maintenance(asset_id);
CREATE INDEX IF NOT EXISTS idx_asset_maintenance_status ON asset_maintenance(status);
CREATE INDEX IF NOT EXISTS idx_asset_maintenance_due_date ON asset_maintenance(next_due_date);

CREATE INDEX IF NOT EXISTS idx_asset_depreciation_asset ON asset_depreciation(asset_id);
CREATE INDEX IF NOT EXISTS idx_asset_depreciation_date ON asset_depreciation(depreciation_date);

CREATE INDEX IF NOT EXISTS idx_asset_transfers_asset ON asset_transfers(asset_id);
CREATE INDEX IF NOT EXISTS idx_asset_transfers_status ON asset_transfers(status);

CREATE INDEX IF NOT EXISTS idx_asset_categories_parent ON asset_categories(parent_category_id);

CREATE INDEX IF NOT EXISTS idx_asset_documents_asset ON asset_documents(asset_id);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION update_assets_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_assets_updated_at BEFORE UPDATE ON assets
    FOR EACH ROW EXECUTE FUNCTION update_assets_updated_at();

CREATE OR REPLACE FUNCTION update_asset_maintenance_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_asset_maintenance_updated_at BEFORE UPDATE ON asset_maintenance
    FOR EACH ROW EXECUTE FUNCTION update_asset_maintenance_updated_at();

CREATE OR REPLACE FUNCTION update_asset_transfers_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_asset_transfers_updated_at BEFORE UPDATE ON asset_transfers
    FOR EACH ROW EXECUTE FUNCTION update_asset_transfers_updated_at();

CREATE OR REPLACE FUNCTION update_asset_categories_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_asset_categories_updated_at BEFORE UPDATE ON asset_categories
    FOR EACH ROW EXECUTE FUNCTION update_asset_categories_updated_at();

CREATE OR REPLACE FUNCTION update_asset_documents_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_asset_documents_updated_at BEFORE UPDATE ON asset_documents
    FOR EACH ROW EXECUTE FUNCTION update_asset_documents_updated_at();

-- Comments
COMMENT ON TABLE assets IS 'Main assets table for tracking all company assets';
COMMENT ON TABLE asset_maintenance IS 'Maintenance schedules and records for assets';
COMMENT ON TABLE asset_depreciation IS 'Depreciation calculations and history for assets';
COMMENT ON TABLE asset_transfers IS 'Asset transfer records between locations/departments/users';
COMMENT ON TABLE asset_categories IS 'Asset categories and depreciation settings';
COMMENT ON TABLE asset_documents IS 'Documents and files related to assets';
