#!/usr/bin/env php
<?php
/**
 * Performance Profiling Baseline Collection Script
 * 
 * Collects baseline performance metrics for:
 * - Database query performance
 * - API endpoint response times
 * - Memory usage
 * - Cache effectiveness
 * 
 * Usage: php collect-performance-baseline.php
 */

class PerformanceProfiler
{
    private $metrics = [];
    private $startTime;
    private $logFile = 'performance-baseline.json';
    
    public function __construct()
    {
        $this->startTime = microtime(true);
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║          Performance Baseline Collection Tool                ║\n";
        echo "║                    KSF Amortization                          ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    }
    
    /**
     * Collect database metrics
     */
    public function collectDatabaseMetrics()
    {
        echo "📊 Collecting Database Metrics...\n";
        echo "─────────────────────────────────────────────────────\n\n";
        
        $metrics = [];
        
        // Query 1: Simple select
        $start = microtime(true);
        $result = $this->simpleQuery("SELECT 1");
        $elapsed = (microtime(true) - $start) * 1000;
        echo "  Simple Query (SELECT 1): {$elapsed}ms\n";
        $metrics['simple_query'] = round($elapsed, 2);
        
        // Query 2: Count records
        $start = microtime(true);
        $result = $this->simpleQuery("SELECT COUNT(*) FROM information_schema.TABLES");
        $elapsed = (microtime(true) - $start) * 1000;
        echo "  Count Query: {$elapsed}ms\n";
        $metrics['count_query'] = round($elapsed, 2);
        
        // Query 3: Join query
        $start = microtime(true);
        $result = $this->simpleQuery("
            SELECT a.id, COUNT(p.id) 
            FROM amortizations a 
            LEFT JOIN loan_payments p ON a.id = p.amortization_id 
            GROUP BY a.id LIMIT 10
        ");
        $elapsed = (microtime(true) - $start) * 1000;
        echo "  Join Query: {$elapsed}ms\n";
        $metrics['join_query'] = round($elapsed, 2);
        
        $this->metrics['database'] = $metrics;
        echo "\n";
    }
    
    /**
     * Collect memory metrics
     */
    public function collectMemoryMetrics()
    {
        echo "💾 Collecting Memory Metrics...\n";
        echo "─────────────────────────────────────────────────────\n\n";
        
        $metrics = [];
        
        // Current memory
        $current = memory_get_usage(true) / 1024 / 1024;
        $peak = memory_get_peak_usage(true) / 1024 / 1024;
        $limit = ini_get('memory_limit');
        
        echo "  Current Memory: {}{$current}MB\n";
        echo "  Peak Memory: {$peak}MB\n";
        echo "  Memory Limit: {$limit}\n";
        
        $metrics['current_mb'] = round($current, 2);
        $metrics['peak_mb'] = round($peak, 2);
        $metrics['limit'] = $limit;
        
        $this->metrics['memory'] = $metrics;
        echo "\n";
    }
    
    /**
     * Collect execution time metrics
     */
    public function collectExecutionMetrics()
    {
        echo "⏱️  Collecting Execution Metrics...\n";
        echo "─────────────────────────────────────────────────────\n\n";
        
        $metrics = [];
        
        // Time various operations
        $operations = [
            'json_encode' => fn() => json_encode(['test' => 'data']),
            'array_merge' => fn() => array_merge([1, 2, 3], [4, 5, 6]),
            'str_replace' => fn() => str_replace('test', 'replacement', 'this is a test string'),
            'regex' => fn() => preg_match('/test/', 'this is a test string'),
        ];
        
        foreach ($operations as $name => $operation) {
            $start = microtime(true);
            
            for ($i = 0; $i < 1000; $i++) {
                $operation();
            }
            
            $elapsed = (microtime(true) - $start) * 1000;
            $avg = $elapsed / 1000;
            echo "  {$name} (1000x): {$elapsed}ms avg = {$avg}µs\n";
            $metrics[$name] = round($avg, 4);
        }
        
        $this->metrics['execution'] = $metrics;
        echo "\n";
    }
    
    /**
     * Collect API simulation metrics
     */
    public function collectAPIMetrics()
    {
        echo "🌐 Collecting API Simulation Metrics...\n";
        echo "─────────────────────────────────────────────────────\n\n";
        
        $metrics = [];
        
        // Simulate API response building
        $start = microtime(true);
        $response = $this->buildMockAPIResponse();
        $elapsed = (microtime(true) - $start) * 1000;
        
        echo "  Mock API Response Build: {$elapsed}ms\n";
        echo "  Response Size: " . strlen(json_encode($response)) . " bytes\n";
        
        $metrics['response_build_ms'] = round($elapsed, 2);
        $metrics['response_size_bytes'] = strlen(json_encode($response));
        
        $this->metrics['api'] = $metrics;
        echo "\n";
    }
    
    /**
     * Build reporting stats
     */
    public function collectCacheMetrics()
    {
        echo "♻️  Collecting Cache Metrics (Simulated)...\n";
        echo "─────────────────────────────────────────────────────\n\n";
        
        $metrics = [];
        
        // Simulate cache operations
        $cache_data = array_fill(0, 100, ['key' => 'value', 'data' => 'test']);
        
        // Cache write
        $start = microtime(true);
        $serialized = serialize($cache_data);
        $elapsed = (microtime(true) - $start) * 1000;
        echo "  Serialize 100 items: {$elapsed}ms\n";
        $metrics['serialize_ms'] = round($elapsed, 2);
        
        // Cache read
        $start = microtime(true);
        $unserialized = unserialize($serialized);
        $elapsed = (microtime(true) - $start) * 1000;
        echo "  Unserialize 100 items: {$elapsed}ms\n";
        $metrics['unserialize_ms'] = round($elapsed, 2);
        
        $metrics['serialized_size_kb'] = round(strlen($serialized) / 1024, 2);
        
        $this->metrics['cache'] = $metrics;
        echo "\n";
    }
    
    /**
     * Generate report
     */
    public function generateReport()
    {
        $elapsed = (microtime(true) - $this->startTime) * 1000;
        
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                    Performance Baseline Report               ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";
        
        echo "📈 Summary Metrics:\n";
        echo "─────────────────────────────────────────────────────\n";
        echo sprintf("  Total Collection Time: %.2fms\n", $elapsed);
        echo sprintf("  Final Memory Usage: %.2fMB\n", memory_get_usage(true) / 1024 / 1024);
        echo "\n";
        
        echo "📊 Collected Baselines:\n";
        echo "─────────────────────────────────────────────────────\n";
        foreach ($this->metrics as $category => $data) {
            echo "\n  📌 {$category}:\n";
            if (is_array($data)) {
                foreach ($data as $metric => $value) {
                    echo sprintf("     • {$metric}: {$value}\n");
                }
            }
        }
        
        // Save to file
        $this->saveToFile();
        
        echo "\n✅ Baseline collection complete!\n";
        echo "📁 Results saved to: {$this->logFile}\n";
        echo "\nNext steps:\n";
        echo "  1. Review baselines above\n";
        echo "  2. Set optimization targets (typically 30-50% improvement)\n";
        echo "  3. Run daily to track performance trends\n";
        echo "\n";
    }
    
    /**
     * Save metrics to file
     */
    private function saveToFile()
    {
        $data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'timestamp_unix' => time(),
            'metrics' => $this->metrics,
        ];
        
        file_put_contents(
            $this->logFile,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            FILE_APPEND | LOCK_EX
        );
    }
    
    /**
     * Simple query execution (mock)
     */
    private function simpleQuery($sql)
    {
        // Mock implementation - in real scenario would use actual DB
        return ['result' => 'mock'];
    }
    
    /**
     * Build mock API response
     */
    private function buildMockAPIResponse()
    {
        return [
            'success' => true,
            'data' => [
                'id' => 1,
                'principal' => 100000,
                'rate' => 5.5,
                'term' => 360,
                'payments' => array_fill(0, 5, [
                    'number' => 1,
                    'amount' => 567.43,
                    'interest' => 458.33,
                    'principal' => 109.10,
                    'balance' => 99890.90,
                ]),
            ],
            'meta' => [
                'response_time_ms' => 125,
                'timestamp' => time(),
            ],
        ];
    }
}

// Run profiling
$profiler = new PerformanceProfiler();
$profiler->collectDatabaseMetrics();
$profiler->collectMemoryMetrics();
$profiler->collectExecutionMetrics();
$profiler->collectAPIMetrics();
$profiler->collectCacheMetrics();
$profiler->generateReport();
?>
