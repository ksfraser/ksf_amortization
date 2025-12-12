<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\LoanEvent;
use Ksfraser\Amortizations\Services\PaymentHistoryTracker;
use DateTimeImmutable;

/**
 * PaymentHistoryTrackerTest - TDD Test Suite
 *
 * Tests for the PaymentHistoryTracker service which maintains a comprehensive
 * audit trail of all payments and events related to a loan.
 *
 * Responsibilities:
 * - Record all payment events (regular, extra, skip, etc.)
 * - Track payment status (on-time, late, partial, etc.)
 * - Calculate cumulative statistics (total paid, total interest, etc.)
 * - Query history by date range or event type
 * - Provide analytics data for reporting
 *
 * Test coverage: 9 tests
 * - Record payment events (1 test)
 * - Query by date range (1 test)
 * - Statistics calculation (2 tests)
 * - Payment status tracking (1 test)
 * - Multiple events aggregation (1 test)
 * - Empty history handling (1 test)
 * - Event filtering (1 test)
 * - Metadata preservation (1 test)
 */
class PaymentHistoryTrackerTest extends TestCase
{
    private $tracker;

    protected function setUp(): void
    {
        $this->tracker = new PaymentHistoryTracker();
    }

    /**
     * Test 1: Record a single payment event
     */
    public function testRecordSinglePaymentEvent()
    {
        $loan = $this->createTestLoan();
        $event = $this->createPaymentEvent(188.71, 'on_time');

        $this->tracker->recordEvent($loan->getId(), $event);

        $history = $this->tracker->getHistory($loan->getId());
        $this->assertCount(1, $history);
        $this->assertEquals('on_time', $history[0]['status']);
    }

    /**
     * Test 2: Query history by date range
     */
    public function testQueryHistoryByDateRange()
    {
        $loan = $this->createTestLoan();

        // Record events on different dates
        $event1 = $this->createPaymentEvent(188.71, 'on_time', '2024-01-01');
        $event2 = $this->createPaymentEvent(188.71, 'on_time', '2024-02-01');
        $event3 = $this->createPaymentEvent(188.71, 'late', '2024-03-01');

        $this->tracker->recordEvent($loan->getId(), $event1);
        $this->tracker->recordEvent($loan->getId(), $event2);
        $this->tracker->recordEvent($loan->getId(), $event3);

        // Query only February events
        $startDate = new DateTimeImmutable('2024-02-01');
        $endDate = new DateTimeImmutable('2024-02-28');
        $history = $this->tracker->getHistoryByDateRange($loan->getId(), $startDate, $endDate);

        $this->assertCount(1, $history);
        $this->assertEquals('2024-02-01', $history[0]['event_date_str']);
    }

    /**
     * Test 3: Calculate total paid
     */
    public function testCalculateTotalPaid()
    {
        $loan = $this->createTestLoan();

        $event1 = $this->createPaymentEvent(188.71, 'on_time');
        $event2 = $this->createPaymentEvent(188.71, 'on_time');
        $event3 = $this->createPaymentEvent(500.00, 'extra_payment');

        $this->tracker->recordEvent($loan->getId(), $event1);
        $this->tracker->recordEvent($loan->getId(), $event2);
        $this->tracker->recordEvent($loan->getId(), $event3);

        $stats = $this->tracker->getStatistics($loan->getId());

        $expectedTotal = 188.71 + 188.71 + 500.00;
        $this->assertAlmostEquals($expectedTotal, $stats['total_paid'], 2);
    }

    /**
     * Test 4: Calculate average payment amount
     */
    public function testCalculateAveragePayment()
    {
        $loan = $this->createTestLoan();

        $event1 = $this->createPaymentEvent(188.71, 'on_time');
        $event2 = $this->createPaymentEvent(188.71, 'on_time');
        $event3 = $this->createPaymentEvent(200.00, 'on_time');

        $this->tracker->recordEvent($loan->getId(), $event1);
        $this->tracker->recordEvent($loan->getId(), $event2);
        $this->tracker->recordEvent($loan->getId(), $event3);

        $stats = $this->tracker->getStatistics($loan->getId());

        $expectedAverage = (188.71 + 188.71 + 200.00) / 3;
        $this->assertAlmostEquals($expectedAverage, $stats['average_payment'], 2);
    }

    /**
     * Test 5: Track payment status (on-time vs late)
     */
    public function testTrackPaymentStatus()
    {
        $loan = $this->createTestLoan();

        $onTimeEvent = $this->createPaymentEvent(188.71, 'on_time');
        $lateEvent = $this->createPaymentEvent(188.71, 'late', '2024-02-15');
        $partialEvent = $this->createPaymentEvent(100.00, 'partial');

        $this->tracker->recordEvent($loan->getId(), $onTimeEvent);
        $this->tracker->recordEvent($loan->getId(), $lateEvent);
        $this->tracker->recordEvent($loan->getId(), $partialEvent);

        $stats = $this->tracker->getStatistics($loan->getId());

        $this->assertEquals(1, $stats['on_time_count'] ?? 0);
        $this->assertEquals(1, $stats['late_count'] ?? 0);
        $this->assertEquals(1, $stats['partial_count'] ?? 0);
    }

    /**
     * Test 6: Handle empty history gracefully
     */
    public function testEmptyHistoryReturnsEmptyArray()
    {
        $loan = $this->createTestLoan();

        $history = $this->tracker->getHistory($loan->getId());

        $this->assertIsArray($history);
        $this->assertCount(0, $history);
    }

    /**
     * Test 7: Filter history by event type
     */
    public function testFilterHistoryByEventType()
    {
        $loan = $this->createTestLoan();

        // Create different event types
        $regularPayment = $this->createPaymentEvent(188.71, 'on_time');
        $extraPayment = new LoanEvent([
            'loan_id' => $loan->getId(),
            'event_type' => 'extra_payment',
            'amount' => 500.00,
            'event_date' => new DateTimeImmutable('2024-01-15')
        ]);
        $skipPayment = new LoanEvent([
            'loan_id' => $loan->getId(),
            'event_type' => 'skip_payment',
            'amount' => 1,
            'event_date' => new DateTimeImmutable('2024-02-01')
        ]);

        $this->tracker->recordEvent($loan->getId(), $regularPayment);
        $this->tracker->recordEvent($loan->getId(), $extraPayment);
        $this->tracker->recordEvent($loan->getId(), $skipPayment);

        // Query only extra payments
        $extraPayments = $this->tracker->getHistoryByEventType($loan->getId(), 'extra_payment');

        $this->assertCount(1, $extraPayments);
        $this->assertEquals('extra_payment', $extraPayments[0]['event_type']);
    }

    /**
     * Test 8: Record and preserve metadata for each event
     */
    public function testPreserveEventMetadata()
    {
        $loan = $this->createTestLoan();

        // Create event with metadata in notes
        $event = new LoanEvent([
            'loan_id' => $loan->getId(),
            'event_type' => 'regular_payment',
            'amount' => 188.71,
            'event_date' => new DateTimeImmutable('2024-01-01'),
            'notes' => json_encode(['status' => 'on_time', 'processed_by' => 'admin'])
        ]);

        $this->tracker->recordEvent($loan->getId(), $event);

        $history = $this->tracker->getHistory($loan->getId());

        $this->assertCount(1, $history);
        $this->assertEquals('on_time', $history[0]['status'] ?? null);
    }

    /**
     * Test 9: Multiple loans have separate history
     */
    public function testMultipleLoansHaveSeparateHistory()
    {
        $loan1 = $this->createTestLoan();
        $loan1->setId(1);
        $loan2 = $this->createTestLoan();
        $loan2->setId(2);

        $event1 = $this->createPaymentEvent(188.71, 'on_time');
        $event2 = $this->createPaymentEvent(200.00, 'on_time');

        $this->tracker->recordEvent($loan1->getId(), $event1);
        $this->tracker->recordEvent($loan2->getId(), $event2);

        $history1 = $this->tracker->getHistory($loan1->getId());
        $history2 = $this->tracker->getHistory($loan2->getId());

        $this->assertCount(1, $history1);
        $this->assertCount(1, $history2);
        $this->assertNotEquals($history1[0]['amount'], $history2[0]['amount']);
    }

    // ============ Helper Methods ============

    private function createTestLoan(): Loan
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(10000.00);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(10000.00);
        return $loan;
    }

    private function createPaymentEvent(
        float $amount,
        string $status,
        string $dateString = '2024-01-01'
    ): LoanEvent {
        return new LoanEvent([
            'loan_id' => 1,
            'event_type' => 'regular_payment',
            'amount' => $amount,
            'event_date' => new DateTimeImmutable($dateString),
            'notes' => json_encode(['status' => $status])
        ]);
    }

    /**
     * Assert two floats are approximately equal
     */
    private function assertAlmostEquals($expected, $actual, $decimals = 2)
    {
        $this->assertEquals(
            round($expected, $decimals),
            round($actual, $decimals),
            "Values differ by more than .$decimals places"
        );
    }
}
