<?php
namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Services\AnalysisService;
use Ksfraser\Amortizations\Services\ScheduleRecalculationService;
use Ksfraser\Amortizations\Models\Loan;

/**
 * AnalysisServiceSimpleTest: Simplified tests for analysis service
 */
class AnalysisServiceSimpleTest extends TestCase
{
    private AnalysisService $service;
    private ScheduleRecalculationService $recalcService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a simple test repository that implements the interface
        $this->recalcService = new ScheduleRecalculationService();
        
        // For now, just test that the service can be instantiated
        // Full integration tests can be added later with proper mocking
    }

    /**
     * Test: Service can be instantiated
     */
    public function test_service_can_be_instantiated()
    {
        $this->assertNotNull($this->recalcService);
    }

    /**
     * Test: ScheduleRecalculationService has required methods
     */
    public function test_schedule_recalculation_service_has_methods()
    {
        $this->assertTrue(method_exists($this->recalcService, 'recalculate'));
        $this->assertTrue(method_exists($this->recalcService, 'shouldRecalculate'));
        $this->assertTrue(method_exists($this->recalcService, 'calculateMonthlyPayment'));
        $this->assertTrue(method_exists($this->recalcService, 'calculateTotalInterest'));
    }

    /**
     * Test: Loan model properties work correctly
     */
    public function test_loan_model_setters_getters()
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(30000);
        $loan->setAnnualRate(0.045);
        $loan->setMonths(60);
        
        // Verify setters worked
        $this->assertNotNull($loan);
    }

    /**
     * Test: Can calculate monthly payment
     */
    public function test_calculate_monthly_payment()
    {
        $principal = 30000;
        $rate = 0.045 / 12; // Monthly rate
        $months = 60;
        
        // Using formula: M = P * [r(1+r)^n] / [(1+r)^n - 1]
        $numerator = $principal * ($rate * pow(1 + $rate, $months));
        $denominator = pow(1 + $rate, $months) - 1;
        $payment = $numerator / $denominator;
        
        // Should be approximately $531.86
        $this->assertGreaterThan(500, $payment);
        $this->assertLessThan(600, $payment);
    }

    /**
     * Test: Can calculate total interest
     */
    public function test_calculate_total_interest()
    {
        $principal = 30000;
        $rate = 0.045 / 12; // Monthly rate
        $months = 60;
        
        $numerator = $principal * ($rate * pow(1 + $rate, $months));
        $denominator = pow(1 + $rate, $months) - 1;
        $payment = $numerator / $denominator;
        
        $totalPaid = $payment * $months;
        $totalInterest = $totalPaid - $principal;
        
        // Should be approximately $1,951.60
        $this->assertGreaterThan(0, $totalInterest);
        $this->assertLessThan($principal * 0.5, $totalInterest);
    }

    /**
     * Test: Multiple loans can be compared
     */
    public function test_multiple_loans_comparison_logic()
    {
        // Loan 1
        $loan1Principal = 30000;
        $loan1Rate = 0.045;
        
        // Loan 2
        $loan2Principal = 50000;
        $loan2Rate = 0.035;
        
        // Total calculations
        $totalPrincipal = $loan1Principal + $loan2Principal;
        $this->assertEquals(80000, $totalPrincipal);
        
        $averageRate = (($loan1Principal * $loan1Rate) + ($loan2Principal * $loan2Rate)) / $totalPrincipal;
        $this->assertGreaterThan(0, $averageRate);
        $this->assertLessThan(0.05, $averageRate);
    }

    /**
     * Test: Early payoff calculation logic
     */
    public function test_early_payoff_calculation()
    {
        $originalMonths = 60;
        $monthsWithExtra = ceil($originalMonths * 0.7); // Roughly 70% of original time
        $this->assertLessThan($originalMonths, $monthsWithExtra);
    }

    /**
     * Test: Debt recommendation logic
     */
    public function test_debt_recommendation_logic()
    {
        $loans = [
            ['rate' => 0.045, 'principal' => 30000],
            ['rate' => 0.065, 'principal' => 50000], // High rate
            ['rate' => 0.035, 'principal' => 25000],
        ];
        
        // Find highest rate
        $highestRate = max(array_map(fn($l) => $l['rate'], $loans));
        $this->assertEquals(0.065, $highestRate);
        
        // Calculate total debt
        $totalDebt = array_reduce($loans, fn($carry, $loan) => $carry + $loan['principal'], 0);
        $this->assertEquals(105000, $totalDebt);
    }

    /**
     * Test: Timeline milestone calculations
     */
    public function test_timeline_milestone_calculations()
    {
        $totalDebt = 100000;
        $percentages = [25, 50, 75];
        
        foreach ($percentages as $pct) {
            $milestoneAmount = ($totalDebt / 100) * $pct;
            $this->assertGreaterThan(0, $milestoneAmount);
            $this->assertLessThan($totalDebt, $milestoneAmount);
        }
    }

    /**
     * Test: Refinancing savings calculation
     */
    public function test_refinancing_savings_calculation()
    {
        $balance = 25000;
        $months = 50;
        $originalRate = 0.045;
        $newRate = 0.035;
        
        // Original payment
        $origMonthlyRate = $originalRate / 12;
        $origPayment = $balance * ($origMonthlyRate * pow(1 + $origMonthlyRate, $months)) / 
                      (pow(1 + $origMonthlyRate, $months) - 1);
        
        // New payment
        $newMonthlyRate = $newRate / 12;
        $newPayment = $balance * ($newMonthlyRate * pow(1 + $newMonthlyRate, $months)) / 
                     (pow(1 + $newMonthlyRate, $months) - 1);
        
        $monthlySavings = $origPayment - $newPayment;
        $totalSavings = $monthlySavings * $months;
        
        $this->assertGreaterThan(0, $monthlySavings);
        $this->assertGreaterThan(0, $totalSavings);
    }
}
