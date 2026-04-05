# Development Workflow - Packagist Integration Guide

**Date:** April 3, 2026  
**Version:** 1.0

## Overview

This project uses a hybrid approach for dependency management:
- **Local packages** (in ../ksfraser-html and other software-devel packages) managed via Composer path repositories
- **External packages** (brick/math, dompdf, etc.) from Packagist
- **Platform-specific modules** (WordPress, SuiteCRM, FrontAccounting) as separate extensible submodules

## Local Package Development

### For ksfraser/html and Similar Local Packages

#### Setup (One-time)
```bash
# Ensure the package exists in software-devel/
# The composer.json will automatically resolve it via path repository
composer install

# Verify the path repository is configured
composer config repositories
```

#### Development Workflow
```bash
# 1. Make changes to the local package
cd ../ksfraser-html
# ... edit files ...

# 2. Commit and push changes
git add .
git commit -m "Your changes"
git push origin main

# 3. Update composer to use latest changes
cd ../ksf_amortization
composer update ksfraser/html

# 4. Verify changes are reflected
vendor/bin/phpunit  # Run tests
```

#### Creating a New Local Package
```bash
# 1. Create package structure in software-devel/
mkdir ../my-new-package
cd ../my-new-package

# 2. Initialize package with composer.json
composer init

# 3. Add to path repository in ksf_amortization/composer.json:
"repositories": [
    {
        "type": "path",
        "url": "../my-new-package",
        "options": {
            "symlink": false
        }
    }
]

# 4. Require the package
cd ../ksf_amortization
composer require vendor/my-new-package:*

# 5. Commit and push both packages
```

## Platform-Specific Modules

### Directory Structure
```
ksf_amortization/
├── modules/
│   ├── wordpress/     # Platform-specific implementation
│   ├── suitecrm/      # Platform-specific implementation
│   └── amortization/  # Platform-specific implementation (FrontAccounting)
├── src/               # Core shared code
└── vendor-src/        # Internal shared libraries
```

### Module Development
Each platform module can be independently developed:
```bash
# Test a specific module
composer test-wp      # WordPress module
composer test-suite   # SuiteCRM module
composer test-fa      # FrontAccounting module

# Or all at once
composer test-all
```

## Dependency Resolution Priority

Composer resolves dependencies in this order:
1. **Path repositories** (local development packages) - uses symlink or copy
2. **VCS repositories** (GitHub repos) - clones specific branch
3. **Packagist** (public registry) - downloads published versions

## Publishing to Packagist

When a local package is ready for production:

```bash
# 1. Ensure package meets Packagist requirements
cd ../ksfraser-html
composer validate

# 2. Create GitHub release with proper version tag
git tag v1.0.0
git push origin v1.0.0

# 3. Submit to Packagist (if not auto-synced)
# Visit: https://packagist.org/packages/submit

# 4. Update composer.json to require from Packagist
cd ../ksf_amortization

# Update from path repository to Packagist:
# Remove from repositories section (remove the path entry)
# Keep: "ksfraser/html": "^1.0"

composer update ksfraser/html
```

## Troubleshooting

### Package not loading from path repository
```bash
# Clear composer cache
composer clear-cache
composer update

# Verify path repository exists
ls ../ksfraser-html/composer.json
```

### Changes not reflected after update
```bash
# If using symlink, check symbolic link
ls -la vendor/ksfraser/

# Force dump-autoload
composer dump-autoload
```

### Switching between local and Packagist versions
```bash
# To use local version (development)
# Ensure path repository is in composer.json

# To use Packagist version (production)
# Remove path repository from composer.json
# Run: composer update
```

## Best Practices

1. **Keep local packages independent** - Each should have own tests and documentation
2. **Use semantic versioning** - Follow [semver.org](https://semver.org/)
3. **Tag releases** - Use Git tags for versions (v1.0.0, v1.0.1, etc.)
4. **Update composer.lock** - Commit lock file for consistency
5. **Document dependencies** - Keep README.md current for each package
