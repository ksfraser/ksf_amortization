<?php

namespace App\Domain\Loan\Entities;

use Decimal\Decimal;
use DateTime;

/**
 * Phase 2: Loan Entity
 * Domain model representing a lending product through its full lifecycle
 * Immutable, value-object based design with event sourcing
 */
class Loan
{
    private int $loanId;
    private string $loanNumber;
    private int $borrowerId;
    private int $loanOfficerId;

    // Loan terms
    private string $loanType;
    private string $purpose;
    private Decimal $originalAmount;
    private Decimal $currentBalance;
    private Decimal $interestRate;
    private int $termMonths;
    private Decimal $monthlyPayment;

    // Dates
    private ?DateTime $originationDate;
    private ?DateTime $fundingDate;
    private ?DateTime $firstPaymentDate;
    private ?DateTime $nextDueDate;
    private ?DateTime $maturityDate;
    private ?DateTime $lastPaymentDate;

    // Status
    private LoanStage $stage;
    private LoanStatus $status;
    private int $daysPastDue;
    private Decimal $pastDueAmount;

    // Totals
    private Decimal $totalPaid;
    private Decimal $totalInterestPaid;
    private int $paymentCount;
    private Decimal $accruedInterest;
    private ?DateTime $lastInterestAccrualDate;

    // Approval
    private ?DateTime $approvalDate;
    private ?int $approvedBy;
    private ?int $creditScoreAtOrigination;
    private ?Decimal $ltvRatio;

    // Fees
    private Decimal $originationFee;
    private Decimal $insuranceAmount;
    private ?string $lateFeeSchedule;

    private array $events = [];

    /**
     * Create new loan in ORIGINATION stage
     */
    public static function initiate(
        int $borrowerId,
        LoanRequest $request,
        int $loanOfficerId
    ): self {
        $loan = new self();
        $loan->loanNumber = self::generateLoanNumber();
        $loan->borrowerId = $borrowerId;
        $loan->loanOfficerId = $loanOfficerId;
        $loan->loanType = $request->getLoanType();
        $loan->purpose = $request->getPurpose();
        $loan->originalAmount = $request->getAmount();
        $loan->currentBalance = $request->getAmount();
        $loan->interestRate = $request->getInterestRate();
        $loan->termMonths = $request->getTermMonths();
        $loan->monthlyPayment = Decimal::fromInt(0);
        $loan->stage = LoanStage::ORIGINATION;
        $loan->status = LoanStatus::CURRENT;
        $loan->daysPastDue = 0;
        $loan->pastDueAmount = Decimal::fromInt(0);
        $loan->totalPaid = Decimal::fromInt(0);
        $loan->totalInterestPaid = Decimal::fromInt(0);
        $loan->paymentCount = 0;
        $loan->accruedInterest = Decimal::fromInt(0);
        $loan->originationFee = Decimal::fromInt(0);
        $loan->insuranceAmount = Decimal::fromInt(0);

        $loan->recordEvent(new LoanInitiated($loan));
        return $loan;
    }

    /**
     * Submit loan for approval (ORIGINATION -> PENDING)
     */
    public function submit(Decimal $originationFee = null, Decimal $insuranceAmount = null): void
    {
        if ($this->stage !== LoanStage::ORIGINATION) {
            throw new InvalidLoanStateException(
                "Cannot submit loan in {$this->stage} stage"
            );
        }

        if ($originationFee) {
            $this->originationFee = $originationFee;
        }
        if ($insuranceAmount) {
            $this->insuranceAmount = $insuranceAmount;
        }

        $this->stage = LoanStage::PENDING;
        $this->recordEvent(new LoanSubmitted($this));
    }

    /**
     * Approve loan (PENDING -> ACTIVE)
     */
    public function approve(
        int $approverUserId,
        Decimal $calculatedMonthlyPayment,
        ?int $creditScore = null,
        ?Decimal $ltv = null
    ): void {
        if ($this->stage !== LoanStage::PENDING) {
            throw new InvalidLoanStateException(
                "Cannot approve loan not in PENDING stage"
            );
        }

        $this->approvalDate = new DateTime();
        $this->approvedBy = $approverUserId;
        $this->creditScoreAtOrigination = $creditScore;
        $this->ltvRatio = $ltv;
        $this->monthlyPayment = $calculatedMonthlyPayment;
        $this->stage = LoanStage::ACTIVE;

        $this->recordEvent(new LoanApproved($this));
    }

    /**
     * Fund the loan (disburse money)
     */
    public function fund(Decimal $fundAmount, DateTime $fundingDate): void
    {
        if ($this->stage !== LoanStage::ACTIVE) {
            throw new InvalidLoanStateException(
                "Can only fund loans in ACTIVE stage"
            );
        }

        if ($fundAmount !== $this->originalAmount) {
            throw new InvalidFundingAmountException(
                "Funding amount {$fundAmount} does not match original amount {$this->originalAmount}"
            );
        }

        $this->fundingDate = $fundingDate;
        $this->firstPaymentDate = $fundingDate->modify('+1 month');
        $this->nextDueDate = $this->firstPaymentDate;
        $this->maturityDate = $fundingDate->modify("+{$this->termMonths} months");

        $this->recordEvent(new LoanFunded($this, $fundAmount));
    }

    /**
     * Record payment received
     */
    public function recordPayment(
        Payment $payment,
        DateTime $receivedDate
    ): void {
        if ($this->stage !== LoanStage::ACTIVE) {
            throw new InvalidLoanStateException(
                "Can only record payments on ACTIVE loans"
            );
        }

        // Update running totals
        $this->totalPaid = $this->totalPaid->add($payment->getAmount());
        $this->totalInterestPaid = $this->totalInterestPaid->add($payment->getInterestPortion());
        $this->currentBalance = $this->currentBalance->subtract($payment->getPrincipalPortion());
        $this->lastPaymentDate = $receivedDate;
        $this->paymentCount++;

        // Update delinquency status if payment made
        if ($this->daysPastDue > 0) {
            $this->daysPastDue = 0;
            $this->pastDueAmount = Decimal::fromInt(0);
        }

        // Move next due date forward
        $this->nextDueDate = $this->nextDueDate->modify('+1 month');

        // Check if loan is paid off
        if ($this->currentBalance->isLessThanOrEqualTo(Decimal::fromInt(0))) {
            $this->stage = LoanStage::PAID_OFF;
            $this->status = LoanStatus::PAID_OFF;
            $this->recordEvent(new LoanPaidOff($this));
        }

        $this->recordEvent(new PaymentRecorded($payment, $this));
    }

    /**
     * Accrue daily interest
     */
    public function accrueInterest(
        Decimal $dailyInterestAmount,
        DateTime $accrualDate
    ): void {
        if ($this->stage !== LoanStage::ACTIVE) {
            return; // Don't accrue interest on non-active loans
        }

        $this->accruedInterest = $this->accruedInterest->add($dailyInterestAmount);
        $this->lastInterestAccrualDate = $accrualDate;

        $this->recordEvent(new InterestAccrued($this, $dailyInterestAmount));
    }

    /**
     * Update delinquency status
     */
    public function setDelinquency(int $daysPastDue, Decimal $pastDueAmount): void
    {
        if ($daysPastDue === 0) {
            $this->status = LoanStatus::CURRENT;
        } elseif ($daysPastDue >= 1 && $daysPastDue < 30) {
            $this->status = LoanStatus::DELINQUENT_30;
        } elseif ($daysPastDue >= 30 && $daysPastDue < 60) {
            $this->status = LoanStatus::DELINQUENT_60;
        } elseif ($daysPastDue >= 60) {
            $this->status = LoanStatus::DELINQUENT_90;
        }

        $this->daysPastDue = $daysPastDue;
        $this->pastDueAmount = $pastDueAmount;

        $this->recordEvent(new DelinquencyUpdated($this, $daysPastDue));
    }

    /**
     * Charge off the loan (ACTIVE -> CHARGED_OFF)
     */
    public function chargeOff(string $reason): void
    {
        if ($this->stage === LoanStage::CHARGED_OFF) {
            throw new InvalidLoanStateException("Loan already charged off");
        }

        $this->stage = LoanStage::CHARGED_OFF;
        $this->status = LoanStatus::CHARGED_OFF;

        $this->recordEvent(new LoanChargedOff($this, $reason));
    }

    // Getters
    public function getId(): int { return $this->loanId; }
    public function getLoanNumber(): string { return $this->loanNumber; }
    public function getBorrowerId(): int { return $this->borrowerId; }
    public function getLoanType(): string { return $this->loanType; }
    public function getOriginalAmount(): Decimal { return $this->originalAmount; }
    public function getCurrentBalance(): Decimal { return $this->currentBalance; }
    public function getInterestRate(): Decimal { return $this->interestRate; }
    public function getTermMonths(): int { return $this->termMonths; }
    public function getMonthlyPayment(): Decimal { return $this->monthlyPayment; }
    public function getStage(): LoanStage { return $this->stage; }
    public function getStatus(): LoanStatus { return $this->status; }
    public function getMaturityDate(): ?DateTime { return $this->maturityDate; }
    public function getNextDueDate(): ?DateTime { return $this->nextDueDate; }
    public function getDaysPastDue(): int { return $this->daysPastDue; }
    public function getPastDueAmount(): Decimal { return $this->pastDueAmount; }
    public function getTotalPaid(): Decimal { return $this->totalPaid; }
    public function getTotalInterestPaid(): Decimal { return $this->totalInterestPaid; }
    public function getAccruedInterest(): Decimal { return $this->accruedInterest; }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function clearEvents(): void
    {
        $this->events = [];
    }

    // Private helpers
    private static function generateLoanNumber(): string
    {
        return 'LN-' . date('Y') . '-' . str_pad(rand(1000, 9999999), 7, '0', STR_PAD_LEFT);
    }

    private function recordEvent($event): void
    {
        $this->events[] = $event;
    }
}

// Event classes
class LoanInitiated {}
class LoanSubmitted {}
class LoanApproved {}
class LoanFunded {}
class PaymentRecorded {}
class InterestAccrued {}
class DelinquencyUpdated {}
class LoanChargedOff {}
class LoanPaidOff {}

// Value objects
enum LoanStage: string {
    case ORIGINATION = 'ORIGINATION';
    case PENDING = 'PENDING';
    case ACTIVE = 'ACTIVE';
    case PAID_OFF = 'PAID_OFF';
    case CHARGED_OFF = 'CHARGED_OFF';
    case DEFAULTED = 'DEFAULTED';
}

enum LoanStatus: string {
    case CURRENT = 'CURRENT';
    case DELINQUENT_30 = 'DELINQUENT_30';
    case DELINQUENT_60 = 'DELINQUENT_60';
    case DELINQUENT_90 = 'DELINQUENT_90+';
    case PAID_OFF = 'PAID_OFF';
    case CHARGED_OFF = 'CHARGED_OFF';
}

// Exceptions
class InvalidLoanStateException extends \Exception {}
class InvalidFundingAmountException extends \Exception {}
