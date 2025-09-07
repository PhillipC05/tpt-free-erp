-- TPT Open ERP - Procurement Vendors
-- Migration: 017
-- Description: Vendor and supplier management for procurement

CREATE TABLE IF NOT EXISTS procurement_vendors (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    vendor_number VARCHAR(20) NOT NULL UNIQUE,
    vendor_type VARCHAR(20) DEFAULT 'supplier', -- supplier, manufacturer, service_provider

    -- Basic Information
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(200),
    display_name VARCHAR(255),

    -- Contact Information
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    mobile VARCHAR(20),
    fax VARCHAR(20),
    website VARCHAR(255),

    -- Address Information
    billing_address JSONB DEFAULT '{}',
    shipping_address JSONB DEFAULT '{}',
    same_address BOOLEAN DEFAULT true,

    -- Business Information
    tax_id VARCHAR(50),
    registration_number VARCHAR(50),
    industry VARCHAR(100),
    company_size VARCHAR(20), -- startup, small, medium, large, enterprise

    -- Financial Information
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    payment_terms VARCHAR(50) DEFAULT 'net_30',
    currency_code VARCHAR(3) DEFAULT 'USD',
    tax_exempt BOOLEAN DEFAULT false,
    tax_exemption_certificate VARCHAR(100),

    -- Procurement Information
    primary_category VARCHAR(100),
    secondary_categories TEXT[],
    preferred_supplier BOOLEAN DEFAULT false,
    blacklisted BOOLEAN DEFAULT false,
    blacklist_reason TEXT,

    -- Performance Metrics
    on_time_delivery_rate DECIMAL(5,2) DEFAULT 0.00, -- percentage
    quality_rating DECIMAL(3,1) DEFAULT 0.0, -- 1.0 to 5.0 scale
    responsiveness_rating DECIMAL(3,1) DEFAULT 0.0,
    overall_rating DECIMAL(3,1) GENERATED ALWAYS AS (
        (on_time_delivery_rate * 0.4 + quality_rating * 0.35 + responsiveness_rating * 0.25) / 100
    ) STORED,

    -- Contract Information
    contract_start_date DATE,
    contract_end_date DATE,
    contract_value DECIMAL(15,2),
    contract_terms TEXT,

    -- Banking Information
    bank_name VARCHAR(255),
    bank_account_number VARCHAR(100),
    bank_routing_number VARCHAR(50),
    bank_swift_code VARCHAR(20),
    payment_method VARCHAR(50) DEFAULT 'check', -- check, wire, ach, card

    -- Communication Preferences
    email_notifications BOOLEAN DEFAULT true,
    order_confirmations BOOLEAN DEFAULT true,
    payment_reminders BOOLEAN DEFAULT true,

    -- Status and Lifecycle
    vendor_status VARCHAR(20) DEFAULT 'active', -- active, inactive, suspended, terminated
    approval_status VARCHAR(20) DEFAULT 'pending', -- pending, approved, rejected
    approved_by INTEGER REFERENCES users(id),
    approved_at TIMESTAMP NULL,

    -- Notes and Additional Data
    internal_notes TEXT,
    vendor_notes TEXT,
    custom_fields JSONB DEFAULT '{}',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT procurement_vendors_email_format CHECK (email IS NULL OR email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'),
    CONSTRAINT procurement_vendors_vendor_type CHECK (vendor_type IN ('supplier', 'manufacturer', 'service_provider', 'consultant')),
    CONSTRAINT procurement_vendors_status CHECK (vendor_status IN ('active', 'inactive', 'suspended', 'terminated')),
    CONSTRAINT procurement_vendors_approval_status CHECK (approval_status IN ('pending', 'approved', 'rejected')),
    CONSTRAINT procurement_vendors_performance_ratings CHECK (
        on_time_delivery_rate >= 0 AND on_time_delivery_rate <= 100 AND
        quality_rating >= 0 AND quality_rating <= 5 AND
        responsiveness_rating >= 0 AND responsiveness_rating <= 5
    ),
    CONSTRAINT procurement_vendors_credit_limit_positive CHECK (credit_limit >= 0),
    CONSTRAINT procurement_vendors_contract_dates CHECK (contract_end_date IS NULL OR contract_end_date >= contract_start_date)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_number ON procurement_vendors(vendor_number);
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_email ON procurement_vendors(email);
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_company ON procurement_vendors(company_name);
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_type ON procurement_vendors(vendor_type);
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_status ON procurement_vendors(vendor_status);
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_category ON procurement_vendors(primary_category);
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_preferred ON procurement_vendors(preferred_supplier);
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_rating ON procurement_vendors(overall_rating);
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_approval ON procurement_vendors(approval_status);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_type_status ON procurement_vendors(vendor_type, vendor_status);
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_category_rating ON procurement_vendors(primary_category, overall_rating DESC);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_active_approved ON procurement_vendors(id, company_name)
    WHERE vendor_status = 'active' AND approval_status = 'approved';
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_preferred_active ON procurement_vendors(id, company_name)
    WHERE preferred_supplier = true AND vendor_status = 'active';
CREATE INDEX IF NOT EXISTS idx_procurement_vendors_high_rated ON procurement_vendors(id, overall_rating)
    WHERE overall_rating >= 4.0 AND vendor_status = 'active';

-- Triggers for updated_at
CREATE TRIGGER update_procurement_vendors_updated_at BEFORE UPDATE ON procurement_vendors
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to generate vendor number
CREATE OR REPLACE FUNCTION generate_vendor_number()
RETURNS VARCHAR(20) AS $$
DECLARE
    current_year INTEGER;
    sequence_number INTEGER;
    vendor_num VARCHAR(20);
BEGIN
    current_year := EXTRACT(YEAR FROM CURRENT_DATE);

    -- Get next sequence number for the year
    SELECT COALESCE(MAX(CAST(SUBSTRING(vendor_number FROM '[0-9]+$') AS INTEGER)), 0) + 1
    INTO sequence_number
    FROM procurement_vendors
    WHERE vendor_number LIKE 'VEND-' || current_year || '-%';

    vendor_num := 'VEND-' || current_year || '-' || LPAD(sequence_number::TEXT, 4, '0');

    RETURN vendor_num;
END;
$$ LANGUAGE plpgsql;

-- Function to update vendor performance metrics
CREATE OR REPLACE FUNCTION update_vendor_performance_metrics(p_vendor_id INTEGER)
RETURNS VOID AS $$
DECLARE
    avg_on_time DECIMAL(5,2);
    avg_quality DECIMAL(3,1);
    avg_responsiveness DECIMAL(3,1);
BEGIN
    -- Calculate average on-time delivery rate from recent purchase orders
    SELECT COALESCE(AVG(CASE WHEN actual_delivery_date <= expected_delivery_date THEN 100.0 ELSE 0.0 END), 0)
    INTO avg_on_time
    FROM procurement_purchase_orders
    WHERE vendor_id = p_vendor_id
      AND order_date >= CURRENT_DATE - INTERVAL '6 months'
      AND status = 'completed';

    -- Calculate average quality rating from recent inspections
    SELECT COALESCE(AVG(quality_score), 0)
    INTO avg_quality
    FROM procurement_quality_inspections
    WHERE vendor_id = p_vendor_id
      AND inspection_date >= CURRENT_DATE - INTERVAL '6 months';

    -- Calculate average responsiveness rating from feedback
    SELECT COALESCE(AVG(responsiveness_rating), 0)
    INTO avg_responsiveness
    FROM procurement_vendor_feedback
    WHERE vendor_id = p_vendor_id
      AND feedback_date >= CURRENT_DATE - INTERVAL '6 months';

    -- Update vendor ratings
    UPDATE procurement_vendors
    SET on_time_delivery_rate = avg_on_time,
        quality_rating = avg_quality,
        responsiveness_rating = avg_responsiveness,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_vendor_id;
END;
$$ LANGUAGE plpgsql;

-- Function to get vendor performance summary
CREATE OR REPLACE FUNCTION get_vendor_performance_summary(p_vendor_id INTEGER)
RETURNS TABLE (
    total_orders INTEGER,
    total_order_value DECIMAL(15,2),
    on_time_delivery_rate DECIMAL(5,2),
    quality_rating DECIMAL(3,1),
    responsiveness_rating DECIMAL(3,1),
    overall_rating DECIMAL(3,1)
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        COUNT(po.id)::INTEGER,
        COALESCE(SUM(po.total_amount), 0)::DECIMAL(15,2),
        v.on_time_delivery_rate,
        v.quality_rating,
        v.responsiveness_rating,
        v.overall_rating
    FROM procurement_vendors v
    LEFT JOIN procurement_purchase_orders po ON v.id = po.vendor_id
    WHERE v.id = p_vendor_id
      AND po.status NOT IN ('cancelled', 'draft')
    GROUP BY v.id, v.on_time_delivery_rate, v.quality_rating, v.responsiveness_rating, v.overall_rating;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE procurement_vendors IS 'Vendor and supplier master data for procurement management';
COMMENT ON COLUMN procurement_vendors.vendor_number IS 'Unique vendor identifier';
COMMENT ON COLUMN procurement_vendors.vendor_type IS 'Type of vendor relationship';
COMMENT ON COLUMN procurement_vendors.overall_rating IS 'Calculated overall performance rating';
COMMENT ON COLUMN procurement_vendors.on_time_delivery_rate IS 'Percentage of on-time deliveries';
COMMENT ON COLUMN procurement_vendors.quality_rating IS 'Average quality rating from inspections';
COMMENT ON COLUMN procurement_vendors.responsiveness_rating IS 'Average responsiveness rating from feedback';
