<?php

namespace Tests\Unit\Caching;

use PHPUnit\Framework\TestCase;
use Ksfraser\Caching\CacheManager;
use Ksfraser\Caching\MemoryCache;

/**
 * CacheManagerTest - Tests for CacheManager
 * 
 * @package    Tests\Unit\Caching
 * @since      20251221
 */
class CacheManagerTest extends TestCase
{
    private CacheManager $manager;
    private MemoryCache $memoryCache;

    protected function setUp(): void
    {
        $this->memoryCache = new MemoryCache();
        $this->manager = new CacheManager($this->memoryCache);
    }

    /**
     * Test cache key generation
     */
    public function testKeyGeneration()
    {
        $key = $this->manager->key('users', 'user_123');
        $this->assertEquals('users:user_123', $key);
    }

    /**
     * Test get with namespace
     */
    public function testGetWithNamespace()
    {
        $this->memoryCache->set('users:123', 'John Doe');
        $value = $this->manager->get('users', '123');
        $this->assertEquals('John Doe', $value);
    }

    /**
     * Test set with namespace
     */
    public function testSetWithNamespace()
    {
        $this->manager->set('users', '456', 'Jane Doe');
        $this->assertTrue($this->memoryCache->has('users:456'));
        $this->assertEquals('Jane Doe', $this->memoryCache->get('users:456'));
    }

    /**
     * Test has with namespace
     */
    public function testHasWithNamespace()
    {
        $this->manager->set('users', '789', 'John');
        $this->assertTrue($this->manager->has('users', '789'));
        $this->assertFalse($this->manager->has('users', '999'));
    }

    /**
     * Test delete with namespace
     */
    public function testDeleteWithNamespace()
    {
        $this->manager->set('users', '999', 'value');
        $this->assertTrue($this->manager->delete('users', '999'));
        $this->assertFalse($this->manager->has('users', '999'));
    }

    /**
     * Test tag-based invalidation
     */
    public function testTagInvalidation()
    {
        // Set multiple values with same tag
        $this->manager->set('users', 'u1', 'John', 0, ['users_list']);
        $this->manager->set('users', 'u2', 'Jane', 0, ['users_list']);
        $this->manager->set('users', 'u3', 'Bob', 0, ['users_list', 'admin']);

        // All should exist
        $this->assertTrue($this->manager->has('users', 'u1'));
        $this->assertTrue($this->manager->has('users', 'u2'));
        $this->assertTrue($this->manager->has('users', 'u3'));

        // Invalidate by tag
        $this->manager->invalidateByTag('users_list');

        // users_list entries should be gone
        $this->assertFalse($this->manager->has('users', 'u1'));
        $this->assertFalse($this->manager->has('users', 'u2'));
        // But u3 might still exist (it had another tag, but we deleted it anyway)
        // This depends on implementation - with current, it will be deleted
    }

    /**
     * Test remember with cache hit
     */
    public function testRememberCacheHit()
    {
        $callCount = 0;
        $callback = function() use (&$callCount) {
            $callCount++;
            return 'computed_value';
        };

        // First call - computes
        $value1 = $this->manager->remember('cache', 'key1', $callback);
        $this->assertEquals('computed_value', $value1);
        $this->assertEquals(1, $callCount);

        // Second call - from cache
        $value2 = $this->manager->remember('cache', 'key1', $callback);
        $this->assertEquals('computed_value', $value2);
        $this->assertEquals(1, $callCount); // Not called again
    }

    /**
     * Test remember with cache miss
     */
    public function testRememberCacheMiss()
    {
        $callback = function() {
            return 'new_value';
        };

        $value = $this->manager->remember('test', 'missing_key', $callback);
        $this->assertEquals('new_value', $value);
        $this->assertTrue($this->manager->has('test', 'missing_key'));
    }

    /**
     * Test clear
     */
    public function testClear()
    {
        $this->manager->set('cache1', 'k1', 'v1');
        $this->manager->set('cache2', 'k2', 'v2');

        $this->manager->clear();

        $this->assertFalse($this->manager->has('cache1', 'k1'));
        $this->assertFalse($this->manager->has('cache2', 'k2'));
    }

    /**
     * Test get cache backend
     */
    public function testGetCacheBackend()
    {
        $backend = $this->manager->getCache();
        $this->assertInstanceOf(MemoryCache::class, $backend);
        $this->assertSame($this->memoryCache, $backend);
    }

    /**
     * Test get statistics
     */
    public function testGetStatistics()
    {
        $this->manager->set('stats', 'key1', 'value');
        $this->manager->get('stats', 'key1'); // Hit
        $this->manager->get('stats', 'missing'); // Miss

        $stats = $this->manager->getStats();

        $this->assertEquals(1, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
    }

    /**
     * Test multiple tags on single key
     */
    public function testMultipleTags()
    {
        $this->manager->set('data', 'item', 'value', 0, ['tag1', 'tag2', 'tag3']);

        // Should exist
        $this->assertTrue($this->manager->has('data', 'item'));

        // Invalidate by one tag
        $this->manager->invalidateByTag('tag2');

        // Should be gone
        $this->assertFalse($this->manager->has('data', 'item'));
    }
}
