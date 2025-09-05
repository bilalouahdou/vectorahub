<?php
/**
 * Debug Webhook Script
 * 
 * This script helps debug webhook issues by:
 * 1. Testing webhook endpoint accessibility
 * 2. Checking webhook logs
 * 3. Verifying database state
 */

require_once 'php/config.php';
require_once 'php/utils.php';

echo "<h1>Webhook Debug Information</h1>";

// Test 1: Check if webhook endpoint is accessible
echo "<h2>1. Webhook Endpoint Test</h2>";
$webhookUrl = 'https://vectrahub.online/webhook/stripe';
$testUrl = 'https://vectrahub.online/webhook/test';

echo "<p><strong>Webhook URL:</strong> {$webhookUrl}</p>";
echo "<p><strong>Test URL:</strong> {$testUrl}</p>";

// Test webhook test endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>Test Endpoint Response:</strong> HTTP {$httpCode}</p>";
echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";

// Test 2: Check recent webhook events in database
echo "<h2>2. Recent Webhook Events</h2>";
try {
    $pdo = getDBConnection();
    
    // Check recent payments
    $stmt = $pdo->prepare("
        SELECT p.*, sp.name as plan_name, u.email 
        FROM payments p 
        JOIN subscription_plans sp ON p.plan_id = sp.id 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.paid_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($payments) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>User</th><th>Plan</th><th>Amount</th><th>Transaction ID</th><th>Paid At</th></tr>";
        foreach ($payments as $payment) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($payment['email']) . "</td>";
            echo "<td>" . htmlspecialchars($payment['plan_name']) . "</td>";
            echo "<td>$" . number_format($payment['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($payment['transaction_id']) . "</td>";
            echo "<td>" . htmlspecialchars($payment['paid_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No recent payments found.</p>";
    }
    
    // Check recent user subscriptions
    echo "<h3>Recent User Subscriptions</h3>";
    $stmt = $pdo->prepare("
        SELECT us.*, sp.name as plan_name, u.email 
        FROM user_subscriptions us 
        JOIN subscription_plans sp ON us.plan_id = sp.id 
        JOIN users u ON us.user_id = u.id 
        ORDER BY us.updated_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($subscriptions) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>User</th><th>Plan</th><th>Active</th><th>Start Date</th><th>End Date</th><th>Stripe ID</th></tr>";
        foreach ($subscriptions as $sub) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($sub['email']) . "</td>";
            echo "<td>" . htmlspecialchars($sub['plan_name']) . "</td>";
            echo "<td>" . ($sub['active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . htmlspecialchars($sub['start_date']) . "</td>";
            echo "<td>" . htmlspecialchars($sub['end_date']) . "</td>";
            echo "<td>" . htmlspecialchars($sub['stripe_subscription_id'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No user subscriptions found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Check environment variables
echo "<h2>3. Environment Variables</h2>";
echo "<p><strong>STRIPE_WEBHOOK_SECRET:</strong> " . (defined('STRIPE_WEBHOOK_SECRET') ? 'Set' : 'Not Set') . "</p>";
echo "<p><strong>STRIPE_SECRET_KEY:</strong> " . (defined('STRIPE_SECRET_KEY') ? 'Set' : 'Not Set') . "</p>";
echo "<p><strong>STRIPE_PUBLISHABLE_KEY:</strong> " . (defined('STRIPE_PUBLISHABLE_KEY') ? 'Set' : 'Not Set') . "</p>";

// Test 4: Check webhook configuration
echo "<h2>4. Webhook Configuration</h2>";
echo "<p><strong>Expected Webhook URL:</strong> https://vectrahub.online/webhook/stripe</p>";
echo "<p><strong>Expected Events:</strong> checkout.session.completed, invoice.payment_failed, customer.subscription.deleted</p>";

echo "<h2>5. Next Steps</h2>";
echo "<ol>";
echo "<li>Make sure your Stripe webhook is pointing to: <strong>https://vectrahub.online/webhook/stripe</strong></li>";
echo "<li>Test a payment and check the webhook logs in Stripe Dashboard</li>";
echo "<li>Check your server logs for webhook processing messages</li>";
echo "<li>Verify that user plans are updated after successful payment</li>";
echo "</ol>";

?>
