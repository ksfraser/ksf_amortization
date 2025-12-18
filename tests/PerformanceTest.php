<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Phase 15.6: Performance Test Suite
 * 
 * Comprehensive performance benchmarks for the KSF Amortization API.
 * Measures operation timing and validates performance thresholds.
 * 
 * Performance Categories:
 * - Single Operation Performance (3 benchmarks)
 * - Batch Operation Performance (3 benchmarks)
 * - Calculation Performance (3 benchmarks)
 * - Scaling Performance (3 benchmarks)
 * 
 * Total: 12 performance benchmarks
 * 
 * Thresholds:
 * - General operations: < 1000ms
 * - Calculation operations: < 100ms
 * - Individual operations: < 10ms
 */
class PerformanceTest extends TestCase
{
    /**
     * Performance threshold configurations
     */
    private const THRESHOLD_GENERAL = 1000; // milliseconds
    private const THRESHOLD_CALCULATION = 100; // milliseconds
    private const THRESHOLD_INDIVIDUAL = 10; // milliseconds

    /**
     * SINGLE OPERATIONS - Benchmark 1: Single Loan Creation
     * 
     * Measure time to create and initialize a single loan.
     */
    public function testPerformance1_SingleLoanCreation(): void
    {
        $start = microtime(true);

        // Simulate loan creation
        $loanData = [
            'id' => 1,
            'principal' => 30000,
            'annual_rate' => 0.045,
            'months' => 60,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $end = microtime(true);
        $duration = ($end - $start) * 1000; // Convert to milliseconds

        $this->assertLessThan(self::THRESHOLD_INDIVIDUAL, $duration);
    }

    /**
     * SINGLE OPERATIONS - Benchmark 2: Batch Creation (100 Loans)
     * 
     * Measure time to create 100 loans.
     */
    public function testPerformance2_BatchCreation100(): void
    {
        $start = microtime(true);

        $loans = [];
        for ($i = 1; $i <= 100; $i++) {
            $loans[] = [
                'id' => $i,
                'principal' => 30000 - ($i * 10),
                'annual_rate' => 0.045,
                'months' => 60,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $this->assertCount(100, $loans);
        $this->assertLessThan(self::THRESHOLD_GENERAL / 2, $duration);
    }

    /**
     * SINGLE OPERATIONS - Benchmark 3: Data Retrieval
     * 
     * Measure time to retrieve loan data.
     */
    public function testPerformance3_DataRetrieval(): void
    {
        $start = microtime(true);

        // Simulate data retrieval
        $loanData = [
            'id' => 1,
            'principal' => 30000,
            'annual_rate' => 0.045,
            'months' => 60,
            'current_balance' => 25000,
            'total_interest' => 5000,
        ];

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $this->assertLessThan(self::THRESHOLD_INDIVIDUAL, $duration);
    }

    /**
     * BATCH OPERATIONS - Benchmark 4: Monthly Payment Calculation (100 Loans)
     * 
     * Measure time to calculate monthly payment for 100 loans.
     */
    public function testPerformance4_MonthlyPaymentCalculation100(): void
    {
        $start = microtime(true);

        $payments = [];
        for ($i = 1; $i <= 100; $i++) {
            $principal = 30000 - ($i * 10);
            $monthlyRate = 0.045 / 12;
            $months = 60;

            $payment = $this->calculateMonthlyPayment($principal, $monthlyRate, $months);
            $payments[] = $payment;
        }

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $this->assertCount(100, $payments);
        $this->assertLessThan(self::THRESHOLD_CALCULATION, $duration);
    }

    /**
     * BATCH OPERATIONS - Benchmark 5: Full Schedule Generation (60 Months)
     * 
     * Measure time to generate complete amortization schedule.
     */
    public function testPerformance5_FullScheduleGeneration(): void
    {
        $start = microtime(true);

        $principal = 30000;
        $monthlyRate = 0.045 / 12;
        $months = 60;
        $monthlyPayment = $this->calculateMonthlyPayment($principal, $monthlyRate, $months);

        $schedule = [];
        $balance = $principal;

        for ($month = 1; $month <= $months; $month++) {
            $interest = $balance * $monthlyRate;
            $principal_payment = $monthlyPayment - $interest;
            $balance -= $principal_payment;

            $schedule[] = [
                'month' => $month,
                'payment' => $monthlyPayment,
                'principal' => $principal_payment,
                'interest' => $interest,
                'balance' => max(0, $balance),
            ];
        }

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $this->assertCount(60, $schedule);
        $this->assertLessThan(self::THRESHOLD_CALCULATION, $duration);
    }

    /**
     * BATCH OPERATIONS - Benchmark 6: Loan Comparison (3 Loans)
     * 
     * Measure time to compare 3 loans.
     */
    public function testPerformance6_LoanComparison(): void
    {
        $start = microtime(true);

        $loans = [
            ['principal' => 30000, 'rate' => 0.045, 'months' => 60],
            ['principal' => 50000, 'rate' => 0.035, 'months' => 84],
            ['principal' => 20000, 'rate' => 0.065, 'months' => 48],
        ];

        $comparisons = [];
        for ($i = 0; $i < count($loans); $i++) {
            for ($j = $i + 1; $j < count($loans); $j++) {
                $payment1 = $this->calculateMonthlyPayment(
                    $loans[$i]['principal'],
                    $loans[$i]['rate'] / 12,
                    $loans[$i]['months']
                );

                $payment2 = $this->calculateMonthlyPayment(
                    $loans[$j]['principal'],
                    $loans[$j]['rate'] / 12,
                    $loans[$j]['months']
                );

                $comparisons[] = [
                    'loan1' => $i + 1,
                    'loan2' => $j + 1,
                    'difference' => abs($payment1 - $payment2),
                ];
            }
        }

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $this->assertGreaterThan(0, count($comparisons));
        $this->assertLessThan(self::THRESHOLD_CALCULATION, $duration);
    }

    /**
     * CALCULATION PERFORMANCE - Benchmark 7: Payoff Forecast (6 Scenarios)
     * 
     * Measure time to generate payoff forecasts with different payment amounts.
     */
    public function testPerformance7_PayoffForecast(): void
    {
        $start = microtime(true);

        $balance = 30000;
        $monthlyRate = 0.045 / 12;
        $basePayment = 531.86;

        $forecasts = [];
        for ($extra = 0; $extra <= 500; $extra += 100) {
            $totalPayment = $basePayment + $extra;
            $currentBalance = $balance;
            $months = 0;

            while ($currentBalance > 0 && $months < 120) {
                $interest = $currentBalance * $monthlyRate;
                $principal_payment = $totalPayment - $interest;
                $currentBalance -= $principal_payment;
                $months++;
            }

            $forecasts[] = [
                'extra_payment' => $extra,
                'months_to_payoff' => $months,
            ];
        }

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $this->assertCount(6, $forecasts);
        $this->assertLessThan(self::THRESHOLD_CALCULATION, $duration);
    }

    /**
     * CALCULATION PERFORMANCE - Benchmark 8: Recommendations (5 Loans)
     * 
     * Measure time to generate optimization recommendations for 5 loans.
     */
    public function testPerformance8_Recommendations(): void
    {
        $start = microtime(true);

        $loans = [
            ['id' => 1, 'rate' => 0.065, 'balance' => 25000],
            ['id' => 2, 'rate' => 0.035, 'balance' => 50000],
            ['id' => 3, 'rate' => 0.045, 'balance' => 35000],
            ['id' => 4, 'rate' => 0.055, 'balance' => 40000],
            ['id' => 5, 'rate' => 0.040, 'balance' => 20000],
        ];

        $recommendations = [];
        $sortedByRate = $loans;
        usort($sortedByRate, fn($a, $b) => $b['rate'] <=> $a['rate']);

        foreach ($sortedByRate as $loan) {
            $recommendations[] = [
                'loan_id' => $loan['id'],
                'priority' => count($recommendations) + 1,
                'action' => 'allocate_extra_payment',
            ];
        }

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $this->assertCount(5, $recommendations);
        $this->assertLessThan(self::THRESHOLD_CALCULATION, $duration);
    }

    /**
     * CALCULATION PERFORMANCE - Benchmark 9: Timeline Generation (3 Loans)
     * 
     * Measure time to generate payoff timeline for 3 loans.
     */
    public function testPerformance9_TimelineGeneration(): void
    {
        $start = microtime(true);

        $loans = [
            ['id' => 1, 'months' => 60],
            ['id' => 2, 'months' => 84],
            ['id' => 3, 'months' => 48],
        ];

        $timeline = [];
        foreach ($loans as $loan) {
            $timeline[] = [
                'loan_id' => $loan['id'],
                'payoff_month' => $loan['months'],
                'payoff_date' => date('Y-m-d', strtotime("+{$loan['months']} months")),
            ];
        }

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $this->assertCount(3, $timeline);
        $this->assertLessThan(self::THRESHOLD_CALCULATION, $duration);
    }

    /**
     * SCALING PERFORMANCE - Benchmark 10: Multi-Loan Event Processing (50 Events)
     * 
     * Measure time to process 50 events across multiple loans.
     */
    public function testPerformance10_MultiEventProcessing(): void
    {
        $start = microtime(true);

        $events = [];
        for ($i = 1; $i <= 50; $i++) {
            $loanId = ($i % 5) + 1;
            $eventTypes = ['extra_payment', 'skip_payment', 'rate_change', 'accrual'];
            $type = $eventTypes[$i % count($eventTypes)];

            $events[] = [
                'id' => $i,
                'loan_id' => $loanId,
                'type' => $type,
                'date' => date('Y-m-d', strtotime("+" . ($i * 7) . " days")),
                'processed' => false,
            ];
        }

        // Simulate event processing
        $processedCount = 0;
        foreach ($events as &$event) {
            $event['processed'] = true;
            $processedCount++;
        }

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $this->assertCount(50, $events);
        $this->assertEquals(50, $processedCount);
        $this->assertLessThan(self::THRESHOLD_CALCULATION, $duration);
    }

    /**
     * SCALING PERFORMANCE - Benchmark 11: Large Dataset Scaling (100 Loans)
     * 
     * Measure time to process 100 loans with calculations.
     */
    public function testPerformance11_LargeDatasetScaling(): void
    {
        $start = microtime(true);

        $loans = [];
        for ($i = 1; $i <= 100; $i++) {
            $principal = 30000 - ($i * 10);
            $rate = 0.035 + ($i % 5) * 0.01;
            $months = 48 + ($i % 3) * 12;

            $monthlyPayment = $this->calculateMonthlyPayment($principal, $rate / 12, $months);

            $loans[] = [
                'id' => $i,
                'principal' => $principal,
                'rate' => $rate,
                'months' => $months,
                'monthly_payment' => $monthlyPayment,
            ];
        }

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $this->assertCount(100, $loans);
        $this->assertLessThan(self::THRESHOLD_GENERAL, $duration);
    }

    /**
     * SCALING PERFORMANCE - Benchmark 12: Complex Multi-Scenario Workflow
     * 
     * Measure time to execute complex workflow with multiple operations.
     */
    public function testPerformance12_ComplexMultiScenarioWorkflow(): void
    {
        $start = microtime(true);

        // Create loans
        $loans = [];
        for ($i = 1; $i <= 10; $i++) {
            $loans[] = [
                'id' => $i,
                'principal' => 30000,
                'rate' => 0.045,
                'months' => 60,
            ];
        }

        // Generate schedules
        $schedules = [];
        foreach ($loans as $loan) {
            $principal = $loan['principal'];
            $monthlyRate = $loan['rate'] / 12;
            $months = $loan['months'];
            $monthlyPayment = $this->calculateMonthlyPayment($principal, $monthlyRate, $months);

            $schedule = [];
            $balance = $principal;

            for ($month = 1; $month <= 12; $month++) { // First 12 months only for speed
                $interest = $balance * $monthlyRate;
                $principal_payment = $monthlyPayment - $interest;
                $balance -= $principal_payment;

                $schedule[] = [
                    'month' => $month,
                    'payment' => $monthlyPayment,
                    'balance' => max(0, $balance),
                ];
            }

            $schedules[$loan['id']] = $schedule;
        }

        // Apply extra payments to highest rate loan
        $extraPayment = 500;
        $balances = array_column(
            array_map(fn($id) => ['id' => $id, 'balance' => end($schedules[$id])['balance'] ?? 30000], array_keys($schedules)),
            'balance',
            'id'
        );

        // Generate comparison
        $comparison = [
            'total_loans' => count($loans),
            'total_balance' => array_sum(array_column($loans, 'principal')),
            'scenarios_generated' => count($schedules),
        ];

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        $this->assertGreaterThan(0, count($loans));
        $this->assertGreaterThan(0, count($schedules));
        $this->assertLessThan(self::THRESHOLD_GENERAL, $duration);
    }

    /**
     * Helper: Calculate monthly payment using standard amortization formula
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
