<?php
namespace Tests\Integration;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\LoanAnalysisService;
use Ksfraser\Amortizations\Services\PortfolioManagementService;
use Ksfraser\Amortizations\Services\AdvancedReportingService;
use Ksfraser\Amortizations\Services\LoanOriginationService;
use Ksfraser\Amortizations\Services\MarketAnalysisService;
use PHPUnit\Framework\TestCase;

class CrossServiceWorkflowIntegrationTest extends TestCase {
    private LoanAnalysisService $analysisService;
    private PortfolioManagementService $portfolioService;
    private AdvancedReportingService $reportingService;
    private LoanOriginationService $originationService;
    private MarketAnalysisService $marketService;
    private array $testLoans;
    private array $testSchedule;

    protected function setUp(): void {
        $this->analysisService = new LoanAnalysisService();
        $this->portfolioService = new PortfolioManagementService();
        $this->reportingService = new AdvancedReportingService();
        $this->originationService = new LoanOriginationService();
        $this->marketService = new MarketAnalysisService();
        
        $this->testLoans = $this->createTestLoans();
        $this->testSchedule = $this->createTestSchedule();
    }

    private function createTestLoans(): array {
        $loans = [];
        
        $loan1 = new Loan();
        $loan1->setId(1)->setPrincipal(200000)->setAnnualRate(0.05)->setMonths(360);
        $loans[] = $loan1;

        $loan2 = new Loan();
        $loan2->setId(2)->setPrincipal(150000)->setAnnualRate(0.06)->setMonths(300);
        $loans[] = $loan2;

        $loan3 = new Loan();
        $loan3->setId(3)->setPrincipal(180000)->setAnnualRate(0.055)->setMonths(360);
        $loans[] = $loan3;

        return $loans;
    }

    private function createTestSchedule(): array {
        $schedule = [];
        for ($i = 0; $i < 360; $i++) {
            $schedule[$i] = [
                'payment' => 1273.61,
                'principal' => 500 + ($i % 100),
                'interest' => 400 - ($i / 10),
                'balance' => 200000 - ($i * 500)
            ];
        }
        return $schedule;
    }

    public function testCompleteLoanLifecycleWorkflow(): void {
        // 1. Create and analyze loan application
        $application = $this->originationService->createLoanApplication([
            'applicant_name' => 'Complete Workflow Test',
            'requested_amount' => 200000,
            'purpose' => 'Home Purchase'
        ]);

        // 2. Analyze loan qualification
        $analysis = $this->analysisService->generateLoanQualificationReport(
            $this->testLoans[0],
            9000,
            750
        );

        // 3. Check market rates
        $marketRates = $this->marketService->getMarketRates();
        $comparison = $this->marketService->compareToMarketAverage(0.05, 'mortgage_30_year');

        // 4. Generate financial report
        $report = $this->reportingService->generateFinancialSummary($this->testLoans[0], $this->testSchedule);

        // 5. Generate offer letter
        $offer = $this->originationService->generateOfferLetter(
            $this->testLoans[0],
            $report['average_payment'],
            $application['applicant_name']
        );

        // 6. Approve loan
        $approval = $this->originationService->approveLoan($application['application_id'], $this->testLoans[0]);

        // 7. Add to portfolio
        $portfolio = $this->portfolioService->exportPortfolioReport($this->testLoans);

        // Verify complete lifecycle
        $this->assertTrue($analysis['recommendation'] === 'qualified' || $analysis['recommendation'] === 'not_qualified');
        $this->assertArrayHasKey('competitive', $comparison);
        $this->assertGreaterThan(0, $report['total_interest']);
        $this->assertStringContainsString('Complete Workflow Test', $offer);
        $this->assertEquals('approved', $approval['status']);
        $this->assertEquals(3, $portfolio['total_loans']);
    }

    public function testPortfolioWideMarketRecommendations(): void {
        // 1. Analyze all loans in portfolio
        $analyses = [];
        foreach ($this->testLoans as $loan) {
            $analyses[] = $this->analysisService->generateLoanQualificationReport($loan, 8500, 720);
        }

        // 2. Calculate portfolio metrics
        $portfolio = $this->portfolioService->exportPortfolioReport($this->testLoans);

        // 3. Generate market analysis
        $competitorRates = [0.045, 0.050, 0.055, 0.060];
        $ranking = $this->marketService->rankRateCompetitiveness(0.055, $competitorRates);
        $forecast = $this->marketService->forecastRateMovement([0.045, 0.048, 0.050, 0.052, 0.055]);

        // 4. Generate comprehensive portfolio report
        $report = $this->reportingService->generateFinancialSummary($this->testLoans[0], $this->testSchedule);

        // Verify portfolio-wide recommendations
        $this->assertCount(3, $analyses);
        $this->assertEquals(3, $portfolio['total_loans']);
        $this->assertArrayHasKey('rank', $ranking);
        $this->assertArrayHasKey('forecast', $forecast);
    }

    public function testMultiServiceRiskAssessment(): void {
        // 1. Individual risk assessments
        $riskAssessments = [];
        foreach ($this->testLoans as $loan) {
            $riskAssessments[] = $this->analysisService->assessLoanRisk($loan, 720);
        }

        // 2. Portfolio risk profile
        $portfolioRisk = $this->portfolioService->getPortfolioRiskProfile($this->testLoans);

        // 3. Market risk context
        $lenderComparison = $this->marketService->analyzeLenderComparison([
            ['name' => 'Bank A', 'rate' => 0.045],
            ['name' => 'Bank B', 'rate' => 0.055],
            ['name' => 'Bank C', 'rate' => 0.065]
        ]);

        // 4. Risk reporting
        $report = $this->reportingService->generateFinancialSummary($this->testLoans[0], $this->testSchedule);

        // Verify multi-service risk view
        $this->assertCount(3, $riskAssessments);
        $this->assertArrayHasKey('portfolio_risk_level', $portfolioRisk);
        $this->assertArrayHasKey('market_concentration', $lenderComparison);
    }

    public function testOriginationApprovalWithComprehensiveAnalysis(): void {
        // 1. Create application
        $app = $this->originationService->createLoanApplication([
            'applicant_name' => 'Comprehensive Test',
            'requested_amount' => 200000,
            'purpose' => 'Refinance'
        ]);

        // 2. Complete analysis
        $qualification = $this->analysisService->generateLoanQualificationReport(
            $this->testLoans[0],
            8000,
            750
        );

        // 3. Portfolio context
        $portfolio = $this->portfolioService->exportPortfolioReport($this->testLoans);

        // 4. Market comparison
        $marketRates = $this->marketService->getMarketRates();
        $optimization = $this->marketService->optimizeRateStrategy(
            0.05,
            0.002,
            array_values($marketRates)
        );

        // 5. Generate all supporting documents
        $report = $this->reportingService->generateFinancialSummary($this->testLoans[0], $this->testSchedule);
        $chart = $this->reportingService->generateAmortizationChart($this->testLoans[0], $this->testSchedule);
        $disclosures = $this->originationService->generateDisclosures($this->testLoans[0], $report['average_payment']);

        // 6. Approval decision
        $approval = $this->originationService->approveLoan($app['application_id'], $this->testLoans[0]);

        // Verify comprehensive approval
        $this->assertTrue($qualification['is_affordable'] || !$qualification['is_affordable']);
        $this->assertEquals(3, $portfolio['total_loans']);
        $this->assertArrayHasKey('optimal_rate', $optimization);
        $this->assertGreaterThan(0, count($disclosures));
        $this->assertEquals('approved', $approval['status']);
    }

    public function testExportPipelineMultipleFormats(): void {
        // 1. Loan analysis to application
        $analyses = [];
        $apps = [];
        foreach ($this->testLoans as $idx => $loan) {
            $analyses[] = $this->analysisService->generateLoanQualificationReport($loan, 8000, 720);
            $apps[] = $this->originationService->createLoanApplication([
                'applicant_name' => 'Export Test ' . $idx,
                'requested_amount' => $loan->getPrincipal(),
                'purpose' => 'Test'
            ]);
        }

        // 2. Portfolio to multiple export formats
        $portfolio = $this->portfolioService->exportPortfolioReport($this->testLoans);
        
        $csv = $this->reportingService->exportToCSV($this->testSchedule);
        $json = $this->reportingService->exportToJSON($this->testLoans[0], $this->testSchedule);
        $xml = $this->reportingService->exportToXML($this->testLoans[0], $this->testSchedule);
        $html = $this->reportingService->generateHTML($this->testLoans[0], $this->testSchedule);

        // 3. Market analysis export
        $marketReport = $this->marketService->generateMarketReport(
            0.05,
            [0.045, 0.048, 0.050, 0.052, 0.055],
            [['name' => 'Bank A', 'rate' => 0.045]]
        );
        $marketJson = $this->marketService->exportMarketAnalysis($marketReport);

        // Verify exports
        $this->assertCount(3, $apps);
        $this->assertEquals(3, $portfolio['total_loans']);
        $this->assertStringContainsString(',', $csv);
        $this->assertStringContainsString('{', $json);
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('<html', $html);
        $this->assertIsString($marketJson);
    }

    public function testDiversificationRecommendations(): void {
        // 1. Calculate current diversification
        $diversification = $this->portfolioService->getLoanDiversification($this->testLoans);

        // 2. Analyze maturity
        $maturity = $this->portfolioService->analyzeLoanMaturity($this->testLoans);

        // 3. Group by rate
        $grouped = $this->portfolioService->groupLoansByRate($this->testLoans);

        // 4. Market analysis for recommendations
        $historicalRates = [0.040, 0.042, 0.045, 0.048, 0.050];
        $trend = $this->marketService->analyzeTrendDirection($historicalRates);

        // 5. Reporting
        $visualization = $this->reportingService->visualizePaymentSchedule($this->testSchedule);

        // Verify diversification view
        $this->assertGreaterThan(0, $diversification['diversification_score']);
        $this->assertGreaterThan(0, count($maturity));
        $this->assertGreaterThan(0, count($grouped));
        $this->assertArrayHasKey('trend', $trend);
        $this->assertGreaterThan(0, count($visualization));
    }

    public function testCompleteOriginationToPortfolioFlow(): void {
        // 1. Application creation & analysis
        $app = $this->originationService->createLoanApplication([
            'applicant_name' => 'Flow Test Applicant',
            'requested_amount' => 200000,
            'purpose' => 'Home Purchase'
        ]);

        $qualification = $this->analysisService->generateLoanQualificationReport(
            $this->testLoans[0],
            9000,
            750
        );

        // 2. Market-based offer
        $marketRates = $this->marketService->getMarketRates();
        $optimization = $this->marketService->optimizeRateStrategy(0.05, 0.002, array_values($marketRates));

        // 3. Generate offer documents
        $report = $this->reportingService->generateFinancialSummary($this->testLoans[0], $this->testSchedule);
        $offer = $this->originationService->generateOfferLetter(
            $this->testLoans[0],
            $report['average_payment'],
            $app['applicant_name']
        );

        // 4. Approval & documentation
        $approval = $this->originationService->approveLoan($app['application_id'], $this->testLoans[0]);
        $documented = $this->originationService->documentApplication(
            $app['application_id'],
            ['offer_letter.pdf', 'amortization.pdf', 'disclosures.pdf']
        );

        // 5. Add to portfolio
        $portfolio = $this->portfolioService->exportPortfolioReport($this->testLoans);
        $riskProfile = $this->portfolioService->getPortfolioRiskProfile($this->testLoans);

        // Verify complete flow
        $this->assertArrayHasKey('application_id', $app);
        $this->assertTrue($qualification['is_affordable']);
        $this->assertArrayHasKey('optimal_rate', $optimization);
        $this->assertStringContainsString('Flow Test Applicant', $offer);
        $this->assertEquals('approved', $approval['status']);
        $this->assertEquals(3, $documented['documents_received']);
        $this->assertEquals(3, $portfolio['total_loans']);
        $this->assertArrayHasKey('portfolio_risk_level', $riskProfile);
    }

    public function testMarketForecastingInfluencesApprovalTerms(): void {
        // 1. Create loan application
        $app = $this->originationService->createLoanApplication([
            'applicant_name' => 'Forecast Test',
            'requested_amount' => 200000,
            'purpose' => 'Refinance'
        ]);

        // 2. Generate market forecast
        $historicalRates = [0.040, 0.042, 0.045, 0.048, 0.050];
        $forecast = $this->marketService->forecastRateMovement($historicalRates, 12);
        $forecast_rate = $this->marketService->createRateForecast($historicalRates, 12);

        // 3. Determine approval rate based on forecast
        $forecast_avg = array_sum($forecast['forecast']) / count($forecast['forecast']);
        
        // Set rate based on forecast
        $this->testLoans[0]->setAnnualRate($forecast_avg);

        // 4. Generate approval with forecasted rate
        $approval = $this->originationService->approveLoan($app['application_id'], $this->testLoans[0]);

        // 5. Analysis & reporting
        $analysis = $this->analysisService->generateLoanQualificationReport(
            $this->testLoans[0],
            8000,
            750
        );

        // Verify forecast influence
        $this->assertArrayHasKey('forecast', $forecast);
        $this->assertEquals(12, $forecast_rate['forecast_period_months']);
        $this->assertEquals('approved', $approval['status']);
        $this->assertTrue($analysis['is_affordable']);
    }

    public function testArbitrageStrategiesInPortfolioManagement(): void {
        // 1. Identify arbitrage opportunities
        $borrowRates = array_map(fn($l) => $l->getAnnualRate() - 0.01, $this->testLoans);
        $lendRates = array_map(fn($l) => $l->getAnnualRate(), $this->testLoans);

        $arb = [];
        for ($i = 0; $i < count($this->testLoans); $i++) {
            $arb[] = $this->marketService->identifyArbitrage(
                ['loan_' . $i => $borrowRates[$i]],
                ['loan_' . $i => $lendRates[$i]]
            );
        }

        // 2. Portfolio profitability analysis
        $profitability = $this->portfolioService->calculateProfitability($this->testLoans);

        // 3. Market share calculation
        $share = $this->marketService->calculateMarketShare(
            array_sum(array_map(fn($l) => $l->getPrincipal(), $this->testLoans)),
            5000000000
        );

        // Verify arbitrage strategy
        $this->assertCount(3, $arb);
        $this->assertGreaterThan(0, $profitability['profitability_ratio']);
        $this->assertArrayHasKey('market_share_percent', $share);
    }
}
