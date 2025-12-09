# KSF Amortizations - Core Library

## Description

Core amortization business logic - platform-agnostic library that provides loan calculation, amortization scheduling, and selector management functionality.

## Installation

```bash
composer require ksfraser/amortizations-core
```

## Features

- Flexible amortization calculations
- Support for multiple interest calculation frequencies
- Extra payment and skip payment handling
- Loan event tracking
- Selector management (dropdown data)
- Database-agnostic adapters for PDO and WordPress

## Core Classes

### Business Logic
- **AmortizationModel** - Main amortization calculations
- **InterestCalcFrequency** - Interest calculation frequencies
- **LoanEvent** - Loan event tracking
- **LoanSummary** - Loan summary calculations
- **LoanType** - Loan type enumeration

### Data Management
- **DataProviderInterface** - Database adapter interface
- **SelectorModel** - Dropdown/selector management
- **SelectorDbAdapterPDO** - PDO database adapter
- **SelectorDbAdapterWPDB** - WordPress database adapter
- **AmortizationModuleInstaller** - Database setup

### Base Classes
- **LoanEventProvider** - Base event provider (abstract)
- **GenericLoanEventProvider** - Generic implementation
- **LoanEventProviderInterface** - Event provider interface

## Usage

```php
use Ksfraser\Amortizations\AmortizationModel;
use Ksfraser\Amortizations\InterestCalcFrequency;

$model = new AmortizationModel($dataProvider);
$frequency = InterestCalcFrequency::MONTHLY;

$schedule = $model->calculateSchedule(
    loanAmount: 100000,
    annualRate: 0.05,
    frequency: $frequency,
    numberOfPayments: 360
);
```

## Testing

```bash
composer test
```

## License

MIT
