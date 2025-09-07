<?php

namespace TPT\ERP\Core;

/**
 * Device Management and Signing System
 *
 * Manages user devices, device fingerprinting, and trusted device authentication.
 */
class DeviceManager
{
    private Database $db;
    private Encryption $encryption;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->encryption = new Encryption();
    }

    /**
     * Register a new device for a user
     */
    public function registerDevice(
        int $userId,
        string $deviceFingerprint,
        array $deviceInfo = []
    ): array {
        // Check if device already exists
        $existingDevice = $this->db->queryOne(
            "SELECT id, is_trusted FROM user_devices WHERE user_id = ? AND device_fingerprint = ?",
            [$userId, $deviceFingerprint]
        );

        if ($existingDevice) {
            // Update device info and return existing device
            $this->updateDeviceInfo($existingDevice['id'], $deviceInfo);
            return $this->getDevice($existingDevice['id']);
        }

        // Create new device record
        $deviceId = $this->db->insert('user_devices', [
            'user_id' => $userId,
            'device_fingerprint' => $deviceFingerprint,
            'device_name' => $deviceInfo['name'] ?? $this->generateDeviceName($deviceInfo),
            'device_type' => $this->detectDeviceType($deviceInfo),
            'browser_name' => $deviceInfo['browser_name'] ?? null,
            'browser_version' => $deviceInfo['browser_version'] ?? null,
            'os_name' => $deviceInfo['os_name'] ?? null,
            'os_version' => $deviceInfo['os_version'] ?? null,
            'ip_address' => $deviceInfo['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'location_data' => isset($deviceInfo['location']) ? json_encode($deviceInfo['location']) : null,
            'is_trusted' => false, // New devices start as untrusted
            'trust_expires_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'last_seen_at' => date('Y-m-d H:i:s')
        ]);

        // Log device registration
        $this->logDeviceEvent($userId, $deviceId, 'device_registered', 'New device registered');

        // Send notification for new device
        $this->notifyNewDevice($userId, $deviceInfo);

        return $this->getDevice($deviceId);
    }

    /**
     * Generate device fingerprint from request data
     */
    public function generateDeviceFingerprint(array $requestData = []): string
    {
        $components = [
            $requestData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
            $requestData['accept_language'] ?? $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $requestData['screen_resolution'] ?? '',
            $requestData['timezone'] ?? '',
            $requestData['platform'] ?? '',
            $requestData['cookie_enabled'] ?? '',
            $requestData['do_not_track'] ?? '',
        ];

        // Create a unique fingerprint
        $fingerprint = hash('sha256', implode('|', $components));

        return $fingerprint;
    }

    /**
     * Trust a device (mark as trusted)
     */
    public function trustDevice(int $userId, int $deviceId, int $trustDuration = 30): bool
    {
        $trustExpiresAt = date('Y-m-d H:i:s', strtotime("+{$trustDuration} days"));

        $result = $this->db->update('user_devices', [
            'is_trusted' => true,
            'trust_expires_at' => $trustExpiresAt,
            'last_seen_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $deviceId,
            'user_id' => $userId
        ]);

        if ($result) {
            $this->logDeviceEvent($userId, $deviceId, 'device_trusted', "Device trusted for {$trustDuration} days");
        }

        return $result > 0;
    }

    /**
     * Revoke device trust
     */
    public function revokeDeviceTrust(int $userId, int $deviceId): bool
    {
        $result = $this->db->update('user_devices', [
            'is_trusted' => false,
            'trust_expires_at' => null,
            'last_seen_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $deviceId,
            'user_id' => $userId
        ]);

        if ($result) {
            $this->logDeviceEvent($userId, $deviceId, 'device_trust_revoked', 'Device trust revoked');
        }

        return $result > 0;
    }

    /**
     * Check if device is trusted
     */
    public function isDeviceTrusted(int $userId, string $deviceFingerprint): bool
    {
        $device = $this->db->queryOne(
            "SELECT is_trusted, trust_expires_at FROM user_devices
             WHERE user_id = ? AND device_fingerprint = ?",
            [$userId, $deviceFingerprint]
        );

        if (!$device || !$device['is_trusted']) {
            return false;
        }

        // Check if trust has expired
        if ($device['trust_expires_at'] && strtotime($device['trust_expires_at']) < time()) {
            // Trust has expired, revoke it
            $this->revokeDeviceTrust($userId, $device['id']);
            return false;
        }

        return true;
    }

    /**
     * Update device last seen time
     */
    public function updateDeviceLastSeen(int $userId, string $deviceFingerprint): void
    {
        $this->db->execute(
            "UPDATE user_devices SET last_seen_at = ? WHERE user_id = ? AND device_fingerprint = ?",
            [date('Y-m-d H:i:s'), $userId, $deviceFingerprint]
        );
    }

    /**
     * Get user's devices
     */
    public function getUserDevices(int $userId): array
    {
        return $this->db->query(
            "SELECT id, device_name, device_type, browser_name, os_name,
                    ip_address, is_trusted, trust_expires_at, created_at, last_seen_at
             FROM user_devices
             WHERE user_id = ?
             ORDER BY last_seen_at DESC",
            [$userId]
        );
    }

    /**
     * Get device by ID
     */
    public function getDevice(int $deviceId): ?array
    {
        $device = $this->db->queryOne(
            "SELECT * FROM user_devices WHERE id = ?",
            [$deviceId]
        );

        if ($device) {
            $device['location_data'] = json_decode($device['location_data'], true);
        }

        return $device;
    }

    /**
     * Remove device
     */
    public function removeDevice(int $userId, int $deviceId): bool
    {
        $result = $this->db->delete('user_devices', [
            'id' => $deviceId,
            'user_id' => $userId
        ]);

        if ($result) {
            $this->logDeviceEvent($userId, $deviceId, 'device_removed', 'Device removed from account');
        }

        return $result > 0;
    }

    /**
     * Detect suspicious device activity
     */
    public function detectSuspiciousActivity(int $userId, string $deviceFingerprint, array $currentInfo): array
    {
        $device = $this->db->queryOne(
            "SELECT * FROM user_devices WHERE user_id = ? AND device_fingerprint = ?",
            [$userId, $deviceFingerprint]
        );

        $suspicious = [];

        if (!$device) {
            $suspicious[] = 'new_device';
        } else {
            // Check for significant changes
            if ($device['ip_address'] !== ($currentInfo['ip_address'] ?? '')) {
                $suspicious[] = 'ip_change';
            }

            if ($device['location_data']) {
                $oldLocation = json_decode($device['location_data'], true);
                $newLocation = $currentInfo['location'] ?? [];

                if ($this->isLocationChanged($oldLocation, $newLocation)) {
                    $suspicious[] = 'location_change';
                }
            }

            // Check time-based anomalies
            $lastSeen = strtotime($device['last_seen_at']);
            $now = time();
            $hoursSinceLastSeen = ($now - $lastSeen) / 3600;

            if ($hoursSinceLastSeen > 24 * 30) { // 30 days
                $suspicious[] = 'long_inactive';
            }
        }

        return $suspicious;
    }

    /**
     * Generate device name from device info
     */
    private function generateDeviceName(array $deviceInfo): string
    {
        $parts = [];

        if (isset($deviceInfo['os_name'])) {
            $parts[] = $deviceInfo['os_name'];
        }

        if (isset($deviceInfo['device_type'])) {
            $parts[] = ucfirst($deviceInfo['device_type']);
        }

        if (isset($deviceInfo['browser_name'])) {
            $parts[] = $deviceInfo['browser_name'];
        }

        if (empty($parts)) {
            $parts[] = 'Unknown Device';
        }

        return implode(' - ', $parts);
    }

    /**
     * Detect device type from user agent
     */
    private function detectDeviceType(array $deviceInfo): string
    {
        $userAgent = $deviceInfo['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (stripos($userAgent, 'mobile') !== false || stripos($userAgent, 'android') !== false) {
            return 'mobile';
        }

        if (stripos($userAgent, 'tablet') !== false || stripos($userAgent, 'ipad') !== false) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Update device information
     */
    private function updateDeviceInfo(int $deviceId, array $deviceInfo): void
    {
        $updateData = ['last_seen_at' => date('Y-m-d H:i:s')];

        if (isset($deviceInfo['ip_address'])) {
            $updateData['ip_address'] = $deviceInfo['ip_address'];
        }

        if (isset($deviceInfo['location'])) {
            $updateData['location_data'] = json_encode($deviceInfo['location']);
        }

        $this->db->update('user_devices', $updateData, ['id' => $deviceId]);
    }

    /**
     * Check if location has changed significantly
     */
    private function isLocationChanged(?array $oldLocation, ?array $newLocation): bool
    {
        if (!$oldLocation || !$newLocation) {
            return false;
        }

        // Simple distance check (rough approximation)
        $oldLat = $oldLocation['latitude'] ?? 0;
        $oldLng = $oldLocation['longitude'] ?? 0;
        $newLat = $newLocation['latitude'] ?? 0;
        $newLng = $newLocation['longitude'] ?? 0;

        $distance = sqrt(pow($newLat - $oldLat, 2) + pow($newLng - $oldLng, 2));

        // If distance is more than ~100km (rough approximation)
        return $distance > 1.0;
    }

    /**
     * Send notification for new device
     */
    private function notifyNewDevice(int $userId, array $deviceInfo): void
    {
        try {
            $notification = new Notification();
            $deviceName = $this->generateDeviceName($deviceInfo);

            $notification->sendToUser(
                $userId,
                'New Device Detected',
                "A new device ({$deviceName}) has been used to access your account. If this wasn't you, please secure your account immediately.",
                'warning',
                [
                    'device_info' => $deviceInfo,
                    'action_required' => true
                ]
            );
        } catch (\Exception $e) {
            // Don't let notification failure break device registration
            error_log('Failed to send new device notification: ' . $e->getMessage());
        }
    }

    /**
     * Log device event
     */
    private function logDeviceEvent(int $userId, int $deviceId, string $event, string $description): void
    {
        $this->db->insert('audit_log', [
            'user_id' => $userId,
            'action' => $event,
            'table_name' => 'user_devices',
            'record_id' => $deviceId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Clean up old untrusted devices
     */
    public function cleanupOldDevices(int $daysOld = 90): int
    {
        return $this->db->execute(
            "DELETE FROM user_devices WHERE is_trusted = false AND created_at < ?",
            [date('Y-m-d H:i:s', strtotime("-{$daysOld} days"))]
        );
    }

    /**
     * Get device statistics
     */
    public function getDeviceStats(int $userId = null): array
    {
        $conditions = $userId ? ['user_id' => $userId] : [];

        $stats = [
            'total_devices' => $this->db->count('user_devices', $conditions),
            'trusted_devices' => $this->db->count('user_devices', array_merge($conditions, ['is_trusted' => true])),
            'untrusted_devices' => $this->db->count('user_devices', array_merge($conditions, ['is_trusted' => false])),
        ];

        // Get device types breakdown
        $typeStats = $this->db->query(
            "SELECT device_type, COUNT(*) as count FROM user_devices " .
            ($userId ? "WHERE user_id = {$userId} " : "") .
            "GROUP BY device_type"
        );

        $stats['device_types'] = [];
        foreach ($typeStats as $stat) {
            $stats['device_types'][$stat['device_type']] = (int) $stat['count'];
        }

        return $stats;
    }
}
