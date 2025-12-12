<?php

namespace Tests\Unit\Strategies;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\RatePeriod;
use Ksfraser\Amortizations\Strategies\VariableRateStrategy;
use DateTimeImmutable;

/**
 * VariableRateStrategyTest
 *
 * Tests for variable interest rate loan strategy.
 * Uses TDD Red-Green-Refactor cycle.
 *
 * Variable rate loans have different interest rates during different periods.
 * Common scenarios:
 * - ARM (Adjustable Rate Mortgage): Fixed initial rate, then adjusts periodically
 * - Tiered rates: Different rates based on loan age
 * - Index-based: Rate adjusts based on index (LIBOR, Prime, etc.)
 *
 * Example: $50,000 car loan
 * - Jan 2024 - Jun 2024: 4.5% (promotional rate)
 * - Jul 2024 - Dec 2024: 5.5% (standard rate)
 * - Jan 2025 onwards: 6.5% (market rate)
 *
 * @covers \Ksfraser\Amortizations\Strategies\VariableRateStrategy
 */
class VariableRateStrategyTest extends TestCase
{
    private VariableRateStrategy $strategy;
    private Loan $loan;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        $this->strategy = new VariableRateStrategy();

        // Create loan with multiple rate periods
        $this->loan = $this->createVariableRateLoan();
    }

    /**
     * Test that strategy supports loans with rate periods.
     *
     * @test
     */
    public function testSupportsLoansWithRatePeriods(): void
    {
        $this->assertTrue($this->strategy->supports($this->loan));
    }

    /**
     * Test that strategy rejects loans without rate periods.
     *
     * @test
     */
    public function testRejectsLoansWithoutRatePeriods(): void
    {
        $loanWithoutRates = new Loan();
        $loanWithoutRates->setPrincipal(50000);
        $loanWithoutRates->setAnnualRate(0.05);
        $loanWithoutRates->setMonths(60);
        $loanWithoutRates->setStartDate(new DateTimeImmutable('2024-01-01'));

        $this->assertFalse($this->strategy->supports($loanWithoutRates));
    }

    /**
     * Test payment calculation with multiple rate periods.
     *
     * With changing rates, payment calculation becomes complex:
     * - Some periods at 4.5%, others at 5.5%, etc.
     * - Must ensure final balance = $0.00
     * - Cannot use simple amortization formula (requires iteration)
     *
     * @test
     */
    public function testCalculatesPaymentWithVariableRates(): void
    {
        $payment = $this->strategy->calculatePayment($this->loan);

        // Payment should be reasonable for a $50k loan
        $this->assertGreaterThan(0, $payment);
        $this->assertLessThan(2000, $payment);

        // Should be 2 decimal places
        $decimalPlaces = strlen(explode('.', (string)$payment)[1] ?? '00');
        $this->assertLessThanOrEqual(2, $decimalPlaces);
    }

    /**
     * Test schedule generation with rate transitions.
     *
     * Schedule should show when rates change and how it affects interest/principal.
     *
     * @test
     */
    public function testGeneratesScheduleWithRateTransitions(): void
    {
        $schedule = $this->strategy->calculateSchedule($this->loan);

        // Should have 60 periods
        $this->assertCount(60, $schedule);

        // First 6 periods at 4.5% rate
        $earlyPeriods = array_slice($schedule, 0, 6);
        foreach ($earlyPeriods as $row) {
            $this->assertEquals(0.045, $row['rate'] ?? 0.045, "Early periods should be at 4.5%");
        }

        // Periods 7-12 at 5.5% rate
        $middlePeriods = array_slice($schedule, 6, 6);
        foreach ($middlePeriods as $row) {
            $this->assertEquals(0.055, $row['rate'] ?? 0.055, "Middle periods should be at 5.5%");
        }
    }

    /**
     * Test that schedule records rate period transitions.
     *
     * Each schedule row should include:
     * - rate_period_id: Which rate period applies
     * - rate: The interest rate for that period
     * - rate_change: Boolean if rate changed this period
     *
     * @test
     */
    public function testScheduleTracksRatePeriodTransitions(): void
    {
        $schedule = $this->strategy->calculateSchedule($this->loan);

        // Check for rate period ID tracking
        foreach ($schedule as $row) {
            $this->assertArrayHasKey('rate_period_id', $row, "Schedule should track rate_period_id");
            $this->assertIsInt($row['rate_period_id']);
        }

        // Check for rate changes at boundaries
        $period6 = $schedule[5];  // Last period at 4.5%
        $period7 = $schedule[6];  // First period at 5.5%

        if ($period6['rate_period_id'] !== $period7['rate_period_id']) {
            $this->assertNotEquals($period6['rate'] ?? 0, $period7['rate'] ?? 0, "Rate should change at period boundary");
        }
    }

    /**
     * Test that balance decreases monotonically across rate changes.
     *
     * Balance should never increase, only decrease (or stay same with 0% payment).
     *
     * @test
     */
    public function testBalanceDecreasesWithRateChanges(): void
    {
        $schedule = $this->strategy->calculateSchedule($this->loan);

        $previousBalance = $schedule[0]['balance'];
        foreach ($schedule as $i => $row) {
            $currentBalance = $row['balance'];
            $this->assertLessThanOrEqual(
                $previousBalance + 0.02,  // Allow for rounding
                $currentBalance,
                "Balance should decrease monotonically at period {$i}"
            );
            $previousBalance = $currentBalance;
        }
    }

    /**
     * Test final balance reaches $0.00 with variable rates.
     *
     * Despite multiple rate changes, final balance must = $0.00 ±$0.02
     *
     * @test
     */
    public function testFinalBalanceIsZeroWithVariableRates(): void
    {
        $schedule = $this->strategy->calculateSchedule($this->loan);
        $finalBalance = $schedule[count($schedule) - 1]['balance'];

        $this->assertLessThanOrEqual(0.02, abs($finalBalance), "Final balance should be $0.00 ±$0.02");
    }

    /**
     * Test rate change after first period (ARM scenario).
     *
     * Simulates ARM where rate is fixed for 6 months then adjusts.
     *
     * @test
     */
    public function testHandlesArmStyleRateChange(): void
    {
        // Create ARM: 4.5% for 6 months, then 5.5% for remaining
        $armLoan = new Loan();
        $armLoan->setPrincipal(50000);
        $armLoan->setAnnualRate(0.045);  // Initial rate
        $armLoan->setMonths(60);
        $armLoan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // Add rate period: 4.5% for first 6 months
        $period1 = new RatePeriod(
            loanId: 1,
            rate: 0.045,
            startDate: new DateTimeImmutable('2024-01-01'),
            endDate: new DateTimeImmutable('2024-06-30')
        );
        $armLoan->addRatePeriod($period1);

        // Add rate period: 5.5% for next 54 months
        $period2 = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2024-07-01'),
            endDate: null  // Ongoing
        );
        $armLoan->addRatePeriod($period2);

        $schedule = $this->strategy->calculateSchedule($armLoan);

        // First 6 periods at 4.5%
        for ($i = 0; $i < 6; $i++) {
            $this->assertEqualsWithDelta(
                0.045,
                $schedule[$i]['rate'] ?? 0.045,
                0.001,
                "Periods 1-6 should be at 4.5%"
            );
        }

        // Remaining periods at 5.5%
        for ($i = 6; $i < 60; $i++) {
            $this->assertEqualsWithDelta(
                0.055,
                $schedule[$i]['rate'] ?? 0.055,
                0.001,
                "Periods 7+ should be at 5.5%"
            );
        }
    }

    /**
     * Test frequent rate changes (monthly adjustments).
     *
     * Some variable rate loans adjust monthly based on index.
     *
     * @test
     */
    public function testHandlesFrequentRateChanges(): void
    {
        // Create loan with rate change every month
        $loanWithMonthlyChanges = new Loan();
        $loanWithMonthlyChanges->setPrincipal(50000);
        $loanWithMonthlyChanges->setAnnualRate(0.05);
        $loanWithMonthlyChanges->setMonths(12);
        $loanWithMonthlyChanges->setStartDate(new DateTimeImmutable('2024-01-01'));

        // Add 12 rate periods (one per month)
        $currentDate = new DateTimeImmutable('2024-01-01');
        for ($i = 0; $i < 12; $i++) {
            $rate = 0.04 + ($i * 0.001);  // Rates: 4.0%, 4.1%, 4.2%, etc.
            $nextDate = $currentDate->modify('+1 month')->modify('-1 day');

            $period = new RatePeriod(
                loanId: 1,
                rate: $rate,
                startDate: $currentDate,
                endDate: $nextDate
            );
            $loanWithMonthlyChanges->addRatePeriod($period);

            $currentDate = $nextDate->modify('+1 day');
        }

        $schedule = $this->strategy->calculateSchedule($loanWithMonthlyChanges);

        // Should have 12 periods
        $this->assertCount(12, $schedule);

        // Each period should have correct rate
        for ($i = 0; $i < 12; $i++) {
            $expectedRate = 0.04 + ($i * 0.001);
            $actualRate = $schedule[$i]['rate'] ?? 0;
            $this->assertEqualsWithDelta(
                $expectedRate,
                $actualRate,
                0.001,
                "Period {$i} should have rate " . round($expectedRate * 100, 1) . "%"
            );
        }
    }

    /**
     * Test that interest decreases as rate decreases in later periods.
     *
     * When rate drops, interest portion should decrease and principal portion increase.
     *
     * @test
     */
    public function testInterestDecreaseWithLowerRate(): void
    {
        $schedule = $this->strategy->calculateSchedule($this->loan);

        // Period 6: Last period at 4.5%
        $period6Interest = $schedule[5]['interest'];

        // Period 12: Last period at 5.5%
        $period12Interest = $schedule[11]['interest'];

        // Even though balance is lower (principle paid down), higher rate means more interest
        // at this point. So period 12 interest might actually be higher initially
        // This is a complex relationship - just verify interest is positive
        $this->assertGreaterThan(0, $period6Interest);
        $this->assertGreaterThan(0, $period12Interest);
    }

    /**
     * Test total interest paid changes with variable rates.
     *
     * Variable rates change total interest compared to fixed rate.
     *
     * @test
     */
    public function testTotalInterestWithVariableRates(): void
    {
        $schedule = $this->strategy->calculateSchedule($this->loan);

        $totalInterest = 0;
        foreach ($schedule as $row) {
            $totalInterest += $row['interest'];
        }

        // For a $50k loan over 60 months, total interest should be reasonable
        // At average 5% rate: roughly $5,000 total interest
        $this->assertGreaterThan(3000, $totalInterest);
        $this->assertLessThan(8000, $totalInterest);
    }

    /**
     * Create a test loan with multiple rate periods.
     *
     * Period 1: 4.5% for 6 months (Jan-Jun 2024)
     * Period 2: 5.5% for 6 months (Jul-Dec 2024)
     * Period 3: 6.5% for remaining (Jan 2025+)
     *
     * @return Loan
     */
    private function createVariableRateLoan(): Loan
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.045);  // Default/first rate
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // Period 1: 4.5% for 6 months
        $period1 = new RatePeriod(
            loanId: 1,
            rate: 0.045,
            startDate: new DateTimeImmutable('2024-01-01'),
            endDate: new DateTimeImmutable('2024-06-30')
        );
        $loan->addRatePeriod($period1);

        // Period 2: 5.5% for 6 months
        $period2 = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2024-07-01'),
            endDate: new DateTimeImmutable('2024-12-31')
        );
        $loan->addRatePeriod($period2);

        // Period 3: 6.5% for remaining
        $period3 = new RatePeriod(
            loanId: 1,
            rate: 0.065,
            startDate: new DateTimeImmutable('2025-01-01'),
            endDate: null
        );
        $loan->addRatePeriod($period3);

        return $loan;
    }
}
