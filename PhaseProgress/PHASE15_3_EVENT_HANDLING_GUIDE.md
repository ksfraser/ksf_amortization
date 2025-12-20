# Phase 15.3: Event Handling & Recording Implementation Guide

## Overview

Phase 15.3 focuses on implementing event recording with proper business logic, validation, and integration with the existing loan and schedule systems.

---

## Event Types & Business Rules

### Supported Event Types

| Type | Description | Required Fields | Business Logic |
|------|-------------|-----------------|-----------------|
| `extra_payment` | Additional payment beyond regular schedule | amount, date | Reduce principal, recalculate remaining schedule |
| `skip_payment` | Skip one or more payment(s) | date, months_to_skip | Extend term, recalculate interest |
| `rate_change` | Interest rate adjustment | new_rate, effective_date | Recalculate remaining payments |
| `loan_modification` | Term or principal adjustment | adjustment_type, value | Full schedule regeneration |
| `payment_applied` | Manual payment recording | amount, date, applied_to | Update payment status |
| `accrual` | Interest accrual event | amount, date | Update loan status tracking |

---

## Implementation Architecture

### 1. Event Recording Service

**File:** `src/Services/EventRecordingService.php`

**Responsibilities:**
- Validate event data
- Record event in repository
- Trigger schedule recalculation
- Update loan status
- Propagate changes

**Key Methods:**
```php
recordEvent(loanId, eventData): Event
├── validateEvent(eventData)
├── createEvent(eventData)
├── updateLoan(loanId, eventData)
├── triggerRecalculation(loanId)
└── propagateChanges(loanId)
```

### 2. Event Validation

**File:** `src/Services/EventValidator.php`

**Validation Rules:**
- Event type must be in supported list
- Date must be valid and after loan start date
- Amount (if applicable) must be positive
- Event should not predate loan start or post-date loan end
- Extra payment: amount ≤ remaining balance
- Rate change: new rate must be valid decimal
- Loan must exist and be active

### 3. Schedule Recalculation Engine

**File:** `src/Services/ScheduleRecalculationService.php`

**Operations:**
- Recalculate remaining schedule after extra payment
- Extend term after skip payment
- Recalculate all payments after rate change
- Full regeneration after loan modification

---

## Event Recording Flow

### Complete Event Recording Process

```
recordEvent(loanId, eventData)
    ↓
[1] Validate Event
    ├── Event type validation
    ├── Date validation
    ├── Amount validation (if applicable)
    └── Loan existence check
    ↓
[2] Create Event Record
    ├── Generate event ID
    ├── Set timestamps
    └── Store in repository
    ↓
[3] Update Loan Status
    ├── Update loan metadata
    ├── Record event relationship
    └── Update last modified date
    ↓
[4] Trigger Recalculation
    ├── Apply event to loan state
    ├── Call appropriate recalculation
    └── Update schedule
    ↓
[5] Propagate Changes
    ├── Update derived calculations
    ├── Log changes
    └── Return event with updated loan
    ↓
Response: {
    "success": true,
    "event": {...event data...},
    "loan": {...updated loan...},
    "schedule": {...updated schedule...}
}
```

---

## Implementation Details

### Event Recording Service Implementation

```php
<?php
namespace Ksfraser\Amortizations\Services;

use Ksfraser\Amortizations\Repositories\{
    EventRepositoryInterface,
    LoanRepositoryInterface,
    ScheduleRepositoryInterface
};
use Ksfraser\Amortizations\Models\{
    Event,
    Loan,
    Schedule
};
use Ksfraser\Amortizations\Api\{
    RecordEventRequest,
    ApiResponse,
    ApiException
};

class EventRecordingService
{
    private EventRepositoryInterface $eventRepository;
    private LoanRepositoryInterface $loanRepository;
    private ScheduleRepositoryInterface $scheduleRepository;
    private EventValidator $eventValidator;
    private ScheduleRecalculationService $recalculationService;

    public function __construct(
        EventRepositoryInterface $eventRepository,
        LoanRepositoryInterface $loanRepository,
        ScheduleRepositoryInterface $scheduleRepository,
        EventValidator $eventValidator,
        ScheduleRecalculationService $recalculationService
    ) {
        $this->eventRepository = $eventRepository;
        $this->loanRepository = $loanRepository;
        $this->scheduleRepository = $scheduleRepository;
        $this->eventValidator = $eventValidator;
        $this->recalculationService = $recalculationService;
    }

    /**
     * Record event and handle all related updates
     *
     * @param int $loanId
     * @param array $eventData
     * @return Event
     * @throws ApiException
     */
    public function recordEvent(int $loanId, array $eventData): Event
    {
        // [1] Validate Event
        $loan = $this->loanRepository->get($loanId);
        if (!$loan) {
            throw ApiException::notFound("Loan not found");
        }

        $validationErrors = $this->eventValidator->validate(
            $eventData,
            $loan
        );
        
        if (!empty($validationErrors)) {
            throw ApiException::validationError(
                "Event validation failed",
                $validationErrors
            );
        }

        // [2] Create Event Record
        $event = $this->eventRepository->record([
            'loan_id' => $loanId,
            'event_type' => $eventData['event_type'],
            'event_date' => $eventData['event_date'],
            'amount' => $eventData['amount'] ?? null,
            'notes' => $eventData['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // [3] Update Loan Status
        $loan->last_event_date = $event->event_date;
        $loan->event_count = ($loan->event_count ?? 0) + 1;
        $this->loanRepository->update($loanId, (array)$loan);

        // [4] Trigger Recalculation (if needed)
        if ($this->shouldRecalculate($eventData['event_type'])) {
            $this->recalculationService->recalculate(
                $loanId,
                $event
            );
        }

        // [5] Propagate Changes
        $this->propagateChanges($loanId, $event);

        return $event;
    }

    /**
     * Determine if recalculation needed
     *
     * @param string $eventType
     * @return bool
     */
    private function shouldRecalculate(string $eventType): bool
    {
        $recalculatableTypes = [
            'extra_payment',
            'skip_payment',
            'rate_change',
            'loan_modification'
        ];
        
        return in_array($eventType, $recalculatableTypes);
    }

    /**
     * Propagate changes to related records
     *
     * @param int $loanId
     * @param Event $event
     * @return void
     */
    private function propagateChanges(int $loanId, Event $event): void
    {
        // Log the event
        // Update dashboard/reports
        // Trigger notifications (if configured)
        // Update event count on loan
    }
}
```

### Event Validator Implementation

```php
<?php
namespace Ksfraser\Amortizations\Services;

use Ksfraser\Amortizations\Models\Loan;

class EventValidator
{
    private const VALID_TYPES = [
        'extra_payment',
        'skip_payment',
        'rate_change',
        'loan_modification',
        'payment_applied',
        'accrual'
    ];

    /**
     * Validate event data
     *
     * @param array $eventData
     * @param Loan $loan
     * @return array Validation errors
     */
    public function validate(array $eventData, Loan $loan): array
    {
        $errors = [];

        // Event type validation
        if (empty($eventData['event_type'])) {
            $errors['event_type'] = 'Event type is required';
        } elseif (!in_array($eventData['event_type'], self::VALID_TYPES)) {
            $errors['event_type'] = 'Invalid event type';
        }

        // Date validation
        if (empty($eventData['event_date'])) {
            $errors['event_date'] = 'Event date is required';
        } elseif (!$this->isValidDate($eventData['event_date'])) {
            $errors['event_date'] = 'Invalid date format';
        } elseif ($eventData['event_date'] < $loan->start_date) {
            $errors['event_date'] = 'Event date cannot be before loan start';
        }

        // Type-specific validation
        $typeErrors = $this->validateByType(
            $eventData['event_type'] ?? '',
            $eventData,
            $loan
        );
        
        $errors = array_merge($errors, $typeErrors);

        return $errors;
    }

    /**
     * Type-specific validation
     *
     * @param string $eventType
     * @param array $eventData
     * @param Loan $loan
     * @return array
     */
    private function validateByType(
        string $eventType,
        array $eventData,
        Loan $loan
    ): array {
        $errors = [];

        match ($eventType) {
            'extra_payment' => $errors = $this->validateExtraPayment(
                $eventData,
                $loan
            ),
            'skip_payment' => $errors = $this->validateSkipPayment(
                $eventData,
                $loan
            ),
            'rate_change' => $errors = $this->validateRateChange(
                $eventData,
                $loan
            ),
            'loan_modification' => $errors = $this->validateLoanModification(
                $eventData,
                $loan
            ),
            default => $errors = []
        };

        return $errors;
    }

    private function validateExtraPayment(
        array $eventData,
        Loan $loan
    ): array {
        $errors = [];

        if (empty($eventData['amount'])) {
            $errors['amount'] = 'Amount is required for extra payment';
        } elseif ($eventData['amount'] <= 0) {
            $errors['amount'] = 'Amount must be positive';
        } elseif ($eventData['amount'] > $loan->current_balance) {
            $errors['amount'] = 'Amount cannot exceed current balance';
        }

        return $errors;
    }

    private function validateSkipPayment(
        array $eventData,
        Loan $loan
    ): array {
        $errors = [];

        if (empty($eventData['months_to_skip'])) {
            $errors['months_to_skip'] = 'Number of months is required';
        } elseif ($eventData['months_to_skip'] <= 0) {
            $errors['months_to_skip'] = 'Must skip at least 1 month';
        } elseif ($eventData['months_to_skip'] > 12) {
            $errors['months_to_skip'] = 'Cannot skip more than 12 months';
        }

        return $errors;
    }

    private function validateRateChange(
        array $eventData,
        Loan $loan
    ): array {
        $errors = [];

        if (!isset($eventData['new_rate'])) {
            $errors['new_rate'] = 'New interest rate is required';
        } elseif ($eventData['new_rate'] < 0 || $eventData['new_rate'] > 1) {
            $errors['new_rate'] = 'Interest rate must be between 0 and 1';
        }

        return $errors;
    }

    private function validateLoanModification(
        array $eventData,
        Loan $loan
    ): array {
        $errors = [];

        if (empty($eventData['adjustment_type'])) {
            $errors['adjustment_type'] = 'Adjustment type is required';
        } elseif (!in_array(
            $eventData['adjustment_type'],
            ['principal', 'term']
        )) {
            $errors['adjustment_type'] = 'Invalid adjustment type';
        }

        if (empty($eventData['value'])) {
            $errors['value'] = 'Adjustment value is required';
        }

        return $errors;
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
```

---

## Integration with Existing Systems

### 1. Event Controller Integration

**Updated EventController::record() Method:**

```php
public function record(int $loanId, array $eventData): ApiResponse
{
    try {
        $event = $this->eventRecordingService->recordEvent(
            $loanId,
            $eventData
        );

        return ApiResponse::success(
            $event->toArray(),
            'Event recorded successfully'
        );
    } catch (ApiException $e) {
        return $e->toResponse();
    } catch (\Exception $e) {
        return ApiResponse::serverError(
            'Failed to record event',
            $e->getMessage()
        );
    }
}
```

### 2. Schedule Recalculation Integration

**Trigger Points:**
- After extra payment event
- After skip payment event
- After rate change event
- After loan modification event

**Recalculation Operations:**

```php
public function recalculate(
    int $loanId,
    Event $event
): void
{
    $loan = $this->loanRepository->get($loanId);
    
    match ($event->event_type) {
        'extra_payment' => $this->recalculateAfterExtraPayment($loan, $event),
        'skip_payment' => $this->recalculateAfterSkipPayment($loan, $event),
        'rate_change' => $this->recalculateAfterRateChange($loan, $event),
        'loan_modification' => $this->recalculateAfterModification($loan, $event),
        default => null
    };
}
```

### 3. Loan Status Updates

**Affected Fields:**
- `last_event_date` - Most recent event timestamp
- `event_count` - Total number of recorded events
- `current_balance` - Updated after extra payment
- `interest_rate` - Updated after rate change
- `term_months` - Updated after skip payment or modification
- `maturity_date` - Recalculated if term changes

---

## Test Strategy

### Unit Tests (EventRecordingService)

```php
// Test event recording flow
testRecordEventUpdatesLoanStatus()
testRecordEventTriggersRecalculation()
testRecordEventPropagatesChanges()

// Test event validation
testValidateEventTypeRequired()
testValidateDateAfterLoanStart()
testValidateAmountForExtraPayment()
testValidateMonthsForSkipPayment()
testValidateRateForRateChange()

// Test error conditions
testRecordEventLoanNotFound()
testRecordEventValidationFails()
testRecordEventRecalculationFails()
```

### Integration Tests (Full Workflow)

```php
// Test complete event recording
testCompleteExtraPaymentWorkflow()
testCompleteSkipPaymentWorkflow()
testCompleteRateChangeWorkflow()

// Test schedule updates
testScheduleRecalculatedAfterExtraPayment()
testScheduleExtendedAfterSkipPayment()
testPaymentsRecalculatedAfterRateChange()

// Test cross-endpoint integration
testEventRecordedAndRetrievable()
testLoanUpdatedAfterEventRecorded()
testScheduleUpdatedAfterEventRecorded()
```

---

## Implementation Checklist

**Step 1: Create Service Classes**
- [ ] EventRecordingService.php (200 lines)
- [ ] EventValidator.php (250 lines)
- [ ] ScheduleRecalculationService.php (300 lines)

**Step 2: Update Repositories**
- [ ] MockEventRepository add event tracking
- [ ] Ensure event-to-loan relationships maintained
- [ ] Add event querying by type, date range

**Step 3: Update Controllers**
- [ ] EventController::record() implementation
- [ ] Error handling and response formatting
- [ ] Integrate with new services

**Step 4: Add Tests**
- [ ] Unit tests for EventRecordingService (10 tests)
- [ ] Unit tests for EventValidator (8 tests)
- [ ] Integration tests for workflows (5 tests)
- [ ] Total: 23 new test cases

**Step 5: Documentation**
- [ ] Service documentation
- [ ] Integration guide
- [ ] Example event payloads
- [ ] API response examples

---

## Example Payloads

### Extra Payment Event

```json
POST /api/v1/loans/1/events
{
  "event_type": "extra_payment",
  "event_date": "2025-02-01",
  "amount": 500,
  "notes": "Bonus payment"
}

Response:
{
  "success": true,
  "message": "Event recorded successfully",
  "data": {
    "id": 1,
    "loan_id": 1,
    "event_type": "extra_payment",
    "event_date": "2025-02-01",
    "amount": 500,
    "notes": "Bonus payment",
    "created_at": "2025-01-15 10:30:00"
  }
}
```

### Skip Payment Event

```json
POST /api/v1/loans/1/events
{
  "event_type": "skip_payment",
  "event_date": "2025-03-01",
  "months_to_skip": 2,
  "notes": "Temporary hardship"
}

Response: {...loan with extended term...}
```

### Rate Change Event

```json
POST /api/v1/loans/1/events
{
  "event_type": "rate_change",
  "event_date": "2025-06-01",
  "new_rate": 0.035,
  "notes": "Refinance approved"
}

Response: {...loan with recalculated payments...}
```

---

## Timeline

| Task | Duration | Status |
|------|----------|--------|
| EventRecordingService.php | 1 hour | Ready |
| EventValidator.php | 0.75 hours | Ready |
| ScheduleRecalculationService.php | 1.25 hours | Ready |
| Test Suite (23 tests) | 1 hour | Ready |
| Documentation | 0.5 hours | Ready |
| **Total** | **4.5 hours** | **Ready** |

Note: Estimated 1.5 hours for Phase 15.3 comprehensive implementation and testing.

---

**Phase 15.3 Status:** READY FOR IMPLEMENTATION ✅  
**Previous Phase:** 15.2 Complete  
**Next Phase:** 15.4 Analysis Endpoints
