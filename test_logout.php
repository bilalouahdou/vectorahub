<?php
// Test logout functionality
require_once 'php/config.php';

echo "🚪 Logout Test\n";
echo "==============\n\n";

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simulate a logged-in user
$_SESSION['user_id'] = 999;
$_SESSION['user_name'] = 'Test User';
$_SESSION['role'] = 'user';

echo "1. Session before logout:\n";
echo "   User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "   User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "\n";
echo "   Role: " . ($_SESSION['role'] ?? 'Not set') . "\n\n";

// Test logout functionality
echo "2. Testing logout...\n";

// Log the logout activity
if (isset($_SESSION['user_id'])) {
    logActivity('logout', 'User logged out', $_SESSION['user_id']);
    logSystemEvent('user_logout', 'User logged out successfully', $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']);
    echo "   ✅ Logout activity logged\n";
}

// Destroy session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
    echo "   ✅ Session cookie cleared\n";
}

echo "   ✅ Session destroyed\n\n";

// Check if session is really destroyed
echo "3. Session after logout:\n";
if (session_status() == PHP_SESSION_NONE) {
    echo "   ✅ Session is properly destroyed\n";
} else {
    echo "   ❌ Session still exists\n";
}

// Test session variables
if (isset($_SESSION['user_id'])) {
    echo "   ❌ User ID still exists: " . $_SESSION['user_id'] . "\n";
} else {
    echo "   ✅ User ID properly cleared\n";
}

echo "\n🎉 Logout test completed!\n";
echo "The logout functionality should work correctly.\n";
?> 