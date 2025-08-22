<?php
namespace Ksfraser\Amortizations\SuiteCRM;

use Ksfraser\Amortizations\DataProviderInterface;

/**
 * SuiteCRM adaptor for Amortization business logic.
 * Implements DataProviderInterface for SuiteCRM integration.
 */
class SuiteCRMDataProvider implements DataProviderInterface
{
    /**
     * Fetch loan data from SuiteCRM module
     */
    public function getLoan(int $loan_id): array
    {
        $bean = \BeanFactory::getBean('AmortizationLoans', $loan_id);
        return $bean ? $bean->toArray() : [];
    }

    /**
     * Insert a new loan record
     */
    public function insertLoan(array $data): int
    {
        $bean = \BeanFactory::newBean('AmortizationLoans');
        foreach ($data as $key => $value) {
            $bean->$key = $value;
        }
        $bean->save();
        return (int)$bean->id;
    }

    /**
     * Insert a schedule row for a loan
     */
    public function insertSchedule(int $loan_id, array $schedule_row): void
    {
        $bean = \BeanFactory::newBean('AmortizationSchedules');
        $bean->loan_id = $loan_id;
        foreach ($schedule_row as $key => $value) {
            $bean->$key = $value;
        }
        $bean->save();
    }
    // ...existing code...
}
