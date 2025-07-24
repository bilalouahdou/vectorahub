<?php
// Don't start session here - it's handled in config.php
require_once __DIR__ . '/config.php';

function connectDB() {
    return getDBConnection();
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirectIfNotAuth() {
    if (!isLoggedIn()) {
        if (!headers_sent()) {
            header('Location: /test/login.php');
            exit;
        } else {
            echo '<script>window.location.href="/test/login.php";</script>';
            exit;
        }
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        if (!headers_sent()) {
            header('Location: /dashboard.php');
            exit;
        } else {
            echo '<script>window.location.href="/dashboard.php";</script>';
            exit;
        }
    }
}

function generateApiKey() {
    return 'va_' . bin2hex(random_bytes(32));
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getUserCoinsRemaining($userId) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT sp.coin_limit - COALESCE(SUM(cu.coins_used), 0) as coins_remaining
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.plan_id = sp.id
        LEFT JOIN coin_usage cu ON cu.user_id = us.user_id 
            AND cu.created_at >= us.start_date 
            AND cu.created_at <= us.end_date
        WHERE us.user_id = ? AND us.active = 1
        AND us.end_date >= CURDATE()
        GROUP BY us.user_id, sp.coin_limit
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result ? max(0, $result['coins_remaining']) : 0;
}

function getCurrentUserSubscription($userId) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT us.*, sp.name, sp.price, sp.coin_limit, sp.features
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.plan_id = sp.id
        WHERE us.user_id = ? AND us.active = 1 AND us.end_date >= CURDATE()
        ORDER BY us.id DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function jsonResponse($data, $status = 200) {
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json');
    }
    echo json_encode($data);
    exit;
}

function processPurchase($userId, $planId, $amount, $transactionId, $billingType = 'monthly') {
    try {
        $pdo = connectDB();
        $pdo->beginTransaction();
        
        // Get plan details
        $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            throw new Exception("Plan not found");
        }
        
        // Deactivate current subscriptions
        $stmt = $pdo->prepare("UPDATE user_subscriptions SET active = 0 WHERE user_id = ? AND active = 1");
        $stmt->execute([$userId]);
        
        // Calculate expiration date based on billing type
        if ($billingType === 'yearly') {
            $intervalSQL = 'DATE_ADD(CURDATE(), INTERVAL 1 YEAR)';
        } else {
            $intervalSQL = 'DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
        }
        
        // Create new subscription
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, active) 
            VALUES (?, ?, CURDATE(), $intervalSQL, 1)
        ");
        $stmt->execute([$userId, $planId]);
        
        // Record payment (if you have a payments table)
        $stmt = $pdo->prepare("
            INSERT INTO payments (user_id, amount, plan_id, payment_method, transaction_id, billing_type) 
            VALUES (?, ?, ?, 'stripe', ?, ?)
        ");
        $stmt->execute([$userId, $amount, $planId, $transactionId, $billingType]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Purchase processing error: " . $e->getMessage());
        return false;
    }
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

/**
 * Get the correct path to an SVG file
 * This function checks multiple possible locations for the SVG file
 */
function getSvgFilePath($filename) {
    // Check if filename is valid
    if (empty($filename) || pathinfo($filename, PATHINFO_EXTENSION) !== 'svg') {
        return false;
    }
    
    // Try different possible paths
    $possiblePaths = [
        __DIR__ . '/../outputs/' . $filename,
        __DIR__ . '/outputs/' . $filename,
        dirname(__DIR__) . '/outputs/' . $filename,
        dirname(dirname(__DIR__)) . '/outputs/' . $filename
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return false;
}

function sanitizeFilename($filename) {
    // Remove extension and clean the filename
    $name = pathinfo($filename, PATHINFO_FILENAME);
    // Replace spaces and special characters with underscores
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    // Remove multiple consecutive underscores
    $name = preg_replace('/_+/', '_', $name);
    // Trim underscores from start and end
    $name = trim($name, '_');
    
    return empty($name) ? 'vectorized_image' : $name;
}

function generateUniqueFilename($directory, $basename, $extension) {
    $filename = $basename . '.' . $extension;
    $counter = 1;
    
    while (file_exists($directory . '/' . $filename)) {
        $filename = $basename . '_' . $counter . '.' . $extension;
        $counter++;
    }
    
    return $filename;
}

function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }
    error_log($logMessage);
}
?>
