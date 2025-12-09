# Implementation Plan - Critical Issues & Phase 1

## Overview
This document provides a detailed implementation plan for fixing the critical issues identified in the code review. The plan is organized by Phase 1 priority with specific code changes, test requirements, and acceptance criteria.

## Phase 1 Timeline: 8-10 weeks
**Dependencies:** None - can start immediately  
**Resources:** 1-2 senior developers  
**Risk Level:** MEDIUM (core calculation changes)

---

## Task 1: Fix Interest Rate & Payment Frequency Calculation
**Effort:** 16-20 hours  
**Timeline:** Weeks 1-2  
**Priority:** CRITICAL  

### Current Issues
1. **Hardcoded monthly calculation** in `AmortizationModel::calculatePayment()`
   ```php
   $monthly_rate = $rate / 100 / 12;  // ALWAYS divides by 12!
   ```

2. **Hardcoded payment frequency** in `AmortizationModel::calculateSchedule()`
   ```php
   $date->modify('+1 month');  // ALWAYS increments by 1 month!
   ```

3. **Interest calculation frequency ignored** - `interest_calc_frequency` field never used

### Files to Modify
1. `src/Ksfraser/Amortizations/AmortizationModel.php`
2. `src/Ksfraser/Amortizations/controller.php`
3. `modules/amortization/FADataProvider.php`

### Implementation Steps

#### Step 1.1: Create Helper Methods in AmortizationModel
```php
/**
 * Convert payments per year to interval in days
 * @param int $payments_per_year
 * @return int days between payments
 */
private function getPaymentIntervalDays($payments_per_year) {
    return round(365 / $payments_per_year);
}

/**
 * Get interest calculation period in days
 * @param string $frequency 'daily', 'weekly', 'monthly', etc.
 * @return int days in calculation period
 */
private function getInterestCalcPeriodDays($frequency) {
    $periods = [
        'daily' => 1,
        'weekly' => 7,
        'bi-weekly' => 14,
        'semi-monthly' => 15,
        'monthly' => 30,
        'semi-annual' => 180,
        'annual' => 365
    ];
    return $periods[$frequency] ?? 30;
}

/**
 * Calculate period rate from annual rate
 * @param float $annual_rate percentage
 * @param int $period_days
 * @return float period rate
 */
private function getInterestRate($annual_rate, $period_days) {
    return ($annual_rate / 100) * ($period_days / 365);
}
```

#### Step 1.2: Fix calculatePayment() Method
```php
public function calculatePayment($principal, $rate, $num_payments, $payments_per_year = 12) {
    // Calculate period rate based on payment frequency
    $period_days = $this->getPaymentIntervalDays($payments_per_year);
    $period_rate = $this->getInterestRate($rate, $period_days);
    
    if ($period_rate > 0) {
        // Standard amortization formula with correct period rate
        $payment = $principal * $period_rate / 
                   (1 - pow(1 + $period_rate, -$num_payments));
    } else {
        // No interest
        $payment = $principal / $num_payments;
    }
    
    return $payment;
}
```

#### Step 1.3: Fix calculateSchedule() Method
```php
public function calculateSchedule($loan_id) {
    $loan = $this->getLoan($loan_id);
    
    // Clear old schedule if exists
    $this->db->deleteScheduleForLoan($loan_id);
    
    $principal = $loan['amount_financed'];
    $rate = $loan['interest_rate'];
    $num_payments = (int)$loan['loan_term_years'] * (int)$loan['payments_per_year'];
    
    // FIX: Pass payments_per_year to calculatePayment
    $payment = $loan['override_payment'] 
        ? $loan['regular_payment'] 
        : $this->calculatePayment($principal, $rate, $num_payments, $loan['payments_per_year']);
    
    $balance = $principal;
    $date = new \DateTime($loan['first_payment_date']);
    
    // FIX: Get correct period days based on payment frequency
    $period_days = $this->getPaymentIntervalDays($loan['payments_per_year']);
    
    // FIX: Get correct interest calc period days
    $interest_period_days = $this->getInterestCalcPeriodDays(
        $loan['interest_calc_frequency'] ?? 'monthly'
    );
    
    for ($i = 1; $i <= $num_payments; $i++) {
        // Calculate interest for this period
        $period_rate = $this->getInterestRate($rate, $interest_period_days);
        $interest = round($balance * $period_rate, 2);
        
        $principal_portion = $payment - $interest;
        $balance -= $principal_portion;
        
        // Handle final payment (ensure balance = 0)
        if ($i == $num_payments) {
            $principal_portion = max(0, $balance);
            $payment = $interest + $principal_portion;
            $balance = 0;
        }
        
        // Ensure balance never goes negative
        $balance = max(0, $balance);
        
        $this->db->insertSchedule($loan_id, [
            'payment_date' => $date->format('Y-m-d'),
            'payment_amount' => round($payment, 2),
            'principal_portion' => round($principal_portion, 2),
            'interest_portion' => $interest,
            'remaining_balance' => round($balance, 2)
        ]);
        
        // FIX: Increment date by correct period days
        $date->add(new \DateInterval('P' . $period_days . 'D'));
    }
}
```

#### Step 1.4: Update Controller
```php
// In controller.php loan creation handling:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['amount_financed'])) {
        $data = [
            // ... existing fields ...
            'payments_per_year' => (int)$_POST['payments_per_year'] ?? 12,
            'interest_calc_frequency' => $_POST['interest_calc_frequency'] ?? 'monthly',
            // ... rest of fields ...
        ];
        // ... existing code ...
    }
}
```

### Unit Tests to Add
**File:** `tests/AmortizationCalculationTest.php` (new file)

```php
<?php
namespace Ksfraser\Amortizations\Tests;

use Ksfraser\Amortizations\AmortizationModel;
use PHPUnit\Framework\TestCase;

class AmortizationCalculationTest extends TestCase {
    private $model;
    private $mockDb;
    
    protected function setUp(): void {
        $this->mockDb = $this->createMock(DataProviderInterface::class);
        $this->model = new AmortizationModel($this->mockDb);
    }
    
    /**
     * Test payment calculation with monthly frequency
     * $10,000 at 5% annual, 12 payments/year, 12 payments = 12 months
     * Expected: ~$860.07 monthly payment
     */
    public function testCalculatePaymentMonthly() {
        $payment = $this->model->calculatePayment(10000, 5.0, 12, 12);
        $this->assertAlmostEquals(860.07, $payment, 0.01);
    }
    
    /**
     * Test payment calculation with bi-weekly frequency
     * $10,000 at 5% annual, 26 payments/year, 26 payments = 1 year
     */
    public function testCalculatePaymentBiWeekly() {
        $payment = $this->model->calculatePayment(10000, 5.0, 26, 26);
        // Bi-weekly rate should be lower than monthly
        $this->assertLessThan(450, $payment);
        $this->assertGreaterThan(400, $payment);
    }
    
    /**
     * Test payment calculation with weekly frequency
     * $10,000 at 5% annual, 52 payments/year, 52 payments = 1 year
     */
    public function testCalculatePaymentWeekly() {
        $payment = $this->model->calculatePayment(10000, 5.0, 52, 52);
        // Weekly payment should be ~1/4 of monthly
        $this->assertLessThan(230, $payment);
        $this->assertGreaterThan(210, $payment);
    }
    
    /**
     * Test that schedule calculation generates correct number of rows
     */
    public function testCalculateScheduleGeneratesCorrectNumberOfRows() {
        $loan = [
            'id' => 1,
            'amount_financed' => 10000,
            'interest_rate' => 5.0,
            'loan_term_years' => 1,
            'payments_per_year' => 12,
            'regular_payment' => 860.07,
            'override_payment' => 0,
            'first_payment_date' => '2025-01-01',
            'interest_calc_frequency' => 'monthly'
        ];
        
        $this->mockDb->expects($this->once())
            ->method('getLoan')
            ->with(1)
            ->willReturn($loan);
        
        // Expect 12 schedule rows to be inserted
        $this->mockDb->expects($this->exactly(12))
            ->method('insertSchedule')
            ->with(1, $this->isType('array'));
        
        $this->model->calculateSchedule(1);
    }
    
    /**
     * Test that final payment brings balance to zero
     */
    public function testFinalBalanceIsZero() {
        $loan = [
            'id' => 1,
            'amount_financed' => 10000,
            'interest_rate' => 5.0,
            'loan_term_years' => 1,
            'payments_per_year' => 12,
            'regular_payment' => 860.07,
            'override_payment' => 0,
            'first_payment_date' => '2025-01-01',
            'interest_calc_frequency' => 'monthly'
        ];
        
        $capturedRows = [];
        $this->mockDb->expects($this->once())
            ->method('getLoan')
            ->willReturn($loan);
        
        $this->mockDb->expects($this->exactly(12))
            ->method('insertSchedule')
            ->willReturnCallback(function($loan_id, $row) use (&$capturedRows) {
                $capturedRows[] = $row;
            });
        
        $this->model->calculateSchedule(1);
        
        // Last row should have balance = 0
        $lastRow = end($capturedRows);
        $this->assertAlmostEquals(0, $lastRow['remaining_balance'], 0.02);
    }
    
    /**
     * Test date increment matches payment frequency
     */
    public function testDateIncrementMatchesPaymentFrequency() {
        // ... test that dates increment by correct interval ...
    }
}
```

### UAT Scripts to Create
**File:** `tests/UAT_Phase1_Calculation.md`

```markdown
# UAT: Amortization Calculation with Flexible Frequencies

## Scenario 1: Monthly Payment, Monthly Interest (Baseline)
**Loan Parameters:**
- Amount: $10,000
- Rate: 5% annual
- Term: 1 year (12 months)
- Payment Frequency: Monthly
- Interest Calc Frequency: Monthly

**Expected Calculation:**
- Monthly payment: ~$860.07
- Payment dates: 1st of each month
- Final balance after payment 12: $0.00

**Test Steps:**
1. Create loan with above parameters
2. Verify payment amount calculated as $860.07
3. View schedule and verify:
   - First payment date: Jan 1, 2025
   - 12 payments total
   - Final payment brings balance to $0
4. Sum of principal portions = $10,000 (within $0.02)
5. Export schedule and verify in Excel

**Acceptance:** ✓ All values match expected calculations

---

## Scenario 2: Bi-Weekly Payment, Monthly Interest
**Loan Parameters:**
- Amount: $10,000
- Rate: 5% annual
- Term: 1 year (26 bi-weekly payments)
- Payment Frequency: Bi-weekly
- Interest Calc Frequency: Monthly

**Expected Behavior:**
- 26 payments in year
- Payments every 14 days
- Interest calculated monthly
- Final balance: $0.00

**Test Steps:**
1. Create loan with above parameters
2. Verify payment amount is less than bi-weekly ($860.07 ÷ ~2)
3. View schedule and verify:
   - Payment dates increment by 14 days
   - 26 payment lines total
4. Verify calculation accuracy
5. Manually calculate one row to verify formula

**Acceptance:** ✓ Dates increment correctly, final balance = $0

---

## Scenario 3: Weekly Payment, Daily Interest
**Loan Parameters:**
- Amount: $10,000
- Rate: 5% annual
- Term: 1 year (52 weekly payments)
- Payment Frequency: Weekly
- Interest Calc Frequency: Daily

**Test Steps:**
1. Create loan
2. Verify payment amount appropriate for weekly
3. Verify 52 payment lines
4. Verify dates increment by 7 days
5. Verify daily interest calculation
6. Check final balance = $0.00

**Acceptance:** ✓ All requirements met

---

## Acceptance Criteria
- ✓ Schedule calculations match external amortization calculators
- ✓ Final payment brings balance to $0.00 (within $0.02)
- ✓ All dates increment correctly per frequency
- ✓ Interest calculation uses correct period
- ✓ All three scenarios pass above tests
```

### Acceptance Criteria
- ✓ `calculatePayment()` correctly uses payment frequency
- ✓ `calculateSchedule()` uses correct interest calculation period
- ✓ Final balance in all schedules = $0.00 (within $0.02)
- ✓ All unit tests pass (15+ test methods)
- ✓ Manual verification against external calculators (monthly, bi-weekly, weekly)

---

## Task 2: Implement Extra Payment Recalculation
**Effort:** 24-30 hours  
**Timeline:** Weeks 2-4  
**Dependencies:** Task 1 must be complete  

### Current State
- LoanEvent model exists ✓
- ksf_loan_events table exists ✓
- GenericLoanEventProvider exists ✓
- **MISSING:** Recalculation logic when extra payment recorded

### Files to Create/Modify
1. `src/Ksfraser/Amortizations/AmortizationModel.php` - new methods
2. `src/Ksfraser/Amortizations/DataProviderInterface.php` - new method signatures
3. `modules/amortization/FADataProvider.php` - implement interface methods
4. `src/Ksfraser/Amortizations/controller.php` - add UI routes

### Implementation Steps

#### Step 2.1: Add Methods to DataProviderInterface
```php
// In DataProviderInterface.php

/**
 * Insert a loan event (extra payment or skip)
 * @param int $loan_id
 * @param LoanEvent $event
 * @return int event ID
 */
public function insertLoanEvent(int $loan_id, LoanEvent $event): int;

/**
 * Get all events for a loan
 * @param int $loan_id
 * @return array of LoanEvent
 */
public function getLoanEvents(int $loan_id): array;

/**
 * Delete schedule rows after a given date
 * @param int $loan_id
 * @param string $date
 */
public function deleteScheduleAfterDate(int $loan_id, string $date): void;

/**
 * Get schedule rows after a date
 * @param int $loan_id
 * @param string $date
 * @return array of staging rows
 */
public function getScheduleRowsAfterDate(int $loan_id, string $date): array;

/**
 * Update a schedule row
 * @param int $staging_id
 * @param array $updates
 */
public function updateScheduleRow(int $staging_id, array $updates): void;
```

#### Step 2.2: Implement Methods in FADataProvider
```php
// In FADataProvider.php

public function insertLoanEvent(int $loan_id, LoanEvent $event): int {
    $sql = "INSERT INTO " . $this->dbPrefix . "ksf_loan_events 
            (loan_id, event_type, event_date, amount, notes, created_by, created_at) 
            VALUES (:loan_id, :event_type, :event_date, :amount, :notes, :created_by, :created_at)";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        ':loan_id' => $loan_id,
        ':event_type' => $event->event_type,
        ':event_date' => $event->event_date,
        ':amount' => $event->amount,
        ':notes' => $event->notes ?? '',
        ':created_by' => $event->created_by ?? 1,
        ':created_at' => date('Y-m-d H:i:s')
    ]);
    
    return (int)$this->pdo->lastInsertId();
}

public function deleteScheduleAfterDate(int $loan_id, string $date): void {
    $sql = "DELETE FROM " . $this->dbPrefix . "ksf_amortization_staging 
            WHERE loan_id = :loan_id AND payment_date > :date AND posted_to_gl = 0";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':loan_id' => $loan_id, ':date' => $date]);
}

public function getScheduleRowsAfterDate(int $loan_id, string $date): array {
    $sql = "SELECT * FROM " . $this->dbPrefix . "ksf_amortization_staging 
            WHERE loan_id = :loan_id AND payment_date > :date 
            ORDER BY payment_date ASC";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':loan_id' => $loan_id, ':date' => $date]);
    
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

public function updateScheduleRow(int $staging_id, array $updates): void {
    $set = [];
    $params = [':id' => $staging_id];
    
    foreach ($updates as $field => $value) {
        $set[] = "$field = :$field";
        $params[":$field"] = $value;
    }
    
    $sql = "UPDATE " . $this->dbPrefix . "ksf_amortization_staging 
            SET " . implode(', ', $set) . ", updated_at = NOW() 
            WHERE id = :id";
    
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
}
```

#### Step 2.3: Add Methods to AmortizationModel
```php
// In AmortizationModel.php

/**
 * Record an extra payment and recalculate schedule
 * @param int $loan_id
 * @param string $event_date
 * @param float $amount
 * @param string $notes
 * @return array updated schedule
 */
public function recordExtraPayment($loan_id, $event_date, $amount, $notes = '') {
    // Create event
    $event = new LoanEvent([
        'loan_id' => $loan_id,
        'event_type' => 'extra',
        'event_date' => $event_date,
        'amount' => $amount,
        'notes' => $notes
    ]);
    
    $event_id = $this->db->insertLoanEvent($loan_id, $event);
    
    // Recalculate affected schedule
    $this->recalculateScheduleAfterEvent($loan_id, $event_date);
    
    return $this->db->getScheduleRows($loan_id);
}

/**
 * Recalculate schedule after event (extra payment, skip, etc.)
 * @param int $loan_id
 * @param string $event_date
 */
private function recalculateScheduleAfterEvent($loan_id, $event_date) {
    $loan = $this->getLoan($loan_id);
    $events = $this->db->getLoanEvents($loan_id);
    
    // Calculate total extra payments applied before/on event_date
    $total_extra = 0;
    foreach ($events as $event) {
        if ($event['event_type'] === 'extra' && 
            strtotime($event['event_date']) <= strtotime($event_date)) {
            $total_extra += $event['amount'];
        }
    }
    
    // Get first affected schedule row (date > event_date)
    $affectedRows = $this->db->getScheduleRowsAfterDate($loan_id, $event_date);
    
    if (empty($affectedRows)) {
        // No rows to recalculate
        return;
    }
    
    // Delete rows after event_date so we can regenerate
    $this->db->deleteScheduleAfterDate($loan_id, $event_date);
    
    // Find the schedule row for event_date or closest before it
    $baseRows = $this->db->getScheduleRows($loan_id);
    $lastRow = null;
    foreach ($baseRows as $row) {
        if (strtotime($row['payment_date']) <= strtotime($event_date)) {
            $lastRow = $row;
        } else {
            break;
        }
    }
    
    if (!$lastRow) {
        return;
    }
    
    // Apply extra payment to base balance
    $startingBalance = $lastRow['remaining_balance'] - $total_extra;
    $startingBalance = max(0, $startingBalance);
    
    // Recalculate from first affected row
    $payment = $loan['override_payment'] 
        ? $loan['regular_payment']
        : $this->calculatePayment(
            $loan['amount_financed'],
            $loan['interest_rate'],
            $loan['loan_term_years'] * $loan['payments_per_year'],
            $loan['payments_per_year']
        );
    
    $balance = $startingBalance;
    $rate = $loan['interest_rate'];
    $interest_period_days = $this->getInterestCalcPeriodDays(
        $loan['interest_calc_frequency'] ?? 'monthly'
    );
    
    // Find when to start regenerating
    $nextPaymentDate = new \DateTime($lastRow['payment_date']);
    $payment_days = $this->getPaymentIntervalDays($loan['payments_per_year']);
    $nextPaymentDate->add(new \DateInterval('P' . $payment_days . 'D'));
    
    // Generate new schedule rows
    $remaining_payments = 0;
    while ($balance > 0.01 && $remaining_payments < 360) {
        $period_rate = $this->getInterestRate($rate, $interest_period_days);
        $interest = round($balance * $period_rate, 2);
        $principal_portion = $payment - $interest;
        $balance -= $principal_portion;
        
        // Adjust final payment
        if ($balance <= 0.01) {
            $principal_portion = max(0, $balance);
            $payment_amount = $interest + $principal_portion;
            $balance = 0;
        } else {
            $payment_amount = $payment;
        }
        
        $this->db->insertSchedule($loan_id, [
            'payment_date' => $nextPaymentDate->format('Y-m-d'),
            'payment_amount' => round($payment_amount, 2),
            'principal_portion' => round($principal_portion, 2),
            'interest_portion' => $interest,
            'remaining_balance' => round(max(0, $balance), 2)
        ]);
        
        $nextPaymentDate->add(new \DateInterval('P' . $payment_days . 'D'));
        $remaining_payments++;
    }
}
```

### Unit Tests
**File:** `tests/ExtraPaymentTest.php`

```php
<?php
namespace Ksfraser\Amortizations\Tests;

use Ksfraser\Amortizations\AmortizationModel;
use Ksfraser\Amortizations\LoanEvent;
use PHPUnit\Framework\TestCase;

class ExtraPaymentTest extends TestCase {
    private $model;
    private $mockDb;
    
    protected function setUp(): void {
        $this->mockDb = $this->createMock(DataProviderInterface::class);
        $this->model = new AmortizationModel($this->mockDb);
    }
    
    /**
     * Test that extra payment creates loan event
     */
    public function testRecordExtraPaymentCreatesEvent() {
        $this->mockDb->expects($this->once())
            ->method('insertLoanEvent');
        
        $this->mockDb->expects($this->once())
            ->method('getLoan')
            ->willReturn([
                'id' => 1,
                'amount_financed' => 12000,
                'interest_rate' => 5.0,
                'loan_term_years' => 1,
                'payments_per_year' => 12,
                'regular_payment' => 1000.65,
                'override_payment' => 0,
                'first_payment_date' => '2025-01-01',
                'interest_calc_frequency' => 'monthly'
            ]);
        
        $this->model->recordExtraPayment(1, '2025-02-15', 500, 'Bonus');
    }
    
    /**
     * Test that extra payment reduces balance
     */
    public function testExtraPaymentReducesBalance() {
        // ... test logic ...
    }
    
    /**
     * Test that extra payment reduces remaining payments
     */
    public function testExtraPaymentReducesTermLength() {
        // ... test logic ...
    }
}
```

### UAT Scripts
**File:** `tests/UAT_Phase1_ExtraPayment.md`

### Acceptance Criteria
- ✓ Extra payment event created and stored
- ✓ Schedule recalculated from payment date forward
- ✓ Subsequent payment balances adjusted correctly
- ✓ Final balance remains $0.00 (within $0.02)
- ✓ Loan term may be reduced
- ✓ All 10+ unit tests pass

---

## Task 3: Implement GL Posting
**Effort:** 20-24 hours  
**Timeline:** Weeks 4-6  
**Dependencies:** Task 1 and Task 2 recommended (but can proceed in parallel)  

### Current State
```php
// Current stub - does NOTHING
public function postPaymentToGL($loan_id, $payment_row, $gl_accounts) {
    // Example stub: integrate with FA journal entry logic
    // Use FA's API or direct SQL for journal posting
    // Mark payment as posted in fa_amortization_staging
    // ...existing code...
    return true;  // Always returns true!
}
```

### Implementation Steps
[Same as detailed in REQUIREMENTS_TRACEABILITY_DETAILED.md Task 3]

### Acceptance Criteria
- ✓ Journal entries created in FA GL
- ✓ trans_no and trans_type captured and stored
- ✓ GL accounts validated before posting
- ✓ Posting marked in staging table with timestamp/user
- ✓ All 8+ unit tests pass
- ✓ All UAT scripts pass

---

## Code Review & Quality Gates

### Before Committing Code
- [ ] Code passes PHPStan level 8 (strict static analysis)
- [ ] Code follows PSR-12 coding standards
- [ ] All phpdoc blocks complete
- [ ] No IDE warnings/errors

### Before Merging to Main
- [ ] All unit tests pass (100% of Phase 1 tests)
- [ ] Code coverage >85% for modified code
- [ ] Manual code review by 2nd developer
- [ ] No security issues flagged
- [ ] UAT scripts prepared and scheduled

### Before Release
- [ ] All Phase 1 + Phase 2 tasks complete
- [ ] UAT sign-off from Finance/Admin stakeholder
- [ ] Deployment plan documented
- [ ] Rollback procedure tested

---

## Risk Mitigation

### Risk 1: Breaking Existing Schedules
**Probability:** HIGH  
**Impact:** HIGH  
**Mitigation:**
- Backup database before changes
- Create migration to validate existing schedules
- Test with actual historical loans from production

### Risk 2: GL Posting Errors
**Probability:** MEDIUM  
**Impact:** HIGH  
**Mitigation:**
- Test in staging environment first
- Implement GL account validation
- Add comprehensive error logging
- Create reversal/rollback procedures

### Risk 3: Performance Issues with Recalculation
**Probability:** MEDIUM  
**Impact:** MEDIUM  
**Mitigation:**
- Test with large numbers of payments (360+)
- Profile code for bottlenecks
- Add indexing to ksf_loan_events, ksf_amortization_staging

---

## Success Metrics

### Phase 1 Completion Criteria
1. **Functionality:** All 3 critical tasks complete and working
2. **Testing:** >80 unit tests passing, >85% code coverage
3. **Quality:** All code review requirements met
4. **UAT:** All Phase 1 UAT scripts passed and approved
5. **Documentation:** CODE_REVIEW.md updated with implementation details

### Business Metrics
- [ ] Calculation accuracy verified (matches external calculators)
- [ ] GL posting creates correct journal entries
- [ ] Extra payments properly recalculate schedules
- [ ] Performance: schedule calc <2 seconds, batch post <30 seconds
- [ ] Zero critical bugs in Phase 1 testing

---
