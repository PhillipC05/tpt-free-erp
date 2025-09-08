<?php
/**
 * TPT Free ERP - Ecommerce Module
 * Complete B2C storefront with product catalog, shopping cart, and checkout
 */

class Ecommerce extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main ecommerce storefront
     */
    public function index() {
        // Public storefront - no authentication required
        $data = [
            'title' => 'Online Store',
            'featured_products' => $this->getFeaturedProducts(),
            'categories' => $this->getCategories(),
            'banners' => $this->getBanners(),
            'store_settings' => $this->getStoreSettings()
        ];

        $this->renderPublic('ecommerce/storefront', $data);
    }

    /**
     * Product catalog
     */
    public function products() {
        $filters = [
            'category' => $_GET['category'] ?? null,
            'search' => $_GET['search'] ?? null,
            'min_price' => $_GET['min_price'] ?? null,
            'max_price' => $_GET['max_price'] ?? null,
            'brand' => $_GET['brand'] ?? null,
            'sort' => $_GET['sort'] ?? 'name',
            'page' => (int)($_GET['page'] ?? 1),
            'limit' => (int)($_GET['limit'] ?? 24)
        ];

        $products = $this->getProducts($filters);
        $totalProducts = $this->getProductsCount($filters);

        $data = [
            'title' => 'Products',
            'products' => $products,
            'filters' => $filters,
            'categories' => $this->getCategories(),
            'brands' => $this->getBrands(),
            'pagination' => [
                'page' => $filters['page'],
                'limit' => $filters['limit'],
                'total' => $totalProducts,
                'pages' => ceil($totalProducts / $filters['limit'])
            ],
            'price_range' => $this->getPriceRange()
        ];

        $this->renderPublic('ecommerce/products', $data);
    }

    /**
     * Individual product page
     */
    public function product($slug) {
        $product = $this->getProductBySlug($slug);

        if (!$product) {
            $this->renderPublic('errors/404', ['title' => 'Product Not Found']);
            return;
        }

        // Track product view
        $this->trackProductView($product['id']);

        $data = [
            'title' => $product['product_name'],
            'product' => $product,
            'variants' => $this->getProductVariants($product['id']),
            'reviews' => $this->getProductReviews($product['id']),
            'related_products' => $this->getRelatedProducts($product['id'], $product['category_id']),
            'breadcrumbs' => $this->getProductBreadcrumbs($product),
            'stock_config' => $this->getStockConfig($product['id'])
        ];

        $this->renderPublic('ecommerce/product', $data);
    }

    /**
     * Shopping cart
     */
    public function cart() {
        $cart = $this->getCart();
        $cartItems = $this->getCartItems($cart['id'] ?? null);

        $data = [
            'title' => 'Shopping Cart',
            'cart' => $cart,
            'items' => $cartItems,
            'subtotal' => array_sum(array_column($cartItems, 'total_price')),
            'shipping_options' => $this->getShippingOptions(),
            'tax_rate' => $this->getTaxRate()
        ];

        $this->renderPublic('ecommerce/cart', $data);
    }

    /**
     * Checkout process
     */
    public function checkout() {
        // Require cart with items
        $cart = $this->getCart();
        $cartItems = $this->getCartItems($cart['id'] ?? null);

        if (empty($cartItems)) {
            $this->redirect('/ecommerce/cart');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processCheckout();
        }

        $data = [
            'title' => 'Checkout',
            'cart' => $cart,
            'items' => $cartItems,
            'subtotal' => array_sum(array_column($cartItems, 'total_price')),
            'shipping_options' => $this->getShippingOptions(),
            'payment_methods' => $this->getPaymentMethods(),
            'countries' => $this->getCountries(),
            'customer' => $this->getCurrentCustomer()
        ];

        $this->renderPublic('ecommerce/checkout', $data);
    }

    /**
     * Customer account pages
     */
    public function account() {
        // Require customer authentication
        $customer = $this->getCurrentCustomer();
        if (!$customer) {
            $this->redirect('/ecommerce/login');
            return;
        }

        $data = [
            'title' => 'My Account',
            'customer' => $customer,
            'orders' => $this->getCustomerOrders($customer['id']),
            'addresses' => $this->getCustomerAddresses($customer['id']),
            'wishlist' => $this->getCustomerWishlist($customer['id'])
        ];

        $this->renderPublic('ecommerce/account', $data);
    }

    /**
     * Customer login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processLogin();
        }

        $data = [
            'title' => 'Login',
            'redirect' => $_GET['redirect'] ?? '/ecommerce/account'
        ];

        $this->renderPublic('ecommerce/login', $data);
    }

    /**
     * Customer registration
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processRegistration();
        }

        $data = [
            'title' => 'Register',
            'countries' => $this->getCountries()
        ];

        $this->renderPublic('ecommerce/register', $data);
    }

    // ============================================================================
    // PRIVATE METHODS - DATA RETRIEVAL
    // ============================================================================

    private function getFeaturedProducts() {
        return $this->db->query("
            SELECT
                ep.*,
                ec.category_name,
                ec.slug as category_slug,
                AVG(epr.rating) as avg_rating,
                COUNT(epr.id) as review_count
            FROM ecommerce_products ep
            LEFT JOIN ecommerce_categories ec ON ep.category_id = ec.id
            LEFT JOIN ecommerce_product_reviews epr ON ep.id = epr.product_id AND epr.status = 'approved'
            WHERE ep.is_active = true AND ep.is_featured = true
            GROUP BY ep.id
            ORDER BY ep.created_at DESC
            LIMIT 12
        ");
    }

    private function getCategories() {
        return $this->db->query("
            SELECT
                ec.*,
                COUNT(ep.id) as product_count
            FROM ecommerce_categories ec
            LEFT JOIN ecommerce_products ep ON ec.id = ep.category_id AND ep.is_active = true
            WHERE ec.is_active = true
            GROUP BY ec.id
            ORDER BY ec.display_order ASC, ec.category_name ASC
        ");
    }

    private function getBanners() {
        return $this->db->query("
            SELECT * FROM ecommerce_banners
            WHERE is_active = true AND (start_date IS NULL OR start_date <= CURDATE())
                AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY display_order ASC
        ");
    }

    private function getStoreSettings() {
        return $this->db->querySingle("
            SELECT * FROM ecommerce_store_settings
            WHERE company_id = 1
        ") ?? [
            'store_name' => 'TPT Online Store',
            'currency' => 'USD',
            'tax_included' => false,
            'allow_guest_checkout' => true
        ];
    }

    private function getProducts($filters) {
        $where = ["ep.is_active = true"];
        $params = [];
        $orderBy = "ep.product_name ASC";

        // Category filter
        if ($filters['category']) {
            $where[] = "ep.category_id = ?";
            $params[] = $filters['category'];
        }

        // Search filter
        if ($filters['search']) {
            $where[] = "(ep.product_name LIKE ? OR ep.description LIKE ? OR ep.sku LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Price filters
        if ($filters['min_price']) {
            $where[] = "ep.price >= ?";
            $params[] = $filters['min_price'];
        }
        if ($filters['max_price']) {
            $where[] = "ep.price <= ?";
            $params[] = $filters['max_price'];
        }

        // Brand filter
        if ($filters['brand']) {
            $where[] = "ep.brand = ?";
            $params[] = $filters['brand'];
        }

        // Sorting
        switch ($filters['sort']) {
            case 'price_low':
                $orderBy = "ep.price ASC";
                break;
            case 'price_high':
                $orderBy = "ep.price DESC";
                break;
            case 'newest':
                $orderBy = "ep.created_at DESC";
                break;
            case 'rating':
                $orderBy = "avg_rating DESC";
                break;
            default:
                $orderBy = "ep.product_name ASC";
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($filters['page'] - 1) * $filters['limit'];

        return $this->db->query("
            SELECT
                ep.*,
                ec.category_name,
                ec.slug as category_slug,
                AVG(epr.rating) as avg_rating,
                COUNT(epr.id) as review_count,
                esc.stock_display_type,
                esc.vague_labels
            FROM ecommerce_products ep
            LEFT JOIN ecommerce_categories ec ON ep.category_id = ec.id
            LEFT JOIN ecommerce_product_reviews epr ON ep.id = epr.product_id AND epr.status = 'approved'
            LEFT JOIN ecommerce_stock_config esc ON (esc.product_id = ep.id OR (esc.product_id IS NULL AND esc.company_id = ep.company_id))
            WHERE $whereClause
            GROUP BY ep.id
            ORDER BY $orderBy
            LIMIT ? OFFSET ?
        ", array_merge($params, [$filters['limit'], $offset]));
    }

    private function getProductsCount($filters) {
        $where = ["is_active = true"];
        $params = [];

        // Apply same filters as getProducts
        if ($filters['category']) {
            $where[] = "category_id = ?";
            $params[] = $filters['category'];
        }

        if ($filters['search']) {
            $where[] = "(product_name LIKE ? OR description LIKE ? OR sku LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if ($filters['min_price']) {
            $where[] = "price >= ?";
            $params[] = $filters['min_price'];
        }
        if ($filters['max_price']) {
            $where[] = "price <= ?";
            $params[] = $filters['max_price'];
        }

        if ($filters['brand']) {
            $where[] = "brand = ?";
            $params[] = $filters['brand'];
        }

        $whereClause = implode(' AND ', $where);

        $result = $this->db->querySingle("
            SELECT COUNT(*) as total FROM ecommerce_products
            WHERE $whereClause
        ", $params);

        return $result['total'] ?? 0;
    }

    private function getProductBySlug($slug) {
        return $this->db->querySingle("
            SELECT
                ep.*,
                ec.category_name,
                ec.slug as category_slug,
                AVG(epr.rating) as avg_rating,
                COUNT(epr.id) as review_count
            FROM ecommerce_products ep
            LEFT JOIN ecommerce_categories ec ON ep.category_id = ec.id
            LEFT JOIN ecommerce_product_reviews epr ON ep.id = epr.product_id AND epr.status = 'approved'
            WHERE ep.slug = ? AND ep.is_active = true
            GROUP BY ep.id
        ", [$slug]);
    }

    private function getProductVariants($productId) {
        return $this->db->query("
            SELECT * FROM ecommerce_product_variants
            WHERE product_id = ? AND is_active = true
            ORDER BY display_order ASC
        ", [$productId]);
    }

    private function getProductReviews($productId) {
        return $this->db->query("
            SELECT
                epr.*,
                ec.first_name,
                ec.last_name,
                eo.order_number
            FROM ecommerce_product_reviews epr
            LEFT JOIN ecommerce_customers ec ON epr.customer_id = ec.id
            LEFT JOIN ecommerce_orders eo ON epr.order_id = eo.id
            WHERE epr.product_id = ? AND epr.status = 'approved'
            ORDER BY epr.created_at DESC
            LIMIT 10
        ", [$productId]);
    }

    private function getRelatedProducts($productId, $categoryId) {
        return $this->db->query("
            SELECT
                ep.*,
                AVG(epr.rating) as avg_rating
            FROM ecommerce_products ep
            LEFT JOIN ecommerce_product_reviews epr ON ep.id = epr.product_id AND epr.status = 'approved'
            WHERE ep.category_id = ? AND ep.id != ? AND ep.is_active = true
            GROUP BY ep.id
            ORDER BY RAND()
            LIMIT 4
        ", [$categoryId, $productId]);
    }

    private function getProductBreadcrumbs($product) {
        $breadcrumbs = [['name' => 'Home', 'url' => '/ecommerce']];

        if ($product['category_name']) {
            $breadcrumbs[] = [
                'name' => $product['category_name'],
                'url' => '/ecommerce/products?category=' . $product['category_id']
            ];
        }

        $breadcrumbs[] = [
            'name' => $product['product_name'],
            'url' => null
        ];

        return $breadcrumbs;
    }

    private function getStockConfig($productId) {
        return $this->db->querySingle("
            SELECT * FROM ecommerce_stock_config
            WHERE (product_id = ? OR (product_id IS NULL AND company_id = 1))
            ORDER BY product_id DESC
            LIMIT 1
        ", [$productId]) ?? [
            'stock_display_type' => 'exact',
            'vague_labels' => '{"low": "Only a few left", "medium": "In stock", "high": "Plenty in stock"}'
        ];
    }

    private function getCart() {
        $sessionId = session_id();

        $cart = $this->db->querySingle("
            SELECT * FROM ecommerce_cart
            WHERE session_id = ? AND expires_at > NOW()
        ", [$sessionId]);

        if (!$cart) {
            $cartId = $this->db->insert('ecommerce_cart', [
                'company_id' => 1,
                'session_id' => $sessionId,
                'customer_id' => $this->getCurrentCustomer()['id'] ?? null,
                'items' => json_encode([]),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]);

            $cart = $this->db->querySingle("SELECT * FROM ecommerce_cart WHERE id = ?", [$cartId]);
        }

        return $cart;
    }

    private function getCartItems($cartId) {
        if (!$cartId) return [];

        return $this->db->query("
            SELECT
                eci.*,
                ep.product_name,
                ep.slug,
                ep.featured_image,
                ep.sku
            FROM ecommerce_cart_items eci
            JOIN ecommerce_products ep ON eci.product_id = ep.id
            WHERE eci.cart_id = ?
            ORDER BY eci.created_at ASC
        ", [$cartId]);
    }

    private function getShippingOptions() {
        return [
            [
                'id' => 'standard',
                'name' => 'Standard Shipping',
                'description' => '5-7 business days',
                'cost' => 9.99,
                'estimated_days' => '5-7'
            ],
            [
                'id' => 'express',
                'name' => 'Express Shipping',
                'description' => '2-3 business days',
                'cost' => 19.99,
                'estimated_days' => '2-3'
            ],
            [
                'id' => 'overnight',
                'name' => 'Overnight Shipping',
                'description' => 'Next business day',
                'cost' => 39.99,
                'estimated_days' => '1'
            ]
        ];
    }

    private function getPaymentMethods() {
        return [
            'card' => 'Credit/Debit Card',
            'paypal' => 'PayPal',
            'apple_pay' => 'Apple Pay',
            'google_pay' => 'Google Pay',
            'crypto' => 'Cryptocurrency'
        ];
    }

    private function getCountries() {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'NL' => 'Netherlands',
            'BE' => 'Belgium'
        ];
    }

    private function getCurrentCustomer() {
        // Check if customer is logged in via session
        if (isset($_SESSION['customer_id'])) {
            return $this->db->querySingle("
                SELECT * FROM ecommerce_customers
                WHERE id = ? AND is_active = true
            ", [$_SESSION['customer_id']]);
        }
        return null;
    }

    private function getCustomerOrders($customerId) {
        return $this->db->query("
            SELECT * FROM ecommerce_orders
            WHERE customer_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ", [$customerId]);
    }

    private function getCustomerAddresses($customerId) {
        return $this->db->query("
            SELECT * FROM ecommerce_customer_addresses
            WHERE customer_id = ?
            ORDER BY is_default DESC, created_at DESC
        ", [$customerId]);
    }

    private function getCustomerWishlist($customerId) {
        return $this->db->query("
            SELECT
                ew.*,
                ep.product_name,
                ep.slug,
                ep.price,
                ep.featured_image
            FROM ecommerce_wishlist ew
            JOIN ecommerce_products ep ON ew.product_id = ep.id
            WHERE ew.customer_id = ?
            ORDER BY ew.added_at DESC
        ", [$customerId]);
    }

    private function getBrands() {
        return $this->db->query("
            SELECT DISTINCT brand FROM ecommerce_products
            WHERE brand IS NOT NULL AND brand != '' AND is_active = true
            ORDER BY brand ASC
        ");
    }

    private function getPriceRange() {
        return $this->db->querySingle("
            SELECT
                MIN(price) as min_price,
                MAX(price) as max_price
            FROM ecommerce_products
            WHERE is_active = true
        ");
    }

    private function getTaxRate() {
        // Default tax rate - could be enhanced with location-based tax calculation
        return 0.08; // 8%
    }

    // ============================================================================
    // PRIVATE METHODS - BUSINESS LOGIC
    // ============================================================================

    private function trackProductView($productId) {
        $customerId = $this->getCurrentCustomer()['id'] ?? null;
        $sessionId = session_id();

        $this->db->insert('ecommerce_product_views', [
            'company_id' => 1,
            'product_id' => $productId,
            'customer_id' => $customerId,
            'session_id' => $sessionId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'referrer' => $_SERVER['HTTP_REFERER'] ?? null
        ]);
    }

    private function processLogin() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->setFlash('error', 'Please enter both email and password');
            $this->redirect('/ecommerce/login');
            return;
        }

        $customer = $this->db->querySingle("
            SELECT * FROM ecommerce_customers
            WHERE email = ? AND is_active = true
        ", [$email]);

        if (!$customer || !password_verify($password, $customer['password_hash'])) {
            $this->setFlash('error', 'Invalid email or password');
            $this->redirect('/ecommerce/login');
            return;
        }

        // Update login tracking
        $this->db->update('ecommerce_customers', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'login_count' => $customer['login_count'] + 1
        ], 'id = ?', [$customer['id']]);

        // Set session
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_email'] = $customer['email'];

        $redirect = $_POST['redirect'] ?? '/ecommerce/account';
        $this->redirect($redirect);
    }

    private function processRegistration() {
        $data = [
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'phone' => $_POST['phone'] ?? ''
        ];

        // Validation
        if (empty($data['email']) || empty($data['password']) || empty($data['first_name'])) {
            $this->setFlash('error', 'Please fill in all required fields');
            $this->redirect('/ecommerce/register');
            return;
        }

        // Check if email already exists
        $existing = $this->db->querySingle("
            SELECT id FROM ecommerce_customers WHERE email = ?
        ", [$data['email']]);

        if ($existing) {
            $this->setFlash('error', 'Email address already registered');
            $this->redirect('/ecommerce/register');
            return;
        }

        // Create customer
        $customerId = $this->db->insert('ecommerce_customers', [
            'company_id' => 1,
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'email_verified' => false,
            'is_active' => true
        ]);

        // Set session
        $_SESSION['customer_id'] = $customerId;
        $_SESSION['customer_email'] = $data['email'];

        $this->setFlash('success', 'Account created successfully! Welcome to our store.');
        $this->redirect('/ecommerce/account');
    }

    private function processCheckout() {
        $cart = $this->getCart();
        $cartItems = $this->getCartItems($cart['id']);

        if (empty($cartItems)) {
            $this->setFlash('error', 'Your cart is empty');
            $this->redirect('/ecommerce/cart');
            return;
        }

        // Validate checkout data
        $checkoutData = $this->validateCheckoutData($_POST);
        if (!$checkoutData) {
            $this->setFlash('error', 'Please fill in all required fields');
            $this->redirect('/ecommerce/checkout');
            return;
        }

        try {
            $this->db->beginTransaction();

            // Calculate totals
            $subtotal = array_sum(array_column($cartItems, 'total_price'));
            $shipping = $checkoutData['shipping_cost'];
            $tax = ($subtotal + $shipping) * $this->getTaxRate();
            $total = $subtotal + $shipping + $tax;

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Create order
            $orderId = $this->db->insert('ecommerce_orders', [
                'company_id' => 1,
                'order_number' => $orderNumber,
                'customer_id' => $this->getCurrentCustomer()['id'] ?? null,
                'customer_email' => $checkoutData['email'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'order_type' => $checkoutData['order_type'],
                'shipping_address' => json_encode($checkoutData['shipping_address']),
                'billing_address' => json_encode($checkoutData['billing_address']),
                'items' => json_encode($cartItems),
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_amount' => $shipping,
                'total' => $total,
                'currency' => 'USD',
                'payment_method' => $checkoutData['payment_method'],
                'notes' => $checkoutData['notes'] ?? '',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            // Create order items
            foreach ($cartItems as $item) {
                $this->db->insert('ecommerce_order_items', [
                    'company_id' => 1,
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'product_data' => json_encode($item)
                ]);
            }

            // Process payment
            $paymentResult = $this->processPayment($orderId, $checkoutData, $total);

            if ($paymentResult['success']) {
                // Update order status
                $this->db->update('ecommerce_orders', [
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'transaction_id' => $paymentResult['transaction_id']
                ], 'id = ?', [$orderId]);

                // Clear cart
                $this->db->delete('ecommerce_cart_items', 'cart_id = ?', [$cart['id']]);

                $this->db->commit();

                // Send confirmation email
                $this->sendOrderConfirmation($orderId);

                $this->setFlash('success', 'Order placed successfully!');
                $this->redirect('/ecommerce/order/' . $orderNumber);
            } else {
                $this->db->rollback();
                $this->setFlash('error', 'Payment failed: ' . $paymentResult['error']);
                $this->redirect('/ecommerce/checkout');
            }

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Order processing failed: ' . $e->getMessage());
            $this->redirect('/ecommerce/checkout');
        }
    }

    private function validateCheckoutData($data) {
        // Basic validation - could be enhanced
        if (empty($data['email']) || empty($data['shipping_address'])) {
            return false;
        }

        return [
            'email' => $data['email'],
            'order_type' => $data['order_type'] ?? 'delivery',
            'shipping_address' => $data['shipping_address'],
            'billing_address' => $data['billing_address'] ?? $data['shipping_address'],
            'payment_method' => $data['payment_method'] ?? 'card',
            'shipping_cost' => (float)($data['shipping_cost'] ?? 9.99),
            'notes' => $data['notes'] ?? ''
        ];
    }

    private function generateOrderNumber() {
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        return "ORD-{$date}-{$random}";
    }

    private function processPayment($orderId, $checkoutData, $amount) {
        // This would integrate with actual payment gateways
        // For now, simulate successful payment
        return [
            'success' => true,
            'transaction_id' => 'txn_' . uniqid(),
            'gateway' => $checkoutData['payment_method']
        ];
    }

    private function sendOrderConfirmation($orderId) {
        // Implementation for sending order confirmation email
        // Would integrate with email system
    }

    // ============================================================================
    // PUBLIC API ENDPOINTS
    // ============================================================================

    public function apiAddToCart() {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $variantId = (int)($_POST['variant_id'] ?? null);

        if (!$productId || $quantity < 1) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid product or quantity'], 400);
        }

        $product = $this->db->querySingle("
            SELECT * FROM ecommerce_products
            WHERE id = ? AND is_active = true
        ", [$productId]);

        if (!$product) {
            $this->jsonResponse(['success' => false, 'error' => 'Product not found'], 404);
        }

        $cart = $this->getCart();

        // Check if item already in cart
        $existingItem = $this->db->querySingle("
            SELECT * FROM ecommerce_cart_items
            WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))
        ", [$cart['id'], $productId, $variantId, $variantId]);

        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            $newTotal = $newQuantity * $existingItem['unit_price'];

            $this->db->update('ecommerce_cart_items', [
                'quantity' => $newQuantity,
                'total_price' => $newTotal
            ], 'id = ?', [$existingItem['id']]);
        } else {
            // Add new item
            $this->db->insert('ecommerce_cart_items', [
                'company_id' => 1,
                'cart_id' => $cart['id'],
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price' => $product['price'],
                'total_price' => $quantity * $product['price'],
                'product_data' => json_encode($product)
            ]);
        }

        $this->jsonResponse(['success' => true, 'message' => 'Product added to cart']);
    }

    public function apiUpdateCart() {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);

        if (!$itemId || $quantity < 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid item or quantity'], 400);
        }

        $cart = $this->getCart();

        if ($quantity === 0) {
            // Remove item
            $this->db->delete('ecommerce_cart_items', 'id = ? AND cart_id = ?', [$itemId, $cart['id']]);
        } else {
            // Update quantity
            $item = $this->db->querySingle("
                SELECT * FROM ecommerce_cart_items WHERE id = ? AND cart_id = ?
            ", [$itemId, $cart['id']]);

            if ($item) {
                $this->db->update('ecommerce_cart_items', [
                    'quantity' => $quantity,
                    'total_price' => $quantity * $item['unit_price']
                ], 'id = ?', [$itemId]);
            }
        }

        $this->jsonResponse(['success' => true]);
    }

    public function apiGetCart() {
        $cart = $this->getCart();
        $items = $this->getCartItems($cart['id'] ?? null);

        $this->jsonResponse([
            'cart' => $cart,
            'items' => $items,
            'subtotal' => array_sum(array_column($items, 'total_price')),
            'item_count' => array_sum(array_column($items, 'quantity'))
        ]);
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    private function renderPublic($template, $data = []) {
        // Custom rendering method for public storefront
        // This would load templates from a public template directory
        extract($data);

        // For now, just output basic HTML structure
        // In a real implementation, this would use a template engine
        header('Content-Type: text/html');
        $title = $data['title'] ?? 'Store';
        echo "<!DOCTYPE html><html><head><title>$title</title></head><body>";
        echo "<h1>$title</h1>";
        echo "<p>Storefront template: $template</p>";
        echo "</body></html>";
    }
}
?>
