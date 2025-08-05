<?php
require_once '../../utils.php';
require_once '../../config.php'; // Ensure config is loaded for getDBConnection

redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $pdo = getDBConnection(); // Use getDBConnection from config.php
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable exceptions for better error handling
    
    // Note: Users table should already exist from myv2.sql
    // No need to create it here as it uses PostgreSQL syntax
    
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
    
    // Get users with job count and current plan info
    $query = "
        SELECT 
            u.id, 
            u.full_name, 
            u.email, 
            u.role, 
            COALESCE(sp.name, 'N/A') AS current_plan_name,
            COALESCE(sp.coin_limit, 0) AS current_plan_coin_limit,
            COUNT(ij.id) AS total_jobs
        FROM users u 
        LEFT JOIN user_subscriptions us ON u.id = us.user_id AND us.active = TRUE
        LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
        LEFT JOIN image_jobs ij ON u.id = ij.user_id
        $whereClause 
        GROUP BY u.id, u.full_name, u.email, u.role, sp.name, sp.coin_limit
        ORDER BY u.id DESC 
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add a default status for display purposes and calculate coins remaining
    foreach ($users as &$user) {
        $user['status'] = 'active'; // Default status for display
        $user['joined'] = 'N/A'; // No created_at column in users table
        $user['created_at'] = 'N/A'; // No created_at column in users table
        // Calculate coins remaining for each user
        $user['coins_remaining'] = getUserCoinsRemaining($user['id']);
    }
    
    // Calculate pagination
    $totalPages = ceil($totalUsers / $limit);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_users' => (int)$totalUsers,
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
