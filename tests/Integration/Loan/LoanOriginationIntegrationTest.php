<?php

namespace Tests\Integration\Loan;

use App\Domain\Loan\Services\LoanOriginationService;
use App\Domain\Loan\Services\AmortizationCalculator;
use App\Domain\Loan\Services\PaymentProcessingService;
use Decimal\Decimal;
use Tests\TestCase;

/**
 * Phase 2: Loan Origination Integration Tests
 * Tests complete loan lifecycle: origination -> approval -> funding
 */
class LoanOriginationIntegrationTest extends TestCase
{
    private LoanOriginationService $originationService;
    private AmortizationCalculator $amortizationCalculator;
    private PaymentProcessingService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->originationService = app(LoanOriginationService::class);
        $this->amortizationCalculator = app(AmortizationCalculator::class);
        $this->paymentService = app(PaymentProcessingService::class);
    }

    /**
     * @test
     * Complete loan origination workflow
     */
    public function it_completes_full_loan_origination_workflow(): void
    {
        // Step 1: Create test borrower
        $borrower = $this->createTestBorrower([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'annual_income' => 75000,
        ]);

        $loanOfficer = $this->createTestUser('officer@test.com', 'loan_officer');
        $approver = $this->createTestUser('approver@test.com', 'admin');

        // Step 2: Initiate loan application
        $request = new LoanRequest(
            'personal',
            'Debt consolidation',
            new Decimal('50000'),
            36,
            new Decimal('7.75')
        );

        $loan = $this->originationService->initiateLoanApplication(
            $borrower->id,
            $request,
            $loanOfficer->id
        );

        $this->assertEquals('ORIGINATION', $loan->getStage()->value);
        $this->assertNotEmpty($loan->getLoanNumber());

        // Step 3: Submit for underwriting
        $loan = $this->originationService->submitForUnderwriting(
            $loan,
            new Decimal('500'), // Origination fee
            new Decimal('0')
        );

        $this->assertEquals('PENDING', $loan->getStage()->value);

        // Step 4: Approve loan
        $decision = new UnderwritingDecision(
            'approved',
            new Decimal('7.75'),
            750,
            new Decimal('80')
        );

        $loan = $this->originationService->underwriteAndApprove(
            $loan,
            $approver->id,
            $decision
        );

        $this->assertEquals('ACTIVE', $loan->getStage()->value);
        $this->assertEquals('1510.00', (string) $loan->getMonthlyPayment());

        // Step 5: Fund the loan
        $loan = $this->originationService->fundLoan($loan, now());

        $this->assertNotNull($loan->getFundingDate());
        $this->assertNotNull($loan->getFirstPaymentDate());
        $this->assertNotNull($loan->getMaturityDate());

        // Verify in database
        $this->assertDatabaseHas('loans', [
            'loan_id' => $loan->getId(),
            'loan_number' => $loan->getLoanNumber(),
            'stage' => 'ACTIVE',
        ]);
    }

    /**
     * @test
     * Amortization schedule is generated correctly after funding
     */
    public function it_generates_correct_amortization_schedule(): void
    {
        $borrower = $this->createTestBorrower();
        $loanOfficer = $this->createTestUser('officer@test.com', 'loan_officer');

        $loan = $this->originationService->initiateLoanApplication(
            $borrower->id,
            new LoanRequest('personal', 'Test', new Decimal('50000'), 36, new Decimal('7.75')),
            $loanOfficer->id
        );

        // Verify schedule generation
        $schedule = $this->amortizationCalculator->generateSchedule(
            $loan->getOriginalAmount(),
            $loan->getInterestRate(),
            $loan->getTermMonths(),
            now()->addMonth()
        );

        $this->assertCount(36, $schedule);

        // First payment
        $this->assertEquals($schedule[0]['period'], 1);
        $this->assertEquals('1510.00', $schedule[0]['payment_amount']);

        // Last payment should close balance
        $this->assertLessThan('0.01', $schedule[35]['balance']);

        // Validate total principal equals original amount
        $totalPrincipal = new Decimal('0');
        foreach ($schedule as $period) {
            $totalPrincipal = $totalPrincipal->add(new Decimal($period['principal']));
        }

        $diff = $loan->getOriginalAmount()->subtract($totalPrincipal)->abs();
        $this->assertLessThan('0.01', (string) $diff);
    }

    /**
     * @test
     * Payment processing with proper distribution
     */
    public function it_processes_payment_and_distributes_correctly(): void
    {
        // Setup: Create and fund a loan
        $borrower = $this->createTestBorrower();
        $loanOfficer = $this->createTestUser('officer@test.com', 'loan_officer');

        $loan = $this->originationService->initiateLoanApplication(
            $borrower->id,
            new LoanRequest('personal', 'Test', new Decimal('50000'), 36, new Decimal('7.75')),
            $loanOfficer->id
        );

        $loan = $this->originationService->submitForUnderwriting($loan);

        $decision = new UnderwritingDecision('approved', new Decimal('7.75'), 750, new Decimal('80'));
        $loan = $this->originationService->underwriteAndApprove($loan, 1, $decision);

        $loan = $this->originationService->fundLoan($loan, now());

        $initialBalance = $loan->getCurrentBalance();

        // Process a payment
        $paymentResult = $this->paymentService->processPayment(
            $loan,
            $loan->getMonthlyPayment(),
            'ach'
        );

        $this->assertTrue($paymentResult->isSuccessful());

        // Post the payment
        $this->paymentService->postPayment($paymentResult->getPayment());

        // Verify balance was reduced
        $payment = $paymentResult->getPayment();
        $this->assertGreaterThan(new Decimal('0'), $payment->getPrincipalPortion());
        $this->assertGreaterThan(new Decimal('0'), $payment->getInterestPortion());
    }

    /**
     * @test
     * Delinquency detection works correctly
     */
    public function it_detects_delinquency_correctly(): void
    {
        $borrower = $this->createTestBorrower();
        $loanOfficer = $this->createTestUser('officer@test.com', 'loan_officer');

        $loan = $this->originationService->initiateLoanApplication(
            $borrower->id,
            new LoanRequest('personal', 'Test', new Decimal('50000'), 36, new Decimal('7.75')),
            $loanOfficer->id
        );

        $loan = $this->originationService->submitForUnderwriting($loan);

        $decision = new UnderwritingDecision('approved', new Decimal('7.75'), 750, new Decimal('80'));
        $loan = $this->originationService->underwriteAndApprove($loan, 1, $decision);

        $loan = $this->originationService->fundLoan($loan, now());

        // Simulate 35 days past due
        $loan->setDelinquency(35, $loan->getMonthlyPayment());

        $this->assertEquals('DELINQUENT_30', $loan->getStatus()->value);
        $this->assertEquals(35, $loan->getDaysPastDue());
    }
}
