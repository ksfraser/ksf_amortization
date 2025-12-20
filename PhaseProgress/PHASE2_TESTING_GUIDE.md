# Phase 2-4 Testing Strategy & Framework

**Focus:** Comprehensive testing approach for enhanced amortization system  
**Document Version:** 1.0  
**Date:** December 11, 2025  
**Coverage Target:** >85% for critical paths, >80% overall

---

## Table of Contents

1. [Testing Philosophy](#testing-philosophy)
2. [Test Pyramid Structure](#test-pyramid-structure)
3. [TDD Workflow](#tdd-workflow)
4. [Unit Testing Guidelines](#unit-testing-guidelines)
5. [Integration Testing Guidelines](#integration-testing-guidelines)
6. [UAT Scenarios](#uat-scenarios)
7. [Coverage Requirements](#coverage-requirements)
8. [Test Execution & CI/CD](#test-execution--cicd)
9. [Common Testing Patterns](#common-testing-patterns)
10. [Debugging & Troubleshooting](#debugging--troubleshooting)

---

## Testing Philosophy

### Core Principles

1. **Test-Driven Development (TDD)**
   - Write failing test first
   - Implement minimum code to pass
   - Refactor for quality

2. **Test Independence**
   - Each test should run standalone
   - No shared state between tests
   - Mock external dependencies

3. **Test Clarity**
   - Test name describes expected behavior
   - AAA pattern: Arrange, Act, Assert
   - One assertion per test (ideally)

4. **Test Maintenance**
   - Keep tests as simple as production code
   - Refactor tests when implementation changes
   - Remove redundant/overlapping tests

### Quality Gates

```
Phase 2 Features:
├─ Unit Tests: REQUIRED (>85% coverage)
├─ Integration Tests: REQUIRED (critical paths)
├─ UAT Scripts: REQUIRED (documented, executable)
├─ Performance: REQUIRED (<10ms per calculation)
└─ Security: REQUIRED (no critical/high issues)

Phase 3 Features:
├─ Unit Tests: REQUIRED (>85% coverage)
├─ Integration Tests: REQUIRED (complex workflows)
├─ UAT Scripts: REQUIRED (multi-feature scenarios)
└─ Regression: REQUIRED (Phase 2 still passing)

Phase 4 Features:
├─ Unit Tests: REQUIRED (>80% coverage)
├─ Integration Tests: REQUIRED (data consistency)
└─ UAT Scripts: REQUIRED (output validation)
```

---

## Test Pyramid Structure

```
                    /\
                   /  \
                  / E2E \               ← Few (UAT/Live)
                 /--------\
                /          \
               / Integration \        ← Some (cross-component)
              /----------------\
             /                  \
            / Unit Tests (Many)  \   ← Most (individual methods)
           /------------------------\
          /_________________________ \
```

### Test Coverage by Feature

#### Unit Tests (Foundation)

**Per Feature** (~60% of test effort):
- Individual method behavior
- Edge cases (boundaries, nulls, zero values)
- Error conditions and exceptions
- Rounding and precision
- Date calculations

**Example: BalloonPaymentStrategy**
```
Unit Tests (12 tests, 40 minutes development)
├─ Supports loans with balloon: 1 test
├─ Rejects loans without balloon: 1 test
├─ Calculates correct payment: 1 test
├─ Final payment includes balloon: 1 test
├─ Schedule balance ends at $0: 1 test
├─ Balloon percentage calculation: 1 test
├─ Edge case: balloon > principal: 1 test
├─ Edge case: 0% interest rate: 1 test
├─ Edge case: 1 payment only: 1 test
├─ Total payments correct: 1 test
├─ Rounding consistency: 1 test
└─ Large values handling: 1 test
```

#### Integration Tests (Workflow)

**Per Feature** (~25% of test effort):
- Feature interaction with other components
- Database persistence and retrieval
- Event handler triggering recalculation
- End-to-end scenarios

**Example: Variable Rate Integration**
```
Integration Tests (4 tests, 20 minutes development)
├─ Create loan with rate periods: 1 test
├─ Schedule generated correctly: 1 test
├─ Rate change triggers recalculation: 1 test
└─ Payment adjustment at rate reset: 1 test
```

#### UAT Scripts (Acceptance)

**Per Feature** (~15% of test effort):
- Real-world scenarios
- Manual or automated
- Documented step-by-step

**Example: Balloon Payment UAT**
```
UAT Scripts (2 scenarios, 10 minutes execution each)
├─ Auto lease with $12k balloon
└─ Mortgage with 20% balloon payment
```

---

## TDD Workflow

### The Red-Green-Refactor Cycle

```
1. RED: Write failing test
   └─ Test expresses desired behavior
   └─ Test fails because implementation missing

2. GREEN: Implement minimum code
   └─ Just enough to pass the test
   └─ May be "hacky" or incomplete
   └─ All tests pass

3. REFACTOR: Improve code quality
   └─ Maintain passing tests
   └─ Apply design patterns
   └─ Optimize for readability/performance
   └─ Extract common code
```

### Example: BalloonPaymentStrategy

**STEP 1: RED - Write failing test**

```php
/**
 * @test
 * @group balloon
 */
public function testFinalPaymentEqualsBalloonAmount(): void
{
    // This test fails because BalloonPaymentStrategy doesn't exist yet
    
    $loan = new Loan([
        'principal' => 100000,
        'rate' => 5.0,
        'periods' => 120,
        'balloon_amount' => 25000,
    ]);

    $strategy = new BalloonPaymentStrategy(new DateHelper());
    $schedule = $strategy->calculateSchedule($loan);
    
    $lastRow = end($schedule);
    
    $this->assertEquals(25000, $lastRow->getPrincipalPortion());
    $this->assertEquals(0, $lastRow->getRemainingBalance(), '', 0.02);
}
```

Output: **FAIL** - Class not found

---

**STEP 2: GREEN - Create class (minimum)**

```php
<?php
namespace Ksfraser\Amortizations\Strategies;

use Ksfraser\Amortizations\Models\Loan;

class BalloonPaymentStrategy implements LoanCalculationStrategy
{
    public function supports(Loan $loan): bool
    {
        return $loan->hasBalloonPayment();
    }

    public function calculatePayment(Loan $loan): float
    {
        // Placeholder - just enough to create object
        return 0;
    }

    public function calculateSchedule(Loan $loan): array
    {
        // Return minimal schedule
        return [];
    }
}
```

Output: **FAIL** - Assertions don't match (empty schedule)

---

**STEP 3: GREEN - Implement real algorithm**

```php
public function calculateSchedule(Loan $loan): array
{
    $schedule = [];
    $payment = $this->calculatePayment($loan);
    $balance = $loan->getPrincipal();
    $date = $this->dateHelper->parseDate($loan->getFirstPaymentDate());
    $balloonAmount = $loan->getBalloonAmount();
    $rate = $loan->getAnnualRate() / 100 / 12; // Monthly

    for ($i = 1; $i <= $loan->getNumberOfPayments(); $i++) {
        $interest = round($balance * $rate, 2);
        
        if ($i === $loan->getNumberOfPayments()) {
            // Final payment: balloon + remaining interest
            $principal = $balance;
            $balance = 0;
        } else {
            // Regular payment
            $principal = $payment - $interest;
            $balance = round($balance - $principal, 2);
        }

        $row = new ScheduleRow([
            'payment_date' => $date->format('Y-m-d'),
            'payment_number' => $i,
            'payment_amount' => $principal + $interest,
            'interest_portion' => $interest,
            'principal_portion' => $principal,
            'remaining_balance' => max(0, $balance),
        ]);

        $schedule[] = $row;
        $date = $this->dateHelper->addPeriod($date, $loan->getPaymentFrequency());
    }

    return $schedule;
}
```

Output: **PASS** ✅

---

**STEP 4: REFACTOR - Improve design**

```php
/**
 * BalloonPaymentStrategy
 *
 * Calculates payment schedules for partially-amortized loans
 * with large balloon payment at end.
 *
 * Algorithm:
 * 1. Calculate amortizable principal = total principal - balloon
 * 2. Use standard amortization formula for (principal - balloon)
 * 3. Generate schedule up to final payment
 * 4. Final payment = remaining balance + balloon
 *
 * @implements LoanCalculationStrategy
 * @since 2.0.0
 */
class BalloonPaymentStrategy implements LoanCalculationStrategy
{
    private $dateHelper;
    private $calculator; // Extract calculation logic

    public function __construct(DateHelper $dateHelper, ?PaymentCalculator $calculator = null)
    {
        $this->dateHelper = $dateHelper;
        $this->calculator = $calculator ?? new StandardPaymentCalculator();
    }

    public function calculateSchedule(Loan $loan): array
    {
        $this->validateLoan($loan);
        
        $schedule = [];
        $balance = $loan->getPrincipal();
        $payment = $this->calculatePayment($loan);
        
        for ($i = 1; $i <= $loan->getNumberOfPayments(); $i++) {
            $row = $this->buildScheduleRow($loan, $balance, $payment, $i);
            $schedule[] = $row;
            $balance = $row->getRemainingBalance();
        }

        return $schedule;
    }

    private function validateLoan(Loan $loan): void
    {
        if (!$this->supports($loan)) {
            throw new InvalidArgumentException('Loan does not support balloon payments');
        }
    }

    private function buildScheduleRow(Loan $loan, float $balance, float $payment, int $paymentNum): ScheduleRow
    {
        // Extracted method for clarity
        // ...
    }
}
```

Output: **PASS** ✅ (tests still pass after refactoring)

---

## Unit Testing Guidelines

### Naming Convention

**Test Method Names** - Describe what's being tested and expected result:

```php
// ✅ GOOD - Clear intent
public function testCalculatesFinalPaymentAsRemainingBalancePlusBalloon(): void { }

// ❌ BAD - Too vague
public function testCalculation(): void { }

// ✅ GOOD - Specific condition
public function testThrowsExceptionWhenBalloonGreaterThanPrincipal(): void { }

// ❌ BAD - Unclear
public function testException(): void { }

// ✅ GOOD - Edge case documented
public function testCalculatesCorrectlyWithZeroInterestRate(): void { }
```

### AAA Pattern

All tests follow Arrange-Act-Assert:

```php
/**
 * @test
 * Test that balloon payment strategy supports loans with balloon configuration
 */
public function testSupportsLoansWithBalloonPayment(): void
{
    // ARRANGE: Set up test data
    $loan = new Loan([
        'principal' => 100000,
        'rate' => 5.0,
        'periods' => 120,
        'balloon_amount' => 20000,
    ]);

    // ACT: Execute behavior being tested
    $strategy = new BalloonPaymentStrategy(new DateHelper());
    $result = $strategy->supports($loan);

    // ASSERT: Verify expected outcome
    $this->assertTrue($result, 'Strategy should support loan with balloon');
}
```

### Common Assertions

```php
// Value assertions
$this->assertEquals($expected, $actual);
$this->assertNotEquals($notExpected, $actual);
$this->assertSame($expected, $actual); // Strict comparison
$this->assertNotSame($notExpected, $actual);

// Numeric assertions
$this->assertGreaterThan(0, $value);
$this->assertLessThan(100, $value);
$this->assertGreaterThanOrEqual(0, $value);

// Float assertions (with tolerance for rounding)
$this->assertAlmostEquals(100.00, $actual, 0.01); // ±$0.01 tolerance
$this->assertAlmostEquals(666.79, $payment, 0.1);

// Array assertions
$this->assertCount(120, $schedule); // 120 payments
$this->assertContains($expectedRow, $schedule);
$this->assertArrayHasKey('payment_date', $row);

// Object/Class assertions
$this->assertInstanceOf(ScheduleRow::class, $row);
$this->assertIsArray($schedule);
$this->assertIsFloat($payment);

// Exception assertions
$this->expectException(InvalidArgumentException::class);
$this->expectExceptionMessage('Balloon amount must be positive');
$strategy->calculatePayment($invalidLoan);

// Boolean assertions
$this->assertTrue($condition);
$this->assertFalse($condition);
$this->assertNull($value);
$this->assertNotNull($value);
```

### Mocking & Isolation

Use mocks to isolate the unit being tested:

```php
public function testRecalculatesScheduleAfterExtraPayment(): void
{
    // Arrange: Create mocks for dependencies
    $scheduleRepoMock = $this->createMock(ScheduleRepository::class);
    $loanRepoMock = $this->createMock(LoanRepository::class);

    $scheduleRepoMock->expects($this->once())
        ->method('deleteScheduleAfter')
        ->with(1, '2025-02-01')
        ->willReturn(100);

    $scheduleRepoMock->expects($this->once())
        ->method('saveScheduleRows')
        ->with($this->equalTo(1), $this->isType('array'));

    $model = new AmortizationModel($loanRepoMock, $scheduleRepoMock);

    // Act: Execute method that uses mocks
    $loan = new Loan(['id' => 1]);
    $event = new LoanEvent(['amount' => 5000]);
    
    $model->handleEvent($loan, $event);

    // Assert: Verify mocks were called correctly
    // (Expectations set above are verified automatically)
}
```

### Parametrized Tests

Test multiple scenarios with same logic:

```php
/**
 * @test
 * @dataProvider frequencyProvider
 */
public function testCalculatePaymentForDifferentFrequencies(
    string $frequency,
    float $expectedPayment
): void {
    $loan = new Loan([
        'principal' => 100000,
        'rate' => 5.0,
        'periods' => 60,
        'frequency' => $frequency,
    ]);

    $strategy = new StandardAmortizationStrategy();
    $payment = $strategy->calculatePayment($loan);

    $this->assertAlmostEquals($expectedPayment, $payment, 0.1);
}

public function frequencyProvider(): array
{
    return [
        ['monthly', 1887.12],      // Standard
        ['bi-weekly', 869.57],     // Every 2 weeks
        ['weekly', 434.78],        // Every week
        ['daily', 62.11],          // Daily
    ];
}
```

### Edge Case Testing

Always test boundaries and special values:

```php
/**
 * Edge Cases for Balloon Payment Strategy
 */

// Zero interest rate
public function testCalculatesCorrectlyWithZeroRate(): void { }

// Balloon equals entire principal (all deferred)
public function testCalculatesWhenBalloonEqualsPrincipal(): void { }

// Balloon is 1% (minimal)
public function testCalculatesWithMinimalBalloon(): void { }

// Single payment loan
public function testCalculatesFor1PaymentLoan(): void { }

// Very high interest (>20%)
public function testCalculatesWithHighInterestRate(): void { }

// Very large principal ($1M+)
public function testCalculatesLargeLoans(): void { }

// Balloon > 50% of principal (aggressive)
public function testCalculatesWithLargeBalloon(): void { }
```

---

## Integration Testing Guidelines

### Test Scope

Integration tests verify multiple components working together:

```php
/**
 * Integration test: Full balloon payment workflow
 *
 * Tests: AmortizationModel + BalloonPaymentStrategy + Schedule Repository
 * Verifies: Data flows correctly from model to strategy to persistence
 */
public function testBalloonPaymentWorkflow(): void
{
    // Arrange
    $dataProvider = new FADataProvider($pdoConnection); // Real DB
    $strategyFactory = new StrategyFactory();
    $model = new AmortizationModel($dataProvider, $dataProvider, $strategyFactory);

    $loan = new Loan([
        'id' => 999,
        'principal' => 50000,
        'rate' => 4.5,
        'periods' => 60,
        'balloon_amount' => 10000,
    ]);

    // Act
    $schedule = $model->calculateSchedule($loan);
    $dataProvider->saveSchedule($loan->getId(), $schedule);

    // Assert
    $retrieved = $dataProvider->getScheduleRows($loan->getId());
    
    $this->assertCount(60, $retrieved);
    
    $lastRow = end($retrieved);
    $this->assertEquals(10000, $lastRow->getPrincipalPortion());
    $this->assertEquals(0, $lastRow->getRemainingBalance(), '', 0.02);

    // Cleanup
    $dataProvider->deleteSchedule($loan->getId());
}
```

### Common Integration Test Scenarios

```
Variable Rate Integration:
├─ Create loan with 2 rate periods
├─ Generate schedule
├─ Verify rates applied correctly
└─ Verify payment recalculation at rate reset

Partial Payment Integration:
├─ Create loan
├─ Record partial payment event
├─ Verify arrears created
├─ Verify balance tracking
└─ Verify GL posting reflects arrears

Recalculation Integration:
├─ Create initial schedule
├─ Record extra payment event
├─ Verify schedule updated
├─ Verify GL entries reversed/recreated
└─ Verify final balance still $0

Multi-Feature Integration:
├─ Create balloon loan with variable rate
├─ Record partial payment in period 1
├─ Record rate change in period 2
├─ Record extra payment in period 3
├─ Verify final schedule accurate
└─ Verify all events processed correctly
```

---

## UAT Scenarios

### Structure

Each UAT scenario documents:
- Scenario name and ID
- Business context
- Given (setup)
- When (actions)
- Then (expected results)
- Verification steps

### Example: Balloon Payment UAT

```markdown
# UAT-BALLOON-001: Auto Lease with Balloon Payment

**Scenario:** Customer finances auto lease with balloon payment

**Given:**
- Vehicle value: $35,000
- Lease term: 36 months
- Interest rate: 3.9% APR
- Residual (balloon): $12,500 (35.7% of value)
- Payment frequency: Monthly

**When:**
- Dealer enters loan details into system
- System calculates payment schedule
- Dealer reviews payment amount and schedule

**Then:**
- Monthly payment ≈ $636
- First 35 payments: $636 each
- Final (month 36): $636 + $12,500 (balloon) = $13,136
- Total interest: ≈ $1,396
- Total paid: $35,000 (principal) + $1,396 (interest)

**Verification Steps:**
1. ✓ Verify monthly payment shown in UI as $636
2. ✓ Verify schedule shows 36 rows
3. ✓ Verify last row shows $12,500 balloon + interest
4. ✓ Export to PDF and verify formatting
5. ✓ Verify GL accounts created (Liability, Interest Expense, Cash)
```

### Phase 2 UAT Scripts (Per Feature)

**FE-001: Balloon Payments**
- [ ] UAT-BALLOON-001: Auto lease (above)
- [ ] UAT-BALLOON-002: Mortgage with 20% balloon
- [ ] UAT-BALLOON-003: Business equipment with residual value
- [ ] UAT-BALLOON-004: Verify GL posting for balloon component

**FE-002: Variable Interest Rates**
- [ ] UAT-VARRATE-001: 5/1 ARM (fixed 5 years, then index)
- [ ] UAT-VARRATE-002: Rate reset within loan term
- [ ] UAT-VARRATE-003: Multiple rate periods (3/3/3 pattern)
- [ ] UAT-VARRATE-004: Payment recalculation verification

**FE-003: Partial Payments**
- [ ] UAT-PARTIAL-001: Single partial payment
- [ ] UAT-PARTIAL-002: Three consecutive partial payments
- [ ] UAT-PARTIAL-003: Partial payment + late fee
- [ ] UAT-PARTIAL-004: Arrears interest accrual

---

## Coverage Requirements

### Coverage Targets by Component

| Component | Phase 2 Target | Phase 3+ Target |
|-----------|:---:|:---:|
| Calculation Strategies | 90% | 90% |
| Event Handlers | 85% | 85% |
| Models/Domain | 95% | 95% |
| Repositories | 80% | 80% |
| Services | 85% | 85% |
| **Overall** | **>85%** | **>85%** |

### Coverage Measurement

```bash
# Generate coverage report
composer test -- --coverage-html reports/

# Check specific file coverage
composer test -- --coverage-text src/Ksfraser/Amortizations/Strategies/BalloonPaymentStrategy.php

# Fail build if coverage drops below threshold
composer test -- --coverage-clover clover.xml
# CI/CD checks: coverage < 85% = build fails
```

### Coverage Types

1. **Line Coverage:** % of executable lines executed
2. **Method Coverage:** % of methods called
3. **Class Coverage:** % of classes instantiated
4. **Branch Coverage:** % of conditional branches taken

### Acceptable Exclusions

```php
// Exclude from coverage where appropriate
// @codeCoverageIgnore

// Don't test getters/setters
/** @codeCoverageIgnore */
public function getId() { return $this->id; }

// Don't test simple initialization
/** @codeCoverageIgnore */
public function __construct() { /* ... */ }

// Don't test deprecated code
/** @deprecated Use newMethod() instead */
/** @codeCoverageIgnore */
public function oldMethod() { /* ... */ }
```

---

## Test Execution & CI/CD

### Local Testing

```bash
# Run all tests
composer test

# Run with verbosity
composer test -- --verbose

# Stop on first failure
composer test -- --stop-on-failure

# Run only fast tests
composer test -- --group unit

# Exclude slow tests
composer test -- --exclude-group integration

# Watch mode (run tests on file change)
composer test -- --watch
```

### CI/CD Pipeline

```yaml
# .github/workflows/test.yml (or equivalent for your CI/CD)

name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP 7.3
        uses: shivammathur/setup-php@v2
        with:
          php-version: 7.3
          
      - name: Install dependencies
        run: composer install
        
      - name: Run tests
        run: composer test
        
      - name: Check coverage
        run: composer test -- --coverage-clover coverage.xml
        
      - name: Upload coverage
        uses: codecov/codecov-action@v2
```

### Test Timing

Target test execution times:

```
Unit Tests: <5 seconds (fast feedback)
Integration Tests: <15 seconds (reasonable wait)
Full Suite: <20 seconds (CI/CD gate)

Per Feature (Week of development):
├─ 10-12 unit tests: 2-3 seconds
├─ 4-5 integration tests: 5-10 seconds
└─ Total: <15 seconds
```

---

## Common Testing Patterns

### Arranging Complex Loan Objects

```php
// Helper to create test loans
private function createTestLoan(array $overrides = []): Loan
{
    $defaults = [
        'id' => 1,
        'principal' => 100000,
        'rate' => 5.0,
        'periods' => 120,
        'frequency' => 'monthly',
        'first_payment_date' => '2025-02-01',
    ];

    return new Loan(array_merge($defaults, $overrides));
}

// Usage
$balloonLoan = $this->createTestLoan([
    'balloon_amount' => 20000,
]);

$variableRateLoan = $this->createTestLoan([
    'rate_periods' => [
        new RatePeriod(['start_date' => '2025-02-01', 'rate' => 4.5]),
        new RatePeriod(['start_date' => '2027-02-01', 'rate' => 5.5]),
    ],
]);
```

### Testing Rounding Precision

```php
// Test payment calculation within tolerance
$payment = $strategy->calculatePayment($loan);
$this->assertAlmostEquals(666.79, $payment, 0.01);

// Test schedule ends with $0
$lastRow = end($schedule);
$this->assertEquals(0, $lastRow->getRemainingBalance(), '', 0.02);

// Test cumulative precision
$totalPrincipal = array_reduce($schedule, 
    fn($carry, $row) => $carry + $row->getPrincipalPortion(), 0
);
$this->assertAlmostEquals($loan->getPrincipal(), $totalPrincipal, 0.05);
```

### Testing Date Calculations

```php
// Mock DateHelper for predictable behavior
$dateHelperMock = $this->createMock(DateHelper::class);
$dateHelperMock->method('parseDate')
    ->willReturn(new DateTime('2025-02-01'));
$dateHelperMock->method('addPeriod')
    ->willReturnCallback(function($date, $freq) {
        $d = clone $date;
        $d->modify('+1 month'); // Simplified
        return $d;
    });

// Or use real DateHelper with predictable inputs
$strategy = new BalloonPaymentStrategy(new DateHelper());
$loan = $this->createTestLoan(['first_payment_date' => '2025-02-01']);
$schedule = $strategy->calculateSchedule($loan);

// Verify dates are correct
$this->assertEquals('2025-02-01', $schedule[0]->getPaymentDate());
$this->assertEquals('2025-03-01', $schedule[1]->getPaymentDate());
```

### Testing Event Handling

```php
public function testHandlesExtraPaymentEvent(): void
{
    // Arrange
    $initialSchedule = [...]; // 360 payments
    $event = new LoanEvent([
        'type' => 'extra_payment',
        'amount' => 50000,
        'date' => '2025-06-01',
    ]);

    $handler = new ExtraPaymentEventHandler($repoMock);

    // Act
    $handler->handle($event);

    // Assert
    // Schedule should be shortened
    $newSchedule = $repoMock->getScheduleRows($loanId);
    $this->assertLessThan(count($initialSchedule), count($newSchedule));
    
    // Final balance should be $0
    $lastRow = end($newSchedule);
    $this->assertEquals(0, $lastRow->getRemainingBalance(), '', 0.02);
}
```

---

## Debugging & Troubleshooting

### Common Test Failures

#### 1. Rounding Precision Issues

**Problem:**
```
Expected: 666.79
Actual: 666.7900000001
Failed: assertEquals()
```

**Solution:**
```php
// Use assertAlmostEquals instead
$this->assertAlmostEquals(666.79, $payment, 0.01);

// Or round both values before comparison
$this->assertEquals(round($expected, 2), round($actual, 2));
```

#### 2. Date Comparison Failures

**Problem:**
```
Expected: 2025-02-01
Actual: DateTime object
Failed: assertEquals()
```

**Solution:**
```php
// Convert DateTime to string for comparison
$this->assertEquals('2025-02-01', $row->getPaymentDate()->format('Y-m-d'));

// Or use DateTimeImmutable comparison
$this->assertEquals(
    new DateTime('2025-02-01'),
    $row->getPaymentDate()
);
```

#### 3. Off-by-One Schedule Errors

**Problem:**
```
Expected: 120 payments
Actual: 119 payments
Failed: assertCount()
```

**Solution:**
```php
// Debug: Print all schedule rows
foreach ($schedule as $i => $row) {
    echo "Payment {$i}: " . $row->getPaymentAmount() . "\n";
}

// Verify final payment included
$this->assertGreaterThanOrEqual(120, count($schedule));

// Check if final payment is correctly sized (includes balloon)
$lastRow = end($schedule);
$this->assertGreaterThan(expectedRegularPayment, $lastRow->getPaymentAmount());
```

#### 4. Mock Expectation Not Met

**Problem:**
```
Expectation failed: Expected 1 call to method, but 0 calls made
Failed: Mock assertion
```

**Solution:**
```php
// Add debugging output
$mock->expects($this->once())->method('save')->willReturnCallback(
    function($data) {
        echo "Save called with: " . json_encode($data) . "\n";
        return true;
    }
);

// Verify method is actually being called
$this->assertTrue($objectUnderTest->requiresMethodCall());
```

### Debug Output Helpers

```php
// Print schedule for visual inspection
private function debugSchedule(array $schedule): void
{
    foreach ($schedule as $row) {
        printf(
            "%s: Payment %0.2f | Interest %0.2f | Principal %0.2f | Balance %0.2f\n",
            $row->getPaymentDate(),
            $row->getPaymentAmount(),
            $row->getInterestPortion(),
            $row->getPrincipalPortion(),
            $row->getRemainingBalance()
        );
    }
}

// Print calculation details
private function debugCalculation(Loan $loan, float $payment): void
{
    $rate = $loan->getAnnualRate() / 100 / 12;
    $periods = $loan->getNumberOfPayments();
    
    echo "Principal: \$" . number_format($loan->getPrincipal(), 2) . "\n";
    echo "Rate: " . $loan->getAnnualRate() . "% annual\n";
    echo "Monthly Rate: " . ($rate * 100) . "%\n";
    echo "Periods: $periods\n";
    echo "Calculated Payment: \$" . number_format($payment, 2) . "\n";
}
```

### Test Isolation Issues

**Problem:** Tests fail when run together but pass individually

**Cause:** Shared test state, database pollution, or timing issues

**Solution:**
```php
// Ensure clean state before each test
protected function setUp(): void
{
    // Reset singletons
    DateHelper::reset();
    
    // Clear database tables
    DB::table('ksf_amortization_staging')->truncate();
    DB::table('ksf_loan_events')->truncate();
    
    // Create fresh mocks
    $this->repositoryMock = $this->createMock(LoanRepository::class);
}

// Clean up after tests
protected function tearDown(): void
{
    // Clean database
    DB::table('ksf_amortization_staging')->truncate();
}
```

---

## Test Maintenance Plan

### When to Update Tests

1. **Feature Changes:** Update corresponding tests
2. **Bug Fixes:** Add regression test before fixing
3. **Refactoring:** Ensure all tests still pass
4. **Performance:** Update timing expectations if improved

### Deprecating Tests

```php
/**
 * @test
 * @deprecated This test is for legacy calculation method
 * @see testNewCalculationMethod()
 */
public function testOldCalculationMethod(): void
{
    // Test still passes but marked for removal
}
```

### Test Review Checklist

- [ ] Test name clearly describes behavior
- [ ] All assertions have meaningful messages
- [ ] No copy-paste between tests
- [ ] No shared state between tests
- [ ] Mocks used for isolation
- [ ] Edge cases covered
- [ ] Coverage >85% for feature

---

**Document Status:** Complete and Ready  
**Last Updated:** December 11, 2025  
**Applies To:** Phase 2-4 Development
