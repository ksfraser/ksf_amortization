<?php
namespace Tests\Unit;

use Ksfraser\Amortizations\Calculators\EffectiveRateCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EffectiveRateCalculator
 *
 * Converts nominal (APR) rates to effective (APY) rates
 * accounting for compounding.
 *
 * @covers Ksfraser\Amortizations\Calculators\EffectiveRateCalculator
 */
class EffectiveRateCalculatorTest extends TestCase
{
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new EffectiveRateCalculator();
    }

    /**
     * @test
     */
    public function testCalculateAPYFromAPRMonthly()
    {
        // APR 5% monthly = APY ≈ 5.116%
        $apy = $this->calculator->calculateAPY(5.0, 'monthly');
        $this->assertEqualsWithDelta(5.116, $apy, 0.01);
    }

    /**
     * @test
     */
    public function testCalculateAPYFromAPRAnnual()
    {
        // APR 5% annual = APY 5%
        $apy = $this->calculator->calculateAPY(5.0, 'annual');
        $this->assertEqualsWithDelta(5.0, $apy, 0.01);
    }

    /**
     * @test
     */
    public function testCalculateAPYZeroRate()
    {
        $apy = $this->calculator->calculateAPY(0.0, 'monthly');
        $this->assertEquals(0.0, $apy);
    }

    /**
     * @test
     */
    public function testCalculateAPYHighFrequency()
    {
        // Daily compounding has highest effect
        $apy = $this->calculator->calculateAPY(5.0, 'daily');
        $this->assertGreaterThan(5.116, $apy);
    }

    /**
     * @test
     */
    public function testCalculateAPYInvalidFrequency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->calculator->calculateAPY(5.0, 'invalid');
    }
}
