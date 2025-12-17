<?php
namespace Tests\Unit;

use Ksfraser\Amortizations\Calculators\SimpleInterestCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SimpleInterestCalculator
 *
 * Simple Interest: I = P × R × T
 * No compounding, just principal × rate × time
 *
 * @covers Ksfraser\Amortizations\Calculators\SimpleInterestCalculator
 */
class SimpleInterestCalculatorTest extends TestCase
{
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SimpleInterestCalculator();
    }

    /**
     * @test
     */
    public function testCalculateOneYear()
    {
        // $100,000 at 5% for 1 year = $5,000
        $interest = $this->calculator->calculate(100000, 5.0, 1);
        $this->assertEquals(5000, $interest);
    }

    /**
     * @test
     */
    public function testCalculateHalfYear()
    {
        // $100,000 at 5% for 0.5 years = $2,500
        $interest = $this->calculator->calculate(100000, 5.0, 0.5);
        $this->assertEquals(2500, $interest);
    }

    /**
     * @test
     */
    public function testCalculateMultipleYears()
    {
        // $100,000 at 5% for 5 years = $25,000
        $interest = $this->calculator->calculate(100000, 5.0, 5);
        $this->assertEquals(25000, $interest);
    }

    /**
     * @test
     */
    public function testCalculateZeroRate()
    {
        $interest = $this->calculator->calculate(100000, 0.0, 1);
        $this->assertEquals(0, $interest);
    }

    /**
     * @test
     */
    public function testCalculateZeroTime()
    {
        // Time is 0 - should throw
        $this->expectException(\InvalidArgumentException::class);
        $this->calculator->calculate(100000, 5.0, 0);
    }

    /**
     * @test
     */
    public function testCalculatePrecision()
    {
        $interest = $this->calculator->calculate(12345.67, 3.456, 2.5);
        // Should be rounded to 2 decimal places
        $this->assertEquals(2, strlen(substr(strrchr($interest, "."), 1)));
    }
}
