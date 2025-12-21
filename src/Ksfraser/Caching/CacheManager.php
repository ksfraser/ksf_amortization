<?php

namespace Ksfraser\Caching;

/**
 * CacheManager - Central cache management and coordination
 * 
 * Provides dependency injection, configuration, and invalidation strategies
 * Supports tagging for batch invalidation
 * 
 * @package    Ksfraser\Caching
 * @since      20251221
 */
class CacheManager
{
    /**
     * @var CacheInterface The cache backend
     */
    private CacheInterface $cache;

    /**
     * @var array Tag to keys mapping for invalidation
     */
    private array $tags = [];

    /**
     * Constructor
     * 
     * @param CacheInterface $cache Cache implementation to use
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Create a named cache key with prefix
     * 
     * @param string $namespace Namespace prefix
     * @param string $key Key name
     * @return string Full cache key
     */
    public function key(string $namespace, string $key): string
    {
        return "{$namespace}:{$key}";
    }

    /**
     * Get from cache with namespace
     * 
     * @param string $namespace Namespace prefix
     * @param string $key Key name
     * @return mixed Cached value or null
     */
    public function get(string $namespace, string $key): mixed
    {
        return $this->cache->get($this->key($namespace, $key));
    }

    /**
     * Set in cache with namespace and tags
     * 
     * @param string $namespace Namespace prefix
     * @param string $key Key name
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     * @param array $tags Optional tags for invalidation
     * @return bool Success
     */
    public function set(string $namespace, string $key, mixed $value, int $ttl = 0, array $tags = []): bool
    {
        $cacheKey = $this->key($namespace, $key);
        $success = $this->cache->set($cacheKey, $value, $ttl);

        // Track tags for invalidation
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }
            $this->tags[$tag][] = $cacheKey;
        }

        return $success;
    }

    /**
     * Check if key exists in cache
     * 
     * @param string $namespace Namespace prefix
     * @param string $key Key name
     * @return bool
     */
    public function has(string $namespace, string $key): bool
    {
        return $this->cache->has($this->key($namespace, $key));
    }

    /**
     * Delete from cache
     * 
     * @param string $namespace Namespace prefix
     * @param string $key Key name
     * @return bool Success
     */
    public function delete(string $namespace, string $key): bool
    {
        return $this->cache->delete($this->key($namespace, $key));
    }

    /**
     * Invalidate all entries with a specific tag
     * 
     * @param string $tag Tag name
     * @return bool Success
     */
    public function invalidateByTag(string $tag): bool
    {
        if (!isset($this->tags[$tag])) {
            return true;
        }

        $keys = $this->tags[$tag];
        $success = $this->cache->deleteMultiple($keys);

        unset($this->tags[$tag]);

        return $success;
    }

    /**
     * Clear all cache entries
     * 
     * @return bool Success
     */
    public function clear(): bool
    {
        $this->tags = [];
        return $this->cache->clear();
    }

    /**
     * Remember a value - get from cache or compute and cache
     * 
     * @param string $namespace Namespace prefix
     * @param string $key Key name
     * @param callable $callback Function to compute value if not cached
     * @param int $ttl Time to live in seconds
     * @param array $tags Optional tags for invalidation
     * @return mixed Cached or computed value
     */
    public function remember(
        string $namespace,
        string $key,
        callable $callback,
        int $ttl = 0,
        array $tags = []
    ): mixed {
        $value = $this->get($namespace, $key);

        if ($value === null) {
            $value = $callback();
            $this->set($namespace, $key, $value, $ttl, $tags);
        }

        return $value;
    }

    /**
     * Get cache statistics
     * 
     * @return array Stats
     */
    public function getStats(): array
    {
        return $this->cache->getStats();
    }

    /**
     * Get the underlying cache backend
     * 
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }
}
