<?php
require_once __DIR__ . '/config/bootstrap.php';

echo "=== Environment Variable Test ===\n";
echo "APP_BASE_URL: " . (getenv('APP_BASE_URL') ?: 'NOT SET') . "\n";
echo "APP_SECRET: " . (getenv('APP_SECRET') ? substr(getenv('APP_SECRET'), 0, 8) . '...' : 'NOT SET') . "\n";
echo "RUNNER_BASE_URL: " . (getenv('RUNNER_BASE_URL') ?: 'NOT SET') . "\n";
echo "RUNNER_TOKEN: " . (getenv('RUNNER_TOKEN') ? substr(getenv('RUNNER_TOKEN'), 0, 8) . '...' : 'NOT SET') . "\n";

// Test file proxy signature generation
$testFile = 'test.png';
$secret = getenv('APP_SECRET');
if ($secret) {
    $sig = hash_hmac('sha256', $testFile, $secret);
    echo "Test signature for '$testFile': " . substr($sig, 0, 16) . "...\n";
} else {
    echo "Cannot generate signature - APP_SECRET not set\n";
}

echo "=== Test Complete ===\n";

