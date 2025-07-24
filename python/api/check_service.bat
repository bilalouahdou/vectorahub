@echo off
REM Check VectorizeAI Python API Service Status

echo Checking VectorizeAI Python API Service Status...
echo.

REM Check if service exists
sc query VectorizeAI-API >nul 2>&1
if %errorLevel% == 0 (
    echo 📊 Service Status:
    sc query VectorizeAI-API | findstr "STATE"
    echo.
    
    echo 🌐 Testing API Health:
    powershell -Command "try { $response = Invoke-RestMethod -Uri 'http://localhost:5000/health' -TimeoutSec 5; Write-Host '✅ API is responding:'; Write-Host $response } catch { Write-Host '❌ API is not responding' }"
) else (
    echo ❌ VectorizeAI-API service is not installed
    echo.
    echo To install the service, run: install_service.bat
)

echo.
pause
