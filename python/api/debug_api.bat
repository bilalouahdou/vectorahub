@echo off
REM Debug script for VectorizeAI Python API (Windows)

echo Starting VectorizeAI Python API in DEBUG mode...

REM Check if virtual environment exists
if not exist "venv" (
    echo Creating virtual environment...
    python -m venv venv
)

REM Activate virtual environment
call venv\Scripts\activate.bat

REM Install dependencies
echo Installing dependencies...
pip install -r requirements.txt

REM Start the API server in debug mode
echo Starting Flask API server in debug mode...
set FLASK_DEBUG=true
set FLASK_APP=app.py
python -m flask run --host=0.0.0.0 --port=5000

pause
