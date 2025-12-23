# Installation & Deployment Guide

## FrontAccounting Module Deployment

### Quick Start

The module automatically handles composer installation during FA module activation. Just follow these steps:

#### 1. Copy Module to FrontAccounting

```bash
# Copy the modules/amortization/ directory to your FA installation
cp -r modules/amortization/ /path/to/frontaccounting/modules/

# Or for Windows:
xcopy modules\amortization\ C:\path\to\frontaccounting\modules\amortization\ /E /I /Y
```

#### 2. Activate Module in FrontAccounting

1. Login to FrontAccounting as Administrator
2. Navigate to **Administration → Modules & Packages**
3. Find "Amortization Module" in the list
4. Click **Install** button
5. The module will automatically:
   - Run `composer install` to download dependencies
   - Initialize the database schema
   - Register menu items
   - Set up GL accounts

#### 3. Verify Installation

```bash
# Test that dependencies are installed
php -r "require 'modules/amortization/vendor/autoload.php'; echo 'OK';"

# Run unit tests
cd modules/amortization/
composer test
```

### What Happens During Installation

The `hooks.php` install() method now:

1. **Checks for Composer** - Verifies composer dependencies are installed
2. **Auto-runs Composer** - If `vendor/` directory is missing, automatically runs `composer install`
3. **Loads Autoloader** - Includes the generated autoload.php
4. **Initializes Database** - Runs AmortizationModuleInstaller to create tables
5. **Registers Menu** - Adds module items to FA menu structure

### Manual Composer Installation

If automatic installation fails, you can manually install dependencies:

```bash
cd modules/amortization/
composer install --no-dev
```

### Deployment Architecture

```
Root (ksf_amortization/)
├── vendor-src/
│   └── ksfraser-html/          ← HTML builder library
├── packages/
│   ├── ksf-amortizations-core/   ← Core logic (reusable)
│   ├── ksf-amortizations-frontaccounting/  ← FA-specific code
│   ├── ksf-amortizations-wordpress/       ← WP-specific code
│   └── ksf-amortizations-suitecrm/        ← SuiteCRM-specific code
└── modules/
    └── amortization/           ← FA Module wrapper
        ├── hooks.php           ← FA lifecycle hooks (NOW HANDLES COMPOSER!)
        ├── composer.json       ← Updated to use local packages
        ├── vendor/             ← Auto-installed on FA module activation
        └── AmortizationModuleInstaller.php
```

### Key Improvements

- ✅ **No Manual Composer Needed** - Automatically runs during FA installation
- ✅ **No Manual Code Copying** - composer.json pulls from local packages
- ✅ **Error Handling** - Clear error messages if composer fails
- ✅ **Dependency Management** - composer.lock ensures reproducible builds
- ✅ **Multi-platform Support** - Same mechanism works for WP, SuiteCRM

### Troubleshooting

#### "Composer command not found"
- Ensure composer is installed: `composer --version`
- Ensure composer is in system PATH
- Fallback: Manually run `cd modules/amortization/ && composer install`

#### "vendor/autoload.php not found after installation"
- Check that composer.json is correct
- Verify internet connection (to download packages)
- Check disk space and file permissions
- Review FA error logs for details

#### "Module not showing in FrontAccounting"
- Verify module files are in correct location
- Check module_name in hooks.php matches directory name
- Ensure database installer completed without errors

### Testing

```bash
# Run module tests
cd modules/amortization/
composer test

# Run root test suite (all modules)
cd ../../
composer test

# Run specific test file
./vendor/bin/phpunit modules/amortization/tests/YourTest.php
```

### Uninstallation

1. In FrontAccounting: Administration → Modules & Packages → Uninstall
2. Delete the modules/amortization/ directory (optional)
3. Tables remain in database for safety (manually delete if needed)

---

**Version:** 1.0.0-complete  
**Last Updated:** December 23, 2025
