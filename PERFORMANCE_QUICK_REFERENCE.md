# Performance Quick Reference Guide

**Purpose**: Fast lookup for common performance issues and solutions  
**Format**: Copy-paste ready SQL, PHP, and shell commands  
**Status**: Ready to use

---

## Database Performance Fixes

### Symptom: Slow Queries in Log

#### Quick Diagnosis

```bash
# View slow queries
tail -f /var/log/mysql/slow-query.log

# Or via MySQL
SHOW VARIABLES LIKE 'long_query_time';  -- Usually 2 seconds

# Find current slow queries
SELECT * FROM INFORMATION_SCHEMA.PROCESSLIST 
WHERE TIME > 2 AND COMMAND != 'Sleep';

# Get top slow queries
SELECT query_time, lock_time, rows_examined, rows_sent, sql_text 
FROM mysql.general_log 
WHERE command_type = 'Query'
ORDER BY query_time DESC 
LIMIT 10;
```

#### Fix: Add Missing Index

```sql
-- Identify missing indexes
EXPLAIN SELECT * FROM amortizations WHERE company_id = 10;

-- Check if index exists
SELECT * FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_NAME = 'amortizations' AND COLUMN_NAME = 'company_id';

-- Add index if missing
ALTER TABLE amortizations ADD INDEX idx_company_id (company_id);

-- Rebuild indexes after large delete/update
OPTIMIZE TABLE amortizations;
```

#### Fix: Optimize Complex Query

```sql
-- BEFORE: Slow query with subquery
SELECT * FROM amortizations a
WHERE a.id IN (SELECT amortization_id FROM payments)
  AND a.status = 'active';

-- AFTER: Use JOIN instead
SELECT DISTINCT a.* FROM amortizations a
INNER JOIN payments p ON a.id = p.amortization_id
WHERE a.status = 'active';

-- AFTER: Use WHERE EXISTS (often faster)
SELECT a.* FROM amortizations a
WHERE a.status = 'active'
  AND EXISTS (SELECT 1 FROM payments p WHERE p.amortization_id = a.id);
```

#### Fix: Prevent N+1 Queries

```php
// BEFORE: N+1 queries
$amortizations = Amortization::all();  // 1 query
foreach ($amortizations as $a) {
    echo $a->payments->count();  // N queries (1 per amortization)
}

// AFTER: Eager load relationships
$amortizations = Amortization::with('payments')->get();  // 1 query
foreach ($amortizations as $a) {
    echo $a->payments->count();  // No query! Relationship already loaded
}

// NESTED: Use nested eager loading
$amortizations = Amortization::with(['payments' => function ($q) {
    $q->orderBy('payment_date', 'desc');
}])->get();
```

#### Fix: Add Query Cache

```php
// Cache a query result
$amortizations = Cache::remember('active_amortizations', 3600, function () {
    return Amortization::where('status', 'active')
        ->with('payments')
        ->get();
});

// Cache a count (fast-changing)
$count = Cache::remember('active_count', 300, function () {
    return Amortization::where('status', 'active')->count();
});

// Cache on write (invalidate when data changes)
Cache::forgetting('active_amortizations');  // Clear before write
Cache::forget('active_count');  // Clear count
```

---

### Symptom: High Database CPU

#### Quick Diagnosis

```sql
-- Check what queries are running
SHOW PROCESSLIST;

-- Check query statistics (MySQL 5.7+)
SELECT * FROM performance_schema.events_statements_summary_by_digest 
ORDER BY SUM_TIMER_WAIT DESC 
LIMIT 10;

-- Monitor in real-time
WATCH -n 1 "mysql -e 'SHOW STATUS LIKE \"Threads_connected\"; SHOW STATUS LIKE \"Questions\";'"
```

#### Fix: Enable Query Cache (if using MySQL 5.6)

```sql
-- Check cache status
SHOW STATUS LIKE 'Qcache%';

-- Enable query cache
SET GLOBAL query_cache_size = 268435456;  -- 256 MB
SET GLOBAL query_cache_type = 1;

-- Add to my.cnf for persistence
query_cache_size = 256M
query_cache_type = 1

-- Monitor effectiveness
SELECT (Qcache_hits / (Qcache_hits + Qcache_inserts)) * 100 as hit_ratio
FROM INFORMATION_SCHEMA.GLOBAL_STATUS;
```

#### Fix: Batch Updates Instead of Row-by-Row

```php
// BEFORE: Slow - 1000 queries
foreach ($data as $item) {
    DB::table('amortizations')
        ->where('id', $item['id'])
        ->update(['status' => $item['status']]);
}

// AFTER: Fast - 1 query
$updates = [];
foreach ($data as $item) {
    $updates[] = ['id' => $item['id'], 'status' => $item['status']];
}
DB::table('amortizations')->upsert($updates, ['id'], ['status']);

// Alternative: Use raw SQL
DB::table('amortizations')
    ->update(DB::raw("status = CASE id 
        WHEN 1 THEN 'active'
        WHEN 2 THEN 'inactive'
        ELSE status END"));
```

---

## API Performance Fixes

### Symptom: Slow API Responses

#### Quick Diagnosis

```bash
# Time an API call
time curl -w "@curl-format.txt" -o /dev/null -s https://api.example.com/api/amortizations

# Monitor requests in real-time
tail -f /var/log/nginx/access.log | cut -d' ' -f10 | sort -n | tail -10

# Test API concurrency
ab -n 100 -c 10 https://api.example.com/api/amortizations
# or
wrk -t4 -c100 -d30s https://api.example.com/api/amortizations
```

#### Fix: Add Response Caching (HTTP Headers)

```php
// Cache static data for 1 hour
return response($data, 200)
    ->header('Cache-Control', 'public, max-age=3600')
    ->header('ETag', md5($data));

// Cache for logged-in users only (private)
return response($data, 200)
    ->header('Cache-Control', 'private, max-age=600');

// Don't cache (dynamic data)
return response($data, 200)
    ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
```

#### Fix: Implement Pagination

```php
// Instead of returning all 50,000 records
// $amortizations = Amortization::all();  // SLOW!

// Return paginated results
$amortizations = Amortization::paginate(50);  // Much faster

// In your API:
{
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 50,
        "total": 10000,
        "last_page": 200
    }
}
```

#### Fix: Add Compression

```nginx
# In nginx.conf
gzip on;
gzip_vary on;
gzip_min_length 1000;
gzip_proxied any;
gzip_types text/plain text/css text/xml text/javascript 
             application/x-javascript application/xml+rss 
             application/json application/javascript;

# Test compression
curl -H "Accept-Encoding: gzip" -i https://api.example.com/api/amortizations
```

#### Fix: Reduce Payload with Sparse Fieldsets

```php
// Client requests only specific fields
GET /api/amortizations?fields=id,principal_amount,status

// Server returns only requested fields
$fields = request('fields') ? explode(',', request('fields')) : null;
$query = Amortization::query();
if ($fields) {
    $query->select($fields);
}
return $query->get();
```

#### Fix: Lazy-Load Related Data

```php
// Instead of eager-loading all relationships
// $amortizations = Amortization::with('payments', 'customer', 'charges')->get();

// Load only what's needed
$amortizations = Amortization::select(['id', 'principal', 'status'])->get();

// Include optional relationships on-demand
if (request('include') === 'payments') {
    $amortizations->load('payments');
}
```

---

## Frontend Performance Fixes

### Symptom: Slow Initial Page Load

#### Quick Diagnosis

```bash
# Test page load speed
npm run build
npm run analyze  # Views bundle

# Quick Lighthouse audit
npx lighthouse https://example.com --output-path=./report.html

# Check bundle size
npm run build -- --report

# Monitor load times
curl -w "@curl-format.txt" -o /dev/null -s https://example.com
```

#### Fix: Code Splitting by Routes

```javascript
// vitest.config.js setup
const modules = import.meta.glob('./views/**/*.vue')

// In router/index.js - BEFORE
import Home from '@/views/Home.vue'
import Dashboard from '@/views/Dashboard.vue'

// In router/index.js - AFTER (lazy load)
const Home = defineAsyncComponent(() => import('@/views/Home.vue'))
const Dashboard = defineAsyncComponent(() => import('@/views/Dashboard.vue'))

// This creates separate chunks: Home-xxx.js, Dashboard-xxx.js
```

#### Fix: Compress Images

```bash
# Resize large images
ffmpeg -i original.png -vf scale=1920x1080 -quality 85 optimized.png

# Convert to WEBP (20-30% smaller)
cwebp original.png -o optimized.webp

# Compress PNG
optipng -o2 original.png

# Use in HTML with fallback
<picture>
  <source srcset="image.webp" type="image/webp">
  <img src="image.png" alt="Description">
</picture>
```

#### Fix: Enable Gzip Compression (Frontend)

```javascript
// vite.config.js
import compression from 'vite-plugin-compression'

export default defineConfig({
  plugins: [
    compression({
      ext: '.gz',
      algorithm: 'gzip',
      deleteOriginFile: false,
    }),
  ],
})

// Or use nginx to compress on-the-fly
```

#### Fix: Minify and Tree-Shake

```javascript
// vite.config.js ensures this by default
export default defineConfig({
  build: {
    minify: 'terser',  // Minify JS
    terserOptions: {
      compress: {
        drop_console: true,  // Remove console.log
      },
    },
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor-vue': ['vue', 'pinia', 'vue-router'],
          'vendor-ui': ['element-plus'],
        },
      },
    },
  },
})
```

---

## Memory Leaks

### Symptom: Memory Usage Growing Over Time

#### Quick Diagnosis (PHP)

```php
// Check current memory usage
echo memory_get_usage(true) / 1024 / 1024 . " MB\n";
echo memory_get_peak_usage(true) / 1024 / 1024 . " MB\n";

// Profile memory allocation
memory_profiler_start();
// ... do work ...
$profile = memory_profiler_end();
print_r($profile);

// Check for circular references
gc_collect_cycles();  // Force garbage collection
```

#### Quick Diagnosis (JavaScript/Node)

```bash
# Check process memory
ps aux | grep node

# Enable heap snapshot
node --trace-gc app.js

# Monitor in Chrome DevTools (if frontend)
# Use Chrome DevTools > Memory tab > Take Snapshot
```

#### Fix: Use Unset/Nullify in PHP

```php
// BEFORE: Memory occupied until function ends
function processLargeDataset() {
    $data = file_get_contents('large_file.csv');  // 100MB
    $parsed = parseCSV($data);
    // ... process ...
    // Memory still occupied!
}

// AFTER: Explicitly free memory
function processLargeDataset() {
    $data = file_get_contents('large_file.csv');
    $parsed = parseCSV($data);
    unset($data);  // Free memory immediately
    // ... process ...
    unset($parsed);  // Release before return
}

// EVEN BETTER: Stream large files
$file = fopen('large_file.csv', 'r');
while (($line = fgets($file)) !== false) {
    processLine($line);
}
fclose($file);  // Memory never holds entire file
```

#### Fix: Break Circular References

```php
// BEFORE: Circular reference prevents garbage collection
class Parent {
    public $child;
}
class Child {
    public $parent;
}

$parent = new Parent();
$child = new Child();
$parent->child = $child;
$child->parent = $parent;

unset($parent);  // Objects still referenced!
unset($child);

// AFTER: Nullify circular references
$parent->child = null;
$child->parent = null;
unset($parent);
unset($child);  // Now garbage collected

// OR: Use Weak references (PHP 7.4+)
class Parent {
    private WeakReference $child;
}
```

---

## Load Testing

### Quick Load Test (Identify Bottlenecks)

```bash
# Install Apache Bench (usually included)
ab -n 1000 -c 10 https://api.example.com/api/amortizations

# Install wrk for more detailed results
# macOS: brew install wrk
# Linux: apt install wrk
wrk -t4 -c100 -d30s https://api.example.com/api/amortizations

# Use autocannon for Node.js apps
npx autocannon https://api.example.com/api/amortizations

# Response time percentiles
wrk -t4 -c100 -d30s \
  -s report.lua \
  https://api.example.com/api/amortizations
```

### Identify Bottleneck Under Load

```bash
#!/bin/bash
# Run steady load for 5 minutes, monitor resources

echo "Starting 5-minute steady load test..."
wrk -t4 -c50 -d300s https://api.example.com/api/amortizations &

# Monitor CPU/Memory
while true; do
    echo "CPU: $(top -bn1 | grep 'Cpu(s)' | awk '{print $2}') | Memory: $(free -h | grep Mem | awk '{print $3}')"
    sleep 1
done
```

---

## Redis/Cache Optimization

### Debug Cache Issues

```bash
# Connect to Redis
redis-cli

# Check memory usage
INFO memory

# List all keys
KEYS *

# Check specific key
GET amortization:123

# Monitor commands in real-time
MONITOR

# Check cache hit ratio
INFO stats
# Look for: keyspace_hits vs keyspace_misses
```

### Fix: Implement Cache Invalidation

```php
// Use file-based cache keys for predictability
Cache::put(
    "amortization:{$id}:{$version}",  // Include version for easy invalidation
    $data,
    3600
);

// Invalidate all related caches on update
public function update(Request $request, $id)
{
    $amortization = Amortization::find($id);
    $amortization->update($request->validated());
    
    // Clear all cache keys related to this amortization
    Cache::forget("amortization:{$id}:*");  // Pattern clear
    Cache::forget("amortizations:active");
    Cache::forget("report:{$amortization->company_id}");
    
    return response()->json($amortization);
}
```

---

## Monitoring & Alerting

### Setup Quick Monitoring (Linux)

```bash
#!/bin/bash
# Monitor key metrics every 30 seconds

while true; do
    clear
    echo "=== KSF Amortization System Monitor ==="
    echo "Time: $(date)"
    echo ""
    
    echo "CPU Usage:"
    top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print int(100 - $1) "%"}'
    
    echo ""
    echo "Memory Usage:"
    free -h | grep Mem | awk '{print $3 " / " $2}'
    
    echo ""
    echo "Database Connections:"
    mysql -e "SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null | tail -1
    
    echo ""
    echo "Slow Queries (5 min):"
    grep $(date -d '5 min ago' '+%H:%M') /var/log/mysql/slow-query.log 2>/dev/null | wc -l
    
    echo ""
    echo "API Requests (5 min):"
    tail -1000 /var/log/nginx/access.log | grep $(date '+%d/%b/%Y:%H:%M') | wc -l
    
    sleep 30
done
```

### Quick Performance Report

```bash
#!/bin/bash
# Generate performance report

cat << EOF
============================================
Performance Report - $(date)
============================================

System Resources:
  CPU Cores: $(nproc)
  Total RAM: $(free -h | grep Mem | awk '{print $2}')
  Available RAM: $(free -h | grep Mem | awk '{print $7}')
  Disk Free: $(df -h / | awk 'NR==2 {print $4}')

Database Status:
  Version: $(mysql --version)
  Max Connections: $(mysql -e "SHOW VARIABLES LIKE 'max_connections';" | tail -1)
  Current Connections: $(mysql -e "SHOW STATUS LIKE 'Threads_connected';" | tail -1)

PHP Status:
  Version: $(php -v | head -1)
  Memory Limit: $(php -i | grep 'memory_limit' | head -1)
  Max Execution Time: $(php -i | grep 'max_execution_time' | head -1)

Recent Errors:
$(tail -20 /var/log/nginx/error.log | grep -v "client closed connection")

Slow Queries (past hour):
$(grep "Query_time > 1" /var/log/mysql/slow-query.log | tail -5)

============================================
EOF
```

---

## Copy-Paste Quick Fixes Cheat Sheet

| Issue | Command | Time |
|-------|---------|------|
| Find slow queries | `SHOW PROCESSLIST; SELECT * FROM performance_schema...` | 30s |
| Add index | `ALTER TABLE table ADD INDEX idx_name (column);` | 1m |
| Cache query | `Cache::remember('key', 3600, function() {...})` | 2m |
| Compress response | `->header('Cache-Control', '...')` | 1m |
| Profile memory | `memory_get_peak_usage()` | 2m |
| Load test | `wrk -t4 -c100 -d30s URL` | 1m |
| Check Redis | `redis-cli INFO stats` | 1m |
| Invalidate cache | `Cache::forget('pattern');` | 1m |

---

**Last Updated**: 2026-04-28  
**Next Review**: 2026-05-05

