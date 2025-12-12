<?php
namespace Tests\Integration;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\AdvancedReportingService;
use Ksfraser\Amortizations\Services\LoanOriginationService;
use PHPUnit\Framework\TestCase;

class ReportingOriginationIntegrationTest extends TestCase {
    private AdvancedReportingService $reportingService;
    private LoanOriginationService $originationService;
    private Loan $testLoan;
    private array $testSchedule;

    protected function setUp(): void {
        $this->reportingService = new AdvancedReportingService();
        $this->originationService = new LoanOriginationService();
        
        $this->testLoan = new Loan();
        $this->testLoan->setId(1)->setPrincipal(250000)->setAnnualRate(0.05)->setMonths(360);
        
        $this->testSchedule = $this->createTestSchedule();
    }

    private function createTestSchedule(): array {
        $schedule = [];
        for ($i = 0; $i < 360; $i++) {
            $schedule[$i] = [
                'payment' => 1340.73,
                'principal' => 500,
                'interest' => 400 - ($i / 10),
                'balance' => 250000 - ($i * 500)
            ];
        }
        return $schedule;
    }

    public function testOfferLetterIncludesReportData(): void {
        $report = $this->reportingService->generateFinancialSummary($this->testLoan, $this->testSchedule);
        $offer = $this->originationService->generateOfferLetter($this->testLoan, $report['average_payment'], 'John Doe');

        $this->assertStringContainsString('LOAN OFFER LETTER', $offer);
        $this->assertStringContainsString('250,000', $offer);
        $this->assertArrayHasKey('total_interest', $report);
    }

    public function testApplicationSummaryWithReporting(): void {
        $application = [
            'application_id' => 'app_001',
            'applicant_name' => 'Jane Smith',
            'requested_amount' => 250000,
            'status' => 'submitted'
        ];

        $report = $this->reportingService->generateFinancialSummary($this->testLoan, $this->testSchedule);
        $summary = $this->originationService->exportApplicationSummary($application, $this->testLoan);

        $this->assertArrayHasKey('applicant_name', $summary);
        $this->assertArrayHasKey('total_interest', $report);
        $this->assertEquals('Jane Smith', $summary['applicant_name']);
    }

    public function testAmortizationChartInApproval(): void {
        $chart = $this->reportingService->generateAmortizationChart($this->testLoan, $this->testSchedule);
        $approval = $this->originationService->approveLoan('app_001', $this->testLoan);

        $this->assertStringContainsString('<table', $chart);
        $this->assertEquals('approved', $approval['status']);
    }

    public function testDisclosuresIncludeReportData(): void {
        $disclosures = $this->originationService->generateDisclosures($this->testLoan, 1340.73);
        $html = $this->reportingService->generateHTML($this->testLoan, $this->testSchedule);

        $this->assertArrayHasKey('truth_in_lending', $disclosures);
        $this->assertStringContainsString('Loan Amortization Report', $html);
    }

    public function testComplianceReportGeneration(): void {
        $compliance = $this->originationService->checkCompliance($this->testLoan);
        $report = $this->reportingService->generateFinancialSummary($this->testLoan, $this->testSchedule);

        $this->assertEquals('compliant', $compliance['status']);
        $this->assertGreaterThan(0, $report['total_cost']);
    }

    public function testApplicationWithExportedReport(): void {
        $app = $this->originationService->createLoanApplication([
            'applicant_name' => 'Bob Johnson',
            'requested_amount' => 250000,
            'purpose' => 'Home Purchase'
        ]);

        $csv = $this->reportingService->exportToCSV($this->testSchedule);
        $summary = $this->originationService->exportApplicationSummary($app, $this->testLoan);

        $this->assertArrayHasKey('application_id', $app);
        $this->assertStringContainsString(',', $csv);
        $this->assertArrayHasKey('approved_amount', $summary);
    }

    public function testMonthlyAnalysisForOriginationPlanning(): void {
        $analysis = $this->reportingService->generateMonthlyAnalysis($this->testSchedule);
        $maxBorrow = $this->originationService->calculateMaxBorrow(8000, 0.43, 0.05, 360);

        $this->assertArrayHasKey('monthly_average_payment', $analysis);
        $this->assertGreaterThan(0, $maxBorrow);
    }

    public function testOfferLetterWithProfitabilityData(): void {
        $monthly = $this->reportingService->generateMonthlyAnalysis($this->testSchedule);
        $offer = $this->originationService->generateOfferLetter(
            $this->testLoan,
            $monthly['monthly_average_payment'],
            'Alice Williams'
        );

        $this->assertStringContainsString('Alice Williams', $offer);
        $this->assertStringContainsString('360 months', $offer);
    }

    public function testApplicationProgressWithReporting(): void {
        $progress = $this->originationService->trackApplicationProgress('app_001');
        $paymentSummary = $this->reportingService->summarizePaymentHistory($this->testSchedule);

        $this->assertArrayHasKey('current_stage', $progress);
        $this->assertArrayHasKey('total_payments_scheduled', $paymentSummary);
    }

    public function testComparisonReportForMultipleOffers(): void {
        $schedule1 = $this->testSchedule;
        $schedule2 = array_slice($this->testSchedule, 0, 240);

        $comparison = $this->reportingService->generateComparisonReport($schedule1, $schedule2);
        $offer = $this->originationService->generateOfferLetter($this->testLoan, 1340.73, 'Charlie Brown');

        $this->assertArrayHasKey('interest_savings', $comparison);
        $this->assertStringContainsString('Charlie Brown', $offer);
    }

    public function testCompleteOriginationWorkflow(): void {
        // 1. Create application
        $app = $this->originationService->createLoanApplication([
            'applicant_name' => 'Diana Prince',
            'requested_amount' => 250000,
            'purpose' => 'Home Refinance'
        ]);

        // 2. Validate application
        $validation = $this->originationService->validateLoanApplication($app);

        // 3. Generate reports
        $report = $this->reportingService->generateFinancialSummary($this->testLoan, $this->testSchedule);
        $chart = $this->reportingService->generateAmortizationChart($this->testLoan, $this->testSchedule);

        // 4. Generate offer letter
        $offer = $this->originationService->generateOfferLetter($this->testLoan, $report['average_payment'], $app['applicant_name']);

        // 5. Generate disclosures
        $disclosures = $this->originationService->generateDisclosures($this->testLoan, $report['average_payment']);

        // Verify integration
        $this->assertTrue($validation['is_valid']);
        $this->assertStringContainsString('Diana Prince', $offer);
        $this->assertStringContainsString('<table', $chart);
        $this->assertGreaterThan(0, count($disclosures));
    }

    public function testApplicationDocumentationWithReports(): void {
        $docs = ['pay_stub.pdf', 'amortization_schedule.pdf', 'tax_return.pdf'];
        $documented = $this->originationService->documentApplication('app_001', $docs);

        $json = $this->reportingService->exportToJSON($this->testLoan, $this->testSchedule);

        $this->assertEquals(3, $documented['documents_received']);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }

    public function testInterestAccrualInApprovalProcess(): void {
        $accrual = $this->reportingService->calculateInterestAccrual($this->testSchedule);
        $approval = $this->originationService->approveLoan('app_001', $this->testLoan);

        $this->assertGreaterThan(0, count($accrual));
        $this->assertEquals('approved', $approval['status']);
    }

    public function testReportExportFormatsForApplication(): void {
        $csv = $this->reportingService->exportToCSV($this->testSchedule);
        $json = $this->reportingService->exportToJSON($this->testLoan, $this->testSchedule);
        $xml = $this->reportingService->exportToXML($this->testLoan, $this->testSchedule);

        $app = $this->originationService->createLoanApplication([
            'applicant_name' => 'Eve Anderson',
            'requested_amount' => 250000,
            'purpose' => 'Debt Consolidation'
        ]);

        $this->assertStringContainsString('Period', $csv);
        $this->assertStringContainsString('schedule', $json);
        $this->assertStringContainsString('xml', $xml);
        $this->assertArrayHasKey('application_id', $app);
    }
}
