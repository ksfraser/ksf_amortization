<?php
namespace Tests\Unit;

use Ksfraser\Amortizations\Calculators\PaymentCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PaymentCalculator
 * 
 * Tests the single responsibility of calculating payment amounts using the PMT formula.
 * Uses TDD approach - tests written first.
 * 
 * @covers Ksfraser\Amortizations\Calculators\PaymentCalculator
 */
class PaymentCalculatorTest extends TestCase
{
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PaymentCalculator();
    }

    /**
     * Test basic payment calculation with monthly frequency
     * 
     * @test
     */
    public function testCalculatePaymentMonthly()
    {
        $principal = 10000;
        $annualRate = 5.0;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 12;

        $payment = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);

        // Result should be positive
        $this->assertGreaterThan(0, $payment);
        
        // Should be reasonable (roughly principal/12 + interest for monthly)
        // Approximate: 10000/12 = 833.33 + interest
        $this->assertGreaterThan(800, $payment);
        $this->assertLessThan(1000, $payment);
    }

    /**
     * Test payment with biweekly frequency
     * 
     * @test
     */
    public function testCalculatePaymentBiweekly()
    {
        $principal = 10000;
        $annualRate = 5.0;
        $paymentFrequency = 'biweekly';
        $numberOfPayments = 26; // 2 weeks * 26 = 52 weeks = 1 year

        $payment = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);

        $this->assertGreaterThan(0, $payment);
        $this->assertIsFloat($payment);
    }

    /**
     * Test payment with weekly frequency
     * 
     * @test
     */
    public function testCalculatePaymentWeekly()
    {
        $principal = 10000;
        $annualRate = 5.0;
        $paymentFrequency = 'weekly';
        $numberOfPayments = 52; // 52 weeks

        $payment = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);

        $this->assertGreaterThan(0, $payment);
        $this->assertIsFloat($payment);
    }

    /**
     * Test payment with annual frequency
     * 
     * @test
     */
    public function testCalculatePaymentAnnual()
    {
        $principal = 10000;
        $annualRate = 5.0;
        $paymentFrequency = 'annual';
        $numberOfPayments = 5; // 5 years

        $payment = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);

        $this->assertGreaterThan(0, $payment);
        // Annual payment should be roughly 2000-2500 for 5 years
        $this->assertGreaterThan(1800, $payment);
        $this->assertLessThan(3000, $payment);
    }

    /**
     * Test payment with zero interest rate
     * 
     * @test
     */
    public function testCalculatePaymentZeroInterest()
    {
        $principal = 12000;
        $annualRate = 0.0;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 12;

        $payment = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);

        // With 0% interest, payment = principal / number of payments
        $this->assertEquals(1000, $payment);
    }

    /**
     * Test that invalid frequency throws exception
     * 
     * @test
     */
    public function testCalculatePaymentInvalidFrequency()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->calculator->calculate(10000, 5.0, 'invalid_frequency', 12);
    }

    /**
     * Test that negative principal throws exception
     * 
     * @test
     */
    public function testCalculatePaymentNegativePrincipal()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->calculator->calculate(-10000, 5.0, 'monthly', 12);
    }

    /**
     * Test that zero principal throws exception
     * 
     * @test
     */
    public function testCalculatePaymentZeroPrincipal()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->calculator->calculate(0, 5.0, 'monthly', 12);
    }

    /**
     * Test that negative number of payments throws exception
     * 
     * @test
     */
    public function testCalculatePaymentNegativePayments()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->calculator->calculate(10000, 5.0, 'monthly', -12);
    }

    /**
     * Test that zero payments throws exception
     * 
     * @test
     */
    public function testCalculatePaymentZeroPayments()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->calculator->calculate(10000, 5.0, 'monthly', 0);
    }

    /**
     * Test payment with high interest rate
     * 
     * @test
     */
    public function testCalculatePaymentHighInterest()
    {
        $principal = 10000;
        $annualRate = 15.0; // 15% interest
        $paymentFrequency = 'monthly';
        $numberOfPayments = 12;

        $payment = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);

        // With higher interest, payment should be greater than with 5% interest
        $this->assertGreaterThan(900, $payment);
    }

    /**
     * Test consistency: same calculation twice should yield same result
     * 
     * @test
     */
    public function testCalculatePaymentConsistency()
    {
        $principal = 50000;
        $annualRate = 7.5;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 60;

        $payment1 = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);
        $payment2 = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);

        $this->assertEquals($payment1, $payment2);
    }

    /**
     * Test semiannual frequency
     * 
     * @test
     */
    public function testCalculatePaymentSemiannual()
    {
        $principal = 10000;
        $annualRate = 5.0;
        $paymentFrequency = 'semiannual';
        $numberOfPayments = 2;

        $payment = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);

        $this->assertGreaterThan(0, $payment);
        $this->assertIsFloat($payment);
    }

    /**
     * Test daily frequency
     * 
     * @test
     */
    public function testCalculatePaymentDaily()
    {
        $principal = 10000;
        $annualRate = 5.0;
        $paymentFrequency = 'daily';
        $numberOfPayments = 365;

        $payment = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);

        $this->assertGreaterThan(0, $payment);
        $this->assertIsFloat($payment);
    }

    /**
     * Test negative annual rate (should throw exception)
     * 
     * @test
     */
    public function testCalculatePaymentNegativeRate()
    {
        // Negative rates might be allowed in some contexts (deflation?)
        // But for now, we'll validate that system accepts it
        // (could be changed to throw exception based on business rules)
        $principal = 10000;
        $annualRate = -5.0;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 12;

        // This may or may not throw - depends on implementation choice
        // For now, just test it returns a number
        try {
            $payment = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);
            $this->assertIsFloat($payment);
        } catch (\InvalidArgumentException $e) {
            // OK if negative rates are not allowed
            $this->assertStringContainsString('rate', strtolower($e->getMessage()));
        }
    }

    /**
     * Test large principal amount
     * 
     * @test
     */
    public function testCalculatePaymentLargePrincipal()
    {
        $principal = 1000000; // $1M loan
        $annualRate = 3.5;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 360; // 30 years

        $payment = $this->calculator->calculate($principal, $annualRate, $paymentFrequency, $numberOfPayments);

        // Should be reasonable payment
        $this->assertGreaterThan(0, $payment);
        $this->assertGreaterThan(4000, $payment); // At least $4000/month
    }
}
