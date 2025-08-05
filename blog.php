<?php 
require_once 'php/config.php';
require_once 'php/utils.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/images/vectra-hub-logo2.png" type="image/png">
    <title>Blog - Image Vectorization Tips & Tutorials - VectraHub</title>
    <meta name="description" content="Learn about image vectorization, design tips, and how to get the best results with VectraHub. Expert tutorials and guides for designers.">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .blog-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .blog-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .blog-meta {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .blog-excerpt {
            color: #6c757d;
            line-height: 1.6;
        }
        
        .category-badge {
            background: linear-gradient(45deg, #20c997, #17a2b8);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
                    <img src="assets/images/vectra-hub-logo.png" alt="VectraHub Logo" height="32" class="me-2">
                    <span class="text-accent">Vectra</span><span class="text-light">Hub</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pricing.php">Pricing</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="api_documentation.php">API</a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="php/auth/logout.php">Logout</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-accent ms-2" href="register.php">Start Free</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="py-5">
        <div class="container">
            <!-- Header Section -->
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h1 class="display-4 fw-bold mb-3">VectraHub Blog</h1>
                    <p class="lead text-muted">Expert tips, tutorials, and insights on image vectorization and design</p>
                </div>
            </div>

            <!-- Featured Article -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card blog-card shadow-lg">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <img src="https://images.unsplash.com/photo-1561070791-2526d30994b5?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                                     class="blog-image" alt="Vector Design">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body p-4">
                                    <span class="category-badge mb-2">Featured</span>
                                    <h2 class="card-title h3">Complete Guide to Image Vectorization for Print Design</h2>
                                    <div class="blog-meta mb-3">
                                        <i class="fas fa-calendar me-2"></i>December 15, 2024
                                        <i class="fas fa-user ms-3 me-2"></i>By VectraHub Team
                                        <i class="fas fa-clock ms-3 me-2"></i>8 min read
                                    </div>
                                    <p class="blog-excerpt">
                                        Learn everything you need to know about converting raster images to vector format for professional printing. 
                                        Discover the best practices, common pitfalls, and how to achieve perfect results every time.
                                    </p>
                                    <a href="#" class="btn btn-accent">Read Full Article</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Blog Grid -->
            <div class="row g-4">
                <!-- Article 1 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card blog-card h-100">
                        <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             class="blog-image" alt="Logo Design">
                        <div class="card-body">
                            <span class="category-badge mb-2">Tutorial</span>
                            <h3 class="card-title h5">How to Vectorize Logos for T-Shirt Printing</h3>
                            <div class="blog-meta mb-2">
                                <i class="fas fa-calendar me-2"></i>December 10, 2024
                                <i class="fas fa-clock ms-3 me-2"></i>5 min read
                            </div>
                            <p class="blog-excerpt">
                                Step-by-step guide to converting your logo designs into print-ready vector formats. 
                                Perfect for t-shirt printing and embroidery.
                            </p>
                            <a href="#" class="btn btn-outline-accent btn-sm">Read More</a>
                        </div>
                    </div>
                </div>

                <!-- Article 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card blog-card h-100">
                        <img src="https://images.unsplash.com/photo-1558655146-d09347e92766?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             class="blog-image" alt="AI Technology">
                        <div class="card-body">
                            <span class="category-badge mb-2">Technology</span>
                            <h3 class="card-title h5">AI-Powered Vectorization: The Future of Design</h3>
                            <div class="blog-meta mb-2">
                                <i class="fas fa-calendar me-2"></i>December 8, 2024
                                <i class="fas fa-clock ms-3 me-2"></i>6 min read
                            </div>
                            <p class="blog-excerpt">
                                Explore how artificial intelligence is revolutionizing the way we convert images to vectors. 
                                Learn about the latest advancements and what's coming next.
                            </p>
                            <a href="#" class="btn btn-outline-accent btn-sm">Read More</a>
                        </div>
                    </div>
                </div>

                <!-- Article 3 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card blog-card h-100">
                        <img src="https://images.unsplash.com/photo-1560472354-b33ff0c44a43?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             class="blog-image" alt="Design Tips">
                        <div class="card-body">
                            <span class="category-badge mb-2">Tips</span>
                            <h3 class="card-title h5">10 Essential Tips for Better Vectorization Results</h3>
                            <div class="blog-meta mb-2">
                                <i class="fas fa-calendar me-2"></i>December 5, 2024
                                <i class="fas fa-clock ms-3 me-2"></i>4 min read
                            </div>
                            <p class="blog-excerpt">
                                Master the art of image vectorization with these proven tips and techniques. 
                                Improve your workflow and get better results every time.
                            </p>
                            <a href="#" class="btn btn-outline-accent btn-sm">Read More</a>
                        </div>
                    </div>
                </div>

                <!-- Article 4 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card blog-card h-100">
                        <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             class="blog-image" alt="Batch Processing">
                        <div class="card-body">
                            <span class="category-badge mb-2">Workflow</span>
                            <h3 class="card-title h5">Batch Processing: Save Time with Multiple Images</h3>
                            <div class="blog-meta mb-2">
                                <i class="fas fa-calendar me-2"></i>December 3, 2024
                                <i class="fas fa-clock ms-3 me-2"></i>7 min read
                            </div>
                            <p class="blog-excerpt">
                                Learn how to efficiently process multiple images at once using VectraHub's batch converter. 
                                Perfect for large projects and tight deadlines.
                            </p>
                            <a href="#" class="btn btn-outline-accent btn-sm">Read More</a>
                        </div>
                    </div>
                </div>

                <!-- Article 5 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card blog-card h-100">
                        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             class="blog-image" alt="File Formats">
                        <div class="card-body">
                            <span class="category-badge mb-2">Education</span>
                            <h3 class="card-title h5">Understanding Vector File Formats: SVG vs AI vs EPS</h3>
                            <div class="blog-meta mb-2">
                                <i class="fas fa-calendar me-2"></i>November 30, 2024
                                <i class="fas fa-clock ms-3 me-2"></i>9 min read
                            </div>
                            <p class="blog-excerpt">
                                Compare different vector file formats and learn when to use each one. 
                                Make informed decisions for your design projects.
                            </p>
                            <a href="#" class="btn btn-outline-accent btn-sm">Read More</a>
                        </div>
                    </div>
                </div>

                <!-- Article 6 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card blog-card h-100">
                        <img src="https://images.unsplash.com/photo-1518709268805-4e9042af2176?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             class="blog-image" alt="API Integration">
                        <div class="card-body">
                            <span class="category-badge mb-2">Development</span>
                            <h3 class="card-title h5">Integrating VectraHub API into Your Applications</h3>
                            <div class="blog-meta mb-2">
                                <i class="fas fa-calendar me-2"></i>November 28, 2024
                                <i class="fas fa-clock ms-3 me-2"></i>10 min read
                            </div>
                            <p class="blog-excerpt">
                                Learn how to integrate VectraHub's vectorization API into your web applications, 
                                mobile apps, and design software.
                            </p>
                            <a href="#" class="btn btn-outline-accent btn-sm">Read More</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Newsletter Signup -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card bg-accent text-white text-center py-5">
                        <div class="card-body">
                            <h3 class="mb-3">Stay Updated with Design Tips</h3>
                            <p class="mb-4">Get the latest vectorization tips, tutorials, and VectraHub updates delivered to your inbox.</p>
                            <form class="row justify-content-center">
                                <div class="col-md-6 col-lg-4">
                                    <div class="input-group">
                                        <input type="email" class="form-control" placeholder="Enter your email" required>
                                        <button class="btn btn-light" type="submit">Subscribe</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 