<?php
/**
 * Test Webhook Script
 * 
 * This script helps test webhook functionality by:
 * 1. Creating a test checkout session
 * 2. Simulating a webhook event
 * 3. Verifying the database update
 */

require_once 'php/config.php';
require_once 'php/utils.php';

// Start session and ensure proper initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo "<h1>Webhook Test Script</h1>";

// Check if we have a user to test with
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo "<p><strong>Error:</strong> No user logged in. Please log in first.</p>";
    exit;
}

echo "<p><strong>Testing with user ID:</strong> {$userId}</p>";

// Get available plans
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE price > 0 ORDER BY price ASC LIMIT 1");
    $stmt->execute();
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        echo "<p><strong>Error:</strong> No paid plans found in database.</p>";
        exit;
    }
    
    echo "<p><strong>Testing with plan:</strong> {$plan['name']} (ID: {$plan['id']})</p>";
    
} catch (Exception $e) {
    echo "<p><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 1: Create a checkout session
echo "<h2>1. Creating Test Checkout Session</h2>";

try {
    require_once 'vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    $sessionData = [
        'success_url' => 'https://vectrahub.online/billing?success=true',
        'cancel_url' => 'https://vectrahub.online/billing?canceled=true',
        'client_reference_id' => $userId,
        'metadata' => [
            'user_id' => $userId,
            'plan_id' => $plan['id'],
        ],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => $plan['name'],
                ],
                'unit_amount' => 100, // $1.00 for testing
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
    ];
    
    $checkout_session = \Stripe\Checkout\Session::create($sessionData);
    
    echo "<p><strong>✅ Checkout session created:</strong> {$checkout_session->id}</p>";
    echo "<p><strong>Checkout URL:</strong> <a href='{$checkout_session->url}' target='_blank'>Complete Payment</a></p>";
    
} catch (Exception $e) {
    echo "<p><strong>Stripe Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 2: Simulate webhook event
echo "<h2>2. Simulating Webhook Event</h2>";

try {
    // Create a test event payload
    $eventPayload = [
        'id' => 'evt_test_' . time(),
        'object' => 'event',
        'api_version' => '2020-08-27',
        'created' => time(),
        'data' => [
            'object' => [
                'id' => $checkout_session->id,
                'object' => 'checkout.session',
                'amount_total' => 100,
                'client_reference_id' => $userId,
                'metadata' => [
                    'user_id' => $userId,
                    'plan_id' => $plan['id'],
                ],
                'payment_status' => 'paid',
                'status' => 'complete',
            ]
        ],
        'livemode' => false,
        'pending_webhooks' => 1,
        'request' => [
            'id' => 'req_test_' . time(),
            'idempotency_key' => null,
        ],
        'type' => 'checkout.session.completed',
    ];
    
    echo "<p><strong>✅ Test event payload created</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($eventPayload, JSON_PRETTY_PRINT)) . "</pre>";
    
} catch (Exception $e) {
    echo "<p><strong>Error creating test event:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Check current user state
echo "<h2>3. Current User State</h2>";

try {
    // Get user info
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>User ID</td><td>{$user['id']}</td></tr>";
        echo "<tr><td>Email</td><td>{$user['email']}</td></tr>";
        echo "</table>";
        
        // Get current subscription info
        $stmt = $pdo->prepare("
            SELECT us.*, sp.name as plan_name 
            FROM user_subscriptions us 
            JOIN subscription_plans sp ON us.plan_id = sp.id 
            WHERE us.user_id = ? AND us.active = true 
            ORDER BY us.updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subscription) {
            echo "<h3>Current Active Subscription</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>Plan Name</td><td>{$subscription['plan_name']}</td></tr>";
            echo "<tr><td>Plan ID</td><td>{$subscription['plan_id']}</td></tr>";
            echo "<tr><td>Start Date</td><td>{$subscription['start_date']}</td></tr>";
            echo "<tr><td>End Date</td><td>{$subscription['end_date']}</td></tr>";
            echo "<tr><td>Active</td><td>" . ($subscription['active'] ? 'Yes' : 'No') . "</td></tr>";
            echo "</table>";
        } else {
            echo "<p><strong>No active subscription found</strong></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>4. Manual Webhook Test</h2>";
echo "<p>You can manually trigger a webhook test without making a real payment:</p>";
echo "<form method='POST' action='test_webhook_trigger.php'>";
echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>";
echo "<input type='hidden' name='user_id' value='" . htmlspecialchars($userId) . "'>";
echo "<input type='hidden' name='plan_id' value='" . htmlspecialchars($plan['id']) . "'>";
echo "<input type='hidden' name='session_id' value='" . htmlspecialchars($checkout_session->id) . "'>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
echo "🔧 Trigger Test Webhook";
echo "</button>";
echo "</form>";

echo "<h2>5. Next Steps</h2>";
echo "<ol>";
echo "<li>Click the checkout URL above to complete a test payment</li>";
echo "<li>Or use the manual webhook trigger button above</li>";
echo "<li>Check Stripe Dashboard → Events for the webhook event</li>";
echo "<li>Check your server logs for webhook processing messages</li>";
echo "<li>Refresh this page to see if user state changed</li>";
echo "</ol>";

?>
