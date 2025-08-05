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
    <title>Refund & Cancellation Policy - VectraHub</title>
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
            <h1 class="display-4 fw-bold">Refund & Cancellation Policy</h1>
            <p class="lead text-muted">Last updated: <?php echo date('F j, Y'); ?></p>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h2>1. Refund Eligibility</h2>
                        
                        <h3>First Purchase Refunds</h3>
                        <p>Your first purchase is refundable within <strong>7 days</strong> of purchase if you have used <strong>2 vectorizations or fewer</strong>. This allows you to try our service with minimal commitment.</p>

                        <h3>Subscription Renewals</h3>
                        <p>Subscription renewals are refundable only in cases of:</p>
                        <ul>
                            <li>Billing errors or duplicate charges</li>
                            <li>Technical issues preventing service access</li>
                            <li>Accidental renewals due to platform errors</li>
                        </ul>

                        <h3>One-off Purchases & Coin Packs</h3>
                        <p>One-time purchases and coin packs are refundable within <strong>7 days</strong> if the coins remain unused.</p>

                        <h2>2. How to Request a Refund</h2>
                        <p>To request a refund, email us with the following information:</p>
                        <ul>
                            <li><strong>Email:</strong> Bilalouahdou@gmail.com</li>
                            <li><strong>Include:</strong> Your account email address</li>
                            <li><strong>Include:</strong> Paddle order ID (found in your email receipt)</li>
                            <li><strong>Include:</strong> Reason for refund request</li>
                        </ul>

                        <h2>3. Refund Processing</h2>
                        <p>Approved refunds are processed by Paddle (our payment processor) and returned to your original payment method within <strong>5-10 business days</strong>. Processing times may vary depending on your bank or payment provider.</p>

                        <h2>4. Refund Exceptions</h2>
                        <p>Refunds may be denied in the following circumstances:</p>
                        <ul>
                            <li>Misuse or abuse of the service</li>
                            <li>Violations of our Terms of Service</li>
                            <li>Upload of unsupported or inappropriate content</li>
                            <li>Requests outside the specified time frames</li>
                            <li>Legal or technical restrictions preventing refund processing</li>
                        </ul>

                        <h2>5. Trial Periods & Promotional Access</h2>
                        <p>Free trials, promotional access, and coupon-based subscriptions are not refundable. You can cancel these at any time to prevent future charges.</p>

                        <h2>6. Subscription Cancellation</h2>
                        <p>You may cancel your subscription at any time through your account dashboard. Cancellation prevents future billing but does not refund the current billing period unless you qualify under our refund policy.</p>

                        <h2>7. Coin Value & Transferability</h2>
                        <p>Coins have no cash value and cannot be:</p>
                        <ul>
                            <li>Withdrawn as cash</li>
                            <li>Transferred between accounts</li>
                            <li>Exchanged for refunds independent of subscription refunds</li>
                        </ul>

                        <h2>8. Contact Information</h2>
                        <p>For refund requests or questions about this policy:</p>
                        <p><strong>Bilal Ouahdou</strong><br>
                        Email: Bilalouahdou@gmail.com<br>
                        Phone: +212 655-296311</p>

                        <div class="alert alert-info mt-4">
                            <h5>Need Help?</h5>
                            <p class="mb-0">Before requesting a refund, consider reaching out for support. Many issues can be resolved quickly, and we're here to help you get the most out of VectraHub.</p>
                        </div>

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
                        <li><a href="terms" class="text-light">Terms of Service</a></li>
                        <li><a href="privacy" class="text-light">Privacy Policy</a></li>
                        <li><a href="refunds" class="text-light">Refunds</a></li>
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