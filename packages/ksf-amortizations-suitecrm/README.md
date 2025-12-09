# KSF Amortizations - SuiteCRM Module

## Description

SuiteCRM amortization module providing loan management and CRM integration.

## Installation

### Prerequisites
- SuiteCRM 7.10+
- PHP 7.4+
- Composer

### Installation Steps

1. **Install via Composer**
   ```bash
   cd /path/to/suitecrm/custom/modules/Amortizations
   composer require ksfraser/amortizations-core
   composer require ksfraser/amortizations-suitecrm
   ```

2. **Deploy module files**
   ```bash
   cp -r vendor/ksfraser/amortizations-suitecrm/module/* .
   ```

3. **Repair SuiteCRM**
   - Go to: Admin → System → Repair
   - Run: Quick Repair & Rebuild

4. **Configure**
   - Go to: Admin → System Settings → Amortizations
   - Set up loan types, payment frequencies, etc.

## Module Structure

```
module/
├── manifest.php                # Module manifest
├── hooks.php                   # SuiteCRM hooks
├── views/                      # Module views
│   ├── module.php              # Module default view
│   ├── setup.php               # Configuration view
│   └── ...
└── (other config files)

src/Ksfraser/Amortizations/SuiteCRM/
├── SuiteCRMLoanEventProvider.php # SuiteCRM data provider
└── (other SuiteCRM-specific classes)
```

## Features

- ✅ Amortization calculations
- ✅ CRM lead/account linking
- ✅ Loan management interface
- ✅ Payment scheduling
- ✅ Activity tracking integration
- ✅ Email notification integration

## Testing

```bash
composer test
```

## License

MIT
