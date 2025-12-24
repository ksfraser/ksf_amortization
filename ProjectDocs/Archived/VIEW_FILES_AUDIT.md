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

## 4. Newly Refactored Files (Session 2)

### Scenario Builder Feature ✅
- [x] `scenario_builder.php` - NEW - What-if scenario creation interface
  - Status: Fully refactored with HTML builders
  - Assets: 5 CSS files + 5 JavaScript classes
  - SRP Pattern: Each class has single responsibility

**New Asset Files Created:**
- `assets/css/scenario-container.css` - Layout and container
- `assets/css/scenario-tabs.css` - Tab navigation
- `assets/css/scenario-forms.css` - Form elements
- `assets/css/scenario-buttons.css` - Button styling
- `assets/css/scenario-tables.css` - Table styling
- `assets/js/ScenarioTabs.js` - Tab management
- `assets/js/ScenarioFormFields.js` - Form field visibility
- `assets/js/ScenarioCalculator.js` - Real-time calculations
- `assets/js/ScenarioActions.js` - User actions (view, delete, compare)
- `assets/js/ScenarioBuilder.js` - Main orchestrator

## 5. Remaining View Files Not Yet Refactored

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
| Scenario Builder View | ✅ Complete | NEW - Fully HTML builder refactored |
| Scenario Builder Assets | ✅ Complete | 5 CSS + 5 JS classes (SRP) |
| View File Reachability | ✅ Confirmed | All paths correct |
| FA-Specific Views | ❌ Not Started | Present but need HTML refactoring |
| Cross-Platform Views | ❌ Not Moved | Still misplaced in FA package |
| Duplicate Views | ⚠️ Exists | Old location at /modules/amortization/views/ |

## 7. What Still Needs Doing

### Immediate Tasks (Next Session)
1. **Refactor admin_settings.php** - GL selector interface with HTML builders
2. **Refactor admin_selectors.php** - Selector management with HTML builders
3. **Refactor user_loan_setup.php** - Loan creation form with HTML builders
4. **Move WordPress Views** - Relocate wp_*.php to WordPress package
5. **Move SuiteCRM Views** - Relocate suitecrm_*.php to SuiteCRM package
6. **Remove Duplicates** - Delete old view files from `/modules/amortization/views/`

### Later Tasks
1. **Refactor Core Views** - Update LoanTypeTable, InterestCalcFrequencyTable, etc.
2. **Create HTML Helper Factories** - Consider platform-specific form builders
3. **Test HTML Output** - Verify all rendered HTML is valid and styled
4. **Document View Patterns** - Establish conventions for new views

---

**Last Updated:** December 20, 2025 (Session 2)
**Updates This Session:**
- ✅ Added scenario_builder.php to refactored views
- ✅ Added 10 new asset files (5 CSS + 5 JS)
- ✅ Fixed controller.php to use consistent ->toHtml()
- ✅ Clarified remaining gaps in audit
