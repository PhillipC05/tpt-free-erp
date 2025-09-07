-- TPT Open ERP - Manufacturing Bills of Materials
-- Migration: 019
-- Description: Bill of materials for product manufacturing

CREATE TABLE IF NOT EXISTS manufacturing_boms (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    bom_number VARCHAR(50) NOT NULL UNIQUE,
    bom_name VARCHAR(255) NOT NULL,

    -- Product information
    product_id INTEGER NOT NULL REFERENCES inventory_products(id),
    product_sku VARCHAR(100) NOT NULL,
    product_name VARCHAR(255) NOT NULL,

    -- BOM details
    bom_type VARCHAR(20) DEFAULT 'standard', -- standard, engineering, production, sales
    version VARCHAR(20) DEFAULT '1.0',
    revision INTEGER DEFAULT 1,
    is_active BOOLEAN DEFAULT true,
    is_default BOOLEAN DEFAULT false,

    -- Quantity and yield
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    unit_of_measure VARCHAR(20) DEFAULT 'each',
    yield_percentage DECIMAL(5,2) DEFAULT 100.00, -- expected yield

    -- Cost information
    total_cost DECIMAL(15,2) DEFAULT 0.00,
    labor_cost DECIMAL(15,2) DEFAULT 0.00,
    overhead_cost DECIMAL(15,2) DEFAULT 0.00,
    currency_code VARCHAR(3) DEFAULT 'USD',

    -- Validity period
    effective_date DATE DEFAULT CURRENT_DATE,
    expiration_date DATE NULL,

    -- Routing information
    routing_id INTEGER, -- Reference to manufacturing routing
    production_time_hours DECIMAL(8,2),

    -- Quality and compliance
    quality_requirements TEXT,
    compliance_requirements TEXT,

    -- Notes and instructions
    description TEXT,
    assembly_instructions TEXT,
    special_notes TEXT,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT manufacturing_boms_quantity_positive CHECK (quantity > 0),
    CONSTRAINT manufacturing_boms_yield_percentage CHECK (yield_percentage > 0 AND yield_percentage <= 100),
    CONSTRAINT manufacturing_boms_bom_type CHECK (bom_type IN ('standard', 'engineering', 'production', 'sales', 'template')),
    CONSTRAINT manufacturing_boms_costs_positive CHECK (
        total_cost >= 0 AND labor_cost >= 0 AND overhead_cost >= 0
    ),
    CONSTRAINT manufacturing_boms_dates CHECK (expiration_date IS NULL OR expiration_date >= effective_date)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_manufacturing_boms_number ON manufacturing_boms(bom_number);
CREATE INDEX IF NOT EXISTS idx_manufacturing_boms_product ON manufacturing_boms(product_id);
CREATE INDEX IF NOT EXISTS idx_manufacturing_boms_type ON manufacturing_boms(bom_type);
CREATE INDEX IF NOT EXISTS idx_manufacturing_boms_active ON manufacturing_boms(is_active);
CREATE INDEX IF NOT EXISTS idx_manufacturing_boms_default ON manufacturing_boms(is_default);
CREATE INDEX IF NOT EXISTS idx_manufacturing_boms_routing ON manufacturing_boms(routing_id);
CREATE INDEX IF NOT EXISTS idx_manufacturing_boms_effective ON manufacturing_boms(effective_date);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_boms_product_active ON manufacturing_boms(product_id, is_active);
CREATE INDEX IF NOT EXISTS idx_manufacturing_boms_product_default ON manufacturing_boms(product_id, is_default) WHERE is_default = true;

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_boms_active_not_expired ON manufacturing_boms(product_id, bom_name)
    WHERE is_active = true AND (expiration_date IS NULL OR expiration_date >= CURRENT_DATE);

-- Triggers for updated_at
CREATE TRIGGER update_manufacturing_boms_updated_at BEFORE UPDATE ON manufacturing_boms
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to generate BOM number
CREATE OR REPLACE FUNCTION generate_bom_number()
RETURNS VARCHAR(50) AS $$
DECLARE
    current_year INTEGER;
    sequence_number INTEGER;
    bom_num VARCHAR(50);
BEGIN
    current_year := EXTRACT(YEAR FROM CURRENT_DATE);

    -- Get next sequence number for the year
    SELECT COALESCE(MAX(CAST(SUBSTRING(bom_number FROM '[0-9]+$') AS INTEGER)), 0) + 1
    INTO sequence_number
    FROM manufacturing_boms
    WHERE bom_number LIKE 'BOM-' || current_year || '-%';

    bom_num := 'BOM-' || current_year || '-' || LPAD(sequence_number::TEXT, 6, '0');

    RETURN bom_num;
END;
$$ LANGUAGE plpgsql;

-- Function to calculate BOM total cost
CREATE OR REPLACE FUNCTION calculate_bom_total_cost(p_bom_id INTEGER)
RETURNS DECIMAL(15,2) AS $$
DECLARE
    material_cost DECIMAL(15,2) := 0;
    labor_cost DECIMAL(15,2) := 0;
    overhead_cost DECIMAL(15,2) := 0;
    total_cost DECIMAL(15,2) := 0;
BEGIN
    -- Calculate material cost from BOM components
    SELECT COALESCE(SUM((quantity * unit_cost) / NULLIF(yield_percentage, 0) * 100), 0)
    INTO material_cost
    FROM manufacturing_bom_components
    WHERE bom_id = p_bom_id;

    -- Get labor and overhead costs
    SELECT b.labor_cost, b.overhead_cost
    INTO labor_cost, overhead_cost
    FROM manufacturing_boms b
    WHERE b.id = p_bom_id;

    -- Calculate total
    total_cost := material_cost + labor_cost + overhead_cost;

    -- Update BOM
    UPDATE manufacturing_boms
    SET total_cost = total_cost,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_bom_id;

    RETURN total_cost;
END;
$$ LANGUAGE plpgsql;

-- Function to get BOM components
CREATE OR REPLACE FUNCTION get_bom_components(p_bom_id INTEGER)
RETURNS TABLE (
    component_id INTEGER,
    product_id INTEGER,
    product_sku VARCHAR(100),
    product_name VARCHAR(255),
    quantity DECIMAL(10,2),
    unit_of_measure VARCHAR(20),
    unit_cost DECIMAL(15,2),
    total_cost DECIMAL(15,2),
    is_optional BOOLEAN
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        bc.id,
        bc.component_product_id,
        ip.sku,
        ip.name,
        bc.quantity,
        bc.unit_of_measure,
        ip.cost_price,
        (bc.quantity * ip.cost_price),
        bc.is_optional
    FROM manufacturing_bom_components bc
    JOIN inventory_products ip ON bc.component_product_id = ip.id
    WHERE bc.bom_id = p_bom_id
    ORDER BY bc.line_number;
END;
$$ LANGUAGE plpgsql;

-- Function to check BOM validity
CREATE OR REPLACE FUNCTION validate_bom(p_bom_id INTEGER)
RETURNS TABLE (
    is_valid BOOLEAN,
    validation_errors TEXT[]
) AS $$
DECLARE
    error_messages TEXT[] := '{}';
    component_count INTEGER;
    has_components BOOLEAN := false;
BEGIN
    -- Check if BOM has components
    SELECT COUNT(*) INTO component_count
    FROM manufacturing_bom_components
    WHERE bom_id = p_bom_id;

    IF component_count = 0 THEN
        error_messages := error_messages || 'BOM has no components defined';
    ELSE
        has_components := true;
    END IF;

    -- Check for missing component products
    IF NOT EXISTS (
        SELECT 1 FROM manufacturing_bom_components bc
        JOIN inventory_products ip ON bc.component_product_id = ip.id
        WHERE bc.bom_id = p_bom_id AND ip.deleted_at IS NULL
    ) THEN
        error_messages := error_messages || 'Some component products are missing or deleted';
    END IF;

    -- Check for circular references (simplified check)
    IF EXISTS (
        SELECT 1 FROM manufacturing_bom_components bc
        WHERE bc.bom_id = p_bom_id
        AND bc.component_product_id IN (
            SELECT product_id FROM manufacturing_boms WHERE id = p_bom_id
        )
    ) THEN
        error_messages := error_messages || 'BOM contains circular reference';
    END IF;

    RETURN QUERY SELECT
        (array_length(error_messages, 1) IS NULL),
        error_messages;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE manufacturing_boms IS 'Bill of materials defining product structure and components';
COMMENT ON COLUMN manufacturing_boms.bom_number IS 'Unique BOM identifier';
COMMENT ON COLUMN manufacturing_boms.bom_type IS 'Type of BOM (standard, engineering, production, etc.)';
COMMENT ON COLUMN manufacturing_boms.quantity IS 'Quantity of finished product this BOM produces';
COMMENT ON COLUMN manufacturing_boms.yield_percentage IS 'Expected manufacturing yield percentage';
COMMENT ON COLUMN manufacturing_boms.total_cost IS 'Total cost of all components and operations';
COMMENT ON COLUMN manufacturing_boms.effective_date IS 'Date when this BOM version becomes effective';
COMMENT ON COLUMN manufacturing_boms.routing_id IS 'Reference to manufacturing routing/work instructions';
