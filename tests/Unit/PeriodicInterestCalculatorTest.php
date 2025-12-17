<?php
namespace Tests\Unit;

use Ksfraser\Amortizations\Calculators\PeriodicInterestCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PeriodicInterestCalculator
 *
 * Single Responsibility: Calculate interest for one payment period
 * on a remaining balance.
 *
 * @covers Ksfraser\Amortizations\Calculators\PeriodicInterestCalculator
 */
class PeriodicInterestCalculatorTest extends TestCase
{
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PeriodicInterestCalculator();
    }

    /**
     * Test monthly interest calculation
     *
     * @test
     */
    public function testCalculateMonthlyInterest()
    {
        $balance = 100000;
        $annualRate = 5.0;

        $interest = $this->calculator->calculate($balance, $annualRate, 'monthly');

        // Expected: 100000 × (5.0/100) / 12 = 416.67
        $this->assertEqualsWithDelta(416.67, $interest, 0.01);
    }

    /**
     * Test biweekly interest calculation
     *
     * @test
     */
    public function testCalculateBiweeklyInterest()
    {
        $balance = 100000;
        $annualRate = 5.0;

        $interest = $this->calculator->calculate($balance, $annualRate, 'biweekly');

        // Expected: 100000 × (5.0/100) / 26 = 192.31
        $this->assertEqualsWithDelta(192.31, $interest, 0.01);
    }

    /**
     * Test weekly interest calculation
     *
     * @test
     */
    public function testCalculateWeeklyInterest()
    {
        $balance = 100000;
        $annualRate = 5.0;

        $interest = $this->calculator->calculate($balance, $annualRate, 'weekly');

        // Expected: 100000 × (5.0/100) / 52 = 96.15
        $this->assertEqualsWithDelta(96.15, $interest, 0.01);
    }

    /**
     * Test zero interest rate
     *
     * @test
     */
    public function testCalculateZeroInterestRate()
    {
        $balance = 100000;
        $annualRate = 0.0;

        $interest = $this->calculator->calculate($balance, $annualRate, 'monthly');

        $this->assertEquals(0.0, $interest);
    }

    /**
     * Test zero balance
     *
     * @test
     */
    public function testCalculateZeroBalance()
    {
        $balance = 0;
        $annualRate = 5.0;

        $interest = $this->calculator->calculate($balance, $annualRate, 'monthly');

        $this->assertEquals(0.0, $interest);
    }

    /**
     * Test negative balance throws exception
     *
     * @test
     */
    public function testCalculateNegativeBalanceThrows()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->calculate(-100000, 5.0, 'monthly');
    }

    /**
     * Test negative rate throws exception
     *
     * @test
     */
    public function testCalculateNegativeRateThrows()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->calculate(100000, -5.0, 'monthly');
    }

    /**
     * Test invalid frequency throws exception
     *
     * @test
     */
    public function testCalculateInvalidFrequencyThrows()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->calculate(100000, 5.0, 'invalid');
    }

    /**
     * Test high balance calculation
     *
     * @test
     */
    public function testCalculateHighBalance()
    {
        $balance = 1000000; // $1M
        $annualRate = 5.0;

        $interest = $this->calculator->calculate($balance, $annualRate, 'monthly');

        // Expected: 1000000 × 0.05 / 12 = 4166.67
        $this->assertEqualsWithDelta(4166.67, $interest, 0.01);
    }

    /**
     * Test high interest rate
     *
     * @test
     */
    public function testCalculateHighInterestRate()
    {
        $balance = 100000;
        $annualRate = 15.0; // 15%

        $interest = $this->calculator->calculate($balance, $annualRate, 'monthly');

        // Expected: 100000 × 0.15 / 12 = 1250.00
        $this->assertEqualsWithDelta(1250.00, $interest, 0.01);
    }

    /**
     * Test precision (rounded to 2 decimal places)
     *
     * @test
     */
    public function testCalculatePrecision()
    {
        $balance = 12345.67;
        $annualRate = 4.321;

        $interest = $this->calculator->calculate($balance, $annualRate, 'monthly');

        // Result should have max 2 decimal places
        $this->assertEquals(2, strlen(substr(strrchr($interest, "."), 1)));
    }
}
