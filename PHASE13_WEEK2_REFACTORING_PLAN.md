# Phase 13 Week 2: Code Refactoring - Detailed Plan

**Date:** December 17, 2025  
**Duration:** 5 days (Week 2)  
**Focus:** Code quality, maintainability, consistency  
**Target:** 4 refactoring areas with 6 tests planned  

---

## Overview

Week 2 focuses on code refactoring to improve clarity, maintainability, and consistency across the codebase. Unlike Week 1 (query optimization for performance), Week 2 prioritizes code structure and design patterns.

**Key Principle:** Maintain functionality while improving structure and clarity.

---

## Refactoring Areas (4 Total)

### Area 1: AmortizationModel - Separate Concerns

**Current Problem:** Mixed responsibilities  
**Target:** Pure calculation layer separate from persistence

#### Current Issues

```php
// CURRENT: Mixed calculation + persistence
class AmortizationModel {
    private $db; // DataProvider
    
    public function calculateSchedule($principal, $rate, $payments) {
        // 50+ lines of calculation logic
        $schedule = [...];
        
        // Then persists
        foreach ($schedule as $row) {
            $this->db->insertSchedule($row); // Side effect!
        }
        
        return $schedule;
    }
    
    public function calculateExtraPaymentRecalculation($loanId, $amount) {
        $loan = $this->db->getLoan($loanId); // Tight coupling
        $remaining = $this->db->getScheduleRows($loanId); // N+1 pattern
        
        // Complex calculation logic mixed with data fetching
        for ($i = 0; $i < count($remaining); $i++) {
            $row = $remaining[$i];
            // Apply extra payment logic
        }
        
        // Save back
        foreach ($newSchedule as $row) {
            $this->db->updateScheduleRow($row['id'], $row);
        }
    }
}
```

**Issues:**
- Methods do multiple things (calculate + persist)
- Hard to test calculation without database
- DataProvider tightly coupled
- Methods too long (50-100+ lines)
- N+1 query patterns in business logic

#### Target State

```php
// REFACTORED: Separated concerns
class AmortizationCalculator {
    // Pure calculation - no side effects, no DataProvider dependency
    public function generateSchedule($principal, $rate, $paymentFrequency, $numberOfPayments): array
    public function recalculateAfterExtraPayment($schedule, $paymentIndex, $extraAmount): array
    public function calculatePaymentAmount($principal, $rate, $frequency, $payments): float
    // ... all pure calculation methods
}

class AmortizationModel {
    // Orchestration layer - uses calculator + persistence
    private $calculator; // AmortizationCalculator
    private $db;         // DataProvider
    
    public function createLoanSchedule($loanData) {
        $schedule = $this->calculator->generateSchedule(...);
        foreach ($schedule as $row) {
            $this->db->insertSchedule($row);
        }
    }
}
```

**Benefits:**
- ✅ Calculation logic fully testable without database
- ✅ Easy to test edge cases (negative rates, extreme values)
- ✅ Clear separation: calculation vs orchestration
- ✅ Reusable calculator in multiple contexts
- ✅ Methods focused on single task

#### Implementation Tasks

**Task 1.1: Create AmortizationCalculator class**
- Extract pure calculation methods from AmortizationModel
- Move to: `src/Ksfraser/Amortizations/Services/AmortizationCalculator.php`
- Make all methods pure (no side effects)
- No DataProvider dependency

**Task 1.2: Refactor calculateSchedule()**
- Current: 80 lines with persistence
- Target: Two methods
  - `generateSchedule()` - pure calculation, returns array
  - Separate `persistSchedule()` - orchestration layer responsibility
- Remove all `$this->db->` calls from calculation

**Task 1.3: Refactor extra payment recalculation**
- Current: Fetches data, calculates, saves
- Target: Split into:
  - `recalculateAfterExtraPayment()` - pure calculation
  - `applyExtraPaymentAndPersist()` - orchestration
- Remove N+1 query patterns

**Task 1.4: Extract payment calculation methods**
- Methods like `calculatePaymentAmount()`, `calculateInterest()`
- Ensure all pure with clear inputs/outputs
- No DataProvider calls

**Task 1.5: Refactor long methods**
- Find all methods > 30 lines
- Break into focused sub-methods
- Each method does one thing

**Task 1.6: Add comprehensive documentation**
- Document calculation formulas
- Add examples for each method
- Explain parameters and return types

**Task 1.7: Update tests**
- Create `AmortizationCalculatorTest`
- Test pure calculation logic independently
- No mocking needed for calculations
- Update existing `AmortizationModelTest`

**Task 1.8: Validation**
- Run full test suite (should all pass)
- Performance benchmarking (should be same or better)
- Code review for clarity

#### Success Metrics

| Metric | Before | Target | Status |
|--------|--------|--------|--------|
| Avg method length | 50-80 lines | < 30 lines | ⏳ |
| Methods with side effects | 8 | 0 (in calculator) | ⏳ |
| DataProvider dependencies | 15+ | 2 (only in model) | ⏳ |
| Cyclomatic complexity | High | Low | ⏳ |
| Test coverage (calc logic) | 70% | 100% | ⏳ |
| Code clarity score | 6/10 | 9/10 | ⏳ |

---

### Area 2: DataProvider Interface - Standardization

**Current Problem:** Inconsistent naming and patterns  
**Target:** Unified interface across all platforms

#### Current Issues

```php
// CURRENT: Inconsistent naming across implementations

// Front Accounting
$glAccount = $this->db->getGLAccount($code);         // get*
$loan = $this->db->fetchLoan($loanId);               // fetch*
$schedule = $this->db->retrieveScheduleRows($loanId); // retrieve*

// WordPress
$loan = $this->db->getLoan($loanId);                 // get*
$schedule = $this->db->getScheduleRows($loanId);     // get*
$event = $this->db->get_event($eventId);             // get_*

// SuiteCRM
$bean = $this->db->getLoanBean($loanId);             // getBean
$schedule = $this->db->getSchedules($loanId);        // get*
$event = $this->db->retrieve_event($eventId);        // retrieve_*
```

**Issues:**
- `get*`, `fetch*`, `retrieve*` all mean the same thing
- Inconsistent naming makes code confusing
- Different error handling per implementation
- Documentation varies widely
- Unclear which operations are read vs write

#### Target State

```php
// REFACTORED: Consistent interface

interface DataProviderInterface {
    // === CREATE operations ===
    public function createLoan(array $data): int;
    public function createScheduleRow(int $loanId, array $data): int;
    public function createLoanEvent(int $loanId, LoanEvent $event): int;
    
    // === READ operations ===
    public function getLoan(int $loanId): array;
    public function getLoanEvents(int $loanId): array;
    public function getScheduleRows(int $loanId): array;
    public function getScheduleRowsAfterDate(int $loanId, string $date): array;
    
    // === UPDATE operations ===
    public function updateLoan(int $loanId, array $updates): void;
    public function updateScheduleRow(int $scheduleId, array $updates): void;
    
    // === DELETE operations ===
    public function deleteScheduleAfterDate(int $loanId, string $date): void;
}

// All implementations follow same naming:
class FADataProvider implements DataProviderInterface {
    public function getLoan(int $loanId): array { ... }
    public function createLoan(array $data): int { ... }
}

class WPDataProvider implements DataProviderInterface {
    public function getLoan(int $loanId): array { ... }
    public function createLoan(array $data): int { ... }
}

class SuiteCRMDataProvider implements DataProviderInterface {
    public function getLoan(int $loanId): array { ... }
    public function createLoan(array $data): int { ... }
}
```

**Benefits:**
- ✅ Consistent naming across all platforms
- ✅ Clear CRUD operations
- ✅ Easy to predict method names
- ✅ Reduced cognitive load
- ✅ Standardized error handling

#### Implementation Tasks

**Task 2.1: Audit current interface**
- List all current method names
- Map to standardized CRUD pattern
- Identify breaking changes needed

**Task 2.2: Define standardized exceptions**
- Create custom exceptions:
  - `DataNotFoundException` - Record not found
  - `DataValidationException` - Invalid data
  - `DataPersistenceException` - Database error
- Use consistently across all adaptors

**Task 2.3: Standardize error handling**
- Front Accounting: Wrap PDOException → DataPersistenceException
- WordPress: Wrap wpdb errors → DataPersistenceException
- SuiteCRM: Wrap bean errors → DataPersistenceException
- All throw same exceptions for same scenarios

**Task 2.4: Update FA adaptor to standard interface**
- Rename methods to match standard (get*, create*, update*, delete*)
- Update error handling
- Add documentation

**Task 2.5: Update WP adaptor to standard interface**
- Rename methods to match standard
- Update error handling
- Add documentation

**Task 2.6: Update SuiteCRM adaptor to standard interface**
- Rename methods to match standard
- Update error handling
- Add documentation

**Task 2.7: Create integration tests**
- Test each adaptor independently
- Verify all return same structure
- Verify all throw same exceptions

**Task 2.8: Backwards compatibility check**
- Search codebase for method name usage
- Update all call sites
- Verify no breaking changes outside adaptors

#### Success Metrics

| Metric | Before | Target | Status |
|--------|--------|--------|--------|
| Method naming consistency | 40% | 100% | ⏳ |
| Standardized exceptions | 0 | 3 types | ⏳ |
| Error handling consistency | 30% | 100% | ⏳ |
| Documentation completeness | 60% | 100% | ⏳ |
| All tests passing | ✓ | ✓ | ⏳ |

---

### Area 3: Platform Adaptors - Consistency

**Current Problem:** Different styles, duplicated logic  
**Target:** Unified adaptor base class with consistent patterns

#### Current Issues

```php
// CURRENT: Duplicated error handling logic

// Front Accounting style
public function getLoan($id) {
    try {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        if (!$result) {
            throw new Exception("Loan not found");
        }
        return $result;
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage());
    }
}

// WordPress style (different pattern)
public function getLoan($id) {
    $row = $this->wpdb->get_row($this->wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d", $id
    ), ARRAY_A);
    
    if (!$row) {
        throw new Exception("Not found");
    }
    
    return $row;
}

// SuiteCRM style (yet another pattern)
public function getLoan($id) {
    $bean = BeanFactory::getBean('Loan', $id);
    if (!$bean) {
        throw new Exception("Loan not found");
    }
    return $bean->toArray();
}
```

**Issues:**
- Error handling logic repeated 3 times
- Different error messages for same error
- Inconsistent null/not-found handling
- Code review hard due to style differences

#### Target State

```php
// REFACTORED: Base class with shared logic

abstract class DataProviderAdaptor implements DataProviderInterface {
    protected function throwNotFound(string $resource, $id) {
        throw new DataNotFoundException("$resource not found: $id");
    }
    
    protected function throwValidationError(string $field, string $reason) {
        throw new DataValidationException("$field: $reason");
    }
    
    protected function throwPersistenceError(string $operation, Throwable $cause) {
        throw new DataPersistenceException("$operation failed: " . $cause->getMessage());
    }
    
    // Common logging
    protected function log(string $level, string $message) {
        error_log("[$level] AmortizationDB: $message");
    }
}

// Platform-specific implementations
class FADataProvider extends DataProviderAdaptor {
    public function getLoan(int $id): array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM loans WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                $this->throwNotFound('Loan', $id);
            }
            
            return $result;
        } catch (PDOException $e) {
            $this->throwPersistenceError('getLoan', $e);
        }
    }
}

class WPDataProvider extends DataProviderAdaptor {
    public function getLoan(int $id): array {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->wpdb->prefix}loans WHERE id = %d", $id),
            ARRAY_A
        );
        
        if (!$result) {
            $this->throwNotFound('Loan', $id);
        }
        
        return $result;
    }
}
```

**Benefits:**
- ✅ Duplicated logic removed
- ✅ Consistent error handling
- ✅ Consistent logging
- ✅ Easier to maintain
- ✅ Easier to review

#### Implementation Tasks

**Task 3.1: Create DataProviderAdaptor base class**
- File: `src/Ksfraser/Amortizations/DataProviderAdaptor.php`
- Implement error handling methods
- Implement logging methods
- Include validation utilities

**Task 3.2: Extract common patterns**
- Identify error handling patterns
- Identify logging patterns
- Create shared methods

**Task 3.3: Update FA adaptor**
- Extend DataProviderAdaptor
- Use shared error methods
- Use shared logging
- Remove duplicated logic

**Task 3.4: Update WP adaptor**
- Extend DataProviderAdaptor
- Use shared error methods
- Use shared logging
- Remove duplicated logic

**Task 3.5: Update SuiteCRM adaptor**
- Extend DataProviderAdaptor
- Use shared error methods
- Use shared logging
- Remove duplicated logic

**Task 3.6: Code style standardization**
- Apply PSR-12 consistently
- Standardize method organization
- Consistent spacing and formatting

**Task 3.7: Documentation standardization**
- Document all public methods
- Include error conditions
- Include usage examples

**Task 3.8: Validation testing**
- Verify all tests still pass
- Verify error messages consistent
- Verify logging working

#### Success Metrics

| Metric | Before | Target | Status |
|--------|--------|--------|--------|
| Duplicated error handling | 20+ lines | 0 | ⏳ |
| Logging consistency | 40% | 100% | ⏳ |
| Code style consistency | 50% | 100% | ⏳ |
| Lines of code (adaptors) | 600+ | 500 | ⏳ |
| Test coverage | 85% | 95% | ⏳ |

---

### Area 4: Test Infrastructure - Enhancement

**Current Problem:** Duplicated test setup, inconsistent patterns  
**Target:** Centralized fixtures and consistent test structure

#### Current Issues

```php
// CURRENT: Duplicated setup across test files

class AmortizationModelTest extends TestCase {
    private $mockDb;
    private $model;
    
    protected function setUp(): void {
        $this->mockDb = $this->createMock(DataProviderInterface::class);
        $this->model = new AmortizationModel($this->mockDb);
    }
    
    private function createTestLoan() {
        return [
            'id' => 1,
            'principal' => 100000,
            'rate' => 5.0,
            'months' => 360
        ];
    }
}

// Same fixture duplicated in another test file
class FADataProviderTest extends TestCase {
    private $mockDb;
    
    protected function setUp(): void {
        $this->mockDb = $this->createMock(DataProviderInterface::class);
    }
    
    private function createTestLoan() {
        return [
            'id' => 1,
            'principal' => 100000,
            'rate' => 5.0,
            'months' => 360
        ];
    }
}
```

**Issues:**
- `createTestLoan()` duplicated 5+ times
- Different fixture structure per test
- Hard to maintain fixture data
- Inconsistent test naming

#### Target State

```php
// REFACTORED: Centralized fixtures

class TestFixtures {
    public static function createTestLoan(array $overrides = []): array {
        $defaults = [
            'id' => 1,
            'principal' => 100000.00,
            'rate' => 5.0,
            'months' => 360
        ];
        return array_merge($defaults, $overrides);
    }
    
    public static function createTestSchedule(array $overrides = []): array {
        $defaults = [
            'payment_number' => 1,
            'payment_date' => '2025-12-01',
            'payment_amount' => 536.82,
            'principal_payment' => 90.82,
            'interest_payment' => 446.00,
            'balance' => 99909.18
        ];
        return array_merge($defaults, $overrides);
    }
}

// Usage in tests
class AmortizationModelTest extends TestCase {
    public function testCalculatePayment() {
        $loan = TestFixtures::createTestLoan(['months' => 180]);
        $this->model->calculatePayment($loan);
    }
}
```

**Benefits:**
- ✅ No fixture duplication
- ✅ Easy to update fixtures
- ✅ Consistent test data
- ✅ Faster test creation

#### Implementation Tasks

**Task 4.1: Create TestFixtures class**
- File: `tests/TestFixtures.php`
- Add `createTestLoan()` with overrides
- Add `createTestSchedule()` with overrides
- Add `createTestEvent()` with overrides

**Task 4.2: Create BaseTestCase enhancement**
- Add fixture helper methods
- Add common mocks (DataProvider, Cache)
- Add assertion helpers

**Task 4.3: Standardize test naming**
- Use `test<Method><Scenario>` pattern
- Document naming convention
- Update all existing tests

**Task 4.4: Update all test files**
- Remove duplicated fixtures
- Use TestFixtures class
- Update to use BaseTestCase enhancements

**Task 4.5: Add helper assertions**
- `assertLoanStructure()`
- `assertScheduleStructure()`
- `assertEventStructure()`

#### Success Metrics

| Metric | Before | Target | Status |
|--------|--------|--------|--------|
| Duplicated fixture code | 200+ lines | 0 | ⏳ |
| Test setup time | 10 min | 5 min | ⏳ |
| Test consistency | 70% | 100% | ⏳ |
| New test creation time | 20 min | 10 min | ⏳ |

---

## Implementation Schedule

### Day 1-2: AmortizationModel Refactoring
- Hours 1-2: Create AmortizationCalculator class
- Hours 3-4: Extract calculation methods
- Hours 5-8: Refactor long methods
- Hours 9-12: Create tests
- Hours 13-16: Code review and cleanup

### Day 3: DataProvider Interface Standardization
- Hours 1-2: Audit current interface
- Hours 3-4: Define standard exceptions
- Hours 5-8: Update all 3 adaptors
- Hours 9-12: Create integration tests
- Hours 13-16: Backwards compatibility check

### Day 4: Platform Adaptors Consistency
- Hours 1-2: Create DataProviderAdaptor base class
- Hours 3-6: Update FA adaptor
- Hours 7-10: Update WP adaptor
- Hours 11-14: Update SuiteCRM adaptor
- Hours 15-16: Validation and testing

### Day 5: Test Infrastructure & Wrap-up
- Hours 1-2: Create TestFixtures class
- Hours 3-4: Update BaseTestCase
- Hours 5-10: Update all test files
- Hours 11-14: Full validation
- Hours 15-16: Documentation and summary

---

## Before & After Comparison

### Code Metrics

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Total lines of code | 5200 | 5000 | -4% |
| Avg method length | 45 | 20 | 55% |
| Cyclomatic complexity | 8 | 4 | 50% |
| Code duplication | 15% | 5% | 67% |
| Test setup time | 15 min | 7 min | 53% |
| Code clarity (1-10) | 6 | 9 | 50% |

### Quality Metrics

| Metric | Before | After |
|--------|--------|-------|
| DataProvider method consistency | 40% | 100% |
| Error handling consistency | 30% | 100% |
| Documentation completeness | 70% | 100% |
| Test fixture duplication | High | Zero |
| Calculation testability | 70% | 100% |

---

## Testing Strategy

### Unit Tests
- **AmortizationCalculatorTest:** Pure calculation methods
- **DataProviderTest:** Each adaptor independently
- **AdaptorTest:** Base class shared functionality

### Integration Tests
- **DataProviderIntegrationTest:** All 3 adaptors together
- **AmortizationModelTest:** Calculator + persistence flow

### Regression Tests
- Run all 723 existing tests
- Target: 100% passing
- Monitor performance (should be same or better)

### Metrics Tests
- Code coverage: Target 95%+
- Complexity: Target avg 3-4
- Documentation: 100% of public methods

---

## Success Criteria

✅ **Code Quality**
- All 723 existing tests pass
- Code coverage >= 95%
- Cyclomatic complexity < 5
- All methods < 30 lines

✅ **Consistency**
- DataProvider methods 100% consistent naming
- All error handling standardized
- All adaptors use base class
- All test fixtures centralized

✅ **Maintainability**
- Code review score >= 8/10
- Clear separation of concerns
- No duplicated logic
- Comprehensive documentation

✅ **Performance**
- No performance degradation
- Query optimization preserved from Week 1
- Response times maintained

---

## Risk Mitigation

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|-----------|
| Breaking changes | Medium | High | Comprehensive testing + backwards compat check |
| Performance regression | Low | High | Performance testing + benchmarking |
| Incomplete refactoring | Low | Medium | Clear task checklist + daily progress tracking |
| Test failures | Medium | Medium | Incremental refactoring + test-driven approach |

---

## Deliverables

### Code Changes
- ✅ AmortizationCalculator class (pure calculation)
- ✅ DataProviderAdaptor base class
- ✅ Updated FA adaptor (standardized)
- ✅ Updated WP adaptor (standardized)
- ✅ Updated SuiteCRM adaptor (standardized)
- ✅ TestFixtures class
- ✅ Enhanced BaseTestCase

### Documentation
- ✅ Refactoring guide (why each change)
- ✅ Code style guide (PSR-12 + standards)
- ✅ DataProvider interface documentation
- ✅ Testing patterns documentation
- ✅ Architecture diagram updates

### Tests
- ✅ 6 new test cases for calculators
- ✅ Updated integration tests
- ✅ All 723 existing tests passing
- ✅ 95%+ code coverage

### Commits
- Commit 1: AmortizationModel refactoring
- Commit 2: DataProvider standardization + base class
- Commit 3: Adaptor consistency + test infrastructure
- Commit 4: Week 2 completion report

---

## Next Steps (Week 3)

After Week 2 refactoring:
1. **Caching Implementation** (Week 3)
   - Implement 3 cache types (portfolio, query result, calculation)
   - Configure appropriate TTLs
   - Validation and testing

2. **Production Deployment** (Phase 14)
   - Execute deployment guide
   - Monitor performance
   - Collect metrics

3. **Final Release** (Phase 15)
   - Integration testing
   - UAT scenarios
   - Release procedures

---

## Summary

Phase 13 Week 2 will deliver **comprehensive code refactoring** addressing 4 key areas:

1. **Separation of Concerns** - Pure calculation logic separated from persistence
2. **Interface Consistency** - Standardized DataProvider across all platforms
3. **Adaptor Unification** - Shared base class eliminates duplication
4. **Test Infrastructure** - Centralized fixtures improve test velocity

**Total Expected Impact:** 50-60% improvement in code clarity, 67% reduction in duplication, 55% reduction in method complexity.

All work maintains **backward compatibility** and preserves **Week 1 performance optimizations**.

