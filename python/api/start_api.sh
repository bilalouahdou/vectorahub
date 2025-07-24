#!/bin/bash
# Start script for VectorizeAI Python API

echo "Starting VectorizeAI Python API..."

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

# Start the API server
echo "Starting Flask API server..."
python run.py
