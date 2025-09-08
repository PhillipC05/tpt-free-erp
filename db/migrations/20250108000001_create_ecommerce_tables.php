<?php

use Phinx\Migration\AbstractMigration;

class CreateEcommerceTables extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change()
    {
        // Ecommerce product categories
        $table = $this->table('ecommerce_categories');
        $table->addColumn('company_id', 'integer', ['null' => false])
              ->addColumn('category_name', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('parent_id', 'integer', ['null' => true])
              ->addColumn('image_url', 'string', ['limit' => 500, 'null' => true])
              ->addColumn('display_order', 'integer', ['default' => 0])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addColumn('meta_title', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('meta_description', 'text', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('created_by', 'integer', ['null' => true])
              ->addColumn('updated_by', 'integer', ['null' => true])
              ->addIndex(['company_id', 'is_active'])
              ->addIndex(['parent_id'])
              ->addIndex(['slug'], ['unique' => true])
              ->addForeignKey('company_id', 'companies', 'id', ['delete' => 'CASCADE'])
              ->addForeignKey('parent_id', 'ecommerce_categories', 'id', ['delete' => 'SET_NULL'])
              ->create();

        // Ecommerce products
        $table = $this->table('ecommerce_products');
        $table->addColumn('company_id', 'integer', ['null' => false])
              ->addColumn('product_name', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('sku', 'string', ['limit' => 100, 'null' => false])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('short_description', 'text', ['null' => true])
              ->addColumn('product_type', 'enum', ['values' => ['physical', 'digital', 'service'], 'default' => 'physical'])
              ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
              ->addColumn('compare_price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('cost_price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('track_quantity', 'boolean', ['default' => true])
              ->addColumn('quantity', 'integer', ['default' => 0])
              ->addColumn('low_stock_threshold', 'integer', ['default' => 10])
              ->addColumn('weight', 'decimal', ['precision' => 8, 'scale' => 3, 'null' => true])
              ->addColumn('weight_unit', 'enum', ['values' => ['kg', 'g', 'lb', 'oz'], 'default' => 'kg'])
              ->addColumn('dimensions', 'text', ['null' => true]) // JSON dimensions
              ->addColumn('category_id', 'integer', ['null' => true])
              ->addColumn('brand', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('tags', 'text', ['null' => true]) // JSON tags
              ->addColumn('images', 'text', ['null' => true]) // JSON images
              ->addColumn('featured_image', 'string', ['limit' => 500, 'null' => true])
              ->addColumn('qr_code', 'string', ['limit' => 500, 'null' => true])
              ->addColumn('qr_code_data', 'text', ['null' => true])
              ->addColumn('barcode', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('barcode_type', 'enum', ['values' => ['ean13', 'upc', 'code128', 'qr'], 'default' => 'ean13'])
              ->addColumn('variants', 'text', ['null' => true]) // JSON variants
              ->addColumn('attributes', 'text', ['null' => true]) // JSON attributes
              ->addColumn('seo_title', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('seo_description', 'text', ['null' => true])
              ->addColumn('seo_keywords', 'string', ['limit' => 500, 'null' => true])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addColumn('is_featured', 'boolean', ['default' => false])
              ->addColumn('is_digital', 'boolean', ['default' => false])
              ->addColumn('digital_file', 'string', ['limit' => 500, 'null' => true])
              ->addColumn('download_limit', 'integer', ['null' => true])
              ->addColumn('download_expiry_days', 'integer', ['null' => true])
              ->addColumn('requires_shipping', 'boolean', ['default' => true])
              ->addColumn('tax_class', 'string', ['limit' => 100, 'default' => 'standard'])
              ->addColumn('vendor_id', 'integer', ['null' => true])
              ->addColumn('inventory_product_id', 'integer', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('created_by', 'integer', ['null' => true])
              ->addColumn('updated_by', 'integer', ['null' => true])
              ->addIndex(['company_id', 'is_active'])
              ->addIndex(['category_id'])
              ->addIndex(['sku'], ['unique' => true])
              ->addIndex(['slug'], ['unique' => true])
              ->addIndex(['is_featured'])
              ->addIndex(['vendor_id'])
              ->addIndex(['inventory_product_id'])
              ->addForeignKey('company_id', 'companies', 'id', ['delete' => 'CASCADE'])
              ->addForeignKey('category_id', 'ecommerce_categories', 'id', ['delete' => 'SET_NULL'])
              ->addForeignKey('vendor_id', 'vendors', 'id', ['delete' => 'SET_NULL'])
              ->addForeignKey('inventory_product_id', 'products', 'id', ['delete' => 'SET_NULL'])
              ->create();

        // Product variants
        $table = $this->table('ecommerce_product_variants');
        $table->addColumn('company_id', 'integer', ['null' => false])
              ->addColumn('product_id', 'integer', ['null' => false])
              ->addColumn('variant_name', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('variant_sku', 'string', ['limit' => 100, 'null' => false])
              ->addColumn('price_modifier', 'decimal', ['precision' => 8, 'scale' => 2, 'default' => 0])
              ->addColumn('quantity', 'integer', ['default' => 0])
              ->addColumn('weight_modifier', 'decimal', ['precision' => 8, 'scale' => 3, 'default' => 0])
              ->addColumn('attributes', 'text', ['null' => false]) // JSON attributes
              ->addColumn('image_url', 'string', ['limit' => 500, 'null' => true])
              ->addColumn('display_order', 'integer', ['default' => 0])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['product_id'])
              ->addIndex(['company_id'])
              ->addIndex(['is_active'])
              ->addForeignKey('company_id', 'companies', 'id', ['delete' => 'CASCADE'])
              ->addForeignKey('product_id', 'ecommerce_products', 'id', ['delete' => 'CASCADE'])
              ->create();

        // Customer accounts for storefront
        $table = $this->table('ecommerce_customers');
        $table->addColumn('company_id', 'integer', ['null' => false])
              ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('first_name', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('last_name', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('date_of_birth', 'date', ['null' => true])
              ->addColumn('gender', 'enum', ['values' => ['male', 'female', 'other', 'prefer_not_to_say'], 'null' => true])
              ->addColumn('avatar', 'string', ['limit' => 500, 'null' => true])
              ->addColumn('email_verified', 'boolean', ['default' => false])
              ->addColumn('email_verification_token', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('email_verified_at', 'timestamp', ['null' => true])
              ->addColumn('phone_verified', 'boolean', ['default' => false])
              ->addColumn('phone_verification_code', 'string', ['limit' => 10, 'null' => true])
              ->addColumn('phone_verified_at', 'timestamp', ['null' => true])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addColumn('last_login_at', 'timestamp', ['null' => true])
              ->addColumn('login_count', 'integer', ['default' => 0])
              ->addColumn('preferences', 'text', ['null' => true]) // JSON preferences
              ->addColumn('marketing_consent', 'boolean', ['default' => false])
              ->addColumn('sms_consent', 'boolean', ['default' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['email'], ['unique' => true])
              ->addIndex(['company_id'])
              ->addIndex(['is_active'])
              ->addIndex(['email_verified'])
              ->addForeignKey('company_id', 'companies', 'id', ['delete' => 'CASCADE'])
              ->create();

        // Shopping cart
        $table = $this->table('ecommerce_cart');
        $table->addColumn('company_id', 'integer', ['null' => false])
              ->addColumn('session_id', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('customer_id', 'integer', ['null' => true])
              ->addColumn('items', 'text', ['null' => false]) // JSON cart items
              ->addColumn('subtotal', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
              ->addColumn('tax_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
              ->addColumn('shipping_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
              ->addColumn('discount_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
              ->addColumn('total', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
              ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'USD'])
              ->addColumn('expires_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP + INTERVAL 7 DAY'])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['session_id'])
              ->addIndex(['customer_id'])
              ->addIndex(['expires_at'])
              ->addForeignKey('company_id', 'companies', 'id', ['delete' => 'CASCADE'])
              ->addForeignKey('customer_id', 'ecommerce_customers', 'id', ['delete' => 'CASCADE'])
              ->create();

        // Ecommerce orders
        $table = $this->table('ecommerce_orders');
        $table->addColumn('company_id', 'integer', ['null' => false])
              ->addColumn('order_number', 'string', ['limit' => 50, 'null' => false])
              ->addColumn('customer_id', 'integer', ['null' => true])
              ->addColumn('customer_email', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('status', 'enum', ['values' => ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'], 'default' => 'pending'])
              ->addColumn('payment_status', 'enum', ['values' => ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'], 'default' => 'pending'])
              ->addColumn('fulfillment_status', 'enum', ['values' => ['unfulfilled', 'partially_fulfilled', 'fulfilled'], 'default' => 'unfulfilled'])
              ->addColumn('order_type', 'enum', ['values' => ['pickup', 'delivery'], 'default' => 'delivery'])
              ->addColumn('pickup_location_id', 'integer', ['null' => true])
              ->addColumn('shipping_address', 'text', ['null' => true]) // JSON shipping address
              ->addColumn('billing_address', 'text', ['null' => true]) // JSON billing address
              ->addColumn('items', 'text', ['null' => false]) // JSON order items
              ->addColumn('subtotal', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
              ->addColumn('tax_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
              ->addColumn('shipping_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
              ->addColumn('discount_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
              ->addColumn('total', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
              ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'USD'])
              ->addColumn('payment_method', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('payment_gateway', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('transaction_id', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('shipping_method', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('tracking_number', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->addColumn('customer_notes', 'text', ['null' => true])
              ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('user_agent', 'text', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('created_by', 'integer', ['null' => true])
              ->addColumn('updated_by', 'integer', ['null' => true])
              ->addIndex(['order_number'], ['unique' => true])
              ->addIndex(['customer_id'])
              ->addIndex(['status'])
              ->addIndex(['payment_status'])
              ->addIndex(['created_at'])
              ->addIndex(['company_id'])
              ->addForeignKey('company_id', 'companies', 'id', ['delete' => 'CASCADE'])
              ->addForeignKey('customer_id', 'ecommerce_customers', 'id', ['delete' => 'CASCADE'])
              ->create();

        // Insert sample data
        $this->insertSampleData();
    }

    private function insertSampleData() {
        // Insert sample categories
        $categories = [
            [
                'company_id' => 1,
                'category_name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices and accessories',
                'display_order' => 1,
                'is_active' => true
            ],
            [
                'company_id' => 1,
                'category_name' => 'Clothing',
                'slug' => 'clothing',
                'description' => 'Fashion and apparel',
                'display_order' => 2,
                'is_active' => true
            ],
            [
                'company_id' => 1,
                'category_name' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Home improvement and garden supplies',
                'display_order' => 3,
                'is_active' => true
            ]
        ];

        $this->table('ecommerce_categories')->insert($categories)->save();

        // Insert sample products
        $products = [
            [
                'company_id' => 1,
                'product_name' => 'Wireless Bluetooth Headphones',
                'slug' => 'wireless-bluetooth-headphones',
                'sku' => 'WBH-001',
                'description' => 'High-quality wireless Bluetooth headphones with noise cancellation',
                'short_description' => 'Premium wireless headphones',
                'product_type' => 'physical',
                'price' => 99.99,
                'compare_price' => 129.99,
                'track_quantity' => true,
                'quantity' => 50,
                'low_stock_threshold' => 10,
                'weight' => 0.3,
                'weight_unit' => 'kg',
                'category_id' => 1,
                'brand' => 'TechBrand',
                'is_active' => true,
                'is_featured' => true,
                'requires_shipping' => true
            ],
            [
                'company_id' => 1,
                'product_name' => 'Cotton T-Shirt',
                'slug' => 'cotton-t-shirt',
                'sku' => 'CTS-001',
                'description' => 'Comfortable 100% cotton t-shirt available in multiple colors',
                'short_description' => 'Classic cotton t-shirt',
                'product_type' => 'physical',
                'price' => 19.99,
                'track_quantity' => true,
                'quantity' => 100,
                'low_stock_threshold' => 20,
                'weight' => 0.2,
                'weight_unit' => 'kg',
                'category_id' => 2,
                'brand' => 'FashionCo',
                'is_active' => true,
                'requires_shipping' => true
            ]
        ];

        $this->table('ecommerce_products')->insert($products)->save();

        // Insert default stock configuration
        $stockConfig = [
            [
                'company_id' => 1,
                'stock_display_type' => 'vague',
                'vague_labels' => '{"low": "Only a few left", "medium": "In stock", "high": "Plenty in stock"}',
                'hide_threshold' => 0,
                'backorder_allowed' => false
            ]
        ];

        $this->table('ecommerce_stock_config')->insert($stockConfig)->save();
    }
}
