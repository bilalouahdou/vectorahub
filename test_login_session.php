<?php
require_once 'php/config.php';
require_once 'php/utils.php';

header('Content-Type: application/json');

startSession();

$response = [
    'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'user_logged_in' => isLoggedIn(),
    'user_id' => $_SESSION['user_id'] ?? null,
    'session_vars' => [
        'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET',
        'user_name' => $_SESSION['user_name'] ?? 'NOT SET',
        'role' => $_SESSION['role'] ?? 'NOT SET'
    ],
    'cookie_params' => session_get_cookie_params()
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>

