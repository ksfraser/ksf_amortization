# Architecture Overview

## System Architecture Layers

### Layer 1: Core Business Logic (`src/Ksfraser/Amortizations/`)
Platform-agnostic calculation and business rule engine.

**Key Components:**
- `AmortizationModel` - Core loan model with business logic
- `AmortizationCalculator` - Payment schedule calculation algorithms
- `InterestCalculator` - Interest calculation with multiple frequency support
- `LoanOriginationService` - Loan creation and approval workflows
- `AdvancedReportingService` - Financial reports and analytics
- `ComplianceReportingService` - Regulatory compliance reports
- `PortfolioAnalyticsService` - Portfolio-level metrics

### Layer 2: Data Access (`Repository/`)
Database abstraction following Repository pattern.

**Components:**
- `SelectorRepository` - CRUD operations for selector/lookup tables
- `GLAccountRepository` - GL account management (FA-specific)
- `LoanRepository` - Loan data persistence
- `DataProviderInterface` - Platform-agnostic data access contract

**Platform Implementations:**
- `FADataProvider` - FrontAccounting adapter
- `WPDataProvider` - WordPress adapter (planned)
- `SuiteCRMDataProvider` - SuiteCRM adapter (planned)

### Layer 3: Presentation (`modules/amortization/`, `views/`)
UI components and controllers.

**Controller Layer:**
- `controller.php` - Main routing controller
  - Action-based routing (admin, create, report, default)
  - FA page wrapper integration (page()/end_page())
  - Autoloader configuration
  - Menu rendering via MenuBuilder

**View Layer:**
- `admin_settings.php` - GL account mappings (FA)
- `admin_selectors.php` - Manage loan types, frequencies
- `user_loan_setup.php` - Loan creation form
- `view.php` - Loan list/dashboard
- `reporting.php` - Payment schedules and reports

**UI Components:**
- `MenuBuilder` (SRP class) - Navigation menu generation
- HTML Builder classes (`Ksfraser\HTML\Elements\*`):
  - `HtmlDiv`, `HtmlA`, `HtmlParagraph`, `HtmlString`
  - `HtmlForm`, `HtmlInput`, `SelectBuilder`
  - `HtmlAttribute` - Attribute management

### Layer 4: Integration Adapters
Platform-specific integration code.

**FrontAccounting Integration:**
- `FAJournalService` - GL posting service
- `FADataProvider` - Database adapter
- `hooks.php` - FA menu registration
- GL mapping configuration

**WordPress Integration (Planned):**
- `WPDataProvider` - WordPress database adapter
- WordPress admin screens
- Shortcode support

**SuiteCRM Integration (Planned):**
- `SuiteCRMDataProvider` - CRM database adapter
- CRM module integration

---

## Design Patterns & Principles

### SOLID Principles Applied

**Single Responsibility Principle (SRP):**
- `MenuBuilder` - ONLY builds navigation menus
- `SelectorRepository` - ONLY handles selector data access
- `InterestCalculator` - ONLY calculates interest
- `AmortizationCalculator` - ONLY calculates payment schedules

**Open/Closed Principle:**
- `DataProviderInterface` allows new platforms without modifying core
- Calculator strategies extensible for new interest methods

**Liskov Substitution:**
- All DataProvider implementations interchangeable
- HTML builder elements follow consistent interfaces

**Interface Segregation:**
- Separate interfaces for data access, calculation, reporting
- No "fat" interfaces forcing unnecessary dependencies

**Dependency Inversion:**
- Controllers depend on abstractions (MenuBuilder, Repository)
- Business logic never depends on platform-specific code

### Repository Pattern
Encapsulates data access logic, providing clean API for CRUD operations.

**Benefits:**
- Database logic isolated from business logic
- Testable with mock repositories
- Platform-agnostic data access
- Query optimization centralized

**Example:**
```php
$selectorRepo = new SelectorRepository($db, 'ksf_selectors', '0_');
$loanTypes = $selectorRepo->getBySelectorName('loan_type');
```

### Builder Pattern (HTML Generation)
Fluent interface for constructing HTML elements.

**Benefits:**
- No hardcoded HTML strings in controllers/views
- Type-safe attribute management
- Reusable, testable components
- Consistent styling

**Example:**
```php
$link = (new HtmlA())
    ->setHref('/loan/create')
    ->setAttribute(new HtmlAttribute('class', 'button'))
    ->setInnerHtml(new HtmlString('Create Loan'))
    ->getHtml();
```

### Dependency Injection
Components receive dependencies via constructor/method injection.

**Example:**
```php
// MenuBuilder receives path dependency
$menuBuilder = new AmortizationMenuBuilder($path_to_root);
echo $menuBuilder->build();
```

---

## Component Interaction Flow

### Loan Creation Flow
```
User Request
    ↓
Controller (controller.php)
    ↓ (routes to action=create)
View (user_loan_setup.php)
    ↓ (renders form using HtmlForm, HtmlInput builders)
User Submits Form
    ↓
Controller validates input
    ↓
AmortizationModel (business logic)
    ↓
FADataProvider (data persistence)
    ↓
Database (ksf_loans_summary)
    ↓
AmortizationCalculator (generate schedule)
    ↓
Database (ksf_amortization_staging)
    ↓
Response/Redirect
```

### Menu Rendering Flow
```
Controller Start
    ↓
Instantiate MenuBuilder($path_to_root)
    ↓
MenuBuilder.build()
    ↓ (creates HtmlDiv container)
    ↓ (creates HtmlA links via createStyledLink())
    ↓ (applies HtmlAttribute styling)
    ↓
Returns HTML string
    ↓
Echo to output
```

### GL Posting Flow (FA Only)
```
User selects payments in Reports View
    ↓
Controller calls FAJournalService
    ↓
FAJournalService.postPayment()
    ↓ (reads ksf_gl_mappings)
    ↓ (creates debit/credit entries)
    ↓
FA GL Tables (gl_trans)
    ↓ (updates staging records)
Database (ksf_amortization_staging: posted=1)
```

---

## Database Architecture

See [DATABASE_ERD.md](./DATABASE_ERD.md) for comprehensive entity relationship diagram.

**Core Tables:**
- `ksf_loans_summary` - Primary loan data
- `ksf_amortization_staging` - Payment schedule (with GL posting flags)
- `ksf_selectors` - Lookup values (loan types, frequencies, etc.)
- `ksf_gl_mappings` - FA GL account mappings

**Relationships:**
- Staging records CASCADE DELETE with loans
- Selectors provide lookup values for loan fields
- GL mappings link to FA chart_master

---

## Testing Strategy

### Unit Tests
Test individual components in isolation.

**Coverage:**
- `AmortizationCalculatorTest` - Payment calculations
- `InterestCalculatorTest` - Interest calculation methods
- `SelectorRepositoryTest` - CRUD operations
- `LoanOriginationServiceTest` - Loan creation workflows

### Integration Tests
Test component interactions.

**Coverage:**
- `FADataProviderTest` - Database operations
- `FAJournalServiceTest` - GL posting
- `ViewRefactoringTests` - View architectural compliance

### Runtime Validation Tests (NEW)
Test that dependencies exist at runtime.

**Coverage:**
- `ViewDependencyTest` - Validates use statements reference existing classes
- Enhanced `FAControllerTest` - Verifies included files don't have undefined dependencies

### User Acceptance Tests (UAT)
Manual test scripts for all user-facing functionality.

**Location:** `tests/UAT.md`

---

## Security Considerations

### Input Validation
- All user inputs sanitized before database insertion
- Type checking on numeric fields (interest rate, principal)
- Date validation for payment schedules

### Access Control
- FA: Uses FrontAccounting permission system
- Controller checks for page() function availability before rendering FA UI
- GL posting restricted to authorized users

### SQL Injection Prevention
- All queries use prepared statements via PDO
- Repository pattern enforces parameterized queries
- No raw SQL concatenation in application code

### XSS Prevention
- HTML builder pattern escapes output automatically
- Use of htmlspecialchars() where raw HTML unavoidable
- Content Security Policy recommended for production

---

## Performance Optimization

### Caching Strategy
- Selector values cached in-memory during request
- Schedule calculations cached until loan parameters change
- Consider Redis/Memcached for multi-user scenarios

### Database Optimization
- Indexes on frequently queried columns (borrower_id, status, posted)
- Composite index on (loan_id, payment_date) for staging queries
- Consider partitioning for large staging tables

### Query Optimization
- Batch inserts for staging records (hundreds of periods)
- Eager loading of related data (JOIN vs N+1 queries)
- LIMIT clauses on large result sets

---

## Extensibility & Future Enhancements

### Adding New Platforms
1. Implement `DataProviderInterface`
2. Create platform-specific adapter directory
3. Add platform detection in controller
4. Reuse existing views or create platform-specific variants

### Adding New Loan Types
1. Add record to `ksf_selectors` (selector_name='loan_type')
2. No code changes required (driven by database)
3. Optional: Add specialized calculation logic in AmortizationCalculator

### Adding New Interest Calculation Methods
1. Extend `InterestCalculator` with new method
2. Add selector value for new frequency type
3. Update schedule generation to support new method

### Multi-Currency Support
1. Add `currency_code` to ksf_loans_summary
2. Integrate with FA/WP currency tables
3. Update reports to format currency appropriately

---

## Deployment Architecture

### Production Environment (FrontAccounting)
```
Web Server (Apache/Nginx)
    ↓
PHP 7.3+ Runtime
    ↓
FrontAccounting 2.4.x
    ↓ (includes amortization module)
modules/amortization/
    ├── controller.php (entry point)
    ├── MenuBuilder.php
    ├── views/
    └── Repository/
    ↓
Composer Dependencies
    ├── vendor/ksfraser/html (HTML builders)
    └── PHPUnit (dev only)
    ↓
MySQL/MariaDB Database
    ├── FA tables (0_*)
    └── Amortization tables (0_ksf_*)
```

### Development Environment
- Local PHP 7.3 with Xdebug
- SQLite for unit tests (in-memory)
- PHPUnit 9.6.x for testing
- Git submodules for modular development

---

## Documentation Standards

### Code Documentation
- PHPDoc blocks on all classes, methods, properties
- Inline comments for complex business logic
- Type hints on all method parameters and return values

### Architectural Documentation
- This Architecture.md (high-level overview)
- DATABASE_ERD.md (database schema details)
- Design/ folder (detailed design documents)
- PhaseProgress/ folder (implementation history)

### Testing Documentation
- tests/README.md (test execution guide)
- tests/UAT.md (user acceptance test scripts)
- Test method docblocks explain what is tested

---

**Document Version:** 2.0  
**Last Updated:** 2025-12-25  
**Phase:** 17 - FA Controller Integration Complete  
**Status:** Production Ready
