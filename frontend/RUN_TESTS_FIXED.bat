@echo off
REM Vitest Frontend Test Runner for Windows
REM Usage: RUN_TESTS_FIXED.bat [option]
REM Options:
REM   (none)     - Run all tests with full config
REM   basic      - Run only basic tests  
REM   simple     - Run with simplified config
REM   debug      - Run debug mode with phase-based testing

setlocal enabledelayedexpansion

cd /d "%~dp0"

set TEST_TYPE=%1

if "%TEST_TYPE%"=="" (
    echo 🧪 Running Vitest - All Tests
    echo.
    call npm test
    goto end
)

if "%TEST_TYPE%"=="basic" (
    echo 🧪 Running Vitest - Basic Tests Only
    echo.
    call npx vitest --run --config vitest.config.js tests\unit\basic.spec.js
    goto end
)

if "%TEST_TYPE%"=="simple" (
    echo 🧪 Running Vitest - Simplified Config
    echo.
    call npx vitest --run --config vitest.config.simple.js
    goto end
)

if "%TEST_TYPE%"=="debug" (
    echo 🧪 Running Vitest - Debug Mode (Phase-based)
    echo.
    call node test-runner-debug.js
    goto end
)

echo ❌ Unknown option: %TEST_TYPE%
echo.
echo Usage: %0 [option]
echo Options:
echo   (none)     - Run all tests with full config
echo   basic      - Run only basic tests
echo   simple     - Run with simplified config
echo   debug      - Run debug mode with phase-based testing
echo.
goto end

:end
pause
