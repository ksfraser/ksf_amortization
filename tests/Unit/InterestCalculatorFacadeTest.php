<?php
namespace Tests\Unit;

use Ksfraser\Amortizations\Calculators\InterestCalculatorFacade;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InterestCalculatorFacade
 *
 * Verifies that the facade correctly delegates to the 6 SRP calculators
 * while maintaining backwards compatibility with the original interface.
 *
 * @covers Ksfraser\Amortizations\Calculators\InterestCalculatorFacade
 */
class InterestCalculatorFacadeTest extends TestCase
{
    private $facade;

    protected function setUp(): void
    {
        $this->facade = new InterestCalculatorFacade();
    }

    /**
     * Test that facade delegates periodic interest calculation
     *
     * @test
     */
    public function testCalculatePeriodicInterest()
    {
        // $100,000 balance at 5% annual, monthly
        $balance = 100000;
        $annualRate = 5.0;
        $frequency = 'monthly';

        $interest = $this->facade->calculatePeriodicInterest($balance, $annualRate, $frequency);

        // Expected: 100000 * (5/100) / 12 = 416.67
        $this->assertEquals(416.67, $interest);
    }

    /**
     * Test periodic interest with biweekly frequency
     *
     * @test
     */
    public function testPeriodicInterestBiweekly()
    {
        $balance = 100000;
        $annualRate = 5.0;
        $frequency = 'biweekly';

        $interest = $this->facade->calculatePeriodicInterest($balance, $annualRate, $frequency);

        // Expected: 100000 * (5/100) / 26 = 192.31
        $this->assertEquals(192.31, $interest);
    }

    /**
     * Test simple interest calculation delegation
     *
     * @test
     */
    public function testCalculateSimpleInterest()
    {
        // $100,000 principal at 5% for 1 year
        $principal = 100000;
        $annualRate = 5.0;
        $timeInYears = 1;

        $interest = $this->facade->calculateSimpleInterest($principal, $annualRate, $timeInYears);

        // Expected: 100000 * (5/100) * 1 = 5000
        $this->assertEquals(5000.00, $interest);
    }

    /**
     * Test compound interest calculation delegation
     *
     * @test
     */
    public function testCalculateCompoundInterest()
    {
        $principal = 100000;
        $annualRate = 5.0;
        $periods = 12;  // 12 months
        $frequency = 'monthly';

        $interest = $this->facade->calculateCompoundInterest($principal, $annualRate, $periods, $frequency);

        // Should return compound interest earned
        $this->assertGreaterThan(0, $interest);
        $this->assertIsFloat($interest);
    }

    /**
     * Test daily interest calculation delegation
     *
     * @test
     */
    public function testCalculateDailyInterest()
    {
        $balance = 100000;
        $annualRate = 5.0;

        $dailyInterest = $this->facade->calculateDailyInterest($balance, $annualRate);

        // Expected: 100000 * (5/100) / 365 = 13.70
        $this->assertEquals(13.70, $dailyInterest);
    }

    /**
     * Test interest accrual calculation delegation
     *
     * @test
     */
    public function testCalculateInterestAccrual()
    {
        $balance = 100000;
        $annualRate = 5.0;
        $startDate = '2025-01-01';
        $endDate = '2025-01-31';  // 30 days

        $accrual = $this->facade->calculateInterestAccrual($balance, $annualRate, $startDate, $endDate);

        // Expected: daily interest (13.70) * 30 days = 411.00
        $this->assertEquals(411.00, $accrual);
    }

    /**
     * Test APY calculation delegation
     *
     * @test
     */
    public function testCalculateAPYFromAPR()
    {
        $apr = 5.0;
        $frequency = 'monthly';

        $apy = $this->facade->calculateAPYFromAPR($apr, $frequency);

        // APY should be greater than APR due to compounding
        $this->assertGreaterThan($apr, $apy);
        $this->assertLessThan(6.0, $apy);  // But not too much greater
    }

    /**
     * Test effective rate calculation delegation (alias for APY)
     *
     * @test
     */
    public function testCalculateEffectiveRate()
    {
        $nominalRate = 5.0;
        $frequency = 'monthly';

        $effectiveRate = $this->facade->calculateEffectiveRate($nominalRate, $frequency);

        // Should be same as APY
        $apy = $this->facade->calculateAPYFromAPR($nominalRate, $frequency);
        $this->assertEquals($apy, $effectiveRate);
    }

    /**
     * Test rate conversion delegation
     *
     * @test
     */
    public function testConvertRate()
    {
        // Monthly rate: 5% / 12 = 0.4167
        $monthlyRate = 5.0 / 12;
        $fromFrequency = 'monthly';
        $toFrequency = 'annual';

        $annualRate = $this->facade->convertRate($monthlyRate, $fromFrequency, $toFrequency);

        // Should be approximately 5.0
        $this->assertAlmostEquals(5.0, $annualRate, 1);
    }

    /**
     * Test total interest calculation delegation
     *
     * @test
     */
    public function testCalculateTotalInterest()
    {
        $schedule = [
            ['interest_amount' => 416.67],
            ['interest_amount' => 415.45],
            ['interest_amount' => 414.23],
        ];

        $total = $this->facade->calculateTotalInterest($schedule);

        // Expected: sum of interest amounts
        $this->assertEquals(1246.35, $total);
    }

    /**
     * Test precision setting delegation
     *
     * @test
     */
    public function testSetPrecision()
    {
        // Should not throw an exception
        $this->facade->setPrecision(6);

        // Verify facade can still calculate after precision change
        $interest = $this->facade->calculatePeriodicInterest(100000, 5.0, 'monthly');
        $this->assertEquals(416.67, $interest);
    }

    /**
     * Test that facade throws on invalid periodic interest
     *
     * @test
     */
    public function testCalculatePeriodicInterestThrowsOnInvalidFrequency()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->facade->calculatePeriodicInterest(100000, 5.0, 'invalid_frequency');
    }

    /**
     * Test that facade throws on invalid simple interest
     *
     * @test
     */
    public function testCalculateSimpleInterestThrowsOnNegativeRate()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->facade->calculateSimpleInterest(100000, -5.0, 1);
    }

    /**
     * Helper to assert approximate equality
     */
    private function assertAlmostEquals($expected, $actual, $precision = 2)
    {
        $this->assertEquals($expected, $actual, '', 10 ** (-$precision));
    }
}
