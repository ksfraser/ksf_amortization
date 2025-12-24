# PHP Version Compatibility Guide

## Overview

This project supports **PHP 7.3+** through a hybrid runtime version detection approach. Tests can use modern PHP features (like arrow functions from PHP 7.4+) while maintaining backward compatibility with PHP 7.3.

**Key Design:**
- No file duplication across versions
- Single test codebase with conditional logic
- Automatic detection at runtime
- Explicit skipping of incompatible tests

---

## Architecture

### Three-Layer Compatibility System

```
┌─────────────────────────────────────────────────┐
│         Test Code (Single Codebase)             │
│  - Uses arrow functions and modern syntax      │
│  - Via VersionCompatibility utilities           │
└──────────────┬──────────────────────────────────┘
               │
┌──────────────▼──────────────────────────────────┐
│       VersionCompatibility Utility               │
│  - Runtime PHP version detection                │
│  - Callback adaptation (fn vs function)         │
│  - Conditional code execution                   │
└──────────────┬──────────────────────────────────┘
               │
┌──────────────▼──────────────────────────────────┐
│    PHP Runtime (7.3 or 7.4+)                    │
│  - Executes appropriate code path               │
│  - Skips incompatible tests when needed         │
└─────────────────────────────────────────────────┘
```

### Core Components

#### 1. **VersionCompatibility Class** (`tests/VersionCompatibility.php`)

Utility class providing:
- **Version Detection:** `getPhpVersion()`, `isLegacy()`, `supportsArrowFunctions()`
- **Callback Adaptation:** `createCallback()`, `createMapCallback()`, `createReduceCallback()`
- **Conditional Execution:** `ifSupportsArrowFunctions()`, `skipIfLegacy()`, `skipIfModern()`

#### 2. **Enhanced BaseTestCase** (`tests/BaseTestCase.php`)

Convenience methods for tests:
- `skipIfLegacyPHP()` - Skip test on PHP 7.3
- `createCallback()` - Create version-aware callbacks
- `isModernPHP()`, `isLegacyPHP()` - Version checks
- `ifModernPHP()` - Conditional code blocks

#### 3. **PHPUnit Configurations**

- **`phpunit.xml`** - Default (PHP 7.4+ features allowed)
- **`phpunit-php73.xml`** - PHP 7.3 mode (excludes modern tests)

#### 4. **Composer Scripts**

```bash
composer test              # Default testing (current PHP version)
composer test-php74+       # Test with PHP 7.4+ features
composer test-php73        # Test PHP 7.3 compatibility
composer test-version      # Show PHP version and run tests
```

---

## Usage Patterns

### Pattern 1: Simple Arrow Function with Fallback

```php
// In test method:
$callback = $this->createCallback(
    fn($item) => $item['id'] === 5,
    function($item) { return $item['id'] === 5; }
);

$result = array_filter($items, $callback);
```

### Pattern 2: Skip Test on PHP 7.3

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Skip this test entirely if running on PHP 7.3
    $this->skipIfLegacyPHP('This test uses arrow functions');
}
```

### Pattern 3: Conditional Code Blocks

```php
// Execute different code paths based on PHP version
$this->ifModernPHP(
    function() {
        // PHP 7.4+ code
        $result = array_map(fn($x) => $x * 2, $data);
    },
    function() {
        // PHP 7.3 fallback
        $result = array_map(function($x) { return $x * 2; }, $data);
    }
);
```

### Pattern 4: Array Operations with Version Awareness

```php
// Array Filter
$evenNumbers = array_filter(
    [1, 2, 3, 4, 5],
    $this->createCallback(
        fn($n) => $n % 2 === 0,
        function($n) { return $n % 2 === 0; }
    )
);

// Array Map
$doubled = array_map(
    VersionCompatibility::createMapCallback(
        fn($x) => $x * 2,
        function($x) { return $x * 2; }
    ),
    [1, 2, 3]
);

// Array Reduce
$sum = array_reduce(
    [1, 2, 3, 4],
    VersionCompatibility::createReduceCallback(
        fn($carry, $item) => $carry + $item,
        function($carry, $item) { return $carry + $item; }
    ),
    0
);
```

### Pattern 5: Mark Tests for Specific Versions

```php
class MyTest extends BaseTestCase
{
    /**
     * @group php74
     */
    public function testModernFeature()
    {
        // This test uses arrow functions and modern syntax
        $result = array_filter($data, fn($x) => $x > 5);
        $this->assertTrue(count($result) === 3);
    }
    
    public function testLegacyCompatible()
    {
        // This test works on both PHP 7.3 and 7.4+
        // Use VersionCompatibility utilities when needed
        if ($this->isModernPHP()) {
            $result = array_filter($data, fn($x) => $x > 5);
        } else {
            $result = array_filter($data, function($x) { return $x > 5; });
        }
        $this->assertTrue(count($result) === 3);
    }
}
```

---

## Testing Workflow

### Testing on Current PHP Version

```bash
# Uses default phpunit.xml
composer test

# Or with detailed output
phpunit --verbose tests/
```

### Testing PHP 7.3 Compatibility

```bash
# Uses phpunit-php73.xml configuration
# Skips @group php74 tests automatically
composer test-php73

# Or manually
phpunit --configuration phpunit-php73.xml tests/
```

### Testing PHP 7.4+ Features

```bash
composer test-php74+
```

### Display PHP Version Info

```bash
# Shows PHP version and runs tests
composer test-version

# Shows detailed version info
php -v
php -r "echo json_encode(\Ksfraser\Amortizations\Tests\VersionCompatibility::getVersionInfo(), JSON_PRETTY_PRINT);"
```

---

## Version Detection Logic

### PHP Version Detection

```php
// In VersionCompatibility::initialize()
$version = explode('.', PHP_VERSION);
$phpVersion = (float)($version[0] . '.' . $version[1]);
// Results: 7.3, 7.4, 8.0, 8.1, etc.
```

### Supported Version Mapping

| PHP Version | Arrow Functions | Status |
|------------|-----------------|--------|
| 7.3        | ❌ Not supported | Legacy |
| 7.4        | ✅ Supported    | Modern |
| 8.0+       | ✅ Supported    | Modern |

### Automatic Decisions

```php
// VersionCompatibility automatically chooses:
if (version >= 7.4) {
    return $arrowFunction;  // Use modern syntax
} else {
    return $legacyCallback; // Use traditional closure
}
```

---

## Migration Path: Converting Tests

### Step 1: Identify Tests Using Arrow Functions

```bash
# Search for arrow functions in tests
grep -r "fn(" tests/ --include="*.php"
```

### Step 2: Add Version Compatibility Wrappers

**Before:**
```php
$result = array_filter($data, fn($x) => $x > 5);
```

**After:**
```php
$result = array_filter(
    $data,
    $this->createCallback(
        fn($x) => $x > 5,
        function($x) { return $x > 5; }
    )
);
```

### Step 3: Mark Tests with @group php74

```php
class MyTest extends BaseTestCase
{
    /**
     * @group php74
     * Uses arrow functions and modern syntax
     */
    public function testArrowFunctions()
    {
        // ...
    }
}
```

### Step 4: Test Both Versions

```bash
composer test-php73      # Should skip @group php74
composer test-php74+     # Should run all tests
```

---

## Best Practices

### ✅ DO

- Use `VersionCompatibility` utilities for version-aware code
- Mark version-specific tests with `@group php74`
- Provide fallbacks when using modern syntax
- Test on both PHP 7.3 and 7.4+ regularly
- Document version requirements in docblocks

### ❌ DON'T

- Mix arrow functions and traditional closures without wrapping
- Forget to provide `$legacyCallback` parameter
- Assume all tests work on all PHP versions without testing
- Use PHP 8.0+ features (match, union types, etc.) without version checks
- Ignore `VersionCompatibility::skipIfLegacy()` warnings

---

## Debugging Version Issues

### Check Current Version

```bash
php -v
# Output: PHP 7.3.0, 7.4.0, 8.0.0, etc.
```

### Get Detailed Version Info

```php
$info = VersionCompatibility::getVersionInfo();
echo json_encode($info, JSON_PRETTY_PRINT);
```

Output:
```json
{
  "php_version": "7.4.0",
  "php_version_short": 7.4,
  "is_legacy": false,
  "supports_arrow_functions": true,
  "php_major": 7,
  "php_minor": 4,
  "php_release": 0
}
```

### Run Tests with Debug Output

```bash
# Verbose output showing skipped tests
phpunit --verbose --debug tests/

# Only show PHP 7.3 compatibility tests
phpunit --configuration phpunit-php73.xml tests/ --verbose
```

---

## Integration with CI/CD

### GitHub Actions Example

```yaml
# .github/workflows/test.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php-version: ['7.3', '7.4', '8.0', '8.1']
    
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
      
      - run: composer install
      
      # Test current version
      - run: composer test
      
      # For PHP 7.3, also test compatibility mode
      - if: matrix.php-version == '7.3'
        run: composer test-php73
```

---

## Common Issues & Solutions

### Issue: Arrow Function Parse Error on PHP 7.3

**Problem:**
```
Parse error: syntax error, unexpected 'fn' on line X
```

**Solution:**
- Use `VersionCompatibility::createCallback()` wrapper
- Provide fallback traditional closure
- Mark test with `@group php74`

### Issue: Test Passes on 7.4 but Fails on 7.3

**Problem:**
- Test uses unguarded arrow function or modern syntax
- Missing fallback callback

**Solution:**
```php
// Use conditional execution
$this->ifModernPHP(
    fn() => array_filter($data, fn($x) => $x > 5),
    fn() => array_filter($data, function($x) { return $x > 5; })
);
```

### Issue: Can't Run Test on Unsupported Version

**Problem:**
- Test requires PHP 7.4+ features that can't be wrapped
- No way to run on PHP 7.3

**Solution:**
```php
protected function setUp(): void
{
    parent::setUp();
    // Completely skip test on PHP 7.3
    $this->skipIfLegacyPHP('Requires match expression from PHP 8.0');
}
```

---

## Additional Resources

- **PHPUnit Docs:** https://phpunit.de/
- **PHP Version Features:** https://www.php.net/releases/
- **Arrow Functions (PHP 7.4):** https://www.php.net/manual/en/functions.arrow.php
- **Match Expression (PHP 8.0):** https://www.php.net/manual/en/control-structures.match.php

---

## Maintenance

### Regular Version Checks

```bash
# Check for unguarded arrow functions
grep -r "fn(" tests/ --include="*.php" | grep -v "createCallback\|createMapCallback\|createReduceCallback"

# Run full test suite across versions
for PHP_VERSION in 7.3 7.4 8.0 8.1; do
    docker run --rm -v $(pwd):/app php:$PHP_VERSION-cli \
        /bin/sh -c "cd /app && composer install && composer test"
done
```

### Update Version Detection

When adding support for new PHP versions:

1. Update `VersionCompatibility::initialize()` thresholds
2. Add new version-specific group (`@group php80`, etc.)
3. Update this documentation
4. Run full test suite

---

**Last Updated:** 2025-12-20  
**Version Compatibility:** PHP 7.3 - 8.2+  
**Status:** Production Ready
