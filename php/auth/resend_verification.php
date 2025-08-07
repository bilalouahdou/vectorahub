<?php
require_once '../utils.php';
require_once '../config.php';
require_once '../services/EmailService.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 400);
    }

    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        jsonResponse(['error' => 'Valid email is required'], 400);
    }

    try {
        $pdo = getDBConnection();
        
        // Check if user exists and email is not verified
        $stmt = $pdo->prepare("SELECT id, full_name, email, email_verified, email_verification_token FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            jsonResponse(['error' => 'User not found'], 404);
        }

        if ($user['email_verified']) {
            jsonResponse(['error' => 'Email is already verified'], 400);
        }

        // Generate new verification token if needed
        if (empty($user['email_verification_token'])) {
            $verificationToken = bin2hex(random_bytes(32));
            $updateStmt = $pdo->prepare("UPDATE users SET email_verification_token = ? WHERE id = ?");
            $updateStmt->execute([$verificationToken, $user['id']]);
        } else {
            $verificationToken = $user['email_verification_token'];
        }

        // Send verification email
        $emailService = new EmailService();
        $emailService->sendWelcomeEmail($user['email'], $user['full_name'], $verificationToken);
        
        // Log the activity
        logActivity('VERIFICATION_EMAIL_RESENT', "Verification email resent to {$user['email']}", $user['id']);
        
        jsonResponse([
            'success' => 'Verification email sent', 
            'message' => 'Please check your email and click the verification link.'
        ]);

    } catch (Exception $e) {
        error_log("Resend verification error: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to send verification email'], 500);
    }
}

// If not POST, return error
jsonResponse(['error' => 'Method not allowed'], 405);
?>
