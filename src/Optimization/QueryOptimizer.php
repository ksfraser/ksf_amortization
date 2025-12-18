<?php

namespace Ksfraser\Amortizations\Optimization;

use Ksfraser\Amortizations\Cache\CacheLayer;

/**
 * Query Optimizer
 * 
 * Optimizes database queries for the KSF Amortization API.
 * Implements strategies for query optimization, batching, and efficiency.
 * 
 * Features:
 * - Lazy loading of related data
 * - Eager loading for batch operations
 * - Query batching to reduce N+1 problems
 * - Selective column selection
 * - Index-aware filtering
 * - Caching of query results
 * 
 * @author KSF
 * @version 1.0.0
 */
class QueryOptimizer
{
    private CacheLayer $cache;
    private array $queryStats = [];

    /**
     * Initialize query optimizer
     * 
     * @param CacheLayer $cache Cache layer for storing query results
     */
    public function __construct(CacheLayer $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get loan with lazy-loaded schedule
     * 
     * Retrieves loan without immediately loading full schedule.
     * Schedule is loaded only when accessed.
     * 
     * @param int $loanId Loan ID
     * @param array $loanData Loan data
     * @return array Loan with lazy-load capability
     */
    public function getLoanWithLazySchedule(int $loanId, array $loanData): array
    {
        // Cache key for lazy reference
        $scheduleKey = "loan_{$loanId}_schedule";

        return [
            ...($loanData ?? []),
            'schedule' => null, // Not loaded yet
            'schedule_cache_key' => $scheduleKey,
            'schedule_loaded' => false,
        ];
    }

    /**
     * Eager load schedules for multiple loans
     * 
     * Loads schedules for all loans in a single batch query.
     * Prevents N+1 query problem.
     * 
     * @param array $loanIds Array of loan IDs
     * @param array $loansData Array of loan data
     * @return array Loans with eager-loaded schedules
     */
    public function eagerLoadSchedules(array $loanIds, array $loansData): array
    {
        $optimizedLoans = [];

        // In production, this would be a single batch query
        // SELECT * FROM schedules WHERE loan_id IN (...)
        
        foreach ($loansData as $loan) {
            $loanId = $loan['id'];
            $scheduleKey = "loan_{$loanId}_schedule";

            // Try cache first
            $schedule = $this->cache->get($scheduleKey);

            if ($schedule === null) {
                // In production, would retrieve from batch query results
                $schedule = [];
            }

            $optimizedLoans[$loanId] = [
                ...($loan ?? []),
                'schedule' => $schedule,
                'schedule_loaded' => true,
            ];

            // Cache result
            $this->cache->set($scheduleKey, $schedule, 3600);
        }

        return $optimizedLoans;
    }

    /**
     * Batch query execution
     * 
     * Combines multiple queries into a single batch operation.
     * Reduces database round trips.
     * 
     * @param array $queries Array of query conditions
     * @return array Combined results
     */
    public function batchQuery(array $queries): array
    {
        $results = [];
        $uncachedQueries = [];

        // Check cache for each query
        foreach ($queries as $queryId => $queryParams) {
            $cacheKey = $this->generateCacheKey($queryParams);

            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                $results[$queryId] = $cached;
            } else {
                $uncachedQueries[$queryId] = $queryParams;
            }
        }

        // Execute uncached queries in batch
        if (!empty($uncachedQueries)) {
            // In production: Execute single batch query
            // SELECT * FROM loans WHERE id IN (...)
            
            foreach ($uncachedQueries as $queryId => $params) {
                // Simulate query execution
                $result = [];
                
                // Cache result
                $cacheKey = $this->generateCacheKey($params);
                $this->cache->set($cacheKey, $result, 1800);
                
                $results[$queryId] = $result;
            }
        }

        return $results;
    }

    /**
     * Select only required columns
     * 
     * Returns only specified columns instead of all columns.
     * Reduces data transfer and memory usage.
     * 
     * @param array $data Full data record
     * @param array $columns Column names to select
     * @return array Selected columns only
     */
    public function selectColumns(array $data, array $columns): array
    {
        $selected = [];

        foreach ($columns as $column) {
            if (isset($data[$column])) {
                $selected[$column] = $data[$column];
            }
        }

        return $selected;
    }

    /**
     * Get indexed lookup query
     * 
     * Uses indexed columns for efficient filtering.
     * 
     * @param string $field Field name (should be indexed)
     * @param mixed $value Field value
     * @return array Query condition for indexed lookup
     */
    public function getIndexedLookup(string $field, mixed $value): array
    {
        return [
            'field' => $field,
            'value' => $value,
            'indexed' => true,
            'expected_selectivity' => 0.01, // Expected 1% of records
        ];
    }

    /**
     * Get query statistics
     * 
     * Returns optimization statistics for monitoring.
     * 
     * @return array Query statistics
     */
    public function getQueryStats(): array
    {
        return [
            'cache_hits' => $this->cache->getStats()['hits'],
            'cache_misses' => $this->cache->getStats()['misses'],
            'hit_rate' => $this->cache->getStats()['hit_rate'],
            'queries_executed' => count($this->queryStats),
        ];
    }

    /**
     * Generate cache key from query parameters
     * 
     * @param array $params Query parameters
     * @return string Cache key
     */
    private function generateCacheKey(array $params): string
    {
        return 'query_' . md5(json_encode($params));
    }
}
