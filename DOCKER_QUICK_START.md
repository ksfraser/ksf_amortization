# Docker Quick Start Guide

## 1. Install Docker

### Windows 10/11 Pro/Enterprise
1. Download [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop/)
2. Run installer, follow instructions (will require restart)
3. Enable WSL2 during installation
4. Verify installation:
   ```bash
   docker --version
   docker-compose --version
   ```

### Mac
```bash
# Using Homebrew
brew install docker docker-compose

# Or download Docker Desktop: https://www.docker.com/products/docker-desktop/
```

### Linux (Ubuntu/Debian)
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add user to docker group (logout/login after)
sudo usermod -aG docker $USER
```

## 2. Initial Setup (First Time)

```bash
cd /path/to/ksf_amortization

# Create .env file for development
cp .env.example .env

# Build all images and start services
docker-compose up --build
```

This will:
- Build PHP image with Composer dependencies
- Build Node image with npm packages
- Start MySQL (automated migrations run)
- Start Redis for caching
- Start nginx for proxying
- Start Node dev server for Vue

**First run takes 3-5 minutes.** Grab coffee! ☕

## 3. Verify Everything Works

Open another terminal:

```bash
# Check all services are running
docker-compose ps
# You should see 5 containers: php, nginx, mysql, redis, node

# Test API health endpoint
curl http://localhost/api/health
# Expected response: {"status":"ok","service":"api","timestamp":"2024..."}

# Open in browser
# Frontend: http://localhost:5173 or http://localhost/
# API: http://localhost/api/
```

## 4. Common Tasks

### Run Tests
```bash
make test-php     # Run PHP unit tests
make test-vue     # Run Vue/Vitest tests
make test         # Run both
```

### View Logs
```bash
make logs              # All services
make logs-api          # Just PHP API
make logs-nginx        # Just nginx
docker-compose logs -f # All with follow (-f)
```

### Stop Services
```bash
make down        # Stop containers (data persists)
make docker-clean # Remove containers and volumes (clean slate)
```

### Access Containers
```bash
make shell-php      # Enter PHP container shell
make shell-node     # Enter Node container shell
docker-compose exec mysql mysql -u ksf_user  # MySQL shell
```

### Database Operations
```bash
make db-migrate     # Run migrations
make db-backup      # Backup database
make db-seed        # Seed test data
```

## 5. Development Workflow

### Add PHP Dependency
```bash
docker-compose exec php composer require vendor/package
```

### Add Node Dependency
```bash
docker-compose exec node npm install package-name
```

### Update PHP Code
```bash
# Just reload in browser (PHP auto-reloads)
curl http://localhost/api/health
```

### Update Vue Components
```bash
# Vitest watches for changes and rebuilds
# Browser auto-refreshes (hot module reload)
# Just edit and save!
```

### Debug PHP
```bash
# Add breakpoint or var_dump to code, then:
curl http://localhost/api/endpoint-name
# Check logs:
docker-compose logs php | tail -20
```

### Debug Vue
```bash
# Open browser dev tools (F12)
# Vue DevTools extension shows component tree
# Check console for errors
docker-compose logs node | tail -20
```

## 6. Production Preview

```bash
# Start production environment (preview mode)
make prod
# or: docker-compose -f docker-compose.prod.yml up --build

# Test static frontend serving
curl http://localhost:8000/

# Stop
make prod-down
```

## 7. Useful Commands

```bash
# See all containers and status
docker ps -a

# Remove dangling images/volumes
docker system prune

# Inspect container
docker inspect ksf_php_dev

# Copy file from container
docker cp ksf_php_dev:/app/file.txt ./

# Copy file to container
docker cp ./file.txt ksf_php_dev:/app/

# Execute command in container
docker exec -it ksf_php_dev php -v

# Monitor resource usage
docker stats
```

## 8. Troubleshooting

### "Port 3306 already in use"
```bash
# Kill existing MySQL or Docker container
# Windows: netstat -ano | findstr :3306 → taskkill /PID [PID] /F
# Mac/Linux: lsof -i :3306 → kill -9 [PID]

# Or use different port in docker-compose.yml
```

### "Cannot connect to Docker daemon"
- Docker Desktop not running (start it)
- On Linux: `sudo service docker start`

### "Vitest still hangs"
```bash
# Try direct invocation
docker-compose exec node npx vitest --run --reporter=verbose

# Check Node logs for real error
docker-compose logs node | grep -i error

# If still hanging, check Windows WSL2 file sync:
# Settings → Resources → WSL Integration → Toggle off/on
```

### MySQL won't start
```bash
# Check logs
docker-compose logs mysql

# Full reset
docker-compose down -v
docker-compose up --build mysql
```

### Nginx returns 502 Bad Gateway
```bash
# Verify PHP is running
docker-compose ps

# Test connection from nginx
docker-compose exec nginx curl http://api:9000/api/health

# Restart nginx
docker-compose restart nginx
```

## 9. Performance Tips

- Mount volumes efficiently (Docker Desktop for Mac/Windows are slower)
- Use `.dockerignore` to exclude large node_modules copies
- Don't bind localhost:3306 for MySQL in production
- Use health checks for readiness (already configured)

## 10. Next Steps

1. Run: `docker-compose up --build`
2. Wait for all services to be healthy
3. Visit: `http://localhost`
4. Run tests: `make test`
5. Check logs: `make logs`
6. Try production: `make prod`
7. Read: [DOCKER_VALIDATION_TESTS.md](./DOCKER_VALIDATION_TESTS.md) for comprehensive testing

## Reference

- All commands in `Makefile` (run `make help`)
- Compose files: `docker-compose.yml` (dev) and `docker-compose.prod.yml` (prod)
- Dockerfiles: `Dockerfile.dev` and `Dockerfile.prod`
- Config files: `nginx-*.conf`, `php-*.ini`

---

**Questions?** See [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md) for detailed architecture information.
