# Implementation Plan: Fix Dual-Head Composer Structure

## Current State

- ✅ Composer configuration partially correct
- ✅ vendor-src/ksfraser-html exists as git submodule
- ✅ packages/ has path repositories  
- ⚠️  src/Ksfraser/HTML/ was removed (good)
- ⚠️  StylesheetManager properly in packages/ksf-amortizations-core/src/Ksfraser/Amortizations/Views/
- ❌ Tests still failing due to missing builder factories in HTML package
- ❌ View files trying to use HTML builders that don't have correct implementations

## The Real Problem

The view files are calling methods like:
```php
$heading = (new Heading(3))->setText('Reports');
$table = (new Table())->addClass('reporting-table');
$row->append($headerRow, $dataRow);
```

But the HTML package has:
- `HtmlHeading3` (requires HtmlElementInterface in constructor)
- `HtmlTable` (requires HtmlElementInterface)
- No generic `append()` method
- No `render()` method (has `getHtml()` instead)

## The Solution: Factory Pattern

The HTML package needs to provide convenience factories that abstract away constructor complexity.

### Step 1: Add Factory Classes to vendor-src/ksfraser-html

These factories should be in the HTML package (not amortizations-specific).

**vendor-src/ksfraser-html/src/Ksfraser/HTML/Elements/Heading.php**
```php
- Factory for HtmlHeading1-6
- Constructor takes level (1-6)
- Proxies setText(), render(), addClass(), etc. to underlying HtmlHeadingX
```

**vendor-src/ksfraser-html/src/Ksfraser/HTML/Elements/Table.php**
```php
- Extends HtmlTable directly for convenience
- Provides fluent append() method
- Has render() that calls getHtml()
```

**Other element factories**: TableRow, TableData, TableHeader, Button, Div, Form, etc.

### Step 2: Update HTML Package Composer to Include Factories

The factories become part of the HTML package's autoload PSR-4:
```
vendor-src/ksfraser-html/src/Ksfraser/HTML/Elements/
  ├── Heading.php (NEW - factory)
  ├── Table.php (NEW - convenience wrapper)
  ├── TableRow.php (NEW)
  ├── ...
  ├── HtmlHeading3.php (EXISTING)
  ├── HtmlTable.php (EXISTING)
  └── ...
```

### Step 3: Views Use the Factory Elements

**src/Ksfraser/Amortizations/Views/ReportingTable.php**
```php
use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;
use Ksfraser\HTML\Elements\StylesheetManager; // Local amortization-specific

class ReportingTable {
    public static function render(array $reports = []): string {
        $output = '';
        $output .= self::getStylesheets();
        
        // Use factories - these work via fluent interface
        $heading = (new Heading(3))->setText('Reports');
        $output .= $heading->getHtml();
        
        $table = (new Table())->addClass('reporting-table');
        // ... build with append(), etc.
        $output .= $table->getHtml();
        
        $output .= self::getScripts();
        return $output;
    }
}
```

### Step 4: Key Implementation Details

**Heading Factory** (most complex):
```php
class Heading implements Proxies {
    private HtmlHeadingX $element;  // Which X? 1-6
    
    public function __construct(int $level = 2) {
        $level = max(1, min(6, $level));
        $className = "\\Ksfraser\\HTML\\Elements\\HtmlHeading" . $level;
        $this->element = new $className(new HtmlString(''));
    }
    
    public function __call($method, $args) {
        return $this->element->$method(...$args);
    }
    
    public function getHtml(): string {
        return $this->element->getHtml();
    }
}
```

**Table, TableRow, etc. (simpler - direct extension)**:
```php
class Table extends HtmlTable {
    // Already have HtmlTable, just provide convenience
    public function render(): string {
        return $this->getHtml();
    }
}
```

### Step 5: Autoload Configuration

**vendor-src/ksfraser-html/composer.json** already has:
```json
"psr-4": {
    "Ksfraser\\HTML\\": "src/Ksfraser/HTML/",
    "Ksfraser\\HTML\\Elements\\": "src/Ksfraser/HTML/Elements/"
}
```

This means when we add factories to Elements/, they're automatically autoloadable.

### Step 6: Test the Chain

1. Run `composer install` at root
2. This installs vendor/ksfraser/html via path repository
3. vendor/autoload.php includes HTML package's autoload
4. Views can `use Ksfraser\HTML\Elements\Heading;`
5. Tests pass because Heading, Table, etc. exist

## Dependency Flow

```
Root composer.json
├─ requires: ksfraser/html (path: vendor-src/ksfraser-html)
│  └─ loaded in vendor/
│     └─ vendor/autoload.php includes it
│
├─ requires: ksfraser/amortizations-core (path: packages/ksf-amortizations-core)
│  ├─ requires: ksfraser/html (same as root)
│  ├─ src/Ksfraser/Amortizations/Views/StylesheetManager.php
│  │  └─ uses Ksfraser\HTML\Elements\Stylesheet (from HTML package)
│  └─ src/Ksfraser/Amortizations/Views/ReportingTable.php
│     └─ uses Ksfraser\HTML\Elements\Heading, Table, etc. (from HTML package)
│
└─ Tests
   └─ use vendor/autoload.php
      └─ can access all HTML package classes

```

## When to Switch to GitHub VCS

Once ksfraser/html is published to GitHub as https://github.com/ksfraser/html:

1. Change root composer.json repositories:
```json
{
    "type": "vcs",
    "url": "https://github.com/ksfraser/html.git"
}
```

2. Keep vendor-src git submodule for:
   - Development/contributions to HTML package
   - Testing changes before push
   - Reference implementation

3. Delete old path repository entry

4. Run `composer update`

This is the proper progression: path repos during development → VCS repos when published.

## Summary

✅ The structure is conceptually sound - three layers, git submodule, proper separation
❌ Implementation is incomplete - factory classes not in HTML package yet
🔧 Fix: Add factory/convenience classes to HTML package
✅ Tests will pass once factories exist and work properly

This is NOT a shortcut - it's the proper way to use the builder pattern with nested dependencies across reusable packages.
