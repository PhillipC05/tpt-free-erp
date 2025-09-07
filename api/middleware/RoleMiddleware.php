<?php

namespace TPT\ERP\Api\Middleware;

use TPT\ERP\Core\Request;
use TPT\ERP\Core\Response;
use TPT\ERP\Core\Database;

/**
 * Role-Based Access Control Middleware
 *
 * Checks if user has required role(s) to access a resource.
 */
class RoleMiddleware
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Handle the middleware
     *
     * @param array $roles Required role(s) - can be string or array
     */
    public function handle(Request $request, Response $response, array $roles = []): ?Response
    {
        $user = $request->getUser();

        if (!$user) {
            return $response->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required'
            ], 401);
        }

        // If no specific roles required, just check if user is authenticated
        if (empty($roles)) {
            return null;
        }

        // Get user roles
        $userRoles = $this->getUserRoles($user['id']);

        // Check if user has any of the required roles
        $hasRequiredRole = false;
        foreach ($roles as $requiredRole) {
            if (in_array($requiredRole, $userRoles)) {
                $hasRequiredRole = true;
                break;
            }
        }

        if (!$hasRequiredRole) {
            return $response->json([
                'error' => 'Forbidden',
                'message' => 'Insufficient role permissions'
            ], 403);
        }

        return null; // Continue to next middleware/route
    }

    /**
     * Get user roles
     */
    private function getUserRoles(int $userId): array
    {
        $roles = $this->db->query(
            "SELECT r.name
             FROM roles r
             INNER JOIN user_roles ur ON r.id = ur.role_id
             WHERE ur.user_id = ? AND ur.deleted_at IS NULL",
            [$userId]
        );

        return array_column($roles, 'name');
    }
}
