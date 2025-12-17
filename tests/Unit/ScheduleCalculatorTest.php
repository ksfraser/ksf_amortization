<?php
namespace Tests\Unit;

use Ksfraser\Amortizations\Calculators\ScheduleCalculator;
use Ksfraser\Amortizations\Calculators\PaymentCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ScheduleCalculator
 *
 * Tests the pure calculation logic for generating amortization schedules.
 * No database access - pure math only.
 *
 * Uses TDD approach - tests written first.
 *
 * @covers Ksfraser\Amortizations\Calculators\ScheduleCalculator
 */
class ScheduleCalculatorTest extends TestCase
{
    private $calculator;
    private $paymentCalculator;

    protected function setUp(): void
    {
        $this->paymentCalculator = new PaymentCalculator();
        $this->calculator = new ScheduleCalculator($this->paymentCalculator);
    }

    /**
     * Test basic schedule generation with simple parameters
     *
     * @test
     */
    public function testGenerateScheduleBasic()
    {
        $principal = 10000;
        $annualRate = 5.0;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 12;

        $schedule = $this->calculator->generateSchedule(
            $principal,
            $annualRate,
            $paymentFrequency,
            $numberOfPayments
        );

        // Should return array of schedule rows
        $this->assertIsArray($schedule);
        $this->assertCount(12, $schedule);

        // Each row should be an array with required fields
        foreach ($schedule as $payment) {
            $this->assertIsArray($payment);
            $this->assertArrayHasKey('payment_number', $payment);
            $this->assertArrayHasKey('payment_date', $payment);
            $this->assertArrayHasKey('payment_amount', $payment);
            $this->assertArrayHasKey('interest_amount', $payment);
            $this->assertArrayHasKey('principal_amount', $payment);
            $this->assertArrayHasKey('remaining_balance', $payment);
        }
    }

    /**
     * Test that schedule starts with correct values
     *
     * @test
     */
    public function testScheduleFirstPayment()
    {
        $principal = 100000;
        $annualRate = 6.0;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 360;

        $schedule = $this->calculator->generateSchedule(
            $principal,
            $annualRate,
            $paymentFrequency,
            $numberOfPayments
        );

        $firstPayment = $schedule[0];

        // First payment should be payment #1
        $this->assertEquals(1, $firstPayment['payment_number']);

        // Interest portion should be: 100000 * (6/100) / 12
        $expectedFirstInterest = round(100000 * 0.06 / 12, 2);
        $this->assertEquals($expectedFirstInterest, $firstPayment['interest_amount']);

        // Remaining balance should be principal - principal portion
        $this->assertGreaterThan(90000, $firstPayment['remaining_balance']);
        $this->assertLessThan(100000, $firstPayment['remaining_balance']);
    }

    /**
     * Test that final payment zeroes the balance
     *
     * @test
     */
    public function testScheduleFinalPaymentBalance()
    {
        $principal = 50000;
        $annualRate = 5.0;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 60;

        $schedule = $this->calculator->generateSchedule(
            $principal,
            $annualRate,
            $paymentFrequency,
            $numberOfPayments
        );

        $finalPayment = $schedule[count($schedule) - 1];

        // Final balance should be $0 or very close (floating point)
        $this->assertLessThanOrEqual(0.01, abs($finalPayment['remaining_balance']));
    }

    /**
     * Test schedule with zero interest rate
     *
     * @test
     */
    public function testScheduleZeroInterest()
    {
        $principal = 12000;
        $annualRate = 0.0;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 12;

        $schedule = $this->calculator->generateSchedule(
            $principal,
            $annualRate,
            $paymentFrequency,
            $numberOfPayments
        );

        // Each payment should be same: 12000/12 = 1000
        foreach ($schedule as $payment) {
            $this->assertEquals(1000, $payment['payment_amount']);
            $this->assertEquals(0, $payment['interest_amount']);
            $this->assertEquals(1000, $payment['principal_amount']);
        }
    }

    /**
     * Test schedule payment dates are calculated correctly
     *
     * @test
     */
    public function testSchedulePaymentDates()
    {
        $principal = 10000;
        $annualRate = 5.0;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 12;
        $startDate = '2025-01-15';

        $schedule = $this->calculator->generateSchedule(
            $principal,
            $annualRate,
            $paymentFrequency,
            $numberOfPayments,
            $startDate
        );

        // First payment should be on start date
        $this->assertEquals($startDate, $schedule[0]['payment_date']);

        // Second payment should be one month later (approximately 30 days)
        $firstDate = new \DateTime($schedule[0]['payment_date']);
        $secondDate = new \DateTime($schedule[1]['payment_date']);
        $interval = $firstDate->diff($secondDate);
        // Monthly payments are 30 days apart (365/12)
        $this->assertEquals(30, $interval->days);
    }

    /**
     * Test schedule with biweekly frequency
     *
     * @test
     */
    public function testScheduleBiweeklyFrequency()
    {
        $principal = 10000;
        $annualRate = 5.0;
        $paymentFrequency = 'biweekly';
        $numberOfPayments = 26;

        $schedule = $this->calculator->generateSchedule(
            $principal,
            $annualRate,
            $paymentFrequency,
            $numberOfPayments
        );

        // Should have 26 payments
        $this->assertCount(26, $schedule);

        // Each payment should be ~14 days apart
        for ($i = 1; $i < count($schedule); $i++) {
            $prevDate = new \DateTime($schedule[$i - 1]['payment_date']);
            $currDate = new \DateTime($schedule[$i]['payment_date']);
            $days = $prevDate->diff($currDate)->days;
            $this->assertEquals(14, $days);
        }
    }

    /**
     * Test that total paid equals principal + total interest
     *
     * @test
     */
    public function testScheduleTotalPayment()
    {
        $principal = 50000;
        $annualRate = 5.0;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 60;

        $schedule = $this->calculator->generateSchedule(
            $principal,
            $annualRate,
            $paymentFrequency,
            $numberOfPayments
        );

        // Sum all payments, interest, and principal
        $totalPayments = array_sum(array_column($schedule, 'payment_amount'));
        $totalInterest = array_sum(array_column($schedule, 'interest_amount'));
        $totalPrincipal = array_sum(array_column($schedule, 'principal_amount'));

        // Total principal should equal original (within rounding)
        $this->assertLessThan(0.10, abs($totalPrincipal - $principal));

        // Total should be verified
        $this->assertLessThan(0.10, abs($totalPayments - ($totalPrincipal + $totalInterest)));
    }

    /**
     * Test with different interest calculation frequency
     *
     * @test
     */
    public function testScheduleWithDifferentInterestFrequency()
    {
        $principal = 10000;
        $annualRate = 5.0;
        $paymentFrequency = 'monthly';
        $interestCalcFrequency = 'semiannual'; // Different from payment frequency
        $numberOfPayments = 12;

        $schedule = $this->calculator->generateSchedule(
            $principal,
            $annualRate,
            $paymentFrequency,
            $numberOfPayments,
            '2025-01-01',
            $interestCalcFrequency
        );

        // Should still generate 12 payments
        $this->assertCount(12, $schedule);

        // All values should be valid numbers
        foreach ($schedule as $payment) {
            $this->assertIsNumeric($payment['payment_amount']);
            $this->assertIsNumeric($payment['interest_amount']);
            $this->assertIsNumeric($payment['principal_amount']);
        }
    }

    /**
     * Test negative values throw exception
     *
     * @test
     */
    public function testScheduleNegativePrincipal()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->generateSchedule(
            -10000,  // negative
            5.0,
            'monthly',
            12
        );
    }

    /**
     * Test invalid frequency throws exception
     *
     * @test
     */
    public function testScheduleInvalidFrequency()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->generateSchedule(
            10000,
            5.0,
            'invalid_freq',
            12
        );
    }

    /**
     * Test that schedule can handle large principal amounts
     *
     * @test
     */
    public function testScheduleLargePrincipal()
    {
        $principal = 1000000;  // $1M loan
        $annualRate = 3.5;
        $paymentFrequency = 'monthly';
        $numberOfPayments = 360;  // 30 years

        $schedule = $this->calculator->generateSchedule(
            $principal,
            $annualRate,
            $paymentFrequency,
            $numberOfPayments
        );

        $this->assertCount(360, $schedule);

        // First payment should be large but reasonable
        $firstPayment = $schedule[0];
        $this->assertGreaterThan(3000, $firstPayment['payment_amount']);
        $this->assertLessThan(5000, $firstPayment['payment_amount']);
    }
}
