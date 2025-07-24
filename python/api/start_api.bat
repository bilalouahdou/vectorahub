@echo off
REM Start script for VectorizeAI Python API (Windows)

echo Starting VectorizeAI Python API...

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

REM Start the API server
echo Starting Flask API server...
python run.py

pause
