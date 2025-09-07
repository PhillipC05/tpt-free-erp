<?php

namespace TPT\ERP\Core;

/**
 * HTTP Request Handler
 *
 * Handles HTTP request parsing, input validation, and authentication.
 */
class Request
{
    private array $headers;
    private array $query;
    private array $body;
    private array $files;
    private array $server;
    private ?array $user = null;
    private string $method;
    private string $uri;
    private string $contentType;
    private array $routeParams = [];

    public function __construct()
    {
        $this->headers = $this->getAllHeaders();
        $this->query = $_GET;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        $this->parseBody();
        $this->authenticateUser();
    }

    /**
     * Get all HTTP headers
     */
    private function getAllHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        return $headers;
    }

    /**
     * Parse request body based on content type
     */
    private function parseBody(): void
    {
        $this->body = $_POST;

        if ($this->isJson()) {
            $json = file_get_contents('php://input');
            if ($json) {
                $parsed = json_decode($json, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->body = array_merge($this->body, $parsed);
                }
            }
        }
    }

    /**
     * Authenticate user from token/session
     */
    private function authenticateUser(): void
    {
        // Check for Bearer token
        $authHeader = $this->getHeader('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $this->user = $this->validateToken($token);
        }

        // Check for session-based auth
        if (!$this->user && isset($_SESSION['user_id'])) {
            $this->user = $this->getUserFromSession($_SESSION['user_id']);
        }
    }

    /**
     * Validate JWT token (placeholder - implement based on your JWT library)
     */
    private function validateToken(string $token): ?array
    {
        try {
            // Use firebase/php-jwt from composer.json
            $decoded = \Firebase\JWT\JWT::decode($token, getenv('JWT_SECRET') ?: 'your-secret-key', ['HS256']);
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get user from session
     */
    private function getUserFromSession(int $userId): ?array
    {
        // This would query the database - placeholder for now
        return [
            'id' => $userId,
            'email' => 'user@example.com',
            'name' => 'User'
        ];
    }

    /**
     * Get request method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get request URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get query parameter
     */
    public function getQuery(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * Get body parameter
     */
    public function getBody(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->body;
        }

        return $this->body[$key] ?? $default;
    }

    /**
     * Get all input (query + body)
     */
    public function getInput(string $key = null, $default = null)
    {
        $input = array_merge($this->query, $this->body);

        if ($key === null) {
            return $input;
        }

        return $input[$key] ?? $default;
    }

    /**
     * Get uploaded file
     */
    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get HTTP header
     */
    public function getHeader(string $key, string $default = ''): string
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get authenticated user
     */
    public function getUser(): ?array
    {
        return $this->user;
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Check if request is JSON
     */
    public function isJson(): bool
    {
        return strpos($this->contentType, 'application/json') !== false;
    }

    /**
     * Get client IP address
     */
    public function getClientIp(): string
    {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipHeaders as $header) {
            if (isset($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get user agent
     */
    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Set route parameters
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Get route parameter
     */
    public function getRouteParam(string $key, $default = null)
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Get all route parameters
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }
}
