<?php
namespace Tests\Unit;

use Ksfraser\Amortizations\Calculators\InterestCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InterestCalculator
 *
 * Tests pure calculation logic for interest computations.
 * No database access - pure math only.
 *
 * Uses TDD approach - tests written first.
 *
 * @covers Ksfraser\Amortizations\Calculators\InterestCalculator
 */
class InterestCalculatorTest extends TestCase
{
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new InterestCalculator();
    }

    /**
     * Test basic periodic interest calculation
     *
     * @test
     */
    public function testCalculatePeriodicInterest()
    {
        // $100,000 balance at 5% annual, monthly (1 month of interest)
        $balance = 100000;
        $annualRate = 5.0;
        $frequency = 'monthly';

        $interest = $this->calculator->calculatePeriodicInterest($balance, $annualRate, $frequency);

        // Expected: 100000 * (5/100) / 12 = 416.67
        $this->assertEquals(416.67, $interest);
    }

    /**
     * Test periodic interest with different frequencies
     *
     * @test
     */
    public function testPeriodicInterestBiweekly()
    {
        $balance = 50000;
        $annualRate = 6.0;
        $frequency = 'biweekly';

        $interest = $this->calculator->calculatePeriodicInterest($balance, $annualRate, $frequency);

        // Expected: 50000 * (6/100) / 26 = 115.38
        $this->assertEquals(115.38, $interest);
    }

    /**
     * Test periodic interest with zero interest
     *
     * @test
     */
    public function testPeriodicInterestZero()
    {
        $balance = 100000;
        $annualRate = 0.0;
        $frequency = 'monthly';

        $interest = $this->calculator->calculatePeriodicInterest($balance, $annualRate, $frequency);

        $this->assertEquals(0, $interest);
    }

    /**
     * Test simple interest calculation
     *
     * @test
     */
    public function testCalculateSimpleInterest()
    {
        // Simple Interest = Principal × Rate × Time
        // $100,000 at 5% for 1 year = $5,000
        $principal = 100000;
        $annualRate = 5.0;
        $timeInYears = 1;

        $interest = $this->calculator->calculateSimpleInterest($principal, $annualRate, $timeInYears);

        // 100000 * 5 / 100 * 1 = 5000
        $this->assertEquals(5000, $interest);
    }

    /**
     * Test simple interest for partial year
     *
     * @test
     */
    public function testSimpleInterestPartialYear()
    {
        $principal = 50000;
        $annualRate = 4.0;
        $timeInYears = 0.5;  // 6 months

        $interest = $this->calculator->calculateSimpleInterest($principal, $annualRate, $timeInYears);

        // 50000 * 4 / 100 * 0.5 = 1000
        $this->assertEquals(1000, $interest);
    }

    /**
     * Test compound interest calculation
     *
     * @test
     */
    public function testCalculateCompoundInterest()
    {
        // Compound: A = P(1 + r/n)^(nt)
        // Interest = A - P
        $principal = 100000;
        $annualRate = 5.0;
        $periods = 12;      // Monthly compounding for 1 year
        $frequency = 'monthly';

        $interest = $this->calculator->calculateCompoundInterest(
            $principal,
            $annualRate,
            $periods,
            $frequency
        );

        // Expected: A = 100000(1 + 0.05/12)^12 = 105116.14
        // Interest = 105116.14 - 100000 = 5116.14
        $this->assertGreaterThan(5100, $interest);
        $this->assertLessThan(5200, $interest);
    }

    /**
     * Test compound interest with different frequency
     *
     * @test
     */
    public function testCompoundInterestSemiannual()
    {
        $principal = 50000;
        $annualRate = 6.0;
        $periods = 2;       // Semiannual for 1 year
        $frequency = 'semiannual';

        $interest = $this->calculator->calculateCompoundInterest(
            $principal,
            $annualRate,
            $periods,
            $frequency
        );

        // A = 50000(1 + 0.06/2)^2 = 50000 * 1.03^2 = 53045
        // Interest = 3045
        $this->assertGreaterThan(3000, $interest);
        $this->assertLessThan(3100, $interest);
    }

    /**
     * Test daily interest (for per diem calculations)
     *
     * @test
     */
    public function testCalculateDailyInterest()
    {
        $balance = 100000;
        $annualRate = 5.0;

        $dailyInterest = $this->calculator->calculateDailyInterest($balance, $annualRate);

        // Expected: 100000 * (5/100) / 365 = 13.70
        $this->assertEquals(13.70, $dailyInterest);
    }

    /**
     * Test total interest over schedule
     *
     * @test
     */
    public function testCalculateTotalInterest()
    {
        // 360 monthly payments of $536.82 on $100,000 at 5%
        $schedule = [
            ['interest_amount' => 416.67],
            ['interest_amount' => 413.59],
            ['interest_amount' => 410.51],
            // ... 357 more rows ...
        ];

        $totalInterest = $this->calculator->calculateTotalInterest($schedule);

        $this->assertGreaterThan(0, $totalInterest);
        $this->assertIsNumeric($totalInterest);
    }

    /**
     * Test interest accrual calculation
     *
     * @test
     */
    public function testCalculateInterestAccrual()
    {
        // How much interest accrues from date1 to date2
        $balance = 50000;
        $annualRate = 4.0;
        $startDate = '2025-01-01';
        $endDate = '2025-02-01';  // 31 days

        $accrual = $this->calculator->calculateInterestAccrual(
            $balance,
            $annualRate,
            $startDate,
            $endDate
        );

        // 31 days of interest
        // Daily rate = 50000 * (4/100) / 365 = 5.48
        // 31 days = 169.86
        $this->assertGreaterThan(160, $accrual);
        $this->assertLessThan(180, $accrual);
    }

    /**
     * Test APY (Annual Percentage Yield) calculation from APR
     *
     * @test
     */
    public function testCalculateAPYFromAPR()
    {
        // APY = (1 + APR/n)^n - 1
        // Where n = compounding periods per year
        $apr = 5.0;
        $frequency = 'monthly';  // 12 compoundings per year

        $apy = $this->calculator->calculateAPYFromAPR($apr, $frequency);

        // Expected: (1 + 0.05/12)^12 - 1 = 0.05116 = 5.116%
        $this->assertGreaterThan(5.1, $apy);
        $this->assertLessThan(5.2, $apy);
    }

    /**
     * Test effective rate calculation
     *
     * @test
     */
    public function testCalculateEffectiveRate()
    {
        $nominalRate = 5.0;
        $frequency = 'monthly';

        $effectiveRate = $this->calculator->calculateEffectiveRate($nominalRate, $frequency);

        // Should be higher than nominal due to compounding
        $this->assertGreaterThan($nominalRate, $effectiveRate);
    }

    /**
     * Test rate conversion between frequencies
     *
     * @test
     */
    public function testConvertRate()
    {
        // Convert monthly effective rate to annual
        $monthlyRate = 0.4167;  // 5% / 12
        $fromFrequency = 'monthly';
        $toFrequency = 'annual';

        $annualRate = $this->calculator->convertRate($monthlyRate, $fromFrequency, $toFrequency);

        // Should approximate to 5%
        $this->assertGreaterThan(4.9, $annualRate);
        $this->assertLessThan(5.1, $annualRate);
    }

    /**
     * Test negative balance throws exception
     *
     * @test
     */
    public function testNegativeBalance()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->calculatePeriodicInterest(-100000, 5.0, 'monthly');
    }

    /**
     * Test invalid frequency throws exception
     *
     * @test
     */
    public function testInvalidFrequency()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->calculatePeriodicInterest(100000, 5.0, 'invalid_freq');
    }

    /**
     * Test that interest calculations are consistent
     *
     * @test
     */
    public function testConsistency()
    {
        $balance = 75000;
        $annualRate = 5.5;
        $frequency = 'monthly';

        $interest1 = $this->calculator->calculatePeriodicInterest($balance, $annualRate, $frequency);
        $interest2 = $this->calculator->calculatePeriodicInterest($balance, $annualRate, $frequency);

        $this->assertEquals($interest1, $interest2);
    }
}
