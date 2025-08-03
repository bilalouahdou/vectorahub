<?php
require_once '../../utils.php';
require_once '../../config.php'; // Ensure config is loaded for getDBConnection

redirectIfNotAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDBConnection(); // Use getDBConnection from config.php
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable exceptions for better error handling

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
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
    
    $pdo->beginTransaction();

    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, password_hash, role) 
        VALUES (?, ?, ?, ?) RETURNING id
    ");
    
    $stmt->execute([$fullName, $email, $hashedPassword, $role]);
    $userId = $stmt->fetchColumn();

    if ($userId) {
        // Assign a default free subscription plan
        // First, find the ID of the 'Free' plan
        $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE name = 'Free' LIMIT 1");
        $stmt->execute();
        $freePlan = $stmt->fetch(PDO::FETCH_ASSOC);

        $defaultFreePlanId = $freePlan['id'] ?? null;

        if ($defaultFreePlanId === null) {
            // Fallback if 'Free' plan not found, or handle error
            // For now, we'll assume plan_id 1 is the default free plan if 'Free' name isn't found
            $defaultFreePlanId = 1; 
            error_log("Warning: 'Free' subscription plan not found by name. Using default ID: 1.");
        }

        // Insert into user_subscriptions
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions (user_id, plan_id, active, start_date, end_date, is_free_from_coupon) 
            VALUES (?, ?, TRUE, CURRENT_DATE, CURRENT_DATE + INTERVAL '1 year', TRUE)
        ");
        $stmt->execute([$userId, $defaultFreePlanId]);

        // Log the action
        logAdminAction($_SESSION['user_id'] ?? null, "Added new user: {$fullName} ({$email}) with role {$role}");

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'User added successfully and assigned free plan',
            'user_id' => $userId
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Failed to create user record']);
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Add user error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to add user: ' . $e->getMessage()]);
}
?>
