<?php
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $pdo = connectDB();
    
    // Create logs table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('info', 'warning', 'error', 'debug') DEFAULT 'info',
        description TEXT NOT NULL,
        user_id INT NULL,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    // Insert some sample logs if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_logs");
    if ($stmt->fetchColumn() == 0) {
        $sampleLogs = [
            ['info', 'System started successfully', null, '127.0.0.1'],
            ['warning', 'High memory usage detected', null, '127.0.0.1'],
            ['error', 'Failed to process image: invalid format', 1, '192.168.1.100'],
            ['info', 'User login successful', 1, '192.168.1.100'],
            ['debug', 'API endpoint called: /api/jobs', 1, '192.168.1.100'],
        ];
        
        $stmt = $pdo->prepare("INSERT INTO system_logs (type, description, user_id, ip_address) VALUES (?, ?, ?, ?)");
        foreach ($sampleLogs as $log) {
            $stmt->execute($log);
        }
    }
    
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
