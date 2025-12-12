<?php
namespace Tests\Integration;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\LoanOriginationService;
use Ksfraser\Amortizations\Services\MarketAnalysisService;
use PHPUnit\Framework\TestCase;

class OriginationMarketIntegrationTest extends TestCase {
    private LoanOriginationService $originationService;
    private MarketAnalysisService $marketService;
    private Loan $testLoan;

    protected function setUp(): void {
        $this->originationService = new LoanOriginationService();
        $this->marketService = new MarketAnalysisService();
        
        $this->testLoan = new Loan();
        $this->testLoan->setId(1)->setPrincipal(250000)->setAnnualRate(0.05)->setMonths(360);
    }

    public function testOfferRateBasedOnMarketAnalysis(): void {
        $marketRates = $this->marketService->getMarketRates();
        $marketAvg = array_sum($marketRates) / count($marketRates);

        // Set loan rate competitive to market
        $this->testLoan->setAnnualRate($marketAvg);

        $offer = $this->originationService->generateOfferLetter($this->testLoan, 1340, 'John Doe');
        $comparison = $this->marketService->compareToMarketAverage($this->testLoan->getAnnualRate(), 'mortgage_30_year');

        $this->assertStringContainsString('LOAN OFFER LETTER', $offer);
        $this->assertArrayHasKey('competitive', $comparison);
    }

    public function testApplicationRateOptimization(): void {
        $app = $this->originationService->createLoanApplication([
            'applicant_name' => 'Jane Smith',
            'requested_amount' => 250000,
            'purpose' => 'Home Purchase'
        ]);

        // Optimize rate based on market data
        $competitors = [0.045, 0.050, 0.055, 0.062];
        $suggestion = $this->marketService->suggestRateOptimization(0.05, 0.052, $competitors);

        $this->assertArrayHasKey('application_id', $app);
        $this->assertArrayHasKey('recommendation', $suggestion);
    }

    public function testApprovalWithCompetitiveRateCheck(): void {
        $marketRates = $this->marketService->getMarketRates();
        $marketAvg = array_sum($marketRates) / count($marketRates);

        $approval = $this->originationService->approveLoan('app_001', $this->testLoan);
        $ranking = $this->marketService->rankRateCompetitiveness(
            $this->testLoan->getAnnualRate(),
            array_values($marketRates)
        );

        $this->assertEquals('approved', $approval['status']);
        $this->assertArrayHasKey('rank', $ranking);
    }

    public function testMarketTrendInfluencesOfferTerm(): void {
        $historicalRates = [0.035, 0.040, 0.045, 0.050];
        $forecast = $this->marketService->forecastRateMovement($historicalRates, 3);

        $offer = $this->originationService->generateOfferLetter($this->testLoan, 1340, 'Bob Johnson');

        $this->assertArrayHasKey('forecast', $forecast);
        $this->assertStringContainsString('360 months', $offer);
    }

    public function testComplianceWithMarketStandards(): void {
        $compliance = $this->originationService->checkCompliance($this->testLoan);
        
        $lenderComparison = $this->marketService->analyzeLenderComparison([
            ['name' => 'Bank A', 'rate' => 0.045],
            ['name' => 'Bank B', 'rate' => 0.050],
            ['name' => 'Bank C', 'rate' => 0.055]
        ]);

        $this->assertEquals('compliant', $compliance['status']);
        $this->assertArrayHasKey('total_lenders', $lenderComparison);
    }

    public function testMaxBorrowCalculationWithMarketRate(): void {
        $marketRate = 0.05;
        
        $maxBorrow = $this->originationService->calculateMaxBorrow(
            8000,
            0.43,
            $marketRate,
            360
        );

        $optimization = $this->marketService->optimizeRateStrategy(
            0.05,
            0.002,
            [0.045, 0.050, 0.055]
        );

        $this->assertGreaterThan(0, $maxBorrow);
        $this->assertArrayHasKey('optimal_rate', $optimization);
    }

    public function testRejectionReasonBasedOnMarketRate(): void {
        $highRateLoan = new Loan();
        $highRateLoan->setPrincipal(100000)->setAnnualRate(0.15)->setMonths(360);

        $rejection = $this->originationService->rejectLoan('app_001', 'Rate exceeds market standards');
        $marketAnalysis = $this->marketService->getMarketRates();

        $this->assertEquals('rejected', $rejection['status']);
        $this->assertArrayHasKey('mortgage_30_year', $marketAnalysis);
    }

    public function testDocumentationWithMarketReport(): void {
        $docs = ['application.pdf', 'market_analysis.pdf', 'competitor_rates.pdf'];
        $documented = $this->originationService->documentApplication('app_001', $docs);

        $marketReport = $this->marketService->generateMarketReport(
            0.05,
            [0.045, 0.048, 0.050, 0.052],
            [['name' => 'Bank A', 'rate' => 0.045]]
        );

        $this->assertEquals(3, $documented['documents_received']);
        $this->assertArrayHasKey('report_date', $marketReport);
    }

    public function testApplicationProgressWithMarketForecast(): void {
        $progress = $this->originationService->trackApplicationProgress('app_001');
        
        $forecast = $this->marketService->createRateForecast(
            [0.045, 0.048, 0.050, 0.052],
            6
        );

        $this->assertArrayHasKey('current_stage', $progress);
        $this->assertArrayHasKey('forecast_period_months', $forecast);
    }

    public function testOfferLetterWithMarketCompetitiveness(): void {
        $competitors = [0.045, 0.050, 0.055, 0.060];
        $ranking = $this->marketService->rankRateCompetitiveness(0.052, $competitors);

        $offer = $this->originationService->generateOfferLetter($this->testLoan, 1340, 'Alice Brown');

        $this->assertArrayHasKey('percentile', $ranking);
        $this->assertStringContainsString('LOAN OFFER LETTER', $offer);
    }

    public function testMultiLoanOriginationStrategy(): void {
        $loans = [];
        for ($i = 0; $i < 3; $i++) {
            $loan = new Loan();
            $loan->setId($i)->setPrincipal(200000 + ($i * 50000))->setAnnualRate(0.05 + ($i * 0.01))->setMonths(360);
            $loans[] = $loan;
        }

        $marketRates = $this->marketService->getMarketRates();
        $comparison = $this->marketService->analyzeLenderComparison(
            array_map(fn($l) => ['name' => 'Loan ' . $l->getId(), 'rate' => $l->getAnnualRate()], $loans)
        );

        $this->assertEquals(3, count($loans));
        $this->assertArrayHasKey('average_rate', $comparison);
    }

    public function testCompleteOriginationMarketWorkflow(): void {
        // 1. Create application
        $app = $this->originationService->createLoanApplication([
            'applicant_name' => 'Charlie Davis',
            'requested_amount' => 250000,
            'purpose' => 'Home Purchase'
        ]);

        // 2. Analyze market conditions
        $marketRates = $this->marketService->getMarketRates();
        $historicalRates = [0.040, 0.042, 0.045, 0.048, 0.050];
        $forecast = $this->marketService->forecastRateMovement($historicalRates);

        // 3. Optimize rate
        $optimization = $this->marketService->optimizeRateStrategy(
            0.05,
            0.002,
            array_values($marketRates)
        );

        // 4. Generate offer with competitive rate
        $offer = $this->originationService->generateOfferLetter(
            $this->testLoan,
            1340.73,
            $app['applicant_name']
        );

        // 5. Approve loan
        $approval = $this->originationService->approveLoan('app_001', $this->testLoan);

        // Verify workflow
        $this->assertArrayHasKey('application_id', $app);
        $this->assertArrayHasKey('forecast', $forecast);
        $this->assertArrayHasKey('optimal_rate', $optimization);
        $this->assertStringContainsString('Charlie Davis', $offer);
        $this->assertEquals('approved', $approval['status']);
    }

    public function testArbitrageOpportunitiesInOrigination(): void {
        $borrowRates = ['auto' => 0.03, 'mortgage' => 0.04];
        $lendRates = ['auto' => 0.06, 'mortgage' => 0.075];

        $arb = $this->marketService->identifyArbitrage($borrowRates, $lendRates);
        
        $validation = $this->originationService->validateLoanApplication([
            'applicant_name' => 'Diana Evans',
            'requested_amount' => 250000,
            'purpose' => 'Refinance'
        ]);

        $this->assertArrayHasKey('arbitrage_opportunities', $arb);
        $this->assertTrue($validation['is_valid']);
    }

    public function testMarketShareImpactOnOrigination(): void {
        $share = $this->marketService->calculateMarketShare(50000000, 1000000000);

        $maxBorrow = $this->originationService->calculateMaxBorrow(8000, 0.43, 0.05, 360);

        $this->assertArrayHasKey('market_share_percent', $share);
        $this->assertGreaterThan(0, $maxBorrow);
    }
}
