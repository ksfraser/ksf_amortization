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
        $data = $bean ? $bean->toArray() : [];
        if ($data && !isset($data['borrower_type'])) {
            $data['borrower_type'] = null;
        }
        return $data;
    }

    /**
     * Insert a new loan record
     */
    public function insertLoan(array $data): int
    {
        $bean = \BeanFactory::newBean('AmortizationLoans');
        if (!isset($data['borrower_type'])) {
            $data['borrower_type'] = null;
        }
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
        $bean = \BeanFactory::newBean('AmortizationEvents');
        $bean->loan_id = $loanId;
        $bean->event_type = $event->event_type;
        $bean->event_date = $event->event_date;
        $bean->amount = $event->amount;
        $bean->notes = $event->notes ?? '';
        $bean->created_at = date('Y-m-d H:i:s');
        $bean->save();
        return (int)$bean->id;
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
        $bean = \BeanFactory::newBean('AmortizationEvents');
        $where = "amortization_events.loan_id = '$loanId'";
        $beans = $bean->get_list('event_date', $where);
        $events = [];
        foreach ($beans['list'] as $eventBean) {
            $events[] = $eventBean->toArray();
        }
        return $events;
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
        $bean = \BeanFactory::newBean('AmortizationSchedules');
        $where = "amortization_schedules.loan_id = '$loanId' AND amortization_schedules.payment_date > '$date' AND amortization_schedules.posted_to_gl = 0";
        $beans = $bean->get_list('payment_date', $where);
        foreach ($beans['list'] as $scheduleBean) {
            $scheduleBean->mark_deleted($scheduleBean->id);
        }
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
        $bean = \BeanFactory::newBean('AmortizationSchedules');
        $where = "amortization_schedules.loan_id = '$loanId' AND amortization_schedules.payment_date > '$date'";
        $beans = $bean->get_list('payment_date', $where);
        $rows = [];
        foreach ($beans['list'] as $scheduleBean) {
            $rows[] = $scheduleBean->toArray();
        }
        return $rows;
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
        $bean = \BeanFactory::getBean('AmortizationSchedules', $stagingId);
        if ($bean) {
            foreach ($updates as $key => $value) {
                $bean->$key = $value;
            }
            $bean->updated_at = date('Y-m-d H:i:s');
            $bean->save();
        }
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
        $bean = \BeanFactory::newBean('AmortizationSchedules');
        $where = "amortization_schedules.loan_id = '$loanId'";
        $beans = $bean->get_list('payment_date', $where);
        $rows = [];
        foreach ($beans['list'] as $scheduleBean) {
            $rows[] = $scheduleBean->toArray();
        }
        return $rows;
    }
}
