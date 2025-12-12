<?php

namespace Tests\Unit\EventHandlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\EventHandlers\GracePeriodHandler;
use DateTimeImmutable;

/**
 * GracePeriodHandlerTest
 *
 * Tests for grace period (initial deferral) event handling.
 * Grace periods allow borrowers to skip initial payments while loan accrues interest.
 *
 * Uses TDD Red-Green-Refactor cycle.
 *
 * @covers \Ksfraser\Amortizations\EventHandlers\GracePeriodHandler
 */
class GracePeriodHandlerTest extends TestCase
{
    private GracePeriodHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new GracePeriodHandler();
    }

    /**
     * Test that handler supports grace period events.
     *
     * @test
     */
    public function testSupportsGracePeriodEvents(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $this->assertTrue(
            $this->handler->supportsEventType('grace_period'),
            'Handler should support grace_period event type'
        );
    }

    /**
     * Test that handler rejects non-grace-period events.
     *
     * @test
     */
    public function testRejectsOtherEventTypes(): void
    {
        $this->assertFalse(
            $this->handler->supportsEventType('skip_payment'),
            'Handler should reject skip_payment events'
        );
        $this->assertFalse(
            $this->handler->supportsEventType('extra_payment'),
            'Handler should reject extra_payment events'
        );
    }

    /**
     * Test that grace period extends loan term.
     *
     * Original: 60 months
     * Grace period: 6 months
     * Result: 66 months
     *
     * @test
     */
    public function testGracePeriodExtendsLoanTerm(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $result = $this->handler->applyGracePeriod($loan, 6);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('months_after_grace', $result);
        $this->assertEquals(66, $result['months_after_grace'], 'Grace period should extend loan term');
    }

    /**
     * Test that grace period accrues interest without reducing principal.
     *
     * Principal: $50,000
     * Rate: 5% annual = 0.4167% monthly
     * Grace: 6 months
     * Interest accrued: $50,000 × 0.004167 × 6 ≈ $1,250
     *
     * @test
     */
    public function testGracePeriodAccruesInterest(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $result = $this->handler->applyGracePeriod($loan, 6);

        $this->assertArrayHasKey('accrued_interest', $result);
        $this->assertGreaterThan(1200, $result['accrued_interest']);
        $this->assertLessThan(1300, $result['accrued_interest']);
    }

    /**
     * Test grace period with 0% interest rate.
     *
     * @test
     */
    public function testGracePeriodWithZeroInterest(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.0);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $result = $this->handler->applyGracePeriod($loan, 6);

        $this->assertEquals(0, $result['accrued_interest']);
        $this->assertEquals(66, $result['months_after_grace']);
    }

    /**
     * Test that grace period rejects negative months.
     *
     * @test
     */
    public function testRejectsNegativeGracePeriod(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $this->handler->applyGracePeriod($loan, -1);
    }

    /**
     * Test that grace period rejects zero months.
     *
     * @test
     */
    public function testRejectsZeroGracePeriod(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $this->handler->applyGracePeriod($loan, 0);
    }

    /**
     * Test that grace period handler has correct priority.
     *
     * Grace period should be applied early (before regular payment scheduling).
     * Priority: High (execute first in event chain)
     *
     * @test
     */
    public function testHandlerHasCorrectPriority(): void
    {
        $this->assertTrue(
            method_exists($this->handler, 'getPriority'),
            'Handler should have getPriority method'
        );
        $priority = $this->handler->getPriority();
        $this->assertIsInt($priority);
        $this->assertLessThan(100, $priority, 'Grace period should have high priority');
    }

    /**
     * Test grace period metadata is properly structured.
     *
     * @test
     */
    public function testGracePeriodMetadata(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $result = $this->handler->applyGracePeriod($loan, 3);

        $this->assertArrayHasKey('event_type', $result);
        $this->assertEquals('grace_period', $result['event_type']);
        $this->assertArrayHasKey('grace_months', $result);
        $this->assertEquals(3, $result['grace_months']);
        $this->assertArrayHasKey('start_date', $result);
        $this->assertArrayHasKey('end_date', $result);
    }

    /**
     * Test grace period with large number of months.
     *
     * @test
     */
    public function testLargeGracePeriod(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $result = $this->handler->applyGracePeriod($loan, 24);

        $this->assertEquals(84, $result['months_after_grace']);
        $this->assertGreaterThanOrEqual(4990, $result['accrued_interest']);
        $this->assertLessThan(5100, $result['accrued_interest']);
    }

    /**
     * Test that grace period properly calculates end date.
     *
     * Start: 2024-01-01
     * Grace: 3 months
     * End: 2024-04-01
     *
     * @test
     */
    public function testGracePeriodEndDate(): void
    {
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        $result = $this->handler->applyGracePeriod($loan, 3);

        $startDate = new DateTimeImmutable($result['start_date']);
        $endDate = new DateTimeImmutable($result['end_date']);
        $expectedEnd = $startDate->modify('+3 months');

        $this->assertEquals($expectedEnd->format('Y-m-d'), $endDate->format('Y-m-d'));
    }
}
