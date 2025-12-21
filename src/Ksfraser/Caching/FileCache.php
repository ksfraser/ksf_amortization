<?php

namespace Ksfraser\Caching;

/**
 * FileCache - File-based cache implementation
 * 
 * Stores cache in serialized files on the filesystem
 * Suitable for development and single-server deployments
 * 
 * @package    Ksfraser\Caching
 * @since      20251221
 */
class FileCache implements CacheInterface
{
    /**
     * @var string Cache directory path
     */
    private string $cacheDir;

    /**
     * @var array Cache statistics
     */
    private array $stats = ['hits' => 0, 'misses' => 0, 'evictions' => 0];

    /**
     * Constructor
     * 
     * @param string $cacheDir Directory for cache files
     */
    public function __construct(string $cacheDir = '/tmp/cache')
    {
        $this->cacheDir = $cacheDir;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }

    /**
     * Generate cache file path
     * 
     * @param string $key Cache key
     * @return string File path
     */
    private function getCachePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): mixed
    {
        $path = $this->getCachePath($key);

        if (!file_exists($path)) {
            $this->stats['misses']++;
            return null;
        }

        $data = unserialize(file_get_contents($path));

        // Check expiration
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            unlink($path);
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
        $path = $this->getCachePath($key);
        $data = [
            'value' => $value,
            'expires' => ($ttl > 0) ? time() + $ttl : 0,
            'created' => time()
        ];

        return file_put_contents($path, serialize($data)) !== false;
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
        $path = $this->getCachePath($key);
        if (file_exists($path)) {
            return unlink($path);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
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
            if (!$this->set($key, $value, $ttl)) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                return false;
            }
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
