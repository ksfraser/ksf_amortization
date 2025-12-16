<?php

declare(strict_types=1);

namespace Tests\Performance;

use Ksfraser\Amortizations\Persistence\Database;
use Ksfraser\Amortizations\Persistence\LoanRepository;
use Ksfraser\Amortizations\Persistence\PortfolioRepository;
use Ksfraser\Amortizations\Persistence\PaymentScheduleRepository;
use Ksfraser\Amortizations\Persistence\Schema;
use Ksfraser\Amortizations\Analytics\PortfolioAnalytics;
use Ksfraser\Amortizations\Analytics\TimeSeriesAnalytics;
use Ksfraser\Amortizations\Analytics\PredictiveAnalytics;
use PHPUnit\Framework\TestCase;

/**
 * Performance Test Case Base Class
 * Provides infrastructure for load testing and performance benchmarking
 */
abstract class PerformanceTestCase extends TestCase
{
    protected Database $db;
    protected Schema $schema;
    
    // Repositories
    protected LoanRepository $loanRepo;
    protected PortfolioRepository $portfolioRepo;
    protected PaymentScheduleRepository $scheduleRepo;
    
    // Analytics Services
    protected PortfolioAnalytics $portfolioAnalytics;
    protected TimeSeriesAnalytics $timeSeriesAnalytics;
    protected PredictiveAnalytics $predictiveAnalytics;

    // Performance metrics
    protected array $metrics = [];
    protected float $startTime = 0;
    protected float $startMemory = 0;

    protected function setUp(): void
    {
        // Initialize database with in-memory SQLite
        $this->db = new Database('sqlite::memory:');
        $this->schema = new Schema($this->db);
        
        // Create all tables
        $this->schema->createLoansTable();
        $this->schema->createPortfoliosTable();
        $this->schema->createApplicationsTable();
        $this->schema->createPaymentSchedulesTable();
        $this->schema->createAuditLogsTable();
        $this->schema->createPortfolioLoansTable();
        
        // Initialize repositories
        $this->loanRepo = new LoanRepository($this->db);
        $this->portfolioRepo = new PortfolioRepository($this->db);
        $this->scheduleRepo = new PaymentScheduleRepository($this->db);
        
        // Initialize analytics services
        $this->portfolioAnalytics = new PortfolioAnalytics($this->db);
        $this->timeSeriesAnalytics = new TimeSeriesAnalytics($this->db);
        $this->predictiveAnalytics = new PredictiveAnalytics($this->db);
        
        $this->metrics = [];
    }

    /**
     * Start performance measurement
     */
    protected function startMeasurement(string $name): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->metrics[$name] = [
            'start_time' => $this->startTime,
            'start_memory' => $this->startMemory,
        ];
    }

    /**
     * End performance measurement and store results
     */
    protected function endMeasurement(string $name): array
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $duration = ($endTime - $this->startTime) * 1000; // milliseconds
        $memoryUsed = ($endMemory - $this->startMemory) / 1024 / 1024; // MB
        
        $this->metrics[$name]['duration_ms'] = $duration;
        $this->metrics[$name]['memory_mb'] = $memoryUsed;
        $this->metrics[$name]['end_time'] = $endTime;
        $this->metrics[$name]['end_memory'] = $endMemory;
        
        return $this->metrics[$name];
    }

    /**
     * Get performance metrics
     */
    protected function getMetrics(string $name = null): array
    {
        if ($name !== null) {
            return $this->metrics[$name] ?? [];
        }
        return $this->metrics;
    }

    /**
     * Create multiple loans for load testing
     */
    protected function createLoansForLoadTest(int $count, int $borrowerStartId = 1): array
    {
        $loanIds = [];
        $rates = [4.5, 5.0, 5.5, 6.0, 6.5];
        $principals = [50000, 75000, 100000, 150000, 200000, 300000];
        
        for ($i = 0; $i < $count; $i++) {
            $loanId = $this->loanRepo->create([
                'loan_number' => "LOAD-TEST-{$i}",
                'borrower_id' => $borrowerStartId + ($i % 100), // 100 borrowers
                'principal' => $principals[$i % count($principals)],
                'interest_rate' => $rates[$i % count($rates)],
                'term_months' => 360,
                'start_date' => date('Y-m-d', strtotime("-" . ($i % 365) . " days")),
                'status' => $i % 20 === 0 ? 'paid_off' : 'active',
            ]);
            
            if ($loanId !== null) {
                $loanIds[] = $loanId;
            }
        }
        
        return $loanIds;
    }

    /**
     * Create payment schedules for loans
     */
    protected function createPaymentSchedulesForLoans(array $loanIds): int
    {
        $count = 0;
        foreach ($loanIds as $loanId) {
            $loan = $this->loanRepo->find($loanId);
            if (!$loan) continue;
            
            $monthlyRate = (float)$loan['interest_rate'] / 12 / 100;
            $principal = (float)$loan['principal'];
            $termMonths = (int)$loan['term_months'];
            
            // Create 12 months of payments
            $monthlyPayment = $principal * ($monthlyRate * pow(1 + $monthlyRate, min(12, $termMonths))) 
                             / (pow(1 + $monthlyRate, min(12, $termMonths)) - 1);
            
            $balance = $principal;
            $startTime = strtotime($loan['start_date']);
            
            for ($i = 1; $i <= min(12, $termMonths); $i++) {
                $dueDate = date('Y-m-d', strtotime("+$i months", $startTime));
                $interest = $balance * $monthlyRate;
                $principal_paid = $monthlyPayment - $interest;
                $balance -= $principal_paid;
                
                $this->scheduleRepo->create([
                    'loan_id' => $loanId,
                    'payment_number' => $i,
                    'due_date' => $dueDate,
                    'payment_amount' => $monthlyPayment,
                    'principal' => $principal_paid,
                    'interest' => $interest,
                    'balance' => max(0, $balance),
                    'status' => 'pending',
                ]);
                
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Assert performance meets threshold
     */
    protected function assertPerformanceWithin(string $name, float $maxDurationMs, float $maxMemoryMb = null): void
    {
        $metric = $this->getMetrics($name);
        
        $this->assertLessThanOrEqual(
            $maxDurationMs,
            $metric['duration_ms'],
            "Performance test '{$name}' exceeded time threshold: {$metric['duration_ms']}ms > {$maxDurationMs}ms"
        );
        
        if ($maxMemoryMb !== null) {
            $this->assertLessThanOrEqual(
                $maxMemoryMb,
                $metric['memory_mb'],
                "Performance test '{$name}' exceeded memory threshold: {$metric['memory_mb']}MB > {$maxMemoryMb}MB"
            );
        }
    }

    /**
     * Print performance metrics (for debugging)
     */
    protected function printMetrics(string $name = null): void
    {
        $metrics = $name ? [$name => $this->getMetrics($name)] : $this->getMetrics();
        
        foreach ($metrics as $testName => $data) {
            if (empty($data)) continue;
            
            printf(
                "Performance: %s - Duration: %.2fms, Memory: %.2fMB\n",
                $testName,
                $data['duration_ms'] ?? 0,
                $data['memory_mb'] ?? 0
            );
        }
    }
}
