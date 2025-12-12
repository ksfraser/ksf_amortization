# Next Steps: Roadmap for Phase 2 Completion

## Current Status
- **Tests**: 71 total (54 passing, 17 failing)
- **Pass rate**: 76%
- **Latest feature**: GracePeriodHandler (100% passing)
- **Main blockers**: Variable rate balance calculations

## Immediate Actions (Next 30 minutes)

### 1. Debug Variable Rate Strategy
**File**: `src/Ksfraser/Amortizations/Strategies/VariableRateStrategy.php`

**Issue**: Final balance not equal to zero (off by 3-46 basis points)
- Tests failing: 4 unit + 2 integration = 6 total
- Root cause: Rate transitions affecting balance accumulation
- Action: Review balance calculation after rate changes

**Steps**:
1. Run variable rate test in isolation
2. Print schedule array for debugging
3. Check rate transition logic
4. Verify balance accumulation math

```bash
vendor\bin\phpunit tests/Unit/Strategies/VariableRateStrategyTest.php
```

### 2. Fix Partial Payment Logic
**File**: `src/Ksfraser/Amortizations/EventHandlers/PartialPaymentEventHandler.php`

**Issue**: Payment priority application not working correctly
- Tests failing: 1 error + 1 failure = 2 total
- Root cause: Payment allocation between principal/interest/penalties
- Action: Review priority-based payment application

**Steps**:
1. Examine payment priority order
2. Verify interest vs principal allocation
3. Check penalty calculation

### 3. Verify BalloonPayment Integration Tests
**File**: `tests/Integration/BalloonPaymentIntegrationTest.php`

**Issue**: Cumulative floating point errors in multi-year schedules
- Tests failing: 3 total
- Root cause: Rounding across 60+ periods
- Action: May require adjusting tolerance or using BigDecimal

## TDD Features Queue (After Stabilization)

### Phase 3 Feature #2: SkipPaymentHandler
**Priority**: High | **Complexity**: Medium | **Tests**: 11

```php
class SkipPaymentHandler implements LoanEventHandler {
    // Allow borrowers to skip one or more regular payments
    // Apply penalties (typically 2-5% of payment)
    // Extend loan term
    // Update schedule
}
```

**Test cases needed**:
1. Skip single payment
2. Skip multiple payments
3. Penalty calculation
4. Term extension
5. Schedule reconstruction
6. Error handling (too many skips)
7. Priority ordering
8. Metadata structure
9. Date calculations
10. Validation rules
11. Edge cases (balloon loans)

### Phase 3 Feature #3: ExtraPaymentHandler
**Priority**: High | **Complexity**: Medium | **Tests**: 12

```php
class ExtraPaymentHandler implements LoanEventHandler {
    // Apply extra payments to principal
    // Option to shorten term or reduce payment
    // Recalculate schedule
    // Track interest savings
}
```

**Test cases needed**:
1. Extra payment applied
2. Term reduction
3. Payment reduction
4. Interest savings calculation
5. Multiple extra payments
6. Extra payment > current payment
7. Extra payment near end of loan
8. Validation
9. Metadata structure
10. Schedule reconstruction
11. Balloon loan handling
12. Edge cases

### Phase 3 Feature #4: PaymentHistoryTracker
**Priority**: Medium | **Complexity**: Low | **Tests**: 9

Tracks all payment events and their outcomes.

### Phase 3 Feature #5: DelinquencyClassifier
**Priority**: Medium | **Complexity**: Medium | **Tests**: 8

Classifies loan risk based on payment history.

## Testing Best Practices

### Before Writing Tests
1. Define business rules clearly
2. Identify happy paths and edge cases
3. Determine validation rules
4. Document expected behavior

### Test Structure Template
```php
public function testFeatureName(): void {
    // ARRANGE: Set up test data
    $loan = new Loan();
    $loan->setPrincipal(50000);
    
    // ACT: Execute the feature
    $result = $handler->handle($loan, $event);
    
    // ASSERT: Verify expected behavior
    $this->assertEquals($expected, $result);
}
```

### Running Tests During Development
```bash
# Run specific test file
vendor\bin\phpunit tests/Unit/EventHandlers/NewHandlerTest.php

# Run specific test method
vendor\bin\phpunit tests/Unit/EventHandlers/NewHandlerTest.php --filter testMethodName

# Run with verbose output
vendor\bin\phpunit --verbose tests/Unit/EventHandlers/NewHandlerTest.php

# Run and watch for failures
vendor\bin\phpunit --testdox tests/Unit/EventHandlers/NewHandlerTest.php
```

## Code Organization

### Event Handler Pattern
All handlers should follow this structure:

```
1. Namespace declaration
2. Use statements
3. Class documentation (phpdoc)
4. Class declaration + interface implementation
5. Constants (priority, event types)
6. Interface methods (supports, handle, getPriority)
7. Domain-specific methods (applyXxx)
8. Helper methods (validate, calculate)
```

### Test Organization
```
1. Namespace declaration
2. Use statements
3. Class documentation
4. Class declaration extends TestCase
5. setUp method
6. Test methods (alphabetically)
   - Happy path tests first
   - Edge cases next
   - Error cases last
7. Helper methods
```

## Performance Targets

- Single test execution: < 100ms
- Full test suite (71 tests): < 2 seconds
- Integration test: < 500ms
- Unit test average: < 50ms

## Quality Checkpoints

Before declaring a feature complete:
- [ ] All tests passing (100%)
- [ ] No linting errors
- [ ] Minimum 90% code coverage
- [ ] Documentation complete
- [ ] Examples provided
- [ ] Edge cases handled
- [ ] Error messages clear

## Integration Testing

After implementing skip/extra payment handlers:

```bash
# Run complete event handler integration
vendor\bin\phpunit tests/Integration/

# Test all handlers together
vendor\bin\phpunit tests/Integration/*IntegrationTest.php

# Full test suite
vendor\bin\phpunit
```

## Documentation Requirements

For each new feature:
1. PHPDoc class documentation
2. Method documentation
3. Usage examples
4. Integration guide
5. Test coverage details
6. Performance notes

## Deployment Checklist

Before release:
- [ ] 100% tests passing
- [ ] No skipped tests
- [ ] Code review completed
- [ ] Documentation updated
- [ ] Version bumped
- [ ] Changelog entry
- [ ] Integration verified

## Estimated Timeline

| Task | Duration | Status |
|------|----------|--------|
| Fix variable rate | 45 min | TO DO |
| Fix partial payment | 30 min | TO DO |
| Stabilize integration | 15 min | TO DO |
| Implement SkipPaymentHandler | 90 min | TO DO |
| Implement ExtraPaymentHandler | 90 min | TO DO |
| Integration testing | 30 min | TO DO |
| Database layer design | 60 min | TO DO |

**Total estimated time**: ~5 hours to complete Phase 2

## Success Criteria

Phase 2 complete when:
- ✅ All 71+ tests passing
- ✅ 5+ event handlers implemented
- ✅ >90% code coverage
- ✅ Full documentation
- ✅ Ready for database implementation

Phase 3 (Database) begins when above criteria met.

## References

- GracePeriodHandler guide: `GRACEPERIODHANDLER_IMPLEMENTATION_GUIDE.md`
- Current test status: `SESSION_COMPLETION_SUMMARY.md`
- Test stabilization: `PHASE2_TEST_STABILIZATION.md`
- Architecture: `Architecture.md`
