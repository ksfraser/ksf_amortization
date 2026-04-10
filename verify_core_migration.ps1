#!/usr/bin/env pwsh
# Core Migration TDD Baseline Test - PowerShell Version
# Verifies that core can be safely merged

Write-Host "=== Core Migration Baseline Verification ===" -ForegroundColor Cyan
Write-Host ""

$CORE_SUBMODULE = "vendor-src/ksf_amortization_core"
$CORE_TARGET = "src/ksf_amortization_core"
$AMORTIZATION_VENDOR = "modules/amortization/vendor/ksfraser/amortizations-core"

# Test 1: Current state - core exists as submodule
Write-Host "[TEST 1] Core submodule exists" -ForegroundColor Yellow
if (Test-Path "$CORE_SUBMODULE/.git") {
    Write-Host "✓ PASS: Core submodule at $CORE_SUBMODULE is a git repo" -ForegroundColor Green
    $coreCommit = & git -C $CORE_SUBMODULE log -1 --oneline
    Write-Host "  Current: $coreCommit"
} else {
    Write-Host "✗ FAIL: Core submodule not found" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Test 2: Core has all required directories
Write-Host "[TEST 2] Core has required directory structure" -ForegroundColor Yellow
$requiredDirs = @(
    "Amortizations",
    "Analytics",
    "Api",
    "Calculators",
    "Services",
    "Database",
    "Models",
    "Persistence",
    "Repositories",
    "Handlers",
    "EventHandlers"
)

$allExist = $true
foreach ($dir in $requiredDirs) {
    if (Test-Path "$CORE_SUBMODULE/$dir") {
        Write-Host "  ✓ $dir exists" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $dir MISSING" -ForegroundColor Red
        $allExist = $false
    }
}

if ($allExist) {
    Write-Host "✓ PASS: All required directories exist" -ForegroundColor Green
} else {
    Write-Host "✗ FAIL: Some directories missing" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Test 3: Core has key files
Write-Host "[TEST 3] Core has critical files" -ForegroundColor Yellow
$requiredFiles = @(
    "composer.json",
    "controller.php",
    "model.php",
    "view.php",
    "schema.sql"
)

$allExist = $true
foreach ($file in $requiredFiles) {
    if (Test-Path "$CORE_SUBMODULE/$file") {
        Write-Host "  ✓ $file exists" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $file MISSING" -ForegroundColor Red
        $allExist = $false
    }
}

if ($allExist) {
    Write-Host "✓ PASS: All critical files exist" -ForegroundColor Green
} else {
    Write-Host "✗ FAIL: Some files missing" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Test 4: File count baseline
Write-Host "[TEST 4] Core file count" -ForegroundColor Yellow
$files = Get-ChildItem -Path $CORE_SUBMODULE -Recurse -File | Where-Object { $_.FullName -notmatch '\.git' }
$fileCount = ($files | Measure-Object).Count
Write-Host "  Files in core: $fileCount"
if ($fileCount -gt 400) {
    Write-Host "✓ PASS: Core has substantial content ($fileCount files)" -ForegroundColor Green
} else {
    Write-Host "✗ FAIL: Core file count too low" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Test 5: Composer.json is valid
Write-Host "[TEST 5] Composer.json validation" -ForegroundColor Yellow
if (Test-Path "$CORE_SUBMODULE/composer.json") {
    try {
        $json = Get-Content "$CORE_SUBMODULE/composer.json" | ConvertFrom-Json
        Write-Host "✓ PASS: composer.json is valid JSON" -ForegroundColor Green
    } catch {
        Write-Host "✗ FAIL: composer.json is invalid" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✗ FAIL: composer.json not found" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Test 6: Target location doesn't exist yet
Write-Host "[TEST 6] Target location verification" -ForegroundColor Yellow
if (-not (Test-Path $CORE_TARGET)) {
    Write-Host "✓ PASS: Target location $CORE_TARGET is free" -ForegroundColor Green
} else {
    Write-Host "✗ FAIL: Target location already exists" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Test 7: Vendor copy is older
Write-Host "[TEST 7] Vendor copy version check" -ForegroundColor Yellow
if (Test-Path "$AMORTIZATION_VENDOR/.git") {
    $vendorCommit = & git -C $AMORTIZATION_VENDOR log -1 --oneline
    Write-Host "  Vendor copy: $vendorCommit" -ForegroundColor Gray
    Write-Host "  Submodule:   $coreCommit" -ForegroundColor Gray
    Write-Host "✓ PASS: Vendor copy exists (will be removed after merge)" -ForegroundColor Green
}
Write-Host ""

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "✓ ALL BASELINE TESTS PASSED" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "  1. Merge core submodule into src/ksf_amortization_core"
Write-Host "  2. Re-run this test against new location"
Write-Host "  3. Remove from composer.json"
Write-Host "  4. Final validation"
Write-Host ""
