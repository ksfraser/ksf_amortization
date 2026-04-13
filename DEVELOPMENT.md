# Development Guide

## Environment Setup

### Requirements

- PHP 7.3+ (8.0+ recommended)
- MySQL 5.7+ or SQLite for local dev
- Node.js 18+ for frontend
- Composer for PHP dependency management
- npm for JavaScript packages

### Initial Setup

```bash
# Clone repository
git clone https://github.com/ksfraser/ksf_amortization.git
cd ksf_amortization

# PHP dependencies
composer install

# Frontend dependencies
cd frontend
npm install
cd ..

# Setup database (if using MySQL)
mysql -u root -p < migrations/migration_20251216_001_query_optimization_indexes.sql
mysql -u root -p < migrations/migration_20251216_002_denormalized_interest.sql
```

### Configuration

Create `.env` files:

**Root `.env` (PHP API):**
```
DB_HOST=localhost
DB_NAME=ksf_amortization
DB_USER=root
DB_PASS=
LOG_LEVEL=debug
CACHE_DRIVER=array
```

**Frontend `.env` (if needed):**
```
VITE_API_URL=http://localhost:8000
VITE_APP_NAME=KSF Amortization
```

## Local Development Workflow

### Start Development Servers

**Terminal 1 - PHP API:**
```bash
cd /path/to/ksf_amortization
php -S localhost:8000 -t public/
# API available at http://localhost:8000/api/
```

**Terminal 2 - Vue Frontend:**
```bash
cd frontend
npm run dev
# Frontend available at http://localhost:5173
```

### Run Tests

**Backend Tests:**
```bash
# All PHP tests
php vendor/bin/phpunit

# Specific test file
php vendor/bin/phpunit tests/Unit/Security/OAuth2/OAuth2ControllerTest.php

# Specific FA module tests
cd ksf_amortization && php ../vendor/bin/phpunit
```

**Frontend Tests:**
```bash
cd frontend

# Single run
npm run test

# Watch mode (re-runs on file changes)
npm run test:watch

# With UI
npm run test:ui

# Coverage report
npm run test:coverage
```

## Code Structure

### Adding a New Calculator

1. Create class in `src/Ksfraser/Amortizations/Calculators/`:
```php
namespace Ksfraser\Amortizations\Calculators;

class MyCalculator
{
    public function calculate($params)
    {
        // Implementation
        return $result;
    }
}
```

2. Add unit test in `tests/Unit/Amortizations/Calculators/`:
```php
use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Calculators\MyCalculator;

class MyCalculatorTest extends TestCase
{
    public function testCalculate()
    {
        $calc = new MyCalculator();
        $result = $calc->calculate(['rate' => 0.05]);
        $this->assertEquals(expected, $result);
    }
}
```

3. Integrate into service layer if needed
4. Expose via API controller if client-facing

### Adding a New API Endpoint

1. Define route in `src/Ksfraser/Api/routes.php`:
```php
'POST|/api/my-endpoint' => [MyController::class, 'handleRequest'],
```

2. Create controller in `src/Ksfraser/Api/Controllers/`:
```php
namespace Ksfraser\Api\Controllers;

class MyController extends BaseController
{
    public function handleRequest()
    {
        $validated = $this->validate([
            'param1' => 'required|string',
        ]);
        
        $result = $this->service->doSomething($validated);
        
        return $this->json(200, ['data' => $result]);
    }
}
```

3. Add tests in `tests/Unit/Api/`:
```php
class MyControllerTest extends TestCase
{
    public function testEndpoint()
    {
        $response = $this->postJson('/api/my-endpoint', ['param1' => 'value']);
        $this->assertEquals(200, $response->status());
    }
}
```

### Adding a Vue Component

1. Create component in `frontend/src/components/`:
```vue
<template>
  <div class="my-component">
    <h1>{{ title }}</h1>
    <button @click="handleClick">{{ buttonText }}</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const title = ref('My Component')
const buttonText = 'Click Me'

const handleClick = () => {
  console.log('Clicked!')
}
</script>

<style scoped>
.my-component {
  padding: 1rem;
}
</style>
```

2. Add test in `frontend/tests/unit/components/`:
```js
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import MyComponent from '@/components/MyComponent.vue'

describe('MyComponent', () => {
  it('renders correctly', () => {
    const wrapper = mount(MyComponent)
    expect(wrapper.text()).toContain('My Component')
  })

  it('handles click', async () => {
    const wrapper = mount(MyComponent)
    await wrapper.find('button').trigger('click')
    // assertions
  })
})
```

## Git Workflow

### Branch Strategy

```
main (stable, deployed)
  ├── feature/feature-name (features)
  ├── fix/issue-name (bugfixes)
  └── docs/documentation (documentation)
```

### Committing Changes

```bash
# Create feature branch
git checkout -b feature/my-feature

# Make changes and test
git add .
git commit -m "description of change"

# Push and create pull request
git push origin feature/my-feature
```

### Commit Message Format

```
<type>: <subject>

<body (optional)>

<footer (optional)>
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

Example:
```
feat: add daily interest calculation

Calculate interest on a daily compounding basis with
configurable day-count conventions (Actual/Actual,
30/360, etc).

Closes #123
```

## Database Development

### Creating Migrations

Place SQL files in `migrations/` with naming convention:
```
migration_YYYYMMDD_NNN_description.sql
```

Example:
```sql
-- migration_20260412_001_add_compliance_table.sql
CREATE TABLE compliance_audits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id)
);
CREATE INDEX idx_loan_audit ON compliance_audits(loan_id);
```

### Running Migrations

Manual (during development):
```bash
mysql -u root -p ksf_amortization < migrations/migration_*.sql
```

### Schema Inspection

```bash
# View current schema
mysql -u root -p -e "DESCRIBE ksf_amortization.loans;"

# Export current schema
mysqldump -u root -p --no-data ksf_amortization > schema.sql
```

## Debugging

### Enable Debug Logging

In PHP code:
```php
error_log(json_encode($data), 3, '/tmp/app.log');
```

View logs:
```bash
tail -f /tmp/app.log
```

### Browser DevTools

Chrome/Firefox:
- Network tab: View API requests/responses
- Console: JavaScript errors
- Application: Local storage, cookies

### VS Code Debugger

Install PHP Debug extension, add to `.vscode/launch.json`:
```json
{
    "name": "PHP Debug",
    "type": "php",
    "request": "launch",
    "port": 9003,
    "pathMapping": {
        "/": "${workspaceRoot}/"
    }
}
```

## Performance Tuning

### Database Queries

Check slow queries:
```bash
mysql -u root -p -e "SELECT * FROM mysql.slow_log;"
```

Index frequently queried columns:
```sql
CREATE INDEX idx_loan_client ON loans(client_id);
ANALYZE TABLE loans;
```

### Frontend

Bundle analysis:
```bash
cd frontend && npm run build -- --analyze
```

### Caching

Query result caching in services:
```php
$cache = new ArrayCache(); // or Redis
$key = md5('schedule_' . $loanId);
if ($cached = $cache->get($key)) {
    return $cached;
}
$result = $calculator->generate();
$cache->set($key, $result, 3600); // 1 hour
return $result;
```

## Common Tasks

### Running Full Test Suite

```bash
# PHP tests
php vendor/bin/phpunit --configuration phpunit.xml

# FA module tests
cd ksf_amortization && php ../vendor/bin/phpunit

# Frontend tests
cd frontend && npm run test
```

### Generate API Documentation

```bash
# OpenAPI spec already in openapi.json
# Serve with Swagger UI:
npm install -g swagger-ui-dist
swagger-ui-dist openapi.json
```

### Build for Production

```bash
# Frontend production build
cd frontend && npm run build

# Output in frontend/dist/

# PHP (no build needed, but optimize autoloader)
composer install --optimize-autoloader --no-dev
```

### Creating Database Backup

```bash
mysqldump -u root -p ksf_amortization > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Resetting Local Database

```bash
# Drop and recreate
mysql -u root -p -e "DROP DATABASE ksf_amortization; CREATE DATABASE ksf_amortization;"

# Rerun migrations
mysql -u root -p ksf_amortization < migrations/migration_*.sql
```

## Troubleshooting

### PHP Tests Fail to Run

**Problem**: `Cannot open file vendor/autoload.php`

**Solution**: Run `composer install` from root directory

### Frontend Tests Hang

**Problem**: Tests appear to hang with no output

**Solution**: 
```bash
# Clean node_modules
rm -rf node_modules
npm install

# Clear vitest cache
rm -rf node_modules/.vite
```

### API Returns 400 Bad Request

**Problem**: Request fails validation

**Solution**: Check request body matches schema:
```bash
curl -X POST http://localhost:8000/api/endpoint \
  -H "Content-Type: application/json" \
  -d '{"required_field": "value"}'
```

### Database Connection Failed

**Problem**: `SQLSTATE[HY000] [1045] Access denied`

**Solution**: Check `.env` credentials and MySQL is running:
```bash
mysql -u root -p -e "SELECT 1;"
```

## Resources

- [PSR-12 Code Style](https://www.php-fig.org/psr/psr-12/)
- [Vue 3 Composition API](https://v3.vuejs.org/guide/composition-api-introduction.html)
- [Vitest Documentation](https://vitest.dev/)
- [Ansible Documentation](https://docs.ansible.com/)
- [MySQL Best Practices](https://dev.mysql.com/doc/refman/5.7/en/optimization.html)
