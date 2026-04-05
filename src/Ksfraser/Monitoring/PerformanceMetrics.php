<?php
namespace Ksfraser\Monitoring;

use Ksfraser\Performance\PerformanceBenchmark;

/**
 * Performance Metrics Collector
 * 
 * Collects, aggregates, and tracks performance metrics for OAuth2 operations.
 * Provides real-time monitoring and historical analytics.
 * 
 * Metrics Tracked:
 * - Operation latencies (authorization, token, userinfo, consent)
 * - Cache hit rates (token, authorization code, consent)
 * - Error rates and error distribution
 * - Throughput metrics
 * - Resource usage (memory, connections)
 * 
 * @package   Ksfraser\Monitoring
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class PerformanceMetrics
{
    /**
     * @var PerformanceBenchmark Benchmark instance
     */
    private $benchmark;

    /**
     * @var array Aggregated metrics
     */
    private $metrics = [];

    /**
     * @var array Time-series data for historical tracking
     */
    private $timeSeries = [];

    /**
     * @var array Error tracking
     */
    private $errors = [
        'total_errors' => 0,
        'by_type' => [],
        'by_operation' => []
    ];

    /**
     * @var array Resource metrics
     */
    private $resources = [
        'memory_peak_mb' => 0,
        'memory_current_mb' => 0,
        'active_connections' => 0,
        'cached_items' => 0
    ];

    /**
     * @var array Configuration
     */
    private $config = [
        'track_history' => true,
        'history_limit' => 1000,
        'error_sampling_rate' => 1.0 // 1.0 = 100%, 0.1 = 10%
    ];

    /**
     * PerformanceMetrics constructor
     *
     * @param PerformanceBenchmark|null $benchmark Benchmark instance
     */
    public function __construct(?PerformanceBenchmark $benchmark = null)
    {
        $this->benchmark = $benchmark ?? new PerformanceBenchmark();
    }

    /**
     * Record metric data point
     *
     * @param string $metric Metric name
     * @param float $value Value
     * @param array $tags Optional tags for filtering
     *
     * @return void
     */
    public function recordMetric(string $metric, float $value, array $tags = []): void
    {
        if (!isset($this->metrics[$metric])) {
            $this->metrics[$metric] = [
                'values' => [],
                'count' => 0,
                'min' => PHP_FLOAT_MAX,
                'max' => PHP_FLOAT_MIN,
                'sum' => 0,
                'lastUpdated' => time()
            ];
        }

        $this->metrics[$metric]['values'][] = $value;
        $this->metrics[$metric]['count']++;
        $this->metrics[$metric]['min'] = min($this->metrics[$metric]['min'], $value);
        $this->metrics[$metric]['max'] = max($this->metrics[$metric]['max'], $value);
        $this->metrics[$metric]['sum'] += $value;
        $this->metrics[$metric]['lastUpdated'] = time();

        // Keep array size manageable
        if (count($this->metrics[$metric]['values']) > $this->config['history_limit']) {
            array_shift($this->metrics[$metric]['values']);
        }

        // Track time series if enabled
        if ($this->config['track_history']) {
            if (!isset($this->timeSeries[$metric])) {
                $this->timeSeries[$metric] = [];
            }

            $this->timeSeries[$metric][] = [
                'timestamp' => time(),
                'value' => $value,
                'tags' => $tags
            ];

            // Limit time series size
            if (count($this->timeSeries[$metric]) > $this->config['history_limit']) {
                array_shift($this->timeSeries[$metric]);
            }
        }
    }

    /**
     * Record error event
     *
     * @param string $errorType Error type
     * @param string $operation Operation name
     * @param string|null $message Error message
     * @param array $context Additional context
     *
     * @return void
     */
    public function recordError(
        string $errorType,
        string $operation,
        ?string $message = null,
        array $context = []
    ): void {
        // Apply sampling
        if (rand(0, 100) / 100 > $this->config['error_sampling_rate']) {
            return;
        }

        $this->errors['total_errors']++;

        if (!isset($this->errors['by_type'][$errorType])) {
            $this->errors['by_type'][$errorType] = 0;
        }
        $this->errors['by_type'][$errorType]++;

        if (!isset($this->errors['by_operation'][$operation])) {
            $this->errors['by_operation'][$operation] = 0;
        }
        $this->errors['by_operation'][$operation]++;

        // Track error details
        if (!isset($this->errors['details'])) {
            $this->errors['details'] = [];
        }

        $this->errors['details'][] = [
            'timestamp' => time(),
            'type' => $errorType,
            'operation' => $operation,
            'message' => $message,
            'context' => $context
        ];

        // Limit details
        if (count($this->errors['details']) > 100) {
            array_shift($this->errors['details']);
        }
    }

    /**
     * Update resource metrics
     *
     * @return void
     */
    public function updateResourceMetrics(): void
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $memoryPeak = memory_get_peak_usage(true) / 1024 / 1024;

        $this->resources['memory_current_mb'] = round($memoryUsage, 2);
        $this->resources['memory_peak_mb'] = max(
            $this->resources['memory_peak_mb'],
            round($memoryPeak, 2)
        );

        $this->recordMetric('memory_usage_mb', $memoryUsage);
    }

    /**
     * Set active connections count
     *
     * @param int $count Number of active connections
     *
     * @return void
     */
    public function setActiveConnections(int $count): void
    {
        $this->resources['active_connections'] = $count;
        $this->recordMetric('active_connections', $count);
    }

    /**
     * Set cached items count
     *
     * @param int $count Number of cached items
     *
     * @return void
     */
    public function setCachedItems(int $count): void
    {
        $this->resources['cached_items'] = $count;
        $this->recordMetric('cached_items', $count);
    }

    /**
     * Get metric statistics
     *
     * @param string $metric Metric name
     *
     * @return array|null Metric statistics or null
     */
    public function getMetricStats(string $metric): ?array
    {
        if (!isset($this->metrics[$metric])) {
            return null;
        }

        $data = $this->metrics[$metric];
        $count = $data['count'];
        $avg = $count > 0 ? $data['sum'] / $count : 0;

        return [
            'metric' => $metric,
            'count' => $count,
            'min' => $data['min'],
            'max' => $data['max'],
            'avg' => round($avg, 2),
            'sum' => $data['sum'],
            'last_updated' => date('Y-m-d H:i:s', $data['lastUpdated']),
            'variance' => $this->calculateVariance($data['values'], $avg),
            'std_dev' => $this->calculateStdDev($data['values'], $avg)
        ];
    }

    /**
     * Get all metrics summary
     *
     * @return array Summary of all metrics
     */
    public function getSummary(): array
    {
        $summary = [
            'timestamp' => date('Y-m-d H:i:s'),
            'metrics' => [],
            'resources' => $this->resources,
            'errors' => [
                'total_errors' => $this->errors['total_errors'],
                'by_type' => $this->errors['by_type'],
                'by_operation' => $this->errors['by_operation']
            ]
        ];

        foreach (array_keys($this->metrics) as $metric) {
            $summary['metrics'][$metric] = $this->getMetricStats($metric);
        }

        return $summary;
    }

    /**
     * Get dashboard data
     *
     * Dashboard-friendly format for real-time monitoring.
     *
     * @return array Dashboard data
     */
    public function getDashboardData(): array
    {
        $this->updateResourceMetrics();

        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => $this->getHealthStatus(),
            'performance' => [
                'operations' => $this->getPerformanceSummary(),
                'cache_hit_rates' => $this->getCacheHitRates(),
                'error_rate' => $this->getErrorRate()
            ],
            'resources' => $this->resources,
            'top_metrics' => $this->getTopMetrics(5)
        ];
    }

    /**
     * Get system health status
     *
     * @return string Status: healthy, degraded, critical
     */
    public function getHealthStatus(): string
    {
        $errorRate = $this->getErrorRate();
        $memoryUsage = $this->resources['memory_current_mb'];

        if ($errorRate > 5 || $memoryUsage > 512) {
            return 'critical';
        } else if ($errorRate > 1 || $memoryUsage > 256) {
            return 'degraded';
        } else {
            return 'healthy';
        }
    }

    /**
     * Get error rate percentage
     *
     * @return float Error rate percentage
     */
    public function getErrorRate(): float
    {
        if ($this->benchmark->getReport()['counters']['total_operations'] === 0) {
            return 0;
        }

        return ($this->errors['total_errors'] /
            $this->benchmark->getReport()['counters']['total_operations']) * 100;
    }

    /**
     * Get performance summary
     *
     * @return array Performance summary by operation
     */
    public function getPerformanceSummary(): array
    {
        $report = $this->benchmark->getReport();
        $summary = [];

        foreach ($report['operations'] as $op => $stats) {
            $summary[$op] = [
                'p99_ms' => round($stats['p99_ms'], 2),
                'avg_ms' => round($stats['avg_ms'], 2),
                'target_met' => $stats['meets_target'],
                'sample_count' => $stats['count']
            ];
        }

        return $summary;
    }

    /**
     * Get cache hit rates
     *
     * @return array Cache hit rates by type
     */
    public function getCacheHitRates(): array
    {
        return [
            'overall' => round($this->benchmark->getCacheHitRate(), 2),
            'benchmark_report' => $this->benchmark->getReport()['cache_hit_rate_percent']
        ];
    }

    /**
     * Get top metrics by value
     *
     * @param int $limit Number of top metrics to return
     *
     * @return array Top metrics
     */
    public function getTopMetrics(int $limit = 5): array
    {
        $sorted = [];

        foreach ($this->metrics as $name => $data) {
            if ($data['count'] > 0) {
                $avg = $data['sum'] / $data['count'];
                $sorted[$name] = $avg;
            }
        }

        arsort($sorted);
        $top = array_slice($sorted, 0, $limit);

        $result = [];
        foreach ($top as $name => $value) {
            $result[$name] = round($value, 2);
        }

        return $result;
    }

    /**
     * Get time series data
     *
     * @param string $metric Metric name
     * @param int|null $limit Limit results count
     *
     * @return array Time series data
     */
    public function getTimeSeries(string $metric, ?int $limit = null): array
    {
        if (!isset($this->timeSeries[$metric])) {
            return [];
        }

        $data = $this->timeSeries[$metric];

        if ($limit !== null && count($data) > $limit) {
            $data = array_slice($data, -$limit);
        }

        return $data;
    }

    /**
     * Set configuration
     *
     * @param array $config Configuration options
     *
     * @return void
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Reset all metrics
     *
     * @return void
     */
    public function reset(): void
    {
        $this->metrics = [];
        $this->timeSeries = [];
        $this->errors = [
            'total_errors' => 0,
            'by_type' => [],
            'by_operation' => []
        ];
    }

    /**
     * Calculate variance
     */
    private function calculateVariance(array $values, float $mean): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $sum = 0;
        foreach ($values as $value) {
            $sum += pow($value - $mean, 2);
        }

        return round($sum / count($values), 2);
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStdDev(array $values, float $mean): float
    {
        $variance = $this->calculateVariance($values, $mean);
        return round(sqrt($variance), 2);
    }
}

/**
 * Metrics Collector Interface
 * 
 * Provides structured collection of metrics throughout system operations.
 * 
 * @package   Ksfraser\Monitoring
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class MetricsCollector
{
    /**
     * @var PerformanceMetrics Metrics instance
     */
    private static $instance;

    /**
     * Get or create metrics instance
     *
     * @return PerformanceMetrics Metrics instance
     */
    public static function getInstance(): PerformanceMetrics
    {
        if (self::$instance === null) {
            self::$instance = new PerformanceMetrics();
        }

        return self::$instance;
    }

    /**
     * Set custom instance
     *
     * @param PerformanceMetrics $metrics Metrics instance
     *
     * @return void
     */
    public static function setInstance(PerformanceMetrics $metrics): void
    {
        self::$instance = $metrics;
    }

    /**
     * Record operation latency
     *
     * @param string $operation Operation name
     * @param float $latencyMs Latency in milliseconds
     * @param bool $success Whether operation succeeded
     *
     * @return void
     */
    public static function recordLatency(string $operation, float $latencyMs, bool $success = true): void
    {
        $metrics = self::getInstance();
        $metrics->recordMetric("${operation}_latency_ms", $latencyMs, [
            'success' => $success
        ]);

        if (!$success) {
            $metrics->recordError('operation_failed', $operation);
        }
    }

    /**
     * Record cache access
     *
     * @param string $cacheType Cache type (token, code, consent)
     * @param bool $isHit Cache hit or miss
     *
     * @return void
     */
    public static function recordCacheAccess(string $cacheType, bool $isHit): void
    {
        $metrics = self::getInstance();
        $metrics->recordMetric("cache_${cacheType}_" . ($isHit ? 'hit' : 'miss'), 1);
    }

    /**
     * Record authentication attempt
     *
     * @param bool $success Whether authentication succeeded
     * @param string|null $reason Reason if failed
     *
     * @return void
     */
    public static function recordAuthAttempt(bool $success, ?string $reason = null): void
    {
        $metrics = self::getInstance();

        if (!$success) {
            $metrics->recordError('auth_failed', 'authentication', $reason ?? 'Unknown');
        }
    }

    /**
     * Export metrics report
     *
     * @return string JSON-formatted report
     */
    public static function exportReport(): string
    {
        return json_encode(
            self::getInstance()->getSummary(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Export dashboard data
     *
     * @return string JSON-formatted dashboard data
     */
    public static function exportDashboard(): string
    {
        return json_encode(
            self::getInstance()->getDashboardData(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }
}
