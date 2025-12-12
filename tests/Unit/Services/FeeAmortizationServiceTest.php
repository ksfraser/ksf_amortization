<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\FeeAmortizationService;
use DateTimeImmutable;

/**
 * FeeAmortizationServiceTest - TDD Test Suite
 *
 * Tests for the FeeAmortizationService which manages loan fees and charges,
 * including origination fees, closing costs, recurring service fees, insurance,
 * and other charges. Supports amortization of fees across the loan term.
 *
 * Responsibilities:
 * - Add and manage one-time fees (origination, documentation, closing)
 * - Add and manage recurring fees (servicing, insurance, misc)
 * - Calculate total borrowing cost (principal + all fees)
 * - Amortize fees across loan term
 * - Generate GL posting entries
 * - Calculate fee impact on effective rate
 *
 * Test coverage: 12 tests
 * - One-time fee handling (2 tests)
 * - Recurring fee handling (2 tests)
 * - Total borrowing cost calculations (2 tests)
 * - Fee amortization schedules (2 tests)
 * - GL posting generation (2 tests)
 * - Effective rate calculation (2 tests)
 */
class FeeAmortizationServiceTest extends TestCase
{
    private $service;

    protected function setUp(): void
    {
        $this->service = new FeeAmortizationService();
    }

    /**
     * Test 1: Add origination fee (one-time)
     */
    public function testAddOriginationFee()
    {
        $loan = $this->createTestLoan();

        $fee = $this->service->addFee(
            $loan->getId(),
            'origination',
            500.00,
            'Loan origination charge',
            'one_time'
        );

        $this->assertIsArray($fee);
        $this->assertEquals($loan->getId(), $fee['loan_id']);
        $this->assertEquals('origination', $fee['type']);
        $this->assertEquals(500.00, $fee['amount']);
        $this->assertEquals('one_time', $fee['frequency']);
    }

    /**
     * Test 2: Add multiple one-time fees (origination, closing, documentation)
     */
    public function testAddMultipleOneTimeFees()
    {
        $loan = $this->createTestLoan();

        $origination = $this->service->addFee($loan->getId(), 'origination', 400.00, 'Origination', 'one_time');
        $closing = $this->service->addFee($loan->getId(), 'closing', 300.00, 'Closing costs', 'one_time');
        $documentation = $this->service->addFee($loan->getId(), 'documentation', 100.00, 'Documentation', 'one_time');

        $fees = $this->service->getAllFees($loan->getId());

        $this->assertEquals(3, count($fees));
        $totalOneTime = array_sum([$origination['amount'], $closing['amount'], $documentation['amount']]);
        $this->assertEquals(800.00, $totalOneTime);
    }

    /**
     * Test 3: Add recurring service fee
     */
    public function testAddRecurringServiceFee()
    {
        $loan = $this->createTestLoan();

        $fee = $this->service->addFee(
            $loan->getId(),
            'servicing',
            25.00,
            'Monthly servicing fee',
            'monthly'
        );

        $this->assertEquals('servicing', $fee['type']);
        $this->assertEquals(25.00, $fee['amount']);
        $this->assertEquals('monthly', $fee['frequency']);
    }

    /**
     * Test 4: Add multiple recurring fees (servicing, insurance, misc)
     */
    public function testAddMultipleRecurringFees()
    {
        $loan = $this->createTestLoan();

        $servicing = $this->service->addFee($loan->getId(), 'servicing', 20.00, 'Servicing', 'monthly');
        $insurance = $this->service->addFee($loan->getId(), 'insurance', 15.00, 'Insurance', 'monthly');
        $misc = $this->service->addFee($loan->getId(), 'misc', 5.00, 'Misc fee', 'monthly');

        $monthlyTotal = $servicing['amount'] + $insurance['amount'] + $misc['amount'];

        $this->assertEquals(40.00, $monthlyTotal);
        
        // Over 60 months: $40/month * 60 = $2,400
        $totalRecurring = $monthlyTotal * 60;
        $this->assertEquals(2400.00, $totalRecurring);
    }

    /**
     * Test 5: Calculate total borrowing cost (principal + all fees)
     */
    public function testCalculateTotalBorrowingCost()
    {
        $loan = $this->createTestLoan();
        // Principal: $10,000

        $this->service->addFee($loan->getId(), 'origination', 400.00, 'Origination', 'one_time');
        $this->service->addFee($loan->getId(), 'closing', 300.00, 'Closing', 'one_time');
        $this->service->addFee($loan->getId(), 'servicing', 25.00, 'Servicing', 'monthly');

        // Total borrowing cost = Principal + OneTime + (Monthly * Months)
        // = $10,000 + $700 + ($25 * 60) = $10,000 + $700 + $1,500 = $12,200
        $totalCost = $this->service->calculateTotalBorrowingCost($loan, $this->service->getAllFees($loan->getId()));

        $this->assertEquals(12200.00, $totalCost);
    }

    /**
     * Test 6: Calculate total interest with fees capitalized
     */
    public function testCalculateTotalInterestWithFees()
    {
        $loan = $this->createTestLoan();
        // $10,000 @ 5% for 60 months = ~$2,747

        $this->service->addFee($loan->getId(), 'origination', 500.00, 'Origination', 'one_time');

        $allFees = $this->service->getAllFees($loan->getId());
        $totalCost = $this->service->calculateTotalBorrowingCost($loan, $allFees);
        $totalInterest = $this->service->calculateTotalInterestWithFees($loan, $allFees);

        // Total cost = principal + one-time fees + monthly fees + interest
        // Interest should include impact of capitalized fees
        $this->assertGreaterThan(2400, $totalInterest);
    }

    /**
     * Test 7: Amortize one-time fee over loan term
     */
    public function testAmortizeOneTimeFee()
    {
        $loan = $this->createTestLoan();
        // $10,000 principal, 60 months

        $fee = $this->service->addFee($loan->getId(), 'origination', 600.00, 'Origination', 'one_time');

        // Amortize $600 over 60 months = $10/month
        $amortized = $this->service->amortizeFee($fee, 60);

        $this->assertIsArray($amortized);
        $this->assertEquals(60, count($amortized['schedule']));
        $this->assertEquals(10.00, $amortized['schedule'][0]);
        $this->assertEquals(10.00, $amortized['schedule'][30]);
    }

    /**
     * Test 8: Generate amortization schedule including fees
     */
    public function testGenerateScheduleWithFees()
    {
        $loan = $this->createTestLoan();

        $this->service->addFee($loan->getId(), 'origination', 400.00, 'Origination', 'one_time');
        $this->service->addFee($loan->getId(), 'servicing', 10.00, 'Servicing', 'monthly');

        $allFees = $this->service->getAllFees($loan->getId());
        $schedule = $this->service->generateScheduleWithFees($loan, $allFees);

        $this->assertIsArray($schedule);
        $this->assertEquals(60, count($schedule['periods']));
        
        // Each period should have fee breakdown
        $period = $schedule['periods'][0];
        $this->assertArrayHasKey('fees_charged', $period);
        $this->assertArrayHasKey('fee_breakdown', $period);
    }

    /**
     * Test 9: Generate GL posting entries for origination fees
     */
    public function testGenerateGLPostingsForOriginationFees()
    {
        $loan = $this->createTestLoan();

        $this->service->addFee($loan->getId(), 'origination', 500.00, 'Origination', 'one_time');

        $postings = $this->service->generateGLPostings($loan, $this->service->getAllFees($loan->getId()));

        $this->assertIsArray($postings);
        $this->assertGreaterThan(0, count($postings));
        
        // Should have debit and credit entries
        $debits = array_filter($postings, fn($p) => $p['account_type'] === 'debit');
        $credits = array_filter($postings, fn($p) => $p['account_type'] === 'credit');
        
        $this->assertGreaterThan(0, count($debits));
        $this->assertGreaterThan(0, count($credits));
    }

    /**
     * Test 10: Generate GL postings for recurring service fees
     */
    public function testGenerateGLPostingsForRecurringFees()
    {
        $loan = $this->createTestLoan();

        $this->service->addFee($loan->getId(), 'servicing', 25.00, 'Servicing', 'monthly');

        $postings = $this->service->generateGLPostings($loan, $this->service->getAllFees($loan->getId()));

        $this->assertIsArray($postings);
        
        // Should have entries for monthly fees
        $monthlyPostings = array_filter($postings, fn($p) => $p['frequency'] === 'monthly' || $p['type'] === 'servicing');
        $this->assertGreaterThan(0, count($monthlyPostings));
    }

    /**
     * Test 11: Calculate effective interest rate including fees
     */
    public function testCalculateEffectiveRateWithFees()
    {
        $loan = $this->createTestLoan();
        // Nominal rate: 5%

        $this->service->addFee($loan->getId(), 'origination', 500.00, 'Origination', 'one_time');

        $effectiveRate = $this->service->calculateEffectiveRate($loan, $this->service->getAllFees($loan->getId()));

        // Effective rate should be higher than nominal rate due to fees
        $this->assertGreaterThan(0.05, $effectiveRate);
        $this->assertLessThan(0.12, $effectiveRate);  // Should not be excessively high
    }

    /**
     * Test 12: Remove or adjust fee
     */
    public function testRemoveFee()
    {
        $loan = $this->createTestLoan();

        $fee1 = $this->service->addFee($loan->getId(), 'origination', 400.00, 'Origination', 'one_time');
        $fee2 = $this->service->addFee($loan->getId(), 'closing', 300.00, 'Closing', 'one_time');

        $allFees = $this->service->getAllFees($loan->getId());
        $this->assertEquals(2, count($allFees));

        // Remove fee
        $this->service->removeFee($loan->getId(), $fee1['fee_id']);

        $remainingFees = $this->service->getAllFees($loan->getId());
        $this->assertEquals(1, count($remainingFees));
        $this->assertEquals('closing', $remainingFees[0]['type']);
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
}
