<?php
namespace Ksfraser\Amortizations;

use Ksfraser\Amortizations\LoanEvent;
use Ksfraser\Amortizations\LoanEventProviderInterface;

/**
 * WordPress implementation of LoanEventProviderInterface
 */
class WPLoanEventProvider implements LoanEventProviderInterface {
    private $wpdb;
    private $dbPrefix;
    public function __construct($wpdb, $dbPrefix = '') {
        $this->wpdb = $wpdb;
        $this->dbPrefix = $dbPrefix;
    }
    public function insertLoanEvent(LoanEvent $event): void {
        $this->wpdb->insert($this->dbPrefix . 'ksf_loan_events', [
            'loan_id' => $event->loan_id,
            'event_type' => $event->event_type,
            'event_date' => $event->event_date,
            'amount' => $event->amount,
            'notes' => $event->notes
        ]);
    }
    public function getLoanEvents(int $loan_id): array {
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->dbPrefix}ksf_loan_events WHERE loan_id = %d ORDER BY event_date ASC", $loan_id
        ), ARRAY_A);
        return array_map(fn($row) => new LoanEvent($row), $results);
    }
    public function updateLoanEvent(LoanEvent $event): void {
        $this->wpdb->update($this->dbPrefix . 'ksf_loan_events', [
            'event_type' => $event->event_type,
            'event_date' => $event->event_date,
            'amount' => $event->amount,
            'notes' => $event->notes
        ], ['id' => $event->id]);
    }
    public function deleteLoanEvent(int $event_id): void {
        $this->wpdb->delete($this->dbPrefix . 'ksf_loan_events', ['id' => $event_id]);
    }
}
