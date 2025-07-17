<?php
namespace Ksfraser\Amortizations\WordPress;

use Ksfraser\Amortizations\DataProviderInterface;

/**
 * Mock class for WPDataProvider for testing and documentation.
 */
class WPDataProviderMock implements DataProviderInterface
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
