# Phase 2 Implementation Guide

**Focus:** FE-001 (Balloon), FE-002 (Variable Rates), FE-003 (Partial Payments)  
**Duration:** 60-76 hours (~3 weeks)  
**Status:** Ready for Development  
**Date:** December 11, 2025

---

## Quick Start Checklist

### Before Starting Development

- [ ] Create feature branches: `feature/balloon-payments`, `feature/variable-rates`, `feature/partial-payments`
- [ ] Set up new test directories: `tests/Unit/Strategies/`, `tests/Unit/EventHandlers/`
- [ ] Review ENHANCEMENT_PLAN_PHASE2_PHASE4.md architecture section
- [ ] Configure CI/CD for expanded test suites (if applicable)
- [ ] Brief team on SOLID principles and Strategy pattern usage
- [ ] Set up code review checklist (PhpDoc, UML, tests, PHP 7.3 compat)

### Development Setup

```bash
# Clone/update repository
cd c:\Users\prote\Documents\ksf_amortization

# Ensure dependencies up to date
composer install

# Verify test infrastructure
composer test

# Watch for changes (optional)
composer test -- --watch
```

---

## File Structure for Phase 2

```
src/Ksfraser/Amortizations/
├── Strategies/
│   ├── LoanCalculationStrategy.php (interface - NEW)
│   ├── StandardAmortizationStrategy.php (existing, refactor)
│   ├── BalloonPaymentStrategy.php (NEW)
│   ├── VariableRateStrategy.php (NEW)
│   └── GracePeriodStrategy.php (NEW in Phase 2 or later)
├── EventHandlers/
│   ├── LoanEventHandler.php (interface - NEW)
│   ├── ExtraPaymentEventHandler.php (refactor from existing)
│   ├── PartialPaymentEventHandler.php (NEW)
│   ├── SkipPaymentEventHandler.php (refactor)
│   └── RateChangeEventHandler.php (NEW)
├── Models/
│   ├── Loan.php (extend with balloon, variable rate support)
│   ├── ScheduleRow.php (extend with rate_period_id, arrears fields)
│   ├── LoanEvent.php (existing)
│   ├── RatePeriod.php (NEW)
│   └── Arrears.php (NEW)
├── Repositories/
│   ├── LoanRepository.php (interface - NEW)
│   ├── ScheduleRepository.php (interface - NEW)
│   ├── RatePeriodRepository.php (interface - NEW)
│   ├── ArrearsRepository.php (interface - NEW)
│   └── [Platform]/FADataProvider.php (implement all interfaces)
├── Services/
│   ├── DateHelper.php (NEW - extracted from existing)
│   ├── ArrearsCalculator.php (NEW)
│   └── StrategyFactory.php (NEW)
└── Exceptions/
    ├── InvalidLoanException.php (NEW)
    └── ArrearsException.php (NEW)

tests/Unit/
├── Strategies/
│   ├── BalloonPaymentStrategyTest.php (NEW)
│   ├── VariableRateStrategyTest.php (NEW)
│   └── StandardAmortizationStrategyTest.php (updated)
├── EventHandlers/
│   ├── PartialPaymentEventHandlerTest.php (NEW)
│   └── RateChangeEventHandlerTest.php (NEW)
├── Models/
│   ├── RatePeriodTest.php (NEW)
│   ├── ArrearsTest.php (NEW)
│   └── LoanTest.php (updated)
└── Services/
    ├── DateHelperTest.php (NEW)
    ├── ArrearsCalculatorTest.php (NEW)
    └── StrategyFactoryTest.php (NEW)

tests/Integration/
├── BalloonPaymentIntegrationTest.php (NEW)
├── VariableRateIntegrationTest.php (NEW)
└── PartialPaymentIntegrationTest.php (NEW)
```

---

## Step-by-Step Implementation

### Week 1: Foundation & Architecture

#### Day 1-2: Interfaces & Abstractions

**Create LoanCalculationStrategy interface:**

```php
<?php
namespace Ksfraser\Amortizations\Strategies;

use Ksfraser\Amortizations\Models\Loan;

/**
 * LoanCalculationStrategy Interface
 *
 * Defines contract for different loan calculation methods.
 * Allows Strategy pattern for standard, balloon, variable rate, grace period.
 *
 * ### Design Pattern
 * Strategy Pattern: Each concrete strategy encapsulates an algorithm
 * for loan calculation. AmortizationModel delegates to appropriate strategy.
 *
 * ### Liskov Substitution
 * All implementations are interchangeable. Code should work with ANY strategy.
 *
 * @since 2.0.0
 * @package Ksfraser\Amortizations\Strategies
 */
interface LoanCalculationStrategy
{
    /**
     * Calculate periodic payment for this loan
     *
     * @param Loan $loan Loan with all configuration parameters
     *
     * @return float Calculated payment amount (rounded to 2 decimals)
     * @throws \InvalidArgumentException If loan invalid for this strategy
     * @throws \RuntimeException On calculation error
     */
    public function calculatePayment(Loan $loan): float;

    /**
     * Calculate full amortization schedule
     *
     * Generates all payment rows from start to finish, accounting for
     * interest calculation, frequency, special terms (balloon, grace, etc).
     *
     * ### Algorithm Responsibility
     * Each strategy implements own algorithm:
     * - StandardAmortization: Simple PMT formula
     * - BalloonPayment: Reduced amortization + balloon final payment
     * - VariableRate: Multiple periods with different rates
     * - GracePeriod: Initial period with deferred principal
     *
     * @param Loan $loan Loan to schedule
     *
     * @return array Array of ScheduleRow objects, ordered by payment date
     * @throws \InvalidArgumentException If loan invalid
     */
    public function calculateSchedule(Loan $loan): array;

    /**
     * Check if this strategy supports the given loan
     *
     * Determines if loan configuration matches strategy requirements.
     *
     * @param Loan $loan Loan to evaluate
     *
     * @return bool True if strategy can handle this loan type
     */
    public function supports(Loan $loan): bool;
}
```

**Create LoanEventHandler interface:**

```php
<?php
namespace Ksfraser\Amortizations\EventHandlers;

use Ksfraser\Amortizations\Models\LoanEvent;

/**
 * LoanEventHandler Interface
 *
 * Defines contract for handling loan-related events.
 * Observer pattern: Events trigger handlers which update loan state.
 *
 * ### Event Types
 * - extra_payment: Customer pays more than scheduled
 * - skip_payment: Customer skips payment (deferred to end)
 * - partial_payment: Customer pays less than scheduled
 * - rate_change: Interest rate changes (ARM reset)
 * - payment_holiday: Temporary payment deferral
 * - refinance: Loan refinanced at new terms
 *
 * @since 2.0.0
 * @package Ksfraser\Amortizations\EventHandlers
 */
interface LoanEventHandler
{
    /**
     * Handle a loan event
     *
     * Process event, update loan/schedule state, persist changes.
     * Implementation should be transactional.
     *
     * @param LoanEvent $event Event to process
     *
     * @return void
     * @throws \InvalidArgumentException If event invalid or not supported
     * @throws \RuntimeException On data access error
     */
    public function handle(LoanEvent $event): void;

    /**
     * Check if handler can process this event type
     *
     * @param string $eventType Event type code
     *
     * @return bool True if handler supports event
     */
    public function supports(string $eventType): bool;
}
```

**Create repository interfaces:**

```php
<?php
namespace Ksfraser\Amortizations\Repositories;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\ScheduleRow;

/**
 * LoanRepository Interface
 *
 * Abstraction for loan data access. Implementations vary by platform
 * (FrontAccounting, WordPress, SuiteCRM, etc).
 *
 * @since 2.0.0
 */
interface LoanRepository
{
    /**
     * @param int $id Loan ID
     * @return Loan|null Loan object or null if not found
     */
    public function getLoan(int $id): ?Loan;

    /**
     * @param Loan $loan Loan to save
     * @return void
     */
    public function saveLoan(Loan $loan): void;

    /**
     * @param int $id Loan ID
     * @return void
     */
    public function deleteLoan(int $id): void;
}

/**
 * ScheduleRepository Interface
 *
 * Abstraction for amortization schedule persistence.
 *
 * @since 2.0.0
 */
interface ScheduleRepository
{
    /**
     * Get all schedule rows for a loan
     *
     * @param int $loanId Loan ID
     * @return ScheduleRow[] Rows ordered by payment date
     */
    public function getScheduleRows(int $loanId): array;

    /**
     * Get row for specific date
     *
     * @param int $loanId Loan ID
     * @param string $date Payment date (YYYY-MM-DD)
     * @return ScheduleRow|null
     */
    public function getRowForDate(int $loanId, string $date): ?ScheduleRow;

    /**
     * Save or update schedule rows
     *
     * @param int $loanId Loan ID
     * @param ScheduleRow[] $rows Rows to save
     * @return void
     */
    public function saveScheduleRows(int $loanId, array $rows): void;

    /**
     * Delete all schedule rows after given date
     *
     * Used when recalculating after event.
     *
     * @param int $loanId Loan ID
     * @param string $date Delete rows after this date (YYYY-MM-DD)
     * @return int Number of rows deleted
     */
    public function deleteScheduleAfter(int $loanId, string $date): int;
}

/**
 * RatePeriodRepository Interface
 *
 * @since 2.0.0
 */
interface RatePeriodRepository
{
    public function getPeriodsForLoan(int $loanId): array;
    public function addPeriod(int $loanId, RatePeriod $period): void;
    public function removePeriod(int $loanId, string $startDate): void;
}

/**
 * ArrearsRepository Interface
 *
 * @since 2.0.0
 */
interface ArrearsRepository
{
    public function getArrears(int $loanId): Arrears;
    public function updateArrears(int $loanId, Arrears $arrears): void;
    public function clearArrears(int $loanId): void;
}
```

#### Day 3: Extend Domain Models

**Update Loan model with new properties:**

```php
<?php
// In Loan.php, add properties and methods

private $balloonAmount;
private $balloonPercentage;
private $balloonType = 'amount';

private $ratePeriods = [];
private $hasVariableRate = false;

private $gracePeriodMonths = 0;
private $graceType = 'interest_only'; // or 'deferred'

/**
 * Check if loan has balloon payment
 * @return bool
 */
public function hasBalloonPayment(): bool
{
    return $this->balloonAmount !== null || $this->balloonPercentage !== null;
}

/**
 * Get effective balloon amount
 * @return float
 */
public function getBalloonAmount(): float
{
    if ($this->balloonPercentage !== null) {
        return round($this->principal * $this->balloonPercentage / 100, 2);
    }
    return $this->balloonAmount ?? 0;
}

/**
 * Check if loan has variable rate
 * @return bool
 */
public function hasVariableRate(): bool
{
    return count($this->ratePeriods) > 1;
}

/**
 * Get rate for specific date
 * @param string $date YYYY-MM-DD
 * @return float Annual rate percentage
 */
public function getRateForDate(string $date): float
{
    foreach ($this->ratePeriods as $period) {
        if ($period->isActive($date)) {
            return $period->getRate();
        }
    }
    return $this->getAnnualRate();
}

/**
 * Add rate period
 * @param RatePeriod $period
 * @return void
 */
public function addRatePeriod(RatePeriod $period): void
{
    $this->ratePeriods[] = $period;
    usort($this->ratePeriods, fn($a, $b) => 
        strtotime($a->getStartDate()) <=> strtotime($b->getStartDate())
    );
    $this->hasVariableRate = count($this->ratePeriods) > 1;
}

// Similar methods for grace period...
```

**Create RatePeriod model:**

```php
<?php
namespace Ksfraser\Amortizations\Models;

/**
 * RatePeriod Model
 *
 * Represents a period with single fixed interest rate.
 * Allows loans to have multiple periods with different rates.
 *
 * @since 2.0.0
 */
class RatePeriod
{
    private $id;
    private $startDate;
    private $endDate;
    private $rate;
    private $resetType = 'fixed';
    private $indexName;
    private $margin;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->startDate = $data['start_date'] ?? date('Y-m-d');
        $this->endDate = $data['end_date'] ?? null;
        $this->rate = (float)($data['rate'] ?? 0);
        $this->resetType = $data['reset_type'] ?? 'fixed';
        $this->indexName = $data['index_name'] ?? null;
        $this->margin = isset($data['margin']) ? (float)$data['margin'] : null;
    }

    public function isActive(string $date): bool
    {
        $d = strtotime($date);
        $start = strtotime($this->startDate);
        $end = $this->endDate ? strtotime($this->endDate) : PHP_INT_MAX;
        return $d >= $start && $d <= $end;
    }

    public function getId() { return $this->id; }
    public function getStartDate(): string { return $this->startDate; }
    public function getEndDate(): ?string { return $this->endDate; }
    public function getRate(): float { return $this->rate; }
    public function getResetType(): string { return $this->resetType; }
    public function getIndexName(): ?string { return $this->indexName; }
    public function getMargin(): ?float { return $this->margin; }
}
```

**Create Arrears model:**

```php
<?php
namespace Ksfraser\Amortizations\Models;

/**
 * Arrears Model
 *
 * Tracks delinquent payments and accrued arrears interest.
 *
 * @since 2.0.0
 */
class Arrears
{
    private $loanId;
    private $firstMissedDate;
    private $principalDue = 0;
    private $interestDue = 0;
    private $arrearsInterest = 0;
    private $missedPaymentCount = 0;

    public function __construct(array $data = [])
    {
        $this->loanId = $data['loan_id'] ?? 0;
        $this->firstMissedDate = $data['first_missed_date'] ?? null;
        $this->principalDue = (float)($data['principal_due'] ?? 0);
        $this->interestDue = (float)($data['interest_due'] ?? 0);
        $this->arrearsInterest = (float)($data['arrears_interest'] ?? 0);
        $this->missedPaymentCount = $data['missed_payment_count'] ?? 0;
    }

    public function getTotalDue(): float
    {
        return $this->principalDue + $this->interestDue + $this->arrearsInterest;
    }

    public function applyPayment(float $amount): float
    {
        $remaining = $amount;

        if ($remaining > 0 && $this->arrearsInterest > 0) {
            $applied = min($remaining, $this->arrearsInterest);
            $this->arrearsInterest -= $applied;
            $remaining -= $applied;
        }

        if ($remaining > 0 && $this->interestDue > 0) {
            $applied = min($remaining, $this->interestDue);
            $this->interestDue -= $applied;
            $remaining -= $applied;
        }

        if ($remaining > 0 && $this->principalDue > 0) {
            $applied = min($remaining, $this->principalDue);
            $this->principalDue -= $applied;
            $remaining -= $applied;
        }

        return $remaining;
    }

    public function accrueInterest(float $annualRate, int $daysSinceMissed): float
    {
        if ($this->principalDue <= 0) {
            return 0;
        }

        $dailyRate = $annualRate / 100 / 365;
        $accrued = round($this->principalDue * $dailyRate * $daysSinceMissed, 2);
        $this->arrearsInterest = round($this->arrearsInterest + $accrued, 2);

        return $accrued;
    }

    public function addMissedPayment(string $date, float $shortfall): void
    {
        if (!$this->firstMissedDate) {
            $this->firstMissedDate = $date;
        }
        $this->missedPaymentCount++;
        $this->principalDue += $shortfall; // Simplified - should split interest/principal
    }

    public function isDelinquent(): bool { return $this->missedPaymentCount > 0; }
    public function isPastDue(): bool { return $this->getTotalDue() > 0.01; }
    
    // Getters...
}
```

#### Day 4-5: Create Concrete Strategies

**Refactor StandardAmortizationStrategy:**

```php
<?php
namespace Ksfraser\Amortizations\Strategies;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\ScheduleRow;
use Ksfraser\Amortizations\Services\DateHelper;

/**
 * StandardAmortizationStrategy
 *
 * Calculates fully-amortized loans with fixed rate.
 * Uses standard PMT formula.
 *
 * @implements LoanCalculationStrategy
 * @since 2.0.0
 */
class StandardAmortizationStrategy implements LoanCalculationStrategy
{
    private $dateHelper;

    public function __construct(DateHelper $dateHelper)
    {
        $this->dateHelper = $dateHelper;
    }

    public function supports(Loan $loan): bool
    {
        return !$loan->hasBalloonPayment() 
            && !$loan->hasVariableRate() 
            && $loan->getGracePeriodMonths() === 0;
    }

    public function calculatePayment(Loan $loan): float
    {
        $rate = $loan->getAnnualRate() / 100 / $loan->getPeriodsPerYear();
        $periods = $loan->getNumberOfPayments();

        if ($rate > 0) {
            $payment = $loan->getPrincipal() 
                     * $rate * (1 + $rate) ** $periods 
                     / ((1 + $rate) ** $periods - 1);
        } else {
            $payment = $loan->getPrincipal() / $periods;
        }

        return round($payment, 2);
    }

    public function calculateSchedule(Loan $loan): array
    {
        $schedule = [];
        $payment = $this->calculatePayment($loan);
        $balance = $loan->getPrincipal();
        $date = $this->dateHelper->parseDate($loan->getFirstPaymentDate());
        $rate = $loan->getAnnualRate() / 100 / $loan->getPeriodsPerYear();

        for ($i = 1; $i <= $loan->getNumberOfPayments(); $i++) {
            $interest = round($balance * $rate, 2);
            $principal = $payment - $interest;

            if ($i === $loan->getNumberOfPayments()) {
                $principal = $balance; // Final payment
                $balance = 0;
            } else {
                $balance = round($balance - $principal, 2);
            }

            $row = new ScheduleRow([
                'payment_date' => $date->format('Y-m-d'),
                'payment_number' => $i,
                'payment_amount' => ($i === $loan->getNumberOfPayments()) 
                    ? $principal + $interest 
                    : $payment,
                'interest_portion' => $interest,
                'principal_portion' => $principal,
                'remaining_balance' => max(0, $balance),
            ]);

            $schedule[] = $row;
            $date = $this->dateHelper->addPeriod($date, $loan->getPaymentFrequency());
        }

        return $schedule;
    }
}
```

### Week 2: Implement FE-001 & FE-002

Follow detailed implementation from ENHANCEMENT_PLAN document...

### Week 3: Implement FE-003 & Complete

Follow partial payment implementation...

---

## Testing Strategy for Phase 2

### Unit Test Template

```php
<?php
namespace Ksfraser\Amortizations\Tests\Unit\Strategies;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Strategies\BalloonPaymentStrategy;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\DateHelper;

/**
 * @covers \Ksfraser\Amortizations\Strategies\BalloonPaymentStrategy
 * @group strategies
 * @group balloon
 */
class BalloonPaymentStrategyTest extends TestCase
{
    private $strategy;
    private $dateHelper;

    protected function setUp(): void
    {
        $this->dateHelper = new DateHelper();
        $this->strategy = new BalloonPaymentStrategy($this->dateHelper);
    }

    /**
     * @test
     * @group unit
     */
    public function testSupportsLoansWithBalloon(): void
    {
        $loan = new Loan(['balloon_amount' => 20000]);
        $this->assertTrue($this->strategy->supports($loan));
    }

    /**
     * @test
     */
    public function testRejectsLoansWithoutBalloon(): void
    {
        $loan = new Loan(['balloon_amount' => null]);
        $this->assertFalse($this->strategy->supports($loan));
    }

    /**
     * @test
     */
    public function testCalculatesCorrectPayment(): void
    {
        $loan = new Loan([
            'principal' => 100000,
            'rate' => 5.0,
            'periods' => 120,
            'balloon_amount' => 20000,
        ]);

        $payment = $this->strategy->calculatePayment($loan);
        
        $this->assertIsFloat($payment);
        $this->assertGreaterThan(0, $payment);
        // Verify against expected calculation
        $this->assertAlmostEquals(666.79, $payment, 0.1);
    }

    /**
     * @test
     */
    public function testFinalPaymentIncludesBalloon(): void
    {
        $loan = new Loan([
            'principal' => 100000,
            'rate' => 5.0,
            'periods' => 120,
            'balloon_amount' => 25000,
        ]);

        $schedule = $this->strategy->calculateSchedule($loan);
        $lastRow = end($schedule);

        $this->assertEquals(25000, $lastRow->getPrincipalPortion());
        $this->assertEquals(0, $lastRow->getRemainingBalance(), '', 0.02);
    }

    /**
     * @test
     */
    public function testTotalPaymentsEqualsAmortization(): void
    {
        $loan = new Loan([
            'principal' => 100000,
            'rate' => 5.0,
            'periods' => 120,
            'balloon_amount' => 20000,
        ]);

        $schedule = $this->strategy->calculateSchedule($loan);
        $totalPayments = array_reduce($schedule, 
            fn($carry, $row) => $carry + $row->getPaymentAmount(), 0
        );

        $this->assertGreaterThan($loan->getPrincipal(), $totalPayments);
        $this->assertLessThan($loan->getPrincipal() * 1.15, $totalPayments);
    }
}
```

### Integration Test Template

```php
<?php
namespace Ksfraser\Amortizations\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\AmortizationModel;
use Ksfraser\Amortizations\Models\Loan;

/**
 * Integration test for balloon payment workflow
 */
class BalloonPaymentIntegrationTest extends TestCase
{
    /**
     * @test
     * @group integration
     */
    public function testCompleteWorkflowWithBalloonPayment(): void
    {
        // Arrange
        $loan = new Loan([
            'id' => 1,
            'principal' => 50000,
            'rate' => 4.5,
            'periods' => 60,
            'balloon_amount' => 10000,
        ]);

        // Act
        $schedule = $this->amortizationModel->calculateSchedule($loan);
        
        // Assert
        $this->assertCount(60, $schedule);
        
        $lastRow = end($schedule);
        $this->assertEquals(10000, $lastRow->getPrincipalPortion());
        $this->assertEquals(0, $lastRow->getRemainingBalance(), '', 0.02);
    }
}
```

---

## Database Migrations for Phase 2

### SQL Scripts to Execute

**Add balloon columns:**
```sql
ALTER TABLE ksf_loans_summary ADD COLUMN (
    balloon_amount DECIMAL(15, 2) DEFAULT NULL,
    balloon_percentage DECIMAL(5, 2) DEFAULT NULL,
    balloon_type ENUM('amount', 'percentage') DEFAULT 'amount'
);
```

**Add rate periods table:**
```sql
CREATE TABLE ksf_rate_periods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    annual_rate DECIMAL(5, 2) NOT NULL,
    reset_type ENUM('fixed', 'index', 'margin') DEFAULT 'fixed',
    index_name VARCHAR(20) NULL,
    margin DECIMAL(5, 2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES ksf_loans_summary(id) ON DELETE CASCADE,
    KEY idx_loan_date (loan_id, start_date),
    UNIQUE KEY uc_loan_period (loan_id, start_date)
);
```

**Add arrears table:**
```sql
CREATE TABLE ksf_loan_arrears (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL UNIQUE,
    first_missed_date DATE NOT NULL,
    principal_due DECIMAL(15, 2) DEFAULT 0,
    interest_due DECIMAL(15, 2) DEFAULT 0,
    arrears_interest DECIMAL(15, 2) DEFAULT 0,
    missed_payment_count INT DEFAULT 0,
    last_accrual_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES ksf_loans_summary(id) ON DELETE CASCADE,
    KEY idx_delinquent (first_missed_date, principal_due)
);
```

**Extend schedule table:**
```sql
ALTER TABLE ksf_amortization_staging ADD COLUMN (
    rate_period_id INT NULL,
    is_partial_payment TINYINT DEFAULT 0,
    arrears_balance DECIMAL(15, 2) DEFAULT 0,
    effective_rate DECIMAL(5, 2) NULL,
    FOREIGN KEY (rate_period_id) REFERENCES ksf_rate_periods(id) ON DELETE SET NULL
);
```

---

## Code Review Checklist

Before merging Phase 2 features, verify:

### Architecture & Design
- [ ] Strategy pattern implemented consistently
- [ ] All strategies implement LoanCalculationStrategy
- [ ] Liskov substitution works (strategies are interchangeable)
- [ ] SOLID principles applied (SRP, O/C, DIP, ISP)
- [ ] No circular dependencies
- [ ] DRY: Common code extracted to base class or trait

### PHP 7.3 Compatibility
- [ ] No union types (use PhpDoc)
- [ ] No match() expressions (use switch)
- [ ] No named arguments
- [ ] No property promotion
- [ ] Type hints compatible with 7.3+
- [ ] No deprecation warnings

### Testing
- [ ] >85% code coverage for strategies
- [ ] >85% coverage for models
- [ ] Unit tests pass locally
- [ ] Integration tests verify workflows
- [ ] UAT scripts documented and executable
- [ ] Edge cases tested (0 rate, 1 period, maximum values)

### Documentation
- [ ] Comprehensive PhpDoc on all classes/methods
- [ ] Algorithm explanations in docblocks
- [ ] Usage examples provided
- [ ] UML class diagrams created
- [ ] Database schema documented
- [ ] Performance characteristics noted (O notation)

### Code Quality
- [ ] Consistent formatting (PSR-12)
- [ ] No debug statements or commented code
- [ ] Error handling complete
- [ ] No magic numbers (use constants)
- [ ] Immutability where applicable
- [ ] Thread-safety considerations noted

---

## Common Pitfalls to Avoid

1. **Rounding Errors:** Always use 2 decimal places consistently
   ```php
   // ❌ WRONG
   $result = $value / 3;
   
   // ✅ CORRECT
   $result = round($value / 3, 2);
   ```

2. **Off-by-one Errors:** Final payment requires special handling
   ```php
   // ❌ WRONG
   if ($i < $loan->getNumberOfPayments()) { /* regular */ }
   
   // ✅ CORRECT
   if ($i === $loan->getNumberOfPayments()) { /* final */ }
   ```

3. **Null Balance:** Always ensure final balance is exactly 0
   ```php
   // ❌ WRONG
   $balance = round($balance - $principal, 2);
   
   // ✅ CORRECT
   if ($i === $loan->getNumberOfPayments()) {
       $principal = $balance; // Absorb rounding
       $balance = 0;
   }
   ```

4. **Date Arithmetic:** Always use DateHelper, not manual calculation
   ```php
   // ❌ WRONG
   $nextDate = new DateTime($currentDate->format('Y-m-d') . ' + 1 month');
   
   // ✅ CORRECT
   $nextDate = $dateHelper->addPeriod($currentDate, 'monthly');
   ```

5. **Incomplete Interfaces:** All strategies must fully implement interface
   ```php
   // ❌ INCOMPLETE
   class MyStrategy implements LoanCalculationStrategy {
       // Missing calculateSchedule()
   }
   ```

---

## Performance Targets

### Calculation Performance
- Standard 30-year schedule: <5ms
- Variable rate (10 periods): <10ms
- Balloon payment: <5ms
- Partial payment recalculation: <100ms

### Test Execution
- Unit tests (Phase 2): <5 seconds
- Integration tests: <10 seconds
- Full suite: <20 seconds

### Database Operations
- Insert 360-row schedule: <500ms
- Query loan + schedule: <100ms
- Recalculate after event: <1000ms

---

## Deployment Checklist

- [ ] All tests passing locally
- [ ] Code reviewed and approved
- [ ] PHP 7.3 compatibility verified
- [ ] Database migrations tested
- [ ] Rollback procedure documented
- [ ] Performance tested against production-like data
- [ ] Documentation updated
- [ ] Team trained on new features
- [ ] Monitoring/alerts configured
- [ ] Staged deployment plan executed

---

## Next Phase (Phase 3) Preparation

- [ ] Grace periods identified for Phase 2
- [ ] Prepayment penalty calculation designed
- [ ] Refinancing service architecture sketched
- [ ] Phase 3 feature branches created
- [ ] Phase 3 requirements reviewed with stakeholders

---

**Document Status:** Ready for Development  
**Last Updated:** December 11, 2025
