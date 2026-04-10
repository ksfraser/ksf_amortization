@echo off
REM Test runner batch file
REM Change to frontend directory and run tests

cd /d "%~dp0"
echo [TEST] Current directory: %cd%
echo [TEST] Running: npm test -- --run
echo.

call npm test -- --run 2>&1

echo.
echo [TEST] Test run completed with exit code: %ERRORLEVEL%
pause
