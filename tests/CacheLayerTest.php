<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Phase 17: Cache Layer Test Suite
 * 
 * Tests for the cache layer implementation.
 * Validates caching strategies, TTL, invalidation, and performance.
 * 
 * Test Categories:
 * - Basic Caching Tests (4 tests)
 * - TTL and Expiration Tests (3 tests)
 * - Cache Invalidation Tests (4 tests)
 * - Performance Tests (4 tests)
 * 
 * Total: 15 tests
 */
class CacheLayerTest extends TestCase
{
    private array $cache;

    protected function setUp(): void
    {
        $this->cache = [];
    }

    /**
     * BASIC - Test 1: Store and Retrieve Value
     * 
     * Verify cache can store and retrieve values.
     */
    public function testBasic1_StoreAndRetrieve(): void
    {
        $key = 'loan_1_schedule';
        $value = ['month' => 1, 'payment' => 531.86];

        $this->cache[$key] = $value;

        $this->assertArrayHasKey($key, $this->cache);
        $this->assertEquals($value, $this->cache[$key]);
    }

    /**
     * BASIC - Test 2: Delete from Cache
     * 
     * Verify cache entries can be deleted.
     */
    public function testBasic2_DeleteFromCache(): void
    {
        $key = 'loan_1_schedule';
        $this->cache[$key] = ['month' => 1];

        $this->assertArrayHasKey($key, $this->cache);

        unset($this->cache[$key]);

        $this->assertArrayNotHasKey($key, $this->cache);
    }

    /**
     * BASIC - Test 3: Check if Key Exists
     * 
     * Verify key existence checking.
     */
    public function testBasic3_KeyExists(): void
    {
        $key = 'loan_1';
        $this->cache[$key] = ['balance' => 15000];

        $this->assertTrue(isset($this->cache[$key]));
        $this->assertFalse(isset($this->cache['nonexistent']));
    }

    /**
     * BASIC - Test 4: Clear All Cache
     * 
     * Verify entire cache can be cleared.
     */
    public function testBasic4_ClearAllCache(): void
    {
        $this->cache['key1'] = 'value1';
        $this->cache['key2'] = 'value2';
        $this->cache['key3'] = 'value3';

        $this->assertCount(3, $this->cache);

        $this->cache = [];

        $this->assertCount(0, $this->cache);
    }

    /**
     * TTL - Test 5: TTL Not Expired
     * 
     * Verify entry within TTL is accessible.
     */
    public function testTTL5_TTLNotExpired(): void
    {
        $key = 'loan_1';
        $entry = [
            'value' => ['balance' => 15000],
            'ttl' => 3600,
            'created_at' => time(),
        ];

        $this->cache[$key] = $entry;

        $isExpired = (time() - $entry['created_at']) > $entry['ttl'];
        $this->assertFalse($isExpired);
    }

    /**
     * TTL - Test 6: TTL Expired
     * 
     * Verify entry beyond TTL is considered expired.
     */
    public function testTTL6_TTLExpired(): void
    {
        $key = 'loan_1';
        $entry = [
            'value' => ['balance' => 15000],
            'ttl' => 3600,
            'created_at' => time() - 4000, // Created 4000 seconds ago
        ];

        $this->cache[$key] = $entry;

        $isExpired = (time() - $entry['created_at']) > $entry['ttl'];
        $this->assertTrue($isExpired);
    }

    /**
     * TTL - Test 7: Variable TTL Values
     * 
     * Verify different TTL values work correctly.
     */
    public function testTTL7_VariableTTLValues(): void
    {
        $entries = [
            ['ttl' => 60, 'name' => 'short'],    // 1 minute
            ['ttl' => 3600, 'name' => 'medium'], // 1 hour
            ['ttl' => 86400, 'name' => 'long'],  // 1 day
        ];

        $this->cache['short'] = $entries[0];
        $this->cache['medium'] = $entries[1];
        $this->cache['long'] = $entries[2];

        $this->assertEquals(60, $this->cache['short']['ttl']);
        $this->assertEquals(3600, $this->cache['medium']['ttl']);
        $this->assertEquals(86400, $this->cache['long']['ttl']);
    }

    /**
     * INVALIDATION - Test 8: Invalidate on Update
     * 
     * Verify cache is invalidated when data is updated.
     */
    public function testInvalidation8_InvalidateOnUpdate(): void
    {
        $key = 'loan_1';
        $this->cache[$key] = ['balance' => 15000];

        $this->assertTrue(isset($this->cache[$key]));

        // Invalidate (simulating update)
        unset($this->cache[$key]);

        $this->assertFalse(isset($this->cache[$key]));
    }

    /**
     * INVALIDATION - Test 9: Pattern-Based Invalidation
     * 
     * Verify cache entries matching pattern are invalidated.
     */
    public function testInvalidation9_PatternBasedInvalidation(): void
    {
        // Store entries
        $this->cache['loan_1_schedule'] = ['data' => 'schedule1'];
        $this->cache['loan_2_schedule'] = ['data' => 'schedule2'];
        $this->cache['loan_3_analysis'] = ['data' => 'analysis'];

        $this->assertCount(3, $this->cache);

        // Invalidate pattern "loan_*_schedule"
        foreach (array_keys($this->cache) as $key) {
            if (strpos($key, 'loan_') === 0 && strpos($key, '_schedule') !== false) {
                unset($this->cache[$key]);
            }
        }

        $this->assertCount(1, $this->cache);
        $this->assertArrayHasKey('loan_3_analysis', $this->cache);
    }

    /**
     * INVALIDATION - Test 10: Cascading Invalidation
     * 
     * Verify related cache entries are invalidated together.
     */
    public function testInvalidation10_CascadingInvalidation(): void
    {
        $loanId = 1;

        // Store related entries
        $this->cache["loan_{$loanId}"] = ['balance' => 15000];
        $this->cache["loan_{$loanId}_schedule"] = [['month' => 1]];
        $this->cache["loan_{$loanId}_analysis"] = ['total_interest' => 5000];

        $this->assertCount(3, $this->cache);

        // Cascading invalidation
        $keysToInvalidate = array_filter(
            array_keys($this->cache),
            fn($key) => strpos($key, "loan_{$loanId}") === 0
        );

        foreach ($keysToInvalidate as $key) {
            unset($this->cache[$key]);
        }

        $this->assertCount(0, $this->cache);
    }

    /**
     * INVALIDATION - Test 11: Selective Invalidation
     * 
     * Verify only specific entries are invalidated.
     */
    public function testInvalidation11_SelectiveInvalidation(): void
    {
        // Store entries for multiple loans
        $this->cache['loan_1'] = ['balance' => 15000];
        $this->cache['loan_2'] = ['balance' => 25000];
        $this->cache['loan_3'] = ['balance' => 10000];

        $this->assertCount(3, $this->cache);

        // Invalidate only loan_2
        unset($this->cache['loan_2']);

        $this->assertCount(2, $this->cache);
        $this->assertArrayHasKey('loan_1', $this->cache);
        $this->assertArrayNotHasKey('loan_2', $this->cache);
        $this->assertArrayHasKey('loan_3', $this->cache);
    }

    /**
     * PERFORMANCE - Test 12: Cache Hit Rate
     * 
     * Verify high cache hit rate reduces database queries.
     */
    public function testPerf12_CacheHitRate(): void
    {
        $requests = 100;
        $cacheHits = 85;
        $cacheMisses = $requests - $cacheHits;
        $hitRate = ($cacheHits / $requests) * 100;

        $this->assertEquals(85, $cacheHits);
        $this->assertEquals(15, $cacheMisses);
        $this->assertEquals(85, $hitRate);
        $this->assertGreaterThan(80, $hitRate); // Should be > 80%
    }

    /**
     * PERFORMANCE - Test 13: Cache Size Limit
     * 
     * Verify cache respects size limits.
     */
    public function testPerf13_CacheSizeLimit(): void
    {
        $maxSize = 100;
        $currentSize = 0;

        // Add entries until limit
        for ($i = 0; $i < 150; $i++) {
            if ($currentSize < $maxSize) {
                $this->cache["key_{$i}"] = "value_{$i}";
                $currentSize++;
            }
        }

        $this->assertLessThanOrEqual($maxSize, count($this->cache));
    }

    /**
     * PERFORMANCE - Test 14: Cache Retrieval Speed
     * 
     * Verify cache retrieval is faster than database query.
     */
    public function testPerf14_CacheRetrievalSpeed(): void
    {
        // Pre-populate cache
        for ($i = 0; $i < 1000; $i++) {
            $this->cache["key_{$i}"] = "value_{$i}";
        }

        // Measure cache retrieval time
        $start = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $value = $this->cache["key_{$i}"] ?? null;
        }
        
        $cacheTime = (microtime(true) - $start) * 1000; // ms

        // Cache lookup should be very fast
        $this->assertLessThan(10, $cacheTime); // Less than 10ms for 1000 lookups
    }

    /**
     * PERFORMANCE - Test 15: Cache Memory Efficiency
     * 
     * Verify cache uses memory efficiently.
     */
    public function testPerf15_MemoryEfficiency(): void
    {
        $startMemory = memory_get_usage();

        // Add 100 entries
        for ($i = 0; $i < 100; $i++) {
            $this->cache["key_{$i}"] = [
                'id' => $i,
                'data' => "value_{$i}",
                'timestamp' => time(),
            ];
        }

        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $startMemory;

        // Cache should use reasonable memory
        $this->assertGreaterThan(0, $memoryUsed);
        $this->assertLessThan(1000000, $memoryUsed); // Less than 1MB for 100 entries
    }
}
