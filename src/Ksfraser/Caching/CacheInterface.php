<?php

namespace Ksfraser\Caching;

/**
 * CacheInterface - Cache abstraction for multiple backends
 * 
 * Defines common interface for cache implementations (Redis, File, Memory)
 * Supports dependency injection of different cache backends
 * 
 * @package    Ksfraser\Caching
 * @since      20251221
 */
interface CacheInterface
{
    /**
     * Get value from cache
     * 
     * @param string $key Cache key
     * @return mixed Cached value or null if not found
     */
    public function get(string $key): mixed;

    /**
     * Set value in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (0 = no expiration)
     * @return bool Success
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool;

    /**
     * Check if key exists in cache
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Delete key from cache
     * 
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete(string $key): bool;

    /**
     * Clear all cache entries
     * 
     * @return bool Success
     */
    public function clear(): bool;

    /**
     * Get multiple values from cache
     * 
     * @param array $keys Cache keys
     * @return array Key-value pairs (missing keys omitted)
     */
    public function getMultiple(array $keys): array;

    /**
     * Set multiple values in cache
     * 
     * @param array $values Key-value pairs to cache
     * @param int $ttl Time to live in seconds
     * @return bool Success
     */
    public function setMultiple(array $values, int $ttl = 0): bool;

    /**
     * Delete multiple keys from cache
     * 
     * @param array $keys Cache keys
     * @return bool Success
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Get cache statistics
     * 
     * @return array Stats including hits, misses, evictions
     */
    public function getStats(): array;
}
