<?php
// Clean dashboard stats API - no HTML errors allowed
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header immediately
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        ob_clean();
        echo json_encode(['error' => 'Not authenticated', 'code' => 'AUTH_REQUIRED']);
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Include database connection
    require_once __DIR__ . '/../config.php';
    $pdo = getDBConnection();

    // Get stats with error handling for each query
    $totalJobs = 0;
    $successfulJobs = 0;
    $currentCoins = 0;

    try {
        // Get total jobs
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM image_jobs WHERE user_id = ?");
        $stmt->execute([$userId]);
        $totalJobs = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        // If table doesn't exist, assume 0
        $totalJobs = 0;
    }

    try {
        // Get successful jobs
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM image_jobs WHERE user_id = ? AND status = 'done'");
        $stmt->execute([$userId]);
        $successfulJobs = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        $successfulJobs = 0;
    }

    try {
        // Get current coins - simplified calculation
        $stmt = $pdo->prepare("
            SELECT sp.coin_limit 
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = ? AND us.active = TRUE AND us.end_date >= CURRENT_DATE
            ORDER BY us.start_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $coinLimit = $stmt->fetchColumn();
        
        if ($coinLimit) {
            // Simple coin calculation - just use the limit for now
            $currentCoins = (int)$coinLimit;
        } else {
            // Default free plan coins
            $currentCoins = 200;
        }
    } catch (Exception $e) {
        $currentCoins = 200; // Default
    }

    // Clear any unexpected output
    ob_clean();

    // Return clean JSON
    echo json_encode([
        'total_jobs' => $totalJobs,
        'successful_jobs' => $successfulJobs,
        'current_coins' => $currentCoins,
        'status' => 'success'
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'error' => 'Server error',
        'status' => 'error'
    ]);
}

ob_end_flush();
?>

