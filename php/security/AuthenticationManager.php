<?php
/**
 * Secure Authentication Manager
 * Implements secure login, registration, and session management
 */

require_once 'SecurityManager.php';

class AuthenticationManager {
    private $pdo;
    private $security;
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 900; // 15 minutes
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->security = SecurityManager::getInstance();
    }
    
    /**
     * Secure user registration
     */
    public function registerUser($fullName, $email, $password) {
        try {
            // Validate and sanitize inputs
            $fullName = $this->security->validateAndSanitizeInput($fullName, 'string', ['max_length' => 100]);
            $email = $this->security->validateAndSanitizeInput($email, 'email');
            
            if (!$fullName || !$email) {
                return ['success' => false, 'error' => 'Invalid input data'];
            }
            
            // Validate password strength
            $passwordValidation = $this->security->validatePasswordStrength($password);
            if (!$passwordValidation['valid']) {
                return ['success' => false, 'errors' => $passwordValidation['errors']];
            }
            
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Email already registered'];
            }
            
            // Hash password securely
            $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536, // 64 MB
                'time_cost' => 4,       // 4 iterations
                'threads' => 3          // 3 threads
            ]);
            
            // Generate email verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (full_name, email, password_hash, email_verification_token, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$fullName, $email, $passwordHash, $verificationToken]);
            
            $userId = $this->pdo->lastInsertId();
            
            // Assign free plan
            $this->assignFreePlan($userId);
            
            // Log registration
            $this->security->logSecurityEvent('USER_REGISTERED', [
                'user_id' => $userId,
                'email' => $email
            ]);
            
            // Send verification email (implement this)
            // $this->sendVerificationEmail($email, $verificationToken);
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch (Exception $e) {
            $this->security->logSecurityEvent('REGISTRATION_ERROR', [
                'error' => $e->getMessage(),
                'email' => $email ?? 'unknown'
            ], 'ERROR');
            
            return ['success' => false, 'error' => 'Registration failed'];
        }
    }
    
    /**
     * Secure user login with brute force protection
     */
    public function loginUser($email, $password, $rememberMe = false) {
        try {
            $email = $this->security->validateAndSanitizeInput($email, 'email');
            if (!$email) {
                return ['success' => false, 'error' => 'Invalid email format'];
            }
            
            // Check rate limiting
            $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (!$this->security->checkRateLimit($clientIP . '_login', 'login')) {
                $this->security->logSecurityEvent('LOGIN_RATE_LIMITED', [
                    'email' => $email,
                    'ip' => $clientIP
                ], 'WARNING');
                
                return ['success' => false, 'error' => 'Too many login attempts. Please try again later.'];
            }
            
            // Check account lockout
            if ($this->isAccountLocked($email)) {
                return ['success' => false, 'error' => 'Account temporarily locked due to multiple failed attempts'];
            }
            
            // Get user data
            $stmt = $this->pdo->prepare("
                SELECT id, full_name, email, password_hash, role, email_verified, failed_login_attempts, last_failed_login 
                FROM users WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->recordFailedLogin($email);
                return ['success' => false, 'error' => 'Invalid credentials'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->recordFailedLogin($email, $user['id']);
                
                $this->security->logSecurityEvent('LOGIN_FAILED', [
                    'user_id' => $user['id'],
                    'email' => $email,
                    'reason' => 'invalid_password'
                ], 'WARNING');
                
                return ['success' => false, 'error' => 'Invalid credentials'];
            }
            
            // Check if email is verified (optional)
            // if (!$user['email_verified']) {
            //     return ['success' => false, 'error' => 'Please verify your email before logging in'];
            // }
            
            // Successful login - reset failed attempts
            $this->resetFailedLoginAttempts($user['id']);
            
            // Initialize secure session
            $this->security->initializeSecureSession();
            
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Handle "Remember Me" functionality
            if ($rememberMe) {
                $this->setRememberMeToken($user['id']);
            }
            
            // Update last login
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            $this->security->logSecurityEvent('LOGIN_SUCCESS', [
                'user_id' => $user['id'],
                'email' => $email
            ]);
            
            return ['success' => true, 'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]];
            
        } catch (Exception $e) {
            $this->security->logSecurityEvent('LOGIN_ERROR', [
                'error' => $e->getMessage(),
                'email' => $email ?? 'unknown'
            ], 'ERROR');
            
            return ['success' => false, 'error' => 'Login failed'];
        }
    }
    
    /**
     * Check if account is locked due to failed attempts
     */
    private function isAccountLocked($email) {
        $stmt = $this->pdo->prepare("
            SELECT failed_login_attempts, last_failed_login 
            FROM users 
            WHERE email = ? AND failed_login_attempts >= ?
        ");
        $stmt->execute([$email, $this->maxLoginAttempts]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        $lastFailedTime = strtotime($result['last_failed_login']);
        $lockoutExpiry = $lastFailedTime + $this->lockoutDuration;
        
        return time() < $lockoutExpiry;
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedLogin($email, $userId = null) {
        if ($userId) {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET failed_login_attempts = failed_login_attempts + 1, 
                    last_failed_login = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
        } else {
            // Create or update failed login record for non-existent users
            $stmt = $this->pdo->prepare("
                INSERT INTO failed_logins (email, attempts, last_attempt) 
                VALUES (?, 1, NOW()) 
                ON DUPLICATE KEY UPDATE 
                attempts = attempts + 1, last_attempt = NOW()
            ");
            $stmt->execute([$email]);
        }
    }
    
    /**
     * Reset failed login attempts after successful login
     */
    private function resetFailedLoginAttempts($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET failed_login_attempts = 0, last_failed_login = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    /**
     * Set remember me token
     */
    private function setRememberMeToken($userId) {
        $token = bin2hex(random_bytes(32));
        $hashedToken = password_hash($token, PASSWORD_DEFAULT);
        $expiry = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days
        
        // Store in database
        $stmt = $this->pdo->prepare("
            INSERT INTO remember_tokens (user_id, token_hash, expires_at) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            token_hash = VALUES(token_hash), expires_at = VALUES(expires_at)
        ");
        $stmt->execute([$userId, $hashedToken, $expiry]);
        
        // Set cookie
        setcookie('remember_token', $token, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    /**
     * Validate remember me token
     */
    public function validateRememberToken($token) {
        if (!$token) return false;
        
        $stmt = $this->pdo->prepare("
            SELECT rt.user_id, rt.token_hash, u.full_name, u.email, u.role 
            FROM remember_tokens rt 
            JOIN users u ON rt.user_id = u.id 
            WHERE rt.expires_at > NOW()
        ");
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($token, $row['token_hash'])) {
                // Valid token found - log user in
                $this->security->initializeSecureSession();
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_name'] = $row['full_name'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['login_time'] = time();
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Secure logout
     */
    public function logoutUser() {
        $userId = $_SESSION['user_id'] ?? null;
        
        // Remove remember me token
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $this->pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        // Log logout event
        if ($userId) {
            $this->security->logSecurityEvent('USER_LOGOUT', ['user_id' => $userId]);
        }
        
        // Destroy session
        session_destroy();
        
        // Regenerate session ID for security
        session_start();
        session_regenerate_id(true);
    }
    
    /**
     * Change password with security checks
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $stmt = $this->pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                $this->security->logSecurityEvent('PASSWORD_CHANGE_FAILED', [
                    'user_id' => $userId,
                    'reason' => 'invalid_current_password'
                ], 'WARNING');
                
                return ['success' => false, 'error' => 'Current password is incorrect'];
            }
            
            // Validate new password strength
            $passwordValidation = $this->security->validatePasswordStrength($newPassword);
            if (!$passwordValidation['valid']) {
                return ['success' => false, 'errors' => $passwordValidation['errors']];
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3
            ]);
            
            // Update password
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET password_hash = ?, password_changed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$newPasswordHash, $userId]);
            
            // Invalidate all remember me tokens
            $stmt = $this->pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $this->security->logSecurityEvent('PASSWORD_CHANGED', ['user_id' => $userId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->security->logSecurityEvent('PASSWORD_CHANGE_ERROR', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ], 'ERROR');
            
            return ['success' => false, 'error' => 'Password change failed'];
        }
    }
    
    /**
     * Assign free plan to new user
     */
    private function assignFreePlan($userId) {
        $stmt = $this->pdo->prepare("SELECT id FROM subscription_plans WHERE name = 'Free' LIMIT 1");
        $stmt->execute();
        $freePlan = $stmt->fetch();
        
        if ($freePlan) {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, active) 
                VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1)
            ");
            $stmt->execute([$userId, $freePlan['id']]);
        }
    }
}
?>
