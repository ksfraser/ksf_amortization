<?php
namespace Tests;

use Ksfraser\Amortizations\Services\CacheManager;
use PHPUnit\Framework\TestCase;

class CacheManagerTest extends TestCase {
    private CacheManager $cache;

    protected function setUp(): void {
        $this->cache = new CacheManager();
    }

    public function testSetAndGetBasicValue(): void {
        $this->cache->set('key1', 'value1');
        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testGetReturnsNullForNonexistentKey(): void {
        $this->assertNull($this->cache->get('nonexistent'));
    }

    public function testHasReturnsTrueForExistingKey(): void {
        $this->cache->set('test_key', 'test_value');
        $this->assertTrue($this->cache->has('test_key'));
    }

    public function testHasReturnsFalseForNonexistentKey(): void {
        $this->assertFalse($this->cache->has('nonexistent'));
    }

    public function testDeleteRemovesEntry(): void {
        $this->cache->set('delete_key', 'value');
        $this->assertTrue($this->cache->has('delete_key'));
        $this->assertTrue($this->cache->delete('delete_key'));
        $this->assertFalse($this->cache->has('delete_key'));
    }

    public function testDeleteReturnsFalseForNonexistentKey(): void {
        $this->assertFalse($this->cache->delete('nonexistent'));
    }

    public function testClearRemovesAllEntries(): void {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $this->cache->clear();

        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
        $this->assertFalse($this->cache->has('key3'));
    }

    public function testDefaultTTLIsApplied(): void {
        $this->cache->setDefaultTTL(60);
        $this->cache->set('ttl_key', 'value');

        $metadata = $this->cache->getMetadata('ttl_key');
        $this->assertEquals(60, $metadata['ttl']);
    }

    public function testCustomTTLOverridesDefault(): void {
        $this->cache->setDefaultTTL(60);
        $this->cache->set('custom_ttl', 'value', 120);

        $metadata = $this->cache->getMetadata('custom_ttl');
        $this->assertEquals(120, $metadata['ttl']);
    }

    public function testGetStatsTracksCacheHits(): void {
        $this->cache->set('hit_key', 'value');
        $this->cache->get('hit_key');
        $this->cache->get('hit_key');
        $this->cache->get('nonexistent');

        $stats = $this->cache->getStats();
        $this->assertEquals(2, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
        $this->assertEquals(3, $stats['total_requests']);
    }

    public function testGetStatsCalculatesHitRate(): void {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');

        $this->cache->get('key1'); // hit
        $this->cache->get('key1'); // hit
        $this->cache->get('key1'); // hit
        $this->cache->get('missing'); // miss

        $stats = $this->cache->getStats();
        $this->assertEquals(75.0, $stats['hit_rate']);
    }

    public function testSetIncrementsSetCounter(): void {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $stats = $this->cache->getStats();
        $this->assertEquals(3, $stats['sets']);
    }

    public function testDeleteIncrementsDeleteCounter(): void {
        $this->cache->set('key1', 'value1');
        $this->cache->delete('key1');

        $stats = $this->cache->getStats();
        $this->assertEquals(1, $stats['deletes']);
    }

    public function testCacheComplexDataStructures(): void {
        $data = [
            'nested' => ['key' => 'value'],
            'array' => [1, 2, 3],
            'string' => 'test'
        ];

        $this->cache->set('complex', $data);
        $retrieved = $this->cache->get('complex');

        $this->assertEquals($data, $retrieved);
    }

    public function testDeleteByPatternRemovesMatchingKeys(): void {
        $this->cache->set('portfolio_1', 'value1');
        $this->cache->set('portfolio_2', 'value2');
        $this->cache->set('loan_1', 'value3');

        $deleted = $this->cache->deleteByPattern('/^portfolio_/');

        $this->assertEquals(2, $deleted);
        $this->assertFalse($this->cache->has('portfolio_1'));
        $this->assertFalse($this->cache->has('portfolio_2'));
        $this->assertTrue($this->cache->has('loan_1'));
    }

    public function testGetKeysReturnsAllCacheKeys(): void {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $keys = $this->cache->getKeys();

        $this->assertCount(3, $keys);
        $this->assertContains('key1', $keys);
        $this->assertContains('key2', $keys);
        $this->assertContains('key3', $keys);
    }

    public function testGetSizeReturnsMemoryUsage(): void {
        $this->cache->set('key1', 'value1');
        $size = $this->cache->getSize();

        $this->assertGreaterThan(0, $size);
    }

    public function testResetStatsResetsAllCounters(): void {
        $this->cache->set('key', 'value');
        $this->cache->get('key');
        $this->cache->delete('key');

        $this->cache->resetStats();
        $stats = $this->cache->getStats();

        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(0, $stats['misses']);
        $this->assertEquals(0, $stats['sets']);
        $this->assertEquals(0, $stats['deletes']);
    }

    public function testWarmCachePopulatesMultipleValues(): void {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        ];

        $count = $this->cache->warm($data);

        $this->assertEquals(3, $count);
        $this->assertEquals('value1', $this->cache->get('key1'));
        $this->assertEquals('value2', $this->cache->get('key2'));
        $this->assertEquals('value3', $this->cache->get('key3'));
    }

    public function testGetExpiringEntriesReturnsEntriesSoonToExpire(): void {
        $this->cache->set('expiring_soon', 'value', 100);
        $this->cache->set('not_expiring', 'value', 3600);

        sleep(1);

        $expiring = $this->cache->getExpiringEntries(150);

        $this->assertArrayHasKey('expiring_soon', $expiring);
        $this->assertLessThanOrEqual(100, $expiring['expiring_soon']['expires_in_seconds']);
    }

    public function testPurgeExpiredRemovesExpiredEntries(): void {
        $this->cache->set('expire1', 'value', 1);
        $this->cache->set('keep', 'value', 3600);

        sleep(2);

        $deleted = $this->cache->purgeExpired();

        $this->assertGreaterThanOrEqual(1, $deleted);
        $this->assertFalse($this->cache->has('expire1'));
        $this->assertTrue($this->cache->has('keep'));
    }

    public function testSetDefaultTTLAffectsNewEntries(): void {
        $this->cache->setDefaultTTL(300);
        $this->cache->set('new_key', 'value');

        $metadata = $this->cache->getMetadata('new_key');
        $this->assertEquals(300, $metadata['ttl']);
    }

    public function testGetDefaultTTLReturnsCurrentDefault(): void {
        $this->cache->setDefaultTTL(500);
        $this->assertEquals(500, $this->cache->getDefaultTTL());
    }

    public function testSetDefaultTTLThrowsForInvalidValue(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->cache->setDefaultTTL(0);
    }

    public function testSetThrowsForEmptyKey(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->cache->set('', 'value');
    }

    public function testCacheMetadataTracksHits(): void {
        $this->cache->set('hit_tracked', 'value');
        $this->cache->get('hit_tracked');
        $this->cache->get('hit_tracked');

        $metadata = $this->cache->getMetadata('hit_tracked');
        $this->assertEquals(2, $metadata['hits']);
    }
}
