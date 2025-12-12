<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\LoanInsuranceCalculator;
use DateTimeImmutable;

/**
 * LoanInsuranceCalculatorTest - TDD Test Suite
 *
 * Tests for the LoanInsuranceCalculator which manages loan insurance products
 * including Private Mortgage Insurance (PMI), credit insurance, and other
 * coverage types commonly added to mortgage and auto loans.
 *
 * Responsibilities:
 * - Add insurance policies to loans
 * - Calculate monthly insurance premiums
 * - Determine PMI cancellation eligibility (80% LTV threshold)
 * - Calculate LTV (Loan-to-Value) ratio
 * - Apply cancellation triggers
 * - Track insurance state and premium payments
 * - Calculate total cost of insurance
 * - Handle automatic and manual cancellation
 *
 * Test coverage: 12 tests
 * - Insurance policy setup (2 tests)
 * - PMI calculations (2 tests)
 * - LTV and cancellation eligibility (2 tests)
 * - Premium calculations (2 tests)
 * - Cancellation scenarios (2 tests)
 * - Cost tracking (2 tests)
 */
class LoanInsuranceCalculatorTest extends TestCase
{
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new LoanInsuranceCalculator();
    }

    /**
     * Test 1: Add PMI policy to loan
     */
    public function testAddPMIPolicyToLoan()
    {
        $loan = $this->createTestLoan();

        $policy = $this->calculator->addInsurancePolicy(
            $loan,
            'PMI',
            0.005,  // 0.5% annual premium
            null    // Use LTV-based calculation
        );

        $this->assertIsArray($policy);
        $this->assertEquals('PMI', $policy['insurance_type']);
        $this->assertEquals(0.005, $policy['annual_rate']);
        $this->assertArrayHasKey('policy_id', $policy);
        $this->assertArrayHasKey('effective_date', $policy);
        $this->assertEquals('ACTIVE', $policy['status']);
    }

    /**
     * Test 2: Add credit insurance policy
     */
    public function testAddCreditInsurancePolicy()
    {
        $loan = $this->createTestLoan();

        $policy = $this->calculator->addInsurancePolicy(
            $loan,
            'Credit_Insurance',
            0.003,  // 0.3% annual premium
            60      // Fixed term in months
        );

        $this->assertEquals('Credit_Insurance', $policy['insurance_type']);
        $this->assertEquals(60, $policy['term_months']);
    }

    /**
     * Test 3: Calculate monthly PMI premium
     */
    public function testCalculateMonthlyPMIPremium()
    {
        $loan = $this->createTestLoan();
        // $10,000 loan @ 0.5% annual PMI = $50/year = $4.17/month

        $policy = $this->calculator->addInsurancePolicy(
            $loan,
            'PMI',
            0.005,
            null
        );

        $monthlyPremium = $this->calculator->calculateMonthlyPremium($loan, $policy);

        // $10,000 * 0.005 / 12 = $4.17
        $this->assertAlmostEquals(4.17, $monthlyPremium, 2);
    }

    /**
     * Test 4: Calculate PMI premium based on down payment percentage
     */
    public function testCalculatePMIPremiumBasedOnDownPayment()
    {
        $loan = $this->createTestLoan();
        // 20% down payment = no PMI needed
        $loanValue = 10000;
        $downPayment = 2500;  // 20%

        $pmiNeeded = $this->calculator->isPMIRequired($loan, $downPayment);

        $this->assertFalse($pmiNeeded);
    }

    /**
     * Test 5: Calculate LTV (Loan-to-Value) ratio
     */
    public function testCalculateLoanToValueRatio()
    {
        $loan = $this->createTestLoan();
        // Loan: $10,000, Property Value: $12,500 = 80% LTV

        $propertyValue = 12500.00;
        $ltv = $this->calculator->calculateLTV($loan, $propertyValue);

        // $10,000 / $12,500 = 0.80 = 80%
        $this->assertAlmostEquals(0.80, $ltv, 2);
    }

    /**
     * Test 6: Determine PMI cancellation eligibility at 80% LTV
     */
    public function testPMICancellationAtEightyPercentLTV()
    {
        $loan = $this->createTestLoan();
        $propertyValue = 12500.00;

        $ltv = $this->calculator->calculateLTV($loan, $propertyValue);
        $isEligible = $this->calculator->isPMICancellationEligible($ltv);

        $this->assertAlmostEquals(0.80, $ltv, 2);
        $this->assertTrue($isEligible);
    }

    /**
     * Test 7: PMI still required above 80% LTV
     */
    public function testPMIRequiredAboveEightyPercentLTV()
    {
        $loan = $this->createTestLoan();
        $propertyValue = 12000.00;  // 83% LTV

        $ltv = $this->calculator->calculateLTV($loan, $propertyValue);
        $isEligible = $this->calculator->isPMICancellationEligible($ltv);

        $this->assertGreaterThan(0.80, $ltv);
        $this->assertFalse($isEligible);
    }

    /**
     * Test 8: Apply automatic PMI cancellation
     */
    public function testApplyAutomaticPMICancellation()
    {
        $loan = $this->createTestLoan();
        $propertyValue = 12500.00;  // 80% LTV

        $policy = $this->calculator->addInsurancePolicy(
            $loan,
            'PMI',
            0.005,
            null
        );

        $cancelled = $this->calculator->applyCancellationTrigger(
            $loan,
            $policy,
            $propertyValue,
            'automatic'
        );

        $this->assertTrue($cancelled);
        $this->assertEquals('CANCELLED', $policy['status']);
        $this->assertArrayHasKey('cancellation_date', $policy);
        $this->assertArrayHasKey('cancellation_reason', $policy);
    }

    /**
     * Test 9: Apply manual PMI cancellation request
     */
    public function testApplyManualPMICancellation()
    {
        $loan = $this->createTestLoan();

        $policy = $this->calculator->addInsurancePolicy(
            $loan,
            'PMI',
            0.005,
            null
        );

        $cancelled = $this->calculator->applyCancellationTrigger(
            $loan,
            $policy,
            null,
            'manual',
            'Borrower_request'
        );

        $this->assertTrue($cancelled);
        $this->assertEquals('CANCELLED', $policy['status']);
    }

    /**
     * Test 10: Calculate total insurance cost over loan term
     */
    public function testCalculateTotalInsuranceCost()
    {
        $loan = $this->createTestLoan();
        // $10,000 @ 0.5% annual = $50/year * 5 years = $250 total

        $policy = $this->calculator->addInsurancePolicy(
            $loan,
            'PMI',
            0.005,
            null
        );

        $totalCost = $this->calculator->calculateTotalInsuranceCost($loan, $policy);

        // $10,000 * 0.005 * 5 years = $250 (allow small rounding variance)
        $this->assertEqualsWithDelta(250.00, $totalCost, 0.5);
    }

    /**
     * Test 11: Calculate total insurance cost with early cancellation
     */
    public function testCalculateTotalInsuranceCostWithCancellation()
    {
        $loan = $this->createTestLoan();

        $policy = $this->calculator->addInsurancePolicy(
            $loan,
            'PMI',
            0.005,
            null
        );

        // Simulate cancellation at month 30
        $policy['cancellation_month'] = 30;

        $totalCost = $this->calculator->calculateTotalInsuranceCost($loan, $policy);

        // $10,000 * 0.005 * 2.5 years (30 months) = $125 (allow rounding variance)
        $this->assertEqualsWithDelta(125.00, $totalCost, 0.5);
    }

    /**
     * Test 12: Track insurance payment history
     */
    public function testTrackInsurancePaymentHistory()
    {
        $loan = $this->createTestLoan();

        $policy = $this->calculator->addInsurancePolicy(
            $loan,
            'PMI',
            0.005,
            null
        );

        // Record monthly payments
        for ($month = 1; $month <= 12; $month++) {
            $this->calculator->recordInsurancePayment(
                $loan->getId(),
                $policy['policy_id'],
                4.17,
                date('Y-m-d', strtotime("+$month months"))
            );
        }

        $history = $this->calculator->getInsurancePaymentHistory($loan->getId(), $policy['policy_id']);

        $this->assertGreaterThanOrEqual(12, count($history));
        $totalPaid = array_sum(array_column($history, 'amount'));
        $this->assertAlmostEquals(50.04, $totalPaid, 2);  // 12 * $4.17
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
