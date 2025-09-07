<?php

namespace TPT\ERP\Core;

/**
 * Threat Detection and Security Monitoring System
 *
 * Advanced threat detection with behavioral analysis, anomaly detection,
 * and automated security responses.
 */
class ThreatDetector
{
    private Database $db;
    private Cache $cache;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->cache = Cache::getInstance();
        $this->config = [
            'max_failed_attempts' => (int) (getenv('MAX_FAILED_ATTEMPTS') ?: 5),
            'lockout_duration' => (int) (getenv('LOCKOUT_DURATION') ?: 900), // 15 minutes
            'suspicious_threshold' => (int) (getenv('SUSPICIOUS_THRESHOLD') ?: 3),
            'alert_cooldown' => (int) (getenv('ALERT_COOLDOWN') ?: 3600), // 1 hour
        ];
    }

    /**
     * Analyze login attempt for threats
     */
    public function analyzeLoginAttempt(array $loginData): array
    {
        $threats = [];
        $riskScore = 0;

        // Extract login information
        $email = $loginData['email'] ?? '';
        $ipAddress = $loginData['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $loginData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceFingerprint = $loginData['device_fingerprint'] ?? '';

        // Check for brute force attempts
        if ($this->isBruteForceAttempt($email, $ipAddress)) {
            $threats[] = 'brute_force';
            $riskScore += 30;
        }

        // Check for suspicious IP
        if ($this->isSuspiciousIP($ipAddress)) {
            $threats[] = 'suspicious_ip';
            $riskScore += 25;
        }

        // Check for unusual login patterns
        if ($this->isUnusualLoginPattern($email, $loginData)) {
            $threats[] = 'unusual_pattern';
            $riskScore += 20;
        }

        // Check for account takeover indicators
        if ($this->isAccountTakeoverAttempt($email, $loginData)) {
            $threats[] = 'account_takeover';
            $riskScore += 40;
        }

        // Check for geographic anomalies
        if ($this->isGeographicAnomaly($email, $loginData)) {
            $threats[] = 'geographic_anomaly';
            $riskScore += 15;
        }

        // Check for device anomalies
        if ($this->isDeviceAnomaly($email, $deviceFingerprint, $loginData)) {
            $threats[] = 'device_anomaly';
            $riskScore += 10;
        }

        // Check for time-based anomalies
        if ($this->isTimeBasedAnomaly($email, $loginData)) {
            $threats[] = 'time_anomaly';
            $riskScore += 5;
        }

        return [
            'threats' => $threats,
            'risk_score' => min($riskScore, 100),
            'risk_level' => $this->calculateRiskLevel($riskScore),
            'recommendations' => $this->getSecurityRecommendations($threats, $riskScore)
        ];
    }

    /**
     * Check for brute force attempts
     */
    private function isBruteForceAttempt(string $email, string $ipAddress): bool
    {
        $cacheKey = "brute_force:{$email}:{$ipAddress}";
        $attempts = (int) $this->cache->get($cacheKey, 0);

        return $attempts >= $this->config['max_failed_attempts'];
    }

    /**
     * Check if IP is suspicious
     */
    private function isSuspiciousIP(string $ipAddress): bool
    {
        // Check against known malicious IP lists (simplified)
        $suspiciousRanges = [
            '10.0.0.0/8',      // Private network
            '172.16.0.0/12',   // Private network
            '192.168.0.0/16',  // Private network
        ];

        // Check if IP is from known VPN/proxy services
        $knownVPNs = [
            '185.156.172.0/22', // Mullvad VPN
            '185.212.149.0/24', // Some VPN provider
        ];

        // Simple check for private IPs
        if ($this->isPrivateIP($ipAddress)) {
            return true;
        }

        // Check recent failed attempts from this IP
        $recentFailures = $this->getRecentFailedAttempts($ipAddress);
        if ($recentFailures > 10) {
            return true;
        }

        return false;
    }

    /**
     * Check for unusual login patterns
     */
    private function isUnusualLoginPattern(string $email, array $loginData): bool
    {
        $user = $this->db->queryOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );

        if (!$user) {
            return false; // New user
        }

        // Check login times
        $currentHour = (int) date('H');
        $normalHours = $this->getUserNormalLoginHours($user['id']);

        if (!in_array($currentHour, $normalHours)) {
            return true;
        }

        // Check login frequency
        $recentLogins = $this->getRecentSuccessfulLogins($user['id'], 24); // Last 24 hours
        if (count($recentLogins) > 20) { // More than 20 logins in 24 hours
            return true;
        }

        return false;
    }

    /**
     * Check for account takeover attempts
     */
    private function isAccountTakeoverAttempt(string $email, array $loginData): bool
    {
        $user = $this->db->queryOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );

        if (!$user) {
            return false;
        }

        // Check for rapid password changes followed by login attempts
        $recentPasswordChanges = $this->getRecentPasswordChanges($user['id'], 24); // Last 24 hours
        if ($recentPasswordChanges > 0) {
            return true;
        }

        // Check for failed login attempts followed by successful login from different location
        $failedAttempts = $this->getRecentFailedAttempts($loginData['ip_address'] ?? '', 1); // Last hour
        if ($failedAttempts > 5) {
            return true;
        }

        return false;
    }

    /**
     * Check for geographic anomalies
     */
    private function isGeographicAnomaly(string $email, array $loginData): bool
    {
        $user = $this->db->queryOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );

        if (!$user) {
            return false;
        }

        $currentLocation = $loginData['location'] ?? null;
        if (!$currentLocation) {
            return false;
        }

        // Get user's normal locations
        $normalLocations = $this->getUserNormalLocations($user['id']);

        // Check if current location is significantly different
        foreach ($normalLocations as $location) {
            $distance = $this->calculateDistance(
                $currentLocation['latitude'],
                $currentLocation['longitude'],
                $location['latitude'],
                $location['longitude']
            );

            if ($distance < 100) { // Within 100km
                return false;
            }
        }

        return true;
    }

    /**
     * Check for device anomalies
     */
    private function isDeviceAnomaly(string $email, string $deviceFingerprint, array $loginData): bool
    {
        $user = $this->db->queryOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );

        if (!$user) {
            return false;
        }

        // Check if device is known and trusted
        $deviceManager = new DeviceManager();
        if (!$deviceManager->isDeviceTrusted($user['id'], $deviceFingerprint)) {
            return true;
        }

        return false;
    }

    /**
     * Check for time-based anomalies
     */
    private function isTimeBasedAnomaly(string $email, array $loginData): bool
    {
        $user = $this->db->queryOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );

        if (!$user) {
            return false;
        }

        // Check for logins at unusual times
        $currentTime = time();
        $lastLogin = $this->getLastLoginTime($user['id']);

        if ($lastLogin) {
            $timeDiff = $currentTime - strtotime($lastLogin);

            // Very rapid successive logins (within 30 seconds)
            if ($timeDiff < 30) {
                return true;
            }

            // No login for 90+ days then sudden activity
            if ($timeDiff > (90 * 24 * 60 * 60)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate risk level from score
     */
    private function calculateRiskLevel(int $score): string
    {
        if ($score >= 70) return 'critical';
        if ($score >= 50) return 'high';
        if ($score >= 30) return 'medium';
        if ($score >= 10) return 'low';
        return 'none';
    }

    /**
     * Get security recommendations based on threats
     */
    private function getSecurityRecommendations(array $threats, int $riskScore): array
    {
        $recommendations = [];

        if (in_array('brute_force', $threats)) {
            $recommendations[] = 'Enable account lockout after failed attempts';
            $recommendations[] = 'Implement CAPTCHA for login attempts';
        }

        if (in_array('suspicious_ip', $threats)) {
            $recommendations[] = 'Block suspicious IP addresses';
            $recommendations[] = 'Enable geo-blocking for high-risk regions';
        }

        if (in_array('account_takeover', $threats)) {
            $recommendations[] = 'Require additional verification for password changes';
            $recommendations[] = 'Monitor for unusual account activity';
        }

        if (in_array('geographic_anomaly', $threats)) {
            $recommendations[] = 'Send location verification notification';
            $recommendations[] = 'Require 2FA for login from new locations';
        }

        if ($riskScore >= 50) {
            $recommendations[] = 'Immediate account lockdown recommended';
            $recommendations[] = 'Security team notification required';
        }

        return array_unique($recommendations);
    }

    /**
     * Record security event
     */
    public function recordSecurityEvent(array $eventData): void
    {
        $this->db->insert('security_events', [
            'event_type' => $eventData['type'],
            'severity' => $eventData['severity'] ?? 'medium',
            'description' => $eventData['description'],
            'user_id' => $eventData['user_id'] ?? null,
            'ip_address' => $eventData['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $eventData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
            'location_data' => isset($eventData['location']) ? json_encode($eventData['location']) : null,
            'metadata' => json_encode($eventData['metadata'] ?? []),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Send alert for high-severity events
        if (($eventData['severity'] ?? 'medium') === 'high' || ($eventData['severity'] ?? 'medium') === 'critical') {
            $this->sendSecurityAlert($eventData);
        }
    }

    /**
     * Send security alert
     */
    private function sendSecurityAlert(array $eventData): void
    {
        try {
            $notification = new Notification();

            // Send to security team (admin users)
            $admins = $this->db->query(
                "SELECT id FROM users WHERE role = 'admin' AND is_active = true"
            );

            foreach ($admins as $admin) {
                $notification->sendToUser(
                    $admin['id'],
                    'Security Alert: ' . ucfirst($eventData['type']),
                    $eventData['description'],
                    'error',
                    [
                        'event_data' => $eventData,
                        'requires_attention' => true
                    ]
                );
            }
        } catch (\Exception $e) {
            error_log('Failed to send security alert: ' . $e->getMessage());
        }
    }

    /**
     * Get security dashboard data
     */
    public function getSecurityDashboard(): array
    {
        $last24Hours = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $last7Days = date('Y-m-d H:i:s', strtotime('-7 days'));

        return [
            'threats_last_24h' => $this->db->queryValue(
                "SELECT COUNT(*) FROM security_events WHERE created_at >= ?",
                [$last24Hours]
            ),
            'high_severity_threats' => $this->db->queryValue(
                "SELECT COUNT(*) FROM security_events WHERE severity IN ('high', 'critical') AND created_at >= ?",
                [$last7Days]
            ),
            'blocked_ips' => $this->db->queryValue(
                "SELECT COUNT(*) FROM security_events WHERE event_type = 'ip_blocked' AND created_at >= ?",
                [$last7Days]
            ),
            'suspicious_logins' => $this->db->queryValue(
                "SELECT COUNT(*) FROM security_events WHERE event_type = 'suspicious_login' AND created_at >= ?",
                [$last24Hours]
            ),
            'recent_events' => $this->db->query(
                "SELECT * FROM security_events ORDER BY created_at DESC LIMIT 10"
            )
        ];
    }

    /**
     * Helper methods for threat detection
     */
    private function isPrivateIP(string $ip): bool
    {
        $privateRanges = [
            '/^10\./',
            '/^172\.(1[6-9]|2[0-9]|3[0-1])\./',
            '/^192\.168\./',
            '/^127\./',
            '/^::1$/'
        ];

        foreach ($privateRanges as $range) {
            if (preg_match($range, $ip)) {
                return true;
            }
        }

        return false;
    }

    private function getRecentFailedAttempts(string $ipAddress, int $hours = 1): int
    {
        $timeAgo = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        return $this->db->queryValue(
            "SELECT COUNT(*) FROM security_events
             WHERE event_type = 'failed_login' AND ip_address = ? AND created_at >= ?",
            [$ipAddress, $timeAgo]
        );
    }

    private function getUserNormalLoginHours(int $userId): array
    {
        $hours = $this->db->query(
            "SELECT DISTINCT HOUR(created_at) as hour FROM security_events
             WHERE user_id = ? AND event_type = 'successful_login'
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            [$userId]
        );

        return array_column($hours, 'hour');
    }

    private function getRecentSuccessfulLogins(int $userId, int $hours = 24): array
    {
        $timeAgo = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        return $this->db->query(
            "SELECT * FROM security_events
             WHERE user_id = ? AND event_type = 'successful_login' AND created_at >= ?",
            [$userId, $timeAgo]
        );
    }

    private function getRecentPasswordChanges(int $userId, int $hours = 24): int
    {
        $timeAgo = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        return $this->db->queryValue(
            "SELECT COUNT(*) FROM password_history
             WHERE user_id = ? AND set_at >= ?",
            [$userId, $timeAgo]
        );
    }

    private function getUserNormalLocations(int $userId): array
    {
        return $this->db->query(
            "SELECT DISTINCT location_data FROM security_events
             WHERE user_id = ? AND location_data IS NOT NULL
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            [$userId]
        );
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function getLastLoginTime(int $userId): ?string
    {
        $result = $this->db->queryOne(
            "SELECT created_at FROM security_events
             WHERE user_id = ? AND event_type = 'successful_login'
             ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );

        return $result ? $result['created_at'] : null;
    }
}
