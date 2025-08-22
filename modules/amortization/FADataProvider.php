<?php
namespace Ksfraser\Amortizations;

use Ksfraser\Amortizations\DataProviderInterface;

// If model.php contains a class, use its namespace import instead. (Assume autoloading via Composer)

/**
 * Class FADataProvider
 * FrontAccounting data provider implementation for amortization module
 *
 * @package Ksfraser\Amortizations
 * @author ksfraser
 *
 * UML:
 * ```
 * class FADataProvider {
 *   - pdo: PDO
 *   + __construct(pdo: PDO)
 *   + insertLoan(data: array): int
 *   + getLoan(loan_id: int): array
 *   + insertSchedule(loan_id: int, schedule_row: array): void
 *   + updateLoan(loan_id: int, data: array): void
 * }
 * ```
 */

class FADataProvider implements DataProviderInterface {
    private $pdo;
    private $dbPrefix;

    public function __construct($pdo, $dbPrefix = '') {
        $this->pdo = $pdo;
        $this->dbPrefix = $dbPrefix;
    }

    public function insertLoan(array $data): int {
        // Implement insert logic here
        // ...existing code...
        return 0; // placeholder
    }

    public function getLoan(int $loan_id): array {
        // Implement get logic here
        // ...existing code...
        return [];
    }

    public function insertSchedule(int $loan_id, array $schedule_row): void {
        // Implement schedule insert logic here
        // ...existing code...
    }

    public function updateLoan(int $loan_id, array $data): void {
        // Update all editable fields
        $fields = [
            'loan_type', 'description', 'amount_financed', 'interest_rate', 'payment_frequency',
            'interest_calc_frequency', 'num_payments', 'regular_payment', 'override_payment',
            'first_payment_date', 'last_payment_date', 'principal', 'term_months',
            'repayment_schedule', 'start_date', 'end_date'
        ];
        $set = [];
        $params = [];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $set[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        $params[':loan_id'] = $loan_id;
        $sql = "UPDATE " . $this->dbPrefix . "ksf_loans_summary SET " . implode(', ', $set) . " WHERE id = :loan_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Insert an out-of-schedule event (skipped/extra payment)
     * @param int $loan_id
     * @param array $eventData
     */
    public function insertOutOfScheduleEvent(int $loan_id, array $eventData): void {
        $sql = "INSERT INTO " . $this->dbPrefix . "ksf_loan_events (loan_id, event_type, event_date, amount, notes) VALUES (:loan_id, :event_type, :event_date, :amount, :notes)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':loan_id' => $loan_id,
            ':event_type' => $eventData['event_type'], // 'skip' or 'extra'
            ':event_date' => $eventData['event_date'],
            ':amount' => $eventData['amount'],
            ':notes' => $eventData['notes'] ?? ''
        ]);
    }

    /**
     * Get all out-of-schedule events for a loan
     * @param int $loan_id
     * @return array
     */
    public function getOutOfScheduleEvents(int $loan_id): array {
        $sql = "SELECT * FROM " . $this->dbPrefix . "ksf_loan_events WHERE loan_id = :loan_id ORDER BY event_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':loan_id' => $loan_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
