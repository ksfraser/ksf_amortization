<?php

namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\EarlyPayoffOptimizer;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class EarlyPayoffOptimizerTest extends TestCase
{
    private EarlyPayoffOptimizer $optimizer;
    private Loan $loan;

    protected function setUp(): void
    {
        $this->optimizer = new EarlyPayoffOptimizer();
        $this->loan = $this->createTestLoan();
    }

    private function createTestLoan(): Loan
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(200000.00);
        $loan->setAnnualRate(0.04);
        $loan->setMonths(360);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(195000.00);
        return $loan;
    }

    /**
     * Test 1: Calculate extra monthly payment needed for target payoff
     */
    public function testCalculateExtraMonthlyPaymentForTargetPayoff()
    {
        $extraPayment = $this->optimizer->calculateExtraMonthlyPaymentForTargetPayoff(
            $this->loan,
            240  // 20 years instead of 30
        );

        $this->assertGreaterThan(0, $extraPayment);
        $this->assertLessThan(500, $extraPayment);  // Should be reasonable for 10-year acceleration
    }

    /**
     * Test 2: Calculate payoff date with extra payments
     */
    public function testCalculatePayoffDateWithExtraPayments()
    {
        $payoffDate = $this->optimizer->calculatePayoffDateWithExtraPayments(
            $this->loan,
            200.00  // $200 extra per month
        );

        $this->assertIsString($payoffDate);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $payoffDate);
        // Should be sooner than 30 years
        $this->assertLessThan(360, $this->countMonthsBetween('2024-01-01', $payoffDate));
    }

    /**
     * Test 3: Analyze lump-sum payment scenarios
     */
    public function testAnalyzeLumpSumPaymentScenarios()
    {
        $scenarios = $this->optimizer->analyzeLumpSumPaymentScenarios(
            $this->loan,
            [10000, 25000, 50000]
        );

        $this->assertIsArray($scenarios);
        $this->assertCount(3, $scenarios);
        $this->assertArrayHasKey('lump_sum', $scenarios[0]);
        $this->assertArrayHasKey('payoff_date', $scenarios[0]);
        $this->assertArrayHasKey('interest_saved', $scenarios[0]);
    }

    /**
     * Test 4: Run Monte Carlo simulation for variable rates
     */
    public function testRunMonteCarloSimulation()
    {
        $simulation = $this->optimizer->runMonteCarloSimulation(
            $this->loan,
            0.04,      // Base rate
            0.02,      // Rate volatility (±2%)
            100,       // Number of simulations
            0.03       // Payoff strategy (3% extra payment)
        );

        $this->assertIsArray($simulation);
        $this->assertArrayHasKey('mean_payoff_months', $simulation);
        $this->assertArrayHasKey('std_dev_payoff_months', $simulation);
        $this->assertArrayHasKey('scenarios', $simulation);
        $this->assertGreaterThan(0, count($simulation['scenarios']));
    }

    /**
     * Test 5: Generate payoff optimization recommendation
     */
    public function testGeneratePayoffOptimizationRecommendation()
    {
        $recommendation = $this->optimizer->generatePayoffOptimizationRecommendation(
            $this->loan,
            150000  // Annual income
        );

        $this->assertIsArray($recommendation);
        $this->assertArrayHasKey('recommended_strategy', $recommendation);
        $this->assertArrayHasKey('rationale', $recommendation);
        $this->assertArrayHasKey('scenarios', $recommendation);
    }

    /**
     * Test 6: Calculate interest saved with accelerated payoff
     */
    public function testCalculateInterestSavedWithAcceleratedPayoff()
    {
        $interestSaved = $this->optimizer->calculateInterestSavedWithAcceleratedPayoff(
            $this->loan,
            300.00,  // Extra payment per month
            0.04
        );

        $this->assertGreaterThan(0, $interestSaved);
    }

    /**
     * Test 7: Plan lump-sum payment strategy
     */
    public function testPlanLumpSumPaymentStrategy()
    {
        $plan = $this->optimizer->planLumpSumPaymentStrategy(
            $this->loan,
            [
                'year_1' => 15000,
                'year_3' => 20000,
                'year_5' => 25000,
            ],
            0.04
        );

        $this->assertIsArray($plan);
        $this->assertArrayHasKey('total_lump_sums', $plan);
        $this->assertArrayHasKey('total_interest_saved', $plan);
        $this->assertArrayHasKey('final_payoff_date', $plan);
        $this->assertEquals(60000, $plan['total_lump_sums']);
    }

    /**
     * Test 8: Analyze tax implications of payoff strategy
     */
    public function testAnalyzeTaxImplications()
    {
        $taxAnalysis = $this->optimizer->analyzeTaxImplications(
            $this->loan,
            250.00,  // Extra payment
            0.04,
            150000   // Income
        );

        $this->assertIsArray($taxAnalysis);
        $this->assertArrayHasKey('lost_interest_deduction', $taxAnalysis);
        $this->assertArrayHasKey('tax_impact', $taxAnalysis);
        $this->assertArrayHasKey('net_benefit', $taxAnalysis);
    }

    /**
     * Test 9: Generate payoff comparison chart data
     */
    public function testGeneratePayoffComparisonChartData()
    {
        $chartData = $this->optimizer->generatePayoffComparisonChartData(
            $this->loan,
            [100, 200, 300, 500]  // Different extra payment amounts
        );

        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('strategies', $chartData);
        $this->assertCount(4, $chartData['strategies']);
    }

    /**
     * Test 10: Calculate minimum monthly extra payment for goal
     */
    public function testCalculateMinimumExtraPaymentForGoal()
    {
        $minPayment = $this->optimizer->calculateMinimumExtraPaymentForGoal(
            $this->loan,
            120  // Goal: 10 years (120 months vs 360)
        );

        $this->assertGreaterThan(0, $minPayment);
        $this->assertLessThan(5000, $minPayment);  // Should be reasonable
    }

    /**
     * Test 11: Analyze early payoff scenarios with inflation
     */
    public function testAnalyzeEarlyPayoffWithInflation()
    {
        $inflationAnalysis = $this->optimizer->analyzeEarlyPayoffWithInflation(
            $this->loan,
            250.00,  // Extra payment
            0.03,    // Inflation rate
            0.04
        );

        $this->assertIsArray($inflationAnalysis);
        $this->assertArrayHasKey('nominal_payoff_date', $inflationAnalysis);
        $this->assertArrayHasKey('real_payoff_cost', $inflationAnalysis);
        $this->assertArrayHasKey('inflation_impact', $inflationAnalysis);
    }

    /**
     * Test 12: Export payoff plan to JSON
     */
    public function testExportPayoffPlanToJSON()
    {
        $plan = $this->optimizer->planLumpSumPaymentStrategy(
            $this->loan,
            ['year_1' => 15000],
            0.04
        );

        $json = $this->optimizer->exportToJSON($plan);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('total_lump_sums', $decoded);
    }

    private function countMonthsBetween(string $start, string $end): int
    {
        $startDate = new DateTimeImmutable($start);
        $endDate = new DateTimeImmutable($end);
        $interval = $startDate->diff($endDate);
        return $interval->m + ($interval->y * 12);
    }
}
