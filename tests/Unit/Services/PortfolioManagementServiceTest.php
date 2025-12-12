<?php
namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\PortfolioManagementService;
use PHPUnit\Framework\TestCase;

class PortfolioManagementServiceTest extends TestCase {
    private PortfolioManagementService $service;
    private array $testLoans = [];

    protected function setUp(): void {
        $this->service = new PortfolioManagementService();
        $this->testLoans = $this->createTestLoans();
    }

    private function createTestLoans(): array {
        $loans = [];
        
        // Loan 1: 5% rate, 60 months
        $loan1 = new Loan();
        $loan1->setId(1)->setPrincipal(100000)->setAnnualRate(0.05)->setMonths(60);
        $loans[] = $loan1;

        // Loan 2: 6% rate, 120 months
        $loan2 = new Loan();
        $loan2->setId(2)->setPrincipal(250000)->setAnnualRate(0.06)->setMonths(120);
        $loans[] = $loan2;

        // Loan 3: 4% rate, 48 months
        $loan3 = new Loan();
        $loan3->setId(3)->setPrincipal(75000)->setAnnualRate(0.04)->setMonths(48);
        $loans[] = $loan3;

        // Loan 4: 8% rate, 84 months
        $loan4 = new Loan();
        $loan4->setId(4)->setPrincipal(150000)->setAnnualRate(0.08)->setMonths(84);
        $loans[] = $loan4;

        return $loans;
    }

    public function testGroupLoansByStatus(): void {
        $grouped = $this->service->groupLoansByStatus($this->testLoans);

        $this->assertIsArray($grouped);
        $this->assertArrayHasKey('active', $grouped);
        $this->assertArrayHasKey('closed', $grouped);
        $this->assertArrayHasKey('defaulted', $grouped);
        // All loans are currently grouped as active since no status tracking exists
        $this->assertEquals(4, count($grouped['active']));
    }

    public function testGroupLoansByType(): void {
        $grouped = $this->service->groupLoansByType($this->testLoans);

        $this->assertIsArray($grouped);
        $this->assertGreaterThanOrEqual(1, count($grouped));
    }

    public function testGroupLoansByRate(): void {
        $grouped = $this->service->groupLoansByRate($this->testLoans, 0.01);

        $this->assertIsArray($grouped);
        $this->assertGreaterThanOrEqual(3, count($grouped));
    }

    public function testCalculatePortfolioYield(): void {
        $yield = $this->service->calculatePortfolioYield($this->testLoans);

        $this->assertIsFloat($yield);
        $this->assertGreaterThan(0, $yield);
        $this->assertLessThan(0.15, $yield);
    }

    public function testCalculatePortfolioYieldEmptyLoans(): void {
        $yield = $this->service->calculatePortfolioYield([]);

        $this->assertEquals(0.0, $yield);
    }

    public function testCalculateDefaultRate(): void {
        $rate = $this->service->calculateDefaultRate($this->testLoans);

        $this->assertIsFloat($rate);
        $this->assertGreaterThanOrEqual(0, $rate);
        $this->assertLessThanOrEqual(1, $rate);
    }

    public function testRankLoansByPerformance(): void {
        $ranked = $this->service->rankLoansByPerformance($this->testLoans);

        $this->assertIsArray($ranked);
        $this->assertCount(4, $ranked);
        $this->assertGreaterThan($ranked[3]['yield'], $ranked[0]['yield']);
    }

    public function testCalculateProfitability(): void {
        $profitability = $this->service->calculateProfitability($this->testLoans);

        $this->assertIsArray($profitability);
        $this->assertArrayHasKey('total_principal', $profitability);
        $this->assertArrayHasKey('total_interest', $profitability);
        $this->assertArrayHasKey('total_cost', $profitability);
        $this->assertArrayHasKey('profitability_ratio', $profitability);
        $this->assertGreaterThan(0, $profitability['total_principal']);
        $this->assertGreaterThan(0, $profitability['total_interest']);
    }

    public function testGetAveragePaymentRate(): void {
        $avgRate = $this->service->getAveragePaymentRate($this->testLoans);

        $this->assertIsFloat($avgRate);
        $this->assertGreaterThan(0.04, $avgRate);
        $this->assertLessThan(0.08, $avgRate);
    }

    public function testGetAveragePaymentRateEmptyLoans(): void {
        $avgRate = $this->service->getAveragePaymentRate([]);

        $this->assertEquals(0.0, $avgRate);
    }

    public function testGetLoanDiversification(): void {
        $diversification = $this->service->getLoanDiversification($this->testLoans);

        $this->assertIsArray($diversification);
        $this->assertArrayHasKey('rate_concentration', $diversification);
        $this->assertArrayHasKey('term_concentration', $diversification);
        $this->assertArrayHasKey('diversification_score', $diversification);
        $this->assertGreaterThanOrEqual(0, $diversification['diversification_score']);
        $this->assertLessThanOrEqual(1, $diversification['diversification_score']);
    }

    public function testAnalyzeLoanMaturity(): void {
        $maturity = $this->service->analyzeLoanMaturity($this->testLoans);

        $this->assertIsArray($maturity);
        $this->assertArrayHasKey('current', $maturity);
        $this->assertArrayHasKey('less_than_12_months', $maturity);
        $this->assertArrayHasKey('less_than_5_years', $maturity);
        $this->assertArrayHasKey('five_plus_years', $maturity);
    }

    public function testGetPortfolioRiskProfile(): void {
        $riskProfile = $this->service->getPortfolioRiskProfile($this->testLoans);

        $this->assertIsArray($riskProfile);
        $this->assertArrayHasKey('average_risk_score', $riskProfile);
        $this->assertArrayHasKey('high_risk_count', $riskProfile);
        $this->assertArrayHasKey('medium_risk_count', $riskProfile);
        $this->assertArrayHasKey('low_risk_count', $riskProfile);
        $this->assertArrayHasKey('portfolio_risk_level', $riskProfile);
        $this->assertContains($riskProfile['portfolio_risk_level'], ['low', 'medium', 'high']);
    }

    public function testExportPortfolioReport(): void {
        $report = $this->service->exportPortfolioReport($this->testLoans);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('total_loans', $report);
        $this->assertArrayHasKey('total_principal', $report);
        $this->assertArrayHasKey('portfolio_yield', $report);
        $this->assertArrayHasKey('default_rate', $report);
        $this->assertArrayHasKey('profitability', $report);
        $this->assertArrayHasKey('diversification', $report);
        $this->assertEquals(4, $report['total_loans']);
    }

    public function testAggregatePortfolioMetrics(): void {
        $portfolio1 = array_slice($this->testLoans, 0, 2);
        $portfolio2 = array_slice($this->testLoans, 2);
        
        $aggregated = $this->service->aggregatePortfolioMetrics([$portfolio1, $portfolio2]);

        $this->assertIsArray($aggregated);
        $this->assertArrayHasKey('total_loans', $aggregated);
        $this->assertEquals(4, $aggregated['total_loans']);
    }
}
