# PhpDoc & UML Documentation Standards

**Version:** 1.0.0  
**Status:** Template & Guidelines for All Development  
**Author:** KSF Development Team  
**Date:** December 8, 2025

---

## Table of Contents

1. [PhpDoc Standards](#phpdoc-standards)
2. [UML Documentation](#uml-documentation)
3. [Design Patterns & Principles](#design-patterns--principles)
4. [Code Examples](#code-examples)
5. [Checklist for Code Review](#checklist-for-code-review)

---

## PhpDoc Standards

### Overview

Every public method, class, and interface must include comprehensive PHPDoc blocks that:
- Describe purpose and behavior
- Document parameters and return types
- Show design patterns used
- Provide usage examples
- Reference related methods/classes

### Class Documentation Template

```php
<?php
/**
 * ClassName - Brief one-line description
 *
 * Longer description explaining the purpose, responsibility, and key concepts.
 * Include any important behavioral details, constraints, or assumptions.
 *
 * ### Responsibility (SRP)
 * This class is responsible for ONE specific thing:
 * - Example: Managing amortization calculations
 * - NOT responsible for: Posting to GL, UI rendering, etc.
 *
 * ### Dependencies (DIP)
 * Depends on interfaces, not concrete implementations:
 * - Depends on: DataProviderInterface
 * - NOT on: MySQLDataProvider, FADataProvider
 *
 * ### Design Patterns
 * - Strategy Pattern: SelectProviders allow pluggable frequency strategies
 * - Repository Pattern: DataProvider abstracts data access
 * - Dependency Injection: Dependencies provided via constructor
 *
 * ### UML Class Diagram
 * ```
 * ┌─────────────────────────────────────┐
 * │      ClassName                      │
 * ├─────────────────────────────────────┤
 * │ - property1: type                   │
 * │ - property2: type (description)     │
 * ├─────────────────────────────────────┤
 * │ + publicMethod(param): returnType   │
 * │ - privateMethod(): void             │
 * ├─────────────────────────────────────┤
 * │ Implements: InterfaceName           │
 * │ Extends: ParentClass                │
 * └─────────────────────────────────────┘
 *      │ Depends On
 *      ▼
 *   InterfaceName
 * ```
 *
 * ### Example Usage
 * ```php
 * $provider = new FADataProvider($connection);
 * $model = new AmortizationModel($provider, $eventProvider);
 * 
 * $schedule = $model->calculateSchedule(
 *     loanSummary: $loan,
 *     numberOfPayments: 360
 * );
 * ```
 *
 * @package   Ksfraser\Amortizations
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-08
 * @see       DataProviderInterface
 * @see       LoanEventProviderInterface
 */

namespace Ksfraser\Amortizations;

class ClassName
{
    // Implementation...
}
```

### Method Documentation Template

```php
/**
 * Concise method name and purpose in present tense verb
 *
 * ### Purpose
 * Clear explanation of what this method does and why
 *
 * ### Algorithm
 * High-level description of the algorithm used:
 * 1. Step one
 * 2. Step two with sub-steps:
 *    a. Sub-step A
 *    b. Sub-step B
 * 3. Return result
 *
 * ### Parameters
 * - `$param1`: What it represents, valid range/format
 * - `$param2`: Optional parameter description
 *
 * ### Returns
 * - Type and what it represents
 * - Null when/if applicable
 * - Exceptions thrown and why
 *
 * ### Example
 * ```php
 * $payment = $model->calculatePayment(
 *     principal: 10000.00,
 *     annualRate: 5.0,
 *     paymentFrequency: 'monthly',
 *     numberOfPayments: 360
 * );
 * // Returns: 53.68 (approximately)
 * ```
 *
 * ### Precision & Rounding
 * - Uses banker's rounding (round to nearest even)
 * - Maintains 4 decimal places internally
 * - Returns 2 decimal places (cents)
 *
 * ### Performance
 * - Time Complexity: O(1)
 * - Space Complexity: O(1)
 * - Typical execution: <1ms
 *
 * @param type $paramName Parameter description
 * @param type $optionalParam Optional parameter (default value)
 *
 * @return type Description of return value
 * @throws ExceptionType When/why this exception is thrown
 *
 * @see relatedMethod() Related method name
 * @since 1.0.0
 */
public function methodName($paramName, $optionalParam = 'default'): type
{
    // Implementation...
}
```

### Special Documentation Tags

```php
/**
 * METHOD DOCUMENTATION
 *
 * @param type $name Description
 * @return type Description
 * @throws ExceptionType Description
 * @deprecated 1.5.0 Use newMethod() instead
 * @since 1.0.0
 * @author Developer Name
 * @see RelatedClass::method()
 * @link https://en.wikipedia.org/wiki/Amortization
 *
 * SECTIONS FOR COMPLEX METHODS
 * ### Purpose - Why does this exist?
 * ### Algorithm - How does it work?
 * ### Edge Cases - Special handling
 * ### Performance - Time/space complexity
 * ### Example - How to use it
 * ### Precision - Rounding, accuracy concerns
 * ### Thread Safety - Is it thread-safe?
 * ### Notes - Additional context
 */
```

---

## UML Documentation

### ASCII UML Diagrams in PhpDoc

#### 1. Class Diagram

```php
/**
 * ### UML Class Diagram
 * ```
 * ┌─────────────────────────────────────┐
 * │    AmortizationModel                │ (Class name, italicized=abstract)
 * ├─────────────────────────────────────┤
 * │ - dataProvider: DataProviderIF      │ (Properties: visibility type name)
 * │ - eventProvider: LoanEventProvider  │
 * │ # calculationPrecision: int         │ (- private, # protected, + public)
 * ├─────────────────────────────────────┤
 * │ + calculatePayment(...): float      │ (Methods: visibility name(params): return)
 * │ + calculateSchedule(...): array     │
 * │ - getInterestRate(...): float       │
 * ├─────────────────────────────────────┤
 * │ Implements: AmortizationService     │ (Interfaces implemented)
 * │ Extends: BaseCalculator             │ (Parent class)
 * └─────────────────────────────────────┘
 * ```
 */
```

#### 2. Dependency Diagram

```php
/**
 * ### Dependency Injection Pattern
 * ```
 * AmortizationModel (Injectable)
 *    │
 *    ├─ Depends on: DataProviderInterface (Injected)
 *    │    │
 *    │    ├─ Implementation: FADataProvider
 *    │    ├─ Implementation: WPDataProvider
 *    │    └─ Implementation: MockDataProvider (Testing)
 *    │
 *    └─ Depends on: LoanEventProviderInterface (Injected)
 *         │
 *         ├─ Implementation: GenericLoanEventProvider
 *         └─ Implementation: MockLoanEventProvider (Testing)
 *
 * ### Benefit
 * Tests can inject MockDataProvider without touching real database
 * New platforms can implement interfaces without changing calculation code
 * ```
 */
```

#### 3. Sequence Diagram (Simplified)

```php
/**
 * ### Sequence: Calculate Schedule with Extra Payment
 * ```
 * Test                AmortModel          DataProvider         LoanEventProvider
 *  │                      │                    │                      │
 *  ├─ createLoan()       │                    │                      │
 *  │                      │                    │                      │
 *  ├─ calculateSchedule()─>│                   │                      │
 *  │                      ├─ getLoanFreq()──> │                      │
 *  │                      │<─ frequency ──────┤                      │
 *  │                      ├─ getEvents()─────────────────────────> │
 *  │                      │<────── events ─────────────────────────┤
 *  │                      ├─ insertSchedule()-> │                   │
 *  │                      │<─ schedule_id ─────┤                    │
 *  │                      │                    │                    │
 *  │<─ schedule ─────────┤                    │                    │
 *  │                      │                    │                    │
 * ```
 */
```

#### 4. State Diagram

```php
/**
 * ### State Machine: Payment Processing
 * ```
 *     ┌───────────────┐
 *     │    Created    │ (Initial state)
 *     └───────┬───────┘
 *             │ calculateSchedule()
 *             ▼
 *     ┌───────────────┐
 *     │  Calculated   │ (Payment amounts computed)
 *     └───────┬───────┘
 *             │ recordExtraPayment()
 *             ▼
 *     ┌───────────────┐
 *     │ Recalculating │ (Extra payment needs recalc)
 *     └───────┬───────┘
 *             │ updateSchedule()
 *             ▼
 *     ┌───────────────┐
 *     │    Updated    │ (Ready to post)
 *     └───────┬───────┘
 *             │ postToGL()
 *             ▼
 *     ┌───────────────┐
 *     │    Posted     │ (Final state)
 *     └───────────────┘
 * ```
 */
```

### Recommended UML Elements

| Element | Notation | Meaning |
|---------|----------|---------|
| Class | `┌─────┐` | Regular class |
| Interface | `<<interface>>` | Contract/interface |
| Abstract | `*ClassName*` | Abstract class |
| Property | `- name: type` | Private property |
| Property | `# name: type` | Protected property |
| Property | `+ name: type` | Public property |
| Method | `+ method(): type` | Public method |
| Method | `- method(): type` | Private method |
| Dependency | `───>` | Uses/depends on |
| Implementation | `─ ─ ─>` | Implements interface |
| Inheritance | `───▲` | Extends class |
| Multiplicity | `*` | Multiple instances |
| Multiplicity | `0..1` | Optional |
| Multiplicity | `1..*` | One or more |

---

## Design Patterns & Principles

### SOLID Principles Documentation

Every class should document which SOLID principles it follows:

```php
/**
 * MyClass - Demonstrates SOLID principles
 *
 * ### SOLID Principles
 * 
 * **Single Responsibility (SRP)**
 * - This class has ONE reason to change: when loan calculations change
 * - NOT responsible for: database access, GL posting, UI rendering
 * - Changes to other concerns don't affect this class
 *
 * **Open/Closed Principle (OCP)**
 * - Open for extension: Can extend with new frequency strategies
 * - Closed for modification: Don't need to modify calculation logic
 * - Uses Strategy pattern via SelectorProvider
 *
 * **Liskov Substitution (LSP)**
 * - Any DataProviderInterface implementation can be substituted
 * - Tests use MockDataProvider in place of FADataProvider
 * - Behavior is predictable regardless of implementation
 *
 * **Interface Segregation (ISP)**
 * - DataProviderInterface has minimal methods (~8)
 * - Clients don't depend on methods they don't use
 * - Related methods grouped by concern
 *
 * **Dependency Inversion (DIP)**
 * - Depends on DataProviderInterface (abstraction)
 * - NOT on FADataProvider or WPDataProvider (concrete)
 * - High-level calculation logic independent of platforms
 *
 */
```

### Design Patterns Documentation

```php
/**
 * ### Design Patterns Used
 *
 * **Repository Pattern**
 * - Purpose: Abstract data access layer
 * - Implementation: DataProviderInterface
 * - Benefit: Can swap implementations without changing business logic
 *
 * **Strategy Pattern**
 * - Purpose: Encapsulate frequency calculation strategies
 * - Implementation: SelectorProvider with selectable frequencies
 * - Benefit: Add new frequencies without changing core calculation
 *
 * **Factory Pattern**
 * - Purpose: Create objects without specifying exact classes
 * - Implementation: DataProviderFactory creates correct provider
 * - Benefit: Centralized object creation logic
 *
 * **Dependency Injection Pattern**
 * - Purpose: Provide dependencies to objects
 * - Implementation: Constructor injection of DataProvider, EventProvider
 * - Benefit: Easy to test, swap implementations, manage dependencies
 *
 * **Decorator Pattern**
 * - Purpose: Add functionality without modifying existing code
 * - Implementation: LoggingDataProvider wraps real provider
 * - Benefit: Cross-cutting concerns (logging) separated
 *
 */
```

---

## Code Examples

### Example 1: Complete Class with All Documentation

```php
<?php
/**
 * AmortizationModel - Core amortization calculation engine
 *
 * Manages loan amortization schedule calculation with support for:
 * - Flexible payment frequencies (monthly, bi-weekly, weekly, daily)
 * - Flexible interest calculation frequencies
 * - Extra payment handling with automatic recalculation
 * - Event-based updates (skip payments, extra payments)
 *
 * ### Responsibility (SRP)
 * Single Responsibility: Amortization calculations only
 * - Calculates payment amounts
 * - Generates payment schedules
 * - Handles extra payment recalculation
 * NOT responsible for:
 * - Data persistence (delegated to DataProvider)
 * - GL posting (delegated to JournalService)
 * - User interface (delegated to Controllers)
 *
 * ### Dependencies (DIP)
 * Depends on interfaces, not concrete implementations:
 * ```
 * AmortizationModel
 *    ├─ depends on: DataProviderInterface
 *    │   ├─ can be: FADataProvider
 *    │   ├─ can be: WPDataProvider
 *    │   └─ can be: MockDataProvider (testing)
 *    │
 *    └─ depends on: LoanEventProviderInterface
 *        ├─ can be: GenericLoanEventProvider
 *        └─ can be: MockLoanEventProvider (testing)
 * ```
 *
 * ### Design Patterns
 * - **Repository Pattern:** DataProvider abstracts persistence
 * - **Strategy Pattern:** Frequency selectors provide calculation strategies
 * - **Dependency Injection:** All dependencies provided via constructor
 *
 * ### UML Class Diagram
 * ```
 * ┌──────────────────────────────────────────┐
 * │      AmortizationModel                   │
 * ├──────────────────────────────────────────┤
 * │ - dataProvider: DataProviderInterface    │
 * │ - eventProvider: LoanEventProviderIF     │
 * │ - selectorProvider: SelectorProvider     │
 * │ - calculationPrecision: int = 4          │
 * │ - roundingMethod: string = 'banker'      │
 * ├──────────────────────────────────────────┤
 * │ + __construct(DP, EP, SP): void          │
 * │ + calculatePayment(...): float           │
 * │ + calculateSchedule(...): array          │
 * │ + recordExtraPayment(...): void          │
 * │ + recalculateSchedule(...): void         │
 * │ - calculateCompoundInterest(...): float  │
 * │ - getPaymentIntervalDays(...): int       │
 * └──────────────────────────────────────────┘
 *      │ Implements: AmortizationService
 *      │ Uses: DataProviderInterface
 *      │ Uses: LoanEventProviderInterface
 *      │ Uses: SelectorProvider
 * ```
 *
 * ### Example Usage
 * ```php
 * // Create model with injected dependencies
 * $dataProvider = new FADataProvider($pdo);
 * $eventProvider = new GenericLoanEventProvider($pdo);
 * $selectorProvider = new SelectorProvider($pdo);
 *
 * $model = new AmortizationModel(
 *     $dataProvider,
 *     $eventProvider,
 *     $selectorProvider
 * );
 *
 * // Calculate a payment
 * $payment = $model->calculatePayment(
 *     principal: 10000.00,
 *     annualRate: 5.0,
 *     paymentFrequency: 'monthly',
 *     numberOfPayments: 360
 * );
 * // Returns: 53.68
 *
 * // Calculate full schedule
 * $schedule = $model->calculateSchedule(
 *     loanSummary: $loan,
 *     numberOfPayments: 360
 * );
 *
 * // Handle extra payment
 * $model->recordExtraPayment(
 *     loanId: 42,
 *     amount: 500.00,
 *     reason: 'Extra payment from customer'
 * );
 * ```
 *
 * ### Performance Characteristics
 * - `calculatePayment()`: O(1), <1ms
 * - `calculateSchedule()`: O(n) where n=payments, ~100ms for 360 payments
 * - `recordExtraPayment()`: O(n), ~150ms for recalculation of 360 payments
 *
 * ### Precision & Rounding
 * - Stores 4 decimal places internally
 * - Rounds to 2 decimal places (cents) for output
 * - Uses banker's rounding (round to nearest even)
 * - Final payment adjusted to ensure balance = $0.00
 *
 * ### Thread Safety
 * Not thread-safe. Create separate instance per thread if needed.
 *
 * @package   Ksfraser\Amortizations
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-08
 * @see       DataProviderInterface
 * @see       LoanEventProviderInterface
 * @see       SelectorProvider
 * @link      https://en.wikipedia.org/wiki/Amortization_schedule
 */

namespace Ksfraser\Amortizations;

use Ksfraser\Amortizations\DataProviderInterface;
use Ksfraser\Amortizations\LoanEventProviderInterface;
use Ksfraser\Amortizations\SelectorProvider;
use DateTime;

final class AmortizationModel implements AmortizationService
{
    /**
     * @var DataProviderInterface Platform-specific data access
     */
    private DataProviderInterface $dataProvider;

    /**
     * @var LoanEventProviderInterface Event management
     */
    private LoanEventProviderInterface $eventProvider;

    /**
     * @var SelectorProvider Configurable frequency/type selectors
     */
    private SelectorProvider $selectorProvider;

    /**
     * @var int Decimal precision for internal calculations
     */
    private int $calculationPrecision = 4;

    /**
     * Constructor with dependency injection
     *
     * ### Dependency Injection Benefits
     * - All dependencies injected, not created internally
     * - Easy to test with mock implementations
     * - Easy to add new platform implementations
     * - Follows Dependency Inversion Principle
     *
     * @param DataProviderInterface $dataProvider Data access layer
     * @param LoanEventProviderInterface $eventProvider Event handling
     * @param SelectorProvider $selectorProvider Configuration selectors
     *
     * @throws InvalidArgumentException If any dependency is null
     */
    public function __construct(
        DataProviderInterface $dataProvider,
        LoanEventProviderInterface $eventProvider,
        SelectorProvider $selectorProvider
    ) {
        $this->dataProvider = $dataProvider;
        $this->eventProvider = $eventProvider;
        $this->selectorProvider = $selectorProvider;
    }

    /**
     * Calculate payment amount for a loan
     *
     * ### Purpose
     * Calculates the periodic payment for a loan using the standard
     * amortization formula, supporting multiple payment frequencies.
     *
     * ### Algorithm
     * Uses the compound interest formula:
     * PMT = (P * r * (1 + r)^n) / ((1 + r)^n - 1)
     *
     * Where:
     * - P = Principal
     * - r = Interest rate per period
     * - n = Number of periods
     *
     * ### Parameters
     * - `$principal`: Loan amount (must be > 0)
     * - `$annualRate`: Annual interest rate as percentage (5 = 5%)
     * - `$paymentFrequency`: 'monthly', 'biweekly', 'weekly', 'daily'
     * - `$numberOfPayments`: Total number of payments
     *
     * ### Returns
     * Float payment amount in dollars and cents (2 decimal places)
     *
     * ### Example
     * ```php
     * // Calculate monthly payment for $10,000 at 5% for 30 years (360 months)
     * $payment = $model->calculatePayment(
     *     principal: 10000.00,
     *     annualRate: 5.0,
     *     paymentFrequency: 'monthly',
     *     numberOfPayments: 360
     * );
     * // Returns: 53.68 (approximately)
     *
     * // Calculate bi-weekly payment for same loan
     * $bwPayment = $model->calculatePayment(
     *     principal: 10000.00,
     *     annualRate: 5.0,
     *     paymentFrequency: 'biweekly',
     *     numberOfPayments: 780  // 30 years * 26 payments/year
     * );
     * // Returns: 27.45 (approximately, paid 26x/year)
     * ```
     *
     * ### Precision & Rounding
     * - Maintains 4 decimal places internally
     * - Returns 2 decimal places (cents)
     * - Uses banker's rounding
     *
     * @param float $principal Principal amount
     * @param float $annualRate Annual interest rate as percentage
     * @param string $paymentFrequency Payment frequency
     * @param int $numberOfPayments Total number of payments
     *
     * @return float Payment amount
     * @throws InvalidArgumentException If parameters are invalid
     *
     * @see calculateSchedule() Generate full schedule using this method
     * @since 1.0.0
     */
    public function calculatePayment(
        float $principal,
        float $annualRate,
        string $paymentFrequency,
        int $numberOfPayments
    ): float
    {
        // Implementation...
    }

    /**
     * Record an extra payment and recalculate schedule
     *
     * ### Purpose
     * Records an extra payment and automatically recalculates the
     * remaining schedule to reflect the additional principal reduction.
     *
     * ### Algorithm
     * 1. Record extra payment as LoanEvent
     * 2. Get all schedule rows after payment date
     * 3. Calculate cumulative principal reduction
     * 4. Update all subsequent payment balances
     * 5. Possibly reduce number of remaining payments
     *
     * ### Example
     * ```php
     * // Customer makes extra $500 payment on payment #37
     * $model->recordExtraPayment(
     *     loanId: 42,
     *     amount: 500.00,
     *     paymentDate: new DateTime('2025-06-15'),
     *     reason: 'Extra principal payment'
     * );
     *
     * // Schedule is automatically recalculated
     * // Payment #38 will have lower principal, ending with zero balance sooner
     * ```
     *
     * @param int $loanId Loan ID
     * @param float $amount Extra payment amount
     * @param DateTime $paymentDate Date of extra payment
     * @param string $reason Reason for extra payment
     *
     * @return void
     * @throws RuntimeException If recalculation fails
     * @since 1.0.0
     */
    public function recordExtraPayment(
        int $loanId,
        float $amount,
        DateTime $paymentDate,
        string $reason = 'Extra payment'
    ): void
    {
        // Implementation...
    }
}
```

---

## Checklist for Code Review

Before submitting code, verify all items:

### PhpDoc Completeness
- [ ] Class has docblock with purpose, responsibilities, design patterns
- [ ] All public methods have docblocks
- [ ] All parameters documented with @param
- [ ] All returns documented with @return
- [ ] All exceptions documented with @throws
- [ ] Examples provided for complex methods
- [ ] @since tag indicates when method was added
- [ ] @see references to related methods

### UML Documentation
- [ ] Class diagram included (if complex class)
- [ ] Dependency diagram shows all dependencies
- [ ] Design patterns documented
- [ ] Sequence flow documented if complex
- [ ] State machine documented if applicable

### SOLID Principles
- [ ] Class has single responsibility (SRP)
- [ ] Design allows extension without modification (OCP)
- [ ] Substitutable implementations work correctly (LSP)
- [ ] Interface minimal and cohesive (ISP)
- [ ] Depends on interfaces, not implementations (DIP)

### Code Quality
- [ ] No hardcoded values (use constants/configuration)
- [ ] Methods named with verb-noun (calculatePayment, not Calculation)
- [ ] No methods do multiple unrelated things
- [ ] Private methods start with underscore or use private visibility
- [ ] Consistent naming conventions throughout

### Testing
- [ ] Unit tests exist for all public methods
- [ ] Tests have descriptive names (testCalculatePaymentMonthly)
- [ ] Tests verify both happy path and edge cases
- [ ] Mocks used instead of real dependencies
- [ ] Test coverage >80% for new code

### Performance
- [ ] No N+1 database queries
- [ ] Inefficient loops identified and optimized
- [ ] Database queries use indexes
- [ ] Time/space complexity documented

### Security
- [ ] Input validation present
- [ ] Parameterized queries used (no SQL injection)
- [ ] No sensitive data in logs
- [ ] Access control checked if needed

---

## References

- [PSR-5: PHPDoc Standard](https://www.php-fig.org/psr/psr-5/)
- [UML Documentation Guide](https://en.wikipedia.org/wiki/Unified_Modeling_Language)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Design Patterns in PHP](https://refactoring.guru/design-patterns/php)
- [Dependency Injection](https://en.wikipedia.org/wiki/Dependency_injection)

