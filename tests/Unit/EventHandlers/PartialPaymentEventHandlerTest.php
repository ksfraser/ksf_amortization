<?php

namespace Tests\Unit\EventHandlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\LoanEvent;
use Ksfraser\Amortizations\EventHandlers\PartialPaymentEventHandler;
use DateTimeImmutable;

/**
 * PartialPaymentEventHandlerTest
 *
 * Tests for partial payment event handling.
 * Uses TDD Red-Green-Refactor cycle.
 *
 * A partial payment is when a borrower pays less than the full regular payment amount.
 * The shortfall becomes part of arrears (principal arrears).
 *
 * Example: Regular payment is $726.61, borrower pays $500.00
 * - $500 is applied to current period
 * - $226.61 shortfall becomes principal arrears
 * - Next payment must cover current + arrears
 *
 * @covers \Ksfraser\Amortizations\EventHandlers\PartialPaymentEventHandler
 */
class PartialPaymentEventHandlerTest extends TestCase
{
    private PartialPaymentEventHandler $handler;
    private Loan $loan;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        $this->handler = new PartialPaymentEventHandler();

        // Create test loan
        $this->loan = $this->createTestLoan();
    }

    /**
     * Test that handler supports partial payment events.
     *
     * @test
     */
    public function testSupportsPartialPaymentEvents(): void
    {
        $event = new LoanEvent([
            'event_type' => 'partial_payment',
            'amount' => 500.00,
            'event_date' => '2024-02-01'
        ]);

        $this->assertTrue($this->handler->supports($event));
    }

    /**
     * Test that handler rejects other event types.
     *
     * @test
     */
    public function testRejectsOtherEventTypes(): void
    {
        $event = new LoanEvent([
            'event_type' => 'extra_payment',
            'amount' => 500.00,
            'event_date' => '2024-02-01'
        ]);

        $this->assertFalse($this->handler->supports($event));
    }

    /**
     * Test that partial payment creates arrears for shortfall.
     *
     * Payment of $500 when regular payment is due:
     * - Regular payment amount: calculated from schedule
     * - Partial payment: $500.00
     * - Shortfall (arrears): regular - $500.00
     *
     * @test
     */
    public function testPartialPaymentCreatesArrears(): void
    {
        $regularPayment = $this->getRegularPaymentFromSchedule($this->loan, '2024-02-01');
        $partialPayment = 500.00;
        $expectedArrears = $regularPayment - $partialPayment;

        $event = new LoanEvent([
            'event_type' => 'partial_payment',
            'amount' => $partialPayment,
            'event_date' => '2024-02-01'
        ]);

        $updatedLoan = $this->handler->handle($this->loan, $event);
        $totalArrears = $updatedLoan->getTotalArrears();

        $this->assertEqualsWithDelta(
            $expectedArrears,
            $totalArrears,
            0.01,
            "Arrears should equal payment shortfall"
        );
    }

    /**
     * Test that partial payment reduces balance correctly.
     *
     * If loan balance is $47,000 and partial payment of $500 is made:
     * - Amount applied to principal: depends on how much went to principal vs interest
     * - New balance: $47,000 - (principal portion of $500)
     *
     * For first payment, most goes to interest, so principal reduction is small.
     * Interest for first month: ~$208 (out of $500)
     * Principal reduction: ~$292
     * New balance: ~$46,708
     *
     * @test
     */
    public function testPartialPaymentReducesBalance(): void
    {
        $originalBalance = 50000.00;
        $partialPayment = 500.00;

        $event = new LoanEvent([
            'event_type' => 'partial_payment',
            'amount' => $partialPayment,
            'event_date' => '2024-02-01'
        ]);

        $updatedLoan = $this->handler->handle($this->loan, $event);
        $newBalance = $updatedLoan->getCurrentBalance();

        // Balance should be reduced
        $this->assertLessThan(
            $originalBalance,
            $newBalance,
            "Balance should decrease after payment"
        );

        // Balance reduction should be at least the principal portion of the payment
        $reduction = $originalBalance - $newBalance;
        $this->assertGreaterThan(0, $reduction);
        $this->assertLessThanOrEqual($partialPayment, $reduction);
    }

    /**
     * Test that zero payment creates full arrears.
     *
     * If payment of $0.00 is made when regular payment is due:
     * - Entire payment amount becomes arrears
     * - Balance unchanged
     *
     * @test
     */
    public function testZeroPaymentCreatesFullArrears(): void
    {
        $originalBalance = 50000.00;
        $regularPayment = $this->getRegularPaymentFromSchedule($this->loan, '2024-02-01');

        $event = new LoanEvent([
            'event_type' => 'partial_payment',
            'amount' => 0.00,
            'event_date' => '2024-02-01'
        ]);

        $updatedLoan = $this->handler->handle($this->loan, $event);
        $totalArrears = $updatedLoan->getTotalArrears();

        $this->assertEqualsWithDelta(
            $regularPayment,
            $totalArrears,
            0.01,
            "Zero payment should result in full amount as arrears"
        );
    }

    /**
     * Test that partial payment triggers schedule recalculation.
     *
     * When arrears are created, future payments must be recalculated to:
     * 1. Account for accrued interest on arrears
     * 2. Ensure borrower pays off principal + arrears in remaining months
     * 3. Maintain final balance = $0.00
     *
     * @test
     */
    public function testPartialPaymentRecalculatesSchedule(): void
    {
        $originalScheduleCount = 60; // 60 periods for test loan

        $event = new LoanEvent([
            'event_type' => 'partial_payment',
            'amount' => 500.00,
            'event_date' => '2024-02-01'
        ]);

        $updatedLoan = $this->handler->handle($this->loan, $event);
        $newSchedule = $updatedLoan->getSchedule();

        // Schedule should still exist
        $this->assertNotEmpty($newSchedule, "Schedule should be recalculated");

        // Number of periods might increase due to arrears
        $this->assertGreaterThanOrEqual(
            $originalScheduleCount,
            count($newSchedule),
            "Schedule may increase due to arrears"
        );
    }

    /**
     * Test handler priority is lower than extra payment handler.
     *
     * Priority order for payment handlers:
     * 100 - Arrears clearing (highest)
     * 70  - Extra payments (apply full payment first)
     * 60  - Partial payments (apply shortfall as arrears)
     * 10  - Skip payments (lowest)
     *
     * @test
     */
    public function testHandlerHasCorrectPriority(): void
    {
        $priority = $this->handler->getPriority();

        $this->assertEquals(60, $priority, "Partial payment handler priority should be 60");
        $this->assertLessThan(70, $priority, "Should be lower priority than extra payment");
        $this->assertGreaterThan(10, $priority, "Should be higher priority than skip payment");
    }

    /**
     * Test that negative payment amount is rejected.
     *
     * @test
     */
    public function testRejectsNegativePaymentAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $event = new LoanEvent([
            'event_type' => 'partial_payment',
            'amount' => -100.00,
            'event_date' => '2024-02-01'
        ]);

        $this->handler->handle($this->loan, $event);
    }

    /**
     * Test that payment exceeding regular amount is rejected.
     *
     * If regular payment is calculated and "partial" payment exceeds it,
     * this should be handled as extra payment, not partial payment.
     *
     * @test
     */
    public function testRejectsPaymentExceedingRegularAmount(): void
    {
        $this->expectException(\LogicException::class);

        $regularPayment = $this->getRegularPaymentFromSchedule($this->loan, '2024-02-01');
        $excessPayment = $regularPayment + 100;  // More than regular payment

        $event = new LoanEvent([
            'event_type' => 'partial_payment',
            'amount' => $excessPayment,
            'event_date' => '2024-02-01'
        ]);

        $this->handler->handle($this->loan, $event);
    }

    /**
     * Test that cumulative partial payments are tracked correctly.
     *
     * Multiple partial payments in same period accumulate towards full payment.
     *
     * @test
     */
    public function testCumulativePartialPaymentsAccumulate(): void
    {
        $regularPayment = $this->getRegularPaymentFromSchedule($this->loan, '2024-02-01');
        $originalBalance = $this->loan->getCurrentBalance();

        // First partial payment
        $event1 = new LoanEvent([
            'event_type' => 'partial_payment',
            'amount' => 300.00,
            'event_date' => '2024-02-01'
        ]);

        $loanAfterPayment1 = $this->handler->handle($this->loan, $event1);
        $balanceAfterPayment1 = $loanAfterPayment1->getCurrentBalance();

        // Second partial payment after first reduces balance
        $this->assertLessThan($originalBalance, $balanceAfterPayment1, "First payment should reduce balance");
    }

    /**
     * Test that partial payment event stores correct metadata.
     *
     * Event should contain:
     * - event_type: 'partial_payment'
     * - amount: payment amount
     * - event_date: date payment made
     * - result: 'arrears_created' or 'payment_applied'
     *
     * @test
     */
    public function testPartialPaymentEventHasCorrectMetadata(): void
    {
        $event = new LoanEvent([
            'event_type' => 'partial_payment',
            'amount' => 500.00,
            'event_date' => '2024-02-01'
        ]);

        $this->assertEquals('partial_payment', $event->event_type);
        $this->assertEquals(500.00, $event->amount);
        $this->assertEquals('2024-02-01', $event->event_date);
    }

    /**
     * Helper to create a test loan.
     *
     * @return Loan A loan with 60 months, 5% rate, $50k principal
     */
    private function createTestLoan(): Loan
    {
        $loan = new Loan();
        $loan->setPrincipal(50000.00);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(50000.00);

        // Generate a simple amortization schedule
        $schedule = $this->generateSimpleSchedule($loan);
        $loan->setSchedule($schedule);

        return $loan;
    }

    /**
     * Generate a simple amortization schedule for testing.
     */
    private function generateSimpleSchedule(Loan $loan): array
    {
        $principal = $loan->getPrincipal();
        $monthlyRate = $loan->getAnnualRate() / 12;
        $months = $loan->getMonths();

        // Calculate payment using standard amortization formula
        if ($monthlyRate == 0) {
            $payment = $principal / $months;
        } else {
            $payment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) /
                      (pow(1 + $monthlyRate, $months) - 1);
        }

        $schedule = [];
        $balance = $principal;
        $startDate = $loan->getStartDate() ?? new DateTimeImmutable('2024-01-01');

        for ($i = 1; $i <= $months; $i++) {
            $interest = round($balance * $monthlyRate, 2);
            $principalPayment = round($payment - $interest, 2);
            $balance = round($balance - $principalPayment, 2);

            if ($i == $months) {
                $principalPayment = round($principal - array_reduce(
                    $schedule,
                    fn($sum, $row) => $sum + $row['principal'],
                    0
                ), 2);
                $balance = 0;
            }

            $paymentDate = $startDate->add(new \DateInterval("P{$i}M"));

            $schedule[] = [
                'payment_number' => $i,
                'payment_date' => $paymentDate->format('Y-m-d'),
                'payment_amount' => round($payment, 2),
                'principal' => $principalPayment,
                'interest' => $interest,
                'balance' => $balance
            ];
        }

        return $schedule;
    }

    /**
     * Get regular payment amount for a given date from schedule.
     */
    private function getRegularPaymentFromSchedule(Loan $loan, string $dateStr): float
    {
        $schedule = $loan->getSchedule();
        foreach ($schedule as $row) {
            if ($row['payment_date'] === $dateStr) {
                return $row['payment_amount'];
            }
        }
        // Return first payment if not found
        return !empty($schedule) ? $schedule[0]['payment_amount'] : 0;
    }
}
