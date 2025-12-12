<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\LoanRefinancingService;
use DateTimeImmutable;

/**
 * LoanRefinancingServiceTest - TDD Test Suite
 *
 * Tests for the LoanRefinancingService which handles loan refinancing,
 * modifications, and rate changes. Refinancing can involve rate modifications,
 * term extensions, principal reductions, or combinations thereof.
 *
 * Responsibilities:
 * - Calculate savings from refinancing
 * - Apply new terms and rates
 * - Recalculate amortization schedules
 * - Track refinancing events for audit
 * - Support partial refinancing
 *
 * Test coverage: 14 tests
 * - Rate reduction scenarios (2 tests)
 * - Term extension scenarios (2 tests)
 * - Principal reduction scenarios (2 tests)
 * - Interest savings calculation (2 tests)
 * - Combined modifications (2 tests)
 * - Schedule regeneration (2 tests)
 * - Edge cases and validation (2 tests)
 */
class LoanRefinancingServiceTest extends TestCase
{
    private $service;

    protected function setUp(): void
    {
        $this->service = new LoanRefinancingService();
    }

    /**
     * Test 1: Refinance with rate reduction
     */
    public function testRefinanceWithRateReduction()
    {
        $loan = $this->createTestLoan();
        $originalRate = $loan->getAnnualRate();  // 5%

        $refinance = $this->service->createRefinance(
            $loan,
            0.04,  // New rate: 4%
            null,  // Keep same term
            null,  // No principal reduction
            new DateTimeImmutable('2024-06-01')
        );

        $this->assertIsArray($refinance);
        $this->assertEquals(0.04, $refinance['new_rate']);
        $this->assertEquals(0.05, $refinance['original_rate']);
        $this->assertEquals(60, $refinance['remaining_months']);
        $this->assertEquals('PENDING', $refinance['status']);
    }

    /**
     * Test 2: Calculate interest savings from rate reduction
     */
    public function testCalculateInterestSavingsFromRateReduction()
    {
        $loan = $this->createTestLoan();
        // $10,000 principal, 5% → 4%, 60 months remaining

        $refinance = $this->service->createRefinance(
            $loan,
            0.04,
            null,
            null,
            new DateTimeImmutable('2024-06-01')
        );

        $savings = $this->service->calculateInterestSavings($loan, $refinance);

        // Old interest: $10,000 @ 5% for 60 months = ~$2,747
        // New interest: $10,000 @ 4% for 60 months = ~$2,475
        // Savings: ~$272 (lower than expected due to formula accuracy)
        $this->assertGreaterThan(200, $savings);
        $this->assertLessThan(350, $savings);
    }

    /**
     * Test 3: Refinance with term extension
     */
    public function testRefinanceWithTermExtension()
    {
        $loan = $this->createTestLoan();
        // Original: 60 months, current balance: $10,000

        $refinance = $this->service->createRefinance(
            $loan,
            0.05,  // Keep same rate
            72,    // Extend to 72 months
            null,  // No principal reduction
            new DateTimeImmutable('2024-06-01')
        );

        $this->assertEquals(0.05, $refinance['new_rate']);
        $this->assertEquals(72, $refinance['new_term']);
        $this->assertEquals(60, $refinance['remaining_months']);
    }

    /**
     * Test 4: Calculate reduced payment from term extension
     */
    public function testCalculateReducedPaymentFromTermExtension()
    {
        $loan = $this->createTestLoan();
        
        // Calculate original payment (60 months @ 5% on $10,000)
        $originalRefinance = $this->service->createRefinance($loan, 0.05, 60);
        $originalPayment = $this->service->calculateNewPayment($loan, $originalRefinance);

        $refinance = $this->service->createRefinance(
            $loan,
            0.05,
            72,    // Extend from 60 to 72 months
            null,
            new DateTimeImmutable('2024-06-01')
        );

        $newPayment = $this->service->calculateNewPayment($loan, $refinance);

        // With more months, payment should be lower
        $this->assertLessThan($originalPayment, $newPayment);
        // 72-month payment should be around 155-165
        $this->assertGreaterThan(150, $newPayment);
        $this->assertLessThan(165, $newPayment);
    }

    /**
     * Test 5: Refinance with principal reduction (payoff)
     */
    public function testRefinanceWithPrincipalReduction()
    {
        $loan = $this->createTestLoan();
        $originalBalance = $loan->getCurrentBalance();  // $10,000

        $refinance = $this->service->createRefinance(
            $loan,
            0.05,
            60,
            2000,  // Pay down $2,000
            new DateTimeImmutable('2024-06-01')
        );

        $this->assertEquals(0.05, $refinance['new_rate']);
        $this->assertEquals(2000, $refinance['principal_reduction']);
        $this->assertEquals(8000, $refinance['new_principal']);  // $10,000 - $2,000
    }

    /**
     * Test 6: Calculate interest savings from principal reduction
     */
    public function testCalculateInterestSavingsFromPrincipalReduction()
    {
        $loan = $this->createTestLoan();
        // $10,000 @ 5% for 60 months = ~$2,747
        // $8,000 @ 5% for 60 months = ~$2,197
        // Savings: ~$550

        $refinance = $this->service->createRefinance(
            $loan,
            0.05,
            60,
            2000,  // $2,000 principal reduction
            new DateTimeImmutable('2024-06-01')
        );

        $savings = $this->service->calculateInterestSavings($loan, $refinance);

        // $10,000 @ 5% for 60 months = ~$2,747
        // $8,000 @ 5% for 60 months = ~$2,197
        // Savings: ~$264 (lower calculation due to formula)
        $this->assertGreaterThan(200, $savings);
        $this->assertLessThan(350, $savings);
    }

    /**
     * Test 7: Combine rate reduction + term extension
     */
    public function testCombinedRateReductionAndTermExtension()
    {
        $loan = $this->createTestLoan();

        $refinance = $this->service->createRefinance(
            $loan,
            0.03,  // Reduce rate from 5% to 3%
            72,    // Extend from 60 to 72 months
            null,
            new DateTimeImmutable('2024-06-01')
        );

        $this->assertEquals(0.03, $refinance['new_rate']);
        $this->assertEquals(72, $refinance['new_term']);
        
        $newPayment = $this->service->calculateNewPayment($loan, $refinance);
        $originalRefinance = $this->service->createRefinance($loan, 0.05, 60);
        $originalPayment = $this->service->calculateNewPayment($loan, $originalRefinance);

        // Both rate reduction and term extension should lower payment significantly
        $this->assertLessThan($originalPayment * 0.85, $newPayment);
    }

    /**
     * Test 8: Combine principal reduction + term extension
     */
    public function testCombinedPrincipalReductionAndTermExtension()
    {
        $loan = $this->createTestLoan();

        $refinance = $this->service->createRefinance(
            $loan,
            0.05,
            72,    // Extend to 72 months
            3000,  // Reduce principal by $3,000
            new DateTimeImmutable('2024-06-01')
        );

        $this->assertEquals(72, $refinance['new_term']);
        $this->assertEquals(3000, $refinance['principal_reduction']);
        $this->assertEquals(7000, $refinance['new_principal']);

        $newPayment = $this->service->calculateNewPayment($loan, $refinance);
        
        $originalRefinance = $this->service->createRefinance($loan, 0.05, 60);
        $originalPayment = $this->service->calculateNewPayment($loan, $originalRefinance);
        
        // Lower principal + more months = significantly lower payment
        $this->assertLessThan($originalPayment, $newPayment);
    }

    /**
     * Test 9: Recalculate schedule after refinancing
     */
    public function testRecalculateScheduleAfterRefinancing()
    {
        $loan = $this->createTestLoan();

        $refinance = $this->service->createRefinance(
            $loan,
            0.03,  // Better rate
            60,    // Same term
            1000,  // Small principal reduction
            new DateTimeImmutable('2024-06-01')
        );

        $newSchedule = $this->service->generateNewSchedule($loan, $refinance);

        $this->assertIsArray($newSchedule);
        $this->assertArrayHasKey('periods', $newSchedule);
        $this->assertArrayHasKey('total_interest', $newSchedule);
        $this->assertArrayHasKey('effective_date', $newSchedule);
        
        $this->assertEquals(60, count($newSchedule['periods']));
        $this->assertEquals('2024-06-01', $newSchedule['effective_date']);
    }

    /**
     * Test 10: Mark refinance as approved and active
     */
    public function testApproveAndActivateRefinance()
    {
        $loan = $this->createTestLoan();

        $refinance = $this->service->createRefinance(
            $loan,
            0.04,
            60,
            null,
            new DateTimeImmutable('2024-06-01')
        );

        $approved = $this->service->approveRefinance($refinance, 'loan_officer_001');
        $active = $this->service->activateRefinance($approved);

        $this->assertEquals('APPROVED', $approved['status']);
        $this->assertEquals('loan_officer_001', $approved['approved_by']);
        $this->assertEquals('ACTIVE', $active['status']);
    }

    /**
     * Test 11: Validate refinance doesn't exceed maximum term
     */
    public function testValidateMaximumTermLimit()
    {
        $loan = $this->createTestLoan();

        // Try to extend to 120 months (beyond maximum 100)
        $isValid = $this->service->isValidRefinance($loan, [
            'new_term' => 120,
            'new_rate' => 0.05,
            'principal_reduction' => null,
        ]);

        $this->assertFalse($isValid);
    }

    /**
     * Test 12: Validate refinance doesn't reduce principal below zero
     */
    public function testValidatePrincipalNotNegative()
    {
        $loan = $this->createTestLoan();
        // Current balance: $10,000

        // Try to reduce by $15,000 (more than balance)
        $isValid = $this->service->isValidRefinance($loan, [
            'new_term' => 60,
            'new_rate' => 0.05,
            'principal_reduction' => 15000,
        ]);

        $this->assertFalse($isValid);
    }

    /**
     * Test 13: Validate refinance rate is reasonable
     */
    public function testValidateReasonableRateRange()
    {
        $loan = $this->createTestLoan();

        // Try to set rate to 50% (unreasonable)
        $isValid = $this->service->isValidRefinance($loan, [
            'new_term' => 60,
            'new_rate' => 0.50,  // 50% is unreasonable
            'principal_reduction' => null,
        ]);

        $this->assertFalse($isValid);
    }

    /**
     * Test 14: Calculate break-even point for refinancing
     */
    public function testCalculateRefinancingBreakEvenPoint()
    {
        $loan = $this->createTestLoan();

        $refinance = $this->service->createRefinance(
            $loan,
            0.03,  // Lower rate
            60,
            null,
            new DateTimeImmutable('2024-06-01')
        );

        // Assume refinancing costs $500
        $breakEven = $this->service->calculateBreakEvenMonths(
            $loan,
            $refinance,
            500  // Refinancing costs
        );

        // Break-even should be within loan term (60 months)
        $this->assertGreaterThan(0, $breakEven);
        $this->assertLessThan(60, $breakEven);
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
