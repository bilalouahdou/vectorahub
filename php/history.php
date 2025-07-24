<?php
// Suppress HTML error output for API endpoints
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'utils.php';
redirectIfNotAuth();

header('Content-Type: application/json');

$page = max(1, intval($_GET['page'] ?? 1));
$limit = intval($_GET['limit'] ?? 10);
$offset = ($page - 1) * $limit;

try {
    $pdo = connectDB();
    $userId = $_SESSION['user_id'] ?? 0;
    
    if (!$userId) {
        jsonResponse(['error' => 'User not authenticated'], 401);
    }
    
    // Get total count with better error handling
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM image_jobs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $total = $stmt->fetchColumn() ?: 0;
    
    // Get jobs with simpler query to avoid JOIN issues
    $stmt = $pdo->prepare("
        SELECT 
            id,
            status,
            created_at,
            original_image_path,
            output_svg_path,
            original_filename,
            1 as coins_used
        FROM image_jobs 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$userId, $limit, $offset]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Format the jobs data safely
    foreach ($jobs as &$job) {
        $job['id'] = (int)($job['id'] ?? 0);
        $job['coins_used'] = 1; // Default value
        $job['original_filename'] = $job['original_filename'] ?: 'Unknown';
    }
    
    jsonResponse([
        'jobs' => $jobs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => max(1, ceil($total / $limit)),
            'total_items' => (int)$total,
            'per_page' => $limit
        ],
        'status' => 'success'
    ]);
} catch (Exception $e) {
    error_log("History API error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to load history', 'debug' => $e->getMessage()], 500);
}
?>
