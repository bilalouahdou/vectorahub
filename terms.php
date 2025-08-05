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
            <h1 class="display-4 fw-bold">Terms of Service</h1>
            <p class="lead text-muted">Last updated: <?php echo date('F j, Y'); ?></p>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <p class="mb-0"><strong>Service Operator:</strong> This service is operated by Bilal Ouahdou (sole proprietor), J.Almans 52/31 F, 10200 Tamesna, Morocco. Contact: Bilalouahdou@gmail.com, Tel: +212 655-296311.</p>
                        </div>

                        <h2>1. Acceptance of Terms</h2>
                        <p>By accessing and using VectraHub ("the Service"), you accept and agree to be bound by the terms and provisions of this agreement.</p>

                        <h2>2. Description of Service</h2>
                        <p>VectraHub is a SaaS platform that converts PNG/JPG images to SVG vector format. The service includes web application tools and API access for developers.</p>

                        <h2>3. User Accounts</h2>
                        <p>To access premium features, you must create an account. You are responsible for:</p>
                        <ul>
                            <li>Maintaining the confidentiality of your account credentials</li>
                            <li>All activities that occur under your account</li>
                            <li>Providing accurate and current information</li>
                            <li>Promptly updating any changes to your information</li>
                        </ul>

                        <h2>4. Acceptable Use</h2>
                        <p>You agree to use the Service only for lawful purposes. You agree not to:</p>
                        <ul>
                            <li>Upload content that infringes intellectual property rights</li>
                            <li>Use the Service for illegal or unauthorized purposes</li>
                            <li>Attempt unauthorized access to the Service or other users' accounts</li>
                            <li>Interfere with, disrupt, or damage the Service</li>
                            <li>Upload malicious files, viruses, or harmful content</li>
                            <li>Abuse "unlimited" features beyond fair use limits</li>
                            <li>Violate any applicable laws or regulations</li>
                        </ul>

                        <h2>5. Intellectual Property</h2>
                        <p>You retain ownership of content you upload. By using the Service, you grant VectraHub a limited, non-exclusive license to process your content solely for providing vectorization services. We do not claim ownership of your uploaded content.</p>

                        <h2>6. Payments & Taxes</h2>
                        <p>Payments are processed by Paddle as our Merchant of Record. Paddle handles all payment processing, tax collection, and compliance. By subscribing, you agree to Paddle's terms and privacy policy. All taxes and fees are included in the displayed prices where applicable.</p>

                        <h2>7. Plan Limits & Coins</h2>
                        <p>Our service operates on a "coins" model where 1 coin = 1 vectorization (image conversion). Plan limits apply as follows:</p>
                        <ul>
                            <li><strong>Free Plan:</strong> Limited coins per month</li>
                            <li><strong>Paid Plans:</strong> Monthly coin allowances as specified</li>
                            <li><strong>"Unlimited" Features:</strong> Apply only to black & white vectorizations under fair use; rate limiting applies to prevent abuse</li>
                            <li><strong>Coins:</strong> Have no cash value and are not withdrawable or transferable</li>
                            <li><strong>Referral/Earn Coins:</strong> Promotional coins earned through referrals or activities have no monetary value</li>
                        </ul>

                        <h2>8. Refunds</h2>
                        <p>Our refund policy is detailed in our <a href="refunds">Refund & Cancellation Policy</a>. Generally, refunds are available for first purchases within 7 days if minimal usage occurred.</p>

                        <h2>9. Termination</h2>
                        <p>Either party may terminate this agreement at any time. We reserve the right to suspend or terminate accounts for violations of these terms. Upon termination, your access to the Service will cease, and any remaining coins will be forfeited.</p>

                        <h2>10. Disclaimers</h2>
                        <p>The Service is provided "as is" without warranties of any kind, express or implied. We do not guarantee that the Service will be uninterrupted, error-free, or meet your specific requirements. We disclaim all warranties including merchantability and fitness for a particular purpose.</p>

                        <h2>11. Limitation of Liability</h2>
                        <p>To the maximum extent permitted by law, VectraHub and Bilal Ouahdou shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of the Service, even if advised of the possibility of such damages.</p>

                        <h2>12. Governing Law</h2>
                        <p>These Terms of Service are governed by and construed in accordance with the laws of Morocco. Any disputes arising under these terms shall be subject to the exclusive jurisdiction of Moroccan courts.</p>

                        <h2>13. Changes to Terms</h2>
                        <p>We reserve the right to modify these terms at any time. We will notify users of material changes via email or through the Service. Continued use after changes constitutes acceptance of the new terms.</p>

                        <h2>14. Contact Information</h2>
                        <p>For questions about these Terms of Service, contact:</p>
                        <p><strong>Bilal Ouahdou</strong><br>
                        J.Almans 52/31 F, 10200 Tamesna, Morocco<br>
                        Email: Bilalouahdou@gmail.com<br>
                        Phone: +212 655-296311</p>

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