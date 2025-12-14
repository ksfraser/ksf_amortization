<?php

declare(strict_types=1);

namespace Tests\Unit\Compliance;

use Ksfraser\Amortizations\Persistence\Database;
use Ksfraser\Amortizations\Persistence\LoanRepository;
use Ksfraser\Amortizations\Persistence\PaymentScheduleRepository;
use Ksfraser\Amortizations\Persistence\ApplicationRepository;
use Ksfraser\Amortizations\Persistence\AuditLogRepository;
use Ksfraser\Amortizations\Persistence\Schema;
use Ksfraser\Amortizations\Compliance\APRValidator;
use Ksfraser\Amortizations\Compliance\TILACompliance;
use Ksfraser\Amortizations\Compliance\FairLendingValidator;
use Ksfraser\Amortizations\Compliance\RegulatoryReporting;
use PHPUnit\Framework\TestCase;

class ComplianceTest extends TestCase
{
    private Database $db;
    private Schema $schema;
    private LoanRepository $loanRepo;
    private PaymentScheduleRepository $scheduleRepo;
    private ApplicationRepository $appRepo;
    private AuditLogRepository $auditRepo;
    private APRValidator $aprValidator;
    private TILACompliance $tila;
    private FairLendingValidator $fairLending;
    private RegulatoryReporting $reporting;

    protected function setUp(): void
    {
        $this->db = new Database('sqlite::memory:');
        $this->schema = new Schema($this->db);
        
        // Create tables
        $this->schema->createLoansTable();
        $this->schema->createPaymentSchedulesTable();
        $this->schema->createApplicationsTable();
        $this->schema->createAuditLogsTable();
        
        // Initialize repositories
        $this->loanRepo = new LoanRepository($this->db);
        $this->scheduleRepo = new PaymentScheduleRepository($this->db);
        $this->appRepo = new ApplicationRepository($this->db);
        $this->auditRepo = new AuditLogRepository($this->db);
        
        // Initialize compliance services
        $this->aprValidator = new APRValidator($this->db);
        $this->tila = new TILACompliance($this->db);
        $this->fairLending = new FairLendingValidator($this->db);
        $this->reporting = new RegulatoryReporting($this->db);
    }

    // ==================== APR Validator Tests ====================

    public function testCalculateAPR(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'APR001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $apr = $this->aprValidator->calculateAPR($loanId);
        $this->assertEquals(5.0, $apr);
    }

    public function testValidateAPRDisclosure(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'APR002',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $result = $this->aprValidator->validateAPRDisclosure($loanId, 5.001);
        $this->assertTrue($result);
    }

    public function testCalculateFinanceCharge(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'APR003',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $finance = $this->aprValidator->calculateFinanceCharge($loanId);
        $this->assertGreaterThan(0, $finance);
    }

    public function testGetLoanAmountFinanced(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'APR004',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $amount = $this->aprValidator->getLoanAmountFinanced($loanId);
        $this->assertEquals(200000, $amount);
    }

    public function testGetTotalPaymentObligation(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'APR005',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $total = $this->aprValidator->getTotalPaymentObligation($loanId);
        $this->assertGreaterThan(0, $total);
    }

    // ==================== TILA Compliance Tests ====================

    public function testGenerateTILADisclosure(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'TILA001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $disclosure = $this->tila->generateDisclosure($loanId);
        $this->assertNotEmpty($disclosure);
        $this->assertArrayHasKey('amount_financed', $disclosure);
        $this->assertArrayHasKey('annual_percentage_rate', $disclosure);
        $this->assertArrayHasKey('total_of_payments', $disclosure);
    }

    public function testGetFirstPaymentDate(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'TILA002',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $date = $this->tila->getFirstPaymentDate($loanId);
        $this->assertEquals('2024-02-01', $date);
    }

    public function testGetFinalPaymentDate(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'TILA003',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 2,
            'due_date' => '2024-03-01',
            'payment_amount' => 1073.64,
            'principal' => 71.50,
            'interest' => 1002.14,
            'balance' => 199857.94,
            'status' => 'pending',
        ]);

        $date = $this->tila->getFinalPaymentDate($loanId);
        $this->assertEquals('2024-03-01', $date);
    }

    public function testValidateTILACompliance(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'TILA004',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $violations = $this->tila->validateCompliance($loanId);
        $this->assertIsArray($violations);
    }

    // ==================== Fair Lending Tests ====================

    public function testCheckInterestRateDisparity(): void
    {
        // Create loans for two groups
        $this->loanRepo->create([
            'loan_number' => 'FL001',
            'borrower_id' => 2,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->loanRepo->create([
            'loan_number' => 'FL002',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 4.5,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $disparity = $this->fairLending->checkInterestRateDisparity();
        $this->assertIsArray($disparity);
    }

    public function testCheckApprovalRateDisparity(): void
    {
        // Create applications for two groups
        $this->appRepo->create([
            'applicant_id' => 2,
            'loan_amount' => 200000,
            'loan_purpose' => 'Home',
            'status' => 'approved',
        ]);

        $this->appRepo->create([
            'applicant_id' => 1,
            'loan_amount' => 200000,
            'loan_purpose' => 'Home',
            'status' => 'approved',
        ]);

        $disparity = $this->fairLending->checkApprovalRateDisparity();
        $this->assertIsArray($disparity);
    }

    public function testCheckLoanAmountConsistency(): void
    {
        $this->loanRepo->create([
            'loan_number' => 'LAC001',
            'borrower_id' => 2,
            'principal' => 300000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->loanRepo->create([
            'loan_number' => 'LAC002',
            'borrower_id' => 1,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $consistency = $this->fairLending->checkLoanAmountConsistency();
        $this->assertIsArray($consistency);
    }

    // ==================== Regulatory Reporting Tests ====================

    public function testGenerateComplianceReport(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'REG001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $report = $this->reporting->generateComplianceReport('2024-01-01', '2024-12-31');
        $this->assertNotEmpty($report);
        $this->assertArrayHasKey('loan_metrics', $report);
        $this->assertArrayHasKey('payment_metrics', $report);
    }

    public function testGetLoanMetrics(): void
    {
        $this->loanRepo->create([
            'loan_number' => 'METRIC001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-15',
            'status' => 'active',
        ]);

        $metrics = $this->reporting->getLoanMetrics('2024-01-01', '2024-12-31');
        $this->assertArrayHasKey('total_loans_originated', $metrics);
        $this->assertEquals(1, $metrics['total_loans_originated']);
    }

    public function testGetPaymentMetrics(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'PMMETRIC001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $metrics = $this->reporting->getPaymentMetrics('2024-01-01', '2024-12-31');
        $this->assertArrayHasKey('payments_processed', $metrics);
        $this->assertEquals(1, $metrics['payments_processed']);
    }

    public function testCalculateDelinquencyRate(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'DELIN001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $rate = $this->reporting->calculateDelinquencyRate('2024-01-01', '2024-12-31');
        $this->assertGreaterThanOrEqual(0, $rate);
    }

    public function testGetComplianceMetrics(): void
    {
        $metrics = $this->reporting->getComplianceMetrics();
        $this->assertArrayHasKey('total_compliance_events', $metrics);
        $this->assertArrayHasKey('violations_reported', $metrics);
    }

    public function testGetAuditTrail(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'AUDIT001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->auditRepo->log('Loan', $loanId, 'create', ['principal' => 200000], 'admin');

        $trail = $this->reporting->getAuditTrail($loanId);
        $this->assertGreaterThan(0, count($trail));
    }
}
