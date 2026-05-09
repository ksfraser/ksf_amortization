# Installation & Deployment Guide - FrontAccounting Module

## Quick Start

The module automatically handles composer installation during FA module activation.

### 1. Copy Module to FrontAccounting

```bash
# Copy the module to your FA installation modules directory
cp -r . /path/to/frontaccounting/modules/amortization/

# Or for Windows:
xcopy . C:\path\to\frontaccounting\modules\amortization\ /E /I /Y
```

### 2. Activate Module in FrontAccounting

1. Login to FrontAccounting as Administrator
2. Navigate to **Administration → Modules & Packages**
3. Find "Amortization" in the list
4. Click **Install** button

The module will automatically:
- Run `composer install` to fetch core dependencies
- Initialize database schema
- Register menu items
- Set up GL accounts

### 3. Verify Installation

```bash
# Test dependencies are installed
php -r "require 'vendor/autoload.php'; echo 'OK';"

# Run tests
composer test
```

## What Happens During Installation

The `hooks.php` install() method:

1. **Checks Composer** - Verifies if dependencies are installed
2. **Auto-runs Composer** - If `vendor/` missing, automatically runs `composer install`
3. **Loads Autoloader** - Includes generated autoload.php
4. **Initializes Database** - Runs AmortizationModuleInstaller
5. **Registers Menus** - Adds module items to FA

## Manual Installation

If automatic installation fails:

```bash
cd modules/amortization/
composer install --no-dev
```

## Troubleshooting

### "Composer command not found"
- Ensure composer is installed: `composer --version`
- Add composer to system PATH
- Fallback: Manually run `composer install` in module directory

### "vendor/autoload.php not found"
- Check composer.json is valid
- Verify internet connection to download packages
- Check disk space and file permissions
- Review FA error logs

### "Module not showing in FrontAccounting"
- Verify files are in correct location
- Check module_name in hooks.php
- Ensure database installer completed

## Testing

```bash
# Run module tests
composer test

# Run full test suite (from main repo)
cd ../.. && composer test-fa
```

---

**Version:** 1.0.0  
**Last Updated:** December 23, 2025
