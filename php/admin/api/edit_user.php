<?php
require_once '../../utils.php';
require_once '../../config.php';

// Ensure user is admin
redirectIfNotAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user data for editing
    $userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
        exit;
    }

    try {
        $pdo = connectDB();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            SELECT 
                id,
                full_name,
                email,
                role,
                coins,
                created_at
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }

        echo json_encode(['success' => true, 'user' => $user]);

    } catch (PDOException $e) {
        error_log("Database error in edit_user.php (GET): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update user data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'User ID is required']);
        exit;
    }

    $user_id = (int)$input['user_id'];
    $full_name = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $role = $input['role'] ?? 'user';
    $coins = (int)($input['coins'] ?? 0);

    if (empty($full_name) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Name and email are required']);
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

    if ($coins < 0) {
        echo json_encode(['success' => false, 'error' => 'Coins cannot be negative']);
        exit;
    }

    try {
        $pdo = connectDB();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction();

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }

        // Check if email already exists for another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'Email already exists']);
            exit;
        }

        // Update user
        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = ?, email = ?, role = ?, coins = ?
            WHERE id = ?
        ");
        $stmt->execute([$full_name, $email, $role, $coins, $user_id]);

        // Log the action if admin_logs table exists
        try {
            $adminId = $_SESSION['user_id'] ?? 1;
            $stmt = $pdo->prepare("
                INSERT INTO admin_logs (admin_id, action, timestamp)
                VALUES (?, ?, NOW())
            ");
            $action = "Updated user ID: $user_id - Name: $full_name, Email: $email, Role: $role, Coins: $coins";
            $stmt->execute([$adminId, $action]);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log("Failed to log admin action: " . $e->getMessage());
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Database error in edit_user.php (POST): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in edit_user.php (POST): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'An error occurred']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
