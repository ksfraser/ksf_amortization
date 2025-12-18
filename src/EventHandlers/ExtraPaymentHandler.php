<?php

namespace Ksfraser\Amortizations\EventHandlers;

use Ksfraser\Amortizations\Repositories\LoanRepository;
use Ksfraser\Amortizations\Repositories\ScheduleRepository;

/**
 * Extra Payment Handler
 * 
 * Handles extra payment events on loans.
 * Processes extra payment requests by:
 * - Validating the extra payment event
 * - Reducing loan balance
 * - Calculating interest savings
 * - Shortening loan term if applicable
 * - Recalculating amortization schedule
 * 
 * @author KSF
 * @version 1.0.0
 */
class ExtraPaymentHandler
{
    private LoanRepository $loanRepository;
    private ScheduleRepository $scheduleRepository;

    public function __construct(
        LoanRepository $loanRepository,
        ScheduleRepository $scheduleRepository
    ) {
        $this->loanRepository = $loanRepository;
        $this->scheduleRepository = $scheduleRepository;
    }

    /**
     * Handle extra payment event
     * 
     * @param array $event Extra payment event data
     * @return array Processing result
     * @throws \InvalidArgumentException If validation fails
     */
    public function handle(array $event): array
    {
        // Validate event
        $this->validateEvent($event);

        $loanId = $event['loan_id'];
        $paymentAmount = $event['amount'];
        $paymentDate = $event['date'] ?? date('Y-m-d');

        // Get loan data
        $loan = $this->loanRepository->findById($loanId);
        if (!$loan) {
            throw new \InvalidArgumentException("Loan not found: {$loanId}");
        }

        // Get current loan data
        $currentBalance = $loan['current_balance'] ?? $loan['principal'] ?? 0;
        $currentTerm = $loan['months'] ?? 60;
        $currentMonth = $loan['current_month'] ?? 1;
        $annualRate = $loan['annual_rate'] ?? $loan['rate'] ?? 0;
        $monthlyRate = $annualRate / 12;
        $monthlyPayment = $loan['monthly_payment'] ?? 531.86;

        // Validate payment doesn't exceed balance
        if ($paymentAmount > $currentBalance) {
            throw new \InvalidArgumentException(
                "Extra payment ({$paymentAmount}) exceeds remaining balance ({$currentBalance})"
            );
        }

        // Calculate interest savings (simplified: interest saved on reduced balance)
        $remainingMonths = $currentTerm - ($currentMonth - 1);
        $originalTotalInterest = ($monthlyPayment * $remainingMonths) - $currentBalance;
        
        // Interest saved based on reduced balance
        $interestSavings = ($paymentAmount * $monthlyRate * $remainingMonths);

        // New balance after extra payment
        $newBalance = $currentBalance - $paymentAmount;

        // Determine if term is shortened
        $newTerm = $this->calculateNewTerm($newBalance, $monthlyRate, $monthlyPayment);

        // Recalculate monthly payment if balance significantly reduced
        $newMonthlyPayment = $this->calculateMonthlyPayment($newBalance, $monthlyRate, $newTerm);

        // Update loan
        $updatedLoan = [
            ...($loan ?? []),
            'current_balance' => $newBalance,
            'months' => $newTerm,
            'monthly_payment' => $newMonthlyPayment,
        ];

        $this->loanRepository->update($loanId, $updatedLoan);

        // Record extra payment in schedule
        $extraPaymentEntry = [
            'loan_id' => $loanId,
            'month' => $currentMonth,
            'payment' => $paymentAmount,
            'principal' => $paymentAmount,
            'interest' => 0,
            'balance' => $newBalance,
            'type' => 'extra_payment',
            'date' => $paymentDate,
        ];

        $this->scheduleRepository->insert($extraPaymentEntry);

        // Recalculate remaining schedule if term changed
        if ($newTerm < $currentTerm) {
            $this->recalculateSchedule(
                $loanId,
                $newBalance,
                $monthlyRate,
                $newTerm,
                $currentMonth + 1,
                $newMonthlyPayment
            );
        }

        return [
            'success' => true,
            'loan_id' => $loanId,
            'payment_amount' => $paymentAmount,
            'original_balance' => $currentBalance,
            'new_balance' => $newBalance,
            'interest_savings' => $interestSavings,
            'original_term' => $currentTerm,
            'new_term' => $newTerm,
            'original_monthly_payment' => $monthlyPayment,
            'new_monthly_payment' => $newMonthlyPayment,
            'months_saved' => max(0, $currentTerm - $newTerm),
            'payment_date' => $paymentDate,
            'message' => "Extra payment of {$paymentAmount} processed. Balance reduced from {$currentBalance} to {$newBalance}. Interest savings: {$interestSavings}",
        ];
    }

    /**
     * Validate extra payment event
     * 
     * @param array $event Event data
     * @throws \InvalidArgumentException If validation fails
     */
    private function validateEvent(array $event): void
    {
        if (empty($event['loan_id'])) {
            throw new \InvalidArgumentException('Missing required field: loan_id');
        }

        if (empty($event['type']) || $event['type'] !== 'extra_payment') {
            throw new \InvalidArgumentException('Invalid event type. Expected: extra_payment');
        }

        if (!isset($event['amount']) || !is_numeric($event['amount'])) {
            throw new \InvalidArgumentException('Missing or invalid field: amount');
        }

        if ($event['amount'] <= 0) {
            throw new \InvalidArgumentException('Payment amount must be positive');
        }

        // Validate loan_id is positive integer
        if (!is_int($event['loan_id']) || $event['loan_id'] <= 0) {
            throw new \InvalidArgumentException('Invalid loan_id. Must be positive integer');
        }
    }

    /**
     * Calculate new loan term after extra payment
     * 
     * @param float $balance Remaining balance
     * @param float $monthlyRate Monthly interest rate
     * @param float $monthlyPayment Monthly payment amount
     * @return int New term in months
     */
    private function calculateNewTerm(float $balance, float $monthlyRate, float $monthlyPayment): int
    {
        if ($balance <= 0) {
            return 0;
        }

        if ($monthlyRate == 0) {
            return (int) ceil($balance / $monthlyPayment);
        }

        // Using loan amortization formula to find n (number of months)
        $rate = 1 + $monthlyRate;
        $months = log($monthlyPayment / ($monthlyPayment - $monthlyRate * $balance)) / log($rate);

        return (int) ceil($months);
    }

    /**
     * Recalculate amortization schedule after extra payment
     * 
     * @param int $loanId Loan ID
     * @param float $balance Current balance
     * @param float $monthlyRate Monthly interest rate
     * @param int $totalMonths Total loan months
     * @param int $startMonth Starting month for recalculation
     * @param float $monthlyPayment Monthly payment amount
     */
    private function recalculateSchedule(
        int $loanId,
        float $balance,
        float $monthlyRate,
        int $totalMonths,
        int $startMonth,
        float $monthlyPayment
    ): void {
        $currentBalance = $balance;

        for ($month = $startMonth; $month <= $totalMonths; $month++) {
            $interest = $currentBalance * $monthlyRate;
            $principal = $monthlyPayment - $interest;

            if ($principal < 0) {
                $principal = $currentBalance;
            }

            $currentBalance -= $principal;

            // Handle final payment
            if ($currentBalance <= 0) {
                $principal += $currentBalance;
                $currentBalance = 0;
            }

            $scheduleEntry = [
                'loan_id' => $loanId,
                'month' => $month,
                'payment' => $monthlyPayment,
                'principal' => $principal,
                'interest' => $interest,
                'balance' => max(0, $currentBalance),
                'type' => 'recalculated',
            ];

            $this->scheduleRepository->insert($scheduleEntry);

            if ($currentBalance <= 0) {
                break;
            }
        }
    }

    /**
     * Calculate monthly payment using amortization formula
     * 
     * @param float $principal Loan principal
     * @param float $monthlyRate Monthly interest rate
     * @param int $months Number of months
     * @return float Monthly payment amount
     */
    private function calculateMonthlyPayment(float $principal, float $monthlyRate, int $months): float
    {
        if ($months <= 0 || $principal <= 0) {
            return 0;
        }

        if ($monthlyRate == 0) {
            return $principal / $months;
        }

        $numerator = $principal * ($monthlyRate * (1 + $monthlyRate) ** $months);
        $denominator = (((1 + $monthlyRate) ** $months) - 1);

        return $numerator / $denominator;
    }
}
