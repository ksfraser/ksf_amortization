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
    // ...existing code...
}
