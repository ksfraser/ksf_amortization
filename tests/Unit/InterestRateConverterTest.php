<?php
namespace Tests\Unit;

use Ksfraser\Amortizations\Calculators\InterestRateConverter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InterestRateConverter
 *
 * Converts interest rates between different payment frequencies.
 *
 * @covers Ksfraser\Amortizations\Calculators\InterestRateConverter
 */
class InterestRateConverterTest extends TestCase
{
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new InterestRateConverter();
    }

    /**
     * @test
     */
    public function testConvertMonthlyToAnnual()
    {
        // Monthly rate 0.4167% × 12 = 5.0%
        $converted = $this->calculator->convert(0.4167, 'monthly', 'annual');
        $this->assertEqualsWithDelta(5.0, $converted, 0.01);
    }

    /**
     * @test
     */
    public function testConvertAnnualToMonthly()
    {
        // Annual rate 5% / 12 = 0.4167%
        $converted = $this->calculator->convert(5.0, 'annual', 'monthly');
        $this->assertEqualsWithDelta(0.4167, $converted, 0.01);
    }

    /**
     * @test
     */
    public function testConvertBiweeklyToMonthly()
    {
        // Biweekly (26/year) to Monthly (12/year)
        $converted = $this->calculator->convert(1.0, 'biweekly', 'monthly');
        $this->assertEqualsWithDelta(2.1667, $converted, 0.01);
    }

    /**
     * @test
     */
    public function testConvertSameFrequency()
    {
        $converted = $this->calculator->convert(5.0, 'monthly', 'monthly');
        $this->assertEquals(5.0, $converted);
    }

    /**
     * @test
     */
    public function testConvertInvalidFrequency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->calculator->convert(5.0, 'invalid', 'monthly');
    }
}
