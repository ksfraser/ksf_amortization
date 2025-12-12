<?php
namespace Tests\Integration;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\AdvancedReportingService;
use Ksfraser\Amortizations\Services\PortfolioManagementService;
use PHPUnit\Framework\TestCase;

class PortfolioReportingIntegrationTest extends TestCase {
    private PortfolioManagementService $portfolioService;
    private AdvancedReportingService $reportingService;
    private array $testLoans;
    private array $testSchedule;

    protected function setUp(): void {
        $this->portfolioService = new PortfolioManagementService();
        $this->reportingService = new AdvancedReportingService();
        $this->testLoans = $this->createTestLoans();
        $this->testSchedule = $this->createTestSchedule();
    }

    private function createTestLoans(): array {
        $loans = [];
        
        $loan1 = new Loan();
        $loan1->setId(1)->setPrincipal(150000)->setAnnualRate(0.05)->setMonths(360);
        $loans[] = $loan1;

        $loan2 = new Loan();
        $loan2->setId(2)->setPrincipal(200000)->setAnnualRate(0.06)->setMonths(360);
        $loans[] = $loan2;

        return $loans;
    }

    private function createTestSchedule(): array {
        $schedule = [];
        for ($i = 0; $i < 360; $i++) {
            $schedule[$i] = [
                'payment' => 800 + ($i % 100),
                'principal' => 400,
                'interest' => 350 - ($i % 50),
                'balance' => 150000 - ($i * 400)
            ];
        }
        return $schedule;
    }

    public function testPortfolioMetricsInReport(): void {
        $portfolio = $this->portfolioService->exportPortfolioReport($this->testLoans);
        
        $summary = $this->reportingService->generateFinancialSummary(
            $this->testLoans[0],
            $this->testSchedule
        );

        $this->assertArrayHasKey('profitability', $portfolio);
        $this->assertArrayHasKey('total_interest', $summary);
        $this->assertGreaterThan(0, $portfolio['profitability']['total_interest']);
    }

    public function testPortfolioYieldInCharting(): void {
        $yield = $this->portfolioService->calculatePortfolioYield($this->testLoans);
        $trends = $this->reportingService->generatePaymentTrendChart($this->testSchedule);

        $this->assertGreaterThan(0, $yield);
        $this->assertArrayHasKey('payments', $trends);
        $this->assertGreaterThan(0, count($trends['payments']));
    }

    public function testDiversificationReporting(): void {
        $diversification = $this->portfolioService->getLoanDiversification($this->testLoans);
        $visualization = $this->reportingService->visualizePaymentSchedule($this->testSchedule);

        $this->assertGreaterThan(0, $diversification['diversification_score']);
        $this->assertGreaterThan(0, count($visualization));
    }

    public function testMaturityAnalysisInReport(): void {
        $maturity = $this->portfolioService->analyzeLoanMaturity($this->testLoans);
        $analysis = $this->reportingService->generateMonthlyAnalysis($this->testSchedule);

        $this->assertArrayHasKey('five_plus_years', $maturity);
        $this->assertArrayHasKey('total_periods', $analysis);
    }

    public function testProfitabilityExportFormats(): void {
        $profitability = $this->portfolioService->calculateProfitability($this->testLoans);
        
        $csv = $this->reportingService->exportToCSV($this->testSchedule);
        $json = $this->reportingService->exportToJSON($this->testLoans[0], $this->testSchedule);
        $xml = $this->reportingService->exportToXML($this->testLoans[0], $this->testSchedule);

        $this->assertGreaterThan(0, $profitability['total_interest']);
        $this->assertStringContainsString(',', $csv);
        $this->assertStringContainsString('{', $json);
        $this->assertStringContainsString('<?xml', $xml);
    }

    public function testPortfolioHtmlGeneration(): void {
        $portfolio = $this->portfolioService->exportPortfolioReport($this->testLoans);
        $html = $this->reportingService->generateHTML($this->testLoans[0], $this->testSchedule);

        $this->assertArrayHasKey('total_loans', $portfolio);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('Loan Amortization Report', $html);
    }

    public function testComparisonReportWithPortfolios(): void {
        $schedule1 = $this->testSchedule;
        $schedule2 = array_slice($this->testSchedule, 0, 240);

        $comparison = $this->reportingService->generateComparisonReport($schedule1, $schedule2);
        $portfolio = $this->portfolioService->exportPortfolioReport($this->testLoans);

        $this->assertArrayHasKey('interest_savings', $comparison);
        $this->assertArrayHasKey('portfolio_yield', $portfolio);
    }

    public function testAggregatePortfolioMetricsInReport(): void {
        $portfolio1 = [$this->testLoans[0]];
        $portfolio2 = [$this->testLoans[1]];

        $aggregated = $this->portfolioService->aggregatePortfolioMetrics([$portfolio1, $portfolio2]);
        $report = $this->reportingService->generateFinancialSummary(
            $this->testLoans[0],
            $this->testSchedule
        );

        $this->assertEquals(2, $aggregated['total_loans']);
        $this->assertGreaterThan(0, $report['total_cost']);
    }

    public function testRiskProfileReporting(): void {
        $riskProfile = $this->portfolioService->getPortfolioRiskProfile($this->testLoans);
        $summary = $this->reportingService->generateFinancialSummary(
            $this->testLoans[0],
            $this->testSchedule
        );

        $this->assertArrayHasKey('portfolio_risk_level', $riskProfile);
        $this->assertArrayHasKey('interest_percentage', $summary);
    }

    public function testInterestAccrualWithPortfolio(): void {
        $accrual = $this->reportingService->calculateInterestAccrual($this->testSchedule);
        $defaultRate = $this->portfolioService->calculateDefaultRate($this->testLoans);

        $this->assertGreaterThan(0, count($accrual));
        $this->assertGreaterThanOrEqual(0, $defaultRate);
    }

    public function testPaymentHistorySummaryWithPortfolio(): void {
        $paymentHistory = $this->reportingService->summarizePaymentHistory($this->testSchedule);
        $profitability = $this->portfolioService->calculateProfitability($this->testLoans);

        $this->assertArrayHasKey('total_payments_scheduled', $paymentHistory);
        $this->assertArrayHasKey('profit_margin_percent', $profitability);
    }

    public function testCompletePortfolioReportingWorkflow(): void {
        // 1. Generate portfolio metrics
        $portfolio = $this->portfolioService->exportPortfolioReport($this->testLoans);
        
        // 2. Create financial reports
        $summary = $this->reportingService->generateFinancialSummary(
            $this->testLoans[0],
            $this->testSchedule
        );

        // 3. Export in multiple formats
        $csv = $this->reportingService->exportToCSV($this->testSchedule);
        $json = $this->reportingService->exportToJSON($this->testLoans[0], $this->testSchedule);

        // 4. Verify integration
        $this->assertGreaterThan(0, $portfolio['total_loans']);
        $this->assertGreaterThan(0, $summary['total_interest']);
        $this->assertStringContainsString(',', $csv);
        $this->assertIsString($json);
    }

    public function testGroupingAndReporting(): void {
        $groupedByRate = $this->portfolioService->groupLoansByRate($this->testLoans);
        $html = $this->reportingService->generateHTML($this->testLoans[0], $this->testSchedule);

        $this->assertIsArray($groupedByRate);
        $this->assertStringContainsString('table', $html);
    }

    public function testYieldCalculationInReport(): void {
        $yield = $this->portfolioService->calculatePortfolioYield($this->testLoans);
        $trends = $this->reportingService->generatePaymentTrendChart($this->testSchedule);
        
        $this->assertGreaterThan(0, $yield);
        $this->assertArrayHasKey('interests', $trends);
    }
}
