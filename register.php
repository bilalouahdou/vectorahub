<?php
require_once 'php/config.php';
require_once 'php/utils.php';

// Start session and generate CSRF token
startSession();
$csrfToken = generateCsrfToken();

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
    <title>Sign Up Free - VectraHub | Create Your Vector Design Account</title>
    <meta name="description" content="Create your free VectraHub account to access AI-powered image vectorization tools. Convert JPG and PNG to SVG instantly with no watermarks.">
    <link rel="canonical" href="https://vectorahub.online/register">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Sign Up Free - VectraHub | Create Your Vector Design Account">
    <meta property="og:description" content="Create your free VectraHub account to access AI-powered image vectorization tools.">
    <meta property="og:url" content="https://vectorahub.online/register">
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
            max-width: 450px;
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
        
        .password-strength {
            height: 4px;
            background-color: #e9ecef;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-fair { background-color: #fd7e14; width: 50%; }
        .strength-good { background-color: #ffc107; width: 75%; }
        .strength-strong { background-color: #28a745; width: 100%; }
        
        .benefits-list {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .benefits-list ul {
            margin: 0;
            padding-left: 1.2rem;
        }
        
        .benefits-list li {
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
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
            
            .benefits-list {
                background-color: #2a2a2a;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <header class="auth-header">
                <img src="assets/images/vectra-hub-logo.png" alt="VectraHub Logo" class="brand-logo">
                <h1 class="h3 mb-2">Join VectraHub</h1>
                <p class="mb-0">Create your free account and start vectorizing</p>
            </header>
            
            <main class="auth-body">
                <!-- Benefits -->
                <div class="benefits-list">
                    <h2 class="h6 mb-2">✨ What you get for free:</h2>
                    <ul class="small">
                        <li>AI-powered image vectorization</li>
                        <li>Unlimited SVG downloads</li>
                        <li>Batch upload support</li>
                        <li>No watermarks or ads</li>
                    </ul>
                </div>
                
                <form id="registerForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <!-- Full Name Field -->
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="fullName" name="full_name" 
                               placeholder="John Doe" required minlength="2"
                               aria-describedby="nameHelp">
                        <label for="fullName">Full Name</label>
                        <div id="nameHelp" class="form-text">Enter your first and last name.</div>
                    </div>
                    
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
                               placeholder="Password" required minlength="8"
                               aria-describedby="passwordHelp">
                        <label for="password">Password</label>
                        <button type="button" class="password-toggle" 
                                onclick="togglePassword('password')" 
                                aria-label="Toggle password visibility"
                                title="Show/Hide password">
                            <i class="fas fa-eye" id="password-eye"></i>
                        </button>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <div id="passwordHelp" class="form-text">Minimum 8 characters with uppercase, lowercase, and number.</div>
                    </div>
                    
                    <!-- Confirm Password Field with Toggle -->
                    <div class="form-floating mb-3 password-input-group">
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" 
                               placeholder="Confirm Password" required 
                               aria-describedby="confirmPasswordHelp">
                        <label for="confirmPassword">Confirm Password</label>
                        <button type="button" class="password-toggle" 
                                onclick="togglePassword('confirmPassword')" 
                                aria-label="Toggle confirm password visibility"
                                title="Show/Hide password">
                            <i class="fas fa-eye" id="confirmPassword-eye"></i>
                        </button>
                        <div id="confirmPasswordHelp" class="form-text">Re-enter your password to confirm.</div>
                    </div>
                    
                    <!-- Terms Agreement -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="agreeTerms" name="agree_terms" required>
                        <label class="form-check-label" for="agreeTerms">
                            I agree to the <a href="/terms" target="_blank" class="text-accent">Terms of Service</a> 
                            and <a href="/privacy" target="_blank" class="text-accent">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <!-- Newsletter Subscription -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" checked>
                        <label class="form-check-label" for="newsletter">
                            Send me updates about new features and design tips
                        </label>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-accent w-100 mb-3" id="registerBtn">
                        <span class="btn-text">Create Free Account</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                    
                    <!-- Sign In Link -->
                    <div class="text-center">
                        <p class="mb-0">Already have an account? 
                            <a href="login.php" class="text-accent fw-semibold">Sign in</a>
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
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            const strengthBar = document.getElementById('passwordStrengthBar');
            
            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Character variety checks
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-fair');
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-good');
            } else {
                strengthBar.classList.add('strength-strong');
            }
            
            return strength;
        }
        
        // Password input event listener
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
        
        // Form validation
        function validateForm() {
            const form = document.getElementById('registerForm');
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const email = document.getElementById('email').value;
            const fullName = document.getElementById('fullName').value;
            const agreeTerms = document.getElementById('agreeTerms').checked;
            
            let isValid = true;
            let errors = [];
            
            // Name validation
            if (fullName.length < 2) {
                errors.push('Full name must be at least 2 characters long.');
                isValid = false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errors.push('Please enter a valid email address.');
                isValid = false;
            }
            
            // Password validation
            if (password.length < 8) {
                errors.push('Password must be at least 8 characters long.');
                isValid = false;
            }
            
            // Check for uppercase, lowercase, and number
            if (!/[A-Z]/.test(password)) {
                errors.push('Password must contain at least one uppercase letter.');
                isValid = false;
            }
            
            if (!/[a-z]/.test(password)) {
                errors.push('Password must contain at least one lowercase letter.');
                isValid = false;
            }
            
            if (!/\d/.test(password)) {
                errors.push('Password must contain at least one number.');
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                errors.push('Passwords do not match.');
                isValid = false;
            }
            
            // Terms agreement
            if (!agreeTerms) {
                errors.push('You must agree to the Terms of Service and Privacy Policy.');
                isValid = false;
            }
            
            if (!isValid) {
                showAlert('danger', '❌ Please fix the following errors:\n• ' + errors.join('\n• '));
            }
            
            return isValid;
        }
        
        // Form submission
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }
            
            const submitBtn = document.getElementById('registerBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner-border');
            const alertArea = document.getElementById('alertArea');
            
            // Show loading state
            btnText.textContent = 'Creating Account...';
            spinner.classList.remove('d-none');
            submitBtn.disabled = true;
            
            // Hide previous alerts
            alertArea.classList.add('d-none');
            
            try {
                const formData = new FormData(this);
                const response = await fetch('php/auth/register.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                // Debug logging
                console.log('Registration response:', result);
                console.log('Response status:', response.status);
                
                if (result.success) {
                    showAlert('success', '✅ Account created successfully! You can now sign in.');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    if (result.errors && Array.isArray(result.errors)) {
                        showAlert('danger', '❌ Please fix the following errors:\n• ' + result.errors.join('\n• '));
                    } else {
                        showAlert('danger', '❌ ' + (result.message || result.error || 'Registration failed. Please try again.'));
                    }
                }
            } catch (error) {
                showAlert('danger', '❌ Network error. Please check your connection and try again.');
            } finally {
                // Reset button state
                btnText.textContent = 'Create Free Account';
                spinner.classList.add('d-none');
                submitBtn.disabled = false;
            }
        });
        
        function showAlert(type, message) {
            const alertArea = document.getElementById('alertArea');
            alertArea.className = `alert alert-${type} mt-3`;
            alertArea.style.whiteSpace = 'pre-line';
            alertArea.textContent = message;
            alertArea.classList.remove('d-none');
        }
        
        // Auto-focus full name field
        document.getElementById('fullName').focus();
    </script>
</body>
</html>
