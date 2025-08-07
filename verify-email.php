<?php
require_once 'php/config.php';
require_once 'php/utils.php';

startSession();

$token = $_GET['token'] ?? '';
$message = '';
$success = false;

if ($token) {
    try {
        $pdo = getDBConnection();
        
        // Find user with this verification token
        $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE email_verification_token = ? AND email_verified = FALSE");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Verify the email
            $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, email_verification_token = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            $success = true;
            $message = "Email verified successfully! You can now log in to your account.";
            
            logActivity('EMAIL_VERIFIED', "Email verified for user: " . $user['email'], $user['id']);
        } else {
            $message = "Invalid or expired verification token. Please register again or contact support.";
        }
    } catch (Exception $e) {
        error_log("Email verification error: " . $e->getMessage());
        $message = "An error occurred during verification. Please try again later.";
    }
} else {
    $message = "No verification token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - VectraHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .verification-container {
            max-width: 500px;
            margin: 0 auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #dee2e6;
            text-align: center;
            padding: 2rem 2rem 1rem;
        }
        .verification-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        .success-icon {
            background-color: #d4edda;
            color: #155724;
        }
        .error-icon {
            background-color: #f8d7da;
            color: #721c24;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-container">
            <div class="card">
                <div class="card-header">
                    <div class="verification-icon <?php echo $success ? 'success-icon' : 'error-icon'; ?>">
                        <?php echo $success ? '✓' : '✗'; ?>
                    </div>
                    <h3><?php echo $success ? 'Email Verified!' : 'Verification Failed'; ?></h3>
                </div>
                <div class="card-body p-4">
                    <div class="text-center">
                        <p class="mb-4"><?php echo htmlspecialchars($message); ?></p>
                        
                        <?php if ($success): ?>
                            <a href="/login" class="btn btn-primary me-2">Login Now</a>
                            <a href="/" class="btn btn-secondary">Go Home</a>
                        <?php else: ?>
                            <a href="/register" class="btn btn-primary me-2">Register Again</a>
                            <a href="/support" class="btn btn-secondary">Contact Support</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
