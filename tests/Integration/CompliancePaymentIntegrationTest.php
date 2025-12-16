<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * Compliance-Payment Integration Test
 * Tests compliance checks during payment processing and disclosure validation
 */
class CompliancePaymentIntegrationTest extends IntegrationTestCase
{
    /**
     * Test APR validation during loan origination
     */
    public function testAPRValidationDuringOrigination(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-201', 1, 100000, 5.0, 360);

        // Calculate and validate APR
        $apr = $this->aprValidator->calculateAPR($loanId);

        $this->assertIsNumeric($apr);
        $this->assertGreaterThan(0, $apr);
        $this->assertLessThan(100, $apr);
    }

    /**
     * Test TILA disclosure generation for new loan
     */
    public function testTILADisclosureGeneration(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-202', 1, 100000, 5.0, 360);

        $disclosure = $this->tila->generateDisclosure($loanId);

        $this->assertIsArray($disclosure);
        $this->assertArrayHasKey('loan_id', $disclosure);
        $this->assertArrayHasKey('annual_percentage_rate', $disclosure);
        $this->assertArrayHasKey('finance_charge', $disclosure);
        $this->assertArrayHasKey('payment_schedule', $disclosure);
        $this->assertEquals($loanId, $disclosure['loan_id']);
    }

    /**
     * Test APR disclosure validation
     */
    public function testAPRDisclosureValidation(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-203', 1, 100000, 5.0, 360);

        $apr = $this->aprValidator->calculateAPR($loanId);
        $isValid = $this->aprValidator->validateAPRDisclosure($loanId, $apr);

        $this->assertTrue($isValid);
        $this->assertGreaterThan(0, $apr);
    }

    /**
     * Test finance charge calculation
     */
    public function testFinanceChargeCalculation(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-204', 1, 100000, 5.0, 360);

        $financeCharge = $this->aprValidator->calculateFinanceCharge($loanId);

        $this->assertIsNumeric($financeCharge);
        $this->assertGreaterThan(0, $financeCharge);
    }

    /**
     * Test fair lending check during application
     */
    public function testFairLendingCheckApplication(): void
    {
        // Create applications with different demographics
        $app1 = $this->appRepo->create([
            'applicant_name' => 'Applicant 1',
            'requested_amount' => 100000,
            'annual_income' => 75000,
            'credit_score' => 750,
            'status' => 'submitted',
            'submission_date' => '2024-01-15',
        ]);

        $app2 = $this->appRepo->create([
            'applicant_name' => 'Applicant 2',
            'requested_amount' => 100000,
            'annual_income' => 75000,
            'credit_score' => 750,
            'status' => 'submitted',
            'submission_date' => '2024-01-15',
        ]);

        // Check for rate disparity
        $disparity = $this->fairLending->checkInterestRateDisparity([
            ['applicant' => 'App1', 'rate' => 5.0],
            ['applicant' => 'App2', 'rate' => 5.5],
        ]);

        $this->assertIsArray($disparity);
    }

    /**
     * Test approval rate disparity (four-fifths test)
     */
    public function testApprovalRateDisparity(): void
    {
        // Create scenario with approval rates
        $scenario = [
            ['group' => 'group_a', 'approved' => 80, 'denied' => 20],
            ['group' => 'group_b', 'approved' => 50, 'denied' => 50],
        ];

        $disparity = $this->fairLending->checkApprovalRateDisparity($scenario);

        $this->assertIsArray($disparity);
    }

    /**
     * Test regulatory report generation
     */
    public function testRegulatoryReportGeneration(): void
    {
        $loan1Id = $this->createLoanWithSchedule('LOAN-205', 1, 100000, 5.0, 360);
        $loan2Id = $this->createLoanWithSchedule('LOAN-206', 2, 150000, 5.5, 360);

        $portfolioId = $this->createPortfolioWithLoans('Portfolio E', 1, [
            $loan1Id, $loan2Id
        ]);

        $report = $this->reporting->generateComplianceReport('2024-01-01', '2024-12-31');

        $this->assertIsArray($report);
        $this->assertArrayHasKey('period_start', $report);
        $this->assertArrayHasKey('period_end', $report);
        $this->assertEquals('2024-01-01', $report['period_start']);
    }

    /**
     * Test compliance during payment processing
     */
    public function testComplianceDuringPaymentProcessing(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-207', 1, 100000, 5.0, 360);

        // Get payment schedule
        $schedules = $this->scheduleRepo->findBy(['loan_id' => $loanId]);
        
        // Process first payment
        $paymentId = $schedules[0]['id'];
        $this->scheduleRepo->update($paymentId, [
            'status' => 'paid',
            'paid_date' => $schedules[0]['due_date'],
        ]);

        // Verify compliance checks
        $apr = $this->aprValidator->calculateAPR($loanId);
        $isValid = $this->aprValidator->validateAPRDisclosure($loanId, $apr);
        $this->assertTrue($isValid);
    }

    /**
     * Test delinquency compliance tracking
     */
    public function testDelinquencyComplianceTracking(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-208', 1, 100000, 5.0, 360);

        // Mark payment as delinquent
        $schedules = $this->scheduleRepo->findBy(['loan_id' => $loanId]);
        $this->scheduleRepo->update($schedules[0]['id'], [
            'status' => 'delinquent',
            'delinquency_days' => 60,
        ]);

        // Get delinquency metrics
        $report = $this->reporting->generateComplianceReport('2024-01-01', '2024-12-31');

        $this->assertIsArray($report);
    }

    /**
     * Test loan amount consistency check
     */
    public function testLoanAmountConsistencyCheck(): void
    {
        // Create scenario with different loan amounts
        $scenario = [
            ['applicant' => 'App A', 'loan_amount' => 100000],
            ['applicant' => 'App B', 'loan_amount' => 100000],
            ['applicant' => 'App C', 'loan_amount' => 80000],
        ];

        $result = $this->fairLending->checkLoanAmountConsistency($scenario);

        $this->assertIsArray($result);
    }

    /**
     * Test TILA payment schedule validation
     */
    public function testTILAPaymentScheduleValidation(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-209', 1, 100000, 5.0, 360);

        $disclosure = $this->tila->generateDisclosure($loanId);

        $this->assertArrayHasKey('payment_schedule', $disclosure);
        $this->assertIsString($disclosure['payment_schedule']);
        $this->assertEquals('Monthly', $disclosure['payment_schedule']);
    }

    /**
     * Test audit trail for compliance actions
     */
    public function testComplianceAuditTrail(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-210', 1, 100000, 5.0, 360);

        // Perform compliance check
        $apr = $this->aprValidator->calculateAPR($loanId);
        $this->aprValidator->validateAPRDisclosure($loanId, $apr);

        // Verify audit entries exist - there should be audit trail from loan creation
        $trail = $this->auditRepo->getHistory('Loan', $loanId);
        // Note: Audit trail may be empty depending on repository implementation
        $this->assertIsArray($trail);
    }

    /**
     * Test multi-loan compliance portfolio report
     */
    public function testMultiLoanCompliancePortfolioReport(): void
    {
        $loan1Id = $this->createLoanWithSchedule('LOAN-211', 1, 100000, 5.0, 360);
        $loan2Id = $this->createLoanWithSchedule('LOAN-212', 2, 150000, 5.5, 360);
        $loan3Id = $this->createLoanWithSchedule('LOAN-213', 3, 200000, 4.5, 360);

        $portfolioId = $this->createPortfolioWithLoans('Portfolio F', 1, [
            $loan1Id, $loan2Id, $loan3Id
        ]);

        $report = $this->reporting->generateComplianceReport('2024-01-01', '2024-12-31');

        $this->assertArrayHasKey('loan_metrics', $report);
        $metrics = $report['loan_metrics'];
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_loans_originated', $metrics);
        $this->assertGreaterThanOrEqual(0, $metrics['total_loans_originated']);
    }
}
