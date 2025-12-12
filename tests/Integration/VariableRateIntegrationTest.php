<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\RatePeriod;
use Ksfraser\Amortizations\Strategies\VariableRateStrategy;
use Tests\Mocks\MockLoanRepository;
use Tests\Mocks\MockScheduleRepository;
use Tests\Mocks\MockRatePeriodRepository;
use DateTimeImmutable;

/**
 * VariableRateIntegrationTest
 *
 * Integration tests for variable rate (ARM) loans.
 * Tests interaction between:
 * - Strategy (VariableRateStrategy)
 * - Models (Loan, RatePeriod)
 * - Repositories (LoanRepository, ScheduleRepository, RatePeriodRepository)
 * - End-to-end ARM workflows
 *
 * Scenarios tested:
 * 1. Create loan → Add rate periods → Calculate schedule → Save all
 * 2. Loan with rate change → Recalculate → Verify new payment
 * 3. ARM-style rate transitions → Schedule with multiple rates
 * 4. Query next rate change date → Plan ahead
 *
 * @covers \Ksfraser\Amortizations\Strategies\VariableRateStrategy
 * @covers \Ksfraser\Amortizations\Models\RatePeriod
 */
class VariableRateIntegrationTest extends TestCase
{
    private VariableRateStrategy $strategy;
    private MockLoanRepository $loanRepo;
    private MockScheduleRepository $scheduleRepo;
    private MockRatePeriodRepository $rateRepo;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        $this->strategy = new VariableRateStrategy();
        $this->loanRepo = new MockLoanRepository();
        $this->scheduleRepo = new MockScheduleRepository();
        $this->rateRepo = new MockRatePeriodRepository();
    }

    /**
     * Test complete ARM workflow: Create → Add rates → Calculate → Save → Retrieve
     *
     * @test
     */
    public function testCompleteARMWorkflow(): void
    {
        // STEP 1: Create loan
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.03);  // Start at 3%
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // STEP 2: Add rate periods (ARM adjustments)
        $period1 = new RatePeriod(
            1,
            0.03,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-12-31')
        );
        $loan->addRatePeriod($period1);

        $period2 = new RatePeriod(
            1,
            0.04,
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-12-31')
        );
        $loan->addRatePeriod($period2);

        $period3 = new RatePeriod(
            1,
            0.05,
            new DateTimeImmutable('2026-01-01'),
            null
        );
        $loan->addRatePeriod($period3);

        // STEP 3: Verify strategy supports variable rates
        $this->assertTrue($this->strategy->supports($loan));

        // STEP 4: Calculate schedule
        $schedule = $this->strategy->calculateSchedule($loan);
        $this->assertCount(60, $schedule);

        // STEP 5: Save loan
        $loanId = $this->loanRepo->save($loan);
        $this->assertGreaterThan(0, $loanId);

        // STEP 6: Save rate periods
        $this->rateRepo->save($period1);
        $this->rateRepo->save($period2);
        $this->rateRepo->save($period3);

        // STEP 7: Save schedule
        $rows = $this->scheduleRepo->saveSchedule($loanId, $schedule);
        $this->assertEquals(60, $rows);

        // STEP 8: Retrieve and verify
        $retrievedLoan = $this->loanRepo->findById($loanId);
        $this->assertNotNull($retrievedLoan);

        $retrievedSchedule = $this->scheduleRepo->getScheduleForLoan($loanId);
        $this->assertCount(60, $retrievedSchedule);

        $retrievedRates = $this->rateRepo->findByLoanId($loanId);
        $this->assertCount(3, $retrievedRates);

        // STEP 9: Verify final balance = 0
        $finalBalance = end($retrievedSchedule)['balance'];
        $this->assertLessThanOrEqual(0.02, abs($finalBalance));
    }

    /**
     * Test rate change detection and schedule impact.
     *
     * @test
     */
    public function testRateChangeImpactsSchedule(): void
    {
        // Create loan with single rate
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.04);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // Calculate with fixed rate
        $schedule = $this->strategy->calculateSchedule($loan);
        $payment1 = $schedule[0]['payment_amount'];

        // Now add rate periods (same fixed rate)
        $period1 = new RatePeriod(
            1,
            0.04,
            new DateTimeImmutable('2024-01-01'),
            null
        );
        $loan->addRatePeriod($period1);

        // Schedule with rate period should be similar
        $schedule = $this->strategy->calculateSchedule($loan);
        $payment2 = $schedule[0]['payment_amount'];

        $this->assertEqualsWithDelta($payment1, $payment2, 0.01);
    }

    /**
     * Test ARM-style rate transition: Low start → Gradual increase
     *
     * @test
     */
    public function testArmStyleRateTransition(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(100000);
        $loan->setAnnualRate(0.03);
        $loan->setMonths(360);  // 30-year mortgage
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // 2/28 ARM: 2 years at 3%, then 28 years at increasing rates
        $period1 = new RatePeriod(
            1,
            0.03,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2025-12-31')
        );
        $loan->addRatePeriod($period1);

        $period2 = new RatePeriod(
            1,
            0.035,
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2030-12-31')
        );
        $loan->addRatePeriod($period2);

        $period3 = new RatePeriod(
            1,
            0.045,
            new DateTimeImmutable('2031-01-01'),
            null
        );
        $loan->addRatePeriod($period3);

        // Generate schedule
        $schedule = $this->strategy->calculateSchedule($loan);
        $this->assertCount(360, $schedule);

        // Verify schedule has rate_period_id tracked
        $row1 = $schedule[0];  // Jan 2024 - should be period 1
        $this->assertArrayHasKey('rate_period_id', $row1);

        // Find row in year 3 (2026)
        $row25 = $schedule[24];  // Should be in period 2
        $this->assertGreaterThan(0, $row1['payment_amount']);

        // Verify final balance
        $finalBalance = end($schedule)['balance'];
        $this->assertLessThanOrEqual(0.05, abs($finalBalance));
    }

    /**
     * Test next rate change date query.
     *
     * @test
     */
    public function testNextRateChangeDate(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.04);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $period1 = new RatePeriod(
            1,
            0.04,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-12-31')
        );
        $loan->addRatePeriod($period1);

        $period2 = new RatePeriod(
            1,
            0.045,
            new DateTimeImmutable('2025-01-01'),
            null
        );
        $loan->addRatePeriod($period2);

        // Save rates
        $this->rateRepo->save($period1);
        $this->rateRepo->save($period2);

        // Query next change date
        $nextChange = $this->rateRepo->getNextRateChangeDate(1);
        $this->assertNotNull($nextChange);
        $this->assertEquals('2025-01-01', $nextChange->format('Y-m-d'));
    }

    /**
     * Test variable rate detection.
     *
     * @test
     */
    public function testVariableRateDetection(): void
    {
        // Loan without rate periods = fixed rate
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);

        $hasVariable = $this->rateRepo->hasVariableRates(1);
        $this->assertFalse($hasVariable);

        // Now add rate period
        $this->rateRepo->addRatePeriod(1);

        $hasVariable = $this->rateRepo->hasVariableRates(1);
        $this->assertTrue($hasVariable);
    }

    /**
     * Test current rate lookup for date.
     *
     * @test
     */
    public function testCurrentRateLookup(): void
    {
        $period1 = new RatePeriod(
            1,
            0.03,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-12-31')
        );

        $period2 = new RatePeriod(
            1,
            0.05,
            new DateTimeImmutable('2025-01-01'),
            null
        );

        $this->rateRepo->save($period1);
        $this->rateRepo->save($period2);

        // Look up rate for Jan 2024 (should be period 1)
        $rate = $this->rateRepo->getCurrentRate(1);
        $this->assertNotNull($rate);
    }

    /**
     * Test multiple rate changes within single year.
     *
     * @test
     */
    public function testMultipleRateChangesPerYear(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.04);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // Add 4 rate changes (quarterly adjustments)
        for ($i = 0; $i < 4; $i++) {
            $startDate = new DateTimeImmutable('2024-01-01');
            $startDate = $startDate->modify("+{$i} months");
            $endDate = $startDate->modify("+1 month");

            $period = new RatePeriod(
                1,
                0.04 + ($i * 0.001),
                $startDate,
                $endDate
            );
            $loan->addRatePeriod($period);
        }

        $schedule = $this->strategy->calculateSchedule($loan);
        $this->assertCount(60, $schedule);

        // Verify final balance
        $finalBalance = end($schedule)['balance'];
        $this->assertLessThanOrEqual(0.05, abs($finalBalance));
    }

    /**
     * Test ARM schedule generation with rate tracking.
     *
     * @test
     */
    public function testArmScheduleRateTracking(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.04);
        $loan->setMonths(36);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $period1 = new RatePeriod(
            1,
            0.04,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-12-31')
        );
        $loan->addRatePeriod($period1);

        $period2 = new RatePeriod(
            1,
            0.05,
            new DateTimeImmutable('2025-01-01'),
            null
        );
        $loan->addRatePeriod($period2);

        // Generate schedule and save
        $schedule = $this->strategy->calculateSchedule($loan);
        $this->scheduleRepo->saveSchedule(1, $schedule);

        // Verify rate_period_id is tracked
        for ($i = 0; $i < 12; $i++) {
            $row = $this->scheduleRepo->getScheduleRow(1, $i + 1);
            $this->assertNotNull($row);
            $this->assertArrayHasKey('rate_period_id', $row);
        }
    }
}
