<?php
require_once '../config.php';
require_once '../utils.php';
require_once '../security/AuthenticationManager.php';

header('Content-Type: application/json');

startSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        jsonResponse(['success' => false, 'error' => 'CSRF token validation failed.'], 403);
    }

    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $referralCode = $_POST['referral_code'] ?? ''; // Get referral code from form

    if ($password !== $confirmPassword) {
        jsonResponse(['success' => false, 'error' => 'Passwords do not match.'], 400);
    }

    try {
        $pdo = getDBConnection();
        $authManager = new AuthenticationManager($pdo);
        $result = $authManager->registerUser($fullName, $email, $password);

        if ($result['success']) {
            $userId = $result['user_id'];

            // Handle referral tracking and rewards
            if (!empty($referralCode)) {
                $referrerId = getReferrerIdFromCode($referralCode);
                if ($referrerId) {
                    // Process referral rewards (50 coins each for referrer and new user)
                    $rewardProcessed = processReferralRewards($referrerId, $userId);
                    if ($rewardProcessed) {
                        logActivity('REFERRAL_SUCCESS', "Referral successful: User $userId registered via referral from user $referrerId", $userId);
                    }
                }
            }

            // Generate and store referral link for the new user (permanent link)
            $newReferralLink = getOrCreateReferralLink($userId);
            if ($newReferralLink) {
                logActivity('REFERRAL_LINK_CREATED', "Referral link created for new user $userId: $newReferralLink", $userId);
            }

            jsonResponse(['success' => true, 'message' => 'Registration successful. You can now log in.', 'user_id' => $userId]);
        } else {
            jsonResponse(['success' => false, 'error' => $result['error'] ?? 'Registration failed.', 'errors' => $result['errors'] ?? []], 400);
        }
    } catch (Exception $e) {
        error_log("Registration API error: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'An unexpected error occurred during registration.'], 500);
    }
} else {
    jsonResponse(['success' => false, 'error' => 'Invalid request method.'], 405);
}
?>
