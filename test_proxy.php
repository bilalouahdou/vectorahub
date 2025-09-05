<?php
// Test script to verify file_proxy is working
require_once 'php/config/bootstrap.php';
require_once 'php/config.php';

$testFile = 'test.png';
$secret = getenv('APP_SECRET') ?: '';
if ($secret === '') { die('APP_SECRET not configured'); }
$sig = hash_hmac('sha256', $testFile, $secret);
$base = rtrim(getenv('APP_BASE_URL') ?: 'https://vectrahub.online', '/');

echo "=== File Proxy Test ===\n";
echo "Test file: $testFile\n";
echo "Secret: $secret\n";
echo "Signature: $sig\n";
echo "Base URL: $base\n";

// Test health endpoint
$healthUrl = $base . '/php/api/file_proxy.php?name=' . rawurlencode($testFile) . '&sig=' . $sig . '&health=1';
echo "Health URL: $healthUrl\n";

// Test actual file serving
$fileUrl = $base . '/php/api/file_proxy.php?name=' . rawurlencode($testFile) . '&sig=' . $sig;
echo "File URL: $fileUrl\n";

// Test with curl if available
if (function_exists('curl_init')) {
    echo "\n=== Testing with cURL ===\n";
    
    // Test health endpoint
    $ch = curl_init($healthUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Health endpoint response (HTTP $httpCode):\n";
    echo $response . "\n";
    
    // Test file endpoint
    $ch = curl_init($fileUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    echo "File endpoint response (HTTP $httpCode, Content-Type: $contentType):\n";
    echo substr($response, 0, 500) . "\n";
} else {
    echo "\ncURL not available for testing\n";
}

echo "\n=== Environment Check ===\n";
echo "APP_BASE_URL: " . getenv('APP_BASE_URL') . "\n";
echo "APP_SECRET: " . (getenv('APP_SECRET') ? 'SET' : 'NOT SET') . "\n";
echo "Uploads directory exists: " . (is_dir('/var/www/html/uploads') ? 'YES' : 'NO') . "\n";
echo "Test file exists: " . (is_file('/var/www/html/uploads/test.png') ? 'YES' : 'NO') . "\n";
if (is_file('/var/www/html/uploads/test.png')) {
    echo "Test file size: " . filesize('/var/www/html/uploads/test.png') . " bytes\n";
}
?>

