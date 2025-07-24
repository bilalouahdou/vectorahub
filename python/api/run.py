#!/usr/bin/env python3
"""
Production runner for the VectorizeAI Flask API
"""
import os
import sys
from app import app, create_directories, waifu2x_exe

def check_dependencies():
    """Check if all required dependencies are available"""
    try:
        import vtracer
        import PIL
        import flask
        import requests
        print("✓ All Python dependencies are available")
        return True
    except ImportError as e:
        print(f"✗ Missing dependency: {e}")
        print("Please run: pip install -r requirements.txt")
        return False

def check_waifu2x():
    """Check if Waifu2x executable is available"""
    if os.path.exists(waifu2x_exe):
        print(f"✓ Waifu2x found at: {waifu2x_exe}")
        return True
    else:
        print(f"✗ Waifu2x not found at: {waifu2x_exe}")
        print("Please update the waifu2x_dir path in app.py")
        return False

if __name__ == '__main__':
    print("VectorizeAI Flask API Server")
    print("=" * 40)
    
    # Check dependencies
    if not check_dependencies():
        sys.exit(1)
    
    # Check Waifu2x
    if not check_waifu2x():
        print("Warning: Waifu2x not found. API will fail on requests.")
    
    # Create directories
    create_directories()
    
    # Get configuration from environment
    host = os.environ.get('FLASK_HOST', '0.0.0.0')
    port = int(os.environ.get('FLASK_PORT', 5000))
    debug = os.environ.get('FLASK_DEBUG', 'False').lower() == 'true'
    
    print(f"Starting server on {host}:{port}")
    print(f"Debug mode: {debug}")
    print("API endpoints:")
    print("  POST /vectorize - Main vectorization endpoint")
    print("  GET  /download/<filename> - Download SVG files")
    print("  GET  /health - Health check")
    print("\nPress Ctrl+C to stop the server")
    
    try:
        app.run(host=host, port=port, debug=debug)
    except KeyboardInterrupt:
        print("\nServer stopped by user")
