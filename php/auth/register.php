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

            // Handle referral tracking
            if (!empty($referralCode)) {
                $referrerId = getReferrerIdFromCode($referralCode);
                if ($referrerId) {
                    recordReferralEvent($referrerId, 'signup', $userId, ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                    // Store referral code in session to award later upon first purchase
                    $_SESSION['referred_by_code'] = $referralCode;
                }
            }

            // Generate and store referral link for the new user
            $newReferralLink = createReferralLinkForUser($userId);
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
