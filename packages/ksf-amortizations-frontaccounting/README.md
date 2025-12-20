# KSF Amortizations - FrontAccounting Module

## Description

FrontAccounting amortization module providing loan management and GL posting integration.

## Installation

### Prerequisites
- FrontAccounting 2.4+
- PHP 7.4+
- Composer

### Step-by-Step

1. **Install via Composer (recommended)**
   ```bash
   cd /path/to/frontaccounting
   composer require ksfraser/amortizations-core
   composer require ksfraser/amortizations-frontaccounting
   ```

2. **Copy module files to FrontAccounting**
   ```bash
   cp -r vendor/ksfraser/amortizations-frontaccounting/module/amortization ./modules/
   ```

3. **Initialize Database**
   ```bash
   # Via FrontAccounting admin interface
   # Setup → System Setup → Modules → Amortizations → Install
   ```

4. **Activate Module**
   - Log into FrontAccounting as administrator
   - Go to: Setup → System Setup → Modules
   - Enable "Amortizations" module

5. **Configure**
   - Setup GL account mappings
   - Configure payment posting behavior
   - Set selector options (payment frequencies, loan types, etc.)

## Module Structure

```
module/amortization/
├── hooks.php                   # FA module hooks
├── controller.php              # Request router
├── model.php                   # Data access layer
├── staging_model.php           # Payment staging table
├── reporting.php               # Reporting functions
├── views/                      # View templates
│   ├── admin_settings.php      # Admin configuration
│   ├── admin_selectors.php     # Selector management
│   ├── user_loan_setup.php     # User loan interface
│   └── ...
├── _init/                      # Module initialization
└── schema.sql                  # Database schema

src/Ksfraser/Amortizations/FA/
├── FAJournalService.php        # GL posting service
├── FADataProvider.php          # FA database adapter
├── GLPostingService.php        # GL posting orchestration
├── AmortizationGLController.php # High-level facade
├── GLAccountMapper.php         # GL account mapping
└── JournalEntryBuilder.php     # Journal entry creation
```

## Platform-Specific Files

- **FAJournalService** - FrontAccounting GL journal entry creation
- **FADataProvider** - FrontAccounting database queries
- **GLPostingService** - Batch GL posting orchestration
- **AmortizationGLController** - High-level API for GL operations

## Usage

```php
use Ksfraser\Amortizations\FA\FADataProvider;
use Ksfraser\Amortizations\AmortizationModel;
use Ksfraser\Amortizations\FA\AmortizationGLController;

// Initialize services
$pdo = new PDO('mysql:host=localhost;dbname=frontaccounting', $user, $pass);

// Get FrontAccounting table prefix (TB_PREF is defined by FA, typically '0_')
$dbPrefix = defined('TB_PREF') ? TB_PREF : '0_';

$dataProvider = new FADataProvider($pdo, $dbPrefix);
$amortizationModel = new AmortizationModel($dataProvider);
$glController = new AmortizationGLController($amortizationModel, $glPostingService, $dataProvider);

// Create loan and post GL entries
$result = $glController->createLoanAndPostSchedule(
    loanId: 123,
    glAccountNumber: '1200',
    dimension1: 0,
    dimension2: 0
);
```

## Testing

```bash
composer test
```

## Features

- ✅ Flexible amortization calculations
- ✅ Automatic GL posting to FrontAccounting
- ✅ Batch payment processing
- ✅ Extra payment handling with GL reversal
- ✅ Payment staging for review
- ✅ Admin interface for configuration
- ✅ User interface for loan setup and review
- ✅ Full integration with FA GL module

## License

MIT
