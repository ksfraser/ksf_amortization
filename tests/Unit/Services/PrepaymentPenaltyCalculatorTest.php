<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\PrepaymentPenaltyCalculator;
use DateTimeImmutable;

/**
 * PrepaymentPenaltyCalculatorTest - TDD Test Suite
 *
 * Tests for the PrepaymentPenaltyCalculator service which calculates and
 * manages prepayment penalties for early loan repayment scenarios.
 *
 * Responsibilities:
 * - Calculate percentage-based penalties
 * - Calculate fixed-amount penalties
 * - Support declining-scale penalties
 * - Track penalty amounts for revenue reporting
 * - Apply penalties to extra payment amounts
 *
 * Test coverage: 10 tests
 * - Penalty type configuration (1 test)
 * - Percentage-based calculation (2 tests)
 * - Fixed-amount calculation (2 tests)
 * - Declining-scale calculation (2 tests)
 * - Penalty waiver by permission (1 test)
 * - Penalty tracking (1 test)
 * - Zero penalty scenarios (1 test)
 */
class PrepaymentPenaltyCalculatorTest extends TestCase
{
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PrepaymentPenaltyCalculator();
    }

    /**
     * Test 1: Configure percentage-based prepayment penalty
     */
    public function testConfigurePercentagePenalty()
    {
        $loan = $this->createTestLoan();

        $penalty = $this->calculator->setPenalty(
            $loan->getId(),
            'percentage',
            5.00  // 5% of prepayment amount
        );

        $this->assertIsArray($penalty);
        $this->assertEquals('percentage', $penalty['type']);
        $this->assertEquals(5.00, $penalty['amount_or_percent']);
        $this->assertEquals('ACTIVE', $penalty['status']);
    }

    /**
     * Test 2: Calculate percentage-based penalty
     */
    public function testCalculatePercentagePenalty()
    {
        $loan = $this->createTestLoan();
        $loan->setCurrentBalance(5000.00);

        $this->calculator->setPenalty($loan->getId(), 'percentage', 3.00);

        // Extra payment of $1000 → 3% = $30 penalty
        $penalty = $this->calculator->calculatePenalty($loan->getId(), 1000.00);

        $this->assertAlmostEquals(30.00, $penalty, 2);
    }

    /**
     * Test 3: Calculate fixed-amount penalty
     */
    public function testCalculateFixedAmountPenalty()
    {
        $loan = $this->createTestLoan();

        $this->calculator->setPenalty($loan->getId(), 'fixed', 250.00);

        // Fixed penalty regardless of prepayment amount
        $penalty = $this->calculator->calculatePenalty($loan->getId(), 1000.00);

        $this->assertAlmostEquals(250.00, $penalty, 2);
    }

    /**
     * Test 4: Calculate declining-scale penalty based on months remaining
     */
    public function testCalculateDeclingScalePenalty()
    {
        $loan = $this->createTestLoan();
        $loan->setMonths(60);
        $loan->setCurrentBalance(5000.00);

        // Declining scale: 5% first year, 3% year 2, 1% year 3, 0% after
        $schedule = [
            ['min_months' => 36, 'percent' => 5.00],
            ['min_months' => 24, 'percent' => 3.00],
            ['min_months' => 12, 'percent' => 1.00],
        ];

        $this->calculator->setPenalty(
            $loan->getId(),
            'declining',
            null,
            $schedule
        );

        // With 48 months remaining: 5% applies
        $loan->setMonths(48);
        $penalty = $this->calculator->calculatePenalty($loan->getId(), 1000.00, $loan);
        $this->assertAlmostEquals(50.00, $penalty, 2);  // 5% of $1000

        // With 24 months remaining: 3% applies
        $loan->setMonths(24);
        $penalty = $this->calculator->calculatePenalty($loan->getId(), 1000.00, $loan);
        $this->assertAlmostEquals(30.00, $penalty, 2);  // 3% of $1000

        // With 6 months remaining: 0% applies
        $loan->setMonths(6);
        $penalty = $this->calculator->calculatePenalty($loan->getId(), 1000.00, $loan);
        $this->assertAlmostEquals(0.00, $penalty, 2);
    }

    /**
     * Test 5: Percentage penalty caps at maximum amount
     */
    public function testPercentagePenaltyCapsAtMaximum()
    {
        $loan = $this->createTestLoan();

        $this->calculator->setPenalty(
            $loan->getId(),
            'percentage',
            10.00,
            null,
            500.00  // Maximum penalty cap
        );

        // 10% of $10,000 = $1000, but capped at $500
        $penalty = $this->calculator->calculatePenalty($loan->getId(), 10000.00);

        $this->assertAlmostEquals(500.00, $penalty, 2);
    }

    /**
     * Test 6: Apply penalty waiver for authorized users
     */
    public function testApplyPenaltyWaiver()
    {
        $loan = $this->createTestLoan();

        $this->calculator->setPenalty($loan->getId(), 'percentage', 5.00);

        // Waive penalty with authorization
        $result = $this->calculator->waivePenalty(
            $loan->getId(),
            'authorized_user',
            'Customer hardship'
        );

        $this->assertTrue($result);

        // Penalty should now return 0
        $penalty = $this->calculator->calculatePenalty($loan->getId(), 1000.00);
        $this->assertAlmostEquals(0.00, $penalty, 2);
    }

    /**
     * Test 7: Track penalty amount for revenue reporting
     */
    public function testTrackPenaltyAmountForReporting()
    {
        $loan = $this->createTestLoan();

        $this->calculator->setPenalty($loan->getId(), 'percentage', 5.00);

        // Record multiple penalty charges
        $this->calculator->recordPenaltyCharge($loan->getId(), 500.00, 'extra_payment');
        $this->calculator->recordPenaltyCharge($loan->getId(), 300.00, 'early_payoff');

        // Get penalty history
        $history = $this->calculator->getPenaltyHistory($loan->getId());

        $this->assertCount(2, $history);
        $this->assertEquals(500.00, $history[0]['amount']);
        $this->assertEquals(300.00, $history[1]['amount']);
    }

    /**
     * Test 8: Get total penalties collected for reporting
     */
    public function testGetTotalPenaltiesCollected()
    {
        $loan = $this->createTestLoan();

        $this->calculator->setPenalty($loan->getId(), 'percentage', 3.00);

        $this->calculator->recordPenaltyCharge($loan->getId(), 150.00, 'extra_payment');
        $this->calculator->recordPenaltyCharge($loan->getId(), 100.00, 'extra_payment');
        $this->calculator->recordPenaltyCharge($loan->getId(), 75.00, 'extra_payment');

        $total = $this->calculator->getTotalPenaltiesCollected($loan->getId());

        $this->assertAlmostEquals(325.00, $total, 2);
    }

    /**
     * Test 9: No penalty if loan has no penalty configured
     */
    public function testNoPenaltyIfNotConfigured()
    {
        $loan = $this->createTestLoan();

        // No penalty set
        $penalty = $this->calculator->calculatePenalty($loan->getId(), 1000.00);

        $this->assertAlmostEquals(0.00, $penalty, 2);
    }

    /**
     * Test 10: No penalty if prepayment is outside defined window
     */
    public function testNoPenaltyOutsidePenaltyWindow()
    {
        $loan = $this->createTestLoan();

        // Penalty only applies in first 24 months
        $this->calculator->setPenalty(
            $loan->getId(),
            'percentage',
            5.00,
            null,
            null,
            [
                'start_month' => 1,
                'end_month' => 24,
                'original_months' => 60  // Penalty window based on original 60-month term
            ]
        );

        // With 6 months remaining (outside window)
        $loan->setMonths(6);
        $penalty = $this->calculator->calculatePenalty($loan->getId(), 1000.00, $loan);

        $this->assertAlmostEquals(0.00, $penalty, 2);
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
