# Phase 13 Week 1: Query Optimization Implementation Guide

**Status:** IN PROGRESS  
**Date:** December 16, 2025  
**Target Duration:** 5 days  
**Performance Target:** 30-50% query improvement  
**Test Coverage:** 8 new tests planned  

---

## Overview

Week 1 of Phase 13 focuses on optimizing the 4 critical database queries that impact overall application performance. This guide provides implementation steps, profiling data, and optimization strategies for each query.

### Week 1 Schedule

- **Days 1-2:** Profile queries, create EXPLAIN analysis, identify bottlenecks
- **Days 3-4:** Implement optimizations with code changes
- **Day 5:** Testing, validation, and performance measurement

### Key Metrics

| Metric | Current | Target | Unit |
|--------|---------|--------|------|
| Portfolio balance query | 200-300ms | <120ms | ms (500 loans) |
| Payment schedule query | 400-500ms | <250ms | ms (1000 records) |
| Interest calculation | 1.2-1.5ms | <0.8ms | ms per loan |
| GL account mapping | 80-120ms | <100ms | ms per loan |

---

## Query 1: Portfolio Balance Query

### Current Implementation

**Location:** [src/Ksfraser/Amortizations/Models/Loan.php](src/Ksfraser/Amortizations/Models/Loan.php)  
**Method:** `getLoanBalance()`, `getPortfolioBalance()`  
**Frequency:** High (called for portfolio views, reports)  
**Impact:** Affects portfolio dashboard, risk assessment, compliance reporting  

### Current Query Pattern

```php
// CURRENT: N+1 query problem
foreach ($loans as $loan) {
    $balance = $db->query(
        "SELECT SUM(principal - paid_principal) as balance 
         FROM amortization_schedule 
         WHERE loan_id = ?"
    );
}
```

**Performance Issue:** N+1 query problem (5+ queries per 500 loans)

### Optimization Strategy

#### Step 1: Index Addition (Primary Optimization)

Add composite index on `amortization_schedule` table:

```sql
ALTER TABLE amortization_schedule 
ADD INDEX idx_loan_balance (loan_id, payment_status);
```

**Expected Improvement:** 40-50% (from 250ms to 120-150ms)

#### Step 2: Batch Query Implementation

Replace N+1 queries with single batch query:

```php
// OPTIMIZED: Single query for all loans
public function getPortfolioBalances(array $loanIds): array {
    $placeholders = implode(',', array_fill(0, count($loanIds), '?'));
    
    $query = "
        SELECT 
            loan_id,
            SUM(principal - paid_principal) as balance,
            SUM(interest_due) as interest_accrued
        FROM amortization_schedule
        WHERE loan_id IN ($placeholders)
        AND payment_status != 'paid'
        GROUP BY loan_id
    ";
    
    return $this->db->query($query, $loanIds);
}
```

**Performance Impact:** 60-70% improvement (from 300ms to 90-120ms for 500 loans)

#### Step 3: Query Result Caching

Implement 5-minute TTL cache:

```php
public function getPortfolioBalance(array $loans): float {
    $cacheKey = 'portfolio_balance_' . hash('sha256', json_encode(
        array_column($loans, 'id')
    ));
    
    if ($cache->has($cacheKey)) {
        return $cache->get($cacheKey);
    }
    
    $total = array_sum(array_column(
        $this->getPortfolioBalances(array_column($loans, 'id')),
        'balance'
    ));
    
    $cache->set($cacheKey, $total, 300); // 5 min TTL
    return $total;
}
```

**Cache Hit Target:** 60%+  
**Final Performance:** <120ms for 500 loans (combined with indexes)

### Implementation Tasks (Query 1)

- [ ] **Task 1.1:** Add index to amortization_schedule table (database migration)
- [ ] **Task 1.2:** Implement `getPortfolioBalances()` batch query method
- [ ] **Task 1.3:** Refactor portfolio balance calculation to use batch query
- [ ] **Task 1.4:** Add QueryResultCache wrapper
- [ ] **Task 1.5:** Create QueryOptimizationTest for portfolio balance
- [ ] **Task 1.6:** Profile query performance (EXPLAIN, execution time)
- [ ] **Task 1.7:** Validate cache hit rate (target: 60%+)
- [ ] **Task 1.8:** Update documentation with optimization notes

---

## Query 2: Payment Schedule Query

### Current Implementation

**Location:** [src/Ksfraser/Amortizations/Models/Loan.php](src/Ksfraser/Amortizations/Models/Loan.php)  
**Method:** `getPaymentSchedule()`, `getRemainingSchedule()`  
**Frequency:** High (called for loan detail views, payment pages)  
**Impact:** Affects loan detail pages, payment tracking, payment forecast  

### Current Query Pattern

```php
// CURRENT: Inefficient column selection and lack of filtering
$stmt = $db->prepare("
    SELECT * FROM amortization_schedule 
    WHERE loan_id = ? 
    ORDER BY payment_date ASC
");
```

**Performance Issue:** 
- Selects all columns (including unused ones)
- No filtering for status
- Large result sets (1000+ rows)

### Optimization Strategy

#### Step 1: Index Addition

Add index on payment schedule queries:

```sql
ALTER TABLE amortization_schedule 
ADD INDEX idx_schedule_lookup (loan_id, payment_date, payment_status);
```

**Expected Improvement:** 20-30% (from 450ms to 315-360ms)

#### Step 2: Selective Column Query

Select only needed columns:

```php
// OPTIMIZED: Select only needed columns
public function getRemainingSchedule(int $loanId, ?DateTime $afterDate = null): array {
    $columns = [
        'payment_number',
        'payment_date',
        'payment_amount',
        'principal_payment',
        'interest_payment',
        'balance_after_payment',
        'payment_status'
    ];
    
    $sql = "
        SELECT " . implode(',', $columns) . "
        FROM amortization_schedule
        WHERE loan_id = ?
        AND payment_status IN ('pending', 'scheduled')
    ";
    
    if ($afterDate) {
        $sql .= " AND payment_date > ?";
        return $this->db->query($sql, [$loanId, $afterDate->format('Y-m-d')]);
    }
    
    return $this->db->query($sql, [$loanId]);
}
```

**Performance Impact:** 30-40% improvement (from 400ms to 240-280ms for 1000 rows)

#### Step 3: Pagination for Large Result Sets

Implement pagination for schedules:

```php
public function getSchedulePage(
    int $loanId,
    int $pageSize = 50,
    int $offset = 0
): array {
    $columns = ['payment_number', 'payment_date', 'payment_amount', ...];
    
    $sql = "
        SELECT " . implode(',', $columns) . "
        FROM amortization_schedule
        WHERE loan_id = ?
        ORDER BY payment_date ASC
        LIMIT ? OFFSET ?
    ";
    
    return [
        'total' => $this->countScheduleRows($loanId),
        'page_size' => $pageSize,
        'data' => $this->db->query($sql, [$loanId, $pageSize, $offset])
    ];
}
```

**Performance Impact:** 50-60% improvement (from 500ms to 200-250ms)

### Implementation Tasks (Query 2)

- [ ] **Task 2.1:** Add index to amortization_schedule table
- [ ] **Task 2.2:** Implement `getRemainingSchedule()` with column selection
- [ ] **Task 2.3:** Implement pagination support
- [ ] **Task 2.4:** Refactor schedule retrieval to use optimized queries
- [ ] **Task 2.5:** Create ScheduleQueryOptimizationTest
- [ ] **Task 2.6:** Profile query performance with EXPLAIN
- [ ] **Task 2.7:** Validate pagination performance
- [ ] **Task 2.8:** Update documentation

---

## Query 3: Interest Calculation Query

### Current Implementation

**Location:** [src/Ksfraser/Amortizations/Models/AmortizationModel.php](src/Ksfraser/Amortizations/Models/AmortizationModel.php)  
**Method:** `calculateCumulativeInterest()`, `getInterestPaid()`  
**Frequency:** Medium (called during calculations, reports)  
**Impact:** Affects interest reports, payment calculations, GL posting  

### Current Query Pattern

```php
// CURRENT: Full table scan per loan
$stmt = $db->prepare("
    SELECT SUM(interest_payment) as total_interest
    FROM amortization_schedule
    WHERE loan_id = ?
");
```

**Performance Issue:** 
- Full table scan (no index)
- Repeated for each calculation
- Redundant calculations

### Optimization Strategy

#### Step 1: Index Addition

Add index for interest calculations:

```sql
ALTER TABLE amortization_schedule 
ADD INDEX idx_interest_calc (loan_id, payment_status);
```

**Expected Improvement:** 25-35% (from 1.4ms to 0.9-1.0ms)

#### Step 2: Denormalized Interest Tracking

Store interest totals at loan level:

```sql
ALTER TABLE loans 
ADD COLUMN total_interest_paid DECIMAL(12,2) DEFAULT 0,
ADD COLUMN total_interest_accrued DECIMAL(12,2) DEFAULT 0;

CREATE INDEX idx_interest_tracking ON loans(
    total_interest_paid, 
    total_interest_accrued
);
```

**Performance Impact:** 70-80% improvement (from 1.4ms to 0.3-0.4ms per loan)

#### Step 3: Interest Calculation Cache

Implement per-loan interest cache:

```php
public function getCumulativeInterest(int $loanId, bool $useCache = true): float {
    if ($useCache) {
        $cacheKey = "interest_loan_{$loanId}";
        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }
    }
    
    // Use denormalized column first
    $loan = $this->db->getLoan($loanId);
    $interest = $loan['total_interest_paid'] ?? 0;
    
    // Recalculate if needed
    if ($interest === 0) {
        $interest = $this->calculateFromSchedule($loanId);
    }
    
    if ($useCache) {
        $cache->set($cacheKey, $interest, 600); // 10 min TTL
    }
    
    return $interest;
}
```

**Cache Hit Target:** 70%+  
**Final Performance:** <0.8ms per loan

### Implementation Tasks (Query 3)

- [ ] **Task 3.1:** Add index to amortization_schedule table
- [ ] **Task 3.2:** Add denormalized columns to loans table (migration)
- [ ] **Task 3.3:** Update loan creation to initialize denormalized columns
- [ ] **Task 3.4:** Implement denormalized column synchronization
- [ ] **Task 3.5:** Create InterestCalculationOptimizationTest
- [ ] **Task 3.6:** Profile query performance
- [ ] **Task 3.7:** Validate denormalization accuracy
- [ ] **Task 3.8:** Update documentation

---

## Query 4: GL Account Mapping Query

### Current Implementation

**Location:** [packages/ksf-amortizations-frontaccounting/src/Ksfraser/Amortizations/FA/GLAccountMapper.php](packages/ksf-amortizations-frontaccounting/src/Ksfraser/Amortizations/FA/GLAccountMapper.php)  
**Method:** `mapLoanToGLAccounts()`, `getAccountMapping()`  
**Frequency:** Medium (called during GL posting)  
**Impact:** Affects GL posting accuracy, journal entry creation  

### Current Query Pattern

```php
// CURRENT: Multiple queries per mapping
foreach ($loanLines as $line) {
    $account = $db->query(
        "SELECT account_code FROM gl_accounts 
         WHERE account_type = ? AND inactive = 0"
    );
}
```

**Performance Issue:** N+1 query pattern with redundant lookups

### Optimization Strategy

#### Step 1: Index Addition

Add index to GL accounts:

```sql
ALTER TABLE gl_accounts 
ADD INDEX idx_account_lookup (account_type, inactive, account_code);
```

**Expected Improvement:** 20-25% (from 100ms to 75-80ms)

#### Step 2: Mapping Cache Implementation

Cache account mappings:

```php
public function getAccountMappingBatch(array $types): array {
    $cacheKey = 'gl_mappings_' . hash('sha256', json_encode($types));
    
    if ($this->cache->has($cacheKey)) {
        return $this->cache->get($cacheKey);
    }
    
    $placeholders = implode(',', array_fill(0, count($types), '?'));
    
    $sql = "
        SELECT 
            account_type,
            account_code,
            account_name
        FROM gl_accounts
        WHERE account_type IN ($placeholders)
        AND inactive = 0
        ORDER BY account_type, account_code
    ";
    
    $mappings = $this->db->query($sql, $types);
    $this->cache->set($cacheKey, $mappings, 3600); // 1 hour TTL
    
    return $mappings;
}
```

**Cache Hit Target:** 80%+  
**Performance Impact:** 60-70% improvement (from 100ms to 30-40ms)

### Implementation Tasks (Query 4)

- [ ] **Task 4.1:** Add index to gl_accounts table
- [ ] **Task 4.2:** Implement `getAccountMappingBatch()` method
- [ ] **Task 4.3:** Refactor GL posting to use batch mapping
- [ ] **Task 4.4:** Add MappingCache wrapper
- [ ] **Task 4.5:** Create GLAccountMappingTest
- [ ] **Task 4.6:** Profile query performance
- [ ] **Task 4.7:** Validate cache effectiveness
- [ ] **Task 4.8:** Update documentation

---

## Implementation Checklist

### Database Changes

- [ ] **Migration 001:** Add indexes to amortization_schedule
  - [ ] idx_loan_balance (loan_id, payment_status)
  - [ ] idx_schedule_lookup (loan_id, payment_date, payment_status)
  - [ ] idx_interest_calc (loan_id, payment_status)

- [ ] **Migration 002:** Add denormalized columns to loans
  - [ ] total_interest_paid
  - [ ] total_interest_accrued
  - [ ] Add index on both columns

- [ ] **Migration 003:** Add index to gl_accounts
  - [ ] idx_account_lookup (account_type, inactive, account_code)

### Code Changes

- [ ] Implement `getPortfolioBalances()` batch query (Query 1)
- [ ] Implement `getRemainingSchedule()` with pagination (Query 2)
- [ ] Implement denormalized interest tracking (Query 3)
- [ ] Implement `getAccountMappingBatch()` caching (Query 4)
- [ ] Add QueryResultCache wrapper class
- [ ] Update all DataProvider implementations

### Testing

- [ ] Create PortfolioBalanceOptimizationTest (Query 1)
- [ ] Create ScheduleOptimizationTest (Query 2)
- [ ] Create InterestCalculationOptimizationTest (Query 3)
- [ ] Create GLAccountMappingTest (Query 4)
- [ ] Performance regression tests (ensure improvements)
- [ ] Batch operation validation tests
- [ ] Cache hit rate validation
- [ ] Integration tests with caching disabled/enabled

### Documentation

- [ ] Update AmortizationModel documentation with optimization notes
- [ ] Document new batch query methods with examples
- [ ] Document caching strategy and TTLs
- [ ] Document index strategy and rationale
- [ ] Create QUERY_OPTIMIZATION_GUIDE.md

---

## Performance Validation

### Measurement Strategy

1. **Baseline Measurements** (Current State)
   - Run each query 100 times, record execution time
   - Calculate average, min, max, p95, p99

2. **Index Impact** (After Step 1)
   - Measure improvement from indexes alone
   - Expected: 20-50% improvement

3. **Batch Query Impact** (After Step 2)
   - Measure improvement from N+1 elimination
   - Expected: 30-70% improvement

4. **Cache Impact** (After Step 3)
   - Measure with cache warm (hit rate 60%+)
   - Measure with cache cold (first run)
   - Expected: 60-80% improvement

### Test Case Examples

```php
class PortfolioBalanceOptimizationTest extends PHPUnit\Framework\TestCase {
    
    public function testPortfolioBalanceIndexPerformance() {
        // Create 500 loans with schedules
        $loans = $this->createTestLoans(500);
        
        // Measure N+1 query time
        $startTime = microtime(true);
        foreach ($loans as $loan) {
            $balance = $this->db->query(
                "SELECT SUM(...) FROM amortization_schedule WHERE loan_id = ?"
            );
        }
        $nPlusOneTime = microtime(true) - $startTime;
        
        // Measure batch query time
        $startTime = microtime(true);
        $balances = $this->dataProvider->getPortfolioBalances(
            array_column($loans, 'id')
        );
        $batchTime = microtime(true) - $startTime;
        
        // Assert improvement
        $improvement = (($nPlusOneTime - $batchTime) / $nPlusOneTime) * 100;
        $this->assertGreaterThan(50, $improvement); // At least 50% improvement
        $this->assertLessThan(150, $batchTime * 1000); // Less than 150ms
    }
    
    public function testPortfolioBalanceCacheHitRate() {
        $loans = $this->createTestLoans(100);
        $cache = new ArrayCache();
        
        // First call - cache miss
        $startTime = microtime(true);
        $balance1 = $this->service->getPortfolioBalance($loans, $cache);
        $missTime = microtime(true) - $startTime;
        
        // Second call - cache hit
        $startTime = microtime(true);
        $balance2 = $this->service->getPortfolioBalance($loans, $cache);
        $hitTime = microtime(true) - $startTime;
        
        // Assert same result
        $this->assertEquals($balance1, $balance2);
        
        // Assert significant speedup
        $improvement = (($missTime - $hitTime) / $missTime) * 100;
        $this->assertGreaterThan(75, $improvement); // 75%+ improvement
        $this->assertLessThan(5, $hitTime * 1000); // Less than 5ms
    }
}
```

---

## Success Criteria

### Performance Targets

| Query | Current | Target | Improvement |
|-------|---------|--------|------------|
| Portfolio balance | 250-300ms | <120ms | 50%+ |
| Payment schedule | 400-500ms | <250ms | 40%+ |
| Interest calc | 1.2-1.5ms | <0.8ms | 35%+ |
| GL account mapping | 100ms | <70ms | 30%+ |

### Code Quality

- [ ] All new methods have documentation (docblocks, examples)
- [ ] Code follows SOLID principles
- [ ] 100% test coverage for optimized queries
- [ ] No new warnings or errors from static analysis

### Cache Effectiveness

- [ ] Portfolio balance cache: 60%+ hit rate
- [ ] Query result cache: 70%+ hit rate
- [ ] GL mapping cache: 80%+ hit rate

### Documentation

- [ ] Optimization strategy documented with rationale
- [ ] Performance measurements recorded
- [ ] Migration scripts documented
- [ ] Usage examples provided

---

## Post-Optimization Validation

### After Implementation Complete

1. **Performance Regression Test**
   - Run all query tests
   - Verify improvements maintained
   - Check memory usage

2. **Integration Testing**
   - Test with caching enabled/disabled
   - Test with different data volumes (100, 500, 1000 loans)
   - Test cache invalidation scenarios

3. **Production Readiness**
   - Verify index creation doesn't lock tables
   - Verify denormalization logic is robust
   - Document rollback procedures

---

## Notes & Considerations

### Small-Scale Deployment Context

This optimization strategy is designed for 1-2 concurrent users:
- No distributed caching needed (in-memory cache sufficient)
- No connection pooling needed (few connections)
- Simple index strategy (no over-indexing)
- Basic monitoring sufficient

### Migration Strategy

All migrations should be tested with real data:
1. Create index (non-blocking migration)
2. Verify index usage with EXPLAIN
3. Deploy code changes
4. Monitor performance improvement
5. Keep rollback plan available

### Future Optimization Opportunities

- Query result compression for large datasets
- Materialized views for complex aggregations
- Periodic denormalization updates (batch jobs)
- Read-write separation for reporting queries

---

## Next Steps

1. **Day 1-2:** Profile all 4 queries, create EXPLAIN analysis
2. **Day 3-4:** Implement all database indexes and code optimizations
3. **Day 5:** Create tests, validate performance, update documentation

**Completion Target:** December 19, 2025  
**Success Metric:** All 4 queries achieve 30-50% improvement  
**Test Coverage:** 8 new tests, 100% passing

