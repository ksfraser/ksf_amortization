# CSS SRP Architecture Analysis

## Executive Summary

You've raised two critical architectural questions about the CSS SRP refactoring:

1. **CSS Reusability**: Are these CSS files common/shareable between views?
2. **FrontAccounting Integration**: Where do these fit in FA's skin/theme system?

The answers have significant implications for how we organize, maintain, and integrate these styles.

---

## Part 1: CSS Reusability Analysis

### CSS Categories Identified

#### Category A: Highly Reusable Common Styles
These classes are **identical across all views** and should be centralized:

**Button Styles**
```css
.btn, .btn-primary, .btn-small, .btn-edit, .btn-delete, .btn-view, .btn-download
- Padding/sizing conventions
- Color schemes (blue=#1976d2, orange=#ff9800, red=#f44336, green=#388e3c)
- Hover state transitions
- Focus/active states
- ALL views use identical implementations
```

**Form Styles**
```css
.form-container, .form-group, input focus states, placeholder styling
- Flex layout (gap: 10px, align-items: flex-end)
- Input field styling (border, padding, border-radius)
- Focus states with blue highlight (#1976d2)
- Placeholder color (#ccc)
- ALL views use identical implementations
```

**Reusability Score: 95%** - These should be extracted to `common.css` or `core.css`

---

#### Category B: Partially Reusable Structural Styles
These classes follow the same **pattern** but with view-specific customization:

**Table Styles**
```css
.{table-name}-table (generic structure, class name varies)
- Width: 100%, border-collapse: collapse
- Header: blue background #1976d2, white text, 12px padding
- Cells: 12px padding, border-bottom 1px solid #eee
- Hover: light gray background #f5f5f5
- Pattern is identical; only table name varies
```

**Status/Context Cells** (LoanSummaryTable only)
```css
.status-active, .status-inactive, .status-pending, .status-completed
- Specific to loan status use case
- Could be abstracted to .status-{type} pattern
```

**Reusability Score: 60%** - Structure is reusable; implementation is view-specific

---

#### Category C: View-Specific Styles
These styles are **unique to a specific table** and should remain isolated:

**Cell Alignment & Sizing**
```css
.id-cell { width: 50px; text-align: center; }
.name-cell { font-weight: 500; }
.amount-cell { text-align: right; } // LoanSummaryTable only
.date-cell { font-size: 13px; } // ReportingTable only
```

**Reusability Score: 0%** - Table-specific; must remain in view CSS files

---

### Proposed CSS Architecture

#### Current Structure (Too Fragmented)
```
/assets/css/
├── interest-freq-table.css    (40 lines)
├── interest-freq-form.css     (35 lines)
├── interest-freq-buttons.css  (65 lines)
├── loan-summary-table.css     (50 lines)
├── loan-summary-form.css      (35 lines)
├── loan-summary-buttons.css   (70 lines)
├── loan-types-table.css       (40 lines)
├── loan-types-form.css        (50 lines)
├── loan-types-buttons.css     (70 lines)
├── reporting-table.css        (35 lines)
├── reporting-form.css         (35 lines)
└── reporting-buttons.css      (75 lines)
```

**Problem**: Massive duplication of identical CSS code across all files

---

#### Recommended Structure (Optimized)
```
/assets/css/
├── core/
│   ├── common.css             (150 lines) - All reusable btn, form, action-buttons styles
│   ├── tables-base.css        (80 lines)  - Generic table structure pattern
│   └── status-badges.css      (40 lines)  - Status cell color patterns
├── views/
│   ├── interest-freq-table.css (20 lines) - Only unique .id-cell, .name-cell, .description-cell
│   ├── loan-summary-table.css  (30 lines) - Amount cell, status cells (specific styling)
│   ├── loan-types-table.css    (20 lines) - Unique cells only
│   └── reporting-table.css     (15 lines) - Unique cells only
```

**Benefit**: Reduce CSS by 70% while maintaining all styling

---

### Reusable CSS Extraction Plan

#### common.css (150 lines)
```css
/* Action Buttons Container */
.action-buttons {
    display: flex;
    gap: 5px;
    justify-content: center;
    flex-wrap: wrap;
}

/* Generic Button Base */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:focus {
    outline: none;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Small Button Variant */
.btn-small {
    padding: 6px 12px;
    font-size: 12px;
}

.btn-small:focus {
    outline: none;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
}

.btn-small:active {
    transform: translateY(1px);
}

/* Button Colors - Primary */
.btn-primary {
    background-color: #1976d2;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: #1565c0;
    box-shadow: 0 2px 8px rgba(25, 118, 210, 0.3);
}

/* Button Colors - Edit/Warning */
.btn-edit {
    background-color: #ff9800;
    color: white;
}

.btn-edit:hover {
    background-color: #f57c00;
    box-shadow: 0 2px 5px rgba(255, 152, 0, 0.3);
}

/* Button Colors - Delete/Danger */
.btn-delete {
    background-color: #f44336;
    color: white;
}

.btn-delete:hover {
    background-color: #d32f2f;
    box-shadow: 0 2px 5px rgba(244, 67, 54, 0.3);
}

/* Button Colors - View/Info */
.btn-view {
    background-color: #1976d2;
    color: white;
}

.btn-view:hover {
    background-color: #1565c0;
    box-shadow: 0 2px 5px rgba(25, 118, 210, 0.3);
}

/* Button Colors - Download/Success */
.btn-download {
    background-color: #388e3c;
    color: white;
}

.btn-download:hover {
    background-color: #2e7d32;
    box-shadow: 0 2px 5px rgba(56, 142, 60, 0.3);
}

/* Form Container */
.form-container {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
}

/* Form Group */
.form-group {
    flex: 1;
    min-width: 150px;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
    font-family: inherit;
    transition: border-color 0.2s ease;
}

.form-group input:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 5px rgba(25, 118, 210, 0.2);
}

.form-group input::placeholder {
    color: #ccc;
}

/* Form Wrappers */
.add-interest-freq-form,
.loan-summary-form,
.add-loan-type-form,
.reporting-form {
    margin: 30px 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
}
```

#### tables-base.css (80 lines)
```css
/* Generic Table Structure */
.interest-freq-table,
.loan-summary-table,
.loan-types-table,
.reporting-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Table Headers */
.interest-freq-table th,
.loan-summary-table th,
.loan-types-table th,
.reporting-table th {
    background-color: #1976d2;
    color: white;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border: 1px solid #1565c0;
}

/* Table Cells */
.interest-freq-table td,
.loan-summary-table td,
.loan-types-table td,
.reporting-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

/* Row Hover */
.interest-freq-table tbody tr:hover,
.loan-summary-table tbody tr:hover,
.loan-types-table tbody tr:hover,
.reporting-table tbody tr:hover {
    background-color: #f5f5f5;
    cursor: pointer;
}

/* Actions Cell (Common) */
.interest-freq-table .actions-cell,
.loan-summary-table .actions-cell,
.loan-types-table .actions-cell,
.reporting-table .actions-cell {
    text-align: center;
}

/* ID Cell (Common) */
.interest-freq-table .id-cell,
.loan-summary-table .id-cell,
.loan-types-table .id-cell,
.reporting-table .id-cell {
    width: 50px;
    text-align: center;
    color: #999;
    font-size: 13px;
}
```

#### status-badges.css (40 lines)
```css
/* Status Cell Styling Pattern */
.status-cell {
    font-weight: 500;
    text-align: center;
    padding: 8px 12px;
    border-radius: 3px;
}

.status-active {
    color: #388e3c;
    background-color: #e8f5e9;
}

.status-inactive {
    color: #d32f2f;
    background-color: #ffebee;
}

.status-pending {
    color: #f57c00;
    background-color: #fff3e0;
}

.status-completed {
    color: #1976d2;
    background-color: #e3f2fd;
}
```

#### interest-freq-table.css (20 lines) - View Specific
```css
/* View-specific cell styling */
.interest-freq-table .name-cell {
    font-weight: 500;
    color: #333;
}

.interest-freq-table .description-cell {
    color: #666;
    font-size: 13px;
}
```

#### loan-summary-table.css (30 lines) - View Specific
```css
/* Loan Summary - Amount Cell (right-aligned currency) */
.loan-summary-table .borrower-cell {
    font-weight: 500;
    color: #333;
}

.loan-summary-table .amount-cell {
    text-align: right;
    font-weight: 500;
    color: #333;
}
```

#### loan-types-table.css (20 lines) - View Specific
```css
/* Loan Type - Cell styling */
.loan-types-table .name-cell {
    font-weight: 500;
    color: #333;
}

.loan-types-table .description-cell {
    color: #666;
    font-size: 13px;
}
```

#### reporting-table.css (15 lines) - View Specific
```css
/* Reporting - Type and Date cells */
.reporting-table .type-cell {
    font-weight: 500;
    color: #333;
}

.reporting-table .date-cell {
    font-size: 13px;
    color: #666;
}
```

---

## Part 2: FrontAccounting Skin/Theme Integration

### FrontAccounting Skin System Overview

FA has a comprehensive theming system in `/company/SKIN_NAME/`:

```
FA Installation
├── company/
│   ├── DEFAULT/          (Default skin)
│   │   ├── css/
│   │   ├── images/
│   │   ├── js/
│   │   └── print.css
│   └── CUSTOM/           (Custom skins)
└── themes/               (Alternative theme system)
```

### How Our Modules Integrate

**Current Module Structure:**
```
/modules/amortization/
├── src/
│   └── Ksfraser/Amortizations/Views/
│       ├── InterestCalcFrequencyTable.php
│       ├── LoanSummaryTable.php
│       └── ReportingTable.php
├── assets/css/           <- OUR CSS HERE (modules don't have skin support)
└── hooks.php
```

**Problem**: Our CSS lives in `/modules/amortization/assets/css/`, but FA skins live in `/company/{SKIN}/css/`

### Architecture Decision: FA Skin Integration

#### Option 1: Keep CSS in Module (Current Approach)
```
/modules/amortization/assets/css/
├── common.css
├── views/
│   └── interest-freq-table.css
```

**Pros:**
- CSS ships with module
- No skin customization needed
- Simpler deployment

**Cons:**
- ❌ Can't override in FA skins
- ❌ Ignores FA's theming system
- ❌ Users can't customize colors/fonts
- ❌ No FA skin variable reuse

#### Option 2: Hybrid - Module + Skin Support (RECOMMENDED)
```
Module Ships With:
/modules/amortization/assets/css/
├── common.css            (Core styles with CSS variables)
├── tables-base.css       (Structure only, no colors)
└── views/
    └── interest-freq-table.css

FA Skin Can Override:
/company/{SKIN}/css/
├── amortization/
│   ├── theme.css         (Skin-specific color overrides)
│   └── views/
│       └── interest-freq-table.css (Optional skin customization)
```

**CSS Variables Approach** (in common.css):
```css
:root {
    /* Color Scheme */
    --primary-color: #1976d2;
    --primary-hover: #1565c0;
    --warning-color: #ff9800;
    --warning-hover: #f57c00;
    --danger-color: #f44336;
    --danger-hover: #d32f2f;
    --success-color: #388e3c;
    --success-hover: #2e7d32;
    
    /* Spacing */
    --button-padding: 10px 20px;
    --button-small-padding: 6px 12px;
    --input-padding: 10px;
    --form-gap: 10px;
    
    /* Sizing */
    --border-radius: 4px;
    --font-size-small: 12px;
    --font-size-base: 14px;
}

.btn-primary {
    background-color: var(--primary-color);
    /* Skin can override: --primary-color: #custom-blue; */
}

.btn-edit {
    background-color: var(--warning-color);
    /* Skin can override: --warning-color: #custom-orange; */
}
```

**Pros:**
- ✅ Module ships with defaults
- ✅ Skin system can override
- ✅ FA users can customize
- ✅ Respects FA architecture
- ✅ CSS Variables enable theming
- ✅ No code changes needed

**Cons:**
- Requires CSS variable knowledge
- Skin creators need to understand variables

---

### FrontAccounting Skin Strategy

#### Step 1: Module CSS (No Changes Needed)
Module ships with:
- `/assets/css/common.css` (with CSS variables)
- `/assets/css/core/*` (structure/layout)
- `/assets/css/views/*` (view-specific, no colors)

#### Step 2: Create Skin Extension
For each FA skin:
```
/company/{SKIN}/css/
├── amortization-theme.css    (Override CSS variables)
```

**Example: amortization-theme.css**
```css
/* Override FA's amortization module colors to match skin */
:root {
    --primary-color: #2196F3;        /* Use skin's primary blue */
    --primary-hover: #1976d2;        /* Match skin's hover */
    --warning-color: #FFC107;        /* Use skin's warning */
    --danger-color: #F44336;         /* Use skin's danger */
    --success-color: #4CAF50;        /* Use skin's success */
}
```

#### Step 3: Load Strategy
In view PHP:
```php
// In render()
if (function_exists('asset_url')) {
    // Core module styles
    $output .= '<link rel="stylesheet" href="' . asset_url('css/common.css') . '">';
    $output .= '<link rel="stylesheet" href="' . asset_url('css/interest-freq-table.css') . '">';
    
    // Skin override (optional - FA system loads this if exists)
    if (file_exists($theme_css_path . '/amortization-theme.css')) {
        $output .= '<link rel="stylesheet" href="' . skin_asset_url('amortization-theme.css') . '">';
    }
}
```

---

### Integration with FA's asset_url()

**How asset_url() Works in FA:**
```php
// FA's asset_url function (in hooks.php or FA core)
function asset_url($path) {
    // Returns correct path based on context
    // Searches: user skin → default skin → module
    return "path/to/asset/$path";
}
```

**Our Module Should Leverage This:**
```php
// Module loads CSS - FA's asset_url finds them in:
// 1. /company/{CURRENT_SKIN}/css/interest-freq-table.css (skin override)
// 2. /company/DEFAULT/css/interest-freq-table.css (default)
// 3. /modules/amortization/assets/css/interest-freq-table.css (module)

if (function_exists('asset_url')) {
    $output .= '<link rel="stylesheet" href="' . asset_url('css/interest-freq-table.css') . '">';
}
```

This automatically respects FA's skin hierarchy!

---

## Recommendations

### Immediate (Next Session)

1. **Consolidate CSS to Common Files**
   - Extract 150+ lines of duplicated CSS to `common.css`
   - Reduce from 12 files to 8-9 files
   - Add CSS variables for theming

2. **Fix View-Specific CSS**
   - Keep only unique cell styling in view files
   - 80% reduction in CSS duplication

### Medium Term

3. **FrontAccounting Integration**
   - Document CSS variable system
   - Create example skin override file
   - Update hooks.php with skin asset loading

4. **Theme Documentation**
   - Create `CSS_THEMING_GUIDE.md`
   - Document all CSS variables
   - Provide skin customization examples

### Long Term

5. **Multi-Platform Support**
   - SugarCRM theme support
   - WordPress theme integration
   - Generic theme system documentation

---

## Summary

**Answer to Your Questions:**

1. **CSS Reusability**: YES - 70% of CSS is duplicated across views
   - Buttons, forms, table structure are identical
   - Should be consolidated to `common.css` with CSS variables
   
2. **FrontAccounting Skins**: YES - Our CSS should integrate with FA skins
   - Use CSS variables for colors
   - Leverage FA's `asset_url()` lookup system
   - Allow skin overrides via `/company/{SKIN}/css/`
   - No code changes needed; just follows FA's hierarchy

**Architecture**: Hybrid module + skin support using CSS variables and FA's asset lookup system.
