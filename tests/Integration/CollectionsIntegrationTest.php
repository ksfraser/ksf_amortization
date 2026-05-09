<?php

declare(strict_types=1);

namespace Tests\Integration;

use DateTimeImmutable;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\LoanEvent;
use Ksfraser\Amortizations\Repositories\PdoDelinquencyRepository;
use Ksfraser\Amortizations\Services\CollectionWorkflowService;
use Ksfraser\Amortizations\Services\DelinquencyClassifier;
use Ksfraser\Amortizations\Services\PaymentHistoryTracker;
use PHPUnit\Framework\TestCase;
use PDO;

/**
 * Collections Integration Test
 *
 * Tests the complete collections workflow including:
 * - Delinquency classification
 * - Persistence to database
 * - Collection action creation
 * - Payment arrangement creation
 *
 * @group collections
 * @group integration
 */
class CollectionsIntegrationTest extends TestCase
{
    /** @var PDO */
    private PDO $pdo;

    /** @var PdoDelinquencyRepository */
    private PdoDelinquencyRepository $repository;

    /** @var DelinquencyClassifier */
    private DelinquencyClassifier $classifier;

    /** @var PaymentHistoryTracker */
    private PaymentHistoryTracker $tracker;

    /** @var CollectionWorkflowService */
    private CollectionWorkflowService $workflowService;

    protected function setUp(): void
    {
        // Initialize in-memory SQLite
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->sqliteCreateFunction('CURDATE', static fn (): string => date('Y-m-d'));

        // Create delinquency tables
        $this->createDelinquencyTables();

        // Initialize repository
        $this->repository = new PdoDelinquencyRepository($this->pdo, 'ksf_');

        // Initialize services
        $this->tracker = new PaymentHistoryTracker();
        $this->classifier = new DelinquencyClassifier($this->tracker);
        $this->workflowService = new CollectionWorkflowService($this->repository);
    }

    /**
     * Create the delinquency schema tables for in-memory SQLite
     */
    private function createDelinquencyTables(): void
    {
        $sql = [
            // Minimal loans table for foreign key compatibility
            "CREATE TABLE IF NOT EXISTS [ksf_loans] (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                loan_number TEXT NOT NULL UNIQUE,
                borrower_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT 'ACTIVE'
            )",

            // Delinquency Status Table
            "CREATE TABLE IF NOT EXISTS [ksf_delinquency_status] (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                loan_id INTEGER UNIQUE NOT NULL,
                status TEXT NOT NULL DEFAULT 'CURRENT',
                days_overdue INTEGER NOT NULL DEFAULT 0,
                missed_payments INTEGER NOT NULL DEFAULT 0,
                risk_score REAL NOT NULL DEFAULT 0.0,
                risk_level TEXT NOT NULL DEFAULT 'LOW',
                pattern_type TEXT,
                trend TEXT,
                on_time_percentage REAL NOT NULL DEFAULT 100.0,
                late_percentage REAL NOT NULL DEFAULT 0.0,
                missed_percentage REAL NOT NULL DEFAULT 0.0,
                next_action_date TEXT,
                last_action TEXT,
                last_action_date TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            // Collection Actions Table
            "CREATE TABLE IF NOT EXISTS [ksf_collection_actions] (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                loan_id INTEGER NOT NULL,
                action_type TEXT NOT NULL,
                description TEXT,
                action_date TEXT,
                due_date TEXT,
                result TEXT NOT NULL DEFAULT 'pending',
                notes TEXT,
                assigned_to TEXT,
                next_action TEXT,
                completed_at TEXT,
                FOREIGN KEY (loan_id) REFERENCES [ksf_loans](id) ON DELETE CASCADE
            )",

            // Payment Arrangements Table
            "CREATE TABLE IF NOT EXISTS [ksf_payment_arrangement] (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                loan_id INTEGER NOT NULL UNIQUE,
                arrangement_type TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                start_date TEXT,
                end_date TEXT,
                modified_payment REAL,
                modified_term INTEGER,
                description TEXT,
                created_date TEXT,
                created_by TEXT,
                FOREIGN KEY (loan_id) REFERENCES [ksf_loans](id) ON DELETE CASCADE
            )",
        ];

        foreach ($sql as $statement) {
            $this->pdo->exec($statement);
        }
    }

    /**
     * Test: Full pipeline from delinquent loan to collection action
     *
     * Scenario:
     * 1. Create a 40+ day late payment
     * 2. Classify the loan as delinquent
     * 3. Persist the classification
     * 4. Trigger collection action creation
     * 5. Verify action was persisted
     */
    public function testFullCollectionsWorkflowFor30DaysPastDue(): void
    {
        // Setup: Create loan in database
        $loanId = 1;
        $this->insertTestLoan($loanId);

        // Step 1: Build payment history showing 40 days late
        $today = new DateTimeImmutable();
        $this->recordPayments($loanId, [
            ['amount' => 1000.0, 'status' => 'on_time', 'date' => $today->modify('-60 days')->format('Y-m-d')],
            ['amount' => 0.0, 'status' => 'missed', 'date' => $today->modify('-45 days')->format('Y-m-d')],
            ['amount' => 500.0, 'status' => 'late', 'date' => $today->modify('-40 days')->format('Y-m-d')],
        ]);

        // Step 2: Classify the loan
        $classification = $this->classifier->classify($this->createTestLoan($loanId));

        // Verify classification
        $this->assertEquals('30_DAYS_PAST_DUE', $classification['status']);
        $this->assertGreaterThanOrEqual(39, $classification['days_overdue']);
        $this->assertEquals(1, $classification['missed_payments']);

        // Step 3: Save classification to database
        $savedId = $this->repository->saveDelinquencyStatus($loanId, $classification);
        $this->assertIsInt($savedId);

        // Verify persistence
        $retrieved = $this->repository->getDelinquencyStatus($loanId);
        $this->assertNotNull($retrieved);
        $this->assertEquals('30_DAYS_PAST_DUE', $retrieved['status']);
        $this->assertGreaterThanOrEqual(39, $retrieved['days_overdue']);

        // Step 4: Trigger collection action
        $actionResult = $this->workflowService->createNextAction($loanId, 'collections_queue');

        // Verify action was created
        $this->assertTrue($actionResult['created']);
        $this->assertEquals('courtesy_reminder', $actionResult['action_type']);
        $this->assertIsInt($actionResult['id']);

        // Step 5: Retrieve and verify persisted action
        $allActions = $this->getPersistedActions($loanId);
        $this->assertCount(1, $allActions);
        $this->assertEquals('courtesy_reminder', $allActions[0]['action_type']);
        $this->assertEquals('pending', $allActions[0]['result']);
    }

    /**
     * Test: Cured account (recent on-time payment) skips collection action
     *
     * Scenario:
     * 1. Create history with old missed/late payments
     * 2. Add recent on-time payment (cures the delinquency)
     * 3. Classify as CURRENT
     * 4. Attempt to create collection action
     * 5. Verify action was NOT created (CURRENT loans skip actions)
     */
    public function testCuredAccountDoesNotTriggerCollectionAction(): void
    {
        // Setup
        $loanId = 2;
        $this->insertTestLoan($loanId);

        // Build history: old late payment + recent on-time cure
        $today = new DateTimeImmutable();
        $this->recordPayments($loanId, [
            ['amount' => 1000.0, 'status' => 'on_time', 'date' => $today->modify('-60 days')->format('Y-m-d')],
            ['amount' => 0.0, 'status' => 'missed', 'date' => $today->modify('-40 days')->format('Y-m-d')],
            ['amount' => 500.0, 'status' => 'late', 'date' => $today->modify('-35 days')->format('Y-m-d')],
            ['amount' => 1000.0, 'status' => 'on_time', 'date' => $today->modify('-5 days')->format('Y-m-d')],
        ]);

        // Classify
        $classification = $this->classifier->classify($this->createTestLoan($loanId));

        // Verify cured status
        $this->assertEquals('CURRENT', $classification['status']);
        $this->assertEquals(0, $classification['days_overdue']);

        // Save and verify
        $this->repository->saveDelinquencyStatus($loanId, $classification);
        $retrieved = $this->repository->getDelinquencyStatus($loanId);
        $this->assertEquals('CURRENT', $retrieved['status']);

        // Attempt collection action
        $actionResult = $this->workflowService->createNextAction($loanId);

        // Verify action was NOT created for CURRENT loans
        $this->assertFalse($actionResult['created']);

        // Verify no actions persisted
        $allActions = $this->getPersistedActions($loanId);
        $this->assertEmpty($allActions);
    }

    /**
     * Test: 60+ days past due triggers payment arrangement offer
     *
     * Scenario:
     * 1. Create 65-day late payment scenario
     * 2. Classify as 60_DAYS_PAST_DUE
     * 3. Persist classification
     * 4. Create collection action (direct_contact)
     * 5. Offer payment arrangement
     * 6. Verify arrangement persisted
     */
    public function testPaymentArrangementForSixtyDaysPastDue(): void
    {
        // Setup
        $loanId = 3;
        $this->insertTestLoan($loanId);

        // Build history: 65 days late (60-89 range)
        $today = new DateTimeImmutable();
        $this->recordPayments($loanId, [
            ['amount' => 1000.0, 'status' => 'on_time', 'date' => $today->modify('-90 days')->format('Y-m-d')],
            ['amount' => 0.0, 'status' => 'missed', 'date' => $today->modify('-70 days')->format('Y-m-d')],
            ['amount' => 500.0, 'status' => 'late', 'date' => $today->modify('-65 days')->format('Y-m-d')],
        ]);

        // Classify
        $classification = $this->classifier->classify($this->createTestLoan($loanId));

        // Verify 60_DAYS_PAST_DUE classification
        $this->assertEquals('60_DAYS_PAST_DUE', $classification['status']);
        $this->assertGreaterThanOrEqual(64, $classification['days_overdue']);

        // Persist
        $this->repository->saveDelinquencyStatus($loanId, $classification);

        // Create action
        $actionResult = $this->workflowService->createNextAction($loanId, 'senior_collections');
        $this->assertTrue($actionResult['created']);
        $this->assertEquals('direct_contact', $actionResult['action_type']);

        // Offer payment arrangement
        $arrangementResult = $this->workflowService->createPaymentArrangement(
            $loanId,
            ['arrangement_type' => 'modified_payment', 'modified_payment' => 450.0, 'duration_days' => 90]
        );
        $this->assertTrue($arrangementResult['created']);

        // Verify arrangement persisted
        $arrangement = $this->getPersistedArrangement($loanId);
        $this->assertNotNull($arrangement);
        $this->assertEquals('modified_payment', $arrangement['arrangement_type']);
    }

    /**
     * Test: Portfolio batch processing triggers actions for all due loans
     *
     * Scenario:
     * 1. Create 3 loans with different delinquency states
     * 2. Classify all loans
     * 3. Run batch processing
     * 4. Verify only delinquent loans get actions
     */
    public function testBatchProcessingCreatesActionsForDueLoans(): void
    {
        // Setup: Create 3 loans
        $loan1Id = 4; // Will be current
        $loan2Id = 5; // Will be 30+ days late
        $loan3Id = 6; // Will be current

        $this->insertTestLoan($loan1Id);
        $this->insertTestLoan($loan2Id);
        $this->insertTestLoan($loan3Id);

        // Loan 1: Current (on-time payments)
        $today = new DateTimeImmutable();
        $this->recordPayments($loan1Id, [
            ['amount' => 1000.0, 'status' => 'on_time', 'date' => $today->modify('-40 days')->format('Y-m-d')],
            ['amount' => 1000.0, 'status' => 'on_time', 'date' => $today->modify('-5 days')->format('Y-m-d')],
        ]);
        $classification1 = $this->classifier->classify($this->createTestLoan($loan1Id));
        $this->repository->saveDelinquencyStatus($loan1Id, $classification1);

        // Loan 2: 35 days late (actionable)
        $this->recordPayments($loan2Id, [
            ['amount' => 1000.0, 'status' => 'on_time', 'date' => $today->modify('-70 days')->format('Y-m-d')],
            ['amount' => 500.0, 'status' => 'late', 'date' => $today->modify('-35 days')->format('Y-m-d')],
        ]);
        $classification2 = $this->classifier->classify($this->createTestLoan($loan2Id));
        $this->repository->saveDelinquencyStatus($loan2Id, $classification2);

        // Loan 3: Current (just paid on time)
        $this->recordPayments($loan3Id, [
            ['amount' => 1000.0, 'status' => 'on_time', 'date' => $today->modify('-50 days')->format('Y-m-d')],
            ['amount' => 1000.0, 'status' => 'on_time', 'date' => $today->modify('-1 days')->format('Y-m-d')],
        ]);
        $classification3 = $this->classifier->classify($this->createTestLoan($loan3Id));
        $this->repository->saveDelinquencyStatus($loan3Id, $classification3);

        // Force all loans to be due now for deterministic batch behavior.
        $this->repository->updateDelinquencyStatus($loan1Id, ['next_action_date' => date('Y-m-d')]);
        $this->repository->updateDelinquencyStatus($loan2Id, ['next_action_date' => date('Y-m-d')]);
        $this->repository->updateDelinquencyStatus($loan3Id, ['next_action_date' => date('Y-m-d')]);

        // Run batch processing
        $result = $this->workflowService->processDueActions('batch_queue');

        // Verify results
        $this->assertIsArray($result);
        $this->assertCount(1, $result); // Only loan 2 should have action created
        $this->assertTrue($result[0]['created']);
        $this->assertEquals('courtesy_reminder', $result[0]['action_type']);

        // Verify actions only for loan 2
        $actions1 = $this->getPersistedActions($loan1Id);
        $actions2 = $this->getPersistedActions($loan2Id);
        $actions3 = $this->getPersistedActions($loan3Id);

        $this->assertEmpty($actions1);
        $this->assertNotEmpty($actions2);
        $this->assertEmpty($actions3);
    }

    /**
     * Test: Consecutive missed payments trigger escalation at 90+ days
     *
     * Scenario:
     * 1. Create 3 consecutive missed payments (90+ pattern)
     * 2. Classify as 90_PLUS_DAYS_PAST_DUE
     * 3. Verify recommendation includes charge-off consideration
     * 4. Create collection action
     * 5. Verify action type is formal_collection_notice
     */
    public function testConsecutiveMissedPaymentsAtNintyPlusDaysEscalatesAction(): void
    {
        // Setup
        $loanId = 7;
        $this->insertTestLoan($loanId);

        // Build history: 3 consecutive missed payments (90+ day late)
        $today = new DateTimeImmutable();
        $this->recordPayments($loanId, [
            ['amount' => 1000.0, 'status' => 'on_time', 'date' => $today->modify('-130 days')->format('Y-m-d')],
            ['amount' => 0.0, 'status' => 'missed', 'date' => $today->modify('-120 days')->format('Y-m-d')],
            ['amount' => 0.0, 'status' => 'missed', 'date' => $today->modify('-105 days')->format('Y-m-d')],
            ['amount' => 0.0, 'status' => 'missed', 'date' => $today->modify('-95 days')->format('Y-m-d')],
        ]);

        // Classify
        $classification = $this->classifier->classify($this->createTestLoan($loanId));

        // Verify 90_PLUS classification with escalation recommendation
        $this->assertEquals('90_PLUS_DAYS_PAST_DUE', $classification['status']);
        $this->assertGreaterThanOrEqual(89, $classification['days_overdue']);
        $this->assertEquals(3, $classification['consecutive_missed_payments']);

        // Verify recommendation includes charge-off
        $recs = $classification['recommendations'];
        $this->assertNotEmpty($recs);
        $recommendationText = strtolower(implode(' ', $recs));
        $this->assertStringContainsString('charge-off', $recommendationText);

        // Persist and create action
        $this->repository->saveDelinquencyStatus($loanId, $classification);
        $actionResult = $this->workflowService->createNextAction($loanId);

        // Verify escalated action type
        $this->assertTrue($actionResult['created']);
        $this->assertContains(
            $actionResult['action_type'],
            ['formal_collection_notice', 'external_collection_referral'],
            'Should create formal collection or external referral at 90+ days'
        );
    }

    // ======================================================================
    // Helper Methods
    // ======================================================================

    /**
     * Insert a minimal test loan for relational integrity
     */
    private function insertTestLoan(int $loanId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO [ksf_loans] (id, loan_number, borrower_id, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$loanId, "LOAN-{$loanId}", $loanId * 100, 'ACTIVE']);
    }

    /**
     * Create a concrete Loan entity for classification.
     */
    private function createTestLoan(int $loanId): Loan
    {
        $loan = new Loan();
        $loan->setId($loanId);
        $loan->setPrincipal(10000.0);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(10000.0);

        return $loan;
    }

    /**
     * Record payment-like events via the domain event API used by PaymentHistoryTracker.
     *
     * @param array<int, array{amount: float, status: string, date: string}> $payments
     */
    private function recordPayments(int $loanId, array $payments): void
    {
        foreach ($payments as $payment) {
            $event = new LoanEvent([
                'loan_id' => $loanId,
                'event_type' => $payment['amount'] > 0 ? 'regular_payment' : 'missed_payment',
                'amount' => $payment['amount'],
                'event_date' => new DateTimeImmutable($payment['date']),
                'notes' => json_encode(['status' => $payment['status']]),
            ]);

            $this->tracker->recordEvent($loanId, $event);
        }
    }

    /**
     * Retrieve all persisted collection actions for a loan
     */
    private function getPersistedActions(int $loanId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM [ksf_collection_actions] WHERE loan_id = ? ORDER BY action_date DESC');
        $stmt->execute([$loanId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retrieve persisted payment arrangement for a loan
     */
    private function getPersistedArrangement(int $loanId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM [ksf_payment_arrangement] WHERE loan_id = ?');
        $stmt->execute([$loanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
