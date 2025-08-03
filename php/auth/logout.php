<?php
require_once '../config.php';
require_once '../utils.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log the logout activity
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userEmail = $_SESSION['email'] ?? 'Unknown';
    
    // Log the logout activity
    if (function_exists('logActivity')) {
        logActivity('logout', "User logged out: $userEmail", $userId);
    }
    
    // Log system event
    if (function_exists('logSystemEvent')) {
        logSystemEvent('info', "User logout: $userEmail", $userId);
    }
}

// Clear all session data
$_SESSION = array();

// Destroy the session
if (session_status() == PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Clear session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear any other cookies that might be set
setcookie('PHPSESSID', '', time() - 3600, '/');
setcookie('user_id', '', time() - 3600, '/');
setcookie('email', '', time() - 3600, '/');

// Redirect to home page
header('Location: /');
exit;
?>
