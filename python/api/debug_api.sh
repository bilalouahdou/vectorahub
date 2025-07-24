#!/bin/bash
# Debug script for VectorizeAI Python API

echo "Starting VectorizeAI Python API in DEBUG mode..."

# Check if virtual environment exists
if [ ! -d "venv" ]; then
    echo "Creating virtual environment..."
    python3 -m venv venv
fi

# Activate virtual environment
source venv/bin/activate

# Install dependencies
echo "Installing dependencies..."
pip install -r requirements.txt

# Start the API server in debug mode
echo "Starting Flask API server in debug mode..."
export FLASK_DEBUG=true
export FLASK_APP=app.py
python -m flask run --host=0.0.0.0 --port=5000
