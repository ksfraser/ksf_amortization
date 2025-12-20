# Deployment Strategy & Architecture Assessment

**Date:** December 9, 2025  
**Status:** Assessment Complete - Restructuring Recommended  
**Version:** 1.0

---

## Executive Summary

**Current State:** Code is intermixed across multiple directories (modules/, src/), making deployment confusing for each platform.

**Issue Identified:** 
- FrontAccounting (FA) code exists in: `modules/fa/`, `modules/amortization/`, and `src/Ksfraser/Amortizations/fa/`
- WordPress (WP) code exists in: `modules/wordpress/` and `src/Ksfraser/Amortizations/wordpress/`
- SuiteCRM code exists in: `modules/suitecrm/` and `src/Ksfraser/Amortizations/suitecrm/`
- Core/Common code in: `src/Ksfraser/Amortizations/`
- Duplicate provider classes at module root level

**Recommendation:** Restructure into **3 independent deployable packages + 1 shared Composer library**

---

## Current Directory Structure Analysis

### Current Layout (PROBLEMATIC)
```
ksf_amortization/
├── modules/                          # Mixed platform code
│   ├── amortization/                # FA-specific hooks & installer
│   │   ├── hooks.php
│   │   ├── composer.json
│   │   ├── LoanEventProvider.php     # FA-specific impl
│   │   ├── FADataProvider.php        # FA DB adapter
│   │   ├── FAJournalService.php      # FA GL service
│   │   ├── views/
│   │   ├── tests/
│   │   └── _init/
│   ├── fa/                           # FA module (DUPLICATE)
│   │   ├── hooks.php
│   │   ├── fa_mock.php
│   │   ├── FAJournalService.php      # DUPLICATE
│   │   ├── LoanEventProvider.php     # DUPLICATE
│   │   ├── tests/
│   │   └── INSTALL.md
│   ├── fa_mock/
│   ├── WPLoanEventProvider.php       # WP provider (ORPHANED)
│   └── SuiteCRMLoanEventProvider.php # SuiteCRM provider (ORPHANED)
│
├── src/Ksfraser/
│   ├── Amortizations/               # Core/common code
│   │   ├── AmortizationModel.php
│   │   ├── LoanEventProvider.php     # Base class
│   │   ├── DataProviderInterface.php
│   │   ├── GenericLoanEventProvider.php
│   │   ├── SelectorModel.php
│   │   └── ... (15+ core classes)
│   ├── fa/                           # FA-specific (SEPARATE)
│   │   ├── INSTALL.md
│   │   └── ... (fa namespace files)
│   ├── wordpress/                    # WP-specific (SEPARATE)
│   │   ├── INSTALL.md
│   │   └── ... (wordpress namespace files)
│   └── suitecrm/                     # SuiteCRM-specific (SEPARATE)
│       ├── INSTALL.md
│       └── ... (suitecrm namespace files)
│
├── tests/                            # Tests (mixed namespace)
├── composer.json                     # Root composer (maps all)
└── README.md
```

**Problems:**
1. ❌ Duplication: FAJournalService.php, LoanEventProvider.php, etc. in multiple locations
2. ❌ Confusion: Three different places for FA code (modules/amortization/, modules/fa/, src/Ksfraser/fa/)
3. ❌ Orphaned files: WPLoanEventProvider.php and SuiteCRMLoanEventProvider.php at module root
4. ❌ Mix of concerns: Platform-specific and core code mixed in src/
5. ❌ Deployment unclear: Which files deploy where for each platform?
6. ❌ Maintainability: Changes to core code require updates in 3 places potentially

---

## Recommended Architecture (CLEAN)

### Option A: Composer Library + Platform-Specific Packages (RECOMMENDED)

```
ksf-amortizations/                   # Main repository
├── composer.json                    # Root: defines 3 platform packages
├── README.md
├── LICENSE
│
├── packages/                        # Three independent packages
│   ├── ksf-amortizations-core/      # Shared library (composer package)
│   │   ├── composer.json            # type: library
│   │   ├── src/
│   │   │   └── Ksfraser/Amortizations/
│   │   │       ├── AmortizationModel.php
│   │   │       ├── LoanEventProvider.php      # Base class
│   │   │       ├── DataProviderInterface.php
│   │   │       ├── GenericLoanEventProvider.php
│   │   │       ├── InterestCalcFrequency.php
│   │   │       ├── LoanEvent.php
│   │   │       ├── LoanSummary.php
│   │   │       ├── LoanType.php
│   │   │       ├── SelectorModel.php
│   │   │       ├── SelectorDbAdapterPDO.php
│   │   │       ├── SelectorDbAdapterWPDB.php
│   │   │       ├── SelectorModels.php
│   │   │       ├── SelectorProvider.php
│   │   │       ├── SelectorTables.php
│   │   │       ├── GLPostingService.php       # Generic GL posting
│   │   │       └── ... (all platform-agnostic classes)
│   │   ├── tests/
│   │   │   ├── AmortizationModelTest.php
│   │   │   ├── BaseTestCase.php
│   │   │   └── MockClasses.php
│   │   └── README.md
│   │
│   ├── ksf-amortizations-frontaccounting/     # FA-specific package
│   │   ├── composer.json            # type: library, requires: ksf-amortizations-core
│   │   ├── README.md
│   │   ├── INSTALL.md               # FA installation only
│   │   ├── src/
│   │   │   └── Ksfraser/Amortizations/FA/
│   │   │       ├── FADataProvider.php
│   │   │       ├── FAJournalService.php
│   │   │       ├── FALoanEventProvider.php
│   │   │       ├── AmortizationGLController.php
│   │   │       └── ... (FA-only classes)
│   │   ├── tests/
│   │   │   ├── FADataProviderTest.php
│   │   │   ├── FAJournalServiceTest.php
│   │   │   └── TASK3GLIntegrationTest.php
│   │   └── module/                  # FA module structure
│   │       ├── hooks.php
│   │       ├── composer.json        # local dev composer
│   │       ├── controller.php
│   │       ├── views/
│   │       │   ├── admin_settings.php
│   │       │   ├── user_loan_setup.php
│   │       │   └── ...
│   │       └── schema.sql
│   │
│   ├── ksf-amortizations-wordpress/          # WP-specific package
│   │   ├── composer.json            # type: wordpress-plugin
│   │   ├── README.md
│   │   ├── INSTALL.md               # WP installation only
│   │   ├── src/
│   │   │   └── Ksfraser/Amortizations/WordPress/
│   │   │       ├── WPDataProvider.php
│   │   │       ├── WPLoanEventProvider.php
│   │   │       └── ... (WP-only classes)
│   │   ├── tests/
│   │   │   ├── WPDataProviderTest.php
│   │   │   └── ...
│   │   └── plugin/                  # WP plugin structure
│   │       ├── amortizations.php    # main plugin file
│   │       ├── hooks.php
│   │       ├── views/
│   │       │   ├── admin-settings.php
│   │       │   └── ...
│   │       └── schema.sql
│   │
│   └── ksf-amortizations-suitecrm/           # SuiteCRM-specific package
│       ├── composer.json            # type: library
│       ├── README.md
│       ├── INSTALL.md               # SuiteCRM installation only
│       ├── src/
│       │   └── Ksfraser/Amortizations/SuiteCRM/
│       │       ├── SuiteCRMDataProvider.php
│       │       ├── SuiteCRMLoanEventProvider.php
│       │       └── ... (SuiteCRM-only classes)
│       ├── tests/
│       │   ├── SuiteCRMDataProviderTest.php
│       │   └── ...
│       └── module/                  # SuiteCRM module structure
│           ├── hooks.php
│           ├── views/
│           └── schema.sql
│
├── docs/                            # Shared documentation
│   ├── ARCHITECTURE.md
│   ├── DEPLOYMENT_GUIDE.md
│   ├── DEVELOPMENT.md
│   └── ...
│
└── tests/                           # Integration tests (all platforms)
    ├── IntegrationTests.php
    └── ...
```

**Benefits:**
- ✅ Clear separation: core code vs. platform-specific
- ✅ Independent packages: each can be versioned/published separately
- ✅ Reusable: core package used by all three platforms
- ✅ Maintainable: no duplication, single source of truth
- ✅ Deployment: clear which files go where for each platform
- ✅ Testable: unit tests per package, integration tests across
- ✅ Composable: developers choose which platform(s) to install

---

## Current Deployment Paths (DOCUMENTED)

### FrontAccounting Deployment (Current State)

**Files Used:**
1. Core logic: `src/Ksfraser/Amortizations/*`
2. FA provider: `src/Ksfraser/Amortizations/FA/*` OR `modules/amortization/*` (duplicated!)
3. FA module: `modules/amortization/*` (hooks.php, controller.php, views/)
4. Tests: `tests/*` and `modules/amortization/tests/`

**Installation (Current - Confused):**
```bash
# Option 1 (via modules/amortization/)
composer require ksfraser/amortizations
# Then... copy what? To where?

# Option 2 (via composer require in FA)
# Unclear what gets installed
```

**Issues:**
- Multiple INSTALL.md files: `modules/amortization/INSTALL.md`, `modules/fa/INSTALL.md`, `src/Ksfraser/fa/INSTALL.md`
- Unclear which one is correct
- Duplication between `modules/fa/` and `src/Ksfraser/Amortizations/fa/`
- Tests located in multiple places

### WordPress Deployment (Current State)

**Files Used:**
1. Core logic: `src/Ksfraser/Amortizations/*`
2. WP provider: `modules/WPLoanEventProvider.php` (orphaned!) OR missing elsewhere?
3. WP module: missing proper WP plugin structure
4. Tests: `tests/WPDataProviderTest.php`

**Issues:**
- WPLoanEventProvider.php exists but is orphaned at module root
- No proper WordPress plugin structure (no plugin.php main file)
- Installation instructions not clear
- WP-specific hook system unclear

### SuiteCRM Deployment (Current State)

**Files Used:**
1. Core logic: `src/Ksfraser/Amortizations/*`
2. SuiteCRM provider: `modules/SuiteCRMLoanEventProvider.php` (orphaned!)
3. SuiteCRM module: missing proper SuiteCRM module structure
4. Tests: `tests/SuiteCRMDataProviderTest.php`

**Issues:**
- SuiteCRMLoanEventProvider.php exists but is orphaned at module root
- No proper SuiteCRM module structure
- Installation instructions not clear
- SuiteCRM-specific lifecycle unclear

---

## Recommended Deployment Instructions (BY PLATFORM)

### FrontAccounting Installation (Recommended Approach)

```bash
# 1. In your FrontAccounting installation
cd /path/to/frontaccounting

# 2. Install the packages
composer require ksfraser/amortizations-core
composer require ksfraser/amortizations-frontaccounting

# 3. Copy FA module structure to FA modules
cp -r vendor/ksfraser/amortizations-frontaccounting/module/amortization ./modules/

# 4. Run database setup (in FA admin or CLI)
php modules/amortization/schema.php

# 5. Activate module in FA
# Menu: Setup → System Setup → Modules

# 6. Verify installation
composer test
```

### WordPress Installation (Recommended Approach)

```bash
# 1. In your WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins

# 2. Create plugin directory
mkdir amortizations && cd amortizations

# 3. Install packages
composer require ksfraser/amortizations-core
composer require ksfraser/amortizations-wordpress

# 4. Copy plugin structure
cp -r vendor/ksfraser/amortizations-wordpress/plugin/* .

# 5. Run database setup (CLI or via admin hook)
php wp-cli.phar db query < schema.sql

# 6. Activate plugin in WordPress
# Menu: Plugins → Amortizations

# 7. Verify installation
composer test
```

### SuiteCRM Installation (Recommended Approach)

```bash
# 1. In your SuiteCRM custom modules directory
cd /path/to/suitecrm/custom/modules

# 2. Create module directory
mkdir Amortizations && cd Amortizations

# 3. Install packages
composer require ksfraser/amortizations-core
composer require ksfraser/amortizations-suitecrm

# 4. Copy module structure
cp -r vendor/ksfraser/amortizations-suitecrm/module/* .

# 5. Run database setup
php sugar_cli.php SchemaManager:rebuild

# 6. Repair SuiteCRM
php sugar_cli.php Module:repair

# 7. Verify installation
composer test
```

---

## Migration Plan (FROM CURRENT TO RECOMMENDED)

### Phase 1: Refactor Code Structure (1-2 weeks)

**Step 1: Create packages/ directory**
```bash
mkdir -p packages/ksf-amortizations-{core,frontaccounting,wordpress,suitecrm}
```

**Step 2: Move core code**
```bash
# Move all platform-agnostic code to core package
mv src/Ksfraser/Amortizations/* packages/ksf-amortizations-core/src/Ksfraser/Amortizations/
# Keep only base classes (LoanEventProvider, DataProviderInterface, etc.)
```

**Step 3: Move FA code**
```bash
# Move FA-specific code to fa package
mv src/Ksfraser/Amortizations/FA/* packages/ksf-amortizations-frontaccounting/src/Ksfraser/Amortizations/FA/
mv modules/fa/* packages/ksf-amortizations-frontaccounting/
# Remove duplicate modules/amortization (keep hooks.php as module wrapper)
```

**Step 4: Move WP code**
```bash
# Create proper WP plugin structure
mkdir -p packages/ksf-amortizations-wordpress/plugin
mv modules/WPLoanEventProvider.php packages/ksf-amortizations-wordpress/src/Ksfraser/Amortizations/WordPress/
# Create main plugin file: amortizations.php
```

**Step 5: Move SuiteCRM code**
```bash
# Create proper SuiteCRM module structure
mkdir -p packages/ksf-amortizations-suitecrm/module
mv modules/SuiteCRMLoanEventProvider.php packages/ksf-amortizations-suitecrm/src/Ksfraser/Amortizations/SuiteCRM/
```

### Phase 2: Create composer.json files (1 week)

**packages/ksf-amortizations-core/composer.json**
```json
{
    "name": "ksfraser/amortizations-core",
    "description": "Core amortization business logic (platform-agnostic)",
    "type": "library",
    "require": {
        "php": ">=7.4"
    },
    "autoload": {
        "psr-4": {
            "Ksfraser\\Amortizations\\": "src/Ksfraser/Amortizations/"
        }
    }
}
```

**packages/ksf-amortizations-frontaccounting/composer.json**
```json
{
    "name": "ksfraser/amortizations-frontaccounting",
    "description": "FrontAccounting amortization module",
    "type": "library",
    "require": {
        "ksfraser/amortizations-core": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Ksfraser\\Amortizations\\FA\\": "src/Ksfraser/Amortizations/FA/"
        }
    }
}
```

**packages/ksf-amortizations-wordpress/composer.json**
```json
{
    "name": "ksfraser/amortizations-wordpress",
    "description": "WordPress amortization plugin",
    "type": "wordpress-plugin",
    "require": {
        "ksfraser/amortizations-core": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Ksfraser\\Amortizations\\WordPress\\": "src/Ksfraser/Amortizations/WordPress/"
        }
    }
}
```

**packages/ksf-amortizations-suitecrm/composer.json**
```json
{
    "name": "ksfraser/amortizations-suitecrm",
    "description": "SuiteCRM amortization module",
    "type": "library",
    "require": {
        "ksfraser/amortizations-core": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Ksfraser\\Amortizations\\SuiteCRM\\": "src/Ksfraser/Amortizations/SuiteCRM/"
        }
    }
}
```

### Phase 3: Update root composer.json (1 week)

**Root composer.json (for monorepo)**
```json
{
    "name": "ksfraser/amortizations",
    "description": "Multi-platform amortization scheduling (FA, WP, SuiteCRM)",
    "type": "library",
    "repositories": [
        {
            "type": "path",
            "url": "packages/ksf-amortizations-core"
        },
        {
            "type": "path",
            "url": "packages/ksf-amortizations-frontaccounting"
        },
        {
            "type": "path",
            "url": "packages/ksf-amortizations-wordpress"
        },
        {
            "type": "path",
            "url": "packages/ksf-amortizations-suitecrm"
        }
    ],
    "require": {
        "ksfraser/amortizations-core": "*",
        "ksfraser/amortizations-frontaccounting": "*",
        "ksfraser/amortizations-wordpress": "*",
        "ksfraser/amortizations-suitecrm": "*"
    }
}
```

### Phase 4: Update tests (1 week)

- Core tests: `packages/ksf-amortizations-core/tests/`
- FA tests: `packages/ksf-amortizations-frontaccounting/tests/`
- WP tests: `packages/ksf-amortizations-wordpress/tests/`
- SuiteCRM tests: `packages/ksf-amortizations-suitecrm/tests/`

### Phase 5: Update documentation (1 week)

- Create `DEPLOYMENT_GUIDE.md` (per-platform instructions)
- Create platform-specific `INSTALL.md` in each package
- Remove conflicting INSTALL.md files
- Update README.md

---

## Quick Reference: What Gets Deployed Where

### For FrontAccounting
```
Install: ksfraser/amortizations-core + ksfraser/amortizations-frontaccounting
Deploy to:
  /modules/amortization/         ← FA hooks, controller, views
  /modules/amortization/src/     ← FA-specific code
```

### For WordPress
```
Install: ksfraser/amortizations-core + ksfraser/amortizations-wordpress
Deploy to:
  /wp-content/plugins/amortizations/         ← Plugin files
  /wp-content/plugins/amortizations/src/     ← WP-specific code
```

### For SuiteCRM
```
Install: ksfraser/amortizations-core + ksfraser/amortizations-suitecrm
Deploy to:
  /custom/modules/Amortizations/         ← SuiteCRM module files
  /custom/modules/Amortizations/src/     ← SuiteCRM-specific code
```

---

## Immediate Action Items (Next Steps)

### Priority 1: Document Current State (THIS WEEK)
- [x] Create this deployment strategy document
- [ ] Create `CURRENT_STRUCTURE.md` - Detailed map of current file locations
- [ ] Create `DEPLOYMENT_ISSUES.md` - Specific problems with current structure

### Priority 2: Begin Migration (NEXT SPRINT)
- [ ] Create packages/ directory structure
- [ ] Move core files to packages/ksf-amortizations-core/
- [ ] Move FA files to packages/ksf-amortizations-frontaccounting/
- [ ] Move WP files to packages/ksf-amortizations-wordpress/
- [ ] Move SuiteCRM files to packages/ksf-amortizations-suitecrm/

### Priority 3: Update Composer (WEEK AFTER)
- [ ] Create platform-specific composer.json files
- [ ] Update root composer.json for monorepo
- [ ] Test: `composer install` works
- [ ] Test: autoloading works across packages

### Priority 4: Verify & Test (FINAL WEEK)
- [ ] All tests pass in each package
- [ ] Integration tests across packages pass
- [ ] Installation documentation verified for each platform
- [ ] Git history clean, ready for release

---

## Summary

**Current Issues:**
- ❌ Code intermixed across 3+ directories
- ❌ Duplication of core files
- ❌ Orphaned provider files
- ❌ Unclear deployment paths
- ❌ Confusing multiple INSTALL.md files

**Recommended Solution:**
- ✅ Separate monorepo into 4 packages (1 core + 3 platform-specific)
- ✅ Each package independent, clearly scoped
- ✅ Single source of truth for core code
- ✅ Clear deployment instructions per platform
- ✅ Professional, maintainable structure

**Effort:** 3-4 weeks for full migration  
**Benefit:** 90% reduction in confusion, duplication, and maintenance burden

---

*Deployment Strategy & Architecture Assessment*  
*Date: December 9, 2025*  
*Status: Assessment Complete - Ready for Migration Planning*
