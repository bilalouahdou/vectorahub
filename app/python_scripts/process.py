#!/opt/venv/bin/python3
# File: app/python_scripts/process.py

import json
import sys
import os

def main():
    try:
        # Your Python logic here
        result = {
            "status": "success",
            "message": "Python script executed successfully",
            "python_version": sys.version,
            "working_directory": os.getcwd()
        }
        
        # If arguments were passed from PHP
        if len(sys.argv) > 1:
            input_data = sys.argv[1]
            try:
                parsed_data = json.loads(input_data)
                result["received_data"] = parsed_data
            except json.JSONDecodeError:
                result["received_data"] = input_data
        
        print(json.dumps(result))
        
    except Exception as e:
        error_result = {
            "status": "error",
            "message": str(e)
        }
        print(json.dumps(error_result))

if __name__ == "__main__":
    main()