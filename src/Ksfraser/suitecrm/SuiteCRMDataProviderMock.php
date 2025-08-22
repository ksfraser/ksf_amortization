<?php
namespace Ksfraser\Amortizations\SuiteCRM;

use Ksfraser\Amortizations\DataProviderInterface;

/**
 * Mock class for SuiteCRMDataProvider for testing and documentation.
 */
class SuiteCRMDataProviderMock implements DataProviderInterface
{
    public function getLoan(int $loan_id): array
    {
        return ['id' => $loan_id, 'principal' => 1000, 'interest_rate' => 5.0, 'term' => 12, 'schedule' => 'monthly'];
    }

    public function insertLoan(array $data): int
    {
        return 42;
    }

    public function insertSchedule(int $loan_id, array $schedule_row): void
    {
        // No-op for mock
    }
}
