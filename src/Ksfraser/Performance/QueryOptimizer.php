<?php

namespace Ksfraser\Performance;

use Ksfraser\Caching\CacheInterface;

/**
 * QueryOptimizer - Database query optimization and tracking
 * 
 * Features:
 * - Query profiling and metrics
 * - n+1 detection
 * - Batch loading utilities
 * - Query result caching
 * - Performance reporting
 * 
 * @package    Ksfraser\Performance
 * @since      20251221
 */
class QueryOptimizer
{
    /**
     * @var array Query metrics
     */
    private array $metrics = [
        'total_queries' => 0,
        'total_time' => 0,
        'cached_queries' => 0,
        'queries' => []
    ];

    /**
     * @var CacheInterface Cache for query results
     */
    private ?CacheInterface $cache = null;

    /**
     * @var bool Enable profiling
     */
    private bool $profiling = false;

    /**
     * Constructor
     * 
     * @param CacheInterface|null $cache Optional cache backend
     */
    public function __construct(?CacheInterface $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * Enable query profiling
     * 
     * @return self
     */
    public function enableProfiling(): self
    {
        $this->profiling = true;
        return $this;
    }

    /**
     * Disable query profiling
     * 
     * @return self
     */
    public function disableProfiling(): self
    {
        $this->profiling = false;
        return $this;
    }

    /**
     * Record a query with profiling data
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @param float $duration Execution time in seconds
     * @param int $rowCount Rows affected/returned
     * @return void
     */
    public function recordQuery(string $sql, array $params = [], float $duration = 0, int $rowCount = 0): void
    {
        if (!$this->profiling) {
            return;
        }

        $this->metrics['total_queries']++;
        $this->metrics['total_time'] += $duration;

        $this->metrics['queries'][] = [
            'sql' => $sql,
            'params' => $params,
            'duration' => $duration,
            'rows' => $rowCount,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Get batch loading optimizer for IDs
     * 
     * Optimizes loading related records using batch queries
     * Instead of: SELECT * FROM users WHERE id = 1; // repeated N times
     * Use: SELECT * FROM users WHERE id IN (1,2,3,...,N);
     * 
     * @param array $ids IDs to load
     * @param int $batchSize How many IDs per query
     * @return array Batches of IDs
     */
    public function batchIds(array $ids, int $batchSize = 100): array
    {
        $batches = [];
        foreach (array_chunk($ids, $batchSize) as $batch) {
            $batches[] = $batch;
        }
        return $batches;
    }

    /**
     * Cache a query result
     * 
     * @param string $cacheKey Cache key
     * @param callable $queryCallback Function that returns query result
     * @param int $ttl Cache TTL in seconds
     * @param array $tags Cache tags for invalidation
     * @return mixed Query result
     */
    public function cacheQuery(
        string $cacheKey,
        callable $queryCallback,
        int $ttl = 3600,
        array $tags = []
    ): mixed {
        if (!$this->cache) {
            return $queryCallback();
        }

        // Try to get from cache
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->metrics['cached_queries']++;
            return $cached;
        }

        // Not cached, execute query
        $result = $queryCallback();

        // Cache the result
        $this->cache->set($cacheKey, $result, $ttl);

        return $result;
    }

    /**
     * Detect potential n+1 query patterns
     * 
     * Returns list of potentially problematic queries
     * (same query executed multiple times)
     * 
     * @return array Potentially problematic queries
     */
    public function detectN1Patterns(): array
    {
        $queryCounts = [];
        $results = [];

        // Count identical queries
        foreach ($this->metrics['queries'] as $query) {
            $key = $query['sql'];
            if (!isset($queryCounts[$key])) {
                $queryCounts[$key] = 0;
            }
            $queryCounts[$key]++;
        }

        // Flag queries executed 3+ times (potential n+1)
        foreach ($queryCounts as $sql => $count) {
            if ($count >= 3) {
                $results[] = [
                    'sql' => $sql,
                    'executions' => $count,
                    'issue' => 'Potential n+1 pattern detected'
                ];
            }
        }

        return $results;
    }

    /**
     * Get query metrics and profiling data
     * 
     * @return array Profiling metrics
     */
    public function getMetrics(): array
    {
        $avgTime = $this->metrics['total_queries'] > 0
            ? $this->metrics['total_time'] / $this->metrics['total_queries']
            : 0;

        return [
            'total_queries' => $this->metrics['total_queries'],
            'total_time' => round($this->metrics['total_time'], 4),
            'avg_query_time' => round($avgTime, 4),
            'cached_queries' => $this->metrics['cached_queries'],
            'cache_hit_rate' => $this->metrics['total_queries'] > 0
                ? round(($this->metrics['cached_queries'] / $this->metrics['total_queries']) * 100, 2)
                : 0,
            'n1_issues' => count($this->detectN1Patterns()),
            'queries_list' => array_slice($this->metrics['queries'], 0, 10) // Last 10
        ];
    }

    /**
     * Reset all metrics
     * 
     * @return void
     */
    public function resetMetrics(): void
    {
        $this->metrics = [
            'total_queries' => 0,
            'total_time' => 0,
            'cached_queries' => 0,
            'queries' => []
        ];
    }

    /**
     * Generate optimization recommendations
     * 
     * @return array List of recommendations
     */
    public function getRecommendations(): array
    {
        $recommendations = [];
        $metrics = $this->getMetrics();

        // Check for high number of queries
        if ($metrics['total_queries'] > 50) {
            $recommendations[] = [
                'severity' => 'high',
                'message' => "High query count ({$metrics['total_queries']}). Consider batch loading or caching."
            ];
        }

        // Check for n+1 patterns
        $n1Issues = $this->detectN1Patterns();
        if (!empty($n1Issues)) {
            $recommendations[] = [
                'severity' => 'high',
                'message' => "Detected " . count($n1Issues) . " potential n+1 patterns. Use batch loading."
            ];
        }

        // Check cache hit rate
        if ($metrics['cache_hit_rate'] < 30 && $this->cache) {
            $recommendations[] = [
                'severity' => 'medium',
                'message' => "Low cache hit rate ({$metrics['cache_hit_rate']}%). Increase cache TTL or coverage."
            ];
        }

        // Check average query time
        if ($metrics['avg_query_time'] > 0.5) {
            $recommendations[] = [
                'severity' => 'medium',
                'message' => "High average query time ({$metrics['avg_query_time']}s). Add database indexes."
            ];
        }

        return $recommendations;
    }
}
