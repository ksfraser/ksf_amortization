# Docker Compose TDD Test Plan

## Test Strategy: Test Scenarios → Implementation → Validation

### Phase 1: Test Scenarios (Define Expected Behaviors)

#### Scenario Group 1: Compose File Validation
- **1.1**: Development compose YAML syntax is valid
- **1.2**: Production compose YAML syntax is valid  
- **1.3**: All required services defined in dev compose
- **1.4**: All required services defined in prod compose
- **1.5**: Environment variables properly configured

#### Scenario Group 2: Development Environment
- **2.1**: All 5 dev services start without errors
- **2.2**: MySQL service reaches health check ready
- **2.3**: Redis service responds to PING
- **2.4**: PHP service loads without fatal errors
- **2.5**: Nginx service starts and listens on ports 80/443
- **2.6**: Node dev server starts on port 5173
- **2.7**: Services communicate across network

#### Scenario Group 3: Production Environment
- **3.1**: Production compose validates with environment variables
- **3.2**: All 4 prod services start without errors
- **3.3**: Environment variable validation enforced (missing vars fail)
- **3.4**: API health check responds when services ready
- **3.5**: Frontend static files served correctly
- **3.6**: HTTPS redirect working (HTTP → HTTPS)

#### Scenario Group 4: Data Persistence
- **4.1**: MySQL data survives container restart
- **4.2**: Redis data survives container restart
- **4.3**: Migrations auto-run on database initialization
- **4.4**: Volume mounts functional in dev mode

#### Scenario Group 5: Service Integration
- **5.1**: PHP can connect to MySQL via network
- **5.2**: PHP can connect to Redis via network
- **5.3**: Nginx correctly proxies to PHP backend
- **5.4**: Nginx correctly proxies to Node dev server
- **5.5**: All containers on same network can communicate

#### Scenario Group 6: Performance & Load
- **6.1**: API response time < 500ms for simple calls
- **6.2**: Nginx handles 100 concurrent requests
- **6.3**: Memory usage within expected bounds (dev)
- **6.4**: No memory leaks in 5-minute load test

#### Scenario Group 7: Security (Production)
- **7.1**: No development files in production image
- **7.2**: Production image runs as non-root user
- **7.3**: HTTPS headers present in prod responses
- **7.4**: Security headers configured (HSTS, CSP, X-Frame-Options)

#### Scenario Group 8: Error Recovery
- **8.1**: Containers restart automatically if killed
- **8.2**: Services recover after temporary network disconnection
- **8.3**: Database recovers from connection loss
- **8.4**: Application handles service unavailability gracefully

### Phase 2: Test Implementation

Tests are implemented in:
- `tests/Docker/ValidateCompose.ps1` - PowerShell test runner
- `tests/Docker/scenarios.json` - Test scenario definitions
- `tests/Docker/helpers.ps1` - Test helper functions

### Phase 3: Validation

Run tests with:
```powershell
# Run all docker tests
.\tests\Docker\ValidateCompose.ps1

# Run specific scenario group
.\tests\Docker\ValidateCompose.ps1 -Group "Compose File Validation"

# Run with detailed output
.\tests\Docker\ValidateCompose.ps1 -Verbose

# Generate HTML report
.\tests\Docker\ValidateCompose.ps1 -Report
```

## Test Coverage Matrix

| Scenario | Dev | Prod | Tool | Status |
|----------|-----|------|------|--------|
| 1.1 | ✓ | - | docker-compose config | TODO |
| 1.2 | - | ✓ | docker-compose config | TODO |
| 1.3 | ✓ | - | yaml check | TODO |
| 1.4 | - | ✓ | yaml check | TODO |
| 1.5 | - | ✓ | env validation | TODO |
| 2.1 | ✓ | - | docker-compose up | TODO |
| 2.2 | ✓ | - | healthcheck | TODO |
| 2.3 | ✓ | - | redis-cli ping | TODO |
| 2.4 | ✓ | - | php check | TODO |
| 2.5 | ✓ | - | curl probe | TODO |
| 2.6 | ✓ | - | curl probe | TODO |
| 2.7 | ✓ | - | exec PHP-MySQL | TODO |
| 3.1 | - | ✓ | compose validate | TODO |
| 3.2 | - | ✓ | docker-compose up | TODO |
| 3.3 | - | ✓ | env check | TODO |
| 3.4 | - | ✓ | curl health | TODO |
| 3.5 | - | ✓ | curl SPA | TODO |
| 3.6 | - | ✓ | curl redirect | TODO |
| 4.1 | ✓ | - | mysql query | TODO |
| 4.2 | ✓ | - | redis get | TODO |
| 4.3 | ✓ | - | check tables | TODO |
| 4.4 | ✓ | - | ls check | TODO |
| 5.1 | ✓ | ✓ | php mysqli | TODO |
| 5.2 | ✓ | ✓ | php redis | TODO |
| 5.3 | ✓ | ✓ | curl api | TODO |
| 5.4 | ✓ | - | curl app | TODO |
| 5.5 | ✓ | ✓ | nc test | TODO |
| 6.1 | ✓ | - | time curl | TODO |
| 6.2 | ✓ | - | load test | TODO |
| 6.3 | ✓ | - | docker stats | TODO |
| 6.4 | ✓ | - | sustained test | TODO |
| 7.1 | - | ✓ | inspect image | TODO |
| 7.2 | - | ✓ | ps check | TODO |
| 7.3 | - | ✓ | curl headers | TODO |
| 7.4 | - | ✓ | header check | TODO |
| 8.1 | ✓ | ✓ | restart test | TODO |
| 8.2 | ✓ | ✓ | network fail | TODO |
| 8.3 | ✓ | ✓ | db reconnect | TODO |
| 8.4 | ✓ | ✓ | graceful degrade | TODO |

## Success Criteria

✅ **All scenarios passing**:
- Compose files valid YAML
- Dev environment starts with all 5 services
- Prod environment starts with all 4 services (with .env)
- Services communicate successfully
- Performance within acceptable bounds
- Security hardening verified

## Timeline

- Phase 1: Test Scenarios (DONE - this document)
- Phase 2: Test Implementation (IN PROGRESS)
- Phase 3: Validation & Execution (NEXT)
