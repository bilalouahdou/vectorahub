@echo off
echo Starting GPU Vectorization Runner...

REM Check if virtual environment exists
if not exist .venv (
    echo Creating virtual environment...
    python -m venv .venv
)

REM Activate virtual environment
echo Activating virtual environment...
call .venv\Scripts\activate

REM Install requirements
echo Installing requirements...
pip install -r requirements.txt

REM Set Python to unbuffered mode for better logging
set PYTHONUNBUFFERED=1

REM Set default port if not specified
if not defined RUNNER_PORT set RUNNER_PORT=8787

REM Start the FastAPI server
echo Starting FastAPI server on 127.0.0.1:%RUNNER_PORT%...
uvicorn app:app --host 127.0.0.1 --port %RUNNER_PORT% --reload

pause 