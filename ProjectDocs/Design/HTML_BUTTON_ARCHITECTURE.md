# HTML Button Classes - Specialized Action Button Architecture

**Date:** December 20, 2025  
**Status:** Complete  
**Commits:** feccb61, aba3899

## Overview

This document describes the specialized action button classes added to the ksfraser/html library through a Git submodule. These classes follow Single Responsibility Principle (SRP) by encapsulating common button behaviors used throughout CRUD interfaces.

## Architecture

### Design Pattern: Template Method + Strategy Pattern

```
HtmlInputButton (base button class)
    ↓
ActionButton (abstract template for action buttons)
    ├─ EditButton (edit action)
    ├─ DeleteButton (delete action with confirmation)
    ├─ AddButton (create action)
    └─ CancelButton (cancel/go-back action)
```

### SOLID Principles Applied

- **Single Responsibility:** Each button class handles only one specific action type
- **Open/Closed:** Extensible for new action types without modifying existing code
- **Liskov Substitution:** All button classes can replace their parent safely
- **Interface Segregation:** Uses HtmlElementInterface appropriately
- **Dependency Inversion:** Depends on abstractions, not concrete implementations

## Git Submodule Setup

The HTML library is now managed as a Git submodule:

```bash
# Location: vendor-src/ksfraser-html
# Remote: https://github.com/ksfraser/html.git
```

### Clone with Submodules

```bash
git clone --recurse-submodules https://github.com/ksfraser/amortizations.git
```

### Update Submodule

```bash
git submodule update --remote
```

### Push Changes Back to HTML Library

```bash
cd vendor-src/ksfraser-html
git push origin main
```

## Class Specifications

### ActionButton (Abstract Base)

```php
namespace Ksfraser\HTML\Elements;

abstract class ActionButton extends HtmlInputGenericButton
{
    protected $actionId;        // Record/row ID
    protected $actionName;      // Action name (edit, delete, etc.)
    
    abstract protected function setupActionButton();
    
    public function getActionId(): string
    public function getActionName(): string
    public function setCssClass($cssClass): self
    public function addCssClass($cssClass): self
}
```

**Features:**
- Encapsulates common action button setup
- Subclasses implement `setupActionButton()` for specific behaviors
- Fluent interface for method chaining

---

### EditButton

```php
class EditButton extends ActionButton
{
    public function __construct(
        HtmlElementInterface $label,
        string $actionId = '',
        string $onclickFunction = ''
    )
    
    public function setOnclickFunction($jsFunction): self
}
```

**Features:**
- Default CSS classes: `btn btn-primary btn-sm`
- Automatic button naming: `edit_btn_{id}`
- Supports custom JavaScript onclick functions
- Perfect for table row edit actions

**Example:**

```php
use Ksfraser\HTML\Elements\EditButton;
use Ksfraser\HTML\Elements\HtmlString;

$editBtn = new EditButton(
    new HtmlString('Edit'),
    '123',
    "editOption(123, 'John', 'Doe', '2025-01-01')"
);
echo $editBtn->getHtml();
// Output: <input type="button" value="Edit" name="edit_btn_123" 
//          onclick="editOption(123, 'John', 'Doe', '2025-01-01')" 
//          class="btn btn-primary btn-sm" />
```

---

### DeleteButton

```php
class DeleteButton extends ActionButton
{
    public function __construct(
        HtmlElementInterface $label,
        string $actionId = ''
    )
    
    public function setConfirmation($message): self
    public function noConfirmation(): self
}
```

**Features:**
- Default CSS classes: `btn btn-danger btn-sm`
- Default confirmation: "Are you sure?"
- Automatic button naming: `delete_btn_{id}`
- Supports custom confirmation messages
- Can disable confirmation with `noConfirmation()`

**Example:**

```php
use Ksfraser\HTML\Elements\DeleteButton;
use Ksfraser\HTML\Elements\HtmlString;

$deleteBtn = new DeleteButton(new HtmlString('Delete'), '456');
$deleteBtn->setConfirmation("Delete this record permanently?");
echo $deleteBtn->getHtml();
// Output: <input type="button" value="Delete" name="delete_btn_456" 
//          onclick="return confirm('Delete this record permanently?');" 
//          class="btn btn-danger btn-sm" />
```

---

### AddButton

```php
class AddButton extends ActionButton
{
    public function __construct(HtmlElementInterface $label)
}
```

**Features:**
- Default CSS classes: `btn btn-success btn-sm`
- Default button naming: `add_btn`
- No action ID (not row-specific)
- Success styling for positive actions

**Example:**

```php
use Ksfraser\HTML\Elements\AddButton;
use Ksfraser\HTML\Elements\HtmlString;

$addBtn = new AddButton(new HtmlString('Add New'));
echo $addBtn->getHtml();
// Output: <input type="button" value="Add New" name="add_btn" class="btn btn-success btn-sm" />
```

---

### CancelButton

```php
class CancelButton extends ActionButton
{
    public function __construct(HtmlElementInterface $label)
    
    public function setOnclickFunction($jsFunction): self
    public function setGoBack(): self
}
```

**Features:**
- Default CSS classes: `btn btn-secondary btn-sm`
- Default button naming: `cancel_btn`
- Support for custom onclick handlers
- Convenience method `setGoBack()` for browser history

**Example:**

```php
use Ksfraser\HTML\Elements\CancelButton;
use Ksfraser\HTML\Elements\HtmlString;

$cancelBtn = new CancelButton(new HtmlString('Cancel'));
$cancelBtn->setGoBack();
echo $cancelBtn->getHtml();
// Output: <input type="button" value="Cancel" name="cancel_btn" 
//          onclick="window.history.back();" class="btn btn-secondary btn-sm" />
```

## Real-World Usage: admin_selectors.php

### Before (Inline Button Creation)

```php
// Old approach - tight coupling, repetitive
$editBtn = (new Button())
    ->setText('Edit')
    ->addAttribute('onclick', sprintf(
        "editOption(%d, '%s', '%s', '%s')",
        $opt['id'],
        addslashes($opt['selector_name']),
        addslashes($opt['option_name']),
        addslashes($opt['option_value'])
    ));
$actionsDiv->appendChild($editBtn);
```

### After (Using Specialized Buttons)

```php
// New approach - clean, reusable, SRP
$editBtn = new EditButton(
    new HtmlString('Edit'),
    (string)$opt['id'],
    sprintf(
        "editOption(%d, '%s', '%s', '%s')",
        $opt['id'],
        addslashes($opt['selector_name']),
        addslashes($opt['option_name']),
        addslashes($opt['option_value'])
    )
);
$actionsDiv->appendChild($editBtn);

$deleteBtn = new DeleteButton(new HtmlString('Delete'), (string)$opt['id']);
$deleteForm->appendChild($deleteBtn);
```

**Benefits:**
- ✅ Clear intent: `EditButton` clearly indicates edit action
- ✅ Consistent styling: Buttons inherit standard CSS classes
- ✅ Reduced duplication: No need to manually set names/classes
- ✅ Maintainability: Button behavior is centralized in one class
- ✅ Extensibility: New button types added without modifying existing code

## CSS Classes

Standard Bootstrap-compatible CSS classes are applied automatically:

| Button | CSS Classes | Color |
|--------|------------|-------|
| EditButton | `btn btn-primary btn-sm` | Blue |
| DeleteButton | `btn btn-danger btn-sm` | Red |
| AddButton | `btn btn-success btn-sm` | Green |
| CancelButton | `btn btn-secondary btn-sm` | Gray |

Override with `setCssClass()`:

```php
$editBtn = new EditButton(new HtmlString('Edit'), '123');
$editBtn->setCssClass('btn btn-info btn-lg');
```

## Testing

All button classes follow the same pattern:

```php
use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\EditButton;
use Ksfraser\HTML\Elements\HtmlString;

class EditButtonTest extends TestCase
{
    public function testEditButtonGeneration()
    {
        $btn = new EditButton(new HtmlString('Edit'), '123', 'testFunc()');
        $html = $btn->getHtml();
        
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('value="Edit"', $html);
        $this->assertStringContainsString('name="edit_btn_123"', $html);
        $this->assertStringContainsString('testFunc()', $html);
        $this->assertStringContainsString('btn btn-primary btn-sm', $html);
    }
}
```

## Migration Guide

### Step 1: Update Uses Statements

```php
// Add to your view file
use Ksfraser\HTML\Elements\EditButton;
use Ksfraser\HTML\Elements\DeleteButton;
use Ksfraser\HTML\Elements\AddButton;
use Ksfraser\HTML\Elements\CancelButton;
use Ksfraser\HTML\Elements\HtmlString;
```

### Step 2: Replace Generic Button Code

```php
// Before
(new Button())->setText('Edit')->addAttribute('onclick', 'handler()');

// After
new EditButton(new HtmlString('Edit'), $recordId, 'handler()');
```

### Step 3: Test for Consistency

Verify that:
- ✅ Button styles render consistently
- ✅ Onclick handlers execute properly
- ✅ Button names submit correctly in forms
- ✅ CSS classes apply as expected

## Future Enhancements

**Potential additions to ksfraser/html:**

1. **SubmitButton** - Form submission with validation
2. **ResetButton** - Form field reset
3. **CustomButton** - Parameterized action buttons
4. **ButtonGroup** - Multiple buttons grouped together
5. **DropdownButton** - Button with dropdown menu

## Contributing Back to ksfraser/html

To contribute improvements:

```bash
# 1. Update submodule
cd vendor-src/ksfraser-html

# 2. Create feature branch
git checkout -b feature/new-button-type

# 3. Make changes
# ... implement new classes ...

# 4. Commit to submodule
git add -A
git commit -m "Add new button class: XyzButton"

# 5. Push to GitHub
git push origin feature/new-button-type

# 6. Create pull request on GitHub
# ... wait for review and merge ...

# 7. Update main project reference
cd ../..
git add vendor-src/ksfraser-html
git commit -m "Update ksfraser/html submodule with XyzButton"
```

## File Structure

```
vendor-src/ksfraser-html/
├── src/Ksfraser/HTML/Elements/
│   ├── ActionButton.php           # Abstract base class
│   ├── EditButton.php             # Edit action button
│   ├── DeleteButton.php           # Delete action button with confirmation
│   ├── AddButton.php              # Add/create action button
│   └── CancelButton.php           # Cancel/go-back button
└── tests/
    └── (Unit tests for each button class)
```

## References

- **Commit (Submodule Setup):** feccb61
- **Commit (View Refactoring):** aba3899
- **GitHub Library:** https://github.com/ksfraser/html
- **SOLID Principles:** https://en.wikipedia.org/wiki/SOLID
- **Template Method Pattern:** https://refactoring.guru/design-patterns/template-method
