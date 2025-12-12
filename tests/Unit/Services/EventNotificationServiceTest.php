<?php

namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\EventNotificationService;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class EventNotificationServiceTest extends TestCase
{
    private EventNotificationService $service;
    private Loan $loan;

    protected function setUp(): void
    {
        $this->service = new EventNotificationService();
        $this->loan = $this->createTestLoan();
    }

    private function createTestLoan(): Loan
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(200000.00);
        $loan->setAnnualRate(0.04);
        $loan->setMonths(360);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(195000.00);
        return $loan;
    }

    public function testRegisterEventSubscriber(): void
    {
        $subscriber = [
            'name' => 'email_notifier',
            'event_types' => ['payment_due', 'payoff_milestone'],
            'handler' => 'sendEmail',
        ];

        $this->service->registerSubscriber($subscriber);
        $subscribers = $this->service->getSubscribers();

        $this->assertCount(1, $subscribers);
        $this->assertEquals('email_notifier', $subscribers[0]['name']);
    }

    public function testUnregisterEventSubscriber(): void
    {
        $subscriber = [
            'name' => 'email_notifier',
            'event_types' => ['payment_due'],
            'handler' => 'sendEmail',
        ];

        $this->service->registerSubscriber($subscriber);
        $this->service->unregisterSubscriber('email_notifier');
        $subscribers = $this->service->getSubscribers();

        $this->assertEmpty($subscribers);
    }

    public function testTriggerPaymentDueEvent(): void
    {
        $event = $this->service->triggerPaymentDueEvent(
            $this->loan->getId(),
            1,
            954.83,
            '2024-02-01'
        );

        $this->assertIsArray($event);
        $this->assertEquals('payment_due', $event['type']);
        $this->assertEquals($this->loan->getId(), $event['loan_id']);
        $this->assertEquals(954.83, $event['amount']);
    }

    public function testTriggerPayoffMilestoneEvent(): void
    {
        $milestone = $this->service->triggerPayoffMilestoneEvent(
            $this->loan->getId(),
            0.25,
            '2028-01-01'
        );

        $this->assertIsArray($milestone);
        $this->assertEquals('payoff_milestone', $milestone['type']);
        $this->assertEquals(0.25, $milestone['payoff_percentage']);
    }

    public function testTriggerRateChangeEvent(): void
    {
        $event = $this->service->triggerRateChangeEvent(
            $this->loan->getId(),
            0.04,
            0.045,
            '2024-03-01'
        );

        $this->assertIsArray($event);
        $this->assertEquals('rate_change', $event['type']);
        $this->assertEquals(0.045, $event['new_rate']);
    }

    public function testGetEventsForLoan(): void
    {
        $this->service->triggerPaymentDueEvent($this->loan->getId(), 1, 954.83, '2024-02-01');
        $this->service->triggerPaymentDueEvent($this->loan->getId(), 2, 954.83, '2024-03-01');

        $events = $this->service->getEventsForLoan($this->loan->getId());

        $this->assertCount(2, $events);
        $this->assertEquals('payment_due', $events[0]['type']);
    }

    public function testFilterEventsByType(): void
    {
        $this->service->triggerPaymentDueEvent($this->loan->getId(), 1, 954.83, '2024-02-01');
        $this->service->triggerPayoffMilestoneEvent($this->loan->getId(), 0.5, '2028-01-01');

        $paymentEvents = $this->service->filterEventsByType('payment_due');

        $this->assertNotEmpty($paymentEvents);
        $this->assertEquals('payment_due', $paymentEvents[0]['type']);
    }

    public function testGenerateNotificationForEvent(): void
    {
        $event = $this->service->triggerPaymentDueEvent($this->loan->getId(), 1, 954.83, '2024-02-01');

        $notification = $this->service->generateNotification($event);

        $this->assertIsArray($notification);
        $this->assertArrayHasKey('subject', $notification);
        $this->assertArrayHasKey('message', $notification);
        $this->assertArrayHasKey('channels', $notification);
    }

    public function testScheduleEventForFutureDate(): void
    {
        $scheduledEvent = $this->service->scheduleEventForFutureDate(
            'payment_due',
            $this->loan->getId(),
            ['amount' => 954.83, 'month' => 1],
            '2024-02-01'
        );

        $this->assertIsArray($scheduledEvent);
        $this->assertEquals('payment_due', $scheduledEvent['type']);
        $this->assertEquals('scheduled', $scheduledEvent['status']);
    }

    public function testExecuteScheduledEvents(): void
    {
        $this->service->scheduleEventForFutureDate(
            'payment_due',
            $this->loan->getId(),
            ['amount' => 954.83],
            date('Y-m-d')
        );

        $executed = $this->service->executeScheduledEvents();

        $this->assertIsArray($executed);
        $this->assertNotEmpty($executed);
    }

    public function testGenerateEventAuditTrail(): void
    {
        $this->service->triggerPaymentDueEvent($this->loan->getId(), 1, 954.83, '2024-02-01');
        $this->service->triggerPayoffMilestoneEvent($this->loan->getId(), 0.25, '2024-03-01');

        $auditTrail = $this->service->generateEventAuditTrail($this->loan->getId());

        $this->assertIsArray($auditTrail);
        $this->assertArrayHasKey('loan_id', $auditTrail);
        $this->assertArrayHasKey('events', $auditTrail);
        $this->assertCount(2, $auditTrail['events']);
    }

    public function testGenerateEventSummary(): void
    {
        $this->service->triggerPaymentDueEvent($this->loan->getId(), 1, 954.83, '2024-02-01');
        $this->service->triggerPaymentDueEvent($this->loan->getId(), 2, 954.83, '2024-03-01');
        $this->service->triggerPayoffMilestoneEvent($this->loan->getId(), 0.5, '2024-03-01');

        $summary = $this->service->generateEventSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_events', $summary);
        $this->assertArrayHasKey('event_types', $summary);
        $this->assertGreaterThan(0, $summary['total_events']);
    }

    public function testExportEventLog(): void
    {
        $this->service->triggerPaymentDueEvent($this->loan->getId(), 1, 954.83, '2024-02-01');

        $json = $this->service->exportEventLogToJSON();

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }

    public function testClearOldEvents(): void
    {
        $this->service->triggerPaymentDueEvent($this->loan->getId(), 1, 954.83, '2024-02-01');

        $cleared = $this->service->clearEventsOlderThan('2023-12-31');

        $this->assertIsInt($cleared);
    }
}
