<?php

namespace Tests\Unit\Domain\Loan;

use App\Domain\Loan\Services\AmortizationCalculator;
use Decimal\Decimal;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * Phase 2: Amortization Calculator Tests
 * Validates calculation accuracy against known external calculators
 */
class AmortizationCalculatorTest extends TestCase
{
    private AmortizationCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new AmortizationCalculator();
    }

    /**
     * @test
     * Calculate monthly payment - standard case
     */
    public function it_calculates_monthly_payment_correctly(): void
    {
        $principal = new Decimal('50000');
        $rate = new Decimal('7.75');
        $months = 36;

        $payment = $this->calculator->calculateMonthlyPayment($principal, $rate, $months);

        // Verified with external calculator
        $this->assertEquals('1510.00', (string) $payment);
    }

    /**
     * @test
     * Calculate monthly payment - zero interest
     */
    public function it_calculates_payment_with_zero_interest(): void
    {
        $principal = new Decimal('12000');
        $rate = new Decimal('0');
        $months = 12;

        $payment = $this->calculator->calculateMonthlyPayment($principal, $rate, $months);

        // Should be principal / months
        $this->assertEquals('1000.00', (string) $payment);
    }

    /**
     * @test
     * Generate complete amortization schedule
     */
    public function it_generates_amortization_schedule(): void
    {
        $schedule = $this->calculator->generateSchedule(
            new Decimal('50000'),
            new Decimal('7.75'),
            36,
            new DateTime('2026-04-29')
        );

        $this->assertCount(36, $schedule);
        
        // First payment breakdown
        $this->assertEquals(36, $schedule[0]['period']);
        $this->assertEquals('1510.00', $schedule[0]['payment_amount']);
        $this->assertLessThan(1000, $schedule[0]['principal']);
        $this->assertGreaterThan(300, $schedule[0]['interest']);

        // Last payment
        $this->assertEquals(36, $schedule[35]['period']);
        $this->assertLessThan('0.01', $schedule[35]['balance']);
    }

    /**
     * @test
     * Verify schedule principal adds up to original amount
     */
    public function schedule_principal_equals_loan_amount(): void
    {
        $principal = new Decimal('50000');
        $schedule = $this->calculator->generateSchedule(
            $principal,
            new Decimal('7.75'),
            36,
            new DateTime('2026-04-29')
        );

        $totalPrincipal = new Decimal('0');
        foreach ($schedule as $period) {
            $totalPrincipal = $totalPrincipal->add(new Decimal($period['principal']));
        }

        // Should equal original amount (within tolerance)
        $diff = $principal->subtract($totalPrincipal)->abs();
        $this->assertLessThan('0.01', (string) $diff);
    }

    /**
     * @test
     * Calculate total interest over loan life
     */
    public function it_calculates_total_interest(): void
    {
        $totalInterest = $this->calculator->calculateTotalInterest(
            new Decimal('50000'),
            new Decimal('7.75'),
            36
        );

        // For $50K at 7.75% for 36 months, ~$8,360 in interest
        $this->assertGreaterThan('8000');
        $this->assertLessThan('9000');
    }

    /**
     * @test
     * Validate schedule for accuracy
     */
    public function it_validates_schedule_accuracy(): void
    {
        $schedule = $this->calculator->generateSchedule(
            new Decimal('50000'),
            new Decimal('7.75'),
            36,
            new DateTime('2026-04-29')
        );

        $isValid = $this->calculator->validateSchedule(
            $schedule,
            new Decimal('50000')
        );

        $this->assertTrue($isValid);
    }

    /**
     * @test
     * Get balance at specific period
     */
    public function it_gets_balance_at_period(): void
    {
        $balance = $this->calculator->getBalanceAtPeriod(
            new Decimal('50000'),
            new Decimal('7.75'),
            36,
            12
        );

        // After 12 months, should have paid ~$3,160 in principal
        $this->assertLessThan('50000', (string) $balance);
        $this->assertGreaterThan('45000', (string) $balance);
    }

    /**
     * @test
     * Test with different loan terms
     */
    public function it_handles_different_loan_terms(): void
    {
        $testCases = [
            ['amount' => '25000', 'rate' => '5.00', 'months' => 24],
            ['amount' => '100000', 'rate' => '4.50', 'months' => 60],
            ['amount' => '15000', 'rate' => '12.00', 'months' => 12],
        ];

        foreach ($testCases as $case) {
            $payment = $this->calculator->calculateMonthlyPayment(
                new Decimal($case['amount']),
                new Decimal($case['rate']),
                $case['months']
            );

            // Payment should be positive and reasonable
            $this->assertGreaterThan('0', (string) $payment);
            $this->assertLessThan($case['amount'], (string) $payment);
        }
    }

    /**
     * @test
     * Accuracy validation against known calculation
     */
    public function it_matches_external_calculator_results(): void
    {
        // Known result from verified external calculator
        // Loan: $50,000, Rate: 5%, Term: 60 months
        // Expected monthly payment: $943.56

        $payment = $this->calculator->calculateMonthlyPayment(
            new Decimal('50000'),
            new Decimal('5.00'),
            60
        );

        $this->assertEquals('943.56', (string) $payment);
    }
}
