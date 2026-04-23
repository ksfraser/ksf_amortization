<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Services\EventValidator;
use Ksfraser\Amortizations\Models\Loan;

class EventValidatorTest extends TestCase
{
    private EventValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new EventValidator();
    }

    private function createMockLoan(array $properties = []): Loan
    {
        $loan = new class extends Loan {
            private array $mockProperties = [];

            public function __construct()
            {
                parent::__construct();
            }

            public function __set(string $name, $value): void
            {
                $this->mockProperties[$name] = $value;
            }

            public function __get(string $name)
            {
                return $this->mockProperties[$name] ?? null;
            }

            public function __isset(string $name): bool
            {
                return isset($this->mockProperties[$name]);
            }

            public function setMockProperty(string $name, $value): void
            {
                $this->mockProperties[$name] = $value;
            }

            public function getMockProperty(string $name)
            {
                return $this->mockProperties[$name] ?? null;
            }
        };

        foreach ($properties as $key => $value) {
            $loan->setMockProperty($key, $value);
        }

        return $loan;
    }

    public function testGetSupportedTypes(): void
    {
        $types = $this->validator->getSupportedTypes();

        $this->assertContains('extra_payment', $types);
        $this->assertContains('skip_payment', $types);
        $this->assertContains('rate_change', $types);
        $this->assertContains('loan_modification', $types);
        $this->assertContains('payment_applied', $types);
        $this->assertContains('accrual', $types);
    }

    public function testIsSupportedTypeReturnsTrue(): void
    {
        $this->assertTrue($this->validator->isSupportedType('extra_payment'));
        $this->assertTrue($this->validator->isSupportedType('skip_payment'));
        $this->assertTrue($this->validator->isSupportedType('rate_change'));
        $this->assertTrue($this->validator->isSupportedType('loan_modification'));
        $this->assertTrue($this->validator->isSupportedType('payment_applied'));
        $this->assertTrue($this->validator->isSupportedType('accrual'));
    }

    public function testIsSupportedTypeReturnsFalse(): void
    {
        $this->assertFalse($this->validator->isSupportedType('invalid_type'));
        $this->assertFalse($this->validator->isSupportedType(''));
        $this->assertFalse($this->validator->isSupportedType('PAYMENT'));
    }

    public function testValidateReturnsEmptyForValidEvent(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
            'current_balance' => 50000,
            'principal' => 50000,
        ]);

        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-06-15',
            'amount' => 1000,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertEmpty($errors);
    }

    public function testValidateReturnsErrorForMissingEventType(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_date' => '2025-06-15',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('event_type', $errors);
        $this->assertEquals('Event type is required', $errors['event_type']);
    }

    public function testValidateReturnsErrorForInvalidEventType(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'invalid_type',
            'event_date' => '2025-06-15',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('event_type', $errors);
        $this->assertStringContainsString('Invalid event type', $errors['event_type']);
    }

    public function testValidateReturnsErrorForMissingEventDate(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'extra_payment',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('event_date', $errors);
        $this->assertEquals('Event date is required', $errors['event_date']);
    }

    public function testValidateReturnsErrorForInvalidDateFormat(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025/06/15',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('event_date', $errors);
        $this->assertEquals('Invalid date format (YYYY-MM-DD)', $errors['event_date']);
    }

    public function testValidateExtraPaymentWithValidAmount(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
            'current_balance' => 50000,
            'principal' => 50000,
        ]);

        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-06-15',
            'amount' => 5000,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayNotHasKey('amount', $errors);
    }

    public function testValidateExtraPaymentWithMissingAmount(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-06-15',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('amount', $errors);
        $this->assertEquals('Amount is required for extra payment', $errors['amount']);
    }

    public function testValidateExtraPaymentWithNonNumericAmount(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-06-15',
            'amount' => 'abc',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('amount', $errors);
        $this->assertEquals('Amount must be numeric', $errors['amount']);
    }

    public function testValidateExtraPaymentWithNegativeAmount(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-06-15',
            'amount' => -100,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('amount', $errors);
        $this->assertEquals('Amount must be positive', $errors['amount']);
    }

    public function testValidateSkipPaymentWithValidMonths(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'skip_payment',
            'event_date' => '2025-06-15',
            'months_to_skip' => 3,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayNotHasKey('months_to_skip', $errors);
    }

    public function testValidateSkipPaymentWithMissingMonths(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'skip_payment',
            'event_date' => '2025-06-15',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('months_to_skip', $errors);
        $this->assertEquals('Number of months is required', $errors['months_to_skip']);
    }

    public function testValidateSkipPaymentWithZeroMonths(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'skip_payment',
            'event_date' => '2025-06-15',
            'months_to_skip' => 0,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('months_to_skip', $errors);
    }

    public function testValidateSkipPaymentWithTooManyMonths(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'skip_payment',
            'event_date' => '2025-06-15',
            'months_to_skip' => 15,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('months_to_skip', $errors);
        $this->assertEquals('Cannot skip more than 12 months', $errors['months_to_skip']);
    }

    public function testValidateRateChangeWithValidRate(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'rate_change',
            'event_date' => '2025-06-15',
            'new_rate' => 0.065,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayNotHasKey('new_rate', $errors);
    }

    public function testValidateRateChangeWithMissingRate(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'rate_change',
            'event_date' => '2025-06-15',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('new_rate', $errors);
        $this->assertEquals('New interest rate is required', $errors['new_rate']);
    }

    public function testValidateRateChangeWithNegativeRate(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'rate_change',
            'event_date' => '2025-06-15',
            'new_rate' => -0.05,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('new_rate', $errors);
        $this->assertEquals('Interest rate must be between 0 and 1 (0% to 100%)', $errors['new_rate']);
    }

    public function testValidateRateChangeWithRateGreaterThanOne(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'rate_change',
            'event_date' => '2025-06-15',
            'new_rate' => 1.5,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('new_rate', $errors);
        $this->assertEquals('Interest rate must be between 0 and 1 (0% to 100%)', $errors['new_rate']);
    }

    public function testValidateLoanModificationWithValidData(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'loan_modification',
            'event_date' => '2025-06-15',
            'adjustment_type' => 'principal',
            'value' => 45000,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertEmpty($errors);
    }

    public function testValidateLoanModificationWithMissingAdjustmentType(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'loan_modification',
            'event_date' => '2025-06-15',
            'value' => 45000,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('adjustment_type', $errors);
    }

    public function testValidateLoanModificationWithInvalidAdjustmentType(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'loan_modification',
            'event_date' => '2025-06-15',
            'adjustment_type' => 'invalid',
            'value' => 45000,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('adjustment_type', $errors);
        $this->assertEquals('Adjustment type must be "principal" or "term"', $errors['adjustment_type']);
    }

    public function testValidatePaymentAppliedWithValidData(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'payment_applied',
            'event_date' => '2025-06-15',
            'amount' => 1000,
            'applied_to' => 'principal',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertEmpty($errors);
    }

    public function testValidatePaymentAppliedWithMissingAppliedTo(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'payment_applied',
            'event_date' => '2025-06-15',
            'amount' => 1000,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('applied_to', $errors);
    }

    public function testValidatePaymentAppliedWithInvalidAppliedTo(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'payment_applied',
            'event_date' => '2025-06-15',
            'amount' => 1000,
            'applied_to' => 'invalid',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('applied_to', $errors);
        $this->assertEquals('Applied to must be "principal", "interest", or "auto"', $errors['applied_to']);
    }

    public function testValidateAccrualWithValidAmount(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'accrual',
            'event_date' => '2025-06-15',
            'amount' => 250.50,
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertEmpty($errors);
    }

    public function testValidateAccrualWithMissingAmount(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'accrual',
            'event_date' => '2025-06-15',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('amount', $errors);
        $this->assertEquals('Amount is required for accrual', $errors['amount']);
    }

    public function testValidateReturnsMultipleErrors(): void
    {
        $loan = $this->createMockLoan([
            'start_date' => '2025-01-01',
        ]);

        $eventData = [
            'event_type' => 'invalid_type',
            'event_date' => 'invalid-date',
        ];

        $errors = $this->validator->validate($eventData, $loan);
        $this->assertArrayHasKey('event_type', $errors);
        $this->assertArrayHasKey('event_date', $errors);
    }
}
