<?php

namespace Ksfraser\Amortizations\Optimization;

use Ksfraser\Amortizations\Cache\CacheLayer;

/**
 * Performance Optimizer
 * 
 * Optimizes calculations and performance for the KSF Amortization API.
 * Implements strategies for calculation efficiency and performance improvement.
 * 
 * Features:
 * - Memoization of calculations
 * - Batch calculation processing
 * - Simplified calculation algorithms
 * - Early exit strategies
 * - Precision vs speed optimization
 * - Performance monitoring
 * 
 * @author KSF
 * @version 1.0.0
 */
class PerformanceOptimizer
{
    private CacheLayer $cache;
    private array $memoCache = [];
    private array $performanceMetrics = [];

    /**
     * Initialize performance optimizer
     * 
     * @param CacheLayer $cache Cache layer for memoization
     */
    public function __construct(CacheLayer $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Calculate monthly payment with memoization
     * 
     * Caches payment calculations to avoid recalculating identical scenarios.
     * 
     * @param float $principal Loan principal
     * @param float $monthlyRate Monthly interest rate
     * @param int $months Number of months
     * @return float Monthly payment amount
     */
    public function calculateMonthlyPaymentMemoized(float $principal, float $monthlyRate, int $months): float
    {
        // Create memo key
        $key = "payment_" . md5("{$principal}_{$monthlyRate}_{$months}");

        // Check cache first
        if (isset($this->memoCache[$key])) {
            return $this->memoCache[$key];
        }

        // Calculate
        if ($monthlyRate == 0) {
            $payment = $principal / $months;
        } else {
            $numerator = $principal * ($monthlyRate * (1 + $monthlyRate) ** $months);
            $denominator = (((1 + $monthlyRate) ** $months) - 1);
            $payment = $numerator / $denominator;
        }

        // Memoize result
        $this->memoCache[$key] = $payment;

        return $payment;
    }

    /**
     * Batch calculate monthly payments
     * 
     * Processes multiple loan calculations efficiently.
     * 
     * @param array $loans Array of loan data
     * @return array Array of calculated payments indexed by loan ID
     */
    public function batchCalculatePayments(array $loans): array
    {
        $payments = [];

        foreach ($loans as $loan) {
            $loanId = $loan['id'];
            $principal = $loan['principal'] ?? 0;
            $monthlyRate = ($loan['rate'] ?? 0.045) / 12;
            $months = $loan['months'] ?? 60;

            // Use memoized calculation
            $payment = $this->calculateMonthlyPaymentMemoized($principal, $monthlyRate, $months);
            $payments[$loanId] = $payment;
        }

        return $payments;
    }

    /**
     * Calculate interest with optimization
     * 
     * Uses simplified, direct calculation for optimal performance.
     * 
     * @param float $balance Loan balance
     * @param float $monthlyRate Monthly interest rate
     * @return float Interest amount
     */
    public function calculateInterestOptimized(float $balance, float $monthlyRate): float
    {
        // Direct calculation (optimized)
        return $balance * $monthlyRate;
    }

    /**
     * Generate schedule with early exit
     * 
     * Terminates schedule generation when balance is paid off.
     * Avoids unnecessary iterations.
     * 
     * @param float $balance Starting balance
     * @param float $monthlyPayment Monthly payment
     * @param float $monthlyRate Monthly interest rate
     * @param int $maxMonths Maximum months to calculate
     * @return array Schedule entries
     */
    public function generateScheduleWithEarlyExit(
        float $balance,
        float $monthlyPayment,
        float $monthlyRate,
        int $maxMonths = 360
    ): array {
        $schedule = [];
        $currentBalance = $balance;

        for ($month = 1; $month <= $maxMonths; $month++) {
            $interest = $this->calculateInterestOptimized($currentBalance, $monthlyRate);
            $principal = $monthlyPayment - $interest;

            // Early exit if balance becomes too small
            if ($currentBalance <= 0.01) {
                break;
            }

            // Handle final payment
            if ($currentBalance < $monthlyPayment) {
                $principal = $currentBalance;
                $currentBalance = 0;
            } else {
                $currentBalance -= $principal;
            }

            $schedule[] = [
                'month' => $month,
                'payment' => $monthlyPayment,
                'principal' => $principal,
                'interest' => $interest,
                'balance' => max(0, $currentBalance),
            ];

            // Exit early when paid off
            if ($currentBalance <= 0) {
                break;
            }
        }

        return $schedule;
    }

    /**
     * Calculate with precision/speed trade-off
     * 
     * Allows choosing between precision and calculation speed.
     * 
     * @param float $balance Loan balance
     * @param float $monthlyRate Monthly interest rate
     * @param string $precision 'high' for high precision, 'standard' for speed
     * @return float Interest amount
     */
    public function calculateWithPrecisionTradeoff(
        float $balance,
        float $monthlyRate,
        string $precision = 'standard'
    ): float {
        if ($precision === 'high') {
            // Use bcmath for high precision (slower)
            return (float) bcmul($balance, (string) $monthlyRate, 8);
        }

        // Standard precision with PHP multiplication (faster)
        return $balance * $monthlyRate;
    }

    /**
     * Optimize loan comparison
     * 
     * Pre-calculates and caches comparison results.
     * 
     * @param array $loans Array of loans to compare
     * @return array Comparison results with rankings
     */
    public function optimizeLoanComparison(array $loans): array
    {
        $cacheKey = 'comparison_' . md5(json_encode(array_column($loans, 'id')));

        // Check cache
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Calculate comparison metrics
        $comparisons = [];

        foreach ($loans as $loan) {
            $monthlyPayment = $this->calculateMonthlyPaymentMemoized(
                $loan['principal'] ?? 0,
                ($loan['rate'] ?? 0.045) / 12,
                $loan['months'] ?? 60
            );

            $comparisons[$loan['id']] = [
                'id' => $loan['id'],
                'principal' => $loan['principal'] ?? 0,
                'rate' => $loan['rate'] ?? 0.045,
                'months' => $loan['months'] ?? 60,
                'monthly_payment' => $monthlyPayment,
                'total_cost' => $monthlyPayment * ($loan['months'] ?? 60),
            ];
        }

        // Sort by monthly payment
        usort($comparisons, fn($a, $b) => $a['monthly_payment'] <=> $b['monthly_payment']);

        // Add rankings
        foreach ($comparisons as $key => $comparison) {
            $comparisons[$key]['rank'] = $key + 1;
        }

        // Cache result
        $this->cache->set($cacheKey, $comparisons, 3600);

        return $comparisons;
    }

    /**
     * Get performance metrics
     * 
     * Returns performance optimization metrics.
     * 
     * @return array Performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'memoization_cache_size' => count($this->memoCache),
            'cache_statistics' => $this->cache->getStats(),
            'optimization_metrics' => $this->performanceMetrics,
        ];
    }

    /**
     * Record performance metric
     * 
     * Tracks optimization performance.
     * 
     * @param string $name Metric name
     * @param float $value Metric value
     * @return void
     */
    public function recordMetric(string $name, float $value): void
    {
        if (!isset($this->performanceMetrics[$name])) {
            $this->performanceMetrics[$name] = [];
        }

        $this->performanceMetrics[$name][] = $value;
    }

    /**
     * Get average metric value
     * 
     * @param string $name Metric name
     * @return float Average value
     */
    public function getAverageMetric(string $name): float
    {
        if (!isset($this->performanceMetrics[$name]) || empty($this->performanceMetrics[$name])) {
            return 0;
        }

        $values = $this->performanceMetrics[$name];
        return array_sum($values) / count($values);
    }

    /**
     * Clear memoization cache
     * 
     * @return void
     */
    public function clearMemoCache(): void
    {
        $this->memoCache = [];
    }
}
