<?php
require_once '../utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 400);
    }

    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    $errors = [];
    if (empty($fullName)) $errors[] = 'Full name is required';
    if (!$email) $errors[] = 'Valid email is required';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';

    if (!empty($errors)) {
        jsonResponse(['errors' => $errors], 400);
    }

    try {
        $pdo = connectDB();
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'Email already registered'], 400);
        }

        // Create user
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$fullName, $email, $passwordHash]);
        
        $userId = $pdo->lastInsertId();

        // Assign free plan
        $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE name = 'Free' LIMIT 1");
        $stmt->execute();
        $freePlan = $stmt->fetch();
        
        if ($freePlan) {
            $stmt = $pdo->prepare("
                INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date) 
                VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))
            ");
            $stmt->execute([$userId, $freePlan['id']]);
        }

        jsonResponse(['success' => 'Registration successful']);
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        jsonResponse(['error' => 'Registration failed'], 500);
    }
}
?>
