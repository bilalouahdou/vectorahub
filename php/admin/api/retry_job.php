<?php
require_once '../../config.php';
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $jobId = $input['job_id'] ?? null;
    
    if (!$jobId) {
        throw new Exception('Job ID is required');
    }
    
    $pdo = connectDB();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Check if job exists and is in failed status - using image_jobs table
    $stmt = $pdo->prepare("SELECT id, status FROM image_jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        throw new Exception('Job not found');
    }
    
    if ($job['status'] !== 'failed') {
        throw new Exception('Only failed jobs can be retried');
    }
    
    // Update job status to queued for retry
    $stmt = $pdo->prepare("
        UPDATE image_jobs 
        SET status = 'queued' 
        WHERE id = ?
    ");
    
    $stmt->execute([$jobId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Failed to update job status');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Job has been queued for retry'
    ]);
    
} catch (Exception $e) {
    error_log("Retry job API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
