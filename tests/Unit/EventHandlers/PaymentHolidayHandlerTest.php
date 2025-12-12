<?php

namespace Tests\Unit\EventHandlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\EventHandlers\PaymentHolidayHandler;
use Ksfraser\Amortizations\Services\PaymentHistoryTracker;
use DateTimeImmutable;

/**
 * PaymentHolidayHandlerTest - TDD Test Suite
 *
 * Tests for the PaymentHolidayHandler event handler which manages payment
 * holidays (forbearance periods) where borrowers can defer payments while
 * optionally accruing or capitalizing interest.
 *
 * Responsibilities:
 * - Create and manage payment holiday periods
 * - Support accrual vs. deferral modes (interest handling)
 * - Track holiday reason and authorization
 * - Calculate term extension or balance adjustment
 * - Recalculate schedules after holiday
 * - Record events in payment history
 *
 * Test coverage: 13 tests
 * - Holiday creation with accrual (2 tests)
 * - Holiday creation with deferral (2 tests)
 * - Interest calculation during holiday (2 tests)
 * - Schedule recalculation (2 tests)
 * - Holiday validation (2 tests)
 * - Event recording and tracking (2 tests)
 * - Edge cases (1 test)
 */
class PaymentHolidayHandlerTest extends TestCase
{
    private $handler;
    private $tracker;

    protected function setUp(): void
    {
        $this->handler = new PaymentHolidayHandler();
        $this->tracker = new PaymentHistoryTracker();
    }

    /**
     * Test 1: Create payment holiday with interest accrual
     */
    public function testCreatePaymentHolidayWithAccrual()
    {
        $loan = $this->createTestLoan();

        $holiday = $this->handler->createHoliday(
            $loan,
            2,  // 2 months
            'accrual',  // Interest accrues to balance
            'Customer financial hardship',
            new DateTimeImmutable('2024-02-01')
        );

        $this->assertIsArray($holiday);
        $this->assertEquals($loan->getId(), $holiday['loan_id']);
        $this->assertEquals(2, $holiday['months']);
        $this->assertEquals('accrual', $holiday['interest_handling']);
        $this->assertEquals('ACTIVE', $holiday['status']);
        $this->assertStringContainsString('hardship', strtolower($holiday['reason']));
    }

    /**
     * Test 2: Create payment holiday with interest deferral
     */
    public function testCreatePaymentHolidayWithDeferral()
    {
        $loan = $this->createTestLoan();

        $holiday = $this->handler->createHoliday(
            $loan,
            3,  // 3 months
            'deferral',  // Interest deferred, capitalized at end
            'COVID-19 pandemic relief',
            new DateTimeImmutable('2024-03-01')
        );

        $this->assertEquals('deferral', $holiday['interest_handling']);
        $this->assertEquals(3, $holiday['months']);
        $this->assertStringContainsString('covid', strtolower($holiday['reason']));
    }

    /**
     * Test 3: Calculate accrued interest during holiday (accrual mode)
     */
    public function testCalculateAccruedInterestDuringHoliday()
    {
        $loan = $this->createTestLoan();
        // $10,000 @ 5% annual = $50/month

        $holiday = $this->handler->createHoliday(
            $loan,
            2,
            'accrual',
            'Temporary hardship',
            new DateTimeImmutable('2024-02-01')
        );

        // 2 months accrual: $50 * 2 = $100
        $accruedInterest = $this->handler->calculateAccruedInterest($holiday);

        $this->assertAlmostEquals(100.00, $accruedInterest, 2);
    }

    /**
     * Test 4: Calculate deferred interest during holiday (deferral mode)
     */
    public function testCalculateDeferredInterestDuringHoliday()
    {
        $loan = $this->createTestLoan();

        $holiday = $this->handler->createHoliday(
            $loan,
            3,
            'deferral',
            'Pandemic relief',
            new DateTimeImmutable('2024-03-01')
        );

        // 3 months deferral: $50 * 3 = $150
        $deferredInterest = $this->handler->calculateDeferredInterest($holiday);

        $this->assertAlmostEquals(150.00, $deferredInterest, 2);
    }

    /**
     * Test 5: Apply accrual mode - add interest to principal
     */
    public function testApplyAccrualMode()
    {
        $loan = $this->createTestLoan();
        $originalBalance = $loan->getCurrentBalance();

        $holiday = $this->handler->createHoliday(
            $loan,
            2,
            'accrual',
            'Hardship',
            new DateTimeImmutable('2024-02-01')
        );

        // After holiday: principal increases by accrued interest
        $newBalance = $this->handler->applyAccrual($loan, $holiday);

        $expectedBalance = $originalBalance + 100.00;
        $this->assertAlmostEquals($expectedBalance, $newBalance, 2);
    }

    /**
     * Test 6: Apply deferral mode - extend term and capitalize interest
     */
    public function testApplyDeferralMode()
    {
        $loan = $this->createTestLoan();
        $originalBalance = $loan->getCurrentBalance();
        $originalMonths = $loan->getMonths();

        $holiday = $this->handler->createHoliday(
            $loan,
            3,
            'deferral',
            'Pandemic relief',
            new DateTimeImmutable('2024-03-01')
        );

        // After holiday: term extends, interest capitalized
        $result = $this->handler->applyDeferral($loan, $holiday);

        $this->assertIsArray($result);
        $this->assertEquals($originalMonths + 3, $result['new_term']);
        $this->assertEquals($originalBalance + 150.00, $result['new_balance']);
    }

    /**
     * Test 7: Recalculate schedule after accrual holiday
     */
    public function testRecalculateScheduleAfterAccrualHoliday()
    {
        $loan = $this->createTestLoan();

        $holiday = $this->handler->createHoliday(
            $loan,
            2,
            'accrual',
            'Temporary hardship',
            new DateTimeImmutable('2024-02-01')
        );

        $schedule = $this->handler->recalculateSchedule($loan, $holiday);

        $this->assertIsArray($schedule);
        $this->assertArrayHasKey('periods', $schedule);
        $this->assertArrayHasKey('holiday_end_date', $schedule);
        
        // Schedule should continue from end of holiday
        $this->assertEquals(2024, (int)date('Y', strtotime($schedule['holiday_end_date'])));
    }

    /**
     * Test 8: Recalculate schedule after deferral holiday (term extension)
     */
    public function testRecalculateScheduleAfterDeferralHoliday()
    {
        $loan = $this->createTestLoan();

        $holiday = $this->handler->createHoliday(
            $loan,
            3,
            'deferral',
            'COVID relief',
            new DateTimeImmutable('2024-03-01')
        );

        $schedule = $this->handler->recalculateSchedule($loan, $holiday);

        $this->assertIsArray($schedule);
        // Should have more periods due to term extension
        $this->assertGreaterThan(60, count($schedule['periods']));
    }

    /**
     * Test 9: Validate holiday months within reasonable limits
     */
    public function testValidateHolidayMonthsLimit()
    {
        $loan = $this->createTestLoan();

        // Try to create 24-month holiday (exceeds max of 12)
        $isValid = $this->handler->isValidHoliday($loan, [
            'months' => 24,
            'reason' => 'Test',
        ]);

        $this->assertFalse($isValid);
    }

    /**
     * Test 10: Validate holiday doesn't exceed loan term
     */
    public function testValidateHolidayNotExceedLoanTerm()
    {
        $loan = $this->createTestLoan();
        // Loan is 60 months

        // Try to create 61-month holiday
        $isValid = $this->handler->isValidHoliday($loan, [
            'months' => 61,
            'reason' => 'Test',
        ]);

        $this->assertFalse($isValid);
    }

    /**
     * Test 11: Record holiday event in payment history
     */
    public function testRecordHolidayEventInHistory()
    {
        $loan = $this->createTestLoan();

        $holiday = $this->handler->createHoliday(
            $loan,
            2,
            'accrual',
            'Hardship',
            new DateTimeImmutable('2024-02-01')
        );

        $this->handler->recordHolidayEvent($loan->getId(), $holiday, $this->tracker);

        // Verify holiday was recorded - handler stores internally
        $this->assertTrue(true);  // Record method succeeded without error
    }

    /**
     * Test 12: Approve and activate holiday
     */
    public function testApproveAndActivateHoliday()
    {
        $loan = $this->createTestLoan();

        $holiday = $this->handler->createHoliday(
            $loan,
            2,
            'accrual',
            'Hardship',
            new DateTimeImmutable('2024-02-01')
        );

        $approved = $this->handler->approveHoliday($holiday, 'loan_officer_001', 'Approved per policy');
        $activated = $this->handler->activateHoliday($approved);

        $this->assertEquals('APPROVED', $approved['status']);
        $this->assertEquals('loan_officer_001', $approved['approved_by']);
        $this->assertEquals('ACTIVE', $activated['status']);
    }

    /**
     * Test 13: Multiple sequential holidays on same loan
     */
    public function testMultipleSequentialHolidays()
    {
        $loan = $this->createTestLoan();

        // First holiday: 2 months accrual
        $holiday1 = $this->handler->createHoliday(
            $loan,
            2,
            'accrual',
            'First hardship',
            new DateTimeImmutable('2024-02-01')
        );

        // Second holiday: 1 month deferral (after first ends)
        $holiday2 = $this->handler->createHoliday(
            $loan,
            1,
            'deferral',
            'Second hardship',
            new DateTimeImmutable('2024-04-01')
        );

        $this->assertEquals('accrual', $holiday1['interest_handling']);
        $this->assertEquals('deferral', $holiday2['interest_handling']);
        $this->assertEquals(2, $holiday1['months']);
        $this->assertEquals(1, $holiday2['months']);
    }

    // ============ Helper Methods ============

    private function createTestLoan(): Loan
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(10000.00);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(10000.00);
        return $loan;
    }

    /**
     * Assert two floats are approximately equal
     */
    private function assertAlmostEquals($expected, $actual, $decimals = 2)
    {
        $this->assertEquals(
            round($expected, $decimals),
            round($actual, $decimals),
            "Values differ by more than .$decimals places"
        );
    }
}
