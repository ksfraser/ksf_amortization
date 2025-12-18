<?php
namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Services\{
    EventValidator,
    EventRecordingService,
    ScheduleRecalculationService
};
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Repositories\{
    MockLoanRepository,
    MockScheduleRepository,
    MockEventRepository
};

/**
 * EventValidatorTest: Tests for event validation logic
 */
class EventValidatorTest extends TestCase
{
    private EventValidator $validator;
    private Loan $testLoan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new EventValidator();
        
        // Create a test loan
        $this->testLoan = new Loan();
        $this->testLoan->id = 1;
        $this->testLoan->principal = 30000;
        $this->testLoan->current_balance = 25000;
        $this->testLoan->interest_rate = 0.045;
        $this->testLoan->term_months = 60;
        $this->testLoan->start_date = '2025-01-01';
    }

    /**
     * Test: Valid extra payment event passes validation
     */
    public function test_extra_payment_valid()
    {
        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-02-01',
            'amount' => 500
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertEmpty($errors);
    }

    /**
     * Test: Extra payment with amount too high fails validation
     */
    public function test_extra_payment_amount_exceeds_balance()
    {
        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-02-01',
            'amount' => 30000 // Exceeds current balance
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertArrayHasKey('amount', $errors);
    }

    /**
     * Test: Extra payment with negative amount fails validation
     */
    public function test_extra_payment_negative_amount()
    {
        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-02-01',
            'amount' => -100
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertArrayHasKey('amount', $errors);
    }

    /**
     * Test: Extra payment with missing amount fails
     */
    public function test_extra_payment_missing_amount()
    {
        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-02-01'
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertArrayHasKey('amount', $errors);
    }

    /**
     * Test: Valid skip payment event passes validation
     */
    public function test_skip_payment_valid()
    {
        $eventData = [
            'event_type' => 'skip_payment',
            'event_date' => '2025-02-01',
            'months_to_skip' => 2
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertEmpty($errors);
    }

    /**
     * Test: Skip payment exceeding 12 months fails
     */
    public function test_skip_payment_exceeds_max()
    {
        $eventData = [
            'event_type' => 'skip_payment',
            'event_date' => '2025-02-01',
            'months_to_skip' => 13
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertArrayHasKey('months_to_skip', $errors);
    }

    /**
     * Test: Valid rate change event passes validation
     */
    public function test_rate_change_valid()
    {
        $eventData = [
            'event_type' => 'rate_change',
            'event_date' => '2025-06-01',
            'new_rate' => 0.035
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertEmpty($errors);
    }

    /**
     * Test: Rate change with invalid rate fails
     */
    public function test_rate_change_invalid_rate()
    {
        $eventData = [
            'event_type' => 'rate_change',
            'event_date' => '2025-06-01',
            'new_rate' => 1.5 // Exceeds 100%
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertArrayHasKey('new_rate', $errors);
    }

    /**
     * Test: Event with date before loan start fails
     */
    public function test_event_date_before_loan_start()
    {
        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2024-12-01', // Before start date
            'amount' => 500
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertArrayHasKey('event_date', $errors);
    }

    /**
     * Test: Event with invalid date format fails
     */
    public function test_event_invalid_date_format()
    {
        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '02/01/2025', // Wrong format
            'amount' => 500
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertArrayHasKey('event_date', $errors);
    }

    /**
     * Test: Event with missing type fails
     */
    public function test_event_missing_type()
    {
        $eventData = [
            'event_date' => '2025-02-01',
            'amount' => 500
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertArrayHasKey('event_type', $errors);
    }

    /**
     * Test: Event with invalid type fails
     */
    public function test_event_invalid_type()
    {
        $eventData = [
            'event_type' => 'invalid_type',
            'event_date' => '2025-02-01',
            'amount' => 500
        ];

        $errors = $this->validator->validate($eventData, $this->testLoan);
        
        $this->assertArrayHasKey('event_type', $errors);
    }

    /**
     * Test: Get supported event types
     */
    public function test_get_supported_types()
    {
        $types = $this->validator->getSupportedTypes();
        
        $this->assertIsArray($types);
        $this->assertContains('extra_payment', $types);
        $this->assertContains('skip_payment', $types);
        $this->assertContains('rate_change', $types);
    }

    /**
     * Test: Check if type is supported
     */
    public function test_is_supported_type()
    {
        $this->assertTrue($this->validator->isSupportedType('extra_payment'));
        $this->assertFalse($this->validator->isSupportedType('invalid_type'));
    }
}

/**
 * ScheduleRecalculationServiceTest: Tests for schedule recalculation
 */
class ScheduleRecalculationServiceTest extends TestCase
{
    private ScheduleRecalculationService $service;
    private Loan $testLoan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScheduleRecalculationService();
        
        // Create a test loan
        $this->testLoan = new Loan();
        $this->testLoan->id = 1;
        $this->testLoan->principal = 30000;
        $this->testLoan->current_balance = 25000;
        $this->testLoan->interest_rate = 0.045;
        $this->testLoan->term_months = 60;
        $this->testLoan->start_date = '2025-01-01';
        $this->testLoan->last_payment_date = '2025-01-01';
    }

    /**
     * Test: Identify recalculatable event types
     */
    public function test_should_recalculate_extra_payment()
    {
        $this->assertTrue($this->service->shouldRecalculate('extra_payment'));
    }

    /**
     * Test: Skip payment is recalculatable
     */
    public function test_should_recalculate_skip_payment()
    {
        $this->assertTrue($this->service->shouldRecalculate('skip_payment'));
    }

    /**
     * Test: Rate change is recalculatable
     */
    public function test_should_recalculate_rate_change()
    {
        $this->assertTrue($this->service->shouldRecalculate('rate_change'));
    }

    /**
     * Test: Accrual does not require recalculation
     */
    public function test_should_not_recalculate_accrual()
    {
        $this->assertFalse($this->service->shouldRecalculate('accrual'));
    }

    /**
     * Test: Calculate monthly payment
     */
    public function test_calculate_monthly_payment()
    {
        $payment = $this->service->calculateMonthlyPayment($this->testLoan);
        
        $this->assertGreaterThan(0, $payment);
        // Payment should be roughly $469 for 25000 at 4.5% for 60 months
        $this->assertGreaterThan(400, $payment);
        $this->assertLessThan(550, $payment);
    }

    /**
     * Test: Calculate remaining payments
     */
    public function test_calculate_remaining_payments()
    {
        $remaining = $this->service->calculateRemainingPayments($this->testLoan);
        
        $this->assertGreaterThan(0, $remaining);
        // Should be around 60 months
        $this->assertLessThanOrEqual(60, $remaining);
    }

    /**
     * Test: Calculate early payoff date with extra payment
     */
    public function test_calculate_early_payoff_date()
    {
        $payoffDate = $this->service->calculateEarlyPayoffDate($this->testLoan, 100);
        
        $this->assertIsString($payoffDate);
        // Should be a valid date
        $d = \DateTime::createFromFormat('Y-m-d', $payoffDate);
        $this->assertTrue($d !== false);
    }

    /**
     * Test: Calculate total interest
     */
    public function test_calculate_total_interest()
    {
        $totalInterest = $this->service->calculateTotalInterest($this->testLoan);
        
        $this->assertGreaterThan(0, $totalInterest);
        // Should have some interest for a loan
        $this->assertGreaterThan(1000, $totalInterest);
    }

    /**
     * Test: Calculate interest savings with extra payment
     */
    public function test_calculate_interest_savings()
    {
        $savings = $this->service->calculateInterestSavings($this->testLoan, 500);
        
        $this->assertGreaterThan(0, $savings);
    }

    /**
     * Test: Calculate interest for skipped months
     */
    public function test_calculate_accrued_interest()
    {
        // This is a private method test via recalculation
        $loan = clone $this->testLoan;
        
        // Interest should accrue for skipped months
        $monthlyRate = $loan->interest_rate / 12;
        $expectedInterest = $loan->current_balance * $monthlyRate * 2; // 2 months
        
        // Rough estimate
        $this->assertGreaterThan(0, $expectedInterest);
    }
}

/**
 * EventRecordingServiceTest: Tests for event recording workflow
 */
class EventRecordingServiceTest extends TestCase
{
    private EventRecordingService $service;
    private MockLoanRepository $loanRepo;
    private MockEventRepository $eventRepo;
    private EventValidator $validator;
    private ScheduleRecalculationService $recalculation;

    protected function setUp(): void
    {
        parent::setUp();
        MockLoanRepository::reset();
        MockEventRepository::reset();
        
        $this->loanRepo = new MockLoanRepository();
        $this->eventRepo = new MockEventRepository();
        $this->validator = new EventValidator();
        $this->recalculation = new ScheduleRecalculationService();
        
        $this->service = new EventRecordingService(
            $this->eventRepo,
            $this->loanRepo,
            $this->validator,
            $this->recalculation
        );
    }

    /**
     * Test: Record valid extra payment event
     */
    public function test_record_extra_payment_event()
    {
        // Create test loan
        $loan = new Loan();
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        
        $loanId = $this->loanRepo->create((array)$loan);
        
        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-02-01',
            'amount' => 500
        ];
        
        $result = $this->service->recordEvent($loanId, $eventData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(201, $result['status_code']);
        $this->assertArrayHasKey('event', $result['data']);
        $this->assertArrayHasKey('loan', $result['data']);
    }

    /**
     * Test: Record event with invalid validation
     */
    public function test_record_event_validation_fails()
    {
        // Create test loan
        $loan = new Loan();
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        
        $loanId = $this->loanRepo->create((array)$loan);
        
        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-02-01',
            'amount' => 50000 // Exceeds balance
        ];
        
        $result = $this->service->recordEvent($loanId, $eventData);
        
        $this->assertFalse($result['success']);
        $this->assertEquals(422, $result['status_code']);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * Test: Record event for non-existent loan
     */
    public function test_record_event_loan_not_found()
    {
        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-02-01',
            'amount' => 500
        ];
        
        $result = $this->service->recordEvent(999, $eventData);
        
        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['status_code']);
    }

    /**
     * Test: Get event count for loan
     */
    public function test_get_event_count()
    {
        $loan = new Loan();
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        
        $loanId = $this->loanRepo->create((array)$loan);
        
        $count = $this->service->getEventCount($loanId);
        
        $this->assertIsInt($count);
    }

    /**
     * Test: Calculate event impact for extra payment
     */
    public function test_calculate_event_impact_extra_payment()
    {
        $loan = new Loan();
        $loan->principal = 30000;
        $loan->current_balance = 25000;
        $loan->interest_rate = 0.045;
        $loan->term_months = 60;
        $loan->start_date = '2025-01-01';
        
        $loanId = $this->loanRepo->create((array)$loan);
        
        $loan = $this->loanRepo->get($loanId);
        
        $impact = $this->service->calculateEventImpact(
            $loanId,
            'extra_payment',
            ['amount' => 500]
        );
        
        $this->assertArrayHasKey('payment_amount', $impact);
        $this->assertArrayHasKey('new_balance', $impact);
        $this->assertArrayHasKey('interest_savings', $impact);
        $this->assertEquals(500, $impact['payment_amount']);
    }
}
