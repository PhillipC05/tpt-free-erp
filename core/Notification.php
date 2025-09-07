<?php

namespace TPT\ERP\Core;

/**
 * Notification System
 *
 * Handles in-app notifications, email notifications, and notification preferences.
 */
class Notification
{
    private Database $db;
    private Email $email;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->email = new Email();
    }

    /**
     * Send notification to user
     */
    public function sendToUser(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        array $data = []
    ): bool {
        // Check user notification preferences
        if (!$this->shouldSendNotification($userId, $type)) {
            return false;
        }

        // Create in-app notification
        $notificationId = $this->createNotification($userId, $title, $message, $type, $data);

        // Send email if enabled
        if ($this->shouldSendEmail($userId, $type)) {
            $user = $this->db->find('users', $userId);
            if ($user && $user['is_verified']) {
                $this->email->sendNotification($user['email'], $title, $message, $data);
            }
        }

        return $notificationId > 0;
    }

    /**
     * Send notification to multiple users
     */
    public function sendToUsers(
        array $userIds,
        string $title,
        string $message,
        string $type = 'info',
        array $data = []
    ): int {
        $sent = 0;

        foreach ($userIds as $userId) {
            if ($this->sendToUser($userId, $title, $message, $type, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send notification to role
     */
    public function sendToRole(
        string $roleName,
        string $title,
        string $message,
        string $type = 'info',
        array $data = []
    ): int {
        $userIds = $this->getUsersByRole($roleName);
        return $this->sendToUsers($userIds, $title, $message, $type, $data);
    }

    /**
     * Create in-app notification
     */
    private function createNotification(
        int $userId,
        string $title,
        string $message,
        string $type,
        array $data
    ): int {
        return $this->db->insert('notifications', [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => json_encode($data),
            'is_read' => false,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(
        int $userId,
        bool $unreadOnly = false,
        int $limit = 50,
        int $offset = 0
    ): array {
        $where = ['user_id' => $userId];

        if ($unreadOnly) {
            $where['is_read'] = false;
        }

        $notifications = $this->db->findBy(
            'notifications',
            $where,
            ['created_at' => 'DESC'],
            $limit,
            $offset
        );

        // Decode data field
        foreach ($notifications as &$notification) {
            $notification['data'] = json_decode($notification['data'], true) ?? [];
        }

        return $notifications;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        return $this->db->update(
            'notifications',
            ['is_read' => true, 'read_at' => date('Y-m-d H:i:s')],
            ['id' => $notificationId, 'user_id' => $userId]
        ) > 0;
    }

    /**
     * Mark all user notifications as read
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->db->execute(
            "UPDATE notifications SET is_read = true, read_at = ? WHERE user_id = ? AND is_read = false",
            [date('Y-m-d H:i:s'), $userId]
        );
    }

    /**
     * Delete notification
     */
    public function deleteNotification(int $notificationId, int $userId): bool
    {
        return $this->db->delete('notifications', [
            'id' => $notificationId,
            'user_id' => $userId
        ]) > 0;
    }

    /**
     * Get unread count
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->db->count('notifications', [
            'user_id' => $userId,
            'is_read' => false
        ]);
    }

    /**
     * Check if notification should be sent based on user preferences
     */
    private function shouldSendNotification(int $userId, string $type): bool
    {
        $preferences = $this->getUserNotificationPreferences($userId);

        // Check if notification type is enabled
        return $preferences[$type]['in_app'] ?? true;
    }

    /**
     * Check if email should be sent
     */
    private function shouldSendEmail(int $userId, string $type): bool
    {
        $preferences = $this->getUserNotificationPreferences($userId);

        // Check if email notifications are enabled for this type
        return $preferences[$type]['email'] ?? false;
    }

    /**
     * Get user notification preferences
     */
    private function getUserNotificationPreferences(int $userId): array
    {
        $user = $this->db->find('users', $userId);

        if (!$user || empty($user['notification_settings'])) {
            return $this->getDefaultNotificationPreferences();
        }

        return json_decode($user['notification_settings'], true) ?: $this->getDefaultNotificationPreferences();
    }

    /**
     * Get default notification preferences
     */
    private function getDefaultNotificationPreferences(): array
    {
        return [
            'info' => ['in_app' => true, 'email' => false],
            'warning' => ['in_app' => true, 'email' => false],
            'error' => ['in_app' => true, 'email' => true],
            'success' => ['in_app' => true, 'email' => false],
            'system' => ['in_app' => true, 'email' => true],
            'security' => ['in_app' => true, 'email' => true]
        ];
    }

    /**
     * Update user notification preferences
     */
    public function updateUserPreferences(int $userId, array $preferences): bool
    {
        $currentPreferences = $this->getUserNotificationPreferences($userId);
        $updatedPreferences = array_merge($currentPreferences, $preferences);

        return $this->db->update('users', [
            'notification_settings' => json_encode($updatedPreferences)
        ], ['id' => $userId]) > 0;
    }

    /**
     * Get users by role
     */
    private function getUsersByRole(string $roleName): array
    {
        $users = $this->db->query(
            "SELECT ur.user_id
             FROM user_roles ur
             INNER JOIN roles r ON ur.role_id = r.id
             WHERE r.name = ? AND ur.deleted_at IS NULL",
            [$roleName]
        );

        return array_column($users, 'user_id');
    }

    /**
     * Send system notification
     */
    public function sendSystemNotification(
        string $title,
        string $message,
        array $data = []
    ): int {
        // Get all active users
        $users = $this->db->query(
            "SELECT id FROM users WHERE is_active = true AND is_verified = true"
        );

        $userIds = array_column($users, 'id');

        return $this->sendToUsers($userIds, $title, $message, 'system', $data);
    }

    /**
     * Send security alert
     */
    public function sendSecurityAlert(
        int $userId,
        string $title,
        string $message,
        array $data = []
    ): bool {
        // Always send security notifications
        $notificationId = $this->createNotification($userId, $title, $message, 'security', $data);

        // Always send email for security alerts
        $user = $this->db->find('users', $userId);
        if ($user && $user['is_verified']) {
            $this->email->sendNotification($user['email'], $title, $message, $data);
        }

        return $notificationId > 0;
    }

    /**
     * Clean old notifications
     */
    public function cleanOldNotifications(int $daysOld = 90): int
    {
        return $this->db->execute(
            "DELETE FROM notifications WHERE created_at < ?",
            [date('Y-m-d H:i:s', strtotime("-{$daysOld} days"))]
        );
    }

    /**
     * Get notification statistics
     */
    public function getStats(int $userId = null): array
    {
        $conditions = $userId ? ['user_id' => $userId] : [];

        $stats = [
            'total' => $this->db->count('notifications', $conditions),
            'unread' => $this->db->count('notifications', array_merge($conditions, ['is_read' => false])),
            'by_type' => []
        ];

        // Get count by type
        $typeStats = $this->db->query(
            "SELECT type, COUNT(*) as count FROM notifications " .
            ($userId ? "WHERE user_id = {$userId} " : "") .
            "GROUP BY type"
        );

        foreach ($typeStats as $stat) {
            $stats['by_type'][$stat['type']] = (int) $stat['count'];
        }

        return $stats;
    }
}
