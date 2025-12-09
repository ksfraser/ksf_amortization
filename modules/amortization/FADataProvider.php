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
        $fields = [
            'borrower_id', 'borrower_type', 'amount_financed', 'interest_rate', 'loan_term_years',
            'payments_per_year', 'first_payment_date', 'regular_payment', 'override_payment', 'loan_type',
            'interest_calc_frequency', 'status'
        ];
        $columns = [];
        $params = [];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $columns[] = $field;
                $params[":" . $field] = $data[$field];
            }
        }
        $sql = "INSERT INTO " . $this->dbPrefix . "ksf_loans_summary (" . implode(", ", $columns) . ") VALUES (" . implode(", ", array_keys($params)) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$this->pdo->lastInsertId();
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
            'borrower_id', 'borrower_type', 'amount_financed', 'interest_rate', 'loan_term_years',
            'payments_per_year', 'first_payment_date', 'regular_payment', 'override_payment', 'loan_type',
            'interest_calc_frequency', 'status'
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

    /**
     * Insert a loan event (extra payment or skip payment)
     *
     * @param int $loanId Loan database ID
     * @param LoanEvent $event Event object with type, date, amount
     *
     * @return int Event ID
     */
    public function insertLoanEvent(int $loanId, LoanEvent $event): int {
        $sql = "INSERT INTO " . $this->dbPrefix . "ksf_loan_events 
                (loan_id, event_type, event_date, amount, notes, created_at) 
                VALUES (:loan_id, :event_type, :event_date, :amount, :notes, :created_at)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':loan_id' => $loanId,
            ':event_type' => $event->event_type,
            ':event_date' => $event->event_date,
            ':amount' => $event->amount,
            ':notes' => $event->notes ?? '',
            ':created_at' => date('Y-m-d H:i:s')
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Get all events for a loan
     *
     * @param int $loanId Loan database ID
     *
     * @return array Array of event records
     */
    public function getLoanEvents(int $loanId): array {
        $sql = "SELECT * FROM " . $this->dbPrefix . "ksf_loan_events 
                WHERE loan_id = :loan_id 
                ORDER BY event_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':loan_id' => $loanId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Delete schedule rows after a given date
     *
     * @param int $loanId Loan database ID
     * @param string $date Date in YYYY-MM-DD format
     *
     * @return void
     */
    public function deleteScheduleAfterDate(int $loanId, string $date): void {
        $sql = "DELETE FROM " . $this->dbPrefix . "ksf_amortization_staging 
                WHERE loan_id = :loan_id AND payment_date > :date AND posted_to_gl = 0";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':loan_id' => $loanId,
            ':date' => $date
        ]);
    }

    /**
     * Get schedule rows after a given date
     *
     * @param int $loanId Loan database ID
     * @param string $date Date in YYYY-MM-DD format
     *
     * @return array Array of schedule rows
     */
    public function getScheduleRowsAfterDate(int $loanId, string $date): array {
        $sql = "SELECT * FROM " . $this->dbPrefix . "ksf_amortization_staging 
                WHERE loan_id = :loan_id AND payment_date > :date 
                ORDER BY payment_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':loan_id' => $loanId,
            ':date' => $date
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Update a single schedule row
     *
     * @param int $stagingId Schedule row ID
     * @param array $updates Fields to update (key => value pairs)
     *
     * @return void
     */
    public function updateScheduleRow(int $stagingId, array $updates): void {
        if (empty($updates)) {
            return;
        }
        
        $setClauses = [];
        $params = [':id' => $stagingId];
        
        foreach ($updates as $field => $value) {
            $setClauses[] = "$field = :$field";
            $params[":$field"] = $value;
        }
        
        $sql = "UPDATE " . $this->dbPrefix . "ksf_amortization_staging 
                SET " . implode(', ', $setClauses) . ", updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Get all schedule rows for a loan
     *
     * @param int $loanId Loan database ID
     *
     * @return array Array of all schedule rows ordered by payment date
     */
    public function getScheduleRows(int $loanId): array {
        $sql = "SELECT * FROM " . $this->dbPrefix . "ksf_amortization_staging 
                WHERE loan_id = :loan_id 
                ORDER BY payment_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':loan_id' => $loanId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
