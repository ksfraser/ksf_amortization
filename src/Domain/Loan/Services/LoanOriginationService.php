<?php

namespace App\Domain\Loan\Services;

use App\Domain\Loan\Entities\Loan;
use App\Domain\Loan\Entities\LoanRequest;
use Decimal\Decimal;
use DateTime;

/**
 * Phase 2: Loan Origination Service
 * Orchestrates the complete loan origination workflow:
 * ORIGINATION -> PENDING -> ACTIVE -> (funded)
 */
class LoanOriginationService
{
    public function __construct(
        private AmortizationCalculator $amortizationCalculator,
        private UnderwritingService $underwritingService,
        private LoanRepository $loanRepository,
        private BorrowerRepository $borrowerRepository
    ) {}

    /**
     * Step 1: Initiate new loan application
     */
    public function initiateLoanApplication(
        int $borrowerId,
        LoanRequest $request,
        int $loanOfficerId
    ): Loan {
        // Verify borrower exists
        $borrower = $this->borrowerRepository->findOrFail($borrowerId);

        // Create loan in ORIGINATION stage
        $loan = Loan::initiate($borrowerId, $request, $loanOfficerId);

        // Log events
        $this->publishEvents($loan);

        return $loan;
    }

    /**
     * Step 2: Submit loan for underwriting (ORIGINATION -> PENDING)
     */
    public function submitForUnderwriting(
        Loan $loan,
        Decimal $originationFee = null,
        Decimal $insuranceAmount = null
    ): Loan {
        $loan->submit($originationFee, $insuranceAmount);

        $this->publishEvents($loan);
        $this->loanRepository->save($loan);

        return $loan;
    }

    /**
     * Step 3: Underwrite loan and approve
     * Includes credit decision, pricing, term verification
     */
    public function underwriteAndApprove(
        Loan $loan,
        int $approverUserId,
        UnderwritingDecision $decision
    ): Loan {
        // Run underwriting checks
        $underwritingResult = $this->underwritingService->assessRisk($loan, $decision);

        if (!$underwritingResult->isPassed()) {
            throw new UnderwritingFailedException(
                "Loan failed underwriting: " . $underwritingResult->getReasons()
            );
        }

        // Calculate monthly payment based on pricing
        $monthlyPayment = $this->amortizationCalculator->calculateMonthlyPayment(
            $loan->getOriginalAmount(),
            $decision->getApprovedRate(),
            $loan->getTermMonths()
        );

        // Approve the loan (PENDING -> ACTIVE pricing)
        $loan->approve(
            $approverUserId,
            $monthlyPayment,
            $decision->getCreditScore(),
            $decision->getLtvRatio()
        );

        $this->publishEvents($loan);
        $this->loanRepository->save($loan);

        return $loan;
    }

    /**
     * Step 4: Fund the loan (disburse money)
     * ACTIVE -> funded with bank integration
     */
    public function fundLoan(Loan $loan, DateTime $fundingDate): Loan
    {
        $this->loanRepository->beginTransaction();

        try {
            // Transfer funds (would integrate with ACH/wire)
            $this->transferFunds($loan);

            // Record funding in loan
            $loan->fund($loan->getOriginalAmount(), $fundingDate);

            // Generate amortization schedule for the first time
            $this->generateAmortizationSchedule($loan);

            $this->publishEvents($loan);
            $this->loanRepository->save($loan);
            $this->loanRepository->commit();

        } catch (\Exception $e) {
            $this->loanRepository->rollback();
            throw new LoanFundingException("Loan funding failed: " . $e->getMessage(), 0, $e);
        }

        return $loan;
    }

    /**
     * Reject loan application (from any stage)
     */
    public function rejectLoan(Loan $loan, string $reason): void
    {
        $loan->reject($reason);
        $this->publishEvents($loan);
        $this->loanRepository->save($loan);
    }

    /**
     * Transfer funds (ACH/wire integration point)
     */
    private function transferFunds(Loan $loan): void
    {
        // Integration with payment gateway
        // This would call Stripe, ACH processor, etc.
        // For now, we just verify the loan is ready for funding
        if ($loan->getOriginalAmount()->isLessThanOrEqualTo(Decimal::fromInt(0))) {
            throw new InvalidFundingAmountException(
                "Cannot transfer negative or zero amount"
            );
        }
    }

    /**
     * Generate and save amortization schedule
     */
    private function generateAmortizationSchedule(Loan $loan): void
    {
        $schedule = $this->amortizationCalculator->generateSchedule(
            $loan->getOriginalAmount(),
            $loan->getInterestRate(),
            $loan->getTermMonths(),
            $loan->getFirstPaymentDate()
        );

        $this->loanRepository->saveAmortizationSchedule($loan->getId(), $schedule);
    }

    /**
     * Publish domain events for external systems
     */
    private function publishEvents(Loan $loan): void
    {
        foreach ($loan->getEvents() as $event) {
            // Publish to event bus (RabbitMQ, SNS, etc.)
            // event(new $event($loan));
        }

        $loan->clearEvents();
    }
}

// Supporting value objects and exceptions
class UnderwritingDecision
{
    private string $decisionType; // approved, approved_with_conditions, rejected
    private Decimal $approvedRate;
    private int $creditScore;
    private Decimal $ltvRatio;
    private array $conditions;

    public function __construct(
        string $decisionType,
        Decimal $approvedRate,
        int $creditScore,
        Decimal $ltvRatio,
        array $conditions = []
    ) {
        $this->decisionType = $decisionType;
        $this->approvedRate = $approvedRate;
        $this->creditScore = $creditScore;
        $this->ltvRatio = $ltvRatio;
        $this->conditions = $conditions;
    }

    public function getDecisionType(): string { return $this->decisionType; }
    public function getApprovedRate(): Decimal { return $this->approvedRate; }
    public function getCreditScore(): int { return $this->creditScore; }
    public function getLtvRatio(): Decimal { return $this->ltvRatio; }
    public function getConditions(): array { return $this->conditions; }
}

class UnderwritingFailedException extends \Exception {}
class LoanFundingException extends \Exception {}
class InvalidFundingAmountException extends \Exception {}
