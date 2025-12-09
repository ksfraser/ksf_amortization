<?php
/**
 * Mock Classes for Testing
 *
 * Provides mock implementations of interfaces for unit testing without
 * requiring actual platform implementations (FA, WP, SuiteCRM).
 *
 * ### UML Class Diagram
 * ```
 * ┌──────────────────────────────────────┐
 * │     MockDataProvider                 │
 * │   implements                         │
 * │  DataProviderInterface               │
 * ├──────────────────────────────────────┤
 * │ - db: PDO                            │
 * │ - recordedCalls: array               │
 * ├──────────────────────────────────────┤
 * │ + getLoan(int): ?LoanSummary         │
 * │ + insertSchedule(): void             │
 * │ + insertLoanEvent(): void            │
 * │ + getScheduleRowsAfter(): array      │
 * └──────────────────────────────────────┘
 *           ▲           ▲
 *           │           │
 *    Implements    Uses SQLite DB
 * ```
 *
 * @package   Ksfraser\Amortizations\Tests
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-08
 */

namespace Ksfraser\Amortizations\Tests;

use Ksfraser\Amortizations\DataProviderInterface;
use Ksfraser\Amortizations\LoanEventProviderInterface;
use Ksfraser\Amortizations\LoanSummary;
use Ksfraser\Amortizations\LoanEvent;
use PDO;
use DateTime;

/**
 * Mock implementation of DataProviderInterface for testing
 */
class MockDataProvider implements DataProviderInterface
{
    /**
     * @var PDO Test database connection
     */
    private PDO $db;

    /**
     * @var array Call recording for verification
     */
    private array $recordedCalls = [];

    /**
     * Constructor
     *
     * @param PDO $db SQLite test database
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get a loan by ID
     *
     * @param int $loanId Loan database ID
     *
     * @return ?LoanSummary Loan object or null if not found
     */
    public function getLoan(int $loanId): ?LoanSummary
    {
        $this->recordCall(__FUNCTION__, ['loanId' => $loanId]);

        $stmt = $this->db->prepare(
            'SELECT * FROM ksf_loans_summary WHERE id = ?'
        );
        $stmt->execute([$loanId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->rowToLoanSummary($row);
    }

    /**
     * Get loans by external ID
     *
     * @param string $externalId External loan identifier
     *
     * @return ?LoanSummary Loan object or null
     */
    public function getLoanByExternalId(string $externalId): ?LoanSummary
    {
        $this->recordCall(__FUNCTION__, ['externalId' => $externalId]);

        $stmt = $this->db->prepare(
            'SELECT * FROM ksf_loans_summary WHERE loan_id_external = ?'
        );
        $stmt->execute([$externalId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->rowToLoanSummary($row);
    }

    /**
     * Insert a payment schedule row
     *
     * @param int $loanId Loan ID
     * @param int $paymentNumber Payment number in schedule
     * @param array $data Payment data
     *
     * @return int Schedule record ID
     */
    public function insertSchedule(int $loanId, int $paymentNumber, array $data): int
    {
        $this->recordCall(__FUNCTION__, [
            'loanId' => $loanId,
            'paymentNumber' => $paymentNumber,
            'dataKeys' => array_keys($data),
        ]);

        $stmt = $this->db->prepare(
            'INSERT INTO ksf_amortization_staging 
            (loan_id, payment_number, payment_date, beginning_balance, 
             payment_amount, principal_payment, interest_payment, ending_balance)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $loanId,
            $paymentNumber,
            $data['payment_date'] ?? null,
            $data['beginning_balance'] ?? 0,
            $data['payment_amount'] ?? 0,
            $data['principal_payment'] ?? 0,
            $data['interest_payment'] ?? 0,
            $data['ending_balance'] ?? 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Record a loan event (extra payment, skip, etc.)
     *
     * @param int $loanId Loan ID
     * @param LoanEvent $event Event to record
     *
     * @return int Event record ID
     */
    public function insertLoanEvent(int $loanId, LoanEvent $event): int
    {
        $this->recordCall(__FUNCTION__, [
            'loanId' => $loanId,
            'eventType' => $event->eventType,
        ]);

        $stmt = $this->db->prepare(
            'INSERT INTO ksf_loan_events 
            (loan_id, event_type, event_date, amount, reason, recalculation_required)
            VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $loanId,
            $event->eventType,
            $event->eventDate->format('Y-m-d'),
            $event->amount,
            $event->reason,
            $event->recalculationRequired ? 1 : 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get schedule rows after a date
     *
     * @param int $loanId Loan ID
     * @param DateTime $date Date to get rows after
     *
     * @return array Array of schedule rows
     */
    public function getScheduleRowsAfterDate(int $loanId, DateTime $date): array
    {
        $this->recordCall(__FUNCTION__, [
            'loanId' => $loanId,
            'date' => $date->format('Y-m-d'),
        ]);

        $stmt = $this->db->prepare(
            'SELECT * FROM ksf_amortization_staging 
            WHERE loan_id = ? AND payment_date >= ? 
            ORDER BY payment_number ASC'
        );
        $stmt->execute([$loanId, $date->format('Y-m-d')]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update a schedule row
     *
     * @param int $scheduleId Schedule row ID
     * @param array $data Updated data
     *
     * @return void
     */
    public function updateScheduleRow(int $scheduleId, array $data): void
    {
        $this->recordCall(__FUNCTION__, [
            'scheduleId' => $scheduleId,
            'dataKeys' => array_keys($data),
        ]);

        $setClauses = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = ?";
            $params[] = $value;
        }

        $params[] = $scheduleId;

        $sql = 'UPDATE ksf_amortization_staging SET ' . implode(', ', $setClauses) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Delete schedule rows after a date
     *
     * @param int $loanId Loan ID
     * @param DateTime $date Date to delete after
     *
     * @return int Number of rows deleted
     */
    public function deleteScheduleAfterDate(int $loanId, DateTime $date): int
    {
        $this->recordCall(__FUNCTION__, [
            'loanId' => $loanId,
            'date' => $date->format('Y-m-d'),
        ]);

        $stmt = $this->db->prepare(
            'DELETE FROM ksf_amortization_staging 
            WHERE loan_id = ? AND payment_date > ?'
        );
        $stmt->execute([$loanId, $date->format('Y-m-d')]);

        return $stmt->rowCount();
    }

    /**
     * Get recorded calls for verification
     *
     * @return array
     */
    public function getRecordedCalls(): array
    {
        return $this->recordedCalls;
    }

    /**
     * Convert database row to LoanSummary object
     *
     * @param array $row Database row
     *
     * @return LoanSummary
     */
    private function rowToLoanSummary(array $row): LoanSummary
    {
        return new LoanSummary(
            id: (int)$row['id'],
            loanIdExternal: $row['loan_id_external'],
            loanTypeId: (int)$row['loan_type_id'],
            principal: (float)$row['principal'],
            annualInterestRate: (float)$row['annual_interest_rate'],
            paymentFrequencyId: (int)$row['payment_frequency_id'],
            interestCalcFrequencyId: (int)$row['interest_calc_frequency_id'],
            startDate: new DateTime($row['start_date']),
            endDate: $row['end_date'] ? new DateTime($row['end_date']) : null,
            currentBalance: (float)($row['current_balance'] ?? $row['principal']),
            paymentsRemaining: (int)($row['payments_remaining'] ?? 0)
        );
    }

    /**
     * Record a method call for verification
     *
     * @param string $method Method name
     * @param array $params Parameters
     *
     * @return void
     */
    private function recordCall(string $method, array $params): void
    {
        $this->recordedCalls[] = [
            'method' => $method,
            'params' => $params,
            'timestamp' => microtime(true),
        ];
    }
}

/**
 * Mock implementation of LoanEventProviderInterface for testing
 */
class MockLoanEventProvider implements LoanEventProviderInterface
{
    /**
     * @var PDO Test database connection
     */
    private PDO $db;

    /**
     * Constructor
     *
     * @param PDO $db SQLite test database
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get events for a loan that require recalculation
     *
     * @param int $loanId Loan ID
     *
     * @return array Array of LoanEvent objects
     */
    public function getPendingEvents(int $loanId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ksf_loan_events 
            WHERE loan_id = ? AND recalculation_required = 1
            ORDER BY event_date ASC'
        );
        $stmt->execute([$loanId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $events = [];
        foreach ($rows as $row) {
            $events[] = new LoanEvent(
                id: (int)$row['id'],
                loanId: (int)$row['loan_id'],
                eventType: $row['event_type'],
                eventDate: new DateTime($row['event_date']),
                amount: (float)($row['amount'] ?? 0),
                reason: $row['reason'] ?? '',
                recalculationRequired: (bool)$row['recalculation_required']
            );
        }

        return $events;
    }

    /**
     * Mark an event as processed
     *
     * @param int $eventId Event ID
     *
     * @return void
     */
    public function markEventProcessed(int $eventId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ksf_loan_events SET recalculation_required = 0 WHERE id = ?'
        );
        $stmt->execute([$eventId]);
    }
}

?>
