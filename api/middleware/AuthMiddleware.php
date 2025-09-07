<?php

namespace TPT\ERP\Api\Middleware;

use TPT\ERP\Core\Request;
use TPT\ERP\Core\Response;

/**
 * Authentication Middleware
 *
 * Checks if user is authenticated before allowing access to protected routes.
 */
class AuthMiddleware
{
    /**
     * Handle the middleware
     */
    public function handle(Request $request, Response $response): ?Response
    {
        if (!$request->isAuthenticated()) {
            return $response->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required'
            ], 401)->setHeader('WWW-Authenticate', 'Bearer');
        }

        return null; // Continue to next middleware/route
    }
}
