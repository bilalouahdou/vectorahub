<?php
require_once '../../config.php';
require_once '../../utils.php';
require_once '../../config.php';

// Ensure user is admin
redirectIfNotAdmin();

header('Content-Type: application/json');

// Get user ID from request
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

try {
    $pdo = connectDB();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user details
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.full_name, u.role, u.created_at, u.coins
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // Get current subscription
    $stmt = $pdo->prepare("
        SELECT us.*, sp.name as plan_name, sp.price, sp.features
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.plan_id = sp.id
        WHERE us.user_id = ? AND us.active = 1 AND us.end_date >= CURDATE()
        ORDER BY us.id DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get job statistics from image_jobs table
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_jobs,
            SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as completed_jobs,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_jobs,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_jobs
        FROM image_jobs
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $job_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent jobs from image_jobs table
    $stmt = $pdo->prepare("
        SELECT id, original_filename, status, created_at
        FROM image_jobs
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recent_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment history
    $stmt = $pdo->prepare("
        SELECT p.id, p.amount, p.payment_method, p.paid_at, sp.name as plan_name
        FROM payments p
        LEFT JOIN subscription_plans sp ON p.plan_id = sp.id
        WHERE p.user_id = ?
        ORDER BY p.paid_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return all data
    echo json_encode([
        'success' => true, 
        'user' => $user,
        'subscription' => $subscription,
        'job_stats' => $job_stats,
        'recent_jobs' => $recent_jobs,
        'payments' => $payments
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in view_user.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in view_user.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred']);
}
?>
