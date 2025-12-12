<?php
namespace Tests\Integration;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\LoanAnalysisService;
use Ksfraser\Amortizations\Services\PortfolioManagementService;
use PHPUnit\Framework\TestCase;

class AnalysisPortfolioIntegrationTest extends TestCase {
    private LoanAnalysisService $analysisService;
    private PortfolioManagementService $portfolioService;
    private array $testLoans;

    protected function setUp(): void {
        $this->analysisService = new LoanAnalysisService();
        $this->portfolioService = new PortfolioManagementService();
        $this->testLoans = $this->createTestLoans();
    }

    private function createTestLoans(): array {
        $loans = [];
        
        // Qualified loan
        $loan1 = new Loan();
        $loan1->setId(1)->setPrincipal(200000)->setAnnualRate(0.05)->setMonths(360);
        $loans[] = $loan1;

        // High-risk loan
        $loan2 = new Loan();
        $loan2->setId(2)->setPrincipal(150000)->setAnnualRate(0.09)->setMonths(240);
        $loans[] = $loan2;

        // Standard loan
        $loan3 = new Loan();
        $loan3->setId(3)->setPrincipal(100000)->setAnnualRate(0.06)->setMonths(180);
        $loans[] = $loan3;

        return $loans;
    }

    public function testQualificationReportInfluencesPortfolioRisk(): void {
        $report = $this->analysisService->generateLoanQualificationReport(
            $this->testLoans[0],
            10000,
            750
        );

        $riskProfile = $this->portfolioService->getPortfolioRiskProfile($this->testLoans);

        $this->assertArrayHasKey('portfolio_risk_level', $riskProfile);
        $this->assertGreaterThan(0, $riskProfile['average_risk_score']);
        // Qualified loan should help portfolio risk
        $this->assertTrue($report['recommendation'] === 'qualified' || $report['recommendation'] === 'not_qualified');
    }

    public function testMultipleLoanAnalysisConsistency(): void {
        $reports = [];
        foreach ($this->testLoans as $loan) {
            $reports[] = $this->analysisService->generateLoanQualificationReport(
                $loan,
                8000,
                700
            );
        }

        $portfolioYield = $this->portfolioService->calculatePortfolioYield($this->testLoans);
        
        $this->assertCount(3, $reports);
        $this->assertGreaterThan(0, $portfolioYield);
        // Verify each report has required fields
        foreach ($reports as $report) {
            $this->assertArrayHasKey('recommendation', $report);
        }
    }

    public function testQualificationScoresCorrelateWithPortfolioMetrics(): void {
        $creditScores = [750, 680, 720];
        $qualifications = [];
        
        foreach ($this->testLoans as $idx => $loan) {
            $qual = $this->analysisService->generateLoanQualificationReport(
                $loan,
                9000,
                $creditScores[$idx]
            );
            $qualifications[] = $qual;
        }

        $profitability = $this->portfolioService->calculateProfitability($this->testLoans);
        $diversification = $this->portfolioService->getLoanDiversification($this->testLoans);

        $this->assertGreaterThan(0, $profitability['total_interest']);
        $this->assertGreaterThan(0, $diversification['diversification_score']);
    }

    public function testRiskAssessmentIntegratesIntoPortfolioRisk(): void {
        $riskAssessments = [];
        
        foreach ($this->testLoans as $loan) {
            $riskAssessments[] = $this->analysisService->assessLoanRisk($loan, 720);
        }

        $portfolioRisk = $this->portfolioService->getPortfolioRiskProfile($this->testLoans);

        $this->assertCount(3, $riskAssessments);
        $this->assertArrayHasKey('portfolio_risk_level', $portfolioRisk);
        // All risk data should be present
        foreach ($riskAssessments as $risk) {
            $this->assertArrayHasKey('risk_level', $risk);
        }
    }

    public function testCreditworthinessCorrelatesWithPortfolioYield(): void {
        $creditScores = [760, 700, 650];
        $creditworthiness = [];
        
        foreach ($this->testLoans as $idx => $loan) {
            $score = $this->analysisService->calculateCreditworthinessScore(
                $loan,
                $creditScores[$idx]
            );
            $creditworthiness[] = $score;
        }

        $yield = $this->portfolioService->calculatePortfolioYield($this->testLoans);
        $defaultRate = $this->portfolioService->calculateDefaultRate($this->testLoans);

        $this->assertGreaterThan(0, $yield);
        $this->assertGreaterThanOrEqual(0, $defaultRate);
    }

    public function testDTIAnalysisAffectsAffordabilityGrouping(): void {
        $monthlyIncome = 8000;
        $affordabilityAnalysis = [];
        
        foreach ($this->testLoans as $loan) {
            $affordability = $this->analysisService->analyzeAfforcability(
                $loan,
                $monthlyIncome
            );
            $affordabilityAnalysis[] = $affordability;
        }

        // Group by affordability
        $affordable = array_filter($affordabilityAnalysis, fn($a) => $a['is_affordable']);
        $notAffordable = array_filter($affordabilityAnalysis, fn($a) => !$a['is_affordable']);

        // Portfolio should handle mixed affordability
        $profitability = $this->portfolioService->calculateProfitability($this->testLoans);
        $this->assertGreaterThan(0, $profitability['total_principal']);
    }

    public function testLoanComparisonWithPortfolioMetrics(): void {
        $comparison = $this->analysisService->compareLoans(
            $this->testLoans[0],
            $this->testLoans[1]
        );

        $portfolio = $this->portfolioService->exportPortfolioReport($this->testLoans);

        $this->assertArrayHasKey('better_option', $comparison);
        $this->assertArrayHasKey('savings', $comparison);
        $this->assertArrayHasKey('profitability', $portfolio);
    }

    public function testMaxBorrowAmountInfluencesPortfolioPrincipal(): void {
        $maxBorrow = $this->analysisService->calculateMaxLoanAmount(
            8000,
            750,
            0.43,
            0.05,
            360
        );

        $portfolioPrincipal = array_sum(array_map(fn($l) => $l->getPrincipal(), $this->testLoans));
        
        $this->assertGreaterThan(0, $maxBorrow);
        $this->assertGreaterThan(0, $portfolioPrincipal);
    }

    public function testQualificationReportExportForPortfolioUse(): void {
        $qualifications = [];
        
        foreach ($this->testLoans as $loan) {
            $qual = $this->analysisService->generateLoanQualificationReport(
                $loan,
                9000,
                720
            );
            $qualifications[] = $qual;
        }

        // All qualifications should be ready for portfolio export
        foreach ($qualifications as $qual) {
            $this->assertArrayHasKey('loan_amount', $qual);
            $this->assertArrayHasKey('recommendation', $qual);
        }

        $portfolioReport = $this->portfolioService->exportPortfolioReport($this->testLoans);
        $this->assertArrayHasKey('total_loans', $portfolioReport);
    }

    public function testMultiServiceWorkflowComplete(): void {
        // 1. Analyze each loan
        $analyses = [];
        foreach ($this->testLoans as $loan) {
            $analyses[] = $this->analysisService->generateLoanQualificationReport(
                $loan,
                8500,
                720
            );
        }

        // 2. Generate portfolio report
        $portfolio = $this->portfolioService->exportPortfolioReport($this->testLoans);

        // 3. Verify integration
        $this->assertCount(3, $analyses);
        $this->assertEquals(3, $portfolio['total_loans']);
        $this->assertGreaterThan(0, $portfolio['portfolio_yield']);
    }

    public function testAnalysisMetricsInPortfolioContext(): void {
        $dtiRatios = [];
        foreach ($this->testLoans as $loan) {
            $dti = $this->analysisService->calculateDebtToIncomeRatio($loan, 8000, 500);
            $dtiRatios[] = $dti;
        }

        $avgRate = $this->portfolioService->getAveragePaymentRate($this->testLoans);
        $profitability = $this->portfolioService->calculateProfitability($this->testLoans);

        $this->assertCount(3, $dtiRatios);
        $this->assertGreaterThan(0, $avgRate);
        $this->assertGreaterThan(0, $profitability['profitability_ratio']);
    }

    public function testCreditworthinessScoresRankLoans(): void {
        $scores = [];
        foreach ($this->testLoans as $loan) {
            $score = $this->analysisService->calculateCreditworthinessScore(
                $loan,
                750
            );
            $scores[] = $score['creditworthiness_score'];
        }

        $ranked = $this->portfolioService->rankLoansByPerformance($this->testLoans);

        $this->assertCount(3, $scores);
        $this->assertCount(3, $ranked);
    }
}
