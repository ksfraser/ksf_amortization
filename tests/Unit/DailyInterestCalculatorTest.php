<?php
namespace Tests\Unit;

use Ksfraser\Amortizations\Calculators\DailyInterestCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DailyInterestCalculator
 *
 * Daily Interest: D = Balance × (Annual Rate / 100) / 365
 * Also calculates accrual between dates
 *
 * @covers Ksfraser\Amortizations\Calculators\DailyInterestCalculator
 */
class DailyInterestCalculatorTest extends TestCase
{
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new DailyInterestCalculator();
    }

    /**
     * @test
     */
    public function testCalculateDailyInterest()
    {
        // $100,000 at 5% daily = 100000 × 0.05 / 365 = 13.70
        $interest = $this->calculator->calculateDaily(100000, 5.0);
        $this->assertEqualsWithDelta(13.70, $interest, 0.01);
    }

    /**
     * @test
     */
    public function testCalculateDailyInterestZeroRate()
    {
        $interest = $this->calculator->calculateDaily(100000, 0.0);
        $this->assertEquals(0, $interest);
    }

    /**
     * @test
     */
    public function testCalculateAccrual()
    {
        // Daily interest × 30 days
        $accrual = $this->calculator->calculateAccrual(100000, 5.0, '2025-01-01', '2025-01-31');
        // 30 days × 13.70 = 411.00
        $this->assertEqualsWithDelta(411.00, $accrual, 0.01);
    }

    /**
     * @test
     */
    public function testCalculateAccrualSameDate()
    {
        $accrual = $this->calculator->calculateAccrual(100000, 5.0, '2025-01-01', '2025-01-01');
        $this->assertEquals(0, $accrual);
    }

    /**
     * @test
     */
    public function testCalculateAccrualInvalidDates()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->calculator->calculateAccrual(100000, 5.0, '2025-01-31', '2025-01-01');
    }
}
