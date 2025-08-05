<?php
require_once '../../config.php';
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $pdo = connectDB();
    $period = $_GET['period'] ?? 'month';
    
    // Calculate date range based on period (PostgreSQL syntax)
    switch ($period) {
        case 'day':
            $dateCondition = "DATE(created_at) = CURRENT_DATE";
            break;
        case 'week':
            $dateCondition = "created_at >= CURRENT_TIMESTAMP - INTERVAL '1 week'";
            break;
        case 'year':
            $dateCondition = "created_at >= CURRENT_TIMESTAMP - INTERVAL '1 year'";
            break;
        default: // month
            $dateCondition = "created_at >= CURRENT_TIMESTAMP - INTERVAL '1 month'";
    }
    
    // User signups - Note: users table doesn't have created_at column
    // For now, return mock data until the column is added
    $userSignups = [
        ['date' => date('Y-m-d'), 'count' => 5],
        ['date' => date('Y-m-d', strtotime('-1 day')), 'count' => 3],
        ['date' => date('Y-m-d', strtotime('-2 days')), 'count' => 7],
    ];
    
    // Job completions
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM image_jobs 
        WHERE status = 'done' AND $dateCondition 
        GROUP BY DATE(created_at) 
        ORDER BY date
    ");
    $jobCompletions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mock revenue data
    $revenue = [
        ['date' => date('Y-m-d'), 'total' => 150.00],
        ['date' => date('Y-m-d', strtotime('-1 day')), 'total' => 200.00],
        ['date' => date('Y-m-d', strtotime('-2 days')), 'total' => 175.00],
    ];
    
    // Job types
    $jobTypes = [
        ['type' => 'Vector Conversion', 'count' => 45],
        ['type' => 'Image Enhancement', 'count' => 32],
        ['type' => 'Logo Design', 'count' => 28],
        ['type' => 'Icon Creation', 'count' => 15],
    ];
    
    echo json_encode([
        'success' => true,
        'analytics' => [
            'user_signups' => $userSignups,
            'job_completions' => $jobCompletions,
            'revenue' => $revenue,
            'job_types' => $jobTypes
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Analytics API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load analytics: ' . $e->getMessage()
    ]);
}
?>
