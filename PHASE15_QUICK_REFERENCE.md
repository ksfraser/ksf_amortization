# Phase 15 Quick Reference: New Helpers & Patterns

## Quick Navigation
- [TableRow Helper](#tablerow-helper)
- [Repository Pattern](#repository-pattern)
- [Builder Pattern](#builder-pattern)
- [Handler Pattern](#handler-pattern)

---

## TableRow Helper

### `addHeadersFromArray()`

**Purpose:** Create table header rows from a simple array of labels

**Location:** `vendor-src/ksfraser-html/src/Ksfraser/HTML/Elements/TableRow.php`

**Before (Verbose):**
```php
$headerRow = (new TableRow());
$headerRow->append((new TableHeader())->setText('ID'));
$headerRow->append((new TableHeader())->setText('Borrower'));
$headerRow->append((new TableHeader())->setText('Amount'));
$headerRow->append((new TableHeader())->setText('Status'));
```

**After (Clean):**
```php
$headerRow = (new TableRow())
    ->addHeadersFromArray(['ID', 'Borrower', 'Amount', 'Status']);
```

**Method Signature:**
```php
public function addHeadersFromArray(array $labels): self
```

**Returns:** `$this` (fluent interface)

**Usage Example:**
```php
$table = (new Table())->addClass('data-table');
$table->append(
    (new TableRow())->addHeadersFromArray(['Name', 'Email', 'Phone'])
);
```

---

## Repository Pattern

### `SelectorRepository`

**Purpose:** Centralize database operations for selector options

**Location:** `packages/ksf-amortizations-frontaccounting/module/amortization/Repository/SelectorRepository.php`

**Usage in Views:**
```php
use Ksfraser\Amortizations\Repository\SelectorRepository;

$selectorRepo = new SelectorRepository();

// CRUD Operations
$selectorRepo->add($_POST);          // Create
$options = $selectorRepo->getAll(); // Read
$selectorRepo->update($_POST);       // Update
$selectorRepo->delete($_POST);       // Delete
```

**Benefits:**
- ✅ Encapsulates database logic
- ✅ Removes SQL from views
- ✅ Testable with mocks
- ✅ Reusable across application

---

## Builder Pattern

### `TableBuilder`

**Purpose:** Simplify table construction from arrays

**Methods:**
```php
// Create header row from array
TableBuilder::createHeaderRow(['ID', 'Name', 'Email']);

// Create data row from array
TableBuilder::createDataRow(['1', 'John Doe', 'john@example.com']);
```

**Usage in Views:**
```php
use Ksfraser\HTML\Elements\TableBuilder;

$table = (new Table())->addClass('selectors-table');
$headerRow = TableBuilder::createHeaderRow(['ID', 'Selector Name', 'Option Name', 'Actions']);
$table->append($headerRow);
```

---

## Handler Pattern

### 1. `SelectEditJSHandler`

**Purpose:** Generate JavaScript for inline edit functionality

**Location:** `vendor-src/ksfraser-html/src/Ksfraser/HTML/Elements/SelectEditJSHandler.php`

**Usage:**
```php
use Ksfraser\HTML\Elements\SelectEditJSHandler;

$handler = (new SelectEditJSHandler())
    ->setFormIdPrefix('selector')
    ->setFieldNames(['id', 'selector_name', 'option_name', 'option_value']);

echo $handler->toHtml();
```

**What It Generates:**
- Encapsulated `editOption()` JavaScript function
- Automatic form population logic
- Zero inline scripts in view

**Benefits:**
- ✅ Removes hardcoded JS from views
- ✅ Centralized edit logic
- ✅ Easy to customize
- ✅ Testable components

---

### 2. `AjaxSelectPopulator`

**Purpose:** Handle dynamic select population via AJAX

**Location:** `vendor-src/ksfraser-html/src/Ksfraser/HTML/Elements/AjaxSelectPopulator.php`

**Usage:**
```php
use Ksfraser\HTML\Elements\AjaxSelectPopulator;

$populator = (new AjaxSelectPopulator())
    ->setTriggerSelectId('borrower_type')
    ->setTargetSelectId('borrower_id')
    ->setAjaxEndpoint('borrower_ajax.php')
    ->setParameterName('type');

echo $populator->toHtml();
```

**What It Generates:**
- Event listener for trigger select
- Automatic AJAX request handling
- Target select population
- Loading states and error handling

**Benefits:**
- ✅ Removes fetch/$.ajax() code from views
- ✅ Handles all AJAX complexity
- ✅ Automatic error handling
- ✅ Loading UI management

---

### 3. `PaymentFrequencyHandler`

**Purpose:** Manage payment frequency options and calculations

**Location:** `vendor-src/ksfraser-html/src/Ksfraser/HTML/Elements/PaymentFrequencyHandler.php`

**Usage:**
```php
use Ksfraser\HTML\Elements\PaymentFrequencyHandler;

$handler = (new PaymentFrequencyHandler())
    ->setSelectId('payment_frequency')
    ->setPaymentsFieldId('payments_per_year')
    ->setSelectedFrequency('monthly');

// Get options for select
$options = $handler->getFrequencyOptions();
// ['annual' => 'Annual (1x)', 'monthly' => 'Monthly (12x)', ...]

// Generate JS for calculations
echo $handler->toHtml();
```

**Frequency Mapping:**
```
annual      → 1 payment/year
semi-annual → 2 payments/year
quarterly   → 4 payments/year
monthly     → 12 payments/year
semi-monthly → 24 payments/year
bi-weekly   → 26 payments/year
weekly      → 52 payments/year
```

**Benefits:**
- ✅ Removes frequency map from views
- ✅ Centralized calculation logic
- ✅ Easy to extend with new frequencies
- ✅ Automatic JS generation

---

## Action Buttons

### `EditButton`

**Purpose:** Consistent edit button with configurable onclick

**Usage:**
```php
use Ksfraser\HTML\Elements\EditButton;
use Ksfraser\HTML\Elements\HtmlString;

$editBtn = new EditButton(
    new HtmlString('Edit'),
    (string)$option['id'],
    sprintf("editOption(%d, '%s')", $option['id'], addslashes($option['name']))
);
```

### `DeleteButton`

**Purpose:** Consistent delete button with confirmation

**Usage:**
```php
use Ksfraser\HTML\Elements\DeleteButton;
use Ksfraser\HTML\Elements\HtmlString;

$deleteBtn = new DeleteButton(
    new HtmlString('Delete'),
    (string)$option['id'],
    "deleteOption(" . intval($option['id']) . ")"
);
```

**Benefits:**
- ✅ Consistent styling and behavior
- ✅ Configurable text and callbacks
- ✅ Built-in accessibility features
- ✅ Easy to theme

---

## Select `addOptionsFromArray()`

**Purpose:** Populate select element from associative array

**Usage:**
```php
use Ksfraser\HTML\Elements\Select;

$frequencyOptions = [
    'annual' => 'Annual (1x)',
    'monthly' => 'Monthly (12x)',
    'weekly' => 'Weekly (52x)'
];

$select = (new Select())
    ->setId('payment_frequency')
    ->setName('payment_frequency')
    ->addOptionsFromArray($frequencyOptions);
```

**Before (Manual Loop):**
```php
foreach ($frequencyOptions as $value => $label) {
    $select->append((new Option())
        ->setValue($value)
        ->setText($label)
        ->setSelected($paymentFrequency === $value)
    );
}
```

**After (Single Line):**
```php
$select->addOptionsFromArray($frequencyOptions);
```

**Benefits:**
- ✅ 90% less code
- ✅ More readable
- ✅ Handles HTML escaping
- ✅ Built-in selected state management

---

## Integration Example: Complete View

```php
<?php
use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Form;
use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\TableBuilder;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\EditButton;
use Ksfraser\HTML\Elements\DeleteButton;
use Ksfraser\HTML\Elements\SelectEditJSHandler;
use Ksfraser\Amortizations\Repository\SelectorRepository;

// Initialize repository
$repo = new SelectorRepository();

// Load data
$options = $repo->getAll();

// Page heading
echo (new Heading(2))->setText('Options Management')->toHtml();

// Build form (simplified)
$form = (new Form())->setId('optionsForm')->setMethod('POST');
// ... add form fields ...
echo $form->toHtml();

// Build table with builder pattern
$table = (new Table())->addClass('options-table');
$table->append(
    TableBuilder::createHeaderRow(['ID', 'Name', 'Value', 'Actions'])
);

// Add data rows
foreach ($options as $option) {
    $row = (new TableRow());
    $row->append(...$cells);
    
    // Action buttons
    $actions = (new Div());
    $actions->append(new EditButton(...));
    $actions->append(new DeleteButton(...));
    
    $table->append($row);
}

echo $table->toHtml();

// Generate handlers
$editHandler = (new SelectEditJSHandler())
    ->setFormIdPrefix('option')
    ->setFieldNames(['id', 'name', 'value']);

echo $editHandler->toHtml();
?>
```

---

## Testing Patterns

### Unit Test Example

```php
use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\TableRow;

class TableRowTest extends TestCase
{
    public function testAddHeadersFromArray()
    {
        $row = (new TableRow())
            ->addHeadersFromArray(['Col1', 'Col2', 'Col3']);
        
        $html = $row->toHtml();
        
        $this->assertStringContainsString('Col1', $html);
        $this->assertStringContainsString('Col2', $html);
        $this->assertStringContainsString('Col3', $html);
    }
}
```

### Integration Test Example

```php
use PHPUnit\Framework\TestCase;

class AdminSelectorsViewRefactoringTest extends TestCase
{
    public function testViewUsesSelectorRepository()
    {
        $content = file_get_contents($this->viewPath);
        
        $this->assertStringContainsString(
            'new SelectorRepository',
            $content,
            "View should use repository pattern"
        );
    }
}
```

---

## Migration Checklist

When refactoring legacy views:

- [ ] Replace raw SQL with Repository pattern
- [ ] Use TableBuilder for table headers
- [ ] Replace inline buttons with EditButton/DeleteButton
- [ ] Extract hardcoded JS to Handler classes
- [ ] Use addOptionsFromArray() for selects
- [ ] Replace render() with toHtml()
- [ ] Remove inline CSS (move to stylesheets)
- [ ] Remove inline scripts (move to handlers)
- [ ] Update tests to verify patterns
- [ ] Verify 100% test pass rate

---

## Performance Considerations

- ✅ Handlers are lazy-loaded (no performance impact)
- ✅ Patterns use efficient caching internally
- ✅ Fluent interfaces avoid intermediate objects
- ✅ Array operations are optimized
- ✅ No additional database queries

---

## Troubleshooting

### "Class not found" errors
- Verify use statements at top of file
- Check namespace is correct
- Ensure package is properly loaded

### "Call to undefined method"
- Verify method exists on class
- Check fluent interface chaining
- Review class documentation

### HTML output issues
- Ensure toHtml() not render() is called
- Verify HtmlString used for dynamic content
- Check escaping for user input

---

## References

- Full documentation: See PHASE15_FINAL_COMPLETION.md
- API Reference: Check PHPDoc in source files
- Test examples: Browse test files for usage patterns
- Git history: Review commits for implementation details

