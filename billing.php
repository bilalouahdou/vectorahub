<?php
require_once 'php/utils.php';
require_once 'php/config.php'; // Ensure config is loaded for Stripe keys

// Ensure session is started first
startSession();
redirectIfNotAuth();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCsrfToken();
}

$userId = $_SESSION['user_id'];
$coinsRemaining = getUserCoinsRemaining($userId);
$currentSubscription = getCurrentUserSubscription($userId);

// Handle Stripe return
$paymentSuccess = false;
$paymentError = '';

if (isset($_GET['session_id'])) {
    try {
        require_once 'vendor/autoload.php';
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        
        $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);
        
        if ($session->payment_status === 'paid') {
            $userId = $session->metadata->user_id;
            $planId = $session->metadata->plan_id;
            $amount = $session->amount_total / 100;
            
            $success = processPurchase($userId, $planId, $amount, $session->id);
            
            if ($success) {
                $paymentSuccess = true;
                // Refresh data
                $coinsRemaining = getUserCoinsRemaining($userId);
                $currentSubscription = getCurrentUserSubscription($userId);
            } else {
                $paymentError = 'There was an error updating your subscription. Please contact support.';
            }
        } else {
            $paymentError = 'Payment was not completed. Please try again.';
        }
    } catch (Exception $e) {
        error_log("Payment verification error: " . $e->getMessage());
        $paymentError = 'Payment verification failed. Please contact support if you were charged.';
    }
}
// Handle free plan activation success message
$activationSuccess = isset($_GET['activation_success']);


// Get available plans, grouped by billing period
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans ORDER BY name, billing_period ASC");
    $stmt->execute();
    $allPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group plans by base name, but exclude yearly-only plans from main display
    $planGroups = [];
    foreach ($allPlans as $plan) {
        $baseName = preg_replace('/ - (Monthly|Yearly)$/', '', $plan['name']);
        
        // Skip yearly-only plan names (they should be combined with monthly)
        if (strpos($plan['name'], '- Yearly') !== false && strpos($plan['name'], 'VectraHub') === 0) {
            $baseName = str_replace(' - Yearly', '', $plan['name']);
        }
        
        if (!isset($planGroups[$baseName])) {
            $planGroups[$baseName] = [];
        }
        $planGroups[$baseName][] = $plan;
    }
    
    // Remove groups that only have yearly plans (they should be combined with monthly)
    $finalPlanGroups = [];
    foreach ($planGroups as $baseName => $plans) {
        // Only include if we have a monthly plan or it's the free plan
        $hasMonthly = false;
        foreach ($plans as $plan) {
            if ($plan['billing_period'] === 'monthly' || $plan['name'] === 'Free') {
                $hasMonthly = true;
                break;
            }
        }
        if ($hasMonthly) {
            $finalPlanGroups[$baseName] = $plans;
        }
    }
    $planGroups = $finalPlanGroups;
} catch (Exception $e) {
    error_log("Failed to load plans: " . $e->getMessage());
    $planGroups = [];
}

// Get purchase history
try {
    $stmt = $pdo->prepare("
        SELECT us.*, sp.name as plan_name, sp.coin_limit, p.amount, p.paid_at
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.plan_id = sp.id
        LEFT JOIN payments p ON p.user_id = us.user_id AND p.plan_id = us.plan_id
        WHERE us.user_id = ?
        ORDER BY us.start_date DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $purchaseHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to load purchase history: " . $e->getMessage());
    $purchaseHistory = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Subscription - VectorizeAI</title>
    <link rel="icon" href="assets/images/vectra-hub-logo2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    
    <!-- Stripe.js must load first, before any custom scripts -->
    <script src="https://js.stripe.com/v3/"></script>
    
    <!-- Inject Stripe publishable key with fail-fast validation -->
    <?php $pk = STRIPE_PUBLISHABLE_KEY ?? ''; ?>
    <script>
        // Validate publishable key before making it available
        if (!'<?= $pk ?>') {
            console.error('Publishable key missing – Stripe will not initialise');
        }
        const STRIPE_PUBLISHABLE_KEY = "<?= htmlspecialchars($pk) ?>";
    </script>
</head>
<body>
    <!-- Hidden CSRF token for JavaScript -->
    <input type="hidden" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    
    <div class="container py-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="display-5 fw-bold">Billing & Subscription</h1>
                    <a href="dashboard" class="btn btn-outline-secondary">← Back to Dashboard</a>
                </div>
            </div>
        </div>

        <!-- Payment Success/Error Messages -->
        <?php if ($paymentSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <h5>🎉 Payment Successful!</h5>
                <p class="mb-0">Your subscription has been updated and coins have been added to your account.</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($paymentError): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5>❌ Payment Error</h5>
                <p class="mb-0"><?php echo htmlspecialchars($paymentError); ?></p>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($activationSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <h5>🎉 Free Plan Activated!</h5>
                <p class="mb-0">Your free plan has been successfully activated.</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Current Status -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="admin-card">
                    <h5>Current Plan</h5>
                    <?php if ($currentSubscription): ?>
                        <h3 class="text-accent"><?php echo htmlspecialchars($currentSubscription['name']); ?></h3>
                        <p class="mb-1">Expires: <?php echo formatDate($currentSubscription['end_date']); ?></p>
                        <p class="mb-0">Coin Limit: <?php echo number_format($currentSubscription['coin_limit']); ?></p>
                        <?php if ($currentSubscription['unlimited_black_images']): ?>
                            <p class="mb-0 text-success">✅ Unlimited Black Image Vectorizations</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <h3 class="text-muted">No Active Plan</h3>
                        <p class="mb-0">Choose a plan below to get started</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-card">
                    <h5>Coins Remaining (Standard)</h5>
                    <h3 class="text-accent"><?php echo number_format($coinsRemaining); ?></h3>
                    <p class="mb-0">
                        <?php if ($coinsRemaining <= 5): ?>
                            <span class="text-danger">⚠️ Running low! Consider upgrading.</span>
                        <?php else: ?>
                            <span class="text-success">✅ You're all set!</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Available Plans -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="mb-4">Available Plans</h2>
            </div>
            
            <?php foreach ($planGroups as $baseName => $plans): ?>
                <?php 
                $monthlyPlan = null;
                $yearlyPlan = null;
                foreach ($plans as $plan) {
                    if ($plan['billing_period'] === 'monthly') $monthlyPlan = $plan;
                    if ($plan['billing_period'] === 'yearly') $yearlyPlan = $plan;
                }
                ?>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card h-100 text-center position-relative">
                        <?php if ($baseName === 'VectraHub Pro'): ?>
                            <div class="position-absolute top-0 start-50 translate-middle">
                                <span class="badge bg-accent text-dark">Most Popular</span>
                            </div>
                        <?php elseif ($baseName === 'VectraHub Black Pack'): ?>
                            <div class="position-absolute top-0 start-50 translate-middle">
                                <span class="badge bg-dark text-light">Best Value</span>
                            </div>
                        <?php endif; ?>
                        
                        <h4 class="mt-3"><?php echo htmlspecialchars($baseName); ?></h4>
                        <p class="text-muted small mb-3">Professional vectorization solution</p>
                        
                        <!-- Coins Display -->
                        <?php if ($monthlyPlan): ?>
                            <div class="mb-3">
                                <h5 class="text-accent"><?php echo number_format($monthlyPlan['coin_limit']); ?> Coins</h5>
                                <p class="small text-muted mb-0">per month</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Monthly Option -->
                        <?php if ($monthlyPlan && $monthlyPlan['price'] > 0): ?>
                            <div class="pricing-option mb-3 p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">Monthly</span>
                                </div>
                                <div class="mb-2">
                                    <span class="h5 mb-0"><?php echo formatCurrency($monthlyPlan['price']); ?></span>
                                    <span class="text-muted">/month</span>
                                </div>
                                <button class="btn btn-outline-primary btn-sm w-100 buy-now-btn" 
                                        data-plan-id="<?php echo $monthlyPlan['id']; ?>"
                                        data-plan-name="<?php echo htmlspecialchars($monthlyPlan['name']); ?>"
                                        data-plan-price="<?php echo $monthlyPlan['price']; ?>">
                                    <span class="btn-text">Select Monthly</span>
                                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Yearly Option -->
                        <?php if ($yearlyPlan): ?>
                            <div class="pricing-option mb-3 p-3 border rounded position-relative bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">Yearly</span>
                                    <span class="badge bg-success">Save 20%</span>
                                </div>
                                <div class="mb-2">
                                    <span class="h5 mb-0"><?php echo formatCurrency($yearlyPlan['price']); ?></span>
                                    <span class="text-muted">/year</span>
                                </div>
                                <button class="btn btn-primary btn-sm w-100 buy-now-btn" 
                                        data-plan-id="<?php echo $yearlyPlan['id']; ?>"
                                        data-plan-name="<?php echo htmlspecialchars($yearlyPlan['name']); ?>"
                                        data-plan-price="<?php echo $yearlyPlan['price']; ?>">
                                    <span class="btn-text">Select Yearly</span>
                                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Free Plan Special Case -->
                        <?php if ($baseName === 'Free'): ?>
                            <div class="pricing-option mb-3 p-3 border rounded">
                                <div class="mb-2">
                                    <span class="h5 mb-0">$0.00</span>
                                    <span class="text-muted">/month</span>
                                </div>
                                <div class="mb-3">
                                    <h6><?php echo number_format($monthlyPlan['coin_limit']); ?> Coins/month</h6>
                                </div>
                                <?php if ($currentSubscription['name'] === 'Free'): ?>
                                    <button class="btn btn-outline-secondary w-100" disabled>
                                        Current Plan
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success w-100 activate-free-plan-btn" 
                                            data-plan-id="<?php echo $monthlyPlan['id']; ?>">
                                        <span class="btn-text">Activate Free Plan</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Features List -->
                        <?php if ($monthlyPlan): ?>
                            <div class="features-list text-start small">
                                <?php 
                                $features = explode(';', $monthlyPlan['features']);
                                foreach($features as $feature): 
                                    $feature = trim($feature);
                                    if (!empty($feature)):
                                ?>
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="text-success me-2">✓</i>
                                        <span><?php echo htmlspecialchars($feature); ?></span>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Purchase History -->
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Purchase History</h2>
                <?php if (!empty($purchaseHistory)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date Purchased</th>
                                    <th>Plan Name</th>
                                    <th>Coins Added</th>
                                    <th>Amount Paid</th>
                                    <th>Expires At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchaseHistory as $purchase): ?>
                                    <tr>
                                        <td><?php echo formatDate($purchase['start_date']); ?></td>
                                        <td><?php echo htmlspecialchars($purchase['plan_name']); ?></td>
                                        <td><?php echo number_format($purchase['coin_limit']); ?></td>
                                        <td>
                                            <?php if ($purchase['amount']): ?>
                                                <?php echo formatCurrency($purchase['amount']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Free</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($purchase['end_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <p class="text-muted">No purchase history yet.</p>
                        <p>Choose a plan above to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/billing.js"></script>
    
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
                        <li><a href="help" class="text-light">Help Center</a></li>
                        <li><a href="contact" class="text-light">Contact</a></li>
                        <li><a href="referral" class="text-light">Referral Program</a></li>
                        <li><a href="ad-rewards" class="text-light">Earn Coins</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h4 class="h6 mb-3">Legal</h4>
                    <ul class="list-unstyled">
                        <li><a href="privacy" class="text-light">Privacy Policy</a></li>
                        <li><a href="terms" class="text-light">Terms of Service</a></li>
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
