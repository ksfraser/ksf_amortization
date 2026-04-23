<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\RatePeriod;
use DateTimeImmutable;

class RatePeriodTest extends TestCase
{
    public function testConstructorWithRequiredParameters(): void
    {
        $period = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2025-01-01')
        );

        $this->assertEquals(1, $period->getLoanId());
        $this->assertEquals(0.055, $period->getRate());
        $this->assertEquals(new DateTimeImmutable('2025-01-01'), $period->getStartDate());
        $this->assertNull($period->getEndDate());
        $this->assertNull($period->getId());
    }

    public function testConstructorWithEndDate(): void
    {
        $period = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2025-01-01'),
            endDate: new DateTimeImmutable('2025-12-31')
        );

        $this->assertEquals(new DateTimeImmutable('2025-12-31'), $period->getEndDate());
    }

    public function testConstructorThrowsOnNegativeRate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Rate must be between 0 and 1");

        new RatePeriod(
            loanId: 1,
            rate: -0.01,
            startDate: new DateTimeImmutable('2025-01-01')
        );
    }

    public function testConstructorThrowsOnRateGreaterThanOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Rate must be between 0 and 1");

        new RatePeriod(
            loanId: 1,
            rate: 1.5,
            startDate: new DateTimeImmutable('2025-01-01')
        );
    }

    public function testConstructorThrowsWhenStartDateAfterEndDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Start date cannot be after end date");

        new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2025-12-31'),
            endDate: new DateTimeImmutable('2025-01-01')
        );
    }

    public function testSetAndGetId(): void
    {
        $period = new RatePeriod(1, 0.05, new DateTimeImmutable());
        $period->setId(100);
        $this->assertEquals(100, $period->getId());
    }

    public function testIsActiveOnStartDate(): void
    {
        $period = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2025-01-01'),
            endDate: new DateTimeImmutable('2025-12-31')
        );

        $this->assertTrue($period->isActive(new DateTimeImmutable('2025-01-01')));
    }

    public function testIsActiveWithinRange(): void
    {
        $period = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2025-01-01'),
            endDate: new DateTimeImmutable('2025-12-31')
        );

        $this->assertTrue($period->isActive(new DateTimeImmutable('2025-06-15')));
    }

    public function testIsActiveOnEndDate(): void
    {
        $period = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2025-01-01'),
            endDate: new DateTimeImmutable('2025-12-31')
        );

        $this->assertTrue($period->isActive(new DateTimeImmutable('2025-12-31')));
    }

    public function testIsNotActiveBeforeStartDate(): void
    {
        $period = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2025-01-01'),
            endDate: new DateTimeImmutable('2025-12-31')
        );

        $this->assertFalse($period->isActive(new DateTimeImmutable('2024-12-31')));
    }

    public function testIsNotActiveAfterEndDate(): void
    {
        $period = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2025-01-01'),
            endDate: new DateTimeImmutable('2025-12-31')
        );

        $this->assertFalse($period->isActive(new DateTimeImmutable('2026-01-01')));
    }

    public function testIsActiveForOngoingPeriodWithNullEndDate(): void
    {
        $period = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2025-01-01')
        );

        $this->assertTrue($period->isActive(new DateTimeImmutable('2030-01-01')));
    }

    public function testMarkUpdated(): void
    {
        $period = new RatePeriod(1, 0.05, new DateTimeImmutable());
        $this->assertNull($period->getUpdatedAt());

        $period->markUpdated();
        $this->assertInstanceOf(DateTimeImmutable::class, $period->getUpdatedAt());
    }

    public function testToStringWithEndDate(): void
    {
        $period = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2025-01-01'),
            endDate: new DateTimeImmutable('2025-12-31')
        );

        $this->assertEquals("5.5% from 2025-01-01 to 2025-12-31", (string)$period);
    }

    public function testToStringWithOngoingPeriod(): void
    {
        $period = new RatePeriod(
            loanId: 1,
            rate: 0.055,
            startDate: new DateTimeImmutable('2025-01-01')
        );

        $this->assertEquals("5.5% from 2025-01-01 to ongoing", (string)$period);
    }

    public function testGetCreatedAt(): void
    {
        $period = new RatePeriod(1, 0.05, new DateTimeImmutable());
        $this->assertInstanceOf(DateTimeImmutable::class, $period->getCreatedAt());
    }

    public function testRateCanBeZero(): void
    {
        $period = new RatePeriod(
            loanId: 1,
            rate: 0.0,
            startDate: new DateTimeImmutable('2025-01-01')
        );

        $this->assertEquals(0.0, $period->getRate());
        $this->assertTrue($period->isActive(new DateTimeImmutable()));
    }
}
