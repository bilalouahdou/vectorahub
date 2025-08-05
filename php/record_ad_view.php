<?php
session_start();
require_once 'config.php';
require_once 'utils.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'User not authenticated'], 401);
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 400);
}

$userId = $_SESSION['user_id'];
$coinsPerView = 3; // 3 coins per ad view

try {
    $pdo = getDBConnection();
    
    // Check daily ad view limit
    $currentViews = getDailyAdViews($userId);
    $maxViews = 5;
    
    if ($currentViews >= $maxViews) {
        jsonResponse(['success' => false, 'error' => 'Daily ad view limit reached'], 400);
    }
    
    // Record the ad view
    $recorded = recordAdView($userId);
    if (!$recorded) {
        jsonResponse(['success' => false, 'error' => 'Failed to record ad view'], 500);
    }
    
    // Award coins
    $awarded = addBonusCoins($userId, $coinsPerView, 'ad_view_reward');
    if (!$awarded) {
        jsonResponse(['success' => false, 'error' => 'Failed to award coins'], 500);
    }
    
    // Get updated coin balance
    $coinsRemaining = getUserCoinsRemaining($userId);
    
    // Log the activity
    logActivity('AD_VIEW_REWARD', "User $userId earned $coinsPerView coins from watching an ad", $userId);
    
    jsonResponse([
        'success' => true,
        'coins_earned' => $coinsPerView,
        'coins_remaining' => $coinsRemaining,
        'new_view_count' => $currentViews + 1
    ]);
    
} catch (Exception $e) {
    error_log("Error recording ad view: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Server error occurred'], 500);
}
?> 