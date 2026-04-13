# Deployment Architecture Test Plan (TDD)

## Phase 26: Deployment Architecture Documentation

This document defines the test cases and requirements for deployment architecture before implementation.

## Deployment Scenarios to Support

### Test Case 1: Single Server - All Components
**Requirement**: Single nginx + PHP-FPM server hosts API, Vue SPA, and FA module

**Test Points**:
- [ ] Static Vue files (dist/) served via nginx
- [ ] PHP-FPM processes API and FA module requests
- [ ] Database connection pooling (max 20 connections)
- [ ] Response times: API <200ms, Vue assets <100ms
- [ ] File permissions: www-data owns web files, PHP can write logs
- [ ] SSL/HTTPS working on both /app and /api paths
- [ ] CORS headers correct for same-origin requests

**Validation Command**:
```bash
curl -H "Authorization: Bearer token" https://example.com/api/health
curl https://example.com/app/ # Should return index.html
```

**Expected**: 
- API returns 200 with health status
- Vue loads without CORS errors

---

### Test Case 2: Multi-Tier Deployment
**Requirement**: Separate API server, Frontend server, Database server

**Test Points**:
- [ ] Frontend (nginx) serves dist/ files only
- [ ] API (PHP-FPM) runs on separate machine
- [ ] Database MySQL on third machine
- [ ] CORS headers configured on API for frontend domain
- [ ] Health check endpoints working for monitoring
- [ ] Database connection can be established from API server
- [ ] SSL certificates valid for all domains

**Validation**:
```bash
# From Frontend server
curl https://api.example.com/api/health  # Should work

# Performance check
ab -c 10 -n 100 https://app.example.com/  # <100ms response
ab -c 10 -n 100 https://api.example.com/api/loans  # <200ms response
```

---

### Test Case 3: Docker/Containerized FA
**Requirement**: FrontAccounting runs in Docker with amortization module integrated

**Test Points**:
- [ ] docker-compose.yml can boot FA + MySQL + nginx in one command
- [ ] Volume mounts preserve database across restarts
- [ ] Module files mounted as volume (code changes reflect immediately)
- [ ] Database migrations run automatically on first boot
- [ ] PHP logs accessible via `docker logs`
- [ ] Persistent data directory backed up before container shutdown
- [ ] Container restarts don't lose data

**Validation**:
```bash
docker-compose up -d
sleep 5
curl http://localhost/modules/amortization/
# Should load FA module interface
```

---

### Test Case 4: Load Balancing
**Requirement**: Multiple API servers behind load balancer

**Test Points**:
- [ ] Session data stored in shared cache (Redis)
- [ ] Database connection pool shared (max 20 connections total)
- [ ] Sticky sessions OR stateless API (no sessions)
- [ ] Health check endpoint responds <100ms
- [ ] Load balancer removes unhealthy instances
- [ ] Failover: If one server dies, others still serve traffic
- [ ] Concurrent requests: 100+ simultaneous handled

**Validation**:
```bash
# Deploy 3 API instances
for i in 1 2 3; do
  php -S localhost:800$i &
done

# Load test through nginx
ab -c 50 -n 1000 http://localhost/api/health

# Verify all instances processed requests
# Check logs on each instance
```

---

### Test Case 5: Development Environment
**Requirement**: Local development with Docker or PHP built-in server

**Test Points**:
- [ ] `docker-compose -f docker-compose.dev.yml up` spins up local env
- [ ] OR `php -S localhost:8000` works for simple dev
- [ ] Vue dev server runs on separate port (5173)
- [ ] Hot reload works when files change
- [ ] Database seeded with test data
- [ ] Migrations run automatically
- [ ] Logs visible in console

**Validation**:
```bash
make dev  # Or docker-compose up

# Should see:
# - FrontAccounting accessible at :8000
# - API running at :8000/api
# - Vue dev server at :5173 (if running separately)
```

---

### Test Case 6: CI/CD Pipeline
**Requirement**: Automated tests and deployment

**Test Points**:
- [ ] GitHub Actions runs on every commit
- [ ] Unit tests pass before deploy
- [ ] Integration tests pass
- [ ] Code coverage > 80%
- [ ] Build frontend: npm run build succeeds
- [ ] Docker image builds successfully
- [ ] Credential secrets not exposed in logs
- [ ] Deploy to staging on green tests
- [ ] Smoke tests pass on staging
- [ ] Require manual approval before prod deploy

**Validation**:
```bash
# Push to GitHub
git push origin feature/test

# GitHub Actions workflow runs
# Should see: ✅ Tests passed, build succeeded
# Staging automatically deployed
```

---

### Test Case 7: Backup & Recovery
**Requirement**: Data can be backed up and restored

**Test Points**:
- [ ] Database daily backup to S3
- [ ] Backup can be restored within 1 hour
- [ ] File uploads backed up (if applicable)
- [ ] Backup retention: 30 days
- [ ] Test restore monthly to ensure backups valid
- [ ] Encryption at rest for backups
- [ ] Access logs for compliance (audit trail)

**Validation**:
```bash
# Create backup
mysqldump -u root -p ksf_amortization > backup.sql

# Restore test
mysql -u root -p ksf_amortization_test < backup.sql

# Verify data integrity
SELECT COUNT(*) FROM loans;  # Compare with production
```

---

### Test Case 8: Monitoring & Alerting
**Requirement**: System health monitored, alerts on issues

**Test Points**:
- [ ] CPU/Memory monitored (alert if >80%)
- [ ] Disk space monitored (alert if >90%)
- [ ] Response times tracked (alert if >500ms p95)
- [ ] Error rates monitored (alert if >1% errors)
- [ ] Database slow queries logged
- [ ] Uptime SLA: 99.5% (< 3.6 hours downtime/month)
- [ ] Alerts sent to Slack/email

**Validation**:
```bash
# Simulate high CPU
yes > /dev/null &  # CPU spike

# Alert should fire within 1 minute
# Check Slack/email for notification
```

---

### Test Case 9: Rollback Capability
**Requirement**: Can quickly revert to previous version if issues

**Test Points**:
- [ ] Previous Docker images tagged with version
- [ ] Rollback to previous image in < 5 minutes
- [ ] Database migrations reversible
- [ ] Feature flags can disable new features
- [ ] Canary deployment: 10% traffic to new version first
- [ ] Blue-green deployment possible (2x infrastructure)

**Validation**:
```bash
# Deploy new version
git push origin main  # CI/CD deploys

# Monitor metrics
# If errors increase, rollback:
docker-compose down
docker-compose up -d  # Pull previous image
```

---

### Test Case 10: Performance SLA
**Requirement**: System meets performance targets

**Test Points**:
- [ ] API response: p50 <100ms, p95 <200ms, p99 <500ms
- [ ] Frontend load time: <2 seconds on 3G
- [ ] Database query: <100ms for typical lock
- [ ] Concurrent users: 100+ without degradation
- [ ] Budget: < 1GB memory per worker, < 20% CPU idle

**Validation**:
```bash
# Load test for 5 minutes
ab -c 50 -n 10000 -t 300 https://api.example.com/api/loans

# Expected output:
# Requests per second: > 100
# Failed requests: 0
# Time per request: 50% < 100ms
```

---

## Success Criteria

✅ All test cases must pass before deployment to production
✅ Performance SLA met under load
✅ Backup tested (restore works)
✅ Alerting functional
✅ Rollback tested
✅ Documentation complete and updated

## Documentation Deliverables

1. **DEPLOYMENT_GUIDE.md** - Step-by-step deployment instructions
2. **docker-compose.yml** - Single-command local development
3. **docker-compose.prod.yml** - Production deployment
4. **.github/workflows/ci-cd.yml** - CI/CD pipeline
5. **Makefile** - Common deployment tasks
6. **MONITORING.md** - Alerting, health checks, SLA
7. **ROLLBACK.md** - Incident management, rollback procedures
8. **ARCHITECTURE_DEPLOYMENT.md** - Deployment architecture diagrams

## Timeline

- **Phase 26a**: Create TDD test plan ← **YOU ARE HERE**
- **Phase 26b**: Write deployment documentation
- **Phase 26c**: Create docker-compose files
- **Phase 26d**: Set up CI/CD pipeline
- **Phase 26e**: Load test and validate SLA
- **Phase 26f**: Production deployment dry run
