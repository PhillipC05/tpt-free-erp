-- TPT Open ERP - Sales Order Items
-- Migration: 015
-- Description: Line items for sales orders

CREATE TABLE IF NOT EXISTS sales_order_items (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    order_id INTEGER NOT NULL REFERENCES sales_orders(id) ON DELETE CASCADE,

    -- Product information
    product_id INTEGER REFERENCES inventory_products(id),
    product_sku VARCHAR(100),
    product_name VARCHAR(255) NOT NULL,

    -- Quantity and pricing
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL,

    -- Tax information
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,

    -- Product details
    description TEXT,
    product_options JSONB DEFAULT '{}', -- color, size, etc.
    custom_fields JSONB DEFAULT '{}',

    -- Fulfillment information
    quantity_ordered INTEGER NOT NULL,
    quantity_shipped INTEGER DEFAULT 0,
    quantity_backordered INTEGER DEFAULT 0,
    quantity_cancelled INTEGER DEFAULT 0,

    -- Warehouse and location
    warehouse_id INTEGER,
    location_id INTEGER,

    -- Batch and serial tracking
    batch_number VARCHAR(100),
    serial_numbers TEXT[], -- Array of serial numbers
    expiry_date DATE,

    -- Status and tracking
    item_status VARCHAR(20) DEFAULT 'pending', -- pending, confirmed, shipped, delivered, cancelled
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,

    -- Notes
    customer_notes TEXT,
    internal_notes TEXT,

    -- Line number for ordering
    line_number INTEGER NOT NULL,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT sales_order_items_quantity_positive CHECK (quantity > 0),
    CONSTRAINT sales_order_items_unit_price_positive CHECK (unit_price >= 0),
    CONSTRAINT sales_order_items_discount_percent CHECK (discount_percent >= 0 AND discount_percent <= 100),
    CONSTRAINT sales_order_items_tax_rate CHECK (tax_rate >= 0 AND tax_rate <= 100),
    CONSTRAINT sales_order_items_line_total_positive CHECK (line_total >= 0),
    CONSTRAINT sales_order_items_status CHECK (item_status IN ('pending', 'confirmed', 'shipped', 'delivered', 'cancelled', 'returned')),
    CONSTRAINT sales_order_items_quantities CHECK (
        quantity_ordered >= 0 AND quantity_shipped >= 0 AND
        quantity_backordered >= 0 AND quantity_cancelled >= 0
    ),
    CONSTRAINT sales_order_items_quantity_breakdown CHECK (
        quantity = quantity_shipped + quantity_backordered + quantity_cancelled
    )
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_sales_order_items_order ON sales_order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_sales_order_items_product ON sales_order_items(product_id);
CREATE INDEX IF NOT EXISTS idx_sales_order_items_sku ON sales_order_items(product_sku);
CREATE INDEX IF NOT EXISTS idx_sales_order_items_status ON sales_order_items(item_status);
CREATE INDEX IF NOT EXISTS idx_sales_order_items_line_number ON sales_order_items(order_id, line_number);
CREATE INDEX IF NOT EXISTS idx_sales_order_items_warehouse ON sales_order_items(warehouse_id);
CREATE INDEX IF NOT EXISTS idx_sales_order_items_batch ON sales_order_items(batch_number);
CREATE INDEX IF NOT EXISTS idx_sales_order_items_expiry ON sales_order_items(expiry_date);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_sales_order_items_order_product ON sales_order_items(order_id, product_id);
CREATE INDEX IF NOT EXISTS idx_sales_order_items_product_status ON sales_order_items(product_id, item_status);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_sales_order_items_pending ON sales_order_items(order_id, product_id)
    WHERE item_status = 'pending';
CREATE INDEX IF NOT EXISTS idx_sales_order_items_backordered ON sales_order_items(order_id, quantity_backordered)
    WHERE quantity_backordered > 0;
CREATE INDEX IF NOT EXISTS idx_sales_order_items_expiring ON sales_order_items(order_id, expiry_date)
    WHERE expiry_date IS NOT NULL AND expiry_date > CURRENT_DATE;

-- Triggers for updated_at
CREATE TRIGGER update_sales_order_items_updated_at BEFORE UPDATE ON sales_order_items
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to calculate line total
CREATE OR REPLACE FUNCTION calculate_line_total(p_item_id INTEGER)
RETURNS DECIMAL(15,2) AS $$
DECLARE
    item_quantity INTEGER;
    item_unit_price DECIMAL(15,2);
    item_discount_percent DECIMAL(5,2);
    item_discount_amount DECIMAL(15,2);
    item_tax_rate DECIMAL(5,2);
    line_subtotal DECIMAL(15,2);
    calculated_discount DECIMAL(15,2);
    calculated_tax DECIMAL(15,2);
    final_total DECIMAL(15,2);
BEGIN
    -- Get item details
    SELECT quantity, unit_price, discount_percent, discount_amount, tax_rate
    INTO item_quantity, item_unit_price, item_discount_percent, item_discount_amount, item_tax_rate
    FROM sales_order_items
    WHERE id = p_item_id;

    -- Calculate subtotal
    line_subtotal := item_quantity * item_unit_price;

    -- Calculate discount
    IF item_discount_percent > 0 THEN
        calculated_discount := line_subtotal * (item_discount_percent / 100);
    ELSE
        calculated_discount := item_discount_amount;
    END IF;

    -- Apply discount
    line_subtotal := line_subtotal - calculated_discount;

    -- Calculate tax
    calculated_tax := line_subtotal * (item_tax_rate / 100);

    -- Calculate final total
    final_total := line_subtotal + calculated_tax;

    -- Update the item
    UPDATE sales_order_items
    SET discount_amount = calculated_discount,
        tax_amount = calculated_tax,
        line_total = final_total,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_item_id;

    RETURN final_total;
END;
$$ LANGUAGE plpgsql;

-- Function to add order item
CREATE OR REPLACE FUNCTION add_order_item(
    p_order_id INTEGER,
    p_product_id INTEGER,
    p_quantity INTEGER,
    p_unit_price DECIMAL,
    p_discount_percent DECIMAL DEFAULT 0,
    p_created_by INTEGER DEFAULT NULL
)
RETURNS INTEGER AS $$
DECLARE
    new_item_id INTEGER;
    product_record RECORD;
    line_num INTEGER;
BEGIN
    -- Get product details
    SELECT sku, name INTO product_record
    FROM inventory_products
    WHERE id = p_product_id;

    -- Get next line number
    SELECT COALESCE(MAX(line_number), 0) + 1 INTO line_num
    FROM sales_order_items
    WHERE order_id = p_order_id;

    -- Insert order item
    INSERT INTO sales_order_items (
        order_id, product_id, product_sku, product_name,
        quantity, quantity_ordered, unit_price, discount_percent,
        line_number, created_by
    ) VALUES (
        p_order_id, p_product_id, product_record.sku, product_record.name,
        p_quantity, p_quantity, p_unit_price, p_discount_percent,
        line_num, p_created_by
    ) RETURNING id INTO new_item_id;

    -- Calculate line total
    PERFORM calculate_line_total(new_item_id);

    -- Update order total
    PERFORM calculate_order_total(p_order_id);

    RETURN new_item_id;
END;
$$ LANGUAGE plpgsql;

-- Function to update item quantity shipped
CREATE OR REPLACE FUNCTION update_item_shipped_quantity(
    p_item_id INTEGER,
    p_shipped_quantity INTEGER,
    p_updated_by INTEGER DEFAULT NULL
)
RETURNS VOID AS $$
DECLARE
    current_shipped INTEGER;
    total_quantity INTEGER;
BEGIN
    -- Get current shipped quantity
    SELECT quantity_shipped, quantity_ordered INTO current_shipped, total_quantity
    FROM sales_order_items
    WHERE id = p_item_id;

    -- Validate shipped quantity
    IF current_shipped + p_shipped_quantity > total_quantity THEN
        RAISE EXCEPTION 'Cannot ship more than ordered quantity';
    END IF;

    -- Update shipped quantity
    UPDATE sales_order_items
    SET quantity_shipped = quantity_shipped + p_shipped_quantity,
        item_status = CASE
            WHEN quantity_shipped + p_shipped_quantity >= total_quantity THEN 'shipped'
            ELSE 'confirmed'
        END,
        shipped_at = CASE
            WHEN shipped_at IS NULL THEN CURRENT_TIMESTAMP
            ELSE shipped_at
        END,
        updated_by = p_updated_by,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_item_id;
END;
$$ LANGUAGE plpgsql;

-- Function to get order items summary
CREATE OR REPLACE FUNCTION get_order_items_summary(p_order_id INTEGER)
RETURNS TABLE (
    item_count INTEGER,
    total_quantity INTEGER,
    shipped_quantity INTEGER,
    backordered_quantity INTEGER,
    total_amount DECIMAL(15,2)
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        COUNT(id)::INTEGER,
        SUM(quantity_ordered)::INTEGER,
        SUM(quantity_shipped)::INTEGER,
        SUM(quantity_backordered)::INTEGER,
        SUM(line_total)::DECIMAL(15,2)
    FROM sales_order_items
    WHERE order_id = p_order_id;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE sales_order_items IS 'Line items for sales orders with detailed product and fulfillment information';
COMMENT ON COLUMN sales_order_items.quantity_ordered IS 'Original quantity ordered by customer';
COMMENT ON COLUMN sales_order_items.quantity_shipped IS 'Quantity that has been shipped';
COMMENT ON COLUMN sales_order_items.quantity_backordered IS 'Quantity on backorder';
COMMENT ON COLUMN sales_order_items.line_total IS 'Total amount for this line item including tax and discount';
COMMENT ON COLUMN sales_order_items.product_options IS 'JSON object for product variants (color, size, etc.)';
COMMENT ON COLUMN sales_order_items.serial_numbers IS 'Array of serial numbers for tracked items';
COMMENT ON COLUMN sales_order_items.custom_fields IS 'Additional custom fields for the line item';
