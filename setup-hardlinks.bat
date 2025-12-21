@echo off
REM Create hardlinks from packages to src to eliminate duplication while keeping local path repos
REM This allows single-source-of-truth development while maintaining the composer path structure

setlocal enabledelayedexpansion

REM Colors
set "success=[OK]"
set "error=[ERROR]"

echo Creating hardlinks from packages to src/Ksfraser...
echo.

REM HTML Package - link to src/Ksfraser/HTML
echo Creating hardlinks for html-project...
if not exist "packages\html-project\src\Ksfraser\HTML" mkdir "packages\html-project\src\Ksfraser\HTML"

REM Create hardlinks for each file in HTML package
for %%F in (src\Ksfraser\HTML\*.php) do (
    set "filename=%%~nxF"
    if not exist "packages\html-project\src\Ksfraser\HTML\!filename!" (
        mklink /H "packages\html-project\src\Ksfraser\HTML\!filename!" "src\Ksfraser\HTML\!filename!" >nul
        if !errorlevel! equ 0 (
            echo %success% !filename!
        ) else (
            echo %error% Failed to link !filename!
        )
    ) else (
        echo [SKIP] !filename! already exists
    )
)

REM HTML Elements subdirectory
echo Creating hardlinks for HTML Elements...
if not exist "packages\html-project\src\Ksfraser\HTML\Elements" mkdir "packages\html-project\src\Ksfraser\HTML\Elements"

for %%F in (src\Ksfraser\HTML\Elements\*.php) do (
    set "filename=%%~nxF"
    if not exist "packages\html-project\src\Ksfraser\HTML\Elements\!filename!" (
        mklink /H "packages\html-project\src\Ksfraser\HTML\Elements\!filename!" "src\Ksfraser\HTML\Elements\!filename!" >nul
        if !errorlevel! equ 0 (
            echo %success% Elements\!filename!
        ) else (
            echo %error% Failed to link Elements\!filename!
        )
    ) else (
        echo [SKIP] Elements\!filename! already exists
    )
)

REM Amortizations Package - link to src/Ksfraser/Amortizations
echo.
echo Creating hardlinks for ksf-amortizations-core...
if not exist "packages\ksf-amortizations-core\src\Ksfraser\Amortizations" mkdir "packages\ksf-amortizations-core\src\Ksfraser\Amortizations"

REM Root Amortizations files
for %%F in (src\Ksfraser\Amortizations\*.php) do (
    set "filename=%%~nxF"
    if not exist "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\!filename!" (
        mklink /H "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\!filename!" "src\Ksfraser\Amortizations\!filename!" >nul
        if !errorlevel! equ 0 (
            echo %success% !filename!
        ) else (
            echo %error% Failed to link !filename!
        )
    ) else (
        echo [SKIP] !filename! already exists
    )
)

REM Views subdirectory
echo Creating hardlinks for Amortizations Views...
if not exist "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Views" mkdir "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Views"

for %%F in (src\Ksfraser\Amortizations\Views\*.php) do (
    set "filename=%%~nxF"
    if not exist "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Views\!filename!" (
        mklink /H "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Views\!filename!" "src\Ksfraser\Amortizations\Views\!filename!" >nul
        if !errorlevel! equ 0 (
            echo %success% Views\!filename!
        ) else (
            echo %error% Failed to link Views\!filename!
        )
    ) else (
        echo [SKIP] Views\!filename! already exists
    )
)

REM Models subdirectory
echo Creating hardlinks for Amortizations Models...
if not exist "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Models" mkdir "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Models"

for %%F in (src\Ksfraser\Amortizations\Models\*.php) do (
    set "filename=%%~nxF"
    if not exist "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Models\!filename!" (
        mklink /H "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Models\!filename!" "src\Ksfraser\Amortizations\Models\!filename!" >nul
        if !errorlevel! equ 0 (
            echo %success% Models\!filename!
        ) else (
            echo %error% Failed to link Models\!filename!
        )
    ) else (
        echo [SKIP] Models\!filename! already exists
    )
)

REM Handlers subdirectory
echo Creating hardlinks for Amortizations Handlers...
if not exist "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Handlers" mkdir "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Handlers"

for %%F in (src\Ksfraser\Amortizations\Handlers\*.php) do (
    set "filename=%%~nxF"
    if not exist "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Handlers\!filename!" (
        mklink /H "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Handlers\!filename!" "src\Ksfraser\Amortizations\Handlers\!filename!" >nul
        if !errorlevel! equ 0 (
            echo %success% Handlers\!filename!
        ) else (
            echo %error% Failed to link Handlers\!filename!
        )
    ) else (
        echo [SKIP] Handlers\!filename! already exists
    )
)

REM Calculators subdirectory
echo Creating hardlinks for Amortizations Calculators...
if not exist "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Calculators" mkdir "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Calculators"

for %%F in (src\Ksfraser\Amortizations\Calculators\*.php) do (
    set "filename=%%~nxF"
    if not exist "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Calculators\!filename!" (
        mklink /H "packages\ksf-amortizations-core\src\Ksfraser\Amortizations\Calculators\!filename!" "src\Ksfraser\Amortizations\Calculators\!filename!" >nul
        if !errorlevel! equ 0 (
            echo %success% Calculators\!filename!
        ) else (
            echo %error% Failed to link Calculators\!filename!
        )
    ) else (
        echo [SKIP] Calculators\!filename! already exists
    )
)

echo.
echo Hardlink creation complete!
echo.
echo Summary:
echo - Source files remain in: src/Ksfraser/
echo - Packages now link to source via hardlinks
echo - PHP/Composer sees both as regular files
echo - Changes in one location affect both automatically
echo - No duplication on disk
echo.
echo To remove all hardlinks, delete the packages\*\src\Ksfraser directories
