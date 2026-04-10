#!/bin/bash
# Core Migration TDD Baseline Test
# Verifies that core can be safely merged

echo "=== Core Migration Baseline Verification ==="
echo ""

CORE_SUBMODULE="vendor-src/ksf_amortization_core"
CORE_TARGET="src/ksf_amortization_core"
AMORTIZATION_VENDOR="modules/amortization/vendor/ksfraser/amortizations-core"

# Test 1: Current state - core exists as submodule
echo "[TEST 1] Core submodule exists"
if [ -d "$CORE_SUBMODULE/.git" ]; then
    echo "✓ PASS: Core submodule at $CORE_SUBMODULE is a git repo"
    CORE_COMMIT=$(cd $CORE_SUBMODULE && git log -1 --oneline)
    echo "  Current: $CORE_COMMIT"
else
    echo "✗ FAIL: Core submodule not found"
    exit 1
fi
echo ""

# Test 2: Core has all required directories
echo "[TEST 2] Core has required directory structure"
REQUIRED_DIRS=(
    "Amortizations"
    "Analytics"
    "Api"
    "Calculators"
    "Services"
    "Database"
    "Models"
    "Persistence"
    "Repositories"
)

all_exist=true
for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$CORE_SUBMODULE/$dir" ]; then
        echo "  ✓ $dir exists"
    else
        echo "  ✗ $dir MISSING"
        all_exist=false
    fi
done

if [ "$all_exist" = true ]; then
    echo "✓ PASS: All required directories exist"
else
    echo "✗ FAIL: Some directories missing"
    exit 1
fi
echo ""

# Test 3: Core has key files
echo "[TEST 3] Core has critical files"
REQUIRED_FILES=(
    "composer.json"
    "controller.php"
    "model.php"
    "view.php"
    "schema.sql"
)

all_exist=true
for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$CORE_SUBMODULE/$file" ]; then
        echo "  ✓ $file exists"
    else
        echo "  ✗ $file MISSING"
        all_exist=false
    fi
done

if [ "$all_exist" = true ]; then
    echo "✓ PASS: All critical files exist"
else
    echo "✗ FAIL: Some files missing"
    exit 1
fi
echo ""

# Test 4: File count baseline
echo "[TEST 4] Core file count"
FILE_COUNT=$(find "$CORE_SUBMODULE" -type f ! -path "*/.git/*" | wc -l)
echo "  Files in core: $FILE_COUNT"
if [ "$FILE_COUNT" -gt 400 ]; then
    echo "✓ PASS: Core has substantial content ($FILE_COUNT files)"
else
    echo "✗ FAIL: Core file count too low"
    exit 1
fi
echo ""

# Test 5: Composer.json is valid
echo "[TEST 5] Composer.json validation"
if [ -f "$CORE_SUBMODULE/composer.json" ]; then
    if php -r "json_decode(file_get_contents('$CORE_SUBMODULE/composer.json'), true); echo 'Valid';" 2>/dev/null | grep -q "Valid"; then
        echo "✓ PASS: composer.json is valid JSON"
    else
        echo "✗ FAIL: composer.json is invalid"
        exit 1
    fi
else
    echo "✗ FAIL: composer.json not found"
    exit 1
fi
echo ""

# Test 6: Target location doesn't exist yet
echo "[TEST 6] Target location verification"
if [ ! -d "$CORE_TARGET" ]; then
    echo "✓ PASS: Target location $CORE_TARGET is free"
else
    echo "✗ FAIL: Target location already exists"
    exit 1
fi
echo ""

echo "============================================"
echo "✓ ALL BASELINE TESTS PASSED"
echo "============================================"
echo ""
echo "Next steps:"
echo "  1. Merge core submodule into src/ksf_amortization_core"
echo "  2. Re-run this test against new location"
echo "  3. Remove from composer.json"
echo "  4. Final validation"
