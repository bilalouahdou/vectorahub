<?php
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = connectDB();

try {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $coins = (int)($_POST['coins'] ?? 10);
    
    // Validate input
    if (empty($fullName) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid role']);
        exit;
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Email already exists']);
        exit;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, password, role, coins, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$fullName, $email, $hashedPassword, $role, $coins]);
    
    echo json_encode([
        'success' => true,
        'message' => 'User added successfully',
        'user_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log("Add user error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to add user']);
}
?>
