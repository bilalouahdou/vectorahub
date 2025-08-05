<?php
require_once 'php/config.php';
require_once 'php/utils.php';

// Start session
startSession();

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Get referral code from URL if present
$referralCode = $_GET['ref'] ?? '';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - VectraHub</title>
    <link rel="icon" href="assets/images/vectra-hub-logo2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .registration-container {
            background: rgba(33, 37, 41, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 8px;
            padding: 12px 15px;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #20c997;
            box-shadow: 0 0 0 0.2rem rgba(32, 201, 151, 0.25);
            color: #fff;
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        .btn-accent {
            background: linear-gradient(45deg, #20c997, #17a2b8);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-accent:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(32, 201, 151, 0.3);
        }
        .form-label {
            color: #fff;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-text {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border-left: 4px solid #dc3545;
        }
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #51cf66;
            border-left: 4px solid #28a745;
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        .password-strength.weak { background: #dc3545; }
        .password-strength.fair { background: #ffc107; }
        .password-strength.good { background: #17a2b8; }
        .password-strength.strong { background: #28a745; }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
        }
        .password-toggle:hover {
            color: #fff;
        }
        .password-field {
            position: relative;
        }
        .benefits-list {
            list-style: none;
            padding: 0;
            margin: 0 0 30px 0;
        }
        .benefits-list li {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }
        .benefits-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #20c997;
            font-weight: bold;
        }
        .link-accent {
            color: #20c997;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .link-accent:hover {
            color: #17a2b8;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="row w-100 justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="registration-container p-4 p-md-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h2 class="text-white mb-3">Create Your Account</h2>
                        <p class="text-white-50 mb-4">Join thousands of designers using VectraHub</p>
                        
                        <!-- Benefits List -->
                        <ul class="benefits-list">
                            <li>Unlimited SVG downloads</li>
                            <li>Batch upload support</li>
                            <li>No watermarks or ads</li>
                        </ul>
                    </div>

                    <!-- Registration Form -->
                    <form id="registrationForm" method="POST" action="php/auth/register.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <?php if (!empty($referralCode)): ?>
                        <input type="hidden" name="referral_code" value="<?php echo htmlspecialchars($referralCode); ?>">
                        <?php endif; ?>

                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                                   required>
                            <div class="form-text">Enter your first and last name.</div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   required>
                            <div class="form-text">We'll never share your email with anyone else.</div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="password-field">
                                <input type="password" class="form-control" id="password" name="password" 
                                       required minlength="8">
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                            <div class="form-text">Minimum 8 characters with uppercase, lowercase, and number.</div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="password-field">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       required minlength="8">
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Re-enter your password to confirm.</div>
                        </div>

                        <!-- Terms and Marketing -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label text-white" for="terms">
                                    I agree to the <a href="#" class="link-accent">Terms of Service</a> and <a href="#" class="link-accent">Privacy Policy</a>.
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="marketing" name="marketing">
                                <label class="form-check-label text-white" for="marketing">
                                    Send me updates about new features and design tips
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-accent w-100 mb-3" id="submitBtn">
                            <span id="submitText">Create Free Account</span>
                            <span id="submitSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                        </button>

                        <!-- Login Link -->
                        <div class="text-center">
                            <span class="text-white-50">Already have an account? </span>
                            <a href="login.php" class="link-accent">Sign in</a>
                        </div>
                    </form>

                    <!-- Error/Success Messages -->
                    <div id="messageContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.nextElementSibling;
            const icon = toggle.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let score = 0;
            const strengthBar = document.getElementById('passwordStrength');
            
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            strengthBar.className = 'password-strength';
            if (score < 3) {
                strengthBar.classList.add('weak');
            } else if (score < 4) {
                strengthBar.classList.add('fair');
            } else if (score < 5) {
                strengthBar.classList.add('good');
            } else {
                strengthBar.classList.add('strong');
            }
        }

        // Form validation and submission
        document.getElementById('registrationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');
            const messageContainer = document.getElementById('messageContainer');
            
            // Show loading state
            submitBtn.disabled = true;
            submitText.textContent = 'Creating Account...';
            submitSpinner.classList.remove('d-none');
            messageContainer.innerHTML = '';
            
            try {
                const response = await fetch('php/auth/register.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    messageContainer.innerHTML = `
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-check-circle me-2"></i>
                            ${result.message || 'Account created successfully! Redirecting to login...'}
                        </div>
                    `;
                    
                    // Redirect to login after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    let errorMessage = result.error || 'Registration failed. Please try again.';
                    
                    // Handle validation errors
                    if (result.errors && Array.isArray(result.errors)) {
                        errorMessage = 'Please fix the following errors:<br>' + result.errors.join('<br>');
                    }
                    
                    messageContainer.innerHTML = `
                        <div class="alert alert-danger mt-3">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            ${errorMessage}
                        </div>
                    `;
                }
            } catch (error) {
                messageContainer.innerHTML = `
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Network error. Please check your connection and try again.
                    </div>
                `;
            } finally {
                // Reset button state
                submitBtn.disabled = false;
                submitText.textContent = 'Create Free Account';
                submitSpinner.classList.add('d-none');
            }
        });

        // Password strength monitoring
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-5" role="contentinfo">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="assets/images/vectra-hub-logo.png" alt="VectraHub Logo" height="24" class="me-2">
                        <h3 class="h5 mb-0">VectraHub</h3>
                    </div>
                    <p class="mb-3">Free AI-powered image vectorization tool for designers, print shops, and students worldwide.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3" aria-label="Follow us on Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-light me-3" aria-label="Follow us on Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-light" aria-label="Follow us on Pinterest">
                            <i class="fab fa-pinterest"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-2">
                    <h4 class="h6 mb-3">Tools</h4>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-light">Image Vectorizer</a></li>
                        <li><a href="/batch-converter/" class="text-light">Batch Converter</a></li>
                        <li><a href="/api/" class="text-light">API Access</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h4 class="h6 mb-3">Resources</h4>
                    <ul class="list-unstyled">
                        <li><a href="/blog/" class="text-light">Blog</a></li>
                        <li><a href="/tutorials/" class="text-light">Tutorials</a></li>
                        <li><a href="/examples/" class="text-light">Examples</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h4 class="h6 mb-3">Support</h4>
                    <ul class="list-unstyled">
                        <li><a href="/help/" class="text-light">Help Center</a></li>
                        <li><a href="/contact/" class="text-light">Contact</a></li>
                        <li><a href="/feedback/" class="text-light">Feedback</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h4 class="h6 mb-3">Legal</h4>
                    <ul class="list-unstyled">
                        <li><a href="privacy.php" class="text-light">Privacy Policy</a></li>
                        <li><a href="terms.php" class="text-light">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 VectraHub. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Made with ❤️ for designers worldwide</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html> 