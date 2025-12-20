# Phase 13: Detailed Requirements - Optimization for Small-Scale Deployment

**Status:** Planning Complete  
**Date:** December 16, 2025  
**Target Users:** 1-2 concurrent  
**Phase Duration:** 3 weeks (15 working days)

---

## Phase 13 Overview

Phase 13 focuses on code refactoring and optimization for a small-scale deployment. Unlike Phase 12 (performance testing), Phase 13 addresses code quality, maintainability, and targeted optimization for typical usage patterns.

### Phase 13 Goals
1. **Improve code clarity** - Make codebase easier to maintain
2. **Optimize hot paths** - Reduce calculation time
3. **Implement strategic caching** - Reduce repeated calculations
4. **Standardize patterns** - Consistent code across adaptors
5. **Enhance testing** - Better test infrastructure

---

## Requirement 1: Query Optimization

### REQ-P13-001: Portfolio Balance Query Optimization

**Requirement Type:** Performance Optimization  
**Priority:** High  
**Effort Estimate:** 1 day  
**Expected Improvement:** 40-60% faster

#### Current State:
```
- Multiple JOIN operations
- Loop-based aggregation in PHP
- N+1 problem on payment lookups
- Estimated time: 200-300ms for 500 loans
```

#### Desired State:
```
- Single optimized query with SQL aggregation
- Database-level calculations
- Proper query indexing
- Estimated time: 80-120ms for 500 loans
```

#### Acceptance Criteria:
1. Portfolio balance calculation < 120ms for 500 loans
2. Query uses single database query (not N+1)
3. Database indexes on portfolio_id, status, amount
4. Performance test validates improvement
5. No functionality change (same results)
6. Backward compatible with existing code

#### Tasks:
1. Analyze current portfolio balance query with EXPLAIN
2. Design optimized query (single aggregation)
3. Create database indexes for filtering columns
4. Implement optimized version
5. Validate results match original implementation
6. Create performance comparison test
7. Update code documentation

---

### REQ-P13-002: Payment Schedule Query Optimization

**Requirement Type:** Performance Optimization  
**Priority:** High  
**Effort Estimate:** 1 day  
**Expected Improvement:** 50-70% faster

#### Current State:
```
- Separate queries per payment
- N+1 problem on loan lookups
- Multiple round-trips to database
- Estimated time: 500-800ms for 1000 schedules
```

#### Desired State:
```
- Batch query for all schedules
- Single query with JOINs
- Indexed lookups
- Estimated time: 150-250ms for 1000 schedules
```

#### Acceptance Criteria:
1. Schedule query < 250ms for 1000 records
2. Query uses batch load (not loop)
3. Proper JOIN on loan, rate, GL accounts
4. Performance test validates improvement
5. Results identical to original
6. Supports filtering and sorting

#### Tasks:
1. Identify all payment schedule queries
2. Create batch query version
3. Add indexes on schedule_id, loan_id, due_date
4. Implement and test
5. Validate backward compatibility
6. Create performance test
7. Document query optimization

---

### REQ-P13-003: Cumulative Interest Calculation Optimization

**Requirement Type:** Performance Optimization  
**Priority:** Medium  
**Effort Estimate:** 0.5 days  
**Expected Improvement:** 30-40% faster

#### Current State:
```
- Loop-based calculation per payment
- Repeated calculations
- Estimated time: 1.2ms per loan
```

#### Desired State:
```
- Aggregate calculation in database (if supported)
- Or optimized PHP calculation
- Reduced calculations
- Estimated time: 0.6-0.8ms per loan
```

#### Acceptance Criteria:
1. Calculation < 0.8ms per loan
2. Results identical to original
3. Performance test validates improvement
4. Works with all database types (SQLite, MySQL, PostgreSQL)

#### Tasks:
1. Analyze current calculation algorithm
2. Identify optimization opportunities
3. Implement optimized version (database or PHP)
4. Test accuracy
5. Create performance test
6. Document algorithm

---

### REQ-P13-004: GL Account Mapping Query Optimization

**Requirement Type:** Performance Optimization  
**Priority:** Medium  
**Effort Estimate:** 0.5 days  
**Expected Improvement:** 20-30% faster

#### Current State:
```
- Individual query per payment in loop
- Repeated lookups for same accounts
- Estimated time: 100-200ms for 100 payments
```

#### Desired State:
```
- Batch load GL accounts
- Cache frequently used mappings
- Estimated time: 20-40ms for 100 payments
```

#### Acceptance Criteria:
1. GL mapping lookup < 40ms for 100 payments
2. Uses batch load (not loop)
3. Performance test validates
4. Results unchanged

#### Tasks:
1. Create batch GL account loader
2. Implement caching for frequently used accounts
3. Add query indexes
4. Test and validate
5. Create performance test

---

## Requirement 2: Code Refactoring

### REQ-P13-005: AmortizationModel Refactoring

**Requirement Type:** Code Quality / Maintainability  
**Priority:** High  
**Effort Estimate:** 1.5 days  
**Expected Benefit:** Improved clarity, testability, maintainability

#### Current Issues:
```
- Mixed responsibilities (calculation + persistence)
- Tight coupling to DataProvider
- Hard to test calculation logic independently
- Some methods too long
```

#### Desired State:
```
- Separated calculation logic from persistence
- Pure calculation methods (no side effects)
- Independently testable
- Clear method names and documentation
```

#### Acceptance Criteria:
1. Calculation methods are pure (no side effects)
2. Persistence separated to DataProvider layer
3. Each method has single responsibility
4. All methods < 30 lines of code
5. All methods documented with examples
6. Existing tests still passing
7. Code clarity improved (peer review)
8. Performance unchanged or improved

#### Tasks:
1. Extract calculation logic to separate class
2. Make calculation methods pure
3. Remove DataProvider dependencies from calculations
4. Refactor long methods into focused methods
5. Add comprehensive documentation
6. Update existing tests
7. Create new focused tests
8. Code review

---

### REQ-P13-006: DataProvider Interface Standardization

**Requirement Type:** Code Quality / Consistency  
**Priority:** High  
**Effort Estimate:** 1 day  
**Expected Benefit:** Consistent interface across all platforms

#### Current Issues:
```
- Inconsistent method naming (getPortfolio vs fetchPortfolio)
- Mixed read/write concerns
- Inconsistent error handling
- Documentation varies by implementation
```

#### Desired State:
```
- Consistent naming convention across all implementations
- Clear separation of read vs write operations
- Standardized error handling and exceptions
- Consistent documentation
```

#### Acceptance Criteria:
1. Interface methods follow consistent naming (get*, create*, update*, delete*)
2. Clear read vs write operation groups
3. All implementations follow same pattern
4. Standardized exceptions (DataNotFoundException, DataValidationException, etc.)
5. Error handling consistent across FA, WP, SuiteCRM
6. Documentation identical for each implementation
7. All tests passing
8. Backward compatibility maintained

#### Tasks:
1. Review current DataProvider implementations
2. Define standardized interface
3. Document naming conventions
4. Implement error handling standardization
5. Update FA adaptor
6. Update WP adaptor
7. Update SuiteCRM adaptor
8. Create integration tests
9. Update documentation

---

### REQ-P13-007: Platform Adaptor Consistency

**Requirement Type:** Code Quality / Consistency  
**Priority:** High  
**Effort Estimate:** 1.5 days  
**Expected Benefit:** Easier to maintain, less bugs

#### Current Issues:
```
- Different code styles per adaptor
- Inconsistent error handling
- Duplicated utility functions
- Different logging approaches
```

#### Desired State:
```
- Consistent code style across FA, WP, SuiteCRM
- Standardized error handling
- Shared utility functions
- Consistent logging
```

#### Acceptance Criteria:
1. All adaptors follow same code style (PSR-12)
2. Error handling patterns identical
3. Common utilities in shared base class
4. Logging consistent across adaptors
5. Code review shows high consistency
6. All tests passing
7. Documentation standardized
8. Performance unchanged

#### Tasks:
1. Create AdaptorBase class with common functionality
2. Implement consistent error handling
3. Extract common utilities
4. Standardize logging approach
5. Refactor FA adaptor
6. Refactor WP adaptor
7. Refactor SuiteCRM adaptor
8. Code review and cleanup
9. Update tests

---

### REQ-P13-008: Test Infrastructure Enhancement

**Requirement Type:** Code Quality / Development Process  
**Priority:** Medium  
**Effort Estimate:** 0.5 days  
**Expected Benefit:** Faster test creation, better test quality

#### Current Issues:
```
- Test setup varies between files
- Duplicated test fixtures
- Inconsistent test naming
- Common utilities scattered
```

#### Desired State:
```
- Centralized fixture creation
- Consistent test naming convention
- Shared test utility methods
- Enhanced documentation
```

#### Acceptance Criteria:
1. BaseTestCase has common fixture methods
2. Test naming follows consistent pattern (test*SuccessCase, test*FailureCase)
3. Common utilities available to all tests
4. Test documentation clear and consistent
5. New tests 25% faster to create
6. All existing tests still passing
7. Code review shows improved test clarity

#### Tasks:
1. Enhance BaseTestCase with fixture methods
2. Create test naming guidelines
3. Extract common test utilities
4. Standardize test organization
5. Update all test files to follow patterns
6. Create test documentation
7. Code review

---

## Requirement 3: Caching Implementation

### REQ-P13-009: Portfolio Cache Implementation

**Requirement Type:** Performance / Optimization  
**Priority:** High  
**Effort Estimate:** 1.5 days  
**Expected Benefit:** 50-70% faster repeated queries

#### Requirements:
```
What to Cache:  Portfolio aggregates (balance, rate, status)
Cache Duration: 5-10 minutes
Invalidation:   On payment or loan update
Expected Hits:  > 60% on repeated queries
Backend:        In-memory (PHP array), migrate to Redis if needed
```

#### Acceptance Criteria:
1. Portfolio cache hits > 60% on repeated operations
2. Cache invalidates on payment/loan updates
3. TTL of 5-10 minutes or explicit invalidation
4. Cache miss falls back to database query
5. API identical to non-cached version (transparent)
6. Cache size monitored (< 10MB for small dataset)
7. Performance improved 50-70%
8. Tests validate cache behavior

#### Tasks:
1. Design cache interface (interface-based for future migration)
2. Implement in-memory cache provider
3. Integrate portfolio cache into calculations
4. Implement invalidation on updates
5. Add cache metrics (hits, misses, size)
6. Create cache tests
7. Performance validation

---

### REQ-P13-010: Query Result Cache Implementation

**Requirement Type:** Performance / Optimization  
**Priority:** Medium  
**Effort Estimate:** 1 day  
**Expected Benefit:** 30-40% faster reference lookups

#### Requirements:
```
What to Cache:  Reference data (GL accounts, rate types, loan types)
Cache Duration: 1 hour
Invalidation:   Admin-triggered or on change
Expected Hits:  > 70% on repeated operations
Backend:        In-memory cache
```

#### Acceptance Criteria:
1. Reference data cached with 1-hour TTL
2. Cache invalidation triggered by admin
3. Cache hits > 70%
4. Cache size limited (< 5MB)
5. Performance improved 30-40%
6. Fallback to database on cache miss
7. Tests validate cache correctness

#### Tasks:
1. Identify reference data to cache
2. Implement query result caching
3. Add cache invalidation triggers
4. Add cache metrics
5. Create tests
6. Performance validation

---

### REQ-P13-011: Calculation Result Cache Implementation

**Requirement Type:** Performance / Optimization  
**Priority:** Medium  
**Effort Estimate:** 0.5 days  
**Expected Benefit:** 40-50% faster repeated calculations

#### Requirements:
```
What to Cache:  Calculation results (interest, balance)
Duration:       Session or 30 seconds
Invalidation:   On any data change
Expected Hits:  > 50% within session
Backend:        Request-scoped cache
```

#### Acceptance Criteria:
1. Calculation results cached within session
2. Cache clears on data change
3. Cache hits > 50% within session
4. Size limited (< 1MB per session)
5. Performance improved 40-50%
6. Transparent to caller
7. Tests validate behavior

#### Tasks:
1. Implement request-scoped cache
2. Add calculation result caching
3. Implement invalidation on change
4. Add metrics
5. Create tests

---

## Requirement 4: Documentation

### REQ-P13-012: Query Optimization Documentation

**Requirement Type:** Documentation  
**Priority:** Medium  
**Effort Estimate:** 0.5 days

#### Deliverable:
```
Document: PHASE13_QUERY_OPTIMIZATION.md
Contents:
- Before/after query comparisons
- EXPLAIN analysis for each query
- Optimization rationale
- Performance benchmarks
- Indexing strategy
- Code examples
- Maintenance notes
```

---

### REQ-P13-013: Caching Architecture Documentation

**Requirement Type:** Documentation  
**Priority:** Medium  
**Effort Estimate:** 0.5 days

#### Deliverable:
```
Document: PHASE13_CACHING_ARCHITECTURE.md
Contents:
- Cache design overview
- Each cache type (portfolio, query, calculation)
- Invalidation strategies
- Hit rate expectations
- Memory usage estimates
- Configuration options
- Migration to Redis strategy
- Code examples
- Metrics and monitoring
```

---

### REQ-P13-014: Refactoring Guide

**Requirement Type:** Documentation  
**Priority:** Medium  
**Effort Estimate:** 0.5 days

#### Deliverable:
```
Document: PHASE13_REFACTORING_GUIDE.md
Contents:
- Code structure improvements
- Design pattern decisions
- Interface standardization
- Error handling strategy
- Code organization
- Naming conventions
- Code examples before/after
- Testing approach
- Future enhancement ideas
```

---

## Requirement 5: Testing

### REQ-P13-015: Query Optimization Tests

**Requirement Type:** Testing  
**Priority:** High  
**Effort Estimate:** 1 day  
**Test Count:** 8 tests

#### Test Coverage:
```
1. testPortfolioBalanceQueryPerformance
   - Validates optimization improves performance 30%+
   - Checks accuracy remains unchanged

2. testPortfolioBalanceQueryN1Prevention
   - Ensures single query (no N+1)
   - Validates query count

3. testPaymentScheduleQueryPerformance
   - Validates optimization improves 50%+
   - Checks result accuracy

4. testPaymentScheduleQueryBatchLoad
   - Ensures batch load (not loop)
   - Validates query efficiency

5. testCumulativeInterestOptimization
   - Validates calculation improvement 30%+
   - Checks accuracy

6. testGLAccountMappingOptimization
   - Validates GL lookup improvement 20%+
   - Checks correctness

7. testQueryIndexingStrategy
   - Validates indexes created
   - Checks query plans

8. testQueryPerformanceRegressionPrevention
   - Prevents future performance regression
   - Validates baseline metrics
```

---

### REQ-P13-016: Caching Tests

**Requirement Type:** Testing  
**Priority:** High  
**Effort Estimate:** 0.5 days  
**Test Count:** 6 tests

#### Test Coverage:
```
1. testPortfolioCacheHitRate
   - Validates cache hit rate > 60%
   - Checks cache correctness

2. testPortfolioCacheInvalidation
   - Ensures cache invalidates on update
   - Validates timing

3. testQueryResultCacheAccuracy
   - Validates cached results match DB
   - Checks cache miss fallback

4. testCalculationCacheLifecycle
   - Validates cache creation and expiration
   - Checks session isolation

5. testCacheMemoryUsage
   - Validates cache size limits
   - Checks memory efficiency

6. testCacheMetrics
   - Validates hit/miss metrics
   - Checks monitoring
```

---

## Success Criteria for Phase 13

### Performance Targets
```
✓ Query performance improved 30-50%
✓ Cache hit rate > 60% on repeated operations
✓ Response times < 1 second for 95% of operations
✓ No performance regression vs Phase 12
✓ Memory usage < 50MB at peak
```

### Code Quality Targets
```
✓ 723 existing tests still passing
✓ 14 new tests passing (optimization + caching)
✓ Code clarity improved (peer review score 8/10+)
✓ Consistency score 9/10+
✓ Test coverage maintained > 80%
```

### Refactoring Targets
```
✓ AmortizationModel refactored (separated concerns)
✓ DataProvider interface standardized
✓ All platform adaptors follow same patterns
✓ Test infrastructure enhanced
✓ Error handling standardized
✓ Code documentation complete
```

### Documentation Targets
```
✓ Query optimization guide complete
✓ Caching architecture documented
✓ Refactoring decisions documented
✓ Phase 13 completion report written
✓ Deployment guide for small-scale setup
```

---

## Phase 13 Timeline

### Week 1: Query Optimization (5 days)
- Day 1-2: Analysis and planning
- Day 3-4: Implementation and testing
- Day 5: Validation and documentation

### Week 2: Code Refactoring (5 days)
- Day 1-2: AmortizationModel refactoring
- Day 3: DataProvider and adaptor refactoring
- Day 4: Test infrastructure enhancement
- Day 5: Integration and validation

### Week 3: Caching & Documentation (5 days)
- Day 1-2: Caching implementation
- Day 3: Cache testing and optimization
- Day 4-5: Documentation and completion

---

## Estimated Effort

```
Total Effort:           15 days
Query Optimization:     3 days (20%)
Code Refactoring:       5 days (33%)
Caching Implementation: 3 days (20%)
Testing & Documentation: 4 days (27%)
```

---

## Next Steps

1. ✅ Phase 13 strategy document created
2. ✅ Phase 13 detailed requirements created
3. ⏳ Begin code refactoring (Week 1)
4. ⏳ Implement caching (Week 2-3)
5. ⏳ Create deployment guide

**Status:** Ready to proceed to implementation

---

*Phase 13 Detailed Requirements*  
*Optimization for Small-Scale Deployment*  
*Ready for Implementation*
