<?php
namespace Ksfraser\Amortizations\SuiteCRM;

use Ksfraser\Amortizations\LoanEvent;
use Ksfraser\Amortizations\LoanEventProviderInterface;

class LoanEventProvider implements \Ksfraser\Amortizations\LoanEventProviderInterface {
    public function insertLoanEvent(LoanEvent $event): void {
        $bean = \BeanFactory::newBean('LoanEvent');
        $bean->loan_id = $event->loan_id;
        $bean->event_type = $event->event_type;
        $bean->event_date = $event->event_date;
        $bean->amount = $event->amount;
        $bean->notes = $event->notes;
        $bean->save();
    }
    public function getLoanEvents(int $loan_id): array {
        $query = "loan_id = '{$loan_id}'";
        $beans = \BeanFactory::getBean('LoanEvent')->get_full_list('', $query);
        $events = [];
        foreach ($beans as $bean) {
            $events[] = new LoanEvent([
                'id' => $bean->id,
                'loan_id' => $bean->loan_id,
                'event_type' => $bean->event_type,
                'event_date' => $bean->event_date,
                'amount' => $bean->amount,
                'notes' => $bean->notes
            ]);
        }
        return $events;
    }
    public function updateLoanEvent(LoanEvent $event): void {
        $bean = \BeanFactory::getBean('LoanEvent', $event->id);
        if ($bean) {
            $bean->event_type = $event->event_type;
            $bean->event_date = $event->event_date;
            $bean->amount = $event->amount;
            $bean->notes = $event->notes;
            $bean->save();
        }
    }
    public function deleteLoanEvent(int $event_id): void {
        $bean = \BeanFactory::getBean('LoanEvent', $event_id);
        if ($bean) {
            $bean->mark_deleted($event_id);
        }
    }
}
