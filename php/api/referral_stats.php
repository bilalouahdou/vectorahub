<?php
require_once '../config.php';
require_once '../utils.php';
startSession(); // Start session before checking auth
redirectIfNotAuth(); // Only authenticated users can view their stats

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    jsonResponse(['success' => false, 'error' => 'User not authenticated.'], 401);
}

try {
    $pdo = getDBConnection();

    // Get referral link
    $stmt = $pdo->prepare("SELECT referral_code FROM referral_links WHERE user_id = ?");
    $stmt->execute([$userId]);
    $referralCode = $stmt->fetchColumn();
    $referralLink = $referralCode ? APP_URL . "/register.php?ref=" . $referralCode : null;

    // Get total clicks
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM referral_events WHERE referrer_user_id = ? AND event_type = 'click'");
    $stmt->execute([$userId]);
    $totalClicks = $stmt->fetchColumn();

    // Get total signups
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM referral_events WHERE referrer_user_id = ? AND event_type = 'signup'");
    $stmt->execute([$userId]);
    $totalSignups = $stmt->fetchColumn();

    // Get total conversions (users who signed up and made a purchase)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM referral_rewards WHERE user_id = ? AND status = 'awarded'");
    $stmt->execute([$userId]);
    $totalConversions = $stmt->fetchColumn();

    // Get earned rewards
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM referral_rewards WHERE user_id = ? AND status = 'awarded' AND reward_type = 'bonus_coins'");
    $stmt->execute([$userId]);
    $earnedCoins = $stmt->fetchColumn();

    // Get recent referrals (signups)
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.email, re.created_at
        FROM referral_events re
        JOIN users u ON re.referred_user_id = u.id
        WHERE re.referrer_user_id = ? AND re.event_type = 'signup'
        ORDER BY re.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentSignups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse([
        'success' => true,
        'referral_link' => $referralLink,
        'stats' => [
            'total_clicks' => (int)$totalClicks,
            'total_signups' => (int)$totalSignups,
            'total_conversions' => (int)$totalConversions,
            'earned_coins' => (float)$earnedCoins,
        ],
        'recent_signups' => array_map(function($signup) {
            $signup['created_at_formatted'] = formatDate($signup['created_at']);
            return $signup;
        }, $recentSignups)
    ]);

} catch (Exception $e) {
    error_log("Referral stats API error for user $userId: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Failed to load referral statistics.'], 500);
}
?>
