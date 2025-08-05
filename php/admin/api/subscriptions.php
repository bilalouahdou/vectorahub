<?php
require_once '../../config.php';
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $pdo = connectDB();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Note: Tables should already exist from myv2.sql
    // No need to create them here as they use PostgreSQL syntax
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? '';

    // Build query conditions
    $conditions = [];
    $params = [];
    
    if (!empty($status)) {
        if ($status === 'active') {
            $conditions[] = "us.active = TRUE AND us.end_date >= CURRENT_DATE";
        } elseif ($status === 'expired') {
            $conditions[] = "us.active = FALSE OR us.end_date < CURRENT_DATE";
        }
    }
    
    $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Get subscription plans
    $plansQuery = "SELECT * FROM subscription_plans ORDER BY price ASC";
    $stmt = $pdo->query($plansQuery);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add status and billing info to plans
    foreach ($plans as &$plan) {
        $plan['status'] = 'active';
        $plan['billing'] = 'monthly';
        $plan['actions'] = ['edit', 'delete'];
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM user_subscriptions us $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalSubscriptions = $stmt->fetchColumn();
    
    // Get subscriptions with user info
    $query = "
        SELECT us.*, u.full_name as user_name, u.email as user_email, p.name as plan_name
        FROM user_subscriptions us 
        LEFT JOIN users u ON us.user_id = u.id 
        LEFT JOIN subscription_plans p ON us.plan_id = p.id
        $whereClause 
        ORDER BY us.start_date DESC 
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format subscription data
    foreach ($subscriptions as &$subscription) {
        $subscription['status'] = ($subscription['active'] && $subscription['end_date'] >= date('Y-m-d')) ? 'active' : 'expired';
        $subscription['actions'] = ['view', 'edit', 'cancel'];
    }
    
    // Get subscription stats
    $statsQuery = "
        SELECT 
            COUNT(CASE WHEN us.active = TRUE AND us.end_date >= CURRENT_DATE THEN 1 END) as active_count,
            COALESCE(SUM(CASE WHEN us.active = TRUE AND us.end_date >= CURRENT_DATE THEN p.price END), 0) as monthly_revenue,
            COALESCE(AVG(CASE WHEN us.active = TRUE AND us.end_date >= CURRENT_DATE THEN p.price END), 0) as avg_value
        FROM user_subscriptions us
        LEFT JOIN subscription_plans p ON us.plan_id = p.id
    ";
    $stmt = $pdo->query($statsQuery);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate pagination
    $totalPages = ceil($totalSubscriptions / $limit);
    
    echo json_encode([
        'success' => true,
        'plans' => $plans,
        'subscriptions' => $subscriptions,
        'stats' => $stats,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_subscriptions' => $totalSubscriptions,
            'per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Admin subscriptions API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load subscriptions: ' . $e->getMessage()]);
}
?>
