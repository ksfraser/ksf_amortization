$ErrorActionPreference = "Stop"

$frontendPath = "$PSScriptRoot"
Write-Host "====================================`n  KSF Amortization Frontend Setup`n====================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/3] Installing npm dependencies..." -ForegroundColor Yellow
Write-Host ""

# Use cmd.exe to run npm to bypass PowerShell execution policy issues
$npmInstall = cmd /c "cd /d `"$frontendPath`" && npm install --no-audit 2>&1"
Write-Host $npmInstall

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: npm install failed" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[2/3] Building Vue.js application..." -ForegroundColor Yellow
Write-Host ""

$npmBuild = cmd /c "cd /d `"$frontendPath`" && npm run build 2>&1"
Write-Host $npmBuild

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: npm run build failed" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "====================================" -ForegroundColor Cyan
Write-Host "  Setup Complete" -ForegroundColor Green
Write-Host "====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Frontend output directory: $frontendPath\dist\" -ForegroundColor Green
Write-Host "Dev server: npm run dev (starts on http://localhost:5173)" -ForegroundColor Green
Write-Host ""

Read-Host "Press Enter to close"
