<?php
require_once 'php/utils.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VectraHub | Access Your Vector Design Account</title>
    <meta name="description" content="Sign in to your VectraHub account to access AI-powered image vectorization tools, manage your projects, and download SVG files.">
    <link rel="canonical" href="https://vectorahub.online/login">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Login - VectraHub | Access Your Vector Design Account">
    <meta property="og:description" content="Sign in to your VectraHub account to access AI-powered image vectorization tools.">
    <meta property="og:url" content="https://vectorahub.online/login">
    <meta property="og:type" content="website">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    
    <style>
        .auth-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .auth-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .auth-header {
            background: linear-gradient(135deg, #1d1d1d 0%, #2d2d2d 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .auth-body {
            padding: 2rem;
        }
        
        .password-input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #495057;
        }
        
        .form-floating > .form-control {
            padding-right: 3rem;
        }
        
        .brand-logo {
            max-height: 40px;
            margin-bottom: 1rem;
        }
        
        @media (prefers-color-scheme: dark) {
            .auth-card {
                background-color: #1e1e1e;
                color: #e0e0e0;
            }
            
            .auth-body {
                background-color: #1e1e1e;
            }
            
            .form-control {
                background-color: #2a2a2a;
                border-color: #444;
                color: #e0e0e0;
            }
            
            .form-control:focus {
                background-color: #2a2a2a;
                color: #e0e0e0;
                border-color: var(--accent-color);
            }
            
            .form-floating > label {
                color: #adb5bd;
            }
            
            .form-floating > .form-control:focus ~ label,
            .form-floating > .form-control:not(:placeholder-shown) ~ label {
                color: var(--accent-color);
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <header class="auth-header">
                <img src="assets/images/vectra-hub-logo.png" alt="VectraHub Logo" class="brand-logo">
                <h1 class="h3 mb-2">Welcome Back</h1>
                <p class="mb-0">Sign in to your VectraHub account</p>
            </header>
            
            <main class="auth-body">
                <form id="loginForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Email Field -->
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="name@example.com" required 
                               aria-describedby="emailHelp">
                        <label for="email">Email address</label>
                        <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                    </div>
                    
                    <!-- Password Field with Toggle -->
                    <div class="form-floating mb-3 password-input-group">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required 
                               aria-describedby="passwordHelp">
                        <label for="password">Password</label>
                        <button type="button" class="password-toggle" 
                                onclick="togglePassword('password')" 
                                aria-label="Toggle password visibility"
                                title="Show/Hide password">
                            <i class="fas fa-eye" id="password-eye"></i>
                        </button>
                        <div id="passwordHelp" class="form-text">Enter your account password.</div>
                    </div>
                    
                    <!-- Remember Me -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-accent w-100 mb-3" id="loginBtn">
                        <span class="btn-text">Sign In</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                    
                    <!-- Forgot Password -->
                    <div class="text-center mb-3">
                        <a href="forgot-password.php" class="text-muted">Forgot your password?</a>
                    </div>
                    
                    <!-- Sign Up Link -->
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? 
                            <a href="register.php" class="text-accent fw-semibold">Create one free</a>
                        </p>
                    </div>
                </form>
                
                <!-- Alert Area -->
                <div id="alertArea" class="mt-3 d-none" role="alert" aria-live="assertive"></div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password visibility toggle
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(fieldId + '-eye');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
        
        // Form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('loginBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner-border');
            const alertArea = document.getElementById('alertArea');
            
            // Show loading state
            btnText.textContent = 'Signing In...';
            spinner.classList.remove('d-none');
            submitBtn.disabled = true;
            
            // Hide previous alerts
            alertArea.classList.add('d-none');
            
            try {
                const formData = new FormData(this);
                const response = await fetch('php/auth/login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', '✅ Login successful! Redirecting...');
                    setTimeout(() => {
                        window.location.href = result.redirect || 'dashboard.php';
                    }, 1000);
                } else {
                    showAlert('danger', '❌ ' + (result.error || 'Login failed. Please try again.'));
                }
            } catch (error) {
                showAlert('danger', '❌ Network error. Please check your connection and try again.');
            } finally {
                // Reset button state
                btnText.textContent = 'Sign In';
                spinner.classList.add('d-none');
                submitBtn.disabled = false;
            }
        });
        
        function showAlert(type, message) {
            const alertArea = document.getElementById('alertArea');
            alertArea.className = `alert alert-${type} mt-3`;
            alertArea.textContent = message;
            alertArea.classList.remove('d-none');
        }
        
        // Auto-focus email field
        document.getElementById('email').focus();
    </script>
</body>
</html>
