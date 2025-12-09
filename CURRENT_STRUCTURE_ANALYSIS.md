# Current Code Structure Analysis

**Date:** December 9, 2025  
**Status:** Problem Documentation Complete  
**Version:** 1.0

---

## Critical Issue Summary

The codebase has **code intermixing and duplication** across multiple locations, making deployment ambiguous for each target system (FA, WordPress, SuiteCRM).

---

## File Location Map (CURRENT STATE)

### ✅ Core Business Logic (Platform-Agnostic)
Located in: `src/Ksfraser/Amortizations/`

**Files:**
```
src/Ksfraser/Amortizations/
├── AmortizationModel.php                  # Core amortization calculations
├── LoanEventProvider.php                  # Base provider (abstract class)
├── GenericLoanEventProvider.php           # Generic implementation
├── DataProviderInterface.php              # DB adapter interface
├── InterestCalcFrequency.php              # Interest frequency calculations
├── LoanEvent.php                          # Event data model
├── LoanSummary.php                        # Summary calculations
├── LoanType.php                           # Loan type enumeration
├── SelectorModel.php                      # Selector/dropdown data
├── SelectorDbAdapterPDO.php               # PDO database adapter
├── SelectorDbAdapterWPDB.php              # WordPress database adapter
├── SelectorModels.php                     # Selector models registry
├── SelectorProvider.php                   # Selector data provider
├── SelectorTables.php                     # Selector table definitions
├── GLPostingService.php                   # GL posting orchestration (GENERIC)
└── ... (14 core classes total)
```

**Status:** ✅ Correct location - should remain here
**Namespace:** `Ksfraser\Amortizations`
**Access:** Used by all platforms

---

### ⚠️ FrontAccounting Code (PROBLEMATIC - 3 LOCATIONS!)

#### Location 1: `modules/amortization/` (MAIN MODULE)
```
modules/amortization/
├── hooks.php                   # FA module hooks & menu registration ✅
├── composer.json               # Local dev composer.json
├── controller.php              # FA request routing
├── model.php                   # FA data access layer
├── admin_selectors_controller.php
├── admin_settings.php
├── reporting.php
├── staging_model.php
├── user_loan_setup.php
├── views/                      # View templates
│   ├── admin_selectors.php
│   ├── admin_settings.php
│   ├── user_loan_setup.php
│   └── ... (5+ views)
├── _init/config                # FA module configuration
├── tests/
│   └── test_amortization.php
└── INSTALL.md
```

**Status:** ⚠️ **FA-specific module code OK here**  
**But includes:**
- FADataProvider.php
- FAJournalService.php
- LoanEventProvider.php (SHOULD NOT BE HERE - duplicated!)

#### Location 2: `modules/fa/` (DUPLICATE COPY)
```
modules/fa/
├── hooks.php                   # DUPLICATE of modules/amortization/
├── fa_mock.php                 # Mock FA data
├── FAJournalService.php        # ⚠️ DUPLICATE!
├── LoanEventProvider.php       # ⚠️ DUPLICATE!
├── tests/
│   └── test_amortization.php   # ⚠️ DUPLICATE!
├── UAT.md
└── INSTALL.md                  # ⚠️ CONFLICTING instructions!
```

**Status:** ❌ **PROBLEMATIC - Should be removed or consolidated**

#### Location 3: `src/Ksfraser/Amortizations/fa/` (SHOULD BE HERE)
```
src/Ksfraser/Amortizations/fa/
├── INSTALL.md
└── (FA-specific classes should go here)
```

**Status:** ⚠️ **Should contain FA-specific implementations but may be empty**

#### Location 4: `src/Ksfraser/Amortizations/`
```
src/Ksfraser/Amortizations/
├── FADataProvider.php          # FA-specific (BELONGS IN fa/ subdirectory!)
├── FAJournalService.php        # FA-specific (BELONGS IN fa/ subdirectory!)
├── SuiteCRMLoanEventProvider.php # SuiteCRM-specific (BELONGS IN suitecrm/ subdirectory!)
├── WPLoanEventProvider.php     # WP-specific (BELONGS IN wordpress/ subdirectory!)
└── ... (other platform-specific files mixed with core)
```

**Status:** ❌ **CRITICAL PROBLEM - Platform-specific code in core namespace!**

---

### ⚠️ WordPress Code (PROBLEMATIC - INCOMPLETE/ORPHANED)

#### Location 1: `modules/WPLoanEventProvider.php` (ORPHANED)
```
modules/
├── WPLoanEventProvider.php     # ⚠️ Orphaned! Where should this be?
```

**Status:** ❌ **Orphaned at root of modules directory**  
**Should be:** `src/Ksfraser/Amortizations/wordpress/` with proper WP plugin structure

#### Location 2: `src/Ksfraser/wordpress/` (INCOMPLETE)
```
src/Ksfraser/wordpress/
├── INSTALL.md                  # Installation instructions exist
└── (Implementation missing or incomplete)
```

**Status:** ❌ **Missing implementation, no plugin structure**

#### Expected Location (MISSING):
```
packages/ksf-amortizations-wordpress/  # Should exist
├── plugin/
│   ├── amortizations.php       # Main plugin file (MISSING!)
│   ├── hooks.php               # WP hooks (MISSING!)
│   └── views/                  # Plugin views (MISSING!)
└── src/
    └── Ksfraser/Amortizations/WordPress/
        └── (Platform implementations)
```

---

### ⚠️ SuiteCRM Code (PROBLEMATIC - INCOMPLETE/ORPHANED)

#### Location 1: `modules/SuiteCRMLoanEventProvider.php` (ORPHANED)
```
modules/
├── SuiteCRMLoanEventProvider.php # ⚠️ Orphaned! Where should this be?
```

**Status:** ❌ **Orphaned at root of modules directory**  
**Should be:** `src/Ksfraser/Amortizations/suitecrm/` with proper SuiteCRM module structure

#### Location 2: `src/Ksfraser/suitecrm/` (INCOMPLETE)
```
src/Ksfraser/suitecrm/
├── INSTALL.md                  # Installation instructions exist
└── (Implementation missing or incomplete)
```

**Status:** ❌ **Missing implementation, no module structure**

#### Expected Location (MISSING):
```
packages/ksf-amortizations-suitecrm/  # Should exist
├── module/
│   ├── hooks.php               # SuiteCRM hooks (MISSING!)
│   └── views/                  # Module views (MISSING!)
└── src/
    └── Ksfraser/Amortizations/SuiteCRM/
        └── (Platform implementations)
```

---

## Duplication Analysis

### Duplicate Files Found

#### 1. FAJournalService.php
```
Location A: modules/amortization/FAJournalService.php    (550 lines)
Location B: modules/fa/FAJournalService.php              (DUPLICATE)
Location C: src/Ksfraser/Amortizations/fa/               (May also exist?)

ISSUE: Which one is authoritative? When one is updated, are the others?
```

#### 2. LoanEventProvider.php
```
Location A: src/Ksfraser/Amortizations/LoanEventProvider.php        (Base class)
Location B: modules/amortization/LoanEventProvider.php              (FA impl?)
Location C: modules/fa/LoanEventProvider.php                        (FA impl duplicate?)

ISSUE: Base class mixed with implementations
```

#### 3. FADataProvider.php
```
Location A: modules/amortization/FADataProvider.php      (FA DB adapter)
Location B: src/Ksfraser/Amortizations/fa/               (Should be here)

ISSUE: Platform-specific code in core namespace
```

#### 4. hooks.php
```
Location A: modules/amortization/hooks.php               (FA module)
Location B: modules/fa/hooks.php                         (Duplicate?)
Location C: modules/fa_mock/hooks.php                    (Mock version?)

ISSUE: Multiple versions - which is active?
```

#### 5. INSTALL.md
```
Location A: modules/amortization/INSTALL.md             (FA install)
Location B: modules/fa/INSTALL.md                       (Conflicting?)
Location C: src/Ksfraser/wordpress/INSTALL.md           (WP install)
Location D: src/Ksfraser/suitecrm/INSTALL.md            (SuiteCRM install)
Location E: src/Ksfraser/fa/INSTALL.md                  (FA install - duplicate?)

ISSUE: Which instructions are correct? Developers will be confused!
```

---

## Deployment Confusion Map

### FrontAccounting Deployment
**Unclear Question:** What files actually deploy to FA?

**Possible Answer A:**
- Copy `modules/amortization/*` to `/path/to/fa/modules/amortization/`
- Copy `src/Ksfraser/Amortizations/*` to... where?

**Possible Answer B:**
- Use `composer require ksfraser/amortizations`
- Which files get installed where?

**Possible Answer C:**
- Copy `modules/fa/*` to FA instead?
- Or combine with `modules/amortization/`?

**RESULT:** ❌ Confusion! Developers don't know which files to use.

### WordPress Deployment
**Unclear Question:** Where is the WordPress plugin?

**Missing:**
- Main plugin file: `amortizations.php` with plugin header
- Plugin hooks in proper WP locations
- Plugin structure in `wp-content/plugins/`

**Existing but Orphaned:**
- `modules/WPLoanEventProvider.php` - Where should this go?

**RESULT:** ❌ No clear WP plugin deployment path!

### SuiteCRM Deployment
**Unclear Question:** Where is the SuiteCRM module?

**Missing:**
- Proper SuiteCRM module structure
- Module hooks and manifest.php
- Module structure in `custom/modules/`

**Existing but Orphaned:**
- `modules/SuiteCRMLoanEventProvider.php` - Where should this go?

**RESULT:** ❌ No clear SuiteCRM module deployment path!

---

## Test File Organization

### Tests (ALSO MIXED)
```
tests/                                     # Root test directory
├── AmortizationModelTest.php             # Core tests
├── ControllerPlatformTest.php            # Platform tests (mixed)
├── FADataProviderTest.php                # FA tests
├── FAJournalServiceTest.php              # FA tests
├── LoanEventProviderTest.php             # Provider tests
├── SelectorModelTest.php                 # Selector tests
├── SuiteCRMDataProviderTest.php          # SuiteCRM tests
├── WPDataProviderTest.php                # WP tests
└── UAT.md                                # UAT documentation

ISSUE: Core and platform tests mixed together
       Should be organized: tests/Core/, tests/FA/, tests/WordPress/, tests/SuiteCRM/
```

### Module-specific Tests (ALSO MIXED)
```
modules/amortization/tests/
└── test_amortization.php                 # FA tests

modules/fa/tests/
└── test_amortization.php                 # DUPLICATE of above?

ISSUE: Test duplication between modules/amortization/ and modules/fa/
```

---

## composer.json Analysis

### Root composer.json
```json
{
    "autoload": {
        "psr-4": {
            "Ksfraser\\Amortizations\\": "src/Ksfraser/Amortizations/",
            "Ksfraser\\Amortizations\\FA\\": "modules/fa/",          // ⚠️ Why here?
            "Ksfraser\\Amortizations\\WordPress\\": "modules/wordpress/",  // ⚠️ Why here?
            "Ksfraser\\Amortizations\\SuiteCRM\\": "modules/suitecrm/"     // ⚠️ Why here?
        }
    }
}
```

**Issues:**
- ⚠️ Platform-specific namespaces mapped to `modules/` but implementations in `src/Ksfraser/Amortizations/`
- ⚠️ `modules/fa/` is mapped as namespace but contains hooks.php, not PHP classes
- ⚠️ `modules/wordpress/` and `modules/suitecrm/` don't actually exist!
- ⚠️ Should platform code really be in `modules/` at all?

### Local composer.json
```
modules/amortization/composer.json        # Exists (local dev)
```

**Questions:**
- How does this relate to root composer.json?
- What packages does it require?
- When should developers use this vs. root?

---

## Summary of Issues

### Critical Problems

| # | Issue | Impact | Severity |
|---|-------|--------|----------|
| 1 | 3 locations for FA code (modules/amortization/, modules/fa/, src/Ksfraser/fa/) | Confusion, maintenance nightmare | 🔴 CRITICAL |
| 2 | Duplicate FAJournalService.php | Updates to one won't be in others | 🔴 CRITICAL |
| 3 | Platform-specific code in core namespace | Wrong architecture | 🔴 CRITICAL |
| 4 | WPLoanEventProvider.php orphaned at module root | Developers won't find it | 🔴 CRITICAL |
| 5 | SuiteCRMLoanEventProvider.php orphaned at module root | Developers won't find it | 🔴 CRITICAL |
| 6 | No proper WordPress plugin structure | WP deployment impossible | 🔴 CRITICAL |
| 7 | No proper SuiteCRM module structure | SuiteCRM deployment impossible | 🔴 CRITICAL |
| 8 | Multiple conflicting INSTALL.md files | Developers don't know which to follow | 🟠 HIGH |
| 9 | Tests mixed in multiple locations | Test maintenance confusion | 🟠 HIGH |
| 10 | composer.json mappings incorrect | Autoloading may fail | 🟠 HIGH |

---

## Quick Fix vs. Proper Fix

### Quick Fix (1 week - TEMPORARY)
1. Remove `modules/fa/` (consolidate to `modules/amortization/`)
2. Move orphaned providers to proper locations
3. Create single, clear INSTALL.md per platform
4. Update composer.json autoloading

**Result:** Slightly less confusing, but structure still problematic

### Proper Fix (3-4 weeks - RECOMMENDED)
1. Create `packages/` directory with 4 independent packages
2. Separate core (library) from platform-specific (plugins/modules)
3. Clear deployment instructions for each platform
4. Clean composer structure with proper dependencies

**Result:** Professional, maintainable, industry-standard structure

---

## Recommendation

**For Phase 1 (Current):** Use **Quick Fix** to unblock deployment  
**For Phase 2:** Implement **Proper Fix** for long-term maintainability

---

*Current Code Structure Analysis*  
*Date: December 9, 2025*  
*Status: Problem Documentation Complete - Ready for Action*
