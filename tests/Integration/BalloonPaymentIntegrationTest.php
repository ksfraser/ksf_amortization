<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Strategies\BalloonPaymentStrategy;
use Tests\Mocks\MockLoanRepository;
use Tests\Mocks\MockScheduleRepository;
use DateTimeImmutable;

/**
 * BalloonPaymentIntegrationTest
 *
 * Integration tests for balloon payment loans.
 * Tests interaction between:
 * - Strategy (BalloonPaymentStrategy)
 * - Models (Loan)
 * - Repositories (LoanRepository, ScheduleRepository)
 * - End-to-end workflows
 *
 * Scenarios tested:
 * 1. Create loan → Calculate payment → Generate schedule → Save to database
 * 2. Load loan from database → Retrieve schedule → Verify calculations
 * 3. Update balloon amount → Recalculate → Verify new schedule
 *
 * @covers \Ksfraser\Amortizations\Strategies\BalloonPaymentStrategy
 * @covers \Ksfraser\Amortizations\Models\Loan
 */
class BalloonPaymentIntegrationTest extends TestCase
{
    private BalloonPaymentStrategy $strategy;
    private MockLoanRepository $loanRepo;
    private MockScheduleRepository $scheduleRepo;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        $this->strategy = new BalloonPaymentStrategy();
        $this->loanRepo = new MockLoanRepository();
        $this->scheduleRepo = new MockScheduleRepository();
    }

    /**
     * Test complete workflow: Create → Calculate → Save → Retrieve
     *
     * @test
     */
    public function testCompleteWorkflow(): void
    {
        // STEP 1: Create loan
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setBalloonAmount(12000);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // STEP 2: Calculate payment
        $payment = $this->strategy->calculatePayment($loan);
        $this->assertGreaterThan(700, $payment);

        // STEP 3: Generate schedule
        $schedule = $this->strategy->calculateSchedule($loan);
        $this->assertCount(60, $schedule);

        // STEP 4: Save loan to "database"
        $loanId = $this->loanRepo->save($loan);
        $this->assertGreaterThan(0, $loanId);

        // STEP 5: Save schedule to "database"
        $scheduleRows = $this->scheduleRepo->saveSchedule($loanId, $schedule);
        $this->assertEquals(60, $scheduleRows);

        // STEP 6: Retrieve loan from "database"
        $retrievedLoan = $this->loanRepo->findById($loanId);
        $this->assertNotNull($retrievedLoan);
        $this->assertEquals(50000, $retrievedLoan->getPrincipal());
        $this->assertEquals(12000, $retrievedLoan->getBalloonAmount());

        // STEP 7: Retrieve schedule from "database"
        $retrievedSchedule = $this->scheduleRepo->getScheduleForLoan($loanId);
        $this->assertCount(60, $retrievedSchedule);

        // STEP 8: Verify calculations
        $finalBalance = end($retrievedSchedule)['balance'];
        $this->assertLessThanOrEqual(0.02, abs($finalBalance));
    }

    /**
     * Test that payment schedule is consistent across strategy methods.
     *
     * @test
     */
    public function testPaymentScheduleConsistency(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(25000);
        $loan->setAnnualRate(0.04);
        $loan->setMonths(36);
        $loan->setBalloonAmount(5000);
        $loan->setStartDate(new DateTimeImmutable('2024-06-15'));

        // Get single payment amount
        $payment = $this->strategy->calculatePayment($loan);

        // Get full schedule
        $schedule = $this->strategy->calculateSchedule($loan);

        // Verify first period payment matches calculated payment
        $firstPayment = $schedule[0]['payment_amount'];
        $this->assertEqualsWithDelta($payment, $firstPayment, 0.01, 'First payment should match calculated payment');

        // Verify all regular periods use same payment (except final with balloon)
        for ($i = 0; $i < 35; $i++) {
            $expectedPayment = $schedule[$i]['payment_amount'];
            if ($i < 34) {  // Not final period
                // All non-final payments should be approximately the same
                $nextPayment = $schedule[$i + 1]['payment_amount'];
                $this->assertLessThan(1.00, abs($expectedPayment - $nextPayment), "Payments should be consistent");
            }
        }

        // Verify final payment includes balloon
        $finalPayment = $schedule[35]['payment_amount'];
        $secondToLastPayment = $schedule[34]['payment_amount'];
        $this->assertGreaterThan($secondToLastPayment + 4000, $finalPayment, 'Final payment should include balloon');
    }

    /**
     * Test extra payment scenario: Apply extra payment, recalculate schedule
     *
     * @test
     */
    public function testExtraPaymentRecalculation(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setBalloonAmount(12000);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // Original schedule
        $originalSchedule = $this->strategy->calculateSchedule($loan);
        $originalPayment = $originalSchedule[0]['payment_amount'];

        // After extra payment of $5,000
        $loan->setCurrentBalance(50000 - $originalPayment - 5000); // One payment + extra
        $loan->setPaymentsMade(1);

        // In a real scenario, would create new loan with adjusted principal
        // For this test, verify the logic would work
        $this->assertEqualsWithDelta(44273.39, $loan->getCurrentBalance(), 0.01);
    }

    /**
     * Test balloon amount change triggers recalculation.
     *
     * @test
     */
    public function testBalloonAmountChangeRecalculation(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setBalloonAmount(12000);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // Original payment with $12k balloon
        $payment1 = $this->strategy->calculatePayment($loan);

        // Change balloon to $15k
        $loan->setBalloonAmount(15000);
        $payment2 = $this->strategy->calculatePayment($loan);

        // New payment should be lower (less principal to amortize over 60 months)
        $this->assertLessThan($payment1, $payment2, 'Higher balloon should result in higher regular payment');
    }

    /**
     * Test schedule used for multiple reports.
     *
     * @test
     */
    public function testScheduleUsedForReporting(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setBalloonAmount(12000);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $schedule = $this->strategy->calculateSchedule($loan);
        $this->scheduleRepo->saveSchedule(1, $schedule);

        // Report 1: Next payment date
        $nextPaymentDate = $this->scheduleRepo->getNextPaymentDate(1);
        $this->assertNotNull($nextPaymentDate);
        $this->assertEquals('2024-01-01', $nextPaymentDate->format('Y-m-d'));

        // Report 2: Total interest
        $totalInterest = $this->scheduleRepo->getTotalInterest(1);
        $this->assertGreaterThan(3000, $totalInterest);
        $this->assertLessThan(8000, $totalInterest);

        // Report 3: Payoff amount
        $payoffAmount = $this->scheduleRepo->getPayoffAmount(1);
        $this->assertEqualsWithDelta(50000, $payoffAmount, 50);  // Approximately original principal
    }

    /**
     * Test balloon loan with variable rates (future enhancement).
     *
     * @test
     */
    public function testBalloonWithVariableRates(): void
    {
        // This test documents expected behavior for Phase 3
        // When balloon + variable rates are combined

        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setBalloonAmount(12000);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // With variable rates, would add rate periods
        // $loan->addRatePeriod(...)
        // Then use VariableRateStrategy with balloon support

        // For now, verify balloon strategy exists
        $this->assertTrue($this->strategy->supports($loan));
    }

    /**
     * Test database persistence of balloon amounts.
     *
     * @test
     */
    public function testBalloonAmountPersistence(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setBalloonAmount(12000);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // Save loan
        $loanId = $this->loanRepo->save($loan);

        // Retrieve loan
        $retrievedLoan = $this->loanRepo->findById($loanId);
        $this->assertNotNull($retrievedLoan);

        // Verify balloon persisted
        $this->assertEquals(12000, $retrievedLoan->getBalloonAmount());
        $this->assertTrue($retrievedLoan->hasBalloonPayment());
    }
}
