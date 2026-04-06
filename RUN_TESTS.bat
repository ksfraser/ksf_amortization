@echo off
cd /d "%~dp0frontend"
echo Running Phase 21 Frontend Tests...
echo.
npm run test
echo.
echo Test run complete.
pause
