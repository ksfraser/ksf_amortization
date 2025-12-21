# KSF Amortization - Composer & File Structure Analysis

## Current Dual-Head Problem

### The Issue
We have TWO sources of the HTML package:
1. **vendor-src/ksfraser-html/** - Git submodule pointing to GitHub (source of truth)
2. **src/Ksfraser/HTML/** - Local copies of parts of the HTML package (duplication)
3. **vendor-src-created files** - Factory classes and managers we added (Heading, Table, etc.)

This is problematic because:
- Changes in vendor-src don't reflect in src
- Hardlinks in packages/ point to src but vendor-src is the actual development location
- Composer autoload was pointing to vendor-src instead of installed vendor/ directory
- No clear separation between what's reusable (HTML package) vs. amortization-specific (StylesheetManager)

## Proper Structure

### Three-Layer Architecture

```
┌─────────────────────────────────────────────────────────┐
│  Root Project (ksf_amortization)                        │
│  - composer.json (aggregates dependencies)              │
│  - Point repositories to submodules & packages/         │
│  - Main entry point for testing/integration             │
└─────────────────────────────────────────────────────────┘
         ↓                    ↓                     ↓
    ┌─────────┐        ┌──────────────┐      ┌─────────┐
    │ ✅ HTML │        │ ✅ Amortiz   │      │Platform │
    │Package  │        │ Core Package │      │Packages │
    │(Reusable│        │(Reusable)    │      │(FA,WP   │
    │)        │        │              │      │SuiteCRM)│
    └─────────┘        └──────────────┘      └─────────┘
   GitHub repo         GitHub repo            GitHub repo
   (submodule)         (path repo)            (path repos)
```

### Layer 1: HTML Package (vendor-src/ksfraser-html → GitHub)

**Purpose**: Generic HTML builder pattern elements
**Location**: vendor-src/ksfraser-html/ (git submodule)
**Published On**: GitHub (https://github.com/ksfraser/html.git)

**Contains**:
- HtmlElement, HtmlString, HtmlAttribute, etc. (base builder pattern)
- Specific elements: HtmlHeading3, HtmlTable, HtmlButton, etc.
- HtmlEmptyElement (for self-closing tags)
- Stylesheet class (extends HtmlElement, generates <link> tags)

**Does NOT contain**:
- View-specific code
- Amortization logic
- Platform-specific implementations

### Layer 2: Amortizations Core (packages/ksf-amortizations-core → GitHub)

**Purpose**: Platform-agnostic amortization business logic & views
**Location**: packages/ksf-amortizations-core/
**Requires**: ksfraser/html (composer dependency from GitHub)

**Contains**:
- StylesheetManager (amortization-specific stylesheet config)
- View files (ReportingTable, LoanTypeTable, etc.)
- Business logic (calculations, models, etc.)

**Uses HTML Package**:
- Imports Stylesheet, HtmlHeading, Table, etc.
- Builds HTML using fluent builder pattern
- `$stylesheet->render()` or `$table->getHtml()` for output

### Layer 3: Platform Packages (packages/ksf-amortizations-*/ → GitHub)

**Purpose**: Platform-specific implementations (FrontAccounting, WordPress, SuiteCRM)
**Location**: packages/ksf-amortizations-frontaccounting/, etc.
**Requires**: ksfraser/amortizations-core (composer dependency)

**Contains**:
- Platform adapters
- Platform-specific routes/endpoints
- Platform-specific styling

## Composer Configuration

### Root composer.json Repositories

```json
"repositories": [
    {
        "type": "path",
        "url": "vendor-src/ksfraser-html"
    },
    {
        "type": "path", 
        "url": "packages/ksf-amortizations-core"
    },
    {
        "type": "path",
        "url": "packages/ksf-amortizations-frontaccounting"
    }
]
```

**Why path repos during development?**
- Allows testing changes to all packages locally
- Changes in vendor-src instantly available via git submodule
- When packages are published to GitHub, switch to VCS repositories
- Git submodule keeps vendor-src synchronized with GitHub repo

### Root composer.json Autoload

```json
"autoload": {
    "psr-4": {
        "Ksfraser\\Amortizations\\": "src/Ksfraser/Amortizations/"
    }
}
```

**Key Point**: Do NOT autoload HTML package directly here!
- It's a composer dependency
- Let composer/autoload.php handle it after `composer install`
- This keeps separation of concerns

### Each Package's composer.json

Example: packages/ksf-amortizations-core/composer.json

```json
{
    "name": "ksfraser/amortizations-core",
    "require": {
        "php": ">=7.4",
        "ksfraser/html": "^1.0"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../../vendor-src/ksfraser-html"
        }
    ],
    "autoload": {
        "psr-4": {
            "Ksfraser\\Amortizations\\": "src/Ksfraser/Amortizations/"
        }
    }
}
```

**Each package declares its dependencies explicitly**
- ksf-amortizations-core needs ksfraser/html
- Platform packages need ksfraser/amortizations-core
- Composer resolves the dependency tree

## File Structure - Clean

```
ksf_amortization/
├── .gitmodules                          (git config)
├── composer.json                        (root: aggregator only)
├── vendor-src/
│   └── ksfraser-html/                   (GIT SUBMODULE → GitHub repo)
│       ├── .git/
│       ├── composer.json
│       └── src/Ksfraser/HTML/
│           ├── Elements/
│           │   ├── Heading.php          (Factory for HtmlHeading1-6)
│           │   ├── Table.php            (Alias for HtmlTable)
│           │   ├── TableRow.php
│           │   ├── TableData.php
│           │   ├── Button.php
│           │   ├── Stylesheet.php       (Extends HtmlElement, renders <link>)
│           │   └── ... (other elements)
│           └── HtmlElement.php, HtmlString.php, etc.
│
├── packages/
│   ├── ksf-amortizations-core/          (Contains views + business logic)
│   │   ├── composer.json
│   │   └── src/Ksfraser/Amortizations/
│   │       ├── Views/
│   │       │   ├── StylesheetManager.php (AMORT-SPECIFIC: stylesheet config)
│   │       │   ├── ReportingTable.php   (Uses HTML builders)
│   │       │   ├── LoanTypeTable.php
│   │       │   └── ...
│   │       └── (business logic, models, etc.)
│   │
│   ├── ksf-amortizations-frontaccounting/
│   ├── ksf-amortizations-wordpress/
│   └── ksf-amortizations-suitecrm/
│
├── src/Ksfraser/Amortizations/          (Root-level amortizations code)
│   ├── Views/                            (Hardlinks to packages/ksf-amortizations-core/src)
│   │   └── ... (symlinks/hardlinks)
│   └── (other code)
│
├── vendor/                               (Generated by composer install)
│   ├── ksfraser/
│   │   ├── html/                         (Installed HTML package)
│   │   ├── amortizations-core/
│   │   └── ...
│   └── autoload.php                      (PHP autoloader - USE THIS)
│
└── vendor-src/ksfraser-html/.git/        (Development - for git submodule)
```

## Resolution Steps

### 1. Remove src/Ksfraser/HTML directory
- It's duplication
- HTML package comes ONLY from vendor-src (via composer)

### 2. Keep vendor-src as git submodule
- Don't commit the files directly
- .gitmodules already configured correctly
- `git submodule update --init --recursive` to clone

### 3. Update Root composer.json Autoload
- REMOVE lines that point to vendor-src paths
- ONLY autoload src/Ksfraser/Amortizations
- Let composer/autoload.php handle HTML package

### 4. Factory classes (Heading.php, Table.php, etc.)
- Put them in vendor-src/ksfraser-html/src/Ksfraser/HTML/Elements/
- They're part of HTML package (reusable for any project)
- They extend/wrap the HtmlXxx classes

### 5. StylesheetManager stays in packages
- It's amortization-specific configuration
- Not reusable elsewhere
- Location: packages/ksf-amortizations-core/src/Ksfraser/Amortizations/Views/StylesheetManager.php

### 6. Hardlinks for development
- Keep hardlinks from packages/ → src/
- Allows single-edit development
- Verified with fsutil

### 7. GitHub Deployment
- vendor-src/ksfraser-html pushes to: https://github.com/ksfraser/html
- packages/ksf-amortizations-core pushes to: https://github.com/ksfraser/amortizations-core
- Root project remains private or published separately

When GitHub repos exist, change root composer.json repositories to:
```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/ksfraser/html.git"
    },
    {
        "type": "vcs",
        "url": "https://github.com/ksfraser/amortizations-core.git"
    }
]
```

This is the proper, non-hackish way to manage it.
