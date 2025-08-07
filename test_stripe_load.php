<?php
header('Content-Type: text/plain');

echo "=== Testing Stripe Dependencies ===\n\n";

// Test 1: Check if vendor directory exists
echo "1. Checking vendor directory...\n";
if (file_exists('vendor/autoload.php')) {
    echo "   ✅ vendor/autoload.php exists\n";
} else {
    echo "   ❌ vendor/autoload.php NOT FOUND\n";
}

// Test 2: Try to load vendor
echo "\n2. Loading vendor/autoload.php...\n";
try {
    require_once 'vendor/autoload.php';
    echo "   ✅ vendor/autoload.php loaded successfully\n";
} catch (Exception $e) {
    echo "   ❌ Error loading vendor: " . $e->getMessage() . "\n";
}

// Test 3: Check if Stripe class exists
echo "\n3. Checking Stripe class...\n";
if (class_exists('Stripe\Stripe')) {
    echo "   ✅ Stripe\Stripe class found\n";
} else {
    echo "   ❌ Stripe\Stripe class NOT FOUND\n";
}

// Test 4: Test config loading
echo "\n4. Testing config loading...\n";
try {
    require_once 'php/config.php';
    echo "   ✅ Config loaded\n";
    echo "   - STRIPE_SECRET_KEY: " . (defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY ? 'SET' : 'NOT SET') . "\n";
} catch (Exception $e) {
    echo "   ❌ Config error: " . $e->getMessage() . "\n";
}

// Test 5: Test utils loading
echo "\n5. Testing utils loading...\n";
try {
    require_once 'php/utils.php';
    echo "   ✅ Utils loaded\n";
} catch (Exception $e) {
    echo "   ❌ Utils error: " . $e->getMessage() . "\n";
}

// Test 6: Test database connection
echo "\n6. Testing database connection...\n";
try {
    $pdo = connectDB();
    echo "   ✅ Database connected\n";
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>

