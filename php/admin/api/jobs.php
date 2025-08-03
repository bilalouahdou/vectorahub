<?php
require_once '../../config.php';
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $pdo = connectDB();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? '';
    $date = $_GET['date'] ?? '';

    // Build query conditions
    $conditions = [];
    $params = [];
    
    if (!empty($status)) {
        $conditions[] = "ij.status = ?";
        $params[] = $status;
    }
    
    if (!empty($date)) {
        $conditions[] = "DATE(ij.created_at) = ?";
        $params[] = $date;
    }
    
    $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Get total count - using image_jobs table
    $countQuery = "SELECT COUNT(*) FROM image_jobs ij $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalJobs = $stmt->fetchColumn();
    
    // Get jobs with user information - using image_jobs table
    $query = "
        SELECT 
            ij.id,
            ij.original_filename,
            ij.status,
            ij.created_at,
            ij.user_id,
            u.full_name as user_name,
            u.email as user_email,
            cu.coins_used
        FROM image_jobs ij 
        LEFT JOIN users u ON ij.user_id = u.id
        LEFT JOIN coin_usage cu ON ij.id = cu.image_job_id
        $whereClause 
        ORDER BY ij.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Note: Jobs will be empty if no users exist or no jobs have been created
    // This is normal for a new system
    
    // Ensure coins_used has a default value
    foreach ($jobs as &$job) {
        $job['coins_used'] = $job['coins_used'] ?? 1;
    }
    
    // Calculate pagination
    $totalPages = ceil($totalJobs / $limit);
    
    echo json_encode([
        'success' => true,
        'jobs' => $jobs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_jobs' => $totalJobs,
            'per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Admin jobs API error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to load jobs: ' . $e->getMessage()
    ]);
}
?>
