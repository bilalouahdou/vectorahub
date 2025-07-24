@echo off
REM Uninstall VectorizeAI Python API Windows Service

echo Uninstalling VectorizeAI Python API Service...

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% == 0 (
    echo Running as administrator - proceeding with uninstallation...
) else (
    echo ERROR: This script must be run as Administrator
    echo Right-click and select "Run as administrator"
    pause
    exit /b 1
)

REM Stop and remove the service
echo Stopping service...
nssm stop VectorizeAI-API

echo Removing service...
nssm remove VectorizeAI-API confirm

echo.
echo ✅ VectorizeAI Python API service has been removed.
echo.
pause
