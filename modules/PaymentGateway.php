<?php
/**
 * TPT Free ERP - Payment Gateway Integration Module
 * Complete payment processing with Paddle, subscriptions, and billing management
 */

class PaymentGateway extends BaseController {
    private $db;
    private $user;
    private $paddleConfig;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->paddleConfig = $this->getPaddleConfig();
    }

    /**
     * Main payment gateway dashboard
     */
    public function index() {
        $this->requirePermission('payments.view');

        $data = [
            'title' => 'Payment Gateway Dashboard',
            'payment_stats' => $this->getPaymentStats(),
            'recent_transactions' => $this->getRecentTransactions(),
            'subscription_overview' => $this->getSubscriptionOverview(),
            'revenue_analytics' => $this->getRevenueAnalytics(),
            'payment_methods' => $this->getPaymentMethods()
        ];

        $this->render('modules/payments/dashboard', $data);
    }

    /**
     * Payment processing
     */
    public function process() {
        $this->requirePermission('payments.process');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processPayment();
        }

        $data = [
            'title' => 'Process Payment',
            'products' => $this->getProducts(),
            'customers' => $this->getCustomers(),
            'payment_methods' => $this->getPaymentMethods(),
            'currencies' => $this->getSupportedCurrencies()
        ];

        $this->render('modules/payments/process', $data);
    }

    /**
     * Subscription management
     */
    public function subscriptions() {
        $this->requirePermission('payments.subscriptions.view');

        $filters = [
            'status' => $_GET['status'] ?? 'all',
            'customer_id' => $_GET['customer_id'] ?? null,
            'product_id' => $_GET['product_id'] ?? null
        ];

        $subscriptions = $this->getSubscriptions($filters);

        $data = [
            'title' => 'Subscription Management',
            'subscriptions' => $subscriptions,
            'filters' => $filters,
            'customers' => $this->getCustomers(),
            'products' => $this->getProducts(),
            'subscription_summary' => $this->getSubscriptionSummary($filters)
        ];

        $this->render('modules/payments/subscriptions', $data);
    }

    /**
     * Transaction history
     */
    public function transactions() {
        $this->requirePermission('payments.transactions.view');

        $filters = [
            'status' => $_GET['status'] ?? 'all',
            'type' => $_GET['type'] ?? 'all',
            'customer_id' => $_GET['customer_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d')
        ];

        $transactions = $this->getTransactions($filters);

        $data = [
            'title' => 'Transaction History',
            'transactions' => $transactions,
            'filters' => $filters,
            'customers' => $this->getCustomers(),
            'transaction_summary' => $this->getTransactionSummary($filters)
        ];

        $this->render('modules/payments/transactions', $data);
    }

    /**
     * Refund management
     */
    public function refunds() {
        $this->requirePermission('payments.refunds.view');

        $data = [
            'title' => 'Refund Management',
            'refund_requests' => $this->getRefundRequests(),
            'processed_refunds' => $this->getProcessedRefunds(),
            'refund_policies' => $this->getRefundPolicies(),
            'refund_analytics' => $this->getRefundAnalytics()
        ];

        $this->render('modules/payments/refunds', $data);
    }

    /**
     * Billing and invoicing
     */
    public function billing() {
        $this->requirePermission('payments.billing.view');

        $data = [
            'title' => 'Billing & Invoicing',
            'invoices' => $this->getInvoices(),
            'billing_cycles' => $this->getBillingCycles(),
            'tax_settings' => $this->getTaxSettings(),
            'billing_history' => $this->getBillingHistory()
        ];

        $this->render('modules/payments/billing', $data);
    }

    /**
     * Webhook management
     */
    public function webhooks() {
        $this->requirePermission('payments.webhooks.view');

        $data = [
            'title' => 'Payment Webhooks',
            'webhook_events' => $this->getWebhookEvents(),
            'webhook_logs' => $this->getWebhookLogs(),
            'webhook_settings' => $this->getWebhookSettings(),
            'event_handlers' => $this->getEventHandlers()
        ];

        $this->render('modules/payments/webhooks', $data);
    }

    /**
     * Payment analytics
     */
    public function analytics() {
        $this->requirePermission('payments.analytics.view');

        $data = [
            'title' => 'Payment Analytics',
            'revenue_trends' => $this->getRevenueTrends(),
            'conversion_rates' => $this->getConversionRates(),
            'customer_lifetime_value' => $this->getCustomerLifetimeValue(),
            'payment_method_usage' => $this->getPaymentMethodUsage(),
            'geographic_revenue' => $this->getGeographicRevenue()
        ];

        $this->render('modules/payments/analytics', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getPaddleConfig() {
        return $this->db->querySingle("
            SELECT * FROM payment_gateway_config
            WHERE provider = 'paddle' AND company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getPaymentStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_transactions,
                SUM(amount) as total_revenue,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_payments,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_payments,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
                AVG(amount) as avg_transaction_value,
                MAX(created_at) as last_transaction
            FROM payment_transactions
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getRecentTransactions() {
        return $this->db->query("
            SELECT
                pt.*,
                c.first_name,
                c.last_name,
                c.email,
                p.name as product_name
            FROM payment_transactions pt
            LEFT JOIN customers c ON pt.customer_id = c.id
            LEFT JOIN products p ON pt.product_id = p.id
            WHERE pt.company_id = ?
            ORDER BY pt.created_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getSubscriptionOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_subscriptions,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_subscriptions,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_subscriptions,
                COUNT(CASE WHEN status = 'past_due' THEN 1 END) as past_due_subscriptions,
                SUM(recurring_amount) as total_recurring_revenue,
                AVG(recurring_amount) as avg_subscription_value
            FROM subscriptions
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRevenueAnalytics() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('month', created_at) as month,
                SUM(amount) as monthly_revenue,
                COUNT(*) as transaction_count,
                COUNT(DISTINCT customer_id) as unique_customers
            FROM payment_transactions
            WHERE company_id = ? AND status = 'completed'
                AND created_at >= ?
            GROUP BY DATE_TRUNC('month', created_at)
            ORDER BY month DESC
            LIMIT 12
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 months'))
        ]);
    }

    private function getPaymentMethods() {
        return [
            'card' => 'Credit/Debit Card',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Bank Transfer',
            'crypto' => 'Cryptocurrency',
            'digital_wallet' => 'Digital Wallet'
        ];
    }

    private function getProducts() {
        return $this->db->query("
            SELECT * FROM products
            WHERE company_id = ? AND is_active = true
            ORDER BY name ASC
        ", [$this->user['company_id']]);
    }

    private function getCustomers() {
        return $this->db->query("
            SELECT
                id,
                first_name,
                last_name,
                email,
                company_name
            FROM customers
            WHERE company_id = ?
            ORDER BY first_name, last_name ASC
        ", [$this->user['company_id']]);
    }

    private function getSupportedCurrencies() {
        return [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'CAD' => 'Canadian Dollar',
            'AUD' => 'Australian Dollar',
            'JPY' => 'Japanese Yen'
        ];
    }

    private function processPayment() {
        $this->requirePermission('payments.process');

        $data = $this->validatePaymentData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid payment data');
            $this->redirect('/payments/process');
        }

        try {
            $this->db->beginTransaction();

            // Create payment transaction record
            $transactionId = $this->db->insert('payment_transactions', [
                'company_id' => $this->user['company_id'],
                'customer_id' => $data['customer_id'],
                'product_id' => $data['product_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'payment_method' => $data['payment_method'],
                'status' => 'pending',
                'description' => $data['description'],
                'metadata' => json_encode($data['metadata'] ?? []),
                'created_by' => $this->user['id']
            ]);

            // Process payment with Paddle
            $paddleResult = $this->processWithPaddle($data, $transactionId);

            if ($paddleResult['success']) {
                // Update transaction status
                $this->db->update('payment_transactions', [
                    'status' => 'completed',
                    'transaction_id' => $paddleResult['transaction_id'],
                    'processed_at' => date('Y-m-d H:i:s'),
                    'response_data' => json_encode($paddleResult)
                ], 'id = ?', [$transactionId]);

                // Create invoice if needed
                if ($data['create_invoice']) {
                    $this->createInvoice($transactionId, $data);
                }

                $this->db->commit();

                $this->setFlash('success', 'Payment processed successfully');
                $this->redirect('/payments/transactions');
            } else {
                // Update transaction with failure
                $this->db->update('payment_transactions', [
                    'status' => 'failed',
                    'error_message' => $paddleResult['error'],
                    'response_data' => json_encode($paddleResult)
                ], 'id = ?', [$transactionId]);

                $this->db->rollback();

                $this->setFlash('error', 'Payment processing failed: ' . $paddleResult['error']);
                $this->redirect('/payments/process');
            }

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Payment processing error: ' . $e->getMessage());
            $this->redirect('/payments/process');
        }
    }

    private function validatePaymentData($data) {
        if (empty($data['customer_id']) || empty($data['amount']) || empty($data['payment_method'])) {
            return false;
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            return false;
        }

        return [
            'customer_id' => $data['customer_id'],
            'product_id' => $data['product_id'] ?? null,
            'amount' => (float)$data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'payment_method' => $data['payment_method'],
            'description' => $data['description'] ?? '',
            'create_invoice' => isset($data['create_invoice']),
            'metadata' => $data['metadata'] ?? []
        ];
    }

    private function processWithPaddle($data, $transactionId) {
        // Implementation for Paddle payment processing
        // This would integrate with Paddle's API

        $config = $this->paddleConfig;

        if (!$config) {
            return [
                'success' => false,
                'error' => 'Paddle configuration not found'
            ];
        }

        // Simulate Paddle API call
        $payload = [
            'amount' => $data['amount'] * 100, // Convert to cents
            'currency' => $data['currency'],
            'payment_method' => $data['payment_method'],
            'customer_id' => $data['customer_id'],
            'description' => $data['description']
        ];

        // Make API request to Paddle
        $response = $this->makePaddleAPIRequest('POST', '/transactions', $payload, $config);

        if ($response && isset($response['data'])) {
            return [
                'success' => true,
                'transaction_id' => $response['data']['id'],
                'status' => $response['data']['status'],
                'response' => $response
            ];
        }

        return [
            'success' => false,
            'error' => $response['error'] ?? 'Unknown error'
        ];
    }

    private function makePaddleAPIRequest($method, $endpoint, $data, $config) {
        $url = 'https://api.paddle.com/v1' . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return ['error' => 'API request failed: ' . $error];
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 400) {
            return [
                'error' => $responseData['error']['message'] ?? 'API error',
                'code' => $httpCode
            ];
        }

        return $responseData;
    }

    private function createInvoice($transactionId, $data) {
        // Implementation for creating invoice
        return $this->db->insert('invoices', [
            'company_id' => $this->user['company_id'],
            'customer_id' => $data['customer_id'],
            'transaction_id' => $transactionId,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'paid',
            'due_date' => date('Y-m-d'),
            'created_by' => $this->user['id']
        ]);
    }

    private function getSubscriptions($filters) {
        $where = ["s.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status'] !== 'all') {
            $where[] = "s.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['customer_id']) {
            $where[] = "s.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        if ($filters['product_id']) {
            $where[] = "s.product_id = ?";
            $params[] = $filters['product_id'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                s.*,
                c.first_name,
                c.last_name,
                c.email,
                p.name as product_name,
                p.price as product_price
            FROM subscriptions s
            JOIN customers c ON s.customer_id = c.id
            JOIN products p ON s.product_id = p.id
            WHERE $whereClause
            ORDER BY s.created_at DESC
        ", $params);
    }

    private function getSubscriptionSummary($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status'] !== 'all') {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_subscriptions,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_subscriptions,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_subscriptions,
                SUM(recurring_amount) as total_recurring_revenue,
                AVG(recurring_amount) as avg_subscription_value
            FROM subscriptions
            WHERE $whereClause
        ", $params);
    }

    private function getTransactions($filters) {
        $where = ["pt.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['status'] !== 'all') {
            $where[] = "pt.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['type'] !== 'all') {
            $where[] = "pt.transaction_type = ?";
            $params[] = $filters['type'];
        }

        if ($filters['customer_id']) {
            $where[] = "pt.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        if ($filters['date_from']) {
            $where[] = "pt.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "pt.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                pt.*,
                c.first_name,
                c.last_name,
                c.email,
                p.name as product_name
            FROM payment_transactions pt
            LEFT JOIN customers c ON pt.customer_id = c.id
            LEFT JOIN products p ON pt.product_id = p.id
            WHERE $whereClause
            ORDER BY pt.created_at DESC
        ", $params);
    }

    private function getTransactionSummary($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['date_from']) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_transactions,
                SUM(amount) as total_amount,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_transactions,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
                AVG(amount) as avg_transaction_amount
            FROM payment_transactions
            WHERE $whereClause
        ", $params);
    }

    private function getRefundRequests() {
        return $this->db->query("
            SELECT
                rr.*,
                pt.amount as original_amount,
                pt.currency,
                c.first_name,
                c.last_name,
                c.email
            FROM refund_requests rr
            JOIN payment_transactions pt ON rr.transaction_id = pt.id
            LEFT JOIN customers c ON pt.customer_id = c.id
            WHERE rr.company_id = ?
            ORDER BY rr.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getProcessedRefunds() {
        return $this->db->query("
            SELECT
                r.*,
                pt.amount as original_amount,
                pt.currency,
                c.first_name,
                c.last_name,
                c.email
            FROM refunds r
            JOIN payment_transactions pt ON r.transaction_id = pt.id
            LEFT JOIN customers c ON pt.customer_id = c.id
            WHERE r.company_id = ?
            ORDER BY r.processed_at DESC
        ", [$this->user['company_id']]);
    }

    private function getRefundPolicies() {
        return $this->db->query("
            SELECT * FROM refund_policies
            WHERE company_id = ? AND is_active = true
            ORDER BY created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getRefundAnalytics() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_refund_requests,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_refunds,
                COUNT(CASE WHEN status = 'processed' THEN 1 END) as processed_refunds,
                SUM(amount) as total_refund_amount,
                AVG(amount) as avg_refund_amount
            FROM refund_requests
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getInvoices() {
        return $this->db->query("
            SELECT
                i.*,
                c.first_name,
                c.last_name,
                c.email,
                pt.transaction_id as payment_transaction_id
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            LEFT JOIN payment_transactions pt ON i.transaction_id = pt.id
            WHERE i.company_id = ?
            ORDER BY i.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getBillingCycles() {
        return [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semi-annual' => 'Semi-Annual',
            'annual' => 'Annual'
        ];
    }

    private function getTaxSettings() {
        return $this->db->query("
            SELECT * FROM tax_settings
            WHERE company_id = ?
            ORDER BY country_code, region
        ", [$this->user['company_id']]);
    }

    private function getBillingHistory() {
        return $this->db->query("
            SELECT
                bh.*,
                c.first_name,
                c.last_name,
                c.email,
                i.invoice_number
            FROM billing_history bh
            LEFT JOIN customers c ON bh.customer_id = c.id
            LEFT JOIN invoices i ON bh.invoice_id = i.id
            WHERE bh.company_id = ?
            ORDER BY bh.billing_date DESC
        ", [$this->user['company_id']]);
    }

    private function getWebhookEvents() {
        return $this->db->query("
            SELECT
                pwe.*,
                COUNT(pwl.id) as delivery_count,
                MAX(pwl.delivered_at) as last_delivery
            FROM payment_webhook_events pwe
            LEFT JOIN payment_webhook_logs pwl ON pwe.id = pwl.event_id
            WHERE pwe.company_id = ?
            GROUP BY pwe.id
            ORDER BY pwe.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getWebhookLogs() {
        return $this->db->query("
            SELECT
                pwl.*,
                pwe.event_type,
                pwe.event_data
            FROM payment_webhook_logs pwl
            JOIN payment_webhook_events pwe ON pwl.event_id = pwe.id
            WHERE pwl.company_id = ?
            ORDER BY pwl.delivered_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getWebhookSettings() {
        return $this->db->querySingle("
            SELECT * FROM payment_webhook_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getEventHandlers() {
        return [
            'payment.succeeded' => 'Payment Succeeded',
            'payment.failed' => 'Payment Failed',
            'subscription.created' => 'Subscription Created',
            'subscription.updated' => 'Subscription Updated',
            'subscription.cancelled' => 'Subscription Cancelled',
            'invoice.created' => 'Invoice Created',
            'invoice.paid' => 'Invoice Paid',
            'refund.processed' => 'Refund Processed'
        ];
    }

    private function getRevenueTrends() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('month', created_at) as month,
                SUM(amount) as revenue,
                COUNT(*) as transactions,
                COUNT(DISTINCT customer_id) as customers
            FROM payment_transactions
            WHERE company_id = ? AND status = 'completed'
                AND created_at >= ?
            GROUP BY DATE_TRUNC('month', created_at)
            ORDER BY month DESC
            LIMIT 12
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 months'))
        ]);
    }

    private function getConversionRates() {
        return $this->db->querySingle("
            SELECT
                COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / COUNT(*) as overall_conversion_rate,
                COUNT(CASE WHEN payment_method = 'card' AND status = 'completed' THEN 1 END) * 100.0 /
                    COUNT(CASE WHEN payment_method = 'card' THEN 1 END) as card_conversion_rate,
                COUNT(CASE WHEN payment_method = 'paypal' AND status = 'completed' THEN 1 END) * 100.0 /
                    COUNT(CASE WHEN payment_method = 'paypal' THEN 1 END) as paypal_conversion_rate
            FROM payment_transactions
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getCustomerLifetimeValue() {
        return $this->db->query("
            SELECT
                c.id,
                c.first_name,
                c.last_name,
                c.email,
                SUM(pt.amount) as total_spent,
                COUNT(pt.id) as total_purchases,
                AVG(pt.amount) as avg_order_value,
                MAX(pt.created_at) as last_purchase,
                MIN(pt.created_at) as first_purchase
            FROM customers c
            LEFT JOIN payment_transactions pt ON c.id = pt.customer_id
                AND pt.status = 'completed'
            WHERE c.company_id = ?
            GROUP BY c.id, c.first_name, c.last_name, c.email
            ORDER BY total_spent DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getPaymentMethodUsage() {
        return $this->db->query("
            SELECT
                payment_method,
                COUNT(*) as usage_count,
                SUM(amount) as total_amount,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_count,
                ROUND(
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / COUNT(*), 2
                ) as success_rate
            FROM payment_transactions
            WHERE company_id = ? AND created_at >= ?
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getGeographicRevenue() {
        return $this->db->query("
            SELECT
                c.country,
                COUNT(*) as transactions,
                SUM(pt.amount) as revenue,
                COUNT(DISTINCT pt.customer_id) as customers
            FROM payment_transactions pt
            JOIN customers c ON pt.customer_id = c.id
            WHERE pt.company_id = ? AND pt.status = 'completed'
                AND pt.created_at >= ?
            GROUP BY c.country
            ORDER BY revenue DESC
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    // ============================================================================
    // WEBHOOK HANDLERS
    // ============================================================================

    public function handlePaddleWebhook() {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

        // Verify webhook signature
        if (!$this->verifyPaddleWebhook($payload, $signature)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            return;
        }

        $data = json_decode($payload, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            return;
        }

        try {
            // Log webhook event
            $eventId = $this->db->insert('payment_webhook_events', [
                'company_id' => $this->user['company_id'],
                'event_type' => $data['event_type'],
                'event_data' => json_encode($data),
                'processed' => false
            ]);

            // Process webhook based on event type
            $this->processPaddleWebhookEvent($data, $eventId);

            // Mark as processed
            $this->db->update('payment_webhook_events', [
                'processed' => true,
                'processed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$eventId]);

            // Log successful processing
            $this->db->insert('payment_webhook_logs', [
                'company_id' => $this->user['company_id'],
                'event_id' => $eventId,
                'status' => 'success',
                'delivered_at' => date('Y-m-d H:i:s')
            ]);

            http_response_code(200);
            echo json_encode(['status' => 'success']);

        } catch (Exception $e) {
            // Log error
            $this->db->insert('payment_webhook_logs', [
                'company_id' => $this->user['company_id'],
                'event_id' => $eventId ?? null,
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'delivered_at' => date('Y-m-d H:i:s')
            ]);

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function verifyPaddleWebhook($payload, $signature) {
        $config = $this->paddleConfig;

        if (!$config || !isset($config['webhook_secret'])) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $config['webhook_secret']);

        return hash_equals($expectedSignature, $signature);
    }

    private function processPaddleWebhookEvent($data, $eventId) {
        switch ($data['event_type']) {
            case 'payment.succeeded':
                $this->handlePaymentSucceeded($data['data']);
                break;
            case 'payment.failed':
                $this->handlePaymentFailed($data['data']);
                break;
            case 'subscription.created':
                $this->handleSubscriptionCreated($data['data']);
                break;
            case 'subscription.updated':
                $this->handleSubscriptionUpdated($data['data']);
                break;
            case 'subscription.cancelled':
                $this->handleSubscriptionCancelled($data['data']);
                break;
            case 'invoice.created':
                $this->handleInvoiceCreated($data['data']);
                break;
            case 'invoice.paid':
                $this->handleInvoicePaid($data['data']);
                break;
            default:
                // Log unknown event type
                error_log("Unknown Paddle webhook event: " . $data['event_type']);
        }
    }

    private function handlePaymentSucceeded($data) {
        // Update transaction status
        $this->db->update('payment_transactions', [
            'status' => 'completed',
            'transaction_id' => $data['id'],
            'processed_at' => date('Y-m-d H:i:s'),
            'response_data' => json_encode($data)
        ], 'transaction_id = ? AND company_id = ?', [
            $data['id'],
            $this->user['company_id']
        ]);

        // Trigger any post-payment actions
        $this->triggerPostPaymentActions($data);
    }

    private function handlePaymentFailed($data) {
        // Update transaction status
        $this->db->update('payment_transactions', [
            'status' => 'failed',
            'error_message' => $data['failure_reason'] ?? 'Payment failed',
            'response_data' => json_encode($data)
        ], 'transaction_id = ? AND company_id = ?', [
            $data['id'],
            $this->user['company_id']
        ]);
    }

    private function handleSubscriptionCreated($data) {
        $this->db->insert('subscriptions', [
            'company_id' => $this->user['company_id'],
            'customer_id' => $this->findCustomerByPaddleId($data['customer_id']),
            'product_id' => $this->findProductByPaddleId($data['product_id']),
            'paddle_subscription_id' => $data['id'],
            'status' => 'active',
            'recurring_amount' => $data['unit_price'] / 100, // Convert from cents
            'currency' => $data['currency'],
            'billing_cycle' => $data['billing_cycle'],
            'next_billing_date' => $data['next_billed_at'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function handleSubscriptionUpdated($data) {
        $this->db->update('subscriptions', [
            'status' => $data['status'],
            'recurring_amount' => $data['unit_price'] / 100,
            'next_billing_date' => $data['next_billed_at'],
            'updated_at' => date('Y-m-d H:i:s')
        ], 'paddle_subscription_id = ? AND company_id = ?', [
            $data['id'],
            $this->user['company_id']
        ]);
    }

    private function handleSubscriptionCancelled($data) {
        $this->db->update('subscriptions', [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s')
        ], 'paddle_subscription_id = ? AND company_id = ?', [
            $data['id'],
            $this->user['company_id']
        ]);
    }

    private function handleInvoiceCreated($data) {
        $this->db->insert('invoices', [
            'company_id' => $this->user['company_id'],
            'customer_id' => $this->findCustomerByPaddleId($data['customer_id']),
            'paddle_invoice_id' => $data['id'],
            'amount' => $data['total'] / 100,
            'currency' => $data['currency'],
            'status' => $data['status'],
            'due_date' => $data['due_date'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function handleInvoicePaid($data) {
        $this->db->update('invoices', [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s')
        ], 'paddle_invoice_id = ? AND company_id = ?', [
            $data['id'],
            $this->user['company_id']
        ]);
    }

    private function findCustomerByPaddleId($paddleCustomerId) {
        $customer = $this->db->querySingle("
            SELECT id FROM customers
            WHERE paddle_customer_id = ? AND company_id = ?
        ", [$paddleCustomerId, $this->user['company_id']]);

        return $customer ? $customer['id'] : null;
    }

    private function findProductByPaddleId($paddleProductId) {
        $product = $this->db->querySingle("
            SELECT id FROM products
            WHERE paddle_product_id = ? AND company_id = ?
        ", [$paddleProductId, $this->user['company_id']]);

        return $product ? $product['id'] : null;
    }

    private function triggerPostPaymentActions($data) {
        // Implementation for post-payment actions
        // This could include sending emails, updating inventory, etc.
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function createSubscription() {
        $this->requirePermission('payments.subscriptions.create');

        $data = $this->validateSubscriptionData($_POST);

        if (!$data) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid subscription data'], 400);
        }

        try {
            // Create subscription with Paddle
            $paddleResult = $this->createPaddleSubscription($data);

            if ($paddleResult['success']) {
                // Save subscription locally
                $subscriptionId = $this->db->insert('subscriptions', [
                    'company_id' => $this->user['company_id'],
                    'customer_id' => $data['customer_id'],
                    'product_id' => $data['product_id'],
                    'paddle_subscription_id' => $paddleResult['subscription_id'],
                    'status' => 'active',
                    'recurring_amount' => $data['amount'],
                    'currency' => $data['currency'],
                    'billing_cycle' => $data['billing_cycle'],
                    'next_billing_date' => $paddleResult['next_billing_date'],
                    'created_by' => $this->user['id']
                ]);

                $this->jsonResponse([
                    'success' => true,
                    'subscription_id' => $subscriptionId,
                    'paddle_subscription_id' => $paddleResult['subscription_id']
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => $paddleResult['error']
                ], 400);
            }

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function validateSubscriptionData($data) {
        if (empty($data['customer_id']) || empty($data['product_id']) || empty($data['amount'])) {
            return false;
        }

        return [
            'customer_id' => $data['customer_id'],
            'product_id' => $data['product_id'],
            'amount' => (float)$data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'billing_cycle' => $data['billing_cycle'] ?? 'monthly'
        ];
    }

    private function createPaddleSubscription($data) {
        $config = $this->paddleConfig;

        if (!$config) {
            return [
                'success' => false,
                'error' => 'Paddle configuration not found'
            ];
        }

        $payload = [
            'customer_id' => $this->getPaddleCustomerId($data['customer_id']),
            'product_id' => $this->getPaddleProductId($data['product_id']),
            'price' => [
                'amount' => $data['amount'] * 100, // Convert to cents
                'currency' => $data['currency']
            ],
            'billing_cycle' => $data['billing_cycle']
        ];

        $response = $this->makePaddleAPIRequest('POST', '/subscriptions', $payload, $config);

        if ($response && isset($response['data'])) {
            return [
                'success' => true,
                'subscription_id' => $response['data']['id'],
                'next_billing_date' => $response['data']['next_billed_at']
            ];
        }

        return [
            'success' => false,
            'error' => $response['error'] ?? 'Unknown error'
        ];
    }

    private function getPaddleCustomerId($customerId) {
        $customer = $this->db->querySingle("
            SELECT paddle_customer_id FROM customers
            WHERE id = ? AND company_id = ?
        ", [$customerId, $this->user['company_id']]);

        return $customer ? $customer['paddle_customer_id'] : null;
    }

    private function getPaddleProductId($productId) {
        $product = $this->db->querySingle("
            SELECT paddle_product_id FROM products
            WHERE id = ? AND company_id = ?
        ", [$productId, $this->user['company_id']]);

        return $product ? $product['paddle_product_id'] : null;
    }

    public function processRefund() {
        $this->requirePermission('payments.refunds.process');

        $data = $this->validateRefundData($_POST);

        if (!$data) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid refund data'], 400);
        }

        try {
            // Process refund with Paddle
            $paddleResult = $this->processPaddleRefund($data);

            if ($paddleResult['success']) {
                // Save refund locally
                $refundId = $this->db->insert('refunds', [
                    'company_id' => $this->user['company_id'],
                    'transaction_id' => $data['transaction_id'],
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
                    'reason' => $data['reason'],
                    'paddle_refund_id' => $paddleResult['refund_id'],
                    'status' => 'processed',
                    'processed_at' => date('Y-m-d H:i:s'),
                    'processed_by' => $this->user['id']
                ]);

                // Update original transaction
                $this->db->update('payment_transactions', [
                    'refunded_amount' => $data['amount'],
                    'refunded_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$data['transaction_id']]);

                $this->jsonResponse([
                    'success' => true,
                    'refund_id' => $refundId,
                    'paddle_refund_id' => $paddleResult['refund_id']
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => $paddleResult['error']
                ], 400);
            }

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function validateRefundData($data) {
        if (empty($data['transaction_id']) || empty($data['amount'])) {
            return false;
        }

        return [
            'transaction_id' => $data['transaction_id'],
            'amount' => (float)$data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'reason' => $data['reason'] ?? ''
        ];
    }

    private function processPaddleRefund($data) {
        $config = $this->paddleConfig;

        if (!$config) {
            return [
                'success' => false,
                'error' => 'Paddle configuration not found'
            ];
        }

        // Get transaction details
        $transaction = $this->db->querySingle("
            SELECT * FROM payment_transactions
            WHERE id = ? AND company_id = ?
        ", [$data['transaction_id'], $this->user['company_id']]);

        if (!$transaction) {
            return [
                'success' => false,
                'error' => 'Transaction not found'
            ];
        }

        $payload = [
            'transaction_id' => $transaction['transaction_id'],
            'amount' => $data['amount'] * 100, // Convert to cents
            'reason' => $data['reason']
        ];

        $response = $this->makePaddleAPIRequest('POST', '/refunds', $payload, $config);

        if ($response && isset($response['data'])) {
            return [
                'success' => true,
                'refund_id' => $response['data']['id']
            ];
        }

        return [
            'success' => false,
            'error' => $response['error'] ?? 'Unknown error'
        ];
    }
}
?>
