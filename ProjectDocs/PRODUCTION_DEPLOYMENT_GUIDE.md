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

### Option A: SQLite (Recommended for Simplicity)

**Pros:**
- No server required
- Easy backup (single file)
- Zero configuration
- Fast for small datasets
- Perfect for 1-2 users

**Cons:**
- Single-user locking (not an issue for 1-2 users)
- Limited to local storage

#### SQLite Setup

```bash
# Create database directory
mkdir -p /var/www/ksf-amortization/data
chmod 755 /var/www/ksf-amortization/data

# Create database file
touch /var/www/ksf-amortization/data/amortization.db
chmod 666 /var/www/ksf-amortization/data/amortization.db

# Initialize schema (using your migration tool)
php artisan migrate --database=sqlite
# Or run schema.sql if provided
```

#### Configuration

```php
// config/database.php
'default' => 'sqlite',
'connections' => [
    'sqlite' => [
        'driver' => 'sqlite',
        'database' => env('DB_DATABASE', storage_path('app/amortization.db')),
        'prefix' => '',
    ],
],
```

---

### Option B: MySQL (For Reliability)

**Pros:**
- Better for concurrent connections
- Transaction support
- Professional backup tools
- Network accessible if needed

**Cons:**
- Requires configuration
- Memory overhead
- More complex backup

#### MySQL Setup

```bash
# Install MySQL Server (if not already done)
sudo apt-get install -y mysql-server

# Secure installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p << EOF
CREATE DATABASE ksf_amortization CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ksf_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON ksf_amortization.* TO 'ksf_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF

# Initialize schema
php artisan migrate
```

#### MySQL Optimization for Small Load

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

### Directory Structure

```
/var/www/ksf-amortization/
├── public/              # Web root
├── src/                 # Application code
├── config/              # Configuration files
├── data/                # SQLite database (if using)
├── logs/                # Application logs
├── backups/             # Database backups
├── .env                 # Environment configuration
├── .env.production      # Production-specific config
└── composer.json        # Dependencies
```

### Deployment Steps

```bash
#!/bin/bash
# Production Deployment Script

# 1. Clone or update repository
cd /var/www/ksf-amortization
git pull origin main
# Or first deployment:
git clone https://github.com/your-org/ksf-amortization.git

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Set permissions
chmod -R 755 /var/www/ksf-amortization
chmod -R 777 /var/www/ksf-amortization/data  # For SQLite writes
chmod -R 777 /var/www/ksf-amortization/logs  # For log files

# 4. Configuration
cp .env.example .env
# Edit .env with production values
nano .env

# 5. Database migration
php artisan migrate --force

# 6. Cache clearing
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Restart services
sudo systemctl restart php8.4-fpm nginx

# 8. Verify deployment
curl http://localhost/
```

### Environment Configuration (.env)

```env
# Production Environment
APP_ENV=production
APP_DEBUG=false
APP_KEY=your-app-key-here

# Database Configuration
DB_CONNECTION=sqlite          # or mysql
DB_DATABASE=/var/www/ksf-amortization/data/amortization.db

# If using MySQL instead:
# DB_CONNECTION=mysql
# DB_HOST=localhost
# DB_PORT=3306
# DB_DATABASE=ksf_amortization
# DB_USERNAME=ksf_user
# DB_PASSWORD=strong_password

# Application Settings
APP_NAME="KSF Amortization"
APP_URL=https://your-domain.com

# Timezone
APP_TIMEZONE=UTC

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=info

# Cache (in-memory)
CACHE_DRIVER=array
SESSION_DRIVER=cookie
```

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

```
Daily Backup:   Database + Configuration
Weekly Backup:  Full filesystem
Monthly:        Offsite backup
Retention:      30 days local, 12 months offsite
```

### Backup Script

```bash
#!/bin/bash
# /usr/local/bin/backup-ksf.sh

BACKUP_DIR="/var/backups/ksf-amortization"
DB_FILE="/var/www/ksf-amortization/data/amortization.db"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# SQLite backup
cp $DB_FILE $BACKUP_DIR/amortization_$DATE.db
gzip $BACKUP_DIR/amortization_$DATE.db

# Or MySQL backup
# mysqldump -u ksf_user -p ksf_amortization | gzip > $BACKUP_DIR/amortization_$DATE.sql.gz

# Keep only 30 days
find $BACKUP_DIR -name "amortization_*.db.gz" -mtime +30 -delete

# Sync to offsite storage (optional)
# rsync -a $BACKUP_DIR/ backup@offsite-server:/backups/ksf-amortization/

echo "Backup completed: $BACKUP_DIR/amortization_$DATE.db.gz"
```

### Cron Job for Daily Backups

```bash
# Add to crontab
# sudo crontab -e

# Daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-ksf.sh

# Weekly full backup at 3 AM Sunday
0 3 * * 0 /usr/local/bin/backup-full-ksf.sh
```

### Recovery Procedure

```bash
#!/bin/bash
# Recovery from backup

BACKUP_FILE="/var/backups/ksf-amortization/amortization_20251216_020000.db.gz"

# 1. Stop application
sudo systemctl stop php8.4-fpm

# 2. Restore database
gunzip -c $BACKUP_FILE > /var/www/ksf-amortization/data/amortization.db.restored
sudo chown www-data:www-data /var/www/ksf-amortization/data/amortization.db.restored

# 3. Verify restoration
# ... verify data integrity ...

# 4. Replace active database
sudo mv /var/www/ksf-amortization/data/amortization.db /var/www/ksf-amortization/data/amortization.db.bak
sudo mv /var/www/ksf-amortization/data/amortization.db.restored /var/www/ksf-amortization/data/amortization.db

# 5. Restart application
sudo systemctl start php8.4-fpm

echo "Recovery completed"
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
