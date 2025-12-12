<?php
namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\LoanOriginationService;
use PHPUnit\Framework\TestCase;

class LoanOriginationServiceTest extends TestCase {
    private LoanOriginationService $service;
    private Loan $loan;

    protected function setUp(): void {
        $this->service = new LoanOriginationService();
        $this->loan = new Loan();
        $this->loan->setId(1)->setPrincipal(250000)->setAnnualRate(0.05)->setMonths(360);
    }

    public function testCreateLoanApplication(): void {
        $appData = [
            'applicant_name' => 'John Doe',
            'requested_amount' => 250000,
            'purpose' => 'Home Purchase'
        ];

        $application = $this->service->createLoanApplication($appData);

        $this->assertIsArray($application);
        $this->assertArrayHasKey('application_id', $application);
        $this->assertArrayHasKey('status', $application);
        $this->assertEquals('submitted', $application['status']);
    }

    public function testValidateLoanApplicationValid(): void {
        $application = [
            'applicant_name' => 'John Doe',
            'requested_amount' => 250000,
            'purpose' => 'Home'
        ];

        $result = $this->service->validateLoanApplication($application);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertTrue($result['is_valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateLoanApplicationInvalid(): void {
        $application = [
            'applicant_name' => '',
            'requested_amount' => 0
        ];

        $result = $this->service->validateLoanApplication($application);

        $this->assertFalse($result['is_valid']);
        $this->assertGreaterThan(0, count($result['errors']));
    }

    public function testGenerateDisclosures(): void {
        $disclosures = $this->service->generateDisclosures($this->loan, 1340.73);

        $this->assertIsArray($disclosures);
        $this->assertArrayHasKey('truth_in_lending', $disclosures);
        $this->assertArrayHasKey('privacy_notice', $disclosures);
        $this->assertArrayHasKey('fair_lending', $disclosures);
    }

    public function testCheckCompliance(): void {
        $compliance = $this->service->checkCompliance($this->loan);

        $this->assertIsArray($compliance);
        $this->assertArrayHasKey('status', $compliance);
        $this->assertArrayHasKey('checks', $compliance);
        $this->assertEquals('compliant', $compliance['status']);
    }

    public function testCalculateMaxBorrow(): void {
        $maxBorrow = $this->service->calculateMaxBorrow(8000, 0.43, 0.05, 360);

        $this->assertIsFloat($maxBorrow);
        $this->assertGreaterThan(0, $maxBorrow);
        $this->assertLessThan(10000000, $maxBorrow);
    }

    public function testAssignLoanOfficer(): void {
        $assignment = $this->service->assignLoanOfficer('app_123', 'officer_001');

        $this->assertIsArray($assignment);
        $this->assertArrayHasKey('application_id', $assignment);
        $this->assertArrayHasKey('assigned_officer', $assignment);
        $this->assertEquals('assigned', $assignment['status']);
    }

    public function testGenerateOfferLetter(): void {
        $letter = $this->service->generateOfferLetter($this->loan, 1340.73, 'John Doe');

        $this->assertIsString($letter);
        $this->assertStringContainsString('LOAN OFFER LETTER', $letter);
        $this->assertStringContainsString('John Doe', $letter);
        $this->assertStringContainsString('250,000', $letter);
    }

    public function testUpdateApplicationStatus(): void {
        $update = $this->service->updateApplicationStatus('app_123', 'under_review');

        $this->assertIsArray($update);
        $this->assertArrayHasKey('new_status', $update);
        $this->assertEquals('under_review', $update['new_status']);
    }

    public function testApproveLoan(): void {
        $approval = $this->service->approveLoan('app_123', $this->loan);

        $this->assertIsArray($approval);
        $this->assertArrayHasKey('status', $approval);
        $this->assertEquals('approved', $approval['status']);
        $this->assertArrayHasKey('approved_amount', $approval);
    }

    public function testRejectLoan(): void {
        $rejection = $this->service->rejectLoan('app_123', 'Insufficient income');

        $this->assertIsArray($rejection);
        $this->assertEquals('rejected', $rejection['status']);
        $this->assertArrayHasKey('reason', $rejection);
    }

    public function testRequestMoreInfo(): void {
        $request = $this->service->requestMoreInfo('app_123', ['pay_stubs', 'tax_returns']);

        $this->assertIsArray($request);
        $this->assertEquals('pending_documentation', $request['status']);
        $this->assertArrayHasKey('required_documents', $request);
    }

    public function testDocumentApplication(): void {
        $docs = ['pay_stub.pdf', 'tax_return.pdf', 'id.pdf'];
        $result = $this->service->documentApplication('app_123', $docs);

        $this->assertIsArray($result);
        $this->assertEquals(3, $result['documents_received']);
    }

    public function testTrackApplicationProgress(): void {
        $progress = $this->service->trackApplicationProgress('app_123');

        $this->assertIsArray($progress);
        $this->assertArrayHasKey('current_stage', $progress);
        $this->assertArrayHasKey('progress_percentage', $progress);
        $this->assertArrayHasKey('next_steps', $progress);
    }

    public function testExportApplicationSummary(): void {
        $application = [
            'application_id' => 'app_123',
            'applicant_name' => 'John Doe',
            'requested_amount' => 250000,
            'status' => 'approved'
        ];

        $summary = $this->service->exportApplicationSummary($application, $this->loan);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('applicant_name', $summary);
        $this->assertArrayHasKey('approved_amount', $summary);
        $this->assertEquals('John Doe', $summary['applicant_name']);
    }
}
