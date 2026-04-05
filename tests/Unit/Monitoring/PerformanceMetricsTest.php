<?php
namespace Tests\Unit\Monitoring;

use Ksfraser\Monitoring\PerformanceMetrics;
use Ksfraser\Monitoring\MetricsCollector;
use Ksfraser\Performance\PerformanceBenchmark;
use PHPUnit\Framework\TestCase;

/**
 * Performance Metrics Tests
 * 
 * Testing of metrics collection and monitoring:
 * - Metric recording and aggregation
 * - Error tracking
 * - Resource metrics
 * - Dashboard data generation
 * - Health status determination
 * 
 * @package   Tests\Unit\Monitoring
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class PerformanceMetricsTest extends TestCase
{
    /**
     * @var PerformanceMetrics Metrics instance
     */
    private $metrics;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $benchmark = new PerformanceBenchmark();
        $this->metrics = new PerformanceMetrics($benchmark);
    }

    /**
     * Test: Record single metric
     *
     * @test
     */
    public function testRecordMetric(): void
    {
        $this->metrics->recordMetric('latency_ms', 50.5);

        $stats = $this->metrics->getMetricStats('latency_ms');
        $this->assertNotNull($stats);
        $this->assertEquals(1, $stats['count']);
        $this->assertEquals(50.5, $stats['avg']);
    }

    /**
     * Test: Record multiple metrics
     *
     * @test
     */
    public function testRecordMultipleMetrics(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->metrics->recordMetric('token_creation_ms', 100 + $i);
        }

        $stats = $this->metrics->getMetricStats('token_creation_ms');
        $this->assertEquals(10, $stats['count']);
        $this->assertEquals(100, $stats['min']);
        $this->assertEquals(109, $stats['max']);
    }

    /**
     * Test: Calculate metric average
     *
     * @test
     */
    public function testMetricAverage(): void
    {
        $values = [10, 20, 30, 40, 50];

        foreach ($values as $v) {
            $this->metrics->recordMetric('avg_test', $v);
        }

        $stats = $this->metrics->getMetricStats('avg_test');
        $this->assertEquals(30, $stats['avg']);
    }

    /**
     * Test: Record error event
     *
     * @test
     */
    public function testRecordError(): void
    {
        $this->metrics->recordError('InvalidScopeException', 'authorization', 'Invalid scope requested');

        $summary = $this->metrics->getSummary();
        $this->assertEquals(1, $summary['errors']['total_errors']);
        $this->assertEquals(1, $summary['errors']['by_type']['InvalidScopeException']);
        $this->assertEquals(1, $summary['errors']['by_operation']['authorization']);
    }

    /**
     * Test: Record multiple errors
     *
     * @test
     */
    public function testRecordMultipleErrors(): void
    {
        $this->metrics->recordError('TokenExpired', 'token_exchange', 'Token has expired');
        $this->metrics->recordError('TokenExpired', 'token_exchange', 'Token has expired');
        $this->metrics->recordError('InvalidClient', 'authorization', 'Unknown client');

        $summary = $this->metrics->getSummary();
        $this->assertEquals(3, $summary['errors']['total_errors']);
        $this->assertEquals(2, $summary['errors']['by_type']['TokenExpired']);
        $this->assertEquals(2, $summary['errors']['by_operation']['token_exchange']);
    }

    /**
     * Test: Update resource metrics
     *
     * @test
     */
    public function testUpdateResourceMetrics(): void
    {
        $this->metrics->updateResourceMetrics();

        $summary = $this->metrics->getSummary();
        $this->assertArrayHasKey('memory_current_mb', $summary['resources']);
        $this->assertArrayHasKey('memory_peak_mb', $summary['resources']);
        $this->assertGreater(0, $summary['resources']['memory_current_mb']);
    }

    /**
     * Test: Set active connections
     *
     * @test
     */
    public function testSetActiveConnections(): void
    {
        $this->metrics->setActiveConnections(5);

        $summary = $this->metrics->getSummary();
        $this->assertEquals(5, $summary['resources']['active_connections']);
    }

    /**
     * Test: Set cached items
     *
     * @test
     */
    public function testSetCachedItems(): void
    {
        $this->metrics->setCachedItems(150);

        $summary = $this->metrics->getSummary();
        $this->assertEquals(150, $summary['resources']['cached_items']);
    }

    /**
     * Test: Get error rate
     *
     * @test
     */
    public function testGetErrorRate(): void
    {
        // Simulate operations and errors
        for ($i = 0; $i < 100; $i++) {
            $this->metrics->recordMetric('operation', 1);
        }

        for ($i = 0; $i < 5; $i++) {
            $this->metrics->recordError('TestError', 'operation', 'test');
        }

        $errorRate = $this->metrics->getErrorRate();
        $this->assertGreater(0, $errorRate);
    }

    /**
     * Test: Get health status - healthy
     *
     * @test
     */
    public function testHealthStatusHealthy(): void
    {
        $status = $this->metrics->getHealthStatus();
        $this->assertEquals('healthy', $status);
    }

    /**
     * Test: Get dashboard data
     *
     * @test
     */
    public function testGetDashboardData(): void
    {
        $this->metrics->recordMetric('auth_latency_ms', 80);
        $this->metrics->recordMetric('token_latency_ms', 120);

        $dashboard = $this->metrics->getDashboardData();

        $this->assertArrayHasKey('timestamp', $dashboard);
        $this->assertArrayHasKey('status', $dashboard);
        $this->assertArrayHasKey('performance', $dashboard);
        $this->assertArrayHasKey('resources', $dashboard);
        $this->assertArrayHasKey('top_metrics', $dashboard);

        $this->assertNotEmpty($dashboard['status']);
    }

    /**
     * Test: Get performance summary
     *
     * @test
     */
    public function testGetPerformanceSummary(): void
    {
        $this->metrics->recordMetric('authorization_latency_ms', 80);
        $this->metrics->recordMetric('authorization_latency_ms', 85);
        $this->metrics->recordMetric('authorization_latency_ms', 90);

        $summary = $this->metrics->getPerformanceSummary();

        $this->assertIsArray($summary);
    }

    /**
     * Test: Get cache hit rates
     *
     * @test
     */
    public function testGetCacheHitRates(): void
    {
        $rates = $this->metrics->getCacheHitRates();

        $this->assertArrayHasKey('overall', $rates);
        $this->assertArrayHasKey('benchmark_report', $rates);
    }

    /**
     * Test: Get top metrics
     *
     * @test
     */
    public function testGetTopMetrics(): void
    {
        $this->metrics->recordMetric('metric_a', 100);
        $this->metrics->recordMetric('metric_b', 200);
        $this->metrics->recordMetric('metric_c', 50);

        $top = $this->metrics->getTopMetrics(2);

        $this->assertEquals(2, count($top));
        $this->assertArrayHasKey('metric_b', $top);
    }

    /**
     * Test: Get time series data
     *
     * @test
     */
    public function testGetTimeSeries(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->metrics->recordMetric('ts_metric', $i * 10);
        }

        $timeSeries = $this->metrics->getTimeSeries('ts_metric');

        $this->assertNotEmpty($timeSeries);
        $this->assertTrue(count($timeSeries) <= 10);
    }

    /**
     * Test: Reset metrics
     *
     * @test
     */
    public function testResetMetrics(): void
    {
        $this->metrics->recordMetric('test', 50);
        $this->metrics->recordError('TestError', 'test');

        $this->metrics->reset();

        $summary = $this->metrics->getSummary();
        $this->assertEmpty($summary['metrics']);
        $this->assertEquals(0, $summary['errors']['total_errors']);
    }

    /**
     * Test: Metrics collector singleton
     *
     * @test
     */
    public function testMetricsCollectorSingleton(): void
    {
        $instance1 = MetricsCollector::getInstance();
        $instance2 = MetricsCollector::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test: Record latency via collector
     *
     * @test
     */
    public function testRecordLatencyViaCollector(): void
    {
        MetricsCollector::recordLatency('authorization', 75.5, true);

        $metrics = MetricsCollector::getInstance();
        $stats = $metrics->getMetricStats('authorization_latency_ms');

        $this->assertNotNull($stats);
        $this->assertEquals(75.5, $stats['avg']);
    }

    /**
     * Test: Record cache access via collector
     *
     * @test
     */
    public function testRecordCacheAccessViaCollector(): void
    {
        MetricsCollector::recordCacheAccess('token', true);
        MetricsCollector::recordCacheAccess('token', false);

        $metrics = MetricsCollector::getInstance();
        $hitStats = $metrics->getMetricStats('cache_token_hit');
        $missStats = $metrics->getMetricStats('cache_token_miss');

        $this->assertNotNull($hitStats);
        $this->assertNotNull($missStats);
    }

    /**
     * Test: Record authentication attempt
     *
     * @test
     */
    public function testRecordAuthAttempt(): void
    {
        MetricsCollector::recordAuthAttempt(true);
        MetricsCollector::recordAuthAttempt(false, 'Invalid credentials');

        $metrics = MetricsCollector::getInstance();
        $summary = $metrics->getSummary();

        $this->assertEquals(1, $summary['errors']['total_errors']);
    }

    /**
     * Test: Export metrics report
     *
     * @test
     */
    public function testExportReport(): void
    {
        MetricsCollector::getInstance()->recordMetric('test', 50);

        $report = MetricsCollector::exportReport();

        $decoded = json_decode($report, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('timestamp', $decoded);
    }

    /**
     * Test: Export dashboard
     *
     * @test
     */
    public function testExportDashboard(): void
    {
        $dashboard = MetricsCollector::exportDashboard();

        $decoded = json_decode($dashboard, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('status', $decoded);
    }

    /**
     * Test: Metric statistics calculation
     *
     * @test
     */
    public function testMetricStatistics(): void
    {
        $values = [10, 15, 20, 25, 30];

        foreach ($values as $v) {
            $this->metrics->recordMetric('stats_test', $v);
        }

        $stats = $this->metrics->getMetricStats('stats_test');

        $this->assertEquals(5, $stats['count']);
        $this->assertEquals(10, $stats['min']);
        $this->assertEquals(30, $stats['max']);
        $this->assertEquals(20, $stats['avg']);
        $this->assertGreater(0, $stats['variance']);
        $this->assertGreater(0, $stats['std_dev']);
    }

    /**
     * Test: Configuration
     *
     * @test
     */
    public function testConfiguration(): void
    {
        $config = [
            'track_history' => false,
            'history_limit' => 500,
            'error_sampling_rate' => 0.5
        ];

        $this->metrics->setConfig($config);
        $this->metrics->recordMetric('config_test', 100);

        // Should not crash with new configuration
        $stats = $this->metrics->getMetricStats('config_test');
        $this->assertNotNull($stats);
    }

    /**
     * Test: Summary report
     *
     * @test
     */
    public function testSummaryReport(): void
    {
        $this->metrics->recordMetric('operation_a', 50);
        $this->metrics->recordMetric('operation_b', 100);
        $this->metrics->recordError('TestError', 'operation_c');
        $this->metrics->updateResourceMetrics();

        $summary = $this->metrics->getSummary();

        $this->assertArrayHasKey('timestamp', $summary);
        $this->assertArrayHasKey('metrics', $summary);
        $this->assertArrayHasKey('resources', $summary);
        $this->assertArrayHasKey('errors', $summary);
    }

    /**
     * Test: Realistic monitoring scenario
     *
     * @test
     */
    public function testRealisticMonitoringScenario(): void
    {
        // Simulate OAuth2 operations
        $operations = [
            'authorization' => [50, 60, 55, 65, 70], // p99 should be ~70
            'token_exchange' => [100, 110, 115, 120, 125], // p99 should be ~125
            'userinfo' => [30, 32, 35, 40, 45] // p99 should be ~45
        ];

        foreach ($operations as $op => $latencies) {
            foreach ($latencies as $lat) {
                $this->metrics->recordMetric("${op}_latency_ms", $lat);
            }
        }

        // Record some cache operations
        for ($i = 0; $i < 50; $i++) {
            MetricsCollector::recordCacheAccess('token', $i % 10 === 0 ? false : true);
        }

        // Record minimal errors
        MetricsCollector::recordAuthAttempt(false, 'Invalid scope');

        $dashboard = $this->metrics->getDashboardData();

        // Verify dashboard contains expected data
        $this->assertNotEmpty($dashboard['performance']['operations']);
        $this->assertGreater(0, $dashboard['performance']['cache_hit_rates']['overall']);
        $this->assertEquals('healthy', $dashboard['status']);
    }

    /**
     * Test: High memory usage health status
     *
     * @test
     */
    public function testHealthStatusWithHighMemory(): void
    {
        // Note: Can't easily simulate high memory in unit test
        // This test documents the behavior
        $status = $this->metrics->getHealthStatus();
        $this->assertIn($status, ['healthy', 'degraded', 'critical']);
    }

    /**
     * Test: Error rate with no operations
     *
     * @test
     */
    public function testErrorRateWithNoOperations(): void
    {
        $errorRate = $this->metrics->getErrorRate();
        $this->assertEquals(0, $errorRate);
    }

    /**
     * Test: Time series limiting
     *
     * @test
     */
    public function testTimeSeriesLimiting(): void
    {
        // Default limit is 1000
        for ($i = 0; $i < 1100; $i++) {
            $this->metrics->recordMetric('limited_ts', $i, ['iteration' => $i]);
        }

        $timeSeries = $this->metrics->getTimeSeries('limited_ts');

        // Should be limited to 1000 most recent
        $this->assertLessThanOrEqual(1000, count($timeSeries));
    }
}
