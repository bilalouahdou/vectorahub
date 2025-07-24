<?php
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $pdo = connectDB();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Create subscription_plans table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscription_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            coin_limit INT NOT NULL,
            features TEXT,
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create user_subscriptions table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            plan_id INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE
        )
    ");
    
    // Insert sample subscription plans if none exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM subscription_plans");
    $planCount = $stmt->fetchColumn();
    
    if ($planCount == 0) {
        $samplePlans = [
            ['Basic Plan', 9.99, 100, 'Basic vectorization, Standard support'],
            ['Pro Plan', 19.99, 500, 'Advanced vectorization, Priority support, Bulk processing'],
            ['Enterprise Plan', 49.99, 2000, 'Premium vectorization, 24/7 support, API access, Custom integrations']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO subscription_plans (name, price, coin_limit, features) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($samplePlans as $plan) {
            $stmt->execute($plan);
        }
    }
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? '';

    // Build query conditions
    $conditions = [];
    $params = [];
    
    if (!empty($status)) {
        if ($status === 'active') {
            $conditions[] = "us.active = 1 AND us.end_date >= CURDATE()";
        } elseif ($status === 'expired') {
            $conditions[] = "us.active = 0 OR us.end_date < CURDATE()";
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
            COUNT(CASE WHEN us.active = 1 AND us.end_date >= CURDATE() THEN 1 END) as active_count,
            COALESCE(SUM(CASE WHEN us.active = 1 AND us.end_date >= CURDATE() THEN p.price END), 0) as monthly_revenue,
            COALESCE(AVG(CASE WHEN us.active = 1 AND us.end_date >= CURDATE() THEN p.price END), 0) as avg_value
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
