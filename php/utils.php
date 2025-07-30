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

// --- Authentication and Authorization ---
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getUserData() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'full_name' => $_SESSION['user_full_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'profile_image' => $_SESSION['user_profile_image'] ?? null
        ];
    }
    return null;
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
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// --- Error Handling Helper ---
function handleError($message, $statusCode = 500) {
    logMessage('ERROR', $message);
    sendJsonResponse(['success' => false, 'message' => $message], $statusCode);
}

?>
