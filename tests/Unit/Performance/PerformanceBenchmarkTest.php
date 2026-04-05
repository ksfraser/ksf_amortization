<?php
namespace Tests\Unit\Performance;

use Ksfraser\Performance\PerformanceBenchmark;
use Ksfraser\Performance\OAuth2LoadTest;
use Ksfraser\Performance\LatencyAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Performance Benchmark Tests
 * 
 * Testing of performance measurement and benchmarking tools:
 * - Latency recording and percentile calculation
 * - Load test simulation
 * - Report generation
 * - Latency analysis
 * 
 * @package   Tests\Unit\Performance
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class PerformanceBenchmarkTest extends TestCase
{
    /**
     * @var PerformanceBenchmark Benchmark instance
     */
    private $benchmark;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->benchmark = new PerformanceBenchmark();
    }

    /**
     * Test: Record single measurement
     *
     * @test
     */
    public function testRecordMeasurement(): void
    {
        $this->benchmark->recordMeasurement('authorization', 50.5);

        $measurements = $this->benchmark->getAllMeasurements();
        $this->assertArrayHasKey('authorization', $measurements);
        $this->assertEquals(1, count($measurements['authorization']));
        $this->assertEquals(50.5, $measurements['authorization'][0]['latency_ms']);
    }

    /**
     * Test: Record multiple measurements
     *
     * @test
     */
    public function testRecordMultipleMeasurements(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->benchmark->recordMeasurement('token_exchange', 100 + ($i * 5));
        }

        $measurements = $this->benchmark->getAllMeasurements();
        $this->assertEquals(10, count($measurements['token_exchange']));
    }

    /**
     * Test: Calculate percentile latency
     *
     * @test
     */
    public function testPercentileCalculation(): void
    {
        // Add 100 measurements from 10ms to 100ms
        for ($i = 0; $i < 100; $i++) {
            $this->benchmark->recordMeasurement('userinfo', 10 + $i);
        }

        $p50 = $this->benchmark->getPercentile('userinfo', 50);
        $p99 = $this->benchmark->getPercentile('userinfo', 99);

        $this->assertNotNull($p50);
        $this->assertNotNull($p99);
        $this->assertGreater($p99, $p50);
    }

    /**
     * Test: Get average latency
     *
     * @test
     */
    public function testAverageLatency(): void
    {
        $this->benchmark->recordMeasurement('auth', 50);
        $this->benchmark->recordMeasurement('auth', 60);
        $this->benchmark->recordMeasurement('auth', 40);

        $avg = $this->benchmark->getAverage('auth');
        $this->assertEquals(50, $avg);
    }

    /**
     * Test: Get operation statistics
     *
     * @test
     */
    public function testOperationStatistics(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->benchmark->recordMeasurement('test_op', $i * 10);
        }

        $stats = $this->benchmark->getStats('test_op');

        $this->assertArrayHasKey('count', $stats);
        $this->assertArrayHasKey('min_ms', $stats);
        $this->assertArrayHasKey('max_ms', $stats);
        $this->assertArrayHasKey('avg_ms', $stats);
        $this->assertArrayHasKey('p50_ms', $stats);
        $this->assertArrayHasKey('p99_ms', $stats);
        $this->assertArrayHasKey('p99_9_ms', $stats);

        $this->assertEquals(11, $stats['count']);
        $this->assertEquals(10, $stats['min_ms']);
        $this->assertEquals(110, $stats['max_ms']);
    }

    /**
     * Test: Check if meets target
     *
     * @test
     */
    public function testMeetsTarget(): void
    {
        // Set target at 100ms for authorization
        $this->benchmark->setTarget('authorization', 100);

        // Add measurements below target
        for ($i = 0; $i < 100; $i++) {
            $this->benchmark->recordMeasurement('authorization', 50 + ($i % 30));
        }

        $this->assertTrue($this->benchmark->meetsTarget('authorization'));
    }

    /**
     * Test: Check if fails target
     *
     * @test
     */
    public function testFailsTarget(): void
    {
        $this->benchmark->setTarget('token_exchange', 150);

        // Add measurements above target
        for ($i = 0; $i < 100; $i++) {
            $this->benchmark->recordMeasurement('token_exchange', 150 + ($i % 50));
        }

        $this->assertFalse($this->benchmark->meetsTarget('token_exchange'));
    }

    /**
     * Test: Record cache access
     *
     * @test
     */
    public function testRecordCacheAccess(): void
    {
        $this->benchmark->recordCacheAccess('token', true);  // Hit
        $this->benchmark->recordCacheAccess('token', true);  // Hit
        $this->benchmark->recordCacheAccess('token', false); // Miss

        $hitRate = $this->benchmark->getCacheHitRate();
        $this->assertAlmostEquals(66.67, $hitRate, 1);
    }

    /**
     * Test: Get cache hit rate
     *
     * @test
     */
    public function testCacheHitRate(): void
    {
        // 9 hits, 1 miss = 90% hit rate
        for ($i = 0; $i < 9; $i++) {
            $this->benchmark->recordCacheAccess('consent', true);
        }
        $this->benchmark->recordCacheAccess('consent', false);

        $hitRate = $this->benchmark->getCacheHitRate();
        $this->assertEquals(90.0, $hitRate);
    }

    /**
     * Test: Get performance report
     *
     * @test
     */
    public function testGetReport(): void
    {
        $this->benchmark->recordMeasurement('authorization', 80);
        $this->benchmark->recordMeasurement('token_exchange', 120);
        $this->benchmark->recordCacheAccess('token', true);

        $report = $this->benchmark->getReport();

        $this->assertArrayHasKey('timestamp', $report);
        $this->assertArrayHasKey('counters', $report);
        $this->assertArrayHasKey('cache_hit_rate_percent', $report);
        $this->assertArrayHasKey('operations', $report);
        $this->assertArrayHasKey('targets', $report);
        $this->assertArrayHasKey('validation', $report);
    }

    /**
     * Test: Export report as JSON
     *
     * @test
     */
    public function testExportJSON(): void
    {
        $this->benchmark->recordMeasurement('test_op', 50);

        $json = $this->benchmark->exportJSON();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('operations', $decoded);
    }

    /**
     * Test: Export report as table
     *
     * @test
     */
    public function testExportTable(): void
    {
        $this->benchmark->recordMeasurement('authorization', 80);

        $table = $this->benchmark->exportTable();

        $this->assertStringContainsString('OAUTH2 PERFORMANCE', $table);
        $this->assertStringContainsString('authorization', $table);
    }

    /**
     * Test: Reset benchmark
     *
     * @test
     */
    public function testReset(): void
    {
        $this->benchmark->recordMeasurement('test', 50);
        $this->benchmark->reset();

        $measurements = $this->benchmark->getAllMeasurements();
        $this->assertEmpty($measurements);
    }

    /**
     * Test: OAuth2 Load Test - Authorization Flow
     *
     * @test
     */
    public function testLoadTestAuthorizationFlow(): void
    {
        $loadTest = new OAuth2LoadTest();

        $loadTest->simulateAuthorizationFlow(10, 2);

        $report = $loadTest->getReport();
        $this->assertGreater(0, $report['counters']['total_operations']);
    }

    /**
     * Test: OAuth2 Load Test - Token Exchange
     *
     * @test
     */
    public function testLoadTestTokenExchange(): void
    {
        $loadTest = new OAuth2LoadTest();

        $loadTest->simulateTokenExchange(50, fn() => usleep(100));

        $report = $loadTest->getReport();
        $this->assertArrayHasKey('token_exchange', $report['operations']);
    }

    /**
     * Test: OAuth2 Load Test - UserInfo Endpoint
     *
     * @test
     */
    public function testLoadTestUserInfo(): void
    {
        $loadTest = new OAuth2LoadTest();

        $loadTest->simulateUserInfoEndpoint(50, fn() => usleep(50));

        $report = $loadTest->getReport();
        $this->assertArrayHasKey('userinfo_endpoint', $report['operations']);
    }

    /**
     * Test: OAuth2 Load Test - Concurrent Simulation
     *
     * @test
     */
    public function testLoadTestConcurrentSimulation(): void
    {
        $loadTest = new OAuth2LoadTest();

        $operationCount = 0;
        $callback = function ($id) use (&$operationCount) {
            $operationCount++;
        };

        $loadTest->runConcurrentSimulation(10, 5, $callback);

        $this->assertEquals(50, $operationCount);
    }

    /**
     * Test: Latency Histogram
     *
     * @test
     */
    public function testLatencyHistogram(): void
    {
        $latencies = [10, 15, 20, 25, 30, 35, 40, 45, 50];

        $histogram = LatencyAnalyzer::getHistogram($latencies, 10);

        $this->assertIsArray($histogram);
        $this->assertGreater(0, count($histogram));
    }

    /**
     * Test: Identify Outliers
     *
     * @test
     */
    public function testIdentifyOutliers(): void
    {
        // Normal distribution: 10-50 (mean ~30)
        // Outlier: 200
        $latencies = array_merge(range(10, 50), [200]);

        $outliers = LatencyAnalyzer::getOutliers($latencies, 2);

        $this->assertContains(200, $outliers);
    }

    /**
     * Test: Distribution Statistics
     *
     * @test
     */
    public function testDistributionStats(): void
    {
        $latencies = [10, 20, 30, 40, 50];

        $stats = LatencyAnalyzer::getDistributionStats($latencies);

        $this->assertArrayHasKey('count', $stats);
        $this->assertArrayHasKey('mean', $stats);
        $this->assertArrayHasKey('median', $stats);
        $this->assertArrayHasKey('variance', $stats);
        $this->assertArrayHasKey('std_dev', $stats);

        $this->assertEquals(5, $stats['count']);
        $this->assertEquals(30, $stats['mean']);
    }

    /**
     * Test: Performance targets validation
     *
     * @test
     */
    public function testPerformanceTargetsValidation(): void
    {
        // Simulate authorization endpoint meeting target
        for ($i = 0; $i < 100; $i++) {
            $latency = 50 + rand(0, 40); // 50-90ms range
            $this->benchmark->recordMeasurement('authorization', $latency);
        }

        // Simulate token exchange meeting target
        for ($i = 0; $i < 100; $i++) {
            $latency = 100 + rand(0, 40); // 100-140ms range
            $this->benchmark->recordMeasurement('token_exchange', $latency);
        }

        // Simulate userinfo meeting target
        for ($i = 0; $i < 100; $i++) {
            $latency = 20 + rand(0, 25); // 20-45ms range
            $this->benchmark->recordMeasurement('userinfo_endpoint', $latency);
        }

        $report = $this->benchmark->getReport();

        // All should meet targets
        $this->assertTrue($this->benchmark->meetsTarget('authorization'));
        $this->assertTrue($this->benchmark->meetsTarget('token_exchange'));
        $this->assertTrue($this->benchmark->meetsTarget('userinfo_endpoint'));
    }

    /**
     * Test: Cache hit rate high (realistic scenario)
     *
     * @test
     */
    public function testCacheHitRateHighScenario(): void
    {
        // Typical OAuth2 flow: high cache reuse
        $totalAccesses = 1000;
        $cacheHitRate = 95;

        $hits = (int)($totalAccesses * $cacheHitRate / 100);
        $misses = $totalAccesses - $hits;

        for ($i = 0; $i < $hits; $i++) {
            $this->benchmark->recordCacheAccess('token', true);
        }
        for ($i = 0; $i < $misses; $i++) {
            $this->benchmark->recordCacheAccess('token', false);
        }

        $actualHitRate = $this->benchmark->getCacheHitRate();
        $this->assertAlmostEquals($cacheHitRate, $actualHitRate, 1);
    }

    /**
     * Test: Latency P99 calculation accuracy
     *
     * @test
     */
    public function testP99Accuracy(): void
    {
        // Create 100 values from 1-100
        for ($i = 1; $i <= 100; $i++) {
            $this->benchmark->recordMeasurement('accuracy_test', $i);
        }

        $p99 = $this->benchmark->getPercentile('accuracy_test', 99);

        // p99 should be ~99
        $this->assertGreaterThan(98, $p99);
        $this->assertLessThan(101, $p99);
    }

    /**
     * Test: Multi-operation benchmarking
     *
     * @test
     */
    public function testMultiOperationBenchmarking(): void
    {
        $operations = ['auth', 'token', 'userinfo'];

        foreach ($operations as $op) {
            for ($i = 0; $i < 50; $i++) {
                $latency = rand(20, 100);
                $this->benchmark->recordMeasurement($op, $latency);
            }
        }

        $report = $this->benchmark->getReport();

        foreach ($operations as $op) {
            $this->assertArrayHasKey($op, $report['operations']);
        }
    }

    /**
     * Test: Metadata tracking in measurements
     *
     * @test
     */
    public function testMetadataTracking(): void
    {
        $this->benchmark->recordMeasurement('traced_op', 50, [
            'client_id' => 'client_123',
            'user_id' => 'user_456',
            'cache_hit' => true
        ]);

        $measurements = $this->benchmark->getAllMeasurements();
        $m = $measurements['traced_op'][0];

        $this->assertEquals('client_123', $m['metadata']['client_id']);
        $this->assertEquals('user_456', $m['metadata']['user_id']);
        $this->assertTrue($m['metadata']['cache_hit']);
    }

    /**
     * Helper: Assert almost equals
     */
    private function assertAlmostEquals($expected, $actual, $delta): void
    {
        $this->assertTrue(
            abs($expected - $actual) <= $delta,
            "Expected $expected ± $delta but got $actual"
        );
    }
}
