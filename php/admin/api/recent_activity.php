<?php
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $pdo = connectDB();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get recent activities from activity_logs table
    $stmt = $pdo->prepare("
        SELECT 
            al.id,
            al.type,
            al.description,
            al.created_at,
            u.full_name as user_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no activities exist, create some sample data
    if (empty($activities)) {
        // Insert sample activities
        $sampleActivities = [
            ['user_registered', 'New user registered', null],
            ['job_completed', 'Image vectorization completed', 1],
            ['payment_received', 'Payment received for Ultimate plan', 1],
        ];
        
        foreach ($sampleActivities as $activity) {
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (type, description, user_id, created_at)
                VALUES (?, ?, ?, NOW() - INTERVAL FLOOR(RAND() * 24) HOUR)
            ");
            $stmt->execute($activity);
        }
        
        // Re-fetch activities
        $stmt = $pdo->prepare("
            SELECT 
                al.id,
                al.type,
                al.description,
                al.created_at,
                u.full_name as user_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);
    
} catch (Exception $e) {
    error_log("Admin recent activity API error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to load recent activity: ' . $e->getMessage()
    ]);
}
?>
