-- TPT Open ERP - Inventory Products
-- Migration: 011
-- Description: Product catalog and inventory items

CREATE TABLE IF NOT EXISTS inventory_products (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    sku VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,

    -- Product categorization
    category_id INTEGER,
    subcategory_id INTEGER,
    brand_id INTEGER,
    manufacturer_id INTEGER,

    -- Product details
    product_type VARCHAR(50) DEFAULT 'goods', -- goods, service, digital
    unit_of_measure VARCHAR(20) DEFAULT 'each',
    weight DECIMAL(10,3),
    weight_unit VARCHAR(10) DEFAULT 'kg',
    dimensions JSONB DEFAULT '{}', -- length, width, height

    -- Pricing
    cost_price DECIMAL(15,2) DEFAULT 0.00,
    selling_price DECIMAL(15,2) DEFAULT 0.00,
    wholesale_price DECIMAL(15,2) DEFAULT 0.00,
    retail_price DECIMAL(15,2) DEFAULT 0.00,
    currency_code VARCHAR(3) DEFAULT 'USD',

    -- Inventory tracking
    track_inventory BOOLEAN DEFAULT true,
    minimum_stock INTEGER DEFAULT 0,
    maximum_stock INTEGER DEFAULT 0,
    reorder_point INTEGER DEFAULT 0,
    reorder_quantity INTEGER DEFAULT 0,

    -- Current stock levels
    current_stock INTEGER DEFAULT 0,
    reserved_stock INTEGER DEFAULT 0,
    available_stock INTEGER GENERATED ALWAYS AS (current_stock - reserved_stock) STORED,

    -- Product status
    is_active BOOLEAN DEFAULT true,
    is_featured BOOLEAN DEFAULT false,
    is_discontinued BOOLEAN DEFAULT false,

    -- Supplier information
    primary_supplier_id INTEGER,
    supplier_sku VARCHAR(100),

    -- Tax information
    tax_category VARCHAR(50),
    tax_rate DECIMAL(5,2) DEFAULT 0.00,

    -- Images and media
    main_image_url TEXT,
    additional_images JSONB DEFAULT '[]',

    -- Product attributes
    attributes JSONB DEFAULT '{}',
    specifications JSONB DEFAULT '{}',

    -- SEO and marketing
    seo_title VARCHAR(255),
    seo_description TEXT,
    meta_keywords TEXT[],

    -- Barcode and identification
    barcode VARCHAR(100),
    qr_code TEXT,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT inventory_products_sku_format CHECK (sku ~* '^[A-Z0-9_-]+$'),
    CONSTRAINT inventory_products_positive_prices CHECK (
        cost_price >= 0 AND selling_price >= 0 AND wholesale_price >= 0 AND retail_price >= 0
    ),
    CONSTRAINT inventory_products_stock_levels CHECK (
        minimum_stock >= 0 AND maximum_stock >= 0 AND reorder_point >= 0
    ),
    CONSTRAINT inventory_products_product_type CHECK (
        product_type IN ('goods', 'service', 'digital', 'bundle')
    )
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_inventory_products_sku ON inventory_products(sku);
CREATE INDEX IF NOT EXISTS idx_inventory_products_name ON inventory_products(name);
CREATE INDEX IF NOT EXISTS idx_inventory_products_category ON inventory_products(category_id);
CREATE INDEX IF NOT EXISTS idx_inventory_products_brand ON inventory_products(brand_id);
CREATE INDEX IF NOT EXISTS idx_inventory_products_supplier ON inventory_products(primary_supplier_id);
CREATE INDEX IF NOT EXISTS idx_inventory_products_active ON inventory_products(is_active);
CREATE INDEX IF NOT EXISTS idx_inventory_products_featured ON inventory_products(is_featured);
CREATE INDEX IF NOT EXISTS idx_inventory_products_barcode ON inventory_products(barcode);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_inventory_products_category_active ON inventory_products(category_id, is_active);
CREATE INDEX IF NOT EXISTS idx_inventory_products_stock_levels ON inventory_products(minimum_stock, current_stock);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_inventory_products_low_stock ON inventory_products(id, current_stock, reorder_point)
    WHERE current_stock <= reorder_point AND is_active = true;
CREATE INDEX IF NOT EXISTS idx_inventory_products_featured_active ON inventory_products(id, name)
    WHERE is_featured = true AND is_active = true;

-- Triggers for updated_at
CREATE TRIGGER update_inventory_products_updated_at BEFORE UPDATE ON inventory_products
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to update product stock
CREATE OR REPLACE FUNCTION update_product_stock(
    p_product_id INTEGER,
    p_quantity_change INTEGER,
    p_transaction_type VARCHAR(20),
    p_reference_id INTEGER DEFAULT NULL,
    p_created_by INTEGER DEFAULT NULL
)
RETURNS BOOLEAN AS $$
DECLARE
    old_stock INTEGER;
    new_stock INTEGER;
BEGIN
    -- Get current stock
    SELECT current_stock INTO old_stock
    FROM inventory_products
    WHERE id = p_product_id;

    -- Calculate new stock
    new_stock := old_stock + p_quantity_change;

    -- Update product stock
    UPDATE inventory_products
    SET current_stock = new_stock,
        updated_at = CURRENT_TIMESTAMP,
        updated_by = p_created_by
    WHERE id = p_product_id;

    -- Log stock movement
    INSERT INTO inventory_stock_movements (
        product_id, transaction_type, quantity_change,
        old_stock, new_stock, reference_id, created_by
    ) VALUES (
        p_product_id, p_transaction_type, p_quantity_change,
        old_stock, new_stock, p_reference_id, p_created_by
    );

    RETURN true;
END;
$$ LANGUAGE plpgsql;

-- Function to check low stock alerts
CREATE OR REPLACE FUNCTION get_low_stock_products()
RETURNS TABLE (
    product_id INTEGER,
    sku VARCHAR(100),
    name VARCHAR(255),
    current_stock INTEGER,
    minimum_stock INTEGER,
    reorder_point INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        ip.id,
        ip.sku,
        ip.name,
        ip.current_stock,
        ip.minimum_stock,
        ip.reorder_point
    FROM inventory_products ip
    WHERE ip.is_active = true
      AND ip.track_inventory = true
      AND ip.current_stock <= ip.reorder_point
    ORDER BY (ip.reorder_point - ip.current_stock) DESC;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE inventory_products IS 'Product catalog with inventory tracking';
COMMENT ON COLUMN inventory_products.sku IS 'Stock Keeping Unit - unique product identifier';
COMMENT ON COLUMN inventory_products.available_stock IS 'Current stock minus reserved stock';
COMMENT ON COLUMN inventory_products.reorder_point IS 'Stock level that triggers reorder';
COMMENT ON COLUMN inventory_products.attributes IS 'JSON object of product attributes (color, size, etc.)';
COMMENT ON COLUMN inventory_products.specifications IS 'Technical specifications as JSON';
