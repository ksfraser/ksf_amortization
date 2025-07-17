<?php
namespace Ksfraser\Amortizations;

/**
 * Interface for data access abstraction
 * @package Ksfraser\Amortizations
 */
interface DataProviderInterface {
    public function insertLoan(array $data): int;
    public function getLoan(int $loan_id): array;
    public function insertSchedule(int $loan_id, array $schedule_row): void;
}
