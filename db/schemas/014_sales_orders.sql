-- TPT Open ERP - Sales Orders
-- Migration: 014
-- Description: Sales order management and processing

CREATE TABLE IF NOT EXISTS sales_orders (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    order_number VARCHAR(50) NOT NULL UNIQUE,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,

    -- Customer information
    customer_id INTEGER NOT NULL REFERENCES sales_customers(id),
    customer_number VARCHAR(50) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,

    -- Order details
    order_type VARCHAR(20) DEFAULT 'standard', -- standard, quote, backorder, return
    order_status VARCHAR(20) DEFAULT 'draft', -- draft, confirmed, processing, shipped, delivered, cancelled
    priority VARCHAR(10) DEFAULT 'normal', -- low, normal, high, urgent

    -- Financial information
    currency_code VARCHAR(3) DEFAULT 'USD',
    subtotal DECIMAL(15,2) DEFAULT 0.00,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    shipping_amount DECIMAL(15,2) DEFAULT 0.00,
    total_amount DECIMAL(15,2) DEFAULT 0.00,

    -- Payment information
    payment_terms VARCHAR(50) DEFAULT 'net_30',
    payment_method VARCHAR(50),
    payment_status VARCHAR(20) DEFAULT 'pending', -- pending, partial, paid, overdue, refunded

    -- Shipping information
    shipping_method VARCHAR(50),
    shipping_carrier VARCHAR(100),
    tracking_number VARCHAR(100),
    shipping_address JSONB DEFAULT '{}',
    billing_address JSONB DEFAULT '{}',

    -- Sales information
    sales_rep_id INTEGER REFERENCES users(id),
    sales_channel VARCHAR(50) DEFAULT 'direct', -- direct, website, marketplace, etc.
    source_campaign VARCHAR(100),

    -- Order processing
    approved_by INTEGER REFERENCES users(id),
    approved_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,

    -- Notes and special instructions
    customer_notes TEXT,
    internal_notes TEXT,
    special_instructions TEXT,

    -- Integration and references
    external_reference VARCHAR(100),
    marketplace_order_id VARCHAR(100),
    integration_data JSONB DEFAULT '{}',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT sales_orders_order_type CHECK (order_type IN ('standard', 'quote', 'backorder', 'return', 'replacement')),
    CONSTRAINT sales_orders_status CHECK (order_status IN ('draft', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded')),
    CONSTRAINT sales_orders_priority CHECK (priority IN ('low', 'normal', 'high', 'urgent')),
    CONSTRAINT sales_orders_payment_status CHECK (payment_status IN ('pending', 'partial', 'paid', 'overdue', 'refunded')),
    CONSTRAINT sales_orders_positive_amounts CHECK (
        subtotal >= 0 AND tax_amount >= 0 AND discount_amount >= 0 AND
        shipping_amount >= 0 AND total_amount >= 0
    ),
    CONSTRAINT sales_orders_delivery_after_order CHECK (expected_delivery_date >= order_date)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_sales_orders_number ON sales_orders(order_number);
CREATE INDEX IF NOT EXISTS idx_sales_orders_customer ON sales_orders(customer_id);
CREATE INDEX IF NOT EXISTS idx_sales_orders_date ON sales_orders(order_date DESC);
CREATE INDEX IF NOT EXISTS idx_sales_orders_status ON sales_orders(order_status);
CREATE INDEX IF NOT EXISTS idx_sales_orders_type ON sales_orders(order_type);
CREATE INDEX IF NOT EXISTS idx_sales_orders_priority ON sales_orders(priority);
CREATE INDEX IF NOT EXISTS idx_sales_orders_payment_status ON sales_orders(payment_status);
CREATE INDEX IF NOT EXISTS idx_sales_orders_sales_rep ON sales_orders(sales_rep_id);
CREATE INDEX IF NOT EXISTS idx_sales_orders_channel ON sales_orders(sales_channel);
CREATE INDEX IF NOT EXISTS idx_sales_orders_external_ref ON sales_orders(external_reference);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_sales_orders_customer_date ON sales_orders(customer_id, order_date DESC);
CREATE INDEX IF NOT EXISTS idx_sales_orders_status_date ON sales_orders(order_status, order_date DESC);
CREATE INDEX IF NOT EXISTS idx_sales_orders_rep_status ON sales_orders(sales_rep_id, order_status);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_sales_orders_pending ON sales_orders(id, order_number)
    WHERE order_status IN ('draft', 'confirmed');
CREATE INDEX IF NOT EXISTS idx_sales_orders_overdue ON sales_orders(id, customer_id, total_amount)
    WHERE payment_status = 'overdue';
CREATE INDEX IF NOT EXISTS idx_sales_orders_unshipped ON sales_orders(id, expected_delivery_date)
    WHERE order_status IN ('confirmed', 'processing') AND shipped_at IS NULL;

-- Triggers for updated_at
CREATE TRIGGER update_sales_orders_updated_at BEFORE UPDATE ON sales_orders
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to generate order number
CREATE OR REPLACE FUNCTION generate_order_number()
RETURNS VARCHAR(50) AS $$
DECLARE
    current_year INTEGER;
    sequence_number INTEGER;
    order_num VARCHAR(50);
BEGIN
    current_year := EXTRACT(YEAR FROM CURRENT_DATE);

    -- Get next sequence number for the year
    SELECT COALESCE(MAX(CAST(SUBSTRING(order_number FROM '[0-9]+$') AS INTEGER)), 0) + 1
    INTO sequence_number
    FROM sales_orders
    WHERE order_number LIKE 'ORD-' || current_year || '-%';

    order_num := 'ORD-' || current_year || '-' || LPAD(sequence_number::TEXT, 6, '0');

    RETURN order_num;
END;
$$ LANGUAGE plpgsql;

-- Function to calculate order totals
CREATE OR REPLACE FUNCTION calculate_order_total(p_order_id INTEGER)
RETURNS DECIMAL(15,2) AS $$
DECLARE
    order_subtotal DECIMAL(15,2) := 0;
    order_tax DECIMAL(15,2) := 0;
    order_discount DECIMAL(15,2) := 0;
    order_shipping DECIMAL(15,2) := 0;
    order_total DECIMAL(15,2) := 0;
BEGIN
    -- Calculate subtotal from order items
    SELECT COALESCE(SUM(quantity * unit_price), 0)
    INTO order_subtotal
    FROM sales_order_items
    WHERE order_id = p_order_id;

    -- Calculate tax from order items
    SELECT COALESCE(SUM(tax_amount), 0)
    INTO order_tax
    FROM sales_order_items
    WHERE order_id = p_order_id;

    -- Get discount and shipping from order
    SELECT discount_amount, shipping_amount
    INTO order_discount, order_shipping
    FROM sales_orders
    WHERE id = p_order_id;

    -- Calculate total
    order_total := order_subtotal + order_tax - order_discount + order_shipping;

    -- Update order totals
    UPDATE sales_orders
    SET subtotal = order_subtotal,
        tax_amount = order_tax,
        total_amount = order_total,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_order_id;

    RETURN order_total;
END;
$$ LANGUAGE plpgsql;

-- Function to get order summary
CREATE OR REPLACE FUNCTION get_order_summary(p_order_id INTEGER)
RETURNS TABLE (
    order_number VARCHAR(50),
    customer_name VARCHAR(255),
    order_date DATE,
    total_amount DECIMAL(15,2),
    order_status VARCHAR(20),
    payment_status VARCHAR(20),
    item_count INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        so.order_number,
        so.customer_name,
        so.order_date,
        so.total_amount,
        so.order_status,
        so.payment_status,
        COUNT(soi.id)::INTEGER
    FROM sales_orders so
    LEFT JOIN sales_order_items soi ON so.id = soi.order_id
    WHERE so.id = p_order_id
    GROUP BY so.id, so.order_number, so.customer_name, so.order_date,
             so.total_amount, so.order_status, so.payment_status;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE sales_orders IS 'Sales order header information';
COMMENT ON COLUMN sales_orders.order_number IS 'Unique order identifier';
COMMENT ON COLUMN sales_orders.order_type IS 'Type of order (standard, quote, backorder, etc.)';
COMMENT ON COLUMN sales_orders.order_status IS 'Current status in order fulfillment process';
COMMENT ON COLUMN sales_orders.subtotal IS 'Sum of all line item amounts before tax/discount';
COMMENT ON COLUMN sales_orders.total_amount IS 'Final order amount including tax, discount, and shipping';
COMMENT ON COLUMN sales_orders.payment_terms IS 'Payment terms agreed with customer';
COMMENT ON COLUMN sales_orders.shipping_address IS 'JSON object containing shipping address details';
COMMENT ON COLUMN sales_orders.integration_data IS 'Additional data from external integrations';
