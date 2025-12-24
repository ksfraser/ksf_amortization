# GracePeriodHandler - TDD Feature Implementation Guide

## Overview
The GracePeriodHandler demonstrates a complete TDD implementation of a loan event handler. This can be used as a template for implementing subsequent event handlers.

## File Structure

```
src/Ksfraser/Amortizations/EventHandlers/GracePeriodHandler.php
tests/Unit/EventHandlers/GracePeriodHandlerTest.php
```

## Implementation Template

### Step 1: Define Test Cases (RED Phase)
Write tests that describe the desired behavior. The tests should cover:
- Normal operation (grace period extends term, accrues interest)
- Edge cases (zero interest, large grace periods)
- Error cases (negative/zero months)
- Integration points (implements interface correctly)
- Metadata structure (event data completeness)

### Step 2: Implement Handler Class (GREEN Phase)
Create the handler class that:
- Implements the `LoanEventHandler` interface
- Provides all required methods: `handle()`, `supports()`, `getPriority()`
- Includes domain logic methods (e.g., `applyGracePeriod()`)
- Handles validation and error cases
- Returns structured metadata

### Step 3: Refactor and Enhance (REFACTOR Phase)
- Add comprehensive documentation
- Improve code clarity
- Add test helper methods
- Ensure DRY principles

## Key Implementation Details

### Interface Compliance
```php
class GracePeriodHandler implements LoanEventHandler {
    public function supports(LoanEvent $event): bool
    public function handle(Loan $loan, LoanEvent $event): Loan
    public function getPriority(): int
}
```

### Priority System
Handlers execute in priority order (lower = earlier):
- 100: Arrears handling (must be first)
- 90: Penalties
- 80: Rate changes
- 10: Grace periods (early execution)
- Default: 50

### Interest Calculation Pattern
```php
$monthlyRate = $annualRate / 12;
$accruedInterest = 0.0;
for ($month = 0; $month < $gracePeriodMonths; $month++) {
    $accruedInterest += round($principal * $monthlyRate, 2);
}
```

## Test Template Usage

To implement a new event handler following this pattern:

1. **Copy test file**: `GracePeriodHandlerTest.php` → `NewEventHandlerTest.php`
2. **Update test class name and namespaces**
3. **Modify test methods** to reflect new event type
4. **Update assertions** for new business logic
5. **Copy handler skeleton**: `GracePeriodHandler.php` → `NewEventHandler.php`
6. **Implement interface methods** for new logic
7. **Add domain-specific methods** (e.g., `applySkipPayment()`)

## Running Tests

### Run all Grace Period tests:
```bash
vendor\bin\phpunit tests/Unit/EventHandlers/GracePeriodHandlerTest.php
```

### Run with coverage:
```bash
vendor\bin\phpunit --coverage-html=coverage tests/Unit/EventHandlers/GracePeriodHandlerTest.php
```

### Run specific test:
```bash
vendor\bin\phpunit tests/Unit/EventHandlers/GracePeriodHandlerTest.php --filter testGracePeriodExtendsLoanTerm
```

## Test Checklist

When implementing a new event handler, ensure tests cover:
- [ ] Event type support check
- [ ] Event type rejection
- [ ] Normal operation scenario
- [ ] All required properties/fields
- [ ] Edge cases (zero values, min/max bounds)
- [ ] Invalid input validation
- [ ] Interface method requirements
- [ ] Priority value correctness
- [ ] Metadata structure completeness
- [ ] Date/time calculations
- [ ] Rounding precision

## Expected Test Results

```
GracePeriodHandlerTest:
✅ testSupportsGracePeriodEvents
✅ testRejectsOtherEventTypes
✅ testGracePeriodExtendsLoanTerm
✅ testGracePeriodAccruesInterest
✅ testGracePeriodWithZeroInterest
✅ testRejectsNegativeGracePeriod
✅ testRejectsZeroGracePeriod
✅ testHandlerHasCorrectPriority
✅ testGracePeriodMetadata
✅ testLargeGracePeriod
✅ testGracePeriodEndDate

Result: 11/11 passing (100%)
```

## Integration with Event Dispatcher

When multiple handlers need to process the same event:

```php
$handlers = [
    new GracePeriodHandler(),      // Priority: 10 (early)
    new RateChangeHandler(),       // Priority: 80
    new PartialPaymentHandler(),   // Priority: 60
];

// Sort by priority (descending)
usort($handlers, fn($a, $b) => $b->getPriority() <=> $a->getPriority());

// Execute in priority order
foreach ($handlers as $handler) {
    if ($handler->supports($event)) {
        $loan = $handler->handle($loan, $event);
    }
}
```

## Extending the Handler

To add new functionality:

1. Add test case first (TDD approach)
2. Implement the feature in the handler
3. Update documentation
4. Verify all tests still pass

Example: Add grace period with variable interest
```php
public function applyVariableGracePeriod(Loan $loan, array $rates): array
{
    // rates = [month => rate] mapping
    // Implement variable rate logic
    // Return updated metadata
}
```

## Performance Considerations

- Interest calculation: O(n) where n = grace period months
- Date arithmetic: O(1)
- Metadata creation: O(1)
- Total complexity: O(n)

For typical grace periods (3-24 months), performance is negligible.

## References

- `LoanEventHandler` interface: `src/Ksfraser/Amortizations/EventHandlers/LoanEventHandler.php`
- Other event handlers: `src/Ksfraser/Amortizations/EventHandlers/`
- Test base class: `tests/Unit/EventHandlers/GracePeriodHandlerTest.php`

## Next Implementation: SkipPaymentHandler

Following the same TDD pattern, the next handler should:
1. Allow borrowers to skip one or more regular payments
2. Apply penalties to skipped amounts
3. Extend loan term by skipped periods
4. Update payment schedule accordingly

Estimated tests: 11-15
Estimated lines of code: 150-200
