<?php
require_once 'config.php';
require_once 'utils.php';

// Start session before checking auth
startSession();

// Set JSON content type
header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Authentication required']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    
    // Get database connection
    try {
        $pdo = getDBConnection();
    } catch (Exception $e) {
        error_log("Dashboard stats DB connection error: " . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
        exit;
    }
    
    // Get total jobs
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM image_jobs WHERE user_id = ?");
        $stmt->execute([$userId]);
        $totalJobs = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Dashboard stats total jobs error: " . $e->getMessage());
        $totalJobs = 0;
    }
    
    // Get successful jobs
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as successful FROM image_jobs WHERE user_id = ? AND status = 'done'");
        $stmt->execute([$userId]);
        $successfulJobs = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Dashboard stats successful jobs error: " . $e->getMessage());
        $successfulJobs = 0;
    }
    
    // Get user's current coins (calculate from subscription and usage)
    try {
        $currentCoins = getUserCoinsRemaining($userId);
    } catch (Exception $e) {
        error_log("Dashboard stats coins error: " . $e->getMessage());
        $currentCoins = 0;
    }
    
    echo json_encode([
        'ok' => true,
        'total_jobs' => (int)$totalJobs,
        'successful_jobs' => (int)$successfulJobs,
        'current_coins' => (int)$currentCoins,
        'status' => 'success'
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to load stats']);
}
?>
