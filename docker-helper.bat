@echo off
REM KSF Amortization Docker Helper for Windows (when GNU Make is not installed)
REM Usage: docker-helper.bat [command]

setlocal enabledelayedexpansion

if "%~1"=="" goto help
if "%~1"=="help" goto help
if "%~1"=="dev" goto dev
if "%~1"=="dev-quick" goto dev_quick
if "%~1"=="up" goto dev
if "%~1"=="down" goto down
if "%~1"=="logs" goto logs
if "%~1"=="test" goto test
if "%~1"=="test-php" goto test_php
if "%~1"=="test-vue" goto test_vue
if "%~1"=="shell-php" goto shell_php
if "%~1"=="shell-node" goto shell_node
if "%~1"=="build" goto build
if "%~1"=="clean" goto clean
if "%~1"=="ps" goto ps
if "%~1"=="setup" goto setup
echo Unknown command: %~1
goto help

:help
echo.
echo KSF Amortization - Docker Helper Commands
echo.
echo Usage: docker-helper.bat [command]
echo.
echo Development:
echo   dev              Start development environment
echo   dev-quick        Start without rebuild
echo   logs             View logs from all services
echo   test             Run all tests (PHP + Vue)
echo   test-php         Run PHP unit tests only
echo   test-vue         Run Vue/Vitest tests only
echo   ps               Show running containers
echo.
echo Containers:
echo   shell-php        Open PHP container shell
echo   shell-node       Open Node container shell
echo.
echo Build/Clean:
echo   build            Build all Docker images
echo   clean            Stop and remove all containers/volumes
echo   down             Stop containers (keep data)
echo.
echo Setup:
echo   setup            First-time setup (build + start MySQL + migrate)
echo.
exit /b 0

:dev
echo Starting development environment...
docker-compose up --build
exit /b %ERRORLEVEL%

:dev_quick
echo Starting development environment (no rebuild)...
docker-compose up
exit /b %ERRORLEVEL%

:down
echo Stopping containers...
docker-compose down
exit /b %ERRORLEVEL%

:logs
docker-compose logs -f
exit /b %ERRORLEVEL%

:test
echo Running all tests...
call :test_php
if %ERRORLEVEL% neq 0 exit /b 1
call :test_vue
exit /b %ERRORLEVEL%

:test_php
echo Running PHP tests...
docker-compose exec php php vendor/bin/vendor/bin/phpunit
exit /b %ERRORLEVEL%

:test_vue
echo Running Vue tests...
docker-compose exec node npm run test -- --run
exit /b %ERRORLEVEL%

:shell_php
docker-compose exec php sh
exit /b %ERRORLEVEL%

:shell_node
docker-compose exec node sh
exit /b %ERRORLEVEL%

:build
echo Building Docker images...
docker-compose build
exit /b %ERRORLEVEL%

:ps
docker-compose ps
exit /b %ERRORLEVEL%

:clean
echo Removing all containers and volumes...
docker-compose down -v
docker system prune -f
exit /b 0

:setup
echo Creating .env file...
if not exist .env (
    copy .env.example .env
    echo .env created. Please edit with your configuration.
)

echo Building images...
docker-compose build

echo Starting MySQL...
docker-compose up -d mysql

echo Waiting for MySQL to be ready...
timeout /t 5

echo Running migrations...
docker-compose exec mysql mysql -u root -proot_dev_password ksf_amortization ^
    < migrations/migration_20251216_001_query_optimization_indexes.sql
docker-compose exec mysql mysql -u root -proot_dev_password ksf_amortization ^
    < migrations/migration_20251216_002_denormalized_interest.sql

echo Starting all services...
docker-compose up -d

echo.
echo Setup complete!
echo.
echo Access points:
echo   Frontend: http://localhost:5173 or http://localhost/
echo   API: http://localhost/api/
echo   MySQL: localhost:3306
echo   Redis: localhost:6379
echo.
exit /b 0
