<?php
/**
 * Stripe Webhook Handler
 * 
 * This endpoint processes Stripe webhook events:
 * - checkout.session.completed: Updates user plan after successful payment
 * - invoice.payment_failed: Handles failed payments
 * - customer.subscription.deleted: Handles subscription cancellations
 */

// Prevent any output before headers
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

// Include required files
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Set JSON headers
header('Content-Type: application/json');

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Get the webhook payload
    $payload = @file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (!$payload) {
        error_log('Stripe webhook: Empty payload received');
        http_response_code(400);
        echo json_encode(['error' => 'Empty payload']);
        exit;
    }

    // Initialize Stripe
    require_once __DIR__ . '/../../vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    // Get webhook secret from environment
    $webhookSecret = getenv('STRIPE_WEBHOOK_SECRET');
    if (!$webhookSecret) {
        error_log('⚠️  STRIPE_WEBHOOK_SECRET not set in environment');
        http_response_code(500);
        echo json_encode(['error' => 'Webhook secret not configured']);
        exit;
    }

    // Verify webhook signature with detailed logging
    try {
        error_log('🔍 Attempting to verify webhook signature...');
        error_log('🔍 Payload length: ' . strlen($payload));
        error_log('🔍 Signature header: ' . substr($sigHeader, 0, 50) . '...');
        
        $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        error_log('✅ Webhook signature verified successfully');
        
    } catch (\UnexpectedValueException $e) {
        error_log('⚠️  Invalid payload: ' . $e->getMessage());
        error_log('⚠️  Payload preview: ' . substr($payload, 0, 200) . '...');
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit;
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        error_log('⚠️  Invalid signature: ' . $e->getMessage());
        error_log('⚠️  Expected secret starts with: ' . substr($webhookSecret, 0, 10) . '...');
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }

    // Log webhook event
    error_log('Stripe webhook received: ' . $event->type);
    error_log('Stripe webhook event data: ' . json_encode($event->data->object));

    // Handle different event types
    switch ($event->type) {
        case 'checkout.session.completed':
            error_log('Processing checkout.session.completed event');
            handleCheckoutSessionCompleted($event->data->object);
            break;
            
        case 'invoice.payment_failed':
            error_log('Processing invoice.payment_failed event');
            handleInvoicePaymentFailed($event->data->object);
            break;
            
        case 'customer.subscription.deleted':
            error_log('Processing customer.subscription.deleted event');
            handleSubscriptionDeleted($event->data->object);
            break;
            
        default:
            // Log unhandled events but don't fail
            error_log('Stripe webhook: Unhandled event type: ' . $event->type);
            break;
    }

    // Return success
    echo json_encode(['received' => true]);

} catch (Exception $e) {
    error_log('Stripe webhook error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Handle successful checkout completion
 */
function handleCheckoutSessionCompleted($session) {
    try {
        error_log('🔄 handleCheckoutSessionCompleted called with session ID: ' . $session->id);
        
        $pdo = getDBConnection();
        
        // Extract data from session - try both client_reference_id and metadata
        $userId = $session->client_reference_id ?? $session->metadata->user_id ?? null;
        $planId = $session->metadata->plan_id ?? null;
        $amount = $session->amount_total / 100; // Convert from cents
        
        error_log("📊 Extracted data - userId: {$userId}, planId: {$planId}, amount: {$amount}");
        error_log("📊 Session metadata: " . json_encode($session->metadata));
        error_log("📊 Client reference ID: " . ($session->client_reference_id ?? 'NULL'));
        
        if (!$userId || !$planId) {
            error_log('⚠️  Missing userId or planId in session ' . $session->id);
            error_log('⚠️  userId: ' . ($userId ?? 'NULL') . ', planId: ' . ($planId ?? 'NULL'));
            return;
        }

        // Get plan details
        $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            error_log('Stripe webhook: Plan not found for ID: ' . $planId);
            return;
        }

        // Calculate expiration date based on billing period
        $expirationDate = date('Y-m-d H:i:s');
        if ($plan['billing_period'] === 'monthly') {
            $expirationDate = date('Y-m-d H:i:s', strtotime('+1 month'));
        } elseif ($plan['billing_period'] === 'yearly') {
            $expirationDate = date('Y-m-d H:i:s', strtotime('+1 year'));
        }

        // Begin transaction
        $pdo->beginTransaction();

        try {
            // Deactivate any existing subscriptions
            $stmt = $pdo->prepare("
                UPDATE user_subscriptions 
                SET active = false, updated_at = NOW() 
                WHERE user_id = ? AND active = true
            ");
            $stmt->execute([$userId]);

            // Create new subscription
            $stmt = $pdo->prepare("
                INSERT INTO user_subscriptions 
                (user_id, plan_id, active, start_date, end_date, auto_renew, stripe_subscription_id, updated_at) 
                VALUES (?, ?, true, NOW(), ?, true, ?, NOW())
            ");
            $stmt->execute([
                $userId, 
                $planId, 
                $expirationDate,
                $session->subscription ?? null
            ]);

            // Note: coins_remaining is not stored in users table in this schema
            // The subscription and coins are managed through user_subscriptions table
            error_log("📝 User subscription created - plan: {$plan['name']}, coins: {$plan['coin_limit']}");

            // Record payment
            $stmt = $pdo->prepare("
                INSERT INTO payments 
                (user_id, plan_id, amount, payment_method, transaction_id, paid_at) 
                VALUES (?, ?, ?, 'stripe', ?, NOW())
            ");
            $stmt->execute([
                $userId, 
                $planId, 
                $amount, 
                $session->id
            ]);
            error_log("💰 Payment recorded for user {$userId}, amount: $" . number_format($amount, 2));

            // Log activity
            logActivity(
                'SUBSCRIPTION_PURCHASED',
                "Subscription purchased: {$plan['name']} for $" . number_format($amount, 2),
                $userId
            );

            $pdo->commit();
            error_log("✅ Successfully processed checkout for user {$userId}, plan {$plan['name']}");

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Stripe webhook: Database error during checkout processing: ' . $e->getMessage());
            throw $e;
        }

    } catch (Exception $e) {
        error_log('Stripe webhook: Error processing checkout session: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle failed payment
 */
function handleInvoicePaymentFailed($invoice) {
    try {
        $pdo = getDBConnection();
        
        // Get subscription details
        $subscriptionId = $invoice->subscription ?? null;
        if (!$subscriptionId) {
            return;
        }

        // Find user subscription
        $stmt = $pdo->prepare("
            SELECT us.*, u.email, u.full_name 
            FROM user_subscriptions us 
            JOIN users u ON us.user_id = u.id 
            WHERE us.stripe_subscription_id = ?
        ");
        $stmt->execute([$subscriptionId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($subscription) {
            // Mark subscription as inactive
            $stmt = $pdo->prepare("
                UPDATE user_subscriptions 
                SET active = false, updated_at = NOW() 
                WHERE stripe_subscription_id = ?
            ");
            $stmt->execute([$subscriptionId]);

            // Log activity
            logActivity(
                'PAYMENT_FAILED',
                "Payment failed for subscription: {$subscriptionId}",
                $subscription['user_id']
            );

            error_log("Stripe webhook: Payment failed for user {$subscription['user_id']}, subscription {$subscriptionId}");
        }

    } catch (Exception $e) {
        error_log('Stripe webhook: Error processing payment failure: ' . $e->getMessage());
    }
}

/**
 * Handle subscription deletion
 */
function handleSubscriptionDeleted($subscription) {
    try {
        $pdo = getDBConnection();
        
        $subscriptionId = $subscription->id;
        
        // Find and deactivate user subscription
        $stmt = $pdo->prepare("
            SELECT us.*, u.email, u.full_name 
            FROM user_subscriptions us 
            JOIN users u ON us.user_id = u.id 
            WHERE us.stripe_subscription_id = ?
        ");
        $stmt->execute([$subscriptionId]);
        $userSubscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userSubscription) {
            // Mark subscription as inactive
            $stmt = $pdo->prepare("
                UPDATE user_subscriptions 
                SET active = false, auto_renew = false, updated_at = NOW() 
                WHERE stripe_subscription_id = ?
            ");
            $stmt->execute([$subscriptionId]);

            // Log activity
            logActivity(
                'SUBSCRIPTION_CANCELLED',
                "Subscription cancelled: {$subscriptionId}",
                $userSubscription['user_id']
            );

            error_log("Stripe webhook: Subscription cancelled for user {$userSubscription['user_id']}, subscription {$subscriptionId}");
        }

    } catch (Exception $e) {
        error_log('Stripe webhook: Error processing subscription deletion: ' . $e->getMessage());
    }
}

// Ensure output buffer is flushed
ob_end_flush();
?>
