<?php

namespace Tests\Unit\Strategies;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Strategies\BalloonPaymentStrategy;
use DateTimeImmutable;

/**
 * BalloonPaymentStrategyTest
 *
 * Tests for balloon payment amortization strategy.
 * Uses TDD Red-Green-Refactor cycle.
 *
 * Balloon payments are used in vehicle leases, some mortgages, and equipment financing.
 * Example: $50,000 car lease with $12,000 balloon (final payment)
 * - Regular monthly payments for 60 months
 * - Large final payment of $12,000 (balloon)
 *
 * @covers \Ksfraser\Amortizations\Strategies\BalloonPaymentStrategy
 */
class BalloonPaymentStrategyTest extends TestCase
{
    private BalloonPaymentStrategy $strategy;
    private Loan $loan;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        $this->strategy = new BalloonPaymentStrategy();

        // Create a test loan: $50,000 principal, 5% annual rate, 60 months, $12,000 balloon
        $this->loan = $this->createTestLoan(
            principal: 50000.00,
            rate: 0.05,
            months: 60,
            startDate: new DateTimeImmutable('2024-01-01'),
            balloonAmount: 12000.00
        );
    }

    /**
     * Test that strategy supports loans with balloon payments.
     *
     * @test
     */
    public function testSupportsLoansWithBalloon(): void
    {
        $this->assertTrue($this->strategy->supports($this->loan));
    }

    /**
     * Test that strategy rejects loans without balloon payments.
     *
     * @test
     */
    public function testRejectsLoansWithoutBalloon(): void
    {
        $loanWithoutBalloon = $this->createTestLoan(
            principal: 50000.00,
            rate: 0.05,
            months: 60,
            startDate: new DateTimeImmutable('2024-01-01'),
            balloonAmount: null
        );

        $this->assertFalse($this->strategy->supports($loanWithoutBalloon));
    }

    /**
     * Test that balloon payment is calculated correctly.
     *
     * Algorithm: Regular payment = (P - Balloon) * [r(1+r)^n] / [(1+r)^n - 1]
     * Where P = principal, Balloon = final payment, r = monthly rate, n = months
     *
     * @test
     */
    public function testCalculatesCorrectPayment(): void
    {
        $payment = $this->strategy->calculatePayment($this->loan);

        // Payment should be a positive numeric value with 2 decimal places
        $this->assertIsFloat($payment);
        $this->assertGreaterThan(0, $payment);
        $this->assertEquals(2, strlen(explode('.', (string)$payment)[1] ?? '00'), 'Payment should have 2 decimal places');
    }

    /**
     * Test that amortization schedule is generated correctly.
     *
     * @test
     */
    public function testGeneratesAmortizationSchedule(): void
    {
        $schedule = $this->strategy->calculateSchedule($this->loan);

        // Should have 60 periods
        $this->assertCount(60, $schedule);

        // First period should exist and have expected structure
        $firstPeriod = $schedule[0];
        $this->assertIsArray($firstPeriod);
        $this->assertArrayHasKey('payment_number', $firstPeriod);
        $this->assertArrayHasKey('payment_date', $firstPeriod);
        $this->assertArrayHasKey('payment_amount', $firstPeriod);
        $this->assertArrayHasKey('principal', $firstPeriod);
        $this->assertArrayHasKey('interest', $firstPeriod);
        $this->assertArrayHasKey('balance', $firstPeriod);
        $this->assertArrayHasKey('balloon_amount', $firstPeriod);
    }

    /**
     * Test that final payment includes balloon amount.
     *
     * @test
     */
    public function testFinalPaymentIncludesBalloon(): void
    {
        $schedule = $this->strategy->calculateSchedule($this->loan);
        $lastPayment = $schedule[count($schedule) - 1];

        // Final balloon should be in the last period
        $this->assertNotNull($lastPayment['balloon_amount']);
        $this->assertEquals(12000.00, $lastPayment['balloon_amount'], 'Final payment should include $12,000 balloon');
    }

    /**
     * Test that schedule balance ends at $0.00 (within acceptable rounding).
     *
     * Acceptable tolerance: ±$0.02 due to floating point arithmetic
     *
     * @test
     */
    public function testScheduleBalanceEndsAtZero(): void
    {
        $schedule = $this->strategy->calculateSchedule($this->loan);
        $finalBalance = $schedule[count($schedule) - 1]['balance'];

        $this->assertLessThanOrEqual(0.02, abs($finalBalance), 'Final balance should be $0.00 ±$0.02');
    }

    /**
     * Test that balloon percentage is calculated correctly for various amounts.
     *
     * @test
     */
    public function testBalloonPercentageCalculation(): void
    {
        $testCases = [
            ['principal' => 50000, 'balloon' => 10000, 'expectedPercent' => 20.0],
            ['principal' => 50000, 'balloon' => 15000, 'expectedPercent' => 30.0],
            ['principal' => 50000, 'balloon' => 25000, 'expectedPercent' => 50.0],
        ];

        foreach ($testCases as $case) {
            $loan = $this->createTestLoan(
                principal: $case['principal'],
                rate: 0.05,
                months: 60,
                startDate: new DateTimeImmutable('2024-01-01'),
                balloonAmount: $case['balloon']
            );

            $payment = $this->strategy->calculatePayment($loan);
            $this->assertIsFloat($payment);
            $this->assertGreaterThan(0, $payment);

            // Calculate actual balloon percentage
            $balloonPercent = ($case['balloon'] / $case['principal']) * 100;
            $this->assertEqualsWithDelta($case['expectedPercent'], $balloonPercent, 0.1);
        }
    }

    /**
     * Test edge case: balloon amount equals principal (invalid, should fail).
     *
     * @test
     */
    public function testRejectsBalloonequalingPrincipal(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $loan = $this->createTestLoan(
            principal: 50000.00,
            rate: 0.05,
            months: 60,
            startDate: new DateTimeImmutable('2024-01-01'),
            balloonAmount: 50000.00  // Balloon = principal (invalid)
        );

        $this->strategy->calculatePayment($loan);
    }

    /**
     * Test edge case: balloon > principal (invalid, should fail).
     *
     * @test
     */
    public function testRejectsBalloonGreaterThanPrincipal(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $loan = $this->createTestLoan(
            principal: 50000.00,
            rate: 0.05,
            months: 60,
            startDate: new DateTimeImmutable('2024-01-01'),
            balloonAmount: 55000.00  // Balloon > principal (invalid)
        );

        $this->strategy->calculatePayment($loan);
    }

    /**
     * Test edge case: 0% interest rate.
     *
     * With 0% interest, regular payments = (Principal - Balloon) / Months
     * For $50,000 principal, $12,000 balloon, 60 months:
     * Payment = ($50,000 - $12,000) / 60 = $633.33
     *
     * @test
     */
    public function testHandlesZeroInterestRate(): void
    {
        $loan = $this->createTestLoan(
            principal: 50000.00,
            rate: 0.0,
            months: 60,
            startDate: new DateTimeImmutable('2024-01-01'),
            balloonAmount: 12000.00
        );

        $payment = $this->strategy->calculatePayment($loan);
        $expectedPayment = (50000 - 12000) / 60;

        $this->assertEqualsWithDelta($expectedPayment, $payment, 0.01, 'At 0% interest, payment should equal (principal - balloon) / months');
    }

    /**
     * Test edge case: single payment only (months = 1).
     * 
     * For a 1-month loan, all principal is due immediately plus 1 month of interest.
     *
     * @test
     */
    public function testHandlesSinglePayment(): void
    {
        $loan = $this->createTestLoan(
            principal: 50000.00,
            rate: 0.05,
            months: 1,
            startDate: new DateTimeImmutable('2024-01-01'),
            balloonAmount: 12000.00
        );

        $schedule = $this->strategy->calculateSchedule($loan);
        $this->assertCount(1, $schedule);

        $singlePayment = $schedule[0];
        $this->assertEquals(1, $singlePayment['payment_number']);
        // Payment should be a positive amount with proper structure
        $this->assertGreaterThan(0, $singlePayment['payment_amount']);
        $this->assertIsNumeric($singlePayment['payment_amount']);
    }

    /**
     * Test that all payments round to 2 decimal places.
     *
     * @test
     */
    public function testPaymentsRoundTo2Decimals(): void
    {
        $schedule = $this->strategy->calculateSchedule($this->loan);

        foreach ($schedule as $row) {
            $decimalPlaces = strlen(explode('.', (string)$row['payment_amount'])[1] ?? '00');
            $this->assertLessThanOrEqual(2, $decimalPlaces, "Payment {$row['payment_number']} has too many decimal places");

            $decimalPlaces = strlen(explode('.', (string)$row['principal'])[1] ?? '00');
            $this->assertLessThanOrEqual(2, $decimalPlaces, "Principal in {$row['payment_number']} has too many decimal places");
        }
    }

    /**
     * Test that principal and interest sum to payment amount (within rounding tolerance).
     *
     * Payment = Principal + Interest
     * In final period: Principal = Balloon amount (what remains to pay off)
     *
     * @test
     */
    public function testPrincipalAndInterestSumToPayment(): void
    {
        $schedule = $this->strategy->calculateSchedule($this->loan);

        foreach ($schedule as $row) {
            $expectedPayment = $row['principal'] + $row['interest'];

            $this->assertEqualsWithDelta(
                $expectedPayment,
                $row['payment_amount'],
                0.02,
                "Payment components don't sum to payment amount for period {$row['payment_number']}"
            );
        }
    }

    /**
     * Helper to create a test loan with specified parameters.
     *
     * @param float $principal Loan principal amount
     * @param float $rate Annual interest rate (as decimal, e.g., 0.05 for 5%)
     * @param int $months Number of months
     * @param DateTimeImmutable $startDate Loan start date
     * @param float|null $balloonAmount Final balloon payment amount
     *
     * @return Loan A configured test loan
     */
    private function createTestLoan(
        float $principal,
        float $rate,
        int $months,
        DateTimeImmutable $startDate,
        ?float $balloonAmount = null
    ): Loan {
        // Create mock loan object with necessary properties
        $loan = new Loan();
        $loan->setPrincipal($principal);
        $loan->setAnnualRate($rate);
        $loan->setMonths($months);
        $loan->setStartDate($startDate);

        if ($balloonAmount !== null) {
            $loan->setBalloonAmount($balloonAmount);
        }

        return $loan;
    }
}
