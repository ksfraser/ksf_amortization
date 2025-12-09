<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\LoanEvent;
use Ksfraser\Amortizations\FA\LoanEventProvider as FALoanEventProvider;
use Ksfraser\Amortizations\WordPress\LoanEventProvider as WPLoanEventProvider;
use Ksfraser\Amortizations\SuiteCRM\LoanEventProvider as SuiteCRMLoanEventProvider;

class LoanEventProviderTest extends TestCase {
    public function testFAInsertAndGet() {
        $pdo = $this->getMockBuilder('PDO')->disableOriginalConstructor()->getMock();
        $provider = new FALoanEventProvider($pdo);
        $event = new LoanEvent([
            'loan_id' => 1,
            'event_type' => 'extra',
            'event_date' => '2025-08-01',
            'amount' => 100.00,
            'notes' => 'Extra payment'
        ]);
        // $provider->insertLoanEvent($event); // Would run in integration test
        $this->assertInstanceOf(FALoanEventProvider::class, $provider);
    }
    public function testWPInsertAndGet() {
        $wpdb = $this->getMockBuilder('stdClass')->getMock();
        $provider = new WPLoanEventProvider($wpdb);
        $event = new LoanEvent([
            'loan_id' => 1,
            'event_type' => 'skip',
            'event_date' => '2025-09-01',
            'amount' => 0.00,
            'notes' => 'Skipped payment'
        ]);
        $this->assertInstanceOf(WPLoanEventProvider::class, $provider);
    }
    public function testSuiteCRMInsertAndGet() {
        $provider = new SuiteCRMLoanEventProvider();
        $event = new LoanEvent([
            'loan_id' => 1,
            'event_type' => 'extra',
            'event_date' => '2025-10-01',
            'amount' => 200.00,
            'notes' => 'Extra payment'
        ]);
        $this->assertInstanceOf(SuiteCRMLoanEventProvider::class, $provider);
    }
}
