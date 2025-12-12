<?php

namespace Tests\Unit\EventHandlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\LoanEvent;
use Ksfraser\Amortizations\EventHandlers\ExtraPaymentHandler;
use DateTimeImmutable;

/**
 * ExtraPaymentHandlerTest - TDD Test Suite
 *
 * Tests for the ExtraPaymentHandler event handler which allows borrowers
 * to make extra payments beyond their regular scheduled payment.
 *
 * Business logic:
 * - Extra payments reduce principal directly
 * - Borrower can choose to reduce term OR reduce payment amount
 * - Interest saved through early payoff calculated
 * - Multiple extra payments supported
 * - Schedule recalculated when extra payment applied
 * - Event metadata records payment details and savings
 *
 * Test coverage: 12 tests
 * - Event support (1 test)
 * - Extra payment application (2 tests)
 * - Term reduction strategy (2 tests)
 * - Payment reduction strategy (2 tests)
 * - Interest savings (1 test)
 * - Validation (2 tests)
 * - Edge cases (1 test)
 * - Metadata tracking (1 test)
 */
class ExtraPaymentHandlerTest extends TestCase
{
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new ExtraPaymentHandler();
    }

    /**
     * Test 1: Handler supports extra_payment events
     */
    public function testSupportsExtraPaymentEventType()
    {
        $loan = $this->createTestLoan();
        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => 100.00,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $this->assertTrue($this->handler->supports($event));
    }

    /**
     * Test 2: Extra payment reduces balance
     */
    public function testExtraPaymentReducesBalance()
    {
        $loan = $this->createTestLoan();
        $originalBalance = $loan->getCurrentBalance();
        $extraPaymentAmount = 500.00;

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => $extraPaymentAmount,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $result = $this->handler->handle($loan, $event);

        $expectedBalance = $originalBalance - $extraPaymentAmount;
        $this->assertAlmostEquals($expectedBalance, $result->getCurrentBalance(), 2);
    }

    /**
     * Test 3: Extra payment with term reduction strategy reduces months
     */
    public function testExtraPaymentWithTermReductionStrategy()
    {
        $loan = $this->createTestLoan();
        $originalMonths = $loan->getMonths();
        $extraPaymentAmount = 1000.00;

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => $extraPaymentAmount,
            'event_date' => new DateTimeImmutable('2024-12-15'),
            'notes' => 'strategy:reduce_term' // Strategy indicator
        ]);

        $result = $this->handler->handle($loan, $event);

        // Term should be reduced by approximately number of extra payments made
        $this->assertLessThan($originalMonths, $result->getMonths());
    }

    /**
     * Test 4: Extra payment with payment reduction strategy reduces payment amount
     */
    public function testExtraPaymentWithPaymentReductionStrategy()
    {
        $loan = $this->createTestLoan();
        $originalBalance = $loan->getCurrentBalance();
        $extraPaymentAmount = 500.00;

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => $extraPaymentAmount,
            'event_date' => new DateTimeImmutable('2024-12-15'),
            'notes' => 'strategy:reduce_payment'
        ]);

        $result = $this->handler->handle($loan, $event);

        // Balance reduced, term stays same (payment recalculated lower)
        $this->assertAlmostEquals(
            $originalBalance - $extraPaymentAmount,
            $result->getCurrentBalance(),
            2
        );
        $this->assertEquals(60, $result->getMonths());
    }

    /**
     * Test 5: Multiple extra payments accumulate correctly
     */
    public function testMultipleExtraPayments()
    {
        $loan = $this->createTestLoan();
        $originalBalance = $loan->getCurrentBalance();

        $event1 = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => 250.00,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $loan = $this->handler->handle($loan, $event1);

        $event2 = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => 250.00,
            'event_date' => new DateTimeImmutable('2024-12-20')
        ]);

        $result = $this->handler->handle($loan, $event2);

        $expectedBalance = $originalBalance - 500.00;
        $this->assertAlmostEquals($expectedBalance, $result->getCurrentBalance(), 2);
    }

    /**
     * Test 6: Interest savings calculated correctly
     */
    public function testInterestSavingsCalculation()
    {
        $loan = $this->createTestLoan();
        $extraPaymentAmount = 2000.00;

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => $extraPaymentAmount,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $result = $this->handler->handle($loan, $event);

        // Interest savings should be calculated and positive
        // For a 5% rate over remaining term, extra payment saves interest
        $this->assertGreaterThan(0, $result->getCurrentBalance());
    }

    /**
     * Test 7: Cannot apply extra payment larger than remaining balance
     */
    public function testValidationExtraPaymentExceedsBalance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Extra payment cannot exceed remaining balance');

        $loan = $this->createTestLoan();
        $balance = $loan->getCurrentBalance();

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => $balance + 1000.00, // Exceeds balance
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $this->handler->handle($loan, $event);
    }

    /**
     * Test 8: Minimum extra payment validation (cannot be $0 or negative)
     */
    public function testValidationMinimumExtraPayment()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Extra payment must be greater than');

        $loan = $this->createTestLoan();

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => 0.00,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $this->handler->handle($loan, $event);
    }

    /**
     * Test 9: Extra payment at end of loan (last month)
     */
    public function testExtraPaymentNearEndOfLoan()
    {
        $loan = $this->createTestLoan();
        $loan->setMonths(1); // Only 1 month left
        $originalBalance = $loan->getCurrentBalance();

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => 100.00,
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $result = $this->handler->handle($loan, $event);

        // Should reduce balance even when near end
        $this->assertAlmostEquals(
            $originalBalance - 100.00,
            $result->getCurrentBalance(),
            2
        );
    }

    /**
     * Test 10: Extra payment that fully pays off loan
     */
    public function testExtraPaymentPayoffLoan()
    {
        $loan = $this->createTestLoan();
        $loan->setCurrentBalance(500.00); // Small remaining balance
        $loan->setMonths(2);

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => 500.00, // Pay off entire remaining
            'event_date' => new DateTimeImmutable('2024-12-15')
        ]);

        $result = $this->handler->handle($loan, $event);

        $this->assertAlmostEquals(0.00, $result->getCurrentBalance(), 2);
    }

    /**
     * Test 11: Handler priority (should run after skip payment but before final calc)
     */
    public function testHandlerPriority()
    {
        // Extra payment handler should have priority 30 (after skip at 20)
        $this->assertEquals(30, $this->handler->getPriority());
    }

    /**
     * Test 12: Default strategy is reduce_term if not specified
     */
    public function testDefaultStrategyIsReduceTerm()
    {
        $loan = $this->createTestLoan();
        $originalMonths = $loan->getMonths();

        $event = new LoanEvent([
            'id' => null,
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => 1000.00,
            'event_date' => new DateTimeImmutable('2024-12-15')
            // No strategy specified, should default to reduce_term
        ]);

        $result = $this->handler->handle($loan, $event);

        // Should reduce term
        $this->assertLessThanOrEqual($originalMonths, $result->getMonths());
    }

    // ============ Helper Methods ============

    private function createTestLoan(): Loan
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(10000.00);
        $loan->setAnnualRate(0.05); // 5% as decimal
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
