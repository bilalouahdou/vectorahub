<?php

// --- Input Sanitization ---
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// --- Password Hashing and Verification ---
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// --- Validation Functions ---
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    // Password must be at least 8 characters long, contain at least one uppercase letter,
    // one lowercase letter, one number, and one special character.
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{};:,<.>]).{8,}$/';
    return preg_match($pattern, $password);
}

// --- Database Connection Alias ---
// This function acts as an alias for getDBConnection defined in config.php
// to maintain compatibility with existing code that calls connectDB().
function connectDB() {
    return getDBConnection();
}

// --- Authentication and Authorization ---
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirectIfNotAuth() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        redirect('dashboard.php'); // Or an unauthorized access page
    }
}

function getUserData() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'full_name' => $_SESSION['user_name'], // Standardized to user_name
            'email' => $_SESSION['user_email'] ?? null, // Assuming email might not always be in session
            'role' => $_SESSION['role'], // Standardized to role
            'profile_image' => $_SESSION['user_profile_image'] ?? null
        ];
    }
    return null;
}

// --- Coin and Subscription Management ---
function getUserCoinsRemaining($userId) {
    try {
        $pdo = getDBConnection();
        // Get the coin_limit from the user's active subscription plan
        $stmt = $pdo->prepare("
            SELECT sp.coin_limit
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = ? AND us.active = TRUE
            ORDER BY us.start_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        $coinLimit = $subscription['coin_limit'] ?? 0;

        // Sum coins used by the user
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(coins_used), 0) AS total_used
            FROM coin_usage
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $usedCoins = $stmt->fetchColumn(); // fetchColumn returns false if no rows, COALESCE handles null

        return max(0, $coinLimit - $usedCoins);
    } catch (Exception $e) {
        error_log("Error getting user coins remaining for user $userId: " . $e->getMessage());
        return 0; // Default to 0 on error
    }
}

function getCurrentUserSubscription($userId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT sp.name, sp.coin_limit, us.start_date, us.end_date
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = ? AND us.active = TRUE
            ORDER BY us.start_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'Free', 'coin_limit' => 0]; // Default to Free
    } catch (Exception $e) {
        error_log("Error getting current user subscription for user $userId: " . $e->getMessage());
        return ['name' => 'Free', 'coin_limit' => 0]; // Default to Free on error
    }
}


// --- Logging Functions ---
function logActivity($type, $description, $userId = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (type, description, user_id, created_at) VALUES (:type, :description, :user_id, NOW())");
        $stmt->execute([
            ':type' => $type,
            ':description' => $description,
            ':user_id' => $userId
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

function logAdminAction($adminId, $action) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, timestamp) VALUES (:admin_id, :action, NOW())");
        $stmt->execute([
            ':admin_id' => $adminId,
            ':action' => $action
        ]);
    } catch (Exception $e) {
        error_log("Failed to log admin action: " . $e->getMessage());
    }
}

function logSystemEvent($type, $description, $userId = null, $ipAddress = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO system_logs (type, description, user_id, ip_address, created_at) VALUES (:type, :description, :user_id, :ip_address, NOW())");
        $stmt->execute([
            ':type' => $type,
            ':description' => $description,
            ':user_id' => $userId,
            ':ip_address' => $ipAddress
        ]);
    } catch (Exception $e) {
        error_log("Failed to log system event: " . $e->getMessage());
    }
}

// --- File Handling ---
function uploadFile($file, $targetDir, $allowedTypes, $maxSize) {
    if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File is too large. Max size: ' . ($maxSize / (1024 * 1024)) . ' MB'];
    }

    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)];
    }

    $fileName = generateUniqueFilename($file['name']);
    $targetPath = $targetDir . $fileName;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => $targetPath, 'filename' => $fileName];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }
}

function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

function generateUniqueFilename($originalFilename) {
    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    $uniqueName = uniqid() . '_' . time() . '.' . $extension;
    return $uniqueName;
}

// --- JSON Response Helper ---
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// --- Error Handling Helper ---
function handleError($message, $statusCode = 500) {
    logMessage('ERROR', $message);
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

?>
