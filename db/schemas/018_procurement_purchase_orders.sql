-- TPT Open ERP - Procurement Purchase Orders
-- Migration: 018
-- Description: Purchase order management and processing

CREATE TABLE IF NOT EXISTS procurement_purchase_orders (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    po_number VARCHAR(50) NOT NULL UNIQUE,
    po_date DATE NOT NULL,

    -- Vendor information
    vendor_id INTEGER NOT NULL REFERENCES procurement_vendors(id),
    vendor_number VARCHAR(20) NOT NULL,
    vendor_name VARCHAR(255) NOT NULL,

    -- Order details
    order_type VARCHAR(20) DEFAULT 'standard', -- standard, blanket, planned, subcontract
    order_status VARCHAR(20) DEFAULT 'draft', -- draft, sent, confirmed, partial, received, completed, cancelled
    priority VARCHAR(10) DEFAULT 'normal', -- low, normal, high, urgent

    -- Financial information
    currency_code VARCHAR(3) DEFAULT 'USD',
    subtotal DECIMAL(15,2) DEFAULT 0.00,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    shipping_amount DECIMAL(15,2) DEFAULT 0.00,
    total_amount DECIMAL(15,2) DEFAULT 0.00,

    -- Payment and terms
    payment_terms VARCHAR(50) DEFAULT 'net_30',
    payment_status VARCHAR(20) DEFAULT 'pending', -- pending, partial, paid, overdue

    -- Shipping and delivery
    shipping_method VARCHAR(50),
    shipping_carrier VARCHAR(100),
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    delivery_address JSONB DEFAULT '{}',

    -- Procurement information
    buyer_id INTEGER REFERENCES users(id),
    department_id INTEGER,
    project_id INTEGER,
    cost_center_id INTEGER,

    -- Approval workflow
    requires_approval BOOLEAN DEFAULT true,
    approval_status VARCHAR(20) DEFAULT 'pending', -- pending, approved, rejected
    approved_by INTEGER REFERENCES users(id),
    approved_at TIMESTAMP NULL,
    approval_notes TEXT,

    -- Order processing
    sent_at TIMESTAMP NULL,
    confirmed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,

    -- Quality and compliance
    quality_requirements TEXT,
    compliance_requirements TEXT,
    special_instructions TEXT,

    -- Notes and references
    internal_notes TEXT,
    vendor_notes TEXT,
    reference_number VARCHAR(100),
    contract_reference VARCHAR(100),

    -- Integration and tracking
    external_reference VARCHAR(100),
    integration_data JSONB DEFAULT '{}',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT procurement_purchase_orders_order_type CHECK (order_type IN ('standard', 'blanket', 'planned', 'subcontract', 'release')),
    CONSTRAINT procurement_purchase_orders_status CHECK (order_status IN ('draft', 'sent', 'confirmed', 'partial', 'received', 'completed', 'cancelled')),
    CONSTRAINT procurement_purchase_orders_priority CHECK (priority IN ('low', 'normal', 'high', 'urgent')),
    CONSTRAINT procurement_purchase_orders_payment_status CHECK (payment_status IN ('pending', 'partial', 'paid', 'overdue', 'cancelled')),
    CONSTRAINT procurement_purchase_orders_approval_status CHECK (approval_status IN ('pending', 'approved', 'rejected')),
    CONSTRAINT procurement_purchase_orders_positive_amounts CHECK (
        subtotal >= 0 AND tax_amount >= 0 AND discount_amount >= 0 AND
        shipping_amount >= 0 AND total_amount >= 0
    ),
    CONSTRAINT procurement_purchase_orders_delivery_dates CHECK (
        actual_delivery_date IS NULL OR expected_delivery_date IS NULL OR
        actual_delivery_date >= expected_delivery_date - INTERVAL '30 days'
    )
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_number ON procurement_purchase_orders(po_number);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_vendor ON procurement_purchase_orders(vendor_id);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_date ON procurement_purchase_orders(po_date DESC);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_status ON procurement_purchase_orders(order_status);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_type ON procurement_purchase_orders(order_type);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_priority ON procurement_purchase_orders(priority);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_payment_status ON procurement_purchase_orders(payment_status);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_buyer ON procurement_purchase_orders(buyer_id);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_department ON procurement_purchase_orders(department_id);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_project ON procurement_purchase_orders(project_id);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_approval ON procurement_purchase_orders(approval_status);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_delivery ON procurement_purchase_orders(expected_delivery_date);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_vendor_date ON procurement_purchase_orders(vendor_id, po_date DESC);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_status_date ON procurement_purchase_orders(order_status, po_date DESC);
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_buyer_status ON procurement_purchase_orders(buyer_id, order_status);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_pending_approval ON procurement_purchase_orders(id, po_number)
    WHERE approval_status = 'pending' AND requires_approval = true;
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_overdue_delivery ON procurement_purchase_orders(id, expected_delivery_date)
    WHERE order_status IN ('confirmed', 'partial') AND expected_delivery_date < CURRENT_DATE;
CREATE INDEX IF NOT EXISTS idx_procurement_purchase_orders_unpaid ON procurement_purchase_orders(id, total_amount)
    WHERE payment_status IN ('pending', 'partial');

-- Triggers for updated_at
CREATE TRIGGER update_procurement_purchase_orders_updated_at BEFORE UPDATE ON procurement_purchase_orders
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to generate purchase order number
CREATE OR REPLACE FUNCTION generate_purchase_order_number()
RETURNS VARCHAR(50) AS $$
DECLARE
    current_year INTEGER;
    sequence_number INTEGER;
    po_num VARCHAR(50);
BEGIN
    current_year := EXTRACT(YEAR FROM CURRENT_DATE);

    -- Get next sequence number for the year
    SELECT COALESCE(MAX(CAST(SUBSTRING(po_number FROM '[0-9]+$') AS INTEGER)), 0) + 1
    INTO sequence_number
    FROM procurement_purchase_orders
    WHERE po_number LIKE 'PO-' || current_year || '-%';

    po_num := 'PO-' || current_year || '-' || LPAD(sequence_number::TEXT, 6, '0');

    RETURN po_num;
END;
$$ LANGUAGE plpgsql;

-- Function to calculate purchase order total
CREATE OR REPLACE FUNCTION calculate_purchase_order_total(p_po_id INTEGER)
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
    FROM procurement_purchase_order_items
    WHERE po_id = p_po_id;

    -- Calculate tax from order items
    SELECT COALESCE(SUM(tax_amount), 0)
    INTO order_tax
    FROM procurement_purchase_order_items
    WHERE po_id = p_po_id;

    -- Get discount and shipping from order
    SELECT discount_amount, shipping_amount
    INTO order_discount, order_shipping
    FROM procurement_purchase_orders
    WHERE id = p_po_id;

    -- Calculate total
    order_total := order_subtotal + order_tax - order_discount + order_shipping;

    -- Update order totals
    UPDATE procurement_purchase_orders
    SET subtotal = order_subtotal,
        tax_amount = order_tax,
        total_amount = order_total,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_po_id;

    RETURN order_total;
END;
$$ LANGUAGE plpgsql;

-- Function to update purchase order status based on items
CREATE OR REPLACE FUNCTION update_purchase_order_status(p_po_id INTEGER)
RETURNS VOID AS $$
DECLARE
    total_items INTEGER;
    received_items INTEGER;
    cancelled_items INTEGER;
    new_status VARCHAR(20);
BEGIN
    -- Get item counts
    SELECT
        COUNT(*),
        COUNT(CASE WHEN quantity_received > 0 THEN 1 END),
        COUNT(CASE WHEN item_status = 'cancelled' THEN 1 END)
    INTO total_items, received_items, cancelled_items
    FROM procurement_purchase_order_items
    WHERE po_id = p_po_id;

    -- Determine new status
    IF cancelled_items = total_items THEN
        new_status := 'cancelled';
    ELSIF received_items = 0 THEN
        new_status := 'confirmed';
    ELSIF received_items = total_items THEN
        new_status := 'completed';
    ELSIF received_items > 0 THEN
        new_status := 'partial';
    ELSE
        new_status := 'confirmed';
    END IF;

    -- Update order status
    UPDATE procurement_purchase_orders
    SET order_status = new_status,
        completed_at = CASE WHEN new_status = 'completed' THEN CURRENT_TIMESTAMP ELSE completed_at END,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_po_id;
END;
$$ LANGUAGE plpgsql;

-- Function to get purchase order summary
CREATE OR REPLACE FUNCTION get_purchase_order_summary(p_po_id INTEGER)
RETURNS TABLE (
    po_number VARCHAR(50),
    vendor_name VARCHAR(255),
    po_date DATE,
    total_amount DECIMAL(15,2),
    order_status VARCHAR(20),
    payment_status VARCHAR(20),
    expected_delivery DATE,
    item_count INTEGER,
    received_items INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        po.po_number,
        po.vendor_name,
        po.po_date,
        po.total_amount,
        po.order_status,
        po.payment_status,
        po.expected_delivery_date,
        COUNT(poi.id)::INTEGER,
        COUNT(CASE WHEN poi.quantity_received > 0 THEN 1 END)::INTEGER
    FROM procurement_purchase_orders po
    LEFT JOIN procurement_purchase_order_items poi ON po.id = poi.po_id
    WHERE po.id = p_po_id
    GROUP BY po.id, po.po_number, po.vendor_name, po.po_date, po.total_amount,
             po.order_status, po.payment_status, po.expected_delivery_date;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE procurement_purchase_orders IS 'Purchase order header information';
COMMENT ON COLUMN procurement_purchase_orders.po_number IS 'Unique purchase order identifier';
COMMENT ON COLUMN procurement_purchase_orders.order_type IS 'Type of purchase order';
COMMENT ON COLUMN procurement_purchase_orders.order_status IS 'Current status in procurement process';
COMMENT ON COLUMN procurement_purchase_orders.subtotal IS 'Sum of all line item amounts before tax/discount';
COMMENT ON COLUMN procurement_purchase_orders.total_amount IS 'Final order amount including tax, discount, and shipping';
COMMENT ON COLUMN procurement_purchase_orders.expected_delivery_date IS 'Expected delivery date from vendor';
COMMENT ON COLUMN procurement_purchase_orders.actual_delivery_date IS 'Actual delivery date when received';
