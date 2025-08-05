<?php

// --- Input Sanitization ---
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// --- Password Hashing and Verification ---
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// --- Validation Functions ---
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    // Password must be at least 8 characters long, contain at least one uppercase letter,
    // one lowercase letter, one number, and one special character.
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{};:,<.>]).{8,}$/';
    return preg_match($pattern, $password);
}

// --- Database Connection Alias ---
// This function acts as an alias for getDBConnection defined in config.php
// to maintain compatibility with existing code that calls connectDB().
function connectDB() {
    return getDBConnection();
}

// --- Authentication and Authorization ---
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirectIfNotAuth() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        redirect('dashboard.php'); // Or an unauthorized access page
    }
}

function getUserData() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'full_name' => $_SESSION['user_name'], // Standardized to user_name
            'email' => $_SESSION['user_email'] ?? null, // Assuming email might not always be in session
            'role' => $_SESSION['role'], // Standardized to role
            'profile_image' => $_SESSION['user_profile_image'] ?? null
        ];
    }
    return null;
}

// --- Coin and Subscription Management ---
function getUserCoinsRemaining($userId) {
    try {
        $pdo = getDBConnection();
        // Get the user's active subscription plan details
        $stmt = $pdo->prepare("
            SELECT sp.coin_limit, sp.unlimited_black_images
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = ? AND us.active = TRUE AND us.end_date >= CURRENT_DATE
            ORDER BY us.start_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $coinLimit = $subscription['coin_limit'] ?? 0;
        $unlimitedBlackImages = $subscription['unlimited_black_images'] ?? FALSE;

        // Sum coins used by the user (all coins since we don't have is_black_image field)
        try {
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(coins_used), 0) AS total_used
                FROM coin_usage
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $usedCoins = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // If coin_usage table doesn't exist, assume no coins used
            if ($e->getCode() == '42P01') {
                $usedCoins = 0;
            } else {
                throw $e;
            }
        }

        return max(0, $coinLimit - $usedCoins);
    } catch (Exception $e) {
        error_log("Error getting user coins remaining for user $userId: " . $e->getMessage());
        return 0; // Default to 0 on error
    }
}

function getCurrentUserSubscription($userId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT sp.name, sp.coin_limit, us.start_date, us.end_date, sp.unlimited_black_images
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = ? AND us.active = TRUE AND us.end_date >= CURRENT_DATE
            ORDER BY us.start_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'Free', 'coin_limit' => 0, 'unlimited_black_images' => FALSE]; // Default to Free
    } catch (Exception $e) {
        error_log("Error getting current user subscription for user $userId: " . $e->getMessage());
        return ['name' => 'Free', 'coin_limit' => 0, 'unlimited_black_images' => FALSE]; // Default to Free on error
    }
}

function recordCoinUsage($userId, $coinsUsed, $isBlackImage = FALSE) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO coin_usage (user_id, coins_used, created_at)
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$userId, $coinsUsed]);
        return true;
    } catch (PDOException $e) {
        // If table doesn't exist, just log the usage
        if ($e->getCode() == '42P01') {
            error_log("Coin usage (table missing): User $userId used $coinsUsed coins");
            return true;
        }
        error_log("Error recording coin usage for user $userId: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Error recording coin usage for user $userId: " . $e->getMessage());
        return false;
    }
}

function addBonusCoins($userId, $amount, $source = 'ad_view') {
    try {
        $pdo = getDBConnection();
        // This assumes 'coin_usage' table can also store positive coin additions
        // Or you might have a separate 'user_balances' table.
        // For simplicity, let's add a negative entry to coin_usage to represent a credit.
        // A more robust system would have a dedicated 'transactions' or 'credits' table.
        $stmt = $pdo->prepare("
            INSERT INTO coin_usage (user_id, coins_used, created_at)
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$userId, -$amount]); // Negative coins_used means a credit
        return true;
    } catch (PDOException $e) {
        // If table doesn't exist, just log the bonus
        if ($e->getCode() == '42P01') {
            error_log("Bonus coins (table missing): User $userId earned $amount coins from $source");
            return true;
        }
        error_log("Error adding bonus coins for user $userId: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Error adding bonus coins for user $userId: " . $e->getMessage());
        return false;
    }
}

function processPurchase($userId, $planId, $amount, $sessionId) {
    try {
        $pdo = connectDB();
        $pdo->beginTransaction();

        // Record the payment
        $stmt = $pdo->prepare("
            INSERT INTO payments (user_id, plan_id, amount, currency, payment_gateway, transaction_id, paid_at)
            VALUES (?, ?, ?, 'USD', 'Stripe', ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$userId, $planId, $amount, $sessionId]);

        // Get plan details
        $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            throw new Exception("Plan not found.");
        }

        // Update or insert user subscription
        // For simplicity, we'll assume a user can only have one active subscription at a time.
        // If they buy a new one, the old one is replaced or extended.
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, active)
            VALUES (?, ?, CURRENT_DATE, CURRENT_DATE + INTERVAL '1 month', TRUE)
            ON CONFLICT (user_id) DO UPDATE SET
                plan_id = EXCLUDED.plan_id,
                start_date = EXCLUDED.start_date,
                end_date = EXCLUDED.end_date,
                active = EXCLUDED.active;
        ");
        $stmt->execute([$userId, $planId]);

        // Log the purchase activity
        logActivity('PURCHASE', "User $userId purchased plan '{$plan['name']}' for $amount USD.", $userId);

        // Check for referral and award referrer
        if (isset($_SESSION['referred_by_code'])) {
            $referrerCode = $_SESSION['referred_by_code'];
            $stmt = $pdo->prepare("SELECT user_id FROM referral_links WHERE referral_code = ?");
            $stmt->execute([$referrerCode]);
            $referrerId = $stmt->fetchColumn();

            if ($referrerId) {
                // Award referrer (e.g., 50 bonus coins or a cash reward)
                $rewardAmount = 50; // Example: 50 bonus coins
                addBonusCoins($referrerId, $rewardAmount, 'referral_bonus');

                // Record the referral reward
                $stmt = $pdo->prepare("
                    INSERT INTO referral_rewards (user_id, referred_user_id, reward_type, amount, status, awarded_at)
                    VALUES (?, ?, 'bonus_coins', ?, 'awarded', NOW())
                ");
                $stmt->execute([$referrerId, $userId, $rewardAmount]);

                logActivity('REFERRAL_REWARD', "User $referrerId received $rewardAmount bonus coins for referring user $userId.", $referrerId);
            }
            unset($_SESSION['referred_by_code']); // Clear referral code after use
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Purchase processing failed for user $userId, session $sessionId: " . $e->getMessage());
        return false;
    }
}

// --- Logging Functions ---
function logActivity($type, $description, $userId = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (type, description, user_id, created_at) VALUES (:type, :description, :user_id, CURRENT_TIMESTAMP)");
        $stmt->execute([
            ':type' => $type,
            ':description' => $description,
            ':user_id' => $userId
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

function logAdminAction($adminId, $action) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, timestamp) VALUES (:admin_id, :action, CURRENT_TIMESTAMP)");
        $stmt->execute([
            ':admin_id' => $adminId,
            ':action' => $action
        ]);
    } catch (Exception $e) {
        error_log("Failed to log admin action: " . $e->getMessage());
    }
}

function logSystemEvent($type, $description, $userId = null, $ipAddress = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO system_logs (type, description, user_id, ip_address, created_at) VALUES (:type, :description, :user_id, :ip_address, CURRENT_TIMESTAMP)");
        $stmt->execute([
            ':type' => $type,
            ':description' => $description,
            ':user_id' => $userId,
            ':ip_address' => $ipAddress
        ]);
    } catch (Exception $e) {
        error_log("Failed to log system event: " . $e->getMessage());
    }
}

// --- File Handling ---
function uploadFile($file, $targetDir, $allowedTypes, $maxSize) {
    if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File is too large. Max size: ' . ($maxSize / (1024 * 1024)) . ' MB'];
    }

    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)];
    }

    $fileName = generateUniqueFilename($file['name']);
    $targetPath = $targetDir . $fileName;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => $targetPath, 'filename' => $fileName];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }
}

function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

function generateUniqueFilename($originalFilename) {
    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    $uniqueName = uniqid() . '_' . time() . '.' . $extension;
    return $uniqueName;
}

// --- JSON Response Helper ---
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// --- Error Handling Helper ---
function handleError($message, $statusCode = 500) {
    logMessage('ERROR', $message);
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

// --- Formatting Helpers ---
function formatDate($dateString) {
    try {
        return (new DateTime($dateString))->format('M d, Y H:i');
    } catch (Exception $e) {
        return 'N/A';
    }
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// --- Referral Functions ---
function generateReferralCode($length = 8) {
    return bin2hex(random_bytes($length / 2));
}

function createReferralLinkForUser($userId) {
    $pdo = getDBConnection();
    $code = generateReferralCode();
    try {
        $stmt = $pdo->prepare("INSERT INTO referral_links (user_id, referral_code) VALUES (?, ?)");
        $stmt->execute([$userId, $code]);
        return APP_URL . "/register.php?ref=" . $code;
    } catch (PDOException $e) {
        // If code already exists (very rare), try again
        if ($e->getCode() == '23505') { // PostgreSQL unique violation error code
            return createReferralLinkForUser($userId); // Recursively try again
        }
        error_log("Error creating referral link: " . $e->getMessage());
        return null;
    }
}

function getReferrerIdFromCode($referralCode) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT user_id FROM referral_links WHERE referral_code = ?");
    $stmt->execute([$referralCode]);
    return $stmt->fetchColumn();
}

function recordReferralEvent($referrerUserId, $eventType, $referredUserId = null, $eventData = null) {
    $pdo = getDBConnection();
            $stmt = $pdo->prepare("
            INSERT INTO referral_events (referrer_user_id, referred_user_id, event_type, event_data, created_at)
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
    $stmt->execute([$referrerUserId, $referredUserId, $eventType, json_encode($eventData)]);
}

// --- Ad View Functions ---
function getDailyAdViews($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT view_count FROM ad_views
        WHERE user_id = ? AND last_viewed_at::date = CURRENT_DATE
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: 0;
}

function recordAdView($userId) {
    $pdo = getDBConnection();
    $maxViewsPerDay = 5;
    $bonusCoinsPerView = 3;

    $currentViews = getDailyAdViews($userId);

    if ($currentViews < $maxViewsPerDay) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                INSERT INTO ad_views (user_id, view_count, last_viewed_at)
                VALUES (?, 1, CURRENT_TIMESTAMP)
                ON CONFLICT (user_id, (last_viewed_at::date)) DO UPDATE SET
                    view_count = ad_views.view_count + 1,
                    last_viewed_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$userId]);

            addBonusCoins($userId, $bonusCoinsPerView, 'ad_view');
            logActivity('AD_VIEW', "User $userId watched an ad and earned $bonusCoinsPerView coins.", $userId);
            $pdo->commit();
            return ['success' => true, 'coins_earned' => $bonusCoinsPerView, 'new_view_count' => $currentViews + 1];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error recording ad view for user $userId: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to record ad view.'];
        }
    } else {
        return ['success' => false, 'error' => 'Daily ad view limit reached.'];
    }
}
?>
