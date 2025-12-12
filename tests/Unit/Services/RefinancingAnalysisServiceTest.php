<?php
namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\RefinancingAnalysisService;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class RefinancingAnalysisServiceTest extends TestCase
{
    private RefinancingAnalysisService $service;
    private Loan $existingLoan;

    protected function setUp(): void
    {
        $this->service = new RefinancingAnalysisService();
        $this->existingLoan = $this->createExistingLoan();
    }

    private function createExistingLoan(): Loan
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(250000.00);
        $loan->setAnnualRate(0.06);
        $loan->setMonths(360);
        $loan->setStartDate(new DateTimeImmutable('2020-01-01'));
        $loan->setCurrentBalance(240000.00);
        $loan->setPaymentsMade(23);
        return $loan;
    }

    public function testCalculateRefinancingBreakEven(): void
    {
        $refinanceOffer = [
            'principal' => 240000.00,
            'rate' => 0.04,
            'months' => 360,
            'closing_costs' => 3000.00,
        ];

        $breakEven = $this->service->calculateRefinancingBreakEven(
            $this->existingLoan,
            $refinanceOffer
        );

        $this->assertIsArray($breakEven);
        $this->assertArrayHasKey('break_even_months', $breakEven);
        $this->assertArrayHasKey('monthly_savings', $breakEven);
        $this->assertGreaterThan(0, $breakEven['break_even_months']);
    }

    public function testCalculateTotalSavingsWithRefinancing(): void
    {
        $refinanceOffer = [
            'principal' => 240000.00,
            'rate' => 0.04,
            'months' => 360,
            'closing_costs' => 3000.00,
        ];

        $savings = $this->service->calculateTotalSavingsWithRefinancing(
            $this->existingLoan,
            $refinanceOffer
        );

        $this->assertIsArray($savings);
        $this->assertArrayHasKey('total_interest_savings', $savings);
        $this->assertArrayHasKey('net_savings', $savings);
        $this->assertGreaterThan(0, $savings['total_interest_savings']);
    }

    public function testCompareRefinancingOffers(): void
    {
        $offers = [
            ['principal' => 240000.00, 'rate' => 0.04, 'months' => 360, 'closing_costs' => 3000.00],
            ['principal' => 240000.00, 'rate' => 0.035, 'months' => 300, 'closing_costs' => 2500.00],
            ['principal' => 240000.00, 'rate' => 0.045, 'months' => 360, 'closing_costs' => 1500.00],
        ];

        $comparison = $this->service->compareRefinancingOffers(
            $this->existingLoan,
            $offers
        );

        $this->assertIsArray($comparison);
        $this->assertCount(3, $comparison);
        $this->assertArrayHasKey('rank', $comparison[0]);
    }

    public function testCalculatePayoffTimelineChange(): void
    {
        $refinanceOffer = [
            'principal' => 240000.00,
            'rate' => 0.04,
            'months' => 300,
            'closing_costs' => 3000.00,
        ];

        $timeline = $this->service->calculatePayoffTimelineChange(
            $this->existingLoan,
            $refinanceOffer
        );

        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('current_payoff_date', $timeline);
        $this->assertArrayHasKey('new_payoff_date', $timeline);
        $this->assertArrayHasKey('months_accelerated', $timeline);
    }

    public function testGenerateRefinancingRecommendation(): void
    {
        $offers = [
            ['principal' => 240000.00, 'rate' => 0.04, 'months' => 360, 'closing_costs' => 3000.00],
            ['principal' => 240000.00, 'rate' => 0.035, 'months' => 300, 'closing_costs' => 2500.00],
        ];

        $recommendation = $this->service->generateRefinancingRecommendation(
            $this->existingLoan,
            $offers,
            'maximize_savings'
        );

        $this->assertIsArray($recommendation);
        $this->assertArrayHasKey('recommended_offer', $recommendation);
        $this->assertArrayHasKey('reasoning', $recommendation);
        $this->assertNotEmpty($recommendation['reasoning']);
    }

    public function testAnalyzeRefinancingROI(): void
    {
        $refinanceOffer = [
            'principal' => 240000.00,
            'rate' => 0.04,
            'months' => 360,
            'closing_costs' => 3000.00,
        ];

        $roi = $this->service->analyzeRefinancingROI(
            $this->existingLoan,
            $refinanceOffer,
            5
        );

        $this->assertIsArray($roi);
        $this->assertArrayHasKey('roi_percentage', $roi);
        $this->assertArrayHasKey('payback_period', $roi);
    }

    public function testCalculateCreditScoreImpact(): void
    {
        $impact = $this->service->calculateCreditScoreImpact(
            $this->existingLoan
        );

        $this->assertIsArray($impact);
        $this->assertArrayHasKey('hard_inquiry_impact', $impact);
        $this->assertArrayHasKey('new_account_impact', $impact);
        $this->assertArrayHasKey('average_age_impact', $impact);
    }

    public function testEstimateMonthlyPaymentAfterRefinancing(): void
    {
        $refinanceOffer = [
            'principal' => 240000.00,
            'rate' => 0.04,
            'months' => 360,
            'closing_costs' => 3000.00,
        ];

        $payment = $this->service->estimateMonthlyPaymentAfterRefinancing(
            $refinanceOffer
        );

        $this->assertIsFloat($payment);
        $this->assertGreaterThan(0, $payment);
        $this->assertLessThan(2000, $payment);
    }

    public function testIdentifyRefinancingOpportunities(): void
    {
        $opportunities = $this->service->identifyRefinancingOpportunities(
            $this->existingLoan,
            0.03
        );

        $this->assertIsArray($opportunities);
        $this->assertArrayHasKey('should_refinance', $opportunities);
        $this->assertArrayHasKey('savings_potential', $opportunities);
    }

    public function testCalculateRefinancingCashOutOptions(): void
    {
        $homeValue = 350000.00;
        $cashOutOptions = $this->service->calculateRefinancingCashOutOptions(
            $this->existingLoan,
            $homeValue,
            0.04
        );

        $this->assertIsArray($cashOutOptions);
        $this->assertArrayHasKey('max_cash_out', $cashOutOptions);
        $this->assertArrayHasKey('loan_amount_at_80ltv', $cashOutOptions);
    }

    public function testSimulateRefinancingScenarios(): void
    {
        $scenarios = $this->service->simulateRefinancingScenarios(
            $this->existingLoan,
            [0.03, 0.035, 0.04, 0.045, 0.05],
            [240, 300, 360]
        );

        $this->assertIsArray($scenarios);
        $this->assertCount(15, $scenarios);
        $this->assertArrayHasKey('monthly_payment', $scenarios[0]);
    }

    public function testGenerateRefinancingReport(): void
    {
        $offers = [
            ['principal' => 240000.00, 'rate' => 0.04, 'months' => 360, 'closing_costs' => 3000.00],
            ['principal' => 240000.00, 'rate' => 0.035, 'months' => 300, 'closing_costs' => 2500.00],
        ];

        $report = $this->service->generateRefinancingReport(
            $this->existingLoan,
            $offers
        );

        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('analysis', $report);
        $this->assertArrayHasKey('recommendations', $report);
    }

    public function testCalculateTaxImplicationsOfRefinancing(): void
    {
        $refinanceOffer = [
            'principal' => 240000.00,
            'rate' => 0.04,
            'months' => 360,
            'closing_costs' => 3000.00,
        ];

        $taxImpact = $this->service->calculateTaxImplicationsOfRefinancing(
            $this->existingLoan,
            $refinanceOffer
        );

        $this->assertIsArray($taxImpact);
        $this->assertArrayHasKey('lost_deduction_value', $taxImpact);
        $this->assertArrayHasKey('new_deduction_value', $taxImpact);
    }

    public function testExportRefinancingAnalysis(): void
    {
        $refinanceOffer = [
            'principal' => 240000.00,
            'rate' => 0.04,
            'months' => 360,
            'closing_costs' => 3000.00,
        ];

        $json = $this->service->exportRefinancingAnalysisToJSON(
            $this->existingLoan,
            $refinanceOffer
        );

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }
}
