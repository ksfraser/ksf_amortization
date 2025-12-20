# Ansible Deployment for KSF Amortizations

This directory contains Ansible playbooks and roles for deploying KSF Amortizations to production environments.

## Quick Start

### 1. Prepare Your Environment

```bash
# Install Ansible
pip install ansible>=2.9

# Set up SSH access
ssh-keygen -t rsa -b 4096
ssh-copy-id -i ~/.ssh/id_rsa root@your_server_ip
```

### 2. Configure Deployment

Edit `inventory.yml`:
```yaml
webservers:
  hosts:
    web01:
      ansible_host: YOUR_SERVER_IP
```

### 3. Deploy

```bash
# Webserver deployment
ansible-playbook -i inventory.yml deploy-webserver.yml

# FrontAccounting container deployment
ansible-playbook -i inventory.yml deploy-frontaccounting-container.yml
```

## Playbooks

### `deploy-webserver.yml`
Deploys KSF Amortizations to a standalone webserver with nginx + PHP-FPM.

**Includes:**
- System package updates
- PHP installation and configuration
- Composer setup
- Application cloning from repository
- nginx vhost configuration
- PHP-FPM pool configuration
- Service startup and verification

**Time:** 5-10 minutes

### `deploy-frontaccounting-container.yml`
Deploys FrontAccounting with KSF Amortizations in Docker containers.

**Includes:**
- Docker and Docker Compose installation
- Container image pulling
- MySQL, nginx, and PHP-FPM services
- KSF Amortizations module installation
- Automated backup configuration
- Health checks

**Time:** 10-15 minutes

## Roles

### `webserver/`
Standalone webserver role with nginx and PHP-FPM.

**Tasks:**
- `main.yml` - Orchestrates deployment
- `composer.yml` - Installs Composer
- `application.yml` - Clones and configures app
- `nginx.yml` - Configures reverse proxy
- `php-fpm.yml` - Configures PHP-FPM
- `permissions.yml` - Sets file permissions
- `services.yml` - Starts services

### `frontaccounting/`
FrontAccounting container orchestration role.

**Tasks:**
- `main.yml` - Orchestrates deployment
- `docker.yml` - Installs Docker
- `directory-setup.yml` - Creates directories
- `docker-compose.yml` - Generates docker-compose
- `environment.yml` - Creates .env file
- `containers.yml` - Starts containers
- `frontaccounting-config.yml` - Configures FA
- `backups.yml` - Sets up automated backups

## Usage Examples

### Deploy with Syntax Check

```bash
ansible-playbook -i inventory.yml deploy-webserver.yml --syntax-check
```

### Dry-Run (Check Mode)

```bash
ansible-playbook -i inventory.yml deploy-webserver.yml --check
```

### Verbose Output

```bash
ansible-playbook -i inventory.yml deploy-webserver.yml -vvv
```

### Deploy to Specific Host

```bash
ansible-playbook -i inventory.yml deploy-webserver.yml -l web01
```

### Run Specific Tags

```bash
ansible-playbook -i inventory.yml deploy-webserver.yml --tags="php,nginx"
```

### Skip Specific Tasks

```bash
ansible-playbook -i inventory.yml deploy-webserver.yml --skip-tags="services"
```

## Configuration Variables

### Global Variables (inventory.yml)

```yaml
ansible_user: root                          # SSH user
ansible_port: 22                            # SSH port
app_name: ksf-amortizations                 # Application name
app_version: "1.0.0"                        # Version
app_repo: https://github.com/...            # Repository URL
app_branch: main                            # Git branch
app_root: /var/www/ksf-amortizations       # Install path
php_version: "7.4"                          # PHP version
```

### Webserver-Specific

```yaml
php_extensions:
  - php-cli
  - php-fpm
  - php-mysql
  - php-curl
  - php-json
```

### FrontAccounting-Specific

```yaml
fa_version: "2.4.x"                         # FrontAccounting version
fa_path: /var/www/frontaccounting          # Container data path
fa_db_root_password: change_me              # MySQL root password
fa_db_user: frontaccounting                 # Database user
fa_db_password: change_me                   # Database password
```

## Troubleshooting

### SSH Connection Issues

```bash
# Test SSH access
ssh root@your_server_ip "echo 'Success'"

# Enable Ansible verbose output
ansible-playbook -i inventory.yml deploy-webserver.yml -vvv

# Check Ansible SSH config
ansible all -i inventory.yml -m debug -a "var=ansible_host"
```

### Deployment Failures

```bash
# Re-run with increased verbosity
ansible-playbook -i inventory.yml deploy-webserver.yml -vvvv

# Run only failed tasks
ansible-playbook -i inventory.yml deploy-webserver.yml --start-at-task="Task Name"

# Check logs on target server
ssh root@your_server_ip "journalctl -xe -u nginx"
ssh root@your_server_ip "journalctl -xe -u php7.4-fpm"
```

### Docker Issues (Container Deployment)

```bash
# Check container status
docker ps | grep fa-

# View container logs
docker logs fa-app
docker logs fa-db

# Restart containers
docker-compose -f /var/www/frontaccounting/docker-compose.yml restart
```

## Security

### Best Practices

1. **SSH Keys:** Use passphrase-protected keys
2. **Vault:** Store secrets in `ansible-vault`
3. **Credentials:** Never commit passwords to version control
4. **Firewall:** Configure firewall rules after deployment
5. **SSL:** Set up HTTPS certificates
6. **Updates:** Regularly update packages

### Using Vault

```bash
# Create vault file
ansible-vault create secrets.yml

# Edit vault file
ansible-vault edit secrets.yml

# Run playbook with vault
ansible-playbook -i inventory.yml deploy-webserver.yml --ask-vault-pass
```

## Post-Deployment Checks

### Webserver

```bash
# Check services
ssh root@your_server_ip "systemctl status nginx php7.4-fpm"

# Test HTTP access
curl http://your_server_ip/

# View logs
ssh root@your_server_ip "tail -f /var/log/nginx/ksf-amortizations-error.log"
```

### FrontAccounting Container

```bash
# Check containers
ssh root@your_server_ip "docker ps | grep fa-"

# View logs
ssh root@your_server_ip "docker-compose -f /var/www/frontaccounting/docker-compose.yml logs -f"

# Access web interface
# Open: http://your_server_ip:8080
```

## Files and Directories

```
ansible/
├── README.md                              # This file
├── DEPLOYMENT_GUIDE.md                    # Detailed guide
├── ansible.cfg                            # Ansible configuration
├── inventory.yml                          # Host inventory
├── deploy-webserver.yml                   # Webserver playbook
├── deploy-frontaccounting-container.yml   # Container playbook
└── roles/
    ├── webserver/
    │   ├── tasks/                         # Task files
    │   ├── handlers/                      # Event handlers
    │   └── templates/                     # Jinja2 templates
    └── frontaccounting/
        ├── tasks/
        ├── handlers/
        └── templates/
```

## Additional Resources

- **Ansible Documentation:** https://docs.ansible.com/
- **Jinja2 Templating:** https://jinja.palletsprojects.com/
- **Docker Compose:** https://docs.docker.com/compose/
- **FrontAccounting:** https://www.frontaccounting.eu/
- **KSF Amortizations:** https://github.com/ksfraser/amortizations

## Support

For issues or questions:

1. Check logs: `tail -f /var/log/*.log`
2. Enable verbose mode: `-vvv` or `-vvvv`
3. Use dry-run: `--check`
4. Review DEPLOYMENT_GUIDE.md for troubleshooting

---

**Version:** 1.0.0  
**Last Updated:** 2025-12-20  
**Status:** Production Ready
