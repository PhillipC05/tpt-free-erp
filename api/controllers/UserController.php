<?php

namespace TPT\ERP\Api\Controllers;

/**
 * User Controller
 *
 * Handles user management operations including CRUD operations,
 * profile management, and user administration.
 */
class UserController extends BaseController
{
    /**
     * Get all users (admin only)
     */
    public function index()
    {
        // Check if user has permission to view users
        if (!$this->hasPermission('users.view')) {
            return $this->forbidden('Insufficient permissions to view users');
        }

        $page = (int) $this->request->getQuery('page', 1);
        $limit = (int) $this->request->getQuery('limit', 20);
        $search = $this->request->getQuery('search');
        $status = $this->request->getQuery('status');

        $offset = ($page - 1) * $limit;

        // Build query
        $whereConditions = [];
        $params = [];

        if ($search) {
            $whereConditions[] = "(first_name ILIKE ? OR last_name ILIKE ? OR email ILIKE ? OR username ILIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }

        if ($status) {
            $whereConditions[] = "is_active = ?";
            $params[] = $status === 'active' ? 'true' : 'false';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get users
        $users = $this->db->query(
            "SELECT id, uuid, email, username, first_name, last_name, display_name,
                    avatar_url, phone, timezone, language, is_active, is_verified,
                    last_login_at, failed_login_attempts, created_at, updated_at
             FROM users {$whereClause}
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        // Get total count
        $totalCount = $this->db->queryValue(
            "SELECT COUNT(*) FROM users {$whereClause}",
            $params
        );

        return $this->success([
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $totalCount,
                'pages' => ceil($totalCount / $limit)
            ]
        ]);
    }

    /**
     * Get current user profile
     */
    public function show()
    {
        $userId = $this->request->getRouteParam('id');

        // If no ID provided or user is requesting their own profile
        if (!$userId || $userId == $this->getUser()['id']) {
            $user = $this->getUser();
            $userId = $user['id'];
        } else {
            // Check if user has permission to view other users
            if (!$this->hasPermission('users.view')) {
                return $this->forbidden('Insufficient permissions to view user details');
            }
        }

        $user = $this->db->queryOne(
            "SELECT id, uuid, email, username, first_name, last_name, display_name,
                    avatar_url, phone, timezone, language, is_active, is_verified,
                    last_login_at, preferences, notification_settings, created_at, updated_at
             FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            return $this->notFound('User not found');
        }

        // Remove sensitive information for other users
        if ($userId != $this->getUser()['id'] && !$this->hasPermission('users.view_sensitive')) {
            unset($user['preferences']);
            unset($user['notification_settings']);
        }

        return $this->success(['user' => $user]);
    }

    /**
     * Create new user (admin only)
     */
    public function store()
    {
        if (!$this->hasPermission('users.create')) {
            return $this->forbidden('Insufficient permissions to create users');
        }

        $data = $this->request->getInput();

        // Validate input
        $errors = $this->validate($data, [
            'email' => ['required' => true, 'type' => 'email'],
            'password' => ['required' => true, 'min' => 8],
            'first_name' => ['required' => true, 'min' => 2, 'max' => 100],
            'last_name' => ['required' => true, 'min' => 2, 'max' => 100],
            'username' => ['min' => 3, 'max' => 50]
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Check if email already exists
        $existingUser = $this->db->queryOne(
            "SELECT id FROM users WHERE email = ?",
            [$data['email']]
        );

        if ($existingUser) {
            return $this->error('Email already registered', 409);
        }

        // Check if username already exists (if provided)
        if (!empty($data['username'])) {
            $existingUser = $this->db->queryOne(
                "SELECT id FROM users WHERE username = ?",
                [$data['username']]
            );

            if ($existingUser) {
                return $this->error('Username already taken', 409);
            }
        }

        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        // Create user
        $userId = $this->db->insert('users', [
            'email' => $data['email'],
            'username' => $data['username'] ?? null,
            'password_hash' => $passwordHash,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'display_name' => $data['display_name'] ?? trim($data['first_name'] . ' ' . $data['last_name']),
            'phone' => $data['phone'] ?? null,
            'timezone' => $data['timezone'] ?? 'UTC',
            'language' => $data['language'] ?? 'en',
            'is_active' => $data['is_active'] ?? true,
            'is_verified' => $data['is_verified'] ?? false,
            'preferences' => $data['preferences'] ?? '{}',
            'notification_settings' => $data['notification_settings'] ?? '{}',
            'created_by' => $this->getUser()['id'],
            'updated_by' => $this->getUser()['id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Log user creation
        $this->logAudit('create', 'users', $userId, 'User created by admin');

        return $this->success([
            'user' => [
                'id' => $userId,
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name']
            ],
            'message' => 'User created successfully'
        ], 'User created successfully', 201);
    }

    /**
     * Update user
     */
    public function update()
    {
        $userId = $this->request->getRouteParam('id');
        $data = $this->request->getInput();

        // Check permissions
        $currentUser = $this->getUser();
        $isOwnProfile = $userId == $currentUser['id'];

        if (!$isOwnProfile && !$this->hasPermission('users.update')) {
            return $this->forbidden('Insufficient permissions to update user');
        }

        // Validate input
        $errors = $this->validate($data, [
            'email' => ['type' => 'email'],
            'first_name' => ['min' => 2, 'max' => 100],
            'last_name' => ['min' => 2, 'max' => 100],
            'username' => ['min' => 3, 'max' => 50],
            'phone' => ['pattern' => '/^\+?[0-9\s\-\(\)]+$/']
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Check if user exists
        $existingUser = $this->db->find('users', $userId);
        if (!$existingUser) {
            return $this->notFound('User not found');
        }

        // Check email uniqueness (if changing)
        if (!empty($data['email']) && $data['email'] !== $existingUser['email']) {
            $emailExists = $this->db->queryOne(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$data['email'], $userId]
            );

            if ($emailExists) {
                return $this->error('Email already in use', 409);
            }
        }

        // Check username uniqueness (if changing)
        if (!empty($data['username']) && $data['username'] !== $existingUser['username']) {
            $usernameExists = $this->db->queryOne(
                "SELECT id FROM users WHERE username = ? AND id != ?",
                [$data['username'], $userId]
            );

            if ($usernameExists) {
                return $this->error('Username already taken', 409);
            }
        }

        // Prepare update data
        $updateData = [];
        $allowedFields = [
            'email', 'username', 'first_name', 'last_name', 'display_name',
            'phone', 'timezone', 'language', 'preferences', 'notification_settings'
        ];

        // Admin-only fields
        if ($this->hasPermission('users.update')) {
            $allowedFields = array_merge($allowedFields, ['is_active', 'is_verified']);
        }

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_by'] = $currentUser['id'];
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            $this->db->update('users', $updateData, ['id' => $userId]);

            // Log update
            $this->logAudit('update', 'users', $userId, 'User profile updated');
        }

        return $this->success([
            'message' => 'User updated successfully'
        ]);
    }

    /**
     * Delete user (admin only)
     */
    public function destroy()
    {
        if (!$this->hasPermission('users.delete')) {
            return $this->forbidden('Insufficient permissions to delete users');
        }

        $userId = $this->request->getRouteParam('id');

        // Check if user exists
        $user = $this->db->find('users', $userId);
        if (!$user) {
            return $this->notFound('User not found');
        }

        // Prevent self-deletion
        if ($userId == $this->getUser()['id']) {
            return $this->error('Cannot delete your own account', 400);
        }

        // Soft delete by setting deleted_at
        $this->db->update('users', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->getUser()['id']
        ], ['id' => $userId]);

        // Log deletion
        $this->logAudit('delete', 'users', $userId, 'User deleted');

        return $this->success(['message' => 'User deleted successfully']);
    }

    /**
     * Change password
     */
    public function changePassword()
    {
        $userId = $this->request->getRouteParam('id');
        $data = $this->request->getInput();

        // Check permissions
        $currentUser = $this->getUser();
        $isOwnPassword = $userId == $currentUser['id'];

        if (!$isOwnPassword && !$this->hasPermission('users.update_password')) {
            return $this->forbidden('Insufficient permissions to change user password');
        }

        // Validate input
        $errors = $this->validate($data, [
            'current_password' => ['required' => $isOwnPassword],
            'new_password' => ['required' => true, 'min' => 8],
            'confirm_password' => ['required' => true]
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Check password confirmation
        if ($data['new_password'] !== $data['confirm_password']) {
            return $this->error('Password confirmation does not match', 400);
        }

        // Get user
        $user = $this->db->find('users', $userId);
        if (!$user) {
            return $this->notFound('User not found');
        }

        // Verify current password (if changing own password)
        if ($isOwnPassword) {
            if (!password_verify($data['current_password'], $user['password_hash'])) {
                return $this->error('Current password is incorrect', 400);
            }
        }

        // Hash new password
        $newPasswordHash = password_hash($data['new_password'], PASSWORD_DEFAULT);

        // Update password
        $this->db->update('users', [
            'password_hash' => $newPasswordHash,
            'password_changed_at' => date('Y-m-d H:i:s'),
            'updated_by' => $currentUser['id']
        ], ['id' => $userId]);

        // Log password change
        $this->logAudit('password_change', 'users', $userId, 'Password changed');

        return $this->success(['message' => 'Password changed successfully']);
    }

    /**
     * Log audit event
     */
    private function logAudit(string $action, string $table, int $recordId, string $description): void
    {
        $this->db->insert('audit_log', [
            'user_id' => $this->getUser()['id'] ?? null,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'description' => $description,
            'ip_address' => $this->request->getClientIp(),
            'user_agent' => $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
