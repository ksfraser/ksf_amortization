<?php

declare(strict_types=1);

namespace Tests\Performance;

/**
 * Load Testing - Performance Under High Volume
 * TDD: Define performance requirements first
 */
class LoadTestingPerformanceTest extends PerformanceTestCase
{
    /**
     * Test: Create 100 loans in reasonable time
     * Requirement: < 500ms
     */
    public function testCreate100LoansPerformance(): void
    {
        $this->startMeasurement('create_100_loans');
        
        $loanIds = $this->createLoansForLoadTest(100);
        
        $metrics = $this->endMeasurement('create_100_loans');
        
        $this->assertEquals(100, count($loanIds));
        $this->assertPerformanceWithin('create_100_loans', 500, 5); // < 500ms, < 5MB
    }

    /**
     * Test: Create 500 loans in reasonable time
     * Requirement: < 2000ms
     */
    public function testCreate500LoansPerformance(): void
    {
        $this->startMeasurement('create_500_loans');
        
        $loanIds = $this->createLoansForLoadTest(500);
        
        $metrics = $this->endMeasurement('create_500_loans');
        
        $this->assertEquals(500, count($loanIds));
        $this->assertPerformanceWithin('create_500_loans', 2000, 10);
    }

    /**
     * Test: Create 1000 loans in reasonable time
     * Requirement: < 5000ms
     */
    public function testCreate1000LoansPerformance(): void
    {
        $this->startMeasurement('create_1000_loans');
        
        $loanIds = $this->createLoansForLoadTest(1000);
        
        $metrics = $this->endMeasurement('create_1000_loans');
        
        $this->assertEquals(1000, count($loanIds));
        $this->assertPerformanceWithin('create_1000_loans', 5000, 20);
    }

    /**
     * Test: Create payment schedules for 100 loans
     * Requirement: < 1000ms (100 loans × 12 payments = 1200 records)
     */
    public function testCreate100LoanPaymentSchedulesPerformance(): void
    {
        $loanIds = $this->createLoansForLoadTest(100);
        
        $this->startMeasurement('create_1200_payment_schedules');
        
        $scheduleCount = $this->createPaymentSchedulesForLoans($loanIds);
        
        $metrics = $this->endMeasurement('create_1200_payment_schedules');
        
        $this->assertEquals(1200, $scheduleCount);
        $this->assertPerformanceWithin('create_1200_payment_schedules', 1000, 8);
    }

    /**
     * Test: Create payment schedules for 500 loans
     * Requirement: < 4000ms (500 × 12 = 6000 records)
     */
    public function testCreate500LoanPaymentSchedulesPerformance(): void
    {
        $loanIds = $this->createLoansForLoadTest(500);
        
        $this->startMeasurement('create_6000_payment_schedules');
        
        $scheduleCount = $this->createPaymentSchedulesForLoans($loanIds);
        
        $metrics = $this->endMeasurement('create_6000_payment_schedules');
        
        $this->assertEquals(6000, $scheduleCount);
        $this->assertPerformanceWithin('create_6000_payment_schedules', 4000, 15);
    }

    /**
     * Test: Query portfolio balance with 100 loans
     * Requirement: < 50ms
     */
    public function testPortfolioBalanceQuery100Loans(): void
    {
        $loanIds = $this->createLoansForLoadTest(100);
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Test Portfolio',
            'manager_id' => 1,
        ]);
        
        // Link loans to portfolio
        foreach ($loanIds as $loanId) {
            $this->db->execute(
                'INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)',
                [$portfolioId, $loanId]
            );
        }
        
        $this->startMeasurement('portfolio_balance_100_loans');
        
        $balance = $this->portfolioAnalytics->getTotalPrincipalBalance($portfolioId);
        
        $metrics = $this->endMeasurement('portfolio_balance_100_loans');
        
        $this->assertIsNumeric($balance);
        $this->assertPerformanceWithin('portfolio_balance_100_loans', 50, 2);
    }

    /**
     * Test: Query portfolio balance with 500 loans
     * Requirement: < 100ms
     */
    public function testPortfolioBalanceQuery500Loans(): void
    {
        $loanIds = $this->createLoansForLoadTest(500);
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Test Portfolio',
            'manager_id' => 1,
        ]);
        
        foreach ($loanIds as $loanId) {
            $this->db->execute(
                'INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)',
                [$portfolioId, $loanId]
            );
        }
        
        $this->startMeasurement('portfolio_balance_500_loans');
        
        $balance = $this->portfolioAnalytics->getTotalPrincipalBalance($portfolioId);
        
        $metrics = $this->endMeasurement('portfolio_balance_500_loans');
        
        $this->assertIsNumeric($balance);
        $this->assertPerformanceWithin('portfolio_balance_500_loans', 100, 5);
    }

    /**
     * Test: Query weighted average rate with 100 loans
     * Requirement: < 50ms
     */
    public function testWeightedAverageRateQuery100Loans(): void
    {
        $loanIds = $this->createLoansForLoadTest(100);
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Test Portfolio',
            'manager_id' => 1,
        ]);
        
        foreach ($loanIds as $loanId) {
            $this->db->execute(
                'INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)',
                [$portfolioId, $loanId]
            );
        }
        
        $this->startMeasurement('weighted_avg_rate_100_loans');
        
        $rate = $this->portfolioAnalytics->getWeightedAverageRate($portfolioId);
        
        $metrics = $this->endMeasurement('weighted_avg_rate_100_loans');
        
        $this->assertIsNumeric($rate);
        $this->assertPerformanceWithin('weighted_avg_rate_100_loans', 50, 2);
    }

    /**
     * Test: Portfolio status distribution query
     * Requirement: < 50ms for 100 loans
     */
    public function testPortfolioStatusDistributionPerformance(): void
    {
        $loanIds = $this->createLoansForLoadTest(100);
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Test Portfolio',
            'manager_id' => 1,
        ]);
        
        foreach ($loanIds as $loanId) {
            $this->db->execute(
                'INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)',
                [$portfolioId, $loanId]
            );
        }
        
        $this->startMeasurement('portfolio_status_dist_100_loans');
        
        $status = $this->portfolioAnalytics->getPortfolioLoanStatus($portfolioId);
        
        $metrics = $this->endMeasurement('portfolio_status_dist_100_loans');
        
        $this->assertIsArray($status);
        $this->assertPerformanceWithin('portfolio_status_dist_100_loans', 50, 2);
    }

    /**
     * Test: Payment history query performance
     * Requirement: < 100ms for 100 loans (1200 payments)
     */
    public function testPaymentHistoryQueryPerformance(): void
    {
        $loanIds = $this->createLoansForLoadTest(100);
        $this->createPaymentSchedulesForLoans($loanIds);
        
        $this->startMeasurement('payment_history_query_1200_records');
        
        // Query payment history for all loans
        $totalRecords = 0;
        foreach ($loanIds as $loanId) {
            $history = $this->timeSeriesAnalytics->getLoanPaymentHistory($loanId);
            $totalRecords += count($history);
        }
        
        $metrics = $this->endMeasurement('payment_history_query_1200_records');
        
        $this->assertEquals(1200, $totalRecords);
        $this->assertPerformanceWithin('payment_history_query_1200_records', 100, 5);
    }

    /**
     * Test: Cumulative interest calculation performance
     * Requirement: < 50ms per loan
     */
    public function testCumulativeInterestCalculationPerformance(): void
    {
        $loanIds = $this->createLoansForLoadTest(50);
        $this->createPaymentSchedulesForLoans($loanIds);
        
        $this->startMeasurement('cumulative_interest_50_loans');
        
        foreach ($loanIds as $loanId) {
            $cumulative = $this->timeSeriesAnalytics->getCumulativeInterestPaid($loanId);
            $this->assertIsArray($cumulative);
        }
        
        $metrics = $this->endMeasurement('cumulative_interest_50_loans');
        
        // Average should be < 1ms per loan
        $avgPerLoan = $metrics['duration_ms'] / count($loanIds);
        $this->assertLessThan(1, $avgPerLoan);
    }

    /**
     * Test: Concurrent queries performance
     * Simulate multiple concurrent portfolio queries
     */
    public function testConcurrentPortfolioQueries(): void
    {
        $loanIds = $this->createLoansForLoadTest(100);
        
        // Create 5 portfolios
        $portfolioIds = [];
        for ($i = 0; $i < 5; $i++) {
            $portfolioId = $this->portfolioRepo->create([
                'name' => "Portfolio {$i}",
                'manager_id' => 1,
            ]);
            $portfolioIds[] = $portfolioId;
            
            // Distribute loans across portfolios
            for ($j = $i; $j < count($loanIds); $j += 5) {
                $this->db->execute(
                    'INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)',
                    [$portfolioId, $loanIds[$j]]
                );
            }
        }
        
        $this->startMeasurement('concurrent_portfolio_queries_5_portfolios');
        
        // Query all portfolios
        foreach ($portfolioIds as $portfolioId) {
            $balance = $this->portfolioAnalytics->getTotalPrincipalBalance($portfolioId);
            $rate = $this->portfolioAnalytics->getWeightedAverageRate($portfolioId);
            $status = $this->portfolioAnalytics->getPortfolioLoanStatus($portfolioId);
            
            $this->assertIsNumeric($balance);
            $this->assertIsNumeric($rate);
            $this->assertIsArray($status);
        }
        
        $metrics = $this->endMeasurement('concurrent_portfolio_queries_5_portfolios');
        
        // Total should be < 300ms
        $this->assertPerformanceWithin('concurrent_portfolio_queries_5_portfolios', 300, 5);
    }

    /**
     * Test: Database transaction performance
     * Requirement: < 200ms for 100 loan transactions
     */
    public function testDatabaseTransactionPerformance(): void
    {
        $this->startMeasurement('database_transactions_100_loans');
        
        $loanIds = [];
        for ($i = 0; $i < 100; $i++) {
            $this->db->beginTransaction();
            
            try {
                $loanId = $this->loanRepo->create([
                    'loan_number' => "TX-{$i}",
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
                
                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollback();
            }
        }
        
        $metrics = $this->endMeasurement('database_transactions_100_loans');
        
        $this->assertEquals(100, count($loanIds));
        $this->assertPerformanceWithin('database_transactions_100_loans', 200, 5);
    }
}
