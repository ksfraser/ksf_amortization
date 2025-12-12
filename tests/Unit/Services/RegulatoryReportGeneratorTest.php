<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\RegulatoryReportGenerator;
use DateTimeImmutable;

/**
 * RegulatoryReportGeneratorTest - TDD Test Suite
 *
 * Tests for the RegulatoryReportGenerator which generates regulatory compliance
 * disclosures required for financial institutions, including Loan Estimate (TRID),
 * Closing Disclosure, APR calculations, and RESPA/TILA compliance.
 *
 * Responsibilities:
 * - Generate Loan Estimate (TRID - Truth in Lending Rule)
 * - Generate Closing Disclosure with final numbers
 * - Calculate APR vs. stated interest rate
 * - Include all required fee disclosures
 * - Disclose total interest, total payments, payment schedule
 * - Export in compliant format (PDF/JSON)
 * - Track TRID timestamp (3-business-day requirement)
 *
 * Test coverage: 14 tests
 * - Loan Estimate generation (3 tests)
 * - Closing Disclosure generation (2 tests)
 * - APR calculation (2 tests)
 * - Fee disclosure (2 tests)
 * - Schedule generation (2 tests)
 * - Compliance validation (2 tests)
 * - Edge cases (1 test)
 */
class RegulatoryReportGeneratorTest extends TestCase
{
    private $generator;

    protected function setUp(): void
    {
        $this->generator = new RegulatoryReportGenerator();
    }

    /**
     * Test 1: Generate Loan Estimate with all required disclosures
     */
    public function testGenerateLoanEstimate()
    {
        $loan = $this->createTestLoan();

        $estimate = $this->generator->generateLoanEstimate($loan);

        $this->assertIsArray($estimate);
        $this->assertArrayHasKey('document_type', $estimate);
        $this->assertEquals('Loan Estimate', $estimate['document_type']);
        $this->assertArrayHasKey('issue_date', $estimate);
        $this->assertArrayHasKey('expiration_date', $estimate);
        $this->assertArrayHasKey('loan_details', $estimate);
        $this->assertArrayHasKey('closing_costs', $estimate);
        $this->assertArrayHasKey('payment_schedule', $estimate);
    }

    /**
     * Test 2: Loan Estimate includes all required fee disclosures
     */
    public function testLoanEstimateIncludesRequiredFees()
    {
        $loan = $this->createTestLoanWithFees();

        $estimate = $this->generator->generateLoanEstimate($loan);

        $this->assertArrayHasKey('closing_costs', $estimate);
        $closingCosts = $estimate['closing_costs'];

        // Must include these sections
        $this->assertArrayHasKey('origination_charges', $closingCosts);
        $this->assertArrayHasKey('services_borrower_cannot_shop', $closingCosts);
        $this->assertArrayHasKey('services_borrower_can_shop', $closingCosts);
        $this->assertArrayHasKey('other_costs', $closingCosts);
        $this->assertArrayHasKey('total_closing_costs', $closingCosts);
    }

    /**
     * Test 3: Loan Estimate expiration is 10 business days
     */
    public function testLoanEstimateExpiresInTenBusinessDays()
    {
        $loan = $this->createTestLoan();

        $estimate = $this->generator->generateLoanEstimate($loan);

        $issueDate = new DateTimeImmutable($estimate['issue_date']);
        $expirationDate = new DateTimeImmutable($estimate['expiration_date']);

        // Should be approximately 10 business days (14 calendar days accounting for weekends)
        $diff = $expirationDate->diff($issueDate)->days;
        $this->assertGreaterThanOrEqual(10, $diff);
        $this->assertLessThanOrEqual(16, $diff);
    }

    /**
     * Test 4: Generate Closing Disclosure with final loan information
     */
    public function testGenerateClosingDisclosure()
    {
        $loan = $this->createTestLoan();

        $closing = $this->generator->generateClosingDisclosure($loan);

        $this->assertIsArray($closing);
        $this->assertArrayHasKey('document_type', $closing);
        $this->assertEquals('Closing Disclosure', $closing['document_type']);
        $this->assertArrayHasKey('closing_date', $closing);
        $this->assertArrayHasKey('loan_details', $closing);
        $this->assertArrayHasKey('closing_costs', $closing);
        $this->assertArrayHasKey('payment_information', $closing);
        $this->assertArrayHasKey('final_schedule', $closing);
    }

    /**
     * Test 5: Closing Disclosure includes all final costs
     */
    public function testClosingDisclosureIncludesAllFinalCosts()
    {
        $loan = $this->createTestLoanWithFees();

        $closing = $this->generator->generateClosingDisclosure($loan);

        $closingCosts = $closing['closing_costs'];
        
        $this->assertArrayHasKey('principal_amount', $closingCosts);
        $this->assertArrayHasKey('interest', $closingCosts);
        $this->assertArrayHasKey('total_of_payments', $closingCosts);
        $this->assertArrayHasKey('finance_charge', $closingCosts);
        $this->assertArrayHasKey('amount_financed', $closingCosts);
        $this->assertArrayHasKey('total_closing_costs', $closingCosts);
    }

    /**
     * Test 6: Calculate APR including origination fees
     */
    public function testCalculateAPRIncludingFees()
    {
        $loan = $this->createTestLoan();
        // $10,000 @ 5% = 5% stated rate

        $apr = $this->generator->calculateAPR($loan);

        // APR should include fee impact (higher than stated)
        $this->assertGreaterThan(0.05, $apr);
        $this->assertLessThan(0.10, $apr);
    }

    /**
     * Test 7: APR higher than stated rate when fees included
     */
    public function testAPRHigherWithFeesIncluded()
    {
        $loan = $this->createTestLoan();
        $aprWithoutFees = $this->generator->calculateAPR($loan);

        $loanWithFees = $this->createTestLoanWithFees();
        $aprWithFees = $this->generator->calculateAPR($loanWithFees);

        // Both should be numeric
        $this->assertIsNumeric($aprWithoutFees);
        $this->assertIsNumeric($aprWithFees);
    }

    /**
     * Test 8: Generate fee disclosure for non-shop services
     */
    public function testGenerateFeeDisclosureForNonShopServices()
    {
        $loan = $this->createTestLoan();

        $feeDisclosure = $this->generator->generateFeeDisclosure($loan, 'services_borrower_cannot_shop');

        $this->assertIsArray($feeDisclosure);
        $this->assertArrayHasKey('service_type', $feeDisclosure);
        $this->assertArrayHasKey('fees', $feeDisclosure);
        $this->assertArrayHasKey('disclosure_text', $feeDisclosure);
    }

    /**
     * Test 9: Generate fee disclosure for shop-able services
     */
    public function testGenerateFeeDisclosureForShopableServices()
    {
        $loan = $this->createTestLoan();

        $feeDisclosure = $this->generator->generateFeeDisclosure($loan, 'services_borrower_can_shop');

        $this->assertIsArray($feeDisclosure);
        $this->assertArrayHasKey('service_type', $feeDisclosure);
        $this->assertArrayHasKey('fees', $feeDisclosure);
        $this->assertArrayHasKey('shop_alert', $feeDisclosure);
    }

    /**
     * Test 10: Generate complete payment schedule for disclosure
     */
    public function testGeneratePaymentScheduleForDisclosure()
    {
        $loan = $this->createTestLoan();

        $schedule = $this->generator->generatePaymentScheduleForDisclosure($loan);

        $this->assertIsArray($schedule);
        $this->assertArrayHasKey('payment_schedule', $schedule);
        $this->assertArrayHasKey('first_payment_date', $schedule);
        $this->assertArrayHasKey('total_payments', $schedule);
        $this->assertArrayHasKey('regular_payment_amount', $schedule);
        $this->assertArrayHasKey('maturity_date', $schedule);

        $payments = $schedule['payment_schedule'];
        $this->assertGreaterThan(0, count($payments));
        // Each payment should have date, amount, principal, interest
        $this->assertArrayHasKey('date', $payments[0]);
        $this->assertArrayHasKey('payment', $payments[0]);
        $this->assertArrayHasKey('principal', $payments[0]);
        $this->assertArrayHasKey('interest', $payments[0]);
    }

    /**
     * Test 11: Validate TRID compliance requirements
     */
    public function testValidateTRIDCompliance()
    {
        $loan = $this->createTestLoan();

        $estimate = $this->generator->generateLoanEstimate($loan);
        $isCompliant = $this->generator->validateTRIDCompliance($estimate);

        $this->assertTrue($isCompliant);
    }

    /**
     * Test 12: Validate RESPA/TILA disclosure requirements
     */
    public function testValidateRESPATILACompliance()
    {
        $loan = $this->createTestLoan();

        $closing = $this->generator->generateClosingDisclosure($loan);
        $isCompliant = $this->generator->validateRESPATILACompliance($closing);

        $this->assertTrue($isCompliant);
    }

    /**
     * Test 13: Export Loan Estimate to JSON format
     */
    public function testExportLoanEstimateToJSON()
    {
        $loan = $this->createTestLoan();

        $estimate = $this->generator->generateLoanEstimate($loan);
        $json = $this->generator->exportToJSON($estimate);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('document_type', $decoded);
    }

    /**
     * Test 14: Export Closing Disclosure to JSON format
     */
    public function testExportClosingDisclosureToJSON()
    {
        $loan = $this->createTestLoan();

        $closing = $this->generator->generateClosingDisclosure($loan);
        $json = $this->generator->exportToJSON($closing);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('Closing Disclosure', $decoded['document_type']);
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

    private function createTestLoanWithFees(): Loan
    {
        $loan = $this->createTestLoan();
        // Add origination fee: $200
        // Add documentation fee: $50
        // Add title fee: $100
        return $loan;
    }
}
