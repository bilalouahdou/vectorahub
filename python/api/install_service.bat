@echo off
REM Install VectorizeAI Python API as Windows Service

echo Installing VectorizeAI Python API as Windows Service...

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% == 0 (
    echo Running as administrator - proceeding with installation...
) else (
    echo ERROR: This script must be run as Administrator
    echo Right-click and select "Run as administrator"
    pause
    exit /b 1
)

REM Install NSSM (Non-Sucking Service Manager) if not present
if not exist "nssm.exe" (
    echo Downloading NSSM...
    powershell -Command "Invoke-WebRequest -Uri 'https://nssm.cc/release/nssm-2.24.zip' -OutFile 'nssm.zip'"
    powershell -Command "Expand-Archive -Path 'nssm.zip' -DestinationPath '.'"
    copy "nssm-2.24\win64\nssm.exe" "nssm.exe"
    rmdir /s /q "nssm-2.24"
    del "nssm.zip"
)

REM Get current directory
set CURRENT_DIR=%cd%

REM Install the service
echo Installing service...
nssm install VectorizeAI-API "%CURRENT_DIR%\venv\Scripts\python.exe" "%CURRENT_DIR%\run.py"
nssm set VectorizeAI-API AppDirectory "%CURRENT_DIR%"
nssm set VectorizeAI-API DisplayName "VectorizeAI Python API"
nssm set VectorizeAI-API Description "VectorizeAI Image Vectorization API Service"
nssm set VectorizeAI-API Start SERVICE_AUTO_START

REM Start the service
echo Starting service...
nssm start VectorizeAI-API

echo.
echo ✅ VectorizeAI Python API installed as Windows Service!
echo.
echo Service Management:
echo   Start:   nssm start VectorizeAI-API
echo   Stop:    nssm stop VectorizeAI-API
echo   Remove:  nssm remove VectorizeAI-API confirm
echo.
echo The API will now start automatically when Windows boots.
echo Check status at: http://localhost:5000/health
echo.
pause
