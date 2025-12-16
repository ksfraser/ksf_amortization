<?php

declare(strict_types=1);

namespace Tests\Integration;

use Ksfraser\Amortizations\Persistence\Database;
use Ksfraser\Amortizations\Persistence\LoanRepository;
use Ksfraser\Amortizations\Persistence\PortfolioRepository;
use Ksfraser\Amortizations\Persistence\ApplicationRepository;
use Ksfraser\Amortizations\Persistence\PaymentScheduleRepository;
use Ksfraser\Amortizations\Persistence\AuditLogRepository;
use Ksfraser\Amortizations\Persistence\Schema;
use Ksfraser\Amortizations\Analytics\PortfolioAnalytics;
use Ksfraser\Amortizations\Analytics\TimeSeriesAnalytics;
use Ksfraser\Amortizations\Analytics\PredictiveAnalytics;
use Ksfraser\Amortizations\Compliance\APRValidator;
use Ksfraser\Amortizations\Compliance\TILACompliance;
use Ksfraser\Amortizations\Compliance\FairLendingValidator;
use Ksfraser\Amortizations\Compliance\RegulatoryReporting;
use PHPUnit\Framework\TestCase;

/**
 * Integration Test Base Class
 * Provides common setup for all integration tests
 */
abstract class IntegrationTestCase extends TestCase
{
    protected Database $db;
    protected Schema $schema;
    
    // Repositories
    protected LoanRepository $loanRepo;
    protected PortfolioRepository $portfolioRepo;
    protected ApplicationRepository $appRepo;
    protected PaymentScheduleRepository $scheduleRepo;
    protected AuditLogRepository $auditRepo;
    
    // Analytics Services
    protected PortfolioAnalytics $portfolioAnalytics;
    protected TimeSeriesAnalytics $timeSeriesAnalytics;
    protected PredictiveAnalytics $predictiveAnalytics;
    
    // Compliance Services
    protected APRValidator $aprValidator;
    protected TILACompliance $tila;
    protected FairLendingValidator $fairLending;
    protected RegulatoryReporting $reporting;

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
        $this->appRepo = new ApplicationRepository($this->db);
        $this->scheduleRepo = new PaymentScheduleRepository($this->db);
        $this->auditRepo = new AuditLogRepository($this->db);
        
        // Initialize analytics services
        $this->portfolioAnalytics = new PortfolioAnalytics($this->db);
        $this->timeSeriesAnalytics = new TimeSeriesAnalytics($this->db);
        $this->predictiveAnalytics = new PredictiveAnalytics($this->db);
        
        // Initialize compliance services
        $this->aprValidator = new APRValidator($this->db);
        $this->tila = new TILACompliance($this->db);
        $this->fairLending = new FairLendingValidator($this->db);
        $this->reporting = new RegulatoryReporting($this->db);
    }

    /**
     * Helper: Create a complete loan with payment schedule
     */
    protected function createLoanWithSchedule(
        string $loanNumber,
        int $borrowerId,
        float $principal,
        float $interestRate,
        int $termMonths,
        string $startDate = '2024-01-01'
    ): int {
        // Create loan
        $loanId = $this->loanRepo->create([
            'loan_number' => $loanNumber,
            'borrower_id' => $borrowerId,
            'principal' => $principal,
            'interest_rate' => $interestRate,
            'term_months' => $termMonths,
            'start_date' => $startDate,
            'status' => 'active',
        ]);

        // Create payment schedule (12 months of payments)
        $monthlyRate = $interestRate / 12 / 100;
        $monthlyPayment = $principal * ($monthlyRate * pow(1 + $monthlyRate, min(12, $termMonths))) 
                         / (pow(1 + $monthlyRate, min(12, $termMonths)) - 1);
        
        $balance = $principal;
        $startTime = strtotime($startDate);

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
        }

        return $loanId;
    }

    /**
     * Helper: Create a portfolio with loans
     */
    protected function createPortfolioWithLoans(
        string $name,
        int $managerId,
        array $loans
    ): int {
        $portfolioId = $this->portfolioRepo->create([
            'name' => $name,
            'manager_id' => $managerId,
            'description' => "Test portfolio: $name",
        ]);

        // Add loans to portfolio
        foreach ($loans as $loanId) {
            $this->db->execute(
                'INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)',
                [$portfolioId, $loanId]
            );
        }

        return $portfolioId;
    }

    /**
     * Helper: Verify audit trail
     */
    protected function assertAuditTrail(int $loanId, string $expectedAction, int $minCount = 1): void
    {
        $trail = $this->auditRepo->getHistory('Loan', $loanId);
        $actions = array_filter($trail, fn($entry) => $entry['action'] === $expectedAction);
        
        $this->assertGreaterThanOrEqual(
            $minCount,
            count($actions),
            "Expected at least $minCount audit entries for action '$expectedAction'"
        );
    }
}
