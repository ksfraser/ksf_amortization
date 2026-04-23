<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\LoanEvent;

class LoanEventTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $event = new LoanEvent();

        $this->assertNull($event->id);
        $this->assertNull($event->loan_id);
        $this->assertNull($event->event_type);
        $this->assertNull($event->event_date);
        $this->assertEquals(0.00, $event->amount);
        $this->assertEquals('', $event->notes);
    }

    public function testConstructorWithData(): void
    {
        $event = new LoanEvent([
            'id' => 1,
            'loan_id' => 100,
            'event_type' => 'extra_payment',
            'event_date' => '2025-06-15',
            'amount' => 500.00,
            'notes' => 'Early payoff payment',
        ]);

        $this->assertEquals(1, $event->id);
        $this->assertEquals(100, $event->loan_id);
        $this->assertEquals('extra_payment', $event->event_type);
        $this->assertEquals('2025-06-15', $event->event_date);
        $this->assertEquals(500.00, $event->amount);
        $this->assertEquals('Early payoff payment', $event->notes);
    }

    public function testConstructorWithPartialData(): void
    {
        $event = new LoanEvent([
            'loan_id' => 100,
            'event_type' => 'skip_payment',
        ]);

        $this->assertNull($event->id);
        $this->assertEquals(100, $event->loan_id);
        $this->assertEquals('skip_payment', $event->event_type);
        $this->assertEquals(0.00, $event->amount);
        $this->assertEquals('', $event->notes);
    }

    public function testEventTypes(): void
    {
        $skipEvent = new LoanEvent([
            'event_type' => 'skip',
        ]);
        $this->assertEquals('skip', $skipEvent->event_type);

        $extraEvent = new LoanEvent([
            'event_type' => 'extra',
        ]);
        $this->assertEquals('extra', $extraEvent->event_type);

        $partialEvent = new LoanEvent([
            'event_type' => 'partial',
        ]);
        $this->assertEquals('partial', $partialEvent->event_type);
    }

    public function testDefaultAmountIsZero(): void
    {
        $event = new LoanEvent();
        $this->assertEquals(0.00, $event->amount);
    }

    public function testDefaultNotesIsEmpty(): void
    {
        $event = new LoanEvent();
        $this->assertEquals('', $event->notes);
    }

    public function testAmountCanBeSet(): void
    {
        $event = new LoanEvent();
        $event->amount = 1234.56;
        $this->assertEquals(1234.56, $event->amount);
    }

    public function testNotesCanBeSet(): void
    {
        $event = new LoanEvent();
        $event->notes = 'Customer requested skip due to vacation';
        $this->assertEquals('Customer requested skip due to vacation', $event->notes);
    }

    public function testEventDateCanBeString(): void
    {
        $event = new LoanEvent([
            'event_date' => '2025-12-25',
        ]);
        $this->assertEquals('2025-12-25', $event->event_date);
    }

    public function testEventDateCanBeDateTime(): void
    {
        $date = new \DateTime('2025-12-25');
        $event = new LoanEvent([
            'event_date' => $date,
        ]);
        $this->assertSame($date, $event->event_date);
    }
}
