<?php
session_start();
require_once 'php/config.php';
require_once 'php/utils.php';
redirectIfNotAuth();

$userId = $_SESSION['user_id'];
$csrfToken = generateCsrfToken();

// Get or create permanent referral link
$referralLink = getOrCreateReferralLink($userId);

// Get referral statistics from database
try {
    $pdo = getDBConnection();
    
    // Get total signups via this user's referral
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM referral_events WHERE referrer_user_id = ? AND event_type = 'signup'");
    $stmt->execute([$userId]);
    $totalSignups = $stmt->fetchColumn();
    
    // Get total earned coins from referrals
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM referral_rewards WHERE user_id = ? AND status = 'awarded'");
    $stmt->execute([$userId]);
    $earnedCoins = $stmt->fetchColumn();
    
    // Get recent referrals
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.email, re.created_at
        FROM referral_events re
        JOIN users u ON re.referred_user_id = u.id
        WHERE re.referrer_user_id = ? AND re.event_type = 'signup'
        ORDER BY re.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentSignups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [
        'total_signups' => (int)$totalSignups,
        'earned_coins' => (float)$earnedCoins,
        'total_clicks' => 0, // Not tracking clicks for now
        'total_conversions' => (int)$totalSignups // Each signup is a conversion
    ];
    
} catch (Exception $e) {
    error_log("Error fetching referral stats: " . $e->getMessage());
    $stats = [
        'total_signups' => 0,
        'earned_coins' => 0,
        'total_clicks' => 0,
        'total_conversions' => 0
    ];
    $recentSignups = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate Program - VectraHub</title>
    <link rel="icon" href="assets/images/vectra-hub-logo2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="display-5 fw-bold">Affiliate Program</h1>
                    <a href="dashboard" class="btn btn-outline-secondary">← Back to Dashboard</a>
                </div>
            </div>
        </div>



        <!-- Referral Link Section -->
        <div class="admin-card mb-5">
            <h2 class="mb-3">Your Permanent Referral Link</h2>
            <p class="lead">Share this link with your friends! Both you and your friend will get 50 coins when they register using your link.</p>
            <div class="input-group mb-3">
                <input type="text" id="referralLinkInput" class="form-control" value="<?php echo htmlspecialchars($referralLink); ?>" readonly>
                <button class="btn btn-accent" type="button" id="copyReferralLinkBtn">Copy Link</button>
            </div>
            <small class="text-muted">✅ You get 50 coins + Your friend gets 50 coins = Win-Win!</small>
        </div>

        <!-- Referral Statistics -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="admin-card text-center h-100">
                    <h5 class="text-muted">Total Referrals</h5>
                    <h2 class="display-4 fw-bold text-accent"><?php echo number_format($stats['total_signups']); ?></h2>
                    <p class="text-muted">People who signed up using your link</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-card text-center h-100">
                    <h5 class="text-muted">Total Earned Coins</h5>
                    <h2 class="display-4 fw-bold text-accent"><?php echo number_format($stats['earned_coins']); ?></h2>
                    <p class="text-muted">Coins earned from referrals (50 per referral)</p>
                </div>
            </div>
        </div>

        <!-- Recent Referrals -->
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Recent Referrals</h2>
                <?php if (!empty($recentSignups)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Signed Up On</th>
                                    <th>Reward</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSignups as $signup): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($signup['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($signup['email']); ?></td>
                                        <td><?php echo formatDate($signup['created_at']); ?></td>
                                        <td><span class="badge bg-success">+50 coins</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <p class="text-muted">No referrals yet. Share your link to start earning!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('copyReferralLinkBtn').addEventListener('click', function() {
            const referralLinkInput = document.getElementById('referralLinkInput');
            referralLinkInput.select();
            referralLinkInput.setSelectionRange(0, 99999); // For mobile devices

            navigator.clipboard.writeText(referralLinkInput.value).then(() => {
                VectorizeUtils.showToast('Referral link copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Failed to copy text: ', err);
                VectorizeUtils.showToast('Failed to copy link.', 'danger');
            });
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
