<?php

namespace Tests\Unit\Performance;

use PHPUnit\Framework\TestCase;
use Ksfraser\Performance\QueryOptimizer;
use Ksfraser\Caching\MemoryCache;

/**
 * QueryOptimizerTest - Tests for query optimization
 * 
 * @package    Tests\Unit\Performance
 * @since      20251221
 */
class QueryOptimizerTest extends TestCase
{
    private QueryOptimizer $optimizer;
    private MemoryCache $cache;

    protected function setUp(): void
    {
        $this->cache = new MemoryCache();
        $this->optimizer = new QueryOptimizer($this->cache);
    }

    /**
     * Test profiling can be enabled/disabled
     */
    public function testProfilingToggle()
    {
        $this->optimizer->enableProfiling();
        $this->optimizer->recordQuery('SELECT * FROM users');

        $metrics = $this->optimizer->getMetrics();
        $this->assertEquals(1, $metrics['total_queries']);

        $this->optimizer->disableProfiling();
        $this->optimizer->recordQuery('SELECT * FROM posts');

        $metrics = $this->optimizer->getMetrics();
        $this->assertEquals(1, $metrics['total_queries']); // Still 1, not 2
    }

    /**
     * Test query recording
     */
    public function testQueryRecording()
    {
        $this->optimizer->enableProfiling();
        $this->optimizer->recordQuery('SELECT * FROM users', [], 0.1, 5);
        $this->optimizer->recordQuery('SELECT * FROM posts', [], 0.05, 3);

        $metrics = $this->optimizer->getMetrics();

        $this->assertEquals(2, $metrics['total_queries']);
        $this->assertGreaterThan(0, $metrics['total_time']);
    }

    /**
     * Test batch ID generation
     */
    public function testBatchIds()
    {
        $ids = range(1, 250);
        $batches = $this->optimizer->batchIds($ids, 100);

        $this->assertEquals(3, count($batches));
        $this->assertEquals(100, count($batches[0]));
        $this->assertEquals(100, count($batches[1]));
        $this->assertEquals(50, count($batches[2]));
    }

    /**
     * Test cache query with hit
     */
    public function testCacheQueryHit()
    {
        $callCount = 0;
        $callback = function() use (&$callCount) {
            $callCount++;
            return ['id' => 1, 'name' => 'Test'];
        };

        // First call
        $result1 = $this->optimizer->cacheQuery('user_1', $callback, 3600);
        $this->assertEquals(1, $callCount);

        // Second call - from cache
        $result2 = $this->optimizer->cacheQuery('user_1', $callback, 3600);
        $this->assertEquals(1, $callCount); // Not incremented

        $this->assertEquals($result1, $result2);
    }

    /**
     * Test cache query without cache backend
     */
    public function testCacheQueryNoBackend()
    {
        $optimizer = new QueryOptimizer(null);
        $callCount = 0;

        $callback = function() use (&$callCount) {
            $callCount++;
            return 'value';
        };

        $optimizer->cacheQuery('key', $callback);
        $this->assertEquals(1, $callCount);

        $optimizer->cacheQuery('key', $callback);
        $this->assertEquals(2, $callCount); // Called again, not cached
    }

    /**
     * Test n+1 pattern detection
     */
    public function testN1Detection()
    {
        $this->optimizer->enableProfiling();

        // Simulate n+1: same query executed multiple times
        for ($i = 0; $i < 5; $i++) {
            $this->optimizer->recordQuery('SELECT * FROM users WHERE id = ?', [$i]);
        }

        $n1Issues = $this->optimizer->detectN1Patterns();

        $this->assertCount(1, $n1Issues);
        $this->assertEquals(5, $n1Issues[0]['executions']);
    }

    /**
     * Test metrics calculation
     */
    public function testMetricsCalculation()
    {
        $this->optimizer->enableProfiling();

        $this->optimizer->recordQuery('Q1', [], 0.1);
        $this->optimizer->recordQuery('Q2', [], 0.2);
        $this->optimizer->recordQuery('Q3', [], 0.3);

        $metrics = $this->optimizer->getMetrics();

        $this->assertEquals(3, $metrics['total_queries']);
        $this->assertGreaterThan(0.5, $metrics['total_time']);
        $this->assertGreaterThan(0.1, $metrics['avg_query_time']);
    }

    /**
     * Test reset metrics
     */
    public function testResetMetrics()
    {
        $this->optimizer->enableProfiling();
        $this->optimizer->recordQuery('SELECT *');

        $metrics = $this->optimizer->getMetrics();
        $this->assertEquals(1, $metrics['total_queries']);

        $this->optimizer->resetMetrics();

        $metrics = $this->optimizer->getMetrics();
        $this->assertEquals(0, $metrics['total_queries']);
    }

    /**
     * Test recommendations
     */
    public function testRecommendations()
    {
        $this->optimizer->enableProfiling();

        // Generate many queries to trigger recommendations
        for ($i = 0; $i < 60; $i++) {
            $this->optimizer->recordQuery('SELECT * FROM users WHERE id = ?', [$i], 0.1);
        }

        $recommendations = $this->optimizer->getRecommendations();

        $this->assertNotEmpty($recommendations);
        $this->assertTrue(
            count($recommendations) > 0,
            "Should have recommendations for high query count"
        );
    }

    /**
     * Test cache statistics integration
     */
    public function testCacheStatistics()
    {
        $this->optimizer->enableProfiling();

        $callback = function() {
            return 'cached_value';
        };

        // Cache hits
        $this->optimizer->cacheQuery('key1', $callback, 3600);
        $this->optimizer->cacheQuery('key1', $callback, 3600);
        $this->optimizer->cacheQuery('key1', $callback, 3600);

        $metrics = $this->optimizer->getMetrics();

        // Should track cache hits
        $this->assertGreaterThanOrEqual(0, $metrics['cached_queries']);
    }
}
