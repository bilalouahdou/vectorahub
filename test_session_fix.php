<?php
// Test session configuration fix
echo "🔧 Session Configuration Fix Test\n";
echo "=================================\n\n";

echo "1. Testing config.php session configuration...\n";
require_once 'php/config.php';

echo "   Session status: " . session_status() . "\n";
echo "   Headers sent: " . (headers_sent() ? 'Yes' : 'No') . "\n\n";

echo "2. Testing admin panel include...\n";
// Simulate what happens in admin panel
require_once 'php/utils.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "   Session status after start: " . session_status() . "\n";
echo "   Headers sent: " . (headers_sent() ? 'Yes' : 'No') . "\n\n";

echo "3. Testing database connection...\n";
try {
    $pdo = getDBConnection();
    echo "   ✅ Database connection successful\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n🎉 Session fix test completed!\n";
echo "If no warnings appeared, the session configuration is working correctly.\n";
?> 