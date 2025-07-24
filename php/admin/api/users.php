<?php
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $pdo = connectDB();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Create users table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin') DEFAULT 'user',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            coins INT DEFAULT 0
        )
    ");
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $filter = $_GET['filter'] ?? '';

    // Build query conditions
    $conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $conditions[] = "(full_name LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($filter)) {
        if ($filter === 'admin' || $filter === 'user') {
            $conditions[] = "role = ?";
            $params[] = $filter;
        }
    }
    
    $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM users $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalUsers = $stmt->fetchColumn();
    
    // Get users with job count from image_jobs table (not jobs table)
    $query = "
        SELECT 
            u.id, 
            u.full_name, 
            u.email, 
            u.role, 
            u.created_at, 
            u.coins,
            COUNT(ij.id) as total_jobs
        FROM users u 
        LEFT JOIN image_jobs ij ON u.id = ij.user_id
        $whereClause 
        GROUP BY u.id, u.full_name, u.email, u.role, u.created_at, u.coins
        ORDER BY u.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no users exist, create a sample admin user
    if (empty($users) && empty($search) && empty($filter)) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, email, password_hash, role) 
            VALUES (?, ?, ?, 'admin')
        ");
        $stmt->execute(['Admin User', 'admin@vectorizeai.com', $hashedPassword]);
        
        // Re-fetch users
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalUsers = 1;
    }
    
    // Add a default status for display purposes
    foreach ($users as &$user) {
        $user['status'] = 'active'; // Default status since column doesn't exist
        $user['joined'] = $user['created_at']; // Alias for display
    }
    
    // Calculate pagination
    $totalPages = ceil($totalUsers / $limit);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_users' => $totalUsers,
            'per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Admin users API error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to load users: ' . $e->getMessage()
    ]);
}
?>
