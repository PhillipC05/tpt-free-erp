<?php

namespace TPT\ERP\Core;

/**
 * Rate Limiting System
 *
 * Implements various rate limiting strategies to protect against abuse
 * and ensure fair resource usage.
 */
class RateLimiter
{
    private Cache $cache;
    private array $config;

    public function __construct()
    {
        $this->cache = Cache::getInstance();
        $this->config = [
            'default_attempts' => (int) (getenv('RATE_LIMIT_ATTEMPTS') ?: 60),
            'default_decay' => (int) (getenv('RATE_LIMIT_DECAY') ?: 60), // seconds
            'throttle_attempts' => (int) (getenv('THROTTLE_ATTEMPTS') ?: 1000),
            'throttle_decay' => (int) (getenv('THROTTLE_DECAY') ?: 3600), // 1 hour
        ];
    }

    /**
     * Check if request is within rate limit
     */
    public function check(string $key, int $maxAttempts = null, int $decaySeconds = null): bool
    {
        $maxAttempts = $maxAttempts ?? $this->config['default_attempts'];
        $decaySeconds = $decaySeconds ?? $this->config['default_decay'];

        $attempts = $this->getAttempts($key);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        $this->incrementAttempts($key, $decaySeconds);
        return true;
    }

    /**
     * Check rate limit and throw exception if exceeded
     */
    public function checkOrFail(string $key, int $maxAttempts = null, int $decaySeconds = null): void
    {
        if (!$this->check($key, $maxAttempts, $decaySeconds)) {
            $this->throwRateLimitException($key, $maxAttempts ?? $this->config['default_attempts']);
        }
    }

    /**
     * Get remaining attempts
     */
    public function remaining(string $key, int $maxAttempts = null): int
    {
        $maxAttempts = $maxAttempts ?? $this->config['default_attempts'];
        $attempts = $this->getAttempts($key);

        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get attempts count
     */
    private function getAttempts(string $key): int
    {
        $cacheKey = $this->getCacheKey($key);
        return (int) $this->cache->get($cacheKey, 0);
    }

    /**
     * Increment attempts
     */
    private function incrementAttempts(string $key, int $decaySeconds): void
    {
        $cacheKey = $this->getCacheKey($key);
        $attempts = $this->getAttempts($key);

        $this->cache->set($cacheKey, $attempts + 1, $decaySeconds);
    }

    /**
     * Clear rate limit for key
     */
    public function clear(string $key): void
    {
        $cacheKey = $this->getCacheKey($key);
        $this->cache->delete($cacheKey);
    }

    /**
     * Get cache key
     */
    private function getCacheKey(string $key): string
    {
        return "rate_limit:{$key}";
    }

    /**
     * Create rate limit key for user
     */
    public function forUser(int $userId, string $action = 'default'): string
    {
        return "user:{$userId}:{$action}";
    }

    /**
     * Create rate limit key for IP
     */
    public function forIp(string $ip, string $action = 'default'): string
    {
        return "ip:{$ip}:{$action}";
    }

    /**
     * Create rate limit key for route
     */
    public function forRoute(string $method, string $uri, string $identifier = null): string
    {
        $key = "route:{$method}:{$uri}";
        if ($identifier) {
            $key .= ":{$identifier}";
        }
        return $key;
    }

    /**
     * Apply rate limiting to request
     */
    public function throttle(Request $request, int $maxAttempts = null, int $decaySeconds = null): void
    {
        $maxAttempts = $maxAttempts ?? $this->config['throttle_attempts'];
        $decaySeconds = $decaySeconds ?? $this->config['throttle_decay'];

        // Create key based on user or IP
        $user = $request->getUser();
        if ($user) {
            $key = $this->forUser($user['id'], 'api');
        } else {
            $key = $this->forIp($request->getClientIp(), 'api');
        }

        $this->checkOrFail($key, $maxAttempts, $decaySeconds);
    }

    /**
     * Get rate limit headers for response
     */
    public function getHeaders(string $key, int $maxAttempts = null): array
    {
        $maxAttempts = $maxAttempts ?? $this->config['default_attempts'];
        $attempts = $this->getAttempts($key);
        $remaining = max(0, $maxAttempts - $attempts);

        return [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => time() + ($this->config['default_decay']),
        ];
    }

    /**
     * Throw rate limit exception
     */
    private function throwRateLimitException(string $key, int $maxAttempts): void
    {
        $headers = $this->getHeaders($key, $maxAttempts);

        throw new RateLimitException(
            'API rate limit exceeded',
            429,
            null,
            $headers
        );
    }

    /**
     * Check if key is rate limited
     */
    public function isLimited(string $key, int $maxAttempts = null): bool
    {
        $maxAttempts = $maxAttempts ?? $this->config['default_attempts'];
        $attempts = $this->getAttempts($key);

        return $attempts >= $maxAttempts;
    }

    /**
     * Get time until reset
     */
    public function availableIn(string $key): int
    {
        $cacheKey = $this->getCacheKey($key);

        // This is a simplified implementation
        // In a real system, you'd need to track expiration time
        return $this->config['default_decay'];
    }

    /**
     * Create middleware for rate limiting
     */
    public function createMiddleware(int $maxAttempts = null, int $decaySeconds = null): callable
    {
        return function (Request $request, Response $response) use ($maxAttempts, $decaySeconds) {
            try {
                $this->throttle($request, $maxAttempts, $decaySeconds);

                // Add rate limit headers to response
                $user = $request->getUser();
                $key = $user ? $this->forUser($user['id'], 'api') : $this->forIp($request->getClientIp(), 'api');
                $headers = $this->getHeaders($key, $maxAttempts);

                foreach ($headers as $name => $value) {
                    $response->setHeader($name, $value);
                }

                return null; // Continue to next middleware
            } catch (RateLimitException $e) {
                return $response->json([
                    'error' => 'Too Many Requests',
                    'message' => 'API rate limit exceeded. Please try again later.',
                    'retry_after' => $this->availableIn($key)
                ], 429, $e->getHeaders());
            }
        };
    }
}

/**
 * Rate Limit Exception
 */
class RateLimitException extends \Exception
{
    private array $headers;

    public function __construct(string $message, int $code = 429, \Throwable $previous = null, array $headers = [])
    {
        parent::__construct($message, $code, $previous);
        $this->headers = $headers;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}

/**
 * Rate Limiting Middleware
 */
class RateLimitMiddleware
{
    private RateLimiter $limiter;
    private int $maxAttempts;
    private int $decaySeconds;

    public function __construct(int $maxAttempts = null, int $decaySeconds = null)
    {
        $this->limiter = new RateLimiter();
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }

    public function handle(Request $request, Response $response): ?Response
    {
        return $this->limiter->createMiddleware($this->maxAttempts, $this->decaySeconds)($request, $response);
    }
}

/**
 * IP-based Rate Limiting Middleware
 */
class IpRateLimitMiddleware extends RateLimitMiddleware
{
    public function handle(Request $request, Response $response): ?Response
    {
        $key = "ip:{$request->getClientIp()}:api";
        $this->limiter->checkOrFail($key, $this->maxAttempts, $this->decaySeconds);

        $headers = $this->limiter->getHeaders($key, $this->maxAttempts);
        foreach ($headers as $name => $value) {
            $response->setHeader($name, $value);
        }

        return null;
    }
}

/**
 * User-based Rate Limiting Middleware
 */
class UserRateLimitMiddleware extends RateLimitMiddleware
{
    public function handle(Request $request, Response $response): ?Response
    {
        $user = $request->getUser();

        if (!$user) {
            return $response->json(['error' => 'Unauthorized'], 401);
        }

        $key = "user:{$user['id']}:api";
        $this->limiter->checkOrFail($key, $this->maxAttempts, $this->decaySeconds);

        $headers = $this->limiter->getHeaders($key, $this->maxAttempts);
        foreach ($headers as $name => $value) {
            $response->setHeader($name, $value);
        }

        return null;
    }
}
