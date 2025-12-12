<?php
namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\LoanAnalysisService;
use PHPUnit\Framework\TestCase;

class LoanAnalysisServiceTest extends TestCase {
    private LoanAnalysisService $service;
    private Loan $loan;

    protected function setUp(): void {
        $this->service = new LoanAnalysisService();
        $this->loan = new Loan();
        $this->loan->setPrincipal(250000);
        $this->loan->setAnnualRate(0.06);
        $this->loan->setMonths(360);
    }

    public function testCalculateLoanToValueRatio(): void {
        $propertyValue = 400000;
        $ltv = $this->service->calculateLoanToValueRatio($this->loan, $propertyValue);
        
        $this->assertIsFloat($ltv);
        $this->assertEquals(0.625, $ltv);
        $this->assertGreaterThan(0, $ltv);
        $this->assertLessThan(1, $ltv);
    }

    public function testCalculateLoanToValueRatioThrowsOnInvalidPropertyValue(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->calculateLoanToValueRatio($this->loan, 0);
    }

    public function testCalculateDebtToIncomeRatio(): void {
        $monthlyIncome = 8000;
        $otherDebts = 500;
        $dti = $this->service->calculateDebtToIncomeRatio($this->loan, $monthlyIncome, $otherDebts);

        $this->assertIsFloat($dti);
        $this->assertGreaterThan(0, $dti);
        $this->assertLessThan(1, $dti);
    }

    public function testCalculateDebtToIncomeRatioThrowsOnInvalidIncome(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->calculateDebtToIncomeRatio($this->loan, 0);
    }

    public function testCalculateCreditworthinessScore(): void {
        $creditScore = 750;
        $dti = 0.40;
        $employmentYears = 7;

        $result = $this->service->calculateCreditworthinessScore($this->loan, $creditScore, $dti, $employmentYears);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('creditworthiness_score', $result);
        $this->assertArrayHasKey('max_score', $result);
        $this->assertArrayHasKey('percentage', $result);
        $this->assertArrayHasKey('factors', $result);
        $this->assertEquals(1000, $result['max_score']);
        $this->assertGreaterThan(0, $result['creditworthiness_score']);
        $this->assertLessThanOrEqual(100, $result['percentage']);
    }

    public function testCreditworthinessScoreFactorsIncluded(): void {
        $result = $this->service->calculateCreditworthinessScore($this->loan);

        $factors = $result['factors'];
        $this->assertArrayHasKey('credit_score_factor', $factors);
        $this->assertArrayHasKey('dti_factor', $factors);
        $this->assertArrayHasKey('employment_factor', $factors);
        $this->assertArrayHasKey('loan_factor', $factors);
    }

    public function testAssessLoanRisk(): void {
        $creditScore = 720;
        $result = $this->service->assessLoanRisk($this->loan, $creditScore);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('risk_level', $result);
        $this->assertArrayHasKey('risk_score', $result);
        $this->assertArrayHasKey('max_risk_score', $result);
        $this->assertArrayHasKey('risk_factors', $result);
        $this->assertArrayHasKey('default_probability', $result);

        $this->assertContains($result['risk_level'], ['low', 'medium', 'high']);
        $this->assertGreaterThanOrEqual(0, $result['risk_score']);
        $this->assertLessThanOrEqual(100, $result['risk_score']);
    }

    public function testAssessLoanRiskHighCreditScore(): void {
        $result = $this->service->assessLoanRisk($this->loan, 800);
        $this->assertLessThan(50, $result['risk_score']);
    }

    public function testAssessLoanRiskLowCreditScore(): void {
        $result = $this->service->assessLoanRisk($this->loan, 600);
        $this->assertGreaterThan(0, $result['risk_score']);
    }

    public function testAnalyzeAffordability(): void {
        $monthlyIncome = 8000;
        $otherDebts = 300;

        $result = $this->service->analyzeAfforcability($this->loan, $monthlyIncome, $otherDebts);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_affordable', $result);
        $this->assertArrayHasKey('monthly_payment', $result);
        $this->assertArrayHasKey('dti_ratio', $result);
        $this->assertArrayHasKey('recommendation', $result);

        $this->assertIsBool($result['is_affordable']);
        $this->assertGreaterThan(0, $result['monthly_payment']);
        $this->assertContains($result['recommendation'], ['approved', 'denied']);
    }

    public function testAnalyzeAffordabilityWithZeroDebts(): void {
        $result = $this->service->analyzeAfforcability($this->loan, 8000, 0, 50000);

        $this->assertArrayHasKey('savings_coverage_months', $result);
        $this->assertGreaterThan(0, $result['savings_coverage_months']);
    }

    public function testCompareLoans(): void {
        $loan1 = new Loan();
        $loan1->setPrincipal(250000)->setAnnualRate(0.05)->setMonths(360);
        
        $loan2 = new Loan();
        $loan2->setPrincipal(300000)->setAnnualRate(0.06)->setMonths(360);

        $result = $this->service->compareLoans($loan1, $loan2);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('loan1', $result);
        $this->assertArrayHasKey('loan2', $result);
        $this->assertArrayHasKey('better_option', $result);
        $this->assertArrayHasKey('savings', $result);
        $this->assertArrayHasKey('monthly_savings', $result);

        $this->assertContains($result['better_option'], ['loan1', 'loan2']);
        $this->assertGreaterThanOrEqual(0, $result['savings']);
    }

    public function testCompareLoansBothLoansHaveMetrics(): void {
        $loan1 = new Loan();
        $loan1->setPrincipal(200000)->setAnnualRate(0.04)->setMonths(240);
        
        $loan2 = new Loan();
        $loan2->setPrincipal(200000)->setAnnualRate(0.05)->setMonths(360);

        $result = $this->service->compareLoans($loan1, $loan2);

        foreach (['loan1', 'loan2'] as $loanKey) {
            $this->assertArrayHasKey('monthly_payment', $result[$loanKey]);
            $this->assertArrayHasKey('total_interest', $result[$loanKey]);
            $this->assertArrayHasKey('total_cost', $result[$loanKey]);
            $this->assertArrayHasKey('effective_rate', $result[$loanKey]);
            $this->assertGreaterThan(0, $result[$loanKey]['monthly_payment']);
        }
    }

    public function testGenerateLoanQualificationReport(): void {
        $monthlyIncome = 9000;
        $creditScore = 750;
        $propertyValue = 400000;

        $report = $this->service->generateLoanQualificationReport(
            $this->loan,
            $monthlyIncome,
            $creditScore,
            $propertyValue
        );

        $this->assertIsArray($report);
        $this->assertArrayHasKey('loan_amount', $report);
        $this->assertArrayHasKey('interest_rate', $report);
        $this->assertArrayHasKey('monthly_payment', $report);
        $this->assertArrayHasKey('total_term_months', $report);
        $this->assertArrayHasKey('loan_to_value_ratio', $report);
        $this->assertArrayHasKey('debt_to_income_ratio', $report);
        $this->assertArrayHasKey('dti_qualified', $report);
        $this->assertArrayHasKey('creditworthiness_score', $report);
        $this->assertArrayHasKey('risk_level', $report);
        $this->assertArrayHasKey('is_affordable', $report);
        $this->assertArrayHasKey('recommendation', $report);
    }

    public function testGenerateQualificationReportWithoutPropertyValue(): void {
        $report = $this->service->generateLoanQualificationReport(
            $this->loan,
            8000,
            750,
            0 // No property value
        );

        $this->assertArrayNotHasKey('loan_to_value_ratio', $report);
    }

    public function testCalculateMaxLoanAmount(): void {
        $monthlyIncome = 10000;
        $maxDtiRatio = 0.43;
        $interestRate = 0.05;
        $months = 360;

        $maxLoan = $this->service->calculateMaxLoanAmount(
            $monthlyIncome,
            750,
            $maxDtiRatio,
            $interestRate,
            $months
        );

        $this->assertIsFloat($maxLoan);
        $this->assertGreaterThan(0, $maxLoan);
        $this->assertLessThan(10000000, $maxLoan); // Sanity check
    }

    public function testCalculateMaxLoanAmountZeroRate(): void {
        $maxLoan = $this->service->calculateMaxLoanAmount(5000, 750, 0.43, 0, 360);

        $this->assertGreaterThan(0, $maxLoan);
        $this->assertIsFloat($maxLoan);
    }

    public function testExportAnalysisToJSON(): void {
        $json = $this->service->exportAnalysisToJSON($this->loan, 8000, 750);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('recommendation', $decoded);
        $this->assertArrayHasKey('loan_amount', $decoded);
    }
}
