<?php
// Start output buffering to prevent headers already sent errors
ob_start();

// Include required files first
require_once 'php/config.php';
require_once 'php/utils.php';

redirectIfNotAuth();

$subscription = getCurrentUserSubscription($_SESSION['user_id']);
$coinsRemaining = getUserCoinsRemaining($_SESSION['user_id']);

// Check if user has Ultimate plan for bulk features
$hasUltimatePlan = ($subscription['name'] ?? '') === 'Ultimate';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VectorizeAI</title>
    <link rel="icon" href="assets/images/vectra-hub-logo2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <style>
        .preview-container {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        
        .preview-box {
            flex: 1;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
        }
        .preview-box h6 {
            margin-bottom: 15px;
            color: #666;
            font-weight: 600;
        }
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .preview-image:hover {
            transform: scale(1.05);
        }
        .preview-placeholder {
            width: 100%;
            height: 150px;
            background: #e9ecef;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 14px;
        }
        
        /* Bulk Upload Styles */
        .mode-toggle {
            display: flex;
            background: #f8f9fa;
            border-radius: 25px;
            padding: 4px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .mode-toggle button {
            flex: 1;
            padding: 10px 20px;
            border: none;
            background: transparent;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .mode-toggle button.active {
            background: #00d4aa;
            color: white;
            box-shadow: 0 2px 8px rgba(0, 212, 170, 0.3);
        }
        
        .bulk-upload-area {
            border: 2px dashed #00d4aa;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            background: linear-gradient(135deg, #f8fffd 0%, #e8f9f5 100%);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .bulk-upload-area:hover {
            border-color: #00b894;
            background: linear-gradient(135deg, #f0fffe 0%, #d1f2e8 100%);
        }
        
        .bulk-upload-area.dragover {
            border-color: #00b894;
            background: linear-gradient(135deg, #e8f9f5 0%, #b8e6d3 100%);
            transform: scale(1.02);
        }
        
        .bulk-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .bulk-preview-item {
            position: relative;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            transition: all 0.3s ease;
        }
        
        .bulk-preview-item:hover {
            border-color: #00d4aa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 212, 170, 0.2);
        }
        
        .bulk-preview-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        
        .bulk-preview-item .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .bulk-preview-item .status {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px;
            font-size: 12px;
            text-align: center;
        }
        
        .bulk-preview-item .status.processing {
            background: rgba(255, 193, 7, 0.9);
            color: #000;
        }
        
        .bulk-preview-item .status.completed {
            background: rgba(40, 167, 69, 0.9);
        }
        
        .bulk-preview-item .status.failed {
            background: rgba(220, 53, 69, 0.9);
        }
        
        .bulk-progress {
            margin-top: 20px;
        }
        
        .bulk-results {
            margin-top: 20px;
        }
        
        .bulk-download-btn {
            background: linear-gradient(135deg, #00d4aa 0%, #00b894 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 212, 170, 0.3);
        }
        
        .bulk-download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 212, 170, 0.4);
        }
        
        .upgrade-notice {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        /* Image Modal Styles */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(5px);
        }
        
        .image-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content-wrapper {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .modal-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            background: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            max-width: 100%;
            max-height: 80vh;
        }
        
        .modal-image {
            display: block;
            max-width: 100%;
            max-height: 100%;
            transition: transform 0.3s ease;
            cursor: grab;
        }
        
        .modal-image:active {
            cursor: grabbing;
        }
        
        .modal-controls {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 25px;
            backdrop-filter: blur(10px);
        }
        
        .modal-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .modal-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        .modal-title {
            color: white;
            margin-bottom: 15px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
        }
        
        .zoom-info {
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            margin-top: 10px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .preview-container {
                flex-direction: column;
            }
            
            .bulk-preview-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 10px;
            }
            
            .modal-controls {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .modal-btn {
                font-size: 12px;
                padding: 8px 12px;
            }
        }

        .current-processing-status {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border: 1px solid #e1bee7;
            border-radius: 8px;
            padding: 15px;
        }

        .processing-queue {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            max-height: 200px;
            overflow-y: auto;
        }

        .queue-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .queue-item.pending {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .queue-item.processing {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            animation: pulse 1.5s infinite;
        }

        .queue-item.completed {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }

        .queue-item.failed {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .queue-item-icon {
            margin-right: 10px;
            font-size: 16px;
        }

        .queue-item-name {
            flex: 1;
            font-weight: 500;
        }

        .queue-item-status {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }

        .queue-item.pending .queue-item-status {
            background: #fff3cd;
            color: #856404;
        }

        .queue-item.processing .queue-item-status {
            background: #d1ecf1;
            color: #0c5460;
        }

        .queue-item.completed .queue-item-status {
            background: #d4edda;
            color: #155724;
        }

        .queue-item.failed .queue-item-status {
            background: #f8d7da;
            color: #721c24;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .processing-step {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .processing-step.active {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
            border-left: 4px solid #28a745;
        }

        .processing-step.completed {
            background: #f8f9fa;
            opacity: 0.7;
        }

        .step-icon {
            margin-right: 10px;
            font-size: 18px;
        }

        .step-text {
            flex: 1;
            font-weight: 500;
        }

        .step-time {
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="dashboard-sidebar p-3" style="width: 250px;">
            <div class="text-center mb-4">
                <h4 class="fw-bold">
                    <span class="text-accent">Vectora</span>hub
                </h4>
            </div>
            
            <div class="mb-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title">Current Plan</h6>
                        <h5 class="text-accent"><?php echo htmlspecialchars($subscription['name'] ?? 'Free'); ?></h5>
                        <p class="mb-1"><strong><?php echo $coinsRemaining; ?></strong> coins left</p>
                        <?php if ($coinsRemaining <= 5): ?>
                            <a href="pricing" class="btn btn-accent btn-sm">Upgrade</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <ul class="nav flex-column sidebar-nav">
                <li class="nav-item">
                    <a class="nav-link active" href="#" data-section="overview">
                        📊 Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-section="upload">
                        📤 New Upload
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="#" data-section="history">
                        📁 History
                    </a>
                </li> -->
                <li class="nav-item">
                    <a class="nav-link" href="pricing">
                        💳 Pricing
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="referral">
                        🎁 Referral Program
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="php/admin/">
                            ⚙️ Admin Panel
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item mt-3">
                                            <a class="nav-link text-danger" href="php/auth/logout.php">
                        🚪 Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="flex-grow-1 p-4">
            <!-- Overview Section -->
            <div id="overview-section" class="section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>!</h2>
                    <a href="ad-rewards" class="btn btn-accent">
                        🎯 Earn Coins
                    </a>
                </div>
                
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="admin-card">
                            <div class="admin-stat">
                                <div class="admin-stat-number" id="totalJobs">-</div>
                                <div>Total Jobs</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="admin-card">
                            <div class="admin-stat">
                                <div class="admin-stat-number" id="successfulJobs">-</div>
                                <div>Successful</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="admin-card">
                            <div class="admin-stat">
                                <div class="admin-stat-number"><?php echo $coinsRemaining; ?></div>
                                <div>Coins Left</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="admin-card">
                            <h5>Recent Jobs</h5>
                            <div id="recentJobs">Loading...</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Upload Section -->
            <div id="upload-section" class="section d-none">
                <h2 class="mb-4">Upload Images</h2>
                
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="card">
                            <div class="card-body p-4">
                                <?php if ($hasUltimatePlan): ?>
                                    <!-- Mode Toggle for Ultimate Users -->
                                    <div class="mode-toggle">
                                        <button type="button" id="singleModeBtn" class="active">
                                            📄 Single Image
                                        </button>
                                        <button type="button" id="bulkModeBtn">
                                            📚 Bulk Upload (up to 12)
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <!-- Upgrade Notice for Free Users -->
                                    <div class="upgrade-notice">
                                        <h5>🚀 Want to upload multiple images at once?</h5>
                                        <p class="mb-3">Upgrade to Ultimate plan to unlock bulk vectorization (up to 12 images)</p>
                                        <a href="pricing" class="btn btn-light">Upgrade Now</a>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Single Upload Form -->
                                <div id="singleUploadForm">
                                    <?php if ($hasUltimatePlan): ?>
                                    <!-- Single Mode Selection (Normal/Black & White) -->
                                    <div class="mode-toggle mb-3" id="singleModeSelection">
                                        <button type="button" id="singleNormalModeBtn" class="active">
                                            🎨 Normal Mode
                                        </button>
                                        <button type="button" id="singleBWModeBtn">
                                            ⚫ Black & White Only
                                        </button>
                                    </div>
                                    <?php endif; ?>

                                    <form id="uploadForm" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="upload_mode" value="single">
                                        
                                        <!-- File Upload Area -->
                                        <div class="upload-area mb-4" id="uploadArea">
                                            <div class="upload-content">
                                                <i class="upload-icon">📁</i>
                                                <p class="mb-2">Drop your image here or click to browse</p>
                                                <small class="text-muted">PNG, JPG up to 5MB</small>
                                            </div>
                                            <input type="file" id="imageFile" name="image" accept=".png,.jpg,.jpeg" class="d-none">
                                        </div>
                                        
                                        <div class="text-center my-3">
                                            <span class="text-muted">OR</span>
                                        </div>
                                        
                                        <!-- URL Input -->
                                        <div class="mb-3">
                                            <input type="url" id="imageUrl" name="image_url" class="form-control" placeholder="Paste image URL...">
                                        </div>
                                        
                                        <!-- Image Preview Area -->
                                        <div id="imagePreviewArea" class="d-none mb-4">
                                            <div class="preview-container">
                                                <div class="preview-box">
                                                    <h6>Original Image</h6>
                                                    <div id="originalImagePreview">
                                                        <div class="preview-placeholder">Original image will appear here</div>
                                                    </div>
                                                </div>
                                                <div class="preview-box">
                                                    <h6>Vectorized Result</h6>
                                                    <div id="vectorizedImagePreview">
                                                        <div class="preview-placeholder">Vectorized image will appear here</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- New badge for black and white detection -->
                                            <span id="blackImageBadge" class="badge bg-dark text-light d-none mb-2">Black & White Image Detected!</span>
                                        </div>
                                        
                                        <button type="submit" id="vectorizeBtn" class="btn btn-accent w-100" disabled>
                                            <span class="btn-text">Vectorize Image</span>
                                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Bulk Upload Form (Ultimate Only) -->
                                <?php if ($hasUltimatePlan): ?>
                                <div id="bulkUploadForm" class="d-none">
                                    <form id="bulkForm" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="upload_mode" value="bulk">
                                        
                                        <!-- Bulk Upload Area -->
                                        <div class="bulk-upload-area" id="bulkUploadArea">
                                            <div class="bulk-upload-content">
                                                <i style="font-size: 48px;">📚</i>
                                                <h4 class="mt-3 mb-2">Drop multiple images here</h4>
                                                <p class="mb-3">Upload up to 12 images at once for bulk vectorization</p>
                                                <button type="button" class="btn btn-accent" onclick="document.getElementById('bulkImageFiles').click()">
                                                    Choose Images
                                                </button>
                                                <p class="mt-2 text-muted small">PNG, JPG up to 5MB each</p>
                                            </div>
                                            <input type="file" id="bulkImageFiles" name="images[]" accept=".png,.jpg,.jpeg" multiple class="d-none">
                                        </div>
                                        
                                        <!-- Bulk Preview Grid -->
                                        <div id="bulkPreviewGrid" class="bulk-preview-grid d-none"></div>
                                        
                                        <!-- Bulk Submit Button -->
                                        <button type="submit" id="bulkVectorizeBtn" class="btn btn-accent w-100 d-none" disabled>
                                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                            <span class="btn-text">Vectorize All Images</span>
                                        </button>
                                    </form>
                                    
                                    <!-- Bulk Progress -->
                                    <div id="bulkProgress" class="bulk-progress d-none">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Processing Images</h5>
                                            </div>
                                            <div class="card-body">
                                                <!-- Current Processing Status -->
                                                <div class="mb-3">
                                                    <h6 id="currentProcessingText">Starting bulk upload...</h6>
                                                    <small id="currentProcessingDetails" class="text-muted">Preparing to process images</small>
                                                </div>
                                                
                                                <!-- Progress Bar -->
                                                <div class="progress mb-3" style="height: 25px;">
                                                    <div id="bulkProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 0%"></div>
                                                </div>
                                                <div class="text-center">
                                                    <small class="text-muted">Progress: <span id="bulkProgressText">0 / 0</span></small>
                                                </div>
                                                
                                                <!-- Processing Queue -->
                                                <div class="mt-4">
                                                    <h6>Processing Queue:</h6>
                                                    <div id="queueList" class="queue-list"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Bulk Results -->
                                    <div id="bulkResults" class="bulk-results d-none">
                                        <div class="alert alert-success">
                                            <h5>✅ Bulk Vectorization Complete!</h5>
                                            <p class="mb-3">All images have been processed. Click below to download all SVG files.</p>
                                            <button type="button" id="bulkDownloadBtn" class="bulk-download-btn w-100">
                                                📥 Download All SVG Files
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Progress Area -->
                                <div id="progressArea" class="mt-4 d-none">
                                    <div class="progress mb-3">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                    </div>
                                    <p class="text-center mb-0">Processing your image...</p>
                                </div>
                                
                                <!-- Result Area -->
                                <div id="resultArea" class="mt-4 d-none">
                                    <div class="alert alert-success">
                                        <h5>✅ Vectorization Complete!</h5>
                                        <p class="mb-3">Your image has been successfully vectorized. You can see the comparison above and download the SVG file below.</p>
                                        <a id="downloadLink" class="btn btn-accent w-100" download>Download SVG</a>
                                    </div>
                                </div>
                                
                                <!-- Error Area -->
                                <div id="errorArea" class="mt-4 d-none">
                                    <div class="alert alert-danger">
                                        <h5>❌ Processing Failed</h5>
                                        <p id="errorMessage" class="mb-0"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- History Section -->
            <div id="history-section" class="section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Job History</h2>
                    <button class="btn btn-outline-secondary" onclick="loadHistory()">
                        🔄 Refresh
                    </button>
                </div>
                
                <div id="historyContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-accent" role="status"></div>
                        <p class="mt-2">Loading history...</p>
                    </div>
                </div>
                
                <nav id="historyPagination" class="d-none">
                    <ul class="pagination justify-content-center"></ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <button class="modal-close" onclick="closeImageModal()">&times;</button>
        <div class="modal-content-wrapper">
            <div class="modal-title" id="modalTitle">Image Preview</div>
            <div class="modal-image-container" id="modalImageContainer">
                <img id="modalImage" class="modal-image" src="/placeholder.svg" alt="Full size image">
            </div>
            <div class="modal-controls">
                <button class="modal-btn" onclick="zoomIn()">
                    🔍 Zoom In
                </button>
                <button class="modal-btn" onclick="zoomOut()">
                    🔍 Zoom Out
                </button>
                <button class="modal-btn" onclick="resetZoom()">
                    🔄 Reset
                </button>
                <button class="modal-btn" onclick="fitToScreen()">
                    📱 Fit Screen
                </button>
            </div>
            <div class="zoom-info">
                Click and drag to pan • Scroll to zoom • ESC to close
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image Modal Functionality
        let currentZoom = 1;
        let isDragging = false;
        let startX, startY, translateX = 0, translateY = 0;

        function openImageModal(imageSrc, title) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalTitle');
            
            modalImage.src = imageSrc;
            modalTitle.textContent = title;
            modal.classList.add('show');
            
            // Reset zoom and position
            currentZoom = 1;
            translateX = 0;
            translateY = 0;
            updateImageTransform();
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function zoomIn() {
            currentZoom = Math.min(currentZoom * 1.2, 5);
            updateImageTransform();
        }

        function zoomOut() {
            currentZoom = Math.max(currentZoom / 1.2, 0.1);
            updateImageTransform();
        }

        function resetZoom() {
            currentZoom = 1;
            translateX = 0;
            translateY = 0;
            updateImageTransform();
        }

        function fitToScreen() {
            const modalImage = document.getElementById('modalImage');
            const container = document.getElementById('modalImageContainer');
            
            const containerRect = container.getBoundingClientRect();
            const imageRect = modalImage.getBoundingClientRect();
            
            const scaleX = (containerRect.width * 0.9) / modalImage.naturalWidth;
            const scaleY = (containerRect.height * 0.9) / modalImage.naturalHeight;
            
            currentZoom = Math.min(scaleX, scaleY);
            translateX = 0;
            translateY = 0;
            updateImageTransform();
        }

        function updateImageTransform() {
            const modalImage = document.getElementById('modalImage');
            modalImage.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentZoom})`;
        }

        // Mouse events for dragging
        document.getElementById('modalImage').addEventListener('mousedown', (e) => {
            if (currentZoom > 1) {
                isDragging = true;
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
                e.preventDefault();
            }
        });

        document.addEventListener('mousemove', (e) => {
            if (isDragging) {
                translateX = e.clientX - startX;
                translateY = e.clientY - startY;
                updateImageTransform();
            }
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
        });

        // Touch events for mobile
        let lastTouchDistance = 0;

        document.getElementById('modalImage').addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                isDragging = true;
                startX = e.touches[0].clientX - translateX;
                startY = e.touches[0].clientY - translateY;
            } else if (e.touches.length === 2) {
                const touch1 = e.touches[0];
                const touch2 = e.touches[1];
                lastTouchDistance = Math.sqrt(
                    Math.pow(touch2.clientX - touch1.clientX, 2) +
                    Math.pow(touch2.clientY - touch1.clientY, 2)
                );
            }
            e.preventDefault();
        });

        document.addEventListener('touchmove', (e) => {
            if (e.touches.length === 1 && isDragging) {
                translateX = e.touches[0].clientX - startX;
                translateY = e.touches[0].clientY - translateY;
                updateImageTransform();
            } else if (e.touches.length === 2) {
                const touch1 = e.touches[0];
                const touch2 = e.touches[1];
                const currentDistance = Math.sqrt(
                    Math.pow(touch2.clientX - touch1.clientX, 2) +
                    Math.pow(touch2.clientY - touch1.clientY, 2)
                );
                
                if (lastTouchDistance > 0) {
                    const scale = currentDistance / lastTouchDistance;
                    currentZoom = Math.min(Math.max(currentZoom * scale, 0.1), 5);
                    updateImageTransform();
                }
                lastTouchDistance = currentDistance;
            }
            e.preventDefault();
        });

        document.addEventListener('touchend', () => {
            isDragging = false;
            lastTouchDistance = 0;
        });

        // Wheel zoom
        document.getElementById('modalImage').addEventListener('wheel', (e) => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            currentZoom = Math.min(Math.max(currentZoom * delta, 0.1), 5);
            updateImageTransform();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (document.getElementById('imageModal').classList.contains('show')) {
                switch(e.key) {
                    case 'Escape':
                        closeImageModal();
                        break;
                    case '+':
                    case '=':
                        zoomIn();
                        break;
                    case '-':
                        zoomOut();
                        break;
                    case '0':
                        resetZoom();
                        break;
                }
            }
        });

        // Close modal when clicking outside
        document.getElementById('imageModal').addEventListener('click', (e) => {
            if (e.target.id === 'imageModal') {
                closeImageModal();
            }
        });
    </script>
    <script src="assets/js/upload.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/dashboard.js"></script>
    <!-- <script src="assets/js/diagnostics.js"></script> -->
    
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
<?php
// End output buffering and flush
ob_end_flush();
?>
