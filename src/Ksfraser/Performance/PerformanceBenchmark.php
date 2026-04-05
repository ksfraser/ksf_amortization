<?php
namespace Ksfraser\Performance;

use Ksfraser\Security\OAuth2\OAuth2Controller;
use Ksfraser\Security\OAuth2\Repositories\AuthorizationCodeRepository;
use Ksfraser\Security\OAuth2\Repositories\OAuth2TokenRepository;
use Ksfraser\Security\Exceptions\TokenException;

/**
 * Performance Benchmark Suite
 * 
 * Comprehensive performance testing and benchmarking for OAuth2 endpoints.
 * Measures latency, throughput, and cache hit rates.
 * 
 * Success Criteria (from Phase 18D):
 * - Authorization endpoint: <100ms (p99)
 * - Token exchange: <150ms (p99)
 * - UserInfo endpoint: <50ms (p99)
 * - Cache hit rate: >95% tokens, >90% consent
 * - Load capacity: 1000+ concurrent requests
 * 
 * @package   Ksfraser\Performance
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class PerformanceBenchmark
{
    /**
     * @var array Collected latency measurements
     */
    private $measurements = [];

    /**
     * @var array Operation counters
     */
    private $counters = [
        'total_operations' => 0,
        'successful_operations' => 0,
        'failed_operations' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0
    ];

    /**
     * @var array Performance targets
     */
    private $targets = [
        'authorization_endpoint_p99_ms' => 100,
        'token_exchange_p99_ms' => 150,
        'userinfo_endpoint_p99_ms' => 50,
        'token_cache_hit_rate_percent' => 95,
        'consent_cache_hit_rate_percent' => 90
    ];

    /**
     * Record a latency measurement
     *
     * @param string $operation Operation name (authorization, token_exchange, etc.)
     * @param float $latencyMs Latency in milliseconds
     * @param array $metadata Additional metadata (cache_hit, client_id, etc.)
     *
     * @return void
     */
    public function recordMeasurement(string $operation, float $latencyMs, array $metadata = []): void
    {
        if (!isset($this->measurements[$operation])) {
            $this->measurements[$operation] = [];
        }

        $this->measurements[$operation][] = [
            'latency_ms' => $latencyMs,
            'timestamp' => time(),
            'metadata' => $metadata
        ];

        $this->counters['total_operations']++;

        if (isset($metadata['success'])) {
            if ($metadata['success']) {
                $this->counters['successful_operations']++;
            } else {
                $this->counters['failed_operations']++;
            }
        }
    }

    /**
     * Get percentile latency for operation
     *
     * @param string $operation Operation name
     * @param float $percentile Percentile (0-100)
     *
     * @return float|null Latency at percentile or null if not available
     */
    public function getPercentile(string $operation, float $percentile = 50): ?float
    {
        if (!isset($this->measurements[$operation]) || empty($this->measurements[$operation])) {
            return null;
        }

        $latencies = array_map(
            fn($m) => $m['latency_ms'],
            $this->measurements[$operation]
        );

        sort($latencies);
        $index = (int)ceil((count($latencies) * $percentile / 100)) - 1;
        $index = max(0, min($index, count($latencies) - 1));

        return $latencies[$index];
    }

    /**
     * Get average latency
     *
     * @param string $operation Operation name
     *
     * @return float|null Average latency or null
     */
    public function getAverage(string $operation): ?float
    {
        if (!isset($this->measurements[$operation]) || empty($this->measurements[$operation])) {
            return null;
        }

        $sum = array_sum(array_map(fn($m) => $m['latency_ms'], $this->measurements[$operation]));
        return $sum / count($this->measurements[$operation]);
    }

    /**
     * Get measurement statistics
     *
     * @param string $operation Operation name
     *
     * @return array Statistics including min, max, avg, p50, p99, p99.9
     */
    public function getStats(string $operation): ?array
    {
        if (!isset($this->measurements[$operation]) || empty($this->measurements[$operation])) {
            return null;
        }

        $latencies = array_map(
            fn($m) => $m['latency_ms'],
            $this->measurements[$operation]
        );

        sort($latencies);

        return [
            'operation' => $operation,
            'count' => count($latencies),
            'min_ms' => min($latencies),
            'max_ms' => max($latencies),
            'avg_ms' => array_sum($latencies) / count($latencies),
            'p50_ms' => $this->getPercentile($operation, 50),
            'p95_ms' => $this->getPercentile($operation, 95),
            'p99_ms' => $this->getPercentile($operation, 99),
            'p99_9_ms' => $this->getPercentile($operation, 99.9),
            'target_ms' => $this->targets[$operation . '_p99_ms'] ?? null,
            'meets_target' => $this->meetsTarget($operation)
        ];
    }

    /**
     * Check if operation meets performance target
     *
     * @param string $operation Operation name
     *
     * @return bool True if p99 latency meets target
     */
    public function meetsTarget(string $operation): bool
    {
        $target = $this->targets[$operation . '_p99_ms'] ?? null;
        if ($target === null) {
            return true;
        }

        return $this->getPercentile($operation, 99) <= $target;
    }

    /**
     * Record cache hit/miss
     *
     * @param string $cacheType cache type (token, consent, code)
     * @param bool $isHit True for hit, false for miss
     *
     * @return void
     */
    public function recordCacheAccess(string $cacheType, bool $isHit): void
    {
        if ($isHit) {
            $this->counters['cache_hits']++;
        } else {
            $this->counters['cache_misses']++;
        }
    }

    /**
     * Get cache hit rate
     *
     * @return float Hit rate percentage (0-100)
     */
    public function getCacheHitRate(): float
    {
        $total = $this->counters['cache_hits'] + $this->counters['cache_misses'];
        if ($total === 0) {
            return 0;
        }
        return ($this->counters['cache_hits'] / $total) * 100;
    }

    /**
     * Get all measurements
     *
     * @return array All measurements by operation
     */
    public function getAllMeasurements(): array
    {
        return $this->measurements;
    }

    /**
     * Get performance report
     *
     * @return array Comprehensive report
     */
    public function getReport(): array
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'counters' => $this->counters,
            'cache_hit_rate_percent' => round($this->getCacheHitRate(), 2),
            'operations' => [],
            'targets' => $this->targets,
            'validation' => []
        ];

        // Add stats for each operation
        foreach (array_keys($this->measurements) as $operation) {
            $report['operations'][$operation] = $this->getStats($operation);
        }

        // Validate targets
        foreach ($this->targets as $target => $value) {
            if (strpos($target, '_p99_ms') !== false) {
                $operation = str_replace('_p99_ms', '', $target);
                $report['validation'][$target] = [
                    'target' => $value,
                    'actual' => $this->getPercentile($operation, 99),
                    'met' => $this->meetsTarget($operation) ? 'PASS' : 'FAIL'
                ];
            }
        }

        return $report;
    }

    /**
     * Export report as JSON
     *
     * @return string JSON-formatted report
     */
    public function exportJSON(): string
    {
        return json_encode($this->getReport(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Export report as table format
     *
     * @return string Table-formatted report
     */
    public function exportTable(): string
    {
        $report = $this->getReport();
        $output = "OAUTH2 PERFORMANCE BENCHMARK REPORT\n";
        $output .= "===================================\n";
        $output .= "Timestamp: " . $report['timestamp'] . "\n\n";

        $output .= "OPERATION STATISTICS\n";
        $output .= "-------------------\n";
        foreach ($report['operations'] as $op => $stats) {
            $output .= sprintf(
                "%s: min=%.2fms, avg=%.2fms, p99=%.2fms, target=%.2fms [%s]\n",
                $op,
                $stats['min_ms'],
                $stats['avg_ms'],
                $stats['p99_ms'],
                $stats['target_ms'] ?? 0,
                $stats['meets_target'] ? 'PASS' : 'FAIL'
            );
        }

        $output .= "\nCACHE STATISTICS\n";
        $output .= "----------------\n";
        $output .= sprintf(
            "Hit Rate: %.2f%% (hits: %d, misses: %d)\n",
            $report['cache_hit_rate_percent'],
            $report['counters']['cache_hits'],
            $report['counters']['cache_misses']
        );

        $output .= "\nOPERATION COUNTS\n";
        $output .= "----------------\n";
        $output .= sprintf(
            "Total: %d, Success: %d, Failed: %d\n",
            $report['counters']['total_operations'],
            $report['counters']['successful_operations'],
            $report['counters']['failed_operations']
        );

        return $output;
    }

    /**
     * Reset all measurements
     *
     * @return void
     */
    public function reset(): void
    {
        $this->measurements = [];
        $this->counters = [
            'total_operations' => 0,
            'successful_operations' => 0,
            'failed_operations' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0
        ];
    }

    /**
     * Set performance target
     *
     * @param string $operation Operation name
     * @param float $targetMs Target latency in ms
     *
     * @return void
     */
    public function setTarget(string $operation, float $targetMs): void
    {
        $this->targets[$operation . '_p99_ms'] = $targetMs;
    }
}

/**
 * Performance Test Runner
 * 
 * Executes performance tests and collects benchmark data.
 * Simulates representative OAuth2 workloads.
 * 
 * @package   Ksfraser\Performance
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class OAuth2LoadTest
{
    /**
     * @var PerformanceBenchmark Benchmark collector
     */
    private $benchmark;

    /**
     * @var callable User simulation function
     */
    private $userSimulator;

    /**
     * OAuth2LoadTest constructor
     *
     * @param PerformanceBenchmark|null $benchmark Benchmark instance
     */
    public function __construct(?PerformanceBenchmark $benchmark = null)
    {
        $this->benchmark = $benchmark ?? new PerformanceBenchmark();
    }

    /**
     * Simulate authorization flow
     *
     * @param int $numUsers Number of users to simulate
     * @param int $numClients Number of clients
     * @param callable|null $beforeCallback Callback before each operation
     *
     * @return void
     */
    public function simulateAuthorizationFlow(
        int $numUsers = 100,
        int $numClients = 10,
        ?callable $beforeCallback = null
    ): void {
        for ($u = 0; $u < $numUsers; $u++) {
            for ($c = 0; $c < $numClients; $c++) {
                $clientId = "client_$c";
                $userId = "user_$u";
                $scopes = ['read', 'write', 'profile'];

                $startTime = microtime(true);
                
                try {
                    // Simulate authorization endpoint
                    if ($beforeCallback) {
                        call_user_func($beforeCallback, 'authorization', $clientId, $userId);
                    }

                    $latencyMs = (microtime(true) - $startTime) * 1000;
                    $this->benchmark->recordMeasurement(
                        'authorization',
                        $latencyMs,
                        ['client_id' => $clientId, 'user_id' => $userId, 'success' => true]
                    );
                } catch (\Exception $e) {
                    $latencyMs = (microtime(true) - $startTime) * 1000;
                    $this->benchmark->recordMeasurement(
                        'authorization',
                        $latencyMs,
                        ['success' => false, 'error' => $e->getMessage()]
                    );
                }
            }
        }
    }

    /**
     * Simulate token exchange
     *
     * @param int $numOperations Number of token exchanges
     * @param callable|null $tokenExchanger Callback to exchange token
     *
     * @return void
     */
    public function simulateTokenExchange(
        int $numOperations = 100,
        ?callable $tokenExchanger = null
    ): void {
        for ($i = 0; $i < $numOperations; $i++) {
            $startTime = microtime(true);

            try {
                if ($tokenExchanger) {
                    call_user_func($tokenExchanger);
                }

                $latencyMs = (microtime(true) - $startTime) * 1000;
                $this->benchmark->recordMeasurement(
                    'token_exchange',
                    $latencyMs,
                    ['success' => true]
                );
            } catch (\Exception $e) {
                $latencyMs = (microtime(true) - $startTime) * 1000;
                $this->benchmark->recordMeasurement(
                    'token_exchange',
                    $latencyMs,
                    ['success' => false]
                );
            }
        }
    }

    /**
     * Simulate UserInfo endpoint access
     *
     * @param int $numOperations Number of UserInfo requests
     * @param callable|null $userinfoFetcher Callback to fetch userinfo
     *
     * @return void
     */
    public function simulateUserInfoEndpoint(
        int $numOperations = 100,
        ?callable $userinfoFetcher = null
    ): void {
        for ($i = 0; $i < $numOperations; $i++) {
            $startTime = microtime(true);

            try {
                if ($userinfoFetcher) {
                    call_user_func($userinfoFetcher);
                }

                $latencyMs = (microtime(true) - $startTime) * 1000;
                $this->benchmark->recordMeasurement(
                    'userinfo_endpoint',
                    $latencyMs,
                    ['success' => true]
                );
            } catch (\Exception $e) {
                $latencyMs = (microtime(true) - $startTime) * 1000;
                $this->benchmark->recordMeasurement(
                    'userinfo_endpoint',
                    $latencyMs,
                    ['success' => false]
                );
            }
        }
    }

    /**
     * Run concurrent-like load test
     *
     * Simulates concurrent-like behavior through sequential operations.
     *
     * @param int $concurrentSimulation Number of concurrent "users"
     * @param int $operationsPerUser Operations per user
     * @param callable $operationCallback Callback to execute
     *
     * @return void
     */
    public function runConcurrentSimulation(
        int $concurrentSimulation = 100,
        int $operationsPerUser = 10,
        callable $operationCallback
    ): void {
        $totalOperations = $concurrentSimulation * $operationsPerUser;

        for ($i = 0; $i < $totalOperations; $i++) {
            $startTime = microtime(true);

            try {
                call_user_func($operationCallback, $i);

                $latencyMs = (microtime(true) - $startTime) * 1000;
                $this->benchmark->recordMeasurement(
                    'concurrent_operation',
                    $latencyMs,
                    ['operation_id' => $i, 'success' => true]
                );
            } catch (\Exception $e) {
                $latencyMs = (microtime(true) - $startTime) * 1000;
                $this->benchmark->recordMeasurement(
                    'concurrent_operation',
                    $latencyMs,
                    ['success' => false]
                );
            }
        }
    }

    /**
     * Get benchmark instance
     *
     * @return PerformanceBenchmark Benchmark collector
     */
    public function getBenchmark(): PerformanceBenchmark
    {
        return $this->benchmark;
    }

    /**
     * Get report
     *
     * @return array Performance report
     */
    public function getReport(): array
    {
        return $this->benchmark->getReport();
    }

    /**
     * Export report
     *
     * @param string $format Format: json, table
     *
     * @return string Formatted report
     */
    public function exportReport(string $format = 'table'): string
    {
        if ($format === 'json') {
            return $this->benchmark->exportJSON();
        }
        return $this->benchmark->exportTable();
    }
}

/**
 * Latency Distribution Analyzer
 * 
 * Analyzes latency distributions and identifies performance issues.
 * 
 * @package   Ksfraser\Performance
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class LatencyAnalyzer
{
    /**
     * Get latency histogram
     *
     * @param array $latencies Array of latency values
     * @param int $bucketSize Bucket size in ms
     *
     * @return array Histogram data
     */
    public static function getHistogram(array $latencies, int $bucketSize = 10): array
    {
        if (empty($latencies)) {
            return [];
        }

        $histogram = [];
        $maxLatency = max($latencies);
        $numBuckets = (int)ceil($maxLatency / $bucketSize);

        for ($i = 0; $i < $numBuckets; $i++) {
            $bucketStart = $i * $bucketSize;
            $bucketEnd = ($i + 1) * $bucketSize;

            $count = count(array_filter(
                $latencies,
                fn($l) => $l >= $bucketStart && $l < $bucketEnd
            ));

            if ($count > 0) {
                $histogram[$bucketStart . '-' . $bucketEnd] = $count;
            }
        }

        return $histogram;
    }

    /**
     * Identify outliers
     *
     * @param array $latencies Array of latency values
     * @param float $stdDevMultiplier Standard deviation multiplier (default 2)
     *
     * @return array Outlier values
     */
    public static function getOutliers(array $latencies, float $stdDevMultiplier = 2): array
    {
        if (count($latencies) < 2) {
            return [];
        }

        $mean = array_sum($latencies) / count($latencies);
        $variance = array_sum(array_map(
            fn($x) => pow($x - $mean, 2),
            $latencies
        )) / count($latencies);
        $stdDev = sqrt($variance);

        $threshold = $mean + ($stdDev * $stdDevMultiplier);

        return array_filter($latencies, fn($l) => $l > $threshold);
    }

    /**
     * Get latency distribution stats
     *
     * @param array $latencies Array of latency values
     *
     * @return array Distribution stats
     */
    public static function getDistributionStats(array $latencies): array
    {
        if (empty($latencies)) {
            return [];
        }

        sort($latencies);
        $count = count($latencies);
        $mean = array_sum($latencies) / $count;

        $variance = array_sum(array_map(
            fn($x) => pow($x - $mean, 2),
            $latencies
        )) / $count;

        return [
            'count' => $count,
            'mean' => $mean,
            'median' => $latencies[(int)($count / 2)],
            'variance' => $variance,
            'std_dev' => sqrt($variance),
            'skewness' => self::calculateSkewness($latencies, $mean, sqrt($variance)),
            'kurtosis' => self::calculateKurtosis($latencies, $mean, sqrt($variance))
        ];
    }

    /**
     * Calculate skewness
     */
    private static function calculateSkewness(array $data, float $mean, float $stdDev): float
    {
        if ($stdDev === 0 || empty($data)) {
            return 0;
        }

        $n = count($data);
        $sum = array_sum(array_map(fn($x) => pow(($x - $mean) / $stdDev, 3), $data));
        return $sum / $n;
    }

    /**
     * Calculate kurtosis
     */
    private static function calculateKurtosis(array $data, float $mean, float $stdDev): float
    {
        if ($stdDev === 0 || empty($data)) {
            return 0;
        }

        $n = count($data);
        $sum = array_sum(array_map(fn($x) => pow(($x - $mean) / $stdDev, 4), $data));
        return ($sum / $n) - 3;
    }
}
