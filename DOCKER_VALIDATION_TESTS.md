# Docker Infrastructure Validation & Testing Guide

## Prerequisites

### Installation
- **Docker Desktop for Windows** (https://www.docker.com/products/docker-desktop/)
  - Includes Docker Engine + Docker Compose
  - Requires Windows 10/11 Pro/Enterprise with WSL2
  
- Or **Docker via WSL2**:
  ```bash
  # In your WSL2 terminal
  curl -fsSL https://get.docker.com -o get-docker.sh
  sudo sh get-docker.sh
  ```

## Validation Steps

### 1. Docker Compose Syntax Validation
```bash
docker-compose config  # Validates docker-compose.yml syntax
docker-compose -f docker-compose.prod.yml config  # Validates prod compose
```

**Expected Output:**
- No errors
- Full YAML structure displayed

### 2. Dockerfile Validation
```bash
# Check for common Dockerfile issues
docker build --dry-run -f Dockerfile.dev .
docker build --dry-run -f Dockerfile.prod .
```

### 3. Development Environment Tests

#### 3.1 Start Dev Environment
```bash
make dev  # or: docker-compose up --build
```

**Expected:**
- All 5 services start (php, nginx, mysql, redis, node)
- No fatal errors in logs

#### 3.2 Wait for Readiness (MySQL crucial)
```bash
# MySQL ready when you see: "ready for connections"
docker-compose logs mysql

# Redis ready when you see: "Ready to accept connections"
docker-compose logs redis

# Node dev server ready when you see: "Local: http://..."
docker-compose logs node
```

**Typical startup time:** 45-60 seconds

#### 3.3 Health Check Tests
```bash
# Test MySQL
docker-compose exec mysql mysql --version
curl -I http://localhost/api/health

# Test Redis
docker-compose exec redis redis-cli ping  # Response: PONG

# Test PHP
curl http://localhost/api/health
# Response: {"status":"ok","service":"api","timestamp":"..."}

# Test Vue Frontend
curl http://localhost/
# Response: HTML with Vue app

# Test Nginx proxying
curl -I http://localhost/app  # Should serve from Node (Vue dev server)
curl -I http://localhost/api/health  # Should proxy to PHP
```

**Expected Status Codes:**
- `/api/health` → 200 OK
- `/` → 200 OK (HTML from Vue or static SPA)
- `/app` → 200 OK (proxied to Node dev server)

#### 3.4 Service-to-Service Communication
```bash
# PHP connecting to MySQL
docker-compose exec php php -r "
\$mysqli = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
echo \$mysqli->server_info;
"

# PHP connecting to Redis
docker-compose exec php php -r "
\$redis = new Redis();
\$redis->connect(getenv('REDIS_HOST'), 6379);
echo \$redis->ping();
"

# Node can access Node.js packages
docker-compose exec node npm list vue  # Should show vue@3.x installed
```

#### 3.5 Persistent Data Test
```bash
# Check MySQL persists across restarts
docker-compose exec mysql mysql -u ksf_user -p ksf_amortization -e "SHOW TABLES;"

# Restart containers
docker-compose restart mysql

# Verify data still exists
docker-compose exec mysql mysql -u ksf_user -p ksf_amortization -e "SHOW TABLES;"
```

### 4. Production Environment Tests

#### 4.1 Environment Variable Validation
```bash
# Must have .env with production values
cp .env.example .env

# Edit .env with production passwords:
# - MYSQL_ROOT_PASSWORD
# - DB_PASS
# - REDIS_PASS

# Simulate missing env var (should fail gracefully)
docker-compose -f docker-compose.prod.yml config
```

**Expected:** Shows validation errors or full config if all vars present

#### 4.2 Production Build & Start
```bash
docker-compose -f docker-compose.prod.yml up --build
```

**Expected:**
- All services start
- API responds at http://localhost:8000/api/health
- Frontend served as static SPA
- No development files in image

#### 4.3 Production Image Size
```bash
docker images | grep ksf-amortization

# Expected final image size: ~200-250 MB for PHP+deps
# Compare to dev which is larger (Composer dev packages included)
```

#### 4.4 Production Security Checks
```bash
# Nginx should require HTTPS (check nginx-prod.conf)
curl -I http://localhost:8000/api/health
# Expected: 301 Moved Permanently to https://

# Check security headers present
curl -I https://localhost:8000/api/health 2>/dev/null | grep -i "strict-transport-security\|x-content-type-options"
```

### 5. Test Execution

#### 5.1 PHP Unit Tests
```bash
make test-php
# or: docker-compose exec php php vendor/bin/phpunit

# Expected: 
# - 3 passing (AdminSelectorsViewTest)
# - Total tests: 3
```

#### 5.2 Vue/Frontend Tests
```bash
make test-vue
# or: docker-compose exec node npm run test -- --run

# Expected:
# - All component tests pass
# - No syntax errors from vitest hang issue
```

#### 5.3 Integration Tests
```bash
# Test Full API Workflow
docker-compose exec php php -r "
include 'src/bootstrap.php';
\$api = new Ksfraser\Api\ApiController();

// Test health endpoint
\$response = \$api->health();
echo json_encode(\$response);
"

# Test Database + Cache + API
curl -X POST http://localhost/api/amortization \
  -H "Content-Type: application/json" \
  -d '{"principal":100000,"rate":5,"term":360}'
```

### 6. Performance Baseline

#### 6.1 Response Time Tests
```bash
# Single request
time curl http://localhost/api/health

# 100 concurrent requests (using Apache Bench)
ab -n 100 -c 10 http://localhost/api/health
# Expected: ~100-200ms avg response time

# Load test with hey (if installed: go get -u github.com/rakyll/hey)
hey -n 1000 -c 50 http://localhost/api/health
```

#### 6.2 Memory Usage
```bash
docker stats
# Monitor: PHP memory, MySQL memory, Redis memory
# Expected: PHP <50MB, MySQL <100MB, Redis <10MB
```

#### 6.3 Database Query Performance
```bash
docker-compose exec mysql mysql -u ksf_user -p ksf_amortization -e "SELECT COUNT(*) FROM amortization_schedules;"

# Check indexes are used
docker-compose exec mysql mysql -u ksf_user -p ksf_amortization -e "EXPLAIN SELECT * FROM amortization_schedules WHERE id = 1;"
```

### 7. Log Analysis

#### 7.1 Check for Errors
```bash
docker-compose logs --tail=100 php 2>&1 | grep -i error
docker-compose logs --tail=100 mysql 2>&1 | grep -i error
docker-compose logs --tail=100 nginx 2>&1 | grep -i error
```

#### 7.2 Verify Expected Logs
```bash
# MySQL migrations should auto-run
docker-compose logs mysql | grep "initialized"

# PHP should load without errors
docker-compose logs php | grep -i "ready\|listening"

# Nginx should start without errors
docker-compose logs nginx | grep -i "ready\|listening"
```

### 8. Cleanup & Verification

#### 8.1 Clean Shutdown
```bash
make down  # or: docker-compose down
```

**Expected:**
- All containers stop gracefully
- No error messages

#### 8.2 Full Cleanup
```bash
make docker-clean  # or: docker-compose down -v
```

**Expected:**
- All containers removed
- All volumes removed
- System prune removes dangling images

## Test Result Documentation

After running these tests, create `DOCKER_VALIDATION_RESULTS.md`:

```markdown
# Docker Validation Results

## Date: YYYY-MM-DD
## Tester: [Name]
## Environment: Windows/Mac/Linux + Docker Desktop v[version]

### Development Environment ✓/✗
- [ ] docker-compose up completes successfully
- [ ] All 5 services healthy
- [ ] MySQL ready in <30s
- [ ] Redis ping returns PONG
- [ ] PHP can connect to MySQL
- [ ] PHP can connect to Redis
- [ ] Nginx proxies correctly
- [ ] Vue dev server accessible

### Production Environment ✓/✗
- [ ] docker-compose -f docker-compose.prod.yml up works
- [ ] Environment variables enforced
- [ ] Image builds in <5 minutes
- [ ] Final image size: ___ MB
- [ ] HTTPS redirect working
- [ ] Security headers present

### Test Execution ✓/✗
- [ ] PHP tests: 3/3 passing
- [ ] Vue tests: X/X passing
- [ ] No vitest hang
- [ ] Coverage reports generated

### Performance ✓/✗
- [ ] API response time: ___ ms
- [ ] Concurrent 100/10 requests: ___ req/s
- [ ] Memory usage acceptable
- [ ] Database queries optimized

### Issues Found
1. [Issue] → [Resolution]
2. [Issue] → [Resolution]

### Recommendations
1. [Recommendation for deployment/optimization]
```

## Troubleshooting Guide

### Docker Compose Won't Start
**Symptom:** `ERROR: Service 'X' failed to start`  
**Diagnosis:**
- Check ports: `netstat -ano | findstr :3306` (Windows), `lsof -i :3306` (Mac/Linux)
- Check logs: `docker-compose logs X`
- Verify .env exists: service might be missing env vars

**Solution:**
```bash
# Free up port
# On Windows: netstat -ano | findstr :3306 → Get PID → taskkill /PID [PID] /F
docker-compose down -v  # Full cleanup
docker-compose up --build  # Fresh start
```

### MySQL Won't Initialize
**Symptom:** MySQL container exits after start  
**Diagnosis:**
```bash
docker-compose logs mysql | tail -20
```

**Common Causes:**
- Volume permission issues: `chmod 777 [volume-path]`
- Corrupted data: `docker-compose down -v` (removes old data)
- Bad environment variables

### PHP Can't Connect to MySQL
**Symptom:** Connection refused error  
**Diagnosis:**
```bash
# Check MySQL is running
docker-compose ps | grep mysql

# Test connection from PHP container
docker-compose exec php mysql -h mysql -u ksf_user -p ksf_amortization -e "SELECT 1;"
```

### Vitest Still Hangs in Docker
**Symptom:** `docker-compose exec node npm run test` hangs  
**Diagnosis:**
- Check Node version: `docker-compose exec node node --version`
- Check vitest installed: `docker-compose exec node npm list vitest`
- Try direct: `docker-compose exec node npx vitest --version`

**Solutions:**
1. Increase timeout: `npm run test -- --run --timeout=30000`
2. Different reporter: `npx vitest --reporter=verbose`
3. Check file system: Might be WSL2 file sync issue

### Nginx Proxy Not Working
**Symptom:** `curl http://localhost/api/health` returns 502 Bad Gateway  
**Diagnosis:**
- Check PHP container running: `docker-compose ps`
- Check PHP listening: `docker-compose logs php | grep "listening"`
- Test direct connection: `docker-compose exec nginx curl http://api:9000/api/health`

**Solution:**
```bash
# Verify service name in docker-compose.yml matches nginx-dev.conf upstream
# Restart nginx
docker-compose restart nginx
```

## Next Steps

1. **Install Docker Desktop** (if not already done)
2. **Run validation tests** in sequence (estimated 30-45 minutes total)
3. **Document results** in DOCKER_VALIDATION_RESULTS.md
4. **Fix issues** as they arise
5. **Commit successful configuration**:
   ```bash
   git add docker-compose.* Dockerfile.* nginx-*.conf php-*.ini .env.example Makefile DOCKER_VALIDATION_TESTS.md
   git commit -m "Phase 26b: Docker Infrastructure - complete with validation guide"
   git tag phase-26b-docker-complete
   git push origin && git push origin --tags
   ```

## References

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [Production Deployment Checklist](./DEPLOYMENT_GUIDE.md#docker-orchestration)
