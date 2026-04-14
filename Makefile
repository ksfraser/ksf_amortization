.PHONY: help dev prod build up down logs test docker-clean

help:
	@echo "KSF Amortization - Development Commands"
	@echo ""
	@echo "Development:"
	@echo "  make dev              Start development environment"
	@echo "  make logs             View logs from dev environment"
	@echo "  make test             Run all tests"
	@echo "  make test-php         Run PHP unit tests"
	@echo "  make test-vue         Run Vue/Vitest tests"
	@echo ""
	@echo "Production:"
	@echo "  make prod             Start production environment (preview only)"
	@echo "  make build-prod       Build production Docker image"
	@echo ""
	@echo "Database:"
	@echo "  make db-migrate       Run database migrations"
	@echo "  make db-seed          Seed database with test data"
	@echo "  make db-backup        Backup database"
	@echo ""
	@echo "Cleanup:"
	@echo "  make down             Stop all containers"
	@echo "  make docker-clean     Remove all containers and volumes"
	@echo ""

# Setup and run development environment
dev: .env
	@echo "Starting development environment..."
	docker-compose up --build

# Start without rebuild
dev-quick:
	docker-compose up

# View logs
logs:
	docker-compose logs -f

logs-api:
	docker-compose logs -f api

logs-nginx:
	docker-compose logs -f nginx

logs-mysql:
	docker-compose logs -f mysql

# Stop containers
down:
	docker-compose down

# Full cleanup
docker-clean: down
	@echo "Removing volumes and containers..."
	docker-compose down -v
	docker system prune -f

# Run tests
test: test-php test-vue

test-php:
	@echo "Running PHP tests..."
	docker-compose exec php php vendor/bin/phpunit

test-php-coverage:
	@echo "Running PHP tests with coverage..."
	docker-compose exec php php vendor/bin/phpunit --coverage-html=coverage/

test-vue:
	@echo "Running Vue tests..."
	docker-compose exec node npm run test -- --run

test-vue-watch:
	@echo "Running Vue tests in watch mode..."
	docker-compose exec node npm run test

# Database commands
db-migrate:
	@echo "Running database migrations..."
	docker-compose exec mysql mysql -u root -proot ksf_amortization < migrations/migration_20251216_001_query_optimization_indexes.sql
	docker-compose exec mysql mysql -u root -proot ksf_amortization < migrations/migration_20251216_002_denormalized_interest.sql

db-backup:
	@echo "Backing up database..."
	docker-compose exec mysql mysqldump -u root -proot ksf_amortization > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "Backup created"

db-shell:
	docker-compose exec mysql mysql -u root -proot

# Build commands
build:
	docker-compose build

build-prod:
	@echo "Building production image..."
	docker build -f Dockerfile.prod -t ksf-amortization:latest .

build-prod-dev:
	docker build -f Dockerfile.dev -t ksf-amortization:dev .

# Install dependencies
install-php:
	docker-compose exec php composer install

install-vue:
	docker-compose exec node npm install

install-all: install-php install-vue

# Production preview (requires .env.prod)
prod:
	@echo "Starting production preview (compose.prod.yml)..."
	@echo "Note: Update .env with production values first"
	docker-compose -f docker-compose.prod.yml up --build

prod-down:
	docker-compose -f docker-compose.prod.yml down

prod-logs:
	docker-compose -f docker-compose.prod.yml logs -f

# Utilities
shell-php:
	docker-compose exec php sh

shell-node:
	docker-compose exec node sh

shell-mysql:
	docker-compose exec mysql sh

ps:
	docker-compose ps

# Initialize environment file
.env:
	@echo "Creating .env from .env.example..."
	cp .env.example .env
	@echo "Please update .env with your configuration"

# Setup (first time)
setup: .env
	docker-compose build
	docker-compose up -d mysql
	@echo "Waiting for MySQL to be ready..."
	sleep 5
	$(MAKE) db-migrate
	docker-compose up -d
	@echo "Setup complete!"
	@echo "Access:"
	@echo "  - Frontend: http://localhost:5173"
	@echo "  - API: http://localhost/api"
	@echo "  - Nginx: http://localhost"

# Run a specific test file
test-file:
	@read -p "Enter test file path: " filepath; \
	docker-compose exec php php vendor/bin/phpunit $$filepath

# Format code
fmt-php:
	@echo "Formatting PHP code..."
	docker-compose exec php vendor/bin/phpcbf src/ tests/

fmt-vue:
	@echo "Formatting Vue code..."
	docker-compose exec node npm run lint -- --fix

fmt-all: fmt-php fmt-vue
