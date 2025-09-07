<?php

namespace TPT\ERP\Core;

/**
 * Location-Based Access Control and Geolocation Management
 *
 * Provides location-based security features, geofencing, and geographic access control.
 */
class LocationManager
{
    private Database $db;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = [
            'max_location_history' => (int) (getenv('MAX_LOCATION_HISTORY') ?: 100),
            'geofence_check_enabled' => getenv('GEOFENCE_CHECK_ENABLED') ?: true,
            'allowed_countries' => explode(',', getenv('ALLOWED_COUNTRIES') ?: ''),
            'blocked_countries' => explode(',', getenv('BLOCKED_COUNTRIES') ?: ''),
            'high_risk_countries' => explode(',', getenv('HIGH_RISK_COUNTRIES') ?: ''),
        ];
    }

    /**
     * Get location data from IP address
     */
    public function getLocationFromIP(string $ipAddress): ?array
    {
        // In production, you would use a geolocation service like:
        // - MaxMind GeoIP2
        // - IP-API
        // - GeoIP services

        // For now, return mock data based on IP ranges
        return $this->getMockLocationData($ipAddress);
    }

    /**
     * Validate location for user access
     */
    public function validateLocationAccess(int $userId, array $locationData): array
    {
        $validation = [
            'allowed' => true,
            'risk_level' => 'low',
            'reasons' => [],
            'recommendations' => []
        ];

        // Check country restrictions
        if ($this->isBlockedCountry($locationData['country'] ?? '')) {
            $validation['allowed'] = false;
            $validation['risk_level'] = 'critical';
            $validation['reasons'][] = 'Access from blocked country';
            $validation['recommendations'][] = 'Contact administrator to allow access from this country';
        }

        // Check if country is in allowed list (if list is defined)
        if (!empty($this->config['allowed_countries']) &&
            !in_array($locationData['country'] ?? '', $this->config['allowed_countries'])) {
            $validation['allowed'] = false;
            $validation['risk_level'] = 'high';
            $validation['reasons'][] = 'Access from non-allowed country';
            $validation['recommendations'][] = 'Request administrator approval for this country';
        }

        // Check high-risk countries
        if ($this->isHighRiskCountry($locationData['country'] ?? '')) {
            $validation['risk_level'] = 'high';
            $validation['reasons'][] = 'Access from high-risk country';
            $validation['recommendations'][] = 'Additional verification required';
        }

        // Check user's allowed locations
        $userAllowedLocations = $this->getUserAllowedLocations($userId);
        if (!empty($userAllowedLocations)) {
            $currentLocation = [
                'latitude' => $locationData['latitude'] ?? 0,
                'longitude' => $locationData['longitude'] ?? 0
            ];

            $isInAllowedArea = false;
            foreach ($userAllowedLocations as $allowedLocation) {
                if ($this->isLocationInGeofence($currentLocation, $allowedLocation)) {
                    $isInAllowedArea = true;
                    break;
                }
            }

            if (!$isInAllowedArea) {
                $validation['allowed'] = false;
                $validation['risk_level'] = 'medium';
                $validation['reasons'][] = 'Access from location outside allowed areas';
                $validation['recommendations'][] = 'Request administrator to add this location to allowed areas';
            }
        }

        // Check distance from user's home location
        $homeLocation = $this->getUserHomeLocation($userId);
        if ($homeLocation) {
            $distance = $this->calculateDistance(
                $homeLocation['latitude'],
                $homeLocation['longitude'],
                $locationData['latitude'] ?? 0,
                $locationData['longitude'] ?? 0
            );

            if ($distance > 1000) { // More than 1000km from home
                $validation['risk_level'] = 'medium';
                $validation['reasons'][] = 'Access from location far from home location';
                $validation['recommendations'][] = 'Verify this is a legitimate access attempt';
            }
        }

        // Record location access attempt
        $this->recordLocationAccess($userId, $locationData, $validation);

        return $validation;
    }

    /**
     * Set user's home location
     */
    public function setUserHomeLocation(int $userId, array $locationData): bool
    {
        return $this->db->execute(
            "INSERT INTO user_locations (user_id, location_type, latitude, longitude, country, city, timezone, created_at)
             VALUES (?, 'home', ?, ?, ?, ?, ?, ?)
             ON CONFLICT (user_id, location_type) DO UPDATE SET
             latitude = EXCLUDED.latitude,
             longitude = EXCLUDED.longitude,
             country = EXCLUDED.country,
             city = EXCLUDED.city,
             timezone = EXCLUDED.timezone,
             updated_at = CURRENT_TIMESTAMP",
            [
                $userId,
                $locationData['latitude'] ?? 0,
                $locationData['longitude'] ?? 0,
                $locationData['country'] ?? '',
                $locationData['city'] ?? '',
                $locationData['timezone'] ?? '',
                date('Y-m-d H:i:s')
            ]
        ) > 0;
    }

    /**
     * Add allowed location for user
     */
    public function addAllowedLocation(int $userId, array $locationData, float $radiusKm = 50): bool
    {
        return $this->db->insert('user_allowed_locations', [
            'user_id' => $userId,
            'name' => $locationData['name'] ?? 'Allowed Location',
            'latitude' => $locationData['latitude'] ?? 0,
            'longitude' => $locationData['longitude'] ?? 0,
            'radius_km' => $radiusKm,
            'country' => $locationData['country'] ?? '',
            'city' => $locationData['city'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }

    /**
     * Remove allowed location
     */
    public function removeAllowedLocation(int $userId, int $locationId): bool
    {
        return $this->db->delete('user_allowed_locations', [
            'id' => $locationId,
            'user_id' => $userId
        ]) > 0;
    }

    /**
     * Get user's allowed locations
     */
    public function getUserAllowedLocations(int $userId): array
    {
        return $this->db->query(
            "SELECT * FROM user_allowed_locations WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Get user's location history
     */
    public function getUserLocationHistory(int $userId, int $limit = 50): array
    {
        return $this->db->query(
            "SELECT * FROM location_access_log
             WHERE user_id = ? ORDER BY accessed_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Check if location is within geofence
     */
    public function isLocationInGeofence(array $currentLocation, array $geofence): bool
    {
        $distance = $this->calculateDistance(
            $currentLocation['latitude'],
            $currentLocation['longitude'],
            $geofence['latitude'],
            $geofence['longitude']
        );

        return $distance <= ($geofence['radius_km'] ?? 50);
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
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

    /**
     * Get user's home location
     */
    private function getUserHomeLocation(int $userId): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM user_locations WHERE user_id = ? AND location_type = 'home'",
            [$userId]
        );
    }

    /**
     * Check if country is blocked
     */
    private function isBlockedCountry(string $country): bool
    {
        return in_array(strtoupper($country), array_map('strtoupper', $this->config['blocked_countries']));
    }

    /**
     * Check if country is high risk
     */
    private function isHighRiskCountry(string $country): bool
    {
        return in_array(strtoupper($country), array_map('strtoupper', $this->config['high_risk_countries']));
    }

    /**
     * Record location access attempt
     */
    private function recordLocationAccess(int $userId, array $locationData, array $validation): void
    {
        $this->db->insert('location_access_log', [
            'user_id' => $userId,
            'ip_address' => $locationData['ip_address'] ?? '',
            'latitude' => $locationData['latitude'] ?? null,
            'longitude' => $locationData['longitude'] ?? null,
            'country' => $locationData['country'] ?? '',
            'city' => $locationData['city'] ?? '',
            'timezone' => $locationData['timezone'] ?? '',
            'access_allowed' => $validation['allowed'],
            'risk_level' => $validation['risk_level'],
            'reasons' => json_encode($validation['reasons']),
            'accessed_at' => date('Y-m-d H:i:s')
        ]);

        // Clean up old records
        $this->cleanupOldLocationRecords($userId);
    }

    /**
     * Clean up old location records
     */
    private function cleanupOldLocationRecords(int $userId): void
    {
        $this->db->execute(
            "DELETE FROM location_access_log
             WHERE user_id = ? AND accessed_at < (
                 SELECT accessed_at FROM location_access_log
                 WHERE user_id = ?
                 ORDER BY accessed_at DESC
                 LIMIT 1 OFFSET ?
             )",
            [$userId, $userId, $this->config['max_location_history']]
        );
    }

    /**
     * Get location-based security statistics
     */
    public function getLocationSecurityStats(): array
    {
        $stats = [
            'total_access_attempts' => $this->db->count('location_access_log'),
            'blocked_access_attempts' => $this->db->queryValue(
                "SELECT COUNT(*) FROM location_access_log WHERE access_allowed = false"
            ),
            'high_risk_accesses' => $this->db->queryValue(
                "SELECT COUNT(*) FROM location_access_log WHERE risk_level IN ('high', 'critical')"
            ),
            'unique_countries' => $this->db->queryValue(
                "SELECT COUNT(DISTINCT country) FROM location_access_log WHERE country != ''"
            ),
            'most_common_countries' => $this->db->query(
                "SELECT country, COUNT(*) as count FROM location_access_log
                 WHERE country != '' GROUP BY country ORDER BY count DESC LIMIT 10"
            )
        ];

        return $stats;
    }

    /**
     * Mock location data for development (replace with real geolocation service)
     */
    private function getMockLocationData(string $ipAddress): ?array
    {
        // This is mock data - in production, use a real geolocation service
        $mockLocations = [
            '192.168.1.1' => [
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'country' => 'US',
                'city' => 'New York',
                'timezone' => 'America/New_York'
            ],
            '10.0.0.1' => [
                'latitude' => 51.5074,
                'longitude' => -0.1278,
                'country' => 'GB',
                'city' => 'London',
                'timezone' => 'Europe/London'
            ],
            '172.16.0.1' => [
                'latitude' => -33.8688,
                'longitude' => 151.2093,
                'country' => 'AU',
                'city' => 'Sydney',
                'timezone' => 'Australia/Sydney'
            ]
        ];

        return $mockLocations[$ipAddress] ?? [
            'latitude' => 0,
            'longitude' => 0,
            'country' => 'Unknown',
            'city' => 'Unknown',
            'timezone' => 'UTC'
        ];
    }

    /**
     * Create location-based access rule
     */
    public function createLocationRule(array $ruleData): bool
    {
        return $this->db->insert('location_access_rules', [
            'name' => $ruleData['name'],
            'description' => $ruleData['description'] ?? '',
            'rule_type' => $ruleData['rule_type'], // 'allow', 'block', 'require_2fa'
            'countries' => json_encode($ruleData['countries'] ?? []),
            'latitude' => $ruleData['latitude'] ?? null,
            'longitude' => $ruleData['longitude'] ?? null,
            'radius_km' => $ruleData['radius_km'] ?? null,
            'is_active' => $ruleData['is_active'] ?? true,
            'created_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }

    /**
     * Get active location rules
     */
    public function getActiveLocationRules(): array
    {
        return $this->db->query(
            "SELECT * FROM location_access_rules WHERE is_active = true ORDER BY created_at DESC"
        );
    }

    /**
     * Check location against rules
     */
    public function checkLocationAgainstRules(array $locationData): array
    {
        $rules = $this->getActiveLocationRules();
        $result = [
            'allowed' => true,
            'requires_2fa' => false,
            'blocking_rules' => [],
            'requiring_rules' => []
        ];

        foreach ($rules as $rule) {
            $matchesRule = $this->locationMatchesRule($locationData, $rule);

            if ($matchesRule) {
                switch ($rule['rule_type']) {
                    case 'block':
                        $result['allowed'] = false;
                        $result['blocking_rules'][] = $rule;
                        break;
                    case 'require_2fa':
                        $result['requires_2fa'] = true;
                        $result['requiring_rules'][] = $rule;
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * Check if location matches a rule
     */
    private function locationMatchesRule(array $locationData, array $rule): bool
    {
        // Check country match
        if (!empty($rule['countries'])) {
            $ruleCountries = json_decode($rule['countries'], true);
            if (!in_array($locationData['country'] ?? '', $ruleCountries)) {
                return false;
            }
        }

        // Check geographic match (if coordinates and radius specified)
        if (isset($rule['latitude']) && isset($rule['longitude']) && isset($rule['radius_km'])) {
            $distance = $this->calculateDistance(
                $locationData['latitude'] ?? 0,
                $locationData['longitude'] ?? 0,
                $rule['latitude'],
                $rule['longitude']
            );

            if ($distance > $rule['radius_km']) {
                return false;
            }
        }

        return true;
    }
}
