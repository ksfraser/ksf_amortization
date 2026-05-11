<?php

namespace App\Domain\Loan\Services;

use App\Domain\Loan\Entities\Loan;
use Decimal\Decimal;

/**
 * Phase 2: Payment Processing Service
 * Handles payment receipt, posting, and distribution (principal/interest/fees)
 */
class PaymentProcessingService
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private LoanRepository $loanRepository,
        private InterestAccrualService $interestAccrualService,
        private StructuredLogger $logger
    ) {}

    /**
     * Process payment through gateway
     * Typical flow: ACH, credit card, wire
     */
    public function processPayment(
        Loan $loan,
        Decimal $amount,
        string $method, // 'ach', 'card', 'wire'
        ?string $referenceNumber = null
    ): PaymentProcessingResult {
        // Step 1: Validate payment
        $validationResult = $this->validatePayment($loan, $amount);
        if (!$validationResult['valid']) {
            return PaymentProcessingResult::failed($validationResult['reason']);
        }

        // Step 2: Create Payment entity in PENDING status
        $payment = Payment::create(
            $loan,
            $amount,
            $method,
            $referenceNumber
        );

        // Step 3: Process through payment gateway
        try {
            $gatewayResult = $this->processViaGateway($payment, $method);

            if (!$gatewayResult['success']) {
                $this->logger->logBusinessEvent(
                    'payment_failed',
                    'Payment',
                    $payment->getId(),
                    ['reason' => $gatewayResult['error']]
                );

                $payment->markFailed($gatewayResult['error']);
                $this->paymentRepository->save($payment);

                return PaymentProcessingResult::failed($gatewayResult['error']);
            }

            // Step 4: Mark as posted
            $payment->markPosted($gatewayResult['transaction_id']);
            $this->paymentRepository->save($payment);

            $this->logger->logBusinessEvent(
                'payment_processed',
                'Payment',
                $payment->getId(),
                ['amount' => (string) $amount, 'method' => $method]
            );

            return PaymentProcessingResult::success($payment);

        } catch (\Exception $e) {
            $this->logger->logError($e, 'payment_processing', ['loan_id' => $loan->getId()]);
            return PaymentProcessingResult::failed("Payment processing error: " . $e->getMessage());
        }
    }

    /**
     * Post payment to loan account
     * Distribute payment between interest, principal, fees
     */
    public function postPayment(Payment $payment): void
    {
        $loan = $this->loanRepository->findOrFail($payment->getLoanId());

        $this->loanRepository->beginTransaction();

        try {
            // Calculate interest accrued since last payment
            $interestAccrued = $this->interestAccrualService->accrueInterestSincePayment(
                $loan->getCurrentBalance(),
                $loan->getInterestRate(),
                $loan->getLastPaymentDate() ?? $loan->getFundingDate(),
                new \DateTime()
            );

            // Distribute payment
            $distribution = $this->distributePayment(
                $loan,
                $payment,
                $interestAccrued
            );

            // Update payment record
            $payment->setDistribution(
                $distribution['interest'],
                $distribution['principal'],
                $distribution['fees']
            );

            // Update loan
            $loan->recordPayment($payment, new \DateTime());

            // Save changes
            $this->paymentRepository->save($payment);
            $this->loanRepository->save($loan);

            // Log the posting
            $this->logger->logBusinessEvent(
                'payment_posted',
                'Loan',
                $loan->getId(),
                [
                    'payment_id' => $payment->getId(),
                    'principal' => (string) $distribution['principal'],
                    'interest' => (string) $distribution['interest'],
                    'new_balance' => (string) $loan->getCurrentBalance(),
                ]
            );

            $this->loanRepository->commit();

        } catch (\Exception $e) {
            $this->loanRepository->rollback();
            throw new PaymentPostingException("Failed to post payment: " . $e->getMessage());
        }
    }

    /**
     * Distribute payment to principal, interest, fees
     * Priority: Fees -> Interest -> Principal
     */
    private function distributePayment(
        Loan $loan,
        Payment $payment,
        Decimal $accruedInterest
    ): array {
        $amount = $payment->getAmount();
        $distribution = [
            'fees' => new Decimal('0'),
            'interest' => new Decimal('0'),
            'principal' => new Decimal('0'),
        ];

        // Fees first (if any)
        $feeDue = $this->calculateFeeDue($loan, $payment);
        if ($feeDue->isGreaterThan(new Decimal('0'))) {
            $distribution['fees'] = min($amount, $feeDue);
            $amount = $amount->subtract($distribution['fees']);
        }

        // Interest second
        if ($amount->isGreaterThan(new Decimal('0')) && $accruedInterest->isGreaterThan(new Decimal('0'))) {
            $distribution['interest'] = min($amount, $accruedInterest);
            $amount = $amount->subtract($distribution['interest']);
        }

        // Principal last (any remaining)
        if ($amount->isGreaterThan(new Decimal('0'))) {
            $distribution['principal'] = $amount;
        }

        return [
            'fees' => $distribution['fees'],
            'interest' => $distribution['interest'],
            'principal' => $distribution['principal'],
        ];
    }

    /**
     * Validate payment is acceptable
     */
    private function validatePayment(Loan $loan, Decimal $amount): array
    {
        // Check loan is active
        if ($loan->getStage()->value !== 'ACTIVE') {
            return ['valid' => false, 'reason' => 'Loan is not active'];
        }

        // Check amount is positive
        if ($amount->isLessThanOrEqualTo(new Decimal('0'))) {
            return ['valid' => false, 'reason' => 'Payment amount must be positive'];
        }

        // Check amount doesn't exceed balance + accrued interest
        $maxPayment = $loan->getCurrentBalance()->add(
            $loan->getAccruedInterest()
        );

        if ($amount->isGreaterThan($maxPayment)) {
            return ['valid' => false, 'reason' => 'Payment exceeds balance'];
        }

        return ['valid' => true];
    }

    /**
     * Process payment through gateway (ACH, card, etc.)
     */
    private function processViaGateway(Payment $payment, string $method): array
    {
        // In production, would integrate with:
        // - Stripe API for cards
        // - ACH processor for bank transfers
        // - Wire processor for wire transfers

        // Mock implementation
        return [
            'success' => true,
            'transaction_id' => 'TXN-' . uniqid(),
        ];
    }

    /**
     * Calculate any fees due (late fees, etc.)
     */
    private function calculateFeeDue(Loan $loan, Payment $payment): Decimal
    {
        // Example: 5% late fee if more than 30 days past due
        if ($loan->getDaysPastDue() > 30) {
            return $loan->getPastDueAmount()->multiply(new Decimal('0.05'));
        }

        return new Decimal('0');
    }
}

// Supporting classes
class Payment
{
    private int $id;
    private int $loanId;
    private Decimal $amount;
    private string $method;
    private ?string $referenceNumber;
    private string $status; // pending, posted, failed
    private Decimal $principalPortion;
    private Decimal $interestPortion;
    private Decimal $feePortion;

    public static function create(
        Loan $loan,
        Decimal $amount,
        string $method,
        ?string $referenceNumber = null
    ): self {
        $payment = new self();
        $payment->loanId = $loan->getId();
        $payment->amount = $amount;
        $payment->method = $method;
        $payment->referenceNumber = $referenceNumber;
        $payment->status = 'pending';
        $payment->principalPortion = new Decimal('0');
        $payment->interestPortion = new Decimal('0');
        $payment->feePortion = new Decimal('0');

        return $payment;
    }

    public function markPosted(string $transactionId): void
    {
        $this->status = 'posted';
    }

    public function markFailed(string $reason): void
    {
        $this->status = 'failed';
    }

    public function setDistribution(
        Decimal $interest,
        Decimal $principal,
        Decimal $fees = null
    ): void {
        $this->interestPortion = $interest;
        $this->principalPortion = $principal;
        $this->feePortion = $fees ?? new Decimal('0');
    }

    public function getId(): int { return $this->id; }
    public function getLoanId(): int { return $this->loanId; }
    public function getAmount(): Decimal { return $this->amount; }
    public function getMethod(): string { return $this->method; }
    public function getStatus(): string { return $this->status; }
    public function getPrincipalPortion(): Decimal { return $this->principalPortion; }
    public function getInterestPortion(): Decimal { return $this->interestPortion; }
}

class PaymentProcessingResult
{
    private bool $success;
    private ?Payment $payment;
    private ?string $error;

    private function __construct(bool $success, ?Payment $payment = null, ?string $error = null)
    {
        $this->success = $success;
        $this->payment = $payment;
        $this->error = $error;
    }

    public static function success(Payment $payment): self
    {
        return new self(true, $payment);
    }

    public static function failed(string $error): self
    {
        return new self(false, null, $error);
    }

    public function isSuccessful(): bool { return $this->success; }
    public function getPayment(): ?Payment { return $this->payment; }
    public function getError(): ?string { return $this->error; }
}

class PaymentPostingException extends \Exception {}
