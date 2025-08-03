<?php
// Suppress HTML error output for API endpoints
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'utils.php';
require_once 'config.php'; // Ensure config is loaded for getDBConnection

redirectIfNotAuth();

header('Content-Type: application/json');

$page = max(1, intval($_GET['page'] ?? 1));
$limit = intval($_GET['limit'] ?? 10);
$offset = ($page - 1) * $limit;

try {
    $pdo = getDBConnection(); // Use getDBConnection from config.php
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable exceptions
    $userId = $_SESSION['user_id'] ?? 0;
    
    if (!$userId) {
        jsonResponse(['error' => 'User not authenticated'], 401);
    }
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM image_jobs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $total = $stmt->fetchColumn() ?: 0;
    
    // Get jobs with coins_used by joining with coin_usage
    $stmt = $pdo->prepare("
        SELECT 
            ij.id,
            ij.status,
            ij.created_at,
            ij.original_image_path,
            ij.output_svg_path,
            ij.original_filename,
            COALESCE(cu.coins_used, 0) AS coins_used -- Use COALESCE to default to 0 if no coin_usage record
        FROM image_jobs ij
        LEFT JOIN coin_usage cu ON ij.id = cu.image_job_id AND ij.user_id = cu.user_id
        WHERE ij.user_id = ?
        ORDER BY ij.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$userId, $limit, $offset]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Format the jobs data safely
    foreach ($jobs as &$job) {
        $job['id'] = (int)($job['id'] ?? 0);
        $job['coins_used'] = (int)($job['coins_used'] ?? 0); // Ensure it's an integer
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
