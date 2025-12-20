# View Files Audit and HTML Library Integration - Summary

**Date:** December 20, 2025  
**Commit:** 80c0f14

## 1. View Files Discovery and Analysis

### FrontAccounting Package Views
Located in: `packages/ksf-amortizations-frontaccounting/module/amortization/views/views/`

| File | FA-Specific | Platform-Agnostic | Status |
|------|-------------|-------------------|--------|
| `admin_settings.php` | ✅ Yes (FA GL APIs) | ❌ No | Ready |
| `admin_selectors.php` | ✅ Yes (FA DB query) | ❌ No | Ready |
| `user_loan_setup.php` | ⚠️ Mixed | ⚠️ Mixed | Ready |
| `fa_loan_borrower_selector.php` | ✅ Yes | ❌ No | Ready |
| `fa_loan_term_selector.php` | ✅ Yes | ❌ No | Ready |
| `wp_loan_borrower_selector.php` | ❌ No (WordPress) | ❌ No | Misplaced |
| `wp_loan_term_selector.php` | ❌ No (WordPress) | ❌ No | Misplaced |
| `suitecrm_loan_borrower_selector.php` | ❌ No (SuiteCRM) | ❌ No | Misplaced |
| `suitecrm_loan_term_selector.php` | ❌ No (SuiteCRM) | ❌ No | Misplaced |

### Issues Found

**1. Cross-Platform Views in FA Package** ❌
- Files: `wp_*.php` and `suitecrm_*.php` 
- Location: FA package views directory
- **Action Needed:** These should be in their respective platform packages, not in the FA module

**2. Path Correctness** ✅
- Controller correctly references `/views/views/` structure
- All view files are reachable from controller

**3. Duplicated View Files**
- Views exist in:
  - `/modules/amortization/views/` (old location)
  - `/packages/ksf-amortizations-frontaccounting/module/amortization/views/views/` (new location)
- **Action Needed:** Remove duplicates from old location

## 2. HTML Generation Issues - RESOLVED ✅

### Previous State (Hardcoded HTML)
❌ Scattered echo statements throughout views:
```php
echo "<label for='$name'>...</label>";
echo "<select name='$name' id='$name'>";
// ... more hardcoded HTML
```

### Solution Implemented ✅

**Added Dependency:**
- Package: `ksfraser/html` ^1.0
- Added to all platform packages:
  - `ksf-amortizations-core`
  - `ksf-amortizations-frontaccounting`
  - `ksf-amortizations-wordpress`
  - `ksf-amortizations-suitecrm`

**Updated Controller:**
- Replaced hardcoded echo with `Ksfraser\HTML` builders
- Uses semantic HTML elements:
  - `Heading` - for h2, h3 tags
  - `Paragraph` - for p tags
  - `Div` - for div containers
  - `Link` - for a tags
  - `Form` - for forms (future)
  - `Input`, `Select`, `Table` - for form elements (to be used in views)

**Example Refactoring:**
```php
// Before
echo '<h2>Amortization Loans</h2>';
echo '<a href="...">Add New Loan</a>';

// After
echo (new Heading(2))->setText('Amortization Loans');
echo (new Link())
    ->setHref('...')
    ->setText('Add New Loan')
    ->addAttribute('class', 'button');
```

## 3. View File Reachability ✅

**Controller Paths:** All view files are properly reachable

```
controller.php
├── ?action=admin → views/views/admin_settings.php ✅
├── ?action=admin_selectors → views/views/admin_selectors.php ✅
├── ?action=create → views/views/user_loan_setup.php ✅
├── ?action=report → reporting.php (TODO) ✅
└── default → In-controller navigation ✅
```

## 4. Remaining View Files Not Yet Refactored

The following view files still use hardcoded HTML and need updating to use `Ksfraser\HTML`:

### FA-Specific Views (Priority 1)
- [ ] `admin_settings.php` - GL selectors
- [ ] `admin_selectors.php` - Selector management
- [ ] `user_loan_setup.php` - Loan form
- [ ] `fa_loan_borrower_selector.php` - FA borrower selector
- [ ] `fa_loan_term_selector.php` - FA term selector

### Core Reusable Views (Priority 2)
- [ ] `LoanTypeTable.php` - in packages/ksf-amortizations-core/src/Views/
- [ ] `InterestCalcFrequencyTable.php` - in packages/ksf-amortizations-core/src/Views/
- [ ] `LoanSummaryTable.php` - in packages/ksf-amortizations-core/src/Views/
- [ ] `ReportingTable.php` - in packages/ksf-amortizations-core/src/Views/

## 5. Misplaced Files - Action Items

**Move WordPress views to WordPress package:**
```
FROM: packages/ksf-amortizations-frontaccounting/module/amortization/views/views/wp_*.php
TO: packages/ksf-amortizations-wordpress/module/views/
```

**Move SuiteCRM views to SuiteCRM package:**
```
FROM: packages/ksf-amortizations-frontaccounting/module/amortization/views/views/suitecrm_*.php
TO: packages/ksf-amortizations-suitecrm/module/views/
```

## 6. Summary

| Item | Status | Details |
|------|--------|---------|
| HTML Library Dependency | ✅ Added | `ksfraser/html` ^1.0 in all packages |
| Controller HTML Refactoring | ✅ Complete | Uses Ksfraser\HTML builders |
| View File Reachability | ✅ Confirmed | All paths correct |
| FA-Specific Views | ⚠️ Ready | Present but need HTML refactoring |
| Cross-Platform Views | ❌ Misplaced | Need to move to respective packages |
| Duplicate Views | ⚠️ Exists | Old location at /modules/amortization/views/ |

## Next Steps

1. **Refactor View Files** - Update all view files to use `Ksfraser\HTML` builders
2. **Move Cross-Platform Views** - Reorganize WordPress/SuiteCRM views to their packages
3. **Remove Duplicates** - Delete old view files from `/modules/amortization/views/`
4. **Test HTML Output** - Verify rendered HTML is valid and styled correctly
5. **Create HTML Helper Factories** - Consider creating platform-specific form builders

---

**Last Updated:** Commit 80c0f14 (December 20, 2025)
