<?php
namespace Ksfraser\Amortizations\FA;

use Ksfraser\Amortizations\LoanEvent;
use Ksfraser\Amortizations\LoanEventProviderInterface;

class LoanEventProvider implements \Ksfraser\Amortizations\LoanEventProviderInterface {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function insertLoanEvent(LoanEvent $event): void {
        $sql = "INSERT INTO loan_events (loan_id, event_type, event_date, amount, notes) VALUES (:loan_id, :event_type, :event_date, :amount, :notes)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':loan_id' => $event->loan_id,
            ':event_type' => $event->event_type,
            ':event_date' => $event->event_date,
            ':amount' => $event->amount,
            ':notes' => $event->notes
        ]);
    }
    public function getLoanEvents(int $loan_id): array {
        $sql = "SELECT * FROM loan_events WHERE loan_id = :loan_id ORDER BY event_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':loan_id' => $loan_id]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new LoanEvent($row), $rows);
    }
    public function updateLoanEvent(LoanEvent $event): void {
        $sql = "UPDATE loan_events SET event_type = :event_type, event_date = :event_date, amount = :amount, notes = :notes WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $event->id,
            ':event_type' => $event->event_type,
            ':event_date' => $event->event_date,
            ':amount' => $event->amount,
            ':notes' => $event->notes
        ]);
    }
    public function deleteLoanEvent(int $event_id): void {
        $sql = "DELETE FROM loan_events WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $event_id]);
    }
}
