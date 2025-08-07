<?php
require_once 'php/config.php';
require_once 'php/utils.php';

header('Content-Type: text/plain'); // Use plain text to see raw response

echo "=== Testing Checkout API ===\n\n";

startSession();

// Set up a test user session
$_SESSION['user_id'] = 11; // Your user ID from the login test
$_SESSION['csrf_token'] = generateCsrfToken();

echo "Session setup:\n";
echo "- User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "- CSRF Token: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "\n\n";

// Test data for POST request
$testData = [
    'csrf_token' => $_SESSION['csrf_token'],
    'plan_id' => 9 // A test plan ID
];

echo "Test data:\n";
print_r($testData);
echo "\n";

// Simulate the POST request
$_POST = $testData;
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "=== Starting checkout script execution ===\n";

// Capture output
ob_start();
try {
    include 'php/create_checkout_session.php';
    $output = ob_get_clean();
    echo "Raw output from create_checkout_session.php:\n";
    echo "Length: " . strlen($output) . " characters\n";
    echo "Content:\n";
    echo $output;
} catch (Exception $e) {
    ob_end_clean();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>

