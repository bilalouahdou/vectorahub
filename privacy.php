<?php require_once 'php/config.php'; ?>
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
            <h1 class="display-4 fw-bold">Privacy Policy</h1>
            <p class="lead text-muted">Last updated: <?php echo date('F j, Y'); ?></p>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h2>1. Information We Collect</h2>
                        <p>We collect information you provide directly to us, such as when you create an account, upload images, or contact us for support.</p>
                        
                        <h3>Account Information</h3>
                        <ul>
                            <li>Name and email address</li>
                            <li>Password (encrypted)</li>
                            <li>Account preferences and settings</li>
                        </ul>

                        <h3>Usage Information</h3>
                        <ul>
                            <li>Images you upload for vectorization</li>
                            <li>API usage and requests</li>
                            <li>Subscription and billing information</li>
                        </ul>

                        <h3>Technical Information</h3>
                        <ul>
                            <li>IP address and browser information</li>
                            <li>Device and operating system details</li>
                            <li>Usage patterns and analytics</li>
                        </ul>

                        <h2>2. How We Use Your Information</h2>
                        <p>We use the information we collect to:</p>
                        <ul>
                            <li>Provide and improve our vectorization services</li>
                            <li>Process your image uploads and generate vector outputs</li>
                            <li>Manage your account and subscription</li>
                            <li>Send important service updates and notifications</li>
                            <li>Provide customer support</li>
                            <li>Analyze usage patterns to improve our service</li>
                        </ul>

                        <h2>3. Information Sharing</h2>
                        <p>We do not sell, trade, or otherwise transfer your personal information to third parties, except:</p>
                        <ul>
                            <li>With your explicit consent</li>
                            <li>To comply with legal obligations</li>
                            <li>To protect our rights and safety</li>
                            <li>With service providers who assist in our operations (under strict confidentiality agreements)</li>
                        </ul>

                        <h2>4. Data Security</h2>
                        <p>We implement appropriate security measures to protect your personal information:</p>
                        <ul>
                            <li>Encryption of sensitive data</li>
                            <li>Secure data transmission (HTTPS)</li>
                            <li>Regular security assessments</li>
                            <li>Limited access to personal information</li>
                        </ul>

                        <h2>5. Data Retention</h2>
                        <p>We retain your information for as long as necessary to provide our services and comply with legal obligations. You may request deletion of your account and associated data at any time.</p>

                        <h2>6. Your Rights</h2>
                        <p>You have the right to:</p>
                        <ul>
                            <li>Access your personal information</li>
                            <li>Correct inaccurate information</li>
                            <li>Request deletion of your data</li>
                            <li>Opt out of marketing communications</li>
                            <li>Export your data</li>
                        </ul>

                        <h2>7. Cookies and Tracking</h2>
                        <p>We use cookies and similar technologies to enhance your experience, analyze usage, and provide personalized content. You can control cookie settings through your browser preferences.</p>

                        <h2>8. Third-Party Services</h2>
                        <p>Our service may integrate with third-party services (such as payment processors). These services have their own privacy policies, and we encourage you to review them.</p>

                        <h2>9. Children's Privacy</h2>
                        <p>Our service is not intended for children under 13. We do not knowingly collect personal information from children under 13.</p>

                        <h2>10. International Data Transfers</h2>
                        <p>Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place for such transfers.</p>

                        <h2>11. Changes to This Policy</h2>
                        <p>We may update this Privacy Policy from time to time. We will notify you of any material changes via email or through our service.</p>

                        <h2>12. Contact Us</h2>
                        <p>If you have questions about this Privacy Policy or our data practices, please contact us at privacy@vectrahub.online.</p>

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