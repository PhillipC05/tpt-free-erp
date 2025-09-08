-- TPT Free ERP - Ecommerce Products Schema
-- Complete B2C ecommerce product catalog with QR codes and advanced features

-- Ecommerce product categories
CREATE TABLE IF NOT EXISTS ecommerce_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    category_name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT DEFAULT NULL,
    image_url VARCHAR(500),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES ecommerce_categories(id) ON DELETE SET NULL,
    INDEX idx_company_active (company_id, is_active),
    INDEX idx_parent (parent_id),
    INDEX idx_slug (slug)
);

-- Ecommerce products
CREATE TABLE IF NOT EXISTS ecommerce_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    short_description TEXT,
    product_type ENUM('physical', 'digital', 'service') DEFAULT 'physical',
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2) DEFAULT NULL,
    cost_price DECIMAL(10,2) DEFAULT NULL,
    track_quantity BOOLEAN DEFAULT TRUE,
    quantity INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 10,
    weight DECIMAL(8,3) DEFAULT NULL,
    weight_unit ENUM('kg', 'g', 'lb', 'oz') DEFAULT 'kg',
    dimensions JSON DEFAULT NULL, -- {"length": 10, "width": 5, "height": 2, "unit": "cm"}
    category_id INT,
    brand VARCHAR(255),
    tags JSON DEFAULT NULL, -- ["tag1", "tag2", "tag3"]
    images JSON DEFAULT NULL, -- ["image1.jpg", "image2.jpg"]
    featured_image VARCHAR(500),
    qr_code VARCHAR(500), -- Generated QR code URL
    qr_code_data TEXT, -- QR code content/data
    barcode VARCHAR(100),
    barcode_type ENUM('ean13', 'upc', 'code128', 'qr') DEFAULT 'ean13',
    variants JSON DEFAULT NULL, -- Product variants (size, color, etc.)
    attributes JSON DEFAULT NULL, -- Custom attributes
    seo_title VARCHAR(255),
    seo_description TEXT,
    seo_keywords VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_digital BOOLEAN DEFAULT FALSE,
    digital_file VARCHAR(500), -- Path to digital file
    download_limit INT DEFAULT NULL, -- Download limit for digital products
    download_expiry_days INT DEFAULT NULL, -- Expiry days for downloads
    requires_shipping BOOLEAN DEFAULT TRUE,
    tax_class VARCHAR(100) DEFAULT 'standard',
    vendor_id INT, -- Link to procurement vendors
    inventory_product_id INT, -- Link to existing inventory products
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES ecommerce_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL,
    FOREIGN KEY (inventory_product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_company_active (company_id, is_active),
    INDEX idx_category (category_id),
    INDEX idx_sku (sku),
    INDEX idx_slug (slug),
    INDEX idx_featured (is_featured),
    INDEX idx_vendor (vendor_id),
    INDEX idx_inventory_product (inventory_product_id)
);

-- Product variants (sizes, colors, etc.)
CREATE TABLE IF NOT EXISTS ecommerce_product_variants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_name VARCHAR(255) NOT NULL,
    variant_sku VARCHAR(100) UNIQUE NOT NULL,
    price_modifier DECIMAL(8,2) DEFAULT 0,
    quantity INT DEFAULT 0,
    weight_modifier DECIMAL(8,3) DEFAULT 0,
    attributes JSON NOT NULL, -- {"size": "L", "color": "red"}
    image_url VARCHAR(500),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES ecommerce_products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_company (company_id),
    INDEX idx_active (is_active)
);

-- Product images
CREATE TABLE IF NOT EXISTS ecommerce_product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    image_url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    display_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES ecommerce_products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES ecommerce_product_variants(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_variant (variant_id),
    INDEX idx_primary (is_primary)
);

-- Product reviews and ratings
CREATE TABLE IF NOT EXISTS ecommerce_product_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    product_id INT NOT NULL,
    customer_id INT NOT NULL,
    order_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    review_text TEXT,
    images JSON DEFAULT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    helpful_votes INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES ecommerce_products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES sales_orders(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_customer (customer_id),
    INDEX idx_rating (rating),
    INDEX idx_status (status),
    INDEX idx_featured (is_featured)
);

-- Customer accounts for storefront
CREATE TABLE IF NOT EXISTS ecommerce_customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    avatar VARCHAR(500),
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    email_verified_at TIMESTAMP NULL,
    phone_verified BOOLEAN DEFAULT FALSE,
    phone_verification_code VARCHAR(10),
    phone_verified_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    login_count INT DEFAULT 0,
    preferences JSON DEFAULT NULL, -- Customer preferences
    marketing_consent BOOLEAN DEFAULT FALSE,
    sms_consent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_company (company_id),
    INDEX idx_active (is_active),
    INDEX idx_email_verified (email_verified)
);

-- Customer addresses
CREATE TABLE IF NOT EXISTS ecommerce_customer_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    customer_id INT NOT NULL,
    address_type ENUM('billing', 'shipping') DEFAULT 'shipping',
    is_default BOOLEAN DEFAULT FALSE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    company VARCHAR(255),
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES ecommerce_customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_type (address_type),
    INDEX idx_default (is_default)
);

-- Shopping cart
CREATE TABLE IF NOT EXISTS ecommerce_cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    customer_id INT DEFAULT NULL,
    items JSON NOT NULL, -- Cart items with product details
    subtotal DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'USD',
    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 7 DAY),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES ecommerce_customers(id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_customer (customer_id),
    INDEX idx_expires (expires_at)
);

-- Cart items (detailed breakdown)
CREATE TABLE IF NOT EXISTS ecommerce_cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    product_data JSON NOT NULL, -- Snapshot of product data at time of adding
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (cart_id) REFERENCES ecommerce_cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES ecommerce_products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES ecommerce_product_variants(id) ON DELETE SET NULL,
    INDEX idx_cart (cart_id),
    INDEX idx_product (product_id),
    INDEX idx_variant (variant_id)
);

-- Ecommerce orders
CREATE TABLE IF NOT EXISTS ecommerce_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded', 'partially_refunded') DEFAULT 'pending',
    fulfillment_status ENUM('unfulfilled', 'partially_fulfilled', 'fulfilled') DEFAULT 'unfulfilled',
    order_type ENUM('pickup', 'delivery') DEFAULT 'delivery',
    pickup_location_id INT DEFAULT NULL,
    shipping_address JSON DEFAULT NULL,
    billing_address JSON DEFAULT NULL,
    items JSON NOT NULL, -- Order items snapshot
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method VARCHAR(100),
    payment_gateway VARCHAR(100),
    transaction_id VARCHAR(255),
    shipping_method VARCHAR(100),
    tracking_number VARCHAR(255),
    notes TEXT,
    customer_notes TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES ecommerce_customers(id) ON DELETE CASCADE,
    INDEX idx_order_number (order_number),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at),
    INDEX idx_company (company_id)
);

-- Order items
CREATE TABLE IF NOT EXISTS ecommerce_order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    product_data JSON NOT NULL, -- Snapshot of product data
    fulfillment_status ENUM('pending', 'fulfilled', 'cancelled') DEFAULT 'pending',
    tracking_number VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES ecommerce_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES ecommerce_products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES ecommerce_product_variants(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id),
    INDEX idx_variant (variant_id),
    INDEX idx_fulfillment (fulfillment_status)
);

-- Order fulfillment
CREATE TABLE IF NOT EXISTS ecommerce_order_fulfillment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    order_id INT NOT NULL,
    order_item_id INT DEFAULT NULL,
    fulfillment_type ENUM('pickup', 'shipping') DEFAULT 'shipping',
    status ENUM('pending', 'processing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
    carrier VARCHAR(100),
    tracking_number VARCHAR(255),
    tracking_url VARCHAR(500),
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    estimated_delivery DATE,
    warehouse_id INT,
    picker_id INT,
    packer_id INT,
    shipper_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES ecommerce_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES ecommerce_order_items(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_status (status),
    INDEX idx_carrier (carrier),
    INDEX idx_tracking (tracking_number)
);

-- Pickup locations
CREATE TABLE IF NOT EXISTS ecommerce_pickup_locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    location_name VARCHAR(255) NOT NULL,
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    operating_hours JSON DEFAULT NULL, -- {"monday": "9:00-17:00", ...}
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company (company_id),
    INDEX idx_active (is_active)
);

-- Discounts and coupons
CREATE TABLE IF NOT EXISTS ecommerce_discounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    discount_code VARCHAR(50) UNIQUE NOT NULL,
    discount_name VARCHAR(255) NOT NULL,
    discount_type ENUM('percentage', 'fixed_amount', 'free_shipping') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    minimum_order_amount DECIMAL(10,2) DEFAULT 0,
    maximum_discount_amount DECIMAL(10,2) DEFAULT NULL,
    applies_to ENUM('all', 'products', 'categories', 'customers') DEFAULT 'all',
    applicable_items JSON DEFAULT NULL, -- Product IDs, category IDs, etc.
    usage_limit INT DEFAULT NULL,
    usage_count INT DEFAULT 0,
    customer_usage_limit INT DEFAULT NULL, -- Per customer limit
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_code (discount_code),
    INDEX idx_company (company_id),
    INDEX idx_active (is_active),
    INDEX idx_dates (start_date, end_date)
);

-- Wishlist
CREATE TABLE IF NOT EXISTS ecommerce_wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES ecommerce_customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES ecommerce_products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES ecommerce_product_variants(id) ON DELETE SET NULL,
    UNIQUE KEY unique_wishlist (customer_id, product_id, variant_id),
    INDEX idx_customer (customer_id),
    INDEX idx_product (product_id)
);

-- Abandoned cart tracking
CREATE TABLE IF NOT EXISTS ecommerce_abandoned_carts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    cart_id INT NOT NULL,
    customer_id INT DEFAULT NULL,
    customer_email VARCHAR(255),
    cart_value DECIMAL(10,2) NOT NULL,
    items_count INT NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recovery_email_sent BOOLEAN DEFAULT FALSE,
    recovery_email_sent_at TIMESTAMP NULL,
    recovered BOOLEAN DEFAULT FALSE,
    recovered_at TIMESTAMP NULL,
    recovery_order_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (cart_id) REFERENCES ecommerce_cart(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES ecommerce_customers(id) ON DELETE SET NULL,
    FOREIGN KEY (recovery_order_id) REFERENCES ecommerce_orders(id) ON DELETE SET NULL,
    INDEX idx_company (company_id),
    INDEX idx_customer (customer_id),
    INDEX idx_recovered (recovered),
    INDEX idx_last_activity (last_activity)
);

-- Product view tracking
CREATE TABLE IF NOT EXISTS ecommerce_product_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    product_id INT NOT NULL,
    customer_id INT DEFAULT NULL,
    session_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES ecommerce_products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES ecommerce_customers(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_customer (customer_id),
    INDEX idx_session (session_id),
    INDEX idx_viewed_at (viewed_at)
);

-- Search tracking
CREATE TABLE IF NOT EXISTS ecommerce_search_queries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    customer_id INT DEFAULT NULL,
    session_id VARCHAR(255),
    query VARCHAR(255) NOT NULL,
    results_count INT DEFAULT 0,
    clicked_product_id INT DEFAULT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES ecommerce_customers(id) ON DELETE SET NULL,
    FOREIGN KEY (clicked_product_id) REFERENCES ecommerce_products(id) ON DELETE SET NULL,
    INDEX idx_query (query),
    INDEX idx_customer (customer_id),
    INDEX idx_searched_at (searched_at)
);

-- Stock level configurations
CREATE TABLE IF NOT EXISTS ecommerce_stock_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    product_id INT DEFAULT NULL, -- NULL for global config
    stock_display_type ENUM('exact', 'vague', 'hide') DEFAULT 'exact',
    vague_labels JSON DEFAULT NULL, -- {"low": "Only a few left", "medium": "In stock", "high": "Plenty in stock"}
    hide_threshold INT DEFAULT 0, -- Hide stock when below this number
    backorder_allowed BOOLEAN DEFAULT FALSE,
    backorder_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES ecommerce_products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_config (company_id, product_id)
);

-- Insert default stock configuration
INSERT INTO ecommerce_stock_config (company_id, stock_display_type, vague_labels) VALUES
(1, 'vague', '{"low": "Only a few left", "medium": "In stock", "high": "Plenty in stock"}')
ON DUPLICATE KEY UPDATE stock_display_type = VALUES(stock_display_type);

-- Create indexes for performance
CREATE INDEX idx_ecommerce_products_category_active ON ecommerce_products(category_id, is_active);
CREATE INDEX idx_ecommerce_products_price ON ecommerce_products(price);
CREATE INDEX idx_ecommerce_products_featured ON ecommerce_products(is_featured, is_active);
CREATE INDEX idx_ecommerce_orders_customer_status ON ecommerce_orders(customer_id, status);
CREATE INDEX idx_ecommerce_orders_created_at ON ecommerce_orders(created_at);
CREATE INDEX idx_ecommerce_cart_expires_at ON ecommerce_cart(expires_at);
CREATE INDEX idx_ecommerce_product_reviews_product_rating ON ecommerce_product_reviews(product_id, rating);
CREATE INDEX idx_ecommerce_abandoned_carts_last_activity ON ecommerce_abandoned_carts(last_activity);
