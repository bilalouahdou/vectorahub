<?php
/**
 * Manual Webhook Trigger Script
 * 
 * This script manually triggers a webhook event for testing purposes
 */

require_once 'php/config.php';
require_once 'php/utils.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo "Invalid CSRF token";
    exit;
}

// Get parameters
$userId = $_POST['user_id'] ?? null;
$planId = $_POST['plan_id'] ?? null;
$sessionId = $_POST['session_id'] ?? null;

if (!$userId || !$planId || !$sessionId) {
    http_response_code(400);
    echo "Missing required parameters";
    exit;
}

echo "<h1>Manual Webhook Trigger</h1>";

try {
    // Get plan details
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        echo "<p><strong>Error:</strong> Plan not found</p>";
        exit;
    }

    // Create a test webhook payload
    $webhookPayload = [
        'id' => 'evt_test_' . time(),
        'object' => 'event',
        'api_version' => '2020-08-27',
        'created' => time(),
        'data' => [
            'object' => [
                'id' => $sessionId,
                'object' => 'checkout.session',
                'amount_total' => 100, // $1.00
                'client_reference_id' => $userId,
                'metadata' => [
                    'user_id' => $userId,
                    'plan_id' => $planId,
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

    echo "<p><strong>✅ Test webhook payload created</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($webhookPayload, JSON_PRETTY_PRINT)) . "</pre>";

    // Manually call the webhook handler
    echo "<h2>Processing Webhook...</h2>";
    
    // Include the webhook handler
    require_once 'php/webhook/stripe.php';
    
    // Create a mock session object
    $mockSession = (object) [
        'id' => $sessionId,
        'client_reference_id' => $userId,
        'metadata' => (object) [
            'user_id' => $userId,
            'plan_id' => $planId,
        ],
        'amount_total' => 100,
        'subscription' => null,
    ];

    // Call the handler function directly
    handleCheckoutSessionCompleted($mockSession);
    
    echo "<p><strong>✅ Webhook processed successfully!</strong></p>";
    
    // Show updated user state
    echo "<h2>Updated User State</h2>";
    
    // Get current subscription
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
    
    // Get recent payments
    $stmt = $pdo->prepare("
        SELECT p.*, sp.name as plan_name 
        FROM payments p 
        JOIN subscription_plans sp ON p.plan_id = sp.id 
        WHERE p.user_id = ? 
        ORDER BY p.paid_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($payments) {
        echo "<h3>Recent Payments</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Plan</th><th>Amount</th><th>Transaction ID</th><th>Paid At</th></tr>";
        foreach ($payments as $payment) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($payment['plan_name']) . "</td>";
            echo "<td>$" . number_format($payment['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($payment['transaction_id']) . "</td>";
            echo "<td>" . htmlspecialchars($payment['paid_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='test_webhook.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Back to Test Page</a></p>";
?>

