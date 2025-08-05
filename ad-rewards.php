<?php
require_once 'php/utils.php';
redirectIfNotAuth();

$userId = $_SESSION['user_id'];
$csrfToken = generateCsrfToken();

// Fetch current ad view status
$adStatus = ['success' => false, 'error' => 'Not loaded'];
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, APP_URL . '/php/api/ad_view.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id()); // Pass session cookie
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new Exception("Failed to fetch ad status: cURL error.");
    }

    $adStatus = json_decode($response, true);
    if ($httpCode !== 200 || !($adStatus['success'] ?? false)) {
        throw new Exception($adStatus['error'] ?? 'Unknown error fetching ad status.');
    }
} catch (Exception $e) {
    error_log("Error fetching ad status: " . $e->getMessage());
    $adStatus['error'] = $e->getMessage();
}

$currentViews = $adStatus['current_views'] ?? 0;
$maxViews = $adStatus['max_views'] ?? 5;
$coinsPerView = $adStatus['coins_per_view'] ?? 3;
$canWatchAd = $currentViews < $maxViews;
$coinsRemaining = getUserCoinsRemaining($userId); // Get updated coins
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad Rewards - VectraHub</title>
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
                    <h1 class="display-5 fw-bold">Ad Rewards</h1>
                    <a href="dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
                </div>
            </div>
        </div>

        <?php if (!($adStatus['success'] ?? false)): ?>
            <div class="alert alert-danger" role="alert">
                Failed to load ad reward data: <?php echo htmlspecialchars($adStatus['error']); ?>
            </div>
        <?php endif; ?>

        <div class="admin-card mb-5 text-center">
            <h2 class="mb-3">Watch Ads, Earn Coins!</h2>
            <p class="lead">Watch short advertisements to earn bonus coins for vectorization.</p>
            <p class="h3 text-accent mb-4">Earn <?php echo $coinsPerView; ?> Coins per Ad!</p>

            <div class="mb-4">
                <p class="h5">Ads Watched Today: <span id="adViewsCount" class="fw-bold"><?php echo $currentViews; ?></span> / <?php echo $maxViews; ?></p>
                <p class="h5">Your Current Coins: <span id="coinsRemainingCount" class="fw-bold"><?php echo number_format($coinsRemaining); ?></span></p>
            </div>

            <?php if ($canWatchAd): ?>
                <button id="watchAdBtn" class="btn btn-accent btn-lg">
                    <span class="btn-text">Watch Ad Now</span>
                    <span class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                </button>
                <p class="text-muted mt-2">You can watch <?php echo ($maxViews - $currentViews); ?> more ads today.</p>
            <?php else: ?>
                <button class="btn btn-secondary btn-lg" disabled>Daily Ad Limit Reached</button>
                <p class="text-muted mt-2">Come back tomorrow to earn more coins!</p>
            <?php endif; ?>

            <input type="hidden" id="csrfToken" value="<?php echo htmlspecialchars($csrfToken); ?>">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const watchAdBtn = document.getElementById('watchAdBtn');
            const adViewsCountSpan = document.getElementById('adViewsCount');
            const coinsRemainingCountSpan = document.getElementById('coinsRemainingCount');
            const csrfToken = document.getElementById('csrfToken').value;
            const maxViews = <?php echo $maxViews; ?>;
            const coinsPerView = <?php echo $coinsPerView; ?>;

            if (watchAdBtn) {
                watchAdBtn.addEventListener('click', async function() {
                    if (this.disabled) return;

                    // Simulate ad watching (replace with actual ad integration)
                    const btnText = this.querySelector('.btn-text');
                    const spinner = this.querySelector('.spinner-border');
                    const originalText = btnText.textContent;

                    btnText.textContent = 'Loading Ad...';
                    spinner.classList.remove('d-none');
                    this.disabled = true;

                    await new Promise(resolve => setTimeout(resolve, 3000)); // Simulate ad loading time

                    try {
                        const formData = new FormData();
                        formData.append('csrf_token', csrfToken);

                        const response = await fetch('php/api/ad_view.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();

                        if (result.success) {
                            VectorizeUtils.showToast(`🎉 You earned ${result.coins_earned} coins!`, 'success');
                            adViewsCountSpan.textContent = result.new_view_count;
                            coinsRemainingCountSpan.textContent = result.coins_remaining.toLocaleString();

                            if (result.new_view_count >= maxViews) {
                                watchAdBtn.disabled = true;
                                btnText.textContent = 'Daily Ad Limit Reached';
                                watchAdBtn.classList.remove('btn-accent');
                                watchAdBtn.classList.add('btn-secondary');
                            } else {
                                btnText.textContent = originalText;
                                watchAdBtn.disabled = false;
                            }
                        } else {
                            VectorizeUtils.showToast(`❌ ${result.error}`, 'danger');
                            btnText.textContent = originalText;
                            watchAdBtn.disabled = false;
                        }
                    } catch (error) {
                        console.error('Error watching ad:', error);
                        VectorizeUtils.showToast('Network error. Please try again.', 'danger');
                        btnText.textContent = originalText;
                        watchAdBtn.disabled = false;
                    } finally {
                        spinner.classList.add('d-none');
                    }
                });
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
