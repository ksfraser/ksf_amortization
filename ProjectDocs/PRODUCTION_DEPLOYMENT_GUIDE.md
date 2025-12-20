# Production Deployment Guide: Small-Scale Setup (1-2 Users)

**Date:** December 16, 2025  
**Target:** 1-2 Concurrent Users  
**Environment:** Single Server  
**Database:** SQLite or MySQL (single instance)

---

## Executive Summary

This guide covers deploying the KSF Amortization Platform to production for a small-scale setup (1-2 concurrent users). The setup prioritizes simplicity, reliability, and maintainability over high-availability or complex infrastructure.

### Key Principles
- **Simplicity:** Minimal infrastructure, easy to maintain
- **Reliability:** Single point of failure mitigation
- **Performance:** Optimized for typical small-team usage patterns
- **Security:** Production-grade security without over-engineering
- **Monitoring:** Basic monitoring for health and issues

---

## System Architecture

### Small-Scale Deployment

```
┌─────────────────────────────────────┐
│      Production Server              │
├─────────────────────────────────────┤
│  Web Server (Apache/Nginx)          │
├─────────────────────────────────────┤
│  PHP-FPM (2-4 workers)              │
├─────────────────────────────────────┤
│  Application Code                   │
│  (KSF Amortization Module)          │
├─────────────────────────────────────┤
│  Database (SQLite or MySQL)         │
├─────────────────────────────────────┤
│  File Storage (Local)               │
├─────────────────────────────────────┤
│  Backup (Daily/Weekly)              │
└─────────────────────────────────────┘
```

### No Complex Infrastructure Needed
- ❌ Load balancers (1 server)
- ❌ Database replication (single instance)
- ❌ Cache clusters (in-memory cache)
- ❌ Message queues (sync processing)
- ❌ Distributed file storage (local storage)

---

## 1. Server Requirements

### Minimum Hardware Specifications

```
CPU:         2+ cores (1 GHz+)
RAM:         4-8 GB
Storage:     50+ GB (for database, logs, backups)
Bandwidth:   10 Mbps+ (sufficient for small team)
```

### Recommended Hardware

```
CPU:         4 cores (2 GHz+)
RAM:         8-16 GB (for comfort)
Storage:     100+ GB (more headroom)
Bandwidth:   25 Mbps+
Backup:      External storage for daily backups
```

### Operating System

**Recommended:** Linux (Ubuntu 20.04 LTS or CentOS 8+)

```bash
# Ubuntu
Ubuntu 20.04 LTS (stable, long support)

# Or CentOS
CentOS 8+ or Rocky Linux 8+
```

**Not Recommended for Production:**
- Windows Server (support complexity)
- Shared hosting (limited control)

---

## 2. Software Stack

### Core Components

```
OS:              Linux (Ubuntu 20.04 LTS)
Web Server:      Nginx 1.20+
PHP Runtime:     PHP 8.4 (with FPM)
Database:        SQLite 3.30+ OR MySQL 8.0+
Version Control: Git
Package Manager: Composer
```

### PHP Extensions Required

```
php-cli
php-fpm
php-mysql (if using MySQL)
php-pdo
php-json
php-mbstring
php-curl
php-xml
```

### Installation Script

```bash
#!/bin/bash
# Ubuntu 20.04 LTS - Production Setup

# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install web server and PHP
sudo apt-get install -y nginx php8.4-fpm php8.4-cli php8.4-mysql php8.4-pdo php8.4-json php8.4-mbstring php8.4-curl php8.4-xml

# Install database (SQLite pre-installed; MySQL optional)
sudo apt-get install -y mysql-server

# Install development tools
sudo apt-get install -y git composer

# Install monitoring tools
sudo apt-get install -y htop iotop iftop netstat-nat

# Start services
sudo systemctl start nginx php8.4-fpm mysql
sudo systemctl enable nginx php8.4-fpm mysql

# Verify installation
php -v
nginx -v
mysql --version
composer --version
```

---

## 3. Database Setup

### Platform-Specific Schema Management

**Important:** KSF Amortizations uses platform-native schema management. The database schema and initialization is handled by each platform's module installation process, NOT by manual migrations.

#### FrontAccounting Module

Schema setup is handled by FrontAccounting's module installer:

```bash
# 1. Database is created by FrontAccounting admin interface
# 2. Copy module files to FrontAccounting
cp -r packages/ksf-amortizations-frontaccounting/module/amortization /path/to/frontaccounting/modules/

# 3. Initialize via FrontAccounting Admin:
# Setup → System Setup → Modules → Amortizations → Install
# (This runs the module's schema.sql automatically)

# 4. No manual SQL needed - FA handles all table creation
```

#### SuiteCRM Integration

Schema setup uses SuiteCRM's module installer:

```bash
# 1. Install via Composer
composer require ksfraser/amortizations-suitecrm

# 2. Run SuiteCRM module repair:
php bin/console cache:clear
php bin/console module:repair

# 3. Module schema automatically applied (SuiteCRM handles this)
```

#### WordPress Plugin

Schema setup uses WordPress's plugin installer:

```bash
# 1. Install via Composer or manual placement
composer require ksfraser/amortizations-wordpress

# 2. Activate plugin in WordPress admin
# Plugins → Amortizations → Activate
# (WordPress hooks handle table creation on activation)

# 3. No manual SQL needed - WordPress activation hook handles schema
```

#### Standalone Core Library

For standalone deployments (PHP CLI, custom frameworks):

```bash
# Use the core schema files from packages/ksf-amortizations-core/
# These are provided as reference SQL files, not migrations:

# - schema.sql              (main tables)
# - schema_events.sql       (event/payment tables)
# - schema_selectors.sql    (selector configuration)

# Apply manually if needed:
mysql ksf_amortization < packages/ksf-amortizations-core/schema.sql
mysql ksf_amortization < packages/ksf-amortizations-core/schema_events.sql
mysql ksf_amortization < packages/ksf-amortizations-core/schema_selectors.sql
```

---

### Database Choice: SQLite vs MySQL

**SQLite (Recommended for Small-Scale)**
- No server required, zero configuration
- Perfect for single-user or 1-2 concurrent users
- Easy backup (single file copy)
- Fast for typical amortization workloads
- Built-in with PHP, no installation needed

**MySQL (For Reliability & Multi-User)**
- Better transaction support
- Professional backup and replication tools
- Suitable if expanding to 3+ concurrent users
- Network accessible if needed

**For FrontAccounting:** Defer to FA's database choice (FA controls the database, not the module)

**For SuiteCRM:** Uses the SuiteCRM database directly

**For WordPress:** Uses the WordPress database directly

---

### Database Installation (Standalone Only)

If deploying the core library standalone:

```bash
# Create database
mysql -u root -p << EOF
CREATE DATABASE ksf_amortization CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ksf_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON ksf_amortization.* TO 'ksf_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF

# Apply schema
mysql -u ksf_user -p ksf_amortization < packages/ksf-amortizations-core/schema.sql
mysql -u ksf_user -p ksf_amortization < packages/ksf-amortizations-core/schema_events.sql
mysql -u ksf_user -p ksf_amortization < packages/ksf-amortizations-core/schema_selectors.sql
```

### MySQL Optimization for Small Load

```sql
-- my.cnf / mysql.conf.d/mysqld.cnf

[mysqld]
# Performance tuning for small workload
max_connections = 10           # Only 1-2 concurrent users
max_allowed_packet = 16M
thread_stack = 128K
sort_buffer_size = 256K
bulk_insert_buffer_size = 16M
innodb_buffer_pool_size = 256M # 25% of RAM
innodb_log_file_size = 50M

# For SSD storage (recommended)
innodb_flush_method = O_DIRECT

# Slow query logging (for monitoring)
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

---

## 4. Application Deployment

### Platform-Specific Deployment Process

The deployment process depends on which platform you're deploying to:

#### FrontAccounting Deployment

```bash
#!/bin/bash
# FrontAccounting Amortization Module Installation

# 1. Install via Composer (into FrontAccounting directory)
cd /path/to/frontaccounting
composer require ksfraser/amortizations-core
composer require ksfraser/amortizations-frontaccounting

# 2. Copy module files to FrontAccounting modules directory
cp -r vendor/ksfraser/amortizations-frontaccounting/module/amortization ./modules/

# 3. Set permissions
chmod -R 755 ./modules/amortization
chmod -R 755 ./modules/amortization/views

# 4. Initialize database via FrontAccounting admin:
# - Log in as administrator
# - Navigate to: Setup → System Setup → Modules
# - Find "Amortizations" in module list
# - Click "Install"
# (FrontAccounting will run the module's schema.sql automatically)

# 5. Configure module settings
# - GL Account mappings
# - Payment posting behavior
# - Selector options

echo "FrontAccounting Amortization Module deployed successfully"
```

#### SuiteCRM Deployment

```bash
#!/bin/bash
# SuiteCRM Amortization Module Installation

# 1. Install via Composer
cd /path/to/suitecrm
composer require ksfraser/amortizations-core
composer require ksfraser/amortizations-suitecrm

# 2. Module files are placed automatically by Composer

# 3. Repair modules (applies schema)
php bin/console cache:clear
php bin/console module:repair

# 4. Log in to SuiteCRM admin to verify module installed
# - Verify in: Administration → Module Manager

echo "SuiteCRM Amortization Module deployed successfully"
```

#### WordPress Deployment

```bash
#!/bin/bash
# WordPress Amortization Plugin Installation

# 1. Install via Composer (in WordPress root)
cd /path/to/wordpress
composer require ksfraser/amortizations-wordpress

# 2. Or manual placement:
# Copy plugin files to: wp-content/plugins/ksf-amortizations/

# 3. Set permissions
chmod -R 755 wp-content/plugins/ksf-amortizations

# 4. Activate plugin in WordPress admin
# - Plugins → All Plugins
# - Find "KSF Amortizations"
# - Click "Activate"
# (WordPress activation hook handles database schema creation)

# 5. Configure plugin settings
# - Navigate to: Settings → KSF Amortizations
# - Configure your settings

echo "WordPress Amortization Plugin deployed successfully"
```

#### Standalone Core Library

```bash
#!/bin/bash
# Standalone Core Library Deployment

# 1. Install via Composer
composer require ksfraser/amortizations-core

# 2. Create database (if not using FA/SuiteCRM/WP)
mysql -u root -p << EOF
CREATE DATABASE ksf_amortization CHARACTER SET utf8mb4;
CREATE USER 'ksf_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON ksf_amortization.* TO 'ksf_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF

# 3. Apply schema from core package
cd /path/to/application
mysql -u ksf_user -p ksf_amortization < vendor/ksfraser/amortizations-core/schema.sql
mysql -u ksf_user -p ksf_amortization < vendor/ksfraser/amortizations-core/schema_events.sql
mysql -u ksf_user -p ksf_amortization < vendor/ksfraser/amortizations-core/schema_selectors.sql

# 4. Configure your application to use the core library
# See: vendor/ksfraser/amortizations-core/README.md

echo "Standalone Core Library deployed successfully"
```

### Directory Structure (Post-Deployment)

The directory structure depends on which platform:

**FrontAccounting:**
```
/path/to/frontaccounting/
├── modules/amortization/    (KSF module - added by deployment)
├── config/
├── includes/
└── (rest of FA structure)
```

**SuiteCRM:**
```
/path/to/suitecrm/
├── modules/KsfAmortizations/  (KSF module - added by Composer)
├── public/
└── (rest of SuiteCRM structure)
```

**WordPress:**
```
/path/to/wordpress/
├── wp-content/plugins/
│   └── ksf-amortizations/     (KSF plugin - added by Composer)
├── wp-admin/
└── (rest of WordPress structure)
```

**Standalone:**
```
/path/to/application/
├── vendor/
│   └── ksfraser/amortizations-core/
├── config/
├── src/
├── data/              (SQLite database, if using)
└── logs/
```

### Environment Configuration

**FrontAccounting & SuiteCRM:**
- Database configuration handled by the platform
- Module configuration done through admin interfaces
- No .env file needed (uses platform config)

**WordPress:**
- Database configuration in wp-config.php
- Plugin settings in WordPress admin
- No .env file needed

**Standalone:**
- Create application-specific configuration
- May use .env for database credentials if desired

```env
# For standalone deployments only
DB_HOST=localhost
DB_NAME=ksf_amortization
DB_USER=ksf_user
DB_PASSWORD=strong_password
```

### Post-Deployment Verification

```bash
# For FrontAccounting
# Test by creating a loan via the module interface
# Verify GL entries created in: Transactions → General Ledger

# For SuiteCRM
# Test by creating an amortization record
# Verify in: Amortizations module

# For WordPress
# Test by accessing: wp-admin → KSF Amortizations
# Create a test amortization schedule

# For Standalone
# Test by running PHP scripts that use the core library
php -r "require 'vendor/autoload.php'; echo 'Core library loaded';"
```

---

### Standalone Core Library Deployment (Detailed)

---

## 5. Web Server Configuration

### Nginx Configuration

```nginx
# /etc/nginx/sites-available/ksf-amortization
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;

    # SSL Certificate (use Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/ksf-amortization-access.log;
    error_log /var/log/nginx/ksf-amortization-error.log;

    # Root directory
    root /var/www/ksf-amortization/public;

    # PHP FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Static files (cache)
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Default location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
}
```

### SSL Certificate Setup (Let's Encrypt)

```bash
# Install Certbot
sudo apt-get install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot certonly --nginx -d your-domain.com

# Auto-renewal (automatic with certbot)
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

# Test auto-renewal
sudo certbot renew --dry-run
```

---

## 6. Security Configuration

### File Permissions

```bash
# Application files
sudo chown -R www-data:www-data /var/www/ksf-amortization
sudo chmod 755 /var/www/ksf-amortization
sudo chmod 755 /var/www/ksf-amortization/public

# Writable directories
sudo chmod 775 /var/www/ksf-amortization/data    # SQLite
sudo chmod 775 /var/www/ksf-amortization/logs
sudo chmod 775 /var/www/ksf-amortization/storage

# Configuration files (readable by web server)
sudo chmod 644 /var/www/ksf-amortization/.env
sudo chmod 644 /var/www/ksf-amortization/.env.production
```

### Firewall Configuration

```bash
# Enable UFW firewall
sudo ufw enable

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Restrict MySQL (if external access needed)
# sudo ufw allow from 192.168.1.0/24 to any port 3306

# Verify rules
sudo ufw status verbose
```

### SSH Hardening

```bash
# /etc/ssh/sshd_config
Port 2222                    # Change default SSH port
PermitRootLogin no           # Disable root login
PasswordAuthentication no    # Use keys only
PubkeyAuthentication yes
X11Forwarding no
AllowUsers deployer          # Limit users

# Restart SSH
sudo systemctl restart sshd
```

### Application Security

```php
// Security headers in application
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

---

## 7. Backup & Recovery

### Backup Strategy

The backup strategy depends on your platform:

**FrontAccounting:**
- Backup the entire FrontAccounting database (which contains KSF module data)
- Daily backups of the database
- Weekly full filesystem backups

**SuiteCRM:**
- Backup the entire SuiteCRM database
- Weekly backups (schema changes are less frequent)
- Module data is stored in SuiteCRM's database

**WordPress:**
- Database backup (all plugin data included)
- Plugin files backup (for code recovery)
- Weekly automation recommended

**Standalone:**
- Database backup (SQLite file or MySQL dump)
- Configuration backup
- Daily schedule recommended

**Retention & Scheduling:**
```
Daily Backup:   Database + Configuration
Weekly Backup:  Full filesystem backup
Monthly:        Offsite backup
Retention:      30 days local, 12 months offsite
```

### Backup Script - FrontAccounting

```bash
#!/bin/bash
# /usr/local/bin/backup-fa.sh
# Backup FrontAccounting with KSF module data

BACKUP_DIR="/var/backups/frontaccounting"
FA_DB_NAME="frontaccounting"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup FrontAccounting database (includes KSF module tables)
mysqldump -u fa_user -p $FA_DB_NAME | gzip > $BACKUP_DIR/fa_$DATE.sql.gz

# Keep 30 days
find $BACKUP_DIR -name "fa_*.sql.gz" -mtime +30 -delete

echo "FrontAccounting backup completed: $BACKUP_DIR/fa_$DATE.sql.gz"
```

### Backup Script - SuiteCRM

```bash
#!/bin/bash
# /usr/local/bin/backup-suitecrm.sh
# Backup SuiteCRM with KSF module data

BACKUP_DIR="/var/backups/suitecrm"
SUITE_DB_NAME="suitecrm"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup SuiteCRM database (includes KSF module tables)
mysqldump -u suite_user -p $SUITE_DB_NAME | gzip > $BACKUP_DIR/suite_$DATE.sql.gz

# Keep 30 days
find $BACKUP_DIR -name "suite_*.sql.gz" -mtime +30 -delete

echo "SuiteCRM backup completed: $BACKUP_DIR/suite_$DATE.sql.gz"
```

### Backup Script - WordPress

```bash
#!/bin/bash
# /usr/local/bin/backup-wordpress.sh
# Backup WordPress with KSF plugin data

BACKUP_DIR="/var/backups/wordpress"
WP_DB_NAME="wordpress"
WP_ROOT="/var/www/wordpress"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup WordPress database (includes KSF plugin tables)
mysqldump -u wp_user -p $WP_DB_NAME | gzip > $BACKUP_DIR/wp_$DATE.sql.gz

# Backup plugin files
tar -czf $BACKUP_DIR/wp_files_$DATE.tar.gz \
  -C $WP_ROOT wp-content/plugins/ksf-amortizations/ \
  -C $WP_ROOT wp-config.php

# Keep 30 days
find $BACKUP_DIR -name "wp_*.sql.gz" -mtime +30 -delete
find $BACKUP_DIR -name "wp_files_*.tar.gz" -mtime +30 -delete

echo "WordPress backup completed"
```

### Backup Script - Standalone

```bash
#!/bin/bash
# /usr/local/bin/backup-standalone.sh
# Backup standalone KSF deployment

BACKUP_DIR="/var/backups/ksf-amortization"
DB_FILE="/var/www/ksf-amortization/data/amortization.db"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# SQLite backup
if [ -f "$DB_FILE" ]; then
  cp $DB_FILE $BACKUP_DIR/amortization_$DATE.db
  gzip $BACKUP_DIR/amortization_$DATE.db
fi

# Or MySQL backup
# mysqldump -u ksf_user -p ksf_amortization | gzip > $BACKUP_DIR/amortization_$DATE.sql.gz

# Keep 30 days
find $BACKUP_DIR -name "amortization_*.db.gz" -mtime +30 -delete

echo "Standalone backup completed: $BACKUP_DIR/amortization_$DATE.db.gz"
```

### Cron Job for Automated Backups

```bash
# Add to crontab
# sudo crontab -e

# FrontAccounting - Daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-fa.sh

# SuiteCRM - Weekly backup at 3 AM Sunday
0 3 * * 0 /usr/local/bin/backup-suitecrm.sh

# WordPress - Weekly backup at 4 AM Sunday
0 4 * * 0 /usr/local/bin/backup-wordpress.sh

# Standalone - Daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-standalone.sh
```

### Recovery Procedure - General

Recovery procedures vary by platform:

**FrontAccounting Recovery:**
```bash
# 1. Stop FrontAccounting (optional)
sudo systemctl stop php8.4-fpm

# 2. Restore database from backup
BACKUP_FILE="/var/backups/frontaccounting/fa_20251216_020000.sql.gz"
gunzip -c $BACKUP_FILE | mysql -u fa_user -p frontaccounting

# 3. Restart
sudo systemctl start php8.4-fpm
```

**SuiteCRM Recovery:**
```bash
# Similar to FrontAccounting
gunzip -c /var/backups/suitecrm/suite_*.sql.gz | mysql -u suite_user -p suitecrm
```

**WordPress Recovery:**
```bash
# 1. Restore database
gunzip -c /var/backups/wordpress/wp_*.sql.gz | mysql -u wp_user -p wordpress

# 2. Restore plugin files (if needed)
tar -xzf /var/backups/wordpress/wp_files_*.tar.gz -C /var/www/wordpress
```

**Standalone Recovery:**
```bash
# 1. Stop application (if applicable)
sudo systemctl stop php8.4-fpm

# 2. Restore database
gunzip -c /var/backups/ksf-amortization/amortization_*.db.gz > /var/www/ksf-amortization/data/amortization.db

# 3. Restart
sudo systemctl start php8.4-fpm
```

---

## 8. Monitoring & Performance

### Key Metrics to Monitor

```
Application Metrics:
- Response time (target: < 1s for 95% of requests)
- Request count per minute
- Error rate
- Active sessions
- Database query time

System Metrics:
- CPU usage (target: < 50% average, < 80% peak)
- Memory usage (target: < 60% average)
- Disk usage (target: < 70%)
- Disk I/O (target: < 50%)
- Network I/O

Database Metrics:
- Query execution time
- Slow query count
- Database size growth
- Backup success rate
```

### Monitoring Tools (Small-Scale)

```bash
# System monitoring (built-in)
htop              # Real-time system monitor
iostat            # Disk I/O statistics
netstat           # Network statistics
df -h             # Disk usage
free -h           # Memory usage

# Log monitoring
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.4-fpm.log
tail -f /var/www/ksf-amortization/logs/*.log

# Manual health check script
#!/bin/bash
echo "=== System Health ==="
echo "CPU:"
top -b -n1 | grep "Cpu(s)" | awk '{print $2}'
echo "Memory:"
free -h | grep "Mem" | awk '{print $3 "/" $2}'
echo "Disk:"
df -h / | tail -1 | awk '{print $3 "/" $2}'
echo "Database:"
ls -lh /var/www/ksf-amortization/data/amortization.db
```

### Simple Monitoring Dashboard (Optional)

```php
// /admin/health
Route::get('/admin/health', function() {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'uptime' => shell_exec('uptime'),
        'disk_free' => disk_free_space('/'),
        'database' => DB::selectOne('SELECT 1'),
        'response_time' => microtime(true),
    ]);
});
```

---

## 9. Performance Tuning for Small Load

### PHP-FPM Configuration

```ini
# /etc/php/8.4/fpm/pool.d/www.conf

[www]
user = www-data
group = www-data

; For 1-2 concurrent users, simple static configuration
pm = static
pm.max_children = 4        ; 2-3 per user + 1 buffer

; Memory limits
php_admin_value[memory_limit] = 256M

; Execution time
php_admin_value[max_execution_time] = 60

; File uploads
php_admin_value[upload_max_filesize] = 32M
php_admin_value[post_max_size] = 32M
```

### Nginx Tuning

```nginx
# /etc/nginx/nginx.conf

# Worker processes (1-2 for small load)
worker_processes auto;
worker_connections 256;    # Total connections = processes × connections

# Keep-alive
keepalive_timeout 65;
keepalive_requests 100;

# Gzip compression
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/json;
```

### Database Optimization

```sql
-- SQLite: No special optimization needed
-- SQLite is optimized for single-user access

-- MySQL: Already configured above in my.cnf

-- Create indexes for performance
CREATE INDEX idx_portfolio_id ON payments(portfolio_id);
CREATE INDEX idx_due_date ON payments(due_date);
CREATE INDEX idx_loan_id ON payments(loan_id);
CREATE INDEX idx_status ON loans(status);

-- Analyze statistics
ANALYZE TABLE payments;
ANALYZE TABLE loans;
```

---

## 10. Deployment Checklist

### Pre-Deployment

- [ ] Code tested locally (all 723 tests passing)
- [ ] Database schema migrations prepared
- [ ] Environment configuration created (.env.production)
- [ ] SSL certificate obtained (Let's Encrypt)
- [ ] Backup strategy documented
- [ ] Monitoring plan documented
- [ ] Recovery procedure tested
- [ ] Security configuration reviewed

### Deployment Day

- [ ] Server provisioned and hardened
- [ ] Web server and PHP installed
- [ ] Database created and initialized
- [ ] Application code deployed
- [ ] Permissions set correctly
- [ ] Configuration files updated
- [ ] SSL certificates installed
- [ ] Firewall configured
- [ ] Health check passed
- [ ] Backup script activated
- [ ] Monitoring activated

### Post-Deployment

- [ ] Application accessible via HTTPS
- [ ] Authentication working
- [ ] All features tested
- [ ] Performance baseline recorded
- [ ] Logs monitored for errors
- [ ] First backup completed
- [ ] User documentation provided
- [ ] Support contact established
- [ ] Monitoring dashboard accessible

---

## 11. Maintenance & Support

### Daily Tasks
- Monitor error logs
- Check system health
- Verify backups completed

### Weekly Tasks
- Review performance metrics
- Check disk usage growth
- Test backup recovery

### Monthly Tasks
- Patch OS and software
- Review security logs
- Capacity planning

### Quarterly Tasks
- Full system security audit
- Performance optimization review
- Disaster recovery drill

---

## 12. Cost Estimation (Small-Scale)

### Monthly Infrastructure Costs

```
Small VPS (4GB RAM, 2 CPU):          $10-20
Domain name:                         $1
SSL certificate (Let's Encrypt):     Free
Backup storage (100GB):              $1-2
Total Monthly:                       $12-23

Annual:                              $150-280
```

### Labor Costs (One-time)

```
Server setup:                        4 hours
Application deployment:              2 hours
Security hardening:                  2 hours
Monitoring setup:                    2 hours
Documentation:                       4 hours
Total:                              14 hours
```

---

## Conclusion

This guide provides a complete production deployment setup for the KSF Amortization Platform with 1-2 concurrent users. The setup is:

- **Simple:** Single server, minimal infrastructure
- **Secure:** Hardened with SSL, firewall, and access controls
- **Reliable:** Daily backups, recovery procedures, monitoring
- **Maintainable:** Clear documentation, simple troubleshooting
- **Cost-Effective:** $12-23/month infrastructure, 14 hours setup

---

*Production Deployment Guide*  
*KSF Amortization Platform*  
*Small-Scale Setup (1-2 Users)*  
*December 16, 2025*
