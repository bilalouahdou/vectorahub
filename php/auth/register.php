<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper headers for JSON response
header('Content-Type: application/json');
require_once '../config.php';
require_once '../utils.php';

function sendJsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

function logError($message, $context = []) {
    error_log(date('Y-m-d H:i:s') . ' [REGISTER] ' . $message . ' ' . json_encode($context));
}

function logActivity($action, $message, $user_id = null) {
    error_log(date('Y-m-d H:i:s') . ' [' . strtoupper($action) . '] ' . $message . ($user_id ? ' User ID: ' . $user_id : ''));
}

function logSystemEvent($action, $message, $user_id = null, $ip_address = null) {
    error_log(date('Y-m-d H:i:s') . ' [' . strtoupper($action) . '] ' . $message . ($user_id ? ' User ID: ' . $user_id : '') . ($ip_address ? ' IP Address: ' . $ip_address : ''));
}

function setCORSHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

function handleError($message, $code = 400) {
    logError($message);
    sendJsonResponse(['error' => $message], $code);
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
        exit();
    }

    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        sendJsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
        exit();
    }

    if (!isValidEmail($email)) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid email format.'], 400);
        exit();
    }

    if (!validatePassword($password)) {
        sendJsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters long and include an uppercase letter, a lowercase letter, a number, and a special character.'], 400);
        exit();
    }

    if ($password !== $confirmPassword) {
        sendJsonResponse(['success' => false, 'message' => 'Passwords do not match.'], 400);
        exit();
    }

    try {
        $pdo = getDBConnection();

        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            sendJsonResponse(['success' => false, 'message' => 'User with this email already exists.'], 409);
            exit();
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (:full_name, :email, :password_hash, 'user') RETURNING id");
        $stmt->execute([
            ':full_name' => $fullName,
            ':email' => $email,
            ':password_hash' => $passwordHash
        ]);
        $userId = $stmt->fetchColumn();

        if ($userId) {
            // Assign a default free subscription plan (e.g., plan_id 1)
            // You might want to fetch the actual default free plan ID from the database
            $defaultFreePlanId = 1; // Assuming plan ID 1 is the free plan

            $stmt = $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, active, start_date, end_date, is_free_from_coupon) VALUES (:user_id, :plan_id, TRUE, CURRENT_DATE, CURRENT_DATE + INTERVAL '1 year', TRUE)");
            $stmt->execute([
                ':user_id' => $userId,
                ':plan_id' => $defaultFreePlanId
            ]);

            // Log activity
            logActivity('registration', 'New user registered: ' . $email, $userId);
            logSystemEvent('user_registration', 'User ' . $email . ' registered successfully.', $userId, $_SERVER['REMOTE_ADDR']);

            sendJsonResponse(['success' => true, 'message' => 'Registration successful! You can now log in.'], 201);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Registration failed. Please try again.'], 500);
        }

    } catch (Exception $e) {
        logError('Registration error: ' . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
    }
} else {
    sendJsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}
?>
