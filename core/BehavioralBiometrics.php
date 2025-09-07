<?php

namespace TPT\ERP\Core;

/**
 * Behavioral Biometrics Security System
 *
 * Advanced user behavior analysis for continuous authentication and threat detection.
 * Supports granular configuration at company, team, and individual user levels.
 */
class BehavioralBiometrics
{
    private Database $db;
    private Cache $cache;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->cache = Cache::getInstance();
        $this->config = [
            'retention_days' => (int) (getenv('BEHAVIORAL_DATA_RETENTION_DAYS') ?: 90),
            'min_samples' => (int) (getenv('BEHAVIORAL_MIN_SAMPLES') ?: 100),
            'anomaly_threshold' => (float) (getenv('BEHAVIORAL_ANOMALY_THRESHOLD') ?: 0.7),
            'learning_period_days' => (int) (getenv('BEHAVIORAL_LEARNING_PERIOD') ?: 14),
        ];
    }

    /**
     * Record user behavior data
     */
    public function recordBehavior(int $userId, array $behaviorData): void
    {
        // Check if behavioral tracking is enabled for this user
        if (!$this->isBehavioralTrackingEnabled($userId)) {
            return;
        }

        $behaviorRecord = [
            'user_id' => $userId,
            'session_id' => session_id(),
            'behavior_type' => $behaviorData['type'],
            'data' => json_encode($behaviorData['data']),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];

        $this->db->insert('behavioral_data', $behaviorRecord);

        // Update behavior profile in real-time
        $this->updateBehaviorProfile($userId, $behaviorData);
    }

    /**
     * Analyze user behavior for anomalies
     */
    public function analyzeBehavior(int $userId, array $currentBehavior): array
    {
        if (!$this->isBehavioralTrackingEnabled($userId)) {
            return ['risk_score' => 0, 'anomalies' => [], 'confidence' => 0];
        }

        $profile = $this->getBehaviorProfile($userId);
        if (!$profile) {
            // Not enough data to analyze
            return ['risk_score' => 0.5, 'anomalies' => ['insufficient_data'], 'confidence' => 0];
        }

        $analysis = [
            'risk_score' => 0,
            'anomalies' => [],
            'confidence' => 0,
            'details' => []
        ];

        // Analyze different behavior types
        foreach ($currentBehavior as $type => $data) {
            if (isset($profile[$type])) {
                $typeAnalysis = $this->analyzeBehaviorType($data, $profile[$type]);
                $analysis['risk_score'] += $typeAnalysis['risk_score'];
                $analysis['anomalies'] = array_merge($analysis['anomalies'], $typeAnalysis['anomalies']);
                $analysis['details'][$type] = $typeAnalysis;
            }
        }

        // Normalize risk score
        $analysis['risk_score'] = min($analysis['risk_score'] / count($currentBehavior), 1.0);
        $analysis['confidence'] = $this->calculateConfidence($profile);

        // Record analysis result
        $this->recordAnalysisResult($userId, $analysis, $currentBehavior);

        return $analysis;
    }

    /**
     * Enable behavioral tracking for user
     */
    public function enableBehavioralTracking(int $userId, array $settings = []): bool
    {
        $settings = array_merge([
            'enabled' => true,
            'sensitivity' => 'medium', // low, medium, high
            'alert_threshold' => 0.7,
            'learning_mode' => true,
            'track_mouse' => true,
            'track_keyboard' => true,
            'track_screen' => true,
            'track_files' => true
        ], $settings);

        return $this->db->insert('behavioral_settings', [
            'user_id' => $userId,
            'settings' => json_encode($settings),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }

    /**
     * Disable behavioral tracking for user
     */
    public function disableBehavioralTracking(int $userId): bool
    {
        return $this->db->execute(
            "UPDATE behavioral_settings SET settings = jsonb_set(settings, '{enabled}', 'false') WHERE user_id = ?",
            [$userId]
        ) > 0;
    }

    /**
     * Check if behavioral tracking is enabled
     */
    public function isBehavioralTrackingEnabled(int $userId): bool
    {
        // Check user-specific setting first
        $userSetting = $this->db->queryOne(
            "SELECT settings FROM behavioral_settings WHERE user_id = ?",
            [$userId]
        );

        if ($userSetting) {
            $settings = json_decode($userSetting['settings'], true);
            if (isset($settings['enabled'])) {
                return $settings['enabled'];
            }
        }

        // Check team/company settings
        return $this->isBehavioralTrackingEnabledForTeam($userId);
    }

    /**
     * Configure behavioral tracking at team/company level
     */
    public function configureTeamBehavioralTracking(int $teamId, array $settings): bool
    {
        return $this->db->insert('team_behavioral_settings', [
            'team_id' => $teamId,
            'settings' => json_encode($settings),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }

    /**
     * Get behavioral profile for user
     */
    public function getBehaviorProfile(int $userId): ?array
    {
        $cacheKey = "behavior_profile:{$userId}";
        $profile = $this->cache->get($cacheKey);

        if (!$profile) {
            $profile = $this->buildBehaviorProfile($userId);
            if ($profile) {
                $this->cache->set($cacheKey, $profile, 3600); // Cache for 1 hour
            }
        }

        return $profile;
    }

    /**
     * Build behavior profile from historical data
     */
    private function buildBehaviorProfile(int $userId): ?array
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$this->config['learning_period_days']} days"));

        $behaviorData = $this->db->query(
            "SELECT behavior_type, data, timestamp FROM behavioral_data
             WHERE user_id = ? AND timestamp >= ?
             ORDER BY timestamp DESC LIMIT 1000",
            [$userId, $cutoffDate]
        );

        if (count($behaviorData) < $this->config['min_samples']) {
            return null; // Not enough data
        }

        $profile = [];

        // Group by behavior type
        $groupedData = [];
        foreach ($behaviorData as $record) {
            $type = $record['behavior_type'];
            if (!isset($groupedData[$type])) {
                $groupedData[$type] = [];
            }
            $groupedData[$type][] = json_decode($record['data'], true);
        }

        // Calculate statistical profiles for each behavior type
        foreach ($groupedData as $type => $data) {
            $profile[$type] = $this->calculateStatisticalProfile($data);
        }

        return $profile;
    }

    /**
     * Calculate statistical profile for behavior data
     */
    private function calculateStatisticalProfile(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $profile = [];
        $numericFields = [];

        // Extract numeric fields
        foreach ($data as $record) {
            foreach ($record as $key => $value) {
                if (is_numeric($value)) {
                    if (!isset($numericFields[$key])) {
                        $numericFields[$key] = [];
                    }
                    $numericFields[$key][] = (float) $value;
                }
            }
        }

        // Calculate statistics for numeric fields
        foreach ($numericFields as $field => $values) {
            sort($values);
            $count = count($values);
            $mean = array_sum($values) / $count;
            $median = $values[(int) ($count / 2)];

            // Calculate standard deviation
            $variance = 0;
            foreach ($values as $value) {
                $variance += pow($value - $mean, 2);
            }
            $stdDev = sqrt($variance / $count);

            $profile[$field] = [
                'mean' => $mean,
                'median' => $median,
                'std_dev' => $stdDev,
                'min' => min($values),
                'max' => max($values),
                'q1' => $values[(int) ($count * 0.25)],
                'q3' => $values[(int) ($count * 0.75)],
                'sample_size' => $count
            ];
        }

        return $profile;
    }

    /**
     * Analyze specific behavior type
     */
    private function analyzeBehaviorType(array $currentData, array $profile): array
    {
        $analysis = [
            'risk_score' => 0,
            'anomalies' => [],
            'details' => []
        ];

        foreach ($currentData as $field => $value) {
            if (isset($profile[$field]) && is_numeric($value)) {
                $fieldAnalysis = $this->analyzeField($value, $profile[$field]);
                $analysis['risk_score'] += $fieldAnalysis['risk_score'];
                if ($fieldAnalysis['is_anomaly']) {
                    $analysis['anomalies'][] = $field;
                }
                $analysis['details'][$field] = $fieldAnalysis;
            }
        }

        $analysis['risk_score'] = min($analysis['risk_score'] / max(count($currentData), 1), 1.0);

        return $analysis;
    }

    /**
     * Analyze individual field for anomalies
     */
    private function analyzeField(float $value, array $stats): array
    {
        $mean = $stats['mean'];
        $stdDev = $stats['std_dev'];
        $min = $stats['min'];
        $max = $stats['max'];

        // Check if value is within normal range
        $isAnomaly = false;
        $deviation = 0;

        if ($stdDev > 0) {
            $deviation = abs($value - $mean) / $stdDev;

            // Consider it an anomaly if deviation is more than 2 standard deviations
            if ($deviation > 2.0) {
                $isAnomaly = true;
            }
        }

        // Check if value is outside historical range
        if ($value < $min * 0.5 || $value > $max * 1.5) {
            $isAnomaly = true;
        }

        return [
            'value' => $value,
            'expected_range' => [$min, $max],
            'deviation' => $deviation,
            'is_anomaly' => $isAnomaly,
            'risk_score' => $isAnomaly ? min($deviation / 4.0, 1.0) : 0
        ];
    }

    /**
     * Update behavior profile with new data
     */
    private function updateBehaviorProfile(int $userId, array $behaviorData): void
    {
        // Invalidate cache to force rebuild
        $cacheKey = "behavior_profile:{$userId}";
        $this->cache->delete($cacheKey);

        // Could implement incremental updates here for better performance
    }

    /**
     * Calculate confidence in analysis
     */
    private function calculateConfidence(array $profile): float
    {
        $totalSamples = 0;
        $behaviorTypes = 0;

        foreach ($profile as $type => $typeProfile) {
            foreach ($typeProfile as $field => $stats) {
                if (isset($stats['sample_size'])) {
                    $totalSamples += $stats['sample_size'];
                    $behaviorTypes++;
                }
            }
        }

        // Confidence increases with more samples and behavior types
        $sampleConfidence = min($totalSamples / ($this->config['min_samples'] * 2), 1.0);
        $typeConfidence = min($behaviorTypes / 5, 1.0); // 5 behavior types for full confidence

        return ($sampleConfidence + $typeConfidence) / 2;
    }

    /**
     * Record analysis result
     */
    private function recordAnalysisResult(int $userId, array $analysis, array $behaviorData): void
    {
        $this->db->insert('behavioral_analysis', [
            'user_id' => $userId,
            'session_id' => session_id(),
            'risk_score' => $analysis['risk_score'],
            'confidence' => $analysis['confidence'],
            'anomalies' => json_encode($analysis['anomalies']),
            'behavior_data' => json_encode($behaviorData),
            'analysis_details' => json_encode($analysis['details']),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        // Trigger alerts for high-risk scores
        if ($analysis['risk_score'] > $this->config['anomaly_threshold']) {
            $this->triggerBehavioralAlert($userId, $analysis);
        }
    }

    /**
     * Trigger behavioral alert
     */
    private function triggerBehavioralAlert(int $userId, array $analysis): void
    {
        $notification = new Notification();

        // Notify user
        $notification->sendToUser(
            $userId,
            'Unusual Activity Detected',
            'We detected unusual behavior patterns on your account. If this wasn\'t you, please secure your account immediately.',
            'warning',
            [
                'risk_score' => $analysis['risk_score'],
                'anomalies' => $analysis['anomalies'],
                'action_required' => true
            ]
        );

        // Notify administrators
        $admins = $this->db->query("SELECT id FROM users WHERE role = 'admin'");
        foreach ($admins as $admin) {
            $notification->sendToUser(
                $admin['id'],
                'Behavioral Anomaly Alert',
                "Unusual behavior detected for user {$userId} (Risk Score: " . round($analysis['risk_score'] * 100) . "%)",
                'error',
                [
                    'user_id' => $userId,
                    'risk_score' => $analysis['risk_score'],
                    'anomalies' => $analysis['anomalies']
                ]
            );
        }
    }

    /**
     * Check team-level behavioral tracking settings
     */
    private function isBehavioralTrackingEnabledForTeam(int $userId): bool
    {
        // Get user's team/company
        $user = $this->db->queryOne(
            "SELECT team_id, company_id FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            return false;
        }

        // Check team settings
        if ($user['team_id']) {
            $teamSetting = $this->db->queryOne(
                "SELECT settings FROM team_behavioral_settings WHERE team_id = ?",
                [$user['team_id']]
            );

            if ($teamSetting) {
                $settings = json_decode($teamSetting['settings'], true);
                return $settings['enabled'] ?? false;
            }
        }

        // Check company settings
        if ($user['company_id']) {
            $companySetting = $this->db->queryOne(
                "SELECT settings FROM company_behavioral_settings WHERE company_id = ?",
                [$user['company_id']]
            );

            if ($companySetting) {
                $settings = json_decode($companySetting['settings'], true);
                return $settings['enabled'] ?? false;
            }
        }

        return false; // Default to disabled
    }

    /**
     * Clean up old behavioral data
     */
    public function cleanupOldData(): array
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$this->config['retention_days']} days"));

        $results = [
            'behavioral_data_deleted' => $this->db->execute(
                "DELETE FROM behavioral_data WHERE timestamp < ?",
                [$cutoffDate]
            ),
            'analysis_data_deleted' => $this->db->execute(
                "DELETE FROM behavioral_analysis WHERE timestamp < ?",
                [$cutoffDate]
            )
        ];

        return $results;
    }

    /**
     * Get behavioral analytics
     */
    public function getBehavioralAnalytics(int $userId = null): array
    {
        $conditions = $userId ? "WHERE user_id = {$userId}" : "";

        return [
            'total_users_tracked' => $this->db->queryValue(
                "SELECT COUNT(DISTINCT user_id) FROM behavioral_settings WHERE settings->>'enabled' = 'true'"
            ),
            'total_behavior_records' => $this->db->queryValue(
                "SELECT COUNT(*) FROM behavioral_data {$conditions}"
            ),
            'high_risk_detections' => $this->db->queryValue(
                "SELECT COUNT(*) FROM behavioral_analysis {$conditions} AND risk_score > 0.7"
            ),
            'average_risk_score' => $this->db->queryValue(
                "SELECT AVG(risk_score) FROM behavioral_analysis {$conditions}"
            ),
            'most_common_anomalies' => $this->db->query(
                "SELECT jsonb_array_elements_text(anomalies) as anomaly, COUNT(*) as count
                 FROM behavioral_analysis {$conditions}
                 GROUP BY anomaly ORDER BY count DESC LIMIT 10"
            )
        ];
    }

    /**
     * Export behavioral data for GDPR compliance
     */
    public function exportBehavioralData(int $userId): array
    {
        return [
            'behavioral_settings' => $this->db->queryOne(
                "SELECT * FROM behavioral_settings WHERE user_id = ?",
                [$userId]
            ),
            'behavioral_data' => $this->db->query(
                "SELECT * FROM behavioral_data WHERE user_id = ? ORDER BY timestamp DESC",
                [$userId]
            ),
            'analysis_history' => $this->db->query(
                "SELECT * FROM behavioral_analysis WHERE user_id = ? ORDER BY timestamp DESC",
                [$userId]
            ),
            'behavior_profile' => $this->getBehaviorProfile($userId)
        ];
    }

    /**
     * Anonymize behavioral data for GDPR compliance
     */
    public function anonymizeBehavioralData(int $userId): void
    {
        // Remove personal identifiers from behavioral data
        $this->db->execute(
            "UPDATE behavioral_data SET data = '{}'::jsonb WHERE user_id = ?",
            [$userId]
        );

        // Remove analysis details
        $this->db->execute(
            "UPDATE behavioral_analysis SET behavior_data = '{}'::jsonb, analysis_details = '{}'::jsonb WHERE user_id = ?",
            [$userId]
        );

        // Clear cache
        $cacheKey = "behavior_profile:{$userId}";
        $this->cache->delete($cacheKey);
    }
}
