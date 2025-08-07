<?php
require_once 'php/config.php';
require_once 'php/utils.php';

header('Content-Type: text/plain');

echo "=== Testing Fixed Checkout Script ===\n\n";

// Set up session
startSession();
$_SESSION['user_id'] = 11;
$_SESSION['csrf_token'] = generateCsrfToken();

echo "Session setup:\n";
echo "- User ID: " . $_SESSION['user_id'] . "\n";
echo "- CSRF Token: " . $_SESSION['csrf_token'] . "\n\n";

// Simulate POST request
$_POST = [
    'csrf_token' => $_SESSION['csrf_token'],
    'plan_id' => 9
];
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "POST data:\n";
print_r($_POST);
echo "\n";

echo "=== Calling fixed checkout script ===\n";

// Capture the output
ob_start();
try {
    include 'php/create_checkout_session_fixed.php';
    $output = ob_get_clean();
    
    echo "Fixed script output:\n";
    echo "Length: " . strlen($output) . " characters\n";
    echo "Content:\n";
    echo $output;
    echo "\n\n";
    
    // Test JSON validity
    $decoded = json_decode($output, true);
    if ($decoded === null) {
        echo "❌ Invalid JSON! Error: " . json_last_error_msg() . "\n";
    } else {
        echo "✅ Valid JSON response:\n";
        print_r($decoded);
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>

