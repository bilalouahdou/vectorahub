<?php 
require_once 'php/config.php';
require_once 'php/utils.php';

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$coinsRemaining = $isLoggedIn ? getUserCoinsRemaining($userId) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/images/vectra-hub-logo2.png" type="image/png">
    <title>Batch Image Vectorizer - Convert Multiple Images to SVG - VectraHub</title>
    <meta name="description" content="Convert multiple images to SVG format at once with our batch vectorizer. Perfect for bulk processing logos, graphics, and designs.">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .batch-upload-area {
            border: 3px dashed #20c997;
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            background: rgba(32, 201, 151, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .batch-upload-area:hover {
            border-color: #17a2b8;
            background: rgba(32, 201, 151, 0.1);
        }
        
        .batch-upload-area.dragover {
            border-color: #17a2b8;
            background: rgba(32, 201, 151, 0.15);
            transform: scale(1.02);
        }
        
        .file-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }
        
        .progress-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #20c997;
        }
        
        .progress-item.processing {
            border-left-color: #ffc107;
        }
        
        .progress-item.completed {
            border-left-color: #28a745;
        }
        
        .progress-item.failed {
            border-left-color: #dc3545;
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
                            <a class="nav-link" href="pricing">Pricing</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="api_documentation">API</a>
                        </li>
                        <?php if ($isLoggedIn): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="billing">Billing</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="referral">Referrals</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="ad-rewards">Earn Coins</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="php/auth/logout.php">Logout</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-accent ms-2" href="register">Start Free</a>
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
                    <h1 class="display-4 fw-bold mb-3">Batch Image Vectorizer</h1>
                    <p class="lead text-muted">Convert multiple images to SVG format at once. Perfect for bulk processing logos, graphics, and designs.</p>
                    <?php if ($isLoggedIn): ?>
                        <div class="d-flex justify-content-center align-items-center gap-4 mt-4">
                            <div class="text-center">
                                <h5 class="text-accent mb-1"><?php echo number_format($coinsRemaining); ?></h5>
                                <small class="text-muted">Credits Remaining</small>
                            </div>
                            <div class="text-center">
                                <h5 class="text-accent mb-1">5MB</h5>
                                <small class="text-muted">Max File Size</small>
                            </div>
                            <div class="text-center">
                                <h5 class="text-accent mb-1">10</h5>
                                <small class="text-muted">Max Files</small>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info d-inline-block mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Login Required:</strong> Please <a href="login" class="alert-link">sign in</a> or <a href="register" class="alert-link">create an account</a> to use the batch converter.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upload Section -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-lg">
                        <div class="card-body p-4">
                            <h2 class="card-title text-center mb-4">Upload Multiple Images</h2>
                            
                            <form id="batchUploadForm" enctype="multipart/form-data">
                                <?php if ($isLoggedIn && isset($_SESSION['csrf_token'])): ?>
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <?php endif; ?>
                                
                                <!-- Upload Area -->
                                <div class="batch-upload-area" id="batchUploadArea">
                                    <div class="upload-content">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-accent mb-3"></i>
                                        <h4>Drop your images here or click to browse</h4>
                                        <p class="text-muted mb-3">Supports PNG, JPG up to 5MB each. Maximum 10 files.</p>
                                        <button type="button" class="btn btn-accent" onclick="document.getElementById('batchFileInput').click()">
                                            <i class="fas fa-folder-open me-2"></i>Select Files
                                        </button>
                                    </div>
                                    <input type="file" id="batchFileInput" name="images[]" multiple accept=".png,.jpg,.jpeg" class="d-none">
                                </div>
                                
                                <!-- File Preview -->
                                <div id="filePreview" class="mt-4 d-none">
                                    <h5>Selected Files:</h5>
                                    <div id="fileList" class="row g-2"></div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="text-center mt-4">
                                    <button type="submit" id="processBatchBtn" class="btn btn-accent btn-lg" <?php echo !$isLoggedIn ? 'disabled' : ''; ?>>
                                        <span class="btn-text"><?php echo $isLoggedIn ? 'Process All Images' : 'Login to Process Images'; ?></span>
                                        <span class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                                    </button>
                                    <p class="text-muted mt-2">Each image costs 1 credit</p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Section -->
            <div id="progressSection" class="row mt-5 d-none">
                <div class="col-12">
                    <h3 class="text-center mb-4">Processing Progress</h3>
                    <div id="progressList"></div>
                </div>
            </div>

            <!-- Results Section -->
            <div id="resultsSection" class="row mt-5 d-none">
                <div class="col-12">
                    <h3 class="text-center mb-4">Download Results</h3>
                    <div id="resultsList" class="row g-3"></div>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedFiles = [];
        let processingJobs = [];

        // File selection handling
        document.getElementById('batchFileInput').addEventListener('change', function(e) {
            handleFiles(e.target.files);
        });

        // Drag and drop handling
        const uploadArea = document.getElementById('batchUploadArea');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        function handleFiles(files) {
            selectedFiles = Array.from(files).slice(0, 10); // Limit to 10 files
            displayFilePreview();
            updateSubmitButton();
        }

        function displayFilePreview() {
            const previewDiv = document.getElementById('filePreview');
            const fileList = document.getElementById('fileList');
            
            if (selectedFiles.length === 0) {
                previewDiv.classList.add('d-none');
                return;
            }
            
            previewDiv.classList.remove('d-none');
            fileList.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const fileDiv = document.createElement('div');
                fileDiv.className = 'col-md-3 col-sm-4 col-6';
                fileDiv.innerHTML = `
                    <div class="card">
                        <img src="${URL.createObjectURL(file)}" class="file-preview w-100" alt="${file.name}">
                        <div class="card-body p-2">
                            <small class="text-muted d-block">${file.name}</small>
                            <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                        </div>
                    </div>
                `;
                fileList.appendChild(fileDiv);
            });
        }

        function updateSubmitButton() {
            const submitBtn = document.getElementById('processBatchBtn');
            submitBtn.disabled = selectedFiles.length === 0;
        }

        // Form submission
        document.getElementById('batchUploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (selectedFiles.length === 0) return;
            
            const formData = new FormData();
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            selectedFiles.forEach(file => {
                formData.append('images[]', file);
            });
            
            const submitBtn = document.getElementById('processBatchBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner-border');
            
            submitBtn.disabled = true;
            btnText.textContent = 'Processing...';
            spinner.classList.remove('d-none');
            
            // Show progress section
            document.getElementById('progressSection').classList.remove('d-none');
            initializeProgress();
            
            try {
                const response = await fetch('php/batch_upload.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    processingJobs = result.jobs;
                    startProcessing();
                } else {
                    alert('Error: ' + (result.error || 'Failed to upload files'));
                    resetForm();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Network error. Please try again.');
                resetForm();
            }
        });

        function initializeProgress() {
            const progressList = document.getElementById('progressList');
            progressList.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const progressItem = document.createElement('div');
                progressItem.className = 'progress-item';
                progressItem.id = `progress-${index}`;
                progressItem.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">${file.name}</h6>
                            <small class="text-muted">Queued</small>
                        </div>
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                    </div>
                `;
                progressList.appendChild(progressItem);
            });
        }

        function startProcessing() {
            processingJobs.forEach((job, index) => {
                processJob(job, index);
            });
        }

        async function processJob(job, index) {
            const progressItem = document.getElementById(`progress-${index}`);
            
            // Update status to processing
            progressItem.classList.add('processing');
            progressItem.querySelector('small').textContent = 'Processing...';
            
            try {
                const response = await fetch(`php/check_job_status.php?job_id=${job.id}`);
                const result = await response.json();
                
                if (result.status === 'done') {
                    // Job completed
                    progressItem.classList.remove('processing');
                    progressItem.classList.add('completed');
                    progressItem.querySelector('small').textContent = 'Completed';
                    progressItem.querySelector('.spinner-border').innerHTML = '<i class="fas fa-check text-success"></i>';
                } else if (result.status === 'failed') {
                    // Job failed
                    progressItem.classList.remove('processing');
                    progressItem.classList.add('failed');
                    progressItem.querySelector('small').textContent = 'Failed';
                    progressItem.querySelector('.spinner-border').innerHTML = '<i class="fas fa-times text-danger"></i>';
                } else {
                    // Still processing, check again in 2 seconds
                    setTimeout(() => processJob(job, index), 2000);
                }
            } catch (error) {
                console.error('Error checking job status:', error);
                progressItem.classList.add('failed');
                progressItem.querySelector('small').textContent = 'Error';
            }
        }

        function resetForm() {
            const submitBtn = document.getElementById('processBatchBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner-border');
            
            submitBtn.disabled = false;
            btnText.textContent = 'Process All Images';
            spinner.classList.add('d-none');
        }
    </script>
</body>
</html> 