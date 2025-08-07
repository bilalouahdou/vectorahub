<?php
// Test what dashboard_stats.php is actually returning
header('Content-Type: text/plain');

echo "=== Testing Dashboard Stats Endpoint ===\n\n";

echo "1. Testing direct access to dashboard_stats.php:\n";
$output = file_get_contents('https://vectrahub.online/php/dashboard_stats.php');
echo "Response: " . var_export($output, true) . "\n\n";

echo "2. Testing with cURL:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://vectrahub.online/php/dashboard_stats.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE'] ?? '');
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . var_export($result, true) . "\n\n";

echo "3. Current session info:\n";
session_start();
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Session data: " . var_export($_SESSION, true) . "\n";
?>

