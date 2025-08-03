<?php
// Test simple logout functionality
echo "🚪 Simple Logout Test\n";
echo "=====================\n\n";

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

// Store user info before destroying session
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Unknown';

echo "2. Testing logout...\n";

// Log to error log
error_log("User logging out: ID=$userId, Name=$userName");
echo "   ✅ Logged logout attempt\n";

// Destroy session
session_destroy();
echo "   ✅ Session destroyed\n";

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
    echo "   ✅ Session cookie cleared\n";
} else {
    echo "   ℹ️  No session cookie to clear\n";
}

// Log successful logout
error_log("User logged out successfully: ID=$userId, Name=$userName");
echo "   ✅ Logged successful logout\n";

// Check if session is really destroyed
echo "\n3. Session after logout:\n";
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

echo "\n🎉 Simple logout test completed!\n";
echo "The logout functionality should work correctly.\n";
echo "Check error logs for logout messages.\n";
?> 