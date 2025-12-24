# Git Submodule Setup Guide

## Overview

The `ksfraser/html` library is now managed as a Git submodule in `vendor-src/ksfraser-html/`. This allows local development and contributions while keeping the library as a separate Git repository.

## Initial Setup (After Clone)

```bash
# Clone the main repository with submodules
git clone --recurse-submodules https://github.com/ksfraser/amortizations.git

# Or if you've already cloned without submodules:
cd ksf_amortization
git submodule update --init --recursive
```

## Everyday Workflow

### Viewing Submodule Status

```bash
# See submodule status
git status
git submodule status

# View submodule details
cat .gitmodules
```

### Updating Submodule (Pull Latest from Origin)

```bash
# Update to latest version of ksfraser/html
git submodule update --remote

# Or update all submodules
git submodule update --remote --recursive
```

### Making Changes to HTML Library

```bash
# Navigate to submodule
cd vendor-src/ksfraser-html

# Create a feature branch
git checkout -b feature/new-button-class

# Make your changes (e.g., create new button classes)
# ...

# Commit your changes
git add .
git commit -m "Add new button class: XyzButton"

# Push to GitHub
git push origin feature/new-button-class

# Create a pull request on GitHub and wait for review

# Go back to main project
cd ../..
```

### Pulling Changes from Submodule into Main Project

```bash
# After your PR is merged in ksfraser/html, update the main project
git submodule update --remote vendor-src/ksfraser-html

# This will point to the latest main branch of ksfraser/html
git add vendor-src/ksfraser-html
git commit -m "Update ksfraser/html submodule to latest version"
git push
```

## Using Submodule Classes in Project

The submodule classes are automatically available when composer autoload is included:

```php
<?php

// Composer autoload (already included in your project)
require_once 'vendor/autoload.php';

// Use submodule classes directly
use Ksfraser\HTML\Elements\EditButton;
use Ksfraser\HTML\Elements\DeleteButton;
use Ksfraser\HTML\Elements\HtmlString;

// Create specialized buttons
$editBtn = new EditButton(new HtmlString('Edit'), '123', 'editFunc()');
```

## Common Tasks

### Check Submodule Version

```bash
cd vendor-src/ksfraser-html
git log --oneline -n 5
```

### Compare Submodule with Remote

```bash
git submodule status
# Shows: <hash> vendor-src/ksfraser-html [<branch>]
# If there's a '+' prefix, you have local changes
```

### Undo Changes in Submodule

```bash
cd vendor-src/ksfraser-html
git checkout .
git clean -fd
cd ../..
```

### Switch Submodule to Specific Commit

```bash
cd vendor-src/ksfraser-html
git checkout <commit-hash>
cd ../..
git add vendor-src/ksfraser-html
git commit -m "Pin ksfraser/html to specific version"
```

## Troubleshooting

### Submodule Shows "new commits"

```bash
# This means the submodule is ahead of what's registered in the main project
# Either update it in main project:
git add vendor-src/ksfraser-html
git commit -m "Update submodule reference"

# Or pull the latest:
git submodule update --remote
```

### Submodule Directory is Empty

```bash
# Reinitialize submodule
git submodule update --init --recursive
```

### Merge Conflicts in Submodule Pointer

```bash
# The .gitmodules or submodule hash can conflict
# Resolve manually by editing the pointer in the main project:
git add vendor-src/ksfraser-html
git commit --no-edit
git push
```

## Best Practices

1. **Keep commits separate** - Changes to submodule should be separate commits from main project
2. **Test before merging** - Always test new button classes before pushing
3. **Update documentation** - Update [HTML_BUTTON_ARCHITECTURE.md](./HTML_BUTTON_ARCHITECTURE.md) when adding new classes
4. **Use meaningful branches** - Use `feature/`, `bugfix/`, etc. prefixes in submodule
5. **Communicate changes** - Document enhancements in PR descriptions for code review
6. **Keep main project on latest** - Regularly update submodule to latest stable version

## Project Structure

```
ksf_amortization/
├── vendor-src/
│   └── ksfraser-html/                 # Git submodule (separate repo)
│       ├── .git/
│       ├── src/Ksfraser/HTML/
│       │   ├── Elements/
│       │   │   ├── ActionButton.php
│       │   │   ├── EditButton.php
│       │   │   ├── DeleteButton.php
│       │   │   ├── AddButton.php
│       │   │   └── CancelButton.php
│       │   └── ... (other HTML classes)
│       └── composer.json
├── .gitmodules                        # Submodule configuration
├── composer.json
├── HTML_BUTTON_ARCHITECTURE.md
└── SUBMODULE_SETUP.md                 # This file
```

## References

- **Git Submodules Documentation:** https://git-scm.com/book/en/v2/Git-Tools-Submodules
- **ksfraser/html Repository:** https://github.com/ksfraser/html
- **Current Submodule Commit:** See `git submodule status`
