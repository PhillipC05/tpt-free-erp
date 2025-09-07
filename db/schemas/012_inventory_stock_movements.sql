-- TPT Open ERP - Inventory Stock Movements
-- Migration: 012
-- Description: Track all inventory stock changes and movements

CREATE TABLE IF NOT EXISTS inventory_stock_movements (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    product_id INTEGER NOT NULL REFERENCES inventory_products(id) ON DELETE CASCADE,

    -- Movement details
    transaction_type VARCHAR(50) NOT NULL, -- purchase, sale, adjustment, transfer, etc.
    movement_type VARCHAR(20) NOT NULL, -- in, out, adjustment
    quantity_change INTEGER NOT NULL,

    -- Stock levels
    old_stock INTEGER NOT NULL,
    new_stock INTEGER NOT NULL,

    -- Reference information
    reference_type VARCHAR(50), -- order, invoice, adjustment, etc.
    reference_id INTEGER,
    reference_number VARCHAR(100),

    -- Location information
    warehouse_id INTEGER,
    location_id INTEGER,
    from_location_id INTEGER,
    to_location_id INTEGER,

    -- Cost information
    unit_cost DECIMAL(15,2),
    total_cost DECIMAL(15,2),

    -- Reason and notes
    reason VARCHAR(100),
    notes TEXT,

    -- Batch and serial tracking
    batch_number VARCHAR(100),
    serial_number VARCHAR(100),
    expiry_date DATE,

    -- Quality control
    quality_status VARCHAR(20) DEFAULT 'approved', -- approved, rejected, quarantined
    quality_notes TEXT,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    approved_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT inventory_stock_movements_transaction_type CHECK (
        transaction_type IN ('purchase', 'sale', 'adjustment', 'transfer', 'return', 'damage', 'theft', 'count', 'production', 'consumption')
    ),
    CONSTRAINT inventory_stock_movements_movement_type CHECK (
        movement_type IN ('in', 'out', 'adjustment')
    ),
    CONSTRAINT inventory_stock_movements_quality_status CHECK (
        quality_status IN ('approved', 'rejected', 'quarantined', 'pending')
    ),
    CONSTRAINT inventory_stock_movements_quantity_not_zero CHECK (quantity_change != 0)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_product ON inventory_stock_movements(product_id);
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_type ON inventory_stock_movements(transaction_type);
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_movement ON inventory_stock_movements(movement_type);
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_date ON inventory_stock_movements(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_reference ON inventory_stock_movements(reference_type, reference_id);
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_warehouse ON inventory_stock_movements(warehouse_id);
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_batch ON inventory_stock_movements(batch_number);
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_serial ON inventory_stock_movements(serial_number);
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_expiry ON inventory_stock_movements(expiry_date);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_product_date ON inventory_stock_movements(product_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_product_type ON inventory_stock_movements(product_id, transaction_type);
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_warehouse_date ON inventory_stock_movements(warehouse_id, created_at DESC);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_negative ON inventory_stock_movements(product_id, quantity_change)
    WHERE quantity_change < 0;
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_expiring ON inventory_stock_movements(product_id, expiry_date)
    WHERE expiry_date IS NOT NULL AND expiry_date > CURRENT_DATE;
CREATE INDEX IF NOT EXISTS idx_inventory_stock_movements_quality_issues ON inventory_stock_movements(product_id, quality_status)
    WHERE quality_status IN ('rejected', 'quarantined');

-- Triggers for updated_at
CREATE TRIGGER update_inventory_stock_movements_updated_at BEFORE UPDATE ON inventory_stock_movements
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to get product stock history
CREATE OR REPLACE FUNCTION get_product_stock_history(
    p_product_id INTEGER,
    p_start_date TIMESTAMP DEFAULT NULL,
    p_end_date TIMESTAMP DEFAULT NULL,
    p_limit INTEGER DEFAULT 100
)
RETURNS TABLE (
    movement_id INTEGER,
    transaction_type VARCHAR(50),
    movement_type VARCHAR(20),
    quantity_change INTEGER,
    old_stock INTEGER,
    new_stock INTEGER,
    reference_type VARCHAR(50),
    reference_number VARCHAR(100),
    created_at TIMESTAMP,
    created_by_name VARCHAR(200)
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        ism.id,
        ism.transaction_type,
        ism.movement_type,
        ism.quantity_change,
        ism.old_stock,
        ism.new_stock,
        ism.reference_type,
        ism.reference_number,
        ism.created_at,
        CONCAT(u.first_name, ' ', u.last_name)
    FROM inventory_stock_movements ism
    LEFT JOIN users u ON ism.created_by = u.id
    WHERE ism.product_id = p_product_id
      AND (p_start_date IS NULL OR ism.created_at >= p_start_date)
      AND (p_end_date IS NULL OR ism.created_at <= p_end_date)
    ORDER BY ism.created_at DESC
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql;

-- Function to get expiring products
CREATE OR REPLACE FUNCTION get_expiring_products(p_days_ahead INTEGER DEFAULT 30)
RETURNS TABLE (
    product_id INTEGER,
    product_name VARCHAR(255),
    batch_number VARCHAR(100),
    expiry_date DATE,
    current_stock INTEGER,
    days_until_expiry INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        ip.id,
        ip.name,
        ism.batch_number,
        ism.expiry_date,
        ip.current_stock,
        (ism.expiry_date - CURRENT_DATE)::INTEGER
    FROM inventory_stock_movements ism
    JOIN inventory_products ip ON ism.product_id = ip.id
    WHERE ism.expiry_date IS NOT NULL
      AND ism.expiry_date <= CURRENT_DATE + INTERVAL '1 day' * p_days_ahead
      AND ism.expiry_date >= CURRENT_DATE
      AND ip.is_active = true
    GROUP BY ip.id, ip.name, ism.batch_number, ism.expiry_date, ip.current_stock
    ORDER BY ism.expiry_date ASC;
END;
$$ LANGUAGE plpgsql;

-- Function to calculate inventory turnover
CREATE OR REPLACE FUNCTION calculate_inventory_turnover(
    p_product_id INTEGER,
    p_period_start DATE,
    p_period_end DATE
)
RETURNS DECIMAL(10,2) AS $$
DECLARE
    avg_inventory DECIMAL(15,2);
    cost_of_goods_sold DECIMAL(15,2);
BEGIN
    -- Calculate average inventory
    SELECT AVG(current_stock * cost_price) INTO avg_inventory
    FROM inventory_products
    WHERE id = p_product_id;

    -- Calculate cost of goods sold (simplified - would need sales data)
    SELECT COALESCE(SUM(quantity_change * unit_cost), 0) INTO cost_of_goods_sold
    FROM inventory_stock_movements
    WHERE product_id = p_product_id
      AND transaction_type = 'sale'
      AND created_at >= p_period_start
      AND created_at <= p_period_end;

    -- Return turnover ratio
    IF avg_inventory > 0 THEN
        RETURN (cost_of_goods_sold / avg_inventory)::DECIMAL(10,2);
    ELSE
        RETURN 0;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE inventory_stock_movements IS 'Detailed tracking of all inventory stock changes';
COMMENT ON COLUMN inventory_stock_movements.transaction_type IS 'Type of transaction causing the movement';
COMMENT ON COLUMN inventory_stock_movements.movement_type IS 'Direction of stock movement (in/out/adjustment)';
COMMENT ON COLUMN inventory_stock_movements.quantity_change IS 'Change in stock quantity (positive for in, negative for out)';
COMMENT ON COLUMN inventory_stock_movements.reference_type IS 'Type of document referencing this movement';
COMMENT ON COLUMN inventory_stock_movements.batch_number IS 'Batch or lot number for traceability';
COMMENT ON COLUMN inventory_stock_movements.serial_number IS 'Serial number for individual item tracking';
