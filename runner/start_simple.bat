@echo off
echo ========================================
echo    VectraHub GPU System Startup
echo ========================================
echo.
echo Starting all services...
echo.
powershell -ExecutionPolicy Bypass -File "start_everything_simple.ps1"
pause



