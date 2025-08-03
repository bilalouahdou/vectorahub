<?php
// Test admin panel functionality
require_once 'php/config.php';
require_once 'php/utils.php';

echo "🔧 Admin Panel Test\n";
echo "==================\n\n";

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "1. Testing database connection...\n";
try {
    $pdo = getDBConnection();
    echo "   ✅ Database connection successful\n";
    
    // Test basic query
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "   ✅ Users table accessible: $userCount users found\n";
    
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

echo "\n2. Testing admin functions...\n";

// Test connectDB function
try {
    $pdo2 = connectDB();
    echo "   ✅ connectDB() function works\n";
} catch (Exception $e) {
    echo "   ❌ connectDB() function failed: " . $e->getMessage() . "\n";
}

// Test admin check functions
echo "\n3. Testing admin authentication...\n";

// Simulate non-admin user
$_SESSION['user_id'] = 999;
$_SESSION['role'] = 'user';

if (isLoggedIn()) {
    echo "   ✅ isLoggedIn() works\n";
} else {
    echo "   ❌ isLoggedIn() failed\n";
}

if (!isAdmin()) {
    echo "   ✅ isAdmin() correctly identifies non-admin user\n";
} else {
    echo "   ❌ isAdmin() incorrectly identified user as admin\n";
}

// Simulate admin user
$_SESSION['role'] = 'admin';

if (isAdmin()) {
    echo "   ✅ isAdmin() correctly identifies admin user\n";
} else {
    echo "   ❌ isAdmin() failed to identify admin user\n";
}

echo "\n4. Testing admin API endpoints...\n";

// Test if admin API files exist and are accessible
$adminApiFiles = [
    'php/admin/api/stats.php',
    'php/admin/api/users.php',
    'php/admin/api/jobs.php',
    'php/admin/api/system_settings.php'
];

foreach ($adminApiFiles as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file exists\n";
    } else {
        echo "   ❌ $file missing\n";
    }
}

echo "\n5. Testing admin panel main file...\n";
if (file_exists('php/admin/index.php')) {
    echo "   ✅ php/admin/index.php exists\n";
} else {
    echo "   ❌ php/admin/index.php missing\n";
}

echo "\n🎉 Admin panel test completed!\n";
echo "If all tests passed, the admin panel should work correctly.\n";
?> 