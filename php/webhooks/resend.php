<?php
/**
 * Resend Webhook Handler
 * Handles bounce, complaint, and delivery events from Resend
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

// Verify webhook signature (recommended for security)
function verifyResendSignature($payload, $signature, $secret) {
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

try {
    // Get raw POST data
    $payload = file_get_contents('php://input');
    
    if (!$payload) {
        http_response_code(400);
        echo json_encode(['error' => 'No payload received']);
        exit;
    }
    
    // Verify signature if webhook secret is configured
    if (defined('RESEND_WEBHOOK_SECRET') && RESEND_WEBHOOK_SECRET) {
        $signature = $_SERVER['HTTP_RESEND_SIGNATURE'] ?? '';
        
        if (!$signature || !verifyResendSignature($payload, $signature, RESEND_WEBHOOK_SECRET)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }
    }
    
    // Parse the event data
    $event = json_decode($payload, true);
    
    if (!$event) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON payload']);
        exit;
    }
    
    // Log the webhook event
    error_log("Resend webhook received: " . json_encode($event));
    
    $pdo = getDBConnection();
    
    // Process different event types
    switch ($event['type']) {
        case 'email.sent':
            handleEmailSent($event['data'], $pdo);
            break;
            
        case 'email.delivered':
            handleEmailDelivered($event['data'], $pdo);
            break;
            
        case 'email.delivery_delayed':
            handleEmailDelayed($event['data'], $pdo);
            break;
            
        case 'email.bounced':
            handleEmailBounced($event['data'], $pdo);
            break;
            
        case 'email.complained':
            handleEmailComplained($event['data'], $pdo);
            break;
            
        case 'email.opened':
            handleEmailOpened($event['data'], $pdo);
            break;
            
        case 'email.clicked':
            handleEmailClicked($event['data'], $pdo);
            break;
            
        default:
            // Log unknown event type
            error_log("Unknown Resend event type: " . $event['type']);
    }
    
    // Respond with success
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log("Resend webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Handle email sent event
 */
function handleEmailSent($data, $pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO email_events (email_id, event_type, email_address, timestamp, data) 
        VALUES (?, 'sent', ?, NOW(), ?)
        ON CONFLICT (email_id, event_type) DO NOTHING
    ");
    $stmt->execute([
        $data['id'] ?? null,
        $data['to'][0] ?? null,
        json_encode($data)
    ]);
}

/**
 * Handle email delivered event
 */
function handleEmailDelivered($data, $pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO email_events (email_id, event_type, email_address, timestamp, data) 
        VALUES (?, 'delivered', ?, NOW(), ?)
        ON CONFLICT (email_id, event_type) DO NOTHING
    ");
    $stmt->execute([
        $data['id'] ?? null,
        $data['to'][0] ?? null,
        json_encode($data)
    ]);
}

/**
 * Handle email delayed event
 */
function handleEmailDelayed($data, $pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO email_events (email_id, event_type, email_address, timestamp, data) 
        VALUES (?, 'delayed', ?, NOW(), ?)
    ");
    $stmt->execute([
        $data['id'] ?? null,
        $data['to'][0] ?? null,
        json_encode($data)
    ]);
}

/**
 * Handle email bounced event - mark email as invalid
 */
function handleEmailBounced($data, $pdo) {
    $email = $data['to'][0] ?? null;
    $bounceType = $data['bounce']['type'] ?? 'unknown';
    
    if ($email) {
        // Log the bounce
        $stmt = $pdo->prepare("
            INSERT INTO email_events (email_id, event_type, email_address, timestamp, data) 
            VALUES (?, 'bounced', ?, NOW(), ?)
        ");
        $stmt->execute([
            $data['id'] ?? null,
            $email,
            json_encode($data)
        ]);
        
        // Mark email as bounced in users table if it's a hard bounce
        if ($bounceType === 'hard') {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET 
                    email_bounced = TRUE,
                    bounce_reason = ?,
                    updated_at = NOW()
                WHERE email = ?
            ");
            $stmt->execute([
                $data['bounce']['reason'] ?? 'Hard bounce',
                $email
            ]);
            
            error_log("Hard bounce recorded for email: $email");
        }
    }
}

/**
 * Handle email complained event - mark as spam complaint
 */
function handleEmailComplained($data, $pdo) {
    $email = $data['to'][0] ?? null;
    
    if ($email) {
        // Log the complaint
        $stmt = $pdo->prepare("
            INSERT INTO email_events (email_id, event_type, email_address, timestamp, data) 
            VALUES (?, 'complained', ?, NOW(), ?)
        ");
        $stmt->execute([
            $data['id'] ?? null,
            $email,
            json_encode($data)
        ]);
        
        // Mark user as complained - stop sending emails
        $stmt = $pdo->prepare("
            UPDATE users 
            SET 
                email_complained = TRUE,
                email_unsubscribed = TRUE,
                updated_at = NOW()
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        
        error_log("Spam complaint recorded for email: $email");
    }
}

/**
 * Handle email opened event
 */
function handleEmailOpened($data, $pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO email_events (email_id, event_type, email_address, timestamp, data) 
        VALUES (?, 'opened', ?, NOW(), ?)
    ");
    $stmt->execute([
        $data['id'] ?? null,
        $data['to'][0] ?? null,
        json_encode($data)
    ]);
}

/**
 * Handle email clicked event
 */
function handleEmailClicked($data, $pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO email_events (email_id, event_type, email_address, timestamp, data) 
        VALUES (?, 'clicked', ?, NOW(), ?)
    ");
    $stmt->execute([
        $data['id'] ?? null,
        $data['to'][0] ?? null,
        json_encode($data)
    ]);
}
?>
