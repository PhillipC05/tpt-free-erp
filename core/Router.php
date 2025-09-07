<?php

namespace TPT\ERP\Core;

use TPT\ERP\Api\Controllers\BaseController;

/**
 * HTTP Router
 *
 * Handles URL routing to controllers and middleware execution.
 */
class Router
{
    private array $routes = [];
    private array $middleware = [];
    private array $globalMiddleware = [];
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Add a route
     */
    public function addRoute(string $method, string $path, $handler, array $middleware = []): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
            'params' => []
        ];

        return $this;
    }

    /**
     * Add GET route
     */
    public function get(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Add POST route
     */
    public function post(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Add PUT route
     */
    public function put(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Add DELETE route
     */
    public function delete(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Add PATCH route
     */
    public function patch(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    /**
     * Add OPTIONS route
     */
    public function options(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('OPTIONS', $path, $handler, $middleware);
    }

    /**
     * Add resource routes (RESTful routes for a resource)
     */
    public function resource(string $name, string $controller, array $middleware = []): self
    {
        $basePath = '/' . ltrim($name, '/');

        $this->get($basePath, [$controller, 'index'], $middleware);
        $this->post($basePath, [$controller, 'store'], $middleware);
        $this->get($basePath . '/{id}', [$controller, 'show'], $middleware);
        $this->put($basePath . '/{id}', [$controller, 'update'], $middleware);
        $this->patch($basePath . '/{id}', [$controller, 'update'], $middleware);
        $this->delete($basePath . '/{id}', [$controller, 'destroy'], $middleware);

        return $this;
    }

    /**
     * Add global middleware
     */
    public function addGlobalMiddleware(string $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    /**
     * Group routes with common middleware
     */
    public function group(array $middleware, callable $callback): self
    {
        $previousMiddleware = $this->middleware;
        $this->middleware = array_merge($this->middleware, $middleware);

        $callback($this);

        $this->middleware = $previousMiddleware;

        return $this;
    }

    /**
     * Dispatch the request
     */
    public function dispatch(): Response
    {
        $method = $this->request->getMethod();
        $uri = $this->request->getUri();

        // Find matching route
        $route = $this->findRoute($method, $uri);

        if (!$route) {
            return $this->response->json(['error' => 'Route not found'], 404);
        }

        // Execute middleware
        $middleware = array_merge($this->globalMiddleware, $this->middleware, $route['middleware']);
        $response = $this->executeMiddleware($middleware);

        if ($response) {
            return $response;
        }

        // Execute handler
        return $this->executeHandler($route['handler'], $route['params']);
    }

    /**
     * Find matching route
     */
    private function findRoute(string $method, string $uri): ?array
    {
        foreach ($this->routes as &$route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['path'], $uri);

            if ($params !== false) {
                $route['params'] = $params;
                return $route;
            }
        }

        return null;
    }

    /**
     * Match route pattern with URI
     */
    private function matchRoute(string $pattern, string $uri): array|false
    {
        // Convert route pattern to regex
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (!is_numeric($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return false;
    }

    /**
     * Execute middleware stack
     */
    private function executeMiddleware(array $middleware): ?Response
    {
        foreach ($middleware as $middlewareClass) {
            if (is_string($middlewareClass)) {
                $middlewareInstance = new $middlewareClass();
                $response = $middlewareInstance->handle($this->request, $this->response);

                if ($response instanceof Response) {
                    return $response;
                }
            }
        }

        return null;
    }

    /**
     * Execute route handler
     */
    private function executeHandler($handler, array $params): Response
    {
        try {
            if (is_array($handler)) {
                // Controller method
                [$controllerClass, $method] = $handler;

                if (!class_exists($controllerClass)) {
                    throw new \Exception("Controller class {$controllerClass} not found");
                }

                $controller = new $controllerClass($this->request, $this->response);

                if (!method_exists($controller, $method)) {
                    throw new \Exception("Method {$method} not found in controller {$controllerClass}");
                }

                // Inject route parameters
                $this->request->setRouteParams($params);

                return $controller->$method();
            } elseif (is_callable($handler)) {
                // Closure or function
                return $handler($this->request, $this->response, $params);
            } else {
                throw new \Exception('Invalid route handler');
            }
        } catch (\Exception $e) {
            // Log error
            error_log('Router error: ' . $e->getMessage());

            return $this->response->json([
                'error' => 'Internal server error',
                'message' => getenv('APP_DEBUG') ? $e->getMessage() : 'Something went wrong'
            ], 500);
        }
    }

    /**
     * Get all routes (for debugging)
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
