#!/usr/bin/env python3
"""
Simple test script for GPU Vectorization Runner
"""

import requests
import json
import time

def test_vectorization():
    """Test the vectorization endpoint"""
    
    # Test data
    url = "http://127.0.0.1:8787/run"
    headers = {
        "Authorization": "Bearer change-me-to-strong-secret-key",
        "Content-Type": "application/json"
    }
    data = {
        "input_url": "https://httpbin.org/image/png",
        "mode": "color"
    }
    
    print("🚀 Testing GPU Vectorization...")
    print(f"URL: {url}")
    print(f"Data: {json.dumps(data, indent=2)}")
    print()
    
    try:
        # Make the request
        start_time = time.time()
        response = requests.post(url, headers=headers, json=data, timeout=300)
        duration = time.time() - start_time
        
        print(f"Response Status: {response.status_code}")
        print(f"Response Time: {duration:.2f} seconds")
        
        if response.status_code == 200:
            result = response.json()
            print("✅ Vectorization Successful!")
            print(f"Job ID: {result.get('job_id')}")
            print(f"Duration: {result.get('duration_ms')}ms")
            print(f"Output: {result.get('output')}")
            return True
        else:
            print(f"❌ Error: {response.status_code}")
            print(f"Response: {response.text}")
            return False
            
    except requests.exceptions.ConnectionError:
        print("❌ Connection Error: Server not running")
        return False
    except requests.exceptions.Timeout:
        print("❌ Timeout: Request took too long")
        return False
    except Exception as e:
        print(f"❌ Error: {e}")
        return False

if __name__ == "__main__":
    success = test_vectorization()
    if success:
        print("\n🎉 Test completed successfully!")
    else:
        print("\n⚠️ Test failed!") 