@echo off
REM Import modules/amortization with full git history as a subtree
REM This converts the submodule into a regular directory while preserving all commits

cd /d c:\Users\prote\Documents\ksf_amortization

echo.
echo ===================================================
echo Step 1: Create import branch
echo ===================================================
git -c submodule.recurse=false checkout -b import-amortization-history
if errorlevel 1 (
    echo ERROR: Could not create branch. Trying alternate approach...
    git -c submodule.recurse=false checkout -b import-amortization-history-2
)

echo.
echo ===================================================
echo Step 2: Remove amortization from submodule config
echo ===================================================
git -c submodule.recurse=false config --file .gitmodules --remove-section submodule.modules/amortization
git -c submodule.recurse=false rm --cached modules/amortization
git -c submodule.recurse=false add .gitmodules

echo.
echo ===================================================
echo Step 3: Import amortization with full history
echo ===================================================
echo This will take a moment - importing all commits...
git -c submodule.recurse=false subtree add --prefix=modules/amortization https://github.com/ksfraser/ksf_amortization_fa.git main

if errorlevel 1 (
    echo ERROR: Subtree import failed. You may need to:
    echo 1. Verify GitHub access: ping github.com
    echo 2. Try: git fetch https://github.com/ksfraser/ksf_amortization_fa.git main
    echo 3. Check your network/VPN
    pause
    exit /b 1
)

echo.
echo ===================================================
echo Step 4: Commit the import
echo ===================================================
git -c submodule.recurse=false commit -m "Import modules/amortization with full git history

- Convert submodule to subtree
- Preserve all commit history
- modules/amortization now part of main repository
- Can maintain as integrated FA module component"

echo.
echo ===================================================
echo Step 5: Merge back to main
echo ===================================================
git -c submodule.recurse=false checkout main
git -c submodule.recurse=false merge import-amortization-history --no-ff -m "Merge: Import amortization module history as subtree

This merge brings in all historical commits from the
ksf_amortization_fa repository while converting it from
a submodule to a regular directory within the main repo."

echo.
echo ===================================================
echo SUCCESS: Amortization imported with full history!
echo ===================================================
echo Next steps:
echo 1. Verify modules/amortization looks good
echo 2. Run: git log --oneline modules/amortization
echo 3. Then do similar process for other submodules or:
echo 4. Move wordpress/suitecrm to software-devel
echo.
pause
