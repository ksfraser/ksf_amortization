<?php

namespace Ksfraser\Amortizations\EventHandlers;

use Ksfraser\Amortizations\Repositories\LoanRepository;
use Ksfraser\Amortizations\Repositories\ScheduleRepository;

/**
 * Skip Payment Handler
 * 
 * Handles skip payment events on loans.
 * Processes skip payment requests by:
 * - Validating the skip payment event
 * - Accruing interest for the skipped month
 * - Extending loan term by one month
 * - Recalculating amortization schedule
 * 
 * @author KSF
 * @version 1.0.0
 */
class SkipPaymentHandler
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
     * Handle skip payment event
     * 
     * @param array $event Skip payment event data
     * @return array Processing result
     * @throws \InvalidArgumentException If validation fails
     */
    public function handle(array $event): array
    {
        // Validate event
        $this->validateEvent($event);

        $loanId = $event['loan_id'];
        $skipDate = $event['date'];

        // Get loan data
        $loan = $this->loanRepository->findById($loanId);
        if (!$loan) {
            throw new \InvalidArgumentException("Loan not found: {$loanId}");
        }

        // Get current loan term and balance
        $currentTerm = $loan['months'] ?? 60;
        $currentBalance = $loan['current_balance'] ?? $loan['principal'] ?? 0;
        $currentMonth = $loan['current_month'] ?? 1;

        // Calculate accrued interest
        $annualRate = $loan['annual_rate'] ?? $loan['rate'] ?? 0;
        $monthlyRate = $annualRate / 12;
        $accruedInterest = $currentBalance * $monthlyRate;

        // New balance includes accrued interest
        $newBalance = $currentBalance + $accruedInterest;

        // Extend loan term by 1 month
        $newTerm = $currentTerm + 1;

        // Recalculate monthly payment
        $newMonthlyPayment = $this->calculateMonthlyPayment($newBalance, $monthlyRate, $newTerm);

        // Update loan
        $updatedLoan = [
            ...($loan ?? []),
            'months' => $newTerm,
            'current_balance' => $newBalance,
            'monthly_payment' => $newMonthlyPayment,
        ];

        $this->loanRepository->update($loanId, $updatedLoan);

        // Generate skip payment schedule entry (no payment, only interest accrual)
        $skipScheduleEntry = [
            'loan_id' => $loanId,
            'month' => $currentMonth,
            'payment' => 0,
            'principal' => 0,
            'interest' => $accruedInterest,
            'balance' => $newBalance,
            'type' => 'skip_payment',
            'date' => $skipDate,
        ];

        $this->scheduleRepository->insert($skipScheduleEntry);

        // Recalculate remaining schedule
        $this->recalculateSchedule($loanId, $newBalance, $monthlyRate, $newTerm, $currentMonth + 1, $newMonthlyPayment);

        return [
            'success' => true,
            'loan_id' => $loanId,
            'original_balance' => $currentBalance,
            'new_balance' => $newBalance,
            'accrued_interest' => $accruedInterest,
            'original_term' => $currentTerm,
            'new_term' => $newTerm,
            'new_monthly_payment' => $newMonthlyPayment,
            'skip_date' => $skipDate,
            'message' => "Skip payment processed. Loan term extended from {$currentTerm} to {$newTerm} months.",
        ];
    }

    /**
     * Validate skip payment event
     * 
     * @param array $event Event data
     * @throws \InvalidArgumentException If validation fails
     */
    private function validateEvent(array $event): void
    {
        if (empty($event['loan_id'])) {
            throw new \InvalidArgumentException('Missing required field: loan_id');
        }

        if (empty($event['type']) || $event['type'] !== 'skip_payment') {
            throw new \InvalidArgumentException('Invalid event type. Expected: skip_payment');
        }

        if (empty($event['date'])) {
            throw new \InvalidArgumentException('Missing required field: date');
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $event['date'])) {
            throw new \InvalidArgumentException('Invalid date format. Expected: YYYY-MM-DD');
        }

        // Validate loan_id is positive integer
        if (!is_int($event['loan_id']) || $event['loan_id'] <= 0) {
            throw new \InvalidArgumentException('Invalid loan_id. Must be positive integer');
        }
    }

    /**
     * Recalculate amortization schedule after skip payment
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
        if ($monthlyRate == 0) {
            return $principal / $months;
        }

        $numerator = $principal * ($monthlyRate * (1 + $monthlyRate) ** $months);
        $denominator = (((1 + $monthlyRate) ** $months) - 1);

        return $numerator / $denominator;
    }
}
