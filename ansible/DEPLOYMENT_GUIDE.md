# Ansible Deployment Guide

## Overview

This project includes Ansible playbooks for automated deployment of KSF Amortizations to:

1. **Standalone Webserver** - nginx + PHP-FPM + composer
2. **FrontAccounting Container** - Docker-based deployment with MySQL, nginx, PHP-FPM

**Key Benefits:**
- Infrastructure as Code (IaC)
- Reproducible, automated deployments
- Minimal manual configuration
- Version-controlled infrastructure
- Idempotent operations (safe to run multiple times)

---

## Directory Structure

```
ansible/
├── ansible.cfg              # Ansible configuration
├── inventory.yml            # Host inventory
├── deploy-webserver.yml     # Webserver deployment playbook
├── deploy-frontaccounting-container.yml  # Container deployment playbook
└── roles/
    ├── webserver/           # Webserver role (nginx + PHP-FPM)
    │   ├── tasks/
    │   │   ├── main.yml
    │   │   ├── composer.yml
    │   │   ├── application.yml
    │   │   ├── nginx.yml
    │   │   ├── php-fpm.yml
    │   │   ├── permissions.yml
    │   │   └── services.yml
    │   ├── handlers/
    │   │   └── main.yml
    │   └── templates/
    │       ├── nginx-vhost.j2
    │       ├── php-fpm-pool.j2
    │       └── .env.j2
    └── frontaccounting/     # FrontAccounting container role
        ├── tasks/
        │   ├── main.yml
        │   ├── docker.yml
        │   ├── directory-setup.yml
        │   ├── docker-compose.yml
        │   ├── environment.yml
        │   ├── containers.yml
        │   ├── frontaccounting-config.yml
        │   └── backups.yml
        ├── handlers/
        │   └── main.yml
        └── templates/
            ├── docker-compose.yml.j2
            ├── .env.fa.j2
            └── backup-fa.sh.j2
```

---

## Prerequisites

### For All Deployments

- **Ansible 2.9+** installed locally
- **SSH access** to target servers
- **Ubuntu/Debian-based** target systems
- **Internet access** for package downloads

### For Webserver Deployment

- Dedicated server or VM (2GB+ RAM, 10GB+ disk)
- Root or sudo access
- Open ports: 22 (SSH), 80 (HTTP), 443 (HTTPS)

### For FrontAccounting Container

- Same as webserver, plus:
- **Docker** and **Docker Compose** (installed by playbook)
- Open ports: 80 (HTTP), 443 (HTTPS), 8080 (FrontAccounting), 8081 (phpMyAdmin)

---

## Setup

### 1. Install Ansible

```bash
# On macOS
brew install ansible

# On Ubuntu/Debian
sudo apt-get update
sudo apt-get install ansible

# Verify installation
ansible --version
```

### 2. Configure SSH Access

```bash
# Generate SSH key if needed
ssh-keygen -t rsa -b 4096 -f ~/.ssh/id_rsa

# Copy public key to servers
ssh-copy-id -i ~/.ssh/id_rsa root@your_server_ip

# Test SSH connection
ssh root@your_server_ip "uname -a"
```

### 3. Update Inventory

Edit `ansible/inventory.yml`:

```yaml
all:
  children:
    webservers:
      hosts:
        web01:
          ansible_host: 192.168.1.100  # Your server IP
          
    frontaccounting:
      hosts:
        fa01:
          ansible_host: 192.168.1.101   # Your server IP
```

### 4. Customize Variables

For webserver (`ansible/inventory.yml`):
```yaml
vars:
  php_version: "7.4"  # or 8.0, 8.1
  app_root: "/var/www/ksf-amortizations"
```

For FrontAccounting:
```yaml
vars:
  fa_version: "2.4.10"
  fa_path: "/var/www/frontaccounting"
  fa_db_root_password: "your_secure_password"
```

---

## Deployment

### Deploy to Webserver

```bash
# 1. Check if servers are reachable
ansible all -i ansible/inventory.yml -m ping

# 2. Validate playbook syntax
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml --syntax-check

# 3. Preview changes (dry-run)
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml --check

# 4. Deploy to all webservers
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml

# 5. Deploy to specific server
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml -l web01

# 6. Deploy with extra verbosity
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml -vvv
```

### Deploy FrontAccounting Container

```bash
# 1. Verify connectivity
ansible all -i ansible/inventory.yml -m ping

# 2. Validate playbook
ansible-playbook -i ansible/inventory.yml ansible/deploy-frontaccounting-container.yml --syntax-check

# 3. Preview changes
ansible-playbook -i ansible/inventory.yml ansible/deploy-frontaccounting-container.yml --check

# 4. Deploy
ansible-playbook -i ansible/inventory.yml ansible/deploy-frontaccounting-container.yml

# 5. Deploy to specific host
ansible-playbook -i ansible/inventory.yml ansible/deploy-frontaccounting-container.yml -l fa01
```

---

## Deployment Flow

### Webserver Deployment Steps

1. ✓ Update system packages
2. ✓ Install PHP and extensions
3. ✓ Install Composer
4. ✓ Clone application repository
5. ✓ Install PHP dependencies
6. ✓ Configure nginx
7. ✓ Configure PHP-FPM
8. ✓ Set permissions
9. ✓ Start services
10. ✓ Verify installation

**Estimated time:** 5-10 minutes

### FrontAccounting Container Steps

1. ✓ Install Docker and Docker Compose
2. ✓ Create directory structure
3. ✓ Generate docker-compose.yml
4. ✓ Generate environment file
5. ✓ Pull container images
6. ✓ Start containers
7. ✓ Configure FrontAccounting
8. ✓ Install KSF Amortizations
9. ✓ Set up automated backups
10. ✓ Verify installation

**Estimated time:** 10-15 minutes

---

## Post-Deployment

### Verify Webserver Deployment

```bash
# Check service status
ssh root@your_server_ip "systemctl status nginx php7.4-fpm"

# Test HTTP access
curl http://your_server_ip/

# Check error logs
ssh root@your_server_ip "tail -f /var/log/nginx/ksf-amortizations-error.log"

# View application logs
ssh root@your_server_ip "tail -f /var/www/ksf-amortizations/storage/logs/app.log"
```

### Verify FrontAccounting Deployment

```bash
# Check Docker containers
ssh root@your_server_ip "docker ps | grep fa-"

# View container logs
ssh root@your_server_ip "docker-compose -f /var/www/frontaccounting/docker-compose.yml logs -f"

# Access FrontAccounting
# Open browser: http://your_server_ip:8080

# Check database
ssh root@your_server_ip "docker-compose -f /var/www/frontaccounting/docker-compose.yml \
  exec -T db mysql -u frontaccounting -p -e 'SHOW DATABASES;'"
```

---

## Configuration

### Environment Variables

**Webserver (.env file):**
- `APP_ENV` - production/development
- `APP_DEBUG` - true/false
- `DB_*` - Database connection details
- `UPLOAD_MAX_FILESIZE` - Max file upload size
- `LOG_LEVEL` - Logging verbosity

**FrontAccounting (.env file):**
- `FA_DB_ROOT_PASSWORD` - MySQL root password
- `FA_DB_PASSWORD` - FrontAccounting database password
- `FA_VERSION` - FrontAccounting version
- `KSF_ENABLE` - Enable KSF Amortizations module

### Customizing Playbooks

#### Override Variables

```bash
# Set PHP version during deployment
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml \
  -e "php_version=8.0"

# Set multiple variables
ansible-playbook -i ansible/inventory.yml ansible/deploy-frontaccounting-container.yml \
  -e "fa_version=2.4.10 fa_db_root_password=my_secure_password"
```

#### Modify Templates

Edit files in `ansible/roles/*/templates/` to customize:
- nginx configuration
- PHP-FPM settings
- docker-compose services
- Environment variables

---

## Troubleshooting

### SSH Connection Failed

```bash
# Verify SSH key is loaded
ssh-add ~/.ssh/id_rsa

# Test SSH directly
ssh -i ~/.ssh/id_rsa -v root@your_server_ip

# Check Ansible SSH config
ansible-inventory -i ansible/inventory.yml --list
```

### Deployment Hangs

```bash
# Kill and retry with timeout
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml \
  --timeout=600 -vvv

# Run specific role only
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml \
  --tags="nginx" -vvv
```

### Services Not Starting

```bash
# SSH and check systemd status
ssh root@your_server_ip "journalctl -xe -u nginx"
ssh root@your_server_ip "journalctl -xe -u php7.4-fpm"

# Check Docker logs
ssh root@your_server_ip "docker logs fa-app"
ssh root@your_server_ip "docker logs fa-db"
```

### Idempotency Issues

```bash
# Run deployment twice to ensure idempotency
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml

# Check for "changed" status (should be minimal on second run)
```

---

## Advanced Topics

### Running Specific Tasks

```bash
# Only install PHP
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml --tags="php"

# Only configure nginx
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml --tags="nginx"

# Skip service restart
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml --skip-tags="services"
```

### Limiting to Specific Hosts

```bash
# Deploy only to web01
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml -l web01

# Deploy to all except fa01
ansible-playbook -i ansible/inventory.yml ansible/deploy-frontaccounting-container.yml -l "all:!fa01"

# Deploy by group
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml -l webservers
```

### Gathering Facts

```bash
# Collect system information
ansible webservers -i ansible/inventory.yml -m setup

# Show specific fact
ansible webservers -i ansible/inventory.yml -m setup -a "filter=ansible_os_family"

# Disable fact gathering if not needed
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml --gathering=explicit
```

### Rolling Deployments

```bash
# Deploy with serial batches
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml \
  -e "ansible_connection=ssh" --serial=1
```

---

## Monitoring and Maintenance

### Health Checks

```bash
# Webserver health
ansible webservers -i ansible/inventory.yml -m uri -a "url=http://localhost/ method=GET"

# Docker container health
ansible frontaccounting -i ansible/inventory.yml -m shell \
  -a "docker-compose -f /var/www/frontaccounting/docker-compose.yml ps"
```

### Updates and Patching

```bash
# Update system packages
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml \
  --tags="packages" -e "update_cache=yes"

# Restart services after updates
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml \
  --tags="services"
```

### Backup and Restore

```bash
# FrontAccounting backup via Ansible
ansible frontaccounting -i ansible/inventory.yml -m shell \
  -a "{{ fa_path }}/backups/backup-fa.sh"

# View backups
ansible frontaccounting -i ansible/inventory.yml -m shell \
  -a "ls -lah {{ fa_path }}/backups/"
```

---

## Security Considerations

### SSH Key Management

- Use passphrase-protected keys
- Rotate keys regularly
- Store keys securely with restricted permissions (chmod 600)

### Variable Secrets

```bash
# Use Ansible vault for sensitive data
ansible-vault create ansible/secrets.yml

# Edit vault file
ansible-vault edit ansible/secrets.yml

# Run playbook with vault
ansible-playbook -i ansible/inventory.yml ansible/deploy-webserver.yml \
  --ask-vault-pass
```

### Server Hardening

- Update `ansible/roles/webserver/tasks/main.yml` to include:
  - UFW firewall configuration
  - SSH hardening
  - SSL/TLS certificates

---

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Install Ansible
        run: pip install ansible
      
      - name: Deploy
        env:
          ANSIBLE_HOST_KEY_CHECKING: False
        run: |
          ansible-playbook -i ansible/inventory.yml \
            ansible/deploy-webserver.yml \
            -u root --private-key=${{ secrets.DEPLOY_KEY }}
```

---

## Additional Resources

- **Ansible Documentation:** https://docs.ansible.com/
- **Ansible Best Practices:** https://docs.ansible.com/ansible/latest/user_guide/playbooks_best_practices.html
- **Jinja2 Templates:** https://jinja.palletsprojects.com/
- **Docker Compose:** https://docs.docker.com/compose/
- **FrontAccounting:** https://www.frontaccounting.eu/

---

**Last Updated:** 2025-12-20  
**Ansible Version:** 2.9+  
**Status:** Production Ready
