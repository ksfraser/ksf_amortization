<?php
namespace Ksfraser\Amortizations\FA;

use Ksfraser\Amortizations\DataProviderInterface;

/**
 * Mock base hooks class for FrontAccounting (for linting and local development)
 */
if (!class_exists('hooks')) {
    require_once __DIR__ . '/../fa_mock/hooks.php';
}

/**
 * Mock class for FADataProvider for testing and documentation.
 */
class FADataProviderMock implements DataProviderInterface
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
