<?php
namespace Tests\Unit;

use Ksfraser\Amortizations\Calculators\CompoundInterestCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CompoundInterestCalculator
 *
 * Compound Interest: A = P(1 + r/n)^(nt)
 * Interest = A - P
 *
 * @covers Ksfraser\Amortizations\Calculators\CompoundInterestCalculator
 */
class CompoundInterestCalculatorTest extends TestCase
{
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new CompoundInterestCalculator();
    }

    /**
     * @test
     */
    public function testCalculateMonthlyCompounding()
    {
        // $100,000 at 5% monthly for 1 year ≈ $5,116.14
        $interest = $this->calculator->calculate(100000, 5.0, 12, 'monthly');
        $this->assertEqualsWithDelta(5116.14, $interest, 0.10);
    }

    /**
     * @test
     */
    public function testCalculateZeroRate()
    {
        $interest = $this->calculator->calculate(100000, 0.0, 12, 'monthly');
        $this->assertEquals(0, $interest);
    }

    /**
     * @test
     */
    public function testCalculateHighRate()
    {
        $interest = $this->calculator->calculate(100000, 15.0, 12, 'monthly');
        $this->assertGreaterThan(15000, $interest);
    }

    /**
     * @test
     */
    public function testCalculateInvalidFrequency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->calculator->calculate(100000, 5.0, 12, 'invalid');
    }
}
