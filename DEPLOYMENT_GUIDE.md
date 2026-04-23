.# KSF Amortization - Deployment Guide

## Overview

This guide covers deployment strategies from local development through production, with emphasis on testability and reliability.

## Deployment Strategies

### 1. Local Development

#### Quick Start (PHP Built-in Server)

```bash
# Clone and setup
git clone https://github.com/ksfraser/ksf_amortization.git
cd ksf_amortization

# Install PHP dependencies
composer install

# Install frontend dependencies
cd frontend && npm install && cd ..

# Start PHP API server (Terminal 1)
php -S localhost:8000 -t public/

# Start Vue dev server (Terminal 2)
cd frontend && npm run dev

# Access:
# - API: http://localhost:8000/api/health
# - Vue SPA: http://localhost:5173
```

#### Docker Development (All-in-One)

```bash
# Start full stack
docker-compose -f docker-compose.dev.yml up

# Access:
# - FrontAccounting: http://localhost
# - API: http://localhost/api/
# - Vue Dev Server: http://localhost:5173
```

**docker-compose.dev.yml:**
```yaml
version: '3.8'

services:
  php:
    image: php:8.1-fpm
    volumes:
      - .:/app
      - ./php.ini:/usr/local/etc/php/php.ini
    environment:
      DB_HOST: mysql
      DB_NAME: ksf_amortization
      DB_USER: root
      DB_PASS: root
    depends_on:
      - mysql
    ports:
      - "9000:9000"

  nginx:
    image: nginx:latest
    ports:
      - "80:80"
    volumes:
      - .:/app
      - ./nginx.dev.conf:/etc/nginx/nginx.conf:ro
    depends_on:
      - php

  mysql:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: ksf_amortization
    volumes:
      - mysql_data:/var/lib/mysql
      - ./migrations:/docker-entrypoint-initdb.d
    ports:
      - "3306:3306"

  node:
    image: node:18
    volumes:
      - ./frontend:/app
    working_dir: /app
    command: npm run dev
    ports:
      - "5173:5173"
    environment:
      VITE_API_URL: http://localhost/api

volumes:
  mysql_data:
```

**nginx.dev.conf:**
```nginx
events {}
http {
  upstream php {
    server php:9000;
  }

  server {
    listen 80;
    server_name localhost;
    root /app;

    # Vue SPA (frontend dev)
    location / {
      proxy_pass http://node:5173;
    }

    # API requests
    location /api/ {
      rewrite ^/api(/.*)$ /src/Ksfraser/Api/index.php$1;
      fastcgi_pass php;
      fastcgi_index index.php;
      include fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /app/src/Ksfraser/Api/index.php;
    }

    # FrontAccounting module
    location /modules/amortization/ {
      rewrite ^/modules/amortization$ /modules/amortization/controller.php;
      fastcgi_pass php;
      fastcgi_index controller.php;
      include fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /app/modules/amortization/$uri;
    }

    # PHP files
    location ~ \.php$ {
      fastcgi_pass php;
      fastcgi_index index.php;
      include fastcgi_params;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
  }
}
```

### 2. Single Server Production

**Hardware**: 2-4 vCPU, 4-8GB RAM, 50GB storage

**Architecture**:
```
nginx (reverse proxy + static files)
  ├─ /app/ → dist/index.html (Vue SPA)
  ├─ /api/ → PHP-FPM (REST API)
  └─ /modules/ → PHP-FPM (FA module)

php-fpm (worker pool)
  ├─ src/Ksfraser/Api/ (API endpoints)
  ├─ modules/amortization/ (FA integration)
  └─ app/ (shared code)

MySQL 5.7+
  └─ ksf_amortization (database)

Redis (optional)
  └─ Session cache
```

**Installation Steps**:

```bash
# 1. System dependencies
apt-get update && apt-get install -y \
  nginx php8.1-fpm php8.1-mysql php8.1-curl php8.1-json \
  mysql-server redis-server git curl wget

# 2. Create app directory
mkdir -p /var/www/ksf-amortization
cd /var/www/ksf-amortization

# 3. Clone repository
git clone https://github.com/ksfraser/ksf_amortization.git .
git checkout main  # or specific tag

# 4. Install dependencies
composer install --no-dev --optimize-autoloader
cd frontend && npm install --production && npm run build && cd ..

# 5. Set permissions
chown -R www-data:www-data /var/www/ksf-amortization
chmod -R 755 /var/www/ksf-amortization
chmod -R 770 /var/www/ksf-amortization/storage  # writable by PHP

# 6. Database setup
mysql -u root -p < migrations/migration_20251216_001_query_optimization_indexes.sql
mysql -u root -p < migrations/migration_20251216_002_denormalized_interest.sql

# 7. Configure PHP-FPM
cp php-fpm.conf /etc/php/8.1/fpm/pool.d/ksf-amortization.conf

# 8. Configure nginx
cp nginx-prod.conf /etc/nginx/sites-available/ksf-amortization
ln -s /etc/nginx/sites-available/ksf-amortization /etc/nginx/sites-enabled/
nginx -t && systemctl restart nginx

# 9. Enable services
systemctl enable nginx php8.1-fpm mysql redis-server
systemctl start nginx php8.1-fpm mysql redis-server

# 10. Verify
curl http://localhost/api/health  # Should return 200
```

**php-fpm.conf:**
```ini
[ksf-amortization]
user = www-data
group = www-data

listen = 127.0.0.1:9000
listen.allowed_clients = 127.0.0.1

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 2
pm.max_spare_servers = 5
pm.max_requests = 200
pm.process_idle_timeout = 30s

catch_workers_output = yes
```

**nginx-prod.conf:**
```nginx
# Upstream PHP-FPM
upstream php_fpm {
    server 127.0.0.1:9000;
}

# HTTPS redirect
server {
    listen 80;
    server_name _;
    return 301 https://$host$request_uri;
}

# Main server
server {
    listen 443 ssl http2;
    server_name app.example.com;

    # SSL certificates (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/app.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    root /var/www/ksf-amortization;
    index index.html index.php;

    # Logging
    access_log /var/log/nginx/ksf-amortization.access.log combined;
    error_log /var/log/nginx/ksf-amortization.error.log warn;

    # Static files (Vue SPA)
    location /app/ {
        alias /var/www/ksf-amortization/frontend/dist/;
        try_files $uri $uri/ /index.html;  # SPA routing
        expires 24h;
        add_header Cache-Control "public, immutable";
    }

    # API requests
    location /api/ {
        try_files $uri $uri/ /api/index.php$request_uri;
        fastcgi_pass php_fpm;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_NAME /api/index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/ksf-amortization/src/Ksfraser/Api/index.php;
        fastcgi_param PATH_INFO $uri;
    }

    # FrontAccounting module
    location /modules/amortization/ {
        try_files $uri $uri/ /modules/amortization/controller.php$is_args$args;
        fastcgi_pass php_fpm;
        fastcgi_index controller.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /var/www/ksf-amortization$fastcgi_script_name;
    }

    # PHP files
    location ~ \.php$ {
        fastcgi_pass php_fpm;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    location ~ ~$ {
        deny all;
    }
}
```

**Environment File (.env)**:
```
# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ksf_amortization
DB_USER=ksf_user
DB_PASS=secure_password_here

# App
APP_URL=https://app.example.com
APP_ENV=production
APP_DEBUG=false

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Logging
LOG_LEVEL=warning
LOG_FILE=/var/log/ksf-amortization/app.log

# API
API_RATE_LIMIT=60  # Requests per minute
API_TIMEOUT=30  # Seconds
```

### 3. Multi-Tier Deployment

**Deployment**: 3 separate servers

**Server 1 - Frontend (nginx)**:
```bash
# Static files only
nginx config:
  location / {
    root /var/www/ksf-amortization/frontend/dist;
    try_files $uri $uri/ /index.html;
  }
```

**Server 2 - API (PHP-FPM)**:
```bash
# REST API only
nginx config:
  location / {
    fastcgi_pass 127.0.0.1:9000;
    # Route to API index.php
  }
```

**Server 3 - Database (MySQL)**:
```bash
# MySQL with replication
mysql config:
  bind-address = 0.0.0.0  # Allow remote connections
  skip-name-resolve      # Faster connections
  max_connections = 100  # Pool across multiple API servers
```

### 4. Docker Production Deployment

**docker-compose.prod.yml**:
```yaml
version: '3.8'

services:
  nginx:
    image: nginx:1.24-alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./frontend/dist:/app/frontend/dist:ro
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
      - ./certs:/etc/nginx/certs:ro
    depends_on:
      - api
    networks:
      - ksf

  api:
    build:
      context: .
      dockerfile: Dockerfile.api
    environment:
      DB_HOST: mysql
      DB_NAME: ksf_amortization
      DB_USER: ${DB_USER}
      DB_PASS: ${DB_PASS}
      REDIS_HOST: redis
    depends_on:
      - mysql
      - redis
    restart: always
    networks:
      - ksf

  mysql:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ksf_amortization
    volumes:
      - mysql_data:/var/lib/mysql
      - ./migrations:/docker-entrypoint-initdb.d:ro
    restart: always
    networks:
      - ksf

  redis:
    image: redis:7-alpine
    restart: always
    networks:
      - ksf

volumes:
  mysql_data:

networks:
  ksf:
    driver: bridge
```

**Dockerfile.api**:
```dockerfile
FROM php:8.1-fpm-alpine

RUN apk add --no-cache \
    git curl libpq-dev \
    && docker-php-ext-install pdo pdo_mysql

WORKDIR /app

COPY composer.json composer.lock ./
RUN curl -sSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

COPY . .

RUN chown -R www-data:www-data /app

EXPOSE 9000
```

### 5. Load Balancing & Scaling

**HAProxy Load Balancer**:
```cfg
global
    maxconn 4096
    log /dev/log local0
    log /dev/log local1 notice

defaults
    log     global
    mode    http
    option  httplog
    option  dontlognull
    timeout connect 5000
    timeout client  50000
    timeout server  50000

frontend http_in
    bind *:80
    redirect scheme https code 301 if !{ ssl_fc }

frontend https_in
    bind *:443 ssl crt /etc/ssl/certs/example.pem
    
    # Route to API servers
    acl is_api path_beg /api/
    use_backend api_backend if is_api
    
    # Default to frontend
    default_backend frontend_backend

backend frontend_backend
    mode http
    balance roundrobin
    server frontend1 10.0.1.10:80 check
    server frontend2 10.0.1.11:80 check

backend api_backend
    mode http
    balance roundrobin
    option httpchk GET /api/health
    server api1 10.0.2.10:80 check inter 5s rise 2 fall 3
    server api2 10.0.2.11:80 check inter 5s rise 2 fall 3
    server api3 10.0.2.12:80 check inter 5s rise 2 fall 3
```

## Deployment Checklist

### Pre-Deployment
- [ ] All tests passing (unit, integration, E2E)
- [ ] Frontend built: `npm run build`
- [ ] Environment variables configured
- [ ] Database backups taken
- [ ] SSL certificates valid (`.pem` files copied)
- [ ] Secrets not in code/config
- [ ] Load test passed (100 concurrent users, <500ms p99)

### During Deployment
- [ ] Database migrations run successfully
- [ ] Services start without errors
- [ ] Health checks pass
- [ ] Sample requests work (create loan, get schedule)
- [ ] No error logs

### Post-Deployment
- [ ] Monitor error rates (<1% of requests)
- [ ] Monitor response times (p95 <200ms)
- [ ] Check disk/memory usage
- [ ] Verify backups completed
- [ ] Announce to users

## Rollback Procedure

If issues detected post-deployment:

**Option 1: Docker Image Rollback**
```bash
# Get previous image tag
docker images | grep ksf-amortization

# Rollback
docker-compose down
DOCKER_TAG=v1.0.0 docker-compose up -d
```

**Option 2: Git Rollback**
```bash
# Revert to previous tag
git checkout v1.0.0
composer install
npm run build
systemctl restart php8.1-fpm nginx
```

**Option 3: Database Rollback**
```bash
# Restore from backup if migrations failed
mysql -u root -p ksf_amortization < backup_20260413_120000.sql
```

## Monitoring & Alerting

### Health Checks

```bash
# API health
curl https://api.example.com/api/health

# Expected: {"status":"ok","database":"connected"}
```

### Logging

```bash
# View logs
journalctl -u php8.1-fpm -n 100 -f
tail -f /var/log/nginx/ksf-amortization.access.log

# Search for errors
grep ERROR /var/log/ksf-amortization/app.log
```

### Performance Monitoring

```bash
# Database slow queries
mysql -u root -p -e "SELECT * FROM mysql.slow_log LIMIT 10;"

# System resources
free -h
df -h
htop
```

## SSL Certificate Management

### Let's Encrypt

```bash
# Install certbot
apt-get install certbot python3-certbot-nginx

# Generate certificate
certbot certonly --webroot -w /var/www/ksf-amortization -d app.example.com

# Auto-renewal
systemctl enable certbot.timer
systemctl start certbot.timer

# Manual renewal
certbot renew
```

## Backup Strategy

### Daily Database Backup

```bash
#!/bin/bash
# /usr/local/bin/backup-ksf.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/ksf-amortization"

mkdir -p $BACKUP_DIR

# MySQL backup
mysqldump -u root -p $DB_PASS ksf_amortization | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Upload to S3
aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz s3://backups/ksf-amortization/

# Keep only 30 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete
```

Add to crontab:
```
0 2 * * * /usr/local/bin/backup-ksf.sh
```

## Common Deployment Issues

### Issue: API returns 502 Bad Gateway
**Solution**:
```bash
# Check PHP-FPM status
systemctl status php8.1-fpm
systemctl restart php8.1-fpm

# Check nginx error log
tail -f /var/log/nginx/error.log
```

### Issue: Cannot connect to database
**Solution**:
```bash
# Verify MySQL running
systemctl status mysql
mysql -u root -p -e "SELECT 1;"

# Check connection settings in .env
cat .env | grep DB_
```

### Issue: Vue SPA returns 404
**Solution**:
```bash
# Verify dist files exist
ls -la /var/www/ksf-amortization/frontend/dist/

# Rebuild frontend
cd /var/www/ksf-amortization/frontend
npm install --production
npm run build

# Check nginx try_files rule in config
```

## Performance Tuning

### Database
```sql
-- Add indexes
CREATE INDEX idx_loan_client ON loans(client_id);
CREATE INDEX idx_payment_loan ON payments(loan_id);
ANALYZE TABLE loans, payments;
```

### PHP
```ini
; /etc/php/8.1/fpm/php.ini
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
```

### Nginx
```nginx
gzip on;
gzip_vary on;
gzip_types text/plain text/css text/xml application/json application/javascript;
gzip_min_length 1000;
```

## Next Steps

1. Set up monitoring (Prometheus + Grafana)
2. Configure alerting (Slack, PagerDuty)
3. Plan disaster recovery
4. Document runbooks for common issues
5. Schedule regular backups verification
