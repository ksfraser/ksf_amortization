# KSF Amortization - Unified Platform

Amortization calculation and loan management engine - shared across FrontAccounting, WordPress, and SuiteCRM.

## 📚 Architecture (Phase 24+)

This monorepo consolidates the core library and platform-specific modules into a unified codebase:

- **Core Library** (`src/Ksfraser/Amortizations/`): Shared business logic, calculators, and services
- **API Layer** (`src/Ksfraser/Api/`): REST endpoints, authentication, and response handling
- **FrontAccounting Module** (`modules/amortization/`): FA-specific adapter and integration
- **Frontend** (`frontend/`): Vue 3 SPA admin dashboard and user portal
- **Testing Infrastructure**: PHPUnit (backend), Vitest (frontend), comprehensive test suites
- **Deployment** (`ansible/`): Automated deployment for webserver and containerized FA

## ✨ Features

### Core Capabilities
- Amortization schedule calculation for all loan types (Auto, Mortgage, etc.)
- Flexible loan setup: amount financed, interest rate, payment frequency
- Interest calculation: daily, monthly, or custom frequencies
- Repayment schedule options: monthly, bi-weekly, weekly, custom
- Advanced calculations: compound interest, effective rates, periodic variations
- Performance optimization: caching layer, query optimization, N+1 detection
- API infrastructure: REST endpoints, response caching, rate limiting
- Extensibility: plugin system with hooks, webhook dispatcher
- Internationalization: multi-currency support, translations, compliance

### Platform-Specific Features
| Feature | FrontAccounting | WordPress | SuiteCRM |
|---------|---|---|---|
| GL Integration | ✅ | - | - |
| Admin Screens | ✅ | ✅ | ✅ |
| Payment Staging | ✅ | ✅ | ✅ |
| CRM Integration | ✅ | ✅ | ✅ |
| Email Notifications | ✅ | ✅ | ✅ |

## 🚀 Quick Start

### Clone the Unified Monorepo
```bash
git clone https://github.com/ksfraser/ksf_amortization.git
cd ksf_amortization
composer install
```

### Run Tests

**Backend (PHP):**
```bash
# FrontAccounting module tests
cd ksf_amortization && php ../vendor/bin/phpunit

# All core tests
php vendor/bin/phpunit --configuration phpunit.xml
```

**Frontend (Vue/Vitest):**
```bash
cd frontend && npm install && npm run test
```

### Local Development

```bash
# Start development servers
cd frontend && npm run dev        # Vue dev server (http://localhost:5173)
php -S localhost:8000 -t public/  # API server

# Watch tests
cd frontend && npm run test:watch
```

### Deploy to FrontAccounting

**Traditional nginx + PHP-FPM:**
```bash
ansible-playbook ansible/deploy-webserver.yml -i ansible/inventory.yml
```

**Containerized with Docker Compose:**
```bash
ansible-playbook ansible/deploy-frontaccounting-container.yml -i ansible/inventory.yml
```

## 📋 Requirements

### Core Library
- PHP 7.3 or higher
- brick/math for advanced calculations
- Composer for dependency management

### Platform Modules
Each platform module requires:
- Core library (ksfraser/amortizations-core)
- Platform-specific dependencies (handled by submodule composer.json)
- Platform installation (FrontAccounting, WordPress, or SuiteCRM)

## 🧪 Testing

### Run All Tests
```bash
composer test-all
```

### Run Core Tests Only
```bash
composer test
```

### Run Specific Platform Tests
```bash
composer test-fa      # FrontAccounting tests
composer test-wp      # WordPress tests
composer test-suite   # SuiteCRM tests
```

### Test Results
- **Current Status**: 317/317 tests passing (100%)
- **Coverage**: Core library fully tested
- **Platforms**: All platform adapters tested

## 📦 Project Structure

```
ksf_amortization/                       # Core library
├── src/Ksfraser/
│   ├── Amortizations/                 # Core calculations
│   ├── Api/                           # REST API layer
│   ├── Caching/                       # Caching abstraction
│   ├── Localization/                  # i18n, currencies, compliance
│   ├── Performance/                   # Query optimization
│   ├── Plugins/                       # Plugin system
│   ├── Security/                      # RBAC
│   └── Webhooks/                      # Event dispatching
├── tests/                             # Core tests
├── packages/                          # External libraries
├── composer.json                      # Core library definition
└── modules/                           # Platform adapters (submodules)
    ├── amortization/     → ksf_amortization_fa
    ├── wordpress/        → ksf_amortization_wp
    └── suitecrm/         → ksf_amortization_suitecrm
```

## 🏗️ Architecture

### 9-Layer SRP Architecture
1. **Models** - Loan, Event, Schedule
2. **Repositories** - Data access patterns
3. **Services** - Business logic
4. **Strategies** - Pluggable algorithms
5. **Handlers** - Event processing
6. **Controllers** - Platform interaction
7. **Providers** - Platform-specific data
8. **Views** - UI rendering
9. **API Layer** - REST endpoints

### Design Patterns
- **Strategy** - Interest calculations
- **Repository** - Data abstraction
- **Factory** - Object creation
- **Observer** - Event handling
- **Decorator** - Caching
- **Adapter** - Platform integration
- **Builder** - Complex objects

### Performance
- **Caching**: Memory and file-based
- **Query Optimization**: N+1 detection
- **Response Caching**: API TTL
- **Rate Limiting**: Endpoint protection

## 📖 Documentation

- [Architecture Overview](Architecture.md)
- [API Documentation](API_DOCUMENTATION.md)
- [Functional Specification](FunctionalSpecification.md)
- [Business Requirements](BusinessRequirements.md)
- [Deployment Guide](ProjectDocs/PRODUCTION_DEPLOYMENT_GUIDE.md)
- [Phase 21: Submodule Refactoring](PHASE21_SUBMODULE_REFACTORING_COMPLETE.md)

## 🧑‍💻 Development

### Clone for Development
```bash
# With all submodules
git clone --recursive https://github.com/ksfraser/ksf_amortization.git

# Or initialize after cloning
git submodule update --init --recursive
```

### Update Submodules
```bash
# Get latest from all submodules
git submodule update --recursive --remote

# Work on a submodule
cd modules/amortization
git checkout main
git pull origin main
```

### Code Standards
- PSR-12 for formatting
- PHPDoc for documentation
- Type hints required
- Unit tests required
- 100% pass rate required

### Contributing
1. Fork the repository
2. Create feature branch
3. Write tests
4. Run `composer test-all`
5. Submit pull request

## 📄 License

MIT License - see [LICENSE](LICENSE) file

## 🤝 Support

- **Issues**: [GitHub Issues](https://github.com/ksfraser/ksf_amortization/issues)
- **Docs**: [ProjectDocs/](ProjectDocs/) and module READMEs

## 📊 Version Info

- **Version**: 1.0.0-submodules
- **Status**: Production Ready
- **Tests**: 317/317 passing
- **Updated**: December 23, 2025

---

**KSF Amortization** - Multi-Platform Loan Management Engine
