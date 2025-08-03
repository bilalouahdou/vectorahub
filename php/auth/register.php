<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper headers for JSON response
header('Content-Type: application/json');
require_once '../config.php';
require_once '../utils.php';

// Functions from utils.php are now available globally or via explicit calls
// No need to redefine jsonResponse, logActivity, logSystemEvent, validateEmail, validatePassword
// setCORSHeaders is also in utils.php if needed, but typically handled by web server config for production.

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// setCORSHeaders(); // Uncomment if CORS is an issue and not handled by web server

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
        exit();
    }

    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        jsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
        exit();
    }

    if (!validateEmail($email)) { // Using validateEmail from utils.php
        jsonResponse(['success' => false, 'message' => 'Invalid email format.'], 400);
        exit();
    }

    if (!validatePassword($password)) { // Using validatePassword from utils.php
        jsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters long and include an uppercase letter, a lowercase letter, a number, and a special character.'], 400);
        exit();
    }

    if ($password !== $confirmPassword) {
        jsonResponse(['success' => false, 'message' => 'Passwords do not match.'], 400);
        exit();
    }

    try {
        $pdo = getDBConnection(); // Use getDBConnection from config.php
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable exceptions

        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'User with this email already exists.'], 409);
            exit();
        }

        // Hash password
        $passwordHash = hashPassword($password); // Using hashPassword from utils.php

        $pdo->beginTransaction();

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (:full_name, :email, :password_hash, 'user') RETURNING id");
        $stmt->execute([
            ':full_name' => $fullName,
            ':email' => $email,
            ':password_hash' => $passwordHash
        ]);
        $userId = $stmt->fetchColumn();

        if ($userId) {
            // Assign a default free subscription plan
            // First, find the ID of the 'Free' plan
            $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE name = 'Free' LIMIT 1");
            $stmt->execute();
            $freePlan = $stmt->fetch(PDO::FETCH_ASSOC);

            $defaultFreePlanId = $freePlan['id'] ?? null;

            if ($defaultFreePlanId === null) {
                // Try to find any plan with price 0 as fallback
                $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE price = 0 LIMIT 1");
                $stmt->execute();
                $freePlan = $stmt->fetch(PDO::FETCH_ASSOC);
                $defaultFreePlanId = $freePlan['id'] ?? null;
                
                if ($defaultFreePlanId === null) {
                    // If still no free plan found, create a basic free plan
                    $stmt = $pdo->prepare("INSERT INTO subscription_plans (name, price, coin_limit, features) VALUES ('Free', 0.00, 10, '10 vectorizations per month, Standard processing, Basic support') RETURNING id");
                    $stmt->execute();
                    $defaultFreePlanId = $stmt->fetchColumn();
                    logMessage('INFO', 'Created default Free plan during registration for user: ' . $email);
                }
            }

            $stmt = $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, active, start_date, end_date, is_free_from_coupon) VALUES (:user_id, :plan_id, TRUE, CURRENT_DATE, CURRENT_DATE + INTERVAL '1 year', FALSE)");
            $stmt->execute([
                ':user_id' => $userId,
                ':plan_id' => $defaultFreePlanId
            ]);

            // Log activity
            logActivity('registration', 'New user registered: ' . $email, $userId);
            logSystemEvent('user_registration', 'User ' . $email . ' registered successfully.', $userId, $_SERVER['REMOTE_ADDR']);

            $pdo->commit();
            jsonResponse(['success' => true, 'message' => 'Registration successful! You can now log in.'], 201);
        } else {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'Registration failed. Please try again.'], 500);
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $errorMessage = $e->getMessage();
        logMessage('ERROR', 'Registration error: ' . $errorMessage);
        
        // Provide more specific error messages
        if (strpos($errorMessage, 'duplicate key') !== false || strpos($errorMessage, 'already exists') !== false) {
            jsonResponse(['success' => false, 'message' => 'An account with this email already exists.'], 409);
        } elseif (strpos($errorMessage, 'could not find driver') !== false) {
            jsonResponse(['success' => false, 'message' => 'Database connection error. Please contact support.'], 500);
        } elseif (strpos($errorMessage, 'connection') !== false) {
            jsonResponse(['success' => false, 'message' => 'Database connection failed. Please try again later.'], 500);
        } else {
            jsonResponse(['success' => false, 'message' => 'Registration failed: ' . $errorMessage], 500);
        }
    }
} else {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}
?>
