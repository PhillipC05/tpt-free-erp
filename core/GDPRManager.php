<?php

namespace TPT\ERP\Core;

/**
 * GDPR Compliance Manager
 *
 * Handles GDPR compliance features including data subject rights,
 * consent management, data retention, and privacy controls.
 */
class GDPRManager
{
    private Database $db;
    private Encryption $encryption;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->encryption = new Encryption();
        $this->config = [
            'data_retention_days' => (int) (getenv('GDPR_DATA_RETENTION_DAYS') ?: 2555), // 7 years
            'consent_retention_days' => (int) (getenv('GDPR_CONSENT_RETENTION_DAYS') ?: 2555),
            'auto_delete_inactive_days' => (int) (getenv('GDPR_AUTO_DELETE_INACTIVE_DAYS') ?: 365 * 3), // 3 years
            'gdpr_officer_email' => getenv('GDPR_OFFICER_EMAIL') ?: 'privacy@tpt-erp.com',
        ];
    }

    /**
     * Record user consent for data processing
     */
    public function recordConsent(int $userId, string $consentType, array $consentData = []): bool
    {
        return $this->db->insert('user_consents', [
            'user_id' => $userId,
            'consent_type' => $consentType,
            'consent_given' => $consentData['given'] ?? true,
            'consent_text' => $consentData['text'] ?? '',
            'ip_address' => $consentData['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $consentData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expires_at' => isset($consentData['expires_at']) ? $consentData['expires_at'] : null,
            'withdrawn_at' => null,
            'metadata' => json_encode($consentData['metadata'] ?? []),
            'created_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }

    /**
     * Withdraw user consent
     */
    public function withdrawConsent(int $userId, string $consentType): bool
    {
        return $this->db->execute(
            "UPDATE user_consents SET withdrawn_at = ?, updated_at = ? WHERE user_id = ? AND consent_type = ? AND withdrawn_at IS NULL",
            [date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $userId, $consentType]
        ) > 0;
    }

    /**
     * Check if user has given consent
     */
    public function hasConsent(int $userId, string $consentType): bool
    {
        $consent = $this->db->queryOne(
            "SELECT id FROM user_consents
             WHERE user_id = ? AND consent_type = ? AND consent_given = true
             AND withdrawn_at IS NULL
             AND (expires_at IS NULL OR expires_at > NOW())",
            [$userId, $consentType]
        );

        return $consent !== null;
    }

    /**
     * Get user's consent history
     */
    public function getUserConsentHistory(int $userId): array
    {
        return $this->db->query(
            "SELECT * FROM user_consents WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Handle GDPR Data Subject Access Request (DSAR)
     */
    public function handleDataAccessRequest(int $userId): array
    {
        $userData = [
            'personal_data' => $this->getUserPersonalData($userId),
            'account_data' => $this->getUserAccountData($userId),
            'activity_data' => $this->getUserActivityData($userId),
            'consent_history' => $this->getUserConsentHistory($userId),
            'export_timestamp' => date('c'),
            'gdpr_version' => 'GDPR Article 15'
        ];

        // Log the access request
        $this->logDataSubjectAction($userId, 'access_request', 'Data access request processed');

        return $userData;
    }

    /**
     * Handle GDPR Data Portability Request
     */
    public function handleDataPortabilityRequest(int $userId): string
    {
        $userData = $this->handleDataAccessRequest($userId);

        // Convert to JSON format for portability
        $portableData = json_encode($userData, JSON_PRETTY_PRINT);

        // Log the portability request
        $this->logDataSubjectAction($userId, 'portability_request', 'Data portability request processed');

        return $portableData;
    }

    /**
     * Handle GDPR Right to Erasure (Right to be Forgotten)
     */
    public function handleDataErasureRequest(int $userId, string $reason = ''): bool
    {
        // Check if user can be erased (no legal holds, etc.)
        if (!$this->canEraseUserData($userId)) {
            throw new \Exception('User data cannot be erased due to legal or regulatory requirements');
        }

        // Anonymize personal data instead of deleting (for audit trails)
        $this->anonymizeUserData($userId);

        // Log the erasure request
        $this->logDataSubjectAction($userId, 'erasure_request', 'Data erasure request processed: ' . $reason);

        // Send confirmation email
        $this->sendErasureConfirmation($userId);

        return true;
    }

    /**
     * Handle GDPR Right to Rectification
     */
    public function handleDataRectificationRequest(int $userId, array $corrections): bool
    {
        $user = $this->db->find('users', $userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Update user data
        $updateData = [];
        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'address'];

        foreach ($corrections as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateData[$field] = $value;
            }
        }

        if (!empty($updateData)) {
            $this->db->update('users', $updateData, ['id' => $userId]);

            // Log the rectification
            $this->logDataSubjectAction(
                $userId,
                'rectification_request',
                'Data rectification processed: ' . json_encode($corrections)
            );

            // Send confirmation
            $this->sendRectificationConfirmation($userId, $corrections);
        }

        return true;
    }

    /**
     * Handle GDPR Right to Restriction of Processing
     */
    public function handleProcessingRestrictionRequest(int $userId, bool $restrict): bool
    {
        $this->db->update('users', [
            'data_processing_restricted' => $restrict,
            'restriction_requested_at' => $restrict ? date('Y-m-d H:i:s') : null
        ], ['id' => $userId]);

        $action = $restrict ? 'restricted' : 'unrestricted';
        $this->logDataSubjectAction(
            $userId,
            'processing_restriction',
            "Data processing {$action}"
        );

        return true;
    }

    /**
     * Handle GDPR Right to Object
     */
    public function handleObjectionRequest(int $userId, string $processingType): bool
    {
        $this->db->insert('user_objections', [
            'user_id' => $userId,
            'processing_type' => $processingType,
            'objection_reason' => 'User exercised right to object',
            'status' => 'pending_review',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->logDataSubjectAction(
            $userId,
            'objection_request',
            "Objection to processing: {$processingType}"
        );

        // Notify privacy officer
        $this->notifyPrivacyOfficer($userId, 'objection', "User objected to processing: {$processingType}");

        return true;
    }

    /**
     * Get user personal data for GDPR export
     */
    private function getUserPersonalData(int $userId): array
    {
        $user = $this->db->find('users', $userId);

        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'phone' => $user['phone'] ?? null,
            'address' => $user['address'] ?? null,
            'date_of_birth' => $user['date_of_birth'] ?? null,
            'created_at' => $user['created_at'],
            'last_login' => $user['last_login_at'] ?? null,
            'account_status' => $user['is_active'] ? 'active' : 'inactive',
            'email_verified' => $user['is_verified']
        ];
    }

    /**
     * Get user account data
     */
    private function getUserAccountData(int $userId): array
    {
        return [
            'roles' => $this->db->query(
                "SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?",
                [$userId]
            ),
            'permissions' => $this->db->query(
                "SELECT p.name FROM user_permissions up JOIN permissions p ON up.permission_id = p.id WHERE up.user_id = ?",
                [$userId]
            ),
            'settings' => json_decode($this->db->queryValue(
                "SELECT notification_settings FROM users WHERE id = ?",
                [$userId]
            ) ?? '{}', true),
            'devices' => $this->db->query(
                "SELECT device_name, device_type, last_seen_at FROM user_devices WHERE user_id = ?",
                [$userId]
            )
        ];
    }

    /**
     * Get user activity data
     */
    private function getUserActivityData(int $userId): array
    {
        return [
            'login_history' => $this->db->query(
                "SELECT ip_address, user_agent, created_at FROM audit_log
                 WHERE user_id = ? AND action = 'login' ORDER BY created_at DESC LIMIT 50",
                [$userId]
            ),
            'location_history' => $this->db->query(
                "SELECT country, city, accessed_at FROM location_access_log
                 WHERE user_id = ? ORDER BY accessed_at DESC LIMIT 50",
                [$userId]
            ),
            'security_events' => $this->db->query(
                "SELECT event_type, severity, created_at FROM security_events
                 WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
                [$userId]
            )
        ];
    }

    /**
     * Check if user data can be erased
     */
    private function canEraseUserData(int $userId): bool
    {
        // Check for legal holds
        $legalHold = $this->db->queryOne(
            "SELECT id FROM legal_holds WHERE user_id = ? AND status = 'active'",
            [$userId]
        );

        if ($legalHold) {
            return false;
        }

        // Check for ongoing disputes
        $dispute = $this->db->queryOne(
            "SELECT id FROM user_disputes WHERE user_id = ? AND status IN ('open', 'pending')",
            [$userId]
        );

        if ($dispute) {
            return false;
        }

        return true;
    }

    /**
     * Anonymize user data instead of deleting
     */
    private function anonymizeUserData(int $userId): void
    {
        // Generate anonymous identifier
        $anonymousId = 'ANONYMOUS_' . hash('sha256', $userId . time());

        // Anonymize personal data
        $this->db->update('users', [
            'first_name' => 'Anonymous',
            'last_name' => 'User',
            'email' => $anonymousId . '@anonymous.local',
            'phone' => null,
            'address' => null,
            'date_of_birth' => null,
            'is_active' => false,
            'anonymized_at' => date('Y-m-d H:i:s')
        ], ['id' => $userId]);

        // Anonymize audit logs
        $this->db->execute(
            "UPDATE audit_log SET ip_address = '0.0.0.0', user_agent = 'Anonymized' WHERE user_id = ?",
            [$userId]
        );

        // Clear sensitive session data
        $this->db->execute(
            "DELETE FROM user_sessions WHERE user_id = ?",
            [$userId]
        );
    }

    /**
     * Send erasure confirmation email
     */
    private function sendErasureConfirmation(int $userId): void
    {
        $user = $this->db->find('users', $userId);
        if (!$user) return;

        $emailData = [
            'subject' => 'GDPR Data Erasure Confirmation - TPT ERP',
            'name' => 'User',
            'erasure_date' => date('c'),
            'gdpr_article' => 'Article 17 - Right to Erasure'
        ];

        $this->db->insert('email_queue', [
            'to' => json_encode([$user['email']]),
            'subject' => $emailData['subject'],
            'body' => json_encode($emailData),
            'template' => 'gdpr_erasure',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Send rectification confirmation
     */
    private function sendRectificationConfirmation(int $userId, array $corrections): void
    {
        $user = $this->db->find('users', $userId);
        if (!$user) return;

        $emailData = [
            'subject' => 'GDPR Data Rectification Confirmation - TPT ERP',
            'name' => 'User',
            'corrections' => json_encode($corrections, JSON_PRETTY_PRINT),
            'rectification_date' => date('c'),
            'gdpr_article' => 'Article 16 - Right to Rectification'
        ];

        $this->db->insert('email_queue', [
            'to' => json_encode([$user['email']]),
            'subject' => $emailData['subject'],
            'body' => json_encode($emailData),
            'template' => 'gdpr_rectification',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log data subject action
     */
    private function logDataSubjectAction(int $userId, string $action, string $description): void
    {
        $this->db->insert('gdpr_requests', [
            'user_id' => $userId,
            'request_type' => $action,
            'description' => $description,
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Notify privacy officer
     */
    private function notifyPrivacyOfficer(int $userId, string $requestType, string $details): void
    {
        $user = $this->db->find('users', $userId);

        $notification = new Notification();
        $admins = $this->db->query("SELECT id FROM users WHERE role = 'admin'");

        foreach ($admins as $admin) {
            $notification->sendToUser(
                $admin['id'],
                'GDPR Request: ' . ucfirst(str_replace('_', ' ', $requestType)),
                "GDPR {$requestType} request from user {$user['email']}: {$details}",
                'warning',
                [
                    'user_id' => $userId,
                    'request_type' => $requestType,
                    'requires_attention' => true
                ]
            );
        }
    }

    /**
     * Get GDPR compliance statistics
     */
    public function getGDPRStats(): array
    {
        return [
            'total_consents' => $this->db->count('user_consents'),
            'active_consents' => $this->db->queryValue(
                "SELECT COUNT(*) FROM user_consents WHERE consent_given = true AND withdrawn_at IS NULL"
            ),
            'withdrawn_consents' => $this->db->queryValue(
                "SELECT COUNT(*) FROM user_consents WHERE withdrawn_at IS NOT NULL"
            ),
            'gdpr_requests' => $this->db->count('gdpr_requests'),
            'pending_requests' => $this->db->queryValue(
                "SELECT COUNT(*) FROM gdpr_requests WHERE status = 'pending'"
            ),
            'anonymized_users' => $this->db->queryValue(
                "SELECT COUNT(*) FROM users WHERE anonymized_at IS NOT NULL"
            ),
            'processing_restrictions' => $this->db->queryValue(
                "SELECT COUNT(*) FROM users WHERE data_processing_restricted = true"
            )
        ];
    }

    /**
     * Clean up expired data (GDPR compliance)
     */
    public function cleanupExpiredData(): array
    {
        $results = [];

        // Delete old consent records
        $results['expired_consents'] = $this->db->execute(
            "DELETE FROM user_consents WHERE created_at < ?",
            [date('Y-m-d H:i:s', strtotime("-{$this->config['consent_retention_days']} days"))]
        );

        // Anonymize inactive users
        $inactiveUsers = $this->db->query(
            "SELECT id FROM users WHERE last_login_at < ? AND is_active = true AND anonymized_at IS NULL",
            [date('Y-m-d H:i:s', strtotime("-{$this->config['auto_delete_inactive_days']} days"))]
        );

        $results['anonymized_users'] = 0;
        foreach ($inactiveUsers as $user) {
            try {
                $this->anonymizeUserData($user['id']);
                $results['anonymized_users']++;
            } catch (\Exception $e) {
                // Log error but continue
                error_log('Failed to anonymize user ' . $user['id'] . ': ' . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Create data processing record
     */
    public function recordDataProcessing(int $userId, string $processingType, array $data = []): void
    {
        $this->db->insert('data_processing_log', [
            'user_id' => $userId,
            'processing_type' => $processingType,
            'data_categories' => json_encode($data['categories'] ?? []),
            'legal_basis' => $data['legal_basis'] ?? 'consent',
            'purpose' => $data['purpose'] ?? '',
            'retention_period' => $data['retention_period'] ?? $this->config['data_retention_days'],
            'processed_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check if data processing is allowed
     */
    public function isDataProcessingAllowed(int $userId, string $processingType): bool
    {
        // Check if user has restricted processing
        $user = $this->db->find('users', $userId);
        if ($user && $user['data_processing_restricted']) {
            return false;
        }

        // Check for objections
        $objection = $this->db->queryOne(
            "SELECT id FROM user_objections WHERE user_id = ? AND processing_type = ? AND status = 'approved'",
            [$userId, $processingType]
        );

        return $objection === null;
    }

    /**
     * Generate GDPR compliance report
     */
    public function generateComplianceReport(): array
    {
        return [
            'generated_at' => date('c'),
            'data_controller' => 'TPT ERP',
            'gdpr_officer' => $this->config['gdpr_officer_email'],
            'statistics' => $this->getGDPRStats(),
            'data_processing_activities' => $this->db->query(
                "SELECT processing_type, COUNT(*) as count FROM data_processing_log
                 WHERE processed_at >= ? GROUP BY processing_type",
                [date('Y-m-d H:i:s', strtotime('-30 days'))]
            ),
            'recent_requests' => $this->db->query(
                "SELECT * FROM gdpr_requests ORDER BY created_at DESC LIMIT 20"
            )
        ];
    }
}
