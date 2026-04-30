# Performance Monitoring & Alerting Setup

**Component**: Monitoring and Alerting Infrastructure  
**Purpose**: Continuous performance tracking and anomaly detection  
**Status**: Configuration Ready

---

## 1. Metrics Collection

### Application Metrics (PHP)

**Add to your application's middleware/service:**

```php
// app/Services/MetricsCollector.php
namespace App\Services;

class MetricsCollector
{
    private static $metrics = [];
    
    /**
     * Record API response time
     */
    public static function recordAPICall(string $endpoint, int $timeMs, int $statusCode)
    {
        self::$metrics['api_calls'][] = [
            'endpoint' => $endpoint,
            'time_ms' => $timeMs,
            'status' => $statusCode,
            'timestamp' => time(),
        ];
    }
    
    /**
     * Record database query
     */
    public static function recordQuery(string $sql, int $timeMs)
    {
        self::$metrics['database_queries'][] = [
            'query' => substr($sql, 0, 100),  // First 100 chars
            'time_ms' => $timeMs,
            'timestamp' => time(),
        ];
    }
    
    /**
     * Record cache hit/miss
     */
    public static function recordCacheAccess(string $key, bool $hit)
    {
        self::$metrics['cache_accesses'][] = [
            'key' => $key,
            'hit' => $hit,
            'timestamp' => time(),
        ];
    }
    
    /**
     * Get current metrics snapshot
     */
    public static function getSnapshot(): array
    {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'metrics' => self::$metrics,
        ];
    }
}
```

### Middleware Integration

```php
// app/Http/Middleware/PerformanceTracking.php
namespace App\Http\Middleware;

class PerformanceTracking
{
    public function handle($request, $next)
    {
        $start = microtime(true);
        $response = $next($request);
        
        $elapsed = (microtime(true) - $start) * 1000;
        $endpoint = $request->path();
        $statusCode = $response->status();
        
        // Record metrics
        \App\Services\MetricsCollector::recordAPICall(
            $endpoint,
            (int) $elapsed,
            $statusCode
        );
        
        // Add to response headers
        $response->header('X-Response-Time', round($elapsed, 2) . 'ms');
        $response->header('X-Request-ID', $request->id());
        
        // Alert on slow requests
        if ($elapsed > 1000) {  // > 1 second
            \Log::warning('Slow API request detected', [
                'endpoint' => $endpoint,
                'time_ms' => round($elapsed, 2),
                'status' => $statusCode,
            ]);
        }
        
        return $response;
    }
}
```

---

## 2. Key Performance Indicators (KPIs)

### Tier 1: Critical Metrics (Alert if degraded)

```
API Response Time:
├─ p50 (median): Target 100ms, Alert > 200ms
├─ p95: Target 500ms, Alert > 1000ms
├─ p99: Target 1s, Alert > 2s
└─ Max: Alert > 5s

Error Rate:
├─ 5xx errors: Alert > 1%
├─ 4xx errors: Track (monitor for patterns)
└─ Failed transactions: Alert > 0.1%

Database:
├─ Query time p95: Alert > 500ms
├─ Slow query count: Alert > 10/min
└─ Connection pool usage: Alert > 80%

Cache:
├─ Hit ratio: Alert < 70%
└─ Eviction rate: Alert > 5%/min
```

### Tier 2: Operational Metrics (Monitor & review)

```
Infrastructure:
├─ CPU usage: Baseline establishment
├─ Memory usage: Baseline establishment
├─ Disk I/O: Monitor trends
└─ Network I/O: Monitor trends

Application:
├─ Active connections: Baseline establishment
├─ Queued jobs: Monitor for buildup
├─ Session count: Baseline establishment
└─ Cache size: Monitor growth
```

---

## 3. Alerting Thresholds

### Setup Alert Channels

```
Severity Levels:
├─ INFO: Informational (no alert)
├─ WARNING: Minor issue (log only)
├─ CRITICAL: Major issue (alert team)
└─ EMERGENCY: System down (page on-call)
```

### Alert Rules

```javascript
// Example monitoring rule format

{
  "name": "High API Response Time",
  "severity": "CRITICAL",
  "condition": "api_response_time_p95 > 1000ms",
  "window": "5 minutes",
  "threshold": 3,  // Alert if condition true 3+ times
  "notify": ["slack:alerts", "email:ops-team", "pagerduty"],
}

{
  "name": "High Error Rate",
  "severity": "CRITICAL",
  "condition": "error_rate > 1%",
  "window": "1 minute",
  "threshold": 2,
  "notify": ["slack:alerts", "pagerduty"],
}

{
  "name": "Database Connection Pool Exhausted",
  "severity": "EMERGENCY",
  "condition": "db_pool_usage > 95%",
  "window": "1 minute",
  "threshold": 1,
  "notify": ["slack:alerts", "sms:oncall", "pagerduty"],
}

{
  "name": "Service Memory Leak",
  "severity": "CRITICAL",
  "condition": "memory_growth_rate > 10MB/hour",
  "window": "15 minutes",
  "threshold": 2,
  "notify": ["slack:alerts", "email:ops-team"],
}
```

---

## 4. Dashboards

### Real-Time Dashboard

**Should Display**:
```
┌─ KSF Amortization - Performance Dashboard ────────═
│                                                    │
│ Now: 2:34 PM | Uptime: 45 days 23h 12m            │
│                                                    │
│ ┌─ API Performance ────────────────────────────┐  │
│ │ Requests/sec: 1,245 ↑                       │  │
│ │ Avg Response: 125ms ✓                       │  │
│ │ p95 Response: 420ms ✓                       │  │
│ │ Error Rate: 0.02% ✓                        │  │
│ └────────────────────────────────────────────┘  │
│                                                    │
│ ┌─ Database ────────────────────────────────────┐ │
│ │ Queries/sec: 342                             │ │
│ │ Avg Query Time: 45ms ✓                       │ │
│ │ Slow Queries: 2 (< 5min) ✓                  │ │
│ │ Connections: 8/20 ✓                         │ │
│ └────────────────────────────────────────────┘  │
│                                                    │
│ ┌─ Infrastructure ───────────────────────────────┐ │
│ │ CPU Usage: 34% ✓                             │ │
│ │ Memory Usage: 245MB / 500MB (49%) ✓         │ │
│ │ Disk I/O: 12 MB/s                           │ │
│ │ Network: 2.3 Mbps ↓                         │ │
│ └────────────────────────────────────────────┘  │
│                                                    │
│ ┌─ Cache ────────────────────────────────────────┐ │
│ │ Hit Ratio: 87% ✓                            │ │
│ │ Cache Size: 156MB / 512MB (30%)              │ │
│ │ Evictions: 0/5min ✓                         │ │
│ └────────────────────────────────────────────┘  │
│                                                    │
└────────────────────────────────────────────────┘
```

### Historical Trends Dashboard

**Should Display**:
```
- 24-hour API response time trend
- 7-day error rate trend
- 30-day database query time trend
- Memory usage over time
- Cache hit ratio trend
- Traffic volume trend
```

---

## 5. Performance Report Template

### Daily Performance Report

```
KSF Amortization - Daily Performance Report
Date: 2026-04-28, Day: Sunday

EXECUTIVE SUMMARY
─────────────────
Status: ✅ HEALTHY
Availability: 100%
Performance: ✅ GOOD (all metrics within SLA)

KEY METRICS
─────────────────
API Response Times:
  • p50 (median): 95ms (target: 100ms) ✓
  • p95: 420ms (target: 500ms) ✓  
  • p99: 890ms (target: 1000ms) ✓
  • Max: 2.3s ✓

Error Rates:
  • Overall error rate: 0.04%
  • 5xx errors: 0.02%
  • 4xx errors: 0.02%

Database Performance:
  • Avg query time: 45ms
  • p95 query time: 180ms
  • Slow queries (> 500ms): 2 total
  • Connection pool usage: 8/20 (40%)

Cache Performance:
  • Cache hit ratio: 86%
  • Cache evictions: 12/day
  • Cache size: 156MB / 512MB (30%)

RESOURCE USAGE
─────────────────
CPU:      ████████░░ 45% (peak: 67%)
Memory:   ███████░░░ 245MB / 500MB (49%)
Disk:     ██░░░░░░░░ 8% full
Network:  2.1 Mbps (avg), 5.4 Mbps (peak)

TOP ENDPOINTS
─────────────────
1. GET    /api/amortizations/123     - 245ms avg, 12.5k req/day
2. POST   /api/amortizations        - 450ms avg, 8.2k req/day
3. GET    /api/reports/1            - 850ms avg, 2.1k req/day
4. GET    /api/health               - 2ms avg, 43.2k req/day
5. GET    /                          - 120ms avg, 15.3k req/day

SLOW QUERIES (> 500ms)
─────────────────────────────────
1. SELECT a.*, COUNT(p.id) FROM amortizations... - 567ms
2. SELECT * FROM reports WHERE... - 823ms

INCIDENTS/ALERTS
─────────────────────────────────
None

RECOMMENDATIONS
─────────────────────────────────
- Monitor: Query #1 is near threshold, consider optimization
- Cache: Consider caching /reports endpoint (850ms avg)
- Monitor: Memory usage trending up 5% since yesterday

PREVIOUS DAY COMPARISON
─────────────────────────────────
Metric                 Today    Yesterday  Change
API Response p95:      420ms    410ms      +2.4%
Error Rate:            0.04%    0.03%      +33%
Memory Usage:          245MB    233MB      +5%
Cache Hit Ratio:       86%      87%        -1%
Database p95:          180ms    170ms      +5.9%

Conclusion: Performance stable, all metrics within normal ranges.
```

---

## 6. Performance Testing Checklist

Before Each Major Release:

```
□ Run baseline performance test
  - Document baseline metrics
  - Compare to previous version
  - Alert if degradation > 5%

□ Load test at target concurrency
  - Test at 50 concurrent users
  - Test at 100 concurrent users
  - Document max capacity

□ Memory leak test
  - Run sustained load for 1 hour
  - Monitor memory growth
  - Alert if growth > 10%

□ Database optimization
  - Analyze slow query log
  - Add missing indexes if needed
  - Verify query plans

□ Cache effectiveness
  - Measure cache hit ratio
  - Verify cache invalidation working
  - Monitor cache memory usage

□ Frontend performance
  - Run Lighthouse audit
  - Check bundle size
  - Verify code splitting
  - Test mobile performance
```

---

## 7. Performance Troubleshooting Guide

### Symptom: Slow API Response Times

**Investigation**:
1. Check database connection pool usage
2. Analyze slow query log
3. Check cache hit ratio
4. Profile specific endpoint

**Solutions**:
- Add database indexes
- Implement query caching
- Fix N+1 queries
- Optimize query logic
- Increase connection pool size

### Symptom: High Memory Usage

**Investigation**:
1. Identify which service is using memory
2. Check for memory leaks (monitor over time)
3. Review object allocation patterns
4. Check cache size

**Solutions**:
- Clear caches
- Fix memory leak (usually circular references)
- Reduce cache TTL
- Implement memory limits in containers
- Switch to lighter alternative/library

### Symptom: High Error Rate

**Investigation**:
1. Review error logs (last 5 minutes)
2. Check which endpoints are failing
3. Check database connectivity
4. Check disk space

**Solutions**:
- Check database connection status
- Review application logs for reasons
- Verify dependencies available
- Check infrastructure (disk, memory)

### Symptom: Database Connection Pool Exhausted

**Investigation**:
1. Check active connections: `SHOW PROCESSLIST`
2. Identify slow queries holding connections
3. Check for connection leaks

**Solutions**:
- Increase pool size (temporary)
- Kill long-running queries
- Fix connection leak in code
- Optimize slow queries

---

## 8. Setup Instructions

### For Production:

1. **Deploy metrics collector** in application
2. **Configure monitoring tool** (Prometheus/DataDog/New Relic)
3. **Set up dashboards** with real-time metrics
4. **Configure alerts** on critical thresholds
5. **Set up logging** for slow queries/requests
6. **Create runbooks** for common issues
7. **Train team** on dashboard usage

### For Local Development:

1. **Use simple logging** to understand performance
2. **Profile slow operations** with built-in tools
3. **Compare against baselines** from production
4. **Test on production-like data** volumes

---

## 9. Success Criteria

✅ **Monitoring Infrastructure**:
- Metrics collected in real-time
- Dashboards available and current
- Alerts working and not noisy
- Historical data retained
- Team trained on tools

✅ **Performance Baseline**:
- All key metrics documented
- Baseline measurements recorded
- Targets defined and reasonable
- Team agrees on SLAs

✅ **Alerting Active**:
- Critical issues trigger alerts
- Team responds to alerts
- False positives minimized
- Runbooks available

---

**Next Steps**: Review this guidance and implement monitoring in production environment.
