<?php
namespace Ksfraser\Amortizations;

use Ksfraser\Amortizations\LoanEvent;
use Ksfraser\Amortizations\LoanEventProviderInterface;

/**
 * FA implementation of LoanEventProviderInterface
 */
class FALoanEventProvider implements LoanEventProviderInterface {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }
    public function insertLoanEvent(LoanEvent $event): void {
        $sql = "INSERT INTO ksf_loan_events (loan_id, event_type, event_date, amount, notes) VALUES ('" .
            $this->db->escape($event->loan_id) . "', '" .
            $this->db->escape($event->event_type) . "', '" .
            $this->db->escape($event->event_date) . "', '" .
            $this->db->escape($event->amount) . "', '" .
            $this->db->escape($event->notes) . "')";
        $this->db->query($sql);
    }
    public function getLoanEvents(int $loan_id): array {
        $sql = "SELECT * FROM ksf_loan_events WHERE loan_id = '" . $this->db->escape($loan_id) . "' ORDER BY event_date ASC";
        $result = $this->db->query($sql);
        $rows = [];
        while ($row = $this->db->fetch_assoc($result)) {
            $rows[] = new LoanEvent($row);
        }
        return $rows;
    }
    public function updateLoanEvent(LoanEvent $event): void {
        $sql = "UPDATE ksf_loan_events SET event_type = '" . $this->db->escape($event->event_type) .
            "', event_date = '" . $this->db->escape($event->event_date) .
            "', amount = '" . $this->db->escape($event->amount) .
            "', notes = '" . $this->db->escape($event->notes) .
            "' WHERE id = '" . $this->db->escape($event->id) . "'";
        $this->db->query($sql);
    }
    public function deleteLoanEvent(int $event_id): void {
        $sql = "DELETE FROM ksf_loan_events WHERE id = '" . $this->db->escape($event_id) . "'";
        $this->db->query($sql);
    }
}
