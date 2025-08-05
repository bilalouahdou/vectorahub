<?php
require_once 'utils.php';
redirectIfNotAuth();

header('Content-Type: application/json');

try {
    $pdo = connectDB();
    $userId = $_SESSION['user_id'];
    
    // Get total jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM image_jobs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalJobs = $stmt->fetchColumn();
    
    // Get successful jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) as successful FROM image_jobs WHERE user_id = ? AND status = 'done'");
    $stmt->execute([$userId]);
    $successfulJobs = $stmt->fetchColumn();
    
    // Get user's current coins (calculate from subscription and usage)
    $currentCoins = getUserCoinsRemaining($userId);
    
    echo json_encode([
        'total_jobs' => (int)$totalJobs,
        'successful_jobs' => (int)$successfulJobs,
        'current_coins' => (int)$currentCoins,
        'status' => 'success'
    ]);
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load stats', 'details' => $e->getMessage()]);
}
?>
