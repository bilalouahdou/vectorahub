@echo off
REM Auto-start monitor for GPU Vectorization Runner
REM This script monitors for start trigger files and launches the runner

echo Starting GPU Runner Auto-Start Monitor...
echo Monitoring: C:\vh_runner\start_trigger.txt
echo.

:MONITOR_LOOP
REM Check if trigger file exists
if exist "C:\vh_runner\start_trigger.txt" (
    echo [%date% %time%] Start trigger detected!
    
    REM Delete the trigger file
    del "C:\vh_runner\start_trigger.txt" >nul 2>&1
    
    REM Check if runner is already running
    netstat -an | findstr :8787 >nul
    if %errorlevel% == 0 (
        echo [%date% %time%] Runner already running on port 8787
    ) else (
        echo [%date% %time%] Starting GPU Runner...
        
        REM Change to runner directory
        cd /d "C:\wamp64\www\test\runner"
        
        REM Start the runner in a new window
        start "GPU Runner" cmd /c "call .venv\Scripts\activate && python app.py"
        
        echo [%date% %time%] GPU Runner started!
    )
)

REM Wait 5 seconds before checking again
timeout /t 5 /nobreak >nul
goto MONITOR_LOOP