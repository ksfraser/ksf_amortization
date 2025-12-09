<?php
namespace Ksfraser\Amortizations\WordPress;

use Ksfraser\Amortizations\DataProviderInterface;

/**
 * WordPress adaptor for Amortization business logic.
 * Implements DataProviderInterface for WP integration.
 */
class WPDataProvider implements DataProviderInterface
{
    /**
     * @var \wpdb
     */
    protected $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * Fetch loan data from WP custom table
     */
    public function getLoan(int $loan_id): array
    {
        $table = $this->wpdb->prefix . 'amortization_loans';
        $row = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $table WHERE id = %d", $loan_id), ARRAY_A);
        // Ensure borrower_type is present
        if ($row && !isset($row['borrower_type'])) {
            $row['borrower_type'] = null;
        }
        return $row ?: [];
    }

    /**
     * Insert a new loan record
     */
    public function insertLoan(array $data): int
    {
        $table = $this->wpdb->prefix . 'amortization_loans';
        // Ensure borrower_type is set
        if (!isset($data['borrower_type'])) {
            $data['borrower_type'] = null;
        }
        $this->wpdb->insert($table, $data);
        return (int)$this->wpdb->insert_id;
    }

    /**
     * Insert a schedule row for a loan
     */
    public function insertSchedule(int $loan_id, array $schedule_row): void
    {
        $table = $this->wpdb->prefix . 'amortization_schedules';
        $schedule_row['loan_id'] = $loan_id;
        $this->wpdb->insert($table, $schedule_row);
    }

    /**
     * Insert a loan event (extra payment or skip payment)
     *
     * @param int $loanId Loan ID
     * @param \Ksfraser\Amortizations\LoanEvent $event Event object
     *
     * @return int Event ID
     */
    public function insertLoanEvent(int $loanId, \Ksfraser\Amortizations\LoanEvent $event): int
    {
        $table = $this->wpdb->prefix . 'amortization_events';
        $data = [
            'loan_id' => $loanId,
            'event_type' => $event->event_type,
            'event_date' => $event->event_date,
            'amount' => $event->amount,
            'notes' => $event->notes ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->wpdb->insert($table, $data);
        return (int)$this->wpdb->insert_id;
    }

    /**
     * Get all events for a loan
     *
     * @param int $loanId Loan ID
     *
     * @return array Array of event records
     */
    public function getLoanEvents(int $loanId): array
    {
        $table = $this->wpdb->prefix . 'amortization_events';
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $table WHERE loan_id = %d ORDER BY event_date ASC",
            $loanId
        ), ARRAY_A);
        return $results ?: [];
    }

    /**
     * Delete schedule rows after a given date
     *
     * @param int $loanId Loan ID
     * @param string $date Date in YYYY-MM-DD format
     *
     * @return void
     */
    public function deleteScheduleAfterDate(int $loanId, string $date): void
    {
        $table = $this->wpdb->prefix . 'amortization_schedules';
        $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM $table WHERE loan_id = %d AND payment_date > %s AND posted_to_gl = 0",
            $loanId,
            $date
        ));
    }

    /**
     * Get schedule rows after a given date
     *
     * @param int $loanId Loan ID
     * @param string $date Date in YYYY-MM-DD format
     *
     * @return array Array of schedule rows
     */
    public function getScheduleRowsAfterDate(int $loanId, string $date): array
    {
        $table = $this->wpdb->prefix . 'amortization_schedules';
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $table WHERE loan_id = %d AND payment_date > %s ORDER BY payment_date ASC",
            $loanId,
            $date
        ), ARRAY_A);
        return $results ?: [];
    }

    /**
     * Update a single schedule row
     *
     * @param int $stagingId Schedule row ID
     * @param array $updates Fields to update
     *
     * @return void
     */
    public function updateScheduleRow(int $stagingId, array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        $table = $this->wpdb->prefix . 'amortization_schedules';
        $updates['updated_at'] = date('Y-m-d H:i:s');
        $this->wpdb->update($table, $updates, ['id' => $stagingId]);
    }

    /**
     * Get all schedule rows for a loan
     *
     * @param int $loanId Loan ID
     *
     * @return array Array of all schedule rows
     */
    public function getScheduleRows(int $loanId): array
    {
        $table = $this->wpdb->prefix . 'amortization_schedules';
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $table WHERE loan_id = %d ORDER BY payment_date ASC",
            $loanId
        ), ARRAY_A);
        return $results ?: [];
    }
}
