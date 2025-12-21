<?php

namespace Ksfraser\Caching;

/**
 * MemoryCache - In-memory cache implementation
 * 
 * Stores cache in PHP memory - suitable for testing and development
 * Cache is cleared at end of request lifecycle
 * 
 * @package    Ksfraser\Caching
 * @since      20251221
 */
class MemoryCache implements CacheInterface
{
    /**
     * @var array In-memory cache store
     */
    private array $store = [];

    /**
     * @var array Cache statistics
     */
    private array $stats = ['hits' => 0, 'misses' => 0, 'evictions' => 0];

    /**
     * {@inheritdoc}
     */
    public function get(string $key): mixed
    {
        if (!isset($this->store[$key])) {
            $this->stats['misses']++;
            return null;
        }

        $data = $this->store[$key];

        // Check expiration
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            unset($this->store[$key]);
            $this->stats['evictions']++;
            $this->stats['misses']++;
            return null;
        }

        $this->stats['hits']++;
        return $data['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $this->store[$key] = [
            'value' => $value,
            'expires' => ($ttl > 0) ? time() + $ttl : 0,
            'created' => time()
        ];
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        unset($this->store[$key]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->store = [];
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $value = $this->get($key);
            if ($value !== null) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(array $values, int $ttl = 0): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
