<?php
// Simple logout - no complex logging
require_once 'php/config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple logging
if (isset($_SESSION['user_id'])) {
    error_log("User logging out: ID=" . $_SESSION['user_id'] . ", Name=" . ($_SESSION['user_name'] ?? 'Unknown'));
}

// Destroy session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to home page
header('Location: /');
exit;
?> 