<?php

namespace TPT\ERP\Core;

/**
 * Session Manager
 *
 * Handles PHP session management with security features and database storage.
 */
class SessionManager
{
    private Database $db;
    private array $config;
    private string $sessionTable = 'user_sessions';

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = [
            'lifetime' => (int) (getenv('SESSION_LIFETIME') ?: 7200), // 2 hours
            'regenerate_frequency' => (int) (getenv('SESSION_REGENERATE_FREQUENCY') ?: 300), // 5 minutes
            'max_sessions_per_user' => (int) (getenv('MAX_SESSIONS_PER_USER') ?: 5),
            'cookie_name' => getenv('SESSION_COOKIE_NAME') ?: 'tpt_session',
            'cookie_secure' => getenv('SESSION_COOKIE_SECURE') ?: false,
            'cookie_httponly' => getenv('SESSION_COOKIE_HTTPONLY') ?: true,
            'cookie_samesite' => getenv('SESSION_COOKIE_SAMESITE') ?: 'Lax',
        ];

        $this->initSession();
    }

    /**
     * Initialize session configuration
     */
    private function initSession(): void
    {
        // Set session configuration
        ini_set('session.name', $this->config['cookie_name']);
        ini_set('session.cookie_secure', $this->config['cookie_secure'] ? '1' : '0');
        ini_set('session.cookie_httponly', $this->config['cookie_httponly'] ? '1' : '0');
        ini_set('session.cookie_samesite', $this->config['cookie_samesite']);
        ini_set('session.gc_maxlifetime', $this->config['lifetime']);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        // Set custom session handlers
        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc']
        );

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Open session
     */
    public function open(string $savePath, string $sessionName): bool
    {
        return true;
    }

    /**
     * Close session
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Read session data
     */
    public function read(string $sessionId): string
    {
        try {
            $session = $this->db->queryOne(
                "SELECT session_data FROM {$this->sessionTable} WHERE session_id = ? AND expires_at > NOW()",
                [$sessionId]
            );

            return $session ? $session['session_data'] : '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Write session data
     */
    public function write(string $sessionId, string $sessionData): bool
    {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + $this->config['lifetime']);

            // Check if session exists
            $existing = $this->db->queryOne(
                "SELECT id FROM {$this->sessionTable} WHERE session_id = ?",
                [$sessionId]
            );

            if ($existing) {
                // Update existing session
                return $this->db->update(
                    $this->sessionTable,
                    [
                        'session_data' => $sessionData,
                        'expires_at' => $expiresAt,
                        'last_activity' => date('Y-m-d H:i:s')
                    ],
                    ['session_id' => $sessionId]
                ) > 0;
            } else {
                // Create new session
                $userId = null;
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

                // Extract user ID from session data if available
                $data = $this->unserializeSessionData($sessionData);
                if (isset($data['user_id'])) {
                    $userId = $data['user_id'];
                }

                return $this->db->insert($this->sessionTable, [
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                    'session_data' => $sessionData,
                    'user_agent' => $userAgent,
                    'ip_address' => $ipAddress,
                    'expires_at' => $expiresAt,
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_activity' => date('Y-m-d H:i:s')
                ]) > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Destroy session
     */
    public function destroy(string $sessionId): bool
    {
        try {
            return $this->db->delete($this->sessionTable, ['session_id' => $sessionId]) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Garbage collection
     */
    public function gc(int $maxLifetime): int
    {
        try {
            return $this->db->execute(
                "DELETE FROM {$this->sessionTable} WHERE expires_at < NOW()"
            );
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Unserialize session data
     */
    private function unserializeSessionData(string $data): array
    {
        $result = [];
        $pairs = explode('|', $data);

        foreach ($pairs as $pair) {
            if (strpos($pair, ':') !== false) {
                [$key, $value] = explode(':', $pair, 2);
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Start user session
     */
    public function startUserSession(int $userId, array $userData = []): bool
    {
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Clean up old sessions for this user
        $this->cleanupUserSessions($userId);

        // Set session data
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_data'] = $userData;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Update session in database
        $this->updateSessionUser($session_id(), $userId);

        return true;
    }

    /**
     * End user session
     */
    public function endUserSession(): bool
    {
        $userId = $_SESSION['user_id'] ?? null;

        // Clear session data
        session_unset();
        session_destroy();

        // Start new session to prevent session fixation
        session_start();
        session_regenerate_id(true);

        // Log logout if user was logged in
        if ($userId) {
            $this->logUserAction($userId, 'logout', 'User logged out');
        }

        return true;
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     */
    public function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user data
     */
    public function getUserData(): ?array
    {
        return $_SESSION['user_data'] ?? null;
    }

    /**
     * Get session data
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session data
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Check if session has key
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session data
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Regenerate session ID
     */
    public function regenerateId(bool $deleteOldSession = true): void
    {
        session_regenerate_id($deleteOldSession);
    }

    /**
     * Check session validity
     */
    public function isValid(): bool
    {
        // Check if session has expired
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            if ($inactiveTime > $this->config['lifetime']) {
                return false;
            }
        }

        // Check IP address consistency (optional security feature)
        if (isset($_SESSION['ip_address'])) {
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($currentIp && $currentIp !== $_SESSION['ip_address']) {
                // Log suspicious activity
                $this->logSecurityEvent('ip_change', [
                    'old_ip' => $_SESSION['ip_address'],
                    'new_ip' => $currentIp,
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);
                return false;
            }
        }

        // Update last activity
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Update session user
     */
    private function updateSessionUser(string $sessionId, int $userId): void
    {
        try {
            $this->db->update(
                $this->sessionTable,
                ['user_id' => $userId],
                ['session_id' => $sessionId]
            );
        } catch (\Exception $e) {
            // Ignore database errors for session updates
        }
    }

    /**
     * Clean up old sessions for user
     */
    private function cleanupUserSessions(int $userId): void
    {
        try {
            // Keep only the most recent sessions
            $sessions = $this->db->query(
                "SELECT session_id FROM {$this->sessionTable}
                 WHERE user_id = ? ORDER BY last_activity DESC
                 LIMIT 999 OFFSET ?",
                [$userId, $this->config['max_sessions_per_user']]
            );

            foreach ($sessions as $session) {
                $this->db->delete($this->sessionTable, ['session_id' => $session['session_id']]);
            }
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }

    /**
     * Get user sessions
     */
    public function getUserSessions(int $userId): array
    {
        try {
            return $this->db->query(
                "SELECT session_id, user_agent, ip_address, created_at, last_activity, expires_at
                 FROM {$this->sessionTable}
                 WHERE user_id = ? AND expires_at > NOW()
                 ORDER BY last_activity DESC",
                [$userId]
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Invalidate user session
     */
    public function invalidateUserSession(int $userId, string $sessionId): bool
    {
        try {
            return $this->db->delete($this->sessionTable, [
                'user_id' => $userId,
                'session_id' => $sessionId
            ]) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Invalidate all user sessions
     */
    public function invalidateAllUserSessions(int $userId): int
    {
        try {
            return $this->db->execute(
                "DELETE FROM {$this->sessionTable} WHERE user_id = ?",
                [$userId]
            );
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Log user action
     */
    private function logUserAction(int $userId, string $action, string $description): void
    {
        try {
            $this->db->insert('audit_log', [
                'user_id' => $userId,
                'action' => $action,
                'table_name' => 'sessions',
                'record_id' => session_id(),
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Ignore logging errors
        }
    }

    /**
     * Log security event
     */
    private function logSecurityEvent(string $event, array $data): void
    {
        try {
            $notification = new Notification();
            $notification->sendSecurityAlert(
                $data['user_id'] ?? null,
                'Security Alert: ' . ucfirst(str_replace('_', ' ', $event)),
                "Suspicious activity detected: {$event}",
                $data
            );
        } catch (\Exception $e) {
            // Ignore notification errors
        }
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(): array
    {
        try {
            $stats = $this->db->queryOne("
                SELECT
                    COUNT(*) as total_sessions,
                    COUNT(DISTINCT user_id) as active_users,
                    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as valid_sessions,
                    COUNT(CASE WHEN expires_at < NOW() THEN 1 END) as expired_sessions
                FROM {$this->sessionTable}
            ");

            return $stats ?: [
                'total_sessions' => 0,
                'active_users' => 0,
                'valid_sessions' => 0,
                'expired_sessions' => 0
            ];
        } catch (\Exception $e) {
            return [
                'total_sessions' => 0,
                'active_users' => 0,
                'valid_sessions' => 0,
                'expired_sessions' => 0
            ];
        }
    }

    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions(): int
    {
        try {
            return $this->db->execute(
                "DELETE FROM {$this->sessionTable} WHERE expires_at < NOW()"
            );
        } catch (\Exception $e) {
            return 0;
        }
    }
}
