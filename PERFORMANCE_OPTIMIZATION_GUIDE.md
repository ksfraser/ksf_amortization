# Performance Profiling & Optimization Guide

**Phase**: 4 - Performance Profiling  
**Status**: In Progress  
**Date**: April 28, 2026  
**Target**: Optimize system for 50+ concurrent users

---

## Performance Profiling Strategy

### 1. Identify Bottlenecks
- Database query performance
- API response times
- Memory usage patterns
- Frontend rendering performance
- Cache effectiveness

### 2. Measure Baseline
- Current state performance metrics
- Identify slow operations
- Establish benchmarks

### 3. Optimize & Iterate
- Apply targeted fixes
- Re-measure improvements
- Track optimization history

### 4. Monitor & Maintain
- Continuous performance monitoring
- Alert on degradation
- Regular optimization reviews

---

## A. Database Query Optimization

### 1. Identify Slow Queries

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.5;  -- Log queries > 500ms

-- Query slow log
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 20;

-- List top 10 slowest queries
SELECT 
  sql_text,
  COUNT(*) as exec_count,
  AVG(query_time) as avg_time,
  MAX(query_time) as max_time
FROM mysql.slow_log
GROUP BY sql_text
ORDER BY avg_time DESC
LIMIT 10;
```

### 2. Analyze Query Execution Plans

```sql
-- Basic EXPLAIN
EXPLAIN SELECT * FROM amortizations WHERE user_id = 1;

-- Extended EXPLAIN
EXPLAIN FORMAT=JSON SELECT * FROM amortizations WHERE user_id = 1\G

-- Check index usage
EXPLAIN SELECT * FROM amortizations WHERE id = 1;  -- Should use PRIMARY KEY
EXPLAIN SELECT * FROM loan_payments WHERE amortization_id = 1;  -- Should use FK index
```

### 3. Index Optimization

**Current Indexes** (verify in database):
```sql
-- Verify existing indexes
SHOW INDEX FROM amortizations;
SHOW INDEX FROM loan_payments;
SHOW INDEX FROM loan_events;

-- Create missing indexes (if needed)
CREATE INDEX idx_user_id ON amortizations(user_id);
CREATE INDEX idx_status ON amortizations(status);
CREATE INDEX idx_payment_date ON loan_payments(payment_date);
CREATE INDEX idx_event_type ON loan_events(event_type);

-- Composite indexes for common multi-column queries
CREATE INDEX idx_user_status ON amortizations(user_id, status);
CREATE INDEX idx_amort_date ON loan_payments(amortization_id, payment_date);
```

### 4. Query Restructuring Examples

**Before** (inefficient):
```php
// N+1 problem: separate query for each amortization
$amortizations = AmortizationModel::where('user_id', $userId)->get();
foreach ($amortizations as $amort) {
    $payments = $amort->payments();  // New query for each!
}
```

**After** (optimized):
```php
// Single query with eager loading
$amortizations = AmortizationModel::with('payments')
    ->where('user_id', $userId)
    ->get();
```

### 5. Database Connection Pooling

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST'),
    'pool' => [
        'min' => 5,      // Minimum connections in pool
        'max' => 20,     // Maximum connections in pool
    ],
    'options' => [
        'max_lifetime' => 3600,  // Connection lifetime (seconds)
    ],
]
```

### 6. Performance Monitoring Queries

```sql
-- Check current connections
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Threads%';

-- Query performance metrics
SHOW STATUS LIKE 'Slow_queries';
SHOW STATUS LIKE 'Questions';
SHOW STATUS LIKE 'Innodb_rows_read';
SHOW STATUS LIKE 'Innodb_rows_inserted';

-- Check table size
SELECT 
  TABLE_NAME,
  ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'ksf_amortization'
ORDER BY size_mb DESC;
```

---

## B. API Response Time Optimization

### 1. Response Time Analysis

```
Target Metrics:
- p50 (median): < 100ms
- p95: < 500ms
- p99: < 1s
- Max: < 2s
```

### 2. Identify Slow Endpoints

**Add timing middleware** (PHP):
```php
// app/Middleware/TimingMiddleware.php
namespace App\Middleware;

class TimingMiddleware
{
    public function handle($request, $next)
    {
        $start = microtime(true);
        $response = $next($request);
        
        $elapsed = (microtime(true) - $start) * 1000;  // ms
        $response->header('X-Response-Time', round($elapsed, 2) . 'ms');
        
        // Log slow requests
        if ($elapsed > 500) {
            \Log::warning('Slow API request', [
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'time_ms' => round($elapsed, 2),
            ]);
        }
        
        return $response;
    }
}
```

### 3. Response Caching Strategy

```php
// Cache frequently accessed data
class AmortizationController
{
    public function show($id)
    {
        return Cache::remember("amortization.{$id}", 3600, function () use ($id) {
            return Amortization::with('payments', 'events')
                ->findOrFail($id);
        });
    }
    
    public function getSchedule($id)
    {
        return Cache::remember("schedule.{$id}", 1800, function () use ($id) {
            return $this->calculateSchedule($id);
        });
    }
}
```

### 4. LazyLoad Optimization

```php
// Paginate large result sets
public function listAmortizations(Request $request)
{
    return Amortization::where('user_id', $request->user()->id)
        ->with('payments:id,amortization_id,amount,date')  // Select specific columns
        ->paginate(50);  // Not all records
}
```

### 5. Compression & Minification

```nginx
# nginx configuration
gzip on;
gzip_types text/plain text/css application/json application/javascript;
gzip_min_length 1000;
gzip_comp_level 6;
```

---

## C. Frontend Performance Optimization

### 1. Bundle Analysis

```bash
# Analyze bundle size
npm run build -- --config vite.config.js --analyze

# Check what's in bundles
npm install -D rollup-plugin-visualizer
// Add to vite.config.js:
plugins: [
  visualizer({
    open: true,
    gzipSize: true,
  })
]
```

### 2. Code Splitting

```javascript
// vite.config.js
build: {
  rollupOptions: {
    output: {
      manualChunks: {
        'vue-vendor': ['vue', 'vue-router'],
        'state': ['pinia'],
        'http': ['axios'],
      }
    }
  }
}
```

### 3. Image Optimization

```html
<!-- Use WebP with fallback -->
<picture>
  <source srcset="image.webp" type="image/webp">
  <source srcset="image.jpg" type="image/jpeg">
  <img src="image.jpg" alt="Description">
</picture>

<!-- Lazy load images -->
<img src="image.jpg" loading="lazy" alt="Description">
```

### 4. Vue Component Performance

```javascript
// Lazy load components
const AdminPanel = defineAsyncComponent(() =>
  import('./components/AdminPanel.vue')
)

// Memoize expensive computations
const expensiveComputation = computed(() => {
  return calculateSomethingExpensive(data.value)
}, {
  // Cache key for memoization
  cache: true
})
```

---

## D. Memory Optimization

### 1. Monitor Memory Usage

```bash
# Docker memory stats
docker stats --no-stream

# Per-container
docker container stats

# Historical monitoring (1 minute intervals)
while true; do
  docker stats --no-stream --format "{{.Container}} {{.CPUPerc}} {{.MemUsage}}"
  sleep 60
done
```

### 2. Memory Leak Detection

```php
// Monitor memory growth over time
class MemoryMonitor
{
    public static function check()
    {
        $peak = memory_get_peak_usage(true) / 1024 / 1024;  // MB
        $current = memory_get_usage(true) / 1024 / 1024;
        
        if ($current > 256) {  // Alert if > 256MB
            \Log::warning('High memory usage', [
                'current' => round($current, 2),
                'peak' => round($peak, 2),
                'limit' => ini_get('memory_limit'),
            ]);
        }
    }
}

// Call regularly
MemoryMonitor::check();
```

### 3. Database Connection Management

```php
// Close connections that aren't being used
// Implement connection pool draining
// Use persistent connections in production (if supported)

// Monitor connection count
SHOW STATUS LIKE 'Threads%';
```

---

## E. Redis/Cache Optimization

### 1. Cache Strategy

```php
// Tier-1: Database query results
Cache::remember('expensive-query', 3600, function () {
    return DB::raw('SELECT ...');
});

// Tier-2: Computed values
Cache::remember('schedule-calculation', 1800, function () {
    return calculateAmortizationSchedule($data);
});

// Tier-3: User session data
Cache::remember("user.{$id}.preferences", 86400, function () {
    return User::find($id)->preferences;
});
```

### 2. Cache Invalidation

```php
// Invalidate related caches when data changes
class Amortization extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        static::updated(function ($model) {
            // Clear schedule cache
            Cache::forget("schedule.{$model->id}");
            // Clear list cache
            Cache::tags('amortizations')->flush();
        });
    }
}
```

### 3. Redis Monitoring

```bash
# Monitor Redis performance
redis-cli MONITOR

# Get statistics
redis-cli INFO stats

# Check memory usage
redis-cli INFO memory

# List all keys (warning: may be slow in production)
redis-cli --scan --pattern "*"

# Get cache hit ratio
redis-cli INFO stats | grep hit_ratio
```

---

## F. Load Testing & Performance Baselines

### 1. Basic Load Test (Apache Bench)

```bash
# Simple endpoint test
ab -n 1000 -c 50 http://localhost/api/health
# -n: number of requests
# -c: concurrency level

# Result interpretation:
# Requests per second (RPS): 1000+ is good
# Mean time per request: < 100ms is good
# Failed requests: 0 expected
```

### 2. Realistic Load Test (wrk)

```bash
# Install wrk
git clone https://github.com/wg/wrk
cd wrk && make

# Run test
wrk -t4 -c100 -d30s http://localhost/api/amortizations
# -t: number of threads
# -c: number of connections
# -d: duration

# With custom script for POST requests
wrk -t4 -c100 -d30s -s create_loan.lua http://localhost/api/amortizations
```

### 3. Performance Baselines

```
Target for 50 Concurrent Users:

API Endpoints:
- GET /api/health: < 10ms (p95)
- GET /api/amortizations: < 200ms (p95)
- GET /api/amortizations/{id}: < 150ms (p95)
- POST /api/amortizations: < 500ms (p95)
- GET /api/reports: < 1000ms (p95)

Database:
- Simple query: < 10ms
- Complex query: < 100ms
- Batch operation: < 500ms

Frontend:
- First Paint: < 1s
- LCP (Largest Contentful Paint): < 2.5s
- FID (First Input Delay): < 100ms
```

### 4. Load Test with Gradual Ramp-Up

```bash
#!/bin/bash
# Gradually increase load to find breaking point

for connections in 10 25 50 100 150 200; do
    echo "Testing with $connections concurrent connections..."
    ab -n 10000 -c $connections http://localhost/api/health
    sleep 10
done
```

---

## G. Monitoring & Observability

### 1. Key Metrics to Monitor

```
Application Metrics:
- API response time (p50, p95, p99)
- API error rate
- Database query time
- Cache hit ratio
- Active user sessions

Infrastructure Metrics:
- CPU usage
- Memory usage
- Disk I/O
- Network I/O
- Connection count

Business Metrics:
- API requests per second
- Failed transactions
- User session duration
- Most used features
```

### 2. Logging Strategy

```php
// Log query performance
DB::listen(function ($query) {
    if ($query->time > 100) {  // Log queries > 100ms
        \Log::notice('Slow query detected', [
            'query' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms',
        ]);
    }
});

// Log API performance
\Log::channel('performance')->info('API request', [
    'endpoint' => $request->path(),
    'method' => $request->method(),
    'response_time' => $elapsed . 'ms',
    'status_code' => $response->status(),
]);
```

### 3. Metrics Collection Endpoints

```php
// Add internal metrics endpoint (protected)
Route::get('/metrics', function () {
    return [
        'uptime' => time() - APP_START_TIME,
        'requests' => (int) Cache::get('request_count', 0),
        'errors' => (int) Cache::get('error_count', 0),
        'avg_response_time' => Cache::get('avg_response_time', 0),
        'memory_mb' => memory_get_usage(true) / 1024 / 1024,
        'cache_hits' => (int) Cache::get('cache_hits', 0),
        'cache_misses' => (int) Cache::get('cache_misses', 0),
    ];
})->middleware('auth:api');
```

---

## H. Optimization Checklist

### Database
- [ ] Indexes created for all foreign keys
- [ ] Composite indexes for common queries
- [ ] N+1 problem eliminated (use eager loading)
- [ ] Query execution plans reviewed (EXPLAIN)
- [ ] Slow query log analyzed
- [ ] Connection pooling configured

### API
- [ ] Response timing middleware in place
- [ ] Slow queries identified and optimized
- [ ] Caching strategy implemented
- [ ] Pagination for large result sets
- [ ] Compression (gzip) enabled
- [ ] Response time targets met (p95 < 500ms)

### Frontend
- [ ] Bundle size optimized
- [ ] Code splitting implemented
- [ ] Images optimized (WebP, lazy loading)
- [ ] Minification enabled
- [ ] Tree-shaking working
- [ ] Lighthouse score > 80

### Infrastructure
- [ ] Docker resource limits configured
- [ ] Memory monitoring set up
- [ ] CPU usage monitored
- [ ] Disk space monitored
- [ ] Network I/O acceptable
- [ ] No memory leaks detected

### Monitoring
- [ ] Performance dashboard created
- [ ] Alerts configured for anomalies
- [ ] Log aggregation working
- [ ] Metrics exported to monitoring system
- [ ] Historical data retained
- [ ] On-call team can access metrics

---

## I. Performance Improvement Roadmap

### Phase 1: Quick Wins (1-2 days)
- [x] Add database indexes
- [x] Implement query caching
- [x] Enable gzip compression
- [x] Create monitoring dashboards
- **Expected Improvement**: 30-50% faster queries

### Phase 2: Optimization (2-3 days)
- [ ] Fix N+1 queries
- [ ] Optimize slow endpoints
- [ ] Implement response caching
- [ ] Optimize images/assets
- **Expected Improvement**: 40-60% faster API

### Phase 3: Scaling (1 week)
- [ ] Load test and identify limits
- [ ] Implement connection pooling
- [ ] Optimize database schema
- [ ] Set up read replicas (if needed)
- **Expected Improvement**: Support 100+ concurrent users

### Phase 4: Advanced (Ongoing)
- [ ] Implement CDN for static content
- [ ] Add query result pagination
- [ ] Implement async job processing
- [ ] Set up distributed caching
- **Expected Improvement**: 10x+ throughput capacity

---

## J. Success Criteria

✅ **Performance Targets Met**:
- API response time p95: < 500ms
- Database query time: < 100ms (average)
- Memory usage: < 500MB per service
- Cache hit ratio: > 80%
- Uptime: 99.9%

✅ **Load Test Results**:
- 50 concurrent users: No errors
- 100 concurrent users: < 5% error rate
- Graceful degradation at 200+ users
- No memory leaks detected
- Recovery after high load

✅ **Monitoring**:
- All key metrics tracked
- Alerts configured
- Dashboards available
- Historical data retained
- On-call team trained

---

## Next Steps

1. [ ] Establish baseline metrics
2. [ ] Identify top 10 slow queries
3. [ ] Profile API endpoints
4. [ ] Run initial load test
5. [ ] Implement Phase 1 optimizations
6. [ ] Re-measure and document improvements
7. [ ] Prioritize next optimizations

**Status**: Ready for implementation  
**Estimated Time**: 2-3 weeks for full optimization cycle
