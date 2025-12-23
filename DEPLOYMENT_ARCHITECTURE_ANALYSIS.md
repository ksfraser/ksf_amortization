# Code Duplication & Deployment Architecture Analysis

**Date:** December 23, 2025  
**Status:** Analysis & Recommendation

---

## Current Structure Issues

### 1. Duplicate Code Problem

You're correct - there IS significant duplication:

```
modules/
├── amortization/          ← FA-specific module
├── fa/                    ← Platform adapter
├── fa_mock/               ← Testing/mocking
└── (other providers)

src/Ksfraser/
├── Amortizations/         ← Core logic (DUPLICATE of modules/amortization)
├── fa/                    ← Platform adapter (DUPLICATE of modules/fa)
├── suitecrm/              ← Platform adapter
├── wordpress/             ← Platform adapter
└── (new namespaces)
```

**The Problem:**
- `src/Ksfraser/` contains the **"single source of truth"** (canonical code)
- `modules/amortization/` appears to be an **older deployment structure**
- Both contain similar code, but only `src/` is current (based on our Phase 17-20 work)

---

## Deployment Architecture Analysis

### Current Deployment Flow

1. **Repository State:** Everything lives in `src/Ksfraser/`
2. **Deployment:** Manual copy to `modules/amortization/`
3. **Composer:** Run `composer dump-autoload` in root
4. **FA Module:** Copy `modules/amortization/` into FrontAccounting

**Issues with Current Approach:**
- ❌ Manual sync between `src/` and `modules/`
- ❌ Easy for `modules/` version to become stale
- ❌ No automated deployment
- ❌ hooks.php doesn't run `composer install`

### Composer Setup Status

**Root composer.json:**
- Correctly references `packages/` for modular composition
- PSR-4 autoload points to `src/Ksfraser/`
- Requires `ksfraser/amortizations-*` packages

**modules/amortization/composer.json:**
- Outdated reference model
- Points to GitHub (not local packages)
- Not used in actual deployment

---

## Hooks.php Current Implementation

```php
function install() {
    // ✅ Checks for vendor/autoload.php
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }
    
    // ✅ Runs installer
    $installer = new \Ksfraser\Amortizations\AmortizationModuleInstaller($db, $dbPrefix);
    $installer->install();
}
```

**Issues:**
- ❌ Does NOT run `composer install`
- ❌ Assumes autoload.php already exists
- ❌ No error handling if composer hasn't been run
- ❌ No composer.lock included for reproducible builds

---

## Recommended Solution

### Option A: Keep Modular Structure (RECOMMENDED)

**Advantages:**
- Each platform can be deployed independently
- Cleaner separation of concerns
- Works with monorepo package approach

**Implementation:**
1. Keep `src/Ksfraser/` as canonical source
2. Update `modules/amortization/composer.json` to use local packages
3. Update hooks.php to run composer commands
4. Create deployment script for packaging

**Action Items:**
```bash
# 1. Update hooks.php to run composer
# 2. Create modules/amortization/composer.lock
# 3. Create deployment automation script
# 4. Update INSTALL.md with new process
```

### Option B: Flatten to Single Module

**Advantages:**
- No duplication
- Single deployment package
- Simpler for FA

**Disadvantages:**
- Loses modularity
- Harder for other platforms
- Not ideal for package distribution

---

## What Needs to be Fixed

### 1. **hooks.php - Add Composer Installation**

Currently missing:
```php
// Need to add this to install() method:
if (!file_exists(__DIR__ . '/vendor')) {
    shell_exec('composer install --no-dev --working-dir=' . __DIR__);
}
```

### 2. **modules/amortization/composer.json - Update References**

Currently points to GitHub, should point to local packages:
```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../../vendor-src/ksfraser-html"
    },
    {
      "type": "path",
      "url": "../../packages/ksf-amortizations-core"
    }
  ]
}
```

### 3. **Create Deployment Automation**

Need a script that:
- Copies `src/` code to `modules/amortization/src/` (if needed)
- Runs `composer install` in modules/amortization/
- Validates all dependencies
- Creates deployment package

### 4. **Update INSTALL.md**

Current instructions are outdated - need to document:
- New composer-based installation
- Automatic dependency resolution
- Platform-specific installation paths

---

## Test Current State

Let me verify the current deployment readiness:
