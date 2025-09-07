<?php

namespace TPT\ERP\Core;

/**
 * Magic Link Authentication Manager
 *
 * Handles passwordless authentication via email magic links.
 */
class MagicLinkManager
{
    private Database $db;
    private Email $email;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->email = new Email();
    }

    /**
     * Send magic link to user
     */
    public function sendMagicLink(string $email, string $redirectUrl = null): bool
    {
        // Find user by email
        $user = $this->db->queryOne(
            "SELECT id, email, first_name, last_name, is_active, is_verified FROM users WHERE email = ?",
            [$email]
        );

        if (!$user) {
            // Don't reveal if email exists or not for security
            return true;
        }

        // Check if user is active and verified
        if (!$user['is_active'] || !$user['is_verified']) {
            return true; // Don't reveal account status
        }

        // Generate secure token
        $token = $this->generateSecureToken();
        $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutes

        // Store token in database
        $this->db->insert('magic_link_tokens', [
            'user_id' => $user['id'],
            'token_hash' => password_hash($token, PASSWORD_DEFAULT),
            'expires_at' => $expiresAt,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Generate magic link URL
        $baseUrl = getenv('APP_URL') ?: 'http://localhost:8080';
        $magicLink = $baseUrl . '/auth/magic-link/verify?token=' . $token;

        if ($redirectUrl) {
            $magicLink .= '&redirect=' . urlencode($redirectUrl);
        }

        // Send email with magic link
        $templateData = [
            'subject' => 'Your Magic Link - TPT ERP',
            'name' => trim($user['first_name'] . ' ' . $user['last_name']),
            'magic_link' => $magicLink,
            'expires_in' => '15 minutes',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ];

        return $this->email->sendTemplate($user['email'], 'magic_link', $templateData);
    }

    /**
     * Verify magic link token
     */
    public function verifyMagicLink(string $token, string $ipAddress = null, string $userAgent = null): ?array
    {
        // Find token in database
        $tokenRecord = $this->db->queryOne(
            "SELECT id, user_id, expires_at FROM magic_link_tokens WHERE token_hash IS NOT NULL AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1"
        );

        if (!$tokenRecord) {
            return null;
        }

        // Verify token
        if (!password_verify($token, $tokenRecord['token_hash'])) {
            // Log failed attempt
            $this->logFailedMagicLink($tokenRecord['user_id'], $ipAddress, $userAgent);
            return null;
        }

        // Check if token is expired
        if (strtotime($tokenRecord['expires_at']) < time()) {
            return null;
        }

        // Get user data
        $user = $this->db->find('users', $tokenRecord['user_id']);

        if (!$user || !$user['is_active'] || !$user['is_verified']) {
            return null;
        }

        // Mark token as used
        $this->db->update('magic_link_tokens', [
            'used_at' => date('Y-m-d H:i:s')
        ], ['id' => $tokenRecord['id']]);

        // Log successful authentication
        $this->logAuthEvent($user['id'], 'magic_link_login', 'Successful magic link authentication');

        return $user;
    }

    /**
     * Enable magic link authentication for user
     */
    public function enableMagicLink(int $userId): bool
    {
        $db = Database::getInstance();

        $result = $db->insert('user_auth_methods', [
            'user_id' => $userId,
            'method_type' => 'magic_link',
            'method_data' => json_encode([
                'enabled_at' => date('Y-m-d H:i:s'),
                'email_verified' => true
            ]),
            'is_enabled' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            $this->logAuthEvent($userId, 'magic_link_enabled', 'Magic link authentication enabled');
        }

        return $result > 0;
    }

    /**
     * Disable magic link authentication for user
     */
    public function disableMagicLink(int $userId): bool
    {
        $db = Database::getInstance();

        $result = $db->execute(
            "UPDATE user_auth_methods SET is_enabled = false WHERE user_id = ? AND method_type = 'magic_link'",
            [$userId]
        );

        if ($result > 0) {
            $this->logAuthEvent($userId, 'magic_link_disabled', 'Magic link authentication disabled');
        }

        return $result > 0;
    }

    /**
     * Check if magic link is enabled for user
     */
    public function isMagicLinkEnabled(int $userId): bool
    {
        $db = Database::getInstance();

        $method = $db->queryOne(
            "SELECT id FROM user_auth_methods WHERE user_id = ? AND method_type = 'magic_link' AND is_enabled = true",
            [$userId]
        );

        return $method !== null;
    }

    /**
     * Generate secure random token
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Clean expired magic link tokens
     */
    public function cleanExpiredTokens(): int
    {
        return $this->db->execute(
            "DELETE FROM magic_link_tokens WHERE expires_at < NOW() OR used_at IS NOT NULL"
        );
    }

    /**
     * Get user's magic link authentication status
     */
    public function getMagicLinkStatus(int $userId): array
    {
        return [
            'enabled' => $this->isMagicLinkEnabled($userId),
            'recent_tokens' => $this->getRecentTokens($userId),
            'last_used' => $this->getLastUsed($userId)
        ];
    }

    /**
     * Get recent magic link tokens for user
     */
    private function getRecentTokens(int $userId): array
    {
        return $this->db->query(
            "SELECT created_at, used_at, ip_address FROM magic_link_tokens
             WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY created_at DESC LIMIT 10",
            [$userId]
        );
    }

    /**
     * Get last used timestamp for magic link
     */
    private function getLastUsed(int $userId): ?string
    {
        $result = $this->db->queryOne(
            "SELECT used_at FROM magic_link_tokens
             WHERE user_id = ? AND used_at IS NOT NULL
             ORDER BY used_at DESC LIMIT 1",
            [$userId]
        );

        return $result ? $result['used_at'] : null;
    }

    /**
     * Send magic link for password reset
     */
    public function sendPasswordResetMagicLink(string $email): bool
    {
        // Find user by email
        $user = $this->db->queryOne(
            "SELECT id, email, first_name, last_name FROM users WHERE email = ? AND is_active = true",
            [$email]
        );

        if (!$user) {
            return true; // Don't reveal if email exists
        }

        // Generate secure token
        $token = $this->generateSecureToken();
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        // Store token
        $this->db->insert('magic_link_tokens', [
            'user_id' => $user['id'],
            'token_hash' => password_hash($token, PASSWORD_DEFAULT),
            'expires_at' => $expiresAt,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Generate password reset link
        $baseUrl = getenv('APP_URL') ?: 'http://localhost:8080';
        $resetLink = $baseUrl . '/auth/reset-password?token=' . $token;

        // Send email
        $templateData = [
            'subject' => 'Password Reset - TPT ERP',
            'name' => trim($user['first_name'] . ' ' . $user['last_name']),
            'reset_link' => $resetLink,
            'expires_in' => '1 hour'
        ];

        return $this->email->sendTemplate($user['email'], 'password_reset_magic', $templateData);
    }

    /**
     * Verify password reset magic link
     */
    public function verifyPasswordResetLink(string $token): ?array
    {
        // Find token
        $tokenRecord = $this->db->queryOne(
            "SELECT id, user_id, expires_at FROM magic_link_tokens
             WHERE token_hash IS NOT NULL AND expires_at > NOW()
             AND used_at IS NULL ORDER BY created_at DESC LIMIT 1"
        );

        if (!$tokenRecord) {
            return null;
        }

        // Verify token
        if (!password_verify($token, $tokenRecord['token_hash'])) {
            return null;
        }

        // Check expiration
        if (strtotime($tokenRecord['expires_at']) < time()) {
            return null;
        }

        // Get user
        $user = $this->db->find('users', $tokenRecord['user_id']);

        if (!$user || !$user['is_active']) {
            return null;
        }

        // Mark token as used
        $this->db->update('magic_link_tokens', [
            'used_at' => date('Y-m-d H:i:s')
        ], ['id' => $tokenRecord['id']]);

        return $user;
    }

    /**
     * Log failed magic link attempt
     */
    private function logFailedMagicLink(int $userId, string $ipAddress = null, string $userAgent = null): void
    {
        $this->db->insert('audit_log', [
            'user_id' => $userId,
            'action' => 'magic_link_failed',
            'table_name' => 'magic_link_tokens',
            'record_id' => $userId,
            'description' => 'Failed magic link authentication attempt',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log authentication event
     */
    private function logAuthEvent(int $userId, string $event, string $description): void
    {
        $this->db->insert('audit_log', [
            'user_id' => $userId,
            'action' => $event,
            'table_name' => 'user_auth_methods',
            'record_id' => $userId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get magic link statistics
     */
    public function getMagicLinkStats(): array
    {
        $stats = [
            'total_tokens' => $this->db->count('magic_link_tokens'),
            'used_tokens' => $this->db->queryValue(
                "SELECT COUNT(*) FROM magic_link_tokens WHERE used_at IS NOT NULL"
            ),
            'expired_tokens' => $this->db->queryValue(
                "SELECT COUNT(*) FROM magic_link_tokens WHERE expires_at < NOW() AND used_at IS NULL"
            ),
            'active_users' => $this->db->queryValue(
                "SELECT COUNT(DISTINCT user_id) FROM user_auth_methods WHERE method_type = 'magic_link' AND is_enabled = true"
            )
        ];

        $stats['success_rate'] = $stats['total_tokens'] > 0
            ? round(($stats['used_tokens'] / $stats['total_tokens']) * 100, 2)
            : 0;

        return $stats;
    }
}
