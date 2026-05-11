<?php

namespace App\Domain\Loan\Services;

use Decimal\Decimal;
use DateTime;

/**
 * Phase 2: Interest Accrual Service
 * Calculates and tracks daily interest accrual for accurate interest tracking
 * Uses Actual/365 day count convention (daily simple interest)
 */
class InterestAccrualService
{
    /**
     * Calculate daily interest accrual
     * Formula: (Balance × Annual Rate) / 365
     * 
     * @param Decimal $balance Current loan balance
     * @param Decimal $annualRate Annual interest rate (e.g., 7.75)
     * @param int $days Number of days to accrue (default 1)
     * @return Decimal Daily interest rounded to 2 decimals
     */
    public function calculateDailyInterest(
        Decimal $balance,
        Decimal $annualRate,
        int $days = 1
    ): Decimal {
        // Convert annual % to daily rate
        // 7.75% / 365 days = 0.0212329...%
        $dailyRate = $annualRate
            ->divide(new Decimal('365'))
            ->divide(new Decimal('100'));

        // Interest = Balance × Daily Rate × Days
        $interest = $balance
            ->multiply($dailyRate)
            ->multiply(new Decimal($days))
            ->round(2, BigDecimal::ROUND_HALF_UP);

        return $interest;
    }

    /**
     * Calculate interest accrued for a date range
     * Useful for: irregular payment periods, month-end accrual
     */
    public function calculateInterestForPeriod(
        Decimal $balance,
        Decimal $annualRate,
        DateTime $startDate,
        DateTime $endDate
    ): Decimal {
        $days = $endDate->diff($startDate)->days;
        return $this->calculateDailyInterest($balance, $annualRate, $days);
    }

    /**
     * Accrue interest since last payment
     * Handles: varying balances, actual payment dates
     */
    public function accrueInterestSincePayment(
        Decimal $currentBalance,
        Decimal $annualRate,
        DateTime $lastPaymentDate,
        DateTime $currentDate
    ): Decimal {
        return $this->calculateInterestForPeriod(
            $currentBalance,
            $annualRate,
            $lastPaymentDate,
            $currentDate
        );
    }

    /**
     * Calculate cumulative accrued interest between payment schedule periods
     * Used to verify amortization schedule matches actual accrual
     */
    public function calculateCumulativeAccrual(
        Decimal $principal,
        Decimal $annualRate,
        int $months,
        DateTime $fundingDate
    ): array {
        $schedule = [];
        $balance = clone $principal;
        $currentDate = clone $fundingDate;
        $cumulativeInterest = new Decimal('0');

        for ($day = 1; $day <= (365 * $months); $day++) {
            $dailyInterest = $this->calculateDailyInterest($balance, $annualRate, 1);
            $cumulativeInterest = $cumulativeInterest->add($dailyInterest);

            // Every 30 days, check against amortization schedule
            if ($day % 30 === 0) {
                $schedule[] = [
                    'day' => $day,
                    'date' => $currentDate->format('Y-m-d'),
                    'daily_accrual' => (string) $dailyInterest,
                    'cumulative' => (string) $cumulativeInterest,
                ];
            }

            $currentDate = $currentDate->modify('+1 day');
        }

        return $schedule;
    }

    /**
     * Post accrued interest to principal (move from accrued to balance)
     * This happens when: payment received, month-end close, etc.
     */
    public function postAccruedInterest(
        Decimal $currentBalance,
        Decimal $accruedInterestAmount
    ): Decimal {
        return $currentBalance->add($accruedInterestAmount)->round(2, BigDecimal::ROUND_HALF_UP);
    }

    /**
     * Calculate interest portion of payment
     * When payment received, split between interest accrued + new interest
     */
    public function calculatePaymentInterestPortion(
        Decimal $accruedInterest,
        Decimal $daysSinceLastPayment,
        Decimal $balance,
        Decimal $annualRate
    ): Decimal {
        // Interest since last payment
        $newInterest = $this->calculateDailyInterest($balance, $annualRate, (int) $daysSinceLastPayment);

        // Total interest due
        return $accruedInterest->add($newInterest)->round(2, BigDecimal::ROUND_HALF_UP);
    }

    /**
     * Validate interest accrual against schedule
     * Ensures actual daily accrual matches amortization schedule
     */
    public function validateAccrualAgainstSchedule(
        array $schedule,
        Decimal $principal,
        Decimal $annualRate,
        DateTime $fundingDate
    ): array {
        $cumulative = $this->calculateCumulativeAccrual(
            $principal,
            $annualRate,
            count($schedule), // Assuming monthly schedule
            $fundingDate
        );

        $differences = [];
        $period = 1;

        foreach ($schedule as $scheduled) {
            if (isset($cumulative[$period - 1])) {
                $scheduledInterest = new Decimal($scheduled['interest']);
                $accruedInterest = new Decimal($cumulative[$period - 1]['cumulative']);
                
                $diff = $scheduledInterest->subtract($accruedInterest)->abs();
                
                if ($diff->isGreaterThan(new Decimal('0.01'))) {
                    $differences[] = [
                        'period' => $period,
                        'scheduled' => (string) $scheduledInterest,
                        'accrued' => (string) $accruedInterest,
                        'difference' => (string) $diff,
                    ];
                }
            }
            $period++;
        }

        return $differences;
    }
}
