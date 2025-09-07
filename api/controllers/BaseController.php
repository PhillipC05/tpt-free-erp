<?php

namespace TPT\ERP\Api\Controllers;

use TPT\ERP\Core\Request;
use TPT\ERP\Core\Response;
use TPT\ERP\Core\Database;

/**
 * Base API Controller
 *
 * Provides common functionality for all API controllers including
 * request/response handling, authentication, and validation.
 */
abstract class BaseController
{
    protected Request $request;
    protected Response $response;
    protected Database $db;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->db = Database::getInstance();
    }

    /**
     * Get authenticated user
     */
    protected function getUser()
    {
        return $this->request->getUser();
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission(string $permission): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        // Check user permissions (implement based on your permission system)
        return $this->checkUserPermission($user['id'], $permission);
    }

    /**
     * Validate request data
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "{$field} is required";
                continue;
            }

            if (!empty($value)) {
                if (isset($rule['type'])) {
                    if (!$this->validateType($value, $rule['type'])) {
                        $errors[$field] = "{$field} must be of type {$rule['type']}";
                    }
                }

                if (isset($rule['min'])) {
                    if (is_string($value) && strlen($value) < $rule['min']) {
                        $errors[$field] = "{$field} must be at least {$rule['min']} characters";
                    } elseif (is_numeric($value) && $value < $rule['min']) {
                        $errors[$field] = "{$field} must be at least {$rule['min']}";
                    }
                }

                if (isset($rule['max'])) {
                    if (is_string($value) && strlen($value) > $rule['max']) {
                        $errors[$field] = "{$field} must be at most {$rule['max']} characters";
                    } elseif (is_numeric($value) && $value > $rule['max']) {
                        $errors[$field] = "{$field} must be at most {$rule['max']}";
                    }
                }

                if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                    $errors[$field] = "{$field} format is invalid";
                }
            }
        }

        return $errors;
    }

    /**
     * Validate data type
     */
    private function validateType($value, string $type): bool
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'int':
            case 'integer':
                return is_int($value) || (is_string($value) && ctype_digit($value));
            case 'float':
            case 'double':
                return is_float($value) || is_int($value);
            case 'bool':
            case 'boolean':
                return is_bool($value) || in_array(strtolower($value), ['true', 'false', '1', '0']);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'uuid':
                return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
            default:
                return true;
        }
    }

    /**
     * Check user permission
     */
    protected function checkUserPermission(int $userId, string $permission): bool
    {
        // Check if user has the permission through their roles
        $hasPermission = $this->db->queryValue(
            "SELECT COUNT(*)
             FROM user_roles ur
             INNER JOIN role_permissions rp ON ur.role_id = rp.role_id
             INNER JOIN permissions p ON rp.permission_id = p.id
             WHERE ur.user_id = ?
             AND p.name = ?
             AND ur.deleted_at IS NULL
             AND rp.deleted_at IS NULL
             AND p.deleted_at IS NULL",
            [$userId, $permission]
        );

        return $hasPermission > 0;
    }

    /**
     * Send success response
     */
    protected function success($data = null, string $message = 'Success', int $statusCode = 200): Response
    {
        return $this->response->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send error response
     */
    protected function error(string $message = 'Error', int $statusCode = 400, $errors = null): Response
    {
        return $this->response->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Send validation error response
     */
    protected function validationError(array $errors): Response
    {
        return $this->error('Validation failed', 422, $errors);
    }

    /**
     * Send unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): Response
    {
        return $this->error($message, 401);
    }

    /**
     * Send forbidden response
     */
    protected function forbidden(string $message = 'Forbidden'): Response
    {
        return $this->error($message, 403);
    }

    /**
     * Send not found response
     */
    protected function notFound(string $message = 'Not found'): Response
    {
        return $this->error($message, 404);
    }
}
