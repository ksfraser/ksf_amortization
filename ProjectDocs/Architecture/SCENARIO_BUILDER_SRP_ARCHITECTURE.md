# Scenario Builder - SRP JavaScript & CSS Architecture

**Date:** December 20, 2025  
**Status:** ✅ Refactored to SRP Classes

## Overview

The Scenario Builder has been refactored into **5 single-responsibility JavaScript classes** and **5 organized CSS files** following SOLID principles. This separates concerns, improves maintainability, and makes the code reusable and testable.

---

## JavaScript Classes

### 1. ScenarioTabs.js
**Responsibility:** Tab navigation and visibility management

**Key Methods:**
- `init()` - Initialize event listeners
- `switchTab(tabName)` - Switch to specified tab
- `getActiveTab()` - Get currently active tab name
- `handleTabClick(event)` - Handle tab button clicks

**Features:**
- Dynamic tab switching with configurable selectors
- Active state management
- Support for custom CSS classes

**Usage:**
```javascript
const tabs = new ScenarioTabs({
    tabButtonSelector: '.tab-button',
    tabContentSelector: '.tab-content',
    activeClass: 'active'
});
```

---

### 2. ScenarioFormFields.js
**Responsibility:** Form field visibility and state based on scenario type

**Key Methods:**
- `init()` - Initialize field listeners
- `handleTypeChange(event)` - Handle scenario type selection
- `updateFieldVisibility(type)` - Show/hide appropriate sections
- `getCurrentType()` - Get selected scenario type
- `resetFields()` - Reset all form fields

**Features:**
- Dynamic form field visibility
- Type-to-section mapping
- Field reset functionality

**Scenario Type Mappings:**
- `extra_monthly` → Extra Monthly Payment section
- `lump_sum` → Lump Sum Payment section
- `skip_payment` → Skip Payment section
- `acceleration` → Accelerated Payoff section
- `custom` → Custom Modifications section

**Usage:**
```javascript
const formFields = new ScenarioFormFields({
    scenarioTypeSelector: '#scenarioType'
});
```

---

### 3. ScenarioCalculator.js
**Responsibility:** Real-time calculation previews without server calls

**Key Methods:**
- `init()` - Initialize calculation listeners
- `updateExtraMonthlyPreview(event)` - Update extra payment preview
- `updateLumpSumPreview(event)` - Update lump sum amount preview
- `updateLumpSumMonthPreview(event)` - Update lump sum period preview
- `updateSkipPaymentPreview(event)` - Update skip payment preview
- `formatCurrency(value)` - Format numbers as currency
- `resetPreviews()` - Reset all preview elements

**Features:**
- Real-time input feedback
- Currency formatting
- DOM-safe element updates
- Configurable preview elements

**Usage:**
```javascript
const calculator = new ScenarioCalculator({
    monthlyPayment: 800.00,
    calculationInputs: {
        extraMonthly: '#extraMonthly',
        lumpSumAmount: '#lumpSumAmount'
    },
    previewElements: {
        newPaymentExtra: '#newPaymentExtra'
    }
});
```

---

### 4. ScenarioActions.js
**Responsibility:** User action handlers (view, delete, compare scenarios)

**Key Methods:**
- `viewScenario(scenarioId)` - View a scenario
- `deleteScenario(scenarioId)` - Delete with confirmation
- `compareScenarios(s1, s2)` - Compare two scenarios
- `defaultViewHandler(scenarioId)` - Default view navigation
- `defaultDeleteHandler(scenarioId)` - Default delete navigation
- `defaultCompareHandler(s1, s2)` - Default compare navigation
- `makeGlobalFunctions()` - Expose actions to global scope

**Features:**
- Pluggable action handlers
- Confirmation dialogs
- URL-based navigation
- Custom handler support
- Global function exposure for inline onclick handlers

**Usage:**
```javascript
const actions = new ScenarioActions({
    confirmMessage: 'Delete this scenario? This cannot be undone.',
    viewHandler: (id) => customViewLogic(id),
    deleteHandler: (id) => customDeleteLogic(id)
});

actions.makeGlobalFunctions(); // Exposes viewScenario(), deleteScenario(), etc.
```

---

### 5. ScenarioBuilder.js
**Responsibility:** Main orchestrator - coordinates all sub-modules

**Key Methods:**
- `init()` - Initialize all modules
- `getFormState()` - Get current form state
- `destroy()` - Cleanup and teardown
- `attachFormResetListener()` - Reset form handling

**Features:**
- Coordinates tabs, form fields, calculator, and actions
- Centralized initialization
- Form reset synchronization
- Lifecycle management

**Usage:**
```javascript
const scenarioBuilder = new ScenarioBuilder({
    monthlyPayment: 800.00,
    remainingMonths: 60,
    tabs: {},
    formFields: {},
    calculator: {},
    actions: {}
});
```

---

## CSS Files Organization

### 1. scenario-container.css
**Purpose:** Main layout and container styling

**Elements:**
- `.scenario-builder-container` - Main wrapper
- `.scenario-form-wrapper` - Form container
- `.subtitle` - Subtitle text

**Features:**
- Responsive container (max-width: 1000px)
- Professional spacing
- Border and padding styling

---

### 2. scenario-tabs.css
**Purpose:** Tab navigation styling

**Elements:**
- `.scenario-tabs` - Tab container
- `.tab-button` - Individual tab buttons
- `.tab-content` - Tab content areas
- `@keyframes fadeIn` - Fade animation

**Features:**
- Flex layout for tabs
- Active state indicators
- Smooth transitions
- Fade-in animation

---

### 3. scenario-forms.css
**Purpose:** Form elements and sections

**Elements:**
- `.form-section` - Form section grouping
- `.form-group` - Form field grouping
- `.loan-info-table` - Loan information display
- `.scenario-config-section` - Configuration sections
- `.calculation-preview` - Preview boxes
- `.help-text` - Help text styling

**Features:**
- Input styling with focus states
- Checkbox support
- Hover effects
- Box shadow for focus
- Professional color scheme

---

### 4. scenario-buttons.css
**Purpose:** Button styling

**Button Types:**
- `.btn` - Base button style
- `.btn-primary` - Primary action (blue)
- `.btn-secondary` - Secondary action (gray)
- `.btn-small` - Small buttons

**Features:**
- Hover and active states
- Disabled state support
- Scale animation on click
- Color variations
- Consistent padding and sizing

---

### 5. scenario-tables.css
**Purpose:** Table styling

**Elements:**
- `.scenarios-table` - Main table
- Header row styling
- Row hover effects
- `.no-data` - Empty state message

**Features:**
- Clean row striping
- Hover highlights
- Column width management
- Color-coded savings column (green)
- Professional borders

---

## View Integration

The `scenario_builder.php` view has been cleaned to:

1. **Use HTML Builders Only** - All HTML generated through Ksfraser\HTML builder classes
2. **Load External Assets** - CSS and JS files loaded from assets directory
3. **Initialize ScenarioBuilder** - Single initialization script at bottom
4. **Clean Separation** - View, style, and behavior fully separated

**Asset Links:**
```php
<link rel="stylesheet" href="<?= asset_url('module/amortization/assets/css/...') ?>">
<script src="<?= asset_url('module/amortization/assets/js/...') ?>"></script>
```

---

## File Structure

```
packages/ksf-amortizations-frontaccounting/module/amortization/
├── assets/
│   ├── css/
│   │   ├── scenario-container.css
│   │   ├── scenario-tabs.css
│   │   ├── scenario-forms.css
│   │   ├── scenario-buttons.css
│   │   └── scenario-tables.css
│   └── js/
│       ├── ScenarioTabs.js
│       ├── ScenarioFormFields.js
│       ├── ScenarioCalculator.js
│       ├── ScenarioActions.js
│       └── ScenarioBuilder.js
└── views/views/
    └── scenario_builder.php
```

---

## Benefits of This Architecture

### Single Responsibility Principle
- Each class has one reason to change
- Each CSS file handles one concern
- Easy to understand and modify

### Reusability
- Classes can be used independently
- CSS can be shared across pages
- Pluggable handlers in actions

### Testability
- Each class can be tested in isolation
- No global state pollution
- Configurable dependencies

### Maintainability
- Clear separation of concerns
- Easy to locate and fix issues
- Self-documenting code

### Performance
- CSS files can be cached separately
- JS files are minifiable
- Lazy loading possible

### Scalability
- Easy to add new scenario types
- Can extend with new handlers
- CSS cascades can be extended

---

## Initialization Example

```javascript
// Basic initialization
const scenarioBuilder = new ScenarioBuilder({
    monthlyPayment: 800.00,
    remainingMonths: 60
});

// Advanced initialization with custom handlers
const scenarioBuilder = new ScenarioBuilder({
    monthlyPayment: 800.00,
    remainingMonths: 60,
    tabs: {
        activeClass: 'is-active'
    },
    formFields: {
        scenarioTypeSelector: '#scenario-type-select'
    },
    calculator: {
        monthlyPayment: 800.00
    },
    actions: {
        viewHandler: (id) => showScenarioModal(id),
        deleteHandler: (id) => sendDeleteRequest(id)
    }
});
```

---

## Next Steps

1. **Browser Testing** - Verify all scenarios work correctly
2. **Controller Integration** - Wire up form submissions
3. **Session Management** - Save scenarios to session
4. **PDF Generation** - Implement PDF export
5. **Mobile Optimization** - Add responsive CSS media queries
6. **Performance** - Minify CSS and JS for production

---

**Total Files Created:** 10 files (5 JS + 5 CSS)  
**Total Lines:** ~800 lines (well-organized and documented)  
**Architecture:** SRP, Reusable, Maintainable, Testable
