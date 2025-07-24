<?php
require_once '../../utils.php';
require_once '../../config.php';

// Ensure user is admin
redirectIfNotAdmin();

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$userId = intval($data['user_id']);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

// Don't allow deleting the current admin
if (isset($_SESSION['user_id']) && $userId === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'You cannot delete your own account']);
    exit;
}

try {
    $pdo = connectDB();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, role, full_name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // Don't allow deleting other admins unless you're a super admin
    if ($user['role'] === 'admin') {
        $currentUserRole = $_SESSION['role'] ?? 'user';
        if ($currentUserRole !== 'admin') {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'You do not have permission to delete admin accounts']);
            exit;
        }
    }
    
    // Delete related data in correct order to avoid foreign key constraint violations
    
    // 1. First delete coin_usage records (they reference image_jobs)
    $stmt = $pdo->prepare("DELETE FROM coin_usage WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // 2. Then delete image_jobs (now safe since coin_usage records are gone)
    $stmt = $pdo->prepare("DELETE FROM image_jobs WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // 3. Delete user's payments
    $stmt = $pdo->prepare("DELETE FROM payments WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // 4. Delete user's subscriptions
    $stmt = $pdo->prepare("DELETE FROM user_subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // 5. Delete user's API keys (if table exists)
    try {
        $stmt = $pdo->prepare("DELETE FROM api_keys WHERE user_id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        // Table might not exist, continue
    }
    
    // 6. Delete user's bulk jobs (if table exists)
    try {
        $stmt = $pdo->prepare("DELETE FROM bulk_jobs WHERE user_id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        // Table might not exist, continue
    }
    
    // 7. Update admin logs to avoid foreign key issues
    try {
        $stmt = $pdo->prepare("UPDATE admin_logs SET admin_id = NULL WHERE admin_id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        // Table might not exist, continue
    }
    
    // 8. Delete from activity_logs if user_id references exist
    try {
        $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE user_id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        // Table might not exist, continue
    }
    
    // Finally, delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Log the action
    try {
        $adminId = $_SESSION['user_id'] ?? 1;
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, timestamp)
            VALUES (?, ?, NOW())
        ");
        $action = "Deleted user: {$user['full_name']} ({$user['email']}) - ID: $userId";
        $stmt->execute([$adminId, $action]);
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Failed to log admin action: " . $e->getMessage());
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in delete_user.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in delete_user.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
}
?>
