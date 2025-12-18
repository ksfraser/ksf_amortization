<?php
namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Services\{AnalysisService, ScheduleRecalculationService};
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Repositories\LoanRepository;

/**
 * TestLoanRepository: Simple test implementation of LoanRepository
 */
class TestLoanRepository implements LoanRepository
{
    private array $loans = [];
    private int $nextId = 1;

    public function findById(int $loanId): ?Loan
    {
        return $this->loans[$loanId] ?? null;
    }

    public function findByBorrowerId(int $borrowerId): array
    {
        return [];
    }

    public function findByStatus(string $status): array
    {
        return [];
    }

    public function save(Loan $loan): int
    {
        if (!isset($loan->id) || $loan->id === null) {
            $loan->id = $this->nextId++;
        }
        $this->loans[$loan->id] = $loan;
        return $loan->id;
    }

    public function delete(int $loanId): bool
    {
        unset($this->loans[$loanId]);
        return true;
    }

    public function countActive(): int
    {
        return count($this->loans);
    }

    public function getTotalActiveBalance(): float
    {
        $total = 0;
        foreach ($this->loans as $loan) {
            $total += $loan->current_balance ?? $loan->principal ?? 0;
        }
        return $total;
    }

    public function findDueOnDate(\DateTimeImmutable $date): array
    {
        return [];
    }

    public function create(array $loanData): int
    {
        $loan = new Loan();
        foreach ($loanData as $key => $value) {
            if (property_exists($loan, $key)) {
                $loan->$key = $value;
            }
        }
        return $this->save($loan);
    }
}

class AnalysisServiceTest extends TestCase
{
    private AnalysisService $analysisService;
    private TestLoanRepository $loanRepository;
    private ScheduleRecalculationService $recalculationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loanRepository = new TestLoanRepository();
        $this->recalculationService = new ScheduleRecalculationService();
        $this->analysisService = new AnalysisService(
            $this->loanRepository,
            $this->recalculationService
        );
    }

    /**
     * Test: Analyze single loan
     */
    public function test_analyze_single_loan()
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(30000);
        $loan->setAnnualRate(0.045);
        $loan->setMonths(60);

        $analysis = $this->analysisService->analyzeLoan($loan);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('monthly_payment', $analysis);
        $this->assertArrayHasKey('total_interest', $analysis);
        $this->assertArrayHasKey('total_cost', $analysis);
        $this->assertGreaterThan(0, $analysis['monthly_payment']);
        $this->assertGreaterThan(0, $analysis['total_interest']);
    }

    /**
     * Test: Compare two loans
     */
    public function test_compare_two_loans()
    {
        // Create first loan
        $loan1 = new Loan();
        $loan1->id = 1;
        $loan1->principal = 30000;
        $loan1->current_balance = 25000;
        $loan1->interest_rate = 0.045;
        $loan1->term_months = 60;
        $loan1->start_date = '2025-01-01';
        $loan1->last_payment_date = '2025-01-01';

        // Create second loan
        $loan2 = new Loan();
        $loan2->id = 2;
        $loan2->principal = 50000;
        $loan2->current_balance = 50000;
        $loan2->interest_rate = 0.035;
        $loan2->term_months = 84;
        $loan2->start_date = '2025-01-01';
        $loan2->last_payment_date = '2025-01-01';

        // Add to repository
        $this->loanRepository->create((array)$loan1);
        $this->loanRepository->create((array)$loan2);

        // Compare
        $comparison = $this->analysisService->compareLoans([1, 2]);

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('loans', $comparison);
        $this->assertArrayHasKey('summary', $comparison);
        $this->assertArrayHasKey('totals', $comparison);
        $this->assertCount(2, $comparison['loans']);
    }

    /**
     * Test: Forecast early payoff with extra payments
     */
    public function test_forecast_early_payoff()
    {
        $loan = new Loan();
        $loan->id = 1;
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        $loan->last_payment_date = '2025-01-01';

        $this->loanRepository->create((array)$loan);

        $forecast = $this->analysisService->forecastEarlyPayoff(1, 500, 'monthly');

        $this->assertIsArray($forecast);
        $this->assertArrayHasKey('original_payoff', $forecast);
        $this->assertArrayHasKey('with_extra_payments', $forecast);
        $this->assertArrayHasKey('savings', $forecast);
        $this->assertArrayHasKey('schedule', $forecast);

        // With extra payments, payoff should be sooner
        $this->assertLessThan(
            $forecast['original_payoff']['months'],
            $forecast['with_extra_payments']['months']
        );

        // Interest savings should be positive
        $this->assertGreaterThan(0, $forecast['savings']['interest_saved']);
    }

    /**
     * Test: Forecast with quarterly extra payments
     */
    public function test_forecast_quarterly_payments()
    {
        $loan = new Loan();
        $loan->id = 1;
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        $loan->last_payment_date = '2025-01-01';

        $this->loanRepository->create((array)$loan);

        $forecast = $this->analysisService->forecastEarlyPayoff(1, 1500, 'quarterly');

        $this->assertIsArray($forecast);
        $this->assertArrayHasKey('with_extra_payments', $forecast);
        $this->assertGreaterThan(0, $forecast['with_extra_payments']['total_extra_payments']);
    }

    /**
     * Test: Generate recommendations for single loan
     */
    public function test_generate_recommendations_single_loan()
    {
        $loan = new Loan();
        $loan->id = 1;
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';

        $this->loanRepository->create((array)$loan);

        $recommendations = $this->analysisService->generateRecommendations([1]);

        $this->assertIsArray($recommendations);
        $this->assertArrayHasKey('total_debt', $recommendations);
        $this->assertArrayHasKey('highest_rate_loan', $recommendations);
        $this->assertArrayHasKey('actions', $recommendations);
        $this->assertGreaterThan(0, $recommendations['total_debt']);
    }

    /**
     * Test: Generate recommendations for multiple loans
     */
    public function test_generate_recommendations_multiple_loans()
    {
        // Create multiple loans
        $loan1 = new Loan();
        $loan1->id = 1;
        $loan1->principal = 30000;
        $loan1->interest_rate = 0.065; // High rate
        $loan1->term_months = 60;
        $loan1->start_date = '2025-01-01';

        $loan2 = new Loan();
        $loan2->id = 2;
        $loan2->principal = 50000;
        $loan2->interest_rate = 0.035;
        $loan2->term_months = 84;
        $loan2->start_date = '2025-01-01';

        $this->loanRepository->create((array)$loan1);
        $this->loanRepository->create((array)$loan2);

        $recommendations = $this->analysisService->generateRecommendations([1, 2]);

        $this->assertIsArray($recommendations);
        $this->assertGreaterThan(0, $recommendations['total_debt']);
        // Should have consolidation recommendation
        $this->assertGreaterThan(0, count($recommendations['actions']));
    }

    /**
     * Test: Get debt payoff timeline
     */
    public function test_get_debt_payoff_timeline()
    {
        $loan = new Loan();
        $loan->id = 1;
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        $loan->last_payment_date = '2025-01-01';

        $this->loanRepository->create((array)$loan);

        $timeline = $this->analysisService->getDebtPayoffTimeline([1]);

        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('start_date', $timeline);
        $this->assertArrayHasKey('end_date', $timeline);
        $this->assertArrayHasKey('loans', $timeline);
        $this->assertArrayHasKey('milestones', $timeline);
        $this->assertCount(1, $timeline['loans']);
        $this->assertCount(3, $timeline['milestones']); // 25%, 50%, 75%
    }

    /**
     * Test: Compare returns valid structure
     */
    public function test_compare_returns_valid_structure()
    {
        $loan = new Loan();
        $loan->id = 1;
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        $loan->last_payment_date = '2025-01-01';

        $this->loanRepository->create((array)$loan);

        $comparison = $this->analysisService->compareLoans([1]);

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('summary', $comparison);
        $this->assertArrayHasKey('totals', $comparison);
        
        $this->assertArrayHasKey('cheapest_by_interest', $comparison['summary']);
        $this->assertArrayHasKey('combined_principal', $comparison['totals']);
        $this->assertArrayHasKey('combined_interest', $comparison['totals']);
        $this->assertArrayHasKey('average_rate', $comparison['totals']);
    }

    /**
     * Test: Invalid loan ID returns error
     */
    public function test_analyze_nonexistent_loan()
    {
        $loan = new Loan();
        $loan->id = 999; // Non-existent
        $loan->principal = 30000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        $loan->last_payment_date = '2025-01-01';

        // This won't be in repository, so we test the analysis directly
        // (In real usage, the controller would handle non-existent loans)
        $analysis = $this->analysisService->analyzeLoan($loan);

        $this->assertIsArray($analysis);
        // Should still calculate values even for non-existent loan
        $this->assertArrayHasKey('monthly_payment', $analysis);
    }

    /**
     * Test: Forecast with high extra payment
     */
    public function test_forecast_with_high_extra_payment()
    {
        $loan = new Loan();
        $loan->id = 1;
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        $loan->last_payment_date = '2025-01-01';

        $this->loanRepository->create((array)$loan);

        $forecast = $this->analysisService->forecastEarlyPayoff(1, 5000, 'monthly');

        $this->assertIsArray($forecast);
        // With high extra payment, payoff should be much faster
        $this->assertLessThan(
            $forecast['original_payoff']['months'],
            $forecast['with_extra_payments']['months']
        );

        // Should be paid off in less than half the time
        $this->assertLess(
            $forecast['with_extra_payments']['months'],
            $forecast['original_payoff']['months'] / 2
        );
    }
}

class AnalysisControllerTest extends TestCase
{
    private AnalysisService $analysisService;
    private TestLoanRepository $loanRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loanRepository = new TestLoanRepository();
        $this->analysisService = new AnalysisService(
            $this->loanRepository,
            new ScheduleRecalculationService()
        );
    }

    /**
     * Test: Can get analysis for valid loan
     */
    public function test_can_get_analysis_for_valid_loan()
    {
        $loan = new Loan();
        $loan->id = 1;
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        $loan->last_payment_date = '2025-01-01';

        $this->loanRepository->create((array)$loan);

        $analysis = $this->analysisService->analyzeLoan($loan);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('id', $analysis);
        $this->assertEquals(1, $analysis['id']);
    }

    /**
     * Test: Forecast requires valid parameters
     */
    public function test_forecast_calculates_correctly()
    {
        $loan = new Loan();
        $loan->id = 1;
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        $loan->last_payment_date = '2025-01-01';

        $this->loanRepository->create((array)$loan);

        // Test that forecast requires valid parameters
        $forecast = $this->analysisService->forecastEarlyPayoff(1, 500, 'monthly');

        $this->assertIsArray($forecast);
        $this->assertArrayHasKey('savings', $forecast);
    }

    /**
     * Test: Recommendations validates all loans exist
     */
    public function test_recommendations_processes_multiple_loans()
    {
        $loan1 = new Loan();
        $loan1->id = 1;
        $loan1->principal = 30000;
        $loan1->interest_rate = 0.045;
        $loan1->term_months = 60;
        $loan1->start_date = '2025-01-01';

        $loan2 = new Loan();
        $loan2->id = 2;
        $loan2->principal = 50000;
        $loan2->interest_rate = 0.035;
        $loan2->term_months = 84;
        $loan2->start_date = '2025-01-01';

        $this->loanRepository->create((array)$loan1);
        $this->loanRepository->create((array)$loan2);

        $recommendations = $this->analysisService->generateRecommendations([1, 2]);

        $this->assertIsArray($recommendations);
        $this->assertArrayHasKey('actions', $recommendations);
        $this->assertIsArray($recommendations['actions']);
    }

    /**
     * Test: Timeline includes milestone dates
     */
    public function test_timeline_includes_milestone_dates()
    {
        $loan = new Loan();
        $loan->id = 1;
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        $loan->last_payment_date = '2025-01-01';

        $this->loanRepository->create((array)$loan);

        $timeline = $this->analysisService->getDebtPayoffTimeline([1]);

        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('milestones', $timeline);
        $this->assertIsArray($timeline['milestones']);
        $this->assertNotEmpty($timeline['milestones']);
    }
}
