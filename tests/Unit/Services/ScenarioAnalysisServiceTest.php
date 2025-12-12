<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\ScenarioAnalysisService;
use DateTimeImmutable;

/**
 * ScenarioAnalysisServiceTest - TDD Test Suite
 *
 * Tests for the ScenarioAnalysisService which provides what-if analysis
 * and scenario modeling capabilities without modifying the actual loan.
 *
 * Responsibilities:
 * - Create temporary scenarios with modifications
 * - Calculate modified amortization schedules
 * - Compare scenarios side-by-side
 * - Calculate total interest, payoff dates, savings
 * - Store favorite scenarios in session/cache
 * - Support multiple strategy types (extra payment, accelerated, etc.)
 *
 * Test coverage: 14 tests
 * - Scenario creation (2 tests)
 * - Extra payment scenarios (2 tests)
 * - Accelerated payment scenarios (2 tests)
 * - Scenario comparison (2 tests)
 * - Savings calculation (2 tests)
 * - Favorite scenarios (2 tests)
 * - Edge cases (2 tests)
 */
class ScenarioAnalysisServiceTest extends TestCase
{
    private $scenarioService;

    protected function setUp(): void
    {
        $this->scenarioService = new ScenarioAnalysisService();
    }

    /**
     * Test 1: Create basic scenario with extra monthly payment
     */
    public function testCreateScenarioWithExtraMonthlyPayment()
    {
        $loan = $this->createTestLoan();
        // $10,000 @ 5% over 60 months = ~$188.71/month

        $scenario = $this->scenarioService->createScenario(
            $loan,
            'extra_payment_100',
            ['extra_monthly_payment' => 100.00]
        );

        $this->assertIsArray($scenario);
        $this->assertEquals('extra_payment_100', $scenario['name']);
        $this->assertEquals(100.00, $scenario['modifications']['extra_monthly_payment']);
        $this->assertArrayHasKey('base_loan_id', $scenario);
        $this->assertEquals($loan->getId(), $scenario['base_loan_id']);
    }

    /**
     * Test 2: Create scenario with lump-sum extra payment at specific month
     */
    public function testCreateScenarioWithLumpSumPayment()
    {
        $loan = $this->createTestLoan();

        $scenario = $this->scenarioService->createScenario(
            $loan,
            'lump_sum_month_12',
            [
                'lump_sum_payment' => 1000.00,
                'lump_sum_month' => 12,
            ]
        );

        $this->assertEquals(1000.00, $scenario['modifications']['lump_sum_payment']);
        $this->assertEquals(12, $scenario['modifications']['lump_sum_month']);
    }

    /**
     * Test 3: Calculate schedule for scenario with extra monthly payment
     */
    public function testCalculateScheduleWithExtraMonthlyPayment()
    {
        $loan = $this->createTestLoan();

        $scenario = $this->scenarioService->createScenario(
            $loan,
            'extra_100',
            ['extra_monthly_payment' => 100.00]
        );

        $schedule = $this->scenarioService->generateScenarioSchedule($scenario, $loan);

        // With extra $100/month, loan should pay off faster
        $this->assertIsArray($schedule);
        $this->assertArrayHasKey('periods', $schedule);
        $this->assertLessThan(60, count($schedule['periods']));  // Fewer periods
        $this->assertGreaterThan(30, count($schedule['periods']));  // But still significant
    }

    /**
     * Test 4: Calculate schedule for scenario with lump-sum payment
     */
    public function testCalculateScheduleWithLumpSumPayment()
    {
        $loan = $this->createTestLoan();

        $scenario = $this->scenarioService->createScenario(
            $loan,
            'lump_1000',
            [
                'lump_sum_payment' => 1000.00,
                'lump_sum_month' => 12,
            ]
        );

        $schedule = $this->scenarioService->generateScenarioSchedule($scenario, $loan);

        // Lump sum should also reduce payoff term
        $this->assertLessThan(60, count($schedule['periods']));
        $this->assertArrayHasKey('periods', $schedule);
    }

    /**
     * Test 5: Create accelerated bi-weekly payment scenario
     */
    public function testCreateAcceleratedBiWeeklyScenario()
    {
        $loan = $this->createTestLoan();
        // Standard: $188.71/month * 12 = $2264.52/year
        // Bi-weekly: $188.71/2 = $94.36 every 2 weeks = 26 payments/year = $2453.36/year

        $scenario = $this->scenarioService->createScenario(
            $loan,
            'accelerated_biweekly',
            ['payment_frequency' => 'bi-weekly']
        );

        $this->assertEquals('bi-weekly', $scenario['modifications']['payment_frequency']);
    }

    /**
     * Test 6: Create accelerated weekly payment scenario
     */
    public function testCreateAcceleratedWeeklyScenario()
    {
        $loan = $this->createTestLoan();

        $scenario = $this->scenarioService->createScenario(
            $loan,
            'accelerated_weekly',
            ['payment_frequency' => 'weekly']
        );

        $this->assertEquals('weekly', $scenario['modifications']['payment_frequency']);
    }

    /**
     * Test 7: Compare two scenarios side-by-side
     */
    public function testCompareScenariosSideBySide()
    {
        $loan = $this->createTestLoan();

        $scenario1 = $this->scenarioService->createScenario(
            $loan,
            'baseline',
            []
        );

        $scenario2 = $this->scenarioService->createScenario(
            $loan,
            'extra_100',
            ['extra_monthly_payment' => 100.00]
        );

        $schedule1 = $this->scenarioService->generateScenarioSchedule($scenario1, $loan);
        $schedule2 = $this->scenarioService->generateScenarioSchedule($scenario2, $loan);

        $comparison = $this->scenarioService->compareScenarios($scenario1, $schedule1, $scenario2, $schedule2);

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('scenario1_name', $comparison);
        $this->assertArrayHasKey('scenario2_name', $comparison);
        $this->assertArrayHasKey('scenario1_periods', $comparison);
        $this->assertArrayHasKey('scenario2_periods', $comparison);
        $this->assertLessThan(
            $comparison['scenario1_periods'],
            $comparison['scenario2_periods']
        );
    }

    /**
     * Test 8: Calculate interest savings between scenarios
     */
    public function testCalculateInterestSavingsBetweenScenarios()
    {
        $loan = $this->createTestLoan();

        $scenario1 = $this->scenarioService->createScenario($loan, 'baseline', []);
        $scenario2 = $this->scenarioService->createScenario(
            $loan,
            'extra_200',
            ['extra_monthly_payment' => 200.00]
        );

        $schedule1 = $this->scenarioService->generateScenarioSchedule($scenario1, $loan);
        $schedule2 = $this->scenarioService->generateScenarioSchedule($scenario2, $loan);

        $comparison = $this->scenarioService->compareScenarios($scenario1, $schedule1, $scenario2, $schedule2);

        // Scenario with extra payments should save interest
        $this->assertArrayHasKey('interest_saved', $comparison);
        $this->assertGreaterThan(0, $comparison['interest_saved']);
    }

    /**
     * Test 9: Calculate total interest for scenario
     */
    public function testCalculateTotalInterestForScenario()
    {
        $loan = $this->createTestLoan();

        $scenario = $this->scenarioService->createScenario(
            $loan,
            'baseline',
            []
        );

        $schedule = $this->scenarioService->generateScenarioSchedule($scenario, $loan);
        $totalInterest = $this->scenarioService->calculateTotalInterest($schedule);

        // For $10k @ 5% over 60 months, interest should be ~$1,323
        $this->assertGreaterThan(1200, $totalInterest);
        $this->assertLessThan(1500, $totalInterest);
    }

    /**
     * Test 10: Calculate payoff date for scenario
     */
    public function testCalculatePayoffDateForScenario()
    {
        $loan = $this->createTestLoan();

        $scenario = $this->scenarioService->createScenario(
            $loan,
            'extra_150',
            ['extra_monthly_payment' => 150.00]
        );

        $schedule = $this->scenarioService->generateScenarioSchedule($scenario, $loan);
        $payoffDate = $this->scenarioService->calculatePayoffDate($schedule);

        $this->assertNotNull($payoffDate);
        // Should payoff in less than 5 years (60 months)
        $this->assertInstanceOf(DateTimeImmutable::class, $payoffDate);
    }

    /**
     * Test 11: Save scenario as favorite
     */
    public function testSaveScenarioAsFavorite()
    {
        $loan = $this->createTestLoan();

        $scenario = $this->scenarioService->createScenario(
            $loan,
            'favorite_extra_100',
            ['extra_monthly_payment' => 100.00]
        );

        $saved = $this->scenarioService->saveAsFavorite($scenario, $loan->getId());

        $this->assertTrue($saved);
        $this->assertTrue($scenario['is_favorite']);
    }

    /**
     * Test 12: Retrieve favorite scenarios for loan
     */
    public function testRetrieveFavoriteScenarios()
    {
        $loan = $this->createTestLoan();

        $scenario1 = $this->scenarioService->createScenario(
            $loan,
            'fav_1',
            ['extra_monthly_payment' => 100.00]
        );
        $this->scenarioService->saveAsFavorite($scenario1, $loan->getId());

        $scenario2 = $this->scenarioService->createScenario(
            $loan,
            'fav_2',
            ['extra_monthly_payment' => 200.00]
        );
        $this->scenarioService->saveAsFavorite($scenario2, $loan->getId());

        $favorites = $this->scenarioService->getFavoriteScenarios($loan->getId());

        $this->assertGreaterThanOrEqual(2, count($favorites));
        $this->assertEquals('fav_1', $favorites[0]['name']);
    }

    /**
     * Test 13: Delete scenario (remove from favorites)
     */
    public function testDeleteScenario()
    {
        $loan = $this->createTestLoan();

        $scenario = $this->scenarioService->createScenario(
            $loan,
            'temp_scenario',
            ['extra_monthly_payment' => 100.00]
        );

        $this->scenarioService->saveAsFavorite($scenario, $loan->getId());

        $deleted = $this->scenarioService->deleteScenario($scenario['id']);
        $this->assertTrue($deleted);

        // Verify it's no longer in favorites
        $favorites = $this->scenarioService->getFavoriteScenarios($loan->getId());
        $this->assertEmpty(array_filter($favorites, fn($s) => $s['id'] === $scenario['id']));
    }

    /**
     * Test 14: Multiple strategies comparison matrix
     */
    public function testCompareMultipleStrategies()
    {
        $loan = $this->createTestLoan();

        $strategies = [
            ['name' => 'baseline', 'modifications' => []],
            ['name' => 'extra_100', 'modifications' => ['extra_monthly_payment' => 100.00]],
            ['name' => 'extra_200', 'modifications' => ['extra_monthly_payment' => 200.00]],
        ];

        $matrix = $this->scenarioService->compareMultipleStrategies($loan, $strategies);

        $this->assertIsArray($matrix);
        $this->assertCount(3, $matrix);
        
        // Verify decreasing interest trend
        $interest0 = $matrix[0]['total_interest'];
        $interest1 = $matrix[1]['total_interest'];
        $interest2 = $matrix[2]['total_interest'];
        
        $this->assertGreaterThan($interest1, $interest0);
        $this->assertGreaterThan($interest2, $interest1);
    }

    // ============ Helper Methods ============

    private function createTestLoan(): Loan
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(10000.00);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(10000.00);
        return $loan;
    }
}
