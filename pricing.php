<?php
require_once 'php/config.php';
require_once 'php/utils.php';

// Fetch plans from database - filter out yearly-only plans 
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans ORDER BY price ASC");
    $stmt->execute();
    $allPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter out yearly-only plans (those with "- Yearly" in the name)
    // We only want to show base plans (monthly plans) and combine yearly pricing within each card
    $plans = [];
    foreach ($allPlans as $plan) {
        // Skip plans that are specifically yearly plans (with "- Yearly" in name)
        if (strpos($plan['name'], '- Yearly') !== false) {
            continue;
        }
        $plans[] = $plan;
    }
} catch (Exception $e) {
    error_log("Failed to load plans: " . $e->getMessage());
    $plans = [];
}

// Determine recommended plan (Pro plan)
$recommendedPlanName = 'Pro';

// Get current user's subscription for comparison (if logged in)
$currentSubscription = null;
$currentPlanId = null;
if (isLoggedIn() && isset($_SESSION['user_id'])) {
    $currentSubscription = getCurrentUserSubscription($_SESSION['user_id']);
    $currentPlanId = $currentSubscription['plan_id'] ?? null;
}

// Function to calculate yearly price with 20% discount
function calculateYearlyPrice($monthlyPrice) {
    if ($monthlyPrice <= 0) return 0;
    return $monthlyPrice * 12 * 0.8; // 20% discount
}

// Function to calculate yearly savings
function calculateYearlySavings($monthlyPrice) {
    if ($monthlyPrice <= 0) return 0;
    $fullYearlyPrice = $monthlyPrice * 12;
    $discountedYearlyPrice = calculateYearlyPrice($monthlyPrice);
    return $fullYearlyPrice - $discountedYearlyPrice;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing - VectorizeAI</title>
    <link rel="icon" href="assets/images/vectra-hub-logo2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/" class="text-accent">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pricing</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-3">Choose Your Plan</h1>
            <p class="lead text-muted">Select the perfect plan for your vectorization needs</p>
            <div class="alert alert-info d-inline-block">
                <strong>💰 Save 20%</strong> when you choose yearly billing!
            </div>
        </div>

        <!-- Coupon Code Section -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-6">
                <div class="card border-accent">
                    <div class="card-header bg-accent text-dark text-center">
                        <h5 class="mb-0">🎟️ Have a Coupon Code?</h5>
                    </div>
                    <div class="card-body">
                        <form id="couponForm">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="couponCode" 
                                       placeholder="Enter coupon code (e.g., FREEULTIMATE)" 
                                       style="text-transform: uppercase;">
                                <button class="btn btn-accent" type="submit">Apply Coupon</button>
                            </div>
                            <?php if (isLoggedIn() && isset($_SESSION['csrf_token'])): ?>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <?php endif; ?>
                        </form>
                        <div id="couponMessage"></div>
                        
                        <!-- Sample Coupon Codes for Demo -->
                        <!-- <div class="mt-3">
                            <small class="text-muted">Try these sample codes:</small>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <span class="badge bg-light text-dark border coupon-sample" style="cursor: pointer;">WELCOME20</span>
                                <span class="badge bg-light text-dark border coupon-sample" style="cursor: pointer;">FREEULTIMATE</span>
                                <span class="badge bg-light text-dark border coupon-sample" style="cursor: pointer;">UPGRADE30</span>
                                <span class="badge bg-light text-dark border coupon-sample" style="cursor: pointer;">FREEYEAR</span>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Cards Grid -->
        <div class="row g-4 mb-5">
            <?php if (empty($plans)): ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        <h5>No plans available</h5>
                        <p class="mb-0">Please contact support for assistance.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($plans as $plan): ?>
                    <div class="col-12 col-md-4">
                        <div class="feature-card h-100 position-relative <?php echo ($plan['name'] == $recommendedPlanName) ? 'border-accent' : ''; ?>">
                            <!-- Recommended Badge -->
                            <?php if ($plan['name'] == $recommendedPlanName): ?>
                                <div class="position-absolute top-0 start-50 translate-middle">
                                    <span class="badge bg-accent text-dark px-3 py-2">
                                        ⭐ Most Popular
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Current Plan Badge -->
                            <?php if ($plan['id'] == $currentPlanId): ?>
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-success">
                                        ✓ Current Plan
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="text-center p-4">
                                <!-- Plan Name -->
                                <h3 class="fw-bold mb-3 <?php echo ($plan['name'] == $recommendedPlanName) ? 'text-accent' : ''; ?>">
                                    <?php echo htmlspecialchars($plan['name']); ?>
                                </h3>
                                
                                <!-- Plan Description -->
                                <p class="text-muted small mb-3">
                                    <?php 
                                    switch($plan['name']) {
                                        case 'Free':
                                            echo 'Perfect for getting started with vectorization';
                                            break;
                                        case 'Black Pack':
                                            echo 'Unlimited black image processing with high volume';
                                            break;
                                        case 'Pro':
                                            echo 'Most popular choice for professionals';
                                            break;
                                        case 'API Pro':
                                            echo 'For developers and high-volume users';
                                            break;
                                        default:
                                            echo 'Professional vectorization solution';
                                    }
                                    ?>
                                </p>

                                <!-- Coin Limit -->
                                <div class="mb-4">
                                    <h4 class="text-primary">
                                        <?php 
                                        if ($plan['name'] == 'API Pro') {
                                            echo number_format($plan['coin_limit']) . ' API Calls';
                                        } elseif ($plan['name'] == 'Black Pack') {
                                            echo number_format($plan['coin_limit']) . ' Coins';
                                        } elseif ($plan['name'] == 'Free') {
                                            echo '25 Coins';
                                        } else {
                                            echo number_format($plan['coin_limit']) . ' Coins';
                                        }
                                        ?>
                                    </h4>
                                    <p class="text-muted small">per month</p>
                                </div>

                                <!-- Pricing Options -->
                                <div class="mb-4 price-display" data-plan-id="<?php echo $plan['id']; ?>" 
                                     data-monthly-price="<?php echo $plan['price']; ?>" 
                                     data-yearly-price="<?php echo calculateYearlyPrice($plan['price']); ?>">
                                    
                                    <!-- Monthly Price -->
                                    <div class="pricing-option mb-3 p-3 border rounded">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold">Monthly</span>
                                        </div>
                                        <?php if ($plan['price'] > 0): ?>
                                            <h4 class="text-accent mb-2 monthly-price" data-plan-id="<?php echo $plan['id']; ?>">
                                                $<?php echo number_format($plan['price'], 2); ?>
                                                <small class="text-muted">/month</small>
                                            </h4>
                                        <?php else: ?>
                                            <h4 class="text-success mb-2">Free</h4>
                                        <?php endif; ?>
                                        
                                        <?php if ($plan['id'] == $currentPlanId): ?>
                                            <button class="btn btn-outline-success btn-sm w-100" disabled>
                                                Current Plan
                                            </button>
                                        <?php else: ?>
                                            <a href="billing?plan_id=<?php echo $plan['id']; ?>&type=monthly" 
                                               class="btn btn-outline-primary btn-sm w-100 plan-select-btn">
                                                Select Monthly
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Yearly Price -->
                                    <?php if ($plan['price'] > 0): ?>
                                        <div class="pricing-option p-3 border rounded position-relative bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold">Yearly</span>
                                                <span class="badge bg-success">Save 20%</span>
                                            </div>
                                            <h4 class="text-accent mb-1 yearly-price" data-plan-id="<?php echo $plan['id']; ?>">
                                                $<?php echo number_format(calculateYearlyPrice($plan['price']), 2); ?>
                                                <small class="text-muted">/year</small>
                                            </h4>
                                            <p class="small text-success mb-2">
                                                Save $<?php echo number_format(calculateYearlySavings($plan['price']), 2); ?> per year!
                                            </p>
                                            
                                            <?php if ($plan['id'] == $currentPlanId): ?>
                                                <button class="btn btn-outline-success btn-sm w-100" disabled>
                                                    Current Plan
                                                </button>
                                            <?php else: ?>
                                                <a href="billing?plan_id=<?php echo $plan['id']; ?>&type=yearly" 
                                                   class="btn <?php echo ($plan['name'] == $recommendedPlanName) ? 'btn-accent' : 'btn-primary'; ?> btn-sm w-100 plan-select-btn">
                                                    Select Yearly
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="pricing-option p-3 border rounded bg-light">
                                            <div class="text-center">
                                                <h4 class="text-success mb-2">Always Free</h4>
                                                <?php if ($plan['id'] == $currentPlanId): ?>
                                                    <button class="btn btn-outline-success btn-sm w-100" disabled>
                                                        Current Plan
                                                    </button>
                                                <?php else: ?>
                                                    <a href="billing?plan_id=<?php echo $plan['id']; ?>&type=monthly" 
                                                       class="btn btn-success btn-sm w-100 plan-select-btn">
                                                        Get Started Free
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Features/Description -->
                                <div class="text-start">
                                    <?php 
                                    // Define logical features based on plan name
                                    $planFeatures = [];
                                    switch($plan['name']) {
                                        case 'Free':
                                            $planFeatures = [
                                                '25 vectorizations per month',
                                                'Standard processing',
                                                'Basic support'
                                            ];
                                            break;
                                        case 'Black Unlimited Pack':
                                            $planFeatures = [
                                                'Unlimited black image vectorizations',
                                                '1,000,000 standard image vectorizations',
                                                'Priority processing',
                                                'Email support'
                                            ];
                                            break;
                                        case 'Pro':
                                            $planFeatures = [
                                                '500 vectorizations per month',
                                                'Priority processing',
                                                'Email support',
                                                'HD output'
                                            ];
                                            break;
                                        case 'API Pro':
                                            $planFeatures = [
                                                '5,000 vectorizations per month',
                                                'API access',
                                                'Priority processing',
                                                'Premium support',
                                                'Bulk operations'
                                            ];
                                            break;
                                        default:
                                            // Use database features if available
                                            if (!empty($plan['features'])) {
                                                $planFeatures = explode(';', $plan['features']);
                                            }
                                    }
                                    ?>
                                    <ul class="list-unstyled">
                                        <?php foreach ($planFeatures as $feature): ?>
                                            <?php if (trim($feature)): ?>
                                                <li class="mb-2">
                                                    <i class="text-accent">✓</i> <?php echo trim(htmlspecialchars($feature)); ?>
                                                </li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- FAQ Section -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2 class="text-center mb-4">Frequently Asked Questions</h2>
                
                <div class="accordion" id="pricingFAQ">
                    <!-- FAQ Item 1 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faq1">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapse1" aria-expanded="false" aria-controls="collapse1">
                                How do coupon codes work?
                            </button>
                        </h3>
                        <div id="collapse1" class="accordion-collapse collapse" aria-labelledby="faq1" 
                             data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                <p>We offer three types of coupon codes:</p>
                                <ul>
                                    <li><strong>Discount Coupons:</strong> Reduce the price by a percentage (e.g., WELCOME20 for 20% off)</li>
                                    <li><strong>Free Plan Access:</strong> Get free access to premium plans for a specific duration (e.g., FREEULTIMATE)</li>
                                    <li><strong>Free Upgrades:</strong> Upgrade your current plan for free for a limited time (e.g., UPGRADE30)</li>
                                </ul>
                                <p>Simply enter your coupon code above and click "Apply Coupon" to see the benefits!</p>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 2 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faq2">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                When do my coins reset?
                            </button>
                        </h3>
                        <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" 
                             data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                <p>Your coins reset at the start of each billing cycle:</p>
                                <ul>
                                    <li><strong>Monthly plans:</strong> Coins reset every 30 days from your subscription start date</li>
                                    <li><strong>Yearly plans:</strong> Coins reset monthly, but you're billed annually</li>
                                    <li><strong>Free coupon plans:</strong> Coins reset monthly during your free period</li>
                                </ul>
                                <p>Unused coins from the previous month do not roll over.</p>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 3 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faq3">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                Can I use multiple coupon codes?
                            </button>
                        </h3>
                        <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" 
                             data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                <p>Each user can use each coupon code only once. However, you can use different coupon codes over time:</p>
                                <ul>
                                    <li>Use a discount coupon for your first purchase</li>
                                    <li>Later use a free upgrade coupon when available</li>
                                    <li>Coupon codes cannot be combined in a single transaction</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 4 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faq4">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                What happens when my free coupon period ends?
                            </button>
                        </h3>
                        <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="faq4" 
                             data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                <p>When your free coupon period expires:</p>
                                <ul>
                                    <li>Your account will revert to the Free plan automatically</li>
                                    <li>You'll receive an email notification 3 days before expiration</li>
                                    <li>You can upgrade to a paid plan anytime during or after the free period</li>
                                    <li>No automatic charges - you stay in control</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="text-center mt-5 pt-5 border-top">
            <h3 class="mb-3">Ready to get started?</h3>
            <p class="text-muted mb-4">Join thousands of users who trust VectorizeAI for their image vectorization needs.</p>
                                        <a href="dashboard" class="btn btn-outline-secondary me-3">← Back to Dashboard</a>
                            <a href="billing" class="btn btn-accent">View Billing</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/pricing.js"></script>
    
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
                        <li><a href="pricing" class="text-light">Pricing</a></li>
                        <li><a href="terms" class="text-light">Terms</a></li>
                        <li><a href="privacy" class="text-light">Privacy</a></li>
                        <li><a href="refunds" class="text-light">Refunds</a></li>
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
