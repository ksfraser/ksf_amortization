# Deployment Validation Checklist

Use this checklist during and after deployment to staging/production environments.

## Pre-Deployment Checklist (Before Deploy)

### Code & Configuration
- [ ] Latest code is tagged/released: `git tag -l | tail -5`
- [ ] Deployment guide reviewed: `cat DEPLOYMENT_GUIDE.md`
- [ ] Environment file prepared with correct values
- [ ] Database backup created (production only)
- [ ] Previous version tag recorded for rollback

### Infrastructure
- [ ] Docker/Docker Compose available: `docker --version && docker-compose --version`
- [ ] Sufficient disk space: `df -h` (min 20GB free)
- [ ] Required ports available: 80, 443, 3306, 6379
- [ ] Network connectivity verified
- [ ] DNS records updated (if applicable)

### Team & Process
- [ ] On-call team on standby
- [ ] Notification channel open (Slack/Teams)
- [ ] Rollback decision criteria defined
- [ ] Monitoring dashboard ready

## Deployment Execution Checklist

### Phase 1: Environment Setup
```bash
# Create working directory
cd /var/www/ksf_amortization

# Clone/pull repository
git clone https://github.com/ksfraser/ksf_amortization.git .
# OR
git pull origin main

# Copy environment template
cp .env.example .env

# Edit environment variables
nano .env
# Required vars:
# - DB_HOST, DB_NAME, DB_USER, DB_PASS
# - REDIS_HOST, REDIS_PORT
# - APP_ENV (staging/production)
# - APP_DEBUG (false for production)
```

- [ ] Code available locally
- [ ] .env file configured
- [ ] Database credentials correct
- [ ] App environment set correctly

### Phase 2: Database Preparation
```bash
# Verify database connectivity
docker-compose -f docker-compose.prod.yml exec mysql \
    mysqladmin ping -h localhost -u root -p$DB_PASS

# Run migrations (TEST on staging first!)
docker-compose -f docker-compose.prod.yml run --rm api \
    php vendor/bin/phinx migrate -e staging  # or production
```

- [ ] Database accessible
- [ ] Migrations started successfully
- [ ] No migration errors in logs
- [ ] Database schema created

### Phase 3: Service Deployment
```bash
# Build images (if not pre-built)
docker-compose -f docker-compose.prod.yml build

# Start services
docker-compose -f docker-compose.prod.yml up -d

# Check service status
docker-compose -f docker-compose.prod.yml ps
# Expected output: all services with status "Up"
```

- [ ] Build completed without errors
- [ ] All services started
- [ ] No "Exit" or "dead" containers
- [ ] Service logs reviewed for errors

### Phase 4: Health Verification
```bash
# Check individual service health
docker-compose -f docker-compose.prod.yml ps

# API health endpoint
curl -s http://localhost:8000/api/health | jq .

# Database check
docker-compose -f docker-compose.prod.yml exec api \
    php -r 'mysqli_connect("mysql","root","$DB_PASS") ? print("DB OK") : print("DB FAIL");'

# Redis check
docker-compose -f docker-compose.prod.yml exec redis \
    redis-cli ping
# Expected: PONG
```

- [ ] All containers running (`docker-compose ps`)
- [ ] API health endpoint returns 200 OK
- [ ] Database connected successfully
- [ ] Redis responding to PING
- [ ] No error messages in logs

### Phase 5: Application Health
```bash
# Frontend accessible
curl -s http://localhost/ | grep -i "<html\|<!doctype" || echo "HTML not found"

# API endpoints responding
for endpoint in /api/health /api/amortizations; do
    echo "Testing $endpoint..."
    curl -s http://localhost$endpoint | jq .
done

# Check response headers (security headers)
curl -i http://localhost/api/health | grep -i "x-frame-options\|strict-transport-security"
```

- [ ] Frontend loads (HTML response 200 OK)
- [ ] /api/health returns valid JSON
- [ ] /api/amortizations returns 200 or 401 (should auth)
- [ ] Security headers present

## Post-Deployment Validation (After Deploy)

### Immediate (First 5 minutes)
```bash
# Real-time log monitoring
docker-compose -f docker-compose.prod.yml logs -f api &
docker-compose -f docker-compose.prod.yml logs -f nginx &

# Monitor error rate
watch 'docker-compose -f docker-compose.prod.yml logs --tail=20 api | grep -i "error\|exception"'

# Check resource usage
docker stats --no-stream
```

- [ ] No "Fatal error", "Exception", or "SQL error" in logs
- [ ] Nginx logs show 200/301 responses (no 500 errors)
- [ ] CPU usage stable (< 80%)
- [ ] Memory usage stable (< 500MB per service)

### Short Term (First 30 minutes)
```bash
# Run smoke tests against APIs
bash tests/smoke_test.sh

# Check database for data consistency
docker-compose -f docker-compose.prod.yml exec mysql mysql -e "
    USE ksf_amortization;
    SELECT COUNT(*) as loan_count FROM loans;
    SELECT COUNT(*) as event_count FROM events;
"

# Verify backup created (production)
ls -lh backups/ | head -1
```

- [ ] All smoke tests passing
- [ ] Database contains expected data
- [ ] Backup file created (production only)
- [ ] No anomalies in application logs

### Extended (30 minutes - 2 hours)
```bash
# Load test simulation
# Option 1: Using Apache Bench
ab -n 1000 -c 50 http://localhost/api/health

# Option 2: Using curl loop
for i in {1..100}; do
    time curl -s http://localhost/api/health > /dev/null
done

# Check database performance
docker-compose -f docker-compose.prod.yml exec mysql mysql -e "
    SHOW PROCESSLIST;
    SHOW STATUS LIKE 'Questions';
"

# Memory leak check (run for several minutes)
watch -n 5 'docker stats --no-stream | head -5'
```

- [ ] API response times consistent (p95 < 500ms)
- [ ] Load handling without errors
- [ ] Database query count normal
- [ ] Memory usage not growing (no leaks)

### Functionality Tests
```bash
# Test core amortization features
# 1. Create amortization
curl -X POST http://localhost/api/amortizations \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{
        "principal": 100000,
        "rate": 5.5,
        "term": 360,
        "start_date": "2026-01-01"
    }'

# 2. Get amortization schedules
curl http://localhost/api/amortizations \
    -H "Authorization: Bearer $TOKEN"

# 3. Get payment schedule
curl http://localhost/api/amortizations/1/payments \
    -H "Authorization: Bearer $TOKEN"

# 4. Generate report
curl http://localhost/api/reports/amortization/1?format=csv \
    -H "Authorization: Bearer $TOKEN" \
    -o report.csv
```

- [ ] Create amortization endpoint works
- [ ] List amortizations returns data
- [ ] Payment schedule endpoint functional
- [ ] Report export working (CSV/PDF)

## Security Validation

### HTTPS/TLS
```bash
# Verify SSL certificate
curl -v https://localhost/api/health 2>&1 | grep -i "subject\|issued"

# Check certificate validity
openssl s_client -connect localhost:443 -servername localhost 2>/dev/null | \
    openssl x509 -noout -dates
```

- [ ] SSL certificate valid (not self-signed in production)
- [ ] Certificate expiration > 30 days away
- [ ] Certificate matches domain

### Security Headers
```bash
curl -i http://localhost/api/health | grep -i "strict-transport-security\|x-content-type-options\|x-frame-options"
```

- [ ] HSTS header present (`strict-transport-security`)
- [ ] Content-Type header present
- [ ] X-Frame-Options present

### Authentication
```bash
# Test protected endpoint without auth (should fail)
curl http://localhost/api/amortizations
# Expected: 401 Unauthorized

# Test with invalid token (should fail)
curl -H "Authorization: Bearer invalid_token" http://localhost/api/amortizations
# Expected: 401 Unauthorized
```

- [ ] Unauthenticated requests rejected
- [ ] Invalid tokens rejected
- [ ] Protected endpoints require auth

## Rollback Checklist

If any critical issue detected, execute rollback:

### Immediate (Stop New Deployment)
- [ ] Stop affected services: `docker-compose down`
- [ ] Verify old version ready: `git log --oneline | head -3`
- [ ] Database backups identified and accessible

### Rollback Execution
```bash
# 1. Identify previous stable version
git tag -l | sort -V | tail -3

# 2. Checkout previous version
git checkout v1.0.0  # replace with stable tag

# 3. Restore database from backup (PRODUCTION ONLY!)
mysql < backup-2026-04-28-120000.sql

# 4. Start previous version services
docker-compose -f docker-compose.prod.yml up -d

# 5. Verify rollback successful
curl -s http://localhost/api/health | jq .
```

- [ ] Checkout completed successfully
- [ ] Database restored (if needed)
- [ ] Services running on previous version
- [ ] Health checks passing
- [ ] Application responding correctly

## Monitoring After Deployment

### First 24 Hours
- [ ] Error rate baseline established
- [ ] Performance metrics compared to baseline
- [ ] No spike in CPU/Memory usage
- [ ] All critical endpoints frequently checked
- [ ] User reports monitored

### First Week
- [ ] Daily error log review
- [ ] Performance trend analysis
- [ ] Database query analysis
- [ ] Security log review
- [ ] Backup verification

### Ongoing
- [ ] Weekly performance reviews
- [ ] Monthly security audits
- [ ] Quarterly capacity planning
- [ ] Continuous log monitoring

## Success Criteria

✅ **Deployment Successful When:**
- [x] All services running without errors
- [x] Health checks passing
- [x] API endpoints responding correctly
- [x] Database connected and queries executing
- [x] Security headers present
- [x] Performance within baseline
- [x] No critical errors in logs
- [x] Core functionality working
- [x] All team members notified
- [x] Monitoring dashboard active

## Troubleshooting Quick Reference

| Symptom | Possible Cause | Fix |
|---------|---|---|
| Services not starting | Config file issues | Validate: `docker-compose config` |
| Database connection failed | Wrong credentials | Check .env file, verify DB running |
| API returns 500 | PHP error | Check logs: `docker-compose logs api` |
| Slow responses | Load/memory issue | Check: `docker stats` |
| HTTPS fails | Certificate issue | Verify: `openssl s_client -connect ...` |
| 403/404 errors | Routing issue | Check nginx config, service network |

## Contact & Escalation

**Deployment Issues**: DevOps Team  
**API Issues**: Backend Team  
**Frontend Issues**: Frontend Team  
**Database Issues**: DBA/Data Team  
**Security Issues**: Security Team  

**Emergency**: Page on-call engineer immediately

---

**Last Updated**: April 28, 2026  
**Next Review**: May 28, 2026  
**Version**: 1.0
