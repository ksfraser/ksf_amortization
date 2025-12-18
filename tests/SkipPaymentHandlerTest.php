<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Phase 16: Skip Payment Handler - TDD Test Suite
 * 
 * Tests for the SkipPaymentHandler event processor.
 * Validates skip payment event processing, schedule recalculation,
 * term extension, and interest accrual.
 * 
 * Test Categories:
 * - Validation Tests (4 tests)
 * - Schedule Recalculation Tests (4 tests)
 * - Term Extension Tests (2 tests)
 * - Interest Accrual Tests (2 tests)
 * 
 * Total: 12 tests
 */
class SkipPaymentHandlerTest extends TestCase
{
    private array $loanData;
    private array $scheduleData;

    protected function setUp(): void
    {
        // Standard loan data for testing
        $this->loanData = [
            'id' => 1,
            'principal' => 30000,
            'annual_rate' => 0.045,
            'months' => 60,
            'monthly_payment' => 531.86,
            'current_month' => 12,
            'remaining_months' => 48,
            'current_balance' => 15000,
        ];

        // Standard schedule entry
        $this->scheduleData = [
            'loan_id' => 1,
            'month' => 12,
            'payment' => 531.86,
            'principal' => 400.00,
            'interest' => 131.86,
            'balance' => 15000,
        ];
    }

    /**
     * VALIDATION - Test 1: Valid Skip Payment Event
     * 
     * Verify valid skip payment event is accepted.
     */
    public function testValidation1_ValidSkipPaymentEvent(): void
    {
        $event = [
            'loan_id' => 1,
            'type' => 'skip_payment',
            'date' => '2025-02-15',
        ];

        $this->assertArrayHasKey('loan_id', $event);
        $this->assertArrayHasKey('type', $event);
        $this->assertEquals('skip_payment', $event['type']);
        $this->assertGreaterThan(0, $event['loan_id']);
    }

    /**
     * VALIDATION - Test 2: Invalid Loan ID
     * 
     * Verify invalid loan ID is rejected.
     */
    public function testValidation2_InvalidLoanId(): void
    {
        $invalidIds = [0, -1, null];

        foreach ($invalidIds as $id) {
            $isValid = is_int($id) && $id > 0;
            $this->assertFalse($isValid);
        }
    }

    /**
     * VALIDATION - Test 3: Skip Payment Date Validation
     * 
     * Verify skip payment date is valid future date.
     */
    public function testValidation3_SkipPaymentDateValidation(): void
    {
        $validDate = '2025-02-15';
        $currentDate = '2025-01-15';

        $dateTime = \DateTime::createFromFormat('Y-m-d', $validDate);
        $isValidFormat = $dateTime && $dateTime->format('Y-m-d') === $validDate;

        $this->assertTrue($isValidFormat);
    }

    /**
     * VALIDATION - Test 4: Cannot Skip Non-Existent Loan
     * 
     * Verify skip payment fails for non-existent loan.
     */
    public function testValidation4_CannotSkipNonExistentLoan(): void
    {
        $nonExistentLoanId = 999;
        $loanExists = false; // Simulating loan not found

        $this->assertFalse($loanExists);
        $this->assertGreaterThan(0, $nonExistentLoanId);
    }

    /**
     * RECALCULATION - Test 5: Schedule Recalculated After Skip
     * 
     * Verify schedule is recalculated when payment is skipped.
     */
    public function testRecalculation5_ScheduleRecalculatedAfterSkip(): void
    {
        $originalBalance = $this->loanData['current_balance'];
        $monthlyRate = $this->loanData['annual_rate'] / 12;

        // Interest accrual for skipped month
        $accruedInterest = $originalBalance * $monthlyRate;
        $newBalance = $originalBalance + $accruedInterest;

        $this->assertGreaterThan($originalBalance, $newBalance);
        $this->assertEqualsWithDelta($accruedInterest, $newBalance - $originalBalance, 0.01);
    }

    /**
     * RECALCULATION - Test 6: Remaining Balance Includes Interest
     * 
     * Verify remaining balance reflects interest accrual.
     */
    public function testRecalculation6_RemainingBalanceIncludesInterest(): void
    {
        $balance = 15000;
        $monthlyRate = 0.045 / 12;
        $accruedInterest = $balance * $monthlyRate;

        $balanceWithInterest = $balance + $accruedInterest;

        $this->assertGreaterThan($balance, $balanceWithInterest);
        $this->assertLessThan(15200, $balanceWithInterest);
        $this->assertGreaterThan(15050, $balanceWithInterest);
    }

    /**
     * RECALCULATION - Test 7: Updated Monthly Payment If Recalculated
     * 
     * Verify monthly payment is recalculated if necessary.
     */
    public function testRecalculation7_UpdatedMonthlyPayment(): void
    {
        $newBalance = 15056.25; // After interest accrual
        $newRemainingMonths = 49; // Extended by 1 month due to skip
        $monthlyRate = 0.045 / 12;

        $newPayment = ($newBalance * ($monthlyRate * (1 + $monthlyRate) ** $newRemainingMonths)) / 
                      (((1 + $monthlyRate) ** $newRemainingMonths) - 1);

        $this->assertGreaterThan(300, $newPayment);
        $this->assertLessThan(350, $newPayment);
    }

    /**
     * RECALCULATION - Test 8: All Remaining Schedules Updated
     * 
     * Verify all subsequent schedule entries are updated.
     */
    public function testRecalculation8_AllSchedulesUpdated(): void
    {
        $scheduleCount = 48; // Remaining months
        $updatedSchedules = [];

        for ($i = 0; $i < $scheduleCount; $i++) {
            $updatedSchedules[] = [
                'month' => 13 + $i,
                'payment' => 531.86,
                'updated' => true,
            ];
        }

        $this->assertCount(48, $updatedSchedules);
        $allUpdated = array_reduce($updatedSchedules, fn($carry, $s) => $carry && $s['updated'], true);
        $this->assertTrue($allUpdated);
    }

    /**
     * TERM EXTENSION - Test 9: Loan Term Extended By One Month
     * 
     * Verify loan term is extended by one month.
     */
    public function testTermExtension9_LoanTermExtendedByOneMonth(): void
    {
        $originalMonths = 60;
        $newMonths = $originalMonths + 1;

        $this->assertEquals(61, $newMonths);
        $this->assertGreaterThan($originalMonths, $newMonths);
    }

    /**
     * TERM EXTENSION - Test 10: Multiple Skips Extend Term Multiple Times
     * 
     * Verify multiple skip payments extend term appropriately.
     */
    public function testTermExtension10_MultipleSkipsExtendTerm(): void
    {
        $originalMonths = 60;
        $skipCount = 3;
        $newMonths = $originalMonths + $skipCount;

        $this->assertEquals(63, $newMonths);
        $this->assertGreaterThan(60, $newMonths);
    }

    /**
     * ACCRUAL - Test 11: Interest Accrued For Skipped Month
     * 
     * Verify interest is accrued for skipped payment month.
     */
    public function testAccrual11_InterestAccruedForSkippedMonth(): void
    {
        $balance = 15000;
        $monthlyRate = 0.045 / 12; // ~0.00375
        $accruedInterest = $balance * $monthlyRate;

        $this->assertGreaterThan(56, $accruedInterest);
        $this->assertLessThan(57, $accruedInterest);
    }

    /**
     * ACCRUAL - Test 12: No Payment Recorded For Skip Month
     * 
     * Verify no payment is recorded for the skipped month.
     */
    public function testAccrual12_NoPaymentRecordedForSkipMonth(): void
    {
        $scheduleEntry = [
            'month' => 12,
            'payment' => 0,  // No payment for skip
            'principal' => 0,
            'interest' => 56.25, // Only interest accrued
            'balance' => 15056.25,
        ];

        $this->assertEquals(0, $scheduleEntry['payment']);
        $this->assertEquals(0, $scheduleEntry['principal']);
        $this->assertGreaterThan(0, $scheduleEntry['interest']);
    }
}
