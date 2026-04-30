# Performance Profiling Results Template

**Purpose**: Template for recording and analyzing performance profiling runs  
**Format**: Copy and paste for each profiling session  
**Retention**: Keep all results for trend analysis

---

## Profiling Session: [DATE] - [DESCRIPTION]

**Session ID**: `prof_20260[MMdd]_[hhmm]`  
**Tester**: [Name]  
**Environment**: [local/staging/production]  
**Baseline Version**: v1.0.0 (yyyy-mm-dd)  
**Test Version**: vX.Y.Z (yyyy-mm-dd)  
**Notes**: [Brief description]

---

### Initial State

```
Date/Time:        [YYYY-MM-DD HH:MM:SS]
System Load:      [Low/Medium/High]
Database Size:    ~[GB]
Cache Status:     [Warm/Cold]
System Memory:    [Used]/[Total] GB
Network Status:   [Normal/Degraded]
```

---

## Test Results

### 1. Database Performance

#### Query: List Amortizations (Basic)
```
Query: SELECT * FROM amortizations LIMIT 100
Execution Plan: [EXPLAIN RESULT HERE]

Attempt 1: [XXX]ms
Attempt 2: [XXX]ms
Attempt 3: [XXX]ms
Average:   [XXX]ms
Min/Max:   [XXX]ms / [XXX]ms

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes: 
```

#### Query: Calculate Amortization Schedule
```
Query: SELECT ... FROM amortizations a 
       LEFT JOIN payments p ON ...
       WHERE ...

Attempt 1: [XXX]ms
Attempt 2: [XXX]ms
Attempt 3: [XXX]ms
Average:   [XXX]ms
Min/Max:   [XXX]ms / [XXX]ms

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

#### Query: Report Generation (Complex)
```
Query: [COMPLEX QUERY HERE]

Attempt 1: [XXX]ms
Attempt 2: [XXX]ms
Attempt 3: [XXX]ms
Average:   [XXX]ms
Min/Max:   [XXX]ms / [XXX]ms

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Memory Used: [MB]
Rows Returned: [N]
Notes:
```

#### Summary

| Query | Baseline | This Test | δ | Status |
|-------|----------|-----------|---|--------|
| List (100) | 45ms | [XX]ms | [+/- XX%] | ✓/⚠/✗ |
| Calculate | 180ms | [XX]ms | [+/- XX%] | ✓/⚠/✗ |
| Report | 520ms | [XX]ms | [+/- XX%] | ✓/⚠/✗ |

---

### 2. API Performance

#### Endpoint: GET /api/amortizations
```
Concurrency: 10 req/sec, 30 requests total
Framework: PHP/Laravel
Cache: [Cold/Warm]

Results:
  Fastest:   [XX]ms
  Slowest:   [XX]ms
  Average:   [XX]ms
  Median:    [XX]ms
  StdDev:    [XX]ms
  
  p50:       [XX]ms
  p75:       [XX]ms
  p90:       [XX]ms
  p95:       [XX]ms
  p99:       [XX]ms

Error Rate: [X]%
Status Codes: 200: [N], 400: [N], 500: [N]

Database Queries: [N]
Cache Hits: [N] (hit rate: [X]%)

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

#### Endpoint: POST /api/amortizations
```
Concurrency: 5 req/sec, 20 requests total

Results:
  Fastest:   [XX]ms
  Slowest:   [XX]ms
  Average:   [XX]ms
  Median:    [XX]ms
  
  p95:       [XX]ms
  p99:       [XX]ms

Error Rate: [X]%
Database Writes: [N]
Database Reads: [N]

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

#### Endpoint: GET /api/reports/1/export
```
File Size: [MB]
Compression: [Yes/No]
Concurrency: 3 req/sec, 10 requests total

Results:
  Fastest:   [XX]ms
  Slowest:   [XX]ms
  Average:   [XX]ms
  p95:       [XX]ms

Memory Peak: [MB]
Error Rate: [X]%

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

#### API Summary

| Endpoint | Baseline p95 | Test p95 | δ | Status |
|----------|--------------|----------|---|--------|
| GET /api/amortizations | 240ms | [XXX]ms | [+/- XX%] | ✓/⚠/✗ |
| POST /api/amortizations | 450ms | [XXX]ms | [+/- XX%] | ✓/⚠/✗ |
| GET .../export | 1200ms | [XXX]ms | [+/- XX%] | ✓/⚠/✗ |

---

### 3. Frontend Performance

#### Bundle Size Analysis
```
JavaScript:
  Baseline:  [XXX] KB (gzipped: [XX] KB)
  This Test: [XXX] KB (gzipped: [XX] KB)
  Change:    [+/- XX%]

CSS:
  Baseline:  [XX] KB (gzipped: [XX] KB)
  This Test: [XX] KB (gzipped: [XX] KB)
  Change:    [+/- XX%]

HTML:
  Baseline:  [XX] KB (gzipped: [XX] KB)
  This Test: [XX] KB (gzipped: [XX] KB)
  Change:    [+/- XX%]

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Target: < [XXX] KB gzipped
Notes:
```

#### Page Load Performance
```
Test: Load homepage (cache cold)

Metrics:
  FCP (First Contentful Paint): [XXX]ms (baseline: [XXX]ms)
  LCP (Largest Contentful Paint): [XXX]ms (baseline: [XXX]ms)
  CLS (Cumulative Layout Shift): [X.XX] (baseline: [X.XX])
  TTI (Time to Interactive): [XXX]ms (baseline: [XXX]ms)

Lighthouse Score: [XX]/100 (baseline: [XX])
  Performance: [XX] (baseline: [XX])
  Accessibility: [XX]
  Best Practices: [XX]
  SEO: [XX]

Resource Timing:
  Largest Resource: [Name] - [XX]ms, [XX] KB
  Render-blocking: [Number] resources

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

#### Frontend Summary

| Metric | Baseline | This Test | δ | Target | Status |
|--------|----------|-----------|---|--------|--------|
| Bundle Size | [XX]KB | [XX]KB | [+/- X%] | <[XX]KB | ✓/⚠/✗ |
| FCP | [XXX]ms | [XXX]ms | [+/- X%] | <[XXX]ms | ✓/⚠/✗ |
| LCP | [XXX]ms | [XXX]ms | [+/- X%] | <[XXX]ms | ✓/⚠/✗ |
| TTI | [XXX]ms | [XXX]ms | [+/- X%] | <[XXX]ms | ✓/⚠/✗ |

---

### 4. Memory & Resource Usage

#### Memory Profiling
```
PHP/Backend Service:
  Baseline Peak:    [XX]MB
  This Test Peak:   [XX]MB
  Average:          [XX]MB
  Leak Detected:    [Yes/No]
  Memory Growth:    [+/- XX]% over [duration]

Node/Frontend Build:
  Baseline Peak:    [XX]MB
  This Test Peak:   [XX]MB
  Average:          [XX]MB

Database Connection Pool:
  Peak Connections: [N]/[Total]
  Average: [N]/[Total]
  Idle Connections: [N]

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

#### CPU Usage
```
Baseline:
  Average CPU: [XX]%
  Peak CPU: [XX]%
  Load Average: [X.XX], [X.XX], [X.XX]

This Test:
  Average CPU: [XX]%
  Peak CPU: [XX]%
  Load Average: [X.XX], [X.XX], [X.XX]

Change: [+/- XX]%

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

#### Disk I/O
```
Baseline:
  Write Speed: [XX] MB/s
  Read Speed: [XX] MB/s
  IOPS: [XX] ops/sec

This Test:
  Write Speed: [XX] MB/s
  Read Speed: [XX] MB/s
  IOPS: [XX] ops/sec

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

---

### 5. Cache Effectiveness

#### Cache Hit Ratio
```
Query Cache:
  Baseline: [XX]% hit rate
  This Test: [XX]% hit rate
  Change: [+/- XX]%

HTTP Cache:
  Status 304 (Not Modified): [XX%]
  Proper headers: Yes/No

Redis/Memcached:
  Hit Rate: [XX]%
  Evictions: [N] (rate: [X]% per minute)
  Memory Usage: [XX]MB / [XX]MB

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

#### Cache Invalidation Test
```
Scenario: Insert new record, verify cache invalidated

Steps:
  1. Cache record: [Time to cache]ms
  2. Retrieve from cache: [Cache hit]
  3. Insert new record via API
  4. Verify cache invalidated: [Time]ms
  5. Cache refreshed: [Time]ms

Total Time to Consistency: [XXX]ms

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

---

### 6. Load Testing Results

#### 10 Concurrent Users (Steady State)
```
Duration: 5 minutes
Total Requests: [N]
Successful: [N] ([X]%)
Failed: [N] ([X]%)

Response Times:
  Average: [XX]ms
  p50: [XX]ms
  p95: [XX]ms
  p99: [XX]ms
  Max: [XX]ms

Throughput: [X] req/sec
Error Rate: [X]%

System Resources:
  CPU: [XX]% avg
  Memory: [XX]MB avg
  DB Connections: [N]/[Total]

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

#### 25 Concurrent Users (Heavy Load)
```
Duration: 5 minutes
Total Requests: [N]
Successful: [N] ([X]%)
Failed: [N] ([X]%)

Response Times:
  Average: [XX]ms
  p50: [XX]ms
  p95: [XX]ms
  p99: [XX]ms
  Max: [XX]ms

Throughput: [X] req/sec
Error Rate: [X]%

System Resources:
  CPU: [XX]% avg (peak: [XX]%)
  Memory: [XX]MB avg (peak: [XX]MB)
  DB Connections: [N]/[Total]

Bottleneck Identified: [Database/API/Frontend/Network]

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

#### Spike Test (Ramp Up to 100 Users in 1 min)
```
Time to Handle Spike: [XXX]ms
Response Degradation: [+XX]%
Error Rate During Spike: [X]%
Recovery Time: [XXX]s

Status: ✓ Pass / ⚠ Warning / ✗ Fail
Notes:
```

---

### 7. Comparison Summary

| Category | Baseline | This Test | Change | Status | Notes |
|----------|----------|-----------|--------|--------|-------|
| **Database** |
| Simple Query | 45ms | [XX]ms | [+/- X%] | ✓/⚠/✗ | |
| Complex Query | 520ms | [XX]ms | [+/- X%] | ✓/⚠/✗ | |
| **API** |
| GET p95 | 240ms | [XX]ms | [+/- X%] | ✓/⚠/✗ | |
| POST p95 | 450ms | [XX]ms | [+/- X%] | ✓/⚠/✗ | |
| **Frontend** |
| Bundle Size | [XX]KB | [XX]KB | [+/- X%] | ✓/⚠/✗ | |
| FCP | [XXX]ms | [XXX]ms | [+/- X%] | ✓/⚠/✗ | |
| **Infrastructure** |
| Avg CPU | [XX]% | [XX]% | [+/- X%] | ✓/⚠/✗ | |
| Peak Memory | [XX]MB | [XX]MB | [+/- X%] | ✓/⚠/✗ | |
| **Cache** |
| Hit Ratio | [XX]% | [XX]% | [+/- X%] | ✓/⚠/✗ | |

---

## Analysis & Recommendations

### Overall Assessment

**Status**: ✓ PASS / ⚠ CONDITIONAL / ✗ FAIL

**Summary**: [2-3 sentences on overall health]

### Performance Improvements Achieved
```
✓ Improvement 1: [Description] - [X]% improvement
✓ Improvement 2: [Description] - [X]% improvement
✓ Improvement 3: [Description] - [X]% improvement
```

### Issues Identified
```
⚠ Issue 1: [Description]
  - Impact: [High/Medium/Low]
  - Root Cause: [Analysis]
  - Fix: [Suggested action]
  
⚠ Issue 2: [Description]
  - Impact: [High/Medium/Low]
  - Root Cause: [Analysis]
  - Fix: [Suggested action]
```

### Recommendations for Next Phase
```
1. Priority: [Description of next optimization]
   Estimated Gain: [X]% improvement
   Effort: [Low/Medium/High]
   
2. Priority: [Description of next optimization]
   Estimated Gain: [X]% improvement
   Effort: [Low/Medium/High]
```

### Bottlenecks Observed
```
1. [Component]: [Description]
   Current Impact: [High/Medium/Low]
   Affected Metrics: [List]
   
2. [Component]: [Description]
   Current Impact: [High/Medium/Low]
   Affected Metrics: [List]
```

---

## Regression Analysis

### Compared to Baseline v1.0.0

| Metric | Baseline | This Test | δ | Assessment |
|--------|----------|-----------|---|------------|
| API p95 | 240ms | [XX]ms | [+/- XX%] | ✓ Improvement / ⚠ Regression / ⚠ Neutral |
| Database Simple | 45ms | [XX]ms | [+/- XX%] | ✓ Improvement / ⚠ Regression / ⚠ Neutral |
| Memory Peak | [XX]MB | [XX]MB | [+/- XX%] | ✓ Improvement / ⚠ Regression / ⚠ Neutral |
| FCP | [XXX]ms | [XXX]ms | [+/- XX%] | ✓ Improvement / ⚠ Regression / ⚠ Neutral |

**Regression Tolerance**: ±5% is acceptable for normal variance

---

## Sign-Off

**Results Verified By**: [Name]  
**Date Verified**: [YYYY-MM-DD]  
**Ready for Merge**: ✓ Yes / ✗ No (Reason: [XXX])  
**Ready for Deployment**: ✓ Yes / ✗ No (Reason: [XXX])  

**Approval Signature**: _________________________ Date: _______

---

**Previous Results**:
- [Link to previous profiling session]
- [Link to previous profiling session]
- [Link to previous profiling session]

**Next Review Date**: [YYYY-MM-DD]

