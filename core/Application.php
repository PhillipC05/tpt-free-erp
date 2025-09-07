<?php

namespace TPT\ERP\Core;

/**
 * Main Application Class
 *
 * Handles the application lifecycle and routing.
 */
class Application
{
    private Router $router;
    private Request $request;
    private Response $response;

    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);

        $this->setupRoutes();
    }

    /**
     * Set up application routes
     */
    private function setupRoutes(): void
    {
        // API routes
        $this->router->group(['TPT\ERP\Api\Middleware\AuthMiddleware'], function ($router) {
            // User routes
            $router->resource('users', 'TPT\ERP\Api\Controllers\UserController');

            // Project routes
            $router->resource('projects', 'TPT\ERP\Api\Controllers\ProjectController');

            // Task routes
            $router->resource('tasks', 'TPT\ERP\Api\Controllers\TaskController');

            // Time tracking routes
            $router->resource('time-entries', 'TPT\ERP\Api\Controllers\TimeEntryController');
        });

        // Public routes (no auth required)
        $this->router->post('/auth/login', 'TPT\ERP\Api\Controllers\AuthController@login');
        $this->router->post('/auth/register', 'TPT\ERP\Api\Controllers\AuthController@register');
        $this->router->post('/auth/refresh', 'TPT\ERP\Api\Controllers\AuthController@refresh');

        // Health check
        $this->router->get('/health', function ($request, $response) {
            return $response->json([
                'status' => 'healthy',
                'timestamp' => date('c'),
                'version' => '1.0.0'
            ]);
        });

        // API documentation
        $this->router->get('/docs', 'TPT\ERP\Api\Controllers\DocsController@index');
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            // Handle CORS
            $this->handleCors();

            // Dispatch the request
            $response = $this->router->dispatch();

            // Send the response
            $response->send();

        } catch (\Throwable $e) {
            // Log the error
            error_log('Application error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            // Send error response
            $this->response->json([
                'error' => 'Internal server error',
                'message' => getenv('APP_DEBUG') ? $e->getMessage() : 'Something went wrong'
            ], 500)->send();
        }
    }

    /**
     * Handle CORS headers
     */
    private function handleCors(): void
    {
        $this->response->setCorsHeaders(
            origin: getenv('CORS_ORIGIN') ?: '*',
            methods: 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            headers: 'Content-Type, Authorization, X-Requested-With, X-API-Key',
            credentials: true
        );

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->response->setStatusCode(200)->send();
            exit;
        }
    }

    /**
     * Get the router instance
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get the request instance
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get the response instance
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
