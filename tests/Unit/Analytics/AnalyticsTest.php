<?php

declare(strict_types=1);

namespace Tests\Unit\Analytics;

use Ksfraser\Amortizations\Persistence\Database;
use Ksfraser\Amortizations\Persistence\LoanRepository;
use Ksfraser\Amortizations\Persistence\PortfolioRepository;
use Ksfraser\Amortizations\Persistence\PaymentScheduleRepository;
use Ksfraser\Amortizations\Persistence\Schema;
use Ksfraser\Amortizations\Analytics\PortfolioAnalytics;
use Ksfraser\Amortizations\Analytics\TimeSeriesAnalytics;
use Ksfraser\Amortizations\Analytics\CohortAnalytics;
use Ksfraser\Amortizations\Analytics\PredictiveAnalytics;
use Ksfraser\Amortizations\Analytics\RiskAnalytics;
use PHPUnit\Framework\TestCase;

class AnalyticsTest extends TestCase
{
    private Database $db;
    private Schema $schema;
    private LoanRepository $loanRepo;
    private PortfolioRepository $portfolioRepo;
    private PaymentScheduleRepository $scheduleRepo;
    private PortfolioAnalytics $portfolioAnalytics;
    private TimeSeriesAnalytics $timeSeriesAnalytics;
    private CohortAnalytics $cohortAnalytics;
    private PredictiveAnalytics $predictiveAnalytics;
    private RiskAnalytics $riskAnalytics;

    protected function setUp(): void
    {
        $this->db = new Database('sqlite::memory:');
        $this->schema = new Schema($this->db);
        
        // Create tables
        $this->schema->createLoansTable();
        $this->schema->createPortfoliosTable();
        $this->schema->createPaymentSchedulesTable();
        $this->schema->createPortfolioLoansTable();
        $this->schema->createAuditLogsTable();
        
        // Initialize repositories
        $this->loanRepo = new LoanRepository($this->db);
        $this->portfolioRepo = new PortfolioRepository($this->db);
        $this->scheduleRepo = new PaymentScheduleRepository($this->db);
        
        // Initialize analytics services
        $this->portfolioAnalytics = new PortfolioAnalytics($this->db);
        $this->timeSeriesAnalytics = new TimeSeriesAnalytics($this->db);
        $this->cohortAnalytics = new CohortAnalytics($this->db);
        $this->predictiveAnalytics = new PredictiveAnalytics($this->db);
        $this->riskAnalytics = new RiskAnalytics($this->db);
    }

    // ==================== Portfolio Analytics Tests ====================

    public function testGetTotalPrincipalBalance(): void
    {
        // Create portfolio and loans
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Test Portfolio',
            'manager_id' => 1,
            'description' => 'Test',
        ]);

        $loanId = $this->loanRepo->create([
            'loan_number' => 'LOAN001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        // Add loan to portfolio
        $this->db->execute(
            'INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)',
            [$portfolioId, $loanId]
        );

        // Create payment schedule
        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $balance = $this->portfolioAnalytics->getTotalPrincipalBalance($portfolioId);
        $this->assertGreaterThan(0, $balance);
    }

    public function testGetWeightedAverageRate(): void
    {
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Rate Test Portfolio',
            'manager_id' => 1,
            'description' => 'Test',
        ]);

        // Create loans with different rates
        $loan1 = $this->loanRepo->create([
            'loan_number' => 'LOAN002',
            'borrower_id' => 1,
            'principal' => 100000,
            'interest_rate' => 4.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $loan2 = $this->loanRepo->create([
            'loan_number' => 'LOAN003',
            'borrower_id' => 2,
            'principal' => 200000,
            'interest_rate' => 6.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        // Add both to portfolio
        $this->db->execute('INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)', [$portfolioId, $loan1]);
        $this->db->execute('INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)', [$portfolioId, $loan2]);

        $avgRate = $this->portfolioAnalytics->getWeightedAverageRate($portfolioId);
        $this->assertGreaterThan(4.0, $avgRate);
        $this->assertLessThan(6.0, $avgRate);
    }

    public function testGetPortfolioLoanStatus(): void
    {
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Status Test Portfolio',
            'manager_id' => 1,
            'description' => 'Test',
        ]);

        $loan1 = $this->loanRepo->create([
            'loan_number' => 'LOAN004',
            'borrower_id' => 1,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $loan2 = $this->loanRepo->create([
            'loan_number' => 'LOAN005',
            'borrower_id' => 2,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'paid_off',
        ]);

        $this->db->execute('INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)', [$portfolioId, $loan1]);
        $this->db->execute('INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)', [$portfolioId, $loan2]);

        $status = $this->portfolioAnalytics->getPortfolioLoanStatus($portfolioId);
        $this->assertArrayHasKey('active', $status);
        $this->assertEquals(1, $status['active']);
        $this->assertEquals(1, $status['paid_off']);
    }

    public function testGetMonthlyPaymentStats(): void
    {
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Payment Stats Portfolio',
            'manager_id' => 1,
            'description' => 'Test',
        ]);

        $loanId = $this->loanRepo->create([
            'loan_number' => 'LOAN006',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->db->execute('INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)', [$portfolioId, $loanId]);

        // Create multiple payments for January 2024
        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $stats = $this->portfolioAnalytics->getMonthlyPaymentStats($portfolioId, '2024-02');
        $this->assertEquals(1, $stats['payment_count']);
        $this->assertGreaterThan(0, $stats['total_payments']);
    }

    // ==================== Time Series Analytics Tests ====================

    public function testGetLoanPaymentHistory(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'LOAN007',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $history = $this->timeSeriesAnalytics->getLoanPaymentHistory($loanId);
        $this->assertEquals(1, count($history));
        $this->assertEquals('2024-02-01', $history[0]['due_date']);
    }

    public function testGetCumulativeInterestPaid(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'LOAN008',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $cumulative = $this->timeSeriesAnalytics->getCumulativeInterestPaid($loanId);
        $this->assertGreaterThan(0, count($cumulative));
        $this->assertArrayHasKey('cumulative_interest', $cumulative[0]);
    }

    public function testGetPaymentFrequency(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'LOAN009',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $frequency = $this->timeSeriesAnalytics->getPaymentFrequency($loanId, '%Y-%m');
        $this->assertGreaterThan(0, $frequency);
    }

    // ==================== Cohort Analytics Tests ====================

    public function testGetLoansByCohort(): void
    {
        $this->loanRepo->create([
            'loan_number' => 'COHORT001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-15',
            'status' => 'active',
        ]);

        $cohorts = $this->cohortAnalytics->getLoansByCohort('%Y-%m');
        $this->assertGreaterThan(0, count($cohorts));
    }

    public function testGetCohortSurvivalRate(): void
    {
        $this->loanRepo->create([
            'loan_number' => 'SURVIVAL001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $rate = $this->cohortAnalytics->getCohortSurvivalRate('2024-01');
        $this->assertGreaterThanOrEqual(0, $rate);
        $this->assertLessThanOrEqual(100, $rate);
    }

    public function testGetLoansByBorrowerSegment(): void
    {
        $this->loanRepo->create([
            'loan_number' => 'SEG001',
            'borrower_id' => 1,
            'principal' => 30000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->loanRepo->create([
            'loan_number' => 'SEG002',
            'borrower_id' => 2,
            'principal' => 150000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $segments = $this->cohortAnalytics->getLoansByBorrowerSegment();
        $this->assertGreaterThan(0, count($segments));
    }

    // ==================== Predictive Analytics Tests ====================

    public function testPredictRemainingTerm(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'PRED001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        for ($i = 1; $i <= 12; $i++) {
            $this->scheduleRepo->create([
                'loan_id' => $loanId,
                'payment_number' => $i,
                'due_date' => date('Y-m-d', strtotime("+$i months", strtotime('2024-01-01'))),
                'payment_amount' => 1073.64,
                'principal' => 70.56,
                'interest' => 1003.08,
                'balance' => 200000 - ($i * 100),
                'status' => 'pending',
            ]);
        }

        $remaining = $this->predictiveAnalytics->predictRemainingTerm($loanId);
        $this->assertEquals(12, $remaining);
    }

    public function testEstimateTotalInterest(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'INTEREST001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $totalInterest = $this->predictiveAnalytics->estimateTotalInterest($loanId);
        $this->assertGreaterThan(0, $totalInterest);
    }

    public function testEstimateLTV(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'LTV001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $ltv = $this->predictiveAnalytics->estimateLTV($loanId);
        $this->assertGreaterThanOrEqual(0, $ltv);
    }

    public function testPredictDelinquencyRisk(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'DELIN001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $risk = $this->predictiveAnalytics->predictDelinquencyRisk($loanId);
        $this->assertGreaterThanOrEqual(0, $risk);
    }

    public function testEstimatePrepaymentProbability(): void
    {
        $loanId = $this->loanRepo->create([
            'loan_number' => 'PREPAY001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
            'paid_date' => null,
        ]);

        $probability = $this->predictiveAnalytics->estimatePrepaymentProbability($loanId);
        $this->assertGreaterThanOrEqual(0, $probability);
    }

    // ==================== Risk Analytics Tests ====================

    public function testGetConcentrationRisk(): void
    {
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Risk Portfolio',
            'manager_id' => 1,
            'description' => 'Test',
        ]);

        $loan1 = $this->loanRepo->create([
            'loan_number' => 'RISK001',
            'borrower_id' => 1,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->db->execute('INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)', [$portfolioId, $loan1]);

        $risk = $this->riskAnalytics->getConcentrationRisk($portfolioId);
        $this->assertGreaterThanOrEqual(0, $risk);
    }

    public function testGetWeightedDuration(): void
    {
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Duration Portfolio',
            'manager_id' => 1,
            'description' => 'Test',
        ]);

        $loanId = $this->loanRepo->create([
            'loan_number' => 'DUR001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->db->execute('INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)', [$portfolioId, $loanId]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $duration = $this->riskAnalytics->getWeightedDuration($portfolioId);
        $this->assertGreaterThan(0, $duration);
    }

    public function testGetPortfolioYield(): void
    {
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Yield Portfolio',
            'manager_id' => 1,
            'description' => 'Test',
        ]);

        $loanId = $this->loanRepo->create([
            'loan_number' => 'YIELD001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->db->execute('INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)', [$portfolioId, $loanId]);

        $this->scheduleRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $yield = $this->riskAnalytics->getPortfolioYield($portfolioId);
        $this->assertGreaterThan(0, $yield);
    }

    public function testGetLossSeverity(): void
    {
        $portfolioId = $this->portfolioRepo->create([
            'name' => 'Loss Portfolio',
            'manager_id' => 1,
            'description' => 'Test',
        ]);

        $loan = $this->loanRepo->create([
            'loan_number' => 'LOSS001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->db->execute('INSERT INTO portfolio_loans (portfolio_id, loan_id) VALUES (?, ?)', [$portfolioId, $loan]);

        $loss = $this->riskAnalytics->getLossSeverity($portfolioId);
        $this->assertGreaterThanOrEqual(0, $loss);
    }
}
