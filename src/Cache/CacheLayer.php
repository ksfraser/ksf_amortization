<?php

namespace Ksfraser\Amortizations\Cache;

/**
 * Cache Layer
 * 
 * In-memory caching implementation for the KSF Amortization API.
 * Provides TTL support, automatic expiration, and cache invalidation.
 * 
 * Features:
 * - In-memory data storage
 * - TTL (Time To Live) support
 * - Automatic expiration on access
 * - Pattern-based invalidation
 * - Memory limit enforcement
 * - Hit rate tracking
 * 
 * @author KSF
 * @version 1.0.0
 */
class CacheLayer
{
    private array $store = [];
    private array $ttls = [];
    private int $maxSize;
    private int $hits = 0;
    private int $misses = 0;

    /**
     * Initialize cache layer
     * 
     * @param int $maxSize Maximum number of entries (default: 1000)
     */
    public function __construct(int $maxSize = 1000)
    {
        $this->maxSize = $maxSize;
    }

    /**
     * Set a cache value with optional TTL
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (0 = no expiration)
     * @return bool Success
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        // Check size limit
        if (count($this->store) >= $this->maxSize && !isset($this->store[$key])) {
            $this->evictOldest();
        }

        $this->store[$key] = $value;

        if ($ttl > 0) {
            $this->ttls[$key] = time() + $ttl;
        } else {
            unset($this->ttls[$key]);
        }

        return true;
    }

    /**
     * Get a cache value
     * 
     * @param string $key Cache key
     * @return mixed Value if found and not expired, null otherwise
     */
    public function get(string $key): mixed
    {
        // Check if key exists
        if (!isset($this->store[$key])) {
            $this->misses++;
            return null;
        }

        // Check if expired
        if ($this->isExpired($key)) {
            $this->delete($key);
            $this->misses++;
            return null;
        }

        $this->hits++;
        return $this->store[$key];
    }

    /**
     * Check if key exists and is not expired
     * 
     * @param string $key Cache key
     * @return bool True if exists and not expired
     */
    public function has(string $key): bool
    {
        if (!isset($this->store[$key])) {
            return false;
        }

        if ($this->isExpired($key)) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * Delete a cache entry
     * 
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete(string $key): bool
    {
        if (!isset($this->store[$key])) {
            return false;
        }

        unset($this->store[$key]);
        unset($this->ttls[$key]);

        return true;
    }

    /**
     * Delete cache entries matching pattern
     * 
     * @param string $pattern Pattern to match (uses strpos)
     * @return int Number of entries deleted
     */
    public function deletePattern(string $pattern): int
    {
        $deleted = 0;

        foreach (array_keys($this->store) as $key) {
            if (strpos($key, $pattern) === 0) {
                $this->delete($key);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Clear all cache entries
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->store = [];
        $this->ttls = [];
    }

    /**
     * Get cache hit rate
     * 
     * @return float Hit rate percentage (0-100)
     */
    public function getHitRate(): float
    {
        $total = $this->hits + $this->misses;

        if ($total === 0) {
            return 0;
        }

        return ($this->hits / $total) * 100;
    }

    /**
     * Get cache statistics
     * 
     * @return array Statistics including size, hits, misses, hit rate
     */
    public function getStats(): array
    {
        return [
            'size' => count($this->store),
            'max_size' => $this->maxSize,
            'hits' => $this->hits,
            'misses' => $this->misses,
            'hit_rate' => $this->getHitRate(),
            'total_requests' => $this->hits + $this->misses,
        ];
    }

    /**
     * Get all cache keys
     * 
     * @return array Array of cache keys
     */
    public function keys(): array
    {
        return array_keys($this->store);
    }

    /**
     * Get current cache size
     * 
     * @return int Number of entries in cache
     */
    public function size(): int
    {
        return count($this->store);
    }

    /**
     * Check if entry is expired
     * 
     * @param string $key Cache key
     * @return bool True if expired
     */
    private function isExpired(string $key): bool
    {
        if (!isset($this->ttls[$key])) {
            return false;
        }

        return time() > $this->ttls[$key];
    }

    /**
     * Evict oldest entry when cache is full
     * 
     * @return void
     */
    private function evictOldest(): void
    {
        if (empty($this->store)) {
            return;
        }

        // Remove first entry (FIFO)
        $key = array_key_first($this->store);
        $this->delete($key);
    }
}
