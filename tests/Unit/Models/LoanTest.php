<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\RatePeriod;
use Ksfraser\Amortizations\Models\Arrears;
use DateTimeImmutable;

class LoanTest extends TestCase
{
    private Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loan = new Loan();
    }

    public function testConstructorInitializesDefaults(): void
    {
        $loan = new Loan();
        $this->assertNull($loan->getId());
        $this->assertEquals(0.0, $loan->getPrincipal());
        $this->assertEquals(0.0, $loan->getAnnualRate());
        $this->assertEquals(0, $loan->getMonths());
        $this->assertInstanceOf(DateTimeImmutable::class, $loan->getStartDate());
        $this->assertNull($loan->getBalloonAmount());
        $this->assertEquals([], $loan->getRatePeriods());
        $this->assertEquals([], $loan->getArrears());
        $this->assertEquals([], $loan->getSchedule());
        $this->assertEquals(0.0, $loan->getCurrentBalance());
        $this->assertEquals(0, $loan->getPaymentsMade());
    }

    public function testSetAndGetId(): void
    {
        $this->loan->setId(123);
        $this->assertEquals(123, $this->loan->getId());
    }

    public function testSetAndGetPrincipal(): void
    {
        $this->loan->setPrincipal(50000.00);
        $this->assertEquals(50000.00, $this->loan->getPrincipal());
        $this->assertEquals(50000.00, $this->loan->getCurrentBalance());
    }

    public function testSetPrincipalRoundsToTwoDecimals(): void
    {
        $this->loan->setPrincipal(50000.999);
        $this->assertEquals(50001.00, $this->loan->getPrincipal());
    }

    public function testSetPrincipalThrowsOnZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Principal must be greater than 0");
        $this->loan->setPrincipal(0);
    }

    public function testSetPrincipalThrowsOnNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Principal must be greater than 0");
        $this->loan->setPrincipal(-1000);
    }

    public function testSetAndGetAnnualRate(): void
    {
        $this->loan->setAnnualRate(0.055);
        $this->assertEquals(0.055, $this->loan->getAnnualRate());
    }

    public function testSetAnnualRateThrowsOnNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Rate must be between 0 and 1");
        $this->loan->setAnnualRate(-0.01);
    }

    public function testSetAnnualRateThrowsOnGreaterThanOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Rate must be between 0 and 1");
        $this->loan->setAnnualRate(1.5);
    }

    public function testSetAndGetMonths(): void
    {
        $this->loan->setMonths(60);
        $this->assertEquals(60, $this->loan->getMonths());
    }

    public function testSetMonthsThrowsOnZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Months must be greater than 0");
        $this->loan->setMonths(0);
    }

    public function testSetMonthsThrowsOnNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Months must be greater than 0");
        $this->loan->setMonths(-12);
    }

    public function testSetAndGetStartDate(): void
    {
        $date = new DateTimeImmutable('2025-01-15');
        $this->loan->setStartDate($date);
        $this->assertEquals($date, $this->loan->getStartDate());
    }

    public function testSetAndGetBalloonAmount(): void
    {
        $this->loan->setBalloonAmount(12000.00);
        $this->assertEquals(12000.00, $this->loan->getBalloonAmount());
    }

    public function testSetBalloonAmountRoundsToTwoDecimals(): void
    {
        $this->loan->setBalloonAmount(12000.999);
        $this->assertEquals(12001.00, $this->loan->getBalloonAmount());
    }

    public function testSetBalloonAmountToNull(): void
    {
        $this->loan->setBalloonAmount(12000.00);
        $this->loan->setBalloonAmount(null);
        $this->assertNull($this->loan->getBalloonAmount());
    }

    public function testSetBalloonAmountThrowsOnNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Balloon amount cannot be negative");
        $this->loan->setBalloonAmount(-500);
    }

    public function testHasBalloonPaymentReturnsTrue(): void
    {
        $this->loan->setBalloonAmount(12000.00);
        $this->assertTrue($this->loan->hasBalloonPayment());
    }

    public function testHasBalloonPaymentReturnsFalseWhenNull(): void
    {
        $this->loan->setBalloonAmount(null);
        $this->assertFalse($this->loan->hasBalloonPayment());
    }

    public function testHasBalloonPaymentReturnsFalseWhenZero(): void
    {
        $this->loan->setBalloonAmount(0);
        $this->assertFalse($this->loan->hasBalloonPayment());
    }

    public function testAddAndGetRatePeriods(): void
    {
        $this->loan->setId(1);
        $period = new RatePeriod(
            1,
            0.06,
            new DateTimeImmutable('2025-06-01'),
            new DateTimeImmutable('2025-12-31')
        );

        $this->loan->addRatePeriod($period);
        $this->assertCount(1, $this->loan->getRatePeriods());
    }

    public function testAddRatePeriodThrowsWhenLoanIdMismatch(): void
    {
        $this->loan->setId(1);
        $period = new RatePeriod(999, 0.05, new DateTimeImmutable());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Rate period belongs to different loan");
        $this->loan->addRatePeriod($period);
    }

    public function testGetRateForDateWithNoRatePeriods(): void
    {
        $this->loan->setAnnualRate(0.05);
        $rate = $this->loan->getRateForDate(new DateTimeImmutable());
        $this->assertEquals(0.05, $rate);
    }

    public function testGetRateForDateWithActiveRatePeriod(): void
    {
        $this->loan->setId(1);
        $this->loan->setAnnualRate(0.05);

        $period = new RatePeriod(
            1,
            0.06,
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-12-31')
        );

        $this->loan->addRatePeriod($period);

        $rate = $this->loan->getRateForDate(new DateTimeImmutable('2025-06-15'));
        $this->assertEquals(0.06, $rate);
    }

    public function testGetRateForDateReturnsDefaultWhenNoActivePeriod(): void
    {
        $this->loan->setId(1);
        $this->loan->setAnnualRate(0.05);

        $period = new RatePeriod(
            1,
            0.06,
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-12-31')
        );

        $this->loan->addRatePeriod($period);

        $rate = $this->loan->getRateForDate(new DateTimeImmutable('2025-06-15'));
        $this->assertEquals(0.05, $rate);
    }

    public function testAddAndGetArrears(): void
    {
        $this->loan->setId(1);
        $arrears = new Arrears(1, 100.00);

        $this->loan->addArrears($arrears);
        $this->assertCount(1, $this->loan->getArrears());
    }

    public function testAddArrearsThrowsWhenLoanIdMismatch(): void
    {
        $this->loan->setId(1);
        $arrears = new Arrears(999);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Arrears belongs to different loan");
        $this->loan->addArrears($arrears);
    }

    public function testGetTotalArrears(): void
    {
        $this->loan->setId(1);

        $arrears1 = new Arrears(1, 100.00);
        $arrears2 = new Arrears(1, 50.50);

        $this->loan->addArrears($arrears1);
        $this->loan->addArrears($arrears2);

        $this->assertEquals(150.50, $this->loan->getTotalArrears());
    }

    public function testGetTotalArrearsReturnsZeroWhenEmpty(): void
    {
        $this->assertEquals(0.0, $this->loan->getTotalArrears());
    }

    public function testSetAndGetSchedule(): void
    {
        $schedule = [
            ['period' => 1, 'payment' => 1000, 'principal' => 800, 'interest' => 200],
            ['period' => 2, 'payment' => 1000, 'principal' => 810, 'interest' => 190],
        ];

        $this->loan->setSchedule($schedule);
        $this->assertEquals($schedule, $this->loan->getSchedule());
    }

    public function testSetAndGetCurrentBalance(): void
    {
        $this->loan->setPrincipal(50000);
        $this->loan->setCurrentBalance(45000);
        $this->assertEquals(45000, $this->loan->getCurrentBalance());
    }

    public function testSetCurrentBalanceRoundsToTwoDecimals(): void
    {
        $this->loan->setCurrentBalance(45000.999);
        $this->assertEquals(45001.00, $this->loan->getCurrentBalance());
    }

    public function testSetAndGetPaymentsMade(): void
    {
        $this->loan->setPaymentsMade(12);
        $this->assertEquals(12, $this->loan->getPaymentsMade());
    }

    public function testSetPaymentsMadePreventsNegative(): void
    {
        $this->loan->setPaymentsMade(-5);
        $this->assertEquals(0, $this->loan->getPaymentsMade());
    }

    public function testGetPaymentsRemaining(): void
    {
        $this->loan->setMonths(60);
        $this->loan->setPaymentsMade(12);
        $this->assertEquals(48, $this->loan->getPaymentsRemaining());
    }

    public function testGetPaymentsRemainingReturnsZeroWhenAllPaid(): void
    {
        $this->loan->setMonths(60);
        $this->loan->setPaymentsMade(60);
        $this->assertEquals(0, $this->loan->getPaymentsRemaining());
    }

    public function testGetPaymentsRemainingReturnsTotalWhenNonePaid(): void
    {
        $this->loan->setMonths(60);
        $this->loan->setPaymentsMade(0);
        $this->assertEquals(60, $this->loan->getPaymentsRemaining());
    }

    public function testGetCreatedAt(): void
    {
        $loan = new Loan();
        $this->assertInstanceOf(DateTimeImmutable::class, $loan->getCreatedAt());
    }

    public function testGetUpdatedAt(): void
    {
        $loan = new Loan();
        $this->assertNull($loan->getUpdatedAt());
    }

    public function testMarkUpdated(): void
    {
        $this->loan->markUpdated();
        $this->assertInstanceOf(DateTimeImmutable::class, $this->loan->getUpdatedAt());
    }

    public function testToStringWithoutBalloon(): void
    {
        $this->loan->setPrincipal(50000);
        $this->loan->setAnnualRate(0.055);
        $this->loan->setMonths(60);

        $expected = "$50000.00 @ 5.50% for 60 months";
        $this->assertEquals($expected, (string)$this->loan);
    }

    public function testToStringWithBalloon(): void
    {
        $this->loan->setPrincipal(50000);
        $this->loan->setAnnualRate(0.055);
        $this->loan->setMonths(60);
        $this->loan->setBalloonAmount(12000);

        $expected = "$50000.00 @ 5.50% for 60 months (with 12000 balloon)";
        $this->assertEquals($expected, (string)$this->loan);
    }

    public function testFluentInterface(): void
    {
        $result = $this->loan
            ->setId(1)
            ->setPrincipal(50000)
            ->setAnnualRate(0.055)
            ->setMonths(60)
            ->setBalloonAmount(12000);

        $this->assertSame($this->loan, $result);
    }
}
