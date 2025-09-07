-- TPT Open ERP - Manufacturing BOM Components
-- Migration: 020
-- Description: Components that make up each bill of materials

CREATE TABLE IF NOT EXISTS manufacturing_bom_components (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    bom_id INTEGER NOT NULL REFERENCES manufacturing_boms(id) ON DELETE CASCADE,

    -- Component product
    component_product_id INTEGER NOT NULL REFERENCES inventory_products(id),
    product_sku VARCHAR(100) NOT NULL,
    product_name VARCHAR(255) NOT NULL,

    -- Quantity requirements
    quantity DECIMAL(10,2) NOT NULL,
    unit_of_measure VARCHAR(20) DEFAULT 'each',
    scrap_percentage DECIMAL(5,2) DEFAULT 0.00, -- additional quantity for scrap/loss

    -- Cost information
    unit_cost DECIMAL(15,2) DEFAULT 0.00,
    total_cost DECIMAL(15,2) GENERATED ALWAYS AS (quantity * unit_cost) STORED,

    -- Component properties
    is_optional BOOLEAN DEFAULT false,
    substitute_allowed BOOLEAN DEFAULT false,
    lead_time_days INTEGER DEFAULT 0,

    -- Quality specifications
    quality_specification TEXT,
    tolerance_min DECIMAL(15,4),
    tolerance_max DECIMAL(15,4),

    -- Manufacturing details
    operation_sequence INTEGER DEFAULT 1,
    work_center VARCHAR(100),
    setup_time_minutes INTEGER DEFAULT 0,
    run_time_minutes INTEGER DEFAULT 0,

    -- Supply chain
    supplier_id INTEGER REFERENCES procurement_vendors(id),
    procurement_type VARCHAR(20) DEFAULT 'buy', -- buy, make, both

    -- Line number for ordering
    line_number INTEGER NOT NULL,

    -- Notes
    notes TEXT,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT manufacturing_bom_components_quantity_positive CHECK (quantity > 0),
    CONSTRAINT manufacturing_bom_components_scrap_percentage CHECK (scrap_percentage >= 0 AND scrap_percentage <= 100),
    CONSTRAINT manufacturing_bom_components_unit_cost_positive CHECK (unit_cost >= 0),
    CONSTRAINT manufacturing_bom_components_procurement_type CHECK (procurement_type IN ('buy', 'make', 'both')),
    CONSTRAINT manufacturing_bom_components_tolerances CHECK (
        tolerance_min IS NULL OR tolerance_max IS NULL OR tolerance_min <= tolerance_max
    ),
    CONSTRAINT manufacturing_bom_components_times_positive CHECK (
        setup_time_minutes >= 0 AND run_time_minutes >= 0
    )
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_manufacturing_bom_components_bom ON manufacturing_bom_components(bom_id);
CREATE INDEX IF NOT EXISTS idx_manufacturing_bom_components_product ON manufacturing_bom_components(component_product_id);
CREATE INDEX IF NOT EXISTS idx_manufacturing_bom_components_supplier ON manufacturing_bom_components(supplier_id);
CREATE INDEX IF NOT EXISTS idx_manufacturing_bom_components_operation ON manufacturing_bom_components(operation_sequence);
CREATE INDEX IF NOT EXISTS idx_manufacturing_bom_components_line_number ON manufacturing_bom_components(bom_id, line_number);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_bom_components_bom_product ON manufacturing_bom_components(bom_id, component_product_id);
CREATE INDEX IF NOT EXISTS idx_manufacturing_bom_components_product_supplier ON manufacturing_bom_components(component_product_id, supplier_id);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_bom_components_optional ON manufacturing_bom_components(bom_id, component_product_id)
    WHERE is_optional = true;
CREATE INDEX IF NOT EXISTS idx_manufacturing_bom_components_substitute ON manufacturing_bom_components(bom_id, component_product_id)
    WHERE substitute_allowed = true;

-- Triggers for updated_at
CREATE TRIGGER update_manufacturing_bom_components_updated_at BEFORE UPDATE ON manufacturing_bom_components
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to get component requirements for production
CREATE OR REPLACE FUNCTION get_component_requirements(
    p_bom_id INTEGER,
    p_production_quantity INTEGER DEFAULT 1
)
RETURNS TABLE (
    component_product_id INTEGER,
    product_sku VARCHAR(100),
    product_name VARCHAR(255),
    required_quantity DECIMAL(10,2),
    unit_of_measure VARCHAR(20),
    available_stock INTEGER,
    shortage_quantity DECIMAL(10,2),
    supplier_id INTEGER,
    lead_time_days INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        bc.component_product_id,
        ip.sku,
        ip.name,
        (bc.quantity * p_production_quantity * (1 + bc.scrap_percentage / 100)),
        bc.unit_of_measure,
        ip.current_stock,
        GREATEST(0, (bc.quantity * p_production_quantity * (1 + bc.scrap_percentage / 100)) - ip.current_stock),
        bc.supplier_id,
        bc.lead_time_days
    FROM manufacturing_bom_components bc
    JOIN inventory_products ip ON bc.component_product_id = ip.id
    WHERE bc.bom_id = p_bom_id
      AND ip.deleted_at IS NULL
    ORDER BY bc.line_number;
END;
$$ LANGUAGE plpgsql;

-- Function to check component availability
CREATE OR REPLACE FUNCTION check_component_availability(
    p_bom_id INTEGER,
    p_production_quantity INTEGER DEFAULT 1
)
RETURNS TABLE (
    all_available BOOLEAN,
    shortage_count INTEGER,
    total_shortage_quantity DECIMAL(10,2),
    critical_components TEXT[]
) AS $$
DECLARE
    shortage_components TEXT[] := '{}';
    component_record RECORD;
BEGIN
    FOR component_record IN
        SELECT * FROM get_component_requirements(p_bom_id, p_production_quantity)
        WHERE shortage_quantity > 0
    LOOP
        shortage_components := shortage_components || component_record.product_name;
    END LOOP;

    RETURN QUERY
    SELECT
        (array_length(shortage_components, 1) IS NULL),
        array_length(shortage_components, 1),
        COALESCE(SUM(shortage_quantity), 0),
        shortage_components
    FROM get_component_requirements(p_bom_id, p_production_quantity)
    WHERE shortage_quantity > 0;
END;
$$ LANGUAGE plpgsql;

-- Function to calculate component costs
CREATE OR REPLACE FUNCTION calculate_component_costs(p_bom_id INTEGER)
RETURNS DECIMAL(15,2) AS $$
DECLARE
    total_cost DECIMAL(15,2) := 0;
BEGIN
    SELECT COALESCE(SUM(quantity * unit_cost), 0)
    INTO total_cost
    FROM manufacturing_bom_components
    WHERE bom_id = p_bom_id;

    RETURN total_cost;
END;
$$ LANGUAGE plpgsql;

-- Function to find substitute components
CREATE OR REPLACE FUNCTION find_substitute_components(p_component_product_id INTEGER)
RETURNS TABLE (
    substitute_product_id INTEGER,
    product_sku VARCHAR(100),
    product_name VARCHAR(255),
    compatibility_score DECIMAL(3,1),
    unit_cost DECIMAL(15,2),
    available_stock INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        ip.id,
        ip.sku,
        ip.name,
        8.5::DECIMAL(3,1), -- Placeholder for compatibility scoring algorithm
        ip.cost_price,
        ip.current_stock
    FROM inventory_products ip
    JOIN inventory_product_categories ipc ON ip.category_id = ipc.id
    WHERE ip.category_id = (
        SELECT category_id FROM inventory_products WHERE id = p_component_product_id
    )
      AND ip.id != p_component_product_id
      AND ip.is_active = true
      AND ip.deleted_at IS NULL
    ORDER BY ip.cost_price ASC, ip.current_stock DESC
    LIMIT 5;
END;
$$ LANGUAGE plpgsql;

-- Function to update component costs from product prices
CREATE OR REPLACE FUNCTION update_component_costs_from_products()
RETURNS INTEGER AS $$
DECLARE
    updated_count INTEGER := 0;
BEGIN
    UPDATE manufacturing_bom_components
    SET unit_cost = ip.cost_price,
        updated_at = CURRENT_TIMESTAMP
    FROM inventory_products ip
    WHERE manufacturing_bom_components.component_product_id = ip.id
      AND manufacturing_bom_components.unit_cost != ip.cost_price;

    GET DIAGNOSTICS updated_count = ROW_COUNT;

    -- Update BOM total costs
    UPDATE manufacturing_boms
    SET updated_at = CURRENT_TIMESTAMP
    WHERE id IN (
        SELECT DISTINCT bom_id
        FROM manufacturing_bom_components
        WHERE updated_at = CURRENT_TIMESTAMP
    );

    RETURN updated_count;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE manufacturing_bom_components IS 'Individual components that make up a bill of materials';
COMMENT ON COLUMN manufacturing_bom_components.quantity IS 'Quantity of component required per BOM quantity';
COMMENT ON COLUMN manufacturing_bom_components.scrap_percentage IS 'Additional percentage for scrap/loss allowance';
COMMENT ON COLUMN manufacturing_bom_components.is_optional IS 'Whether this component is optional in the BOM';
COMMENT ON COLUMN manufacturing_bom_components.operation_sequence IS 'Order of operations in manufacturing process';
COMMENT ON COLUMN manufacturing_bom_components.procurement_type IS 'How this component is procured (buy/make/both)';
COMMENT ON COLUMN manufacturing_bom_components.tolerance_min IS 'Minimum acceptable tolerance for this component';
COMMENT ON COLUMN manufacturing_bom_components.tolerance_max IS 'Maximum acceptable tolerance for this component';
