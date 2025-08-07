<?php
require_once 'php/config.php';
require_once 'php/utils.php';

startSession();
redirectIfNotAuth();

$subscription = getCurrentUserSubscription($_SESSION['user_id']);
$coinsRemaining = getUserCoinsRemaining($_SESSION['user_id']);

// Get all plans and group them
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE stripe_price_id IS NOT NULL ORDER BY price ASC");
$stmt->execute();
$allPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$planGroups = [];
foreach ($allPlans as $plan) {
    $baseName = preg_replace('/\s*-\s*(monthly|yearly)\s*$/i', '', $plan['name']);
    $baseName = preg_replace('/\s*-\s*Yearly\s*$/i', '', $baseName);
    $planGroups[$baseName][] = $plan;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - VectraHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <div class="container py-5">
        <h1>Billing & Subscription</h1>
        
        <!-- Current Plan -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h3>Current Plan</h3>
                <p><strong><?php echo htmlspecialchars($subscription['name'] ?? 'No active plan'); ?></strong></p>
                <p>Coins Remaining: <?php echo $coinsRemaining; ?></p>
            </div>
        </div>

        <!-- Available Plans -->
        <div class="row">
            <?php foreach ($planGroups as $baseName => $plans): ?>
                <?php
                $monthlyPlan = null;
                $yearlyPlan = null;
                
                foreach ($plans as $plan) {
                    if (strpos(strtolower($plan['name']), 'yearly') !== false) {
                        $yearlyPlan = $plan;
                    } else {
                        $monthlyPlan = $plan;
                    }
                }
                
                $displayPlan = $monthlyPlan ?: $yearlyPlan;
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($baseName); ?></h5>
                            
                            <?php if ($monthlyPlan): ?>
                                <div class="mb-3">
                                    <h6>Monthly</h6>
                                    <p class="h4">$<?php echo number_format($monthlyPlan['price'], 2); ?></p>
                                    <button class="btn btn-primary subscribe-btn" data-plan-id="<?php echo $monthlyPlan['id']; ?>">
                                        Choose Monthly
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($yearlyPlan): ?>
                                <div class="mb-3">
                                    <h6>Yearly <span class="badge bg-success">Save 20%</span></h6>
                                    <p class="h4">$<?php echo number_format($yearlyPlan['price'], 2); ?></p>
                                    <button class="btn btn-success subscribe-btn" data-plan-id="<?php echo $yearlyPlan['id']; ?>">
                                        Choose Yearly
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
            
            document.querySelectorAll('.subscribe-btn').forEach(button => {
                button.addEventListener('click', async function() {
                    const planId = this.dataset.planId;
                    this.disabled = true;
                    this.textContent = 'Processing...';
                    
                    try {
                        const response = await fetch('/php/create_checkout_working.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ plan_id: planId })
                        });
                        
                        const result = await response.json();
                        
                        if (result.session_id) {
                            // Real Stripe session
                            stripe.redirectToCheckout({ sessionId: result.session_id });
                        } else if (result.success) {
                            // Test success
                            alert('Test successful! Plan: ' + result.plan_name + ' ($' + result.plan_price + ')');
                            this.disabled = false;
                            this.textContent = 'Choose Monthly';
                        } else {
                            alert('Error: ' + (result.error || 'Unknown error'));
                            this.disabled = false;
                            this.textContent = 'Try Again';
                        }
                    } catch (error) {
                        alert('Network error');
                        this.disabled = false;
                        this.textContent = 'Try Again';
                    }
                });
            });
        });
    </script>
</body>
</html>
