<?php

namespace Tests\Unit\Caching;

use PHPUnit\Framework\TestCase;
use Ksfraser\Caching\MemoryCache;
use Ksfraser\Caching\FileCache;

/**
 * CacheImplementationTest - Tests for cache implementations
 * 
 * @package    Tests\Unit\Caching
 * @since      20251221
 */
class CacheImplementationTest extends TestCase
{
    private MemoryCache $memoryCache;
    private FileCache $fileCache;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->memoryCache = new MemoryCache();
        $this->tempDir = sys_get_temp_dir() . '/cache_test_' . uniqid();
        $this->fileCache = new FileCache($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->tempDir);
        }
    }

    /**
     * Test memory cache set and get
     */
    public function testMemoryCacheSetAndGet()
    {
        $this->memoryCache->set('test_key', 'test_value');
        $this->assertEquals('test_value', $this->memoryCache->get('test_key'));
    }

    /**
     * Test file cache set and get
     */
    public function testFileCacheSetAndGet()
    {
        $this->fileCache->set('test_key', 'test_value');
        $this->assertEquals('test_value', $this->fileCache->get('test_key'));
    }

    /**
     * Test cache get returns null for missing key
     */
    public function testCacheGetMissingKey()
    {
        $this->assertNull($this->memoryCache->get('nonexistent'));
        $this->assertNull($this->fileCache->get('nonexistent'));
    }

    /**
     * Test cache has method
     */
    public function testCacheHas()
    {
        $this->memoryCache->set('exists', 'value');
        $this->fileCache->set('exists', 'value');

        $this->assertTrue($this->memoryCache->has('exists'));
        $this->assertTrue($this->fileCache->has('exists'));
        $this->assertFalse($this->memoryCache->has('missing'));
        $this->assertFalse($this->fileCache->has('missing'));
    }

    /**
     * Test cache delete
     */
    public function testCacheDelete()
    {
        $this->memoryCache->set('key', 'value');
        $this->fileCache->set('key', 'value');

        $this->assertTrue($this->memoryCache->delete('key'));
        $this->assertTrue($this->fileCache->delete('key'));
        $this->assertNull($this->memoryCache->get('key'));
        $this->assertNull($this->fileCache->get('key'));
    }

    /**
     * Test cache clear
     */
    public function testCacheClear()
    {
        $this->memoryCache->set('key1', 'value1');
        $this->memoryCache->set('key2', 'value2');
        $this->fileCache->set('key1', 'value1');
        $this->fileCache->set('key2', 'value2');

        $this->memoryCache->clear();
        $this->fileCache->clear();

        $this->assertNull($this->memoryCache->get('key1'));
        $this->assertNull($this->fileCache->get('key1'));
    }

    /**
     * Test cache expiration (TTL)
     */
    public function testCacheExpiration()
    {
        // Set with 1 second TTL
        $this->memoryCache->set('expiring', 'value', 1);
        $this->fileCache->set('expiring', 'value', 1);

        // Should exist immediately
        $this->assertNotNull($this->memoryCache->get('expiring'));
        $this->assertNotNull($this->fileCache->get('expiring'));

        // Wait for expiration
        sleep(2);

        // Should be expired
        $this->assertNull($this->memoryCache->get('expiring'));
        $this->assertNull($this->fileCache->get('expiring'));
    }

    /**
     * Test get multiple
     */
    public function testGetMultiple()
    {
        $values = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];

        $this->memoryCache->setMultiple($values);
        $result = $this->memoryCache->getMultiple(['key1', 'key2', 'key3', 'missing']);

        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'], $result);
    }

    /**
     * Test set multiple
     */
    public function testSetMultiple()
    {
        $values = ['k1' => 'v1', 'k2' => 'v2', 'k3' => 'v3'];

        $this->memoryCache->setMultiple($values);
        $this->fileCache->setMultiple($values);

        $this->assertEquals('v1', $this->memoryCache->get('k1'));
        $this->assertEquals('v2', $this->fileCache->get('k2'));
    }

    /**
     * Test delete multiple
     */
    public function testDeleteMultiple()
    {
        $this->memoryCache->set('k1', 'v1');
        $this->memoryCache->set('k2', 'v2');

        $this->memoryCache->deleteMultiple(['k1', 'k2']);

        $this->assertNull($this->memoryCache->get('k1'));
        $this->assertNull($this->memoryCache->get('k2'));
    }

    /**
     * Test cache statistics
     */
    public function testCacheStatistics()
    {
        $this->memoryCache->set('key', 'value');

        // First get - hit
        $this->memoryCache->get('key');
        // Second get - hit
        $this->memoryCache->get('key');
        // Get missing - miss
        $this->memoryCache->get('missing');

        $stats = $this->memoryCache->getStats();

        $this->assertEquals(2, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
    }

    /**
     * Test cache with complex values
     */
    public function testCacheComplexValues()
    {
        $array = ['nested' => ['data' => 'value']];
        $object = (object)['prop' => 'value'];

        $this->memoryCache->set('array', $array);
        $this->memoryCache->set('object', $object);

        $this->assertEquals($array, $this->memoryCache->get('array'));
        $this->assertEquals($object, $this->memoryCache->get('object'));
    }
}
