<?php
namespace Ksfraser\Amortizations\WordPress;

use Ksfraser\Amortizations\LoanEvent;
use Ksfraser\Amortizations\LoanEventProviderInterface;

class LoanEventProvider implements \Ksfraser\Amortizations\LoanEventProviderInterface {
    private $wpdb;
    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
    }
    public function insertLoanEvent(LoanEvent $event): void {
        $this->wpdb->insert('ksf_loan_events', [
            'loan_id' => $event->loan_id,
            'event_type' => $event->event_type,
            'event_date' => $event->event_date,
            'amount' => $event->amount,
            'notes' => $event->notes
        ]);
    }
    public function getLoanEvents(int $loan_id): array {
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM ksf_loan_events WHERE loan_id = %d ORDER BY event_date ASC", $loan_id
        ), 'ARRAY_A');
        return array_map(fn($row) => new LoanEvent($row), $results);
    }
    public function updateLoanEvent(LoanEvent $event): void {
        $this->wpdb->update('ksf_loan_events', [
            'event_type' => $event->event_type,
            'event_date' => $event->event_date,
            'amount' => $event->amount,
            'notes' => $event->notes
        ], ['id' => $event->id]);
    }
    public function deleteLoanEvent(int $event_id): void {
        $this->wpdb->delete('ksf_loan_events', ['id' => $event_id]);
    }
}
