<?php
// Simple logout without database logging
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Store user info before destroying session
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Unknown';

// Log to error log
error_log("User logging out: ID=$userId, Name=$userName");

// Destroy session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Log successful logout
error_log("User logged out successfully: ID=$userId, Name=$userName");

// Redirect to home page
header('Location: /');
exit;
?> 