<?php
// Direct debug of dashboard_stats.php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Dashboard Stats Debug ===\n\n";

// Test session
session_start();
echo "1. Session Info:\n";
echo "   Session ID: " . session_id() . "\n";
echo "   User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "   User logged in: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "\n\n";

// Test database connection
echo "2. Database Connection:\n";
try {
    require_once 'php/config.php';
    $pdo = getDBConnection();
    echo "   Database: CONNECTED\n";
    
    // Test if user exists
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   User found: " . ($user ? 'YES - ' . $user['full_name'] : 'NO') . "\n";
    }
} catch (Exception $e) {
    echo "   Database: ERROR - " . $e->getMessage() . "\n";
}

echo "\n3. Testing dashboard_stats.php directly:\n";

// Capture output from dashboard_stats.php
ob_start();
try {
    include 'php/dashboard_stats.php';
    $output = ob_get_contents();
} catch (Exception $e) {
    $output = "ERROR: " . $e->getMessage();
}
ob_end_clean();

echo "   Raw output: " . var_export($output, true) . "\n";
echo "   Output length: " . strlen($output) . " characters\n";

// Check if it's valid JSON
if ($output) {
    $json = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "   Valid JSON: YES\n";
        echo "   Decoded: " . var_export($json, true) . "\n";
    } else {
        echo "   Valid JSON: NO\n";
        echo "   JSON Error: " . json_last_error_msg() . "\n";
    }
}
?>

