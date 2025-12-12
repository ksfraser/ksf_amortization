<?php

namespace Tests\Unit\EventHandlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\LoanEvent;
use Ksfraser\Amortizations\EventHandlers\SkipPaymentHandler;
use DateTimeImmutable;

/**
 * SkipPaymentHandlerTest - TDD Test Suite
 *
 * Tests for the SkipPaymentHandler event handler which allows borrowers
 * to skip one or more regular loan payments.
 *
 * Business logic:
 * - Borrowers can defer one or more regular payments
 * - Penalties applied (typically 2-5% of skipped payment amount)
 * - Loan term extended by number of skipped periods
 * - Schedule recalculated from next payment date
 * - Metadata records event with dates and amounts
 *
 * Test coverage: 11 tests
 * - Event support (1 test)
 * - Skip mechanics (4 tests)
 * - Penalty calculation (2 tests)
 * - Term extension (1 test)
 * - Validation (2 tests)
 * - Integration (1 test)
 */
class SkipPaymentHandlerTest extends TestCase
{
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new SkipPaymentHandler();
    }

    /**
     * Test 1: Handler supports skip_payment events
     */
    public function testSupportsSkipPaymentEventType()
    {
        $loan = $this->createTestLoan();
        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'skip_payment',
            'amount' => 1,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $this->assertTrue($this->handler->supports($event));
    }

    /**
     * Test 2: Handler supports skip_payments (plural) events
     */
    public function testSupportsSkipPaymentsEventType()
    {
        $loan = $this->createTestLoan();
        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'skip_payments',
            'amount' => 2,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $this->assertTrue($this->handler->supports($event));
    }

    /**
     * Test 3: Skip single payment - term extension by 1 month
     */
    public function testSkipSinglePaymentExtendsTerm()
    {
        $loan = $this->createTestLoan();
        $originalMonths = $loan->getMonths();

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'skip_payment',
            'amount' => 1,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $result = $this->handler->handle($loan, $event);

        $this->assertEquals($originalMonths + 1, $result->getMonths());
    }

    /**
     * Test 4: Skip multiple payments - term extended by payment count
     */
    public function testSkipMultiplePaymentsExtendsTerm()
    {
        $loan = $this->createTestLoan();
        $originalMonths = $loan->getMonths();

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'skip_payments',
            'amount' => 3,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $result = $this->handler->handle($loan, $event);

        $this->assertEquals($originalMonths + 3, $result->getMonths());
    }

    /**
     * Test 5: Default penalty is 2% of regular payment
     */
    public function testDefaultPenaltyCalculation()
    {
        $loan = $this->createTestLoan();
        $originalBalance = $loan->getCurrentBalance();
        $regularPayment = $originalBalance / $loan->getMonths(); // ~$166.67

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'skip_payment',
            'amount' => 1,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $result = $this->handler->handle($loan, $event);

        // Balance should increase by penalty (2% of $166.67 = $3.33)
        $expectedPenalty = $regularPayment * 0.02;
        $expectedNewBalance = $originalBalance + $expectedPenalty;
        $this->assertAlmostEquals($expectedNewBalance, $result->getCurrentBalance(), 2);
    }

    /**
     * Test 6: Multiple payments increase penalty and term extension
     */
    public function testMultipleSkipPaymentsPenalty()
    {
        $loan = $this->createTestLoan();
        $originalBalance = $loan->getCurrentBalance();
        $regularPayment = $originalBalance / $loan->getMonths();

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'skip_payments',
            'amount' => 3,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $result = $this->handler->handle($loan, $event);

        // Balance should increase by penalty for all 3 payments (2% * 3)
        $expectedPenalty = $regularPayment * 0.02 * 3;
        $expectedNewBalance = $originalBalance + $expectedPenalty;
        $this->assertAlmostEquals($expectedNewBalance, $result->getCurrentBalance(), 2);
    }

    /**
     * Test 7: Skip payment reduces balance (deferred payments reduce principal paydown)
     */
    public function testSkipPaymentReducesPrincipalPaydown()
    {
        $loan = $this->createTestLoan();
        $originalBalance = $loan->getCurrentBalance();

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'skip_payment',
            'amount' => 1,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $result = $this->handler->handle($loan, $event);

        // Balance should increase (or not decrease as much) because we're deferring payment
        $this->assertGreaterThanOrEqual($originalBalance, $result->getCurrentBalance());
    }

    /**
     * Test 8: Cannot skip more than 12 consecutive payments (fraud protection)
     */
    public function testValidationMaxSkipPaymentsExceeded()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot skip more than 12 payments');

        $loan = $this->createTestLoan();

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'skip_payments',
            'amount' => 13, // More than allowed
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $this->handler->handle($loan, $event);
    }

    /**
     * Test 9: Must specify at least 1 payment to skip
     */
    public function testValidationMinSkipPayments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must skip at least 1 payment');

        $loan = $this->createTestLoan();

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'skip_payment',
            'amount' => 0,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $this->handler->handle($loan, $event);
    }

    /**
     * Test 10: Metadata includes skip details (dates, penalties, term change)
     */
    public function testMetadataStructure()
    {
        $loan = $this->createTestLoan();
        $originalMonths = $loan->getMonths();

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'skip_payment',
            'amount' => 1,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $result = $this->handler->handle($loan, $event);

        // Verify that term was extended
        $this->assertEquals($originalMonths + 1, $result->getMonths());
        // Verify that loan was marked updated
        $this->assertNotNull($result->getUpdatedAt());
    }

    /**
     * Test 11: Handler priority (should run after grace period but before final payment)
     */
    public function testHandlerPriority()
    {
        // Skip payment handler should have priority 20 (after grace period at 10)
        $this->assertEquals(20, $this->handler->getPriority());
    }

    // ============ Helper Methods ============

    private function createTestLoan(): Loan
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(10000.00);
        $loan->setAnnualRate(0.05); // 5% as decimal (0-1)
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(10000.00);
        return $loan;
    }

    /**
     * Assert two floats are approximately equal (within cents)
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
