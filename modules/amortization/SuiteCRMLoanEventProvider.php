<?php
namespace Ksfraser\Amortizations;

use Ksfraser\Amortizations\LoanEvent;
use Ksfraser\Amortizations\LoanEventProviderInterface;

/**
 * SuiteCRM implementation of LoanEventProviderInterface
 * Uses SuiteCRM Beans/ORM for data access
 */
class SuiteCRMLoanEventProvider implements LoanEventProviderInterface {
    private $beanFactory;
    private $tableName;
    public function __construct($beanFactory, $tableName = 'ksf_amort_loan_events') {
        $this->beanFactory = $beanFactory;
        $this->tableName = $tableName;
    }
    public function insertLoanEvent(LoanEvent $event): void {
        $bean = $this->beanFactory->newBean($this->tableName);
        $bean->loan_id = $event->loan_id;
        $bean->event_type = $event->event_type;
        $bean->event_date = $event->event_date;
        $bean->amount = $event->amount;
        $bean->notes = $event->notes;
        $bean->save();
    }
    public function getLoanEvents(int $loan_id): array {
        $query = "loan_id = '" . $loan_id . "'";
        $beans = $this->beanFactory->getBeans($this->tableName, $query, ['order_by' => 'event_date ASC']);
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
        $bean = $this->beanFactory->getBean($this->tableName, $event->id);
        if ($bean) {
            $bean->event_type = $event->event_type;
            $bean->event_date = $event->event_date;
            $bean->amount = $event->amount;
            $bean->notes = $event->notes;
            $bean->save();
        }
    }
    public function deleteLoanEvent(int $event_id): void {
        $bean = $this->beanFactory->getBean($this->tableName, $event_id);
        if ($bean) {
            $bean->mark_deleted($event_id);
        }
    }
}<?php
namespace Ksfraser\Amortizations;

use Ksfraser\Amortizations\LoanEvent;
use Ksfraser\Amortizations\LoanEventProviderInterface;

/**
 * SuiteCRM implementation of LoanEventProviderInterface
 */
class SuiteCRMLoanEventProvider implements LoanEventProviderInterface {
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
