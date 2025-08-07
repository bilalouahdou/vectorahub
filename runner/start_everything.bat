@echo off
REM VectraHub GPU System - Complete Auto-Startup
REM This starts everything: Monitor + Cloudflare Tunnel + GPU Runner (on-demand)

echo.
echo ========================================
echo    VectraHub GPU System Startup
echo ========================================
echo.

echo Starting all services...
echo.

REM Start the PowerShell script
powershell.exe -ExecutionPolicy Bypass -File "%~dp0start_everything.ps1"

pause