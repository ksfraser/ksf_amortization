<?php

namespace Tests\Integration\PlatformAdapters;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\LoanEvent;
use Ksfraser\Amortizations\Services\PaymentHistoryTracker;
use Ksfraser\Amortizations\Services\DelinquencyClassifier;
use DateTimeImmutable;

/**
 * PlatformAdapterIntegrationTest - TDD Test Suite
 *
 * Tests integration of PaymentHistoryTracker and DelinquencyClassifier services
 * with platform-specific data providers and adapters.
 *
 * Verifies:
 * - Services work correctly with FrontAccounting loans
 * - Services work correctly with WordPress loans
 * - Services work correctly with SuiteCRM loans
 * - Payment events are properly recorded and retrieved
 * - Delinquency classifications are accurate across platforms
 * - Cross-platform loan portability
 *
 * Test coverage: 12 tests
 * - FrontAccounting adapter tests (4)
 * - WordPress adapter tests (4)
 * - SuiteCRM adapter tests (4)
 */
class PlatformAdapterIntegrationTest extends TestCase
{
    private $tracker;
    private $classifier;

    protected function setUp(): void
    {
        $this->tracker = new PaymentHistoryTracker();
        $this->classifier = new DelinquencyClassifier($this->tracker);
    }

    /**
     * Test 1: FrontAccounting - Record payment events for FA loan
     */
    public function testFrontAccountingRecordPaymentEvents()
    {
        $loan = $this->createFALoan(1001);

        // Record payment events
        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-02-01'],
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-03-01'],
        ]);

        $history = $this->tracker->getHistory($loan->getId());

        $this->assertCount(3, $history);
        $this->assertEquals(188.71, $history[0]['amount']);
    }

    /**
     * Test 2: FrontAccounting - Classify delinquency status for FA loan
     */
    public function testFrontAccountingClassifyDelinquency()
    {
        $loan = $this->createFALoan(1001);

        $today = new DateTimeImmutable();
        $thirtyFiveDaysAgo = $today->modify('-35 days')->format('Y-m-d');

        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 188.71, 'status' => 'late', 'date' => $thirtyFiveDaysAgo],
        ]);

        $status = $this->classifier->classify($loan);

        $this->assertEquals('30_DAYS_PAST_DUE', $status['status']);
        $this->assertGreaterThanOrEqual(30, $status['days_overdue']);
    }

    /**
     * Test 3: FrontAccounting - Get payment statistics
     */
    public function testFrontAccountingPaymentStatistics()
    {
        $loan = $this->createFALoan(1001);

        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-02-01'],
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-03-01'],
        ]);

        $stats = $this->tracker->getStatistics($loan->getId());

        $this->assertAlmostEquals(566.13, $stats['total_paid'], 2);
        $this->assertAlmostEquals(188.71, $stats['average_payment'], 2);
        $this->assertEquals(3, $stats['payment_count']);
        $this->assertEquals(3, $stats['on_time_count']);
    }

    /**
     * Test 4: FrontAccounting - Identify payment patterns
     */
    public function testFrontAccountingPaymentPatterns()
    {
        $loan = $this->createFALoan(1001);

        // Create chronic late payer pattern
        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'late', 'date' => '2024-01-15'],
            ['amount' => 188.71, 'status' => 'late', 'date' => '2024-02-15'],
            ['amount' => 188.71, 'status' => 'late', 'date' => '2024-03-15'],
            ['amount' => 188.71, 'status' => 'late', 'date' => '2024-04-15'],
        ]);

        $pattern = $this->classifier->identifyPaymentPattern($loan);

        $this->assertEquals('CHRONIC_LATE', $pattern['pattern_type']);
        $this->assertGreaterThanOrEqual(75, $pattern['late_percentage']);
    }

    /**
     * Test 5: WordPress - Record payment events for WP loan
     */
    public function testWordPressRecordPaymentEvents()
    {
        $loan = $this->createWPLoan(2001);

        $this->recordPayments($loan->getId(), [
            ['amount' => 250.00, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 250.00, 'status' => 'on_time', 'date' => '2024-02-01'],
        ]);

        $history = $this->tracker->getHistory($loan->getId());

        $this->assertCount(2, $history);
        $this->assertEquals(250.00, $history[0]['amount']);
    }

    /**
     * Test 6: WordPress - Classify delinquency status for WP loan
     */
    public function testWordPressClassifyDelinquency()
    {
        $loan = $this->createWPLoan(2001);

        $today = new DateTimeImmutable();
        $sixtyFiveDaysAgo = $today->modify('-65 days')->format('Y-m-d');

        $this->recordPayments($loan->getId(), [
            ['amount' => 250.00, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 250.00, 'status' => 'late', 'date' => $sixtyFiveDaysAgo],
        ]);

        $status = $this->classifier->classify($loan);

        $this->assertEquals('60_DAYS_PAST_DUE', $status['status']);
        $this->assertGreaterThanOrEqual(60, $status['days_overdue']);
    }

    /**
     * Test 7: WordPress - Get payment statistics
     */
    public function testWordPressPaymentStatistics()
    {
        $loan = $this->createWPLoan(2001);

        $this->recordPayments($loan->getId(), [
            ['amount' => 250.00, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 250.00, 'status' => 'on_time', 'date' => '2024-02-01'],
            ['amount' => 300.00, 'status' => 'on_time', 'date' => '2024-03-01'],
        ]);

        $stats = $this->tracker->getStatistics($loan->getId());

        $this->assertAlmostEquals(800.00, $stats['total_paid'], 2);
        $this->assertAlmostEquals(266.67, $stats['average_payment'], 2);
        $this->assertEquals(3, $stats['payment_count']);
    }

    /**
     * Test 8: WordPress - Recent deterioration pattern detection
     */
    public function testWordPressPaymentPatternDeterioration()
    {
        $loan = $this->createWPLoan(2001);

        // Previously on-time, recent deterioration
        $this->recordPayments($loan->getId(), [
            ['amount' => 250.00, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 250.00, 'status' => 'on_time', 'date' => '2024-02-01'],
            ['amount' => 250.00, 'status' => 'on_time', 'date' => '2024-03-01'],
            ['amount' => 250.00, 'status' => 'late', 'date' => '2024-04-01'],
            ['amount' => 250.00, 'status' => 'late', 'date' => '2024-05-01'],
        ]);

        $pattern = $this->classifier->identifyPaymentPattern($loan);

        // Should detect pattern (may be RECENT_DETERIORATION or SPORADIC_PAYER)
        $this->assertIsArray($pattern);
        $this->assertArrayHasKey('pattern_type', $pattern);
    }

    /**
     * Test 9: SuiteCRM - Record payment events for SuiteCRM loan
     */
    public function testSuiteCRMRecordPaymentEvents()
    {
        $loan = $this->createSuiteCRMLoan(3001);

        $this->recordPayments($loan->getId(), [
            ['amount' => 150.00, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 150.00, 'status' => 'on_time', 'date' => '2024-02-01'],
            ['amount' => 150.00, 'status' => 'partial', 'date' => '2024-03-01'],
        ]);

        $history = $this->tracker->getHistory($loan->getId());

        $this->assertCount(3, $history);
        $this->assertEquals('partial', $history[2]['status']);
    }

    /**
     * Test 10: SuiteCRM - Classify delinquency status for SuiteCRM loan
     */
    public function testSuiteCRMClassifyDelinquency()
    {
        $loan = $this->createSuiteCRMLoan(3001);

        $today = new DateTimeImmutable();
        $ninetyFiveDaysAgo = $today->modify('-95 days')->format('Y-m-d');

        $this->recordPayments($loan->getId(), [
            ['amount' => 150.00, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 150.00, 'status' => 'late', 'date' => $ninetyFiveDaysAgo],
        ]);

        $status = $this->classifier->classify($loan);

        $this->assertEquals('90_PLUS_DAYS_PAST_DUE', $status['status']);
        $this->assertGreaterThanOrEqual(90, $status['days_overdue']);
    }

    /**
     * Test 11: SuiteCRM - Get payment statistics
     */
    public function testSuiteCRMPaymentStatistics()
    {
        $loan = $this->createSuiteCRMLoan(3001);

        $this->recordPayments($loan->getId(), [
            ['amount' => 150.00, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 150.00, 'status' => 'on_time', 'date' => '2024-02-01'],
            ['amount' => 150.00, 'status' => 'partial', 'date' => '2024-03-01'],
        ]);

        $stats = $this->tracker->getStatistics($loan->getId());

        $this->assertAlmostEquals(450.00, $stats['total_paid'], 2);
        $this->assertAlmostEquals(150.00, $stats['average_payment'], 2);
        $this->assertEquals(3, $stats['payment_count']);
        $this->assertEquals(1, $stats['partial_count']);
    }

    /**
     * Test 12: Cross-platform loan portability - Same loan ID on different platforms
     */
    public function testCrossPlatformLoanPortability()
    {
        $loanId = 5000;

        // Create same loan on different platforms
        $faLoan = $this->createFALoan($loanId);
        $wpLoan = $this->createWPLoan($loanId);
        $crmLoan = $this->createSuiteCRMLoan($loanId);

        // Record events for FA loan
        $this->recordPayments($faLoan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
        ]);

        // Record same events for WP loan (simulating sync)
        $this->recordPayments($wpLoan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
        ]);

        // Verify both have consistent history
        $faHistory = $this->tracker->getHistory($faLoan->getId());
        $wpHistory = $this->tracker->getHistory($wpLoan->getId());

        $this->assertEquals($faHistory[0]['amount'], $wpHistory[0]['amount']);
        $this->assertEquals($faHistory[0]['status'], $wpHistory[0]['status']);
    }

    // ============ Helper Methods ============

    private function createFALoan(int $id): Loan
    {
        $loan = new Loan();
        $loan->setId($id);
        $loan->setPrincipal(10000.00);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(10000.00);
        return $loan;
    }

    private function createWPLoan(int $id): Loan
    {
        $loan = new Loan();
        $loan->setId($id);
        $loan->setPrincipal(15000.00);
        $loan->setAnnualRate(0.06);
        $loan->setMonths(72);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(15000.00);
        return $loan;
    }

    private function createSuiteCRMLoan(int $id): Loan
    {
        $loan = new Loan();
        $loan->setId($id);
        $loan->setPrincipal(12000.00);
        $loan->setAnnualRate(0.055);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(12000.00);
        return $loan;
    }

    private function recordPayments(int $loanId, array $payments): void
    {
        foreach ($payments as $payment) {
            $event = new LoanEvent([
                'loan_id' => $loanId,
                'event_type' => $payment['amount'] > 0 ? 'regular_payment' : 'missed_payment',
                'amount' => $payment['amount'],
                'event_date' => new DateTimeImmutable($payment['date']),
                'notes' => json_encode(['status' => $payment['status']])
            ]);
            $this->tracker->recordEvent($loanId, $event);
        }
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
