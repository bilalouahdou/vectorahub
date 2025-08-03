<?php
require_once '../../config.php';
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $pdo = connectDB();
    
    // Note: system_logs table should already exist from myv2.sql
    // No need to create it here as it uses PostgreSQL syntax
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $type = $_GET['type'] ?? '';
    $date = $_GET['date'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build query conditions
    $conditions = [];
    $params = [];
    
    if (!empty($type)) {
        $conditions[] = "sl.type = ?";
        $params[] = $type;
    }
    
    if (!empty($date)) {
        $conditions[] = "DATE(sl.created_at) = ?";
        $params[] = $date;
    }
    
    if (!empty($search)) {
        $conditions[] = "sl.description LIKE ?";
        $params[] = "%$search%";
    }
    
    $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM system_logs sl $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalLogs = $stmt->fetchColumn();
    
    // Get logs with user info
    $query = "
        SELECT sl.*, u.full_name as user_name 
        FROM system_logs sl 
        LEFT JOIN users u ON sl.user_id = u.id 
        $whereClause 
        ORDER BY sl.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination
    $totalPages = ceil($totalLogs / $limit);
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_logs' => $totalLogs,
            'per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    error_log("System logs API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load system logs: ' . $e->getMessage()
    ]);
}
?>
