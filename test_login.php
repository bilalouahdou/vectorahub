<?php
// Test login functionality
require_once 'php/config.php';

echo "🔐 Login Test\n";
echo "=============\n\n";

// Start session
startSession();
$csrfToken = generateCsrfToken();

echo "1. CSRF Token generated: " . substr($csrfToken, 0, 20) . "...\n";
echo "2. Session started: " . (session_status() == PHP_SESSION_ACTIVE ? 'Yes' : 'No') . "\n\n";

// Test CSRF token verification
echo "3. Testing CSRF token verification...\n";
if (verifyCsrfToken($csrfToken)) {
    echo "   ✅ CSRF token is valid\n";
} else {
    echo "   ❌ CSRF token is invalid\n";
}

// Test with wrong token
if (verifyCsrfToken('wrong_token')) {
    echo "   ❌ Wrong token was accepted (this is bad)\n";
} else {
    echo "   ✅ Wrong token was rejected (this is good)\n";
}

echo "\n4. Testing login with test user...\n";

// Simulate login data
$_POST = [
    'email' => 'test@example.com',
    'password' => 'TestPass123!',
    'csrf_token' => $csrfToken
];

// Test login logic
try {
    $pdo = getDBConnection();
    
    // Check if test user exists
    $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "   ✅ Test user found\n";
        echo "      ID: {$user['id']}\n";
        echo "      Name: {$user['full_name']}\n";
        echo "      Role: {$user['role']}\n";
        
        // Test password verification
        if (password_verify($_POST['password'], $user['password_hash'])) {
            echo "   ✅ Password is correct\n";
            
            // Simulate successful login
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            echo "   ✅ Session created successfully\n";
            echo "      Session User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
            echo "      Session User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "\n";
            echo "      Session Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
            
        } else {
            echo "   ❌ Password is incorrect\n";
        }
    } else {
        echo "   ❌ Test user not found\n";
        echo "   💡 Create a test user first\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Login error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Login test completed!\n";
?> 