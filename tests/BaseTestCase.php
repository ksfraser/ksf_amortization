<?php
/**
 * BaseTestCase - Abstract base class for all unit tests
 *
 * Provides common test infrastructure with dependency injection container,
 * mock database setup, and test helper methods following SOLID principles.
 *
 * @package   Ksfraser\Amortizations\Tests
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-08
 *
 * ### UML Class Diagram (ASCII)
 * ```
 * ┌─────────────────────────────────────┐
 * │         BaseTestCase                │
 * ├─────────────────────────────────────┤
 * │ - container: DIContainer            │
 * │ - mockDB: PDOStatement              │
 * │ - mockDataProvider: MockProvider    │
 * ├─────────────────────────────────────┤
 * │ + setUp(): void                     │
 * │ + tearDown(): void                  │
 * │ + getMockDatabase(): PDO            │
 * │ + getContainer(): DIContainer       │
 * │ + assertAlmostEquals(): void        │
 * │ + assertRecorded(): void            │
 * │ + createMockLoan(): LoanSummary     │
 * │ + createMockEvent(): LoanEvent      │
 * └─────────────────────────────────────┘
 *      ▲                    ▲
 *      │                    │
 *   Extended by         Depends on
 *      │                    │
 *   Test Classes      DIContainer, PDO, Mock Classes
 * ```
 *
 * ### Dependency Injection Pattern
 * Uses lightweight DIContainer (not Symfony) for testability:
 * - All dependencies injected via constructor
 * - Mock instances registered for testing
 * - Swap implementations without changing test code
 *
 * ### Design Principles
 * - **Single Responsibility:** Only handles test setup/teardown
 * - **Open/Closed:** Extended by specific test classes
 * - **Liskov Substitution:** All tests inherit and follow contract
 * - **Interface Segregation:** Mock interfaces are minimal
 * - **Dependency Inversion:** Depends on interfaces, not implementations
 *
 * @see https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html
 */

namespace Ksfraser\Amortizations\Tests;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

/**
 * Abstract base test case with dependency injection and test helpers
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * @var DIContainer Service container for dependency injection
     */
    protected DIContainer $container;

    /**
     * @var PDO Mock database connection
     */
    protected PDO $mockDB;

    /**
     * @var array Recorded database calls for verification
     */
    protected array $recordedCalls = [];

    /**
     * Set up test fixtures before each test method
     *
     * ### Setup Process
     * 1. Initialize DI container with mock services
     * 2. Create in-memory SQLite database
     * 3. Build database schema
     * 4. Register mocks for platform-specific implementations
     * 5. Set call recording for verification
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize dependency injection container
        $this->container = new DIContainer();

        // Create in-memory SQLite database for testing
        try {
            $this->mockDB = new PDO('sqlite::memory:');
            $this->mockDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $this->fail("Failed to create test database: {$e->getMessage()}");
        }

        // Build test schema
        $this->setupTestSchema();

        // Register mock services in container
        $this->registerMockServices();

        // Reset recorded calls
        $this->recordedCalls = [];
    }

    /**
     * Clean up after each test method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Close database connection
        $this->mockDB = null;

        // Clear container
        $this->container = null;

        // Clear recorded calls
        $this->recordedCalls = [];
    }

    /**
     * Build test database schema
     *
     * Creates all necessary tables for amortization module testing:
     * - ksf_loans_summary: Core loan records
     * - ksf_amortization_staging: Payment schedules
     * - ksf_loan_events: Extra payments/skipped payments
     * - ksf_amort_loan_types: Configurable loan types
     * - ksf_amort_interest_calc_frequencies: Frequency configurations
     *
     * @return void
     * @throws \PDOException If schema creation fails
     */
    protected function setupTestSchema(): void
    {
        $schema = <<<SQL
            -- Core loan records
            CREATE TABLE ksf_loans_summary (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                loan_id_external VARCHAR(50) UNIQUE NOT NULL,
                loan_type_id INTEGER NOT NULL,
                principal DECIMAL(12,2) NOT NULL,
                annual_interest_rate DECIMAL(5,4) NOT NULL,
                payment_frequency_id INTEGER NOT NULL,
                interest_calc_frequency_id INTEGER NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE,
                current_balance DECIMAL(12,2),
                payments_remaining INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            -- Payment schedules (staging before GL posting)
            CREATE TABLE ksf_amortization_staging (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                loan_id INTEGER NOT NULL,
                payment_number INTEGER NOT NULL,
                payment_date DATE NOT NULL,
                beginning_balance DECIMAL(12,2) NOT NULL,
                payment_amount DECIMAL(12,2) NOT NULL,
                principal_payment DECIMAL(12,2) NOT NULL,
                interest_payment DECIMAL(12,2) NOT NULL,
                ending_balance DECIMAL(12,2) NOT NULL,
                is_posted BOOLEAN DEFAULT 0,
                trans_no VARCHAR(50),
                trans_type VARCHAR(10),
                posted_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(loan_id, payment_number),
                FOREIGN KEY(loan_id) REFERENCES ksf_loans_summary(id)
            );

            -- Extra payments and event handling
            CREATE TABLE ksf_loan_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                loan_id INTEGER NOT NULL,
                event_type VARCHAR(20) NOT NULL,
                event_date DATE NOT NULL,
                amount DECIMAL(12,2),
                reason VARCHAR(255),
                recalculation_required BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(loan_id) REFERENCES ksf_loans_summary(id)
            );

            -- Configurable loan types
            CREATE TABLE ksf_amort_loan_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type_name VARCHAR(50) UNIQUE NOT NULL,
                description VARCHAR(255)
            );

            -- Configurable payment frequencies
            CREATE TABLE ksf_amort_interest_calc_frequencies (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                frequency_name VARCHAR(50) UNIQUE NOT NULL,
                frequency_key VARCHAR(20) UNIQUE NOT NULL,
                periods_per_year INTEGER NOT NULL,
                days_per_period DECIMAL(6,3) NOT NULL
            );

            -- Insert default frequency configurations
            INSERT INTO ksf_amort_interest_calc_frequencies 
            (frequency_name, frequency_key, periods_per_year, days_per_period)
            VALUES
                ('Monthly', 'monthly', 12, 30.4167),
                ('Bi-weekly', 'biweekly', 26, 14.0),
                ('Weekly', 'weekly', 52, 7.0),
                ('Daily', 'daily', 365, 1.0);

            -- Insert default loan types
            INSERT INTO ksf_amort_loan_types (type_name, description)
            VALUES
                ('Standard', 'Standard amortized loan'),
                ('Credit Line', 'Credit line with variable terms');
SQL;

        try {
            $this->mockDB->exec($schema);
        } catch (\PDOException $e) {
            throw new \PDOException("Schema setup failed: {$e->getMessage()}");
        }
    }

    /**
     * Register mock services in dependency injection container
     *
     * ### Registered Services
     * - PDO: Mock database connection
     * - DataProviderInterface: Mock implementation
     * - LoanEventProviderInterface: Mock implementation
     * - AmortizationModel: Real instance with mocked dependencies
     *
     * @return void
     */
    protected function registerMockServices(): void
    {
        // Register database
        $this->container->set('db', $this->mockDB);

        // Register mock data provider
        $mockDataProvider = new MockDataProvider($this->mockDB);
        $this->container->set('DataProviderInterface', $mockDataProvider);

        // Register mock loan event provider
        $mockEventProvider = new MockLoanEventProvider($this->mockDB);
        $this->container->set('LoanEventProviderInterface', $mockEventProvider);

        // Register configuration
        $this->container->set('config', [
            'precision' => 2,
            'tolerance' => 0.02,  // $0.02 tolerance for rounding
        ]);
    }

    /**
     * Get the dependency injection container
     *
     * @return DIContainer
     */
    protected function getContainer(): DIContainer
    {
        return $this->container;
    }

    /**
     * Get mock database connection
     *
     * @return PDO
     */
    protected function getMockDatabase(): PDO
    {
        return $this->mockDB;
    }

    /**
     * Record a database operation for verification
     *
     * ### Purpose
     * Tracks all database calls made during test execution
     * Allows verification of correct methods called with correct parameters
     * Enables testing of side effects without testing database directly
     *
     * @param string $method Method name (e.g., 'insertSchedule')
     * @param array $params Parameters passed to method
     * @param mixed $result Result returned from method
     *
     * @return void
     */
    protected function recordCall(string $method, array $params, $result = null): void
    {
        $this->recordedCalls[] = [
            'method' => $method,
            'params' => $params,
            'result' => $result,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get all recorded database calls
     *
     * ### Purpose
     * Retrieve complete call history for verification in tests
     *
     * @return array Array of recorded calls
     */
    protected function getRecordedCalls(): array
    {
        return $this->recordedCalls;
    }

    /**
     * Assert that a database method was called with specific parameters
     *
     * ### Purpose
     * Verify that the system made correct database calls
     * Uses loose parameter matching (no strict comparison)
     *
     * @param string $method Method name to verify
     * @param array $expectedParams Parameters expected
     * @param int $times Number of times expected (default 1)
     *
     * @return void
     */
    protected function assertMethodWasCalled(
        string $method,
        array $expectedParams = [],
        int $times = 1
    ): void
    {
        $callCount = 0;

        foreach ($this->recordedCalls as $call) {
            if ($call['method'] === $method) {
                if (empty($expectedParams) || $this->parametersMatch($call['params'], $expectedParams)) {
                    $callCount++;
                }
            }
        }

        $this->assertEquals(
            $times,
            $callCount,
            "Expected {$times} calls to {$method}, but found {$callCount}"
        );
    }

    /**
     * Verify floating-point equality with tolerance
     *
     * ### Purpose
     * PHP floating-point arithmetic introduces rounding errors
     * This assertion allows for small tolerance (typically $0.02)
     *
     * ### Example
     * ```php
     * $this->assertAlmostEquals(860.07, $payment, 0.02);
     * // Passes if: abs(860.07 - $payment) <= 0.02
     * ```
     *
     * @param float $expected Expected value
     * @param float $actual Actual value
     * @param float $tolerance Maximum acceptable difference
     * @param string $message Optional failure message
     *
     * @return void
     */
    protected function assertAlmostEquals(
        float $expected,
        float $actual,
        float $tolerance = 0.02,
        string $message = ''
    ): void
    {
        $diff = abs($expected - $actual);
        $this->assertLessThanOrEqual(
            $tolerance,
            $diff,
            $message ?: "Values differ by {$diff}: expected {$expected}, got {$actual}"
        );
    }

    /**
     * Create a test loan summary
     *
     * ### Purpose
     * Factory method for creating standardized test loans
     * Reduces code duplication across tests
     *
     * ### Example
     * ```php
     * $loan = $this->createMockLoan(
     *     principal: 10000.00,
     *     rate: 5.0,
     *     frequency: 'monthly'
     * );
     * ```
     *
     * @param float $principal Principal amount (default $10,000)
     * @param float $rate Annual interest rate (default 5%)
     * @param string $frequency Payment frequency (default 'monthly')
     * @param string $interestCalcFreq Interest calculation frequency (default 'monthly')
     * @param int $loanTypeId Loan type ID (default 1)
     * @param string $externalId External loan ID (auto-generated if not provided)
     *
     * @return LoanSummary Mock loan object
     * @throws \RuntimeException If loan creation fails
     */
    protected function createMockLoan(
        float $principal = 10000.00,
        float $rate = 5.0,
        string $frequency = 'monthly',
        string $interestCalcFreq = 'monthly',
        int $loanTypeId = 1,
        ?string $externalId = null
    ): LoanSummary
    {
        $externalId = $externalId ?? 'TEST-' . uniqid();
        $startDate = new \DateTime('2025-01-01');

        // Get frequency ID from database
        $stmt = $this->mockDB->prepare(
            'SELECT id FROM ksf_amort_interest_calc_frequencies WHERE frequency_key = ?'
        );
        $stmt->execute([$frequency]);
        $freqRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$freqRow) {
            throw new \RuntimeException("Frequency '{$frequency}' not found in test database");
        }

        $frequencyId = $freqRow['id'];

        // Get interest calc frequency ID
        $stmt = $this->mockDB->prepare(
            'SELECT id FROM ksf_amort_interest_calc_frequencies WHERE frequency_key = ?'
        );
        $stmt->execute([$interestCalcFreq]);
        $calcFreqRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$calcFreqRow) {
            throw new \RuntimeException("Interest calc frequency '{$interestCalcFreq}' not found");
        }

        // Create LoanSummary object
        $loan = new LoanSummary(
            id: null,
            loanIdExternal: $externalId,
            loanTypeId: $loanTypeId,
            principal: $principal,
            annualInterestRate: $rate,
            paymentFrequencyId: $frequencyId,
            interestCalcFrequencyId: $calcFreqRow['id'],
            startDate: $startDate,
            endDate: null,
            currentBalance: $principal,
            paymentsRemaining: 12
        );

        return $loan;
    }

    /**
     * Create a test loan event (extra payment or skip)
     *
     * ### Purpose
     * Factory method for creating test events
     *
     * @param int $loanId Loan database ID
     * @param string $eventType Event type ('extra_payment', 'skip_payment')
     * @param float $amount Amount if applicable
     * @param string $reason Reason for event
     *
     * @return LoanEvent Test event object
     */
    protected function createMockEvent(
        int $loanId,
        string $eventType = 'extra_payment',
        float $amount = 500.00,
        string $reason = 'Customer payment'
    ): LoanEvent
    {
        return new LoanEvent(
            id: null,
            loanId: $loanId,
            eventType: $eventType,
            eventDate: new \DateTime(),
            amount: $amount,
            reason: $reason,
            recalculationRequired: true
        );
    }

    /**
     * Check if call parameters match expected parameters
     *
     * @param array $actual Actual parameters from recorded call
     * @param array $expected Expected parameters to match
     *
     * @return bool True if parameters match
     */
    private function parametersMatch(array $actual, array $expected): bool
    {
        foreach ($expected as $key => $value) {
            if (!isset($actual[$key])) {
                return false;
            }

            // Loose comparison for parameters
            if ($actual[$key] != $value) {
                return false;
            }
        }

        return true;
    }
}

?>
