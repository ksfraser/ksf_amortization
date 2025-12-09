# Development Guidelines - SOLID, TDD, DI Principles

**Version:** 1.0.0  
**Status:** Live - Use for all Phase 1+ development  
**Author:** KSF Development Team  
**Last Updated:** December 8, 2025  

---

## Table of Contents

1. [TDD Workflow](#tdd-workflow)
2. [SOLID Principles in Practice](#solid-principles-in-practice)
3. [Dependency Injection Patterns](#dependency-injection-patterns)
4. [Code Organization](#code-organization)
5. [Testing Guidelines](#testing-guidelines)
6. [Documentation Requirements](#documentation-requirements)
7. [Code Review Checklist](#code-review-checklist)

---

## TDD Workflow

### The Red-Green-Refactor Cycle

All development follows strict TDD discipline:

```
┌─────────────────────────────────────────────────────────┐
│                   RED-GREEN-REFACTOR                    │
└─────────────────────────────────────────────────────────┘

STEP 1: RED (Write Failing Test)
├─ Write test for desired functionality
├─ Test should fail initially (implementation doesn't exist)
├─ Test must be specific and have single assertion focus
├─ Example:
│   public function testCalculatePaymentMonthly() {
│       $payment = $model->calculatePayment(
│           principal: 10000, rate: 5.0, 
│           frequency: 'monthly', payments: 360
│       );
│       $this->assertAlmostEquals(53.68, $payment, 0.02);
│   }
└─ Run: vendor/bin/phpunit tests/Phase1CriticalTest.php::Test::testCalculatePaymentMonthly
   RESULT: ❌ FAIL - Method doesn't exist

STEP 2: GREEN (Write Minimal Implementation)
├─ Write minimal code to make test pass
├─ Don't optimize yet, don't add extras
├─ Focus only on making test pass
├─ Example:
│   public function calculatePayment(
│       float $principal,
│       float $rate,
│       string $frequency,
│       int $payments
│   ): float {
│       $monthlyRate = $rate / 100 / 12;
│       $numerator = $monthlyRate * pow(1 + $monthlyRate, $payments);
│       $denominator = pow(1 + $monthlyRate, $payments) - 1;
│       return round($principal * ($numerator / $denominator), 2);
│   }
└─ Run test
   RESULT: ✅ PASS

STEP 3: REFACTOR (Improve Without Changing Tests)
├─ Extract methods for readability
├─ Add configuration constants
├─ Improve variable names
├─ Add inline documentation
├─ Example improvements:
│   - Extract getPeriodsPerYear() method
│   - Extract calculateCompoundFactor() method
│   - Add constants: PRECISION = 2, INTERNAL_PRECISION = 4
│   - Improve variable names: $rate → $annualInterestRate
└─ Run tests after each change
   RESULT: ✅ STILL PASS (tests unchanged, only code improved)

REPEAT: Write next test for next feature
```

### Benefits of TDD

| Benefit | How It Helps |
|---------|-------------|
| Design Clarity | Writing test first forces you to think about API |
| Confidence | If all tests pass, code works (by definition) |
| Regression Prevention | Tests catch accidental breaks |
| Documentation | Tests show how to use the code |
| Maintainability | Tests allow safe refactoring |
| Quality | Bugs prevented rather than fixed |
| Coverage | Can't finish until 80%+ covered |

### TDD Rules

✓ **Always write test first** - NEVER write code without test  
✓ **Test should fail initially** - Proves you're testing something  
✓ **Write minimal code to pass** - No over-engineering  
✓ **Keep tests simple** - One assertion per test when possible  
✓ **Run tests frequently** - After every change  
✓ **Never skip refactoring** - TDD is Red-Green-**Refactor**  
✓ **Delete tests that don't fail** - Dead code  

---

## SOLID Principles in Practice

### 1. Single Responsibility Principle (SRP)

**Definition:** A class should have ONE reason to change.

**Good Example:**
```php
/**
 * AmortizationModel - Calculation only
 * 
 * Responsibility: Amortization calculations
 * Reasons to change: When calculation logic needs adjustment
 */
class AmortizationModel {
    // Responsible for: Payments, schedules, interest calculations
    // NOT responsible for: Database, GL posting, UI
    
    public function calculatePayment(...) { }
    public function calculateSchedule(...) { }
    private function calculateCompoundInterest(...) { }
}

/**
 * FAJournalService - GL posting only
 * 
 * Responsibility: Creating journal entries
 * Reasons to change: When GL posting logic needs adjustment
 */
class FAJournalService {
    // Responsible for: Journal entries, account mapping
    // NOT responsible for: Calculations, database operations
    
    public function postPaymentToGL(...) { }
    public function validateGLAccounts(...) { }
}

/**
 * SelectorProvider - Configuration selection
 * 
 * Responsibility: Providing configured types/frequencies
 * Reasons to change: When selector logic needs adjustment
 */
class SelectorProvider {
    // Responsible for: Configuration lookup
    // NOT responsible for: Calculations, persistence
    
    public function getFrequencies() { }
    public function getLoanTypes() { }
}
```

**Bad Example (Violates SRP):**
```php
/**
 * ❌ DON'T: Mixing concerns
 */
class LoanProcessor {
    // This class has TOO MANY reasons to change:
    // 1. When calculation logic changes
    // 2. When database schema changes
    // 3. When GL posting changes
    // 4. When email format changes
    // 5. When report format changes
    
    public function processLoan() {
        // Calculates payment
        // Saves to database
        // Posts to GL
        // Sends email
        // Generates report
    }
}
```

**Application to Amortization:**

| Class | Single Responsibility | Changed When |
|-------|----------------------|--------------|
| AmortizationModel | Calculate payments & schedules | Calculation algorithm changes |
| FAJournalService | Post to GL, manage journal entries | GL posting requirements change |
| DataProviderInterface | Abstract data access | Platform changes (FA, WP, etc) |
| SelectorProvider | Provide configured options | Frequencies/types need adjustment |
| LoanEventProvider | Manage extra payment events | Event handling changes |

**Benefit:** Each class has ONE reason to change → Easier to maintain, test, and modify

---

### 2. Open/Closed Principle (OCP)

**Definition:** Open for extension, closed for modification.

**Good Example:**
```php
/**
 * New frequency? No need to modify AmortizationModel!
 * Just add to database and SelectorProvider picks it up
 */

// Add to database
INSERT INTO ksf_amort_interest_calc_frequencies 
VALUES (5, 'Semi-Annual', 'semiannual', 2, 182.5);

// AmortizationModel automatically supports it
$payment = $model->calculatePayment(
    principal: 10000,
    rate: 5.0,
    frequency: 'semiannual',  // NEW - works without code change!
    payments: 60
);

// Works because:
// 1. Frequency retrieved dynamically from database (SelectorProvider)
// 2. Calculation uses getPaymentsPerYear() method (flexible)
// 3. No hardcoded frequency logic in AmortizationModel
```

**Bad Example (Violates OCP):**
```php
/**
 * ❌ DON'T: Adding switch cases for every frequency
 */
public function calculatePayment(...) {
    switch ($frequency) {
        case 'monthly':
            $periodsPerYear = 12;
            break;
        case 'biweekly':
            $periodsPerYear = 26;
            break;
        case 'weekly':
            $periodsPerYear = 52;
            break;
        case 'daily':
            $periodsPerYear = 365;
            break;
        // Need to modify this method to add new frequency!
    }
}
```

**Application to Amortization:**

```php
// ✓ GOOD: Open/Closed approach
class AmortizationModel {
    public function __construct(
        private SelectorProvider $selectors  // Inject dependency
    ) {}
    
    public function calculatePayment(...): float {
        // Get frequency from provider (database-driven)
        $frequency = $this->selectors->getFrequency($frequencyId);
        $periodsPerYear = $frequency->getPeriodsPerYear();
        
        // Use dynamically retrieved periods per year
        $periodRate = $annualRate / 100 / $periodsPerYear;
        // ... rest of calculation
    }
}

// New frequency? Add to database - code unchanged!
// AmortizationModel remains CLOSED for modification
// But OPEN for extension through database configuration
```

**Benefit:** Add new features without modifying existing code → Less bugs, more maintainable

---

### 3. Liskov Substitution Principle (LSP)

**Definition:** Subclasses must be substitutable for their base classes.

**Good Example:**
```php
/**
 * All DataProviders can be substituted for each other
 */
interface DataProviderInterface {
    public function getLoan(int $loanId): ?LoanSummary;
    public function insertSchedule(...): int;
    public function getScheduleRowsAfterDate(...): array;
    // ... other methods
}

class FADataProvider implements DataProviderInterface {
    // FrontAccounting-specific implementation
}

class WPDataProvider implements DataProviderInterface {
    // WordPress-specific implementation
}

class MockDataProvider implements DataProviderInterface {
    // Test mock implementation
}

// Usage: All are substitutable!
class AmortizationModel {
    public function __construct(DataProviderInterface $provider) {
        // Could be FA, WP, or Mock - doesn't matter!
        // All implement same contract
        $this->provider = $provider;
    }
}

// Production
$provider = new FADataProvider($pdo);
$model = new AmortizationModel($provider);

// Testing
$provider = new MockDataProvider($mockDb);
$model = new AmortizationModel($provider);  // ✓ Substitutable!

// Future: WordPress support
$provider = new WPDataProvider($wpDb);
$model = new AmortizationModel($provider);  // ✓ Still works!
```

**Bad Example (Violates LSP):**
```php
/**
 * ❌ DON'T: Subclass changes contract
 */
class BaseDataProvider {
    public function getLoan(int $id): ?LoanSummary { }
}

class BrokenDataProvider extends BaseDataProvider {
    public function getLoan(int $id): ?LoanSummary {
        // Returns null for valid IDs (violates contract!)
        return null;
    }
}

// This breaks substitutability!
$model = new AmortizationModel(new BrokenDataProvider());
// Model expects valid loans, gets null - crashes!
```

**Benefit:** Can swap implementations without worrying about breaking changes → Maintainable, testable

---

### 4. Interface Segregation Principle (ISP)

**Definition:** Clients shouldn't depend on interfaces they don't use.

**Good Example:**
```php
/**
 * ✓ GOOD: Focused, minimal interface
 */
interface DataProviderInterface {
    public function getLoan(int $loanId): ?LoanSummary;
    public function insertSchedule(int $loanId, int $paymentNumber, array $data): int;
    public function insertLoanEvent(int $loanId, LoanEvent $event): int;
    public function getScheduleRowsAfterDate(int $loanId, DateTime $date): array;
    public function updateScheduleRow(int $scheduleId, array $data): void;
    public function deleteScheduleAfterDate(int $loanId, DateTime $date): int;
}

// Client depends only on methods it uses
class AmortizationModel {
    public function __construct(private DataProviderInterface $provider) {}
    
    // Uses: getLoan, insertSchedule, insertLoanEvent, getScheduleRowsAfterDate, updateScheduleRow
    // Doesn't use: deleteScheduleAfterDate (used elsewhere only)
    // But that's OK - interface is still focused
}
```

**Bad Example (Violates ISP):**
```php
/**
 * ❌ DON'T: Fat interface with methods clients don't use
 */
interface DataProviderInterface {
    // Loan management (needed)
    public function getLoan(): ?LoanSummary;
    public function insertSchedule(): int;
    
    // User management (not needed by AmortizationModel)
    public function getUserById(): ?User;
    public function createUser(): int;
    public function deleteUser(): void;
    
    // Reporting (not needed by AmortizationModel)
    public function generateScheduleReport(): string;
    public function generateGLReport(): string;
    
    // Email (not needed by AmortizationModel)
    public function sendNotificationEmail(): void;
    
    // ... lots more methods
}

// AmortizationModel forced to implement/depend on all of these!
class AmortizationModel implements DataProviderInterface {
    // Must implement: getUserById, createUser, deleteUser, sendNotificationEmail, etc.
    // Even though it doesn't use them!
}
```

**Application to Amortization:**

```php
// ✓ Minimal, focused interfaces by concern

interface AmortizationCalculator {
    public function calculatePayment(...): float;
    public function calculateSchedule(...): array;
}

interface DataPersistence {
    public function insertSchedule(...): int;
    public function updateScheduleRow(...): void;
    public function getScheduleRowsAfterDate(...): array;
}

interface EventManagement {
    public function insertLoanEvent(...): int;
    public function getPendingEvents(...): array;
    public function markEventProcessed(...): void;
}

interface GLPosting {
    public function postPaymentToGL(...): bool;
    public function validateGLAccounts(...): bool;
    public function createJournalEntry(...): string;
}

// Each class depends only on what it needs
class AmortizationModel implements AmortizationCalculator {
    // Uses: calculatePayment, calculateSchedule
}

class FADataProvider implements DataPersistence {
    // Uses: insertSchedule, updateScheduleRow, getScheduleRowsAfterDate
}

class FAJournalService implements GLPosting {
    // Uses: postPaymentToGL, validateGLAccounts, createJournalEntry
}
```

**Benefit:** Classes depend only on methods they use → No forced dependencies, cleaner code

---

### 5. Dependency Inversion Principle (DIP)

**Definition:** Depend on abstractions (interfaces), not concretions (implementations).

**Good Example:**
```php
/**
 * ✓ GOOD: Depend on interface, not implementation
 */

// High-level code (AmortizationModel)
class AmortizationModel {
    // Depends on INTERFACE, not on specific implementation
    public function __construct(
        private DataProviderInterface $provider,  // Interface!
        private LoanEventProviderInterface $events  // Interface!
    ) {}
    
    public function calculateSchedule($loan, $payments) {
        // Use interface methods
        $schedule = [];
        // ...calculation logic...
        
        // Store using interface (could be FA, WP, or Mock)
        $this->provider->insertSchedule(...);
        
        // Handle events using interface
        $this->events->getPendingEvents($loan->id);
    }
}

// Low-level code (implementations)
class FADataProvider implements DataProviderInterface {
    // Implements interface contract
    public function insertSchedule(...): int { }
}

class MockDataProvider implements DataProviderInterface {
    // Implements same interface contract
    public function insertSchedule(...): int { }
}

// Dependency inversion:
// High-level (AmortizationModel) depends on Interface
// Low-level (FADataProvider, MockDataProvider) depends on Interface
// Both point to Interface (inverted from normal hierarchy)
```

**Bad Example (Violates DIP):**
```php
/**
 * ❌ DON'T: Depend on concrete implementations
 */
class AmortizationModel {
    public function __construct(
        private FADataProvider $provider  // ❌ Concrete class!
    ) {}
    
    public function calculateSchedule($loan) {
        $this->provider->insertSchedule(...);  // Tightly coupled to FA
    }
}

// Problems:
// 1. Hard to test (can't inject mock)
// 2. Hard to change platforms (if switching from FA to WP)
// 3. Hard to add new platforms (must modify AmortizationModel)
// 4. Violates Open/Closed Principle
```

**Application to Amortization:**

```php
// ✓ DIP Applied Correctly

// Setup: Container manages dependencies
$container = new DIContainer();

// Register implementations behind interfaces
$container->singleton('DataProviderInterface', 
    fn() => new FADataProvider($pdo)
);
$container->singleton('LoanEventProviderInterface',
    fn() => new GenericLoanEventProvider($pdo)
);
$container->singleton('SelectorProvider',
    fn() => new SelectorProvider($pdo)
);

// High-level code depends on interfaces
$model = new AmortizationModel(
    $container->get('DataProviderInterface'),      // Get interface
    $container->get('LoanEventProviderInterface'), // Get interface
    $container->get('SelectorProvider')            // Get interface
);

// In tests: swap implementations
$testContainer = new DIContainer();
$testContainer->singleton('DataProviderInterface',
    fn() => new MockDataProvider($mockDb)  // Swap to mock
);

$testModel = new AmortizationModel(
    $testContainer->get('DataProviderInterface')   // Gets mock instead!
);

// AmortizationModel never changes - completely flexible!
```

**Benefit:** Decouple high-level from low-level code → Testable, extensible, maintainable

---

## Dependency Injection Patterns

### Constructor Injection (Preferred)

```php
/**
 * ✓ BEST PRACTICE: Inject dependencies in constructor
 */
class AmortizationModel {
    public function __construct(
        private DataProviderInterface $provider,
        private LoanEventProviderInterface $events,
        private SelectorProvider $selectors
    ) {
        // Dependencies are immutable and required
        // Makes dependencies explicit
    }
}

// Usage
$model = new AmortizationModel($provider, $events, $selectors);

// Benefits
// ✓ Dependencies explicit in constructor signature
// ✓ Dependencies immutable (private readonly)
// ✓ Can't create invalid objects (missing dependencies)
// ✓ Easy to test (pass mocks)
// ✓ Follows DIP
```

### Service Container/DI Container

```php
/**
 * ✓ GOOD: Use container to manage dependencies
 */

// Register services
$container = new DIContainer();
$container->singleton('PDO', fn() => new PDO('...'));
$container->singleton('DataProviderInterface', 
    fn($c) => new FADataProvider($c->get('PDO'))
);
$container->singleton('AmortizationModel',
    fn($c) => new AmortizationModel(
        $c->get('DataProviderInterface'),
        $c->get('LoanEventProviderInterface')
    )
);

// Resolve from container
$model = $container->get('AmortizationModel');

// Benefits
// ✓ Centralized configuration
// ✓ Easy to swap implementations
// ✓ Singletons for expensive objects (DB connections)
// ✓ Factory functions for complex creation logic
```

### Avoid These Anti-Patterns

```php
/**
 * ❌ DON'T: Hardcode dependencies
 */
class AmortizationModel {
    public function __construct() {
        // Bad! Creates its own dependencies
        // Hard to test, hard to change
        $this->provider = new FADataProvider(
            new PDO('...')  // Connection hardcoded!
        );
    }
}

/**
 * ❌ DON'T: Use Service Locator
 */
class AmortizationModel {
    public function calculateSchedule() {
        // Bad! Uses global service locator
        // Dependencies hidden, hard to test
        $provider = ServiceLocator::get('DataProvider');
        $provider->insertSchedule(...);
    }
}

/**
 * ❌ DON'T: Mix injection with creation
 */
class AmortizationModel {
    public function __construct(
        private DataProviderInterface $provider,
        private $eventProvider = null  // Sometimes injected?
    ) {
        if (!$eventProvider) {
            // Sometimes created here?
            // Inconsistent and confusing
            $this->eventProvider = new GenericLoanEventProvider();
        }
    }
}
```

---

## Code Organization

### Directory Structure

```
ksf_amortization/
├── src/Ksfraser/Amortizations/
│   ├── AmortizationModel.php          # Core business logic
│   ├── AmortizationModuleInstaller.php
│   ├── DataProviderInterface.php      # Abstract data access
│   ├── GenericLoanEventProvider.php   # Event handling
│   ├── InterestCalcFrequency.php      # Value object
│   ├── LoanEvent.php                   # Value object
│   ├── LoanEventProvider.php           # Base event provider
│   ├── LoanEventProviderInterface.php  # Event interface
│   ├── LoanSummary.php                # Value object
│   ├── LoanType.php                    # Value object
│   ├── SelectorDbAdapterPDO.php       # PDO database adapter
│   ├── SelectorDbAdapterWPDB.php      # WPDB adapter
│   ├── SelectorModels.php              # Configuration models
│   ├── SelectorProvider.php            # Configuration provider
│   ├── SelectorTables.php              # Table definitions
│   ├── controller.php                  # HTTP controller
│   ├── model.php                       # Module model
│   ├── reporting.php                   # Reporting logic
│   └── ...
│
├── modules/amortization/               # Legacy module structure
│   ├── FADataProvider.php
│   ├── FAJournalService.php
│   ├── LoanEventProvider.php
│   └── ...
│
├── tests/
│   ├── BaseTestCase.php               # Base test class with infrastructure
│   ├── DIContainer.php                # DI container for tests
│   ├── MockClasses.php                # Mock implementations
│   ├── Phase1CriticalTest.php         # Phase 1 test suite
│   ├── ControllerPlatformTest.php
│   ├── FADataProviderTest.php
│   ├── LoanEventProviderTest.php
│   └── ...
│
├── docs/
│   ├── PHPDOC_UML_STANDARDS.md        # Documentation standards
│   ├── DEVELOPMENT_GUIDELINES.md      # This file
│   ├── IMPLEMENTATION_PLAN_PHASE1.md
│   ├── UAT_TEST_SCRIPTS.md
│   └── ...
│
└── ...
```

### File Naming Conventions

| File Type | Naming | Example |
|-----------|--------|---------|
| Interface | `*Interface.php` | `DataProviderInterface.php` |
| Abstract Class | `Abstract*.php` | `AbstractDataProvider.php` |
| Trait | `*Trait.php` | `LoggingTrait.php` |
| Test Case | `*Test.php` | `AmortizationModelTest.php` |
| Value Object | `*.php` | `LoanSummary.php` |
| Service | `*Service.php` | `FAJournalService.php` |
| Provider | `*Provider.php` | `SelectorProvider.php` |

### Class Organization

```php
<?php
namespace Ksfraser\Amortizations;

/**
 * Class documentation (see PHPDOC_UML_STANDARDS.md)
 */
class MyClass {
    // 1. Constants (all caps, publicly documented)
    public const PRECISION = 4;
    private const INTERNAL_PRECISION = 6;
    
    // 2. Properties (private unless there's a reason)
    private DataProviderInterface $dataProvider;
    private int $calculationPrecision;
    
    // 3. Constructor
    public function __construct(
        DataProviderInterface $dataProvider,
        int $precision = self::PRECISION
    ) {
        $this->dataProvider = $dataProvider;
        $this->calculationPrecision = $precision;
    }
    
    // 4. Public methods (API)
    public function publicMethod(): void { }
    
    // 5. Private methods (helpers)
    private function privateHelper(): void { }
}
```

---

## Testing Guidelines

### Test Structure

```php
<?php
namespace Ksfraser\Amortizations\Tests;

use PHPUnit\Framework\TestCase;

/**
 * [Feature]Test - Test class for [feature]
 * 
 * Tests: [class/method being tested]
 * Coverage target: 80%+
 */
class MyFeatureTest extends BaseTestCase {
    
    // ========================================
    // Test Methods (alphabetically grouped by feature)
    // ========================================
    
    /**
     * @test
     * Descriptive test name (what it tests)
     * 
     * Tests: Specific method/behavior
     * Scenario: What situation is being tested
     * Expected: What should happen
     */
    public function testSpecificBehavior(): void {
        // Arrange: Set up test data
        $loan = $this->createMockLoan(principal: 10000);
        
        // Act: Execute the behavior
        $payment = $model->calculatePayment(...);
        
        // Assert: Verify the result
        $this->assertAlmostEquals(53.68, $payment, 0.02);
    }
}
```

### Test Naming Convention

```
test[Feature][Scenario]

Examples:
✓ testCalculatePaymentMonthly
✓ testCalculateScheduleWithBiWeeklyFrequency
✓ testExtraPaymentReducesBalance
✓ testGLPostingWithInvalidAccounts
✓ testRecalculationAfterMultipleEvents
```

### Assertion Best Practices

```php
// ✓ Good: One assertion per test (or very focused assertions)
public function testPaymentCalculation(): void {
    $payment = $model->calculatePayment(10000, 5.0, 'monthly', 360);
    $this->assertAlmostEquals(53.68, $payment, 0.02);
}

// ✓ Also OK: Related assertions on same object
public function testScheduleFirstPayment(): void {
    $schedule = $model->calculateSchedule($loan, 360);
    $first = $schedule[0];
    
    // All assertions about first payment
    $this->assertAlmostEquals(41.67, $first['interest'], 0.02);
    $this->assertAlmostEquals(12.01, $first['principal'], 0.02);
    $this->assertAlmostEquals(9987.99, $first['balance'], 0.02);
}

// ❌ Bad: Unrelated assertions (should be separate tests)
public function testEverything(): void {
    $payment = $model->calculatePayment(...);
    $this->assertAlmostEquals(53.68, $payment);
    
    $schedule = $model->calculateSchedule(...);
    $this->assertCount(360, $schedule);
    
    $event = $model->recordExtraPayment(...);
    $this->assertTrue($event);
    // 3 different features in 1 test!
}
```

### Mock Objects

```php
// ✓ Use mock classes instead of real implementations
public function testCalculation(): void {
    // Use mock instead of real FA connection
    $provider = new MockDataProvider($mockDb);
    $model = new AmortizationModel($provider);
    
    // Test proceeds without touching real database
}

// ❌ Don't connect to real database in unit tests
public function testCalculation(): void {
    // This makes tests slow and fragile
    $pdo = new PDO('mysql:host=localhost;dbname=production');
    $provider = new FADataProvider($pdo);
    $model = new AmortizationModel($provider);
    
    // If database down, test fails!
}
```

### Test Coverage Requirements

```
Minimum Coverage by Component:

Critical Components (Phase 1):
├─ AmortizationModel: 90%+ (core business logic)
├─ DataProviderInterface: 85%+ (data access)
├─ LoanEventProvider: 85%+ (event handling)
└─ FAJournalService: 80%+ (GL posting)

Important Components:
├─ SelectorProvider: 75%+
├─ Controllers: 70%+
└─ Value Objects: 70%+

Nice to Have:
├─ UI (views): 50%+
├─ Reporting: 60%+
└─ Utilities: 60%+

Run coverage:
$ vendor/bin/phpunit --coverage-html coverage/ tests/
```

---

## Documentation Requirements

### Every Public Class Must Have

✓ Class docblock with purpose, responsibilities, design patterns  
✓ Constructor docblock  
✓ Every public method documented  
✓ UML class diagram (if complex)  
✓ Example usage  

### Every Public Method Must Have

✓ Brief description of what it does  
✓ @param tags for all parameters  
✓ @return tag describing return value  
✓ @throws tags for exceptions  
✓ Example of how to use it  

### When to Add Extra Documentation

| Situation | Documentation Needed |
|-----------|---------------------|
| Complex algorithm | Algorithm explanation, step-by-step |
| Business logic | Business rules and assumptions |
| Performance critical | Performance notes, complexity |
| Thread safety concerns | Thread safety statement |
| Precision/rounding | Precision and rounding approach |
| Edge cases | Edge cases and special handling |

---

## Code Review Checklist

Before submitting code, verify all items:

### TDD Adherence
- [ ] Test written before implementation
- [ ] Test fails initially (RED)
- [ ] Implementation makes test pass (GREEN)
- [ ] Code refactored without changing tests (REFACTOR)
- [ ] All tests still pass
- [ ] No skipped tests

### SOLID Principles
- [ ] Single Responsibility: Class has ONE reason to change
- [ ] Open/Closed: Extensible without modifying
- [ ] Liskov Substitution: Implementations are substitutable
- [ ] Interface Segregation: Interface minimal and focused
- [ ] Dependency Inversion: Depends on interfaces, not concretes

### Dependency Injection
- [ ] No hardcoded dependencies
- [ ] Dependencies injected via constructor
- [ ] Dependencies are interfaces, not implementations
- [ ] No Service Locator pattern
- [ ] Testability with mocks verified

### Code Quality
- [ ] No duplicate code (DRY principle)
- [ ] Variable names descriptive
- [ ] Methods have ONE responsibility
- [ ] No magic numbers (use constants)
- [ ] No null checks needed (proper typing)

### Testing
- [ ] All public methods have tests
- [ ] Happy path tested
- [ ] Edge cases tested
- [ ] Error cases tested
- [ ] Coverage >80% for new code
- [ ] Mocks used instead of real dependencies

### Documentation
- [ ] Class has complete docblock
- [ ] All public methods documented
- [ ] Parameters documented with @param
- [ ] Returns documented with @return
- [ ] Exceptions documented with @throws
- [ ] Examples provided for complex methods
- [ ] UML diagram included if complex

### Performance
- [ ] No N+1 queries
- [ ] Inefficient loops identified
- [ ] Large data structures optimized
- [ ] Performance goals met (<2 sec calculations)

### Security
- [ ] Input validation present
- [ ] Parameterized queries used (no SQL injection)
- [ ] Access control implemented
- [ ] Sensitive data not logged

### Commit Quality
- [ ] Commit message clear and descriptive
- [ ] Commits atomic (one logical change per commit)
- [ ] No unrelated changes in commit
- [ ] Branch name descriptive

---

## Running Tests

### Run All Tests
```bash
vendor/bin/phpunit tests/
```

### Run Specific Test File
```bash
vendor/bin/phpunit tests/Phase1CriticalTest.php
```

### Run Specific Test Method
```bash
vendor/bin/phpunit tests/Phase1CriticalTest.php::testCalculatePaymentMonthly
```

### Run With Coverage
```bash
vendor/bin/phpunit --coverage-html coverage/ tests/
# Open coverage/index.html in browser
```

### Run in Watch Mode
```bash
vendor/bin/phpunit tests/ --watch
# Re-runs when files change
```

---

## Resources

- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Test Driven Development](https://en.wikipedia.org/wiki/Test-driven_development)
- [Dependency Injection](https://en.wikipedia.org/wiki/Dependency_injection)
- [PHPUnit Documentation](https://phpunit.de/)
- [PHP Best Practices](https://www.php.net/manual/en/)

