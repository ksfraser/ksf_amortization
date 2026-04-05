<?php
namespace Ksfraser\Caching;

/**
 * Cache Backend Interface
 * 
 * Defines contract for cache implementations.
 * Multiple backends supported: Redis, Memcached, Database, File.
 * 
 * @package   Ksfraser\Caching
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
interface CacheBackend
{
    /**
     * Get value from cache
     *
     * @param string $key Cache key
     *
     * @return string|null Cached value or null if not found/expired
     */
    public function get(string $key): ?string;

    /**
     * Set value in cache
     *
     * @param string $key Cache key
     * @param string $value Value to cache
     * @param int $ttl Time-to-live in seconds (0 = no expiration)
     *
     * @return bool True on success
     */
    public function set(string $key, string $value, int $ttl = 3600): bool;

    /**
     * Delete value from cache
     *
     * @param string $key Cache key
     *
     * @return bool True if deleted, false if not found
     */
    public function delete(string $key): bool;

    /**
     * Check if key exists in cache
     *
     * @param string $key Cache key
     *
     * @return bool True if exists and not expired
     */
    public function has(string $key): bool;

    /**
     * Clear all cache entries
     *
     * @return bool True on success
     *
     * @warning Use with caution in production
     */
    public function clear(): bool;

    /**
     * Get cache statistics
     *
     * @return array Statistics including hits, misses, size, etc.
     */
    public function getStats(): array;

    /**
     * Get backend name
     *
     * @return string Backend identifier (redis, db, file, etc.)
     */
    public function getName(): string;
}

/**
 * Redis Cache Backend
 * 
 * High-performance in-memory cache using Redis.
 * Recommended for production with high request volumes.
 * 
 * Requirements:
 * - PHP Redis extension (phpredis or predis)
 * - Redis server running
 * 
 * @package   Ksfraser\Caching
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class RedisCache implements CacheBackend
{
    /**
     * @var \Redis Redis client instance
     */
    private $redis;

    /**
     * @var string Namespace prefix for keys
     */
    private $namespace;

    /**
     * @var int Default TTL in seconds
     */
    private $defaultTTL = 3600;

    /**
     * @var array Statistics tracking
     */
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'errors' => 0
    ];

    /**
     * RedisCache constructor
     *
     * @param \Redis|string $redisOrHost Redis instance or hostname
     * @param int $port Redis port (default 6379)
     * @param int $database Redis database number (default 0)
     * @param string $namespace Key namespace prefix
     * @param int $defaultTTL Default TTL in seconds
     *
     * @throws \RuntimeException If Redis connection fails
     */
    public function __construct(
        $redisOrHost,
        int $port = 6379,
        int $database = 0,
        string $namespace = 'oauth2:',
        int $defaultTTL = 3600
    ) {
        if ($redisOrHost instanceof \Redis) {
            $this->redis = $redisOrHost;
        } else {
            $this->redis = new \Redis();
            if (!$this->redis->connect($redisOrHost, $port)) {
                throw new \RuntimeException("Failed to connect to Redis at {$redisOrHost}:{$port}");
            }
            $this->redis->select($database);
        }

        $this->namespace = $namespace;
        $this->defaultTTL = $defaultTTL;
    }

    /**
     * Get value from Redis
     *
     * @param string $key Cache key
     *
     * @return string|null Value or null
     */
    public function get(string $key): ?string
    {
        try {
            $value = $this->redis->get($this->getPrefixedKey($key));
            
            if ($value !== false) {
                $this->stats['hits']++;
                return $value;
            }

            $this->stats['misses']++;
            return null;
        } catch (\Exception $e) {
            $this->stats['errors']++;
            return null;
        }
    }

    /**
     * Set value in Redis
     *
     * @param string $key Cache key
     * @param string $value Value to cache
     * @param int $ttl Time-to-live in seconds (0 = no expiration)
     *
     * @return bool True on success
     */
    public function set(string $key, string $value, int $ttl = 3600): bool
    {
        try {
            $prefixedKey = $this->getPrefixedKey($key);
            $ttl = $ttl > 0 ? $ttl : $this->defaultTTL;

            if ($ttl > 0) {
                $this->redis->setex($prefixedKey, $ttl, $value);
            } else {
                $this->redis->set($prefixedKey, $value);
            }

            $this->stats['sets']++;
            return true;
        } catch (\Exception $e) {
            $this->stats['errors']++;
            return false;
        }
    }

    /**
     * Delete value from Redis
     *
     * @param string $key Cache key
     *
     * @return bool True if deleted
     */
    public function delete(string $key): bool
    {
        try {
            $result = $this->redis->del($this->getPrefixedKey($key));
            $this->stats['deletes']++;
            return $result > 0;
        } catch (\Exception $e) {
            $this->stats['errors']++;
            return false;
        }
    }

    /**
     * Check if key exists in Redis
     *
     * @param string $key Cache key
     *
     * @return bool True if exists
     */
    public function has(string $key): bool
    {
        try {
            return $this->redis->exists($this->getPrefixedKey($key)) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear all cache entries in namespace
     *
     * @return bool True on success
     */
    public function clear(): bool
    {
        try {
            $pattern = $this->namespace . '*';
            $keys = $this->redis->keys($pattern);
            
            if (!empty($keys)) {
                $this->redis->del($keys);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get Redis connection status
     *
     * @return bool True if connected
     */
    public function isConnected(): bool
    {
        try {
            return $this->redis->ping();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get cache statistics
     *
     * @return array Statistics
     */
    public function getStats(): array
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? round(($this->stats['hits'] / $total) * 100, 2) : 0;

        return array_merge($this->stats, [
            'total_requests' => $total,
            'hit_rate_percent' => $hitRate,
            'backend' => $this->getName(),
            'namespace' => $this->namespace
        ]);
    }

    /**
     * Get backend name
     *
     * @return string "redis"
     */
    public function getName(): string
    {
        return 'redis';
    }

    /**
     * Get Redis client instance
     *
     * @return \Redis Redis instance
     */
    public function getClient(): \Redis
    {
        return $this->redis;
    }

    /**
     * Get prefixed cache key
     *
     * @param string $key Original key
     *
     * @return string Namespaced key
     */
    private function getPrefixedKey(string $key): string
    {
        return $this->namespace . $key;
    }
}

/**
 * Database Cache Backend
 * 
 * Fallback cache using database storage.
 * Used when Redis unavailable or for persistent caching.
 * 
 * @package   Ksfraser\Caching
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class DatabaseCache implements CacheBackend
{
    /**
     * @var \PDO Database connection
     */
    private $db;

    /**
     * @var string Cache table name
     */
    private $table = 'cache_entries';

    /**
     * @var int Default TTL in seconds
     */
    private $defaultTTL = 3600;

    /**
     * @var array Statistics
     */
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'errors' => 0
    ];

    /**
     * DatabaseCache constructor
     *
     * @param \PDO $db Database connection
     * @param string $table Cache table name
     * @param int $defaultTTL Default TTL in seconds
     */
    public function __construct(\PDO $db, string $table = 'cache_entries', int $defaultTTL = 3600)
    {
        $this->db = $db;
        $this->table = $table;
        $this->defaultTTL = $defaultTTL;

        // Create table if it doesn't exist
        $this->initializeTable();
    }

    /**
     * Initialize cache table
     *
     * @return void
     */
    private function initializeTable(): void
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INTEGER PRIMARY KEY AUTO_INCREMENT,
                    cache_key VARCHAR(255) NOT NULL UNIQUE,
                    cache_value LONGBLOB NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_expires (expires_at),
                    INDEX idx_key (cache_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\PDOException $e) {
            // Try SQLite syntax
            try {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS {$this->table} (
                        id INTEGER PRIMARY KEY,
                        cache_key TEXT NOT NULL UNIQUE,
                        cache_value TEXT NOT NULL,
                        expires_at DATETIME NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
            } catch (\PDOException $e2) {
                // Table creation failed, but might already exist
            }
        }
    }

    /**
     * Get value from database
     *
     * @param string $key Cache key
     *
     * @return string|null Value or null
     */
    public function get(string $key): ?string
    {
        try {
            // Clean expired entries first
            $this->cleanExpired();

            $stmt = $this->db->prepare(
                "SELECT cache_value FROM {$this->table} WHERE cache_key = ? AND expires_at > NOW()"
            );
            $stmt->execute([$key]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                $this->stats['hits']++;
                return $result['cache_value'];
            }

            $this->stats['misses']++;
            return null;
        } catch (\PDOException $e) {
            $this->stats['errors']++;
            return null;
        }
    }

    /**
     * Set value in database
     *
     * @param string $key Cache key
     * @param string $value Value to cache
     * @param int $ttl Time-to-live in seconds
     *
     * @return bool True on success
     */
    public function set(string $key, string $value, int $ttl = 3600): bool
    {
        try {
            $ttl = $ttl > 0 ? $ttl : $this->defaultTTL;
            $expiresAt = date('Y-m-d H:i:s', time() + $ttl);

            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (cache_key, cache_value, expires_at) 
                 VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE cache_value = ?, expires_at = ?"
            );
            $stmt->execute([$key, $value, $expiresAt, $value, $expiresAt]);

            $this->stats['sets']++;
            return true;
        } catch (\PDOException $e) {
            $this->stats['errors']++;
            return false;
        }
    }

    /**
     * Delete value from database
     *
     * @param string $key Cache key
     *
     * @return bool True if deleted
     */
    public function delete(string $key): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE cache_key = ?");
            $result = $stmt->execute([$key]);
            $this->stats['deletes']++;
            return $result && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            $this->stats['errors']++;
            return false;
        }
    }

    /**
     * Check if key exists
     *
     * @param string $key Cache key
     *
     * @return bool True if exists
     */
    public function has(string $key): bool
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT 1 FROM {$this->table} WHERE cache_key = ? AND expires_at > NOW()"
            );
            $stmt->execute([$key]);
            return $stmt->fetch() !== false;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Clear all cache entries
     *
     * @return bool True on success
     */
    public function clear(): bool
    {
        try {
            $this->db->exec("TRUNCATE TABLE {$this->table}");
            return true;
        } catch (\PDOException $e) {
            // SQLite doesn't support TRUNCATE
            try {
                $this->db->exec("DELETE FROM {$this->table}");
                return true;
            } catch (\PDOException $e2) {
                return false;
            }
        }
    }

    /**
     * Get cache statistics
     *
     * @return array Statistics
     */
    public function getStats(): array
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? round(($this->stats['hits'] / $total) * 100, 2) : 0;

        return array_merge($this->stats, [
            'total_requests' => $total,
            'hit_rate_percent' => $hitRate,
            'backend' => $this->getName(),
            'table' => $this->table
        ]);
    }

    /**
     * Get backend name
     *
     * @return string "database"
     */
    public function getName(): string
    {
        return 'database';
    }

    /**
     * Clean expired entries
     *
     * @return int Number of entries deleted
     */
    private function cleanExpired(): int
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE expires_at <= NOW()");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            return 0;
        }
    }
}

/**
 * Multi-Tier Cache Strategy
 * 
 * Falls back through multiple cache backends for high availability.
 * Strategy: Try L1 (Redis) → L2 (Database) → L3 (compute)
 * 
 * @package   Ksfraser\Caching
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class MultiTierCache implements CacheBackend
{
    /**
     * @var CacheBackend[] Ordered list of cache backends
     */
    private $backends = [];

    /**
     * @var CacheBackend First tier (write target)
     */
    private $primary;

    /**
     * @var array Statistics
     */
    private $stats = [];

    /**
     * MultiTierCache constructor
     *
     * @param CacheBackend[] $backends List of backends (ordered by priority)
     */
    public function __construct(array $backends)
    {
        if (empty($backends)) {
            throw new \InvalidArgumentException("At least one cache backend required");
        }

        $this->backends = array_values($backends);
        $this->primary = $this->backends[0];

        // Initialize stats for each backend
        foreach ($this->backends as $backend) {
            $this->stats[$backend->getName()] = $backend->getStats();
        }
    }

    /**
     * Get value from first available backend
     *
     * @param string $key Cache key
     *
     * @return string|null Value or null
     */
    public function get(string $key): ?string
    {
        foreach ($this->backends as $i => $backend) {
            $value = $backend->get($key);
            
            if ($value !== null) {
                // Backfill to higher tiers
                for ($j = 0; $j < $i; $j++) {
                    $this->backends[$j]->set($key, $value);
                }
                return $value;
            }
        }

        return null;
    }

    /**
     * Set value in all backends
     *
     * @param string $key Cache key
     * @param string $value Value
     * @param int $ttl TTL in seconds
     *
     * @return bool True if stored in primary
     */
    public function set(string $key, string $value, int $ttl = 3600): bool
    {
        $result = true;

        foreach ($this->backends as $backend) {
            if (!$backend->set($key, $value, $ttl)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Delete value from all backends
     *
     * @param string $key Cache key
     *
     * @return bool True if deleted from primary
     */
    public function delete(string $key): bool
    {
        $result = true;

        foreach ($this->backends as $backend) {
            if (!$backend->delete($key)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Check if key exists
     *
     * @param string $key Cache key
     *
     * @return bool True if exists in any backend
     */
    public function has(string $key): bool
    {
        foreach ($this->backends as $backend) {
            if ($backend->has($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Clear all backends
     *
     * @return bool True if cleared
     */
    public function clear(): bool
    {
        $result = true;
        foreach ($this->backends as $backend) {
            if (!$backend->clear()) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Get statistics from all backends
     *
     * @return array Combined stats
     */
    public function getStats(): array
    {
        $stats = ['backends' => []];
        
        foreach ($this->backends as $backend) {
            $stats['backends'][$backend->getName()] = $backend->getStats();
        }

        return $stats;
    }

    /**
     * Get backend name
     *
     * @return string "multi-tier"
     */
    public function getName(): string
    {
        return 'multi-tier';
    }

    /**
     * Get primary (write) backend
     *
     * @return CacheBackend Primary backend
     */
    public function getPrimaryBackend(): CacheBackend
    {
        return $this->primary;
    }
}
