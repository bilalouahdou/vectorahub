<?php
header('Content-Type: text/plain');
echo "=== Testing Create Checkout Final ===\n\n";

// Test session
session_start();
echo "1. Session Info:\n";
echo "   User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "   Logged in: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "\n\n";

// Test direct POST to checkout endpoint
echo "2. Testing checkout endpoint with cURL:\n";

$postData = ['plan_id' => '9']; // Test with plan ID 9

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://vectrahub.online/php/create_checkout_final.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE'] ?? '');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: " . $httpCode . "\n";
echo "   cURL Error: " . ($error ?: 'None') . "\n";
echo "   Response Length: " . strlen($result) . " bytes\n";
echo "   Raw Response: " . var_export($result, true) . "\n\n";

if ($result) {
    $json = json_decode($result, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "   Valid JSON: YES\n";
        echo "   Decoded: " . var_export($json, true) . "\n";
    } else {
        echo "   Valid JSON: NO\n";
        echo "   JSON Error: " . json_last_error_msg() . "\n";
    }
}

// Test database connection
echo "\n3. Database Test:\n";
try {
    require_once 'php/config.php';
    $pdo = getDBConnection();
    echo "   Database: CONNECTED\n";
    
    // Test plan query
    $stmt = $pdo->prepare("SELECT id, name, stripe_price_id FROM subscription_plans WHERE id = ?");
    $stmt->execute([9]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Plan 9: " . ($plan ? $plan['name'] . ' (' . $plan['stripe_price_id'] . ')' : 'NOT FOUND') . "\n";
} catch (Exception $e) {
    echo "   Database: ERROR - " . $e->getMessage() . "\n";
}

// Test Stripe keys
echo "\n4. Stripe Configuration:\n";
echo "   Publishable Key: " . (STRIPE_PUBLISHABLE_KEY ? 'SET' : 'NOT SET') . "\n";
echo "   Secret Key: " . (STRIPE_SECRET_KEY ? 'SET' : 'NOT SET') . "\n";
?>

