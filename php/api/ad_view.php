<?php
require_once '../config.php';
require_once '../utils.php';
startSession(); // Start session before checking auth
redirectIfNotAuth();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    jsonResponse(['success' => false, 'error' => 'User not authenticated.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        jsonResponse(['success' => false, 'error' => 'CSRF token validation failed.'], 403);
    }

    $result = recordAdView($userId);

    if ($result['success']) {
        jsonResponse([
            'success' => true,
            'message' => 'Ad view recorded and coins awarded!',
            'coins_earned' => $result['coins_earned'],
            'new_view_count' => $result['new_view_count'],
            'coins_remaining' => getUserCoinsRemaining($userId)
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => $result['error']], 400);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Endpoint to get current daily ad views
    try {
        $currentViews = getDailyAdViews($userId);
        jsonResponse([
            'success' => true,
            'current_views' => $currentViews,
            'max_views' => 5,
            'coins_per_view' => 3
        ]);
    } catch (Exception $e) {
        error_log("Get ad views API error for user $userId: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to retrieve ad view status.'], 500);
    }
} else {
    jsonResponse(['success' => false, 'error' => 'Invalid request method.'], 405);
}
?>
