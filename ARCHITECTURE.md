# KSF Amortization - Architecture Guide

## System Overview

KSF Amortization is a unified monorepo combining the core amortization engine, API layer, platform adapters, and frontend UI. All components share the same database schema and business logic.

```
┌─────────────────────────────────────────────────────┐
│         Frontend (Vue 3 SPA)                        │
│  - Admin Dashboard                                  │
│  - User Portal                                      │
│  - Real-time Notifications                          │
└────────────────┬────────────────────────────────────┘
                 │ HTTPS/REST API
┌────────────────▼────────────────────────────────────┐
│         API Layer (Ksfraser\Api)                    │
│  - Route Matching & Controllers                     │
│  - Authentication & Authorization                   │
│  - Request Validation                               │
│  - Response Formatting                              │
│  - Error Handling                                   │
└────────────────┬────────────────────────────────────┘
                 │ Services
┌────────────────▼────────────────────────────────────┐
│    Core Business Logic (Ksfraser\Amortizations)    │
│  ┌──────────────────────────────────────────────────┤
│  │  Calculators        Compliance   Models          │
│  │  ├─ Interest       ├─ Rules      ├─ Loan        │
│  │  ├─ Payment        ├─ Validation ├─ Client      │
│  │  ├─ Schedule       └─ Auditing   └─ Payment     │
│  │  ├─ Effective Rate                               │
│  │  └─ Compound Rates                               │
│  ├──────────────────────────────────────────────────┤
│  │  Database           Caching      Utils           │
│  │  ├─ Persistence    ├─ Query      ├─ Numbers     │
│  │  ├─ Repositories   ├─ Results    └─ Formatting  │
│  │  └─ Migrations     └─ Schedules                 │
│  └──────────────────────────────────────────────────┤
└────────────────┬────────────────────────────────────┘
                 │ Database
        ┌────────▼────────┐
        │   FrontEnd DB   │
        │   (MySQL/5.7+)  │
        └─────────────────┘
```

## Directory Structure

```
ksf_amortization/
├── src/
│   └── Ksfraser/
│       ├── Amortizations/          # Core business logic
│       │   ├── Analytics/          # Usage metrics & reporting
│       │   ├── Calculators/        # Interest, payment, schedule calcs
│       │   ├── Compliance/         # Rules, validation, auditing
│       │   ├── Database/           # Query builders, migrations
│       │   ├── Exceptions/         # Custom exception classes
│       │   ├── Models/             # Domain entities (Loan, Client, etc)
│       │   ├── Persistence/        # Repository pattern
│       │   ├── Reports/            # Report generation
│       │   ├── Services/           # Business service layer
│       │   ├── Strategies/         # Pluggable calculation strategies
│       │   ├── Utils/              # Helper functions
│       │   ├── Views/              # View helpers
│       │   └── Handlers/           # Event handlers
│       │
│       └── Api/                    # REST API Layer
│           ├── index.php           # API entry point
│           ├── routes.php          # Route definitions
│           ├── Router.php          # HTTP router
│           ├── Bootstrap.php       # Initialization
│           └── Controllers/        # Request handlers
│               ├── AuthController.php
│               ├── ClientController.php
│               ├── MetricsController.php
│               ├── HealthController.php
│               └── BaseController.php
│
├── modules/
│   └── amortization/              # FrontAccounting Module
│       ├── controller.php         # FA integration
│       ├── hooks.php              # FA hooks
│       ├── views/                 # FA templates
│       └── tests/                 # FA-specific tests
│
├── frontend/                      # Vue 3 SPA
│   ├── src/
│   │   ├── pages/                # Page components
│   │   ├── components/           # Reusable components
│   │   ├── stores/               # Pinia state management
│   │   ├── router/               # Vue Router config
│   │   └── App.vue
│   ├── tests/                    # Vitest test suites
│   ├── vitest.config.js
│   └── package.json
│
├── tests/                        # PHP integration tests
│   ├── Unit/
│   ├── Integration/
│   └── Fixtures/
│
├── ansible/                      # Deployment automation
│   ├── deploy-webserver.yml
│   ├── deploy-frontaccounting-container.yml
│   └── roles/
│       ├── webserver/
│       └── frontaccounting/
│
├── migrations/                   # Database migrations
│   ├── migration_20251216_001_query_optimization_indexes.sql
│   └── migration_20251216_002_denormalized_interest.sql
│
└── composer.json / package.json  # Dependencies
```

## Core Components

### 1. Calculators (`src/Ksfraser/Amortizations/Calculators/`)

Specialized calculation engines:

- **InterestCalculator**: Handles daily, monthly, and custom interest calculations
- **PaymentCalculator**: Computes periodic payments and balloon amounts
- **ScheduleCalculator**: Generates full amortization schedules
- **EffectiveRateCalculator**: Calculates APR and effective rates
- **CompoundRateCalculator**: Advanced compound interest scenarios

**Usage Pattern:**
```php
use Ksfraser\Amortizations\Calculators\ScheduleCalculator;

$calculator = new ScheduleCalculator($loan);
$schedule = $calculator->generate();
```

### 2. Models (`src/Ksfraser/Amortizations/Models/`)

Domain entities:

- **Loan**: Loan details (amount, term, rate, frequency)
- **Client**: Client/customer information
- **Payment**: Individual payment records
- **PaymentSchedule**: Collection of payments with metadata
- **Interest**: Interest calculation details
- **Compliance**: Compliance record tracking

### 3. Repositories (`src/Ksfraser/Amortizations/Persistence/`)

Data access layer using Repository pattern:

- **LoanRepository**: Loan CRUD & queries
- **ClientRepository**: Client CRUD & queries
- **PaymentRepository**: Payment persistence
- **ScheduleRepository**: Schedule caching and retrieval

**Usage:**
```php
use Ksfraser\Amortizations\Persistence\LoanRepository;

$repo = new LoanRepository($db);
$loan = $repo->findById($loanId);
```

### 4. Services (`src/Ksfraser/Amortizations/Services/`)

Business logic orchestration:

- **LoanService**: High-level loan operations
- **CalculationService**: Schedule generation + caching
- **ReportService**: Report generation
- **ComplianceService**: Auditing and rule validation
- **NotificationService**: Event-based notifications

### 5. API Layer (`src/Ksfraser/Api/`)

REST API controllers mapped to core services:

- **Routes** (40+):
  - `POST /api/loans` - Create loan
  - `GET /api/loans/{id}` - Fetch loan
  - `POST /api/loans/{id}/schedule` - Generate schedule
  - `GET /api/metrics` - Usage metrics
  - etc.

- **Authentication**: Middleware-based (API keys, OAuth2)
- **Validation**: Auto-validation before controller methods
- **Responses**: Standardized JSON format with metadata

### 6. Frontend (`frontend/`)

Vue 3 SPA with:

- **Pages**: Loan forms, admin dashboards, user portals
- **Components**: Reusable UI elements (forms, tables, charts)
- **Stores**: Pinia state management for client/loan data
- **Router**: Client-side routing with layout management
- **Tests**: Vitest unit and component tests

## Data Flow

### Typical Request Flow

```
1. HTTP Request
   ↓
2. API Router matches route → selects Controller method
   ↓
3. BaseController validates request data
   ↓
4. Service layer processes business logic
   ↓
5. Repositories query/update database
   ↓
6. Response formatted as JSON
   ↓
7. HTTP Response
```

### Example: Create Loan Schedule

```
POST /api/loans/{id}/schedule
  ↓
LoanController::generateSchedule()
  ↓
validates loanId, fetches Loan from repository
  ↓
CalculationService::generateSchedule($loan)
  ↓
ScheduleCalculator::generate()
  ↓
InterestCalculator, PaymentCalculator invoked
  ↓
ScheduleRepository::cache($schedule)
  ↓
return Schedule JSON response
```

## Key Design Patterns

### 1. Repository Pattern
Abstracts database access behind repository interfaces. Easy to test with mocks.

### 2. Service Layer
Business logic resides in services, not controllers. Reusable across platforms.

### 3. Strategy Pattern
Different calculation strategies (daily compounding, monthly, etc) pluggable.

### 4. Observer Pattern
Event system for notifications, compliance auditing, analytics.

### 5. Dependency Injection
Constructor injection for testability and loose coupling.

## Namespacing

All code uses PSR-4 namespacing:

- `Ksfraser\Amortizations\*` - Core business logic
- `Ksfraser\Api\*` - API controllers and routing
- `Test\Unit\*` - Unit tests
- `Test\Integration\*` - Integration tests

## Performance Considerations

### Caching
- Query result caching to prevent N+1 queries
- Schedule caching to avoid recalculation
- Response caching for metrics endpoints

### Database
- Indexed queries for common lookups
- Pagination for large result sets
- Denormalized interest fields for reporting

### Frontend
- Code splitting and lazy loading
- Component-level caching
- Debounced form submissions

## Security

- API key or OAuth2 authentication
- CSRF protection on state-changing requests
- Input validation on all endpoints
- SQL injection prevention via parameterized queries
- XSS protection via Vue auto-escaping

## Testing Strategy

### Backend
- Unit tests: Core calculators, repositories (PHPUnit)
- Integration tests: Service layer with mock database
- Test database isolation with transactions

### Frontend
- Component tests: Individual components (Vitest)
- Unit tests: Utility functions, stores
- E2E tests: Critical user flows (Playwright)

## Deployment Architecture

### Local Development
- PHP built-in server + SQLite
- Hot reload for frontend (npm run dev)

### Staging
- nginx + PHP-FPM + MySQL 5.7+
- SSL certificates via Let's Encrypt
- Deployed via Ansible

### Production FA
- FrontAccounting container (Docker Compose)
- Nginx reverse proxy
- Managed database with automated backups
