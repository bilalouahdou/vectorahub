<?php 
require_once 'php/config.php';
require_once 'php/utils.php';

// isLoggedIn function is now in utils.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/images/vectra-hub-logo2.png" type="image/png">
    
    <!-- SEO Meta Tags -->
    <title>Free AI Image Vectorizer | Convert JPG PNG to SVG Online - VectraHub</title>
    <meta name="description" content="Transform images to crisp SVG vectors instantly with VectraHub. Free AI-powered tool for designers, print shops & students. Batch upload, perfect for logos & graphics.">
    <meta name="keywords" content="image vectorizer, convert jpg to svg, png to svg converter, free vector tool, ai vectorization, vectra hub">
    <link rel="canonical" href="https://vectorahub.online/">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Free AI Image Vectorizer | Convert JPG PNG to SVG Online - VectraHub">
    <meta property="og:description" content="Transform images to crisp SVG vectors instantly with VectraHub. Free AI-powered tool for designers, print shops & students.">
    <meta property="og:image" content="https://vectorahub.online/assets/images/og-vectorizer-demo.jpg">
    <meta property="og:url" content="https://vectorahub.online/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="VectraHub">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Free AI Image Vectorizer | Convert JPG PNG to SVG Online - VectraHub">
    <meta name="twitter:description" content="Transform images to crisp SVG vectors instantly with VectraHub. Free AI-powered tool for designers, print shops & students.">
    <meta name="twitter:image" content="https://vectorahub.online/assets/images/twitter-vectorizer-demo.jpg">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" as="style">
    <link rel="preload" href="assets/css/custom.css" as="style">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "VectraHub",
        "description": "Free AI-powered image vectorization tool that converts JPG and PNG images to scalable SVG format",
        "url": "https://vectorahub.online",
        "applicationCategory": "DesignApplication",
        "operatingSystem": "Web Browser",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "reviewCount": "1247"
        }
    }
    </script>
</head>
<body>
    
    <!-- Skip Navigation for Accessibility -->
    <a href="#main-content" class="visually-hidden-focusable">Skip to main content</a>
    
    <!-- Header -->
    <header role="banner">
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top" role="navigation" aria-label="Main navigation">
            <div class="container">
                <a class="navbar-brand fw-bold d-flex align-items-center" href="/" aria-label="VectraHub - Free Image Vectorizer">
                    <img src="assets/images/vectra-hub-logo.png" alt="VectraHub Logo" height="32" class="me-2">
                    <span class="text-accent">Vectra</span><span class="text-light">Hub</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto" role="menubar">
                        <li class="nav-item" role="none">
                            <a class="nav-link" href="#features" role="menuitem">Features</a>
                        </li>
                        <li class="nav-item" role="none">
                            <a class="nav-link" href="#how-it-works" role="menuitem">How It Works</a>
                        </li>
                        <li class="nav-item" role="none">
                            <a class="nav-link" href="/blog/" role="menuitem">Blog</a>
                        </li>
                        <li class="nav-item" role="none">
                            <a class="nav-link" href="pricing.php" role="menuitem">Pricing</a>
                        </li>
                        <li class="nav-item" role="none">
                            <a class="nav-link" href="api_documentation.php" role="menuitem">API</a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item" role="none">
                                <a class="nav-link" href="dashboard.php" role="menuitem">Dashboard</a>
                            </li>
                            <li class="nav-item" role="none">
                                <a class="nav-link" href="../auth/logout.php" role="menuitem">Logout</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item" role="none">
                                <a class="nav-link" href="login.php" role="menuitem">Login</a>
                            </li>
                            <li class="nav-item" role="none">
                                <a class="btn btn-accent ms-2" href="register.php" role="menuitem">Start Free</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main id="main-content" role="main">
        <!-- Hero Section -->
        <section class="hero-section" aria-labelledby="hero-heading">
            <div class="container">
                <div class="row align-items-center min-vh-100">
                    <div class="col-lg-6">
                        <header>
                            <h1 id="hero-heading" class="display-4 fw-bold mb-4">
                                Free AI <span class="text-accent">Image Vectorizer</span> - Convert JPG & PNG to <span class="text-secondary">SVG</span>
                            </h1>
                        </header>
                        <p class="lead mb-4">
                            Transform any image into crisp, scalable SVG vectors instantly with VectraHub. Perfect for logos, graphics, and print designs. 
                            AI-powered with Waifu2x upscaling and VTracer precision - completely free to use.
                        </p>
                        
                        <!-- Key Benefits -->
                        <div class="hero-benefits mb-4">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="benefit-item">
                                        <span class="benefit-icon">🚀</span>
                                        <span>AI-Enhanced Quality</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="benefit-item">
                                        <span class="benefit-icon">⚡</span>
                                        <span>Instant Processing</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="benefit-item">
                                        <span class="benefit-icon">📦</span>
                                        <span>Batch Upload</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="benefit-item">
                                        <span class="benefit-icon">💯</span>
                                        <span>100% Free</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!isLoggedIn()): ?>
                            <div class="hero-actions" role="group" aria-label="Get started actions">
                                <a href="register.php" class="btn btn-accent btn-lg me-3" 
                                   aria-describedby="free-signup-desc">Start Vectorizing Free</a>
                                <a href="#upload" class="btn btn-outline-light btn-lg" 
                                   aria-describedby="try-now-desc">Try Demo</a>
                                <div id="free-signup-desc" class="visually-hidden">Create a free account to start vectorizing images</div>
                                <div id="try-now-desc" class="visually-hidden">Try the image upload tool below</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-6">
                        <aside class="upload-card" id="upload" aria-labelledby="upload-heading">
                            <div class="card shadow-lg">
                                <div class="card-body p-4">
                                    <h2 id="upload-heading" class="card-title text-center mb-4">Upload Your Image</h2>
                                    
                                    <?php if (isLoggedIn()): ?>
                                        <form id="uploadForm" enctype="multipart/form-data" aria-labelledby="upload-heading">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            
                                            <!-- File Upload Area -->
                                            <div class="upload-area" id="uploadArea" role="button" tabindex="0" 
                                                 aria-label="Click to select image file or drag and drop image here"
                                                 aria-describedby="upload-instructions">
                                                <div class="upload-content">
                                                    <i class="upload-icon" aria-hidden="true">📁</i>
                                                    <p class="mb-2">Drop your image here or click to browse</p>
                                                    <small id="upload-instructions" class="text-muted">PNG, JPG up to 5MB</small>
                                                </div>
                                                <input type="file" id="imageFile" name="image" accept=".png,.jpg,.jpeg" 
                                                       class="d-none" aria-describedby="upload-instructions">
                                            </div>
                                            
                                            <div class="text-center my-3" aria-hidden="true">
                                                <span class="text-muted">OR</span>
                                            </div>
                                            
                                            <!-- URL Input -->
                                            <div class="mb-3">
                                                <label for="imageUrl" class="visually-hidden">Image URL</label>
                                                <input type="url" id="imageUrl" name="image_url" class="form-control" 
                                                       placeholder="Paste image URL..." aria-describedby="url-help">
                                                <div id="url-help" class="visually-hidden">Enter a direct link to an image file</div>
                                            </div>
                                            
                                            <button type="submit" id="vectorizeBtn" class="btn btn-accent w-100" disabled
                                                    aria-describedby="vectorize-help">
                                                <span class="btn-text">Vectorize Image</span>
                                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                                <span class="visually-hidden" id="loading-text">Processing...</span>
                                            </button>
                                            <div id="vectorize-help" class="visually-hidden">Convert your uploaded image to SVG format</div>
                                        </form>
                                        
                                        <!-- Progress Area -->
                                        <div id="progressArea" class="mt-4 d-none" role="status" aria-live="polite">
                                            <div class="progress mb-3" role="progressbar" aria-label="Image processing progress">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                                            </div>
                                            <p class="text-center mb-0">Processing your image...</p>
                                        </div>
                                        
                                        <!-- Result Area -->
                                        <div id="resultArea" class="mt-4 d-none" role="region" aria-labelledby="result-heading">
                                            <div class="alert alert-success">
                                                <h3 id="result-heading" class="h5">✅ Vectorization Complete!</h3>
                                                <div id="svgPreview" class="text-center my-3" aria-label="SVG preview"></div>
                                                <a id="downloadLink" class="btn btn-accent w-100" download 
                                                   aria-describedby="download-help">Download SVG</a>
                                                <div id="download-help" class="visually-hidden">Download your vectorized image as SVG file</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Error Area -->
                                        <div id="errorArea" class="mt-4 d-none" role="alert" aria-live="assertive">
                                            <div class="alert alert-danger">
                                                <h3 class="h5">❌ Processing Failed</h3>
                                                <p id="errorMessage" class="mb-0"></p>
                                            </div>
                                        </div>
                                        
                                        <!-- Coins Display -->
                                        <div class="coins-display mt-3 text-center" aria-label="Account status">
                                            <small class="text-muted">
                                                Credits remaining: <span class="text-accent fw-bold" id="coinsCount"><?php echo getUserCoinsRemaining($_SESSION['user_id']); ?></span>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center">
                                            <p class="mb-3">Sign up to start vectorizing images for free</p>
                                            <a href="register.php" class="btn btn-accent w-100" 
                                               aria-describedby="signup-benefit">Create Free Account</a>
                                            <p class="mt-3 small text-muted">
                                                Already have an account? <a href="login.php" class="text-accent">Sign in</a>
                                            </p>
                                            <div id="signup-benefit" class="visually-hidden">Get free credits to start vectorizing images</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-5 bg-light" aria-labelledby="features-heading">
            <div class="container">
                <header class="row">
                    <div class="col-lg-12 text-center mb-5">
                        <h2 id="features-heading" class="display-5 fw-bold">Why Choose VectraHub for Image Vectorization?</h2>
                        <p class="lead">Advanced AI-powered vectorization with professional results for designers and businesses</p>
                    </div>
                </header>
                <div class="row g-4" role="list">
                    <article class="col-md-4" role="listitem">
                        <div class="feature-card text-center h-100">
                            <div class="feature-icon" aria-hidden="true">🤖</div>
                            <h3 class="h4">AI-Powered Upscaling</h3>
                            <p>Waifu2x technology enhances image quality before vectorization, ensuring superior results for logos and graphics.</p>
                        </div>
                    </article>
                    <article class="col-md-4" role="listitem">
                        <div class="feature-card text-center h-100">
                            <div class="feature-icon" aria-hidden="true">⚡</div>
                            <h3 class="h4">Lightning Fast Processing</h3>
                            <p>Process images in seconds with our optimized pipeline. Perfect for designers with tight deadlines.</p>
                        </div>
                    </article>
                    <article class="col-md-4" role="listitem">
                        <div class="feature-card text-center h-100">
                            <div class="feature-icon" aria-hidden="true">🎯</div>
                            <h3 class="h4">Precision Vectorization</h3>
                            <p>VTracer engine creates clean, scalable SVGs with minimal artifacts - ideal for print and embroidery.</p>
                        </div>
                    </article>
                    <article class="col-md-4" role="listitem">
                        <div class="feature-card text-center h-100">
                            <div class="feature-icon" aria-hidden="true">📦</div>
                            <h3 class="h4">Batch Upload Support</h3>
                            <p>Upload multiple images at once for bulk vectorization. Save time on large projects and workflows.</p>
                        </div>
                    </article>
                    <article class="col-md-4" role="listitem">
                        <div class="feature-card text-center h-100">
                            <div class="feature-icon" aria-hidden="true">🎨</div>
                            <h3 class="h4">Perfect for Print Design</h3>
                            <p>Optimized for t-shirt printing, embroidery, and professional graphics. Clean vectors every time.</p>
                        </div>
                    </article>
                    <article class="col-md-4" role="listitem">
                        <div class="feature-card text-center h-100">
                            <div class="feature-icon" aria-hidden="true">💯</div>
                            <h3 class="h4">100% Free to Use</h3>
                            <p>No hidden fees or watermarks. Free tier includes generous limits for students and small businesses.</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="py-5" aria-labelledby="how-it-works-heading">
            <div class="container">
                <header class="row">
                    <div class="col-lg-12 text-center mb-5">
                        <h2 id="how-it-works-heading" class="display-5 fw-bold">How to Vectorize Images in 4 Simple Steps</h2>
                        <p class="lead">Transform your images to scalable SVG vectors in minutes</p>
                    </div>
                </header>
                <div class="row g-4" role="list">
                    <article class="col-md-3" role="listitem">
                        <div class="step-card text-center h-100">
                            <div class="step-number" aria-label="Step 1">1</div>
                            <h3 class="h5">Upload Your Image</h3>
                            <p>Drop your PNG or JPG file, or paste an image URL. Supports files up to 5MB.</p>
                        </div>
                    </article>
                    <article class="col-md-3" role="listitem">
                        <div class="step-card text-center h-100">
                            <div class="step-number" aria-label="Step 2">2</div>
                            <h3 class="h5">AI Enhancement</h3>
                            <p>Our Waifu2x AI upscales and enhances your image quality for better vectorization results.</p>
                        </div>
                    </article>
                    <article class="col-md-3" role="listitem">
                        <div class="step-card text-center h-100">
                            <div class="step-number" aria-label="Step 3">3</div>
                            <h3 class="h5">Vector Conversion</h3>
                            <p>VTracer engine converts the enhanced image to crisp, scalable SVG format automatically.</p>
                        </div>
                    </article>
                    <article class="col-md-3" role="listitem">
                        <div class="step-card text-center h-100">
                            <div class="step-number" aria-label="Step 4">4</div>
                            <h3 class="h5">Download & Use</h3>
                            <p>Get your perfect SVG file instantly. Ready for print, web, or any design project.</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Use Cases Section -->
        <section class="py-5 bg-light" aria-labelledby="use-cases-heading">
            <div class="container">
                <header class="text-center mb-5">
                    <h2 id="use-cases-heading" class="display-5 fw-bold">Perfect for Every Design Need</h2>
                    <p class="lead">From logos to graphics, our vectorizer handles it all</p>
                </header>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="use-case-card text-center">
                            <div class="use-case-icon">👕</div>
                            <h3 class="h5">T-Shirt Printing</h3>
                            <p>Convert logos and designs for crisp t-shirt prints</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="use-case-card text-center">
                            <div class="use-case-icon">🧵</div>
                            <h3 class="h5">Embroidery</h3>
                            <p>Create clean vectors perfect for embroidery machines</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="use-case-card text-center">
                            <div class="use-case-icon">🎨</div>
                            <h3 class="h5">Logo Design</h3>
                            <p>Transform raster logos into scalable vector formats</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="use-case-card text-center">
                            <div class="use-case-icon">📚</div>
                            <h3 class="h5">Student Projects</h3>
                            <p>Free tool for students and educational purposes</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-5" aria-labelledby="faq-heading">
            <div class="container">
                <header class="text-center mb-5">
                    <h2 id="faq-heading" class="display-5 fw-bold">Frequently Asked Questions</h2>
                </header>
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h3 class="accordion-header" id="faq1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                                        How to vectorize an image for free online?
                                    </button>
                                </h3>
                                <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="faq1" 
                                     data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Simply upload your JPG or PNG image to VectraHub, and our AI-powered tool will automatically convert it to a scalable SVG vector format. The process takes seconds and is completely free with no watermarks or registration required.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h3 class="accordion-header" id="faq2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                        What image formats can I convert to SVG?
                                    </button>
                                </h3>
                                <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" 
                                     data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        VectraHub supports JPG, JPEG, and PNG image formats. Files can be up to 5MB in size. For best results, use high-resolution images with clear, defined shapes and minimal noise.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h3 class="accordion-header" id="faq3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                        Is the vectorized SVG suitable for printing?
                                    </button>
                                </h3>
                                <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" 
                                     data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Yes! Our AI-enhanced vectorization creates print-ready SVG files perfect for t-shirts, business cards, posters, and professional printing. The vectors are scalable without quality loss.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

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
                        <li><a href="/privacy/" class="text-light">Privacy Policy</a></li>
                        <li><a href="/terms/" class="text-light">Terms of Service</a></li>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="assets/js/upload.js" defer></script>
    
    <!-- Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'GA_MEASUREMENT_ID');
    </script>
</body>
</html>
