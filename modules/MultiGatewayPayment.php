<?php
/**
 * TPT Free ERP - Multi-Gateway Payment Module
 * Complete payment processing with multiple gateways including cryptocurrency
 */

class MultiGatewayPayment extends BaseController {
    private $db;
    private $user;
    private $supportedGateways;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->supportedGateways = $this->getSupportedGateways();
    }

    /**
     * Main payment gateway dashboard
     */
    public function index() {
        $this->requirePermission('payments.view');

        $data = array(
            'title' => 'Multi-Gateway Payment Management',
            'payment_stats' => $this->getPaymentStats(),
            'gateway_status' => $this->getGatewayStatus(),
            'recent_transactions' => $this->getRecentTransactions(),
            'supported_gateways' => $this->supportedGateways,
            'payment_analytics' => $this->getPaymentAnalytics()
        );

        $this->render('modules/payments/dashboard', $data);
    }

    /**
     * Process payment with selected gateway
     */
    public function processPayment() {
        $this->requirePermission('payments.process');

        $data = $this->validatePaymentData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid payment data');
            $this->redirect('/payments/process');
        }

        $gateway = isset($_POST['gateway']) ? $_POST['gateway'] : 'paddle';

        if (!isset($this->supportedGateways[$gateway])) {
            $this->setFlash('error', 'Unsupported payment gateway');
            $this->redirect('/payments/process');
        }

        try {
            $this->db->beginTransaction();

            // Create payment transaction record
            $transactionId = $this->db->insert('payment_transactions', array(
                'company_id' => $this->user['company_id'],
                'customer_id' => $data['customer_id'],
                'product_id' => $data['product_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'payment_method' => $gateway,
                'status' => 'pending',
                'description' => $data['description'],
                'metadata' => json_encode($data['metadata'] ?? array()),
                'created_by' => $this->user['id']
            ));

            // Process payment with selected gateway
            $result = $this->processWithGateway($gateway, $data, $transactionId);

            if ($result['success']) {
                // Update transaction status
                $this->db->update('payment_transactions', array(
                    'status' => 'completed',
                    'transaction_id' => $result['transaction_id'],
                    'processed_at' => date('Y-m-d H:i:s'),
                    'response_data' => json_encode($result)
                ), 'id = ?', array($transactionId));

                $this->db->commit();

                $this->setFlash('success', 'Payment processed successfully');
                $this->redirect('/payments/transactions');
            } else {
                // Update transaction with failure
                $this->db->update('payment_transactions', array(
                    'status' => 'failed',
                    'error_message' => $result['error'],
                    'response_data' => json_encode($result)
                ), 'id = ?', array($transactionId));

                $this->db->rollback();

                $this->setFlash('error', 'Payment processing failed: ' . $result['error']);
                $this->redirect('/payments/process');
            }

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Payment processing error: ' . $e->getMessage());
            $this->redirect('/payments/process');
        }
    }

    /**
     * Process cryptocurrency payment
     */
    public function processCryptoPayment() {
        $this->requirePermission('payments.crypto.process');

        $data = $this->validateCryptoPaymentData($_POST);

        if (!$data) {
            $this->setFlash('error', 'Invalid cryptocurrency payment data');
            $this->redirect('/payments/crypto');
        }

        try {
            $this->db->beginTransaction();

            // Create crypto transaction record
            $transactionId = $this->db->insert('crypto_transactions', array(
                'company_id' => $this->user['company_id'],
                'customer_id' => $data['customer_id'],
                'cryptocurrency' => $data['cryptocurrency'],
                'amount' => $data['amount'],
                'fiat_amount' => $data['fiat_amount'],
                'fiat_currency' => $data['fiat_currency'],
                'wallet_address' => $this->generateWalletAddress($data['cryptocurrency']),
                'status' => 'pending',
                'description' => $data['description'],
                'exchange_rate' => $data['exchange_rate'],
                'created_by' => $this->user['id']
            ));

            // Generate payment QR code
            $qrCode = $this->generateCryptoQRCode($transactionId, $data);

            // Update transaction with QR code
            $this->db->update('crypto_transactions', array(
                'qr_code' => $qrCode,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ), 'id = ?', array($transactionId));

            $this->db->commit();

            $this->setFlash('success', 'Cryptocurrency payment initiated. Please complete the transaction.');
            $this->redirect('/payments/crypto/status/' . $transactionId);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Cryptocurrency payment error: ' . $e->getMessage());
            $this->redirect('/payments/crypto');
        }
    }

    /**
     * Get supported payment gateways
     */
    private function getSupportedGateways() {
        return array(
            'paddle' => array(
                'name' => 'Paddle',
                'type' => 'subscription',
                'currencies' => array('USD', 'EUR', 'GBP'),
                'features' => array('subscriptions', 'one-time', 'refunds'),
                'status' => 'active'
            ),
            'gocardless' => array(
                'name' => 'GoCardless',
                'type' => 'direct_debit',
                'currencies' => array('GBP', 'EUR', 'SEK', 'DKK', 'AUD'),
                'features' => array('direct_debit', 'recurring'),
                'status' => 'active'
            ),
            'stripe' => array(
                'name' => 'Stripe',
                'type' => 'card',
                'currencies' => array('USD', 'EUR', 'GBP', 'CAD', 'AUD'),
                'features' => array('cards', 'digital_wallets', 'subscriptions'),
                'status' => 'active'
            ),
            'paypal' => array(
                'name' => 'PayPal',
                'type' => 'digital_wallet',
                'currencies' => array('USD', 'EUR', 'GBP', 'CAD', 'AUD'),
                'features' => array('digital_wallet', 'cards', 'recurring'),
                'status' => 'active'
            ),
            'square' => array(
                'name' => 'Square',
                'type' => 'pos',
                'currencies' => array('USD', 'CAD', 'GBP', 'JPY', 'AUD'),
                'features' => array('cards', 'digital_wallets', 'in-person'),
                'status' => 'active'
            ),
            'bitcoin' => array(
                'name' => 'Bitcoin',
                'type' => 'cryptocurrency',
                'currencies' => array('BTC'),
                'features' => array('crypto_wallet', 'qr_codes'),
                'status' => 'active'
            ),
            'ethereum' => array(
                'name' => 'Ethereum',
                'type' => 'cryptocurrency',
                'currencies' => array('ETH'),
                'features' => array('crypto_wallet', 'smart_contracts'),
                'status' => 'active'
            ),
            'usdc' => array(
                'name' => 'USD Coin',
                'type' => 'stablecoin',
                'currencies' => array('USDC'),
                'features' => array('stablecoin', 'fast_settlement'),
                'status' => 'active'
            ),
            'usdt' => array(
                'name' => 'Tether',
                'type' => 'stablecoin',
                'currencies' => array('USDT'),
                'features' => array('stablecoin', 'multi_chain'),
                'status' => 'active'
            )
        );
    }

    /**
     * Process payment with specific gateway
     */
    private function processWithGateway($gateway, $data, $transactionId) {
        switch ($gateway) {
            case 'paddle':
                return $this->processWithPaddle($data, $transactionId);
            case 'gocardless':
                return $this->processWithGoCardless($data, $transactionId);
            case 'stripe':
                return $this->processWithStripe($data, $transactionId);
            case 'paypal':
                return $this->processWithPayPal($data, $transactionId);
            case 'square':
                return $this->processWithSquare($data, $transactionId);
            default:
                return array('success' => false, 'error' => 'Unsupported gateway');
        }
    }

    /**
     * Process payment with Paddle
     */
    private function processWithPaddle($data, $transactionId) {
        $config = $this->getGatewayConfig('paddle');

        if (!$config) {
            return array(
                'success' => false,
                'error' => 'Paddle configuration not found'
            );
        }

        $payload = array(
            'amount' => $data['amount'] * 100, // Convert to cents
            'currency' => $data['currency'],
            'payment_method' => 'card',
            'customer_id' => $data['customer_id'],
            'description' => $data['description']
        );

        $response = $this->makeAPIRequest('POST', 'https://api.paddle.com/v1/transactions', $payload, $config);

        if ($response && isset($response['data'])) {
            return array(
                'success' => true,
                'transaction_id' => $response['data']['id'],
                'status' => $response['data']['status'],
                'response' => $response
            );
        }

        return array(
            'success' => false,
            'error' => isset($response['error']) ? $response['error']['message'] : 'Unknown error'
        );
    }

    /**
     * Process payment with GoCardless
     */
    private function processWithGoCardless($data, $transactionId) {
        $config = $this->getGatewayConfig('gocardless');

        if (!$config) {
            return array(
                'success' => false,
                'error' => 'GoCardless configuration not found'
            );
        }

        $payload = array(
            'amount' => $data['amount'] * 100, // Convert to pence
            'currency' => $data['currency'],
            'description' => $data['description'],
            'customer_id' => $data['customer_id']
        );

        $response = $this->makeAPIRequest('POST', 'https://api.gocardless.com/mandates', $payload, $config);

        if ($response && isset($response['id'])) {
            return array(
                'success' => true,
                'transaction_id' => $response['id'],
                'status' => 'pending',
                'response' => $response
            );
        }

        return array(
            'success' => false,
            'error' => isset($response['error']) ? $response['error']['message'] : 'Unknown error'
        );
    }

    /**
     * Process cryptocurrency payment
     */
    private function processCryptoPaymentInternal($data, $transactionId) {
        $cryptocurrency = $data['cryptocurrency'];

        // Generate wallet address
        $walletAddress = $this->generateWalletAddress($cryptocurrency);

        // Calculate crypto amount based on exchange rate
        $exchangeRate = $this->getExchangeRate($cryptocurrency, $data['fiat_currency']);
        $cryptoAmount = $data['fiat_amount'] / $exchangeRate;

        // Create crypto transaction record
        $cryptoTransactionId = $this->db->insert('crypto_transactions', array(
            'company_id' => $this->user['company_id'],
            'customer_id' => $data['customer_id'],
            'cryptocurrency' => $cryptocurrency,
            'amount' => $cryptoAmount,
            'fiat_amount' => $data['fiat_amount'],
            'fiat_currency' => $data['fiat_currency'],
            'wallet_address' => $walletAddress,
            'status' => 'pending',
            'description' => $data['description'],
            'exchange_rate' => $exchangeRate,
            'payment_transaction_id' => $transactionId,
            'created_by' => $this->user['id']
        ));

        return array(
            'success' => true,
            'transaction_id' => 'crypto_' . $cryptoTransactionId,
            'wallet_address' => $walletAddress,
            'crypto_amount' => $cryptoAmount,
            'exchange_rate' => $exchangeRate
        );
    }

    /**
     * Generate cryptocurrency wallet address
     */
    private function generateWalletAddress($cryptocurrency) {
        // In a real implementation, this would integrate with a crypto wallet service
        // For now, generate a mock address
        $prefixes = array(
            'bitcoin' => 'bc1',
            'ethereum' => '0x',
            'usdc' => '0x',
            'usdt' => '0x'
        );

        $prefix = isset($prefixes[$cryptocurrency]) ? $prefixes[$cryptocurrency] : '0x';
        return $prefix . substr(md5(uniqid()), 0, 32);
    }

    /**
     * Get cryptocurrency exchange rate
     */
    private function getExchangeRate($cryptocurrency, $fiatCurrency) {
        // In a real implementation, this would fetch from a crypto exchange API
        // For now, return mock rates
        $rates = array(
            'bitcoin' => array('USD' => 45000, 'EUR' => 42000),
            'ethereum' => array('USD' => 3000, 'EUR' => 2800),
            'usdc' => array('USD' => 1.00, 'EUR' => 0.95),
            'usdt' => array('USD' => 1.00, 'EUR' => 0.95)
        );

        return isset($rates[$cryptocurrency][$fiatCurrency]) ? $rates[$cryptocurrency][$fiatCurrency] : 1.00;
    }

    /**
     * Generate QR code for crypto payment
     */
    private function generateCryptoQRCode($transactionId, $data) {
        $transaction = $this->db->querySingle("SELECT * FROM crypto_transactions WHERE id = ?", array($transactionId));

        if (!$transaction) {
            return null;
        }

        // Generate QR code content based on cryptocurrency
        $qrContent = $this->generateQRContent($transaction);

        // In a real implementation, this would use a QR code library
        // For now, return a placeholder
        return 'data:image/png;base64,' . base64_encode('QR_CODE_PLACEHOLDER');
    }

    /**
     * Generate QR content for different cryptocurrencies
     */
    private function generateQRContent($transaction) {
        $crypto = $transaction['cryptocurrency'];

        switch ($crypto) {
            case 'bitcoin':
                return 'bitcoin:' . $transaction['wallet_address'] . '?amount=' . $transaction['amount'];
            case 'ethereum':
            case 'usdc':
            case 'usdt':
                return 'ethereum:' . $transaction['wallet_address'] . '@1?value=' . $this->toWei($transaction['amount']);
            default:
                return $transaction['wallet_address'];
        }
    }

    /**
     * Convert ETH amount to Wei
     */
    private function toWei($ethAmount) {
        return bcmul($ethAmount, bcpow('10', '18'));
    }

    /**
     * Get gateway configuration
     */
    private function getGatewayConfig($gateway) {
        return $this->db->querySingle("
            SELECT * FROM payment_gateway_configs
            WHERE gateway = ? AND company_id = ?
        ", array($gateway, $this->user['company_id']));
    }

    /**
     * Make API request to payment gateway
     */
    private function makeAPIRequest($method, $url, $data, $config) {
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['api_key']
        );

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
            return array('error' => 'API request failed: ' . $error);
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 400) {
            return array(
                'error' => isset($responseData['error']) ? $responseData['error']['message'] : 'API error',
                'code' => $httpCode
            );
        }

        return $responseData;
    }

    /**
     * Validate payment data
     */
    private function validatePaymentData($data) {
        if (empty($data['customer_id']) || empty($data['amount']) || empty($data['currency'])) {
            return false;
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            return false;
        }

        return array(
            'customer_id' => $data['customer_id'],
            'product_id' => isset($data['product_id']) ? $data['product_id'] : null,
            'amount' => (float)$data['amount'],
            'currency' => $data['currency'],
            'description' => isset($data['description']) ? $data['description'] : '',
            'metadata' => isset($data['metadata']) ? $data['metadata'] : array()
        );
    }

    /**
     * Validate cryptocurrency payment data
     */
    private function validateCryptoPaymentData($data) {
        if (empty($data['customer_id']) || empty($data['cryptocurrency']) || empty($data['fiat_amount'])) {
            return false;
        }

        if (!is_numeric($data['fiat_amount']) || $data['fiat_amount'] <= 0) {
            return false;
        }

        return array(
            'customer_id' => $data['customer_id'],
            'cryptocurrency' => $data['cryptocurrency'],
            'fiat_amount' => (float)$data['fiat_amount'],
            'fiat_currency' => isset($data['fiat_currency']) ? $data['fiat_currency'] : 'USD',
            'description' => isset($data['description']) ? $data['description'] : '',
            'exchange_rate' => $this->getExchangeRate($data['cryptocurrency'], $data['fiat_currency'])
        );
    }

    /**
     * Get payment statistics
     */
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
        ", array(
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
    }

    /**
     * Get gateway status
     */
    private function getGatewayStatus() {
        $status = array();
        foreach ($this->supportedGateways as $key => $gateway) {
            $config = $this->getGatewayConfig($key);
            $status[$key] = array(
                'name' => $gateway['name'],
                'configured' => $config !== null,
                'status' => $gateway['status'],
                'last_test' => isset($config['last_test']) ? $config['last_test'] : null
            );
        }
        return $status;
    }

    /**
     * Get recent transactions
     */
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
        ", array($this->user['company_id']));
    }

    /**
     * Get payment analytics
     */
    private function getPaymentAnalytics() {
        return $this->db->query("
            SELECT
                payment_method,
                COUNT(*) as transaction_count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_count,
                ROUND(
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / COUNT(*), 2
                ) as success_rate
            FROM payment_transactions
            WHERE company_id = ? AND created_at >= ?
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ", array(
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
    }
}
?>
