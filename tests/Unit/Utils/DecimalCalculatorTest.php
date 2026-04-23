<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Utils\DecimalCalculator;

class DecimalCalculatorTest extends TestCase
{
    private DecimalCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new DecimalCalculator();
    }

    public function testAddTwoPositiveNumbers(): void
    {
        $result = $this->calculator->add(10.5, 20.3);
        $this->assertEquals('30.8000000000', $result);
    }

    public function testAddWithStrings(): void
    {
        $result = $this->calculator->add('10.50', '20.30');
        $this->assertEquals('30.8000000000', $result);
    }

    public function testAddWithCustomPrecision(): void
    {
        $result = $this->calculator->add(10.5, 20.3, 4);
        $this->assertEquals('30.8000', $result);
    }

    public function testSubtractTwoPositiveNumbers(): void
    {
        $result = $this->calculator->subtract(50.75, 25.25);
        $this->assertEquals('25.5000000000', $result);
    }

    public function testSubtractResultingNegative(): void
    {
        $result = $this->calculator->subtract(10, 20);
        $this->assertEquals('-10.0000000000', $result);
    }

    public function testMultiplyTwoNumbers(): void
    {
        $result = $this->calculator->multiply(10.5, 2);
        $this->assertEquals('21.0000000000', $result);
    }

    public function testMultiplyWithDecimals(): void
    {
        $result = $this->calculator->multiply(100, 0.055);
        $this->assertEquals('5.5000000000', $result);
    }

    public function testDivideTwoNumbers(): void
    {
        $result = $this->calculator->divide(100, 4);
        $this->assertEquals('25.0000000000', $result);
    }

    public function testDivideWithDecimals(): void
    {
        $result = $this->calculator->divide(5, 12, 6);
        $this->assertEquals('0.416667', $result);
    }

    public function testDivideThrowsOnZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Division by zero');
        $this->calculator->divide(100, 0);
    }

    public function testPower(): void
    {
        $result = $this->calculator->power(2, 3);
        $this->assertEquals('8.0000000000', $result);
    }

    public function testPowerWithDecimalBase(): void
    {
        $result = $this->calculator->power(1.05, 2);
        $this->assertEquals('1.1025000000', $result);
    }

    public function testPowerWithZeroExponent(): void
    {
        $result = $this->calculator->power(100, 0);
        $this->assertEquals('1.0000000000', $result);
    }

    public function testRoundToTwoDecimals(): void
    {
        $result = $this->calculator->round(10.555, 2);
        $this->assertEquals('10.56', $result);
    }

    public function testRoundToZeroDecimals(): void
    {
        $result = $this->calculator->round(10.555, 0);
        $this->assertEquals('11', $result);
    }

    public function testRoundDown(): void
    {
        $result = $this->calculator->round(10.544, 2);
        $this->assertEquals('10.54', $result);
    }

    public function testToFloat(): void
    {
        $result = $this->calculator->toFloat('10.555', 2);
        $this->assertEquals(10.56, $result);
    }

    public function testAsFloatWithFloat(): void
    {
        $result = $this->calculator->asFloat(10.555, 2);
        $this->assertEquals(10.56, $result);
    }

    public function testAsFloatWithInt(): void
    {
        $result = $this->calculator->asFloat(10, 2);
        $this->assertEquals(10.0, $result);
    }

    public function testMaxWithTwoValues(): void
    {
        $result = $this->calculator->max(10, 20);
        $this->assertEquals('20.0000000000', $result);
    }

    public function testMaxWithMultipleValues(): void
    {
        $result = $this->calculator->max(10, 50, 30, 20);
        $this->assertEquals('50.0000000000', $result);
    }

    public function testMinWithTwoValues(): void
    {
        $result = $this->calculator->min(10, 20);
        $this->assertEquals('10.0000000000', $result);
    }

    public function testMinWithMultipleValues(): void
    {
        $result = $this->calculator->min(10, 50, 30, 5);
        $this->assertEquals('5.0000000000', $result);
    }

    public function testAbsWithPositive(): void
    {
        $result = $this->calculator->abs(10.5);
        $this->assertEquals('10.5000000000', $result);
    }

    public function testAbsWithNegative(): void
    {
        $result = $this->calculator->abs(-10.5);
        $this->assertEquals('10.5000000000', $result);
    }

    public function testAbsWithZero(): void
    {
        $result = $this->calculator->abs(0);
        $this->assertEquals('0.0000000000', $result);
    }

    public function testIsZeroReturnsTrue(): void
    {
        $this->assertTrue($this->calculator->isZero(0));
        $this->assertTrue($this->calculator->isZero('0'));
        $this->assertTrue($this->calculator->isZero('0.00'));
    }

    public function testIsZeroReturnsFalse(): void
    {
        $this->assertFalse($this->calculator->isZero(1));
        $this->assertFalse($this->calculator->isZero(-1));
        $this->assertFalse($this->calculator->isZero(0.001));
    }

    public function testCompareLessThan(): void
    {
        $result = $this->calculator->compare(10, 20);
        $this->assertEquals(-1, $result);
    }

    public function testCompareGreaterThan(): void
    {
        $result = $this->calculator->compare(30, 20);
        $this->assertEquals(1, $result);
    }

    public function testCompareEqual(): void
    {
        $result = $this->calculator->compare(20, 20);
        $this->assertEquals(0, $result);
    }

    public function testCompareWithDecimals(): void
    {
        $result = $this->calculator->compare(10.5, 10.50);
        $this->assertEquals(0, $result);
    }

    public function testSetInternalPrecision(): void
    {
        $this->calculator->setInternalPrecision(6);
        $result = $this->calculator->add(10.5, 20.3);
        $this->assertEquals('30.800000', $result);
    }

    public function testSetInternalPrecisionThrowsOnLowValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Internal precision must be at least 2');
        $this->calculator->setInternalPrecision(1);
    }

    public function testSetOutputPrecision(): void
    {
        $this->calculator->setOutputPrecision(4);
        $reflection = new \ReflectionClass($this->calculator);
        $property = $reflection->getProperty('outputPrecision');
        $property->setAccessible(true);
        $this->assertEquals(4, $property->getValue($this->calculator));
    }

    public function testSetOutputPrecisionThrowsOnNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Output precision must be non-negative');
        $this->calculator->setOutputPrecision(-1);
    }

    public function testAmortizationFormula(): void
    {
        $principal = 100000;
        $annualRate = 0.05;
        $months = 360;

        $r = $this->calculator->divide($annualRate, 12, 10);
        $r_plus_1 = $this->calculator->add(1, $r);
        $power = $this->calculator->power($r_plus_1, $months, 10);
        $numerator = $this->calculator->multiply($principal, $r, 10);
        $numerator = $this->calculator->multiply($numerator, $power, 10);
        $denominator = $this->calculator->subtract($power, 1, 10);
        $payment = $this->calculator->divide($numerator, $denominator, 2);

        $expectedPayment = 536.82;
        $this->assertEquals($expectedPayment, (float)$payment, '', 0.02);
    }
}
