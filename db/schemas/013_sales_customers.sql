-- TPT Open ERP - Sales Customers
-- Migration: 013
-- Description: Customer management and CRM data

CREATE TABLE IF NOT EXISTS sales_customers (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    customer_number VARCHAR(50) NOT NULL UNIQUE,
    customer_type VARCHAR(20) DEFAULT 'individual', -- individual, business

    -- Basic Information
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    company_name VARCHAR(255),
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
    payment_terms VARCHAR(50) DEFAULT 'net_30', -- net_15, net_30, net_60, cod
    currency_code VARCHAR(3) DEFAULT 'USD',
    tax_exempt BOOLEAN DEFAULT false,
    tax_exemption_number VARCHAR(50),

    -- Sales Information
    sales_rep_id INTEGER REFERENCES users(id),
    customer_source VARCHAR(50), -- website, referral, advertisement, etc.
    lead_status VARCHAR(20) DEFAULT 'prospect', -- prospect, qualified, customer, inactive
    customer_rating VARCHAR(10) DEFAULT 'C', -- A, B, C, D

    -- Communication Preferences
    email_marketing BOOLEAN DEFAULT true,
    sms_marketing BOOLEAN DEFAULT false,
    phone_marketing BOOLEAN DEFAULT false,
    postal_marketing BOOLEAN DEFAULT false,

    -- Status and Lifecycle
    is_active BOOLEAN DEFAULT true,
    is_vip BOOLEAN DEFAULT false,
    last_contact_date TIMESTAMP NULL,
    next_followup_date TIMESTAMP NULL,
    acquisition_date DATE,
    churn_date DATE NULL,

    -- Social Media and Online Presence
    social_profiles JSONB DEFAULT '{}',
    linkedin_url VARCHAR(255),
    twitter_handle VARCHAR(100),
    facebook_url VARCHAR(255),

    -- Additional Data
    tags TEXT[],
    notes TEXT,
    custom_fields JSONB DEFAULT '{}',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT sales_customers_email_format CHECK (email IS NULL OR email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'),
    CONSTRAINT sales_customers_customer_type CHECK (customer_type IN ('individual', 'business')),
    CONSTRAINT sales_customers_lead_status CHECK (lead_status IN ('prospect', 'qualified', 'customer', 'inactive', 'churned')),
    CONSTRAINT sales_customers_rating CHECK (customer_rating IN ('A', 'B', 'C', 'D')),
    CONSTRAINT sales_customers_payment_terms CHECK (payment_terms IN ('net_15', 'net_30', 'net_60', 'net_90', 'cod', 'prepaid')),
    CONSTRAINT sales_customers_credit_limit_positive CHECK (credit_limit >= 0)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_sales_customers_number ON sales_customers(customer_number);
CREATE INDEX IF NOT EXISTS idx_sales_customers_email ON sales_customers(email);
CREATE INDEX IF NOT EXISTS idx_sales_customers_company ON sales_customers(company_name);
CREATE INDEX IF NOT EXISTS idx_sales_customers_type ON sales_customers(customer_type);
CREATE INDEX IF NOT EXISTS idx_sales_customers_status ON sales_customers(lead_status);
CREATE INDEX IF NOT EXISTS idx_sales_customers_sales_rep ON sales_customers(sales_rep_id);
CREATE INDEX IF NOT EXISTS idx_sales_customers_active ON sales_customers(is_active);
CREATE INDEX IF NOT EXISTS idx_sales_customers_vip ON sales_customers(is_vip);
CREATE INDEX IF NOT EXISTS idx_sales_customers_rating ON sales_customers(customer_rating);
CREATE INDEX IF NOT EXISTS idx_sales_customers_source ON sales_customers(customer_source);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_sales_customers_type_status ON sales_customers(customer_type, lead_status);
CREATE INDEX IF NOT EXISTS idx_sales_customers_rep_status ON sales_customers(sales_rep_id, lead_status);
CREATE INDEX IF NOT EXISTS idx_sales_customers_active_rating ON sales_customers(is_active, customer_rating);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_sales_customers_active_customers ON sales_customers(id, display_name)
    WHERE is_active = true AND lead_status = 'customer';
CREATE INDEX IF NOT EXISTS idx_sales_customers_vip_customers ON sales_customers(id, display_name)
    WHERE is_active = true AND is_vip = true;

-- Triggers for updated_at
CREATE TRIGGER update_sales_customers_updated_at BEFORE UPDATE ON sales_customers
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to generate customer number
CREATE OR REPLACE FUNCTION generate_customer_number()
RETURNS VARCHAR(50) AS $$
DECLARE
    current_year INTEGER;
    sequence_number INTEGER;
    customer_num VARCHAR(50);
BEGIN
    current_year := EXTRACT(YEAR FROM CURRENT_DATE);

    -- Get next sequence number for the year
    SELECT COALESCE(MAX(CAST(SUBSTRING(customer_number FROM '[0-9]+$') AS INTEGER)), 0) + 1
    INTO sequence_number
    FROM sales_customers
    WHERE customer_number LIKE 'CUST-' || current_year || '-%';

    customer_num := 'CUST-' || current_year || '-' || LPAD(sequence_number::TEXT, 6, '0');

    RETURN customer_num;
END;
$$ LANGUAGE plpgsql;

-- Function to get customer summary
CREATE OR REPLACE FUNCTION get_customer_summary(p_customer_id INTEGER)
RETURNS TABLE (
    total_orders INTEGER,
    total_revenue DECIMAL(15,2),
    average_order_value DECIMAL(15,2),
    last_order_date DATE,
    days_since_last_order INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        COUNT(so.id)::INTEGER,
        COALESCE(SUM(so.total_amount), 0)::DECIMAL(15,2),
        COALESCE(AVG(so.total_amount), 0)::DECIMAL(15,2),
        MAX(so.order_date)::DATE,
        CASE
            WHEN MAX(so.order_date) IS NOT NULL THEN (CURRENT_DATE - MAX(so.order_date))::INTEGER
            ELSE NULL
        END
    FROM sales_orders so
    WHERE so.customer_id = p_customer_id
      AND so.status NOT IN ('cancelled', 'draft');
END;
$$ LANGUAGE plpgsql;

-- Function to update customer lifecycle
CREATE OR REPLACE FUNCTION update_customer_lifecycle(p_customer_id INTEGER)
RETURNS VOID AS $$
DECLARE
    last_order_date DATE;
    days_since_order INTEGER;
BEGIN
    -- Get last order date
    SELECT MAX(order_date) INTO last_order_date
    FROM sales_orders
    WHERE customer_id = p_customer_id
      AND status NOT IN ('cancelled', 'draft');

    -- Calculate days since last order
    IF last_order_date IS NOT NULL THEN
        days_since_order := (CURRENT_DATE - last_order_date)::INTEGER;

        -- Update customer status based on inactivity
        IF days_since_order > 365 THEN
            UPDATE sales_customers
            SET lead_status = 'inactive',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = p_customer_id AND lead_status = 'customer';
        END IF;
    END IF;

    -- Update last contact date
    UPDATE sales_customers
    SET last_contact_date = CURRENT_TIMESTAMP,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_customer_id;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE sales_customers IS 'Customer master data for CRM and sales management';
COMMENT ON COLUMN sales_customers.customer_number IS 'Unique customer identifier';
COMMENT ON COLUMN sales_customers.customer_type IS 'Individual or business customer';
COMMENT ON COLUMN sales_customers.lead_status IS 'Customer lifecycle stage';
COMMENT ON COLUMN sales_customers.customer_rating IS 'ABC customer classification';
COMMENT ON COLUMN sales_customers.credit_limit IS 'Maximum credit allowed';
COMMENT ON COLUMN sales_customers.payment_terms IS 'Standard payment terms for this customer';
COMMENT ON COLUMN sales_customers.social_profiles IS 'Social media profiles as JSON';
