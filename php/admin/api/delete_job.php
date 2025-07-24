<?php
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
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Check if job exists - using image_jobs table
        $stmt = $pdo->prepare("SELECT id FROM image_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$job) {
            throw new Exception('Job not found');
        }
        
        // Delete related coin usage records first
        $stmt = $pdo->prepare("DELETE FROM coin_usage WHERE image_job_id = ?");
        $stmt->execute([$jobId]);
        
        // Delete the job
        $stmt = $pdo->prepare("DELETE FROM image_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to delete job');
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Job deleted successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Delete job API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
