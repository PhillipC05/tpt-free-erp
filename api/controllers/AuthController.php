<?php

namespace TPT\ERP\Api\Controllers;

use TPT\ERP\Core\Database;

/**
 * Authentication Controller
 *
 * Handles user authentication, registration, and token management.
 */
class AuthController extends BaseController
{
    /**
     * User login
     */
    public function login()
    {
        $data = $this->request->getInput();

        // Validate input
        $errors = $this->validate($data, [
            'email' => ['required' => true, 'type' => 'email'],
            'password' => ['required' => true, 'min' => 6]
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Find user by email
        $user = $this->db->queryOne(
            "SELECT id, email, password_hash, is_active, is_verified FROM users WHERE email = ?",
            [$data['email']]
        );

        if (!$user) {
            return $this->error('Invalid credentials', 401);
        }

        // Check if user is active
        if (!$user['is_active']) {
            return $this->error('Account is deactivated', 401);
        }

        // Check if user is verified
        if (!$user['is_verified']) {
            return $this->error('Please verify your email before logging in', 401);
        }

        // Verify password
        if (!password_verify($data['password'], $user['password_hash'])) {
            // Log failed login attempt
            $this->logFailedLogin($user['id'], $this->request->getClientIp());
            return $this->error('Invalid credentials', 401);
        }

        // Generate JWT token
        $token = $this->generateToken($user);

        // Update last login
        $this->db->execute(
            "UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?",
            [$this->request->getClientIp(), $user['id']]
        );

        // Log successful login
        $this->logAudit('login', 'users', $user['id'], 'User logged in successfully');

        return $this->success([
            'user' => [
                'id' => $user['id'],
                'email' => $user['email']
            ],
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600 // 1 hour
        ], 'Login successful');
    }

    /**
     * User registration
     */
    public function register()
    {
        $data = $this->request->getInput();

        // Validate input
        $errors = $this->validate($data, [
            'email' => ['required' => true, 'type' => 'email'],
            'password' => ['required' => true, 'min' => 8],
            'first_name' => ['required' => true, 'min' => 2, 'max' => 100],
            'last_name' => ['required' => true, 'min' => 2, 'max' => 100]
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

        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        // Generate verification token
        $verificationToken = bin2hex(random_bytes(32));

        // Create user
        $userId = $this->db->insert('users', [
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'is_active' => true,
            'is_verified' => false,
            'email_verified_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Log user registration
        $this->logAudit('register', 'users', $userId, 'User registered successfully');

        // TODO: Send verification email
        // $this->sendVerificationEmail($data['email'], $verificationToken);

        return $this->success([
            'user' => [
                'id' => $userId,
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name']
            ],
            'message' => 'Registration successful. Please check your email for verification.'
        ], 'Registration successful', 201);
    }

    /**
     * Refresh authentication token
     */
    public function refresh()
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->unauthorized();
        }

        // Generate new token
        $token = $this->generateToken($user);

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ], 'Token refreshed successfully');
    }

    /**
     * Generate JWT token
     */
    private function generateToken(array $user): string
    {
        $payload = [
            'iss' => getenv('APP_URL') ?: 'tpt-erp.local',
            'aud' => getenv('APP_URL') ?: 'tpt-erp.local',
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour
            'sub' => $user['id'],
            'email' => $user['email']
        ];

        return \Firebase\JWT\JWT::encode($payload, getenv('JWT_SECRET') ?: 'your-secret-key', 'HS256');
    }

    /**
     * Log failed login attempt
     */
    private function logFailedLogin(int $userId, string $ip): void
    {
        $this->db->execute(
            "UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?",
            [$userId]
        );

        $this->logAudit('failed_login', 'users', $userId, "Failed login attempt from IP: {$ip}");
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
