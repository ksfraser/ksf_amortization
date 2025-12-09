<?php
namespace Ksfraser\Amortizations;

/**
 * Data Access Layer Interface - Repository Pattern
 *
 * Provides abstraction for all data persistence operations. Implementations
 * can use different backends (FA database, WordPress, SuiteCRM, etc).
 *
 * ### Responsibility
 * - Persist and retrieve loan records
 * - Manage amortization schedules
 * - Track loan events (extra payments, skips)
 *
 * ### SOLID Principles
 * - Dependency Inversion: Depend on this interface, not concrete implementations
 * - Interface Segregation: Only includes methods needed for amortization
 * - Liskov Substitution: All implementations are fully substitutable
 *
 * @package Ksfraser\Amortizations
 * @version 1.0.0 (Refactored for extra payment support)
 * @since 2025-12-08
 */
interface DataProviderInterface {
    /**
     * Insert a new loan record
     *
     * @param array $data Loan data with fields: amount_financed, interest_rate, 
     *                     payment_frequency, interest_calc_frequency, first_payment_date, etc.
     * @return int Loan ID
     */
    public function insertLoan(array $data): int;

    /**
     * Retrieve a loan record by ID
     *
     * @param int $loan_id Loan ID
     * @return array Loan data with all fields
     * @throws RuntimeException If loan not found
     */
    public function getLoan(int $loan_id): array;

    /**
     * Insert schedule row for a loan
     *
     * @param int $loan_id Loan ID
     * @param array $schedule_row Schedule data: payment_date, payment_amount, 
     *                             principal_payment, interest_payment, ending_balance, etc.
     * @return void
     */
    public function insertSchedule(int $loan_id, array $schedule_row): void;

    /**
     * Insert a loan event (extra payment, skip payment, etc.)
     *
     * @param int $loan_id Loan ID
     * @param LoanEvent $event Event object
     * @return int Event ID
     */
    public function insertLoanEvent(int $loan_id, LoanEvent $event): int;

    /**
     * Get all events for a loan
     *
     * @param int $loan_id Loan ID
     * @return array Array of LoanEvent data
     */
    public function getLoanEvents(int $loan_id): array;

    /**
     * Delete schedule rows after a given date (for recalculation)
     *
     * @param int $loan_id Loan ID
     * @param string $date Date in YYYY-MM-DD format
     * @return void
     */
    public function deleteScheduleAfterDate(int $loan_id, string $date): void;

    /**
     * Get schedule rows after a given date
     *
     * @param int $loan_id Loan ID
     * @param string $date Date in YYYY-MM-DD format
     * @return array Array of schedule rows
     */
    public function getScheduleRowsAfterDate(int $loan_id, string $date): array;

    /**
     * Update a single schedule row
     *
     * @param int $staging_id Schedule row ID
     * @param array $updates Fields to update
     * @return void
     */
    public function updateScheduleRow(int $staging_id, array $updates): void;

    /**
     * Get all schedule rows for a loan
     *
     * @param int $loan_id Loan ID
     * @return array Array of all schedule rows
     */
    public function getScheduleRows(int $loan_id): array;
}
