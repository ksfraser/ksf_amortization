<?php

declare(strict_types=1);

namespace Tests\Performance;

/**
 * Stress Testing - System Limits and Failure Modes
 * TDD: Define stress test requirements first
 */
class StressTestingPerformanceTest extends PerformanceTestCase
{
    /**
     * Test: Memory usage doesn't grow excessively with 1000 loans
     * Requirement: < 30MB for 1000 loans
     */
    public function testMemoryUsageWith1000Loans(): void
    {
        $this->startMeasurement('memory_usage_1000_loans');
        
        $loanIds = $this->createLoansForLoadTest(1000);
        
        $metrics = $this->endMeasurement('memory_usage_1000_loans');
        
        $this->assertEquals(1000, count($loanIds));
        $this->assertLessThan(30, $metrics['memory_mb']);
    }

    /**
     * Test: Memory usage with 1000 loans and 12000 payment schedules
     * Requirement: < 50MB
     */
    public function testMemoryUsageWith1000LoansAndPaymentSchedules(): void
    {
        $loanIds = $this->createLoansForLoadTest(1000);
        
        $this->startMeasurement('memory_usage_1000_loans_12000_payments');
        
        $scheduleCount = $this->createPaymentSchedulesForLoans($loanIds);
        
        $metrics = $this->endMeasurement('memory_usage_1000_loans_12000_payments');
        
        $this->assertEquals(12000, $scheduleCount);
        $this->assertLessThan(50, $metrics['memory_mb']);
    }

    /**
     * Test: Rapid sequential loan creation (stress)
     * Requirement: Create 1000 loans in < 10 seconds without errors
     */
    public function testRapidSequentialLoanCreation(): void
    {
        $this->startMeasurement('rapid_sequential_1000_loans');
        
        $createdCount = 0;
        $failedCount = 0;
        
        for ($i = 0; $i < 1000; $i++) {
            $loanId = $this->loanRepo->create([
                'loan_number' => "STRESS-{$i}",
                'borrower_id' => rand(1, 100),
                'principal' => rand(50000, 300000),
                'interest_rate' => rand(40, 70) / 10, // 4.0 - 7.0
                'term_months' => 360,
                'start_date' => date('Y-m-d', strtotime("-" . rand(0, 365) . " days")),
                'status' => rand(0, 1) === 0 ? 'active' : 'paid_off',
            ]);
            
            if ($loanId !== null) {
                $createdCount++;
            } else {
                $failedCount++;
            }
        }
        
        $metrics = $this->endMeasurement('rapid_sequential_1000_loans');
        
        $this->assertEquals(1000, $createdCount);
        $this->assertEquals(0, $failedCount);
        $this->assertPerformanceWithin('rapid_sequential_1000_loans', 10000);
    }

    /**
     * Test: Rapid portfolio queries under load
     * Create 100 portfolios and query each 10 times
     */
    public function testRapidPortfolioQueries(): void
    {
        $loanIds = $this->createLoansForLoadTest(200);
        
        // Create portfolios
        $portfolioIds = [];
        for ($i = 0; $i < 100; $i++) {
            $portfolioId = $this->portfolioRepo->create([
                'name' => "Portfolio {$i}",
                'manager_id' => 1,
            ]);
            $portfolioIds[] = $portfolioId;
            
            // Distribute loans
            for ($j = $i % 10; $j < count($loanIds); $j += 10) {
                $this->db->execute(
                    'INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)',
                    [$portfolioId, $loanIds[$j]]
                );
            }
        }
        
        $this->startMeasurement('rapid_portfolio_queries_100_portfolios_10x');
        
        // Query each portfolio 10 times
        $queryCount = 0;
        for ($i = 0; $i < 10; $i++) {
            foreach ($portfolioIds as $portfolioId) {
                $balance = $this->portfolioAnalytics->getTotalPrincipalBalance($portfolioId);
                $rate = $this->portfolioAnalytics->getWeightedAverageRate($portfolioId);
                $status = $this->portfolioAnalytics->getPortfolioLoanStatus($portfolioId);
                
                $queryCount++;
            }
        }
        
        $metrics = $this->endMeasurement('rapid_portfolio_queries_100_portfolios_10x');
        
        $this->assertEquals(1000, $queryCount); // 100 portfolios * 10 queries
        $this->assertPerformanceWithin('rapid_portfolio_queries_100_portfolios_10x', 2000);
    }

    /**
     * Test: Database handles rapid transactions
     * Requirement: 100 rapid transactions < 500ms
     */
    public function testRapidDatabaseTransactions(): void
    {
        $this->startMeasurement('rapid_transactions_100');
        
        $successCount = 0;
        for ($i = 0; $i < 100; $i++) {
            $this->db->beginTransaction();
            
            try {
                $loanId = $this->loanRepo->create([
                    'loan_number' => "TRANS-{$i}",
                    'borrower_id' => 1,
                    'principal' => 100000,
                    'interest_rate' => 5.0,
                    'term_months' => 360,
                    'start_date' => '2024-01-01',
                    'status' => 'active',
                ]);
                
                // Update immediately
                if ($loanId !== null) {
                    $this->loanRepo->update($loanId, ['status' => 'pending']);
                    $successCount++;
                }
                
                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollback();
            }
        }
        
        $metrics = $this->endMeasurement('rapid_transactions_100');
        
        $this->assertEquals(100, $successCount);
        $this->assertPerformanceWithin('rapid_transactions_100', 500, 3);
    }

    /**
     * Test: Payment schedule queries with large dataset
     * Requirement: Query 6000+ payment records efficiently
     */
    public function testLargePaymentScheduleQueries(): void
    {
        $loanIds = $this->createLoansForLoadTest(500);
        $this->createPaymentSchedulesForLoans($loanIds);
        
        $this->startMeasurement('large_payment_schedule_queries_6000_records');
        
        $totalRecords = 0;
        $totalInterest = 0;
        
        foreach ($loanIds as $loanId) {
            $history = $this->timeSeriesAnalytics->getLoanPaymentHistory($loanId);
            $cumulative = $this->timeSeriesAnalytics->getCumulativeInterestPaid($loanId);
            
            $totalRecords += count($history);
            if (!empty($cumulative)) {
                $totalInterest += end($cumulative)['cumulative_interest'] ?? 0;
            }
        }
        
        $metrics = $this->endMeasurement('large_payment_schedule_queries_6000_records');
        
        $this->assertEquals(6000, $totalRecords);
        $this->assertGreaterThan(0, $totalInterest);
        $this->assertPerformanceWithin('large_payment_schedule_queries_6000_records', 2500, 10);
    }

    /**
     * Test: Complex aggregations with large dataset
     * Requirement: Calculate portfolio metrics for 500 loans < 200ms
     */
    public function testComplexAggregations500Loans(): void
    {
        $loanIds = $this->createLoansForLoadTest(500);
        
        // Create portfolio
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Complex Portfolio',
            'manager_id' => 1,
        ]);
        
        foreach ($loanIds as $loanId) {
            $this->db->execute(
                'INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)',
                [$portfolioId, $loanId]
            );
        }
        
        $this->startMeasurement('complex_aggregations_500_loans');
        
        $balance = $this->portfolioAnalytics->getTotalPrincipalBalance($portfolioId);
        $rate = $this->portfolioAnalytics->getWeightedAverageRate($portfolioId);
        $status = $this->portfolioAnalytics->getPortfolioLoanStatus($portfolioId);
        
        $metrics = $this->endMeasurement('complex_aggregations_500_loans');
        
        $this->assertIsNumeric($balance);
        $this->assertIsNumeric($rate);
        $this->assertIsArray($status);
        $this->assertPerformanceWithin('complex_aggregations_500_loans', 200, 5);
    }

    /**
     * Test: Stress test with alternating operations
     * Create, read, update, delete in rapid succession
     */
    public function testAlternatingOperationsStress(): void
    {
        $this->startMeasurement('alternating_crud_stress');
        
        $operationCount = 0;
        $loanIds = [];
        
        // Create phase
        for ($i = 0; $i < 50; $i++) {
            $loanId = $this->loanRepo->create([
                'loan_number' => "CRUD-{$i}",
                'borrower_id' => 1,
                'principal' => 100000,
                'interest_rate' => 5.0,
                'term_months' => 360,
                'start_date' => '2024-01-01',
                'status' => 'active',
            ]);
            if ($loanId !== null) {
                $loanIds[] = $loanId;
            }
            $operationCount++;
        }
        
        // Read phase
        foreach ($loanIds as $loanId) {
            $loan = $this->loanRepo->find($loanId);
            $this->assertNotNull($loan);
            $operationCount++;
        }
        
        // Update phase
        foreach ($loanIds as $loanId) {
            $this->loanRepo->update($loanId, ['status' => 'pending']);
            $operationCount++;
        }
        
        // Read again
        foreach ($loanIds as $loanId) {
            $loan = $this->loanRepo->find($loanId);
            $this->assertEquals('pending', $loan['status']);
            $operationCount++;
        }
        
        $metrics = $this->endMeasurement('alternating_crud_stress');
        
        // 50 creates + 50 reads + 50 updates + 50 reads = 200 operations
        $this->assertEquals(200, $operationCount);
        // Should complete in < 500ms
        $this->assertPerformanceWithin('alternating_crud_stress', 500, 5);
    }

    /**
     * Test: Concurrent access pattern (simulated)
     * Multiple queries and updates happening in sequence
     */
    public function testConcurrentAccessPattern(): void
    {
        $loanIds = $this->createLoansForLoadTest(100);
        
        // Create payment schedules
        $this->createPaymentSchedulesForLoans($loanIds);
        
        $this->startMeasurement('concurrent_access_pattern');
        
        for ($round = 0; $round < 10; $round++) {
            // Query operations
            foreach (array_slice($loanIds, 0, 20) as $loanId) {
                $history = $this->timeSeriesAnalytics->getLoanPaymentHistory($loanId);
                $this->assertIsArray($history);
            }
            
            // Update operations
            foreach (array_slice($loanIds, 20, 20) as $loanId) {
                $this->scheduleRepo->update(
                    $this->db->fetchOne(
                        'SELECT id FROM payment_schedules WHERE loan_id = ? LIMIT 1',
                        [$loanId]
                    )['id'] ?? null,
                    ['status' => 'paid']
                );
            }
            
            // Analytics operations
            $rate = $this->portfolioAnalytics->getWeightedAverageRate(1);
            $this->assertIsNumeric($rate);
        }
        
        $metrics = $this->endMeasurement('concurrent_access_pattern');
        
        // Should handle 10 rounds of mixed operations smoothly
        $this->assertPerformanceWithin('concurrent_access_pattern', 1000, 10);
    }

    /**
     * Test: Peak load scenario - 5000 loans
     * Requirement: Complete in < 30 seconds
     */
    public function testPeakLoadScenario5000Loans(): void
    {
        $this->startMeasurement('peak_load_5000_loans');
        
        $loanIds = $this->createLoansForLoadTest(5000);
        
        $metrics = $this->endMeasurement('peak_load_5000_loans');
        
        $this->assertEquals(5000, count($loanIds));
        // At peak load, allow up to 30 seconds
        $this->assertPerformanceWithin('peak_load_5000_loans', 30000, 50);
    }

    /**
     * Test: Sustained load - continuous operations
     * Create, query, update continuously for 5 seconds
     */
    public function testSustainedLoadContinuousOperations(): void
    {
        $this->startMeasurement('sustained_load_continuous');
        
        $operations = 0;
        $startTime = microtime(true);
        $maxDuration = 5; // 5 seconds
        
        while (microtime(true) - $startTime < $maxDuration) {
            // Create
            $loanId = $this->loanRepo->create([
                'loan_number' => "SUSTAINED-{$operations}",
                'borrower_id' => rand(1, 50),
                'principal' => 100000,
                'interest_rate' => 5.0,
                'term_months' => 360,
                'start_date' => '2024-01-01',
                'status' => 'active',
            ]);
            
            if ($loanId !== null) {
                // Read
                $loan = $this->loanRepo->find($loanId);
                
                // Update
                $this->loanRepo->update($loanId, ['status' => 'pending']);
                
                $operations++;
            }
        }
        
        $metrics = $this->endMeasurement('sustained_load_continuous');
        
        // Should complete many operations per second
        $opsPerSecond = $operations / $maxDuration;
        $this->assertGreaterThan(50, $opsPerSecond); // At least 50 ops/sec
    }
}
