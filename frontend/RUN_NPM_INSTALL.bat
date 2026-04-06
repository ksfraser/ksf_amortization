@echo off
REM Build installation script for KSF Amortization Frontend
REM This script installs all npm dependencies and builds the Vue.js application

echo ====================================
echo KSF Amortization Frontend Setup
echo ====================================
echo.

REM Navigate to the frontend directory
cd /d "%~dp0"

echo [1/3] Installing npm dependencies...
echo.
npm install --no-audit
if errorlevel 1 (
    echo ERROR: npm install failed
    exit /b 1
)
echo.

echo [2/3] Building Vue.js application...
echo.
npm run build
if errorlevel 1 (
    echo ERROR: npm run build failed
    exit /b 1
)
echo.

echo [3/3] Build complete!
echo.
echo ====================================
echo  Setup Complete
echo ====================================
echo.
echo Frontend output directory: %cd%\dist\
echo Dev server: npm run dev (starts on http://localhost:5173)
echo.
pause
