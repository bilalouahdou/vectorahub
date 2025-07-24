<?php
// Suppress HTML error output for API endpoints
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'utils.php';

// Handle both GET and POST requests
$jobId = 0;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $jobId = intval($_GET['job_id'] ?? 0);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $jobId = intval($input['job_id'] ?? $_POST['job_id'] ?? 0);
}

if (!$jobId) {
    jsonResponse(['error' => 'Missing or invalid job_id parameter'], 400);
}

// Check if user is authenticated
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT status, output_svg_path 
        FROM image_jobs 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$jobId, $_SESSION['user_id']]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        jsonResponse(['error' => 'Job not found'], 404);
    }
    
    $response = ['status' => $job['status']];
    
    if ($job['status'] === 'done' && $job['output_svg_path']) {
        $response['svg_url'] = '/outputs/' . $job['output_svg_path'];
    }
    
    jsonResponse($response);
} catch (Exception $e) {
    error_log("Status check error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to check status'], 500);
}
?>
