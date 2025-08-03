<?php
require_once '../utils.php';
require_once '../config.php'; // Ensure config is loaded for getDBConnection

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 400);
    }

    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || empty($password)) {
        jsonResponse(['error' => 'Email and password are required'], 400);
    }

    try {
        $pdo = getDBConnection(); // Use getDBConnection from config.php
        $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role']; // Standardize to 'role'
            
            jsonResponse(['success' => 'Login successful', 'redirect' => 'dashboard.php']);
        } else {
            jsonResponse(['error' => 'Invalid credentials'], 401);
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        jsonResponse(['error' => 'Login failed'], 500);
    }
}
?>
