<?php
/**
 * Cron job to check for expired subscriptions and send notification emails
 * Run this daily: 0 9 * * * /usr/bin/php /var/www/html/php/cron/check_expired_subscriptions.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../services/EmailService.php';

// Ensure this runs only from command line or cron
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key']) || (isset($_GET['cron_key']) && $_GET['cron_key'] !== CRON_SECRET_KEY)) {
    die('Access denied');
}

$logFile = __DIR__ . '/../../logs/subscription_check.log';

function logCronMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    echo "[$timestamp] $message\n";
}

try {
    logCronMessage("Starting subscription expiration check...");
    
    $pdo = getDBConnection();
    $emailService = new EmailService();
    
    // Find users with expired subscriptions who haven't been notified recently
    $query = "
        SELECT 
            u.id,
            u.email,
            u.full_name,
            us.plan_id,
            sp.name as plan_name,
            us.end_date as expires_at,
            us.last_notification_sent
        FROM users u
        JOIN user_subscriptions us ON u.id = us.user_id
        JOIN subscription_plans sp ON us.plan_id = sp.id
        WHERE 
            us.end_date < NOW()
            AND us.active = true
            AND (
                us.last_notification_sent IS NULL 
                OR us.last_notification_sent < NOW() - INTERVAL '7 days'
            )
    ";
    
    $stmt = $pdo->query($query);
    $expiredSubscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logCronMessage("Found " . count($expiredSubscriptions) . " expired subscriptions to process");
    
    $emailsSent = 0;
    $errors = 0;
    
    foreach ($expiredSubscriptions as $subscription) {
        try {
            // Send expiration email
            $emailService->sendSubscriptionExpiredEmail(
                $subscription['email'],
                $subscription['full_name'],
                $subscription['plan_name']
            );
            
            // Update notification timestamp
            $updateStmt = $pdo->prepare("
                UPDATE user_subscriptions 
                SET 
                    last_notification_sent = NOW(),
                    active = false
                WHERE user_id = ? AND plan_id = ?
            ");
            $updateStmt->execute([$subscription['id'], $subscription['plan_id']]);
            
            // Log activity
            logActivity(
                'SUBSCRIPTION_EXPIRED_EMAIL', 
                "Subscription expired email sent for plan: {$subscription['plan_name']}", 
                $subscription['id']
            );
            
            $emailsSent++;
            logCronMessage("Sent expiration email to: {$subscription['email']} (Plan: {$subscription['plan_name']})");
            
        } catch (Exception $e) {
            $errors++;
            $errorMsg = "Failed to send email to {$subscription['email']}: " . $e->getMessage();
            logCronMessage("ERROR: $errorMsg");
            
            // Log the error
            logActivity(
                'SUBSCRIPTION_EMAIL_FAILED', 
                $errorMsg, 
                $subscription['id']
            );
        }
        
        // Small delay to avoid overwhelming the email service
        usleep(500000); // 0.5 seconds
    }
    
    // Also check for subscriptions expiring in 3 days (warning email)
    $warningQuery = "
        SELECT 
            u.id,
            u.email,
            u.full_name,
            us.plan_id,
            sp.name as plan_name,
            us.end_date as expires_at,
            us.warning_sent
        FROM users u
        JOIN user_subscriptions us ON u.id = us.user_id
        JOIN subscription_plans sp ON us.plan_id = sp.id
        WHERE 
            us.end_date > NOW()
            AND us.end_date <= NOW() + INTERVAL '3 days'
            AND us.active = true
            AND (us.warning_sent IS NULL OR us.warning_sent = FALSE)
    ";
    
    $stmt = $pdo->query($warningQuery);
    $warningSubscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logCronMessage("Found " . count($warningSubscriptions) . " subscriptions expiring in 3 days");
    
    foreach ($warningSubscriptions as $subscription) {
        try {
            // Send warning email (reuse expired template with different subject)
            $emailService->sendSubscriptionExpiredEmail(
                $subscription['email'],
                $subscription['full_name'],
                $subscription['plan_name'] . " (Expires Soon)"
            );
            
            // Mark warning as sent
            $updateStmt = $pdo->prepare("
                UPDATE user_subscriptions 
                SET warning_sent = TRUE
                WHERE user_id = ? AND plan_id = ?
            ");
            $updateStmt->execute([$subscription['id'], $subscription['plan_id']]);
            
            logCronMessage("Sent warning email to: {$subscription['email']} (Plan: {$subscription['plan_name']})");
            
        } catch (Exception $e) {
            logCronMessage("ERROR: Failed to send warning email to {$subscription['email']}: " . $e->getMessage());
        }
        
        usleep(500000); // 0.5 seconds
    }
    
    logCronMessage("Subscription check completed. Emails sent: $emailsSent, Errors: $errors");
    
    // Clean up old log entries (keep last 30 days)
    $cleanupQuery = "DELETE FROM activity_logs WHERE created_at < NOW() - INTERVAL '30 days'";
    $pdo->exec($cleanupQuery);
    
} catch (Exception $e) {
    logCronMessage("CRITICAL ERROR: " . $e->getMessage());
    
    // Send incident email to admin
    try {
        $emailService = new EmailService();
        $emailService->sendIncidentEmail(
            "Subscription check cron job failed: " . $e->getMessage(),
            ['cron_job' => 'check_expired_subscriptions'],
            ['timestamp' => date('Y-m-d H:i:s')]
        );
    } catch (Exception $emailError) {
        logCronMessage("Failed to send incident email: " . $emailError->getMessage());
    }
}

logCronMessage("=== Subscription check process completed ===");
?>
