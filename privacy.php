<?php 
session_start();
require_once 'php/config.php';
require_once 'php/utils.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - VectraHub</title>
    <link rel="icon" href="assets/images/vectra-hub-logo2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <span class="text-accent">Vectra</span>Hub
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/">Home</a>
                <a class="nav-link" href="pricing">Pricing</a>
                <a class="nav-link" href="api-documentation">API</a>
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="dashboard">Dashboard</a>
                <?php else: ?>
                    <a class="nav-link" href="login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold">Privacy Policy</h1>
            <p class="lead text-muted">Last updated: <?php echo date('F j, Y'); ?></p>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h2>1. Information We Collect</h2>
                        <p>We collect information you provide when you create an account, upload images, or contact us for support.</p>
                        
                        <ul>
                            <li><strong>Account Information:</strong> Name, email address, encrypted password</li>
                            <li><strong>Usage Data:</strong> Images uploaded for vectorization, API usage, service preferences</li>
                            <li><strong>Technical Data:</strong> IP address, browser information, device details for service functionality</li>
                        </ul>

                        <h2>2. How We Use Your Information</h2>
                        <p>We use collected information to:</p>
                        <ul>
                            <li>Provide image vectorization services</li>
                            <li>Process uploads and generate vector outputs</li>
                            <li>Manage your account and subscription</li>
                            <li>Provide customer support</li>
                            <li>Send service-related communications</li>
                        </ul>

                        <h2>3. Information Sharing</h2>
                        <p>We do not sell your personal information. We may share information only:</p>
                        <ul>
                            <li>With your consent</li>
                            <li>To comply with legal requirements</li>
                            <li>With trusted service providers under confidentiality agreements</li>
                        </ul>

                        <h2>4. Payment Processing</h2>
                        <p>All payment processing and checkout is handled by Paddle, our Merchant of Record. Paddle processes your payment information and handles tax compliance. We do not store your payment details. Paddle's privacy policy governs the collection and use of payment information.</p>

                        <h2>5. Data Security</h2>
                        <p>We implement security measures including data encryption, secure transmission (HTTPS), and limited access controls to protect your information.</p>

                        <h2>6. Data Retention</h2>
                        <p>We retain your information as needed to provide services and comply with legal obligations. You may request account deletion at any time.</p>

                        <h2>7. Your Rights</h2>
                        <p>You may access, correct, or request deletion of your personal information. Contact us to exercise these rights.</p>

                        <h2>8. Cookies</h2>
                        <p>We use cookies to enhance functionality and analyze usage. You can control cookie settings in your browser.</p>

                        <h2>9. Updates to This Policy</h2>
                        <p>We may update this policy occasionally. We will notify you of material changes via email or service notifications.</p>

                        <h2>10. Contact Information</h2>
                        <p>For privacy questions, contact: <strong>Bilalouahdou@gmail.com</strong></p>

                        <div class="text-center mt-5">
                            <a href="/" class="btn btn-accent">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="text-accent">VectraHub</h5>
                    <p class="text-muted">Transform images to crisp SVG vectors instantly with our AI-powered tool.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-light">Home</a></li>
                        <li><a href="pricing" class="text-light">Pricing</a></li>
                        <li><a href="api_documentation" class="text-light">API</a></li>
                        <li><a href="contact" class="text-light">Contact</a></li>
                        <li><a href="referral" class="text-light">Referral Program</a></li>
                        <li><a href="ad-rewards" class="text-light">Earn Coins</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h6>Legal</h6>
                    <ul class="list-unstyled">
                        <li><a href="privacy" class="text-light">Privacy Policy</a></li>
                        <li><a href="terms" class="text-light">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> VectraHub. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 