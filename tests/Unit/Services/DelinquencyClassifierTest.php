<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\LoanEvent;
use Ksfraser\Amortizations\Services\DelinquencyClassifier;
use Ksfraser\Amortizations\Services\PaymentHistoryTracker;
use DateTimeImmutable;

/**
 * DelinquencyClassifierTest - TDD Test Suite
 *
 * Tests for the DelinquencyClassifier service which analyzes payment history
 * to classify loan risk, track delinquency, and recommend collection actions.
 *
 * Responsibilities:
 * - Classify loan delinquency status (current, 30/60/90+ days past due)
 * - Calculate days overdue
 * - Track consecutive missed payments
 * - Recommend collection actions based on delinquency tier
 * - Generate risk scores for loan portfolio analysis
 * - Identify patterns (chronic late payer, recent deterioration, etc.)
 *
 * Test coverage: 8 tests
 * - Classify current loans (1 test)
 * - Classify 30/60/90+ day delinquency (3 tests)
 * - Calculate days overdue (1 test)
 * - Track missed payment count (1 test)
 * - Generate collection recommendations (1 test)
 * - Identify payment patterns (1 test)
 */
class DelinquencyClassifierTest extends TestCase
{
    private $classifier;
    private $tracker;

    protected function setUp(): void
    {
        $this->tracker = new PaymentHistoryTracker();
        $this->classifier = new DelinquencyClassifier($this->tracker);
    }

    /**
     * Test 1: Classify loan as CURRENT (all payments on time)
     */
    public function testClassifyLoanAsCurrent()
    {
        $loan = $this->createTestLoan();
        
        // Record on-time payments
        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-02-01'],
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-03-01'],
        ]);

        $status = $this->classifier->classify($loan);

        $this->assertEquals('CURRENT', $status['status']);
        $this->assertEquals(0, $status['days_overdue']);
        $this->assertEquals('No action required', $status['recommendation']);
    }

    /**
     * Test 2: Classify loan as 30 DAYS PAST DUE
     */
    public function testClassify30DaysPastDue()
    {
        $loan = $this->createTestLoan();
        
        // Record payments, last one was 35 days ago (should be 30+ days overdue)
        $today = new DateTimeImmutable();
        $thirtyFiveDaysAgo = $today->modify('-35 days')->format('Y-m-d');
        
        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-02-01'],
            ['amount' => 188.71, 'status' => 'late', 'date' => $thirtyFiveDaysAgo],
        ]);

        $status = $this->classifier->classify($loan);

        $this->assertEquals('30_DAYS_PAST_DUE', $status['status']);
        $this->assertGreaterThanOrEqual(30, $status['days_overdue']);
        // Check that payment arrangement is in the recommendations array
        $recommendationText = implode(' ', $status['recommendations'] ?? []);
        $this->assertStringContainsString('payment arrangement', strtolower($recommendationText));
    }

    /**
     * Test 3: Classify loan as 60 DAYS PAST DUE
     */
    public function testClassify60DaysPastDue()
    {
        $loan = $this->createTestLoan();
        
        // Record payment from 65 days ago (should be 60+ days overdue)
        $today = new DateTimeImmutable();
        $sixtyFiveDaysAgo = $today->modify('-65 days')->format('Y-m-d');
        
        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 188.71, 'status' => 'late', 'date' => $sixtyFiveDaysAgo],
        ]);

        $status = $this->classifier->classify($loan);

        $this->assertEquals('60_DAYS_PAST_DUE', $status['status']);
        $this->assertGreaterThanOrEqual(60, $status['days_overdue']);
        // Check recommendations array for contact action
        $recommendationText = implode(' ', $status['recommendations'] ?? []);
        $this->assertStringContainsString('contact', strtolower($recommendationText));
    }

    /**
     * Test 4: Classify loan as 90+ DAYS PAST DUE
     */
    public function testClassify90DaysPastDue()
    {
        $loan = $this->createTestLoan();
        
        // Record payment from 95 days ago (should be 90+ days overdue)
        $today = new DateTimeImmutable();
        $ninetyFiveDaysAgo = $today->modify('-95 days')->format('Y-m-d');
        
        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 188.71, 'status' => 'late', 'date' => $ninetyFiveDaysAgo],
        ]);

        $status = $this->classifier->classify($loan);

        $this->assertEquals('90_PLUS_DAYS_PAST_DUE', $status['status']);
        $this->assertGreaterThanOrEqual(90, $status['days_overdue']);
        // Check recommendations array for collection action
        $recommendationText = implode(' ', $status['recommendations'] ?? []);
        $this->assertStringContainsString('collection', strtolower($recommendationText));
    }

    /**
     * Test 5: Calculate days overdue from current date
     */
    public function testCalculateDaysOverdue()
    {
        $loan = $this->createTestLoan();
        
        // Last payment was 45 days ago
        $lastPaymentDate = (new DateTimeImmutable())->modify('-45 days');
        
        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 188.71, 'status' => 'late', 'date' => $lastPaymentDate->format('Y-m-d')],
        ]);

        $daysOverdue = $this->classifier->calculateDaysOverdue($loan);

        // Should be approximately 45 days (allowing 1 day variance for test execution)
        $this->assertGreaterThanOrEqual(44, $daysOverdue);
        $this->assertLessThanOrEqual(46, $daysOverdue);
    }

    /**
     * Test 6: Count consecutive missed payments
     */
    public function testCountConsecutiveMissedPayments()
    {
        $loan = $this->createTestLoan();
        
        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 188.71, 'status' => 'late', 'date' => '2024-03-15'],
            ['amount' => 0.00, 'status' => 'missed', 'date' => '2024-04-01'],
            ['amount' => 0.00, 'status' => 'missed', 'date' => '2024-05-01'],
            ['amount' => 0.00, 'status' => 'missed', 'date' => '2024-06-01'],
        ]);

        $missedCount = $this->classifier->countMissedPayments($loan);

        $this->assertEquals(3, $missedCount);
    }

    /**
     * Test 7: Generate collection recommendations based on delinquency tier
     */
    public function testGenerateCollectionRecommendations()
    {
        $loan = $this->createTestLoan();
        
        // 125 days past due
        $today = new DateTimeImmutable();
        $oneTwentyFiveDaysAgo = $today->modify('-125 days')->format('Y-m-d');
        
        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'on_time', 'date' => '2024-01-01'],
            ['amount' => 188.71, 'status' => 'late', 'date' => $oneTwentyFiveDaysAgo],
        ]);

        $status = $this->classifier->classify($loan);
        $recommendations = $status['recommendations'] ?? [];

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        // Should include collection-level actions
        $recommendationText = implode(' ', $recommendations);
        $this->assertTrue(
            strpos(strtolower($recommendationText), 'collection') !== false ||
            strpos(strtolower($recommendationText), 'attorney') !== false ||
            strpos(strtolower($recommendationText), 'legal') !== false
        );
    }

    /**
     * Test 8: Identify payment patterns (chronic late payer, recent deterioration)
     */
    public function testIdentifyPaymentPatterns()
    {
        $loan = $this->createTestLoan();
        
        // Chronic late payer pattern
        $this->recordPayments($loan->getId(), [
            ['amount' => 188.71, 'status' => 'late', 'date' => '2024-01-15'],
            ['amount' => 188.71, 'status' => 'late', 'date' => '2024-02-15'],
            ['amount' => 188.71, 'status' => 'late', 'date' => '2024-03-15'],
            ['amount' => 188.71, 'status' => 'late', 'date' => '2024-04-15'],
        ]);

        $pattern = $this->classifier->identifyPaymentPattern($loan);

        $this->assertIsArray($pattern);
        $this->assertArrayHasKey('pattern_type', $pattern);
        $this->assertEquals('CHRONIC_LATE', $pattern['pattern_type']);
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
}
