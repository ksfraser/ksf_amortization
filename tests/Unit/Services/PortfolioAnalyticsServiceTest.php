<?php

namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\PortfolioAnalyticsService;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class PortfolioAnalyticsServiceTest extends TestCase
{
    private PortfolioAnalyticsService $service;
    private array $loans;

    protected function setUp(): void
    {
        $this->service = new PortfolioAnalyticsService();
        $this->loans = $this->createTestPortfolio();
    }

    private function createTestPortfolio(): array
    {
        $loan1 = new Loan();
        $loan1->setId(1);
        $loan1->setPrincipal(200000.00);
        $loan1->setAnnualRate(0.04);
        $loan1->setMonths(360);
        $loan1->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan1->setCurrentBalance(198000.00);

        $loan2 = new Loan();
        $loan2->setId(2);
        $loan2->setPrincipal(150000.00);
        $loan2->setAnnualRate(0.035);
        $loan2->setMonths(240);
        $loan2->setStartDate(new DateTimeImmutable('2023-06-15'));
        $loan2->setCurrentBalance(125000.00);

        $loan3 = new Loan();
        $loan3->setId(3);
        $loan3->setPrincipal(300000.00);
        $loan3->setAnnualRate(0.05);
        $loan3->setMonths(300);
        $loan3->setStartDate(new DateTimeImmutable('2022-03-01'));
        $loan3->setCurrentBalance(180000.00);

        return [$loan1, $loan2, $loan3];
    }

    /**
     * Test 1: Calculate total portfolio principal
     */
    public function testCalculateTotalPortfolioPrincipal()
    {
        $total = $this->service->calculateTotalPortfolioPrincipal($this->loans);

        $this->assertEquals(650000.00, $total);
    }

    /**
     * Test 2: Calculate total portfolio current balance
     */
    public function testCalculateTotalPortfolioCurrentBalance()
    {
        $total = $this->service->calculateTotalPortfolioCurrentBalance($this->loans);

        $this->assertEquals(503000.00, $total);
    }

    /**
     * Test 3: Calculate aggregate weighted average rate
     */
    public function testCalculateWeightedAverageRate()
    {
        $weightedRate = $this->service->calculateWeightedAverageRate($this->loans);

        // (200k*0.04 + 150k*0.035 + 300k*0.05) / 650k = ~0.0428
        $this->assertGreaterThan(0.042, $weightedRate);
        $this->assertLessThan(0.044, $weightedRate);
    }

    /**
     * Test 4: Analyze portfolio composition by loan type
     */
    public function testAnalyzePortfolioComposition()
    {
        $composition = $this->service->analyzePortfolioComposition($this->loans);

        $this->assertIsArray($composition);
        $this->assertArrayHasKey('total_loans', $composition);
        $this->assertArrayHasKey('total_principal', $composition);
        $this->assertArrayHasKey('total_balance', $composition);
        $this->assertEquals(3, $composition['total_loans']);
    }

    /**
     * Test 5: Calculate portfolio risk score
     */
    public function testCalculatePortfolioRiskScore()
    {
        $riskScore = $this->service->calculatePortfolioRiskScore($this->loans);

        // Risk based on rate variance and concentration
        $this->assertGreaterThan(0, $riskScore);
        $this->assertLessThan(100, $riskScore);
    }

    /**
     * Test 6: Identify high-risk loans
     */
    public function testIdentifyHighRiskLoans()
    {
        $highRiskLoans = $this->service->identifyHighRiskLoans($this->loans);

        $this->assertIsArray($highRiskLoans);
        // High-risk: highest rate (0.05) and longest term
        $this->assertNotEmpty($highRiskLoans);
    }

    /**
     * Test 7: Calculate portfolio concentration metrics
     */
    public function testCalculatePortfolioConcentrationMetrics()
    {
        $concentration = $this->service->calculatePortfolioConcentrationMetrics($this->loans);

        $this->assertArrayHasKey('herfindahl_index', $concentration);
        $this->assertArrayHasKey('max_loan_percentage', $concentration);
        $this->assertArrayHasKey('concentration_level', $concentration);
        $this->assertGreaterThan(0.3, $concentration['max_loan_percentage']);  // 300k/650k = ~46%
    }

    /**
     * Test 8: Generate portfolio performance dashboard
     */
    public function testGeneratePortfolioPerformanceDashboard()
    {
        $dashboard = $this->service->generatePortfolioPerformanceDashboard($this->loans);

        $this->assertIsArray($dashboard);
        $this->assertArrayHasKey('total_principal', $dashboard);
        $this->assertArrayHasKey('total_balance', $dashboard);
        $this->assertArrayHasKey('weighted_avg_rate', $dashboard);
        $this->assertArrayHasKey('risk_score', $dashboard);
        $this->assertArrayHasKey('loans', $dashboard);
    }

    /**
     * Test 9: Calculate portfolio equity position
     */
    public function testCalculatePortfolioEquityPosition()
    {
        $equity = $this->service->calculatePortfolioEquityPosition($this->loans);

        // Total paid = principal - current_balance
        $this->assertEquals(147000.00, $equity);
    }

    /**
     * Test 10: Compare loan performance within portfolio
     */
    public function testCompareLoanPerformanceWithinPortfolio()
    {
        $comparison = $this->service->compareLoanPerformanceWithinPortfolio($this->loans);

        $this->assertIsArray($comparison);
        $this->assertCount(3, $comparison);
        $this->assertArrayHasKey('loan_id', $comparison[0]);
        $this->assertArrayHasKey('payoff_percentage', $comparison[0]);
    }

    /**
     * Test 11: Calculate portfolio debt-to-income proxy
     */
    public function testCalculatePortfolioDebtRatio()
    {
        $debtRatio = $this->service->calculatePortfolioDebtRatio($this->loans, 150000);  // Annual income

        // 503k balance / 150k income = 3.35
        $this->assertGreaterThan(3.0, $debtRatio);
        $this->assertLessThan(4.0, $debtRatio);
    }

    /**
     * Test 12: Export portfolio analytics to JSON
     */
    public function testExportPortfolioAnalyticsToJSON()
    {
        $dashboard = $this->service->generatePortfolioPerformanceDashboard($this->loans);
        $json = $this->service->exportToJSON($dashboard);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('total_principal', $decoded);
    }

    /**
     * Test 13: Identify portfolio rebalancing opportunities
     */
    public function testIdentifyRebalancingOpportunities()
    {
        $opportunities = $this->service->identifyRebalancingOpportunities($this->loans);

        $this->assertIsArray($opportunities);
        // Should suggest refinancing high-rate or high-concentration loans
        $this->assertNotEmpty($opportunities);
    }

    /**
     * Test 14: Calculate aggregate interest paid
     */
    public function testCalculateAggregateInterestPaid()
    {
        $interestPaid = $this->service->calculateAggregateInterestPaid($this->loans, 0.04);

        $this->assertGreaterThan(0, $interestPaid);
    }

    /**
     * Test 15: Generate loan-by-loan analytics report
     */
    public function testGenerateLoanByLoanAnalyticsReport()
    {
        $report = $this->service->generateLoanByLoanAnalyticsReport($this->loans);

        $this->assertIsArray($report);
        $this->assertCount(3, $report);
        $this->assertArrayHasKey('loan_id', $report[0]);
        $this->assertArrayHasKey('principal', $report[0]);
        $this->assertArrayHasKey('rate', $report[0]);
        $this->assertArrayHasKey('payoff_percentage', $report[0]);
    }

    /**
     * Test 16: Calculate portfolio quality score
     */
    public function testCalculatePortfolioQualityScore()
    {
        $qualityScore = $this->service->calculatePortfolioQualityScore($this->loans);

        // Quality: diversity, not concentration, reasonable rates
        $this->assertGreaterThan(0, $qualityScore);
        $this->assertLessThan(100, $qualityScore);
    }
}
