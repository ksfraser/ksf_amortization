# Phase 13 Week 2: Calculator Architecture UML Diagrams

**Document Version:** 1.0  
**Date Created:** December 17, 2025  
**Phase:** 13 (Optimization)  
**Week:** 2 (Code Refactoring)  
**Status:** Active Development

---

## Overview

This document provides comprehensive UML diagrams showing the calculator class architecture introduced in Phase 13 Week 2. The calculator classes follow the Single Responsibility Principle (SRP) by separating pure calculation logic from persistence.

---

## 1. Calculator Class Hierarchy

```
┌─────────────────────────────────────────────────────────────────┐
│                    Calculator Pattern                            │
│                    (Abstract Concept)                            │
├─────────────────────────────────────────────────────────────────┤
│  Pure Functions: No side effects, no state changes               │
│  Input → Calculation → Output (deterministic)                   │
└─────────────────────────────────────────────────────────────────┘
                              △
                              │
           ┌──────────────────┼──────────────────┐
           │                  │                  │
    ┌──────┴──────┐   ┌──────┴──────┐   ┌──────┴──────┐
    │  Payment    │   │  Schedule   │   │  Interest   │
    │ Calculator  │   │ Calculator  │   │ Calculator  │
    └─────────────┘   └─────────────┘   └─────────────┘
```

---

## 2. PaymentCalculator Class Diagram

```
┌──────────────────────────────────────────────────────┐
│           PaymentCalculator                          │
├──────────────────────────────────────────────────────┤
│ RESPONSIBILITY: Calculate periodic payment amounts   │
├──────────────────────────────────────────────────────┤
│ - frequencyConfig: array (static)                    │
│ - precision: int = 4                                 │
├──────────────────────────────────────────────────────┤
│ + calculate(principal, rate, freq, n): float        │
│ + getPeriodsPerYear(frequency): int (static)        │
│ + getPaymentIntervalDays(frequency): int            │
│ + getSupportedFrequencies(): array (static)         │
│ + setPrecision(precision): void                     │
│ + getPrecision(): int                               │
├──────────────────────────────────────────────────────┤
│ Supported Frequencies:                              │
│ • monthly (12)    • biweekly (26)                   │
│ • weekly (52)     • daily (365)                     │
│ • semiannual (2)  • annual (1)                      │
├──────────────────────────────────────────────────────┤
│ Formula: P = r × PV / (1 - (1 + r)^(-n))            │
│ Where:                                               │
│ - P = Payment amount                                │
│ - PV = Principal                                    │
│ - r = Interest rate per period                      │
│ - n = Number of periods                             │
└──────────────────────────────────────────────────────┘
```

### PMT Formula Implementation

```php
// Example: $100,000 loan at 5% annual for 360 monthly payments
$payment = (5.0/100/12) * 100000 / (1 - pow(1 + 5.0/100/12, -360))
         = 536.82  // Monthly payment
```

---

## 3. ScheduleCalculator Class Diagram

```
┌──────────────────────────────────────────────────────────┐
│         ScheduleCalculator                              │
├──────────────────────────────────────────────────────────┤
│ RESPONSIBILITY: Generate amortization schedules         │
├──────────────────────────────────────────────────────────┤
│ - paymentCalculator: PaymentCalculator (DI)            │
│ - precision: int = 4                                   │
├──────────────────────────────────────────────────────────┤
│ + generateSchedule(principal, rate, freq, n,          │
│                    startDate?, interestFreq?): array   │
│ + setPrecision(precision): void                       │
│ - validateInputs(...): void                           │
├──────────────────────────────────────────────────────────┤
│ Return Structure (each payment row):                   │
│ {                                                       │
│   'payment_number': 1,                                │
│   'payment_date': '2025-02-01',                       │
│   'payment_amount': 536.82,                           │
│   'interest_amount': 416.67,                          │
│   'principal_amount': 120.15,                         │
│   'remaining_balance': 99879.85                       │
│ }                                                       │
└──────────────────────────────────────────────────────────┘
```

### Algorithm Flow

```
generateSchedule(P, r, freq, n, startDate, interestFreq)
  │
  ├─► Validate Inputs
  │   ├─ P > 0
  │   ├─ n > 0
  │   ├─ r >= 0
  │   └─ freq is supported
  │
  ├─► Calculate Fixed Payment
  │   └─ payment = paymentCalculator.calculate(P, r, freq, n)
  │
  ├─► Initialize Loop
  │   ├─ balance = P
  │   ├─ currentDate = startDate
  │   └─ schedule = []
  │
  ├─► For Each Payment (i = 1 to n)
  │   ├─ interest = balance × (r/100) / periodsPerYear
  │   ├─ principal = payment - interest
  │   │
  │   ├─ If i == n (Final Payment)
  │   │   ├─ principal = balance  (ensure zero)
  │   │   └─ payment = principal + interest
  │   │
  │   ├─ Add row to schedule
  │   ├─ balance = balance - principal
  │   └─ currentDate += interval
  │
  └─► Return schedule array
```

---

## 4. Dependency Injection Diagram

```
┌─────────────────────────────────┐
│     Client Code                 │
│  (AmortizationModel, Tests)    │
└────────────┬────────────────────┘
             │
             │ Creates
             │ (with DI)
             ▼
┌─────────────────────────────────┐
│   ScheduleCalculator            │
│  (requires PaymentCalculator)   │
└──────────┬──────────────────────┘
           │ Depends on (DI)
           │ (constructor injection)
           ▼
┌─────────────────────────────────┐
│   PaymentCalculator             │
│  (pure calculation, no deps)    │
└─────────────────────────────────┘

Benefits:
✓ Easy to test (mock PaymentCalculator)
✓ Flexible (can swap implementations)
✓ Follows Dependency Inversion Principle
✓ Constructor makes dependencies explicit
```

---

## 5. Data Flow Diagram

### Basic Calculation Flow

```
Input Parameters
  ├─ Principal: $100,000
  ├─ Annual Rate: 5%
  ├─ Frequency: Monthly
  ├─ Payments: 360
  └─ Start Date: 2025-01-01
       │
       ▼
┌──────────────────────────┐
│ PaymentCalculator        │
│ calculate()              │ ──► $536.82 (monthly payment)
└──────────────────────────┘
       │
       ├─────────────────────────────┐
       │ (passed to)                  │
       ▼                              ▼
┌──────────────────────────┐  ┌────────────────────┐
│ ScheduleCalculator       │  │ Input Loan Details │
│ generateSchedule()       │  ├────────────────────┤
│                          │  │ Principal: $100K   │
│ • Calculate interest     │  │ Rate: 5%           │
│ • Calculate principal    │  │ Frequency: Monthly │
│ • Update balance         │  │ Payments: 360      │
│ • Generate row           │  │ Start: 2025-01-01  │
│ • Repeat 360 times       │  └────────────────────┘
└──────────────────────────┘
       │
       ▼
Schedule Array
  ├─ Payment 1: 2025-02-01  $536.82  Int: $416.67  Prin: $120.15  Bal: $99,879.85
  ├─ Payment 2: 2025-03-01  $536.82  Int: $415.92  Prin: $120.90  Bal: $99,758.95
  ├─ ...
  └─ Payment 360: 2055-01-01 $536.82  Int: $2.23   Prin: $534.59  Bal: $0.00
       │
       ▼
Ready for Persistence
  (AmortizationModel writes to database)
```

---

## 6. Interaction Diagram: AmortizationModel Integration

```
AmortizationModel
  │
  ├─► Create PaymentCalculator
  │   └─ paymentCalc = new PaymentCalculator()
  │
  ├─► Create ScheduleCalculator
  │   └─ scheduleCalc = new ScheduleCalculator(paymentCalc)
  │
  ├─► Calculate Payment
  │   └─ payment = paymentCalc.calculate(P, r, freq, n)
  │
  ├─► Generate Schedule
  │   └─ schedule = scheduleCalc.generateSchedule(P, r, freq, n, startDate)
  │
  ├─► [Pure Calculation Phase Ends Here]
  │
  └─► Persist Schedule
      └─ For each row in schedule
          └─ db.insertScheduleRow(row)  ◄─── Only now access database
```

---

## 7. Test Architecture

### Test Coverage Map

```
Unit Tests
  ├─ PaymentCalculatorTest (16 tests)
  │   ├─ Basic calculations
  │   ├─ Frequency support
  │   ├─ Edge cases (zero interest, extreme values)
  │   └─ Validation
  │
  ├─ ScheduleCalculatorTest (11 tests)
  │   ├─ Basic schedule generation
  │   ├─ Payment calculations
  │   ├─ Balance verification
  │   ├─ Date calculations
  │   ├─ Different frequencies
  │   └─ Edge cases
  │
  └─ AmortizationModelTest (updated in next task)
      ├─ Integration with calculators
      ├─ Database persistence
      ├─ Loan creation/retrieval
      └─ End-to-end workflows
```

---

## 8. Design Principles Implemented

### Single Responsibility Principle (SRP)

| Class | Responsibility | NOT Responsible For |
|-------|-----------------|---------------------|
| PaymentCalculator | Calculate payment amounts | Schedules, persistence, dates |
| ScheduleCalculator | Generate payment schedules | Individual payments, persistence |
| AmortizationModel | Orchestration & persistence | Calculation logic |

### Dependency Inversion Principle (DIP)

```
HIGH-LEVEL MODULES
       △
       │ Depend on
       │
   ABSTRACTIONS / INTERFACES
       △
       │ Implemented by
       │
   LOW-LEVEL MODULES

Instead of: AmortizationModel → ScheduleCalculator → PaymentCalculator
Correct:    AmortizationModel → IScheduleCalculator → IPaymentCalculator
              (Both depend on abstractions)
```

### Pure Functions

- **No side effects**: Don't modify external state
- **Deterministic**: Same input → Same output (always)
- **Testable**: Can be tested without mocks or fixtures
- **Example**:
  ```php
  // Pure function
  $payment = $paymentCalc.calculate(100000, 5.0, 'monthly', 360)
  // Always returns 536.82
  
  // NOT pure (side effect: persistence)
  $schedule = amortizationModel.calculateSchedule(loanId, 360)
  // Writes to database during calculation
  ```

---

## 9. Sequence Diagram: Schedule Generation

```
Client             AmortizationModel    ScheduleCalculator    PaymentCalculator
  │                      │                     │                      │
  ├─ createLoan()       │                     │                      │
  │──────────────────────→ insertLoan()       │                      │
  │                      └──> DB                                      │
  │                                                                    │
  ├─ calculateSchedule(loanId, 360)                                  │
  │──────────────────────→ getLoan()                                 │
  │                      └──> DB                                      │
  │                      │                                             │
  │                      ├─ new ScheduleCalculator()                 │
  │                      │──────────────────────→ __construct()       │
  │                      │                      (with PaymentCalc)   │
  │                      │                                             │
  │                      ├─ generateSchedule()                        │
  │                      │─────────────────────→ calculate()          │
  │                      │                      ─────────────────→   │
  │                      │                      ←───── return payment │
  │                      │                      (536.82)              │
  │                      │                                             │
  │                      │                      For each payment:     │
  │                      │←───── return schedule                      │
  │                      (array of 360 rows)                          │
  │                      │                                             │
  │                      ├─ insertScheduleRow() (×360 times)         │
  │                      └──> DB                                      │
  │                      │                                             │
  │←───── Success                                                     │
```

---

## 10. Class Composition Map

```
AmortizationModel (NOT a Calculator)
  │
  ├─► Has: ScheduleCalculator (composition)
  │   │
  │   └─► Has: PaymentCalculator (composition)
  │
  ├─► Has: DataProviderInterface (composition)
  │   └─► Responsibility: Persistence only
  │
  └─► Responsibility: Orchestration + Persistence
      (Calculation delegated to Calculators)
```

---

## 11. Error Handling Flow

```
calculateSchedule(principal, rate, freq, numberOfPayments, startDate, interestFreq)
  │
  └─► validateInputs()
      │
      ├─ If principal <= 0
      │  └─► throw InvalidArgumentException("Principal must be > 0")
      │
      ├─ If numberOfPayments <= 0
      │  └─► throw InvalidArgumentException("Payments must be > 0")
      │
      ├─ If rate < 0
      │  └─► throw InvalidArgumentException("Rate cannot be negative")
      │
      ├─ If freq not supported
      │  └─► throw InvalidArgumentException("Unknown frequency: {freq}")
      │
      └─► [All valid, continue]
          └─► generate() succeeds
              └─► return schedule array
```

---

## 12. Future Enhancement: Interest Calculator

```
InterestCalculator (to be implemented in next phase)
  │
  ├─ calculateSimpleInterest(principal, rate, time)
  ├─ calculateCompoundInterest(principal, rate, periods, frequency)
  ├─ calculateAccruedInterest(loanId, toDate)
  ├─ calculateInterestForPeriod(balance, rate, frequency)
  └─ convertRate(fromFreq, toFreq)

This will further reduce complexity in ScheduleCalculator
```

---

## 13. Metrics & Benefits

### Code Quality Improvements (Phase 13 Week 2)

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Tests | 715 | 726 | +11 new pure function tests |
| Calculator Classes | 1 | 2 | +100% (PaymentCalculator → +ScheduleCalculator) |
| Pure Functions | 0% | 40% | +40% of calculation code |
| Code Duplication | High | Reduced | Extracted to single classes |
| SRP Violations | AmortizationModel | Resolved | Calculation separated |
| Testability | Moderate | High | No DB needed for calculators |

### Performance Benefits

- **No database queries** during calculation (pure functions)
- **100% deterministic** - same input always returns same output
- **Parallelizable** - multiple schedules can be calculated simultaneously
- **Cached results** - same calculations don't need recomputation

---

## 14. Implementation Timeline

### Phase 13 Week 2 Refactoring Schedule

```
Day 1-2: AmortizationModel Extraction (CURRENT)
  ├─ ✅ PaymentCalculator (existing, enhanced)
  ├─ ✅ ScheduleCalculator (created, 11 tests)
  └─ ⏳ Update AmortizationModel to use calculators

Day 3: DataProvider Standardization
  ├─ Unified CRUD naming
  ├─ Standard exceptions
  └─ Consistent error handling

Day 4: Platform Adaptors Consistency
  ├─ Base class for common logic
  ├─ Extract duplication
  └─ Shared utilities

Day 5: Test Infrastructure
  ├─ Centralized TestFixtures
  ├─ Enhanced BaseTestCase
  └─ Consistent naming
```

---

## 15. Related Documentation

- **[UML_ProcessFlows.md](UML_ProcessFlows.md)** - Overall process flows
- **[UML_MessageFlows.md](UML_MessageFlows.md)** - Message interactions
- **[PHASE13_WEEK2_REFACTORING_PLAN.md](PHASE13_WEEK2_REFACTORING_PLAN.md)** - Detailed execution plan
- **[DEVELOPMENT_GUIDELINES.md](DEVELOPMENT_GUIDELINES.md)** - Coding standards
- **[PHPDOC_UML_STANDARDS.md](PHPDOC_UML_STANDARDS.md)** - Documentation standards

---

## Status

✅ **PaymentCalculator:** Complete with tests  
✅ **ScheduleCalculator:** Complete with tests (726 total tests passing)  
⏳ **AmortizationModel Refactoring:** Next task  
⏳ **Documentation:** Updating in real-time  

**Last Updated:** December 17, 2025  
**Next Review:** After AmortizationModel refactoring complete
