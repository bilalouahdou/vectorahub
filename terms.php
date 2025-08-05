<?php require_once 'php/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - VectraHub</title>
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
                <a class="nav-link" href="pricing.php">Pricing</a>
                <a class="nav-link" href="api_documentation.php">API</a>
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold">Terms of Service</h1>
            <p class="lead text-muted">Last updated: <?php echo date('F j, Y'); ?></p>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h2>1. Acceptance of Terms</h2>
                        <p>By accessing and using VectraHub ("the Service"), you accept and agree to be bound by the terms and provision of this agreement.</p>

                        <h2>2. Description of Service</h2>
                        <p>VectraHub provides AI-powered image vectorization services, converting raster images (JPG, PNG) to scalable vector formats (SVG). The service includes both web-based tools and API access.</p>

                        <h2>3. User Accounts</h2>
                        <p>To access certain features of the Service, you must create an account. You are responsible for maintaining the confidentiality of your account information and for all activities that occur under your account.</p>

                        <h2>4. Acceptable Use</h2>
                        <p>You agree to use the Service only for lawful purposes and in accordance with these Terms. You agree not to:</p>
                        <ul>
                            <li>Upload content that infringes on intellectual property rights</li>
                            <li>Use the Service for any illegal or unauthorized purpose</li>
                            <li>Attempt to gain unauthorized access to the Service</li>
                            <li>Interfere with or disrupt the Service</li>
                            <li>Upload malicious files or content</li>
                        </ul>

                        <h2>5. Subscription Plans and Billing</h2>
                        <p>VectraHub offers various subscription plans with different features and usage limits. Billing occurs on a recurring basis according to your selected plan. You may cancel your subscription at any time.</p>

                        <h2>6. Intellectual Property</h2>
                        <p>You retain ownership of content you upload. By using the Service, you grant VectraHub a limited license to process your content for the purpose of providing the vectorization service.</p>

                        <h2>7. Privacy</h2>
                        <p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of the Service.</p>

                        <h2>8. Disclaimers</h2>
                        <p>The Service is provided "as is" without warranties of any kind. VectraHub does not guarantee that the Service will be uninterrupted or error-free.</p>

                        <h2>9. Limitation of Liability</h2>
                        <p>VectraHub shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of the Service.</p>

                        <h2>10. Changes to Terms</h2>
                        <p>We reserve the right to modify these terms at any time. We will notify users of any material changes via email or through the Service.</p>

                        <h2>11. Contact Information</h2>
                        <p>If you have any questions about these Terms of Service, please contact us at support@vectrahub.online.</p>

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
                        <li><a href="pricing.php" class="text-light">Pricing</a></li>
                        <li><a href="api_documentation.php" class="text-light">API</a></li>
                        <li><a href="contact.php" class="text-light">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h6>Legal</h6>
                    <ul class="list-unstyled">
                        <li><a href="privacy.php" class="text-light">Privacy Policy</a></li>
                        <li><a href="terms.php" class="text-light">Terms of Service</a></li>
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