<?php
require_once '../../config.php';
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $jobId = $_GET['id'] ?? null;
    
    if (!$jobId) {
        throw new Exception('Job ID is required');
    }
    
    $pdo = connectDB();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get job details with user info
    $stmt = $pdo->prepare("
        SELECT ij.*, u.full_name as user_name, u.email as user_email, cu.coins_used 
        FROM image_jobs ij 
        LEFT JOIN users u ON ij.user_id = u.id 
        LEFT JOIN coin_usage cu ON ij.id = cu.image_job_id 
        WHERE ij.id = ?
    ");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        throw new Exception('Job not found');
    }
    
    // Ensure coins_used has a default value
    $job['coins_used'] = $job['coins_used'] ?? 1;
    
    // Add error message if status is failed (simulate for demo)
    if ($job['status'] === 'failed') {
        $job['error_message'] = 'Image processing failed: Invalid file format or corrupted image';
    }
    
    echo json_encode([
        'success' => true,
        'job' => $job
    ]);
    
} catch (Exception $e) {
    error_log("View job API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
