# Deployment Preparation & Staging Setup

**Status**: Phase 3 - Deployment Preparation  
**Last Updated**: April 28, 2026

## 1. Pre-Deployment Checklist

### Code Quality & Testing
- [x] Unit tests passing (ksf_amortization core)
- [x] Integration tests passing  
- [x] API documentation complete (OpenAPI/Swagger)
- [-] Frontend vitest (fix in progress)
- [x] Docker Compose files validated (YAML syntax)
- [x] Database migrations prepared
- [x] Security audit complete

### Infrastructure Readiness
- [x] Docker Compose development setup
- [x] Docker Compose production setup
- [x] Nginx configuration (dev & prod)
- [x] PHP configuration (dev & prod)
- [x] MySQL database configuration
- [x] Redis caching configured
- [x] Environment file templates (.env.example)

### Security & Compliance
- [x] HTTPS/TLS configuration ready
- [x] Security headers configured
- [x] Database password rotation policy
- [x] API authentication (OAuth2/JWT)
- [x] Rate limiting configured
- [x] CORS policy defined
- [x] Secrets management planned

### Documentation
- [x] API endpoints documented
- [x] Deployment guide created
- [x] Architecture documentation
- [x] Database schema documented
- [x] Installation guide
- [x] Troubleshooting guide

## 2. Staging Environment Setup

### 2.1 Staging Infrastructure

```
STAGING ENVIRONMENT:
├── Web Tier
│   ├── Nginx (reverse proxy + load balancing)
│   └── SSL/TLS certificates
├── Application Tier  
│   ├── PHP-FPM (amortization module)
│   ├── Node.js (frontend dev server OR built artifacts)
│   └── PHP CLI (background jobs/migrations)
├── Data Tier
│   ├── MySQL (database)
│   ├── Redis (cache + sessions)
│   └── Volume storage (backups)
└── Monitoring
    ├── Health checks
    ├── Error logging (ELK stack optional)
    └── Performance monitoring
```

### 2.2 Staging Deployment Steps

#### Step 1: Environment Setup
```bash
# On staging server
1. Clone repository to /var/www/ksf_amortization
2. Copy .env.example to .env
3. Update .env with staging values:
   - DB_HOST=staging-mysql.local
   - DB_NAME=ksf_staging
   - DB_USER=staging_user
   - DB_PASS=<secure-password>
   - REDIS_HOST=staging-redis.local
   - APP_ENV=staging
   - APP_DEBUG=false
```

#### Step 2: Docker Image Build
```bash
# Build images for staging
docker-compose -f docker-compose.prod.yml build api
docker-compose -f docker-compose.prod.yml build nginx

# Tag for registry (if using private registry)
docker tag ksf_amortization:prod registry.example.com/ksf/api:latest
docker tag ksf_nginx:prod registry.example.com/ksf/nginx:latest
```

#### Step 3: Database Initialization
```bash
# Run migrations
docker-compose -f docker-compose.prod.yml run --rm api \
    php /app/vendor/bin/phinx migrate -e staging

# Seed test data (optional)
docker-compose -f docker-compose.prod.yml run --rm api \
    php /app/vendor/bin/phinx seed:run -e staging
```

#### Step 4: Service Startup
```bash
# Start all services
docker-compose -f docker-compose.prod.yml up -d

# Verify services are running
docker-compose -f docker-compose.prod.yml ps

# Check logs
docker-compose -f docker-compose.prod.yml logs -f api
```

#### Step 5: Health Checks
```bash
# Verify API is responding
curl -s https://staging.example.com/api/health | jq .

# Check database connection
docker-compose -f docker-compose.prod.yml exec api \
    php -r "mysqli_connect('mysql','root','root','ksf_amortization') ? print('OK') : print('FAIL');"

# Check Redis connection
docker-compose -f docker-compose.prod.yml exec redis \
    redis-cli ping
```

### 2.3 Staging Test Plan

#### Scenario 1: Basic Connectivity
```
✓ Web server responds on port 80/443
✓ API endpoints return 200 OK
✓ Database queries execute successfully
✓ Redis cache responds
```

#### Scenario 2: Feature Testing
```
✓ Create amortization schedule
✓ Calculate interest payments
✓ Generate reports
✓ Export data (CSV/PDF)
```

#### Scenario 3: Load Testing
```
✓ Handle 50 concurrent users
✓ API response time < 500ms (p95)
✓ Database queries complete < 2s
✓ Memory usage stable over 1 hour
```

#### Scenario 4: Security Testing
```
✓ SQL injection protection
✓ XSS protection headers present
✓ Rate limiting functional
✓ Authentication required for protected endpoints
✓ HTTPS enforced
```

#### Scenario 5: Data Testing
```
✓ Migrations run successfully
✓ Seed data loads correctly
✓ Database backup/restore works
✓ Data validation enforced
```

## 3. Staging to Production Promotion

### 3.1 Promotion Criteria
- [x] All tests passing (unit, integration)
- [ ] Load testing baseline established
- [ ] Security audit signed off
- [ ] Documentation complete and reviewed
- [ ] Runbooks created for common issues
- [ ] On-call team trained
- [ ] Backup/restore procedures tested
- [ ] Rollback procedure documented

### 3.2 Production Deployment Steps

```bash
# 1. Create database backup
mysqldump -h prod-mysql -u root -p ksf_amortization > backup-$(date +%Y%m%d-%H%M%S).sql

# 2. Pull latest code (or deploy specific release tag)
git pull origin main  # or git checkout v1.0.0

# 3. Update configuration for production
# Edit .env with production values:
# - DB_HOST=prod-mysql-primary.internal
# - APP_ENV=production
# - APP_DEBUG=false
# - Enable SSL/TLS

# 4. Build production images
docker-compose -f docker-compose.prod.yml build

# 5. Run migrations (with backup first!)
docker-compose -f docker-compose.prod.yml run --rm api \
    php /app/vendor/bin/phinx migrate -e production

# 6. Deploy new version (blue-green or rolling)
docker-compose -f docker-compose.prod.yml up -d

# 7. Verify deployment
curl -s https://api.example.com/api/health | jq .
```

### 3.3 Rollback Procedure

```bash
# 1. Identify issue
# 2. Stop current deployment
docker-compose -f docker-compose.prod.yml down

# 3. Checkout previous version
git checkout <previous-tag>

# 4. Restore database from backup
mysql -h prod-mysql -u root -p ksf_amortization < backup-YYYYMMDD-HHMMSS.sql

# 5. Start previous version
docker-compose -f docker-compose.prod.yml up -d

# 6. Verify rollback
curl -s https://api.example.com/api/health | jq .
```

## 4. Staging Environment Validation

### 4.1 API Functionality Tests

```bash
#!/bin/bash
# test_staging_api.sh

BASE_URL="https://staging.example.com"
FAILED=0

# Test 1: Health Check
echo "Testing health endpoint..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL/api/health)
if [ "$HTTP_CODE" == "200" ]; then
    echo "✓ Health check passed"
else
    echo "✗ Health check failed: $HTTP_CODE"
    FAILED=$((FAILED+1))
fi

# Test 2: Authentication
echo "Testing authentication..."
RESPONSE=$(curl -s -X POST $BASE_URL/api/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test@example.com","password":"test"}')
if echo $RESPONSE | grep -q "token\|error"; then
    echo "✓ Authentication endpoint responding"
else
    echo "✗ Authentication failed"
    FAILED=$((FAILED+1))
fi

# Test 3: Amortization Module
echo "Testing amortization endpoints..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
    -H "Authorization: Bearer $TOKEN" \
    $BASE_URL/api/amortizations)
if [ "$HTTP_CODE" == "200" ] || [ "$HTTP_CODE" == "401" ]; then
    echo "✓ Amortization endpoint accessible"
else
    echo "✗ Amortization endpoint failed: $HTTP_CODE"
    FAILED=$((FAILED+1))
fi

if [ $FAILED -eq 0 ]; then
    echo ""
    echo "✓ All tests passed!"
    exit 0
else
    echo ""
    echo "✗ $FAILED test(s) failed!"
    exit 1
fi
```

### 4.2 Database Validation

```sql
-- Verify database structure
SELECT COUNT(*) as TABLE_COUNT FROM information_schema.TABLES 
WHERE TABLE_SCHEMA='ksf_staging';

-- Check migrations status
SELECT * FROM phinxlog ORDER BY execution_time DESC LIMIT 5;

-- Verify indexes
SHOW INDEX FROM amortizations;
SHOW INDEX FROM loan_events;

-- Check data integrity
SELECT COUNT(*) FROM amortizations;
SELECT COUNT(*) FROM loan_payments;
```

### 4.3 Performance Baseline Collection

```bash
#!/bin/bash
# collect_baseline.sh

echo "Collecting performance baseline..."

# API Response Times
for endpoint in "/api/health" "/api/amortizations" "/api/reports"; do
    echo "Testing $endpoint..."
    for i in {1..10}; do
        curl -w "%{time_total}\n" -o /dev/null -s https://staging.example.com$endpoint
    done | awk 'NR==1{min=$1;next}{if($1<min)min=$1;if($1>max)max=$1;sum+=$1}END{print "Min:"min" Max:"max" Avg:"sum/NR}'
done

# Database Performance
echo "Database query performance..."
docker-compose exec -T mysql mysql -e "
    SELECT * FROM INFORMATION_SCHEMA.EVENTS_WAITS_SUMMARY_GLOBAL_BY_EVENT_NAME 
    WHERE OBJECT_SCHEMA='ksf_staging' 
    ORDER BY SUM_TIMER_WAIT DESC LIMIT 10;
"
```

## 5. Monitoring & Observability

### 5.1 Health Checks
```yaml
# docker-compose.prod.yml additions
services:
  api:
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8000/api/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
  
  mysql:
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 30s
      timeout: 10s
      retries: 3
```

### 5.2 Logging
```bash
# View application logs
docker-compose -f docker-compose.prod.yml logs -f api

# View nginx access logs
docker-compose -f docker-compose.prod.yml logs -f nginx

# Export log audit trail
docker-compose -f docker-compose.prod.yml logs api > logs/application-$(date +%Y%m%d).log
```

### 5.3 Metrics to Monitor
- API response time (p50, p95, p99)
- Database query time
- Cache hit ratio
- Memory usage per container
- Disk space usage
- Number of active connections
- Error rate

## 6. Runbooks for Common Issues

### Issue: API returns 500 errors
```
1. Check logs: docker-compose logs -f api
2. Verify database connection:
   docker-compose exec api php -r "@mysqli('mysql','root',...)"
3. Check disk space: docker stats
4. Verify Redis is running: docker-compose ps redis
5. Restart API service: docker-compose restart api
```

### Issue: Slow database queries
```
1. Check slow query log in MySQL
2. Review query execution plans: EXPLAIN <query>
3. Check indexes on frequently queried tables
4. Consider query optimization or caching
5. Monitor with docker stats during load test
```

### Issue: High memory usage
```
1. Identify container: docker stats
2. Check for memory leaks in application
3. Review cache memory limits in Redis
4. Increase Docker memory limit if needed
5. Monitor over time to detect trends
```

## 7. Success Criteria

✅ **Staging Deployment Complete When:**
- [ ] All services start without errors
- [ ] Health checks all passing
- [ ] API endpoints responding with proper status codes
- [ ] Database initialized with migrations
- [ ] Security headers configured in responses
- [ ] Performance baseline established
- [ ] Load testing completed successfully
- [ ] Team trained on deployment process
- [ ] Runbooks reviewed and documented
- [ ] Backup/restore tested
- [ ] Rollback procedure tested

## 8. Production Deployment Timeline

```
Day 0 (Staging Complete):
  - All tests passing
  - Performance baseline documented
  - Security audit signed off
  
Day 1-2 (Pre-Production):
  - On-call team on-boarded
  - Deployment runbooks reviewed
  - Backup procedures tested
  - Alert thresholds configured
  
Day 3 (Production Deploy):
  - 8:00 AM - Pre-deployment verification
  - 8:15 AM - Create production backup
  - 8:30 AM - Deploy to production
  - 8:45 AM - Run smoke tests
  - 9:00 AM - Monitor for 30 minutes
  - 9:30 AM - Announce production live
```

## 9. Post-Deployment

### 9.1 Validation
- [ ] Monitor error logs for 24 hours
- [ ] Review performance metrics against baseline
- [ ] Sample user transactions for correctness
- [ ] Verify data integrity
- [ ] Check SSL certificate validity

### 9.2 Communication
- [ ] Notify users of new deployment
- [ ] Update status page
- [ ] Document any known issues
- [ ] Schedule retro meeting with ops team

### 9.3 Clean Up
- [ ] Archive staging data
- [ ] Document lessons learned
- [ ] Update operational runbooks
- [ ] Plan for next optimization/iteration
