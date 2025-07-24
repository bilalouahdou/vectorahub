<?php
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $pdo = connectDB();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();
    
    // Get total jobs from image_jobs table
    $stmt = $pdo->query("SELECT COUNT(*) FROM image_jobs");
    $totalJobs = $stmt->fetchColumn();
    
    // Get active subscriptions
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM user_subscriptions 
        WHERE active = 1 AND end_date >= CURDATE()
    ");
    $activeSubscriptions = $stmt->fetchColumn();
    
    // Get monthly revenue (current month)
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(amount), 0) FROM payments 
        WHERE MONTH(paid_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(paid_at) = YEAR(CURRENT_DATE())
    ");
    $monthlyRevenue = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => $totalUsers,
            'total_jobs' => $totalJobs,
            'active_subscriptions' => $activeSubscriptions,
            'monthly_revenue' => number_format($monthlyRevenue, 2)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Admin stats API error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to load statistics: ' . $e->getMessage()
    ]);
}
?>
