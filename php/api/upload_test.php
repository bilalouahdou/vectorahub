<?php
// Minimal test to isolate the 500 error
header('Content-Type: application/json');

// Test basic functionality
try {
    // Start session
    session_start();
    
    // Test required includes
    require_once '../config.php';
    require_once '../utils.php';
    
    // Test authentication
    $loggedIn = isLoggedIn();
    
    // Test getting user ID
    $userId = $_SESSION['user_id'] ?? null;
    
    echo json_encode([
        'success' => true,
        'debug' => [
            'logged_in' => $loggedIn,
            'user_id' => $userId,
            'session_data' => array_keys($_SESSION),
            'post_method' => $_SERVER['REQUEST_METHOD'],
            'files_posted' => isset($_FILES['vectorize_file'])
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'PHP Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>