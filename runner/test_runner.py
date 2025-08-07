#!/usr/bin/env python3
"""
Test script for GPU Vectorization Runner
Run this to verify your setup is working correctly
"""

import os
import sys
import requests
import json
import time
from pathlib import Path

def test_health_check(base_url, token):
    """Test the health check endpoint"""
    print("🔍 Testing health check...")
    
    try:
        response = requests.get(f"{base_url}/health", timeout=10)
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Health check passed: {data}")
            return True
        else:
            print(f"❌ Health check failed: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Health check error: {e}")
        return False

def test_vectorization(base_url, token, test_url):
    """Test the vectorization endpoint"""
    print("🔍 Testing vectorization...")
    
    payload = {
        "input_url": test_url,
        "mode": "color"
    }
    
    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Bearer {token}"
    }
    
    try:
        start_time = time.time()
        response = requests.post(
            f"{base_url}/run",
            json=payload,
            headers=headers,
            timeout=300  # 5 minutes timeout
        )
        duration = time.time() - start_time
        
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Vectorization successful!")
            print(f"   Job ID: {data['job_id']}")
            print(f"   Duration: {data['duration_ms']}ms")
            print(f"   Output: {data['output']}")
            print(f"   Actual time: {duration:.2f}s")
            return True
        else:
            print(f"❌ Vectorization failed: {response.status_code}")
            try:
                error_data = response.json()
                print(f"   Error: {error_data.get('detail', 'Unknown error')}")
            except:
                print(f"   Response: {response.text}")
            return False
    except Exception as e:
        print(f"❌ Vectorization error: {e}")
        return False

def test_authentication(base_url, token):
    """Test authentication"""
    print("🔍 Testing authentication...")
    
    payload = {
        "input_url": "https://example.com/test.png",
        "mode": "color"
    }
    
    headers = {
        "Content-Type": "application/json",
        "Authorization": "Bearer invalid-token"
    }
    
    try:
        response = requests.post(
            f"{base_url}/run",
            json=payload,
            headers=headers,
            timeout=10
        )
        
        if response.status_code == 401:
            print("✅ Authentication working correctly (rejected invalid token)")
            return True
        else:
            print(f"❌ Authentication test failed: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Authentication test error: {e}")
        return False

def main():
    """Main test function"""
    print("🚀 GPU Vectorization Runner Test Suite")
    print("=" * 50)
    
    # Load configuration
    base_url = os.getenv('RUNNER_URL', 'http://127.0.0.1:8787')
    token = os.getenv('RUNNER_TOKEN', 'change-me-to-strong-secret-key')
    test_image_url = os.getenv('TEST_IMAGE_URL', 'https://via.placeholder.com/300x300.png')
    
    print(f"Base URL: {base_url}")
    print(f"Token: {token[:10]}..." if len(token) > 10 else f"Token: {token}")
    print(f"Test Image: {test_image_url}")
    print()
    
    # Run tests
    tests = [
        ("Health Check", lambda: test_health_check(base_url, token)),
        ("Authentication", lambda: test_authentication(base_url, token)),
        ("Vectorization", lambda: test_vectorization(base_url, token, test_image_url))
    ]
    
    results = []
    for test_name, test_func in tests:
        print(f"\n🧪 Running {test_name}...")
        try:
            result = test_func()
            results.append((test_name, result))
        except Exception as e:
            print(f"❌ {test_name} crashed: {e}")
            results.append((test_name, False))
    
    # Summary
    print("\n" + "=" * 50)
    print("📊 Test Results Summary:")
    print("=" * 50)
    
    passed = 0
    total = len(results)
    
    for test_name, result in results:
        status = "✅ PASS" if result else "❌ FAIL"
        print(f"{test_name:20} {status}")
        if result:
            passed += 1
    
    print(f"\nOverall: {passed}/{total} tests passed")
    
    if passed == total:
        print("🎉 All tests passed! Your GPU runner is working correctly.")
    else:
        print("⚠️  Some tests failed. Check the configuration and setup.")
    
    return passed == total

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1) 