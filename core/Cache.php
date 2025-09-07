<?php

namespace TPT\ERP\Core;

/**
 * Cache System
 *
 * Provides unified caching interface with Redis/Memcached support and file fallback.
 */
class Cache
{
    private static ?Cache $instance = null;
    private $cacheDriver;
    private string $cachePath;
    private array $config;

    private function __construct()
    {
        $this->config = [
            'driver' => getenv('CACHE_DRIVER') ?: 'file',
            'redis_host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'redis_port' => (int) (getenv('REDIS_PORT') ?: 6379),
            'redis_password' => getenv('REDIS_PASSWORD'),
            'redis_database' => (int) (getenv('REDIS_DB') ?: 0),
            'memcached_host' => getenv('MEMCACHED_HOST') ?: '127.0.0.1',
            'memcached_port' => (int) (getenv('MEMCACHED_PORT') ?: 11211),
            'ttl' => (int) (getenv('CACHE_TTL') ?: 3600), // 1 hour default
        ];

        $this->cachePath = __DIR__ . '/../storage/cache';
        $this->ensureCacheDirectory();

        $this->initializeDriver();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): Cache
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize cache driver
     */
    private function initializeDriver(): void
    {
        switch ($this->config['driver']) {
            case 'redis':
                $this->initializeRedis();
                break;
            case 'memcached':
                $this->initializeMemcached();
                break;
            case 'file':
            default:
                $this->initializeFileCache();
                break;
        }
    }

    /**
     * Initialize Redis driver
     */
    private function initializeRedis(): void
    {
        if (!extension_loaded('redis')) {
            $this->initializeFileCache();
            return;
        }

        try {
            $this->cacheDriver = new \Redis();
            $this->cacheDriver->connect(
                $this->config['redis_host'],
                $this->config['redis_port']
            );

            if ($this->config['redis_password']) {
                $this->cacheDriver->auth($this->config['redis_password']);
            }

            $this->cacheDriver->select($this->config['redis_database']);
        } catch (\Exception $e) {
            // Fallback to file cache
            $this->initializeFileCache();
        }
    }

    /**
     * Initialize Memcached driver
     */
    private function initializeMemcached(): void
    {
        if (!extension_loaded('memcached')) {
            $this->initializeFileCache();
            return;
        }

        try {
            $this->cacheDriver = new \Memcached();
            $this->cacheDriver->addServer(
                $this->config['memcached_host'],
                $this->config['memcached_port']
            );
        } catch (\Exception $e) {
            // Fallback to file cache
            $this->initializeFileCache();
        }
    }

    /**
     * Initialize file-based cache
     */
    private function initializeFileCache(): void
    {
        $this->config['driver'] = 'file';
        $this->cacheDriver = 'file';
    }

    /**
     * Get cache key
     */
    private function getCacheKey(string $key): string
    {
        return 'tpt_erp:' . md5($key);
    }

    /**
     * Get value from cache
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = $this->getCacheKey($key);

        switch ($this->config['driver']) {
            case 'redis':
                $value = $this->cacheDriver->get($cacheKey);
                return $value === false ? $default : unserialize($value);

            case 'memcached':
                $value = $this->cacheDriver->get($cacheKey);
                return $value === false ? $default : unserialize($value);

            case 'file':
            default:
                return $this->getFileCache($cacheKey, $default);
        }
    }

    /**
     * Set value in cache
     */
    public function set(string $key, $value, int $ttl = null): bool
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? $this->config['ttl'];
        $serializedValue = serialize($value);

        switch ($this->config['driver']) {
            case 'redis':
                return $this->cacheDriver->setex($cacheKey, $ttl, $serializedValue);

            case 'memcached':
                return $this->cacheDriver->set($cacheKey, $serializedValue, $ttl);

            case 'file':
            default:
                return $this->setFileCache($cacheKey, $serializedValue, $ttl);
        }
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);

        switch ($this->config['driver']) {
            case 'redis':
                return $this->cacheDriver->exists($cacheKey);

            case 'memcached':
                return $this->cacheDriver->get($cacheKey) !== false;

            case 'file':
            default:
                return $this->hasFileCache($cacheKey);
        }
    }

    /**
     * Delete key from cache
     */
    public function delete(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);

        switch ($this->config['driver']) {
            case 'redis':
                return $this->cacheDriver->del($cacheKey) > 0;

            case 'memcached':
                return $this->cacheDriver->delete($cacheKey);

            case 'file':
            default:
                return $this->deleteFileCache($cacheKey);
        }
    }

    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        switch ($this->config['driver']) {
            case 'redis':
                return $this->cacheDriver->flushDB();

            case 'memcached':
                return $this->cacheDriver->flush();

            case 'file':
            default:
                return $this->clearFileCache();
        }
    }

    /**
     * Get or set cache value
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $value = $this->get($key);

        if ($value === null) {
            $value = $callback();
            $this->set($key, $value, $ttl);
        }

        return $value;
    }

    /**
     * Increment numeric value
     */
    public function increment(string $key, int $value = 1): int|false
    {
        $cacheKey = $this->getCacheKey($key);

        switch ($this->config['driver']) {
            case 'redis':
                return $this->cacheDriver->incrBy($cacheKey, $value);

            case 'memcached':
                return $this->cacheDriver->increment($cacheKey, $value);

            case 'file':
            default:
                $current = $this->get($key, 0);
                $newValue = $current + $value;
                $this->set($key, $newValue);
                return $newValue;
        }
    }

    /**
     * Decrement numeric value
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        $cacheKey = $this->getCacheKey($key);

        switch ($this->config['driver']) {
            case 'redis':
                return $this->cacheDriver->decrBy($cacheKey, $value);

            case 'memcached':
                return $this->cacheDriver->decrement($cacheKey, $value);

            case 'file':
            default:
                $current = $this->get($key, 0);
                $newValue = $current - $value;
                $this->set($key, $newValue);
                return $newValue;
        }
    }

    /**
     * Get file cache
     */
    private function getFileCache(string $key, $default = null)
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return $default;
        }

        $data = unserialize($content);
        if (!$data || !isset($data['expires']) || time() > $data['expires']) {
            unlink($file);
            return $default;
        }

        return $data['value'];
    }

    /**
     * Set file cache
     */
    private function setFileCache(string $key, string $value, int $ttl): bool
    {
        $file = $this->getCacheFile($key);
        $this->ensureDirectory(dirname($file));

        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * Check if file cache exists
     */
    private function hasFileCache(string $key): bool
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return false;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return false;
        }

        $data = unserialize($content);
        if (!$data || !isset($data['expires'])) {
            return false;
        }

        if (time() > $data['expires']) {
            unlink($file);
            return false;
        }

        return true;
    }

    /**
     * Delete file cache
     */
    private function deleteFileCache(string $key): bool
    {
        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    /**
     * Clear all file cache
     */
    private function clearFileCache(): bool
    {
        $files = glob($this->cachePath . '/*.cache');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Get cache file path
     */
    private function getCacheFile(string $key): string
    {
        return $this->cachePath . '/' . $key . '.cache';
    }

    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Ensure directory exists
     */
    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $stats = [
            'driver' => $this->config['driver'],
            'hits' => 0,
            'misses' => 0,
            'uptime' => 0
        ];

        switch ($this->config['driver']) {
            case 'redis':
                try {
                    $info = $this->cacheDriver->info();
                    $stats['uptime'] = $info['uptime_in_seconds'] ?? 0;
                } catch (\Exception $e) {
                    // Ignore
                }
                break;

            case 'file':
                $files = glob($this->cachePath . '/*.cache');
                $stats['files'] = count($files);
                break;
        }

        return $stats;
    }

    /**
     * Cache database query results
     */
    public function rememberQuery(string $query, array $params = [], int $ttl = null)
    {
        $key = 'query:' . md5($query . serialize($params));
        return $this->remember($key, $ttl ?? $this->config['ttl'], function () use ($query, $params) {
            $db = Database::getInstance();
            return $db->query($query, $params);
        });
    }

    /**
     * Cache user data
     */
    public function rememberUser(int $userId, int $ttl = null)
    {
        $key = 'user:' . $userId;
        return $this->remember($key, $ttl ?? $this->config['ttl'], function () use ($userId) {
            $db = Database::getInstance();
            return $db->find('users', $userId);
        });
    }

    /**
     * Invalidate user cache
     */
    public function forgetUser(int $userId): void
    {
        $this->delete('user:' . $userId);
    }

    /**
     * Cache with tags (simple implementation)
     */
    public function tags(array $tags): TaggedCache
    {
        return new TaggedCache($this, $tags);
    }
}

/**
 * Tagged Cache Helper
 */
class TaggedCache
{
    private Cache $cache;
    private array $tags;

    public function __construct(Cache $cache, array $tags)
    {
        $this->cache = $cache;
        $this->tags = $tags;
    }

    public function get(string $key, $default = null)
    {
        return $this->cache->get($this->getTaggedKey($key), $default);
    }

    public function set(string $key, $value, int $ttl = null): bool
    {
        return $this->cache->set($this->getTaggedKey($key), $value, $ttl);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($this->getTaggedKey($key));
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($this->getTaggedKey($key));
    }

    public function flush(): bool
    {
        // Simple implementation - in production, you'd track tagged keys
        return true;
    }

    private function getTaggedKey(string $key): string
    {
        return implode(':', $this->tags) . ':' . $key;
    }
}
