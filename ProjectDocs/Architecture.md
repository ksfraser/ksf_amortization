# Architecture Overview

## Generic Business Logic
- Located in `src/Ksfraser/Amortizations/`
- Implements core amortization calculations, loan management, and schedule generation
- Exposes `AmortizationModel` and `DataProviderInterface` for platform integration
- Fully documented with phpdoc and UML
- Unit tested via PHPUnit

## Platform Adaptors

### FrontAccounting (FA)
- Adaptor: `FADataProvider` implements `DataProviderInterface`
- GL integration via `FAJournalService`
- Admin screens for GL mapping and loan management
- Staging table for payment review and posting

### WordPress (WP)
- Adaptor: `WPDataProvider` (to be implemented)
- Integrates with WP database and UI
- Reuses generic business logic and screens

### SuiteCRM
- Adaptor: `SuiteCRMDataProvider` (to be implemented)
- Integrates with SuiteCRM modules and UI
- Reuses generic business logic and screens

## Multi-Platform Design
- All business logic is framework-agnostic
- Views and controllers are designed for easy integration
- Platform-specific code isolated in adaptors/services

## Testing
- Unit tests for all business logic and adaptors
- UAT scripts for every screen, button, and data entry box

## Extensibility
- Add new loan types, platforms, or features by implementing new adaptors or extending the model
